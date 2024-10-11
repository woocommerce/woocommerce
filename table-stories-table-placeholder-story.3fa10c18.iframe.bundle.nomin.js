"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4962],{

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

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/config-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ui_utils_space__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js");
/* harmony import */ var _colors_values__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js");
/**
 * Internal dependencies
 */


const CONTROL_HEIGHT = '36px';
const CONTROL_PADDING_X = '12px';
const CONTROL_PROPS = {
  controlSurfaceColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  controlTextActiveColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.ui.theme,
  controlPaddingX: CONTROL_PADDING_X,
  controlPaddingXLarge: `calc(${CONTROL_PADDING_X} * 1.3334)`,
  controlPaddingXSmall: `calc(${CONTROL_PADDING_X} / 1.3334)`,
  controlBackgroundColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  controlBorderRadius: '2px',
  controlBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.gray[700],
  controlBoxShadow: 'transparent',
  controlBorderColorHover: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.gray[700],
  controlBoxShadowFocus: `0 0 0 0.5px ${_colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.admin.theme}`,
  controlDestructiveBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.alert.red,
  controlHeight: CONTROL_HEIGHT,
  controlHeightXSmall: `calc( ${CONTROL_HEIGHT} * 0.6 )`,
  controlHeightSmall: `calc( ${CONTROL_HEIGHT} * 0.8 )`,
  controlHeightLarge: `calc( ${CONTROL_HEIGHT} * 1.2 )`,
  controlHeightXLarge: `calc( ${CONTROL_HEIGHT} * 1.4 )`
};
const TOGGLE_GROUP_CONTROL_PROPS = {
  toggleGroupControlBackgroundColor: CONTROL_PROPS.controlBackgroundColor,
  toggleGroupControlBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.ui.border,
  toggleGroupControlBackdropBackgroundColor: CONTROL_PROPS.controlSurfaceColor,
  toggleGroupControlBackdropBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.ui.border,
  toggleGroupControlBackdropBoxShadow: 'transparent',
  toggleGroupControlButtonColorActive: CONTROL_PROPS.controlBackgroundColor
}; // Using Object.assign to avoid creating circular references when emitting
// TypeScript type declarations.

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Object.assign({}, CONTROL_PROPS, TOGGLE_GROUP_CONTROL_PROPS, {
  colorDivider: 'rgba(0, 0, 0, 0.1)',
  colorScrollbarThumb: 'rgba(0, 0, 0, 0.2)',
  colorScrollbarThumbHover: 'rgba(0, 0, 0, 0.5)',
  colorScrollbarTrack: 'rgba(0, 0, 0, 0.04)',
  elevationIntensity: 1,
  radiusBlockUi: '2px',
  borderWidth: '1px',
  borderWidthFocus: '1.5px',
  borderWidthTab: '4px',
  spinnerSize: 16,
  fontSize: '13px',
  fontSizeH1: 'calc(2.44 * 13px)',
  fontSizeH2: 'calc(1.95 * 13px)',
  fontSizeH3: 'calc(1.56 * 13px)',
  fontSizeH4: 'calc(1.25 * 13px)',
  fontSizeH5: '13px',
  fontSizeH6: 'calc(0.8 * 13px)',
  fontSizeInputMobile: '16px',
  fontSizeMobile: '15px',
  fontSizeSmall: 'calc(0.92 * 13px)',
  fontSizeXSmall: 'calc(0.75 * 13px)',
  fontLineHeightBase: '1.2',
  fontWeight: 'normal',
  fontWeightHeading: '600',
  gridBase: '4px',
  cardBorderRadius: '2px',
  cardPaddingXSmall: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(2)}`,
  cardPaddingSmall: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)}`,
  cardPaddingMedium: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)} ${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)}`,
  cardPaddingLarge: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)} ${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(8)}`,
  surfaceBackgroundColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  surfaceBackgroundSubtleColor: '#F3F3F3',
  surfaceBackgroundTintColor: '#F5F5F5',
  surfaceBorderColor: 'rgba(0, 0, 0, 0.1)',
  surfaceBorderBoldColor: 'rgba(0, 0, 0, 0.15)',
  surfaceBorderSubtleColor: 'rgba(0, 0, 0, 0.05)',
  surfaceBackgroundTertiaryColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  surfaceColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  transitionDuration: '200ms',
  transitionDurationFast: '160ms',
  transitionDurationFaster: '120ms',
  transitionDurationFastest: '100ms',
  transitionTimingFunction: 'cubic-bezier(0.08, 0.52, 0.52, 1)',
  transitionTimingFunctionControl: 'cubic-bezier(0.12, 0.8, 0.32, 1)'
}));
//# sourceMappingURL=config-values.js.map

