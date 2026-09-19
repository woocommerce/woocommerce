"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7947],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/index.js":
/***/ (() => {




































































































































//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ spinner_default)
});

// UNUSED EXPORTS: Spinner, UnforwardedSpinner

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+styled@11.14.1_@em_ee3c5e4b650da353509223cfd78f3fc7/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js
var emotion_styled_base_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+styled@11.14.1_@em_ee3c5e4b650da353509223cfd78f3fc7/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/styles.js

function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}


const spinAnimation = (0,emotion_react_browser_esm/* keyframes */.i7)`
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
 `;
const StyledSpinner = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("svg",  true ? {
  target: "ea4tfvq2"
} : 0)("width:", config_values/* default */.A.spinnerSize, "px;height:", config_values/* default */.A.spinnerSize, "px;display:inline-block;margin:5px 11px 0;position:relative;color:", colors_values/* COLORS */.l.theme.accent, ";overflow:visible;opacity:1;background-color:transparent;" + ( true ? "" : 0));
const commonPathProps =  true ? {
  name: "9s4963",
  styles: "fill:transparent;stroke-width:1.5px"
} : 0;
const SpinnerTrack = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("circle",  true ? {
  target: "ea4tfvq1"
} : 0)(commonPathProps, ";stroke:", colors_values/* COLORS */.l.gray[300], ";" + ( true ? "" : 0));
const SpinnerIndicator = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("path",  true ? {
  target: "ea4tfvq0"
} : 0)(commonPathProps, ";stroke:currentColor;stroke-linecap:round;transform-origin:50% 50%;animation:1.4s linear infinite both ", spinAnimation, ";" + ( true ? "" : 0));

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js




function UnforwardedSpinner({
  className,
  ...props
}, forwardedRef) {
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(StyledSpinner, {
    className: (0,clsx/* default */.A)("components-spinner", className),
    viewBox: "0 0 100 100",
    width: "16",
    height: "16",
    xmlns: "http://www.w3.org/2000/svg",
    role: "presentation",
    focusable: "false",
    ...props,
    ref: forwardedRef,
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(SpinnerTrack, {
      cx: "50",
      cy: "50",
      r: "50",
      vectorEffect: "non-scaling-stroke"
    }), /* @__PURE__ */ (0,jsx_runtime.jsx)(SpinnerIndicator, {
      d: "m 50 0 a 50 50 0 0 1 50 50",
      vectorEffect: "non-scaling-stroke"
    })]
  });
}
const Spinner = (0,react.forwardRef)(UnforwardedSpinner);
var spinner_default = Spinner;

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

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.48.1/node_modules/@wordpress/hooks/build-module/index.mjs");

const logged = /* @__PURE__ */ Object.create(null);
function deprecated(feature, options = {}) {
  const { since, version, alternative, plugin, link, hint } = options;
  const pluginMessage = plugin ? ` from ${plugin}` : "";
  const sinceMessage = since ? ` since version ${since}` : "";
  const versionMessage = version ? ` and will be removed${pluginMessage} in version ${version}` : "";
  const useInsteadMessage = alternative ? ` Please use ${alternative} instead.` : "";
  const linkMessage = link ? ` See: ${link}` : "";
  const hintMessage = hint ? ` Note: ${hint}` : "";
  const message = `${feature} is deprecated${sinceMessage}${versionMessage}.${useInsteadMessage}${linkMessage}${hintMessage}`;
  if (message in logged) {
    return;
  }
  (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)("deprecated", feature, options, message);
  console.warn(message);
  logged[message] = true;
}

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ create_interpolate_element_default)
/* harmony export */ });
/* harmony import */ var _react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

