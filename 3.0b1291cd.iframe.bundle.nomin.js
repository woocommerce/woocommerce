"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex-item/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _ui_context__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js");
/* harmony import */ var _view__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/view/component.js");
/* harmony import */ var _hook__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js");



/**
 * Internal dependencies
 */



/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').FlexItemProps, 'div'>} props
 * @param {import('react').ForwardedRef<any>}                                                           forwardedRef
 */

function FlexItem(props, forwardedRef) {
  const flexItemProps = (0,_hook__WEBPACK_IMPORTED_MODULE_0__/* .useFlexItem */ .K)(props);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_view__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)({}, flexItemProps, {
    ref: forwardedRef
  }));
}
/**
 * `FlexItem` is a primitive layout component that aligns content within layout containers like `Flex`.
 *
 * @example
 * ```jsx
 * <Flex>
 * 	<FlexItem>...</FlexItem>
 * </Flex>
 * ```
 */


const ConnectedFlexItem = (0,_ui_context__WEBPACK_IMPORTED_MODULE_4__/* .contextConnect */ .KZ)(FlexItem, 'FlexItem');
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ConnectedFlexItem);
//# sourceMappingURL=component.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   K: () => (/* binding */ useFlexItem)
/* harmony export */ });
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
/* harmony import */ var _ui_context__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/context.js");
/* harmony import */ var _styles__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/styles.js");
/* harmony import */ var _utils_hooks_use_cx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */





/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').FlexItemProps, 'div'>} props
 */

function useFlexItem(props) {
  const {
    className,
    display: displayProp,
    isBlock = false,
    ...otherProps
  } = (0,_ui_context__WEBPACK_IMPORTED_MODULE_0__/* .useContextSystem */ .A)(props, 'FlexItem');
  const sx = {};
  const contextDisplay = (0,_context__WEBPACK_IMPORTED_MODULE_1__/* .useFlexContext */ .a)().flexItemDisplay;
  sx.Base = /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_2__/* .css */ .AH)({
    display: displayProp || contextDisplay
  },  true ? "" : 0,  true ? "" : 0);
  const cx = (0,_utils_hooks_use_cx__WEBPACK_IMPORTED_MODULE_3__/* .useCx */ .l)();
  const classes = cx(_styles__WEBPACK_IMPORTED_MODULE_4__/* .Item */ .q7, sx.Base, isBlock && _styles__WEBPACK_IMPORTED_MODULE_4__/* .block */ .om, className);
  return { ...otherProps,
    className: classes
  };
}
//# sourceMappingURL=hook.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/input-base.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ input_base)
});

// UNUSED EXPORTS: InputBase

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/styles/input-control-styles.js
var input_control_styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/styles/input-control-styles.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/backdrop.js


/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



function Backdrop(_ref) {
  let {
    disabled = false,
    isFocused = false
  } = _ref;
  return (0,react.createElement)(input_control_styles/* BackdropUI */.Hr, {
    "aria-hidden": "true",
    className: "components-input-control__backdrop",
    disabled: disabled,
    isFocused: isFocused
  });
}

const MemoizedBackdrop = (0,react.memo)(Backdrop);
/* harmony default export */ const backdrop = (MemoizedBackdrop);
//# sourceMappingURL=backdrop.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/visually-hidden/component.js + 1 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/label.js



/**
 * Internal dependencies
 */


function Label(_ref) {
  let {
    children,
    hideLabelFromVision,
    htmlFor,
    ...props
  } = _ref;
  if (!children) return null;

  if (hideLabelFromVision) {
    return (0,react.createElement)(component/* default */.A, {
      as: "label",
      htmlFor: htmlFor
    }, children);
  }

  return (0,react.createElement)(input_control_styles/* Label */.JU, (0,esm_extends/* default */.A)({
    htmlFor: htmlFor
  }, props), children);
}
//# sourceMappingURL=label.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/input-base.js



/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */





