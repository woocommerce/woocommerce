"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1727],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs
var library_reset = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.mjs
// packages/icons/src/library/check.tsx


var check_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M16.5 7.5 10 13.9l-2.5-2.4-1 1 3.5 3.6 7.5-7.6z" }) });

//# sourceMappingURL=check.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js








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


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/form-toggle/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ay: () => (/* binding */ form_toggle_default)
/* harmony export */ });
/* unused harmony exports FormToggle, noop */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");



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
  const wrapperClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)("components-form-toggle", className, {
    "is-checked": checked,
    "is-disabled": disabled
  });
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("span", {
    className: wrapperClasses,
    children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
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
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
      className: "components-form-toggle__track"
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
      className: "components-form-toggle__thumb"
    })]
  });
}
const FormToggle = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(UnforwardedFormToggle);
var form_toggle_default = FormToggle;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/reducer/actions.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   PL: () => (/* binding */ CHANGE),
/* harmony export */   Qf: () => (/* binding */ DRAG_START),
/* harmony export */   Ry: () => (/* binding */ DRAG_END),
/* harmony export */   Ut: () => (/* binding */ RESET),
/* harmony export */   W3: () => (/* binding */ CONTROL),
/* harmony export */   bR: () => (/* binding */ PRESS_ENTER),
/* harmony export */   cJ: () => (/* binding */ COMMIT),
/* harmony export */   j: () => (/* binding */ DRAG),
/* harmony export */   r7: () => (/* binding */ PRESS_DOWN),
/* harmony export */   uY: () => (/* binding */ INVALIDATE),
/* harmony export */   wX: () => (/* binding */ PRESS_UP)
/* harmony export */ });
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


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/radio-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ toggle_control_default)
});

// UNUSED EXPORTS: ToggleControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js
var hook = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex-block/hook.js


function useFlexBlock(props) {
  const otherProps = (0,use_context_system/* useContextSystem */.A)(props, "FlexBlock");
  const flexItemProps = (0,hook/* useFlexItem */.K)({
    isBlock: true,
    ...otherProps
  });
  return flexItemProps;
}

//# sourceMappingURL=hook.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex-block/component.js




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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/form-toggle/index.js
var form_toggle = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/form-toggle/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js
var h_stack_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-control/index.js












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
    (0,build_module/* default */.A)("Bottom margin styles for wp.components.ToggleControl", {
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


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/with-ignore-ime-events.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/components/registry-provider/context.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ay: () => (/* binding */ context_default)
/* harmony export */ });
/* unused harmony exports Context, RegistryConsumer */
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _default_registry_mjs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.mjs");
// packages/data/src/components/registry-provider/context.ts


var Context = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(_default_registry_mjs__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A);
Context.displayName = "RegistryProviderContext";
var { Consumer, Provider } = Context;
var RegistryConsumer = (/* unused pure expression or super */ null && (Consumer));
var context_default = Provider;

//# sourceMappingURL=context.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ default_registry_default)
/* harmony export */ });
/* harmony import */ var _registry_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.mjs");
// packages/data/src/default-registry.ts

var defaultRegistry = (0,_registry_mjs__WEBPACK_IMPORTED_MODULE_0__/* .createRegistry */ .I)();
var default_registry_default = defaultRegistry;

//# sourceMappingURL=default-registry.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/dispatch.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   J: () => (/* binding */ dispatch)
/* harmony export */ });
/* harmony import */ var _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.mjs");
// packages/data/src/dispatch.ts

function dispatch(storeNameOrDescriptor) {
  return _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.dispatch(
    storeNameOrDescriptor
  );
}

//# sourceMappingURL=dispatch.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   kz: () => (/* binding */ register)
/* harmony export */ });
/* unused harmony exports combineReducers, registerGenericStore, registerStore, resolveSelect, subscribe, suspendSelect, use */
/* harmony import */ var _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.mjs");
// packages/data/src/index.ts

















var combineReducers = (/* unused pure expression or super */ null && (combineReducersModule));
function resolveSelect(storeNameOrDescriptor) {
  return defaultRegistry.resolveSelect(
    storeNameOrDescriptor
  );
}
var suspendSelect = (storeNameOrDescriptor) => defaultRegistry.suspendSelect(storeNameOrDescriptor);
var subscribe = (listener, storeNameOrDescriptor) => defaultRegistry.subscribe(listener, storeNameOrDescriptor);
var registerGenericStore = _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.registerGenericStore;
var registerStore = _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.registerStore;
var use = _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.use;
var register = (store) => _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.register(store);

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ unlock),
/* harmony export */   s: () => (/* binding */ lock)
/* harmony export */ });
/* harmony import */ var _wordpress_private_apis__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs");
// packages/data/src/lock-unlock.ts

var { lock, unlock } = (0,_wordpress_private_apis__WEBPACK_IMPORTED_MODULE_0__/* .__dangerousOptInToUnstableAPIsOnlyForCoreModules */ .yf)(
  "I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.",
  "@wordpress/data"
);

