(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4832],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/radio-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ RadioControl)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");



/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


function RadioControl(_ref) {
  let {
    label,
    className,
    selected,
    help,
    onChange,
    hideLabelFromVision,
    options = [],
    ...props
  } = _ref;
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(RadioControl);
  const id = `inspector-radio-control-${instanceId}`;

  const onChangeValue = event => onChange(event.target.value);

  return !(0,lodash__WEBPACK_IMPORTED_MODULE_0__.isEmpty)(options) && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_base_control__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .Ay, {
    label: label,
    id: id,
    hideLabelFromVision: hideLabelFromVision,
    help: help,
    className: classnames__WEBPACK_IMPORTED_MODULE_1___default()(className, 'components-radio-control')
  }, options.map((option, index) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("div", {
    key: `${id}-${index}`,
    className: "components-radio-control__option"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("input", (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)({
    id: `${id}-${index}`,
    className: "components-radio-control__input",
    type: "radio",
    name: id,
    value: option.value,
    onChange: onChangeValue,
    checked: option.value === selected,
    "aria-describedby": !!help ? `${id}__help` : undefined
  }, props)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("label", {
    htmlFor: `${id}-${index}`
  }, option.label))));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../packages/js/components/src/date-time-picker-control/date-time-picker-control.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   hD: () => (/* binding */ DateTimePickerControl)
/* harmony export */ });
/* unused harmony exports defaultDateFormat, default12HourDateTimeFormat, default24HourDateTimeFormat */
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
/* harmony import */ var core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_date_to_iso_string_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-iso-string.js");
/* harmony import */ var core_js_modules_es_date_to_iso_string_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_iso_string_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+date@4.6.1/node_modules/@wordpress/date/build-module/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/calendar.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/date.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/index.js");




var _excluded = ["currentDate", "isDateOnlyPicker", "is12HourPicker", "timeForDateOnly", "dateTimeFormat", "disabled", "onChange", "onBlur", "label", "placeholder", "help", "className", "onChangeDebounceWait", "popoverProps"];

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
      (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}


















/**
 * External dependencies
 */










// PHP style formatting:
// https://wordpress.org/support/article/formatting-date-and-time/
var defaultDateFormat = 'm/d/Y';
var default12HourDateTimeFormat = 'm/d/Y h:i a';
var default24HourDateTimeFormat = 'm/d/Y H:i';
var MINUTE_IN_SECONDS = 60;
var HOUR_IN_MINUTES = 60;
var HOUR_IN_SECONDS = 60 * MINUTE_IN_SECONDS;

/**
 * Map of PHP formats to Moment.js formats.
 *
 * Copied from @wordpress/date, since it's not exposed. If this is exposed upstream,
 * it should ideally be used from there.
 */
