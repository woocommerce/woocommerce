(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[8331],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  SQ: () => (/* binding */ slot_fill_Fill),
  DX: () => (/* binding */ slot_fill_Slot)
});

// UNUSED EXPORTS: Provider, createSlotFill, useSlot

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/context.js
// @ts-nocheck

/**
 * WordPress dependencies
 */

const SlotFillContext = (0,react.createContext)({
  registerSlot: () => {},
  unregisterSlot: () => {},
  registerFill: () => {},
  unregisterFill: () => {},
  getSlot: () => {},
  getFills: () => {},
  subscribe: () => {}
});
/* harmony default export */ const context = (SlotFillContext);
//# sourceMappingURL=context.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/use-slot.js
// @ts-nocheck

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


/**
 * React hook returning the active slot given a name.
 *
 * @param {string} name Slot name.
 * @return {Object} Slot object.
 */

const useSlot = name => {
  const {
    getSlot,
    subscribe
  } = (0,react.useContext)(context);
  const [slot, setSlot] = (0,react.useState)(getSlot(name));
  (0,react.useEffect)(() => {
    setSlot(getSlot(name));
    const unsubscribe = subscribe(() => {
      setSlot(getSlot(name));
    });
    return unsubscribe;
  }, [name]);
  return slot;
};

/* harmony default export */ const use_slot = (useSlot);
//# sourceMappingURL=use-slot.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/fill.js


// @ts-nocheck

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */




function FillComponent(_ref) {
  let {
    name,
    children,
    registerFill,
    unregisterFill
  } = _ref;
  const slot = use_slot(name);
  const ref = (0,react.useRef)({
    name,
    children
  });
  (0,react.useLayoutEffect)(() => {
    registerFill(name, ref.current);
    return () => unregisterFill(name, ref.current);
  }, []);
  (0,react.useLayoutEffect)(() => {
    ref.current.children = children;

    if (slot) {
      slot.forceUpdate();
    }
  }, [children]);
  (0,react.useLayoutEffect)(() => {
    if (name === ref.current.name) {
      // Ignore initial effect.
      return;
    }

    unregisterFill(ref.current.name, ref.current);
    ref.current.name = name;
    registerFill(name, ref.current);
  }, [name]);

  if (!slot || !slot.node) {
    return null;
  } // If a function is passed as a child, provide it with the fillProps.


  if ((0,lodash.isFunction)(children)) {
    children = children(slot.props.fillProps);
  }

  return (0,react_dom.createPortal)(children, slot.node);
}

const Fill = props => (0,react.createElement)(context.Consumer, null, _ref2 => {
  let {
    registerFill,
    unregisterFill
  } = _ref2;
  return (0,react.createElement)(FillComponent, (0,esm_extends/* default */.A)({}, props, {
    registerFill: registerFill,
    unregisterFill: unregisterFill
  }));
});

/* harmony default export */ const fill = (Fill);
//# sourceMappingURL=fill.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/utils.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/slot.js


// @ts-nocheck

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



class SlotComponent extends react.Component {
  constructor() {
    super(...arguments);
    this.isUnmounted = false;
    this.bindNode = this.bindNode.bind(this);
  }

  componentDidMount() {
    const {
      registerSlot
    } = this.props;
    registerSlot(this.props.name, this);
  }

  componentWillUnmount() {
    const {
      unregisterSlot
    } = this.props;
    this.isUnmounted = true;
    unregisterSlot(this.props.name, this);
  }

  componentDidUpdate(prevProps) {
    const {
      name,
      unregisterSlot,
      registerSlot
    } = this.props;

    if (prevProps.name !== name) {
      unregisterSlot(prevProps.name);
      registerSlot(name, this);
    }
  }

  bindNode(node) {
    this.node = node;
  }

  forceUpdate() {
    if (this.isUnmounted) {
      return;
    }

    super.forceUpdate();
  }

