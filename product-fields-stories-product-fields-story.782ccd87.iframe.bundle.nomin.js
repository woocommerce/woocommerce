"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3358],{

/***/ "../../packages/js/components/src/product-fields/stories/product-fields.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  ToggleWithTooltip: () => (/* binding */ ToggleWithTooltip),
  "default": () => (/* binding */ product_fields_story)
});

// NAMESPACE OBJECT: ../../packages/js/components/src/product-fields/store/selectors.ts
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  getProductField: () => (getProductField),
  getRegisteredProductFields: () => (getRegisteredProductFields)
});

// NAMESPACE OBJECT: ../../packages/js/components/src/product-fields/store/actions.ts
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  registerProductField: () => (registerProductField)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.mjs + 2 modules
var registry = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/components/registry-provider/context.mjs
var context = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/components/registry-provider/context.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.mjs + 10 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/index.mjs");
;// ../../packages/js/components/src/product-fields/store/action-types.ts
let TYPES = /*#__PURE__*/function (TYPES) {
  TYPES["REGISTER_FIELD"] = "REGISTER_FIELD";
  return TYPES;
}({});
/* harmony default export */ const action_types = (TYPES);
;// ../../packages/js/components/src/product-fields/store/reducer.ts
/**
 * Internal dependencies
 */

const reducer = (state = {
  fields: {}
}, payload) => {
  if (payload && 'type' in payload) {
    switch (payload.type) {
      case action_types.REGISTER_FIELD:
        return {
          ...state,
          fields: {
            ...state.fields,
            [payload.field.name]: payload.field
          }
        };
      default:
        return state;
    }
  }
  return state;
};
/* harmony default export */ const store_reducer = (reducer);
// EXTERNAL MODULE: ../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js
var memoize_one_esm = __webpack_require__("../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js");
;// ../../packages/js/components/src/product-fields/store/selectors.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

function getProductField(state, name) {
  return state.fields[name] || null;
}
const getRegisteredProductFields = (0,memoize_one_esm/* default */.A)(state => Object.keys(state.fields), ([newState], [oldState]) => {
  return newState.fields === oldState.fields;
});
;// ../../packages/js/components/src/product-fields/store/actions.ts
/**
 * Internal dependencies
 */

function registerProductField(field) {
  return {
    type: action_types.REGISTER_FIELD,
    field
  };
}
;// ../../packages/js/components/src/product-fields/store/constants.ts
const STORE_NAME = 'wc/admin/product/fields';
;// ../../packages/js/components/src/product-fields/store/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




const store = (0,redux_store/* default */.A)(STORE_NAME, {
  reducer: store_reducer,
  selectors: selectors_namespaceObject,
  actions: actions_namespaceObject
});
(0,build_module/* register */.kz)(store);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/select.mjs
var build_module_select = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/select.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/index.js + 8 modules
var input_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/product-fields/api/render.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


// eslint-disable-next-line @typescript-eslint/no-explicit-any
function renderField(name, props) {
  const fieldConfig = (0,build_module_select/* select */.L)(store).getProductField(name);
  if (fieldConfig.render) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(fieldConfig.render, {
      ...props
    });
  }
  if (fieldConfig.type) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(input_control/* default */.Ay, {
      type: fieldConfig.type,
      ...props
    });
  }
  return null;
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/dispatch.mjs
var dispatch = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/dispatch.mjs");
;// ../../packages/js/components/src/product-fields/api/registration.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

/**
 * Registers a new product field provided a unique name and an object defining its
 * behavior. Once registered, the field is made available to use with the product form API.
 *
 * @param {string|Object} fieldName Field name.
 * @param {Object}        settings  Field settings.
 *
 * @example
 * ```js
 * import { registerProductField } from '@woocommerce/components'
 *
 * registerProductFieldType( 'attributes-field', {
 * } );
 * ```
 */
