(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7302],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _arrayLikeToArray)
/* harmony export */ });
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(obj, key, value) {
  key = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(key);
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/iterableToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _iterableToArray)
/* harmony export */ });
function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _toConsumableArray)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js
var arrayLikeToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return (0,arrayLikeToArray/* default */.A)(arr);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/iterableToArray.js
var iterableToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/iterableToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js
var unsupportedIterableToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js
function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js




function _toConsumableArray(arr) {
  return _arrayWithoutHoles(arr) || (0,iterableToArray/* default */.A)(arr) || (0,unsupportedIterableToArray/* default */.A)(arr) || _nonIterableSpread();
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _toPropertyKey)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPrimitive.js

function _toPrimitive(input, hint) {
  if ((0,esm_typeof/* default */.A)(input) !== "object" || input === null) return input;
  var prim = input[Symbol.toPrimitive];
  if (prim !== undefined) {
    var res = prim.call(input, hint || "default");
    if ((0,esm_typeof/* default */.A)(res) !== "object") return res;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (hint === "string" ? String : Number)(input);
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js


function _toPropertyKey(arg) {
  var key = _toPrimitive(arg, "string");
  return (0,esm_typeof/* default */.A)(key) === "symbol" ? key : String(key);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _unsupportedIterableToArray)
/* harmony export */ });
/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(o, minLen);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+date@4.6.1/node_modules/@wordpress/date/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GP: () => (/* binding */ format)
/* harmony export */ });
/* unused harmony exports setSettings, __experimentalGetSettings, date, gmdate, dateI18n, gmdateI18n, isInTheFuture, getDate */
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var moment_timezone_moment_timezone__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/moment-timezone@0.5.43/node_modules/moment-timezone/moment-timezone.js");
/* harmony import */ var moment_timezone_moment_timezone__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(moment_timezone_moment_timezone__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var moment_timezone_moment_timezone_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/moment-timezone@0.5.43/node_modules/moment-timezone/moment-timezone-utils.js");
/* harmony import */ var moment_timezone_moment_timezone_utils__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(moment_timezone_moment_timezone_utils__WEBPACK_IMPORTED_MODULE_2__);
/**
 * External dependencies
 */



/** @typedef {import('moment').Moment} Moment */

/** @typedef {import('moment').LocaleSpecification} MomentLocaleSpecification */

/**
 * @typedef MeridiemConfig
 * @property {string} am Lowercase AM.
 * @property {string} AM Uppercase AM.
 * @property {string} pm Lowercase PM.
 * @property {string} PM Uppercase PM.
 */

/**
 * @typedef FormatsConfig
 * @property {string} time                Time format.
 * @property {string} date                Date format.
 * @property {string} datetime            Datetime format.
 * @property {string} datetimeAbbreviated Abbreviated datetime format.
 */

/**
 * @typedef TimezoneConfig
 * @property {string} offset Offset setting.
 * @property {string} string The timezone as a string (e.g., `'America/Los_Angeles'`).
 * @property {string} abbr   Abbreviation for the timezone.
 */

/* eslint-disable jsdoc/valid-types */

/**
 * @typedef L10nSettings
 * @property {string}                                     locale        Moment locale.
 * @property {MomentLocaleSpecification['months']}        months        Locale months.
 * @property {MomentLocaleSpecification['monthsShort']}   monthsShort   Locale months short.
 * @property {MomentLocaleSpecification['weekdays']}      weekdays      Locale weekdays.
 * @property {MomentLocaleSpecification['weekdaysShort']} weekdaysShort Locale weekdays short.
 * @property {MeridiemConfig}                             meridiem      Meridiem config.
 * @property {MomentLocaleSpecification['relativeTime']}  relative      Relative time config.
 */

/* eslint-enable jsdoc/valid-types */

/**
 * @typedef DateSettings
 * @property {L10nSettings}   l10n     Localization settings.
 * @property {FormatsConfig}  formats  Date/time formats config.
 * @property {TimezoneConfig} timezone Timezone settings.
 */

const WP_ZONE = 'WP'; // This regular expression tests positive for UTC offsets as described in ISO 8601.
// See: https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC

const VALID_UTC_OFFSET = /^[+-][0-1][0-9](:?[0-9][0-9])?$/; // Changes made here will likely need to be made in `lib/client-assets.php` as
// well because it uses the `setSettings()` function to change these settings.

/** @type {DateSettings} */

let settings = {
  l10n: {
    locale: 'en',
    months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    weekdays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    weekdaysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    meridiem: {
      am: 'am',
      pm: 'pm',
      AM: 'AM',
      PM: 'PM'
    },
    relative: {
      future: '%s from now',
      past: '%s ago',
      s: 'a few seconds',
      ss: '%d seconds',
      m: 'a minute',
      mm: '%d minutes',
      h: 'an hour',
      hh: '%d hours',
      d: 'a day',
      dd: '%d days',
      M: 'a month',
      MM: '%d months',
      y: 'a year',
      yy: '%d years'
    }
  },
  formats: {
    time: 'g: i a',
    date: 'F j, Y',
    datetime: 'F j, Y g: i a',
    datetimeAbbreviated: 'M j, Y g: i a'
  },
  timezone: {
    offset: '0',
    string: '',
    abbr: ''
  }
};
/**
 * Adds a locale to moment, using the format supplied by `wp_localize_script()`.
 *
 * @param {DateSettings} dateSettings Settings, including locale data.
 */

function setSettings(dateSettings) {
  settings = dateSettings;
  setupWPTimezone(); // Does moment already have a locale with the right name?

  if (momentLib.locales().includes(dateSettings.l10n.locale)) {
    // Is that locale misconfigured, e.g. because we are on a site running
    // WordPress < 6.0?
    if (momentLib.localeData(dateSettings.l10n.locale).longDateFormat('LTS') === null) {
      // Delete the misconfigured locale.
      // @ts-ignore Type definitions are incorrect - null is permitted.
      momentLib.defineLocale(dateSettings.l10n.locale, null);
    } else {
      // We have a properly configured locale, so no need to create one.
      return;
    }
  } // defineLocale() will modify the current locale, so back it up.


  const currentLocale = momentLib.locale(); // Create locale.

  momentLib.defineLocale(dateSettings.l10n.locale, {
    // Inherit anything missing from English. We don't load
    // moment-with-locales.js so English is all there is.
    parentLocale: 'en',
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
      LTS: momentLib.localeData('en').longDateFormat('LTS'),
      L: momentLib.localeData('en').longDateFormat('L'),
      LL: dateSettings.formats.date,
      LLL: dateSettings.formats.datetime,
      LLLL: momentLib.localeData('en').longDateFormat('LLLL')
    },
    // From human_time_diff?
    // Set to `(number, withoutSuffix, key, isFuture) => {}` instead.
    relativeTime: dateSettings.l10n.relative
  }); // Restore the locale to what it was.

  momentLib.locale(currentLocale);
}
/**
 * Returns the currently defined date settings.
 *
 * @return {Object} Settings, including locale data.
 */

