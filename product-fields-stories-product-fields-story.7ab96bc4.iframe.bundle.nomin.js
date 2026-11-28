"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3358],{

/***/ "../../packages/js/components/src/product-fields/stories/product-fields.story.tsx"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  ToggleWithTooltip: () => (/* binding */ ToggleWithTooltip),
  "default": () => (/* binding */ product_fields_story)
});

// NAMESPACE OBJECT (decoupled): ../../packages/js/components/src/product-fields/store/selectors.ts
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  getProductField: () => (getProductField),
  getRegisteredProductFields: () => (getRegisteredProductFields)
});

// NAMESPACE OBJECT (decoupled): ../../packages/js/components/src/product-fields/store/actions.ts
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  registerProductField: () => (registerProductField)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.js + 2 modules
var registry = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.js
var default_registry = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.js");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/components/registry-provider/context.js


const Context = (0,react.createContext)(default_registry/* default */.A);
Context.displayName = "RegistryProviderContext";
const { Consumer, Provider } = Context;
const RegistryConsumer = (/* unused pure expression or super */ null && (Consumer));
var context_default = Provider;

//# sourceMappingURL=context.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.js + 19 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/index.js");
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
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/select.js

function select_select(storeNameOrDescriptor) {
  return default_registry/* default */.A.select(storeNameOrDescriptor);
}

//# sourceMappingURL=select.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/input-control/index.js + 8 modules
var input_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/input-control/index.js");
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
  const fieldConfig = select_select(store).getProductField(name);
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/dispatch.js
var dispatch = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/dispatch.js");
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
  if (select_select(store).getProductField(fieldName)) {
    // eslint-disable-next-line no-console
    console.error('Product Field "' + fieldName + '" is already registered.');
    return;
  }
  void (0,dispatch/* dispatch */.J)(store).registerProductField({
    attributes: {},
    ...settings
  });
  return select_select(store).getProductField(fieldName);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/select-control/index.js + 4 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/select-control/index.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/radio-control/index.js
var radio_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/radio-control/index.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/text-control/index.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@typ_4a231fcc775935778c7b100ed42f0c1e/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@typ_4a231fcc775935778c7b100ed42f0c1e/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js
var hook = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex-block/hook.js


function useFlexBlock(props) {
  const otherProps = (0,use_context_system/* useContextSystem */.A)(props, "FlexBlock");
  const flexItemProps = (0,hook/* useFlexItem */.K)({
    isBlock: true,
    ...otherProps
  });
  return flexItemProps;
}

//# sourceMappingURL=hook.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex-block/component.js




function UnconnectedFlexBlock(props, forwardedRef) {
  const flexBlockProps = useFlexBlock(props);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    ...flexBlockProps,
    ref: forwardedRef
  });
}
const FlexBlock = (0,context_connect/* contextConnect */.KZ)(UnconnectedFlexBlock, "FlexBlock");
var component_default = FlexBlock;

//# sourceMappingURL=component.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/form-toggle/index.js
var form_toggle = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/form-toggle/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/component.js
var h_stack_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/space.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/toggle-control/index.js












function UnforwardedToggleControl({
  __nextHasNoMarginBottom,
  label,
  checked,
  help,
  className,
  onChange,
  disabled
}, ref) {
  function onChangeToggle(event) {
    onChange(event.target.checked);
  }
  const instanceId = (0,use_instance_id/* default */.A)(ToggleControl);
  const id = `inspector-toggle-control-${instanceId}`;
  const cx = (0,use_cx/* useCx */.l)();
  const classes = cx("components-toggle-control", className, !__nextHasNoMarginBottom && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
    marginBottom: (0,space/* space */.x)(3)
  },  true ? "" : 0,  true ? "" : 0));
  if (!__nextHasNoMarginBottom) {
    (0,deprecated_build_module/* default */.A)("Bottom margin styles for wp.components.ToggleControl", {
      since: "6.7",
      version: "7.0",
      hint: "Set the `__nextHasNoMarginBottom` prop to true to start opting into the new styles, which will become the default in a future version."
    });
  }
  let describedBy, helpLabel;
  if (help) {
    if (typeof help === "function") {
      if (checked !== void 0) {
        helpLabel = help(checked);
      }
    } else {
      helpLabel = help;
    }
    if (helpLabel) {
      describedBy = id + "__help";
    }
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    id,
    help: helpLabel && /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
      className: "components-toggle-control__help",
      children: helpLabel
    }),
    className: classes,
    __nextHasNoMarginBottom: true,
    children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(h_stack_component/* default */.A, {
      justify: "flex-start",
      spacing: 2,
      children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(form_toggle/* default */.Ay, {
        id,
        checked,
        onChange: onChangeToggle,
        "aria-describedby": describedBy,
        disabled,
        ref
      }), /* @__PURE__ */ (0,jsx_runtime.jsx)(component_default, {
        as: "label",
        htmlFor: id,
        className: (0,clsx/* default */.A)("components-toggle-control__label", {
          "is-disabled": disabled
        }),
        children: label
      })]
    })
  });
}
const ToggleControl = (0,react.forwardRef)(UnforwardedToggleControl);
var toggle_control_default = ToggleControl;

