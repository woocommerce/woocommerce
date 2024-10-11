(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[8620],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ date_time)
});

// UNUSED EXPORTS: DatePicker, TimePicker

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/initialize.js
var initialize = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/initialize.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/date.js + 1 modules
var date = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/date.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button-group/index.js



/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



function ButtonGroup(_ref, ref) {
  let {
    className,
    ...props
  } = _ref;
  const classes = classnames_default()('components-button-group', className);
  return (0,react.createElement)("div", (0,esm_extends/* default */.A)({
    ref: ref,
    role: "group",
    className: classes
  }, props));
}

/* harmony default export */ const button_group = ((0,react.forwardRef)(ButtonGroup));
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment-timezone@0.5.43/node_modules/moment-timezone/moment-timezone.js
var moment_timezone = __webpack_require__("../../node_modules/.pnpm/moment-timezone@0.5.43/node_modules/moment-timezone/moment-timezone.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment-timezone@0.5.43/node_modules/moment-timezone/moment-timezone-utils.js
var moment_timezone_utils = __webpack_require__("../../node_modules/.pnpm/moment-timezone@0.5.43/node_modules/moment-timezone/moment-timezone-utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.47.0/node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.47.0/node_modules/@wordpress/deprecated/build-module/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+date@4.44.0/node_modules/@wordpress/date/build-module/index.js
/**
 * External dependencies
 */




/**
 * WordPress dependencies
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
 * @property {0|1|2|3|4|5|6}                              startOfWeek   Day that the week starts on.
 */
/* eslint-enable jsdoc/valid-types */

/**
 * @typedef DateSettings
 * @property {L10nSettings}   l10n     Localization settings.
 * @property {FormatsConfig}  formats  Date/time formats config.
 * @property {TimezoneConfig} timezone Timezone settings.
 */

const WP_ZONE = 'WP';

// This regular expression tests positive for UTC offsets as described in ISO 8601.
// See: https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC
const VALID_UTC_OFFSET = /^[+-][0-1][0-9](:?[0-9][0-9])?$/;

// Changes made here will likely need to be made in `lib/client-assets.php` as
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
    },
    startOfWeek: 0
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
  setupWPTimezone();

  // Does moment already have a locale with the right name?
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
  }

  // defineLocale() will modify the current locale, so back it up.
  const currentLocale = momentLib.locale();

  // Create locale.
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
  });

  // Restore the locale to what it was.
  momentLib.locale(currentLocale);
}

/**
 * Returns the currently defined date settings.
 *
 * @return {DateSettings} Settings, including locale data.
 */
function getSettings() {
  return settings;
}

/**
 * Returns the currently defined date settings.
 *
 * @deprecated
 * @return {DateSettings} Settings, including locale data.
 */
function __experimentalGetSettings() {
  (0,deprecated_build_module/* default */.A)('wp.date.__experimentalGetSettings', {
    since: '6.1',
    alternative: 'wp.date.getSettings'
  });
  return getSettings();
}
function setupWPTimezone() {
  // Get the current timezone settings from the WP timezone string.
  const currentTimezone = moment_default().tz.zone(settings.timezone.string);

  // Check to see if we have a valid TZ data, if so, use it for the custom WP_ZONE timezone, otherwise just use the offset.
  if (currentTimezone) {
    // Create WP timezone based off settings.timezone.string.  We need to include the additional data so that we
    // don't lose information about daylight savings time and other items.
    // See https://github.com/WordPress/gutenberg/pull/48083
    moment_default().tz.add(moment_default().tz.pack({
      name: WP_ZONE,
      abbrs: currentTimezone.abbrs,
      untils: currentTimezone.untils,
      offsets: currentTimezone.offsets
    }));
  } else {
    // Create WP timezone based off dateSettings.
    moment_default().tz.add(moment_default().tz.pack({
      name: WP_ZONE,
      abbrs: [WP_ZONE],
      untils: [null],
      offsets: [-settings.timezone.offset * 60 || 0]
    }));
  }
}

