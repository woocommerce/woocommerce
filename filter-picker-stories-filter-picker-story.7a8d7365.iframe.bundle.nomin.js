"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3942],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _assertThisInitialized)
/* harmony export */ });
function _assertThisInitialized(e) {
  if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  return e;
}


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ Dropdown)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");


// @ts-nocheck

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function useObservableState(initialState, onStateChange) {
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(initialState);
  return [state, value => {
    setState(value);

    if (onStateChange) {
      onStateChange(value);
    }
  }];
}

function Dropdown(props) {
  var _popoverProps$anchorR;

  const {
    renderContent,
    renderToggle,
    position = 'bottom right',
    className,
    contentClassName,
    expandOnMobile,
    headerTitle,
    focusOnMount,
    popoverProps,
    onClose,
    onToggle
  } = props;
  const containerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)();
  const [isOpen, setIsOpen] = useObservableState(false, onToggle);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => () => {
    if (onToggle) {
      onToggle(false);
    }
  }, []);

  function toggle() {
    setIsOpen(!isOpen);
  }
  /**
   * Closes the popover when focus leaves it unless the toggle was pressed or
   * focus has moved to a separate dialog. The former is to let the toggle
   * handle closing the popover and the latter is to preserve presence in
   * case a dialog has opened, allowing focus to return when it's dismissed.
   */


  function closeIfFocusOutside() {
    const {
      ownerDocument
    } = containerRef.current;
    const dialog = ownerDocument.activeElement.closest('[role="dialog"]');

    if (!containerRef.current.contains(ownerDocument.activeElement) && (!dialog || dialog.contains(containerRef.current))) {
      close();
    }
  }

  function close() {
    if (onClose) {
      onClose();
    }

    setIsOpen(false);
  }

  const args = {
    isOpen,
    onToggle: toggle,
    onClose: close
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('components-dropdown', className),
    ref: containerRef // Some UAs focus the closest focusable parent when the toggle is
    // clicked. Making this div focusable ensures such UAs will focus
    // it and `closeIfFocusOutside` can tell if the toggle was clicked.
    ,
    tabIndex: "-1"
  }, renderToggle(args), isOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_popover__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)({
    position: position,
    onClose: close,
    onFocusOutside: closeIfFocusOutside,
    expandOnMobile: expandOnMobile,
    headerTitle: headerTitle,
    focusOnMount: focusOnMount
  }, popoverProps, {
    anchorRef: (_popoverProps$anchorR = popoverProps === null || popoverProps === void 0 ? void 0 : popoverProps.anchorRef) !== null && _popoverProps$anchorR !== void 0 ? _popoverProps$anchorR : containerRef.current,
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('components-dropdown__content', popoverProps ? popoverProps.className : undefined, contentClassName)
  }), renderContent(args)));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-left.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@3.4.1/node_modules/@wordpress/primitives/build-module/svg/index.js");


/**
 * WordPress dependencies
 */

const chevronLeft = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, {
  d: "M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z"
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (chevronLeft);
//# sourceMappingURL=chevron-left.js.map

/***/ }),

/***/ "../../packages/js/components/src/animation-slider/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/CSSTransition.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_14__);










function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(this, result);
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
 * This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.
 * All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition
 */
var AnimationSlider = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)(AnimationSlider, _Component);
  var _super = _createSuper(AnimationSlider);
  function AnimationSlider() {
    var _this;
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)(this, AnimationSlider);
    _this = _super.call(this);
    _this.state = {
      animate: null
    };
    _this.container = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_8__.createRef)();
    _this.onExited = _this.onExited.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)(_this));
    return _this;
  }
  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(AnimationSlider, [{
    key: "onExited",
    value: function onExited() {
      var onExited = this.props.onExited;
      if (onExited) {
        onExited(this.container.current);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
        children = _this$props.children,
        animationKey = _this$props.animationKey,
        animate = _this$props.animate;
      var containerClasses = classnames__WEBPACK_IMPORTED_MODULE_5___default()('woocommerce-slide-animation', animate && "animate-".concat(animate));
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_8__.createElement)("div", {
        className: containerClasses,
        ref: this.container
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_8__.createElement)(react_transition_group__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_8__.createElement)(react_transition_group__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)({
        timeout: 200,
        classNames: "slide",
        key: animationKey
      }, this.props, {
        onExited: this.onExited
      }), function (status) {
        return children({
          status: status
        });
      })));
    }
  }]);
  return AnimationSlider;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__.Component);