//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: ../../packages/js/components/src/tooltip/tooltip.tsx + 1 modules
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
  return /*#__PURE__*/(0,jsx_runtime.jsx)(toggle_control_default, {
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
  return /*#__PURE__*/(0,jsx_runtime.jsx)(context_default, {
    value: product_fields_story_registry,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(RenderField, {})
  });
};
const ToggleWithTooltip = () => {
  const [value, setValue] = (0,react.useState)();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(context_default, {
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

/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/checkbox-control/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ checkbox_control_default)
});

// UNUSED EXPORTS: CheckboxControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs
var use_ref_effect = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@ty_86928fcf4ee49b0fd065dc6175a48ea6/node_modules/@wordpress/icons/build-module/icon/index.mjs
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@ty_86928fcf4ee49b0fd065dc6175a48ea6/node_modules/@wordpress/icons/build-module/icon/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@ty_86928fcf4ee49b0fd065dc6175a48ea6/node_modules/@wordpress/icons/build-module/library/reset.mjs
var library_reset = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@ty_86928fcf4ee49b0fd065dc6175a48ea6/node_modules/@wordpress/icons/build-module/library/reset.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.57._32fe7e38935c407cea8bd9e57e6c56c9/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.57._32fe7e38935c407cea8bd9e57e6c56c9/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@ty_86928fcf4ee49b0fd065dc6175a48ea6/node_modules/@wordpress/icons/build-module/library/check.mjs
// packages/icons/src/library/check.tsx


var check_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M16.5 7.5 10 13.9l-2.5-2.4-1 1 3.5 3.6 7.5-7.6z" }) });

//# sourceMappingURL=check.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/checkbox-control/index.js








function CheckboxControl(props) {
  const {
    __nextHasNoMarginBottom,
    label,
    className,
    heading,
    checked,
    indeterminate,
    help,
    id: idProp,
    onChange,
    onClick,
    ...additionalProps
  } = props;
  if (heading) {
    (0,build_module/* default */.A)("`heading` prop in `CheckboxControl`", {
      alternative: "a separate element to implement a heading",
      since: "5.8"
    });
  }
  const [showCheckedIcon, setShowCheckedIcon] = (0,react.useState)(false);
  const [showIndeterminateIcon, setShowIndeterminateIcon] = (0,react.useState)(false);
  const ref = (0,use_ref_effect/* default */.A)((node) => {
    if (!node) {
      return;
    }
    node.indeterminate = !!indeterminate;
    setShowCheckedIcon(node.matches(":checked"));
    setShowIndeterminateIcon(node.matches(":indeterminate"));
  }, [checked, indeterminate]);
  const id = (0,use_instance_id/* default */.A)(CheckboxControl, "inspector-checkbox-control", idProp);
  const onChangeValue = (event) => onChange(event.target.checked);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "CheckboxControl",
    label: heading,
    id,
    help: help && /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
      className: "components-checkbox-control__help",
      children: help
    }),
    className: (0,clsx/* default */.A)("components-checkbox-control", className),
    children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(component/* default */.A, {
      spacing: 0,
      justify: "start",
      alignment: "top",
      children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)("span", {
        className: "components-checkbox-control__input-container",
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)("input", {
          ref,
          id,
          className: "components-checkbox-control__input",
          type: "checkbox",
          value: "1",
          onChange: onChangeValue,
          checked,
          "aria-describedby": !!help ? id + "__help" : void 0,
          onClick: (event) => {
            event.currentTarget.focus();
            onClick?.(event);
          },
          ...additionalProps
        }), showIndeterminateIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: library_reset/* default */.A,
          className: "components-checkbox-control__indeterminate",
          role: "presentation"
        }) : null, showCheckedIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: check_default,
          className: "components-checkbox-control__checked",
          role: "presentation"
        }) : null]
      }), label && /* @__PURE__ */ (0,jsx_runtime.jsx)("label", {
        className: "components-checkbox-control__label",
        htmlFor: id,
        children: label
      })]
    })
  });
}
var checkbox_control_default = CheckboxControl;

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/form-toggle/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ay: () => (/* binding */ form_toggle_default)
/* harmony export */ });
/* unused harmony exports FormToggle, noop */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");



