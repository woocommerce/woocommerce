"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5969],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ date_time_date_time_default)
});

// UNUSED EXPORTS: DatePicker, TimePicker

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option-base/styles.js
var toggle_group_control_option_base_styles_namespaceObject = {};
__webpack_require__.r(toggle_group_control_option_base_styles_namespaceObject);
__webpack_require__.d(toggle_group_control_option_base_styles_namespaceObject, {
  Rp: () => (ButtonContentView),
  y0: () => (LabelView),
  uG: () => (buttonView),
  eh: () => (labelBlock)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/index.js + 26 modules
var date = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs
var toDate = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/toDate.mjs");
;// ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/startOfMinute.mjs


/**
 * @name startOfMinute
 * @category Minute Helpers
 * @summary Return the start of a minute for the given date.
 *
 * @description
 * Return the start of a minute for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The original date
 *
 * @returns The start of a minute
 *
 * @example
 * // The start of a minute for 1 December 2014 22:15:45.400:
 * const result = startOfMinute(new Date(2014, 11, 1, 22, 15, 45, 400))
 * //=> Mon Dec 01 2014 22:15:00
 */
function startOfMinute(date) {
  const _date = (0,toDate/* toDate */.a)(date);
  _date.setSeconds(0, 0);
  return _date;
}

// Fallback for modularized imports:
/* harmony default export */ const date_fns_startOfMinute = ((/* unused pure expression or super */ null && (startOfMinute)));

// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/format.mjs + 29 modules
var format = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/format.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/set.mjs
var set = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/set.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setMonth.mjs + 1 modules
var setMonth = __webpack_require__("../../node_modules/.pnpm/date-fns@3.6.0/node_modules/date-fns/setMonth.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js + 1 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js + 4 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+date@5.48.1/node_modules/@wordpress/date/build-module/index.mjs
var date_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+date@5.48.1/node_modules/@wordpress/date/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js + 40 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+styled@11.14.1_@em_ee3c5e4b650da353509223cfd78f3fc7/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js
var emotion_styled_base_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+styled@11.14.1_@em_ee3c5e4b650da353509223cfd78f3fc7/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/styles/input-control-styles.js
var input_control_styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/styles/input-control-styles.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/plus.mjs
// packages/icons/src/library/plus.tsx


var plus_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z" }) });

//# sourceMappingURL=plus.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs
var library_reset = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/index.js + 8 modules
var input_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/number-control/styles/number-control-styles.js

function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}





var _ref =  true ? {
  name: "euqsgg",
  styles: "input[type='number']::-webkit-outer-spin-button,input[type='number']::-webkit-inner-spin-button{-webkit-appearance:none!important;margin:0!important;}input[type='number']{-moz-appearance:textfield;}"
} : 0;
const htmlArrowStyles = ({
  hideHTMLArrows
}) => {
  if (!hideHTMLArrows) {
    return ``;
  }
  return _ref;
};
const Input = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(input_control/* default */.Ay,  true ? {
  target: "ep09it41"
} : 0)(htmlArrowStyles, ";" + ( true ? "" : 0));
const SpinButton = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(build_module_button/* default */.Ay,  true ? {
  target: "ep09it40"
} : 0)("&&&&&{color:", colors_values/* COLORS */.l.theme.accent, ";}" + ( true ? "" : 0));
const smallSpinButtons = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("width:", (0,space/* space */.x)(5), ";min-width:", (0,space/* space */.x)(5), ";height:", (0,space/* space */.x)(5), ";" + ( true ? "" : 0),  true ? "" : 0);
const styles = {
  smallSpinButtons
};

//# sourceMappingURL=number-control-styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/reducer/actions.js
var actions = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/input-control/reducer/actions.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/math.js
function getNumber(value) {
  const number = Number(value);
  return isNaN(number) ? 0 : number;
}
function add(...args) {
  return args.reduce(
    /** @type {(sum:number, arg: number|string) => number} */
    (sum, arg) => sum + getNumber(arg),
    0
  );
}
function subtract(...args) {
  return args.reduce(
    /** @type {(diff:number, arg: number|string, index:number) => number} */
    (diff, arg, index) => {
      const value = getNumber(arg);
      return index === 0 ? value : diff - value;
    },
    0
  );
}
function getPrecision(value) {
  const split = (value + "").split(".");
  return split[1] !== void 0 ? split[1].length : 0;
}
function clamp(value, min, max) {
  const baseValue = getNumber(value);
  return Math.max(min, Math.min(baseValue, max));
}
function ensureValidStep(value, min, step) {
  const baseValue = getNumber(value);
  const minValue = getNumber(min);
  const stepValue = getNumber(step);
  const precision = Math.max(getPrecision(step), getPrecision(min));
  const tare = minValue % stepValue ? minValue : 0;
  const rounded = Math.round((baseValue - tare) / stepValue) * stepValue;
  const fromMin = rounded + tare;
  return precision ? getNumber(fromMin.toFixed(precision)) : fromMin;
}

//# sourceMappingURL=math.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js
var values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js
var h_stack_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spacer/component.js + 1 modules
var spacer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spacer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/use-deprecated-props.js
var use_deprecated_props = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/use-deprecated-props.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js
var deprecated_36px_size = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/number-control/index.js
















const noop = () => {
};
function UnforwardedNumberControl(props, forwardedRef) {
  const {
    __unstableStateReducer: stateReducerProp,
    className,
    dragDirection = "n",
    hideHTMLArrows = false,
    spinControls = hideHTMLArrows ? "none" : "native",
    isDragEnabled = true,
    isShiftStepEnabled = true,
    label,
    max = Infinity,
    min = -Infinity,
    required = false,
    shiftStep = 10,
    step = 1,
    spinFactor = 1,
    type: typeProp = "number",
    value: valueProp,
    size = "default",
    suffix,
    onChange = noop,
    __shouldNotWarnDeprecated36pxSize,
    ...restProps
  } = (0,use_deprecated_props/* useDeprecated36pxDefaultSizeProp */.R)(props);
  (0,deprecated_36px_size/* maybeWarnDeprecated36pxSize */.M)({
    componentName: "NumberControl",
    size,
    __next40pxDefaultSize: restProps.__next40pxDefaultSize,
    __shouldNotWarnDeprecated36pxSize
  });
  if (hideHTMLArrows) {
    (0,deprecated_build_module/* default */.A)("wp.components.NumberControl hideHTMLArrows prop ", {
      alternative: 'spinControls="none"',
      since: "6.2",
      version: "6.3"
    });
  }
  const inputRef = (0,react.useRef)();
  const mergedRef = (0,use_merge_refs/* default */.A)([inputRef, forwardedRef]);
  const isStepAny = step === "any";
  const baseStep = isStepAny ? 1 : (0,values/* ensureNumber */.GB)(step);
  const baseSpin = (0,values/* ensureNumber */.GB)(spinFactor) * baseStep;
  const constrainValue = (value, stepOverride) => {
    if (!isStepAny) {
      value = ensureValidStep(value, min, stepOverride !== null && stepOverride !== void 0 ? stepOverride : baseStep);
    }
    return `${clamp(value, min, max)}`;
  };
  const baseValue = constrainValue(0);
  const autoComplete = typeProp === "number" ? "off" : void 0;
  const classes = (0,clsx/* default */.A)("components-number-control", className);
  const cx = (0,use_cx/* useCx */.l)();
  const spinButtonClasses = cx(size === "small" && styles.smallSpinButtons);
  const spinValue = (value, direction, event) => {
    event?.preventDefault();
    const shift = event?.shiftKey && isShiftStepEnabled;
    const delta = shift ? (0,values/* ensureNumber */.GB)(shiftStep) * baseSpin : baseSpin;
    let nextValue = (0,values/* isValueEmpty */.r6)(value) ? baseValue : value;
    if (direction === "up") {
      nextValue = add(nextValue, delta);
    } else if (direction === "down") {
      nextValue = subtract(nextValue, delta);
    }
    return constrainValue(nextValue, shift ? delta : void 0);
  };
  const numberControlStateReducer = (state, action) => {
    const nextState = {
      ...state
    };
    const {
      type,
      payload
    } = action;
    const event = payload.event;
    const currentValue = nextState.value;
    if (type === actions/* PRESS_UP */.wX || type === actions/* PRESS_DOWN */.r7) {
      nextState.value = spinValue(currentValue, type === actions/* PRESS_UP */.wX ? "up" : "down", event);
    }
    if (type === actions/* DRAG */.j && isDragEnabled) {
      const [x, y] = payload.delta;
      const enableShift = payload.shiftKey && isShiftStepEnabled;
      const modifier = enableShift ? (0,values/* ensureNumber */.GB)(shiftStep) * baseSpin : baseSpin;
      let directionModifier;
      let delta;
      switch (dragDirection) {
        case "n":
          delta = y;
          directionModifier = -1;
          break;
        case "e":
          delta = x;
          directionModifier = (0,build_module/* isRTL */.V8)() ? -1 : 1;
          break;
        case "s":
          delta = y;
          directionModifier = 1;
          break;
        case "w":
          delta = x;
          directionModifier = (0,build_module/* isRTL */.V8)() ? 1 : -1;
          break;
      }
      if (delta !== 0) {
        delta = Math.ceil(Math.abs(delta)) * Math.sign(delta);
        const distance = delta * modifier * directionModifier;
        nextState.value = constrainValue(
          // @ts-expect-error TODO: Investigate if it's ok for currentValue to be undefined
          add(currentValue, distance),
          enableShift ? modifier : void 0
        );
      }
    }
    if (type === actions/* PRESS_ENTER */.bR || type === actions/* COMMIT */.cJ) {
      const applyEmptyValue = required === false && currentValue === "";
      nextState.value = applyEmptyValue ? currentValue : (
        // @ts-expect-error TODO: Investigate if it's ok for currentValue to be undefined
        constrainValue(currentValue)
      );
    }
    return nextState;
  };
  const buildSpinButtonClickHandler = (direction) => (event) => onChange(String(spinValue(valueProp, direction, event)), {
    // Set event.target to the <input> so that consumers can use
    // e.g. event.target.validity.
    event: {
      ...event,
      target: inputRef.current
    }
  });
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(Input, {
    autoComplete,
    inputMode: "numeric",
    ...restProps,
    className: classes,
    dragDirection,
    hideHTMLArrows: spinControls !== "native",
    isDragEnabled,
    label,
    max: max === Infinity ? void 0 : max,
    min: min === -Infinity ? void 0 : min,
    ref: mergedRef,
    required,
    step,
    type: typeProp,
    value: valueProp,
    __unstableStateReducer: (state, action) => {
      var _stateReducerProp;
      const baseState = numberControlStateReducer(state, action);
      return (_stateReducerProp = stateReducerProp?.(baseState, action)) !== null && _stateReducerProp !== void 0 ? _stateReducerProp : baseState;
    },
    size,
    __shouldNotWarnDeprecated36pxSize: true,
    suffix: spinControls === "custom" ? /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [suffix, /* @__PURE__ */ (0,jsx_runtime.jsx)(spacer_component/* default */.A, {
        marginBottom: 0,
        marginRight: 2,
        children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(h_stack_component/* default */.A, {
          spacing: 1,
          children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(SpinButton, {
            className: spinButtonClasses,
            icon: plus_default,
            size: "small",
            label: (0,build_module.__)("Increment"),
            onClick: buildSpinButtonClickHandler("up")
          }), /* @__PURE__ */ (0,jsx_runtime.jsx)(SpinButton, {
            className: spinButtonClasses,
            icon: library_reset/* default */.A,
            size: "small",
            label: (0,build_module.__)("Decrement"),
            onClick: buildSpinButtonClickHandler("down")
          })]
        })
      })]
    }) : suffix,
    onChange
  });
}
const NumberControl = (0,react.forwardRef)(UnforwardedNumberControl);
var number_control_default = NumberControl;

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/time/styles.js

