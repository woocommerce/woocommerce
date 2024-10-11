(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4839],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export Button */
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.41.0/node_modules/@wordpress/deprecated/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var _tooltip__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js");
/* harmony import */ var _icon__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js");
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/visually-hidden/component.js");


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




const disabledEventsOnDisabledButton = ['onMouseDown', 'onClick'];

function useDeprecatedProps(_ref) {
  let {
    isDefault,
    isPrimary,
    isSecondary,
    isTertiary,
    isLink,
    variant,
    ...otherProps
  } = _ref;
  let computedVariant = variant;

  if (isPrimary) {
    var _computedVariant;

    (_computedVariant = computedVariant) !== null && _computedVariant !== void 0 ? _computedVariant : computedVariant = 'primary';
  }

  if (isTertiary) {
    var _computedVariant2;

    (_computedVariant2 = computedVariant) !== null && _computedVariant2 !== void 0 ? _computedVariant2 : computedVariant = 'tertiary';
  }

  if (isSecondary) {
    var _computedVariant3;

    (_computedVariant3 = computedVariant) !== null && _computedVariant3 !== void 0 ? _computedVariant3 : computedVariant = 'secondary';
  }

  if (isDefault) {
    var _computedVariant4;

    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('Button isDefault prop', {
      since: '5.4',
      alternative: 'variant="secondary"',
      version: '6.2'
    });
    (_computedVariant4 = computedVariant) !== null && _computedVariant4 !== void 0 ? _computedVariant4 : computedVariant = 'secondary';
  }

  if (isLink) {
    var _computedVariant5;

    (_computedVariant5 = computedVariant) !== null && _computedVariant5 !== void 0 ? _computedVariant5 : computedVariant = 'link';
  }

  return { ...otherProps,
    variant: computedVariant
  };
}

function Button(props, ref) {
  const {
    href,
    target,
    isSmall,
    isPressed,
    isBusy,
    isDestructive,
    className,
    disabled,
    icon,
    iconPosition = 'left',
    iconSize,
    showTooltip,
    tooltipPosition,
    shortcut,
    label,
    children,
    text,
    variant,
    __experimentalIsFocusable: isFocusable,
    describedBy,
    ...additionalProps
  } = useDeprecatedProps(props);
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Button, 'components-button__description');
  const classes = classnames__WEBPACK_IMPORTED_MODULE_0___default()('components-button', className, {
    'is-secondary': variant === 'secondary',
    'is-primary': variant === 'primary',
    'is-small': isSmall,
    'is-tertiary': variant === 'tertiary',
    'is-pressed': isPressed,
    'is-busy': isBusy,
    'is-link': variant === 'link',
    'is-destructive': isDestructive,
    'has-text': !!icon && !!children,
    'has-icon': !!icon
  });
  const trulyDisabled = disabled && !isFocusable;
  const Tag = href !== undefined && !trulyDisabled ? 'a' : 'button';
  const tagProps = Tag === 'a' ? {
    href,
    target
  } : {
    type: 'button',
    disabled: trulyDisabled,
    'aria-pressed': isPressed
  };

  if (disabled && isFocusable) {
    // In this case, the button will be disabled, but still focusable and
    // perceivable by screen reader users.
    tagProps['aria-disabled'] = true;

    for (const disabledEvent of disabledEventsOnDisabledButton) {
      additionalProps[disabledEvent] = event => {
        event.stopPropagation();
        event.preventDefault();
      };
    }
  } // Should show the tooltip if...


  const shouldShowTooltip = !trulyDisabled && ( // An explicit tooltip is passed or...
  showTooltip && label || // There's a shortcut or...
  shortcut || // There's a label and...
  !!label && ( // The children are empty and...
  !children || (0,lodash__WEBPACK_IMPORTED_MODULE_1__.isArray)(children) && !children.length) && // The tooltip is not explicitly disabled.
  false !== showTooltip);
  const descriptionId = describedBy ? instanceId : null;
  const describedById = additionalProps['aria-describedby'] || descriptionId;
  const element = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(Tag, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)({}, tagProps, additionalProps, {
    className: classes,
    "aria-label": additionalProps['aria-label'] || label,
    "aria-describedby": describedById,
    ref: ref
  }), icon && iconPosition === 'left' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_icon__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    icon: icon,
    size: iconSize
  }), text && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Fragment, null, text), icon && iconPosition === 'right' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_icon__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    icon: icon,
    size: iconSize
  }), children);

  if (!shouldShowTooltip) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Fragment, null, element, describedBy && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_visually_hidden__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)("span", {
      id: descriptionId
    }, describedBy)));
  }

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_tooltip__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
    text: describedBy ? describedBy : label,
    shortcut: shortcut,
    position: tooltipPosition
  }, element), describedBy && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_visually_hidden__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)("span", {
    id: descriptionId
  }, describedBy)));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.forwardRef)(Button));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ icon)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@3.4.1/node_modules/@wordpress/primitives/build-module/svg/index.js
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@3.4.1/node_modules/@wordpress/primitives/build-module/svg/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dashicon/index.js



/**
 * @typedef OwnProps
 *
 * @property {import('./types').IconKey} icon        Icon name
 * @property {string}                    [className] Class name
 */

/** @typedef {import('react').ComponentPropsWithoutRef<'span'> & OwnProps} Props */

/**
 * @param {Props} props
 * @return {JSX.Element} Element
 */
function Dashicon(_ref) {
  let {
    icon,
    className,
    ...extraProps
  } = _ref;
  const iconClass = ['dashicon', 'dashicons', 'dashicons-' + icon, className].filter(Boolean).join(' ');
  return (0,react.createElement)("span", (0,esm_extends/* default */.A)({
    className: iconClass
  }, extraProps));
}

/* harmony default export */ const dashicon = (Dashicon);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js


/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function Icon(_ref) {
  let {
    icon = null,
    size = 24,
    ...additionalProps
  } = _ref;

  if ('string' === typeof icon) {
    return (0,react.createElement)(dashicon, (0,esm_extends/* default */.A)({
      icon: icon
    }, additionalProps));
  }

  if ((0,react.isValidElement)(icon) && dashicon === icon.type) {
    return (0,react.cloneElement)(icon, { ...additionalProps
    });
  }

  if ('function' === typeof icon) {
    if (icon.prototype instanceof react.Component) {
      return (0,react.createElement)(icon, {
        size,
        ...additionalProps
      });
    }

    return icon({
      size,
      ...additionalProps
    });
  }

  if (icon && (icon.type === 'svg' || icon.type === svg/* SVG */.t4)) {
    const appliedProps = {
      width: size,
      height: size,
      ...icon.props,
      ...additionalProps
    };
    return (0,react.createElement)(svg/* SVG */.t4, appliedProps);
  }

  if ((0,react.isValidElement)(icon)) {
    return (0,react.cloneElement)(icon, {
      // @ts-ignore Just forwarding the size prop along
      size,
      ...additionalProps
    });
  }

  return icon;
}

/* harmony default export */ const icon = (Icon);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ popover)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/utils/assert-is-defined.js
function assertIsDefined(val, name) {
  if (false) {}
}
//# sourceMappingURL=assert-is-defined.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/dom/get-rectangle-from-range.js
/**
 * Internal dependencies
 */

/**
 * Get the rectangle of a given Range.
 *
 * @param {Range} range The range.
 *
 * @return {DOMRect} The rectangle.
 */

function getRectangleFromRange(range) {
  // For uncollapsed ranges, get the rectangle that bounds the contents of the
  // range; this a rectangle enclosing the union of the bounding rectangles
  // for all the elements in the range.
  if (!range.collapsed) {
    const rects = Array.from(range.getClientRects()); // If there's just a single rect, return it.

    if (rects.length === 1) {
      return rects[0];
    } // Ignore tiny selection at the edge of a range.


    const filteredRects = rects.filter(_ref => {
      let {
        width
      } = _ref;
      return width > 1;
    }); // If it's full of tiny selections, return browser default.

    if (filteredRects.length === 0) {
      return range.getBoundingClientRect();
    }

    if (filteredRects.length === 1) {
      return filteredRects[0];
    }

    let {
      top: furthestTop,
      bottom: furthestBottom,
      left: furthestLeft,
      right: furthestRight
    } = filteredRects[0];

    for (const {
      top,
      bottom,
      left,
      right
    } of filteredRects) {
      if (top < furthestTop) furthestTop = top;
      if (bottom > furthestBottom) furthestBottom = bottom;
      if (left < furthestLeft) furthestLeft = left;
      if (right > furthestRight) furthestRight = right;
    }

    return new window.DOMRect(furthestLeft, furthestTop, furthestRight - furthestLeft, furthestBottom - furthestTop);
  }

  const {
    startContainer
  } = range;
  const {
    ownerDocument
  } = startContainer; // Correct invalid "BR" ranges. The cannot contain any children.

  if (startContainer.nodeName === 'BR') {
    const {
      parentNode
    } = startContainer;
    assertIsDefined(parentNode, 'parentNode');
    const index =
    /** @type {Node[]} */
    Array.from(parentNode.childNodes).indexOf(startContainer);
    assertIsDefined(ownerDocument, 'ownerDocument');
    range = ownerDocument.createRange();
    range.setStart(parentNode, index);
    range.setEnd(parentNode, index);
  }

  let rect = range.getClientRects()[0]; // If the collapsed range starts (and therefore ends) at an element node,
  // `getClientRects` can be empty in some browsers. This can be resolved
  // by adding a temporary text node with zero-width space to the range.
  //
  // See: https://stackoverflow.com/a/6847328/995445

  if (!rect) {
    assertIsDefined(ownerDocument, 'ownerDocument');
    const padNode = ownerDocument.createTextNode('\u200b'); // Do not modify the live range.

    range = range.cloneRange();
    range.insertNode(padNode);
    rect = range.getClientRects()[0];
    assertIsDefined(padNode.parentNode, 'padNode.parentNode');
    padNode.parentNode.removeChild(padNode);
  }

  return rect;
}
//# sourceMappingURL=get-rectangle-from-range.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-media-query/index.js
var use_media_query = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-media-query/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


/**
 * @typedef {"huge" | "wide" | "large" | "medium" | "small" | "mobile"} WPBreakpoint
 */

/**
 * Hash of breakpoint names with pixel width at which it becomes effective.
 *
 * @see _breakpoints.scss
 *
 * @type {Record<WPBreakpoint, number>}
 */

const BREAKPOINTS = {
  huge: 1440,
  wide: 1280,
  large: 960,
  medium: 782,
  small: 600,
  mobile: 480
};
/**
 * @typedef {">=" | "<"} WPViewportOperator
 */

/**
 * Object mapping media query operators to the condition to be used.
 *
 * @type {Record<WPViewportOperator, string>}
 */

const CONDITIONS = {
  '>=': 'min-width',
  '<': 'max-width'
};
/**
 * Object mapping media query operators to a function that given a breakpointValue and a width evaluates if the operator matches the values.
 *
 * @type {Record<WPViewportOperator, (breakpointValue: number, width: number) => boolean>}
 */

const OPERATOR_EVALUATORS = {
  '>=': (breakpointValue, width) => width >= breakpointValue,
  '<': (breakpointValue, width) => width < breakpointValue
};
const ViewportMatchWidthContext = (0,react.createContext)(
/** @type {null | number} */
null);
/**
 * Returns true if the viewport matches the given query, or false otherwise.
 *
 * @param {WPBreakpoint}       breakpoint      Breakpoint size name.
 * @param {WPViewportOperator} [operator=">="] Viewport operator.
 *
 * @example
 *
 * ```js
 * useViewportMatch( 'huge', '<' );
 * useViewportMatch( 'medium' );
 * ```
 *
 * @return {boolean} Whether viewport matches query.
 */

const useViewportMatch = function (breakpoint) {
  let operator = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '>=';
  const simulatedWidth = (0,react.useContext)(ViewportMatchWidthContext);
  const mediaQuery = !simulatedWidth && `(${CONDITIONS[operator]}: ${BREAKPOINTS[breakpoint]}px)`;
  const mediaQueryResult = (0,use_media_query/* default */.A)(mediaQuery || undefined);

  if (simulatedWidth) {
    return OPERATOR_EVALUATORS[operator](BREAKPOINTS[breakpoint], simulatedWidth);
  }

  return mediaQueryResult;
};

useViewportMatch.__experimentalWidthProvider = ViewportMatchWidthContext.Provider;
/* harmony default export */ const use_viewport_match = (useViewportMatch);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-resize-aware@3.1.1_react@17.0.2/node_modules/react-resize-aware/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/react-resize-aware@3.1.1_react@17.0.2/node_modules/react-resize-aware/dist/index.js");
var dist_default = /*#__PURE__*/__webpack_require__.n(dist);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-resize-observer/index.js
/**
 * External dependencies
 */

/**
 * Hook which allows to listen the resize event of any target element when it changes sizes.
 * _Note: `useResizeObserver` will report `null` until after first render_
 *
 * Simply a re-export of `react-resize-aware` so refer to its documentation <https://github.com/FezVrasta/react-resize-aware>
 * for more details.
 *
 * @see https://github.com/FezVrasta/react-resize-aware
 *
 * @example
 *
 * ```js
 * const App = () => {
 * 	const [ resizeListener, sizes ] = useResizeObserver();
 *
 * 	return (
 * 		<div>
 * 			{ resizeListener }
 * 			Your content here
 * 		</div>
 * 	);
 * };
 * ```
 *
 */

/* harmony default export */ const use_resize_observer = ((dist_default()));
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-constrained-tabbing/index.js
var use_constrained_tabbing = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-constrained-tabbing/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-on-mount/index.js
var use_focus_on_mount = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-on-mount/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-return/index.js
var use_focus_return = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-return/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-outside/index.js
var use_focus_outside = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-outside/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-dialog/index.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */






/* eslint-disable jsdoc/valid-types */

/**
 * @typedef DialogOptions
 * @property {Parameters<useFocusOnMount>[0]} focusOnMount Focus on mount arguments.
 * @property {() => void}                     onClose      Function to call when the dialog is closed.
 */

/* eslint-enable jsdoc/valid-types */

/**
 * Returns a ref and props to apply to a dialog wrapper to enable the following behaviors:
 *  - constrained tabbing.
 *  - focus on mount.
 *  - return focus on unmount.
 *  - focus outside.
 *
 * @param {DialogOptions} options Dialog Options.
 */

function useDialog(options) {
  /**
   * @type {import('react').MutableRefObject<DialogOptions | undefined>}
   */
  const currentOptions = (0,react.useRef)();
  (0,react.useEffect)(() => {
    currentOptions.current = options;
  }, Object.values(options));
  const constrainedTabbingRef = (0,use_constrained_tabbing/* default */.A)();
  const focusOnMountRef = (0,use_focus_on_mount/* default */.A)(options.focusOnMount);
  const focusReturnRef = (0,use_focus_return/* default */.A)();
  const focusOutsideProps = (0,use_focus_outside/* default */.A)(event => {
    var _currentOptions$curre, _currentOptions$curre2;

    // This unstable prop  is here only to manage backward compatibility
    // for the Popover component otherwise, the onClose should be enough.
    // @ts-ignore unstable property
    if ((_currentOptions$curre = currentOptions.current) !== null && _currentOptions$curre !== void 0 && _currentOptions$curre.__unstableOnClose) {
      // @ts-ignore unstable property
      currentOptions.current.__unstableOnClose('focus-outside', event);
    } else if ((_currentOptions$curre2 = currentOptions.current) !== null && _currentOptions$curre2 !== void 0 && _currentOptions$curre2.onClose) {
      currentOptions.current.onClose();
    }
  });
  const closeOnEscapeRef = (0,react.useCallback)(node => {
    if (!node) {
      return;
    }

    node.addEventListener('keydown', (
    /** @type {KeyboardEvent} */
    event) => {
      var _currentOptions$curre3;

      // Close on escape.
      if (event.keyCode === build_module/* ESCAPE */._f && !event.defaultPrevented && (_currentOptions$curre3 = currentOptions.current) !== null && _currentOptions$curre3 !== void 0 && _currentOptions$curre3.onClose) {
        event.preventDefault();
        currentOptions.current.onClose();
      }
    });
  }, []);
  return [(0,use_merge_refs/* default */.A)([options.focusOnMount !== false ? constrainedTabbingRef : null, options.focusOnMount !== false ? focusReturnRef : null, options.focusOnMount !== false ? focusOnMountRef : null, closeOnEscapeRef]), { ...focusOutsideProps,
    tabIndex: '-1'
  }];
}