var formatMap = {
  // Day.
  d: 'DD',
  D: 'ddd',
  j: 'D',
  l: 'dddd',
  N: 'E',
  S: function S(momentDate) {
    // Do - D.
    var num = momentDate.format('D');
    var withOrdinal = momentDate.format('Do');
    return withOrdinal.replace(num, '');
  },
  w: 'd',
  z: function z(momentDate) {
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
  t: function t(momentDate) {
    return momentDate.daysInMonth();
  },
  L: function L(momentDate) {
    return momentDate.isLeapYear() ? '1' : '0';
  },
  o: 'GGGG',
  Y: 'YYYY',
  y: 'YY',
  // Time.
  a: 'a',
  A: 'A',
  B: function B(momentDate) {
    var timezoned = moment__WEBPACK_IMPORTED_MODULE_20___default()(momentDate).utcOffset(60);
    var seconds = parseInt(timezoned.format('s'), 10),
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
  I: function I(momentDate) {
    return momentDate.isDST() ? '1' : '0';
  },
  O: 'ZZ',
  P: 'Z',
  T: 'z',
  Z: function Z(momentDate) {
    // Timezone offset in seconds.
    var offset = momentDate.format('Z');
    var sign = offset[0] === '-' ? -1 : 1;
    var parts = offset.substring(1).split(':').map(function (n) {
      return parseInt(n, 10);
    });
    return sign * (parts[0] * HOUR_IN_MINUTES + parts[1]) * MINUTE_IN_SECONDS;
  },
  // Full date/time.
  c: 'YYYY-MM-DDTHH:mm:ssZ',
  // .toISOString.
  r: function r(momentDate) {
    return momentDate.locale('en').format('ddd, DD MMM YYYY HH:mm:ss ZZ');
  },
  U: 'X'
};

/**
 * A modified version of the `format` function from @wordpress/date.
 * This is needed to create a date object from the typed string and the date format,
 * that needs to be mapped from the PHP format to moment's format.
 */
var createMomentDate = function createMomentDate(dateFormat, date) {
  var i, _char;
  var newFormat = [];
  for (i = 0; i < dateFormat.length; i++) {
    _char = dateFormat[i];
    // Is this an escape?
    if (_char === '\\') {
      // Add next character, then move on.
      i++;
      newFormat.push('[' + dateFormat[i] + ']');
      continue;
    }
    if (_char in formatMap) {
      var formatter = formatMap[_char];
      if (typeof formatter !== 'string') {
        // If the format is a function, call it.
        newFormat.push('[' + formatter(moment__WEBPACK_IMPORTED_MODULE_20___default()(date)) + ']');
      } else {
        // Otherwise, add as a formatting string.
        newFormat.push(formatter);
      }
    } else {
      newFormat.push('[' + _char + ']');
    }
  }
  // Join with [] between to separate characters, and replace
  // unneeded separators with static text.
  return moment__WEBPACK_IMPORTED_MODULE_20___default()(date, newFormat.join('[]'));
};
var DateTimePickerControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.forwardRef)(function ForwardedDateTimePickerControl(_ref, _ref3) {
  var currentDate = _ref.currentDate,
    _ref$isDateOnlyPicker = _ref.isDateOnlyPicker,
    isDateOnlyPicker = _ref$isDateOnlyPicker === void 0 ? false : _ref$isDateOnlyPicker,
    _ref$is12HourPicker = _ref.is12HourPicker,
    is12HourPicker = _ref$is12HourPicker === void 0 ? true : _ref$is12HourPicker,
    _ref$timeForDateOnly = _ref.timeForDateOnly,
    timeForDateOnly = _ref$timeForDateOnly === void 0 ? 'start-of-day' : _ref$timeForDateOnly,
    dateTimeFormat = _ref.dateTimeFormat,
    _ref$disabled = _ref.disabled,
    disabled = _ref$disabled === void 0 ? false : _ref$disabled,
    onChange = _ref.onChange,
    onBlur = _ref.onBlur,
    label = _ref.label,
    placeholder = _ref.placeholder,
    help = _ref.help,
    _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    _ref$onChangeDebounce = _ref.onChangeDebounceWait,
    onChangeDebounceWait = _ref$onChangeDebounce === void 0 ? 500 : _ref$onChangeDebounce,
    _ref$popoverProps = _ref.popoverProps,
    popoverProps = _ref$popoverProps === void 0 ? {} : _ref$popoverProps,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_24__/* ["default"] */ .A)(_ref, _excluded);
  var id = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_25__/* ["default"] */ .A)(DateTimePickerControl, 'inspector-date-time-picker-control', props.id);
  var inputControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useRef)();
  var displayFormat = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useMemo)(function () {
    if (dateTimeFormat) {
      return dateTimeFormat;
    }
    if (isDateOnlyPicker) {
      return defaultDateFormat;
    }
    if (is12HourPicker) {
      return default12HourDateTimeFormat;
    }
    return default24HourDateTimeFormat;
  }, [dateTimeFormat, isDateOnlyPicker, is12HourPicker]);
  function parseAsISODateTime(dateString) {
    var assumeLocalTime = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    return assumeLocalTime ? moment__WEBPACK_IMPORTED_MODULE_20___default()(dateString, (moment__WEBPACK_IMPORTED_MODULE_20___default().ISO_8601), true).utc() : moment__WEBPACK_IMPORTED_MODULE_20___default().utc(dateString, (moment__WEBPACK_IMPORTED_MODULE_20___default().ISO_8601), true);
  }
  function parseAsLocalDateTime(dateString) {
    // parse input date string as local time;
    // be lenient of user input and try to match any format Moment can
    return dateTimeFormat && dateString ? createMomentDate(dateTimeFormat, dateString) : moment__WEBPACK_IMPORTED_MODULE_20___default()(dateString);
  }
  var maybeForceTime = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useCallback)(function (momentDate) {
    if (!isDateOnlyPicker || !momentDate.isValid()) return momentDate;

    // We want to set to the start/end of the local time, so
    // we need to put our Moment instance into "local" mode
    var updatedMomentDate = momentDate.clone().local();
    if (timeForDateOnly === 'start-of-day') {
      updatedMomentDate.startOf('day');
    } else if (timeForDateOnly === 'end-of-day') {
      updatedMomentDate.endOf('day');
    }
    return updatedMomentDate;
  }, [isDateOnlyPicker, timeForDateOnly]);
  function hasFocusLeftInputAndDropdownContent(event) {
    var _event$relatedTarget;
    return !((_event$relatedTarget = event.relatedTarget) !== null && _event$relatedTarget !== void 0 && _event$relatedTarget.closest('.components-dropdown__content'));
  }
  var formatDateTimeForDisplay = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useCallback)(function (dateTime) {
    var _dateTime$creationDat;
    return dateTime.isValid() ? (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_19__/* .format */ .GP)(displayFormat, dateTime.local()) : ((_dateTime$creationDat = dateTime.creationData().input) === null || _dateTime$creationDat === void 0 ? void 0 : _dateTime$creationDat.toString()) || '';
  }, [displayFormat]);
  function formatDateTimeAsISO(dateTime) {
    var _dateTime$creationDat2;
    return dateTime.isValid() ? dateTime.utc().toISOString() : ((_dateTime$creationDat2 = dateTime.creationData().input) === null || _dateTime$creationDat2 === void 0 ? void 0 : _dateTime$creationDat2.toString()) || '';
  }
  var currentDateTime = parseAsISODateTime(currentDate);
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useState)(currentDateTime.isValid() ? formatDateTimeForDisplay(maybeForceTime(currentDateTime)) : ''),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_26__/* ["default"] */ .A)(_useState, 2),
    inputString = _useState2[0],
    setInputString = _useState2[1];
  var inputStringDateTime = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useMemo)(function () {
    return maybeForceTime(parseAsLocalDateTime(inputString));
  }, [inputString, maybeForceTime]);

  // We keep a ref to the onChange prop so that we can be sure we are
  // always using the more up-to-date value, even if it changes
  // it while a debounced onChange handler is in progress
  var onChangeRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useRef)();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useEffect)(function () {
    onChangeRef.current = onChange;
  }, [onChange]);
  var setInputStringAndMaybeCallOnChange = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useCallback)(function (newInputString, isUserTypedInput) {
    // InputControl doesn't fire an onChange if what the user has typed
    // matches the current value of the input field. To get around this,
    // we pull the value directly out of the input field. This fixes
    // the issue where the user ends up typing the same value. Unless they
    // are typing extra slow. Without this workaround, we miss the last
    // character typed.
    var lastTypedValue = inputControl.current.value;
    var newDateTime = maybeForceTime(isUserTypedInput ? parseAsLocalDateTime(lastTypedValue) : parseAsISODateTime(newInputString, true));
    var isDateTimeSame = newDateTime.isSame(inputStringDateTime);
    if (isUserTypedInput) {
      setInputString(lastTypedValue);
    } else if (!isDateTimeSame) {
      setInputString(formatDateTimeForDisplay(newDateTime));
    }
    if (typeof onChangeRef.current === 'function' && !isDateTimeSame) {
      onChangeRef.current(newDateTime.isValid() ? formatDateTimeAsISO(newDateTime) : lastTypedValue, newDateTime.isValid());
    }
  }, [formatDateTimeForDisplay, inputStringDateTime, maybeForceTime]);
  var debouncedSetInputStringAndMaybeCallOnChange = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_27__/* ["default"] */ .A)(setInputStringAndMaybeCallOnChange, onChangeDebounceWait);
  function focusInputControl() {
    if (inputControl.current) {
      inputControl.current.focus();
    }
  }
  var getUserInputOrUpdatedCurrentDate = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useCallback)(function () {
    if (currentDate !== undefined) {
      var newDateTime = maybeForceTime(parseAsISODateTime(currentDate, false));
      if (!newDateTime.isValid()) {
        // keep the invalid string, so the user can correct it
        return currentDate;
      }
      if (!newDateTime.isSame(inputStringDateTime)) {
        return formatDateTimeForDisplay(newDateTime);
      }

      // the new currentDate is the same date as the inputString,
      // so keep exactly what the user typed in
      return inputString;
    }

    // the component is uncontrolled (not using currentDate),
    // so just return the input string
    return inputString;
  }, [currentDate, formatDateTimeForDisplay, inputString, maybeForceTime]);

  // We keep a ref to the onBlur prop so that we can be sure we are
  // always using the more up-to-date value, otherwise, we get in
  // any infinite loop when calling onBlur
  var onBlurRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useRef)();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useEffect)(function () {
    onBlurRef.current = onBlur;
  }, [onBlur]);
  var callOnBlurIfDropdownIsNotOpening = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.useCallback)(function (willOpen) {
    if (!willOpen && typeof onBlurRef.current === 'function' && inputControl.current) {
      // in case the component is blurred before a debounced
      // change has been processed, immediately set the input string
      // to the current value of the input field, so that
      // it won't be set back to the pre-change value
      setInputStringAndMaybeCallOnChange(inputControl.current.value, true);
      onBlurRef.current();
    }
  }, []);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_28__/* ["default"] */ .A, {
    className: classnames__WEBPACK_IMPORTED_MODULE_21___default()('woocommerce-date-time-picker-control', className),
    focusOnMount: false
    // @ts-expect-error `onToggle` does exist.
    ,

    onToggle: callOnBlurIfDropdownIsNotOpening,
    renderToggle: function renderToggle(_ref2) {
      var isOpen = _ref2.isOpen,
        onClose = _ref2.onClose,
        onToggle = _ref2.onToggle;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_29__/* ["default"] */ .Ay, {
        id: id,
        label: label,
        help: help
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_30__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_31__/* ["default"] */ .A)({}, props, {
        id: id,
        ref: function ref(element) {
          inputControl.current = element;
          if (typeof _ref3 === 'function') {
            _ref3(element);
          }
        },
        disabled: disabled,
        value: getUserInputOrUpdatedCurrentDate(),
        onChange: function onChange(newValue) {
          return debouncedSetInputStringAndMaybeCallOnChange(newValue, true);
        },
        onBlur: function onBlur(event) {
          if (hasFocusLeftInputAndDropdownContent(event)) {
            // close the dropdown, which will also trigger
            // the component's onBlur to be called
            onClose();
          }
        },
        suffix: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_32__/* ["default"] */ .A, {
          icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_33__/* ["default"] */ .A,
          className: "calendar-icon woocommerce-date-time-picker-control__input-control__suffix",
          onClick: focusInputControl,
          size: 16
        }),
        placeholder: placeholder,
        describedBy: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_22__/* .sprintf */ .nv)( /* translators: A datetime format */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_22__.__)('Date input describing a selected date in format %s', 'woocommerce'), dateTimeFormat),
        onFocus: function onFocus() {
          if (isOpen) {
            return; // the dropdown is already open, do we don't need to do anything
          }
          onToggle(); // show the dropdown
        },
        "aria-expanded": isOpen
      })));
    },
    popoverProps: _objectSpread({
      anchor: inputControl.current,
      className: 'woocommerce-date-time-picker-control__popover',
      placement: 'bottom-start'
    }, popoverProps),
    renderContent: function renderContent() {
      var Picker = isDateOnlyPicker ? _wordpress_components__WEBPACK_IMPORTED_MODULE_34__/* ["default"] */ .A : _wordpress_components__WEBPACK_IMPORTED_MODULE_35__/* ["default"] */ .Ay;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_23__.createElement)(Picker
      // @ts-expect-error null is valid for currentDate
      , {
        currentDate: inputStringDateTime.isValid() ? formatDateTimeAsISO(inputStringDateTime) : null,
        onChange: function onChange(newDateTimeISOString) {
          return setInputStringAndMaybeCallOnChange(newDateTimeISOString, false);
        },
        is12Hour: is12HourPicker
        // Opt out of the Reset and Help buttons, as they are going to be removed.
        // These properties are removed in @wordpress/components 25.0.0 (Gutenberg 15.9.0).
        ,

        __nextRemoveResetButton: true,
        __nextRemoveHelpButton: true
      });
    }
  });
});
try {
    // @ts-ignore
    DateTimePickerControl.displayName = "DateTimePickerControl";
    // @ts-ignore
    DateTimePickerControl.__docgenInfo = { "description": "", "displayName": "DateTimePickerControl", "props": { "currentDate": { "defaultValue": null, "description": "", "name": "currentDate", "required": false, "type": { "name": "string | null" } }, "dateTimeFormat": { "defaultValue": null, "description": "", "name": "dateTimeFormat", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": { value: "false" }, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "isDateOnlyPicker": { "defaultValue": { value: "false" }, "description": "", "name": "isDateOnlyPicker", "required": false, "type": { "name": "boolean" } }, "is12HourPicker": { "defaultValue": { value: "true" }, "description": "", "name": "is12HourPicker", "required": false, "type": { "name": "boolean" } }, "timeForDateOnly": { "defaultValue": { value: "start-of-day" }, "description": "", "name": "timeForDateOnly", "required": false, "type": { "name": "enum", "value": [{ "value": "\"start-of-day\"" }, { "value": "\"end-of-day\"" }] } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": false, "type": { "name": "DateTimePickerControlOnChangeHandler" } }, "onBlur": { "defaultValue": null, "description": "", "name": "onBlur", "required": false, "type": { "name": "((() => void) & FocusEventHandler<HTMLInputElement>)" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": false, "type": { "name": "string" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "help": { "defaultValue": null, "description": "", "name": "help", "required": false, "type": { "name": "string | null" } }, "onChangeDebounceWait": { "defaultValue": { value: "500" }, "description": "", "name": "onChangeDebounceWait", "required": false, "type": { "name": "number" } }, "popoverProps": { "defaultValue": { value: "{}" }, "description": "", "name": "popoverProps", "required": false, "type": { "name": "Record<string, string | boolean>" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/date-time-picker-control/date-time-picker-control.tsx#DateTimePickerControl"] = { docgenInfo: DateTimePickerControl.__docgenInfo, name: "DateTimePickerControl", path: "../../packages/js/components/src/date-time-picker-control/date-time-picker-control.tsx#DateTimePickerControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/form/form.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  l: () => (/* binding */ Form)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js");
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);
// EXTERNAL MODULE: ../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js
var runtime = __webpack_require__("../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/setWith.js
var setWith = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/setWith.js");
var setWith_default = /*#__PURE__*/__webpack_require__.n(setWith);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/get.js
var get = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/get.js");
var get_default = /*#__PURE__*/__webpack_require__.n(get);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/clone.js
var clone = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/clone.js");
var clone_default = /*#__PURE__*/__webpack_require__.n(clone);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/isEqual.js
var isEqual = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/isEqual.js");
var isEqual_default = /*#__PURE__*/__webpack_require__.n(isEqual);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/omit.js
var omit = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/omit.js");
var omit_default = /*#__PURE__*/__webpack_require__.n(omit);
;// CONCATENATED MODULE: ../../packages/js/components/src/form/form-context.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

// eslint-disable-next-line @typescript-eslint/no-explicit-any

// eslint-disable-next-line @typescript-eslint/no-explicit-any
var FormContext =
// eslint-disable-next-line @typescript-eslint/no-explicit-any
(0,react.createContext)(
// eslint-disable-next-line @typescript-eslint/no-explicit-any
{});

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function useFormContext() {
  var formContext = useContext(FormContext);
  return formContext;
}
;// CONCATENATED MODULE: ../../packages/js/components/src/form/form.tsx













var _excluded = ["children", "onSubmit", "onChange", "onChanges"],
  _excluded2 = ["className", "onBlur", "onChange", "sanitize"];

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

function isChangeEvent(value) {
  return value.target !== undefined;
}
/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FormComponent(_ref, ref) {
  var _props$initialValues, _props$initialValues2;
  var children = _ref.children,
    _ref$onSubmit = _ref.onSubmit,
    onSubmit = _ref$onSubmit === void 0 ? function () {} : _ref$onSubmit,
    _ref$onChange = _ref.onChange,
    onChange = _ref$onChange === void 0 ? function () {} : _ref$onChange,
    _ref$onChanges = _ref.onChanges,
    onChanges = _ref$onChanges === void 0 ? function () {} : _ref$onChanges,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  var initialValues = (0,react.useRef)((_props$initialValues = props.initialValues) !== null && _props$initialValues !== void 0 ? _props$initialValues : {});
  var _useState = (0,react.useState)((_props$initialValues2 = props.initialValues) !== null && _props$initialValues2 !== void 0 ? _props$initialValues2 : {}),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    values = _useState2[0],
    setValuesInternal = _useState2[1];
  var _useState3 = (0,react.useState)(props.errors || {}),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    errors = _useState4[0],
    setErrors = _useState4[1];
  var _useState5 = (0,react.useState)(props.touched || {}),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    touched = _useState6[0],
    setTouched = _useState6[1];
  var validate = (0,react.useCallback)(function (newValues) {
    var onValidate = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : function () {};
    var newErrors = props.validate ? props.validate(newValues) : {};
    setErrors(newErrors || {});
    onValidate(newErrors);
  }, [props.validate]);
  (0,react.useEffect)(function () {
    validate(values);
  }, []);
  var resetForm = function resetForm(newInitialValues) {
    var _ref2;
    var newTouchedFields = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var newErrors = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    var newValues = (_ref2 = newInitialValues !== null && newInitialValues !== void 0 ? newInitialValues : initialValues.current) !== null && _ref2 !== void 0 ? _ref2 : {};
    initialValues.current = newValues;
    setValuesInternal(newValues);
    setTouched(newTouchedFields);
    setErrors(newErrors);
  };
  (0,react.useImperativeHandle)(ref, function () {
    return {
      resetForm: resetForm
    };
  });
  var isValidForm = /*#__PURE__*/function () {
    var _ref3 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee() {
      return regenerator_default().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            validate(values);
            return _context.abrupt("return", !Object.keys(errors).length);
          case 2:
          case "end":
            return _context.stop();
        }
      }, _callee);
    }));
    return function isValidForm() {
      return _ref3.apply(this, arguments);
    };
  }();
  var setValues = (0,react.useCallback)(function (valuesToSet) {
    var newValues = _objectSpread(_objectSpread({}, values), valuesToSet);
    setValuesInternal(newValues);
    validate(newValues, function (newErrors) {
      var onChangeCallback = props.onChangeCallback;

      // Note that onChange is a no-op by default so this will never be null
      var singleValueChangeCallback = onChangeCallback || onChange;
      if (onChangeCallback) {
        (0,build_module/* default */.A)('onChangeCallback', {
          version: '9.0.0',
          alternative: 'onChange',
          plugin: '@woocommerce/components'
        });
      }
      if (!singleValueChangeCallback && !onChanges) {
        return;
      }

      // onChange and onChanges keep track of validity, so needs to
      // happen after setting the error state.

      var isValid = !Object.keys(newErrors || {}).length;
      var nameValuePairs = [];
      for (var _key in valuesToSet) {
        var nameValuePair = {
          name: _key,
          value: valuesToSet[_key]
        };
        nameValuePairs.push(nameValuePair);
        if (singleValueChangeCallback) {
          singleValueChangeCallback(nameValuePair, newValues, isValid);
        }
      }
      if (onChanges) {
        onChanges(nameValuePairs, newValues, isValid);
      }
    });
  }, [values, validate, onChange, props.onChangeCallback]);
  var setValue = (0,react.useCallback)(
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function (name, value) {
    setValues(setWith_default()(_objectSpread({}, values), name, value, (clone_default())));
  }, [values, validate, onChange, props.onChangeCallback]);
  var handleChange = (0,react.useCallback)(function (name, value) {
    // Handle native events.
    if (isChangeEvent(value) && value.target) {
      if (value.target.type === 'checkbox') {
        setValue(name, !get_default()(values, name));
      } else {
        setValue(name, value.target.value);
      }
    } else {
      setValue(name, value);
    }
  }, [setValue]);
  var handleBlur = (0,react.useCallback)(function (name) {
    setTouched(_objectSpread(_objectSpread({}, touched), {}, (0,defineProperty/* default */.A)({}, name, true)));
  }, [touched]);
  var handleSubmit = /*#__PURE__*/function () {
    var _ref4 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee2() {
      var onSubmitCallback, touchedFields, callback;
      return regenerator_default().wrap(function _callee2$(_context2) {
        while (1) switch (_context2.prev = _context2.next) {
          case 0:
            onSubmitCallback = props.onSubmitCallback;
            touchedFields = {};
            Object.keys(values).map(function (name) {
              return touchedFields[name] = true;
            });
            setTouched(touchedFields);
            _context2.next = 6;
            return isValidForm();
          case 6:
            if (!_context2.sent) {
              _context2.next = 11;
              break;
            }
            // Note that onSubmit is a no-op by default so this will never be null
            callback = onSubmitCallback || onSubmit;
            if (onSubmitCallback) {
              (0,build_module/* default */.A)('onSubmitCallback', {
                version: '9.0.0',
                alternative: 'onSubmit',
                plugin: '@woocommerce/components'
              });
            }
            if (!callback) {
              _context2.next = 11;
              break;
            }
            return _context2.abrupt("return", callback(values));
          case 11:
          case "end":
            return _context2.stop();
        }
      }, _callee2);
    }));
    return function handleSubmit() {
      return _ref4.apply(this, arguments);
    };
  }();
  function getInputProps(name) {
    var inputProps = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var inputValue = get_default()(values, name);
    var isTouched = touched[name];
    var inputError = get_default()(errors, name);
    var classNameProp = inputProps.className,
      onBlurProp = inputProps.onBlur,
      onChangeProp = inputProps.onChange,
      sanitize = inputProps.sanitize,
      additionalProps = (0,objectWithoutProperties/* default */.A)(inputProps, _excluded2);
    return _objectSpread({
      value: inputValue,
      checked: Boolean(inputValue),
      selected: inputValue,
      onChange: function onChange(value) {
        handleChange(name, value);
        if (onChangeProp) {
          onChangeProp(value);
        }
      },
      onBlur: function onBlur() {
        if (sanitize) {
          handleChange(name, sanitize(inputValue));
        }
        handleBlur(name);
        if (onBlurProp) {
          onBlurProp();
        }
      },
      className: classnames_default()(classNameProp, {
        'has-error': isTouched && inputError
      }),
      help: isTouched ? inputError : null
    }, additionalProps);
  }
  function getCheckboxControlProps(name) {
    var inputProps = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    return omit_default()(getInputProps(name, inputProps), ['selected', 'value']);
  }
  function getSelectControlProps(name) {
    var inputProps = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var selectControlProps = getInputProps(name, inputProps);
    return _objectSpread(_objectSpread({}, selectControlProps), {}, {
      value: selectControlProps.value === undefined ? undefined : String(selectControlProps.value)
    });
  }
  var isDirty = (0,react.useMemo)(function () {
    return !isEqual_default()(initialValues.current, values);
  }, [initialValues.current, values]);
  var getStateAndHelpers = function getStateAndHelpers() {
    return {
      values: values,
      errors: errors,
      touched: touched,
      isDirty: isDirty,
      setTouched: setTouched,
      setValue: setValue,
      setValues: setValues,
      handleSubmit: handleSubmit,
      getCheckboxControlProps: getCheckboxControlProps,
      getInputProps: getInputProps,
      getSelectControlProps: getSelectControlProps,
      isValidForm: !Object.keys(errors).length,
      resetForm: resetForm
    };
  };
  function getChildren() {
    if (typeof children === 'function') {
      var element = children(getStateAndHelpers());
      return (0,react.cloneElement)(element);
    }
    return children;
  }
  return (0,react.createElement)(FormContext.Provider, {
    value: getStateAndHelpers()
  }, getChildren());
}
var Form = (0,react.forwardRef)(FormComponent);


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

