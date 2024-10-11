(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[964],{

/***/ "../../packages/js/date/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ad: () => (/* binding */ presetValues),
/* harmony export */   RE: () => (/* binding */ periods),
/* harmony export */   Y6: () => (/* binding */ dateValidationMessages),
/* harmony export */   lI: () => (/* binding */ getCurrentDates),
/* harmony export */   r3: () => (/* binding */ isoDateFormat),
/* harmony export */   sf: () => (/* binding */ toMoment),
/* harmony export */   t_: () => (/* binding */ validateDateInputForRange),
/* harmony export */   vW: () => (/* binding */ getDateParamsFromQuery)
/* harmony export */ });
/* unused harmony exports defaultDateTimeFormat, appendTimestamp, getRangeLabel, getStoreTimeZoneMoment, getLastPeriod, getCurrentPeriod, getDateDifferenceInDays, getPreviousDate, getAllowedIntervalsForQuery, getIntervalForQuery, getChartTypeForQuery, dayTicksThreshold, weekTicksThreshold, defaultTableDateFormat, getDateFormatsForIntervalD3, getDateFormatsForIntervalPhp, getDateFormatsForInterval, loadLocaleData, isLeapYear, containsLeapYear */
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
/* harmony import */ var core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/qs@6.11.2/node_modules/qs/lib/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(qs__WEBPACK_IMPORTED_MODULE_10__);







/**
 * External dependencies
 */




var isoDateFormat = 'YYYY-MM-DD';
var defaultDateTimeFormat = 'YYYY-MM-DDTHH:mm:ss';

/**
 * DateValue Object
 *
 * @typedef  {Object} DateValue - DateValue data about the selected period.
 * @property {moment.Moment} primaryStart   - Primary start of the date range.
 * @property {moment.Moment} primaryEnd     - Primary end of the date range.
 * @property {moment.Moment} secondaryStart - Secondary start of the date range.
 * @property {moment.Moment} secondaryEnd   - Secondary End of the date range.
 */

/**
 * DataPickerOptions Object
 *
 * @typedef  {Object}  DataPickerOptions - Describes the date range supplied by the date picker.
 * @property {string}        label  - The translated value of the period.
 * @property {string}        range  - The human readable value of a date range.
 * @property {moment.Moment} after  - Start of the date range.
 * @property {moment.Moment} before - End of the date range.
 */

/**
 * DateParams Object
 *
 * @typedef {Object} DateParams - date parameters derived from query parameters.
 * @property {string}             period  - period value, ie `last_week`
 * @property {string}             compare - compare valuer, ie previous_year
 * @param    {moment.Moment|null} after   - If the period supplied is "custom", this is the after date
 * @param    {moment.Moment|null} before  - If the period supplied is "custom", this is the before date
 */

var presetValues = [{
  value: 'today',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Today', 'woocommerce')
}, {
  value: 'yesterday',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Yesterday', 'woocommerce')
}, {
  value: 'week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Week to date', 'woocommerce')
}, {
  value: 'last_week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last week', 'woocommerce')
}, {
  value: 'month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Month to date', 'woocommerce')
}, {
  value: 'last_month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last month', 'woocommerce')
}, {
  value: 'quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Quarter to date', 'woocommerce')
}, {
  value: 'last_quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last quarter', 'woocommerce')
}, {
  value: 'year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Year to date', 'woocommerce')
}, {
  value: 'last_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last year', 'woocommerce')
}, {
  value: 'custom',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Custom', 'woocommerce')
}];
var periods = [{
  value: 'previous_period',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Previous period', 'woocommerce')
}, {
  value: 'previous_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Previous year', 'woocommerce')
}];
var isValidMomentInput = function isValidMomentInput(input) {
  return moment__WEBPACK_IMPORTED_MODULE_7___default()(input).isValid();
};

/**
 * Adds timestamp to a string date.
 *
 * @param {moment.Moment} date      - Date as a moment object.
 * @param {string}        timeOfDay - Either `start`, `now` or `end` of the day.
 * @return {string} - String date with timestamp attached.
 */
var appendTimestamp = function appendTimestamp(date, timeOfDay) {
  if (timeOfDay === 'start') {
    return date.startOf('day').format(defaultDateTimeFormat);
  }
  if (timeOfDay === 'now') {
    // Set seconds to 00 to avoid consecutives calls happening before the previous
    // one finished.
    return date.format(defaultDateTimeFormat);
  }
  if (timeOfDay === 'end') {
    return date.endOf('day').format(defaultDateTimeFormat);
  }
  throw new Error('appendTimestamp requires second parameter to be either `start`, `now` or `end`');
};

/**
 * Convert a string to Moment object
 *
 * @param {string}  format - localized date string format
 * @param {unknown} str    - date string or moment object
 * @return {moment.Moment|null} - Moment object representing given string
 */