function useUniqueId(idProp) {
  const instanceId = (0,use_instance_id/* default */.A)(InputBase);
  const id = `input-base-control-${instanceId}`;
  return idProp || id;
} // Adapter to map props for the new ui/flex compopnent.


function getUIFlexProps(labelPosition) {
  const props = {};

  switch (labelPosition) {
    case 'top':
      props.direction = 'column';
      props.gap = 0;
      break;

    case 'bottom':
      props.direction = 'column-reverse';
      props.gap = 0;
      break;

    case 'edge':
      props.justify = 'space-between';
      break;
  }

  return props;
}

function InputBase(_ref, ref) {
  let {
    __unstableInputWidth,
    children,
    className,
    disabled = false,
    hideLabelFromVision = false,
    labelPosition,
    id: idProp,
    isFocused = false,
    label,
    prefix,
    size = 'default',
    suffix,
    ...props
  } = _ref;
  const id = useUniqueId(idProp);
  const hideLabel = hideLabelFromVision || !label;
  return (// @ts-expect-error The `direction` prop from Flex (FlexDirection) conflicts with legacy SVGAttributes `direction` (string) that come from React intrinsic prop definitions.
    (0,react.createElement)(input_control_styles/* Root */.bL, (0,esm_extends/* default */.A)({}, props, getUIFlexProps(labelPosition), {
      className: className,
      isFocused: isFocused,
      labelPosition: labelPosition,
      ref: ref
    }), (0,react.createElement)(input_control_styles/* LabelWrapper */.cR, null, (0,react.createElement)(Label, {
      className: "components-input-control__label",
      hideLabelFromVision: hideLabelFromVision,
      labelPosition: labelPosition,
      htmlFor: id,
      size: size
    }, label)), (0,react.createElement)(input_control_styles/* Container */.mc, {
      __unstableInputWidth: __unstableInputWidth,
      className: "components-input-control__container",
      disabled: disabled,
      hideLabel: hideLabel,
      labelPosition: labelPosition
    }, prefix && (0,react.createElement)(input_control_styles/* Prefix */.b3, {
      className: "components-input-control__prefix"
    }, prefix), children, suffix && (0,react.createElement)(input_control_styles/* Suffix */.sZ, {
      className: "components-input-control__suffix"
    }, suffix), (0,react.createElement)(backdrop, {
      disabled: disabled,
      isFocused: isFocused
    })))
  );
}
/* harmony default export */ const input_base = ((0,react.forwardRef)(InputBase));
//# sourceMappingURL=input-base.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/styles/input-control-styles.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Hr: () => (/* binding */ BackdropUI),
/* harmony export */   JU: () => (/* binding */ Label),
/* harmony export */   b3: () => (/* binding */ Prefix),
/* harmony export */   bL: () => (/* binding */ Root),
/* harmony export */   cR: () => (/* binding */ LabelWrapper),
/* harmony export */   mc: () => (/* binding */ Container),
/* harmony export */   pd: () => (/* binding */ Input),
/* harmony export */   sZ: () => (/* binding */ Suffix)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@emotion+styled@11.11.0_@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2__@types+react@17.0.71_react@17.0.2/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
/* harmony import */ var _flex__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js");
/* harmony import */ var _flex__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex-item/component.js");
/* harmony import */ var _text__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/rtl.js");




function _EMOTION_STRINGIFIED_CSS_ERROR__() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }

/**
 * External dependencies
 */





var _ref6 =  true ? {
  name: "1739oy8",
  styles: "z-index:1"
} : 0;

const rootFocusedStyles = _ref7 => {
  let {
    isFocused
  } = _ref7;
  if (!isFocused) return '';
  return _ref6;
};

var _ref3 =  true ? {
  name: "2o6p8u",
  styles: "justify-content:space-between"
} : 0;

var _ref4 =  true ? {
  name: "14qk3ip",
  styles: "align-items:flex-start;flex-direction:column-reverse"
} : 0;

