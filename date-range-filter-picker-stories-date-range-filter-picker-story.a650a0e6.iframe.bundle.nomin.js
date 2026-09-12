(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9416],{

/***/ "../../packages/js/components/src/date-range-filter-picker/stories/date-range-filter-picker.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../packages/js/components/src/date-range-filter-picker/index.js");
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/date/src/index.ts");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



/**
 * External dependencies
 */


const query = {};
const defaultDateRange = 'period=month&compare=previous_year';
const storeGetDateParamsFromQuery = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partialRight)(_woocommerce_date__WEBPACK_IMPORTED_MODULE_0__/* .getDateParamsFromQuery */ .vW, defaultDateRange);
const storeGetCurrentDates = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partialRight)(_woocommerce_date__WEBPACK_IMPORTED_MODULE_0__/* .getCurrentDates */ .lI, defaultDateRange);
const {
  period,
  compare,
  before,
  after
} = storeGetDateParamsFromQuery(query);
const {
  primary: primaryDate,
  secondary: secondaryDate
} = storeGetCurrentDates(query);
const dateQuery = {
  period,
  compare,
  before,
  after,
  primaryDate,
  secondaryDate
};
const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
  query: query,
  onRangeSelect: () => {},
  dateQuery: dateQuery,
  isoDateFormat: _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__/* .isoDateFormat */ .r3
}, "daterange");
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/DateRangeFilterPicker',
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