const noop = () => {
};
function UnforwardedFormToggle(props, ref) {
  const {
    className,
    checked,
    id,
    disabled,
    onChange = noop,
    onClick,
    ...additionalProps
  } = props;
  const wrapperClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("components-form-toggle", className, {
    "is-checked": checked,
    "is-disabled": disabled
  });
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("span", {
    className: wrapperClasses,
    children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
      className: "components-form-toggle__input",
      id,
      type: "checkbox",
      checked,
      onChange,
      disabled,
      onClick: (event) => {
        event.currentTarget.focus();
        onClick?.(event);
      },
      ...additionalProps,
      ref
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
      className: "components-form-toggle__track"
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
      className: "components-form-toggle__thumb"
    })]
  });
}
const FormToggle = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.forwardRef)(UnforwardedFormToggle);
var form_toggle_default = FormToggle;

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/component.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ component_default)
/* harmony export */ });
/* unused harmony export HStack */
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/context-connect.js");
/* harmony import */ var _view__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/view/component.js");
/* harmony import */ var _hook__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/hook.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");




function UnconnectedHStack(props, forwardedRef) {
  const hStackProps = (0,_hook__WEBPACK_IMPORTED_MODULE_2__/* .useHStack */ .A)(props);
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_view__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
    ...hStackProps,
    ref: forwardedRef
  });
}
const HStack = (0,_context__WEBPACK_IMPORTED_MODULE_0__/* .contextConnect */ .KZ)(UnconnectedHStack, "HStack");
var component_default = HStack;

//# sourceMappingURL=component.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/hook.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ useHStack)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex-item/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex-item/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex/hook.js + 1 modules
var hook = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/flex/flex/hook.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/values.js
var values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/values.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/utils.js

const H_ALIGNMENTS = {
  bottom: {
    align: "flex-end",
    justify: "center"
  },
  bottomLeft: {
    align: "flex-end",
    justify: "flex-start"
  },
  bottomRight: {
    align: "flex-end",
    justify: "flex-end"
  },
  center: {
    align: "center",
    justify: "center"
  },
  edge: {
    align: "center",
    justify: "space-between"
  },
  left: {
    align: "center",
    justify: "flex-start"
  },
  right: {
    align: "center",
    justify: "flex-end"
  },
  stretch: {
    align: "stretch"
  },
  top: {
    align: "flex-start",
    justify: "center"
  },
  topLeft: {
    align: "flex-start",
    justify: "flex-start"
  },
  topRight: {
    align: "flex-start",
    justify: "flex-end"
  }
};
const V_ALIGNMENTS = {
  bottom: {
    justify: "flex-end",
    align: "center"
  },
  bottomLeft: {
    justify: "flex-end",
    align: "flex-start"
  },
  bottomRight: {
    justify: "flex-end",
    align: "flex-end"
  },
  center: {
    justify: "center",
    align: "center"
  },
  edge: {
    justify: "space-between",
    align: "center"
  },
  left: {
    justify: "center",
    align: "flex-start"
  },
  right: {
    justify: "center",
    align: "flex-end"
  },
  stretch: {
    align: "stretch"
  },
  top: {
    justify: "flex-start",
    align: "center"
  },
  topLeft: {
    justify: "flex-start",
    align: "flex-start"
  },
  topRight: {
    justify: "flex-start",
    align: "flex-end"
  }
};
function getAlignmentProps(alignment, direction = "row") {
  if (!(0,values/* isValueDefined */.J5)(alignment)) {
    return {};
  }
  const isVertical = direction === "column";
  const props = isVertical ? V_ALIGNMENTS : H_ALIGNMENTS;
  const alignmentProps = alignment in props ? props[alignment] : {
    align: alignment
  };
  return alignmentProps;
}

//# sourceMappingURL=utils.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/get-valid-children.js

function getValidChildren(children) {
  if (typeof children === "string") {
    return [children];
  }
  return react.Children.toArray(children).filter((child) => (0,react.isValidElement)(child));
}

//# sourceMappingURL=get-valid-children.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/h-stack/hook.js