function styles_EMOTION_STRINGIFIED_CSS_ERROR_() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}





const Wrapper = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "evcr2319"
} : 0)("box-sizing:border-box;font-size:", config_values/* default */.A.fontSize, ";" + ( true ? "" : 0));
const Fieldset = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("fieldset",  true ? {
  target: "evcr2318"
} : 0)("border:0;margin:0 0 ", (0,space/* space */.x)(2 * 2), " 0;padding:0;&:last-child{margin-bottom:0;}" + ( true ? "" : 0));
const TimeWrapper = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "evcr2317"
} : 0)( true ? {
  name: "pd0mhc",
  styles: "direction:ltr;display:flex"
} : 0);
const baseInput = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("&&& ", input_control_styles/* Input */.pd, "{padding-left:", (0,space/* space */.x)(2), ";padding-right:", (0,space/* space */.x)(2), ";text-align:center;}" + ( true ? "" : 0),  true ? "" : 0);
const HoursInput = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(number_control_default,  true ? {
  target: "evcr2316"
} : 0)(baseInput, " width:", (0,space/* space */.x)(9), ";&&& ", input_control_styles/* Input */.pd, "{padding-right:0;}&&& ", input_control_styles/* BackdropUI */.Hr, "{border-right:0;border-top-right-radius:0;border-bottom-right-radius:0;}" + ( true ? "" : 0));
const TimeSeparator = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "evcr2315"
} : 0)("border-top:", config_values/* default */.A.borderWidth, " solid ", colors_values/* COLORS */.l.gray[700], ";border-bottom:", config_values/* default */.A.borderWidth, " solid ", colors_values/* COLORS */.l.gray[700], ";font-size:", config_values/* default */.A.fontSize, ";line-height:calc(\n		", config_values/* default */.A.controlHeight, " - ", config_values/* default */.A.borderWidth, " * 2\n	);display:inline-block;" + ( true ? "" : 0));
const MinutesInput = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(number_control_default,  true ? {
  target: "evcr2314"
} : 0)(baseInput, " width:", (0,space/* space */.x)(9), ";&&& ", input_control_styles/* Input */.pd, "{padding-left:0;}&&& ", input_control_styles/* BackdropUI */.Hr, "{border-left:0;border-top-left-radius:0;border-bottom-left-radius:0;}" + ( true ? "" : 0));
const MonthSelectWrapper = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "evcr2313"
} : 0)( true ? {
  name: "1ff36h2",
  styles: "flex-grow:1"
} : 0);
const DayInput = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(number_control_default,  true ? {
  target: "evcr2312"
} : 0)(baseInput, " width:", (0,space/* space */.x)(9), ";" + ( true ? "" : 0));
const YearInput = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(number_control_default,  true ? {
  target: "evcr2311"
} : 0)(baseInput, " width:", (0,space/* space */.x)(14), ";" + ( true ? "" : 0));
const TimeZone = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "evcr2310"
} : 0)( true ? {
  name: "ebu3jh",
  styles: "text-decoration:underline dotted"
} : 0);

//# sourceMappingURL=styles.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/time/timezone.js





const timezone_TimeZone = () => {
  const {
    timezone
  } = (0,date_build_module/* getSettings */.mt)();
  const userTimezoneOffset = -1 * ((/* @__PURE__ */ new Date()).getTimezoneOffset() / 60);
  if (Number(timezone.offset) === userTimezoneOffset) {
    return null;
  }
  const offsetSymbol = Number(timezone.offset) >= 0 ? "+" : "";
  const zoneAbbr = "" !== timezone.abbr && isNaN(Number(timezone.abbr)) ? timezone.abbr : `UTC${offsetSymbol}${timezone.offsetFormatted}`;
  const prettyTimezoneString = timezone.string.replace("_", " ");
  const timezoneDetail = "UTC" === timezone.string ? (0,build_module.__)("Coordinated Universal Time") : `(${zoneAbbr}) ${prettyTimezoneString}`;
  const hasNoAdditionalTimezoneDetail = prettyTimezoneString.trim().length === 0;
  return hasNoAdditionalTimezoneDetail ? /* @__PURE__ */ (0,jsx_runtime.jsx)(TimeZone, {
    className: "components-datetime__timezone",
    children: zoneAbbr
  }) : /* @__PURE__ */ (0,jsx_runtime.jsx)(tooltip/* default */.Ay, {
    placement: "top",
    text: timezoneDetail,
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)(TimeZone, {
      className: "components-datetime__timezone",
      children: zoneAbbr
    })
  });
};
var timezone_default = timezone_TimeZone;

//# sourceMappingURL=timezone.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/constants.js
var constants = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/constants.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js
var use_controlled_value = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control/styles.js

function toggle_group_control_styles_EMOTION_STRINGIFIED_CSS_ERROR_() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}


