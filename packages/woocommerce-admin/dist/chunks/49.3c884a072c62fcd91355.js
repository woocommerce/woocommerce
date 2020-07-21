(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[49],{

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

/***/ 146:
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(419);

var iterableToArrayLimit = __webpack_require__(420);

var unsupportedIterableToArray = __webpack_require__(172);

var nonIterableRest = __webpack_require__(421);

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;

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

/***/ 266:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_create_higher_order_component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(54);
/* harmony import */ var _hooks_use_instance_id__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(112);



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

/* harmony default export */ __webpack_exports__["a"] = (Object(_utils_create_higher_order_component__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(function (WrappedComponent) {
  return function (props) {
    var instanceId = Object(_hooks_use_instance_id__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(WrappedComponent);
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(WrappedComponent, Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, props, {
      instanceId: instanceId
    }));
  };
}, 'withInstanceId'));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 419:
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;

/***/ }),

/***/ 420:
/***/ (function(module, exports) {

function _iterableToArrayLimit(arr, i) {
  if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return;
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;

/***/ }),

/***/ 421:
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;

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

/***/ 722:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(12);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(14);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__(9);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__(7);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(4);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js + 1 modules
var inherits = __webpack_require__(10);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js
var with_instance_id = __webpack_require__(266);

// EXTERNAL MODULE: ./node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__(59);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(5);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var keycodes_build_module = __webpack_require__(31);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/isolated-event-container/index.js
var isolated_event_container = __webpack_require__(85);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js
var with_focus_outside = __webpack_require__(84);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-return/index.js + 1 modules
var with_focus_return = __webpack_require__(86);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-constrained-tabbing/index.js
var with_constrained_tabbing = __webpack_require__(83);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/modal/frame.js








function _createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

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






var frame_ModalFrame = /*#__PURE__*/function (_Component) {
  Object(inherits["a" /* default */])(ModalFrame, _Component);

  var _super = _createSuper(ModalFrame);

  function ModalFrame() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, ModalFrame);

    _this = _super.apply(this, arguments);
    _this.containerRef = Object(external_this_wp_element_["createRef"])();
    _this.handleKeyDown = _this.handleKeyDown.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.handleFocusOutside = _this.handleFocusOutside.bind(Object(assertThisInitialized["a" /* default */])(_this));
    return _this;
  }
  /**
   * Focuses the first tabbable element when props.focusOnMount is true.
   */


  Object(createClass["a" /* default */])(ModalFrame, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      // Focus on mount
      if (this.props.focusOnMount) {
        this.containerRef.current.focus();
      }
    }
    /**
     * Callback function called when clicked outside the modal.
     *
     * @param {Object} event Mouse click event.
     */

  }, {
    key: "handleFocusOutside",
    value: function handleFocusOutside(event) {
      if (this.props.shouldCloseOnClickOutside) {
        this.onRequestClose(event);
      }
    }
    /**
     * Callback function called when a key is pressed.
     *
     * @param {KeyboardEvent} event Key down event.
     */

  }, {
    key: "handleKeyDown",
    value: function handleKeyDown(event) {
      if (event.keyCode === keycodes_build_module["b" /* ESCAPE */]) {
        this.handleEscapeKeyDown(event);
      }
    }
    /**
     * Handles a escape key down event.
     *
     * Calls onRequestClose and prevents propagation of the event outside the modal.
     *
     * @param {Object} event Key down event.
     */

  }, {
    key: "handleEscapeKeyDown",
    value: function handleEscapeKeyDown(event) {
      if (this.props.shouldCloseOnEsc) {
        event.stopPropagation();
        this.onRequestClose(event);
      }
    }
    /**
     * Calls the onRequestClose callback props when it is available.
     *
     * @param {Object} event Event object.
     */

  }, {
    key: "onRequestClose",
    value: function onRequestClose(event) {
      var onRequestClose = this.props.onRequestClose;

      if (onRequestClose) {
        onRequestClose(event);
      }
    }
    /**
     * Renders the modal frame element.
     *
     * @return {WPElement} The modal frame element.
     */

  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          overlayClassName = _this$props.overlayClassName,
          contentLabel = _this$props.contentLabel,
          _this$props$aria = _this$props.aria,
          describedby = _this$props$aria.describedby,
          labelledby = _this$props$aria.labelledby,
          children = _this$props.children,
          className = _this$props.className,
          role = _this$props.role,
          style = _this$props.style;
      return Object(external_this_wp_element_["createElement"])(isolated_event_container["a" /* default */], {
        className: classnames_default()('components-modal__screen-overlay', overlayClassName),
        onKeyDown: this.handleKeyDown
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: classnames_default()('components-modal__frame', className),
        style: style,
        ref: this.containerRef,
        role: role,
        "aria-label": contentLabel,
        "aria-labelledby": contentLabel ? null : labelledby,
        "aria-describedby": describedby,
        tabIndex: "-1"
      }, children));
    }
  }]);

  return ModalFrame;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var modal_frame = (Object(compose["a" /* default */])([with_focus_return["a" /* default */], with_constrained_tabbing["a" /* default */], with_focus_outside["a" /* default */]])(frame_ModalFrame));
