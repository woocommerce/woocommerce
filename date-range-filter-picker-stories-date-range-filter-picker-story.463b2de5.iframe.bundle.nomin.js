(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9416],{

/***/ "../../packages/js/components/src/calendar/date-range.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

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

/***/ "../../packages/js/components/src/calendar/input.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/calendar.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_6__);
/**
 * External dependencies
 */






var DateInput = function DateInput(_ref) {
  var _ref$disabled = _ref.disabled,
    disabled = _ref$disabled === void 0 ? false : _ref$disabled,
    value = _ref.value,
    onChange = _ref.onChange,
    dateFormat = _ref.dateFormat,
    label = _ref.label,
    describedBy = _ref.describedBy,
    error = _ref.error,
    _ref$onFocus = _ref.onFocus,
    onFocus = _ref$onFocus === void 0 ? function () {} : _ref$onFocus,
    _ref$onBlur = _ref.onBlur,
    onBlur = _ref$onBlur === void 0 ? function () {} : _ref$onBlur,
    _ref$onKeyDown = _ref.onKeyDown,
    onKeyDown = _ref$onKeyDown === void 0 ? lodash__WEBPACK_IMPORTED_MODULE_1__.noop : _ref$onKeyDown,
    _ref$errorPosition = _ref.errorPosition,
    errorPosition = _ref$errorPosition === void 0 ? 'bottom center' : _ref$errorPosition;
  var classes = classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-calendar__input', {
    'is-empty': value.length === 0,
    'is-error': error
  });
  var id = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.uniqueId)('_woo-dates-input');
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    className: classes
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("input", {
    type: "text",
    className: "woocommerce-calendar__input-text",
    value: value,
    onChange: onChange,
    "aria-label": label,
    id: id,
    "aria-describedby": "".concat(id, "-message"),
    placeholder: dateFormat.toLowerCase(),
    onFocus: onFocus,
    onBlur: onBlur,
    onKeyDown: onKeyDown,
    disabled: disabled
  }), error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
    className: "woocommerce-calendar__input-error",
    focusOnMount: false,
    position: errorPosition
  }, error), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A,
    className: "calendar-icon"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("p", {
    className: "screen-reader-text",
    id: "".concat(id, "-message")
  }, error || describedBy));
};
DateInput.propTypes = {
  disabled: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().bool),
  value: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string),
  onChange: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func).isRequired,
  dateFormat: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string).isRequired,
  label: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string).isRequired,
  describedBy: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string).isRequired,
  error: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string),
  errorPosition: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string),
  onFocus: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func),
  onBlur: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func),
  onKeyDown: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DateInput);

/***/ }),

/***/ "../../packages/js/components/src/date-range-filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

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

"use strict";
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

/***/ "../../packages/js/components/src/segmented-selection/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "../../packages/js/components/src/section/context.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $: () => (/* binding */ Level)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */


/**
 * Context container for heading level. We start at 2 because the `h1` is defined in <Header />
 *
 * See https://medium.com/@Heydon/managing-heading-levels-in-design-systems-18be9a746fa3
 */
var Level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(2);


/***/ }),

/***/ "../../packages/js/components/src/section/header.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H: () => (/* binding */ H)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
 * (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
 * heading level.
 *
 * @type {HTMLElement}
 */
function H(props) {
  var level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$);
  var Heading = 'h' + Math.min(level, 6);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Heading, props);
}
try {
    // @ts-ignore
    H.displayName = "H";
    // @ts-ignore
    H.__docgenInfo = { "description": "These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels\n(`h2`, `h3`, \u2026) you can use `<H />` to create \"section headings\", which look to the parent `<Section />`s for the appropriate\nheading level.", "displayName": "H", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/header.tsx#H"] = { docgenInfo: H.__docgenInfo, name: "H", path: "../../packages/js/components/src/section/header.tsx#H" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/section.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   w: () => (/* binding */ Section)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");

var _excluded = ["component", "children"];

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 */
var Section = function Section(_ref) {
  var component = _ref.component,
    children = _ref.children,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(_ref, _excluded);
  var Component = component || 'div';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$.Consumer, null, function (level) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$.Provider, {
      value: level + 1
    }, component === false ? children : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(Component, props, children));
  });
};
try {
    // @ts-ignore
    Section.displayName = "Section";
    // @ts-ignore
    Section.__docgenInfo = { "description": "The section wrapper, used to indicate a sub-section (and change the header level context).", "displayName": "Section", "props": { "component": { "defaultValue": null, "description": "The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component.", "name": "component", "required": false, "type": { "name": "string | false | ComponentType<{}>" } }, "className": { "defaultValue": null, "description": "Optional classname", "name": "className", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "The children inside this section, rendered in the `component`. This increases the context level for the next heading used.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/section.tsx#Section"] = { docgenInfo: Section.__docgenInfo, name: "Section", path: "../../packages/js/components/src/section/section.tsx#Section" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/date-range-filter-picker/stories/date-range-filter-picker.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../packages/js/components/src/date-range-filter-picker/index.js");
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/date/src/index.ts");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);

/**
 * External dependencies
 */



/**
 * External dependencies
 */

var query = {};
var defaultDateRange = 'period=month&compare=previous_year';
var storeGetDateParamsFromQuery = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partialRight)(_woocommerce_date__WEBPACK_IMPORTED_MODULE_0__/* .getDateParamsFromQuery */ .vW, defaultDateRange);
var storeGetCurrentDates = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partialRight)(_woocommerce_date__WEBPACK_IMPORTED_MODULE_0__/* .getCurrentDates */ .lI, defaultDateRange);
var _storeGetDateParamsFr = storeGetDateParamsFromQuery(query),
  period = _storeGetDateParamsFr.period,
  compare = _storeGetDateParamsFr.compare,
  before = _storeGetDateParamsFr.before,
  after = _storeGetDateParamsFr.after;
var _storeGetCurrentDates = storeGetCurrentDates(query),
  primaryDate = _storeGetCurrentDates.primary,
  secondaryDate = _storeGetCurrentDates.secondary;
var dateQuery = {
  period: period,
  compare: compare,
  before: before,
  after: after,
  primaryDate: primaryDate,
  secondaryDate: secondaryDate
};
var Basic = function Basic() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
    key: "daterange",
    query: query,
    onRangeSelect: function onRangeSelect() {},
    dateQuery: dateQuery,
    isoDateFormat: _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__/* .isoDateFormat */ .r3
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/components/DateRangeFilterPicker',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <DateRangeFilterPicker key=\"daterange\" query={query} onRangeSelect={() => {}} dateQuery={dateQuery} isoDateFormat={isoDateFormat} />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "?bbf9":
/***/ (() => {

/* (ignored) */

/***/ })

}]);