function registration_registerProductField(fieldName, settings) {
  if ((0,build_module_select/* select */.L)(store).getProductField(fieldName)) {
    // eslint-disable-next-line no-console
    console.error('Product Field "' + fieldName + '" is already registered.');
    return;
  }
  void (0,dispatch/* dispatch */.J)(store).registerProductField({
    attributes: {},
    ...settings
  });
  return (0,build_module_select/* select */.L)(store).getProductField(fieldName);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js + 4 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js");
;// ../../packages/js/components/src/product-fields/fields/basic-select-control/render.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const SelectControlField = ({
  label,
  value,
  onChange,
  multiple,
  options = []
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
      multiple: multiple,
      label: label,
      options: options,
      onChange: onChange,
      value: value
    })
  });
};
/* harmony default export */ const basic_select_control_render = (SelectControlField);
try {
    // @ts-ignore
    render.displayName = "render";
    // @ts-ignore
    render.__docgenInfo = { "description": "", "displayName": "render", "props": { "value": { "defaultValue": null, "description": "", "name": "value", "required": true, "type": { "name": "string | string[]" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: string | string[]) => void" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": null, "description": "", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "options": { "defaultValue": { value: "[]" }, "description": "", "name": "options", "required": false, "type": { "name": "readonly ({ label: string; value: string; } & Omit<OptionHTMLAttributes<HTMLOptionElement>, \"label\" | \"value\">)[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/product-fields/fields/basic-select-control/render.tsx#render"] = { docgenInfo: render.__docgenInfo, name: "render", path: "../../packages/js/components/src/product-fields/fields/basic-select-control/render.tsx#render" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/product-fields/fields/basic-select-control/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const basicSelectControlSettings = {
  name: 'basic-select-control',
  render: basic_select_control_render
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
;// ../../packages/js/components/src/product-fields/fields/checkbox/render.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const CheckboxField = ({
  label,
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control/* default */.A, {
    label: label,
    onChange: onChange,
    checked: value
  });
};
/* harmony default export */ const checkbox_render = (CheckboxField);
try {
    // @ts-ignore
    render.displayName = "render";
    // @ts-ignore
    render.__docgenInfo = { "description": "", "displayName": "render", "props": { "value": { "defaultValue": null, "description": "", "name": "value", "required": true, "type": { "name": "boolean" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: boolean) => void" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/product-fields/fields/checkbox/render.tsx#render"] = { docgenInfo: render.__docgenInfo, name: "render", path: "../../packages/js/components/src/product-fields/fields/checkbox/render.tsx#render" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/product-fields/fields/checkbox/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const checkboxSettings = {
  name: 'checkbox',
  render: checkbox_render
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/radio-control/index.js
var radio_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/radio-control/index.js");
;// ../../packages/js/components/src/product-fields/fields/radio/render.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const RadioField = ({
  label,
  value,
  onChange,
  options = []
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(radio_control/* default */.A, {
    label: label,
    options: options,
    onChange: onChange,
    selected: value
  });
};
/* harmony default export */ const radio_render = (RadioField);
try {
    // @ts-ignore
    render.displayName = "render";
    // @ts-ignore
    render.__docgenInfo = { "description": "", "displayName": "render", "props": { "value": { "defaultValue": null, "description": "", "name": "value", "required": true, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: string) => void" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "options": { "defaultValue": { value: "[]" }, "description": "", "name": "options", "required": false, "type": { "name": "{ label: string; value: string; }[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/product-fields/fields/radio/render.tsx#render"] = { docgenInfo: render.__docgenInfo, name: "render", path: "../../packages/js/components/src/product-fields/fields/radio/render.tsx#render" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/product-fields/fields/radio/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const radioSettings = {
  name: 'radio',
  render: radio_render
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
;// ../../packages/js/components/src/product-fields/fields/text/render.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const TextField = ({
  label,
  value,
  onChange
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
    label: label,
    onChange: onChange,
    value: value
  });
};
/* harmony default export */ const text_render = (TextField);
try {
    // @ts-ignore
    render.displayName = "render";
    // @ts-ignore
    render.__docgenInfo = { "description": "", "displayName": "render", "props": { "value": { "defaultValue": null, "description": "", "name": "value", "required": true, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: string) => void" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/product-fields/fields/text/render.tsx#render"] = { docgenInfo: render.__docgenInfo, name: "render", path: "../../packages/js/components/src/product-fields/fields/text/render.tsx#render" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/product-fields/fields/text/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const textSettings = {
  name: 'text',
  render: text_render
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-control/index.js + 2 modules
var toggle_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-control/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/tooltip/tooltip.tsx
var tooltip_tooltip = __webpack_require__("../../packages/js/components/src/tooltip/tooltip.tsx");
;// ../../packages/js/components/src/product-fields/fields/toggle/render.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const ToggleField = ({
  label,
  value,
  onChange,
  tooltip,
  disabled = false
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(toggle_control/* default */.A, {
    label: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [label, tooltip && /*#__PURE__*/(0,jsx_runtime.jsx)(tooltip_tooltip/* Tooltip */.m, {
        text: tooltip
      })]
    }),
    checked: value,
    onChange: onChange,
    disabled: disabled
  });
};
/* harmony default export */ const toggle_render = (ToggleField);
try {
    // @ts-ignore
    render.displayName = "render";
    // @ts-ignore
    render.__docgenInfo = { "description": "", "displayName": "render", "props": { "value": { "defaultValue": null, "description": "", "name": "value", "required": true, "type": { "name": "boolean" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: boolean) => void" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string" } }, "disabled": { "defaultValue": { value: "false" }, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "tooltip": { "defaultValue": null, "description": "", "name": "tooltip", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/product-fields/fields/toggle/render.tsx#render"] = { docgenInfo: render.__docgenInfo, name: "render", path: "../../packages/js/components/src/product-fields/fields/toggle/render.tsx#render" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/product-fields/fields/toggle/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const toggleSettings = {
  name: 'toggle',
  render: toggle_render
};
;// ../../packages/js/components/src/product-fields/fields/index.ts
/**
 * Internal dependencies
 */






const getAllProductFields = () => [...['number'].map(type => ({
  name: type,
  type
})), textSettings, toggleSettings, radioSettings, basicSelectControlSettings, checkboxSettings].filter(Boolean);
const registerCoreProductFields = (fields = getAllProductFields()) => {
  fields.forEach(field => {
    registration_registerProductField(field.name, field);
  });
};
;// ../../packages/js/components/src/product-fields/stories/product-fields.story.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




const product_fields_story_registry = (0,registry/* createRegistry */.I)();
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
product_fields_story_registry.register(store);
registerCoreProductFields();
const fieldConfigs = [{
  name: 'text-field',
  type: 'text',
  label: 'Text field'
}, {
  name: 'number-field',
  type: 'number',
  label: 'Number field'
}, {
  name: 'toggle-field',
  type: 'toggle',
  label: 'Toggle field'
}, {
  name: 'checkbox-field',
  type: 'checkbox',
  label: 'Checkbox field'
}, {
  name: 'radio-field',
  type: 'radio',
  label: 'Radio field',
  options: [{
    label: 'Option',
    value: 'option'
  }, {
    label: 'Option 2',
    value: 'option2'
  }, {
    label: 'Option 3',
    value: 'option3'
  }]
}, {
  name: 'basic-select-control-field',
  type: 'basic-select-control',
  label: 'Basic select control field',
  options: [{
    label: 'Option',
    value: 'option'
  }, {
    label: 'Option 2',
    value: 'option2'
  }, {
    label: 'Option 3',
    value: 'option3'
  }]
}];
const RenderField = () => {
  const [selectedField, setSelectedField] = (0,react.useState)(fieldConfigs[0].name || undefined);
  const [value, setValue] = (0,react.useState)();
  const handleChange = event => {
    setSelectedField(event.target.value);
  };
  const selectedFieldConfig = fieldConfigs.find(f => f.name === selectedField);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("select", {
      value: selectedField,
      onChange: handleChange,
      children: fieldConfigs.map(field => /*#__PURE__*/(0,jsx_runtime.jsx)("option", {
        value: field.name,
        children: field.label
      }, field.name))
    }), selectedFieldConfig && renderField(selectedFieldConfig.type, {
      value,
      onChange: setValue,
      ...selectedFieldConfig
    })]
  });
};
const Basic = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(context/* default */.Ay, {
    value: product_fields_story_registry,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(RenderField, {})
  });
};
const ToggleWithTooltip = () => {
  const [value, setValue] = (0,react.useState)();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(context/* default */.Ay, {
    value: product_fields_story_registry,
    children: renderField('toggle', {
      value,
      onChange: setValue,
      name: 'toggle',
      label: 'Toggle with Tooltip',
      tooltip: 'This is a sample tooltip'
    })
  });
};
/* harmony default export */ const product_fields_story = ({
  title: 'Experimental/product-fields',
  component: Basic
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <RegistryProvider value={registry}>\n            <RenderField />\n        </RegistryProvider>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
ToggleWithTooltip.parameters = {
  ...ToggleWithTooltip.parameters,
  docs: {
    ...ToggleWithTooltip.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [value, setValue] = useState();\n  return <RegistryProvider value={registry}>\n            {renderField('toggle', {\n      value,\n      onChange: setValue,\n      name: 'toggle',\n      label: 'Toggle with Tooltip',\n      tooltip: 'This is a sample tooltip'\n    })}\n        </RegistryProvider>;\n}",
      ...ToggleWithTooltip.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/tooltip/tooltip.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   m: () => (/* binding */ Tooltip)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/help.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */







const Tooltip = ({
  children = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A
  }),
  className = '',
  helperText = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Help', 'woocommerce'),
  position = 'top center',
  text
}) => {
  const [isPopoverVisible, setIsPopoverVisible] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(false);
  const uniqueIdentifier = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)(Tooltip, 'product_tooltip');
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)('woocommerce-tooltip', uniqueIdentifier),
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .Ay, {
        className: (0,clsx__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)('woocommerce-tooltip__button', className),
        onKeyDown: event => {
          if (event.key !== 'Enter') {
            return;
          }
          setIsPopoverVisible(true);
        },
        onClick: () => setIsPopoverVisible(!isPopoverVisible),
        label: helperText,
        children: children
      }), isPopoverVisible && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .Ay, {
        focusOnMount: true,
        position: position,
        inline: true,
        className: "woocommerce-tooltip__text",
        onFocusOutside: event => {
          if (event.currentTarget?.classList.contains(uniqueIdentifier)) {
            return;
          }
          setIsPopoverVisible(false);
        },
        onKeyDown: event => {
          if (event.key !== 'Escape') {
            return;
          }
          setIsPopoverVisible(false);
        },
        children: text
      })]
    })
  });
};
try {
    // @ts-ignore
    Tooltip.displayName = "Tooltip";
    // @ts-ignore
    Tooltip.__docgenInfo = { "description": "", "displayName": "Tooltip", "props": { "helperText": { "defaultValue": { value: "__( 'Help', 'woocommerce' )" }, "description": "", "name": "helperText", "required": false, "type": { "name": "string" } }, "position": { "defaultValue": { value: "top center" }, "description": "", "name": "position", "required": false, "type": { "name": "enum", "value": [{ "value": "\"top left\"" }, { "value": "\"top right\"" }, { "value": "\"top center\"" }, { "value": "\"middle left\"" }, { "value": "\"middle right\"" }, { "value": "\"middle center\"" }, { "value": "\"bottom left\"" }, { "value": "\"bottom right\"" }, { "value": "\"bottom center\"" }] } }, "text": { "defaultValue": null, "description": "", "name": "text", "required": true, "type": { "name": "string | Element" } }, "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tooltip/tooltip.tsx#Tooltip"] = { docgenInfo: Tooltip.__docgenInfo, name: "Tooltip", path: "../../packages/js/components/src/tooltip/tooltip.tsx#Tooltip" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);