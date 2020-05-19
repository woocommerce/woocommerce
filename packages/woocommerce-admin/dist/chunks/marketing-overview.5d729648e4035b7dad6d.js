(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["marketing-overview"],{

/***/ "./client/marketing/components/button/index.js":
/*!*****************************************************!*\
  !*** ./client/marketing/components/button/index.js ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/components/button/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_4__);



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (function (props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Button"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, props, {
    className: classnames__WEBPACK_IMPORTED_MODULE_3___default()(props.className, 'woocommere-admin-marketing-button')
  }));
});

/***/ }),

/***/ "./client/marketing/components/button/style.scss":
/*!*******************************************************!*\
  !*** ./client/marketing/components/button/style.scss ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/marketing/components/index.js":
/*!**********************************************!*\
  !*** ./client/marketing/components/index.js ***!
  \**********************************************/
/*! exports provided: Button, ProductIcon, Slider */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _button__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./button */ "./client/marketing/components/button/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Button", function() { return _button__WEBPACK_IMPORTED_MODULE_0__["default"]; });

/* harmony import */ var _product_icon__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./product-icon */ "./client/marketing/components/product-icon/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "ProductIcon", function() { return _product_icon__WEBPACK_IMPORTED_MODULE_1__["default"]; });

/* harmony import */ var _slider__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./slider */ "./client/marketing/components/slider/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Slider", function() { return _slider__WEBPACK_IMPORTED_MODULE_2__["default"]; });





/***/ }),

/***/ "./client/marketing/components/product-icon/index.js":
/*!***********************************************************!*\
  !*** ./client/marketing/components/product-icon/index.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/components/product-icon/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_8__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



var ProductIcon = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(ProductIcon, _Component);

  var _super = _createSuper(ProductIcon);

  function ProductIcon() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ProductIcon);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ProductIcon, [{
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("img", {
        src: this.props.src,
        className: classnames__WEBPACK_IMPORTED_MODULE_6___default()(this.props.className, 'woocommere-admin-marketing-product-icon'),
        alt: ""
      });
    }
  }]);

  return ProductIcon;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

ProductIcon.propTypes = {
  /**
   * Icon src.
   */
  src: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string.isRequired,

  /**
   * Additional classNames.
   */
  className: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string
};
/* harmony default export */ __webpack_exports__["default"] = (ProductIcon);

/***/ }),

/***/ "./client/marketing/components/product-icon/style.scss":
/*!*************************************************************!*\
  !*** ./client/marketing/components/product-icon/style.scss ***!
  \*************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/marketing/components/slider/index.js":
/*!*****************************************************!*\
  !*** ./client/marketing/components/slider/index.js ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react-transition-group */ "./node_modules/react-transition-group/esm/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/components/slider/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_11__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



var Slider = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Slider, _Component);

  var _super = _createSuper(Slider);

  function Slider(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, Slider);

    _this = _super.call(this, props);
    _this.state = {
      height: null
    };
    _this.container = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createRef"])();
    _this.onEnter = _this.onEnter.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.updateSliderHeight = _this.updateSliderHeight.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }
  /**
   * Update the slider height on Resize
   */


  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(Slider, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      // Update the slider height on Resize
      window.addEventListener('resize', Object(lodash__WEBPACK_IMPORTED_MODULE_10__["debounce"])(this.updateSliderHeight, 50));
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      window.removeEventListener('resize', this.updateSliderHeight);
    }
  }, {
    key: "updateSliderHeight",
    value: function updateSliderHeight() {
      var slide = this.container.current.querySelector('.woocommerce-marketing-slider__slide');
      this.setState({
        height: slide.clientHeight
      });
    }
    /**
     * Fix slider height before a slide enters because slides are absolutely position
     */

  }, {
    key: "onEnter",
    value: function onEnter() {
      var newSlide = this.container.current.querySelector('.slide-enter');
      this.setState({
        height: newSlide.clientHeight
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          children = _this$props.children,
          animationKey = _this$props.animationKey,
          animate = _this$props.animate;
      var height = this.state.height;
      var containerClasses = classnames__WEBPACK_IMPORTED_MODULE_7___default()('woocommerce-marketing-slider', animate && "animate-".concat(animate));
      var style = {};

      if (height) {
        style.height = height;
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: containerClasses,
        ref: this.container,
        style: style
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(react_transition_group__WEBPACK_IMPORTED_MODULE_8__["TransitionGroup"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(react_transition_group__WEBPACK_IMPORTED_MODULE_8__["CSSTransition"] // timeout should be slightly longer than the CSS animation
      , {
        timeout: 320,
        classNames: "slide",
        key: animationKey,
        onEnter: this.onEnter
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-marketing-slider__slide"
      }, children))));
    }
  }]);

  return Slider;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

Slider.propTypes = {
  /**
   * A unique identifier for each slideable page.
   */
  animationKey: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.any.isRequired,

  /**
   * null, 'left', 'right', to designate which direction to slide on a change.
   */
  animate: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.oneOf([null, 'left', 'right'])
};
/* harmony default export */ __webpack_exports__["default"] = (Slider);

/***/ }),

/***/ "./client/marketing/components/slider/style.scss":
/*!*******************************************************!*\
  !*** ./client/marketing/components/slider/style.scss ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/marketing/data/actions.js":
/*!******************************************!*\
  !*** ./client/marketing/data/actions.js ***!
  \******************************************/
/*! exports provided: receiveInstalledPlugins, receiveActivatingPlugin, removeActivatingPlugin, receiveRecommendedPlugins, receiveBlogPosts, handleFetchError, activateInstalledPlugin, loadInstalledPluginsAfterActivation */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "receiveInstalledPlugins", function() { return receiveInstalledPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "receiveActivatingPlugin", function() { return receiveActivatingPlugin; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "removeActivatingPlugin", function() { return removeActivatingPlugin; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "receiveRecommendedPlugins", function() { return receiveRecommendedPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "receiveBlogPosts", function() { return receiveBlogPosts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "handleFetchError", function() { return handleFetchError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "activateInstalledPlugin", function() { return activateInstalledPlugin; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "loadInstalledPluginsAfterActivation", function() { return loadInstalledPluginsAfterActivation; });
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./client/marketing/data/constants.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
var _marked = /*#__PURE__*/regeneratorRuntime.mark(activateInstalledPlugin),
    _marked2 = /*#__PURE__*/regeneratorRuntime.mark(loadInstalledPluginsAfterActivation);

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function receiveInstalledPlugins(plugins) {
  return {
    type: 'SET_INSTALLED_PLUGINS',
    plugins: plugins
  };
}
function receiveActivatingPlugin(pluginSlug) {
  return {
    type: 'SET_ACTIVATING_PLUGIN',
    pluginSlug: pluginSlug
  };
}
function removeActivatingPlugin(pluginSlug) {
  return {
    type: 'REMOVE_ACTIVATING_PLUGIN',
    pluginSlug: pluginSlug
  };
}
function receiveRecommendedPlugins(plugins) {
  return {
    type: 'SET_RECOMMENDED_PLUGINS',
    plugins: plugins
  };
}
function receiveBlogPosts(posts) {
  return {
    type: 'SET_BLOG_POSTS',
    posts: posts
  };
}
function handleFetchError(error, message) {
  var _dispatch = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["dispatch"])('core/notices'),
      createNotice = _dispatch.createNotice;

  createNotice('error', message); // eslint-disable-next-line no-console

  console.log(error);
}
function activateInstalledPlugin(pluginSlug) {
  var _dispatch2, createNotice, response;

  return regeneratorRuntime.wrap(function activateInstalledPlugin$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _dispatch2 = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["dispatch"])('core/notices'), createNotice = _dispatch2.createNotice;
          _context.next = 3;
          return receiveActivatingPlugin(pluginSlug);

        case 3:
          _context.prev = 3;
          _context.next = 6;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: _constants__WEBPACK_IMPORTED_MODULE_2__["API_NAMESPACE"] + '/overview/activate-plugin',
            method: 'POST',
            data: {
              plugin: pluginSlug
            }
          });

        case 6:
          response = _context.sent;

          if (!response) {
            _context.next = 14;
            break;
          }

          _context.next = 10;
          return createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('The extension has been successfully activated.', 'woocommerce'));

        case 10:
          _context.next = 12;
          return loadInstalledPluginsAfterActivation(pluginSlug);

        case 12:
          _context.next = 15;
          break;

        case 14:
          throw new Error();

        case 15:
          _context.next = 23;
          break;

        case 17:
          _context.prev = 17;
          _context.t0 = _context["catch"](3);
          _context.next = 21;
          return handleFetchError(_context.t0, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('There was an error trying to activate the extension.', 'woocommerce'));

        case 21:
          _context.next = 23;
          return removeActivatingPlugin(pluginSlug);

        case 23:
          return _context.abrupt("return", true);

        case 24:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[3, 17]]);
}
function loadInstalledPluginsAfterActivation(activatedPluginSlug) {
  var response;
  return regeneratorRuntime.wrap(function loadInstalledPluginsAfterActivation$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.prev = 0;
          _context2.next = 3;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_0__["apiFetch"])({
            path: "".concat(_constants__WEBPACK_IMPORTED_MODULE_2__["API_NAMESPACE"], "/overview/installed-plugins")
          });

        case 3:
          response = _context2.sent;

          if (!response) {
            _context2.next = 11;
            break;
          }

          _context2.next = 7;
          return receiveInstalledPlugins(response);

        case 7:
          _context2.next = 9;
          return removeActivatingPlugin(activatedPluginSlug);

        case 9:
          _context2.next = 12;
          break;

        case 11:
          throw new Error();

        case 12:
          _context2.next = 18;
          break;

        case 14:
          _context2.prev = 14;
          _context2.t0 = _context2["catch"](0);
          _context2.next = 18;
          return handleFetchError(_context2.t0, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('There was an error loading installed extensions.', 'woocommerce'));

        case 18:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2, null, [[0, 14]]);
}

/***/ }),

/***/ "./client/marketing/data/constants.js":
/*!********************************************!*\
  !*** ./client/marketing/data/constants.js ***!
  \********************************************/
/*! exports provided: STORE_KEY, API_NAMESPACE */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "STORE_KEY", function() { return STORE_KEY; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "API_NAMESPACE", function() { return API_NAMESPACE; });
var STORE_KEY = 'wc/marketing';
var API_NAMESPACE = '/wc-admin/marketing';

/***/ }),

/***/ "./client/marketing/data/index.js":
/*!****************************************!*\
  !*** ./client/marketing/data/index.js ***!
  \****************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./constants */ "./client/marketing/data/constants.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./actions */ "./client/marketing/data/actions.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./selectors */ "./client/marketing/data/selectors.js");
/* harmony import */ var _resolvers__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./resolvers */ "./client/marketing/data/resolvers.js");



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */






var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_4__["getSetting"])('marketing', {}),
    installedExtensions = _getSetting.installedExtensions;

var DEFAULT_STATE = {
  installedPlugins: installedExtensions,
  activatingPlugins: [],
  recommendedPlugins: [],
  blogPosts: []
};
Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["registerStore"])(_constants__WEBPACK_IMPORTED_MODULE_6__["STORE_KEY"], {
  actions: _actions__WEBPACK_IMPORTED_MODULE_7__,
  selectors: _selectors__WEBPACK_IMPORTED_MODULE_8__,
  resolvers: _resolvers__WEBPACK_IMPORTED_MODULE_9__,
  controls: _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__["controls"],
  reducer: function reducer() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DEFAULT_STATE;
    var action = arguments.length > 1 ? arguments[1] : undefined;

    switch (action.type) {
      case 'SET_INSTALLED_PLUGINS':
        return _objectSpread({}, state, {
          installedPlugins: action.plugins
        });

      case 'SET_ACTIVATING_PLUGIN':
        return _objectSpread({}, state, {
          activatingPlugins: [].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default()(state.activatingPlugins), [action.pluginSlug])
        });

      case 'REMOVE_ACTIVATING_PLUGIN':
        return _objectSpread({}, state, {
          activatingPlugins: Object(lodash__WEBPACK_IMPORTED_MODULE_5__["without"])(state.activatingPlugins, action.pluginSlug)
        });

      case 'SET_RECOMMENDED_PLUGINS':
        return _objectSpread({}, state, {
          recommendedPlugins: action.plugins
        });

      case 'SET_BLOG_POSTS':
        return _objectSpread({}, state, {
          blogPosts: action.posts
        });
    }

    return state;
  }
});

/***/ }),

/***/ "./client/marketing/data/resolvers.js":
/*!********************************************!*\
  !*** ./client/marketing/data/resolvers.js ***!
  \********************************************/
/*! exports provided: getRecommendedPlugins, getBlogPosts */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getRecommendedPlugins", function() { return getRecommendedPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getBlogPosts", function() { return getBlogPosts; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./actions */ "./client/marketing/data/actions.js");
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./constants */ "./client/marketing/data/constants.js");
var _marked = /*#__PURE__*/regeneratorRuntime.mark(getRecommendedPlugins),
    _marked2 = /*#__PURE__*/regeneratorRuntime.mark(getBlogPosts);

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function getRecommendedPlugins() {
  var response;
  return regeneratorRuntime.wrap(function getRecommendedPlugins$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.prev = 0;
          _context.next = 3;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: "".concat(_constants__WEBPACK_IMPORTED_MODULE_3__["API_NAMESPACE"], "/overview/recommended?per_page=6")
          });

        case 3:
          response = _context.sent;

          if (!response) {
            _context.next = 9;
            break;
          }

          _context.next = 7;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_2__["receiveRecommendedPlugins"])(response);

        case 7:
          _context.next = 10;
          break;

        case 9:
          throw new Error();

        case 10:
          _context.next = 16;
          break;

        case 12:
          _context.prev = 12;
          _context.t0 = _context["catch"](0);
          _context.next = 16;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_2__["handleFetchError"])(_context.t0, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('There was an error loading recommended extensions.', 'woocommerce'));

        case 16:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[0, 12]]);
}
function getBlogPosts() {
  var response;
  return regeneratorRuntime.wrap(function getBlogPosts$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.prev = 0;
          _context2.next = 3;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: "".concat(_constants__WEBPACK_IMPORTED_MODULE_3__["API_NAMESPACE"], "/overview/knowledge-base"),
            method: 'GET'
          });

        case 3:
          response = _context2.sent;

          if (!response) {
            _context2.next = 9;
            break;
          }

          _context2.next = 7;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_2__["receiveBlogPosts"])(response);

        case 7:
          _context2.next = 10;
          break;

        case 9:
          throw new Error();

        case 10:
          _context2.next = 16;
          break;

        case 12:
          _context2.prev = 12;
          _context2.t0 = _context2["catch"](0);
          _context2.next = 16;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_2__["handleFetchError"])(_context2.t0, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('There was an error loading knowledge base posts.', 'woocommerce'));

        case 16:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2, null, [[0, 12]]);
}