var _ref5 =  true ? {
  name: "hbng6e",
  styles: "align-items:flex-start;flex-direction:column"
} : 0;

const rootLabelPositionStyles = _ref8 => {
  let {
    labelPosition
  } = _ref8;

  switch (labelPosition) {
    case 'top':
      return _ref5;

    case 'bottom':
      return _ref4;

    case 'edge':
      return _ref3;

    default:
      return '';
  }
};

const Root = /*#__PURE__*/(0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(_flex__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A,  true ? {
  target: "em5sgkm7"
} : 0)("position:relative;border-radius:2px;padding-top:0;", rootFocusedStyles, " ", rootLabelPositionStyles, ";" + ( true ? "" : 0));

const containerDisabledStyles = _ref9 => {
  let {
    disabled
  } = _ref9;
  const backgroundColor = disabled ? _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.ui.backgroundDisabled : _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.ui.background;
  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)({
    backgroundColor
  },  true ? "" : 0,  true ? "" : 0);
}; // Normalizes the margins from the <Flex /> (components/ui/flex/) container.


var _ref2 =  true ? {
  name: "wyxldh",
  styles: "margin:0 !important"
} : 0;

const containerMarginStyles = _ref10 => {
  let {
    hideLabel
  } = _ref10;
  return hideLabel ? _ref2 : null;
};

var _ref =  true ? {
  name: "1d3w5wq",
  styles: "width:100%"
} : 0;

const containerWidthStyles = _ref11 => {
  let {
    __unstableInputWidth,
    labelPosition
  } = _ref11;
  if (!__unstableInputWidth) return _ref;
  if (labelPosition === 'side') return '';

  if (labelPosition === 'edge') {
    return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)({
      flex: `0 0 ${__unstableInputWidth}`
    },  true ? "" : 0,  true ? "" : 0);
  }

  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)({
    width: __unstableInputWidth
  },  true ? "" : 0,  true ? "" : 0);
};

const Container = (0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("div",  true ? {
  target: "em5sgkm6"
} : 0)("align-items:center;box-sizing:border-box;border-radius:inherit;display:flex;flex:1;position:relative;", containerDisabledStyles, " ", containerMarginStyles, " ", containerWidthStyles, ";" + ( true ? "" : 0));

const disabledStyles = _ref12 => {
  let {
    disabled
  } = _ref12;
  if (!disabled) return '';
  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)({
    color: _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.ui.textDisabled
  },  true ? "" : 0,  true ? "" : 0);
};

const fontSizeStyles = _ref13 => {
  let {
    inputSize: size
  } = _ref13;
  const sizes = {
    default: '13px',
    small: '11px',
    '__unstable-large': '13px'
  };
  const fontSize = sizes[size] || sizes.default;
  const fontSizeMobile = '16px';
  if (!fontSize) return '';
  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)("font-size:", fontSizeMobile, ";@media ( min-width: 600px ){font-size:", fontSize, ";}" + ( true ? "" : 0),  true ? "" : 0);
};

const sizeStyles = _ref14 => {
  let {
    inputSize: size
  } = _ref14;
  const sizes = {
    default: {
      height: 30,
      lineHeight: 1,
      minHeight: 30,
      paddingLeft: 8,
      paddingRight: 8
    },
    small: {
      height: 24,
      lineHeight: 1,
      minHeight: 24,
      paddingLeft: 8,
      paddingRight: 8
    },
    '__unstable-large': {
      height: 40,
      lineHeight: 1,
      minHeight: 40,
      paddingLeft: 16,
      paddingRight: 16
    }
  };
  const style = sizes[size] || sizes.default;
  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)(style,  true ? "" : 0,  true ? "" : 0);
};

