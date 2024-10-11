"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3847],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ Dropdown)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");


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



function useObservableState(initialState, onStateChange) {
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(initialState);
  return [state, value => {
    setState(value);

    if (onStateChange) {
      onStateChange(value);
    }
  }];
}

function Dropdown(props) {
  var _popoverProps$anchorR;

  const {
    renderContent,
    renderToggle,
    position = 'bottom right',
    className,
    contentClassName,
    expandOnMobile,
    headerTitle,
    focusOnMount,
    popoverProps,
    onClose,
    onToggle
  } = props;
  const containerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)();
  const [isOpen, setIsOpen] = useObservableState(false, onToggle);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => () => {
    if (onToggle) {
      onToggle(false);
    }
  }, []);

  function toggle() {
    setIsOpen(!isOpen);
  }
  /**
   * Closes the popover when focus leaves it unless the toggle was pressed or
   * focus has moved to a separate dialog. The former is to let the toggle
   * handle closing the popover and the latter is to preserve presence in
   * case a dialog has opened, allowing focus to return when it's dismissed.
   */


  function closeIfFocusOutside() {
    const {
      ownerDocument
    } = containerRef.current;
    const dialog = ownerDocument.activeElement.closest('[role="dialog"]');

    if (!containerRef.current.contains(ownerDocument.activeElement) && (!dialog || dialog.contains(containerRef.current))) {
      close();
    }
  }

  function close() {
    if (onClose) {
      onClose();
    }

    setIsOpen(false);
  }

  const args = {
    isOpen,
    onToggle: toggle,
    onClose: close
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('components-dropdown', className),
    ref: containerRef // Some UAs focus the closest focusable parent when the toggle is
    // clicked. Making this div focusable ensures such UAs will focus
    // it and `closeIfFocusOutside` can tell if the toggle was clicked.
    ,
    tabIndex: "-1"
  }, renderToggle(args), isOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_popover__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)({
    position: position,
    onClose: close,
    onFocusOutside: closeIfFocusOutside,
    expandOnMobile: expandOnMobile,
    headerTitle: headerTitle,
    focusOnMount: focusOnMount
  }, popoverProps, {
    anchorRef: (_popoverProps$anchorR = popoverProps === null || popoverProps === void 0 ? void 0 : popoverProps.anchorRef) !== null && _popoverProps$anchorR !== void 0 ? _popoverProps$anchorR : containerRef.current,
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('components-dropdown__content', popoverProps ? popoverProps.className : undefined, contentClassName)
  }), renderContent(args)));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/index.js":
/***/ (() => {

// Primitives.
 // Components.
























































































































 // Higher-Order Components.









//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/menu.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ menu)
});

// UNUSED EXPORTS: NavigableMenu

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@3.47.0/node_modules/@wordpress/keycodes/build-module/index.js + 2 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@3.47.0/node_modules/@wordpress/keycodes/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js + 2 modules
var dom_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/container.js


// @ts-nocheck

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



const MENU_ITEM_ROLES = ['menuitem', 'menuitemradio', 'menuitemcheckbox'];

function cycleValue(value, total, offset) {
  const nextValue = value + offset;

  if (nextValue < 0) {
    return total + nextValue;
  } else if (nextValue >= total) {
    return nextValue - total;
  }

  return nextValue;
}

class NavigableContainer extends react.Component {
  constructor() {
    super(...arguments);
    this.onKeyDown = this.onKeyDown.bind(this);
    this.bindContainer = this.bindContainer.bind(this);
    this.getFocusableContext = this.getFocusableContext.bind(this);
    this.getFocusableIndex = this.getFocusableIndex.bind(this);
  }

  componentDidMount() {
    // We use DOM event listeners instead of React event listeners
    // because we want to catch events from the underlying DOM tree
    // The React Tree can be different from the DOM tree when using
    // portals. Block Toolbars for instance are rendered in a separate
    // React Trees.
    this.container.addEventListener('keydown', this.onKeyDown);
    this.container.addEventListener('focus', this.onFocus);
  }

  componentWillUnmount() {
    this.container.removeEventListener('keydown', this.onKeyDown);
    this.container.removeEventListener('focus', this.onFocus);
  }