//# sourceMappingURL=lock-unlock.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ createReduxStore)
});

// UNUSED EXPORTS: combineReducers

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.mjs
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  countSelectorsByStatus: () => (countSelectorsByStatus),
  getCachedResolvers: () => (getCachedResolvers),
  getIsResolving: () => (getIsResolving),
  getResolutionError: () => (getResolutionError),
  getResolutionState: () => (getResolutionState),
  hasFinishedResolution: () => (hasFinishedResolution),
  hasResolutionFailed: () => (hasResolutionFailed),
  hasResolvingSelectors: () => (hasResolvingSelectors),
  hasStartedResolution: () => (hasStartedResolution),
  isResolving: () => (isResolving)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.mjs
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  failResolution: () => (failResolution),
  failResolutions: () => (failResolutions),
  finishResolution: () => (finishResolution),
  finishResolutions: () => (finishResolutions),
  invalidateResolution: () => (invalidateResolution),
  invalidateResolutionForStore: () => (invalidateResolutionForStore),
  invalidateResolutionForStoreSelector: () => (invalidateResolutionForStoreSelector),
  startResolution: () => (startResolution),
  startResolutions: () => (startResolutions)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/redux@5.0.1/node_modules/redux/dist/redux.mjs
var redux = __webpack_require__("../../node_modules/.pnpm/redux@5.0.1/node_modules/redux/dist/redux.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js
var equivalent_key_map = __webpack_require__("../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js");
var equivalent_key_map_default = /*#__PURE__*/__webpack_require__.n(equivalent_key_map);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@5.48.1_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+redux-routine@5.48.1_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.mjs + 1 modules
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.mjs");
;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/combine-reducers.mjs
// packages/data/src/redux-store/combine-reducers.ts
function combineReducers(reducers) {
  const keys = Object.keys(reducers);
  return function combinedReducer(state = {}, action) {
    const nextState = {};
    let hasChanged = false;
    for (const key of keys) {
      const reducer = reducers[key];
      const prevStateForKey = state[key];
      const nextStateForKey = reducer(prevStateForKey, action);
      nextState[key] = nextStateForKey;
      hasChanged = hasChanged || nextStateForKey !== prevStateForKey;
    }
    return hasChanged ? nextState : state;
  };
}

//# sourceMappingURL=combine-reducers.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/factory.mjs
// packages/data/src/factory.ts
function createRegistrySelector(registrySelector) {
  const selectorsByRegistry = /* @__PURE__ */ new WeakMap();
  const wrappedSelector = (...args) => {
    let selector = selectorsByRegistry.get(wrappedSelector.registry);
    if (!selector) {
      selector = registrySelector(wrappedSelector.registry.select);
      selectorsByRegistry.set(wrappedSelector.registry, selector);
    }
    return selector(...args);
  };
  wrappedSelector.isRegistrySelector = true;
  return wrappedSelector;
}
function createRegistryControl(registryControl) {
  registryControl.isRegistryControl = true;
  return registryControl;
}

//# sourceMappingURL=factory.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/controls.mjs
// packages/data/src/controls.ts

var SELECT = "@@data/SELECT";
var RESOLVE_SELECT = "@@data/RESOLVE_SELECT";
var DISPATCH = "@@data/DISPATCH";
function isStoreDescriptor(object) {
  return object !== null && typeof object === "object";
}
function controls_select(storeNameOrDescriptor, selectorName, ...args) {
  return {
    type: SELECT,
    storeKey: isStoreDescriptor(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}
function resolveSelect(storeNameOrDescriptor, selectorName, ...args) {
  return {
    type: RESOLVE_SELECT,
    storeKey: isStoreDescriptor(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}
function dispatch(storeNameOrDescriptor, actionName, ...args) {
  return {
    type: DISPATCH,
    storeKey: isStoreDescriptor(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    actionName,
    args
  };
}
var controls = { select: controls_select, resolveSelect, dispatch };
var builtinControls = {
  [SELECT]: createRegistryControl(
    (registry) => ({ storeKey, selectorName, args }) => registry.select(storeKey)[selectorName](...args)
  ),
  [RESOLVE_SELECT]: createRegistryControl(
    (registry) => ({ storeKey, selectorName, args }) => {
      const selector = registry.select(storeKey)[selectorName];
      const method = selector.hasResolver ? "resolveSelect" : "select";
      return registry[method](storeKey)[selectorName](
        ...args
      );
    }
  ),
  [DISPATCH]: createRegistryControl(
    (registry) => ({ storeKey, actionName, args }) => registry.dispatch(storeKey)[actionName](...args)
  )
};

//# sourceMappingURL=controls.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.mjs
var lock_unlock = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs
var is_promise = __webpack_require__("../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/promise-middleware.mjs
// packages/data/src/promise-middleware.ts

var promiseMiddleware = () => (next) => (action) => {
  if ((0,is_promise/* default */.A)(action)) {
    return action.then((resolvedAction) => {
      if (resolvedAction) {
        return next(resolvedAction);
      }
      return void 0;
    });
  }
  return next(action);
};
var promise_middleware_default = promiseMiddleware;

//# sourceMappingURL=promise-middleware.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/resolvers-cache-middleware.mjs
// packages/data/src/resolvers-cache-middleware.ts
var createResolversCacheMiddleware = (registry, storeName) => () => (next) => (action) => {
  const resolvers = registry.select(storeName).getCachedResolvers();
  const resolverEntries = Object.entries(
    resolvers
  );
  resolverEntries.forEach(([selectorName, resolversByArgs]) => {
    const resolver = registry.stores[storeName]?.resolvers?.[selectorName];
    if (!resolver || !resolver.shouldInvalidate) {
      return;
    }
    const { shouldInvalidate } = resolver;
    resolversByArgs.forEach((value, args) => {
      if (value === void 0) {
        return;
      }
      if (value.status !== "finished" && value.status !== "error") {
        return;
      }
      if (!shouldInvalidate(action, ...args)) {
        return;
      }
      registry.dispatch(storeName).invalidateResolution(selectorName, args);
    });
  });
  return next(action);
};
var resolvers_cache_middleware_default = createResolversCacheMiddleware;

//# sourceMappingURL=resolvers-cache-middleware.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/thunk-middleware.mjs
// packages/data/src/redux-store/thunk-middleware.ts
function createThunkMiddleware(args) {
  return () => (next) => (action) => {
    if (typeof action === "function") {
      return action(args);
    }
    return next(action);
  };
}

//# sourceMappingURL=thunk-middleware.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/utils.mjs
// packages/data/src/redux-store/metadata/utils.ts
var onSubKey = (actionProperty) => (reducer) => (state = {}, action) => {
  const key = action[actionProperty];
  if (key === void 0) {
    return state;
  }
  const nextKeyState = reducer(state[key], action);
  if (nextKeyState === state[key]) {
    return state;
  }
  return {
    ...state,
    [key]: nextKeyState
  };
};
function selectorArgsToStateKey(args) {
  if (args === void 0 || args === null) {
    return [];
  }
  const len = args.length;
  let idx = len;
  while (idx > 0 && args[idx - 1] === void 0) {
    idx--;
  }
  return idx === len ? args : args.slice(0, idx);
}

//# sourceMappingURL=utils.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/reducer.mjs
// packages/data/src/redux-store/metadata/reducer.ts


var subKeysIsResolved = onSubKey("selectorName")((state = new (equivalent_key_map_default())(), action) => {
  switch (action.type) {
    case "START_RESOLUTION": {
      const nextState = new (equivalent_key_map_default())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "resolving"
      });
      return nextState;
    }
    case "FINISH_RESOLUTION": {
      const nextState = new (equivalent_key_map_default())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "finished"
      });
      return nextState;
    }
    case "FAIL_RESOLUTION": {
      const nextState = new (equivalent_key_map_default())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "error",
        error: action.error
      });
      return nextState;
    }
    case "START_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default())(state);
      for (const resolutionArgs of action.args) {
        nextState.set(selectorArgsToStateKey(resolutionArgs), {
          status: "resolving"
        });
      }
      return nextState;
    }
    case "FINISH_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default())(state);
      for (const resolutionArgs of action.args) {
        nextState.set(selectorArgsToStateKey(resolutionArgs), {
          status: "finished"
        });
      }
      return nextState;
    }
    case "FAIL_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default())(state);
      action.args.forEach((resolutionArgs, idx) => {
        const resolutionState = {
          status: "error",
          error: void 0
        };
        const error = action.errors[idx];
        if (error) {
          resolutionState.error = error;
        }
        nextState.set(
          selectorArgsToStateKey(resolutionArgs),
          resolutionState
        );
      });
      return nextState;
    }
    case "INVALIDATE_RESOLUTION": {
      const nextState = new (equivalent_key_map_default())(state);
      nextState.delete(selectorArgsToStateKey(action.args));
      return nextState;
    }
  }
  return state;
});
var isResolved = (state = {}, action) => {
  switch (action.type) {
    case "INVALIDATE_RESOLUTION_FOR_STORE":
      return {};
    case "INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR": {
      if (action.selectorName in state) {
        const {
          [action.selectorName]: removedSelector,
          ...restState
        } = state;
        return restState;
      }
      return state;
    }
    case "START_RESOLUTION":
    case "FINISH_RESOLUTION":
    case "FAIL_RESOLUTION":
    case "START_RESOLUTIONS":
    case "FINISH_RESOLUTIONS":
    case "FAIL_RESOLUTIONS":
    case "INVALIDATE_RESOLUTION":
      return subKeysIsResolved(state, action);
    default:
      return state;
  }
};
var reducer_default = isResolved;