function toMoment(format, str) {
  if (moment__WEBPACK_IMPORTED_MODULE_7___default().isMoment(str)) {
    return str.isValid() ? str : null;
  }
  if (typeof str === 'string') {
    var date = moment__WEBPACK_IMPORTED_MODULE_7___default()(str, [isoDateFormat, format], true);
    return date.isValid() ? date : null;
  }
  throw new Error('toMoment requires a string to be passed as an argument');
}

/**
 * Given two dates, derive a string representation
 *
 * @param {moment.Moment} after  - start date
 * @param {moment.Moment} before - end date
 * @return {string} - text value for the supplied date range
 */
function getRangeLabel(after, before) {
  var isSameYear = after.year() === before.year();
  var isSameMonth = isSameYear && after.month() === before.month();
  var isSameDay = isSameYear && isSameMonth && after.isSame(before, 'day');
  var fullDateFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('MMM D, YYYY', 'woocommerce');
  if (isSameDay) {
    return after.format(fullDateFormat);
  } else if (isSameMonth) {
    var afterDate = after.date();
    return after.format(fullDateFormat).replace(String(afterDate), "".concat(afterDate, " - ").concat(before.date()));
  } else if (isSameYear) {
    var monthDayFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('MMM D', 'woocommerce');
    return "".concat(after.format(monthDayFormat), " - ").concat(before.format(fullDateFormat));
  }
  return "".concat(after.format(fullDateFormat), " - ").concat(before.format(fullDateFormat));
}

/**
 * Gets the current time in the store time zone if set.
 *
 * @return {string} - Datetime string.
 */
function getStoreTimeZoneMoment() {
  if (!window.wcSettings || !window.wcSettings.timeZone) {
    return moment__WEBPACK_IMPORTED_MODULE_7___default()();
  }
  if (['+', '-'].includes(window.wcSettings.timeZone.charAt(0))) {
    return moment__WEBPACK_IMPORTED_MODULE_7___default()().utcOffset(window.wcSettings.timeZone);
  }
  return moment__WEBPACK_IMPORTED_MODULE_7___default()().tz(window.wcSettings.timeZone);
}

/**
 * Get a DateValue object for a period prior to the current period.
 *
 * @param {moment.DurationInputArg2} period  - the chosen period
 * @param {string}                   compare - `previous_period` or `previous_year`
 * @return {DateValue} - DateValue data about the selected period
 */
function getLastPeriod(period, compare) {
  var primaryStart = getStoreTimeZoneMoment().startOf(period).subtract(1, period);
  var primaryEnd = primaryStart.clone().endOf(period);
  var secondaryStart;
  var secondaryEnd;
  if (compare === 'previous_period') {
    if (period === 'year') {
      // Subtract two entire periods for years to take into account leap year
      secondaryStart = moment__WEBPACK_IMPORTED_MODULE_7___default()().startOf(period).subtract(2, period);
      secondaryEnd = secondaryStart.clone().endOf(period);
    } else {
      // Otherwise, use days in primary period to figure out how far to go back
      // This is necessary for calculating weeks instead of using `endOf`.
      var daysDiff = primaryEnd.diff(primaryStart, 'days');
      secondaryEnd = primaryStart.clone().subtract(1, 'days');
      secondaryStart = secondaryEnd.clone().subtract(daysDiff, 'days');
    }
  } else if (period === 'week') {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    secondaryEnd = primaryEnd.clone().subtract(1, 'years');
  } else {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    secondaryEnd = secondaryStart.clone().endOf(period);
  }

  // When the period is month, be sure to force end of month to take into account leap year
  if (period === 'month') {
    secondaryEnd = secondaryEnd.clone().endOf('month');
  }
  return {
    primaryStart: primaryStart,
    primaryEnd: primaryEnd,
    secondaryStart: secondaryStart,
    secondaryEnd: secondaryEnd
  };
}

/**
 * Get a DateValue object for a current period. The period begins on the first day of the period,
 * and ends on the current day.
 *
 * @param {moment.DurationInputArg2} period  - the chosen period
 * @param {string}                   compare - `previous_period` or `previous_year`
 * @return {DateValue} - DateValue data about the selected period
 */
function getCurrentPeriod(period, compare) {
  var primaryStart = getStoreTimeZoneMoment().startOf(period);
  var primaryEnd = getStoreTimeZoneMoment();
  var daysSoFar = primaryEnd.diff(primaryStart, 'days');
  var secondaryStart;
  var secondaryEnd;
  if (compare === 'previous_period') {
    secondaryStart = primaryStart.clone().subtract(1, period);
    secondaryEnd = primaryEnd.clone().subtract(1, period);
  } else {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    // Set the end time to 23:59:59.
    secondaryEnd = secondaryStart.clone().add(daysSoFar + 1, 'days').subtract(1, 'seconds');
  }
  return {
    primaryStart: primaryStart,
    primaryEnd: primaryEnd,
    secondaryStart: secondaryStart,
    secondaryEnd: secondaryEnd
  };
}