AnimationSlider.propTypes = {
  /**
   * A function returning rendered content with argument status, reflecting `CSSTransition` status.
   */
  children: (prop_types__WEBPACK_IMPORTED_MODULE_14___default().func).isRequired,
  /**
   * A unique identifier for each slideable page.
   */
  animationKey: (prop_types__WEBPACK_IMPORTED_MODULE_14___default().any).isRequired,
  /**
   * null, 'left', 'right', to designate which direction to slide on a change.
   */
  animate: prop_types__WEBPACK_IMPORTED_MODULE_14___default().oneOf([null, 'left', 'right']),
  /**
   * A function to be executed after a transition is complete, passing the containing ref as the argument.
   */
  onExited: (prop_types__WEBPACK_IMPORTED_MODULE_14___default().func)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AnimationSlider);

/***/ }),

/***/ "../../packages/js/components/src/dropdown-button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);


var _excluded = ["labels", "isOpen"];

/**
 * External dependencies
 */






/**
 * A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays
 * single or multiple lines rendered as `<span/>` elements.
 *
 * @param {Object} props Props passed to component.
 * @return {Object} -
 */
var DropdownButton = function DropdownButton(props) {
  var labels = props.labels,
    isOpen = props.isOpen,
    otherProps = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(props, _excluded);
  var buttonClasses = classnames__WEBPACK_IMPORTED_MODULE_1___default()('woocommerce-dropdown-button', {
    'is-open': isOpen,
    'is-multi-line': labels.length > 1
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)({
    className: buttonClasses,
    "aria-expanded": isOpen
  }, otherProps), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("div", {
    className: "woocommerce-dropdown-button__labels"
  }, labels.map(function (label, i) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("span", {
      key: i
    }, (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_6__/* .decodeEntities */ .S)(label));
  })));
};
DropdownButton.propTypes = {
  /**
   * An array of elements to be rendered as the content of the button.
   */
  labels: (prop_types__WEBPACK_IMPORTED_MODULE_7___default().array).isRequired,
  /**
   * Boolean describing if the dropdown in open or not.
   */
  isOpen: (prop_types__WEBPACK_IMPORTED_MODULE_7___default().bool)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DropdownButton);

/***/ }),

/***/ "../../packages/js/components/src/filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export DEFAULT_FILTER */
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js");
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_39___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_39__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var _animation_slider__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__("../../packages/js/components/src/animation-slider/index.js");
/* harmony import */ var _dropdown_button__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__("../../packages/js/components/src/dropdown-button/index.js");
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__("../../packages/js/components/src/search/index.tsx");
















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
      (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}










function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_19__/* ["default"] */ .A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_19__/* ["default"] */ .A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_20__/* ["default"] */ .A)(this, result);
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
 * Internal dependencies
 */



var DEFAULT_FILTER = 'all';

/**
 * Modify a url query parameter via a dropdown selection of configurable options.
 * This component manipulates the `filter` query parameter.
 */
