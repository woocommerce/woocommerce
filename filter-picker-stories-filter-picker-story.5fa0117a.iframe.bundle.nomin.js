"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3942],{

/***/ "../../packages/js/components/src/filter-picker/stories/filter-picker.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/filter-picker/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const query = {
  meal: 'breakfast'
};
const config = {
  label: 'Meal',
  staticParams: [],
  param: 'meal',
  showFilters: () => true,
  filters: [{
    label: 'Breakfast',
    value: 'breakfast'
  }, {
    label: 'Lunch',
    value: 'lunch',
    subFilters: [{
      label: 'Meat',
      value: 'meat',
      path: ['lunch']
    }, {
      label: 'Vegan',
      value: 'vegan',
      path: ['lunch']
    }, {
      label: 'Pescatarian',
      value: 'fish',
      path: ['lunch'],
      subFilters: [{
        label: 'Snapper',
        value: 'snapper',
        path: ['lunch', 'fish']
      }, {
        label: 'Cod',
        value: 'cod',
        path: ['lunch', 'fish']
      },
      // Specify a custom component to render (Work in Progress)
      {
        label: 'Other',
        value: 'other_fish',
        path: ['lunch', 'fish'],
        component: 'OtherFish'
      }]
    }]
  }, {
    label: 'Dinner',
    value: 'dinner'
  }]
};
const Basic = ({
  path = new URL(document.location).searchParams.get('path')
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
    config: config,
    path: path,
    query: query
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/FilterPicker',
  component: ___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "({\n  path = new URL(document.location).searchParams.get('path')\n}) => {\n  return <FilterPicker config={config} path={path} query={query} />;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ dropdown_default)
/* harmony export */ });
/* unused harmony export Dropdown */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
/* harmony import */ var _utils_hooks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js");
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");








const UnconnectedDropdown = (props, forwardedRef) => {
  const {
    renderContent,
    renderToggle,
    className,
    contentClassName,
    expandOnMobile,
    headerTitle,
    focusOnMount,
    popoverProps,
    onClose,
    onToggle,
    style,
    open,
    defaultOpen,
    // Deprecated props
    position,
    // From context system
    variant
  } = (0,_context__WEBPACK_IMPORTED_MODULE_1__/* .useContextSystem */ .A)(props, "Dropdown");
  if (position !== void 0) {
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)("`position` prop in wp.components.Dropdown", {
      since: "6.2",
      alternative: "`popoverProps.placement` prop",
      hint: "Note that the `position` prop will override any values passed through the `popoverProps.placement` prop."
    });
  }
  const [fallbackPopoverAnchor, setFallbackPopoverAnchor] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null);
  const containerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useRef)();
  const [isOpen, setIsOpen] = (0,_utils_hooks__WEBPACK_IMPORTED_MODULE_4__/* .useControlledValue */ .j)({
    defaultValue: defaultOpen,
    value: open,
    onChange: onToggle
  });
  function closeIfFocusOutside() {
    if (!containerRef.current) {
      return;
    }
    const {
      ownerDocument
    } = containerRef.current;
    const dialog = ownerDocument?.activeElement?.closest('[role="dialog"]');
    if (!containerRef.current.contains(ownerDocument.activeElement) && (!dialog || dialog.contains(containerRef.current))) {
      close();
    }
  }
  function close() {
    onClose?.();
    setIsOpen(false);
  }
  const args = {
    isOpen: !!isOpen,
    onToggle: () => setIsOpen(!isOpen),
    onClose: close
  };
  const popoverPropsHaveAnchor = !!popoverProps?.anchor || // Note: `anchorRef`, `getAnchorRect` and `anchorRect` are deprecated and
  // be removed from `Popover` from WordPress 6.3
  !!popoverProps?.anchorRef || !!popoverProps?.getAnchorRect || !!popoverProps?.anchorRect;
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    className,
    ref: (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)([containerRef, forwardedRef, setFallbackPopoverAnchor]),
    tabIndex: -1,
    style,
    children: [renderToggle(args), isOpen && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_popover__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      position,
      onClose: close,
      onFocusOutside: closeIfFocusOutside,
      expandOnMobile,
      headerTitle,
      focusOnMount,
      offset: 13,
      anchor: !popoverPropsHaveAnchor ? fallbackPopoverAnchor : void 0,
      variant,
      ...popoverProps,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)("components-dropdown__content", popoverProps?.className, contentClassName),
      children: renderContent(args)
    })]
  });
};
const Dropdown = (0,_context__WEBPACK_IMPORTED_MODULE_8__/* .contextConnect */ .KZ)(UnconnectedDropdown, "Dropdown");
var dropdown_default = Dropdown;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   j: () => (/* binding */ useControlledValue)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

