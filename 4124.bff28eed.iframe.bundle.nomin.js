"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4124],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ component_component_default)
});

// UNUSED EXPORTS: Card

// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-system-provider.js + 1 modules
var context_system_provider = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-system-provider.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/elevation/styles.js
function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}

const Elevation =  true ? {
  name: "12ip69d",
  styles: "background:transparent;display:block;margin:0!important;pointer-events:none;position:absolute;will-change:box-shadow"
} : 0;

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js
var values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/elevation/hook.js







function getBoxShadow(value) {
  const boxShadowColor = `rgba(0, 0, 0, ${value / 20})`;
  const boxShadow = `0 ${value}px ${value * 2}px 0
	${boxShadowColor}`;
  return boxShadow;
}
function useElevation(props) {
  const {
    active,
    borderRadius = "inherit",
    className,
    focus,
    hover,
    isInteractive = false,
    offset = 0,
    value = 0,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "Elevation");
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    let hoverValue = (0,values/* isValueDefined */.J5)(hover) ? hover : value * 2;
    let activeValue = (0,values/* isValueDefined */.J5)(active) ? active : value / 2;
    if (!isInteractive) {
      hoverValue = (0,values/* isValueDefined */.J5)(hover) ? hover : void 0;
      activeValue = (0,values/* isValueDefined */.J5)(active) ? active : void 0;
    }
    const transition = `box-shadow ${config_values/* default */.A.transitionDuration} ${config_values/* default */.A.transitionTimingFunction}`;
    const sx = {};
    sx.Base = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
      borderRadius,
      bottom: offset,
      boxShadow: getBoxShadow(value),
      opacity: config_values/* default */.A.elevationIntensity,
      left: offset,
      right: offset,
      top: offset
    }, /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("@media not ( prefers-reduced-motion ){transition:", transition, ";}" + ( true ? "" : 0),  true ? "" : 0),  true ? "" : 0,  true ? "" : 0);
    if ((0,values/* isValueDefined */.J5)(hoverValue)) {
      sx.hover = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("*:hover>&{box-shadow:", getBoxShadow(hoverValue), ";}" + ( true ? "" : 0),  true ? "" : 0);
    }
    if ((0,values/* isValueDefined */.J5)(activeValue)) {
      sx.active = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("*:active>&{box-shadow:", getBoxShadow(activeValue), ";}" + ( true ? "" : 0),  true ? "" : 0);
    }
    if ((0,values/* isValueDefined */.J5)(focus)) {
      sx.focus = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("*:focus>&{box-shadow:", getBoxShadow(focus), ";}" + ( true ? "" : 0),  true ? "" : 0);
    }
    return cx(Elevation, sx.Base, sx.hover, sx.focus, sx.active, className);
  }, [active, borderRadius, className, cx, focus, hover, isInteractive, offset, value]);
  return {
    ...otherProps,
    className: classes,
    "aria-hidden": true
  };
}

//# sourceMappingURL=hook.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/elevation/component.js




function UnconnectedElevation(props, forwardedRef) {
  const elevationProps = useElevation(props);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    ...elevationProps,
    ref: forwardedRef
  });
}
const component_Elevation = (0,context_connect/* contextConnect */.KZ)(UnconnectedElevation, "Elevation");
var component_default = component_Elevation;

//# sourceMappingURL=component.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/styles.js
var styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/styles.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/surface/styles.js


