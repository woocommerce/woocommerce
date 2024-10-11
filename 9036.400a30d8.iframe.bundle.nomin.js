(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9036],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ text_component)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/styles.js
var text_styles_namespaceObject = {};
__webpack_require__.r(text_styles_namespaceObject);
__webpack_require__.d(text_styles_namespaceObject, {
  Text: () => (Text),
  block: () => (block),
  destructive: () => (destructive),
  highlighterText: () => (highlighterText),
  muted: () => (muted),
  positive: () => (positive),
  upperCase: () => (upperCase)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/truncate/styles.js
function _EMOTION_STRINGIFIED_CSS_ERROR__() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }

/**
 * External dependencies
 */

const Truncate =  true ? {
  name: "hdknak",
  styles: "display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
} : 0;
//# sourceMappingURL=styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/truncate/utils.js
/**
 * External dependencies
 */

const TRUNCATE_ELLIPSIS = '…';
const TRUNCATE_TYPE = {
  auto: 'auto',
  head: 'head',
  middle: 'middle',
  tail: 'tail',
  none: 'none'
};
const TRUNCATE_DEFAULT_PROPS = {
  ellipsis: TRUNCATE_ELLIPSIS,
  ellipsizeMode: TRUNCATE_TYPE.auto,
  limit: 0,
  numberOfLines: 0
}; // Source
// https://github.com/kahwee/truncate-middle

/**
 * @param {string} word
 * @param {number} headLength
 * @param {number} tailLength
 * @param {string} ellipsis
 */

function truncateMiddle(word, headLength, tailLength, ellipsis) {
  if (typeof word !== 'string') {
    return '';
  }

  const wordLength = word.length; // Setting default values
  // eslint-disable-next-line no-bitwise

  const frontLength = ~~headLength; // Will cast to integer
  // eslint-disable-next-line no-bitwise

  const backLength = ~~tailLength;
  /* istanbul ignore next */

  const truncateStr = !(0,lodash.isNil)(ellipsis) ? ellipsis : TRUNCATE_ELLIPSIS;

  if (frontLength === 0 && backLength === 0 || frontLength >= wordLength || backLength >= wordLength || frontLength + backLength >= wordLength) {
    return word;
  } else if (backLength === 0) {
    return word.slice(0, frontLength) + truncateStr;
  }

  return word.slice(0, frontLength) + truncateStr + word.slice(wordLength - backLength);
}
/**
 *
 * @param {string}                        words
 * @param {typeof TRUNCATE_DEFAULT_PROPS} props
 */

function truncateContent() {
  let words = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  let props = arguments.length > 1 ? arguments[1] : undefined;
  const mergedProps = { ...TRUNCATE_DEFAULT_PROPS,
    ...props
  };
  const {
    ellipsis,
    ellipsizeMode,
    limit
  } = mergedProps;

  if (ellipsizeMode === TRUNCATE_TYPE.none) {
    return words;
  }

  let truncateHead;
  let truncateTail;

  switch (ellipsizeMode) {
    case TRUNCATE_TYPE.head:
      truncateHead = 0;
      truncateTail = limit;
      break;

    case TRUNCATE_TYPE.middle:
      truncateHead = Math.floor(limit / 2);
      truncateTail = Math.floor(limit / 2);
      break;

    default:
      truncateHead = limit;
      truncateTail = 0;
  }

  const truncatedContent = ellipsizeMode !== TRUNCATE_TYPE.auto ? truncateMiddle(words, truncateHead, truncateTail, ellipsis) : words;
  return truncatedContent;
}
//# sourceMappingURL=utils.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/truncate/hook.js
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
 * @param {import('../ui/context').WordPressComponentProps<import('./types').Props, 'span'>} props
 */

function useTruncate(props) {
  const {
    className,
    children,
    ellipsis = TRUNCATE_ELLIPSIS,
    ellipsizeMode = TRUNCATE_TYPE.auto,
    limit = 0,
    numberOfLines = 0,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'Truncate');
  const cx = (0,use_cx/* useCx */.l)();
  const truncatedContent = truncateContent(typeof children === 'string' ?
  /** @type {string} */
  children : '', {
    ellipsis,
    ellipsizeMode,
    limit,
    numberOfLines
  });
  const shouldTruncate = ellipsizeMode === TRUNCATE_TYPE.auto;
  const classes = (0,react.useMemo)(() => {
    const sx = {};
    sx.numberOfLines = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("-webkit-box-orient:vertical;-webkit-line-clamp:", numberOfLines, ";display:-webkit-box;overflow:hidden;" + ( true ? "" : 0),  true ? "" : 0);
    return cx(shouldTruncate && !numberOfLines && Truncate, shouldTruncate && !!numberOfLines && sx.numberOfLines, className);
  }, [className, cx, numberOfLines, shouldTruncate]);
  return { ...otherProps,
    className: classes,
    children: truncatedContent
  };
}
//# sourceMappingURL=hook.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/memize@1.1.0/node_modules/memize/index.js
var memize = __webpack_require__("../../node_modules/.pnpm/memize@1.1.0/node_modules/memize/index.js");
var memize_default = /*#__PURE__*/__webpack_require__.n(memize);
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs
var colord = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs
var names = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/colors.js
/**
 * External dependencies
 */



/** @type {HTMLDivElement} */

let colorComputationNode;
(0,colord/* extend */.X$)([names/* default */.A]);
/**
 * @return {HTMLDivElement | undefined} The HTML element for color computation.
 */

function getColorComputationNode() {
  if (typeof document === 'undefined') return;

  if (!colorComputationNode) {
    // Create a temporary element for style computation.
    const el = document.createElement('div');
    el.setAttribute('data-g2-color-computation-node', ''); // Inject for window computed style.

    document.body.appendChild(el);
    colorComputationNode = el;
  }

  return colorComputationNode;
}
/**
 * @param {string | unknown} value
 *
 * @return {boolean} Whether the value is a valid color.
 */


function isColor(value) {
  if (typeof value !== 'string') return false;
  const test = (0,colord/* colord */.Mj)(value);
  return test.isValid();
}
/**
 * Retrieves the computed background color. This is useful for getting the
 * value of a CSS variable color.
 *
 * @param {string | unknown} backgroundColor The background color to compute.
 *
 * @return {string} The computed background color.
 */


function _getComputedBackgroundColor(backgroundColor) {
  var _window;

  if (typeof backgroundColor !== 'string') return '';
  if (isColor(backgroundColor)) return backgroundColor;
  if (!backgroundColor.includes('var(')) return '';
  if (typeof document === 'undefined') return ''; // Attempts to gracefully handle CSS variables color values.

  const el = getColorComputationNode();
  if (!el) return '';
  el.style.background = backgroundColor; // Grab the style.

  const computedColor = (_window = window) === null || _window === void 0 ? void 0 : _window.getComputedStyle(el).background; // Reset.

  el.style.background = '';
  return computedColor || '';
}

const getComputedBackgroundColor = memize_default()(_getComputedBackgroundColor);
/**
 * Get the text shade optimized for readability, based on a background color.
 *
 * @param {string | unknown} backgroundColor The background color.
 *
 * @return {string} The optimized text color (black or white).
 */

function getOptimalTextColor(backgroundColor) {
  const background = getComputedBackgroundColor(backgroundColor);
  return (0,colord/* colord */.Mj)(background).isLight() ? '#000000' : '#ffffff';
}
/**
 * Get the text shade optimized for readability, based on a background color.
 *
 * @param {string | unknown} backgroundColor The background color.
 *
 * @return {string} The optimized text shade (dark or light).
 */

function getOptimalTextShade(backgroundColor) {
  const result = getOptimalTextColor(backgroundColor);
  return result === '#000000' ? 'dark' : 'light';
}
//# sourceMappingURL=colors.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js + 1 modules
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/config-values.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/styles.js
function styles_EMOTION_STRINGIFIED_CSS_ERROR_() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const Text = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.lm.darkGray.primary, ";line-height:", config_values/* default */.A.fontLineHeightBase, ";margin:0;" + ( true ? "" : 0),  true ? "" : 0);
const block =  true ? {
  name: "4zleql",
  styles: "display:block"
} : 0;
const positive = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.lm.alert.green, ";" + ( true ? "" : 0),  true ? "" : 0);
const destructive = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.lm.alert.red, ";" + ( true ? "" : 0),  true ? "" : 0);
const muted = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.lm.mediumGray.text, ";" + ( true ? "" : 0),  true ? "" : 0);
const highlighterText = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("mark{background:", colors_values/* COLORS */.lm.alert.yellow, ";border-radius:2px;box-shadow:0 0 0 1px rgba( 0, 0, 0, 0.05 ) inset,0 -1px 0 rgba( 0, 0, 0, 0.1 ) inset;}" + ( true ? "" : 0),  true ? "" : 0);
const upperCase =  true ? {
  name: "50zrmy",
  styles: "text-transform:uppercase"
} : 0;
//# sourceMappingURL=styles.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/highlight-words-core@1.2.2/node_modules/highlight-words-core/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/highlight-words-core@1.2.2/node_modules/highlight-words-core/dist/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/utils.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Source:
 * https://github.com/bvaughn/react-highlight-words/blob/HEAD/src/Highlighter.js
 */