var FilterPicker = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_25__/* ["default"] */ .A)(FilterPicker, _Component);
  var _super = _createSuper(FilterPicker);
  function FilterPicker(props) {
    var _this;
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_26__/* ["default"] */ .A)(this, FilterPicker);
    _this = _super.call(this, props);
    var selectedFilter = _this.getFilter();
    _this.state = {
      nav: selectedFilter.path || [],
      animate: null,
      selectedTag: null
    };
    _this.selectSubFilter = _this.selectSubFilter.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_27__/* ["default"] */ .A)(_this));
    _this.getVisibleFilters = _this.getVisibleFilters.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_27__/* ["default"] */ .A)(_this));
    _this.updateSelectedTag = _this.updateSelectedTag.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_27__/* ["default"] */ .A)(_this));
    _this.onTagChange = _this.onTagChange.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_27__/* ["default"] */ .A)(_this));
    _this.onContentMount = _this.onContentMount.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_27__/* ["default"] */ .A)(_this));
    _this.goBack = _this.goBack.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_27__/* ["default"] */ .A)(_this));
    if (selectedFilter.settings && selectedFilter.settings.getLabels) {
      var query = _this.props.query;
      var _selectedFilter$setti = selectedFilter.settings,
        filterParam = _selectedFilter$setti.param,
        getLabels = _selectedFilter$setti.getLabels;
      getLabels(query[filterParam], query).then(_this.updateSelectedTag);
    }
    return _this;
  }
  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_28__/* ["default"] */ .A)(FilterPicker, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(_ref) {
      var prevQuery = _ref.query;
      var _this$props = this.props,
        nextQuery = _this$props.query,
        config = _this$props.config;
      if (prevQuery[config.param] !== nextQuery[[config.param]]) {
        var selectedFilter = this.getFilter();
        if (selectedFilter && selectedFilter.component === 'Search') {
          /* eslint-disable react/no-did-update-set-state */
          this.setState({
            nav: selectedFilter.path || []
          });
          /* eslint-enable react/no-did-update-set-state */
          var _selectedFilter$setti2 = selectedFilter.settings,
            filterParam = _selectedFilter$setti2.param,
            getLabels = _selectedFilter$setti2.getLabels;
          getLabels(nextQuery[filterParam], nextQuery).then(this.updateSelectedTag);
        }
      }
    }
  }, {
    key: "updateSelectedTag",
    value: function updateSelectedTag(tags) {
      this.setState({
        selectedTag: tags[0]
      });
    }
  }, {
    key: "getFilter",
    value: function getFilter(value) {
      var _this$props2 = this.props,
        config = _this$props2.config,
        query = _this$props2.query;
      var allFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_24__/* .flattenFilters */ .SI)(config.filters);
      value = value || query[config.param] || config.defaultValue || DEFAULT_FILTER;
      return (0,lodash__WEBPACK_IMPORTED_MODULE_23__.find)(allFilters, {
        value: value
      }) || {};
    }
  }, {
    key: "getButtonLabel",
    value: function getButtonLabel(selectedFilter) {
      if (selectedFilter.component === 'Search') {
        var selectedTag = this.state.selectedTag;
        return [selectedTag && selectedTag.label, (0,lodash__WEBPACK_IMPORTED_MODULE_23__.get)(selectedFilter, 'settings.labels.button')];
      }
      return selectedFilter ? [selectedFilter.label] : [];
    }
  }, {
    key: "getVisibleFilters",
    value: function getVisibleFilters(filters, nav) {
      if (nav.length === 0) {
        return filters;
      }
      var value = nav[0];
      var nextFilters = (0,lodash__WEBPACK_IMPORTED_MODULE_23__.find)(filters, {
        value: value
      });
      return this.getVisibleFilters(nextFilters && nextFilters.subFilters, nav.slice(1));
    }
  }, {
    key: "selectSubFilter",
    value: function selectSubFilter(value) {
      // Add the value onto the nav path
      this.setState(function (prevState) {
        return {
          nav: [].concat((0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_29__/* ["default"] */ .A)(prevState.nav), [value]),
          animate: 'left'
        };
      });
    }
  }, {
    key: "goBack",
    value: function goBack() {
      // Remove the last item from the nav path
      this.setState(function (prevState) {
        return {
          nav: prevState.nav.slice(0, -1),
          animate: 'right'
        };
      });
    }
  }, {
    key: "getAllFilterParams",
    value: function getAllFilterParams() {
      var config = this.props.config;
      var params = [];
      var getParam = function getParam(filters) {
        filters.forEach(function (filter) {
          if (filter.settings && !params.includes(filter.settings.param)) {
            params.push(filter.settings.param);
          }
          if (filter.subFilters) {
            getParam(filter.subFilters);
          }
        });
      };
      getParam(config.filters);
      return params;
    }
  }, {
    key: "update",
    value: function update(value) {
      var additionalQueries = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var _this$props3 = this.props,
        path = _this$props3.path,
        query = _this$props3.query,
        config = _this$props3.config,
        onFilterSelect = _this$props3.onFilterSelect,
        advancedFilters = _this$props3.advancedFilters;
      var update = _objectSpread((0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)({}, config.param, (config.defaultValue || DEFAULT_FILTER) === value ? undefined : value), additionalQueries);
      // Keep any url parameters as designated by the config
      config.staticParams.forEach(function (param) {
        update[param] = query[param];
      });

      // Remove all of this filter's params not associated with the update while
      // leaving any other params from any other filter an extension may have added.
      this.getAllFilterParams().forEach(function (param) {
        if (!update[param]) {
          // Explicitly give value of undefined so it can be removed from the query.
          update[param] = undefined;
        }
      });

      // If the main filter is being set to anything but advanced, remove any advancedFilters.
      if (config.param === 'filter' && value !== 'advanced') {
        var resetAdvancedFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_24__/* .getQueryFromActiveFilters */ .Sz)([], query, advancedFilters.filters || {});
        update = _objectSpread(_objectSpread({}, update), resetAdvancedFilters);
      }
      (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_24__/* .updateQueryString */ .Ze)(update, path, query);
      onFilterSelect(update);
    }
  }, {
    key: "onTagChange",
    value: function onTagChange(filter, onClose, config, tags) {
      var tag = (0,lodash__WEBPACK_IMPORTED_MODULE_23__.last)(tags);
      var value = filter.value,
        settings = filter.settings;
      var filterParam = settings.param;
      if (tag) {
        this.update(value, (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)({}, filterParam, tag.key));
        onClose();
      } else {
        this.update(config.defaultValue || DEFAULT_FILTER);
      }
      this.updateSelectedTag([tag]);
    }
  }, {
    key: "renderButton",
    value: function renderButton(filter, onClose, config) {
      var _this2 = this;
      if (filter.component) {
        var _filter$settings = filter.settings,
          type = _filter$settings.type,
          labels = _filter$settings.labels,
          autocompleter = _filter$settings.autocompleter;
        var persistedFilter = this.getFilter();
        var selectedTag = persistedFilter.value === filter.value ? this.state.selectedTag : null;
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)(_search__WEBPACK_IMPORTED_MODULE_31__/* ["default"] */ .A, {
          autocompleter: autocompleter,
          className: "woocommerce-filters-filter__search",
          type: type,
          placeholder: labels.placeholder,
          selected: selectedTag ? [selectedTag] : [],
          onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_23__.partial)(this.onTagChange, filter, onClose, config),
          inlineTags: true,
          staticResults: true
        });
      }
      var selectFilter = function selectFilter(event) {
        onClose(event);
        _this2.update(filter.value, filter.query || {});
        _this2.setState({
          selectedTag: null
        });
      };
      var selectSubFilter = (0,lodash__WEBPACK_IMPORTED_MODULE_23__.partial)(this.selectSubFilter, filter.value);
      var selectedFilter = this.getFilter();
      var buttonIsSelected = selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_23__.includes)(selectedFilter.path, filter.value);
      var onClick = function onClick(event) {
        if (buttonIsSelected) {
          // Don't navigate if the button is already selected.
          onClose(event);
          return;
        }
        if (filter.subFilters) {
          selectSubFilter(event);
          return;
        }
        selectFilter(event);
      };
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_32__/* ["default"] */ .A, {
        className: "woocommerce-filters-filter__button",
        onClick: onClick
      }, filter.label);
    }
  }, {
    key: "onContentMount",
    value: function onContentMount(content) {
      var nav = this.state.nav;
      var parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
      var focusableIndex = parentFilter ? 1 : 0;
      var focusable = _wordpress_dom__WEBPACK_IMPORTED_MODULE_33__/* .focus */ .XC.tabbable.find(content)[focusableIndex];
      setTimeout(function () {
        focusable.focus();
      }, 0);
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;
      var config = this.props.config;
      var _this$state = this.state,
        nav = _this$state.nav,
        animate = _this$state.animate;
      var visibleFilters = this.getVisibleFilters(config.filters, nav);
      var parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
      var selectedFilter = this.getFilter();
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)("div", {
        className: "woocommerce-filters-filter"
      }, config.label && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)("span", {
        className: "woocommerce-filters-label"
      }, config.label, ":"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_34__/* ["default"] */ .A, {
        contentClassName: "woocommerce-filters-filter__content",
        popoverProps: {
          placement: 'bottom'
        },
        expandOnMobile: true,
        headerTitle: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_21__.__)('filter report to show:', 'woocommerce'),
        renderToggle: function renderToggle(_ref2) {
          var isOpen = _ref2.isOpen,
            onToggle = _ref2.onToggle;
          return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)(_dropdown_button__WEBPACK_IMPORTED_MODULE_35__/* ["default"] */ .A, {
            onClick: onToggle,
            isOpen: isOpen,
            labels: _this3.getButtonLabel(selectedFilter)
          });
        },
        renderContent: function renderContent(_ref3) {
          var onClose = _ref3.onClose;
          return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)(_animation_slider__WEBPACK_IMPORTED_MODULE_36__/* ["default"] */ .A, {
            animationKey: nav,
            animate: animate,
            onExited: _this3.onContentMount
          }, function () {
            return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)("ul", {
              className: "woocommerce-filters-filter__content-list"
            }, parentFilter && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)("li", {
              className: "woocommerce-filters-filter__content-list-item"
            }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_32__/* ["default"] */ .A, {
              className: "woocommerce-filters-filter__button",
              onClick: _this3.goBack
            }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_37__/* ["default"] */ .A, {
              icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_38__/* ["default"] */ .A
            }), parentFilter.label)), visibleFilters.map(function (filter) {
              return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.createElement)("li", {
                key: filter.value,
                className: classnames__WEBPACK_IMPORTED_MODULE_22___default()('woocommerce-filters-filter__content-list-item', {
                  'is-selected': selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_23__.includes)(selectedFilter.path, filter.value)
                })
              }, _this3.renderButton(filter, onClose, config));
            }));
          });
        }
      }));
    }
  }]);
  return FilterPicker;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_30__.Component);