  bindContainer(ref) {
    const {
      forwardedRef
    } = this.props;
    this.container = ref;

    if ((0,lodash.isFunction)(forwardedRef)) {
      forwardedRef(ref);
    } else if (forwardedRef && 'current' in forwardedRef) {
      forwardedRef.current = ref;
    }
  }

  getFocusableContext(target) {
    const {
      onlyBrowserTabstops
    } = this.props;
    const finder = onlyBrowserTabstops ? dom_build_module/* focus */.XC.tabbable : dom_build_module/* focus */.XC.focusable;
    const focusables = finder.find(this.container);
    const index = this.getFocusableIndex(focusables, target);

    if (index > -1 && target) {
      return {
        index,
        target,
        focusables
      };
    }

    return null;
  }

  getFocusableIndex(focusables, target) {
    const directIndex = focusables.indexOf(target);

    if (directIndex !== -1) {
      return directIndex;
    }
  }

  onKeyDown(event) {
    if (this.props.onKeyDown) {
      this.props.onKeyDown(event);
    }

    const {
      getFocusableContext
    } = this;
    const {
      cycle = true,
      eventToOffset,
      onNavigate = lodash.noop,
      stopNavigationEvents
    } = this.props;
    const offset = eventToOffset(event); // eventToOffset returns undefined if the event is not handled by the component.

    if (offset !== undefined && stopNavigationEvents) {
      // Prevents arrow key handlers bound to the document directly interfering.
      event.stopImmediatePropagation(); // When navigating a collection of items, prevent scroll containers
      // from scrolling. The preventDefault also prevents Voiceover from
      // 'handling' the event, as voiceover will try to use arrow keys
      // for highlighting text.

      const targetRole = event.target.getAttribute('role');

      if (MENU_ITEM_ROLES.includes(targetRole)) {
        event.preventDefault();
      }
    }

    if (!offset) {
      return;
    }

    const context = getFocusableContext(event.target.ownerDocument.activeElement);

    if (!context) {
      return;
    }

    const {
      index,
      focusables
    } = context;
    const nextIndex = cycle ? cycleValue(index, focusables.length, offset) : index + offset;

    if (nextIndex >= 0 && nextIndex < focusables.length) {
      focusables[nextIndex].focus();
      onNavigate(nextIndex, focusables[nextIndex]);
    }
  }

  render() {
    const {
      children,
      ...props
    } = this.props;
    return (0,react.createElement)("div", (0,esm_extends/* default */.A)({
      ref: this.bindContainer
    }, (0,lodash.omit)(props, ['stopNavigationEvents', 'eventToOffset', 'onNavigate', 'onKeyDown', 'cycle', 'onlyBrowserTabstops', 'forwardedRef'])), children);
  }

}

const forwardedNavigableContainer = (props, ref) => {
  return (0,react.createElement)(NavigableContainer, (0,esm_extends/* default */.A)({}, props, {
    forwardedRef: ref
  }));
};

forwardedNavigableContainer.displayName = 'NavigableContainer';
/* harmony default export */ const container = ((0,react.forwardRef)(forwardedNavigableContainer));
//# sourceMappingURL=container.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/menu.js


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


function NavigableMenu(_ref, ref) {
  let {
    role = 'menu',
    orientation = 'vertical',
    ...rest
  } = _ref;

  const eventToOffset = evt => {
    const {
      keyCode
    } = evt;
    let next = [build_module/* DOWN */.PX];
    let previous = [build_module.UP];

    if (orientation === 'horizontal') {
      next = [build_module/* RIGHT */.NS];
      previous = [build_module/* LEFT */.M3];
    }

    if (orientation === 'both') {
      next = [build_module/* RIGHT */.NS, build_module/* DOWN */.PX];
      previous = [build_module/* LEFT */.M3, build_module.UP];
    }

    if ((0,lodash.includes)(next, keyCode)) {
      return 1;
    } else if ((0,lodash.includes)(previous, keyCode)) {
      return -1;
    } else if ((0,lodash.includes)([build_module/* DOWN */.PX, build_module.UP, build_module/* LEFT */.M3, build_module/* RIGHT */.NS], keyCode)) {
      // Key press should be handled, e.g. have event propagation and
      // default behavior handled by NavigableContainer but not result
      // in an offset.
      return 0;
    }
  };

  return (0,react.createElement)(container, (0,esm_extends/* default */.A)({
    ref: ref,
    stopNavigationEvents: true,
    onlyBrowserTabstops: false,
    role: role,
    "aria-orientation": role === 'presentation' ? null : orientation,
    eventToOffset: eventToOffset
  }, rest));
}
/* harmony default export */ const menu = ((0,react.forwardRef)(NavigableMenu));
//# sourceMappingURL=menu.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   x: () => (/* binding */ space)
/* harmony export */ });
/**
 * A real number or something parsable as a number
 */