/* harmony default export */ const use_dialog = (useDialog);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/library/close.js
var library_close = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/library/close.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/utils.js
// @ts-nocheck

/**
 * WordPress dependencies
 */

/**
 * Module constants
 */

const HEIGHT_OFFSET = 10; // Used by the arrow and a bit of empty space.

/**
 * Utility used to compute the popover position over the xAxis
 *
 * @param {Object}  anchorRect            Anchor Rect.
 * @param {Object}  contentSize           Content Size.
 * @param {string}  xAxis                 Desired xAxis.
 * @param {string}  corner                Desired corner.
 * @param {boolean} stickyBoundaryElement The boundary element to use when
 *                                        switching between sticky and normal
 *                                        position.
 * @param {string}  chosenYAxis           yAxis to be used.
 * @param {Element} boundaryElement       Boundary element.
 * @param {boolean} forcePosition         Don't adjust position based on anchor.
 * @param {boolean} forceXAlignment       Don't adjust alignment based on YAxis
 *
 * @return {Object} Popover xAxis position and constraints.
 */

function computePopoverXAxisPosition(anchorRect, contentSize, xAxis, corner, stickyBoundaryElement, chosenYAxis, boundaryElement, forcePosition, forceXAlignment) {
  const {
    width
  } = contentSize; // Correct xAxis for RTL support.

  if (xAxis === 'left' && (0,i18n_build_module/* isRTL */.V8)()) {
    xAxis = 'right';
  } else if (xAxis === 'right' && (0,i18n_build_module/* isRTL */.V8)()) {
    xAxis = 'left';
  }

  if (corner === 'left' && (0,i18n_build_module/* isRTL */.V8)()) {
    corner = 'right';
  } else if (corner === 'right' && (0,i18n_build_module/* isRTL */.V8)()) {
    corner = 'left';
  } // X axis alignment choices.


  const anchorMidPoint = Math.round(anchorRect.left + anchorRect.width / 2);
  const centerAlignment = {
    popoverLeft: anchorMidPoint,
    contentWidth: (anchorMidPoint - width / 2 > 0 ? width / 2 : anchorMidPoint) + (anchorMidPoint + width / 2 > window.innerWidth ? window.innerWidth - anchorMidPoint : width / 2)
  };
  let leftAlignmentX = anchorRect.left;

  if (corner === 'right') {
    leftAlignmentX = anchorRect.right;
  } else if (chosenYAxis !== 'middle' && !forceXAlignment) {
    leftAlignmentX = anchorMidPoint;
  }

  let rightAlignmentX = anchorRect.right;

  if (corner === 'left') {
    rightAlignmentX = anchorRect.left;
  } else if (chosenYAxis !== 'middle' && !forceXAlignment) {
    rightAlignmentX = anchorMidPoint;
  }

  const leftAlignment = {
    popoverLeft: leftAlignmentX,
    contentWidth: leftAlignmentX - width > 0 ? width : leftAlignmentX
  };
  const rightAlignment = {
    popoverLeft: rightAlignmentX,
    contentWidth: rightAlignmentX + width > window.innerWidth ? window.innerWidth - rightAlignmentX : width
  }; // Choosing the x axis.

  let chosenXAxis = xAxis;
  let contentWidth = null;

  if (!stickyBoundaryElement && !forcePosition) {
    if (xAxis === 'center' && centerAlignment.contentWidth === width) {
      chosenXAxis = 'center';
    } else if (xAxis === 'left' && leftAlignment.contentWidth === width) {
      chosenXAxis = 'left';
    } else if (xAxis === 'right' && rightAlignment.contentWidth === width) {
      chosenXAxis = 'right';
    } else {
      chosenXAxis = leftAlignment.contentWidth > rightAlignment.contentWidth ? 'left' : 'right';
      const chosenWidth = chosenXAxis === 'left' ? leftAlignment.contentWidth : rightAlignment.contentWidth; // Limit width of the content to the viewport width

      if (width > window.innerWidth) {
        contentWidth = window.innerWidth;
      } // If we can't find any alignment options that could fit
      // our content, then let's fallback to the center of the viewport.


      if (chosenWidth !== width) {
        chosenXAxis = 'center';
        centerAlignment.popoverLeft = window.innerWidth / 2;
      }
    }
  }

  let popoverLeft;

  if (chosenXAxis === 'center') {
    popoverLeft = centerAlignment.popoverLeft;
  } else if (chosenXAxis === 'left') {
    popoverLeft = leftAlignment.popoverLeft;
  } else {
    popoverLeft = rightAlignment.popoverLeft;
  }

  if (boundaryElement) {
    popoverLeft = Math.min(popoverLeft, boundaryElement.offsetLeft + boundaryElement.offsetWidth - width); // Avoid the popover being position beyond the left boundary if the
    // direction is left to right.

    if (!(0,i18n_build_module/* isRTL */.V8)()) {
      popoverLeft = Math.max(popoverLeft, 0);
    }
  }

  return {
    xAxis: chosenXAxis,
    popoverLeft,
    contentWidth
  };
}
/**
 * Utility used to compute the popover position over the yAxis
 *
 * @param {Object}       anchorRect            Anchor Rect.
 * @param {Object}       contentSize           Content Size.
 * @param {string}       yAxis                 Desired yAxis.
 * @param {string}       corner                Desired corner.
 * @param {boolean}      stickyBoundaryElement The boundary element to use when switching between sticky
 *                                             and normal position.
 * @param {Element}      anchorRef             The anchor element.
 * @param {Element}      relativeOffsetTop     If applicable, top offset of the relative positioned
 *                                             parent container.
 * @param {boolean}      forcePosition         Don't adjust position based on anchor.
 * @param {Element|null} editorWrapper         Element that wraps the editor content. Used to access
 *                                             scroll position to determine sticky behavior.
 * @return {Object} Popover xAxis position and constraints.
 */

function computePopoverYAxisPosition(anchorRect, contentSize, yAxis, corner, stickyBoundaryElement, anchorRef, relativeOffsetTop, forcePosition, editorWrapper) {
  const {
    height
  } = contentSize;

  if (stickyBoundaryElement) {
    const stickyRect = stickyBoundaryElement.getBoundingClientRect();
    const stickyPositionTop = stickyRect.top + height - relativeOffsetTop;
    const stickyPositionBottom = stickyRect.bottom - height - relativeOffsetTop;

    if (anchorRect.top <= stickyPositionTop) {
      if (editorWrapper) {
        // If a popover cannot be positioned above the anchor, even after scrolling, we must
        // ensure we use the bottom position instead of the popover slot.  This prevents the
        // popover from always restricting block content and interaction while selected if the
        // block is near the top of the site editor.
        const isRoomAboveInCanvas = height + HEIGHT_OFFSET < editorWrapper.scrollTop + anchorRect.top;

        if (!isRoomAboveInCanvas) {
          return {
            yAxis: 'bottom',
            // If the bottom of the block is also below the bottom sticky position (ex -
            // block is also taller than the editor window), return the bottom sticky
            // position instead.  We do this instead of the top sticky position both to
            // allow a smooth transition and more importantly to ensure every section of
            // the block can be free from popover obscuration at some point in the
            // scroll position.
            popoverTop: Math.min(anchorRect.bottom, stickyPositionBottom)
          };
        }
      } // Default sticky behavior.


      return {
        yAxis,
        popoverTop: Math.min(anchorRect.bottom, stickyPositionTop)
      };
    }
  } // Y axis alignment choices.


  let anchorMidPoint = anchorRect.top + anchorRect.height / 2;

  if (corner === 'bottom') {
    anchorMidPoint = anchorRect.bottom;
  } else if (corner === 'top') {
    anchorMidPoint = anchorRect.top;
  }

  const middleAlignment = {
    popoverTop: anchorMidPoint,
    contentHeight: (anchorMidPoint - height / 2 > 0 ? height / 2 : anchorMidPoint) + (anchorMidPoint + height / 2 > window.innerHeight ? window.innerHeight - anchorMidPoint : height / 2)
  };
  const topAlignment = {
    popoverTop: anchorRect.top,
    contentHeight: anchorRect.top - HEIGHT_OFFSET - height > 0 ? height : anchorRect.top - HEIGHT_OFFSET
  };
  const bottomAlignment = {
    popoverTop: anchorRect.bottom,
    contentHeight: anchorRect.bottom + HEIGHT_OFFSET + height > window.innerHeight ? window.innerHeight - HEIGHT_OFFSET - anchorRect.bottom : height
  }; // Choosing the y axis.

  let chosenYAxis = yAxis;
  let contentHeight = null;

  if (!stickyBoundaryElement && !forcePosition) {
    if (yAxis === 'middle' && middleAlignment.contentHeight === height) {
      chosenYAxis = 'middle';
    } else if (yAxis === 'top' && topAlignment.contentHeight === height) {
      chosenYAxis = 'top';
    } else if (yAxis === 'bottom' && bottomAlignment.contentHeight === height) {
      chosenYAxis = 'bottom';
    } else {
      chosenYAxis = topAlignment.contentHeight > bottomAlignment.contentHeight ? 'top' : 'bottom';
      const chosenHeight = chosenYAxis === 'top' ? topAlignment.contentHeight : bottomAlignment.contentHeight;
      contentHeight = chosenHeight !== height ? chosenHeight : null;
    }
  }

  let popoverTop;

  if (chosenYAxis === 'middle') {
    popoverTop = middleAlignment.popoverTop;
  } else if (chosenYAxis === 'top') {
    popoverTop = topAlignment.popoverTop;
  } else {
    popoverTop = bottomAlignment.popoverTop;
  }

  return {
    yAxis: chosenYAxis,
    popoverTop,
    contentHeight
  };
}
/**
 * Utility used to compute the popover position and the content max width/height for a popover given
 * its anchor rect and its content size.
 *
 * @param {Object}       anchorRect            Anchor Rect.
 * @param {Object}       contentSize           Content Size.
 * @param {string}       position              Position.
 * @param {boolean}      stickyBoundaryElement The boundary element to use when switching between
 *                                             sticky and normal position.
 * @param {Element}      anchorRef             The anchor element.
 * @param {number}       relativeOffsetTop     If applicable, top offset of the relative positioned
 *                                             parent container.
 * @param {Element}      boundaryElement       Boundary element.
 * @param {boolean}      forcePosition         Don't adjust position based on anchor.
 * @param {boolean}      forceXAlignment       Don't adjust alignment based on YAxis
 * @param {Element|null} editorWrapper         Element that wraps the editor content. Used to access
 *                                             scroll position to determine sticky behavior.
 * @return {Object} Popover position and constraints.
 */

function computePopoverPosition(anchorRect, contentSize) {
  let position = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'top';
  let stickyBoundaryElement = arguments.length > 3 ? arguments[3] : undefined;
  let anchorRef = arguments.length > 4 ? arguments[4] : undefined;
  let relativeOffsetTop = arguments.length > 5 ? arguments[5] : undefined;
  let boundaryElement = arguments.length > 6 ? arguments[6] : undefined;
  let forcePosition = arguments.length > 7 ? arguments[7] : undefined;
  let forceXAlignment = arguments.length > 8 ? arguments[8] : undefined;
  let editorWrapper = arguments.length > 9 ? arguments[9] : undefined;
  const [yAxis, xAxis = 'center', corner] = position.split(' ');
  const yAxisPosition = computePopoverYAxisPosition(anchorRect, contentSize, yAxis, corner, stickyBoundaryElement, anchorRef, relativeOffsetTop, forcePosition, editorWrapper);
  const xAxisPosition = computePopoverXAxisPosition(anchorRect, contentSize, xAxis, corner, stickyBoundaryElement, yAxisPosition.yAxis, boundaryElement, forcePosition, forceXAlignment);
  return { ...xAxisPosition,
    ...yAxisPosition
  };
}
/**
 * Offsets the given rect by the position of the iframe that contains the
 * element. If the owner document is not in an iframe then it returns with the
 * original rect. If the popover container document and the anchor document are
 * the same, the original rect will also be returned.
 *
 * @param {DOMRect}  rect          bounds of the element
 * @param {Document} ownerDocument document of the element
 * @param {Element}  container     The popover container to position.
 *
 * @return {DOMRect} offsetted bounds
 */

function offsetIframe(rect, ownerDocument, container) {
  const {
    defaultView
  } = ownerDocument;
  const {
    frameElement
  } = defaultView;

  if (!frameElement || ownerDocument === container.ownerDocument) {
    return rect;
  }

  const iframeRect = frameElement.getBoundingClientRect();
  return new defaultView.DOMRect(rect.left + iframeRect.left, rect.top + iframeRect.top, rect.width, rect.height);
}
//# sourceMappingURL=utils.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/scroll-lock/index.js
/**
 * WordPress dependencies
 */

/*
 * Setting `overflow: hidden` on html and body elements resets body scroll in iOS.
 * Save scroll top so we can restore it after locking scroll.
 *
 * NOTE: It would be cleaner and possibly safer to find a localized solution such
 * as preventing default on certain touchmove events.
 */

let previousScrollTop = 0;
/**
 * @param {boolean} locked
 */

function setLocked(locked) {
  const scrollingElement = document.scrollingElement || document.body;

  if (locked) {
    previousScrollTop = scrollingElement.scrollTop;
  }

  const methodName = locked ? 'add' : 'remove';
  scrollingElement.classList[methodName]('lockscroll'); // Adding the class to the document element seems to be necessary in iOS.

  document.documentElement.classList[methodName]('lockscroll');

  if (!locked) {
    scrollingElement.scrollTop = previousScrollTop;
  }
}

let lockCounter = 0;
/**
 * A component that will lock scrolling when it is mounted and unlock scrolling when it is unmounted.
 *
 * @return {null} Render nothing.
 */

function ScrollLock() {
  (0,react.useEffect)(() => {
    if (lockCounter === 0) {
      setLocked(true);
    }

    ++lockCounter;
    return () => {
      if (lockCounter === 1) {
        setLocked(false);
      }

      --lockCounter;
    };
  }, []);
  return null;
}
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/use-slot.js
var use_slot = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/use-slot.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js + 8 modules
var slot_fill = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/animate/index.js
/**
 * External dependencies
 */

/**
 * @typedef {'top' | 'top left' | 'top right' | 'middle' | 'middle left' | 'middle right' | 'bottom' | 'bottom left' | 'bottom right'} AppearOrigin
 * @typedef {'left' | 'right'} SlideInOrigin
 * @typedef {{ type: 'appear'; origin?: AppearOrigin }} AppearOptions
 * @typedef {{ type: 'slide-in'; origin?: SlideInOrigin }} SlideInOptions
 * @typedef {{ type: 'loading' }} LoadingOptions
 * @typedef {AppearOptions | SlideInOptions | LoadingOptions} GetAnimateOptions
 */