//# sourceMappingURL=frame.js.map
// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/icons/build-module/library/close.js
var library_close = __webpack_require__(209);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(67);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/modal/header.js


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



var header_ModalHeader = function ModalHeader(_ref) {
  var icon = _ref.icon,
      title = _ref.title,
      onClose = _ref.onClose,
      closeLabel = _ref.closeLabel,
      headingId = _ref.headingId,
      isDismissible = _ref.isDismissible;
  var label = closeLabel ? closeLabel : Object(external_this_wp_i18n_["__"])('Close dialog');
  return Object(external_this_wp_element_["createElement"])("div", {
    className: "components-modal__header"
  }, Object(external_this_wp_element_["createElement"])("div", {
    className: "components-modal__header-heading-container"
  }, icon && Object(external_this_wp_element_["createElement"])("span", {
    className: "components-modal__icon-container",
    "aria-hidden": true
  }, icon), title && Object(external_this_wp_element_["createElement"])("h1", {
    id: headingId,
    className: "components-modal__header-heading"
  }, title)), isDismissible && Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
    onClick: onClose,
    icon: library_close["a" /* default */],
    label: label
  }));
};

/* harmony default export */ var header = (header_ModalHeader);
//# sourceMappingURL=header.js.map
// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/modal/aria-helper.js
/**
 * External dependencies
 */

var LIVE_REGION_ARIA_ROLES = new Set(['alert', 'status', 'log', 'marquee', 'timer']);
var hiddenElements = [],
    isHidden = false;
/**
 * Hides all elements in the body element from screen-readers except
 * the provided element and elements that should not be hidden from
 * screen-readers.
 *
 * The reason we do this is because `aria-modal="true"` currently is bugged
 * in Safari, and support is spotty in other browsers overall. In the future
 * we should consider removing these helper functions in favor of
 * `aria-modal="true"`.
 *
 * @param {Element} unhiddenElement The element that should not be hidden.
 */

function hideApp(unhiddenElement) {
  if (isHidden) {
    return;
  }

  var elements = document.body.children;
  Object(external_lodash_["forEach"])(elements, function (element) {
    if (element === unhiddenElement) {
      return;
    }

    if (elementShouldBeHidden(element)) {
      element.setAttribute('aria-hidden', 'true');
      hiddenElements.push(element);
    }
  });
  isHidden = true;
}
/**
 * Determines if the passed element should not be hidden from screen readers.
 *
 * @param {HTMLElement} element The element that should be checked.
 *
 * @return {boolean} Whether the element should not be hidden from screen-readers.
 */

function elementShouldBeHidden(element) {
  var role = element.getAttribute('role');
  return !(element.tagName === 'SCRIPT' || element.hasAttribute('aria-hidden') || element.hasAttribute('aria-live') || LIVE_REGION_ARIA_ROLES.has(role));
}
/**
 * Makes all elements in the body that have been hidden by `hideApp`
 * visible again to screen-readers.
 */

function showApp() {
  if (!isHidden) {
    return;
  }

  Object(external_lodash_["forEach"])(hiddenElements, function (element) {
    element.removeAttribute('aria-hidden');
  });
  hiddenElements = [];
  isHidden = false;
}
//# sourceMappingURL=aria-helper.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/modal/index.js









