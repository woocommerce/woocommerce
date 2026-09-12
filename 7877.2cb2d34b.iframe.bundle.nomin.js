(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7877],{

/***/ "../../packages/js/components/src/calendar/input.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/calendar.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */







const DateInput = ({
  disabled = false,
  value,
  onChange,
  dateFormat,
  label,
  describedBy,
  error,
  onFocus = () => {},
  onBlur = () => {},
  onKeyDown = lodash__WEBPACK_IMPORTED_MODULE_0__.noop,
  errorPosition = 'bottom center'
}) => {
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-calendar__input', {
    'is-empty': value.length === 0,
    'is-error': error
  });
  const id = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.uniqueId)('_woo-dates-input');
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    className: classes,
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
      type: "text",
      className: "woocommerce-calendar__input-text",
      value: value,
      onChange: onChange,
      "aria-label": label,
      id: id,
      "aria-describedby": `${id}-message`,
      placeholder: dateFormat.toLowerCase(),
      onFocus: onFocus,
      onBlur: onBlur,
      onKeyDown: onKeyDown,
      disabled: disabled
    }), error && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
      className: "woocommerce-calendar__input-error",
      focusOnMount: false,
      position: errorPosition,
      children: error
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A,
      className: "calendar-icon"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("p", {
      className: "screen-reader-text",
      id: `${id}-message`,
      children: error || describedBy
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DateInput);
;
DateInput.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "DateInput",
  "props": {
    "disabled": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "onFocus": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "onBlur": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "onKeyDown": {
      "defaultValue": {
        "value": "noop",
        "computed": true
      },
      "description": "",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "errorPosition": {
      "defaultValue": {
        "value": "'bottom center'",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "value": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onChange": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "dateFormat": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "label": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "describedBy": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "error": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};

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
const Level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(2);

try {
    // @ts-ignore
    Context.displayName = "Context";
    // @ts-ignore
    Context.__docgenInfo = { "description": "Context lets components pass information deep down without explicitly\npassing props.\n\nCreated from {@link createContext}", "displayName": "Context", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/context.tsx#Context"] = { docgenInfo: Context.__docgenInfo, name: "Context", path: "../../packages/js/components/src/section/context.tsx#Context" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/header.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H: () => (/* binding */ H)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
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
  const level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$);
  const Heading = 'h' + Math.min(level, 6);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Heading, {
    ...props
  });
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
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 */
const Section = ({
  component,
  children,
  ...props
}) => {
  const Component = component || 'div';
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Consumer, {
    children: level => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Provider, {
      value: level + 1,
      children: component === false ? children : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Component, {
        ...props,
        children: children
      })
    })
  });
};
try {
    // @ts-ignore
    Section.displayName = "Section";
    // @ts-ignore
    Section.__docgenInfo = { "description": "The section wrapper, used to indicate a sub-section (and change the header level context).", "displayName": "Section", "props": { "component": { "defaultValue": null, "description": "The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component.", "name": "component", "required": false, "type": { "name": "string | false | ComponentType<{ className?: string; }>" } }, "className": { "defaultValue": null, "description": "Optional classname", "name": "className", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "The children inside this section, rendered in the `component`. This increases the context level for the next heading used.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/section.tsx#Section"] = { docgenInfo: Section.__docgenInfo, name: "Section", path: "../../packages/js/components/src/section/section.tsx#Section" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

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
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var date_fns_tz__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/date-fns-tz@3.2.0_date-fns@4.1.0/node_modules/date-fns-tz/dist/esm/index.js");
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+date@5.33.1/node_modules/@wordpress/date/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/qs@6.15.1/node_modules/qs/lib/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(qs__WEBPACK_IMPORTED_MODULE_5__);
/**
 * External dependencies
 */






const isoDateFormat = 'YYYY-MM-DD';
const defaultDateTimeFormat = 'YYYY-MM-DDTHH:mm:ss';

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

const presetValues = [{
  value: 'today',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Today', 'woocommerce')
}, {
  value: 'yesterday',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Yesterday', 'woocommerce')
}, {
  value: 'week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Week to date', 'woocommerce')
}, {
  value: 'last_week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last week', 'woocommerce')
}, {
  value: 'month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Month to date', 'woocommerce')
}, {
  value: 'last_month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last month', 'woocommerce')
}, {
  value: 'quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Quarter to date', 'woocommerce')
}, {
  value: 'last_quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last quarter', 'woocommerce')
}, {
  value: 'year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Year to date', 'woocommerce')
}, {
  value: 'last_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last year', 'woocommerce')
}, {
  value: 'custom',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Custom', 'woocommerce')
}];
const periods = [{
  value: 'previous_period',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Previous period', 'woocommerce')
}, {
  value: 'previous_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Previous year', 'woocommerce')
}];
const isValidMomentInput = input => moment__WEBPACK_IMPORTED_MODULE_0___default()(input).isValid();