/* eslint-disable jsdoc/valid-types */

/**
 * @param {GetAnimateOptions['type']} type The animation type
 * @return {'top' | 'left'} Default origin
 */

function getDefaultOrigin(type) {
  return type === 'appear' ? 'top' : 'left';
}
/* eslint-enable jsdoc/valid-types */

/**
 * @param {GetAnimateOptions} options
 *
 * @return {string | void} ClassName that applies the animations
 */


function getAnimateClassName(options) {
  if (options.type === 'loading') {
    return classnames_default()('components-animate__loading');
  }

  const {
    type,
    origin = getDefaultOrigin(type)
  } = options;

  if (type === 'appear') {
    const [yAxis, xAxis = 'center'] = origin.split(' ');
    return classnames_default()('components-animate__appear', {
      ['is-from-' + xAxis]: xAxis !== 'center',
      ['is-from-' + yAxis]: yAxis !== 'middle'
    });
  }

  if (type === 'slide-in') {
    return classnames_default()('components-animate__slide-in', 'is-from-' + origin);
  }
} // @ts-ignore Reason: Planned for deprecation

function Animate(_ref) {
  let {
    type,
    options = {},
    children
  } = _ref;
  return children({
    className: getAnimateClassName({
      type,
      ...options
    })
  });
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js


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






/**
 * Name of slot in which popover should fill.
 *
 * @type {string}
 */

const SLOT_NAME = 'Popover';
const slotNameContext = (0,react.createContext)();

function computeAnchorRect(anchorRefFallback, anchorRect, getAnchorRect) {
  let anchorRef = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  let shouldAnchorIncludePadding = arguments.length > 4 ? arguments[4] : undefined;
  let container = arguments.length > 5 ? arguments[5] : undefined;

  if (anchorRect) {
    return anchorRect;
  }

  if (getAnchorRect) {
    if (!anchorRefFallback.current) {
      return;
    }

    const rect = getAnchorRect(anchorRefFallback.current);
    return offsetIframe(rect, rect.ownerDocument || anchorRefFallback.current.ownerDocument, container);
  }

  if (anchorRef !== false) {
    if (!anchorRef || !window.Range || !window.Element || !window.DOMRect) {
      return;
    } // Duck-type to check if `anchorRef` is an instance of Range
    // `anchorRef instanceof window.Range` checks will break across document boundaries
    // such as in an iframe.


    if (typeof (anchorRef === null || anchorRef === void 0 ? void 0 : anchorRef.cloneRange) === 'function') {
      return offsetIframe(getRectangleFromRange(anchorRef), anchorRef.endContainer.ownerDocument, container);
    } // Duck-type to check if `anchorRef` is an instance of Element
    // `anchorRef instanceof window.Element` checks will break across document boundaries
    // such as in an iframe.


    if (typeof (anchorRef === null || anchorRef === void 0 ? void 0 : anchorRef.getBoundingClientRect) === 'function') {
      const rect = offsetIframe(anchorRef.getBoundingClientRect(), anchorRef.ownerDocument, container);

      if (shouldAnchorIncludePadding) {
        return rect;
      }

      return withoutPadding(rect, anchorRef);
    }

    const {
      top,
      bottom
    } = anchorRef;
    const topRect = top.getBoundingClientRect();
    const bottomRect = bottom.getBoundingClientRect();
    const rect = offsetIframe(new window.DOMRect(topRect.left, topRect.top, topRect.width, bottomRect.bottom - topRect.top), top.ownerDocument, container);

    if (shouldAnchorIncludePadding) {
      return rect;
    }

    return withoutPadding(rect, anchorRef);
  }

  if (!anchorRefFallback.current) {
    return;
  }

  const {
    parentNode
  } = anchorRefFallback.current;
  const rect = offsetIframe(parentNode.getBoundingClientRect(), parentNode.ownerDocument, container);

  if (shouldAnchorIncludePadding) {
    return rect;
  }

  return withoutPadding(rect, parentNode);
}

function getComputedStyle(node) {
  return node.ownerDocument.defaultView.getComputedStyle(node);
}

function withoutPadding(rect, element) {
  const {
    paddingTop,
    paddingBottom,
    paddingLeft,
    paddingRight
  } = getComputedStyle(element);
  const top = paddingTop ? parseInt(paddingTop, 10) : 0;
  const bottom = paddingBottom ? parseInt(paddingBottom, 10) : 0;
  const left = paddingLeft ? parseInt(paddingLeft, 10) : 0;
  const right = paddingRight ? parseInt(paddingRight, 10) : 0;
  return {
    x: rect.left + left,
    y: rect.top + top,
    width: rect.width - left - right,
    height: rect.height - top - bottom,
    left: rect.left + left,
    right: rect.right - right,
    top: rect.top + top,
    bottom: rect.bottom - bottom
  };
}
/**
 * Sets or removes an element attribute.
 *
 * @param {Element} element The element to modify.
 * @param {string}  name    The attribute name to set or remove.
 * @param {?string} value   The value to set. A falsy value will remove the
 *                          attribute.
 */


function setAttribute(element, name, value) {
  if (!value) {
    if (element.hasAttribute(name)) {
      element.removeAttribute(name);
    }
  } else if (element.getAttribute(name) !== value) {
    element.setAttribute(name, value);
  }
}
/**
 * Sets or removes an element style property.
 *
 * @param {Element} element  The element to modify.
 * @param {string}  property The property to set or remove.
 * @param {?string} value    The value to set. A falsy value will remove the
 *                           property.
 */


function setStyle(element, property) {
  let value = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';

  if (element.style[property] !== value) {
    element.style[property] = value;
  }
}
/**
 * Sets or removes an element class.
 *
 * @param {Element} element The element to modify.
 * @param {string}  name    The class to set or remove.
 * @param {boolean} toggle  True to set the class, false to remove.
 */


function setClass(element, name, toggle) {
  if (toggle) {
    if (!element.classList.contains(name)) {
      element.classList.add(name);
    }
  } else if (element.classList.contains(name)) {
    element.classList.remove(name);
  }
}

function getAnchorDocument(anchor) {
  if (!anchor) {
    return;
  }

  if (anchor.endContainer) {
    return anchor.endContainer.ownerDocument;
  }

  if (anchor.top) {
    return anchor.top.ownerDocument;
  }

  return anchor.ownerDocument;
}

const Popover = (_ref, ref) => {
  let {
    headerTitle,
    onClose,
    children,
    className,
    noArrow = true,
    isAlternate,
    // Disable reason: We generate the `...contentProps` rest as remainder
    // of props which aren't explicitly handled by this component.

    /* eslint-disable no-unused-vars */
    position = 'bottom right',
    range,
    focusOnMount = 'firstElement',
    anchorRef,
    shouldAnchorIncludePadding,
    anchorRect,
    getAnchorRect,
    expandOnMobile,
    animate = true,
    onFocusOutside,
    __unstableStickyBoundaryElement,
    __unstableSlotName = SLOT_NAME,
    __unstableObserveElement,
    __unstableBoundaryParent,
    __unstableForcePosition,
    __unstableForceXAlignment,
    __unstableEditorCanvasWrapper,

    /* eslint-enable no-unused-vars */
    ...contentProps
  } = _ref;
  const anchorRefFallback = (0,react.useRef)(null);
  const contentRef = (0,react.useRef)(null);
  const containerRef = (0,react.useRef)();
  const isMobileViewport = use_viewport_match('medium', '<');
  const [animateOrigin, setAnimateOrigin] = (0,react.useState)();

  const slotName = (0,react.useContext)(slotNameContext) || __unstableSlotName;

  const slot = (0,use_slot/* default */.A)(slotName);
  const isExpanded = expandOnMobile && isMobileViewport;
  const [containerResizeListener, contentSize] = use_resize_observer();
  noArrow = isExpanded || noArrow;
  (0,react.useLayoutEffect)(() => {
    if (isExpanded) {
      setClass(containerRef.current, 'is-without-arrow', noArrow);
      setClass(containerRef.current, 'is-alternate', isAlternate);
      setAttribute(containerRef.current, 'data-x-axis');
      setAttribute(containerRef.current, 'data-y-axis');
      setStyle(containerRef.current, 'top');
      setStyle(containerRef.current, 'left');
      setStyle(contentRef.current, 'maxHeight');
      setStyle(contentRef.current, 'maxWidth');
      return;
    }

    const refresh = () => {
      if (!containerRef.current || !contentRef.current) {
        return;
      }

      let anchor = computeAnchorRect(anchorRefFallback, anchorRect, getAnchorRect, anchorRef, shouldAnchorIncludePadding, containerRef.current);

      if (!anchor) {
        return;
      }

      const {
        offsetParent,
        ownerDocument
      } = containerRef.current;
      let relativeOffsetTop = 0; // If there is a positioned ancestor element that is not the body,
      // subtract the position from the anchor rect. If the position of
      // the popover is fixed, the offset parent is null or the body
      // element, in which case the position is relative to the viewport.
      // See https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/offsetParent

      if (offsetParent && offsetParent !== ownerDocument.body) {
        const offsetParentRect = offsetParent.getBoundingClientRect();
        relativeOffsetTop = offsetParentRect.top;
        anchor = new window.DOMRect(anchor.left - offsetParentRect.left, anchor.top - offsetParentRect.top, anchor.width, anchor.height);
      }

      let boundaryElement;

      if (__unstableBoundaryParent) {
        boundaryElement = containerRef.current.parentElement;
      }

      const usedContentSize = !contentSize.height ? contentRef.current.getBoundingClientRect() : contentSize;
      const {
        popoverTop,
        popoverLeft,
        xAxis,
        yAxis,
        contentHeight,
        contentWidth
      } = computePopoverPosition(anchor, usedContentSize, position, __unstableStickyBoundaryElement, containerRef.current, relativeOffsetTop, boundaryElement, __unstableForcePosition, __unstableForceXAlignment, __unstableEditorCanvasWrapper);

      if (typeof popoverTop === 'number' && typeof popoverLeft === 'number') {
        setStyle(containerRef.current, 'top', popoverTop + 'px');
        setStyle(containerRef.current, 'left', popoverLeft + 'px');
      }

      setClass(containerRef.current, 'is-without-arrow', noArrow || xAxis === 'center' && yAxis === 'middle');
      setClass(containerRef.current, 'is-alternate', isAlternate);
      setAttribute(containerRef.current, 'data-x-axis', xAxis);
      setAttribute(containerRef.current, 'data-y-axis', yAxis);
      setStyle(contentRef.current, 'maxHeight', typeof contentHeight === 'number' ? contentHeight + 'px' : '');
      setStyle(contentRef.current, 'maxWidth', typeof contentWidth === 'number' ? contentWidth + 'px' : ''); // Compute the animation position.

      const yAxisMapping = {
        top: 'bottom',
        bottom: 'top'
      };
      const xAxisMapping = {
        left: 'right',
        right: 'left'
      };
      const animateYAxis = yAxisMapping[yAxis] || 'middle';
      const animateXAxis = xAxisMapping[xAxis] || 'center';
      setAnimateOrigin(animateXAxis + ' ' + animateYAxis);
    };

    refresh();
    const {
      ownerDocument
    } = containerRef.current;
    const {
      defaultView
    } = ownerDocument;
    /*
     * There are sometimes we need to reposition or resize the popover that
     * are not handled by the resize/scroll window events (i.e. CSS changes
     * in the layout that changes the position of the anchor).
     *
     * For these situations, we refresh the popover every 0.5s
     */

    const intervalHandle = defaultView.setInterval(refresh, 500);
    let rafId;

    const refreshOnAnimationFrame = () => {
      defaultView.cancelAnimationFrame(rafId);
      rafId = defaultView.requestAnimationFrame(refresh);
    }; // Sometimes a click trigger a layout change that affects the popover
    // position. This is an opportunity to immediately refresh rather than
    // at the interval.


    defaultView.addEventListener('click', refreshOnAnimationFrame);
    defaultView.addEventListener('resize', refresh);
    defaultView.addEventListener('scroll', refresh, true);
    const anchorDocument = getAnchorDocument(anchorRef); // If the anchor is within an iframe, the popover position also needs
    // to refrest when the iframe content is scrolled or resized.

    if (anchorDocument && anchorDocument !== ownerDocument) {
      anchorDocument.defaultView.addEventListener('resize', refresh);
      anchorDocument.defaultView.addEventListener('scroll', refresh, true);
    }

    let observer;

    if (__unstableObserveElement) {
      observer = new defaultView.MutationObserver(refresh);
      observer.observe(__unstableObserveElement, {
        attributes: true
      });
    }

    return () => {
      defaultView.clearInterval(intervalHandle);
      defaultView.removeEventListener('resize', refresh);
      defaultView.removeEventListener('scroll', refresh, true);
      defaultView.removeEventListener('click', refreshOnAnimationFrame);
      defaultView.cancelAnimationFrame(rafId);

      if (anchorDocument && anchorDocument !== ownerDocument) {
        var _anchorDocument$defau, _anchorDocument$defau2;

        (_anchorDocument$defau = anchorDocument.defaultView) === null || _anchorDocument$defau === void 0 ? void 0 : _anchorDocument$defau.removeEventListener('resize', refresh);
        (_anchorDocument$defau2 = anchorDocument.defaultView) === null || _anchorDocument$defau2 === void 0 ? void 0 : _anchorDocument$defau2.removeEventListener('scroll', refresh, true);
      }

      if (observer) {
        observer.disconnect();
      }
    };
  }, [isExpanded, anchorRect, getAnchorRect, anchorRef, shouldAnchorIncludePadding, position, contentSize, __unstableStickyBoundaryElement, __unstableObserveElement, __unstableBoundaryParent]);

  const onDialogClose = (type, event) => {
    // Ideally the popover should have just a single onClose prop and
    // not three props that potentially do the same thing.
    if (type === 'focus-outside' && onFocusOutside) {
      onFocusOutside(event);
    } else if (onClose) {
      onClose();
    }
  };

  const [dialogRef, dialogProps] = use_dialog({
    focusOnMount,
    __unstableOnClose: onDialogClose,
    onClose: onDialogClose
  });
  const mergedRefs = (0,use_merge_refs/* default */.A)([containerRef, dialogRef, ref]);
  /** @type {false | string} */

  const animateClassName = Boolean(animate && animateOrigin) && getAnimateClassName({
    type: 'appear',
    origin: animateOrigin
  }); // Disable reason: We care to capture the _bubbled_ events from inputs
  // within popover as inferring close intent.

  let content = // eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions
  // eslint-disable-next-line jsx-a11y/no-static-element-interactions
  (0,react.createElement)("div", (0,esm_extends/* default */.A)({
    className: classnames_default()('components-popover', className, animateClassName, {
      'is-expanded': isExpanded,
      'is-without-arrow': noArrow,
      'is-alternate': isAlternate
    })
  }, contentProps, {
    ref: mergedRefs
  }, dialogProps, {
    tabIndex: "-1"
  }), isExpanded && (0,react.createElement)(ScrollLock, null), isExpanded && (0,react.createElement)("div", {
    className: "components-popover__header"
  }, (0,react.createElement)("span", {
    className: "components-popover__header-title"
  }, headerTitle), (0,react.createElement)(build_module_button/* default */.A, {
    className: "components-popover__close",
    icon: library_close/* default */.A,
    onClick: onClose
  })), (0,react.createElement)("div", {
    ref: contentRef,
    className: "components-popover__content"
  }, (0,react.createElement)("div", {
    style: {
      position: 'relative'
    }
  }, containerResizeListener, children)));

  if (slot.ref) {
    content = (0,react.createElement)(slot_fill/* Fill */.SQ, {
      name: slotName
    }, content);
  }

  if (anchorRef || anchorRect) {
    return content;
  }

  return (0,react.createElement)("span", {
    ref: anchorRefFallback
  }, content);
};

const PopoverContainer = (0,react.forwardRef)(Popover);

function PopoverSlot(_ref2, ref) {
  let {
    name = SLOT_NAME
  } = _ref2;
  return (0,react.createElement)(slot_fill/* Slot */.DX, {
    bubblesVirtually: true,
    name: name,
    className: "popover-slot",
    ref: ref
  });
}

PopoverContainer.Slot = (0,react.forwardRef)(PopoverSlot);
PopoverContainer.__unstableSlotNameProvider = slotNameContext.Provider;
/* harmony default export */ const popover = (PopoverContainer);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/shortcut/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);