const GRID_BASE = '4px';
/**
 * A function that handles numbers, numeric strings, and unit values.
 *
 * When given a number or a numeric string, it will return the grid-based
 * value as a factor of GRID_BASE, defined above.
 *
 * When given a unit value or one of the named CSS values like `auto`,
 * it will simply return the value back.
 *
 * @param  value A number, numeric string, or a unit value.
 */

function space(value) {
  var _window$CSS, _window$CSS$supports;

  if (typeof value === 'undefined') {
    return undefined;
  } // Handle empty strings, if it's the number 0 this still works.


  if (!value) {
    return '0';
  }

  const asInt = typeof value === 'number' ? value : Number(value); // Test if the input has a unit, was NaN, or was one of the named CSS values (like `auto`), in which case just use that value.

  if (typeof window !== 'undefined' && (_window$CSS = window.CSS) !== null && _window$CSS !== void 0 && (_window$CSS$supports = _window$CSS.supports) !== null && _window$CSS$supports !== void 0 && _window$CSS$supports.call(_window$CSS, 'margin', value.toString()) || Number.isNaN(asInt)) {
    return value.toString();
  }

  return `calc(${GRID_BASE} * ${value})`;
}
//# sourceMappingURL=space.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  lm: () => (/* binding */ COLORS)
});

// UNUSED EXPORTS: ADMIN, ALERT, BASE, BLUE, DARK_GRAY, DARK_OPACITY, DARK_OPACITY_LIGHT, G2, LIGHT_GRAY, LIGHT_OPACITY_LIGHT, UI, default

// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs
var colord = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs
var names = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors.js
/**
 * External dependencies
 */


(0,colord/* extend */.X$)([names/* default */.A]);
/**
 * Generating a CSS compliant rgba() color value.
 *
 * @param {string} hexValue The hex value to convert to rgba().
 * @param {number} alpha    The alpha value for opacity.
 * @return {string} The converted rgba() color value.
 *
 * @example
 * rgba( '#000000', 0.5 )
 * // rgba(0, 0, 0, 0.5)
 */

function rgba() {
  let hexValue = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  let alpha = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
  return (0,colord/* colord */.Mj)(hexValue).alpha(alpha).toRgbString();
}
//# sourceMappingURL=colors.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const BASE = {
  black: '#000',
  white: '#fff'
};
/**
 * TODO: Continue to update values as "G2" design evolves.
 *
 * "G2" refers to the movement to advance the interface of the block editor.
 * https://github.com/WordPress/gutenberg/issues/18667
 */