/**
 * Get a DateValue object for a period described by a period, compare value, and start/end
 * dates, for custom dates.
 *
 * @param {string}             period   - the chosen period
 * @param {string}             compare  - `previous_period` or `previous_year`
 * @param {moment.Moment|null} [after]  - after date if custom period
 * @param {moment.Moment|null} [before] - before date if custom period
 * @return {DateValue} - DateValue data about the selected period
 */
var getDateValue = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.memoize)(function (period, compare, after, before) {
  switch (period) {
    case 'today':
      return getCurrentPeriod('day', compare);
    case 'yesterday':
      return getLastPeriod('day', compare);
    case 'week':
      return getCurrentPeriod('week', compare);
    case 'last_week':
      return getLastPeriod('week', compare);
    case 'month':
      return getCurrentPeriod('month', compare);
    case 'last_month':
      return getLastPeriod('month', compare);
    case 'quarter':
      return getCurrentPeriod('quarter', compare);
    case 'last_quarter':
      return getLastPeriod('quarter', compare);
    case 'year':
      return getCurrentPeriod('year', compare);
    case 'last_year':
      return getLastPeriod('year', compare);
    case 'custom':
      if (!after || !before) {
        throw Error('Custom date range requires both after and before dates.');
      }
      var difference = before.diff(after, 'days');
      if (compare === 'previous_period') {
        var secondaryEnd = after.clone().subtract(1, 'days');
        var secondaryStart = secondaryEnd.clone().subtract(difference, 'days');
        return {
          primaryStart: after,
          primaryEnd: before,
          secondaryStart: secondaryStart,
          secondaryEnd: secondaryEnd
        };
      }
      return {
        primaryStart: after,
        primaryEnd: before,
        secondaryStart: after.clone().subtract(1, 'years'),
        secondaryEnd: before.clone().subtract(1, 'years')
      };
  }
}, function (period, compare, after, before) {
  return [period, compare, after && after.format(), before && before.format()].join(':');
});

/**
 * Memoized internal logic of getDateParamsFromQuery().
 *
 * @param {string|undefined} period           - period value, ie `last_week`
 * @param {string|undefined} compare          - compare value, ie `previous_year`
 * @param {string|undefined} after            - date in iso date format, ie `2018-07-03`
 * @param {string|undefined} before           - date in iso date format, ie `2018-07-03`
 * @param {string}           defaultDateRange - the store's default date range
 * @return {DateParams} - date parameters derived from query parameters with added defaults
 */
var getDateParamsFromQueryMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.memoize)(function (period, compare, after, before, defaultDateRange) {
  if (period && compare) {
    return {
      period: period,
      compare: compare,
      after: after ? moment__WEBPACK_IMPORTED_MODULE_7___default()(after) : null,
      before: before ? moment__WEBPACK_IMPORTED_MODULE_7___default()(before) : null
    };
  }
  var queryDefaults = (0,qs__WEBPACK_IMPORTED_MODULE_10__.parse)(defaultDateRange.replace(/&amp;/g, '&'));
  if (typeof queryDefaults.period !== 'string') {
    /* eslint-disable no-console */
    console.warn("Unexpected default period type ".concat(queryDefaults.period));
    /* eslint-enable no-console */
    queryDefaults.period = '';
  }
  if (typeof queryDefaults.compare !== 'string') {
    /* eslint-disable no-console */
    console.warn("Unexpected default compare type ".concat(queryDefaults.compare));
    /* eslint-enable no-console */
    queryDefaults.compare = '';
  }
  return {
    period: queryDefaults.period,
    compare: queryDefaults.compare,
    after: queryDefaults.after && isValidMomentInput(queryDefaults.after) ? moment__WEBPACK_IMPORTED_MODULE_7___default()(queryDefaults.after) : null,
    before: queryDefaults.before && isValidMomentInput(queryDefaults.before) ? moment__WEBPACK_IMPORTED_MODULE_7___default()(queryDefaults.before) : null
  };
}, function (period, compare, after, before, defaultDateRange) {
  return [period, compare, after, before, defaultDateRange].join(':');
});

/**
 * Add default date-related parameters to a query object
 *
 * @param {Object} query            - query object
 * @param {string} query.period     - period value, ie `last_week`
 * @param {string} query.compare    - compare value, ie `previous_year`
 * @param {string} query.after      - date in iso date format, ie `2018-07-03`
 * @param {string} query.before     - date in iso date format, ie `2018-07-03`
 * @param {string} defaultDateRange - the store's default date range
 * @return {DateParams} - date parameters derived from query parameters with added defaults
 */