const Surface = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("background-color:", config_values/* default */.A.surfaceColor, ";color:", colors_values/* COLORS */.l.gray[900], ";position:relative;" + ( true ? "" : 0),  true ? "" : 0);
const background = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("background-color:", config_values/* default */.A.surfaceBackgroundColor, ";" + ( true ? "" : 0),  true ? "" : 0);
function getBorders({
  borderBottom,
  borderLeft,
  borderRight,
  borderTop
}) {
  const borderStyle = `1px solid ${config_values/* default */.A.surfaceBorderColor}`;
  return /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
    borderBottom: borderBottom ? borderStyle : void 0,
    borderLeft: borderLeft ? borderStyle : void 0,
    borderRight: borderRight ? borderStyle : void 0,
    borderTop: borderTop ? borderStyle : void 0
  },  true ? "" : 0,  true ? "" : 0);
}
const primary = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)( true ? "" : 0,  true ? "" : 0);
const secondary = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("background:", config_values/* default */.A.surfaceBackgroundTintColor, ";" + ( true ? "" : 0),  true ? "" : 0);
const tertiary = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("background:", config_values/* default */.A.surfaceBackgroundTertiaryColor, ";" + ( true ? "" : 0),  true ? "" : 0);
const customBackgroundSize = (surfaceBackgroundSize) => [surfaceBackgroundSize, surfaceBackgroundSize].join(" ");
const dottedBackground1 = (surfaceBackgroundSizeDotted) => ["90deg", [config_values/* default */.A.surfaceBackgroundColor, surfaceBackgroundSizeDotted].join(" "), "transparent 1%"].join(",");
const dottedBackground2 = (surfaceBackgroundSizeDotted) => [[config_values/* default */.A.surfaceBackgroundColor, surfaceBackgroundSizeDotted].join(" "), "transparent 1%"].join(",");
const dottedBackgroundCombined = (surfaceBackgroundSizeDotted) => [`linear-gradient( ${dottedBackground1(surfaceBackgroundSizeDotted)} ) center`, `linear-gradient( ${dottedBackground2(surfaceBackgroundSizeDotted)} ) center`, config_values/* default */.A.surfaceBorderBoldColor].join(",");
const getDotted = (surfaceBackgroundSize, surfaceBackgroundSizeDotted) => /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("background:", dottedBackgroundCombined(surfaceBackgroundSizeDotted), ";background-size:", customBackgroundSize(surfaceBackgroundSize), ";" + ( true ? "" : 0),  true ? "" : 0);
const gridBackground1 = [`${config_values/* default */.A.surfaceBorderSubtleColor} 1px`, "transparent 1px"].join(",");
const gridBackground2 = ["90deg", `${config_values/* default */.A.surfaceBorderSubtleColor} 1px`, "transparent 1px"].join(",");
const gridBackgroundCombined = [`linear-gradient( ${gridBackground1} )`, `linear-gradient( ${gridBackground2} )`].join(",");
const getGrid = (surfaceBackgroundSize) => {
  return /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("background:", config_values/* default */.A.surfaceBackgroundColor, ";background-image:", gridBackgroundCombined, ";background-size:", customBackgroundSize(surfaceBackgroundSize), ";" + ( true ? "" : 0),  true ? "" : 0);
};
const getVariant = (variant, surfaceBackgroundSize, surfaceBackgroundSizeDotted) => {
  switch (variant) {
    case "dotted": {
      return getDotted(surfaceBackgroundSize, surfaceBackgroundSizeDotted);
    }
    case "grid": {
      return getGrid(surfaceBackgroundSize);
    }
    case "primary": {
      return primary;
    }
    case "secondary": {
      return secondary;
    }
    case "tertiary": {
      return tertiary;
    }
  }
};

//# sourceMappingURL=styles.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/surface/hook.js




function useSurface(props) {
  const {
    backgroundSize = 12,
    borderBottom = false,
    borderLeft = false,
    borderRight = false,
    borderTop = false,
    className,
    variant = "primary",
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "Surface");
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    const sx = {
      borders: getBorders({
        borderBottom,
        borderLeft,
        borderRight,
        borderTop
      })
    };
    return cx(Surface, sx.borders, getVariant(variant, `${backgroundSize}px`, `${backgroundSize - 1}px`), className);
  }, [backgroundSize, borderBottom, borderLeft, borderRight, borderTop, className, cx, variant]);
  return {
    ...otherProps,
    className: classes
  };
}

//# sourceMappingURL=hook.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/hook.js






function useDeprecatedProps({
  elevation,
  isElevated,
  ...otherProps
}) {
  const propsToReturn = {
    ...otherProps
  };
  let computedElevation = elevation;
  if (isElevated) {
    var _computedElevation;
    (0,build_module/* default */.A)("Card isElevated prop", {
      since: "5.9",
      alternative: "elevation"
    });
    (_computedElevation = computedElevation) !== null && _computedElevation !== void 0 ? _computedElevation : computedElevation = 2;
  }
  if (typeof computedElevation !== "undefined") {
    propsToReturn.elevation = computedElevation;
  }
  return propsToReturn;
}
function useCard(props) {
  const {
    className,
    elevation = 0,
    isBorderless = false,
    isRounded = true,
    size = "medium",
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(useDeprecatedProps(props), "Card");
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    return cx(styles/* Card */.Zp, isBorderless && styles/* boxShadowless */.XC, isRounded && styles/* rounded */.Wf, className);
  }, [className, cx, isBorderless, isRounded]);
  const surfaceProps = useSurface({
    ...otherProps,
    className: classes
  });
  return {
    ...surfaceProps,
    elevation,
    isBorderless,
    isRounded,
    size
  };
}

//# sourceMappingURL=hook.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js










