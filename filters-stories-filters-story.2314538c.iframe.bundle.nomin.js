"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5190],{

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

/***/ "../../packages/js/components/src/calendar/date-range.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date_range)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js
var inherits = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/features/object/assign.js
var object_assign = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/features/object/assign.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/features/array/from.js
var from = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/features/array/from.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@21.8.0_@babel+runtime@7.23.5_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-w_qrvdladxp45xl5h4eb2r4lcuey/node_modules/react-dates/index.js
var react_dates = __webpack_require__("../../node_modules/.pnpm/react-dates@21.8.0_@babel+runtime@7.23.5_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-w_qrvdladxp45xl5h4eb2r4lcuey/node_modules/react-dates/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js + 6 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@21.8.0_@babel+runtime@7.23.5_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-w_qrvdladxp45xl5h4eb2r4lcuey/node_modules/react-dates/initialize.js
var initialize = __webpack_require__("../../node_modules/.pnpm/react-dates@21.8.0_@babel+runtime@7.23.5_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-w_qrvdladxp45xl5h4eb2r4lcuey/node_modules/react-dates/initialize.js");
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/input.js
var input = __webpack_require__("../../packages/js/components/src/calendar/input.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/calendar/phrases.js
/**
 * External dependencies
 */

/* harmony default export */ const phrases = ({
  calendarLabel: (0,build_module.__)('Calendar', 'woocommerce'),
  closeDatePicker: (0,build_module.__)('Close', 'woocommerce'),
  focusStartDate: (0,build_module.__)('Interact with the calendar and select start and end dates.', 'woocommerce'),
  clearDate: (0,build_module.__)('Clear Date', 'woocommerce'),
  clearDates: (0,build_module.__)('Clear Dates', 'woocommerce'),
  jumpToPrevMonth: (0,build_module.__)('Move backward to switch to the previous month.', 'woocommerce'),
  jumpToNextMonth: (0,build_module.__)('Move forward to switch to the next month.', 'woocommerce'),
  enterKey: (0,build_module.__)('Enter key', 'woocommerce'),
  leftArrowRightArrow: (0,build_module.__)('Right and left arrow keys', 'woocommerce'),
  upArrowDownArrow: (0,build_module.__)('up and down arrow keys', 'woocommerce'),
  pageUpPageDown: (0,build_module.__)('page up and page down keys', 'woocommerce'),
  homeEnd: (0,build_module.__)('Home and end keys', 'woocommerce'),
  escape: (0,build_module.__)('Escape key', 'woocommerce'),
  questionMark: (0,build_module.__)('Question mark', 'woocommerce'),
  selectFocusedDate: (0,build_module.__)('Select the date in focus.', 'woocommerce'),
  moveFocusByOneDay: (0,build_module.__)('Move backward (left) and forward (right) by one day.', 'woocommerce'),
  moveFocusByOneWeek: (0,build_module.__)('Move backward (up) and forward (down) by one week.', 'woocommerce'),
  moveFocusByOneMonth: (0,build_module.__)('Switch months.', 'woocommerce'),
  moveFocustoStartAndEndOfWeek: (0,build_module.__)('Go to the first or last day of a week.', 'woocommerce'),
  returnFocusToInput: (0,build_module.__)('Return to the date input field.', 'woocommerce'),
  keyboardNavigationInstructions: (0,build_module.__)('Press the down arrow key to interact with the calendar and select a date.', 'woocommerce'),
  chooseAvailableStartDate: function chooseAvailableStartDate(_ref) {
    var date = _ref.date;
    return /* translators: %s: start date */(
      (0,build_module/* sprintf */.nv)((0,build_module.__)('Select %s as a start date.', 'woocommerce'), date)
    );
  },
  chooseAvailableEndDate: function chooseAvailableEndDate(_ref2) {
    var date = _ref2.date;
    return /* translators: %s: end date */(
      (0,build_module/* sprintf */.nv)((0,build_module.__)('Select %s as an end date.', 'woocommerce'), date)
    );
  },
  chooseAvailableDate: function chooseAvailableDate(_ref3) {
    var date = _ref3.date;
    return date;
  },
  dateIsUnavailable: function dateIsUnavailable(_ref4) {
    var date = _ref4.date;
    return /* translators: %s: unavailable date which was selected */(
      (0,build_module/* sprintf */.nv)((0,build_module.__)('%s is not selectable.', 'woocommerce'), date)
    );
  },
  dateIsSelected: function dateIsSelected(_ref5) {
    var date = _ref5.date;
    return /* translators: %s: selected date successfully */(
      (0,build_module/* sprintf */.nv)((0,build_module.__)('Selected. %s', 'woocommerce'), date)
    );
  }
});
;// CONCATENATED MODULE: ../../packages/js/components/src/calendar/date-range.js










function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
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












// ^^ The above: Turn on react-dates classes/styles, see https://github.com/airbnb/react-dates#initialize.

/**
 * Internal dependencies
 */


var isRTL = function isRTL() {
  return document.documentElement.dir === 'rtl';
};
// Blur event sources
var CONTAINER_DIV = 'container';
var NEXT_MONTH_CLICK = 'onNextMonthClick';
var PREV_MONTH_CLICK = 'onPrevMonthClick';

/**
 * This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.
 */
var DateRange = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(DateRange, _Component);
  var _super = _createSuper(DateRange);
  function DateRange(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, DateRange);
    _this = _super.call(this, props);
    _this.onDatesChange = _this.onDatesChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onFocusChange = _this.onFocusChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onInputChange = _this.onInputChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.nodeRef = (0,react.createRef)();
    _this.keepFocusInside = _this.keepFocusInside.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }

  /*
   * Todo: We should remove this function when possible.
   * It is kept because focus is lost when we click on the previous and next
   * month buttons or clicking on a date in the calendar.
   * This focus loss closes the date picker popover.
   * Ideally we should add an upstream commit on react-dates to fix this issue.
   *
   * See: https://github.com/WordPress/gutenberg/pull/17201.
   */
  (0,createClass/* default */.A)(DateRange, [{
    key: "keepFocusInside",
    value: function keepFocusInside(blurSource, e) {
      if (!this.nodeRef.current) {
        return;
      }
      var losesFocusTo = this.props.losesFocusTo;

      // Blur triggered internal to the DayPicker component.
      if (CONTAINER_DIV === blurSource && e.target && (e.target.classList.contains('DayPickerNavigation_button') || e.target.classList.contains('CalendarDay')) && (
      // Allow other DayPicker elements to take focus.
      !e.relatedTarget || !e.relatedTarget.classList.contains('DayPickerNavigation_button') && !e.relatedTarget.classList.contains('CalendarDay'))) {
        // Allow other DayPicker elements to take focus.
        if (e.relatedTarget && (e.relatedTarget.classList.contains('DayPickerNavigation_button') || e.relatedTarget.classList.contains('CalendarDay'))) {
          return;
        }

        // Allow elements inside a specified ref to take focus.
        if (e.relatedTarget && losesFocusTo && losesFocusTo.contains(e.relatedTarget)) {
          return;
        }

        // DayPickerNavigation or CalendarDay mouseUp() is blurring,
        // so switch focus to the DayPicker's focus region.
        var focusRegion = this.nodeRef.current.querySelector('.DayPicker_focusRegion');
        if (focusRegion) {
          focusRegion.focus();
        }
        return;
      }

      // Blur triggered after next/prev click callback props.
      if (PREV_MONTH_CLICK === blurSource || NEXT_MONTH_CLICK === blurSource) {
        // DayPicker's updateStateAfterMonthTransition() is about to blur
        // the activeElement, so focus a DayPickerNavigation button so the next
        // blur event gets fixed by the above logic path.
        var _focusRegion = this.nodeRef.current.querySelector('.DayPickerNavigation_button');
        if (_focusRegion) {
          _focusRegion.focus();
        }
      }
    }
  }, {
    key: "onDatesChange",
    value: function onDatesChange(_ref) {
      var startDate = _ref.startDate,
        endDate = _ref.endDate;
      var _this$props = this.props,
        onUpdate = _this$props.onUpdate,
        shortDateFormat = _this$props.shortDateFormat;
      onUpdate({
        after: startDate,
        before: endDate,
        afterText: startDate ? startDate.format(shortDateFormat) : '',
        beforeText: endDate ? endDate.format(shortDateFormat) : '',
        afterError: null,
        beforeError: null
      });
    }
  }, {
    key: "onFocusChange",
    value: function onFocusChange(focusedInput) {
      this.props.onUpdate({
        focusedInput: !focusedInput ? 'startDate' : focusedInput
      });
    }
  }, {
    key: "onInputChange",
    value: function onInputChange(input, event) {
      var value = event.target.value;
      var _this$props2 = this.props,
        after = _this$props2.after,
        before = _this$props2.before,
        shortDateFormat = _this$props2.shortDateFormat;
      var _validateDateInputFor = (0,src/* validateDateInputForRange */.t_)(input, value, before, after, shortDateFormat),
        date = _validateDateInputFor.date,
        error = _validateDateInputFor.error;
      this.props.onUpdate((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)({}, input, date), input + 'Text', value), input + 'Error', value.length > 0 ? error : null));
    }
  }, {
    key: "setTnitialVisibleMonth",
    value: function setTnitialVisibleMonth(isDoubleCalendar, before) {
      return function () {
        var visibleDate = before || moment_default()();
        if (isDoubleCalendar) {
          return visibleDate.clone().subtract(1, 'month');
        }
        return visibleDate;
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props3 = this.props,
        after = _this$props3.after,
        before = _this$props3.before,
        focusedInput = _this$props3.focusedInput,
        afterText = _this$props3.afterText,
        beforeText = _this$props3.beforeText,
        afterError = _this$props3.afterError,
        beforeError = _this$props3.beforeError,
        shortDateFormat = _this$props3.shortDateFormat,
        isViewportMobile = _this$props3.isViewportMobile,
        isViewportSmall = _this$props3.isViewportSmall,
        isInvalidDate = _this$props3.isInvalidDate;
      var isDoubleCalendar = isViewportMobile && !isViewportSmall;
      return (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-calendar', {
          'is-mobile': isViewportMobile
        })
      }, (0,react.createElement)("div", {
        className: "woocommerce-calendar__inputs"
      }, (0,react.createElement)(input/* default */.A, {
        value: afterText,
        onChange: (0,lodash.partial)(this.onInputChange, 'after'),
        dateFormat: shortDateFormat,
        label: (0,build_module.__)('Start Date', 'woocommerce'),
        error: afterError,
        describedBy: (0,build_module/* sprintf */.nv)( /* translators: %s: date format specification */
        (0,build_module.__)("Date input describing a selected date range's start date in format %s", 'woocommerce'), shortDateFormat),
        onFocus: function onFocus() {
          return _this2.onFocusChange('startDate');
        }
      }), (0,react.createElement)("div", {
        className: "woocommerce-calendar__inputs-to"
      }, (0,build_module.__)('to', 'woocommerce')), (0,react.createElement)(input/* default */.A, {
        value: beforeText,
        onChange: (0,lodash.partial)(this.onInputChange, 'before'),
        dateFormat: shortDateFormat,
        label: (0,build_module.__)('End Date', 'woocommerce'),
        error: beforeError,
        describedBy: (0,build_module/* sprintf */.nv)( /* translators: %s: date format specification */
        (0,build_module.__)("Date input describing a selected date range's end date in format %s", 'woocommerce'), shortDateFormat),
        onFocus: function onFocus() {
          return _this2.onFocusChange('endDate');
        }
      })), (0,react.createElement)("div", {
        className: "woocommerce-calendar__react-dates",
        ref: this.nodeRef,
        onBlur: (0,lodash.partial)(this.keepFocusInside, CONTAINER_DIV),
        tabIndex: -1
      }, (0,react.createElement)(react_dates.DayPickerRangeController, {
        onNextMonthClick: (0,lodash.partial)(this.keepFocusInside, NEXT_MONTH_CLICK),
        onPrevMonthClick: (0,lodash.partial)(this.keepFocusInside, PREV_MONTH_CLICK),
        onDatesChange: this.onDatesChange,
        onFocusChange: this.onFocusChange,
        focusedInput: focusedInput,
        startDate: after,
        endDate: before,
        orientation: 'horizontal',
        numberOfMonths: isDoubleCalendar ? 2 : 1,
        isOutsideRange: function isOutsideRange(date) {
          return isInvalidDate && isInvalidDate(date.toDate());
        },
        minimumNights: 0,
        hideKeyboardShortcutsPanel: true,
        noBorder: true,
        isRTL: isRTL(),
        initialVisibleMonth: this.setTnitialVisibleMonth(isDoubleCalendar, before),
        phrases: phrases
      })));
    }
  }]);
  return DateRange;
}(react.Component);
DateRange.propTypes = {
  /**
   * A moment date object representing the selected start. `null` for no selection.
   */
  after: (prop_types_default()).object,
  /**
   * A string error message, shown to the user.
   */
  afterError: (prop_types_default()).string,
  /**
   * The start date in human-readable format. Displayed in the text input.
   */
  afterText: (prop_types_default()).string,
  /**
   * A moment date object representing the selected end. `null` for no selection.
   */
  before: (prop_types_default()).object,
  /**
   * A string error message, shown to the user.
   */
  beforeError: (prop_types_default()).string,
  /**
   * The end date in human-readable format. Displayed in the text input.
   */
  beforeText: (prop_types_default()).string,
  /**
   * String identifying which is the currently focused input (start or end).
   */
  focusedInput: (prop_types_default()).string,
  /**
   * A function to determine if a day on the calendar is not valid
   */
  isInvalidDate: (prop_types_default()).func,
  /**
   * A function called upon selection of a date.
   */
  onUpdate: (prop_types_default()).func.isRequired,
  /**
   * The date format in moment.js-style tokens.
   */
  shortDateFormat: (prop_types_default()).string.isRequired,
  /**
   * A ref that the DateRange can lose focus to.
   * See: https://github.com/woocommerce/woocommerce-admin/pull/2929.
   */
  // eslint-disable-next-line no-undef
  losesFocusTo: prop_types_default().instanceOf(Element)
};
/* harmony default export */ const date_range = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium',
  isViewportSmall: '< small'
})(DateRange));