/* eslint-disable jsdoc/valid-types */

/**
 * @typedef Options
 * @property {string}                                                     [activeClassName='']      Classname for active highlighted areas.
 * @property {number}                                                     [activeIndex=-1]          The index of the active highlighted area.
 * @property {import('react').AllHTMLAttributes<HTMLDivElement>['style']} [activeStyle]             Styles to apply to the active highlighted area.
 * @property {boolean}                                                    [autoEscape]              Whether to automatically escape text.
 * @property {boolean}                                                    [caseSensitive=false]     Whether to highlight in a case-sensitive manner.
 * @property {string}                                                     children                  Children to highlight.
 * @property {import('highlight-words-core').FindAllArgs['findChunks']}   [findChunks]              Custom `findChunks` function to pass to `highlight-words-core`.
 * @property {string | Record<string, unknown>}                           [highlightClassName='']   Classname to apply to highlighted text or a Record of classnames to apply to given text (which should be the key).
 * @property {import('react').AllHTMLAttributes<HTMLDivElement>['style']} [highlightStyle={}]       Styles to apply to highlighted text.
 * @property {keyof JSX.IntrinsicElements}                                [highlightTag='mark']     Tag to use for the highlighted text.
 * @property {import('highlight-words-core').FindAllArgs['sanitize']}     [sanitize]                Custom `santize` function to pass to `highlight-words-core`.
 * @property {string[]}                                                   [searchWords=[]]          Words to search for and highlight.
 * @property {string}                                                     [unhighlightClassName=''] Classname to apply to unhighlighted text.
 * @property {import('react').AllHTMLAttributes<HTMLDivElement>['style']} [unhighlightStyle]        Style to apply to unhighlighted text.
 */