//# sourceMappingURL=reducer.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js
var rememo = __webpack_require__("../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js");
;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.mjs
// packages/data/src/redux-store/metadata/selectors.ts



function getResolutionState(state, selectorName, args) {
  const map = state[selectorName];
  if (!map) {
    return;
  }
  return map.get(selectorArgsToStateKey(args));
}
function getIsResolving(state, selectorName, args) {
  (0,deprecated_build_module/* default */.A)("wp.data.select( store ).getIsResolving", {
    since: "6.6",
    version: "6.8",
    alternative: "wp.data.select( store ).getResolutionState"
  });
  const resolutionState = getResolutionState(state, selectorName, args);
  return resolutionState && resolutionState.status === "resolving";
}
function hasStartedResolution(state, selectorName, args) {
  return getResolutionState(state, selectorName, args) !== void 0;
}
function hasFinishedResolution(state, selectorName, args) {
  const status = getResolutionState(state, selectorName, args)?.status;
  return status === "finished" || status === "error";
}
function hasResolutionFailed(state, selectorName, args) {
  return getResolutionState(state, selectorName, args)?.status === "error";
}
function getResolutionError(state, selectorName, args) {
  const resolutionState = getResolutionState(state, selectorName, args);
  return resolutionState?.status === "error" ? resolutionState.error : null;
}
function isResolving(state, selectorName, args) {
  return getResolutionState(state, selectorName, args)?.status === "resolving";
}
function getCachedResolvers(state) {
  return state;
}
function hasResolvingSelectors(state) {
  return Object.values(state).some(
    (selectorState) => (
      /**
       * This uses the internal `_map` property of `EquivalentKeyMap` for
       * optimization purposes, since the `EquivalentKeyMap` implementation
       * does not support a `.values()` implementation.
       *
       * @see https://github.com/aduth/equivalent-key-map
       */
      Array.from(selectorState._map.values()).some(
        (resolution) => resolution[1]?.status === "resolving"
      )
    )
  );
}
var countSelectorsByStatus = (0,rememo/* default */.A)(
  (state) => {
    const selectorsByStatus = {};
    Object.values(state).forEach(
      (selectorState) => (
        /**
         * This uses the internal `_map` property of `EquivalentKeyMap` for
         * optimization purposes, since the `EquivalentKeyMap` implementation
         * does not support a `.values()` implementation.
         *
         * @see https://github.com/aduth/equivalent-key-map
         */
        Array.from(selectorState._map.values()).forEach(
          (resolution) => {
            const currentStatus = resolution[1]?.status ?? "error";
            if (!selectorsByStatus[currentStatus]) {
              selectorsByStatus[currentStatus] = 0;
            }
            selectorsByStatus[currentStatus]++;
          }
        )
      )
    );
    return selectorsByStatus;
  },
  (state) => [state]
);