/***/ }),

/***/ "../../packages/js/components/src/compare-filter/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  S: () => (/* binding */ CompareFilter)
});

// UNUSED EXPORTS: CompareButton

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js
var inherits = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js + 7 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 3 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js + 1 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/compare-filter/button.js
/**
 * External dependencies
 */





/**
 * A button used when comparing items, if `count` is less than 2 a hoverable tooltip is added with `helpText`.
 *
 * @param {Object}   props
 * @param {string}   props.className
 * @param {number}   props.count
 * @param {Node}     props.children
 * @param {boolean}  props.disabled
 * @param {string}   props.helpText
 * @param {Function} props.onClick
 * @return {Object} -
 */
var CompareButton = function CompareButton(_ref) {
  var className = _ref.className,
    count = _ref.count,
    children = _ref.children,
    disabled = _ref.disabled,
    helpText = _ref.helpText,
    onClick = _ref.onClick;
  return !disabled && count < 2 ? (0,react.createElement)(tooltip/* default */.A, {
    text: helpText
  }, (0,react.createElement)("span", {
    className: className
  }, (0,react.createElement)(build_module_button/* default */.A, {
    className: "woocommerce-compare-button",
    disabled: true,
    isSecondary: true
  }, children))) : (0,react.createElement)(build_module_button/* default */.A, {
    className: classnames_default()('woocommerce-compare-button', className),
    onClick: onClick,
    disabled: disabled,
    isSecondary: true
  }, children);
};
CompareButton.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * The count of items selected.
   */
  count: (prop_types_default()).number.isRequired,
  /**
   * The button content.
   */
  children: (prop_types_default()).node.isRequired,
  /**
   * Text displayed when hovering over a disabled button.
   */
  helpText: (prop_types_default()).string.isRequired,
  /**
   * The function called when the button is clicked.
   */
  onClick: (prop_types_default()).func.isRequired,
  /**
   * Whether the control is disabled or not.
   */
  disabled: (prop_types_default()).bool
};
/* harmony default export */ const compare_filter_button = (CompareButton);
// EXTERNAL MODULE: ../../packages/js/components/src/search/index.tsx + 14 modules
var search = __webpack_require__("../../packages/js/components/src/search/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental.js
var experimental = __webpack_require__("../../packages/js/components/src/experimental.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/compare-filter/index.js













function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
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





/**
 * Displays a card + search used to filter results as a comparison between objects.
 */
var CompareFilter = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(CompareFilter, _Component);
  var _super = _createSuper(CompareFilter);
  function CompareFilter(_ref) {
    var _this;
    var getLabels = _ref.getLabels,
      param = _ref.param,
      query = _ref.query;
    (0,classCallCheck/* default */.A)(this, CompareFilter);
    _this = _super.apply(this, arguments);
    _this.state = {
      selected: []
    };
    _this.clearQuery = _this.clearQuery.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateQuery = _this.updateQuery.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateLabels = _this.updateLabels.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onButtonClicked = _this.onButtonClicked.bind((0,assertThisInitialized/* default */.A)(_this));
    if (query[param]) {
      getLabels(query[param], query).then(_this.updateLabels);
    }
    return _this;
  }
  (0,createClass/* default */.A)(CompareFilter, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(_ref2, _ref3) {
      var prevParam = _ref2.param,
        prevQuery = _ref2.query;
      var prevSelected = _ref3.selected;
      var _this$props = this.props,
        getLabels = _this$props.getLabels,
        param = _this$props.param,
        query = _this$props.query;
      var selected = this.state.selected;
      if (prevParam !== param || prevSelected.length > 0 && selected.length === 0) {
        this.clearQuery();
        return;
      }
      var prevIds = (0,src/* getIdsFromQuery */.DF)(prevQuery[param]);
      var currentIds = (0,src/* getIdsFromQuery */.DF)(query[param]);
      if (!(0,lodash.isEqual)(prevIds.sort(), currentIds.sort())) {
        getLabels(query[param], query).then(this.updateLabels);
      }
    }
  }, {
    key: "clearQuery",
    value: function clearQuery() {
      var _this$props2 = this.props,
        param = _this$props2.param,
        path = _this$props2.path,
        query = _this$props2.query;
      this.setState({
        selected: []
      });
      (0,src/* updateQueryString */.Ze)((0,defineProperty/* default */.A)({}, param, undefined), path, query);
    }
  }, {
    key: "updateLabels",
    value: function updateLabels(selected) {
      this.setState({
        selected: selected
      });
    }
  }, {
    key: "updateQuery",
    value: function updateQuery() {
      var _this$props3 = this.props,
        param = _this$props3.param,
        path = _this$props3.path,
        query = _this$props3.query;
      var selected = this.state.selected;
      var idList = selected.map(function (p) {
        return p.key;
      });
      (0,src/* updateQueryString */.Ze)((0,defineProperty/* default */.A)({}, param, idList.join(',')), path, query);
    }
  }, {
    key: "onButtonClicked",
    value: function onButtonClicked(e) {
      this.updateQuery(e);
      if ((0,lodash.isFunction)(this.props.onClick)) {
        this.props.onClick(e);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props4 = this.props,
        labels = _this$props4.labels,
        type = _this$props4.type,
        autocompleter = _this$props4.autocompleter;
      var selected = this.state.selected;
      return (0,react.createElement)(component/* default */.A, {
        className: "woocommerce-filters__compare"
      }, (0,react.createElement)(card_header_component/* default */.A, null, (0,react.createElement)(experimental/* Text */.E, {
        variant: "subtitle.small",
        weight: "600",
        size: "14",
        lineHeight: "20px"
      }, labels.title)), (0,react.createElement)(card_body_component/* default */.A, null, (0,react.createElement)(search/* default */.A, {
        autocompleter: autocompleter,
        type: type,
        selected: selected,
        placeholder: labels.placeholder,
        onChange: function onChange(value) {
          _this2.setState({
            selected: value
          });
        }
      })), (0,react.createElement)(card_footer_component/* default */.A, {
        justify: "flex-start"
      }, (0,react.createElement)(compare_filter_button, {
        count: selected.length,
        helpText: labels.helpText,
        onClick: this.onButtonClicked
      }, labels.update), selected.length > 0 && (0,react.createElement)(build_module_button/* default */.A, {
        isLink: true,
        onClick: this.clearQuery
      }, (0,build_module.__)('Clear all', 'woocommerce'))));
    }
  }]);
  return CompareFilter;
}(react.Component);
CompareFilter.propTypes = {
  /**
   * Function used to fetch object labels via an API request, returns a Promise.
   */
  getLabels: (prop_types_default()).func.isRequired,
  /**
   * Object of localized labels.
   */
  labels: prop_types_default().shape({
    /**
     * Label for the search placeholder.
     */
    placeholder: (prop_types_default()).string,
    /**
     * Label for the card title.
     */
    title: (prop_types_default()).string,
    /**
     * Label for button which updates the URL/report.
     */
    update: (prop_types_default()).string
  }),
  /**
   * The parameter to use in the querystring.
   */
  param: (prop_types_default()).string.isRequired,
  /**
   * The `path` parameter supplied by React-Router
   */
  path: (prop_types_default()).string.isRequired,
  /**
   * The query string represented in object form
   */
  query: (prop_types_default()).object,
  /**
   * Which type of autocompleter should be used in the Search
   */
  type: (prop_types_default()).string.isRequired,
  /**
   * The custom autocompleter to be forwarded to the `Search` component.
   */
  autocompleter: (prop_types_default()).object
};
CompareFilter.defaultProps = {
  labels: {},
  query: {}
};