let indoc;
let offset;
let output;
let stack;
const tokenizer = /<(\/)?(\w+)\s*(\/)?>/g;
function createFrame(element, tokenStart, tokenLength, prevOffset, leadingTextStart) {
  return {
    element,
    tokenStart,
    tokenLength,
    prevOffset,
    leadingTextStart,
    children: []
  };
}
const createInterpolateElement = (interpolatedString, conversionMap) => {
  indoc = interpolatedString;
  offset = 0;
  output = [];
  stack = [];
  tokenizer.lastIndex = 0;
  if (!isValidConversionMap(conversionMap)) {
    throw new TypeError(
      "The conversionMap provided is not valid. It must be an object with values that are React Elements"
    );
  }
  do {
  } while (proceed(conversionMap));
  return (0,_react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, ...output);
};
const isValidConversionMap = (conversionMap) => {
  const isObject = typeof conversionMap === "object" && conversionMap !== null;
  const values = isObject && Object.values(conversionMap);
  return isObject && values.length > 0 && values.every((element) => (0,_react__WEBPACK_IMPORTED_MODULE_0__.isValidElement)(element));
};
function proceed(conversionMap) {
  const next = nextToken();
  const [tokenType, name, startOffset, tokenLength] = next;
  const stackDepth = stack.length;
  const leadingTextStart = startOffset > offset ? offset : null;
  if (name && !conversionMap[name]) {
    addText();
    return false;
  }
  switch (tokenType) {
    case "no-more-tokens":
      if (stackDepth !== 0) {
        const { leadingTextStart: stackLeadingText, tokenStart } = stack.pop();
        output.push(indoc.substr(stackLeadingText, tokenStart));
      }
      addText();
      return false;
    case "self-closed":
      if (0 === stackDepth) {
        if (null !== leadingTextStart) {
          output.push(
            indoc.substr(
              leadingTextStart,
              startOffset - leadingTextStart
            )
          );
        }
        output.push(conversionMap[name]);
        offset = startOffset + tokenLength;
        return true;
      }
      addChild(
        createFrame(conversionMap[name], startOffset, tokenLength)
      );
      offset = startOffset + tokenLength;
      return true;
    case "opener":
      stack.push(
        createFrame(
          conversionMap[name],
          startOffset,
          tokenLength,
          startOffset + tokenLength,
          leadingTextStart
        )
      );
      offset = startOffset + tokenLength;
      return true;
    case "closer":
      if (1 === stackDepth) {
        closeOuterElement(startOffset);
        offset = startOffset + tokenLength;
        return true;
      }
      const stackTop = stack.pop();
      const text = indoc.substr(
        stackTop.prevOffset,
        startOffset - stackTop.prevOffset
      );
      stackTop.children.push(text);
      stackTop.prevOffset = startOffset + tokenLength;
      const frame = createFrame(
        stackTop.element,
        stackTop.tokenStart,
        stackTop.tokenLength,
        startOffset + tokenLength
      );
      frame.children = stackTop.children;
      addChild(frame);
      offset = startOffset + tokenLength;
      return true;
    default:
      addText();
      return false;
  }
}
function nextToken() {
  const matches = tokenizer.exec(indoc);
  if (null === matches) {
    return ["no-more-tokens"];
  }
  const startedAt = matches.index;
  const [match, isClosing, name, isSelfClosed] = matches;
  const length = match.length;
  if (isSelfClosed) {
    return ["self-closed", name, startedAt, length];
  }
  if (isClosing) {
    return ["closer", name, startedAt, length];
  }
  return ["opener", name, startedAt, length];
}
function addText() {
  const length = indoc.length - offset;
  if (0 === length) {
    return;
  }
  output.push(indoc.substr(offset, length));
}
function addChild(frame) {
  const { element, tokenStart, tokenLength, prevOffset, children } = frame;
  const parent = stack[stack.length - 1];
  const text = indoc.substr(
    parent.prevOffset,
    tokenStart - parent.prevOffset
  );
  if (text) {
    parent.children.push(text);
  }
  parent.children.push((0,_react__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(element, null, ...children));
  parent.prevOffset = prevOffset ? prevOffset : tokenStart + tokenLength;
}
function closeOuterElement(endOffset) {
  const { element, leadingTextStart, prevOffset, tokenStart, children } = stack.pop();
  const text = endOffset ? indoc.substr(prevOffset, endOffset - prevOffset) : indoc.substr(prevOffset);
  if (text) {
    children.push(text);
  }
  if (null !== leadingTextStart) {
    output.push(
      indoc.substr(leadingTextStart, tokenStart - leadingTextStart)
    );
  }
  output.push((0,_react__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(element, null, ...children));
}
var create_interpolate_element_default = createInterpolateElement;

//# sourceMappingURL=create-interpolate-element.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/add-outline.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-add-outline",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 4c4.411 0 8 3.589 8 8s-3.589 8-8 8-8-3.589-8-8 3.589-8 8-8m0-2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm5 9h-4V7h-2v4H7v2h4v4h2v-4h4v-2z"})))}


/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/cross-small.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-cross-small",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M17.705 7.705l-1.41-1.41L12 10.59 7.705 6.295l-1.41 1.41L10.59 12l-4.295 4.295 1.41 1.41L12 13.41l4.295 4.295 1.41-1.41L13.41 12l4.295-4.295z"})))}