/***/ }),

/***/ "./client/marketing/data/selectors.js":
/*!********************************************!*\
  !*** ./client/marketing/data/selectors.js ***!
  \********************************************/
/*! exports provided: getInstalledPlugins, getActivatingPlugins, getRecommendedPlugins, getBlogPosts */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getInstalledPlugins", function() { return getInstalledPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getActivatingPlugins", function() { return getActivatingPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getRecommendedPlugins", function() { return getRecommendedPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getBlogPosts", function() { return getBlogPosts; });
function getInstalledPlugins(state) {
  return state.installedPlugins;
}
function getActivatingPlugins(state) {
  return state.activatingPlugins;
}
function getRecommendedPlugins(state) {
  return state.recommendedPlugins;
}
function getBlogPosts(state) {
  return state.blogPosts;
}

/***/ }),

/***/ "./client/marketing/overview/index.js":
/*!********************************************!*\
  !*** ./client/marketing/overview/index.js ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/overview/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _installed_extensions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./installed-extensions */ "./client/marketing/overview/installed-extensions/index.js");
/* harmony import */ var _recommended_extensions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./recommended-extensions */ "./client/marketing/overview/recommended-extensions/index.js");
/* harmony import */ var _knowledge_base__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./knowledge-base */ "./client/marketing/overview/knowledge-base/index.js");
/* harmony import */ var _welcome_card__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./welcome-card */ "./client/marketing/overview/welcome-card/index.js");
/* harmony import */ var _data__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../data */ "./client/marketing/data/index.js");


/**
 * WooCommerce dependencies
 */

/**
 * Internal dependencies
 */








var MarketingOverview = function MarketingOverview() {
  var allowMarketplaceSuggestions = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('allowMarketplaceSuggestions', false);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-marketing-overview"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_welcome_card__WEBPACK_IMPORTED_MODULE_6__["default"], null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_installed_extensions__WEBPACK_IMPORTED_MODULE_3__["default"], null), allowMarketplaceSuggestions && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_recommended_extensions__WEBPACK_IMPORTED_MODULE_4__["default"], null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_knowledge_base__WEBPACK_IMPORTED_MODULE_5__["default"], null));
};

/* harmony default export */ __webpack_exports__["default"] = (MarketingOverview);

/***/ }),

/***/ "./client/marketing/overview/installed-extensions/index.js":
/*!*****************************************************************!*\
  !*** ./client/marketing/overview/installed-extensions/index.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/overview/installed-extensions/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _row__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./row */ "./client/marketing/overview/installed-extensions/row.js");
/* harmony import */ var _data_constants__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../../data/constants */ "./client/marketing/data/constants.js");








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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





var InstalledExtensions = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(InstalledExtensions, _Component);

  var _super = _createSuper(InstalledExtensions);

  function InstalledExtensions() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, InstalledExtensions);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(InstalledExtensions, [{
    key: "activatePlugin",
    value: function activatePlugin(pluginSlug) {
      var activateInstalledPlugin = this.props.activateInstalledPlugin;
      activateInstalledPlugin(pluginSlug);
    }
  }, {
    key: "isActivatingPlugin",
    value: function isActivatingPlugin(pluginSlug) {
      var activatingPlugins = this.props.activatingPlugins;
      return activatingPlugins.includes(pluginSlug);
    }
  }, {
    key: "render",
    value: function render() {
      var _this = this;

      var plugins = this.props.plugins;

      if (plugins.length === 0) {
        return null;
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["Card"], {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Installed marketing extensions', 'woocommerce'),
        className: "woocommerce-marketing-installed-extensions-card"
      }, plugins.map(function (plugin) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_row__WEBPACK_IMPORTED_MODULE_13__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          key: plugin.slug
        }, plugin, {
          activatePlugin: function activatePlugin() {
            return _this.activatePlugin(plugin.slug);
          },
          isLoading: _this.isActivatingPlugin(plugin.slug)
        }));
      }));
    }
  }]);

  return InstalledExtensions;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

InstalledExtensions.propTypes = {
  /**
   * Array of installed plugin objects.
   */
  plugins: prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.object).isRequired,

  /**
   * Array of plugins that are currently activating.
   */
  activatingPlugins: prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.string).isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_8__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_9__["withSelect"])(function (select) {
  var _select = select(_data_constants__WEBPACK_IMPORTED_MODULE_14__["STORE_KEY"]),
      getInstalledPlugins = _select.getInstalledPlugins,
      getActivatingPlugins = _select.getActivatingPlugins;

  return {
    plugins: getInstalledPlugins(),
    activatingPlugins: getActivatingPlugins()
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_9__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch(_data_constants__WEBPACK_IMPORTED_MODULE_14__["STORE_KEY"]),
      activateInstalledPlugin = _dispatch.activateInstalledPlugin;

  return {
    activateInstalledPlugin: activateInstalledPlugin
  };
}))(InstalledExtensions));

/***/ }),

/***/ "./client/marketing/overview/installed-extensions/row.js":
/*!***************************************************************!*\
  !*** ./client/marketing/overview/installed-extensions/row.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../components */ "./client/marketing/components/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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




var InstalledExtensionRow = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(InstalledExtensionRow, _Component);

  var _super = _createSuper(InstalledExtensionRow);

  function InstalledExtensionRow(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, InstalledExtensionRow);

    _this = _super.call(this, props);
    _this.onActivateClick = _this.onActivateClick.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.onFinishSetupClick = _this.onFinishSetupClick.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(InstalledExtensionRow, [{
    key: "getLinks",
    value: function getLinks() {
      var _this2 = this;

      var _this$props = this.props,
          docsUrl = _this$props.docsUrl,
          settingsUrl = _this$props.settingsUrl,
          supportUrl = _this$props.supportUrl,
          dashboardUrl = _this$props.dashboardUrl;
      var links = [];

      if (docsUrl) {
        links.push({
          key: 'docs',
          href: docsUrl,
          text: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Docs', 'woocommerce')
        });
      }

      if (supportUrl) {
        links.push({
          key: 'support',
          href: supportUrl,
          text: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Get support', 'woocommerce')
        });
      }

      if (settingsUrl) {
        links.push({
          key: 'settings',
          href: settingsUrl,
          text: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Settings', 'woocommerce')
        });
      }

      if (dashboardUrl) {
        links.push({
          key: 'dashboard',
          href: dashboardUrl,
          text: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Dashboard', 'woocommerce')
        });
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("ul", {
        className: "woocommerce-marketing-installed-extensions-card__item-links"
      }, links.map(function (link) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("li", {
          key: link.key
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["Link"], {
          href: link.href,
          type: "external",
          onClick: _this2.onLinkClick.bind(_this2, link)
        }, link.text));
      }));
    }
  }, {
    key: "onLinkClick",
    value: function onLinkClick(link) {
      var name = this.props.name;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_11__["recordEvent"])('marketing_installed_options', {
        name: name,
        link: link.key
      });
    }
  }, {
    key: "onActivateClick",
    value: function onActivateClick() {
      var _this$props2 = this.props,
          activatePlugin = _this$props2.activatePlugin,
          name = _this$props2.name;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_11__["recordEvent"])('marketing_installed_activate', {
        name: name
      });
      activatePlugin();
    }
  }, {
    key: "onFinishSetupClick",
    value: function onFinishSetupClick() {
      var name = this.props.name;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_11__["recordEvent"])('marketing_installed_finish_setup', {
        name: name
      });
    }
  }, {
    key: "getActivateButton",
    value: function getActivateButton() {
      var isLoading = this.props.isLoading;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_components__WEBPACK_IMPORTED_MODULE_10__["Button"], {
        isDefault: true,
        onClick: this.onActivateClick,
        disabled: isLoading
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Activate', 'woocommerce'));
    }
  }, {
    key: "getFinishSetupButton",
    value: function getFinishSetupButton() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_components__WEBPACK_IMPORTED_MODULE_10__["Button"], {
        isDefault: true,
        href: this.props.settingsUrl,
        onClick: this.onFinishSetupClick
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Finish setup', 'woocommerce'));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
          name = _this$props3.name,
          description = _this$props3.description,
          status = _this$props3.status,
          icon = _this$props3.icon;
      var actions = null;

      switch (status) {
        case 'installed':
          actions = this.getActivateButton();
          break;

        case 'activated':
          actions = this.getFinishSetupButton();
          break;

        case 'configured':
          actions = this.getLinks();
          break;
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-marketing-installed-extensions-card__item"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_components__WEBPACK_IMPORTED_MODULE_10__["ProductIcon"], {
        src: icon
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-marketing-installed-extensions-card__item-text-and-actions"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-marketing-installed-extensions-card__item-text"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h4", null, name), status === 'configured' || Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", {
        className: "woocommerce-marketing-installed-extensions-card__item-description"
      }, description)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-marketing-installed-extensions-card__item-actions"
      }, actions)));
    }
  }]);

  return InstalledExtensionRow;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

InstalledExtensionRow.defaultProps = {
  isLoading: false
};
InstalledExtensionRow.propTypes = {
  name: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,
  slug: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,
  description: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,
  status: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,
  settingsUrl: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string,
  docsUrl: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string,
  supportUrl: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string,
  dashboardUrl: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string,
  activatePlugin: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.func.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (InstalledExtensionRow);

/***/ }),

/***/ "./client/marketing/overview/installed-extensions/style.scss":
/*!*******************************************************************!*\
  !*** ./client/marketing/overview/installed-extensions/style.scss ***!
  \*******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/marketing/overview/knowledge-base/index.js":
/*!***********************************************************!*\
  !*** ./client/marketing/overview/knowledge-base/index.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/overview/knowledge-base/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../../components */ "./client/marketing/components/index.js");
/* harmony import */ var _data_constants__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../../data/constants */ "./client/marketing/data/constants.js");








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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






var KnowledgeBase = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(KnowledgeBase, _Component);

  var _super = _createSuper(KnowledgeBase);

  function KnowledgeBase(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, KnowledgeBase);

    _this = _super.call(this, props);
    _this.state = {
      page: 1,
      animate: null,
      isLoading: true
    };
    _this.onPaginationPageChange = _this.onPaginationPageChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(KnowledgeBase, [{
    key: "onPaginationPageChange",
    value: function onPaginationPageChange(newPage) {
      var page = this.state.page;
      var animate;

      if (newPage > page) {
        animate = 'left';
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('marketing_knowledge_carousel', {
          direction: 'forward',
          page: newPage
        });
      } else {
        animate = 'right';
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('marketing_knowledge_carousel', {
          direction: 'back',
          page: newPage
        });
      }

      this.setState({
        page: newPage,
        animate: animate
      });
    }
  }, {
    key: "onPostClick",
    value: function onPostClick(post) {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('marketing_knowledge_article', {
        title: post.title
      });
    }
    /**
     * Get the 2 posts we need for the current page
     */

  }, {
    key: "getCurrentSlide",
    value: function getCurrentSlide() {
      var _this2 = this;

      var posts = this.props.posts;
      var page = this.state.page;
      var currentPosts = posts.slice((page - 1) * 2, (page - 1) * 2 + 2);
      var pageClass = classnames__WEBPACK_IMPORTED_MODULE_10___default()('woocommerce-marketing-knowledgebase-card__page', {
        'page-with-single-post': currentPosts.length === 1
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: pageClass
      }, currentPosts.map(function (post, index) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("a", {
          className: "woocommerce-marketing-knowledgebase-card__post",
          href: post.link,
          key: index,
          onClick: _this2.onPostClick(_this2, post),
          target: "_blank",
          rel: "noopener noreferrer"
        }, post.image && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: "woocommerce-marketing-knowledgebase-card__post-img"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("img", {
          src: post.image,
          alt: ""
        })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: "woocommerce-marketing-knowledgebase-card__post-text"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h3", null, post.title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", {
          className: "woocommerce-marketing-knowledgebase-card__post-meta"
        }, "By ", post.author_name, post.author_avatar && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("img", {
          src: post.author_avatar.replace('s=96', 's=32'),
          className: "woocommerce-gravatar",
          alt: "",
          width: "16",
          height: "16"
        }))));
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var _this$props = this.props,
          posts = _this$props.posts,
          isLoading = _this$props.isLoading;
      var _this$state = this.state,
          page = _this$state.page,
          animate = _this$state.animate;

      var renderEmpty = function renderEmpty() {
        var title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('There was an error loading knowledge base posts. Please check again later.', 'woocommerce');

        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["EmptyContent"], {
          title: title,
          illustrationWidth: 250,
          actionLabel: ""
        });
      };

      var renderPosts = function renderPosts() {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: "woocommerce-marketing-knowledgebase-card__posts"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_components__WEBPACK_IMPORTED_MODULE_15__["Slider"], {
          animationKey: page,
          animate: animate
        }, _this3.getCurrentSlide()), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Pagination"], {
          page: page,
          perPage: 2,
          total: posts.length,
          onPageChange: _this3.onPaginationPageChange,
          showPagePicker: false,
          showPerPagePicker: false,
          showPageArrowsLabel: false
        }));
      };

      var renderCardBody = function renderCardBody() {
        if (isLoading) {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["Spinner"], null);
        }

        return posts.length === 0 ? renderEmpty() : renderPosts();
      };

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Card"], {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('WooCommerce knowledge base', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Learn the ins and outs of successful marketing from the experts at WooCommerce.', 'woocommerce'),
        className: "woocommerce-marketing-knowledgebase-card"
      }, renderCardBody());
    }
  }]);

  return KnowledgeBase;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withSelect"])(function (select) {
  var _select = select(_data_constants__WEBPACK_IMPORTED_MODULE_16__["STORE_KEY"]),
      getBlogPosts = _select.getBlogPosts,
      isResolving = _select.isResolving;

  return {
    posts: getBlogPosts(),
    isLoading: isResolving('getBlogPosts')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(KnowledgeBase));