/**
 * Maps props to lowercase names.
 *
 * @template {Record<string, unknown>} T
 * @param {T} object Props to map.
 * @return {{[K in keyof T as Lowercase<string & K>]: T[K]}} The mapped props.
 */

/* eslint-enable jsdoc/valid-types */

const lowercaseProps = object => {
  /** @type {any} */
  const mapped = {};

  for (const key in object) {
    mapped[key.toLowerCase()] = object[key];
  }

  return mapped;
};

const memoizedLowercaseProps = memize_default()(lowercaseProps);
/**
 *
 * @param {Options} options
 */

function createHighlighterText(_ref) {
  let {
    activeClassName = '',
    activeIndex = -1,
    activeStyle,
    autoEscape,
    caseSensitive = false,
    children,
    findChunks,
    highlightClassName = '',
    highlightStyle = {},
    highlightTag = 'mark',
    sanitize,
    searchWords = [],
    unhighlightClassName = '',
    unhighlightStyle
  } = _ref;
  if (!children) return null;
  if (typeof children !== 'string') return children;
  const textToHighlight = children;
  const chunks = (0,dist.findAll)({
    autoEscape,
    caseSensitive,
    findChunks,
    sanitize,
    searchWords,
    textToHighlight
  });
  const HighlightTag = highlightTag;
  let highlightIndex = -1;
  let highlightClassNames = '';
  let highlightStyles;
  const textContent = chunks.map((chunk, index) => {
    const text = textToHighlight.substr(chunk.start, chunk.end - chunk.start);

    if (chunk.highlight) {
      highlightIndex++;
      let highlightClass;

      if (typeof highlightClassName === 'object') {
        if (!caseSensitive) {
          highlightClassName = memoizedLowercaseProps(highlightClassName);
          highlightClass = highlightClassName[text.toLowerCase()];
        } else {
          highlightClass = highlightClassName[text];
        }
      } else {
        highlightClass = highlightClassName;
      }

      const isActive = highlightIndex === +activeIndex;
      highlightClassNames = `${highlightClass} ${isActive ? activeClassName : ''}`;
      highlightStyles = isActive === true && activeStyle !== null ? Object.assign({}, highlightStyle, activeStyle) : highlightStyle;
      /** @type {Record<string, any>} */

      const props = {
        children: text,
        className: highlightClassNames,
        key: index,
        style: highlightStyles
      }; // Don't attach arbitrary props to DOM elements; this triggers React DEV warnings (https://fb.me/react-unknown-prop)
      // Only pass through the highlightIndex attribute for custom components.

      if (typeof HighlightTag !== 'string') {
        props.highlightIndex = highlightIndex;
      }

      return (0,react.createElement)(HighlightTag, props);
    }

    return (0,react.createElement)('span', {
      children: text,
      className: unhighlightClassName,
      key: index,
      style: unhighlightStyle
    });
  });
  return textContent;
}
//# sourceMappingURL=utils.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/font-size.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

