(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3426],{

/***/ "../../packages/js/components/src/calendar/stories/date-range.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../packages/js/components/src/section/header.tsx");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../packages/js/components/src/section/section.tsx");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../packages/js/components/src/calendar/date-range.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const dateFormat = 'MM/DD/YYYY';
const DateRangeExample = () => {
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)({
    after: null,
    afterText: '',
    before: null,
    beforeText: '',
    afterError: null,
    beforeError: null,
    focusedInput: 'startDate'
  });
  const {
    after,
    afterText,
    before,
    beforeText,
    focusedInput
  } = state;
  function onRangeUpdate(update) {
    setState({
      ...state,
      ...update
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__.H, {
      children: "Date range picker"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_4__/* .Section */ .w, {
      component: false,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
        after: after,
        afterText: afterText,
        before: before,
        beforeText: beforeText,
        onUpdate: onRangeUpdate,
        shortDateFormat: dateFormat,
        focusedInput: focusedInput,
        isInvalidDate: date => moment__WEBPACK_IMPORTED_MODULE_0___default()().isBefore(moment__WEBPACK_IMPORTED_MODULE_0___default()(date), 'date')
      })
    })]
  });
};
const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(DateRangeExample, {});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/calendar/DateRange',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <DateRangeExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ createHigherOrderComponent)
/* harmony export */ });
/* harmony import */ var change_case__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js");
// packages/compose/src/utils/create-higher-order-component/index.ts

function createHigherOrderComponent(mapComponent, modifierName) {
  return (Inner) => {
    const Outer = mapComponent(Inner);
    Outer.displayName = hocName(modifierName, Inner);
    return Outer;
  };
}
var hocName = (name, Inner) => {
  const inner = Inner.displayName || Inner.name || "Component";
  const outer = (0,change_case__WEBPACK_IMPORTED_MODULE_0__/* .pascalCase */ .fL)(name ?? "");
  return `${outer}(${inner})`;
};

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/debounce/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   s: () => (/* binding */ debounce)
/* harmony export */ });
// packages/compose/src/utils/debounce/index.ts
var debounce = (func, wait, options) => {
  let lastArgs;
  let lastThis;
  let maxWait = 0;
  let result;
  let timerId;
  let lastCallTime;
  let lastInvokeTime = 0;
  let leading = false;
  let maxing = false;
  let trailing = true;
  if (options) {
    leading = !!options.leading;
    maxing = "maxWait" in options;
    if (options.maxWait !== void 0) {
      maxWait = Math.max(options.maxWait, wait);
    }
    trailing = "trailing" in options ? !!options.trailing : trailing;
  }
  function invokeFunc(time) {
    const args = lastArgs;
    const thisArg = lastThis;
    lastArgs = void 0;
    lastThis = void 0;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }
  function startTimer(pendingFunc, waitTime) {
    timerId = setTimeout(pendingFunc, waitTime);
  }
  function cancelTimer() {
    if (timerId !== void 0) {
      clearTimeout(timerId);
    }
  }
  function leadingEdge(time) {
    lastInvokeTime = time;
    startTimer(timerExpired, wait);
    return leading ? invokeFunc(time) : result;
  }
  function getTimeSinceLastCall(time) {
    return time - (lastCallTime || 0);
  }
  function remainingWait(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime;
    const timeWaiting = wait - timeSinceLastCall;
    return maxing ? Math.min(timeWaiting, maxWait - timeSinceLastInvoke) : timeWaiting;
  }
  function shouldInvoke(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime;
    return lastCallTime === void 0 || timeSinceLastCall >= wait || timeSinceLastCall < 0 || maxing && timeSinceLastInvoke >= maxWait;
  }
  function timerExpired() {
    const time = Date.now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    startTimer(timerExpired, remainingWait(time));
    return void 0;
  }
  function clearTimer() {
    timerId = void 0;
  }
  function trailingEdge(time) {
    clearTimer();
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = void 0;
    return result;
  }
  function cancel() {
    cancelTimer();
    lastInvokeTime = 0;
    clearTimer();
    lastArgs = lastCallTime = lastThis = void 0;
  }
  function flush() {
    return pending() ? trailingEdge(Date.now()) : result;
  }
  function pending() {
    return timerId !== void 0;
  }
  function debounced(...args) {
    const time = Date.now();
    const isInvoking = shouldInvoke(time);
    lastArgs = args;
    lastThis = this;
    lastCallTime = time;
    if (isInvoking) {
      if (!pending()) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        startTimer(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (!pending()) {
      startTimer(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  debounced.pending = pending;
  return debounced;
};

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var icon_default = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(
  ({ icon, size = 24, ...props }, ref) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
      width: size,
      height: size,
      ...props,
      ref
    });
  }
);

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/calendar.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ calendar_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var calendar_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z" }) });

//# sourceMappingURL=calendar.js.map


/***/ }),