/***/ }),

/***/ "./client/marketing/overview/knowledge-base/style.scss":
/*!*************************************************************!*\
  !*** ./client/marketing/overview/knowledge-base/style.scss ***!
  \*************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/marketing/overview/recommended-extensions/index.js":
/*!*******************************************************************!*\
  !*** ./client/marketing/overview/recommended-extensions/index.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/overview/recommended-extensions/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _item__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./item */ "./client/marketing/overview/recommended-extensions/item.js");
/* harmony import */ var _data_constants__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../../data/constants */ "./client/marketing/data/constants.js");








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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





var RecommendedExtensions = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(RecommendedExtensions, _Component);

  var _super = _createSuper(RecommendedExtensions);

  function RecommendedExtensions() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, RecommendedExtensions);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(RecommendedExtensions, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          isLoading = _this$props.isLoading,
          extensions = _this$props.extensions;

      if (extensions.length === 0 && !isLoading) {
        return null;
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Card"], {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Recommended extensions', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Great marketing requires the right tools. Take your marketing to the next level with our recommended marketing extensions.', 'woocommerce'),
        className: "woocommerce-marketing-recommended-extensions-card"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, isLoading ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["Spinner"], null) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: classnames__WEBPACK_IMPORTED_MODULE_10___default()('woocommerce-marketing-recommended-extensions-card__items', "woocommerce-marketing-recommended-extensions-card__items--count-".concat(extensions.length))
      }, extensions.map(function (extension) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_item__WEBPACK_IMPORTED_MODULE_15__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          key: extension.product
        }, extension));
      }))));
    }
  }]);

  return RecommendedExtensions;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

RecommendedExtensions.propTypes = {
  /**
   * Array of recommended extensions.
   */
  extensions: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.object).isRequired,

  /**
   * Whether the card is loading.
   */
  isLoading: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.bool.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_8__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withSelect"])(function (select) {
  var _select = select(_data_constants__WEBPACK_IMPORTED_MODULE_16__["STORE_KEY"]),
      getRecommendedPlugins = _select.getRecommendedPlugins,
      isResolving = _select.isResolving;

  return {
    extensions: getRecommendedPlugins(),
    isLoading: isResolving('getRecommendedPlugins')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(RecommendedExtensions));

/***/ }),

/***/ "./client/marketing/overview/recommended-extensions/item.js":
/*!******************************************************************!*\
  !*** ./client/marketing/overview/recommended-extensions/item.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/overview/recommended-extensions/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _components___WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../components/ */ "./client/marketing/components/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




/**
 * WooCommerce dependencies
 */




var RecommendedExtensionsItem = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(RecommendedExtensionsItem, _Component);

  var _super = _createSuper(RecommendedExtensionsItem);

  function RecommendedExtensionsItem() {
    var _temp, _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, RecommendedExtensionsItem);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.onProductClick = function () {
      var title = _this.props.title;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_10__["recordEvent"])('marketing_recommended_extension', {
        name: title
      });
    }, _temp));
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(RecommendedExtensionsItem, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          title = _this$props.title,
          description = _this$props.description,
          icon = _this$props.icon,
          url = _this$props.url;
      var classNameBase = 'woocommerce-marketing-recommended-extensions-item';

      var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__["getSetting"])('marketing', {}),
          connectNonce = _getSetting.connectNonce;

      var connectURL = Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_7__["addQueryArgs"])(url, {
        'wccom-site': Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__["getSetting"])('siteUrl'),
        'wccom-back': '/wp-admin/' + Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__["getNewPath"])({}),
        'wccom-woo-version': Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__["getSetting"])('wcVersion'),
        'wccom-connect-nonce': connectNonce
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("a", {
        href: connectURL,
        className: classNameBase,
        onClick: this.onProductClick
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_components___WEBPACK_IMPORTED_MODULE_9__["ProductIcon"], {
        src: icon
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "".concat(classNameBase, "__text")
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("h4", null, title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, description)));
    }
  }]);

  return RecommendedExtensionsItem;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

RecommendedExtensionsItem.propTypes = {
  title: prop_types__WEBPACK_IMPORTED_MODULE_6___default.a.string.isRequired,
  description: prop_types__WEBPACK_IMPORTED_MODULE_6___default.a.string.isRequired,
  icon: prop_types__WEBPACK_IMPORTED_MODULE_6___default.a.string.isRequired,
  url: prop_types__WEBPACK_IMPORTED_MODULE_6___default.a.string.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (RecommendedExtensionsItem);

/***/ }),