const G2 = {
  blue: {
    medium: {
      focus: '#007cba',
      focusDark: '#fff'
    }
  },
  gray: {
    900: '#1e1e1e',
    700: '#757575',
    // Meets 4.6:1 text contrast against white.
    600: '#949494',
    // Meets 3:1 UI or large text contrast against white.
    400: '#ccc',
    300: '#ddd',
    // Used for most borders.
    200: '#e0e0e0',
    // Used sparingly for light borders.
    100: '#f0f0f0' // Used for light gray backgrounds.

  },
  darkGray: {
    primary: '#1e1e1e',
    heading: '#050505'
  },
  mediumGray: {
    text: '#757575'
  },
  lightGray: {
    ui: '#949494',
    secondary: '#ccc',
    tertiary: '#e7e8e9'
  }
};
const DARK_GRAY = {
  900: '#191e23',
  800: '#23282d',
  700: '#32373c',
  600: '#40464d',
  500: '#555d66',
  // Use this most of the time for dark items.
  400: '#606a73',
  300: '#6c7781',
  // Lightest gray that can be used for AA text contrast.
  200: '#7e8993',
  150: '#8d96a0',
  // Lightest gray that can be used for AA non-text contrast.
  100: '#8f98a1',
  placeholder: rgba(G2.gray[900], 0.62)
};
const DARK_OPACITY = {
  900: rgba('#000510', 0.9),
  800: rgba('#00000a', 0.85),
  700: rgba('#06060b', 0.8),
  600: rgba('#000913', 0.75),
  500: rgba('#0a1829', 0.7),
  400: rgba('#0a1829', 0.65),
  300: rgba('#0e1c2e', 0.62),
  200: rgba('#162435', 0.55),
  100: rgba('#223443', 0.5),
  backgroundFill: rgba(DARK_GRAY[700], 0.7)
};
const DARK_OPACITY_LIGHT = {
  900: rgba('#304455', 0.45),
  800: rgba('#425863', 0.4),
  700: rgba('#667886', 0.35),
  600: rgba('#7b86a2', 0.3),
  500: rgba('#9197a2', 0.25),
  400: rgba('#95959c', 0.2),
  300: rgba('#829493', 0.15),
  200: rgba('#8b8b96', 0.1),
  100: rgba('#747474', 0.05)
};
const LIGHT_GRAY = {
  900: '#a2aab2',
  800: '#b5bcc2',
  700: '#ccd0d4',
  600: '#d7dade',
  500: '#e2e4e7',
  // Good for "grayed" items and borders.
  400: '#e8eaeb',
  // Good for "readonly" input fields and special text selection.
  300: '#edeff0',
  200: '#f3f4f5',
  100: '#f8f9f9',
  placeholder: rgba(BASE.white, 0.65)
};
const LIGHT_OPACITY_LIGHT = {
  900: rgba(BASE.white, 0.5),
  800: rgba(BASE.white, 0.45),
  700: rgba(BASE.white, 0.4),
  600: rgba(BASE.white, 0.35),
  500: rgba(BASE.white, 0.3),
  400: rgba(BASE.white, 0.25),
  300: rgba(BASE.white, 0.2),
  200: rgba(BASE.white, 0.15),
  100: rgba(BASE.white, 0.1),
  backgroundFill: rgba(LIGHT_GRAY[300], 0.8)
}; // Additional colors.
// Some are from https://make.wordpress.org/design/handbook/foundations/colors/.

const BLUE = {
  wordpress: {
    700: '#00669b'
  },
  dark: {
    900: '#0071a1'
  },
  medium: {
    900: '#006589',
    800: '#00739c',
    700: '#007fac',
    600: '#008dbe',
    500: '#00a0d2',
    400: '#33b3db',
    300: '#66c6e4',
    200: '#bfe7f3',
    100: '#e5f5fa',
    highlight: '#b3e7fe',
    focus: '#007cba'
  }
};
const ALERT = {
  yellow: '#f0b849',
  red: '#d94f4f',
  green: '#4ab866'
};
const ADMIN = {
  theme: `var( --wp-admin-theme-color, ${BLUE.wordpress[700]})`,
  themeDark10: `var( --wp-admin-theme-color-darker-10, ${BLUE.medium.focus})`
}; // Namespaced values for raw colors hex codes.

const UI = {
  theme: ADMIN.theme,
  background: BASE.white,
  backgroundDisabled: LIGHT_GRAY[200],
  border: G2.gray[700],
  borderHover: G2.gray[700],
  borderFocus: ADMIN.themeDark10,
  borderDisabled: G2.gray[400],
  borderLight: G2.gray[300],
  label: DARK_GRAY[500],
  textDisabled: DARK_GRAY[150],
  textDark: BASE.white,
  textLight: BASE.black
}; // Using Object.assign instead of { ...spread } syntax helps TypeScript
// to extract the correct type defs here.

