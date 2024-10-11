"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[686],{

/***/ "../../packages/js/components/src/text-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js");









var _excluded = ["className", "onClick"];
function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(this, result);
  };
}
function _isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;
  try {
    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}
/**
 * External dependencies
 */





/**
 * An input field use for text inputs in forms.
 */
var TextControl = (0,_wordpress_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)( /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)(_class, _Component);
  var _super = _createSuper(_class);
  function _class(props) {
    var _this;
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)(this, _class);
    _this = _super.call(this, props);
    _this.state = {
      isFocused: false
    };
    return _this;
  }
  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)(_class, [{
    key: "handleFocusOutside",
    value: function handleFocusOutside() {
      this.setState({
        isFocused: false
      });
    }
  }, {
    key: "handleOnClick",
    value: function handleOnClick(event, onClick) {
      this.setState({
        isFocused: true
      });
      if (typeof onClick === 'function') {
        onClick(event);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var isFocused = this.state.isFocused;
      var _this$props = this.props,
        className = _this$props.className,
        _onClick = _this$props.onClick,
        otherProps = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)(_this$props, _excluded);
      var label = otherProps.label,
        value = otherProps.value,
        disabled = otherProps.disabled;
      var isEmpty = value === '';
      var isActive = isFocused && !disabled;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A)({
        className: classnames__WEBPACK_IMPORTED_MODULE_4___default()('muriel-component', 'muriel-input-text', className, {
          disabled: disabled,
          empty: isEmpty,
          active: isActive,
          'with-value': !isEmpty
        }),
        placeholder: label,
        onClick: function onClick(event) {
          return _this2.handleOnClick(event, _onClick);
        },
        onFocus: function onFocus() {
          return _this2.setState({
            isFocused: true
          });
        }
      }, otherProps));
    }
  }]);
  return _class;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.Component));
TextControl.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * Disables the field.
   */
  disabled: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().bool),
  /**
   * Input label used as a placeholder.
   */
  label: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * On click handler called when the component is clicked, passed the click event.
   */
  onClick: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().func),
  /**
   * The value of the input field.
   */
  value: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TextControl);

/***/ }),

/***/ "../../packages/js/components/src/form/form.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  l: () => (/* binding */ Form)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js");
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);
// EXTERNAL MODULE: ../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js
var runtime = __webpack_require__("../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/setWith.js
var setWith = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/setWith.js");
var setWith_default = /*#__PURE__*/__webpack_require__.n(setWith);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/get.js
var get = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/get.js");
var get_default = /*#__PURE__*/__webpack_require__.n(get);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/clone.js
var clone = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/clone.js");
var clone_default = /*#__PURE__*/__webpack_require__.n(clone);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/isEqual.js
var isEqual = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/isEqual.js");
var isEqual_default = /*#__PURE__*/__webpack_require__.n(isEqual);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/omit.js
var omit = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/omit.js");
var omit_default = /*#__PURE__*/__webpack_require__.n(omit);
;// CONCATENATED MODULE: ../../packages/js/components/src/form/form-context.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

// eslint-disable-next-line @typescript-eslint/no-explicit-any

// eslint-disable-next-line @typescript-eslint/no-explicit-any
var FormContext =
// eslint-disable-next-line @typescript-eslint/no-explicit-any
(0,react.createContext)(
// eslint-disable-next-line @typescript-eslint/no-explicit-any
{});

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function useFormContext() {
  var formContext = useContext(FormContext);
  return formContext;
}
;// CONCATENATED MODULE: ../../packages/js/components/src/form/form.tsx













var _excluded = ["children", "onSubmit", "onChange", "onChanges"],
  _excluded2 = ["className", "onBlur", "onChange", "sanitize"];

function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}




/**
 * External dependencies
 */










/**
 * Internal dependencies
 */