const toggleGroupControl = ({
  isBlock,
  isDeselectable,
  size
}) => /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("background:", colors_values/* COLORS */.l.ui.background, ";border:1px solid transparent;border-radius:", config_values/* default */.A.radiusSmall, ";display:inline-flex;min-width:0;position:relative;", toggleGroupControlSize(size), " ", !isDeselectable && enclosingBorders(isBlock), "@media not ( prefers-reduced-motion ){&[data-indicator-animated]::before{transition-property:transform,border-radius;transition-duration:0.2s;transition-timing-function:ease-out;}}&::before{content:'';position:absolute;pointer-events:none;background:", colors_values/* COLORS */.l.theme.foreground, ";outline:2px solid transparent;outline-offset:-3px;--antialiasing-factor:100;border-radius:calc(\n				", config_values/* default */.A.radiusXSmall, " /\n					(\n						var( --selected-width, 0 ) /\n							var( --antialiasing-factor )\n					)\n			)/", config_values/* default */.A.radiusXSmall, ";left:-1px;width:calc( var( --antialiasing-factor ) * 1px );height:calc( var( --selected-height, 0 ) * 1px );transform-origin:left top;transform:translateX( calc( var( --selected-left, 0 ) * 1px ) ) scaleX(\n				calc(\n					var( --selected-width, 0 ) / var( --antialiasing-factor )\n				)\n			);}" + ( true ? "" : 0),  true ? "" : 0);
const enclosingBorders = (isBlock) => {
  const enclosingBorder = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("border-color:", colors_values/* COLORS */.l.ui.border, ";" + ( true ? "" : 0),  true ? "" : 0);
  return /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)(isBlock && enclosingBorder, " &:hover{border-color:", colors_values/* COLORS */.l.ui.borderHover, ";}&:focus-within{border-color:", colors_values/* COLORS */.l.ui.borderFocus, ";box-shadow:", config_values/* default */.A.controlBoxShadowFocus, ";z-index:1;outline:2px solid transparent;outline-offset:-2px;}" + ( true ? "" : 0),  true ? "" : 0);
};
var styles_ref =  true ? {
  name: "1aqh2c7",
  styles: "min-height:40px;padding:3px"
} : 0;
var _ref2 =  true ? {
  name: "1ndywgm",
  styles: "min-height:36px;padding:2px"
} : 0;
const toggleGroupControlSize = (size) => {
  const styles = {
    default: _ref2,
    "__unstable-large": styles_ref
  };
  return styles[size];
};
const block =  true ? {
  name: "7whenc",
  styles: "display:flex;width:100%"
} : 0;
const VisualLabelWrapper = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "eakva830"
} : 0)( true ? {
  name: "zjik7",
  styles: "display:flex"
} : 0);

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js + 1 modules
var ZJG6VNPS = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js
var YAS7X7HB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js
var UNDE2QJS = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js
var XTZ53NXG = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js
var UWJK2WK2 = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/radio/radio-store.js
"use client";







// src/radio/radio-store.ts
function createRadioStore({
  ...props
} = {}) {
  var _a;
  const syncState = (_a = props.store) == null ? void 0 : _a.getState();
  const composite = (0,UNDE2QJS/* createCompositeStore */.z)({
    ...props,
    focusLoop: (0,UWJK2WK2/* defaultValue */.Jh)(props.focusLoop, syncState == null ? void 0 : syncState.focusLoop, true)
  });
  const initialState = {
    ...composite.getState(),
    value: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.value,
      syncState == null ? void 0 : syncState.value,
      props.defaultValue,
      null
    )
  };
  const radio = (0,XTZ53NXG/* createStore */.y$)(initialState, composite, props.store);
  return {
    ...composite,
    ...radio,
    setValue: (value) => radio.setState("value", value)
  };
}


;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/2GY5TTXI.js
"use client";



// src/radio/radio-store.ts

function useRadioStoreProps(store, update, props) {
  store = (0,ZJG6VNPS/* useCompositeStoreProps */.YO)(store, update, props);
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "value", "setValue");
  return store;
}
function useRadioStore(props = {}) {
  const [store, update] = (0,YAS7X7HB/* useStore */.Pj)(createRadioStore, props);
  return useRadioStoreProps(store, update, props);
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js
var _55FNNNML = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js
var TVXRYIJB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YVQZ63F5.js
"use client";



// src/radio/radio-context.tsx
var ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [_55FNNNML/* CompositeContextProvider */.ws],
  [_55FNNNML/* CompositeScopedContextProvider */.aN]
);
var useRadioContext = ctx.useContext;
var useRadioScopedContext = ctx.useScopedContext;
var useRadioProviderContext = ctx.useProviderContext;
var RadioContextProvider = ctx.ContextProvider;
var RadioScopedContextProvider = ctx.ScopedContextProvider;



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js
var _6PX47O7P = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js
var CEM7J6TT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/radio/radio-group.js
"use client";












// src/radio/radio-group.tsx


