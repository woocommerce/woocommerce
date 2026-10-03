"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9462],{

/***/ "../../packages/js/components/src/summary/stories/summary.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ summary_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js + 29 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js + 4 modules
var menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
;// ../../packages/js/components/src/summary/utils.js
/**
 * Get a class name depending on item count.
 *
 * @param {number} count - Item count.
 * @return {string} - class name.
 */
function getHasItemsClass(count) {
  return count < 10 ? `has-${count}-items` : 'has-10-items';
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/summary/menu.js
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */


const Menu = ({
  label,
  orientation,
  itemCount,
  items
}) => {
  const instanceId = (0,lodash.uniqueId)('woocommerce-summary-helptext-');
  const hasItemsClass = getHasItemsClass(itemCount);
  const classes = (0,clsx/* default */.A)('woocommerce-summary', {
    [hasItemsClass]: orientation === 'horizontal'
  });
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(menu/* default */.Ay, {
    "aria-label": label,
    "aria-describedby": instanceId,
    orientation: orientation,
    stopNavigationEvents: true,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
      id: instanceId,
      className: "screen-reader-text",
      children: (0,build_module.__)('List of data points available for filtering. Use arrow keys to cycle through ' + 'the list. Click a data point for a detailed report.', 'woocommerce')
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("ul", {
      className: classes,
      children: items
    })]
  });
};
/* harmony default export */ const summary_menu = (Menu);
;
Menu.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "Menu",
  "props": {
    "label": {
      "description": "An optional label of this group, read to screen reader users.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "orientation": {
      "description": "Item layout orientation.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'vertical'",
          "computed": false
        }, {
          "value": "'horizontal'",
          "computed": false
        }]
      },
      "required": true
    },
    "items": {
      "description": "A list of `<SummaryNumber />`s.",
      "type": {
        "name": "node"
      },
      "required": true
    },
    "itemCount": {
      "description": "Number of items.",
      "type": {
        "name": "number"
      },
      "required": true
    }
  }
};
;// ../../packages/js/components/src/summary/index.js
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


/**
 * A container element for a list of SummaryNumbers. This component handles detecting & switching to
 * the mobile format on smaller screens.
 *
 * @param {Object} props
 * @param {Node}   props.children
 * @param {string} props.isDropdownBreakpoint
 * @param {string} props.label
 * @return {Object} -
 */