/***/ }),

/***/ "../../node_modules/.pnpm/locutus@3.0.34/node_modules/locutus/esm/php/strings/number_format.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   m: () => (/* binding */ number_format)
/* harmony export */ });
function number_format(number, decimals, decPoint, thousandsSep) {
    //  discuss at: https://locutus.io/php/number_format/
    // original by: Jonas Raoni Soares Silva (https://www.jsfromhell.com)
    // improved by: Kevin van Zonneveld (https://kvz.io)
    // improved by: davook
    // improved by: Brett Zamir (https://brett-zamir.me)
    // improved by: Brett Zamir (https://brett-zamir.me)
    // improved by: Theriault (https://github.com/Theriault)
    // improved by: Kevin van Zonneveld (https://kvz.io)
    // bugfixed by: Michael White (https://getsprink.com)
    // bugfixed by: Benjamin Lupton
    // bugfixed by: Allan Jensen (https://www.winternet.no)
    // bugfixed by: Howard Yeend
    // bugfixed by: Diogo Resende
    // bugfixed by: Rival
    // bugfixed by: Brett Zamir (https://brett-zamir.me)
    //  revised by: Jonas Raoni Soares Silva (https://www.jsfromhell.com)
    //  revised by: Luke Smith (https://lucassmith.name)
    //    input by: Kheang Hok Chin (https://www.distantia.ca/)
    //    input by: Jay Klehr
    //    input by: Amir Habibi (https://www.residence-mixte.com/)
    //    input by: Amirouche
    //   example 1: number_format(1234.56)
    //   returns 1: '1,235'
    //   example 2: number_format(1234.56, 2, ',', ' ')
    //   returns 2: '1 234,56'
    //   example 3: number_format(1234.5678, 2, '.', '')
    //   returns 3: '1234.57'
    //   example 4: number_format(67, 2, ',', '.')
    //   returns 4: '67,00'
    //   example 5: number_format(1000)
    //   returns 5: '1,000'
    //   example 6: number_format(67.311, 2)
    //   returns 6: '67.31'
    //   example 7: number_format(1000.55, 1)
    //   returns 7: '1,000.6'
    //   example 8: number_format(67000, 5, ',', '.')
    //   returns 8: '67.000,00000'
    //   example 9: number_format(0.9, 0)
    //   returns 9: '1'
    //  example 10: number_format('1.20', 2)
    //  returns 10: '1.20'
    //  example 11: number_format('1.20', 4)
    //  returns 11: '1.2000'
    //  example 12: number_format('1.2000', 3)
    //  returns 12: '1.200'
    //  example 13: number_format('1 000,50', 2, '.', ' ')
    //  returns 13: '100 050.00'
    //  example 14: number_format(1e-8, 8, '.', '')
    //  returns 14: '0.00000001'
    const numStr = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    const n = !isFinite(+numStr) ? 0 : +numStr;
    const prec = !isFinite(+(decimals || 0)) ? 0 : Math.abs(decimals || 0);
    const sep = typeof thousandsSep === 'undefined' ? ',' : thousandsSep;
    const dec = typeof decPoint === 'undefined' ? '.' : decPoint;
    let s;
    const toFixedFix = function (n, prec) {
        if (!('' + n).includes('e')) {
            return +(Math.round(+(n + 'e+' + prec)) + 'e-' + prec);
        }
        else {
            const arr = ('' + n).split('e');
            const mantissa = arr[0] ?? '0';
            const exponent = Number(arr[1] ?? 0);
            let sig = '';
            if (exponent + prec > 0) {
                sig = '+';
            }
            return (+(Math.round(+(+mantissa + 'e' + sig + (exponent + prec))) + 'e-' + prec)).toFixed(prec);
        }
    };
    // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec).toString() : '' + Math.round(n)).split('.');
    const whole = s[0] ?? '';
    if (whole.length > 3) {
        s[0] = whole.replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}


/***/ })

}]);