/***/ }),

/***/ "../../packages/js/components/src/date-range-filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date_range_filter_picker)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js
var inherits = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js + 6 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tab-panel/index.js
var tab_panel = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tab-panel/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/segmented-selection/index.js
var segmented_selection = __webpack_require__("../../packages/js/components/src/segmented-selection/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/date-range-filter-picker/compare-periods.js







function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
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

var ComparePeriods = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(ComparePeriods, _Component);
  var _super = _createSuper(ComparePeriods);
  function ComparePeriods() {
    (0,classCallCheck/* default */.A)(this, ComparePeriods);
    return _super.apply(this, arguments);
  }
  (0,createClass/* default */.A)(ComparePeriods, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
        onSelect = _this$props.onSelect,
        compare = _this$props.compare;
      return (0,react.createElement)(segmented_selection/* default */.A, {
        options: src/* periods */.RE,
        selected: compare,
        onSelect: onSelect,
        name: "compare",
        legend: (0,build_module.__)('compare to', 'woocommerce')
      });
    }
  }]);
  return ComparePeriods;
}(react.Component);
ComparePeriods.propTypes = {
  onSelect: (prop_types_default()).func.isRequired,
  compare: (prop_types_default()).string
};
/* harmony default export */ const compare_periods = (ComparePeriods);
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/date-range.js + 1 modules
var date_range = __webpack_require__("../../packages/js/components/src/calendar/date-range.js");
// EXTERNAL MODULE: ../../packages/js/components/src/section/header.tsx
var header = __webpack_require__("../../packages/js/components/src/section/header.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/section/section.tsx
var section = __webpack_require__("../../packages/js/components/src/section/section.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/date-range-filter-picker/preset-periods.js







function preset_periods_createSuper(Derived) {
  var hasNativeReflectConstruct = preset_periods_isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function preset_periods_isNativeReflectConstruct() {
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

var PresetPeriods = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(PresetPeriods, _Component);
  var _super = preset_periods_createSuper(PresetPeriods);
  function PresetPeriods() {
    (0,classCallCheck/* default */.A)(this, PresetPeriods);
    return _super.apply(this, arguments);
  }
  (0,createClass/* default */.A)(PresetPeriods, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
        onSelect = _this$props.onSelect,
        period = _this$props.period;
      return (0,react.createElement)(segmented_selection/* default */.A, {
        options: (0,lodash.filter)(src/* presetValues */.Ad, function (preset) {
          return preset.value !== 'custom';
        }),
        selected: period,
        onSelect: onSelect,
        name: "period",
        legend: (0,build_module.__)('select a preset period', 'woocommerce')
      });
    }
  }]);
  return PresetPeriods;
}(react.Component);
PresetPeriods.propTypes = {
  onSelect: (prop_types_default()).func.isRequired,
  period: (prop_types_default()).string
};
/* harmony default export */ const preset_periods = (PresetPeriods);
;// CONCATENATED MODULE: ../../packages/js/components/src/date-range-filter-picker/content.js










