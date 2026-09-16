"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4620],{

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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js + 6 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/form-section/form-section.tsx
/**
 * External dependencies
 */



const form_section_FormSection = ({
  title,
  description,
  className,
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: (0,clsx/* default */.A)('woocommerce-form-section', className),
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-form-section__header",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("h3", {
        className: "woocommerce-form-section__title",
        children: title
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-form-section__description",
        children: description
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-form-section__content",
      children: children
    })]
  });
};
try {
    // @ts-ignore
    form_section_FormSection.displayName = "FormSection";
    // @ts-ignore
    form_section_FormSection.__docgenInfo = { "description": "", "displayName": "FormSection", "props": { "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "string | Element" } }, "description": { "defaultValue": null, "description": "", "name": "description", "required": true, "type": { "name": "string | Element" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/form-section/form-section.tsx#FormSection"] = { docgenInfo: form_section_FormSection.__docgenInfo, name: "FormSection", path: "../../packages/js/components/src/form-section/form-section.tsx#FormSection" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pill/pill.js
var pill = __webpack_require__("../../packages/js/components/src/pill/pill.js");
;// ../../packages/js/components/src/form-section/stories/form-section.story.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const Basic = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(form_section_FormSection, {
    title: "My form section",
    description: "Some text to describe what this section covers",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)(card_body_component/* default */.A, {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
          label: "My first field",
          onChange: () => {},
          value: ""
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
          label: "My second field",
          onChange: () => {},
          value: ""
        })]
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(card_body_component/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
          label: "My third field",
          onChange: () => {},
          value: ""
        })
      })
    })]
  });
};
const CustomElements = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(form_section_FormSection, {
    title: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: ["Custom elements ", /*#__PURE__*/(0,jsx_runtime.jsx)(pill/* Pill */.a, {
        children: "Cool"
      })]
    }),
    description: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
        children: "Some text to describe what this section covers"
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        variant: "link",
        children: "Read more"
      })]
    }),
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)(card_body_component/* default */.A, {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
          label: "My first field",
          onChange: () => {},
          value: ""
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
          label: "My second field",
          onChange: () => {},
          value: ""
        })]
      })
    })
  });
};
/* harmony default export */ const form_section_story = ({
  title: 'Components/FormSection',
  component: form_section_FormSection
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
    FormSection.displayName = "FormSection";
    // @ts-ignore
    FormSection.__docgenInfo = { "description": "", "displayName": "FormSection", "props": { "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "string | Element" } }, "description": { "defaultValue": null, "description": "", "name": "description", "required": true, "type": { "name": "string | Element" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/form-section/stories/form-section.story.tsx#FormSection"] = { docgenInfo: FormSection.__docgenInfo, name: "FormSection", path: "../../packages/js/components/src/form-section/stories/form-section.story.tsx#FormSection" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ component_component_default)
});

// UNUSED EXPORTS: CardBody

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/scrollable/styles.js
function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}


const scrollableScrollbar = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("@media only screen and ( min-device-width: 40em ){&::-webkit-scrollbar{height:12px;width:12px;}&::-webkit-scrollbar-track{background-color:transparent;}&::-webkit-scrollbar-track{background:", config_values/* default */.A.colorScrollbarTrack, ";border-radius:8px;}&::-webkit-scrollbar-thumb{background-clip:padding-box;background-color:", config_values/* default */.A.colorScrollbarThumb, ";border:2px solid rgba( 0, 0, 0, 0 );border-radius:7px;}&:hover::-webkit-scrollbar-thumb{background-color:", config_values/* default */.A.colorScrollbarThumbHover, ";}}" + ( true ? "" : 0),  true ? "" : 0);
const Scrollable =  true ? {
  name: "13udsys",
  styles: "height:100%"
} : 0;
const Content =  true ? {
  name: "bjn8wh",
  styles: "position:relative"
} : 0;
const styles_smoothScroll =  true ? {
  name: "7zq9w",
  styles: "scroll-behavior:smooth"
} : 0;
const scrollX =  true ? {
  name: "q33xhg",
  styles: "overflow-x:auto;overflow-y:hidden"
} : 0;
const scrollY =  true ? {
  name: "103x71s",
  styles: "overflow-x:hidden;overflow-y:auto"
} : 0;
const scrollAuto =  true ? {
  name: "umwchj",
  styles: "overflow-y:auto"
} : 0;

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/scrollable/hook.js