var getDateParamsFromQuery = function getDateParamsFromQuery(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=month&compare=previous_year';
  var period = query.period,
    compare = query.compare,
    after = query.after,
    before = query.before;
  return getDateParamsFromQueryMemoized(period, compare, after, before, defaultDateRange);
};

/**
 * Memoized internal logic of getCurrentDates().
 *
 * @param {string|undefined} period         - period value, ie `last_week`
 * @param {string|undefined} compare        - compare value, ie `previous_year`
 * @param {Object}           primaryStart   - primary query start DateTime, in Moment instance.
 * @param {Object}           primaryEnd     - primary query start DateTime, in Moment instance.
 * @param {Object}           secondaryStart - secondary query start DateTime, in Moment instance.
 * @param {Object}           secondaryEnd   - secondary query start DateTime, in Moment instance.
 * @return {{primary: DataPickerOptions, secondary: DataPickerOptions}} - Primary and secondary DataPickerOptions objects
 */
var getCurrentDatesMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.memoize)(function (period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) {
  var primaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.find)(presetValues, function (item) {
    return item.value === period;
  });
  if (!primaryItem) {
    throw new Error("Cannot find period: ".concat(period));
  }
  var secondaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.find)(periods, function (item) {
    return item.value === compare;
  });
  if (!secondaryItem) {
    throw new Error("Cannot find compare: ".concat(compare));
  }
  return {
    primary: {
      label: primaryItem.label,
      range: getRangeLabel(primaryStart, primaryEnd),
      after: primaryStart,
      before: primaryEnd
    },
    secondary: {
      label: secondaryItem.label,
      range: getRangeLabel(secondaryStart, secondaryEnd),
      after: secondaryStart,
      before: secondaryEnd
    }
  };
}, function (period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) {
  return [period, compare, primaryStart && primaryStart.format(), primaryEnd && primaryEnd.format(), secondaryStart && secondaryStart.format(), secondaryEnd && secondaryEnd.format()].join(':');
});

/**
 * Get Date Value Objects for a primary and secondary date range
 *
 * @param {Object} query            - query object
 * @param {string} query.period     - period value, ie `last_week`
 * @param {string} query.compare    - compare value, ie `previous_year`
 * @param {string} query.after      - date in iso date format, ie `2018-07-03`
 * @param {string} query.before     - date in iso date format, ie `2018-07-03`
 * @param {string} defaultDateRange - the store's default date range
 * @return {{primary: DataPickerOptions, secondary: DataPickerOptions}} - Primary and secondary DataPickerOptions objects
 */
var getCurrentDates = function getCurrentDates(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=month&compare=previous_year';
  var _getDateParamsFromQue = getDateParamsFromQuery(query, defaultDateRange),
    period = _getDateParamsFromQue.period,
    compare = _getDateParamsFromQue.compare,
    after = _getDateParamsFromQue.after,
    before = _getDateParamsFromQue.before;
  var dateValue = getDateValue(period, compare, after, before);
  if (!dateValue) {
    throw Error('Invalid date range');
  }
  var primaryStart = dateValue.primaryStart,
    primaryEnd = dateValue.primaryEnd,
    secondaryStart = dateValue.secondaryStart,
    secondaryEnd = dateValue.secondaryEnd;
  return getCurrentDatesMemoized(period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd);
};

/**
 * Calculates the date difference between two dates. Used in calculating a matching date for previous period.
 *
 * @param {string} date  - Date to compare
 * @param {string} date2 - Secondary date to compare
 * @return {number}  - Difference in days.
 */
var getDateDifferenceInDays = function getDateDifferenceInDays(date, date2) {
  var _date = moment(date);
  var _date2 = moment(date2);
  return _date.diff(_date2, 'days');
};

/**
 * Get the previous date for either the previous period of year.
 *
 * @param {string}                 date     - Base date
 * @param {string}                 date1    - primary start
 * @param {string}                 date2    - secondary start
 * @param {string}                 compare  - `previous_period`  or `previous_year`
 * @param {moment.unitOfTime.Diff} interval - interval
 * @return {Object}  - Calculated date
 */
var getPreviousDate = function getPreviousDate(date, date1, date2) {
  var compare = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'previous_year';
  var interval = arguments.length > 4 ? arguments[4] : undefined;
  var dateMoment = moment(date);
  if (compare === 'previous_year') {
    return dateMoment.clone().subtract(1, 'years');
  }
  var _date1 = moment(date1);
  var _date2 = moment(date2);
  var difference = _date1.diff(_date2, interval);
  return dateMoment.clone().subtract(difference, interval);
};