/**
 * Adds timestamp to a string date.
 *
 * @param {moment.Moment} date      - Date as a moment object.
 * @param {string}        timeOfDay - Either `start`, `now` or `end` of the day.
 * @return {string} - String date with timestamp attached.
 */
const appendTimestamp = (date, timeOfDay) => {
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
  if (moment__WEBPACK_IMPORTED_MODULE_0___default().isMoment(str)) {
    return str.isValid() ? str : null;
  }
  if (typeof str === 'string') {
    const date = moment__WEBPACK_IMPORTED_MODULE_0___default()(str, [isoDateFormat, format], true);
    return date.isValid() ? date : null;
  }
  throw new Error('toMoment requires a string to be passed as an argument');
}

/**
 * Expands moment's localized format tokens ("L", "LL", "ll", ...) into the
 * underlying format the locale defines for them.
 *
 * Moment resolves those tokens only while formatting, so a day rendered through
 * one is invisible to the day token scan below and the range end would be
 * dropped. This mirrors moment's own expansion, including its pass limit, so
 * the expanded format renders exactly what the original one would.
 *
 * @param {string}        format     - localized date string format
 * @param {moment.Locale} localeData - locale the format will be rendered with
 * @return {string} - format string with its localized tokens expanded, leaving
 *                      escaped and bracketed ones as the literals they are
 */
function expandLocalizedFormat(format, localeData) {
  // Bracketed sections and backslash escapes are moment's literals, so an "L"
  // inside one is text; matching them first leaves them untouched, as
  // `longDateFormat` has no entry for them.
  const localizedTokens = /\[[^[]*\]|\\?(?:LTS|LT|LL?L?L?|l{1,4})/g;
  let expanded = format;
  // An expansion can itself hold localized tokens; moment allows six passes.
  let passes = 6;
  while (passes-- > 0) {
    localizedTokens.lastIndex = 0;
    if (!localizedTokens.test(expanded)) {
      break;
    }
    expanded = expanded.replace(localizedTokens, token => localeData.longDateFormat(token) || token);
  }
  return expanded;
}

/**
 * Renders the month and weekday names of a moment format string into escaped
 * literals.
 *
 * Moment picks the grammatical form of both names by pattern-testing the
 * format string while rendering: month choosers look for a day token next to
 * the month one, and Ukrainian renders the genitive weekday whenever a
 * bracketed literal sits before "dddd" - exactly the shape the substitutions
 * here leave behind. Months and weekdays are the only tokens moment resolves
 * against the format, so rendering every name in one pass, against the format
 * as the locale received it, settles each choice before any substitution can
 * flip one.
 *
 * @param {string}        format     - localized date string format
 * @param {moment.Moment} date       - date whose month and weekday to render
 * @param {moment.Locale} localeData - locale the format will be rendered with
 * @return {string} - format string with its month and weekday tokens escaped
 */
function escapeNameTokens(format, date, localeData) {
  // Backslash escapes and bracketed sections are moment's literals, so an
  // "M" or "d" inside one is text. A backslash escapes the whole token that
  // follows it; the escaped alternatives mirror moment's own tokens. "MM",
  // "M", "Mo", "do" and "d" render digits, which carry no grammar.
  return format.replace(/\\(?:Mo|MM?M?M?|ddd?d?|do?)|\\.|\[[^\]]*\]|M{3,4}|d{2,4}/g, token => {
    if (token.startsWith('M')) {
      const name = token.length === 4 ? localeData.months(date, format) : localeData.monthsShort(date, format);
      return `[${name}]`;
    }
    if (!token.startsWith('d')) {
      return token;
    }
    if (token.length === 4) {
      return `[${localeData.weekdays(date, format)}]`;
    }
    const name = token.length === 3 ? localeData.weekdaysShort(date) : localeData.weekdaysMin(date);
    return `[${name}]`;
  });
}