function __experimentalGetSettings() {
  return settings;
}

function setupWPTimezone() {
  // Create WP timezone based off dateSettings.
  moment__WEBPACK_IMPORTED_MODULE_0___default().tz.add(moment__WEBPACK_IMPORTED_MODULE_0___default().tz.pack({
    name: WP_ZONE,
    abbrs: [WP_ZONE],
    untils: [null],
    offsets: [-settings.timezone.offset * 60 || 0]
  }));
} // Date constants.

/**
 * Number of seconds in one minute.
 *
 * @type {number}
 */


const MINUTE_IN_SECONDS = 60;
/**
 * Number of minutes in one hour.
 *
 * @type {number}
 */

const HOUR_IN_MINUTES = 60;
/**
 * Number of seconds in one hour.
 *
 * @type {number}
 */

const HOUR_IN_SECONDS = 60 * MINUTE_IN_SECONDS;
/**
 * Map of PHP formats to Moment.js formats.
 *
 * These are used internally by {@link wp.date.format}, and are either
 * a string representing the corresponding Moment.js format code, or a
 * function which returns the formatted string.
 *
 * This should only be used through {@link wp.date.format}, not
 * directly.
 */

const formatMap = {
  // Day.
  d: 'DD',
  D: 'ddd',
  j: 'D',
  l: 'dddd',
  N: 'E',

  /**
   * Gets the ordinal suffix.
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {string} Formatted date.
   */
  S(momentDate) {
    // Do - D.
    const num = momentDate.format('D');
    const withOrdinal = momentDate.format('Do');
    return withOrdinal.replace(num, '');
  },

  w: 'd',

  /**
   * Gets the day of the year (zero-indexed).
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {string} Formatted date.
   */
  z(momentDate) {
    // DDD - 1.
    return (parseInt(momentDate.format('DDD'), 10) - 1).toString();
  },

  // Week.
  W: 'W',
  // Month.
  F: 'MMMM',
  m: 'MM',
  M: 'MMM',
  n: 'M',

  /**
   * Gets the days in the month.
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {number} Formatted date.
   */
  t(momentDate) {
    return momentDate.daysInMonth();
  },

  // Year.

  /**
   * Gets whether the current year is a leap year.
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {string} Formatted date.
   */
  L(momentDate) {
    return momentDate.isLeapYear() ? '1' : '0';
  },

  o: 'GGGG',
  Y: 'YYYY',
  y: 'YY',
  // Time.
  a: 'a',
  A: 'A',

  /**
   * Gets the current time in Swatch Internet Time (.beats).
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {number} Formatted date.
   */
  B(momentDate) {
    const timezoned = moment__WEBPACK_IMPORTED_MODULE_0___default()(momentDate).utcOffset(60);
    const seconds = parseInt(timezoned.format('s'), 10),
          minutes = parseInt(timezoned.format('m'), 10),
          hours = parseInt(timezoned.format('H'), 10);
    return parseInt(((seconds + minutes * MINUTE_IN_SECONDS + hours * HOUR_IN_SECONDS) / 86.4).toString(), 10);
  },

  g: 'h',
  G: 'H',
  h: 'hh',
  H: 'HH',
  i: 'mm',
  s: 'ss',
  u: 'SSSSSS',
  v: 'SSS',
  // Timezone.
  e: 'zz',

  /**
   * Gets whether the timezone is in DST currently.
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {string} Formatted date.
   */
  I(momentDate) {
    return momentDate.isDST() ? '1' : '0';
  },

  O: 'ZZ',
  P: 'Z',
  T: 'z',

  /**
   * Gets the timezone offset in seconds.
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {number} Formatted date.
   */
  Z(momentDate) {
    // Timezone offset in seconds.
    const offset = momentDate.format('Z');
    const sign = offset[0] === '-' ? -1 : 1;
    const parts = offset.substring(1).split(':').map(n => parseInt(n, 10));
    return sign * (parts[0] * HOUR_IN_MINUTES + parts[1]) * MINUTE_IN_SECONDS;
  },

  // Full date/time.
  c: 'YYYY-MM-DDTHH:mm:ssZ',

  // .toISOString.

  /**
   * Formats the date as RFC2822.
   *
   * @param {Moment} momentDate Moment instance.
   *
   * @return {string} Formatted date.
   */
  r(momentDate) {
    return momentDate.locale('en').format('ddd, DD MMM YYYY HH:mm:ss ZZ');
  },

  U: 'X'
};
/**
 * Formats a date. Does not alter the date's timezone.
 *
 * @param {string}                             dateFormat PHP-style formatting string.
 *                                                        See php.net/date.
 * @param {Moment | Date | string | undefined} dateValue  Date object or string,
 *                                                        parsable by moment.js.
 *
 * @return {string} Formatted date.
 */

