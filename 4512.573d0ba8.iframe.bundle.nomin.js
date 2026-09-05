"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4512],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spacer/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ component_default)
});

// UNUSED EXPORTS: Spacer

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/rtl.js
var rtl = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/rtl.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spacer/hook.js




function isDefined(o) {
  return typeof o !== "undefined" && o !== null;
}
function useSpacer(props) {
  const {
    className,
    margin,
    marginBottom = 2,
    marginLeft,
    marginRight,
    marginTop,
    marginX,
    marginY,
    padding,
    paddingBottom,
    paddingLeft,
    paddingRight,
    paddingTop,
    paddingX,
    paddingY,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "Spacer");
  const cx = (0,use_cx/* useCx */.l)();
  const classes = cx(isDefined(margin) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("margin:", (0,space/* space */.x)(margin), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(marginY) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("margin-bottom:", (0,space/* space */.x)(marginY), ";margin-top:", (0,space/* space */.x)(marginY), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(marginX) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("margin-left:", (0,space/* space */.x)(marginX), ";margin-right:", (0,space/* space */.x)(marginX), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(marginTop) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("margin-top:", (0,space/* space */.x)(marginTop), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(marginBottom) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("margin-bottom:", (0,space/* space */.x)(marginBottom), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(marginLeft) && (0,rtl/* rtl */.h)({
    marginLeft: (0,space/* space */.x)(marginLeft)
  })(), isDefined(marginRight) && (0,rtl/* rtl */.h)({
    marginRight: (0,space/* space */.x)(marginRight)
  })(), isDefined(padding) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("padding:", (0,space/* space */.x)(padding), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(paddingY) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("padding-bottom:", (0,space/* space */.x)(paddingY), ";padding-top:", (0,space/* space */.x)(paddingY), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(paddingX) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("padding-left:", (0,space/* space */.x)(paddingX), ";padding-right:", (0,space/* space */.x)(paddingX), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(paddingTop) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("padding-top:", (0,space/* space */.x)(paddingTop), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(paddingBottom) && /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("padding-bottom:", (0,space/* space */.x)(paddingBottom), ";" + ( true ? "" : 0),  true ? "" : 0), isDefined(paddingLeft) && (0,rtl/* rtl */.h)({
    paddingLeft: (0,space/* space */.x)(paddingLeft)
  })(), isDefined(paddingRight) && (0,rtl/* rtl */.h)({
    paddingRight: (0,space/* space */.x)(paddingRight)
  })(), className);
  return {
    ...otherProps,
    className: classes
  };
}

//# sourceMappingURL=hook.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spacer/component.js




function UnconnectedSpacer(props, forwardedRef) {
  const spacerProps = useSpacer(props);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    ...spacerProps,
    ref: forwardedRef
  });
}
const Spacer = (0,context_connect/* contextConnect */.KZ)(UnconnectedSpacer, "Spacer");
var component_default = Spacer;

//# sourceMappingURL=component.js.map


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


/***/ })

}]);