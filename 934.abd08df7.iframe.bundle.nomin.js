"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[934],{

/***/ "../../packages/js/components/src/calendar/date-picker.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_features_object_assign__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/object/assign.js");
/* harmony import */ var core_js_features_object_assign__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_features_object_assign__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_features_array_from__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/array/from.js");
/* harmony import */ var core_js_features_array_from__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_features_array_from__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../packages/js/date/src/index.ts");
/* harmony import */ var _input__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../packages/js/components/src/calendar/input.js");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../packages/js/components/src/section/section.tsx");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../packages/js/components/src/section/header.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */










/**
 * Internal dependencies
 */



class DatePicker extends _wordpress_element__WEBPACK_IMPORTED_MODULE_7__.Component {
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
    const date = moment__WEBPACK_IMPORTED_MODULE_4___default()(dateString);
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
    const date = (0,_woocommerce_date__WEBPACK_IMPORTED_MODULE_5__/* .toMoment */ .sf)(dateFormat, value);
    const error = date ? null : _woocommerce_date__WEBPACK_IMPORTED_MODULE_5__/* .dateValidationMessages */ .Y6.invalid;
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
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
      focusOnMount: false,
      popoverProps: popoverProps,
      renderToggle: ({
        isOpen,
        onToggle
      }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_input__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A, {
        disabled: disabled,
        value: text,
        onChange: this.onInputChange,
        onBlur: (0,lodash__WEBPACK_IMPORTED_MODULE_3__.partial)(this.handleBlur, isOpen, onToggle),
        dateFormat: dateFormat,
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Choose a date', 'woocommerce'),
        error: error,
        describedBy: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__/* .sprintf */ .nv)(/* translators: %s: date format specification */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Date input describing a selected date in format %s', 'woocommerce'), dateFormat),
        onFocus: (0,lodash__WEBPACK_IMPORTED_MODULE_3__.partial)(this.handleFocus, isOpen, onToggle),
        "aria-expanded": isOpen,
        focusOnMount: false,
        errorPosition: "top center"
      }),
      renderContent: ({
        onToggle
      }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(_section__WEBPACK_IMPORTED_MODULE_10__/* .Section */ .w, {
        component: false,
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_section__WEBPACK_IMPORTED_MODULE_11__.H, {
          className: "woocommerce-calendar__date-picker-title",
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('select a date', 'woocommerce')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          className: "woocommerce-calendar__react-dates is-core-datepicker",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A, {
            currentDate: date instanceof (moment__WEBPACK_IMPORTED_MODULE_4___default()) ? date.toDate() : date,
            onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_3__.partial)(this.onDateChange, onToggle)
            // onMonthPreviewed is required to prevent a React error from happening.
            ,
            onMonthPreviewed: lodash__WEBPACK_IMPORTED_MODULE_3__.noop,
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

/***/ }),

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */


/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
const Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Text || _wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A;

/***/ }),

/***/ "../../packages/js/components/src/text-control-with-affixes/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */






/**
 * This component is essentially a wrapper (really a reimplementation) around the
 * TextControl component that adds support for affixes, i.e. the ability to display
 * a fixed part either at the beginning or at the end of the text input.
 */

class TextControlWithAffixes extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  constructor(props) {
    super(props);
    this.state = {
      isFocused: false
    };
  }
  handleFocusOutside() {
    this.setState({
      isFocused: false
    });
  }
  handleOnClick(event, onClick) {
    this.setState({
      isFocused: true
    });
    if (typeof onClick === 'function') {
      onClick(event);
    }
  }
  render() {
    const {
      label,
      value,
      help,
      className,
      instanceId,
      onChange,
      onClick,
      prefix,
      suffix,
      type,
      disabled,
      ...props
    } = this.props;
    const {
      isFocused
    } = this.state;
    const id = `inspector-text-control-with-affixes-${instanceId}`;
    const onChangeValue = event => onChange(event.target.value);
    const describedby = [];
    if (help) {
      describedby.push(`${id}__help`);
    }
    if (prefix) {
      describedby.push(`${id}__prefix`);
    }
    if (suffix) {
      describedby.push(`${id}__suffix`);
    }
    const baseControlClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, {
      'with-value': value !== '',
      empty: value === '',
      active: isFocused && !disabled
    });
    const affixesClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('text-control-with-affixes', {
      'text-control-with-prefix': prefix,
      'text-control-with-suffix': suffix,
      disabled
    });
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
      label: label,
      id: id,
      help: help,
      className: baseControlClasses,
      onClick: event => this.handleOnClick(event, onClick),
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: affixesClasses,
        children: [prefix && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
          id: `${id}__prefix`,
          className: "text-control-with-affixes__prefix",
          children: prefix
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
          className: "components-text-control__input",
          type: type,
          id: id,
          value: value,
          onChange: onChangeValue,
          "aria-describedby": describedby.join(' '),
          disabled: disabled,
          onFocus: () => this.setState({
            isFocused: true
          }),
          ...props
        }), suffix && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
          id: `${id}__suffix`,
          className: "text-control-with-affixes__suffix",
          children: suffix
        })]
      })
    });
  }
}
TextControlWithAffixes.defaultProps = {
  type: 'text'
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)([_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, _wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A // this MUST be the innermost HOC as it calls handleFocusOutside
])(TextControlWithAffixes));
;
TextControlWithAffixes.__docgenInfo = {
  "description": "This component is essentially a wrapper (really a reimplementation) around the\nTextControl component that adds support for affixes, i.e. the ability to display\na fixed part either at the beginning or at the end of the text input.",
  "methods": [{
    "name": "handleFocusOutside",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "handleOnClick",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "event",
      "optional": false,
      "type": null
    }, {
      "name": "onClick",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "TextControlWithAffixes",
  "props": {
    "type": {
      "defaultValue": {
        "value": "'text'",
        "computed": false
      },
      "description": "Type of the input element to render. Defaults to \"text\".",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "label": {
      "description": "If this property is added, a label will be generated using label property as the content.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "help": {
      "description": "If this property is added, a help text will be generated using help property as the content.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "value": {
      "description": "The current value of the input.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "className": {
      "description": "The class that will be added with \"components-base-control\" to the classes of the wrapper div.\nIf no className is passed only components-base-control is used.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onChange": {
      "description": "A function that receives the value of the input.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "prefix": {
      "description": "Markup to be inserted at the beginning of the input.",
      "type": {
        "name": "node"
      },
      "required": false
    },
    "suffix": {
      "description": "Markup to be appended at the end of the input.",
      "type": {
        "name": "node"
      },
      "required": false
    },
    "disabled": {
      "description": "Whether or not the input is disabled.",
      "type": {
        "name": "bool"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "./setting.mock.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   P: () => (/* binding */ getSetting)
/* harmony export */ });
// @woocommerce/settings mocked module for storybook webpack resolve.alias config
// see ./webpack.config.js

function getSetting() {
  return {};
}

/***/ }),

/***/ "../../packages/js/components/src/advanced-filters/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ advanced_filters)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js + 4 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js + 6 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/add-outline.js
var add_outline = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/add-outline.js");
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 4 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/cross-small.js
var cross_small = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/cross-small.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js
var create_interpolate_element = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js");
;// ../../packages/js/components/src/advanced-filters/utils.ts
/**
 * External dependencies
 */



/**
 * DOM Node.textContent for React components
 * See: https://github.com/rwu823/react-addons-text-content/blob/master/src/index.js
 *
 * @param {Array<string|Node>} components array of components
 *
 * @return {string} concatenated text content of all nodes
 */
function textContent(components) {
  let text = '';
  const toText = component => {
    if ((0,lodash.isString)(component) || (0,lodash.isNumber)(component)) {
      text += component;
    } else if ((0,lodash.isArray)(component)) {
      component.forEach(toText);
    } else if ((0,react.isValidElement)(component) && component.props) {
      const {
        children
      } = component.props;
      if ((0,lodash.isArray)(children)) {
        children.forEach(toText);
      } else {
        toText(children);
      }
    }
  };
  toText(components);
  return text;
}

/**
 * This function processes an input string, checks for deprecated interpolation formatting, and
 * modifies it to conform to the new standard.
 * The deprecated interpolation formatting is `{{element}}...{{/element}}`, and the new standard
 * formatting is `<element>...</element>`.
 *
 * @param {string} interpolatedString The interpolation string to be parsed.
 *
 * @return {string}  Fixed interpolation string.
 */
function getInterpolatedString(interpolatedString) {
  const regex = /(\{\{)(\/?\s*\w+\s*\/?)(\}\})/g;
  const replacedString = interpolatedString.replaceAll(regex, (match, p1, p2) => {
    const inner = p2.trim();
    let replacement;
    if (inner.startsWith('/')) {
      // Closing tag
      replacement = `</${inner.slice(1)}>`;
    } else if (inner.endsWith('/')) {
      // Self-closing tag
      replacement = `<${inner.slice(0, -1)}/>`;
    } else {
      // Opening tag
      replacement = `<${inner}>`;
    }
    return replacement;
  });
  if (replacedString !== interpolatedString) {
    (0,deprecated_build_module/* default */.A)('Old interpolation string format `{{element}}...{{/element}}` or `{{element/}}`', {
      since: '7.8',
      alternative: 'new interpolation string format `<element>...</element>` or `<element/>`',
      link: 'https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/components/src/advanced-filters/README.md'
    });
  }
  return replacedString;
}

/**
 * This function creates an interpolation element that is backwards compatible.
 *
 * @param {string} interpolatedString The interpolation string to be parsed and transformed.
 * @param {Object} conversionMap      The map used for the conversion to create the interpolate element.
 *
 * @return {Element} A React element that is the result of applying the transformation.
 */
function backwardsCompatibleCreateInterpolateElement(interpolatedString, conversionMap) {
  return (0,create_interpolate_element/* default */.A)(getInterpolatedString(interpolatedString), conversionMap);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/advanced-filters/select-filter.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


class SelectFilter extends react.Component {
  constructor(props) {
    super(props);
    const {
      filter,
      config,
      onFilterChange
    } = props;
    const options = config.input.options;
    this.state = {
      options
    };
    this.updateOptions = this.updateOptions.bind(this);
    if (!options && config.input.getOptions) {
      void config.input.getOptions().then(this.updateOptions).then(returnedOptions => {
        if (!filter.value) {
          const value = (0,src/* getDefaultOptionValue */.Am)(config, returnedOptions);
          onFilterChange({
            property: 'value',
            value
          });
        }
      });
    }
  }
  updateOptions(options) {
    this.setState({
      options
    });
    return options;
  }
  getScreenReaderText(filter, config) {
    if (filter.value === '') {
      return '';
    }
    const rule = (0,lodash.find)(config.rules, {
      value: filter.rule
    }) || {};
    const value = (0,lodash.find)(config.input.options, option => option.value === filter.value) || {};
    return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
      filter: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: value.label
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: rule.label
      }),
      title: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
    }));
  }
  render() {
    const {
      className,
      config,
      filter,
      onFilterChange,
      isEnglish
    } = this.props;
    const {
      options
    } = this.state;
    const {
      rule,
      value
    } = filter;
    const {
      labels,
      rules
    } = config;
    const children = backwardsCompatibleCreateInterpolateElement(labels.title, {
      title: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: className
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
        __next40pxDefaultSize: true,
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__rule'),
        options: rules,
        value: rule,
        onChange: selectedValue => onFilterChange({
          property: 'rule',
          value: selectedValue
        }),
        "aria-label": labels.rule
      }),
      filter: options ? /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
        __next40pxDefaultSize: true,
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__input'),
        options: options,
        value: String(value ?? ''),
        onChange: selectedValue => onFilterChange({
          property: 'value',
          value: selectedValue
        }),
        "aria-label": labels.filter
      }) : /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {})
    });
    const screenReaderText = this.getScreenReaderText(filter, config);
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("fieldset", {
      className: "woocommerce-filters-advanced__line-item",
      tabIndex: 0,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("legend", {
        className: "screen-reader-text",
        children: labels.add || ''
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        }),
        children: children
      }), screenReaderText && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "screen-reader-text",
        children: screenReaderText
      })]
    });
  }
}
/* harmony default export */ const select_filter = (SelectFilter);
try {
    // @ts-ignore
    SelectFilter.displayName = "SelectFilter";
    // @ts-ignore
    SelectFilter.__docgenInfo = { "description": "", "displayName": "SelectFilter", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "FilterConfig" } }, "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "ActiveFilter" } }, "isEnglish": { "defaultValue": null, "description": "", "name": "isEnglish", "required": false, "type": { "name": "boolean" } }, "onFilterChange": { "defaultValue": null, "description": "", "name": "onFilterChange", "required": true, "type": { "name": "OnFilterChange" } }, "query": { "defaultValue": null, "description": "", "name": "query", "required": false, "type": { "name": "Query" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/select-filter.tsx#SelectFilter"] = { docgenInfo: SelectFilter.__docgenInfo, name: "SelectFilter", path: "../../packages/js/components/src/advanced-filters/select-filter.tsx#SelectFilter" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/search/index.tsx
var search = __webpack_require__("../../packages/js/components/src/search/index.tsx");
;// ../../packages/js/components/src/advanced-filters/search-filter.tsx
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



/**
 * A selected search value. Keys come from API ids, so they may be numeric at runtime.
 */

const normalizeFilterValue = value => {
  if (Array.isArray(value)) {
    return value.join(',');
  }
  return typeof value === 'string' ? value : '';
};
class SearchFilter extends react.Component {
  constructor(props) {
    super(props);
    const {
      filter,
      query
    } = props;
    this.onSearchChange = this.onSearchChange.bind(this);
    this.state = {
      selected: []
    };
    this.updateLabels = this.updateLabels.bind(this);
    const filterValue = normalizeFilterValue(filter.value);
    if (filterValue.length) {
      this.loadLabels(filterValue, query);
    }
  }
  componentDidUpdate(prevProps) {
    const {
      filter,
      query
    } = this.props;
    const {
      filter: prevFilter
    } = prevProps;
    const filterValue = normalizeFilterValue(filter.value);
    if (filterValue.length && !(0,lodash.isEqual)(prevFilter, filter)) {
      const {
        selected
      } = this.state;
      const selectedIds = selected.map(item => String(item.key));
      const filterIds = filterValue.split(',').filter(Boolean).map(String);
      const haveIdsChanged = filterIds.length !== selectedIds.length || filterIds.some(id => !selectedIds.includes(id));
      if (haveIdsChanged) {
        this.loadLabels(filterValue, query);
      }
    }
  }
  loadLabels(filterValue, query) {
    void this.props.config.input.getLabels?.(filterValue, query).then(selected => {
      if (filterValue === normalizeFilterValue(this.props.filter.value)) {
        this.updateLabels(selected);
      }
    });
  }
  updateLabels(selected) {
    const normalizedSelected = selected.map(item => ({
      ...item,
      key: item.key ?? item.id
    })).filter(item => item.key !== undefined);
    const prevIds = this.state.selected.map(item => item.key);
    const ids = normalizedSelected.map(item => item.key);
    if (!(0,lodash.isEqual)([...ids].sort(), [...prevIds].sort())) {
      this.setState({
        selected: normalizedSelected
      });
    }
  }
  onSearchChange(values) {
    this.setState({
      selected: values
    });
    const {
      onFilterChange
    } = this.props;
    const idList = values.map(value => value.key).join(',');
    onFilterChange({
      property: 'value',
      value: idList
    });
  }
  getScreenReaderText(filter, config) {
    const {
      selected
    } = this.state;
    if (selected.length === 0) {
      return '';
    }
    const rule = (0,lodash.find)(config.rules, {
      value: filter.rule
    }) || {};
    const filterStr = selected.map(item => item.label).join(', ');
    return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
      filter: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: filterStr
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: rule.label
      }),
      title: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
    }));
  }
  render() {
    const {
      className,
      config,
      filter,
      onFilterChange,
      isEnglish
    } = this.props;
    const {
      selected
    } = this.state;
    const {
      rule
    } = filter;
    const {
      input,
      labels,
      rules
    } = config;
    const children = backwardsCompatibleCreateInterpolateElement(labels.title, {
      title: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: className
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
        __next40pxDefaultSize: true,
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__rule'),
        options: rules,
        value: rule,
        onChange: value => onFilterChange({
          property: 'rule',
          value
        }),
        "aria-label": labels.rule
      }),
      filter: /*#__PURE__*/(0,jsx_runtime.jsx)(search/* default */.A, {
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__input'),
        onChange: this.onSearchChange,
        type: input.type,
        autocompleter: input.autocompleter,
        placeholder: labels.placeholder
        // Search types keys as strings, but ids resolved from the API are numeric.
        ,
        selected: selected,
        inlineTags: true,
        "aria-label": labels.filter
      })
    });
    const screenReaderText = this.getScreenReaderText(filter, config);
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("fieldset", {
      className: "woocommerce-filters-advanced__line-item",
      tabIndex: 0,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("legend", {
        className: "screen-reader-text",
        children: labels.add || ''
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        }),
        children: children
      }), screenReaderText && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "screen-reader-text",
        children: screenReaderText
      })]
    });
  }
}
/* harmony default export */ const search_filter = (SearchFilter);
try {
    // @ts-ignore
    SearchFilter.displayName = "SearchFilter";
    // @ts-ignore
    SearchFilter.__docgenInfo = { "description": "", "displayName": "SearchFilter", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "FilterConfig" } }, "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "ActiveFilter" } }, "isEnglish": { "defaultValue": null, "description": "", "name": "isEnglish", "required": false, "type": { "name": "boolean" } }, "onFilterChange": { "defaultValue": null, "description": "", "name": "onFilterChange", "required": true, "type": { "name": "OnFilterChange" } }, "query": { "defaultValue": null, "description": "", "name": "query", "required": false, "type": { "name": "Query" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/search-filter.tsx#SearchFilter"] = { docgenInfo: SearchFilter.__docgenInfo, name: "SearchFilter", path: "../../packages/js/components/src/advanced-filters/search-filter.tsx#SearchFilter" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../packages/js/currency/src/index.ts + 3 modules
var currency_src = __webpack_require__("../../packages/js/currency/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/text-control-with-affixes/index.js
var text_control_with_affixes = __webpack_require__("../../packages/js/components/src/text-control-with-affixes/index.js");
;// ../../packages/js/components/src/advanced-filters/number-filter.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



// The component is wrapped in withInstanceId, which hides its props from TS.
const TextControlWithAffixes = text_control_with_affixes/* default */.A;
class NumberFilter extends react.Component {
  getBetweenString() {
    return (0,build_module._x)('<rangeStart/><span> and </span><rangeEnd/>', 'Numerical range inputs arranged on a single line', 'woocommerce');
  }
  getScreenReaderText(filter, config) {
    const {
      currency
    } = this.props;
    const rule = (0,lodash.find)(config.rules, {
      value: filter.rule
    }) || {};
    let [rangeStart, rangeEnd] = (0,lodash.isArray)(filter.value) ? filter.value : [filter.value];

    // Return nothing if we're missing input(s)
    if (!rangeStart || rule.value === 'between' && !rangeEnd) {
      return '';
    }
    const inputType = (0,lodash.get)(config, ['input', 'type'], 'number');
    if (inputType === 'currency') {
      const {
        formatAmount
      } = (0,currency_src/* CurrencyFactory */.uU)(currency);
      rangeStart = formatAmount(rangeStart);
      if (rangeEnd) {
        rangeEnd = formatAmount(rangeEnd);
      }
    }
    let filterStr = rangeStart;
    if (rule.value === 'between') {
      filterStr = backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
        rangeStart: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
          children: rangeStart
        }),
        rangeEnd: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
          children: rangeEnd
        }),
        span: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
      });
    }
    return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
      filter: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: filterStr
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: rule.label
      }),
      title: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
    }));
  }
  getFormControl({
    type,
    value,
    label,
    onChange,
    currencySymbol,
    symbolPosition
  }) {
    if (type === 'currency') {
      return symbolPosition.indexOf('right') === 0 ? /*#__PURE__*/(0,jsx_runtime.jsx)(TextControlWithAffixes, {
        suffix: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: currencySymbol
        }),
        className: "woocommerce-filters-advanced__input",
        type: "number",
        value: value || '',
        "aria-label": label,
        onChange: onChange
      }) : /*#__PURE__*/(0,jsx_runtime.jsx)(TextControlWithAffixes, {
        prefix: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: currencySymbol
        }),
        className: "woocommerce-filters-advanced__input",
        type: "number",
        value: value || '',
        "aria-label": label,
        onChange: onChange
      });
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
      className: "woocommerce-filters-advanced__input",
      type: "number",
      value: value || '',
      "aria-label": label,
      onChange: onChange
    });
  }
  getFilterInputs() {
    const {
      config,
      filter,
      onFilterChange,
      currency
    } = this.props;
    const {
      symbol: currencySymbol,
      symbolPosition
    } = currency;
    if (filter.rule === 'between') {
      return this.getRangeInput();
    }
    const inputType = (0,lodash.get)(config, ['input', 'type'], 'number');
    const [rangeStart, rangeEnd] = (0,lodash.isArray)(filter.value) ? filter.value : [filter.value];
    if (rangeEnd) {
      // If there's a value for rangeEnd, we've just changed from "between"
      // to "less than" or "more than" and need to transition the value
      onFilterChange({
        property: 'value',
        value: rangeStart || rangeEnd
      });
    }
    const labelFormat = filter.rule === 'lessthan' ? /* translators: Sentence fragment, "maximum amount" refers to a numeric value the field must be less than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
    (0,build_module._x)('%(field)s maximum amount', 'maximum value input', 'woocommerce') : /* translators: Sentence fragment, "minimum amount" refers to a numeric value the field must be more than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
    (0,build_module._x)('%(field)s minimum amount', 'minimum value input', 'woocommerce');
    return this.getFormControl({
      type: inputType,
      value: rangeStart || rangeEnd,
      label: (0,build_module/* sprintf */.nv)(labelFormat, {
        field: (0,lodash.get)(config, ['labels', 'add'])
      }),
      onChange: value => onFilterChange({
        property: 'value',
        value
      }),
      currencySymbol,
      symbolPosition
    });
  }
  getRangeInput() {
    const {
      config,
      filter,
      onFilterChange,
      currency
    } = this.props;
    const {
      symbol: currencySymbol,
      symbolPosition
    } = currency;
    const inputType = (0,lodash.get)(config, ['input', 'type'], 'number');
    const [rangeStart, rangeEnd] = (0,lodash.isArray)(filter.value) ? filter.value : [filter.value];
    const rangeStartOnChange = newRangeStart => {
      onFilterChange({
        property: 'value',
        value: [newRangeStart, rangeEnd]
      });
    };
    const rangeEndOnChange = newRangeEnd => {
      onFilterChange({
        property: 'value',
        value: [rangeStart, newRangeEnd]
      });
    };
    return backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
      rangeStart: this.getFormControl({
        type: inputType,
        value: rangeStart || '',
        label: (0,build_module/* sprintf */.nv)(/* translators: Sentence fragment, "range start" refers to the first of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
        (0,build_module.__)('%(field)s range start', 'woocommerce'), {
          field: (0,lodash.get)(config, ['labels', 'add'])
        }),
        onChange: rangeStartOnChange,
        currencySymbol,
        symbolPosition
      }),
      rangeEnd: this.getFormControl({
        type: inputType,
        value: rangeEnd || '',
        label: (0,build_module/* sprintf */.nv)(/* translators: Sentence fragment, "range end" refers to the second of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
        (0,build_module.__)('%(field)s range end', 'woocommerce'), {
          field: (0,lodash.get)(config, ['labels', 'add'])
        }),
        onChange: rangeEndOnChange,
        currencySymbol,
        symbolPosition
      }),
      span: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "separator"
      })
    });
  }
  render() {
    const {
      className,
      config,
      filter,
      onFilterChange,
      isEnglish
    } = this.props;
    const {
      rule
    } = filter;
    const {
      labels,
      rules
    } = config;
    const children = backwardsCompatibleCreateInterpolateElement(labels.title, {
      title: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: className
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
        __next40pxDefaultSize: true,
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__rule'),
        options: rules,
        value: rule,
        onChange: value => onFilterChange({
          property: 'rule',
          value
        }),
        "aria-label": labels.rule
      }),
      filter: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__input-range', {
          'is-between': rule === 'between'
        }),
        children: this.getFilterInputs()
      })
    });
    const screenReaderText = this.getScreenReaderText(filter, config);
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("fieldset", {
      className: "woocommerce-filters-advanced__line-item",
      tabIndex: 0,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("legend", {
        className: "screen-reader-text",
        children: labels.add || ''
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        }),
        children: children
      }), screenReaderText && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "screen-reader-text",
        children: screenReaderText
      })]
    });
  }
}
/* harmony default export */ const number_filter = (NumberFilter);
try {
    // @ts-ignore
    NumberFilter.displayName = "NumberFilter";
    // @ts-ignore
    NumberFilter.__docgenInfo = { "description": "", "displayName": "NumberFilter", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "FilterConfig" } }, "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "ActiveFilter" } }, "isEnglish": { "defaultValue": null, "description": "", "name": "isEnglish", "required": false, "type": { "name": "boolean" } }, "onFilterChange": { "defaultValue": null, "description": "", "name": "onFilterChange", "required": true, "type": { "name": "OnFilterChange" } }, "query": { "defaultValue": null, "description": "", "name": "query", "required": false, "type": { "name": "Query" } }, "currency": { "defaultValue": null, "description": "", "name": "currency", "required": true, "type": { "name": "Currency" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/number-filter.tsx#NumberFilter"] = { docgenInfo: NumberFilter.__docgenInfo, name: "NumberFilter", path: "../../packages/js/components/src/advanced-filters/number-filter.tsx#NumberFilter" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var date_src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/date-picker.js
var date_picker = __webpack_require__("../../packages/js/components/src/calendar/date-picker.js");
;// ../../packages/js/components/src/advanced-filters/date-filter.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */



const dateStringFormat = (0,build_module.__)('MMM D, YYYY', 'woocommerce');
const dateFormat = (0,build_module.__)('MM/DD/YYYY', 'woocommerce');
class DateFilter extends react.Component {
  constructor(props) {
    super(props);
    const {
      filter
    } = props;
    const [isoAfter, isoBefore] = Array.isArray(filter.value) ? filter.value : [null, filter.value];
    const after = isoAfter ? (0,date_src/* toMoment */.sf)(date_src/* isoDateFormat */.r3, isoAfter) : null;
    const before = isoBefore ? (0,date_src/* toMoment */.sf)(date_src/* isoDateFormat */.r3, isoBefore) : null;
    this.state = {
      before,
      beforeText: before ? before.format(dateFormat) : '',
      beforeError: null,
      after,
      afterText: after ? after.format(dateFormat) : '',
      afterError: null,
      rule: filter.rule
    };
    this.onSingleDateChange = this.onSingleDateChange.bind(this);
    this.onRangeDateChange = this.onRangeDateChange.bind(this);
    this.onRuleChange = this.onRuleChange.bind(this);
  }
  getBetweenString() {
    return (0,build_module._x)('<after/><span> and </span><before/>', 'Date range inputs arranged on a single line', 'woocommerce');
  }
  getScreenReaderText(filterRule, config) {
    const rule = (0,lodash.find)(config.rules, {
      value: filterRule
    }) || {};
    const {
      before,
      after
    } = this.state;

    // Return nothing if we're missing input(s)
    if (!before || rule.value === 'between' && !after) {
      return '';
    }
    let filterStr = before.format(dateStringFormat);
    if (rule.value === 'between' && after) {
      filterStr = backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
        after: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
          children: after.format(dateStringFormat)
        }),
        before: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
          children: before.format(dateStringFormat)
        }),
        span: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
      });
    }
    return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
      filter: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: filterStr
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
        children: rule.label
      }),
      title: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
    }));
  }
  onSingleDateChange({
    date,
    text,
    error
  }) {
    const {
      onFilterChange
    } = this.props;
    this.setState({
      before: date,
      beforeText: text,
      beforeError: error
    });
    if (date) {
      onFilterChange({
        property: 'value',
        value: date.format(date_src/* isoDateFormat */.r3)
      });
    }
  }
  onRangeDateChange(input, {
    date,
    text,
    error
  }) {
    const {
      onFilterChange
    } = this.props;
    if (input === 'after') {
      this.setState({
        after: date,
        afterText: text,
        afterError: error
      });
    } else {
      this.setState({
        before: date,
        beforeText: text,
        beforeError: error
      });
    }
    if (date) {
      const {
        before,
        after
      } = this.state;
      let nextAfter = null;
      let nextBefore = null;
      if (input === 'after') {
        nextAfter = date.format(date_src/* isoDateFormat */.r3);
        nextBefore = before ? before.format(date_src/* isoDateFormat */.r3) : null;
      }
      if (input === 'before') {
        nextAfter = after ? after.format(date_src/* isoDateFormat */.r3) : null;
        nextBefore = date.format(date_src/* isoDateFormat */.r3);
      }
      if (nextAfter && nextBefore) {
        onFilterChange({
          property: 'value',
          value: [nextAfter, nextBefore]
        });
      }
    }
  }
  onRuleChange(newRule) {
    const {
      onFilterChange
    } = this.props;
    const {
      rule
    } = this.state;
    const shouldResetValue = [rule, newRule].includes('between');
    if (shouldResetValue) {
      this.setState({
        rule: newRule,
        before: null,
        beforeText: '',
        beforeError: null,
        after: null,
        afterText: '',
        afterError: null
      });
    } else {
      this.setState({
        rule: newRule
      });
    }
    onFilterChange({
      property: 'rule',
      value: newRule,
      shouldResetValue
    });
  }
  isFutureDate(date) {
    return moment_default()().isBefore(moment_default()(date), 'day');
  }
  getFormControl({
    date,
    error,
    onUpdate,
    text
  }) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(date_picker/* default */.A, {
      date: date,
      dateFormat: dateFormat,
      error: error,
      isInvalidDate: this.isFutureDate,
      onUpdate: onUpdate,
      text: text
    });
  }
  getRangeInput() {
    const {
      before,
      beforeText,
      beforeError,
      after,
      afterText,
      afterError
    } = this.state;
    return backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
      after: this.getFormControl({
        date: after,
        error: afterError,
        onUpdate: (0,lodash.partial)(this.onRangeDateChange, 'after'),
        text: afterText
      }),
      before: this.getFormControl({
        date: before,
        error: beforeError,
        onUpdate: (0,lodash.partial)(this.onRangeDateChange, 'before'),
        text: beforeText
      }),
      span: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "separator"
      })
    });
  }
  getFilterInputs() {
    const {
      before,
      beforeText,
      beforeError,
      rule
    } = this.state;
    if (rule === 'between') {
      return this.getRangeInput();
    }
    return this.getFormControl({
      date: before,
      error: beforeError,
      onUpdate: this.onSingleDateChange,
      text: beforeText
    });
  }
  render() {
    const {
      className,
      config,
      isEnglish
    } = this.props;
    const {
      rule
    } = this.state;
    const {
      labels,
      rules
    } = config;
    const screenReaderText = this.getScreenReaderText(rule, config);
    const children = backwardsCompatibleCreateInterpolateElement(labels.title, {
      title: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: className
      }),
      rule: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
        __next40pxDefaultSize: true,
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__rule'),
        options: rules,
        value: rule,
        onChange: this.onRuleChange,
        "aria-label": labels.rule
      }),
      filter: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__input-range', {
          'is-between': rule === 'between'
        }),
        children: this.getFilterInputs()
      })
    });
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("fieldset", {
      className: "woocommerce-filters-advanced__line-item",
      tabIndex: 0,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("legend", {
        className: "screen-reader-text",
        children: labels.add || ''
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        }),
        children: children
      }), screenReaderText && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "screen-reader-text",
        children: screenReaderText
      })]
    });
  }
}
/* harmony default export */ const date_filter = (DateFilter);
try {
    // @ts-ignore
    DateFilter.displayName = "DateFilter";
    // @ts-ignore
    DateFilter.__docgenInfo = { "description": "", "displayName": "DateFilter", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "FilterConfig" } }, "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "ActiveFilter" } }, "isEnglish": { "defaultValue": null, "description": "", "name": "isEnglish", "required": false, "type": { "name": "boolean" } }, "onFilterChange": { "defaultValue": null, "description": "", "name": "onFilterChange", "required": true, "type": { "name": "OnFilterChange" } }, "query": { "defaultValue": null, "description": "", "name": "query", "required": false, "type": { "name": "Query" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/date-filter.tsx#DateFilter"] = { docgenInfo: DateFilter.__docgenInfo, name: "DateFilter", path: "../../packages/js/components/src/advanced-filters/date-filter.tsx#DateFilter" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/index.js + 10 modules
var api_fetch_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var src_select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
;// ../../packages/js/components/src/advanced-filters/attribute-filter.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */




const getScreenReaderText = ({
  attributeTerms,
  config,
  filter,
  selectedAttribute,
  selectedAttributeTerm
}) => {
  if (!attributeTerms || attributeTerms.length === 0 || !selectedAttribute || selectedAttribute.length === 0 || selectedAttributeTerm === '') {
    return '';
  }
  const rule = Array.isArray(config.rules) ? config.rules.find(configRule => configRule.value === filter.rule) || {} : {};
  const attributeName = selectedAttribute[0].label;
  const termObject = attributeTerms.find(({
    key
  }) => key === selectedAttributeTerm);
  const attributeTerm = termObject && termObject.label;
  if (!attributeName || !attributeTerm) {
    return '';
  }
  const filterStr = backwardsCompatibleCreateInterpolateElement(/* translators: Sentence fragment describing a product attribute match. Example: "Color Is Not Blue" - attribute = Color, equals = Is Not, value = Blue */
  (0,build_module.__)('<attribute/> <equals/> <value/>', 'woocommerce'), {
    attribute: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
      children: attributeName
    }),
    equals: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
      children: rule.label
    }),
    value: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
      children: attributeTerm
    })
  });
  return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
    filter: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {
      children: filterStr
    }),
    rule: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {}),
    title: /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
  }));
};
const AttributeFilter = props => {
  const {
    className,
    config,
    filter,
    isEnglish,
    onFilterChange
  } = props;
  const {
    rule,
    value
  } = filter;
  const {
    labels,
    rules
  } = config;
  const [selectedAttribute, setSelectedAttribute] = (0,react.useState)([]);

  // Set selected attribute from filter value (in query string).
  (0,react.useEffect)(() => {
    if (!selectedAttribute.length && Array.isArray(value) && value[0]) {
      void (0,api_fetch_build_module["default"])({
        path: `/wc-analytics/products/attributes/${value[0]}`
      }).then(({
        id,
        name
      }) => [{
        key: id.toString(),
        label: name
      }]).then(setSelectedAttribute);
    }
  }, [value, selectedAttribute]);
  const [attributeTerms, setAttributeTerms] = (0,react.useState)([]);

  // Fetch all product attributes on mount.
  (0,react.useEffect)(() => {
    if (!selectedAttribute.length) {
      return;
    }
    setAttributeTerms(false);
    void (0,api_fetch_build_module["default"])({
      path: `/wc-analytics/products/attributes/${selectedAttribute[0].key}/terms?per_page=100`
    }).then(terms => terms.map(({
      id,
      name
    }) => ({
      key: id.toString(),
      label: name
    }))).then(setAttributeTerms);
  }, [selectedAttribute]);
  const [selectedAttributeTerm, setSelectedAttributeTerm] = (0,react.useState)(Array.isArray(value) ? String(value[1] || '') : '');
  const screenReaderText = getScreenReaderText({
    attributeTerms,
    config,
    filter,
    selectedAttribute,
    selectedAttributeTerm
  });
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("fieldset", {
    className: "woocommerce-filters-advanced__line-item",
    tabIndex: 0,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("legend", {
      className: "screen-reader-text",
      children: labels.add || ''
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: (0,clsx/* default */.A)('woocommerce-filters-advanced__fieldset', {
        'is-english': isEnglish
      }),
      children: backwardsCompatibleCreateInterpolateElement(labels.title, {
        title: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: className
        }),
        rule: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
          __next40pxDefaultSize: true,
          className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__rule'),
          options: rules,
          value: rule,
          onChange: selectedValue => onFilterChange({
            property: 'rule',
            value: selectedValue
          }),
          "aria-label": labels.rule
        }),
        filter: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: (0,clsx/* default */.A)(className, 'woocommerce-filters-advanced__attribute-fieldset'),
          children: [!Array.isArray(value) || !value.length || selectedAttribute.length ? /*#__PURE__*/(0,jsx_runtime.jsx)(search/* default */.A, {
            className: "woocommerce-filters-advanced__input woocommerce-search",
            onChange: ([attr]) => {
              setSelectedAttribute(attr ? [attr] : []);
              setSelectedAttributeTerm('');
              onFilterChange({
                property: 'value',
                value: [attr && attr.key].filter(Boolean)
              });
            },
            type: "attributes",
            placeholder: (0,build_module.__)('Attribute name', 'woocommerce'),
            multiple: false,
            selected: selectedAttribute,
            inlineTags: true,
            "aria-label": (0,build_module.__)('Attribute name', 'woocommerce')
          }) : /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {}), selectedAttribute.length > 0 && (attributeTerms && attributeTerms.length ? /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
            children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
              className: "woocommerce-filters-advanced__attribute-field-separator",
              children: "="
            }), /*#__PURE__*/(0,jsx_runtime.jsx)(src_select_control/* default */.A, {
              className: "woocommerce-filters-advanced__input woocommerce-search",
              placeholder: (0,build_module.__)('Attribute value', 'woocommerce'),
              inlineTags: true,
              isSearchable: true,
              multiple: false,
              showAllOnFocus: true,
              options: attributeTerms,
              selected: selectedAttributeTerm,
              onChange: term => {
                // Clearing the input using delete/backspace causes an empty array to be passed here.
                if (typeof term !== 'string') {
                  term = '';
                }
                setSelectedAttributeTerm(term);
                onFilterChange({
                  property: 'value',
                  value: [selectedAttribute[0].key, term].filter(Boolean)
                });
              }
            })]
          }) : /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {}))]
        })
      })
    }), screenReaderText && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "screen-reader-text",
      children: screenReaderText
    })]
  });
};
/* harmony default export */ const attribute_filter = (AttributeFilter);
try {
    // @ts-ignore
    attributefilter.displayName = "attributefilter";
    // @ts-ignore
    attributefilter.__docgenInfo = { "description": "", "displayName": "attributefilter", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "FilterConfig" } }, "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "ActiveFilter" } }, "isEnglish": { "defaultValue": null, "description": "", "name": "isEnglish", "required": false, "type": { "name": "boolean" } }, "onFilterChange": { "defaultValue": null, "description": "", "name": "onFilterChange", "required": true, "type": { "name": "OnFilterChange" } }, "query": { "defaultValue": null, "description": "", "name": "query", "required": false, "type": { "name": "Query" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/attribute-filter.tsx#attributefilter"] = { docgenInfo: attributefilter.__docgenInfo, name: "attributefilter", path: "../../packages/js/components/src/advanced-filters/attribute-filter.tsx#attributefilter" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/advanced-filters/item.tsx
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */






const componentMap = {
  Currency: number_filter,
  Date: date_filter,
  Number: number_filter,
  ProductAttribute: attribute_filter,
  Search: search_filter,
  SelectControl: select_filter
};
const isKnownComponent = component => componentMap.hasOwnProperty(component);
const AdvancedFilterItem = props => {
  const {
    config,
    currency,
    filter: filterValue,
    isEnglish,
    onFilterChange,
    query,
    removeFilter
  } = props;
  const {
    key
  } = filterValue;
  let filterConfig = config.filters[key];
  const {
    input,
    labels
  } = filterConfig;
  if (!isKnownComponent(input.component)) {
    return null;
  }
  if (input.component === 'Currency') {
    filterConfig = {
      ...filterConfig,
      ...{
        input: {
          type: 'currency',
          component: 'Currency'
        }
      }
    };
  }
  const FilterComponent = componentMap[input.component];
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("li", {
    className: "woocommerce-filters-advanced__list-item",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(FilterComponent, {
      className: "woocommerce-filters-advanced__fieldset-item",
      currency: currency,
      filter: filterValue,
      config: filterConfig,
      onFilterChange: onFilterChange,
      isEnglish: isEnglish,
      query: query
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      className: (0,clsx/* default */.A)('woocommerce-filters-advanced__line-item', 'woocommerce-filters-advanced__remove'),
      label: labels.remove,
      onClick: removeFilter,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(cross_small/* default */.A, {})
    })]
  });
};
/* harmony default export */ const advanced_filters_item = (AdvancedFilterItem);
try {
    // @ts-ignore
    item.displayName = "item";
    // @ts-ignore
    item.__docgenInfo = { "description": "", "displayName": "item", "props": { "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "AdvancedFilterConfig" } }, "currency": { "defaultValue": null, "description": "", "name": "currency", "required": true, "type": { "name": "Currency" } }, "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "ActiveFilter" } }, "isEnglish": { "defaultValue": null, "description": "", "name": "isEnglish", "required": true, "type": { "name": "boolean" } }, "onFilterChange": { "defaultValue": null, "description": "", "name": "onFilterChange", "required": true, "type": { "name": "OnFilterChange" } }, "query": { "defaultValue": null, "description": "", "name": "query", "required": true, "type": { "name": "Query" } }, "removeFilter": { "defaultValue": null, "description": "", "name": "removeFilter", "required": true, "type": { "name": "() => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/item.tsx#item"] = { docgenInfo: item.__docgenInfo, name: "item", path: "../../packages/js/components/src/advanced-filters/item.tsx#item" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/experimental.js
var experimental = __webpack_require__("../../packages/js/components/src/experimental.js");
;// ../../packages/js/components/src/advanced-filters/index.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





// Inside the class the defaulted props are always present.

const matches = [{
  value: 'all',
  label: (0,build_module.__)('All', 'woocommerce')
}, {
  value: 'any',
  label: (0,build_module.__)('Any', 'woocommerce')
}];

/**
 * Displays a configurable set of filters which can modify query parameters.
 */
class AdvancedFilters extends react.Component {
  static defaultProps = {
    query: {},
    onAdvancedFilterAction: () => {},
    siteLocale: 'en_US'
  };
  instanceCounts = {};
  filterListRef = (0,react.createRef)();
  constructor(props) {
    super(props);
    const {
      query,
      config
    } = props;
    const filtersFromQuery = (0,src/* getActiveFiltersFromQuery */.Q$)(query, config.filters);
    // @todo: This causes rerenders when instance numbers don't match (from adding/remove before updating query string).
    const activeFilters = filtersFromQuery.map(filter => {
      if (config.filters[filter.key].allowMultiple) {
        filter.instance = this.getInstanceNumber(filter.key);
      }
      return filter;
    });
    this.state = {
      match: typeof query.match === 'string' && query.match ? query.match : 'all',
      activeFilters
    };
    this.onMatchChange = this.onMatchChange.bind(this);
    this.onFilterChange = this.onFilterChange.bind(this);
    this.getAvailableFilters = this.getAvailableFilters.bind(this);
    this.addFilter = this.addFilter.bind(this);
    this.removeFilter = this.removeFilter.bind(this);
    this.clearFilters = this.clearFilters.bind(this);
    this.getUpdateHref = this.getUpdateHref.bind(this);
    this.onFilter = this.onFilter.bind(this);
  }
  componentDidUpdate(prevProps) {
    const {
      config,
      query
    } = this.props;
    const {
      query: prevQuery
    } = prevProps;
    if (!(0,lodash.isEqual)(prevQuery, query)) {
      const filtersFromQuery = (0,src/* getActiveFiltersFromQuery */.Q$)(query, config.filters);

      // Update all multiple instance counts.
      this.instanceCounts = {};
      // @todo: This causes rerenders when instance numbers don't match (from adding/remove before updating query string).
      const activeFilters = filtersFromQuery.map(filter => {
        if (config.filters[filter.key].allowMultiple) {
          filter.instance = this.getInstanceNumber(filter.key);
        }
        return filter;
      });
      this.setState({
        activeFilters
      });
    }
  }
  getInstanceNumber(key) {
    if (!this.instanceCounts.hasOwnProperty(key)) {
      this.instanceCounts[key] = 1;
    }
    return this.instanceCounts[key]++;
  }
  onMatchChange(match) {
    const {
      onAdvancedFilterAction
    } = this.props;
    this.setState({
      match
    });
    onAdvancedFilterAction('match', {
      match
    });
  }
  onFilterChange(index, {
    property,
    value,
    shouldResetValue = false
  }) {
    const newActiveFilters = [...this.state.activeFilters];
    newActiveFilters[index] = {
      ...newActiveFilters[index],
      [property]: value,
      ...(shouldResetValue === true ? {
        value: null
      } : {})
    };
    this.setState({
      activeFilters: newActiveFilters
    });
  }
  removeFilter(index) {
    const {
      onAdvancedFilterAction
    } = this.props;
    const activeFilters = [...this.state.activeFilters];
    onAdvancedFilterAction('remove', activeFilters[index]);
    activeFilters.splice(index, 1);
    this.setState({
      activeFilters
    });
    if (activeFilters.length === 0) {
      const history = (0,src/* getHistory */.JK)();
      history.push(this.getUpdateHref([]));
    }
  }
  getTitle() {
    const {
      match
    } = this.state;
    const {
      config
    } = this.props;
    return backwardsCompatibleCreateInterpolateElement(config.title, {
      select: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
        __next40pxDefaultSize: true,
        className: "woocommerce-filters-advanced__title-select",
        options: matches,
        value: match,
        onChange: this.onMatchChange,
        "aria-label": (0,build_module.__)('Choose to apply any or all filters', 'woocommerce')
      })
    });
  }
  getAvailableFilters() {
    const {
      config
    } = this.props;
    const activeFilterKeys = this.state.activeFilters.map(f => f.key);

    // Get filter objects with keys.
    const allFilters = Object.entries(config.filters).map(([key, value]) => ({
      key,
      ...value
    }));

    // Available filters are those that allow multiple instances or are not already active.
    const availableFilters = allFilters.filter(filter => {
      return filter.allowMultiple || !activeFilterKeys.includes(filter.key);
    });

    // Sort filters by their add label.
    availableFilters.sort((a, b) => a.labels.add.localeCompare(b.labels.add));
    return availableFilters;
  }
  addFilter(key, onClose) {
    const {
      onAdvancedFilterAction,
      config
    } = this.props;
    const filterConfig = config.filters[key];
    const newFilter = {
      key
    };
    if (Array.isArray(filterConfig.rules) && filterConfig.rules.length) {
      newFilter.rule = filterConfig.rules[0].value;
    }
    if (filterConfig.input && filterConfig.input.options) {
      newFilter.value = (0,src/* getDefaultOptionValue */.Am)(filterConfig, filterConfig.input.options);
    }
    if (filterConfig.input && filterConfig.input.component === 'Search') {
      newFilter.value = '';
    }
    if (filterConfig.allowMultiple) {
      newFilter.instance = this.getInstanceNumber(key);
    }
    this.setState(state => {
      return {
        activeFilters: [...state.activeFilters, newFilter]
      };
    });
    onAdvancedFilterAction('add', newFilter);
    onClose();
    // after render, focus the newly added filter's first focusable element
    setTimeout(() => {
      const addedFilter = this.filterListRef.current?.querySelector('li:last-of-type fieldset');
      addedFilter?.focus();
    });
  }
  clearFilters() {
    const {
      onAdvancedFilterAction
    } = this.props;
    onAdvancedFilterAction('clear_all');
    this.setState({
      activeFilters: [],
      match: 'all'
    });
  }
  getUpdateHref(activeFilters, matchValue) {
    const {
      path,
      query,
      config
    } = this.props;
    const updatedQuery = (0,src/* getQueryFromActiveFilters */.Sz)(activeFilters, query, config.filters);
    const match = matchValue === 'all' ? undefined : matchValue;
    return (0,src/* getNewPath */.Gy)({
      ...updatedQuery,
      match
    }, path, query);
  }
  isEnglish() {
    return /en[-|_]/.test(this.props.siteLocale);
  }
  onFilter() {
    const {
      onAdvancedFilterAction,
      query,
      config
    } = this.props;
    const {
      activeFilters,
      match
    } = this.state;
    const updatedQuery = (0,src/* getQueryFromActiveFilters */.Sz)(activeFilters, query, config.filters);
    onAdvancedFilterAction('filter', {
      ...updatedQuery,
      match
    });
  }
  orderFilters(a, b) {
    const qs = window.location.search;
    const aPos = qs.indexOf(a.key);
    const bPos = qs.indexOf(b.key);
    // If either isn't in the url, it means its just been added, so leave it as is.
    if (aPos === -1 || bPos === -1) {
      return 0;
    }
    // Otherwise use the url to determine order in which filter was added.
    return aPos - bPos;
  }
  render() {
    const {
      config,
      query,
      currency
    } = this.props;
    const {
      activeFilters,
      match
    } = this.state;
    const availableFilters = this.getAvailableFilters();
    const updateHref = this.getUpdateHref(activeFilters, match);
    const updateDisabled = 'admin.php' + window.location.search === updateHref || activeFilters.length === 0;
    const isEnglish = this.isEnglish();
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      className: "woocommerce-filters-advanced",
      size: "small",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(card_header_component/* default */.A, {
        justify: "flex-start",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(experimental/* Text */.E, {
          variant: "subtitle.small",
          as: "div",
          weight: "600",
          size: "14",
          lineHeight: "20px",
          isBlock: "false",
          children: this.getTitle()
        })
      }), !!activeFilters.length &&
      /*#__PURE__*/
      // An unknown size maps to no padding class, which is what the list relies on.
      // @ts-expect-error: size must be one of small, medium, large, xSmall, extraSmall.
      (0,jsx_runtime.jsx)(card_body_component/* default */.A, {
        size: "none",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("ul", {
          className: "woocommerce-filters-advanced__list",
          ref: this.filterListRef,
          children: activeFilters.sort(this.orderFilters).map((filter, idx) => {
            const {
              instance,
              key
            } = filter;
            return /*#__PURE__*/(0,jsx_runtime.jsx)(advanced_filters_item, {
              config: config,
              currency: currency,
              filter: filter,
              isEnglish: isEnglish,
              onFilterChange: (0,lodash.partial)(this.onFilterChange, idx),
              query: query,
              removeFilter: () => this.removeFilter(idx)
            }, key + (instance || ''));
          })
        })
      }), availableFilters.length > 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(card_body_component/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-filters-advanced__add-filter",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown/* default */.A, {
            className: "woocommerce-filters-advanced__add-filter-dropdown",
            popoverProps: {
              placement: 'bottom'
            },
            renderToggle: ({
              isOpen,
              onToggle
            }) => /*#__PURE__*/(0,jsx_runtime.jsxs)(build_module_button/* default */.Ay, {
              className: "woocommerce-filters-advanced__add-button",
              onClick: onToggle,
              "aria-expanded": isOpen,
              children: [/*#__PURE__*/(0,jsx_runtime.jsx)(add_outline/* default */.A, {}), (0,build_module.__)('Add a filter', 'woocommerce')]
            }),
            renderContent: ({
              onClose
            }) => /*#__PURE__*/(0,jsx_runtime.jsx)("ul", {
              className: "woocommerce-filters-advanced__add-dropdown",
              children: availableFilters.map(filter => /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
                children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  onClick: (0,lodash.partial)(this.addFilter, filter.key, onClose),
                  children: filter.labels.add
                })
              }, filter.key))
            })
          })
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(card_footer_component/* default */.A, {
        align: "center",
        children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "woocommerce-filters-advanced__controls",
          children: [updateDisabled && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            isPrimary: true,
            disabled: true,
            children: (0,build_module.__)('Filter', 'woocommerce')
          }), !updateDisabled && /*#__PURE__*/(0,jsx_runtime.jsx)(src_link/* default */.A, {
            className: "components-button is-primary is-button",
            type: "wc-admin",
            href: updateHref,
            onClick: this.onFilter,
            children: (0,build_module.__)('Filter', 'woocommerce')
          }), activeFilters.length > 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(src_link/* default */.A, {
            type: "wc-admin",
            href: this.getUpdateHref([]),
            onClick: this.clearFilters,
            children: (0,build_module.__)('Clear all filters', 'woocommerce')
          })]
        })
      })]
    });
  }
}
/* harmony default export */ const advanced_filters = (AdvancedFilters);
try {
    // @ts-ignore
    ActiveFilterValue.displayName = "ActiveFilterValue";
    // @ts-ignore
    ActiveFilterValue.__docgenInfo = { "description": "Range filters hold `[ start, end ]` while either end may still be unset.", "displayName": "ActiveFilterValue", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/index.tsx#ActiveFilterValue"] = { docgenInfo: ActiveFilterValue.__docgenInfo, name: "ActiveFilterValue", path: "../../packages/js/components/src/advanced-filters/index.tsx#ActiveFilterValue" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Query.displayName = "Query";
    // @ts-ignore
    Query.__docgenInfo = { "description": "Parsed URL query. `allowMultiple` filters serialize as nested arrays\n(`attribute_is[0][0]=1`), so values are not always plain strings.", "displayName": "Query", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/index.tsx#Query"] = { docgenInfo: Query.__docgenInfo, name: "Query", path: "../../packages/js/components/src/advanced-filters/index.tsx#Query" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    AdvancedFilters.displayName = "AdvancedFilters";
    // @ts-ignore
    AdvancedFilters.__docgenInfo = { "description": "Displays a configurable set of filters which can modify query parameters.", "displayName": "AdvancedFilters", "props": { "config": { "defaultValue": null, "description": "The configuration object required to render filters.", "name": "config", "required": true, "type": { "name": "AdvancedFilterConfig" } }, "path": { "defaultValue": null, "description": "Name of this filter, used in translations.", "name": "path", "required": true, "type": { "name": "string" } }, "currency": { "defaultValue": null, "description": "The currency formatting instance for the site.", "name": "currency", "required": true, "type": { "name": "Currency" } }, "query": { "defaultValue": { value: "{}" }, "description": "The query string represented in object form.", "name": "query", "required": false, "type": { "name": "Query" } }, "onAdvancedFilterAction": { "defaultValue": { value: "() => {}" }, "description": "Function to be called after an advanced filter action has been taken.", "name": "onAdvancedFilterAction", "required": false, "type": { "name": "(action: AdvancedFilterAction, data?: Record<string, unknown> | ActiveFilter) => void" } }, "siteLocale": { "defaultValue": { value: "en_US" }, "description": "The locale for the site.", "name": "siteLocale", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/index.tsx#AdvancedFilters"] = { docgenInfo: AdvancedFilters.__docgenInfo, name: "AdvancedFilters", path: "../../packages/js/components/src/advanced-filters/index.tsx#AdvancedFilters" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
const Link = ({
  href,
  children,
  type = 'wc-admin',
  ...props
}) => {
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  const wcAdminLinkHandler = (onClick, event) => {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
      return;
    }
    event?.preventDefault();

    // If there is an onclick event, execute it.
    const onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if (event?.target instanceof Element) {
      const closestEventTarget = event.target.closest('a')?.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  const passProps = {
    ...props,
    'data-link-type': type
  };
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
    href: href,
    ...passProps,
    children: children
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Link);
try {
    // @ts-ignore
    Link.displayName = "Link";
    // @ts-ignore
    Link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "Link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#Link"] = { docgenInfo: Link.__docgenInfo, name: "Link", path: "../../packages/js/components/src/link/index.tsx#Link" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    link.displayName = "link";
    // @ts-ignore
    link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#link"] = { docgenInfo: link.__docgenInfo, name: "link", path: "../../packages/js/components/src/link/index.tsx#link" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/search/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/components/src/search/search.tsx");
/**
 * Internal dependencies
 */


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_search__WEBPACK_IMPORTED_MODULE_0__/* .Search */ .v);
try {
    // @ts-ignore
    Search.displayName = "Search";
    // @ts-ignore
    Search.__docgenInfo = { "description": "A search box which autocompletes results while typing, allowing for the user to select an existing object\n(product, order, customer, etc). Currently only products are supported.", "displayName": "Search", "props": { "allowFreeTextSearch": { "defaultValue": { value: "false" }, "description": "Render additional options in the autocompleter to allow free text entering depending on the type.", "name": "allowFreeTextSearch", "required": false, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((value: Option | OptionCompletionValue[]) => unknown)" } }, "type": { "defaultValue": null, "description": "The object type to be used in searching.", "name": "type", "required": true, "type": { "name": "enum", "value": [{ "value": "\"custom\"" }, { "value": "\"countries\"" }, { "value": "\"attributes\"" }, { "value": "\"categories\"" }, { "value": "\"coupons\"" }, { "value": "\"customerNames\"" }, { "value": "\"customers\"" }, { "value": "\"downloadIps\"" }, { "value": "\"emails\"" }, { "value": "\"orders\"" }, { "value": "\"products\"" }, { "value": "\"registeredCustomers\"" }, { "value": "\"taxes\"" }, { "value": "\"usernames\"" }, { "value": "\"variableProducts\"" }, { "value": "\"variations\"" }] } }, "autocompleter": { "defaultValue": null, "description": "The custom autocompleter to be used in searching when type is 'custom'", "name": "autocompleter", "required": false, "type": { "name": "AutoCompleter" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "string | { key: string; label: string; }[]" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "staticResults": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticResults", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": { value: "false" }, "description": "Whether the control is disabled or not.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "true" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/search/index.tsx#Search"] = { docgenInfo: Search.__docgenInfo, name: "Search", path: "../../packages/js/components/src/search/index.tsx#Search" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/currency/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  uU: () => (/* reexport */ utils_CurrencyFactory)
});

// UNUSED EXPORTS: CurrencyContext, default, getCurrencyData, getFilteredCurrencyInstance, localiseMonetaryValue, unformatLocalisedMonetaryValue

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/locutus@3.0.34/node_modules/locutus/esm/php/strings/number_format.js
var number_format = __webpack_require__("../../node_modules/.pnpm/locutus@3.0.34/node_modules/locutus/esm/php/strings/number_format.js");
;// ../../packages/js/number/src/index.ts
/**
 * External dependencies
 */


/**
 * Number formatting configuration object
 *
 * @typedef {Object} NumberConfig
 * @property {number|string|null} [precision]         Decimal precision.
 * @property {string}             [decimalSeparator]  Decimal separator.
 * @property {string}             [thousandSeparator] Character used to separate thousands groups.
 */

/**
 * Formats a number using site's current locale
 *
 * @see http://locutus.io/php/strings/number_format/
 * @param {NumberConfig}  numberConfig Number formatting configuration object.
 * @param {number|string} number       number to format
 * @return {string} A formatted string.
 */
function src_numberFormat({
  precision = null,
  decimalSeparator = '.',
  thousandSeparator = ','
}, number) {
  if (number === undefined) {
    return '';
  }
  if (typeof number !== 'number') {
    number = parseFloat(number);
  }
  if (isNaN(number)) {
    return '';
  }
  let parsedPrecision = precision === null ? NaN : Number(precision);
  if (isNaN(parsedPrecision)) {
    const [, decimals] = number.toString().split('.');
    parsedPrecision = decimals ? decimals.length : 0;
  }
  return (0,number_format/* number_format */.m)(number, parsedPrecision, decimalSeparator, thousandSeparator);
}

/**
 * Formats a number as average or number string according to the given `type`.
 *  - `type = 'average'` returns a rounded `Number`
 *  - `type = 'number'` returns a formatted `String`
 *
 * @param {NumberConfig} numberConfig number formatting configuration object.
 * @param {string}       type         of number to format, `'average'` or `'number'`
 * @param {number}       value        to format.
 * @return {string | number | null} A formatted string.
 */
function formatValue(numberConfig, type, value) {
  if (!Number.isFinite(value)) {
    return null;
  }
  switch (type) {
    case 'average':
      return Math.round(value);
    case 'number':
      return src_numberFormat({
        ...numberConfig,
        precision: null
      }, value);
  }
  return null;
}

/**
 * Calculates the delta/percentage change between two numbers.
 *
 * @param {number} primaryValue   the value to calculate change for.
 * @param {number} secondaryValue the baseline against which to calculate the change.
 * @return {?number} Percent change between the primaryValue from the secondaryValue.
 */
function calculateDelta(primaryValue, secondaryValue) {
  if (!Number.isFinite(primaryValue) || !Number.isFinite(secondaryValue)) {
    return null;
  }
  if (secondaryValue === 0) {
    return 0;
  }
  return Math.round((primaryValue - secondaryValue) / Math.abs(secondaryValue) * 100);
}

/**
 * Parse a string into a number using site's current config
 *
 * @param {NumberConfig} numberConfig Number formatting configuration object.
 * @param {string}       value        value to parse
 * @return {string} A parsed number.
 */
function src_parseNumber({
  precision = null,
  decimalSeparator = '.',
  thousandSeparator = ','
}, value) {
  if (typeof value !== 'string' || value === '') {
    return '';
  }
  let parsedPrecision = precision === null ? NaN : Number(precision);
  if (isNaN(parsedPrecision)) {
    const [, decimals] = value.split(decimalSeparator);
    parsedPrecision = decimals ? decimals.length : 0;
  }
  let parsedValue = value;
  if (thousandSeparator) {
    parsedValue = parsedValue.replace(new RegExp(`\\${thousandSeparator}`, 'g'), '');
  }
  if (decimalSeparator) {
    parsedValue = parsedValue.replace(new RegExp(`\\${decimalSeparator}`, 'g'), '.');
  }
  return Number.parseFloat(parsedValue).toFixed(parsedPrecision);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/currency/src/utils.tsx
/**
 * External dependencies
 */






/**
 * @typedef {import('@woocommerce/number').NumberConfig} NumberConfig
 */
/**
 * @typedef {Object} CurrencyProps
 * @property {string} code           Currency ISO code.
 * @property {string} symbol         Symbol, can be multi-character. Should be in plain text, w/o HTML markup. HTML entities will be decoded.
 * @property {string} symbolPosition Where the symbol should be relative to the amount. One of `'left' | 'right' | 'left_space | 'right_space'`.
 * @typedef {NumberConfig & CurrencyProps} CurrencyConfig
 */

/**
 *
 * @param {CurrencyConfig} currencySetting
 * @return {Object} currency object
 */
const CurrencyFactoryBase = function (currencySetting) {
  let currency;
  function stripTags(str) {
    // sanitize Polyfill - see https://github.com/WordPress/WordPress/blob/master/wp-includes/js/wp-sanitize.js
    const strippedStr = str.replace(/<!--[\s\S]*?(-->|$)/g, '').replace(/<(script|style)[^>]*>[\s\S]*?(<\/\1>|$)/gi, '').replace(/<\/?[a-z][\s\S]*?(>|$)/gi, '');
    if (strippedStr !== str) {
      return stripTags(strippedStr);
    }
    return strippedStr;
  }

  /**
   * Get the default price format from a currency.
   *
   * @param {CurrencyConfig} config Currency configuration.
   * @return {string} Price format.
   */
  function getPriceFormat(config) {
    if (config.priceFormat) {
      return stripTags(config.priceFormat.toString());
    }
    switch (config.symbolPosition) {
      case 'left':
        return '%1$s%2$s';
      case 'right':
        return '%2$s%1$s';
      case 'left_space':
        return '%1$s %2$s';
      case 'right_space':
        return '%2$s %1$s';
    }
    return '%1$s%2$s';
  }
  function setCurrency(setting) {
    const defaultCurrency = {
      code: 'USD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    };
    const config = {
      ...defaultCurrency,
      ...setting
    };
    let precision = config.precision;
    if (precision === null) {
      // eslint-disable-next-line no-console
      console.warn('Currency precision is null');
      // eslint-enable-next-line no-console

      precision = NaN;
    } else if (typeof precision === 'string') {
      precision = parseInt(precision, 10);
    }
    currency = {
      code: config.code.toString(),
      symbol: (0,build_module/* decodeEntities */.S)(config.symbol.toString()),
      symbolPosition: config.symbolPosition.toString(),
      decimalSeparator: config.decimalSeparator.toString(),
      priceFormat: getPriceFormat(config),
      thousandSeparator: config.thousandSeparator.toString(),
      precision
    };
  }

  /**
   * Formats money value.
   *
   * @param {number|string} number          number to format
   * @param {boolean}       [useCode=false] Set to `true` to use the currency code instead of the symbol.
   * @return {?string} A formatted string.
   */
  function formatAmount(number, useCode = false) {
    const formattedNumber = src_numberFormat(currency, number);
    if (formattedNumber === '') {
      return formattedNumber;
    }
    const {
      priceFormat,
      symbol,
      code
    } = currency;

    // @ts-expect-error priceFormat is dynamic, but is expected to include placeholders for the currency and amount.
    // eslint-disable-next-line @wordpress/valid-sprintf
    return (0,i18n_build_module/* sprintf */.nv)(priceFormat, useCode ? code : symbol, formattedNumber);
  }

  /**
   * Formats money value.
   *
   * @deprecated
   * @param {number|string} number number to format
   * @return {?string} A formatted string.
   */
  function formatCurrency(number) {
    (0,deprecated_build_module/* default */.A)('Currency().formatCurrency', {
      version: '5.0.0',
      alternative: 'Currency().formatAmount',
      plugin: 'WooCommerce',
      hint: '`formatAmount` accepts the same arguments as formatCurrency'
    });
    return formatAmount(number);
  }

  /**
   * Get formatted data for a country from supplied locale and symbol info.
   *
   * @param {string} countryCode     Country code.
   * @param {Object} localeInfo      Locale info by country code.
   * @param {Object} currencySymbols Currency symbols by symbol code. HTML entities will be decoded.
   * @return {CurrencyConfig | {}} Formatted currency data for country.
   */
  function getDataForCountry(countryCode, localeInfo = {}, currencySymbols = {}) {
    const countryInfo = localeInfo[countryCode];
    if (!countryInfo) {
      return {};
    }
    const symbol = currencySymbols[countryInfo.currency_code];
    if (!symbol) {
      return {};
    }
    return {
      code: countryInfo.currency_code,
      symbol: (0,build_module/* decodeEntities */.S)(symbol),
      symbolPosition: countryInfo.currency_pos,
      thousandSeparator: countryInfo.thousand_sep,
      decimalSeparator: countryInfo.decimal_sep,
      precision: countryInfo.num_decimals
    };
  }
  setCurrency(currencySetting);
  return {
    getCurrencyConfig: () => {
      return {
        ...currency
      };
    },
    getDataForCountry,
    setCurrency,
    formatAmount,
    formatCurrency,
    getPriceFormat,
    /**
     * Get the rounded decimal value of a number at the precision used for the current currency.
     * This is a work-around for fraction-cents, meant to be used like `wc_format_decimal`
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {number} The original number rounded to a decimal point
     */
    formatDecimal(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }
      if (Number.isNaN(number)) {
        return 0;
      }
      const {
        precision
      } = currency;
      return Math.round(number * Math.pow(10, precision)) / Math.pow(10, precision);
    },
    /**
     * Get the string representation of a floating point number to the precision used by the current currency.
     * This is different from `formatAmount` by not returning the currency symbol.
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {string}               The original number rounded to a decimal point
     */
    formatDecimalString(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }
      if (Number.isNaN(number)) {
        return '';
      }
      const {
        precision
      } = currency;
      return number.toFixed(precision);
    },
    /**
     * Render a currency for display in a component.
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {Node|string} The number formatted as currency and rendered for display.
     */
    render(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }
      if (number < 0) {
        return /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "is-negative",
          children: formatAmount(number)
        });
      }
      return formatAmount(number);
    }
  };
};
const utils_CurrencyFactory = CurrencyFactoryBase;

/**
 * Returns currency data by country/region. Contains code, symbol, position, thousands separator, decimal separator, and precision.
 *
 * Dev Note: When adding new currencies below, the exchange rate array should also be updated in WooCommerce Admin's `business-details.js`.
 *
 * @deprecated
 * @return {Object} Currency data.
 */
function getCurrencyData() {
  deprecated('getCurrencyData', {
    version: '3.1.0',
    alternative: 'CurrencyFactory.getDataForCountry',
    plugin: 'WooCommerce Admin',
    hint: 'Pass in the country, locale data, and symbol info to use getDataForCountry'
  });

  // See https://github.com/woocommerce/woocommerce-admin/issues/3101.
  return {
    US: {
      code: 'USD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    EU: {
      code: 'EUR',
      symbol: '€',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    IN: {
      code: 'INR',
      symbol: '₹',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    GB: {
      code: 'GBP',
      symbol: '£',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    BR: {
      code: 'BRL',
      symbol: 'R$',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    VN: {
      code: 'VND',
      symbol: '₫',
      symbolPosition: 'right',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 1
    },
    ID: {
      code: 'IDR',
      symbol: 'Rp',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 0
    },
    BD: {
      code: 'BDT',
      symbol: '৳',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 0
    },
    PK: {
      code: 'PKR',
      symbol: '₨',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    RU: {
      code: 'RUB',
      symbol: '₽',
      symbolPosition: 'right',
      thousandSeparator: ' ',
      decimalSeparator: ',',
      precision: 2
    },
    TR: {
      code: 'TRY',
      symbol: '₺',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    MX: {
      code: 'MXN',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    CA: {
      code: 'CAD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    }
  };
}

/**
 * Escape special characters for user input in regex.
 *
 * @param {string} string
 * @return {string} string
 */
const escapeRegExp = string => {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
};

/**
 * Localises a number or numeric string for display, adding the appropriate thousands and decimal separators.
 * For compatibility reasons, it returns the input if it's not a number or a string of numbers.
 */
const localiseMonetaryValue = (config, number) => {
  if (typeof number === 'number') {
    return numberFormat(config, number);
  }
  if (typeof number === 'string') {
    const dot = escapeRegExp(config.decimalSeparator);
    const comma = escapeRegExp(config.thousandSeparator);

    // Regex to match strictly numbers with arbitrary thousands and decimal separators.
    // Example: /^\s*(\d+|\d{1,3}(?:,\d{3})*)(?:\.\d+)?\s*$/ for default config.
    const regex = new RegExp(`^\\s*(\\d+|\\d{1,3}(?:${comma}\\d{3})*)(?:${dot}\\d+)?\\s*$`);
    return number.replace(regex, n => {
      const parsed = parseNumber(config, n);
      return numberFormat(config, parsed);
    });
  }
  return number;
};
const unformatLocalisedMonetaryValue = (config, inputNumber) => {
  if (!inputNumber) {
    throw new Error('Input value is undefined');
  }
  if (Number.isFinite(inputNumber)) {
    return inputNumber;
  }
  if (typeof inputNumber !== 'string') {
    throw new Error('Input value is not a number or a numeric string');
  }

  // Brackets signal a formula. Avoid unformatting these values.
  if (inputNumber.includes('[') && inputNumber.includes(']')) {
    throw new Error('Input value contains formula');
  }

  // Check if the string contains any non-numeric characters except allowed separators and whitespace
  const allowedChars = new RegExp(`^\\s*[0-9${escapeRegExp(config.thousandSeparator)}${escapeRegExp(config.decimalSeparator)}]+\\s*$`);
  if (!allowedChars.test(inputNumber)) {
    throw new Error('Input value contains non-numeric characters and is not a formula');
  }
  if (
  // check that there is only 1 decimal separator and it is not to the left of
  // the thousands separator if there is a thousands separator in the value
  inputNumber.split(config.decimalSeparator).length > 2 || inputNumber.includes(config.thousandSeparator) && inputNumber.includes(config.decimalSeparator) && inputNumber.indexOf(config.decimalSeparator) <= inputNumber.indexOf(config.thousandSeparator)) {
    throw new Error('Invalid decimal separator');
  }
  const unformattedValue = inputNumber.replace(new RegExp(escapeRegExp(config.thousandSeparator), 'g'), '').replace(config.decimalSeparator, '.');
  return Number(unformattedValue);
};
try {
    // @ts-ignore
    getCurrencyData.displayName = "getCurrencyData";
    // @ts-ignore
    getCurrencyData.__docgenInfo = { "description": "Returns currency data by country/region. Contains code, symbol, position, thousands separator, decimal separator, and precision.\n\nDev Note: When adding new currencies below, the exchange rate array should also be updated in WooCommerce Admin's `business-details.js`.", "displayName": "getCurrencyData", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/currency/src/utils.tsx#getCurrencyData"] = { docgenInfo: getCurrencyData.__docgenInfo, name: "getCurrencyData", path: "../../packages/js/currency/src/utils.tsx#getCurrencyData" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    utils_CurrencyFactory.displayName = "CurrencyFactory";
    // @ts-ignore
    utils_CurrencyFactory.__docgenInfo = { "description": "", "displayName": "CurrencyFactory", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/currency/src/utils.tsx#CurrencyFactory"] = { docgenInfo: utils_CurrencyFactory.__docgenInfo, name: "CurrencyFactory", path: "../../packages/js/currency/src/utils.tsx#CurrencyFactory" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    localiseMonetaryValue.displayName = "localiseMonetaryValue";
    // @ts-ignore
    localiseMonetaryValue.__docgenInfo = { "description": "Localises a number or numeric string for display, adding the appropriate thousands and decimal separators.\nFor compatibility reasons, it returns the input if it's not a number or a string of numbers.", "displayName": "localiseMonetaryValue", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/currency/src/utils.tsx#localiseMonetaryValue"] = { docgenInfo: localiseMonetaryValue.__docgenInfo, name: "localiseMonetaryValue", path: "../../packages/js/currency/src/utils.tsx#localiseMonetaryValue" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.33.1/node_modules/@wordpress/hooks/build-module/index.js + 10 modules
var hooks_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.33.1/node_modules/@wordpress/hooks/build-module/index.js");
// EXTERNAL MODULE: ./setting.mock.js
var setting_mock = __webpack_require__("./setting.mock.js");
;// ../../packages/js/currency/src/currency-context.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

const CURRENCY = (0,setting_mock/* getSetting */.P)('currency');
const appCurrency = utils_CurrencyFactory(CURRENCY);
const getFilteredCurrencyInstance = query => {
  const config = appCurrency.getCurrencyConfig();
  /**
   * Filter the currency context. This affects all WooCommerce Admin currency formatting.
   *
   * @filter woocommerce_admin_report_currency
   * @param {Object} config Currency configuration.
   * @param {Object} query  Url query parameters.
   */
  const filteredConfig = applyFilters('woocommerce_admin_report_currency', config, query);
  return CurrencyFactory(filteredConfig);
};
const CurrencyContext = (0,react.createContext)(appCurrency // default value
);
;// ../../packages/js/currency/src/index.ts
/**
 * Internal dependencies
 */

/* harmony default export */ const src = ((/* unused pure expression or super */ null && (CurrencyFactory)));



/***/ })

}]);