/***/ }),

/***/ "../../packages/js/components/src/table/placeholder.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _table__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../packages/js/components/src/table/table.tsx");



var _excluded = ["query", "caption", "headers", "numberOfRows"];

function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}











/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

/**
 * `TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.
 */
var TablePlaceholder = function TablePlaceholder(_ref) {
  var query = _ref.query,
    caption = _ref.caption,
    headers = _ref.headers,
    _ref$numberOfRows = _ref.numberOfRows,
    numberOfRows = _ref$numberOfRows === void 0 ? 5 : _ref$numberOfRows,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)(_ref, _excluded);
  var rows = (0,lodash__WEBPACK_IMPORTED_MODULE_12__.range)(numberOfRows).map(function () {
    return headers.map(function () {
      return {
        display: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_14__.createElement)("span", {
          className: "is-placeholder"
        })
      };
    });
  });
  var tableProps = _objectSpread({
    query: query,
    caption: caption,
    headers: headers,
    numberOfRows: numberOfRows
  }, props);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_14__.createElement)(_table__WEBPACK_IMPORTED_MODULE_15__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_16__/* ["default"] */ .A)({
    ariaHidden: true,
    className: "is-loading",
    rows: rows
  }, tableProps));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TablePlaceholder);

/***/ }),

/***/ "../../packages/js/components/src/table/stories/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ge: () => (/* binding */ rows),
/* harmony export */   b3: () => (/* binding */ headers),
/* harmony export */   z: () => (/* binding */ summary)
/* harmony export */ });
var headers = [{
  key: 'month',
  label: 'Month'
}, {
  key: 'orders',
  label: 'Orders'
}, {
  key: 'revenue',
  label: 'Revenue'
}];
var rows = [[{
  display: 'January',
  value: 1
}, {
  display: 10,
  value: 10
}, {
  display: '$530.00',
  value: 530
}], [{
  display: 'February',
  value: 2
}, {
  display: 13,
  value: 13
}, {
  display: '$675.00',
  value: 675
}], [{
  display: 'March',
  value: 3
}, {
  display: 9,
  value: 9
}, {
  display: '$460.00',
  value: 460
}]];
var summary = [{
  label: 'Gross Income',
  value: '$830.00'
}, {
  label: 'Taxes',
  value: '$96.32'
}, {
  label: 'Shipping',
  value: '$50.00'
}];

/***/ }),

/***/ "../../packages/js/components/src/table/table.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");



var _excluded = ["instanceId", "headers", "rows", "ariaHidden", "caption", "className", "onSort", "query", "rowHeader", "rowKey", "emptyMessage"];






/**
 * External dependencies
 */










/**
 * Internal dependencies
 */

var ASC = 'asc';
var DESC = 'desc';
var getDisplay = function getDisplay(cell) {
  return cell.display || null;
};

/**
 * A table component, without the Card wrapper. This is a basic table display, sortable, but no default filtering.
 *
 * Row data should be passed to the component as a list of arrays, where each array is a row in the table.
 * Headers are passed in separately as an array of objects with column-related properties. For example,
 * this data would render the following table.
 *
 * ```js
 * const headers = [ { label: 'Month' }, { label: 'Orders' }, { label: 'Revenue' } ];
 * const rows = [
 * 	[
 * 		{ display: 'January', value: 1 },
 * 		{ display: 10, value: 10 },
 * 		{ display: '$530.00', value: 530 },
 * 	],
 * 	[
 * 		{ display: 'February', value: 2 },
 * 		{ display: 13, value: 13 },
 * 		{ display: '$675.00', value: 675 },
 * 	],
 * 	[
 * 		{ display: 'March', value: 3 },
 * 		{ display: 9, value: 9 },
 * 		{ display: '$460.00', value: 460 },
 * 	],
 * ]
 * ```
 *
 * |   Month  | Orders | Revenue |
 * | ---------|--------|---------|
 * | January  |     10 | $530.00 |
 * | February |     13 | $675.00 |
 * | March    |      9 | $460.00 |
 */