//# sourceMappingURL=selectors.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.mjs
// packages/data/src/redux-store/metadata/actions.ts
function startResolution(selectorName, args) {
  return {
    type: "START_RESOLUTION",
    selectorName,
    args
  };
}
function finishResolution(selectorName, args) {
  return {
    type: "FINISH_RESOLUTION",
    selectorName,
    args
  };
}
function failResolution(selectorName, args, error) {
  return {
    type: "FAIL_RESOLUTION",
    selectorName,
    args,
    error
  };
}
function startResolutions(selectorName, args) {
  return {
    type: "START_RESOLUTIONS",
    selectorName,
    args
  };
}
function finishResolutions(selectorName, args) {
  return {
    type: "FINISH_RESOLUTIONS",
    selectorName,
    args
  };
}
function failResolutions(selectorName, args, errors) {
  return {
    type: "FAIL_RESOLUTIONS",
    selectorName,
    args,
    errors
  };
}
function invalidateResolution(selectorName, args) {
  return {
    type: "INVALIDATE_RESOLUTION",
    selectorName,
    args
  };
}
function invalidateResolutionForStore() {
  return {
    type: "INVALIDATE_RESOLUTION_FOR_STORE"
  };
}
function invalidateResolutionForStoreSelector(selectorName) {
  return {
    type: "INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR",
    selectorName
  };
}

//# sourceMappingURL=actions.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.mjs
// packages/data/src/redux-store/index.ts













