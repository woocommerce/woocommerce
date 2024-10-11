"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4620],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/index.js":
/***/ (() => {

// Primitives.
 // Components.
























































































































 // Higher-Order Components.









//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");



/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * @typedef OwnProps
 * @property {string}                  label                 Label for the control.
 * @property {boolean}                 [hideLabelFromVision] Whether to accessibly hide the label.
 * @property {string}                  value                 Value of the input.
 * @property {string}                  [help]                Optional help text for the control.
 * @property {string}                  [className]           Classname passed to BaseControl wrapper
 * @property {(value: string) => void} onChange              Handle changes.
 * @property {string}                  [type='text']         Type of the input.
 */

/** @typedef {OwnProps & import('react').ComponentProps<'input'>} Props */

/**
 *
 * @param {Props}                                          props Props
 * @param {import('react').ForwardedRef<HTMLInputElement>} ref
 */

function TextControl(_ref, ref) {
  let {
    label,
    hideLabelFromVision,
    value,
    help,
    className,
    onChange,
    type = 'text',
    ...props
  } = _ref;
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(TextControl);
  const id = `inspector-text-control-${instanceId}`;

  const onChangeValue = (
  /** @type {import('react').ChangeEvent<HTMLInputElement>} */
  event) => onChange(event.target.value);

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_base_control__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("input", (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)({
    className: "components-text-control__input",
    type: type,
    id: id,
    value: value,
    onChange: onChangeValue,
    "aria-describedby": !!help ? id + '__help' : undefined,
    ref: ref
  }, props)));
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.forwardRef)(TextControl));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */


/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
var Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Text || _wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A;

/***/ }),

/***/ "../../packages/js/components/src/pill/pill.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ Pill)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _experimental__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/experimental.js");
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

function Pill(_ref) {
  var children = _ref.children,
    _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_experimental__WEBPACK_IMPORTED_MODULE_2__/* .Text */ .E, {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-pill', className),
    variant: "caption",
    as: "span",
    size: "12",
    lineHeight: "16px"
  }, children);
}

/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

// `Symbol.prototype.description` getter
// https://tc39.es/ecma262/#sec-symbol.prototype.description

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var hasOwn = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/has-own-property.js");
var isCallable = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-callable.js");
var isPrototypeOf = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-is-prototype-of.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var defineBuiltInAccessor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/define-built-in-accessor.js");
var copyConstructorProperties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/copy-constructor-properties.js");

var NativeSymbol = global.Symbol;
var SymbolPrototype = NativeSymbol && NativeSymbol.prototype;

if (DESCRIPTORS && isCallable(NativeSymbol) && (!('description' in SymbolPrototype) ||
  // Safari 12 bug
  NativeSymbol().description !== undefined
)) {
  var EmptyStringDescriptionStore = {};
  // wrap Symbol constructor for correct work with undefined description
  var SymbolWrapper = function Symbol() {
    var description = arguments.length < 1 || arguments[0] === undefined ? undefined : toString(arguments[0]);
    var result = isPrototypeOf(SymbolPrototype, this)
      ? new NativeSymbol(description)
      // in Edge 13, String(Symbol(undefined)) === 'Symbol(undefined)'
      : description === undefined ? NativeSymbol() : NativeSymbol(description);
    if (description === '') EmptyStringDescriptionStore[result] = true;
    return result;
  };

  copyConstructorProperties(SymbolWrapper, NativeSymbol);
  SymbolWrapper.prototype = SymbolPrototype;
  SymbolPrototype.constructor = SymbolWrapper;

  var NATIVE_SYMBOL = String(NativeSymbol('description detection')) === 'Symbol(description detection)';
  var thisSymbolValue = uncurryThis(SymbolPrototype.valueOf);
  var symbolDescriptiveString = uncurryThis(SymbolPrototype.toString);
  var regexp = /^Symbol\((.*)\)[^)]+$/;
  var replace = uncurryThis(''.replace);
  var stringSlice = uncurryThis(''.slice);

  defineBuiltInAccessor(SymbolPrototype, 'description', {
    configurable: true,
    get: function description() {
      var symbol = thisSymbolValue(this);
      if (hasOwn(EmptyStringDescriptionStore, symbol)) return '';
      var string = symbolDescriptiveString(symbol);
      var desc = NATIVE_SYMBOL ? stringSlice(string, 7, -1) : replace(string, regexp, '$1');
      return desc === '' ? undefined : desc;
    }
  });

  $({ global: true, constructor: true, forced: true }, {
    Symbol: SymbolWrapper
  });
}


/***/ }),