/**
 * External dependencies
 */

/** @typedef {string | { display: string, ariaLabel: string }} Shortcut */

/**
 * @typedef Props
 * @property {Shortcut} shortcut    Shortcut configuration
 * @property {string}   [className] Classname
 */

/**
 * @param {Props} props Props
 * @return {JSX.Element | null} Element
 */

function Shortcut(_ref) {
  let {
    shortcut,
    className
  } = _ref;

  if (!shortcut) {
    return null;
  }

  let displayText;
  let ariaLabel;

  if ((0,lodash__WEBPACK_IMPORTED_MODULE_0__.isString)(shortcut)) {
    displayText = shortcut;
  }

  if ((0,lodash__WEBPACK_IMPORTED_MODULE_0__.isObject)(shortcut)) {
    displayText = shortcut.display;
    ariaLabel = shortcut.ariaLabel;
  }

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
    className: className,
    "aria-label": ariaLabel
  }, displayText);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Shortcut);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* provided dependency */ var process = __webpack_require__("../../node_modules/.pnpm/process@0.11.10/node_modules/process/browser.js");
// @ts-nocheck

/**
 * WordPress dependencies
 */


const SlotFillContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)({
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SlotFillContext);
//# sourceMappingURL=slot-fill-context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/use-slot.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useSlot)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _slot_fill_context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js");
// @ts-nocheck

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


function useSlot(name) {
  const registry = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(_slot_fill_context__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A);
  const slot = registry.slots[name] || {};
  const slotFills = registry.fills[name];
  const fills = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => slotFills || [], [slotFills]);
  const updateSlot = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(fillProps => {
    registry.updateSlot(name, fillProps);
  }, [name, registry.updateSlot]);
  const unregisterSlot = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(slotRef => {
    registry.unregisterSlot(name, slotRef);
  }, [name, registry.unregisterSlot]);
  const registerFill = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(fillRef => {
    registry.registerFill(name, fillRef);
  }, [name, registry.registerFill]);
  const unregisterFill = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(fillRef => {
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

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  SQ: () => (/* binding */ slot_fill_Fill),
  Kq: () => (/* binding */ Provider),
  DX: () => (/* binding */ slot_fill_Slot)
});

// UNUSED EXPORTS: createSlotFill, useSlot

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/context.js
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
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/use-slot.js
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
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/fill.js


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
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/slot.js


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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/use-slot.js
var bubbles_virtually_use_slot = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/use-slot.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/style-provider/index.js
var style_provider = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/style-provider/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/fill.js

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
  const slot = (0,bubbles_virtually_use_slot/* default */.A)(name);
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


  const wrappedChildren = (0,react.createElement)(style_provider/* default */.A, {
    document: slot.ref.current.ownerDocument
  }, children);
  return (0,react_dom.createPortal)(wrappedChildren, slot.ref.current);
}
//# sourceMappingURL=fill.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js
var slot_fill_context = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot.js


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
  const registry = (0,react.useContext)(slot_fill_context/* default */.A);
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.24.0/node_modules/@wordpress/is-shallow-equal/build-module/index.js + 2 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.24.0/node_modules/@wordpress/is-shallow-equal/build-module/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-provider.js

// @ts-nocheck

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function useSlotRegistry() {
  const [slots, setSlots] = (0,react.useState)({});
  const [fills, setFills] = (0,react.useState)({});
  const registerSlot = (0,react.useCallback)((name, ref, fillProps) => {
    setSlots(prevSlots => {
      const slot = prevSlots[name] || {};
      return { ...prevSlots,
        [name]: { ...slot,
          ref: ref || slot.ref,
          fillProps: fillProps || slot.fillProps || {}
        }
      };
    });
  }, []);
  const unregisterSlot = (0,react.useCallback)((name, ref) => {
    setSlots(prevSlots => {
      const {
        [name]: slot,
        ...nextSlots
      } = prevSlots; // Make sure we're not unregistering a slot registered by another element
      // See https://github.com/WordPress/gutenberg/pull/19242#issuecomment-590295412

      if ((slot === null || slot === void 0 ? void 0 : slot.ref) === ref) {
        return nextSlots;
      }

      return prevSlots;
    });
  }, []);
  const updateSlot = (0,react.useCallback)((name, fillProps) => {
    const slot = slots[name];

    if (!slot) {
      return;
    }

    if (!(0,build_module/* default */.Ay)(slot.fillProps, fillProps)) {
      slot.fillProps = fillProps;
      const slotFills = fills[name];

      if (slotFills) {
        // Force update fills.
        slotFills.map(fill => fill.current.rerender());
      }
    }
  }, [slots, fills]);
  const registerFill = (0,react.useCallback)((name, ref) => {
    setFills(prevFills => ({ ...prevFills,
      [name]: [...(prevFills[name] || []), ref]
    }));
  }, []);
  const unregisterFill = (0,react.useCallback)((name, ref) => {
    setFills(prevFills => {
      if (prevFills[name]) {
        return { ...prevFills,
          [name]: prevFills[name].filter(fillRef => fillRef !== ref)
        };
      }

      return prevFills;
    });
  }, []); // Memoizing the return value so it can be directly passed to Provider value

  const registry = (0,react.useMemo)(() => ({
    slots,
    fills,
    registerSlot,
    updateSlot,
    unregisterSlot,
    registerFill,
    unregisterFill
  }), [slots, fills, registerSlot, updateSlot, unregisterSlot, registerFill, unregisterFill]);
  return registry;
}

function SlotFillProvider(_ref) {
  let {
    children
  } = _ref;
  const registry = useSlotRegistry();
  return (0,react.createElement)(slot_fill_context/* default */.A.Provider, {
    value: registry
  }, children);
}
//# sourceMappingURL=slot-fill-provider.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/provider.js

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


class provider_SlotFillProvider extends react.Component {
  constructor() {
    super(...arguments);
    this.registerSlot = this.registerSlot.bind(this);
    this.registerFill = this.registerFill.bind(this);
    this.unregisterSlot = this.unregisterSlot.bind(this);
    this.unregisterFill = this.unregisterFill.bind(this);
    this.getSlot = this.getSlot.bind(this);
    this.getFills = this.getFills.bind(this);
    this.hasFills = this.hasFills.bind(this);
    this.subscribe = this.subscribe.bind(this);
    this.slots = {};
    this.fills = {};
    this.listeners = [];
    this.contextValue = {
      registerSlot: this.registerSlot,
      unregisterSlot: this.unregisterSlot,
      registerFill: this.registerFill,
      unregisterFill: this.unregisterFill,
      getSlot: this.getSlot,
      getFills: this.getFills,
      hasFills: this.hasFills,
      subscribe: this.subscribe
    };
  }

  registerSlot(name, slot) {
    const previousSlot = this.slots[name];
    this.slots[name] = slot;
    this.triggerListeners(); // Sometimes the fills are registered after the initial render of slot
    // But before the registerSlot call, we need to rerender the slot.

    this.forceUpdateSlot(name); // If a new instance of a slot is being mounted while another with the
    // same name exists, force its update _after_ the new slot has been
    // assigned into the instance, such that its own rendering of children
    // will be empty (the new Slot will subsume all fills for this name).

    if (previousSlot) {
      previousSlot.forceUpdate();
    }
  }

  registerFill(name, instance) {
    this.fills[name] = [...(this.fills[name] || []), instance];
    this.forceUpdateSlot(name);
  }

  unregisterSlot(name, instance) {
    // If a previous instance of a Slot by this name unmounts, do nothing,
    // as the slot and its fills should only be removed for the current
    // known instance.
    if (this.slots[name] !== instance) {
      return;
    }

    delete this.slots[name];
    this.triggerListeners();
  }

  unregisterFill(name, instance) {
    this.fills[name] = (0,lodash.without)(this.fills[name], instance);
    this.forceUpdateSlot(name);
  }

  getSlot(name) {
    return this.slots[name];
  }

  getFills(name, slotInstance) {
    // Fills should only be returned for the current instance of the slot
    // in which they occupy.
    if (this.slots[name] !== slotInstance) {
      return [];
    }

    return this.fills[name];
  }

  hasFills(name) {
    return this.fills[name] && !!this.fills[name].length;
  }

  forceUpdateSlot(name) {
    const slot = this.getSlot(name);

    if (slot) {
      slot.forceUpdate();
    }
  }

  triggerListeners() {
    this.listeners.forEach(listener => listener());
  }

  subscribe(listener) {
    this.listeners.push(listener);
    return () => {
      this.listeners = (0,lodash.without)(this.listeners, listener);
    };
  }

