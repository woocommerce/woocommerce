"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7274],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   R: () => (/* binding */ FlexContext),
/* harmony export */   a: () => (/* binding */ useFlexContext)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * WordPress dependencies
 */

const FlexContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)({
  flexItemDisplay: undefined
});
const useFlexContext = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(FlexContext);
//# sourceMappingURL=context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ flex_component)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.41.0/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.41.0/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/use-responsive-value.js
/**
 * WordPress dependencies
 */

const breakpoints = ['40em', '52em', '64em'];
const useBreakpointIndex = function () {
  let options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  const {
    defaultIndex = 0
  } = options;

  if (typeof defaultIndex !== 'number') {
    throw new TypeError(`Default breakpoint index should be a number. Got: ${defaultIndex}, ${typeof defaultIndex}`);
  } else if (defaultIndex < 0 || defaultIndex > breakpoints.length - 1) {
    throw new RangeError(`Default breakpoint index out of range. Theme has ${breakpoints.length} breakpoints, got index ${defaultIndex}`);
  }

  const [value, setValue] = (0,react.useState)(defaultIndex);
  (0,react.useEffect)(() => {
    const getIndex = () => breakpoints.filter(bp => {
      return typeof window !== 'undefined' ? window.matchMedia(`screen and (min-width: ${bp})`).matches : false;
    }).length;

    const onResize = () => {
      const newValue = getIndex();

      if (value !== newValue) {
        setValue(newValue);
      }
    };

    onResize();

    if (typeof window !== 'undefined') {
      window.addEventListener('resize', onResize);
    }

    return () => {
      if (typeof window !== 'undefined') {
        window.removeEventListener('resize', onResize);
      }
    };
  }, [value]);
  return value;
};
function useResponsiveValue(values) {
  let options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  const index = useBreakpointIndex(options); // Allow calling the function with a "normal" value without having to check on the outside.

  if (!Array.isArray(values) && typeof values !== 'function') return values;
  const array = values || [];
  /* eslint-disable jsdoc/no-undefined-types */

  return (
    /** @type {T[]} */
    array[
    /* eslint-enable jsdoc/no-undefined-types */
    index >= array.length ? array.length - 1 : index]
  );
}
//# sourceMappingURL=use-responsive-value.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/styles.js
var styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/styles.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/rtl.js
var rtl = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/rtl.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/hook.js
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
 *
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').FlexProps, 'div'>} props
 * @return {import('../../ui/context').WordPressComponentProps<import('../types').FlexProps, 'div'>} Props with the deprecated props removed.
 */

function useDeprecatedProps(_ref) {
  let {
    isReversed,
    ...otherProps
  } = _ref;

  if (typeof isReversed !== 'undefined') {
    (0,build_module/* default */.A)('Flex isReversed', {
      alternative: 'Flex direction="row-reverse" or "column-reverse"',
      since: '5.9'
    });
    return { ...otherProps,
      direction: isReversed ? 'row-reverse' : 'row'
    };
  }

  return otherProps;
}
/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').FlexProps, 'div'>} props
 */