var TagName = "div";
var useRadioGroup = (0,TVXRYIJB/* createHook */.ab)(
  function useRadioGroup2({ store, ...props }) {
    const context = useRadioProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(RadioScopedContextProvider, { value: store, children: element }),
      [store]
    );
    props = {
      role: "radiogroup",
      ...props
    };
    props = (0,_6PX47O7P/* useComposite */.T)({ store, ...props });
    return props;
  }
);
var RadioGroup = (0,TVXRYIJB/* forwardRef */.Rf)(function RadioGroup2(props) {
  const htmlProps = useRadioGroup(props);
  return (0,TVXRYIJB/* createElement */.n)(TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var view_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/context.js

const ToggleGroupControlContext = (0,react.createContext)({});
ToggleGroupControlContext.displayName = "ToggleGroupControlContext";
const useToggleGroupControlContext = () => (0,react.useContext)(ToggleGroupControlContext);
var context_default = ToggleGroupControlContext;

//# sourceMappingURL=context.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs
var use_previous = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control/utils.js


function useComputeControlledOrUncontrolledValue(valueProp) {
  const isInitialRenderRef = (0,react.useRef)(true);
  const prevValueProp = (0,use_previous/* default */.A)(valueProp);
  const prevIsControlledRef = (0,react.useRef)(false);
  (0,react.useEffect)(() => {
    if (isInitialRenderRef.current) {
      isInitialRenderRef.current = false;
    }
  }, []);
  const isControlled = prevIsControlledRef.current || !isInitialRenderRef.current && prevValueProp !== valueProp;
  (0,react.useEffect)(() => {
    prevIsControlledRef.current = isControlled;
  }, [isControlled]);
  if (isControlled) {
    return {
      value: valueProp !== null && valueProp !== void 0 ? valueProp : "",
      defaultValue: void 0
    };
  }
  return {
    value: void 0,
    defaultValue: valueProp
  };
}

//# sourceMappingURL=utils.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control/as-radio-group.js








function UnforwardedToggleGroupControlAsRadioGroup({
  children,
  isAdaptiveWidth,
  label,
  onChange: onChangeProp,
  size,
  value: valueProp,
  id: idProp,
  setSelectedElement,
  ...otherProps
}, forwardedRef) {
  const generatedId = (0,use_instance_id/* default */.A)(ToggleGroupControlAsRadioGroup, "toggle-group-control-as-radio-group");
  const baseId = idProp || generatedId;
  const {
    value,
    defaultValue
  } = useComputeControlledOrUncontrolledValue(valueProp);
  const wrappedOnChangeProp = onChangeProp ? (v) => {
    onChangeProp(v !== null && v !== void 0 ? v : void 0);
  } : void 0;
  const radio = useRadioStore({
    defaultValue,
    value,
    setValue: wrappedOnChangeProp,
    rtl: (0,build_module/* isRTL */.V8)()
  });
  const selectedValue = YAS7X7HB/* useStoreState */.O$(radio, "value");
  const setValue = radio.setValue;
  (0,react.useEffect)(() => {
    if (selectedValue === "") {
      radio.setActiveId(void 0);
    }
  }, [radio, selectedValue]);
  const groupContextValue = (0,react.useMemo)(() => ({
    activeItemIsNotFirstItem: () => radio.getState().activeId !== radio.first(),
    baseId,
    isBlock: !isAdaptiveWidth,
    size,
    // @ts-expect-error - This is wrong and we should fix it.
    value: selectedValue,
    // @ts-expect-error - This is wrong and we should fix it.
    setValue,
    setSelectedElement
  }), [baseId, isAdaptiveWidth, radio, selectedValue, setSelectedElement, setValue, size]);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(context_default.Provider, {
    value: groupContextValue,
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)(RadioGroup, {
      store: radio,
      "aria-label": label,
      render: /* @__PURE__ */ (0,jsx_runtime.jsx)(view_component/* default */.A, {}),
      ...otherProps,
      id: baseId,
      ref: forwardedRef,
      children
    })
  });
}
const ToggleGroupControlAsRadioGroup = (0,react.forwardRef)(UnforwardedToggleGroupControlAsRadioGroup);

//# sourceMappingURL=as-radio-group.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control/as-button-group.js







function UnforwardedToggleGroupControlAsButtonGroup({
  children,
  isAdaptiveWidth,
  label,
  onChange,
  size,
  value: valueProp,
  id: idProp,
  setSelectedElement,
  ...otherProps
}, forwardedRef) {
  const generatedId = (0,use_instance_id/* default */.A)(ToggleGroupControlAsButtonGroup, "toggle-group-control-as-button-group");
  const baseId = idProp || generatedId;
  const {
    value,
    defaultValue
  } = useComputeControlledOrUncontrolledValue(valueProp);
  const [selectedValue, setSelectedValue] = (0,use_controlled_value/* useControlledValue */.j)({
    defaultValue,
    value,
    onChange
  });
  const groupContextValue = (0,react.useMemo)(() => ({
    baseId,
    value: selectedValue,
    setValue: setSelectedValue,
    isBlock: !isAdaptiveWidth,
    isDeselectable: true,
    size,
    setSelectedElement
  }), [baseId, selectedValue, setSelectedValue, isAdaptiveWidth, size, setSelectedElement]);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(context_default.Provider, {
    value: groupContextValue,
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)(view_component/* default */.A, {
      "aria-label": label,
      ...otherProps,
      ref: forwardedRef,
      role: "group",
      children
    })
  });
}
const ToggleGroupControlAsButtonGroup = (0,react.forwardRef)(UnforwardedToggleGroupControlAsButtonGroup);

//# sourceMappingURL=as-button-group.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-event/index.mjs
var use_event = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-event/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-resize-observer/use-resize-observer.mjs
// packages/compose/src/hooks/use-resize-observer/use-resize-observer.ts


function useResizeObserver(callback, resizeObserverOptions = {}) {
  const callbackEvent = (0,use_event/* default */.A)(callback);
  const observedElementRef = (0,react.useRef)(null);
  const resizeObserverRef = (0,react.useRef)(void 0);
  return (0,use_event/* default */.A)((element) => {
    if (element === observedElementRef.current) {
      return;
    }
    resizeObserverRef.current ??= new ResizeObserver(callbackEvent);
    const { current: resizeObserver } = resizeObserverRef;
    if (observedElementRef.current) {
      resizeObserver.unobserve(observedElementRef.current);
    }
    observedElementRef.current = element ?? null;
    if (element) {
      resizeObserver.observe(element, resizeObserverOptions);
    }
  });
}

//# sourceMappingURL=use-resize-observer.mjs.map

;// ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-resize-observer/legacy/index.mjs
// packages/compose/src/hooks/use-resize-observer/legacy/index.tsx



var extractSize = (entry) => {
  let entrySize;
  if (!entry.contentBoxSize) {
    entrySize = [entry.contentRect.width, entry.contentRect.height];
  } else if (entry.contentBoxSize[0]) {
    const contentBoxSize = entry.contentBoxSize[0];
    entrySize = [contentBoxSize.inlineSize, contentBoxSize.blockSize];
  } else {
    const contentBoxSize = entry.contentBoxSize;
    entrySize = [contentBoxSize.inlineSize, contentBoxSize.blockSize];
  }
  const [width, height] = entrySize.map((d) => Math.round(d));
  return { width, height };
};
var RESIZE_ELEMENT_STYLES = {
  position: "absolute",
  top: 0,
  left: 0,
  right: 0,
  bottom: 0,
  pointerEvents: "none",
  opacity: 0,
  overflow: "hidden",
  zIndex: -1
};
function ResizeElement({ onResize }) {
  const resizeElementRef = useResizeObserver((entries) => {
    const newSize = extractSize(entries.at(-1));
    onResize(newSize);
  });
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(
    "div",
    {
      ref: resizeElementRef,
      style: RESIZE_ELEMENT_STYLES,
      "aria-hidden": "true"
    }
  );
}
function sizeEquals(a, b) {
  return a.width === b.width && a.height === b.height;
}
var NULL_SIZE = { width: null, height: null };
function useLegacyResizeObserver() {
  const [size, setSize] = (0,react.useState)(NULL_SIZE);
  const previousSizeRef = (0,react.useRef)(NULL_SIZE);
  const handleResize = (0,react.useCallback)((newSize) => {
    if (!sizeEquals(previousSizeRef.current, newSize)) {
      previousSizeRef.current = newSize;
      setSize(newSize);
    }
  }, []);
  const resizeElement = /* @__PURE__ */ (0,jsx_runtime.jsx)(ResizeElement, { onResize: handleResize });
  return [resizeElement, size];
}

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-resize-observer/index.mjs
// packages/compose/src/hooks/use-resize-observer/index.ts


function use_resize_observer_useResizeObserver(callback, options = {}) {
  return callback ? useResizeObserver(callback, options) : useLegacyResizeObserver();
}

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/element-rect.js


const NULL_ELEMENT_OFFSET_RECT = {
  element: void 0,
  top: 0,
  right: 0,
  bottom: 0,
  left: 0,
  width: 0,
  height: 0
};
function getElementOffsetRect(element) {
  var _offsetParent$getBoun, _offsetParent$scrollL, _offsetParent$scrollT;
  const rect = element.getBoundingClientRect();
  if (rect.width === 0 || rect.height === 0) {
    return;
  }
  const offsetParent = element.offsetParent;
  const offsetParentRect = (_offsetParent$getBoun = offsetParent?.getBoundingClientRect()) !== null && _offsetParent$getBoun !== void 0 ? _offsetParent$getBoun : NULL_ELEMENT_OFFSET_RECT;
  const offsetParentScrollX = (_offsetParent$scrollL = offsetParent?.scrollLeft) !== null && _offsetParent$scrollL !== void 0 ? _offsetParent$scrollL : 0;
  const offsetParentScrollY = (_offsetParent$scrollT = offsetParent?.scrollTop) !== null && _offsetParent$scrollT !== void 0 ? _offsetParent$scrollT : 0;
  const computedWidth = parseFloat(getComputedStyle(element).width);
  const computedHeight = parseFloat(getComputedStyle(element).height);
  const scaleX = computedWidth / rect.width;
  const scaleY = computedHeight / rect.height;
  return {
    element,
    // To obtain the adjusted values for the position:
    // 1. Compute the element's position relative to the offset parent.
    // 2. Correct for the scale factor.
    // 3. Adjust for the scroll position of the offset parent.
    top: (rect.top - offsetParentRect?.top) * scaleY + offsetParentScrollY,
    right: (offsetParentRect?.right - rect.right) * scaleX - offsetParentScrollX,
    bottom: (offsetParentRect?.bottom - rect.bottom) * scaleY - offsetParentScrollY,
    left: (rect.left - offsetParentRect?.left) * scaleX + offsetParentScrollX,
    // Computed dimensions don't need any adjustments.
    width: computedWidth,
    height: computedHeight
  };
}
const POLL_RATE = 100;
function useTrackElementOffsetRect(targetElement, deps = []) {
  const [indicatorPosition, setIndicatorPosition] = (0,react.useState)(NULL_ELEMENT_OFFSET_RECT);
  const intervalRef = (0,react.useRef)();
  const measure = (0,use_event/* default */.A)(() => {
    if (targetElement && targetElement.isConnected) {
      const elementOffsetRect = getElementOffsetRect(targetElement);
      if (elementOffsetRect) {
        setIndicatorPosition(elementOffsetRect);
        clearInterval(intervalRef.current);
        return true;
      }
    } else {
      clearInterval(intervalRef.current);
    }
    return false;
  });
  const setElement = use_resize_observer_useResizeObserver(() => {
    if (!measure()) {
      requestAnimationFrame(() => {
        if (!measure()) {
          intervalRef.current = setInterval(measure, POLL_RATE);
        }
      });
    }
  });
  (0,react.useLayoutEffect)(() => {
    setElement(targetElement);
    if (!targetElement) {
      setIndicatorPosition(NULL_ELEMENT_OFFSET_RECT);
    }
  }, [setElement, targetElement]);
  (0,react.useLayoutEffect)(() => {
    measure();
  }, deps);
  return indicatorPosition;
}

//# sourceMappingURL=element-rect.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-on-value-update.js


function useOnValueUpdate(value, onUpdate) {
  const previousValueRef = (0,react.useRef)(value);
  const updateCallbackEvent = (0,use_event/* default */.A)(onUpdate);
  (0,react.useLayoutEffect)(() => {
    if (previousValueRef.current !== value) {
      updateCallbackEvent({
        previousValue: previousValueRef.current
      });
      previousValueRef.current = value;
    }
  }, [updateCallbackEvent, value]);
}

//# sourceMappingURL=use-on-value-update.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-animated-offset-rect.js



function useAnimatedOffsetRect(container, rect, {
  prefix = "subelement",
  dataAttribute = `${prefix}-animated`,
  transitionEndFilter = () => true,
  roundRect = false
} = {}) {
  const setProperties = (0,use_event/* default */.A)(() => {
    Object.keys(rect).forEach((property) => property !== "element" && container?.style.setProperty(`--${prefix}-${property}`, String(roundRect ? Math.floor(rect[property]) : rect[property])));
  });
  (0,react.useLayoutEffect)(() => {
    setProperties();
  }, [rect, setProperties]);
  useOnValueUpdate(rect.element, ({
    previousValue
  }) => {
    if (rect.element && previousValue) {
      container?.setAttribute(`data-${dataAttribute}`, "");
    }
  });
  (0,react.useLayoutEffect)(() => {
    function onTransitionEnd(event) {
      if (transitionEndFilter(event)) {
        container?.removeAttribute(`data-${dataAttribute}`);
      }
    }
    container?.addEventListener("transitionend", onTransitionEnd);
    return () => container?.removeEventListener("transitionend", onTransitionEnd);
  }, [dataAttribute, container, transitionEndFilter]);
}

//# sourceMappingURL=use-animated-offset-rect.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control/component.js













function UnconnectedToggleGroupControl(props, forwardedRef) {
  const {
    __nextHasNoMarginBottom = false,
    __next40pxDefaultSize = false,
    __shouldNotWarnDeprecated36pxSize,
    className,
    isAdaptiveWidth = false,
    isBlock = false,
    isDeselectable = false,
    label,
    hideLabelFromVision = false,
    help,
    onChange,
    size = "default",
    value,
    children,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "ToggleGroupControl");
  const normalizedSize = __next40pxDefaultSize && size === "default" ? "__unstable-large" : size;
  const [selectedElement, setSelectedElement] = (0,react.useState)();
  const [controlElement, setControlElement] = (0,react.useState)();
  const refs = (0,use_merge_refs/* default */.A)([setControlElement, forwardedRef]);
  const selectedRect = useTrackElementOffsetRect(value !== null && value !== void 0 ? selectedElement : void 0);
  useAnimatedOffsetRect(controlElement, selectedRect, {
    prefix: "selected",
    dataAttribute: "indicator-animated",
    transitionEndFilter: (event) => event.pseudoElement === "::before",
    roundRect: true
  });
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => cx(toggleGroupControl({
    isBlock,
    isDeselectable,
    size: normalizedSize
  }), isBlock && block, className), [className, cx, isBlock, isDeselectable, normalizedSize]);
  const MainControl = isDeselectable ? ToggleGroupControlAsButtonGroup : ToggleGroupControlAsRadioGroup;
  (0,deprecated_36px_size/* maybeWarnDeprecated36pxSize */.M)({
    componentName: "ToggleGroupControl",
    size,
    __next40pxDefaultSize,
    __shouldNotWarnDeprecated36pxSize
  });
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(base_control/* default */.Ay, {
    help,
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "ToggleGroupControl",
    children: [!hideLabelFromVision && /* @__PURE__ */ (0,jsx_runtime.jsx)(VisualLabelWrapper, {
      children: /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default.VisualLabel */.Ay.VisualLabel, {
        children: label
      })
    }), /* @__PURE__ */ (0,jsx_runtime.jsx)(MainControl, {
      ...otherProps,
      setSelectedElement,
      className: classes,
      isAdaptiveWidth,
      label,
      onChange,
      ref: refs,
      size: normalizedSize,
      value,
      children
    })]
  });
}
const ToggleGroupControl = (0,context_connect/* contextConnect */.KZ)(UnconnectedToggleGroupControl, "ToggleGroupControl");
var component_default = ToggleGroupControl;