function useHStack(props) {
  const {
    alignment = "edge",
    children,
    direction,
    spacing = 2,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "HStack");
  const align = getAlignmentProps(alignment, direction);
  const validChildren = getValidChildren(children);
  const clonedChildren = validChildren.map((child, index) => {
    const _isSpacer = (0,context_connect/* hasConnectNamespace */.SZ)(child, ["Spacer"]);
    if (_isSpacer) {
      const childElement = child;
      const _key = childElement.key || `hstack-${index}`;
      return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
        isBlock: true,
        ...childElement.props
      }, _key);
    }
    return child;
  });
  const propsForFlex = {
    children: clonedChildren,
    direction,
    justify: "center",
    ...align,
    ...otherProps,
    gap: spacing
  };
  const {
    isColumn,
    ...flexProps
  } = (0,hook/* useFlex */.v)(propsForFlex);
  return flexProps;
}

//# sourceMappingURL=hook.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/input-control/reducer/actions.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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

/* harmony export */ __webpack_require__.d(__webpack_exports__, [
/* harmony export */   "Qf", 0, /* binding */ DRAG_START,
/* harmony export */   "Ry", 0, /* binding */ DRAG_END,
/* harmony export */   "W3", 0, /* binding */ CONTROL,
/* harmony export */   "bR", 0, /* binding */ PRESS_ENTER,
/* harmony export */   "r7", 0, /* binding */ PRESS_DOWN,
/* harmony export */   "uY", 0, /* binding */ INVALIDATE,
/* harmony export */   "wX", 0, /* binding */ PRESS_UP
/* harmony export */ ]);


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/radio-control/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ radio_control_default)
/* harmony export */ });
/* unused harmony export RadioControl */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _v_stack__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/v-stack/component.js");
/* harmony import */ var _base_control_styles_base_control_styles__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/base-control/styles/base-control-styles.js");
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");







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
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("fieldset", {
    id,
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(className, "components-radio-control"),
    "aria-describedby": !!help ? generateHelpId(id) : void 0,
    children: [hideLabelFromVision ? /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_visually_hidden__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
      as: "legend",
      children: label
    }) : /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_base_control__WEBPACK_IMPORTED_MODULE_2__/* ["default"].VisualLabel */ .Ay.VisualLabel, {
      as: "legend",
      children: label
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_v_stack__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
      spacing: 3,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("components-radio-control__group-wrapper", {
        "has-help": !!help
      }),
      children: options.map((option, index) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: "components-radio-control__option",
        children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("input", {
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
        }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("label", {
          className: "components-radio-control__label",
          htmlFor: generateOptionId(id, index),
          children: option.label
        }), !!option.description ? /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_base_control_styles_base_control_styles__WEBPACK_IMPORTED_MODULE_4__/* .StyledHelp */ .te, {
          __nextHasNoMarginBottom: true,
          id: generateOptionDescriptionId(id, index),
          className: "components-radio-control__option-description",
          children: option.description
        }) : null]
      }, generateOptionId(id, index)))
    }), !!help && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_base_control_styles_base_control_styles__WEBPACK_IMPORTED_MODULE_4__/* .StyledHelp */ .te, {
      __nextHasNoMarginBottom: true,
      id: generateHelpId(id),
      className: "components-base-control__help",
      children: help
    })]
  });
}
var radio_control_default = RadioControl;

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/text-control/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ text_control_default)
/* harmony export */ });
/* unused harmony export TextControl */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _utils_deprecated_36px_size__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");






function UnforwardedTextControl(props, ref) {
  const {
    __nextHasNoMarginBottom,
    __next40pxDefaultSize = false,
    label,
    hideLabelFromVision,
    value,
    help,
    id: idProp,
    className,
    onChange,
    type = "text",
    ...additionalProps
  } = props;
  const id = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(TextControl, "inspector-text-control", idProp);
  const onChangeValue = (event) => onChange(event.target.value);
  (0,_utils_deprecated_36px_size__WEBPACK_IMPORTED_MODULE_4__/* .maybeWarnDeprecated36pxSize */ .M)({
    componentName: "TextControl",
    size: void 0,
    __next40pxDefaultSize
  });
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)(_base_control__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "TextControl",
    label,
    hideLabelFromVision,
    id,
    help,
    className,
    children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_5__.jsx)("input", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("components-text-control__input", {
        "is-next-40px-default-size": __next40pxDefaultSize
      }),
      type,
      id,
      value,
      onChange: onChangeValue,
      "aria-describedby": !!help ? id + "__help" : void 0,
      ref,
      ...additionalProps
    })
  });
}
const TextControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(UnforwardedTextControl);
var text_control_default = TextControl;

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/utils/with-ignore-ime-events.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   n: () => (/* binding */ withIgnoreIMEEvents)
/* harmony export */ });
function withIgnoreIMEEvents(handler) {
  return (event) => {
    const {
      isComposing
    } = "nativeEvent" in event ? event.nativeEvent : event;
    if (isComposing || // Workaround for Mac Safari where the final Enter/Backspace of an IME composition
    // is `isComposing=false`, even though it's technically still part of the composition.
    // These can only be detected by keyCode.
    event.keyCode === 229) {
      return;
    }
    handler(event);
  };
}