  render() {
    return (0,react.createElement)(context.Provider, {
      value: this.contextValue
    }, this.props.children);
  }

}
//# sourceMappingURL=provider.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js


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
  return (0,react.createElement)(provider_SlotFillProvider, props, (0,react.createElement)(SlotFillProvider, null, children));
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

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/style-provider/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ StyleProvider)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-element-c39617d8.browser.esm.js");
/* harmony import */ var _emotion_cache__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@emotion+cache@11.11.0/node_modules/@emotion/cache/dist/emotion-cache.browser.esm.js");
/* harmony import */ var memize__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/memize@1.1.0/node_modules/memize/index.js");
/* harmony import */ var memize__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(memize__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var uuid__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/v4.js");

// @ts-nocheck

/**
 * External dependencies
 */




const uuidCache = new Set();
const memoizedCreateCacheWithContainer = memize__WEBPACK_IMPORTED_MODULE_1___default()(container => {
  // Emotion only accepts alphabetical and hyphenated keys so we just strip the numbers from the UUID. It _should_ be fine.
  let key = uuid__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A().replace(/[0-9]/g, '');

  while (uuidCache.has(key)) {
    key = uuid__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A().replace(/[0-9]/g, '');
  }

  uuidCache.add(key);
  return (0,_emotion_cache__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)({
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
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_emotion_react__WEBPACK_IMPORTED_MODULE_4__.C, {
    value: cache
  }, children);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ tooltip)
});

// UNUSED EXPORTS: TOOLTIP_DELAY

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/react.js
/**
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/no-restricted-imports


/**
 * Object containing a React element.
 *
 * @typedef {import('react').ReactElement} WPElement
 */

/**
 * Object containing a React component.
 *
 * @typedef {import('react').ComponentType} WPComponent
 */

/**
 * Object containing a React synthetic event.
 *
 * @typedef {import('react').SyntheticEvent} WPSyntheticEvent
 */

/**
 * Object that provides utilities for dealing with React children.
 */


/**
 * Creates a copy of an element with extended props.
 *
 * @param {WPElement} element Element
 * @param {?Object}   props   Props to apply to cloned element
 *
 * @return {WPElement} Cloned element.
 */


/**
 * A base class to create WordPress Components (Refs, state and lifecycle hooks)
 */


/**
 * Creates a context object containing two components: a provider and consumer.
 *
 * @param {Object} defaultValue A default data stored in the context.
 *
 * @return {Object} Context object.
 */


/**
 * Returns a new element of given type. Type can be either a string tag name or
 * another function which itself returns an element.
 *
 * @param {?(string|Function)} type     Tag name or element creator
 * @param {Object}             props    Element properties, either attribute
 *                                      set to apply to DOM node or values to
 *                                      pass through to element creator
 * @param {...WPElement}       children Descendant elements
 *
 * @return {WPElement} Element.
 */


/**
 * Returns an object tracking a reference to a rendered element via its
 * `current` property as either a DOMElement or Element, dependent upon the
 * type of element rendered with the ref attribute.
 *
 * @return {Object} Ref object.
 */


/**
 * Component enhancer used to enable passing a ref to its wrapped component.
 * Pass a function argument which receives `props` and `ref` as its arguments,
 * returning an element using the forwarded ref. The return value is a new
 * component which forwards its ref.
 *
 * @param {Function} forwarder Function passed `props` and `ref`, expected to
 *                             return an element.
 *
 * @return {WPComponent} Enhanced component.
 */


/**
 * A component which renders its children without any wrapping element.
 */


/**
 * Checks if an object is a valid WPElement.
 *
 * @param {Object} objectToCheck The object to be checked.
 *
 * @return {boolean} true if objectToTest is a valid WPElement and false otherwise.
 */


/**
 * @see https://reactjs.org/docs/react-api.html#reactmemo
 */


/**
 * Component that activates additional checks and warnings for its descendants.
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#usecallback
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#usecontext
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#usedebugvalue
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#useeffect
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#useimperativehandle
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#uselayouteffect
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#usememo
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#usereducer
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#useref
 */


/**
 * @see https://reactjs.org/docs/hooks-reference.html#usestate
 */


/**
 * @see https://reactjs.org/docs/react-api.html#reactlazy
 */


/**
 * @see https://reactjs.org/docs/react-api.html#reactsuspense
 */


/**
 * Concatenate two or more React children objects.
 *
 * @param {...?Object} childrenArguments Array of children arguments (array of arrays/strings/objects) to concatenate.
 *
 * @return {Array} The concatenated value.
 */

function concatChildren() {
  for (var _len = arguments.length, childrenArguments = new Array(_len), _key = 0; _key < _len; _key++) {
    childrenArguments[_key] = arguments[_key];
  }

  return childrenArguments.reduce((accumulator, children, i) => {
    react.Children.forEach(children, (child, j) => {
      if (child && 'string' !== typeof child) {
        child = (0,react.cloneElement)(child, {
          key: [i, j].join()
        });
      }

      accumulator.push(child);
    });
    return accumulator;
  }, []);
}
/**
 * Switches the nodeName of all the elements in the children object.
 *
 * @param {?Object} children Children object.
 * @param {string}  nodeName Node name.
 *
 * @return {?Object} The updated children object.
 */

function switchChildrenNodeName(children, nodeName) {
  return children && Children.map(children, (elt, index) => {
    if (isString(elt)) {
      return createElement(nodeName, {
        key: index
      }, elt);
    }

    const {
      children: childrenProp,
      ...props
    } = elt.props;
    return createElement(nodeName, {
      key: index,
      ...props
    }, childrenProp);
  });
}
//# sourceMappingURL=react.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js
var use_debounce = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js + 8 modules
var popover = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/shortcut/index.js
var build_module_shortcut = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/shortcut/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js

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



/**
 * Time over children to wait before showing tooltip
 *
 * @type {number}
 */

const TOOLTIP_DELAY = 700;
const eventCatcher = (0,react.createElement)("div", {
  className: "event-catcher"
});

const getDisabledElement = _ref => {
  let {
    eventHandlers,
    child,
    childrenWithPopover
  } = _ref;
  return (0,react.cloneElement)((0,react.createElement)("span", {
    className: "disabled-element-wrapper"
  }, (0,react.cloneElement)(eventCatcher, eventHandlers), (0,react.cloneElement)(child, {
    children: childrenWithPopover
  })), eventHandlers);
};

const getRegularElement = _ref2 => {
  let {
    child,
    eventHandlers,
    childrenWithPopover
  } = _ref2;
  return (0,react.cloneElement)(child, { ...eventHandlers,
    children: childrenWithPopover
  });
};

const addPopoverToGrandchildren = _ref3 => {
  let {
    grandchildren,
    isOver,
    position,
    text,
    shortcut
  } = _ref3;
  return concatChildren(grandchildren, isOver && (0,react.createElement)(popover/* default */.A, {
    focusOnMount: false,
    position: position,
    className: "components-tooltip",
    "aria-hidden": "true",
    animate: false,
    noArrow: true
  }, text, (0,react.createElement)(build_module_shortcut/* default */.A, {
    className: "components-tooltip__shortcut",
    shortcut: shortcut
  })));
};

const emitToChild = (children, eventName, event) => {
  if (react.Children.count(children) !== 1) {
    return;
  }

  const child = react.Children.only(children); // If the underlying element is disabled, do not emit the event.

  if (child.props.disabled) {
    return;
  }

  if (typeof child.props[eventName] === 'function') {
    child.props[eventName](event);
  }
};

function Tooltip(props) {
  const {
    children,
    position,
    text,
    shortcut,
    delay = TOOLTIP_DELAY
  } = props;
  /**
   * Whether a mouse is currently pressed, used in determining whether
   * to handle a focus event as displaying the tooltip immediately.
   *
   * @type {boolean}
   */

  const [isMouseDown, setIsMouseDown] = (0,react.useState)(false);
  const [isOver, setIsOver] = (0,react.useState)(false);
  const delayedSetIsOver = (0,use_debounce/* default */.A)(setIsOver, delay);

  const createMouseDown = event => {
    // Preserve original child callback behavior.
    emitToChild(children, 'onMouseDown', event); // On mouse down, the next `mouseup` should revert the value of the
    // instance property and remove its own event handler. The bind is
    // made on the document since the `mouseup` might not occur within
    // the bounds of the element.

    document.addEventListener('mouseup', cancelIsMouseDown);
    setIsMouseDown(true);
  };

  const createMouseUp = event => {
    emitToChild(children, 'onMouseUp', event);
    document.removeEventListener('mouseup', cancelIsMouseDown);
    setIsMouseDown(false);
  };

  const createMouseEvent = type => {
    if (type === 'mouseUp') return createMouseUp;
    if (type === 'mouseDown') return createMouseDown;
  };
  /**
   * Prebound `isInMouseDown` handler, created as a constant reference to
   * assure ability to remove in component unmount.
   *
   * @type {Function}
   */


  const cancelIsMouseDown = createMouseEvent('mouseUp');

  const createToggleIsOver = (eventName, isDelayed) => {
    return event => {
      // Preserve original child callback behavior.
      emitToChild(children, eventName, event); // Mouse events behave unreliably in React for disabled elements,
      // firing on mouseenter but not mouseleave.  Further, the default
      // behavior for disabled elements in some browsers is to ignore
      // mouse events. Don't bother trying to to handle them.
      //
      // See: https://github.com/facebook/react/issues/4251

      if (event.currentTarget.disabled) {
        return;
      } // A focus event will occur as a result of a mouse click, but it
      // should be disambiguated between interacting with the button and
      // using an explicit focus shift as a cue to display the tooltip.


      if ('focus' === event.type && isMouseDown) {
        return;
      } // Needed in case unsetting is over while delayed set pending, i.e.
      // quickly blur/mouseleave before delayedSetIsOver is called.


      delayedSetIsOver.cancel();

      const _isOver = (0,lodash.includes)(['focus', 'mouseenter'], event.type);

      if (_isOver === isOver) {
        return;
      }

      if (isDelayed) {
        delayedSetIsOver(_isOver);
      } else {
        setIsOver(_isOver);
      }
    };
  };

  const clearOnUnmount = () => {
    delayedSetIsOver.cancel();
    document.removeEventListener('mouseup', cancelIsMouseDown);
  };

  (0,react.useEffect)(() => clearOnUnmount, []);

  if (react.Children.count(children) !== 1) {
    if (false) {}

    return children;
  }

  const eventHandlers = {
    onMouseEnter: createToggleIsOver('onMouseEnter', true),
    onMouseLeave: createToggleIsOver('onMouseLeave'),
    onClick: createToggleIsOver('onClick'),
    onFocus: createToggleIsOver('onFocus'),
    onBlur: createToggleIsOver('onBlur'),
    onMouseDown: createMouseEvent('mouseDown')
  };
  const child = react.Children.only(children);
  const {
    children: grandchildren,
    disabled
  } = child.props;
  const getElementWithPopover = disabled ? getDisabledElement : getRegularElement;
  const popoverData = {
    isOver,
    position,
    text,
    shortcut
  };
  const childrenWithPopover = addPopoverToGrandchildren({
    grandchildren,
    ...popoverData
  });
  return getElementWithPopover({
    child,
    eventHandlers,
    childrenWithPopover
  });
}

/* harmony default export */ const tooltip = (Tooltip);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/visually-hidden/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ visually_hidden_component)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/visually-hidden/styles.js
/** @type {import('react').CSSProperties} */
const visuallyHidden = {
  border: 0,
  clip: 'rect(1px, 1px, 1px, 1px)',
  WebkitClipPath: 'inset( 50% )',
  clipPath: 'inset( 50% )',
  height: '1px',
  margin: '-1px',
  overflow: 'hidden',
  padding: 0,
  position: 'absolute',
  width: '1px',
  wordWrap: 'normal'
};
//# sourceMappingURL=styles.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/view/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/visually-hidden/component.js



/**
 * Internal dependencies
 */



/**
 * @param {import('../ui/context').WordPressComponentProps<{ children: import('react').ReactNode }, 'div'>} props
 * @param {import('react').ForwardedRef<any>}                                                               forwardedRef
 */

function VisuallyHidden(props, forwardedRef) {
  const {
    style: styleProp,
    ...contextProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'VisuallyHidden');
  return (0,react.createElement)(component/* default */.A, (0,esm_extends/* default */.A)({
    ref: forwardedRef
  }, contextProps, {
    style: { ...visuallyHidden,
      ...(styleProp || {})
    }
  }));
}
/**
 * `VisuallyHidden` is a component used to render text intended to be visually
 * hidden, but will show for alternate devices, for example a screen reader.
 *
 * @example
 * ```jsx
 * import { VisuallyHidden } from `@wordpress/components`;
 *
 * function Example() {
 * 	return (
 * 		<VisuallyHidden>
 * 			<label>Code is Poetry</label>
 * 		</VisuallyHidden>
 * 	);
 * }
 * ```
 */


const ConnectedVisuallyHidden = (0,context_connect/* contextConnect */.KZ)(VisuallyHidden, 'VisuallyHidden');
/* harmony default export */ const visually_hidden_component = (ConnectedVisuallyHidden);
//# sourceMappingURL=component.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-constrained-tabbing/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js");
/* harmony import */ var _use_ref_effect__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.js");
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * In Dialogs/modals, the tabbing must be constrained to the content of
 * the wrapper element. This hook adds the behavior to the returned ref.
 *
 * @return {import('react').RefCallback<Element>} Element Ref.
 *
 * @example
 * ```js
 * import { useConstrainedTabbing } from '@wordpress/compose';
 *
 * const ConstrainedTabbingExample = () => {
 *     const constrainedTabbingRef = useConstrainedTabbing()
 *     return (
 *         <div ref={ constrainedTabbingRef }>
 *             <Button />
 *             <Button />
 *         </div>
 *     );
 * }
 * ```
 */

function useConstrainedTabbing() {
  return (0,_use_ref_effect__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)((
  /** @type {HTMLElement} */
  node) => {
    /** @type {number|undefined} */
    let timeoutId;

    function onKeyDown(
    /** @type {KeyboardEvent} */
    event) {
      const {
        keyCode,
        shiftKey,
        target
      } = event;

      if (keyCode !== _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_1__/* .TAB */ .wn) {
        return;
      }

      const action = shiftKey ? 'findPrevious' : 'findNext';
      const nextElement = _wordpress_dom__WEBPACK_IMPORTED_MODULE_2__/* .focus */ .XC.tabbable[action](
      /** @type {HTMLElement} */
      target) || null; // If the element that is about to receive focus is outside the
      // area, move focus to a div and insert it at the start or end of
      // the area, depending on the direction. Without preventing default
      // behaviour, the browser will then move focus to the next element.

      if (node.contains(nextElement)) {
        return;
      }

      const domAction = shiftKey ? 'append' : 'prepend';
      const {
        ownerDocument
      } = node;
      const trap = ownerDocument.createElement('div');
      trap.tabIndex = -1;
      node[domAction](trap);
      trap.focus(); // Remove after the browser moves focus to the next element.

      timeoutId = setTimeout(() => node.removeChild(trap));
    }

    node.addEventListener('keydown', onKeyDown);
    return () => {
      node.removeEventListener('keydown', onKeyDown);
      clearTimeout(timeoutId);
    };
  }, []);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useConstrainedTabbing);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useDebounce)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var use_memo_one__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/use-memo-one@1.1.3_react@17.0.2/node_modules/use-memo-one/dist/use-memo-one.esm.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/* eslint-disable jsdoc/valid-types */

/**
 * Debounces a function with Lodash's `debounce`. A new debounced function will
 * be returned and any scheduled calls cancelled if any of the arguments change,
 * including the function to debounce, so please wrap functions created on
 * render in components in `useCallback`.
 *
 * @see https://docs-lodash.com/v4/debounce/
 *
 * @template {(...args: any[]) => void} TFunc
 *
 * @param {TFunc}                             fn        The function to debounce.
 * @param {number}                            [wait]    The number of milliseconds to delay.
 * @param {import('lodash').DebounceSettings} [options] The options object.
 * @return {import('lodash').DebouncedFunc<TFunc>} Debounced function.
 */

function useDebounce(fn, wait, options) {
  /* eslint-enable jsdoc/valid-types */
  const debounced = (0,use_memo_one__WEBPACK_IMPORTED_MODULE_1__/* .useMemoOne */ .MA)(() => (0,lodash__WEBPACK_IMPORTED_MODULE_0__.debounce)(fn, wait, options), [fn, wait, options]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => () => debounced.cancel(), [debounced]);
  return debounced;
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-on-mount/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useFocusOnMount)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js");
/**
 * WordPress dependencies
 */


/**
 * Hook used to focus the first tabbable element on mount.
 *
 * @param {boolean | 'firstElement'} focusOnMount Focus on mount mode.
 * @return {import('react').RefCallback<HTMLElement>} Ref callback.
 *
 * @example
 * ```js
 * import { useFocusOnMount } from '@wordpress/compose';
 *
 * const WithFocusOnMount = () => {
 *     const ref = useFocusOnMount()
 *     return (
 *         <div ref={ ref }>
 *             <Button />
 *             <Button />
 *         </div>
 *     );
 * }
 * ```
 */

function useFocusOnMount() {
  let focusOnMount = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'firstElement';
  const focusOnMountRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(focusOnMount);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    focusOnMountRef.current = focusOnMount;
  }, [focusOnMount]);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(node => {
    var _node$ownerDocument$a, _node$ownerDocument;

    if (!node || focusOnMountRef.current === false) {
      return;
    }

    if (node.contains((_node$ownerDocument$a = (_node$ownerDocument = node.ownerDocument) === null || _node$ownerDocument === void 0 ? void 0 : _node$ownerDocument.activeElement) !== null && _node$ownerDocument$a !== void 0 ? _node$ownerDocument$a : null)) {
      return;
    }

    let target = node;

    if (focusOnMountRef.current === 'firstElement') {
      const firstTabbable = _wordpress_dom__WEBPACK_IMPORTED_MODULE_1__/* .focus */ .XC.tabbable.find(node)[0];

      if (firstTabbable) {
        target =
        /** @type {HTMLElement} */
        firstTabbable;
      }
    }

    target.focus();
  }, []);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-outside/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useFocusOutside)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Input types which are classified as button types, for use in considering
 * whether element is a (focus-normalized) button.
 *
 * @type {string[]}
 */

const INPUT_BUTTON_TYPES = ['button', 'submit'];
/**
 * @typedef {HTMLButtonElement | HTMLLinkElement | HTMLInputElement} FocusNormalizedButton
 */
// Disable reason: Rule doesn't support predicate return types.

/* eslint-disable jsdoc/valid-types */

/**
 * Returns true if the given element is a button element subject to focus
 * normalization, or false otherwise.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
 *
 * @param {EventTarget} eventTarget The target from a mouse or touch event.
 *
 * @return {eventTarget is FocusNormalizedButton} Whether element is a button.
 */

function isFocusNormalizedButton(eventTarget) {
  if (!(eventTarget instanceof window.HTMLElement)) {
    return false;
  }

  switch (eventTarget.nodeName) {
    case 'A':
    case 'BUTTON':
      return true;

    case 'INPUT':
      return (0,lodash__WEBPACK_IMPORTED_MODULE_0__.includes)(INPUT_BUTTON_TYPES,
      /** @type {HTMLInputElement} */
      eventTarget.type);
  }

  return false;
}
/* eslint-enable jsdoc/valid-types */

/**
 * @typedef {import('react').SyntheticEvent} SyntheticEvent
 */

/**
 * @callback EventCallback
 * @param {SyntheticEvent} event input related event.
 */

/**
 * @typedef FocusOutsideReactElement
 * @property {EventCallback} handleFocusOutside callback for a focus outside event.
 */

/**
 * @typedef {import('react').MutableRefObject<FocusOutsideReactElement | undefined>} FocusOutsideRef
 */

/**
 * @typedef {Object} FocusOutsideReturnValue
 * @property {EventCallback} onFocus      An event handler for focus events.
 * @property {EventCallback} onBlur       An event handler for blur events.
 * @property {EventCallback} onMouseDown  An event handler for mouse down events.
 * @property {EventCallback} onMouseUp    An event handler for mouse up events.
 * @property {EventCallback} onTouchStart An event handler for touch start events.
 * @property {EventCallback} onTouchEnd   An event handler for touch end events.
 */

/**
 * A react hook that can be used to check whether focus has moved outside the
 * element the event handlers are bound to.
 *
 * @param {EventCallback} onFocusOutside A callback triggered when focus moves outside
 *                                       the element the event handlers are bound to.
 *
 * @return {FocusOutsideReturnValue} An object containing event handlers. Bind the event handlers
 *                                   to a wrapping element element to capture when focus moves
 *                                   outside that element.
 */


