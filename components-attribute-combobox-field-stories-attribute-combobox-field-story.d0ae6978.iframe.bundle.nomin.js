"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2527],{

/***/ "../../packages/js/product-editor/src/components/attribute-combobox-field/stories/attribute-combobox-field.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Default: () => (/* binding */ Default),
  MultipleInstances: () => (/* binding */ MultipleInstances),
  "default": () => (/* binding */ attribute_combobox_field_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+interface@4.5.6_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-dir_imd5lple4ttszfhibm65m7atq4/node_modules/@wordpress/interface/src/style.scss
var style = __webpack_require__("../../node_modules/.pnpm/@wordpress+interface@4.5.6_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-dir_imd5lple4ttszfhibm65m7atq4/node_modules/@wordpress/interface/src/style.scss");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.assign.js
var es_object_assign = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.assign.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js
var es_number_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/combobox-control/index.js + 5 modules
var combobox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/combobox-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js + 1 modules
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
;// CONCATENATED MODULE: ../../packages/js/product-editor/src/components/attribute-combobox-field/index.tsx













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

/*
 * Create an interface that includes
 * the `__experimentalRenderItem` property.
 */

/*
 * Create an alias for the ComboboxControl core component,
 * but with the custom ComboboxControlProps interface.
 */
var ComboboxControl = combobox_control/* default */.A;
/**
 * Map the product attribute item to the Combobox core option.
 *
 * @param {AttributesComboboxControlItem} attr - Product attribute item.
 * @return {ComboboxControlOption}               Combobox option.
 */
function mapItemToOption(attr) {
  return {
    label: attr.name,
    value: "attr-".concat(attr.id),
    disabled: !!attr.isDisabled
  };
}
var createNewAttributeOptionDefault = {
  label: '',
  value: '',
  state: 'draft'
};

/**
 * ComboboxControlOption component.
 *
 * @param {ComboboxControlOptionProps} props - props.
 * @return {JSX.Element}                       Component item.
 */
function ComboboxControlOption(props) {
  var item = props.item;
  if (item.disabled) {
    return (0,react.createElement)("div", {
      className: "item-wrapper is-disabled"
    }, item.label);
  }
  return (0,react.createElement)("div", {
    className: "item-wrapper"
  }, item.label);
}
var AttributesComboboxControl = function AttributesComboboxControl(_ref) {
  var label = _ref.label,
    help = _ref.help,
    _ref$current = _ref.current,
    current = _ref$current === void 0 ? null : _ref$current,
    _ref$items = _ref.items,
    items = _ref$items === void 0 ? [] : _ref$items,
    _ref$instanceNumber = _ref.instanceNumber,
    instanceNumber = _ref$instanceNumber === void 0 ? 0 : _ref$instanceNumber,
    _ref$isLoading = _ref.isLoading,
    isLoading = _ref$isLoading === void 0 ? false : _ref$isLoading,
    onAddNew = _ref.onAddNew,
    _onChange = _ref.onChange;
  var _useState = (0,react.useState)(createNewAttributeOptionDefault),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    createNewAttributeOption = _useState2[0],
    updateCreateNewAttributeOption = _useState2[1];

  /**
   * Map the items to the Combobox options.
   * Each option is an object with a label and value.
   * Both are strings.
   */
  var attributeOptions = items === null || items === void 0 ? void 0 : items.map(mapItemToOption);
  var options = (0,react.useMemo)(function () {
    if (!createNewAttributeOption.label.length) {
      return attributeOptions;
    }
    return [].concat((0,toConsumableArray/* default */.A)(attributeOptions), [{
      label: createNewAttributeOption.state === 'draft' ? (0,build_module/* sprintf */.nv)( /* translators: The name of the new attribute term to be created */
      (0,build_module.__)('Create "%s"', 'woocommerce'), createNewAttributeOption.label) : createNewAttributeOption.label,
      value: createNewAttributeOption.value
    }]);
  }, [attributeOptions, createNewAttributeOption]);

  // Get current of the selected item.
  var currentValue = current ? "attr-".concat(current.id) : '';
  if (createNewAttributeOption.state === 'creating') {
    currentValue = 'create-attribute';
  }
  var comboRef = (0,react.useRef)(null);

  // Label to link the input with the label.
  var _useState3 = (0,react.useState)(''),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    labelFor = _useState4[0],
    setLabelFor = _useState4[1];
  (0,react.useEffect)(function () {
    if (!(comboRef !== null && comboRef !== void 0 && comboRef.current)) {
      return;
    }

    /*
     * Hack to set the base control ID,
     * to link the label with the input,
     * picking the input ID from the ComboboxControl.
     */
    var inputElement = comboRef.current.querySelector('input.components-combobox-control__input');
    var id = inputElement === null || inputElement === void 0 ? void 0 : inputElement.getAttribute('id');
    if (inputElement && typeof id === 'string') {
      setLabelFor(id);
    }

    /*
     * Hack to handle AttributesComboboxControl instances z index,
     * avoiding to overlap the dropdown instances list.
     * Todo: this is a temporary/not-ideal solution.
     * It should be handled by the core ComboboxControl component.
     */
    var listContainerElement = comboRef.current.querySelector('.components-combobox-control__suggestions-container');
    var style = {
      zIndex: 1000 - instanceNumber
    };
    if (listContainerElement) {
      Object.assign(listContainerElement.style, style);
    }
  }, [instanceNumber]);
  if (!help) {
    help = (0,react.createElement)("div", {
      className: "woocommerce-attributes-combobox-help"
    }, (0,build_module.__)('Select an attribute or type to create.', 'woocommerce'));
    if (isLoading) {
      help = (0,react.createElement)("div", {
        className: "woocommerce-attributes-combobox-help"
      }, (0,react.createElement)(spinner/* default */.A, null), (0,build_module.__)('Loading…', 'woocommerce'));
    } else if (!items.length) {
      help = (0,react.createElement)("div", {
        className: "woocommerce-attributes-combobox-help"
      }, (0,build_module.__)('No attributes yet. Type to create.', 'woocommerce'));
    }
  }
  return (0,react.createElement)("div", {
    className: classnames_default()('woocommerce-attributes-combobox-container', {
      'no-items': !options.length
    }),
    ref: comboRef
  }, (0,react.createElement)(base_control/* default */.Ay, {
    label: label,
    help: help,
    id: labelFor
  }, (0,react.createElement)(ComboboxControl, {
    className: "woocommerce-attributes-combobox",
    allowReset: false,
    options: options,
    value: currentValue,
    onChange: function onChange(newValue) {
      if (!newValue) {
        return;
      }
      if (newValue === 'create-attribute') {
        updateCreateNewAttributeOption(_objectSpread(_objectSpread({}, createNewAttributeOption), {}, {
          state: 'creating'
        }));
        return onAddNew === null || onAddNew === void 0 ? void 0 : onAddNew(createNewAttributeOption.label);
      }
      var selectedAttribute = items === null || items === void 0 ? void 0 : items.find(function (item) {
        return item.id === Number(newValue.replace('attr-', ''));
      });

      /*
       * Do not select when it is disabled.
       * `disabled` item option should be
       * handled by the core ComboboxControl component.
       */
      if (!selectedAttribute || selectedAttribute.isDisabled) {
        return;
      }
      _onChange(selectedAttribute);
    },
    onFilterValueChange: function onFilterValueChange(filterValue) {
      updateCreateNewAttributeOption({
        label: filterValue,
        value: 'create-attribute',
        state: 'draft'
      });
    },
    __experimentalRenderItem: ComboboxControlOption
  })));
};
/* harmony default export */ const attribute_combobox_field = (AttributesComboboxControl);
try {
    // @ts-ignore
    attributecomboboxfield.displayName = "attributecomboboxfield";
    // @ts-ignore
    attributecomboboxfield.__docgenInfo = { "description": "", "displayName": "attributecomboboxfield", "props": { "label": { "defaultValue": null, "description": "", "name": "label", "required": false, "type": { "name": "string" } }, "help": { "defaultValue": null, "description": "", "name": "help", "required": false, "type": { "name": "string | Element | null" } }, "isLoading": { "defaultValue": { value: "false" }, "description": "", "name": "isLoading", "required": false, "type": { "name": "boolean" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "instanceNumber": { "defaultValue": { value: "0" }, "description": "", "name": "instanceNumber", "required": false, "type": { "name": "number" } }, "current": { "defaultValue": { value: "null" }, "description": "", "name": "current", "required": false, "type": { "name": "AttributesComboboxControlItem | null" } }, "items": { "defaultValue": { value: "[]" }, "description": "", "name": "items", "required": false, "type": { "name": "AttributesComboboxControlItem[]" } }, "disabledAttributeMessage": { "defaultValue": null, "description": "", "name": "disabledAttributeMessage", "required": false, "type": { "name": "string" } }, "createNewAttributesAsGlobal": { "defaultValue": null, "description": "", "name": "createNewAttributesAsGlobal", "required": false, "type": { "name": "boolean" } }, "onAddNew": { "defaultValue": null, "description": "", "name": "onAddNew", "required": false, "type": { "name": "((value: string) => void)" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: AttributesComboboxControlItem) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/product-editor/src/components/attribute-combobox-field/index.tsx#attributecomboboxfield"] = { docgenInfo: attributecomboboxfield.__docgenInfo, name: "attributecomboboxfield", path: "../../packages/js/product-editor/src/components/attribute-combobox-field/index.tsx#attributecomboboxfield" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/product-editor/src/components/attribute-combobox-field/stories/attribute-combobox-field.story.tsx



/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

/* harmony default export */ const attribute_combobox_field_story = ({
  title: 'Product Editor/components/AttributesComboboxControl',
  component: attribute_combobox_field
});
var items = [{
  id: 1,
  name: 'Color'
}, {
  id: 2,
  name: 'Size'
}, {
  id: 3,
  name: 'Material',
  isDisabled: true
}, {
  id: 4,
  name: 'Style'
}, {
  id: 5,
  name: 'Brand'
}, {
  id: 6,
  name: 'Pattern'
}, {
  id: 7,
  name: 'Theme',
  isDisabled: true
}, {
  id: 8,
  name: 'Collection',
  isDisabled: true
}, {
  id: 9,
  name: 'Occasion'
}, {
  id: 10,
  name: 'Season'
}];
var Default = function Default(args) {
  var _useState = (0,react.useState)(null),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    selectedAttribute = _useState2[0],
    setSelectedAttribute = _useState2[1];
  function selectAttribute(item) {
    if (typeof item === 'string') {
      return;
    }
    setSelectedAttribute(item);
    args.onChange(item);
  }
  return (0,react.createElement)(attribute_combobox_field, (0,esm_extends/* default */.A)({}, args, {
    label: (0,build_module.__)('Attributes', 'woocommerce'),
    items: items,
    help: (0,build_module.__)('Select or create attributes for this product.', 'woocommerce'),
    onChange: selectAttribute,
    current: selectedAttribute
  }));
};
Default.args = {
  onChange: function onChange(newValue) {
    console.log('(onChange) newValue:', newValue); // eslint-disable-line no-console
  }
};
var MultipleInstances = function MultipleInstances(args) {
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(attribute_combobox_field, (0,esm_extends/* default */.A)({}, args, {
    label: (0,build_module.__)('Attributes 1', 'woocommerce'),
    items: items,
    instanceNumber: 1
  })), (0,react.createElement)(attribute_combobox_field, (0,esm_extends/* default */.A)({}, args, {
    label: (0,build_module.__)('Attributes 2', 'woocommerce'),
    items: items,
    instanceNumber: 2
  })));
};
MultipleInstances.args = Default.args;
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "(args: AttributesComboboxControlComponent) => {\n  const [selectedAttribute, setSelectedAttribute] = useState<AttributesComboboxControlItem | null>(null);\n  function selectAttribute(item: AttributesComboboxControlItem) {\n    if (typeof item === 'string') {\n      return;\n    }\n    setSelectedAttribute(item);\n    args.onChange(item);\n  }\n  return <AttributesComboboxControl {...args} label={__('Attributes', 'woocommerce')} items={items} help={__('Select or create attributes for this product.', 'woocommerce')} onChange={selectAttribute} current={selectedAttribute} />;\n}",
      ...Default.parameters?.docs?.source
    }
  }
};
MultipleInstances.parameters = {
  ...MultipleInstances.parameters,
  docs: {
    ...MultipleInstances.parameters?.docs,
    source: {
      originalSource: "(args: AttributesComboboxControlComponent) => {\n  return <>\n            <AttributesComboboxControl {...args} label={__('Attributes 1', 'woocommerce')} items={items} instanceNumber={1} />\n\n            <AttributesComboboxControl {...args} label={__('Attributes 2', 'woocommerce')} items={items} instanceNumber={2} />\n        </>;\n}",
      ...MultipleInstances.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Default.displayName = "Default";
    // @ts-ignore
    Default.__docgenInfo = { "description": "", "displayName": "Default", "props": { "label": { "defaultValue": null, "description": "", "name": "label", "required": false, "type": { "name": "string" } }, "help": { "defaultValue": null, "description": "", "name": "help", "required": false, "type": { "name": "string | Element | null" } }, "isLoading": { "defaultValue": null, "description": "", "name": "isLoading", "required": true, "type": { "name": "boolean" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "instanceNumber": { "defaultValue": null, "description": "", "name": "instanceNumber", "required": false, "type": { "name": "number" } }, "current": { "defaultValue": null, "description": "", "name": "current", "required": true, "type": { "name": "AttributesComboboxControlItem | null" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "AttributesComboboxControlItem[]" } }, "disabledAttributeMessage": { "defaultValue": null, "description": "", "name": "disabledAttributeMessage", "required": false, "type": { "name": "string" } }, "createNewAttributesAsGlobal": { "defaultValue": null, "description": "", "name": "createNewAttributesAsGlobal", "required": false, "type": { "name": "boolean" } }, "onAddNew": { "defaultValue": null, "description": "", "name": "onAddNew", "required": false, "type": { "name": "((value: string) => void)" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: AttributesComboboxControlItem) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/product-editor/src/components/attribute-combobox-field/stories/attribute-combobox-field.story.tsx#Default"] = { docgenInfo: Default.__docgenInfo, name: "Default", path: "../../packages/js/product-editor/src/components/attribute-combobox-field/stories/attribute-combobox-field.story.tsx#Default" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    MultipleInstances.displayName = "MultipleInstances";
    // @ts-ignore
    MultipleInstances.__docgenInfo = { "description": "", "displayName": "MultipleInstances", "props": { "label": { "defaultValue": null, "description": "", "name": "label", "required": false, "type": { "name": "string" } }, "help": { "defaultValue": null, "description": "", "name": "help", "required": false, "type": { "name": "string | Element | null" } }, "isLoading": { "defaultValue": null, "description": "", "name": "isLoading", "required": true, "type": { "name": "boolean" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "instanceNumber": { "defaultValue": null, "description": "", "name": "instanceNumber", "required": false, "type": { "name": "number" } }, "current": { "defaultValue": null, "description": "", "name": "current", "required": true, "type": { "name": "AttributesComboboxControlItem | null" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "AttributesComboboxControlItem[]" } }, "disabledAttributeMessage": { "defaultValue": null, "description": "", "name": "disabledAttributeMessage", "required": false, "type": { "name": "string" } }, "createNewAttributesAsGlobal": { "defaultValue": null, "description": "", "name": "createNewAttributesAsGlobal", "required": false, "type": { "name": "boolean" } }, "onAddNew": { "defaultValue": null, "description": "", "name": "onAddNew", "required": false, "type": { "name": "((value: string) => void)" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: AttributesComboboxControlItem) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/product-editor/src/components/attribute-combobox-field/stories/attribute-combobox-field.story.tsx#MultipleInstances"] = { docgenInfo: MultipleInstances.__docgenInfo, name: "MultipleInstances", path: "../../packages/js/product-editor/src/components/attribute-combobox-field/stories/attribute-combobox-field.story.tsx#MultipleInstances" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);