function modal_createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (modal_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

function modal_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */



 // Used to count the number of open modals.

var parentElement,
    openModalCount = 0;

var modal_Modal = /*#__PURE__*/function (_Component) {
  Object(inherits["a" /* default */])(Modal, _Component);

  var _super = modal_createSuper(Modal);

  function Modal(props) {
    var _this;

    Object(classCallCheck["a" /* default */])(this, Modal);

    _this = _super.call(this, props);

    _this.prepareDOM();

    return _this;
  }
  /**
   * Appends the modal's node to the DOM, so the portal can render the
   * modal in it. Also calls the openFirstModal when this is the first modal to be
   * opened.
   */


  Object(createClass["a" /* default */])(Modal, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      openModalCount++;

      if (openModalCount === 1) {
        this.openFirstModal();
      }
    }
    /**
     * Removes the modal's node from the DOM. Also calls closeLastModal when this is
     * the last modal to be closed.
     */

  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      openModalCount--;

      if (openModalCount === 0) {
        this.closeLastModal();
      }

      this.cleanDOM();
    }
    /**
     * Prepares the DOM for the modals to be rendered.
     *
     * Every modal is mounted in a separate div appended to a parent div
     * that is appended to the document body.
     *
     * The parent div will be created if it does not yet exist, and the
     * separate div for this specific modal will be appended to that.
     */

  }, {
    key: "prepareDOM",
    value: function prepareDOM() {
      if (!parentElement) {
        parentElement = document.createElement('div');
        document.body.appendChild(parentElement);
      }

      this.node = document.createElement('div');
      parentElement.appendChild(this.node);
    }
    /**
     * Removes the specific mounting point for this modal from the DOM.
     */

  }, {
    key: "cleanDOM",
    value: function cleanDOM() {
      parentElement.removeChild(this.node);
    }
    /**
     * Prepares the DOM for this modal and any additional modal to be mounted.
     *
     * It appends an additional div to the body for the modals to be rendered in,
     * it hides any other elements from screen-readers and adds an additional class
     * to the body to prevent scrolling while the modal is open.
     */

  }, {
    key: "openFirstModal",
    value: function openFirstModal() {
      hideApp(parentElement);
      document.body.classList.add(this.props.bodyOpenClassName);
    }
    /**
     * Cleans up the DOM after the last modal is closed and makes the app available
     * for screen-readers again.
     */

  }, {
    key: "closeLastModal",
    value: function closeLastModal() {
      document.body.classList.remove(this.props.bodyOpenClassName);
      showApp();
    }
    /**
     * Renders the modal.
     *
     * @return {WPElement} The modal element.
     */

  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          onRequestClose = _this$props.onRequestClose,
          title = _this$props.title,
          icon = _this$props.icon,
          closeButtonLabel = _this$props.closeButtonLabel,
          children = _this$props.children,
          aria = _this$props.aria,
          instanceId = _this$props.instanceId,
          isDismissible = _this$props.isDismissible,
          isDismissable = _this$props.isDismissable,
          otherProps = Object(objectWithoutProperties["a" /* default */])(_this$props, ["onRequestClose", "title", "icon", "closeButtonLabel", "children", "aria", "instanceId", "isDismissible", "isDismissable"]);

      var headingId = aria.labelledby || "components-modal-header-".concat(instanceId);

      if (isDismissable) {
        Object(build_module["a" /* default */])('isDismissable prop of the Modal component', {
          alternative: 'isDismissible prop (renamed) of the Modal component'
        });
      } // Disable reason: this stops mouse events from triggering tooltips and
      // other elements underneath the modal overlay.


      return Object(external_this_wp_element_["createPortal"])(Object(external_this_wp_element_["createElement"])(modal_frame, Object(esm_extends["a" /* default */])({
        onRequestClose: onRequestClose,
        aria: {
          labelledby: title ? headingId : null,
          describedby: aria.describedby
        }
      }, otherProps), Object(external_this_wp_element_["createElement"])("div", {
        className: 'components-modal__content',
        role: "document"
      }, Object(external_this_wp_element_["createElement"])(header, {
        closeLabel: closeButtonLabel,
        headingId: headingId,
        icon: icon,
        isDismissible: isDismissible || isDismissable,
        onClose: onRequestClose,
        title: title
      }), children)), this.node);
    }
  }]);

  return Modal;
}(external_this_wp_element_["Component"]);