//# sourceMappingURL=with-ignore-ime-events.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ compose_default)
});

;// ../../node_modules/.pnpm/@wordpress+compose@7.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/pipe.js
const basePipe = (reverse = false) => (...funcs) => (...args) => {
  const functions = funcs.flat();
  if (reverse) {
    functions.reverse();
  }
  return functions.reduce(
    (prev, func) => [func(...prev)],
    args
  )[0];
};
const pipe = basePipe();
var pipe_default = (/* unused pure expression or super */ null && (pipe));

//# sourceMappingURL=pipe.js.map

;// ../../node_modules/.pnpm/@wordpress+compose@7.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js

const compose = basePipe(true);
var compose_default = compose;

//# sourceMappingURL=compose.js.map


/***/ },

/***/ "../../packages/js/components/src/tooltip/tooltip.tsx"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  m: () => (/* binding */ Tooltip)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/popover/index.js + 259 modules
var popover = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/popover/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_@ty_5f53abb8ba70778c64ea7e8549cea5d4/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@ty_5f53abb8ba70778c64ea7e8549cea5d4/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.57._32fe7e38935c407cea8bd9e57e6c56c9/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.57._32fe7e38935c407cea8bd9e57e6c56c9/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+icons@11.0.1_@ty_5f53abb8ba70778c64ea7e8549cea5d4/node_modules/@wordpress/icons/build-module/library/help.js


var help_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M12 4a8 8 0 1 1 .001 16.001A8 8 0 0 1 12 4Zm0 1.5a6.5 6.5 0 1 0-.001 13.001A6.5 6.5 0 0 0 12 5.5Zm.75 11h-1.5V15h1.5v1.5Zm-.445-9.234a3 3 0 0 1 .445 5.89V14h-1.5v-1.25c0-.57.452-.958.917-1.01A1.5 1.5 0 0 0 12 8.75a1.5 1.5 0 0 0-1.5 1.5H9a3 3 0 0 1 3.305-2.984Z" }) });

//# sourceMappingURL=help.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
;// ../../packages/js/components/src/tooltip/tooltip.tsx
/**
 * External dependencies
 */







const Tooltip = ({
  children = /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
    icon: help_default
  }),
  className = '',
  helperText = (0,build_module.__)('Help', 'woocommerce'),
  position = 'top center',
  text
}) => {
  const [isPopoverVisible, setIsPopoverVisible] = (0,react.useState)(false);
  const uniqueIdentifier = (0,use_instance_id/* default */.A)(Tooltip, 'product_tooltip');
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('woocommerce-tooltip', uniqueIdentifier),
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        className: (0,clsx/* default */.A)('woocommerce-tooltip__button', className),
        onKeyDown: event => {
          if (event.key !== 'Enter') {
            return;
          }
          setIsPopoverVisible(true);
        },
        onClick: () => setIsPopoverVisible(!isPopoverVisible),
        label: helperText,
        children: children
      }), isPopoverVisible && /*#__PURE__*/(0,jsx_runtime.jsx)(popover/* default */.Ay, {
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

/***/ },

/***/ "../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ memoizeOne)
/* harmony export */ });
var safeIsNaN = Number.isNaN ||
    function ponyfill(value) {
        return typeof value === 'number' && value !== value;
    };
function isEqual(first, second) {
    if (first === second) {
        return true;
    }
    if (safeIsNaN(first) && safeIsNaN(second)) {
        return true;
    }
    return false;
}
function areInputsEqual(newInputs, lastInputs) {
    if (newInputs.length !== lastInputs.length) {
        return false;
    }
    for (var i = 0; i < newInputs.length; i++) {
        if (!isEqual(newInputs[i], lastInputs[i])) {
            return false;
        }
    }
    return true;
}

function memoizeOne(resultFn, isEqual) {
    if (isEqual === void 0) { isEqual = areInputsEqual; }
    var cache = null;
    function memoized() {
        var newArgs = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            newArgs[_i] = arguments[_i];
        }
        if (cache && cache.lastThis === this && isEqual(newArgs, cache.lastArgs)) {
            return cache.lastResult;
        }
        var lastResult = resultFn.apply(this, newArgs);
        cache = {
            lastResult: lastResult,
            lastArgs: newArgs,
            lastThis: this,
        };
        return lastResult;
    }
    memoized.clear = function clear() {
        cache = null;
    };
    return memoized;
}




/***/ }

}]);