/**
 * Swaps the day of month token of a moment format string for an escaped literal.
 *
 * Substituting in the format instead of in the formatted date keeps the value
 * away from the rest of the localized output: Japanese renders October as
 * "10月", where replacing the day "1" lands on the month instead, and locales
 * with non-Latin digits never match a Latin day number at all.
 *
 * @param {string}   format      - localized date string format
 * @param {Function} replacement - builds the literal text to render in place of
 *                               the day, from the token it replaces
 * @return {string|null} - format string, or null when it holds no day token
 */
function replaceDayToken(format, replacement) {
  let replaced = false;
  // Backslash escapes and bracketed sections are moment's literals, so a "D"
  // inside one is text. A backslash escapes the whole token that follows it,
  // not just its first character; the escaped alternatives mirror moment's
  // own day tokens, so a longer run of "D"s leaves the rest live.
  const dayRangeFormat = format.replace(/\\(?:Do|DDDo|DD?D?D?)|\\.|\[[^\]]*\]|D+o?/g, token => {
    // Runs longer than "DD" are day of year tokens, not day of month.
    const dayDigits = token.endsWith('o') ? token.length - 1 : token.length;
    if (replaced || token.startsWith('[') || token.startsWith('\\') || dayDigits > 2) {
      return token;
    }
    replaced = true;
    return `[${replacement(token)}]`;
  });
  return replaced ? dayRangeFormat : null;
}

/**
 * Given two dates, derive a string representation
 *
 * @param {moment.Moment} after  - start date
 * @param {moment.Moment} before - end date
 * @return {string} - text value for the supplied date range
 */
function getRangeLabel(after, before) {
  const isSameYear = after.year() === before.year();
  const isSameMonth = isSameYear && after.month() === before.month();
  const isSameDay = isSameYear && isSameMonth && after.isSame(before, 'day');
  const fullDateFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('MMM D, YYYY', 'woocommerce');
  if (isSameDay) {
    return after.format(fullDateFormat);
  } else if (isSameMonth) {
    // Formatting each day through the token it replaces keeps whatever the
    // format asked for, such as the zero padding of "DD" or the ordinal of "Do".
    // Everything else still renders from `after`, so a weekday, week number
    // or time in the format stays the one the range starts on.
    const localeData = after.localeData();
    const dayRangeFormat = replaceDayToken(escapeNameTokens(expandLocalizedFormat(fullDateFormat, localeData), after, localeData), dayToken => `${after.format(dayToken)} - ${before.format(dayToken)}`);

    // No day of month token to swap: the format either omits the day or
    // holds only a day of year token, which is left alone. Either way the
    // shared month is as much of the range as this format can carry.
    if (dayRangeFormat === null) {
      return after.format(fullDateFormat);
    }
    return after.format(dayRangeFormat);
  } else if (isSameYear) {
    const monthDayFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('MMM D', 'woocommerce');
    return `${after.format(monthDayFormat)} - ${before.format(fullDateFormat)}`;
  }
  return `${after.format(fullDateFormat)} - ${before.format(fullDateFormat)}`;
}

/**
 * Reads the configured store time zone from `wcSettings`.
 *
 * @return {string | undefined} - IANA zone name or `±HH:mm` offset, if set.
 */
function getStoreTimeZoneSetting() {
  // Optional chaining does not protect the free `window` reference, so guard
  // it for non-browser environments before falling back to the local moment.
  if (typeof window === 'undefined') {
    return undefined;
  }
  return window.wcSettings?.timeZone || window.wcSettings?.admin?.timeZone;
}

/**
 * Gets the current time in the store time zone if set.
 *
 * @return {moment.Moment} - Moment object in the store time zone.
 */