modal_Modal.defaultProps = {
  bodyOpenClassName: 'modal-open',
  role: 'dialog',
  title: null,
  focusOnMount: true,
  shouldCloseOnEsc: true,
  shouldCloseOnClickOutside: true,
  isDismissible: true,

  /* accessibility */
  aria: {
    labelledby: null,
    describedby: null
  }
};
/* harmony default export */ var modal = __webpack_exports__["a"] = (Object(with_instance_id["a" /* default */])(modal_Modal));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 725:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(10);
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(266);
/* harmony import */ var _navigable_container__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(418);
/* harmony import */ var _button__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(67);











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

var TabPanel = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(TabPanel, _Component);

  var _super = _createSuper(TabPanel);

  function TabPanel() {
    var _this;

    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(this, TabPanel);

    _this = _super.apply(this, arguments);
    var _this$props = _this.props,
        tabs = _this$props.tabs,
        initialTabName = _this$props.initialTabName;
    _this.handleClick = _this.handleClick.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
    _this.onNavigate = _this.onNavigate.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
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
          className: classnames__WEBPACK_IMPORTED_MODULE_10___default()('components-tab-panel__tabs-item', tab.className, Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, activeClass, tab.name === selected)),
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
        className: "components-tab-panel__tab-content"
      }, this.props.children(selectedTab)));
    }
  }]);

  return TabPanel;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_12__[/* default */ "a"])(TabPanel));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 726:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(32);




/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



function FormToggle(_ref) {
  var className = _ref.className,
      checked = _ref.checked,
      id = _ref.id,
      _ref$onChange = _ref.onChange,
      onChange = _ref$onChange === void 0 ? lodash__WEBPACK_IMPORTED_MODULE_4__["noop"] : _ref$onChange,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["className", "checked", "id", "onChange"]);

  var wrapperClasses = classnames__WEBPACK_IMPORTED_MODULE_3___default()('components-form-toggle', className, {
    'is-checked': checked
  });
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("span", {
    className: wrapperClasses
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("input", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    className: "components-form-toggle__input",
    id: id,
    type: "checkbox",
    checked: checked,
    onChange: onChange
  }, props)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("span", {
    className: "components-form-toggle__track"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("span", {
    className: "components-form-toggle__thumb"
  }), checked ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_5__[/* SVG */ "b"], {
    className: "components-form-toggle__on",
    width: "2",
    height: "6",
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 2 6"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_5__[/* Path */ "a"], {
    d: "M0 0h2v6H0z"
  })) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_5__[/* SVG */ "b"], {
    className: "components-form-toggle__off",
    width: "6",
    height: "6",
    "aria-hidden": "true",
    role: "img",
    focusable: "false",
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 6 6"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_5__[/* Path */ "a"], {
    d: "M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"
  })));
}

/* harmony default export */ __webpack_exports__["a"] = (FormToggle);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 731:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(32);


/**
 * WordPress dependencies
 */

var upload = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* SVG */ "b"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* Path */ "a"], {
  d: "M18.5 15v3.5H13V6.7l4.5 4.1 1-1.1-5.7-5.3-.6.6-.5-.5-5.2 5.2 1 1.1 4-4v11.7h-6V15H4v5h16v-5z"
}));
/* harmony default export */ __webpack_exports__["a"] = (upload);
//# sourceMappingURL=upload.js.map

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

/***/ 902:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Context; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return Consumer; });
/* harmony import */ var _babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(15);
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
/* harmony import */ var _wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(65);
/* harmony import */ var _wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9__);









function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



var Context = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createContext"])({
  addDropZone: function addDropZone() {},
  removeDropZone: function removeDropZone() {}
});
var Provider = Context.Provider,
    Consumer = Context.Consumer;