function useFlex(props) {
  const {
    align = 'center',
    className,
    direction: directionProp = 'row',
    expanded = true,
    gap = 2,
    justify = 'space-between',
    wrap = false,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(useDeprecatedProps(props), 'Flex');
  const directionAsArray = Array.isArray(directionProp) ? directionProp : [directionProp];
  const direction = useResponsiveValue(directionAsArray);
  const isColumn = typeof direction === 'string' && !!direction.includes('column');
  const isReverse = typeof direction === 'string' && direction.includes('reverse');
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    const sx = {};
    sx.Base = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
      alignItems: isColumn ? 'normal' : align,
      flexDirection: direction,
      flexWrap: wrap ? 'wrap' : undefined,
      justifyContent: justify,
      height: isColumn && expanded ? '100%' : undefined,
      width: !isColumn && expanded ? '100%' : undefined,
      marginBottom: wrap ? `calc(${(0,space/* space */.x)(gap)} * -1)` : undefined
    },  true ? "" : 0,  true ? "" : 0);
    /**
     * Workaround to optimize DOM rendering.
     * We'll enhance alignment with naive parent flex assumptions.
     *
     * Trade-off:
     * Far less DOM less. However, UI rendering is not as reliable.
     */

    sx.Items = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)(">*+*:not( marquee ){margin-top:", isColumn ? (0,space/* space */.x)(gap) : undefined, ";", (0,rtl/* rtl */.h)({
      marginLeft: !isColumn && !isReverse ? (0,space/* space */.x)(gap) : undefined,
      marginRight: !isColumn && isReverse ? (0,space/* space */.x)(gap) : undefined
    })(), ";}" + ( true ? "" : 0),  true ? "" : 0);
    sx.WrapItems = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)(">*:not( marquee ){margin-bottom:", (0,space/* space */.x)(gap), ";", (0,rtl/* rtl */.h)({
      marginLeft: !isColumn && isReverse ? (0,space/* space */.x)(gap) : undefined,
      marginRight: !isColumn && !isReverse ? (0,space/* space */.x)(gap) : undefined
    })(), ";}>*:last-child:not( marquee ){", (0,rtl/* rtl */.h)({
      marginLeft: !isColumn && isReverse ? 0 : undefined,
      marginRight: !isColumn && !isReverse ? 0 : undefined
    })(), ";}" + ( true ? "" : 0),  true ? "" : 0);
    return cx(styles/* Flex */.so, sx.Base, wrap ? sx.WrapItems : sx.Items, isColumn ? styles/* ItemsColumn */.Z2 : styles/* ItemsRow */.RI, className);
  }, [align, className, cx, direction, expanded, gap, isColumn, isReverse, justify, wrap, rtl/* rtl */.h.watch()]);
  return { ...otherProps,
    className: classes,
    isColumn
  };
}
//# sourceMappingURL=hook.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/context.js
var context = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/view/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js



/**
 * Internal dependencies
 */




/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').FlexProps, 'div'>} props
 * @param {import('react').ForwardedRef<any>}                                                       forwardedRef
 */

function Flex(props, forwardedRef) {
  const {
    children,
    isColumn,
    ...otherProps
  } = useFlex(props);
  return (0,react.createElement)(context/* FlexContext */.R.Provider, {
    value: {
      flexItemDisplay: isColumn ? 'block' : undefined
    }
  }, (0,react.createElement)(component/* default */.A, (0,esm_extends/* default */.A)({}, otherProps, {
    ref: forwardedRef
  }), children));
}
/**
 * `Flex` is a primitive layout component that adaptively aligns child content
 * horizontally or vertically. `Flex` powers components like `HStack` and
 * `VStack`.
 *
 * `Flex` is used with any of it's two sub-components, `FlexItem` and `FlexBlock`.
 *
 * @example
 * ```jsx
 * import {
 * 	__experimentalFlex as Flex,
 * 	__experimentalFlexBlock as FlexBlock,
 * 	__experimentalFlexItem as FlexItem,
 * 	__experimentalText as Text
 * } from `@wordpress/components`;
 *
 * function Example() {
 * 	return (
 * 		<Flex>
 * 			<FlexItem>
 * 				<Text>Code</Text>
 * 			</FlexItem>
 * 			<FlexBlock>
 * 				<Text>Poetry</Text>
 * 			</FlexBlock>
 * 		</Flex>
 * 	);
 * }
 * ```
 *
 */


const ConnectedFlex = (0,context_connect/* contextConnect */.KZ)(Flex, 'Flex');
/* harmony default export */ const flex_component = (ConnectedFlex);
//# sourceMappingURL=component.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/styles.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   RI: () => (/* binding */ ItemsRow),
/* harmony export */   Z2: () => (/* binding */ ItemsColumn),
/* harmony export */   om: () => (/* binding */ block),
/* harmony export */   q7: () => (/* binding */ Item),
/* harmony export */   so: () => (/* binding */ Flex)
/* harmony export */ });
function _EMOTION_STRINGIFIED_CSS_ERROR__() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }

/**
 * External dependencies
 */

const Flex =  true ? {
  name: "zjik7",
  styles: "display:flex"
} : 0;
const Item =  true ? {
  name: "qgaee5",
  styles: "display:block;max-height:100%;max-width:100%;min-height:0;min-width:0"
} : 0;
const block =  true ? {
  name: "82a6rk",
  styles: "flex:1"
} : 0;
/**
 * Workaround to optimize DOM rendering.
 * We'll enhance alignment with naive parent flex assumptions.
 *
 * Trade-off:
 * Far less DOM less. However, UI rendering is not as reliable.
 */