FilterPicker.propTypes = {
  /**
   * An array of filters and subFilters to construct the menu.
   */
  config: prop_types__WEBPACK_IMPORTED_MODULE_39___default().shape({
    /**
     * A label above the filter selector.
     */
    label: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string),
    /**
     * Url parameters to persist when selecting a new filter.
     */
    staticParams: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().array).isRequired,
    /**
     * The url parameter this filter will modify.
     */
    param: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string).isRequired,
    /**
     * The default parameter value to use instead of 'all'.
     */
    defaultValue: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string),
    /**
     * Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.
     */
    showFilters: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().func).isRequired,
    /**
     * An array of filter a user can select.
     */
    filters: prop_types__WEBPACK_IMPORTED_MODULE_39___default().arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_39___default().shape({
      /**
       * The chart display mode to use for charts displayed when this filter is active.
       */
      chartMode: prop_types__WEBPACK_IMPORTED_MODULE_39___default().oneOf(['item-comparison', 'time-comparison']),
      /**
       * A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.
       */
      component: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string),
      /**
       * The label for this filter. Optional only for custom component filters.
       */
      label: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string),
      /**
       * An array representing the "path" to this filter, if nested.
       */
      path: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string),
      /**
       * An array of more filter objects that act as "children" to this item.
       * This set of filters is shown if the parent filter is clicked.
       */
      subFilters: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().array),
      /**
       * The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.
       */
      value: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string).isRequired
    }))
  }).isRequired,
  /**
   * The `path` parameter supplied by React-Router.
   */
  path: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().string).isRequired,
  /**
   * The query string represented in object form.
   */
  query: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().object),
  /**
   * Function to be called after filter selection.
   */
  onFilterSelect: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().func),
  /**
   * Advanced Filters configuration object.
   */
  advancedFilters: (prop_types__WEBPACK_IMPORTED_MODULE_39___default().object)
};
FilterPicker.defaultProps = {
  query: {},
  onFilterSelect: function onFilterSelect() {}
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FilterPicker);