const COLORS = Object.assign({}, BASE, {
  darkGray: (0,lodash.merge)({}, DARK_GRAY, G2.darkGray),
  darkOpacity: DARK_OPACITY,
  darkOpacityLight: DARK_OPACITY_LIGHT,
  mediumGray: G2.mediumGray,
  gray: G2.gray,
  lightGray: (0,lodash.merge)({}, LIGHT_GRAY, G2.lightGray),
  lightGrayLight: LIGHT_OPACITY_LIGHT,
  blue: (0,lodash.merge)({}, BLUE, G2.blue),
  alert: ALERT,
  admin: ADMIN,
  ui: UI
});
/* harmony default export */ const colors_values = ((/* unused pure expression or super */ null && (COLORS)));
//# sourceMappingURL=colors-values.js.map

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

/***/ "../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/info.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@3.4.1/node_modules/@wordpress/primitives/build-module/svg/index.js");


/**
 * WordPress dependencies
 */

const info = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, {
  d: "M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (info);
//# sourceMappingURL=info.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  uE: () => (/* reexport */ with_viewport_match)
});

// UNUSED EXPORTS: ifViewportMatches, store

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  setIsMatching: () => (setIsMatching)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  isViewportMatch: () => (isViewportMatch)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/debounce/index.js
var debounce = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/debounce/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js + 9 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/reducer.js
/**
 * Reducer returning the viewport state, as keys of breakpoint queries with
 * boolean value representing whether query is matched.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
function reducer() {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  let action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SET_IS_MATCHING':
      return action.values;
  }

  return state;
}

/* harmony default export */ const store_reducer = (reducer);
//# sourceMappingURL=reducer.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/actions.js
/**
 * Returns an action object used in signalling that viewport queries have been
 * updated. Values are specified as an object of breakpoint query keys where
 * value represents whether query matches.
 * Ignored from documentation as it is for internal use only.
 *
 * @ignore
 *
 * @param {Object} values Breakpoint query matches.
 *
 * @return {Object} Action object.
 */
function setIsMatching(values) {
  return {
    type: 'SET_IS_MATCHING',
    values
  };
}
//# sourceMappingURL=actions.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/selectors.js
/**
 * Returns true if the viewport matches the given query, or false otherwise.
 *
 * @param {Object} state Viewport state object.
 * @param {string} query Query string. Includes operator and breakpoint name,
 *                       space separated. Operator defaults to >=.
 *
 * @example
 *
 * ```js
 * import { store as viewportStore } from '@wordpress/viewport';
 * import { useSelect } from '@wordpress/data';
 * import { __ } from '@wordpress/i18n';
 * const ExampleComponent = () => {
 *     const isMobile = useSelect(
 *         ( select ) => select( viewportStore ).isViewportMatch( '< small' ),
 *         []
 *     );
 *
 *     return isMobile ? (
 *         <div>{ __( 'Mobile' ) }</div>
 *     ) : (
 *         <div>{ __( 'Not Mobile' ) }</div>
 *     );
 * };
 * ```
 *
 * @return {boolean} Whether viewport matches query.
 */
function isViewportMatch(state, query) {
  // Default to `>=` if no operator is present.
  if (query.indexOf(' ') === -1) {
    query = '>= ' + query;
  }

  return !!state[query];
}
//# sourceMappingURL=selectors.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/index.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




const STORE_NAME = 'core/viewport';
/**
 * Store definition for the viewport namespace.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
 *
 * @type {Object}
 */

const store = (0,redux_store/* default */.A)(STORE_NAME, {
  reducer: store_reducer,
  actions: actions_namespaceObject,
  selectors: selectors_namespaceObject
});
(0,build_module/* register */.kz)(store);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/listener.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const addDimensionsEventListener = (breakpoints, operators) => {
  /**
   * Callback invoked when media query state should be updated. Is invoked a
   * maximum of one time per call stack.
   */
  const setIsMatching = (0,debounce/* debounce */.s)(() => {
    const values = Object.fromEntries(queries.map(_ref => {
      let [key, query] = _ref;
      return [key, query.matches];
    }));
    (0,build_module/* dispatch */.JD)(store).setIsMatching(values);
  }, 0, {
    leading: true
  });
  /**
   * Hash of breakpoint names with generated MediaQueryList for corresponding
   * media query.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Window/matchMedia
   * @see https://developer.mozilla.org/en-US/docs/Web/API/MediaQueryList
   *
   * @type {Object<string,MediaQueryList>}
   */

  const operatorEntries = Object.entries(operators);
  const queries = Object.entries(breakpoints).flatMap(_ref2 => {
    let [name, width] = _ref2;
    return operatorEntries.map(_ref3 => {
      let [operator, condition] = _ref3;
      const list = window.matchMedia(`(${condition}: ${width}px)`);
      list.addEventListener('change', setIsMatching);
      return [`${operator} ${name}`, list];
    });
  });
  window.addEventListener('orientationchange', setIsMatching); // Set initial values.

  setIsMatching();
  setIsMatching.flush();
};