function getStoreTimeZoneMoment() {
  const timeZone = getStoreTimeZoneSetting();
  if (typeof timeZone !== 'string' || timeZone.length === 0) {
    return moment__WEBPACK_IMPORTED_MODULE_0___default()();
  }
  if (['+', '-'].includes(timeZone.charAt(0))) {
    return moment__WEBPACK_IMPORTED_MODULE_0___default()().utcOffset(timeZone);
  }

  // Named IANA zone (e.g. `America/New_York`). Resolve the current UTC
  // offset with `date-fns-tz` (which uses the browser `Intl` API) rather
  // than `moment-timezone`'s `.tz()`: the admin build externalises
  // `moment-timezone` to the global `window.moment`, so a plugin replacing
  // `window.moment` strips `.tz` and crashes Analytics (#64020).
  const offsetInMinutes = (0,date_fns_tz__WEBPACK_IMPORTED_MODULE_1__/* .getTimezoneOffset */ .Zn)(timeZone) / 60000;
  if (Number.isNaN(offsetInMinutes)) {
    return moment__WEBPACK_IMPORTED_MODULE_0___default()();
  }
  return moment__WEBPACK_IMPORTED_MODULE_0___default()().utcOffset(offsetInMinutes);
}

/**
 * Re-applies the store time zone's UTC offset for a moment's own date, keeping
 * its wall-clock time. `getStoreTimeZoneMoment` resolves a named IANA zone's
 * offset for "now", so a range boundary in a different DST period (e.g. last
 * year/quarter) would otherwise be an hour off; this corrects each boundary
 * against its own date. Fixed `±HH:mm` offsets and the no-zone case are
 * returned unchanged (#64020).
 *
 * @param {moment.Moment} date - The moment to anchor.
 * @return {moment.Moment} - The anchored moment.
 */
function anchorToStoreTimeZone(date) {
  const timeZone = getStoreTimeZoneSetting();
  if (typeof timeZone !== 'string' || timeZone.length === 0 || ['+', '-'].includes(timeZone.charAt(0))) {
    return date;
  }
  const offsetInMinutes = (0,date_fns_tz__WEBPACK_IMPORTED_MODULE_1__/* .getTimezoneOffset */ .Zn)(timeZone, date.toDate()) / 60000;
  return Number.isNaN(offsetInMinutes) ? date : date.utcOffset(offsetInMinutes, true);
}

/**
 * Anchors every boundary of a date range to the store time zone.
 * See {@link anchorToStoreTimeZone}.
 *
 * @param {DateValue} range - The computed range.
 * @return {DateValue} - The range with each boundary anchored.
 */
function anchorRangeToStoreTimeZone(range) {
  return {
    primaryStart: anchorToStoreTimeZone(range.primaryStart),
    primaryEnd: anchorToStoreTimeZone(range.primaryEnd),
    secondaryStart: anchorToStoreTimeZone(range.secondaryStart),
    secondaryEnd: anchorToStoreTimeZone(range.secondaryEnd)
  };
}

/**
 * Aligns the moment locale's start of the week with the WordPress
 * "Week Starts On" setting. WordPress core applies the setting to the moment
 * locale, but `wp.date.setSettings` then redefines the locale without a `week`
 * key, resetting the start of the week to Sunday; without this correction,
 * week ranges and calendar layouts ignore the setting.
 */
function ensureMomentStartOfWeek() {
  const startOfWeek = (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_2__.getSettings)().l10n?.startOfWeek;
  if (typeof startOfWeek !== 'number' || !Number.isInteger(startOfWeek) || startOfWeek < 0 || startOfWeek > 6) {
    return;
  }
  if (moment__WEBPACK_IMPORTED_MODULE_0___default().localeData().firstDayOfWeek() !== startOfWeek) {
    moment__WEBPACK_IMPORTED_MODULE_0___default().updateLocale(moment__WEBPACK_IMPORTED_MODULE_0___default().locale(), {
      week: {
        dow: startOfWeek
      }
    });
  }
}
ensureMomentStartOfWeek();

/**
 * Get a DateValue object for a period prior to the current period.
 *
 * @param {moment.DurationInputArg2} period  - the chosen period
 * @param {string}                   compare - `previous_period` or `previous_year`
 * @return {DateValue} - DateValue data about the selected period
 */