function content_createSuper(Derived) {
  var hasNativeReflectConstruct = content_isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function content_isNativeReflectConstruct() {
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




var DatePickerContent = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(DatePickerContent, _Component);
  var _super = content_createSuper(DatePickerContent);
  function DatePickerContent() {
    var _this;
    (0,classCallCheck/* default */.A)(this, DatePickerContent);
    _this = _super.call(this);
    _this.onTabSelect = _this.onTabSelect.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.controlsRef = (0,react.createRef)();
    return _this;
  }
  (0,createClass/* default */.A)(DatePickerContent, [{
    key: "onTabSelect",
    value: function onTabSelect(tab) {
      var _this$props = this.props,
        onUpdate = _this$props.onUpdate,
        period = _this$props.period;

      /**
       * If the period is `custom` and the user switches tabs to view the presets,
       * then a preset should be selected. This logic selects the default, otherwise
       * `custom` value for period will result in no selection.
       */
      if (tab === 'period' && period === 'custom') {
        onUpdate({
          period: 'today'
        });
      }
    }
  }, {
    key: "isFutureDate",
    value: function isFutureDate(dateString) {
      return moment_default()().isBefore(moment_default()(dateString), 'day');
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props2 = this.props,
        period = _this$props2.period,
        compare = _this$props2.compare,
        after = _this$props2.after,
        before = _this$props2.before,
        onUpdate = _this$props2.onUpdate,
        onClose = _this$props2.onClose,
        onSelect = _this$props2.onSelect,
        isValidSelection = _this$props2.isValidSelection,
        resetCustomValues = _this$props2.resetCustomValues,
        focusedInput = _this$props2.focusedInput,
        afterText = _this$props2.afterText,
        beforeText = _this$props2.beforeText,
        afterError = _this$props2.afterError,
        beforeError = _this$props2.beforeError,
        shortDateFormat = _this$props2.shortDateFormat;
      return (0,react.createElement)("div", null, (0,react.createElement)(header.H, {
        className: "screen-reader-text",
        tabIndex: "0"
      }, (0,build_module.__)('Select date range and comparison', 'woocommerce')), (0,react.createElement)(section/* Section */.w, {
        component: false
      }, (0,react.createElement)(header.H, {
        className: "woocommerce-filters-date__text"
      }, (0,build_module.__)('select a date range', 'woocommerce')), (0,react.createElement)(tab_panel/* default */.A, {
        tabs: [{
          name: 'period',
          title: (0,build_module.__)('Presets', 'woocommerce'),
          className: 'woocommerce-filters-date__tab'
        }, {
          name: 'custom',
          title: (0,build_module.__)('Custom', 'woocommerce'),
          className: 'woocommerce-filters-date__tab'
        }],
        className: "woocommerce-filters-date__tabs",
        activeClass: "is-active",
        initialTabName: period === 'custom' ? 'custom' : 'period',
        onSelect: this.onTabSelect
      }, function (selected) {
        return (0,react.createElement)(react.Fragment, null, selected.name === 'period' && (0,react.createElement)(preset_periods, {
          onSelect: onUpdate,
          period: period
        }), selected.name === 'custom' && (0,react.createElement)(date_range/* default */.A, {
          after: after,
          before: before,
          onUpdate: onUpdate,
          isInvalidDate: _this2.isFutureDate,
          focusedInput: focusedInput,
          afterText: afterText,
          beforeText: beforeText,
          afterError: afterError,
          beforeError: beforeError,
          shortDateFormat: shortDateFormat,
          losesFocusTo: _this2.controlsRef.current
        }), (0,react.createElement)("div", {
          className: classnames_default()('woocommerce-filters-date__content-controls', {
            'is-custom': selected.name === 'custom'
          }),
          ref: _this2.controlsRef
        }, (0,react.createElement)(header.H, {
          className: "woocommerce-filters-date__text"
        }, (0,build_module.__)('compare to', 'woocommerce')), (0,react.createElement)(compare_periods, {
          onSelect: onUpdate,
          compare: compare
        }), (0,react.createElement)("div", {
          className: "woocommerce-filters-date__button-group"
        }, selected.name === 'custom' && (0,react.createElement)(build_module_button/* default */.A, {
          className: "woocommerce-filters-date__button",
          isSecondary: true,
          onClick: resetCustomValues,
          disabled: !(after || before)
        }, (0,build_module.__)('Reset', 'woocommerce')), isValidSelection(selected.name) ? (0,react.createElement)(build_module_button/* default */.A, {
          className: "woocommerce-filters-date__button",
          onClick: onSelect(selected.name, onClose),
          isPrimary: true
        }, (0,build_module.__)('Update', 'woocommerce')) : (0,react.createElement)(build_module_button/* default */.A, {
          className: "woocommerce-filters-date__button",
          isPrimary: true,
          disabled: true
        }, (0,build_module.__)('Update', 'woocommerce')))));
      })));
    }
  }]);
  return DatePickerContent;
}(react.Component);
DatePickerContent.propTypes = {
  period: (prop_types_default()).string.isRequired,
  compare: (prop_types_default()).string.isRequired,
  onUpdate: (prop_types_default()).func.isRequired,
  onClose: (prop_types_default()).func.isRequired,
  onSelect: (prop_types_default()).func.isRequired,
  resetCustomValues: (prop_types_default()).func.isRequired,
  focusedInput: (prop_types_default()).string,
  afterText: (prop_types_default()).string,
  beforeText: (prop_types_default()).string,
  afterError: (prop_types_default()).string,
  beforeError: (prop_types_default()).string,
  shortDateFormat: (prop_types_default()).string.isRequired
};
/* harmony default export */ const content = (DatePickerContent);
// EXTERNAL MODULE: ../../packages/js/components/src/dropdown-button/index.js
var dropdown_button = __webpack_require__("../../packages/js/components/src/dropdown-button/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/date-range-filter-picker/index.js










function date_range_filter_picker_createSuper(Derived) {
  var hasNativeReflectConstruct = date_range_filter_picker_isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function date_range_filter_picker_isNativeReflectConstruct() {
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


var shortDateFormat = (0,build_module.__)('MM/DD/YYYY', 'woocommerce');

/**
 * Select a range of dates or single dates.
 */
var DateRangeFilterPicker = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(DateRangeFilterPicker, _Component);
  var _super = date_range_filter_picker_createSuper(DateRangeFilterPicker);
  function DateRangeFilterPicker(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, DateRangeFilterPicker);
    _this = _super.call(this, props);
    _this.state = _this.getResetState();
    _this.update = _this.update.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onSelect = _this.onSelect.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.isValidSelection = _this.isValidSelection.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.resetCustomValues = _this.resetCustomValues.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(DateRangeFilterPicker, [{
    key: "formatDate",
    value: function formatDate(date, format) {
      if (date && date._isAMomentObject && typeof date.format === 'function') {
        return date.format(format);
      }
      return '';
    }
  }, {
    key: "getResetState",
    value: function getResetState() {
      var _this$props$dateQuery = this.props.dateQuery,
        period = _this$props$dateQuery.period,
        compare = _this$props$dateQuery.compare,
        before = _this$props$dateQuery.before,
        after = _this$props$dateQuery.after;
      return {
        period: period,
        compare: compare,
        before: before,
        after: after,
        focusedInput: 'startDate',
        afterText: this.formatDate(after, shortDateFormat),
        beforeText: this.formatDate(before, shortDateFormat),
        afterError: null,
        beforeError: null
      };
    }
  }, {
    key: "update",
    value: function update(_update) {
      this.setState(_update);
    }
  }, {
    key: "onSelect",
    value: function onSelect(selectedTab, onClose) {
      var _this2 = this;
      var _this$props = this.props,
        isoDateFormat = _this$props.isoDateFormat,
        onRangeSelect = _this$props.onRangeSelect;
      return function (event) {
        var _this2$state = _this2.state,
          period = _this2$state.period,
          compare = _this2$state.compare,
          after = _this2$state.after,
          before = _this2$state.before;
        var data = {
          period: selectedTab === 'custom' ? 'custom' : period,
          compare: compare
        };
        if (selectedTab === 'custom') {
          data.after = _this2.formatDate(after, isoDateFormat);
          data.before = _this2.formatDate(before, isoDateFormat);
        } else {
          data.after = undefined;
          data.before = undefined;
        }
        onRangeSelect(data);
        onClose(event);
      };
    }
  }, {
    key: "getButtonLabel",
    value: function getButtonLabel() {
      var _this$props$dateQuery2 = this.props.dateQuery,
        primaryDate = _this$props$dateQuery2.primaryDate,
        secondaryDate = _this$props$dateQuery2.secondaryDate;
      return ["".concat(primaryDate.label, " (").concat(primaryDate.range, ")"), "".concat((0,build_module.__)('vs.', 'woocommerce'), " ").concat(secondaryDate.label, " (").concat(secondaryDate.range, ")")];
    }
  }, {
    key: "isValidSelection",
    value: function isValidSelection(selectedTab) {
      var _this$state = this.state,
        compare = _this$state.compare,
        after = _this$state.after,
        before = _this$state.before;
      if (selectedTab === 'custom') {
        return compare && after && before;
      }
      return true;
    }
  }, {
    key: "resetCustomValues",
    value: function resetCustomValues() {
      this.setState({
        after: null,
        before: null,
        focusedInput: 'startDate',
        afterText: '',
        beforeText: '',
        afterError: null,
        beforeError: null
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;
      var _this$state2 = this.state,
        period = _this$state2.period,
        compare = _this$state2.compare,
        after = _this$state2.after,
        before = _this$state2.before,
        focusedInput = _this$state2.focusedInput,
        afterText = _this$state2.afterText,
        beforeText = _this$state2.beforeText,
        afterError = _this$state2.afterError,
        beforeError = _this$state2.beforeError;
      var _this$props2 = this.props,
        isViewportMobile = _this$props2.isViewportMobile,
        _this$props2$focusOnM = _this$props2.focusOnMount,
        focusOnMount = _this$props2$focusOnM === void 0 ? true : _this$props2$focusOnM,
        _this$props2$popoverP = _this$props2.popoverProps,
        popoverProps = _this$props2$popoverP === void 0 ? {
          inline: true
        } : _this$props2$popoverP;
      if (!popoverProps.placement) {
        popoverProps.placement = 'bottom';
      }
      var contentClasses = classnames_default()('woocommerce-filters-date__content', {
        'is-mobile': isViewportMobile
      });
      return (0,react.createElement)("div", {
        className: "woocommerce-filters-filter"
      }, (0,react.createElement)("span", {
        className: "woocommerce-filters-label"
      }, (0,build_module.__)('Date range', 'woocommerce'), ":"), (0,react.createElement)(dropdown/* default */.A, {
        contentClassName: contentClasses,
        expandOnMobile: true,
        focusOnMount: focusOnMount,
        popoverProps: popoverProps,
        renderToggle: function renderToggle(_ref) {
          var isOpen = _ref.isOpen,
            onToggle = _ref.onToggle;
          return (0,react.createElement)(dropdown_button/* default */.A, {
            onClick: onToggle,
            isOpen: isOpen,
            labels: _this3.getButtonLabel()
          });
        },
        renderContent: function renderContent(_ref2) {
          var onClose = _ref2.onClose;
          return (0,react.createElement)(content, {
            period: period,
            compare: compare,
            after: after,
            before: before,
            onUpdate: _this3.update,
            onClose: onClose,
            onSelect: _this3.onSelect,
            isValidSelection: _this3.isValidSelection,
            resetCustomValues: _this3.resetCustomValues,
            focusedInput: focusedInput,
            afterText: afterText,
            beforeText: beforeText,
            afterError: afterError,
            beforeError: beforeError,
            shortDateFormat: shortDateFormat
          });
        }
      }));
    }
  }]);
  return DateRangeFilterPicker;
}(react.Component);
DateRangeFilterPicker.propTypes = {
  /**
   * Callback called when selection is made.
   */
  onRangeSelect: (prop_types_default()).func.isRequired,
  /**
   * The date query string represented in object form.
   */
  dateQuery: prop_types_default().shape({
    period: (prop_types_default()).string.isRequired,
    compare: (prop_types_default()).string.isRequired,
    before: (prop_types_default()).object,
    after: (prop_types_default()).object,
    primaryDate: prop_types_default().shape({
      label: (prop_types_default()).string.isRequired,
      range: (prop_types_default()).string.isRequired
    }).isRequired,
    secondaryDate: prop_types_default().shape({
      label: (prop_types_default()).string.isRequired,
      range: (prop_types_default()).string.isRequired
    }).isRequired
  }).isRequired
};
/* harmony default export */ const date_range_filter_picker = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium'
})(DateRangeFilterPicker));

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

/***/ "../../packages/js/components/src/segmented-selection/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_7__);










function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)(this, result);
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
 * Create a panel of styled selectable options rendering stylized checkboxes and labels
 */
var SegmentedSelection = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)(SegmentedSelection, _Component);
  var _super = _createSuper(SegmentedSelection);
  function SegmentedSelection() {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)(this, SegmentedSelection);
    return _super.apply(this, arguments);
  }
  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(SegmentedSelection, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
        className = _this$props.className,
        options = _this$props.options,
        selected = _this$props.selected,
        onSelect = _this$props.onSelect,
        name = _this$props.name,
        legend = _this$props.legend;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.createElement)("fieldset", {
        className: "woocommerce-segmented-selection"
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.createElement)("legend", {
        className: "screen-reader-text"
      }, legend), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.createElement)("div", {
        className: classnames__WEBPACK_IMPORTED_MODULE_6___default()(className, 'woocommerce-segmented-selection__container')
      }, options.map(function (_ref) {
        var value = _ref.value,
          label = _ref.label;
        if (!value || !label) {
          return null;
        }
        var id = (0,lodash__WEBPACK_IMPORTED_MODULE_7__.uniqueId)("".concat(value, "_"));
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.createElement)("div", {
          className: "woocommerce-segmented-selection__item",
          key: value
        }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.createElement)("input", {
          className: "woocommerce-segmented-selection__input",
          type: "radio",
          name: name,
          id: id,
          checked: selected === value,
          onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_7__.partial)(onSelect, (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A)({}, name, value))
        }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.createElement)("label", {
          htmlFor: id
        }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.createElement)("span", {
          className: "woocommerce-segmented-selection__label"
        }, label)));
      })));
    }
  }]);
  return SegmentedSelection;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_11__.Component);