//# sourceMappingURL=component.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js + 1 modules
var ZCYMVQGT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/AZ7UIBND.js
"use client";






// src/radio/radio.tsx


var AZ7UIBND_TagName = "input";
function getIsChecked(value, storeValue) {
  if (storeValue === void 0) return;
  if (value != null && storeValue != null) {
    return storeValue === value;
  }
  return !!storeValue;
}
function isNativeRadio(tagName, type) {
  return tagName === "input" && (!type || type === "radio");
}
var useRadio = (0,TVXRYIJB/* createHook */.ab)(function useRadio2({
  store,
  name,
  value,
  checked,
  ...props
}) {
  const context = useRadioContext();
  store = store || context;
  const id = (0,CEM7J6TT/* useId */.Bi)(props.id);
  const ref = (0,react.useRef)(null);
  const isChecked = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => checked != null ? checked : getIsChecked(value, state == null ? void 0 : state.value)
  );
  (0,react.useEffect)(() => {
    if (!id) return;
    if (!isChecked) return;
    const isActiveItem = (store == null ? void 0 : store.getState().activeId) === id;
    if (isActiveItem) return;
    store == null ? void 0 : store.setActiveId(id);
  }, [store, isChecked, id]);
  const onChangeProp = props.onChange;
  const tagName = (0,CEM7J6TT/* useTagName */.vO)(ref, AZ7UIBND_TagName);
  const nativeRadio = isNativeRadio(tagName, props.type);
  const disabled = (0,UWJK2WK2/* disabledFromProps */.$f)(props);
  const [propertyUpdated, schedulePropertyUpdate] = (0,CEM7J6TT/* useForceUpdate */.CH)();
  (0,react.useEffect)(() => {
    const element = ref.current;
    if (!element) return;
    if (nativeRadio) return;
    if (isChecked !== void 0) {
      element.checked = isChecked;
    }
    if (name !== void 0) {
      element.name = name;
    }
    if (value !== void 0) {
      element.value = `${value}`;
    }
  }, [propertyUpdated, nativeRadio, isChecked, name, value]);
  const onChange = (0,CEM7J6TT/* useEvent */._q)((event) => {
    if (disabled) {
      event.preventDefault();
      event.stopPropagation();
      return;
    }
    if ((store == null ? void 0 : store.getState().value) === value) return;
    if (!nativeRadio) {
      event.currentTarget.checked = true;
      schedulePropertyUpdate();
    }
    onChangeProp == null ? void 0 : onChangeProp(event);
    if (event.defaultPrevented) return;
    store == null ? void 0 : store.setValue(value);
  });
  const onClickProp = props.onClick;
  const onClick = (0,CEM7J6TT/* useEvent */._q)((event) => {
    onClickProp == null ? void 0 : onClickProp(event);
    if (event.defaultPrevented) return;
    if (nativeRadio) return;
    onChange(event);
  });
  const onFocusProp = props.onFocus;
  const onFocus = (0,CEM7J6TT/* useEvent */._q)((event) => {
    onFocusProp == null ? void 0 : onFocusProp(event);
    if (event.defaultPrevented) return;
    if (!nativeRadio) return;
    if (!store) return;
    const { moves, activeId } = store.getState();
    if (!moves) return;
    if (id && activeId !== id) return;
    onChange(event);
  });
  props = {
    role: !nativeRadio ? "radio" : void 0,
    type: nativeRadio ? "radio" : void 0,
    "aria-checked": isChecked,
    ...props,
    id,
    ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref),
    onChange,
    onClick,
    onFocus
  };
  props = (0,ZCYMVQGT/* useCompositeItem */.k)({
    store,
    clickOnEnter: !nativeRadio,
    ...props
  });
  return (0,UWJK2WK2/* removeUndefinedValues */.HR)({
    name: nativeRadio ? name : void 0,
    value: nativeRadio ? value : void 0,
    checked: isChecked,
    ...props
  });
});
var Radio = (0,TVXRYIJB/* memo */.ph)(
  (0,TVXRYIJB/* forwardRef */.Rf)(function Radio2(props) {
    const htmlProps = useRadio(props);
    return (0,TVXRYIJB/* createElement */.n)(AZ7UIBND_TagName, htmlProps);
  })
);