/***/ "./client/marketing/overview/recommended-extensions/style.scss":
/*!*********************************************************************!*\
  !*** ./client/marketing/overview/recommended-extensions/style.scss ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/marketing/overview/style.scss":
/*!**********************************************!*\
  !*** ./client/marketing/overview/style.scss ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/marketing/overview/welcome-card/images/welcome.svg":
/*!*******************************************************************!*\
  !*** ./client/marketing/overview/welcome-card/images/welcome.svg ***!
  \*******************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMzEiIGhlaWdodD0iMTY1IiBmaWxsPSJub25lIj4KICA8ZGVmcy8+CiAgPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwKSI+CiAgICA8cGF0aCBmaWxsPSIjRjJGMkYyIiBkPSJNMjMxIDk5LjI2M2MwIDI4LjgyOS0xNy4yMSAzOC44OTUtMzguNDM4IDM4Ljg5NS0yMS4yMjkgMC0zOC40MzktMTAuMDY2LTM4LjQzOS0zOC44OTUgMC0yOC44MyAzOC40MzktNjUuNTA1IDM4LjQzOS02NS41MDVTMjMxIDcwLjQzMyAyMzEgOTkuMjYzeiIvPgogICAgPHBhdGggZmlsbD0iIzNGM0Q1NiIgZD0iTTE5MS4xNjEgMTMzLjc0OGwuMzk0LTI0LjEyNyAxNi4zODMtMjkuODUtMTYuMzIxIDI2LjA2NS4xNzctMTAuODQ5IDExLjI5MS0yMS41OTYtMTEuMjQ1IDE4LjcyNS4zMTktMTkuNTEyIDEyLjA5MS0xNy4xOTMtMTIuMDQxIDE0LjEyNS4xOTgtMzUuNzc4LTEuMjQ5IDQ3LjM2My4xMDItMS45NTMtMTIuMjkzLTE4Ljc0IDEyLjA5NiAyMi40OS0xLjE0NSAyMS43OTItLjAzNC0uNTc4LTE0LjE3Mi0xOS43MiAxNC4xMjkgMjEuNzYzLS4xNDMgMi43MjUtLjAyNi4wNDEuMDEyLjIyNC0yLjkwNiA1NS4yODdoMy44ODJsLjQ2Ni0yOC41NTcgMTQuMDk0LTIxLjcxLTE0LjA1OSAxOS41NjN6Ii8+CiAgICA8cGF0aCBmaWxsPSIjRjJGMkYyIiBkPSJNMjAuNzkgMTQ3LjAyNWMwIDcuNzk2LTQuNjU0IDEwLjUxOC0xMC4zOTUgMTAuNTE4UzAgMTU0LjgyMSAwIDE0Ny4wMjVjMC03Ljc5NiAxMC4zOTUtMTcuNzE0IDEwLjM5NS0xNy43MTRzMTAuMzk0IDkuOTE4IDEwLjM5NCAxNy43MTR6Ii8+CiAgICA8cGF0aCBmaWxsPSIjM0YzRDU2IiBkPSJNMTAuMDE2IDE1Ni4zNTFsLjEwNi02LjUyNSA0LjQzLTguMDcyLTQuNDEzIDcuMDQ4LjA0OC0yLjkzMyAzLjA1My01Ljg0LTMuMDQgNS4wNjMuMDg1LTUuMjc2IDMuMjctNC42NDktMy4yNTYgMy44MTkuMDU0LTkuNjc1LS4zMzggMTIuODA4LjAyNy0uNTI4LTMuMzI0LTUuMDY4IDMuMjcxIDYuMDgyLS4zMSA1Ljg5My0uMDA5LS4xNTYtMy44MzItNS4zMzMgMy44MiA1Ljg4NS0uMDM4LjczNy0uMDA3LjAxMS4wMDMuMDYxLS43ODYgMTQuOTUxaDEuMDVsLjEyNi03LjcyMyAzLjgxMi01Ljg3MS0zLjgwMiA1LjI5MXoiLz4KICAgIDxwYXRoIHN0cm9rZT0iIzNGM0Q1NiIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2Utd2lkdGg9IjIiIGQ9Ik0xIDE2NWgyMzAiLz4KICAgIDxwYXRoIGZpbGw9IiMwNzdDQjIiIGQ9Ik0yMTAuODQ1IDE1Ny42NjZjMCA1LjI3My0zLjE0OCA3LjExNS03LjAzMSA3LjExNWExMi4xNTMgMTIuMTUzIDAgMDEtLjgwNC0uMDI4Yy0zLjUwNC0uMjQ3LTYuMjI3LTIuMTgzLTYuMjI3LTcuMDg3IDAtNS4wNzUgNi41MTMtMTEuNDc5IDcuMDAyLTExLjk1M2wuMDAxLS4wMDEuMDI4LS4wMjdzNy4wMzEgNi43MDggNy4wMzEgMTEuOTgxeiIvPgogICAgPHBhdGggZmlsbD0iIzNGM0Q1NiIgZD0iTTIwMy41NTcgMTYzLjk3NGwyLjU3Mi0zLjU3OS0yLjU3OCAzLjk3Mi0uMDA3LjQxYy0uMTgtLjAwMy0uMzU4LS4wMTItLjUzNC0uMDI0bC4yNzctNS4yNzYtLjAwMi0uMDQxLjAwNS0uMDA3LjAyNi0uNDk4LTIuNTg1LTMuOTgyIDIuNTkzIDMuNjA4LjAwNi4xMDYuMjA5LTMuOTg3LTIuMjEyLTQuMTEzIDIuMjM5IDMuNDE0LjIxOC04LjI2NC4wMDEtLjAyOHYuMDI3bC0uMDM2IDYuNTE3IDIuMjAyLTIuNTg0LTIuMjExIDMuMTQ1LS4wNTggMy41NjkgMi4wNTYtMy40MjUtMi4wNjUgMy45NS0uMDMzIDEuOTg0IDIuOTg2LTQuNzY3LTIuOTk3IDUuNDYtLjA3MiA0LjQxM3pNMTM1Ljg4MSAzNy4zNGgtMS4zNWEuNTQuNTQgMCAwMC0uNTQuNTM4djE4Ljg3OGEuNTQuNTQgMCAwMC41NC41MzhoMS4zNWMuMjk5IDAgLjU0LS4yNDEuNTQtLjUzOFYzNy44NzhhLjUzOC41MzggMCAwMC0uNTQtLjUzOHpNNTQuMjEyIDIxLjcwOGgtLjY1NmEuMzU0LjM1NCAwIDAwLS4zNTQuMzUzdjUuODYzYzAgLjE5NS4xNTkuMzUzLjM1NC4zNTNoLjY1NmEuMzU0LjM1NCAwIDAwLjM1NC0uMzUzdi01Ljg2M2EuMzU0LjM1NCAwIDAwLS4zNTQtLjM1M3pNNTQuMjkxIDMzLjczNWgtLjczOGMtLjIyIDAtLjQuMTc4LS40LjM5OFY0NC43N2MwIC4yMi4xOC4zOTguNC4zOThoLjczOGMuMjIgMCAuNC0uMTc4LjQtLjM5OFYzNC4xMzJhLjM5OS4zOTkgMCAwMC0uNC0uMzk3ek01NC4yNiA0OS4xN2gtLjcwMmEuMzguMzggMCAwMC0uMzguMzc4djEwLjc3NWMwIC4yMS4xNy4zNzkuMzguMzc5aC43MDNjLjIxIDAgLjM4LS4xNy4zOC0uMzc5VjQ5LjU0OGEuMzguMzggMCAwMC0uMzgtLjM3OXoiLz4KICAgIDxwYXRoIGZpbGw9IiMzRjNENTYiIGQ9Ik0xMjYuODkyIDBINjIuMzg3Yy00LjY0NSAwLTguNDExIDMuNzUtOC40MTEgOC4zNzdWMTU2LjIzYzAgNC42MjcgMy43NjYgOC4zNzcgOC40MTEgOC4zNzdoNjQuNTA1YzQuNjQ2IDAgOC40MTItMy43NSA4LjQxMi04LjM3N1Y4LjM3N2MwLTQuNjI2LTMuNzY2LTguMzc3LTguNDEyLTguMzc3eiIvPgogICAgPHBhdGggZmlsbD0iI0U2RThFQyIgZD0iTTk3LjU1MiA0LjkxNkg4Ny4zOTNjLS4zMyAwLS42LjI2Ny0uNi41OTZ2MS4xMDRjMCAuMzMuMjcuNTk2LjYuNTk2aDEwLjE1OWMuMzMgMCAuNTk5LS4yNjcuNTk5LS41OTZWNS41MTJhLjU5OC41OTggMCAwMC0uNTk5LS41OTZ6TTEwMS4xNzYgNy4zNjdjLjcyMyAwIDEuMzA4LS41ODQgMS4zMDgtMS4zMDMgMC0uNzItLjU4NS0xLjMwMy0xLjMwOC0xLjMwMy0uNzIyIDAtMS4zMDguNTg0LTEuMzA4IDEuMzAzIDAgLjcyLjU4NiAxLjMwMyAxLjMwOCAxLjMwM3oiLz4KICAgIDxwYXRoIGZpbGw9IiNmZmYiIGQ9Ik0xMzAuMjgxIDEwLjcydjE0My4xNjhhNi40MTkgNi40MTkgMCAwMS0xLjg5MiA0LjU0OCA2LjQ4IDYuNDggMCAwMS00LjU2NyAxLjg4M0g2NS40NTdhNi40NzcgNi40NzcgMCAwMS00LjU2Ni0xLjg4MyA2LjQyIDYuNDIgMCAwMS0xLjg5My00LjU0OFYxMC43MjFhNi40MSA2LjQxIDAgMDExLjg5Mi00LjU1IDYuNDYyIDYuNDYyIDAgMDE0LjU2Ny0xLjg4M2g4LjczNHYxLjExN2MwIDEuNDA1LjU2MSAyLjc1MiAxLjU1OCAzLjc0NWE1LjMzMiA1LjMzMiAwIDAwMy43NjEgMS41NTJoMjkuNjgyYzEuNDEgMCAyLjc2My0uNTU4IDMuNzYxLTEuNTUxYTUuMjg4IDUuMjg4IDAgMDAxLjU1OC0zLjc0NlY0LjI4OGg5LjMxMWE2LjQ4NCA2LjQ4NCAwIDAxNC41NjcgMS44ODQgNi40MjEgNi40MjEgMCAwMTEuODkyIDQuNTQ5eiIvPgogICAgPHBhdGggZmlsbD0iI0YyRjJGMiIgZD0iTTEzMC4zMjEgMzcuNDc3SDU4LjkxNXY4MS42NzNoNzEuNDA2VjM3LjQ3N3oiLz4KICAgIDxwYXRoIGZpbGw9IiNGRjY1ODQiIGQ9Ik02NC4xIDEzMC40NTJsLS4zNzctLjMzOGMtMS4zMzUtMS4yMjEtMi4yMTYtMi4wMTMtMi4yMTYtM2ExLjM5MyAxLjM5MyAwIDAxLjQxLTEuMDE0IDEuNDEzIDEuNDEzIDAgMDExLjAxNi0uNDE0IDEuNTM4IDEuNTM4IDAgMDExLjE2Ni41NDUgMS41NCAxLjU0IDAgMDExLjE2Ny0uNTQ1IDEuNDAxIDEuNDAxIDAgMDExLjAxNi40MTQgMS4zOTQgMS4zOTQgMCAwMS40MSAxLjAxNGMwIC45ODctLjg4MiAxLjc3OS0yLjIxNyAzbC0uMzc2LjMzOHoiLz4KICAgIDxwYXRoIGZpbGw9IiNGMkYyRjIiIGQ9Ik03Ny43MjggMTI5LjIwM2wuNzI3LjIwN2EyLjQzOCAyLjQzOCAwIDAxMS42NjUtMS41NTggMi40NjMgMi40NjMgMCAwMTIuMjMyLjQ4NmwtMS4xMSAxLjA3MmgyLjc3di0yLjY4MmwtMS4xMTYgMS4wNjVhMy4yMjYgMy4yMjYgMCAwMC0yLjk2NC0uNjc5IDMuMjAyIDMuMjAyIDAgMDAtMi4yMDQgMi4wODl6Ii8+CiAgICA8cGF0aCBmaWxsPSIjMDc3Q0IyIiBkPSJNNjMuNjI4IDMxLjM3NWMxLjgyMyAwIDMuMy0xLjQ3IDMuMy0zLjI4NWEzLjI5MiAzLjI5MiAwIDAwLTMuMy0zLjI4NiAzLjI5MiAzLjI5MiAwIDAwLTMuMjk5IDMuMjg2IDMuMjkyIDMuMjkyIDAgMDAzLjMgMy4yODV6TTEwMC4xNTYgMjYuMjEySDY5LjUxOXYzLjc1NWgzMC42Mzd2LTMuNzU1eiIvPgogICAgPHBhdGggZmlsbD0iI0YyRjJGMiIgZD0iTTkyLjE0NCAxMzYuMDQ4SDYxLjUwOHYzLjc1NWgzMC42MzZ2LTMuNzU1ek0xMjYuMzE1IDE0NC4yMzRINjEuNTA4djMuNzU1aDY0LjgwN3YtMy43NTV6TTc0LjcwNSAxMzAuMjE0bC0uODEyLTEuNDE1YTIuMDggMi4wOCAwIDAwLS44NjYtMi41OTcgMi4xIDIuMSAwIDAwLTIuNzA0LjQ4OSAyLjA4NSAyLjA4NSAwIDAwMS40MjMgMy4zODIgMi4xIDIuMSAwIDAwMS40Mi0uMzdsMS41MzkuNTExeiIvPgogICAgPHBhdGggZmlsbD0iIzJGMkU0MSIgZD0iTTg4LjU3NiA1Ni42MzJhOS40NDkgOS40NDkgMCAwMC0zLjM5IDEuNDIgOS40MDggOS40MDggMCAwMC0yLjU4NyAyLjYwNSA5LjM2NSA5LjM2NSAwIDAwLTEuMzY4IDcuMDU0bDEuOTQ1IDkuNDY4IDIuMTc4LS40NDQuMTU0LTEuMDAzLjQ1Ni44NzkgMTMuMjQzLTIuNy4xNTQtMS4wMDMuNDU2Ljg4IDEuODI5LS4zNzMtMS45NDUtOS40NjhhOS4zNjYgOS4zNjYgMCAwMC0xLjQyNy0zLjM3NiA5LjQwOCA5LjQwOCAwIDAwLTIuNjE1LTIuNTc2IDkuNDUgOS40NSAwIDAwLTcuMDgzLTEuMzYzeiIvPgogICAgPHBhdGggZmlsbD0iIzlGNjE2QSIgZD0iTTEwNC45ODcgODUuNjc4bDExLjMxMiA2LjgwNi0xLjQxNCAzLjI4Ni0xMC42MDUtNC45MjguNzA3LTUuMTYzeiIvPgogICAgPHBhdGggZmlsbD0iIzA3N0NCMiIgZD0iTTkwLjYxMiA5My40MjNzLTIyLjg2IDAtMjYuNjMgNS44NjhjLTMuNzcgNS44NjctLjcwNyAxNC4wODEgMTEuMDc2IDE1Ljk1OSAxMS43ODMgMS44NzcgNDcuNjA0IDIuODE2IDUwLjQzMi00LjIyNSAyLjgyOC03LjA0LjcwNy0xMS4wMy43MDctMTEuMDNzLTEyLjI1NS04LjQ1LTM1LjU4NS02LjU3MnoiLz4KICAgIDxwYXRoIGZpbGw9IiMwNzdDQjIiIGQ9Ik0xMTAuODc4IDk5LjA1NmwxMi40OTEgNC42OTRzNS4xODQtMy43NTUgMi41OTItNy45OGMtMi41OTItNC4yMjQtMTQuMzc2LTI4LjYzMi0xNC4zNzYtMjguNjMycy0zLjI5OS00LjY5NC45NDMtNy4yNzZjNC4yNDItMi41ODEgNS44OTIgMS42NDMgNS44OTIgMS42NDNzLS4yMzYuOTM5IDEuNDE0LjIzNWMxLjY0OS0uNzA0IDQuNzEzIDEuNjQzIDMuMDYzIDUuMzk4LTEuNjQ5IDMuNzU1LTIuODI4IDQuNjk0LjcwNyA0LjQ1OSAxLjI3Ny0uMDg1IDIuNC0uOTk2IDMuMzEzLTIuMTI1IDEuNzUyLTIuMTY1Ljk3LTMuOTEuODM3LTYuNjg2LS4yMjctNC43NTctLjU5OC03Ljg2LTEuNzkzLTkuNzMtMS42NS0yLjU4MS00LjAwNi00LjY5NC0xMC4xMzQtMy41Mi02LjEyNyAxLjE3My0xNS4wODIgNy4wNC0xMi45NjEgMTUuNDkgMi4xMjEgOC40NDkgNi44MzQgMTcuODM2IDYuODM0IDE3LjgzNnMzLjI5OSAxMy4xNDMgMS4xNzggMTYuMTk0eiIvPgogICAgPHBhdGggZmlsbD0iIzAwMCIgZD0iTTExMC44NzggOTkuMDU2Yy0yLjAzNi41NTktNC41OTMuODMtNy4zNDUuOTE1LTQuODAzLjE0OC0xMC4yMS0uMjc0LTE0LjUyNi0uNzUzLTQuNjY0LS41MTktOC4wNTgtMS4xLTguMDU4LTEuMSAxLjU3Ny0xLjQ2NSA0LTIuMDQ1IDYuODQ2LTIuMjU2IDMuNTM1LS4yNjMgNy43MjUuMDM3IDExLjc3Mi0uMDkyYTIwLjQxIDIwLjQxIDAgMDE0LjQ1MS4zMzRjNC40NjQuODQ3IDYuODYgMi45NTIgNi44NiAyLjk1MnoiIG9wYWNpdHk9Ii4xIi8+CiAgICA8cGF0aCBmaWxsPSIjOUY2MTZBIiBkPSJNOTAuNDk0IDczLjgyNmMzLjY0NCAwIDYuNTk5LTIuOTQyIDYuNTk5LTYuNTcgMC0zLjYzLTIuOTU1LTYuNTcyLTYuNTk5LTYuNTcycy02LjU5OSAyLjk0Mi02LjU5OSA2LjU3MWMwIDMuNjMgMi45NTUgNi41NzIgNi41OTkgNi41NzJ6Ii8+CiAgICA8cGF0aCBmaWxsPSIjOUY2MTZBIiBkPSJNODkuNjcgNzIuNTM2czEuMTc4IDQuNDU5IDAgNC42OTRjLTEuMTc5LjIzNC00LjAwNyAxLjQwOC00LjAwNyAxLjQwOGwzLjUzNSAxLjQwOCA2LjU5OSA2LjEwMiA1LjY1NSAyLjgxNiAyLjgyOC0uOTM5di01LjE2M2wtNC4yNDItNi41NzFzLTIuODI4LjkzOS0zLjUzNC0yLjExMmMtLjcwNy0zLjA1MS0yLjEyMS00LjIyNS0yLjEyMS00LjIyNWwtNC43MTQgMi41ODJ6Ii8+CiAgICA8cGF0aCBmaWxsPSIjOUY2MTZBIiBkPSJNODguMDIgNzguODcySDg1LjE5cy00Ljk0OSAxLjY0My03Ljc3NyA1LjM5OGMtMi44MjggMy43NTUtNy4zMDUgNy41MS03LjMwNSA3LjUxcy0zLjA2NCA0LjIyNS0xLjQxNCAxMC4wOTJjMS42NSA1Ljg2OCA0LjAwNiAxMC4wOTIgNi4xMjcgOC45MTkgMi4xMjEtMS4xNzQtMy4wNjQtOS42MjMtMy4wNjQtOS42MjNsMS40MTQtNS4xNjNzNy4zMDYtOC40NDkgMTAuMzctOC4yMTRjMy4wNjMuMjM0IDMuMjk5IDIuODE2IDMuMjk5IDIuODE2bDYuNTk4IDEuODc4IDIuMTIxLTQuNDYtMS4xNzgtNC42OTMtNi4zNjMtNC40NnoiLz4KICAgIDxwYXRoIGZpbGw9IiMzRjNENTYiIGQ9Ik0xMDQuOTg3IDkzLjY1OGE4LjY2OCA4LjY2OCAwIDAwLS45NjggMi40NDYgMTkuMDE4IDE5LjAxOCAwIDAwLS40ODYgMy44NjdjLTQuODAyLjE0OC0xMC4yMDktLjI3NC0xNC41MjYtLjc1My0uMzgyLTEuMDE0LS44MDEtMi4xOC0xLjIxMS0zLjM1Ni0xLjEwNi0zLjE3OC0yLjEzMy02LjQyMS0yLjEzMy02Ljg5OCAwLS45MzkgNC45NDkuNzA0IDYuMTI3LjQ3IDEuMTc5LS4yMzUgMS42NS0zLjk5IDAtNS44NjgtMS42NS0xLjg3Ny02LjQ4LTUuMDQ2LTYuNDgtNS4wNDZsMS42NS0uNDY5czMuNjUyIDIuNjk5IDExLjE5MyA3LjE1OGM3LjU0MSA0LjQ2IDQuNDc4LTIuNTgxIDQuNDc4LTIuNTgxbC00LjM2LTYuNjloMS44ODVzMy42NTMgNi4yMiA1LjUzOCA5LjAzN2MxLjg4NiAyLjgxNiAxLjE3OSA1LjYzMi0uNzA3IDguNjgzeiIvPgogICAgPHBhdGggZmlsbD0iIzJGMkU0MSIgZD0iTTk1LjE2MyA1OC4zOWwtMTEuOTUxIDIuNDM1IDEuNSA3LjMwNCAxMS45NTItMi40MzYtMS41LTcuMzAzeiIvPgogICAgPHBhdGggZmlsbD0iIzNGM0Q1NiIgZD0iTTEyMC44OTQgNTkuMjc2YTMuNTI3IDMuNTI3IDAgMDAzLjUzNS0zLjUyIDMuNTI3IDMuNTI3IDAgMDAtMy41MzUtMy41MjEgMy41MjggMy41MjggMCAwMC0zLjUzNSAzLjUyIDMuNTI4IDMuNTI4IDAgMDAzLjUzNSAzLjUyeiIvPgogICAgPHBhdGggZmlsbD0iI2ZmZiIgZD0iTTEyMC44OTQgNTcuNjMzYzEuMDQxIDAgMS44ODUtLjg0IDEuODg1LTEuODc4YTEuODgxIDEuODgxIDAgMDAtMS44ODUtMS44NzdjLTEuMDQxIDAtMS44ODUuODQtMS44ODUgMS44NzdzLjg0NCAxLjg3OCAxLjg4NSAxLjg3OHoiLz4KICAgIDxwYXRoIGZpbGw9IiMwNzdDQjIiIGQ9Ik0xNy4xNDIgOTYuMzkxbC0uMjQxIDEuODUyYy0uMDgyLjYyNy0uMTYzIDEuMjU0LS4yMDcgMS44ODUtLjE0NiAyLjA5OC4xMjYgNC4yMDUuMDU0IDYuMzA3LS4wNTUgMS42MTgtLjMxNCAzLjIzNS0uMTg5IDQuODQ5LjExIDEuNDI4LjUxOSAyLjgxNS45MjUgNC4xODlsLjU4IDEuOTYzYS4zNzcuMzc3IDAgMDAuMTc1LjI5MmMuMDUuMDMyLjEwNi4wNTIuMTY1LjA1OGEuMzguMzggMCAwMC4xNzQtLjAybDIuNTY2LS4yMzJjLS4xNjQtLjc1LS4zNDctMS41MzMtLjQzNS0yLjI5Ni0uMDctLjYwNC0uMTEyLTEuMjEtLjE1Ni0xLjgxNmE1OTMuNzMgNTkzLjczIDAgMDAtLjQ0Ni01LjY2NGwtLjI3MS0zLjMxNmMtLjExOC0xLjQzOC0uMjM3LTIuODg1LS42MDEtNC4yODJhOC41MTggOC41MTggMCAwMC0yLjA5My0zLjc2OXoiLz4KICAgIDxwYXRoIGZpbGw9IiMwMDAiIGQ9Ik0xNy4xNDIgOTYuMzkxbC0uMjQxIDEuODUyYy0uMDgyLjYyNy0uMTYzIDEuMjU0LS4yMDcgMS44ODUtLjE0NiAyLjA5OC4xMjYgNC4yMDUuMDU0IDYuMzA3LS4wNTUgMS42MTgtLjMxNCAzLjIzNS0uMTg5IDQuODQ5LjExIDEuNDI4LjUxOSAyLjgxNS45MjUgNC4xODlsLjU4IDEuOTYzYS4zNzcuMzc3IDAgMDAuMTc1LjI5MmMuMDUuMDMyLjEwNi4wNTIuMTY1LjA1OGEuMzguMzggMCAwMC4xNzQtLjAybDIuNTY2LS4yMzJjLS4xNjQtLjc1LS4zNDctMS41MzMtLjQzNS0yLjI5Ni0uMDctLjYwNC0uMTEyLTEuMjEtLjE1Ni0xLjgxNmE1OTMuNzMgNTkzLjczIDAgMDAtLjQ0Ni01LjY2NGwtLjI3MS0zLjMxNmMtLjExOC0xLjQzOC0uMjM3LTIuODg1LS42MDEtNC4yODJhOC41MTggOC41MTggMCAwMC0yLjA5My0zLjc2OXoiIG9wYWNpdHk9Ii4xIi8+CiAgICA8cGF0aCBmaWxsPSIjM0YzRDU2IiBkPSJNMjEuNzE0IDE1OS44OTZhNy40OTMgNy40OTMgMCAwMC0uMzk0LjkxMmMtLjI5NC44NjEtLjM0IDEuNzg1LS4zODEgMi42OTQtLjAyLjE3NS0uMDA0LjM1My4wNDguNTIyLjEyNi4zMjcuNDkuNDkyLjgyOS41ODMgMS40MjEuMzgxIDIuOTIuMDIzIDQuMzg3LS4xMDggMS40MzktLjEyOCAyLjkwNS0uMDM1IDQuMzE0LS4zNTIuMjA5LS4wMzkuNDEtLjEwOC41OTgtLjIwNi4zNTctLjIzMi42MjQtLjU3OC43NTUtLjk4Mi4wNDMtLjEwNC4wNzItLjIxMi4wODYtLjMyMy4wNDYtLjQ3OC0uMzcyLS44OTQtLjgyOC0xLjA1MS0uNDU3LS4xNTctLjk1Mi0uMTMyLTEuNDMzLS4xODFhNC41MjQgNC41MjQgMCAwMS0yLjYzNi0xLjIwMSA0LjQ5MyA0LjQ5MyAwIDAxLTEuMzU4LTIuNTUxYy0uMDE4LS4xMDUtMi4zNzQtLjA0My0yLjYyMi4wOTItLjI5LjE1OS0uNDY2LjU1NS0uNjMuODIzLS4yNjQuNDMyLS41MS44NzUtLjczNSAxLjMyOXoiLz4KICAgIDxwYXRoIGZpbGw9IiMwMDAiIGQ9Ik0yMS43MTQgMTU5Ljg5NmE3LjQ5MyA3LjQ5MyAwIDAwLS4zOTQuOTEyYy0uMjk0Ljg2MS0uMzQgMS43ODUtLjM4MSAyLjY5NC0uMDIuMTc1LS4wMDQuMzUzLjA0OC41MjIuMTI2LjMyNy40OS40OTIuODI5LjU4MyAxLjQyMS4zODEgMi45Mi4wMjMgNC4zODctLjEwOCAxLjQzOS0uMTI4IDIuOTA1LS4wMzUgNC4zMTQtLjM1Mi4yMDktLjAzOS40MS0uMTA4LjU5OC0uMjA2LjM1Ny0uMjMyLjYyNC0uNTc4Ljc1NS0uOTgyLjA0My0uMTA0LjA3Mi0uMjEyLjA4Ni0uMzIzLjA0Ni0uNDc4LS4zNzItLjg5NC0uODI4LTEuMDUxLS40NTctLjE1Ny0uOTUyLS4xMzItMS40MzMtLjE4MWE0LjUyNCA0LjUyNCAwIDAxLTIuNjM2LTEuMjAxIDQuNDkzIDQuNDkzIDAgMDEtMS4zNTgtMi41NTFjLS4wMTgtLjEwNS0yLjM3NC0uMDQzLTIuNjIyLjA5Mi0uMjkuMTU5LS40NjYuNTU1LS42My44MjMtLjI2NC40MzItLjUxLjg3NS0uNzM1IDEuMzI5eiIgb3BhY2l0eT0iLjEiLz4KICAgIDxwYXRoIGZpbGw9IiMzRjNENTYiIGQ9Ik0yNS42MjEgMTYwLjExOWE3LjU0NyA3LjU0NyAwIDAwLS4zOTQuOTExYy0uMjk0Ljg2Mi0uMzM5IDEuNzg1LS4zOCAyLjY5NGExLjI4IDEuMjggMCAwMC4wNDcuNTIyYy4xMjYuMzI3LjQ5LjQ5My44MjkuNTg0IDEuNDIxLjM4MSAyLjkyLjAyMiA0LjM4Ny0uMTA4IDEuNDM5LS4xMjkgMi45MDUtLjAzNiA0LjMxNS0uMzUyLjIwOC0uMDM5LjQxLS4xMDkuNTk3LS4yMDdhMS44NSAxLjg1IDAgMDAuNzU1LS45ODJjLjA0My0uMTAzLjA3Mi0uMjEyLjA4Ni0uMzIyLjA0Ni0uNDc5LS4zNzItLjg5NC0uODI4LTEuMDUyLS40NTYtLjE1Ny0uOTUyLS4xMzEtMS40MzItLjE4YTQuNTI1IDQuNTI1IDAgMDEtMi42MzctMS4yMDIgNC40OSA0LjQ5IDAgMDEtMS4zNTgtMi41NTFjLS4wMTctLjEwNC0yLjM3NC0uMDQzLTIuNjIyLjA5My0uMjkuMTU5LS40NjYuNTU1LS42My44MjItLjI2NC40MzItLjUxLjg3NS0uNzM1IDEuMzN6Ii8+CiAgICA8cGF0aCBmaWxsPSIjMkYyRTQxIiBkPSJNMzUuODEzIDEzNi45NTFjLjAzIDQuNzEzLTEuMzQ2IDkuMzItMi45NiAxMy43NWExMjQuOTA2IDEyNC45MDYgMCAwMS0yLjAzNCA1LjIxNmMtLjQyNSAxLjAyMS0uOTAzIDIuMTU0LS40NDggMy4xNjItMS4zMS0uNDk2LTIuNjUtLjc4OS00LjAzMy0uNTYuNTI2LTEuMDY4LjQ3Ni0yLjMxMi41LTMuNTAxYTMwLjM2IDMwLjM2IDAgMDEuNTIyLTQuODEgMjYuNzQgMjYuNzQgMCAwMTIuMzYtNi45NTQgNS4yMyA1LjIzIDAgMDAuNDU5LTEuNDY1IDkuMjg3IDkuMjg3IDAgMDAtLjE5NS0zLjM4NCAyMC4xNDcgMjAuMTQ3IDAgMDAtMi44MjQtNi44OTJsLS4wMTQuMTQxYTIwLjUxNyAyMC41MTcgMCAwMS0uMzU1IDIuMDg2Yy0uMzA5IDEuNDYtLjY3IDIuOTItLjYxNiA0LjM4OGEyMy41MiAyMy41MiAwIDAxLS4yODkgNC42NTMgNy4wMiA3LjAyIDAgMDEtLjQ3IDEuNzU5Yy0uMTUuMzM3LS4zNDguNjU0LS40NjcgMS4wMDRhNC41NiA0LjU2IDAgMDAtLjE4MyAxLjI0MmMtLjE3NiAzLjU0OS0uMDAyIDguMDkxLjcyNiAxMS41N2E2LjgwNiA2LjgwNiAwIDAwLTMuNTMuODAxYy0uODgtMi40NzUtMS40NDEtNS45ODktMS44Ni04LjU5NGEyNS4zMzcgMjUuMzM3IDAgMDEuMjg0LTkuMjZjLjIwNi0xLjAxNi4yODQtMi4wNTMuMjMtMy4wODgtLjA5NC0yLjA2LS42MDItNC4wNzYtMS4xMDgtNi4wNzdhMTM0MDguMzg3IDEzNDA4LjM4NyAwIDAxLTEuODctNy4zODdjLS4yNDMtLjk2My0uNDg4LTEuOTQ2LS40MTItMi45MzZhNy4zMDEgNy4zMDEgMCAwMS4zNDEtMS41OTYgMTcuODE2IDE3LjgxNiAwIDAxMi40MjUtNC45MzljNC4yMi4yMzYgOC40OS0uMTU3IDEyLjcwMy0uNDhhLjg2Ljg2IDAgMDEuNTk3LjEwMS44NC44NCAwIDAxLjI0LjU0MWwxLjU4MyA4LjkzMmMuMjU3IDEuMjQ0LjQwOCAyLjUwNy40NSAzLjc3NS4wMDkgMS4yNjQtLjE5NyAyLjUyNC0uMTUgMy43ODguMDY1IDEuNzAyLjM4OCAzLjMxMS4zOTggNS4wMTR6Ii8+CiAgICA8cGF0aCBmaWxsPSIjMDAwIiBkPSJNMjcuMTQ2IDEzMS42NTRjLS4wODIuNzAxLS4yIDEuMzk3LS4zNTUgMi4wODYtLjkxOC0xLjU4MS0xLjkwNy0zLjEyMi0yLjc1Mi00Ljc0Mi0uMjE4LS40MTgtLjQyNS0uODQxLS42MzQtMS4yNjNsLS42MjItMS4yNjZjLS4xMTctLjIzNy0uMjM0LS40NzUtLjMzOS0uNzE4YTUuMTk2IDUuMTk2IDAgMDEtLjM5Mi0xLjA5N2MuMzA3LS4wNTYgMS4wNjQuNzY5IDEuMjkyLjk4NC40NjcuNDU4LjkwNy45NDIgMS4zMiAxLjQ0OS40OTkuNTYzLjk1NyAxLjE2IDEuMzcgMS43ODhhOC42OCA4LjY4IDAgMDExLjExMiAyLjc3OXoiIG9wYWNpdHk9Ii4xIi8+CiAgICA8cGF0aCBmaWxsPSIjRUZCN0I5IiBkPSJNMzAuNjYzIDg4LjI3MXMtMi4zNDUgMy43MTktMS45MSA0LjQ5N2MuNDMzLjc3OC03LjM4Mi0uNjkyLTcuMzgyLS42OTJzNC4yNTUtMy43MTggMy44Mi01LjI3NWMtLjQzMy0xLjU1NiA1LjQ3MiAxLjQ3IDUuNDcyIDEuNDd6Ii8+CiAgICA8cGF0aCBmaWxsPSIjRUZCN0I5IiBkPSJNMjguNTc4IDg5LjM5NWMyLjkyNiAwIDUuMjk3LTIuMzYxIDUuMjk3LTUuMjc1IDAtMi45MTMtMi4zNzEtNS4yNzUtNS4yOTctNS4yNzUtMi45MjUgMC01LjI5NyAyLjM2Mi01LjI5NyA1LjI3NSAwIDIuOTE0IDIuMzcyIDUuMjc1IDUuMjk3IDUuMjc1eiIvPgogICAgPHBhdGggZmlsbD0iIzA3N0NCMiIgZD0iTTMwLjc0IDkyLjE3YTUuMzg0IDUuMzg0IDAgMDAtMi4yMjctMS43OSA4LjA1IDguMDUgMCAwMC0xLjU4My0uMzg4bC0yLjExMy0uMzdjLS41MTgtLjA5MS0xLjA3Mi0uMTc4LTEuNTUzLjAzNS0uMjQ1LjEyNC0uNDY5LjI4Ni0uNjYyLjQ4Mi0uNzM0LjY3Ny0xLjQ0NiAxLjQyMi0yLjM2NyAxLjgxMy0uMjUyLjEwNy0uNTE2LjE4Ni0uNzY3LjI5NC0xLjAzLjQ0Mi0xLjgwMSAxLjM2NC0yLjIzOSAyLjM5My0uNDU5IDEuMDc5LS40NzIgMi4yNDktLjQzNiAzLjQyYTUuOTggNS45OCAwIDAwLjExOSAxLjIwNGMuMTA3LjM3Ny4yNC43NDYuNCAxLjEwNC4yOTMuNzY0LjUzNyAxLjU0NS43MzMgMi4zNGE3My42OCA3My42OCAwIDAxMS4zNjUgNi4zMjVjLjAzMi4zMDYuMTIyLjYwMy4yNjcuODc1LjIwNC4yNzEuNDUuNTA3LjczLjY5OS4yMjIuMTgzLjQyNy4zODUuNjEyLjYwNC4wOTEuMDk0LjE1Ny4yMDguMTk0LjMzMy4wNjkuMzE0LS4yMS41ODQtLjQzOC44MTRhMy45MjcgMy45MjcgMCAwMC0uOTg5IDEuNzE1LjY4LjY4IDAgMDAtLjE1Ny40NzQuODIuODIgMCAwMS4wNS4zNzQuODcuODcgMCAwMS0uMi4zMDkgMS4xNjEgMS4xNjEgMCAwMC0uMjIuOTMyIDE0LjkyIDE0LjkyIDAgMDA1LjE4NyAxLjIxYy40MzguMDIuODc4LjAyIDEuMzEzLjA3NC4zMS4wMzguNjE2LjEwMy45MjcuMTM5LjQ1Mi4wNDIuOTA2LjA1MyAxLjM1OS4wMzQgMi4zNTgtLjA0NiA0Ljc4NC0uMTA3IDYuOTUtMS4wMzguMjAzLTEuMTA2LS4yMzYtMi4yMzMtLjUxMi0zLjMyMy0uNDM4LTEuNzM0LS41NDgtMy41MzItLjg3NC01LjI5LS4yMjQtMS4yMDQtLjU0OC0yLjM4OC0uNzM0LTMuNTk4LS4xODYtMS4yMS0uMjMtMi40NjQuMTA1LTMuNjQyLjI5Ni0xLjA0NC4zODUtMi4xMDYuNjkyLTMuMTQ3LjMwNi0xLjA0LjUyMS0yLjE2OC4xNzUtMy4xOTctLjQzLTEuMjc0LTEuNzU4LTIuMjgzLTMuMTA2LTIuMjEzeiIvPgogICAgPHBhdGggZmlsbD0iIzJGMkU0MSIgZD0iTTMzLjM0MiA4MS4zMjRjLjI1Ny0uOTM3LS4yNS0xLjkxMi0uODU1LTIuNjczLS42NTEtLjgxOC0xLjQ5My0xLjU1NS0yLjUyLTEuNzYtLjgzNS0uMTY4LTEuNy4wMzQtMi41NDktLjAzMy0uNzUyLS4wNi0xLjQ3My0uMzI4LTIuMjIyLS40MjVhNy4wNjcgNy4wNjcgMCAwMC0yLjAzLjA3NyA3LjU4IDcuNTggMCAwMC0xLjg1Mi41MTVjLTIuMjU1Ljk5NS0zLjU3NiAzLjQ1Mi0zLjg4MyA1Ljg5LS4zMDYgMi40MzcuMjMxIDQuODkyLjc2NiA3LjI5bC40ODMgMi4xNjdjLjUwMiAyLjI1NiAxLjAwNyA0LjUyNCAxLjE0MSA2LjgzLjEzNSAyLjMwOC0uMTE2IDQuNjc2LTEuMDY5IDYuNzgzYTE0LjYwNyAxNC42MDcgMCAwMDYuNzMtNy41ODhjLjM0My0uODkyLjU5Ni0xLjgxOC45NzYtMi42OTUuMzI2LS43NTUuNzQ0LTEuNDcgMS4wMzQtMi4yNC4zMjYtLjg2OS40ODMtMS43OTIuNDYxLTIuNzItLjAxNi0uNjk4LS4xMzMtMS4zOTItLjExMy0yLjA4OS4wMi0uNjk3LjE5OS0xLjQyNS42ODMtMS45My40My0uNDQ3IDEuMDQxLS42NjMgMS41OTYtLjk0NWE2LjYyIDYuNjIgMCAwMDIuMjMtMS44NTNjLjM1LS40NTMuMzY4LS42NDcuNDU3LTEuMTcuMDg2LS41MDcuNC0uOTM4LjUzNi0xLjQzMXoiLz4KICAgIDxwYXRoIGZpbGw9IiNFRkI3QjkiIGQ9Ik0zMy4zNSAxMjAuOTU0Yy4wNTMuNTQyLS4wNDYgMS4wOTguMDczIDEuNjMuMDkuMzk5LjI5OC43NjMuNDIgMS4xNTQuMTIyLjQ1My4xODQuOTIuMTg2IDEuMzg5LjAxOC4zOTMtLjAxMy44NzEtLjM2MiAxLjA1Ni0uMTUzLjA4MS0uMzM3LjA4Mi0uNDk3LjE1LS4xNi4wNjgtLjI5Ny4yNjEtLjIwNS40MDguMDc3LjEyMy4yNTEuMTI1LjM5NC4xNTQuMTQzLjAyOS4zLjE3Ny4yMTguMjk3YS4yNTIuMjUyIDAgMDEtLjEyOC4wOCAxLjQ4NSAxLjQ4NSAwIDAwLS40MS4xOTcuNDEzLjQxMyAwIDAwLS4xNjkuMzk3LjI0My4yNDMgMCAwMC4xMzcuMTY3LjI1My4yNTMgMCAwMC4yMTUtLjAwNWMtLjA2NC4xMzYtLjA5OS4yODQtLjEwMi40MzUuNTU5LjIzOSAxLjE4OC0uMDc0IDEuNzE0LS4zNzhhNi41NiA2LjU2IDAgMDAuNjYtLjQxOCAzLjI2IDMuMjYgMCAwMDEuMjcxLTIuNzg0IDYuMzYzIDYuMzYzIDAgMDAtLjEyNC0uNzk2Yy0uMDgxLS40NTctLjE5LS45MDktLjMyMi0xLjM1My0uMTUtLjQ2OS0uMzU4LS45MTgtLjUzNS0xLjM3Ny0uMzc3LS45NzktLjYyLTIuMDA0LS45Ni0yLjk5N2EuMzQuMzQgMCAwMC0uMTM3LS4yNDEuMzQyLjM0MiAwIDAwLS4yNzEtLjA2NCA0LjAxNSA0LjAxNSAwIDAwLTEuMTQuMTA0Yy0uMjI2LjA2Ni0uNTk2LjIxLS42OTcuNDQ2LS4wOTEuMjEyLjA2MS40MDEuMTU4LjU4M2E0LjgzIDQuODMgMCAwMS42MTMgMS43NjZ6Ii8+CiAgICA8cGF0aCBmaWxsPSIjMDAwIiBkPSJNMzIuOTkgOTQuNDUyYy4xMTQuMTE0LjIwOC4yNDYuMjgxLjM4OS43NTMgMS4zNjIuNzYyIDIuOTkyLjc5OCA0LjU0Ny4wNCAxLjc0OS4xMzYgMy40OTcuMjMyIDUuMjQ1bC4yODcgNS4yMjZjLjAyOC40OTkuMDU1IDEgLjEyNiAxLjQ5NS4zMzguMDg1LjM1Mi41Mi4zMTMuODY1LS4yNDIgMi4xMzMtLjg0NSA0LjMyOC0uMjE0IDYuMzgxYS42NDEuNjQxIDAgMDAuMTQ4LjI4NCAxLjcwOSAxLjcwOSAwIDAwLS45OC0uMDEzYy0uMzE0LjA2Ni0uNjE2LjE4LS45My4yNTItLjM5OS4wOS0uODUuMTI5LTEuMTIzLjQzM2EyMS41NzYgMjEuNTc2IDAgMDEtLjczMy0yLjgxOWMtLjE0OS0uODY4LS4xOS0xLjc3LS41NDUtMi41NzctLjE0My0uMzI1LS4zMzQtLjYyNy0uNTEtLjkzNy0uOTE5LTEuNjMyLTEuMzctMy40NzYtMS44MS01LjI5NWE3LjY5MiA3LjY5MiAwIDAxLS4xNC0yLjYzYy4wNjMtLjQwMS4xLS44MDYuMTEtMS4yMTFhOS42OTQgOS42OTQgMCAwMC0uMTY4LTEuMjczIDguNTI1IDguNTI1IDAgMDEuMjU1LTMuNjg4Yy4zMy0xLjExLjYxOS0yLjI3OSAxLjMwOC0zLjIxMi42ODktLjkzMiAyLjEzMy0xLjQ0NiAzLjI5Ni0xLjQ2MnoiIG9wYWNpdHk9Ii4xIi8+CiAgICA8cGF0aCBmaWxsPSIjMDc3Q0IyIiBkPSJNMzMuMjUxIDk0LjI3OWMuMTEzLjExNC4yMDguMjQ2LjI4LjM4OS43NTQgMS4zNjIuNzYzIDIuOTkyLjc5OSA0LjU0Ny4wNCAxLjc1LjEzNSAzLjQ5Ny4yMzIgNS4yNDVsLjI4NyA1LjIyNmMuMDI4LjUuMDU1IDEgLjEyNiAxLjQ5NS4zMzguMDg1LjM1Mi41Mi4zMTMuODY1LS4yNDMgMi4xMzMtLjg0NSA0LjMyOC0uMjE0IDYuMzgxYS42MzUuNjM1IDAgMDAuMTQ3LjI4NCAxLjcwOSAxLjcwOSAwIDAwLS45NzktLjAxM2MtLjMxNC4wNjYtLjYxNy4xODEtLjkzLjI1Mi0uMzk5LjA5LS44NS4xMjktMS4xMjMuNDMzYTIxLjYwOCAyMS42MDggMCAwMS0uNzMzLTIuODE5Yy0uMTUtLjg2OC0uMTktMS43Ny0uNTQ1LTIuNTc3LS4xNDMtLjMyNS0uMzM1LS42MjctLjUxLS45MzctLjkyLTEuNjMxLTEuMzctMy40NzYtMS44MS01LjI5NWE3LjY5IDcuNjkgMCAwMS0uMTQtMi42M2MuMDYzLS40MDEuMS0uODA1LjExLTEuMjExYTkuNjkzIDkuNjkzIDAgMDAtLjE2OC0xLjI3MyA4LjUyMyA4LjUyMyAwIDAxLjI1NS0zLjY4OGMuMzMtMS4xMS42MTgtMi4yNzkgMS4zMDgtMy4yMTIuNjg5LS45MzIgMi4xMzMtMS40NDYgMy4yOTUtMS40NjJ6Ii8+CiAgICA8ZyBmaWxsPSIjMDAwIiBvcGFjaXR5PSIuMSI+CiAgICAgIDxwYXRoIGQ9Ik0zMi40ODcgNzguNjUxYy0uNjUxLS44MTgtMS40OTMtMS41NTUtMi41Mi0xLjc2LS44MzUtLjE2OC0xLjcuMDM0LTIuNTQ5LS4wMzMtLjc1Mi0uMDYtMS40NzMtLjMyOC0yLjIyMi0uNDI1YTcuMDY3IDcuMDY3IDAgMDAtMi4wMy4wNzcgNy41OCA3LjU4IDAgMDAtMS44NTIuNTE1Yy0uMzIuMTQyLS42MjYuMzE1LS45MTMuNTE1YTcuOTgxIDcuOTgxIDAgMDExLjYzNS0uNDI1IDcuMDcgNy4wNyAwIDAxMi4wMzEtLjA3NmMuNzQ5LjA5NiAxLjQ3LjM2NSAyLjIyMi40MjQuODUuMDY3IDEuNzE0LS4xMzQgMi41NS4wMzMgMS4wMjcuMjA1IDEuODY4Ljk0MyAyLjUxOSAxLjc2LjYwNi43NjEgMS4xMTMgMS43MzYuODU1IDIuNjczLS4xMzYuNDkzLS40NS45MjQtLjUzNiAxLjQzMi0uMDkuNTIzLS4xMDguNzE2LS40NTcgMS4xN2E2LjYzOSA2LjYzOSAwIDAxLTEuNjA4IDEuNDljLjE3LS4wOC4zNC0uMTU4LjUwNy0uMjQzYTYuNjIgNi42MiAwIDAwMi4yMy0xLjg1M2MuMzUtLjQ1My4zNjgtLjY0Ny40NTctMS4xNy4wODYtLjUwNy40LS45MzguNTM2LTEuNDMxLjI1OC0uOTM3LS4yNS0xLjkxMi0uODU1LTIuNjczek0yOC41MjMgODYuNzI0Yy4wOS0uMDk0LjE5LS4xOC4yOTYtLjI1Ni0uNTA2LjI0NC0xLjAzOS40NTktMS40MjUuODYxLS40ODQuNTA1LS42NjIgMS4yMzItLjY4MyAxLjkzLS4wMi42OTcuMDk3IDEuMzkuMTE0IDIuMDg4YTcuMjY0IDcuMjY0IDAgMDEtLjQ2MiAyLjcyMWMtLjI5Ljc3LS43MDggMS40ODUtMS4wMzQgMi4yNC0uMzguODc2LS42MzMgMS44MDItLjk3NiAyLjY5NGExNC42MTcgMTQuNjE3IDAgMDEtNS41NjQgNi44OTVsLS4wMzcuMDg4YTE0LjYwNyAxNC42MDcgMCAwMDYuNzMtNy41ODhjLjM0My0uODkyLjU5Ni0xLjgxOC45NzYtMi42OTUuMzI2LS43NTUuNzQ0LTEuNDcgMS4wMzQtMi4yNC4zMjYtLjg2OS40ODMtMS43OTIuNDYxLTIuNzItLjAxNi0uNjk4LS4xMzMtMS4zOTItLjExMy0yLjA4OS4wMi0uNjk3LjE5OS0xLjQyNS42ODMtMS45M3oiIG9wYWNpdHk9Ii4xIi8+CiAgICA8L2c+CiAgICA8cGF0aCBmaWxsPSIjMkYyRTQxIiBkPSJNMTQ0Ljg1MyAxNjQuMjU4Yy0uMjUyLjMwMy0uNjg0LjM2NC0xLjA3NC40MDRhNi4xMjEgNi4xMjEgMCAwMS0xLjUyNC4wMjljLS42MDMtLjA4OS0uOTY0LjI1Mi0xLjUxNC0uMDEtLjMzMy0uMTU5LS44NDUuMTU4LTEuMTc4IDAtLjQ1Ny0uMjE4LTIuMDMzLS4yNjMtMi4zMS0uNjg3LS4yNzgtLjQyNS0uMjUtMS4wOTYuMTg5LTEuMzQ2YTIuOTUgMi45NSAwIDAxLjM4NS0uMTVjLjczNi0uMjkyIDIuMjI2LTIuNDkgMy4wMDgtMi42MDMuMzg4LS4wMTguNzc0LjA3MiAxLjExMy4yNjEuNzcyLjM0MSAyLjEzNC42MTEgMi40NzEgMS40NzYuMjYyLjY3NS45OCAxLjk2Ny40MzQgMi42MjZ6TTE1MC4zOTYgMTY0LjA4NGMuMjUyLjMwMy42ODQuMzY0IDEuMDc0LjQwNGE2LjEyMSA2LjEyMSAwIDAwMS41MjQuMDI5Yy42MDMtLjA4OSAxLjMzNi40MjYgMS44ODcuMTY0LjMzMy0uMTU5Ljg0NS0uMDc2IDEuMTc4LS4yMzUuNDU3LS4yMTggMi42MDIuMjY4IDIuODgtLjE1Ny4yNzgtLjQyNS4yNDktMS4wOTUtLjE5LTEuMzQ2YTIuODk2IDIuODk2IDAgMDAtLjM4NS0uMTVjLS43MzUtLjI5MS0zLjE2OC0yLjk2LTMuOTUtMy4wNzJhMi4wODggMi4wODggMCAwMC0xLjExMy4yNjFjLS43NzIuMzQxLTIuMTM0LjYxMS0yLjQ3MSAxLjQ3Ni0uMjYyLjY3NS0uOTggMS45NjctLjQzNCAyLjYyNnoiLz4KICAgIDxwYXRoIGZpbGw9IiMyRjJFNDEiIGQ9Ik0xNTIuODMyIDExNi4xNjFjLjI0MS40NjEuNDUyLjkzOC42MzEgMS40MjcgMi4xMzkgNS42MzYgMi4zMTggMTEuNzk1IDIuNDA3IDE3LjgyLS4wMDguOTc5LjA0OCAxLjk1OC4xNjggMi45MzEuMDg5LjYxNi4yMzEgMS4yNC4xNCAxLjg1Ni0uMDc5LjUzMy0uMzMxIDEuMDU5LS4yMzEgMS41ODguMDQ1LjIzNy4xNi40NTcuMjA1LjY5NS4wMzQuMjYuMDI3LjUyNC0uMDIyLjc4M2wtLjI4NiAyLjEyNWMtLjE2NCAxLjIxLS41NDEgMi4zNzctLjUyIDMuNTk4LjAxNC44MzMuMDI4IDEuNjY4LjEyMSAyLjQ5Ni4wOTQuODQxLjI3MSAxLjY3NS4yOCAyLjUyMS4wMDYuNjMzLS4wODEgMS4yNjQtLjEzNyAxLjg5NWExOS40OTMgMTkuNDkzIDAgMDAtLjA1NCAyLjY4Yy4wMzQuNjg5LjExMyAxLjQwNC40OCAxLjk4OS0uNDExLjU4Ni0uODE4IDEuMTEyLTEuNDc2IDEuMzk5LTEuMzM2LjU4NC0yLjg3LjM4Ni00LjMxNC4xNzcuMDgtLjI2NS4yMDQtLjU2Ni4wODgtLjgxOGExLjYzIDEuNjMgMCAwMC0uMjQ4LS4zMzYgMy45NTYgMy45NTYgMCAwMS0uNjQzLTEuNDg5Yy0uMTc1LS42MzQtLjM1LTEuMzE3LjAxLTEuOTI2YTEuMjMgMS4yMyAwIDAwLjE1Ni0uNzE1Yy0uMjk5LTMuOTk1LTEuMTkyLTguMTI5LS4zNy0xMi4wNS4wODgtLjQxOS4xOTMtLjgzNS4yNDItMS4yNi4wNDQtLjYxMS4wNTEtMS4yMjQuMDIyLTEuODM2bC0uMDE3LS44NTFjLS4wNDMtLjkwOS0uMDA2LTEuODQxLS4xODktMi43NC0uMTMxLS42NDQtLjM3Ni0xLjI5MS0uMDc0LTEuOTQ3YTEuMDczIDEuMDczIDAgMDAtLjA0NS0uOTQ1IDE3LjMyNCAxNy4zMjQgMCAwMS0xLjUyMi0zLjQ5OCA2LjA4MyA2LjA4MyAwIDAwLS4zMi0uOTk2IDcuMDQzIDcuMDQzIDAgMDAtLjYzMy0uOTY5Yy0uNzE2LTEuMDM0LTEuMTQ0LTIuMjMzLTEuNTY0LTMuNDE3LjA0NS0uMTA5LjAxMS0uMjYtLjEwNi0uMjQ0YS40NDguNDQ4IDAgMDAtLjI3Ni4yMDVjLS41NzYuNzctLjYyNCAxLjc5OC0uNjUgMi43NTgtLjA0My44OTItLjAyOCAxLjc4Ni4wNDYgMi42NzYuMDQ5LjQ2Ny4xMzEuOTI5LjE3MyAxLjM5Ni4wNTUuODcyLjA0MSAxLjc0Ni0uMDQyIDIuNjE1bC0uMjkzIDQuMjc3Yy0uMDQuMzU0LS4wMTkuNzEyLjA2MyAxLjA1OC4xODguNTk5LjI4IDEuMjI1LjI3MiAxLjg1M2EzMy4yNzcgMzMuMjc3IDAgMDAuNjY4IDcuNjk5Yy4xNDQuNjk5LjMxIDEuMzk0LjQzMiAyLjA5Ny4yODcgMS42NTcuMzI2IDMuMzQ2LjM2NSA1LjAyNy4wMjMgMS4wMzQuMDQgMi4xMDQtLjM2OCAzLjA1NS0uMTYuMjkzLS4yNzguNjA3LS4zNDkuOTMyYTEuOTkyIDEuOTkyIDAgMDEtLjA2Ny40OThjLS4xMi4zMDgtLjQ4LjQzOS0uODA0LjUwOWE2LjUxNCA2LjUxNCAwIDAxLTEuODUzLjEzMSAxLjA4IDEuMDggMCAwMS0uNDIzLS4wOTNjLS4yMzEtLjEyLS4zNTQtLjM3My0uNTMtLjU2NS0uMzgtLjQxMy0xLjAxMi0uNTM5LTEuMzU4LS45ODEtLjQzMy0uNTUzLS4yNTEtMS4zNTMuMDA2LTIuMDA1LjI1OC0uNjUyLjU3Ny0xLjM2LjM1NS0yLjAyNS0uMjYxLS43ODQtMS4xODgtMS4yMDYtMS40MzctMS45OTQuNzEtMy4xNzctMS4wMzMtNi40NjMtLjg4NC05LjcxNC4wMzUtLjc1OC4xNjctMS41MDkuMTctMi4yNjguMDA3LTEuOTUzLS44NDMtMy44NTMtLjcwMy01LjgwMS4zNTUtNC45NS0xLjMzOC05Ljg5Mi0yLjA1NS0xNC44MDNhOS42MzkgOS42MzkgMCAwMS0uMTUxLTIuMjI0Yy4xMDYtMS4yMjkuNjc5LTIuMzczIDEuMzY4LTMuMzk5LjQ5NC0uNzM0IDEuMDg4LTEuNDUzIDEuOTA2LTEuNzk3LjY3My0uMjgzIDEuNDI4LS4yODIgMi4xNTktLjI3NiAxLjgyNC4wMTQgMy42NDguMDI4IDUuNDcxLjA2MWExOC4wNjYgMTguMDY2IDAgMDEzLjY5OS4zMjljLjk4Ny4yMjUgMS45MS42ODMgMi45MTEuODI2eiIvPgogICAgPHBhdGggZmlsbD0iI0ExNjE2QSIgZD0iTTEyMy4zNjQgOTMuNTk0Yy0uMTc2LS44NjEuMTU4LTEuNzgyLS4xMDQtMi42MjItLjA5NC0uMzAyLS4zMjUtLjYxNy0uNjQzLS42MDgtLjMwOS4wMDgtLjUxOC4zMS0uNzkyLjQ1Mi0uNDQyLjIzLS45NzkuMDIxLTEuNDItLjIxLS40NDEtLjIzLS45MzMtLjQ5LTEuNDEyLS4zNTItLjA1Ni4yOTguMTQ2LjU4My4zNjYuNzkzLjI0NC4xODQuNDQ2LjQxNy41OTMuNjg0LjA3Mi4yOS4wOTkuNTg3LjA4MS44ODUuMDUuNjgyLjQ4NCAxLjI2OS45MTcgMS44LjY5OC44NTQgMS40NCAxLjY3MyAyLjIyMiAyLjQ1My0uMDAyLS4wMDEgMS4xODYtMS40NjUgMS4xNjMtMS43Mi0uMDIxLS4yNDItLjQyMS0uNDY3LS41NjYtLjY2MmEyLjI2MiAyLjI2MiAwIDAxLS40MDUtLjg5M3pNMTU4LjcyOCAxMTEuNjE4Yy4xMDYuMjc0LjE4NS41NTcuMjM3Ljg0NS4zMDYgMS40NzkuNDk5IDIuOTc4LjY5IDQuNDc2LjA1LjQuMTAxLjgwMS4xMTcgMS4yMDNhNS40OSA1LjQ5IDAgMDEtLjU3MyAyLjc2NyAzLjg4NSAzLjg4NSAwIDAxLTIuMDgyIDEuODY4Yy0uMjc5LjA5OC0uNjY3LjExNi0uNzg5LS4xNTJhLjU3LjU3IDAgMDEuMDAzLS4zODJjLjExLS4zOTkuMzMzLS43NTcuNDc4LTEuMTQ0YTMuMDc4IDMuMDc4IDAgMDAuMTAzLTEuODA5Yy0uMDU3LS4yMjktLjE3Ny0uNDg1LS40MS0uNTMxYTIuMDUyIDIuMDUyIDAgMDEtLjI3MiAxLjUzOS41NDIuNTQyIDAgMDEtLjMxOC4yNjguNDI5LjQyOSAwIDAxLS40NDctLjI4NyAxLjIzNyAxLjIzNyAwIDAxLS4wMzYtLjU3Yy4wNzUtLjk2MS4yMjItMS45MTUuNDQtMi44NTMuMjU5LTEuMDMxLjY3LTIuMDQ4LjY0NC0zLjExLS4wMjQtLjk2My0uNDA5LTEuOTA4LS4zMDEtMi44NjVhLjI4Ni4yODYgMCAwMS4wNTUtLjE2NC4zLjMgMCAwMS4xMi0uMDc0Yy4yNy0uMTExLjU0OC0uMjAzLjgzMS0uMjc0LjE2Ny0uMDQyLjczOC0uMjQuODk5LS4xNTEuMTI5LjA3MS4xNjkuNS4yMzQuNjQ1LjExNS4yNTcuMjY4LjQ5NS4zNzcuNzU1ek0xMzkuNjU1IDgzLjEwM2EzLjkzOSAzLjkzOSAwIDAwMy45NDYtMy45M2MwLTIuMTctMS43NjctMy45My0zLjk0Ni0zLjkzYTMuOTM5IDMuOTM5IDAgMDAtMy45NDcgMy45M2MwIDIuMTcgMS43NjcgMy45MyAzLjk0NyAzLjkzeiIvPgogICAgPHBhdGggZmlsbD0iI0ExNjE2QSIgZD0iTTE0My4xOTEgODIuMDE3YzAgLjI1LjAyLjUwMS4wNi43NDguMDU0LjI1LjEzMS40OTUuMjMuNzMxLjEzMi4zODUuMzE4Ljc0OC41NTMgMS4wOC4yMzguMzM1LjU5NS41NjYuOTk5LjY0OGEyOC41MDcgMjguNTA3IDAgMDEtOC41MzQgMi40NTZjLjQ2Ni0uMzA4Ljg1My0uNzIgMS4xMzItMS4yMDMuMTQ4LS4zMS4yNDktLjY0LjMtLjk4YTguMTkgOC4xOSAwIDAwLS4zNzEtNC4yMzljLS4wNDktLjEzNC0uMDk3LS4yOTgtLjAwNC0uNDA2YS4zOC4zOCAwIDAxLjE0LS4wOSA4LjgzMiA4LjgzMiAwIDAxMy43MS0uNjljLjMyNS4wMDIuNjUuMDE3Ljk3NS4wNC4xNzQuMDEzLjU1OS0uMDI0LjY5OC4xMDMuMTY1LjE1Mi4wODIuNjIzLjA4OC44M2wuMDI0Ljk3MnoiLz4KICAgIDxwYXRoIGZpbGw9IiNEMENERTEiIGQ9Ik0xNDUuMjggODQuODc3YTQuMjQgNC4yNCAwIDAwLTUuMDUyLTEuMjU0Yy0xLjg3OS44OTMtMi44MDQgMy4wMzgtNC40NTYgNC4yOTktLjM3Ni4yOC0uNzc2LjUyNi0xLjE5Ni43MzQtLjI0OC4xMzYtLjUxMy4yNC0uNzg4LjMxLS4zMDguMDY4LS42MzEuMDU5LS45MzguMTMyYTIuNDE2IDIuNDE2IDAgMDAtMS4zOTcgMS4wOCA3LjU1NCA3LjU1NCAwIDAxLS41MTQuNzg5Yy0uMTcyLjIwNC0uMzg3LjM2OS0uNTY5LjU2NC0uNjU3LjcwMy0uODM2IDEuNzEzLS45ODYgMi42NjJhMTIuNjU0IDEyLjY1NCAwIDAxLTEuNjc2IDEuNDcyIDIuMzggMi4zOCAwIDAxLS42MjIuMzQzYy0uNTc4LjE4Ny0xLjIxNi0uMDUtMS43MDgtLjQwNS0uNDkzLS4zNTQtLjg5NC0uODIyLTEuMzgtMS4xODVhNTAuNzQzIDUwLjc0MyAwIDAxLTEuMzg4IDEuODg2LjYxLjYxIDAgMDEtLjMxMS4yNTRjLS4wODkuMjk5LjEyNS42MDkuMjg2Ljg3N2wuODYgMS40NGE3LjIyMSA3LjIyMSAwIDAwMS4wMzkgMS40NWMuNDExLjQ0MS45NzMuNzEzIDEuNTc1Ljc2M2EzLjIgMy4yIDAgMDAxLjExMS0uMjEyYzEuNjM5LS41NDkgMy4xNDUtMS40MjYgNC43MDUtMi4xNzEuMjgzLS4xNjEuNTk5LS4yNTMuOTI0LS4yN2EuNzQ0Ljc0NCAwIDAxLjczMS41MjNjLjE3OS45LjM1NiAxLjc5Ny42MDIgMi42OC4yMzIuODM0LjQ0OCAxLjY3Mi42NDcgMi41MTQuMTMzLjUxNS4yMjggMS4wNC4yODQgMS41NjkuMDI2LjMuMDI4LjYwMi4wMzEuOTA0bC4wMzMgMy42NDNjLjAwNC4zOTMtLjIyMS42MzctLjM5NC45OWEyLjMyNyAyLjMyNyAwIDAwLS4xMTEgMS43NTFjLjA5Ny4yODQuMjQ5LjU0Ny4zNTcuODI3LjI2LjY3OC4yNSAxLjQyNS4yMzYgMi4xNTFsLS4wMzEgMS42NjJhLjM1OC4zNTggMCAwMC4wMzYuMjAxYy4xMDIuMTU4LjM0OC4wNjUuNTAzLS4wNDJsLjc4MS0uNTM5YzEuMDQ0LjE0NCAyLjA5OS4xOTQgMy4xNTMuMTUyIDIuOTk2LS4wNDMgNS45OTMuMDc2IDguOTgyLjI4NC45OTguMDcgMi4wMjkuMTQ1IDIuOTcyLS4xODcuNDc4LS4xNjguOTE5LS40MzYgMS40MDgtLjU3MS4yMDEtLjAzMi4zOTMtLjExLjU1OC0uMjI5LjI3NC0uMjUyLjIwMS0uNjk1LjA5LTEuMDQ5LS4wOTgtLjMxLS4yMDktLjYxNS0uMzMzLS45MTZhNS4zMTcgNS4zMTcgMCAwMS0uMzExLS44NTdjLS4xNS0uNjYzLS4wMDQtMS40My0uNDE5LTEuOTctLjkyOC0xLjIwOS0uMjcxLTMuMDY5LS42Ni00LjU0LS4xMjYtLjQ3Ni0uMzEzLS45MzUtLjQzMS0xLjQxMy0uMDc0LS4zLS4xMjEtLjYwNi0uMTY4LS45MTJhMTAuOTUyIDEwLjk1MiAwIDAxLS4xODEtMi4wMDRjLjAxMi0uMzM2LjA1NC0uNjcuMDk3LTEuMDA0bC4yOTUtMi4zMThjLjM2OC42ODUuNjI0IDEuNDI1Ljc1OCAyLjE5MS4yNjkgMS4xNDQuNTk5IDIuMjczLjk5IDMuMzgxLjIyMi42NjkuNDQ1IDEuMzM4LjY4NSAyIC4xNTIuMzc3LjI2OS43NjcuMzUgMS4xNjUuMDUzLjMyMS4wNTQuNjQ5LjA5Mi45NzJhNC40MiA0LjQyIDAgMDAuNzExIDEuOTQxYy4wNzEuMTM1LjE5LjI0LjMzMy4yOTRhLjY1LjY1IDAgMDAuMjkxLS4wMTIgMTEuNTA5IDExLjUwOSAwIDAwMi45OTYtMS4wMzIgMS4xOSAxLjE5IDAgMDAuNTA2LS4zOTRjLjI1NS0uNDEyLS4wMy0uOTI3LS4xNTQtMS4zOTUtLjEzMi0uNTAyLS4xMDEtMS4xMDYtLjQ5OC0xLjQ0My0xLjM4Mi0xLjE3MS0uNjk2LTMuNTQ5LTEuMzg4LTUuMjItLjEyMS0uMjkzLS4yNjMtLjU3OC0uMzY4LS44NzgtLjA4LS4yMjktLjEzOC0uNDY1LS4xOTYtLjcwMS0uNDgzLTEuOTQ2LTEtMy44ODMtMS41NTEtNS44MTItLjU3MS0yLS41MDktNC4wODItMS4yMjgtNi4wMzRhNi4zNTEgNi4zNTEgMCAwMC0uNTI3LTEuMTc3IDMuMTkgMy4xOSAwIDAwLS45NTMtLjk3OGMtMS4wNDMtLjY4Ny0yLjA5Ny0xLjI3Mi0zLjMyMi0xLjUyMS0xLjIyNi0uMjUtMi41NTItLjIxLTMuODAzLS4yMDV6Ii8+CiAgICA8cGF0aCBmaWxsPSIjMkYyRTQxIiBkPSJNMTM2LjA2OCA4MC4xMzVjLS41My0uMjg1LS4zOS0xLjA1Ny0uNDgxLTEuNjUtLjE4NS0xLjIyMi0xLjU3LTEuOTQ2LTEuODk2LTMuMTM3LjM3NC4wNDUuNzUzLS4wMDggMS4wOTktLjE1NWExLjY2NCAxLjY2NCAwIDAwLS4yMjEtMS4xNDVjMS4xMzktLjQ2IDIuMDYxLTEuMzY1IDMuMjE4LTEuNzggMS40NTUtLjUyMyAzLjA3NS0uMjAyIDQuNTQyLjI4My43MzMuMjQzIDEuNDk1LjU1NyAxLjkzNyAxLjE4Ny40ODcuNjkuNDc5IDEuNi40NTMgMi40NDQtLjA0MSAxLjMyMy0uMDkzIDIuNjk0LS42ODUgMy44OC0uNzUzIDEuNTA5LTIuODg1IDIuOTA3LTQuNjQ3IDIuNDk4LS43MzItLjE3LS45NDgtLjYyOC0xLjI1Ny0xLjI2Mi0uMTkyLS4zOTUtLjI2NC0uODUzLS42OTctMS4wNTgtLjQyMy0uMjAxLS45MzUuMTI1LTEuMzY1LS4xMDV6Ii8+CiAgICA8ZyBmaWxsPSIjMDAwIiBvcGFjaXR5PSIuMSI+CiAgICAgIDxwYXRoIGQ9Ik0xNTIuMDcyIDEwOC4zMTZjLjAwNS4xMDQuMDA4LjIwOS4wMDkuMzE1YTguMTYzIDguMTYzIDAgMDEtLjAwOS0uMzE1ek0xMzQuOTc4IDExMi4yNjRjLjI2LjY3OC4yNSAxLjQyNS4yMzcgMi4xNWwtLjAxMS41NjlhMy44NzQgMy44NzQgMCAwMC0uMjI2LTEuMTQ3Yy0uMTA3LS4yOC0uMjU5LS41NDMtLjM1Ni0uODI3YTIuMzIgMi4zMiAwIDAxLjAxNy0xLjUyOGMuMDk3LjI2Ny4yMzcuNTE4LjMzOS43ODN6IiBvcGFjaXR5PSIuMSIvPgogICAgPC9nPgogICAgPHBhdGggZmlsbD0iIzAwMCIgZD0iTTEzNC43OTEgNzQuNjIxbC0uMDA0LjAwMmExLjY1IDEuNjUgMCAwMC0uMjE4LS41NzUgNS4xMiA1LjEyIDAgMDAuMTk5LS4wODZjLjA1Mi4yMTYuMDU5LjQ0LjAyMy42NTl6IiBvcGFjaXR5PSIuMSIvPgogICAgPHBhdGggZmlsbD0iI0ZGNjU4NCIgZD0iTTEwLjQyIDY2LjYwOWwtLjkzLS44MzZjLTMuMzA0LTMuMDItNS40ODUtNC45ODEtNS40ODUtNy40MjNhMy40NjIgMy40NjIgMCAwMTEuMDE0LTIuNTEgMy40OSAzLjQ5IDAgMDEyLjUxNC0xLjAyNSAzLjgwOCAzLjgwOCAwIDAxMi44ODcgMS4zNSAzLjc5MiAzLjc5MiAwIDAxMi44ODYtMS4zNSAzLjUwMiAzLjUwMiAwIDAxMi41MTQgMS4wMjUgMy40NzUgMy40NzUgMCAwMTEuMDE0IDIuNTFjMCAyLjQ0Mi0yLjE4IDQuNDAyLTUuNDg0IDcuNDIzbC0uOTMuODM2eiIvPgogICAgPHBhdGggc3Ryb2tlPSIjM0YzRDU2IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS13aWR0aD0iMiIgZD0iTTEyLjA3IDY0LjQ5NmwtLjkzLS44MzVjLTMuMzAzLTMuMDItNS40ODQtNC45ODEtNS40ODQtNy40MjNhMy40NjIgMy40NjIgMCAwMTEuMDE0LTIuNTEgMy40OSAzLjQ5IDAgMDEyLjUxNC0xLjAyNSAzLjgwOCAzLjgwOCAwIDAxMi44ODYgMS4zNSAzLjc5MiAzLjc5MiAwIDAxMi44ODctMS4zNSAzLjUwMiAzLjUwMiAwIDAxMi41MTQgMS4wMjUgMy40NzQgMy40NzQgMCAwMTEuMDE0IDIuNTFjMCAyLjQ0Mi0yLjE4MSA0LjQwMi01LjQ4NSA3LjQyM2wtLjkzLjgzNXoiLz4KICA8L2c+CiAgPGRlZnM+CiAgICA8Y2xpcFBhdGggaWQ9ImNsaXAwIj4KICAgICAgPHBhdGggZmlsbD0iI2ZmZiIgZD0iTTAgMGgyMzF2MTY1SDB6Ii8+CiAgICA8L2NsaXBQYXRoPgogIDwvZGVmcz4KPC9zdmc+Cg=="

/***/ }),