/***/ "../../packages/js/components/src/form-section/stories/form-section.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  CustomElements: () => (/* binding */ CustomElements),
  "default": () => (/* binding */ form_section_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js + 7 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js
var es_symbol_description = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
;// CONCATENATED MODULE: ../../packages/js/components/src/form-section/form-section.tsx



/**
 * External dependencies
 */


var FormSection = function FormSection(_ref) {
  var title = _ref.title,
    description = _ref.description,
    className = _ref.className,
    children = _ref.children;
  return (0,react.createElement)("div", {
    className: classnames_default()('woocommerce-form-section', className)
  }, (0,react.createElement)("div", {
    className: "woocommerce-form-section__header"
  }, (0,react.createElement)("h3", {
    className: "woocommerce-form-section__title"
  }, title), (0,react.createElement)("div", {
    className: "woocommerce-form-section__description"
  }, description)), (0,react.createElement)("div", {
    className: "woocommerce-form-section__content"
  }, children));
};
try {
    // @ts-ignore
    FormSection.displayName = "FormSection";
    // @ts-ignore
    FormSection.__docgenInfo = { "description": "", "displayName": "FormSection", "props": { "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "string | Element" } }, "description": { "defaultValue": null, "description": "", "name": "description", "required": true, "type": { "name": "string | Element" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/form-section/form-section.tsx#FormSection"] = { docgenInfo: FormSection.__docgenInfo, name: "FormSection", path: "../../packages/js/components/src/form-section/form-section.tsx#FormSection" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pill/pill.js
var pill = __webpack_require__("../../packages/js/components/src/pill/pill.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/form-section/stories/form-section.story.tsx

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var Basic = function Basic() {
  return (0,react.createElement)(FormSection, {
    title: "My form section",
    description: "Some text to describe what this section covers"
  }, (0,react.createElement)(component/* default */.A, null, (0,react.createElement)(card_body_component/* default */.A, null, (0,react.createElement)(text_control/* default */.A, {
    label: "My first field",
    onChange: function onChange() {},
    value: ""
  }), (0,react.createElement)(text_control/* default */.A, {
    label: "My second field",
    onChange: function onChange() {},
    value: ""
  }))), (0,react.createElement)(component/* default */.A, null, (0,react.createElement)(card_body_component/* default */.A, null, (0,react.createElement)(text_control/* default */.A, {
    label: "My third field",
    onChange: function onChange() {},
    value: ""
  }))));
};
var CustomElements = function CustomElements() {
  return (0,react.createElement)(FormSection, {
    title: (0,react.createElement)(react.Fragment, null, "Custom elements ", (0,react.createElement)(pill/* Pill */.a, null, "Cool")),
    description: (0,react.createElement)(react.Fragment, null, (0,react.createElement)("p", null, "Some text to describe what this section covers"), (0,react.createElement)(build_module_button/* default */.A, {
      variant: "link"
    }, "Read more"))
  }, (0,react.createElement)(component/* default */.A, null, (0,react.createElement)(card_body_component/* default */.A, null, (0,react.createElement)(text_control/* default */.A, {
    label: "My first field",
    onChange: function onChange() {},
    value: ""
  }), (0,react.createElement)(text_control/* default */.A, {
    label: "My second field",
    onChange: function onChange() {},
    value: ""
  }))));
};
/* harmony default export */ const form_section_story = ({
  title: 'WooCommerce Admin/components/FormSection',
  component: FormSection
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <FormSection title=\"My form section\" description=\"Some text to describe what this section covers\">\n            <Card>\n                <CardBody>\n                    <TextControl label=\"My first field\" onChange={() => {}} value=\"\" />\n                    <TextControl label=\"My second field\" onChange={() => {}} value=\"\" />\n                </CardBody>\n            </Card>\n\n            <Card>\n                <CardBody>\n                    <TextControl label=\"My third field\" onChange={() => {}} value=\"\" />\n                </CardBody>\n            </Card>\n        </FormSection>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
CustomElements.parameters = {
  ...CustomElements.parameters,
  docs: {
    ...CustomElements.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <FormSection title={<>\n                    Custom elements <Pill>Cool</Pill>\n                </>} description={<>\n                    <p>Some text to describe what this section covers</p>\n                    <Button variant=\"link\">Read more</Button>\n                </>}>\n            <Card>\n                <CardBody>\n                    <TextControl label=\"My first field\" onChange={() => {}} value=\"\" />\n                    <TextControl label=\"My second field\" onChange={() => {}} value=\"\" />\n                </CardBody>\n            </Card>\n        </FormSection>;\n}",
      ...CustomElements.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Basic.displayName = "Basic";
    // @ts-ignore
    Basic.__docgenInfo = { "description": "", "displayName": "Basic", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/form-section/stories/form-section.story.tsx#Basic"] = { docgenInfo: Basic.__docgenInfo, name: "Basic", path: "../../packages/js/components/src/form-section/stories/form-section.story.tsx#Basic" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    CustomElements.displayName = "CustomElements";
    // @ts-ignore
    CustomElements.__docgenInfo = { "description": "", "displayName": "CustomElements", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/form-section/stories/form-section.story.tsx#CustomElements"] = { docgenInfo: CustomElements.__docgenInfo, name: "CustomElements", path: "../../packages/js/components/src/form-section/stories/form-section.story.tsx#CustomElements" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);