/* harmony default export */ const listener = (addDimensionsEventListener);
//# sourceMappingURL=listener.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js
var use_viewport_match = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js + 1 modules
var create_higher_order_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pure/index.js
var pure = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pure/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/with-viewport-match.js



/**
 * WordPress dependencies
 */

/**
 * Higher-order component creator, creating a new component which renders with
 * the given prop names, where the value passed to the underlying component is
 * the result of the query assigned as the object's value.
 *
 * @see isViewportMatch
 *
 * @param {Object} queries Object of prop name to viewport query.
 *
 * @example
 *
 * ```jsx
 * function MyComponent( { isMobile } ) {
 * 	return (
 * 		<div>Currently: { isMobile ? 'Mobile' : 'Not Mobile' }</div>
 * 	);
 * }
 *
 * MyComponent = withViewportMatch( { isMobile: '< small' } )( MyComponent );
 * ```
 *
 * @return {Function} Higher-order component.
 */

const withViewportMatch = queries => {
  const queryEntries = Object.entries(queries);

  const useViewPortQueriesResult = () => Object.fromEntries(queryEntries.map(_ref => {
    let [key, query] = _ref;
    let [operator, breakpointName] = query.split(' ');

    if (breakpointName === undefined) {
      breakpointName = operator;
      operator = '>=';
    } // Hooks should unconditionally execute in the same order,
    // we are respecting that as from the static query of the HOC we generate
    // a hook that calls other hooks always in the same order (because the query never changes).
    // eslint-disable-next-line react-hooks/rules-of-hooks


    return [key, (0,use_viewport_match/* default */.A)(breakpointName, operator)];
  }));

  return (0,create_higher_order_component/* createHigherOrderComponent */.f)(WrappedComponent => {
    return (0,pure/* default */.A)(props => {
      const queriesResult = useViewPortQueriesResult();
      return (0,react.createElement)(WrappedComponent, (0,esm_extends/* default */.A)({}, props, queriesResult));
    });
  }, 'withViewportMatch');
};

/* harmony default export */ const with_viewport_match = (withViewportMatch);
//# sourceMappingURL=with-viewport-match.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js
/**
 * Internal dependencies
 */




/**
 * Hash of breakpoint names with pixel width at which it becomes effective.
 *
 * @see _breakpoints.scss
 *
 * @type {Object}
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
 * Hash of query operators with corresponding condition for media query.
 *
 * @type {Object}
 */

const OPERATORS = {
  '<': 'max-width',
  '>=': 'min-width'
};
listener(BREAKPOINTS, OPERATORS);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $find = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").find);
var addToUnscopables = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/add-to-unscopables.js");

var FIND = 'find';
var SKIPS_HOLES = true;

// Shouldn't skip holes
// eslint-disable-next-line es/no-array-prototype-find -- testing
if (FIND in []) Array(1)[FIND](function () { SKIPS_HOLES = false; });

// `Array.prototype.find` method
// https://tc39.es/ecma262/#sec-array.prototype.find
$({ target: 'Array', proto: true, forced: SKIPS_HOLES }, {
  find: function find(callbackfn /* , that = undefined */) {
    return $find(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});

// https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
addToUnscopables(FIND);


/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/chevron-down.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-chevron-down",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M20 9l-8 8-8-8 1.414-1.414L12 14.172l6.586-6.586z"})))}


/***/ })

}]);