const dragStyles = _ref15 => {
  let {
    isDragging,
    dragCursor
  } = _ref15;
  let defaultArrowStyles;
  let activeDragCursorStyles;

  if (isDragging) {
    defaultArrowStyles = /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)("cursor:", dragCursor, ";user-select:none;&::-webkit-outer-spin-button,&::-webkit-inner-spin-button{-webkit-appearance:none!important;margin:0!important;}" + ( true ? "" : 0),  true ? "" : 0);
  }

  if (isDragging && dragCursor) {
    activeDragCursorStyles = /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)("&:active{cursor:", dragCursor, ";}" + ( true ? "" : 0),  true ? "" : 0);
  }

  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)(defaultArrowStyles, " ", activeDragCursorStyles, ";" + ( true ? "" : 0),  true ? "" : 0);
}; // TODO: Resolve need to use &&& to increase specificity
// https://github.com/WordPress/gutenberg/issues/18483


const Input = (0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("input",  true ? {
  target: "em5sgkm5"
} : 0)("&&&{background-color:transparent;box-sizing:border-box;border:none;box-shadow:none!important;color:", _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.black, ";display:block;font-family:inherit;margin:0;outline:none;width:100%;", dragStyles, " ", disabledStyles, " ", fontSizeStyles, " ", sizeStyles, " &::-webkit-input-placeholder{line-height:normal;}}" + ( true ? "" : 0));

const labelMargin = _ref16 => {
  let {
    labelPosition
  } = _ref16;
  let marginBottom = 8;

  if (labelPosition === 'edge' || labelPosition === 'side') {
    marginBottom = 0;
  }

  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)({
    marginTop: 0,
    marginRight: 0,
    marginBottom,
    marginLeft: 0
  },  true ? "" : 0,  true ? "" : 0);
};

const BaseLabel = /*#__PURE__*/(0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(_text__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A,  true ? {
  target: "em5sgkm4"
} : 0)("&&&{box-sizing:border-box;color:currentColor;display:block;padding-top:0;padding-bottom:0;max-width:100%;z-index:1;", labelMargin, " overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}" + ( true ? "" : 0));

const Label = props => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.createElement)(BaseLabel, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)({}, props, {
  as: "label"
}));
const LabelWrapper = /*#__PURE__*/(0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(_flex__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A,  true ? {
  target: "em5sgkm3"
} : 0)( true ? {
  name: "1b6uupn",
  styles: "max-width:calc( 100% - 10px )"
} : 0);

const backdropFocusedStyles = _ref17 => {
  let {
    disabled,
    isFocused
  } = _ref17;
  let borderColor = isFocused ? _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.ui.borderFocus : _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.ui.border;
  let boxShadow;

  if (isFocused) {
    boxShadow = `0 0 0 1px ${_utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.ui.borderFocus} inset`;
  }

  if (disabled) {
    borderColor = _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .lm.ui.borderDisabled;
  }

  return /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_3__/* .css */ .AH)({
    boxShadow,
    borderColor,
    borderStyle: 'solid',
    borderWidth: 1
  },  true ? "" : 0,  true ? "" : 0);
};

const BackdropUI = (0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("div",  true ? {
  target: "em5sgkm2"
} : 0)("&&&{box-sizing:border-box;border-radius:inherit;bottom:0;left:0;margin:0;padding:0;pointer-events:none;position:absolute;right:0;top:0;", backdropFocusedStyles, " ", (0,_utils__WEBPACK_IMPORTED_MODULE_8__/* .rtl */ .h)({
  paddingLeft: 2
}), ";}" + ( true ? "" : 0));
const Prefix = (0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("span",  true ? {
  target: "em5sgkm1"
} : 0)( true ? {
  name: "pvvbxf",
  styles: "box-sizing:border-box;display:block"
} : 0);
const Suffix = (0,_emotion_styled_base__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)("span",  true ? {
  target: "em5sgkm0"
} : 0)( true ? {
  name: "jgf79h",
  styles: "align-items:center;align-self:stretch;box-sizing:border-box;display:flex"
} : 0);
//# sourceMappingURL=input-control-styles.js.map

/***/ })

}]);