function getLastPeriod(period, compare) {
  ensureMomentStartOfWeek();
  const primaryStart = getStoreTimeZoneMoment().startOf(period).subtract(1, period);
  const primaryEnd = primaryStart.clone().endOf(period);
  let secondaryStart;
  let secondaryEnd;
  if (compare === 'previous_period') {
    if (period === 'year') {
      // Subtract two entire periods for years to take into account leap year
      secondaryStart = moment__WEBPACK_IMPORTED_MODULE_0___default()().startOf(period).subtract(2, period);
      secondaryEnd = secondaryStart.clone().endOf(period);
    } else {
      // Otherwise, use days in primary period to figure out how far to go back
      // This is necessary for calculating weeks instead of using `endOf`.
      const daysDiff = primaryEnd.diff(primaryStart, 'days');
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
  return anchorRangeToStoreTimeZone({
    primaryStart,
    primaryEnd,
    secondaryStart,
    secondaryEnd
  });
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
  ensureMomentStartOfWeek();
  const primaryStart = getStoreTimeZoneMoment().startOf(period);
  const primaryEnd = getStoreTimeZoneMoment();
  const daysSoFar = primaryEnd.diff(primaryStart, 'days');
  let secondaryStart;
  let secondaryEnd;
  if (compare === 'previous_period') {
    secondaryStart = primaryStart.clone().subtract(1, period);
    secondaryEnd = primaryEnd.clone().subtract(1, period);
  } else {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    // Set the end time to 23:59:59.
    secondaryEnd = secondaryStart.clone().add(daysSoFar + 1, 'days').subtract(1, 'seconds');
  }
  return anchorRangeToStoreTimeZone({
    primaryStart,
    primaryEnd,
    secondaryStart,
    secondaryEnd
  });
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
const getDateValue = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.memoize)((period, compare, after, before) => {
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
      const difference = before.diff(after, 'days');
      if (compare === 'previous_period') {
        const secondaryEnd = after.clone().subtract(1, 'days');
        const secondaryStart = secondaryEnd.clone().subtract(difference, 'days');
        return {
          primaryStart: after,
          primaryEnd: before,
          secondaryStart,
          secondaryEnd
        };
      }
      return {
        primaryStart: after,
        primaryEnd: before,
        secondaryStart: after.clone().subtract(1, 'years'),
        secondaryEnd: before.clone().subtract(1, 'years')
      };
  }
}, (period, compare, after, before) => [period, compare, after && after.format(), before && before.format()].join(':'));

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
const getDateParamsFromQueryMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.memoize)((period, compare, after, before, defaultDateRange) => {
  if (period && compare) {
    return {
      period,
      compare,
      after: after ? moment__WEBPACK_IMPORTED_MODULE_0___default()(after) : null,
      before: before ? moment__WEBPACK_IMPORTED_MODULE_0___default()(before) : null
    };
  }
  const queryDefaults = (0,qs__WEBPACK_IMPORTED_MODULE_5__.parse)(defaultDateRange.replace(/&amp;/g, '&'));
  if (typeof queryDefaults.period !== 'string') {
    /* eslint-disable no-console */
    console.warn(`Unexpected default period type ${queryDefaults.period}`);
    /* eslint-enable no-console */
    queryDefaults.period = '';
  }
  if (typeof queryDefaults.compare !== 'string') {
    /* eslint-disable no-console */
    console.warn(`Unexpected default compare type ${queryDefaults.compare}`);
    /* eslint-enable no-console */
    queryDefaults.compare = '';
  }
  return {
    period: queryDefaults.period,
    compare: queryDefaults.compare,
    after: queryDefaults.after && isValidMomentInput(queryDefaults.after) ? moment__WEBPACK_IMPORTED_MODULE_0___default()(queryDefaults.after) : null,
    before: queryDefaults.before && isValidMomentInput(queryDefaults.before) ? moment__WEBPACK_IMPORTED_MODULE_0___default()(queryDefaults.before) : null
  };
}, (period, compare, after, before, defaultDateRange) => [period, compare, after, before, defaultDateRange].join(':'));

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
const getDateParamsFromQuery = (query, defaultDateRange = 'period=month&compare=previous_year') => {
  const {
    period,
    compare,
    after,
    before
  } = query;
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
const getCurrentDatesMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.memoize)((period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) => {
  const primaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.find)(presetValues, item => item.value === period);
  if (!primaryItem) {
    throw new Error(`Cannot find period: ${period}`);
  }
  const secondaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.find)(periods, item => item.value === compare);
  if (!secondaryItem) {
    throw new Error(`Cannot find compare: ${compare}`);
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
}, (period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) => [period, compare, primaryStart && primaryStart.format(), primaryEnd && primaryEnd.format(), secondaryStart && secondaryStart.format(), secondaryEnd && secondaryEnd.format()].join(':'));

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
const getCurrentDates = (query, defaultDateRange = 'period=month&compare=previous_year') => {
  const {
    period,
    compare,
    after,
    before
  } = getDateParamsFromQuery(query, defaultDateRange);
  const dateValue = getDateValue(period, compare, after, before);
  if (!dateValue) {
    throw Error('Invalid date range');
  }
  const {
    primaryStart,
    primaryEnd,
    secondaryStart,
    secondaryEnd
  } = dateValue;
  return getCurrentDatesMemoized(period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd);
};

