"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5114],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _arrayLikeToArray)
/* harmony export */ });
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(obj, key, value) {
  key = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(key);
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _toPropertyKey)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPrimitive.js

function _toPrimitive(input, hint) {
  if ((0,esm_typeof/* default */.A)(input) !== "object" || input === null) return input;
  var prim = input[Symbol.toPrimitive];
  if (prim !== undefined) {
    var res = prim.call(input, hint || "default");
    if ((0,esm_typeof/* default */.A)(res) !== "object") return res;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (hint === "string" ? String : Number)(input);
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js


function _toPropertyKey(arg) {
  var key = _toPrimitive(arg, "string");
  return (0,esm_typeof/* default */.A)(key) === "symbol" ? key : String(key);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _unsupportedIterableToArray)
/* harmony export */ });
/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(o, minLen);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ input_control)
});

// UNUSED EXPORTS: InputControl

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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/input-base.js + 2 modules
var input_base = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/input-base.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@use-gesture+react@10.3.0_react@17.0.2/node_modules/@use-gesture/react/dist/use-gesture-react.esm.js + 3 modules
var use_gesture_react_esm = __webpack_require__("../../node_modules/.pnpm/@use-gesture+react@10.3.0_react@17.0.2/node_modules/@use-gesture/react/dist/use-gesture-react.esm.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/utils.js
/**
 * WordPress dependencies
 */

/**
 * Gets a CSS cursor value based on a drag direction.
 *
 * @param  dragDirection The drag direction.
 * @return  The CSS cursor value.
 */

function getDragCursor(dragDirection) {
  let dragCursor = 'ns-resize';

  switch (dragDirection) {
    case 'n':
    case 's':
      dragCursor = 'ns-resize';
      break;

    case 'e':
    case 'w':
      dragCursor = 'ew-resize';
      break;
  }

  return dragCursor;
}
/**
 * Custom hook that renders a drag cursor when dragging.
 *
 * @param {boolean} isDragging    The dragging state.
 * @param {string}  dragDirection The drag direction.
 *
 * @return {string} The CSS cursor value.
 */

function useDragCursor(isDragging, dragDirection) {
  const dragCursor = getDragCursor(dragDirection);
  (0,react.useEffect)(() => {
    if (isDragging) {
      document.documentElement.style.cursor = dragCursor;
    } else {
      // @ts-expect-error
      document.documentElement.style.cursor = null;
    }
  }, [isDragging]);
  return dragCursor;
}
//# sourceMappingURL=utils.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/styles/input-control-styles.js
var input_control_styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/styles/input-control-styles.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/reducer/state.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
const initialStateReducer = state => state;
const initialInputControlState = {
  _event: {},
  error: null,
  initialValue: '',
  isDirty: false,
  isDragEnabled: false,
  isDragging: false,
  isPressEnterToChange: false,
  value: ''
};
//# sourceMappingURL=state.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/reducer/actions.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
const CHANGE = 'CHANGE';
const COMMIT = 'COMMIT';
const DRAG_END = 'DRAG_END';
const DRAG_START = 'DRAG_START';
const DRAG = 'DRAG';
const INVALIDATE = 'INVALIDATE';
const PRESS_DOWN = 'PRESS_DOWN';
const PRESS_ENTER = 'PRESS_ENTER';
const PRESS_UP = 'PRESS_UP';
const RESET = 'RESET';
//# sourceMappingURL=actions.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/reducer/reducer.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



/**
 * Prepares initialState for the reducer.
 *
 * @param  initialState The initial state.
 * @return Prepared initialState for the reducer
 */

function mergeInitialState() {
  let initialState = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : initialInputControlState;
  const {
    value
  } = initialState;
  return { ...initialInputControlState,
    ...initialState,
    initialValue: value
  };
}
/**
 * Creates a reducer that opens the channel for external state subscription
 * and modification.
 *
 * This technique uses the "stateReducer" design pattern:
 * https://kentcdodds.com/blog/the-state-reducer-pattern/
 *
 * @param  composedStateReducers A custom reducer that can subscribe and modify state.
 * @return The reducer.
 */


function inputControlStateReducer(composedStateReducers) {
  return (state, action) => {
    const nextState = { ...state
    };

    switch (action.type) {
      /**
       * Keyboard events
       */
      case PRESS_UP:
        nextState.isDirty = false;
        break;

      case PRESS_DOWN:
        nextState.isDirty = false;
        break;

      /**
       * Drag events
       */

      case DRAG_START:
        nextState.isDragging = true;
        break;

      case DRAG_END:
        nextState.isDragging = false;
        break;

      /**
       * Input events
       */

      case CHANGE:
        nextState.error = null;
        nextState.value = action.payload.value;

        if (state.isPressEnterToChange) {
          nextState.isDirty = true;
        }

        break;

      case COMMIT:
        nextState.value = action.payload.value;
        nextState.isDirty = false;
        break;

      case RESET:
        nextState.error = null;
        nextState.isDirty = false;
        nextState.value = action.payload.value || state.initialValue;
        break;

      /**
       * Validation
       */

      case INVALIDATE:
        nextState.error = action.payload.error;
        break;
    }

    if (action.payload.event) {
      nextState._event = action.payload.event;
    }
    /**
     * Send the nextState + action to the composedReducers via
     * this "bridge" mechanism. This allows external stateReducers
     * to hook into actions, and modify state if needed.
     */


    return composedStateReducers(nextState, action);
  };
}
/**
 * A custom hook that connects and external stateReducer with an internal
 * reducer. This hook manages the internal state of InputControl.
 * However, by connecting an external stateReducer function, other
 * components can react to actions as well as modify state before it is
 * applied.
 *
 * This technique uses the "stateReducer" design pattern:
 * https://kentcdodds.com/blog/the-state-reducer-pattern/
 *
 * @param  stateReducer An external state reducer.
 * @param  initialState The initial state for the reducer.
 * @return State, dispatch, and a collection of actions.
 */


function useInputControlStateReducer() {
  let stateReducer = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : initialStateReducer;
  let initialState = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : initialInputControlState;
  const [state, dispatch] = (0,react.useReducer)(inputControlStateReducer(stateReducer), mergeInitialState(initialState));

  const createChangeEvent = type => (nextValue, event) => {
    /**
     * Persist allows for the (Synthetic) event to be used outside of
     * this function call.
     * https://reactjs.org/docs/events.html#event-pooling
     */
    if (event && event.persist) {
      event.persist();
    }

    dispatch({
      type,
      payload: {
        value: nextValue,
        event
      }
    });
  };

  const createKeyEvent = type => event => {
    /**
     * Persist allows for the (Synthetic) event to be used outside of
     * this function call.
     * https://reactjs.org/docs/events.html#event-pooling
     */
    if (event && event.persist) {
      event.persist();
    }

    dispatch({
      type,
      payload: {
        event
      }
    });
  };

  const createDragEvent = type => payload => {
    dispatch({
      type,
      payload
    });
  };
  /**
   * Actions for the reducer
   */


  const change = createChangeEvent(CHANGE);

  const invalidate = (error, event) => dispatch({
    type: INVALIDATE,
    payload: {
      error,
      event
    }
  });

  const reset = createChangeEvent(RESET);
  const commit = createChangeEvent(COMMIT);
  const dragStart = createDragEvent(DRAG_START);
  const drag = createDragEvent(DRAG);
  const dragEnd = createDragEvent(DRAG_END);
  const pressUp = createKeyEvent(PRESS_UP);
  const pressDown = createKeyEvent(PRESS_DOWN);
  const pressEnter = createKeyEvent(PRESS_ENTER);
  return {
    change,
    commit,
    dispatch,
    drag,
    dragEnd,
    dragStart,
    invalidate,
    pressDown,
    pressEnter,
    pressUp,
    reset,
    state
  };
}
//# sourceMappingURL=reducer.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-update-effect.js
/**
 * WordPress dependencies
 */

/**
 * A `React.useEffect` that will not run on the first render.
 * Source:
 * https://github.com/reakit/reakit/blob/HEAD/packages/reakit-utils/src/useUpdateEffect.ts
 *
 * @param {import('react').EffectCallback} effect
 * @param {import('react').DependencyList} deps
 */

function useUpdateEffect(effect, deps) {
  const mounted = (0,react.useRef)(false);
  (0,react.useEffect)(() => {
    if (mounted.current) {
      return effect();
    }

    mounted.current = true;
    return undefined;
  }, deps);
}

/* harmony default export */ const use_update_effect = (useUpdateEffect);
//# sourceMappingURL=use-update-effect.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/input-field.js



/**
 * External dependencies
 */



/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */






function InputField(_ref, ref) {
  let {
    disabled = false,
    dragDirection = 'n',
    dragThreshold = 10,
    id,
    isDragEnabled = false,
    isFocused,
    isPressEnterToChange = false,
    onBlur = lodash.noop,
    onChange = lodash.noop,
    onDrag = lodash.noop,
    onDragEnd = lodash.noop,
    onDragStart = lodash.noop,
    onFocus = lodash.noop,
    onKeyDown = lodash.noop,
    onValidate = lodash.noop,
    size = 'default',
    setIsFocused,
    stateReducer = state => state,
    value: valueProp,
    type,
    ...props
  } = _ref;
  const {
    // State.
    state,
    // Actions.
    change,
    commit,
    drag,
    dragEnd,
    dragStart,
    invalidate,
    pressDown,
    pressEnter,
    pressUp,
    reset
  } = useInputControlStateReducer(stateReducer, {
    isDragEnabled,
    value: valueProp,
    isPressEnterToChange
  });
  const {
    _event,
    value,
    isDragging,
    isDirty
  } = state;
  const wasDirtyOnBlur = (0,react.useRef)(false);
  const dragCursor = useDragCursor(isDragging, dragDirection);
  /*
   * Handles synchronization of external and internal value state.
   * If not focused and did not hold a dirty value[1] on blur
   * updates the value from the props. Otherwise if not holding
   * a dirty value[1] propagates the value and event through onChange.
   * [1] value is only made dirty if isPressEnterToChange is true
   */

  use_update_effect(() => {
    if (valueProp === value) {
      return;
    }

    if (!isFocused && !wasDirtyOnBlur.current) {
      commit(valueProp, _event);
    } else if (!isDirty) {
      onChange(value, {
        event: _event
      });
      wasDirtyOnBlur.current = false;
    }
  }, [value, isDirty, isFocused, valueProp]);

  const handleOnBlur = event => {
    onBlur(event);
    setIsFocused === null || setIsFocused === void 0 ? void 0 : setIsFocused(false);
    /**
     * If isPressEnterToChange is set, this commits the value to
     * the onChange callback.
     */

    if (isDirty || !event.target.validity.valid) {
      wasDirtyOnBlur.current = true;
      handleOnCommit(event);
    }
  };

  const handleOnFocus = event => {
    onFocus(event);
    setIsFocused === null || setIsFocused === void 0 ? void 0 : setIsFocused(true);
  };

  const handleOnChange = event => {
    const nextValue = event.target.value;
    change(nextValue, event);
  };

  const handleOnCommit = event => {
    const nextValue = event.currentTarget.value;

    try {
      onValidate(nextValue);
      commit(nextValue, event);
    } catch (err) {
      invalidate(err, event);
    }
  };

  const handleOnKeyDown = event => {
    const {
      key
    } = event;
    onKeyDown(event);

    switch (key) {
      case 'ArrowUp':
        pressUp(event);
        break;

      case 'ArrowDown':
        pressDown(event);
        break;

      case 'Enter':
        pressEnter(event);

        if (isPressEnterToChange) {
          event.preventDefault();
          handleOnCommit(event);
        }

        break;

      case 'Escape':
        if (isPressEnterToChange && isDirty) {
          event.preventDefault();
          reset(valueProp, event);
        }

        break;
    }
  };

  const dragGestureProps = (0,use_gesture_react_esm.useDrag)(dragProps => {
    const {
      distance,
      dragging,
      event,
      target
    } = dragProps; // The `target` prop always references the `input` element while, by
    // default, the `dragProps.event.target` property would reference the real
    // event target (i.e. any DOM element that the pointer is hovering while
    // dragging). Ensuring that the `target` is always the `input` element
    // allows consumers of `InputControl` (or any higher-level control) to
    // check the input's validity by accessing `event.target.validity.valid`.

    dragProps.event = { ...dragProps.event,
      target
    };
    if (!distance) return;
    event.stopPropagation();
    /**
     * Quick return if no longer dragging.
     * This prevents unnecessary value calculations.
     */

    if (!dragging) {
      onDragEnd(dragProps);
      dragEnd(dragProps);
      return;
    }

    onDrag(dragProps);
    drag(dragProps);

    if (!isDragging) {
      onDragStart(dragProps);
      dragStart(dragProps);
    }
  }, {
    axis: dragDirection === 'e' || dragDirection === 'w' ? 'x' : 'y',
    threshold: dragThreshold,
    enabled: isDragEnabled,
    pointer: {
      capture: false
    }
  });
  const dragProps = isDragEnabled ? dragGestureProps() : {};
  /*
   * Works around the odd UA (e.g. Firefox) that does not focus inputs of
   * type=number when their spinner arrows are pressed.
   */

  let handleOnMouseDown;

  if (type === 'number') {
    handleOnMouseDown = event => {
      var _props$onMouseDown;

      (_props$onMouseDown = props.onMouseDown) === null || _props$onMouseDown === void 0 ? void 0 : _props$onMouseDown.call(props, event);

      if (event.currentTarget !== event.currentTarget.ownerDocument.activeElement) {
        event.currentTarget.focus();
      }
    };
  }

  return (0,react.createElement)(input_control_styles/* Input */.pd, (0,esm_extends/* default */.A)({}, props, dragProps, {
    className: "components-input-control__input",
    disabled: disabled,
    dragCursor: dragCursor,
    isDragging: isDragging,
    id: id,
    onBlur: handleOnBlur,
    onChange: handleOnChange,
    onFocus: handleOnFocus,
    onKeyDown: handleOnKeyDown,
    onMouseDown: handleOnMouseDown,
    ref: ref,
    inputSize: size,
    value: value,
    type: type
  }));
}

const ForwardedComponent = (0,react.forwardRef)(InputField);
/* harmony default export */ const input_field = (ForwardedComponent);
//# sourceMappingURL=input-field.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/input-control/index.js



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
  const instanceId = (0,use_instance_id/* default */.A)(InputControl);
  const id = `inspector-input-control-${instanceId}`;
  return idProp || id;
}