const BASE_FONT_SIZE = 13;
const PRESET_FONT_SIZES = {
  body: BASE_FONT_SIZE,
  caption: 10,
  footnote: 11,
  largeTitle: 28,
  subheadline: 12,
  title: 20
};
const HEADING_FONT_SIZES = [1, 2, 3, 4, 5, 6].flatMap(n => [n, n.toString()]);
function getFontSize() {
  let size = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : BASE_FONT_SIZE;

  if (size in PRESET_FONT_SIZES) {
    return getFontSize(PRESET_FONT_SIZES[size]);
  }

  if (typeof size !== 'number') {
    const parsed = parseFloat(size);
    if (Number.isNaN(parsed)) return size;
    size = parsed;
  }

  const ratio = `(${size} / ${BASE_FONT_SIZE})`;
  return `calc(${ratio} * ${config_values/* default */.A.fontSize})`;
}
function getHeadingFontSize() {
  let size = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 3;

  if (!HEADING_FONT_SIZES.includes(size)) {
    return getFontSize(size);
  }

  const headingSize = `fontSizeH${size}`;
  return CONFIG[headingSize];
}
//# sourceMappingURL=font-size.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/get-line-height.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


function getLineHeight(adjustLineHeightForInnerControls, lineHeight) {
  if (lineHeight) return lineHeight;
  if (!adjustLineHeightForInnerControls) return;
  let value = `calc(${config_values/* default */.A.controlHeight} + ${(0,space/* space */.x)(2)})`;

  switch (adjustLineHeightForInnerControls) {
    case 'large':
      value = `calc(${config_values/* default */.A.controlHeightLarge} + ${(0,space/* space */.x)(2)})`;
      break;

    case 'small':
      value = `calc(${config_values/* default */.A.controlHeightSmall} + ${(0,space/* space */.x)(2)})`;
      break;

    case 'xSmall':
      value = `calc(${config_values/* default */.A.controlHeightXSmall} + ${(0,space/* space */.x)(2)})`;
      break;

    default:
      break;
  }

  return value;
}
//# sourceMappingURL=get-line-height.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/hook.js
function hook_EMOTION_STRINGIFIED_CSS_ERROR_() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }

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
 * @param {import('../ui/context').WordPressComponentProps<import('./types').Props, 'span'>} props
 */