/**
 * Returns the allowed selectable intervals for a specific query.
 *
 * @param {Query}  query            Current query
 * @param {string} defaultDateRange - the store's default date range
 * @return {Array} Array containing allowed intervals.
 */
function getAllowedIntervalsForQuery(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=&compare=previous_year';
  var _getDateParamsFromQue2 = getDateParamsFromQuery(query, defaultDateRange),
    period = _getDateParamsFromQue2.period;
  var allowed = [];
  if (period === 'custom') {
    var _getCurrentDates = getCurrentDates(query),
      primary = _getCurrentDates.primary;
    var differenceInDays = getDateDifferenceInDays(primary.before, primary.after);
    if (differenceInDays >= 365) {
      allowed = ['day', 'week', 'month', 'quarter', 'year'];
    } else if (differenceInDays >= 90) {
      allowed = ['day', 'week', 'month', 'quarter'];
    } else if (differenceInDays >= 28) {
      allowed = ['day', 'week', 'month'];
    } else if (differenceInDays >= 7) {
      allowed = ['day', 'week'];
    } else if (differenceInDays > 1 && differenceInDays < 7) {
      allowed = ['day'];
    } else {
      allowed = ['hour', 'day'];
    }
  } else {
    switch (period) {
      case 'today':
      case 'yesterday':
        allowed = ['hour', 'day'];
        break;
      case 'week':
      case 'last_week':
        allowed = ['day'];
        break;
      case 'month':
      case 'last_month':
        allowed = ['day', 'week'];
        break;
      case 'quarter':
      case 'last_quarter':
        allowed = ['day', 'week', 'month'];
        break;
      case 'year':
      case 'last_year':
        allowed = ['day', 'week', 'month', 'quarter'];
        break;
      default:
        allowed = ['day'];
        break;
    }
  }
  return allowed;
}

/**
 * Returns the current interval to use.
 *
 * @param {Query}  query            Current query
 * @param {string} defaultDateRange - the store's default date range
 * @return {string} Current interval.
 */
function getIntervalForQuery(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=&compare=previous_year';
  var allowed = getAllowedIntervalsForQuery(query, defaultDateRange);
  var defaultInterval = allowed[0];
  var current = query.interval || defaultInterval;
  if (query.interval && !allowed.includes(query.interval)) {
    current = defaultInterval;
  }
  return current;
}

/**
 * Returns the current chart type to use.
 *
 * @param {Query}  query           Current query
 * @param {string} query.chartType
 * @return {string} Current chart type.
 */
function getChartTypeForQuery(_ref) {
  var chartType = _ref.chartType;
  if (chartType !== undefined && ['line', 'bar'].includes(chartType)) {
    return chartType;
  }
  return 'line';
}
var dayTicksThreshold = 63;
var weekTicksThreshold = 9;
var defaultTableDateFormat = 'm/d/Y';

/**
 * Returns d3 date formats for the current interval.
 * See https://github.com/d3/d3-time-format for chart formats.
 *
 * @param {string} interval Interval to get date formats for.
 * @param {number} [ticks]  Number of ticks the axis will have.
 * @return {string} Current interval.
 */
function getDateFormatsForIntervalD3(interval) {
  var ticks = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var screenReaderFormat = '%B %-d, %Y';
  var tooltipLabelFormat = '%B %-d, %Y';
  var xFormat = '%Y-%m-%d';
  var x2Format = '%b %Y';
  var tableFormat = defaultTableDateFormat;
  switch (interval) {
    case 'hour':
      screenReaderFormat = '%_I%p %B %-d, %Y';
      tooltipLabelFormat = '%_I%p %b %-d, %Y';
      xFormat = '%_I%p';
      x2Format = '%b %-d, %Y';
      tableFormat = 'h A';
      break;
    case 'day':
      if (ticks < dayTicksThreshold) {
        xFormat = '%-d';
      } else {
        xFormat = '%b';
        x2Format = '%Y';
      }
      break;
    case 'week':
      if (ticks < weekTicksThreshold) {
        xFormat = '%-d';
        x2Format = '%b %Y';
      } else {
        xFormat = '%b';
        x2Format = '%Y';
      }
      // eslint-disable-next-line @wordpress/i18n-translator-comments
      screenReaderFormat = __('Week of %B %-d, %Y', 'woocommerce');
      // eslint-disable-next-line @wordpress/i18n-translator-comments
      tooltipLabelFormat = __('Week of %B %-d, %Y', 'woocommerce');
      break;
    case 'quarter':
    case 'month':
      screenReaderFormat = '%B %Y';
      tooltipLabelFormat = '%B %Y';
      xFormat = '%b';
      x2Format = '%Y';
      break;
    case 'year':
      screenReaderFormat = '%Y';
      tooltipLabelFormat = '%Y';
      xFormat = '%Y';
      break;
  }
  return {
    screenReaderFormat: screenReaderFormat,
    tooltipLabelFormat: tooltipLabelFormat,
    xFormat: xFormat,
    x2Format: x2Format,
    tableFormat: tableFormat
  };
}