function useFocusOutside(onFocusOutside) {
  const currentOnFocusOutside = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(onFocusOutside);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    currentOnFocusOutside.current = onFocusOutside;
  }, [onFocusOutside]);
  const preventBlurCheck = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(false);
  /**
   * @type {import('react').MutableRefObject<number | undefined>}
   */

  const blurCheckTimeoutId = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)();
  /**
   * Cancel a blur check timeout.
   */

  const cancelBlurCheck = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(() => {
    clearTimeout(blurCheckTimeoutId.current);
  }, []); // Cancel blur checks on unmount.

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    return () => cancelBlurCheck();
  }, []); // Cancel a blur check if the callback or ref is no longer provided.

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!onFocusOutside) {
      cancelBlurCheck();
    }
  }, [onFocusOutside, cancelBlurCheck]);
  /**
   * Handles a mousedown or mouseup event to respectively assign and
   * unassign a flag for preventing blur check on button elements. Some
   * browsers, namely Firefox and Safari, do not emit a focus event on
   * button elements when clicked, while others do. The logic here
   * intends to normalize this as treating click on buttons as focus.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
   *
   * @param {SyntheticEvent} event Event for mousedown or mouseup.
   */

  const normalizeButtonFocus = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(event => {
    const {
      type,
      target
    } = event;
    const isInteractionEnd = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.includes)(['mouseup', 'touchend'], type);

    if (isInteractionEnd) {
      preventBlurCheck.current = false;
    } else if (isFocusNormalizedButton(target)) {
      preventBlurCheck.current = true;
    }
  }, []);
  /**
   * A callback triggered when a blur event occurs on the element the handler
   * is bound to.
   *
   * Calls the `onFocusOutside` callback in an immediate timeout if focus has
   * move outside the bound element and is still within the document.
   *
   * @param {SyntheticEvent} event Blur event.
   */

  const queueBlurCheck = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(event => {
    // React does not allow using an event reference asynchronously
    // due to recycling behavior, except when explicitly persisted.
    event.persist(); // Skip blur check if clicking button. See `normalizeButtonFocus`.

    if (preventBlurCheck.current) {
      return;
    }

    blurCheckTimeoutId.current = setTimeout(() => {
      // If document is not focused then focus should remain
      // inside the wrapped component and therefore we cancel
      // this blur event thereby leaving focus in place.
      // https://developer.mozilla.org/en-US/docs/Web/API/Document/hasFocus.
      if (!document.hasFocus()) {
        event.preventDefault();
        return;
      }

      if ('function' === typeof currentOnFocusOutside.current) {
        currentOnFocusOutside.current(event);
      }
    }, 0);
  }, []);
  return {
    onFocus: cancelBlurCheck,
    onMouseDown: normalizeButtonFocus,
    onMouseUp: normalizeButtonFocus,
    onTouchStart: normalizeButtonFocus,
    onTouchEnd: normalizeButtonFocus,
    onBlur: queueBlurCheck
  };
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-return/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * WordPress dependencies
 */

/**
 * When opening modals/sidebars/dialogs, the focus
 * must move to the opened area and return to the
 * previously focused element when closed.
 * The current hook implements the returning behavior.
 *
 * @param {() => void} [onFocusReturn] Overrides the default return behavior.
 * @return {import('react').RefCallback<HTMLElement>} Element Ref.
 *
 * @example
 * ```js
 * import { useFocusReturn } from '@wordpress/compose';
 *
 * const WithFocusReturn = () => {
 *     const ref = useFocusReturn()
 *     return (
 *         <div ref={ ref }>
 *             <Button />
 *             <Button />
 *         </div>
 *     );
 * }
 * ```
 */

function useFocusReturn(onFocusReturn) {
  /** @type {import('react').MutableRefObject<null | HTMLElement>} */
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  /** @type {import('react').MutableRefObject<null | Element>} */

  const focusedBeforeMount = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const onFocusReturnRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(onFocusReturn);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    onFocusReturnRef.current = onFocusReturn;
  }, [onFocusReturn]);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(node => {
    if (node) {
      // Set ref to be used when unmounting.
      ref.current = node; // Only set when the node mounts.

      if (focusedBeforeMount.current) {
        return;
      }

      focusedBeforeMount.current = node.ownerDocument.activeElement;
    } else if (focusedBeforeMount.current) {
      var _ref$current, _ref$current2, _ref$current3;

      const isFocused = (_ref$current = ref.current) === null || _ref$current === void 0 ? void 0 : _ref$current.contains((_ref$current2 = ref.current) === null || _ref$current2 === void 0 ? void 0 : _ref$current2.ownerDocument.activeElement);

      if ((_ref$current3 = ref.current) !== null && _ref$current3 !== void 0 && _ref$current3.isConnected && !isFocused) {
        return;
      } // Defer to the component's own explicit focus return behavior, if
      // specified. This allows for support that the `onFocusReturn`
      // decides to allow the default behavior to occur under some
      // conditions.


      if (onFocusReturnRef.current) {
        onFocusReturnRef.current();
      } else {
        var _focusedBeforeMount$c;

        /** @type {null | HTMLElement} */
        (_focusedBeforeMount$c = focusedBeforeMount.current) === null || _focusedBeforeMount$c === void 0 ? void 0 : _focusedBeforeMount$c.focus();
      }
    }
  }, []);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useFocusReturn);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useInstanceId)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// Disable reason: Object and object are distinctly different types in TypeScript and we mean the lowercase object in thise case
// but eslint wants to force us to use `Object`. See https://stackoverflow.com/questions/49464634/difference-between-object-and-object-in-typescript

/* eslint-disable jsdoc/check-types */

/**
 * WordPress dependencies
 */

/**
 * @type {WeakMap<object, number>}
 */

const instanceMap = new WeakMap();
/**
 * Creates a new id for a given object.
 *
 * @param {object} object Object reference to create an id for.
 * @return {number} The instance id (index).
 */

function createId(object) {
  const instances = instanceMap.get(object) || 0;
  instanceMap.set(object, instances + 1);
  return instances;
}
/**
 * Provides a unique instance ID.
 *
 * @param {object}          object           Object reference to create an id for.
 * @param {string}          [prefix]         Prefix for the unique id.
 * @param {string | number} [preferredId=''] Default ID to use.
 * @return {string | number} The unique instance id.
 */


function useInstanceId(object, prefix) {
  let preferredId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    if (preferredId) return preferredId;
    const id = createId(object);
    return prefix ? `${prefix}-${id}` : id;
  }, [object]);
}
/* eslint-enable jsdoc/check-types */
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-media-query/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useMediaQuery)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * WordPress dependencies
 */

/**
 * Runs a media query and returns its value when it changes.
 *
 * @param {string} [query] Media Query.
 * @return {boolean} return value of the media query.
 */

function useMediaQuery(query) {
  const [match, setMatch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(() => !!(query && typeof window !== 'undefined' && window.matchMedia(query).matches));
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!query) {
      return;
    }

    const updateMatch = () => setMatch(window.matchMedia(query).matches);

    updateMatch();
    const list = window.matchMedia(query);
    list.addListener(updateMatch);
    return () => {
      list.removeListener(updateMatch);
    };
  }, [query]);
  return !!query && match;
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useMergeRefs)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * WordPress dependencies
 */

/* eslint-disable jsdoc/valid-types */

/**
 * @template T
 * @typedef {T extends import('react').Ref<infer R> ? R : never} TypeFromRef
 */

/* eslint-enable jsdoc/valid-types */

/**
 * @template T
 * @param {import('react').Ref<T>} ref
 * @param {T}                      value
 */

function assignRef(ref, value) {
  if (typeof ref === 'function') {
    ref(value);
  } else if (ref && ref.hasOwnProperty('current')) {
    /* eslint-disable jsdoc/no-undefined-types */

    /** @type {import('react').MutableRefObject<T>} */
    ref.current = value;
    /* eslint-enable jsdoc/no-undefined-types */
  }
}
/**
 * Merges refs into one ref callback.
 *
 * It also ensures that the merged ref callbacks are only called when they
 * change (as a result of a `useCallback` dependency update) OR when the ref
 * value changes, just as React does when passing a single ref callback to the
 * component.
 *
 * As expected, if you pass a new function on every render, the ref callback
 * will be called after every render.
 *
 * If you don't wish a ref callback to be called after every render, wrap it
 * with `useCallback( callback, dependencies )`. When a dependency changes, the
 * old ref callback will be called with `null` and the new ref callback will be
 * called with the same value.
 *
 * To make ref callbacks easier to use, you can also pass the result of
 * `useRefEffect`, which makes cleanup easier by allowing you to return a
 * cleanup function instead of handling `null`.
 *
 * It's also possible to _disable_ a ref (and its behaviour) by simply not
 * passing the ref.
 *
 * ```jsx
 * const ref = useRefEffect( ( node ) => {
 *   node.addEventListener( ... );
 *   return () => {
 *     node.removeEventListener( ... );
 *   };
 * }, [ ...dependencies ] );
 * const otherRef = useRef();
 * const mergedRefs useMergeRefs( [
 *   enabled && ref,
 *   otherRef,
 * ] );
 * return <div ref={ mergedRefs } />;
 * ```
 *
 * @template {import('react').Ref<any>} TRef
 * @param {Array<TRef>} refs The refs to be merged.
 *
 * @return {import('react').RefCallback<TypeFromRef<TRef>>} The merged ref callback.
 */


function useMergeRefs(refs) {
  const element = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const didElementChange = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  /* eslint-disable jsdoc/no-undefined-types */

  /** @type {import('react').MutableRefObject<TRef[]>} */

  /* eslint-enable jsdoc/no-undefined-types */

  const previousRefs = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)([]);
  const currentRefs = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(refs); // Update on render before the ref callback is called, so the ref callback
  // always has access to the current refs.

  currentRefs.current = refs; // If any of the refs change, call the previous ref with `null` and the new
  // ref with the node, except when the element changes in the same cycle, in
  // which case the ref callbacks will already have been called.

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect)(() => {
    if (didElementChange.current === false) {
      refs.forEach((ref, index) => {
        const previousRef = previousRefs.current[index];

        if (ref !== previousRef) {
          assignRef(previousRef, null);
          assignRef(ref, element.current);
        }
      });
    }

    previousRefs.current = refs;
  }, refs); // No dependencies, must be reset after every render so ref callbacks are
  // correctly called after a ref change.

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect)(() => {
    didElementChange.current = false;
  }); // There should be no dependencies so that `callback` is only called when
  // the node changes.

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(value => {
    // Update the element so it can be used when calling ref callbacks on a
    // dependency change.
    assignRef(element, value);
    didElementChange.current = true; // When an element changes, the current ref callback should be called
    // with the new element and the previous one with `null`.

    const refsToAssign = value ? currentRefs.current : previousRefs.current; // Update the latest refs.

    for (const ref of refsToAssign) {
      assignRef(ref, value);
    }
  }, []);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useRefEffect)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Effect-like ref callback. Just like with `useEffect`, this allows you to
 * return a cleanup function to be run if the ref changes or one of the
 * dependencies changes. The ref is provided as an argument to the callback
 * functions. The main difference between this and `useEffect` is that
 * the `useEffect` callback is not called when the ref changes, but this is.
 * Pass the returned ref callback as the component's ref and merge multiple refs
 * with `useMergeRefs`.
 *
 * It's worth noting that if the dependencies array is empty, there's not
 * strictly a need to clean up event handlers for example, because the node is
 * to be removed. It *is* necessary if you add dependencies because the ref
 * callback will be called multiple times for the same node.
 *
 * @param  callback     Callback with ref as argument.
 * @param  dependencies Dependencies of the callback.
 *
 * @return Ref callback.
 */

function useRefEffect(callback, dependencies) {
  const cleanup = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(node => {
    if (node) {
      cleanup.current = callback(node);
    } else if (cleanup.current) {
      cleanup.current();
    }
  }, dependencies);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  XC: () => (/* binding */ build_module_focus)
});