var _ref =  true ? {
  name: "50zrmy",
  styles: "text-transform:uppercase"
} : 0;

function useText(props) {
  const {
    adjustLineHeightForInnerControls,
    align,
    children,
    className,
    color,
    ellipsizeMode,
    isDestructive = false,
    display,
    highlightEscape = false,
    highlightCaseSensitive = false,
    highlightWords,
    highlightSanitize,
    isBlock = false,
    letterSpacing,
    lineHeight: lineHeightProp,
    optimizeReadabilityFor,
    size,
    truncate = false,
    upperCase = false,
    variant,
    weight = config_values/* default */.A.fontWeight,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'Text');
  /** @type {import('react').ReactNode} */

  let content = children;
  const isHighlighter = Array.isArray(highlightWords);
  const isCaption = size === 'caption';

  if (isHighlighter) {
    if (typeof children !== 'string') {
      throw new TypeError('`children` of `Text` must only be `string` types when `highlightWords` is defined');
    }

    content = createHighlighterText({
      autoEscape: highlightEscape,
      // Disable reason: We need to disable this otherwise it erases the cast
      // eslint-disable-next-line object-shorthand
      children:
      /** @type {string} */
      children,
      caseSensitive: highlightCaseSensitive,
      searchWords: highlightWords,
      sanitize: highlightSanitize
    });
  }

  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    const sx = {};
    const lineHeight = getLineHeight(adjustLineHeightForInnerControls, lineHeightProp);
    sx.Base = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
      color,
      display,
      fontSize: getFontSize(size),

      /* eslint-disable jsdoc/valid-types */
      fontWeight:
      /** @type {import('react').CSSProperties['fontWeight']} */
      weight,

      /* eslint-enable jsdoc/valid-types */
      lineHeight,
      letterSpacing,
      textAlign: align
    },  true ? "" : 0,  true ? "" : 0);
    sx.upperCase = _ref;
    sx.optimalTextColor = null;

    if (optimizeReadabilityFor) {
      const isOptimalTextColorDark = getOptimalTextShade(optimizeReadabilityFor) === 'dark';
      sx.optimalTextColor = isOptimalTextColorDark ? /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
        color: colors_values/* COLORS */.lm.black
      },  true ? "" : 0,  true ? "" : 0) : /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
        color: colors_values/* COLORS */.lm.white
      },  true ? "" : 0,  true ? "" : 0);
    }

    return cx(Text, sx.Base, sx.optimalTextColor, isDestructive && destructive, !!isHighlighter && highlighterText, isBlock && block, isCaption && muted, variant && text_styles_namespaceObject[variant], upperCase && sx.upperCase, className);
  }, [adjustLineHeightForInnerControls, align, className, color, cx, display, isBlock, isCaption, isDestructive, isHighlighter, letterSpacing, lineHeightProp, optimizeReadabilityFor, size, upperCase, variant, weight]);
  /** @type {undefined | 'auto' | 'none'} */

  let finalEllipsizeMode;

  if (truncate === true) {
    finalEllipsizeMode = 'auto';
  }

  if (truncate === false) {
    finalEllipsizeMode = 'none';
  }

  const finalComponentProps = { ...otherProps,
    className: classes,
    children,
    ellipsizeMode: ellipsizeMode || finalEllipsizeMode
  };
  const truncateProps = useTruncate(finalComponentProps);
  /**
   * Enhance child `<Link />` components to inherit font size.
   */

  if (!truncate && Array.isArray(children)) {
    content = react.Children.map(children, child => {
      // @ts-ignore
      if (!(0,lodash.isPlainObject)(child) || !('props' in child)) {
        return child;
      }

      const isLink = (0,context_connect/* hasConnectNamespace */.SZ)(child, ['Link']);

      if (isLink) {
        return (0,react.cloneElement)(child, {
          size: child.props.size || 'inherit'
        });
      }

      return child;
    });
  }

  return { ...truncateProps,
    children: truncate ? truncateProps.children : content
  };
}
//# sourceMappingURL=hook.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js