/**
 * Returns php date formats for the current interval.
 * See see https://www.php.net/manual/en/datetime.format.php.
 *
 * @param {string} interval Interval to get date formats for.
 * @param {number} [ticks]  Number of ticks the axis will have.
 * @return {string} Current interval.
 */
function getDateFormatsForIntervalPhp(interval) {
  var ticks = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var screenReaderFormat = 'F j, Y';
  var tooltipLabelFormat = 'F j, Y';
  var xFormat = 'Y-m-d';
  var x2Format = 'M Y';
  var tableFormat = defaultTableDateFormat;
  switch (interval) {
    case 'hour':
      screenReaderFormat = 'gA F j, Y';
      tooltipLabelFormat = 'gA M j, Y';
      xFormat = 'gA';
      x2Format = 'M j, Y';
      tableFormat = 'h A';
      break;
    case 'day':
      if (ticks < dayTicksThreshold) {
        xFormat = 'j';
      } else {
        xFormat = 'M';
        x2Format = 'Y';
      }
      break;
    case 'week':
      if (ticks < weekTicksThreshold) {
        xFormat = 'j';
        x2Format = 'M Y';
      } else {
        xFormat = 'M';
        x2Format = 'Y';
      }

      // Since some alphabet letters have php associated formats, we need to escape them first.
      var escapedWeekOfStr = __('Week of', 'woocommerce').replace(/(\w)/g, '\\$1');
      screenReaderFormat = "".concat(escapedWeekOfStr, " F j, Y");
      tooltipLabelFormat = "".concat(escapedWeekOfStr, " F j, Y");
      break;
    case 'quarter':
    case 'month':
      screenReaderFormat = 'F Y';
      tooltipLabelFormat = 'F Y';
      xFormat = 'M';
      x2Format = 'Y';
      break;
    case 'year':
      screenReaderFormat = 'Y';
      tooltipLabelFormat = 'Y';
      xFormat = 'Y';
      break;
  }
  return {
    screenReaderFormat: screenReaderFormat,
    tooltipLabelFormat: tooltipLabelFormat,
    xFormat: xFormat,
    x2Format: x2Format,
    tableFormat: tableFormat
  };
}

/**
 * Returns date formats for the current interval.
 *
 * @param {string} interval      Interval to get date formats for.
 * @param {number} [ticks]       Number of ticks the axis will have.
 * @param {Object} [option]      Options
 * @param {string} [option.type] Date format type, d3 or php, defaults to d3.
 * @return {string} Current interval.
 */
function getDateFormatsForInterval(interval) {
  var ticks = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var option = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {
    type: 'd3'
  };
  switch (option.type) {
    case 'php':
      return getDateFormatsForIntervalPhp(interval, ticks);
    case 'd3':
    default:
      return getDateFormatsForIntervalD3(interval, ticks);
  }
}

/**
 * Gutenberg's moment instance is loaded with i18n values, which are
 * PHP date formats, ie 'LLL: "F j, Y g:i a"'. Override those with translations
 * of moment style js formats.
 *
 * @param {Object} config               Locale config object, from store settings.
 * @param {string} config.userLocale
 * @param {Array}  config.weekdaysShort
 */
function loadLocaleData(_ref2) {
  var userLocale = _ref2.userLocale,
    weekdaysShort = _ref2.weekdaysShort;
  // Don't update if the wp locale hasn't been set yet, like in unit tests, for instance.
  if (moment.locale() !== 'en') {
    moment.updateLocale(userLocale, {
      longDateFormat: {
        L: __('MM/DD/YYYY', 'woocommerce'),
        LL: __('MMMM D, YYYY', 'woocommerce'),
        LLL: __('D MMMM YYYY LT', 'woocommerce'),
        LLLL: __('dddd, D MMMM YYYY LT', 'woocommerce'),
        LT: __('HH:mm', 'woocommerce'),
        // Set LTS to default LTS locale format because we don't have a specific format for it.
        // Reference https://github.com/moment/moment/blob/develop/dist/moment.js
        LTS: 'h:mm:ss A'
      },
      weekdaysMin: weekdaysShort
    });
  }
}
var dateValidationMessages = {
  invalid: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Invalid date', 'woocommerce'),
  future: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Select a date in the past', 'woocommerce'),
  startAfterEnd: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Start date must be before end date', 'woocommerce'),
  endBeforeStart: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Start date must be before end date', 'woocommerce')
};

/**
 * @typedef {Object} validatedDate
 * @property {Object|null} date  - A resulting Moment date object or null, if invalid
 * @property {string}      error - An optional error message if date is invalid
 */