/**
 * Improves stability of width/height rendering.
 * https://github.com/ItsJonQ/g2/pull/149
 */

const ItemsColumn =  true ? {
  name: "13nosa1",
  styles: ">*{min-height:0;}"
} : 0;
const ItemsRow =  true ? {
  name: "1pwxzk4",
  styles: ">*{min-width:0;}"
} : 0;
//# sourceMappingURL=styles.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/rtl.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ rtl)
/* harmony export */ });
/* unused harmony export convertLTRToRTL */
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


const LOWER_LEFT_REGEXP = new RegExp(/-left/g);
const LOWER_RIGHT_REGEXP = new RegExp(/-right/g);
const UPPER_LEFT_REGEXP = new RegExp(/Left/g);
const UPPER_RIGHT_REGEXP = new RegExp(/Right/g);
/**
 * Flips a CSS property from left <-> right.
 *
 * @param {string} key The CSS property name.
 *
 * @return {string} The flipped CSS property name, if applicable.
 */

function getConvertedKey(key) {
  if (key === 'left') {
    return 'right';
  }

  if (key === 'right') {
    return 'left';
  }

  if (LOWER_LEFT_REGEXP.test(key)) {
    return key.replace(LOWER_LEFT_REGEXP, '-right');
  }

  if (LOWER_RIGHT_REGEXP.test(key)) {
    return key.replace(LOWER_RIGHT_REGEXP, '-left');
  }

  if (UPPER_LEFT_REGEXP.test(key)) {
    return key.replace(UPPER_LEFT_REGEXP, 'Right');
  }

  if (UPPER_RIGHT_REGEXP.test(key)) {
    return key.replace(UPPER_RIGHT_REGEXP, 'Left');
  }

  return key;
}
/**
 * An incredibly basic ltr -> rtl converter for style properties
 *
 * @param {import('react').CSSProperties} ltrStyles
 *
 * @return {import('react').CSSProperties} Converted ltr -> rtl styles
 */


const convertLTRToRTL = function () {
  let ltrStyles = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  return (0,lodash__WEBPACK_IMPORTED_MODULE_0__.mapKeys)(ltrStyles, (_value, key) => getConvertedKey(key));
};
/**
 * A higher-order function that create an incredibly basic ltr -> rtl style converter for CSS objects.
 *
 * @param {import('react').CSSProperties} ltrStyles   Ltr styles. Converts and renders from ltr -> rtl styles, if applicable.
 * @param {import('react').CSSProperties} [rtlStyles] Rtl styles. Renders if provided.
 *
 * @return {() => import('@emotion/react').SerializedStyles} A function to output CSS styles for Emotion's renderer
 */

function rtl() {
  let ltrStyles = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  let rtlStyles = arguments.length > 1 ? arguments[1] : undefined;
  return () => {
    if (rtlStyles) {
      // @ts-ignore: `css` types are wrong, it can accept an object: https://emotion.sh/docs/object-styles#with-css
      return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__/* .isRTL */ .V8)() ? /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_2__/* .css */ .AH)(rtlStyles,  true ? "" : 0) : /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_2__/* .css */ .AH)(ltrStyles,  true ? "" : 0);
    } // @ts-ignore: `css` types are wrong, it can accept an object: https://emotion.sh/docs/object-styles#with-css


    return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__/* .isRTL */ .V8)() ? /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_2__/* .css */ .AH)(convertLTRToRTL(ltrStyles),  true ? "" : 0) : /*#__PURE__*/(0,_emotion_react__WEBPACK_IMPORTED_MODULE_2__/* .css */ .AH)(ltrStyles,  true ? "" : 0);
  };
}
/**
 * Call this in the `useMemo` dependency array to ensure that subsequent renders will
 * cause rtl styles to update based on the `isRTL` return value even if all other dependencies
 * remain the same.
 *
 * @example
 * const styles = useMemo( () => {
 *   return css`
 *     ${ rtl( { marginRight: '10px' } ) }
 *   `;
 * }, [ rtl.watch() ] );
 */

rtl.watch = () => (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__/* .isRTL */ .V8)();
//# sourceMappingURL=rtl.js.map

/***/ })

}]);