function format(dateFormat) {
  let dateValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : new Date();
  let i, char;
  const newFormat = [];
  const momentDate = moment__WEBPACK_IMPORTED_MODULE_0___default()(dateValue);

  for (i = 0; i < dateFormat.length; i++) {
    char = dateFormat[i]; // Is this an escape?

    if ('\\' === char) {
      // Add next character, then move on.
      i++;
      newFormat.push('[' + dateFormat[i] + ']');
      continue;
    }

    if (char in formatMap) {
      const formatter = formatMap[
      /** @type {keyof formatMap} */
      char];

      if (typeof formatter !== 'string') {
        // If the format is a function, call it.
        newFormat.push('[' + formatter(momentDate) + ']');
      } else {
        // Otherwise, add as a formatting string.
        newFormat.push(formatter);
      }
    } else {
      newFormat.push('[' + char + ']');
    }
  } // Join with [] between to separate characters, and replace
  // unneeded separators with static text.


  return momentDate.format(newFormat.join('[]'));
}
/**
 * Formats a date (like `date()` in PHP).
 *
 * @param {string}                             dateFormat PHP-style formatting string.
 *                                                        See php.net/date.
 * @param {Moment | Date | string | undefined} dateValue  Date object or string, parsable
 *                                                        by moment.js.
 * @param {string | undefined}                 timezone   Timezone to output result in or a
 *                                                        UTC offset. Defaults to timezone from
 *                                                        site.
 *
 * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
 * @see https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC
 *
 * @return {string} Formatted date in English.
 */

function date(dateFormat) {
  let dateValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : new Date();
  let timezone = arguments.length > 2 ? arguments[2] : undefined;
  const dateMoment = buildMoment(dateValue, timezone);
  return format(dateFormat, dateMoment);
}
/**
 * Formats a date (like `date()` in PHP), in the UTC timezone.
 *
 * @param {string}                             dateFormat PHP-style formatting string.
 *                                                        See php.net/date.
 * @param {Moment | Date | string | undefined} dateValue  Date object or string,
 *                                                        parsable by moment.js.
 *
 * @return {string} Formatted date in English.
 */

function gmdate(dateFormat) {
  let dateValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : new Date();
  const dateMoment = momentLib(dateValue).utc();
  return format(dateFormat, dateMoment);
}
/**
 * Formats a date (like `wp_date()` in PHP), translating it into site's locale.
 *
 * Backward Compatibility Notice: if `timezone` is set to `true`, the function
 * behaves like `gmdateI18n`.
 *
 * @param {string}                             dateFormat PHP-style formatting string.
 *                                                        See php.net/date.
 * @param {Moment | Date | string | undefined} dateValue  Date object or string, parsable by
 *                                                        moment.js.
 * @param {string | boolean | undefined}       timezone   Timezone to output result in or a
 *                                                        UTC offset. Defaults to timezone from
 *                                                        site. Notice: `boolean` is effectively
 *                                                        deprecated, but still supported for
 *                                                        backward compatibility reasons.
 *
 * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
 * @see https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC
 *
 * @return {string} Formatted date.
 */

function dateI18n(dateFormat) {
  let dateValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : new Date();
  let timezone = arguments.length > 2 ? arguments[2] : undefined;

  if (true === timezone) {
    return gmdateI18n(dateFormat, dateValue);
  }

  if (false === timezone) {
    timezone = undefined;
  }

  const dateMoment = buildMoment(dateValue, timezone);
  dateMoment.locale(settings.l10n.locale);
  return format(dateFormat, dateMoment);
}
/**
 * Formats a date (like `wp_date()` in PHP), translating it into site's locale
 * and using the UTC timezone.
 *
 * @param {string}                             dateFormat PHP-style formatting string.
 *                                                        See php.net/date.
 * @param {Moment | Date | string | undefined} dateValue  Date object or string,
 *                                                        parsable by moment.js.
 *
 * @return {string} Formatted date.
 */

function gmdateI18n(dateFormat) {
  let dateValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : new Date();
  const dateMoment = momentLib(dateValue).utc();
  dateMoment.locale(settings.l10n.locale);
  return format(dateFormat, dateMoment);
}
/**
 * Check whether a date is considered in the future according to the WordPress settings.
 *
 * @param {string} dateValue Date String or Date object in the Defined WP Timezone.
 *
 * @return {boolean} Is in the future.
 */

function isInTheFuture(dateValue) {
  const now = momentLib.tz(WP_ZONE);
  const momentObject = momentLib.tz(dateValue, WP_ZONE);
  return momentObject.isAfter(now);
}
/**
 * Create and return a JavaScript Date Object from a date string in the WP timezone.
 *
 * @param {string?} dateString Date formatted in the WP timezone.
 *
 * @return {Date} Date
 */

function getDate(dateString) {
  if (!dateString) {
    return momentLib.tz(WP_ZONE).toDate();
  }

  return momentLib.tz(dateString, WP_ZONE).toDate();
}
/**
 * Creates a moment instance using the given timezone or, if none is provided, using global settings.
 *
 * @param {Moment | Date | string | undefined} dateValue Date object or string, parsable
 *                                                       by moment.js.
 * @param {string | undefined}                 timezone  Timezone to output result in or a
 *                                                       UTC offset. Defaults to timezone from
 *                                                       site.
 *
 * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
 * @see https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC
 *
 * @return {Moment} a moment instance.
 */

function buildMoment(dateValue) {
  let timezone = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  const dateMoment = momentLib(dateValue);

  if (timezone && !isUTCOffset(timezone)) {
    return dateMoment.tz(timezone);
  }

  if (timezone && isUTCOffset(timezone)) {
    return dateMoment.utcOffset(timezone);
  }

  if (settings.timezone.string) {
    return dateMoment.tz(settings.timezone.string);
  }

  return dateMoment.utcOffset(+settings.timezone.offset);
}
/**
 * Returns whether a certain UTC offset is valid or not.
 *
 * @param {number|string} offset a UTC offset.
 *
 * @return {boolean} whether a certain UTC offset is valid or not.
 */


function isUTCOffset(offset) {
  if ('number' === typeof offset) {
    return true;
  }

  return VALID_UTC_OFFSET.test(offset);
}