/**
 * Validate text input supplied for a date range.
 *
 * @param {string}      type     - Designate beginning or end of range, eg `before` or `after`.
 * @param {string}      value    - User input value
 * @param {Object|null} [before] - If already designated, the before date parameter
 * @param {Object|null} [after]  - If already designated, the after date parameter
 * @param {string}      format   - The expected date format in a user's locale
 * @return {Object} validatedDate - validated date object
 */
function validateDateInputForRange(type, value, before, after, format) {
  var date = toMoment(format, value);
  if (!date) {
    return {
      date: null,
      error: dateValidationMessages.invalid
    };
  }
  if (moment__WEBPACK_IMPORTED_MODULE_7___default()().isBefore(date, 'day')) {
    return {
      date: null,
      error: dateValidationMessages.future
    };
  }
  if (type === 'after' && before && date.isAfter(before, 'day')) {
    return {
      date: null,
      error: dateValidationMessages.startAfterEnd
    };
  }
  if (type === 'before' && after && date.isBefore(after, 'day')) {
    return {
      date: null,
      error: dateValidationMessages.endBeforeStart
    };
  }
  return {
    date: date
  };
}

/**
 * Checks whether the year is a leap year.
 *
 * @param  year Year to check
 * @return {boolean} True if leap year
 */
function isLeapYear(year) {
  return year % 4 === 0 && year % 100 !== 0 || year % 400 === 0;
}

/**
 * Checks whether a date range contains leap year.
 *
 * @param {string} startDate Start date
 * @param {string} endDate   End date
 * @return {boolean} True if date range contains a leap year
 */
function containsLeapYear(startDate, endDate) {
  // Parse the input dates to get the years
  var startYear = new Date(startDate).getFullYear();
  var endYear = new Date(endDate).getFullYear();
  if (!isNaN(startYear) && !isNaN(endYear)) {
    // Check each year in the range
    for (var year = startYear; year <= endYear; year++) {
      if (isLeapYear(year)) {
        return true;
      }
    }
  }
  return false; // No leap years in the range or invalid date
}

/***/ }),