function useScrollable(props) {
  const {
    className,
    scrollDirection = "y",
    smoothScroll = false,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "Scrollable");
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => cx(Scrollable, scrollableScrollbar, smoothScroll && styles_smoothScroll, scrollDirection === "x" && scrollX, scrollDirection === "y" && scrollY, scrollDirection === "auto" && scrollAuto, className), [className, cx, scrollDirection, smoothScroll]);
  return {
    ...otherProps,
    className: classes
  };
}

//# sourceMappingURL=hook.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/scrollable/component.js




function UnconnectedScrollable(props, forwardedRef) {
  const scrollableProps = useScrollable(props);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    ...scrollableProps,
    ref: forwardedRef
  });
}
const component_Scrollable = (0,context_connect/* contextConnect */.KZ)(UnconnectedScrollable, "Scrollable");
var component_default = component_Scrollable;

//# sourceMappingURL=component.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/styles.js
var styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/styles.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/hook.js




function useCardBody(props) {
  const {
    className,
    isScrollable = false,
    isShady = false,
    size = "medium",
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "CardBody");
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => cx(
    styles/* Body */.nB,
    styles/* borderRadius */.Vq,
    styles/* cardPaddings */.L7[size],
    isShady && styles/* shady */.QC,
    // This classname is added for legacy compatibility reasons.
    "components-card__body",
    className
  ), [className, cx, isShady, size]);
  return {
    ...otherProps,
    className: classes,
    isScrollable
  };
}

//# sourceMappingURL=hook.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js





function UnconnectedCardBody(props, forwardedRef) {
  const {
    isScrollable,
    ...otherProps
  } = useCardBody(props);
  if (isScrollable) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(component_default, {
      ...otherProps,
      ref: forwardedRef
    });
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    ...otherProps,
    ref: forwardedRef
  });
}
const CardBody = (0,context_connect/* contextConnect */.KZ)(UnconnectedCardBody, "CardBody");
var component_component_default = CardBody;

//# sourceMappingURL=component.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/index.js":
/***/ (() => {




































































































































//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ text_control_default)
/* harmony export */ });
/* unused harmony export TextControl */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _utils_deprecated_36px_size__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");






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
  (0,_utils_deprecated_36px_size__WEBPACK_IMPORTED_MODULE_2__/* .maybeWarnDeprecated36pxSize */ .M)({
    componentName: "TextControl",
    size: void 0,
    __next40pxDefaultSize
  });
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_base_control__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "TextControl",
    label,
    hideLabelFromVision,
    id,
    help,
    className,
    children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)("components-text-control__input", {
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
const TextControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.forwardRef)(UnforwardedTextControl);
var text_control_default = TextControl;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ component_default)
/* harmony export */ });
/* unused harmony export Text */
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
/* harmony import */ var _view__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
/* harmony import */ var _hook__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/hook.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");




function UnconnectedText(props, forwardedRef) {
  const textProps = (0,_hook__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(props);
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_view__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    as: "span",
    ...textProps,
    ref: forwardedRef
  });
}
const Text = (0,_context__WEBPACK_IMPORTED_MODULE_3__/* .contextConnect */ .KZ)(UnconnectedText, "Text");
var component_default = Text;

//# sourceMappingURL=component.js.map


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

/***/ "../../packages/js/components/src/pill/pill.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ Pill)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _experimental__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/experimental.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


function Pill({
  children,
  className = ''
}) {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_experimental__WEBPACK_IMPORTED_MODULE_1__/* .Text */ .E, {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-pill', className),
    variant: "caption",
    as: "span",
    size: "12",
    lineHeight: "16px",
    children: children
  });
}
;
Pill.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "Pill",
  "props": {
    "className": {
      "defaultValue": {
        "value": "''",
        "computed": false
      },
      "required": false
    }
  }
};

/***/ })

}]);