(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[143],{

/***/ "../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ DataViews)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  countSelectorsByStatus: () => (countSelectorsByStatus),
  getCachedResolvers: () => (getCachedResolvers),
  getIsResolving: () => (getIsResolving),
  getResolutionError: () => (getResolutionError),
  getResolutionState: () => (getResolutionState),
  hasFinishedResolution: () => (hasFinishedResolution),
  hasResolutionFailed: () => (hasResolutionFailed),
  hasResolvingSelectors: () => (hasResolvingSelectors),
  hasStartedResolution: () => (hasStartedResolution),
  isResolving: () => (isResolving)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  failResolution: () => (failResolution),
  failResolutions: () => (failResolutions),
  finishResolution: () => (finishResolution),
  finishResolutions: () => (finishResolutions),
  invalidateResolution: () => (invalidateResolution),
  invalidateResolutionForStore: () => (invalidateResolutionForStore),
  invalidateResolutionForStoreSelector: () => (invalidateResolutionForStoreSelector),
  startResolution: () => (startResolution),
  startResolutions: () => (startResolutions)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/h-stack/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/h-stack/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@5.8.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@5.8.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.8.1_react@17.0.2/node_modules/@wordpress/primitives/build-module/svg/index.js
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.8.1_react@17.0.2/node_modules/@wordpress/primitives/build-module/svg/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@17.0.2/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@17.0.2/node_modules/react/jsx-runtime.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/arrow-up.js
/**
 * WordPress dependencies
 */


const arrowUp = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M12 3.9 6.5 9.5l1 1 3.8-3.7V20h1.5V6.8l3.7 3.7 1-1z"
  })
});
/* harmony default export */ const arrow_up = (arrowUp);
//# sourceMappingURL=arrow-up.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/arrow-down.js
/**
 * WordPress dependencies
 */


const arrowDown = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "m16.5 13.5-3.7 3.7V4h-1.5v13.2l-3.8-3.7-1 1 5.5 5.6 5.5-5.6z"
  })
});
/* harmony default export */ const arrow_down = (arrowDown);
//# sourceMappingURL=arrow-down.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/constants.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */

// Filter operators.
const OPERATOR_IS = 'is';
const OPERATOR_IS_NOT = 'isNot';
const OPERATOR_IS_ANY = 'isAny';
const OPERATOR_IS_NONE = 'isNone';
const OPERATOR_IS_ALL = 'isAll';
const OPERATOR_IS_NOT_ALL = 'isNotAll';
const ALL_OPERATORS = [OPERATOR_IS, OPERATOR_IS_NOT, OPERATOR_IS_ANY, OPERATOR_IS_NONE, OPERATOR_IS_ALL, OPERATOR_IS_NOT_ALL];
const OPERATORS = {
  [OPERATOR_IS]: {
    key: 'is-filter',
    label: (0,build_module.__)('Is')
  },
  [OPERATOR_IS_NOT]: {
    key: 'is-not-filter',
    label: (0,build_module.__)('Is not')
  },
  [OPERATOR_IS_ANY]: {
    key: 'is-any-filter',
    label: (0,build_module.__)('Is any')
  },
  [OPERATOR_IS_NONE]: {
    key: 'is-none-filter',
    label: (0,build_module.__)('Is none')
  },
  [OPERATOR_IS_ALL]: {
    key: 'is-all-filter',
    label: (0,build_module.__)('Is all')
  },
  [OPERATOR_IS_NOT_ALL]: {
    key: 'is-not-all-filter',
    label: (0,build_module.__)('Is not all')
  }
};
const SORTING_DIRECTIONS = ['asc', 'desc'];
const sortArrows = {
  asc: '↑',
  desc: '↓'
};
const sortValues = {
  asc: 'ascending',
  desc: 'descending'
};
const sortLabels = {
  asc: (0,build_module.__)('Sort ascending'),
  desc: (0,build_module.__)('Sort descending')
};
const sortIcons = {
  asc: arrow_up,
  desc: arrow_down
};

// View layouts.
const LAYOUT_TABLE = 'table';
const LAYOUT_GRID = 'grid';
const LAYOUT_LIST = 'list';
//# sourceMappingURL=constants.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-context/index.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


const DataViewsContext = (0,react.createContext)({
  view: {
    type: LAYOUT_TABLE
  },
  onChangeView: () => {},
  fields: [],
  data: [],
  paginationInfo: {
    totalItems: 0,
    totalPages: 0
  },
  selection: [],
  onChangeSelection: () => {},
  setOpenedFilter: () => {},
  openedFilter: null,
  getItemId: item => item.id,
  density: 0
});
/* harmony default export */ const dataviews_context = (DataViewsContext);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/button/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/funnel.js
/**
 * WordPress dependencies
 */


const funnel = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M10 17.5H14V16H10V17.5ZM6 6V7.5H18V6H6ZM8 12.5H16V11H8V12.5Z"
  })
});
/* harmony default export */ const library_funnel = (funnel);
//# sourceMappingURL=funnel.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/flex/flex-item/component.js + 1 modules
var flex_item_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/flex/flex-item/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/select-control/index.js + 3 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/select-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown/index.js + 8 modules
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tooltip/index.js + 6 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tooltip/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var build_module_icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/v-stack/component.js + 1 modules
var v_stack_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/v-stack/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+element@6.8.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js
/**
 * Internal dependencies
 */


/**
 * Object containing a React element.
 *
 * @typedef {import('react').ReactElement} Element
 */

let indoc, offset, output, stack;

/**
 * Matches tags in the localized string
 *
 * This is used for extracting the tag pattern groups for parsing the localized
 * string and along with the map converting it to a react element.
 *
 * There are four references extracted using this tokenizer:
 *
 * match: Full match of the tag (i.e. <strong>, </strong>, <br/>)
 * isClosing: The closing slash, if it exists.
 * name: The name portion of the tag (strong, br) (if )
 * isSelfClosed: The slash on a self closing tag, if it exists.
 *
 * @type {RegExp}
 */
const tokenizer = /<(\/)?(\w+)\s*(\/)?>/g;

/**
 * The stack frame tracking parse progress.
 *
 * @typedef Frame
 *
 * @property {Element}   element            A parent element which may still have
 * @property {number}    tokenStart         Offset at which parent element first
 *                                          appears.
 * @property {number}    tokenLength        Length of string marking start of parent
 *                                          element.
 * @property {number}    [prevOffset]       Running offset at which parsing should
 *                                          continue.
 * @property {number}    [leadingTextStart] Offset at which last closing element
 *                                          finished, used for finding text between
 *                                          elements.
 * @property {Element[]} children           Children.
 */

/**
 * Tracks recursive-descent parse state.
 *
 * This is a Stack frame holding parent elements until all children have been
 * parsed.
 *
 * @private
 * @param {Element} element            A parent element which may still have
 *                                     nested children not yet parsed.
 * @param {number}  tokenStart         Offset at which parent element first
 *                                     appears.
 * @param {number}  tokenLength        Length of string marking start of parent
 *                                     element.
 * @param {number}  [prevOffset]       Running offset at which parsing should
 *                                     continue.
 * @param {number}  [leadingTextStart] Offset at which last closing element
 *                                     finished, used for finding text between
 *                                     elements.
 *
 * @return {Frame} The stack frame tracking parse progress.
 */
function createFrame(element, tokenStart, tokenLength, prevOffset, leadingTextStart) {
  return {
    element,
    tokenStart,
    tokenLength,
    prevOffset,
    leadingTextStart,
    children: []
  };
}

/**
 * This function creates an interpolated element from a passed in string with
 * specific tags matching how the string should be converted to an element via
 * the conversion map value.
 *
 * @example
 * For example, for the given string:
 *
 * "This is a <span>string</span> with <a>a link</a> and a self-closing
 * <CustomComponentB/> tag"
 *
 * You would have something like this as the conversionMap value:
 *
 * ```js
 * {
 *     span: <span />,
 *     a: <a href={ 'https://github.com' } />,
 *     CustomComponentB: <CustomComponent />,
 * }
 * ```
 *
 * @param {string}                  interpolatedString The interpolation string to be parsed.
 * @param {Record<string, Element>} conversionMap      The map used to convert the string to
 *                                                     a react element.
 * @throws {TypeError}
 * @return {Element}  A wp element.
 */
const createInterpolateElement = (interpolatedString, conversionMap) => {
  indoc = interpolatedString;
  offset = 0;
  output = [];
  stack = [];
  tokenizer.lastIndex = 0;
  if (!isValidConversionMap(conversionMap)) {
    throw new TypeError('The conversionMap provided is not valid. It must be an object with values that are React Elements');
  }
  do {
    // twiddle our thumbs
  } while (proceed(conversionMap));
  return (0,react.createElement)(react.Fragment, null, ...output);
};

/**
 * Validate conversion map.
 *
 * A map is considered valid if it's an object and every value in the object
 * is a React Element
 *
 * @private
 *
 * @param {Object} conversionMap The map being validated.
 *
 * @return {boolean}  True means the map is valid.
 */
const isValidConversionMap = conversionMap => {
  const isObject = typeof conversionMap === 'object';
  const values = isObject && Object.values(conversionMap);
  return isObject && values.length && values.every(element => (0,react.isValidElement)(element));
};

/**
 * This is the iterator over the matches in the string.
 *
 * @private
 *
 * @param {Object} conversionMap The conversion map for the string.
 *
 * @return {boolean} true for continuing to iterate, false for finished.
 */
function proceed(conversionMap) {
  const next = nextToken();
  const [tokenType, name, startOffset, tokenLength] = next;
  const stackDepth = stack.length;
  const leadingTextStart = startOffset > offset ? offset : null;
  if (!conversionMap[name]) {
    addText();
    return false;
  }
  switch (tokenType) {
    case 'no-more-tokens':
      if (stackDepth !== 0) {
        const {
          leadingTextStart: stackLeadingText,
          tokenStart
        } = stack.pop();
        output.push(indoc.substr(stackLeadingText, tokenStart));
      }
      addText();
      return false;
    case 'self-closed':
      if (0 === stackDepth) {
        if (null !== leadingTextStart) {
          output.push(indoc.substr(leadingTextStart, startOffset - leadingTextStart));
        }
        output.push(conversionMap[name]);
        offset = startOffset + tokenLength;
        return true;
      }

      // Otherwise we found an inner element.
      addChild(createFrame(conversionMap[name], startOffset, tokenLength));
      offset = startOffset + tokenLength;
      return true;
    case 'opener':
      stack.push(createFrame(conversionMap[name], startOffset, tokenLength, startOffset + tokenLength, leadingTextStart));
      offset = startOffset + tokenLength;
      return true;
    case 'closer':
      // If we're not nesting then this is easy - close the block.
      if (1 === stackDepth) {
        closeOuterElement(startOffset);
        offset = startOffset + tokenLength;
        return true;
      }

      // Otherwise we're nested and we have to close out the current
      // block and add it as a innerBlock to the parent.
      const stackTop = stack.pop();
      const text = indoc.substr(stackTop.prevOffset, startOffset - stackTop.prevOffset);
      stackTop.children.push(text);
      stackTop.prevOffset = startOffset + tokenLength;
      const frame = createFrame(stackTop.element, stackTop.tokenStart, stackTop.tokenLength, startOffset + tokenLength);
      frame.children = stackTop.children;
      addChild(frame);
      offset = startOffset + tokenLength;
      return true;
    default:
      addText();
      return false;
  }
}

/**
 * Grabs the next token match in the string and returns it's details.
 *
 * @private
 *
 * @return {Array}  An array of details for the token matched.
 */
function nextToken() {
  const matches = tokenizer.exec(indoc);
  // We have no more tokens.
  if (null === matches) {
    return ['no-more-tokens'];
  }
  const startedAt = matches.index;
  const [match, isClosing, name, isSelfClosed] = matches;
  const length = match.length;
  if (isSelfClosed) {
    return ['self-closed', name, startedAt, length];
  }
  if (isClosing) {
    return ['closer', name, startedAt, length];
  }
  return ['opener', name, startedAt, length];
}

/**
 * Pushes text extracted from the indoc string to the output stack given the
 * current rawLength value and offset (if rawLength is provided ) or the
 * indoc.length and offset.
 *
 * @private
 */
function addText() {
  const length = indoc.length - offset;
  if (0 === length) {
    return;
  }
  output.push(indoc.substr(offset, length));
}

/**
 * Pushes a child element to the associated parent element's children for the
 * parent currently active in the stack.
 *
 * @private
 *
 * @param {Frame} frame The Frame containing the child element and it's
 *                      token information.
 */
function addChild(frame) {
  const {
    element,
    tokenStart,
    tokenLength,
    prevOffset,
    children
  } = frame;
  const parent = stack[stack.length - 1];
  const text = indoc.substr(parent.prevOffset, tokenStart - parent.prevOffset);
  if (text) {
    parent.children.push(text);
  }
  parent.children.push((0,react.cloneElement)(element, null, ...children));
  parent.prevOffset = prevOffset ? prevOffset : tokenStart + tokenLength;
}

/**
 * This is called for closing tags. It creates the element currently active in
 * the stack.
 *
 * @private
 *
 * @param {number} endOffset Offset at which the closing tag for the element
 *                           begins in the string. If this is greater than the
 *                           prevOffset attached to the element, then this
 *                           helps capture any remaining nested text nodes in
 *                           the element.
 */
function closeOuterElement(endOffset) {
  const {
    element,
    leadingTextStart,
    prevOffset,
    tokenStart,
    children
  } = stack.pop();
  const text = endOffset ? indoc.substr(prevOffset, endOffset - prevOffset) : indoc.substr(prevOffset);
  if (text) {
    children.push(text);
  }
  if (null !== leadingTextStart) {
    output.push(indoc.substr(leadingTextStart, tokenStart - leadingTextStart));
  }
  output.push((0,react.cloneElement)(element, null, ...children));
}
/* harmony default export */ const create_interpolate_element = (createInterpolateElement);
//# sourceMappingURL=create-interpolate-element.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/close-small.js
var close_small = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/close-small.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/WENSINUV.js
var WENSINUV = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/WENSINUV.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/HKOOKEDE.js
var HKOOKEDE = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/HKOOKEDE.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/P2OTTZSX.js
"use client";



// src/tag/tag-context.tsx

var TagValueContext = (0,react.createContext)(null);
var TagRemoveIdContext = (0,react.createContext)(
  null
);
var ctx = (0,HKOOKEDE/* createStoreContext */.B0)(
  [WENSINUV/* CompositeContextProvider */.ws],
  [WENSINUV/* CompositeScopedContextProvider */.aN]
);
var useTagContext = ctx.useContext;
var useTagScopedContext = ctx.useScopedContext;
var useTagProviderContext = ctx.useProviderContext;
var TagContextProvider = ctx.ContextProvider;
var TagScopedContextProvider = ctx.ScopedContextProvider;



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/UVQLZ7T5.js + 1 modules
var UVQLZ7T5 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/UVQLZ7T5.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/CBC47ZYL.js
var CBC47ZYL = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/CBC47ZYL.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/2GXGCHW6.js
var _2GXGCHW6 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/2GXGCHW6.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/Z32BISHQ.js
var Z32BISHQ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/Z32BISHQ.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/3YLGPPWQ.js
var _3YLGPPWQ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/3YLGPPWQ.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/D7EIQZAU.js
var D7EIQZAU = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/D7EIQZAU.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/3UYWTADI.js
var _3UYWTADI = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/3UYWTADI.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/EQQLU3CG.js
var EQQLU3CG = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/EQQLU3CG.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/PBFD2E7P.js
var PBFD2E7P = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/PBFD2E7P.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/US4USQPI.js
var US4USQPI = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/US4USQPI.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/3YLGPPWQ.js
var _chunks_3YLGPPWQ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/3YLGPPWQ.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/combobox/combobox-store.js
"use client";












// src/combobox/combobox-store.ts
var isTouchSafari = (0,US4USQPI/* isSafari */.nr)() && (0,US4USQPI/* isTouchDevice */.CN)();
function createComboboxStore(_a = {}) {
  var _b = _a, {
    tag
  } = _b, props = (0,_chunks_3YLGPPWQ/* __objRest */.YG)(_b, [
    "tag"
  ]);
  const store = (0,EQQLU3CG/* mergeStore */.od)(props.store, (0,EQQLU3CG/* pick */.Up)(tag, ["value", "rtl"]));
  (0,EQQLU3CG/* throwOnConflictingProps */.UE)(props, store);
  const tagState = tag == null ? void 0 : tag.getState();
  const syncState = store == null ? void 0 : store.getState();
  const activeId = (0,PBFD2E7P/* defaultValue */.Jh)(
    props.activeId,
    syncState == null ? void 0 : syncState.activeId,
    props.defaultActiveId,
    null
  );
  const composite = (0,D7EIQZAU/* createCompositeStore */.z)((0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    activeId,
    includesBaseElement: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.includesBaseElement,
      syncState == null ? void 0 : syncState.includesBaseElement,
      true
    ),
    orientation: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.orientation,
      syncState == null ? void 0 : syncState.orientation,
      "vertical"
    ),
    focusLoop: (0,PBFD2E7P/* defaultValue */.Jh)(props.focusLoop, syncState == null ? void 0 : syncState.focusLoop, true),
    focusWrap: (0,PBFD2E7P/* defaultValue */.Jh)(props.focusWrap, syncState == null ? void 0 : syncState.focusWrap, true),
    virtualFocus: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.virtualFocus,
      syncState == null ? void 0 : syncState.virtualFocus,
      true
    )
  }));
  const popover = (0,_3UYWTADI/* createPopoverStore */.N)((0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    placement: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.placement,
      syncState == null ? void 0 : syncState.placement,
      "bottom-start"
    )
  }));
  const value = (0,PBFD2E7P/* defaultValue */.Jh)(
    props.value,
    syncState == null ? void 0 : syncState.value,
    props.defaultValue,
    ""
  );
  const selectedValue = (0,PBFD2E7P/* defaultValue */.Jh)(
    props.selectedValue,
    syncState == null ? void 0 : syncState.selectedValue,
    tagState == null ? void 0 : tagState.values,
    props.defaultSelectedValue,
    ""
  );
  const multiSelectable = Array.isArray(selectedValue);
  const initialState = (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, composite.getState()), popover.getState()), {
    value,
    selectedValue,
    resetValueOnSelect: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.resetValueOnSelect,
      syncState == null ? void 0 : syncState.resetValueOnSelect,
      multiSelectable
    ),
    resetValueOnHide: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.resetValueOnHide,
      syncState == null ? void 0 : syncState.resetValueOnHide,
      multiSelectable && !tag
    ),
    activeValue: syncState == null ? void 0 : syncState.activeValue
  });
  const combobox = (0,EQQLU3CG/* createStore */.y$)(initialState, composite, popover, store);
  if (isTouchSafari) {
    (0,EQQLU3CG/* setup */.mj)(
      combobox,
      () => (0,EQQLU3CG/* sync */.OH)(combobox, ["virtualFocus"], () => {
        combobox.setState("virtualFocus", false);
      })
    );
  }
  (0,EQQLU3CG/* setup */.mj)(combobox, () => {
    if (!tag) return;
    return (0,PBFD2E7P/* chain */.cy)(
      (0,EQQLU3CG/* sync */.OH)(combobox, ["selectedValue"], (state) => {
        if (!Array.isArray(state.selectedValue)) return;
        tag.setValues(state.selectedValue);
      }),
      (0,EQQLU3CG/* sync */.OH)(tag, ["values"], (state) => {
        combobox.setState("selectedValue", state.values);
      })
    );
  });
  (0,EQQLU3CG/* setup */.mj)(
    combobox,
    () => (0,EQQLU3CG/* sync */.OH)(combobox, ["resetValueOnHide", "mounted"], (state) => {
      if (!state.resetValueOnHide) return;
      if (state.mounted) return;
      combobox.setState("value", value);
    })
  );
  (0,EQQLU3CG/* setup */.mj)(
    combobox,
    () => (0,EQQLU3CG/* batch */.vA)(combobox, ["mounted"], (state) => {
      if (state.mounted) return;
      combobox.setState("activeId", activeId);
      combobox.setState("moves", 0);
    })
  );
  (0,EQQLU3CG/* setup */.mj)(
    combobox,
    () => (0,EQQLU3CG/* sync */.OH)(combobox, ["moves", "activeId"], (state, prevState) => {
      if (state.moves === prevState.moves) {
        combobox.setState("activeValue", void 0);
      }
    })
  );
  (0,EQQLU3CG/* setup */.mj)(
    combobox,
    () => (0,EQQLU3CG/* batch */.vA)(combobox, ["moves", "renderedItems"], (state, prev) => {
      if (state.moves === prev.moves) return;
      const { activeId: activeId2 } = combobox.getState();
      const activeItem = composite.item(activeId2);
      combobox.setState("activeValue", activeItem == null ? void 0 : activeItem.value);
    })
  );
  return (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, popover), composite), combobox), {
    tag,
    setValue: (value2) => combobox.setState("value", value2),
    resetValue: () => combobox.setState("value", initialState.value),
    setSelectedValue: (selectedValue2) => combobox.setState("selectedValue", selectedValue2)
  });
}


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/7BSNT25J.js
"use client";







// src/combobox/combobox-store.ts

function useComboboxStoreProps(store, update, props) {
  (0,Z32BISHQ/* useUpdateEffect */.w5)(update, [props.tag]);
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "value", "setValue");
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "selectedValue", "setSelectedValue");
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "resetValueOnHide");
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "resetValueOnSelect");
  return Object.assign(
    (0,UVQLZ7T5/* useCompositeStoreProps */.Y)(
      (0,CBC47ZYL/* usePopoverStoreProps */.o)(store, update, props),
      update,
      props
    ),
    { tag: props.tag }
  );
}
function useComboboxStore(props = {}) {
  const tag = useTagContext();
  props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    tag: props.tag !== void 0 ? props.tag : tag
  });
  const [store, update] = (0,_2GXGCHW6/* useStore */.Pj)(createComboboxStore, props);
  return useComboboxStoreProps(store, update, props);
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/54MGSIOI.js
var _54MGSIOI = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/54MGSIOI.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/DWZ7E5TJ.js
"use client";




// src/combobox/combobox-context.tsx

var ComboboxListRoleContext = (0,react.createContext)(
  void 0
);
var DWZ7E5TJ_ctx = (0,HKOOKEDE/* createStoreContext */.B0)(
  [_54MGSIOI/* PopoverContextProvider */.wf, WENSINUV/* CompositeContextProvider */.ws],
  [_54MGSIOI/* PopoverScopedContextProvider */.s1, WENSINUV/* CompositeScopedContextProvider */.aN]
);
var useComboboxContext = DWZ7E5TJ_ctx.useContext;
var useComboboxScopedContext = DWZ7E5TJ_ctx.useScopedContext;
var useComboboxProviderContext = DWZ7E5TJ_ctx.useProviderContext;
var ComboboxContextProvider = DWZ7E5TJ_ctx.ContextProvider;
var ComboboxScopedContextProvider = DWZ7E5TJ_ctx.ScopedContextProvider;
var ComboboxItemValueContext = (0,react.createContext)(
  void 0
);
var ComboboxItemCheckedContext = (0,react.createContext)(false);



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/combobox/combobox-provider.js
"use client";



















// src/combobox/combobox-provider.tsx

function ComboboxProvider(props = {}) {
  const store = useComboboxStore(props);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(ComboboxContextProvider, { value: store, children: props.children });
}


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/combobox/combobox-label.js
"use client";











// src/combobox/combobox-label.tsx

var TagName = "label";
var useComboboxLabel = (0,HKOOKEDE/* createHook */.ab)(
  function useComboboxLabel2(_a) {
    var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
    const context = useComboboxProviderContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const comboboxId = store.useState((state) => {
      var _a2;
      return (_a2 = state.baseElement) == null ? void 0 : _a2.id;
    });
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      htmlFor: comboboxId
    }, props);
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
  }
);
var ComboboxLabel = (0,HKOOKEDE/* memo */.ph)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function ComboboxLabel2(props) {
    const htmlProps = useComboboxLabel(props);
    return (0,HKOOKEDE/* createElement */.n)(TagName, htmlProps);
  })
);


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/74NFH3UH.js
"use client";





// src/popover/popover-anchor.tsx
var _74NFH3UH_TagName = "div";
var usePopoverAnchor = (0,HKOOKEDE/* createHook */.ab)(
  function usePopoverAnchor2(_a) {
    var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
    const context = (0,_54MGSIOI/* usePopoverProviderContext */.zG)();
    store = store || context;
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(store == null ? void 0 : store.setAnchorElement, props.ref)
    });
    return props;
  }
);
var PopoverAnchor = (0,HKOOKEDE/* forwardRef */.Rf)(function PopoverAnchor2(props) {
  const htmlProps = usePopoverAnchor(props);
  return (0,HKOOKEDE/* createElement */.n)(_74NFH3UH_TagName, htmlProps);
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/TW35PKTK.js
var TW35PKTK = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/TW35PKTK.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/HWOIWM4O.js
var HWOIWM4O = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/HWOIWM4O.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/utils/events.js
var events = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/utils/events.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/utils/focus.js
var utils_focus = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/utils/focus.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/combobox/combobox.js
"use client";
















// src/combobox/combobox.tsx






var combobox_TagName = "input";
function isFirstItemAutoSelected(items, activeValue, autoSelect) {
  if (!autoSelect) return false;
  const firstItem = items.find((item) => !item.disabled && item.value);
  return (firstItem == null ? void 0 : firstItem.value) === activeValue;
}
function hasCompletionString(value, activeValue) {
  if (!activeValue) return false;
  if (value == null) return false;
  value = (0,PBFD2E7P/* normalizeString */.J2)(value);
  return activeValue.length > value.length && activeValue.toLowerCase().indexOf(value.toLowerCase()) === 0;
}
function isInputEvent(event) {
  return event.type === "input";
}
function isAriaAutoCompleteValue(value) {
  return value === "inline" || value === "list" || value === "both" || value === "none";
}
function getDefaultAutoSelectId(items) {
  const item = items.find((item2) => {
    var _a;
    if (item2.disabled) return false;
    return ((_a = item2.element) == null ? void 0 : _a.getAttribute("role")) !== "tab";
  });
  return item == null ? void 0 : item.id;
}
var useCombobox = (0,HKOOKEDE/* createHook */.ab)(
  function useCombobox2(_a) {
    var _b = _a, {
      store,
      focusable = true,
      autoSelect: autoSelectProp = false,
      getAutoSelectId,
      setValueOnChange,
      showMinLength = 0,
      showOnChange,
      showOnMouseDown,
      showOnClick = showOnMouseDown,
      showOnKeyDown,
      showOnKeyPress = showOnKeyDown,
      blurActiveItemOnClick,
      setValueOnClick = true,
      moveOnKeyPress = true,
      autoComplete = "list"
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "focusable",
      "autoSelect",
      "getAutoSelectId",
      "setValueOnChange",
      "showMinLength",
      "showOnChange",
      "showOnMouseDown",
      "showOnClick",
      "showOnKeyDown",
      "showOnKeyPress",
      "blurActiveItemOnClick",
      "setValueOnClick",
      "moveOnKeyPress",
      "autoComplete"
    ]);
    const context = useComboboxProviderContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const [valueUpdated, forceValueUpdate] = (0,Z32BISHQ/* useForceUpdate */.CH)();
    const canAutoSelectRef = (0,react.useRef)(false);
    const composingRef = (0,react.useRef)(false);
    const autoSelect = store.useState(
      (state) => state.virtualFocus && autoSelectProp
    );
    const inline = autoComplete === "inline" || autoComplete === "both";
    const [canInline, setCanInline] = (0,react.useState)(inline);
    (0,Z32BISHQ/* useUpdateLayoutEffect */.Kp)(() => {
      if (!inline) return;
      setCanInline(true);
    }, [inline]);
    const storeValue = store.useState("value");
    const prevSelectedValueRef = (0,react.useRef)();
    (0,react.useEffect)(() => {
      return (0,EQQLU3CG/* sync */.OH)(store, ["selectedValue", "activeId"], (_, prev) => {
        prevSelectedValueRef.current = prev.selectedValue;
      });
    }, []);
    const inlineActiveValue = store.useState((state) => {
      var _a2;
      if (!inline) return;
      if (!canInline) return;
      if (state.activeValue && Array.isArray(state.selectedValue)) {
        if (state.selectedValue.includes(state.activeValue)) return;
        if ((_a2 = prevSelectedValueRef.current) == null ? void 0 : _a2.includes(state.activeValue)) return;
      }
      return state.activeValue;
    });
    const items = store.useState("renderedItems");
    const open = store.useState("open");
    const contentElement = store.useState("contentElement");
    const value = (0,react.useMemo)(() => {
      if (!inline) return storeValue;
      if (!canInline) return storeValue;
      const firstItemAutoSelected = isFirstItemAutoSelected(
        items,
        inlineActiveValue,
        autoSelect
      );
      if (firstItemAutoSelected) {
        if (hasCompletionString(storeValue, inlineActiveValue)) {
          const slice = (inlineActiveValue == null ? void 0 : inlineActiveValue.slice(storeValue.length)) || "";
          return storeValue + slice;
        }
        return storeValue;
      }
      return inlineActiveValue || storeValue;
    }, [inline, canInline, items, inlineActiveValue, autoSelect, storeValue]);
    (0,react.useEffect)(() => {
      const element = ref.current;
      if (!element) return;
      const onCompositeItemMove = () => setCanInline(true);
      element.addEventListener("combobox-item-move", onCompositeItemMove);
      return () => {
        element.removeEventListener("combobox-item-move", onCompositeItemMove);
      };
    }, []);
    (0,react.useEffect)(() => {
      if (!inline) return;
      if (!canInline) return;
      if (!inlineActiveValue) return;
      const firstItemAutoSelected = isFirstItemAutoSelected(
        items,
        inlineActiveValue,
        autoSelect
      );
      if (!firstItemAutoSelected) return;
      if (!hasCompletionString(storeValue, inlineActiveValue)) return;
      let cleanup = PBFD2E7P/* noop */.lQ;
      queueMicrotask(() => {
        const element = ref.current;
        if (!element) return;
        const { start: prevStart, end: prevEnd } = (0,HWOIWM4O/* getTextboxSelection */.Zy)(element);
        const nextStart = storeValue.length;
        const nextEnd = inlineActiveValue.length;
        (0,HWOIWM4O/* setSelectionRange */.eG)(element, nextStart, nextEnd);
        cleanup = () => {
          if (!(0,utils_focus/* hasFocus */.AJ)(element)) return;
          const { start, end } = (0,HWOIWM4O/* getTextboxSelection */.Zy)(element);
          if (start !== nextStart) return;
          if (end !== nextEnd) return;
          (0,HWOIWM4O/* setSelectionRange */.eG)(element, prevStart, prevEnd);
        };
      });
      return () => cleanup();
    }, [
      valueUpdated,
      inline,
      canInline,
      inlineActiveValue,
      items,
      autoSelect,
      storeValue
    ]);
    const scrollingElementRef = (0,react.useRef)(null);
    const getAutoSelectIdProp = (0,Z32BISHQ/* useEvent */._q)(getAutoSelectId);
    const autoSelectIdRef = (0,react.useRef)(null);
    (0,react.useEffect)(() => {
      if (!open) return;
      if (!contentElement) return;
      const scrollingElement = (0,HWOIWM4O/* getScrollingElement */.qj)(contentElement);
      if (!scrollingElement) return;
      scrollingElementRef.current = scrollingElement;
      const onUserScroll = () => {
        canAutoSelectRef.current = false;
      };
      const onScroll = () => {
        if (!store) return;
        if (!canAutoSelectRef.current) return;
        const { activeId } = store.getState();
        if (activeId === null) return;
        if (activeId === autoSelectIdRef.current) return;
        canAutoSelectRef.current = false;
      };
      const options = { passive: true, capture: true };
      scrollingElement.addEventListener("wheel", onUserScroll, options);
      scrollingElement.addEventListener("touchmove", onUserScroll, options);
      scrollingElement.addEventListener("scroll", onScroll, options);
      return () => {
        scrollingElement.removeEventListener("wheel", onUserScroll, true);
        scrollingElement.removeEventListener("touchmove", onUserScroll, true);
        scrollingElement.removeEventListener("scroll", onScroll, true);
      };
    }, [open, contentElement, store]);
    (0,Z32BISHQ/* useSafeLayoutEffect */.UQ)(() => {
      if (!storeValue) return;
      if (composingRef.current) return;
      canAutoSelectRef.current = true;
    }, [storeValue]);
    (0,Z32BISHQ/* useSafeLayoutEffect */.UQ)(() => {
      if (autoSelect !== "always" && open) return;
      canAutoSelectRef.current = open;
    }, [autoSelect, open]);
    const resetValueOnSelect = store.useState("resetValueOnSelect");
    (0,Z32BISHQ/* useUpdateEffect */.w5)(() => {
      var _a2, _b2;
      const canAutoSelect = canAutoSelectRef.current;
      if (!store) return;
      if (!open) return;
      if ((!autoSelect || !canAutoSelect) && !resetValueOnSelect) return;
      const { baseElement, contentElement: contentElement2, activeId } = store.getState();
      if (baseElement && !(0,utils_focus/* hasFocus */.AJ)(baseElement)) return;
      if (contentElement2 == null ? void 0 : contentElement2.hasAttribute("data-placing")) {
        const observer = new MutationObserver(forceValueUpdate);
        observer.observe(contentElement2, { attributeFilter: ["data-placing"] });
        return () => observer.disconnect();
      }
      if (autoSelect && canAutoSelect) {
        const userAutoSelectId = getAutoSelectIdProp(items);
        const autoSelectId = userAutoSelectId !== void 0 ? userAutoSelectId : (_a2 = getDefaultAutoSelectId(items)) != null ? _a2 : store.first();
        autoSelectIdRef.current = autoSelectId;
        store.move(autoSelectId != null ? autoSelectId : null);
      } else {
        const element = (_b2 = store.item(activeId)) == null ? void 0 : _b2.element;
        if (element && "scrollIntoView" in element) {
          element.scrollIntoView({ block: "nearest", inline: "nearest" });
        }
      }
      return;
    }, [
      store,
      open,
      valueUpdated,
      storeValue,
      autoSelect,
      resetValueOnSelect,
      getAutoSelectIdProp,
      items
    ]);
    (0,react.useEffect)(() => {
      if (!inline) return;
      const combobox = ref.current;
      if (!combobox) return;
      const elements = [combobox, contentElement].filter(
        (value2) => !!value2
      );
      const onBlur2 = (event) => {
        if (elements.every((el) => (0,events/* isFocusEventOutside */.aG)(event, el))) {
          store == null ? void 0 : store.setValue(value);
        }
      };
      for (const element of elements) {
        element.addEventListener("focusout", onBlur2);
      }
      return () => {
        for (const element of elements) {
          element.removeEventListener("focusout", onBlur2);
        }
      };
    }, [inline, contentElement, store, value]);
    const canShow = (event) => {
      const currentTarget = event.currentTarget;
      return currentTarget.value.length >= showMinLength;
    };
    const onChangeProp = props.onChange;
    const showOnChangeProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(showOnChange != null ? showOnChange : canShow);
    const setValueOnChangeProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(
      // If the combobox is combined with tags, the value will be set by the tag
      // input component.
      setValueOnChange != null ? setValueOnChange : !store.tag
    );
    const onChange = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onChangeProp == null ? void 0 : onChangeProp(event);
      if (event.defaultPrevented) return;
      if (!store) return;
      const currentTarget = event.currentTarget;
      const { value: value2, selectionStart, selectionEnd } = currentTarget;
      const nativeEvent = event.nativeEvent;
      canAutoSelectRef.current = true;
      if (isInputEvent(nativeEvent)) {
        if (nativeEvent.isComposing) {
          canAutoSelectRef.current = false;
          composingRef.current = true;
        }
        if (inline) {
          const textInserted = nativeEvent.inputType === "insertText" || nativeEvent.inputType === "insertCompositionText";
          const caretAtEnd = selectionStart === value2.length;
          setCanInline(textInserted && caretAtEnd);
        }
      }
      if (setValueOnChangeProp(event)) {
        const isSameValue = value2 === store.getState().value;
        store.setValue(value2);
        queueMicrotask(() => {
          (0,HWOIWM4O/* setSelectionRange */.eG)(currentTarget, selectionStart, selectionEnd);
        });
        if (inline && autoSelect && isSameValue) {
          forceValueUpdate();
        }
      }
      if (showOnChangeProp(event)) {
        store.show();
      }
      if (!autoSelect || !canAutoSelectRef.current) {
        store.setActiveId(null);
      }
    });
    const onCompositionEndProp = props.onCompositionEnd;
    const onCompositionEnd = (0,Z32BISHQ/* useEvent */._q)((event) => {
      canAutoSelectRef.current = true;
      composingRef.current = false;
      onCompositionEndProp == null ? void 0 : onCompositionEndProp(event);
      if (event.defaultPrevented) return;
      if (!autoSelect) return;
      forceValueUpdate();
    });
    const onMouseDownProp = props.onMouseDown;
    const blurActiveItemOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(
      blurActiveItemOnClick != null ? blurActiveItemOnClick : () => !!(store == null ? void 0 : store.getState().includesBaseElement)
    );
    const setValueOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(setValueOnClick);
    const showOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(showOnClick != null ? showOnClick : canShow);
    const onMouseDown = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onMouseDownProp == null ? void 0 : onMouseDownProp(event);
      if (event.defaultPrevented) return;
      if (event.button) return;
      if (event.ctrlKey) return;
      if (!store) return;
      if (blurActiveItemOnClickProp(event)) {
        store.setActiveId(null);
      }
      if (setValueOnClickProp(event)) {
        store.setValue(value);
      }
      if (showOnClickProp(event)) {
        (0,events/* queueBeforeEvent */.nz)(event.currentTarget, "mouseup", store.show);
      }
    });
    const onKeyDownProp = props.onKeyDown;
    const showOnKeyPressProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(showOnKeyPress != null ? showOnKeyPress : canShow);
    const onKeyDown = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (!event.repeat) {
        canAutoSelectRef.current = false;
      }
      if (event.defaultPrevented) return;
      if (event.ctrlKey) return;
      if (event.altKey) return;
      if (event.shiftKey) return;
      if (event.metaKey) return;
      if (!store) return;
      const { open: open2, activeId } = store.getState();
      if (open2) return;
      if (activeId !== null) return;
      if (event.key === "ArrowUp" || event.key === "ArrowDown") {
        if (showOnKeyPressProp(event)) {
          event.preventDefault();
          store.show();
        }
      }
    });
    const onBlurProp = props.onBlur;
    const onBlur = (0,Z32BISHQ/* useEvent */._q)((event) => {
      canAutoSelectRef.current = false;
      onBlurProp == null ? void 0 : onBlurProp(event);
      if (event.defaultPrevented) return;
    });
    const id = (0,Z32BISHQ/* useId */.Bi)(props.id);
    const ariaAutoComplete = isAriaAutoCompleteValue(autoComplete) ? autoComplete : void 0;
    const isActiveItem = store.useState((state) => state.activeId === null);
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      id,
      role: "combobox",
      "aria-autocomplete": ariaAutoComplete,
      "aria-haspopup": (0,HWOIWM4O/* getPopupRole */.Tc)(contentElement, "listbox"),
      "aria-expanded": open,
      "aria-controls": contentElement == null ? void 0 : contentElement.id,
      "data-active-item": isActiveItem || void 0,
      value
    }, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref),
      onChange,
      onCompositionEnd,
      onMouseDown,
      onKeyDown,
      onBlur
    });
    props = (0,TW35PKTK/* useComposite */.T)((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store,
      focusable
    }, props), {
      // Enable inline autocomplete when the user moves from the combobox input
      // to an item.
      moveOnKeyPress: (event) => {
        if ((0,PBFD2E7P/* isFalsyBooleanCallback */.zO)(moveOnKeyPress, event)) return false;
        if (inline) setCanInline(true);
        return true;
      }
    }));
    props = usePopoverAnchor((0,_3YLGPPWQ/* __spreadValues */.IA)({ store }, props));
    return (0,_3YLGPPWQ/* __spreadValues */.IA)({ autoComplete: "off" }, props);
  }
);
var Combobox = (0,HKOOKEDE/* forwardRef */.Rf)(function Combobox2(props) {
  const htmlProps = useCombobox(props);
  return (0,HKOOKEDE/* createElement */.n)(combobox_TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/BSEL4YAF.js
var BSEL4YAF = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/BSEL4YAF.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/6ZVAPMHT.js
"use client";






// src/combobox/combobox-list.tsx



var _6ZVAPMHT_TagName = "div";
var useComboboxList = (0,HKOOKEDE/* createHook */.ab)(
  function useComboboxList2(_a) {
    var _b = _a, { store, alwaysVisible } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store", "alwaysVisible"]);
    const scopedContext = useComboboxScopedContext(true);
    const context = useComboboxContext();
    store = store || context;
    const scopedContextSameStore = !!store && store === scopedContext;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const id = (0,Z32BISHQ/* useId */.Bi)(props.id);
    const mounted = store.useState("mounted");
    const hidden = (0,BSEL4YAF/* isHidden */.dK)(mounted, props.hidden, alwaysVisible);
    const style = hidden ? (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props.style), { display: "none" }) : props.style;
    const multiSelectable = store.useState(
      (state) => Array.isArray(state.selectedValue)
    );
    const role = (0,Z32BISHQ/* useAttribute */.Cy)(ref, "role", props.role);
    const isCompositeRole = role === "listbox" || role === "tree" || role === "grid";
    const ariaMultiSelectable = isCompositeRole ? multiSelectable || void 0 : void 0;
    const [hasListboxInside, setHasListboxInside] = (0,react.useState)(false);
    const contentElement = store.useState("contentElement");
    (0,Z32BISHQ/* useSafeLayoutEffect */.UQ)(() => {
      if (!mounted) return;
      const element = ref.current;
      if (!element) return;
      if (contentElement !== element) return;
      const callback = () => {
        setHasListboxInside(!!element.querySelector("[role='listbox']"));
      };
      const observer = new MutationObserver(callback);
      observer.observe(element, {
        subtree: true,
        childList: true,
        attributeFilter: ["role"]
      });
      callback();
      return () => observer.disconnect();
    }, [mounted, contentElement]);
    if (!hasListboxInside) {
      props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
        role: "listbox",
        "aria-multiselectable": ariaMultiSelectable
      }, props);
    }
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(ComboboxScopedContextProvider, { value: store, children: /* @__PURE__ */ (0,jsx_runtime.jsx)(ComboboxListRoleContext.Provider, { value: role, children: element }) }),
      [store, role]
    );
    const setContentElement = id && (!scopedContext || !scopedContextSameStore) ? store.setContentElement : null;
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      id,
      hidden
    }, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(setContentElement, ref, props.ref),
      style
    });
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
  }
);
var ComboboxList = (0,HKOOKEDE/* forwardRef */.Rf)(function ComboboxList2(props) {
  const htmlProps = useComboboxList(props);
  return (0,HKOOKEDE/* createElement */.n)(_6ZVAPMHT_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/OBZMLI6J.js
"use client";





// src/composite/composite-hover.tsx




var OBZMLI6J_TagName = "div";
function getMouseDestination(event) {
  const relatedTarget = event.relatedTarget;
  if ((relatedTarget == null ? void 0 : relatedTarget.nodeType) === Node.ELEMENT_NODE) {
    return relatedTarget;
  }
  return null;
}
function hoveringInside(event) {
  const nextElement = getMouseDestination(event);
  if (!nextElement) return false;
  return (0,HWOIWM4O/* contains */.gR)(event.currentTarget, nextElement);
}
var symbol = Symbol("composite-hover");
function movingToAnotherItem(event) {
  let dest = getMouseDestination(event);
  if (!dest) return false;
  do {
    if ((0,PBFD2E7P/* hasOwnProperty */.mQ)(dest, symbol) && dest[symbol]) return true;
    dest = dest.parentElement;
  } while (dest);
  return false;
}
var useCompositeHover = (0,HKOOKEDE/* createHook */.ab)(
  function useCompositeHover2(_a) {
    var _b = _a, {
      store,
      focusOnHover = true,
      blurOnHoverEnd = !!focusOnHover
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "focusOnHover",
      "blurOnHoverEnd"
    ]);
    const context = (0,WENSINUV/* useCompositeContext */.k)();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const isMouseMoving = (0,Z32BISHQ/* useIsMouseMoving */.P$)();
    const onMouseMoveProp = props.onMouseMove;
    const focusOnHoverProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(focusOnHover);
    const onMouseMove = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onMouseMoveProp == null ? void 0 : onMouseMoveProp(event);
      if (event.defaultPrevented) return;
      if (!isMouseMoving()) return;
      if (!focusOnHoverProp(event)) return;
      if (!(0,utils_focus/* hasFocusWithin */.oW)(event.currentTarget)) {
        const baseElement = store == null ? void 0 : store.getState().baseElement;
        if (baseElement && !(0,utils_focus/* hasFocus */.AJ)(baseElement)) {
          baseElement.focus();
        }
      }
      store == null ? void 0 : store.setActiveId(event.currentTarget.id);
    });
    const onMouseLeaveProp = props.onMouseLeave;
    const blurOnHoverEndProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(blurOnHoverEnd);
    const onMouseLeave = (0,Z32BISHQ/* useEvent */._q)((event) => {
      var _a2;
      onMouseLeaveProp == null ? void 0 : onMouseLeaveProp(event);
      if (event.defaultPrevented) return;
      if (!isMouseMoving()) return;
      if (hoveringInside(event)) return;
      if (movingToAnotherItem(event)) return;
      if (!focusOnHoverProp(event)) return;
      if (!blurOnHoverEndProp(event)) return;
      store == null ? void 0 : store.setActiveId(null);
      (_a2 = store == null ? void 0 : store.getState().baseElement) == null ? void 0 : _a2.focus();
    });
    const ref = (0,react.useCallback)((element) => {
      if (!element) return;
      element[symbol] = true;
    }, []);
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref),
      onMouseMove,
      onMouseLeave
    });
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
  }
);
var OBZMLI6J_CompositeHover = (0,HKOOKEDE/* memo */.ph)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function CompositeHover2(props) {
    const htmlProps = useCompositeHover(props);
    return (0,HKOOKEDE/* createElement */.n)(OBZMLI6J_TagName, htmlProps);
  })
);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/3CCTMYB6.js
var _3CCTMYB6 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/3CCTMYB6.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/combobox/combobox-item.js
"use client";



















// src/combobox/combobox-item.tsx






var combobox_item_TagName = "div";
function isSelected(storeValue, itemValue) {
  if (itemValue == null) return;
  if (storeValue == null) return false;
  if (Array.isArray(storeValue)) {
    return storeValue.includes(itemValue);
  }
  return storeValue === itemValue;
}
function getItemRole(popupRole) {
  var _a;
  const itemRoleByPopupRole = {
    menu: "menuitem",
    listbox: "option",
    tree: "treeitem"
  };
  const key = popupRole;
  return (_a = itemRoleByPopupRole[key]) != null ? _a : "option";
}
var useComboboxItem = (0,HKOOKEDE/* createHook */.ab)(
  function useComboboxItem2(_a) {
    var _b = _a, {
      store,
      value,
      hideOnClick,
      setValueOnClick,
      selectValueOnClick = true,
      resetValueOnSelect,
      focusOnHover = false,
      moveOnKeyPress = true,
      getItem: getItemProp
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "value",
      "hideOnClick",
      "setValueOnClick",
      "selectValueOnClick",
      "resetValueOnSelect",
      "focusOnHover",
      "moveOnKeyPress",
      "getItem"
    ]);
    var _a2;
    const context = useComboboxScopedContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const getItem = (0,react.useCallback)(
      (item) => {
        const nextItem = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, item), { value });
        if (getItemProp) {
          return getItemProp(nextItem);
        }
        return nextItem;
      },
      [value, getItemProp]
    );
    const multiSelectable = store.useState(
      (state) => Array.isArray(state.selectedValue)
    );
    const selected = store.useState(
      (state) => isSelected(state.selectedValue, value)
    );
    const resetValueOnSelectState = store.useState("resetValueOnSelect");
    setValueOnClick = setValueOnClick != null ? setValueOnClick : !multiSelectable;
    hideOnClick = hideOnClick != null ? hideOnClick : value != null && !multiSelectable;
    const onClickProp = props.onClick;
    const setValueOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(setValueOnClick);
    const selectValueOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(selectValueOnClick);
    const resetValueOnSelectProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(
      (_a2 = resetValueOnSelect != null ? resetValueOnSelect : resetValueOnSelectState) != null ? _a2 : multiSelectable
    );
    const hideOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(hideOnClick);
    const onClick = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onClickProp == null ? void 0 : onClickProp(event);
      if (event.defaultPrevented) return;
      if ((0,events/* isDownloading */.RN)(event)) return;
      if ((0,events/* isOpeningInNewTab */.$b)(event)) return;
      if (value != null) {
        if (selectValueOnClickProp(event)) {
          if (resetValueOnSelectProp(event)) {
            store == null ? void 0 : store.resetValue();
          }
          store == null ? void 0 : store.setSelectedValue((prevValue) => {
            if (!Array.isArray(prevValue)) return value;
            if (prevValue.includes(value)) {
              return prevValue.filter((v) => v !== value);
            }
            return [...prevValue, value];
          });
        }
        if (setValueOnClickProp(event)) {
          store == null ? void 0 : store.setValue(value);
        }
      }
      if (hideOnClickProp(event)) {
        store == null ? void 0 : store.hide();
      }
    });
    const onKeyDownProp = props.onKeyDown;
    const onKeyDown = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (event.defaultPrevented) return;
      const baseElement = store == null ? void 0 : store.getState().baseElement;
      if (!baseElement) return;
      if ((0,utils_focus/* hasFocus */.AJ)(baseElement)) return;
      const printable = event.key.length === 1;
      if (printable || event.key === "Backspace" || event.key === "Delete") {
        queueMicrotask(() => baseElement.focus());
        if ((0,HWOIWM4O/* isTextField */.mB)(baseElement)) {
          store == null ? void 0 : store.setValue(baseElement.value);
        }
      }
    });
    if (multiSelectable && selected != null) {
      props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
        "aria-selected": selected
      }, props);
    }
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(ComboboxItemValueContext.Provider, { value, children: /* @__PURE__ */ (0,jsx_runtime.jsx)(ComboboxItemCheckedContext.Provider, { value: selected != null ? selected : false, children: element }) }),
      [value, selected]
    );
    const popupRole = (0,react.useContext)(ComboboxListRoleContext);
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: getItemRole(popupRole),
      children: value
    }, props), {
      onClick,
      onKeyDown
    });
    const moveOnKeyPressProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(moveOnKeyPress);
    props = (0,_3CCTMYB6/* useCompositeItem */.k)((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store
    }, props), {
      getItem,
      // Dispatch a custom event on the combobox input when moving to an item
      // with the keyboard so the Combobox component can enable inline
      // autocompletion.
      moveOnKeyPress: (event) => {
        if (!moveOnKeyPressProp(event)) return false;
        const moveEvent = new Event("combobox-item-move");
        const baseElement = store == null ? void 0 : store.getState().baseElement;
        baseElement == null ? void 0 : baseElement.dispatchEvent(moveEvent);
        return true;
      }
    }));
    props = useCompositeHover((0,_3YLGPPWQ/* __spreadValues */.IA)({ store, focusOnHover }, props));
    return props;
  }
);
var ComboboxItem = (0,HKOOKEDE/* memo */.ph)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function ComboboxItem2(props) {
    const htmlProps = useComboboxItem(props);
    return (0,HKOOKEDE/* createElement */.n)(combobox_item_TagName, htmlProps);
  })
);


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/7PRQYBBV.js
var _7PRQYBBV = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/7PRQYBBV.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/combobox/combobox-item-value.js
"use client";












// src/combobox/combobox-item-value.tsx




var combobox_item_value_TagName = "span";
function normalizeValue(value) {
  return (0,PBFD2E7P/* normalizeString */.J2)(value).toLowerCase();
}
function getOffsets(string, values) {
  const offsets = [];
  for (const value of values) {
    let pos = 0;
    const length = value.length;
    while (string.indexOf(value, pos) !== -1) {
      const index = string.indexOf(value, pos);
      if (index !== -1) {
        offsets.push([index, length]);
      }
      pos = index + 1;
    }
  }
  return offsets;
}
function filterOverlappingOffsets(offsets) {
  return offsets.filter(([offset, length], i, arr) => {
    return !arr.some(
      ([o, l], j) => j !== i && o <= offset && o + l >= offset + length
    );
  });
}
function sortOffsets(offsets) {
  return offsets.sort(([a], [b]) => a - b);
}
function splitValue(itemValue, userValue) {
  if (!itemValue) return itemValue;
  if (!userValue) return itemValue;
  const userValues = (0,_7PRQYBBV/* toArray */.$r)(userValue).filter(Boolean).map(normalizeValue);
  const parts = [];
  const span = (value, autocomplete = false) => /* @__PURE__ */ (0,jsx_runtime.jsx)(
    "span",
    {
      "data-autocomplete-value": autocomplete ? "" : void 0,
      "data-user-value": autocomplete ? void 0 : "",
      children: value
    },
    parts.length
  );
  const offsets = sortOffsets(
    filterOverlappingOffsets(
      // Convert userValues into a set to avoid duplicates
      getOffsets(normalizeValue(itemValue), new Set(userValues))
    )
  );
  if (!offsets.length) {
    parts.push(span(itemValue, true));
    return parts;
  }
  const [firstOffset] = offsets[0];
  const values = [
    itemValue.slice(0, firstOffset),
    ...offsets.flatMap(([offset, length], i) => {
      var _a;
      const value = itemValue.slice(offset, offset + length);
      const nextOffset = (_a = offsets[i + 1]) == null ? void 0 : _a[0];
      const nextValue = itemValue.slice(offset + length, nextOffset);
      return [value, nextValue];
    })
  ];
  values.forEach((value, i) => {
    if (!value) return;
    parts.push(span(value, i % 2 === 0));
  });
  return parts;
}
var useComboboxItemValue = (0,HKOOKEDE/* createHook */.ab)(function useComboboxItemValue2(_a) {
  var _b = _a, { store, value, userValue } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store", "value", "userValue"]);
  const context = useComboboxScopedContext();
  store = store || context;
  const itemContext = (0,react.useContext)(ComboboxItemValueContext);
  const itemValue = value != null ? value : itemContext;
  const inputValue = (0,_2GXGCHW6/* useStoreState */.O$)(store, (state) => userValue != null ? userValue : state == null ? void 0 : state.value);
  const children = (0,react.useMemo)(() => {
    if (!itemValue) return;
    if (!inputValue) return itemValue;
    return splitValue(itemValue, inputValue);
  }, [itemValue, inputValue]);
  props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
    children
  }, props);
  return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
});
var ComboboxItemValue = (0,HKOOKEDE/* forwardRef */.Rf)(function ComboboxItemValue2(props) {
  const htmlProps = useComboboxItemValue(props);
  return (0,HKOOKEDE/* createElement */.n)(combobox_item_value_TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/remove-accents@0.5.0/node_modules/remove-accents/index.js
var remove_accents = __webpack_require__("../../node_modules/.pnpm/remove-accents@0.5.0/node_modules/remove-accents/index.js");
var remove_accents_default = /*#__PURE__*/__webpack_require__.n(remove_accents);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/context.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */

const CompositeContext = (0,react.createContext)({});
const useCompositeContext = () => (0,react.useContext)(CompositeContext);
//# sourceMappingURL=context.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/7HVFURXT.js
"use client";

// src/group/group-label-context.tsx

var GroupLabelContext = (0,react.createContext)(void 0);



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/ZPO4YZYE.js
"use client";





// src/group/group.tsx



var ZPO4YZYE_TagName = "div";
var useGroup = (0,HKOOKEDE/* createHook */.ab)(
  function useGroup2(props) {
    const [labelId, setLabelId] = (0,react.useState)();
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(GroupLabelContext.Provider, { value: setLabelId, children: element }),
      []
    );
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: "group",
      "aria-labelledby": labelId
    }, props);
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
  }
);
var Group = (0,HKOOKEDE/* forwardRef */.Rf)(function Group2(props) {
  const htmlProps = useGroup(props);
  return (0,HKOOKEDE/* createElement */.n)(ZPO4YZYE_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/IEKMDIUY.js
"use client";




// src/composite/composite-group.tsx
var IEKMDIUY_TagName = "div";
var useCompositeGroup = (0,HKOOKEDE/* createHook */.ab)(
  function useCompositeGroup2(_a) {
    var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
    props = useGroup(props);
    return props;
  }
);
var IEKMDIUY_CompositeGroup = (0,HKOOKEDE/* forwardRef */.Rf)(function CompositeGroup2(props) {
  const htmlProps = useCompositeGroup(props);
  return (0,HKOOKEDE/* createElement */.n)(IEKMDIUY_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/group.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const CompositeGroup = (0,react.forwardRef)(function CompositeGroup(props, ref) {
  const context = useCompositeContext();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(IEKMDIUY_CompositeGroup, {
    store: context.store,
    ...props,
    ref: ref
  });
});
//# sourceMappingURL=group.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/IGFP5YPG.js
"use client";





// src/group/group-label.tsx


var IGFP5YPG_TagName = "div";
var useGroupLabel = (0,HKOOKEDE/* createHook */.ab)(
  function useGroupLabel2(props) {
    const setLabelId = (0,react.useContext)(GroupLabelContext);
    const id = (0,Z32BISHQ/* useId */.Bi)(props.id);
    (0,Z32BISHQ/* useSafeLayoutEffect */.UQ)(() => {
      setLabelId == null ? void 0 : setLabelId(id);
      return () => setLabelId == null ? void 0 : setLabelId(void 0);
    }, [setLabelId, id]);
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      id,
      "aria-hidden": true
    }, props);
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
  }
);
var GroupLabel = (0,HKOOKEDE/* forwardRef */.Rf)(function GroupLabel2(props) {
  const htmlProps = useGroupLabel(props);
  return (0,HKOOKEDE/* createElement */.n)(IGFP5YPG_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/Y2MAXF6C.js
"use client";




// src/composite/composite-group-label.tsx
var Y2MAXF6C_TagName = "div";
var useCompositeGroupLabel = (0,HKOOKEDE/* createHook */.ab)(function useCompositeGroupLabel2(_a) {
  var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
  props = useGroupLabel(props);
  return props;
});
var Y2MAXF6C_CompositeGroupLabel = (0,HKOOKEDE/* forwardRef */.Rf)(function CompositeGroupLabel2(props) {
  const htmlProps = useCompositeGroupLabel(props);
  return (0,HKOOKEDE/* createElement */.n)(Y2MAXF6C_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/group-label.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const CompositeGroupLabel = (0,react.forwardRef)(function CompositeGroupLabel(props, ref) {
  const context = useCompositeContext();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Y2MAXF6C_CompositeGroupLabel, {
    store: context.store,
    ...props,
    ref: ref
  });
});
//# sourceMappingURL=group-label.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/hover.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const CompositeHover = (0,react.forwardRef)(function CompositeHover(props, ref) {
  const context = useCompositeContext();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(OBZMLI6J_CompositeHover, {
    store: context.store,
    ...props,
    ref: ref
  });
});
//# sourceMappingURL=hover.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/item.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const CompositeItem = (0,react.forwardRef)(function CompositeItem(props, ref) {
  const context = useCompositeContext();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(_3CCTMYB6/* CompositeItem */.l, {
    store: context.store,
    ...props,
    ref: ref
  });
});
//# sourceMappingURL=item.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/6BE7QOX5.js
"use client";





// src/composite/composite-row.tsx



var _6BE7QOX5_TagName = "div";
var useCompositeRow = (0,HKOOKEDE/* createHook */.ab)(
  function useCompositeRow2(_a) {
    var _b = _a, {
      store,
      "aria-setsize": ariaSetSize,
      "aria-posinset": ariaPosInSet
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "aria-setsize",
      "aria-posinset"
    ]);
    const context = (0,WENSINUV/* useCompositeContext */.k)();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const id = (0,Z32BISHQ/* useId */.Bi)(props.id);
    const baseElement = store.useState(
      (state) => state.baseElement || void 0
    );
    const providerValue = (0,react.useMemo)(
      () => ({ id, baseElement, ariaSetSize, ariaPosInSet }),
      [id, baseElement, ariaSetSize, ariaPosInSet]
    );
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(WENSINUV/* CompositeRowContext */.$o.Provider, { value: providerValue, children: element }),
      [providerValue]
    );
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({ id }, props);
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
  }
);
var _6BE7QOX5_CompositeRow = (0,HKOOKEDE/* forwardRef */.Rf)(function CompositeRow2(props) {
  const htmlProps = useCompositeRow(props);
  return (0,HKOOKEDE/* createElement */.n)(_6BE7QOX5_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/row.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const CompositeRow = (0,react.forwardRef)(function CompositeRow(props, ref) {
  const context = useCompositeContext();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(_6BE7QOX5_CompositeRow, {
    store: context.store,
    ...props,
    ref: ref
  });
});
//# sourceMappingURL=row.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/5VQZOHHZ.js
var _5VQZOHHZ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/5VQZOHHZ.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/DS36B3MQ.js
"use client";






// src/composite/composite-typeahead.tsx




var DS36B3MQ_TagName = "div";
var chars = "";
function clearChars() {
  chars = "";
}
function isValidTypeaheadEvent(event) {
  const target = event.target;
  if (target && (0,HWOIWM4O/* isTextField */.mB)(target)) return false;
  if (event.key === " " && chars.length) return true;
  return event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey && /^[\p{Letter}\p{Number}]$/u.test(event.key);
}
function isSelfTargetOrItem(event, items) {
  if ((0,events/* isSelfTarget */.uh)(event)) return true;
  const target = event.target;
  if (!target) return false;
  const isItem = items.some((item) => item.element === target);
  return isItem;
}
function getEnabledItems(items) {
  return items.filter((item) => !item.disabled);
}
function itemTextStartsWith(item, text) {
  var _a;
  const itemText = ((_a = item.element) == null ? void 0 : _a.textContent) || item.children || // The composite item object itself doesn't include a value property, but
  // other components like Select do. Since CompositeTypeahead is a generic
  // component that can be used with those as well, we also consider the value
  // property as a fallback for the typeahead text content.
  "value" in item && item.value;
  if (!itemText) return false;
  return (0,PBFD2E7P/* normalizeString */.J2)(itemText).trim().toLowerCase().startsWith(text.toLowerCase());
}
function getSameInitialItems(items, char, activeId) {
  if (!activeId) return items;
  const activeItem = items.find((item) => item.id === activeId);
  if (!activeItem) return items;
  if (!itemTextStartsWith(activeItem, char)) return items;
  if (chars !== char && itemTextStartsWith(activeItem, chars)) return items;
  chars = char;
  return (0,_5VQZOHHZ/* flipItems */._d)(
    items.filter((item) => itemTextStartsWith(item, chars)),
    activeId
  ).filter((item) => item.id !== activeId);
}
var useCompositeTypeahead = (0,HKOOKEDE/* createHook */.ab)(function useCompositeTypeahead2(_a) {
  var _b = _a, { store, typeahead = true } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store", "typeahead"]);
  const context = (0,WENSINUV/* useCompositeContext */.k)();
  store = store || context;
  (0,PBFD2E7P/* invariant */.V1)(
    store,
     false && 0
  );
  const onKeyDownCaptureProp = props.onKeyDownCapture;
  const cleanupTimeoutRef = (0,react.useRef)(0);
  const onKeyDownCapture = (0,Z32BISHQ/* useEvent */._q)((event) => {
    onKeyDownCaptureProp == null ? void 0 : onKeyDownCaptureProp(event);
    if (event.defaultPrevented) return;
    if (!typeahead) return;
    if (!store) return;
    const { renderedItems, items, activeId } = store.getState();
    if (!isValidTypeaheadEvent(event)) return clearChars();
    let enabledItems = getEnabledItems(
      renderedItems.length ? renderedItems : items
    );
    if (!isSelfTargetOrItem(event, enabledItems)) return clearChars();
    event.preventDefault();
    window.clearTimeout(cleanupTimeoutRef.current);
    cleanupTimeoutRef.current = window.setTimeout(() => {
      chars = "";
    }, 500);
    const char = event.key.toLowerCase();
    chars += char;
    enabledItems = getSameInitialItems(enabledItems, char, activeId);
    const item = enabledItems.find((item2) => itemTextStartsWith(item2, chars));
    if (item) {
      store.move(item.id);
    } else {
      clearChars();
    }
  });
  props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    onKeyDownCapture
  });
  return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
});
var DS36B3MQ_CompositeTypeahead = (0,HKOOKEDE/* forwardRef */.Rf)(function CompositeTypeahead2(props) {
  const htmlProps = useCompositeTypeahead(props);
  return (0,HKOOKEDE/* createElement */.n)(DS36B3MQ_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/typeahead.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const CompositeTypeahead = (0,react.forwardRef)(function CompositeTypeahead(props, ref) {
  const context = useCompositeContext();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(DS36B3MQ_CompositeTypeahead, {
    store: context.store,
    ...props,
    ref: ref
  });
});
//# sourceMappingURL=typeahead.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/composite/index.js
/**
 * Composite is a component that may contain navigable items represented by
 * Composite.Item. It's inspired by the WAI-ARIA Composite Role and implements
 * all the keyboard navigation mechanisms to ensure that there's only one
 * tab stop for the whole Composite element. This means that it can behave as
 * a roving tabindex or aria-activedescendant container.
 *
 * @see https://ariakit.org/components/composite
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









/**
 * Renders a widget based on the WAI-ARIA [`composite`](https://w3c.github.io/aria/#composite)
 * role, which provides a single tab stop on the page and arrow key navigation
 * through the focusable descendants.
 *
 * @example
 * ```jsx
 * import { Composite } from '@wordpress/components';
 *
 * <Composite>
 *   <Composite.Item>Item 1</Composite.Item>
 *   <Composite.Item>Item 2</Composite.Item>
 * </Composite>
 * ```
 */
const Composite = Object.assign((0,react.forwardRef)(function Composite({
  // Composite store props
  activeId,
  defaultActiveId,
  setActiveId,
  focusLoop = false,
  focusWrap = false,
  focusShift = false,
  virtualFocus = false,
  orientation = 'both',
  rtl = (0,build_module/* isRTL */.V8)(),
  // Composite component props
  children,
  disabled = false,
  // Rest props
  ...props
}, ref) {
  const store = UVQLZ7T5/* useCompositeStore */.q({
    activeId,
    defaultActiveId,
    setActiveId,
    focusLoop,
    focusWrap,
    focusShift,
    virtualFocus,
    orientation,
    rtl
  });
  const contextValue = (0,react.useMemo)(() => ({
    store
  }), [store]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(TW35PKTK/* Composite */.e, {
    disabled: disabled,
    store: store,
    ...props,
    ref: ref,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(CompositeContext.Provider, {
      value: contextValue,
      children: children
    })
  });
}), {
  /**
   * Renders a group element for composite items.
   *
   * @example
   * ```jsx
   * import { Composite } from '@wordpress/components';
   *
   * <Composite>
   *   <Composite.Group>
   *     <Composite.GroupLabel>Label</Composite.GroupLabel>
   *     <Composite.Item>Item 1</Composite.Item>
   *     <Composite.Item>Item 2</Composite.Item>
   *   </CompositeGroup>
   * </Composite>
   * ```
   */
  Group: Object.assign(CompositeGroup, {
    displayName: 'Composite.Group'
  }),
  /**
   * Renders a label in a composite group. This component must be wrapped with
   * `Composite.Group` so the `aria-labelledby` prop is properly set on the
   * composite group element.
   *
   * @example
   * ```jsx
   * import { Composite } from '@wordpress/components';
   *
   * <Composite>
   *   <Composite.Group>
   *     <Composite.GroupLabel>Label</Composite.GroupLabel>
   *     <Composite.Item>Item 1</Composite.Item>
   *     <Composite.Item>Item 2</Composite.Item>
   *   </CompositeGroup>
   * </Composite>
   * ```
   */
  GroupLabel: Object.assign(CompositeGroupLabel, {
    displayName: 'Composite.GroupLabel'
  }),
  /**
   * Renders a composite item.
   *
   * @example
   * ```jsx
   * import { Composite } from '@wordpress/components';
   *
   * <Composite>
   *   <Composite.Item>Item 1</Composite.Item>
   *   <Composite.Item>Item 2</Composite.Item>
   *   <Composite.Item>Item 3</Composite.Item>
   * </Composite>
   * ```
   */
  Item: Object.assign(CompositeItem, {
    displayName: 'Composite.Item'
  }),
  /**
   * Renders a composite row. Wrapping `Composite.Item` elements within
   * `Composite.Row` will create a two-dimensional composite widget, such as a
   * grid.
   *
   * @example
   * ```jsx
   * import { Composite } from '@wordpress/components';
   *
   * <Composite>
   *   <Composite.Row>
   *     <Composite.Item>Item 1.1</Composite.Item>
   *     <Composite.Item>Item 1.2</Composite.Item>
   *     <Composite.Item>Item 1.3</Composite.Item>
   *   </Composite.Row>
   *   <Composite.Row>
   *     <Composite.Item>Item 2.1</Composite.Item>
   *     <Composite.Item>Item 2.2</Composite.Item>
   *     <Composite.Item>Item 2.3</Composite.Item>
   *   </Composite.Row>
   * </Composite>
   * ```
   */
  Row: Object.assign(CompositeRow, {
    displayName: 'Composite.Row'
  }),
  /**
   * Renders an element in a composite widget that receives focus on mouse move
   * and loses focus to the composite base element on mouse leave. This should
   * be combined with the `Composite.Item` component.
   *
   * @example
   * ```jsx
   * import { Composite } from '@wordpress/components';
   *
   * <Composite>
   *   <Composite.Hover render={ <Composite.Item /> }>
   *     Item 1
   *   </Composite.Hover>
   *   <Composite.Hover render={ <Composite.Item /> }>
   *     Item 2
   *   </Composite.Hover>
   * </Composite>
   * ```
   */
  Hover: Object.assign(CompositeHover, {
    displayName: 'Composite.Hover'
  }),
  /**
   * Renders a component that adds typeahead functionality to composite
   * components. Hitting printable character keys will move focus to the next
   * composite item that begins with the input characters.
   *
   * @example
   * ```jsx
   * import { Composite } from '@wordpress/components';
   *
   * <Composite render={ <CompositeTypeahead /> }>
   *   <Composite.Item>Item 1</Composite.Item>
   *   <Composite.Item>Item 2</Composite.Item>
   * </Composite>
   * ```
   */
  Typeahead: Object.assign(CompositeTypeahead, {
    displayName: 'Composite.Typeahead'
  }),
  /**
   * The React context used by the composite components. It can be used by
   * to access the composite store, and to forward the context when composite
   * sub-components are rendered across portals (ie. `SlotFill` components)
   * that would not otherwise forward the context to the `Fill` children.
   *
   * @example
   * ```jsx
   * import { Composite } from '@wordpress/components';
   * import { useContext } from '@wordpress/element';
   *
   * const compositeContext = useContext( Composite.Context );
   * ```
   */
  Context: Object.assign(CompositeContext, {
    displayName: 'Composite.Context'
  })
});
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/visually-hidden/component.js + 1 modules
var visually_hidden_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/check.js
/**
 * WordPress dependencies
 */


const check = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"
  })
});
/* harmony default export */ const library_check = (check);
//# sourceMappingURL=check.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/search.js
/**
 * WordPress dependencies
 */


const search = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"
  })
});
/* harmony default export */ const library_search = (search);
//# sourceMappingURL=search.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-filters/search-widget.js
/**
 * External dependencies
 */
// eslint-disable-next-line no-restricted-imports



/**
 * WordPress dependencies
 */







/**
 * Internal dependencies
 */


const radioCheck = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Circle */.jl, {
    cx: 12,
    cy: 12,
    r: 3
  })
});
function normalizeSearchInput(input = '') {
  return remove_accents_default()(input.trim().toLowerCase());
}
const EMPTY_ARRAY = [];
const getCurrentValue = (filterDefinition, currentFilter) => {
  if (filterDefinition.singleSelection) {
    return currentFilter?.value;
  }
  if (Array.isArray(currentFilter?.value)) {
    return currentFilter.value;
  }
  if (!Array.isArray(currentFilter?.value) && !!currentFilter?.value) {
    return [currentFilter.value];
  }
  return EMPTY_ARRAY;
};
const getNewValue = (filterDefinition, currentFilter, value) => {
  if (filterDefinition.singleSelection) {
    return value;
  }
  if (Array.isArray(currentFilter?.value)) {
    return currentFilter.value.includes(value) ? currentFilter.value.filter(v => v !== value) : [...currentFilter.value, value];
  }
  return [value];
};
function generateFilterElementCompositeItemId(prefix, filterElementValue) {
  return `${prefix}-${filterElementValue}`;
}
function ListBox({
  view,
  filter,
  onChangeView
}) {
  const baseId = (0,use_instance_id/* default */.A)(ListBox, 'dataviews-filter-list-box');
  const [activeCompositeId, setActiveCompositeId] = (0,react.useState)(
  // When there are one or less operators, the first item is set as active
  // (by setting the initial `activeId` to `undefined`).
  // With 2 or more operators, the focus is moved on the operators control
  // (by setting the initial `activeId` to `null`), meaning that there won't
  // be an active item initially. Focus is then managed via the
  // `onFocusVisible` callback.
  filter.operators?.length === 1 ? undefined : null);
  const currentFilter = view.filters?.find(f => f.field === filter.field);
  const currentValue = getCurrentValue(filter, currentFilter);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Composite, {
    virtualFocus: true,
    focusLoop: true,
    activeId: activeCompositeId,
    setActiveId: setActiveCompositeId,
    role: "listbox",
    className: "dataviews-filters__search-widget-listbox",
    "aria-label": (0,build_module/* sprintf */.nv)( /* translators: List of items for a filter. 1: Filter name. e.g.: "List of: Author". */
    (0,build_module.__)('List of: %1$s'), filter.name),
    onFocusVisible: () => {
      // `onFocusVisible` needs the `Composite` component to be focusable,
      // which is implicitly achieved via the `virtualFocus` prop.
      if (!activeCompositeId && filter.elements.length) {
        setActiveCompositeId(generateFilterElementCompositeItemId(baseId, filter.elements[0].value));
      }
    },
    render: /*#__PURE__*/(0,jsx_runtime.jsx)(Composite.Typeahead, {}),
    children: filter.elements.map(element => /*#__PURE__*/(0,jsx_runtime.jsxs)(Composite.Hover, {
      render: /*#__PURE__*/(0,jsx_runtime.jsx)(Composite.Item, {
        id: generateFilterElementCompositeItemId(baseId, element.value),
        render: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          "aria-label": element.label,
          role: "option",
          className: "dataviews-filters__search-widget-listitem"
        }),
        onClick: () => {
          var _view$filters, _view$filters2;
          const newFilters = currentFilter ? [...((_view$filters = view.filters) !== null && _view$filters !== void 0 ? _view$filters : []).map(_filter => {
            if (_filter.field === filter.field) {
              return {
                ..._filter,
                operator: currentFilter.operator || filter.operators[0],
                value: getNewValue(filter, currentFilter, element.value)
              };
            }
            return _filter;
          })] : [...((_view$filters2 = view.filters) !== null && _view$filters2 !== void 0 ? _view$filters2 : []), {
            field: filter.field,
            operator: filter.operators[0],
            value: getNewValue(filter, currentFilter, element.value)
          }];
          onChangeView({
            ...view,
            page: 1,
            filters: newFilters
          });
        }
      }),
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
        className: "dataviews-filters__search-widget-listitem-check",
        children: [filter.singleSelection && currentValue === element.value && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
          icon: radioCheck
        }), !filter.singleSelection && currentValue.includes(element.value) && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
          icon: library_check
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        children: element.label
      })]
    }, element.value))
  });
}
function search_widget_ComboboxList({
  view,
  filter,
  onChangeView
}) {
  const [searchValue, setSearchValue] = (0,react.useState)('');
  const deferredSearchValue = (0,react.useDeferredValue)(searchValue);
  const currentFilter = view.filters?.find(_filter => _filter.field === filter.field);
  const currentValue = getCurrentValue(filter, currentFilter);
  const matches = (0,react.useMemo)(() => {
    const normalizedSearch = normalizeSearchInput(deferredSearchValue);
    return filter.elements.filter(item => normalizeSearchInput(item.label).includes(normalizedSearch));
  }, [filter.elements, deferredSearchValue]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(ComboboxProvider, {
    selectedValue: currentValue,
    setSelectedValue: value => {
      var _view$filters3, _view$filters4;
      const newFilters = currentFilter ? [...((_view$filters3 = view.filters) !== null && _view$filters3 !== void 0 ? _view$filters3 : []).map(_filter => {
        if (_filter.field === filter.field) {
          return {
            ..._filter,
            operator: currentFilter.operator || filter.operators[0],
            value
          };
        }
        return _filter;
      })] : [...((_view$filters4 = view.filters) !== null && _view$filters4 !== void 0 ? _view$filters4 : []), {
        field: filter.field,
        operator: filter.operators[0],
        value
      }];
      onChangeView({
        ...view,
        page: 1,
        filters: newFilters
      });
    },
    setValue: setSearchValue,
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "dataviews-filters__search-widget-filter-combobox__wrapper",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ComboboxLabel, {
        render: /*#__PURE__*/(0,jsx_runtime.jsx)(visually_hidden_component/* default */.A, {
          children: (0,build_module.__)('Search items')
        }),
        children: (0,build_module.__)('Search items')
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(Combobox, {
        autoSelect: "always",
        placeholder: (0,build_module.__)('Search'),
        className: "dataviews-filters__search-widget-filter-combobox__input"
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "dataviews-filters__search-widget-filter-combobox__icon",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
          icon: library_search
        })
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(ComboboxList, {
      className: "dataviews-filters__search-widget-filter-combobox-list",
      alwaysVisible: true,
      children: [matches.map(element => {
        return /*#__PURE__*/(0,jsx_runtime.jsxs)(ComboboxItem, {
          resetValueOnSelect: false,
          value: element.value,
          className: "dataviews-filters__search-widget-listitem",
          hideOnClick: false,
          setValueOnClick: false,
          focusOnHover: true,
          children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
            className: "dataviews-filters__search-widget-listitem-check",
            children: [filter.singleSelection && currentValue === element.value && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
              icon: radioCheck
            }), !filter.singleSelection && currentValue.includes(element.value) && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
              icon: library_check
            })]
          }), /*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
            children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ComboboxItemValue, {
              className: "dataviews-filters__search-widget-filter-combobox-item-value",
              value: element.label
            }), !!element.description && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
              className: "dataviews-filters__search-widget-listitem-description",
              children: element.description
            })]
          })]
        }, element.value);
      }), !matches.length && /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
        children: (0,build_module.__)('No results found')
      })]
    })]
  });
}
function SearchWidget(props) {
  const Widget = props.filter.elements.length > 10 ? search_widget_ComboboxList : ListBox;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Widget, {
    ...props
  });
}
//# sourceMappingURL=search-widget.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-filters/filter-summary.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




const ENTER = 'Enter';
const SPACE = ' ';

/**
 * Internal dependencies
 */




const FilterText = ({
  activeElements,
  filterInView,
  filter
}) => {
  if (activeElements === undefined || activeElements.length === 0) {
    return filter.name;
  }
  const filterTextWrappers = {
    Name: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "dataviews-filters__summary-filter-text-name"
    }),
    Value: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "dataviews-filters__summary-filter-text-value"
    })
  };
  if (filterInView?.operator === OPERATOR_IS_ANY) {
    return create_interpolate_element((0,build_module/* sprintf */.nv)( /* translators: 1: Filter name. 3: Filter value. e.g.: "Author is any: Admin, Editor". */
    (0,build_module.__)('<Name>%1$s is any: </Name><Value>%2$s</Value>'), filter.name, activeElements.map(element => element.label).join(', ')), filterTextWrappers);
  }
  if (filterInView?.operator === OPERATOR_IS_NONE) {
    return create_interpolate_element((0,build_module/* sprintf */.nv)( /* translators: 1: Filter name. 3: Filter value. e.g.: "Author is none: Admin, Editor". */
    (0,build_module.__)('<Name>%1$s is none: </Name><Value>%2$s</Value>'), filter.name, activeElements.map(element => element.label).join(', ')), filterTextWrappers);
  }
  if (filterInView?.operator === OPERATOR_IS_ALL) {
    return create_interpolate_element((0,build_module/* sprintf */.nv)( /* translators: 1: Filter name. 3: Filter value. e.g.: "Author is all: Admin, Editor". */
    (0,build_module.__)('<Name>%1$s is all: </Name><Value>%2$s</Value>'), filter.name, activeElements.map(element => element.label).join(', ')), filterTextWrappers);
  }
  if (filterInView?.operator === OPERATOR_IS_NOT_ALL) {
    return create_interpolate_element((0,build_module/* sprintf */.nv)( /* translators: 1: Filter name. 3: Filter value. e.g.: "Author is not all: Admin, Editor". */
    (0,build_module.__)('<Name>%1$s is not all: </Name><Value>%2$s</Value>'), filter.name, activeElements.map(element => element.label).join(', ')), filterTextWrappers);
  }
  if (filterInView?.operator === OPERATOR_IS) {
    return create_interpolate_element((0,build_module/* sprintf */.nv)( /* translators: 1: Filter name. 3: Filter value. e.g.: "Author is: Admin". */
    (0,build_module.__)('<Name>%1$s is: </Name><Value>%2$s</Value>'), filter.name, activeElements[0].label), filterTextWrappers);
  }
  if (filterInView?.operator === OPERATOR_IS_NOT) {
    return create_interpolate_element((0,build_module/* sprintf */.nv)( /* translators: 1: Filter name. 3: Filter value. e.g.: "Author is not: Admin". */
    (0,build_module.__)('<Name>%1$s is not: </Name><Value>%2$s</Value>'), filter.name, activeElements[0].label), filterTextWrappers);
  }
  return (0,build_module/* sprintf */.nv)( /* translators: 1: Filter name e.g.: "Unknown status for Author". */
  (0,build_module.__)('Unknown status for %1$s'), filter.name);
};
function OperatorSelector({
  filter,
  view,
  onChangeView
}) {
  const operatorOptions = filter.operators?.map(operator => ({
    value: operator,
    label: OPERATORS[operator]?.label
  }));
  const currentFilter = view.filters?.find(_filter => _filter.field === filter.field);
  const value = currentFilter?.operator || filter.operators[0];
  return operatorOptions.length > 1 && /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
    spacing: 2,
    justify: "flex-start",
    className: "dataviews-filters__summary-operators-container",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(flex_item_component/* default */.A, {
      className: "dataviews-filters__summary-operators-filter-name",
      children: filter.name
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
      label: (0,build_module.__)('Conditions'),
      value: value,
      options: operatorOptions,
      onChange: newValue => {
        var _view$filters, _view$filters2;
        const operator = newValue;
        const newFilters = currentFilter ? [...((_view$filters = view.filters) !== null && _view$filters !== void 0 ? _view$filters : []).map(_filter => {
          if (_filter.field === filter.field) {
            return {
              ..._filter,
              operator
            };
          }
          return _filter;
        })] : [...((_view$filters2 = view.filters) !== null && _view$filters2 !== void 0 ? _view$filters2 : []), {
          field: filter.field,
          operator,
          value: undefined
        }];
        onChangeView({
          ...view,
          page: 1,
          filters: newFilters
        });
      },
      size: "small",
      __nextHasNoMarginBottom: true,
      hideLabelFromVision: true
    })]
  });
}
function FilterSummary({
  addFilterRef,
  openedFilter,
  ...commonProps
}) {
  const toggleRef = (0,react.useRef)(null);
  const {
    filter,
    view,
    onChangeView
  } = commonProps;
  const filterInView = view.filters?.find(f => f.field === filter.field);
  const activeElements = filter.elements.filter(element => {
    if (filter.singleSelection) {
      return element.value === filterInView?.value;
    }
    return filterInView?.value?.includes(element.value);
  });
  const isPrimary = filter.isPrimary;
  const hasValues = filterInView?.value !== undefined;
  const canResetOrRemove = !isPrimary || hasValues;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown/* default */.A, {
    defaultOpen: openedFilter === filter.field,
    contentClassName: "dataviews-filters__summary-popover",
    popoverProps: {
      placement: 'bottom-start',
      role: 'dialog'
    },
    onClose: () => {
      toggleRef.current?.focus();
    },
    renderToggle: ({
      isOpen,
      onToggle
    }) => /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "dataviews-filters__summary-chip-container",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(tooltip/* default */.Ay, {
        text: (0,build_module/* sprintf */.nv)( /* translators: 1: Filter name. */
        (0,build_module.__)('Filter by: %1$s'), filter.name.toLowerCase()),
        placement: "top",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: (0,clsx/* default */.A)('dataviews-filters__summary-chip', {
            'has-reset': canResetOrRemove,
            'has-values': hasValues
          }),
          role: "button",
          tabIndex: 0,
          onClick: onToggle,
          onKeyDown: event => {
            if ([ENTER, SPACE].includes(event.key)) {
              onToggle();
              event.preventDefault();
            }
          },
          "aria-pressed": isOpen,
          "aria-expanded": isOpen,
          ref: toggleRef,
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(FilterText, {
            activeElements: activeElements,
            filterInView: filterInView,
            filter: filter
          })
        })
      }), canResetOrRemove && /*#__PURE__*/(0,jsx_runtime.jsx)(tooltip/* default */.Ay, {
        text: isPrimary ? (0,build_module.__)('Reset') : (0,build_module.__)('Remove'),
        placement: "top",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
          className: (0,clsx/* default */.A)('dataviews-filters__summary-chip-remove', {
            'has-values': hasValues
          }),
          onClick: () => {
            onChangeView({
              ...view,
              page: 1,
              filters: view.filters?.filter(_filter => _filter.field !== filter.field)
            });
            // If the filter is not primary and can be removed, it will be added
            // back to the available filters from `Add filter` component.
            if (!isPrimary) {
              addFilterRef.current?.focus();
            } else {
              // If is primary, focus the toggle button.
              toggleRef.current?.focus();
            }
          },
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
            icon: close_small/* default */.A
          })
        })
      })]
    }),
    renderContent: () => {
      return /*#__PURE__*/(0,jsx_runtime.jsxs)(v_stack_component/* default */.A, {
        spacing: 0,
        justify: "flex-start",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(OperatorSelector, {
          ...commonProps
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(SearchWidget, {
          ...commonProps
        })]
      });
    }
  });
}
//# sourceMappingURL=filter-summary.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/popover/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/popover/utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/slot-fill/index.js + 12 modules
var slot_fill = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/slot-fill/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/CTQR3VDU.js
var CTQR3VDU = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/CTQR3VDU.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/LG4RFBHV.js
"use client";





// src/menu/menu-context.tsx

var menu = (0,HKOOKEDE/* createStoreContext */.B0)(
  [WENSINUV/* CompositeContextProvider */.ws, CTQR3VDU/* HovercardContextProvider */.pR],
  [WENSINUV/* CompositeScopedContextProvider */.aN, CTQR3VDU/* HovercardScopedContextProvider */.n0]
);
var useMenuContext = menu.useContext;
var useMenuScopedContext = menu.useScopedContext;
var useMenuProviderContext = menu.useProviderContext;
var MenuContextProvider = menu.ContextProvider;
var MenuScopedContextProvider = menu.ScopedContextProvider;
var useMenuBarContext = (/* unused pure expression or super */ null && (useMenubarContext));
var useMenuBarScopedContext = (/* unused pure expression or super */ null && (useMenubarScopedContext));
var useMenuBarProviderContext = (/* unused pure expression or super */ null && (useMenubarProviderContext));
var MenuBarContextProvider = (/* unused pure expression or super */ null && (MenubarContextProvider));
var MenuBarScopedContextProvider = (/* unused pure expression or super */ null && (MenubarScopedContextProvider));
var MenuItemCheckedContext = (0,react.createContext)(
  void 0
);



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/WSQNIDGC.js
"use client";



// src/menubar/menubar-context.tsx

var menubar = (0,HKOOKEDE/* createStoreContext */.B0)(
  [WENSINUV/* CompositeContextProvider */.ws],
  [WENSINUV/* CompositeScopedContextProvider */.aN]
);
var WSQNIDGC_useMenubarContext = menubar.useContext;
var WSQNIDGC_useMenubarScopedContext = menubar.useScopedContext;
var WSQNIDGC_useMenubarProviderContext = menubar.useProviderContext;
var WSQNIDGC_MenubarContextProvider = menubar.ContextProvider;
var WSQNIDGC_MenubarScopedContextProvider = menubar.ScopedContextProvider;
var WSQNIDGC_MenuItemCheckedContext = (0,react.createContext)(
  void 0
);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/XMDAT5SM.js
var XMDAT5SM = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/XMDAT5SM.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/EACLTACN.js
var EACLTACN = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/EACLTACN.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/menu/menu-store.js
"use client";












// src/menu/menu-store.ts
function createMenuStore(_a = {}) {
  var _b = _a, {
    combobox,
    parent,
    menubar
  } = _b, props = (0,_chunks_3YLGPPWQ/* __objRest */.YG)(_b, [
    "combobox",
    "parent",
    "menubar"
  ]);
  const parentIsMenubar = !!menubar && !parent;
  const store = (0,EQQLU3CG/* mergeStore */.od)(
    props.store,
    (0,EQQLU3CG/* pick */.Up)(parent, ["values"]),
    (0,EQQLU3CG/* omit */.cJ)(combobox, [
      "arrowElement",
      "anchorElement",
      "contentElement",
      "popoverElement",
      "disclosureElement"
    ])
  );
  (0,EQQLU3CG/* throwOnConflictingProps */.UE)(props, store);
  const syncState = store.getState();
  const composite = (0,D7EIQZAU/* createCompositeStore */.z)((0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    store,
    orientation: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.orientation,
      syncState.orientation,
      "vertical"
    )
  }));
  const hovercard = (0,EACLTACN/* createHovercardStore */.y)((0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    store,
    placement: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.placement,
      syncState.placement,
      "bottom-start"
    ),
    timeout: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.timeout,
      syncState.timeout,
      parentIsMenubar ? 0 : 150
    ),
    hideTimeout: (0,PBFD2E7P/* defaultValue */.Jh)(props.hideTimeout, syncState.hideTimeout, 0)
  }));
  const initialState = (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, composite.getState()), hovercard.getState()), {
    initialFocus: (0,PBFD2E7P/* defaultValue */.Jh)(syncState.initialFocus, "container"),
    values: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.values,
      syncState.values,
      props.defaultValues,
      {}
    )
  });
  const menu = (0,EQQLU3CG/* createStore */.y$)(initialState, composite, hovercard, store);
  (0,EQQLU3CG/* setup */.mj)(
    menu,
    () => (0,EQQLU3CG/* sync */.OH)(menu, ["mounted"], (state) => {
      if (state.mounted) return;
      menu.setState("activeId", null);
    })
  );
  (0,EQQLU3CG/* setup */.mj)(
    menu,
    () => (0,EQQLU3CG/* sync */.OH)(parent, ["orientation"], (state) => {
      menu.setState(
        "placement",
        state.orientation === "vertical" ? "right-start" : "bottom-start"
      );
    })
  );
  return (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, composite), hovercard), menu), {
    combobox,
    parent,
    menubar,
    hideAll: () => {
      hovercard.hide();
      parent == null ? void 0 : parent.hideAll();
    },
    setInitialFocus: (value) => menu.setState("initialFocus", value),
    setValues: (values) => menu.setState("values", values),
    setValue: (name, value) => {
      if (name === "__proto__") return;
      if (name === "constructor") return;
      if (Array.isArray(name)) return;
      menu.setState("values", (values) => {
        const prevValue = values[name];
        const nextValue = (0,PBFD2E7P/* applyState */.Qh)(value, prevValue);
        if (nextValue === prevValue) return values;
        return (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, values), {
          [name]: nextValue !== void 0 && nextValue
        });
      });
    }
  });
}


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/MS4VD4RJ.js
"use client";









// src/menu/menu-store.ts

function useMenuStoreProps(store, update, props) {
  (0,Z32BISHQ/* useUpdateEffect */.w5)(update, [props.combobox, props.parent, props.menubar]);
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "values", "setValues");
  return Object.assign(
    (0,XMDAT5SM/* useHovercardStoreProps */.B)(
      (0,UVQLZ7T5/* useCompositeStoreProps */.Y)(store, update, props),
      update,
      props
    ),
    {
      combobox: props.combobox,
      parent: props.parent,
      menubar: props.menubar
    }
  );
}
function useMenuStore(props = {}) {
  const parent = useMenuContext();
  const menubar = WSQNIDGC_useMenubarContext();
  const combobox = useComboboxProviderContext();
  props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    parent: props.parent !== void 0 ? props.parent : parent,
    menubar: props.menubar !== void 0 ? props.menubar : menubar,
    combobox: props.combobox !== void 0 ? props.combobox : combobox
  });
  const [store, update] = (0,_2GXGCHW6/* useStore */.Pj)(createMenuStore, props);
  return useMenuStoreProps(store, update, props);
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/NAXN2XAB.js
var NAXN2XAB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/NAXN2XAB.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/3R3N43YO.js
"use client";





// src/button/button.tsx


var _3R3N43YO_TagName = "button";
var useButton = (0,HKOOKEDE/* createHook */.ab)(
  function useButton2(props) {
    const ref = (0,react.useRef)(null);
    const tagName = (0,Z32BISHQ/* useTagName */.vO)(ref, _3R3N43YO_TagName);
    const [isNativeButton, setIsNativeButton] = (0,react.useState)(
      () => !!tagName && (0,HWOIWM4O/* isButton */.Bm)({ tagName, type: props.type })
    );
    (0,react.useEffect)(() => {
      if (!ref.current) return;
      setIsNativeButton((0,HWOIWM4O/* isButton */.Bm)(ref.current));
    }, []);
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: !isNativeButton && tagName !== "a" ? "button" : void 0
    }, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref)
    });
    props = (0,NAXN2XAB/* useCommand */.D)(props);
    return props;
  }
);
var Button = (0,HKOOKEDE/* forwardRef */.Rf)(function Button2(props) {
  const htmlProps = useButton(props);
  return (0,HKOOKEDE/* createElement */.n)(_3R3N43YO_TagName, htmlProps);
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/RGUP62TM.js
var RGUP62TM = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/RGUP62TM.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/O3TNZQBF.js
"use client";






// src/disclosure/disclosure.tsx


var O3TNZQBF_TagName = "button";
var O3TNZQBF_symbol = Symbol("disclosure");
var useDisclosure = (0,HKOOKEDE/* createHook */.ab)(
  function useDisclosure2(_a) {
    var _b = _a, { store, toggleOnClick = true } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store", "toggleOnClick"]);
    const context = (0,RGUP62TM/* useDisclosureProviderContext */.vO)();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const [expanded, setExpanded] = (0,react.useState)(false);
    const disclosureElement = store.useState("disclosureElement");
    const open = store.useState("open");
    (0,react.useEffect)(() => {
      let isCurrentDisclosure = disclosureElement === ref.current;
      if (!(disclosureElement == null ? void 0 : disclosureElement.isConnected)) {
        store == null ? void 0 : store.setDisclosureElement(ref.current);
        isCurrentDisclosure = true;
      }
      setExpanded(open && isCurrentDisclosure);
    }, [disclosureElement, store, open]);
    const onClickProp = props.onClick;
    const toggleOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(toggleOnClick);
    const [isDuplicate, metadataProps] = (0,Z32BISHQ/* useMetadataProps */.P1)(props, O3TNZQBF_symbol, true);
    const onClick = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onClickProp == null ? void 0 : onClickProp(event);
      if (event.defaultPrevented) return;
      if (isDuplicate) return;
      if (!toggleOnClickProp(event)) return;
      store == null ? void 0 : store.setDisclosureElement(event.currentTarget);
      store == null ? void 0 : store.toggle();
    });
    const contentElement = store.useState("contentElement");
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      "aria-expanded": expanded,
      "aria-controls": contentElement == null ? void 0 : contentElement.id
    }, metadataProps), props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref),
      onClick
    });
    props = useButton(props);
    return props;
  }
);
var Disclosure = (0,HKOOKEDE/* forwardRef */.Rf)(function Disclosure2(props) {
  const htmlProps = useDisclosure(props);
  return (0,HKOOKEDE/* createElement */.n)(O3TNZQBF_TagName, htmlProps);
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/DU4D3UCJ.js
var DU4D3UCJ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/DU4D3UCJ.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/KCVNYWKF.js
"use client";





// src/dialog/dialog-disclosure.tsx


var KCVNYWKF_TagName = "button";
var useDialogDisclosure = (0,HKOOKEDE/* createHook */.ab)(
  function useDialogDisclosure2(_a) {
    var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
    const context = (0,DU4D3UCJ/* useDialogProviderContext */.cH)();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const contentElement = store.useState("contentElement");
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      "aria-haspopup": (0,HWOIWM4O/* getPopupRole */.Tc)(contentElement, "dialog")
    }, props);
    props = useDisclosure((0,_3YLGPPWQ/* __spreadValues */.IA)({ store }, props));
    return props;
  }
);
var DialogDisclosure = (0,HKOOKEDE/* forwardRef */.Rf)(function DialogDisclosure2(props) {
  const htmlProps = useDialogDisclosure(props);
  return (0,HKOOKEDE/* createElement */.n)(KCVNYWKF_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/IZAFJHVY.js
"use client";







// src/popover/popover-disclosure.tsx


var IZAFJHVY_TagName = "button";
var usePopoverDisclosure = (0,HKOOKEDE/* createHook */.ab)(function usePopoverDisclosure2(_a) {
  var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
  const context = (0,_54MGSIOI/* usePopoverProviderContext */.zG)();
  store = store || context;
  (0,PBFD2E7P/* invariant */.V1)(
    store,
     false && 0
  );
  const onClickProp = props.onClick;
  const onClick = (0,Z32BISHQ/* useEvent */._q)((event) => {
    store == null ? void 0 : store.setAnchorElement(event.currentTarget);
    onClickProp == null ? void 0 : onClickProp(event);
  });
  props = (0,Z32BISHQ/* useWrapElement */.w7)(
    props,
    (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(_54MGSIOI/* PopoverScopedContextProvider */.s1, { value: store, children: element }),
    [store]
  );
  props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    onClick
  });
  props = usePopoverAnchor((0,_3YLGPPWQ/* __spreadValues */.IA)({ store }, props));
  props = useDialogDisclosure((0,_3YLGPPWQ/* __spreadValues */.IA)({ store }, props));
  return props;
});
var PopoverDisclosure = (0,HKOOKEDE/* forwardRef */.Rf)(function PopoverDisclosure2(props) {
  const htmlProps = usePopoverDisclosure(props);
  return (0,HKOOKEDE/* createElement */.n)(IZAFJHVY_TagName, htmlProps);
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/QA27FYGF.js
var QA27FYGF = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/QA27FYGF.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/AXRBYZQP.js
var AXRBYZQP = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/AXRBYZQP.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu-button.js
"use client";


























// src/menu/menu-button.tsx




var menu_button_TagName = "button";
function getInitialFocus(event, dir) {
  const keyMap = {
    ArrowDown: dir === "bottom" || dir === "top" ? "first" : false,
    ArrowUp: dir === "bottom" || dir === "top" ? "last" : false,
    ArrowRight: dir === "right" ? "first" : false,
    ArrowLeft: dir === "left" ? "first" : false
  };
  return keyMap[event.key];
}
function hasActiveItem(items, excludeElement) {
  return !!(items == null ? void 0 : items.some((item) => {
    if (!item.element) return false;
    if (item.element === excludeElement) return false;
    return item.element.getAttribute("aria-expanded") === "true";
  }));
}
var useMenuButton = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuButton2(_a) {
    var _b = _a, {
      store,
      focusable,
      accessibleWhenDisabled,
      showOnHover
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "focusable",
      "accessibleWhenDisabled",
      "showOnHover"
    ]);
    const context = useMenuProviderContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const parentMenu = store.parent;
    const parentMenubar = store.menubar;
    const hasParentMenu = !!parentMenu;
    const parentIsMenubar = !!parentMenubar && !hasParentMenu;
    const disabled = (0,PBFD2E7P/* disabledFromProps */.$f)(props);
    const showMenu = () => {
      const trigger = ref.current;
      if (!trigger) return;
      store == null ? void 0 : store.setDisclosureElement(trigger);
      store == null ? void 0 : store.setAnchorElement(trigger);
      store == null ? void 0 : store.show();
    };
    const onFocusProp = props.onFocus;
    const onFocus = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onFocusProp == null ? void 0 : onFocusProp(event);
      if (disabled) return;
      if (event.defaultPrevented) return;
      store == null ? void 0 : store.setAutoFocusOnShow(false);
      store == null ? void 0 : store.setActiveId(null);
      if (!parentMenubar) return;
      if (!parentIsMenubar) return;
      const { items } = parentMenubar.getState();
      if (hasActiveItem(items, event.currentTarget)) {
        showMenu();
      }
    });
    const dir = store.useState(
      (state) => state.placement.split("-")[0]
    );
    const onKeyDownProp = props.onKeyDown;
    const onKeyDown = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (disabled) return;
      if (event.defaultPrevented) return;
      const initialFocus = getInitialFocus(event, dir);
      if (initialFocus) {
        event.preventDefault();
        showMenu();
        store == null ? void 0 : store.setAutoFocusOnShow(true);
        store == null ? void 0 : store.setInitialFocus(initialFocus);
      }
    });
    const onClickProp = props.onClick;
    const onClick = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onClickProp == null ? void 0 : onClickProp(event);
      if (event.defaultPrevented) return;
      if (!store) return;
      const isKeyboardClick = !event.detail;
      const { open } = store.getState();
      if (!open || isKeyboardClick) {
        if (!hasParentMenu || isKeyboardClick) {
          store.setAutoFocusOnShow(true);
        }
        store.setInitialFocus(isKeyboardClick ? "first" : "container");
      }
      if (hasParentMenu) {
        showMenu();
      }
    });
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(MenuContextProvider, { value: store, children: element }),
      [store]
    );
    if (hasParentMenu) {
      props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
        render: /* @__PURE__ */ (0,jsx_runtime.jsx)(AXRBYZQP/* Role */.X.div, { render: props.render })
      });
    }
    const id = (0,Z32BISHQ/* useId */.Bi)(props.id);
    const parentContentElement = (0,_2GXGCHW6/* useStoreState */.O$)(
      (parentMenu == null ? void 0 : parentMenu.combobox) || parentMenu,
      "contentElement"
    );
    const role = hasParentMenu || parentIsMenubar ? (0,HWOIWM4O/* getPopupItemRole */.cn)(parentContentElement, "menuitem") : void 0;
    const contentElement = store.useState("contentElement");
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      id,
      role,
      "aria-haspopup": (0,HWOIWM4O/* getPopupRole */.Tc)(contentElement, "menu")
    }, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref),
      onFocus,
      onKeyDown,
      onClick
    });
    props = (0,QA27FYGF/* useHovercardAnchor */.p)((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store,
      focusable,
      accessibleWhenDisabled
    }, props), {
      showOnHover: (event) => {
        const getShowOnHover = () => {
          if (typeof showOnHover === "function") return showOnHover(event);
          if (showOnHover != null) return showOnHover;
          if (hasParentMenu) return true;
          if (!parentMenubar) return false;
          const { items } = parentMenubar.getState();
          return parentIsMenubar && hasActiveItem(items);
        };
        const canShowOnHover = getShowOnHover();
        if (!canShowOnHover) return false;
        const parent = parentIsMenubar ? parentMenubar : parentMenu;
        if (!parent) return true;
        parent.setActiveId(event.currentTarget.id);
        return true;
      }
    }));
    props = usePopoverDisclosure((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store,
      toggleOnClick: !hasParentMenu,
      focusable,
      accessibleWhenDisabled
    }, props));
    props = useCompositeTypeahead((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store,
      typeahead: parentIsMenubar
    }, props));
    return props;
  }
);
var MenuButton = (0,HKOOKEDE/* forwardRef */.Rf)(function MenuButton2(props) {
  const htmlProps = useMenuButton(props);
  return (0,HKOOKEDE/* createElement */.n)(menu_button_TagName, htmlProps);
});


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/ULASSYJG.js
"use client";









// src/menu/menu-list.tsx



var ULASSYJG_TagName = "div";
function useAriaLabelledBy(_a) {
  var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
  const [id, setId] = (0,react.useState)(void 0);
  const label = props["aria-label"];
  const disclosureElement = (0,_2GXGCHW6/* useStoreState */.O$)(store, "disclosureElement");
  const contentElement = (0,_2GXGCHW6/* useStoreState */.O$)(store, "contentElement");
  (0,react.useEffect)(() => {
    const disclosure = disclosureElement;
    if (!disclosure) return;
    const menu = contentElement;
    if (!menu) return;
    const menuLabel = label || menu.hasAttribute("aria-label");
    if (menuLabel) {
      setId(void 0);
    } else if (disclosure.id) {
      setId(disclosure.id);
    }
  }, [label, disclosureElement, contentElement]);
  return id;
}
var useMenuList = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuList2(_a) {
    var _b = _a, { store, alwaysVisible, composite } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store", "alwaysVisible", "composite"]);
    const context = useMenuProviderContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const parentMenu = store.parent;
    const parentMenubar = store.menubar;
    const hasParentMenu = !!parentMenu;
    const id = (0,Z32BISHQ/* useId */.Bi)(props.id);
    const onKeyDownProp = props.onKeyDown;
    const dir = store.useState(
      (state) => state.placement.split("-")[0]
    );
    const orientation = store.useState(
      (state) => state.orientation === "both" ? void 0 : state.orientation
    );
    const isHorizontal = orientation !== "vertical";
    const isMenubarHorizontal = (0,_2GXGCHW6/* useStoreState */.O$)(
      parentMenubar,
      (state) => !!state && state.orientation !== "vertical"
    );
    const onKeyDown = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (event.defaultPrevented) return;
      if (hasParentMenu || parentMenubar && !isHorizontal) {
        const hideMap = {
          ArrowRight: () => dir === "left" && !isHorizontal,
          ArrowLeft: () => dir === "right" && !isHorizontal,
          ArrowUp: () => dir === "bottom" && isHorizontal,
          ArrowDown: () => dir === "top" && isHorizontal
        };
        const action = hideMap[event.key];
        if (action == null ? void 0 : action()) {
          event.stopPropagation();
          event.preventDefault();
          return store == null ? void 0 : store.hide();
        }
      }
      if (parentMenubar) {
        const keyMap = {
          ArrowRight: () => {
            if (!isMenubarHorizontal) return;
            return parentMenubar.next();
          },
          ArrowLeft: () => {
            if (!isMenubarHorizontal) return;
            return parentMenubar.previous();
          },
          ArrowDown: () => {
            if (isMenubarHorizontal) return;
            return parentMenubar.next();
          },
          ArrowUp: () => {
            if (isMenubarHorizontal) return;
            return parentMenubar.previous();
          }
        };
        const action = keyMap[event.key];
        const id2 = action == null ? void 0 : action();
        if (id2 !== void 0) {
          event.stopPropagation();
          event.preventDefault();
          parentMenubar.move(id2);
        }
      }
    });
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(MenuScopedContextProvider, { value: store, children: element }),
      [store]
    );
    const ariaLabelledBy = useAriaLabelledBy((0,_3YLGPPWQ/* __spreadValues */.IA)({ store }, props));
    const mounted = store.useState("mounted");
    const hidden = (0,BSEL4YAF/* isHidden */.dK)(mounted, props.hidden, alwaysVisible);
    const style = hidden ? (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props.style), { display: "none" }) : props.style;
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      id,
      "aria-labelledby": ariaLabelledBy,
      hidden
    }, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(id ? store.setContentElement : null, props.ref),
      style,
      onKeyDown
    });
    const hasCombobox = !!store.combobox;
    composite = composite != null ? composite : !hasCombobox;
    if (composite) {
      props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
        role: "menu",
        "aria-orientation": orientation
      }, props);
    }
    props = (0,TW35PKTK/* useComposite */.T)((0,_3YLGPPWQ/* __spreadValues */.IA)({ store, composite }, props));
    props = useCompositeTypeahead((0,_3YLGPPWQ/* __spreadValues */.IA)({ store, typeahead: !hasCombobox }, props));
    return props;
  }
);
var MenuList = (0,HKOOKEDE/* forwardRef */.Rf)(function MenuList2(props) {
  const htmlProps = useMenuList(props);
  return (0,HKOOKEDE/* createElement */.n)(ULASSYJG_TagName, htmlProps);
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/HQFKUKP3.js + 2 modules
var HQFKUKP3 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/HQFKUKP3.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/TT2355LN.js + 21 modules
var TT2355LN = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/TT2355LN.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu.js
"use client";

















































// src/menu/menu.tsx




var menu_TagName = "div";
var useMenu = (0,HKOOKEDE/* createHook */.ab)(function useMenu2(_a) {
  var _b = _a, {
    store,
    modal: modalProp = false,
    portal = !!modalProp,
    hideOnEscape = true,
    autoFocusOnShow = true,
    hideOnHoverOutside,
    alwaysVisible
  } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
    "store",
    "modal",
    "portal",
    "hideOnEscape",
    "autoFocusOnShow",
    "hideOnHoverOutside",
    "alwaysVisible"
  ]);
  const context = useMenuProviderContext();
  store = store || context;
  (0,PBFD2E7P/* invariant */.V1)(
    store,
     false && 0
  );
  const ref = (0,react.useRef)(null);
  const parentMenu = store.parent;
  const parentMenubar = store.menubar;
  const hasParentMenu = !!parentMenu;
  const parentIsMenubar = !!parentMenubar && !hasParentMenu;
  props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref)
  });
  const _a2 = useMenuList((0,_3YLGPPWQ/* __spreadValues */.IA)({
    store,
    alwaysVisible
  }, props)), { "aria-labelledby": ariaLabelledBy } = _a2, menuListProps = (0,_3YLGPPWQ/* __objRest */.YG)(_a2, ["aria-labelledby"]);
  props = menuListProps;
  const [initialFocusRef, setInitialFocusRef] = (0,react.useState)();
  const autoFocusOnShowState = store.useState("autoFocusOnShow");
  const initialFocus = store.useState("initialFocus");
  const baseElement = store.useState("baseElement");
  const items = store.useState("renderedItems");
  (0,react.useEffect)(() => {
    let cleaning = false;
    setInitialFocusRef((prevInitialFocusRef) => {
      var _a3, _b2, _c;
      if (cleaning) return;
      if (!autoFocusOnShowState) return;
      if ((_a3 = prevInitialFocusRef == null ? void 0 : prevInitialFocusRef.current) == null ? void 0 : _a3.isConnected) return prevInitialFocusRef;
      const ref2 = (0,react.createRef)();
      switch (initialFocus) {
        case "first":
          ref2.current = ((_b2 = items.find((item) => !item.disabled && item.element)) == null ? void 0 : _b2.element) || null;
          break;
        case "last":
          ref2.current = ((_c = [...items].reverse().find((item) => !item.disabled && item.element)) == null ? void 0 : _c.element) || null;
          break;
        default:
          ref2.current = baseElement;
      }
      return ref2;
    });
    return () => {
      cleaning = true;
    };
  }, [store, autoFocusOnShowState, initialFocus, items, baseElement]);
  const modal = hasParentMenu ? false : modalProp;
  const mayAutoFocusOnShow = !!autoFocusOnShow;
  const canAutoFocusOnShow = !!initialFocusRef || !!props.initialFocus || !!modal;
  const contentElement = (0,_2GXGCHW6/* useStoreState */.O$)(
    store.combobox || store,
    "contentElement"
  );
  const parentContentElement = (0,_2GXGCHW6/* useStoreState */.O$)(
    (parentMenu == null ? void 0 : parentMenu.combobox) || parentMenu,
    "contentElement"
  );
  const preserveTabOrderAnchor = (0,react.useMemo)(() => {
    if (!parentContentElement) return;
    if (!contentElement) return;
    const role = contentElement.getAttribute("role");
    const parentRole = parentContentElement.getAttribute("role");
    const parentIsMenuOrMenubar = parentRole === "menu" || parentRole === "menubar";
    if (parentIsMenuOrMenubar && role === "menu") return;
    return parentContentElement;
  }, [contentElement, parentContentElement]);
  if (preserveTabOrderAnchor !== void 0) {
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      preserveTabOrderAnchor
    }, props);
  }
  props = (0,HQFKUKP3/* useHovercard */.a)((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
    store,
    alwaysVisible,
    initialFocus: initialFocusRef,
    autoFocusOnShow: mayAutoFocusOnShow ? canAutoFocusOnShow && autoFocusOnShow : autoFocusOnShowState || !!modal
  }, props), {
    hideOnEscape(event) {
      if ((0,PBFD2E7P/* isFalsyBooleanCallback */.zO)(hideOnEscape, event)) return false;
      store == null ? void 0 : store.hideAll();
      return true;
    },
    hideOnHoverOutside(event) {
      const disclosureElement = store == null ? void 0 : store.getState().disclosureElement;
      const getHideOnHoverOutside = () => {
        if (typeof hideOnHoverOutside === "function") {
          return hideOnHoverOutside(event);
        }
        if (hideOnHoverOutside != null) return hideOnHoverOutside;
        if (hasParentMenu) return true;
        if (!parentIsMenubar) return false;
        if (!disclosureElement) return true;
        if ((0,utils_focus/* hasFocusWithin */.oW)(disclosureElement)) return false;
        return true;
      };
      if (!getHideOnHoverOutside()) return false;
      if (event.defaultPrevented) return true;
      if (!hasParentMenu) return true;
      if (!disclosureElement) return true;
      (0,events/* fireEvent */.rC)(disclosureElement, "mouseout", event);
      if (!(0,utils_focus/* hasFocusWithin */.oW)(disclosureElement)) return true;
      requestAnimationFrame(() => {
        if ((0,utils_focus/* hasFocusWithin */.oW)(disclosureElement)) return;
        store == null ? void 0 : store.hide();
      });
      return false;
    },
    modal,
    portal,
    backdrop: hasParentMenu ? false : props.backdrop
  }));
  props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
    "aria-labelledby": ariaLabelledBy
  }, props);
  return props;
});
var Menu = (0,TT2355LN/* createDialogComponent */.AV)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function Menu2(props) {
    const htmlProps = useMenu(props);
    return (0,HKOOKEDE/* createElement */.n)(menu_TagName, htmlProps);
  }),
  useMenuProviderContext
);


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/chevron-right-small.js
/**
 * WordPress dependencies
 */


const chevronRightSmall = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M10.8622 8.04053L14.2805 12.0286L10.8622 16.0167L9.72327 15.0405L12.3049 12.0286L9.72327 9.01672L10.8622 8.04053Z"
  })
});
/* harmony default export */ const chevron_right_small = (chevronRightSmall);
//# sourceMappingURL=chevron-right-small.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+styled@11.11.0_@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2__@types+react@17.0.71_react@17.0.2/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js
var emotion_styled_base_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+styled@11.11.0_@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2__@types+react@17.0.71_react@17.0.2/node_modules/@emotion/styled/base/dist/emotion-styled-base.browser.esm.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/B6XZVSMQ.js
"use client";









// src/menu/menu-item.tsx




var B6XZVSMQ_TagName = "div";
function menuHasFocus(baseElement, items, currentTarget) {
  var _a;
  if (!baseElement) return false;
  if ((0,utils_focus/* hasFocusWithin */.oW)(baseElement)) return true;
  const expandedItem = items == null ? void 0 : items.find((item) => {
    var _a2;
    if (item.element === currentTarget) return false;
    return ((_a2 = item.element) == null ? void 0 : _a2.getAttribute("aria-expanded")) === "true";
  });
  const expandedMenuId = (_a = expandedItem == null ? void 0 : expandedItem.element) == null ? void 0 : _a.getAttribute("aria-controls");
  if (!expandedMenuId) return false;
  const doc = (0,HWOIWM4O/* getDocument */.YE)(baseElement);
  const expandedMenu = doc.getElementById(expandedMenuId);
  if (!expandedMenu) return false;
  if ((0,utils_focus/* hasFocusWithin */.oW)(expandedMenu)) return true;
  return !!expandedMenu.querySelector("[role=menuitem][aria-expanded=true]");
}
var useMenuItem = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuItem2(_a) {
    var _b = _a, {
      store,
      hideOnClick = true,
      preventScrollOnKeyDown = true,
      focusOnHover,
      blurOnHoverEnd
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "hideOnClick",
      "preventScrollOnKeyDown",
      "focusOnHover",
      "blurOnHoverEnd"
    ]);
    const menuContext = useMenuScopedContext(true);
    const menubarContext = WSQNIDGC_useMenubarScopedContext();
    store = store || menuContext || menubarContext;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const onClickProp = props.onClick;
    const hideOnClickProp = (0,Z32BISHQ/* useBooleanEvent */.O4)(hideOnClick);
    const hideMenu = "hideAll" in store ? store.hideAll : void 0;
    const isWithinMenu = !!hideMenu;
    const onClick = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onClickProp == null ? void 0 : onClickProp(event);
      if (event.defaultPrevented) return;
      if ((0,events/* isDownloading */.RN)(event)) return;
      if ((0,events/* isOpeningInNewTab */.$b)(event)) return;
      if (!hideMenu) return;
      const popupType = event.currentTarget.getAttribute("aria-haspopup");
      if (popupType === "menu") return;
      if (!hideOnClickProp(event)) return;
      hideMenu();
    });
    const contentElement = (0,_2GXGCHW6/* useStoreState */.O$)(
      store,
      (state) => "contentElement" in state ? state.contentElement : null
    );
    const role = (0,HWOIWM4O/* getPopupItemRole */.cn)(contentElement, "menuitem");
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      role
    }, props), {
      onClick
    });
    props = (0,_3CCTMYB6/* useCompositeItem */.k)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store,
      preventScrollOnKeyDown
    }, props));
    props = useCompositeHover((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store
    }, props), {
      focusOnHover(event) {
        const getFocusOnHover = () => {
          if (typeof focusOnHover === "function") return focusOnHover(event);
          if (focusOnHover != null) return focusOnHover;
          return true;
        };
        if (!store) return false;
        if (!getFocusOnHover()) return false;
        const { baseElement, items } = store.getState();
        if (isWithinMenu) {
          if (event.currentTarget.hasAttribute("aria-expanded")) {
            event.currentTarget.focus();
          }
          return true;
        }
        if (menuHasFocus(baseElement, items, event.currentTarget)) {
          event.currentTarget.focus();
          return true;
        }
        return false;
      },
      blurOnHoverEnd(event) {
        if (typeof blurOnHoverEnd === "function") return blurOnHoverEnd(event);
        if (blurOnHoverEnd != null) return blurOnHoverEnd;
        return isWithinMenu;
      }
    }));
    return props;
  }
);
var MenuItem = (0,HKOOKEDE/* memo */.ph)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function MenuItem2(props) {
    const htmlProps = useMenuItem(props);
    return (0,HKOOKEDE/* createElement */.n)(B6XZVSMQ_TagName, htmlProps);
  })
);



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/AUGWLYYL.js
"use client";


// src/checkbox/checkbox-context.tsx
var AUGWLYYL_ctx = (0,HKOOKEDE/* createStoreContext */.B0)();
var useCheckboxContext = AUGWLYYL_ctx.useContext;
var useCheckboxScopedContext = AUGWLYYL_ctx.useScopedContext;
var useCheckboxProviderContext = AUGWLYYL_ctx.useProviderContext;
var CheckboxContextProvider = AUGWLYYL_ctx.ContextProvider;
var CheckboxScopedContextProvider = AUGWLYYL_ctx.ScopedContextProvider;



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/EYKMH5G5.js
"use client";

// src/checkbox/checkbox-checked-context.tsx

var CheckboxCheckedContext = (0,react.createContext)(false);



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/L7GTXQJP.js
"use client";








// src/checkbox/checkbox.tsx



var L7GTXQJP_TagName = "input";
function setMixed(element, mixed) {
  if (mixed) {
    element.indeterminate = true;
  } else if (element.indeterminate) {
    element.indeterminate = false;
  }
}
function isNativeCheckbox(tagName, type) {
  return tagName === "input" && (!type || type === "checkbox");
}
function getPrimitiveValue(value) {
  if (Array.isArray(value)) {
    return value.toString();
  }
  return value;
}
var useCheckbox = (0,HKOOKEDE/* createHook */.ab)(
  function useCheckbox2(_a) {
    var _b = _a, {
      store,
      name,
      value: valueProp,
      checked: checkedProp,
      defaultChecked
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "name",
      "value",
      "checked",
      "defaultChecked"
    ]);
    const context = useCheckboxContext();
    store = store || context;
    const [_checked, setChecked] = (0,react.useState)(defaultChecked != null ? defaultChecked : false);
    const checked = (0,_2GXGCHW6/* useStoreState */.O$)(store, (state) => {
      if (checkedProp !== void 0) return checkedProp;
      if ((state == null ? void 0 : state.value) === void 0) return _checked;
      if (valueProp != null) {
        if (Array.isArray(state.value)) {
          const primitiveValue = getPrimitiveValue(valueProp);
          return state.value.includes(primitiveValue);
        }
        return state.value === valueProp;
      }
      if (Array.isArray(state.value)) return false;
      if (typeof state.value === "boolean") return state.value;
      return false;
    });
    const ref = (0,react.useRef)(null);
    const tagName = (0,Z32BISHQ/* useTagName */.vO)(ref, L7GTXQJP_TagName);
    const nativeCheckbox = isNativeCheckbox(tagName, props.type);
    const mixed = checked ? checked === "mixed" : void 0;
    const isChecked = checked === "mixed" ? false : checked;
    const disabled = (0,PBFD2E7P/* disabledFromProps */.$f)(props);
    const [propertyUpdated, schedulePropertyUpdate] = (0,Z32BISHQ/* useForceUpdate */.CH)();
    (0,react.useEffect)(() => {
      const element = ref.current;
      if (!element) return;
      setMixed(element, mixed);
      if (nativeCheckbox) return;
      element.checked = isChecked;
      if (name !== void 0) {
        element.name = name;
      }
      if (valueProp !== void 0) {
        element.value = `${valueProp}`;
      }
    }, [propertyUpdated, mixed, nativeCheckbox, isChecked, name, valueProp]);
    const onChangeProp = props.onChange;
    const onChange = (0,Z32BISHQ/* useEvent */._q)((event) => {
      if (disabled) {
        event.stopPropagation();
        event.preventDefault();
        return;
      }
      setMixed(event.currentTarget, mixed);
      if (!nativeCheckbox) {
        event.currentTarget.checked = !event.currentTarget.checked;
        schedulePropertyUpdate();
      }
      onChangeProp == null ? void 0 : onChangeProp(event);
      if (event.defaultPrevented) return;
      const elementChecked = event.currentTarget.checked;
      setChecked(elementChecked);
      store == null ? void 0 : store.setValue((prevValue) => {
        if (valueProp == null) return elementChecked;
        const primitiveValue = getPrimitiveValue(valueProp);
        if (!Array.isArray(prevValue)) {
          return prevValue === primitiveValue ? false : primitiveValue;
        }
        if (elementChecked) {
          if (prevValue.includes(primitiveValue)) {
            return prevValue;
          }
          return [...prevValue, primitiveValue];
        }
        return prevValue.filter((v) => v !== primitiveValue);
      });
    });
    const onClickProp = props.onClick;
    const onClick = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onClickProp == null ? void 0 : onClickProp(event);
      if (event.defaultPrevented) return;
      if (nativeCheckbox) return;
      onChange(event);
    });
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(CheckboxCheckedContext.Provider, { value: isChecked, children: element }),
      [isChecked]
    );
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: !nativeCheckbox ? "checkbox" : void 0,
      type: nativeCheckbox ? "checkbox" : void 0,
      "aria-checked": checked
    }, props), {
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref),
      onChange,
      onClick
    });
    props = (0,NAXN2XAB/* useCommand */.D)((0,_3YLGPPWQ/* __spreadValues */.IA)({ clickOnEnter: !nativeCheckbox }, props));
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      name: nativeCheckbox ? name : void 0,
      value: nativeCheckbox ? valueProp : void 0,
      checked: isChecked
    }, props));
  }
);
var Checkbox = (0,HKOOKEDE/* forwardRef */.Rf)(function Checkbox2(props) {
  const htmlProps = useCheckbox(props);
  return (0,HKOOKEDE/* createElement */.n)(L7GTXQJP_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/checkbox/checkbox-store.js
"use client";




// src/checkbox/checkbox-store.ts
function createCheckboxStore(props = {}) {
  var _a;
  (0,EQQLU3CG/* throwOnConflictingProps */.UE)(props, props.store);
  const syncState = (_a = props.store) == null ? void 0 : _a.getState();
  const initialState = {
    value: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.value,
      syncState == null ? void 0 : syncState.value,
      props.defaultValue,
      false
    )
  };
  const checkbox = (0,EQQLU3CG/* createStore */.y$)(initialState, props.store);
  return (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, checkbox), {
    setValue: (value) => checkbox.setState("value", value)
  });
}


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/EJOTW52C.js
"use client";



// src/checkbox/checkbox-store.ts

function useCheckboxStoreProps(store, update, props) {
  (0,Z32BISHQ/* useUpdateEffect */.w5)(update, [props.store]);
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "value", "setValue");
  return store;
}
function useCheckboxStore(props = {}) {
  const [store, update] = (0,_2GXGCHW6/* useStore */.Pj)(createCheckboxStore, props);
  return useCheckboxStoreProps(store, update, props);
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu-item-checkbox.js
"use client";


























// src/menu/menu-item-checkbox.tsx


var menu_item_checkbox_TagName = "div";
function menu_item_checkbox_getPrimitiveValue(value) {
  if (Array.isArray(value)) {
    return value.toString();
  }
  return value;
}
function getValue(storeValue, value, checked) {
  if (value === void 0) {
    if (Array.isArray(storeValue)) return storeValue;
    return !!checked;
  }
  const primitiveValue = menu_item_checkbox_getPrimitiveValue(value);
  if (!Array.isArray(storeValue)) {
    if (checked) {
      return primitiveValue;
    }
    return storeValue === primitiveValue ? false : storeValue;
  }
  if (checked) {
    if (storeValue.includes(primitiveValue)) {
      return storeValue;
    }
    return [...storeValue, primitiveValue];
  }
  return storeValue.filter((v) => v !== primitiveValue);
}
var useMenuItemCheckbox = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuItemCheckbox2(_a) {
    var _b = _a, {
      store,
      name,
      value,
      checked,
      defaultChecked: defaultCheckedProp,
      hideOnClick = false
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "name",
      "value",
      "checked",
      "defaultChecked",
      "hideOnClick"
    ]);
    const context = useMenuScopedContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const defaultChecked = (0,Z32BISHQ/* useInitialValue */.nf)(defaultCheckedProp);
    (0,react.useEffect)(() => {
      store == null ? void 0 : store.setValue(name, (prevValue = []) => {
        if (!defaultChecked) return prevValue;
        return getValue(prevValue, value, true);
      });
    }, [store, name, value, defaultChecked]);
    (0,react.useEffect)(() => {
      if (checked === void 0) return;
      store == null ? void 0 : store.setValue(name, (prevValue) => {
        return getValue(prevValue, value, checked);
      });
    }, [store, name, value, checked]);
    const checkboxStore = useCheckboxStore({
      value: store.useState((state) => state.values[name]),
      setValue(internalValue) {
        store == null ? void 0 : store.setValue(name, () => {
          if (checked === void 0) return internalValue;
          const nextValue = getValue(internalValue, value, checked);
          if (!Array.isArray(nextValue)) return nextValue;
          if (!Array.isArray(internalValue)) return nextValue;
          if ((0,PBFD2E7P/* shallowEqual */.bN)(internalValue, nextValue)) return internalValue;
          return nextValue;
        });
      }
    });
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: "menuitemcheckbox"
    }, props);
    props = useCheckbox((0,_3YLGPPWQ/* __spreadValues */.IA)({
      store: checkboxStore,
      name,
      value,
      checked
    }, props));
    props = useMenuItem((0,_3YLGPPWQ/* __spreadValues */.IA)({ store, hideOnClick }, props));
    return props;
  }
);
var MenuItemCheckbox = (0,HKOOKEDE/* memo */.ph)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function MenuItemCheckbox2(props) {
    const htmlProps = useMenuItemCheckbox(props);
    return (0,HKOOKEDE/* createElement */.n)(menu_item_checkbox_TagName, htmlProps);
  })
);


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/K7FXVWIT.js
var K7FXVWIT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/K7FXVWIT.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu-item-radio.js
"use client";
























// src/menu/menu-item-radio.tsx



var menu_item_radio_TagName = "div";
function menu_item_radio_getValue(prevValue, value, checked) {
  if (checked === void 0) return prevValue;
  if (checked) return value;
  return prevValue;
}
var useMenuItemRadio = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuItemRadio2(_a) {
    var _b = _a, {
      store,
      name,
      value,
      checked,
      onChange: onChangeProp,
      hideOnClick = false
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "name",
      "value",
      "checked",
      "onChange",
      "hideOnClick"
    ]);
    const context = useMenuScopedContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const defaultChecked = (0,Z32BISHQ/* useInitialValue */.nf)(props.defaultChecked);
    (0,react.useEffect)(() => {
      store == null ? void 0 : store.setValue(name, (prevValue = false) => {
        return menu_item_radio_getValue(prevValue, value, defaultChecked);
      });
    }, [store, name, value, defaultChecked]);
    (0,react.useEffect)(() => {
      if (checked === void 0) return;
      store == null ? void 0 : store.setValue(name, (prevValue) => {
        return menu_item_radio_getValue(prevValue, value, checked);
      });
    }, [store, name, value, checked]);
    const isChecked = store.useState((state) => state.values[name] === value);
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(MenuItemCheckedContext.Provider, { value: !!isChecked, children: element }),
      [isChecked]
    );
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: "menuitemradio"
    }, props);
    props = (0,K7FXVWIT/* useRadio */.z)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      name,
      value,
      checked: isChecked,
      onChange(event) {
        onChangeProp == null ? void 0 : onChangeProp(event);
        if (event.defaultPrevented) return;
        const element = event.currentTarget;
        store == null ? void 0 : store.setValue(name, (prevValue) => {
          return menu_item_radio_getValue(prevValue, value, checked != null ? checked : element.checked);
        });
      }
    }, props));
    props = useMenuItem((0,_3YLGPPWQ/* __spreadValues */.IA)({ store, hideOnClick }, props));
    return props;
  }
);
var MenuItemRadio = (0,HKOOKEDE/* memo */.ph)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function MenuItemRadio2(props) {
    const htmlProps = useMenuItemRadio(props);
    return (0,HKOOKEDE/* createElement */.n)(menu_item_radio_TagName, htmlProps);
  })
);


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu-group.js
"use client";








// src/menu/menu-group.tsx
var menu_group_TagName = "div";
var useMenuGroup = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuGroup2(props) {
    props = useCompositeGroup(props);
    return props;
  }
);
var MenuGroup = (0,HKOOKEDE/* forwardRef */.Rf)(function MenuGroup2(props) {
  const htmlProps = useMenuGroup(props);
  return (0,HKOOKEDE/* createElement */.n)(menu_group_TagName, htmlProps);
});


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu-group-label.js
"use client";








// src/menu/menu-group-label.tsx
var menu_group_label_TagName = "div";
var useMenuGroupLabel = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuGroupLabel2(props) {
    props = useCompositeGroupLabel(props);
    return props;
  }
);
var MenuGroupLabel = (0,HKOOKEDE/* forwardRef */.Rf)(function MenuGroupLabel2(props) {
  const htmlProps = useMenuGroupLabel(props);
  return (0,HKOOKEDE/* createElement */.n)(menu_group_label_TagName, htmlProps);
});


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/LOI6GHIP.js
"use client";



// src/separator/separator.tsx
var LOI6GHIP_TagName = "hr";
var useSeparator = (0,HKOOKEDE/* createHook */.ab)(
  function useSeparator2(_a) {
    var _b = _a, { orientation = "horizontal" } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["orientation"]);
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: "separator",
      "aria-orientation": orientation
    }, props);
    return props;
  }
);
var Separator = (0,HKOOKEDE/* forwardRef */.Rf)(function Separator2(props) {
  const htmlProps = useSeparator(props);
  return (0,HKOOKEDE/* createElement */.n)(LOI6GHIP_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/WEEEI3KU.js
"use client";





// src/composite/composite-separator.tsx

var WEEEI3KU_TagName = "hr";
var useCompositeSeparator = (0,HKOOKEDE/* createHook */.ab)(function useCompositeSeparator2(_a) {
  var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
  const context = (0,WENSINUV/* useCompositeContext */.k)();
  store = store || context;
  (0,PBFD2E7P/* invariant */.V1)(
    store,
     false && 0
  );
  const orientation = store.useState(
    (state) => state.orientation === "horizontal" ? "vertical" : "horizontal"
  );
  props = useSeparator((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), { orientation }));
  return props;
});
var CompositeSeparator = (0,HKOOKEDE/* forwardRef */.Rf)(function CompositeSeparator2(props) {
  const htmlProps = useCompositeSeparator(props);
  return (0,HKOOKEDE/* createElement */.n)(WEEEI3KU_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu-separator.js
"use client";















// src/menu/menu-separator.tsx
var menu_separator_TagName = "hr";
var useMenuSeparator = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuSeparator2(_a) {
    var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
    const context = useMenuContext();
    store = store || context;
    props = useCompositeSeparator((0,_3YLGPPWQ/* __spreadValues */.IA)({ store }, props));
    return props;
  }
);
var MenuSeparator = (0,HKOOKEDE/* forwardRef */.Rf)(function MenuSeparator2(props) {
  const htmlProps = useMenuSeparator(props);
  return (0,HKOOKEDE/* createElement */.n)(menu_separator_TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.11.1_@types+react@17.0.71_react@17.0.2/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/colors-values.js
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/colors-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/config-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/font.js + 1 modules
var font = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/font.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/rtl.js
var rtl = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/rtl.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/space.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/view/component.js
var view_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/truncate/hook.js + 2 modules
var hook = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/truncate/hook.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/truncate/component.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */





function UnconnectedTruncate(props, forwardedRef) {
  const truncateProps = (0,hook/* default */.A)(props);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(view_component/* default */.A, {
    as: "span",
    ...truncateProps,
    ref: forwardedRef
  });
}

/**
 * `Truncate` is a typography primitive that trims text content.
 * For almost all cases, it is recommended that `Text`, `Heading`, or
 * `Subheading` is used to render text content. However,`Truncate` is
 * available for custom implementations.
 *
 * ```jsx
 * import { __experimentalTruncate as Truncate } from `@wordpress/components`;
 *
 * function Example() {
 * 	return (
 * 		<Truncate>
 * 			Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc ex
 * 			neque, vulputate a diam et, luctus convallis lacus. Vestibulum ac
 * 			mollis mi. Morbi id elementum massa.
 * 		</Truncate>
 * 	);
 * }
 * ```
 */
const Truncate = (0,context_connect/* contextConnect */.KZ)(UnconnectedTruncate, 'Truncate');
/* harmony default export */ const truncate_component = (Truncate);
//# sourceMappingURL=component.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/styles.js

function _EMOTION_STRINGIFIED_CSS_ERROR__() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




const ANIMATION_PARAMS = {
  SCALE_AMOUNT_OUTER: 0.82,
  SCALE_AMOUNT_CONTENT: 0.9,
  DURATION: {
    IN: '400ms',
    OUT: '200ms'
  },
  EASING: 'cubic-bezier(0.33, 0, 0, 1)'
};
const CONTENT_WRAPPER_PADDING = (0,space/* space */.x)(1);
const ITEM_PADDING_BLOCK = (0,space/* space */.x)(2);
const ITEM_PADDING_INLINE = (0,space/* space */.x)(3);

// TODO:
// - border color and divider color are different from COLORS.theme variables
// - lighter text color is not defined in COLORS.theme, should it be?
// - lighter background color is not defined in COLORS.theme, should it be?
const DEFAULT_BORDER_COLOR = colors_values/* COLORS */.l.theme.gray[300];
const DIVIDER_COLOR = colors_values/* COLORS */.l.theme.gray[200];
const LIGHTER_TEXT_COLOR = colors_values/* COLORS */.l.theme.gray[700];
const LIGHT_BACKGROUND_COLOR = colors_values/* COLORS */.l.theme.gray[100];
const TOOLBAR_VARIANT_BORDER_COLOR = colors_values/* COLORS */.l.theme.foreground;
const DEFAULT_BOX_SHADOW = `0 0 0 ${config_values/* default */.A.borderWidth} ${DEFAULT_BORDER_COLOR}, ${config_values/* default */.A.elevationMedium}`;
const TOOLBAR_VARIANT_BOX_SHADOW = `0 0 0 ${config_values/* default */.A.borderWidth} ${TOOLBAR_VARIANT_BORDER_COLOR}`;
const GRID_TEMPLATE_COLS = 'minmax( 0, max-content ) 1fr';
const MenuPopoverOuterWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1kdzosf14"
} : 0)("position:relative;background-color:", colors_values/* COLORS */.l.ui.background, ";border-radius:", config_values/* default */.A.radiusMedium, ";", props => /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("box-shadow:", props.variant === 'toolbar' ? TOOLBAR_VARIANT_BOX_SHADOW : DEFAULT_BOX_SHADOW, ";" + ( true ? "" : 0),  true ? "" : 0), " overflow:hidden;@media not ( prefers-reduced-motion ){transition-property:transform,opacity;transition-timing-function:", ANIMATION_PARAMS.EASING, ";transition-duration:", ANIMATION_PARAMS.DURATION.IN, ";will-change:transform,opacity;opacity:0;&:has( [data-enter] ){opacity:1;}&:has( [data-leave] ){transition-duration:", ANIMATION_PARAMS.DURATION.OUT, ";}&:has( [data-side='bottom'] ),&:has( [data-side='top'] ){transform:scaleY( ", ANIMATION_PARAMS.SCALE_AMOUNT_OUTER, " );}&:has( [data-side='bottom'] ){transform-origin:top;}&:has( [data-side='top'] ){transform-origin:bottom;}&:has( [data-enter][data-side='bottom'] ),&:has( [data-enter][data-side='top'] ),&:has( [data-leave][data-side='bottom'] ),&:has( [data-leave][data-side='top'] ){transform:scaleY( 1 );}}" + ( true ? "" : 0));
const MenuPopoverInnerWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1kdzosf13"
} : 0)("position:relative;z-index:1000000;display:grid;grid-template-columns:", GRID_TEMPLATE_COLS, ";grid-template-rows:auto;box-sizing:border-box;min-width:160px;max-width:320px;max-height:var( --popover-available-height );padding:", CONTENT_WRAPPER_PADDING, ";overscroll-behavior:contain;overflow:auto;outline:2px solid transparent!important;@media not ( prefers-reduced-motion ){transition:inherit;transform-origin:inherit;&[data-side='bottom'],&[data-side='top']{transform:scaleY(\n\t\t\t\tcalc(\n\t\t\t\t\t1 / ", ANIMATION_PARAMS.SCALE_AMOUNT_OUTER, " *\n\t\t\t\t\t\t", ANIMATION_PARAMS.SCALE_AMOUNT_CONTENT, "\n\t\t\t\t)\n\t\t\t);}&[data-enter][data-side='bottom'],&[data-enter][data-side='top'],&[data-leave][data-side='bottom'],&[data-leave][data-side='top']{transform:scaleY( 1 );}}" + ( true ? "" : 0));
const baseItem = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("all:unset;position:relative;min-height:", (0,space/* space */.x)(10), ";box-sizing:border-box;grid-column:1/-1;display:grid;grid-template-columns:", GRID_TEMPLATE_COLS, ";align-items:center;@supports ( grid-template-columns: subgrid ){grid-template-columns:subgrid;}font-size:", (0,font/* font */.g)('default.fontSize'), ";font-family:inherit;font-weight:normal;line-height:20px;color:", colors_values/* COLORS */.l.theme.foreground, ";border-radius:", config_values/* default */.A.radiusSmall, ";padding-block:", ITEM_PADDING_BLOCK, ";padding-inline:", ITEM_PADDING_INLINE, ";scroll-margin:", CONTENT_WRAPPER_PADDING, ";user-select:none;outline:none;&[aria-disabled='true']{color:", colors_values/* COLORS */.l.ui.textDisabled, ";cursor:not-allowed;}&[data-active-item]:not( [data-focus-visible] ):not(\n\t\t\t[aria-disabled='true']\n\t\t){background-color:", colors_values/* COLORS */.l.theme.accent, ";color:", colors_values/* COLORS */.l.white, ";}&[data-focus-visible]{box-shadow:0 0 0 1.5px ", colors_values/* COLORS */.l.theme.accent, ";outline:2px solid transparent;}&:active,&[data-active]{}", MenuPopoverInnerWrapper, ":not(:focus) &:not(:focus)[aria-expanded=\"true\"]{background-color:", LIGHT_BACKGROUND_COLOR, ";color:", colors_values/* COLORS */.l.theme.foreground, ";}svg{fill:currentColor;}" + ( true ? "" : 0),  true ? "" : 0);
const styles_DropdownMenuItem = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(MenuItem,  true ? {
  target: "e1kdzosf12"
} : 0)(baseItem, ";" + ( true ? "" : 0));
const styles_DropdownMenuCheckboxItem = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(MenuItemCheckbox,  true ? {
  target: "e1kdzosf11"
} : 0)(baseItem, ";" + ( true ? "" : 0));
const styles_DropdownMenuRadioItem = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(MenuItemRadio,  true ? {
  target: "e1kdzosf10"
} : 0)(baseItem, ";" + ( true ? "" : 0));
const ItemPrefixWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1kdzosf9"
} : 0)("grid-column:1;", styles_DropdownMenuCheckboxItem, ">&,", styles_DropdownMenuRadioItem, ">&{min-width:", (0,space/* space */.x)(6), ";}", styles_DropdownMenuCheckboxItem, ">&,", styles_DropdownMenuRadioItem, ">&,&:not( :empty ){margin-inline-end:", (0,space/* space */.x)(2), ";}display:flex;align-items:center;justify-content:center;color:", LIGHTER_TEXT_COLOR, ";[data-active-item]:not( [data-focus-visible] )>&,[aria-disabled='true']>&{color:inherit;}" + ( true ? "" : 0));
const DropdownMenuItemContentWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1kdzosf8"
} : 0)("grid-column:2;display:flex;align-items:center;justify-content:space-between;gap:", (0,space/* space */.x)(3), ";pointer-events:none;" + ( true ? "" : 0));
const DropdownMenuItemChildrenWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1kdzosf7"
} : 0)("flex:1;display:inline-flex;flex-direction:column;gap:", (0,space/* space */.x)(1), ";" + ( true ? "" : 0));
const ItemSuffixWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1kdzosf6"
} : 0)("flex:0 1 fit-content;min-width:0;width:fit-content;display:flex;align-items:center;justify-content:center;gap:", (0,space/* space */.x)(3), ";color:", LIGHTER_TEXT_COLOR, ";[data-active-item]:not( [data-focus-visible] ) *:not(", MenuPopoverInnerWrapper, ") &,[aria-disabled='true'] *:not(", MenuPopoverInnerWrapper, ") &{color:inherit;}" + ( true ? "" : 0));
const styles_DropdownMenuGroup = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(MenuGroup,  true ? {
  target: "e1kdzosf5"
} : 0)( true ? {
  name: "49aokf",
  styles: "display:contents"
} : 0);
const DropdownMenuGroupLabel = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(MenuGroupLabel,  true ? {
  target: "e1kdzosf4"
} : 0)("grid-column:1/-1;padding-block-start:", (0,space/* space */.x)(3), ";padding-block-end:", (0,space/* space */.x)(2), ";padding-inline:", ITEM_PADDING_INLINE, ";" + ( true ? "" : 0));
const styles_DropdownMenuSeparator = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(MenuSeparator,  true ? {
  target: "e1kdzosf3"
} : 0)("grid-column:1/-1;border:none;height:", config_values/* default */.A.borderWidth, ";background-color:", props => props.variant === 'toolbar' ? TOOLBAR_VARIANT_BORDER_COLOR : DIVIDER_COLOR, ";margin-block:", (0,space/* space */.x)(2), ";margin-inline:", ITEM_PADDING_INLINE, ";outline:2px solid transparent;" + ( true ? "" : 0));
const SubmenuChevronIcon = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(build_module_icon/* default */.A,  true ? {
  target: "e1kdzosf2"
} : 0)("width:", (0,space/* space */.x)(1.5), ";", (0,rtl/* rtl */.h)({
  transform: `scaleX(1)`
}, {
  transform: `scaleX(-1)`
}), ";" + ( true ? "" : 0));
const styles_DropdownMenuItemLabel = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(truncate_component,  true ? {
  target: "e1kdzosf1"
} : 0)("font-size:", (0,font/* font */.g)('default.fontSize'), ";line-height:20px;color:inherit;" + ( true ? "" : 0));
const styles_DropdownMenuItemHelpText = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(truncate_component,  true ? {
  target: "e1kdzosf0"
} : 0)("font-size:", (0,font/* font */.g)('helpText.fontSize'), ";line-height:16px;color:", LIGHTER_TEXT_COLOR, ";word-break:break-all;[data-active-item]:not( [data-focus-visible] ) *:not( ", MenuPopoverInnerWrapper, " ) &,[aria-disabled='true'] *:not( ", MenuPopoverInnerWrapper, " ) &{color:inherit;}" + ( true ? "" : 0));
//# sourceMappingURL=styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/context.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */

const DropdownMenuContext = (0,react.createContext)(undefined);
//# sourceMappingURL=context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/use-temporary-focus-visible-fix.js
/**
 * WordPress dependencies
 */

function useTemporaryFocusVisibleFix({
  onBlur: onBlurProp
}) {
  const [focusVisible, setFocusVisible] = (0,react.useState)(false);
  return {
    'data-focus-visible': focusVisible || undefined,
    onFocusVisible: () => {
      (0,react_dom.flushSync)(() => setFocusVisible(true));
    },
    onBlur: event => {
      onBlurProp?.(event);
      setFocusVisible(false);
    }
  };
}
//# sourceMappingURL=use-temporary-focus-visible-fix.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/item.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */






const DropdownMenuItem = (0,react.forwardRef)(function DropdownMenuItem({
  prefix,
  suffix,
  children,
  onBlur,
  hideOnClick = true,
  ...props
}, ref) {
  // TODO: Remove when https://github.com/ariakit/ariakit/issues/4083 is fixed
  const focusVisibleFixProps = useTemporaryFocusVisibleFix({
    onBlur
  });
  const dropdownMenuContext = (0,react.useContext)(DropdownMenuContext);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(styles_DropdownMenuItem, {
    ref: ref,
    ...props,
    ...focusVisibleFixProps,
    accessibleWhenDisabled: true,
    hideOnClick: hideOnClick,
    store: dropdownMenuContext?.store,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ItemPrefixWrapper, {
      children: prefix
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(DropdownMenuItemContentWrapper, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(DropdownMenuItemChildrenWrapper, {
        children: children
      }), suffix && /*#__PURE__*/(0,jsx_runtime.jsx)(ItemSuffixWrapper, {
        children: suffix
      })]
    })]
  });
});
//# sourceMappingURL=item.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/RPLYUYNN.js
"use client";




// src/checkbox/checkbox-check.tsx



var RPLYUYNN_TagName = "span";
var checkmark = /* @__PURE__ */ (0,jsx_runtime.jsx)(
  "svg",
  {
    display: "block",
    fill: "none",
    stroke: "currentColor",
    strokeLinecap: "round",
    strokeLinejoin: "round",
    strokeWidth: 1.5,
    viewBox: "0 0 16 16",
    height: "1em",
    width: "1em",
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)("polyline", { points: "4,8 7,12 12,4" })
  }
);
function getChildren(props) {
  if (props.checked) {
    return props.children || checkmark;
  }
  if (typeof props.children === "function") {
    return props.children;
  }
  return null;
}
var useCheckboxCheck = (0,HKOOKEDE/* createHook */.ab)(
  function useCheckboxCheck2(_a) {
    var _b = _a, { store, checked } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store", "checked"]);
    const context = (0,react.useContext)(CheckboxCheckedContext);
    checked = checked != null ? checked : context;
    const children = getChildren({ checked, children: props.children });
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      "aria-hidden": true
    }, props), {
      children,
      style: (0,_3YLGPPWQ/* __spreadValues */.IA)({
        width: "1em",
        height: "1em",
        pointerEvents: "none"
      }, props.style)
    });
    return (0,PBFD2E7P/* removeUndefinedValues */.HR)(props);
  }
);
var CheckboxCheck = (0,HKOOKEDE/* forwardRef */.Rf)(function CheckboxCheck2(props) {
  const htmlProps = useCheckboxCheck(props);
  return (0,HKOOKEDE/* createElement */.n)(RPLYUYNN_TagName, htmlProps);
});



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/menu/menu-item-check.js
"use client";















// src/menu/menu-item-check.tsx

var menu_item_check_TagName = "span";
var useMenuItemCheck = (0,HKOOKEDE/* createHook */.ab)(
  function useMenuItemCheck2(_a) {
    var _b = _a, { store, checked } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store", "checked"]);
    const context = (0,react.useContext)(MenuItemCheckedContext);
    checked = checked != null ? checked : context;
    props = useCheckboxCheck((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), { checked }));
    return props;
  }
);
var MenuItemCheck = (0,HKOOKEDE/* forwardRef */.Rf)(function MenuItemCheck2(props) {
  const htmlProps = useMenuItemCheck(props);
  return (0,HKOOKEDE/* createElement */.n)(menu_item_check_TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/icon/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/checkbox-item.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */






const DropdownMenuCheckboxItem = (0,react.forwardRef)(function DropdownMenuCheckboxItem({
  suffix,
  children,
  onBlur,
  hideOnClick = false,
  ...props
}, ref) {
  // TODO: Remove when https://github.com/ariakit/ariakit/issues/4083 is fixed
  const focusVisibleFixProps = useTemporaryFocusVisibleFix({
    onBlur
  });
  const dropdownMenuContext = (0,react.useContext)(DropdownMenuContext);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(styles_DropdownMenuCheckboxItem, {
    ref: ref,
    ...props,
    ...focusVisibleFixProps,
    accessibleWhenDisabled: true,
    hideOnClick: hideOnClick,
    store: dropdownMenuContext?.store,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(MenuItemCheck, {
      store: dropdownMenuContext?.store,
      render: /*#__PURE__*/(0,jsx_runtime.jsx)(ItemPrefixWrapper, {})
      // Override some ariakit inline styles
      ,
      style: {
        width: 'auto',
        height: 'auto'
      },
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
        icon: library_check,
        size: 24
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(DropdownMenuItemContentWrapper, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(DropdownMenuItemChildrenWrapper, {
        children: children
      }), suffix && /*#__PURE__*/(0,jsx_runtime.jsx)(ItemSuffixWrapper, {
        children: suffix
      })]
    })]
  });
});
//# sourceMappingURL=checkbox-item.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/radio-item.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */







const radio_item_radioCheck = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Circle */.jl, {
    cx: 12,
    cy: 12,
    r: 3
  })
});
const DropdownMenuRadioItem = (0,react.forwardRef)(function DropdownMenuRadioItem({
  suffix,
  children,
  onBlur,
  hideOnClick = false,
  ...props
}, ref) {
  // TODO: Remove when https://github.com/ariakit/ariakit/issues/4083 is fixed
  const focusVisibleFixProps = useTemporaryFocusVisibleFix({
    onBlur
  });
  const dropdownMenuContext = (0,react.useContext)(DropdownMenuContext);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(styles_DropdownMenuRadioItem, {
    ref: ref,
    ...props,
    ...focusVisibleFixProps,
    accessibleWhenDisabled: true,
    hideOnClick: hideOnClick,
    store: dropdownMenuContext?.store,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(MenuItemCheck, {
      store: dropdownMenuContext?.store,
      render: /*#__PURE__*/(0,jsx_runtime.jsx)(ItemPrefixWrapper, {})
      // Override some ariakit inline styles
      ,
      style: {
        width: 'auto',
        height: 'auto'
      },
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
        icon: radio_item_radioCheck,
        size: 24
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(DropdownMenuItemContentWrapper, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(DropdownMenuItemChildrenWrapper, {
        children: children
      }), suffix && /*#__PURE__*/(0,jsx_runtime.jsx)(ItemSuffixWrapper, {
        children: suffix
      })]
    })]
  });
});
//# sourceMappingURL=radio-item.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/group.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */




const DropdownMenuGroup = (0,react.forwardRef)(function DropdownMenuGroup(props, ref) {
  const dropdownMenuContext = (0,react.useContext)(DropdownMenuContext);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(styles_DropdownMenuGroup, {
    ref: ref,
    ...props,
    store: dropdownMenuContext?.store
  });
});
//# sourceMappingURL=group.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/text/component.js
var text_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/text/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/group-label.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */





const group_label_DropdownMenuGroupLabel = (0,react.forwardRef)(function DropdownMenuGroup(props, ref) {
  const dropdownMenuContext = (0,react.useContext)(DropdownMenuContext);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(DropdownMenuGroupLabel, {
    ref: ref,
    render:
    /*#__PURE__*/
    // @ts-expect-error The `children` prop is passed
    (0,jsx_runtime.jsx)(text_component/* default */.A, {
      upperCase: true,
      variant: "muted",
      size: "11px",
      weight: 500,
      lineHeight: "16px"
    }),
    ...props,
    store: dropdownMenuContext?.store
  });
});
//# sourceMappingURL=group-label.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/separator.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */




const DropdownMenuSeparator = (0,react.forwardRef)(function DropdownMenuSeparator(props, ref) {
  const dropdownMenuContext = (0,react.useContext)(DropdownMenuContext);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(styles_DropdownMenuSeparator, {
    ref: ref,
    ...props,
    store: dropdownMenuContext?.store,
    variant: dropdownMenuContext?.variant
  });
});
//# sourceMappingURL=separator.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/item-label.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const DropdownMenuItemLabel = (0,react.forwardRef)(function DropdownMenuItemLabel(props, ref) {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(styles_DropdownMenuItemLabel, {
    numberOfLines: 1,
    ref: ref,
    ...props
  });
});
//# sourceMappingURL=item-label.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/item-help-text.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const DropdownMenuItemHelpText = (0,react.forwardRef)(function DropdownMenuItemHelpText(props, ref) {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(styles_DropdownMenuItemHelpText, {
    numberOfLines: 2,
    ref: ref,
    ...props
  });
});
//# sourceMappingURL=item-help-text.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/dropdown-menu-v2/index.js
/**
 * External dependencies
 */



/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */














const UnconnectedDropdownMenu = (props, ref) => {
  var _props$placement;
  const {
    // Store props
    open,
    defaultOpen = false,
    onOpenChange,
    placement,
    // Menu trigger props
    trigger,
    // Menu props
    gutter,
    children,
    shift,
    modal = true,
    // From internal components context
    variant,
    // Rest
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'DropdownMenu');
  const parentContext = (0,react.useContext)(DropdownMenuContext);
  const computedDirection = (0,build_module/* isRTL */.V8)() ? 'rtl' : 'ltr';

  // If an explicit value for the `placement` prop is not passed,
  // apply a default placement of `bottom-start` for the root dropdown,
  // and of `right-start` for nested dropdowns.
  let computedPlacement = (_props$placement = props.placement) !== null && _props$placement !== void 0 ? _props$placement : parentContext?.store ? 'right-start' : 'bottom-start';
  // Swap left/right in case of RTL direction
  if (computedDirection === 'rtl') {
    if (/right/.test(computedPlacement)) {
      computedPlacement = computedPlacement.replace('right', 'left');
    } else if (/left/.test(computedPlacement)) {
      computedPlacement = computedPlacement.replace('left', 'right');
    }
  }
  const dropdownMenuStore = useMenuStore({
    parent: parentContext?.store,
    open,
    defaultOpen,
    placement: computedPlacement,
    focusLoop: true,
    setOpen(willBeOpen) {
      onOpenChange?.(willBeOpen);
    },
    rtl: computedDirection === 'rtl'
  });
  const contextValue = (0,react.useMemo)(() => ({
    store: dropdownMenuStore,
    variant
  }), [dropdownMenuStore, variant]);

  // Extract the side from the applied placement — useful for animations.
  // Using `currentPlacement` instead of `placement` to make sure that we
  // use the final computed placement (including "flips" etc).
  const appliedPlacementSide = (0,_2GXGCHW6/* useStoreState */.O$)(dropdownMenuStore, 'currentPlacement').split('-')[0];
  if (dropdownMenuStore.parent && !((0,react.isValidElement)(trigger) && DropdownMenuItem === trigger.type)) {
    // eslint-disable-next-line no-console
    console.warn('For nested DropdownMenus, the `trigger` should always be a `DropdownMenuItem`.');
  }
  const hideOnEscape = (0,react.useCallback)(event => {
    // Pressing Escape can cause unexpected consequences (ie. exiting
    // full screen mode on MacOs, close parent modals...).
    event.preventDefault();
    // Returning `true` causes the menu to hide.
    return true;
  }, []);
  const wrapperProps = (0,react.useMemo)(() => ({
    dir: computedDirection,
    style: {
      direction: computedDirection
    }
  }), [computedDirection]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(MenuButton, {
      ref: ref,
      store: dropdownMenuStore,
      render: dropdownMenuStore.parent ? (0,react.cloneElement)(trigger, {
        // Add submenu arrow, unless a `suffix` is explicitly specified
        suffix: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
          children: [trigger.props.suffix, /*#__PURE__*/(0,jsx_runtime.jsx)(SubmenuChevronIcon, {
            "aria-hidden": "true",
            icon: chevron_right_small,
            size: 24,
            preserveAspectRatio: "xMidYMid slice"
          })]
        })
      }) : trigger
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(Menu, {
      ...otherProps,
      modal: modal,
      store: dropdownMenuStore
      // Root menu has an 8px distance from its trigger,
      // otherwise 0 (which causes the submenu to slightly overlap)
      ,
      gutter: gutter !== null && gutter !== void 0 ? gutter : dropdownMenuStore.parent ? 0 : 8
      // Align nested menu by the same (but opposite) amount
      // as the menu container's padding.
      ,
      shift: shift !== null && shift !== void 0 ? shift : dropdownMenuStore.parent ? -4 : 0,
      hideOnHoverOutside: false,
      "data-side": appliedPlacementSide,
      wrapperProps: wrapperProps,
      hideOnEscape: hideOnEscape,
      unmountOnHide: true,
      render: renderProps =>
      /*#__PURE__*/
      // Two wrappers are needed for the entry animation, where the menu
      // container scales with a different factor than its contents.
      // The {...renderProps} are passed to the inner wrapper, so that the
      // menu element is the direct parent of the menu item elements.
      (0,jsx_runtime.jsx)(MenuPopoverOuterWrapper, {
        variant: variant,
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(MenuPopoverInnerWrapper, {
          ...renderProps
        })
      }),
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(DropdownMenuContext.Provider, {
        value: contextValue,
        children: children
      })
    })]
  });
};
const DropdownMenuV2 = Object.assign((0,context_connect/* contextConnect */.KZ)(UnconnectedDropdownMenu, 'DropdownMenu'), {
  Context: Object.assign(DropdownMenuContext, {
    displayName: 'DropdownMenuV2.Context'
  }),
  Item: Object.assign(DropdownMenuItem, {
    displayName: 'DropdownMenuV2.Item'
  }),
  RadioItem: Object.assign(DropdownMenuRadioItem, {
    displayName: 'DropdownMenuV2.RadioItem'
  }),
  CheckboxItem: Object.assign(DropdownMenuCheckboxItem, {
    displayName: 'DropdownMenuV2.CheckboxItem'
  }),
  Group: Object.assign(DropdownMenuGroup, {
    displayName: 'DropdownMenuV2.Group'
  }),
  GroupLabel: Object.assign(group_label_DropdownMenuGroupLabel, {
    displayName: 'DropdownMenuV2.GroupLabel'
  }),
  Separator: Object.assign(DropdownMenuSeparator, {
    displayName: 'DropdownMenuV2.Separator'
  }),
  ItemLabel: Object.assign(DropdownMenuItemLabel, {
    displayName: 'DropdownMenuV2.ItemLabel'
  }),
  ItemHelpText: Object.assign(DropdownMenuItemHelpText, {
    displayName: 'DropdownMenuV2.ItemHelpText'
  })
});
/* harmony default export */ const dropdown_menu_v2 = ((/* unused pure expression or super */ null && (DropdownMenuV2)));
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/context/context-system-provider.js + 1 modules
var context_system_provider = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/context/context-system-provider.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/theme/styles.js

function styles_EMOTION_STRINGIFIED_CSS_ERROR_() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const colorVariables = ({
  colors
}) => {
  const shades = Object.entries(colors.gray || {}).map(([k, v]) => `--wp-components-color-gray-${k}: ${v};`).join('');
  return [/*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("--wp-components-color-accent:", colors.accent, ";--wp-components-color-accent-darker-10:", colors.accentDarker10, ";--wp-components-color-accent-darker-20:", colors.accentDarker20, ";--wp-components-color-accent-inverted:", colors.accentInverted, ";--wp-components-color-background:", colors.background, ";--wp-components-color-foreground:", colors.foreground, ";--wp-components-color-foreground-inverted:", colors.foregroundInverted, ";", shades, ";" + ( true ? "" : 0),  true ? "" : 0)];
};
const Wrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1krjpvb0"
} : 0)( true ? {
  name: "1a3idx0",
  styles: "color:var( --wp-components-color-foreground, currentColor )"
} : 0);
//# sourceMappingURL=styles.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs
var colord = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/a11y.mjs
var a11y = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/a11y.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs
var names = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+warning@3.8.1/node_modules/@wordpress/warning/build-module/index.js + 1 modules
var warning_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+warning@3.8.1/node_modules/@wordpress/warning/build-module/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/theme/color-algorithms.js
/**
 * External dependencies
 */




/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


(0,colord/* extend */.X$)([names/* default */.A, a11y/* default */.A]);
function generateThemeVariables(inputs) {
  validateInputs(inputs);
  const generatedColors = {
    ...generateAccentDependentColors(inputs.accent),
    ...generateBackgroundDependentColors(inputs.background)
  };
  warnContrastIssues(checkContrasts(inputs, generatedColors));
  return {
    colors: generatedColors
  };
}
function validateInputs(inputs) {
  for (const [key, value] of Object.entries(inputs)) {
    if (typeof value !== 'undefined' && !(0,colord/* colord */.Mj)(value).isValid()) {
      globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)(`wp.components.Theme: "${value}" is not a valid color value for the '${key}' prop.`) : void 0;
    }
  }
}
function checkContrasts(inputs, outputs) {
  const background = inputs.background || colors_values/* COLORS */.l.white;
  const accent = inputs.accent || '#3858e9';
  const foreground = outputs.foreground || colors_values/* COLORS */.l.gray[900];
  const gray = outputs.gray || colors_values/* COLORS */.l.gray;
  return {
    accent: (0,colord/* colord */.Mj)(background).isReadable(accent) ? undefined : `The background color ("${background}") does not have sufficient contrast against the accent color ("${accent}").`,
    foreground: (0,colord/* colord */.Mj)(background).isReadable(foreground) ? undefined : `The background color provided ("${background}") does not have sufficient contrast against the standard foreground colors.`,
    grays: (0,colord/* colord */.Mj)(background).contrast(gray[600]) >= 3 && (0,colord/* colord */.Mj)(background).contrast(gray[700]) >= 4.5 ? undefined : `The background color provided ("${background}") cannot generate a set of grayscale foreground colors with sufficient contrast. Try adjusting the color to be lighter or darker.`
  };
}
function warnContrastIssues(issues) {
  for (const error of Object.values(issues)) {
    if (error) {
      globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)('wp.components.Theme: ' + error) : void 0;
    }
  }
}
function generateAccentDependentColors(accent) {
  if (!accent) {
    return {};
  }
  return {
    accent,
    accentDarker10: (0,colord/* colord */.Mj)(accent).darken(0.1).toHex(),
    accentDarker20: (0,colord/* colord */.Mj)(accent).darken(0.2).toHex(),
    accentInverted: getForegroundForColor(accent)
  };
}
function generateBackgroundDependentColors(background) {
  if (!background) {
    return {};
  }
  const foreground = getForegroundForColor(background);
  return {
    background,
    foreground,
    foregroundInverted: getForegroundForColor(foreground),
    gray: generateShades(background, foreground)
  };
}
function getForegroundForColor(color) {
  return (0,colord/* colord */.Mj)(color).isDark() ? colors_values/* COLORS */.l.white : colors_values/* COLORS */.l.gray[900];
}
function generateShades(background, foreground) {
  // How much darkness you need to add to #fff to get the COLORS.gray[n] color
  const SHADES = {
    100: 0.06,
    200: 0.121,
    300: 0.132,
    400: 0.2,
    600: 0.42,
    700: 0.543,
    800: 0.821
  };

  // Darkness of COLORS.gray[ 900 ], relative to #fff
  const limit = 0.884;
  const direction = (0,colord/* colord */.Mj)(background).isDark() ? 'lighten' : 'darken';

  // Lightness delta between the background and foreground colors
  const range = Math.abs((0,colord/* colord */.Mj)(background).toHsl().l - (0,colord/* colord */.Mj)(foreground).toHsl().l) / 100;
  const result = {};
  Object.entries(SHADES).forEach(([key, value]) => {
    result[parseInt(key)] = (0,colord/* colord */.Mj)(background)[direction](value / limit * range).toHex();
  });
  return result;
}
//# sourceMappingURL=color-algorithms.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/theme/index.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */





/**
 * `Theme` allows defining theme variables for components in the `@wordpress/components` package.
 *
 * Multiple `Theme` components can be nested in order to override specific theme variables.
 *
 *
 * ```jsx
 * const Example = () => {
 *   return (
 *     <Theme accent="red">
 *       <Button variant="primary">I'm red</Button>
 *       <Theme accent="blue">
 *         <Button variant="primary">I'm blue</Button>
 *       </Theme>
 *     </Theme>
 *   );
 * };
 * ```
 */

function Theme({
  accent,
  background,
  className,
  ...props
}) {
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => cx(...colorVariables(generateThemeVariables({
    accent,
    background
  })), className), [accent, background, className, cx]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Wrapper, {
    className: classes,
    ...props
  });
}
/* harmony default export */ const theme = (Theme);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/KZ2S4ZC5.js
"use client";




// src/select/select-context.tsx

var KZ2S4ZC5_ctx = (0,HKOOKEDE/* createStoreContext */.B0)(
  [_54MGSIOI/* PopoverContextProvider */.wf, WENSINUV/* CompositeContextProvider */.ws],
  [_54MGSIOI/* PopoverScopedContextProvider */.s1, WENSINUV/* CompositeScopedContextProvider */.aN]
);
var useSelectContext = KZ2S4ZC5_ctx.useContext;
var useSelectScopedContext = KZ2S4ZC5_ctx.useScopedContext;
var useSelectProviderContext = KZ2S4ZC5_ctx.useProviderContext;
var SelectContextProvider = KZ2S4ZC5_ctx.ContextProvider;
var SelectScopedContextProvider = KZ2S4ZC5_ctx.ScopedContextProvider;
var SelectItemCheckedContext = (0,react.createContext)(false);
var SelectHeadingContext = (0,react.createContext)(null);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/6DHTHWXD.js
var _6DHTHWXD = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/__chunks/6DHTHWXD.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.9/node_modules/@ariakit/core/esm/tab/tab-store.js
"use client";








// src/tab/tab-store.ts
function createTabStore(_a = {}) {
  var _b = _a, {
    composite: parentComposite,
    combobox
  } = _b, props = (0,_chunks_3YLGPPWQ/* __objRest */.YG)(_b, [
    "composite",
    "combobox"
  ]);
  const independentKeys = [
    "items",
    "renderedItems",
    "moves",
    "orientation",
    "virtualFocus",
    "includesBaseElement",
    "baseElement",
    "focusLoop",
    "focusShift",
    "focusWrap"
  ];
  const store = (0,EQQLU3CG/* mergeStore */.od)(
    props.store,
    (0,EQQLU3CG/* omit */.cJ)(parentComposite, independentKeys),
    (0,EQQLU3CG/* omit */.cJ)(combobox, independentKeys)
  );
  const syncState = store == null ? void 0 : store.getState();
  const composite = (0,D7EIQZAU/* createCompositeStore */.z)((0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    store,
    // We need to explicitly set the default value of `includesBaseElement` to
    // `false` since we don't want the composite store to default it to `true`
    // when the activeId state is null, which could be the case when rendering
    // combobox with tab.
    includesBaseElement: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.includesBaseElement,
      syncState == null ? void 0 : syncState.includesBaseElement,
      false
    ),
    orientation: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.orientation,
      syncState == null ? void 0 : syncState.orientation,
      "horizontal"
    ),
    focusLoop: (0,PBFD2E7P/* defaultValue */.Jh)(props.focusLoop, syncState == null ? void 0 : syncState.focusLoop, true)
  }));
  const panels = (0,_6DHTHWXD/* createCollectionStore */.I)();
  const initialState = (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, composite.getState()), {
    selectedId: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.selectedId,
      syncState == null ? void 0 : syncState.selectedId,
      props.defaultSelectedId
    ),
    selectOnMove: (0,PBFD2E7P/* defaultValue */.Jh)(
      props.selectOnMove,
      syncState == null ? void 0 : syncState.selectOnMove,
      true
    )
  });
  const tab = (0,EQQLU3CG/* createStore */.y$)(initialState, composite, store);
  (0,EQQLU3CG/* setup */.mj)(
    tab,
    () => (0,EQQLU3CG/* sync */.OH)(tab, ["moves"], () => {
      const { activeId, selectOnMove } = tab.getState();
      if (!selectOnMove) return;
      if (!activeId) return;
      const tabItem = composite.item(activeId);
      if (!tabItem) return;
      if (tabItem.dimmed) return;
      if (tabItem.disabled) return;
      tab.setState("selectedId", tabItem.id);
    })
  );
  (0,EQQLU3CG/* setup */.mj)(
    tab,
    () => (0,EQQLU3CG/* batch */.vA)(tab, ["selectedId"], (state, prev) => {
      if (parentComposite && state.selectedId === prev.selectedId) return;
      tab.setState("activeId", state.selectedId);
    })
  );
  (0,EQQLU3CG/* setup */.mj)(
    tab,
    () => (0,EQQLU3CG/* sync */.OH)(tab, ["selectedId", "renderedItems"], (state) => {
      if (state.selectedId !== void 0) return;
      const { activeId, renderedItems } = tab.getState();
      const tabItem = composite.item(activeId);
      if (tabItem && !tabItem.disabled && !tabItem.dimmed) {
        tab.setState("selectedId", tabItem.id);
      } else {
        const tabItem2 = renderedItems.find(
          (item) => !item.disabled && !item.dimmed
        );
        tab.setState("selectedId", tabItem2 == null ? void 0 : tabItem2.id);
      }
    })
  );
  (0,EQQLU3CG/* setup */.mj)(
    tab,
    () => (0,EQQLU3CG/* sync */.OH)(tab, ["renderedItems"], (state) => {
      const tabs = state.renderedItems;
      if (!tabs.length) return;
      return (0,EQQLU3CG/* sync */.OH)(panels, ["renderedItems"], (state2) => {
        const items = state2.renderedItems;
        const hasOrphanPanels = items.some((panel) => !panel.tabId);
        if (!hasOrphanPanels) return;
        items.forEach((panel, i) => {
          if (panel.tabId) return;
          const tabItem = tabs[i];
          if (!tabItem) return;
          panels.renderItem((0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, panel), { tabId: tabItem.id }));
        });
      });
    })
  );
  let selectedIdFromSelectedValue = null;
  (0,EQQLU3CG/* setup */.mj)(tab, () => {
    const backupSelectedId = () => {
      selectedIdFromSelectedValue = tab.getState().selectedId;
    };
    const restoreSelectedId = () => {
      tab.setState("selectedId", selectedIdFromSelectedValue);
    };
    if (parentComposite && "setSelectElement" in parentComposite) {
      return (0,PBFD2E7P/* chain */.cy)(
        (0,EQQLU3CG/* sync */.OH)(parentComposite, ["value"], backupSelectedId),
        (0,EQQLU3CG/* sync */.OH)(parentComposite, ["open"], restoreSelectedId)
      );
    }
    if (!combobox) return;
    return (0,PBFD2E7P/* chain */.cy)(
      (0,EQQLU3CG/* sync */.OH)(combobox, ["selectedValue"], backupSelectedId),
      (0,EQQLU3CG/* sync */.OH)(combobox, ["open"], restoreSelectedId)
    );
  });
  return (0,_chunks_3YLGPPWQ/* __spreadProps */.ko)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)((0,_chunks_3YLGPPWQ/* __spreadValues */.IA)({}, composite), tab), {
    panels,
    setSelectedId: (id) => tab.setState("selectedId", id),
    select: (id) => {
      tab.setState("selectedId", id);
      composite.move(id);
    }
  });
}


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/JZUY7XL6.js
"use client";







// src/tab/tab-store.ts


function useTabStoreProps(store, update, props) {
  (0,Z32BISHQ/* useUpdateEffect */.w5)(update, [props.composite, props.combobox]);
  store = (0,UVQLZ7T5/* useCompositeStoreProps */.Y)(store, update, props);
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "selectedId", "setSelectedId");
  (0,_2GXGCHW6/* useStoreProps */.Tz)(store, props, "selectOnMove");
  const [panels, updatePanels] = (0,_2GXGCHW6/* useStore */.Pj)(() => store.panels, {});
  (0,Z32BISHQ/* useUpdateEffect */.w5)(updatePanels, [store, updatePanels]);
  return Object.assign(
    (0,react.useMemo)(() => (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, store), { panels }), [store, panels]),
    { composite: props.composite, combobox: props.combobox }
  );
}
function useTabStore(props = {}) {
  const combobox = useComboboxContext();
  const composite = useSelectContext() || combobox;
  props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
    composite: props.composite !== void 0 ? props.composite : composite,
    combobox: props.combobox !== void 0 ? props.combobox : combobox
  });
  const [store, update] = (0,_2GXGCHW6/* useStore */.Pj)(createTabStore, props);
  return useTabStoreProps(store, update, props);
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tabs/context.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */

const TabsContext = (0,react.createContext)(undefined);
const useTabsContext = () => (0,react.useContext)(TabsContext);
//# sourceMappingURL=context.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/TNITL632.js
"use client";



// src/tab/tab-context.tsx
var TNITL632_ctx = (0,HKOOKEDE/* createStoreContext */.B0)(
  [WENSINUV/* CompositeContextProvider */.ws],
  [WENSINUV/* CompositeScopedContextProvider */.aN]
);
var useTabContext = TNITL632_ctx.useContext;
var useTabScopedContext = TNITL632_ctx.useScopedContext;
var useTabProviderContext = TNITL632_ctx.useProviderContext;
var TabContextProvider = TNITL632_ctx.ContextProvider;
var TabScopedContextProvider = TNITL632_ctx.ScopedContextProvider;



;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/tab/tab.js
"use client";















// src/tab/tab.tsx



var tab_TagName = "button";
var useTab = (0,HKOOKEDE/* createHook */.ab)(function useTab2(_a) {
  var _b = _a, {
    store,
    getItem: getItemProp
  } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
    "store",
    "getItem"
  ]);
  var _a2;
  const context = useTabScopedContext();
  store = store || context;
  (0,PBFD2E7P/* invariant */.V1)(
    store,
     false && 0
  );
  const defaultId = (0,Z32BISHQ/* useId */.Bi)();
  const id = props.id || defaultId;
  const dimmed = (0,PBFD2E7P/* disabledFromProps */.$f)(props);
  const getItem = (0,react.useCallback)(
    (item) => {
      const nextItem = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, item), { dimmed });
      if (getItemProp) {
        return getItemProp(nextItem);
      }
      return nextItem;
    },
    [dimmed, getItemProp]
  );
  const onClickProp = props.onClick;
  const onClick = (0,Z32BISHQ/* useEvent */._q)((event) => {
    onClickProp == null ? void 0 : onClickProp(event);
    if (event.defaultPrevented) return;
    store == null ? void 0 : store.setSelectedId(id);
  });
  const panelId = store.panels.useState(
    (state) => {
      var _a3;
      return (_a3 = state.items.find((item) => item.tabId === id)) == null ? void 0 : _a3.id;
    }
  );
  const shouldRegisterItem = defaultId ? props.shouldRegisterItem : false;
  const isActive = store.useState((state) => !!id && state.activeId === id);
  const selected = store.useState((state) => !!id && state.selectedId === id);
  const hasActiveItem = store.useState((state) => !!store.item(state.activeId));
  const canRegisterComposedItem = isActive || selected && !hasActiveItem;
  const accessibleWhenDisabled = selected || ((_a2 = props.accessibleWhenDisabled) != null ? _a2 : true);
  const isWithinVirtualFocusComposite = (0,_2GXGCHW6/* useStoreState */.O$)(
    store.combobox || store.composite,
    "virtualFocus"
  );
  if (isWithinVirtualFocusComposite) {
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
      tabIndex: -1
    });
  }
  props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
    id,
    role: "tab",
    "aria-selected": selected,
    "aria-controls": panelId || void 0
  }, props), {
    onClick
  });
  if (store.composite) {
    const defaultProps = {
      id,
      accessibleWhenDisabled,
      store: store.composite,
      shouldRegisterItem: canRegisterComposedItem && shouldRegisterItem,
      render: props.render
    };
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, props), {
      render: /* @__PURE__ */ (0,jsx_runtime.jsx)(
        _3CCTMYB6/* CompositeItem */.l,
        (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, defaultProps), {
          render: store.combobox && store.composite !== store.combobox ? /* @__PURE__ */ (0,jsx_runtime.jsx)(_3CCTMYB6/* CompositeItem */.l, (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, defaultProps), { store: store.combobox })) : defaultProps.render
        })
      )
    });
  }
  props = (0,_3CCTMYB6/* useCompositeItem */.k)((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
    store
  }, props), {
    accessibleWhenDisabled,
    getItem,
    shouldRegisterItem
  }));
  return props;
});
var Tab = (0,HKOOKEDE/* memo */.ph)(
  (0,HKOOKEDE/* forwardRef */.Rf)(function Tab2(props) {
    const htmlProps = useTab(props);
    return (0,HKOOKEDE/* createElement */.n)(tab_TagName, htmlProps);
  })
);


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/PLQDTVXM.js
var PLQDTVXM = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/PLQDTVXM.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/HGZKAGPL.js
var HGZKAGPL = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/HGZKAGPL.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/KGK2TTFO.js
var KGK2TTFO = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/__chunks/KGK2TTFO.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/tab/tab-panel.js
"use client";
















// src/tab/tab-panel.tsx





var tab_panel_TagName = "div";
var useTabPanel = (0,HKOOKEDE/* createHook */.ab)(
  function useTabPanel2(_a) {
    var _b = _a, {
      store,
      unmountOnHide,
      tabId: tabIdProp,
      getItem: getItemProp
    } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, [
      "store",
      "unmountOnHide",
      "tabId",
      "getItem"
    ]);
    const context = useTabProviderContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const id = (0,Z32BISHQ/* useId */.Bi)(props.id);
    const [hasTabbableChildren, setHasTabbableChildren] = (0,react.useState)(false);
    (0,react.useEffect)(() => {
      const element = ref.current;
      if (!element) return;
      const tabbable = (0,utils_focus/* getAllTabbableIn */.a9)(element);
      setHasTabbableChildren(!!tabbable.length);
    }, []);
    const getItem = (0,react.useCallback)(
      (item) => {
        const nextItem = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, item), { id: id || item.id, tabId: tabIdProp });
        if (getItemProp) {
          return getItemProp(nextItem);
        }
        return nextItem;
      },
      [id, tabIdProp, getItemProp]
    );
    const onKeyDownProp = props.onKeyDown;
    const onKeyDown = (0,Z32BISHQ/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (event.defaultPrevented) return;
      if (!(store == null ? void 0 : store.composite)) return;
      const state = store.getState();
      const tab = createTabStore((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({}, state), { activeId: state.selectedId }));
      tab.setState("renderedItems", state.renderedItems);
      const keyMap = {
        ArrowLeft: tab.previous,
        ArrowRight: tab.next,
        Home: tab.first,
        End: tab.last
      };
      const action = keyMap[event.key];
      if (!action) return;
      const nextId = action();
      if (!nextId) return;
      event.preventDefault();
      store.move(nextId);
    });
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, { value: store, children: element }),
      [store]
    );
    const tabId = store.panels.useState(
      () => {
        var _a2;
        return tabIdProp || ((_a2 = store == null ? void 0 : store.panels.item(id)) == null ? void 0 : _a2.tabId);
      }
    );
    const open = store.useState(
      (state) => !!tabId && state.selectedId === tabId
    );
    const disclosure = (0,KGK2TTFO/* useDisclosureStore */.E)({ open });
    const mounted = disclosure.useState("mounted");
    props = (0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      id,
      role: "tabpanel",
      "aria-labelledby": tabId || void 0
    }, props), {
      children: unmountOnHide && !mounted ? null : props.children,
      ref: (0,Z32BISHQ/* useMergeRefs */.SV)(ref, props.ref),
      onKeyDown
    });
    props = (0,HGZKAGPL/* useFocusable */.W)((0,_3YLGPPWQ/* __spreadValues */.IA)({
      // If the tab panel is rendered as part of another composite widget such
      // as combobox, it should not be focusable.
      focusable: !store.composite && !hasTabbableChildren
    }, props));
    props = (0,BSEL4YAF/* useDisclosureContent */.aT)((0,_3YLGPPWQ/* __spreadValues */.IA)({ store: disclosure }, props));
    props = (0,PLQDTVXM/* useCollectionItem */.v)((0,_3YLGPPWQ/* __spreadProps */.ko)((0,_3YLGPPWQ/* __spreadValues */.IA)({ store: store.panels }, props), { getItem }));
    return props;
  }
);
var TabPanel = (0,HKOOKEDE/* forwardRef */.Rf)(function TabPanel2(props) {
  const htmlProps = useTabPanel(props);
  return (0,HKOOKEDE/* createElement */.n)(tab_panel_TagName, htmlProps);
});


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tabs/styles.js

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const TabListWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "enfox0g2"
} : 0)("position:relative;display:flex;align-items:stretch;flex-direction:row;text-align:center;&[aria-orientation='vertical']{flex-direction:column;text-align:start;}@media not ( prefers-reduced-motion ){&.is-animation-enabled::after{transition-property:transform;transition-duration:0.2s;transition-timing-function:ease-out;}}--direction-factor:1;--direction-origin-x:left;--indicator-start:var( --indicator-left );&:dir( rtl ){--direction-factor:-1;--direction-origin-x:right;--indicator-start:var( --indicator-right );}&::after{content:'';position:absolute;pointer-events:none;transform-origin:var( --direction-origin-x ) top;outline:2px solid transparent;outline-offset:-1px;}--antialiasing-factor:100;&:not( [aria-orientation='vertical'] ){&::after{bottom:0;height:0;width:calc( var( --antialiasing-factor ) * 1px );transform:translateX(\n\t\t\t\t\tcalc(\n\t\t\t\t\t\tvar( --indicator-start ) * var( --direction-factor ) *\n\t\t\t\t\t\t\t1px\n\t\t\t\t\t)\n\t\t\t\t) scaleX(\n\t\t\t\t\tcalc(\n\t\t\t\t\t\tvar( --indicator-width ) / var( --antialiasing-factor )\n\t\t\t\t\t)\n\t\t\t\t);border-bottom:var( --wp-admin-border-width-focus ) solid ", colors_values/* COLORS */.l.theme.accent, ";}}&[aria-orientation='vertical']::after{z-index:-1;top:0;left:0;width:100%;height:calc( var( --antialiasing-factor ) * 1px );transform:translateY( calc( var( --indicator-top ) * 1px ) ) scaleY(\n\t\t\t\tcalc( var( --indicator-height ) / var( --antialiasing-factor ) )\n\t\t\t);background-color:", colors_values/* COLORS */.l.theme.gray[100], ";}" + ( true ? "" : 0));
const styles_Tab = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(Tab,  true ? {
  target: "enfox0g1"
} : 0)("&{display:inline-flex;align-items:center;position:relative;border-radius:0;min-height:", (0,space/* space */.x)(12), ";height:auto;background:transparent;border:none;box-shadow:none;cursor:pointer;line-height:1.2;padding:", (0,space/* space */.x)(4), ";margin-left:0;font-weight:500;text-align:inherit;hyphens:auto;color:", colors_values/* COLORS */.l.theme.foreground, ";&[aria-disabled='true']{cursor:default;color:", colors_values/* COLORS */.l.ui.textDisabled, ";}&:not( [aria-disabled='true'] ):hover{color:", colors_values/* COLORS */.l.theme.accent, ";}&:focus:not( :disabled ){position:relative;box-shadow:none;outline:none;}&::before{content:'';position:absolute;top:", (0,space/* space */.x)(3), ";right:", (0,space/* space */.x)(3), ";bottom:", (0,space/* space */.x)(3), ";left:", (0,space/* space */.x)(3), ";pointer-events:none;outline:var( --wp-admin-border-width-focus ) solid ", colors_values/* COLORS */.l.theme.accent, ";border-radius:", config_values/* default */.A.radiusSmall, ";opacity:0;@media not ( prefers-reduced-motion ){transition:opacity 0.1s linear;}}&:focus-visible::before{opacity:1;}}[aria-orientation='vertical'] &{min-height:", (0,space/* space */.x)(10), ";}" + ( true ? "" : 0));
const styles_TabPanel = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(TabPanel,  true ? {
  target: "enfox0g0"
} : 0)("&:focus{box-shadow:none;outline:none;}&:focus-visible{box-shadow:0 0 0 var( --wp-admin-border-width-focus ) ", colors_values/* COLORS */.l.theme.accent, ";outline:2px solid transparent;outline-offset:0;}" + ( true ? "" : 0));
//# sourceMappingURL=styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tabs/tab.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */





const tab_Tab = (0,react.forwardRef)(function Tab({
  children,
  tabId,
  disabled,
  render,
  ...otherProps
}, ref) {
  const context = useTabsContext();
  if (!context) {
    globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)('`Tabs.Tab` must be wrapped in a `Tabs` component.') : void 0;
    return null;
  }
  const {
    store,
    instanceId
  } = context;
  const instancedTabId = `${instanceId}-${tabId}`;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(styles_Tab, {
    ref: ref,
    store: store,
    id: instancedTabId,
    disabled: disabled,
    render: render,
    ...otherProps,
    children: children
  });
});
//# sourceMappingURL=tab.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.10_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@ariakit/react-core/esm/tab/tab-list.js
"use client";












// src/tab/tab-list.tsx


var tab_list_TagName = "div";
var useTabList = (0,HKOOKEDE/* createHook */.ab)(
  function useTabList2(_a) {
    var _b = _a, { store } = _b, props = (0,_3YLGPPWQ/* __objRest */.YG)(_b, ["store"]);
    const context = useTabProviderContext();
    store = store || context;
    (0,PBFD2E7P/* invariant */.V1)(
      store,
       false && 0
    );
    const orientation = store.useState(
      (state) => state.orientation === "both" ? void 0 : state.orientation
    );
    props = (0,Z32BISHQ/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, { value: store, children: element }),
      [store]
    );
    if (store.composite) {
      props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
        focusable: false
      }, props);
    }
    props = (0,_3YLGPPWQ/* __spreadValues */.IA)({
      role: "tablist",
      "aria-orientation": orientation
    }, props);
    props = (0,TW35PKTK/* useComposite */.T)((0,_3YLGPPWQ/* __spreadValues */.IA)({ store }, props));
    return props;
  }
);
var tab_list_TabList = (0,HKOOKEDE/* forwardRef */.Rf)(function TabList2(props) {
  const htmlProps = useTabList(props);
  return (0,HKOOKEDE/* createElement */.n)(tab_list_TagName, htmlProps);
});


;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-event/index.js
/**
 * WordPress dependencies
 */


/**
 * Any function.
 */

/**
 * Creates a stable callback function that has access to the latest state and
 * can be used within event handlers and effect callbacks. Throws when used in
 * the render phase.
 *
 * @param callback The callback function to wrap.
 *
 * @example
 *
 * ```tsx
 * function Component( props ) {
 *   const onClick = useEvent( props.onClick );
 *   useEffect( () => {
 *     onClick();
 *     // Won't trigger the effect again when props.onClick is updated.
 *   }, [ onClick ] );
 *   // Won't re-render Button when props.onClick is updated (if `Button` is
 *   // wrapped in `React.memo`).
 *   return <Button onClick={ onClick } />;
 * }
 * ```
 */
function useEvent(
/**
 * The callback function to wrap.
 */
callback) {
  const ref = (0,react.useRef)(() => {
    throw new Error('Callbacks created with `useEvent` cannot be called during rendering.');
  });
  (0,react.useInsertionEffect)(() => {
    ref.current = callback;
  });
  return (0,react.useCallback)((...args) => ref.current?.(...args), []);
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-resize-observer/_legacy/index.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


// We're only using the first element of the size sequences, until future versions of the spec solidify on how
// exactly it'll be used for fragments in multi-column scenarios:
// From the spec:
// > The box size properties are exposed as FrozenArray in order to support elements that have multiple fragments,
// > which occur in multi-column scenarios. However the current definitions of content rect and border box do not
// > mention how those boxes are affected by multi-column layout. In this spec, there will only be a single
// > ResizeObserverSize returned in the FrozenArray, which will correspond to the dimensions of the first column.
// > A future version of this spec will extend the returned FrozenArray to contain the per-fragment size information.
// (https://drafts.csswg.org/resize-observer/#resize-observer-entry-interface)
//
// Also, testing these new box options revealed that in both Chrome and FF everything is returned in the callback,
// regardless of the "box" option.
// The spec states the following on this:
// > This does not have any impact on which box dimensions are returned to the defined callback when the event
// > is fired, it solely defines which box the author wishes to observe layout changes on.
// (https://drafts.csswg.org/resize-observer/#resize-observer-interface)
// I'm not exactly clear on what this means, especially when you consider a later section stating the following:
// > This section is non-normative. An author may desire to observe more than one CSS box.
// > In this case, author will need to use multiple ResizeObservers.
// (https://drafts.csswg.org/resize-observer/#resize-observer-interface)
// Which is clearly not how current browser implementations behave, and seems to contradict the previous quote.
// For this reason I decided to only return the requested size,
// even though it seems we have access to results for all box types.
// This also means that we get to keep the current api, being able to return a simple { width, height } pair,
// regardless of box option.
const extractSize = entry => {
  let entrySize;
  if (!entry.contentBoxSize) {
    // The dimensions in `contentBoxSize` and `contentRect` are equivalent according to the spec.
    // See the 6th step in the description for the RO algorithm:
    // https://drafts.csswg.org/resize-observer/#create-and-populate-resizeobserverentry-h
    // > Set this.contentRect to logical this.contentBoxSize given target and observedBox of "content-box".
    // In real browser implementations of course these objects differ, but the width/height values should be equivalent.
    entrySize = [entry.contentRect.width, entry.contentRect.height];
  } else if (entry.contentBoxSize[0]) {
    const contentBoxSize = entry.contentBoxSize[0];
    entrySize = [contentBoxSize.inlineSize, contentBoxSize.blockSize];
  } else {
    // TS complains about this, because the RO entry type follows the spec and does not reflect Firefox's buggy
    // behaviour of returning objects instead of arrays for `borderBoxSize` and `contentBoxSize`.
    const contentBoxSize = entry.contentBoxSize;
    entrySize = [contentBoxSize.inlineSize, contentBoxSize.blockSize];
  }
  const [width, height] = entrySize.map(d => Math.round(d));
  return {
    width,
    height
  };
};
const RESIZE_ELEMENT_STYLES = {
  position: 'absolute',
  top: 0,
  left: 0,
  right: 0,
  bottom: 0,
  pointerEvents: 'none',
  opacity: 0,
  overflow: 'hidden',
  zIndex: -1
};
function ResizeElement({
  onResize
}) {
  const resizeElementRef = useResizeObserver(entries => {
    const newSize = extractSize(entries.at(-1)); // Entries are never empty.
    onResize(newSize);
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    ref: resizeElementRef,
    style: RESIZE_ELEMENT_STYLES,
    "aria-hidden": "true"
  });
}
function sizeEquals(a, b) {
  return a.width === b.width && a.height === b.height;
}
const NULL_SIZE = {
  width: null,
  height: null
};

/**
 * Hook which allows to listen to the resize event of any target element when it changes size.
 * _Note: `useResizeObserver` will report `null` sizes until after first render.
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
 */
function useLegacyResizeObserver() {
  const [size, setSize] = (0,react.useState)(NULL_SIZE);

  // Using a ref to track the previous width / height to avoid unnecessary renders.
  const previousSizeRef = (0,react.useRef)(NULL_SIZE);
  const handleResize = (0,react.useCallback)(newSize => {
    if (!sizeEquals(previousSizeRef.current, newSize)) {
      previousSizeRef.current = newSize;
      setSize(newSize);
    }
  }, []);
  const resizeElement = /*#__PURE__*/(0,jsx_runtime.jsx)(ResizeElement, {
    onResize: handleResize
  });
  return [resizeElement, size];
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-resize-observer/index.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


/**
 * External dependencies
 */

// This is the current implementation of `useResizeObserver`.
//
// The legacy implementation is still supported for backwards compatibility.
// This is achieved by overloading the exported function with both signatures,
// and detecting which API is being used at runtime.
function _useResizeObserver(callback, resizeObserverOptions = {}) {
  const callbackEvent = useEvent(callback);
  const observedElementRef = (0,react.useRef)();
  const resizeObserverRef = (0,react.useRef)();
  return useEvent(element => {
    var _resizeObserverRef$cu;
    if (element === observedElementRef.current) {
      return;
    }
    observedElementRef.current = element;

    // Set up `ResizeObserver`.
    (_resizeObserverRef$cu = resizeObserverRef.current) !== null && _resizeObserverRef$cu !== void 0 ? _resizeObserverRef$cu : resizeObserverRef.current = new ResizeObserver(callbackEvent);
    const {
      current: resizeObserver
    } = resizeObserverRef;

    // Unobserve previous element.
    if (observedElementRef.current) {
      resizeObserver.unobserve(observedElementRef.current);
    }

    // Observe new element.
    if (element) {
      resizeObserver.observe(element, resizeObserverOptions);
    }
  });
}

/**
 * Sets up a [`ResizeObserver`](https://developer.mozilla.org/en-US/docs/Web/API/Resize_Observer_API)
 * for an HTML or SVG element.
 *
 * Pass the returned setter as a callback ref to the React element you want
 * to observe, or use it in layout effects for advanced use cases.
 *
 * @example
 *
 * ```tsx
 * const setElement = useResizeObserver(
 * 	( resizeObserverEntries ) => console.log( resizeObserverEntries ),
 * 	{ box: 'border-box' }
 * );
 * <div ref={ setElement } />;
 *
 * // The setter can be used in other ways, for example:
 * useLayoutEffect( () => {
 * 	setElement( document.querySelector( `data-element-id="${ elementId }"` ) );
 * }, [ elementId ] );
 * ```
 *
 * @param callback The `ResizeObserver` callback - [MDN docs](https://developer.mozilla.org/en-US/docs/Web/API/ResizeObserver/ResizeObserver#callback).
 * @param options  Options passed to `ResizeObserver.observe` when called - [MDN docs](https://developer.mozilla.org/en-US/docs/Web/API/ResizeObserver/observe#options). Changes will be ignored.
 */

/**
 * **This is a legacy API and should not be used.**
 *
 * @deprecated Use the other `useResizeObserver` API instead: `const ref = useResizeObserver( ( entries ) => { ... } )`.
 *
 * Hook which allows to listen to the resize event of any target element when it changes size.
 * _Note: `useResizeObserver` will report `null` sizes until after first render.
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
 */

function useResizeObserver(callback, options = {}) {
  return callback ? _useResizeObserver(callback, options) : useLegacyResizeObserver();
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/hooks/use-event.js
/* eslint-disable jsdoc/require-param */
/**
 * WordPress dependencies
 */


/**
 * Any function.
 */

/**
 * Creates a stable callback function that has access to the latest state and
 * can be used within event handlers and effect callbacks. Throws when used in
 * the render phase.
 *
 * @example
 *
 * ```tsx
 * function Component(props) {
 *   const onClick = useEvent(props.onClick);
 *   React.useEffect(() => {}, [onClick]);
 * }
 * ```
 */
function use_event_useEvent(callback) {
  const ref = (0,react.useRef)(() => {
    throw new Error('Cannot call an event handler while rendering.');
  });
  (0,react.useInsertionEffect)(() => {
    ref.current = callback;
  });
  return (0,react.useCallback)((...args) => ref.current?.(...args), []);
}
/* eslint-enable jsdoc/require-param */
//# sourceMappingURL=use-event.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/element-rect.js
/* eslint-disable jsdoc/require-param */
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * The position and dimensions of an element, relative to its offset parent.
 */

/**
 * An `ElementOffsetRect` object with all values set to zero.
 */
const NULL_ELEMENT_OFFSET_RECT = {
  top: 0,
  right: 0,
  bottom: 0,
  left: 0,
  width: 0,
  height: 0
};

/**
 * Returns the position and dimensions of an element, relative to its offset
 * parent, with subpixel precision. Values reflect the real measures before any
 * potential scaling distortions along the X and Y axes.
 *
 * Useful in contexts where plain `getBoundingClientRect` calls or `ResizeObserver`
 * entries are not suitable, such as when the element is transformed, and when
 * `element.offset<Top|Left|Width|Height>` methods are not precise enough.
 *
 * **Note:** in some contexts, like when the scale is 0, this method will fail
 * because it's impossible to calculate a scaling ratio. When that happens, it
 * will return `undefined`.
 */
function getElementOffsetRect(element) {
  var _element$offsetParent;
  // Position and dimension values computed with `getBoundingClientRect` have
  // subpixel precision, but are affected by distortions since they represent
  // the "real" measures, or in other words, the actual final values as rendered
  // by the browser.
  const rect = element.getBoundingClientRect();
  if (rect.width === 0 || rect.height === 0) {
    return;
  }
  const offsetParentRect = (_element$offsetParent = element.offsetParent?.getBoundingClientRect()) !== null && _element$offsetParent !== void 0 ? _element$offsetParent : NULL_ELEMENT_OFFSET_RECT;

  // Computed widths and heights have subpixel precision, and are not affected
  // by distortions.
  const computedWidth = parseFloat(getComputedStyle(element).width);
  const computedHeight = parseFloat(getComputedStyle(element).height);

  // We can obtain the current scale factor for the element by comparing "computed"
  // dimensions with the "real" ones.
  const scaleX = computedWidth / rect.width;
  const scaleY = computedHeight / rect.height;
  return {
    // To obtain the adjusted values for the position:
    // 1. Compute the element's position relative to the offset parent.
    // 2. Correct for the scale factor.
    top: (rect.top - offsetParentRect?.top) * scaleY,
    right: (offsetParentRect?.right - rect.right) * scaleX,
    bottom: (offsetParentRect?.bottom - rect.bottom) * scaleY,
    left: (rect.left - offsetParentRect?.left) * scaleX,
    // Computed dimensions don't need any adjustments.
    width: computedWidth,
    height: computedHeight
  };
}
const POLL_RATE = 100;

/**
 * Tracks the position and dimensions of an element, relative to its offset
 * parent. The element can be changed dynamically.
 *
 * **Note:** sometimes, the measurement will fail (see `getElementOffsetRect`'s
 * documentation for more details). When that happens, this hook will attempt
 * to measure again after a frame, and if that fails, it will poll every 100
 * milliseconds until it succeeds.
 */
function useTrackElementOffsetRect(targetElement) {
  const [indicatorPosition, setIndicatorPosition] = (0,react.useState)(NULL_ELEMENT_OFFSET_RECT);
  const intervalRef = (0,react.useRef)();
  const measure = use_event_useEvent(() => {
    if (targetElement) {
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
  const setElement = useResizeObserver(() => {
    if (!measure()) {
      requestAnimationFrame(() => {
        if (!measure()) {
          intervalRef.current = setInterval(measure, POLL_RATE);
        }
      });
    }
  });
  (0,react.useLayoutEffect)(() => setElement(targetElement), [setElement, targetElement]);
  return indicatorPosition;
}

/* eslint-enable jsdoc/require-param */
//# sourceMappingURL=element-rect.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/hooks/use-on-value-update.js
/* eslint-disable jsdoc/require-param */
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


/**
 * Context object for the `onUpdate` callback of `useOnValueUpdate`.
 */

/**
 * Calls the `onUpdate` callback when the `value` changes.
 */
function useOnValueUpdate(
/**
 * The value to watch for changes.
 */
value,
/**
 * Callback to fire when the value changes.
 */
onUpdate) {
  const previousValueRef = (0,react.useRef)(value);
  const updateCallbackEvent = use_event_useEvent(onUpdate);
  (0,react.useEffect)(() => {
    if (previousValueRef.current !== value) {
      updateCallbackEvent({
        previousValue: previousValueRef.current
      });
      previousValueRef.current = value;
    }
  }, [updateCallbackEvent, value]);
}
/* eslint-enable jsdoc/require-param */
//# sourceMappingURL=use-on-value-update.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tabs/tablist.js
/**
 * External dependencies
 */



/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */







const TabList = (0,react.forwardRef)(function TabList({
  children,
  ...otherProps
}, ref) {
  const context = useTabsContext();
  const tabStoreState = (0,_2GXGCHW6/* useStoreState */.O$)(context?.store);
  const selectedId = tabStoreState?.selectedId;
  const indicatorPosition = useTrackElementOffsetRect(context?.store.item(selectedId)?.element);
  const [animationEnabled, setAnimationEnabled] = (0,react.useState)(false);
  useOnValueUpdate(selectedId, ({
    previousValue
  }) => previousValue && setAnimationEnabled(true));
  if (!context || !tabStoreState) {
    globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)('`Tabs.TabList` must be wrapped in a `Tabs` component.') : void 0;
    return null;
  }
  const {
    store
  } = context;
  const {
    activeId,
    selectOnMove
  } = tabStoreState;
  const {
    setActiveId
  } = store;
  const onBlur = () => {
    if (!selectOnMove) {
      return;
    }

    // When automatic tab selection is on, make sure that the active tab is up
    // to date with the selected tab when leaving the tablist. This makes sure
    // that the selected tab will receive keyboard focus when tabbing back into
    // the tablist.
    if (selectedId !== activeId) {
      setActiveId(selectedId);
    }
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(tab_list_TabList, {
    ref: ref,
    store: store,
    render: /*#__PURE__*/(0,jsx_runtime.jsx)(TabListWrapper, {
      onTransitionEnd: event => {
        if (event.pseudoElement === '::after') {
          setAnimationEnabled(false);
        }
      }
    }),
    onBlur: onBlur,
    ...otherProps,
    style: {
      '--indicator-top': indicatorPosition.top,
      '--indicator-right': indicatorPosition.right,
      '--indicator-left': indicatorPosition.left,
      '--indicator-width': indicatorPosition.width,
      '--indicator-height': indicatorPosition.height,
      ...otherProps.style
    },
    className: (0,clsx/* default */.A)(animationEnabled ? 'is-animation-enabled' : '', otherProps.className),
    children: children
  });
});
//# sourceMappingURL=tablist.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tabs/tabpanel.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */





const tabpanel_TabPanel = (0,react.forwardRef)(function TabPanel({
  children,
  tabId,
  focusable = true,
  ...otherProps
}, ref) {
  const context = useTabsContext();
  const selectedId = (0,_2GXGCHW6/* useStoreState */.O$)(context?.store, 'selectedId');
  if (!context) {
    globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)('`Tabs.TabPanel` must be wrapped in a `Tabs` component.') : void 0;
    return null;
  }
  const {
    store,
    instanceId
  } = context;
  const instancedTabId = `${instanceId}-${tabId}`;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(styles_TabPanel, {
    ref: ref,
    store: store
    // For TabPanel, the id passed here is the id attribute of the DOM
    // element.
    // `tabId` is the id of the tab that controls this panel.
    ,
    id: `${instancedTabId}-view`,
    tabId: instancedTabId,
    focusable: focusable,
    ...otherProps,
    children: selectedId === instancedTabId && children
  });
});
//# sourceMappingURL=tabpanel.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/tabs/index.js
/**
 * External dependencies
 */



/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */






function Tabs({
  selectOnMove = true,
  defaultTabId,
  orientation = 'horizontal',
  onSelect,
  children,
  selectedTabId
}) {
  const instanceId = (0,use_instance_id/* default */.A)(Tabs, 'tabs');
  const store = useTabStore({
    selectOnMove,
    orientation,
    defaultSelectedId: defaultTabId && `${instanceId}-${defaultTabId}`,
    setSelectedId: selectedId => {
      const strippedDownId = typeof selectedId === 'string' ? selectedId.replace(`${instanceId}-`, '') : selectedId;
      onSelect?.(strippedDownId);
    },
    selectedId: selectedTabId && `${instanceId}-${selectedTabId}`
  });
  const isControlled = selectedTabId !== undefined;
  const {
    items,
    selectedId,
    activeId
  } = (0,_2GXGCHW6/* useStoreState */.O$)(store);
  const {
    setSelectedId,
    setActiveId
  } = store;

  // Keep track of whether tabs have been populated. This is used to prevent
  // certain effects from firing too early while tab data and relevant
  // variables are undefined during the initial render.
  const tabsHavePopulatedRef = (0,react.useRef)(false);
  if (items.length > 0) {
    tabsHavePopulatedRef.current = true;
  }
  const selectedTab = items.find(item => item.id === selectedId);
  const firstEnabledTab = items.find(item => {
    // Ariakit internally refers to disabled tabs as `dimmed`.
    return !item.dimmed;
  });
  const initialTab = items.find(item => item.id === `${instanceId}-${defaultTabId}`);

  // Handle selecting the initial tab.
  (0,react.useLayoutEffect)(() => {
    if (isControlled) {
      return;
    }

    // Wait for the denoted initial tab to be declared before making a
    // selection. This ensures that if a tab is declared lazily it can
    // still receive initial selection, as well as ensuring no tab is
    // selected if an invalid `defaultTabId` is provided.
    if (defaultTabId && !initialTab) {
      return;
    }

    // If the currently selected tab is missing (i.e. removed from the DOM),
    // fall back to the initial tab or the first enabled tab if there is
    // one. Otherwise, no tab should be selected.
    if (!items.find(item => item.id === selectedId)) {
      if (initialTab && !initialTab.dimmed) {
        setSelectedId(initialTab?.id);
        return;
      }
      if (firstEnabledTab) {
        setSelectedId(firstEnabledTab.id);
      } else if (tabsHavePopulatedRef.current) {
        setSelectedId(null);
      }
    }
  }, [firstEnabledTab, initialTab, defaultTabId, isControlled, items, selectedId, setSelectedId]);

  // Handle the currently selected tab becoming disabled.
  (0,react.useLayoutEffect)(() => {
    if (!selectedTab?.dimmed) {
      return;
    }

    // In controlled mode, we trust that disabling tabs is done
    // intentionally, and don't select a new tab automatically.
    if (isControlled) {
      setSelectedId(null);
      return;
    }

    // If the currently selected tab becomes disabled, fall back to the
    // `defaultTabId` if possible. Otherwise select the first
    // enabled tab (if there is one).
    if (initialTab && !initialTab.dimmed) {
      setSelectedId(initialTab.id);
      return;
    }
    if (firstEnabledTab) {
      setSelectedId(firstEnabledTab.id);
    }
  }, [firstEnabledTab, initialTab, isControlled, selectedTab?.dimmed, setSelectedId]);

  // Clear `selectedId` if the active tab is removed from the DOM in controlled mode.
  (0,react.useLayoutEffect)(() => {
    if (!isControlled) {
      return;
    }

    // Once the tabs have populated, if the `selectedTabId` still can't be
    // found, clear the selection.
    if (tabsHavePopulatedRef.current && !!selectedTabId && !selectedTab) {
      setSelectedId(null);
    }
  }, [isControlled, selectedTab, selectedTabId, setSelectedId]);
  (0,react.useEffect)(() => {
    // If there is no active tab, fallback to place focus on the first enabled tab
    // so there is always an active element
    if (selectedTabId === null && !activeId && firstEnabledTab?.id) {
      setActiveId(firstEnabledTab.id);
    }
  }, [selectedTabId, activeId, firstEnabledTab?.id, setActiveId]);
  (0,react.useEffect)(() => {
    if (!isControlled) {
      return;
    }
    requestAnimationFrame(() => {
      const focusedElement = items?.[0]?.element?.ownerDocument.activeElement;
      if (!focusedElement || !items.some(item => focusedElement === item.element)) {
        return; // Return early if no tabs are focused.
      }

      // If, after ariakit re-computes the active tab, that tab doesn't match
      // the currently focused tab, then we force an update to ariakit to avoid
      // any mismatches, especially when navigating to previous/next tab with
      // arrow keys.
      if (activeId !== focusedElement.id) {
        setActiveId(focusedElement.id);
      }
    });
  }, [activeId, isControlled, items, setActiveId]);
  const contextValue = (0,react.useMemo)(() => ({
    store,
    instanceId
  }), [store, instanceId]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(TabsContext.Provider, {
    value: contextValue,
    children: children
  });
}
Tabs.TabList = TabList;
Tabs.Tab = tab_Tab;
Tabs.TabPanel = tabpanel_TabPanel;
Tabs.Context = TabsContext;
/* harmony default export */ const tabs = (Tabs);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/param-case@3.0.4/node_modules/param-case/dist.es2015/index.js + 1 modules
var dist_es2015 = __webpack_require__("../../node_modules/.pnpm/param-case@3.0.4/node_modules/param-case/dist.es2015/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/strings.js
/**
 * External dependencies
 */



/**
 * All unicode characters that we consider "dash-like":
 * - `\u007e`: ~ (tilde)
 * - `\u00ad`: ­ (soft hyphen)
 * - `\u2053`: ⁓ (swung dash)
 * - `\u207b`: ⁻ (superscript minus)
 * - `\u208b`: ₋ (subscript minus)
 * - `\u2212`: − (minus sign)
 * - `\\p{Pd}`: any other Unicode dash character
 */
const ALL_UNICODE_DASH_CHARACTERS = (/* unused pure expression or super */ null && (new RegExp(/[\u007e\u00ad\u2053\u207b\u208b\u2212\p{Pd}]/gu)));
const normalizeTextString = value => {
  return removeAccents(value).toLocaleLowerCase().replace(ALL_UNICODE_DASH_CHARACTERS, '-');
};

/**
 * Converts any string to kebab case.
 * Backwards compatible with Lodash's `_.kebabCase()`.
 * Backwards compatible with `_wp_to_kebab_case()`.
 *
 * @see https://lodash.com/docs/4.17.15#kebabCase
 * @see https://developer.wordpress.org/reference/functions/_wp_to_kebab_case/
 *
 * @param str String to convert.
 * @return Kebab-cased string
 */
function kebabCase(str) {
  var _str$toString;
  let input = (_str$toString = str?.toString?.()) !== null && _str$toString !== void 0 ? _str$toString : '';

  // See https://github.com/lodash/lodash/blob/b185fcee26b2133bd071f4aaca14b455c2ed1008/lodash.js#L4970
  input = input.replace(/['\u2019]/, '');
  return (0,dist_es2015/* paramCase */.c)(input, {
    splitRegexp: [/(?!(?:1ST|2ND|3RD|[4-9]TH)(?![a-z]))([a-z0-9])([A-Z])/g,
    // fooBar => foo-bar, 3Bar => 3-bar
    /(?!(?:1st|2nd|3rd|[4-9]th)(?![a-z]))([0-9])([a-z])/g,
    // 3bar => 3-bar
    /([A-Za-z])([0-9])/g,
    // Foo3 => foo-3, foo3 => foo-3
    /([A-Z])([A-Z][a-z])/g // FOOBar => foo-bar
    ]
  });
}

/**
 * Escapes the RegExp special characters.
 *
 * @param string Input string.
 *
 * @return Regex-escaped string.
 */
function escapeRegExp(string) {
  return string.replace(/[\\^$.*+?()[\]{}|]/g, '\\$&');
}
//# sourceMappingURL=strings.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+private-apis@1.8.1/node_modules/@wordpress/private-apis/build-module/implementation.js
/**
 * wordpress/private-apis – the utilities to enable private cross-package
 * exports of private APIs.
 *
 * This "implementation.js" file is needed for the sake of the unit tests. It
 * exports more than the public API of the package to aid in testing.
 */

/**
 * The list of core modules allowed to opt-in to the private APIs.
 */
const CORE_MODULES_USING_PRIVATE_APIS = ['@wordpress/block-directory', '@wordpress/block-editor', '@wordpress/block-library', '@wordpress/blocks', '@wordpress/commands', '@wordpress/components', '@wordpress/core-commands', '@wordpress/core-data', '@wordpress/customize-widgets', '@wordpress/data', '@wordpress/edit-post', '@wordpress/edit-site', '@wordpress/edit-widgets', '@wordpress/editor', '@wordpress/format-library', '@wordpress/interface', '@wordpress/patterns', '@wordpress/preferences', '@wordpress/reusable-blocks', '@wordpress/router', '@wordpress/dataviews', '@wordpress/fields'];

/**
 * A list of core modules that already opted-in to
 * the privateApis package.
 *
 * @type {string[]}
 */
const registeredPrivateApis = [];

/*
 * Warning for theme and plugin developers.
 *
 * The use of private developer APIs is intended for use by WordPress Core
 * and the Gutenberg plugin exclusively.
 *
 * Dangerously opting in to using these APIs is NOT RECOMMENDED. Furthermore,
 * the WordPress Core philosophy to strive to maintain backward compatibility
 * for third-party developers DOES NOT APPLY to private APIs.
 *
 * THE CONSENT STRING FOR OPTING IN TO THESE APIS MAY CHANGE AT ANY TIME AND
 * WITHOUT NOTICE. THIS CHANGE WILL BREAK EXISTING THIRD-PARTY CODE. SUCH A
 * CHANGE MAY OCCUR IN EITHER A MAJOR OR MINOR RELEASE.
 */
const requiredConsent = 'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.';

/** @type {boolean} */
let allowReRegistration;
// The safety measure is meant for WordPress core where IS_WORDPRESS_CORE
// is set to true.
// For the general use-case, the re-registration should be allowed by default
// Let's default to true, then. Try/catch will fall back to "true" even if the
// environment variable is not explicitly defined.
try {
  allowReRegistration = globalThis.IS_WORDPRESS_CORE ? false : true;
} catch (error) {
  allowReRegistration = true;
}

/**
 * Called by a @wordpress package wishing to opt-in to accessing or exposing
 * private private APIs.
 *
 * @param {string} consent    The consent string.
 * @param {string} moduleName The name of the module that is opting in.
 * @return {{lock: typeof lock, unlock: typeof unlock}} An object containing the lock and unlock functions.
 */
const __dangerousOptInToUnstableAPIsOnlyForCoreModules = (consent, moduleName) => {
  if (!CORE_MODULES_USING_PRIVATE_APIS.includes(moduleName)) {
    throw new Error(`You tried to opt-in to unstable APIs as module "${moduleName}". ` + 'This feature is only for JavaScript modules shipped with WordPress core. ' + 'Please do not use it in plugins and themes as the unstable APIs will be removed ' + 'without a warning. If you ignore this error and depend on unstable features, ' + 'your product will inevitably break on one of the next WordPress releases.');
  }
  if (!allowReRegistration && registeredPrivateApis.includes(moduleName)) {
    // This check doesn't play well with Story Books / Hot Module Reloading
    // and isn't included in the Gutenberg plugin. It only matters in the
    // WordPress core release.
    throw new Error(`You tried to opt-in to unstable APIs as module "${moduleName}" which is already registered. ` + 'This feature is only for JavaScript modules shipped with WordPress core. ' + 'Please do not use it in plugins and themes as the unstable APIs will be removed ' + 'without a warning. If you ignore this error and depend on unstable features, ' + 'your product will inevitably break on one of the next WordPress releases.');
  }
  if (consent !== requiredConsent) {
    throw new Error(`You tried to opt-in to unstable APIs without confirming you know the consequences. ` + 'This feature is only for JavaScript modules shipped with WordPress core. ' + 'Please do not use it in plugins and themes as the unstable APIs will removed ' + 'without a warning. If you ignore this error and depend on unstable features, ' + 'your product will inevitably break on the next WordPress release.');
  }
  registeredPrivateApis.push(moduleName);
  return {
    lock,
    unlock
  };
};

/**
 * Binds private data to an object.
 * It does not alter the passed object in any way, only
 * registers it in an internal map of private data.
 *
 * The private data can't be accessed by any other means
 * than the `unlock` function.
 *
 * @example
 * ```js
 * const object = {};
 * const privateData = { a: 1 };
 * lock( object, privateData );
 *
 * object
 * // {}
 *
 * unlock( object );
 * // { a: 1 }
 * ```
 *
 * @param {any} object      The object to bind the private data to.
 * @param {any} privateData The private data to bind to the object.
 */
function lock(object, privateData) {
  if (!object) {
    throw new Error('Cannot lock an undefined object.');
  }
  if (!(__private in object)) {
    object[__private] = {};
  }
  lockedData.set(object[__private], privateData);
}

/**
 * Unlocks the private data bound to an object.
 *
 * It does not alter the passed object in any way, only
 * returns the private data paired with it using the `lock()`
 * function.
 *
 * @example
 * ```js
 * const object = {};
 * const privateData = { a: 1 };
 * lock( object, privateData );
 *
 * object
 * // {}
 *
 * unlock( object );
 * // { a: 1 }
 * ```
 *
 * @param {any} object The object to unlock the private data from.
 * @return {any} The private data bound to the object.
 */
function unlock(object) {
  if (!object) {
    throw new Error('Cannot unlock an undefined object.');
  }
  if (!(__private in object)) {
    throw new Error('Cannot unlock an object that was not locked before. ');
  }
  return lockedData.get(object[__private]);
}
const lockedData = new WeakMap();

/**
 * Used by lock() and unlock() to uniquely identify the private data
 * related to a containing object.
 */
const __private = Symbol('Private API ID');

// Unit tests utilities:

/**
 * Private function to allow the unit tests to allow
 * a mock module to access the private APIs.
 *
 * @param {string} name The name of the module.
 */
function allowCoreModule(name) {
  CORE_MODULES_USING_PRIVATE_APIS.push(name);
}

/**
 * Private function to allow the unit tests to set
 * a custom list of allowed modules.
 */
function resetAllowedCoreModules() {
  while (CORE_MODULES_USING_PRIVATE_APIS.length) {
    CORE_MODULES_USING_PRIVATE_APIS.pop();
  }
}
/**
 * Private function to allow the unit tests to reset
 * the list of registered private apis.
 */
function resetRegisteredPrivateApis() {
  while (registeredPrivateApis.length) {
    registeredPrivateApis.pop();
  }
}
//# sourceMappingURL=implementation.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/lock-unlock.js
/**
 * WordPress dependencies
 */

const {
  lock: lock_unlock_lock,
  unlock: lock_unlock_unlock
} = __dangerousOptInToUnstableAPIsOnlyForCoreModules('I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.', '@wordpress/components');
//# sourceMappingURL=lock-unlock.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/private-apis.js
/**
 * Internal dependencies
 */








const privateApis = {};
lock_unlock_lock(privateApis, {
  __experimentalPopoverLegacyPositionToPlacement: utils/* positionToPlacement */.YK,
  createPrivateSlotFill: slot_fill/* createPrivateSlotFill */.VI,
  ComponentsContext: context_system_provider/* ComponentsContext */.aG,
  Tabs: tabs,
  Theme: theme,
  DropdownMenuV2: DropdownMenuV2,
  kebabCase: kebabCase
});
//# sourceMappingURL=private-apis.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/lock-unlock.js
/**
 * WordPress dependencies
 */

const {
  lock: build_module_lock_unlock_lock,
  unlock: build_module_lock_unlock_unlock
} = __dangerousOptInToUnstableAPIsOnlyForCoreModules('I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.', '@wordpress/dataviews');
//# sourceMappingURL=lock-unlock.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-filters/add-filter.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */


const {
  DropdownMenuV2: add_filter_DropdownMenuV2
} = build_module_lock_unlock_unlock(privateApis);
function AddFilterDropdownMenu({
  filters,
  view,
  onChangeView,
  setOpenedFilter,
  trigger
}) {
  const inactiveFilters = filters.filter(filter => !filter.isVisible);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(add_filter_DropdownMenuV2, {
    trigger: trigger,
    children: inactiveFilters.map(filter => {
      return /*#__PURE__*/(0,jsx_runtime.jsx)(add_filter_DropdownMenuV2.Item, {
        onClick: () => {
          setOpenedFilter(filter.field);
          onChangeView({
            ...view,
            page: 1,
            filters: [...(view.filters || []), {
              field: filter.field,
              value: undefined,
              operator: filter.operators[0]
            }]
          });
        },
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(add_filter_DropdownMenuV2.ItemLabel, {
          children: filter.name
        })
      }, filter.field);
    })
  });
}
function AddFilter({
  filters,
  view,
  onChangeView,
  setOpenedFilter
}, ref) {
  if (!filters.length || filters.every(({
    isPrimary
  }) => isPrimary)) {
    return null;
  }
  const inactiveFilters = filters.filter(filter => !filter.isVisible);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(AddFilterDropdownMenu, {
    trigger: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      accessibleWhenDisabled: true,
      size: "compact",
      className: "dataviews-filters-button",
      variant: "tertiary",
      disabled: !inactiveFilters.length,
      ref: ref,
      children: (0,build_module.__)('Add filter')
    }),
    filters,
    view,
    onChangeView,
    setOpenedFilter
  });
}
/* harmony default export */ const add_filter = ((0,react.forwardRef)(AddFilter));
//# sourceMappingURL=add-filter.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-filters/reset-filters.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */

function ResetFilter({
  filters,
  view,
  onChangeView
}) {
  const isPrimary = field => filters.some(_filter => _filter.field === field && _filter.isPrimary);
  const isDisabled = !view.search && !view.filters?.some(_filter => _filter.value !== undefined || !isPrimary(_filter.field));
  return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    disabled: isDisabled,
    accessibleWhenDisabled: true,
    size: "compact",
    variant: "tertiary",
    className: "dataviews-filters__reset-button",
    onClick: () => {
      onChangeView({
        ...view,
        page: 1,
        search: '',
        filters: []
      });
    },
    children: (0,build_module.__)('Reset')
  });
}
//# sourceMappingURL=reset-filters.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/utils.js
/**
 * Internal dependencies
 */

function sanitizeOperators(field) {
  let operators = field.filterBy?.operators;

  // Assign default values.
  if (!operators || !Array.isArray(operators)) {
    operators = [OPERATOR_IS_ANY, OPERATOR_IS_NONE];
  }

  // Make sure only valid operators are used.
  operators = operators.filter(operator => ALL_OPERATORS.includes(operator));

  // Do not allow mixing single & multiselection operators.
  // Remove multiselection operators if any of the single selection ones is present.
  if (operators.includes(OPERATOR_IS) || operators.includes(OPERATOR_IS_NOT)) {
    operators = operators.filter(operator => [OPERATOR_IS, OPERATOR_IS_NOT].includes(operator));
  }
  return operators;
}
//# sourceMappingURL=utils.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-filters/index.js
/**
 * WordPress dependencies
 */





/**
 * Internal dependencies
 */








function useFilters(fields, view) {
  return (0,react.useMemo)(() => {
    const filters = [];
    fields.forEach(field => {
      if (!field.elements?.length) {
        return;
      }
      const operators = sanitizeOperators(field);
      if (operators.length === 0) {
        return;
      }
      const isPrimary = !!field.filterBy?.isPrimary;
      filters.push({
        field: field.id,
        name: field.label,
        elements: field.elements,
        singleSelection: operators.some(op => [OPERATOR_IS, OPERATOR_IS_NOT].includes(op)),
        operators,
        isVisible: isPrimary || !!view.filters?.some(f => f.field === field.id && ALL_OPERATORS.includes(f.operator)),
        isPrimary
      });
    });
    // Sort filters by primary property. We need the primary filters to be first.
    // Then we sort by name.
    filters.sort((a, b) => {
      if (a.isPrimary && !b.isPrimary) {
        return -1;
      }
      if (!a.isPrimary && b.isPrimary) {
        return 1;
      }
      return a.name.localeCompare(b.name);
    });
    return filters;
  }, [fields, view]);
}
function FilterVisibilityToggle({
  filters,
  view,
  onChangeView,
  setOpenedFilter,
  isShowingFilter,
  setIsShowingFilter
}) {
  const onChangeViewWithFilterVisibility = (0,react.useCallback)(_view => {
    onChangeView(_view);
    setIsShowingFilter(true);
  }, [onChangeView, setIsShowingFilter]);
  const visibleFilters = filters.filter(filter => filter.isVisible);
  const hasVisibleFilters = !!visibleFilters.length;
  if (filters.length === 0) {
    return null;
  }
  if (!hasVisibleFilters) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(AddFilterDropdownMenu, {
      filters: filters,
      view: view,
      onChangeView: onChangeViewWithFilterVisibility,
      setOpenedFilter: setOpenedFilter,
      trigger: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        className: "dataviews-filters__visibility-toggle",
        size: "compact",
        icon: library_funnel,
        label: (0,build_module.__)('Add filter'),
        isPressed: false,
        "aria-expanded": false
      })
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "dataviews-filters__container-visibility-toggle",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      className: "dataviews-filters__visibility-toggle",
      size: "compact",
      icon: library_funnel,
      label: (0,build_module.__)('Toggle filter display'),
      onClick: () => {
        if (!isShowingFilter) {
          setOpenedFilter(null);
        }
        setIsShowingFilter(!isShowingFilter);
      },
      isPressed: isShowingFilter,
      "aria-expanded": isShowingFilter
    }), hasVisibleFilters && !!view.filters?.length && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "dataviews-filters-toggle__count",
      children: view.filters?.length
    })]
  });
}
function Filters() {
  const {
    fields,
    view,
    onChangeView,
    openedFilter,
    setOpenedFilter
  } = (0,react.useContext)(dataviews_context);
  const addFilterRef = (0,react.useRef)(null);
  const filters = useFilters(fields, view);
  const addFilter = /*#__PURE__*/(0,jsx_runtime.jsx)(add_filter, {
    filters: filters,
    view: view,
    onChangeView: onChangeView,
    ref: addFilterRef,
    setOpenedFilter: setOpenedFilter
  }, "add-filter");
  const visibleFilters = filters.filter(filter => filter.isVisible);
  if (visibleFilters.length === 0) {
    return null;
  }
  const filterComponents = [...visibleFilters.map(filter => {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(FilterSummary, {
      filter: filter,
      view: view,
      onChangeView: onChangeView,
      addFilterRef: addFilterRef,
      openedFilter: openedFilter
    }, filter.field);
  }), addFilter];
  filterComponents.push( /*#__PURE__*/(0,jsx_runtime.jsx)(ResetFilter, {
    filters: filters,
    view: view,
    onChangeView: onChangeView
  }, "reset-filters"));
  return /*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
    justify: "flex-start",
    style: {
      width: 'fit-content'
    },
    className: "dataviews-filters__container",
    wrap: true,
    children: filterComponents
  });
}
/* harmony default export */ const dataviews_filters = ((0,react.memo)(Filters));
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/block-table.js
/**
 * WordPress dependencies
 */


const blockTable = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v3.5h-15V5c0-.3.2-.5.5-.5zm8 5.5h6.5v3.5H13V10zm-1.5 3.5h-7V10h7v3.5zm-7 5.5v-4h7v4.5H5c-.3 0-.5-.2-.5-.5zm14.5.5h-6V15h6.5v4c0 .3-.2.5-.5.5z"
  })
});
/* harmony default export */ const block_table = (blockTable);
//# sourceMappingURL=block-table.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/category.js
/**
 * WordPress dependencies
 */


const category = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M6 5.5h3a.5.5 0 01.5.5v3a.5.5 0 01-.5.5H6a.5.5 0 01-.5-.5V6a.5.5 0 01.5-.5zM4 6a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm11-.5h3a.5.5 0 01.5.5v3a.5.5 0 01-.5.5h-3a.5.5 0 01-.5-.5V6a.5.5 0 01.5-.5zM13 6a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V6zm5 8.5h-3a.5.5 0 00-.5.5v3a.5.5 0 00.5.5h3a.5.5 0 00.5-.5v-3a.5.5 0 00-.5-.5zM15 13a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2v-3a2 2 0 00-2-2h-3zm-9 1.5h3a.5.5 0 01.5.5v3a.5.5 0 01-.5.5H6a.5.5 0 01-.5-.5v-3a.5.5 0 01.5-.5zM4 15a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3z",
    fillRule: "evenodd",
    clipRule: "evenodd"
  })
});
/* harmony default export */ const library_category = (category);
//# sourceMappingURL=category.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/format-list-bullets-rtl.js
/**
 * WordPress dependencies
 */


const formatListBulletsRTL = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M4 8.8h8.9V7.2H4v1.6zm0 7h8.9v-1.5H4v1.5zM18 13c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"
  })
});
/* harmony default export */ const format_list_bullets_rtl = (formatListBulletsRTL);
//# sourceMappingURL=format-list-bullets-rtl.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/format-list-bullets.js
/**
 * WordPress dependencies
 */


const formatListBullets = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M11.1 15.8H20v-1.5h-8.9v1.5zm0-8.6v1.5H20V7.2h-8.9zM6 13c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"
  })
});
/* harmony default export */ const format_list_bullets = (formatListBullets);
//# sourceMappingURL=format-list-bullets.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/spinner/styles.js

function spinner_styles_EMOTION_STRINGIFIED_CSS_ERROR_() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const spinAnimation = (0,emotion_react_browser_esm/* keyframes */.i7)`
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
 `;
const StyledSpinner = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("svg",  true ? {
  target: "ea4tfvq2"
} : 0)("width:", config_values/* default */.A.spinnerSize, "px;height:", config_values/* default */.A.spinnerSize, "px;display:inline-block;margin:5px 11px 0;position:relative;color:", colors_values/* COLORS */.l.theme.accent, ";overflow:visible;opacity:1;background-color:transparent;" + ( true ? "" : 0));
const commonPathProps =  true ? {
  name: "9s4963",
  styles: "fill:transparent;stroke-width:1.5px"
} : 0;
const SpinnerTrack = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("circle",  true ? {
  target: "ea4tfvq1"
} : 0)(commonPathProps, ";stroke:", colors_values/* COLORS */.l.gray[300], ";" + ( true ? "" : 0));
const SpinnerIndicator = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("path",  true ? {
  target: "ea4tfvq0"
} : 0)(commonPathProps, ";stroke:currentColor;stroke-linecap:round;transform-origin:50% 50%;animation:1.4s linear infinite both ", spinAnimation, ";" + ( true ? "" : 0));
//# sourceMappingURL=styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/spinner/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * WordPress dependencies
 */



function UnforwardedSpinner({
  className,
  ...props
}, forwardedRef) {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(StyledSpinner, {
    className: (0,clsx/* default */.A)('components-spinner', className),
    viewBox: "0 0 100 100",
    width: "16",
    height: "16",
    xmlns: "http://www.w3.org/2000/svg",
    role: "presentation",
    focusable: "false",
    ...props,
    ref: forwardedRef,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(SpinnerTrack, {
      cx: "50",
      cy: "50",
      r: "50",
      vectorEffect: "non-scaling-stroke"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(SpinnerIndicator, {
      d: "m 50 0 a 50 50 0 0 1 50 50",
      vectorEffect: "non-scaling-stroke"
    })]
  });
}
/**
 * `Spinner` is a component used to notify users that their action is being processed.
 *
 * ```js
 *   import { Spinner } from '@wordpress/components';
 *
 *   function Example() {
 *     return <Spinner />;
 *   }
 * ```
 */
const Spinner = (0,react.forwardRef)(UnforwardedSpinner);
/* harmony default export */ const spinner = (Spinner);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.js
var use_ref_effect = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.8.1/node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.8.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/reset.js
var library_reset = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/reset.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/base-control/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/checkbox-control/index.js
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
 * Checkboxes allow the user to select one or more items from a set.
 *
 * ```jsx
 * import { CheckboxControl } from '@wordpress/components';
 * import { useState } from '@wordpress/element';
 *
 * const MyCheckboxControl = () => {
 *   const [ isChecked, setChecked ] = useState( true );
 *   return (
 *     <CheckboxControl
 *       __nextHasNoMarginBottom
 *       label="Is author"
 *       help="Is the user a author or not?"
 *       checked={ isChecked }
 *       onChange={ setChecked }
 *     />
 *   );
 * };
 * ```
 */
function CheckboxControl(props) {
  const {
    __nextHasNoMarginBottom,
    label,
    className,
    heading,
    checked,
    indeterminate,
    help,
    id: idProp,
    onChange,
    ...additionalProps
  } = props;
  if (heading) {
    (0,deprecated_build_module/* default */.A)('`heading` prop in `CheckboxControl`', {
      alternative: 'a separate element to implement a heading',
      since: '5.8'
    });
  }
  const [showCheckedIcon, setShowCheckedIcon] = (0,react.useState)(false);
  const [showIndeterminateIcon, setShowIndeterminateIcon] = (0,react.useState)(false);

  // Run the following callback every time the `ref` (and the additional
  // dependencies) change.
  const ref = (0,use_ref_effect/* default */.A)(node => {
    if (!node) {
      return;
    }

    // It cannot be set using an HTML attribute.
    node.indeterminate = !!indeterminate;
    setShowCheckedIcon(node.matches(':checked'));
    setShowIndeterminateIcon(node.matches(':indeterminate'));
  }, [checked, indeterminate]);
  const id = (0,use_instance_id/* default */.A)(CheckboxControl, 'inspector-checkbox-control', idProp);
  const onChangeValue = event => onChange(event.target.checked);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    __nextHasNoMarginBottom: __nextHasNoMarginBottom,
    __associatedWPComponentName: "CheckboxControl",
    label: heading,
    id: id,
    help: help && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "components-checkbox-control__help",
      children: help
    }),
    className: (0,clsx/* default */.A)('components-checkbox-control', className),
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      spacing: 0,
      justify: "start",
      alignment: "top",
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
        className: "components-checkbox-control__input-container",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("input", {
          ref: ref,
          id: id,
          className: "components-checkbox-control__input",
          type: "checkbox",
          value: "1",
          onChange: onChangeValue,
          checked: checked,
          "aria-describedby": !!help ? id + '__help' : undefined,
          ...additionalProps
        }), showIndeterminateIcon ? /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: library_reset/* default */.A,
          className: "components-checkbox-control__indeterminate",
          role: "presentation"
        }) : null, showCheckedIcon ? /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: library_check,
          className: "components-checkbox-control__checked",
          role: "presentation"
        }) : null]
      }), label && /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
        className: "components-checkbox-control__label",
        htmlFor: id,
        children: label
      })]
    })
  });
}
/* harmony default export */ const checkbox_control = (CheckboxControl);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-selection-checkbox/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */

function DataViewsSelectionCheckbox({
  selection,
  onChangeSelection,
  item,
  getItemId,
  primaryField,
  disabled
}) {
  const id = getItemId(item);
  const checked = !disabled && selection.includes(id);
  let selectionLabel;
  if (primaryField?.getValue && item) {
    // eslint-disable-next-line @wordpress/valid-sprintf
    selectionLabel = (0,build_module/* sprintf */.nv)( /* translators: %s: item title. */
    checked ? (0,build_module.__)('Deselect item: %s') : (0,build_module.__)('Select item: %s'), primaryField.getValue({
      item
    }));
  } else {
    selectionLabel = checked ? (0,build_module.__)('Select a new item') : (0,build_module.__)('Deselect item');
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control, {
    className: "dataviews-selection-checkbox",
    __nextHasNoMarginBottom: true,
    "aria-label": selectionLabel,
    "aria-disabled": disabled,
    checked: checked,
    onChange: () => {
      if (disabled) {
        return;
      }
      onChangeSelection(selection.includes(id) ? selection.filter(itemId => id !== itemId) : [...selection, id]);
    }
  });
}
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-on-mount/index.js
var use_focus_on_mount = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-on-mount/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-constrained-tabbing/index.js
var use_constrained_tabbing = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-constrained-tabbing/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-return/index.js
var use_focus_return = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-return/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/close.js
var library_close = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/close.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@4.8.1/node_modules/@wordpress/dom/build-module/utils/assert-is-defined.js
function assertIsDefined(val, name) {
  if (false) {}
}
//# sourceMappingURL=assert-is-defined.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@4.8.1/node_modules/@wordpress/dom/build-module/dom/get-computed-style.js
/**
 * Internal dependencies
 */


/* eslint-disable jsdoc/valid-types */
/**
 * @param {Element} element
 * @return {ReturnType<Window['getComputedStyle']>} The computed style for the element.
 */
function get_computed_style_getComputedStyle(element) {
  /* eslint-enable jsdoc/valid-types */
  assertIsDefined(element.ownerDocument.defaultView, 'element.ownerDocument.defaultView');
  return element.ownerDocument.defaultView.getComputedStyle(element);
}
//# sourceMappingURL=get-computed-style.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@4.8.1/node_modules/@wordpress/dom/build-module/dom/get-scroll-container.js
/**
 * Internal dependencies
 */


/**
 * Given a DOM node, finds the closest scrollable container node or the node
 * itself, if scrollable.
 *
 * @param {Element | null} node      Node from which to start.
 * @param {?string}        direction Direction of scrollable container to search for ('vertical', 'horizontal', 'all').
 *                                   Defaults to 'vertical'.
 * @return {Element | undefined} Scrollable container node, if found.
 */
function getScrollContainer(node, direction = 'vertical') {
  if (!node) {
    return undefined;
  }
  if (direction === 'vertical' || direction === 'all') {
    // Scrollable if scrollable height exceeds displayed...
    if (node.scrollHeight > node.clientHeight) {
      // ...except when overflow is defined to be hidden or visible
      const {
        overflowY
      } = get_computed_style_getComputedStyle(node);
      if (/(auto|scroll)/.test(overflowY)) {
        return node;
      }
    }
  }
  if (direction === 'horizontal' || direction === 'all') {
    // Scrollable if scrollable width exceeds displayed...
    if (node.scrollWidth > node.clientWidth) {
      // ...except when overflow is defined to be hidden or visible
      const {
        overflowX
      } = get_computed_style_getComputedStyle(node);
      if (/(auto|scroll)/.test(overflowX)) {
        return node;
      }
    }
  }
  if (node.ownerDocument === node.parentNode) {
    return node;
  }

  // Continue traversing.
  return getScrollContainer( /** @type {Element} */node.parentNode, direction);
}
//# sourceMappingURL=get-scroll-container.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/modal/aria-helper.js
const LIVE_REGION_ARIA_ROLES = new Set(['alert', 'status', 'log', 'marquee', 'timer']);
const hiddenElementsByDepth = [];

/**
 * Hides all elements in the body element from screen-readers except
 * the provided element and elements that should not be hidden from
 * screen-readers.
 *
 * The reason we do this is because `aria-modal="true"` currently is bugged
 * in Safari, and support is spotty in other browsers overall. In the future
 * we should consider removing these helper functions in favor of
 * `aria-modal="true"`.
 *
 * @param modalElement The element that should not be hidden.
 */
function modalize(modalElement) {
  const elements = Array.from(document.body.children);
  const hiddenElements = [];
  hiddenElementsByDepth.push(hiddenElements);
  for (const element of elements) {
    if (element === modalElement) {
      continue;
    }
    if (elementShouldBeHidden(element)) {
      element.setAttribute('aria-hidden', 'true');
      hiddenElements.push(element);
    }
  }
}

/**
 * Determines if the passed element should not be hidden from screen readers.
 *
 * @param element The element that should be checked.
 *
 * @return Whether the element should not be hidden from screen-readers.
 */
function elementShouldBeHidden(element) {
  const role = element.getAttribute('role');
  return !(element.tagName === 'SCRIPT' || element.hasAttribute('aria-hidden') || element.hasAttribute('aria-live') || role && LIVE_REGION_ARIA_ROLES.has(role));
}

/**
 * Accessibly reveals the elements hidden by the latest modal.
 */
function unmodalize() {
  const hiddenElements = hiddenElementsByDepth.pop();
  if (!hiddenElements) {
    return;
  }
  for (const element of hiddenElements) {
    element.removeAttribute('aria-hidden');
  }
}
//# sourceMappingURL=aria-helper.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/style-provider/index.js
var style_provider = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/style-provider/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/with-ignore-ime-events.js
var with_ignore_ime_events = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/with-ignore-ime-events.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/spacer/component.js + 1 modules
var spacer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/spacer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-reduced-motion/index.js
var use_reduced_motion = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-reduced-motion/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/modal/use-modal-exit-animation.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */



// Animation duration (ms) extracted to JS in order to be used on a setTimeout.
const FRAME_ANIMATION_DURATION = config_values/* default */.A.transitionDuration;
const FRAME_ANIMATION_DURATION_NUMBER = Number.parseInt(config_values/* default */.A.transitionDuration);
const EXIT_ANIMATION_NAME = 'components-modal__disappear-animation';
function useModalExitAnimation() {
  const frameRef = (0,react.useRef)();
  const [isAnimatingOut, setIsAnimatingOut] = (0,react.useState)(false);
  const isReducedMotion = (0,use_reduced_motion/* default */.A)();
  const closeModal = (0,react.useCallback)(() => new Promise(closeModalResolve => {
    // Grab a "stable" reference of the frame element, since
    // the value held by the react ref might change at runtime.
    const frameEl = frameRef.current;
    if (isReducedMotion) {
      closeModalResolve();
      return;
    }
    if (!frameEl) {
      globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)("wp.components.Modal: the Modal component can't be closed with an exit animation because of a missing reference to the modal frame element.") : void 0;
      closeModalResolve();
      return;
    }
    let handleAnimationEnd;
    const startAnimation = () => new Promise(animationResolve => {
      handleAnimationEnd = e => {
        if (e.animationName === EXIT_ANIMATION_NAME) {
          animationResolve();
        }
      };
      frameEl.addEventListener('animationend', handleAnimationEnd);
      setIsAnimatingOut(true);
    });
    const animationTimeout = () => new Promise(timeoutResolve => {
      setTimeout(() => timeoutResolve(),
      // Allow an extra 20% of the animation duration for the
      // animationend event to fire, in case the animation frame is
      // slightly delayes by some other events in the event loop.
      FRAME_ANIMATION_DURATION_NUMBER * 1.2);
    });
    Promise.race([startAnimation(), animationTimeout()]).then(() => {
      if (handleAnimationEnd) {
        frameEl.removeEventListener('animationend', handleAnimationEnd);
      }
      setIsAnimatingOut(false);
      closeModalResolve();
    });
  }), [isReducedMotion]);
  return {
    overlayClassname: isAnimatingOut ? 'is-animating-out' : undefined,
    frameRef,
    frameStyle: {
      '--modal-frame-animation-duration': `${FRAME_ANIMATION_DURATION}`
    },
    closeModal
  };
}
//# sourceMappingURL=use-modal-exit-animation.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/modal/index.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */







// Used to track and dismiss the prior modal when another opens unless nested.



const ModalContext = (0,react.createContext)(new Set());

// Used to track body class names applied while modals are open.
const bodyOpenClasses = new Map();
function UnforwardedModal(props, forwardedRef) {
  const {
    bodyOpenClassName = 'modal-open',
    role = 'dialog',
    title = null,
    focusOnMount = true,
    shouldCloseOnEsc = true,
    shouldCloseOnClickOutside = true,
    isDismissible = true,
    /* Accessibility. */
    aria = {
      labelledby: undefined,
      describedby: undefined
    },
    onRequestClose,
    icon,
    closeButtonLabel,
    children,
    style,
    overlayClassName: overlayClassnameProp,
    className,
    contentLabel,
    onKeyDown,
    isFullScreen = false,
    size,
    headerActions = null,
    __experimentalHideHeader = false
  } = props;
  const ref = (0,react.useRef)();
  const instanceId = (0,use_instance_id/* default */.A)(Modal);
  const headingId = title ? `components-modal-header-${instanceId}` : aria.labelledby;

  // The focus hook does not support 'firstContentElement' but this is a valid
  // value for the Modal's focusOnMount prop. The following code ensures the focus
  // hook will focus the first focusable node within the element to which it is applied.
  // When `firstContentElement` is passed as the value of the focusOnMount prop,
  // the focus hook is applied to the Modal's content element.
  // Otherwise, the focus hook is applied to the Modal's ref. This ensures that the
  // focus hook will focus the first element in the Modal's **content** when
  // `firstContentElement` is passed.
  const focusOnMountRef = (0,use_focus_on_mount/* default */.A)(focusOnMount === 'firstContentElement' ? 'firstElement' : focusOnMount);
  const constrainedTabbingRef = (0,use_constrained_tabbing/* default */.A)();
  const focusReturnRef = (0,use_focus_return/* default */.A)();
  const contentRef = (0,react.useRef)(null);
  const childrenContainerRef = (0,react.useRef)(null);
  const [hasScrolledContent, setHasScrolledContent] = (0,react.useState)(false);
  const [hasScrollableContent, setHasScrollableContent] = (0,react.useState)(false);
  let sizeClass;
  if (isFullScreen || size === 'fill') {
    sizeClass = 'is-full-screen';
  } else if (size) {
    sizeClass = `has-size-${size}`;
  }

  // Determines whether the Modal content is scrollable and updates the state.
  const isContentScrollable = (0,react.useCallback)(() => {
    if (!contentRef.current) {
      return;
    }
    const closestScrollContainer = getScrollContainer(contentRef.current);
    if (contentRef.current === closestScrollContainer) {
      setHasScrollableContent(true);
    } else {
      setHasScrollableContent(false);
    }
  }, [contentRef]);

  // Accessibly isolates/unisolates the modal.
  (0,react.useEffect)(() => {
    modalize(ref.current);
    return () => unmodalize();
  }, []);

  // Keeps a fresh ref for the subsequent effect.
  const onRequestCloseRef = (0,react.useRef)();
  (0,react.useEffect)(() => {
    onRequestCloseRef.current = onRequestClose;
  }, [onRequestClose]);

  // The list of `onRequestClose` callbacks of open (non-nested) Modals. Only
  // one should remain open at a time and the list enables closing prior ones.
  const dismissers = (0,react.useContext)(ModalContext);
  // Used for the tracking and dismissing any nested modals.
  const [nestedDismissers] = (0,react.useState)(() => new Set());

  // Updates the stack tracking open modals at this level and calls
  // onRequestClose for any prior and/or nested modals as applicable.
  (0,react.useEffect)(() => {
    // add this modal instance to the dismissers set
    dismissers.add(onRequestCloseRef);
    // request that all the other modals close themselves
    for (const dismisser of dismissers) {
      if (dismisser !== onRequestCloseRef) {
        dismisser.current?.();
      }
    }
    return () => {
      // request that all the nested modals close themselves
      for (const dismisser of nestedDismissers) {
        dismisser.current?.();
      }
      // remove this modal instance from the dismissers set
      dismissers.delete(onRequestCloseRef);
    };
  }, [dismissers, nestedDismissers]);

  // Adds/removes the value of bodyOpenClassName to body element.
  (0,react.useEffect)(() => {
    var _bodyOpenClasses$get;
    const theClass = bodyOpenClassName;
    const oneMore = 1 + ((_bodyOpenClasses$get = bodyOpenClasses.get(theClass)) !== null && _bodyOpenClasses$get !== void 0 ? _bodyOpenClasses$get : 0);
    bodyOpenClasses.set(theClass, oneMore);
    document.body.classList.add(bodyOpenClassName);
    return () => {
      const oneLess = bodyOpenClasses.get(theClass) - 1;
      if (oneLess === 0) {
        document.body.classList.remove(theClass);
        bodyOpenClasses.delete(theClass);
      } else {
        bodyOpenClasses.set(theClass, oneLess);
      }
    };
  }, [bodyOpenClassName]);
  const {
    closeModal,
    frameRef,
    frameStyle,
    overlayClassname
  } = useModalExitAnimation();

  // Calls the isContentScrollable callback when the Modal children container resizes.
  (0,react.useLayoutEffect)(() => {
    if (!window.ResizeObserver || !childrenContainerRef.current) {
      return;
    }
    const resizeObserver = new ResizeObserver(isContentScrollable);
    resizeObserver.observe(childrenContainerRef.current);
    isContentScrollable();
    return () => {
      resizeObserver.disconnect();
    };
  }, [isContentScrollable, childrenContainerRef]);
  function handleEscapeKeyDown(event) {
    if (shouldCloseOnEsc && (event.code === 'Escape' || event.key === 'Escape') && !event.defaultPrevented) {
      event.preventDefault();
      closeModal().then(() => onRequestClose(event));
    }
  }
  const onContentContainerScroll = (0,react.useCallback)(e => {
    var _e$currentTarget$scro;
    const scrollY = (_e$currentTarget$scro = e?.currentTarget?.scrollTop) !== null && _e$currentTarget$scro !== void 0 ? _e$currentTarget$scro : -1;
    if (!hasScrolledContent && scrollY > 0) {
      setHasScrolledContent(true);
    } else if (hasScrolledContent && scrollY <= 0) {
      setHasScrolledContent(false);
    }
  }, [hasScrolledContent]);
  let pressTarget = null;
  const overlayPressHandlers = {
    onPointerDown: event => {
      if (event.target === event.currentTarget) {
        pressTarget = event.target;
        // Avoids focus changing so that focus return works as expected.
        event.preventDefault();
      }
    },
    // Closes the modal with two exceptions. 1. Opening the context menu on
    // the overlay. 2. Pressing on the overlay then dragging the pointer
    // over the modal and releasing. Due to the modal being a child of the
    // overlay, such a gesture is a `click` on the overlay and cannot be
    // excepted by a `click` handler. Thus the tactic of handling
    // `pointerup` and comparing its target to that of the `pointerdown`.
    onPointerUp: ({
      target,
      button
    }) => {
      const isSameTarget = target === pressTarget;
      pressTarget = null;
      if (button === 0 && isSameTarget) {
        closeModal().then(() => onRequestClose());
      }
    }
  };
  const modal =
  /*#__PURE__*/
  // eslint-disable-next-line jsx-a11y/no-static-element-interactions
  (0,jsx_runtime.jsx)("div", {
    ref: (0,use_merge_refs/* default */.A)([ref, forwardedRef]),
    className: (0,clsx/* default */.A)('components-modal__screen-overlay', overlayClassname, overlayClassnameProp),
    onKeyDown: (0,with_ignore_ime_events/* withIgnoreIMEEvents */.n)(handleEscapeKeyDown),
    ...(shouldCloseOnClickOutside ? overlayPressHandlers : {}),
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(style_provider/* default */.A, {
      document: document,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('components-modal__frame', sizeClass, className),
        style: {
          ...frameStyle,
          ...style
        },
        ref: (0,use_merge_refs/* default */.A)([frameRef, constrainedTabbingRef, focusReturnRef, focusOnMount !== 'firstContentElement' ? focusOnMountRef : null]),
        role: role,
        "aria-label": contentLabel,
        "aria-labelledby": contentLabel ? undefined : headingId,
        "aria-describedby": aria.describedby,
        tabIndex: -1,
        onKeyDown: onKeyDown,
        children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: (0,clsx/* default */.A)('components-modal__content', {
            'hide-header': __experimentalHideHeader,
            'is-scrollable': hasScrollableContent,
            'has-scrolled-content': hasScrolledContent
          }),
          role: "document",
          onScroll: onContentContainerScroll,
          ref: contentRef,
          "aria-label": hasScrollableContent ? (0,build_module.__)('Scrollable section') : undefined,
          tabIndex: hasScrollableContent ? 0 : undefined,
          children: [!__experimentalHideHeader && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
            className: "components-modal__header",
            children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
              className: "components-modal__header-heading-container",
              children: [icon && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                className: "components-modal__icon-container",
                "aria-hidden": true,
                children: icon
              }), title && /*#__PURE__*/(0,jsx_runtime.jsx)("h1", {
                id: headingId,
                className: "components-modal__header-heading",
                children: title
              })]
            }), headerActions, isDismissible && /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
              children: [/*#__PURE__*/(0,jsx_runtime.jsx)(spacer_component/* default */.A, {
                marginBottom: 0,
                marginLeft: 3
              }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                size: "small",
                onClick: event => closeModal().then(() => onRequestClose(event)),
                icon: library_close/* default */.A,
                label: closeButtonLabel || (0,build_module.__)('Close')
              })]
            })]
          }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
            ref: (0,use_merge_refs/* default */.A)([childrenContainerRef, focusOnMount === 'firstContentElement' ? focusOnMountRef : null]),
            children: children
          })]
        })
      })
    })
  });
  return (0,react_dom.createPortal)( /*#__PURE__*/(0,jsx_runtime.jsx)(ModalContext.Provider, {
    value: nestedDismissers,
    children: modal
  }), document.body);
}

/**
 * Modals give users information and choices related to a task they’re trying to
 * accomplish. They can contain critical information, require decisions, or
 * involve multiple tasks.
 *
 * ```jsx
 * import { Button, Modal } from '@wordpress/components';
 * import { useState } from '@wordpress/element';
 *
 * const MyModal = () => {
 *   const [ isOpen, setOpen ] = useState( false );
 *   const openModal = () => setOpen( true );
 *   const closeModal = () => setOpen( false );
 *
 *   return (
 *     <>
 *       <Button variant="secondary" onClick={ openModal }>
 *         Open Modal
 *       </Button>
 *       { isOpen && (
 *         <Modal title="This is my modal" onRequestClose={ closeModal }>
 *           <Button variant="secondary" onClick={ closeModal }>
 *             My custom close button
 *           </Button>
 *         </Modal>
 *       ) }
 *     </>
 *   );
 * };
 * ```
 */
const Modal = (0,react.forwardRef)(UnforwardedModal);
/* harmony default export */ const modal = (Modal);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/more-vertical.js
/**
 * WordPress dependencies
 */


const moreVertical = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z"
  })
});
/* harmony default export */ const more_vertical = (moreVertical);
//# sourceMappingURL=more-vertical.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/redux@4.2.1/node_modules/redux/es/redux.js + 5 modules
var redux = __webpack_require__("../../node_modules/.pnpm/redux@4.2.1/node_modules/redux/es/redux.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js
var equivalent_key_map = __webpack_require__("../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js");
var equivalent_key_map_default = /*#__PURE__*/__webpack_require__.n(equivalent_key_map);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@5.8.1_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/is-generator.js
/* eslint-disable jsdoc/valid-types */
/**
 * Returns true if the given object is a generator, or false otherwise.
 *
 * @see https://www.ecma-international.org/ecma-262/6.0/#sec-generator-objects
 *
 * @param {any} object Object to test.
 *
 * @return {object is Generator} Whether object is a generator.
 */
function isGenerator(object) {
  /* eslint-enable jsdoc/valid-types */
  // Check that iterator (next) and iterable (Symbol.iterator) interfaces are satisfied.
  // These checks seem to be compatible with several generator helpers as well as the native implementation.
  return !!object && typeof object[Symbol.iterator] === 'function' && typeof object.next === 'function';
}
//# sourceMappingURL=is-generator.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs
var is_promise = __webpack_require__("../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs
var is_plain_object = __webpack_require__("../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@5.8.1_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/is-action.js
/**
 * External dependencies
 */


/* eslint-disable jsdoc/valid-types */
/**
 * Returns true if the given object quacks like an action.
 *
 * @param {any} object Object to test
 *
 * @return {object is import('redux').AnyAction}  Whether object is an action.
 */
function isAction(object) {
  return (0,is_plain_object/* isPlainObject */.Q)(object) && typeof object.type === 'string';
}

/**
 * Returns true if the given object quacks like an action and has a specific
 * action type
 *
 * @param {unknown} object       Object to test
 * @param {string}  expectedType The expected type for the action.
 *
 * @return {object is import('redux').AnyAction} Whether object is an action and is of specific type.
 */
function isActionOfType(object, expectedType) {
  /* eslint-enable jsdoc/valid-types */
  return isAction(object) && object.type === expectedType;
}
//# sourceMappingURL=is-action.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@5.8.1_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/runtime.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Create a co-routine runtime.
 *
 * @param controls Object of control handlers.
 * @param dispatch Unhandled action dispatch.
 */
function createRuntime(controls = {}, dispatch) {
  const rungenControls = Object.entries(controls).map(([actionType, control]) => (value, next, iterate, yieldNext, yieldError) => {
    if (!isActionOfType(value, actionType)) {
      return false;
    }
    const routine = control(value);
    if ((0,is_promise/* default */.A)(routine)) {
      // Async control routine awaits resolution.
      routine.then(yieldNext, yieldError);
    } else {
      yieldNext(routine);
    }
    return true;
  });
  const unhandledActionControl = (value, next) => {
    if (!isAction(value)) {
      return false;
    }
    dispatch(value);
    next();
    return true;
  };
  rungenControls.push(unhandledActionControl);
  const rungenRuntime = (0,dist.create)(rungenControls);
  return action => new Promise((resolve, reject) => rungenRuntime(action, result => {
    if (isAction(result)) {
      dispatch(result);
    }
    resolve(result);
  }, reject));
}
//# sourceMappingURL=runtime.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@5.8.1_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/index.js
/**
 * Internal dependencies
 */



/**
 * Creates a Redux middleware, given an object of controls where each key is an
 * action type for which to act upon, the value a function which returns either
 * a promise which is to resolve when evaluation of the action should continue,
 * or a value. The value or resolved promise value is assigned on the return
 * value of the yield assignment. If the control handler returns undefined, the
 * execution is not continued.
 *
 * @param {Record<string, (value: import('redux').AnyAction) => Promise<boolean> | boolean>} controls Object of control handlers.
 *
 * @return {import('redux').Middleware} Co-routine runtime
 */
function createMiddleware(controls = {}) {
  return store => {
    const runtime = createRuntime(controls, store.dispatch);
    return next => action => {
      if (!isGenerator(action)) {
        return next(action);
      }
      return runtime(action);
    };
  };
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pipe.js
/**
 * Parts of this source were derived and modified from lodash,
 * released under the MIT license.
 *
 * https://github.com/lodash/lodash
 *
 * Copyright JS Foundation and other contributors <https://js.foundation/>
 *
 * Based on Underscore.js, copyright Jeremy Ashkenas,
 * DocumentCloud and Investigative Reporters & Editors <http://underscorejs.org/>
 *
 * This software consists of voluntary contributions made by many
 * individuals. For exact contribution history, see the revision history
 * available at https://github.com/lodash/lodash
 *
 * The following license applies to all parts of this software except as
 * documented below:
 *
 * ====
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Creates a pipe function.
 *
 * Allows to choose whether to perform left-to-right or right-to-left composition.
 *
 * @see https://lodash.com/docs/4#flow
 *
 * @param {boolean} reverse True if right-to-left, false for left-to-right composition.
 */
const basePipe = (reverse = false) => (...funcs) => (...args) => {
  const functions = funcs.flat();
  if (reverse) {
    functions.reverse();
  }
  return functions.reduce((prev, func) => [func(...prev)], args)[0];
};

/**
 * Composes multiple higher-order components into a single higher-order component. Performs left-to-right function
 * composition, where each successive invocation is supplied the return value of the previous.
 *
 * This is inspired by `lodash`'s `flow` function.
 *
 * @see https://lodash.com/docs/4#flow
 */
const pipe = basePipe();

/* harmony default export */ const higher_order_pipe = ((/* unused pure expression or super */ null && (pipe)));
//# sourceMappingURL=pipe.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js
/**
 * Internal dependencies
 */


/**
 * Composes multiple higher-order components into a single higher-order component. Performs right-to-left function
 * composition, where each successive invocation is supplied the return value of the previous.
 *
 * This is inspired by `lodash`'s `flowRight` function.
 *
 * @see https://lodash.com/docs/4#flow-right
 */
const compose = basePipe(true);
/* harmony default export */ const higher_order_compose = (compose);
//# sourceMappingURL=compose.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/combine-reducers.js
function combineReducers(reducers) {
  const keys = Object.keys(reducers);
  return function combinedReducer(state = {}, action) {
    const nextState = {};
    let hasChanged = false;
    for (const key of keys) {
      const reducer = reducers[key];
      const prevStateForKey = state[key];
      const nextStateForKey = reducer(prevStateForKey, action);
      nextState[key] = nextStateForKey;
      hasChanged = hasChanged || nextStateForKey !== prevStateForKey;
    }
    return hasChanged ? nextState : state;
  };
}
//# sourceMappingURL=combine-reducers.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/factory.js
/**
 * Creates a selector function that takes additional curried argument with the
 * registry `select` function. While a regular selector has signature
 * ```js
 * ( state, ...selectorArgs ) => ( result )
 * ```
 * that allows to select data from the store's `state`, a registry selector
 * has signature:
 * ```js
 * ( select ) => ( state, ...selectorArgs ) => ( result )
 * ```
 * that supports also selecting from other registered stores.
 *
 * @example
 * ```js
 * import { store as coreStore } from '@wordpress/core-data';
 * import { store as editorStore } from '@wordpress/editor';
 *
 * const getCurrentPostId = createRegistrySelector( ( select ) => ( state ) => {
 *   return select( editorStore ).getCurrentPostId();
 * } );
 *
 * const getPostEdits = createRegistrySelector( ( select ) => ( state ) => {
 *   // calling another registry selector just like any other function
 *   const postType = getCurrentPostType( state );
 *   const postId = getCurrentPostId( state );
 *	 return select( coreStore ).getEntityRecordEdits( 'postType', postType, postId );
 * } );
 * ```
 *
 * Note how the `getCurrentPostId` selector can be called just like any other function,
 * (it works even inside a regular non-registry selector) and we don't need to pass the
 * registry as argument. The registry binding happens automatically when registering the selector
 * with a store.
 *
 * @param {Function} registrySelector Function receiving a registry `select`
 *                                    function and returning a state selector.
 *
 * @return {Function} Registry selector that can be registered with a store.
 */
function createRegistrySelector(registrySelector) {
  const selectorsByRegistry = new WeakMap();
  // Create a selector function that is bound to the registry referenced by `selector.registry`
  // and that has the same API as a regular selector. Binding it in such a way makes it
  // possible to call the selector directly from another selector.
  const wrappedSelector = (...args) => {
    let selector = selectorsByRegistry.get(wrappedSelector.registry);
    // We want to make sure the cache persists even when new registry
    // instances are created. For example patterns create their own editors
    // with their own core/block-editor stores, so we should keep track of
    // the cache for each registry instance.
    if (!selector) {
      selector = registrySelector(wrappedSelector.registry.select);
      selectorsByRegistry.set(wrappedSelector.registry, selector);
    }
    return selector(...args);
  };

  /**
   * Flag indicating that the selector is a registry selector that needs the correct registry
   * reference to be assigned to `selector.registry` to make it work correctly.
   * be mapped as a registry selector.
   *
   * @type {boolean}
   */
  wrappedSelector.isRegistrySelector = true;
  return wrappedSelector;
}

/**
 * Creates a control function that takes additional curried argument with the `registry` object.
 * While a regular control has signature
 * ```js
 * ( action ) => ( iteratorOrPromise )
 * ```
 * where the control works with the `action` that it's bound to, a registry control has signature:
 * ```js
 * ( registry ) => ( action ) => ( iteratorOrPromise )
 * ```
 * A registry control is typically used to select data or dispatch an action to a registered
 * store.
 *
 * When registering a control created with `createRegistryControl` with a store, the store
 * knows which calling convention to use when executing the control.
 *
 * @param {Function} registryControl Function receiving a registry object and returning a control.
 *
 * @return {Function} Registry control that can be registered with a store.
 */
function createRegistryControl(registryControl) {
  registryControl.isRegistryControl = true;
  return registryControl;
}
//# sourceMappingURL=factory.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/controls.js
/**
 * Internal dependencies
 */


/** @typedef {import('./types').StoreDescriptor} StoreDescriptor */

const SELECT = '@@data/SELECT';
const RESOLVE_SELECT = '@@data/RESOLVE_SELECT';
const DISPATCH = '@@data/DISPATCH';
function isObject(object) {
  return object !== null && typeof object === 'object';
}

/**
 * Dispatches a control action for triggering a synchronous registry select.
 *
 * Note: This control synchronously returns the current selector value, triggering the
 * resolution, but not waiting for it.
 *
 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
 * @param {string}                 selectorName          The name of the selector.
 * @param {Array}                  args                  Arguments for the selector.
 *
 * @example
 * ```js
 * import { controls } from '@wordpress/data';
 *
 * // Action generator using `select`.
 * export function* myAction() {
 *   const isEditorSideBarOpened = yield controls.select( 'core/edit-post', 'isEditorSideBarOpened' );
 *   // Do stuff with the result from the `select`.
 * }
 * ```
 *
 * @return {Object} The control descriptor.
 */
function controls_select(storeNameOrDescriptor, selectorName, ...args) {
  return {
    type: SELECT,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}

/**
 * Dispatches a control action for triggering and resolving a registry select.
 *
 * Note: when this control action is handled, it automatically considers
 * selectors that may have a resolver. In such case, it will return a `Promise` that resolves
 * after the selector finishes resolving, with the final result value.
 *
 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
 * @param {string}                 selectorName          The name of the selector
 * @param {Array}                  args                  Arguments for the selector.
 *
 * @example
 * ```js
 * import { controls } from '@wordpress/data';
 *
 * // Action generator using resolveSelect
 * export function* myAction() {
 * 	const isSidebarOpened = yield controls.resolveSelect( 'core/edit-post', 'isEditorSideBarOpened' );
 * 	// do stuff with the result from the select.
 * }
 * ```
 *
 * @return {Object} The control descriptor.
 */
function resolveSelect(storeNameOrDescriptor, selectorName, ...args) {
  return {
    type: RESOLVE_SELECT,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}

/**
 * Dispatches a control action for triggering a registry dispatch.
 *
 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
 * @param {string}                 actionName            The name of the action to dispatch
 * @param {Array}                  args                  Arguments for the dispatch action.
 *
 * @example
 * ```js
 * import { controls } from '@wordpress/data-controls';
 *
 * // Action generator using dispatch
 * export function* myAction() {
 *   yield controls.dispatch( 'core/editor', 'togglePublishSidebar' );
 *   // do some other things.
 * }
 * ```
 *
 * @return {Object}  The control descriptor.
 */
function dispatch(storeNameOrDescriptor, actionName, ...args) {
  return {
    type: DISPATCH,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    actionName,
    args
  };
}
const controls = {
  select: controls_select,
  resolveSelect,
  dispatch
};
const builtinControls = {
  [SELECT]: createRegistryControl(registry => ({
    storeKey,
    selectorName,
    args
  }) => registry.select(storeKey)[selectorName](...args)),
  [RESOLVE_SELECT]: createRegistryControl(registry => ({
    storeKey,
    selectorName,
    args
  }) => {
    const method = registry.select(storeKey)[selectorName].hasResolver ? 'resolveSelect' : 'select';
    return registry[method](storeKey)[selectorName](...args);
  }),
  [DISPATCH]: createRegistryControl(registry => ({
    storeKey,
    actionName,
    args
  }) => registry.dispatch(storeKey)[actionName](...args))
};
//# sourceMappingURL=controls.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/lock-unlock.js
/**
 * WordPress dependencies
 */

const {
  lock: data_build_module_lock_unlock_lock,
  unlock: data_build_module_lock_unlock_unlock
} = __dangerousOptInToUnstableAPIsOnlyForCoreModules('I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.', '@wordpress/data');
//# sourceMappingURL=lock-unlock.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/promise-middleware.js
/**
 * External dependencies
 */


/**
 * Simplest possible promise redux middleware.
 *
 * @type {import('redux').Middleware}
 */
const promiseMiddleware = () => next => action => {
  if ((0,is_promise/* default */.A)(action)) {
    return action.then(resolvedAction => {
      if (resolvedAction) {
        return next(resolvedAction);
      }
    });
  }
  return next(action);
};
/* harmony default export */ const promise_middleware = (promiseMiddleware);
//# sourceMappingURL=promise-middleware.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/resolvers-cache-middleware.js
/** @typedef {import('./registry').WPDataRegistry} WPDataRegistry */

/**
 * Creates a middleware handling resolvers cache invalidation.
 *
 * @param {WPDataRegistry} registry  Registry for which to create the middleware.
 * @param {string}         storeName Name of the store for which to create the middleware.
 *
 * @return {Function} Middleware function.
 */
const createResolversCacheMiddleware = (registry, storeName) => () => next => action => {
  const resolvers = registry.select(storeName).getCachedResolvers();
  const resolverEntries = Object.entries(resolvers);
  resolverEntries.forEach(([selectorName, resolversByArgs]) => {
    const resolver = registry.stores[storeName]?.resolvers?.[selectorName];
    if (!resolver || !resolver.shouldInvalidate) {
      return;
    }
    resolversByArgs.forEach((value, args) => {
      // Works around a bug in `EquivalentKeyMap` where `map.delete` merely sets an entry value
      // to `undefined` and `map.forEach` then iterates also over these orphaned entries.
      if (value === undefined) {
        return;
      }

      // resolversByArgs is the map Map([ args ] => boolean) storing the cache resolution status for a given selector.
      // If the value is "finished" or "error" it means this resolver has finished its resolution which means we need
      // to invalidate it, if it's true it means it's inflight and the invalidation is not necessary.
      if (value.status !== 'finished' && value.status !== 'error') {
        return;
      }
      if (!resolver.shouldInvalidate(action, ...args)) {
        return;
      }

      // Trigger cache invalidation
      registry.dispatch(storeName).invalidateResolution(selectorName, args);
    });
  });
  return next(action);
};
/* harmony default export */ const resolvers_cache_middleware = (createResolversCacheMiddleware);
//# sourceMappingURL=resolvers-cache-middleware.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/thunk-middleware.js
function createThunkMiddleware(args) {
  return () => next => action => {
    if (typeof action === 'function') {
      return action(args);
    }
    return next(action);
  };
}
//# sourceMappingURL=thunk-middleware.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/utils.js
/**
 * External dependencies
 */

/**
 * Higher-order reducer creator which creates a combined reducer object, keyed
 * by a property on the action object.
 *
 * @param actionProperty Action property by which to key object.
 * @return Higher-order reducer.
 */
const onSubKey = actionProperty => reducer => (state = {}, action) => {
  // Retrieve subkey from action. Do not track if undefined; useful for cases
  // where reducer is scoped by action shape.
  const key = action[actionProperty];
  if (key === undefined) {
    return state;
  }

  // Avoid updating state if unchanged. Note that this also accounts for a
  // reducer which returns undefined on a key which is not yet tracked.
  const nextKeyState = reducer(state[key], action);
  if (nextKeyState === state[key]) {
    return state;
  }
  return {
    ...state,
    [key]: nextKeyState
  };
};

/**
 * Normalize selector argument array by defaulting `undefined` value to an empty array
 * and removing trailing `undefined` values.
 *
 * @param args Selector argument array
 * @return Normalized state key array
 */
function selectorArgsToStateKey(args) {
  if (args === undefined || args === null) {
    return [];
  }
  const len = args.length;
  let idx = len;
  while (idx > 0 && args[idx - 1] === undefined) {
    idx--;
  }
  return idx === len ? args : args.slice(0, idx);
}
//# sourceMappingURL=utils.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/reducer.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Reducer function returning next state for selector resolution of
 * subkeys, object form:
 *
 *  selectorName -> EquivalentKeyMap<Array,boolean>
 */
const subKeysIsResolved = onSubKey('selectorName')((state = new (equivalent_key_map_default())(), action) => {
  switch (action.type) {
    case 'START_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.set(selectorArgsToStateKey(action.args), {
          status: 'resolving'
        });
        return nextState;
      }
    case 'FINISH_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.set(selectorArgsToStateKey(action.args), {
          status: 'finished'
        });
        return nextState;
      }
    case 'FAIL_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.set(selectorArgsToStateKey(action.args), {
          status: 'error',
          error: action.error
        });
        return nextState;
      }
    case 'START_RESOLUTIONS':
      {
        const nextState = new (equivalent_key_map_default())(state);
        for (const resolutionArgs of action.args) {
          nextState.set(selectorArgsToStateKey(resolutionArgs), {
            status: 'resolving'
          });
        }
        return nextState;
      }
    case 'FINISH_RESOLUTIONS':
      {
        const nextState = new (equivalent_key_map_default())(state);
        for (const resolutionArgs of action.args) {
          nextState.set(selectorArgsToStateKey(resolutionArgs), {
            status: 'finished'
          });
        }
        return nextState;
      }
    case 'FAIL_RESOLUTIONS':
      {
        const nextState = new (equivalent_key_map_default())(state);
        action.args.forEach((resolutionArgs, idx) => {
          const resolutionState = {
            status: 'error',
            error: undefined
          };
          const error = action.errors[idx];
          if (error) {
            resolutionState.error = error;
          }
          nextState.set(selectorArgsToStateKey(resolutionArgs), resolutionState);
        });
        return nextState;
      }
    case 'INVALIDATE_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.delete(selectorArgsToStateKey(action.args));
        return nextState;
      }
  }
  return state;
});

/**
 * Reducer function returning next state for selector resolution, object form:
 *
 *   selectorName -> EquivalentKeyMap<Array, boolean>
 *
 * @param state  Current state.
 * @param action Dispatched action.
 *
 * @return Next state.
 */
const isResolved = (state = {}, action) => {
  switch (action.type) {
    case 'INVALIDATE_RESOLUTION_FOR_STORE':
      return {};
    case 'INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR':
      {
        if (action.selectorName in state) {
          const {
            [action.selectorName]: removedSelector,
            ...restState
          } = state;
          return restState;
        }
        return state;
      }
    case 'START_RESOLUTION':
    case 'FINISH_RESOLUTION':
    case 'FAIL_RESOLUTION':
    case 'START_RESOLUTIONS':
    case 'FINISH_RESOLUTIONS':
    case 'FAIL_RESOLUTIONS':
    case 'INVALIDATE_RESOLUTION':
      return subKeysIsResolved(state, action);
  }
  return state;
};
/* harmony default export */ const metadata_reducer = (isResolved);
//# sourceMappingURL=reducer.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js
var rememo = __webpack_require__("../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



/** @typedef {Record<string, import('./reducer').State>} State */
/** @typedef {import('./reducer').StateValue} StateValue */
/** @typedef {import('./reducer').Status} Status */

/**
 * Returns the raw resolution state value for a given selector name,
 * and arguments set. May be undefined if the selector has never been resolved
 * or not resolved for the given set of arguments, otherwise true or false for
 * resolution started and completed respectively.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {StateValue|undefined} isResolving value.
 */
function getResolutionState(state, selectorName, args) {
  const map = state[selectorName];
  if (!map) {
    return;
  }
  return map.get(selectorArgsToStateKey(args));
}

/**
 * Returns an `isResolving`-like value for a given selector name and arguments set.
 * Its value is either `undefined` if the selector has never been resolved or has been
 * invalidated, or a `true`/`false` boolean value if the resolution is in progress or
 * has finished, respectively.
 *
 * This is a legacy selector that was implemented when the "raw" internal data had
 * this `undefined | boolean` format. Nowadays the internal value is an object that
 * can be retrieved with `getResolutionState`.
 *
 * @deprecated
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean | undefined} isResolving value.
 */
function getIsResolving(state, selectorName, args) {
  (0,deprecated_build_module/* default */.A)('wp.data.select( store ).getIsResolving', {
    since: '6.6',
    version: '6.8',
    alternative: 'wp.data.select( store ).getResolutionState'
  });
  const resolutionState = getResolutionState(state, selectorName, args);
  return resolutionState && resolutionState.status === 'resolving';
}

/**
 * Returns true if resolution has already been triggered for a given
 * selector name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Whether resolution has been triggered.
 */
function hasStartedResolution(state, selectorName, args) {
  return getResolutionState(state, selectorName, args) !== undefined;
}

/**
 * Returns true if resolution has completed for a given selector
 * name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Whether resolution has completed.
 */
function hasFinishedResolution(state, selectorName, args) {
  const status = getResolutionState(state, selectorName, args)?.status;
  return status === 'finished' || status === 'error';
}

/**
 * Returns true if resolution has failed for a given selector
 * name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Has resolution failed
 */
function hasResolutionFailed(state, selectorName, args) {
  return getResolutionState(state, selectorName, args)?.status === 'error';
}

/**
 * Returns the resolution error for a given selector name, and arguments set.
 * Note it may be of an Error type, but may also be null, undefined, or anything else
 * that can be `throw`-n.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {Error|unknown} Last resolution error
 */
function getResolutionError(state, selectorName, args) {
  const resolutionState = getResolutionState(state, selectorName, args);
  return resolutionState?.status === 'error' ? resolutionState.error : null;
}

/**
 * Returns true if resolution has been triggered but has not yet completed for
 * a given selector name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Whether resolution is in progress.
 */
function isResolving(state, selectorName, args) {
  return getResolutionState(state, selectorName, args)?.status === 'resolving';
}

/**
 * Returns the list of the cached resolvers.
 *
 * @param {State} state Data state.
 *
 * @return {State} Resolvers mapped by args and selectorName.
 */
function getCachedResolvers(state) {
  return state;
}

/**
 * Whether the store has any currently resolving selectors.
 *
 * @param {State} state Data state.
 *
 * @return {boolean} True if one or more selectors are resolving, false otherwise.
 */
function hasResolvingSelectors(state) {
  return Object.values(state).some(selectorState =>
  /**
   * This uses the internal `_map` property of `EquivalentKeyMap` for
   * optimization purposes, since the `EquivalentKeyMap` implementation
   * does not support a `.values()` implementation.
   *
   * @see https://github.com/aduth/equivalent-key-map
   */
  Array.from(selectorState._map.values()).some(resolution => resolution[1]?.status === 'resolving'));
}

/**
 * Retrieves the total number of selectors, grouped per status.
 *
 * @param {State} state Data state.
 *
 * @return {Object} Object, containing selector totals by status.
 */
const countSelectorsByStatus = (0,rememo/* default */.A)(state => {
  const selectorsByStatus = {};
  Object.values(state).forEach(selectorState =>
  /**
   * This uses the internal `_map` property of `EquivalentKeyMap` for
   * optimization purposes, since the `EquivalentKeyMap` implementation
   * does not support a `.values()` implementation.
   *
   * @see https://github.com/aduth/equivalent-key-map
   */
  Array.from(selectorState._map.values()).forEach(resolution => {
    var _resolution$1$status;
    const currentStatus = (_resolution$1$status = resolution[1]?.status) !== null && _resolution$1$status !== void 0 ? _resolution$1$status : 'error';
    if (!selectorsByStatus[currentStatus]) {
      selectorsByStatus[currentStatus] = 0;
    }
    selectorsByStatus[currentStatus]++;
  }));
  return selectorsByStatus;
}, state => [state]);
//# sourceMappingURL=selectors.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.js
/**
 * Returns an action object used in signalling that selector resolution has
 * started.
 *
 * @param {string}    selectorName Name of selector for which resolver triggered.
 * @param {unknown[]} args         Arguments to associate for uniqueness.
 *
 * @return {{ type: 'START_RESOLUTION', selectorName: string, args: unknown[] }} Action object.
 */
function startResolution(selectorName, args) {
  return {
    type: 'START_RESOLUTION',
    selectorName,
    args
  };
}

/**
 * Returns an action object used in signalling that selector resolution has
 * completed.
 *
 * @param {string}    selectorName Name of selector for which resolver triggered.
 * @param {unknown[]} args         Arguments to associate for uniqueness.
 *
 * @return {{ type: 'FINISH_RESOLUTION', selectorName: string, args: unknown[] }} Action object.
 */
function finishResolution(selectorName, args) {
  return {
    type: 'FINISH_RESOLUTION',
    selectorName,
    args
  };
}

/**
 * Returns an action object used in signalling that selector resolution has
 * failed.
 *
 * @param {string}        selectorName Name of selector for which resolver triggered.
 * @param {unknown[]}     args         Arguments to associate for uniqueness.
 * @param {Error|unknown} error        The error that caused the failure.
 *
 * @return {{ type: 'FAIL_RESOLUTION', selectorName: string, args: unknown[], error: Error|unknown }} Action object.
 */
function failResolution(selectorName, args, error) {
  return {
    type: 'FAIL_RESOLUTION',
    selectorName,
    args,
    error
  };
}

/**
 * Returns an action object used in signalling that a batch of selector resolutions has
 * started.
 *
 * @param {string}      selectorName Name of selector for which resolver triggered.
 * @param {unknown[][]} args         Array of arguments to associate for uniqueness, each item
 *                                   is associated to a resolution.
 *
 * @return {{ type: 'START_RESOLUTIONS', selectorName: string, args: unknown[][] }} Action object.
 */
function startResolutions(selectorName, args) {
  return {
    type: 'START_RESOLUTIONS',
    selectorName,
    args
  };
}

/**
 * Returns an action object used in signalling that a batch of selector resolutions has
 * completed.
 *
 * @param {string}      selectorName Name of selector for which resolver triggered.
 * @param {unknown[][]} args         Array of arguments to associate for uniqueness, each item
 *                                   is associated to a resolution.
 *
 * @return {{ type: 'FINISH_RESOLUTIONS', selectorName: string, args: unknown[][] }} Action object.
 */
function finishResolutions(selectorName, args) {
  return {
    type: 'FINISH_RESOLUTIONS',
    selectorName,
    args
  };
}

/**
 * Returns an action object used in signalling that a batch of selector resolutions has
 * completed and at least one of them has failed.
 *
 * @param {string}            selectorName Name of selector for which resolver triggered.
 * @param {unknown[]}         args         Array of arguments to associate for uniqueness, each item
 *                                         is associated to a resolution.
 * @param {(Error|unknown)[]} errors       Array of errors to associate for uniqueness, each item
 *                                         is associated to a resolution.
 * @return {{ type: 'FAIL_RESOLUTIONS', selectorName: string, args: unknown[], errors: Array<Error|unknown> }} Action object.
 */
function failResolutions(selectorName, args, errors) {
  return {
    type: 'FAIL_RESOLUTIONS',
    selectorName,
    args,
    errors
  };
}

/**
 * Returns an action object used in signalling that we should invalidate the resolution cache.
 *
 * @param {string}    selectorName Name of selector for which resolver should be invalidated.
 * @param {unknown[]} args         Arguments to associate for uniqueness.
 *
 * @return {{ type: 'INVALIDATE_RESOLUTION', selectorName: string, args: any[] }} Action object.
 */
function invalidateResolution(selectorName, args) {
  return {
    type: 'INVALIDATE_RESOLUTION',
    selectorName,
    args
  };
}

/**
 * Returns an action object used in signalling that the resolution
 * should be invalidated.
 *
 * @return {{ type: 'INVALIDATE_RESOLUTION_FOR_STORE' }} Action object.
 */
function invalidateResolutionForStore() {
  return {
    type: 'INVALIDATE_RESOLUTION_FOR_STORE'
  };
}

/**
 * Returns an action object used in signalling that the resolution cache for a
 * given selectorName should be invalidated.
 *
 * @param {string} selectorName Name of selector for which all resolvers should
 *                              be invalidated.
 *
 * @return  {{ type: 'INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR', selectorName: string }} Action object.
 */
function invalidateResolutionForStoreSelector(selectorName) {
  return {
    type: 'INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR',
    selectorName
  };
}
//# sourceMappingURL=actions.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js
/**
 * External dependencies
 */



/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */











/** @typedef {import('../types').DataRegistry} DataRegistry */
/** @typedef {import('../types').ListenerFunction} ListenerFunction */
/**
 * @typedef {import('../types').StoreDescriptor<C>} StoreDescriptor
 * @template {import('../types').AnyConfig} C
 */
/**
 * @typedef {import('../types').ReduxStoreConfig<State,Actions,Selectors>} ReduxStoreConfig
 * @template State
 * @template {Record<string,import('../types').ActionCreator>} Actions
 * @template Selectors
 */

const trimUndefinedValues = array => {
  const result = [...array];
  for (let i = result.length - 1; i >= 0; i--) {
    if (result[i] === undefined) {
      result.splice(i, 1);
    }
  }
  return result;
};

/**
 * Creates a new object with the same keys, but with `callback()` called as
 * a transformer function on each of the values.
 *
 * @param {Object}   obj      The object to transform.
 * @param {Function} callback The function to transform each object value.
 * @return {Array} Transformed object.
 */
const mapValues = (obj, callback) => Object.fromEntries(Object.entries(obj !== null && obj !== void 0 ? obj : {}).map(([key, value]) => [key, callback(value, key)]));

// Convert  non serializable types to plain objects
const devToolsReplacer = (key, state) => {
  if (state instanceof Map) {
    return Object.fromEntries(state);
  }
  if (state instanceof window.HTMLElement) {
    return null;
  }
  return state;
};

/**
 * Create a cache to track whether resolvers started running or not.
 *
 * @return {Object} Resolvers Cache.
 */
function createResolversCache() {
  const cache = {};
  return {
    isRunning(selectorName, args) {
      return cache[selectorName] && cache[selectorName].get(trimUndefinedValues(args));
    },
    clear(selectorName, args) {
      if (cache[selectorName]) {
        cache[selectorName].delete(trimUndefinedValues(args));
      }
    },
    markAsRunning(selectorName, args) {
      if (!cache[selectorName]) {
        cache[selectorName] = new (equivalent_key_map_default())();
      }
      cache[selectorName].set(trimUndefinedValues(args), true);
    }
  };
}
function createBindingCache(bind) {
  const cache = new WeakMap();
  return {
    get(item, itemName) {
      let boundItem = cache.get(item);
      if (!boundItem) {
        boundItem = bind(item, itemName);
        cache.set(item, boundItem);
      }
      return boundItem;
    }
  };
}

/**
 * Creates a data store descriptor for the provided Redux store configuration containing
 * properties describing reducer, actions, selectors, controls and resolvers.
 *
 * @example
 * ```js
 * import { createReduxStore } from '@wordpress/data';
 *
 * const store = createReduxStore( 'demo', {
 *     reducer: ( state = 'OK' ) => state,
 *     selectors: {
 *         getValue: ( state ) => state,
 *     },
 * } );
 * ```
 *
 * @template State
 * @template {Record<string,import('../types').ActionCreator>} Actions
 * @template Selectors
 * @param {string}                                    key     Unique namespace identifier.
 * @param {ReduxStoreConfig<State,Actions,Selectors>} options Registered store options, with properties
 *                                                            describing reducer, actions, selectors,
 *                                                            and resolvers.
 *
 * @return   {StoreDescriptor<ReduxStoreConfig<State,Actions,Selectors>>} Store Object.
 */
function createReduxStore(key, options) {
  const privateActions = {};
  const privateSelectors = {};
  const privateRegistrationFunctions = {
    privateActions,
    registerPrivateActions: actions => {
      Object.assign(privateActions, actions);
    },
    privateSelectors,
    registerPrivateSelectors: selectors => {
      Object.assign(privateSelectors, selectors);
    }
  };
  const storeDescriptor = {
    name: key,
    instantiate: registry => {
      /**
       * Stores listener functions registered with `subscribe()`.
       *
       * When functions register to listen to store changes with
       * `subscribe()` they get added here. Although Redux offers
       * its own `subscribe()` function directly, by wrapping the
       * subscription in this store instance it's possible to
       * optimize checking if the state has changed before calling
       * each listener.
       *
       * @type {Set<ListenerFunction>}
       */
      const listeners = new Set();
      const reducer = options.reducer;
      const thunkArgs = {
        registry,
        get dispatch() {
          return thunkActions;
        },
        get select() {
          return thunkSelectors;
        },
        get resolveSelect() {
          return getResolveSelectors();
        }
      };
      const store = instantiateReduxStore(key, options, registry, thunkArgs);
      // Expose the private registration functions on the store
      // so they can be copied to a sub registry in registry.js.
      data_build_module_lock_unlock_lock(store, privateRegistrationFunctions);
      const resolversCache = createResolversCache();
      function bindAction(action) {
        return (...args) => Promise.resolve(store.dispatch(action(...args)));
      }
      const actions = {
        ...mapValues(actions_namespaceObject, bindAction),
        ...mapValues(options.actions, bindAction)
      };
      const boundPrivateActions = createBindingCache(bindAction);
      const allActions = new Proxy(() => {}, {
        get: (target, prop) => {
          const privateAction = privateActions[prop];
          return privateAction ? boundPrivateActions.get(privateAction, prop) : actions[prop];
        }
      });
      const thunkActions = new Proxy(allActions, {
        apply: (target, thisArg, [action]) => store.dispatch(action)
      });
      data_build_module_lock_unlock_lock(actions, allActions);
      const resolvers = options.resolvers ? mapResolvers(options.resolvers) : {};
      function bindSelector(selector, selectorName) {
        if (selector.isRegistrySelector) {
          selector.registry = registry;
        }
        const boundSelector = (...args) => {
          args = normalize(selector, args);
          const state = store.__unstableOriginalGetState();
          // Before calling the selector, switch to the correct
          // registry.
          if (selector.isRegistrySelector) {
            selector.registry = registry;
          }
          return selector(state.root, ...args);
        };

        // Expose normalization method on the bound selector
        // in order that it can be called when fullfilling
        // the resolver.
        boundSelector.__unstableNormalizeArgs = selector.__unstableNormalizeArgs;
        const resolver = resolvers[selectorName];
        if (!resolver) {
          boundSelector.hasResolver = false;
          return boundSelector;
        }
        return mapSelectorWithResolver(boundSelector, selectorName, resolver, store, resolversCache);
      }
      function bindMetadataSelector(metaDataSelector) {
        const boundSelector = (...args) => {
          const state = store.__unstableOriginalGetState();
          const originalSelectorName = args && args[0];
          const originalSelectorArgs = args && args[1];
          const targetSelector = options?.selectors?.[originalSelectorName];

          // Normalize the arguments passed to the target selector.
          if (originalSelectorName && targetSelector) {
            args[1] = normalize(targetSelector, originalSelectorArgs);
          }
          return metaDataSelector(state.metadata, ...args);
        };
        boundSelector.hasResolver = false;
        return boundSelector;
      }
      const selectors = {
        ...mapValues(selectors_namespaceObject, bindMetadataSelector),
        ...mapValues(options.selectors, bindSelector)
      };
      const boundPrivateSelectors = createBindingCache(bindSelector);

      // Pre-bind the private selectors that have been registered by the time of
      // instantiation, so that registry selectors are bound to the registry.
      for (const [selectorName, selector] of Object.entries(privateSelectors)) {
        boundPrivateSelectors.get(selector, selectorName);
      }
      const allSelectors = new Proxy(() => {}, {
        get: (target, prop) => {
          const privateSelector = privateSelectors[prop];
          return privateSelector ? boundPrivateSelectors.get(privateSelector, prop) : selectors[prop];
        }
      });
      const thunkSelectors = new Proxy(allSelectors, {
        apply: (target, thisArg, [selector]) => selector(store.__unstableOriginalGetState())
      });
      data_build_module_lock_unlock_lock(selectors, allSelectors);
      const resolveSelectors = mapResolveSelectors(selectors, store);
      const suspendSelectors = mapSuspendSelectors(selectors, store);
      const getSelectors = () => selectors;
      const getActions = () => actions;
      const getResolveSelectors = () => resolveSelectors;
      const getSuspendSelectors = () => suspendSelectors;

      // We have some modules monkey-patching the store object
      // It's wrong to do so but until we refactor all of our effects to controls
      // We need to keep the same "store" instance here.
      store.__unstableOriginalGetState = store.getState;
      store.getState = () => store.__unstableOriginalGetState().root;

      // Customize subscribe behavior to call listeners only on effective change,
      // not on every dispatch.
      const subscribe = store && (listener => {
        listeners.add(listener);
        return () => listeners.delete(listener);
      });
      let lastState = store.__unstableOriginalGetState();
      store.subscribe(() => {
        const state = store.__unstableOriginalGetState();
        const hasChanged = state !== lastState;
        lastState = state;
        if (hasChanged) {
          for (const listener of listeners) {
            listener();
          }
        }
      });

      // This can be simplified to just { subscribe, getSelectors, getActions }
      // Once we remove the use function.
      return {
        reducer,
        store,
        actions,
        selectors,
        resolvers,
        getSelectors,
        getResolveSelectors,
        getSuspendSelectors,
        getActions,
        subscribe
      };
    }
  };

  // Expose the private registration functions on the store
  // descriptor. That's a natural choice since that's where the
  // public actions and selectors are stored .
  data_build_module_lock_unlock_lock(storeDescriptor, privateRegistrationFunctions);
  return storeDescriptor;
}

/**
 * Creates a redux store for a namespace.
 *
 * @param {string}       key       Unique namespace identifier.
 * @param {Object}       options   Registered store options, with properties
 *                                 describing reducer, actions, selectors,
 *                                 and resolvers.
 * @param {DataRegistry} registry  Registry reference.
 * @param {Object}       thunkArgs Argument object for the thunk middleware.
 * @return {Object} Newly created redux store.
 */
function instantiateReduxStore(key, options, registry, thunkArgs) {
  const controls = {
    ...options.controls,
    ...builtinControls
  };
  const normalizedControls = mapValues(controls, control => control.isRegistryControl ? control(registry) : control);
  const middlewares = [resolvers_cache_middleware(registry, key), promise_middleware, createMiddleware(normalizedControls), createThunkMiddleware(thunkArgs)];
  const enhancers = [(0,redux/* applyMiddleware */.Tw)(...middlewares)];
  if (typeof window !== 'undefined' && window.__REDUX_DEVTOOLS_EXTENSION__) {
    enhancers.push(window.__REDUX_DEVTOOLS_EXTENSION__({
      name: key,
      instanceId: key,
      serialize: {
        replacer: devToolsReplacer
      }
    }));
  }
  const {
    reducer,
    initialState
  } = options;
  const enhancedReducer = combineReducers({
    metadata: metadata_reducer,
    root: reducer
  });
  return (0,redux/* createStore */.y$)(enhancedReducer, {
    root: initialState
  }, higher_order_compose(enhancers));
}

/**
 * Maps selectors to functions that return a resolution promise for them
 *
 * @param {Object} selectors Selectors to map.
 * @param {Object} store     The redux store the selectors select from.
 *
 * @return {Object} Selectors mapped to their resolution functions.
 */
function mapResolveSelectors(selectors, store) {
  const {
    getIsResolving,
    hasStartedResolution,
    hasFinishedResolution,
    hasResolutionFailed,
    isResolving,
    getCachedResolvers,
    getResolutionState,
    getResolutionError,
    hasResolvingSelectors,
    countSelectorsByStatus,
    ...storeSelectors
  } = selectors;
  return mapValues(storeSelectors, (selector, selectorName) => {
    // If the selector doesn't have a resolver, just convert the return value
    // (including exceptions) to a Promise, no additional extra behavior is needed.
    if (!selector.hasResolver) {
      return async (...args) => selector.apply(null, args);
    }
    return (...args) => {
      return new Promise((resolve, reject) => {
        const hasFinished = () => selectors.hasFinishedResolution(selectorName, args);
        const finalize = result => {
          const hasFailed = selectors.hasResolutionFailed(selectorName, args);
          if (hasFailed) {
            const error = selectors.getResolutionError(selectorName, args);
            reject(error);
          } else {
            resolve(result);
          }
        };
        const getResult = () => selector.apply(null, args);
        // Trigger the selector (to trigger the resolver)
        const result = getResult();
        if (hasFinished()) {
          return finalize(result);
        }
        const unsubscribe = store.subscribe(() => {
          if (hasFinished()) {
            unsubscribe();
            finalize(getResult());
          }
        });
      });
    };
  });
}

/**
 * Maps selectors to functions that throw a suspense promise if not yet resolved.
 *
 * @param {Object} selectors Selectors to map.
 * @param {Object} store     The redux store the selectors select from.
 *
 * @return {Object} Selectors mapped to their suspense functions.
 */
function mapSuspendSelectors(selectors, store) {
  return mapValues(selectors, (selector, selectorName) => {
    // Selector without a resolver doesn't have any extra suspense behavior.
    if (!selector.hasResolver) {
      return selector;
    }
    return (...args) => {
      const result = selector.apply(null, args);
      if (selectors.hasFinishedResolution(selectorName, args)) {
        if (selectors.hasResolutionFailed(selectorName, args)) {
          throw selectors.getResolutionError(selectorName, args);
        }
        return result;
      }
      throw new Promise(resolve => {
        const unsubscribe = store.subscribe(() => {
          if (selectors.hasFinishedResolution(selectorName, args)) {
            resolve();
            unsubscribe();
          }
        });
      });
    };
  });
}

/**
 * Convert resolvers to a normalized form, an object with `fulfill` method and
 * optional methods like `isFulfilled`.
 *
 * @param {Object} resolvers Resolver to convert
 */
function mapResolvers(resolvers) {
  return mapValues(resolvers, resolver => {
    if (resolver.fulfill) {
      return resolver;
    }
    return {
      ...resolver,
      // Copy the enumerable properties of the resolver function.
      fulfill: resolver // Add the fulfill method.
    };
  });
}

/**
 * Returns a selector with a matched resolver.
 * Resolvers are side effects invoked once per argument set of a given selector call,
 * used in ensuring that the data needs for the selector are satisfied.
 *
 * @param {Object} selector       The selector function to be bound.
 * @param {string} selectorName   The selector name.
 * @param {Object} resolver       Resolver to call.
 * @param {Object} store          The redux store to which the resolvers should be mapped.
 * @param {Object} resolversCache Resolvers Cache.
 */
function mapSelectorWithResolver(selector, selectorName, resolver, store, resolversCache) {
  function fulfillSelector(args) {
    const state = store.getState();
    if (resolversCache.isRunning(selectorName, args) || typeof resolver.isFulfilled === 'function' && resolver.isFulfilled(state, ...args)) {
      return;
    }
    const {
      metadata
    } = store.__unstableOriginalGetState();
    if (hasStartedResolution(metadata, selectorName, args)) {
      return;
    }
    resolversCache.markAsRunning(selectorName, args);
    setTimeout(async () => {
      resolversCache.clear(selectorName, args);
      store.dispatch(startResolution(selectorName, args));
      try {
        const action = resolver.fulfill(...args);
        if (action) {
          await store.dispatch(action);
        }
        store.dispatch(finishResolution(selectorName, args));
      } catch (error) {
        store.dispatch(failResolution(selectorName, args, error));
      }
    }, 0);
  }
  const selectorResolver = (...args) => {
    args = normalize(selector, args);
    fulfillSelector(args);
    return selector(...args);
  };
  selectorResolver.hasResolver = true;
  return selectorResolver;
}

/**
 * Applies selector's normalization function to the given arguments
 * if it exists.
 *
 * @param {Object} selector The selector potentially with a normalization method property.
 * @param {Array}  args     selector arguments to normalize.
 * @return {Array} Potentially normalized arguments.
 */
function normalize(selector, args) {
  if (selector.__unstableNormalizeArgs && typeof selector.__unstableNormalizeArgs === 'function' && args?.length) {
    return selector.__unstableNormalizeArgs(args);
  }
  return args;
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/store/index.js
const coreDataStore = {
  name: 'core/data',
  instantiate(registry) {
    const getCoreDataSelector = selectorName => (key, ...args) => {
      return registry.select(key)[selectorName](...args);
    };
    const getCoreDataAction = actionName => (key, ...args) => {
      return registry.dispatch(key)[actionName](...args);
    };
    return {
      getSelectors() {
        return Object.fromEntries(['getIsResolving', 'hasStartedResolution', 'hasFinishedResolution', 'isResolving', 'getCachedResolvers'].map(selectorName => [selectorName, getCoreDataSelector(selectorName)]));
      },
      getActions() {
        return Object.fromEntries(['startResolution', 'finishResolution', 'invalidateResolution', 'invalidateResolutionForStore', 'invalidateResolutionForStoreSelector'].map(actionName => [actionName, getCoreDataAction(actionName)]));
      },
      subscribe() {
        // There's no reasons to trigger any listener when we subscribe to this store
        // because there's no state stored in this store that need to retrigger selectors
        // if a change happens, the corresponding store where the tracking stated live
        // would have already triggered a "subscribe" call.
        return () => () => {};
      }
    };
  }
};
/* harmony default export */ const store = (coreDataStore);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/utils/emitter.js
/**
 * Create an event emitter.
 *
 * @return {import("../types").DataEmitter} Emitter.
 */
function createEmitter() {
  let isPaused = false;
  let isPending = false;
  const listeners = new Set();
  const notifyListeners = () =>
  // We use Array.from to clone the listeners Set
  // This ensures that we don't run a listener
  // that was added as a response to another listener.
  Array.from(listeners).forEach(listener => listener());
  return {
    get isPaused() {
      return isPaused;
    },
    subscribe(listener) {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
    pause() {
      isPaused = true;
    },
    resume() {
      isPaused = false;
      if (isPending) {
        isPending = false;
        notifyListeners();
      }
    },
    emit() {
      if (isPaused) {
        isPending = true;
        return;
      }
      notifyListeners();
    }
  };
}
//# sourceMappingURL=emitter.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/registry.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */





/** @typedef {import('./types').StoreDescriptor} StoreDescriptor */

/**
 * @typedef {Object} WPDataRegistry An isolated orchestrator of store registrations.
 *
 * @property {Function} registerGenericStore Given a namespace key and settings
 *                                           object, registers a new generic
 *                                           store.
 * @property {Function} registerStore        Given a namespace key and settings
 *                                           object, registers a new namespace
 *                                           store.
 * @property {Function} subscribe            Given a function callback, invokes
 *                                           the callback on any change to state
 *                                           within any registered store.
 * @property {Function} select               Given a namespace key, returns an
 *                                           object of the  store's registered
 *                                           selectors.
 * @property {Function} dispatch             Given a namespace key, returns an
 *                                           object of the store's registered
 *                                           action dispatchers.
 */

/**
 * @typedef {Object} WPDataPlugin An object of registry function overrides.
 *
 * @property {Function} registerStore registers store.
 */

function getStoreName(storeNameOrDescriptor) {
  return typeof storeNameOrDescriptor === 'string' ? storeNameOrDescriptor : storeNameOrDescriptor.name;
}
/**
 * Creates a new store registry, given an optional object of initial store
 * configurations.
 *
 * @param {Object}  storeConfigs Initial store configurations.
 * @param {Object?} parent       Parent registry.
 *
 * @return {WPDataRegistry} Data registry.
 */
function createRegistry(storeConfigs = {}, parent = null) {
  const stores = {};
  const emitter = createEmitter();
  let listeningStores = null;

  /**
   * Global listener called for each store's update.
   */
  function globalListener() {
    emitter.emit();
  }

  /**
   * Subscribe to changes to any data, either in all stores in registry, or
   * in one specific store.
   *
   * @param {Function}                listener              Listener function.
   * @param {string|StoreDescriptor?} storeNameOrDescriptor Optional store name.
   *
   * @return {Function} Unsubscribe function.
   */
  const subscribe = (listener, storeNameOrDescriptor) => {
    // subscribe to all stores
    if (!storeNameOrDescriptor) {
      return emitter.subscribe(listener);
    }

    // subscribe to one store
    const storeName = getStoreName(storeNameOrDescriptor);
    const store = stores[storeName];
    if (store) {
      return store.subscribe(listener);
    }

    // Trying to access a store that hasn't been registered,
    // this is a pattern rarely used but seen in some places.
    // We fallback to global `subscribe` here for backward-compatibility for now.
    // See https://github.com/WordPress/gutenberg/pull/27466 for more info.
    if (!parent) {
      return emitter.subscribe(listener);
    }
    return parent.subscribe(listener, storeNameOrDescriptor);
  };

  /**
   * Calls a selector given the current state and extra arguments.
   *
   * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
   *                                                       or the store descriptor.
   *
   * @return {*} The selector's returned value.
   */
  function select(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getSelectors();
    }
    return parent?.select(storeName);
  }
  function __unstableMarkListeningStores(callback, ref) {
    listeningStores = new Set();
    try {
      return callback.call(this);
    } finally {
      ref.current = Array.from(listeningStores);
      listeningStores = null;
    }
  }

  /**
   * Given a store descriptor, returns an object containing the store's selectors pre-bound to
   * state so that you only need to supply additional arguments, and modified so that they return
   * promises that resolve to their eventual values, after any resolvers have ran.
   *
   * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
   *                                                       convention of passing the store name is
   *                                                       also supported.
   *
   * @return {Object} Each key of the object matches the name of a selector.
   */
  function resolveSelect(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getResolveSelectors();
    }
    return parent && parent.resolveSelect(storeName);
  }

  /**
   * Given a store descriptor, returns an object containing the store's selectors pre-bound to
   * state so that you only need to supply additional arguments, and modified so that they throw
   * promises in case the selector is not resolved yet.
   *
   * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
   *                                                       convention of passing the store name is
   *                                                       also supported.
   *
   * @return {Object} Object containing the store's suspense-wrapped selectors.
   */
  function suspendSelect(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getSuspendSelectors();
    }
    return parent && parent.suspendSelect(storeName);
  }

  /**
   * Returns the available actions for a part of the state.
   *
   * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
   *                                                       or the store descriptor.
   *
   * @return {*} The action's returned value.
   */
  function dispatch(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    const store = stores[storeName];
    if (store) {
      return store.getActions();
    }
    return parent && parent.dispatch(storeName);
  }

  //
  // Deprecated
  // TODO: Remove this after `use()` is removed.
  function withPlugins(attributes) {
    return Object.fromEntries(Object.entries(attributes).map(([key, attribute]) => {
      if (typeof attribute !== 'function') {
        return [key, attribute];
      }
      return [key, function () {
        return registry[key].apply(null, arguments);
      }];
    }));
  }

  /**
   * Registers a store instance.
   *
   * @param {string}   name        Store registry name.
   * @param {Function} createStore Function that creates a store object (getSelectors, getActions, subscribe).
   */
  function registerStoreInstance(name, createStore) {
    if (stores[name]) {
      // eslint-disable-next-line no-console
      console.error('Store "' + name + '" is already registered.');
      return stores[name];
    }
    const store = createStore();
    if (typeof store.getSelectors !== 'function') {
      throw new TypeError('store.getSelectors must be a function');
    }
    if (typeof store.getActions !== 'function') {
      throw new TypeError('store.getActions must be a function');
    }
    if (typeof store.subscribe !== 'function') {
      throw new TypeError('store.subscribe must be a function');
    }
    // The emitter is used to keep track of active listeners when the registry
    // get paused, that way, when resumed we should be able to call all these
    // pending listeners.
    store.emitter = createEmitter();
    const currentSubscribe = store.subscribe;
    store.subscribe = listener => {
      const unsubscribeFromEmitter = store.emitter.subscribe(listener);
      const unsubscribeFromStore = currentSubscribe(() => {
        if (store.emitter.isPaused) {
          store.emitter.emit();
          return;
        }
        listener();
      });
      return () => {
        unsubscribeFromStore?.();
        unsubscribeFromEmitter?.();
      };
    };
    stores[name] = store;
    store.subscribe(globalListener);

    // Copy private actions and selectors from the parent store.
    if (parent) {
      try {
        data_build_module_lock_unlock_unlock(store.store).registerPrivateActions(data_build_module_lock_unlock_unlock(parent).privateActionsOf(name));
        data_build_module_lock_unlock_unlock(store.store).registerPrivateSelectors(data_build_module_lock_unlock_unlock(parent).privateSelectorsOf(name));
      } catch (e) {
        // unlock() throws if store.store was not locked.
        // The error indicates there's nothing to do here so let's
        // ignore it.
      }
    }
    return store;
  }

  /**
   * Registers a new store given a store descriptor.
   *
   * @param {StoreDescriptor} store Store descriptor.
   */
  function register(store) {
    registerStoreInstance(store.name, () => store.instantiate(registry));
  }
  function registerGenericStore(name, store) {
    (0,deprecated_build_module/* default */.A)('wp.data.registerGenericStore', {
      since: '5.9',
      alternative: 'wp.data.register( storeDescriptor )'
    });
    registerStoreInstance(name, () => store);
  }

  /**
   * Registers a standard `@wordpress/data` store.
   *
   * @param {string} storeName Unique namespace identifier.
   * @param {Object} options   Store description (reducer, actions, selectors, resolvers).
   *
   * @return {Object} Registered store object.
   */
  function registerStore(storeName, options) {
    if (!options.reducer) {
      throw new TypeError('Must specify store reducer');
    }
    const store = registerStoreInstance(storeName, () => createReduxStore(storeName, options).instantiate(registry));
    return store.store;
  }
  function batch(callback) {
    // If we're already batching, just call the callback.
    if (emitter.isPaused) {
      callback();
      return;
    }
    emitter.pause();
    Object.values(stores).forEach(store => store.emitter.pause());
    try {
      callback();
    } finally {
      emitter.resume();
      Object.values(stores).forEach(store => store.emitter.resume());
    }
  }
  let registry = {
    batch,
    stores,
    namespaces: stores,
    // TODO: Deprecate/remove this.
    subscribe,
    select,
    resolveSelect,
    suspendSelect,
    dispatch,
    use,
    register,
    registerGenericStore,
    registerStore,
    __unstableMarkListeningStores
  };

  //
  // TODO:
  // This function will be deprecated as soon as it is no longer internally referenced.
  function use(plugin, options) {
    if (!plugin) {
      return;
    }
    registry = {
      ...registry,
      ...plugin(registry, options)
    };
    return registry;
  }
  registry.register(store);
  for (const [name, config] of Object.entries(storeConfigs)) {
    registry.register(createReduxStore(name, config));
  }
  if (parent) {
    parent.subscribe(globalListener);
  }
  const registryWithPlugins = withPlugins(registry);
  data_build_module_lock_unlock_lock(registryWithPlugins, {
    privateActionsOf: name => {
      try {
        return data_build_module_lock_unlock_unlock(stores[name].store).privateActions;
      } catch (e) {
        // unlock() throws an error the store was not locked – this means
        // there no private actions are available
        return {};
      }
    },
    privateSelectorsOf: name => {
      try {
        return data_build_module_lock_unlock_unlock(stores[name].store).privateSelectors;
      } catch (e) {
        return {};
      }
    }
  });
  return registryWithPlugins;
}
//# sourceMappingURL=registry.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/default-registry.js
/**
 * Internal dependencies
 */

/* harmony default export */ const default_registry = (createRegistry());
//# sourceMappingURL=default-registry.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/components/registry-provider/context.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */

const Context = (0,react.createContext)(default_registry);
const {
  Consumer,
  Provider
} = Context;

/**
 * A custom react Context consumer exposing the provided `registry` to
 * children components. Used along with the RegistryProvider.
 *
 * You can read more about the react context api here:
 * https://react.dev/learn/passing-data-deeply-with-context#step-3-provide-the-context
 *
 * @example
 * ```js
 * import {
 *   RegistryProvider,
 *   RegistryConsumer,
 *   createRegistry
 * } from '@wordpress/data';
 *
 * const registry = createRegistry( {} );
 *
 * const App = ( { props } ) => {
 *   return <RegistryProvider value={ registry }>
 *     <div>Hello There</div>
 *     <RegistryConsumer>
 *       { ( registry ) => (
 *         <ComponentUsingRegistry
 *         		{ ...props }
 *         	  registry={ registry }
 *       ) }
 *     </RegistryConsumer>
 *   </RegistryProvider>
 * }
 * ```
 */
const RegistryConsumer = (/* unused pure expression or super */ null && (Consumer));

/**
 * A custom Context provider for exposing the provided `registry` to children
 * components via a consumer.
 *
 * See <a name="#RegistryConsumer">RegistryConsumer</a> documentation for
 * example.
 */
/* harmony default export */ const context = ((/* unused pure expression or super */ null && (Provider)));
//# sourceMappingURL=context.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@10.8.1_react@17.0.2/node_modules/@wordpress/data/build-module/components/registry-provider/use-registry.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * A custom react hook exposing the registry context for use.
 *
 * This exposes the `registry` value provided via the
 * <a href="#RegistryProvider">Registry Provider</a> to a component implementing
 * this hook.
 *
 * It acts similarly to the `useContext` react hook.
 *
 * Note: Generally speaking, `useRegistry` is a low level hook that in most cases
 * won't be needed for implementation. Most interactions with the `@wordpress/data`
 * API can be performed via the `useSelect` hook,  or the `withSelect` and
 * `withDispatch` higher order components.
 *
 * @example
 * ```js
 * import {
 *   RegistryProvider,
 *   createRegistry,
 *   useRegistry,
 * } from '@wordpress/data';
 *
 * const registry = createRegistry( {} );
 *
 * const SomeChildUsingRegistry = ( props ) => {
 *   const registry = useRegistry();
 *   // ...logic implementing the registry in other react hooks.
 * };
 *
 *
 * const ParentProvidingRegistry = ( props ) => {
 *   return <RegistryProvider value={ registry }>
 *     <SomeChildUsingRegistry { ...props } />
 *   </RegistryProvider>
 * };
 * ```
 *
 * @return {Function}  A custom react hook exposing the registry context value.
 */
function useRegistry() {
  return (0,react.useContext)(Context);
}
//# sourceMappingURL=use-registry.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-item-actions/index.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */




const {
  DropdownMenuV2: dataviews_item_actions_DropdownMenuV2,
  kebabCase: dataviews_item_actions_kebabCase
} = build_module_lock_unlock_unlock(privateApis);
function ButtonTrigger({
  action,
  onClick,
  items
}) {
  const label = typeof action.label === 'string' ? action.label : action.label(items);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    label: label,
    icon: action.icon,
    isDestructive: action.isDestructive,
    size: "compact",
    onClick: onClick
  });
}
function DropdownMenuItemTrigger({
  action,
  onClick,
  items
}) {
  const label = typeof action.label === 'string' ? action.label : action.label(items);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_item_actions_DropdownMenuV2.Item, {
    onClick: onClick,
    hideOnClick: !('RenderModal' in action),
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_item_actions_DropdownMenuV2.ItemLabel, {
      children: label
    })
  });
}
function ActionModal({
  action,
  items,
  closeModal
}) {
  const label = typeof action.label === 'string' ? action.label : action.label(items);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(modal, {
    title: action.modalHeader || label,
    __experimentalHideHeader: !!action.hideModalHeader,
    onRequestClose: closeModal !== null && closeModal !== void 0 ? closeModal : () => {},
    focusOnMount: "firstContentElement",
    size: "small",
    overlayClassName: `dataviews-action-modal dataviews-action-modal__${dataviews_item_actions_kebabCase(action.id)}`,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(action.RenderModal, {
      items: items,
      closeModal: closeModal
    })
  });
}
function ActionWithModal({
  action,
  items,
  ActionTrigger,
  isBusy
}) {
  const [isModalOpen, setIsModalOpen] = (0,react.useState)(false);
  const actionTriggerProps = {
    action,
    onClick: () => {
      setIsModalOpen(true);
    },
    items,
    isBusy
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ActionTrigger, {
      ...actionTriggerProps
    }), isModalOpen && /*#__PURE__*/(0,jsx_runtime.jsx)(ActionModal, {
      action: action,
      items: items,
      closeModal: () => setIsModalOpen(false)
    })]
  });
}
function ActionsDropdownMenuGroup({
  actions,
  item
}) {
  const registry = useRegistry();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_item_actions_DropdownMenuV2.Group, {
    children: actions.map(action => {
      if ('RenderModal' in action) {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(ActionWithModal, {
          action: action,
          items: [item],
          ActionTrigger: DropdownMenuItemTrigger
        }, action.id);
      }
      return /*#__PURE__*/(0,jsx_runtime.jsx)(DropdownMenuItemTrigger, {
        action: action,
        onClick: () => {
          action.callback([item], {
            registry
          });
        },
        items: [item]
      }, action.id);
    })
  });
}
function ItemActions({
  item,
  actions,
  isCompact
}) {
  const registry = useRegistry();
  const {
    primaryActions,
    eligibleActions
  } = (0,react.useMemo)(() => {
    // If an action is eligible for all items, doesn't need
    // to provide the `isEligible` function.
    const _eligibleActions = actions.filter(action => !action.isEligible || action.isEligible(item));
    const _primaryActions = _eligibleActions.filter(action => action.isPrimary && !!action.icon);
    return {
      primaryActions: _primaryActions,
      eligibleActions: _eligibleActions
    };
  }, [actions, item]);
  if (isCompact) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(CompactItemActions, {
      item: item,
      actions: eligibleActions
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
    spacing: 1,
    justify: "flex-end",
    className: "dataviews-item-actions",
    style: {
      flexShrink: '0',
      width: 'auto'
    },
    children: [!!primaryActions.length && primaryActions.map(action => {
      if ('RenderModal' in action) {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(ActionWithModal, {
          action: action,
          items: [item],
          ActionTrigger: ButtonTrigger
        }, action.id);
      }
      return /*#__PURE__*/(0,jsx_runtime.jsx)(ButtonTrigger, {
        action: action,
        onClick: () => {
          action.callback([item], {
            registry
          });
        },
        items: [item]
      }, action.id);
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(CompactItemActions, {
      item: item,
      actions: eligibleActions
    })]
  });
}
function CompactItemActions({
  item,
  actions
}) {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_item_actions_DropdownMenuV2, {
    trigger: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      size: "compact",
      icon: more_vertical,
      label: (0,build_module.__)('Actions'),
      accessibleWhenDisabled: true,
      disabled: !actions.length,
      className: "dataviews-all-actions-button"
    }),
    placement: "bottom-end",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(ActionsDropdownMenuGroup, {
      actions: actions,
      item: item
    })
  });
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-bulk-actions/index.js
/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */




function useHasAPossibleBulkAction(actions, item) {
  return (0,react.useMemo)(() => {
    return actions.some(action => {
      return action.supportsBulk && (!action.isEligible || action.isEligible(item));
    });
  }, [actions, item]);
}
function useSomeItemHasAPossibleBulkAction(actions, data) {
  return (0,react.useMemo)(() => {
    return data.some(item => {
      return actions.some(action => {
        return action.supportsBulk && (!action.isEligible || action.isEligible(item));
      });
    });
  }, [actions, data]);
}
function BulkSelectionCheckbox({
  selection,
  onChangeSelection,
  data,
  actions,
  getItemId
}) {
  const selectableItems = (0,react.useMemo)(() => {
    return data.filter(item => {
      return actions.some(action => action.supportsBulk && (!action.isEligible || action.isEligible(item)));
    });
  }, [data, actions]);
  const selectedItems = data.filter(item => selection.includes(getItemId(item)) && selectableItems.includes(item));
  const areAllSelected = selectedItems.length === selectableItems.length;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control, {
    className: "dataviews-view-table-selection-checkbox",
    __nextHasNoMarginBottom: true,
    checked: areAllSelected,
    indeterminate: !areAllSelected && !!selectedItems.length,
    onChange: () => {
      if (areAllSelected) {
        onChangeSelection([]);
      } else {
        onChangeSelection(selectableItems.map(item => getItemId(item)));
      }
    },
    "aria-label": areAllSelected ? (0,build_module.__)('Deselect all') : (0,build_module.__)('Select all')
  });
}
function ActionTrigger({
  action,
  onClick,
  isBusy,
  items
}) {
  const label = typeof action.label === 'string' ? action.label : action.label(items);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    disabled: isBusy,
    accessibleWhenDisabled: true,
    label: label,
    icon: action.icon,
    isDestructive: action.isDestructive,
    size: "compact",
    onClick: onClick,
    isBusy: isBusy,
    tooltipPosition: "top"
  });
}
const dataviews_bulk_actions_EMPTY_ARRAY = [];
function ActionButton({
  action,
  selectedItems,
  actionInProgress,
  setActionInProgress
}) {
  const registry = useRegistry();
  const selectedEligibleItems = (0,react.useMemo)(() => {
    return selectedItems.filter(item => {
      return !action.isEligible || action.isEligible(item);
    });
  }, [action, selectedItems]);
  if ('RenderModal' in action) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(ActionWithModal, {
      action: action,
      items: selectedEligibleItems,
      ActionTrigger: ActionTrigger
    }, action.id);
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(ActionTrigger, {
    action: action,
    onClick: async () => {
      setActionInProgress(action.id);
      await action.callback(selectedItems, {
        registry
      });
      setActionInProgress(null);
    },
    items: selectedEligibleItems,
    isBusy: actionInProgress === action.id
  }, action.id);
}
function renderFooterContent(data, actions, getItemId, selection, actionsToShow, selectedItems, actionInProgress, setActionInProgress, onChangeSelection) {
  const message = selectedItems.length > 0 ? (0,build_module/* sprintf */.nv)( /* translators: %d: number of items. */
  (0,build_module._n)('%d Item selected', '%d Items selected', selectedItems.length), selectedItems.length) : (0,build_module/* sprintf */.nv)( /* translators: %d: number of items. */
  (0,build_module._n)('%d Item', '%d Items', data.length), data.length);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
    expanded: false,
    className: "dataviews-bulk-actions-footer__container",
    spacing: 3,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(BulkSelectionCheckbox, {
      selection: selection,
      onChangeSelection: onChangeSelection,
      data: data,
      actions: actions,
      getItemId: getItemId
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "dataviews-bulk-actions-footer__item-count",
      children: message
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      className: "dataviews-bulk-actions-footer__action-buttons",
      expanded: false,
      spacing: 1,
      children: [actionsToShow.map(action => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(ActionButton, {
          action: action,
          selectedItems: selectedItems,
          actionInProgress: actionInProgress,
          setActionInProgress: setActionInProgress
        }, action.id);
      }), selectedItems.length > 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        icon: close_small/* default */.A,
        showTooltip: true,
        tooltipPosition: "top",
        size: "compact",
        label: (0,build_module.__)('Cancel'),
        disabled: !!actionInProgress,
        accessibleWhenDisabled: false,
        onClick: () => {
          onChangeSelection(dataviews_bulk_actions_EMPTY_ARRAY);
        }
      })]
    })]
  });
}
function FooterContent({
  selection,
  actions,
  onChangeSelection,
  data,
  getItemId
}) {
  const [actionInProgress, setActionInProgress] = (0,react.useState)(null);
  const footerContent = (0,react.useRef)(null);
  const bulkActions = (0,react.useMemo)(() => actions.filter(action => action.supportsBulk), [actions]);
  const selectableItems = (0,react.useMemo)(() => {
    return data.filter(item => {
      return bulkActions.some(action => !action.isEligible || action.isEligible(item));
    });
  }, [data, bulkActions]);
  const selectedItems = (0,react.useMemo)(() => {
    return data.filter(item => selection.includes(getItemId(item)) && selectableItems.includes(item));
  }, [selection, data, getItemId, selectableItems]);
  const actionsToShow = (0,react.useMemo)(() => actions.filter(action => {
    return action.supportsBulk && action.icon && selectedItems.some(item => !action.isEligible || action.isEligible(item));
  }), [actions, selectedItems]);
  if (!actionInProgress) {
    if (footerContent.current) {
      footerContent.current = null;
    }
    return renderFooterContent(data, actions, getItemId, selection, actionsToShow, selectedItems, actionInProgress, setActionInProgress, onChangeSelection);
  } else if (!footerContent.current) {
    footerContent.current = renderFooterContent(data, actions, getItemId, selection, actionsToShow, selectedItems, actionInProgress, setActionInProgress, onChangeSelection);
  }
  return footerContent.current;
}
function BulkActionsFooter() {
  const {
    data,
    selection,
    actions = dataviews_bulk_actions_EMPTY_ARRAY,
    onChangeSelection,
    getItemId
  } = (0,react.useContext)(dataviews_context);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(FooterContent, {
    selection: selection,
    onChangeSelection: onChangeSelection,
    data: data,
    actions: actions,
    getItemId: getItemId
  });
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/arrow-left.js
/**
 * WordPress dependencies
 */


const arrowLeft = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M20 11.2H6.8l3.7-3.7-1-1L3.9 12l5.6 5.5 1-1-3.7-3.7H20z"
  })
});
/* harmony default export */ const arrow_left = (arrowLeft);
//# sourceMappingURL=arrow-left.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/arrow-right.js
/**
 * WordPress dependencies
 */


const arrowRight = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "m14.5 6.5-1 1 3.7 3.7H4v1.6h13.2l-3.7 3.7 1 1 5.6-5.5z"
  })
});
/* harmony default export */ const arrow_right = (arrowRight);
//# sourceMappingURL=arrow-right.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/unseen.js
/**
 * WordPress dependencies
 */


const unseen = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M4.67 10.664s-2.09 1.11-2.917 1.582l.494.87 1.608-.914.002.002c.343.502.86 1.17 1.563 1.84.348.33.742.663 1.185.976L5.57 16.744l.858.515 1.02-1.701a9.1 9.1 0 0 0 4.051 1.18V19h1v-2.263a9.1 9.1 0 0 0 4.05-1.18l1.021 1.7.858-.514-1.034-1.723c.442-.313.837-.646 1.184-.977.703-.669 1.22-1.337 1.563-1.839l.002-.003 1.61.914.493-.87c-1.75-.994-2.918-1.58-2.918-1.58l-.003.005a8.29 8.29 0 0 1-.422.689 10.097 10.097 0 0 1-1.36 1.598c-1.218 1.16-3.042 2.293-5.544 2.293-2.503 0-4.327-1.132-5.546-2.293a10.099 10.099 0 0 1-1.359-1.599 8.267 8.267 0 0 1-.422-.689l-.003-.005Z"
  })
});
/* harmony default export */ const library_unseen = (unseen);
//# sourceMappingURL=unseen.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/dataviews-layouts/table/column-header-menu.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */





/**
 * Internal dependencies
 */






const {
  DropdownMenuV2: column_header_menu_DropdownMenuV2
} = build_module_lock_unlock_unlock(privateApis);
function WithDropDownMenuSeparators({
  children
}) {
  return react.Children.toArray(children).filter(Boolean).map((child, i) => /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
    children: [i > 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.Separator, {}), child]
  }, i));
}
const _HeaderMenu = (0,react.forwardRef)(function HeaderMenu({
  fieldId,
  view,
  fields,
  onChangeView,
  onHide,
  setOpenedFilter
}, ref) {
  const visibleFieldIds = getVisibleFieldIds(view, fields);
  const index = visibleFieldIds?.indexOf(fieldId);
  const isSorted = view.sort?.field === fieldId;
  let isHidable = false;
  let isSortable = false;
  let canAddFilter = false;
  let header;
  let operators = [];
  const combinedField = view.layout?.combinedFields?.find(f => f.id === fieldId);
  const field = fields.find(f => f.id === fieldId);
  if (!combinedField) {
    if (!field) {
      // No combined or regular field found.
      return null;
    }
    isHidable = field.enableHiding !== false;
    isSortable = field.enableSorting !== false;
    header = field.header;
    operators = sanitizeOperators(field);
    // Filter can be added:
    // 1. If the field is not already part of a view's filters.
    // 2. If the field meets the type and operator requirements.
    // 3. If it's not primary. If it is, it should be already visible.
    canAddFilter = !view.filters?.some(_filter => fieldId === _filter.field) && !!field.elements?.length && !!operators.length && !field.filterBy?.isPrimary;
  } else {
    header = combinedField.header || combinedField.label;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2, {
    align: "start",
    trigger: /*#__PURE__*/(0,jsx_runtime.jsxs)(build_module_button/* default */.Ay, {
      size: "compact",
      className: "dataviews-view-table-header-button",
      ref: ref,
      variant: "tertiary",
      children: [header, view.sort && isSorted && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        "aria-hidden": "true",
        children: sortArrows[view.sort.direction]
      })]
    }),
    style: {
      minWidth: '240px'
    },
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)(WithDropDownMenuSeparators, {
      children: [isSortable && /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.Group, {
        children: SORTING_DIRECTIONS.map(direction => {
          const isChecked = view.sort && isSorted && view.sort.direction === direction;
          const value = `${fieldId}-${direction}`;
          return /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.RadioItem, {
            // All sorting radio items share the same name, so that
            // selecting a sorting option automatically deselects the
            // previously selected one, even if it is displayed in
            // another submenu. The field and direction are passed via
            // the `value` prop.
            name: "view-table-sorting",
            value: value,
            checked: isChecked,
            onChange: () => {
              onChangeView({
                ...view,
                sort: {
                  field: fieldId,
                  direction
                }
              });
            },
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.ItemLabel, {
              children: sortLabels[direction]
            })
          }, value);
        })
      }), canAddFilter && /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.Group, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.Item, {
          prefix: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
            icon: library_funnel
          }),
          onClick: () => {
            setOpenedFilter(fieldId);
            onChangeView({
              ...view,
              page: 1,
              filters: [...(view.filters || []), {
                field: fieldId,
                value: undefined,
                operator: operators[0]
              }]
            });
          },
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.ItemLabel, {
            children: (0,build_module.__)('Add filter')
          })
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(column_header_menu_DropdownMenuV2.Group, {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.Item, {
          prefix: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
            icon: arrow_left
          }),
          disabled: index < 1,
          onClick: () => {
            var _visibleFieldIds$slic;
            onChangeView({
              ...view,
              fields: [...((_visibleFieldIds$slic = visibleFieldIds.slice(0, index - 1)) !== null && _visibleFieldIds$slic !== void 0 ? _visibleFieldIds$slic : []), fieldId, visibleFieldIds[index - 1], ...visibleFieldIds.slice(index + 1)]
            });
          },
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.ItemLabel, {
            children: (0,build_module.__)('Move left')
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.Item, {
          prefix: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
            icon: arrow_right
          }),
          disabled: index >= visibleFieldIds.length - 1,
          onClick: () => {
            var _visibleFieldIds$slic2;
            onChangeView({
              ...view,
              fields: [...((_visibleFieldIds$slic2 = visibleFieldIds.slice(0, index)) !== null && _visibleFieldIds$slic2 !== void 0 ? _visibleFieldIds$slic2 : []), visibleFieldIds[index + 1], fieldId, ...visibleFieldIds.slice(index + 2)]
            });
          },
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.ItemLabel, {
            children: (0,build_module.__)('Move right')
          })
        }), isHidable && field && /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.Item, {
          prefix: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
            icon: library_unseen
          }),
          onClick: () => {
            onHide(field);
            onChangeView({
              ...view,
              fields: visibleFieldIds.filter(id => id !== fieldId)
            });
          },
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu_DropdownMenuV2.ItemLabel, {
            children: (0,build_module.__)('Hide column')
          })
        })]
      })]
    })
  });
});

// @ts-expect-error Lift the `Item` type argument through the forwardRef.
const ColumnHeaderMenu = _HeaderMenu;
/* harmony default export */ const column_header_menu = (ColumnHeaderMenu);
//# sourceMappingURL=column-header-menu.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/dataviews-layouts/table/index.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */









function TableColumn({
  column,
  fields,
  view,
  ...props
}) {
  const field = fields.find(f => f.id === column);
  if (!!field) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(TableColumnField, {
      ...props,
      field: field
    });
  }
  const combinedField = view.layout?.combinedFields?.find(f => f.id === column);
  if (!!combinedField) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(TableColumnCombined, {
      ...props,
      fields: fields,
      view: view,
      field: combinedField
    });
  }
  return null;
}
function TableColumnField({
  primaryField,
  item,
  field
}) {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: (0,clsx/* default */.A)('dataviews-view-table__cell-content-wrapper', {
      'dataviews-view-table__primary-field': primaryField?.id === field.id
    }),
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(field.render, {
      item
    })
  });
}
function TableColumnCombined({
  field,
  ...props
}) {
  const children = field.children.map(child => /*#__PURE__*/(0,jsx_runtime.jsx)(TableColumn, {
    ...props,
    column: child
  }, child));
  if (field.direction === 'horizontal') {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
      spacing: 3,
      children: children
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(v_stack_component/* default */.A, {
    spacing: 0,
    children: children
  });
}
function TableRow({
  hasBulkActions,
  item,
  actions,
  fields,
  id,
  view,
  primaryField,
  selection,
  getItemId,
  onChangeSelection
}) {
  const hasPossibleBulkAction = useHasAPossibleBulkAction(actions, item);
  const isSelected = hasPossibleBulkAction && selection.includes(id);
  const [isHovered, setIsHovered] = (0,react.useState)(false);
  const handleMouseEnter = () => {
    setIsHovered(true);
  };
  const handleMouseLeave = () => {
    setIsHovered(false);
  };

  // Will be set to true if `onTouchStart` fires. This happens before
  // `onClick` and can be used to exclude touchscreen devices from certain
  // behaviours.
  const isTouchDeviceRef = (0,react.useRef)(false);
  const columns = getVisibleFieldIds(view, fields);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("tr", {
    className: (0,clsx/* default */.A)('dataviews-view-table__row', {
      'is-selected': hasPossibleBulkAction && isSelected,
      'is-hovered': isHovered,
      'has-bulk-actions': hasPossibleBulkAction
    }),
    onMouseEnter: handleMouseEnter,
    onMouseLeave: handleMouseLeave,
    onTouchStart: () => {
      isTouchDeviceRef.current = true;
    },
    onClick: () => {
      if (!hasPossibleBulkAction) {
        return;
      }
      if (!isTouchDeviceRef.current && document.getSelection()?.type !== 'Range') {
        onChangeSelection(selection.includes(id) ? selection.filter(itemId => id !== itemId) : [id]);
      }
    },
    children: [hasBulkActions && /*#__PURE__*/(0,jsx_runtime.jsx)("td", {
      className: "dataviews-view-table__checkbox-column",
      style: {
        width: '1%'
      },
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "dataviews-view-table__cell-content-wrapper",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(DataViewsSelectionCheckbox, {
          item: item,
          selection: selection,
          onChangeSelection: onChangeSelection,
          getItemId: getItemId,
          primaryField: primaryField,
          disabled: !hasPossibleBulkAction
        })
      })
    }), columns.map(column => {
      var _view$layout$styles$c;
      // Explicits picks the supported styles.
      const {
        width,
        maxWidth,
        minWidth
      } = (_view$layout$styles$c = view.layout?.styles?.[column]) !== null && _view$layout$styles$c !== void 0 ? _view$layout$styles$c : {};
      return /*#__PURE__*/(0,jsx_runtime.jsx)("td", {
        style: {
          width,
          maxWidth,
          minWidth
        },
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(TableColumn, {
          primaryField: primaryField,
          fields: fields,
          item: item,
          column: column,
          view: view
        })
      }, column);
    }), !!actions?.length &&
    /*#__PURE__*/
    // Disable reason: we are not making the element interactive,
    // but preventing any click events from bubbling up to the
    // table row. This allows us to add a click handler to the row
    // itself (to toggle row selection) without erroneously
    // intercepting click events from ItemActions.
    /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,jsx_runtime.jsx)("td", {
      className: "dataviews-view-table__actions-column",
      onClick: e => e.stopPropagation(),
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(ItemActions, {
        item: item,
        actions: actions
      })
    })
    /* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */]
  });
}
function ViewTable({
  actions,
  data,
  fields,
  getItemId,
  isLoading = false,
  onChangeView,
  onChangeSelection,
  selection,
  setOpenedFilter,
  view
}) {
  const headerMenuRefs = (0,react.useRef)(new Map());
  const headerMenuToFocusRef = (0,react.useRef)();
  const [nextHeaderMenuToFocus, setNextHeaderMenuToFocus] = (0,react.useState)();
  const hasBulkActions = useSomeItemHasAPossibleBulkAction(actions, data);
  (0,react.useEffect)(() => {
    if (headerMenuToFocusRef.current) {
      headerMenuToFocusRef.current.focus();
      headerMenuToFocusRef.current = undefined;
    }
  });
  const tableNoticeId = (0,react.useId)();
  if (nextHeaderMenuToFocus) {
    // If we need to force focus, we short-circuit rendering here
    // to prevent any additional work while we handle that.
    // Clearing out the focus directive is necessary to make sure
    // future renders don't cause unexpected focus jumps.
    headerMenuToFocusRef.current = nextHeaderMenuToFocus;
    setNextHeaderMenuToFocus(undefined);
    return;
  }
  const onHide = field => {
    const hidden = headerMenuRefs.current.get(field.id);
    const fallback = hidden ? headerMenuRefs.current.get(hidden.fallback) : undefined;
    setNextHeaderMenuToFocus(fallback?.node);
  };
  const columns = getVisibleFieldIds(view, fields);
  const hasData = !!data?.length;
  const primaryField = fields.find(field => field.id === view.layout?.primaryField);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("table", {
      className: "dataviews-view-table",
      "aria-busy": isLoading,
      "aria-describedby": tableNoticeId,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("thead", {
        children: /*#__PURE__*/(0,jsx_runtime.jsxs)("tr", {
          className: "dataviews-view-table__row",
          children: [hasBulkActions && /*#__PURE__*/(0,jsx_runtime.jsx)("th", {
            className: "dataviews-view-table__checkbox-column",
            style: {
              width: '1%'
            },
            scope: "col",
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(BulkSelectionCheckbox, {
              selection: selection,
              onChangeSelection: onChangeSelection,
              data: data,
              actions: actions,
              getItemId: getItemId
            })
          }), columns.map((column, index) => {
            var _view$layout$styles$c2;
            // Explicits picks the supported styles.
            const {
              width,
              maxWidth,
              minWidth
            } = (_view$layout$styles$c2 = view.layout?.styles?.[column]) !== null && _view$layout$styles$c2 !== void 0 ? _view$layout$styles$c2 : {};
            return /*#__PURE__*/(0,jsx_runtime.jsx)("th", {
              style: {
                width,
                maxWidth,
                minWidth
              },
              "aria-sort": view.sort?.field === column ? sortValues[view.sort.direction] : undefined,
              scope: "col",
              children: /*#__PURE__*/(0,jsx_runtime.jsx)(column_header_menu, {
                ref: node => {
                  if (node) {
                    headerMenuRefs.current.set(column, {
                      node,
                      fallback: columns[index > 0 ? index - 1 : 1]
                    });
                  } else {
                    headerMenuRefs.current.delete(column);
                  }
                },
                fieldId: column,
                view: view,
                fields: fields,
                onChangeView: onChangeView,
                onHide: onHide,
                setOpenedFilter: setOpenedFilter
              })
            }, column);
          }), !!actions?.length && /*#__PURE__*/(0,jsx_runtime.jsx)("th", {
            className: "dataviews-view-table__actions-column",
            children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
              className: "dataviews-view-table-header",
              children: (0,build_module.__)('Actions')
            })
          })]
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("tbody", {
        children: hasData && data.map((item, index) => /*#__PURE__*/(0,jsx_runtime.jsx)(TableRow, {
          item: item,
          hasBulkActions: hasBulkActions,
          actions: actions,
          fields: fields,
          id: getItemId(item) || index.toString(),
          view: view,
          primaryField: primaryField,
          selection: selection,
          getItemId: getItemId,
          onChangeSelection: onChangeSelection
        }, getItemId(item)))
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: (0,clsx/* default */.A)({
        'dataviews-loading': isLoading,
        'dataviews-no-results': !hasData && !isLoading
      }),
      id: tableNoticeId,
      children: !hasData && /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
        children: isLoading ? /*#__PURE__*/(0,jsx_runtime.jsx)(spinner, {}) : (0,build_module.__)('No results')
      })
    })]
  });
}
/* harmony default export */ const table = (ViewTable);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/flex/flex/component.js
var flex_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/flex/flex/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/grid/utils.js
/**
 * External dependencies
 */

const ALIGNMENTS = {
  bottom: {
    alignItems: 'flex-end',
    justifyContent: 'center'
  },
  bottomLeft: {
    alignItems: 'flex-start',
    justifyContent: 'flex-end'
  },
  bottomRight: {
    alignItems: 'flex-end',
    justifyContent: 'flex-end'
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center'
  },
  spaced: {
    alignItems: 'center',
    justifyContent: 'space-between'
  },
  left: {
    alignItems: 'center',
    justifyContent: 'flex-start'
  },
  right: {
    alignItems: 'center',
    justifyContent: 'flex-end'
  },
  stretch: {
    alignItems: 'stretch'
  },
  top: {
    alignItems: 'flex-start',
    justifyContent: 'center'
  },
  topLeft: {
    alignItems: 'flex-start',
    justifyContent: 'flex-start'
  },
  topRight: {
    alignItems: 'flex-start',
    justifyContent: 'flex-end'
  }
};
function getAlignmentProps(alignment) {
  const alignmentProps = alignment ? ALIGNMENTS[alignment] : {};
  return alignmentProps;
}
//# sourceMappingURL=utils.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/use-responsive-value.js
var use_responsive_value = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/use-responsive-value.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/grid/hook.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */






function useGrid(props) {
  const {
    align,
    alignment,
    className,
    columnGap,
    columns = 2,
    gap = 3,
    isInline = false,
    justify,
    rowGap,
    rows,
    templateColumns,
    templateRows,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'Grid');
  const columnsAsArray = Array.isArray(columns) ? columns : [columns];
  const column = (0,use_responsive_value/* useResponsiveValue */.t)(columnsAsArray);
  const rowsAsArray = Array.isArray(rows) ? rows : [rows];
  const row = (0,use_responsive_value/* useResponsiveValue */.t)(rowsAsArray);
  const gridTemplateColumns = templateColumns || !!columns && `repeat( ${column}, 1fr )`;
  const gridTemplateRows = templateRows || !!rows && `repeat( ${row}, 1fr )`;
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    const alignmentProps = getAlignmentProps(alignment);
    const gridClasses = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
      alignItems: align,
      display: isInline ? 'inline-grid' : 'grid',
      gap: `calc( ${config_values/* default */.A.gridBase} * ${gap} )`,
      gridTemplateColumns: gridTemplateColumns || undefined,
      gridTemplateRows: gridTemplateRows || undefined,
      gridRowGap: rowGap,
      gridColumnGap: columnGap,
      justifyContent: justify,
      verticalAlign: isInline ? 'middle' : undefined,
      ...alignmentProps
    },  true ? "" : 0,  true ? "" : 0);
    return cx(gridClasses, className);
  }, [align, alignment, className, columnGap, cx, gap, gridTemplateColumns, gridTemplateRows, isInline, justify, rowGap]);
  return {
    ...otherProps,
    className: classes
  };
}
//# sourceMappingURL=hook.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/grid/component.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */





function UnconnectedGrid(props, forwardedRef) {
  const gridProps = useGrid(props);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(view_component/* default */.A, {
    ...gridProps,
    ref: forwardedRef
  });
}

/**
 * `Grid` is a primitive layout component that can arrange content in a grid configuration.
 *
 * ```jsx
 * import {
 * 	__experimentalGrid as Grid,
 * 	__experimentalText as Text
 * } from `@wordpress/components`;
 *
 * function Example() {
 * 	return (
 * 		<Grid columns={ 3 }>
 * 			<Text>Code</Text>
 * 			<Text>is</Text>
 * 			<Text>Poetry</Text>
 * 		</Grid>
 * 	);
 * }
 * ```
 */
const Grid = (0,context_connect/* contextConnect */.KZ)(UnconnectedGrid, 'Grid');
/* harmony default export */ const grid_component = (Grid);
//# sourceMappingURL=component.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/dataviews-layouts/grid/index.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */






function GridItem({
  selection,
  onChangeSelection,
  getItemId,
  item,
  actions,
  mediaField,
  primaryField,
  visibleFields,
  badgeFields,
  columnFields
}) {
  const hasBulkAction = useHasAPossibleBulkAction(actions, item);
  const id = getItemId(item);
  const isSelected = selection.includes(id);
  const renderedMediaField = mediaField?.render ? /*#__PURE__*/(0,jsx_runtime.jsx)(mediaField.render, {
    item: item
  }) : null;
  const renderedPrimaryField = primaryField?.render ? /*#__PURE__*/(0,jsx_runtime.jsx)(primaryField.render, {
    item: item
  }) : null;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(v_stack_component/* default */.A, {
    spacing: 0,
    className: (0,clsx/* default */.A)('dataviews-view-grid__card', {
      'is-selected': hasBulkAction && isSelected
    }),
    onClickCapture: event => {
      if (event.ctrlKey || event.metaKey) {
        event.stopPropagation();
        event.preventDefault();
        if (!hasBulkAction) {
          return;
        }
        onChangeSelection(selection.includes(id) ? selection.filter(itemId => id !== itemId) : [...selection, id]);
      }
    },
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "dataviews-view-grid__media",
      children: renderedMediaField
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(DataViewsSelectionCheckbox, {
      item: item,
      selection: selection,
      onChangeSelection: onChangeSelection,
      getItemId: getItemId,
      primaryField: primaryField,
      disabled: !hasBulkAction
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      justify: "space-between",
      className: "dataviews-view-grid__title-actions",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
        className: "dataviews-view-grid__primary-field",
        children: renderedPrimaryField
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(ItemActions, {
        item: item,
        actions: actions,
        isCompact: true
      })]
    }), !!badgeFields?.length && /*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
      className: "dataviews-view-grid__badge-fields",
      spacing: 2,
      wrap: true,
      alignment: "top",
      justify: "flex-start",
      children: badgeFields.map(field => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(flex_item_component/* default */.A, {
          className: "dataviews-view-grid__field-value",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(field.render, {
            item: item
          })
        }, field.id);
      })
    }), !!visibleFields?.length && /*#__PURE__*/(0,jsx_runtime.jsx)(v_stack_component/* default */.A, {
      className: "dataviews-view-grid__fields",
      spacing: 1,
      children: visibleFields.map(field => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(flex_component/* default */.A, {
          className: (0,clsx/* default */.A)('dataviews-view-grid__field', columnFields?.includes(field.id) ? 'is-column' : 'is-row'),
          gap: 1,
          justify: "flex-start",
          expanded: true,
          style: {
            height: 'auto'
          },
          direction: columnFields?.includes(field.id) ? 'column' : 'row',
          children: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
            children: [/*#__PURE__*/(0,jsx_runtime.jsx)(flex_item_component/* default */.A, {
              className: "dataviews-view-grid__field-name",
              children: field.header
            }), /*#__PURE__*/(0,jsx_runtime.jsx)(flex_item_component/* default */.A, {
              className: "dataviews-view-grid__field-value",
              style: {
                maxHeight: 'none'
              },
              children: /*#__PURE__*/(0,jsx_runtime.jsx)(field.render, {
                item: item
              })
            })]
          })
        }, field.id);
      })
    })]
  }, id);
}
function ViewGrid({
  actions,
  data,
  fields,
  getItemId,
  isLoading,
  onChangeSelection,
  selection,
  view,
  density
}) {
  const mediaField = fields.find(field => field.id === view.layout?.mediaField);
  const primaryField = fields.find(field => field.id === view.layout?.primaryField);
  const viewFields = view.fields || fields.map(field => field.id);
  const {
    visibleFields,
    badgeFields
  } = fields.reduce((accumulator, field) => {
    if (!viewFields.includes(field.id) || [view.layout?.mediaField, view?.layout?.primaryField].includes(field.id)) {
      return accumulator;
    }
    // If the field is a badge field, add it to the badgeFields array
    // otherwise add it to the rest visibleFields array.
    const key = view.layout?.badgeFields?.includes(field.id) ? 'badgeFields' : 'visibleFields';
    accumulator[key].push(field);
    return accumulator;
  }, {
    visibleFields: [],
    badgeFields: []
  });
  const hasData = !!data?.length;
  const gridStyle = density ? {
    gridTemplateColumns: `repeat(${density}, minmax(0, 1fr))`
  } : {};
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [hasData && /*#__PURE__*/(0,jsx_runtime.jsx)(grid_component, {
      gap: 8,
      columns: 2,
      alignment: "top",
      className: "dataviews-view-grid",
      style: gridStyle,
      "aria-busy": isLoading,
      children: data.map(item => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(GridItem, {
          selection: selection,
          onChangeSelection: onChangeSelection,
          getItemId: getItemId,
          item: item,
          actions: actions,
          mediaField: mediaField,
          primaryField: primaryField,
          visibleFields: visibleFields,
          badgeFields: badgeFields,
          columnFields: view.layout?.columnFields
        }, getItemId(item));
      })
    }), !hasData && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: (0,clsx/* default */.A)({
        'dataviews-loading': isLoading,
        'dataviews-no-results': !isLoading
      }),
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
        children: isLoading ? /*#__PURE__*/(0,jsx_runtime.jsx)(spinner, {}) : (0,build_module.__)('No results')
      })
    })]
  });
}
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.js
var use_previous = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/dataviews-layouts/list/index.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */







/**
 * Internal dependencies
 */




const {
  DropdownMenuV2: DropdownMenu
} = build_module_lock_unlock_unlock(privateApis);
function generateItemWrapperCompositeId(idPrefix) {
  return `${idPrefix}-item-wrapper`;
}
function generatePrimaryActionCompositeId(idPrefix, primaryActionId) {
  return `${idPrefix}-primary-action-${primaryActionId}`;
}
function generateDropdownTriggerCompositeId(idPrefix) {
  return `${idPrefix}-dropdown`;
}
function PrimaryActionGridCell({
  idPrefix,
  primaryAction,
  item
}) {
  const registry = useRegistry();
  const [isModalOpen, setIsModalOpen] = (0,react.useState)(false);
  const compositeItemId = generatePrimaryActionCompositeId(idPrefix, primaryAction.id);
  const label = typeof primaryAction.label === 'string' ? primaryAction.label : primaryAction.label([item]);
  return 'RenderModal' in primaryAction ? /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    role: "gridcell",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(Composite.Item, {
      id: compositeItemId,
      render: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        label: label,
        icon: primaryAction.icon,
        isDestructive: primaryAction.isDestructive,
        size: "small",
        onClick: () => setIsModalOpen(true)
      }),
      children: isModalOpen && /*#__PURE__*/(0,jsx_runtime.jsx)(ActionModal, {
        action: primaryAction,
        items: [item],
        closeModal: () => setIsModalOpen(false)
      })
    })
  }, primaryAction.id) : /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    role: "gridcell",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(Composite.Item, {
      id: compositeItemId,
      render: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        label: label,
        icon: primaryAction.icon,
        isDestructive: primaryAction.isDestructive,
        size: "small",
        onClick: () => {
          primaryAction.callback([item], {
            registry
          });
        }
      })
    })
  }, primaryAction.id);
}
function ListItem({
  actions,
  idPrefix,
  isSelected,
  item,
  mediaField,
  onSelect,
  primaryField,
  visibleFields,
  onDropdownTriggerKeyDown
}) {
  const itemRef = (0,react.useRef)(null);
  const labelId = `${idPrefix}-label`;
  const descriptionId = `${idPrefix}-description`;
  const [isHovered, setIsHovered] = (0,react.useState)(false);
  const handleMouseEnter = () => {
    setIsHovered(true);
  };
  const handleMouseLeave = () => {
    setIsHovered(false);
  };
  (0,react.useEffect)(() => {
    if (isSelected) {
      itemRef.current?.scrollIntoView({
        behavior: 'auto',
        block: 'nearest',
        inline: 'nearest'
      });
    }
  }, [isSelected]);
  const {
    primaryAction,
    eligibleActions
  } = (0,react.useMemo)(() => {
    // If an action is eligible for all items, doesn't need
    // to provide the `isEligible` function.
    const _eligibleActions = actions.filter(action => !action.isEligible || action.isEligible(item));
    const _primaryActions = _eligibleActions.filter(action => action.isPrimary && !!action.icon);
    return {
      primaryAction: _primaryActions?.[0],
      eligibleActions: _eligibleActions
    };
  }, [actions, item]);
  const renderedMediaField = mediaField?.render ? /*#__PURE__*/(0,jsx_runtime.jsx)(mediaField.render, {
    item: item
  }) : /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "dataviews-view-list__media-placeholder"
  });
  const renderedPrimaryField = primaryField?.render ? /*#__PURE__*/(0,jsx_runtime.jsx)(primaryField.render, {
    item: item
  }) : null;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Composite.Row, {
    ref: itemRef,
    render: /*#__PURE__*/(0,jsx_runtime.jsx)("li", {}),
    role: "row",
    className: (0,clsx/* default */.A)({
      'is-selected': isSelected,
      'is-hovered': isHovered
    }),
    onMouseEnter: handleMouseEnter,
    onMouseLeave: handleMouseLeave,
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      className: "dataviews-view-list__item-wrapper",
      alignment: "center",
      spacing: 0,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        role: "gridcell",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(Composite.Item, {
          render: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {}),
          role: "button",
          id: generateItemWrapperCompositeId(idPrefix),
          "aria-pressed": isSelected,
          "aria-labelledby": labelId,
          "aria-describedby": descriptionId,
          className: "dataviews-view-list__item",
          onClick: () => onSelect(item),
          children: /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
            spacing: 3,
            justify: "start",
            alignment: "flex-start",
            children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
              className: "dataviews-view-list__media-wrapper",
              children: renderedMediaField
            }), /*#__PURE__*/(0,jsx_runtime.jsxs)(v_stack_component/* default */.A, {
              spacing: 1,
              className: "dataviews-view-list__field-wrapper",
              children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                className: "dataviews-view-list__primary-field",
                id: labelId,
                children: renderedPrimaryField
              }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
                className: "dataviews-view-list__fields",
                id: descriptionId,
                children: visibleFields.map(field => /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
                  className: "dataviews-view-list__field",
                  children: [/*#__PURE__*/(0,jsx_runtime.jsx)(visually_hidden_component/* default */.A, {
                    as: "span",
                    className: "dataviews-view-list__field-label",
                    children: field.label
                  }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                    className: "dataviews-view-list__field-value",
                    children: /*#__PURE__*/(0,jsx_runtime.jsx)(field.render, {
                      item: item
                    })
                  })]
                }, field.id))
              })]
            })]
          })
        })
      }), eligibleActions?.length > 0 && /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
        spacing: 3,
        justify: "flex-end",
        className: "dataviews-view-list__item-actions",
        style: {
          flexShrink: '0',
          width: 'auto'
        },
        children: [primaryAction && /*#__PURE__*/(0,jsx_runtime.jsx)(PrimaryActionGridCell, {
          idPrefix: idPrefix,
          primaryAction: primaryAction,
          item: item
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          role: "gridcell",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(DropdownMenu, {
            trigger: /*#__PURE__*/(0,jsx_runtime.jsx)(Composite.Item, {
              id: generateDropdownTriggerCompositeId(idPrefix),
              render: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                size: "small",
                icon: more_vertical,
                label: (0,build_module.__)('Actions'),
                accessibleWhenDisabled: true,
                disabled: !actions.length,
                onKeyDown: onDropdownTriggerKeyDown
              })
            }),
            placement: "bottom-end",
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(ActionsDropdownMenuGroup, {
              actions: eligibleActions,
              item: item
            })
          })
        })]
      })]
    })
  });
}
function ViewList(props) {
  const {
    actions,
    data,
    fields,
    getItemId,
    isLoading,
    onChangeSelection,
    selection,
    view
  } = props;
  const baseId = (0,use_instance_id/* default */.A)(ViewList, 'view-list');
  const selectedItem = data?.findLast(item => selection.includes(getItemId(item)));
  const mediaField = fields.find(field => field.id === view.layout?.mediaField);
  const primaryField = fields.find(field => field.id === view.layout?.primaryField);
  const viewFields = view.fields || fields.map(field => field.id);
  const visibleFields = fields.filter(field => viewFields.includes(field.id) && ![view.layout?.primaryField, view.layout?.mediaField].includes(field.id));
  const onSelect = item => onChangeSelection([getItemId(item)]);
  const generateCompositeItemIdPrefix = (0,react.useCallback)(item => `${baseId}-${getItemId(item)}`, [baseId, getItemId]);
  const isActiveCompositeItem = (0,react.useCallback)((item, idToCheck) => {
    // All composite items use the same prefix in their IDs.
    return idToCheck.startsWith(generateCompositeItemIdPrefix(item));
  }, [generateCompositeItemIdPrefix]);

  // Controlled state for the active composite item.
  const [activeCompositeId, setActiveCompositeId] = (0,react.useState)(undefined);

  // Update the active composite item when the selected item changes.
  (0,react.useEffect)(() => {
    if (selectedItem) {
      setActiveCompositeId(generateItemWrapperCompositeId(generateCompositeItemIdPrefix(selectedItem)));
    }
  }, [selectedItem, generateCompositeItemIdPrefix]);
  const activeItemIndex = data.findIndex(item => isActiveCompositeItem(item, activeCompositeId !== null && activeCompositeId !== void 0 ? activeCompositeId : ''));
  const previousActiveItemIndex = (0,use_previous/* default */.A)(activeItemIndex);
  const isActiveIdInList = activeItemIndex !== -1;
  const selectCompositeItem = (0,react.useCallback)((targetIndex, generateCompositeId) => {
    // Clamping between 0 and data.length - 1 to avoid out of bounds.
    const clampedIndex = Math.min(data.length - 1, Math.max(0, targetIndex));
    if (!data[clampedIndex]) {
      return;
    }
    const itemIdPrefix = generateCompositeItemIdPrefix(data[clampedIndex]);
    const targetCompositeItemId = generateCompositeId(itemIdPrefix);
    setActiveCompositeId(targetCompositeItemId);
    document.getElementById(targetCompositeItemId)?.focus();
  }, [data, generateCompositeItemIdPrefix]);

  // Select a new active composite item when the current active item
  // is removed from the list.
  (0,react.useEffect)(() => {
    const wasActiveIdInList = previousActiveItemIndex !== undefined && previousActiveItemIndex !== -1;
    if (!isActiveIdInList && wasActiveIdInList) {
      // By picking `previousActiveItemIndex` as the next item index, we are
      // basically picking the item that would have been after the deleted one.
      // If the previously active (and removed) item was the last of the list,
      // we will select the item before it — which is the new last item.
      selectCompositeItem(previousActiveItemIndex, generateItemWrapperCompositeId);
    }
  }, [isActiveIdInList, selectCompositeItem, previousActiveItemIndex]);

  // Prevent the default behavior (open dropdown menu) and instead select the
  // dropdown menu trigger on the previous/next row.
  // https://github.com/ariakit/ariakit/issues/3768
  const onDropdownTriggerKeyDown = (0,react.useCallback)(event => {
    if (event.key === 'ArrowDown') {
      // Select the dropdown menu trigger item in the next row.
      event.preventDefault();
      selectCompositeItem(activeItemIndex + 1, generateDropdownTriggerCompositeId);
    }
    if (event.key === 'ArrowUp') {
      // Select the dropdown menu trigger item in the previous row.
      event.preventDefault();
      selectCompositeItem(activeItemIndex - 1, generateDropdownTriggerCompositeId);
    }
  }, [selectCompositeItem, activeItemIndex]);
  const hasData = data?.length;
  if (!hasData) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: (0,clsx/* default */.A)({
        'dataviews-loading': isLoading,
        'dataviews-no-results': !hasData && !isLoading
      }),
      children: !hasData && /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
        children: isLoading ? /*#__PURE__*/(0,jsx_runtime.jsx)(spinner, {}) : (0,build_module.__)('No results')
      })
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Composite, {
    id: baseId,
    render: /*#__PURE__*/(0,jsx_runtime.jsx)("ul", {}),
    className: "dataviews-view-list",
    role: "grid",
    activeId: activeCompositeId,
    setActiveId: setActiveCompositeId,
    children: data.map(item => {
      const id = generateCompositeItemIdPrefix(item);
      return /*#__PURE__*/(0,jsx_runtime.jsx)(ListItem, {
        idPrefix: id,
        actions: actions,
        item: item,
        isSelected: item === selectedItem,
        onSelect: onSelect,
        mediaField: mediaField,
        primaryField: primaryField,
        visibleFields: visibleFields,
        onDropdownTriggerKeyDown: onDropdownTriggerKeyDown
      }, id);
    })
  });
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/dataviews-layouts/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */




const VIEW_LAYOUTS = [{
  type: LAYOUT_TABLE,
  label: (0,build_module.__)('Table'),
  component: table,
  icon: block_table
}, {
  type: LAYOUT_GRID,
  label: (0,build_module.__)('Grid'),
  component: ViewGrid,
  icon: library_category
}, {
  type: LAYOUT_LIST,
  label: (0,build_module.__)('List'),
  component: ViewList,
  icon: (0,build_module/* isRTL */.V8)() ? format_list_bullets_rtl : format_list_bullets
}];
function getNotHidableFieldIds(view) {
  if (view.type === 'table') {
    var _view$layout$combined;
    return [view.layout?.primaryField].concat((_view$layout$combined = view.layout?.combinedFields?.flatMap(field => field.children)) !== null && _view$layout$combined !== void 0 ? _view$layout$combined : []).filter(item => !!item);
  }
  if (view.type === 'grid') {
    return [view.layout?.primaryField, view.layout?.mediaField].filter(item => !!item);
  }
  if (view.type === 'list') {
    return [view.layout?.primaryField, view.layout?.mediaField].filter(item => !!item);
  }
  return [];
}
function getCombinedFieldIds(view) {
  const combinedFields = [];
  if (view.type === LAYOUT_TABLE && view.layout?.combinedFields) {
    view.layout.combinedFields.forEach(combination => {
      combinedFields.push(...combination.children);
    });
  }
  return combinedFields;
}
function getVisibleFieldIds(view, fields) {
  const fieldsToExclude = getCombinedFieldIds(view);
  if (view.fields) {
    return view.fields.filter(id => !fieldsToExclude.includes(id));
  }
  const visibleFields = [];
  if (view.type === LAYOUT_TABLE && view.layout?.combinedFields) {
    visibleFields.push(...view.layout.combinedFields.map(({
      id
    }) => id));
  }
  visibleFields.push(...fields.filter(({
    id
  }) => !fieldsToExclude.includes(id)).map(({
    id
  }) => id));
  return visibleFields;
}
function getHiddenFieldIds(view, fields) {
  const fieldsToExclude = [...getCombinedFieldIds(view), ...getVisibleFieldIds(view, fields)];

  // The media field does not need to be in the view.fields to be displayed.
  if (view.type === LAYOUT_GRID && view.layout?.mediaField) {
    fieldsToExclude.push(view.layout?.mediaField);
  }
  if (view.type === LAYOUT_LIST && view.layout?.mediaField) {
    fieldsToExclude.push(view.layout?.mediaField);
  }
  return fields.filter(({
    id,
    enableHiding
  }) => !fieldsToExclude.includes(id) && enableHiding).map(({
    id
  }) => id);
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-layout/index.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function DataViewsLayout() {
  const {
    actions = [],
    data,
    fields,
    getItemId,
    isLoading,
    view,
    onChangeView,
    selection,
    onChangeSelection,
    setOpenedFilter,
    density
  } = (0,react.useContext)(dataviews_context);
  const ViewComponent = VIEW_LAYOUTS.find(v => v.type === view.type)?.component;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(ViewComponent, {
    actions: actions,
    data: data,
    fields: fields,
    getItemId: getItemId,
    isLoading: isLoading,
    onChangeView: onChangeView,
    onChangeSelection: onChangeSelection,
    selection: selection,
    setOpenedFilter: setOpenedFilter,
    view: view,
    density: density
  });
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/next.js
/**
 * WordPress dependencies
 */


const next = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M6.6 6L5.4 7l4.5 5-4.5 5 1.1 1 5.5-6-5.4-6zm6 0l-1.1 1 4.5 5-4.5 5 1.1 1 5.5-6-5.5-6z"
  })
});
/* harmony default export */ const library_next = (next);
//# sourceMappingURL=next.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/previous.js
/**
 * WordPress dependencies
 */


const previous = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M11.6 7l-1.1-1L5 12l5.5 6 1.1-1L7 12l4.6-5zm6 0l-1.1-1-5.5 6 5.5 6 1.1-1-4.6-5 4.6-5z"
  })
});
/* harmony default export */ const library_previous = (previous);
//# sourceMappingURL=previous.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-pagination/index.js
/**
 * WordPress dependencies
 */





/**
 * Internal dependencies
 */



function DataViewsPagination() {
  var _view$page;
  const {
    view,
    onChangeView,
    paginationInfo: {
      totalItems = 0,
      totalPages
    }
  } = (0,react.useContext)(dataviews_context);
  if (!totalItems || !totalPages) {
    return null;
  }
  const currentPage = (_view$page = view.page) !== null && _view$page !== void 0 ? _view$page : 1;
  const pageSelectOptions = Array.from(Array(totalPages)).map((_, i) => {
    const page = i + 1;
    return {
      value: page.toString(),
      label: page.toString(),
      'aria-label': currentPage === page ? (0,build_module/* sprintf */.nv)(
      // translators: Current page number in total number of pages
      (0,build_module.__)('Page %1$s of %2$s'), currentPage, totalPages) : page.toString()
    };
  });
  return !!totalItems && totalPages !== 1 && /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
    expanded: false,
    className: "dataviews-pagination",
    justify: "end",
    spacing: 6,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
      justify: "flex-start",
      expanded: false,
      spacing: 1,
      className: "dataviews-pagination__page-select",
      children: create_interpolate_element((0,build_module/* sprintf */.nv)(
      // translators: 1: Current page number, 2: Total number of pages.
      (0,build_module._x)('<div>Page</div>%1$s<div>of %2$s</div>', 'paging'), '<CurrentPage />', totalPages), {
        div: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          "aria-hidden": true
        }),
        CurrentPage: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
          "aria-label": (0,build_module.__)('Current page'),
          value: currentPage.toString(),
          options: pageSelectOptions,
          onChange: newValue => {
            onChangeView({
              ...view,
              page: +newValue
            });
          },
          size: "small",
          __nextHasNoMarginBottom: true,
          variant: "minimal"
        })
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      expanded: false,
      spacing: 1,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        onClick: () => onChangeView({
          ...view,
          page: currentPage - 1
        }),
        disabled: currentPage === 1,
        accessibleWhenDisabled: true,
        label: (0,build_module.__)('Previous page'),
        icon: (0,build_module/* isRTL */.V8)() ? library_next : library_previous,
        showTooltip: true,
        size: "compact",
        tooltipPosition: "top"
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        onClick: () => onChangeView({
          ...view,
          page: currentPage + 1
        }),
        disabled: currentPage >= totalPages,
        accessibleWhenDisabled: true,
        label: (0,build_module.__)('Next page'),
        icon: (0,build_module/* isRTL */.V8)() ? library_previous : library_next,
        showTooltip: true,
        size: "compact",
        tooltipPosition: "top"
      })]
    })]
  });
}
/* harmony default export */ const dataviews_pagination = ((0,react.memo)(DataViewsPagination));
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-footer/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */






const dataviews_footer_EMPTY_ARRAY = [];
function DataViewsFooter() {
  const {
    view,
    paginationInfo: {
      totalItems = 0,
      totalPages
    },
    data,
    actions = dataviews_footer_EMPTY_ARRAY
  } = (0,react.useContext)(dataviews_context);
  const hasBulkActions = useSomeItemHasAPossibleBulkAction(actions, data) && [LAYOUT_TABLE, LAYOUT_GRID].includes(view.type);
  if (!totalItems || !totalPages || totalPages <= 1 && !hasBulkActions) {
    return null;
  }
  return !!totalItems && /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
    expanded: false,
    justify: "end",
    className: "dataviews-footer",
    children: [hasBulkActions && /*#__PURE__*/(0,jsx_runtime.jsx)(BulkActionsFooter, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_pagination, {})]
  });
}
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/input-control/index.js + 8 modules
var input_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/input-control/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/search-control/styles.js

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const inlinePadding = ({
  size
}) => {
  return (0,space/* space */.x)(size === 'compact' ? 1 : 2);
};
const SuffixItemWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "effl84m1"
} : 0)("display:flex;padding-inline-end:", inlinePadding, ";svg{fill:currentColor;}" + ( true ? "" : 0));
const StyledInputControl = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(input_control/* default */.Ay,  true ? {
  target: "effl84m0"
} : 0)("input[type='search']{&::-webkit-search-decoration,&::-webkit-search-cancel-button,&::-webkit-search-results-button,&::-webkit-search-results-decoration{-webkit-appearance:none;}}&:not( :focus-within ){--wp-components-color-background:", colors_values/* COLORS */.l.theme.gray[100], ";}" + ( true ? "" : 0));
//# sourceMappingURL=styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/search-control/index.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */





/**
 * Internal dependencies
 */




function SuffixItem({
  searchRef,
  value,
  onChange,
  onClose
}) {
  if (!onClose && !value) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
      icon: library_search
    });
  }
  const onReset = () => {
    onChange('');
    searchRef.current?.focus();
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    size: "small",
    icon: close_small/* default */.A,
    label: onClose ? (0,build_module.__)('Close search') : (0,build_module.__)('Reset search'),
    onClick: onClose !== null && onClose !== void 0 ? onClose : onReset
  });
}
function UnforwardedSearchControl({
  __nextHasNoMarginBottom = false,
  className,
  onChange,
  value,
  label = (0,build_module.__)('Search'),
  placeholder = (0,build_module.__)('Search'),
  hideLabelFromVision = true,
  onClose,
  size = 'default',
  ...restProps
}, forwardedRef) {
  // @ts-expect-error The `disabled` prop is not yet supported in the SearchControl component.
  // Work with the design team (@WordPress/gutenberg-design) if you need this feature.
  delete restProps.disabled;
  const searchRef = (0,react.useRef)(null);
  const instanceId = (0,use_instance_id/* default */.A)(SearchControl, 'components-search-control');
  const contextValue = (0,react.useMemo)(() => ({
    BaseControl: {
      // Overrides the underlying BaseControl `__nextHasNoMarginBottom` via the context system
      // to provide backwards compatibile margin for SearchControl.
      // (In a standard InputControl, the BaseControl `__nextHasNoMarginBottom` is always set to true.)
      _overrides: {
        __nextHasNoMarginBottom
      },
      __associatedWPComponentName: 'SearchControl'
    },
    // `isBorderless` is still experimental and not a public prop for InputControl yet.
    InputBase: {
      isBorderless: true
    }
  }), [__nextHasNoMarginBottom]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(context_system_provider/* ContextSystemProvider */.c7, {
    value: contextValue,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(StyledInputControl, {
      __next40pxDefaultSize: true,
      id: instanceId,
      hideLabelFromVision: hideLabelFromVision,
      label: label,
      ref: (0,use_merge_refs/* default */.A)([searchRef, forwardedRef]),
      type: "search",
      size: size,
      className: (0,clsx/* default */.A)('components-search-control', className),
      onChange: nextValue => onChange(nextValue !== null && nextValue !== void 0 ? nextValue : ''),
      autoComplete: "off",
      placeholder: placeholder,
      value: value !== null && value !== void 0 ? value : '',
      suffix: /*#__PURE__*/(0,jsx_runtime.jsx)(SuffixItemWrapper, {
        size: size,
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(SuffixItem, {
          searchRef: searchRef,
          value: value,
          onChange: onChange,
          onClose: onClose
        })
      }),
      ...restProps
    })
  });
}

/**
 * SearchControl components let users display a search control.
 *
 * ```jsx
 * import { SearchControl } from '@wordpress/components';
 * import { useState } from '@wordpress/element';
 *
 * function MySearchControl( { className, setState } ) {
 *   const [ searchInput, setSearchInput ] = useState( '' );
 *
 *   return (
 *     <SearchControl
 *       __nextHasNoMarginBottom
 *       value={ searchInput }
 *       onChange={ setSearchInput }
 *     />
 *   );
 * }
 * ```
 */
const SearchControl = (0,react.forwardRef)(UnforwardedSearchControl);
/* harmony default export */ const search_control = (SearchControl);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/use-memo-one@1.1.3_react@17.0.2/node_modules/use-memo-one/dist/use-memo-one.esm.js
var use_memo_one_esm = __webpack_require__("../../node_modules/.pnpm/use-memo-one@1.1.3_react@17.0.2/node_modules/use-memo-one/dist/use-memo-one.esm.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/debounce/index.js
/**
 * Parts of this source were derived and modified from lodash,
 * released under the MIT license.
 *
 * https://github.com/lodash/lodash
 *
 * Copyright JS Foundation and other contributors <https://js.foundation/>
 *
 * Based on Underscore.js, copyright Jeremy Ashkenas,
 * DocumentCloud and Investigative Reporters & Editors <http://underscorejs.org/>
 *
 * This software consists of voluntary contributions made by many
 * individuals. For exact contribution history, see the revision history
 * available at https://github.com/lodash/lodash
 *
 * The following license applies to all parts of this software except as
 * documented below:
 *
 * ====
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * A simplified and properly typed version of lodash's `debounce`, that
 * always uses timers instead of sometimes using rAF.
 *
 * Creates a debounced function that delays invoking `func` until after `wait`
 * milliseconds have elapsed since the last time the debounced function was
 * invoked. The debounced function comes with a `cancel` method to cancel delayed
 * `func` invocations and a `flush` method to immediately invoke them. Provide
 * `options` to indicate whether `func` should be invoked on the leading and/or
 * trailing edge of the `wait` timeout. The `func` is invoked with the last
 * arguments provided to the debounced function. Subsequent calls to the debounced
 * function return the result of the last `func` invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the debounced function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * @param {Function}                   func             The function to debounce.
 * @param {number}                     wait             The number of milliseconds to delay.
 * @param {Partial< DebounceOptions >} options          The options object.
 * @param {boolean}                    options.leading  Specify invoking on the leading edge of the timeout.
 * @param {number}                     options.maxWait  The maximum time `func` is allowed to be delayed before it's invoked.
 * @param {boolean}                    options.trailing Specify invoking on the trailing edge of the timeout.
 *
 * @return Returns the new debounced function.
 */
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
    maxing = 'maxWait' in options;
    if (options.maxWait !== undefined) {
      maxWait = Math.max(options.maxWait, wait);
    }
    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }
  function invokeFunc(time) {
    const args = lastArgs;
    const thisArg = lastThis;
    lastArgs = undefined;
    lastThis = undefined;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }
  function startTimer(pendingFunc, waitTime) {
    timerId = setTimeout(pendingFunc, waitTime);
  }
  function cancelTimer() {
    if (timerId !== undefined) {
      clearTimeout(timerId);
    }
  }
  function leadingEdge(time) {
    // Reset any `maxWait` timer.
    lastInvokeTime = time;
    // Start the timer for the trailing edge.
    startTimer(timerExpired, wait);
    // Invoke the leading edge.
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

    // Either this is the first call, activity has stopped and we're at the
    // trailing edge, the system time has gone backwards and we're treating
    // it as the trailing edge, or we've hit the `maxWait` limit.
    return lastCallTime === undefined || timeSinceLastCall >= wait || timeSinceLastCall < 0 || maxing && timeSinceLastInvoke >= maxWait;
  }
  function timerExpired() {
    const time = Date.now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    // Restart the timer.
    startTimer(timerExpired, remainingWait(time));
    return undefined;
  }
  function clearTimer() {
    timerId = undefined;
  }
  function trailingEdge(time) {
    clearTimer();

    // Only invoke if we have `lastArgs` which means `func` has been
    // debounced at least once.
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = undefined;
    return result;
  }
  function cancel() {
    cancelTimer();
    lastInvokeTime = 0;
    clearTimer();
    lastArgs = lastCallTime = lastThis = undefined;
  }
  function flush() {
    return pending() ? trailingEdge(Date.now()) : result;
  }
  function pending() {
    return timerId !== undefined;
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
        // Handle invocations in a tight loop.
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
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js
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
 * Debounces a function similar to Lodash's `debounce`. A new debounced function will
 * be returned and any scheduled calls cancelled if any of the arguments change,
 * including the function to debounce, so please wrap functions created on
 * render in components in `useCallback`.
 *
 * @see https://lodash.com/docs/4#debounce
 *
 * @template {(...args: any[]) => void} TFunc
 *
 * @param {TFunc}                                          fn        The function to debounce.
 * @param {number}                                         [wait]    The number of milliseconds to delay.
 * @param {import('../../utils/debounce').DebounceOptions} [options] The options object.
 * @return {import('../../utils/debounce').DebouncedFunc<TFunc>} Debounced function.
 */
function useDebounce(fn, wait, options) {
  const debounced = (0,use_memo_one_esm/* useMemoOne */.MA)(() => debounce(fn, wait !== null && wait !== void 0 ? wait : 0, options), [fn, wait, options]);
  (0,react.useEffect)(() => () => debounced.cancel(), [debounced]);
  return debounced;
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounced-input/index.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Helper hook for input fields that need to debounce the value before using it.
 *
 * @param defaultValue The default value to use.
 * @return The input value, the setter and the debounced input value.
 */
function useDebouncedInput(defaultValue = '') {
  const [input, setInput] = (0,react.useState)(defaultValue);
  const [debouncedInput, setDebouncedState] = (0,react.useState)(defaultValue);
  const setDebouncedInput = useDebounce(setDebouncedState, 250);
  (0,react.useEffect)(() => {
    setDebouncedInput(input);
  }, [input, setDebouncedInput]);
  return [input, setInput, debouncedInput];
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-search/index.js
/**
 * WordPress dependencies
 */





/**
 * Internal dependencies
 */


const DataViewsSearch = (0,react.memo)(function Search({
  label
}) {
  const {
    view,
    onChangeView
  } = (0,react.useContext)(dataviews_context);
  const [search, setSearch, debouncedSearch] = useDebouncedInput(view.search);
  (0,react.useEffect)(() => {
    var _view$search;
    setSearch((_view$search = view.search) !== null && _view$search !== void 0 ? _view$search : '');
  }, [view.search, setSearch]);
  const onChangeViewRef = (0,react.useRef)(onChangeView);
  const viewRef = (0,react.useRef)(view);
  (0,react.useEffect)(() => {
    onChangeViewRef.current = onChangeView;
    viewRef.current = view;
  }, [onChangeView, view]);
  (0,react.useEffect)(() => {
    if (debouncedSearch !== viewRef.current?.search) {
      onChangeViewRef.current({
        ...viewRef.current,
        page: 1,
        search: debouncedSearch
      });
    }
  }, [debouncedSearch]);
  const searchLabel = label || (0,build_module.__)('Search');
  return /*#__PURE__*/(0,jsx_runtime.jsx)(search_control, {
    className: "dataviews-search",
    __nextHasNoMarginBottom: true,
    onChange: setSearch,
    value: search,
    label: searchLabel,
    placeholder: searchLabel,
    size: "compact"
  });
});
/* harmony default export */ const dataviews_search = (DataViewsSearch);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control/component.js + 12 modules
var toggle_group_control_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option-base/component.js + 1 modules
var toggle_group_control_option_base_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option-base/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option-icon/component.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */




function UnforwardedToggleGroupControlOptionIcon(props, ref) {
  const {
    icon,
    label,
    ...restProps
  } = props;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(toggle_group_control_option_base_component/* default */.A, {
    ...restProps,
    isIcon: true,
    "aria-label": label,
    showTooltip: true,
    ref: ref,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
      icon: icon
    })
  });
}

/**
 * `ToggleGroupControlOptionIcon` is a form component which is meant to be used as a
 * child of `ToggleGroupControl` and displays an icon.
 *
 * ```jsx
 *
 * import {
 *	__experimentalToggleGroupControl as ToggleGroupControl,
 *	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
 * from '@wordpress/components';
 * import { formatLowercase, formatUppercase } from '@wordpress/icons';
 *
 * function Example() {
 *  return (
 *    <ToggleGroupControl __nextHasNoMarginBottom>
 *      <ToggleGroupControlOptionIcon
 *        value="uppercase"
 *        label="Uppercase"
 *        icon={ formatUppercase }
 *      />
 *      <ToggleGroupControlOptionIcon
 *        value="lowercase"
 *        label="Lowercase"
 *        icon={ formatLowercase }
 *      />
 *    </ToggleGroupControl>
 *  );
 * }
 * ```
 */
const ToggleGroupControlOptionIcon = (0,react.forwardRef)(UnforwardedToggleGroupControlOptionIcon);
/* harmony default export */ const toggle_group_control_option_icon_component = (ToggleGroupControlOptionIcon);
//# sourceMappingURL=component.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option/component.js
var toggle_group_control_option_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/toggle-group-control/toggle-group-control-option/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/item-group/styles.js
function item_group_styles_EMOTION_STRINGIFIED_CSS_ERROR_() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

const unstyledButton = as => {
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("font-size:", (0,font/* font */.g)('default.fontSize'), ";font-family:inherit;appearance:none;border:1px solid transparent;cursor:pointer;background:none;text-align:start;text-decoration:", as === 'a' ? 'none' : undefined, ";svg,path{fill:currentColor;}&:hover{color:", colors_values/* COLORS */.l.theme.accent, ";}&:focus{box-shadow:none;outline:none;}&:focus-visible{box-shadow:0 0 0 var( --wp-admin-border-width-focus ) ", colors_values/* COLORS */.l.theme.accent, ";outline:2px solid transparent;outline-offset:0;}" + ( true ? "" : 0),  true ? "" : 0);
};
const itemWrapper =  true ? {
  name: "1bcj5ek",
  styles: "width:100%;display:block"
} : 0;
const item =  true ? {
  name: "150ruhm",
  styles: "box-sizing:border-box;width:100%;display:block;margin:0;color:inherit"
} : 0;
const bordered = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("border:1px solid ", config_values/* default */.A.surfaceBorderColor, ";" + ( true ? "" : 0),  true ? "" : 0);
const separated = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)(">*:not( marquee )>*{border-bottom:1px solid ", config_values/* default */.A.surfaceBorderColor, ";}>*:last-of-type>*:not( :focus ){border-bottom-color:transparent;}" + ( true ? "" : 0),  true ? "" : 0);
const borderRadius = config_values/* default */.A.radiusSmall;
const styles_spacedAround = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("border-radius:", borderRadius, ";" + ( true ? "" : 0),  true ? "" : 0);
const rounded = /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("border-radius:", borderRadius, ";>*:first-of-type>*{border-top-left-radius:", borderRadius, ";border-top-right-radius:", borderRadius, ";}>*:last-of-type>*{border-bottom-left-radius:", borderRadius, ";border-bottom-right-radius:", borderRadius, ";}" + ( true ? "" : 0),  true ? "" : 0);
const baseFontHeight = `calc(${config_values/* default */.A.fontSize} * ${config_values/* default */.A.fontLineHeightBase})`;

/*
 * Math:
 * - Use the desired height as the base value
 * - Subtract the computed height of (default) text
 * - Subtract the effects of border
 * - Divide the calculated number by 2, in order to get an individual top/bottom padding
 */
const paddingY = `calc((${config_values/* default */.A.controlHeight} - ${baseFontHeight} - 2px) / 2)`;
const paddingYSmall = `calc((${config_values/* default */.A.controlHeightSmall} - ${baseFontHeight} - 2px) / 2)`;
const paddingYLarge = `calc((${config_values/* default */.A.controlHeightLarge} - ${baseFontHeight} - 2px) / 2)`;
const itemSizes = {
  small: /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("padding:", paddingYSmall, " ", config_values/* default */.A.controlPaddingXSmall, "px;" + ( true ? "" : 0),  true ? "" : 0),
  medium: /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("padding:", paddingY, " ", config_values/* default */.A.controlPaddingX, "px;" + ( true ? "" : 0),  true ? "" : 0),
  large: /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("padding:", paddingYLarge, " ", config_values/* default */.A.controlPaddingXLarge, "px;" + ( true ? "" : 0),  true ? "" : 0)
};
//# sourceMappingURL=styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/item-group/context.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */

const ItemGroupContext = (0,react.createContext)({
  size: 'medium'
});
const useItemGroupContext = () => (0,react.useContext)(ItemGroupContext);
//# sourceMappingURL=context.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/item-group/item/hook.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */





function useItem(props) {
  const {
    as: asProp,
    className,
    onClick,
    role = 'listitem',
    size: sizeProp,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'Item');
  const {
    spacedAround,
    size: contextSize
  } = useItemGroupContext();
  const size = sizeProp || contextSize;
  const as = asProp || (typeof onClick !== 'undefined' ? 'button' : 'div');
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => cx((as === 'button' || as === 'a') && unstyledButton(as), itemSizes[size] || itemSizes.medium, item, spacedAround && styles_spacedAround, className), [as, className, cx, size, spacedAround]);
  const wrapperClassName = cx(itemWrapper);
  return {
    as,
    className: classes,
    onClick,
    wrapperClassName,
    role,
    ...otherProps
  };
}
//# sourceMappingURL=hook.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/item-group/item/component.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */





function UnconnectedItem(props, forwardedRef) {
  const {
    role,
    wrapperClassName,
    ...otherProps
  } = useItem(props);
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    role: role,
    className: wrapperClassName,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(view_component/* default */.A, {
      ...otherProps,
      ref: forwardedRef
    })
  });
}

/**
 * `Item` is used in combination with `ItemGroup` to display a list of items
 * grouped and styled together.
 *
 * ```jsx
 * import {
 *   __experimentalItemGroup as ItemGroup,
 *   __experimentalItem as Item,
 * } from '@wordpress/components';
 *
 * function Example() {
 *   return (
 *     <ItemGroup>
 *       <Item>Code</Item>
 *       <Item>is</Item>
 *       <Item>Poetry</Item>
 *     </ItemGroup>
 *   );
 * }
 * ```
 */
const Item = (0,context_connect/* contextConnect */.KZ)(UnconnectedItem, 'Item');
/* harmony default export */ const item_component = (Item);
//# sourceMappingURL=component.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/item-group/item-group/hook.js
/**
 * Internal dependencies
 */



/**
 * Internal dependencies
 */


function useItemGroup(props) {
  const {
    className,
    isBordered = false,
    isRounded = true,
    isSeparated = false,
    role = 'list',
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'ItemGroup');
  const cx = (0,use_cx/* useCx */.l)();
  const classes = cx(isBordered && bordered, isSeparated && separated, isRounded && rounded, className);
  return {
    isBordered,
    className: classes,
    role,
    isSeparated,
    ...otherProps
  };
}
//# sourceMappingURL=hook.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/item-group/item-group/component.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */






function UnconnectedItemGroup(props, forwardedRef) {
  const {
    isBordered,
    isSeparated,
    size: sizeProp,
    ...otherProps
  } = useItemGroup(props);
  const {
    size: contextSize
  } = useItemGroupContext();
  const spacedAround = !isBordered && !isSeparated;
  const size = sizeProp || contextSize;
  const contextValue = {
    spacedAround,
    size
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(ItemGroupContext.Provider, {
    value: contextValue,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(view_component/* default */.A, {
      ...otherProps,
      ref: forwardedRef
    })
  });
}

/**
 * `ItemGroup` displays a list of `Item`s grouped and styled together.
 *
 * ```jsx
 * import {
 *   __experimentalItemGroup as ItemGroup,
 *   __experimentalItem as Item,
 * } from '@wordpress/components';
 *
 * function Example() {
 *   return (
 *     <ItemGroup>
 *       <Item>Code</Item>
 *       <Item>is</Item>
 *       <Item>Poetry</Item>
 *     </ItemGroup>
 *   );
 * }
 * ```
 */
const ItemGroup = (0,context_connect/* contextConnect */.KZ)(UnconnectedItemGroup, 'ItemGroup');
/* harmony default export */ const item_group_component = (ItemGroup);
//# sourceMappingURL=component.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/heading/component.js + 1 modules
var heading_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/heading/component.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/chevron-up.js
/**
 * WordPress dependencies
 */


const chevronUp = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z"
  })
});
/* harmony default export */ const chevron_up = (chevronUp);
//# sourceMappingURL=chevron-up.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/seen.js
/**
 * WordPress dependencies
 */


const seen = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    d: "M3.99961 13C4.67043 13.3354 4.6703 13.3357 4.67017 13.3359L4.67298 13.3305C4.67621 13.3242 4.68184 13.3135 4.68988 13.2985C4.70595 13.2686 4.7316 13.2218 4.76695 13.1608C4.8377 13.0385 4.94692 12.8592 5.09541 12.6419C5.39312 12.2062 5.84436 11.624 6.45435 11.0431C7.67308 9.88241 9.49719 8.75 11.9996 8.75C14.502 8.75 16.3261 9.88241 17.5449 11.0431C18.1549 11.624 18.6061 12.2062 18.9038 12.6419C19.0523 12.8592 19.1615 13.0385 19.2323 13.1608C19.2676 13.2218 19.2933 13.2686 19.3093 13.2985C19.3174 13.3135 19.323 13.3242 19.3262 13.3305L19.3291 13.3359C19.3289 13.3357 19.3288 13.3354 19.9996 13C20.6704 12.6646 20.6703 12.6643 20.6701 12.664L20.6697 12.6632L20.6688 12.6614L20.6662 12.6563L20.6583 12.6408C20.6517 12.6282 20.6427 12.6108 20.631 12.5892C20.6078 12.5459 20.5744 12.4852 20.5306 12.4096C20.4432 12.2584 20.3141 12.0471 20.1423 11.7956C19.7994 11.2938 19.2819 10.626 18.5794 9.9569C17.1731 8.61759 14.9972 7.25 11.9996 7.25C9.00203 7.25 6.82614 8.61759 5.41987 9.9569C4.71736 10.626 4.19984 11.2938 3.85694 11.7956C3.68511 12.0471 3.55605 12.2584 3.4686 12.4096C3.42484 12.4852 3.39142 12.5459 3.36818 12.5892C3.35656 12.6108 3.34748 12.6282 3.34092 12.6408L3.33297 12.6563L3.33041 12.6614L3.32948 12.6632L3.32911 12.664C3.32894 12.6643 3.32879 12.6646 3.99961 13ZM11.9996 16C13.9326 16 15.4996 14.433 15.4996 12.5C15.4996 10.567 13.9326 9 11.9996 9C10.0666 9 8.49961 10.567 8.49961 12.5C8.49961 14.433 10.0666 16 11.9996 16Z"
  })
});
/* harmony default export */ const library_seen = (seen);
//# sourceMappingURL=seen.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+icons@10.8.1_react@17.0.2/node_modules/@wordpress/icons/build-module/library/cog.js
/**
 * WordPress dependencies
 */


const cog = /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* SVG */.t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(svg/* Path */.wA, {
    fillRule: "evenodd",
    d: "M10.289 4.836A1 1 0 0111.275 4h1.306a1 1 0 01.987.836l.244 1.466c.787.26 1.503.679 2.108 1.218l1.393-.522a1 1 0 011.216.437l.653 1.13a1 1 0 01-.23 1.273l-1.148.944a6.025 6.025 0 010 2.435l1.149.946a1 1 0 01.23 1.272l-.653 1.13a1 1 0 01-1.216.437l-1.394-.522c-.605.54-1.32.958-2.108 1.218l-.244 1.466a1 1 0 01-.987.836h-1.306a1 1 0 01-.986-.836l-.244-1.466a5.995 5.995 0 01-2.108-1.218l-1.394.522a1 1 0 01-1.217-.436l-.653-1.131a1 1 0 01.23-1.272l1.149-.946a6.026 6.026 0 010-2.435l-1.148-.944a1 1 0 01-.23-1.272l.653-1.131a1 1 0 011.217-.437l1.393.522a5.994 5.994 0 012.108-1.218l.244-1.466zM14.929 12a3 3 0 11-6 0 3 3 0 016 0z",
    clipRule: "evenodd"
  })
});
/* harmony default export */ const library_cog = (cog);
//# sourceMappingURL=cog.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/values.js
var values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/values.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-state.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * @template T
 * @typedef Options
 * @property {T}      [initial] Initial value
 * @property {T | ""} fallback  Fallback value
 */

/** @type {Readonly<{ initial: undefined, fallback: '' }>} */
const defaultOptions = {
  initial: undefined,
  /**
   * Defaults to empty string, as that is preferred for usage with
   * <input />, <textarea />, and <select /> form elements.
   */
  fallback: ''
};

/**
 * Custom hooks for "controlled" components to track and consolidate internal
 * state and incoming values. This is useful for components that render
 * `input`, `textarea`, or `select` HTML elements.
 *
 * https://reactjs.org/docs/forms.html#controlled-components
 *
 * At first, a component using useControlledState receives an initial prop
 * value, which is used as initial internal state.
 *
 * This internal state can be maintained and updated without
 * relying on new incoming prop values.
 *
 * Unlike the basic useState hook, useControlledState's state can
 * be updated if a new incoming prop value is changed.
 *
 * @template T
 *
 * @param {T | undefined} currentState             The current value.
 * @param {Options<T>}    [options=defaultOptions] Additional options for the hook.
 *
 * @return {[T | "", (nextState: T) => void]} The controlled value and the value setter.
 */
function useControlledState(currentState, options = defaultOptions) {
  const {
    initial,
    fallback
  } = {
    ...defaultOptions,
    ...options
  };
  const [internalState, setInternalState] = (0,react.useState)(currentState);
  const hasCurrentState = (0,values/* isValueDefined */.J5)(currentState);

  /*
   * Resets internal state if value every changes from uncontrolled <-> controlled.
   */
  (0,react.useEffect)(() => {
    if (hasCurrentState && internalState) {
      setInternalState(undefined);
    }
  }, [hasCurrentState, internalState]);
  const state = (0,values/* getDefinedValue */.vD)([currentState, internalState, initial], fallback);

  /* eslint-disable jsdoc/no-undefined-types */
  /** @type {(nextState: T) => void} */
  const setState = (0,react.useCallback)(nextState => {
    if (!hasCurrentState) {
      setInternalState(nextState);
    }
  }, [hasCurrentState]);
  /* eslint-enable jsdoc/no-undefined-types */

  return [state, setState];
}
/* harmony default export */ const use_controlled_state = (useControlledState);
//# sourceMappingURL=use-controlled-state.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/math.js
var math = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/utils/math.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/range-control/utils.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * A float supported clamp function for a specific value.
 *
 * @param value The value to clamp.
 * @param min   The minimum value.
 * @param max   The maximum value.
 *
 * @return A (float) number
 */
function floatClamp(value, min, max) {
  if (typeof value !== 'number') {
    return null;
  }
  return parseFloat(`${(0,math/* clamp */.qE)(value, min, max)}`);
}

/**
 * Hook to store a clamped value, derived from props.
 *
 * @param settings
 * @return The controlled value and the value setter.
 */
function useControlledRangeValue(settings) {
  const {
    min,
    max,
    value: valueProp,
    initial
  } = settings;
  const [state, setInternalState] = use_controlled_state(floatClamp(valueProp, min, max), {
    initial: floatClamp(initial !== null && initial !== void 0 ? initial : null, min, max),
    fallback: null
  });
  const setState = (0,react.useCallback)(nextValue => {
    if (nextValue === null) {
      setInternalState(null);
    } else {
      setInternalState(floatClamp(nextValue, min, max));
    }
  }, [min, max, setInternalState]);

  // `state` can't be an empty string because we specified a fallback value of
  // `null` in `useControlledState`
  return [state, setState];
}
//# sourceMappingURL=utils.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/number-control/index.js + 2 modules
var number_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/number-control/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/range-control/styles/range-control-styles.js

function range_control_styles_EMOTION_STRINGIFIED_CSS_ERROR_() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const rangeHeightValue = 30;
const railHeight = 4;
const rangeHeight = () => /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
  height: rangeHeightValue,
  minHeight: rangeHeightValue
},  true ? "" : 0,  true ? "" : 0);
const thumbSize = 12;
const deprecatedHeight = ({
  __next40pxDefaultSize
}) => !__next40pxDefaultSize && /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
  minHeight: rangeHeightValue
},  true ? "" : 0,  true ? "" : 0);
const Root = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1epgpqk14"
} : 0)("-webkit-tap-highlight-color:transparent;align-items:center;display:flex;justify-content:flex-start;padding:0;position:relative;touch-action:none;width:100%;min-height:40px;", deprecatedHeight, ";" + ( true ? "" : 0));
const wrapperColor = ({
  color = colors_values/* COLORS */.l.ui.borderFocus
}) => /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
  color
},  true ? "" : 0,  true ? "" : 0);
const wrapperMargin = ({
  marks,
  __nextHasNoMarginBottom
}) => {
  if (!__nextHasNoMarginBottom) {
    return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
      marginBottom: marks ? 16 : undefined
    },  true ? "" : 0,  true ? "" : 0);
  }
  return '';
};
const range_control_styles_Wrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("div",  true ? {
  target: "e1epgpqk13"
} : 0)("display:block;flex:1;position:relative;width:100%;", wrapperColor, ";", rangeHeight, ";", wrapperMargin, ";" + ( true ? "" : 0));
const BeforeIconWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk12"
} : 0)("display:flex;margin-top:", railHeight, "px;", (0,rtl/* rtl */.h)({
  marginRight: 6
}), ";" + ( true ? "" : 0));
const AfterIconWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk11"
} : 0)("display:flex;margin-top:", railHeight, "px;", (0,rtl/* rtl */.h)({
  marginLeft: 6
}), ";" + ( true ? "" : 0));
const railBackgroundColor = ({
  disabled,
  railColor
}) => {
  let background = railColor || '';
  if (disabled) {
    background = colors_values/* COLORS */.l.ui.backgroundDisabled;
  }
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
    background
  },  true ? "" : 0,  true ? "" : 0);
};
const Rail = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk10"
} : 0)("background-color:", colors_values/* COLORS */.l.gray[300], ";left:0;pointer-events:none;right:0;display:block;height:", railHeight, "px;position:absolute;margin-top:", (rangeHeightValue - railHeight) / 2, "px;top:0;border-radius:", config_values/* default */.A.radiusFull, ";", railBackgroundColor, ";" + ( true ? "" : 0));
const trackBackgroundColor = ({
  disabled,
  trackColor
}) => {
  let background = trackColor || 'currentColor';
  if (disabled) {
    background = colors_values/* COLORS */.l.gray[400];
  }
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
    background
  },  true ? "" : 0,  true ? "" : 0);
};
const Track = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk9"
} : 0)("background-color:currentColor;border-radius:", config_values/* default */.A.radiusFull, ";height:", railHeight, "px;pointer-events:none;display:block;position:absolute;margin-top:", (rangeHeightValue - railHeight) / 2, "px;top:0;", trackBackgroundColor, ";" + ( true ? "" : 0));
const MarksWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk8"
} : 0)( true ? {
  name: "l7tjj5",
  styles: "display:block;pointer-events:none;position:relative;width:100%;user-select:none"
} : 0);
const markFill = ({
  disabled,
  isFilled
}) => {
  let backgroundColor = isFilled ? 'currentColor' : colors_values/* COLORS */.l.gray[300];
  if (disabled) {
    backgroundColor = colors_values/* COLORS */.l.gray[400];
  }
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
    backgroundColor
  },  true ? "" : 0,  true ? "" : 0);
};
const Mark = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk7"
} : 0)("height:", thumbSize, "px;left:0;position:absolute;top:9px;width:1px;", markFill, ";" + ( true ? "" : 0));
const markLabelFill = ({
  isFilled
}) => {
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
    color: isFilled ? colors_values/* COLORS */.l.gray[700] : colors_values/* COLORS */.l.gray[300]
  },  true ? "" : 0,  true ? "" : 0);
};
const MarkLabel = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk6"
} : 0)("color:", colors_values/* COLORS */.l.gray[300], ";font-size:11px;position:absolute;top:22px;white-space:nowrap;", (0,rtl/* rtl */.h)({
  left: 0
}), ";", (0,rtl/* rtl */.h)({
  transform: 'translateX( -50% )'
}, {
  transform: 'translateX( 50% )'
}), ";", markLabelFill, ";" + ( true ? "" : 0));
const thumbColor = ({
  disabled
}) => disabled ? /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("background-color:", colors_values/* COLORS */.l.gray[400], ";" + ( true ? "" : 0),  true ? "" : 0) : /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("background-color:", colors_values/* COLORS */.l.theme.accent, ";" + ( true ? "" : 0),  true ? "" : 0);
const ThumbWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk5"
} : 0)("align-items:center;display:flex;height:", thumbSize, "px;justify-content:center;margin-top:", (rangeHeightValue - thumbSize) / 2, "px;outline:0;pointer-events:none;position:absolute;top:0;user-select:none;width:", thumbSize, "px;border-radius:", config_values/* default */.A.radiusRound, ";", thumbColor, ";", (0,rtl/* rtl */.h)({
  marginLeft: -10
}), ";", (0,rtl/* rtl */.h)({
  transform: 'translateX( 4.5px )'
}, {
  transform: 'translateX( -4.5px )'
}), ";" + ( true ? "" : 0));
const thumbFocus = ({
  isFocused
}) => {
  return isFocused ? /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)("&::before{content:' ';position:absolute;background-color:", colors_values/* COLORS */.l.theme.accent, ";opacity:0.4;border-radius:", config_values/* default */.A.radiusRound, ";height:", thumbSize + 8, "px;width:", thumbSize + 8, "px;top:-4px;left:-4px;}" + ( true ? "" : 0),  true ? "" : 0) : '';
};
const Thumb = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk4"
} : 0)("align-items:center;border-radius:", config_values/* default */.A.radiusRound, ";height:100%;outline:0;position:absolute;user-select:none;width:100%;box-shadow:", config_values/* default */.A.elevationXSmall, ";", thumbColor, ";", thumbFocus, ";" + ( true ? "" : 0));
const InputRange = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("input",  true ? {
  target: "e1epgpqk3"
} : 0)("box-sizing:border-box;cursor:pointer;display:block;height:100%;left:0;margin:0 -", thumbSize / 2, "px;opacity:0;outline:none;position:absolute;right:0;top:0;width:calc( 100% + ", thumbSize, "px );" + ( true ? "" : 0));
const tooltipShow = ({
  show
}) => {
  return /*#__PURE__*/(0,emotion_react_browser_esm/* css */.AH)({
    opacity: show ? 1 : 0
  },  true ? "" : 0,  true ? "" : 0);
};
var _ref =  true ? {
  name: "1cypxip",
  styles: "top:-80%"
} : 0;
var _ref2 =  true ? {
  name: "1lr98c4",
  styles: "bottom:-80%"
} : 0;
const tooltipPosition = ({
  position
}) => {
  const isBottom = position === 'bottom';
  if (isBottom) {
    return _ref2;
  }
  return _ref;
};
const Tooltip = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk2"
} : 0)("background:rgba( 0, 0, 0, 0.8 );border-radius:", config_values/* default */.A.radiusSmall, ";color:white;display:inline-block;font-size:12px;min-width:32px;opacity:0;padding:4px 8px;pointer-events:none;position:absolute;text-align:center;user-select:none;line-height:1.4;@media not ( prefers-reduced-motion ){transition:opacity 120ms ease;}", tooltipShow, ";", tooltipPosition, ";", (0,rtl/* rtl */.h)({
  transform: 'translateX(-50%)'
}, {
  transform: 'translateX(50%)'
}), ";" + ( true ? "" : 0));

// @todo Refactor RangeControl with latest HStack configuration
// @see: packages/components/src/h-stack
const InputNumber = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)(number_control/* default */.A,  true ? {
  target: "e1epgpqk1"
} : 0)("display:inline-block;font-size:13px;margin-top:0;input[type='number']&{", rangeHeight, ";}", (0,rtl/* rtl */.h)({
  marginLeft: `${(0,space/* space */.x)(4)} !important`
}), ";" + ( true ? "" : 0));
const ActionRightWrapper = /*#__PURE__*/(0,emotion_styled_base_browser_esm/* default */.A)("span",  true ? {
  target: "e1epgpqk0"
} : 0)("display:block;margin-top:0;button,button.is-small{margin-left:0;", rangeHeight, ";}", (0,rtl/* rtl */.h)({
  marginLeft: 8
}), ";" + ( true ? "" : 0));
//# sourceMappingURL=range-control-styles.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/range-control/input-range.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


function input_range_InputRange(props, ref) {
  const {
    describedBy,
    label,
    value,
    ...otherProps
  } = props;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(InputRange, {
    ...otherProps,
    "aria-describedby": describedBy,
    "aria-label": label,
    "aria-hidden": false,
    ref: ref,
    tabIndex: 0,
    type: "range",
    value: value
  });
}
const ForwardedComponent = (0,react.forwardRef)(input_range_InputRange);
/* harmony default export */ const input_range = (ForwardedComponent);
//# sourceMappingURL=input-range.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/range-control/mark.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




function RangeMark(props) {
  const {
    className,
    isFilled = false,
    label,
    style = {},
    ...otherProps
  } = props;
  const classes = (0,clsx/* default */.A)('components-range-control__mark', isFilled && 'is-filled', className);
  const labelClasses = (0,clsx/* default */.A)('components-range-control__mark-label', isFilled && 'is-filled');
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(Mark, {
      ...otherProps,
      "aria-hidden": "true",
      className: classes,
      isFilled: isFilled,
      style: style
    }), label && /*#__PURE__*/(0,jsx_runtime.jsx)(MarkLabel, {
      "aria-hidden": "true",
      className: labelClasses,
      isFilled: isFilled,
      style: style,
      children: label
    })]
  });
}
//# sourceMappingURL=mark.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/range-control/rail.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */






function RangeRail(props) {
  const {
    disabled = false,
    marks = false,
    min = 0,
    max = 100,
    step = 1,
    value = 0,
    ...restProps
  } = props;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(Rail, {
      disabled: disabled,
      ...restProps
    }), marks && /*#__PURE__*/(0,jsx_runtime.jsx)(Marks, {
      disabled: disabled,
      marks: marks,
      min: min,
      max: max,
      step: step,
      value: value
    })]
  });
}
function Marks(props) {
  const {
    disabled = false,
    marks = false,
    min = 0,
    max = 100,
    step: stepProp = 1,
    value = 0
  } = props;
  const step = stepProp === 'any' ? 1 : stepProp;
  const marksData = useMarks({
    marks,
    min,
    max,
    step,
    value
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(MarksWrapper, {
    "aria-hidden": "true",
    className: "components-range-control__marks",
    children: marksData.map(mark => /*#__PURE__*/(0,react.createElement)(RangeMark, {
      ...mark,
      key: mark.key,
      "aria-hidden": "true",
      disabled: disabled
    }))
  });
}
function useMarks({
  marks,
  min = 0,
  max = 100,
  step = 1,
  value = 0
}) {
  if (!marks) {
    return [];
  }
  const range = max - min;
  if (!Array.isArray(marks)) {
    marks = [];
    const count = 1 + Math.round(range / step);
    while (count > marks.push({
      value: step * marks.length + min
    })) {}
  }
  const placedMarks = [];
  marks.forEach((mark, index) => {
    if (mark.value < min || mark.value > max) {
      return;
    }
    const key = `mark-${index}`;
    const isFilled = mark.value <= value;
    const offset = `${(mark.value - min) / range * 100}%`;
    const offsetStyle = {
      [(0,build_module/* isRTL */.V8)() ? 'right' : 'left']: offset
    };
    placedMarks.push({
      ...mark,
      isFilled,
      key,
      style: offsetStyle
    });
  });
  return placedMarks;
}
//# sourceMappingURL=rail.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/range-control/tooltip.js
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


function SimpleTooltip(props) {
  const {
    className,
    inputRef,
    tooltipPosition,
    show = false,
    style = {},
    value = 0,
    renderTooltipContent = v => v,
    zIndex = 100,
    ...restProps
  } = props;
  const position = useTooltipPosition({
    inputRef,
    tooltipPosition
  });
  const classes = (0,clsx/* default */.A)('components-simple-tooltip', className);
  const styles = {
    ...style,
    zIndex
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Tooltip, {
    ...restProps,
    "aria-hidden": show,
    className: classes,
    position: position,
    show: show,
    role: "tooltip",
    style: styles,
    children: renderTooltipContent(value)
  });
}
function useTooltipPosition({
  inputRef,
  tooltipPosition
}) {
  const [position, setPosition] = (0,react.useState)();
  const setTooltipPosition = (0,react.useCallback)(() => {
    if (inputRef && inputRef.current) {
      setPosition(tooltipPosition);
    }
  }, [tooltipPosition, inputRef]);
  (0,react.useEffect)(() => {
    setTooltipPosition();
  }, [setTooltipPosition]);
  (0,react.useEffect)(() => {
    window.addEventListener('resize', setTooltipPosition);
    return () => {
      window.removeEventListener('resize', setTooltipPosition);
    };
  });
  return position;
}
//# sourceMappingURL=tooltip.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@28.8.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0_urapc3yhl5ji6mdpihaulkvcdq/node_modules/@wordpress/components/build-module/range-control/index.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */













const noop = () => {};

/**
 * Computes the value that `RangeControl` should reset to when pressing
 * the reset button.
 */
function computeResetValue({
  resetFallbackValue,
  initialPosition
}) {
  if (resetFallbackValue !== undefined) {
    return !Number.isNaN(resetFallbackValue) ? resetFallbackValue : null;
  }
  if (initialPosition !== undefined) {
    return !Number.isNaN(initialPosition) ? initialPosition : null;
  }
  return null;
}
function UnforwardedRangeControl(props, forwardedRef) {
  const {
    __nextHasNoMarginBottom = false,
    afterIcon,
    allowReset = false,
    beforeIcon,
    className,
    color: colorProp = colors_values/* COLORS */.l.theme.accent,
    currentInput,
    disabled = false,
    help,
    hideLabelFromVision = false,
    initialPosition,
    isShiftStepEnabled = true,
    label,
    marks = false,
    max = 100,
    min = 0,
    onBlur = noop,
    onChange = noop,
    onFocus = noop,
    onMouseLeave = noop,
    onMouseMove = noop,
    railColor,
    renderTooltipContent = v => v,
    resetFallbackValue,
    __next40pxDefaultSize = false,
    shiftStep = 10,
    showTooltip: showTooltipProp,
    step = 1,
    trackColor,
    value: valueProp,
    withInputField = true,
    ...otherProps
  } = props;
  const [value, setValue] = useControlledRangeValue({
    min,
    max,
    value: valueProp !== null && valueProp !== void 0 ? valueProp : null,
    initial: initialPosition
  });
  const isResetPendent = (0,react.useRef)(false);
  let hasTooltip = showTooltipProp;
  let hasInputField = withInputField;
  if (step === 'any') {
    // The tooltip and number input field are hidden when the step is "any"
    // because the decimals get too lengthy to fit well.
    hasTooltip = false;
    hasInputField = false;
  }
  const [showTooltip, setShowTooltip] = (0,react.useState)(hasTooltip);
  const [isFocused, setIsFocused] = (0,react.useState)(false);
  const inputRef = (0,react.useRef)();
  const isCurrentlyFocused = inputRef.current?.matches(':focus');
  const isThumbFocused = !disabled && isFocused;
  const isValueReset = value === null;
  const currentValue = value !== undefined ? value : currentInput;
  const inputSliderValue = isValueReset ? '' : currentValue;
  const rangeFillValue = isValueReset ? (max - min) / 2 + min : value;
  const fillValue = isValueReset ? 50 : (value - min) / (max - min) * 100;
  const fillValueOffset = `${(0,math/* clamp */.qE)(fillValue, 0, 100)}%`;
  const classes = (0,clsx/* default */.A)('components-range-control', className);
  const wrapperClasses = (0,clsx/* default */.A)('components-range-control__wrapper', !!marks && 'is-marked');
  const id = (0,use_instance_id/* default */.A)(UnforwardedRangeControl, 'inspector-range-control');
  const describedBy = !!help ? `${id}__help` : undefined;
  const enableTooltip = hasTooltip !== false && Number.isFinite(value);
  const handleOnRangeChange = event => {
    const nextValue = parseFloat(event.target.value);
    setValue(nextValue);
    onChange(nextValue);
  };
  const handleOnChange = next => {
    // @ts-expect-error TODO: Investigate if it's problematic for setValue() to
    // potentially receive a NaN when next is undefined.
    let nextValue = parseFloat(next);
    setValue(nextValue);

    /*
     * Calls onChange only when nextValue is numeric
     * otherwise may queue a reset for the blur event.
     */
    if (!isNaN(nextValue)) {
      if (nextValue < min || nextValue > max) {
        nextValue = floatClamp(nextValue, min, max);
      }
      onChange(nextValue);
      isResetPendent.current = false;
    } else if (allowReset) {
      isResetPendent.current = true;
    }
  };
  const handleOnInputNumberBlur = () => {
    if (isResetPendent.current) {
      handleOnReset();
      isResetPendent.current = false;
    }
  };
  const handleOnReset = () => {
    // Reset to `resetFallbackValue` if defined, otherwise set internal value
    // to `null` — which, if propagated to the `value` prop, will cause
    // the value to be reset to the `initialPosition` prop if defined.
    const resetValue = Number.isNaN(resetFallbackValue) ? null : resetFallbackValue !== null && resetFallbackValue !== void 0 ? resetFallbackValue : null;
    setValue(resetValue);

    /**
     * Previously, this callback would always receive undefined as
     * an argument. This behavior is unexpected, specifically
     * when resetFallbackValue is defined.
     *
     * The value of undefined is not ideal. Passing it through
     * to internal <input /> elements would change it from a
     * controlled component to an uncontrolled component.
     *
     * For now, to minimize unexpected regressions, we're going to
     * preserve the undefined callback argument, except when a
     * resetFallbackValue is defined.
     */
    onChange(resetValue !== null && resetValue !== void 0 ? resetValue : undefined);
  };
  const handleShowTooltip = () => setShowTooltip(true);
  const handleHideTooltip = () => setShowTooltip(false);
  const handleOnBlur = event => {
    onBlur(event);
    setIsFocused(false);
    handleHideTooltip();
  };
  const handleOnFocus = event => {
    onFocus(event);
    setIsFocused(true);
    handleShowTooltip();
  };
  const offsetStyle = {
    [(0,build_module/* isRTL */.V8)() ? 'right' : 'left']: fillValueOffset
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    __nextHasNoMarginBottom: __nextHasNoMarginBottom,
    __associatedWPComponentName: "RangeControl",
    className: classes,
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: `${id}`,
    help: help,
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)(Root, {
      className: "components-range-control__root",
      __next40pxDefaultSize: __next40pxDefaultSize,
      children: [beforeIcon && /*#__PURE__*/(0,jsx_runtime.jsx)(BeforeIconWrapper, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
          icon: beforeIcon
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(range_control_styles_Wrapper, {
        __nextHasNoMarginBottom: __nextHasNoMarginBottom,
        className: wrapperClasses,
        color: colorProp,
        marks: !!marks,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(input_range, {
          ...otherProps,
          className: "components-range-control__slider",
          describedBy: describedBy,
          disabled: disabled,
          id: `${id}`,
          label: label,
          max: max,
          min: min,
          onBlur: handleOnBlur,
          onChange: handleOnRangeChange,
          onFocus: handleOnFocus,
          onMouseMove: onMouseMove,
          onMouseLeave: onMouseLeave,
          ref: (0,use_merge_refs/* default */.A)([inputRef, forwardedRef]),
          step: step,
          value: inputSliderValue !== null && inputSliderValue !== void 0 ? inputSliderValue : undefined
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(RangeRail, {
          "aria-hidden": true,
          disabled: disabled,
          marks: marks,
          max: max,
          min: min,
          railColor: railColor,
          step: step,
          value: rangeFillValue
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(Track, {
          "aria-hidden": true,
          className: "components-range-control__track",
          disabled: disabled,
          style: {
            width: fillValueOffset
          },
          trackColor: trackColor
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(ThumbWrapper, {
          className: "components-range-control__thumb-wrapper",
          style: offsetStyle,
          disabled: disabled,
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(Thumb, {
            "aria-hidden": true,
            isFocused: isThumbFocused,
            disabled: disabled
          })
        }), enableTooltip && /*#__PURE__*/(0,jsx_runtime.jsx)(SimpleTooltip, {
          className: "components-range-control__tooltip",
          inputRef: inputRef,
          tooltipPosition: "bottom",
          renderTooltipContent: renderTooltipContent,
          show: isCurrentlyFocused || showTooltip,
          style: offsetStyle,
          value: value
        })]
      }), afterIcon && /*#__PURE__*/(0,jsx_runtime.jsx)(AfterIconWrapper, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
          icon: afterIcon
        })
      }), hasInputField && /*#__PURE__*/(0,jsx_runtime.jsx)(InputNumber, {
        "aria-label": label,
        className: "components-range-control__number",
        disabled: disabled,
        inputMode: "decimal",
        isShiftStepEnabled: isShiftStepEnabled,
        max: max,
        min: min,
        onBlur: handleOnInputNumberBlur,
        onChange: handleOnChange,
        shiftStep: shiftStep,
        size: __next40pxDefaultSize ? '__unstable-large' : 'default',
        __unstableInputWidth: __next40pxDefaultSize ? (0,space/* space */.x)(20) : (0,space/* space */.x)(16),
        step: step
        // @ts-expect-error TODO: Investigate if the `null` value is necessary
        ,
        value: inputSliderValue
      }), allowReset && /*#__PURE__*/(0,jsx_runtime.jsx)(ActionRightWrapper, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          className: "components-range-control__reset"
          // If the RangeControl itself is disabled, the reset button shouldn't be in the tab sequence.
          ,
          accessibleWhenDisabled: !disabled
          // The reset button should be disabled if RangeControl itself is disabled,
          // or if the current `value` is equal to the value that would be currently
          // assigned when clicking the button.
          ,
          disabled: disabled || value === computeResetValue({
            resetFallbackValue,
            initialPosition
          }),
          variant: "secondary",
          size: "small",
          onClick: handleOnReset,
          children: (0,build_module.__)('Reset')
        })
      })]
    })
  });
}

/**
 * RangeControls are used to make selections from a range of incremental values.
 *
 * ```jsx
 * import { RangeControl } from '@wordpress/components';
 * import { useState } from '@wordpress/element';
 *
 * const MyRangeControl = () => {
 *   const [ isChecked, setChecked ] = useState( true );
 *   return (
 *     <RangeControl
 *       __nextHasNoMarginBottom
 *       help="Please select how transparent you would like this."
 *       initialPosition={50}
 *       label="Opacity"
 *       max={100}
 *       min={0}
 *       onChange={() => {}}
 *     />
 *   );
 * };
 * ```
 */
const RangeControl = (0,react.forwardRef)(UnforwardedRangeControl);
/* harmony default export */ const range_control = (RangeControl);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js
var use_viewport_match = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.8.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/dataviews-layouts/grid/density-picker.js
/**
 * WordPress dependencies
 */





const viewportBreaks = {
  xhuge: {
    min: 3,
    max: 6,
    default: 5
  },
  huge: {
    min: 2,
    max: 4,
    default: 4
  },
  xlarge: {
    min: 2,
    max: 3,
    default: 3
  },
  large: {
    min: 1,
    max: 2,
    default: 2
  },
  mobile: {
    min: 1,
    max: 2,
    default: 2
  }
};
function useViewPortBreakpoint() {
  const isXHuge = (0,use_viewport_match/* default */.A)('xhuge', '>=');
  const isHuge = (0,use_viewport_match/* default */.A)('huge', '>=');
  const isXlarge = (0,use_viewport_match/* default */.A)('xlarge', '>=');
  const isLarge = (0,use_viewport_match/* default */.A)('large', '>=');
  const isMobile = (0,use_viewport_match/* default */.A)('mobile', '>=');
  if (isXHuge) {
    return 'xhuge';
  }
  if (isHuge) {
    return 'huge';
  }
  if (isXlarge) {
    return 'xlarge';
  }
  if (isLarge) {
    return 'large';
  }
  if (isMobile) {
    return 'mobile';
  }
  return null;
}
function DensityPicker({
  density,
  setDensity
}) {
  const viewport = useViewPortBreakpoint();
  (0,react.useEffect)(() => {
    setDensity(_density => {
      if (!viewport || !_density) {
        return 0;
      }
      const breakValues = viewportBreaks[viewport];
      if (_density < breakValues.min) {
        return breakValues.min;
      }
      if (_density > breakValues.max) {
        return breakValues.max;
      }
      return _density;
    });
  }, [setDensity, viewport]);
  const breakValues = viewportBreaks[viewport || 'mobile'];
  const densityToUse = density || breakValues.default;
  const marks = (0,react.useMemo)(() => Array.from({
    length: breakValues.max - breakValues.min + 1
  }, (_, i) => {
    return {
      value: breakValues.min + i
    };
  }), [breakValues]);
  if (!viewport) {
    return null;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(range_control, {
    __nextHasNoMarginBottom: true,
    __next40pxDefaultSize: true,
    showTooltip: false,
    label: (0,build_module.__)('Preview size'),
    value: breakValues.max + breakValues.min - densityToUse,
    marks: marks,
    min: breakValues.min,
    max: breakValues.max,
    withInputField: false,
    onChange: (value = 0) => {
      setDensity(breakValues.max + breakValues.min - value);
    },
    step: 1
  });
}
//# sourceMappingURL=density-picker.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews-view-config/index.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */








const {
  DropdownMenuV2: dataviews_view_config_DropdownMenuV2
} = build_module_lock_unlock_unlock(privateApis);
function ViewTypeMenu({
  defaultLayouts = {
    list: {},
    grid: {},
    table: {}
  }
}) {
  const {
    view,
    onChangeView
  } = (0,react.useContext)(dataviews_context);
  const availableLayouts = Object.keys(defaultLayouts);
  if (availableLayouts.length <= 1) {
    return null;
  }
  const activeView = VIEW_LAYOUTS.find(v => view.type === v.type);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_view_config_DropdownMenuV2, {
    trigger: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      size: "compact",
      icon: activeView?.icon,
      label: (0,build_module.__)('Layout')
    }),
    children: availableLayouts.map(layout => {
      const config = VIEW_LAYOUTS.find(v => v.type === layout);
      if (!config) {
        return null;
      }
      return /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_view_config_DropdownMenuV2.RadioItem, {
        value: layout,
        name: "view-actions-available-view",
        checked: layout === view.type,
        hideOnClick: true,
        onChange: e => {
          switch (e.target.value) {
            case 'list':
            case 'grid':
            case 'table':
              return onChangeView({
                ...view,
                type: e.target.value,
                ...defaultLayouts[e.target.value]
              });
          }
          globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)('Invalid dataview') : void 0;
        },
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_view_config_DropdownMenuV2.ItemLabel, {
          children: config.label
        })
      }, layout);
    })
  });
}
function SortFieldControl() {
  const {
    view,
    fields,
    onChangeView
  } = (0,react.useContext)(dataviews_context);
  const orderOptions = (0,react.useMemo)(() => {
    const sortableFields = fields.filter(field => field.enableSorting !== false);
    return sortableFields.map(field => {
      return {
        label: field.label,
        value: field.id
      };
    });
  }, [fields]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
    __nextHasNoMarginBottom: true,
    __next40pxDefaultSize: true,
    label: (0,build_module.__)('Sort by'),
    value: view.sort?.field,
    options: orderOptions,
    onChange: value => {
      onChangeView({
        ...view,
        sort: {
          direction: view?.sort?.direction || 'desc',
          field: value
        }
      });
    }
  });
}
function SortDirectionControl() {
  const {
    view,
    fields,
    onChangeView
  } = (0,react.useContext)(dataviews_context);
  const sortableFields = fields.filter(field => field.enableSorting !== false);
  if (sortableFields.length === 0) {
    return null;
  }
  let value = view.sort?.direction;
  if (!value && view.sort?.field) {
    value = 'desc';
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(toggle_group_control_component/* default */.A, {
    className: "dataviews-view-config__sort-direction",
    __nextHasNoMarginBottom: true,
    __next40pxDefaultSize: true,
    isBlock: true,
    label: (0,build_module.__)('Order'),
    value: value,
    onChange: newDirection => {
      if (newDirection === 'asc' || newDirection === 'desc') {
        onChangeView({
          ...view,
          sort: {
            direction: newDirection,
            field: view.sort?.field ||
            // If there is no field assigned as the sorting field assign the first sortable field.
            fields.find(field => field.enableSorting !== false)?.id || ''
          }
        });
        return;
      }
      globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)('Invalid direction') : void 0;
    },
    children: SORTING_DIRECTIONS.map(direction => {
      return /*#__PURE__*/(0,jsx_runtime.jsx)(toggle_group_control_option_icon_component, {
        value: direction,
        icon: sortIcons[direction],
        label: sortLabels[direction]
      }, direction);
    })
  });
}
const PAGE_SIZE_VALUES = [10, 20, 50, 100];
function ItemsPerPageControl() {
  const {
    view,
    onChangeView
  } = (0,react.useContext)(dataviews_context);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(toggle_group_control_component/* default */.A, {
    __nextHasNoMarginBottom: true,
    __next40pxDefaultSize: true,
    isBlock: true,
    label: (0,build_module.__)('Items per page'),
    value: view.perPage || 10,
    disabled: !view?.sort?.field,
    onChange: newItemsPerPage => {
      const newItemsPerPageNumber = typeof newItemsPerPage === 'number' || newItemsPerPage === undefined ? newItemsPerPage : parseInt(newItemsPerPage, 10);
      onChangeView({
        ...view,
        perPage: newItemsPerPageNumber,
        page: 1
      });
    },
    children: PAGE_SIZE_VALUES.map(value => {
      return /*#__PURE__*/(0,jsx_runtime.jsx)(toggle_group_control_option_component/* default */.A, {
        value: value,
        label: value.toString()
      }, value);
    })
  });
}
function FieldItem({
  field: {
    id,
    label,
    index,
    isVisible,
    isHidable
  },
  fields,
  view,
  onChangeView
}) {
  const visibleFieldIds = getVisibleFieldIds(view, fields);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(item_component, {
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      expanded: true,
      className: `dataviews-field-control__field dataviews-field-control__field-${id}`,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        children: label
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
        justify: "flex-end",
        expanded: false,
        className: "dataviews-field-control__actions",
        children: [view.type === LAYOUT_TABLE && isVisible && /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            disabled: index < 1,
            accessibleWhenDisabled: true,
            size: "compact",
            onClick: () => {
              var _visibleFieldIds$slic;
              onChangeView({
                ...view,
                fields: [...((_visibleFieldIds$slic = visibleFieldIds.slice(0, index - 1)) !== null && _visibleFieldIds$slic !== void 0 ? _visibleFieldIds$slic : []), id, visibleFieldIds[index - 1], ...visibleFieldIds.slice(index + 1)]
              });
            },
            icon: chevron_up,
            label: (0,build_module/* sprintf */.nv)( /* translators: %s: field label */
            (0,build_module.__)('Move %s up'), label)
          }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            disabled: index >= visibleFieldIds.length - 1,
            accessibleWhenDisabled: true,
            size: "compact",
            onClick: () => {
              var _visibleFieldIds$slic2;
              onChangeView({
                ...view,
                fields: [...((_visibleFieldIds$slic2 = visibleFieldIds.slice(0, index)) !== null && _visibleFieldIds$slic2 !== void 0 ? _visibleFieldIds$slic2 : []), visibleFieldIds[index + 1], id, ...visibleFieldIds.slice(index + 2)]
              });
            },
            icon: chevron_down/* default */.A,
            label: (0,build_module/* sprintf */.nv)( /* translators: %s: field label */
            (0,build_module.__)('Move %s down'), label)
          }), ' ']
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          className: "dataviews-field-control__field-visibility-button",
          disabled: !isHidable,
          accessibleWhenDisabled: true,
          size: "compact",
          onClick: () => {
            onChangeView({
              ...view,
              fields: isVisible ? visibleFieldIds.filter(fieldId => fieldId !== id) : [...visibleFieldIds, id]
            });
            // Focus the visibility button to avoid focus loss.
            // Our code is safe against the component being unmounted, so we don't need to worry about cleaning the timeout.
            // eslint-disable-next-line @wordpress/react-no-unsafe-timeout
            setTimeout(() => {
              const element = document.querySelector(`.dataviews-field-control__field-${id} .dataviews-field-control__field-visibility-button`);
              if (element instanceof HTMLElement) {
                element.focus();
              }
            }, 50);
          },
          icon: isVisible ? library_seen : library_unseen,
          label: isVisible ? (0,build_module/* sprintf */.nv)( /* translators: %s: field label */
          (0,build_module.__)('Hide %s'), label) : (0,build_module/* sprintf */.nv)( /* translators: %s: field label */
          (0,build_module.__)('Show %s'), label)
        })]
      })]
    })
  }, id);
}
function FieldControl() {
  const {
    view,
    fields,
    onChangeView
  } = (0,react.useContext)(dataviews_context);
  const visibleFieldIds = (0,react.useMemo)(() => getVisibleFieldIds(view, fields), [view, fields]);
  const hiddenFieldIds = (0,react.useMemo)(() => getHiddenFieldIds(view, fields), [view, fields]);
  const notHidableFieldIds = (0,react.useMemo)(() => getNotHidableFieldIds(view), [view]);
  const visibleFields = fields.filter(({
    id
  }) => visibleFieldIds.includes(id)).map(({
    id,
    label,
    enableHiding
  }) => {
    return {
      id,
      label,
      index: visibleFieldIds.indexOf(id),
      isVisible: true,
      isHidable: notHidableFieldIds.includes(id) ? false : enableHiding
    };
  });
  if (view.type === LAYOUT_TABLE && view.layout?.combinedFields) {
    view.layout.combinedFields.forEach(({
      id,
      label
    }) => {
      visibleFields.push({
        id,
        label,
        index: visibleFieldIds.indexOf(id),
        isVisible: true,
        isHidable: notHidableFieldIds.includes(id)
      });
    });
  }
  visibleFields.sort((a, b) => a.index - b.index);
  const hiddenFields = fields.filter(({
    id
  }) => hiddenFieldIds.includes(id)).map(({
    id,
    label,
    enableHiding
  }, index) => {
    return {
      id,
      label,
      index,
      isVisible: false,
      isHidable: enableHiding
    };
  });
  if (!visibleFields?.length && !hiddenFields?.length) {
    return null;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(v_stack_component/* default */.A, {
    spacing: 6,
    className: "dataviews-field-control",
    children: [!!visibleFields?.length && /*#__PURE__*/(0,jsx_runtime.jsx)(item_group_component, {
      isBordered: true,
      isSeparated: true,
      children: visibleFields.map(field => /*#__PURE__*/(0,jsx_runtime.jsx)(FieldItem, {
        field: field,
        fields: fields,
        view: view,
        onChangeView: onChangeView
      }, field.id))
    }), !!hiddenFields?.length && /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)(v_stack_component/* default */.A, {
        spacing: 4,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default.VisualLabel */.Ay.VisualLabel, {
          style: {
            margin: 0
          },
          children: (0,build_module.__)('Hidden')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(item_group_component, {
          isBordered: true,
          isSeparated: true,
          children: hiddenFields.map(field => /*#__PURE__*/(0,jsx_runtime.jsx)(FieldItem, {
            field: field,
            fields: fields,
            view: view,
            onChangeView: onChangeView
          }, field.id))
        })]
      })
    })]
  });
}
function SettingsSection({
  title,
  description,
  children
}) {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(grid_component, {
    columns: 12,
    className: "dataviews-settings-section",
    gap: 4,
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "dataviews-settings-section__sidebar",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(heading_component/* default */.A, {
        level: 2,
        className: "dataviews-settings-section__title",
        children: title
      }), description && /*#__PURE__*/(0,jsx_runtime.jsx)(text_component/* default */.A, {
        variant: "muted",
        className: "dataviews-settings-section__description",
        children: description
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(grid_component, {
      columns: 8,
      gap: 4,
      className: "dataviews-settings-section__content",
      children: children
    })]
  });
}
function DataviewsViewConfigContent({
  density,
  setDensity
}) {
  const {
    view
  } = (0,react.useContext)(dataviews_context);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(v_stack_component/* default */.A, {
    className: "dataviews-view-config",
    spacing: 6,
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(SettingsSection, {
      title: (0,build_module.__)('Appearance'),
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
        expanded: true,
        className: "is-divided-in-two",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(SortFieldControl, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(SortDirectionControl, {})]
      }), view.type === LAYOUT_GRID && /*#__PURE__*/(0,jsx_runtime.jsx)(DensityPicker, {
        density: density,
        setDensity: setDensity
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(ItemsPerPageControl, {})]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(SettingsSection, {
      title: (0,build_module.__)('Properties'),
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(FieldControl, {})
    })]
  });
}
function _DataViewsViewConfig({
  density,
  setDensity,
  defaultLayouts = {
    list: {},
    grid: {},
    table: {}
  }
}) {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ViewTypeMenu, {
      defaultLayouts: defaultLayouts
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown/* default */.A, {
      popoverProps: {
        placement: 'bottom-end',
        offset: 9
      },
      contentClassName: "dataviews-view-config",
      renderToggle: ({
        onToggle
      }) => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          size: "compact",
          icon: library_cog,
          label: (0,build_module._x)('View options', 'View is used as a noun'),
          onClick: onToggle
        });
      },
      renderContent: () => /*#__PURE__*/(0,jsx_runtime.jsx)(DataviewsViewConfigContent, {
        density: density,
        setDensity: setDensity
      })
    })]
  });
}
const DataViewsViewConfig = (0,react.memo)(_DataViewsViewConfig);
/* harmony default export */ const dataviews_view_config = (DataViewsViewConfig);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/normalize-fields.js + 58 modules
var normalize_fields = __webpack_require__("../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/normalize-fields.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews/index.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */









const defaultGetItemId = item => item.id;
function DataViews({
  view,
  onChangeView,
  fields,
  search = true,
  searchLabel = undefined,
  actions = [],
  data,
  getItemId = defaultGetItemId,
  isLoading = false,
  paginationInfo,
  defaultLayouts,
  selection: selectionProperty,
  onChangeSelection,
  header
}) {
  const [selectionState, setSelectionState] = (0,react.useState)([]);
  const [density, setDensity] = (0,react.useState)(0);
  const isUncontrolled = selectionProperty === undefined || onChangeSelection === undefined;
  const selection = isUncontrolled ? selectionState : selectionProperty;
  const [openedFilter, setOpenedFilter] = (0,react.useState)(null);
  function setSelectionWithChange(value) {
    const newValue = typeof value === 'function' ? value(selection) : value;
    if (isUncontrolled) {
      setSelectionState(newValue);
    }
    if (onChangeSelection) {
      onChangeSelection(newValue);
    }
  }
  const _fields = (0,react.useMemo)(() => (0,normalize_fields/* normalizeFields */.t)(fields), [fields]);
  const _selection = (0,react.useMemo)(() => {
    return selection.filter(id => data.some(item => getItemId(item) === id));
  }, [selection, data, getItemId]);
  const filters = useFilters(_fields, view);
  const [isShowingFilter, setIsShowingFilter] = (0,react.useState)(() => (filters || []).some(filter => filter.isPrimary));
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_context.Provider, {
    value: {
      view,
      onChangeView,
      fields: _fields,
      actions,
      data,
      isLoading,
      paginationInfo,
      selection: _selection,
      onChangeSelection: setSelectionWithChange,
      openedFilter,
      setOpenedFilter,
      getItemId,
      density
    },
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "dataviews-wrapper",
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
        alignment: "top",
        justify: "space-between",
        className: "dataviews__view-actions",
        spacing: 1,
        children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
          justify: "start",
          expanded: false,
          className: "dataviews__search",
          children: [search && /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_search, {
            label: searchLabel
          }), /*#__PURE__*/(0,jsx_runtime.jsx)(FilterVisibilityToggle, {
            filters: filters,
            view: view,
            onChangeView: onChangeView,
            setOpenedFilter: setOpenedFilter,
            setIsShowingFilter: setIsShowingFilter,
            isShowingFilter: isShowingFilter
          })]
        }), /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
          spacing: 1,
          expanded: false,
          style: {
            flexShrink: 0
          },
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_view_config, {
            defaultLayouts: defaultLayouts,
            density: density,
            setDensity: setDensity
          }), header]
        })]
      }), isShowingFilter && /*#__PURE__*/(0,jsx_runtime.jsx)(dataviews_filters, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(DataViewsLayout, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(DataViewsFooter, {})]
    })
  });
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/a11y.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var o=function(o){var t=o/255;return t<.04045?t/12.92:Math.pow((t+.055)/1.055,2.4)},t=function(t){return.2126*o(t.r)+.7152*o(t.g)+.0722*o(t.b)};/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(o){o.prototype.luminance=function(){return o=t(this.rgba),void 0===(r=2)&&(r=0),void 0===n&&(n=Math.pow(10,r)),Math.round(n*o)/n+0;var o,r,n},o.prototype.contrast=function(r){void 0===r&&(r="#FFF");var n,a,i,e,v,u,d,c=r instanceof o?r:new o(r);return e=this.rgba,v=c.toRgb(),u=t(e),d=t(v),n=u>d?(u+.05)/(d+.05):(d+.05)/(u+.05),void 0===(a=2)&&(a=0),void 0===i&&(i=Math.pow(10,a)),Math.floor(i*n)/i+0},o.prototype.isReadable=function(o,t){return void 0===o&&(o="#FFF"),void 0===t&&(t={}),this.contrast(o)>=(e=void 0===(i=(r=t).size)?"normal":i,"AAA"===(a=void 0===(n=r.level)?"AA":n)&&"normal"===e?7:"AA"===a&&"large"===e?3:4.5);var r,n,a,i,e}}


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-bind-context.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var IndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/indexed-object.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var lengthOfArrayLike = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/length-of-array-like.js");
var arraySpeciesCreate = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-create.js");

var push = uncurryThis([].push);

// `Array.prototype.{ forEach, map, filter, some, every, find, findIndex, filterReject }` methods implementation
var createMethod = function (TYPE) {
  var IS_MAP = TYPE === 1;
  var IS_FILTER = TYPE === 2;
  var IS_SOME = TYPE === 3;
  var IS_EVERY = TYPE === 4;
  var IS_FIND_INDEX = TYPE === 6;
  var IS_FILTER_REJECT = TYPE === 7;
  var NO_HOLES = TYPE === 5 || IS_FIND_INDEX;
  return function ($this, callbackfn, that, specificCreate) {
    var O = toObject($this);
    var self = IndexedObject(O);
    var length = lengthOfArrayLike(self);
    var boundFunction = bind(callbackfn, that);
    var index = 0;
    var create = specificCreate || arraySpeciesCreate;
    var target = IS_MAP ? create($this, length) : IS_FILTER || IS_FILTER_REJECT ? create($this, 0) : undefined;
    var value, result;
    for (;length > index; index++) if (NO_HOLES || index in self) {
      value = self[index];
      result = boundFunction(value, index, O);
      if (TYPE) {
        if (IS_MAP) target[index] = result; // map
        else if (result) switch (TYPE) {
          case 3: return true;              // some
          case 5: return value;             // find
          case 6: return index;             // findIndex
          case 2: push(target, value);      // filter
        } else switch (TYPE) {
          case 4: return false;             // every
          case 7: push(target, value);      // filterReject
        }
      }
    }
    return IS_FIND_INDEX ? -1 : IS_SOME || IS_EVERY ? IS_EVERY : target;
  };
};

module.exports = {
  // `Array.prototype.forEach` method
  // https://tc39.es/ecma262/#sec-array.prototype.foreach
  forEach: createMethod(0),
  // `Array.prototype.map` method
  // https://tc39.es/ecma262/#sec-array.prototype.map
  map: createMethod(1),
  // `Array.prototype.filter` method
  // https://tc39.es/ecma262/#sec-array.prototype.filter
  filter: createMethod(2),
  // `Array.prototype.some` method
  // https://tc39.es/ecma262/#sec-array.prototype.some
  some: createMethod(3),
  // `Array.prototype.every` method
  // https://tc39.es/ecma262/#sec-array.prototype.every
  every: createMethod(4),
  // `Array.prototype.find` method
  // https://tc39.es/ecma262/#sec-array.prototype.find
  find: createMethod(5),
  // `Array.prototype.findIndex` method
  // https://tc39.es/ecma262/#sec-array.prototype.findIndex
  findIndex: createMethod(6),
  // `Array.prototype.filterReject` method
  // https://github.com/tc39/proposal-array-filtering
  filterReject: createMethod(7)
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-constructor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var isArray = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-array.js");
var isConstructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-constructor.js");
var isObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-object.js");
var wellKnownSymbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/well-known-symbol.js");

var SPECIES = wellKnownSymbol('species');
var $Array = Array;

// a part of `ArraySpeciesCreate` abstract operation
// https://tc39.es/ecma262/#sec-arrayspeciescreate
module.exports = function (originalArray) {
  var C;
  if (isArray(originalArray)) {
    C = originalArray.constructor;
    // cross-realm fallback
    if (isConstructor(C) && (C === $Array || isArray(C.prototype))) C = undefined;
    else if (isObject(C)) {
      C = C[SPECIES];
      if (C === null) C = undefined;
    }
  } return C === undefined ? $Array : C;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-create.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var arraySpeciesConstructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-constructor.js");

// `ArraySpeciesCreate` abstract operation
// https://tc39.es/ecma262/#sec-arrayspeciescreate
module.exports = function (originalArray, length) {
  return new (arraySpeciesConstructor(originalArray))(length === 0 ? 0 : length);
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-array.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var classof = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/classof-raw.js");

// `IsArray` abstract operation
// https://tc39.es/ecma262/#sec-isarray
// eslint-disable-next-line es/no-array-isarray -- safe
module.exports = Array.isArray || function isArray(argument) {
  return classof(argument) === 'Array';
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var FUNCTION_NAME_EXISTS = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-name.js").EXISTS);
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var defineBuiltInAccessor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/define-built-in-accessor.js");

var FunctionPrototype = Function.prototype;
var functionToString = uncurryThis(FunctionPrototype.toString);
var nameRE = /function\b(?:\s|\/\*[\S\s]*?\*\/|\/\/[^\n\r]*[\n\r]+)*([^\s(/]*)/;
var regExpExec = uncurryThis(nameRE.exec);
var NAME = 'name';

// Function instances `.name` property
// https://tc39.es/ecma262/#sec-function-instances-name
if (DESCRIPTORS && !FUNCTION_NAME_EXISTS) {
  defineBuiltInAccessor(FunctionPrototype, NAME, {
    configurable: true,
    get: function () {
      try {
        return regExpExec(nameRE, functionToString(this))[1];
      } catch (error) {
        return '';
      }
    }
  });
}


/***/ }),

/***/ "../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });


/** @typedef {(...args: any[]) => *[]} GetDependants */

/** @typedef {() => void} Clear */

/**
 * @typedef {{
 *   getDependants: GetDependants,
 *   clear: Clear
 * }} EnhancedSelector
 */

/**
 * Internal cache entry.
 *
 * @typedef CacheNode
 *
 * @property {?CacheNode|undefined} [prev] Previous node.
 * @property {?CacheNode|undefined} [next] Next node.
 * @property {*[]} args Function arguments for cache entry.
 * @property {*} val Function result.
 */

/**
 * @typedef Cache
 *
 * @property {Clear} clear Function to clear cache.
 * @property {boolean} [isUniqueByDependants] Whether dependants are valid in
 * considering cache uniqueness. A cache is unique if dependents are all arrays
 * or objects.
 * @property {CacheNode?} [head] Cache head.
 * @property {*[]} [lastDependants] Dependants from previous invocation.
 */

/**
 * Arbitrary value used as key for referencing cache object in WeakMap tree.
 *
 * @type {{}}
 */
var LEAF_KEY = {};

/**
 * Returns the first argument as the sole entry in an array.
 *
 * @template T
 *
 * @param {T} value Value to return.
 *
 * @return {[T]} Value returned as entry in array.
 */
function arrayOf(value) {
	return [value];
}

/**
 * Returns true if the value passed is object-like, or false otherwise. A value
 * is object-like if it can support property assignment, e.g. object or array.
 *
 * @param {*} value Value to test.
 *
 * @return {boolean} Whether value is object-like.
 */
function isObjectLike(value) {
	return !!value && 'object' === typeof value;
}

/**
 * Creates and returns a new cache object.
 *
 * @return {Cache} Cache object.
 */
function createCache() {
	/** @type {Cache} */
	var cache = {
		clear: function () {
			cache.head = null;
		},
	};

	return cache;
}

/**
 * Returns true if entries within the two arrays are strictly equal by
 * reference from a starting index.
 *
 * @param {*[]} a First array.
 * @param {*[]} b Second array.
 * @param {number} fromIndex Index from which to start comparison.
 *
 * @return {boolean} Whether arrays are shallowly equal.
 */
function isShallowEqual(a, b, fromIndex) {
	var i;

	if (a.length !== b.length) {
		return false;
	}

	for (i = fromIndex; i < a.length; i++) {
		if (a[i] !== b[i]) {
			return false;
		}
	}

	return true;
}

/**
 * Returns a memoized selector function. The getDependants function argument is
 * called before the memoized selector and is expected to return an immutable
 * reference or array of references on which the selector depends for computing
 * its own return value. The memoize cache is preserved only as long as those
 * dependant references remain the same. If getDependants returns a different
 * reference(s), the cache is cleared and the selector value regenerated.
 *
 * @template {(...args: *[]) => *} S
 *
 * @param {S} selector Selector function.
 * @param {GetDependants=} getDependants Dependant getter returning an array of
 * references used in cache bust consideration.
 */
/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(selector, getDependants) {
	/** @type {WeakMap<*,*>} */
	var rootCache;

	/** @type {GetDependants} */
	var normalizedGetDependants = getDependants ? getDependants : arrayOf;

	/**
	 * Returns the cache for a given dependants array. When possible, a WeakMap
	 * will be used to create a unique cache for each set of dependants. This
	 * is feasible due to the nature of WeakMap in allowing garbage collection
	 * to occur on entries where the key object is no longer referenced. Since
	 * WeakMap requires the key to be an object, this is only possible when the
	 * dependant is object-like. The root cache is created as a hierarchy where
	 * each top-level key is the first entry in a dependants set, the value a
	 * WeakMap where each key is the next dependant, and so on. This continues
	 * so long as the dependants are object-like. If no dependants are object-
	 * like, then the cache is shared across all invocations.
	 *
	 * @see isObjectLike
	 *
	 * @param {*[]} dependants Selector dependants.
	 *
	 * @return {Cache} Cache object.
	 */
	function getCache(dependants) {
		var caches = rootCache,
			isUniqueByDependants = true,
			i,
			dependant,
			map,
			cache;

		for (i = 0; i < dependants.length; i++) {
			dependant = dependants[i];

			// Can only compose WeakMap from object-like key.
			if (!isObjectLike(dependant)) {
				isUniqueByDependants = false;
				break;
			}

			// Does current segment of cache already have a WeakMap?
			if (caches.has(dependant)) {
				// Traverse into nested WeakMap.
				caches = caches.get(dependant);
			} else {
				// Create, set, and traverse into a new one.
				map = new WeakMap();
				caches.set(dependant, map);
				caches = map;
			}
		}

		// We use an arbitrary (but consistent) object as key for the last item
		// in the WeakMap to serve as our running cache.
		if (!caches.has(LEAF_KEY)) {
			cache = createCache();
			cache.isUniqueByDependants = isUniqueByDependants;
			caches.set(LEAF_KEY, cache);
		}

		return caches.get(LEAF_KEY);
	}

	/**
	 * Resets root memoization cache.
	 */
	function clear() {
		rootCache = new WeakMap();
	}

	/* eslint-disable jsdoc/check-param-names */
	/**
	 * The augmented selector call, considering first whether dependants have
	 * changed before passing it to underlying memoize function.
	 *
	 * @param {*}    source    Source object for derivation.
	 * @param {...*} extraArgs Additional arguments to pass to selector.
	 *
	 * @return {*} Selector result.
	 */
	/* eslint-enable jsdoc/check-param-names */
	function callSelector(/* source, ...extraArgs */) {
		var len = arguments.length,
			cache,
			node,
			i,
			args,
			dependants;

		// Create copy of arguments (avoid leaking deoptimization).
		args = new Array(len);
		for (i = 0; i < len; i++) {
			args[i] = arguments[i];
		}

		dependants = normalizedGetDependants.apply(null, args);
		cache = getCache(dependants);

		// If not guaranteed uniqueness by dependants (primitive type), shallow
		// compare against last dependants and, if references have changed,
		// destroy cache to recalculate result.
		if (!cache.isUniqueByDependants) {
			if (
				cache.lastDependants &&
				!isShallowEqual(dependants, cache.lastDependants, 0)
			) {
				cache.clear();
			}

			cache.lastDependants = dependants;
		}

		node = cache.head;
		while (node) {
			// Check whether node arguments match arguments
			if (!isShallowEqual(node.args, args, 1)) {
				node = node.next;
				continue;
			}

			// At this point we can assume we've found a match

			// Surface matched node to head if not already
			if (node !== cache.head) {
				// Adjust siblings to point to each other.
				/** @type {CacheNode} */ (node.prev).next = node.next;
				if (node.next) {
					node.next.prev = node.prev;
				}

				node.next = cache.head;
				node.prev = null;
				/** @type {CacheNode} */ (cache.head).prev = node;
				cache.head = node;
			}

			// Return immediately
			return node.val;
		}

		// No cached value found. Continue to insertion phase:

		node = /** @type {CacheNode} */ ({
			// Generate the result from original function
			val: selector.apply(null, args),
		});

		// Avoid including the source object in the cache.
		args[0] = null;
		node.args = args;

		// Don't need to check whether node is already head, since it would
		// have been returned above already if it was

		// Shift existing head down list
		if (cache.head) {
			cache.head.prev = node;
			node.next = cache.head;
		}

		cache.head = node;

		return node.val;
	}

	callSelector.getDependants = normalizedGetDependants;
	callSelector.clear = clear;
	clear();

	return /** @type {S & EnhancedSelector} */ (callSelector);
}


/***/ }),

/***/ "../../node_modules/.pnpm/remove-accents@0.5.0/node_modules/remove-accents/index.js":
/***/ ((module) => {

var characterMap = {
	"À": "A",
	"Á": "A",
	"Â": "A",
	"Ã": "A",
	"Ä": "A",
	"Å": "A",
	"Ấ": "A",
	"Ắ": "A",
	"Ẳ": "A",
	"Ẵ": "A",
	"Ặ": "A",
	"Æ": "AE",
	"Ầ": "A",
	"Ằ": "A",
	"Ȃ": "A",
	"Ả": "A",
	"Ạ": "A",
	"Ẩ": "A",
	"Ẫ": "A",
	"Ậ": "A",
	"Ç": "C",
	"Ḉ": "C",
	"È": "E",
	"É": "E",
	"Ê": "E",
	"Ë": "E",
	"Ế": "E",
	"Ḗ": "E",
	"Ề": "E",
	"Ḕ": "E",
	"Ḝ": "E",
	"Ȇ": "E",
	"Ẻ": "E",
	"Ẽ": "E",
	"Ẹ": "E",
	"Ể": "E",
	"Ễ": "E",
	"Ệ": "E",
	"Ì": "I",
	"Í": "I",
	"Î": "I",
	"Ï": "I",
	"Ḯ": "I",
	"Ȋ": "I",
	"Ỉ": "I",
	"Ị": "I",
	"Ð": "D",
	"Ñ": "N",
	"Ò": "O",
	"Ó": "O",
	"Ô": "O",
	"Õ": "O",
	"Ö": "O",
	"Ø": "O",
	"Ố": "O",
	"Ṍ": "O",
	"Ṓ": "O",
	"Ȏ": "O",
	"Ỏ": "O",
	"Ọ": "O",
	"Ổ": "O",
	"Ỗ": "O",
	"Ộ": "O",
	"Ờ": "O",
	"Ở": "O",
	"Ỡ": "O",
	"Ớ": "O",
	"Ợ": "O",
	"Ù": "U",
	"Ú": "U",
	"Û": "U",
	"Ü": "U",
	"Ủ": "U",
	"Ụ": "U",
	"Ử": "U",
	"Ữ": "U",
	"Ự": "U",
	"Ý": "Y",
	"à": "a",
	"á": "a",
	"â": "a",
	"ã": "a",
	"ä": "a",
	"å": "a",
	"ấ": "a",
	"ắ": "a",
	"ẳ": "a",
	"ẵ": "a",
	"ặ": "a",
	"æ": "ae",
	"ầ": "a",
	"ằ": "a",
	"ȃ": "a",
	"ả": "a",
	"ạ": "a",
	"ẩ": "a",
	"ẫ": "a",
	"ậ": "a",
	"ç": "c",
	"ḉ": "c",
	"è": "e",
	"é": "e",
	"ê": "e",
	"ë": "e",
	"ế": "e",
	"ḗ": "e",
	"ề": "e",
	"ḕ": "e",
	"ḝ": "e",
	"ȇ": "e",
	"ẻ": "e",
	"ẽ": "e",
	"ẹ": "e",
	"ể": "e",
	"ễ": "e",
	"ệ": "e",
	"ì": "i",
	"í": "i",
	"î": "i",
	"ï": "i",
	"ḯ": "i",
	"ȋ": "i",
	"ỉ": "i",
	"ị": "i",
	"ð": "d",
	"ñ": "n",
	"ò": "o",
	"ó": "o",
	"ô": "o",
	"õ": "o",
	"ö": "o",
	"ø": "o",
	"ố": "o",
	"ṍ": "o",
	"ṓ": "o",
	"ȏ": "o",
	"ỏ": "o",
	"ọ": "o",
	"ổ": "o",
	"ỗ": "o",
	"ộ": "o",
	"ờ": "o",
	"ở": "o",
	"ỡ": "o",
	"ớ": "o",
	"ợ": "o",
	"ù": "u",
	"ú": "u",
	"û": "u",
	"ü": "u",
	"ủ": "u",
	"ụ": "u",
	"ử": "u",
	"ữ": "u",
	"ự": "u",
	"ý": "y",
	"ÿ": "y",
	"Ā": "A",
	"ā": "a",
	"Ă": "A",
	"ă": "a",
	"Ą": "A",
	"ą": "a",
	"Ć": "C",
	"ć": "c",
	"Ĉ": "C",
	"ĉ": "c",
	"Ċ": "C",
	"ċ": "c",
	"Č": "C",
	"č": "c",
	"C̆": "C",
	"c̆": "c",
	"Ď": "D",
	"ď": "d",
	"Đ": "D",
	"đ": "d",
	"Ē": "E",
	"ē": "e",
	"Ĕ": "E",
	"ĕ": "e",
	"Ė": "E",
	"ė": "e",
	"Ę": "E",
	"ę": "e",
	"Ě": "E",
	"ě": "e",
	"Ĝ": "G",
	"Ǵ": "G",
	"ĝ": "g",
	"ǵ": "g",
	"Ğ": "G",
	"ğ": "g",
	"Ġ": "G",
	"ġ": "g",
	"Ģ": "G",
	"ģ": "g",
	"Ĥ": "H",
	"ĥ": "h",
	"Ħ": "H",
	"ħ": "h",
	"Ḫ": "H",
	"ḫ": "h",
	"Ĩ": "I",
	"ĩ": "i",
	"Ī": "I",
	"ī": "i",
	"Ĭ": "I",
	"ĭ": "i",
	"Į": "I",
	"į": "i",
	"İ": "I",
	"ı": "i",
	"Ĳ": "IJ",
	"ĳ": "ij",
	"Ĵ": "J",
	"ĵ": "j",
	"Ķ": "K",
	"ķ": "k",
	"Ḱ": "K",
	"ḱ": "k",
	"K̆": "K",
	"k̆": "k",
	"Ĺ": "L",
	"ĺ": "l",
	"Ļ": "L",
	"ļ": "l",
	"Ľ": "L",
	"ľ": "l",
	"Ŀ": "L",
	"ŀ": "l",
	"Ł": "l",
	"ł": "l",
	"Ḿ": "M",
	"ḿ": "m",
	"M̆": "M",
	"m̆": "m",
	"Ń": "N",
	"ń": "n",
	"Ņ": "N",
	"ņ": "n",
	"Ň": "N",
	"ň": "n",
	"ŉ": "n",
	"N̆": "N",
	"n̆": "n",
	"Ō": "O",
	"ō": "o",
	"Ŏ": "O",
	"ŏ": "o",
	"Ő": "O",
	"ő": "o",
	"Œ": "OE",
	"œ": "oe",
	"P̆": "P",
	"p̆": "p",
	"Ŕ": "R",
	"ŕ": "r",
	"Ŗ": "R",
	"ŗ": "r",
	"Ř": "R",
	"ř": "r",
	"R̆": "R",
	"r̆": "r",
	"Ȓ": "R",
	"ȓ": "r",
	"Ś": "S",
	"ś": "s",
	"Ŝ": "S",
	"ŝ": "s",
	"Ş": "S",
	"Ș": "S",
	"ș": "s",
	"ş": "s",
	"Š": "S",
	"š": "s",
	"Ţ": "T",
	"ţ": "t",
	"ț": "t",
	"Ț": "T",
	"Ť": "T",
	"ť": "t",
	"Ŧ": "T",
	"ŧ": "t",
	"T̆": "T",
	"t̆": "t",
	"Ũ": "U",
	"ũ": "u",
	"Ū": "U",
	"ū": "u",
	"Ŭ": "U",
	"ŭ": "u",
	"Ů": "U",
	"ů": "u",
	"Ű": "U",
	"ű": "u",
	"Ų": "U",
	"ų": "u",
	"Ȗ": "U",
	"ȗ": "u",
	"V̆": "V",
	"v̆": "v",
	"Ŵ": "W",
	"ŵ": "w",
	"Ẃ": "W",
	"ẃ": "w",
	"X̆": "X",
	"x̆": "x",
	"Ŷ": "Y",
	"ŷ": "y",
	"Ÿ": "Y",
	"Y̆": "Y",
	"y̆": "y",
	"Ź": "Z",
	"ź": "z",
	"Ż": "Z",
	"ż": "z",
	"Ž": "Z",
	"ž": "z",
	"ſ": "s",
	"ƒ": "f",
	"Ơ": "O",
	"ơ": "o",
	"Ư": "U",
	"ư": "u",
	"Ǎ": "A",
	"ǎ": "a",
	"Ǐ": "I",
	"ǐ": "i",
	"Ǒ": "O",
	"ǒ": "o",
	"Ǔ": "U",
	"ǔ": "u",
	"Ǖ": "U",
	"ǖ": "u",
	"Ǘ": "U",
	"ǘ": "u",
	"Ǚ": "U",
	"ǚ": "u",
	"Ǜ": "U",
	"ǜ": "u",
	"Ứ": "U",
	"ứ": "u",
	"Ṹ": "U",
	"ṹ": "u",
	"Ǻ": "A",
	"ǻ": "a",
	"Ǽ": "AE",
	"ǽ": "ae",
	"Ǿ": "O",
	"ǿ": "o",
	"Þ": "TH",
	"þ": "th",
	"Ṕ": "P",
	"ṕ": "p",
	"Ṥ": "S",
	"ṥ": "s",
	"X́": "X",
	"x́": "x",
	"Ѓ": "Г",
	"ѓ": "г",
	"Ќ": "К",
	"ќ": "к",
	"A̋": "A",
	"a̋": "a",
	"E̋": "E",
	"e̋": "e",
	"I̋": "I",
	"i̋": "i",
	"Ǹ": "N",
	"ǹ": "n",
	"Ồ": "O",
	"ồ": "o",
	"Ṑ": "O",
	"ṑ": "o",
	"Ừ": "U",
	"ừ": "u",
	"Ẁ": "W",
	"ẁ": "w",
	"Ỳ": "Y",
	"ỳ": "y",
	"Ȁ": "A",
	"ȁ": "a",
	"Ȅ": "E",
	"ȅ": "e",
	"Ȉ": "I",
	"ȉ": "i",
	"Ȍ": "O",
	"ȍ": "o",
	"Ȑ": "R",
	"ȑ": "r",
	"Ȕ": "U",
	"ȕ": "u",
	"B̌": "B",
	"b̌": "b",
	"Č̣": "C",
	"č̣": "c",
	"Ê̌": "E",
	"ê̌": "e",
	"F̌": "F",
	"f̌": "f",
	"Ǧ": "G",
	"ǧ": "g",
	"Ȟ": "H",
	"ȟ": "h",
	"J̌": "J",
	"ǰ": "j",
	"Ǩ": "K",
	"ǩ": "k",
	"M̌": "M",
	"m̌": "m",
	"P̌": "P",
	"p̌": "p",
	"Q̌": "Q",
	"q̌": "q",
	"Ř̩": "R",
	"ř̩": "r",
	"Ṧ": "S",
	"ṧ": "s",
	"V̌": "V",
	"v̌": "v",
	"W̌": "W",
	"w̌": "w",
	"X̌": "X",
	"x̌": "x",
	"Y̌": "Y",
	"y̌": "y",
	"A̧": "A",
	"a̧": "a",
	"B̧": "B",
	"b̧": "b",
	"Ḑ": "D",
	"ḑ": "d",
	"Ȩ": "E",
	"ȩ": "e",
	"Ɛ̧": "E",
	"ɛ̧": "e",
	"Ḩ": "H",
	"ḩ": "h",
	"I̧": "I",
	"i̧": "i",
	"Ɨ̧": "I",
	"ɨ̧": "i",
	"M̧": "M",
	"m̧": "m",
	"O̧": "O",
	"o̧": "o",
	"Q̧": "Q",
	"q̧": "q",
	"U̧": "U",
	"u̧": "u",
	"X̧": "X",
	"x̧": "x",
	"Z̧": "Z",
	"z̧": "z",
	"й":"и",
	"Й":"И",
	"ё":"е",
	"Ё":"Е",
};

var chars = Object.keys(characterMap).join('|');
var allAccents = new RegExp(chars, 'g');
var firstAccent = new RegExp(chars, '');

function matcher(match) {
	return characterMap[match];
}

var removeAccents = function(string) {
	return string.replace(allAccents, matcher);
};

var hasAccents = function(string) {
	return !!string.match(firstAccent);
};

module.exports = removeAccents;
module.exports.has = hasAccents;
module.exports.remove = removeAccents;


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




/***/ })

}]);