// UNUSED EXPORTS: __unstableStripHTML, computeCaretRect, documentHasSelection, documentHasTextSelection, documentHasUncollapsedSelection, getFilesFromDataTransfer, getOffsetParent, getPhrasingContentSchema, getRectangleFromRange, getScrollContainer, insertAfter, isEmpty, isEntirelySelected, isFormElement, isHorizontalEdge, isNumberInput, isPhrasingContent, isRTL, isTextContent, isTextField, isVerticalEdge, placeCaretAtHorizontalEdge, placeCaretAtVerticalEdge, remove, removeInvalidHTML, replace, replaceTag, safeHTML, unwrap, wrap

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/focusable.js
var focusable_namespaceObject = {};
__webpack_require__.r(focusable_namespaceObject);
__webpack_require__.d(focusable_namespaceObject, {
  find: () => (find)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/tabbable.js
var tabbable_namespaceObject = {};
__webpack_require__.r(tabbable_namespaceObject);
__webpack_require__.d(tabbable_namespaceObject, {
  find: () => (tabbable_find),
  findNext: () => (findNext),
  findPrevious: () => (findPrevious),
  isTabbableIndex: () => (isTabbableIndex)
});

;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/focusable.js
/**
 * References:
 *
 * Focusable:
 *  - https://www.w3.org/TR/html5/editing.html#focus-management
 *
 * Sequential focus navigation:
 *  - https://www.w3.org/TR/html5/editing.html#sequential-focus-navigation-and-the-tabindex-attribute
 *
 * Disabled elements:
 *  - https://www.w3.org/TR/html5/disabled-elements.html#disabled-elements
 *
 * getClientRects algorithm (requiring layout box):
 *  - https://www.w3.org/TR/cssom-view-1/#extension-to-the-element-interface
 *
 * AREA elements associated with an IMG:
 *  - https://w3c.github.io/html/editing.html#data-model
 */

/**
 * Returns a CSS selector used to query for focusable elements.
 *
 * @param {boolean} sequential If set, only query elements that are sequentially
 *                             focusable. Non-interactive elements with a
 *                             negative `tabindex` are focusable but not
 *                             sequentially focusable.
 *                             https://html.spec.whatwg.org/multipage/interaction.html#the-tabindex-attribute
 *
 * @return {string} CSS selector.
 */
function buildSelector(sequential) {
  return [sequential ? '[tabindex]:not([tabindex^="-"])' : '[tabindex]', 'a[href]', 'button:not([disabled])', 'input:not([type="hidden"]):not([disabled])', 'select:not([disabled])', 'textarea:not([disabled])', 'iframe:not([tabindex^="-"])', 'object', 'embed', 'area[href]', '[contenteditable]:not([contenteditable=false])'].join(',');
}
/**
 * Returns true if the specified element is visible (i.e. neither display: none
 * nor visibility: hidden).
 *
 * @param {HTMLElement} element DOM element to test.
 *
 * @return {boolean} Whether element is visible.
 */


function isVisible(element) {
  return element.offsetWidth > 0 || element.offsetHeight > 0 || element.getClientRects().length > 0;
}
/**
 * Returns true if the specified area element is a valid focusable element, or
 * false otherwise. Area is only focusable if within a map where a named map
 * referenced by an image somewhere in the document.
 *
 * @param {HTMLAreaElement} element DOM area element to test.
 *
 * @return {boolean} Whether area element is valid for focus.
 */


function isValidFocusableArea(element) {
  /** @type {HTMLMapElement | null} */
  const map = element.closest('map[name]');

  if (!map) {
    return false;
  }
  /** @type {HTMLImageElement | null} */


  const img = element.ownerDocument.querySelector('img[usemap="#' + map.name + '"]');
  return !!img && isVisible(img);
}
/**
 * Returns all focusable elements within a given context.
 *
 * @param {Element} context              Element in which to search.
 * @param {Object}  [options]
 * @param {boolean} [options.sequential] If set, only return elements that are
 *                                       sequentially focusable.
 *                                       Non-interactive elements with a
 *                                       negative `tabindex` are focusable but
 *                                       not sequentially focusable.
 *                                       https://html.spec.whatwg.org/multipage/interaction.html#the-tabindex-attribute
 *
 * @return {Element[]} Focusable elements.
 */


function find(context) {
  let {
    sequential = false
  } = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

  /* eslint-disable jsdoc/no-undefined-types */

  /** @type {NodeListOf<HTMLElement>} */

  /* eslint-enable jsdoc/no-undefined-types */
  const elements = context.querySelectorAll(buildSelector(sequential));
  return Array.from(elements).filter(element => {
    if (!isVisible(element)) {
      return false;
    }

    const {
      nodeName
    } = element;

    if ('AREA' === nodeName) {
      return isValidFocusableArea(
      /** @type {HTMLAreaElement} */
      element);
    }

    return true;
  });
}
//# sourceMappingURL=focusable.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/tabbable.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * Returns the tab index of the given element. In contrast with the tabIndex
 * property, this normalizes the default (0) to avoid browser inconsistencies,
 * operating under the assumption that this function is only ever called with a
 * focusable node.
 *
 * @see https://bugzilla.mozilla.org/show_bug.cgi?id=1190261
 *
 * @param {Element} element Element from which to retrieve.
 *
 * @return {number} Tab index of element (default 0).
 */

function getTabIndex(element) {
  const tabIndex = element.getAttribute('tabindex');
  return tabIndex === null ? 0 : parseInt(tabIndex, 10);
}
/**
 * Returns true if the specified element is tabbable, or false otherwise.
 *
 * @param {Element} element Element to test.
 *
 * @return {boolean} Whether element is tabbable.
 */


function isTabbableIndex(element) {
  return getTabIndex(element) !== -1;
}
/** @typedef {Element & { type?: string, checked?: boolean, name?: string }} MaybeHTMLInputElement */

/**
 * Returns a stateful reducer function which constructs a filtered array of
 * tabbable elements, where at most one radio input is selected for a given
 * name, giving priority to checked input, falling back to the first
 * encountered.
 *
 * @return {(acc: MaybeHTMLInputElement[], el: MaybeHTMLInputElement) => MaybeHTMLInputElement[]} Radio group collapse reducer.
 */

function createStatefulCollapseRadioGroup() {
  /** @type {Record<string, MaybeHTMLInputElement>} */
  const CHOSEN_RADIO_BY_NAME = {};
  return function collapseRadioGroup(
  /** @type {MaybeHTMLInputElement[]} */
  result,
  /** @type {MaybeHTMLInputElement} */
  element) {
    const {
      nodeName,
      type,
      checked,
      name
    } = element; // For all non-radio tabbables, construct to array by concatenating.

    if (nodeName !== 'INPUT' || type !== 'radio' || !name) {
      return result.concat(element);
    }

    const hasChosen = CHOSEN_RADIO_BY_NAME.hasOwnProperty(name); // Omit by skipping concatenation if the radio element is not chosen.

    const isChosen = checked || !hasChosen;

    if (!isChosen) {
      return result;
    } // At this point, if there had been a chosen element, the current
    // element is checked and should take priority. Retroactively remove
    // the element which had previously been considered the chosen one.


    if (hasChosen) {
      const hadChosenElement = CHOSEN_RADIO_BY_NAME[name];
      result = (0,lodash.without)(result, hadChosenElement);
    }

    CHOSEN_RADIO_BY_NAME[name] = element;
    return result.concat(element);
  };
}
/**
 * An array map callback, returning an object with the element value and its
 * array index location as properties. This is used to emulate a proper stable
 * sort where equal tabIndex should be left in order of their occurrence in the
 * document.
 *
 * @param {Element} element Element.
 * @param {number}  index   Array index of element.
 *
 * @return {{ element: Element, index: number }} Mapped object with element, index.
 */


function mapElementToObjectTabbable(element, index) {
  return {
    element,
    index
  };
}
/**
 * An array map callback, returning an element of the given mapped object's
 * element value.
 *
 * @param {{ element: Element }} object Mapped object with element.
 *
 * @return {Element} Mapped object element.
 */


function mapObjectTabbableToElement(object) {
  return object.element;
}
/**
 * A sort comparator function used in comparing two objects of mapped elements.
 *
 * @see mapElementToObjectTabbable
 *
 * @param {{ element: Element, index: number }} a First object to compare.
 * @param {{ element: Element, index: number }} b Second object to compare.
 *
 * @return {number} Comparator result.
 */


function compareObjectTabbables(a, b) {
  const aTabIndex = getTabIndex(a.element);
  const bTabIndex = getTabIndex(b.element);

  if (aTabIndex === bTabIndex) {
    return a.index - b.index;
  }

  return aTabIndex - bTabIndex;
}
/**
 * Givin focusable elements, filters out tabbable element.
 *
 * @param {Element[]} focusables Focusable elements to filter.
 *
 * @return {Element[]} Tabbable elements.
 */


function filterTabbable(focusables) {
  return focusables.filter(isTabbableIndex).map(mapElementToObjectTabbable).sort(compareObjectTabbables).map(mapObjectTabbableToElement).reduce(createStatefulCollapseRadioGroup(), []);
}
/**
 * @param {Element} context
 * @return {Element[]} Tabbable elements within the context.
 */


function tabbable_find(context) {
  return filterTabbable(find(context));
}
/**
 * Given a focusable element, find the preceding tabbable element.
 *
 * @param {Element} element The focusable element before which to look. Defaults
 *                          to the active element.
 *
 * @return {Element|undefined} Preceding tabbable element.
 */

function findPrevious(element) {
  const focusables = find(element.ownerDocument.body);
  const index = focusables.indexOf(element);

  if (index === -1) {
    return undefined;
  } // Remove all focusables after and including `element`.


  focusables.length = index;
  return (0,lodash.last)(filterTabbable(focusables));
}
/**
 * Given a focusable element, find the next tabbable element.
 *
 * @param {Element} element The focusable element after which to look. Defaults
 *                          to the active element.
 */

function findNext(element) {
  const focusables = find(element.ownerDocument.body);
  const index = focusables.indexOf(element); // Remove all focusables before and including `element`.

  const remaining = focusables.slice(index + 1);
  return (0,lodash.first)(filterTabbable(remaining));
}
//# sourceMappingURL=tabbable.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js
/**
 * Internal dependencies
 */


/**
 * Object grouping `focusable` and `tabbable` utils
 * under the keys with the same name.
 */

const build_module_focus = {
  focusable: focusable_namespaceObject,
  tabbable: tabbable_namespaceObject
};



//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/utils.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   s: () => (/* binding */ isEmptyElement)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Checks if the provided WP element is empty.
 *
 * @param {*} element WP element to check.
 * @return {boolean} True when an element is considered empty.
 */

const isEmptyElement = element => {
  if ((0,lodash__WEBPACK_IMPORTED_MODULE_0__.isNumber)(element)) {
    return false;
  }

  if ((0,lodash__WEBPACK_IMPORTED_MODULE_0__.isString)(element) || (0,lodash__WEBPACK_IMPORTED_MODULE_0__.isArray)(element)) {
    return !element.length;
  }

  return !element;
};
//# sourceMappingURL=utils.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@8.4.0/node_modules/@wordpress/icons/build-module/library/close.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@3.45.0/node_modules/@wordpress/primitives/build-module/svg/index.js");


/**
 * WordPress dependencies
 */

const close = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, {
  d: "M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (close);
//# sourceMappingURL=close.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.24.0/node_modules/@wordpress/is-shallow-equal/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ isShallowEqual)
});

// UNUSED EXPORTS: isShallowEqualArrays, isShallowEqualObjects

;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.24.0/node_modules/@wordpress/is-shallow-equal/build-module/objects.js
/**
 * Returns true if the two objects are shallow equal, or false otherwise.
 *
 * @param {import('.').ComparableObject} a First object to compare.
 * @param {import('.').ComparableObject} b Second object to compare.
 *
 * @return {boolean} Whether the two objects are shallow equal.
 */
function isShallowEqualObjects(a, b) {
  if (a === b) {
    return true;
  }

  const aKeys = Object.keys(a);
  const bKeys = Object.keys(b);

  if (aKeys.length !== bKeys.length) {
    return false;
  }

  let i = 0;

  while (i < aKeys.length) {
    const key = aKeys[i];
    const aValue = a[key];

    if ( // In iterating only the keys of the first object after verifying
    // equal lengths, account for the case that an explicit `undefined`
    // value in the first is implicitly undefined in the second.
    //
    // Example: isShallowEqualObjects( { a: undefined }, { b: 5 } )
    aValue === undefined && !b.hasOwnProperty(key) || aValue !== b[key]) {
      return false;
    }

    i++;
  }

  return true;
}
//# sourceMappingURL=objects.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.24.0/node_modules/@wordpress/is-shallow-equal/build-module/arrays.js
/**
 * Returns true if the two arrays are shallow equal, or false otherwise.
 *
 * @param {any[]} a First array to compare.
 * @param {any[]} b Second array to compare.
 *
 * @return {boolean} Whether the two arrays are shallow equal.
 */
function isShallowEqualArrays(a, b) {
  if (a === b) {
    return true;
  }

  if (a.length !== b.length) {
    return false;
  }

  for (let i = 0, len = a.length; i < len; i++) {
    if (a[i] !== b[i]) {
      return false;
    }
  }

  return true;
}
//# sourceMappingURL=arrays.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.24.0/node_modules/@wordpress/is-shallow-equal/build-module/index.js
/**
 * Internal dependencies
 */




/**
 * @typedef {Record<string, any>} ComparableObject
 */

/**
 * Returns true if the two arrays or objects are shallow equal, or false
 * otherwise.
 *
 * @param {any[]|ComparableObject} a First object or array to compare.
 * @param {any[]|ComparableObject} b Second object or array to compare.
 *
 * @return {boolean} Whether the two values are shallow equal.
 */

function isShallowEqual(a, b) {
  if (a && b) {
    if (a.constructor === Object && b.constructor === Object) {
      return isShallowEqualObjects(a, b);
    } else if (Array.isArray(a) && Array.isArray(b)) {
      return isShallowEqualArrays(a, b);
    }
  }

  return a === b;
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  G_: () => (/* binding */ BACKSPACE),
  PX: () => (/* binding */ DOWN),
  Fm: () => (/* binding */ ENTER),
  _f: () => (/* binding */ ESCAPE),
  M3: () => (/* binding */ LEFT),
  NS: () => (/* binding */ RIGHT),
  t6: () => (/* binding */ SPACE),
  wn: () => (/* binding */ TAB),
  UP: () => (/* binding */ UP),
  dz: () => (/* binding */ displayShortcut),
  JF: () => (/* binding */ rawShortcut),
  _A: () => (/* binding */ shortcutAriaLabel)
});

// UNUSED EXPORTS: ALT, COMMAND, CTRL, DELETE, END, F10, HOME, PAGEDOWN, PAGEUP, SHIFT, ZERO, displayShortcutList, isKeyboardEvent, modifiers

// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/platform.js
/**
 * External dependencies
 */

/**
 * Return true if platform is MacOS.
 *
 * @param {Window?} _window window object by default; used for DI testing.
 *
 * @return {boolean} True if MacOS; false otherwise.
 */

function isAppleOS() {
  let _window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

  if (!_window) {
    if (typeof window === 'undefined') {
      return false;
    }

    _window = window;
  }

  const {
    platform
  } = _window.navigator;
  return platform.indexOf('Mac') !== -1 || (0,lodash.includes)(['iPad', 'iPhone'], platform);
}
//# sourceMappingURL=platform.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js
/**
 * Note: The order of the modifier keys in many of the [foo]Shortcut()
 * functions in this file are intentional and should not be changed. They're
 * designed to fit with the standard menu keyboard shortcuts shown in the
 * user's platform.
 *
 * For example, on MacOS menu shortcuts will place Shift before Command, but
 * on Windows Control will usually come first. So don't provide your own
 * shortcut combos directly to keyboardShortcut().
 */

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/** @typedef {typeof ALT | CTRL | COMMAND | SHIFT } WPModifierPart */

/** @typedef {'primary' | 'primaryShift' | 'primaryAlt' | 'secondary' | 'access' | 'ctrl' | 'alt' | 'ctrlShift' | 'shift' | 'shiftAlt' | 'undefined'} WPKeycodeModifier */

/**
 * An object of handler functions for each of the possible modifier
 * combinations. A handler will return a value for a given key.
 *
 * @template T
 *
 * @typedef {Record<WPKeycodeModifier, T>} WPModifierHandler
 */

/**
 * @template T
 *
 * @typedef {(character: string, isApple?: () => boolean) => T} WPKeyHandler
 */

/** @typedef {(event: KeyboardEvent, character: string, isApple?: () => boolean) => boolean} WPEventKeyHandler */

/**
 * Keycode for BACKSPACE key.
 */

const BACKSPACE = 8;
/**
 * Keycode for TAB key.
 */

const TAB = 9;
/**
 * Keycode for ENTER key.
 */

const ENTER = 13;
/**
 * Keycode for ESCAPE key.
 */

const ESCAPE = 27;
/**
 * Keycode for SPACE key.
 */

const SPACE = 32;
/**
 * Keycode for PAGEUP key.
 */

const PAGEUP = 33;
/**
 * Keycode for PAGEDOWN key.
 */

const PAGEDOWN = 34;
/**
 * Keycode for END key.
 */

const END = 35;
/**
 * Keycode for HOME key.
 */

const HOME = 36;
/**
 * Keycode for LEFT key.
 */

const LEFT = 37;
/**
 * Keycode for UP key.
 */

const UP = 38;
/**
 * Keycode for RIGHT key.
 */

const RIGHT = 39;
/**
 * Keycode for DOWN key.
 */

const DOWN = 40;
/**
 * Keycode for DELETE key.
 */

const DELETE = 46;
/**
 * Keycode for F10 key.
 */

const F10 = 121;
/**
 * Keycode for ALT key.
 */

const ALT = 'alt';
/**
 * Keycode for CTRL key.
 */

const CTRL = 'ctrl';
/**
 * Keycode for COMMAND/META key.
 */

const COMMAND = 'meta';
/**
 * Keycode for SHIFT key.
 */

const SHIFT = 'shift';
/**
 * Keycode for ZERO key.
 */

const ZERO = 48;
/**
 * Object that contains functions that return the available modifier
 * depending on platform.
 *
 * @type {WPModifierHandler< ( isApple: () => boolean ) => WPModifierPart[]>}
 */

const modifiers = {
  primary: _isApple => _isApple() ? [COMMAND] : [CTRL],
  primaryShift: _isApple => _isApple() ? [SHIFT, COMMAND] : [CTRL, SHIFT],
  primaryAlt: _isApple => _isApple() ? [ALT, COMMAND] : [CTRL, ALT],
  secondary: _isApple => _isApple() ? [SHIFT, ALT, COMMAND] : [CTRL, SHIFT, ALT],
  access: _isApple => _isApple() ? [CTRL, ALT] : [SHIFT, ALT],
  ctrl: () => [CTRL],
  alt: () => [ALT],
  ctrlShift: () => [CTRL, SHIFT],
  shift: () => [SHIFT],
  shiftAlt: () => [SHIFT, ALT],
  undefined: () => []
};
/**
 * An object that contains functions to get raw shortcuts.
 *
 * These are intended for user with the KeyboardShortcuts.
 *
 * @example
 * ```js
 * // Assuming macOS:
 * rawShortcut.primary( 'm' )
 * // "meta+m""
 * ```
 *
 * @type {WPModifierHandler<WPKeyHandler<string>>} Keyed map of functions to raw
 *                                                 shortcuts.
 */