/**
 * Internal dependencies
 */



/**
 * @param {import('../ui/context').WordPressComponentProps<import('./types').Props, 'span'>} props
 * @param {import('react').ForwardedRef<any>}                                                forwardedRef
 */

function component_Text(props, forwardedRef) {
  const textProps = useText(props);
  return (0,react.createElement)(component/* default */.A, (0,esm_extends/* default */.A)({
    as: "span"
  }, textProps, {
    ref: forwardedRef
  }));
}
/**
 * `Text` is a core component that renders text in the library, using the
 * library's typography system.
 *
 * `Text` can be used to render any text-content, like an HTML `p` or `span`.
 *
 * @example
 *
 * ```jsx
 * import { __experimentalText as Text } from `@wordpress/components`;
 *
 * function Example() {
 * 	return <Text>Code is Poetry</Text>;
 * }
 * ```
 */


const ConnectedText = (0,context_connect/* contextConnect */.KZ)(component_Text, 'Text');
/* harmony default export */ const text_component = (ConnectedText);
//# sourceMappingURL=component.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/config-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "../../node_modules/.pnpm/highlight-words-core@1.2.2/node_modules/highlight-words-core/dist/index.js":
/***/ ((module) => {

module.exports =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __nested_webpack_require_187__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;
/******/
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __nested_webpack_require_187__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__nested_webpack_require_187__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__nested_webpack_require_187__.c = installedModules;
/******/
/******/ 	// __webpack_public_path__
/******/ 	__nested_webpack_require_187__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __nested_webpack_require_187__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __nested_webpack_require_1468__) {

	module.exports = __nested_webpack_require_1468__(1);


/***/ }),
/* 1 */
/***/ (function(module, exports, __nested_webpack_require_1587__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	  value: true
	});
	
	var _utils = __nested_webpack_require_1587__(2);
	
	Object.defineProperty(exports, 'combineChunks', {
	  enumerable: true,
	  get: function get() {
	    return _utils.combineChunks;
	  }
	});
	Object.defineProperty(exports, 'fillInChunks', {
	  enumerable: true,
	  get: function get() {
	    return _utils.fillInChunks;
	  }
	});
	Object.defineProperty(exports, 'findAll', {
	  enumerable: true,
	  get: function get() {
	    return _utils.findAll;
	  }
	});
	Object.defineProperty(exports, 'findChunks', {
	  enumerable: true,
	  get: function get() {
	    return _utils.findChunks;
	  }
	});