;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option-base/styles.js

function toggle_group_control_option_base_styles_EMOTION_STRINGIFIED_CSS_ERROR_() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}


const LabelView = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "et6ln9s1"
} : 0)( true ? {
  name: "sln1fl",
  styles: "display:inline-flex;max-width:100%;min-width:0;position:relative"
} : 0);
const labelBlock =  true ? {
  name: "82a6rk",
  styles: "flex:1"
} : 0;
const buttonView = ({
  isDeselectable,
  isIcon,
  isPressed,
  size
}) => /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("align-items:center;appearance:none;background:transparent;border:none;border-radius:", config_values/* default */.A.radiusXSmall, ";color:", colors_values/* COLORS */.l.theme.gray[700], ";fill:currentColor;cursor:pointer;display:flex;font-family:inherit;height:100%;justify-content:center;line-height:100%;outline:none;padding:0 12px;position:relative;text-align:center;@media not ( prefers-reduced-motion ){transition:background ", config_values/* default */.A.transitionDurationFast, " linear,color ", config_values/* default */.A.transitionDurationFast, " linear,font-weight 60ms linear;}user-select:none;width:100%;z-index:2;&::-moz-focus-inner{border:0;}&[disabled]{opacity:0.4;cursor:default;}&:active{background:", colors_values/* COLORS */.l.ui.background, ";}", isDeselectable && deselectable, " ", isIcon && isIconStyles({
  size
}), " ", isPressed && pressed, ";" + ( true ? "" : 0),  true ? "" : 0);
const pressed = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.l.theme.foregroundInverted, ";&:active{background:transparent;}" + ( true ? "" : 0),  true ? "" : 0);
const deselectable = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.l.theme.foreground, ";&:focus{box-shadow:inset 0 0 0 1px ", colors_values/* COLORS */.l.ui.background, ",0 0 0 ", config_values/* default */.A.borderWidthFocus, " ", colors_values/* COLORS */.l.theme.accent, ";outline:2px solid transparent;}" + ( true ? "" : 0),  true ? "" : 0);
const ButtonContentView = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "et6ln9s0"
} : 0)("display:flex;font-size:", config_values/* default */.A.fontSize, ";line-height:1;" + ( true ? "" : 0));
const isIconStyles = ({
  size = "default"
}) => {
  const iconButtonSizes = {
    default: "30px",
    "__unstable-large": "32px"
  };
  return /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.l.theme.foreground, ";height:", iconButtonSizes[size], ";aspect-ratio:1;padding-left:0;padding-right:0;" + ( true ? "" : 0),  true ? "" : 0);
};

//# sourceMappingURL=styles.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option-base/component.js









const {
  /* ButtonContentView */ "Rp": component_ButtonContentView,
  /* LabelView */ "y0": component_LabelView
} = toggle_group_control_option_base_styles_namespaceObject;
const WithToolTip = ({
  showTooltip,
  text,
  children
}) => {
  if (showTooltip && text) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(tooltip/* default */.Ay, {
      text,
      placement: "top",
      children
    });
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children
  });
};
function ToggleGroupControlOptionBase(props, forwardedRef) {
  const toggleGroupControlContext = useToggleGroupControlContext();
  const id = (0,use_instance_id/* default */.A)(ToggleGroupControlOptionBase, toggleGroupControlContext.baseId || "toggle-group-control-option-base");
  const buttonProps = (0,use_context_system/* useContextSystem */.A)({
    ...props,
    id
  }, "ToggleGroupControlOptionBase");
  const {
    isBlock = false,
    isDeselectable = false,
    size = "default"
  } = toggleGroupControlContext;
  const {
    className,
    isIcon = false,
    value,
    children,
    showTooltip = false,
    disabled,
    ...otherButtonProps
  } = buttonProps;
  const isPressed = toggleGroupControlContext.value === value;
  const cx = (0,use_cx/* useCx */.l)();
  const labelViewClasses = (0,react.useMemo)(() => cx(isBlock && labelBlock), [cx, isBlock]);
  const itemClasses = (0,react.useMemo)(() => cx(buttonView({
    isDeselectable,
    isIcon,
    isPressed,
    size
  }), className), [cx, isDeselectable, isIcon, isPressed, size, className]);
  const buttonOnClick = () => {
    if (isDeselectable && isPressed) {
      toggleGroupControlContext.setValue(void 0);
    } else {
      toggleGroupControlContext.setValue(value);
    }
  };
  const commonProps = {
    ...otherButtonProps,
    className: itemClasses,
    "data-value": value,
    ref: forwardedRef
  };
  const labelRef = (0,react.useRef)(null);
  (0,react.useLayoutEffect)(() => {
    if (isPressed && labelRef.current) {
      toggleGroupControlContext.setSelectedElement(labelRef.current);
    }
  }, [isPressed, toggleGroupControlContext]);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component_LabelView, {
    ref: labelRef,
    className: labelViewClasses,
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)(WithToolTip, {
      showTooltip,
      text: otherButtonProps["aria-label"],
      children: isDeselectable ? /* @__PURE__ */ (0,jsx_runtime.jsx)("button", {
        ...commonProps,
        disabled,
        "aria-pressed": isPressed,
        type: "button",
        onClick: buttonOnClick,
        children: /* @__PURE__ */ (0,jsx_runtime.jsx)(component_ButtonContentView, {
          children
        })
      }) : /* @__PURE__ */ (0,jsx_runtime.jsx)(Radio, {
        disabled,
        onFocusVisible: () => {
          const selectedValueIsEmpty = toggleGroupControlContext.value === null || toggleGroupControlContext.value === "";
          if (!selectedValueIsEmpty || toggleGroupControlContext.activeItemIsNotFirstItem?.()) {
            toggleGroupControlContext.setValue(value);
          }
        },
        render: /* @__PURE__ */ (0,jsx_runtime.jsx)("button", {
          type: "button",
          ...commonProps
        }),
        value,
        children: /* @__PURE__ */ (0,jsx_runtime.jsx)(component_ButtonContentView, {
          children
        })
      })
    })
  });
}
const ConnectedToggleGroupControlOptionBase = (0,context_connect/* contextConnect */.KZ)(ToggleGroupControlOptionBase, "ToggleGroupControlOptionBase");
var component_component_default = ConnectedToggleGroupControlOptionBase;

//# sourceMappingURL=component.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option/component.js



function UnforwardedToggleGroupControlOption(props, ref) {
  const {
    label,
    ...restProps
  } = props;
  const optionLabel = restProps["aria-label"] || label;
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component_component_default, {
    ...restProps,
    "aria-label": optionLabel,
    ref,
    children: label
  });
}
const ToggleGroupControlOption = (0,react.forwardRef)(UnforwardedToggleGroupControlOption);
var toggle_group_control_option_component_component_default = ToggleGroupControlOption;

//# sourceMappingURL=component.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/time/time-input/index.js