/***/ "./client/marketing/overview/welcome-card/index.js":
/*!*********************************************************!*\
  !*** ./client/marketing/overview/welcome-card/index.js ***!
  \*********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! gridicons */ "./node_modules/gridicons/dist/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./style.scss */ "./client/marketing/overview/welcome-card/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _images_welcome_svg__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./images/welcome.svg */ "./client/marketing/overview/welcome-card/images/welcome.svg");
/* harmony import */ var _images_welcome_svg__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_images_welcome_svg__WEBPACK_IMPORTED_MODULE_17__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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






var WelcomeCard = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(WelcomeCard, _Component);

  var _super = _createSuper(WelcomeCard);

  function WelcomeCard(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, WelcomeCard);

    _this = _super.call(this, props);
    _this.hide = _this.hide.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(WelcomeCard, [{
    key: "hide",
    value: function hide() {
      this.props.updateOptions({
        woocommerce_marketing_overview_welcome_hidden: 'yes'
      });
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_15__["recordEvent"])('marketing_intro_close', {});
    }
  }, {
    key: "render",
    value: function render() {
      if (this.props.isHidden) {
        return null;
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Card"], {
        className: "woocommerce-marketing-overview-welcome-card"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["Button"], {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Hide', 'woocommerce'),
        onClick: this.hide,
        className: "woocommerce-marketing-overview-welcome-card__hide-button"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_10___default.a, {
        icon: "cross"
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("img", {
        src: _images_welcome_svg__WEBPACK_IMPORTED_MODULE_17___default.a,
        alt: ""
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("h3", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Grow your customer base and increase your sales with marketing tools built for WooCommerce', 'woocommerce')));
    }
  }]);

  return WelcomeCard;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_11__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__["default"])(function (select) {
  var _select = select('wc-api'),
      getOptions = _select.getOptions,
      isUpdateOptionsRequesting = _select.isUpdateOptionsRequesting;

  var hideOptionName = 'woocommerce_marketing_overview_welcome_hidden';
  var options = getOptions([hideOptionName]);
  var isHidden = Object(lodash__WEBPACK_IMPORTED_MODULE_8__["get"])(options, [hideOptionName], 'no') === 'yes';
  var isUpdateRequesting = Boolean(isUpdateOptionsRequesting([hideOptionName]));
  return {
    isHidden: isHidden || isUpdateRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateOptions = _dispatch.updateOptions;

  return {
    updateOptions: updateOptions
  };
}))(WelcomeCard));

/***/ }),

/***/ "./client/marketing/overview/welcome-card/style.scss":
/*!***********************************************************!*\
  !*** ./client/marketing/overview/welcome-card/style.scss ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);
//# sourceMappingURL=marketing-overview.5d729648e4035b7dad6d.min.js.map