/***/ "../../packages/js/components/src/calendar/date-range.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date_range)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/object/assign.js
var object_assign = __webpack_require__("../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/object/assign.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/array/from.js
var from = __webpack_require__("../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/array/from.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/index.js
var react_dates = __webpack_require__("../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js + 29 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/initialize.js
var initialize = __webpack_require__("../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/initialize.js");
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/input.js
var input = __webpack_require__("../../packages/js/components/src/calendar/input.js");
;// ../../packages/js/components/src/calendar/phrases.js
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
  chooseAvailableStartDate: ({
    date
  }) => /* translators: %s: start date */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('Select %s as a start date.', 'woocommerce'), date),
  chooseAvailableEndDate: ({
    date
  }) => /* translators: %s: end date */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('Select %s as an end date.', 'woocommerce'), date),
  chooseAvailableDate: ({
    date
  }) => date,
  dateIsUnavailable: ({
    date
  }) => /* translators: %s: unavailable date which was selected */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('%s is not selectable.', 'woocommerce'), date),
  dateIsSelected: ({
    date
  }) => /* translators: %s: selected date successfully */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('Selected. %s', 'woocommerce'), date)
});
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/calendar/date-range.js
/**
 * External dependencies
 */












// ^^ The above: Turn on react-dates classes/styles, see https://github.com/airbnb/react-dates#initialize.

/**
 * Internal dependencies
 */



const isRTL = () => document.documentElement.dir === 'rtl';
// Blur event sources
const CONTAINER_DIV = 'container';
const NEXT_MONTH_CLICK = 'onNextMonthClick';
const PREV_MONTH_CLICK = 'onPrevMonthClick';

/**
 * This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.
 */