function TimeInput({
  value: valueProp,
  defaultValue,
  is12Hour,
  label,
  minutesProps,
  onChange
}) {
  const [value = {
    hours: (/* @__PURE__ */ new Date()).getHours(),
    minutes: (/* @__PURE__ */ new Date()).getMinutes()
  }, setValue] = (0,use_controlled_value/* useControlledValue */.j)({
    value: valueProp,
    onChange,
    defaultValue
  });
  const dayPeriod = parseDayPeriod(value.hours);
  const hours12Format = (0,utils/* from24hTo12h */.jh)(value.hours);
  const buildNumberControlChangeCallback = (method) => {
    return (_value, {
      event
    }) => {
      if (!(0,utils/* validateInputElementTarget */.qN)(event)) {
        return;
      }
      const numberValue = Number(_value);
      setValue({
        ...value,
        [method]: method === "hours" && is12Hour ? (0,utils/* from12hTo24h */.th)(numberValue, dayPeriod === "PM") : numberValue
      });
    };
  };
  const buildAmPmChangeCallback = (_value) => {
    return () => {
      if (dayPeriod === _value) {
        return;
      }
      setValue({
        ...value,
        hours: (0,utils/* from12hTo24h */.th)(hours12Format, _value === "PM")
      });
    };
  };
  function parseDayPeriod(_hours) {
    return _hours < 12 ? "AM" : "PM";
  }
  const Wrapper = label ? Fieldset : react.Fragment;
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(Wrapper, {
    children: [label && /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default.VisualLabel */.Ay.VisualLabel, {
      as: "legend",
      children: label
    }), /* @__PURE__ */ (0,jsx_runtime.jsxs)(h_stack_component/* default */.A, {
      alignment: "left",
      expanded: false,
      children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)(TimeWrapper, {
        className: "components-datetime__time-field components-datetime__time-field-time",
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(HoursInput, {
          className: "components-datetime__time-field-hours-input",
          label: (0,build_module.__)("Hours"),
          hideLabelFromVision: true,
          __next40pxDefaultSize: true,
          value: String(is12Hour ? hours12Format : value.hours).padStart(2, "0"),
          step: 1,
          min: is12Hour ? 1 : 0,
          max: is12Hour ? 12 : 23,
          required: true,
          spinControls: "none",
          isPressEnterToChange: true,
          isDragEnabled: false,
          isShiftStepEnabled: false,
          onChange: buildNumberControlChangeCallback("hours"),
          __unstableStateReducer: (0,utils/* buildPadInputStateReducer */.nK)(2)
        }), /* @__PURE__ */ (0,jsx_runtime.jsx)(TimeSeparator, {
          className: "components-datetime__time-separator",
          "aria-hidden": "true",
          children: ":"
        }), /* @__PURE__ */ (0,jsx_runtime.jsx)(MinutesInput, {
          className: (0,clsx/* default */.A)(
            "components-datetime__time-field-minutes-input",
            // Unused, for backwards compatibility.
            minutesProps?.className
          ),
          label: (0,build_module.__)("Minutes"),
          hideLabelFromVision: true,
          __next40pxDefaultSize: true,
          value: String(value.minutes).padStart(2, "0"),
          step: 1,
          min: 0,
          max: 59,
          required: true,
          spinControls: "none",
          isPressEnterToChange: true,
          isDragEnabled: false,
          isShiftStepEnabled: false,
          onChange: (...args) => {
            buildNumberControlChangeCallback("minutes")(...args);
            minutesProps?.onChange?.(...args);
          },
          __unstableStateReducer: (0,utils/* buildPadInputStateReducer */.nK)(2),
          ...minutesProps
        })]
      }), is12Hour && /* @__PURE__ */ (0,jsx_runtime.jsxs)(component_default, {
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true,
        isBlock: true,
        label: (0,build_module.__)("Select AM or PM"),
        hideLabelFromVision: true,
        value: dayPeriod,
        onChange: (newValue) => {
          buildAmPmChangeCallback(newValue)();
        },
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(toggle_group_control_option_component_component_default, {
          value: "AM",
          label: (0,build_module.__)("AM")
        }), /* @__PURE__ */ (0,jsx_runtime.jsx)(toggle_group_control_option_component_component_default, {
          value: "PM",
          label: (0,build_module.__)("PM")
        })]
      })]
    })]
  });
}
var time_input_default = (/* unused pure expression or super */ null && (TimeInput));

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/time/index.js














const VALID_DATE_ORDERS = ["dmy", "mdy", "ymd"];
function TimePicker({
  is12Hour,
  currentTime,
  onChange,
  dateOrder: dateOrderProp,
  hideLabelFromVision = false
}) {
  const [date, setDate] = (0,react.useState)(() => (
    // Truncate the date at the minutes, see: #15495.
    currentTime ? startOfMinute((0,utils/* inputToDate */.Ch)(currentTime)) : /* @__PURE__ */ new Date()
  ));
  (0,react.useEffect)(() => {
    setDate(currentTime ? startOfMinute((0,utils/* inputToDate */.Ch)(currentTime)) : /* @__PURE__ */ new Date());
  }, [currentTime]);
  const monthOptions = [{
    value: "01",
    label: (0,build_module.__)("January")
  }, {
    value: "02",
    label: (0,build_module.__)("February")
  }, {
    value: "03",
    label: (0,build_module.__)("March")
  }, {
    value: "04",
    label: (0,build_module.__)("April")
  }, {
    value: "05",
    label: (0,build_module.__)("May")
  }, {
    value: "06",
    label: (0,build_module.__)("June")
  }, {
    value: "07",
    label: (0,build_module.__)("July")
  }, {
    value: "08",
    label: (0,build_module.__)("August")
  }, {
    value: "09",
    label: (0,build_module.__)("September")
  }, {
    value: "10",
    label: (0,build_module.__)("October")
  }, {
    value: "11",
    label: (0,build_module.__)("November")
  }, {
    value: "12",
    label: (0,build_module.__)("December")
  }];
  const {
    day,
    month,
    year,
    minutes,
    hours
  } = (0,react.useMemo)(() => ({
    day: (0,format/* format */.GP)(date, "dd"),
    month: (0,format/* format */.GP)(date, "MM"),
    year: (0,format/* format */.GP)(date, "yyyy"),
    minutes: (0,format/* format */.GP)(date, "mm"),
    hours: (0,format/* format */.GP)(date, "HH"),
    am: (0,format/* format */.GP)(date, "a")
  }), [date]);
  const buildNumberControlChangeCallback = (method) => {
    const callback = (value, {
      event
    }) => {
      if (!(0,utils/* validateInputElementTarget */.qN)(event)) {
        return;
      }
      const numberValue = Number(value);
      const newDate = (0,set/* set */.h)(date, {
        [method]: numberValue
      });
      setDate(newDate);
      onChange?.((0,format/* format */.GP)(newDate, constants/* TIMEZONELESS_FORMAT */.T));
    };
    return callback;
  };
  const onTimeInputChangeCallback = ({
    hours: newHours,
    minutes: newMinutes
  }) => {
    const newDate = (0,set/* set */.h)(date, {
      hours: newHours,
      minutes: newMinutes
    });
    setDate(newDate);
    onChange?.((0,format/* format */.GP)(newDate, constants/* TIMEZONELESS_FORMAT */.T));
  };
  const dayField = /* @__PURE__ */ (0,jsx_runtime.jsx)(DayInput, {
    className: "components-datetime__time-field components-datetime__time-field-day",
    label: (0,build_module.__)("Day"),
    hideLabelFromVision: true,
    __next40pxDefaultSize: true,
    value: day,
    step: 1,
    min: 1,
    max: 31,
    required: true,
    spinControls: "none",
    isPressEnterToChange: true,
    isDragEnabled: false,
    isShiftStepEnabled: false,
    onChange: buildNumberControlChangeCallback("date")
  }, "day");
  const monthField = /* @__PURE__ */ (0,jsx_runtime.jsx)(MonthSelectWrapper, {
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)(select_control/* default */.A, {
      className: "components-datetime__time-field components-datetime__time-field-month",
      label: (0,build_module.__)("Month"),
      hideLabelFromVision: true,
      __next40pxDefaultSize: true,
      __nextHasNoMarginBottom: true,
      value: month,
      options: monthOptions,
      onChange: (value) => {
        const newDate = (0,setMonth/* setMonth */.Z)(date, Number(value) - 1);
        setDate(newDate);
        onChange?.((0,format/* format */.GP)(newDate, constants/* TIMEZONELESS_FORMAT */.T));
      }
    })
  }, "month");
  const yearField = /* @__PURE__ */ (0,jsx_runtime.jsx)(YearInput, {
    className: "components-datetime__time-field components-datetime__time-field-year",
    label: (0,build_module.__)("Year"),
    hideLabelFromVision: true,
    __next40pxDefaultSize: true,
    value: year,
    step: 1,
    min: 1,
    max: 9999,
    required: true,
    spinControls: "none",
    isPressEnterToChange: true,
    isDragEnabled: false,
    isShiftStepEnabled: false,
    onChange: buildNumberControlChangeCallback("year"),
    __unstableStateReducer: (0,utils/* buildPadInputStateReducer */.nK)(4)
  }, "year");
  const defaultDateOrder = is12Hour ? "mdy" : "dmy";
  const dateOrder = dateOrderProp && VALID_DATE_ORDERS.includes(dateOrderProp) ? dateOrderProp : defaultDateOrder;
  const fields = dateOrder.split("").map((field) => {
    switch (field) {
      case "d":
        return dayField;
      case "m":
        return monthField;
      case "y":
        return yearField;
      default:
        return null;
    }
  });
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(Wrapper, {
    className: "components-datetime__time",
    children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)(Fieldset, {
      children: [hideLabelFromVision ? /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
        as: "legend",
        children: (0,build_module.__)("Time")
      }) : /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default.VisualLabel */.Ay.VisualLabel, {
        as: "legend",
        className: "components-datetime__time-legend",
        children: (0,build_module.__)("Time")
      }), /* @__PURE__ */ (0,jsx_runtime.jsxs)(h_stack_component/* default */.A, {
        className: "components-datetime__time-wrapper",
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(TimeInput, {
          value: {
            hours: Number(hours),
            minutes: Number(minutes)
          },
          is12Hour,
          onChange: onTimeInputChangeCallback
        }), /* @__PURE__ */ (0,jsx_runtime.jsx)(spacer_component/* default */.A, {}), /* @__PURE__ */ (0,jsx_runtime.jsx)(timezone_default, {})]
      })]
    }), /* @__PURE__ */ (0,jsx_runtime.jsxs)(Fieldset, {
      children: [hideLabelFromVision ? /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
        as: "legend",
        children: (0,build_module.__)("Date")
      }) : /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default.VisualLabel */.Ay.VisualLabel, {
        as: "legend",
        className: "components-datetime__time-legend",
        children: (0,build_module.__)("Date")
      }), /* @__PURE__ */ (0,jsx_runtime.jsx)(h_stack_component/* default */.A, {
        className: "components-datetime__time-wrapper",
        children: fields
      })]
    })]
  });
}
TimePicker.TimeInput = TimeInput;
Object.assign(TimePicker.TimeInput, {
  displayName: "TimePicker.TimeInput"
});
var time_default = TimePicker;