var trimUndefinedValues = (array) => {
  const result = [...array];
  for (let i = result.length - 1; i >= 0; i--) {
    if (result[i] === void 0) {
      result.splice(i, 1);
    }
  }
  return result;
};
var mapValues = (obj, callback) => Object.fromEntries(
  Object.entries(obj ?? {}).map(([key, value]) => [
    key,
    callback(value, key)
  ])
);
var devToolsReplacer = (_key, state) => {
  if (state instanceof Map) {
    return Object.fromEntries(state);
  }
  if (typeof window !== "undefined" && state instanceof window.HTMLElement) {
    return null;
  }
  return state;
};
function createResolversCache() {
  const cache = {};
  return {
    isRunning(selectorName, args) {
      return !!(cache[selectorName] && cache[selectorName].get(trimUndefinedValues(args)));
    },
    clear(selectorName, args) {
      if (cache[selectorName]) {
        cache[selectorName].delete(trimUndefinedValues(args));
      }
    },
    markAsRunning(selectorName, args) {
      if (!cache[selectorName]) {
        cache[selectorName] = new (equivalent_key_map_default())();
      }
      cache[selectorName].set(trimUndefinedValues(args), true);
    }
  };
}
function createBindingCache(getItem, bindItem) {
  const cache = /* @__PURE__ */ new WeakMap();
  return {
    get(itemName) {
      const item = getItem(itemName);
      if (!item) {
        return null;
      }
      let boundItem = cache.get(item);
      if (!boundItem) {
        boundItem = bindItem(item, itemName);
        cache.set(item, boundItem);
      }
      return boundItem;
    }
  };
}
function createPrivateProxy(publicItems, privateItems) {
  return new Proxy(publicItems, {
    get: (target, itemName) => privateItems.get(itemName) || Reflect.get(target, itemName)
  });
}
function createReduxStore(key, options) {
  const privateActions = {};
  const privateSelectors = {};
  const privateRegistrationFunctions = {
    privateActions,
    registerPrivateActions: (actions) => {
      Object.assign(privateActions, actions);
    },
    privateSelectors,
    registerPrivateSelectors: (selectors) => {
      Object.assign(privateSelectors, selectors);
    }
  };
  const storeDescriptor = {
    name: key,
    instantiate: (registry) => {
      const listeners = /* @__PURE__ */ new Set();
      const reducer = options.reducer;
      const thunkArgs = {
        registry,
        get dispatch() {
          return thunkDispatch;
        },
        get select() {
          return thunkSelect;
        },
        get resolveSelect() {
          return resolveSelectors;
        }
      };
      const store = instantiateReduxStore(
        key,
        options,
        registry,
        thunkArgs
      );
      (0,lock_unlock/* lock */.s)(store, privateRegistrationFunctions);
      const resolversCache = createResolversCache();
      function bindAction(action) {
        return (...args) => Promise.resolve(store.dispatch(action(...args)));
      }
      const actions = {
        ...mapValues(
          actions_namespaceObject,
          bindAction
        ),
        ...mapValues(
          options.actions,
          bindAction
        )
      };
      const allActions = createPrivateProxy(
        actions,
        createBindingCache(
          (name) => privateActions[name],
          bindAction
        )
      );
      const thunkDispatch = new Proxy(
        (action) => store.dispatch(action),
        {
          get: (_target, name) => allActions[name]
        }
      );
      (0,lock_unlock/* lock */.s)(actions, allActions);
      const resolvers = options.resolvers ? mapValues(
        options.resolvers,
        mapResolver
      ) : {};
      function bindSelector(selector, selectorName) {
        if (selector.isRegistrySelector) {
          selector.registry = registry;
        }
        const boundSelector = (...args) => {
          args = normalize(selector, args);
          const state = store.__unstableOriginalGetState();
          if (selector.isRegistrySelector) {
            selector.registry = registry;
          }
          return selector(state.root, ...args);
        };
        boundSelector.__unstableNormalizeArgs = selector.__unstableNormalizeArgs;
        const resolver = resolvers[selectorName];
        if (!resolver) {
          boundSelector.hasResolver = false;
          return boundSelector;
        }
        return mapSelectorWithResolver(
          boundSelector,
          selectorName,
          resolver,
          store,
          resolversCache,
          boundMetadataSelectors
        );
      }
      function bindMetadataSelector(metaDataSelector) {
        const boundSelector = (selectorName, selectorArgs, ...args) => {
          if (selectorName) {
            const targetSelector = options.selectors?.[selectorName];
            if (targetSelector) {
              selectorArgs = normalize(
                targetSelector,
                selectorArgs
              );
            }
          }
          const state = store.__unstableOriginalGetState();
          return metaDataSelector(
            state.metadata,
            selectorName,
            selectorArgs,
            ...args
          );
        };
        boundSelector.hasResolver = false;
        return boundSelector;
      }
      const boundMetadataSelectors = mapValues(
        selectors_namespaceObject,
        bindMetadataSelector
      );
      const boundSelectors = mapValues(
        options.selectors,
        bindSelector
      );
      const selectors = {
        ...boundMetadataSelectors,
        ...boundSelectors
      };
      const boundPrivateSelectors = createBindingCache(
        (name) => privateSelectors[name],
        bindSelector
      );
      const allSelectors = createPrivateProxy(
        selectors,
        boundPrivateSelectors
      );
      for (const selectorName of Object.keys(privateSelectors)) {
        boundPrivateSelectors.get(selectorName);
      }
      const thunkSelect = new Proxy(
        (selector) => selector(store.__unstableOriginalGetState()),
        {
          get: (_target, name) => allSelectors[name]
        }
      );
      (0,lock_unlock/* lock */.s)(selectors, allSelectors);
      const bindResolveSelector = mapResolveSelector(
        store,
        boundMetadataSelectors
      );
      const resolveSelectors = mapValues(
        boundSelectors,
        bindResolveSelector
      );
      const allResolveSelectors = createPrivateProxy(
        resolveSelectors,
        createBindingCache(
          (name) => boundPrivateSelectors.get(name),
          bindResolveSelector
        )
      );
      (0,lock_unlock/* lock */.s)(resolveSelectors, allResolveSelectors);
      const bindSuspendSelector = mapSuspendSelector(
        store,
        boundMetadataSelectors
      );
      const suspendSelectors = {
        ...boundMetadataSelectors,
        // no special suspense behavior
        ...mapValues(boundSelectors, bindSuspendSelector)
      };
      const allSuspendSelectors = createPrivateProxy(
        suspendSelectors,
        createBindingCache(
          (name) => boundPrivateSelectors.get(name),
          bindSuspendSelector
        )
      );
      (0,lock_unlock/* lock */.s)(suspendSelectors, allSuspendSelectors);
      const getSelectors = () => selectors;
      const getActions = () => actions;
      const getResolveSelectors = () => resolveSelectors;
      const getSuspendSelectors = () => suspendSelectors;
      store.__unstableOriginalGetState = store.getState;
      store.getState = () => store.__unstableOriginalGetState().root;
      const subscribe = (listener) => {
        listeners.add(listener);
        return () => listeners.delete(listener);
      };
      let lastState = store.__unstableOriginalGetState();
      store.subscribe(() => {
        const state = store.__unstableOriginalGetState();
        const hasChanged = state !== lastState;
        lastState = state;
        if (hasChanged) {
          for (const listener of listeners) {
            listener();
          }
        }
      });
      return {
        reducer,
        store,
        actions,
        selectors,
        resolvers,
        getSelectors,
        getResolveSelectors,
        getSuspendSelectors,
        getActions,
        subscribe
      };
    }
  };
  (0,lock_unlock/* lock */.s)(storeDescriptor, privateRegistrationFunctions);
  return storeDescriptor;
}
function instantiateReduxStore(key, options, registry, thunkArgs) {
  const controls = {
    ...options.controls,
    ...builtinControls
  };
  const normalizedControls = mapValues(
    controls,
    (control) => control.isRegistryControl ? control(registry) : control
  );
  const middlewares = [
    resolvers_cache_middleware_default(registry, key),
    promise_middleware_default,
    (0,build_module/* default */.A)(normalizedControls),
    createThunkMiddleware(thunkArgs)
  ];
  const enhancers = [(0,redux/* applyMiddleware */.Tw)(...middlewares)];
  if (typeof window !== "undefined" && window.__REDUX_DEVTOOLS_EXTENSION__) {
    enhancers.push(
      window.__REDUX_DEVTOOLS_EXTENSION__({
        name: key,
        instanceId: key,
        serialize: {
          replacer: devToolsReplacer
        }
      })
    );
  }
  const { reducer, initialState } = options;
  const enhancedReducer = combineReducers({
    metadata: reducer_default,
    root: reducer
  });
  return (0,redux/* createStore */.y$)(
    enhancedReducer,
    { root: initialState },
    (0,compose/* default */.A)(...enhancers)
  );
}
function mapResolveSelector(store, boundMetadataSelectors) {
  return (selector, selectorName) => {
    if (!selector.hasResolver) {
      return async (...args) => selector(...args);
    }
    return (...args) => new Promise((resolve, reject) => {
      const hasFinished = () => {
        return boundMetadataSelectors.hasFinishedResolution(
          selectorName,
          args
        );
      };
      const finalize = (result2) => {
        const hasFailed = boundMetadataSelectors.hasResolutionFailed(
          selectorName,
          args
        );
        if (hasFailed) {
          const error = boundMetadataSelectors.getResolutionError(
            selectorName,
            args
          );
          reject(error);
        } else {
          resolve(result2);
        }
      };
      const getResult = () => selector(...args);
      const result = getResult();
      if (hasFinished()) {
        return finalize(result);
      }
      const unsubscribe = store.subscribe(() => {
        if (hasFinished()) {
          unsubscribe();
          finalize(getResult());
        }
      });
    });
  };
}
function mapSuspendSelector(store, boundMetadataSelectors) {
  return (selector, selectorName) => {
    if (!selector.hasResolver) {
      return selector;
    }
    return (...args) => {
      const result = selector(...args);
      if (boundMetadataSelectors.hasFinishedResolution(
        selectorName,
        args
      )) {
        if (boundMetadataSelectors.hasResolutionFailed(
          selectorName,
          args
        )) {
          throw boundMetadataSelectors.getResolutionError(
            selectorName,
            args
          );
        }
        return result;
      }
      throw new Promise((resolve) => {
        const unsubscribe = store.subscribe(() => {
          if (boundMetadataSelectors.hasFinishedResolution(
            selectorName,
            args
          )) {
            resolve();
            unsubscribe();
          }
        });
      });
    };
  };
}
function mapResolver(resolver) {
  if (resolver.fulfill) {
    return resolver;
  }
  return {
    ...resolver,
    // Copy the enumerable properties of the resolver function.
    fulfill: resolver
    // Add the fulfill method.
  };
}
function mapSelectorWithResolver(selector, selectorName, resolver, store, resolversCache, boundMetadataSelectors) {
  function fulfillSelector(args) {
    if (resolversCache.isRunning(selectorName, args) || boundMetadataSelectors.hasStartedResolution(selectorName, args)) {
      return;
    }
    resolversCache.markAsRunning(selectorName, args);
    setTimeout(async () => {
      resolversCache.clear(selectorName, args);
      store.dispatch(
        startResolution(selectorName, args)
      );
      try {
        const isFulfilled = typeof resolver.isFulfilled === "function" && resolver.isFulfilled(store.getState(), ...args);
        if (!isFulfilled) {
          const action = resolver.fulfill(...args);
          if (action) {
            await store.dispatch(action);
          }
        }
        store.dispatch(
          finishResolution(selectorName, args)
        );
      } catch (error) {
        store.dispatch(
          failResolution(selectorName, args, error)
        );
      }
    }, 0);
  }
  const selectorResolver = (...args) => {
    args = normalize(selector, args);
    fulfillSelector(args);
    return selector(...args);
  };
  selectorResolver.hasResolver = true;
  return selectorResolver;
}
function normalize(selector, args) {
  if (selector.__unstableNormalizeArgs && typeof selector.__unstableNormalizeArgs === "function" && args?.length) {
    return selector.__unstableNormalizeArgs(args);
  }
  return args;
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  I: () => (/* binding */ createRegistry)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.mjs + 10 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/store/index.mjs
// packages/data/src/store/index.ts
var coreDataStore = {
  name: "core/data",
  instantiate(registry) {
    const getCoreDataSelector = (selectorName) => (key, ...args) => {
      return registry.select(key)[selectorName](...args);
    };
    const getCoreDataAction = (actionName) => (key, ...args) => {
      return registry.dispatch(key)[actionName](...args);
    };
    return {
      getSelectors() {
        return Object.fromEntries(
          [
            "getIsResolving",
            "hasStartedResolution",
            "hasFinishedResolution",
            "isResolving",
            "getCachedResolvers"
          ].map((selectorName) => [
            selectorName,
            getCoreDataSelector(selectorName)
          ])
        );
      },
      getActions() {
        return Object.fromEntries(
          [
            "startResolution",
            "finishResolution",
            "invalidateResolution",
            "invalidateResolutionForStore",
            "invalidateResolutionForStoreSelector"
          ].map((actionName) => [
            actionName,
            getCoreDataAction(actionName)
          ])
        );
      },
      subscribe() {
        return () => () => {
        };
      }
    };
  }
};
var store_default = coreDataStore;

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/utils/emitter.mjs
// packages/data/src/utils/emitter.ts
function createEmitter() {
  let isPaused = false;
  let isPending = false;
  const listeners = /* @__PURE__ */ new Set();
  const notifyListeners = () => (
    // We use Array.from to clone the listeners Set
    // This ensures that we don't run a listener
    // that was added as a response to another listener.
    Array.from(listeners).forEach((listener) => listener())
  );
  return {
    get isPaused() {
      return isPaused;
    },
    subscribe(listener) {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
    pause() {
      isPaused = true;
    },
    resume() {
      isPaused = false;
      if (isPending) {
        isPending = false;
        notifyListeners();
      }
    },
    emit() {
      if (isPaused) {
        isPending = true;
        return;
      }
      notifyListeners();
    }
  };
}

//# sourceMappingURL=emitter.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.mjs
var lock_unlock = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.mjs");
;// ../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.mjs
// packages/data/src/registry.ts





function getStoreName(storeNameOrDescriptor) {
  return typeof storeNameOrDescriptor === "string" ? storeNameOrDescriptor : storeNameOrDescriptor.name;
}
function createRegistry(storeConfigs = {}, parent = null) {
  const stores = {};
  const emitter = createEmitter();
  let listeningStores = null;
  function globalListener() {
    emitter.emit();
  }
  const subscribe = (listener, storeNameOrDescriptor) => {
    if (!storeNameOrDescriptor) {
      return emitter.subscribe(listener);
    }
    const storeName = getStoreName(storeNameOrDescriptor);
    const store = stores[storeName];
    if (store) {
      return store.subscribe(listener);
    }
    if (!parent) {
      return emitter.subscribe(listener);
    }
    return parent.subscribe(listener, storeNameOrDescriptor);
  };
  function select(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getSelectors();
    }
    return parent?.select(storeName);
  }
  function __unstableMarkListeningStores(callback, ref) {
    listeningStores = /* @__PURE__ */ new Set();
    try {
      return callback.call(this);
    } finally {
      ref.current = Array.from(listeningStores);
      listeningStores = null;
    }
  }
  function resolveSelect(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getResolveSelectors();
    }
    return parent && parent.resolveSelect(storeName);
  }
  function suspendSelect(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getSuspendSelectors();
    }
    return parent && parent.suspendSelect(storeName);
  }
  function dispatch(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    const store = stores[storeName];
    if (store) {
      return store.getActions();
    }
    return parent && parent.dispatch(storeName);
  }
  function withPlugins(attributes) {
    return Object.fromEntries(
      Object.entries(attributes).map(([key, attribute]) => {
        if (typeof attribute !== "function") {
          return [key, attribute];
        }
        return [
          key,
          (...args) => {
            return registry[key](...args);
          }
        ];
      })
    );
  }
  function registerStoreInstance(name, createStoreFunc) {
    if (stores[name]) {
      console.error('Store "' + name + '" is already registered.');
      return stores[name];
    }
    const store = createStoreFunc();
    if (typeof store.getSelectors !== "function") {
      throw new TypeError("store.getSelectors must be a function");
    }
    if (typeof store.getActions !== "function") {
      throw new TypeError("store.getActions must be a function");
    }
    if (typeof store.subscribe !== "function") {
      throw new TypeError("store.subscribe must be a function");
    }
    store.emitter = createEmitter();
    const currentSubscribe = store.subscribe;
    store.subscribe = (listener) => {
      const unsubscribeFromEmitter = store.emitter.subscribe(listener);
      const unsubscribeFromStore = currentSubscribe(() => {
        if (store.emitter.isPaused) {
          store.emitter.emit();
          return;
        }
        listener();
      });
      return () => {
        unsubscribeFromStore?.();
        unsubscribeFromEmitter?.();
      };
    };
    stores[name] = store;
    store.subscribe(globalListener);
    if (parent) {
      try {
        (0,lock_unlock/* unlock */.T)(store.store).registerPrivateActions(
          (0,lock_unlock/* unlock */.T)(parent).privateActionsOf(name)
        );
        (0,lock_unlock/* unlock */.T)(store.store).registerPrivateSelectors(
          (0,lock_unlock/* unlock */.T)(parent).privateSelectorsOf(name)
        );
      } catch {
      }
    }
    return store;
  }
  function register(store) {
    registerStoreInstance(
      store.name,
      () => store.instantiate(registry)
    );
  }
  function registerGenericStore(name, store) {
    (0,build_module/* default */.A)("wp.data.registerGenericStore", {
      since: "5.9",
      alternative: "wp.data.register( storeDescriptor )"
    });
    registerStoreInstance(name, () => store);
  }
  function registerStore(storeName, options) {
    if (!options.reducer) {
      throw new TypeError("Must specify store reducer");
    }
    const store = registerStoreInstance(
      storeName,
      () => (0,redux_store/* default */.A)(storeName, options).instantiate(
        registry
      )
    );
    return store.store;
  }
  function batch(callback) {
    if (emitter.isPaused) {
      callback();
      return;
    }
    emitter.pause();
    Object.values(stores).forEach((store) => store.emitter.pause());
    try {
      callback();
    } finally {
      emitter.resume();
      Object.values(stores).forEach(
        (store) => store.emitter.resume()
      );
    }
  }
  let registry = {
    batch,
    stores,
    namespaces: stores,
    // TODO: Deprecate/remove this.
    subscribe,
    select,
    resolveSelect,
    suspendSelect,
    dispatch,
    use,
    register,
    registerGenericStore,
    registerStore,
    __unstableMarkListeningStores
  };
  function use(plugin, options) {
    if (!plugin) {
      return;
    }
    registry = {
      ...registry,
      ...plugin(registry, options)
    };
    return registry;
  }
  registry.register(store_default);
  for (const [name, config] of Object.entries(storeConfigs)) {
    registry.register((0,redux_store/* default */.A)(name, config));
  }
  if (parent) {
    parent.subscribe(globalListener);
  }
  const registryWithPlugins = withPlugins(
    registry
  );
  (0,lock_unlock/* lock */.s)(registryWithPlugins, {
    privateActionsOf: (name) => {
      try {
        return (0,lock_unlock/* unlock */.T)(stores[name].store).privateActions;
      } catch {
        return {};
      }
    },
    privateSelectorsOf: (name) => {
      try {
        return (0,lock_unlock/* unlock */.T)(stores[name].store).privateSelectors;
      } catch {
        return {};
      }
    }
  });
  return registryWithPlugins;
}

//# sourceMappingURL=registry.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/select.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   L: () => (/* binding */ select)
/* harmony export */ });
/* harmony import */ var _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.44.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.mjs");
// packages/data/src/select.ts

function select(storeNameOrDescriptor) {
  return _default_registry_mjs__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.select(
    storeNameOrDescriptor
  );
}

//# sourceMappingURL=select.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/help.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ help_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var help_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M12 4a8 8 0 1 1 .001 16.001A8 8 0 0 1 12 4Zm0 1.5a6.5 6.5 0 1 0-.001 13.001A6.5 6.5 0 0 0 12 5.5Zm.75 11h-1.5V15h1.5v1.5Zm-.445-9.234a3 3 0 0 1 .445 5.89V14h-1.5v-1.25c0-.57.452-.958.917-1.01A1.5 1.5 0 0 0 12 8.75a1.5 1.5 0 0 0-1.5 1.5H9a3 3 0 0 1 3.305-2.984Z" }) });

//# sourceMappingURL=help.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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




/***/ })

}]);