function useControlledValue({
  defaultValue,
  onChange,
  value: valueProp
}) {
  const hasValue = typeof valueProp !== "undefined";
  const initialValue = hasValue ? valueProp : defaultValue;
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(initialValue);
  const value = hasValue ? valueProp : state;
  const uncontrolledSetValue = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)((nextValue, ...args) => {
    setState(nextValue);
    onChange?.(nextValue, ...args);
  }, [onChange]);
  let setValue;
  if (hasValue && typeof onChange === "function") {
    setValue = onChange;
  } else if (!hasValue && typeof onChange === "function") {
    setValue = uncontrolledSetValue;
  } else {
    setValue = setState;
  }
  return [value, setValue];
}

//# sourceMappingURL=use-controlled-value.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  XC: () => (/* binding */ build_module_focus)
});

// UNUSED EXPORTS: __unstableStripHTML, computeCaretRect, documentHasSelection, documentHasTextSelection, documentHasUncollapsedSelection, getFilesFromDataTransfer, getOffsetParent, getPhrasingContentSchema, getRectangleFromRange, getScrollContainer, insertAfter, isEmpty, isEntirelySelected, isFormElement, isHorizontalEdge, isNumberInput, isPhrasingContent, isRTL, isSelectionForward, isTextContent, isTextField, isVerticalEdge, placeCaretAtHorizontalEdge, placeCaretAtVerticalEdge, remove, removeInvalidHTML, replace, replaceTag, safeHTML, unwrap, wrap

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/focusable.js
var focusable_namespaceObject = {};
__webpack_require__.r(focusable_namespaceObject);
__webpack_require__.d(focusable_namespaceObject, {
  find: () => (find)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/tabbable.js
var tabbable_namespaceObject = {};
__webpack_require__.r(tabbable_namespaceObject);
__webpack_require__.d(tabbable_namespaceObject, {
  find: () => (tabbable_find),
  findNext: () => (findNext),
  findPrevious: () => (findPrevious),
  isTabbableIndex: () => (isTabbableIndex)
});

;// ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/focusable.js
function buildSelector(sequential) {
  return [
    sequential ? '[tabindex]:not([tabindex^="-"])' : "[tabindex]",
    "a[href]",
    "button:not([disabled])",
    'input:not([type="hidden"]):not([disabled])',
    "select:not([disabled])",
    "textarea:not([disabled])",
    'iframe:not([tabindex^="-"])',
    "object",
    "embed",
    "summary",
    "area[href]",
    "[contenteditable]:not([contenteditable=false])"
  ].join(",");
}
function isVisible(element) {
  return element.offsetWidth > 0 || element.offsetHeight > 0 || element.getClientRects().length > 0;
}
function isValidFocusableArea(element) {
  const map = element.closest("map[name]");
  if (!map) {
    return false;
  }
  const img = element.ownerDocument.querySelector(
    'img[usemap="#' + map.name + '"]'
  );
  return !!img && isVisible(img);
}
function find(context, { sequential = false } = {}) {
  const elements = context.querySelectorAll(buildSelector(sequential));
  return Array.from(elements).filter((element) => {
    if (!isVisible(element)) {
      return false;
    }
    const { nodeName } = element;
    if ("AREA" === nodeName) {
      return isValidFocusableArea(
        /** @type {HTMLAreaElement} */
        element
      );
    }
    return true;
  });
}

//# sourceMappingURL=focusable.js.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/tabbable.js

function getTabIndex(element) {
  const tabIndex = element.getAttribute("tabindex");
  return tabIndex === null ? 0 : parseInt(tabIndex, 10);
}
function isTabbableIndex(element) {
  return getTabIndex(element) !== -1;
}
function createStatefulCollapseRadioGroup() {
  const CHOSEN_RADIO_BY_NAME = {};
  return function collapseRadioGroup(result, element) {
    const { nodeName, type, checked, name } = element;
    if (nodeName !== "INPUT" || type !== "radio" || !name) {
      return result.concat(element);
    }
    const hasChosen = CHOSEN_RADIO_BY_NAME.hasOwnProperty(name);
    const isChosen = checked || !hasChosen;
    if (!isChosen) {
      return result;
    }
    if (hasChosen) {
      const hadChosenElement = CHOSEN_RADIO_BY_NAME[name];
      result = result.filter((e) => e !== hadChosenElement);
    }
    CHOSEN_RADIO_BY_NAME[name] = element;
    return result.concat(element);
  };
}
function mapElementToObjectTabbable(element, index) {
  return { element, index };
}
function mapObjectTabbableToElement(object) {
  return object.element;
}
function compareObjectTabbables(a, b) {
  const aTabIndex = getTabIndex(a.element);
  const bTabIndex = getTabIndex(b.element);
  if (aTabIndex === bTabIndex) {
    return a.index - b.index;
  }
  return aTabIndex - bTabIndex;
}
function filterTabbable(focusables) {
  return focusables.filter(isTabbableIndex).map(mapElementToObjectTabbable).sort(compareObjectTabbables).map(mapObjectTabbableToElement).reduce(createStatefulCollapseRadioGroup(), []);
}
function tabbable_find(context) {
  return filterTabbable(find(context));
}
function findPrevious(element) {
  return filterTabbable(find(element.ownerDocument.body)).reverse().find(
    (focusable) => (
      // eslint-disable-next-line no-bitwise
      element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_PRECEDING
    )
  );
}
function findNext(element) {
  return filterTabbable(find(element.ownerDocument.body)).find(
    (focusable) => (
      // eslint-disable-next-line no-bitwise
      element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_FOLLOWING
    )
  );
}

//# sourceMappingURL=tabbable.js.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js


const build_module_focus = { focusable: focusable_namespaceObject, tabbable: tabbable_namespaceObject };




//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_left_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_left_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z" }) });

//# sourceMappingURL=chevron-left.js.map


/***/ }),