/***/ "../../packages/js/components/src/date-range-filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date_range_filter_picker)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js + 29 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js + 8 modules
var tab_panel = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/segmented-selection/index.js
var segmented_selection = __webpack_require__("../../packages/js/components/src/segmented-selection/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/date-range-filter-picker/compare-periods.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


class ComparePeriods extends react.Component {
  render() {
    const {
      onSelect,
      compare
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsx)(segmented_selection/* default */.A, {
      options: src/* periods */.RE,
      selected: compare,
      onSelect: onSelect,
      name: "compare",
      legend: (0,build_module.__)('compare to', 'woocommerce')
    });
  }
}
/* harmony default export */ const compare_periods = (ComparePeriods);
;
ComparePeriods.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "ComparePeriods",
  "props": {
    "onSelect": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "compare": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/date-range.js + 1 modules
var date_range = __webpack_require__("../../packages/js/components/src/calendar/date-range.js");
// EXTERNAL MODULE: ../../packages/js/components/src/section/header.tsx
var header = __webpack_require__("../../packages/js/components/src/section/header.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/section/section.tsx
var section = __webpack_require__("../../packages/js/components/src/section/section.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
;// ../../packages/js/components/src/date-range-filter-picker/preset-periods.js
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


class PresetPeriods extends react.Component {
  render() {
    const {
      onSelect,
      period
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsx)(segmented_selection/* default */.A, {
      options: (0,lodash.filter)(src/* presetValues */.Ad, preset => preset.value !== 'custom'),
      selected: period,
      onSelect: onSelect,
      name: "period",
      legend: (0,build_module.__)('select a preset period', 'woocommerce')
    });
  }
}
/* harmony default export */ const preset_periods = (PresetPeriods);
;
PresetPeriods.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "PresetPeriods",
  "props": {
    "onSelect": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "period": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/date-range-filter-picker/content.js
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





class DatePickerContent extends react.Component {
  constructor() {
    super();
    this.onTabSelect = this.onTabSelect.bind(this);
    this.controlsRef = (0,react.createRef)();
  }
  onTabSelect(tab) {
    const {
      onUpdate,
      period
    } = this.props;

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
  isFutureDate(dateString) {
    return moment_default()().isBefore(moment_default()(dateString), 'day');
  }
  render() {
    const {
      period,
      compare,
      after,
      before,
      onUpdate,
      onClose,
      onSelect,
      isValidSelection,
      resetCustomValues,
      focusedInput,
      afterText,
      beforeText,
      afterError,
      beforeError,
      shortDateFormat,
      shortDateFormatPlaceholder
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
        className: "screen-reader-text",
        tabIndex: "0",
        children: (0,build_module.__)('Select date range and comparison', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(section/* Section */.w, {
        component: false,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
          className: "woocommerce-filters-date__text",
          children: (0,build_module.__)('select a date range', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(tab_panel/* default */.A, {
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
          onSelect: this.onTabSelect,
          children: selected => /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
            children: [selected.name === 'period' && /*#__PURE__*/(0,jsx_runtime.jsx)(preset_periods, {
              onSelect: onUpdate,
              period: period
            }), selected.name === 'custom' && /*#__PURE__*/(0,jsx_runtime.jsx)(date_range/* default */.A, {
              after: after,
              before: before,
              onUpdate: onUpdate,
              isInvalidDate: this.isFutureDate,
              focusedInput: focusedInput,
              afterText: afterText,
              beforeText: beforeText,
              afterError: afterError,
              beforeError: beforeError,
              shortDateFormat: shortDateFormat,
              shortDateFormatPlaceholder: shortDateFormatPlaceholder,
              losesFocusTo: this.controlsRef.current
            }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
              className: (0,clsx/* default */.A)('woocommerce-filters-date__content-controls', {
                'is-custom': selected.name === 'custom'
              }),
              ref: this.controlsRef,
              children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
                className: "woocommerce-filters-date__text",
                children: (0,build_module.__)('compare to', 'woocommerce')
              }), /*#__PURE__*/(0,jsx_runtime.jsx)(compare_periods, {
                onSelect: onUpdate,
                compare: compare
              }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
                className: "woocommerce-filters-date__button-group",
                children: [selected.name === 'custom' && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  className: "woocommerce-filters-date__button",
                  isSecondary: true,
                  onClick: resetCustomValues,
                  disabled: !(after || before),
                  children: (0,build_module.__)('Reset', 'woocommerce')
                }), isValidSelection(selected.name) ? /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  className: "woocommerce-filters-date__button",
                  onClick: onSelect(selected.name, onClose),
                  isPrimary: true,
                  children: (0,build_module.__)('Update', 'woocommerce')
                }) : /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  className: "woocommerce-filters-date__button",
                  isPrimary: true,
                  disabled: true,
                  children: (0,build_module.__)('Update', 'woocommerce')
                })]
              })]
            })]
          })
        })]
      })]
    });
  }
}
/* harmony default export */ const content = (DatePickerContent);
;
DatePickerContent.__docgenInfo = {
  "description": "",
  "methods": [{
    "name": "onTabSelect",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "tab",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "isFutureDate",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "dateString",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "DatePickerContent",
  "props": {
    "period": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "compare": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "onUpdate": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "onClose": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "onSelect": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "resetCustomValues": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "focusedInput": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "afterText": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "beforeText": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "afterError": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "beforeError": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "shortDateFormat": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "shortDateFormatPlaceholder": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../packages/js/components/src/dropdown-button/index.js
var dropdown_button = __webpack_require__("../../packages/js/components/src/dropdown-button/index.js");
;// ../../packages/js/components/src/date-range-filter-picker/index.js
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */



const shortDateFormatPlaceholder = (0,build_module.__)('MM/DD/YYYY', 'woocommerce');
const shortDateFormat = 'MM/DD/YYYY';

/**
 * Select a range of dates or single dates.
 */
class DateRangeFilterPicker extends react.Component {
  constructor(props) {
    super(props);
    this.state = this.getResetState();
    this.update = this.update.bind(this);
    this.onSelect = this.onSelect.bind(this);
    this.isValidSelection = this.isValidSelection.bind(this);
    this.resetCustomValues = this.resetCustomValues.bind(this);
  }
  formatDate(date, format) {
    if (date && date._isAMomentObject && date.isValid() && typeof date.format === 'function') {
      return date.format(format);
    }
    return '';
  }
  getResetState() {
    const {
      period,
      compare,
      before,
      after
    } = this.props.dateQuery;
    return {
      period,
      compare,
      before,
      after,
      focusedInput: 'startDate',
      afterText: this.formatDate(after, shortDateFormat),
      beforeText: this.formatDate(before, shortDateFormat),
      afterError: null,
      beforeError: null
    };
  }
  update(update) {
    this.setState(update);
  }
  onSelect(selectedTab, onClose) {
    const {
      isoDateFormat,
      onRangeSelect
    } = this.props;
    return event => {
      const {
        period,
        compare,
        after,
        before
      } = this.state;
      const data = {
        period: selectedTab === 'custom' ? 'custom' : period,
        compare
      };
      if (selectedTab === 'custom') {
        data.after = this.formatDate(after, isoDateFormat);
        data.before = this.formatDate(before, isoDateFormat);
      } else {
        data.after = undefined;
        data.before = undefined;
      }
      onRangeSelect(data);
      onClose(event);
    };
  }
  getButtonLabel() {
    const {
      primaryDate,
      secondaryDate
    } = this.props.dateQuery;
    return [`${primaryDate.label} (${primaryDate.range})`, `${(0,build_module.__)('vs.', 'woocommerce')} ${secondaryDate.label} (${secondaryDate.range})`];
  }
  isValidSelection(selectedTab) {
    const {
      compare,
      after,
      before
    } = this.state;
    if (selectedTab === 'custom') {
      return compare && after && before;
    }
    return true;
  }
  resetCustomValues() {
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
  render() {
    const {
      period,
      compare,
      after,
      before,
      focusedInput,
      afterText,
      beforeText,
      afterError,
      beforeError
    } = this.state;
    const {
      isViewportMobile,
      focusOnMount = true,
      popoverProps = {
        inline: true
      }
    } = this.props;
    if (!popoverProps.placement) {
      popoverProps.placement = 'bottom';
    }
    const contentClasses = (0,clsx/* default */.A)('woocommerce-filters-date__content', {
      'is-mobile': isViewportMobile
    });
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-filters-filter",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "woocommerce-filters-label",
        children: (0,build_module.__)('Date range', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown/* default */.A, {
        contentClassName: contentClasses,
        expandOnMobile: true,
        focusOnMount: focusOnMount,
        popoverProps: popoverProps,
        renderToggle: ({
          isOpen,
          onToggle
        }) => /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown_button/* default */.A, {
          onClick: onToggle,
          isOpen: isOpen,
          labels: this.getButtonLabel()
        }),
        renderContent: ({
          onClose
        }) => /*#__PURE__*/(0,jsx_runtime.jsx)(content, {
          period: period,
          compare: compare,
          after: after,
          before: before,
          onUpdate: this.update,
          onClose: onClose,
          onSelect: this.onSelect,
          isValidSelection: this.isValidSelection,
          resetCustomValues: this.resetCustomValues,
          focusedInput: focusedInput,
          afterText: afterText,
          beforeText: beforeText,
          afterError: afterError,
          beforeError: beforeError,
          shortDateFormat: shortDateFormat,
          shortDateFormatPlaceholder: shortDateFormatPlaceholder
        })
      })]
    });
  }
}
/* harmony default export */ const date_range_filter_picker = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium'
})(DateRangeFilterPicker));
;
DateRangeFilterPicker.__docgenInfo = {
  "description": "Select a range of dates or single dates.",
  "methods": [{
    "name": "formatDate",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "date",
      "optional": false,
      "type": null
    }, {
      "name": "format",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getResetState",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "update",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "update",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onSelect",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selectedTab",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getButtonLabel",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "isValidSelection",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selectedTab",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "resetCustomValues",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "DateRangeFilterPicker",
  "props": {
    "onRangeSelect": {
      "description": "Callback called when selection is made.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "dateQuery": {
      "description": "The date query string represented in object form.",
      "type": {
        "name": "shape",
        "value": {
          "period": {
            "name": "string",
            "required": true
          },
          "compare": {
            "name": "string",
            "required": true
          },
          "before": {
            "name": "object",
            "required": false
          },
          "after": {
            "name": "object",
            "required": false
          },
          "primaryDate": {
            "name": "shape",
            "value": {
              "label": {
                "name": "string",
                "required": true
              },
              "range": {
                "name": "string",
                "required": true
              }
            },
            "required": true
          },
          "secondaryDate": {
            "name": "shape",
            "value": {
              "label": {
                "name": "string",
                "required": true
              },
              "range": {
                "name": "string",
                "required": true
              }
            },
            "required": true
          }
        }
      },
      "required": true
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/dropdown-button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
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

const DropdownButton = props => {
  const {
    labels,
    isOpen,
    ...otherProps
  } = props;
  const buttonClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-dropdown-button', {
    'is-open': isOpen,
    'is-multi-line': labels.length > 1
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
    className: buttonClasses,
    "aria-expanded": isOpen,
    ...otherProps,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: "woocommerce-dropdown-button__labels",
      children: labels.map((label, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        children: (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__/* .decodeEntities */ .S)(label)
      }, i))
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DropdownButton);
;
DropdownButton.__docgenInfo = {
  "description": "A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays\nsingle or multiple lines rendered as `<span/>` elements.\n\n@param {Object} props Props passed to component.\n@return {Object} -",
  "methods": [],
  "displayName": "DropdownButton",
  "props": {
    "labels": {
      "description": "An array of elements to be rendered as the content of the button.",
      "type": {
        "name": "array"
      },
      "required": true
    },
    "isOpen": {
      "description": "Boolean describing if the dropdown in open or not.",
      "type": {
        "name": "bool"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/segmented-selection/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * Create a panel of styled selectable options rendering stylized checkboxes and labels
 */

class SegmentedSelection extends _wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Component {
  render() {
    const {
      className,
      options,
      selected,
      onSelect,
      name,
      legend
    } = this.props;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("fieldset", {
      className: "woocommerce-segmented-selection",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("legend", {
        className: "screen-reader-text",
        children: legend
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(className, 'woocommerce-segmented-selection__container'),
        children: options.map(({
          value,
          label
        }) => {
          if (!value || !label) {
            return null;
          }
          const id = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.uniqueId)(`${value}_`);
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
            className: "woocommerce-segmented-selection__item",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
              className: "woocommerce-segmented-selection__input",
              type: "radio",
              name: name,
              id: id,
              checked: selected === value,
              onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(onSelect, {
                [name]: value
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("label", {
              htmlFor: id,
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
                className: "woocommerce-segmented-selection__label",
                children: label
              })
            })]
          }, value);
        })
      })]
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SegmentedSelection);
;
SegmentedSelection.__docgenInfo = {
  "description": "Create a panel of styled selectable options rendering stylized checkboxes and labels",
  "methods": [],
  "displayName": "SegmentedSelection",
  "props": {
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "options": {
      "description": "An Array of options to render. The array needs to be composed of objects with properties `label` and `value`.",
      "type": {
        "name": "arrayOf",
        "value": {
          "name": "shape",
          "value": {
            "value": {
              "name": "string",
              "required": true
            },
            "label": {
              "name": "string",
              "required": true
            }
          }
        }
      },
      "required": true
    },
    "selected": {
      "description": "Value of selected item.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onSelect": {
      "description": "Callback to be executed after selection",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "name": {
      "description": "This will be the key in the key and value arguments supplied to `onSelect`.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "legend": {
      "description": "Create a legend visible to screen readers.",
      "type": {
        "name": "string"
      },
      "required": true
    }
  }
};

/***/ }),

/***/ "?9f28":
/***/ (() => {

/* (ignored) */

/***/ })

}]);