/***/ "../../packages/js/components/src/form/stories/form.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/radio-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../packages/js/components/src/form/form.tsx");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../packages/js/components/src/date-time-picker-control/date-time-picker-control.tsx");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_4__);







/**
 * External dependencies
 */




var validate = function validate(values) {
  var errors = {};
  if (!values.firstName) {
    errors.firstName = 'First name is required';
  }
  if (values.lastName.length < 3) {
    errors.lastName = 'Last name must be at least 3 characters';
  }
  if (!moment__WEBPACK_IMPORTED_MODULE_4___default()(values.date, (moment__WEBPACK_IMPORTED_MODULE_4___default().ISO_8601), true).isValid()) {
    errors.date = 'Invalid date';
  }
  return errors;
};

// eslint-disable-next-line no-console
var onSubmit = function onSubmit(values) {
  return console.log(values);
};
var initialValues = {
  firstName: '',
  lastName: '',
  select: '3',
  date: '2014-10-24T13:02',
  checkbox: true,
  radio: 'one'
};
var Basic = function Basic() {
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useState)({}),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)(_useState, 2),
    onChangeValues = _useState2[0],
    setOnChangeValues = _useState2[1];
  var handleChange = function handleChange(change, newValues) {
    setOnChangeValues(newValues);
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__/* .Form */ .l, {
    validate: validate,
    onSubmit: onSubmit,
    onChange: handleChange,
    initialValues: initialValues
  }, function (_ref) {
    var getInputProps = _ref.getInputProps,
      values = _ref.values,
      errors = _ref.errors,
      handleSubmit = _ref.handleSubmit;
    var radioInputProps = getInputProps('radio');
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)({
      label: 'First Name'
    }, getInputProps('firstName'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)({
      label: 'Last Name'
    }, getInputProps('lastName'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)({
      label: "Select",
      options: [{
        label: 'Option 1',
        value: '1'
      }, {
        label: 'Option 2',
        value: '2'
      }, {
        label: 'Option 3',
        value: '3'
      }]
    }, getInputProps('select'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__/* .DateTimePickerControl */ .hD, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)({
      label: "Date",
      dateTimeFormat: "YYYY-MM-DD HH:mm",
      placeholder: "Enter a date",
      currentDate: values.date
    }, getInputProps('date'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)({
      label: "Checkbox"
    }, getInputProps('checkbox'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A, {
      label: "Radio",
      onChange: radioInputProps.onChange,
      selected: radioInputProps.value,
      options: [{
        label: 'Option 1',
        value: 'one'
      }, {
        label: 'Option 2',
        value: 'two'
      }, {
        label: 'Option 3',
        value: 'three'
      }]
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A, {
      isPrimary: true,
      onClick: handleSubmit,
      disabled: Object.keys(errors).length
    }, "Submit"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("h3", null, "Return data:"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("pre", null, "Values: ", JSON.stringify(values), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("br", null), "Errors: ", JSON.stringify(errors)));
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)("pre", null, "onChange values: ", JSON.stringify(onChangeValues))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/components/Form',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__/* .Form */ .l
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [onChangeValues, setOnChangeValues] = useState({});\n  const handleChange = (change, newValues) => {\n    setOnChangeValues(newValues);\n  };\n  return <div>\n            <Form validate={validate} onSubmit={onSubmit} onChange={handleChange} initialValues={initialValues}>\n                {({\n        getInputProps,\n        values,\n        errors,\n        handleSubmit\n      }) => {\n        const radioInputProps = getInputProps('radio');\n        return <div>\n                            <TextControl label={'First Name'} {...getInputProps('firstName')} />\n                            <TextControl label={'Last Name'} {...getInputProps('lastName')} />\n                            <SelectControl label=\"Select\" options={[{\n            label: 'Option 1',\n            value: '1'\n          }, {\n            label: 'Option 2',\n            value: '2'\n          }, {\n            label: 'Option 3',\n            value: '3'\n          }]} {...getInputProps('select')} />\n                            <DateTimePickerControl label=\"Date\" dateTimeFormat=\"YYYY-MM-DD HH:mm\" placeholder=\"Enter a date\" currentDate={values.date} {...getInputProps('date')} />\n                            <CheckboxControl label=\"Checkbox\" {...getInputProps('checkbox')} />\n                            <RadioControl label=\"Radio\" onChange={radioInputProps.onChange} selected={radioInputProps.value} options={[{\n            label: 'Option 1',\n            value: 'one'\n          }, {\n            label: 'Option 2',\n            value: 'two'\n          }, {\n            label: 'Option 3',\n            value: 'three'\n          }]} />\n                            <Button isPrimary onClick={handleSubmit} disabled={Object.keys(errors).length}>\n                                Submit\n                            </Button>\n                            <br />\n                            <br />\n                            <h3>Return data:</h3>\n                            <pre>\n                                Values: {JSON.stringify(values)}\n                                <br />\n                                Errors: {JSON.stringify(errors)}\n                            </pre>\n                        </div>;\n      }}\n            </Form>\n            <div>\n                <pre>onChange values: {JSON.stringify(onChangeValues)}</pre>\n            </div>\n        </div>;\n}",
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