function UnconnectedCard(props, forwardedRef) {
  const {
    children,
    elevation,
    isBorderless,
    isRounded,
    size,
    ...otherProps
  } = useCard(props);
  const elevationBorderRadius = isRounded ? config_values/* default */.A.radiusLarge : 0;
  const cx = (0,use_cx/* useCx */.l)();
  const elevationClassName = (0,react.useMemo)(() => cx(/* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
    borderRadius: elevationBorderRadius
  },  true ? "" : 0,  true ? "" : 0)), [cx, elevationBorderRadius]);
  const contextProviderValue = (0,react.useMemo)(() => {
    const contextProps = {
      size,
      isBorderless
    };
    return {
      CardBody: contextProps,
      CardHeader: contextProps,
      CardFooter: contextProps
    };
  }, [isBorderless, size]);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(context_system_provider/* ContextSystemProvider */.c7, {
    value: contextProviderValue,
    children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(component/* default */.A, {
      ...otherProps,
      ref: forwardedRef,
      children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
        className: cx(styles/* Content */.UC),
        children
      }), /* @__PURE__ */ (0,jsx_runtime.jsx)(component_default, {
        className: elevationClassName,
        isInteractive: false,
        value: elevation ? 1 : 0
      }), /* @__PURE__ */ (0,jsx_runtime.jsx)(component_default, {
        className: elevationClassName,
        isInteractive: false,
        value: elevation
      })]
    })
  });
}
const Card = (0,context_connect/* contextConnect */.KZ)(UnconnectedCard, "Card");
var component_component_default = Card;

//# sourceMappingURL=component.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/styles.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Cz: () => (/* binding */ borderColor),
/* harmony export */   Gn: () => (/* binding */ borderless),
/* harmony export */   L7: () => (/* binding */ cardPaddings),
/* harmony export */   QC: () => (/* binding */ shady),
/* harmony export */   UC: () => (/* binding */ Content),
/* harmony export */   Vq: () => (/* binding */ borderRadius),
/* harmony export */   Wf: () => (/* binding */ rounded),
/* harmony export */   XC: () => (/* binding */ boxShadowless),
/* harmony export */   Y9: () => (/* binding */ Header),
/* harmony export */   Zp: () => (/* binding */ Card),
/* harmony export */   nB: () => (/* binding */ Body),
/* harmony export */   wi: () => (/* binding */ Footer)
/* harmony export */ });
/* unused harmony exports Divider, Media */
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");
function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}


const adjustedBorderRadius = `calc(${_utils__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.radiusLarge} - 1px)`;
const Card = /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("box-shadow:0 0 0 1px ", _utils__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.surfaceBorderColor, ";outline:none;" + ( true ? "" : 0),  true ? "" : 0);
const Header =  true ? {
  name: "1showjb",
  styles: "border-bottom:1px solid;box-sizing:border-box;&:last-child{border-bottom:none;}"
} : 0;
const Footer =  true ? {
  name: "14n5oej",
  styles: "border-top:1px solid;box-sizing:border-box;&:first-of-type{border-top:none;}"
} : 0;
const Content =  true ? {
  name: "13udsys",
  styles: "height:100%"
} : 0;
const Body =  true ? {
  name: "6ywzd",
  styles: "box-sizing:border-box;height:auto;max-height:100%"
} : 0;
const Media =  true ? {
  name: "dq805e",
  styles: "box-sizing:border-box;overflow:hidden;&>img,&>iframe{display:block;height:auto;max-width:100%;width:100%;}"
} : 0;
const Divider =  true ? {
  name: "c990dr",
  styles: "box-sizing:border-box;display:block;width:100%"
} : 0;
const borderRadius = /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("&:first-of-type{border-top-left-radius:", adjustedBorderRadius, ";border-top-right-radius:", adjustedBorderRadius, ";}&:last-of-type{border-bottom-left-radius:", adjustedBorderRadius, ";border-bottom-right-radius:", adjustedBorderRadius, ";}" + ( true ? "" : 0),  true ? "" : 0);
const borderColor = /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("border-color:", _utils__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.colorDivider, ";" + ( true ? "" : 0),  true ? "" : 0);
const boxShadowless =  true ? {
  name: "1t90u8d",
  styles: "box-shadow:none"
} : 0;
const borderless =  true ? {
  name: "1e1ncky",
  styles: "border:none"
} : 0;
const rounded = /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("border-radius:", adjustedBorderRadius, ";" + ( true ? "" : 0),  true ? "" : 0);
const xSmallCardPadding = /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("padding:", _utils__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.cardPaddingXSmall, ";" + ( true ? "" : 0),  true ? "" : 0);
const cardPaddings = {
  large: /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("padding:", _utils__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.cardPaddingLarge, ";" + ( true ? "" : 0),  true ? "" : 0),
  medium: /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("padding:", _utils__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.cardPaddingMedium, ";" + ( true ? "" : 0),  true ? "" : 0),
  small: /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("padding:", _utils__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.cardPaddingSmall, ";" + ( true ? "" : 0),  true ? "" : 0),
  xSmall: xSmallCardPadding,
  // The `extraSmall` size is not officially documented, but the following styles
  // are kept for legacy reasons to support older values of the `size` prop.
  extraSmall: xSmallCardPadding
};
const shady = /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)("background-color:", _utils__WEBPACK_IMPORTED_MODULE_2__/* .COLORS */ .l.ui.backgroundDisabled, ";" + ( true ? "" : 0),  true ? "" : 0);

//# sourceMappingURL=styles.js.map


/***/ })

}]);