"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[686],{

/***/ "../../packages/js/components/src/dynamic-form/stories/index.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ index_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/form/form.tsx + 1 modules
var form_form = __webpack_require__("../../packages/js/components/src/form/form.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/text-control/index.js
var text_control = __webpack_require__("../../packages/js/components/src/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/dynamic-form/field-types/field-text.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const TextField = ({
  field,
  type = 'text',
  ...props
}) => {
  const {
    label,
    description
  } = field;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
    type: type,
    title: description,
    label: label,
    ...props
  });
};
try {
    // @ts-ignore
    TextField.displayName = "TextField";
    // @ts-ignore
    TextField.__docgenInfo = { "description": "", "displayName": "TextField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-text.tsx#TextField"] = { docgenInfo: TextField.__docgenInfo, name: "TextField", path: "../../packages/js/components/src/dynamic-form/field-types/field-text.tsx#TextField" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/dynamic-form/field-types/field-password.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const PasswordField = props => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(TextField, {
    ...props,
    type: "password"
  });
};
try {
    // @ts-ignore
    PasswordField.displayName = "PasswordField";
    // @ts-ignore
    PasswordField.__docgenInfo = { "description": "", "displayName": "PasswordField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-password.tsx#PasswordField"] = { docgenInfo: PasswordField.__docgenInfo, name: "PasswordField", path: "../../packages/js/components/src/dynamic-form/field-types/field-password.tsx#PasswordField" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
;// ../../packages/js/components/src/dynamic-form/field-types/field-checkbox.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

const CheckboxField = ({
  field,
  onChange,
  ...props
}) => {
  const {
    label,
    description
  } = field;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control/* default */.A, {
    onChange: val => onChange(val),
    title: description,
    label: label,
    ...props
  });
};
try {
    // @ts-ignore
    CheckboxField.displayName = "CheckboxField";
    // @ts-ignore
    CheckboxField.__docgenInfo = { "description": "", "displayName": "CheckboxField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-checkbox.tsx#CheckboxField"] = { docgenInfo: CheckboxField.__docgenInfo, name: "CheckboxField", path: "../../packages/js/components/src/dynamic-form/field-types/field-checkbox.tsx#CheckboxField" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
;// ../../packages/js/components/src/dynamic-form/field-types/field-select.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const transformOptions = options => Object.entries(options).map(([key, value]) => ({
  key,
  label: value,
  value: {
    id: key
  }
}));
const SelectField = ({
  field,
  ...props
}) => {
  const {
    description,
    label,
    options = {}
  } = field;
  const transformedOptions = (0,react.useMemo)(() => transformOptions(options), [options]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
    title: description,
    label: label,
    options: transformedOptions,
    ...props
  });
};
try {
    // @ts-ignore
    SelectField.displayName = "SelectField";
    // @ts-ignore
    SelectField.__docgenInfo = { "description": "", "displayName": "SelectField", "props": { "field": { "defaultValue": null, "description": "", "name": "field", "required": true, "type": { "name": "Field" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/field-types/field-select.tsx#SelectField"] = { docgenInfo: SelectField.__docgenInfo, name: "SelectField", path: "../../packages/js/components/src/dynamic-form/field-types/field-select.tsx#SelectField" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/dynamic-form/dynamic-form.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



const fieldTypeMap = {
  text: TextField,
  password: PasswordField,
  checkbox: CheckboxField,
  select: SelectField,
  default: TextField
};
const getInitialConfigValues = fields => fields.reduce((data, field) => ({
  ...data,
  [field.id]: field.type === 'checkbox' ? field.value === 'yes' : field.value
}), {});
const DynamicForm = ({
  fields: baseFields = [],
  isBusy = false,
  onSubmit = () => {},
  onChange = () => {},
  validate = () => ({}),
  submitLabel = (0,build_module.__)('Proceed', 'woocommerce')
}) => {
  // Support accepting fields in the format provided by the API (object), but transform to Array
  const fields = baseFields instanceof Array ? baseFields : Object.values(baseFields);
  const initialValues = (0,react.useMemo)(() => getInitialConfigValues(fields), [fields]);
  return (
    /*#__PURE__*/
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (0,jsx_runtime.jsx)(form_form/* Form */.l, {
      initialValues: initialValues,
      onChange: onChange,
      onSubmit: onSubmit,
      validate: validate,
      children: ({
        getInputProps,
        handleSubmit
      }) => {
        return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "woocommerce-component_dynamic-form",
          children: [fields.map(field => {
            if (field.type && !(field.type in fieldTypeMap)) {
              /* eslint-disable no-console */
              console.warn(`Field type of ${field.type} not current supported in DynamicForm component`);
              /* eslint-enable no-console */
              return null;
            }
            const Control = fieldTypeMap[field.type || 'default'];
            return /*#__PURE__*/(0,jsx_runtime.jsx)(Control, {
              field: field,
              ...getInputProps(field.id)
            }, field.id);
          }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            isPrimary: true,
            isBusy: isBusy,
            onClick: () => {
              handleSubmit();
            },
            children: submitLabel
          })]
        });
      }
    })
  );
};
try {
    // @ts-ignore
    DynamicForm.displayName = "DynamicForm";
    // @ts-ignore
    DynamicForm.__docgenInfo = { "description": "", "displayName": "DynamicForm", "props": { "fields": { "defaultValue": { value: "[]" }, "description": "", "name": "fields", "required": false, "type": { "name": "Field[] | { [key: string]: Field; }" } }, "validate": { "defaultValue": { value: "() => ( {} )" }, "description": "", "name": "validate", "required": false, "type": { "name": "(values: Record<string, string>) => Record<string, string>" } }, "isBusy": { "defaultValue": { value: "false" }, "description": "", "name": "isBusy", "required": false, "type": { "name": "boolean" } }, "onSubmit": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onSubmit", "required": false, "type": { "name": "((values: Record<string, string>) => void)" } }, "onChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onChange", "required": false, "type": { "name": "((value: { name: string; value: unknown; }, values: Record<string, string>, result: boolean) => void)" } }, "submitLabel": { "defaultValue": { value: "__( 'Proceed', 'woocommerce' )" }, "description": "", "name": "submitLabel", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/dynamic-form/dynamic-form.tsx#DynamicForm"] = { docgenInfo: DynamicForm.__docgenInfo, name: "DynamicForm", path: "../../packages/js/components/src/dynamic-form/dynamic-form.tsx#DynamicForm" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/dynamic-form/stories/index.story.js
/**
 * External dependencies
 */



const fields = [{
  id: 'user_name',
  label: 'Username',
  description: 'This is your username.',
  type: 'text',
  value: '',
  default: '',
  tip: 'This is your username.',
  placeholder: ''
}, {
  id: 'pass_phrase',
  label: 'Passphrase',
  description: '* Required. Needed to ensure the data passed through is secure.',
  type: 'password',
  value: '',
  default: '',
  tip: '* Required. Needed to ensure the data passed through is secure.',
  placeholder: ''
}, {
  id: 'button_type',
  label: 'Button Type',
  description: 'Select the button type you would like to show.',
  type: 'select',
  value: 'buy',
  default: 'buy',
  tip: 'Select the button type you would like to show.',
  placeholder: '',
  options: {
    default: 'Default',
    buy: 'Buy',
    donate: 'Donate',
    branded: 'Branded',
    custom: 'Custom'
  }
}, {
  id: 'checkbox_sample',
  label: 'Checkbox style',
  description: 'This is an example checkbox field.',
  type: 'checkbox',
  value: 'no',
  default: 'no',
  tip: 'This is an example checkbox field.',
  placeholder: ''
}];
const getField = fieldId => fields.find(field => field.id === fieldId);
const validate = values => {
  const errors = {};
  for (const [key, value] of Object.entries(values)) {
    const field = getField(key);
    if (!(value || field.type === 'checkbox')) {
      errors[key] = `Please enter your ${field.label.toLowerCase()}`;
    }
  }
  return errors;
};
const DynamicExample = () => {
  const [submitted, setSubmitted] = (0,react.useState)(null);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(DynamicForm, {
      fields: fields,
      onSubmit: values => setSubmitted(values),
      validate: validate
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("h4", {
      children: "Submitted:"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
      children: submitted ? JSON.stringify(submitted, null, 3) : 'None'
    })]
  });
};
const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(DynamicExample, {});
/* harmony default export */ const index_story = ({
  title: 'Components/DynamicForm',
  component: DynamicForm
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <DynamicExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   M: () => (/* binding */ maybeWarnDeprecated36pxSize)
/* harmony export */ });
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");

function maybeWarnDeprecated36pxSize({
  componentName,
  __next40pxDefaultSize,
  size,
  __shouldNotWarnDeprecated36pxSize
}) {
  if (__shouldNotWarnDeprecated36pxSize || __next40pxDefaultSize || size !== void 0 && size !== "default") {
    return;
  }
  (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(`36px default size for wp.components.${componentName}`, {
    since: "6.8",
    version: "7.1",
    hint: "Set the `__next40pxDefaultSize` prop to true to start opting into the new default size, which will become the default in a future version."
  });
}

//# sourceMappingURL=deprecated-36px-size.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GB: () => (/* binding */ ensureNumber),
/* harmony export */   J5: () => (/* binding */ isValueDefined),
/* harmony export */   r6: () => (/* binding */ isValueEmpty)
/* harmony export */ });
/* unused harmony exports getDefinedValue, stringToNumber */
function isValueDefined(value) {
  return value !== void 0 && value !== null;
}
function isValueEmpty(value) {
  const isEmptyString = value === "";
  return !isValueDefined(value) || isEmptyString;
}
function getDefinedValue(values = [], fallbackValue) {
  var _values$find;
  return (_values$find = values.find(isValueDefined)) !== null && _values$find !== void 0 ? _values$find : fallbackValue;
}
const stringToNumber = (value) => {
  return parseFloat(value);
};
const ensureNumber = (value) => {
  return typeof value === "string" ? stringToNumber(value) : value;
};

//# sourceMappingURL=values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/icons/src/icon/index.ts

var icon_default = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(
  ({ icon, size = 24, ...props }, ref) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
      width: size,
      height: size,
      ...props,
      ref
    });
  }
);

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ reset_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/icons/src/library/reset.tsx


var reset_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M7 11.5h10V13H7z" }) });

//# sourceMappingURL=reset.mjs.map


/***/ }),

/***/ "../../packages/js/components/src/text-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * An input field use for text inputs in forms.
 */

const TextControl = (0,_wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(class extends _wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Component {
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
      isFocused
    } = this.state;
    const {
      className,
      onClick,
      ...otherProps
    } = this.props;
    const {
      label,
      value,
      disabled
    } = otherProps;
    const isEmpty = value === '';
    const isActive = isFocused && !disabled;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)('muriel-component', 'muriel-input-text', className, {
        disabled,
        empty: isEmpty,
        active: isActive,
        'with-value': !isEmpty
      }),
      placeholder: label,
      onClick: event => this.handleOnClick(event, onClick),
      onFocus: () => this.setState({
        isFocused: true
      }),
      ...otherProps
    });
  }
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TextControl);

/***/ }),

/***/ "../../packages/js/components/src/form/form.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


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


/***/ })

}]);