function InputControl(_ref, ref) {
  let {
    __unstableStateReducer: stateReducer = state => state,
    __unstableInputWidth,
    className,
    disabled = false,
    hideLabelFromVision = false,
    id: idProp,
    isPressEnterToChange = false,
    label,
    labelPosition = 'top',
    onChange = lodash.noop,
    onValidate = lodash.noop,
    onKeyDown = lodash.noop,
    prefix,
    size = 'default',
    suffix,
    value,
    ...props
  } = _ref;
  const [isFocused, setIsFocused] = (0,react.useState)(false);
  const id = useUniqueId(idProp);
  const classes = classnames_default()('components-input-control', className);
  return (0,react.createElement)(input_base/* default */.A, {
    __unstableInputWidth: __unstableInputWidth,
    className: classes,
    disabled: disabled,
    gap: 3,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    isFocused: isFocused,
    justify: "left",
    label: label,
    labelPosition: labelPosition,
    prefix: prefix,
    size: size,
    suffix: suffix
  }, (0,react.createElement)(input_field, (0,esm_extends/* default */.A)({}, props, {
    className: "components-input-control__input",
    disabled: disabled,
    id: id,
    isFocused: isFocused,
    isPressEnterToChange: isPressEnterToChange,
    onChange: onChange,
    onKeyDown: onKeyDown,
    onValidate: onValidate,
    ref: ref,
    setIsFocused: setIsFocused,
    size: size,
    stateReducer: stateReducer,
    value: value
  })));
}
const input_control_ForwardedComponent = (0,react.forwardRef)(InputControl);
/* harmony default export */ const input_control = (input_control_ForwardedComponent);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js":
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

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {


var $forEach = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").forEach);
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");

var STRICT_METHOD = arrayMethodIsStrict('forEach');

// `Array.prototype.forEach` method implementation
// https://tc39.es/ecma262/#sec-array.prototype.foreach
module.exports = !STRICT_METHOD ? function forEach(callbackfn /* , thisArg */) {
  return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
} : [].forEach;


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {


var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var wellKnownSymbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/well-known-symbol.js");
var V8_VERSION = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-v8-version.js");

var SPECIES = wellKnownSymbol('species');

module.exports = function (METHOD_NAME) {
  // We can't use this feature detection in V8 since it causes
  // deoptimization and serious performance degradation
  // https://github.com/zloirock/core-js/issues/677
  return V8_VERSION >= 51 || !fails(function () {
    var array = [];
    var constructor = array.constructor = {};
    constructor[SPECIES] = function () {
      return { foo: 1 };
    };
    return array[METHOD_NAME](Boolean).foo !== 1;
  });
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {


var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");

module.exports = function (METHOD_NAME, argument) {
  var method = [][METHOD_NAME];
  return !!method && fails(function () {
    // eslint-disable-next-line no-useless-call -- required for testing
    method.call(null, argument || function () { return 1; }, 1);
  });
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $filter = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").filter);
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');

// `Array.prototype.filter` method
// https://tc39.es/ecma262/#sec-array.prototype.filter
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  filter: function filter(callbackfn /* , thisArg */) {
    return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");

// `Array.prototype.forEach` method
// https://tc39.es/ecma262/#sec-array.prototype.foreach
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
$({ target: 'Array', proto: true, forced: [].forEach !== forEach }, {
  forEach: forEach
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $map = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").map);
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('map');

// `Array.prototype.map` method
// https://tc39.es/ecma262/#sec-array.prototype.map
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  map: function map(callbackfn /* , thisArg */) {
    return $map(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperties = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-properties.js").f);

// `Object.defineProperties` method
// https://tc39.es/ecma262/#sec-object.defineproperties
// eslint-disable-next-line es/no-object-defineproperties -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperties !== defineProperties, sham: !DESCRIPTORS }, {
  defineProperties: defineProperties
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperty = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-property.js").f);

// `Object.defineProperty` method
// https://tc39.es/ecma262/#sec-object.defineproperty
// eslint-disable-next-line es/no-object-defineproperty -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperty !== defineProperty, sham: !DESCRIPTORS }, {
  defineProperty: defineProperty
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var nativeGetOwnPropertyDescriptor = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js").f);
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");

var FORCED = !DESCRIPTORS || fails(function () { nativeGetOwnPropertyDescriptor(1); });

// `Object.getOwnPropertyDescriptor` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptor
$({ target: 'Object', stat: true, forced: FORCED, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptor: function getOwnPropertyDescriptor(it, key) {
    return nativeGetOwnPropertyDescriptor(toIndexedObject(it), key);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var ownKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/own-keys.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var getOwnPropertyDescriptorModule = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js");
var createProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-property.js");

// `Object.getOwnPropertyDescriptors` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptors
$({ target: 'Object', stat: true, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptors: function getOwnPropertyDescriptors(object) {
    var O = toIndexedObject(object);
    var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
    var keys = ownKeys(O);
    var result = {};
    var index = 0;
    var key, descriptor;
    while (keys.length > index) {
      descriptor = getOwnPropertyDescriptor(O, key = keys[index++]);
      if (descriptor !== undefined) createProperty(result, key, descriptor);
    }
    return result;
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var nativeKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-keys.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");

var FAILS_ON_PRIMITIVES = fails(function () { nativeKeys(1); });

// `Object.keys` method
// https://tc39.es/ecma262/#sec-object.keys
$({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES }, {
  keys: function keys(it) {
    return nativeKeys(toObject(it));
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var DOMIterables = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-iterables.js");
var DOMTokenListPrototype = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-token-list-prototype.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");
var createNonEnumerableProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-non-enumerable-property.js");

var handlePrototype = function (CollectionPrototype) {
  // some Chrome versions have non-configurable methods on DOMTokenList
  if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
    createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
  } catch (error) {
    CollectionPrototype.forEach = forEach;
  }
};

for (var COLLECTION_NAME in DOMIterables) {
  if (DOMIterables[COLLECTION_NAME]) {
    handlePrototype(global[COLLECTION_NAME] && global[COLLECTION_NAME].prototype);
  }
}

handlePrototype(DOMTokenListPrototype);


/***/ })

}]);