  render() {
    const {
      children,
      name,
      fillProps = {},
      getFills
    } = this.props;
    const fills = (0,lodash.map)(getFills(name, this), fill => {
      const fillChildren = (0,lodash.isFunction)(fill.children) ? fill.children(fillProps) : fill.children;
      return react.Children.map(fillChildren, (child, childIndex) => {
        if (!child || (0,lodash.isString)(child)) {
          return child;
        }

        const childKey = child.key || childIndex;
        return (0,react.cloneElement)(child, {
          key: childKey
        });
      });
    }).filter( // In some cases fills are rendered only when some conditions apply.
    // This ensures that we only use non-empty fills when rendering, i.e.,
    // it allows us to render wrappers only when the fills are actually present.
    (0,lodash.negate)(utils/* isEmptyElement */.s));
    return (0,react.createElement)(react.Fragment, null, (0,lodash.isFunction)(children) ? children(fills) : fills);
  }

}

const Slot = props => (0,react.createElement)(context.Consumer, null, _ref => {
  let {
    registerSlot,
    unregisterSlot,
    getFills
  } = _ref;
  return (0,react.createElement)(SlotComponent, (0,esm_extends/* default */.A)({}, props, {
    registerSlot: registerSlot,
    unregisterSlot: unregisterSlot,
    getFills: getFills
  }));
});

/* harmony default export */ const slot = (Slot);
//# sourceMappingURL=slot.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js
/* provided dependency */ var process = __webpack_require__("../../node_modules/.pnpm/process@0.11.10/node_modules/process/browser.js");
// @ts-nocheck

/**
 * WordPress dependencies
 */


const slot_fill_context_SlotFillContext = (0,react.createContext)({
  slots: {},
  fills: {},
  registerSlot: () => {
    typeof process !== "undefined" && process.env && "production" !== "production" ? 0 : void 0;
  },
  updateSlot: () => {},
  unregisterSlot: () => {},
  registerFill: () => {},
  unregisterFill: () => {}
});
/* harmony default export */ const slot_fill_context = (slot_fill_context_SlotFillContext);
//# sourceMappingURL=slot-fill-context.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/use-slot.js
// @ts-nocheck

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