// Date constants.
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
    const timezoned = moment_default()(momentDate).utcOffset(60);
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
function format(dateFormat, dateValue = new Date()) {
  let i, char;
  const newFormat = [];
  const momentDate = momentLib(dateValue);
  for (i = 0; i < dateFormat.length; i++) {
    char = dateFormat[i];
    // Is this an escape?
    if ('\\' === char) {
      // Add next character, then move on.
      i++;
      newFormat.push('[' + dateFormat[i] + ']');
      continue;
    }
    if (char in formatMap) {
      const formatter = formatMap[/** @type {keyof formatMap} */char];
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
  }
  // Join with [] between to separate characters, and replace
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
 * @param {string | number | undefined}        timezone   Timezone to output result in or a
 *                                                        UTC offset. Defaults to timezone from
 *                                                        site.
 *
 * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
 * @see https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC
 *
 * @return {string} Formatted date in English.
 */
function build_module_date(dateFormat, dateValue = new Date(), timezone) {
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
function gmdate(dateFormat, dateValue = new Date()) {
  const dateMoment = momentLib(dateValue).utc();
  return format(dateFormat, dateMoment);
}

/**
 * Formats a date (like `wp_date()` in PHP), translating it into site's locale.
 *
 * Backward Compatibility Notice: if `timezone` is set to `true`, the function
 * behaves like `gmdateI18n`.
 *
 * @param {string}                                dateFormat PHP-style formatting string.
 *                                                           See php.net/date.
 * @param {Moment | Date | string | undefined}    dateValue  Date object or string, parsable by
 *                                                           moment.js.
 * @param {string | number | boolean | undefined} timezone   Timezone to output result in or a
 *                                                           UTC offset. Defaults to timezone from
 *                                                           site. Notice: `boolean` is effectively
 *                                                           deprecated, but still supported for
 *                                                           backward compatibility reasons.
 *
 * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
 * @see https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC
 *
 * @return {string} Formatted date.
 */
function dateI18n(dateFormat, dateValue = new Date(), timezone) {
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
function gmdateI18n(dateFormat, dateValue = new Date()) {
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
 * Returns a human-readable time difference between two dates, like human_time_diff() in PHP.
 *
 * @param {Moment | Date | string}             from From date, in the WP timezone.
 * @param {Moment | Date | string | undefined} to   To date, formatted in the WP timezone.
 *
 * @return {string} Human-readable time difference.
 */
function humanTimeDiff(from, to) {
  const fromMoment = momentLib.tz(from, WP_ZONE);
  const toMoment = to ? momentLib.tz(to, WP_ZONE) : momentLib.tz(WP_ZONE);
  return fromMoment.from(toMoment);
}

/**
 * Creates a moment instance using the given timezone or, if none is provided, using global settings.
 *
 * @param {Moment | Date | string | undefined} dateValue Date object or string, parsable
 *                                                       by moment.js.
 * @param {string | number | undefined}        timezone  Timezone to output result in or a
 *                                                       UTC offset. Defaults to timezone from
 *                                                       site.
 *
 * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
 * @see https://en.wikipedia.org/wiki/ISO_8601#Time_offsets_from_UTC
 *
 * @return {Moment} a moment instance.
 */
function buildMoment(dateValue, timezone = '') {
  const dateMoment = momentLib(dateValue);
  if (timezone && !isUTCOffset(timezone)) {
    // The ! isUTCOffset() check guarantees that timezone is a string.
    return dateMoment.tz( /** @type {string} */timezone);
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js + 1 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/timezone.js


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Displays timezone information when user timezone is different from site timezone.
 */

const TimeZone = () => {
  const {
    timezone
  } = __experimentalGetSettings(); // Convert timezone offset to hours.

  const userTimezoneOffset = -1 * (new Date().getTimezoneOffset() / 60); // System timezone and user timezone match, nothing needed.
  // Compare as numbers because it comes over as string.

  if (Number(timezone.offset) === userTimezoneOffset) {
    return null;
  }

  const offsetSymbol = timezone.offset >= 0 ? '+' : '';
  const zoneAbbr = '' !== timezone.abbr && isNaN(timezone.abbr) ? timezone.abbr : `UTC${offsetSymbol}${timezone.offset}`;
  const timezoneDetail = 'UTC' === timezone.string ? (0,build_module.__)('Coordinated Universal Time') : `(${zoneAbbr}) ${timezone.string.replace('_', ' ')}`;
  return (0,react.createElement)(tooltip/* default */.A, {
    position: "top center",
    text: timezoneDetail
  }, (0,react.createElement)("div", {
    className: "components-datetime__timezone"
  }, zoneAbbr));
};

/* harmony default export */ const timezone = (TimeZone);
//# sourceMappingURL=timezone.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/time.js


/**
 * External dependencies
 */



/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */




/**
 * Module Constants
 */

const TIMEZONELESS_FORMAT = 'YYYY-MM-DDTHH:mm:ss';

function from12hTo24h(hours, isPm) {
  return isPm ? (hours % 12 + 12) % 24 : hours % 12;
}
/**
 * <UpdateOnBlurAsIntegerField>
 * A shared component to parse, validate, and handle remounting of the underlying form field element like <input> and <select>.
 *
 * @param {Object}        props             Component props.
 * @param {string}        props.as          Render the component as specific element tag, defaults to "input".
 * @param {number|string} props.value       The default value of the component which will be parsed to integer.
 * @param {Function}      props.onUpdate    Call back when blurred and validated.
 * @param {string}        [props.className]
 */


function UpdateOnBlurAsIntegerField(_ref) {
  let {
    as,
    value,
    onUpdate,
    className,
    ...props
  } = _ref;

  function handleBlur(event) {
    const {
      target
    } = event;

    if (value === target.value) {
      return;
    }

    const parsedValue = parseInt(target.value, 10); // Run basic number validation on the input.

    if (!(0,lodash.isInteger)(parsedValue) || typeof props.max !== 'undefined' && parsedValue > props.max || typeof props.min !== 'undefined' && parsedValue < props.min) {
      // If validation failed, reset the value to the previous valid value.
      target.value = value;
    } else {
      // Otherwise, it's valid, call onUpdate.
      onUpdate(target.name, parsedValue);
    }
  }

  return (0,react.createElement)(as || 'input', {
    // Re-mount the input value to accept the latest value as the defaultValue.
    key: value,
    defaultValue: value,
    onBlur: handleBlur,
    className: classnames_default()('components-datetime__time-field-integer-field', className),
    ...props
  });
}
/**
 * <TimePicker>
 *
 * @typedef {Date|string|number} WPValidDateTimeFormat
 *
 * @param {Object}                props             Component props.
 * @param {boolean}               props.is12Hour    Should the time picker showed in 12 hour format or 24 hour format.
 * @param {WPValidDateTimeFormat} props.currentTime The initial current time the time picker should render.
 * @param {Function}              props.onChange    Callback function when the date changed.
 */


function TimePicker(_ref2) {
  let {
    is12Hour,
    currentTime,
    onChange
  } = _ref2;
  const [date, setDate] = (0,react.useState)(() => // Truncate the date at the minutes, see: #15495.
  moment_default()(currentTime).startOf('minutes')); // Reset the state when currentTime changed.

  (0,react.useEffect)(() => {
    setDate(currentTime ? moment_default()(currentTime).startOf('minutes') : moment_default()());
  }, [currentTime]);
  const {
    day,
    month,
    year,
    minutes,
    hours,
    am
  } = (0,react.useMemo)(() => ({
    day: date.format('DD'),
    month: date.format('MM'),
    year: date.format('YYYY'),
    minutes: date.format('mm'),
    hours: date.format(is12Hour ? 'hh' : 'HH'),
    am: date.format('H') <= 11 ? 'AM' : 'PM'
  }), [date, is12Hour]);
  /**
   * Function that sets the date state and calls the onChange with a new date.
   * The date is truncated at the minutes.
   *
   * @param {Object} newDate The date object.
   */

  function changeDate(newDate) {
    setDate(newDate);
    onChange(newDate.format(TIMEZONELESS_FORMAT));
  }

  function update(name, value) {
    // If the 12-hour format is being used and the 'PM' period is selected, then
    // the incoming value (which ranges 1-12) should be increased by 12 to match
    // the expected 24-hour format.
    let adjustedValue = value;

    if (name === 'hours' && is12Hour) {
      adjustedValue = from12hTo24h(value, am === 'PM');
    } // Clone the date and call the specific setter function according to `name`.


    const newDate = date.clone()[name](adjustedValue);
    changeDate(newDate);
  }

  function updateAmPm(value) {
    return () => {
      if (am === value) {
        return;
      }

      const parsedHours = parseInt(hours, 10);
      const newDate = date.clone().hours(from12hTo24h(parsedHours, value === 'PM'));
      changeDate(newDate);
    };
  }

  const dayFormat = (0,react.createElement)("div", {
    className: "components-datetime__time-field components-datetime__time-field-day"
  }, (0,react.createElement)(UpdateOnBlurAsIntegerField, {
    "aria-label": (0,build_module.__)('Day'),
    className: "components-datetime__time-field-day-input",
    type: "number" // The correct function to call in moment.js is "date" not "day".
    ,
    name: "date",
    value: day,
    step: 1,
    min: 1,
    max: 31,
    onUpdate: update
  }));
  const monthFormat = (0,react.createElement)("div", {
    className: "components-datetime__time-field components-datetime__time-field-month"
  }, (0,react.createElement)(UpdateOnBlurAsIntegerField, {
    as: "select",
    "aria-label": (0,build_module.__)('Month'),
    className: "components-datetime__time-field-month-select",
    name: "month",
    value: month // The value starts from 0, so we have to -1 when setting month.
    ,
    onUpdate: (key, value) => update(key, value - 1)
  }, (0,react.createElement)("option", {
    value: "01"
  }, (0,build_module.__)('January')), (0,react.createElement)("option", {
    value: "02"
  }, (0,build_module.__)('February')), (0,react.createElement)("option", {
    value: "03"
  }, (0,build_module.__)('March')), (0,react.createElement)("option", {
    value: "04"
  }, (0,build_module.__)('April')), (0,react.createElement)("option", {
    value: "05"
  }, (0,build_module.__)('May')), (0,react.createElement)("option", {
    value: "06"
  }, (0,build_module.__)('June')), (0,react.createElement)("option", {
    value: "07"
  }, (0,build_module.__)('July')), (0,react.createElement)("option", {
    value: "08"
  }, (0,build_module.__)('August')), (0,react.createElement)("option", {
    value: "09"
  }, (0,build_module.__)('September')), (0,react.createElement)("option", {
    value: "10"
  }, (0,build_module.__)('October')), (0,react.createElement)("option", {
    value: "11"
  }, (0,build_module.__)('November')), (0,react.createElement)("option", {
    value: "12"
  }, (0,build_module.__)('December'))));
  const dayMonthFormat = is12Hour ? (0,react.createElement)(react.Fragment, null, monthFormat, dayFormat) : (0,react.createElement)(react.Fragment, null, dayFormat, monthFormat);
  return (0,react.createElement)("div", {
    className: classnames_default()('components-datetime__time')
  }, (0,react.createElement)("fieldset", null, (0,react.createElement)("legend", {
    className: "components-datetime__time-legend invisible"
  }, (0,build_module.__)('Date')), (0,react.createElement)("div", {
    className: "components-datetime__time-wrapper"
  }, dayMonthFormat, (0,react.createElement)("div", {
    className: "components-datetime__time-field components-datetime__time-field-year"
  }, (0,react.createElement)(UpdateOnBlurAsIntegerField, {
    "aria-label": (0,build_module.__)('Year'),
    className: "components-datetime__time-field-year-input",
    type: "number",
    name: "year",
    step: 1,
    min: 0,
    max: 9999,
    value: year,
    onUpdate: update
  })))), (0,react.createElement)("fieldset", null, (0,react.createElement)("legend", {
    className: "components-datetime__time-legend invisible"
  }, (0,build_module.__)('Time')), (0,react.createElement)("div", {
    className: "components-datetime__time-wrapper"
  }, (0,react.createElement)("div", {
    className: "components-datetime__time-field components-datetime__time-field-time"
  }, (0,react.createElement)(UpdateOnBlurAsIntegerField, {
    "aria-label": (0,build_module.__)('Hours'),
    className: "components-datetime__time-field-hours-input",
    type: "number",
    name: "hours",
    step: 1,
    min: is12Hour ? 1 : 0,
    max: is12Hour ? 12 : 23,
    value: hours,
    onUpdate: update
  }), (0,react.createElement)("span", {
    className: "components-datetime__time-separator",
    "aria-hidden": "true"
  }, ":"), (0,react.createElement)(UpdateOnBlurAsIntegerField, {
    "aria-label": (0,build_module.__)('Minutes'),
    className: "components-datetime__time-field-minutes-input",
    type: "number",
    name: "minutes",
    step: 1,
    min: 0,
    max: 59,
    value: minutes,
    onUpdate: update
  })), is12Hour && (0,react.createElement)(button_group, {
    className: "components-datetime__time-field components-datetime__time-field-am-pm"
  }, (0,react.createElement)(build_module_button/* default */.A, {
    variant: am === 'AM' ? 'primary' : 'secondary',
    onClick: updateAmPm('AM'),
    className: "components-datetime__time-am-button"
  }, (0,build_module.__)('AM')), (0,react.createElement)(build_module_button/* default */.A, {
    variant: am === 'PM' ? 'primary' : 'secondary',
    onClick: updateAmPm('PM'),
    className: "components-datetime__time-pm-button"
  }, (0,build_module.__)('PM'))), (0,react.createElement)(timezone, null))));
}
/* harmony default export */ const time = (TimePicker);
//# sourceMappingURL=time.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/index.js


/**
 * External dependencies
 */
// Needed to initialise the default datepicker styles.
// See: https://github.com/airbnb/react-dates#initialize


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */






function DateTimePicker(_ref, ref) {
  let {
    currentDate,
    is12Hour,
    isInvalidDate,
    onMonthPreviewed = lodash.noop,
    onChange,
    events
  } = _ref;
  const [calendarHelpIsVisible, setCalendarHelpIsVisible] = (0,react.useState)(false);

  function onClickDescriptionToggle() {
    setCalendarHelpIsVisible(!calendarHelpIsVisible);
  }

  return (0,react.createElement)("div", {
    ref: ref,
    className: "components-datetime"
  }, !calendarHelpIsVisible && (0,react.createElement)(react.Fragment, null, (0,react.createElement)(time, {
    currentTime: currentDate,
    onChange: onChange,
    is12Hour: is12Hour
  }), (0,react.createElement)(date/* default */.A, {
    currentDate: currentDate,
    onChange: onChange,
    isInvalidDate: isInvalidDate,
    events: events,
    onMonthPreviewed: onMonthPreviewed
  })), calendarHelpIsVisible && (0,react.createElement)(react.Fragment, null, (0,react.createElement)("div", {
    className: "components-datetime__calendar-help"
  }, (0,react.createElement)("h4", null, (0,build_module.__)('Click to Select')), (0,react.createElement)("ul", null, (0,react.createElement)("li", null, (0,build_module.__)('Click the right or left arrows to select other months in the past or the future.')), (0,react.createElement)("li", null, (0,build_module.__)('Click the desired day to select it.'))), (0,react.createElement)("h4", null, (0,build_module.__)('Navigating with a keyboard')), (0,react.createElement)("ul", null, (0,react.createElement)("li", null, (0,react.createElement)("abbr", {
    "aria-label": (0,build_module._x)('Enter', 'keyboard button')
  }, "\u21B5"), ' '
  /* JSX removes whitespace, but a space is required for screen readers. */
  , (0,react.createElement)("span", null, (0,build_module.__)('Select the date in focus.'))), (0,react.createElement)("li", null, (0,react.createElement)("abbr", {
    "aria-label": (0,build_module.__)('Left and Right Arrows')
  }, "\u2190/\u2192"), ' '
  /* JSX removes whitespace, but a space is required for screen readers. */
  , (0,build_module.__)('Move backward (left) or forward (right) by one day.')), (0,react.createElement)("li", null, (0,react.createElement)("abbr", {
    "aria-label": (0,build_module.__)('Up and Down Arrows')
  }, "\u2191/\u2193"), ' '
  /* JSX removes whitespace, but a space is required for screen readers. */
  , (0,build_module.__)('Move backward (up) or forward (down) by one week.')), (0,react.createElement)("li", null, (0,react.createElement)("abbr", {
    "aria-label": (0,build_module.__)('Page Up and Page Down')
  }, (0,build_module.__)('PgUp/PgDn')), ' '
  /* JSX removes whitespace, but a space is required for screen readers. */
  , (0,build_module.__)('Move backward (PgUp) or forward (PgDn) by one month.')), (0,react.createElement)("li", null, (0,react.createElement)("abbr", {
    "aria-label": (0,build_module.__)('Home and End')
  }, (0,build_module.__)('Home/End')), ' '
  /* JSX removes whitespace, but a space is required for screen readers. */
  , (0,build_module.__)('Go to the first (Home) or last (End) day of a week.'))))), (0,react.createElement)("div", {
    className: "components-datetime__buttons"
  }, !calendarHelpIsVisible && currentDate && (0,react.createElement)(build_module_button/* default */.A, {
    className: "components-datetime__date-reset-button",
    variant: "link",
    onClick: () => onChange(null)
  }, (0,build_module.__)('Reset')), (0,react.createElement)(build_module_button/* default */.A, {
    className: "components-datetime__date-help-toggle",
    variant: "link",
    onClick: onClickDescriptionToggle
  }, calendarHelpIsVisible ? (0,build_module.__)('Close') : (0,build_module.__)('Calendar Help'))));
}

/* harmony default export */ const date_time = ((0,react.forwardRef)(DateTimePicker));
//# sourceMappingURL=index.js.map

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

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@3.47.0/node_modules/@wordpress/deprecated/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@3.57.0/node_modules/@wordpress/hooks/build-module/index.js");
/**
 * WordPress dependencies
 */


/**
 * Object map tracking messages which have been logged, for use in ensuring a
 * message is only logged once.
 *
 * @type {Record<string, true | undefined>}
 */
const logged = Object.create(null);

/**
 * Logs a message to notify developers about a deprecated feature.
 *
 * @param {string} feature               Name of the deprecated feature.
 * @param {Object} [options]             Personalisation options
 * @param {string} [options.since]       Version in which the feature was deprecated.
 * @param {string} [options.version]     Version in which the feature will be removed.
 * @param {string} [options.alternative] Feature to use instead
 * @param {string} [options.plugin]      Plugin name if it's a plugin feature
 * @param {string} [options.link]        Link to documentation
 * @param {string} [options.hint]        Additional message to help transition away from the deprecated feature.
 *
 * @example
 * ```js
 * import deprecated from '@wordpress/deprecated';
 *
 * deprecated( 'Eating meat', {
 * 	since: '2019.01.01'
 * 	version: '2020.01.01',
 * 	alternative: 'vegetables',
 * 	plugin: 'the earth',
 * 	hint: 'You may find it beneficial to transition gradually.',
 * } );
 *
 * // Logs: 'Eating meat is deprecated since version 2019.01.01 and will be removed from the earth in version 2020.01.01. Please use vegetables instead. Note: You may find it beneficial to transition gradually.'
 * ```
 */
function deprecated(feature, options = {}) {
  const {
    since,
    version,
    alternative,
    plugin,
    link,
    hint
  } = options;
  const pluginMessage = plugin ? ` from ${plugin}` : '';
  const sinceMessage = since ? ` since version ${since}` : '';
  const versionMessage = version ? ` and will be removed${pluginMessage} in version ${version}` : '';
  const useInsteadMessage = alternative ? ` Please use ${alternative} instead.` : '';
  const linkMessage = link ? ` See: ${link}` : '';
  const hintMessage = hint ? ` Note: ${hint}` : '';
  const message = `${feature} is deprecated${sinceMessage}${versionMessage}.${useInsteadMessage}${linkMessage}${hintMessage}`;

  // Skip if already logged.
  if (message in logged) {
    return;
  }

  /**
   * Fires whenever a deprecated feature is encountered
   *
   * @param {string}  feature             Name of the deprecated feature.
   * @param {?Object} options             Personalisation options
   * @param {string}  options.since       Version in which the feature was deprecated.
   * @param {?string} options.version     Version in which the feature will be removed.
   * @param {?string} options.alternative Feature to use instead
   * @param {?string} options.plugin      Plugin name if it's a plugin feature
   * @param {?string} options.link        Link to documentation
   * @param {?string} options.hint        Additional message to help transition away from the deprecated feature.
   * @param {?string} message             Message sent to console.warn
   */
  (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)('deprecated', feature, options, message);

  // eslint-disable-next-line no-console
  console.warn(message);
  logged[message] = true;
}

/** @typedef {import('utility-types').NonUndefined<Parameters<typeof deprecated>[1]>} DeprecatedOptions */
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/date-to-iso-string.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var padStart = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-pad.js").start);

var $RangeError = RangeError;
var $isFinite = isFinite;
var abs = Math.abs;
var DatePrototype = Date.prototype;
var nativeDateToISOString = DatePrototype.toISOString;
var thisTimeValue = uncurryThis(DatePrototype.getTime);
var getUTCDate = uncurryThis(DatePrototype.getUTCDate);
var getUTCFullYear = uncurryThis(DatePrototype.getUTCFullYear);
var getUTCHours = uncurryThis(DatePrototype.getUTCHours);
var getUTCMilliseconds = uncurryThis(DatePrototype.getUTCMilliseconds);
var getUTCMinutes = uncurryThis(DatePrototype.getUTCMinutes);
var getUTCMonth = uncurryThis(DatePrototype.getUTCMonth);
var getUTCSeconds = uncurryThis(DatePrototype.getUTCSeconds);

// `Date.prototype.toISOString` method implementation
// https://tc39.es/ecma262/#sec-date.prototype.toisostring
// PhantomJS / old WebKit fails here:
module.exports = (fails(function () {
  return nativeDateToISOString.call(new Date(-5e13 - 1)) !== '0385-07-25T07:06:39.999Z';
}) || !fails(function () {
  nativeDateToISOString.call(new Date(NaN));
})) ? function toISOString() {
  if (!$isFinite(thisTimeValue(this))) throw new $RangeError('Invalid time value');
  var date = this;
  var year = getUTCFullYear(date);
  var milliseconds = getUTCMilliseconds(date);
  var sign = year < 0 ? '-' : year > 9999 ? '+' : '';
  return sign + padStart(abs(year), sign ? 6 : 4, 0) +
    '-' + padStart(getUTCMonth(date) + 1, 2, 0) +
    '-' + padStart(getUTCDate(date), 2, 0) +
    'T' + padStart(getUTCHours(date), 2, 0) +
    ':' + padStart(getUTCMinutes(date), 2, 0) +
    ':' + padStart(getUTCSeconds(date), 2, 0) +
    '.' + padStart(milliseconds, 3, 0) +
    'Z';
} : nativeDateToISOString;


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fix-regexp-well-known-symbol-logic.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

// TODO: Remove from `core-js@4` since it's moved to entry points
__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this-clause.js");
var defineBuiltIn = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/define-built-in.js");
var regexpExec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/regexp-exec.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var wellKnownSymbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/well-known-symbol.js");
var createNonEnumerableProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-non-enumerable-property.js");

var SPECIES = wellKnownSymbol('species');
var RegExpPrototype = RegExp.prototype;

module.exports = function (KEY, exec, FORCED, SHAM) {
  var SYMBOL = wellKnownSymbol(KEY);

  var DELEGATES_TO_SYMBOL = !fails(function () {
    // String methods call symbol-named RegEp methods
    var O = {};
    O[SYMBOL] = function () { return 7; };
    return ''[KEY](O) !== 7;
  });

  var DELEGATES_TO_EXEC = DELEGATES_TO_SYMBOL && !fails(function () {
    // Symbol-named RegExp methods call .exec
    var execCalled = false;
    var re = /a/;

    if (KEY === 'split') {
      // We can't use real regex here since it causes deoptimization
      // and serious performance degradation in V8
      // https://github.com/zloirock/core-js/issues/306
      re = {};
      // RegExp[@@split] doesn't call the regex's exec method, but first creates
      // a new one. We need to return the patched regex when creating the new one.
      re.constructor = {};
      re.constructor[SPECIES] = function () { return re; };
      re.flags = '';
      re[SYMBOL] = /./[SYMBOL];
    }

    re.exec = function () {
      execCalled = true;
      return null;
    };

    re[SYMBOL]('');
    return !execCalled;
  });

  if (
    !DELEGATES_TO_SYMBOL ||
    !DELEGATES_TO_EXEC ||
    FORCED
  ) {
    var uncurriedNativeRegExpMethod = uncurryThis(/./[SYMBOL]);
    var methods = exec(SYMBOL, ''[KEY], function (nativeMethod, regexp, str, arg2, forceStringMethod) {
      var uncurriedNativeMethod = uncurryThis(nativeMethod);
      var $exec = regexp.exec;
      if ($exec === regexpExec || $exec === RegExpPrototype.exec) {
        if (DELEGATES_TO_SYMBOL && !forceStringMethod) {
          // The native String method already delegates to @@method (this
          // polyfilled function), leasing to infinite recursion.
          // We avoid it by directly calling the native @@method method.
          return { done: true, value: uncurriedNativeRegExpMethod(regexp, str, arg2) };
        }
        return { done: true, value: uncurriedNativeMethod(str, regexp, arg2) };
      }
      return { done: false };
    });

    defineBuiltIn(String.prototype, KEY, methods[0]);
    defineBuiltIn(RegExpPrototype, SYMBOL, methods[1]);
  }

  if (SHAM) createNonEnumerableProperty(RegExpPrototype[SYMBOL], 'sham', true);
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/number-parse-int.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var trim = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-trim.js").trim);
var whitespaces = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/whitespaces.js");

var $parseInt = global.parseInt;
var Symbol = global.Symbol;
var ITERATOR = Symbol && Symbol.iterator;
var hex = /^[+-]?0x/i;
var exec = uncurryThis(hex.exec);
var FORCED = $parseInt(whitespaces + '08') !== 8 || $parseInt(whitespaces + '0x16') !== 22
  // MS Edge 18- broken with boxed symbols
  || (ITERATOR && !fails(function () { $parseInt(Object(ITERATOR)); }));

// `parseInt` method
// https://tc39.es/ecma262/#sec-parseint-string-radix
module.exports = FORCED ? function parseInt(string, radix) {
  var S = trim(toString(string));
  return $parseInt(S, (radix >>> 0) || (exec(hex, S) ? 16 : 10));
} : $parseInt;


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/regexp-exec-abstract.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var call = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-call.js");
var anObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/an-object.js");
var isCallable = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-callable.js");
var classof = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/classof-raw.js");
var regexpExec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/regexp-exec.js");

var $TypeError = TypeError;

// `RegExpExec` abstract operation
// https://tc39.es/ecma262/#sec-regexpexec
module.exports = function (R, S) {
  var exec = R.exec;
  if (isCallable(exec)) {
    var result = call(exec, R, S);
    if (result !== null) anObject(result);
    return result;
  }
  if (classof(R) === 'RegExp') return call(regexpExec, R, S);
  throw new $TypeError('RegExp#exec called on incompatible receiver');
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-pad.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

// https://github.com/tc39/proposal-string-pad-start-end
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var toLength = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-length.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var $repeat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-repeat.js");
var requireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/require-object-coercible.js");

var repeat = uncurryThis($repeat);
var stringSlice = uncurryThis(''.slice);
var ceil = Math.ceil;

// `String.prototype.{ padStart, padEnd }` methods implementation
var createMethod = function (IS_END) {
  return function ($this, maxLength, fillString) {
    var S = toString(requireObjectCoercible($this));
    var intMaxLength = toLength(maxLength);
    var stringLength = S.length;
    var fillStr = fillString === undefined ? ' ' : toString(fillString);
    var fillLen, stringFiller;
    if (intMaxLength <= stringLength || fillStr === '') return S;
    fillLen = intMaxLength - stringLength;
    stringFiller = repeat(fillStr, ceil(fillLen / fillStr.length));
    if (stringFiller.length > fillLen) stringFiller = stringSlice(stringFiller, 0, fillLen);
    return IS_END ? S + stringFiller : stringFiller + S;
  };
};

module.exports = {
  // `String.prototype.padStart` method
  // https://tc39.es/ecma262/#sec-string.prototype.padstart
  start: createMethod(false),
  // `String.prototype.padEnd` method
  // https://tc39.es/ecma262/#sec-string.prototype.padend
  end: createMethod(true)
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-repeat.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var toIntegerOrInfinity = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-integer-or-infinity.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var requireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/require-object-coercible.js");

var $RangeError = RangeError;

// `String.prototype.repeat` method implementation
// https://tc39.es/ecma262/#sec-string.prototype.repeat
module.exports = function repeat(count) {
  var str = toString(requireObjectCoercible(this));
  var result = '';
  var n = toIntegerOrInfinity(count);
  if (n < 0 || n === Infinity) throw new $RangeError('Wrong number of repetitions');
  for (;n > 0; (n >>>= 1) && (str += str)) if (n & 1) result += str;
  return result;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-trim.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var requireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/require-object-coercible.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var whitespaces = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/whitespaces.js");

var replace = uncurryThis(''.replace);
var ltrim = RegExp('^[' + whitespaces + ']+');
var rtrim = RegExp('(^|[^' + whitespaces + '])[' + whitespaces + ']+$');

// `String.prototype.{ trim, trimStart, trimEnd, trimLeft, trimRight }` methods implementation
var createMethod = function (TYPE) {
  return function ($this) {
    var string = toString(requireObjectCoercible($this));
    if (TYPE & 1) string = replace(string, ltrim, '');
    if (TYPE & 2) string = replace(string, rtrim, '$1');
    return string;
  };
};

module.exports = {
  // `String.prototype.{ trimLeft, trimStart }` methods
  // https://tc39.es/ecma262/#sec-string.prototype.trimstart
  start: createMethod(1),
  // `String.prototype.{ trimRight, trimEnd }` methods
  // https://tc39.es/ecma262/#sec-string.prototype.trimend
  end: createMethod(2),
  // `String.prototype.trim` method
  // https://tc39.es/ecma262/#sec-string.prototype.trim
  trim: createMethod(3)
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/whitespaces.js":
/***/ ((module) => {

"use strict";

// a string of all valid unicode whitespaces
module.exports = '\u0009\u000A\u000B\u000C\u000D\u0020\u00A0\u1680\u2000\u2001\u2002' +
  '\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF';


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-iso-string.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var toISOString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/date-to-iso-string.js");

// `Date.prototype.toISOString` method
// https://tc39.es/ecma262/#sec-date.prototype.toisostring
// PhantomJS / old WebKit has a broken implementations
$({ target: 'Date', proto: true, forced: Date.prototype.toISOString !== toISOString }, {
  toISOString: toISOString
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $parseInt = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/number-parse-int.js");

// `parseInt` method
// https://tc39.es/ecma262/#sec-parseint-string-radix
$({ global: true, forced: parseInt !== $parseInt }, {
  parseInt: $parseInt
});


/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/initialize.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

// eslint-disable-next-line import/no-unresolved
__webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/initialize.js");


/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/initialize.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var _registerCSSInterfaceWithDefaultTheme = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/registerCSSInterfaceWithDefaultTheme.js");

var _registerCSSInterfaceWithDefaultTheme2 = _interopRequireDefault(_registerCSSInterfaceWithDefaultTheme);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

(0, _registerCSSInterfaceWithDefaultTheme2['default'])();

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/registerCSSInterfaceWithDefaultTheme.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";


Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = registerCSSInterfaceWithDefaultTheme;

var _reactWithStylesInterfaceCss = __webpack_require__("../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/index.js");

var _reactWithStylesInterfaceCss2 = _interopRequireDefault(_reactWithStylesInterfaceCss);

var _registerInterfaceWithDefaultTheme = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/registerInterfaceWithDefaultTheme.js");

var _registerInterfaceWithDefaultTheme2 = _interopRequireDefault(_registerInterfaceWithDefaultTheme);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function registerCSSInterfaceWithDefaultTheme() {
  (0, _registerInterfaceWithDefaultTheme2['default'])(_reactWithStylesInterfaceCss2['default']);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/registerInterfaceWithDefaultTheme.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";


Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = registerInterfaceWithDefaultTheme;

var _ThemedStyleSheet = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/ThemedStyleSheet.js");

var _ThemedStyleSheet2 = _interopRequireDefault(_ThemedStyleSheet);

var _DefaultTheme = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/theme/DefaultTheme.js");

var _DefaultTheme2 = _interopRequireDefault(_DefaultTheme);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function registerInterfaceWithDefaultTheme(reactWithStylesInterface) {
  _ThemedStyleSheet2['default'].registerInterface(reactWithStylesInterface);
  _ThemedStyleSheet2['default'].registerTheme(_DefaultTheme2['default']);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/index.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({
  value: true
});

var _arrayPrototype = __webpack_require__("../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/index.js");

var _arrayPrototype2 = _interopRequireDefault(_arrayPrototype);

var _globalCache = __webpack_require__("../../node_modules/.pnpm/global-cache@1.2.1/node_modules/global-cache/index.js");

var _globalCache2 = _interopRequireDefault(_globalCache);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/utils/constants.js");

var _getClassName = __webpack_require__("../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/utils/getClassName.js");

var _getClassName2 = _interopRequireDefault(_getClassName);

var _separateStyles2 = __webpack_require__("../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/utils/separateStyles.js");

var _separateStyles3 = _interopRequireDefault(_separateStyles2);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

/**
 * Function required as part of the react-with-styles interface. Parses the styles provided by
 * react-with-styles to produce class names based on the style name and optionally the namespace if
 * available.
 *
 * stylesObject {Object} The styles object passed to withStyles.
 *
 * Return an object mapping style names to class names.
 */
function create(stylesObject) {
  var stylesToClasses = {};
  var styleNames = Object.keys(stylesObject);
  var sharedState = _globalCache2['default'].get(_constants.GLOBAL_CACHE_KEY) || {};
  var _sharedState$namespac = sharedState.namespace,
      namespace = _sharedState$namespac === undefined ? '' : _sharedState$namespac;

  styleNames.forEach(function (styleName) {
    var className = (0, _getClassName2['default'])(namespace, styleName);
    stylesToClasses[styleName] = className;
  });
  return stylesToClasses;
}

/**
 * Process styles to be consumed by a component.
 *
 * stylesArray {Array} Array of the following: values returned by create, plain JavaScript objects
 * representing inline styles, or arrays thereof.
 *
 * Return an object with optional className and style properties to be spread on a component.
 */
function resolve(stylesArray) {
  var flattenedStyles = (0, _arrayPrototype2['default'])(stylesArray, Infinity);

  var _separateStyles = (0, _separateStyles3['default'])(flattenedStyles),
      classNames = _separateStyles.classNames,
      hasInlineStyles = _separateStyles.hasInlineStyles,
      inlineStyles = _separateStyles.inlineStyles;

  var specificClassNames = classNames.map(function (name, index) {
    return String(name) + ' ' + String(name) + '_' + String(index + 1);
  });
  var className = specificClassNames.join(' ');

  var result = { className: className };
  if (hasInlineStyles) result.style = inlineStyles;
  return result;
}

exports["default"] = { create: create, resolve: resolve };

/***/ }),

/***/ "../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/utils/constants.js":
/***/ ((__unused_webpack_module, exports) => {

Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var GLOBAL_CACHE_KEY = 'reactWithStylesInterfaceCSS';
var MAX_SPECIFICITY = 20;

exports.GLOBAL_CACHE_KEY = GLOBAL_CACHE_KEY;
exports.MAX_SPECIFICITY = MAX_SPECIFICITY;

/***/ }),

/***/ "../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/utils/getClassName.js":
/***/ ((__unused_webpack_module, exports) => {

Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getClassName;
/**
 * Construct a class name.
 *
 * namespace {String} Used to construct unique class names.
 * styleName {String} Name identifying the specific style.
 *
 * Return the class name.
 */
function getClassName(namespace, styleName) {
  var namespaceSegment = namespace.length > 0 ? String(namespace) + '__' : '';
  return '' + namespaceSegment + String(styleName);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/utils/separateStyles.js":
/***/ ((__unused_webpack_module, exports) => {

Object.defineProperty(exports, "__esModule", ({
  value: true
}));
// This function takes an array of styles and separates them into styles that
// are handled by Aphrodite and inline styles.
function separateStyles(stylesArray) {
  var classNames = [];

  // Since determining if an Object is empty requires collecting all of its
  // keys, and we want the best performance in this code because we are in the
  // render path, we are going to do a little bookkeeping ourselves.
  var hasInlineStyles = false;
  var inlineStyles = {};

  // This is run on potentially every node in the tree when rendering, where
  // performance is critical. Normally we would prefer using `forEach`, but
  // old-fashioned for loops are faster so that's what we have chosen here.
  for (var i = 0; i < stylesArray.length; i++) {
    // eslint-disable-line no-plusplus
    var style = stylesArray[i];

    // If this  style is falsy, we just want to disregard it. This allows for
    // syntax like:
    //
    //   css(isFoo && styles.foo)
    if (style) {
      if (typeof style === 'string') {
        classNames.push(style);
      } else {
        Object.assign(inlineStyles, style);
        hasInlineStyles = true;
      }
    }
  }

  return {
    classNames: classNames,
    hasInlineStyles: hasInlineStyles,
    inlineStyles: inlineStyles
  };
}

exports["default"] = separateStyles;

/***/ }),

/***/ "../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

// eslint-disable-next-line import/no-unresolved
module.exports = __webpack_require__("../../node_modules/.pnpm/react-with-styles-interface-css@4.0.3_react-with-styles@3.2.3_react-with-direction@1.4.0_reac_novaymm5uby73lhwfnuik3qcti/node_modules/react-with-styles-interface-css/dist/index.js")["default"];


/***/ })

}]);