SegmentedSelection.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * An Array of options to render. The array needs to be composed of objects with properties `label` and `value`.
   */
  options: prop_types__WEBPACK_IMPORTED_MODULE_13___default().arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_13___default().shape({
    value: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string).isRequired,
    label: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string).isRequired
  })).isRequired,
  /**
   * Value of selected item.
   */
  selected: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * Callback to be executed after selection
   */
  onSelect: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().func).isRequired,
  /**
   * This will be the key in the key and value arguments supplied to `onSelect`.
   */
  name: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string).isRequired,
  /**
   * Create a legend visible to screen readers.
   */
  legend: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string).isRequired
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SegmentedSelection);

/***/ }),

/***/ "../../packages/js/components/src/filters/stories/filters.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Examples: () => (/* binding */ Examples),
  "default": () => (/* binding */ filters_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../packages/js/components/src/section/header.tsx
var header = __webpack_require__("../../packages/js/components/src/section/header.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/section/section.tsx
var section = __webpack_require__("../../packages/js/components/src/section/section.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js
var inherits = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 3 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var date_src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/currency/src/index.ts + 3 modules
var currency_src = __webpack_require__("../../packages/js/currency/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/advanced-filters/index.js + 7 modules
var advanced_filters = __webpack_require__("../../packages/js/components/src/advanced-filters/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/compare-filter/index.js + 1 modules
var compare_filter = __webpack_require__("../../packages/js/components/src/compare-filter/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/date-range-filter-picker/index.js + 3 modules
var date_range_filter_picker = __webpack_require__("../../packages/js/components/src/date-range-filter-picker/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/filter-picker/index.js
var filter_picker = __webpack_require__("../../packages/js/components/src/filter-picker/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/filters/index.js












function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
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






/**
 * Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
 * or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.
 *
 * @return {Object} -
 */
var ReportFilters = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(ReportFilters, _Component);
  var _super = _createSuper(ReportFilters);
  function ReportFilters() {
    var _this;
    (0,classCallCheck/* default */.A)(this, ReportFilters);
    _this = _super.call(this);
    _this.renderCard = _this.renderCard.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onRangeSelect = _this.onRangeSelect.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(ReportFilters, [{
    key: "renderCard",
    value: function renderCard(config) {
      var _this$props = this.props,
        siteLocale = _this$props.siteLocale,
        advancedFilters = _this$props.advancedFilters,
        query = _this$props.query,
        path = _this$props.path,
        onAdvancedFilterAction = _this$props.onAdvancedFilterAction,
        currency = _this$props.currency;
      var filters = config.filters,
        param = config.param;
      if (!query[param]) {
        return null;
      }
      if (query[param].indexOf('compare') === 0) {
        var filter = (0,lodash.find)(filters, {
          value: query[param]
        });
        if (!filter) {
          return null;
        }
        var _filter$settings = filter.settings,
          settings = _filter$settings === void 0 ? {} : _filter$settings;
        return (0,react.createElement)("div", {
          key: param,
          className: "woocommerce-filters__advanced-filters"
        }, (0,react.createElement)(compare_filter/* CompareFilter */.S, (0,esm_extends/* default */.A)({
          path: path,
          query: query
        }, settings)));
      }
      if (query[param] === 'advanced') {
        return (0,react.createElement)("div", {
          key: param,
          className: "woocommerce-filters__advanced-filters"
        }, (0,react.createElement)(advanced_filters/* default */.A, {
          siteLocale: siteLocale,
          currency: currency,
          config: advancedFilters,
          path: path,
          query: query,
          onAdvancedFilterAction: onAdvancedFilterAction
        }));
      }
    }
  }, {
    key: "onRangeSelect",
    value: function onRangeSelect(data) {
      var _this$props2 = this.props,
        query = _this$props2.query,
        path = _this$props2.path,
        onDateSelect = _this$props2.onDateSelect;
      (0,src/* updateQueryString */.Ze)(data, path, query);
      onDateSelect(data);
    }
  }, {
    key: "getDateQuery",
    value: function getDateQuery(query) {
      var _getDateParamsFromQue = (0,date_src/* getDateParamsFromQuery */.vW)(query),
        period = _getDateParamsFromQue.period,
        compare = _getDateParamsFromQue.compare,
        before = _getDateParamsFromQue.before,
        after = _getDateParamsFromQue.after;
      var _getCurrentDates = (0,date_src/* getCurrentDates */.lI)(query),
        primaryDate = _getCurrentDates.primary,
        secondaryDate = _getCurrentDates.secondary;
      return {
        period: period,
        compare: compare,
        before: before,
        after: after,
        primaryDate: primaryDate,
        secondaryDate: secondaryDate
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
        dateQuery = _this$props3.dateQuery,
        filters = _this$props3.filters,
        query = _this$props3.query,
        path = _this$props3.path,
        showDatePicker = _this$props3.showDatePicker,
        onFilterSelect = _this$props3.onFilterSelect,
        isoDateFormat = _this$props3.isoDateFormat,
        advancedFilters = _this$props3.advancedFilters;
      return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(header.H, {
        className: "screen-reader-text"
      }, (0,build_module.__)('Filters', 'woocommerce')), (0,react.createElement)(section/* Section */.w, {
        component: "div",
        className: "woocommerce-filters"
      }, (0,react.createElement)("div", {
        className: "woocommerce-filters__basic-filters"
      }, showDatePicker && (0,react.createElement)(date_range_filter_picker/* default */.A, {
        key: JSON.stringify(query),
        dateQuery: dateQuery || this.getDateQuery(query),
        onRangeSelect: this.onRangeSelect,
        isoDateFormat: isoDateFormat
      }), filters.map(function (config) {
        if (config.showFilters(query)) {
          return (0,react.createElement)(filter_picker/* default */.A, {
            key: config.param,
            config: config,
            advancedFilters: advancedFilters,
            query: query,
            path: path,
            onFilterSelect: onFilterSelect
          });
        }
        return null;
      })), filters.map(this.renderCard)));
    }
  }]);
  return ReportFilters;
}(react.Component);
ReportFilters.propTypes = {
  /**
   * The locale of the site (passed through to `AdvancedFilters`)
   */
  siteLocale: (prop_types_default()).string,
  /**
   * Config option passed through to `AdvancedFilters`
   */
  advancedFilters: (prop_types_default()).object,
  /**
   * Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
   */
  filters: (prop_types_default()).array,
  /**
   * The `path` parameter supplied by React-Router
   */
  path: (prop_types_default()).string.isRequired,
  /**
   * The query string represented in object form
   */
  query: (prop_types_default()).object,
  /**
   * Whether the date picker must be shown.
   */
  showDatePicker: (prop_types_default()).bool,
  /**
   * Function to be called after date selection.
   */
  onDateSelect: (prop_types_default()).func,
  /**
   * Function to be called after filter selection.
   */
  onFilterSelect: (prop_types_default()).func,
  /**
   * Function to be called after an advanced filter action has been taken.
   */
  onAdvancedFilterAction: (prop_types_default()).func,
  /**
   * The currency formatting instance for the site.
   */
  currency: (prop_types_default()).object,
  /**
   * The date query string represented in object form.
   */
  dateQuery: prop_types_default().shape({
    period: (prop_types_default()).string.isRequired,
    compare: (prop_types_default()).string.isRequired,
    before: (prop_types_default()).object,
    after: (prop_types_default()).object,
    primaryDate: prop_types_default().shape({
      label: (prop_types_default()).string.isRequired,
      range: (prop_types_default()).string.isRequired
    }).isRequired,
    secondaryDate: prop_types_default().shape({
      label: (prop_types_default()).string.isRequired,
      range: (prop_types_default()).string.isRequired
    })
  }),
  /**
   * ISO date format string.
   */
  isoDateFormat: (prop_types_default()).string
};
ReportFilters.defaultProps = {
  siteLocale: 'en_US',
  advancedFilters: {
    title: '',
    filters: {}
  },
  filters: [],
  query: {},
  showDatePicker: true,
  onDateSelect: function onDateSelect() {},
  currency: (0,currency_src/* CurrencyFactory */.uU)().getCurrencyConfig()
};
/* harmony default export */ const filters = (ReportFilters);
;// CONCATENATED MODULE: ../../packages/js/components/src/filters/stories/filters.story.js






/**
 * External dependencies
 */



var ORDER_STATUSES = {
  cancelled: 'Cancelled',
  completed: 'Completed',
  failed: 'Failed',
  'on-hold': 'On hold',
  pending: 'Pending payment',
  processing: 'Processing',
  refunded: 'Refunded'
};
var CURRENCY = {
  code: 'USD',
  decimalSeparator: '.',
  precision: 2,
  priceFormat: '%1$s%2$s',
  symbol: '$',
  symbolPosition: 'left',
  thousandSeparator: ','
};

// Fetch store default date range and compose with date utility functions.
var defaultDateRange = 'period=month&compare=previous_year';
var storeGetDateParamsFromQuery = (0,lodash.partialRight)(date_src/* getDateParamsFromQuery */.vW, defaultDateRange);
var storeGetCurrentDates = (0,lodash.partialRight)(date_src/* getCurrentDates */.lI, defaultDateRange);

// Package date utilities for filter picker component.
var storeDate = {
  getDateParamsFromQuery: storeGetDateParamsFromQuery,
  getCurrentDates: storeGetCurrentDates,
  isoDateFormat: date_src/* isoDateFormat */.r3
};
var siteLocale = 'en_US';
var path = '';
var query = {};
var filters_story_filters = [{
  label: 'Show',
  staticParams: ['chart'],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: 'All orders',
    value: 'all'
  }, {
    label: 'Advanced filters',
    value: 'advanced'
  }]
}];
var advancedFilters = {
  title: 'Orders Match <select/> Filters',
  filters: {
    status: {
      labels: {
        add: 'Order Status',
        remove: 'Remove order status filter',
        rule: 'Select an order status filter match',
        title: 'Order Status <rule/> <filter/>',
        filter: 'Select an order status'
      },
      rules: [{
        value: 'is',
        label: 'Is'
      }, {
        value: 'is_not',
        label: 'Is Not'
      }],
      input: {
        component: 'SelectControl',
        options: Object.keys(ORDER_STATUSES).map(function (key) {
          return {
            value: key,
            label: ORDER_STATUSES[key]
          };
        })
      }
    },
    product: {
      labels: {
        add: 'Products',
        placeholder: 'Search products',
        remove: 'Remove products filter',
        rule: 'Select a product filter match',
        title: 'Product <rule/> <filter/>',
        filter: 'Select products'
      },
      rules: [{
        value: 'includes',
        label: 'Includes'
      }, {
        value: 'excludes',
        label: 'Excludes'
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: function getLabels() {
          return Promise.resolve([]);
        }
      }
    },
    customer: {
      labels: {
        add: 'Customer type',
        remove: 'Remove customer filter',
        rule: 'Select a customer filter match',
        title: 'Customer is <filter/>',
        filter: 'Select a customer type'
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'new',
          label: 'New'
        }, {
          value: 'returning',
          label: 'Returning'
        }],
        defaultOption: 'new'
      }
    },
    quantity: {
      labels: {
        add: 'Item Quantity',
        remove: 'Remove item quantity filter',
        rule: 'Select an item quantity filter match',
        title: 'Item Quantity is <rule/> <filter/>'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number'
      }
    },
    subtotal: {
      labels: {
        add: 'Subtotal',
        remove: 'Remove subtotal filter',
        rule: 'Select a subtotal filter match',
        title: 'Subtotal is <rule/> <filter/>'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number',
        type: 'currency'
      }
    }
  }
};
var compareFilter = {
  type: 'products',
  param: 'product',
  getLabels: function getLabels() {
    return Promise.resolve([]);
  },
  labels: {
    helpText: 'Select at least two products to compare',
    placeholder: 'Search for products to compare',
    title: 'Compare Products',
    update: 'Compare'
  }
};
var Examples = function Examples() {
  return (0,react.createElement)("div", null, (0,react.createElement)(header.H, null, "Date picker only"), (0,react.createElement)(section/* Section */.w, {
    component: false
  }, (0,react.createElement)(filters, {
    path: path,
    query: query,
    storeDate: storeDate
  })), (0,react.createElement)(header.H, null, "Date picker & more filters"), (0,react.createElement)(section/* Section */.w, {
    component: false
  }, (0,react.createElement)(filters, {
    filters: filters_story_filters,
    path: path,
    query: query,
    storeDate: storeDate
  })), (0,react.createElement)(header.H, null, "Advanced filters"), (0,react.createElement)(section/* Section */.w, {
    component: false
  }, (0,react.createElement)(advanced_filters/* default */.A, {
    siteLocale: siteLocale,
    path: path,
    query: query,
    filterTitle: "Orders",
    config: advancedFilters,
    currency: CURRENCY
  })), (0,react.createElement)(header.H, null, "Compare Filter"), (0,react.createElement)(section/* Section */.w, {
    component: false
  }, (0,react.createElement)(compare_filter/* CompareFilter */.S, (0,esm_extends/* default */.A)({
    path: path,
    query: query
  }, compareFilter))));
};
/* harmony default export */ const filters_story = ({
  title: 'WooCommerce Admin/components/ReportFilters',
  component: filters
});
Examples.parameters = {
  ...Examples.parameters,
  docs: {
    ...Examples.parameters?.docs,
    source: {
      originalSource: "() => <div>\n        <H>Date picker only</H>\n        <Section component={false}>\n            <ReportFilters path={path} query={query} storeDate={storeDate} />\n        </Section>\n\n        <H>Date picker & more filters</H>\n        <Section component={false}>\n            <ReportFilters filters={filters} path={path} query={query} storeDate={storeDate} />\n        </Section>\n\n        <H>Advanced filters</H>\n        <Section component={false}>\n            <AdvancedFilters siteLocale={siteLocale} path={path} query={query} filterTitle=\"Orders\" config={advancedFilters} currency={CURRENCY} />\n        </Section>\n\n        <H>Compare Filter</H>\n        <Section component={false}>\n            <CompareFilter path={path} query={query} {...compareFilter} />\n        </Section>\n    </div>",
      ...Examples.parameters?.docs?.source
    }
  }
};

/***/ })

}]);