/***/ "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale sync recursive ^\\.\\/.*$":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./af": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/af.js",
	"./af.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/af.js",
	"./ar": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar.js",
	"./ar-dz": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ma.js",
	"./ar-sa": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-tn.js",
	"./ar.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar.js",
	"./az": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/az.js",
	"./az.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/az.js",
	"./be": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/be.js",
	"./be.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/be.js",
	"./bg": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bg.js",
	"./bg.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bg.js",
	"./bm": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bm.js",
	"./bm.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bm.js",
	"./bn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn.js",
	"./bn-bd": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn-bd.js",
	"./bn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn.js",
	"./bo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bo.js",
	"./bo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bo.js",
	"./br": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/br.js",
	"./br.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/br.js",
	"./bs": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bs.js",
	"./bs.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bs.js",
	"./ca": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ca.js",
	"./ca.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ca.js",
	"./cs": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cs.js",
	"./cs.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cs.js",
	"./cv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cv.js",
	"./cv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cv.js",
	"./cy": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cy.js",
	"./cy.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cy.js",
	"./da": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/da.js",
	"./da.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/da.js",
	"./de": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de.js",
	"./de-at": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-at.js",
	"./de-at.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-at.js",
	"./de-ch": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-ch.js",
	"./de.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de.js",
	"./dv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/dv.js",
	"./dv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/dv.js",
	"./el": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/el.js",
	"./el.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/el.js",
	"./en-au": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-au.js",
	"./en-au.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-au.js",
	"./en-ca": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ca.js",
	"./en-gb": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-gb.js",
	"./en-ie": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ie.js",
	"./en-il": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-il.js",
	"./en-il.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-il.js",
	"./en-in": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-in.js",
	"./en-in.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-in.js",
	"./en-nz": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-nz.js",
	"./en-sg": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-sg.js",
	"./eo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eo.js",
	"./eo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eo.js",
	"./es": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es.js",
	"./es-do": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-do.js",
	"./es-do.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-do.js",
	"./es-mx": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-mx.js",
	"./es-us": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-us.js",
	"./es-us.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-us.js",
	"./es.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es.js",
	"./et": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/et.js",
	"./et.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/et.js",
	"./eu": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eu.js",
	"./eu.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eu.js",
	"./fa": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fa.js",
	"./fa.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fa.js",
	"./fi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fi.js",
	"./fi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fi.js",
	"./fil": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fil.js",
	"./fil.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fil.js",
	"./fo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fo.js",
	"./fo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fo.js",
	"./fr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr.js",
	"./fr-ca": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ch.js",
	"./fr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr.js",
	"./fy": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fy.js",
	"./fy.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fy.js",
	"./ga": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ga.js",
	"./ga.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ga.js",
	"./gd": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gd.js",
	"./gd.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gd.js",
	"./gl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gl.js",
	"./gl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gl.js",
	"./gom-deva": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-latn.js",
	"./gu": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gu.js",
	"./gu.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gu.js",
	"./he": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/he.js",
	"./he.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/he.js",
	"./hi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hi.js",
	"./hi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hi.js",
	"./hr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hr.js",
	"./hr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hr.js",
	"./hu": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hu.js",
	"./hu.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hu.js",
	"./hy-am": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hy-am.js",
	"./id": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/id.js",
	"./id.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/id.js",
	"./is": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/is.js",
	"./is.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/is.js",
	"./it": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it.js",
	"./it-ch": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it-ch.js",
	"./it.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it.js",
	"./ja": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ja.js",
	"./ja.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ja.js",
	"./jv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/jv.js",
	"./jv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/jv.js",
	"./ka": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ka.js",
	"./ka.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ka.js",
	"./kk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kk.js",
	"./kk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kk.js",
	"./km": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/km.js",
	"./km.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/km.js",
	"./kn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kn.js",
	"./kn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kn.js",
	"./ko": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ko.js",
	"./ko.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ko.js",
	"./ku": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ku.js",
	"./ku.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ku.js",
	"./ky": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ky.js",
	"./ky.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ky.js",
	"./lb": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lb.js",
	"./lb.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lb.js",
	"./lo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lo.js",
	"./lo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lo.js",
	"./lt": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lt.js",
	"./lt.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lt.js",
	"./lv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lv.js",
	"./lv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lv.js",
	"./me": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/me.js",
	"./me.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/me.js",
	"./mi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mi.js",
	"./mi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mi.js",
	"./mk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mk.js",
	"./mk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mk.js",
	"./ml": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ml.js",
	"./ml.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ml.js",
	"./mn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mn.js",
	"./mn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mn.js",
	"./mr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mr.js",
	"./mr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mr.js",
	"./ms": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms.js",
	"./ms-my": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms-my.js",
	"./ms.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms.js",
	"./mt": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mt.js",
	"./mt.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mt.js",
	"./my": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/my.js",
	"./my.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/my.js",
	"./nb": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nb.js",
	"./nb.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nb.js",
	"./ne": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ne.js",
	"./ne.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ne.js",
	"./nl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl.js",
	"./nl-be": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl-be.js",
	"./nl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl.js",
	"./nn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nn.js",
	"./nn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nn.js",
	"./oc-lnc": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pa-in.js",
	"./pl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pl.js",
	"./pl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pl.js",
	"./pt": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt.js",
	"./pt-br": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt-br.js",
	"./pt.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt.js",
	"./ro": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ro.js",
	"./ro.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ro.js",
	"./ru": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ru.js",
	"./ru.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ru.js",
	"./sd": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sd.js",
	"./sd.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sd.js",
	"./se": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/se.js",
	"./se.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/se.js",
	"./si": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/si.js",
	"./si.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/si.js",
	"./sk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sk.js",
	"./sk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sk.js",
	"./sl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sl.js",
	"./sl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sl.js",
	"./sq": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sq.js",
	"./sq.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sq.js",
	"./sr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr.js",
	"./sr-cyrl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr.js",
	"./ss": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ss.js",
	"./ss.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ss.js",
	"./sv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sv.js",
	"./sv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sv.js",
	"./sw": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sw.js",
	"./sw.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sw.js",
	"./ta": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ta.js",
	"./ta.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ta.js",
	"./te": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/te.js",
	"./te.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/te.js",
	"./tet": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tet.js",
	"./tet.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tet.js",
	"./tg": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tg.js",
	"./tg.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tg.js",
	"./th": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/th.js",
	"./th.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/th.js",
	"./tk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tk.js",
	"./tk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tk.js",
	"./tl-ph": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tl-ph.js",
	"./tlh": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tlh.js",
	"./tlh.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tlh.js",
	"./tr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tr.js",
	"./tr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tr.js",
	"./tzl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzl.js",
	"./tzl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzl.js",
	"./tzm": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm.js",
	"./tzm-latn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm.js",
	"./ug-cn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ug-cn.js",
	"./uk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uk.js",
	"./uk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uk.js",
	"./ur": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ur.js",
	"./ur.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ur.js",
	"./uz": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz.js",
	"./uz-latn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz-latn.js",
	"./uz.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz.js",
	"./vi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/vi.js",
	"./vi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/vi.js",
	"./x-pseudo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/x-pseudo.js",
	"./yo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/yo.js",
	"./yo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/yo.js",
	"./zh-cn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-tw.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ })

}]);