function isChangeEvent(value) {
  return value.target !== undefined;
}
/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FormComponent(_ref, ref) {
  var _props$initialValues, _props$initialValues2;
  var children = _ref.children,
    _ref$onSubmit = _ref.onSubmit,
    onSubmit = _ref$onSubmit === void 0 ? function () {} : _ref$onSubmit,
    _ref$onChange = _ref.onChange,
    onChange = _ref$onChange === void 0 ? function () {} : _ref$onChange,
    _ref$onChanges = _ref.onChanges,
    onChanges = _ref$onChanges === void 0 ? function () {} : _ref$onChanges,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  var initialValues = (0,react.useRef)((_props$initialValues = props.initialValues) !== null && _props$initialValues !== void 0 ? _props$initialValues : {});
  var _useState = (0,react.useState)((_props$initialValues2 = props.initialValues) !== null && _props$initialValues2 !== void 0 ? _props$initialValues2 : {}),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    values = _useState2[0],
    setValuesInternal = _useState2[1];
  var _useState3 = (0,react.useState)(props.errors || {}),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    errors = _useState4[0],
    setErrors = _useState4[1];
  var _useState5 = (0,react.useState)(props.touched || {}),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    touched = _useState6[0],
    setTouched = _useState6[1];
  var validate = (0,react.useCallback)(function (newValues) {
    var onValidate = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : function () {};
    var newErrors = props.validate ? props.validate(newValues) : {};
    setErrors(newErrors || {});
    onValidate(newErrors);
  }, [props.validate]);
  (0,react.useEffect)(function () {
    validate(values);
  }, []);
  var resetForm = function resetForm(newInitialValues) {
    var _ref2;
    var newTouchedFields = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var newErrors = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    var newValues = (_ref2 = newInitialValues !== null && newInitialValues !== void 0 ? newInitialValues : initialValues.current) !== null && _ref2 !== void 0 ? _ref2 : {};
    initialValues.current = newValues;
    setValuesInternal(newValues);
    setTouched(newTouchedFields);
    setErrors(newErrors);
  };
  (0,react.useImperativeHandle)(ref, function () {
    return {
      resetForm: resetForm
    };
  });
  var isValidForm = /*#__PURE__*/function () {
    var _ref3 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee() {
      return regenerator_default().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            validate(values);
            return _context.abrupt("return", !Object.keys(errors).length);
          case 2:
          case "end":
            return _context.stop();
        }
      }, _callee);
    }));
    return function isValidForm() {
      return _ref3.apply(this, arguments);
    };
  }();
  var setValues = (0,react.useCallback)(function (valuesToSet) {
    var newValues = _objectSpread(_objectSpread({}, values), valuesToSet);
    setValuesInternal(newValues);
    validate(newValues, function (newErrors) {
      var onChangeCallback = props.onChangeCallback;

      // Note that onChange is a no-op by default so this will never be null
      var singleValueChangeCallback = onChangeCallback || onChange;
      if (onChangeCallback) {
        (0,build_module/* default */.A)('onChangeCallback', {
          version: '9.0.0',
          alternative: 'onChange',
          plugin: '@woocommerce/components'
        });
      }
      if (!singleValueChangeCallback && !onChanges) {
        return;
      }

      // onChange and onChanges keep track of validity, so needs to
      // happen after setting the error state.

      var isValid = !Object.keys(newErrors || {}).length;
      var nameValuePairs = [];
      for (var _key in valuesToSet) {
        var nameValuePair = {
          name: _key,
          value: valuesToSet[_key]
        };
        nameValuePairs.push(nameValuePair);
        if (singleValueChangeCallback) {
          singleValueChangeCallback(nameValuePair, newValues, isValid);
        }
      }
      if (onChanges) {
        onChanges(nameValuePairs, newValues, isValid);
      }
    });
  }, [values, validate, onChange, props.onChangeCallback]);
  var setValue = (0,react.useCallback)(
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function (name, value) {
    setValues(setWith_default()(_objectSpread({}, values), name, value, (clone_default())));
  }, [values, validate, onChange, props.onChangeCallback]);
  var handleChange = (0,react.useCallback)(function (name, value) {
    // Handle native events.
    if (isChangeEvent(value) && value.target) {
      if (value.target.type === 'checkbox') {
        setValue(name, !get_default()(values, name));
      } else {
        setValue(name, value.target.value);
      }
    } else {
      setValue(name, value);
    }
  }, [setValue]);
  var handleBlur = (0,react.useCallback)(function (name) {
    setTouched(_objectSpread(_objectSpread({}, touched), {}, (0,defineProperty/* default */.A)({}, name, true)));
  }, [touched]);
  var handleSubmit = /*#__PURE__*/function () {
    var _ref4 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee2() {
      var onSubmitCallback, touchedFields, callback;
      return regenerator_default().wrap(function _callee2$(_context2) {
        while (1) switch (_context2.prev = _context2.next) {
          case 0:
            onSubmitCallback = props.onSubmitCallback;
            touchedFields = {};
            Object.keys(values).map(function (name) {
              return touchedFields[name] = true;
            });
            setTouched(touchedFields);
            _context2.next = 6;
            return isValidForm();
          case 6:
            if (!_context2.sent) {
              _context2.next = 11;
              break;
            }
            // Note that onSubmit is a no-op by default so this will never be null
            callback = onSubmitCallback || onSubmit;
            if (onSubmitCallback) {
              (0,build_module/* default */.A)('onSubmitCallback', {
                version: '9.0.0',
                alternative: 'onSubmit',
                plugin: '@woocommerce/components'
              });
            }
            if (!callback) {
              _context2.next = 11;
              break;
            }
            return _context2.abrupt("return", callback(values));
          case 11:
          case "end":
            return _context2.stop();
        }
      }, _callee2);
    }));
    return function handleSubmit() {
      return _ref4.apply(this, arguments);
    };
  }();
  function getInputProps(name) {
    var inputProps = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var inputValue = get_default()(values, name);
    var isTouched = touched[name];
    var inputError = get_default()(errors, name);
    var classNameProp = inputProps.className,
      onBlurProp = inputProps.onBlur,
      onChangeProp = inputProps.onChange,
      sanitize = inputProps.sanitize,
      additionalProps = (0,objectWithoutProperties/* default */.A)(inputProps, _excluded2);
    return _objectSpread({
      value: inputValue,
      checked: Boolean(inputValue),
      selected: inputValue,
      onChange: function onChange(value) {
        handleChange(name, value);
        if (onChangeProp) {
          onChangeProp(value);
        }
      },
      onBlur: function onBlur() {
        if (sanitize) {
          handleChange(name, sanitize(inputValue));
        }
        handleBlur(name);
        if (onBlurProp) {
          onBlurProp();
        }
      },
      className: classnames_default()(classNameProp, {
        'has-error': isTouched && inputError
      }),
      help: isTouched ? inputError : null
    }, additionalProps);
  }
  function getCheckboxControlProps(name) {
    var inputProps = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    return omit_default()(getInputProps(name, inputProps), ['selected', 'value']);
  }
  function getSelectControlProps(name) {
    var inputProps = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var selectControlProps = getInputProps(name, inputProps);
    return _objectSpread(_objectSpread({}, selectControlProps), {}, {
      value: selectControlProps.value === undefined ? undefined : String(selectControlProps.value)
    });
  }
  var isDirty = (0,react.useMemo)(function () {
    return !isEqual_default()(initialValues.current, values);
  }, [initialValues.current, values]);
  var getStateAndHelpers = function getStateAndHelpers() {
    return {
      values: values,
      errors: errors,
      touched: touched,
      isDirty: isDirty,
      setTouched: setTouched,
      setValue: setValue,
      setValues: setValues,
      handleSubmit: handleSubmit,
      getCheckboxControlProps: getCheckboxControlProps,
      getInputProps: getInputProps,
      getSelectControlProps: getSelectControlProps,
      isValidForm: !Object.keys(errors).length,
      resetForm: resetForm
    };
  };
  function getChildren() {
    if (typeof children === 'function') {
      var element = children(getStateAndHelpers());
      return (0,react.cloneElement)(element);
    }
    return children;
  }
  return (0,react.createElement)(FormContext.Provider, {
    value: getStateAndHelpers()
  }, getChildren());
}
var Form = (0,react.forwardRef)(FormComponent);