/***/ "../../packages/js/components/src/animation-slider/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.
 * All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition
 */

class AnimationSlider extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  constructor() {
    super();
    this.state = {
      animate: null
    };
    this.container = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createRef)();
    this.onExited = this.onExited.bind(this);
  }
  onExited() {
    const {
      onExited
    } = this.props;
    if (onExited) {
      onExited(this.container.current);
    }
  }
  render() {
    const {
      children,
      animationKey,
      animate
    } = this.props;
    const containerClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-slide-animation', animate && `animate-${animate}`);
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: containerClasses,
      ref: this.container,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_transition_group__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_transition_group__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
          timeout: 200,
          classNames: "slide",
          ...this.props,
          onExited: this.onExited,
          children: status => children({
            status
          })
        }, animationKey)
      })
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AnimationSlider);
;
AnimationSlider.__docgenInfo = {
  "description": "This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.\nAll other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition",
  "methods": [{
    "name": "onExited",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "AnimationSlider",
  "props": {
    "children": {
      "description": "A function returning rendered content with argument status, reflecting `CSSTransition` status.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "animationKey": {
      "description": "A unique identifier for each slideable page.",
      "type": {
        "name": "any"
      },
      "required": true
    },
    "animate": {
      "description": "null, 'left', 'right', to designate which direction to slide on a change.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "null",
          "computed": false
        }, {
          "value": "'left'",
          "computed": false
        }, {
          "value": "'right'",
          "computed": false
        }]
      },
      "required": false
    },
    "onExited": {
      "description": "A function to be executed after a transition is complete, passing the containing ref as the argument.",
      "type": {
        "name": "func"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/dropdown-button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */






/**
 * A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays
 * single or multiple lines rendered as `<span/>` elements.
 *
 * @param {Object} props Props passed to component.
 * @return {Object} -
 */

const DropdownButton = props => {
  const {
    labels,
    isOpen,
    ...otherProps
  } = props;
  const buttonClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-dropdown-button', {
    'is-open': isOpen,
    'is-multi-line': labels.length > 1
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
    className: buttonClasses,
    "aria-expanded": isOpen,
    ...otherProps,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: "woocommerce-dropdown-button__labels",
      children: labels.map((label, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        children: (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__/* .decodeEntities */ .S)(label)
      }, i))
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DropdownButton);
;
DropdownButton.__docgenInfo = {
  "description": "A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays\nsingle or multiple lines rendered as `<span/>` elements.\n\n@param {Object} props Props passed to component.\n@return {Object} -",
  "methods": [],
  "displayName": "DropdownButton",
  "props": {
    "labels": {
      "description": "An array of elements to be rendered as the content of the button.",
      "type": {
        "name": "array"
      },
      "required": true
    },
    "isOpen": {
      "description": "Boolean describing if the dropdown in open or not.",
      "type": {
        "name": "bool"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export DEFAULT_FILTER */
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var _animation_slider__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../packages/js/components/src/animation-slider/index.js");
/* harmony import */ var _dropdown_button__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../packages/js/components/src/dropdown-button/index.js");
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../packages/js/components/src/search/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */










/**
 * Internal dependencies
 */




const DEFAULT_FILTER = 'all';

/**
 * Modify a url query parameter via a dropdown selection of configurable options.
 * This component manipulates the `filter` query parameter.
 */
class FilterPicker extends _wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Component {
  constructor(props) {
    super(props);
    const selectedFilter = this.getFilter();
    this.state = {
      nav: selectedFilter.path || [],
      animate: null,
      selectedTag: null
    };
    this.selectSubFilter = this.selectSubFilter.bind(this);
    this.getVisibleFilters = this.getVisibleFilters.bind(this);
    this.updateSelectedTag = this.updateSelectedTag.bind(this);
    this.onTagChange = this.onTagChange.bind(this);
    this.onContentMount = this.onContentMount.bind(this);
    this.goBack = this.goBack.bind(this);
    if (selectedFilter.settings && selectedFilter.settings.getLabels) {
      const {
        query
      } = this.props;
      const {
        param: filterParam,
        getLabels
      } = selectedFilter.settings;
      getLabels(query[filterParam], query).then(this.updateSelectedTag);
    }
  }
  componentDidUpdate({
    query: prevQuery
  }) {
    const {
      query: nextQuery,
      config
    } = this.props;
    if (prevQuery[config.param] !== nextQuery[[config.param]]) {
      const selectedFilter = this.getFilter();
      if (selectedFilter && selectedFilter.component === 'Search') {
        /* eslint-disable react/no-did-update-set-state */
        this.setState({
          nav: selectedFilter.path || []
        });
        /* eslint-enable react/no-did-update-set-state */
        const {
          param: filterParam,
          getLabels
        } = selectedFilter.settings;
        getLabels(nextQuery[filterParam], nextQuery).then(this.updateSelectedTag);
      }
    }
  }
  updateSelectedTag(tags) {
    this.setState({
      selectedTag: tags[0]
    });
  }
  getFilter(value) {
    const {
      config,
      query
    } = this.props;
    const allFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .flattenFilters */ .SI)(config.filters);
    value = value || query[config.param] || config.defaultValue || DEFAULT_FILTER;
    return (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(allFilters, {
      value
    }) || {};
  }
  getButtonLabel(selectedFilter) {
    if (selectedFilter.component === 'Search') {
      const {
        selectedTag
      } = this.state;
      return [selectedTag && selectedTag.label, (0,lodash__WEBPACK_IMPORTED_MODULE_1__.get)(selectedFilter, 'settings.labels.button')];
    }
    return selectedFilter ? [selectedFilter.label] : [];
  }
  getVisibleFilters(filters, nav) {
    if (nav.length === 0) {
      return filters;
    }
    const value = nav[0];
    const nextFilters = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(filters, {
      value
    });
    return this.getVisibleFilters(nextFilters && nextFilters.subFilters, nav.slice(1));
  }
  selectSubFilter(value) {
    // Add the value onto the nav path
    this.setState(prevState => ({
      nav: [...prevState.nav, value],
      animate: 'left'
    }));
  }
  goBack() {
    // Remove the last item from the nav path
    this.setState(prevState => ({
      nav: prevState.nav.slice(0, -1),
      animate: 'right'
    }));
  }
  getAllFilterParams() {
    const {
      config
    } = this.props;
    const params = [];
    const getParam = filters => {
      filters.forEach(filter => {
        if (filter.settings && !params.includes(filter.settings.param)) {
          params.push(filter.settings.param);
        }
        if (filter.subFilters) {
          getParam(filter.subFilters);
        }
      });
    };
    getParam(config.filters);
    return params;
  }
  update(value, additionalQueries = {}) {
    const {
      path,
      query,
      config,
      onFilterSelect,
      advancedFilters
    } = this.props;
    let update = {
      [config.param]: (config.defaultValue || DEFAULT_FILTER) === value ? undefined : value,
      ...additionalQueries
    };
    // Keep any url parameters as designated by the config
    config.staticParams.forEach(param => {
      update[param] = query[param];
    });

    // Remove all of this filter's params not associated with the update while
    // leaving any other params from any other filter an extension may have added.
    this.getAllFilterParams().forEach(param => {
      if (!update[param]) {
        // Explicitly give value of undefined so it can be removed from the query.
        update[param] = undefined;
      }
    });

    // If the main filter is being set to anything but advanced, remove any advancedFilters.
    if (config.param === 'filter' && value !== 'advanced') {
      const resetAdvancedFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .getQueryFromActiveFilters */ .Sz)([], query, advancedFilters.filters || {});
      update = {
        ...update,
        ...resetAdvancedFilters
      };
    }
    (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .updateQueryString */ .Ze)(update, path, query);
    onFilterSelect(update);
  }
  onTagChange(filter, onClose, config, tags) {
    const tag = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.last)(tags);
    const {
      value,
      settings
    } = filter;
    const {
      param: filterParam
    } = settings;
    if (tag) {
      this.update(value, {
        [filterParam]: tag.key
      });
      onClose();
    } else {
      this.update(config.defaultValue || DEFAULT_FILTER);
    }
    this.updateSelectedTag([tag]);
  }
  renderButton(filter, onClose, config) {
    if (filter.component) {
      const {
        type,
        labels,
        autocompleter
      } = filter.settings;
      const persistedFilter = this.getFilter();
      const selectedTag = persistedFilter.value === filter.value ? this.state.selectedTag : null;
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_search__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
        autocompleter: autocompleter,
        className: "woocommerce-filters-filter__search",
        type: type,
        placeholder: labels.placeholder,
        selected: selectedTag ? [selectedTag] : [],
        onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partial)(this.onTagChange, filter, onClose, config),
        inlineTags: true,
        staticResults: true
      });
    }
    const selectFilter = event => {
      onClose(event);
      this.update(filter.value, filter.query || {});
      this.setState({
        selectedTag: null
      });
    };
    const selectSubFilter = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partial)(this.selectSubFilter, filter.value);
    const selectedFilter = this.getFilter();
    const buttonIsSelected = selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_1__.includes)(selectedFilter.path, filter.value);
    const onClick = event => {
      if (buttonIsSelected) {
        // Don't navigate if the button is already selected.
        onClose(event);
        return;
      }
      if (filter.subFilters) {
        selectSubFilter(event);
        return;
      }
      selectFilter(event);
    };
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-filters-filter__button",
      onClick: onClick,
      children: filter.label
    });
  }
  onContentMount(content) {
    const {
      nav
    } = this.state;
    const parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
    const focusableIndex = parentFilter ? 1 : 0;
    const focusable = _wordpress_dom__WEBPACK_IMPORTED_MODULE_7__/* .focus */ .XC.tabbable.find(content)[focusableIndex];
    setTimeout(() => {
      focusable.focus();
    }, 0);
  }
  render() {
    const {
      config
    } = this.props;
    const {
      nav,
      animate
    } = this.state;
    const visibleFilters = this.getVisibleFilters(config.filters, nav);
    const parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
    const selectedFilter = this.getFilter();
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      className: "woocommerce-filters-filter",
      children: [config.label && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "woocommerce-filters-label",
        children: config.label
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
        contentClassName: "woocommerce-filters-filter__content",
        popoverProps: {
          placement: 'bottom'
        },
        expandOnMobile: true,
        headerTitle: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('filter report to show:', 'woocommerce'),
        renderToggle: ({
          isOpen,
          onToggle
        }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_dropdown_button__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A, {
          onClick: onToggle,
          isOpen: isOpen,
          labels: this.getButtonLabel(selectedFilter)
        }),
        renderContent: ({
          onClose
        }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_animation_slider__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A, {
          animationKey: nav,
          animate: animate,
          onExited: this.onContentMount,
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("ul", {
            className: "woocommerce-filters-filter__content-list",
            children: [parentFilter && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
              className: "woocommerce-filters-filter__content-list-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
                className: "woocommerce-filters-filter__button",
                onClick: this.goBack,
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A, {
                  icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A
                }), parentFilter.label]
              })
            }), visibleFilters.map(filter => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
              className: (0,clsx__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)('woocommerce-filters-filter__content-list-item', {
                'is-selected': selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_1__.includes)(selectedFilter.path, filter.value)
              }),
              children: this.renderButton(filter, onClose, config)
            }, filter.value))]
          })
        })
      })]
    });
  }
}
FilterPicker.defaultProps = {
  query: {},
  onFilterSelect: () => {}
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FilterPicker);
;
FilterPicker.__docgenInfo = {
  "description": "Modify a url query parameter via a dropdown selection of configurable options.\nThis component manipulates the `filter` query parameter.",
  "methods": [{
    "name": "updateSelectedTag",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "tags",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getFilter",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getButtonLabel",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selectedFilter",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getVisibleFilters",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filters",
      "optional": false,
      "type": null
    }, {
      "name": "nav",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "selectSubFilter",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "goBack",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getAllFilterParams",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "update",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }, {
      "name": "additionalQueries",
      "optional": true,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onTagChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filter",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }, {
      "name": "config",
      "optional": false,
      "type": null
    }, {
      "name": "tags",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "renderButton",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filter",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }, {
      "name": "config",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onContentMount",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "content",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "FilterPicker",
  "props": {
    "query": {
      "defaultValue": {
        "value": "{}",
        "computed": false
      },
      "description": "The query string represented in object form.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "onFilterSelect": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "Function to be called after filter selection.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "config": {
      "description": "An array of filters and subFilters to construct the menu.",
      "type": {
        "name": "shape",
        "value": {
          "label": {
            "name": "string",
            "description": "A label above the filter selector.",
            "required": false
          },
          "staticParams": {
            "name": "array",
            "description": "Url parameters to persist when selecting a new filter.",
            "required": true
          },
          "param": {
            "name": "string",
            "description": "The url parameter this filter will modify.",
            "required": true
          },
          "defaultValue": {
            "name": "string",
            "description": "The default parameter value to use instead of 'all'.",
            "required": false
          },
          "showFilters": {
            "name": "func",
            "description": "Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.",
            "required": true
          },
          "filters": {
            "name": "arrayOf",
            "value": {
              "name": "shape",
              "value": {
                "chartMode": {
                  "name": "enum",
                  "value": [{
                    "value": "'item-comparison'",
                    "computed": false
                  }, {
                    "value": "'time-comparison'",
                    "computed": false
                  }],
                  "description": "The chart display mode to use for charts displayed when this filter is active.",
                  "required": false
                },
                "component": {
                  "name": "string",
                  "description": "A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.",
                  "required": false
                },
                "label": {
                  "name": "string",
                  "description": "The label for this filter. Optional only for custom component filters.",
                  "required": false
                },
                "path": {
                  "name": "string",
                  "description": "An array representing the \"path\" to this filter, if nested.",
                  "required": false
                },
                "subFilters": {
                  "name": "array",
                  "description": "An array of more filter objects that act as \"children\" to this item.\nThis set of filters is shown if the parent filter is clicked.",
                  "required": false
                },
                "value": {
                  "name": "string",
                  "description": "The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.",
                  "required": true
                }
              }
            },
            "description": "An array of filter a user can select.",
            "required": false
          }
        }
      },
      "required": true
    },
    "path": {
      "description": "The `path` parameter supplied by React-Router.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "advancedFilters": {
      "description": "Advanced Filters configuration object.",
      "type": {
        "name": "object"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/search/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/components/src/search/search.tsx");
/**
 * Internal dependencies
 */


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_search__WEBPACK_IMPORTED_MODULE_0__/* .Search */ .v);
try {
    // @ts-ignore
    Search.displayName = "Search";
    // @ts-ignore
    Search.__docgenInfo = { "description": "A search box which autocompletes results while typing, allowing for the user to select an existing object\n(product, order, customer, etc). Currently only products are supported.", "displayName": "Search", "props": { "allowFreeTextSearch": { "defaultValue": { value: "false" }, "description": "Render additional options in the autocompleter to allow free text entering depending on the type.", "name": "allowFreeTextSearch", "required": false, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((value: Option | OptionCompletionValue[]) => unknown)" } }, "type": { "defaultValue": null, "description": "The object type to be used in searching.", "name": "type", "required": true, "type": { "name": "enum", "value": [{ "value": "\"custom\"" }, { "value": "\"countries\"" }, { "value": "\"attributes\"" }, { "value": "\"categories\"" }, { "value": "\"coupons\"" }, { "value": "\"customerNames\"" }, { "value": "\"customers\"" }, { "value": "\"downloadIps\"" }, { "value": "\"emails\"" }, { "value": "\"orders\"" }, { "value": "\"products\"" }, { "value": "\"registeredCustomers\"" }, { "value": "\"taxes\"" }, { "value": "\"usernames\"" }, { "value": "\"variableProducts\"" }, { "value": "\"variations\"" }] } }, "autocompleter": { "defaultValue": null, "description": "The custom autocompleter to be used in searching when type is 'custom'", "name": "autocompleter", "required": false, "type": { "name": "AutoCompleter" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "string | { key: string; label: string; }[]" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "staticResults": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticResults", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": { value: "false" }, "description": "Whether the control is disabled or not.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "true" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/search/index.tsx#Search"] = { docgenInfo: Search.__docgenInfo, name: "Search", path: "../../packages/js/components/src/search/index.tsx#Search" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ esm_TransitionGroup)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js + 1 modules
var inheritsLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js");
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/utils/ChildMapping.js

/**
 * Given `this.props.children`, return an object mapping key to child.
 *
 * @param {*} children `this.props.children`
 * @return {object} Mapping of key to child
 */

function getChildMapping(children, mapFn) {
  var mapper = function mapper(child) {
    return mapFn && (0,react.isValidElement)(child) ? mapFn(child) : child;
  };

  var result = Object.create(null);
  if (children) react.Children.map(children, function (c) {
    return c;
  }).forEach(function (child) {
    // run the map function here instead so that the key is the computed one
    result[child.key] = mapper(child);
  });
  return result;
}
/**
 * When you're adding or removing children some may be added or removed in the
 * same render pass. We want to show *both* since we want to simultaneously
 * animate elements in and out. This function takes a previous set of keys
 * and a new set of keys and merges them with its best guess of the correct
 * ordering. In the future we may expose some of the utilities in
 * ReactMultiChild to make this easy, but for now React itself does not
 * directly have this concept of the union of prevChildren and nextChildren
 * so we implement it here.
 *
 * @param {object} prev prev children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @param {object} next next children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @return {object} a key set that contains all keys in `prev` and all keys
 * in `next` in a reasonable order.
 */

function mergeChildMappings(prev, next) {
  prev = prev || {};
  next = next || {};

  function getValueForKey(key) {
    return key in next ? next[key] : prev[key];
  } // For each key of `next`, the list of keys to insert before that key in
  // the combined list


  var nextKeysPending = Object.create(null);
  var pendingKeys = [];

  for (var prevKey in prev) {
    if (prevKey in next) {
      if (pendingKeys.length) {
        nextKeysPending[prevKey] = pendingKeys;
        pendingKeys = [];
      }
    } else {
      pendingKeys.push(prevKey);
    }
  }

  var i;
  var childMapping = {};

  for (var nextKey in next) {
    if (nextKeysPending[nextKey]) {
      for (i = 0; i < nextKeysPending[nextKey].length; i++) {
        var pendingNextKey = nextKeysPending[nextKey][i];
        childMapping[nextKeysPending[nextKey][i]] = getValueForKey(pendingNextKey);
      }
    }

    childMapping[nextKey] = getValueForKey(nextKey);
  } // Finally, add the keys which didn't appear before any key in `next`


  for (i = 0; i < pendingKeys.length; i++) {
    childMapping[pendingKeys[i]] = getValueForKey(pendingKeys[i]);
  }

  return childMapping;
}

function getProp(child, prop, props) {
  return props[prop] != null ? props[prop] : child.props[prop];
}

function getInitialChildMapping(props, onExited) {
  return getChildMapping(props.children, function (child) {
    return (0,react.cloneElement)(child, {
      onExited: onExited.bind(null, child),
      in: true,
      appear: getProp(child, 'appear', props),
      enter: getProp(child, 'enter', props),
      exit: getProp(child, 'exit', props)
    });
  });
}
function getNextChildMapping(nextProps, prevChildMapping, onExited) {
  var nextChildMapping = getChildMapping(nextProps.children);
  var children = mergeChildMappings(prevChildMapping, nextChildMapping);
  Object.keys(children).forEach(function (key) {
    var child = children[key];
    if (!(0,react.isValidElement)(child)) return;
    var hasPrev = (key in prevChildMapping);
    var hasNext = (key in nextChildMapping);
    var prevChild = prevChildMapping[key];
    var isLeaving = (0,react.isValidElement)(prevChild) && !prevChild.props.in; // item is new (entering)

    if (hasNext && (!hasPrev || isLeaving)) {
      // console.log('entering', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: true,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    } else if (!hasNext && hasPrev && !isLeaving) {
      // item is old (exiting)
      // console.log('leaving', key)
      children[key] = (0,react.cloneElement)(child, {
        in: false
      });
    } else if (hasNext && hasPrev && (0,react.isValidElement)(prevChild)) {
      // item hasn't changed transition states
      // copy over the last transition props;
      // console.log('unchanged', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: prevChild.props.in,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    }
  });
  return children;
}
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js









var values = Object.values || function (obj) {
  return Object.keys(obj).map(function (k) {
    return obj[k];
  });
};

var defaultProps = {
  component: 'div',
  childFactory: function childFactory(child) {
    return child;
  }
};
/**
 * The `<TransitionGroup>` component manages a set of transition components
 * (`<Transition>` and `<CSSTransition>`) in a list. Like with the transition
 * components, `<TransitionGroup>` is a state machine for managing the mounting
 * and unmounting of components over time.
 *
 * Consider the example below. As items are removed or added to the TodoList the
 * `in` prop is toggled automatically by the `<TransitionGroup>`.
 *
 * Note that `<TransitionGroup>`  does not define any animation behavior!
 * Exactly _how_ a list item animates is up to the individual transition
 * component. This means you can mix and match animations across different list
 * items.
 */

var TransitionGroup = /*#__PURE__*/function (_React$Component) {
  (0,inheritsLoose/* default */.A)(TransitionGroup, _React$Component);

  function TransitionGroup(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;

    var handleExited = _this.handleExited.bind((0,assertThisInitialized/* default */.A)(_this)); // Initial children should all be entering, dependent on appear


    _this.state = {
      contextValue: {
        isMounting: true
      },
      handleExited: handleExited,
      firstRender: true
    };
    return _this;
  }

  var _proto = TransitionGroup.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this.mounted = true;
    this.setState({
      contextValue: {
        isMounting: false
      }
    });
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    this.mounted = false;
  };

  TransitionGroup.getDerivedStateFromProps = function getDerivedStateFromProps(nextProps, _ref) {
    var prevChildMapping = _ref.children,
        handleExited = _ref.handleExited,
        firstRender = _ref.firstRender;
    return {
      children: firstRender ? getInitialChildMapping(nextProps, handleExited) : getNextChildMapping(nextProps, prevChildMapping, handleExited),
      firstRender: false
    };
  } // node is `undefined` when user provided `nodeRef` prop
  ;

  _proto.handleExited = function handleExited(child, node) {
    var currentChildMapping = getChildMapping(this.props.children);
    if (child.key in currentChildMapping) return;

    if (child.props.onExited) {
      child.props.onExited(node);
    }

    if (this.mounted) {
      this.setState(function (state) {
        var children = (0,esm_extends/* default */.A)({}, state.children);

        delete children[child.key];
        return {
          children: children
        };
      });
    }
  };

  _proto.render = function render() {
    var _this$props = this.props,
        Component = _this$props.component,
        childFactory = _this$props.childFactory,
        props = (0,objectWithoutPropertiesLoose/* default */.A)(_this$props, ["component", "childFactory"]);

    var contextValue = this.state.contextValue;
    var children = values(this.state.children).map(childFactory);
    delete props.appear;
    delete props.enter;
    delete props.exit;

    if (Component === null) {
      return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
        value: contextValue
      }, children);
    }

    return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
      value: contextValue
    }, /*#__PURE__*/react.createElement(Component, props, children));
  };

  return TransitionGroup;
}(react.Component);

TransitionGroup.propTypes =  false ? 0 : {};
TransitionGroup.defaultProps = defaultProps;
/* harmony default export */ const esm_TransitionGroup = (TransitionGroup);

/***/ })

}]);