/**
 * Calculates the date difference between two dates. Used in calculating a matching date for previous period.
 *
 * @param {string} date  - Date to compare
 * @param {string} date2 - Secondary date to compare
 * @return {number}  - Difference in days.
 */
const getDateDifferenceInDays = (date, date2) => {
  const _date = moment(date);
  const _date2 = moment(date2);
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
const getPreviousDate = (date, date1, date2, compare = 'previous_year', interval) => {
  const dateMoment = moment(date);
  if (compare === 'previous_year') {
    return dateMoment.clone().subtract(1, 'years');
  }
  const _date1 = moment(date1);
  const _date2 = moment(date2);
  const difference = _date1.diff(_date2, interval);
  return dateMoment.clone().subtract(difference, interval);
};

/**
 * Returns the allowed selectable intervals for a specific query.
 *
 * @param {Query}  query            Current query
 * @param {string} defaultDateRange - the store's default date range
 * @return {Array} Array containing allowed intervals.
 */
function getAllowedIntervalsForQuery(query, defaultDateRange = 'period=&compare=previous_year') {
  const {
    period
  } = getDateParamsFromQuery(query, defaultDateRange);
  let allowed = [];
  if (period === 'custom') {
    const {
      primary
    } = getCurrentDates(query);
    const differenceInDays = getDateDifferenceInDays(primary.before, primary.after);
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
function getIntervalForQuery(query, defaultDateRange = 'period=&compare=previous_year') {
  const allowed = getAllowedIntervalsForQuery(query, defaultDateRange);
  const defaultInterval = allowed[0];
  let current = query.interval || defaultInterval;
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
function getChartTypeForQuery({
  chartType
}) {
  if (chartType !== undefined && ['line', 'bar'].includes(chartType)) {
    return chartType;
  }
  return 'line';
}
const dayTicksThreshold = 63;
const weekTicksThreshold = 9;
const defaultTableDateFormat = 'm/d/Y';

/**
 * Returns d3 date formats for the current interval.
 * See https://github.com/d3/d3-time-format for chart formats.
 *
 * @param {string} interval Interval to get date formats for.
 * @param {number} [ticks]  Number of ticks the axis will have.
 * @return {string} Current interval.
 */
function getDateFormatsForIntervalD3(interval, ticks = 0) {
  let screenReaderFormat = '%B %-d, %Y';
  let tooltipLabelFormat = '%B %-d, %Y';
  let xFormat = '%Y-%m-%d';
  let x2Format = '%b %Y';
  let tableFormat = defaultTableDateFormat;
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
    screenReaderFormat,
    tooltipLabelFormat,
    xFormat,
    x2Format,
    tableFormat
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
function getDateFormatsForIntervalPhp(interval, ticks = 0) {
  let screenReaderFormat = 'F j, Y';
  let tooltipLabelFormat = 'F j, Y';
  let xFormat = 'Y-m-d';
  let x2Format = 'M Y';
  let tableFormat = defaultTableDateFormat;
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
      const escapedWeekOfStr = __('Week of', 'woocommerce').replace(/(\w)/g, '\\$1');
      screenReaderFormat = `${escapedWeekOfStr} F j, Y`;
      tooltipLabelFormat = `${escapedWeekOfStr} F j, Y`;
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
    screenReaderFormat,
    tooltipLabelFormat,
    xFormat,
    x2Format,
    tableFormat
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
function getDateFormatsForInterval(interval, ticks = 0, option = {
  type: 'd3'
}) {
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
function loadLocaleData({
  userLocale,
  weekdaysShort
}) {
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
const dateValidationMessages = {
  invalid: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Invalid date', 'woocommerce'),
  future: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Select a date in the past', 'woocommerce'),
  startAfterEnd: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Start date must be before end date', 'woocommerce'),
  endBeforeStart: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Start date must be before end date', 'woocommerce')
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
  const date = toMoment(format, value);
  if (!date) {
    return {
      date: null,
      error: dateValidationMessages.invalid
    };
  }
  if (moment__WEBPACK_IMPORTED_MODULE_0___default()().isBefore(date, 'day')) {
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
    date
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
  const startYear = new Date(startDate).getFullYear();
  const endYear = new Date(endDate).getFullYear();
  if (!isNaN(startYear) && !isNaN(endYear)) {
    // Check each year in the range
    for (let year = startYear; year <= endYear; year++) {
      if (isLeapYear(year)) {
        return true;
      }
    }
  }
  return false; // No leap years in the range or invalid date
}

/***/ }),

/***/ "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale sync recursive ^\\.\\/.*$":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./af": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/af.js",
	"./af.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/af.js",
	"./ar": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar.js",
	"./ar-dz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ma.js",
	"./ar-ps": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ps.js",
	"./ar-ps.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ps.js",
	"./ar-sa": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-tn.js",
	"./ar.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar.js",
	"./az": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/az.js",
	"./az.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/az.js",
	"./be": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/be.js",
	"./be.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/be.js",
	"./bg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bg.js",
	"./bg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bg.js",
	"./bm": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bm.js",
	"./bm.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bm.js",
	"./bn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn.js",
	"./bn-bd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn-bd.js",
	"./bn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn.js",
	"./bo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bo.js",
	"./bo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bo.js",
	"./br": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/br.js",
	"./br.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/br.js",
	"./bs": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bs.js",
	"./bs.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bs.js",
	"./ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ca.js",
	"./ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ca.js",
	"./cs": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cs.js",
	"./cs.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cs.js",
	"./cv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cv.js",
	"./cv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cv.js",
	"./cy": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cy.js",
	"./cy.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cy.js",
	"./da": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/da.js",
	"./da.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/da.js",
	"./de": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de.js",
	"./de-at": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-at.js",
	"./de-at.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-at.js",
	"./de-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-ch.js",
	"./de.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de.js",
	"./dv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/dv.js",
	"./dv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/dv.js",
	"./el": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/el.js",
	"./el.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/el.js",
	"./en-au": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-au.js",
	"./en-au.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-au.js",
	"./en-ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ca.js",
	"./en-gb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-gb.js",
	"./en-ie": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ie.js",
	"./en-il": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-il.js",
	"./en-il.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-il.js",
	"./en-in": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-in.js",
	"./en-in.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-in.js",
	"./en-nz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-nz.js",
	"./en-sg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-sg.js",
	"./eo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eo.js",
	"./eo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eo.js",
	"./es": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es.js",
	"./es-do": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-do.js",
	"./es-do.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-do.js",
	"./es-mx": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-mx.js",
	"./es-us": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-us.js",
	"./es-us.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-us.js",
	"./es.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es.js",
	"./et": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/et.js",
	"./et.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/et.js",
	"./eu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eu.js",
	"./eu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eu.js",
	"./fa": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fa.js",
	"./fa.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fa.js",
	"./fi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fi.js",
	"./fi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fi.js",
	"./fil": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fil.js",
	"./fil.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fil.js",
	"./fo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fo.js",
	"./fo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fo.js",
	"./fr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr.js",
	"./fr-ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ch.js",
	"./fr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr.js",
	"./fy": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fy.js",
	"./fy.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fy.js",
	"./ga": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ga.js",
	"./ga.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ga.js",
	"./gd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gd.js",
	"./gd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gd.js",
	"./gl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gl.js",
	"./gl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gl.js",
	"./gom-deva": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-latn.js",
	"./gu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gu.js",
	"./gu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gu.js",
	"./he": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/he.js",
	"./he.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/he.js",
	"./hi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hi.js",
	"./hi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hi.js",
	"./hr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hr.js",
	"./hr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hr.js",
	"./hu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hu.js",
	"./hu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hu.js",
	"./hy-am": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hy-am.js",
	"./id": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/id.js",
	"./id.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/id.js",
	"./is": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/is.js",
	"./is.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/is.js",
	"./it": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it.js",
	"./it-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it-ch.js",
	"./it.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it.js",
	"./ja": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ja.js",
	"./ja.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ja.js",
	"./jv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/jv.js",
	"./jv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/jv.js",
	"./ka": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ka.js",
	"./ka.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ka.js",
	"./kk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kk.js",
	"./kk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kk.js",
	"./km": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/km.js",
	"./km.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/km.js",
	"./kn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kn.js",
	"./kn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kn.js",
	"./ko": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ko.js",
	"./ko.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ko.js",
	"./ku": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku.js",
	"./ku-kmr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku-kmr.js",
	"./ku-kmr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku-kmr.js",
	"./ku.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku.js",
	"./ky": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ky.js",
	"./ky.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ky.js",
	"./lb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lb.js",
	"./lb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lb.js",
	"./lo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lo.js",
	"./lo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lo.js",
	"./lt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lt.js",
	"./lt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lt.js",
	"./lv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lv.js",
	"./lv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lv.js",
	"./me": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/me.js",
	"./me.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/me.js",
	"./mi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mi.js",
	"./mi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mi.js",
	"./mk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mk.js",
	"./mk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mk.js",
	"./ml": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ml.js",
	"./ml.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ml.js",
	"./mn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mn.js",
	"./mn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mn.js",
	"./mr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mr.js",
	"./mr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mr.js",
	"./ms": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms.js",
	"./ms-my": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms-my.js",
	"./ms.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms.js",
	"./mt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mt.js",
	"./mt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mt.js",
	"./my": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/my.js",
	"./my.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/my.js",
	"./nb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nb.js",
	"./nb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nb.js",
	"./ne": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ne.js",
	"./ne.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ne.js",
	"./nl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl.js",
	"./nl-be": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl-be.js",
	"./nl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl.js",
	"./nn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nn.js",
	"./nn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nn.js",
	"./oc-lnc": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pa-in.js",
	"./pl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pl.js",
	"./pl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pl.js",
	"./pt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt.js",
	"./pt-br": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt-br.js",
	"./pt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt.js",
	"./ro": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ro.js",
	"./ro.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ro.js",
	"./ru": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ru.js",
	"./ru.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ru.js",
	"./sd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sd.js",
	"./sd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sd.js",
	"./se": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/se.js",
	"./se.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/se.js",
	"./si": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/si.js",
	"./si.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/si.js",
	"./sk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sk.js",
	"./sk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sk.js",
	"./sl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sl.js",
	"./sl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sl.js",
	"./sq": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sq.js",
	"./sq.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sq.js",
	"./sr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr.js",
	"./sr-cyrl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr.js",
	"./ss": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ss.js",
	"./ss.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ss.js",
	"./sv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sv.js",
	"./sv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sv.js",
	"./sw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sw.js",
	"./sw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sw.js",
	"./ta": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ta.js",
	"./ta.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ta.js",
	"./te": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/te.js",
	"./te.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/te.js",
	"./tet": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tet.js",
	"./tet.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tet.js",
	"./tg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tg.js",
	"./tg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tg.js",
	"./th": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/th.js",
	"./th.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/th.js",
	"./tk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tk.js",
	"./tk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tk.js",
	"./tl-ph": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tl-ph.js",
	"./tlh": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tlh.js",
	"./tlh.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tlh.js",
	"./tr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tr.js",
	"./tr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tr.js",
	"./tzl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzl.js",
	"./tzl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzl.js",
	"./tzm": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm.js",
	"./tzm-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm.js",
	"./ug-cn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ug-cn.js",
	"./uk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uk.js",
	"./uk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uk.js",
	"./ur": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ur.js",
	"./ur.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ur.js",
	"./uz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz.js",
	"./uz-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz-latn.js",
	"./uz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz.js",
	"./vi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/vi.js",
	"./vi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/vi.js",
	"./x-pseudo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/x-pseudo.js",
	"./yo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/yo.js",
	"./yo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/yo.js",
	"./zh-cn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-tw.js"
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
webpackContext.id = "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ })

}]);