setupWPTimezone();
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $forEach = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").forEach);
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");

var STRICT_METHOD = arrayMethodIsStrict('forEach');

// `Array.prototype.forEach` method implementation
// https://tc39.es/ecma262/#sec-array.prototype.foreach
module.exports = !STRICT_METHOD ? function forEach(callbackfn /* , thisArg */) {
  return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
} : [].forEach;


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var wellKnownSymbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/well-known-symbol.js");
var V8_VERSION = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-v8-version.js");

var SPECIES = wellKnownSymbol('species');

module.exports = function (METHOD_NAME) {
  // We can't use this feature detection in V8 since it causes
  // deoptimization and serious performance degradation
  // https://github.com/zloirock/core-js/issues/677
  return V8_VERSION >= 51 || !fails(function () {
    var array = [];
    var constructor = array.constructor = {};
    constructor[SPECIES] = function () {
      return { foo: 1 };
    };
    return array[METHOD_NAME](Boolean).foo !== 1;
  });
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");

module.exports = function (METHOD_NAME, argument) {
  var method = [][METHOD_NAME];
  return !!method && fails(function () {
    // eslint-disable-next-line no-useless-call -- required for testing
    method.call(null, argument || function () { return 1; }, 1);
  });
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-reduce.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var aCallable = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/a-callable.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var IndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/indexed-object.js");
var lengthOfArrayLike = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/length-of-array-like.js");

var $TypeError = TypeError;

// `Array.prototype.{ reduce, reduceRight }` methods implementation
var createMethod = function (IS_RIGHT) {
  return function (that, callbackfn, argumentsLength, memo) {
    var O = toObject(that);
    var self = IndexedObject(O);
    var length = lengthOfArrayLike(O);
    aCallable(callbackfn);
    var index = IS_RIGHT ? length - 1 : 0;
    var i = IS_RIGHT ? -1 : 1;
    if (argumentsLength < 2) while (true) {
      if (index in self) {
        memo = self[index];
        index += i;
        break;
      }
      index += i;
      if (IS_RIGHT ? index < 0 : length <= index) {
        throw new $TypeError('Reduce of empty array with no initial value');
      }
    }
    for (;IS_RIGHT ? index >= 0 : length > index; index += i) if (index in self) {
      memo = callbackfn(memo, self[index], index, O);
    }
    return memo;
  };
};

module.exports = {
  // `Array.prototype.reduce` method
  // https://tc39.es/ecma262/#sec-array.prototype.reduce
  left: createMethod(false),
  // `Array.prototype.reduceRight` method
  // https://tc39.es/ecma262/#sec-array.prototype.reduceright
  right: createMethod(true)
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-sort.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var arraySlice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-slice-simple.js");

var floor = Math.floor;

var mergeSort = function (array, comparefn) {
  var length = array.length;
  var middle = floor(length / 2);
  return length < 8 ? insertionSort(array, comparefn) : merge(
    array,
    mergeSort(arraySlice(array, 0, middle), comparefn),
    mergeSort(arraySlice(array, middle), comparefn),
    comparefn
  );
};

var insertionSort = function (array, comparefn) {
  var length = array.length;
  var i = 1;
  var element, j;

  while (i < length) {
    j = i;
    element = array[i];
    while (j && comparefn(array[j - 1], element) > 0) {
      array[j] = array[--j];
    }
    if (j !== i++) array[j] = element;
  } return array;
};

var merge = function (array, left, right, comparefn) {
  var llength = left.length;
  var rlength = right.length;
  var lindex = 0;
  var rindex = 0;

  while (lindex < llength || rindex < rlength) {
    array[lindex + rindex] = (lindex < llength && rindex < rlength)
      ? comparefn(left[lindex], right[rindex]) <= 0 ? left[lindex++] : right[rindex++]
      : lindex < llength ? left[lindex++] : right[rindex++];
  } return array;
};

module.exports = mergeSort;


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/delete-property-or-throw.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var tryToString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/try-to-string.js");

var $TypeError = TypeError;

module.exports = function (O, P) {
  if (!delete O[P]) throw new $TypeError('Cannot delete property ' + tryToString(P) + ' of ' + tryToString(O));
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/does-not-exceed-safe-integer.js":
/***/ ((module) => {

"use strict";

var $TypeError = TypeError;
var MAX_SAFE_INTEGER = 0x1FFFFFFFFFFFFF; // 2 ** 53 - 1 == 9007199254740991

module.exports = function (it) {
  if (it > MAX_SAFE_INTEGER) throw $TypeError('Maximum allowed index exceeded');
  return it;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-ff-version.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var userAgent = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-user-agent.js");

var firefox = userAgent.match(/firefox\/(\d+)/i);

module.exports = !!firefox && +firefox[1];


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-is-ie-or-edge.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var UA = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-user-agent.js");

module.exports = /MSIE|Trident/.test(UA);


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-webkit-version.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var userAgent = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-user-agent.js");

var webkit = userAgent.match(/AppleWebKit\/(\d+)\./);

module.exports = !!webkit && +webkit[1];


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/regexp-get-flags.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var call = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-call.js");
var hasOwn = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/has-own-property.js");
var isPrototypeOf = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-is-prototype-of.js");
var regExpFlags = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/regexp-flags.js");

var RegExpPrototype = RegExp.prototype;

module.exports = function (R) {
  var flags = R.flags;
  return flags === undefined && !('flags' in RegExpPrototype) && !hasOwn(R, 'flags') && isPrototypeOf(RegExpPrototype, R)
    ? call(regExpFlags, R) : flags;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var isArray = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-array.js");
var isObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-object.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var lengthOfArrayLike = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/length-of-array-like.js");
var doesNotExceedSafeInteger = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/does-not-exceed-safe-integer.js");
var createProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-property.js");
var arraySpeciesCreate = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-create.js");
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");
var wellKnownSymbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/well-known-symbol.js");
var V8_VERSION = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-v8-version.js");

var IS_CONCAT_SPREADABLE = wellKnownSymbol('isConcatSpreadable');

// We can't use this feature detection in V8 since it causes
// deoptimization and serious performance degradation
// https://github.com/zloirock/core-js/issues/679
var IS_CONCAT_SPREADABLE_SUPPORT = V8_VERSION >= 51 || !fails(function () {
  var array = [];
  array[IS_CONCAT_SPREADABLE] = false;
  return array.concat()[0] !== array;
});

var isConcatSpreadable = function (O) {
  if (!isObject(O)) return false;
  var spreadable = O[IS_CONCAT_SPREADABLE];
  return spreadable !== undefined ? !!spreadable : isArray(O);
};

var FORCED = !IS_CONCAT_SPREADABLE_SUPPORT || !arrayMethodHasSpeciesSupport('concat');

// `Array.prototype.concat` method
// https://tc39.es/ecma262/#sec-array.prototype.concat
// with adding support of @@isConcatSpreadable and @@species
$({ target: 'Array', proto: true, arity: 1, forced: FORCED }, {
  // eslint-disable-next-line no-unused-vars -- required for `.length`
  concat: function concat(arg) {
    var O = toObject(this);
    var A = arraySpeciesCreate(O, 0);
    var n = 0;
    var i, k, length, len, E;
    for (i = -1, length = arguments.length; i < length; i++) {
      E = i === -1 ? O : arguments[i];
      if (isConcatSpreadable(E)) {
        len = lengthOfArrayLike(E);
        doesNotExceedSafeInteger(n + len);
        for (k = 0; k < len; k++, n++) if (k in E) createProperty(A, n, E[k]);
      } else {
        doesNotExceedSafeInteger(n + 1);
        createProperty(A, n++, E);
      }
    }
    A.length = n;
    return A;
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $filter = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").filter);
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');

// `Array.prototype.filter` method
// https://tc39.es/ecma262/#sec-array.prototype.filter
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  filter: function filter(callbackfn /* , thisArg */) {
    return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $findIndex = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").findIndex);
var addToUnscopables = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/add-to-unscopables.js");

var FIND_INDEX = 'findIndex';
var SKIPS_HOLES = true;

// Shouldn't skip holes
// eslint-disable-next-line es/no-array-prototype-findindex -- testing
if (FIND_INDEX in []) Array(1)[FIND_INDEX](function () { SKIPS_HOLES = false; });

// `Array.prototype.findIndex` method
// https://tc39.es/ecma262/#sec-array.prototype.findindex
$({ target: 'Array', proto: true, forced: SKIPS_HOLES }, {
  findIndex: function findIndex(callbackfn /* , that = undefined */) {
    return $findIndex(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});

// https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
addToUnscopables(FIND_INDEX);


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");

// `Array.prototype.forEach` method
// https://tc39.es/ecma262/#sec-array.prototype.foreach
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
$({ target: 'Array', proto: true, forced: [].forEach !== forEach }, {
  forEach: forEach
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $map = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").map);
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('map');

// `Array.prototype.map` method
// https://tc39.es/ecma262/#sec-array.prototype.map
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  map: function map(callbackfn /* , thisArg */) {
    return $map(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $reduce = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-reduce.js").left);
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");
var CHROME_VERSION = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-v8-version.js");
var IS_NODE = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-is-node.js");

// Chrome 80-82 has a critical bug
// https://bugs.chromium.org/p/chromium/issues/detail?id=1049982
var CHROME_BUG = !IS_NODE && CHROME_VERSION > 79 && CHROME_VERSION < 83;
var FORCED = CHROME_BUG || !arrayMethodIsStrict('reduce');

// `Array.prototype.reduce` method
// https://tc39.es/ecma262/#sec-array.prototype.reduce
$({ target: 'Array', proto: true, forced: FORCED }, {
  reduce: function reduce(callbackfn /* , initialValue */) {
    var length = arguments.length;
    return $reduce(this, callbackfn, length, length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var aCallable = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/a-callable.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var lengthOfArrayLike = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/length-of-array-like.js");
var deletePropertyOrThrow = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/delete-property-or-throw.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var internalSort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-sort.js");
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");
var FF = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-ff-version.js");
var IE_OR_EDGE = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-is-ie-or-edge.js");
var V8 = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-v8-version.js");
var WEBKIT = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-webkit-version.js");

var test = [];
var nativeSort = uncurryThis(test.sort);
var push = uncurryThis(test.push);

// IE8-
var FAILS_ON_UNDEFINED = fails(function () {
  test.sort(undefined);
});
// V8 bug
var FAILS_ON_NULL = fails(function () {
  test.sort(null);
});
// Old WebKit
var STRICT_METHOD = arrayMethodIsStrict('sort');

var STABLE_SORT = !fails(function () {
  // feature detection can be too slow, so check engines versions
  if (V8) return V8 < 70;
  if (FF && FF > 3) return;
  if (IE_OR_EDGE) return true;
  if (WEBKIT) return WEBKIT < 603;

  var result = '';
  var code, chr, value, index;

  // generate an array with more 512 elements (Chakra and old V8 fails only in this case)
  for (code = 65; code < 76; code++) {
    chr = String.fromCharCode(code);

    switch (code) {
      case 66: case 69: case 70: case 72: value = 3; break;
      case 68: case 71: value = 4; break;
      default: value = 2;
    }

    for (index = 0; index < 47; index++) {
      test.push({ k: chr + index, v: value });
    }
  }

  test.sort(function (a, b) { return b.v - a.v; });

  for (index = 0; index < test.length; index++) {
    chr = test[index].k.charAt(0);
    if (result.charAt(result.length - 1) !== chr) result += chr;
  }

  return result !== 'DGBEFHACIJK';
});

var FORCED = FAILS_ON_UNDEFINED || !FAILS_ON_NULL || !STRICT_METHOD || !STABLE_SORT;

var getSortCompare = function (comparefn) {
  return function (x, y) {
    if (y === undefined) return -1;
    if (x === undefined) return 1;
    if (comparefn !== undefined) return +comparefn(x, y) || 0;
    return toString(x) > toString(y) ? 1 : -1;
  };
};

// `Array.prototype.sort` method
// https://tc39.es/ecma262/#sec-array.prototype.sort
$({ target: 'Array', proto: true, forced: FORCED }, {
  sort: function sort(comparefn) {
    if (comparefn !== undefined) aCallable(comparefn);

    var array = toObject(this);

    if (STABLE_SORT) return comparefn === undefined ? nativeSort(array) : nativeSort(array, comparefn);

    var items = [];
    var arrayLength = lengthOfArrayLike(array);
    var itemsLength, index;

    for (index = 0; index < arrayLength; index++) {
      if (index in array) push(items, array[index]);
    }

    internalSort(items, getSortCompare(comparefn));

    itemsLength = lengthOfArrayLike(items);
    index = 0;

    while (index < itemsLength) array[index] = items[index++];
    while (index < arrayLength) deletePropertyOrThrow(array, index++);

    return array;
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

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

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperties = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-properties.js").f);

// `Object.defineProperties` method
// https://tc39.es/ecma262/#sec-object.defineproperties
// eslint-disable-next-line es/no-object-defineproperties -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperties !== defineProperties, sham: !DESCRIPTORS }, {
  defineProperties: defineProperties
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperty = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-property.js").f);

// `Object.defineProperty` method
// https://tc39.es/ecma262/#sec-object.defineproperty
// eslint-disable-next-line es/no-object-defineproperty -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperty !== defineProperty, sham: !DESCRIPTORS }, {
  defineProperty: defineProperty
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var nativeGetOwnPropertyDescriptor = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js").f);
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");

var FORCED = !DESCRIPTORS || fails(function () { nativeGetOwnPropertyDescriptor(1); });

// `Object.getOwnPropertyDescriptor` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptor
$({ target: 'Object', stat: true, forced: FORCED, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptor: function getOwnPropertyDescriptor(it, key) {
    return nativeGetOwnPropertyDescriptor(toIndexedObject(it), key);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var ownKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/own-keys.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var getOwnPropertyDescriptorModule = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js");
var createProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-property.js");

// `Object.getOwnPropertyDescriptors` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptors
$({ target: 'Object', stat: true, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptors: function getOwnPropertyDescriptors(object) {
    var O = toIndexedObject(object);
    var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
    var keys = ownKeys(O);
    var result = {};
    var index = 0;
    var key, descriptor;
    while (keys.length > index) {
      descriptor = getOwnPropertyDescriptor(O, key = keys[index++]);
      if (descriptor !== undefined) createProperty(result, key, descriptor);
    }
    return result;
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var nativeKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-keys.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");

var FAILS_ON_PRIMITIVES = fails(function () { nativeKeys(1); });

// `Object.keys` method
// https://tc39.es/ecma262/#sec-object.keys
$({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES }, {
  keys: function keys(it) {
    return nativeKeys(toObject(it));
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var PROPER_FUNCTION_NAME = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-name.js").PROPER);
var defineBuiltIn = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/define-built-in.js");
var anObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/an-object.js");
var $toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var getRegExpFlags = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/regexp-get-flags.js");

var TO_STRING = 'toString';
var RegExpPrototype = RegExp.prototype;
var nativeToString = RegExpPrototype[TO_STRING];

var NOT_GENERIC = fails(function () { return nativeToString.call({ source: 'a', flags: 'b' }) !== '/a/b'; });
// FF44- RegExp#toString has a wrong name
var INCORRECT_NAME = PROPER_FUNCTION_NAME && nativeToString.name !== TO_STRING;

// `RegExp.prototype.toString` method
// https://tc39.es/ecma262/#sec-regexp.prototype.tostring
if (NOT_GENERIC || INCORRECT_NAME) {
  defineBuiltIn(RegExp.prototype, TO_STRING, function toString() {
    var R = anObject(this);
    var pattern = $toString(R.source);
    var flags = $toString(getRegExpFlags(R));
    return '/' + pattern + '/' + flags;
  }, { unsafe: true });
}


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var DOMIterables = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-iterables.js");
var DOMTokenListPrototype = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-token-list-prototype.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");
var createNonEnumerableProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-non-enumerable-property.js");

var handlePrototype = function (CollectionPrototype) {
  // some Chrome versions have non-configurable methods on DOMTokenList
  if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
    createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
  } catch (error) {
    CollectionPrototype.forEach = forEach;
  }
};

for (var COLLECTION_NAME in DOMIterables) {
  if (DOMIterables[COLLECTION_NAME]) {
    handlePrototype(global[COLLECTION_NAME] && global[COLLECTION_NAME].prototype);
  }
}

handlePrototype(DOMTokenListPrototype);


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

/***/ }),

/***/ "../../packages/js/components/src/timeline/stories/timeline.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Empty: () => (/* binding */ Empty),
  Filled: () => (/* binding */ Filled),
  "default": () => (/* binding */ timeline_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/index.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+date@4.6.1/node_modules/@wordpress/date/build-module/index.js
var date_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+date@4.6.1/node_modules/@wordpress/date/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/timeline/timeline-item.js


/**
 * External dependencies
 */




var TimelineItem = function TimelineItem(_ref) {
  var _ref$item = _ref.item,
    item = _ref$item === void 0 ? {} : _ref$item,
    _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    clockFormat = _ref.clockFormat;
  var itemClassName = classnames_default()('woocommerce-timeline-item', className);
  var itemTimeString = (0,date_build_module/* format */.GP)(clockFormat, item.date);
  return (0,react.createElement)("li", {
    className: itemClassName
  }, (0,react.createElement)("div", {
    className: 'woocommerce-timeline-item__top-border'
  }), (0,react.createElement)("div", {
    className: 'woocommerce-timeline-item__title'
  }, (0,react.createElement)("div", {
    className: 'woocommerce-timeline-item__headline'
  }, item.icon, (0,react.createElement)("span", null, item.headline)), (0,react.createElement)("span", {
    className: 'woocommerce-timeline-item__timestamp'
  }, item.hideTimestamp || false ? null : itemTimeString)), (0,react.createElement)("div", {
    className: 'woocommerce-timeline-item__body'
  }, (item.body || []).map(function (bodyItem, index) {
    return (0,react.createElement)("span", {
      key: "timeline-item-body-".concat(index)
    }, bodyItem);
  })));
};
TimelineItem.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * An array of list items.
   */
  item: prop_types_default().shape({
    /**
     * Date for the timeline item.
     */
    date: prop_types_default().instanceOf(Date).isRequired,
    /**
     * Icon for the Timeline item.
     */
    icon: (prop_types_default()).element.isRequired,
    /**
     * Headline displayed for the list item.
     */
    headline: prop_types_default().oneOfType([(prop_types_default()).element, (prop_types_default()).string]).isRequired,
    /**
     * Body displayed for the list item.
     */
    body: prop_types_default().arrayOf(prop_types_default().oneOfType([(prop_types_default()).element, (prop_types_default()).string])),
    /**
     * Allows users to toggle the timestamp on or off.
     */
    hideTimestamp: (prop_types_default()).bool,
    /**
     * The PHP clock format string used to format times, see php.net/date.
     */
    clockFormat: (prop_types_default()).string
  })
};
/* harmony default export */ const timeline_item = (TimelineItem);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js
var es_array_find_index = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
;// CONCATENATED MODULE: ../../packages/js/components/src/timeline/util.js



/**
 * External dependencies
 */

var orderByOptions = {
  ASC: 'asc',
  DESC: 'desc'
};
var groupByOptions = {
  DAY: 'day',
  WEEK: 'week',
  MONTH: 'month'
};
var sortAscending = function sortAscending(groupA, groupB) {
  return groupA.date.getTime() - groupB.date.getTime();
};
var sortDescending = function sortDescending(groupA, groupB) {
  return groupB.date.getTime() - groupA.date.getTime();
};
var sortByDateUsing = function sortByDateUsing(orderBy) {
  switch (orderBy) {
    case orderByOptions.ASC:
      return sortAscending;
    case orderByOptions.DESC:
    default:
      return sortDescending;
  }
};
var groupItemsUsing = function groupItemsUsing(groupBy) {
  return function (groups, newItem) {
    // Helper functions defined to make the logic a bit more readable.
    var hasSameMoment = function hasSameMoment(group, item) {
      return moment_default()(group.date).isSame(moment_default()(item.date), groupBy);
    };
    var groupIndexExists = function groupIndexExists(index) {
      return index >= 0;
    };
    var groupForItem = groups.findIndex(function (group) {
      return hasSameMoment(group, newItem);
    });
    if (!groupIndexExists(groupForItem)) {
      // Create new group for newItem.
      return [].concat((0,toConsumableArray/* default */.A)(groups), [{
        date: newItem.date,
        items: [newItem]
      }]);
    }
    groups[groupForItem].items.push(newItem);
    return groups;
  };
};

;// CONCATENATED MODULE: ../../packages/js/components/src/timeline/timeline-group.js



/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


var TimelineGroup = function TimelineGroup(_ref) {
  var _ref$group = _ref.group,
    group = _ref$group === void 0 ? {
      title: '',
      items: []
    } : _ref$group,
    _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    _ref$orderBy = _ref.orderBy,
    orderBy = _ref$orderBy === void 0 ? 'desc' : _ref$orderBy,
    clockFormat = _ref.clockFormat;
  var groupClassName = classnames_default()('woocommerce-timeline-group', className);
  var itemsToTimlineItem = function itemsToTimlineItem(item, itemIndex) {
    var itemKey = group.title + '-' + itemIndex;
    return (0,react.createElement)(timeline_item, {
      key: itemKey,
      item: item,
      clockFormat: clockFormat
    });
  };
  return (0,react.createElement)("li", {
    className: groupClassName
  }, (0,react.createElement)("p", {
    className: 'woocommerce-timeline-group__title'
  }, group.title), (0,react.createElement)("ul", null, group.items.sort(sortByDateUsing(orderBy)).map(itemsToTimlineItem)), (0,react.createElement)("hr", null));
};
TimelineGroup.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * The group to render.
   */
  group: prop_types_default().shape({
    /**
     * The group title.
     */
    title: (prop_types_default()).string,
    /**
     * An array of list items.
     */
    items: prop_types_default().arrayOf(prop_types_default().shape({
      /**
       * Date for the timeline item.
       */
      date: prop_types_default().instanceOf(Date).isRequired,
      /**
       * Icon for the Timeline item.
       */
      icon: (prop_types_default()).element.isRequired,
      /**
       * Headline displayed for the list item.
       */
      headline: prop_types_default().oneOfType([(prop_types_default()).element, (prop_types_default()).string]).isRequired,
      /**
       * Body displayed for the list item.
       */
      body: prop_types_default().arrayOf(prop_types_default().oneOfType([(prop_types_default()).element, (prop_types_default()).string])),
      /**
       * Allows users to toggle the timestamp on or off.
       */
      hideTimestamp: (prop_types_default()).bool
    }))
  }),
  /**
   * Defines how items should be ordered.
   */
  orderBy: prop_types_default().oneOf(['asc', 'desc']),
  /**
   * The PHP clock format string used to format times, see php.net/date.
   */
  clockFormat: (prop_types_default()).string
};
/* harmony default export */ const timeline_group = (TimelineGroup);
;// CONCATENATED MODULE: ../../packages/js/components/src/timeline/index.js
















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


var Timeline = function Timeline(_ref) {
  var _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    _ref$items = _ref.items,
    items = _ref$items === void 0 ? [] : _ref$items,
    _ref$groupBy = _ref.groupBy,
    groupBy = _ref$groupBy === void 0 ? 'day' : _ref$groupBy,
    _ref$orderBy = _ref.orderBy,
    orderBy = _ref$orderBy === void 0 ? 'desc' : _ref$orderBy,
    _ref$dateFormat = _ref.dateFormat,
    dateFormat = _ref$dateFormat === void 0 ? (0,build_module.__)('F j, Y', 'woocommerce') : _ref$dateFormat,
    _ref$clockFormat = _ref.clockFormat,
    clockFormat = _ref$clockFormat === void 0 ? (0,build_module.__)('g:ia', 'woocommerce') : _ref$clockFormat;
  var timelineClassName = classnames_default()('woocommerce-timeline', className);

  // Early return in case no data was passed to the component.
  if (!items || items.length === 0) {
    return (0,react.createElement)("div", {
      className: timelineClassName
    }, (0,react.createElement)("p", {
      className: 'timeline_no_events'
    }, (0,build_module.__)('No data to display', 'woocommerce')));
  }
  var addGroupTitles = function addGroupTitles(group) {
    return _objectSpread(_objectSpread({}, group), {}, {
      title: (0,date_build_module/* format */.GP)(dateFormat, group.date)
    });
  };
  return (0,react.createElement)("div", {
    className: timelineClassName
  }, (0,react.createElement)("ul", null, items.reduce(groupItemsUsing(groupBy), []).map(addGroupTitles).sort(sortByDateUsing(orderBy)).map(function (group) {
    return (0,react.createElement)(timeline_group, {
      key: group.date.getTime().toString(),
      group: group,
      orderBy: orderBy,
      clockFormat: clockFormat
    });
  })));
};
Timeline.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * An array of list items.
   */
  items: prop_types_default().arrayOf(prop_types_default().shape({
    /**
     * Date for the timeline item.
     */
    date: prop_types_default().instanceOf(Date).isRequired,
    /**
     * Icon for the Timeline item.
     */
    icon: (prop_types_default()).element.isRequired,
    /**
     * Headline displayed for the list item.
     */
    headline: prop_types_default().oneOfType([(prop_types_default()).element, (prop_types_default()).string]).isRequired,
    /**
     * Body displayed for the list item.
     */
    body: prop_types_default().arrayOf(prop_types_default().oneOfType([(prop_types_default()).element, (prop_types_default()).string])),
    /**
     * Allows users to toggle the timestamp on or off.
     */
    hideTimestamp: (prop_types_default()).bool
  })),
  /**
   * Defines how items should be grouped together.
   */
  groupBy: prop_types_default().oneOf(['day', 'week', 'month']),
  /**
   * Defines how groups should be ordered.
   */
  orderBy: prop_types_default().oneOf(['asc', 'desc']),
  /**
   * The PHP date format string used to format dates, see php.net/date.
   */
  dateFormat: (prop_types_default()).string,
  /**
   * The PHP clock format string used to format times, see php.net/date.
   */
  clockFormat: (prop_types_default()).string
};

/* harmony default export */ const timeline = (Timeline);
;// CONCATENATED MODULE: ../../packages/js/components/src/timeline/stories/timeline.story.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var Filled = function Filled() {
  return (0,react.createElement)(timeline, {
    orderBy: orderByOptions.DESC,
    items: [{
      date: new Date(2020, 0, 20, 1, 30),
      body: [(0,react.createElement)("p", {
        key: "1"
      }, "p element in body"), 'string in body'],
      headline: (0,react.createElement)("p", null, "p tag in headline"),
      icon: (0,react.createElement)(dist/* default */.A, {
        className: "is-success",
        icon: "checkmark",
        size: 16
      }),
      hideTimestamp: true
    }, {
      date: new Date(2020, 0, 20, 23, 45),
      body: [],
      headline: (0,react.createElement)("span", null, "span in headline"),
      icon: (0,react.createElement)(dist/* default */.A, {
        className: 'is-warning',
        icon: "refresh",
        size: 16
      })
    }, {
      date: new Date(2020, 0, 22, 15, 13),
      body: [(0,react.createElement)("span", {
        key: "1"
      }, "span in body")],
      headline: 'string in headline',
      icon: (0,react.createElement)(dist/* default */.A, {
        className: 'is-error',
        icon: "cross",
        size: 16
      })
    }, {
      date: new Date(2020, 0, 17, 1, 45),
      headline: 'undefined body and string headline',
      icon: (0,react.createElement)(dist/* default */.A, {
        icon: "cross",
        size: 16
      })
    }]
  });
};
/* harmony default export */ const timeline_story = ({
  title: 'WooCommerce Admin/components/Timeline',
  component: Filled
});
var Empty = function Empty() {
  return (0,react.createElement)(timeline, null);
};
Filled.parameters = {
  ...Filled.parameters,
  docs: {
    ...Filled.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <Timeline orderBy={orderByOptions.DESC} items={[{\n    date: new Date(2020, 0, 20, 1, 30),\n    body: [<p key=\"1\">p element in body</p>, 'string in body'],\n    headline: <p>p tag in headline</p>,\n    icon: <GridIcon className=\"is-success\" icon=\"checkmark\" size={16} />,\n    hideTimestamp: true\n  }, {\n    date: new Date(2020, 0, 20, 23, 45),\n    body: [],\n    headline: <span>span in headline</span>,\n    icon: <GridIcon className={'is-warning'} icon=\"refresh\" size={16} />\n  }, {\n    date: new Date(2020, 0, 22, 15, 13),\n    body: [<span key=\"1\">span in body</span>],\n    headline: 'string in headline',\n    icon: <GridIcon className={'is-error'} icon=\"cross\" size={16} />\n  }, {\n    date: new Date(2020, 0, 17, 1, 45),\n    headline: 'undefined body and string headline',\n    icon: <GridIcon icon=\"cross\" size={16} />\n  }]} />;\n}",
      ...Filled.parameters?.docs?.source
    }
  }
};
Empty.parameters = {
  ...Empty.parameters,
  docs: {
    ...Empty.parameters?.docs,
    source: {
      originalSource: "() => <Timeline />",
      ...Empty.parameters?.docs?.source
    }
  }
};

/***/ })

}]);