const rawShortcut = (0,lodash.mapValues)(modifiers, modifier => {
  return (
    /** @type {WPKeyHandler<string>} */
    function (character) {
      let _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

      return [...modifier(_isApple), character.toLowerCase()].join('+');
    }
  );
});
/**
 * Return an array of the parts of a keyboard shortcut chord for display.
 *
 * @example
 * ```js
 * // Assuming macOS:
 * displayShortcutList.primary( 'm' );
 * // [ "⌘", "M" ]
 * ```
 *
 * @type {WPModifierHandler<WPKeyHandler<string[]>>} Keyed map of functions to
 *                                                   shortcut sequences.
 */

const displayShortcutList = (0,lodash.mapValues)(modifiers, modifier => {
  return (
    /** @type {WPKeyHandler<string[]>} */
    function (character) {
      let _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

      const isApple = _isApple();

      const replacementKeyMap = {
        [ALT]: isApple ? '⌥' : 'Alt',
        [CTRL]: isApple ? '⌃' : 'Ctrl',
        // Make sure ⌃ is the U+2303 UP ARROWHEAD unicode character and not the caret character.
        [COMMAND]: '⌘',
        [SHIFT]: isApple ? '⇧' : 'Shift'
      };
      const modifierKeys = modifier(_isApple).reduce((accumulator, key) => {
        const replacementKey = (0,lodash.get)(replacementKeyMap, key, key); // If on the Mac, adhere to platform convention and don't show plus between keys.

        if (isApple) {
          return [...accumulator, replacementKey];
        }

        return [...accumulator, replacementKey, '+'];
      },
      /** @type {string[]} */
      []);
      const capitalizedCharacter = (0,lodash.capitalize)(character);
      return [...modifierKeys, capitalizedCharacter];
    }
  );
});
/**
 * An object that contains functions to display shortcuts.
 *
 * @example
 * ```js
 * // Assuming macOS:
 * displayShortcut.primary( 'm' );
 * // "⌘M"
 * ```
 *
 * @type {WPModifierHandler<WPKeyHandler<string>>} Keyed map of functions to
 *                                                 display shortcuts.
 */

const displayShortcut = (0,lodash.mapValues)(displayShortcutList, shortcutList => {
  return (
    /** @type {WPKeyHandler<string>} */
    function (character) {
      let _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

      return shortcutList(character, _isApple).join('');
    }
  );
});
/**
 * An object that contains functions to return an aria label for a keyboard
 * shortcut.
 *
 * @example
 * ```js
 * // Assuming macOS:
 * shortcutAriaLabel.primary( '.' );
 * // "Command + Period"
 * ```
 *
 * @type {WPModifierHandler<WPKeyHandler<string>>} Keyed map of functions to
 *                                                 shortcut ARIA labels.
 */

const shortcutAriaLabel = (0,lodash.mapValues)(modifiers, modifier => {
  return (
    /** @type {WPKeyHandler<string>} */
    function (character) {
      let _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

      const isApple = _isApple();

      const replacementKeyMap = {
        [SHIFT]: 'Shift',
        [COMMAND]: isApple ? 'Command' : 'Control',
        [CTRL]: 'Control',
        [ALT]: isApple ? 'Option' : 'Alt',

        /* translators: comma as in the character ',' */
        ',': (0,build_module.__)('Comma'),

        /* translators: period as in the character '.' */
        '.': (0,build_module.__)('Period'),

        /* translators: backtick as in the character '`' */
        '`': (0,build_module.__)('Backtick')
      };
      return [...modifier(_isApple), character].map(key => (0,lodash.capitalize)((0,lodash.get)(replacementKeyMap, key, key))).join(isApple ? ' ' : ' + ');
    }
  );
});
/**
 * From a given KeyboardEvent, returns an array of active modifier constants for
 * the event.
 *
 * @param {KeyboardEvent} event Keyboard event.
 *
 * @return {Array<WPModifierPart>} Active modifier constants.
 */

function getEventModifiers(event) {
  return (
    /** @type {WPModifierPart[]} */
    [ALT, CTRL, COMMAND, SHIFT].filter(key => event[
    /** @type {'altKey' | 'ctrlKey' | 'metaKey' | 'shiftKey'} */
    `${key}Key`])
  );
}
/**
 * An object that contains functions to check if a keyboard event matches a
 * predefined shortcut combination.
 *
 * @example
 * ```js
 * // Assuming an event for ⌘M key press:
 * isKeyboardEvent.primary( event, 'm' );
 * // true
 * ```
 *
 * @type {WPModifierHandler<WPEventKeyHandler>} Keyed map of functions
 *                                                       to match events.
 */


const isKeyboardEvent = (0,lodash.mapValues)(modifiers, getModifiers => {
  return (
    /** @type {WPEventKeyHandler} */
    function (event, character) {
      let _isApple = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : isAppleOS;

      const mods = getModifiers(_isApple);
      const eventMods = getEventModifiers(event);

      if ((0,lodash.xor)(mods, eventMods).length) {
        return false;
      }

      let key = event.key.toLowerCase();

      if (!character) {
        return (0,lodash.includes)(mods, key);
      }

      if (event.altKey && character.length === 1) {
        key = String.fromCharCode(event.keyCode).toLowerCase();
      } // For backwards compatibility.


      if (character === 'del') {
        character = 'delete';
      }

      return key === character.toLowerCase();
    }
  );
});
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+primitives@3.4.1/node_modules/@wordpress/primitives/build-module/svg/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   G: () => (/* binding */ G),
/* harmony export */   rw: () => (/* binding */ Rect),
/* harmony export */   t4: () => (/* binding */ SVG),
/* harmony export */   wA: () => (/* binding */ Path)
/* harmony export */ });
/* unused harmony exports Circle, Polygon, Defs, RadialGradient, LinearGradient, Stop */
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/** @typedef {{isPressed?: boolean} & import('react').ComponentPropsWithoutRef<'svg'>} SVGProps */

/**
 * @param {import('react').ComponentPropsWithoutRef<'circle'>} props
 *
 * @return {JSX.Element} Circle component
 */

const Circle = props => createElement('circle', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'g'>} props
 *
 * @return {JSX.Element} G component
 */

const G = props => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)('g', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'path'>} props
 *
 * @return {JSX.Element} Path component
 */

const Path = props => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)('path', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'polygon'>} props
 *
 * @return {JSX.Element} Polygon component
 */

const Polygon = props => createElement('polygon', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'rect'>} props
 *
 * @return {JSX.Element} Rect component
 */

const Rect = props => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)('rect', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'defs'>} props
 *
 * @return {JSX.Element} Defs component
 */

const Defs = props => createElement('defs', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'radialGradient'>} props
 *
 * @return {JSX.Element} RadialGradient component
 */

const RadialGradient = props => createElement('radialGradient', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'linearGradient'>} props
 *
 * @return {JSX.Element} LinearGradient component
 */

const LinearGradient = props => createElement('linearGradient', props);
/**
 * @param {import('react').ComponentPropsWithoutRef<'stop'>} props
 *
 * @return {JSX.Element} Stop component
 */

const Stop = props => createElement('stop', props);
/**
 *
 * @param {SVGProps} props isPressed indicates whether the SVG should appear as pressed.
 *                         Other props will be passed through to svg component.
 *
 * @return {JSX.Element} Stop component
 */

const SVG = _ref => {
  let {
    className,
    isPressed,
    ...props
  } = _ref;
  const appliedProps = { ...props,
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()(className, {
      'is-pressed': isPressed
    }) || undefined,
    'aria-hidden': true,
    focusable: false
  }; // Disable reason: We need to have a way to render HTML tag for web.
  // eslint-disable-next-line react/forbid-elements

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("svg", appliedProps);
};
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+primitives@3.45.0/node_modules/@wordpress/primitives/build-module/svg/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t4: () => (/* binding */ SVG),
/* harmony export */   wA: () => (/* binding */ Path)
/* harmony export */ });
/* unused harmony exports Circle, G, Line, Polygon, Rect, Defs, RadialGradient, LinearGradient, Stop */
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/** @typedef {{isPressed?: boolean} & import('react').ComponentPropsWithoutRef<'svg'>} SVGProps */

/**
 * @param {import('react').ComponentPropsWithoutRef<'circle'>} props
 *
 * @return {JSX.Element} Circle component
 */
const Circle = props => createElement('circle', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'g'>} props
 *
 * @return {JSX.Element} G component
 */
const G = props => createElement('g', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'line'>} props
 *
 * @return {JSX.Element} Path component
 */
const Line = props => createElement('line', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'path'>} props
 *
 * @return {JSX.Element} Path component
 */
const Path = props => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)('path', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'polygon'>} props
 *
 * @return {JSX.Element} Polygon component
 */
const Polygon = props => createElement('polygon', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'rect'>} props
 *
 * @return {JSX.Element} Rect component
 */
const Rect = props => createElement('rect', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'defs'>} props
 *
 * @return {JSX.Element} Defs component
 */
const Defs = props => createElement('defs', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'radialGradient'>} props
 *
 * @return {JSX.Element} RadialGradient component
 */
const RadialGradient = props => createElement('radialGradient', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'linearGradient'>} props
 *
 * @return {JSX.Element} LinearGradient component
 */
const LinearGradient = props => createElement('linearGradient', props);

/**
 * @param {import('react').ComponentPropsWithoutRef<'stop'>} props
 *
 * @return {JSX.Element} Stop component
 */
const Stop = props => createElement('stop', props);
const SVG = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.forwardRef)(
/**
 * @param {SVGProps}                                    props isPressed indicates whether the SVG should appear as pressed.
 *                                                            Other props will be passed through to svg component.
 * @param {import('react').ForwardedRef<SVGSVGElement>} ref   The forwarded ref to the SVG element.
 *
 * @return {JSX.Element} Stop component
 */
({
  className,
  isPressed,
  ...props
}, ref) => {
  const appliedProps = {
    ...props,
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()(className, {
      'is-pressed': isPressed
    }) || undefined,
    'aria-hidden': true,
    focusable: false
  };

  // Disable reason: We need to have a way to render HTML tag for web.
  // eslint-disable-next-line react/forbid-elements
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("svg", {
    ...appliedProps,
    ref: ref
  });
});
SVG.displayName = 'SVG';
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js":
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;
	var nativeCodeString = '[native code]';

	function classNames() {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg)) {
				if (arg.length) {
					var inner = classNames.apply(null, arg);
					if (inner) {
						classes.push(inner);
					}
				}
			} else if (argType === 'object') {
				if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
					classes.push(arg.toString());
					continue;
				}

				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "../../node_modules/.pnpm/react-resize-aware@3.1.1_react@17.0.2/node_modules/react-resize-aware/dist/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var e=__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js"),n={display:"block",opacity:0,position:"absolute",top:0,left:0,height:"100%",width:"100%",overflow:"hidden",pointerEvents:"none",zIndex:-1},t=function(t){var r=t.onResize,u=e.useRef();return function(n,t){var r=function(){return n.current&&n.current.contentDocument&&n.current.contentDocument.defaultView};function u(){t();var e=r();e&&e.addEventListener("resize",t)}e.useEffect((function(){return r()?u():n.current&&n.current.addEventListener&&n.current.addEventListener("load",u),function(){var e=r();e&&"function"==typeof e.removeEventListener&&e.removeEventListener("resize",t)}}),[])}(u,(function(){return r(u)})),e.createElement("iframe",{style:n,src:"about:blank",ref:u,"aria-hidden":!0,tabIndex:-1,frameBorder:0})},r=function(e){return{width:null!=e?e.offsetWidth:null,height:null!=e?e.offsetHeight:null}};module.exports=function(n){void 0===n&&(n=r);var u=e.useState(n(null)),o=u[0],i=u[1],c=e.useCallback((function(e){return i(n(e.current))}),[n]);return[e.useMemo((function(){return e.createElement(t,{onResize:c})}),[c]),o]};
//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/use-memo-one@1.1.3_react@17.0.2/node_modules/use-memo-one/dist/use-memo-one.esm.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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




/***/ }),

/***/ "../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/v4.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ esm_browser_v4)
});

;// CONCATENATED MODULE: ../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/rng.js
// Unique ID creation requires a high quality random # generator. In the browser we therefore
// require the crypto API and do not support built-in fallback to lower quality random number
// generators (like Math.random()).
var getRandomValues;
var rnds8 = new Uint8Array(16);
function rng() {
  // lazy load so that environments that need to polyfill have a chance to do so
  if (!getRandomValues) {
    // getRandomValues needs to be invoked in a context where "this" is a Crypto implementation. Also,
    // find the complete implementation of crypto (msCrypto) on IE11.
    getRandomValues = typeof crypto !== 'undefined' && crypto.getRandomValues && crypto.getRandomValues.bind(crypto) || typeof msCrypto !== 'undefined' && typeof msCrypto.getRandomValues === 'function' && msCrypto.getRandomValues.bind(msCrypto);

    if (!getRandomValues) {
      throw new Error('crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported');
    }
  }

  return getRandomValues(rnds8);
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/regex.js
/* harmony default export */ const regex = (/^(?:[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}|00000000-0000-0000-0000-000000000000)$/i);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/validate.js


function validate(uuid) {
  return typeof uuid === 'string' && regex.test(uuid);
}

/* harmony default export */ const esm_browser_validate = (validate);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/stringify.js

/**
 * Convert array of 16 byte values to UUID string format of the form:
 * XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
 */

var byteToHex = [];

for (var i = 0; i < 256; ++i) {
  byteToHex.push((i + 0x100).toString(16).substr(1));
}

function stringify(arr) {
  var offset = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  // Note: Be careful editing this code!  It's been tuned for performance
  // and works in ways you may not expect. See https://github.com/uuidjs/uuid/pull/434
  var uuid = (byteToHex[arr[offset + 0]] + byteToHex[arr[offset + 1]] + byteToHex[arr[offset + 2]] + byteToHex[arr[offset + 3]] + '-' + byteToHex[arr[offset + 4]] + byteToHex[arr[offset + 5]] + '-' + byteToHex[arr[offset + 6]] + byteToHex[arr[offset + 7]] + '-' + byteToHex[arr[offset + 8]] + byteToHex[arr[offset + 9]] + '-' + byteToHex[arr[offset + 10]] + byteToHex[arr[offset + 11]] + byteToHex[arr[offset + 12]] + byteToHex[arr[offset + 13]] + byteToHex[arr[offset + 14]] + byteToHex[arr[offset + 15]]).toLowerCase(); // Consistency check for valid UUID.  If this throws, it's likely due to one
  // of the following:
  // - One or more input array values don't map to a hex octet (leading to
  // "undefined" in the uuid)
  // - Invalid input values for the RFC `version` or `variant` fields

  if (!esm_browser_validate(uuid)) {
    throw TypeError('Stringified UUID is invalid');
  }

  return uuid;
}

/* harmony default export */ const esm_browser_stringify = (stringify);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/uuid@8.3.2/node_modules/uuid/dist/esm-browser/v4.js



function v4(options, buf, offset) {
  options = options || {};
  var rnds = options.random || (options.rng || rng)(); // Per 4.4, set bits for version and `clock_seq_hi_and_reserved`

  rnds[6] = rnds[6] & 0x0f | 0x40;
  rnds[8] = rnds[8] & 0x3f | 0x80; // Copy bytes to buffer, if provided

  if (buf) {
    offset = offset || 0;

    for (var i = 0; i < 16; ++i) {
      buf[offset + i] = rnds[i];
    }

    return buf;
  }

  return esm_browser_stringify(rnds);
}

/* harmony default export */ const esm_browser_v4 = (v4);

/***/ })

}]);