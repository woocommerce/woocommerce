"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[8306],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/constants.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ TIMEZONELESS_FORMAT)
/* harmony export */ });
const TIMEZONELESS_FORMAT = "yyyy-MM-dd'T'HH:mm:ss";

//# sourceMappingURL=constants.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date_default)
});

// UNUSED EXPORTS: DatePicker

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfDay.mjs
var startOfDay = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfDay.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs
var toDate = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs
var constructFrom = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/addMonths.mjs



/**
 * @name addMonths
 * @category Month Helpers
 * @summary Add the specified number of months to the given date.
 *
 * @description
 * Add the specified number of months to the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param amount - The amount of months to be added.
 *
 * @returns The new date with the months added
 *
 * @example
 * // Add 5 months to 1 September 2014:
 * const result = addMonths(new Date(2014, 8, 1), 5)
 * //=> Sun Feb 01 2015 00:00:00
 *
 * // Add one month to 30 January 2023:
 * const result = addMonths(new Date(2023, 0, 30), 1)
 * //=> Tue Feb 28 2023 00:00:00
 */
function addMonths(date, amount) {
  const _date = (0,toDate/* toDate */.a)(date);
  if (isNaN(amount)) return (0,constructFrom/* constructFrom */.w)(date, NaN);
  if (!amount) {
    // If 0 months, no-op to avoid changing times in the hour before end of DST
    return _date;
  }
  const dayOfMonth = _date.getDate();

  // The JS Date object supports date math by accepting out-of-bounds values for
  // month, day, etc. For example, new Date(2020, 0, 0) returns 31 Dec 2019 and
  // new Date(2020, 13, 1) returns 1 Feb 2021.  This is *almost* the behavior we
  // want except that dates will wrap around the end of a month, meaning that
  // new Date(2020, 13, 31) will return 3 Mar 2021 not 28 Feb 2021 as desired. So
  // we'll default to the end of the desired month by adding 1 to the desired
  // month and using a date of 0 to back up one day to the end of the desired
  // month.
  const endOfDesiredMonth = (0,constructFrom/* constructFrom */.w)(date, _date.getTime());
  endOfDesiredMonth.setMonth(_date.getMonth() + amount + 1, 0);
  const daysInMonth = endOfDesiredMonth.getDate();
  if (dayOfMonth >= daysInMonth) {
    // If we're already at the end of the month, then this is the correct date
    // and we're done.
    return endOfDesiredMonth;
  } else {
    // Otherwise, we now know that setting the original day-of-month value won't
    // cause an overflow, so set the desired day-of-month. Note that we can't
    // just set the date of `endOfDesiredMonth` because that object may have had
    // its time changed in the unusual case where where a DST transition was on
    // the last day of the month and its local time was in the hour skipped or
    // repeated next to a DST transition.  So we use `date` instead which is
    // guaranteed to still have the original time.
    _date.setFullYear(
      endOfDesiredMonth.getFullYear(),
      endOfDesiredMonth.getMonth(),
      dayOfMonth,
    );
    return _date;
  }
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_addMonths = ((/* unused pure expression or super */ null && (addMonths)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/subMonths.mjs


/**
 * @name subMonths
 * @category Month Helpers
 * @summary Subtract the specified number of months from the given date.
 *
 * @description
 * Subtract the specified number of months from the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param amount - The amount of months to be subtracted.
 *
 * @returns The new date with the months subtracted
 *
 * @example
 * // Subtract 5 months from 1 February 2015:
 * const result = subMonths(new Date(2015, 1, 1), 5)
 * //=> Mon Sep 01 2014 00:00:00
 */
function subMonths(date, amount) {
  return addMonths(date, -amount);
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_subMonths = ((/* unused pure expression or super */ null && (subMonths)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/format.mjs + 29 modules
var format = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/format.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/isSameMonth.mjs


/**
 * @name isSameMonth
 * @category Month Helpers
 * @summary Are the given dates in the same month (and year)?
 *
 * @description
 * Are the given dates in the same month (and year)?
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param dateLeft - The first date to check
 * @param dateRight - The second date to check
 *
 * @returns The dates are in the same month (and year)
 *
 * @example
 * // Are 2 September 2014 and 25 September 2014 in the same month?
 * const result = isSameMonth(new Date(2014, 8, 2), new Date(2014, 8, 25))
 * //=> true
 *
 * @example
 * // Are 2 September 2014 and 25 September 2015 in the same month?
 * const result = isSameMonth(new Date(2014, 8, 2), new Date(2015, 8, 25))
 * //=> false
 */
function isSameMonth(dateLeft, dateRight) {
  const _dateLeft = (0,toDate/* toDate */.a)(dateLeft);
  const _dateRight = (0,toDate/* toDate */.a)(dateRight);
  return (
    _dateLeft.getFullYear() === _dateRight.getFullYear() &&
    _dateLeft.getMonth() === _dateRight.getMonth()
  );
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_isSameMonth = ((/* unused pure expression or super */ null && (isSameMonth)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/isEqual.mjs


/**
 * @name isEqual
 * @category Common Helpers
 * @summary Are the given dates equal?
 *
 * @description
 * Are the given dates equal?
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param dateLeft - The first date to compare
 * @param dateRight - The second date to compare
 *
 * @returns The dates are equal
 *
 * @example
 * // Are 2 July 2014 06:30:45.000 and 2 July 2014 06:30:45.500 equal?
 * const result = isEqual(
 *   new Date(2014, 6, 2, 6, 30, 45, 0),
 *   new Date(2014, 6, 2, 6, 30, 45, 500)
 * )
 * //=> false
 */
function isEqual(leftDate, rightDate) {
  const _dateLeft = (0,toDate/* toDate */.a)(leftDate);
  const _dateRight = (0,toDate/* toDate */.a)(rightDate);
  return +_dateLeft === +_dateRight;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_isEqual = ((/* unused pure expression or super */ null && (isEqual)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/isSameDay.mjs


/**
 * @name isSameDay
 * @category Day Helpers
 * @summary Are the given dates in the same day (and year and month)?
 *
 * @description
 * Are the given dates in the same day (and year and month)?
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param dateLeft - The first date to check
 * @param dateRight - The second date to check

 * @returns The dates are in the same day (and year and month)
 *
 * @example
 * // Are 4 September 06:00:00 and 4 September 18:00:00 in the same day?
 * const result = isSameDay(new Date(2014, 8, 4, 6, 0), new Date(2014, 8, 4, 18, 0))
 * //=> true
 *
 * @example
 * // Are 4 September and 4 October in the same day?
 * const result = isSameDay(new Date(2014, 8, 4), new Date(2014, 9, 4))
 * //=> false
 *
 * @example
 * // Are 4 September, 2014 and 4 September, 2015 in the same day?
 * const result = isSameDay(new Date(2014, 8, 4), new Date(2015, 8, 4))
 * //=> false
 */
function isSameDay(dateLeft, dateRight) {
  const dateLeftStartOfDay = (0,startOfDay/* startOfDay */.o)(dateLeft);
  const dateRightStartOfDay = (0,startOfDay/* startOfDay */.o)(dateRight);

  return +dateLeftStartOfDay === +dateRightStartOfDay;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_isSameDay = ((/* unused pure expression or super */ null && (isSameDay)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/addDays.mjs



/**
 * @name addDays
 * @category Day Helpers
 * @summary Add the specified number of days to the given date.
 *
 * @description
 * Add the specified number of days to the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param amount - The amount of days to be added.
 *
 * @returns The new date with the days added
 *
 * @example
 * // Add 10 days to 1 September 2014:
 * const result = addDays(new Date(2014, 8, 1), 10)
 * //=> Thu Sep 11 2014 00:00:00
 */
function addDays(date, amount) {
  const _date = (0,toDate/* toDate */.a)(date);
  if (isNaN(amount)) return (0,constructFrom/* constructFrom */.w)(date, NaN);
  if (!amount) {
    // If 0 days, no-op to avoid changing times in the hour before end of DST
    return _date;
  }
  _date.setDate(_date.getDate() + amount);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_addDays = ((/* unused pure expression or super */ null && (addDays)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/addWeeks.mjs


/**
 * @name addWeeks
 * @category Week Helpers
 * @summary Add the specified number of weeks to the given date.
 *
 * @description
 * Add the specified number of week to the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param amount - The amount of weeks to be added.
 *
 * @returns The new date with the weeks added
 *
 * @example
 * // Add 4 weeks to 1 September 2014:
 * const result = addWeeks(new Date(2014, 8, 1), 4)
 * //=> Mon Sep 29 2014 00:00:00
 */
function addWeeks(date, amount) {
  const days = amount * 7;
  return addDays(date, days);
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_addWeeks = ((/* unused pure expression or super */ null && (addWeeks)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/subWeeks.mjs


/**
 * @name subWeeks
 * @category Week Helpers
 * @summary Subtract the specified number of weeks from the given date.
 *
 * @description
 * Subtract the specified number of weeks from the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param amount - The amount of weeks to be subtracted.
 *
 * @returns The new date with the weeks subtracted
 *
 * @example
 * // Subtract 4 weeks from 1 September 2014:
 * const result = subWeeks(new Date(2014, 8, 1), 4)
 * //=> Mon Aug 04 2014 00:00:00
 */
function subWeeks(date, amount) {
  return addWeeks(date, -amount);
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_subWeeks = ((/* unused pure expression or super */ null && (subWeeks)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfWeek.mjs
var startOfWeek = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfWeek.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/defaultOptions.mjs
var _lib_defaultOptions = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/defaultOptions.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/endOfWeek.mjs



/**
 * The {@link endOfWeek} function options.
 */

/**
 * @name endOfWeek
 * @category Week Helpers
 * @summary Return the end of a week for the given date.
 *
 * @description
 * Return the end of a week for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 * @param options - An object with options
 *
 * @returns The end of a week
 *
 * @example
 * // The end of a week for 2 September 2014 11:55:00:
 * const result = endOfWeek(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Sat Sep 06 2014 23:59:59.999
 *
 * @example
 * // If the week starts on Monday, the end of the week for 2 September 2014 11:55:00:
 * const result = endOfWeek(new Date(2014, 8, 2, 11, 55, 0), { weekStartsOn: 1 })
 * //=> Sun Sep 07 2014 23:59:59.999
 */
function endOfWeek(date, options) {
  const defaultOptions = (0,_lib_defaultOptions/* getDefaultOptions */.q)();
  const weekStartsOn =
    options?.weekStartsOn ??
    options?.locale?.options?.weekStartsOn ??
    defaultOptions.weekStartsOn ??
    defaultOptions.locale?.options?.weekStartsOn ??
    0;

  const _date = (0,toDate/* toDate */.a)(date);
  const day = _date.getDay();
  const diff = (day < weekStartsOn ? -7 : 0) + 6 - (day - weekStartsOn);

  _date.setDate(_date.getDate() + diff);
  _date.setHours(23, 59, 59, 999);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_endOfWeek = ((/* unused pure expression or super */ null && (endOfWeek)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/arrow-right.mjs
// packages/icons/src/library/arrow-right.tsx


var arrow_right_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "m14.5 6.5-1 1 3.7 3.7H4v1.6h13.2l-3.7 3.7 1 1 5.6-5.5z" }) });

//# sourceMappingURL=arrow-right.mjs.map

;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/arrow-left.mjs
// packages/icons/src/library/arrow-left.tsx


var arrow_left_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M20 11.2H6.8l3.7-3.7-1-1L3.9 12l5.6 5.5 1-1-3.7-3.7H20z" }) });

//# sourceMappingURL=arrow-left.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+date@5.48.1/node_modules/@wordpress/date/build-module/index.mjs
var date_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+date@5.48.1/node_modules/@wordpress/date/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/isAfter.mjs


/**
 * @name isAfter
 * @category Common Helpers
 * @summary Is the first date after the second one?
 *
 * @description
 * Is the first date after the second one?
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date that should be after the other one to return true
 * @param dateToCompare - The date to compare with
 *
 * @returns The first date is after the second date
 *
 * @example
 * // Is 10 July 1989 after 11 February 1987?
 * const result = isAfter(new Date(1989, 6, 10), new Date(1987, 1, 11))
 * //=> true
 */
function isAfter(date, dateToCompare) {
  const _date = (0,toDate/* toDate */.a)(date);
  const _dateToCompare = (0,toDate/* toDate */.a)(dateToCompare);
  return _date.getTime() > _dateToCompare.getTime();
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_isAfter = ((/* unused pure expression or super */ null && (isAfter)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/isBefore.mjs


/**
 * @name isBefore
 * @category Common Helpers
 * @summary Is the first date before the second one?
 *
 * @description
 * Is the first date before the second one?
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date that should be before the other one to return true
 * @param dateToCompare - The date to compare with
 *
 * @returns The first date is before the second date
 *
 * @example
 * // Is 10 July 1989 before 11 February 1987?
 * const result = isBefore(new Date(1989, 6, 10), new Date(1987, 1, 11))
 * //=> false
 */
function isBefore(date, dateToCompare) {
  const _date = (0,toDate/* toDate */.a)(date);
  const _dateToCompare = (0,toDate/* toDate */.a)(dateToCompare);
  return +_date < +_dateToCompare;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_isBefore = ((/* unused pure expression or super */ null && (isBefore)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/set.mjs
var set = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/set.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfToday.mjs


/**
 * @name startOfToday
 * @category Day Helpers
 * @summary Return the start of today.
 * @pure false
 *
 * @description
 * Return the start of today.
 *
 * @returns The start of today
 *
 * @example
 * // If today is 6 October 2014:
 * const result = startOfToday()
 * //=> Mon Oct 6 2014 00:00:00
 */
function startOfToday() {
  return (0,startOfDay/* startOfDay */.o)(Date.now());
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_startOfToday = ((/* unused pure expression or super */ null && (startOfToday)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setMonth.mjs + 1 modules
var setMonth = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setMonth.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setYear.mjs



/**
 * @name setYear
 * @category Year Helpers
 * @summary Set the year to the given date.
 *
 * @description
 * Set the year to the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param year - The year of the new date
 *
 * @returns The new date with the year set
 *
 * @example
 * // Set year 2013 to 1 September 2014:
 * const result = setYear(new Date(2014, 8, 1), 2013)
 * //=> Sun Sep 01 2013 00:00:00
 */
function setYear(date, year) {
  const _date = (0,toDate/* toDate */.a)(date);

  // Check if date is Invalid Date because Date.prototype.setFullYear ignores the value of Invalid Date
  if (isNaN(+_date)) {
    return (0,constructFrom/* constructFrom */.w)(date, NaN);
  }

  _date.setFullYear(year);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_setYear = ((/* unused pure expression or super */ null && (setYear)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/addYears.mjs


/**
 * @name addYears
 * @category Year Helpers
 * @summary Add the specified number of years to the given date.
 *
 * @description
 * Add the specified number of years to the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param amount - The amount of years to be added.
 *
 * @returns The new date with the years added
 *
 * @example
 * // Add 5 years to 1 September 2014:
 * const result = addYears(new Date(2014, 8, 1), 5)
 * //=> Sun Sep 01 2019 00:00:00
 */
function addYears(date, amount) {
  return addMonths(date, amount * 12);
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_addYears = ((/* unused pure expression or super */ null && (addYears)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/subYears.mjs


/**
 * @name subYears
 * @category Year Helpers
 * @summary Subtract the specified number of years from the given date.
 *
 * @description
 * Subtract the specified number of years from the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param amount - The amount of years to be subtracted.
 *
 * @returns The new date with the years subtracted
 *
 * @example
 * // Subtract 5 years from 1 September 2014:
 * const result = subYears(new Date(2014, 8, 1), 5)
 * //=> Tue Sep 01 2009 00:00:00
 */
function subYears(date, amount) {
  return addYears(date, -amount);
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_subYears = ((/* unused pure expression or super */ null && (subYears)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/eachDayOfInterval.mjs


/**
 * The {@link eachDayOfInterval} function options.
 */

/**
 * @name eachDayOfInterval
 * @category Interval Helpers
 * @summary Return the array of dates within the specified time interval.
 *
 * @description
 * Return the array of dates within the specified time interval.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param interval - The interval.
 * @param options - An object with options.
 *
 * @returns The array with starts of days from the day of the interval start to the day of the interval end
 *
 * @example
 * // Each day between 6 October 2014 and 10 October 2014:
 * const result = eachDayOfInterval({
 *   start: new Date(2014, 9, 6),
 *   end: new Date(2014, 9, 10)
 * })
 * //=> [
 * //   Mon Oct 06 2014 00:00:00,
 * //   Tue Oct 07 2014 00:00:00,
 * //   Wed Oct 08 2014 00:00:00,
 * //   Thu Oct 09 2014 00:00:00,
 * //   Fri Oct 10 2014 00:00:00
 * // ]
 */
function eachDayOfInterval(interval, options) {
  const startDate = (0,toDate/* toDate */.a)(interval.start);
  const endDate = (0,toDate/* toDate */.a)(interval.end);

  let reversed = +startDate > +endDate;
  const endTime = reversed ? +startDate : +endDate;
  const currentDate = reversed ? endDate : startDate;
  currentDate.setHours(0, 0, 0, 0);

  let step = options?.step ?? 1;
  if (!step) return [];
  if (step < 0) {
    step = -step;
    reversed = !reversed;
  }

  const dates = [];

  while (+currentDate <= endTime) {
    dates.push((0,toDate/* toDate */.a)(currentDate));
    currentDate.setDate(currentDate.getDate() + step);
    currentDate.setHours(0, 0, 0, 0);
  }

  return reversed ? dates.reverse() : dates;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_eachDayOfInterval = ((/* unused pure expression or super */ null && (eachDayOfInterval)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/eachMonthOfInterval.mjs


/**
 * The {@link eachMonthOfInterval} function options.
 */

/**
 * @name eachMonthOfInterval
 * @category Interval Helpers
 * @summary Return the array of months within the specified time interval.
 *
 * @description
 * Return the array of months within the specified time interval.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param interval - The interval
 *
 * @returns The array with starts of months from the month of the interval start to the month of the interval end
 *
 * @example
 * // Each month between 6 February 2014 and 10 August 2014:
 * const result = eachMonthOfInterval({
 *   start: new Date(2014, 1, 6),
 *   end: new Date(2014, 7, 10)
 * })
 * //=> [
 * //   Sat Feb 01 2014 00:00:00,
 * //   Sat Mar 01 2014 00:00:00,
 * //   Tue Apr 01 2014 00:00:00,
 * //   Thu May 01 2014 00:00:00,
 * //   Sun Jun 01 2014 00:00:00,
 * //   Tue Jul 01 2014 00:00:00,
 * //   Fri Aug 01 2014 00:00:00
 * // ]
 */
function eachMonthOfInterval(interval, options) {
  const startDate = (0,toDate/* toDate */.a)(interval.start);
  const endDate = (0,toDate/* toDate */.a)(interval.end);

  let reversed = +startDate > +endDate;
  const endTime = reversed ? +startDate : +endDate;
  const currentDate = reversed ? endDate : startDate;
  currentDate.setHours(0, 0, 0, 0);
  currentDate.setDate(1);

  let step = options?.step ?? 1;
  if (!step) return [];
  if (step < 0) {
    step = -step;
    reversed = !reversed;
  }

  const dates = [];

  while (+currentDate <= endTime) {
    dates.push((0,toDate/* toDate */.a)(currentDate));
    currentDate.setMonth(currentDate.getMonth() + step);
  }

  return reversed ? dates.reverse() : dates;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_eachMonthOfInterval = ((/* unused pure expression or super */ null && (eachMonthOfInterval)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfMonth.mjs


/**
 * @name startOfMonth
 * @category Month Helpers
 * @summary Return the start of a month for the given date.
 *
 * @description
 * Return the start of a month for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 *
 * @returns The start of a month
 *
 * @example
 * // The start of a month for 2 September 2014 11:55:00:
 * const result = startOfMonth(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Mon Sep 01 2014 00:00:00
 */
function startOfMonth(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  _date.setDate(1);
  _date.setHours(0, 0, 0, 0);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_startOfMonth = ((/* unused pure expression or super */ null && (startOfMonth)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/endOfMonth.mjs


/**
 * @name endOfMonth
 * @category Month Helpers
 * @summary Return the end of a month for the given date.
 *
 * @description
 * Return the end of a month for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 *
 * @returns The end of a month
 *
 * @example
 * // The end of a month for 2 September 2014 11:55:00:
 * const result = endOfMonth(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Tue Sep 30 2014 23:59:59.999
 */
function endOfMonth(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  const month = _date.getMonth();
  _date.setFullYear(_date.getFullYear(), month + 1, 0);
  _date.setHours(23, 59, 59, 999);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_endOfMonth = ((/* unused pure expression or super */ null && (endOfMonth)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/eachWeekOfInterval.mjs




/**
 * The {@link eachWeekOfInterval} function options.
 */

/**
 * @name eachWeekOfInterval
 * @category Interval Helpers
 * @summary Return the array of weeks within the specified time interval.
 *
 * @description
 * Return the array of weeks within the specified time interval.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param interval - The interval.
 * @param options - An object with options.
 *
 * @returns The array with starts of weeks from the week of the interval start to the week of the interval end
 *
 * @example
 * // Each week within interval 6 October 2014 - 23 November 2014:
 * const result = eachWeekOfInterval({
 *   start: new Date(2014, 9, 6),
 *   end: new Date(2014, 10, 23)
 * })
 * //=> [
 * //   Sun Oct 05 2014 00:00:00,
 * //   Sun Oct 12 2014 00:00:00,
 * //   Sun Oct 19 2014 00:00:00,
 * //   Sun Oct 26 2014 00:00:00,
 * //   Sun Nov 02 2014 00:00:00,
 * //   Sun Nov 09 2014 00:00:00,
 * //   Sun Nov 16 2014 00:00:00,
 * //   Sun Nov 23 2014 00:00:00
 * // ]
 */
function eachWeekOfInterval(interval, options) {
  const startDate = (0,toDate/* toDate */.a)(interval.start);
  const endDate = (0,toDate/* toDate */.a)(interval.end);

  let reversed = +startDate > +endDate;
  const startDateWeek = reversed
    ? (0,startOfWeek/* startOfWeek */.k)(endDate, options)
    : (0,startOfWeek/* startOfWeek */.k)(startDate, options);
  const endDateWeek = reversed
    ? (0,startOfWeek/* startOfWeek */.k)(startDate, options)
    : (0,startOfWeek/* startOfWeek */.k)(endDate, options);

  // Some timezones switch DST at midnight, making start of day unreliable in these timezones, 3pm is a safe bet
  startDateWeek.setHours(15);
  endDateWeek.setHours(15);

  const endTime = +endDateWeek.getTime();
  let currentDate = startDateWeek;

  let step = options?.step ?? 1;
  if (!step) return [];
  if (step < 0) {
    step = -step;
    reversed = !reversed;
  }

  const dates = [];

  while (+currentDate <= endTime) {
    currentDate.setHours(0);
    dates.push((0,toDate/* toDate */.a)(currentDate));
    currentDate = addWeeks(currentDate, step);
    currentDate.setHours(15);
  }

  return reversed ? dates.reverse() : dates;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_eachWeekOfInterval = ((/* unused pure expression or super */ null && (eachWeekOfInterval)));

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/use-lilius/index.js


let Month = /* @__PURE__ */ (function(Month2) {
  Month2[Month2["JANUARY"] = 0] = "JANUARY";
  Month2[Month2["FEBRUARY"] = 1] = "FEBRUARY";
  Month2[Month2["MARCH"] = 2] = "MARCH";
  Month2[Month2["APRIL"] = 3] = "APRIL";
  Month2[Month2["MAY"] = 4] = "MAY";
  Month2[Month2["JUNE"] = 5] = "JUNE";
  Month2[Month2["JULY"] = 6] = "JULY";
  Month2[Month2["AUGUST"] = 7] = "AUGUST";
  Month2[Month2["SEPTEMBER"] = 8] = "SEPTEMBER";
  Month2[Month2["OCTOBER"] = 9] = "OCTOBER";
  Month2[Month2["NOVEMBER"] = 10] = "NOVEMBER";
  Month2[Month2["DECEMBER"] = 11] = "DECEMBER";
  return Month2;
})({});
let Day = /* @__PURE__ */ (function(Day2) {
  Day2[Day2["SUNDAY"] = 0] = "SUNDAY";
  Day2[Day2["MONDAY"] = 1] = "MONDAY";
  Day2[Day2["TUESDAY"] = 2] = "TUESDAY";
  Day2[Day2["WEDNESDAY"] = 3] = "WEDNESDAY";
  Day2[Day2["THURSDAY"] = 4] = "THURSDAY";
  Day2[Day2["FRIDAY"] = 5] = "FRIDAY";
  Day2[Day2["SATURDAY"] = 6] = "SATURDAY";
  return Day2;
})({});
const inRange = (date, min, max) => (isEqual(date, min) || isAfter(date, min)) && (isEqual(date, max) || isBefore(date, max));
const clearTime = (date) => (0,set/* set */.h)(date, {
  hours: 0,
  minutes: 0,
  seconds: 0,
  milliseconds: 0
});
const useLilius = ({
  weekStartsOn = Day.SUNDAY,
  viewing: initialViewing = /* @__PURE__ */ new Date(),
  selected: initialSelected = [],
  numberOfMonths = 1
} = {}) => {
  const [viewing, setViewing] = (0,react.useState)(initialViewing);
  const viewToday = (0,react.useCallback)(() => setViewing(startOfToday()), [setViewing]);
  const viewMonth = (0,react.useCallback)((month) => setViewing((v) => (0,setMonth/* setMonth */.Z)(v, month)), []);
  const viewPreviousMonth = (0,react.useCallback)(() => setViewing((v) => subMonths(v, 1)), []);
  const viewNextMonth = (0,react.useCallback)(() => setViewing((v) => addMonths(v, 1)), []);
  const viewYear = (0,react.useCallback)((year) => setViewing((v) => setYear(v, year)), []);
  const viewPreviousYear = (0,react.useCallback)(() => setViewing((v) => subYears(v, 1)), []);
  const viewNextYear = (0,react.useCallback)(() => setViewing((v) => addYears(v, 1)), []);
  const [selected, setSelected] = (0,react.useState)(initialSelected.map(clearTime));
  const clearSelected = () => setSelected([]);
  const isSelected = (0,react.useCallback)((date) => selected.findIndex((s) => isEqual(s, date)) > -1, [selected]);
  const select = (0,react.useCallback)((date, replaceExisting) => {
    if (replaceExisting) {
      setSelected(Array.isArray(date) ? date : [date]);
    } else {
      setSelected((selectedItems) => selectedItems.concat(Array.isArray(date) ? date : [date]));
    }
  }, []);
  const deselect = (0,react.useCallback)((date) => setSelected((selectedItems) => Array.isArray(date) ? selectedItems.filter((s) => !date.map((d) => d.getTime()).includes(s.getTime())) : selectedItems.filter((s) => !isEqual(s, date))), []);
  const toggle = (0,react.useCallback)((date, replaceExisting) => isSelected(date) ? deselect(date) : select(date, replaceExisting), [deselect, isSelected, select]);
  const selectRange = (0,react.useCallback)((start, end, replaceExisting) => {
    if (replaceExisting) {
      setSelected(eachDayOfInterval({
        start,
        end
      }));
    } else {
      setSelected((selectedItems) => selectedItems.concat(eachDayOfInterval({
        start,
        end
      })));
    }
  }, []);
  const deselectRange = (0,react.useCallback)((start, end) => {
    setSelected((selectedItems) => selectedItems.filter((s) => !eachDayOfInterval({
      start,
      end
    }).map((d) => d.getTime()).includes(s.getTime())));
  }, []);
  const calendar = (0,react.useMemo)(() => eachMonthOfInterval({
    start: startOfMonth(viewing),
    end: endOfMonth(addMonths(viewing, numberOfMonths - 1))
  }).map((month) => eachWeekOfInterval({
    start: startOfMonth(month),
    end: endOfMonth(month)
  }, {
    weekStartsOn
  }).map((week) => eachDayOfInterval({
    start: (0,startOfWeek/* startOfWeek */.k)(week, {
      weekStartsOn
    }),
    end: endOfWeek(week, {
      weekStartsOn
    })
  }))), [viewing, weekStartsOn, numberOfMonths]);
  return {
    clearTime,
    inRange,
    viewing,
    setViewing,
    viewToday,
    viewMonth,
    viewPreviousMonth,
    viewNextMonth,
    viewYear,
    viewPreviousYear,
    viewNextYear,
    selected,
    setSelected,
    clearSelected,
    isSelected,
    select,
    deselect,
    toggle,
    selectRange,
    deselectRange,
    calendar
  };
};

//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+styled@11.14.1_@em_ee3c5e4b650da353509223cfd78f3fc7/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js
var emotion_styled_base_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+styled@11.14.1_@em_ee3c5e4b650da353509223cfd78f3fc7/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/box-sizing.js
var box_sizing = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/box-sizing.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var view_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/hook.js + 9 modules
var hook = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/hook.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/font-size.js
var font_size = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/font-size.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/heading/hook.js




function useHeading(props) {
  const {
    as: asProp,
    level = 2,
    color = colors_values/* COLORS */.l.theme.foreground,
    isBlock = true,
    weight = config_values/* default */.A.fontWeightHeading,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "Heading");
  const as = asProp || `h${level}`;
  const a11yProps = {};
  if (typeof as === "string" && as[0] !== "h") {
    a11yProps.role = "heading";
    a11yProps["aria-level"] = typeof level === "string" ? parseInt(level) : level;
  }
  const textProps = (0,hook/* default */.A)({
    color,
    isBlock,
    weight,
    size: (0,font_size/* getHeadingFontSize */.fM)(level),
    ...otherProps
  });
  return {
    ...textProps,
    ...a11yProps,
    as
  };
}

//# sourceMappingURL=hook.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/heading/component.js




function UnconnectedHeading(props, forwardedRef) {
  const headerProps = useHeading(props);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(view_component/* default */.A, {
    ...headerProps,
    ref: forwardedRef
  });
}
const Heading = (0,context_connect/* contextConnect */.KZ)(UnconnectedHeading, "Heading");
var component_default = Heading;

//# sourceMappingURL=component.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/styles.js

function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}





const Wrapper = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e105ri6r7"
} : 0)(box_sizing/* boxSizingReset */.r, ";" + ( true ? "" : 0));
const Navigator = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(component/* default */.A,  true ? {
  target: "e105ri6r6"
} : 0)("column-gap:", (0,space/* space */.x)(2), ";display:grid;grid-template-columns:0.5fr repeat( 5, 1fr ) 0.5fr;justify-items:center;margin-bottom:", (0,space/* space */.x)(4), ";" + ( true ? "" : 0));
const ViewPreviousMonthButton = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(build_module_button/* default */.Ay,  true ? {
  target: "e105ri6r5"
} : 0)( true ? {
  name: "sarfoe",
  styles: "grid-column:1/2"
} : 0);
const ViewNextMonthButton = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(build_module_button/* default */.Ay,  true ? {
  target: "e105ri6r4"
} : 0)( true ? {
  name: "1v98r3z",
  styles: "grid-column:7/8"
} : 0);
const NavigatorHeading = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(component_default,  true ? {
  target: "e105ri6r3"
} : 0)("font-size:", config_values/* default */.A.fontSize, ";font-weight:", config_values/* default */.A.fontWeight, ";grid-column:2/7;strong{font-weight:", config_values/* default */.A.fontWeightHeading, ";}" + ( true ? "" : 0));
const Calendar = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e105ri6r2"
} : 0)("column-gap:", (0,space/* space */.x)(2), ";display:grid;grid-template-columns:0.5fr repeat( 5, 1fr ) 0.5fr;justify-items:center;row-gap:", (0,space/* space */.x)(2), ";" + ( true ? "" : 0));
const DayOfWeek = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e105ri6r1"
} : 0)("color:", colors_values/* COLORS */.l.theme.gray[700], ";font-size:", config_values/* default */.A.fontSize, ";line-height:", config_values/* default */.A.fontLineHeightBase, ";" + ( true ? "" : 0));
const DayButton = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(build_module_button/* default */.Ay,  true ? {
  shouldForwardProp: (prop) => !["column", "isSelected", "isToday", "hasEvents"].includes(prop),
  target: "e105ri6r0"
} : 0)("grid-column:", (props) => props.column, ";position:relative;justify-content:center;", (props) => props.disabled && `
		pointer-events: none;
		`, " &&&{border-radius:", config_values/* default */.A.radiusRound, ";height:", (0,space/* space */.x)(7), ";width:", (0,space/* space */.x)(7), ";", (props) => props.isSelected && `
				background: ${colors_values/* COLORS */.l.theme.accent};

				&,
				&:hover:not(:disabled, [aria-disabled=true]) {
					color: ${colors_values/* COLORS */.l.theme.accentInverted};
				}

				&:focus:not(:disabled),
				&:focus:not(:disabled) {
					border: ${config_values/* default */.A.borderWidthFocus} solid currentColor;
				}

				/* Highlight the selected day for high-contrast mode */
				&::after {
					content: '';
					position: absolute;
					pointer-events: none;
					inset: 0;
					border-radius: inherit;
					border: 1px solid transparent;
				}
			`, " ", (props) => !props.isSelected && props.isToday && `
			background: ${colors_values/* COLORS */.l.theme.gray[200]};
			`, ";}", (props) => props.hasEvents && `
		::before {
			border: 2px solid ${props.isSelected ? colors_values/* COLORS */.l.theme.accentInverted : colors_values/* COLORS */.l.theme.accent};
			border-radius: ${config_values/* default */.A.radiusRound};
			content: " ";
			left: 50%;
			position: absolute;
			transform: translate(-50%, 9px);
		}
		`, ";" + ( true ? "" : 0));

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/constants.js
var constants = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/constants.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/index.js










function DatePicker({
  currentDate,
  onChange,
  events = [],
  isInvalidDate,
  onMonthPreviewed,
  startOfWeek: weekStartsOn = 0
}) {
  const date = currentDate ? (0,utils/* inputToDate */.Ch)(currentDate) : /* @__PURE__ */ new Date();
  const {
    calendar,
    viewing,
    setSelected,
    setViewing,
    isSelected,
    viewPreviousMonth,
    viewNextMonth
  } = useLilius({
    selected: [(0,startOfDay/* startOfDay */.o)(date)],
    viewing: (0,startOfDay/* startOfDay */.o)(date),
    weekStartsOn
  });
  const [focusable, setFocusable] = (0,react.useState)((0,startOfDay/* startOfDay */.o)(date));
  const [isFocusWithinCalendar, setIsFocusWithinCalendar] = (0,react.useState)(false);
  const [prevCurrentDate, setPrevCurrentDate] = (0,react.useState)(currentDate);
  if (currentDate !== prevCurrentDate) {
    setPrevCurrentDate(currentDate);
    setSelected([(0,startOfDay/* startOfDay */.o)(date)]);
    setViewing((0,startOfDay/* startOfDay */.o)(date));
    setFocusable((0,startOfDay/* startOfDay */.o)(date));
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(Wrapper, {
    className: "components-datetime__date",
    role: "application",
    "aria-label": (0,build_module.__)("Calendar"),
    children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)(Navigator, {
      children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(ViewPreviousMonthButton, {
        icon: (0,build_module/* isRTL */.V8)() ? arrow_right_default : arrow_left_default,
        variant: "tertiary",
        "aria-label": (0,build_module.__)("View previous month"),
        onClick: () => {
          viewPreviousMonth();
          setFocusable(subMonths(focusable, 1));
          onMonthPreviewed?.((0,format/* format */.GP)(subMonths(viewing, 1), constants/* TIMEZONELESS_FORMAT */.T));
        },
        size: "compact"
      }), /* @__PURE__ */ (0,jsx_runtime.jsxs)(NavigatorHeading, {
        level: 3,
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)("strong", {
          children: (0,date_build_module/* dateI18n */.b5)("F", viewing, -viewing.getTimezoneOffset())
        }), " ", (0,date_build_module/* dateI18n */.b5)("Y", viewing, -viewing.getTimezoneOffset())]
      }), /* @__PURE__ */ (0,jsx_runtime.jsx)(ViewNextMonthButton, {
        icon: (0,build_module/* isRTL */.V8)() ? arrow_left_default : arrow_right_default,
        variant: "tertiary",
        "aria-label": (0,build_module.__)("View next month"),
        onClick: () => {
          viewNextMonth();
          setFocusable(addMonths(focusable, 1));
          onMonthPreviewed?.((0,format/* format */.GP)(addMonths(viewing, 1), constants/* TIMEZONELESS_FORMAT */.T));
        },
        size: "compact"
      })]
    }), /* @__PURE__ */ (0,jsx_runtime.jsxs)(Calendar, {
      onFocus: () => setIsFocusWithinCalendar(true),
      onBlur: () => setIsFocusWithinCalendar(false),
      children: [calendar[0][0].map((day) => /* @__PURE__ */ (0,jsx_runtime.jsx)(DayOfWeek, {
        children: (0,date_build_module/* dateI18n */.b5)("D", day, -day.getTimezoneOffset())
      }, day.toString())), calendar[0].map((week) => week.map((day, index) => {
        if (!isSameMonth(day, viewing)) {
          return null;
        }
        return /* @__PURE__ */ (0,jsx_runtime.jsx)(date_Day, {
          day,
          column: index + 1,
          isSelected: isSelected(day),
          isFocusable: isEqual(day, focusable),
          isFocusAllowed: isFocusWithinCalendar,
          isToday: isSameDay(day, /* @__PURE__ */ new Date()),
          isInvalid: isInvalidDate ? isInvalidDate(day) : false,
          numEvents: events.filter((event) => isSameDay(event.date, day)).length,
          onClick: () => {
            setSelected([day]);
            setFocusable(day);
            onChange?.((0,format/* format */.GP)(
              // Don't change the selected date's time fields.
              new Date(day.getFullYear(), day.getMonth(), day.getDate(), date.getHours(), date.getMinutes(), date.getSeconds(), date.getMilliseconds()),
              constants/* TIMEZONELESS_FORMAT */.T
            ));
          },
          onKeyDown: (event) => {
            let nextFocusable;
            if (event.key === "ArrowLeft") {
              nextFocusable = addDays(day, (0,build_module/* isRTL */.V8)() ? 1 : -1);
            }
            if (event.key === "ArrowRight") {
              nextFocusable = addDays(day, (0,build_module/* isRTL */.V8)() ? -1 : 1);
            }
            if (event.key === "ArrowUp") {
              nextFocusable = subWeeks(day, 1);
            }
            if (event.key === "ArrowDown") {
              nextFocusable = addWeeks(day, 1);
            }
            if (event.key === "PageUp") {
              nextFocusable = subMonths(day, 1);
            }
            if (event.key === "PageDown") {
              nextFocusable = addMonths(day, 1);
            }
            if (event.key === "Home") {
              nextFocusable = (0,startOfWeek/* startOfWeek */.k)(day);
            }
            if (event.key === "End") {
              nextFocusable = (0,startOfDay/* startOfDay */.o)(endOfWeek(day));
            }
            if (nextFocusable) {
              event.preventDefault();
              setFocusable(nextFocusable);
              if (!isSameMonth(nextFocusable, viewing)) {
                setViewing(nextFocusable);
                onMonthPreviewed?.((0,format/* format */.GP)(nextFocusable, constants/* TIMEZONELESS_FORMAT */.T));
              }
            }
          }
        }, day.toString());
      }))]
    })]
  });
}
function date_Day({
  day,
  column,
  isSelected,
  isFocusable,
  isFocusAllowed,
  isToday,
  isInvalid,
  numEvents,
  onClick,
  onKeyDown
}) {
  const ref = (0,react.useRef)();
  (0,react.useEffect)(() => {
    if (ref.current && isFocusable && isFocusAllowed) {
      ref.current.focus();
    }
  }, [isFocusable]);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(DayButton, {
    __next40pxDefaultSize: true,
    ref,
    className: "components-datetime__date__day",
    disabled: isInvalid,
    tabIndex: isFocusable ? 0 : -1,
    "aria-label": getDayLabel(day, isSelected, numEvents),
    column,
    isSelected,
    isToday,
    hasEvents: numEvents > 0,
    onClick,
    onKeyDown,
    children: (0,date_build_module/* dateI18n */.b5)("j", day, -day.getTimezoneOffset())
  });
}
function getDayLabel(date, isSelected, numEvents) {
  const {
    formats
  } = (0,date_build_module/* getSettings */.mt)();
  const localizedDate = (0,date_build_module/* dateI18n */.b5)(formats.date, date, -date.getTimezoneOffset());
  if (isSelected && numEvents > 0) {
    return (0,build_module/* sprintf */.nv)(
      // translators: 1: The calendar date. 2: Number of events on the calendar date.
      (0,build_module._n)("%1$s. Selected. There is %2$d event", "%1$s. Selected. There are %2$d events", numEvents),
      localizedDate,
      numEvents
    );
  } else if (isSelected) {
    return (0,build_module/* sprintf */.nv)(
      // translators: 1: The calendar date.
      (0,build_module.__)("%1$s. Selected"),
      localizedDate
    );
  } else if (numEvents > 0) {
    return (0,build_module/* sprintf */.nv)(
      // translators: 1: The calendar date. 2: Number of events on the calendar date.
      (0,build_module._n)("%1$s. There is %2$d event", "%1$s. There are %2$d events", numEvents),
      localizedDate,
      numEvents
    );
  }
  return localizedDate;
}
var date_default = DatePicker;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/utils.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ch: () => (/* binding */ inputToDate),
/* harmony export */   jh: () => (/* binding */ from24hTo12h),
/* harmony export */   nK: () => (/* binding */ buildPadInputStateReducer),
/* harmony export */   qN: () => (/* binding */ validateInputElementTarget),
/* harmony export */   th: () => (/* binding */ from12hTo24h)
/* harmony export */ });
/* harmony import */ var date_fns__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");
/* harmony import */ var _input_control_reducer_actions__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/reducer/actions.js");


function inputToDate(input) {
  if (typeof input === "string") {
    return new Date(input);
  }
  return (0,date_fns__WEBPACK_IMPORTED_MODULE_0__/* .toDate */ .a)(input);
}
function from12hTo24h(hours, isPm) {
  return isPm ? (hours % 12 + 12) % 24 : hours % 12;
}
function from24hTo12h(hours) {
  return hours % 12 || 12;
}
function buildPadInputStateReducer(pad) {
  return (state, action) => {
    const nextState = {
      ...state
    };
    if (action.type === _input_control_reducer_actions__WEBPACK_IMPORTED_MODULE_1__/* .COMMIT */ .cJ || action.type === _input_control_reducer_actions__WEBPACK_IMPORTED_MODULE_1__/* .PRESS_UP */ .wX || action.type === _input_control_reducer_actions__WEBPACK_IMPORTED_MODULE_1__/* .PRESS_DOWN */ .r7) {
      if (nextState.value !== void 0) {
        nextState.value = nextState.value.toString().padStart(pad, "0");
      }
    }
    return nextState;
  };
}
function validateInputElementTarget(event) {
  var _ownerDocument$defaul;
  const HTMLInputElementInstance = (_ownerDocument$defaul = event.target?.ownerDocument.defaultView?.HTMLInputElement) !== null && _ownerDocument$defaul !== void 0 ? _ownerDocument$defaul : HTMLInputElement;
  if (!(event.target instanceof HTMLInputElementInstance)) {
    return false;
  }
  return event.target.validity.valid;
}

//# sourceMappingURL=utils.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ dropdown_default)
/* harmony export */ });
/* unused harmony export Dropdown */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
/* harmony import */ var _utils_hooks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js");
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");








const UnconnectedDropdown = (props, forwardedRef) => {
  const {
    renderContent,
    renderToggle,
    className,
    contentClassName,
    expandOnMobile,
    headerTitle,
    focusOnMount,
    popoverProps,
    onClose,
    onToggle,
    style,
    open,
    defaultOpen,
    // Deprecated props
    position,
    // From context system
    variant
  } = (0,_context__WEBPACK_IMPORTED_MODULE_1__/* .useContextSystem */ .A)(props, "Dropdown");
  if (position !== void 0) {
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)("`position` prop in wp.components.Dropdown", {
      since: "6.2",
      alternative: "`popoverProps.placement` prop",
      hint: "Note that the `position` prop will override any values passed through the `popoverProps.placement` prop."
    });
  }
  const [fallbackPopoverAnchor, setFallbackPopoverAnchor] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null);
  const containerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useRef)();
  const [isOpen, setIsOpen] = (0,_utils_hooks__WEBPACK_IMPORTED_MODULE_4__/* .useControlledValue */ .j)({
    defaultValue: defaultOpen,
    value: open,
    onChange: onToggle
  });
  function closeIfFocusOutside() {
    if (!containerRef.current) {
      return;
    }
    const {
      ownerDocument
    } = containerRef.current;
    const dialog = ownerDocument?.activeElement?.closest('[role="dialog"]');
    if (!containerRef.current.contains(ownerDocument.activeElement) && (!dialog || dialog.contains(containerRef.current))) {
      close();
    }
  }
  function close() {
    onClose?.();
    setIsOpen(false);
  }
  const args = {
    isOpen: !!isOpen,
    onToggle: () => setIsOpen(!isOpen),
    onClose: close
  };
  const popoverPropsHaveAnchor = !!popoverProps?.anchor || // Note: `anchorRef`, `getAnchorRect` and `anchorRect` are deprecated and
  // be removed from `Popover` from WordPress 6.3
  !!popoverProps?.anchorRef || !!popoverProps?.getAnchorRect || !!popoverProps?.anchorRect;
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    className,
    ref: (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)([containerRef, forwardedRef, setFallbackPopoverAnchor]),
    tabIndex: -1,
    style,
    children: [renderToggle(args), isOpen && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_popover__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      position,
      onClose: close,
      onFocusOutside: closeIfFocusOutside,
      expandOnMobile,
      headerTitle,
      focusOnMount,
      offset: 13,
      anchor: !popoverPropsHaveAnchor ? fallbackPopoverAnchor : void 0,
      variant,
      ...popoverProps,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)("components-dropdown__content", popoverProps?.className, contentClassName),
      children: renderContent(args)
    })]
  });
};
const Dropdown = (0,_context__WEBPACK_IMPORTED_MODULE_8__/* .contextConnect */ .KZ)(UnconnectedDropdown, "Dropdown");
var dropdown_default = Dropdown;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/reducer/actions.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   PL: () => (/* binding */ CHANGE),
/* harmony export */   Qf: () => (/* binding */ DRAG_START),
/* harmony export */   Ry: () => (/* binding */ DRAG_END),
/* harmony export */   Ut: () => (/* binding */ RESET),
/* harmony export */   W3: () => (/* binding */ CONTROL),
/* harmony export */   bR: () => (/* binding */ PRESS_ENTER),
/* harmony export */   cJ: () => (/* binding */ COMMIT),
/* harmony export */   j: () => (/* binding */ DRAG),
/* harmony export */   r7: () => (/* binding */ PRESS_DOWN),
/* harmony export */   uY: () => (/* binding */ INVALIDATE),
/* harmony export */   wX: () => (/* binding */ PRESS_UP)
/* harmony export */ });
const CHANGE = "CHANGE";
const COMMIT = "COMMIT";
const CONTROL = "CONTROL";
const DRAG_END = "DRAG_END";
const DRAG_START = "DRAG_START";
const DRAG = "DRAG";
const INVALIDATE = "INVALIDATE";
const PRESS_DOWN = "PRESS_DOWN";
const PRESS_ENTER = "PRESS_ENTER";
const PRESS_UP = "PRESS_UP";
const RESET = "RESET";

//# sourceMappingURL=actions.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   j: () => (/* binding */ useControlledValue)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

function useControlledValue({
  defaultValue,
  onChange,
  value: valueProp
}) {
  const hasValue = typeof valueProp !== "undefined";
  const initialValue = hasValue ? valueProp : defaultValue;
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(initialValue);
  const value = hasValue ? valueProp : state;
  const uncontrolledSetValue = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)((nextValue, ...args) => {
    setState(nextValue);
    onChange?.(nextValue, ...args);
  }, [onChange]);
  let setValue;
  if (hasValue && typeof onChange === "function") {
    setValue = onChange;
  } else if (!hasValue && typeof onChange === "function") {
    setValue = uncontrolledSetValue;
  } else {
    setValue = setState;
  }
  return [value, setValue];
}

//# sourceMappingURL=use-controlled-value.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+date@5.48.1/node_modules/@wordpress/date/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   b5: () => (/* binding */ dateI18n),
/* harmony export */   mt: () => (/* binding */ getSettings)
/* harmony export */ });
/* unused harmony exports __experimentalGetSettings, date, format, getDate, gmdate, gmdateI18n, humanTimeDiff, isInTheFuture, setSettings */
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var moment_timezone_moment_timezone_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/moment-timezone@0.5.48/node_modules/moment-timezone/moment-timezone.js");
/* harmony import */ var moment_timezone_moment_timezone_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(moment_timezone_moment_timezone_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var moment_timezone_moment_timezone_utils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/moment-timezone@0.5.48/node_modules/moment-timezone/moment-timezone-utils.js");
/* harmony import */ var moment_timezone_moment_timezone_utils_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(moment_timezone_moment_timezone_utils_js__WEBPACK_IMPORTED_MODULE_2__);
// packages/date/src/index.ts




var WP_ZONE = "WP";
var VALID_UTC_OFFSET = /^[+-][0-1][0-9](:?[0-9][0-9])?$/;
var settings = {
  l10n: {
    locale: "en",
    months: [
      "January",
      "February",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
      "September",
      "October",
      "November",
      "December"
    ],
    monthsShort: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec"
    ],
    weekdays: [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday"
    ],
    weekdaysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
    meridiem: { am: "am", pm: "pm", AM: "AM", PM: "PM" },
    relative: {
      future: "%s from now",
      past: "%s ago",
      s: "a few seconds",
      ss: "%d seconds",
      m: "a minute",
      mm: "%d minutes",
      h: "an hour",
      hh: "%d hours",
      d: "a day",
      dd: "%d days",
      M: "a month",
      MM: "%d months",
      y: "a year",
      yy: "%d years"
    },
    startOfWeek: 0
  },
  formats: {
    time: "g:i a",
    date: "F j, Y",
    datetime: "F j, Y g:i a",
    datetimeAbbreviated: "M j, Y g:i a"
  },
  timezone: { offset: 0, offsetFormatted: "0", string: "", abbr: "" }
};
function setSettings(dateSettings) {
  settings = dateSettings;
  setupWPTimezone();
  if (momentLib.locales().includes(dateSettings.l10n.locale)) {
    if (momentLib.localeData(dateSettings.l10n.locale).longDateFormat("LTS") === null) {
      momentLib.defineLocale(dateSettings.l10n.locale, null);
    } else {
      return;
    }
  }
  const currentLocale = momentLib.locale();
  momentLib.defineLocale(dateSettings.l10n.locale, {
    // Inherit anything missing from English. We don't load
    // moment-with-locales.js so English is all there is.
    parentLocale: "en",
    months: dateSettings.l10n.months,
    monthsShort: dateSettings.l10n.monthsShort,
    weekdays: dateSettings.l10n.weekdays,
    weekdaysShort: dateSettings.l10n.weekdaysShort,
    meridiem(hour, minute, isLowercase) {
      if (hour < 12) {
        return isLowercase ? dateSettings.l10n.meridiem.am : dateSettings.l10n.meridiem.AM;
      }
      return isLowercase ? dateSettings.l10n.meridiem.pm : dateSettings.l10n.meridiem.PM;
    },
    longDateFormat: {
      LT: dateSettings.formats.time,
      LTS: momentLib.localeData("en").longDateFormat("LTS"),
      L: momentLib.localeData("en").longDateFormat("L"),
      LL: dateSettings.formats.date,
      LLL: dateSettings.formats.datetime,
      LLLL: momentLib.localeData("en").longDateFormat("LLLL")
    },
    // From human_time_diff?
    // Set to `(number, withoutSuffix, key, isFuture) => {}` instead.
    relativeTime: dateSettings.l10n.relative
  });
  momentLib.locale(currentLocale);
}
function getSettings() {
  return settings;
}
function __experimentalGetSettings() {
  deprecated("wp.date.__experimentalGetSettings", {
    since: "6.1",
    alternative: "wp.date.getSettings"
  });
  return getSettings();
}
var wpZonePacked;
function ensureWPTimezone() {
  if (!momentLib.tz.zone(WP_ZONE)) {
    if (wpZonePacked) {
      momentLib.tz.add(wpZonePacked);
    } else {
      setupWPTimezone();
    }
  }
}
function setupWPTimezone() {
  const currentTimezone = moment__WEBPACK_IMPORTED_MODULE_0___default().tz.zone(settings.timezone.string);
  let packed;
  if (currentTimezone) {
    packed = moment__WEBPACK_IMPORTED_MODULE_0___default().tz.pack({
      name: WP_ZONE,
      abbrs: currentTimezone.abbrs,
      untils: currentTimezone.untils,
      offsets: currentTimezone.offsets
    });
  } else {
    packed = moment__WEBPACK_IMPORTED_MODULE_0___default().tz.pack({
      name: WP_ZONE,
      abbrs: [WP_ZONE],
      untils: [null],
      offsets: [-settings.timezone.offset * 60 || 0]
    });
  }
  wpZonePacked = packed;
  moment__WEBPACK_IMPORTED_MODULE_0___default().tz.add(packed);
}
var MINUTE_IN_SECONDS = 60;
var HOUR_IN_MINUTES = 60;
var HOUR_IN_SECONDS = 60 * MINUTE_IN_SECONDS;
var formatMap = {
  // Day.
  d: "DD",
  D: "ddd",
  j: "D",
  l: "dddd",
  N: "E",
  /**
   * Gets the ordinal suffix.
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  S(momentDate) {
    const num = momentDate.format("D");
    const withOrdinal = momentDate.format("Do");
    return withOrdinal.replace(num, "");
  },
  w: "d",
  /**
   * Gets the day of the year (zero-indexed).
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  z(momentDate) {
    return (parseInt(momentDate.format("DDD"), 10) - 1).toString();
  },
  // Week.
  W: "W",
  // Month.
  F: "MMMM",
  m: "MM",
  M: "MMM",
  n: "M",
  /**
   * Gets the days in the month.
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  t(momentDate) {
    return momentDate.daysInMonth();
  },
  // Year.
  /**
   * Gets whether the current year is a leap year.
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  L(momentDate) {
    return momentDate.isLeapYear() ? "1" : "0";
  },
  o: "GGGG",
  Y: "YYYY",
  y: "YY",
  // Time.
  a: "a",
  A: "A",
  /**
   * Gets the current time in Swatch Internet Time (.beats).
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  B(momentDate) {
    const timezoned = moment__WEBPACK_IMPORTED_MODULE_0___default()(momentDate).utcOffset(60);
    const seconds = parseInt(timezoned.format("s"), 10), minutes = parseInt(timezoned.format("m"), 10), hours = parseInt(timezoned.format("H"), 10);
    return parseInt(
      ((seconds + minutes * MINUTE_IN_SECONDS + hours * HOUR_IN_SECONDS) / 86.4).toString(),
      10
    );
  },
  g: "h",
  G: "H",
  h: "hh",
  H: "HH",
  i: "mm",
  s: "ss",
  u: "SSSSSS",
  v: "SSS",
  // Timezone.
  e: "zz",
  /**
   * Gets whether the timezone is in DST currently.
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  I(momentDate) {
    return momentDate.isDST() ? "1" : "0";
  },
  O: "ZZ",
  P: "Z",
  T: "z",
  /**
   * Gets the timezone offset in seconds.
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  Z(momentDate) {
    const offset = momentDate.format("Z");
    const sign = offset[0] === "-" ? -1 : 1;
    const parts = offset.substring(1).split(":").map((n) => parseInt(n, 10));
    return sign * (parts[0] * HOUR_IN_MINUTES + parts[1]) * MINUTE_IN_SECONDS;
  },
  // Full date/time.
  c: "YYYY-MM-DDTHH:mm:ssZ",
  // .toISOString.
  /**
   * Formats the date as RFC2822.
   *
   * @param momentDate Moment instance.
   *
   * @return Formatted date.
   */
  r(momentDate) {
    return momentDate.locale("en").format("ddd, DD MMM YYYY HH:mm:ss ZZ");
  },
  U: "X"
};
function format(dateFormat, dateValue = /* @__PURE__ */ new Date()) {
  let i, char;
  const newFormat = [];
  const momentDate = moment__WEBPACK_IMPORTED_MODULE_0___default()(dateValue);
  for (i = 0; i < dateFormat.length; i++) {
    char = dateFormat[i];
    if ("\\" === char) {
      i++;
      newFormat.push("[" + dateFormat[i] + "]");
      continue;
    }
    if (char in formatMap) {
      const formatter = formatMap[char];
      if (typeof formatter !== "string") {
        newFormat.push("[" + formatter(momentDate) + "]");
      } else {
        newFormat.push(formatter);
      }
    } else {
      newFormat.push("[" + char + "]");
    }
  }
  return momentDate.format(newFormat.join("[]"));
}
function date(dateFormat, dateValue = /* @__PURE__ */ new Date(), timezone) {
  const dateMoment = buildMoment(dateValue, timezone);
  return format(dateFormat, dateMoment);
}
function gmdate(dateFormat, dateValue = /* @__PURE__ */ new Date()) {
  const dateMoment = momentLib(dateValue).utc();
  return format(dateFormat, dateMoment);
}
function dateI18n(dateFormat, dateValue = /* @__PURE__ */ new Date(), timezone) {
  if (true === timezone) {
    return gmdateI18n(dateFormat, dateValue);
  }
  if (false === timezone) {
    timezone = void 0;
  }
  const dateMoment = buildMoment(dateValue, timezone);
  dateMoment.locale(settings.l10n.locale);
  return format(dateFormat, dateMoment);
}
function gmdateI18n(dateFormat, dateValue = /* @__PURE__ */ new Date()) {
  const dateMoment = moment__WEBPACK_IMPORTED_MODULE_0___default()(dateValue).utc();
  dateMoment.locale(settings.l10n.locale);
  return format(dateFormat, dateMoment);
}
function isInTheFuture(dateValue) {
  ensureWPTimezone();
  const now = momentLib.tz(WP_ZONE);
  const momentObject = momentLib.tz(dateValue, WP_ZONE);
  return momentObject.isAfter(now);
}
function getDate(dateString) {
  ensureWPTimezone();
  if (!dateString) {
    return momentLib.tz(WP_ZONE).toDate();
  }
  return momentLib.tz(dateString, WP_ZONE).toDate();
}
function humanTimeDiff(from, to) {
  ensureWPTimezone();
  const fromMoment = momentLib.tz(from, WP_ZONE);
  const toMoment = to ? momentLib.tz(to, WP_ZONE) : momentLib.tz(WP_ZONE);
  return fromMoment.from(toMoment);
}
function buildMoment(dateValue, timezone = "") {
  const dateMoment = moment__WEBPACK_IMPORTED_MODULE_0___default()(dateValue);
  if (timezone !== "") {
    return isUTCOffset(timezone) ? dateMoment.utcOffset(timezone) : (
      // A false isUTCOffset() guarantees that timezone is a string.
      dateMoment.tz(timezone)
    );
  }
  if (settings.timezone.string) {
    return dateMoment.tz(settings.timezone.string);
  }
  return dateMoment.utcOffset(+settings.timezone.offset);
}
function isUTCOffset(offset) {
  if ("number" === typeof offset) {
    return true;
  }
  return VALID_UTC_OFFSET.test(offset);
}
setupWPTimezone();

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/calendar.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ calendar_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var calendar_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z" }) });

//# sourceMappingURL=calendar.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/defaultOptions.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   q: () => (/* binding */ getDefaultOptions)
/* harmony export */ });
/* unused harmony export setDefaultOptions */
let defaultOptions = {};

function getDefaultOptions() {
  return defaultOptions;
}

function setDefaultOptions(newOptions) {
  defaultOptions = newOptions;
}


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   w: () => (/* binding */ constructFrom)
/* harmony export */ });
/**
 * @name constructFrom
 * @category Generic Helpers
 * @summary Constructs a date using the reference date and the value
 *
 * @description
 * The function constructs a new date using the constructor from the reference
 * date and the given value. It helps to build generic functions that accept
 * date extensions.
 *
 * It defaults to `Date` if the passed reference date is a number or a string.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The reference date to take constructor from
 * @param value - The value to create the date
 *
 * @returns Date initialized using the given date and value
 *
 * @example
 * import { constructFrom } from 'date-fns'
 *
 * // A function that clones a date preserving the original type
 * function cloneDate<DateType extends Date(date: DateType): DateType {
 *   return constructFrom(
 *     date, // Use contrustor from the given date
 *     date.getTime() // Use the date value to create a new date
 *   )
 * }
 */
function constructFrom(date, value) {
  if (date instanceof Date) {
    return new date.constructor(value);
  } else {
    return new Date(value);
  }
}

// Fallback for modularized imports:
/* unused harmony default export */ var __WEBPACK_DEFAULT_EXPORT__ = ((/* unused pure expression or super */ null && (constructFrom)));


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/format.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  GP: () => (/* binding */ format)
});

// UNUSED EXPORTS: default, formatDate, formatters, longFormatters

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/en-US/_lib/formatDistance.mjs
const formatDistanceLocale = {
  lessThanXSeconds: {
    one: "less than a second",
    other: "less than {{count}} seconds",
  },

  xSeconds: {
    one: "1 second",
    other: "{{count}} seconds",
  },

  halfAMinute: "half a minute",

  lessThanXMinutes: {
    one: "less than a minute",
    other: "less than {{count}} minutes",
  },

  xMinutes: {
    one: "1 minute",
    other: "{{count}} minutes",
  },

  aboutXHours: {
    one: "about 1 hour",
    other: "about {{count}} hours",
  },

  xHours: {
    one: "1 hour",
    other: "{{count}} hours",
  },

  xDays: {
    one: "1 day",
    other: "{{count}} days",
  },

  aboutXWeeks: {
    one: "about 1 week",
    other: "about {{count}} weeks",
  },

  xWeeks: {
    one: "1 week",
    other: "{{count}} weeks",
  },

  aboutXMonths: {
    one: "about 1 month",
    other: "about {{count}} months",
  },

  xMonths: {
    one: "1 month",
    other: "{{count}} months",
  },

  aboutXYears: {
    one: "about 1 year",
    other: "about {{count}} years",
  },

  xYears: {
    one: "1 year",
    other: "{{count}} years",
  },

  overXYears: {
    one: "over 1 year",
    other: "over {{count}} years",
  },

  almostXYears: {
    one: "almost 1 year",
    other: "almost {{count}} years",
  },
};

const formatDistance = (token, count, options) => {
  let result;

  const tokenValue = formatDistanceLocale[token];
  if (typeof tokenValue === "string") {
    result = tokenValue;
  } else if (count === 1) {
    result = tokenValue.one;
  } else {
    result = tokenValue.other.replace("{{count}}", count.toString());
  }

  if (options?.addSuffix) {
    if (options.comparison && options.comparison > 0) {
      return "in " + result;
    } else {
      return result + " ago";
    }
  }

  return result;
};

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/_lib/buildFormatLongFn.mjs
function buildFormatLongFn(args) {
  return (options = {}) => {
    // TODO: Remove String()
    const width = options.width ? String(options.width) : args.defaultWidth;
    const format = args.formats[width] || args.formats[args.defaultWidth];
    return format;
  };
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/en-US/_lib/formatLong.mjs


const dateFormats = {
  full: "EEEE, MMMM do, y",
  long: "MMMM do, y",
  medium: "MMM d, y",
  short: "MM/dd/yyyy",
};

const timeFormats = {
  full: "h:mm:ss a zzzz",
  long: "h:mm:ss a z",
  medium: "h:mm:ss a",
  short: "h:mm a",
};

const dateTimeFormats = {
  full: "{{date}} 'at' {{time}}",
  long: "{{date}} 'at' {{time}}",
  medium: "{{date}}, {{time}}",
  short: "{{date}}, {{time}}",
};

const formatLong = {
  date: buildFormatLongFn({
    formats: dateFormats,
    defaultWidth: "full",
  }),

  time: buildFormatLongFn({
    formats: timeFormats,
    defaultWidth: "full",
  }),

  dateTime: buildFormatLongFn({
    formats: dateTimeFormats,
    defaultWidth: "full",
  }),
};

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/en-US/_lib/formatRelative.mjs
const formatRelativeLocale = {
  lastWeek: "'last' eeee 'at' p",
  yesterday: "'yesterday at' p",
  today: "'today at' p",
  tomorrow: "'tomorrow at' p",
  nextWeek: "eeee 'at' p",
  other: "P",
};

const formatRelative = (token, _date, _baseDate, _options) =>
  formatRelativeLocale[token];

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/_lib/buildLocalizeFn.mjs
/* eslint-disable no-unused-vars */

/**
 * The localize function argument callback which allows to convert raw value to
 * the actual type.
 *
 * @param value - The value to convert
 *
 * @returns The converted value
 */

/**
 * The map of localized values for each width.
 */

/**
 * The index type of the locale unit value. It types conversion of units of
 * values that don't start at 0 (i.e. quarters).
 */

/**
 * Converts the unit value to the tuple of values.
 */

/**
 * The tuple of localized era values. The first element represents BC,
 * the second element represents AD.
 */

/**
 * The tuple of localized quarter values. The first element represents Q1.
 */

/**
 * The tuple of localized day values. The first element represents Sunday.
 */

/**
 * The tuple of localized month values. The first element represents January.
 */

function buildLocalizeFn(args) {
  return (value, options) => {
    const context = options?.context ? String(options.context) : "standalone";

    let valuesArray;
    if (context === "formatting" && args.formattingValues) {
      const defaultWidth = args.defaultFormattingWidth || args.defaultWidth;
      const width = options?.width ? String(options.width) : defaultWidth;

      valuesArray =
        args.formattingValues[width] || args.formattingValues[defaultWidth];
    } else {
      const defaultWidth = args.defaultWidth;
      const width = options?.width ? String(options.width) : args.defaultWidth;

      valuesArray = args.values[width] || args.values[defaultWidth];
    }
    const index = args.argumentCallback ? args.argumentCallback(value) : value;

    // @ts-expect-error - For some reason TypeScript just don't want to match it, no matter how hard we try. I challenge you to try to remove it!
    return valuesArray[index];
  };
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/en-US/_lib/localize.mjs


const eraValues = {
  narrow: ["B", "A"],
  abbreviated: ["BC", "AD"],
  wide: ["Before Christ", "Anno Domini"],
};

const quarterValues = {
  narrow: ["1", "2", "3", "4"],
  abbreviated: ["Q1", "Q2", "Q3", "Q4"],
  wide: ["1st quarter", "2nd quarter", "3rd quarter", "4th quarter"],
};

// Note: in English, the names of days of the week and months are capitalized.
// If you are making a new locale based on this one, check if the same is true for the language you're working on.
// Generally, formatted dates should look like they are in the middle of a sentence,
// e.g. in Spanish language the weekdays and months should be in the lowercase.
const monthValues = {
  narrow: ["J", "F", "M", "A", "M", "J", "J", "A", "S", "O", "N", "D"],
  abbreviated: [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
  ],

  wide: [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ],
};

const dayValues = {
  narrow: ["S", "M", "T", "W", "T", "F", "S"],
  short: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
  abbreviated: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
  wide: [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ],
};

const dayPeriodValues = {
  narrow: {
    am: "a",
    pm: "p",
    midnight: "mi",
    noon: "n",
    morning: "morning",
    afternoon: "afternoon",
    evening: "evening",
    night: "night",
  },
  abbreviated: {
    am: "AM",
    pm: "PM",
    midnight: "midnight",
    noon: "noon",
    morning: "morning",
    afternoon: "afternoon",
    evening: "evening",
    night: "night",
  },
  wide: {
    am: "a.m.",
    pm: "p.m.",
    midnight: "midnight",
    noon: "noon",
    morning: "morning",
    afternoon: "afternoon",
    evening: "evening",
    night: "night",
  },
};

const formattingDayPeriodValues = {
  narrow: {
    am: "a",
    pm: "p",
    midnight: "mi",
    noon: "n",
    morning: "in the morning",
    afternoon: "in the afternoon",
    evening: "in the evening",
    night: "at night",
  },
  abbreviated: {
    am: "AM",
    pm: "PM",
    midnight: "midnight",
    noon: "noon",
    morning: "in the morning",
    afternoon: "in the afternoon",
    evening: "in the evening",
    night: "at night",
  },
  wide: {
    am: "a.m.",
    pm: "p.m.",
    midnight: "midnight",
    noon: "noon",
    morning: "in the morning",
    afternoon: "in the afternoon",
    evening: "in the evening",
    night: "at night",
  },
};

const ordinalNumber = (dirtyNumber, _options) => {
  const number = Number(dirtyNumber);

  // If ordinal numbers depend on context, for example,
  // if they are different for different grammatical genders,
  // use `options.unit`.
  //
  // `unit` can be 'year', 'quarter', 'month', 'week', 'date', 'dayOfYear',
  // 'day', 'hour', 'minute', 'second'.

  const rem100 = number % 100;
  if (rem100 > 20 || rem100 < 10) {
    switch (rem100 % 10) {
      case 1:
        return number + "st";
      case 2:
        return number + "nd";
      case 3:
        return number + "rd";
    }
  }
  return number + "th";
};

const localize = {
  ordinalNumber,

  era: buildLocalizeFn({
    values: eraValues,
    defaultWidth: "wide",
  }),

  quarter: buildLocalizeFn({
    values: quarterValues,
    defaultWidth: "wide",
    argumentCallback: (quarter) => quarter - 1,
  }),

  month: buildLocalizeFn({
    values: monthValues,
    defaultWidth: "wide",
  }),

  day: buildLocalizeFn({
    values: dayValues,
    defaultWidth: "wide",
  }),

  dayPeriod: buildLocalizeFn({
    values: dayPeriodValues,
    defaultWidth: "wide",
    formattingValues: formattingDayPeriodValues,
    defaultFormattingWidth: "wide",
  }),
};

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/_lib/buildMatchFn.mjs
function buildMatchFn(args) {
  return (string, options = {}) => {
    const width = options.width;

    const matchPattern =
      (width && args.matchPatterns[width]) ||
      args.matchPatterns[args.defaultMatchWidth];
    const matchResult = string.match(matchPattern);

    if (!matchResult) {
      return null;
    }
    const matchedString = matchResult[0];

    const parsePatterns =
      (width && args.parsePatterns[width]) ||
      args.parsePatterns[args.defaultParseWidth];

    const key = Array.isArray(parsePatterns)
      ? findIndex(parsePatterns, (pattern) => pattern.test(matchedString))
      : // eslint-disable-next-line @typescript-eslint/no-explicit-any -- I challange you to fix the type
        findKey(parsePatterns, (pattern) => pattern.test(matchedString));

    let value;

    value = args.valueCallback ? args.valueCallback(key) : key;
    value = options.valueCallback
      ? // eslint-disable-next-line @typescript-eslint/no-explicit-any -- I challange you to fix the type
        options.valueCallback(value)
      : value;

    const rest = string.slice(matchedString.length);

    return { value, rest };
  };
}

function findKey(object, predicate) {
  for (const key in object) {
    if (
      Object.prototype.hasOwnProperty.call(object, key) &&
      predicate(object[key])
    ) {
      return key;
    }
  }
  return undefined;
}

function findIndex(array, predicate) {
  for (let key = 0; key < array.length; key++) {
    if (predicate(array[key])) {
      return key;
    }
  }
  return undefined;
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/_lib/buildMatchPatternFn.mjs
function buildMatchPatternFn(args) {
  return (string, options = {}) => {
    const matchResult = string.match(args.matchPattern);
    if (!matchResult) return null;
    const matchedString = matchResult[0];

    const parseResult = string.match(args.parsePattern);
    if (!parseResult) return null;
    let value = args.valueCallback
      ? args.valueCallback(parseResult[0])
      : parseResult[0];

    // eslint-disable-next-line @typescript-eslint/no-explicit-any -- I challange you to fix the type
    value = options.valueCallback ? options.valueCallback(value) : value;

    const rest = string.slice(matchedString.length);

    return { value, rest };
  };
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/en-US/_lib/match.mjs



const matchOrdinalNumberPattern = /^(\d+)(th|st|nd|rd)?/i;
const parseOrdinalNumberPattern = /\d+/i;

const matchEraPatterns = {
  narrow: /^(b|a)/i,
  abbreviated: /^(b\.?\s?c\.?|b\.?\s?c\.?\s?e\.?|a\.?\s?d\.?|c\.?\s?e\.?)/i,
  wide: /^(before christ|before common era|anno domini|common era)/i,
};
const parseEraPatterns = {
  any: [/^b/i, /^(a|c)/i],
};

const matchQuarterPatterns = {
  narrow: /^[1234]/i,
  abbreviated: /^q[1234]/i,
  wide: /^[1234](th|st|nd|rd)? quarter/i,
};
const parseQuarterPatterns = {
  any: [/1/i, /2/i, /3/i, /4/i],
};

const matchMonthPatterns = {
  narrow: /^[jfmasond]/i,
  abbreviated: /^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i,
  wide: /^(january|february|march|april|may|june|july|august|september|october|november|december)/i,
};
const parseMonthPatterns = {
  narrow: [
    /^j/i,
    /^f/i,
    /^m/i,
    /^a/i,
    /^m/i,
    /^j/i,
    /^j/i,
    /^a/i,
    /^s/i,
    /^o/i,
    /^n/i,
    /^d/i,
  ],

  any: [
    /^ja/i,
    /^f/i,
    /^mar/i,
    /^ap/i,
    /^may/i,
    /^jun/i,
    /^jul/i,
    /^au/i,
    /^s/i,
    /^o/i,
    /^n/i,
    /^d/i,
  ],
};

const matchDayPatterns = {
  narrow: /^[smtwf]/i,
  short: /^(su|mo|tu|we|th|fr|sa)/i,
  abbreviated: /^(sun|mon|tue|wed|thu|fri|sat)/i,
  wide: /^(sunday|monday|tuesday|wednesday|thursday|friday|saturday)/i,
};
const parseDayPatterns = {
  narrow: [/^s/i, /^m/i, /^t/i, /^w/i, /^t/i, /^f/i, /^s/i],
  any: [/^su/i, /^m/i, /^tu/i, /^w/i, /^th/i, /^f/i, /^sa/i],
};

const matchDayPeriodPatterns = {
  narrow: /^(a|p|mi|n|(in the|at) (morning|afternoon|evening|night))/i,
  any: /^([ap]\.?\s?m\.?|midnight|noon|(in the|at) (morning|afternoon|evening|night))/i,
};
const parseDayPeriodPatterns = {
  any: {
    am: /^a/i,
    pm: /^p/i,
    midnight: /^mi/i,
    noon: /^no/i,
    morning: /morning/i,
    afternoon: /afternoon/i,
    evening: /evening/i,
    night: /night/i,
  },
};

const match = {
  ordinalNumber: buildMatchPatternFn({
    matchPattern: matchOrdinalNumberPattern,
    parsePattern: parseOrdinalNumberPattern,
    valueCallback: (value) => parseInt(value, 10),
  }),

  era: buildMatchFn({
    matchPatterns: matchEraPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseEraPatterns,
    defaultParseWidth: "any",
  }),

  quarter: buildMatchFn({
    matchPatterns: matchQuarterPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseQuarterPatterns,
    defaultParseWidth: "any",
    valueCallback: (index) => index + 1,
  }),

  month: buildMatchFn({
    matchPatterns: matchMonthPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseMonthPatterns,
    defaultParseWidth: "any",
  }),

  day: buildMatchFn({
    matchPatterns: matchDayPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseDayPatterns,
    defaultParseWidth: "any",
  }),

  dayPeriod: buildMatchFn({
    matchPatterns: matchDayPeriodPatterns,
    defaultMatchWidth: "any",
    parsePatterns: parseDayPeriodPatterns,
    defaultParseWidth: "any",
  }),
};

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/locale/en-US.mjs






/**
 * @category Locales
 * @summary English locale (United States).
 * @language English
 * @iso-639-2 eng
 * @author Sasha Koss [@kossnocorp](https://github.com/kossnocorp)
 * @author Lesha Koss [@leshakoss](https://github.com/leshakoss)
 */
const enUS = {
  code: "en-US",
  formatDistance: formatDistance,
  formatLong: formatLong,
  formatRelative: formatRelative,
  localize: localize,
  match: match,
  options: {
    weekStartsOn: 0 /* Sunday */,
    firstWeekContainsDate: 1,
  },
};

// Fallback for modularized imports:
/* harmony default export */ const en_US = ((/* unused pure expression or super */ null && (enUS)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/defaultOptions.mjs
var _lib_defaultOptions = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/defaultOptions.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constants.mjs
/**
 * @module constants
 * @summary Useful constants
 * @description
 * Collection of useful date constants.
 *
 * The constants could be imported from `date-fns/constants`:
 *
 * ```ts
 * import { maxTime, minTime } from "./constants/date-fns/constants";
 *
 * function isAllowedTime(time) {
 *   return time <= maxTime && time >= minTime;
 * }
 * ```
 */

/**
 * @constant
 * @name daysInWeek
 * @summary Days in 1 week.
 */
const daysInWeek = 7;

/**
 * @constant
 * @name daysInYear
 * @summary Days in 1 year.
 *
 * @description
 * How many days in a year.
 *
 * One years equals 365.2425 days according to the formula:
 *
 * > Leap year occures every 4 years, except for years that are divisable by 100 and not divisable by 400.
 * > 1 mean year = (365+1/4-1/100+1/400) days = 365.2425 days
 */
const daysInYear = 365.2425;

/**
 * @constant
 * @name maxTime
 * @summary Maximum allowed time.
 *
 * @example
 * import { maxTime } from "./constants/date-fns/constants";
 *
 * const isValid = 8640000000000001 <= maxTime;
 * //=> false
 *
 * new Date(8640000000000001);
 * //=> Invalid Date
 */
const maxTime = Math.pow(10, 8) * 24 * 60 * 60 * 1000;

/**
 * @constant
 * @name minTime
 * @summary Minimum allowed time.
 *
 * @example
 * import { minTime } from "./constants/date-fns/constants";
 *
 * const isValid = -8640000000000001 >= minTime;
 * //=> false
 *
 * new Date(-8640000000000001)
 * //=> Invalid Date
 */
const minTime = -maxTime;

/**
 * @constant
 * @name millisecondsInWeek
 * @summary Milliseconds in 1 week.
 */
const millisecondsInWeek = 604800000;

/**
 * @constant
 * @name millisecondsInDay
 * @summary Milliseconds in 1 day.
 */
const millisecondsInDay = 86400000;

/**
 * @constant
 * @name millisecondsInMinute
 * @summary Milliseconds in 1 minute
 */
const millisecondsInMinute = 60000;

/**
 * @constant
 * @name millisecondsInHour
 * @summary Milliseconds in 1 hour
 */
const millisecondsInHour = 3600000;

/**
 * @constant
 * @name millisecondsInSecond
 * @summary Milliseconds in 1 second
 */
const millisecondsInSecond = 1000;

/**
 * @constant
 * @name minutesInYear
 * @summary Minutes in 1 year.
 */
const minutesInYear = 525600;

/**
 * @constant
 * @name minutesInMonth
 * @summary Minutes in 1 month.
 */
const minutesInMonth = 43200;

/**
 * @constant
 * @name minutesInDay
 * @summary Minutes in 1 day.
 */
const minutesInDay = 1440;

/**
 * @constant
 * @name minutesInHour
 * @summary Minutes in 1 hour.
 */
const minutesInHour = 60;

/**
 * @constant
 * @name monthsInQuarter
 * @summary Months in 1 quarter.
 */
const monthsInQuarter = 3;

/**
 * @constant
 * @name monthsInYear
 * @summary Months in 1 year.
 */
const monthsInYear = 12;

/**
 * @constant
 * @name quartersInYear
 * @summary Quarters in 1 year
 */
const quartersInYear = 4;

/**
 * @constant
 * @name secondsInHour
 * @summary Seconds in 1 hour.
 */
const secondsInHour = 3600;

/**
 * @constant
 * @name secondsInMinute
 * @summary Seconds in 1 minute.
 */
const secondsInMinute = 60;

/**
 * @constant
 * @name secondsInDay
 * @summary Seconds in 1 day.
 */
const secondsInDay = secondsInHour * 24;

/**
 * @constant
 * @name secondsInWeek
 * @summary Seconds in 1 week.
 */
const secondsInWeek = secondsInDay * 7;

/**
 * @constant
 * @name secondsInYear
 * @summary Seconds in 1 year.
 */
const secondsInYear = secondsInDay * daysInYear;

/**
 * @constant
 * @name secondsInMonth
 * @summary Seconds in 1 month
 */
const secondsInMonth = secondsInYear / 12;

/**
 * @constant
 * @name secondsInQuarter
 * @summary Seconds in 1 quarter.
 */
const secondsInQuarter = secondsInMonth * 3;

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfDay.mjs
var startOfDay = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfDay.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs
var toDate = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/getTimezoneOffsetInMilliseconds.mjs


/**
 * Google Chrome as of 67.0.3396.87 introduced timezones with offset that includes seconds.
 * They usually appear for dates that denote time before the timezones were introduced
 * (e.g. for 'Europe/Prague' timezone the offset is GMT+00:57:44 before 1 October 1891
 * and GMT+01:00:00 after that date)
 *
 * Date#getTimezoneOffset returns the offset in minutes and would return 57 for the example above,
 * which would lead to incorrect calculations.
 *
 * This function returns the timezone offset in milliseconds that takes seconds in account.
 */
function getTimezoneOffsetInMilliseconds(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  const utcDate = new Date(
    Date.UTC(
      _date.getFullYear(),
      _date.getMonth(),
      _date.getDate(),
      _date.getHours(),
      _date.getMinutes(),
      _date.getSeconds(),
      _date.getMilliseconds(),
    ),
  );
  utcDate.setUTCFullYear(_date.getFullYear());
  return +date - +utcDate;
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/differenceInCalendarDays.mjs




/**
 * @name differenceInCalendarDays
 * @category Day Helpers
 * @summary Get the number of calendar days between the given dates.
 *
 * @description
 * Get the number of calendar days between the given dates. This means that the times are removed
 * from the dates and then the difference in days is calculated.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param dateLeft - The later date
 * @param dateRight - The earlier date
 *
 * @returns The number of calendar days
 *
 * @example
 * // How many calendar days are between
 * // 2 July 2011 23:00:00 and 2 July 2012 00:00:00?
 * const result = differenceInCalendarDays(
 *   new Date(2012, 6, 2, 0, 0),
 *   new Date(2011, 6, 2, 23, 0)
 * )
 * //=> 366
 * // How many calendar days are between
 * // 2 July 2011 23:59:00 and 3 July 2011 00:01:00?
 * const result = differenceInCalendarDays(
 *   new Date(2011, 6, 3, 0, 1),
 *   new Date(2011, 6, 2, 23, 59)
 * )
 * //=> 1
 */
function differenceInCalendarDays(dateLeft, dateRight) {
  const startOfDayLeft = (0,startOfDay/* startOfDay */.o)(dateLeft);
  const startOfDayRight = (0,startOfDay/* startOfDay */.o)(dateRight);

  const timestampLeft =
    +startOfDayLeft - getTimezoneOffsetInMilliseconds(startOfDayLeft);
  const timestampRight =
    +startOfDayRight - getTimezoneOffsetInMilliseconds(startOfDayRight);

  // Round the number of days to the nearest integer because the number of
  // milliseconds in a day is not constant (e.g. it's different in the week of
  // the daylight saving time clock shift).
  return Math.round((timestampLeft - timestampRight) / millisecondsInDay);
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_differenceInCalendarDays = ((/* unused pure expression or super */ null && (differenceInCalendarDays)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs
var constructFrom = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfYear.mjs



/**
 * @name startOfYear
 * @category Year Helpers
 * @summary Return the start of a year for the given date.
 *
 * @description
 * Return the start of a year for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 *
 * @returns The start of a year
 *
 * @example
 * // The start of a year for 2 September 2014 11:55:00:
 * const result = startOfYear(new Date(2014, 8, 2, 11, 55, 00))
 * //=> Wed Jan 01 2014 00:00:00
 */
function startOfYear(date) {
  const cleanDate = (0,toDate/* toDate */.a)(date);
  const _date = (0,constructFrom/* constructFrom */.w)(date, 0);
  _date.setFullYear(cleanDate.getFullYear(), 0, 1);
  _date.setHours(0, 0, 0, 0);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_startOfYear = ((/* unused pure expression or super */ null && (startOfYear)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/getDayOfYear.mjs




/**
 * @name getDayOfYear
 * @category Day Helpers
 * @summary Get the day of the year of the given date.
 *
 * @description
 * Get the day of the year of the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The given date
 *
 * @returns The day of year
 *
 * @example
 * // Which day of the year is 2 July 2014?
 * const result = getDayOfYear(new Date(2014, 6, 2))
 * //=> 183
 */
function getDayOfYear(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  const diff = differenceInCalendarDays(_date, startOfYear(_date));
  const dayOfYear = diff + 1;
  return dayOfYear;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_getDayOfYear = ((/* unused pure expression or super */ null && (getDayOfYear)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfWeek.mjs
var startOfWeek = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfWeek.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfISOWeek.mjs


/**
 * @name startOfISOWeek
 * @category ISO Week Helpers
 * @summary Return the start of an ISO week for the given date.
 *
 * @description
 * Return the start of an ISO week for the given date.
 * The result will be in the local timezone.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 *
 * @returns The start of an ISO week
 *
 * @example
 * // The start of an ISO week for 2 September 2014 11:55:00:
 * const result = startOfISOWeek(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Mon Sep 01 2014 00:00:00
 */
function startOfISOWeek(date) {
  return (0,startOfWeek/* startOfWeek */.k)(date, { weekStartsOn: 1 });
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_startOfISOWeek = ((/* unused pure expression or super */ null && (startOfISOWeek)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/getISOWeekYear.mjs




/**
 * @name getISOWeekYear
 * @category ISO Week-Numbering Year Helpers
 * @summary Get the ISO week-numbering year of the given date.
 *
 * @description
 * Get the ISO week-numbering year of the given date,
 * which always starts 3 days before the year's first Thursday.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The given date
 *
 * @returns The ISO week-numbering year
 *
 * @example
 * // Which ISO-week numbering year is 2 January 2005?
 * const result = getISOWeekYear(new Date(2005, 0, 2))
 * //=> 2004
 */
function getISOWeekYear(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  const year = _date.getFullYear();

  const fourthOfJanuaryOfNextYear = (0,constructFrom/* constructFrom */.w)(date, 0);
  fourthOfJanuaryOfNextYear.setFullYear(year + 1, 0, 4);
  fourthOfJanuaryOfNextYear.setHours(0, 0, 0, 0);
  const startOfNextYear = startOfISOWeek(fourthOfJanuaryOfNextYear);

  const fourthOfJanuaryOfThisYear = (0,constructFrom/* constructFrom */.w)(date, 0);
  fourthOfJanuaryOfThisYear.setFullYear(year, 0, 4);
  fourthOfJanuaryOfThisYear.setHours(0, 0, 0, 0);
  const startOfThisYear = startOfISOWeek(fourthOfJanuaryOfThisYear);

  if (_date.getTime() >= startOfNextYear.getTime()) {
    return year + 1;
  } else if (_date.getTime() >= startOfThisYear.getTime()) {
    return year;
  } else {
    return year - 1;
  }
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_getISOWeekYear = ((/* unused pure expression or super */ null && (getISOWeekYear)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfISOWeekYear.mjs




/**
 * @name startOfISOWeekYear
 * @category ISO Week-Numbering Year Helpers
 * @summary Return the start of an ISO week-numbering year for the given date.
 *
 * @description
 * Return the start of an ISO week-numbering year,
 * which always starts 3 days before the year's first Thursday.
 * The result will be in the local timezone.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 *
 * @returns The start of an ISO week-numbering year
 *
 * @example
 * // The start of an ISO week-numbering year for 2 July 2005:
 * const result = startOfISOWeekYear(new Date(2005, 6, 2))
 * //=> Mon Jan 03 2005 00:00:00
 */
function startOfISOWeekYear(date) {
  const year = getISOWeekYear(date);
  const fourthOfJanuary = (0,constructFrom/* constructFrom */.w)(date, 0);
  fourthOfJanuary.setFullYear(year, 0, 4);
  fourthOfJanuary.setHours(0, 0, 0, 0);
  return startOfISOWeek(fourthOfJanuary);
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_startOfISOWeekYear = ((/* unused pure expression or super */ null && (startOfISOWeekYear)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/getISOWeek.mjs





/**
 * @name getISOWeek
 * @category ISO Week Helpers
 * @summary Get the ISO week of the given date.
 *
 * @description
 * Get the ISO week of the given date.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The given date
 *
 * @returns The ISO week
 *
 * @example
 * // Which week of the ISO-week numbering year is 2 January 2005?
 * const result = getISOWeek(new Date(2005, 0, 2))
 * //=> 53
 */
function getISOWeek(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  const diff = +startOfISOWeek(_date) - +startOfISOWeekYear(_date);

  // Round the number of weeks to the nearest integer because the number of
  // milliseconds in a week is not constant (e.g. it's different in the week of
  // the daylight saving time clock shift).
  return Math.round(diff / millisecondsInWeek) + 1;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_getISOWeek = ((/* unused pure expression or super */ null && (getISOWeek)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/getWeekYear.mjs





/**
 * The {@link getWeekYear} function options.
 */

/**
 * @name getWeekYear
 * @category Week-Numbering Year Helpers
 * @summary Get the local week-numbering year of the given date.
 *
 * @description
 * Get the local week-numbering year of the given date.
 * The exact calculation depends on the values of
 * `options.weekStartsOn` (which is the index of the first day of the week)
 * and `options.firstWeekContainsDate` (which is the day of January, which is always in
 * the first week of the week-numbering year)
 *
 * Week numbering: https://en.wikipedia.org/wiki/Week#The_ISO_week_date_system
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The given date
 * @param options - An object with options.
 *
 * @returns The local week-numbering year
 *
 * @example
 * // Which week numbering year is 26 December 2004 with the default settings?
 * const result = getWeekYear(new Date(2004, 11, 26))
 * //=> 2005
 *
 * @example
 * // Which week numbering year is 26 December 2004 if week starts on Saturday?
 * const result = getWeekYear(new Date(2004, 11, 26), { weekStartsOn: 6 })
 * //=> 2004
 *
 * @example
 * // Which week numbering year is 26 December 2004 if the first week contains 4 January?
 * const result = getWeekYear(new Date(2004, 11, 26), { firstWeekContainsDate: 4 })
 * //=> 2004
 */
function getWeekYear(date, options) {
  const _date = (0,toDate/* toDate */.a)(date);
  const year = _date.getFullYear();

  const defaultOptions = (0,_lib_defaultOptions/* getDefaultOptions */.q)();
  const firstWeekContainsDate =
    options?.firstWeekContainsDate ??
    options?.locale?.options?.firstWeekContainsDate ??
    defaultOptions.firstWeekContainsDate ??
    defaultOptions.locale?.options?.firstWeekContainsDate ??
    1;

  const firstWeekOfNextYear = (0,constructFrom/* constructFrom */.w)(date, 0);
  firstWeekOfNextYear.setFullYear(year + 1, 0, firstWeekContainsDate);
  firstWeekOfNextYear.setHours(0, 0, 0, 0);
  const startOfNextYear = (0,startOfWeek/* startOfWeek */.k)(firstWeekOfNextYear, options);

  const firstWeekOfThisYear = (0,constructFrom/* constructFrom */.w)(date, 0);
  firstWeekOfThisYear.setFullYear(year, 0, firstWeekContainsDate);
  firstWeekOfThisYear.setHours(0, 0, 0, 0);
  const startOfThisYear = (0,startOfWeek/* startOfWeek */.k)(firstWeekOfThisYear, options);

  if (_date.getTime() >= startOfNextYear.getTime()) {
    return year + 1;
  } else if (_date.getTime() >= startOfThisYear.getTime()) {
    return year;
  } else {
    return year - 1;
  }
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_getWeekYear = ((/* unused pure expression or super */ null && (getWeekYear)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfWeekYear.mjs





/**
 * The {@link startOfWeekYear} function options.
 */

/**
 * @name startOfWeekYear
 * @category Week-Numbering Year Helpers
 * @summary Return the start of a local week-numbering year for the given date.
 *
 * @description
 * Return the start of a local week-numbering year.
 * The exact calculation depends on the values of
 * `options.weekStartsOn` (which is the index of the first day of the week)
 * and `options.firstWeekContainsDate` (which is the day of January, which is always in
 * the first week of the week-numbering year)
 *
 * Week numbering: https://en.wikipedia.org/wiki/Week#The_ISO_week_date_system
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 * @param options - An object with options
 *
 * @returns The start of a week-numbering year
 *
 * @example
 * // The start of an a week-numbering year for 2 July 2005 with default settings:
 * const result = startOfWeekYear(new Date(2005, 6, 2))
 * //=> Sun Dec 26 2004 00:00:00
 *
 * @example
 * // The start of a week-numbering year for 2 July 2005
 * // if Monday is the first day of week
 * // and 4 January is always in the first week of the year:
 * const result = startOfWeekYear(new Date(2005, 6, 2), {
 *   weekStartsOn: 1,
 *   firstWeekContainsDate: 4
 * })
 * //=> Mon Jan 03 2005 00:00:00
 */
function startOfWeekYear(date, options) {
  const defaultOptions = (0,_lib_defaultOptions/* getDefaultOptions */.q)();
  const firstWeekContainsDate =
    options?.firstWeekContainsDate ??
    options?.locale?.options?.firstWeekContainsDate ??
    defaultOptions.firstWeekContainsDate ??
    defaultOptions.locale?.options?.firstWeekContainsDate ??
    1;

  const year = getWeekYear(date, options);
  const firstWeek = (0,constructFrom/* constructFrom */.w)(date, 0);
  firstWeek.setFullYear(year, 0, firstWeekContainsDate);
  firstWeek.setHours(0, 0, 0, 0);
  const _date = (0,startOfWeek/* startOfWeek */.k)(firstWeek, options);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_startOfWeekYear = ((/* unused pure expression or super */ null && (startOfWeekYear)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/getWeek.mjs





/**
 * The {@link getWeek} function options.
 */

/**
 * @name getWeek
 * @category Week Helpers
 * @summary Get the local week index of the given date.
 *
 * @description
 * Get the local week index of the given date.
 * The exact calculation depends on the values of
 * `options.weekStartsOn` (which is the index of the first day of the week)
 * and `options.firstWeekContainsDate` (which is the day of January, which is always in
 * the first week of the week-numbering year)
 *
 * Week numbering: https://en.wikipedia.org/wiki/Week#The_ISO_week_date_system
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The given date
 * @param options - An object with options
 *
 * @returns The week
 *
 * @example
 * // Which week of the local week numbering year is 2 January 2005 with default options?
 * const result = getWeek(new Date(2005, 0, 2))
 * //=> 2
 *
 * @example
 * // Which week of the local week numbering year is 2 January 2005,
 * // if Monday is the first day of the week,
 * // and the first week of the year always contains 4 January?
 * const result = getWeek(new Date(2005, 0, 2), {
 *   weekStartsOn: 1,
 *   firstWeekContainsDate: 4
 * })
 * //=> 53
 */

function getWeek(date, options) {
  const _date = (0,toDate/* toDate */.a)(date);
  const diff = +(0,startOfWeek/* startOfWeek */.k)(_date, options) - +startOfWeekYear(_date, options);

  // Round the number of weeks to the nearest integer because the number of
  // milliseconds in a week is not constant (e.g. it's different in the week of
  // the daylight saving time clock shift).
  return Math.round(diff / millisecondsInWeek) + 1;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_getWeek = ((/* unused pure expression or super */ null && (getWeek)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/addLeadingZeros.mjs
function addLeadingZeros(number, targetLength) {
  const sign = number < 0 ? "-" : "";
  const output = Math.abs(number).toString().padStart(targetLength, "0");
  return sign + output;
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/format/lightFormatters.mjs


/*
 * |     | Unit                           |     | Unit                           |
 * |-----|--------------------------------|-----|--------------------------------|
 * |  a  | AM, PM                         |  A* |                                |
 * |  d  | Day of month                   |  D  |                                |
 * |  h  | Hour [1-12]                    |  H  | Hour [0-23]                    |
 * |  m  | Minute                         |  M  | Month                          |
 * |  s  | Second                         |  S  | Fraction of second             |
 * |  y  | Year (abs)                     |  Y  |                                |
 *
 * Letters marked by * are not implemented but reserved by Unicode standard.
 */

const lightFormatters = {
  // Year
  y(date, token) {
    // From http://www.unicode.org/reports/tr35/tr35-31/tr35-dates.html#Date_Format_tokens
    // | Year     |     y | yy |   yyy |  yyyy | yyyyy |
    // |----------|-------|----|-------|-------|-------|
    // | AD 1     |     1 | 01 |   001 |  0001 | 00001 |
    // | AD 12    |    12 | 12 |   012 |  0012 | 00012 |
    // | AD 123   |   123 | 23 |   123 |  0123 | 00123 |
    // | AD 1234  |  1234 | 34 |  1234 |  1234 | 01234 |
    // | AD 12345 | 12345 | 45 | 12345 | 12345 | 12345 |

    const signedYear = date.getFullYear();
    // Returns 1 for 1 BC (which is year 0 in JavaScript)
    const year = signedYear > 0 ? signedYear : 1 - signedYear;
    return addLeadingZeros(token === "yy" ? year % 100 : year, token.length);
  },

  // Month
  M(date, token) {
    const month = date.getMonth();
    return token === "M" ? String(month + 1) : addLeadingZeros(month + 1, 2);
  },

  // Day of the month
  d(date, token) {
    return addLeadingZeros(date.getDate(), token.length);
  },

  // AM or PM
  a(date, token) {
    const dayPeriodEnumValue = date.getHours() / 12 >= 1 ? "pm" : "am";

    switch (token) {
      case "a":
      case "aa":
        return dayPeriodEnumValue.toUpperCase();
      case "aaa":
        return dayPeriodEnumValue;
      case "aaaaa":
        return dayPeriodEnumValue[0];
      case "aaaa":
      default:
        return dayPeriodEnumValue === "am" ? "a.m." : "p.m.";
    }
  },

  // Hour [1-12]
  h(date, token) {
    return addLeadingZeros(date.getHours() % 12 || 12, token.length);
  },

  // Hour [0-23]
  H(date, token) {
    return addLeadingZeros(date.getHours(), token.length);
  },

  // Minute
  m(date, token) {
    return addLeadingZeros(date.getMinutes(), token.length);
  },

  // Second
  s(date, token) {
    return addLeadingZeros(date.getSeconds(), token.length);
  },

  // Fraction of second
  S(date, token) {
    const numberOfDigits = token.length;
    const milliseconds = date.getMilliseconds();
    const fractionalSeconds = Math.trunc(
      milliseconds * Math.pow(10, numberOfDigits - 3),
    );
    return addLeadingZeros(fractionalSeconds, token.length);
  },
};

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/format/formatters.mjs








const dayPeriodEnum = {
  am: "am",
  pm: "pm",
  midnight: "midnight",
  noon: "noon",
  morning: "morning",
  afternoon: "afternoon",
  evening: "evening",
  night: "night",
};

/*
 * |     | Unit                           |     | Unit                           |
 * |-----|--------------------------------|-----|--------------------------------|
 * |  a  | AM, PM                         |  A* | Milliseconds in day            |
 * |  b  | AM, PM, noon, midnight         |  B  | Flexible day period            |
 * |  c  | Stand-alone local day of week  |  C* | Localized hour w/ day period   |
 * |  d  | Day of month                   |  D  | Day of year                    |
 * |  e  | Local day of week              |  E  | Day of week                    |
 * |  f  |                                |  F* | Day of week in month           |
 * |  g* | Modified Julian day            |  G  | Era                            |
 * |  h  | Hour [1-12]                    |  H  | Hour [0-23]                    |
 * |  i! | ISO day of week                |  I! | ISO week of year               |
 * |  j* | Localized hour w/ day period   |  J* | Localized hour w/o day period  |
 * |  k  | Hour [1-24]                    |  K  | Hour [0-11]                    |
 * |  l* | (deprecated)                   |  L  | Stand-alone month              |
 * |  m  | Minute                         |  M  | Month                          |
 * |  n  |                                |  N  |                                |
 * |  o! | Ordinal number modifier        |  O  | Timezone (GMT)                 |
 * |  p! | Long localized time            |  P! | Long localized date            |
 * |  q  | Stand-alone quarter            |  Q  | Quarter                        |
 * |  r* | Related Gregorian year         |  R! | ISO week-numbering year        |
 * |  s  | Second                         |  S  | Fraction of second             |
 * |  t! | Seconds timestamp              |  T! | Milliseconds timestamp         |
 * |  u  | Extended year                  |  U* | Cyclic year                    |
 * |  v* | Timezone (generic non-locat.)  |  V* | Timezone (location)            |
 * |  w  | Local week of year             |  W* | Week of month                  |
 * |  x  | Timezone (ISO-8601 w/o Z)      |  X  | Timezone (ISO-8601)            |
 * |  y  | Year (abs)                     |  Y  | Local week-numbering year      |
 * |  z  | Timezone (specific non-locat.) |  Z* | Timezone (aliases)             |
 *
 * Letters marked by * are not implemented but reserved by Unicode standard.
 *
 * Letters marked by ! are non-standard, but implemented by date-fns:
 * - `o` modifies the previous token to turn it into an ordinal (see `format` docs)
 * - `i` is ISO day of week. For `i` and `ii` is returns numeric ISO week days,
 *   i.e. 7 for Sunday, 1 for Monday, etc.
 * - `I` is ISO week of year, as opposed to `w` which is local week of year.
 * - `R` is ISO week-numbering year, as opposed to `Y` which is local week-numbering year.
 *   `R` is supposed to be used in conjunction with `I` and `i`
 *   for universal ISO week-numbering date, whereas
 *   `Y` is supposed to be used in conjunction with `w` and `e`
 *   for week-numbering date specific to the locale.
 * - `P` is long localized date format
 * - `p` is long localized time format
 */

const formatters = {
  // Era
  G: function (date, token, localize) {
    const era = date.getFullYear() > 0 ? 1 : 0;
    switch (token) {
      // AD, BC
      case "G":
      case "GG":
      case "GGG":
        return localize.era(era, { width: "abbreviated" });
      // A, B
      case "GGGGG":
        return localize.era(era, { width: "narrow" });
      // Anno Domini, Before Christ
      case "GGGG":
      default:
        return localize.era(era, { width: "wide" });
    }
  },

  // Year
  y: function (date, token, localize) {
    // Ordinal number
    if (token === "yo") {
      const signedYear = date.getFullYear();
      // Returns 1 for 1 BC (which is year 0 in JavaScript)
      const year = signedYear > 0 ? signedYear : 1 - signedYear;
      return localize.ordinalNumber(year, { unit: "year" });
    }

    return lightFormatters.y(date, token);
  },

  // Local week-numbering year
  Y: function (date, token, localize, options) {
    const signedWeekYear = getWeekYear(date, options);
    // Returns 1 for 1 BC (which is year 0 in JavaScript)
    const weekYear = signedWeekYear > 0 ? signedWeekYear : 1 - signedWeekYear;

    // Two digit year
    if (token === "YY") {
      const twoDigitYear = weekYear % 100;
      return addLeadingZeros(twoDigitYear, 2);
    }

    // Ordinal number
    if (token === "Yo") {
      return localize.ordinalNumber(weekYear, { unit: "year" });
    }

    // Padding
    return addLeadingZeros(weekYear, token.length);
  },

  // ISO week-numbering year
  R: function (date, token) {
    const isoWeekYear = getISOWeekYear(date);

    // Padding
    return addLeadingZeros(isoWeekYear, token.length);
  },

  // Extended year. This is a single number designating the year of this calendar system.
  // The main difference between `y` and `u` localizers are B.C. years:
  // | Year | `y` | `u` |
  // |------|-----|-----|
  // | AC 1 |   1 |   1 |
  // | BC 1 |   1 |   0 |
  // | BC 2 |   2 |  -1 |
  // Also `yy` always returns the last two digits of a year,
  // while `uu` pads single digit years to 2 characters and returns other years unchanged.
  u: function (date, token) {
    const year = date.getFullYear();
    return addLeadingZeros(year, token.length);
  },

  // Quarter
  Q: function (date, token, localize) {
    const quarter = Math.ceil((date.getMonth() + 1) / 3);
    switch (token) {
      // 1, 2, 3, 4
      case "Q":
        return String(quarter);
      // 01, 02, 03, 04
      case "QQ":
        return addLeadingZeros(quarter, 2);
      // 1st, 2nd, 3rd, 4th
      case "Qo":
        return localize.ordinalNumber(quarter, { unit: "quarter" });
      // Q1, Q2, Q3, Q4
      case "QQQ":
        return localize.quarter(quarter, {
          width: "abbreviated",
          context: "formatting",
        });
      // 1, 2, 3, 4 (narrow quarter; could be not numerical)
      case "QQQQQ":
        return localize.quarter(quarter, {
          width: "narrow",
          context: "formatting",
        });
      // 1st quarter, 2nd quarter, ...
      case "QQQQ":
      default:
        return localize.quarter(quarter, {
          width: "wide",
          context: "formatting",
        });
    }
  },

  // Stand-alone quarter
  q: function (date, token, localize) {
    const quarter = Math.ceil((date.getMonth() + 1) / 3);
    switch (token) {
      // 1, 2, 3, 4
      case "q":
        return String(quarter);
      // 01, 02, 03, 04
      case "qq":
        return addLeadingZeros(quarter, 2);
      // 1st, 2nd, 3rd, 4th
      case "qo":
        return localize.ordinalNumber(quarter, { unit: "quarter" });
      // Q1, Q2, Q3, Q4
      case "qqq":
        return localize.quarter(quarter, {
          width: "abbreviated",
          context: "standalone",
        });
      // 1, 2, 3, 4 (narrow quarter; could be not numerical)
      case "qqqqq":
        return localize.quarter(quarter, {
          width: "narrow",
          context: "standalone",
        });
      // 1st quarter, 2nd quarter, ...
      case "qqqq":
      default:
        return localize.quarter(quarter, {
          width: "wide",
          context: "standalone",
        });
    }
  },

  // Month
  M: function (date, token, localize) {
    const month = date.getMonth();
    switch (token) {
      case "M":
      case "MM":
        return lightFormatters.M(date, token);
      // 1st, 2nd, ..., 12th
      case "Mo":
        return localize.ordinalNumber(month + 1, { unit: "month" });
      // Jan, Feb, ..., Dec
      case "MMM":
        return localize.month(month, {
          width: "abbreviated",
          context: "formatting",
        });
      // J, F, ..., D
      case "MMMMM":
        return localize.month(month, {
          width: "narrow",
          context: "formatting",
        });
      // January, February, ..., December
      case "MMMM":
      default:
        return localize.month(month, { width: "wide", context: "formatting" });
    }
  },

  // Stand-alone month
  L: function (date, token, localize) {
    const month = date.getMonth();
    switch (token) {
      // 1, 2, ..., 12
      case "L":
        return String(month + 1);
      // 01, 02, ..., 12
      case "LL":
        return addLeadingZeros(month + 1, 2);
      // 1st, 2nd, ..., 12th
      case "Lo":
        return localize.ordinalNumber(month + 1, { unit: "month" });
      // Jan, Feb, ..., Dec
      case "LLL":
        return localize.month(month, {
          width: "abbreviated",
          context: "standalone",
        });
      // J, F, ..., D
      case "LLLLL":
        return localize.month(month, {
          width: "narrow",
          context: "standalone",
        });
      // January, February, ..., December
      case "LLLL":
      default:
        return localize.month(month, { width: "wide", context: "standalone" });
    }
  },

  // Local week of year
  w: function (date, token, localize, options) {
    const week = getWeek(date, options);

    if (token === "wo") {
      return localize.ordinalNumber(week, { unit: "week" });
    }

    return addLeadingZeros(week, token.length);
  },

  // ISO week of year
  I: function (date, token, localize) {
    const isoWeek = getISOWeek(date);

    if (token === "Io") {
      return localize.ordinalNumber(isoWeek, { unit: "week" });
    }

    return addLeadingZeros(isoWeek, token.length);
  },

  // Day of the month
  d: function (date, token, localize) {
    if (token === "do") {
      return localize.ordinalNumber(date.getDate(), { unit: "date" });
    }

    return lightFormatters.d(date, token);
  },

  // Day of year
  D: function (date, token, localize) {
    const dayOfYear = getDayOfYear(date);

    if (token === "Do") {
      return localize.ordinalNumber(dayOfYear, { unit: "dayOfYear" });
    }

    return addLeadingZeros(dayOfYear, token.length);
  },

  // Day of week
  E: function (date, token, localize) {
    const dayOfWeek = date.getDay();
    switch (token) {
      // Tue
      case "E":
      case "EE":
      case "EEE":
        return localize.day(dayOfWeek, {
          width: "abbreviated",
          context: "formatting",
        });
      // T
      case "EEEEE":
        return localize.day(dayOfWeek, {
          width: "narrow",
          context: "formatting",
        });
      // Tu
      case "EEEEEE":
        return localize.day(dayOfWeek, {
          width: "short",
          context: "formatting",
        });
      // Tuesday
      case "EEEE":
      default:
        return localize.day(dayOfWeek, {
          width: "wide",
          context: "formatting",
        });
    }
  },

  // Local day of week
  e: function (date, token, localize, options) {
    const dayOfWeek = date.getDay();
    const localDayOfWeek = (dayOfWeek - options.weekStartsOn + 8) % 7 || 7;
    switch (token) {
      // Numerical value (Nth day of week with current locale or weekStartsOn)
      case "e":
        return String(localDayOfWeek);
      // Padded numerical value
      case "ee":
        return addLeadingZeros(localDayOfWeek, 2);
      // 1st, 2nd, ..., 7th
      case "eo":
        return localize.ordinalNumber(localDayOfWeek, { unit: "day" });
      case "eee":
        return localize.day(dayOfWeek, {
          width: "abbreviated",
          context: "formatting",
        });
      // T
      case "eeeee":
        return localize.day(dayOfWeek, {
          width: "narrow",
          context: "formatting",
        });
      // Tu
      case "eeeeee":
        return localize.day(dayOfWeek, {
          width: "short",
          context: "formatting",
        });
      // Tuesday
      case "eeee":
      default:
        return localize.day(dayOfWeek, {
          width: "wide",
          context: "formatting",
        });
    }
  },

  // Stand-alone local day of week
  c: function (date, token, localize, options) {
    const dayOfWeek = date.getDay();
    const localDayOfWeek = (dayOfWeek - options.weekStartsOn + 8) % 7 || 7;
    switch (token) {
      // Numerical value (same as in `e`)
      case "c":
        return String(localDayOfWeek);
      // Padded numerical value
      case "cc":
        return addLeadingZeros(localDayOfWeek, token.length);
      // 1st, 2nd, ..., 7th
      case "co":
        return localize.ordinalNumber(localDayOfWeek, { unit: "day" });
      case "ccc":
        return localize.day(dayOfWeek, {
          width: "abbreviated",
          context: "standalone",
        });
      // T
      case "ccccc":
        return localize.day(dayOfWeek, {
          width: "narrow",
          context: "standalone",
        });
      // Tu
      case "cccccc":
        return localize.day(dayOfWeek, {
          width: "short",
          context: "standalone",
        });
      // Tuesday
      case "cccc":
      default:
        return localize.day(dayOfWeek, {
          width: "wide",
          context: "standalone",
        });
    }
  },

  // ISO day of week
  i: function (date, token, localize) {
    const dayOfWeek = date.getDay();
    const isoDayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek;
    switch (token) {
      // 2
      case "i":
        return String(isoDayOfWeek);
      // 02
      case "ii":
        return addLeadingZeros(isoDayOfWeek, token.length);
      // 2nd
      case "io":
        return localize.ordinalNumber(isoDayOfWeek, { unit: "day" });
      // Tue
      case "iii":
        return localize.day(dayOfWeek, {
          width: "abbreviated",
          context: "formatting",
        });
      // T
      case "iiiii":
        return localize.day(dayOfWeek, {
          width: "narrow",
          context: "formatting",
        });
      // Tu
      case "iiiiii":
        return localize.day(dayOfWeek, {
          width: "short",
          context: "formatting",
        });
      // Tuesday
      case "iiii":
      default:
        return localize.day(dayOfWeek, {
          width: "wide",
          context: "formatting",
        });
    }
  },

  // AM or PM
  a: function (date, token, localize) {
    const hours = date.getHours();
    const dayPeriodEnumValue = hours / 12 >= 1 ? "pm" : "am";

    switch (token) {
      case "a":
      case "aa":
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting",
        });
      case "aaa":
        return localize
          .dayPeriod(dayPeriodEnumValue, {
            width: "abbreviated",
            context: "formatting",
          })
          .toLowerCase();
      case "aaaaa":
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "narrow",
          context: "formatting",
        });
      case "aaaa":
      default:
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "wide",
          context: "formatting",
        });
    }
  },

  // AM, PM, midnight, noon
  b: function (date, token, localize) {
    const hours = date.getHours();
    let dayPeriodEnumValue;
    if (hours === 12) {
      dayPeriodEnumValue = dayPeriodEnum.noon;
    } else if (hours === 0) {
      dayPeriodEnumValue = dayPeriodEnum.midnight;
    } else {
      dayPeriodEnumValue = hours / 12 >= 1 ? "pm" : "am";
    }

    switch (token) {
      case "b":
      case "bb":
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting",
        });
      case "bbb":
        return localize
          .dayPeriod(dayPeriodEnumValue, {
            width: "abbreviated",
            context: "formatting",
          })
          .toLowerCase();
      case "bbbbb":
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "narrow",
          context: "formatting",
        });
      case "bbbb":
      default:
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "wide",
          context: "formatting",
        });
    }
  },

  // in the morning, in the afternoon, in the evening, at night
  B: function (date, token, localize) {
    const hours = date.getHours();
    let dayPeriodEnumValue;
    if (hours >= 17) {
      dayPeriodEnumValue = dayPeriodEnum.evening;
    } else if (hours >= 12) {
      dayPeriodEnumValue = dayPeriodEnum.afternoon;
    } else if (hours >= 4) {
      dayPeriodEnumValue = dayPeriodEnum.morning;
    } else {
      dayPeriodEnumValue = dayPeriodEnum.night;
    }

    switch (token) {
      case "B":
      case "BB":
      case "BBB":
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting",
        });
      case "BBBBB":
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "narrow",
          context: "formatting",
        });
      case "BBBB":
      default:
        return localize.dayPeriod(dayPeriodEnumValue, {
          width: "wide",
          context: "formatting",
        });
    }
  },

  // Hour [1-12]
  h: function (date, token, localize) {
    if (token === "ho") {
      let hours = date.getHours() % 12;
      if (hours === 0) hours = 12;
      return localize.ordinalNumber(hours, { unit: "hour" });
    }

    return lightFormatters.h(date, token);
  },

  // Hour [0-23]
  H: function (date, token, localize) {
    if (token === "Ho") {
      return localize.ordinalNumber(date.getHours(), { unit: "hour" });
    }

    return lightFormatters.H(date, token);
  },

  // Hour [0-11]
  K: function (date, token, localize) {
    const hours = date.getHours() % 12;

    if (token === "Ko") {
      return localize.ordinalNumber(hours, { unit: "hour" });
    }

    return addLeadingZeros(hours, token.length);
  },

  // Hour [1-24]
  k: function (date, token, localize) {
    let hours = date.getHours();
    if (hours === 0) hours = 24;

    if (token === "ko") {
      return localize.ordinalNumber(hours, { unit: "hour" });
    }

    return addLeadingZeros(hours, token.length);
  },

  // Minute
  m: function (date, token, localize) {
    if (token === "mo") {
      return localize.ordinalNumber(date.getMinutes(), { unit: "minute" });
    }

    return lightFormatters.m(date, token);
  },

  // Second
  s: function (date, token, localize) {
    if (token === "so") {
      return localize.ordinalNumber(date.getSeconds(), { unit: "second" });
    }

    return lightFormatters.s(date, token);
  },

  // Fraction of second
  S: function (date, token) {
    return lightFormatters.S(date, token);
  },

  // Timezone (ISO-8601. If offset is 0, output is always `'Z'`)
  X: function (date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();

    if (timezoneOffset === 0) {
      return "Z";
    }

    switch (token) {
      // Hours and optional minutes
      case "X":
        return formatTimezoneWithOptionalMinutes(timezoneOffset);

      // Hours, minutes and optional seconds without `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `XX`
      case "XXXX":
      case "XX": // Hours and minutes without `:` delimiter
        return formatTimezone(timezoneOffset);

      // Hours, minutes and optional seconds with `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `XXX`
      case "XXXXX":
      case "XXX": // Hours and minutes with `:` delimiter
      default:
        return formatTimezone(timezoneOffset, ":");
    }
  },

  // Timezone (ISO-8601. If offset is 0, output is `'+00:00'` or equivalent)
  x: function (date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();

    switch (token) {
      // Hours and optional minutes
      case "x":
        return formatTimezoneWithOptionalMinutes(timezoneOffset);

      // Hours, minutes and optional seconds without `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `xx`
      case "xxxx":
      case "xx": // Hours and minutes without `:` delimiter
        return formatTimezone(timezoneOffset);

      // Hours, minutes and optional seconds with `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `xxx`
      case "xxxxx":
      case "xxx": // Hours and minutes with `:` delimiter
      default:
        return formatTimezone(timezoneOffset, ":");
    }
  },

  // Timezone (GMT)
  O: function (date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();

    switch (token) {
      // Short
      case "O":
      case "OO":
      case "OOO":
        return "GMT" + formatTimezoneShort(timezoneOffset, ":");
      // Long
      case "OOOO":
      default:
        return "GMT" + formatTimezone(timezoneOffset, ":");
    }
  },

  // Timezone (specific non-location)
  z: function (date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();

    switch (token) {
      // Short
      case "z":
      case "zz":
      case "zzz":
        return "GMT" + formatTimezoneShort(timezoneOffset, ":");
      // Long
      case "zzzz":
      default:
        return "GMT" + formatTimezone(timezoneOffset, ":");
    }
  },

  // Seconds timestamp
  t: function (date, token, _localize) {
    const timestamp = Math.trunc(date.getTime() / 1000);
    return addLeadingZeros(timestamp, token.length);
  },

  // Milliseconds timestamp
  T: function (date, token, _localize) {
    const timestamp = date.getTime();
    return addLeadingZeros(timestamp, token.length);
  },
};

function formatTimezoneShort(offset, delimiter = "") {
  const sign = offset > 0 ? "-" : "+";
  const absOffset = Math.abs(offset);
  const hours = Math.trunc(absOffset / 60);
  const minutes = absOffset % 60;
  if (minutes === 0) {
    return sign + String(hours);
  }
  return sign + String(hours) + delimiter + addLeadingZeros(minutes, 2);
}

function formatTimezoneWithOptionalMinutes(offset, delimiter) {
  if (offset % 60 === 0) {
    const sign = offset > 0 ? "-" : "+";
    return sign + addLeadingZeros(Math.abs(offset) / 60, 2);
  }
  return formatTimezone(offset, delimiter);
}

function formatTimezone(offset, delimiter = "") {
  const sign = offset > 0 ? "-" : "+";
  const absOffset = Math.abs(offset);
  const hours = addLeadingZeros(Math.trunc(absOffset / 60), 2);
  const minutes = addLeadingZeros(absOffset % 60, 2);
  return sign + hours + delimiter + minutes;
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/format/longFormatters.mjs
const dateLongFormatter = (pattern, formatLong) => {
  switch (pattern) {
    case "P":
      return formatLong.date({ width: "short" });
    case "PP":
      return formatLong.date({ width: "medium" });
    case "PPP":
      return formatLong.date({ width: "long" });
    case "PPPP":
    default:
      return formatLong.date({ width: "full" });
  }
};

const timeLongFormatter = (pattern, formatLong) => {
  switch (pattern) {
    case "p":
      return formatLong.time({ width: "short" });
    case "pp":
      return formatLong.time({ width: "medium" });
    case "ppp":
      return formatLong.time({ width: "long" });
    case "pppp":
    default:
      return formatLong.time({ width: "full" });
  }
};

const dateTimeLongFormatter = (pattern, formatLong) => {
  const matchResult = pattern.match(/(P+)(p+)?/) || [];
  const datePattern = matchResult[1];
  const timePattern = matchResult[2];

  if (!timePattern) {
    return dateLongFormatter(pattern, formatLong);
  }

  let dateTimeFormat;

  switch (datePattern) {
    case "P":
      dateTimeFormat = formatLong.dateTime({ width: "short" });
      break;
    case "PP":
      dateTimeFormat = formatLong.dateTime({ width: "medium" });
      break;
    case "PPP":
      dateTimeFormat = formatLong.dateTime({ width: "long" });
      break;
    case "PPPP":
    default:
      dateTimeFormat = formatLong.dateTime({ width: "full" });
      break;
  }

  return dateTimeFormat
    .replace("{{date}}", dateLongFormatter(datePattern, formatLong))
    .replace("{{time}}", timeLongFormatter(timePattern, formatLong));
};

const longFormatters = {
  p: timeLongFormatter,
  P: dateTimeLongFormatter,
};

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/protectedTokens.mjs
const dayOfYearTokenRE = /^D+$/;
const weekYearTokenRE = /^Y+$/;

const throwTokens = ["D", "DD", "YY", "YYYY"];

function isProtectedDayOfYearToken(token) {
  return dayOfYearTokenRE.test(token);
}

function isProtectedWeekYearToken(token) {
  return weekYearTokenRE.test(token);
}

function warnOrThrowProtectedError(token, format, input) {
  const _message = message(token, format, input);
  console.warn(_message);
  if (throwTokens.includes(token)) throw new RangeError(_message);
}

function message(token, format, input) {
  const subject = token[0] === "Y" ? "years" : "days of the month";
  return `Use \`${token.toLowerCase()}\` instead of \`${token}\` (in \`${format}\`) for formatting ${subject} to the input \`${input}\`; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md`;
}

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/isDate.mjs
/**
 * @name isDate
 * @category Common Helpers
 * @summary Is the given value a date?
 *
 * @description
 * Returns true if the given value is an instance of Date. The function works for dates transferred across iframes.
 *
 * @param value - The value to check
 *
 * @returns True if the given value is a date
 *
 * @example
 * // For a valid date:
 * const result = isDate(new Date())
 * //=> true
 *
 * @example
 * // For an invalid date:
 * const result = isDate(new Date(NaN))
 * //=> true
 *
 * @example
 * // For some value:
 * const result = isDate('2014-02-31')
 * //=> false
 *
 * @example
 * // For an object:
 * const result = isDate({})
 * //=> false
 */
function isDate(value) {
  return (
    value instanceof Date ||
    (typeof value === "object" &&
      Object.prototype.toString.call(value) === "[object Date]")
  );
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_isDate = ((/* unused pure expression or super */ null && (isDate)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/isValid.mjs



/**
 * @name isValid
 * @category Common Helpers
 * @summary Is the given date valid?
 *
 * @description
 * Returns false if argument is Invalid Date and true otherwise.
 * Argument is converted to Date using `toDate`. See [toDate](https://date-fns.org/docs/toDate)
 * Invalid Date is a Date, whose time value is NaN.
 *
 * Time value of Date: http://es5.github.io/#x15.9.1.1
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to check
 *
 * @returns The date is valid
 *
 * @example
 * // For the valid date:
 * const result = isValid(new Date(2014, 1, 31))
 * //=> true
 *
 * @example
 * // For the value, convertable into a date:
 * const result = isValid(1393804800000)
 * //=> true
 *
 * @example
 * // For the invalid date:
 * const result = isValid(new Date(''))
 * //=> false
 */
function isValid(date) {
  if (!isDate(date) && typeof date !== "number") {
    return false;
  }
  const _date = (0,toDate/* toDate */.a)(date);
  return !isNaN(Number(_date));
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_isValid = ((/* unused pure expression or super */ null && (isValid)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/format.mjs








// Rexports of internal for libraries to use.
// See: https://github.com/date-fns/date-fns/issues/3638#issuecomment-1877082874


// This RegExp consists of three parts separated by `|`:
// - [yYQqMLwIdDecihHKkms]o matches any available ordinal number token
//   (one of the certain letters followed by `o`)
// - (\w)\1* matches any sequences of the same letter
// - '' matches two quote characters in a row
// - '(''|[^'])+('|$) matches anything surrounded by two quote characters ('),
//   except a single quote symbol, which ends the sequence.
//   Two quote characters do not end the sequence.
//   If there is no matching single quote
//   then the sequence will continue until the end of the string.
// - . matches any single character unmatched by previous parts of the RegExps
const formattingTokensRegExp =
  /[yYQqMLwIdDecihHKkms]o|(\w)\1*|''|'(''|[^'])+('|$)|./g;

// This RegExp catches symbols escaped by quotes, and also
// sequences of symbols P, p, and the combinations like `PPPPPPPppppp`
const longFormattingTokensRegExp = /P+p+|P+|p+|''|'(''|[^'])+('|$)|./g;

const escapedStringRegExp = /^'([^]*?)'?$/;
const doubleQuoteRegExp = /''/g;
const unescapedLatinCharacterRegExp = /[a-zA-Z]/;



/**
 * The {@link format} function options.
 */

/**
 * @name format
 * @alias formatDate
 * @category Common Helpers
 * @summary Format the date.
 *
 * @description
 * Return the formatted date string in the given format. The result may vary by locale.
 *
 * > ⚠️ Please note that the `format` tokens differ from Moment.js and other libraries.
 * > See: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 *
 * The characters wrapped between two single quotes characters (') are escaped.
 * Two single quotes in a row, whether inside or outside a quoted sequence, represent a 'real' single quote.
 * (see the last example)
 *
 * Format of the string is based on Unicode Technical Standard #35:
 * https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table
 * with a few additions (see note 7 below the table).
 *
 * Accepted patterns:
 * | Unit                            | Pattern | Result examples                   | Notes |
 * |---------------------------------|---------|-----------------------------------|-------|
 * | Era                             | G..GGG  | AD, BC                            |       |
 * |                                 | GGGG    | Anno Domini, Before Christ        | 2     |
 * |                                 | GGGGG   | A, B                              |       |
 * | Calendar year                   | y       | 44, 1, 1900, 2017                 | 5     |
 * |                                 | yo      | 44th, 1st, 0th, 17th              | 5,7   |
 * |                                 | yy      | 44, 01, 00, 17                    | 5     |
 * |                                 | yyy     | 044, 001, 1900, 2017              | 5     |
 * |                                 | yyyy    | 0044, 0001, 1900, 2017            | 5     |
 * |                                 | yyyyy   | ...                               | 3,5   |
 * | Local week-numbering year       | Y       | 44, 1, 1900, 2017                 | 5     |
 * |                                 | Yo      | 44th, 1st, 1900th, 2017th         | 5,7   |
 * |                                 | YY      | 44, 01, 00, 17                    | 5,8   |
 * |                                 | YYY     | 044, 001, 1900, 2017              | 5     |
 * |                                 | YYYY    | 0044, 0001, 1900, 2017            | 5,8   |
 * |                                 | YYYYY   | ...                               | 3,5   |
 * | ISO week-numbering year         | R       | -43, 0, 1, 1900, 2017             | 5,7   |
 * |                                 | RR      | -43, 00, 01, 1900, 2017           | 5,7   |
 * |                                 | RRR     | -043, 000, 001, 1900, 2017        | 5,7   |
 * |                                 | RRRR    | -0043, 0000, 0001, 1900, 2017     | 5,7   |
 * |                                 | RRRRR   | ...                               | 3,5,7 |
 * | Extended year                   | u       | -43, 0, 1, 1900, 2017             | 5     |
 * |                                 | uu      | -43, 01, 1900, 2017               | 5     |
 * |                                 | uuu     | -043, 001, 1900, 2017             | 5     |
 * |                                 | uuuu    | -0043, 0001, 1900, 2017           | 5     |
 * |                                 | uuuuu   | ...                               | 3,5   |
 * | Quarter (formatting)            | Q       | 1, 2, 3, 4                        |       |
 * |                                 | Qo      | 1st, 2nd, 3rd, 4th                | 7     |
 * |                                 | QQ      | 01, 02, 03, 04                    |       |
 * |                                 | QQQ     | Q1, Q2, Q3, Q4                    |       |
 * |                                 | QQQQ    | 1st quarter, 2nd quarter, ...     | 2     |
 * |                                 | QQQQQ   | 1, 2, 3, 4                        | 4     |
 * | Quarter (stand-alone)           | q       | 1, 2, 3, 4                        |       |
 * |                                 | qo      | 1st, 2nd, 3rd, 4th                | 7     |
 * |                                 | qq      | 01, 02, 03, 04                    |       |
 * |                                 | qqq     | Q1, Q2, Q3, Q4                    |       |
 * |                                 | qqqq    | 1st quarter, 2nd quarter, ...     | 2     |
 * |                                 | qqqqq   | 1, 2, 3, 4                        | 4     |
 * | Month (formatting)              | M       | 1, 2, ..., 12                     |       |
 * |                                 | Mo      | 1st, 2nd, ..., 12th               | 7     |
 * |                                 | MM      | 01, 02, ..., 12                   |       |
 * |                                 | MMM     | Jan, Feb, ..., Dec                |       |
 * |                                 | MMMM    | January, February, ..., December  | 2     |
 * |                                 | MMMMM   | J, F, ..., D                      |       |
 * | Month (stand-alone)             | L       | 1, 2, ..., 12                     |       |
 * |                                 | Lo      | 1st, 2nd, ..., 12th               | 7     |
 * |                                 | LL      | 01, 02, ..., 12                   |       |
 * |                                 | LLL     | Jan, Feb, ..., Dec                |       |
 * |                                 | LLLL    | January, February, ..., December  | 2     |
 * |                                 | LLLLL   | J, F, ..., D                      |       |
 * | Local week of year              | w       | 1, 2, ..., 53                     |       |
 * |                                 | wo      | 1st, 2nd, ..., 53th               | 7     |
 * |                                 | ww      | 01, 02, ..., 53                   |       |
 * | ISO week of year                | I       | 1, 2, ..., 53                     | 7     |
 * |                                 | Io      | 1st, 2nd, ..., 53th               | 7     |
 * |                                 | II      | 01, 02, ..., 53                   | 7     |
 * | Day of month                    | d       | 1, 2, ..., 31                     |       |
 * |                                 | do      | 1st, 2nd, ..., 31st               | 7     |
 * |                                 | dd      | 01, 02, ..., 31                   |       |
 * | Day of year                     | D       | 1, 2, ..., 365, 366               | 9     |
 * |                                 | Do      | 1st, 2nd, ..., 365th, 366th       | 7     |
 * |                                 | DD      | 01, 02, ..., 365, 366             | 9     |
 * |                                 | DDD     | 001, 002, ..., 365, 366           |       |
 * |                                 | DDDD    | ...                               | 3     |
 * | Day of week (formatting)        | E..EEE  | Mon, Tue, Wed, ..., Sun           |       |
 * |                                 | EEEE    | Monday, Tuesday, ..., Sunday      | 2     |
 * |                                 | EEEEE   | M, T, W, T, F, S, S               |       |
 * |                                 | EEEEEE  | Mo, Tu, We, Th, Fr, Sa, Su        |       |
 * | ISO day of week (formatting)    | i       | 1, 2, 3, ..., 7                   | 7     |
 * |                                 | io      | 1st, 2nd, ..., 7th                | 7     |
 * |                                 | ii      | 01, 02, ..., 07                   | 7     |
 * |                                 | iii     | Mon, Tue, Wed, ..., Sun           | 7     |
 * |                                 | iiii    | Monday, Tuesday, ..., Sunday      | 2,7   |
 * |                                 | iiiii   | M, T, W, T, F, S, S               | 7     |
 * |                                 | iiiiii  | Mo, Tu, We, Th, Fr, Sa, Su        | 7     |
 * | Local day of week (formatting)  | e       | 2, 3, 4, ..., 1                   |       |
 * |                                 | eo      | 2nd, 3rd, ..., 1st                | 7     |
 * |                                 | ee      | 02, 03, ..., 01                   |       |
 * |                                 | eee     | Mon, Tue, Wed, ..., Sun           |       |
 * |                                 | eeee    | Monday, Tuesday, ..., Sunday      | 2     |
 * |                                 | eeeee   | M, T, W, T, F, S, S               |       |
 * |                                 | eeeeee  | Mo, Tu, We, Th, Fr, Sa, Su        |       |
 * | Local day of week (stand-alone) | c       | 2, 3, 4, ..., 1                   |       |
 * |                                 | co      | 2nd, 3rd, ..., 1st                | 7     |
 * |                                 | cc      | 02, 03, ..., 01                   |       |
 * |                                 | ccc     | Mon, Tue, Wed, ..., Sun           |       |
 * |                                 | cccc    | Monday, Tuesday, ..., Sunday      | 2     |
 * |                                 | ccccc   | M, T, W, T, F, S, S               |       |
 * |                                 | cccccc  | Mo, Tu, We, Th, Fr, Sa, Su        |       |
 * | AM, PM                          | a..aa   | AM, PM                            |       |
 * |                                 | aaa     | am, pm                            |       |
 * |                                 | aaaa    | a.m., p.m.                        | 2     |
 * |                                 | aaaaa   | a, p                              |       |
 * | AM, PM, noon, midnight          | b..bb   | AM, PM, noon, midnight            |       |
 * |                                 | bbb     | am, pm, noon, midnight            |       |
 * |                                 | bbbb    | a.m., p.m., noon, midnight        | 2     |
 * |                                 | bbbbb   | a, p, n, mi                       |       |
 * | Flexible day period             | B..BBB  | at night, in the morning, ...     |       |
 * |                                 | BBBB    | at night, in the morning, ...     | 2     |
 * |                                 | BBBBB   | at night, in the morning, ...     |       |
 * | Hour [1-12]                     | h       | 1, 2, ..., 11, 12                 |       |
 * |                                 | ho      | 1st, 2nd, ..., 11th, 12th         | 7     |
 * |                                 | hh      | 01, 02, ..., 11, 12               |       |
 * | Hour [0-23]                     | H       | 0, 1, 2, ..., 23                  |       |
 * |                                 | Ho      | 0th, 1st, 2nd, ..., 23rd          | 7     |
 * |                                 | HH      | 00, 01, 02, ..., 23               |       |
 * | Hour [0-11]                     | K       | 1, 2, ..., 11, 0                  |       |
 * |                                 | Ko      | 1st, 2nd, ..., 11th, 0th          | 7     |
 * |                                 | KK      | 01, 02, ..., 11, 00               |       |
 * | Hour [1-24]                     | k       | 24, 1, 2, ..., 23                 |       |
 * |                                 | ko      | 24th, 1st, 2nd, ..., 23rd         | 7     |
 * |                                 | kk      | 24, 01, 02, ..., 23               |       |
 * | Minute                          | m       | 0, 1, ..., 59                     |       |
 * |                                 | mo      | 0th, 1st, ..., 59th               | 7     |
 * |                                 | mm      | 00, 01, ..., 59                   |       |
 * | Second                          | s       | 0, 1, ..., 59                     |       |
 * |                                 | so      | 0th, 1st, ..., 59th               | 7     |
 * |                                 | ss      | 00, 01, ..., 59                   |       |
 * | Fraction of second              | S       | 0, 1, ..., 9                      |       |
 * |                                 | SS      | 00, 01, ..., 99                   |       |
 * |                                 | SSS     | 000, 001, ..., 999                |       |
 * |                                 | SSSS    | ...                               | 3     |
 * | Timezone (ISO-8601 w/ Z)        | X       | -08, +0530, Z                     |       |
 * |                                 | XX      | -0800, +0530, Z                   |       |
 * |                                 | XXX     | -08:00, +05:30, Z                 |       |
 * |                                 | XXXX    | -0800, +0530, Z, +123456          | 2     |
 * |                                 | XXXXX   | -08:00, +05:30, Z, +12:34:56      |       |
 * | Timezone (ISO-8601 w/o Z)       | x       | -08, +0530, +00                   |       |
 * |                                 | xx      | -0800, +0530, +0000               |       |
 * |                                 | xxx     | -08:00, +05:30, +00:00            | 2     |
 * |                                 | xxxx    | -0800, +0530, +0000, +123456      |       |
 * |                                 | xxxxx   | -08:00, +05:30, +00:00, +12:34:56 |       |
 * | Timezone (GMT)                  | O...OOO | GMT-8, GMT+5:30, GMT+0            |       |
 * |                                 | OOOO    | GMT-08:00, GMT+05:30, GMT+00:00   | 2     |
 * | Timezone (specific non-locat.)  | z...zzz | GMT-8, GMT+5:30, GMT+0            | 6     |
 * |                                 | zzzz    | GMT-08:00, GMT+05:30, GMT+00:00   | 2,6   |
 * | Seconds timestamp               | t       | 512969520                         | 7     |
 * |                                 | tt      | ...                               | 3,7   |
 * | Milliseconds timestamp          | T       | 512969520900                      | 7     |
 * |                                 | TT      | ...                               | 3,7   |
 * | Long localized date             | P       | 04/29/1453                        | 7     |
 * |                                 | PP      | Apr 29, 1453                      | 7     |
 * |                                 | PPP     | April 29th, 1453                  | 7     |
 * |                                 | PPPP    | Friday, April 29th, 1453          | 2,7   |
 * | Long localized time             | p       | 12:00 AM                          | 7     |
 * |                                 | pp      | 12:00:00 AM                       | 7     |
 * |                                 | ppp     | 12:00:00 AM GMT+2                 | 7     |
 * |                                 | pppp    | 12:00:00 AM GMT+02:00             | 2,7   |
 * | Combination of date and time    | Pp      | 04/29/1453, 12:00 AM              | 7     |
 * |                                 | PPpp    | Apr 29, 1453, 12:00:00 AM         | 7     |
 * |                                 | PPPppp  | April 29th, 1453 at ...           | 7     |
 * |                                 | PPPPpppp| Friday, April 29th, 1453 at ...   | 2,7   |
 * Notes:
 * 1. "Formatting" units (e.g. formatting quarter) in the default en-US locale
 *    are the same as "stand-alone" units, but are different in some languages.
 *    "Formatting" units are declined according to the rules of the language
 *    in the context of a date. "Stand-alone" units are always nominative singular:
 *
 *    `format(new Date(2017, 10, 6), 'do LLLL', {locale: cs}) //=> '6. listopad'`
 *
 *    `format(new Date(2017, 10, 6), 'do MMMM', {locale: cs}) //=> '6. listopadu'`
 *
 * 2. Any sequence of the identical letters is a pattern, unless it is escaped by
 *    the single quote characters (see below).
 *    If the sequence is longer than listed in table (e.g. `EEEEEEEEEEE`)
 *    the output will be the same as default pattern for this unit, usually
 *    the longest one (in case of ISO weekdays, `EEEE`). Default patterns for units
 *    are marked with "2" in the last column of the table.
 *
 *    `format(new Date(2017, 10, 6), 'MMM') //=> 'Nov'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMM') //=> 'November'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMMM') //=> 'N'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMMMM') //=> 'November'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMMMMM') //=> 'November'`
 *
 * 3. Some patterns could be unlimited length (such as `yyyyyyyy`).
 *    The output will be padded with zeros to match the length of the pattern.
 *
 *    `format(new Date(2017, 10, 6), 'yyyyyyyy') //=> '00002017'`
 *
 * 4. `QQQQQ` and `qqqqq` could be not strictly numerical in some locales.
 *    These tokens represent the shortest form of the quarter.
 *
 * 5. The main difference between `y` and `u` patterns are B.C. years:
 *
 *    | Year | `y` | `u` |
 *    |------|-----|-----|
 *    | AC 1 |   1 |   1 |
 *    | BC 1 |   1 |   0 |
 *    | BC 2 |   2 |  -1 |
 *
 *    Also `yy` always returns the last two digits of a year,
 *    while `uu` pads single digit years to 2 characters and returns other years unchanged:
 *
 *    | Year | `yy` | `uu` |
 *    |------|------|------|
 *    | 1    |   01 |   01 |
 *    | 14   |   14 |   14 |
 *    | 376  |   76 |  376 |
 *    | 1453 |   53 | 1453 |
 *
 *    The same difference is true for local and ISO week-numbering years (`Y` and `R`),
 *    except local week-numbering years are dependent on `options.weekStartsOn`
 *    and `options.firstWeekContainsDate` (compare [getISOWeekYear](https://date-fns.org/docs/getISOWeekYear)
 *    and [getWeekYear](https://date-fns.org/docs/getWeekYear)).
 *
 * 6. Specific non-location timezones are currently unavailable in `date-fns`,
 *    so right now these tokens fall back to GMT timezones.
 *
 * 7. These patterns are not in the Unicode Technical Standard #35:
 *    - `i`: ISO day of week
 *    - `I`: ISO week of year
 *    - `R`: ISO week-numbering year
 *    - `t`: seconds timestamp
 *    - `T`: milliseconds timestamp
 *    - `o`: ordinal number modifier
 *    - `P`: long localized date
 *    - `p`: long localized time
 *
 * 8. `YY` and `YYYY` tokens represent week-numbering years but they are often confused with years.
 *    You should enable `options.useAdditionalWeekYearTokens` to use them. See: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 *
 * 9. `D` and `DD` tokens represent days of the year but they are often confused with days of the month.
 *    You should enable `options.useAdditionalDayOfYearTokens` to use them. See: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 * @param format - The string of tokens
 * @param options - An object with options
 *
 * @returns The formatted date string
 *
 * @throws `date` must not be Invalid Date
 * @throws `options.locale` must contain `localize` property
 * @throws `options.locale` must contain `formatLong` property
 * @throws use `yyyy` instead of `YYYY` for formatting years using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws use `yy` instead of `YY` for formatting years using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws use `d` instead of `D` for formatting days of the month using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws use `dd` instead of `DD` for formatting days of the month using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws format string contains an unescaped latin alphabet character
 *
 * @example
 * // Represent 11 February 2014 in middle-endian format:
 * const result = format(new Date(2014, 1, 11), 'MM/dd/yyyy')
 * //=> '02/11/2014'
 *
 * @example
 * // Represent 2 July 2014 in Esperanto:
 * import { eoLocale } from 'date-fns/locale/eo'
 * const result = format(new Date(2014, 6, 2), "do 'de' MMMM yyyy", {
 *   locale: eoLocale
 * })
 * //=> '2-a de julio 2014'
 *
 * @example
 * // Escape string by single quote characters:
 * const result = format(new Date(2014, 6, 2, 15), "h 'o''clock'")
 * //=> "3 o'clock"
 */
function format(date, formatStr, options) {
  const defaultOptions = (0,_lib_defaultOptions/* getDefaultOptions */.q)();
  const locale = options?.locale ?? defaultOptions.locale ?? enUS;

  const firstWeekContainsDate =
    options?.firstWeekContainsDate ??
    options?.locale?.options?.firstWeekContainsDate ??
    defaultOptions.firstWeekContainsDate ??
    defaultOptions.locale?.options?.firstWeekContainsDate ??
    1;

  const weekStartsOn =
    options?.weekStartsOn ??
    options?.locale?.options?.weekStartsOn ??
    defaultOptions.weekStartsOn ??
    defaultOptions.locale?.options?.weekStartsOn ??
    0;

  const originalDate = (0,toDate/* toDate */.a)(date);

  if (!isValid(originalDate)) {
    throw new RangeError("Invalid time value");
  }

  let parts = formatStr
    .match(longFormattingTokensRegExp)
    .map((substring) => {
      const firstCharacter = substring[0];
      if (firstCharacter === "p" || firstCharacter === "P") {
        const longFormatter = longFormatters[firstCharacter];
        return longFormatter(substring, locale.formatLong);
      }
      return substring;
    })
    .join("")
    .match(formattingTokensRegExp)
    .map((substring) => {
      // Replace two single quote characters with one single quote character
      if (substring === "''") {
        return { isToken: false, value: "'" };
      }

      const firstCharacter = substring[0];
      if (firstCharacter === "'") {
        return { isToken: false, value: cleanEscapedString(substring) };
      }

      if (formatters[firstCharacter]) {
        return { isToken: true, value: substring };
      }

      if (firstCharacter.match(unescapedLatinCharacterRegExp)) {
        throw new RangeError(
          "Format string contains an unescaped latin alphabet character `" +
            firstCharacter +
            "`",
        );
      }

      return { isToken: false, value: substring };
    });

  // invoke localize preprocessor (only for french locales at the moment)
  if (locale.localize.preprocessor) {
    parts = locale.localize.preprocessor(originalDate, parts);
  }

  const formatterOptions = {
    firstWeekContainsDate,
    weekStartsOn,
    locale,
  };

  return parts
    .map((part) => {
      if (!part.isToken) return part.value;

      const token = part.value;

      if (
        (!options?.useAdditionalWeekYearTokens &&
          isProtectedWeekYearToken(token)) ||
        (!options?.useAdditionalDayOfYearTokens &&
          isProtectedDayOfYearToken(token))
      ) {
        warnOrThrowProtectedError(token, formatStr, String(date));
      }

      const formatter = formatters[token[0]];
      return formatter(originalDate, token, locale.localize, formatterOptions);
    })
    .join("");
}

function cleanEscapedString(input) {
  const matched = input.match(escapedStringRegExp);

  if (!matched) {
    return input;
  }

  return matched[1].replace(doubleQuoteRegExp, "'");
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_format = ((/* unused pure expression or super */ null && (format)));


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/set.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ set)
/* harmony export */ });
/* harmony import */ var _constructFrom_mjs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs");
/* harmony import */ var _setMonth_mjs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setMonth.mjs");
/* harmony import */ var _toDate_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");




/**
 * @name set
 * @category Common Helpers
 * @summary Set date values to a given date.
 *
 * @description
 * Set date values to a given date.
 *
 * Sets time values to date from object `values`.
 * A value is not set if it is undefined or null or doesn't exist in `values`.
 *
 * Note about bundle size: `set` does not internally use `setX` functions from date-fns but instead opts
 * to use native `Date#setX` methods. If you use this function, you may not want to include the
 * other `setX` functions that date-fns provides if you are concerned about the bundle size.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param values - The date values to be set
 *
 * @returns The new date with options set
 *
 * @example
 * // Transform 1 September 2014 into 20 October 2015 in a single line:
 * const result = set(new Date(2014, 8, 20), { year: 2015, month: 9, date: 20 })
 * //=> Tue Oct 20 2015 00:00:00
 *
 * @example
 * // Set 12 PM to 1 September 2014 01:23:45 to 1 September 2014 12:00:00:
 * const result = set(new Date(2014, 8, 1, 1, 23, 45), { hours: 12 })
 * //=> Mon Sep 01 2014 12:23:45
 */

function set(date, values) {
  let _date = (0,_toDate_mjs__WEBPACK_IMPORTED_MODULE_0__/* .toDate */ .a)(date);

  // Check if date is Invalid Date because Date.prototype.setFullYear ignores the value of Invalid Date
  if (isNaN(+_date)) {
    return (0,_constructFrom_mjs__WEBPACK_IMPORTED_MODULE_1__/* .constructFrom */ .w)(date, NaN);
  }

  if (values.year != null) {
    _date.setFullYear(values.year);
  }

  if (values.month != null) {
    _date = (0,_setMonth_mjs__WEBPACK_IMPORTED_MODULE_2__/* .setMonth */ .Z)(_date, values.month);
  }

  if (values.date != null) {
    _date.setDate(values.date);
  }

  if (values.hours != null) {
    _date.setHours(values.hours);
  }

  if (values.minutes != null) {
    _date.setMinutes(values.minutes);
  }

  if (values.seconds != null) {
    _date.setSeconds(values.seconds);
  }

  if (values.milliseconds != null) {
    _date.setMilliseconds(values.milliseconds);
  }

  return _date;
}

// Fallback for modularized imports:
/* unused harmony default export */ var __WEBPACK_DEFAULT_EXPORT__ = ((/* unused pure expression or super */ null && (set)));


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setMonth.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Z: () => (/* binding */ setMonth)
});

// UNUSED EXPORTS: default

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs
var constructFrom = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/constructFrom.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs
var toDate = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/getDaysInMonth.mjs



/**
 * @name getDaysInMonth
 * @category Month Helpers
 * @summary Get the number of days in a month of the given date.
 *
 * @description
 * Get the number of days in a month of the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The given date
 *
 * @returns The number of days in a month
 *
 * @example
 * // How many days are in February 2000?
 * const result = getDaysInMonth(new Date(2000, 1))
 * //=> 29
 */
function getDaysInMonth(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  const year = _date.getFullYear();
  const monthIndex = _date.getMonth();
  const lastDayOfMonth = (0,constructFrom/* constructFrom */.w)(date, 0);
  lastDayOfMonth.setFullYear(year, monthIndex + 1, 0);
  lastDayOfMonth.setHours(0, 0, 0, 0);
  return lastDayOfMonth.getDate();
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_getDaysInMonth = ((/* unused pure expression or super */ null && (getDaysInMonth)));

;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setMonth.mjs




/**
 * @name setMonth
 * @category Month Helpers
 * @summary Set the month to the given date.
 *
 * @description
 * Set the month to the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The date to be changed
 * @param month - The month index to set (0-11)
 *
 * @returns The new date with the month set
 *
 * @example
 * // Set February to 1 September 2014:
 * const result = setMonth(new Date(2014, 8, 1), 1)
 * //=> Sat Feb 01 2014 00:00:00
 */
function setMonth(date, month) {
  const _date = (0,toDate/* toDate */.a)(date);
  const year = _date.getFullYear();
  const day = _date.getDate();

  const dateWithDesiredMonth = (0,constructFrom/* constructFrom */.w)(date, 0);
  dateWithDesiredMonth.setFullYear(year, month, 15);
  dateWithDesiredMonth.setHours(0, 0, 0, 0);
  const daysInMonth = getDaysInMonth(dateWithDesiredMonth);
  // Set the last day of the new month
  // if the original date was the last day of the longer month
  _date.setMonth(month, Math.min(day, daysInMonth));
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_setMonth = ((/* unused pure expression or super */ null && (setMonth)));


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfDay.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   o: () => (/* binding */ startOfDay)
/* harmony export */ });
/* harmony import */ var _toDate_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");


/**
 * @name startOfDay
 * @category Day Helpers
 * @summary Return the start of a day for the given date.
 *
 * @description
 * Return the start of a day for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 *
 * @returns The start of a day
 *
 * @example
 * // The start of a day for 2 September 2014 11:55:00:
 * const result = startOfDay(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Tue Sep 02 2014 00:00:00
 */
function startOfDay(date) {
  const _date = (0,_toDate_mjs__WEBPACK_IMPORTED_MODULE_0__/* .toDate */ .a)(date);
  _date.setHours(0, 0, 0, 0);
  return _date;
}

// Fallback for modularized imports:
/* unused harmony default export */ var __WEBPACK_DEFAULT_EXPORT__ = ((/* unused pure expression or super */ null && (startOfDay)));


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfWeek.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   k: () => (/* binding */ startOfWeek)
/* harmony export */ });
/* harmony import */ var _toDate_mjs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");
/* harmony import */ var _lib_defaultOptions_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/_lib/defaultOptions.mjs");



/**
 * The {@link startOfWeek} function options.
 */

/**
 * @name startOfWeek
 * @category Week Helpers
 * @summary Return the start of a week for the given date.
 *
 * @description
 * Return the start of a week for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 * @param options - An object with options
 *
 * @returns The start of a week
 *
 * @example
 * // The start of a week for 2 September 2014 11:55:00:
 * const result = startOfWeek(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Sun Aug 31 2014 00:00:00
 *
 * @example
 * // If the week starts on Monday, the start of the week for 2 September 2014 11:55:00:
 * const result = startOfWeek(new Date(2014, 8, 2, 11, 55, 0), { weekStartsOn: 1 })
 * //=> Mon Sep 01 2014 00:00:00
 */
function startOfWeek(date, options) {
  const defaultOptions = (0,_lib_defaultOptions_mjs__WEBPACK_IMPORTED_MODULE_0__/* .getDefaultOptions */ .q)();
  const weekStartsOn =
    options?.weekStartsOn ??
    options?.locale?.options?.weekStartsOn ??
    defaultOptions.weekStartsOn ??
    defaultOptions.locale?.options?.weekStartsOn ??
    0;

  const _date = (0,_toDate_mjs__WEBPACK_IMPORTED_MODULE_1__/* .toDate */ .a)(date);
  const day = _date.getDay();
  const diff = (day < weekStartsOn ? 7 : 0) + day - weekStartsOn;

  _date.setDate(_date.getDate() - diff);
  _date.setHours(0, 0, 0, 0);
  return _date;
}

// Fallback for modularized imports:
/* unused harmony default export */ var __WEBPACK_DEFAULT_EXPORT__ = ((/* unused pure expression or super */ null && (startOfWeek)));


/***/ }),

/***/ "../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ toDate)
/* harmony export */ });
/**
 * @name toDate
 * @category Common Helpers
 * @summary Convert the given argument to an instance of Date.
 *
 * @description
 * Convert the given argument to an instance of Date.
 *
 * If the argument is an instance of Date, the function returns its clone.
 *
 * If the argument is a number, it is treated as a timestamp.
 *
 * If the argument is none of the above, the function returns Invalid Date.
 *
 * **Note**: *all* Date arguments passed to any *date-fns* function is processed by `toDate`.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param argument - The value to convert
 *
 * @returns The parsed date in the local time zone
 *
 * @example
 * // Clone the date:
 * const result = toDate(new Date(2014, 1, 11, 11, 30, 30))
 * //=> Tue Feb 11 2014 11:30:30
 *
 * @example
 * // Convert the timestamp to date:
 * const result = toDate(1392098430000)
 * //=> Tue Feb 11 2014 11:30:30
 */
function toDate(argument) {
  const argStr = Object.prototype.toString.call(argument);

  // Clone the date
  if (
    argument instanceof Date ||
    (typeof argument === "object" && argStr === "[object Date]")
  ) {
    // Prevent the date to lose the milliseconds when passed to new Date() in IE10
    return new argument.constructor(+argument);
  } else if (
    typeof argument === "number" ||
    argStr === "[object Number]" ||
    typeof argument === "string" ||
    argStr === "[object String]"
  ) {
    // TODO: Can we get rid of as?
    return new Date(argument);
  } else {
    // TODO: Can we get rid of as?
    return new Date(NaN);
  }
}

// Fallback for modularized imports:
/* unused harmony default export */ var __WEBPACK_DEFAULT_EXPORT__ = ((/* unused pure expression or super */ null && (toDate)));


/***/ })

}]);