var getDragEventType = function getDragEventType(_ref) {
  var dataTransfer = _ref.dataTransfer;

  if (dataTransfer) {
    // Use lodash `includes` here as in the Edge browser `types` is implemented
    // as a DomStringList, whereas in other browsers it's an array. `includes`
    // happily works with both types.
    if (Object(lodash__WEBPACK_IMPORTED_MODULE_8__["includes"])(dataTransfer.types, 'Files')) {
      return 'file';
    }

    if (Object(lodash__WEBPACK_IMPORTED_MODULE_8__["includes"])(dataTransfer.types, 'text/html')) {
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

var DropZoneProvider = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(DropZoneProvider, _Component);

  var _super = _createSuper(DropZoneProvider);

  function DropZoneProvider() {
    var _this;

    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(this, DropZoneProvider);

    _this = _super.apply(this, arguments); // Event listeners

    _this.onDragOver = _this.onDragOver.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
    _this.onDrop = _this.onDrop.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this)); // Context methods so this component can receive data from consumers

    _this.addDropZone = _this.addDropZone.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
    _this.removeDropZone = _this.removeDropZone.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this)); // Utility methods

    _this.resetDragState = _this.resetDragState.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
    _this.toggleDraggingOverDocument = Object(lodash__WEBPACK_IMPORTED_MODULE_8__["throttle"])(_this.toggleDraggingOverDocument.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this)), 200);
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

  Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(DropZoneProvider, [{
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
      this.dropZones = Object(lodash__WEBPACK_IMPORTED_MODULE_8__["filter"])(this.dropZones, function (dz) {
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

      var hoveredDropZones = Object(lodash__WEBPACK_IMPORTED_MODULE_8__["filter"])(this.dropZones, function (dropZone) {
        return isTypeSupportedByDropZone(dragEventType, dropZone) && isWithinElementBounds(dropZone.element.current, detail.clientX, detail.clientY);
      }); // Find the leaf dropzone not containing another dropzone

      var hoveredDropZone = Object(lodash__WEBPACK_IMPORTED_MODULE_8__["find"])(hoveredDropZones, function (zone) {
        var container = zone.isRelative ? zone.element.current.parentElement : zone.element.current;
        return !Object(lodash__WEBPACK_IMPORTED_MODULE_8__["some"])(hoveredDropZones, function (subZone) {
          return subZone !== zone && container.contains(subZone.element.current);
        });
      });
      var hoveredDropZoneIndex = this.dropZones.indexOf(hoveredDropZone);
      var position = null;

      if (hoveredDropZone && hoveredDropZone.withPosition) {
        position = {
          x: detail.clientX,
          y: detail.clientY
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
      } else if (hoveredDropZone && hoveredDropZoneIndex === this.state.hoveredDropZone && !Object(lodash__WEBPACK_IMPORTED_MODULE_8__["isEqual"])(position, this.state.position)) {
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

      if (!_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9___default()(newState, this.state)) {
        this.setState(newState);
      }
    }
  }, {
    key: "onDragOver",
    value: function onDragOver(event) {
      this.toggleDraggingOverDocument(event, getDragEventType(event));
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
      var dragEventType = getDragEventType(event);
      var dropZone = this.dropZones[hoveredDropZone];
      this.resetDragState();

      if (dropZone) {
        switch (dragEventType) {
          case 'file':
            dropZone.onFilesDrop(Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(event.dataTransfer.files), position);
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("div", {
        onDrop: this.onDrop,
        className: "components-drop-zone__provider"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(Provider, {
        value: this.dropZoneCallbacks
      }, this.props.children));
    }
  }]);

  return DropZoneProvider;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

/* harmony default export */ __webpack_exports__["c"] = (DropZoneProvider);

//# sourceMappingURL=provider.js.map

/***/ }),

/***/ 926:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(10);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _button__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(67);










function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



var FormFileUpload = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])(FormFileUpload, _Component);

  var _super = _createSuper(FormFileUpload);

  function FormFileUpload() {
    var _this;

    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(this, FormFileUpload);

    _this = _super.apply(this, arguments);
    _this.openFileDialog = _this.openFileDialog.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(_this));
    _this.bindInput = _this.bindInput.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(_this));
    return _this;
  }

  Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(FormFileUpload, [{
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
          _this$props$multiple = _this$props.multiple,
          multiple = _this$props$multiple === void 0 ? false : _this$props$multiple,
          onChange = _this$props.onChange,
          render = _this$props.render,
          props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_this$props, ["accept", "children", "multiple", "onChange", "render"]);

      var ui = render ? render({
        openFileDialog: this.openFileDialog
      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_button__WEBPACK_IMPORTED_MODULE_9__[/* default */ "a"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
        onClick: this.openFileDialog
      }, props), children);
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "components-form-file-upload"
      }, ui, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("input", {
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
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

/* harmony default export */ __webpack_exports__["a"] = (FormFileUpload);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 927:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export useDropZone */
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_slicedToArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(19);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(422);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(731);
/* harmony import */ var _provider__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(902);





/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */


function useDropZone(_ref) {
  var element = _ref.element,
      onFilesDrop = _ref.onFilesDrop,
      onHTMLDrop = _ref.onHTMLDrop,
      onDrop = _ref.onDrop,
      isDisabled = _ref.isDisabled,
      withPosition = _ref.withPosition,
      _ref$__unstableIsRela = _ref.__unstableIsRelative,
      __unstableIsRelative = _ref$__unstableIsRela === void 0 ? false : _ref$__unstableIsRela;

  var _useContext = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useContext"])(_provider__WEBPACK_IMPORTED_MODULE_8__[/* Context */ "a"]),
      addDropZone = _useContext.addDropZone,
      removeDropZone = _useContext.removeDropZone;

  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useState"])({
    isDraggingOverDocument: false,
    isDraggingOverElement: false,
    type: null
  }),
      _useState2 = Object(_babel_runtime_helpers_esm_slicedToArray__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_useState, 2),
      state = _useState2[0],
      setState = _useState2[1];

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useEffect"])(function () {
    if (!isDisabled) {
      var dropZone = {
        element: element,
        onDrop: onDrop,
        onFilesDrop: onFilesDrop,
        onHTMLDrop: onHTMLDrop,
        setState: setState,
        withPosition: withPosition,
        isRelative: __unstableIsRelative
      };
      addDropZone(dropZone);
      return function () {
        removeDropZone(dropZone);
      };
    }
  }, [isDisabled, onDrop, onFilesDrop, onHTMLDrop, withPosition]);
  return state;
}

