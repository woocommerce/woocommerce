"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[897],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ select_control)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js + 1 modules
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/input-base.js + 2 modules
var input_base = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/input-base.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+styled@11.11.0_@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2__@types+react@17.0.71_react@17.0.2/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js
var emotion_styled_base_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+styled@11.11.0_@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2__@types+react@17.0.71_react@17.0.2/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js + 1 modules
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/rtl.js
var rtl = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/rtl.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/styles/select-control-styles.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const disabledStyles = _ref => {
  let {
    disabled
  } = _ref;
  if (!disabled) return '';
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
    color: colors_values/* COLORS */.lm.ui.textDisabled
  },  true ? "" : 0,  true ? "" : 0);
};

const fontSizeStyles = _ref2 => {
  let {
    selectSize
  } = _ref2;
  const sizes = {
    default: '13px',
    small: '11px',
    '__unstable-large': '13px'
  };
  const fontSize = sizes[selectSize];
  const fontSizeMobile = '16px';
  if (!fontSize) return '';
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("font-size:", fontSizeMobile, ";@media ( min-width: 600px ){font-size:", fontSize, ";}" + ( true ? "" : 0),  true ? "" : 0);
};

const sizeStyles = _ref3 => {
  let {
    selectSize
  } = _ref3;
  const sizes = {
    default: {
      height: 30,
      lineHeight: 1,
      minHeight: 30
    },
    small: {
      height: 24,
      lineHeight: 1,
      minHeight: 24
    },
    '__unstable-large': {
      height: 40,
      lineHeight: 1,
      minHeight: 40
    }
  };
  const style = sizes[selectSize] || sizes.default;
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)(style,  true ? "" : 0,  true ? "" : 0);
};

const sizePaddings = _ref4 => {
  let {
    selectSize = 'default'
  } = _ref4;
  const sizes = {
    default: {
      paddingLeft: 8,
      paddingRight: 24
    },
    small: {
      paddingLeft: 8,
      paddingRight: 24
    },
    '__unstable-large': {
      paddingLeft: 16,
      paddingRight: 32
    }
  };
  return (0,rtl/* rtl */.h)(sizes[selectSize]);
}; // TODO: Resolve need to use &&& to increase specificity
// https://github.com/WordPress/gutenberg/issues/18483


const Select = (0,emotion_styled_base_browser_esm/* default */.A)("select",  true ? {
  target: "e1mv6sxx1"
} : 0)("&&&{appearance:none;background:transparent;box-sizing:border-box;border:none;box-shadow:none!important;color:", colors_values/* COLORS */.lm.black, ";display:block;font-family:inherit;margin:0;width:100%;", disabledStyles, ";", fontSizeStyles, ";", sizeStyles, ";", sizePaddings, ";}" + ( true ? "" : 0));
const DownArrowWrapper = (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1mv6sxx0"
} : 0)("align-items:center;bottom:0;box-sizing:border-box;display:flex;padding:0 4px;pointer-events:none;position:absolute;top:0;", (0,rtl/* rtl */.h)({
  right: 0
}), " svg{display:block;}" + ( true ? "" : 0));
//# sourceMappingURL=select-control-styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js



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
  const instanceId = (0,use_instance_id/* default */.A)(SelectControl);
  const id = `inspector-select-control-${instanceId}`;
  return idProp || id;
}

function SelectControl(_ref, ref) {
  let {
    className,
    disabled = false,
    help,
    hideLabelFromVision,
    id: idProp,
    label,
    multiple = false,
    onBlur = lodash.noop,
    onChange = lodash.noop,
    onFocus = lodash.noop,
    options = [],
    size = 'default',
    value: valueProp,
    labelPosition = 'top',
    children,
    prefix,
    suffix,
    ...props
  } = _ref;
  const [isFocused, setIsFocused] = (0,react.useState)(false);
  const id = useUniqueId(idProp);
  const helpId = help ? `${id}__help` : undefined; // Disable reason: A select with an onchange throws a warning.

  if ((0,lodash.isEmpty)(options) && !children) return null;

  const handleOnBlur = event => {
    onBlur(event);
    setIsFocused(false);
  };

  const handleOnFocus = event => {
    onFocus(event);
    setIsFocused(true);
  };

  const handleOnChange = event => {
    if (multiple) {
      const selectedOptions = Array.from(event.target.options).filter(_ref2 => {
        let {
          selected
        } = _ref2;
        return selected;
      });
      const newValues = selectedOptions.map(_ref3 => {
        let {
          value
        } = _ref3;
        return value;
      });
      onChange(newValues);
      return;
    }

    onChange(event.target.value, {
      event
    });
  };

  const classes = classnames_default()('components-select-control', className);
  /* eslint-disable jsx-a11y/no-onchange */

  return (0,react.createElement)(base_control/* default */.Ay, {
    help: help,
    id: id
  }, (0,react.createElement)(input_base/* default */.A, {
    className: classes,
    disabled: disabled,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    isFocused: isFocused,
    label: label,
    size: size,
    suffix: suffix || (0,react.createElement)(DownArrowWrapper, null, (0,react.createElement)(icon/* default */.A, {
      icon: chevron_down/* default */.A,
      size: 18
    })),
    prefix: prefix,
    labelPosition: labelPosition
  }, (0,react.createElement)(Select, (0,esm_extends/* default */.A)({}, props, {
    "aria-describedby": helpId,
    className: "components-select-control__input",
    disabled: disabled,
    id: id,
    multiple: multiple,
    onBlur: handleOnBlur,
    onChange: handleOnChange,
    onFocus: handleOnFocus,
    ref: ref,
    selectSize: size,
    value: valueProp
  }), children || options.map((option, index) => {
    const key = option.id || `${option.label}-${option.value}-${index}`;
    return (0,react.createElement)("option", {
      key: key,
      value: option.value,
      disabled: option.disabled
    }, option.label);
  }))));
  /* eslint-enable jsx-a11y/no-onchange */
}

const ForwardedComponent = (0,react.forwardRef)(SelectControl);
/* harmony default export */ const select_control = (ForwardedComponent);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * WordPress dependencies
 */

/** @typedef {{icon: JSX.Element, size?: number} & import('@wordpress/primitives').SVGProps} IconProps */

/**
 * Return an SVG icon.
 *
 * @param {IconProps} props icon is the SVG component to render
 *                          size is a number specifiying the icon size in pixels
 *                          Other props will be passed to wrapped SVG component
 *
 * @return {JSX.Element}  Icon component
 */

function Icon(_ref) {
  let {
    icon,
    size = 24,
    ...props
  } = _ref;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
    width: size,
    height: size,
    ...props
  });
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Icon);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/library/chevron-down.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@3.45.0/node_modules/@wordpress/primitives/build-module/svg/index.js");


/**
 * WordPress dependencies
 */

const chevronDown = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg"
}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, {
  d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (chevronDown);
//# sourceMappingURL=chevron-down.js.map

/***/ })

}]);