const SummaryList = ({
  children,
  isDropdownBreakpoint,
  label = (0,build_module.__)('Performance Indicators', 'woocommerce')
}) => {
  const items = children({});
  // We default to "one" because we can't have empty children.
  const itemCount = react.Children.count(items) || 1;
  const orientation = isDropdownBreakpoint ? 'vertical' : 'horizontal';
  const summaryMenu = /*#__PURE__*/(0,jsx_runtime.jsx)(summary_menu, {
    label: label,
    orientation: orientation,
    itemCount: itemCount,
    items: items
  });

  // On large screens, or if there are not multiple SummaryNumbers, we'll display the plain list.
  if (!isDropdownBreakpoint || itemCount < 2) {
    return summaryMenu;
  }
  const selected = items.find(item => !!item.props.selected);
  if (!selected) {
    return summaryMenu;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown/* default */.A, {
    className: "woocommerce-summary",
    popoverProps: {
      placement: 'bottom'
    },
    headerTitle: label,
    renderToggle: ({
      isOpen,
      onToggle
    }) => (0,react.cloneElement)(selected, {
      onToggle,
      isOpen
    }),
    renderContent: renderContentArgs => /*#__PURE__*/(0,jsx_runtime.jsx)(summary_menu, {
      label: label,
      orientation: orientation,
      itemCount: itemCount,
      items: children(renderContentArgs)
    })
  });
};
/* harmony default export */ const summary = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isDropdownBreakpoint: '< large'
})(SummaryList));
;
SummaryList.__docgenInfo = {
  "description": "A container element for a list of SummaryNumbers. This component handles detecting & switching to\nthe mobile format on smaller screens.\n\n@param {Object} props\n@param {Node}   props.children\n@param {string} props.isDropdownBreakpoint\n@param {string} props.label\n@return {Object} -",
  "methods": [],
  "displayName": "SummaryList",
  "props": {
    "label": {
      "defaultValue": {
        "value": "__( 'Performance Indicators', 'woocommerce' )",
        "computed": true
      },
      "description": "An optional label of this group, read to screen reader users.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "children": {
      "description": "A function returning a list of `<SummaryNumber />`s",
      "type": {
        "name": "func"
      },
      "required": true
    }
  }
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js + 40 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/info.js
var info = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/info.js");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental.js
var experimental = __webpack_require__("../../packages/js/components/src/experimental.js");
;// ../../packages/js/components/src/summary/number.js
/**
 * External dependencies
 */









/**
 * Internal dependencies
 */



/**
 * A component to show a value, label, and optionally a change percentage and children node. Can also act as a link to a specific report focus.
 *
 * @param {Object}        props
 * @param {Node}          props.children
 * @param {number}        props.delta               Change percentage. Float precision is rendered as given.
 * @param {string}        props.href
 * @param {string}        props.hrefType
 * @param {boolean}       props.isOpen
 * @param {string}        props.label
 * @param {string}        props.labelTooltipText
 * @param {Function}      props.onToggle
 * @param {string}        props.prevLabel
 * @param {number|string} props.prevValue
 * @param {boolean}       props.reverseTrend
 * @param {boolean}       props.selected
 * @param {number|string} props.value
 * @param {Function}      props.onLinkClickCallback
 * @return {Object} -
 */

const SummaryNumber = ({
  children,
  delta,
  href = '',
  hrefType = 'wc-admin',
  isOpen = false,
  label,
  labelTooltipText,
  onToggle,
  prevLabel = (0,build_module.__)('Previous period:', 'woocommerce'),
  prevValue,
  reverseTrend = false,
  selected = false,
  value,
  onLinkClickCallback = lodash.noop
}) => {
  const liClasses = (0,clsx/* default */.A)('woocommerce-summary__item-container', {
    'is-dropdown-button': onToggle,
    'is-dropdown-expanded': isOpen
  });
  const classes = (0,clsx/* default */.A)('woocommerce-summary__item', {
    'is-selected': selected,
    'is-good-trend': reverseTrend ? delta < 0 : delta > 0,
    'is-bad-trend': reverseTrend ? delta > 0 : delta < 0
  });
  let screenReaderLabel = delta > 0 ?
  // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
  (0,build_module/* sprintf */.nv)(/* translators: percentage change upwards */
  (0,build_module.__)('Up %f%% from %s', 'woocommerce'), delta, prevLabel) :
  // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
  (0,build_module/* sprintf */.nv)(/* translators: percentage change downwards */
  (0,build_module.__)('Down %f%% from %s', 'woocommerce'), Math.abs(delta), prevLabel);
  if (!delta) {
    screenReaderLabel = (0,build_module/* sprintf */.nv)(/* translators: previous value */
    (0,build_module.__)('No change from %s', 'woocommerce'), prevLabel);
  }
  let Container;
  const containerProps = {
    className: classes,
    'aria-current': selected ? 'page' : null
  };
  if (onToggle || href) {
    const isButton = !!onToggle;
    Container = isButton ? build_module_button/* default */.Ay : src_link/* default */.A;
    if (isButton) {
      containerProps.onClick = onToggle;
      containerProps['aria-expanded'] = isOpen;
    } else {
      containerProps.href = href;
      containerProps.role = 'menuitem';
      containerProps.onClick = onLinkClickCallback;
      containerProps.type = hrefType;
    }
  } else {
    Container = 'div';
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
    className: liClasses,
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)(Container, {
      ...containerProps,
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-summary__item-label",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(experimental/* Text */.E, {
          variant: "body.small",
          size: "14",
          lineHeight: "20px",
          children: label
        }), labelTooltipText && /*#__PURE__*/(0,jsx_runtime.jsx)(tooltip/* default */.Ay, {
          text: labelTooltipText,
          position: "top center",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
            className: "woocommerce-summary__info-tooltip",
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
              width: 20,
              height: 20,
              icon: info/* default */.A
            })
          })
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-summary__item-data",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-summary__item-value",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(experimental/* Text */.E, {
            variant: "title.small",
            size: "20",
            lineHeight: "28px",
            children: !(0,lodash.isNil)(value) ? value : (0,build_module.__)('N/A', 'woocommerce')
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(tooltip/* default */.Ay, {
          text: !(0,lodash.isNil)(prevValue) ? `${prevLabel} ${prevValue}` : (0,build_module.__)('N/A', 'woocommerce'),
          position: "top center",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
            className: "woocommerce-summary__item-delta",
            role: "presentation",
            "aria-label": screenReaderLabel,
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(experimental/* Text */.E, {
              variant: "caption",
              size: "12",
              lineHeight: "16px",
              children: !(0,lodash.isNil)(delta) ?
              // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
              (0,build_module/* sprintf */.nv)(/* translators: percentage change */
              (0,build_module.__)('%f%%', 'woocommerce'), delta) : (0,build_module.__)('N/A', 'woocommerce')
            })
          })
        })]
      }), onToggle ? /*#__PURE__*/(0,jsx_runtime.jsx)(chevron_down/* default */.A, {
        className: "woocommerce-summary__toggle",
        size: 24
      }) : null, children]
    })
  });
};
/* harmony default export */ const number = (SummaryNumber);
;
SummaryNumber.__docgenInfo = {
  "description": "A component to show a value, label, and optionally a change percentage and children node. Can also act as a link to a specific report focus.\n\n@param {Object}        props\n@param {Node}          props.children\n@param {number}        props.delta               Change percentage. Float precision is rendered as given.\n@param {string}        props.href\n@param {string}        props.hrefType\n@param {boolean}       props.isOpen\n@param {string}        props.label\n@param {string}        props.labelTooltipText\n@param {Function}      props.onToggle\n@param {string}        props.prevLabel\n@param {number|string} props.prevValue\n@param {boolean}       props.reverseTrend\n@param {boolean}       props.selected\n@param {number|string} props.value\n@param {Function}      props.onLinkClickCallback\n@return {Object} -",
  "methods": [],
  "displayName": "SummaryNumber",
  "props": {
    "href": {
      "defaultValue": {
        "value": "''",
        "computed": false
      },
      "description": "An internal link to the report focused on this number.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "hrefType": {
      "defaultValue": {
        "value": "'wc-admin'",
        "computed": false
      },
      "description": "The type of the link",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'wp-admin'",
          "computed": false
        }, {
          "value": "'wc-admin'",
          "computed": false
        }, {
          "value": "'external'",
          "computed": false
        }]
      },
      "required": false
    },
    "isOpen": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "description": "Boolean describing whether the menu list is open. Only applies in mobile view,\nand only applies to the toggle-able item (first in the list).",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "prevLabel": {
      "defaultValue": {
        "value": "__( 'Previous period:', 'woocommerce' )",
        "computed": true
      },
      "description": "A string description of the previous value's timeframe, ex \"Previous year:\".",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "reverseTrend": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "description": "A boolean used to indicate that a negative delta is \"good\", and should be styled like a positive (and vice-versa).",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "selected": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "description": "A boolean used to show a highlight style on this number.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "onLinkClickCallback": {
      "defaultValue": {
        "value": "noop",
        "computed": true
      },
      "description": "A function to be called after a SummaryNumber, rendered as a link, is clicked.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "delta": {
      "description": "A number to represent the percentage change since the last comparison period - positive numbers will show\na green up arrow, negative numbers will show a red down arrow, and zero will show a flat right arrow.\nIf omitted, no change value will display.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "label": {
      "description": "A string description of this value, ex \"Revenue\", or \"New Customers\"",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "labelTooltipText": {
      "description": "A string that will displayed via a Tooltip next to the label",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onToggle": {
      "description": "A function used to switch the given SummaryNumber to a button, and called on click.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "prevValue": {
      "description": "A string or number value to display - a string is allowed so we can accept currency formatting.\nIf omitted, this section won't display.",
      "type": {
        "name": "union",
        "value": [{
          "name": "number"
        }, {
          "name": "string"
        }]
      },
      "required": false
    },
    "value": {
      "description": "A string or number value to display - a string is allowed so we can accept currency formatting.",
      "type": {
        "name": "union",
        "value": [{
          "name": "number"
        }, {
          "name": "string"
        }]
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/summary/stories/summary.story.js
/**
 * External dependencies
 */


const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(summary, {
  children: () => {
    return [/*#__PURE__*/(0,jsx_runtime.jsx)(number, {
      value: '$829.40',
      label: "Total sales",
      delta: 29,
      href: "/analytics/report",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        children: "27 orders"
      })
    }, "revenue"), /*#__PURE__*/(0,jsx_runtime.jsx)(number, {
      value: '$24.00',
      label: "Refunds",
      delta: -10.12,
      href: "/analytics/report",
      selected: true
    }, "refunds"), /*#__PURE__*/(0,jsx_runtime.jsx)(number, {
      value: '$49.90',
      label: "Coupons",
      href: "/analytics/report"
    }, "coupons")];
  }
});
/* harmony default export */ const summary_story = ({
  title: 'Components/SummaryList',
  component: summary
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <SummaryList>\n        {() => {\n    return [<SummaryNumber key=\"revenue\" value={'$829.40'} label=\"Total sales\" delta={29} href=\"/analytics/report\">\n                    <span>27 orders</span>\n                </SummaryNumber>, <SummaryNumber key=\"refunds\" value={'$24.00'} label=\"Refunds\" delta={-10.12} href=\"/analytics/report\" selected />, <SummaryNumber key=\"coupons\" value={'$49.90'} label=\"Coupons\" href=\"/analytics/report\" />];\n  }}\n    </SummaryList>",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */


/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
const Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Text || _wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A;

/***/ }),

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
const Link = ({
  href,
  children,
  type = 'wc-admin',
  ...props
}) => {
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  const wcAdminLinkHandler = (onClick, event) => {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
      return;
    }
    event?.preventDefault();

    // If there is an onclick event, execute it.
    const onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if (event?.target instanceof Element) {
      const closestEventTarget = event.target.closest('a')?.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  const passProps = {
    ...props,
    'data-link-type': type
  };
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
    href: href,
    ...passProps,
    children: children
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Link);
try {
    // @ts-ignore
    Link.displayName = "Link";
    // @ts-ignore
    Link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "Link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#Link"] = { docgenInfo: Link.__docgenInfo, name: "Link", path: "../../packages/js/components/src/link/index.tsx#Link" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    link.displayName = "link";
    // @ts-ignore
    link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#link"] = { docgenInfo: link.__docgenInfo, name: "link", path: "../../packages/js/components/src/link/index.tsx#link" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);