var DropZone = function DropZone(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_provider__WEBPACK_IMPORTED_MODULE_8__[/* DropZoneConsumer */ "b"], null, function (_ref2) {
    var addDropZone = _ref2.addDropZone,
        removeDropZone = _ref2.removeDropZone;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(DropZoneComponent, Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])({
      addDropZone: addDropZone,
      removeDropZone: removeDropZone
    }, props));
  });
};

function DropZoneComponent(_ref3) {
  var className = _ref3.className,
      label = _ref3.label,
      onFilesDrop = _ref3.onFilesDrop,
      onHTMLDrop = _ref3.onHTMLDrop,
      onDrop = _ref3.onDrop;
  var element = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useRef"])();

  var _useDropZone = useDropZone({
    element: element,
    onFilesDrop: onFilesDrop,
    onHTMLDrop: onHTMLDrop,
    onDrop: onDrop,
    __unstableIsRelative: true
  }),
      isDraggingOverDocument = _useDropZone.isDraggingOverDocument,
      isDraggingOverElement = _useDropZone.isDraggingOverElement,
      type = _useDropZone.type;

  var children;

  if (isDraggingOverElement) {
    children = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("div", {
      className: "components-drop-zone__content"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"],
      size: "40",
      className: "components-drop-zone__content-icon"
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("span", {
      className: "components-drop-zone__content-text"
    }, label ? label : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Drop files to upload')));
  }

  var classes = classnames__WEBPACK_IMPORTED_MODULE_4___default()('components-drop-zone', className, Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    'is-active': (isDraggingOverDocument || isDraggingOverElement) && (type === 'file' && onFilesDrop || type === 'html' && onHTMLDrop || type === 'default' && onDrop),
    'is-dragging-over-document': isDraggingOverDocument,
    'is-dragging-over-element': isDraggingOverElement
  }, "is-dragging-".concat(type), !!type));
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("div", {
    ref: element,
    className: classes
  }, children);
}

/* harmony default export */ __webpack_exports__["a"] = (DropZone);
//# sourceMappingURL=index.js.map

/***/ })

}]);