class DateRange extends react.Component {
  constructor(props) {
    super(props);
    this.onDatesChange = this.onDatesChange.bind(this);
    this.onFocusChange = this.onFocusChange.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
    this.nodeRef = (0,react.createRef)();
    this.keepFocusInside = this.keepFocusInside.bind(this);
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
  keepFocusInside(blurSource, e) {
    if (!this.nodeRef.current) {
      return;
    }
    const {
      losesFocusTo
    } = this.props;

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
      const focusRegion = this.nodeRef.current.querySelector('.DayPicker_focusRegion');
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
      const focusRegion = this.nodeRef.current.querySelector('.DayPickerNavigation_button');
      if (focusRegion) {
        focusRegion.focus();
      }
    }
  }
  onDatesChange({
    startDate,
    endDate
  }) {
    const {
      onUpdate,
      shortDateFormat
    } = this.props;
    onUpdate({
      after: startDate,
      before: endDate,
      afterText: startDate ? startDate.format(shortDateFormat) : '',
      beforeText: endDate ? endDate.format(shortDateFormat) : '',
      afterError: null,
      beforeError: null
    });
  }
  onFocusChange(focusedInput) {
    this.props.onUpdate({
      focusedInput: !focusedInput ? 'startDate' : focusedInput
    });
  }
  onInputChange(input, event) {
    const value = event.target.value;
    const {
      after,
      before,
      shortDateFormat
    } = this.props;
    const {
      date,
      error
    } = (0,src/* validateDateInputForRange */.t_)(input, value, before, after, shortDateFormat);
    this.props.onUpdate({
      [input]: date,
      [input + 'Text']: value,
      [input + 'Error']: value.length > 0 ? error : null
    });
  }
  setTnitialVisibleMonth(isDoubleCalendar, before) {
    return () => {
      const isValidMoment = before && moment_default().isMoment(before) && before.isValid();
      const visibleDate = isValidMoment ? before : moment_default()();
      if (isDoubleCalendar) {
        return visibleDate.clone().subtract(1, 'month');
      }
      return visibleDate;
    };
  }
  render() {
    const {
      after,
      before,
      focusedInput,
      afterText,
      beforeText,
      afterError,
      beforeError,
      shortDateFormat,
      shortDateFormatPlaceholder,
      isViewportMobile,
      isViewportSmall,
      isInvalidDate
    } = this.props;
    const isDoubleCalendar = isViewportMobile && !isViewportSmall;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('woocommerce-calendar', {
        'is-mobile': isViewportMobile
      }),
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-calendar__inputs",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(input/* default */.A, {
          value: afterText,
          onChange: (0,lodash.partial)(this.onInputChange, 'after'),
          dateFormat: shortDateFormatPlaceholder || shortDateFormat,
          label: (0,build_module.__)('Start Date', 'woocommerce'),
          error: afterError,
          describedBy: (0,build_module/* sprintf */.nv)(/* translators: %s: date format specification */
          (0,build_module.__)("Date input describing a selected date range's start date in format %s", 'woocommerce'), shortDateFormatPlaceholder || shortDateFormat),
          onFocus: () => this.onFocusChange('startDate')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-calendar__inputs-to",
          children: (0,build_module.__)('to', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(input/* default */.A, {
          value: beforeText,
          onChange: (0,lodash.partial)(this.onInputChange, 'before'),
          dateFormat: shortDateFormatPlaceholder || shortDateFormat,
          label: (0,build_module.__)('End Date', 'woocommerce'),
          error: beforeError,
          describedBy: (0,build_module/* sprintf */.nv)(/* translators: %s: date format specification */
          (0,build_module.__)("Date input describing a selected date range's end date in format %s", 'woocommerce'), shortDateFormatPlaceholder || shortDateFormat),
          onFocus: () => this.onFocusChange('endDate')
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-calendar__react-dates",
        ref: this.nodeRef,
        onBlur: (0,lodash.partial)(this.keepFocusInside, CONTAINER_DIV),
        tabIndex: -1,
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(react_dates.DayPickerRangeController, {
          onNextMonthClick: (0,lodash.partial)(this.keepFocusInside, NEXT_MONTH_CLICK),
          onPrevMonthClick: (0,lodash.partial)(this.keepFocusInside, PREV_MONTH_CLICK),
          onDatesChange: this.onDatesChange,
          onFocusChange: this.onFocusChange,
          focusedInput: focusedInput,
          startDate: after,
          endDate: before,
          orientation: 'horizontal',
          numberOfMonths: isDoubleCalendar ? 2 : 1,
          isOutsideRange: date => {
            return isInvalidDate && isInvalidDate(date.toDate());
          },
          minimumNights: 0,
          hideKeyboardShortcutsPanel: true,
          noBorder: true,
          isRTL: isRTL(),
          initialVisibleMonth: this.setTnitialVisibleMonth(isDoubleCalendar, before),
          phrases: phrases
        })
      })]
    });
  }
}
/* harmony default export */ const date_range = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium',
  isViewportSmall: '< small'
})(DateRange));
;
DateRange.__docgenInfo = {
  "description": "This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.",
  "methods": [{
    "name": "keepFocusInside",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "blurSource",
      "optional": false,
      "type": null
    }, {
      "name": "e",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onDatesChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "{ startDate, endDate }",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onFocusChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "focusedInput",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onInputChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "input",
      "optional": false,
      "type": null
    }, {
      "name": "event",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "setTnitialVisibleMonth",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "isDoubleCalendar",
      "optional": false,
      "type": null
    }, {
      "name": "before",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "DateRange",
  "props": {
    "after": {
      "description": "A moment date object representing the selected start. `null` for no selection.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "afterError": {
      "description": "A string error message, shown to the user.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "afterText": {
      "description": "The start date in human-readable format. Displayed in the text input.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "before": {
      "description": "A moment date object representing the selected end. `null` for no selection.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "beforeError": {
      "description": "A string error message, shown to the user.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "beforeText": {
      "description": "The end date in human-readable format. Displayed in the text input.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "focusedInput": {
      "description": "String identifying which is the currently focused input (start or end).",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "isInvalidDate": {
      "description": "A function to determine if a day on the calendar is not valid",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "onUpdate": {
      "description": "A function called upon selection of a date.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "shortDateFormat": {
      "description": "The date format in moment.js-style tokens.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "shortDateFormatPlaceholder": {
      "description": "The date format in human-readable format.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "losesFocusTo": {
      "description": "A ref that the DateRange can lose focus to.\nSee: https://github.com/woocommerce/woocommerce-admin/pull/2929.",
      "type": {
        "name": "instanceOf",
        "value": "Element"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/hoist-non-react-statics@3.3.2/node_modules/hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var reactIs = __webpack_require__("../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/index.js");

/**
 * Copyright 2015, Yahoo! Inc.
 * Copyrights licensed under the New BSD License. See the accompanying LICENSE file for terms.
 */
var REACT_STATICS = {
  childContextTypes: true,
  contextType: true,
  contextTypes: true,
  defaultProps: true,
  displayName: true,
  getDefaultProps: true,
  getDerivedStateFromError: true,
  getDerivedStateFromProps: true,
  mixins: true,
  propTypes: true,
  type: true
};
var KNOWN_STATICS = {
  name: true,
  length: true,
  prototype: true,
  caller: true,
  callee: true,
  arguments: true,
  arity: true
};
var FORWARD_REF_STATICS = {
  '$$typeof': true,
  render: true,
  defaultProps: true,
  displayName: true,
  propTypes: true
};
var MEMO_STATICS = {
  '$$typeof': true,
  compare: true,
  defaultProps: true,
  displayName: true,
  propTypes: true,
  type: true
};
var TYPE_STATICS = {};
TYPE_STATICS[reactIs.ForwardRef] = FORWARD_REF_STATICS;
TYPE_STATICS[reactIs.Memo] = MEMO_STATICS;

function getStatics(component) {
  // React v16.11 and below
  if (reactIs.isMemo(component)) {
    return MEMO_STATICS;
  } // React v16.12 and above


  return TYPE_STATICS[component['$$typeof']] || REACT_STATICS;
}

var defineProperty = Object.defineProperty;
var getOwnPropertyNames = Object.getOwnPropertyNames;
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
var getPrototypeOf = Object.getPrototypeOf;
var objectPrototype = Object.prototype;
function hoistNonReactStatics(targetComponent, sourceComponent, blacklist) {
  if (typeof sourceComponent !== 'string') {
    // don't hoist over string (html) components
    if (objectPrototype) {
      var inheritedComponent = getPrototypeOf(sourceComponent);

      if (inheritedComponent && inheritedComponent !== objectPrototype) {
        hoistNonReactStatics(targetComponent, inheritedComponent, blacklist);
      }
    }

    var keys = getOwnPropertyNames(sourceComponent);

    if (getOwnPropertySymbols) {
      keys = keys.concat(getOwnPropertySymbols(sourceComponent));
    }

    var targetStatics = getStatics(targetComponent);
    var sourceStatics = getStatics(sourceComponent);

    for (var i = 0; i < keys.length; ++i) {
      var key = keys[i];

      if (!KNOWN_STATICS[key] && !(blacklist && blacklist[key]) && !(sourceStatics && sourceStatics[key]) && !(targetStatics && targetStatics[key])) {
        var descriptor = getOwnPropertyDescriptor(sourceComponent, key);

        try {
          // Avoid failures from read-only properties
          defineProperty(targetComponent, key, descriptor);
        } catch (e) {}
      }
    }
  }

  return targetComponent;
}

module.exports = hoistNonReactStatics;


/***/ }),

/***/ "../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   fL: () => (/* binding */ pascalCase)
/* harmony export */ });
/* unused harmony exports pascalCaseTransform, pascalCaseTransformMerge */
/* harmony import */ var tslib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/tslib@2.8.1/node_modules/tslib/tslib.es6.mjs");
/* harmony import */ var no_case__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/no-case@3.0.4/node_modules/no-case/dist.es2015/index.js");


function pascalCaseTransform(input, index) {
    var firstChar = input.charAt(0);
    var lowerChars = input.substr(1).toLowerCase();
    if (index > 0 && firstChar >= "0" && firstChar <= "9") {
        return "_" + firstChar + lowerChars;
    }
    return "" + firstChar.toUpperCase() + lowerChars;
}
function pascalCaseTransformMerge(input) {
    return input.charAt(0).toUpperCase() + input.slice(1).toLowerCase();
}
function pascalCase(input, options) {
    if (options === void 0) { options = {}; }
    return (0,no_case__WEBPACK_IMPORTED_MODULE_0__/* .noCase */ .W)(input, (0,tslib__WEBPACK_IMPORTED_MODULE_1__/* .__assign */ .Cl)({ delimiter: "", transform: pascalCaseTransform }, options));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/cjs/react-is.production.min.js":
/***/ ((__unused_webpack_module, exports) => {

"use strict";
/** @license React v16.13.1
 * react-is.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var b="function"===typeof Symbol&&Symbol.for,c=b?Symbol.for("react.element"):60103,d=b?Symbol.for("react.portal"):60106,e=b?Symbol.for("react.fragment"):60107,f=b?Symbol.for("react.strict_mode"):60108,g=b?Symbol.for("react.profiler"):60114,h=b?Symbol.for("react.provider"):60109,k=b?Symbol.for("react.context"):60110,l=b?Symbol.for("react.async_mode"):60111,m=b?Symbol.for("react.concurrent_mode"):60111,n=b?Symbol.for("react.forward_ref"):60112,p=b?Symbol.for("react.suspense"):60113,q=b?
Symbol.for("react.suspense_list"):60120,r=b?Symbol.for("react.memo"):60115,t=b?Symbol.for("react.lazy"):60116,v=b?Symbol.for("react.block"):60121,w=b?Symbol.for("react.fundamental"):60117,x=b?Symbol.for("react.responder"):60118,y=b?Symbol.for("react.scope"):60119;
function z(a){if("object"===typeof a&&null!==a){var u=a.$$typeof;switch(u){case c:switch(a=a.type,a){case l:case m:case e:case g:case f:case p:return a;default:switch(a=a&&a.$$typeof,a){case k:case n:case t:case r:case h:return a;default:return u}}case d:return u}}}function A(a){return z(a)===m}exports.AsyncMode=l;exports.ConcurrentMode=m;exports.ContextConsumer=k;exports.ContextProvider=h;exports.Element=c;exports.ForwardRef=n;exports.Fragment=e;exports.Lazy=t;exports.Memo=r;exports.Portal=d;
exports.Profiler=g;exports.StrictMode=f;exports.Suspense=p;exports.isAsyncMode=function(a){return A(a)||z(a)===l};exports.isConcurrentMode=A;exports.isContextConsumer=function(a){return z(a)===k};exports.isContextProvider=function(a){return z(a)===h};exports.isElement=function(a){return"object"===typeof a&&null!==a&&a.$$typeof===c};exports.isForwardRef=function(a){return z(a)===n};exports.isFragment=function(a){return z(a)===e};exports.isLazy=function(a){return z(a)===t};
exports.isMemo=function(a){return z(a)===r};exports.isPortal=function(a){return z(a)===d};exports.isProfiler=function(a){return z(a)===g};exports.isStrictMode=function(a){return z(a)===f};exports.isSuspense=function(a){return z(a)===p};
exports.isValidElementType=function(a){return"string"===typeof a||"function"===typeof a||a===e||a===m||a===g||a===f||a===p||a===q||"object"===typeof a&&null!==a&&(a.$$typeof===t||a.$$typeof===r||a.$$typeof===h||a.$$typeof===k||a.$$typeof===n||a.$$typeof===w||a.$$typeof===x||a.$$typeof===y||a.$$typeof===v)};exports.typeOf=z;


/***/ }),

/***/ "../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/cjs/react-is.production.min.js");
} else {}


/***/ }),

/***/ "?9f28":
/***/ (() => {

/* (ignored) */

/***/ })

}]);