function use_slot_useSlot(name) {
  const registry = (0,react.useContext)(slot_fill_context);
  const slot = registry.slots[name] || {};
  const slotFills = registry.fills[name];
  const fills = (0,react.useMemo)(() => slotFills || [], [slotFills]);
  const updateSlot = (0,react.useCallback)(fillProps => {
    registry.updateSlot(name, fillProps);
  }, [name, registry.updateSlot]);
  const unregisterSlot = (0,react.useCallback)(slotRef => {
    registry.unregisterSlot(name, slotRef);
  }, [name, registry.unregisterSlot]);
  const registerFill = (0,react.useCallback)(fillRef => {
    registry.registerFill(name, fillRef);
  }, [name, registry.registerFill]);
  const unregisterFill = (0,react.useCallback)(fillRef => {
    registry.unregisterFill(name, fillRef);
  }, [name, registry.unregisterFill]);
  return { ...slot,
    updateSlot,
    unregisterSlot,
    fills,
    registerFill,
    unregisterFill
  };
}
//# sourceMappingURL=use-slot.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-element-c39617d8.browser.esm.js
var emotion_element_c39617d8_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-element-c39617d8.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+cache@11.11.0/node_modules/@emotion/cache/dist/emotion-cache.browser.esm.js + 7 modules
var emotion_cache_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+cache@11.11.0/node_modules/@emotion/cache/dist/emotion-cache.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/memize@1.1.0/node_modules/memize/index.js
var memize = __webpack_require__("../../node_modules/.pnpm/memize@1.1.0/node_modules/memize/index.js");
var memize_default = /*#__PURE__*/__webpack_require__.n(memize);
// EXTERNAL MODULE: ../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/v4.js + 4 modules
var v4 = __webpack_require__("../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/v4.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/style-provider/index.js

// @ts-nocheck

/**
 * External dependencies
 */




const uuidCache = new Set();
const memoizedCreateCacheWithContainer = memize_default()(container => {
  // Emotion only accepts alphabetical and hyphenated keys so we just strip the numbers from the UUID. It _should_ be fine.
  let key = v4/* default */.A().replace(/[0-9]/g, '');

  while (uuidCache.has(key)) {
    key = v4/* default */.A().replace(/[0-9]/g, '');
  }

  uuidCache.add(key);
  return (0,emotion_cache_browser_esm/* default */.A)({
    container,
    key
  });
});
function StyleProvider(_ref) {
  let {
    children,
    document
  } = _ref;

  if (!document) {
    return null;
  }

  const cache = memoizedCreateCacheWithContainer(document.head);
  return (0,react.createElement)(emotion_element_c39617d8_browser_esm.C, {
    value: cache
  }, children);
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/fill.js

// @ts-nocheck

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




function useForceUpdate() {
  const [, setState] = (0,react.useState)({});
  const mounted = (0,react.useRef)(true);
  (0,react.useEffect)(() => {
    return () => {
      mounted.current = false;
    };
  }, []);
  return () => {
    if (mounted.current) {
      setState({});
    }
  };
}

function fill_Fill(_ref) {
  let {
    name,
    children
  } = _ref;
  const slot = use_slot_useSlot(name);
  const ref = (0,react.useRef)({
    rerender: useForceUpdate()
  });
  (0,react.useEffect)(() => {
    // We register fills so we can keep track of their existance.
    // Some Slot implementations need to know if there're already fills
    // registered so they can choose to render themselves or not.
    slot.registerFill(ref);
    return () => {
      slot.unregisterFill(ref);
    };
  }, [slot.registerFill, slot.unregisterFill]);

  if (!slot.ref || !slot.ref.current) {
    return null;
  }

  if (typeof children === 'function') {
    children = children(slot.fillProps);
  } // When using a `Fill`, the `children` will be rendered in the document of the
  // `Slot`. This means that we need to wrap the `children` in a `StyleProvider`
  // to make sure we're referencing the right document/iframe (instead of the
  // context of the `Fill`'s parent).


  const wrappedChildren = (0,react.createElement)(StyleProvider, {
    document: slot.ref.current.ownerDocument
  }, children);
  return (0,react_dom.createPortal)(wrappedChildren, slot.ref.current);
}
//# sourceMappingURL=fill.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot.js


// @ts-nocheck

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function slot_Slot(_ref, forwardedRef) {
  let {
    name,
    fillProps = {},
    as: Component = 'div',
    ...props
  } = _ref;
  const registry = (0,react.useContext)(slot_fill_context);
  const ref = (0,react.useRef)();
  (0,react.useLayoutEffect)(() => {
    registry.registerSlot(name, ref, fillProps);
    return () => {
      registry.unregisterSlot(name, ref);
    }; // We are not including fillProps in the deps because we don't want to
    // unregister and register the slot whenever fillProps change, which would
    // cause the fill to be re-mounted. We are only considering the initial value
    // of fillProps.
  }, [registry.registerSlot, registry.unregisterSlot, name]); // fillProps may be an update that interacts with the layout, so we
  // useLayoutEffect.

  (0,react.useLayoutEffect)(() => {
    registry.updateSlot(name, fillProps);
  });
  return (0,react.createElement)(Component, (0,esm_extends/* default */.A)({
    ref: (0,use_merge_refs/* default */.A)([forwardedRef, ref])
  }, props));
}

/* harmony default export */ const bubbles_virtually_slot = ((0,react.forwardRef)(slot_Slot));
//# sourceMappingURL=slot.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@18.3.1_react@17.0.2__react-with-d_2lvt2m6o33cg7sz6nsmi7nf4ga/node_modules/@wordpress/components/build-module/slot-fill/index.js


// @ts-nocheck

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */








function slot_fill_Fill(props) {
  // We're adding both Fills here so they can register themselves before
  // their respective slot has been registered. Only the Fill that has a slot
  // will render. The other one will return null.
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(fill, props), (0,react.createElement)(fill_Fill, props));
}
const slot_fill_Slot = (0,react.forwardRef)((_ref, ref) => {
  let {
    bubblesVirtually,
    ...props
  } = _ref;

  if (bubblesVirtually) {
    return (0,react.createElement)(bubbles_virtually_slot, (0,esm_extends/* default */.A)({}, props, {
      ref: ref
    }));
  }

  return (0,react.createElement)(slot, props);
});
function Provider(_ref2) {
  let {
    children,
    ...props
  } = _ref2;
  return createElement(SlotFillProvider, props, createElement(BubblesVirtuallySlotFillProvider, null, children));
}
function createSlotFill(name) {
  const FillComponent = props => createElement(slot_fill_Fill, _extends({
    name: name
  }, props));

  FillComponent.displayName = name + 'Fill';

  const SlotComponent = props => createElement(slot_fill_Slot, _extends({
    name: name
  }, props));

  SlotComponent.displayName = name + 'Slot';
  SlotComponent.__unstableName = name;
  return {
    Fill: FillComponent,
    Slot: SlotComponent
  };
}

//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/date-to-iso-string.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var padStart = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-pad.js").start);

var $RangeError = RangeError;
var $isFinite = isFinite;
var abs = Math.abs;
var DatePrototype = Date.prototype;
var nativeDateToISOString = DatePrototype.toISOString;
var thisTimeValue = uncurryThis(DatePrototype.getTime);
var getUTCDate = uncurryThis(DatePrototype.getUTCDate);
var getUTCFullYear = uncurryThis(DatePrototype.getUTCFullYear);
var getUTCHours = uncurryThis(DatePrototype.getUTCHours);
var getUTCMilliseconds = uncurryThis(DatePrototype.getUTCMilliseconds);
var getUTCMinutes = uncurryThis(DatePrototype.getUTCMinutes);
var getUTCMonth = uncurryThis(DatePrototype.getUTCMonth);
var getUTCSeconds = uncurryThis(DatePrototype.getUTCSeconds);

// `Date.prototype.toISOString` method implementation
// https://tc39.es/ecma262/#sec-date.prototype.toisostring
// PhantomJS / old WebKit fails here:
module.exports = (fails(function () {
  return nativeDateToISOString.call(new Date(-5e13 - 1)) !== '0385-07-25T07:06:39.999Z';
}) || !fails(function () {
  nativeDateToISOString.call(new Date(NaN));
})) ? function toISOString() {
  if (!$isFinite(thisTimeValue(this))) throw new $RangeError('Invalid time value');
  var date = this;
  var year = getUTCFullYear(date);
  var milliseconds = getUTCMilliseconds(date);
  var sign = year < 0 ? '-' : year > 9999 ? '+' : '';
  return sign + padStart(abs(year), sign ? 6 : 4, 0) +
    '-' + padStart(getUTCMonth(date) + 1, 2, 0) +
    '-' + padStart(getUTCDate(date), 2, 0) +
    'T' + padStart(getUTCHours(date), 2, 0) +
    ':' + padStart(getUTCMinutes(date), 2, 0) +
    ':' + padStart(getUTCSeconds(date), 2, 0) +
    '.' + padStart(milliseconds, 3, 0) +
    'Z';
} : nativeDateToISOString;


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-pad.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

// https://github.com/tc39/proposal-string-pad-start-end
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var toLength = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-length.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var $repeat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-repeat.js");
var requireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/require-object-coercible.js");

var repeat = uncurryThis($repeat);
var stringSlice = uncurryThis(''.slice);
var ceil = Math.ceil;

// `String.prototype.{ padStart, padEnd }` methods implementation
var createMethod = function (IS_END) {
  return function ($this, maxLength, fillString) {
    var S = toString(requireObjectCoercible($this));
    var intMaxLength = toLength(maxLength);
    var stringLength = S.length;
    var fillStr = fillString === undefined ? ' ' : toString(fillString);
    var fillLen, stringFiller;
    if (intMaxLength <= stringLength || fillStr === '') return S;
    fillLen = intMaxLength - stringLength;
    stringFiller = repeat(fillStr, ceil(fillLen / fillStr.length));
    if (stringFiller.length > fillLen) stringFiller = stringSlice(stringFiller, 0, fillLen);
    return IS_END ? S + stringFiller : stringFiller + S;
  };
};

module.exports = {
  // `String.prototype.padStart` method
  // https://tc39.es/ecma262/#sec-string.prototype.padstart
  start: createMethod(false),
  // `String.prototype.padEnd` method
  // https://tc39.es/ecma262/#sec-string.prototype.padend
  end: createMethod(true)
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-repeat.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var toIntegerOrInfinity = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-integer-or-infinity.js");
var toString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-string.js");
var requireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/require-object-coercible.js");

var $RangeError = RangeError;

// `String.prototype.repeat` method implementation
// https://tc39.es/ecma262/#sec-string.prototype.repeat
module.exports = function repeat(count) {
  var str = toString(requireObjectCoercible(this));
  var result = '';
  var n = toIntegerOrInfinity(count);
  if (n < 0 || n === Infinity) throw new $RangeError('Wrong number of repetitions');
  for (;n > 0; (n >>>= 1) && (str += str)) if (n & 1) result += str;
  return result;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-trim-forced.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var PROPER_FUNCTION_NAME = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-name.js").PROPER);
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var whitespaces = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/whitespaces.js");

var non = '\u200B\u0085\u180E';

// check that a method works with the correct list
// of whitespaces and has a correct name
module.exports = function (METHOD_NAME) {
  return fails(function () {
    return !!whitespaces[METHOD_NAME]()
      || non[METHOD_NAME]() !== non
      || (PROPER_FUNCTION_NAME && whitespaces[METHOD_NAME].name !== METHOD_NAME);
  });
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-iso-string.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var toISOString = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/date-to-iso-string.js");

// `Date.prototype.toISOString` method
// https://tc39.es/ecma262/#sec-date.prototype.toisostring
// PhantomJS / old WebKit has a broken implementations
$({ target: 'Date', proto: true, forced: Date.prototype.toISOString !== toISOString }, {
  toISOString: toISOString
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $trim = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-trim.js").trim);
var forcedStringTrimMethod = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/string-trim-forced.js");

// `String.prototype.trim` method
// https://tc39.es/ecma262/#sec-string.prototype.trim
$({ target: 'String', proto: true, forced: forcedStringTrimMethod('trim') }, {
  trim: function trim() {
    return $trim(this);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/notice-outline.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-notice-outline",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 4c4.411 0 8 3.589 8 8s-3.589 8-8 8-8-3.589-8-8 3.589-8 8-8m0-2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 13h-2v2h2v-2zm-2-2h2l.5-6h-3l.5 6z"})))}


/***/ }),

/***/ "../../node_modules/.pnpm/string-similarity@4.0.4/node_modules/string-similarity/src/index.js":
/***/ ((module) => {

module.exports = {
	compareTwoStrings:compareTwoStrings,
	findBestMatch:findBestMatch
};

function compareTwoStrings(first, second) {
	first = first.replace(/\s+/g, '')
	second = second.replace(/\s+/g, '')

	if (first === second) return 1; // identical or empty
	if (first.length < 2 || second.length < 2) return 0; // if either is a 0-letter or 1-letter string

	let firstBigrams = new Map();
	for (let i = 0; i < first.length - 1; i++) {
		const bigram = first.substring(i, i + 2);
		const count = firstBigrams.has(bigram)
			? firstBigrams.get(bigram) + 1
			: 1;

		firstBigrams.set(bigram, count);
	};

	let intersectionSize = 0;
	for (let i = 0; i < second.length - 1; i++) {
		const bigram = second.substring(i, i + 2);
		const count = firstBigrams.has(bigram)
			? firstBigrams.get(bigram)
			: 0;

		if (count > 0) {
			firstBigrams.set(bigram, count - 1);
			intersectionSize++;
		}
	}

	return (2.0 * intersectionSize) / (first.length + second.length - 2);
}

function findBestMatch(mainString, targetStrings) {
	if (!areArgsValid(mainString, targetStrings)) throw new Error('Bad arguments: First argument should be a string, second should be an array of strings');
	
	const ratings = [];
	let bestMatchIndex = 0;

	for (let i = 0; i < targetStrings.length; i++) {
		const currentTargetString = targetStrings[i];
		const currentRating = compareTwoStrings(mainString, currentTargetString)
		ratings.push({target: currentTargetString, rating: currentRating})
		if (currentRating > ratings[bestMatchIndex].rating) {
			bestMatchIndex = i
		}
	}
	
	
	const bestMatch = ratings[bestMatchIndex]
	
	return { ratings: ratings, bestMatch: bestMatch, bestMatchIndex: bestMatchIndex };
}

function areArgsValid(mainString, targetStrings) {
	if (typeof mainString !== 'string') return false;
	if (!Array.isArray(targetStrings)) return false;
	if (!targetStrings.length) return false;
	if (targetStrings.find( function (s) { return typeof s !== 'string'})) return false;
	return true;
}


/***/ })

}]);