//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/v-stack/component.js + 1 modules
var v_stack_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/v-stack/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date-time/styles.js

function date_time_styles_EMOTION_STRINGIFIED_CSS_ERROR_() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}

const styles_Wrapper = /* @__PURE__ */ (0,emotion_styled_base_browser_esm/* default */.A)(v_stack_component/* default */.A,  true ? {
  target: "e1p5onf00"
} : 0)( true ? {
  name: "1khn195",
  styles: "box-sizing:border-box"
} : 0);

//# sourceMappingURL=styles.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/date-time/index.js





const date_time_noop = () => {
};
function UnforwardedDateTimePicker({
  currentDate,
  is12Hour,
  dateOrder,
  isInvalidDate,
  onMonthPreviewed = date_time_noop,
  onChange,
  events,
  startOfWeek
}, ref) {
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(styles_Wrapper, {
    ref,
    className: "components-datetime",
    spacing: 4,
    children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(time_default, {
        currentTime: currentDate,
        onChange,
        is12Hour,
        dateOrder
      }), /* @__PURE__ */ (0,jsx_runtime.jsx)(date/* default */.A, {
        currentDate,
        onChange,
        isInvalidDate,
        events,
        onMonthPreviewed,
        startOfWeek
      })]
    })
  });
}
const DateTimePicker = (0,react.forwardRef)(UnforwardedDateTimePicker);
var date_time_default = DateTimePicker;

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/date-time/index.js



var date_time_date_time_default = date_time_default;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ useDebounce)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/use-memo-one@1.1.3_react@18.3.1/node_modules/use-memo-one/dist/use-memo-one.esm.js
var use_memo_one_esm = __webpack_require__("../../node_modules/.pnpm/use-memo-one@1.1.3_react@18.3.1/node_modules/use-memo-one/dist/use-memo-one.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/debounce/index.js
const debounce = (func, wait, options) => {
  let lastArgs;
  let lastThis;
  let maxWait = 0;
  let result;
  let timerId;
  let lastCallTime;
  let lastInvokeTime = 0;
  let leading = false;
  let maxing = false;
  let trailing = true;
  if (options) {
    leading = !!options.leading;
    maxing = "maxWait" in options;
    if (options.maxWait !== void 0) {
      maxWait = Math.max(options.maxWait, wait);
    }
    trailing = "trailing" in options ? !!options.trailing : trailing;
  }
  function invokeFunc(time) {
    const args = lastArgs;
    const thisArg = lastThis;
    lastArgs = void 0;
    lastThis = void 0;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }
  function startTimer(pendingFunc, waitTime) {
    timerId = setTimeout(pendingFunc, waitTime);
  }
  function cancelTimer() {
    if (timerId !== void 0) {
      clearTimeout(timerId);
    }
  }
  function leadingEdge(time) {
    lastInvokeTime = time;
    startTimer(timerExpired, wait);
    return leading ? invokeFunc(time) : result;
  }
  function getTimeSinceLastCall(time) {
    return time - (lastCallTime || 0);
  }
  function remainingWait(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime;
    const timeWaiting = wait - timeSinceLastCall;
    return maxing ? Math.min(timeWaiting, maxWait - timeSinceLastInvoke) : timeWaiting;
  }
  function shouldInvoke(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime;
    return lastCallTime === void 0 || timeSinceLastCall >= wait || timeSinceLastCall < 0 || maxing && timeSinceLastInvoke >= maxWait;
  }
  function timerExpired() {
    const time = Date.now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    startTimer(timerExpired, remainingWait(time));
    return void 0;
  }
  function clearTimer() {
    timerId = void 0;
  }
  function trailingEdge(time) {
    clearTimer();
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = void 0;
    return result;
  }
  function cancel() {
    cancelTimer();
    lastInvokeTime = 0;
    clearTimer();
    lastArgs = lastCallTime = lastThis = void 0;
  }
  function flush() {
    return pending() ? trailingEdge(Date.now()) : result;
  }
  function pending() {
    return timerId !== void 0;
  }
  function debounced(...args) {
    const time = Date.now();
    const isInvoking = shouldInvoke(time);
    lastArgs = args;
    lastThis = this;
    lastCallTime = time;
    if (isInvoking) {
      if (!pending()) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        startTimer(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (!pending()) {
      startTimer(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  debounced.pending = pending;
  return debounced;
};

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js



function useDebounce(fn, wait, options) {
  const debounced = (0,use_memo_one_esm/* useMemoOne */.MA)(
    () => debounce(fn, wait ?? 0, options),
    [fn, wait, options?.leading, options?.trailing, options?.maxWait]
  );
  (0,react.useEffect)(() => () => debounced.cancel(), [debounced]);
  return debounced;
}

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-event/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useEvent)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-event/index.ts

function useEvent(callback) {
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(() => {
    throw new Error(
      "Callbacks created with `useEvent` cannot be called during rendering."
    );
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useInsertionEffect)(() => {
    ref.current = callback;
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(
    (...args) => ref.current?.(...args),
    []
  );
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ usePrevious)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-previous/index.ts

function usePrevious(value) {
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(void 0);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    ref.current = value;
  }, [value]);
  return ref.current;
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/use-memo-one@1.1.3_react@18.3.1/node_modules/use-memo-one/dist/use-memo-one.esm.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   MA: () => (/* binding */ useMemoOne)
/* harmony export */ });
/* unused harmony exports useCallback, useCallbackOne, useMemo */
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");


function areInputsEqual(newInputs, lastInputs) {
  if (newInputs.length !== lastInputs.length) {
    return false;
  }

  for (var i = 0; i < newInputs.length; i++) {
    if (newInputs[i] !== lastInputs[i]) {
      return false;
    }
  }

  return true;
}

function useMemoOne(getResult, inputs) {
  var initial = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(function () {
    return {
      inputs: inputs,
      result: getResult()
    };
  })[0];
  var isFirstRun = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(true);
  var committed = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(initial);
  var useCache = isFirstRun.current || Boolean(inputs && committed.current.inputs && areInputsEqual(inputs, committed.current.inputs));
  var cache = useCache ? committed.current : {
    inputs: inputs,
    result: getResult()
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    isFirstRun.current = false;
    committed.current = cache;
  }, [cache]);
  return cache.result;
}
function useCallbackOne(callback, inputs) {
  return useMemoOne(function () {
    return callback;
  }, inputs);
}
var useMemo = (/* unused pure expression or super */ null && (useMemoOne));
var useCallback = (/* unused pure expression or super */ null && (useCallbackOne));




/***/ })

}]);