/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-is-bun.js":
/***/ ((module) => {


/* global Bun -- Bun case */
module.exports = typeof Bun == 'function' && Bun && typeof Bun.version == 'string';


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/schedulers-fix.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {


var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var apply = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-apply.js");
var isCallable = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-callable.js");
var ENGINE_IS_BUN = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-is-bun.js");
var USER_AGENT = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-user-agent.js");
var arraySlice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-slice.js");
var validateArgumentsLength = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/validate-arguments-length.js");

var Function = global.Function;
// dirty IE9- and Bun 0.3.0- checks
var WRAP = /MSIE .\./.test(USER_AGENT) || ENGINE_IS_BUN && (function () {
  var version = global.Bun.version.split('.');
  return version.length < 3 || version[0] === '0' && (version[1] < 3 || version[1] === '3' && version[2] === '0');
})();

// IE9- / Bun 0.3.0- setTimeout / setInterval / setImmediate additional parameters fix
// https://html.spec.whatwg.org/multipage/timers-and-user-prompts.html#timers
// https://github.com/oven-sh/bun/issues/1633
module.exports = function (scheduler, hasTimeArg) {
  var firstParamIndex = hasTimeArg ? 2 : 1;
  return WRAP ? function (handler, timeout /* , ...arguments */) {
    var boundArgs = validateArgumentsLength(arguments.length, 1) > firstParamIndex;
    var fn = isCallable(handler) ? handler : Function(handler);
    var params = boundArgs ? arraySlice(arguments, firstParamIndex) : [];
    var callback = boundArgs ? function () {
      apply(fn, this, params);
    } : fn;
    return hasTimeArg ? scheduler(callback, timeout) : scheduler(callback);
  } : scheduler;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var IndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/indexed-object.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");

var nativeJoin = uncurryThis([].join);

var ES3_STRINGS = IndexedObject !== Object;
var FORCED = ES3_STRINGS || !arrayMethodIsStrict('join', ',');

// `Array.prototype.join` method
// https://tc39.es/ecma262/#sec-array.prototype.join
$({ target: 'Array', proto: true, forced: FORCED }, {
  join: function join(separator) {
    return nativeJoin(toIndexedObject(this), separator === undefined ? ',' : separator);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


// TODO: Remove from `core-js@4`
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var defineBuiltIn = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/define-built-in.js");

var DatePrototype = Date.prototype;
var INVALID_DATE = 'Invalid Date';
var TO_STRING = 'toString';
var nativeDateToString = uncurryThis(DatePrototype[TO_STRING]);
var thisTimeValue = uncurryThis(DatePrototype.getTime);

// `Date.prototype.toString` method
// https://tc39.es/ecma262/#sec-date.prototype.tostring
if (String(new Date(NaN)) !== INVALID_DATE) {
  defineBuiltIn(DatePrototype, TO_STRING, function toString() {
    var value = thisTimeValue(this);
    // eslint-disable-next-line no-self-compare -- NaN check
    return value === value ? nativeDateToString(this) : INVALID_DATE;
  });
}


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-interval.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var schedulersFix = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/schedulers-fix.js");

var setInterval = schedulersFix(global.setInterval, true);

// Bun / IE9- setInterval additional parameters fix
// https://html.spec.whatwg.org/multipage/timers-and-user-prompts.html#dom-setinterval
$({ global: true, bind: true, forced: global.setInterval !== setInterval }, {
  setInterval: setInterval
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-timeout.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var schedulersFix = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/schedulers-fix.js");

var setTimeout = schedulersFix(global.setTimeout, true);

// Bun / IE9- setTimeout additional parameters fix
// https://html.spec.whatwg.org/multipage/timers-and-user-prompts.html#dom-settimeout
$({ global: true, bind: true, forced: global.setTimeout !== setTimeout }, {
  setTimeout: setTimeout
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


// TODO: Remove this module from `core-js@4` since it's split to modules listed below
__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-interval.js");
__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-timeout.js");


/***/ }),

/***/ "../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ esm_TransitionGroup)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js
var inheritsLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/TransitionGroupContext.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/utils/ChildMapping.js

/**
 * Given `this.props.children`, return an object mapping key to child.
 *
 * @param {*} children `this.props.children`
 * @return {object} Mapping of key to child
 */

function getChildMapping(children, mapFn) {
  var mapper = function mapper(child) {
    return mapFn && (0,react.isValidElement)(child) ? mapFn(child) : child;
  };

  var result = Object.create(null);
  if (children) react.Children.map(children, function (c) {
    return c;
  }).forEach(function (child) {
    // run the map function here instead so that the key is the computed one
    result[child.key] = mapper(child);
  });
  return result;
}
/**
 * When you're adding or removing children some may be added or removed in the
 * same render pass. We want to show *both* since we want to simultaneously
 * animate elements in and out. This function takes a previous set of keys
 * and a new set of keys and merges them with its best guess of the correct
 * ordering. In the future we may expose some of the utilities in
 * ReactMultiChild to make this easy, but for now React itself does not
 * directly have this concept of the union of prevChildren and nextChildren
 * so we implement it here.
 *
 * @param {object} prev prev children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @param {object} next next children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @return {object} a key set that contains all keys in `prev` and all keys
 * in `next` in a reasonable order.
 */

function mergeChildMappings(prev, next) {
  prev = prev || {};
  next = next || {};

  function getValueForKey(key) {
    return key in next ? next[key] : prev[key];
  } // For each key of `next`, the list of keys to insert before that key in
  // the combined list


  var nextKeysPending = Object.create(null);
  var pendingKeys = [];

  for (var prevKey in prev) {
    if (prevKey in next) {
      if (pendingKeys.length) {
        nextKeysPending[prevKey] = pendingKeys;
        pendingKeys = [];
      }
    } else {
      pendingKeys.push(prevKey);
    }
  }

  var i;
  var childMapping = {};

  for (var nextKey in next) {
    if (nextKeysPending[nextKey]) {
      for (i = 0; i < nextKeysPending[nextKey].length; i++) {
        var pendingNextKey = nextKeysPending[nextKey][i];
        childMapping[nextKeysPending[nextKey][i]] = getValueForKey(pendingNextKey);
      }
    }

    childMapping[nextKey] = getValueForKey(nextKey);
  } // Finally, add the keys which didn't appear before any key in `next`


  for (i = 0; i < pendingKeys.length; i++) {
    childMapping[pendingKeys[i]] = getValueForKey(pendingKeys[i]);
  }

  return childMapping;
}

function getProp(child, prop, props) {
  return props[prop] != null ? props[prop] : child.props[prop];
}

function getInitialChildMapping(props, onExited) {
  return getChildMapping(props.children, function (child) {
    return (0,react.cloneElement)(child, {
      onExited: onExited.bind(null, child),
      in: true,
      appear: getProp(child, 'appear', props),
      enter: getProp(child, 'enter', props),
      exit: getProp(child, 'exit', props)
    });
  });
}
function getNextChildMapping(nextProps, prevChildMapping, onExited) {
  var nextChildMapping = getChildMapping(nextProps.children);
  var children = mergeChildMappings(prevChildMapping, nextChildMapping);
  Object.keys(children).forEach(function (key) {
    var child = children[key];
    if (!(0,react.isValidElement)(child)) return;
    var hasPrev = (key in prevChildMapping);
    var hasNext = (key in nextChildMapping);
    var prevChild = prevChildMapping[key];
    var isLeaving = (0,react.isValidElement)(prevChild) && !prevChild.props.in; // item is new (entering)

    if (hasNext && (!hasPrev || isLeaving)) {
      // console.log('entering', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: true,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    } else if (!hasNext && hasPrev && !isLeaving) {
      // item is old (exiting)
      // console.log('leaving', key)
      children[key] = (0,react.cloneElement)(child, {
        in: false
      });
    } else if (hasNext && hasPrev && (0,react.isValidElement)(prevChild)) {
      // item hasn't changed transition states
      // copy over the last transition props;
      // console.log('unchanged', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: prevChild.props.in,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    }
  });
  return children;
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js









var values = Object.values || function (obj) {
  return Object.keys(obj).map(function (k) {
    return obj[k];
  });
};

var defaultProps = {
  component: 'div',
  childFactory: function childFactory(child) {
    return child;
  }
};
/**
 * The `<TransitionGroup>` component manages a set of transition components
 * (`<Transition>` and `<CSSTransition>`) in a list. Like with the transition
 * components, `<TransitionGroup>` is a state machine for managing the mounting
 * and unmounting of components over time.
 *
 * Consider the example below. As items are removed or added to the TodoList the
 * `in` prop is toggled automatically by the `<TransitionGroup>`.
 *
 * Note that `<TransitionGroup>`  does not define any animation behavior!
 * Exactly _how_ a list item animates is up to the individual transition
 * component. This means you can mix and match animations across different list
 * items.
 */

var TransitionGroup = /*#__PURE__*/function (_React$Component) {
  (0,inheritsLoose/* default */.A)(TransitionGroup, _React$Component);

  function TransitionGroup(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;

    var handleExited = _this.handleExited.bind((0,assertThisInitialized/* default */.A)(_this)); // Initial children should all be entering, dependent on appear


    _this.state = {
      contextValue: {
        isMounting: true
      },
      handleExited: handleExited,
      firstRender: true
    };
    return _this;
  }

  var _proto = TransitionGroup.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this.mounted = true;
    this.setState({
      contextValue: {
        isMounting: false
      }
    });
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    this.mounted = false;
  };

  TransitionGroup.getDerivedStateFromProps = function getDerivedStateFromProps(nextProps, _ref) {
    var prevChildMapping = _ref.children,
        handleExited = _ref.handleExited,
        firstRender = _ref.firstRender;
    return {
      children: firstRender ? getInitialChildMapping(nextProps, handleExited) : getNextChildMapping(nextProps, prevChildMapping, handleExited),
      firstRender: false
    };
  } // node is `undefined` when user provided `nodeRef` prop
  ;

  _proto.handleExited = function handleExited(child, node) {
    var currentChildMapping = getChildMapping(this.props.children);
    if (child.key in currentChildMapping) return;

    if (child.props.onExited) {
      child.props.onExited(node);
    }

    if (this.mounted) {
      this.setState(function (state) {
        var children = (0,esm_extends/* default */.A)({}, state.children);

        delete children[child.key];
        return {
          children: children
        };
      });
    }
  };

  _proto.render = function render() {
    var _this$props = this.props,
        Component = _this$props.component,
        childFactory = _this$props.childFactory,
        props = (0,objectWithoutPropertiesLoose/* default */.A)(_this$props, ["component", "childFactory"]);

    var contextValue = this.state.contextValue;
    var children = values(this.state.children).map(childFactory);
    delete props.appear;
    delete props.enter;
    delete props.exit;

    if (Component === null) {
      return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
        value: contextValue
      }, children);
    }

    return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
      value: contextValue
    }, /*#__PURE__*/react.createElement(Component, props, children));
  };

  return TransitionGroup;
}(react.Component);

TransitionGroup.propTypes =  false ? 0 : {};
TransitionGroup.defaultProps = defaultProps;
/* harmony default export */ const esm_TransitionGroup = (TransitionGroup);

/***/ }),

/***/ "../../packages/js/components/src/filter-picker/stories/filter-picker.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url.js");
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url-search-params.js");
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../packages/js/components/src/filter-picker/index.js");






/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var query = {
  meal: 'breakfast'
};
var config = {
  label: 'Meal',
  staticParams: [],
  param: 'meal',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: 'Breakfast',
    value: 'breakfast'
  }, {
    label: 'Lunch',
    value: 'lunch',
    subFilters: [{
      label: 'Meat',
      value: 'meat',
      path: ['lunch']
    }, {
      label: 'Vegan',
      value: 'vegan',
      path: ['lunch']
    }, {
      label: 'Pescatarian',
      value: 'fish',
      path: ['lunch'],
      subFilters: [{
        label: 'Snapper',
        value: 'snapper',
        path: ['lunch', 'fish']
      }, {
        label: 'Cod',
        value: 'cod',
        path: ['lunch', 'fish']
      },
      // Specify a custom component to render (Work in Progress)
      {
        label: 'Other',
        value: 'other_fish',
        path: ['lunch', 'fish'],
        component: 'OtherFish'
      }]
    }]
  }, {
    label: 'Dinner',
    value: 'dinner'
  }]
};
var Basic = function Basic(_ref) {
  var _ref$path = _ref.path,
    path = _ref$path === void 0 ? new URL(document.location).searchParams.get('path') : _ref$path;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_6__.createElement)(___WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
    config: config,
    path: path,
    query: query
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/components/FilterPicker',
  component: ___WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "({\n  path = new URL(document.location).searchParams.get('path')\n}) => {\n  return <FilterPicker config={config} path={path} query={query} />;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ })

}]);