(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4832],{

/***/ "../../packages/js/components/src/form/stories/form.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/radio-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../packages/js/components/src/form/form.tsx");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../packages/js/components/src/date-time-picker-control/date-time-picker-control.tsx");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





const validate = values => {
  const errors = {};
  if (!values.firstName) {
    errors.firstName = 'First name is required';
  }
  if (values.lastName.length < 3) {
    errors.lastName = 'Last name must be at least 3 characters';
  }
  if (!moment__WEBPACK_IMPORTED_MODULE_0___default()(values.date, (moment__WEBPACK_IMPORTED_MODULE_0___default().ISO_8601), true).isValid()) {
    errors.date = 'Invalid date';
  }
  return errors;
};

// eslint-disable-next-line no-console
const onSubmit = values => console.log(values);
const initialValues = {
  firstName: '',
  lastName: '',
  select: '3',
  date: '2014-10-24T13:02',
  checkbox: true,
  radio: 'one'
};
const Basic = () => {
  const [onChangeValues, setOnChangeValues] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)({});
  const handleChange = (change, newValues) => {
    setOnChangeValues(newValues);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__/* .Form */ .l, {
      validate: validate,
      onSubmit: onSubmit,
      onChange: handleChange,
      initialValues: initialValues,
      children: ({
        getInputProps,
        values,
        errors,
        handleSubmit
      }) => {
        const radioInputProps = getInputProps('radio');
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
            label: 'First Name',
            ...getInputProps('firstName')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
            label: 'Last Name',
            ...getInputProps('lastName')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
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
            }],
            ...getInputProps('select')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__/* .DateTimePickerControl */ .hD, {
            label: "Date",
            dateTimeFormat: "YYYY-MM-DD HH:mm",
            placeholder: "Enter a date",
            currentDate: values.date,
            ...getInputProps('date')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
            label: "Checkbox",
            ...getInputProps('checkbox')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
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
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .Ay, {
            isPrimary: true,
            onClick: handleSubmit,
            disabled: Object.keys(errors).length,
            children: "Submit"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h3", {
            children: "Return data:"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("pre", {
            children: ["Values: ", JSON.stringify(values), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("br", {}), "Errors: ", JSON.stringify(errors)]
          })]
        });
      }
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("pre", {
        children: ["onChange values: ", JSON.stringify(onChangeValues)]
      })
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/Form',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__/* .Form */ .l
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

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/radio-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ radio_control_default)
/* harmony export */ });
/* unused harmony export RadioControl */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _v_stack__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/v-stack/component.js");
/* harmony import */ var _base_control_styles_base_control_styles__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/styles/base-control-styles.js");
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");







function generateOptionDescriptionId(radioGroupId, index) {
  return `${radioGroupId}-${index}-option-description`;
}
function generateOptionId(radioGroupId, index) {
  return `${radioGroupId}-${index}`;
}
function generateHelpId(radioGroupId) {
  return `${radioGroupId}__help`;
}
function RadioControl(props) {
  const {
    label,
    className,
    selected,
    help,
    onChange,
    onClick,
    hideLabelFromVision,
    options = [],
    id: preferredId,
    ...additionalProps
  } = props;
  const id = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(RadioControl, "inspector-radio-control", preferredId);
  const onChangeValue = (event) => onChange(event.target.value);
  if (!options?.length) {
    return null;
  }
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("fieldset", {
    id,
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, "components-radio-control"),
    "aria-describedby": !!help ? generateHelpId(id) : void 0,
    children: [hideLabelFromVision ? /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_visually_hidden__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
      as: "legend",
      children: label
    }) : /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_base_control__WEBPACK_IMPORTED_MODULE_4__/* ["default"].VisualLabel */ .Ay.VisualLabel, {
      as: "legend",
      children: label
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_v_stack__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
      spacing: 3,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)("components-radio-control__group-wrapper", {
        "has-help": !!help
      }),
      children: options.map((option, index) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: "components-radio-control__option",
        children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
          id: generateOptionId(id, index),
          className: "components-radio-control__input",
          type: "radio",
          name: id,
          value: option.value,
          onChange: onChangeValue,
          checked: option.value === selected,
          "aria-describedby": !!option.description ? generateOptionDescriptionId(id, index) : void 0,
          onClick: (event) => {
            event.currentTarget.focus();
            onClick?.(event);
          },
          ...additionalProps
        }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("label", {
          className: "components-radio-control__label",
          htmlFor: generateOptionId(id, index),
          children: option.label
        }), !!option.description ? /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_base_control_styles_base_control_styles__WEBPACK_IMPORTED_MODULE_6__/* .StyledHelp */ .te, {
          __nextHasNoMarginBottom: true,
          id: generateOptionDescriptionId(id, index),
          className: "components-radio-control__option-description",
          children: option.description
        }) : null]
      }, generateOptionId(id, index)))
    }), !!help && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_base_control_styles_base_control_styles__WEBPACK_IMPORTED_MODULE_6__/* .StyledHelp */ .te, {
      __nextHasNoMarginBottom: true,
      id: generateHelpId(id),
      className: "components-base-control__help",
      children: help
    })]
  });
}
var radio_control_default = RadioControl;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../packages/js/components/src/date-time-picker-control/date-time-picker-control.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   hD: () => (/* binding */ DateTimePickerControl)
/* harmony export */ });
/* unused harmony exports defaultDateFormat, default12HourDateTimeFormat, default24HourDateTimeFormat */
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+date@5.33.1/node_modules/@wordpress/date/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/calendar.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */










// PHP style formatting:
// https://wordpress.org/support/article/formatting-date-and-time/

const defaultDateFormat = 'm/d/Y';
const default12HourDateTimeFormat = 'm/d/Y h:i a';
const default24HourDateTimeFormat = 'm/d/Y H:i';
const MINUTE_IN_SECONDS = 60;
const HOUR_IN_MINUTES = 60;
const HOUR_IN_SECONDS = 60 * MINUTE_IN_SECONDS;

/**
 * Map of PHP formats to Moment.js formats.
 *
 * Copied from @wordpress/date, since it's not exposed. If this is exposed upstream,
 * it should ideally be used from there.
 */
const formatMap = {
  // Day.
  d: 'DD',
  D: 'ddd',
  j: 'D',
  l: 'dddd',
  N: 'E',
  S(momentDate) {
    // Do - D.
    const num = momentDate.format('D');
    const withOrdinal = momentDate.format('Do');
    return withOrdinal.replace(num, '');
  },
  w: 'd',
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
  t(momentDate) {
    return momentDate.daysInMonth();
  },
  L(momentDate) {
    return momentDate.isLeapYear() ? '1' : '0';
  },
  o: 'GGGG',
  Y: 'YYYY',
  y: 'YY',
  // Time.
  a: 'a',
  A: 'A',
  B(momentDate) {
    const timezoned = moment__WEBPACK_IMPORTED_MODULE_1___default()(momentDate).utcOffset(60);
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
  I(momentDate) {
    return momentDate.isDST() ? '1' : '0';
  },
  O: 'ZZ',
  P: 'Z',
  T: 'z',
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
  r(momentDate) {
    return momentDate.locale('en').format('ddd, DD MMM YYYY HH:mm:ss ZZ');
  },
  U: 'X'
};

/**
 * A modified version of the `format` function from @wordpress/date.
 * This is needed to create a date object from the typed string and the date format,
 * that needs to be mapped from the PHP format to moment's format.
 */
const createMomentDate = (dateFormat, date) => {
  let i, char;
  const newFormat = [];
  for (i = 0; i < dateFormat.length; i++) {
    char = dateFormat[i];
    // Is this an escape?
    if (char === '\\') {
      // Add next character, then move on.
      i++;
      newFormat.push('[' + dateFormat[i] + ']');
      continue;
    }
    if (char in formatMap) {
      const formatter = formatMap[char];
      if (typeof formatter !== 'string') {
        // If the format is a function, call it.
        newFormat.push('[' + formatter(moment__WEBPACK_IMPORTED_MODULE_1___default()(date)) + ']');
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
  return moment__WEBPACK_IMPORTED_MODULE_1___default()(date, newFormat.join('[]'));
};
const DateTimePickerControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.forwardRef)(function ForwardedDateTimePickerControl({
  currentDate,
  isDateOnlyPicker = false,
  is12HourPicker = true,
  timeForDateOnly = 'start-of-day',
  dateTimeFormat,
  disabled = false,
  onChange,
  onBlur,
  label,
  placeholder,
  help,
  className = '',
  onChangeDebounceWait = 500,
  popoverProps = {},
  ...props
}, ref) {
  const id = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)(DateTimePickerControl, 'inspector-date-time-picker-control', props.id);
  const inputControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useRef)();
  const displayFormat = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useMemo)(() => {
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
  function parseAsISODateTime(dateString, assumeLocalTime = false) {
    return assumeLocalTime ? moment__WEBPACK_IMPORTED_MODULE_1___default()(dateString, (moment__WEBPACK_IMPORTED_MODULE_1___default().ISO_8601), true).utc() : moment__WEBPACK_IMPORTED_MODULE_1___default().utc(dateString, (moment__WEBPACK_IMPORTED_MODULE_1___default().ISO_8601), true);
  }
  function parseAsLocalDateTime(dateString) {
    // parse input date string as local time;
    // be lenient of user input and try to match any format Moment can
    return dateTimeFormat && dateString ? createMomentDate(dateTimeFormat, dateString) : moment__WEBPACK_IMPORTED_MODULE_1___default()(dateString);
  }
  const maybeForceTime = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useCallback)(momentDate => {
    if (!isDateOnlyPicker || !momentDate.isValid()) return momentDate;

    // We want to set to the start/end of the local time, so
    // we need to put our Moment instance into "local" mode
    const updatedMomentDate = momentDate.clone().local();
    if (timeForDateOnly === 'start-of-day') {
      updatedMomentDate.startOf('day');
    } else if (timeForDateOnly === 'end-of-day') {
      updatedMomentDate.endOf('day');
    }
    return updatedMomentDate;
  }, [isDateOnlyPicker, timeForDateOnly]);
  function hasFocusLeftInputAndDropdownContent(event) {
    return !event.relatedTarget?.closest('.components-dropdown__content');
  }
  const formatDateTimeForDisplay = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useCallback)(dateTime => {
    return dateTime.isValid() ? (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_0__.format)(displayFormat, dateTime.local()) : dateTime.creationData().input?.toString() || '';
  }, [displayFormat]);
  function formatDateTimeAsISO(dateTime) {
    return dateTime.isValid() ? dateTime.utc().toISOString() : dateTime.creationData().input?.toString() || '';
  }
  const currentDateTime = parseAsISODateTime(currentDate);
  const [inputString, setInputString] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(currentDateTime.isValid() ? formatDateTimeForDisplay(maybeForceTime(currentDateTime)) : '');
  const inputStringDateTime = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useMemo)(() => {
    return maybeForceTime(parseAsLocalDateTime(inputString));
  }, [inputString, maybeForceTime]);

  // We keep a ref to the onChange prop so that we can be sure we are
  // always using the more up-to-date value, even if it changes
  // it while a debounced onChange handler is in progress
  const onChangeRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useRef)();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    onChangeRef.current = onChange;
  }, [onChange]);
  const setInputStringAndMaybeCallOnChange = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useCallback)((newInputString, isUserTypedInput) => {
    // InputControl doesn't fire an onChange if what the user has typed
    // matches the current value of the input field. To get around this,
    // we pull the value directly out of the input field. This fixes
    // the issue where the user ends up typing the same value. Unless they
    // are typing extra slow. Without this workaround, we miss the last
    // character typed.
    const lastTypedValue = inputControl.current?.value ?? '';
    const newDateTime = maybeForceTime(isUserTypedInput ? parseAsLocalDateTime(lastTypedValue) : parseAsISODateTime(newInputString, true));
    const isDateTimeSame = newDateTime.isSame(inputStringDateTime);
    if (isUserTypedInput) {
      setInputString(lastTypedValue);
    } else if (!isDateTimeSame) {
      setInputString(formatDateTimeForDisplay(newDateTime));
    }
    if (typeof onChangeRef.current === 'function' && !isDateTimeSame) {
      onChangeRef.current(newDateTime.isValid() ? formatDateTimeAsISO(newDateTime) : lastTypedValue, newDateTime.isValid());
    }
  }, [formatDateTimeForDisplay, inputStringDateTime, maybeForceTime]);
  const debouncedSetInputStringAndMaybeCallOnChange = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)(setInputStringAndMaybeCallOnChange, onChangeDebounceWait);
  function focusInputControl() {
    if (inputControl.current) {
      inputControl.current.focus();
    }
  }
  const getUserInputOrUpdatedCurrentDate = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useCallback)(() => {
    if (currentDate !== undefined) {
      const newDateTime = maybeForceTime(parseAsISODateTime(currentDate, false));
      if (!newDateTime.isValid()) {
        // keep the invalid string, so the user can correct it
        return currentDate ?? '';
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
  const onBlurRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useRef)();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    onBlurRef.current = onBlur;
  }, [onBlur]);
  const callOnBlurIfDropdownIsNotOpening = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useCallback)(willOpen => {
    if (!willOpen && typeof onBlurRef.current === 'function' && inputControl.current) {
      // in case the component is blurred before a debounced
      // change has been processed, immediately set the input string
      // to the current value of the input field, so that
      // it won't be set back to the pre-change value
      setInputStringAndMaybeCallOnChange(inputControl.current.value, true);
      onBlurRef.current();
    }
  }, []);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)('woocommerce-date-time-picker-control', className),
    focusOnMount: false,
    onToggle: callOnBlurIfDropdownIsNotOpening,
    renderToggle: ({
      isOpen,
      onClose,
      onToggle
    }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .Ay, {
      id: id,
      label: label,
      help: help,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .Ay, {
        ...props,
        id: id,
        ref: element => {
          inputControl.current = element;
          if (typeof ref === 'function') {
            ref(element);
          }
        },
        disabled: disabled,
        value: getUserInputOrUpdatedCurrentDate(),
        onChange: newValue => debouncedSetInputStringAndMaybeCallOnChange(newValue ?? '', true),
        onBlur: event => {
          if (hasFocusLeftInputAndDropdownContent(event)) {
            // close the dropdown, which will also trigger
            // the component's onBlur to be called
            onClose();
          }
        },
        suffix: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A, {
          icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A,
          className: "calendar-icon woocommerce-date-time-picker-control__input-control__suffix",
          onClick: focusInputControl,
          size: 16
        }),
        placeholder: placeholder,
        "aria-describedby": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__/* .sprintf */ .nv)(/* translators: A datetime format */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Date input describing a selected date in format %s', 'woocommerce'), dateTimeFormat ?? ''),
        onFocus: () => {
          if (isOpen) {
            return; // the dropdown is already open, do we don't need to do anything
          }
          onToggle(); // show the dropdown
        },
        "aria-expanded": isOpen
      })
    }),
    popoverProps: {
      anchor: inputControl.current,
      className: 'woocommerce-date-time-picker-control__popover',
      placement: 'bottom-start',
      ...popoverProps
    },
    renderContent: () => {
      const Picker = isDateOnlyPicker ? _wordpress_components__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A : _wordpress_components__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .Ay;
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(Picker, {
        currentDate: inputStringDateTime.isValid() ? formatDateTimeAsISO(inputStringDateTime) : null,
        onChange: newDateTimeISOString => setInputStringAndMaybeCallOnChange(newDateTimeISOString, false),
        is12Hour: is12HourPicker
      });
    }
  });
});
try {
    // @ts-ignore
    DateTimePickerControl.displayName = "DateTimePickerControl";
    // @ts-ignore
    DateTimePickerControl.__docgenInfo = { "description": "", "displayName": "DateTimePickerControl", "props": { "onBlur": { "defaultValue": null, "description": "", "name": "onBlur", "required": false, "type": { "name": "((() => void) & FocusEventHandler<HTMLInputElement>)" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": false, "type": { "name": "DateTimePickerControlOnChangeHandler" } }, "label": { "defaultValue": null, "description": "If this property is added, a label will be generated using label property as the content.", "name": "label", "required": false, "type": { "name": "string | (string & ReactElement<any, string | JSXElementConstructor<any>>) | (string & Iterable<ReactNode>) | (string & ReactPortal)" } }, "disabled": { "defaultValue": { value: "false" }, "description": "If true, the `input` will be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "help": { "defaultValue": null, "description": "Additional description for the control.\n\nOnly use for meaningful description or instructions for the control. An element containing the description will be programmatically associated to the BaseControl by the means of an `aria-describedby` attribute.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "currentDate": { "defaultValue": null, "description": "", "name": "currentDate", "required": false, "type": { "name": "string | null" } }, "isDateOnlyPicker": { "defaultValue": { value: "false" }, "description": "", "name": "isDateOnlyPicker", "required": false, "type": { "name": "boolean" } }, "is12HourPicker": { "defaultValue": { value: "true" }, "description": "", "name": "is12HourPicker", "required": false, "type": { "name": "boolean" } }, "timeForDateOnly": { "defaultValue": { value: "start-of-day" }, "description": "", "name": "timeForDateOnly", "required": false, "type": { "name": "enum", "value": [{ "value": "\"start-of-day\"" }, { "value": "\"end-of-day\"" }] } }, "dateTimeFormat": { "defaultValue": null, "description": "", "name": "dateTimeFormat", "required": false, "type": { "name": "string" } }, "onChangeDebounceWait": { "defaultValue": { value: "500" }, "description": "", "name": "onChangeDebounceWait", "required": false, "type": { "name": "number" } }, "popoverProps": { "defaultValue": { value: "{}" }, "description": "", "name": "popoverProps", "required": false, "type": { "name": "Record<string, string | boolean>" } } } };
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

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/setWith.js
var setWith = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/setWith.js");
var setWith_default = /*#__PURE__*/__webpack_require__.n(setWith);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/get.js
var get = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/get.js");
var get_default = /*#__PURE__*/__webpack_require__.n(get);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/clone.js
var clone = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/clone.js");
var clone_default = /*#__PURE__*/__webpack_require__.n(clone);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/toPath.js
var toPath = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/toPath.js");
var toPath_default = /*#__PURE__*/__webpack_require__.n(toPath);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/isEqual.js
var isEqual = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/isEqual.js");
var isEqual_default = /*#__PURE__*/__webpack_require__.n(isEqual);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/omit.js
var omit = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/omit.js");
var omit_default = /*#__PURE__*/__webpack_require__.n(omit);
;// ../../packages/js/components/src/form/form-context.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