/***/ }),

/***/ "../../packages/js/components/src/dynamic-form/stories/index.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ index_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js
var es_object_entries = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.values.js
var es_object_values = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/form/form.tsx + 1 modules
var form_form = __webpack_require__("../../packages/js/components/src/form/form.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js
var es_symbol_description = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js");
// EXTERNAL MODULE: ../../packages/js/components/src/text-control/index.js
var text_control = __webpack_require__("../../packages/js/components/src/text-control/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/dynamic-form/field-types/field-text.tsx


var _excluded = ["field", "type"];



/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var TextField = function TextField(_ref) {
  var field = _ref.field,
    _ref$type = _ref.type,
    type = _ref$type === void 0 ? 'text' : _ref$type,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  var label = field.label,
    description = field.description;
  return (0,react.createElement)(text_control/* default */.A, (0,esm_extends/* default */.A)({
    type: type,
    title: description,
    label: label
  }, props));
};
try {
    // @ts-ignore
    TextField.displayName = "TextField";
    // @ts-ignore
    TextField.__docgenInfo = { "description": "", "displayName": "TextField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-text.tsx#TextField"] = { docgenInfo: TextField.__docgenInfo, name: "TextField", path: "../../packages/js/components/src/dynamic-form/field-types/field-text.tsx#TextField" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/dynamic-form/field-types/field-password.tsx


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var PasswordField = function PasswordField(props) {
  return (0,react.createElement)(TextField, (0,esm_extends/* default */.A)({}, props, {
    type: "password"
  }));
};
try {
    // @ts-ignore
    PasswordField.displayName = "PasswordField";
    // @ts-ignore
    PasswordField.__docgenInfo = { "description": "", "displayName": "PasswordField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-password.tsx#PasswordField"] = { docgenInfo: PasswordField.__docgenInfo, name: "PasswordField", path: "../../packages/js/components/src/dynamic-form/field-types/field-password.tsx#PasswordField" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/dynamic-form/field-types/field-checkbox.tsx


var field_checkbox_excluded = ["field", "onChange"];



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var CheckboxField = function CheckboxField(_ref) {
  var field = _ref.field,
    _onChange = _ref.onChange,
    props = (0,objectWithoutProperties/* default */.A)(_ref, field_checkbox_excluded);
  var label = field.label,
    description = field.description;
  return (0,react.createElement)(checkbox_control/* default */.A, (0,esm_extends/* default */.A)({
    onChange: function onChange(val) {
      return _onChange(val);
    },
    title: description,
    label: label
  }, props));
};
try {
    // @ts-ignore
    CheckboxField.displayName = "CheckboxField";
    // @ts-ignore
    CheckboxField.__docgenInfo = { "description": "", "displayName": "CheckboxField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-checkbox.tsx#CheckboxField"] = { docgenInfo: CheckboxField.__docgenInfo, name: "CheckboxField", path: "../../packages/js/components/src/dynamic-form/field-types/field-checkbox.tsx#CheckboxField" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/dynamic-form/field-types/field-select.tsx



var field_select_excluded = ["field"];





/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var transformOptions = function transformOptions(options) {
  return Object.entries(options).map(function (_ref) {
    var _ref2 = (0,slicedToArray/* default */.A)(_ref, 2),
      key = _ref2[0],
      value = _ref2[1];
    return {
      key: key,
      label: value,
      value: {
        id: key
      }
    };
  });
};
var SelectField = function SelectField(_ref3) {
  var field = _ref3.field,
    props = (0,objectWithoutProperties/* default */.A)(_ref3, field_select_excluded);
  var description = field.description,
    label = field.label,
    _field$options = field.options,
    options = _field$options === void 0 ? {} : _field$options;
  var transformedOptions = (0,react.useMemo)(function () {
    return transformOptions(options);
  }, [options]);
  return (0,react.createElement)(select_control/* default */.A, (0,esm_extends/* default */.A)({
    title: description,
    label: label,
    options: transformedOptions
  }, props));
};
try {
    // @ts-ignore
    SelectField.displayName = "SelectField";
    // @ts-ignore
    SelectField.__docgenInfo = { "description": "", "displayName": "SelectField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-select.tsx#SelectField"] = { docgenInfo: SelectField.__docgenInfo, name: "SelectField", path: "../../packages/js/components/src/dynamic-form/field-types/field-select.tsx#SelectField" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/dynamic-form/dynamic-form.tsx












function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}




/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


var fieldTypeMap = {
  text: TextField,
  password: PasswordField,
  checkbox: CheckboxField,
  select: SelectField,
  "default": TextField
};
var getInitialConfigValues = function getInitialConfigValues(fields) {
  return fields.reduce(function (data, field) {
    return _objectSpread(_objectSpread({}, data), {}, (0,defineProperty/* default */.A)({}, field.id, field.type === 'checkbox' ? field.value === 'yes' : field.value));
  }, {});
};
var DynamicForm = function DynamicForm(_ref) {
  var _ref$fields = _ref.fields,
    baseFields = _ref$fields === void 0 ? [] : _ref$fields,
    _ref$isBusy = _ref.isBusy,
    isBusy = _ref$isBusy === void 0 ? false : _ref$isBusy,
    _ref$onSubmit = _ref.onSubmit,
    onSubmit = _ref$onSubmit === void 0 ? function () {} : _ref$onSubmit,
    _ref$onChange = _ref.onChange,
    onChange = _ref$onChange === void 0 ? function () {} : _ref$onChange,
    _ref$validate = _ref.validate,
    validate = _ref$validate === void 0 ? function () {
      return {};
    } : _ref$validate,
    _ref$submitLabel = _ref.submitLabel,
    submitLabel = _ref$submitLabel === void 0 ? (0,build_module.__)('Proceed', 'woocommerce') : _ref$submitLabel;
  // Support accepting fields in the format provided by the API (object), but transform to Array
  var fields = baseFields instanceof Array ? baseFields : Object.values(baseFields);
  var initialValues = (0,react.useMemo)(function () {
    return getInitialConfigValues(fields);
  }, [fields]);
  return (
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (0,react.createElement)(form_form/* Form */.l, {
      initialValues: initialValues,
      onChange: onChange,
      onSubmit: onSubmit,
      validate: validate
    }, function (_ref2) {
      var getInputProps = _ref2.getInputProps,
        handleSubmit = _ref2.handleSubmit;
      return (0,react.createElement)("div", {
        className: "woocommerce-component_dynamic-form"
      }, fields.map(function (field) {
        if (field.type && !(field.type in fieldTypeMap)) {
          /* eslint-disable no-console */
          console.warn("Field type of ".concat(field.type, " not current supported in DynamicForm component"));
          /* eslint-enable no-console */
          return null;
        }
        var Control = fieldTypeMap[field.type || 'default'];
        return (0,react.createElement)(Control, (0,esm_extends/* default */.A)({
          key: field.id,
          field: field
        }, getInputProps(field.id)));
      }), (0,react.createElement)(build_module_button/* default */.A, {
        isPrimary: true,
        isBusy: isBusy,
        onClick: function onClick() {
          handleSubmit();
        }
      }, submitLabel));
    })
  );
};
try {
    // @ts-ignore
    DynamicForm.displayName = "DynamicForm";
    // @ts-ignore
    DynamicForm.__docgenInfo = { "description": "", "displayName": "DynamicForm", "props": { "fields": { "defaultValue": null, "description": "", "name": "fields", "required": true, "type": { "name": "Field[] | { [key: string]: Field; }" } }, "validate": { "defaultValue": { value: "() => ( {} )" }, "description": "", "name": "validate", "required": false, "type": { "name": "(values: Record<string, string>) => Record<string, string>" } }, "isBusy": { "defaultValue": { value: "false" }, "description": "", "name": "isBusy", "required": false, "type": { "name": "boolean" } }, "onSubmit": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onSubmit", "required": false, "type": { "name": "((values: Record<string, string>) => void)" } }, "onChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onChange", "required": false, "type": { "name": "((value: { name: string; value: unknown; }, values: Record<string, string>, result: boolean) => void)" } }, "submitLabel": { "defaultValue": { value: "__( 'Proceed', 'woocommerce' )" }, "description": "", "name": "submitLabel", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/dynamic-form.tsx#DynamicForm"] = { docgenInfo: DynamicForm.__docgenInfo, name: "DynamicForm", path: "../../packages/js/components/src/dynamic-form/dynamic-form.tsx#DynamicForm" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/dynamic-form/stories/index.story.js





/**
 * External dependencies
 */


var fields = [{
  id: 'user_name',
  label: 'Username',
  description: 'This is your username.',
  type: 'text',
  value: '',
  "default": '',
  tip: 'This is your username.',
  placeholder: ''
}, {
  id: 'pass_phrase',
  label: 'Passphrase',
  description: '* Required. Needed to ensure the data passed through is secure.',
  type: 'password',
  value: '',
  "default": '',
  tip: '* Required. Needed to ensure the data passed through is secure.',
  placeholder: ''
}, {
  id: 'button_type',
  label: 'Button Type',
  description: 'Select the button type you would like to show.',
  type: 'select',
  value: 'buy',
  "default": 'buy',
  tip: 'Select the button type you would like to show.',
  placeholder: '',
  options: {
    "default": 'Default',
    buy: 'Buy',
    donate: 'Donate',
    branded: 'Branded',
    custom: 'Custom'
  }
}, {
  id: 'checkbox_sample',
  label: 'Checkbox style',
  description: 'This is an example checkbox field.',
  type: 'checkbox',
  value: 'no',
  "default": 'no',
  tip: 'This is an example checkbox field.',
  placeholder: ''
}];
var getField = function getField(fieldId) {
  return fields.find(function (field) {
    return field.id === fieldId;
  });
};
var validate = function validate(values) {
  var errors = {};
  for (var _i = 0, _Object$entries = Object.entries(values); _i < _Object$entries.length; _i++) {
    var _Object$entries$_i = (0,slicedToArray/* default */.A)(_Object$entries[_i], 2),
      key = _Object$entries$_i[0],
      value = _Object$entries$_i[1];
    var field = getField(key);
    if (!(value || field.type === 'checkbox')) {
      errors[key] = "Please enter your ".concat(field.label.toLowerCase());
    }
  }
  return errors;
};
var DynamicExample = function DynamicExample() {
  var _useState = (0,react.useState)(null),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    submitted = _useState2[0],
    setSubmitted = _useState2[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(DynamicForm, {
    fields: fields,
    onSubmit: function onSubmit(values) {
      return setSubmitted(values);
    },
    validate: validate
  }), (0,react.createElement)("h4", null, "Submitted:"), (0,react.createElement)("p", null, submitted ? JSON.stringify(submitted, null, 3) : 'None'));
};
var Basic = function Basic() {
  return (0,react.createElement)(DynamicExample, null);
};
/* harmony default export */ const index_story = ({
  title: 'WooCommerce Admin/components/DynamicForm',
  component: DynamicForm
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <DynamicExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ })

}]);