var Table = function Table(_ref) {
  var instanceId = _ref.instanceId,
    _ref$headers = _ref.headers,
    headers = _ref$headers === void 0 ? [] : _ref$headers,
    _ref$rows = _ref.rows,
    rows = _ref$rows === void 0 ? [] : _ref$rows,
    ariaHidden = _ref.ariaHidden,
    caption = _ref.caption,
    className = _ref.className,
    _ref$onSort = _ref.onSort,
    onSort = _ref$onSort === void 0 ? function (f) {
      return f;
    } : _ref$onSort,
    _ref$query = _ref.query,
    query = _ref$query === void 0 ? {} : _ref$query,
    rowHeader = _ref.rowHeader,
    rowKey = _ref.rowKey,
    emptyMessage = _ref.emptyMessage,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)(_ref, _excluded);
  var classNames = props.classNames;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_8__.useState)(undefined),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(_useState, 2),
    tabIndex = _useState2[0],
    setTabIndex = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_8__.useState)(false),
    _useState4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(_useState3, 2),
    isScrollableRight = _useState4[0],
    setIsScrollableRight = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_8__.useState)(false),
    _useState6 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(_useState5, 2),
    isScrollableLeft = _useState6[0],
    setIsScrollableLeft = _useState6[1];
  var container = (0,react__WEBPACK_IMPORTED_MODULE_8__.useRef)(null);
  if (classNames) {
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A)("Table component's classNames prop", {
      since: '11.1.0',
      version: '12.0.0',
      alternative: 'className',
      plugin: '@woocommerce/components'
    });
  }
  var classes = classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-table__table', classNames, className, {
    'is-scrollable-right': isScrollableRight,
    'is-scrollable-left': isScrollableLeft
  });
  var sortBy = function sortBy(key) {
    return function () {
      var currentKey = query.orderby || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
        defaultSort: true
      }), 'key', false);
      var currentDir = query.order || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
        key: currentKey
      }), 'defaultOrder', DESC);
      var dir = DESC;
      if (key === currentKey) {
        dir = DESC === currentDir ? ASC : DESC;
      }
      onSort(key, dir);
    };
  };
  var getRowKey = function getRowKey(row, index) {
    if (rowKey && typeof rowKey === 'function') {
      return rowKey(row, index);
    }
    return index;
  };
  var updateTableShadow = function updateTableShadow() {
    var table = container.current;
    if (table !== null && table !== void 0 && table.scrollWidth && table !== null && table !== void 0 && table.scrollHeight && table !== null && table !== void 0 && table.offsetWidth) {
      var scrolledToEnd = (table === null || table === void 0 ? void 0 : table.scrollWidth) - (table === null || table === void 0 ? void 0 : table.scrollLeft) <= (table === null || table === void 0 ? void 0 : table.offsetWidth);
      if (scrolledToEnd && isScrollableRight) {
        setIsScrollableRight(false);
      } else if (!scrolledToEnd && !isScrollableRight) {
        setIsScrollableRight(true);
      }
    }
    if (table !== null && table !== void 0 && table.scrollLeft) {
      var scrolledToStart = (table === null || table === void 0 ? void 0 : table.scrollLeft) <= 0;
      if (scrolledToStart && isScrollableLeft) {
        setIsScrollableLeft(false);
      } else if (!scrolledToStart && !isScrollableLeft) {
        setIsScrollableLeft(true);
      }
    }
  };
  var sortedBy = query.orderby || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
    defaultSort: true
  }), 'key', false);
  var sortDir = query.order || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
    key: sortedBy
  }), 'defaultOrder', DESC);
  var hasData = !!rows.length;
  (0,react__WEBPACK_IMPORTED_MODULE_8__.useEffect)(function () {
    var _container$current, _container$current2;
    var scrollWidth = (_container$current = container.current) === null || _container$current === void 0 ? void 0 : _container$current.scrollWidth;
    var clientWidth = (_container$current2 = container.current) === null || _container$current2 === void 0 ? void 0 : _container$current2.clientWidth;
    if (scrollWidth === undefined || clientWidth === undefined) {
      return;
    }
    var scrollable = scrollWidth > clientWidth;
    setTabIndex(scrollable ? 0 : undefined);
    updateTableShadow();
    window.addEventListener('resize', updateTableShadow);
    return function () {
      window.removeEventListener('resize', updateTableShadow);
    };
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_8__.useEffect)(updateTableShadow, [headers, rows, emptyMessage]);
  return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("div", {
    className: classes,
    ref: container,
    tabIndex: tabIndex,
    "aria-hidden": ariaHidden,
    "aria-labelledby": "caption-".concat(instanceId),
    role: "group",
    onScroll: updateTableShadow
  }, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("table", null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("caption", {
    id: "caption-".concat(instanceId),
    className: "woocommerce-table__caption screen-reader-text"
  }, caption, tabIndex === 0 && (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("small", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('(scroll to see more)', 'woocommerce'))), (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tbody", null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tr", null, headers.map(function (header, i) {
    var cellClassName = header.cellClassName,
      isLeftAligned = header.isLeftAligned,
      isSortable = header.isSortable,
      isNumeric = header.isNumeric,
      key = header.key,
      label = header.label,
      screenReaderLabel = header.screenReaderLabel;
    var labelId = "header-".concat(instanceId, "-").concat(i);
    var thProps = {
      className: classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-table__header', cellClassName, {
        'is-left-aligned': isLeftAligned || !isNumeric,
        'is-sortable': isSortable,
        'is-sorted': sortedBy === key,
        'is-numeric': isNumeric
      })
    };
    if (isSortable) {
      thProps['aria-sort'] = 'none';
      if (sortedBy === key) {
        thProps['aria-sort'] = sortDir === ASC ? 'ascending' : 'descending';
      }
    }
    // We only sort by ascending if the col is already sorted descending
    var iconLabel = sortedBy === key && sortDir !== ASC ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /* translators: %s: column label */
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Sort by %s in ascending order', 'woocommerce'), screenReaderLabel || label) : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /* translators: %s: column label */
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Sort by %s in descending order', 'woocommerce'), screenReaderLabel || label);
    var textLabel = (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(react__WEBPACK_IMPORTED_MODULE_8__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("span", {
      "aria-hidden": Boolean(screenReaderLabel)
    }, label), screenReaderLabel && (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("span", {
      className: "screen-reader-text"
    }, screenReaderLabel));
    return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("th", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A)({
      role: "columnheader",
      scope: "col",
      key: header.key || i
    }, thProps), isSortable ? (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(react__WEBPACK_IMPORTED_MODULE_8__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A, {
      "aria-describedby": labelId,
      onClick: hasData ? sortBy(key) : lodash__WEBPACK_IMPORTED_MODULE_7__.noop
    }, sortedBy === key && sortDir === ASC ? (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_15__/* ["default"] */ .A
    }) : (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_16__/* ["default"] */ .A
    }), textLabel), (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("span", {
      className: "screen-reader-text",
      id: labelId
    }, iconLabel)) : textLabel);
  })), hasData ? rows.map(function (row, i) {
    return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tr", {
      key: getRowKey(row, i)
    }, row.map(function (cell, j) {
      var _headers$j = headers[j],
        cellClassName = _headers$j.cellClassName,
        isLeftAligned = _headers$j.isLeftAligned,
        isNumeric = _headers$j.isNumeric;
      var isHeader = rowHeader === j;
      var Cell = isHeader ? 'th' : 'td';
      var cellClasses = classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-table__item', cellClassName, {
        'is-left-aligned': isLeftAligned || !isNumeric,
        'is-numeric': isNumeric,
        'is-sorted': sortedBy === headers[j].key
      });
      var cellKey = getRowKey(row, i).toString() + j;
      return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(Cell, {
        scope: isHeader ? 'row' : undefined,
        key: cellKey,
        className: cellClasses
      }, getDisplay(cell));
    }));
  }) : (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("td", {
    className: "woocommerce-table__empty-item",
    colSpan: headers.length
  }, emptyMessage !== null && emptyMessage !== void 0 ? emptyMessage : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('No data to display', 'woocommerce'))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_17__/* ["default"] */ .A)(Table));

/***/ }),

/***/ "../../packages/js/components/src/table/stories/table-placeholder.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/table/placeholder.tsx");
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../packages/js/components/src/table/stories/index.ts");

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var Basic = function Basic() {
  return /* @ts-expect-error: size must be one of small, medium, largel, xSmall, extraSmall. */(
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
      size: null
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      caption: "Revenue last week",
      headers: _index__WEBPACK_IMPORTED_MODULE_3__/* .headers */ .b3
    }))
  );
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/components/TablePlaceholder',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return /* @ts-expect-error: size must be one of small, medium, largel, xSmall, extraSmall. */(\n    <Card size={null}>\n            <TablePlaceholder caption=\"Revenue last week\" headers={headers} />\n        </Card>\n  );\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ })

}]);