const FormContext = (0,react.createContext)({});

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function useFormContext() {
  const formContext = useContext(FormContext);
  return formContext;
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/form/form.tsx
/**
 * External dependencies
 */











/**
 * Internal dependencies
 */


function isChangeEvent(value) {
  return value.target !== undefined;
}

// Path segments lodash refuses to write through.
const UNWRITABLE_KEYS = ['__proto__', 'constructor', 'prototype'];

/**
 * A form component to handle form state and provide input helper props.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FormComponent({
  children,
  onSubmit = () => {},
  // Keep these defaults inline: setValues depends on them, so hoisting them
  // to module constants would make setValue/setValues referentially stable
  // for consumers that omit the props and change when dependent effects run.
  onChange = () => {},
  onChanges = () => {},
  ...props
}, ref) {
  const initialValues = (0,react.useRef)(props.initialValues ?? {});
  // The latest logical values, advanced synchronously on every write so
  // same-stack writes build on each other instead of on the last render.
  const pendingValuesRef = (0,react.useRef)(initialValues.current);
  const [values, setValuesInternal] = (0,react.useState)(initialValues.current);
  const [errors, setErrors] = (0,react.useState)(props.errors || {});
  const [touched, setTouched] = (0,react.useState)(props.touched || {});
  const validate = (0,react.useCallback)((newValues, onValidate = () => {}) => {
    const newErrors = props.validate ? props.validate(newValues) : {};
    setErrors(newErrors || {});
    onValidate(newErrors);
  }, [props.validate]);
  (0,react.useEffect)(() => {
    validate(values);
  }, []);
  const resetForm = (newInitialValues, newTouchedFields = {}, newErrors = {}) => {
    const newValues = newInitialValues ?? initialValues.current ?? {};
    initialValues.current = newValues;
    pendingValuesRef.current = newValues;
    setValuesInternal(newValues);
    setTouched(newTouchedFields);
    setErrors(newErrors);
  };
  (0,react.useImperativeHandle)(ref, () => ({
    resetForm
  }));
  const isValidForm = async () => {
    validate(values);
    return !Object.keys(errors).length;
  };
  const setValues = (0,react.useCallback)(valuesToSet => {
    const newValues = {
      ...pendingValuesRef.current,
      ...valuesToSet
    };
    pendingValuesRef.current = newValues;
    setValuesInternal(newValues);
    validate(newValues, newErrors => {
      const {
        onChangeCallback
      } = props;

      // Note that onChange is a no-op by default so this will never be null
      const singleValueChangeCallback = onChangeCallback || onChange;
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

      const isValid = !Object.keys(newErrors || {}).length;
      const nameValuePairs = [];
      // Report the keys the merge above actually took, which is the
      // own enumerable ones. A `for...in` here would also walk the
      // prototype chain and report fields the form never stored.
      // The `|| {}` leaves a nullish patch a no-op, which is what
      // the spread above and the previous `for...in` both did.
      for (const key of Object.keys(valuesToSet || {})) {
        const nameValuePair = {
          name: key,
          value: valuesToSet[key]
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
  }, [validate, onChange, onChanges, props.onChangeCallback]);
  const setValue = (0,react.useCallback)(
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  (name, value) => {
    // lodash writes an existing literal key such as 'a.b' in place rather
    // than as a path, so only split a name the form does not already hold.
    const path = Object.prototype.hasOwnProperty.call(pendingValuesRef.current, name) ? [String(name)] : toPath_default()(name);

    // toPath() yields no segments for a name such as '' or null. Write
    // those under the literal key instead, and hand setWith() the same
    // segments so the entry read below is the one the write landed on.
    const segments = path.length ? path : [String(name)];

    // lodash drops a write whose path steps through one of these keys.
    // Drop it here too: otherwise the entry picked below reads an
    // inherited value and setValues adds it to the form as an own key.
    if (segments.some(segment => UNWRITABLE_KEYS.includes(segment))) {
      return;
    }
    const newValues = setWith_default()({
      ...pendingValuesRef.current
    }, segments, value, (clone_default()));
    // Hand setValues only the entry this write touched so it reports one
    // change: a literal key is its own only segment, and a path reports
    // under its top-level key.
    const key = segments[0];
    setValues({
      [key]: newValues[key]
    });
  }, [setValues]);
  const handleChange = (0,react.useCallback)((name, value) => {
    // Handle native events.
    if (isChangeEvent(value) && value.target) {
      if (value.target.type === 'checkbox') {
        setValue(name, !get_default()(pendingValuesRef.current, name));
      } else {
        setValue(name, value.target.value);
      }
    } else {
      setValue(name, value);
    }
  }, [setValue]);
  const handleBlur = (0,react.useCallback)(name => {
    setTouched({
      ...touched,
      [name]: true
    });
  }, [touched]);
  const handleSubmit = async () => {
    const {
      onSubmitCallback
    } = props;
    const touchedFields = {};
    Object.keys(values).map(name => touchedFields[name] = true);
    setTouched(touchedFields);
    if (await isValidForm()) {
      // Note that onSubmit is a no-op by default so this will never be null
      const callback = onSubmitCallback || onSubmit;
      if (onSubmitCallback) {
        (0,build_module/* default */.A)('onSubmitCallback', {
          version: '9.0.0',
          alternative: 'onSubmit',
          plugin: '@woocommerce/components'
        });
      }
      if (callback) {
        return callback(values);
      }
    }
  };
  function getInputProps(name, inputProps = {}) {
    const inputValue = get_default()(values, name);
    const isTouched = touched[name];
    const inputError = get_default()(errors, name);
    const {
      className: classNameProp,
      onBlur: onBlurProp,
      onChange: onChangeProp,
      sanitize,
      ...additionalProps
    } = inputProps;
    return {
      value: inputValue,
      checked: Boolean(inputValue),
      selected: inputValue,
      onChange: value => {
        handleChange(name, value);
        if (onChangeProp) {
          onChangeProp(value);
        }
      },
      onBlur: () => {
        if (sanitize) {
          handleChange(name, sanitize(inputValue));
        }
        handleBlur(name);
        if (onBlurProp) {
          onBlurProp();
        }
      },
      className: (0,clsx/* default */.A)(classNameProp, {
        'has-error': isTouched && inputError
      }),
      help: isTouched ? inputError : null,
      ...additionalProps
    };
  }
  function getCheckboxControlProps(name, inputProps = {}) {
    return omit_default()(getInputProps(name, inputProps), ['selected', 'value']);
  }
  function getSelectControlProps(name, inputProps = {}) {
    const selectControlProps = getInputProps(name, inputProps);
    return {
      ...selectControlProps,
      value: selectControlProps.value === undefined ? undefined : String(selectControlProps.value)
    };
  }
  const isDirty = (0,react.useMemo)(() => !isEqual_default()(initialValues.current, values), [initialValues.current, values]);
  const getStateAndHelpers = () => {
    return {
      values,
      errors,
      touched,
      isDirty,
      setTouched,
      setValue,
      setValues,
      handleSubmit,
      getCheckboxControlProps,
      getInputProps,
      getSelectControlProps,
      isValidForm: !Object.keys(errors).length,
      resetForm
    };
  };
  function getChildren() {
    if (typeof children === 'function') {
      return children(getStateAndHelpers());
    }
    return children;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(FormContext.Provider, {
    value: getStateAndHelpers(),
    children: getChildren()
  });
}
const Form = (0,react.forwardRef)(FormComponent);


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