/***/ }),
/* 2 */
/***/ (function(module, exports) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	  value: true
	});
	
	
	/**
	 * Creates an array of chunk objects representing both higlightable and non highlightable pieces of text that match each search word.
	 * @return Array of "chunks" (where a Chunk is { start:number, end:number, highlight:boolean })
	 */
	var findAll = exports.findAll = function findAll(_ref) {
	  var autoEscape = _ref.autoEscape,
	      _ref$caseSensitive = _ref.caseSensitive,
	      caseSensitive = _ref$caseSensitive === undefined ? false : _ref$caseSensitive,
	      _ref$findChunks = _ref.findChunks,
	      findChunks = _ref$findChunks === undefined ? defaultFindChunks : _ref$findChunks,
	      sanitize = _ref.sanitize,
	      searchWords = _ref.searchWords,
	      textToHighlight = _ref.textToHighlight;
	  return fillInChunks({
	    chunksToHighlight: combineChunks({
	      chunks: findChunks({
	        autoEscape: autoEscape,
	        caseSensitive: caseSensitive,
	        sanitize: sanitize,
	        searchWords: searchWords,
	        textToHighlight: textToHighlight
	      })
	    }),
	    totalLength: textToHighlight ? textToHighlight.length : 0
	  });
	};
	
	/**
	 * Takes an array of {start:number, end:number} objects and combines chunks that overlap into single chunks.
	 * @return {start:number, end:number}[]
	 */
	
	
	var combineChunks = exports.combineChunks = function combineChunks(_ref2) {
	  var chunks = _ref2.chunks;
	
	  chunks = chunks.sort(function (first, second) {
	    return first.start - second.start;
	  }).reduce(function (processedChunks, nextChunk) {
	    // First chunk just goes straight in the array...
	    if (processedChunks.length === 0) {
	      return [nextChunk];
	    } else {
	      // ... subsequent chunks get checked to see if they overlap...
	      var prevChunk = processedChunks.pop();
	      if (nextChunk.start <= prevChunk.end) {
	        // It may be the case that prevChunk completely surrounds nextChunk, so take the
	        // largest of the end indeces.
	        var endIndex = Math.max(prevChunk.end, nextChunk.end);
	        processedChunks.push({ highlight: false, start: prevChunk.start, end: endIndex });
	      } else {
	        processedChunks.push(prevChunk, nextChunk);
	      }
	      return processedChunks;
	    }
	  }, []);
	
	  return chunks;
	};
	
	/**
	 * Examine text for any matches.
	 * If we find matches, add them to the returned array as a "chunk" object ({start:number, end:number}).
	 * @return {start:number, end:number}[]
	 */
	var defaultFindChunks = function defaultFindChunks(_ref3) {
	  var autoEscape = _ref3.autoEscape,
	      caseSensitive = _ref3.caseSensitive,
	      _ref3$sanitize = _ref3.sanitize,
	      sanitize = _ref3$sanitize === undefined ? defaultSanitize : _ref3$sanitize,
	      searchWords = _ref3.searchWords,
	      textToHighlight = _ref3.textToHighlight;
	
	  textToHighlight = sanitize(textToHighlight);
	
	  return searchWords.filter(function (searchWord) {
	    return searchWord;
	  }) // Remove empty words
	  .reduce(function (chunks, searchWord) {
	    searchWord = sanitize(searchWord);
	
	    if (autoEscape) {
	      searchWord = escapeRegExpFn(searchWord);
	    }
	
	    var regex = new RegExp(searchWord, caseSensitive ? 'g' : 'gi');
	
	    var match = void 0;
	    while (match = regex.exec(textToHighlight)) {
	      var _start = match.index;
	      var _end = regex.lastIndex;
	      // We do not return zero-length matches
	      if (_end > _start) {
	        chunks.push({ highlight: false, start: _start, end: _end });
	      }
	
	      // Prevent browsers like Firefox from getting stuck in an infinite loop
	      // See http://www.regexguru.com/2008/04/watch-out-for-zero-length-matches/
	      if (match.index === regex.lastIndex) {
	        regex.lastIndex++;
	      }
	    }
	
	    return chunks;
	  }, []);
	};
	// Allow the findChunks to be overridden in findAll,
	// but for backwards compatibility we export as the old name
	exports.findChunks = defaultFindChunks;
	
	/**
	 * Given a set of chunks to highlight, create an additional set of chunks
	 * to represent the bits of text between the highlighted text.
	 * @param chunksToHighlight {start:number, end:number}[]
	 * @param totalLength number
	 * @return {start:number, end:number, highlight:boolean}[]
	 */
	
	var fillInChunks = exports.fillInChunks = function fillInChunks(_ref4) {
	  var chunksToHighlight = _ref4.chunksToHighlight,
	      totalLength = _ref4.totalLength;
	
	  var allChunks = [];
	  var append = function append(start, end, highlight) {
	    if (end - start > 0) {
	      allChunks.push({
	        start: start,
	        end: end,
	        highlight: highlight
	      });
	    }
	  };
	
	  if (chunksToHighlight.length === 0) {
	    append(0, totalLength, false);
	  } else {
	    var lastIndex = 0;
	    chunksToHighlight.forEach(function (chunk) {
	      append(lastIndex, chunk.start, false);
	      append(chunk.start, chunk.end, true);
	      lastIndex = chunk.end;
	    });
	    append(lastIndex, totalLength, false);
	  }
	  return allChunks;
	};
	
	function defaultSanitize(string) {
	  return string;
	}
	
	function escapeRegExpFn(string) {
	  return string.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
	}

/***/ })
/******/ ]);
//# sourceMappingURL=index.js.map

/***/ })

}]);