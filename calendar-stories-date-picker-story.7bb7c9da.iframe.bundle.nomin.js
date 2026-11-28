(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3381],{

/***/ "../../packages/js/components/src/calendar/stories/date-picker.story.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/moment@2.31.0/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/section.tsx");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/header.tsx");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../packages/js/components/src/calendar/date-picker.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const dateFormat = 'MM/DD/YYYY';
const DatePickerExample = () => {
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)({
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
    afterError
  } = state;
  function onDatePickerUpdate({
    date,
    text,
    error
  }) {
    setState({
      ...state,
      after: date,
      afterText: text,
      afterError: error
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsxs)("div", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__.H, {
      children: "Date Picker"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__/* .Section */ .w, {
      component: false,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
        date: after,
        text: afterText,
        error: afterError,
        onUpdate: onDatePickerUpdate,
        dateFormat: dateFormat,
        isInvalidDate: date => moment__WEBPACK_IMPORTED_MODULE_0___default()(date).day() === 1
      })
    })]
  });
};
const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(DatePickerExample, {});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/calendar/DatePicker',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <DatePickerExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};
/* harmony export */ __webpack_require__.d(__webpack_exports__, [
/* harmony export */   "Basic", 0, /* binding */ Basic,
/* harmony export */   "default", 0, __WEBPACK_DEFAULT_EXPORT__
/* harmony export */ ]);


/***/ },

/***/ "../../packages/js/components/src/calendar/date-picker.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/date-time/date/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/moment@2.31.0/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../packages/js/date/src/index.ts");
/* harmony import */ var _input__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../packages/js/components/src/calendar/input.js");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../packages/js/components/src/section/section.tsx");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../packages/js/components/src/section/header.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */








/**
 * Internal dependencies
 */



class DatePicker extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  constructor(props) {
    super(props);
    this.onDateChange = this.onDateChange.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
  }
  handleFocus(isOpen, onToggle) {
    if (!isOpen) {
      onToggle();
    }
  }
  handleBlur(isOpen, onToggle, event) {
    if (!isOpen) {
      return;
    }
    const relatedTargetParent = event.relatedTarget?.closest('.components-dropdown');
    const currentTargetParent = event.currentTarget?.closest('.components-dropdown');
    if (!relatedTargetParent || relatedTargetParent !== currentTargetParent) {
      onToggle();
    }
  }
  onDateChange(onToggle, dateString) {
    const {
      onUpdate,
      dateFormat
    } = this.props;
    const date = moment__WEBPACK_IMPORTED_MODULE_5___default()(dateString);
    onUpdate({
      date,
      text: dateString ? date.format(dateFormat) : '',
      error: null
    });
    onToggle();
  }
  onInputChange(event) {
    const value = event.target.value;
    const {
      dateFormat
    } = this.props;
    const date = (0,_woocommerce_date__WEBPACK_IMPORTED_MODULE_6__/* .toMoment */ .sf)(dateFormat, value);
    const error = date ? null : _woocommerce_date__WEBPACK_IMPORTED_MODULE_6__/* .dateValidationMessages */ .Y6.invalid;
    this.props.onUpdate({
      date,
      text: value,
      error: value.length > 0 ? error : null
    });
  }
  render() {
    const {
      date,
      disabled,
      text,
      dateFormat,
      error,
      isInvalidDate,
      popoverProps = {
        inline: true
      }
    } = this.props;
    if (!popoverProps.placement) {
      popoverProps.placement = 'bottom';
    }
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
      focusOnMount: false,
      popoverProps: popoverProps,
      renderToggle: ({
        isOpen,
        onToggle
      }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_input__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
        disabled: disabled,
        value: text,
        onChange: this.onInputChange,
        onBlur: (0,lodash__WEBPACK_IMPORTED_MODULE_4__.partial)(this.handleBlur, isOpen, onToggle),
        dateFormat: dateFormat,
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Choose a date', 'woocommerce'),
        error: error,
        describedBy: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %s: date format specification */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Date input describing a selected date in format %s', 'woocommerce'), dateFormat),
        onFocus: (0,lodash__WEBPACK_IMPORTED_MODULE_4__.partial)(this.handleFocus, isOpen, onToggle),
        "aria-expanded": isOpen,
        focusOnMount: false,
        errorPosition: "top center"
      }),
      renderContent: ({
        onToggle
      }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsxs)(_section__WEBPACK_IMPORTED_MODULE_8__/* .Section */ .w, {
        component: false,
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_section__WEBPACK_IMPORTED_MODULE_9__.H, {
          className: "woocommerce-calendar__date-picker-title",
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('select a date', 'woocommerce')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)("div", {
          className: "woocommerce-calendar__react-dates is-core-datepicker",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_10__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
            currentDate: date instanceof (moment__WEBPACK_IMPORTED_MODULE_5___default()) ? date.toDate() : date,
            onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_4__.partial)(this.onDateChange, onToggle)
            // onMonthPreviewed is required to prevent a React error from happening.
            ,
            onMonthPreviewed: lodash__WEBPACK_IMPORTED_MODULE_4__.noop,
            isInvalidDate: isInvalidDate
          })
        })]
      })
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DatePicker);
;
DatePicker.__docgenInfo = {
  "description": "",
  "methods": [{
    "name": "handleFocus",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "isOpen",
      "optional": false,
      "type": null
    }, {
      "name": "onToggle",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "handleBlur",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "isOpen",
      "optional": false,
      "type": null
    }, {
      "name": "onToggle",
      "optional": false,
      "type": null
    }, {
      "name": "event",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onDateChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "onToggle",
      "optional": false,
      "type": null
    }, {
      "name": "dateString",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onInputChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "event",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "DatePicker",
  "props": {
    "date": {
      "description": "A moment date object representing the selected date. `null` for no selection.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "disabled": {
      "description": "Whether the input is disabled.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "text": {
      "description": "The date in human-readable format. Displayed in the text input.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "error": {
      "description": "A string error message, shown to the user.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onUpdate": {
      "description": "A function called upon selection of a date or input change.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "dateFormat": {
      "description": "The date format in moment.js-style tokens.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "isInvalidDate": {
      "description": "A function to determine if a day on the calendar is not valid",
      "type": {
        "name": "func"
      },
      "required": false
    }
  }
};
/* harmony export */ __webpack_require__.d(__webpack_exports__, [
/* harmony export */   "A", 0, __WEBPACK_DEFAULT_EXPORT__
/* harmony export */ ]);


/***/ },

/***/ "?9f28"
() {

/* (ignored) */

/***/ }

}]);