"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9462],{

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */


/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
var Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Text || _wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A;

/***/ }),

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../packages/js/navigation/src/index.js");













var _excluded = ["href", "children", "type"];

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
      (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */




// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
var Link = function Link(_ref) {
  var href = _ref.href,
    children = _ref.children,
    _ref$type = _ref.type,
    type = _ref$type === void 0 ? 'wc-admin' : _ref$type,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A)(_ref, _excluded);
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  var wcAdminLinkHandler = function wcAdminLinkHandler(onClick, event) {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event !== null && event !== void 0 && event.ctrlKey || event !== null && event !== void 0 && event.metaKey || event !== null && event !== void 0 && event.altKey || event !== null && event !== void 0 && event.shiftKey) {
      return;
    }
    event === null || event === void 0 || event.preventDefault();

    // If there is an onclick event, execute it.
    var onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if ((event === null || event === void 0 ? void 0 : event.target) instanceof Element) {
      var _event$target$closest;
      var closestEventTarget = (_event$target$closest = event.target.closest('a')) === null || _event$target$closest === void 0 ? void 0 : _event$target$closest.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  var passProps = _objectSpread(_objectSpread({}, props), {}, {
    'data-link-type': type
  });
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_11__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_13__.createElement)("a", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_15__/* ["default"] */ .A)({
    href: href
  }, passProps), children);
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

/***/ }),

/***/ "../../packages/js/components/src/summary/stories/summary.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ summary_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js + 6 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/menu.js + 1 modules
var menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/summary/utils.js
/**
 * Get a class name depending on item count.
 *
 * @param {number} count - Item count.
 * @return {string} - class name.
 */
function getHasItemsClass(count) {
  return count < 10 ? "has-".concat(count, "-items") : 'has-10-items';
}
;// CONCATENATED MODULE: ../../packages/js/components/src/summary/menu.js

/**
 * External dependencies
 */







/**
 * Internal dependencies
 */

var Menu = function Menu(_ref) {
  var label = _ref.label,
    orientation = _ref.orientation,
    itemCount = _ref.itemCount,
    items = _ref.items;
  var instanceId = (0,lodash.uniqueId)('woocommerce-summary-helptext-');
  var hasItemsClass = getHasItemsClass(itemCount);
  var classes = classnames_default()('woocommerce-summary', (0,defineProperty/* default */.A)({}, hasItemsClass, orientation === 'horizontal'));
  return (0,react.createElement)(menu/* default */.A, {
    "aria-label": label,
    "aria-describedby": instanceId,
    orientation: orientation,
    stopNavigationEvents: true
  }, (0,react.createElement)("p", {
    id: instanceId,
    className: "screen-reader-text"
  }, (0,build_module.__)('List of data points available for filtering. Use arrow keys to cycle through ' + 'the list. Click a data point for a detailed report.', 'woocommerce')), (0,react.createElement)("ul", {
    className: classes
  }, items));
};
Menu.propTypes = {
  /**
   * An optional label of this group, read to screen reader users.
   */
  label: (prop_types_default()).string,
  /**
   * Item layout orientation.
   */
  orientation: prop_types_default().oneOf(['vertical', 'horizontal']).isRequired,
  /**
   * A list of `<SummaryNumber />`s.
   */
  items: (prop_types_default()).node.isRequired,
  /**
   * Number of items.
   */
  itemCount: (prop_types_default()).number.isRequired
};
/* harmony default export */ const summary_menu = (Menu);
;// CONCATENATED MODULE: ../../packages/js/components/src/summary/index.js


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
var SummaryList = function SummaryList(_ref) {
  var children = _ref.children,
    isDropdownBreakpoint = _ref.isDropdownBreakpoint,
    _ref$label = _ref.label,
    label = _ref$label === void 0 ? (0,build_module.__)('Performance Indicators', 'woocommerce') : _ref$label;
  var items = children({});
  // We default to "one" because we can't have empty children.
  var itemCount = react.Children.count(items) || 1;
  var orientation = isDropdownBreakpoint ? 'vertical' : 'horizontal';
  var summaryMenu = (0,react.createElement)(summary_menu, {
    label: label,
    orientation: orientation,
    itemCount: itemCount,
    items: items
  });

  // On large screens, or if there are not multiple SummaryNumbers, we'll display the plain list.
  if (!isDropdownBreakpoint || itemCount < 2) {
    return summaryMenu;
  }
  var selected = items.find(function (item) {
    return !!item.props.selected;
  });
  if (!selected) {
    return summaryMenu;
  }
  return (0,react.createElement)(dropdown/* default */.A, {
    className: "woocommerce-summary",
    popoverProps: {
      placement: 'bottom'
    },
    headerTitle: label,
    renderToggle: function renderToggle(_ref2) {
      var isOpen = _ref2.isOpen,
        onToggle = _ref2.onToggle;
      return (0,react.cloneElement)(selected, {
        onToggle: onToggle,
        isOpen: isOpen
      });
    },
    renderContent: function renderContent(renderContentArgs) {
      return (0,react.createElement)(summary_menu, {
        label: label,
        orientation: orientation,
        itemCount: itemCount,
        items: children(renderContentArgs)
      });
    }
  });
};
SummaryList.propTypes = {
  /**
   * A function returning a list of `<SummaryNumber />`s
   */
  children: (prop_types_default()).func.isRequired,
  /**
   * An optional label of this group, read to screen reader users.
   */
  label: (prop_types_default()).string
};
/* harmony default export */ const summary = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isDropdownBreakpoint: '< large'
})(SummaryList));
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js + 1 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/info.js
var info = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/info.js");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental.js
var experimental = __webpack_require__("../../packages/js/components/src/experimental.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/summary/number.js

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
var SummaryNumber = function SummaryNumber(_ref) {
  var children = _ref.children,
    delta = _ref.delta,
    _ref$href = _ref.href,
    href = _ref$href === void 0 ? '' : _ref$href,
    _ref$hrefType = _ref.hrefType,
    hrefType = _ref$hrefType === void 0 ? 'wc-admin' : _ref$hrefType,
    _ref$isOpen = _ref.isOpen,
    isOpen = _ref$isOpen === void 0 ? false : _ref$isOpen,
    label = _ref.label,
    labelTooltipText = _ref.labelTooltipText,
    onToggle = _ref.onToggle,
    _ref$prevLabel = _ref.prevLabel,
    prevLabel = _ref$prevLabel === void 0 ? (0,build_module.__)('Previous period:', 'woocommerce') : _ref$prevLabel,
    prevValue = _ref.prevValue,
    _ref$reverseTrend = _ref.reverseTrend,
    reverseTrend = _ref$reverseTrend === void 0 ? false : _ref$reverseTrend,
    _ref$selected = _ref.selected,
    selected = _ref$selected === void 0 ? false : _ref$selected,
    value = _ref.value,
    _ref$onLinkClickCallb = _ref.onLinkClickCallback,
    onLinkClickCallback = _ref$onLinkClickCallb === void 0 ? lodash.noop : _ref$onLinkClickCallb;
  var liClasses = classnames_default()('woocommerce-summary__item-container', {
    'is-dropdown-button': onToggle,
    'is-dropdown-expanded': isOpen
  });
  var classes = classnames_default()('woocommerce-summary__item', {
    'is-selected': selected,
    'is-good-trend': reverseTrend ? delta < 0 : delta > 0,
    'is-bad-trend': reverseTrend ? delta > 0 : delta < 0
  });
  var screenReaderLabel = delta > 0 ?
  // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
  (0,build_module/* sprintf */.nv)( /* translators: percentage change upwards */
  (0,build_module.__)('Up %f%% from %s', 'woocommerce'), delta, prevLabel) :
  // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
  (0,build_module/* sprintf */.nv)( /* translators: percentage change downwards */
  (0,build_module.__)('Down %f%% from %s', 'woocommerce'), Math.abs(delta), prevLabel);
  if (!delta) {
    screenReaderLabel = (0,build_module/* sprintf */.nv)( /* translators: previous value */
    (0,build_module.__)('No change from %s', 'woocommerce'), prevLabel);
  }
  var Container;
  var containerProps = {
    className: classes,
    'aria-current': selected ? 'page' : null
  };
  if (onToggle || href) {
    var isButton = !!onToggle;
    Container = isButton ? build_module_button/* default */.A : src_link/* default */.A;
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
  return (0,react.createElement)("li", {
    className: liClasses
  }, (0,react.createElement)(Container, containerProps, (0,react.createElement)("div", {
    className: "woocommerce-summary__item-label"
  }, (0,react.createElement)(experimental/* Text */.E, {
    variant: "body.small",
    size: "14",
    lineHeight: "20px"
  }, label), labelTooltipText && (0,react.createElement)(tooltip/* default */.A, {
    text: labelTooltipText,
    position: "top center"
  }, (0,react.createElement)("div", {
    className: "woocommerce-summary__info-tooltip"
  }, (0,react.createElement)(icon/* default */.A, {
    width: 20,
    height: 20,
    icon: info/* default */.A
  })))), (0,react.createElement)("div", {
    className: "woocommerce-summary__item-data"
  }, (0,react.createElement)("div", {
    className: "woocommerce-summary__item-value"
  }, (0,react.createElement)(experimental/* Text */.E, {
    variant: "title.small",
    size: "20",
    lineHeight: "28px"
  }, !(0,lodash.isNil)(value) ? value : (0,build_module.__)('N/A', 'woocommerce'))), (0,react.createElement)(tooltip/* default */.A, {
    text: !(0,lodash.isNil)(prevValue) ? "".concat(prevLabel, " ").concat(prevValue) : (0,build_module.__)('N/A', 'woocommerce'),
    position: "top center"
  }, (0,react.createElement)("div", {
    className: "woocommerce-summary__item-delta",
    role: "presentation",
    "aria-label": screenReaderLabel
  }, (0,react.createElement)(experimental/* Text */.E, {
    variant: "caption",
    size: "12",
    lineHeight: "16px"
  }, !(0,lodash.isNil)(delta) ?
  // eslint-disable-next-line @wordpress/valid-sprintf -- false positive from %%
  (0,build_module/* sprintf */.nv)( /* translators: percentage change */
  (0,build_module.__)('%f%%', 'woocommerce'), delta) : (0,build_module.__)('N/A', 'woocommerce'))))), onToggle ? (0,react.createElement)(chevron_down/* default */.A, {
    className: "woocommerce-summary__toggle",
    size: 24
  }) : null, children));
};
SummaryNumber.propTypes = {
  /**
   * A number to represent the percentage change since the last comparison period - positive numbers will show
   * a green up arrow, negative numbers will show a red down arrow, and zero will show a flat right arrow.
   * If omitted, no change value will display.
   */
  delta: (prop_types_default()).number,
  /**
   * An internal link to the report focused on this number.
   */
  href: (prop_types_default()).string,
  /**
   * The type of the link
   */
  hrefType: prop_types_default().oneOf(['wp-admin', 'wc-admin', 'external']),
  /**
   * Boolean describing whether the menu list is open. Only applies in mobile view,
   * and only applies to the toggle-able item (first in the list).
   */
  isOpen: (prop_types_default()).bool,
  /**
   * A string description of this value, ex "Revenue", or "New Customers"
   */
  label: (prop_types_default()).string.isRequired,
  /**
   * A string that will displayed via a Tooltip next to the label
   */
  labelTooltipText: (prop_types_default()).string,
  /**
   * A function used to switch the given SummaryNumber to a button, and called on click.
   */
  onToggle: (prop_types_default()).func,
  /**
   * A string description of the previous value's timeframe, ex "Previous year:".
   */
  prevLabel: (prop_types_default()).string,
  /**
   * A string or number value to display - a string is allowed so we can accept currency formatting.
   * If omitted, this section won't display.
   */
  prevValue: prop_types_default().oneOfType([(prop_types_default()).number, (prop_types_default()).string]),
  /**
   * A boolean used to indicate that a negative delta is "good", and should be styled like a positive (and vice-versa).
   */
  reverseTrend: (prop_types_default()).bool,
  /**
   * A boolean used to show a highlight style on this number.
   */
  selected: (prop_types_default()).bool,
  /**
   * A string or number value to display - a string is allowed so we can accept currency formatting.
   */
  value: prop_types_default().oneOfType([(prop_types_default()).number, (prop_types_default()).string]),
  /**
   * A function to be called after a SummaryNumber, rendered as a link, is clicked.
   */
  onLinkClickCallback: (prop_types_default()).func
};
/* harmony default export */ const number = (SummaryNumber);
;// CONCATENATED MODULE: ../../packages/js/components/src/summary/stories/summary.story.js

/**
 * External dependencies
 */

var Basic = function Basic() {
  return (0,react.createElement)(summary, null, function () {
    return [(0,react.createElement)(number, {
      key: "revenue",
      value: '$829.40',
      label: "Total sales",
      delta: 29,
      href: "/analytics/report"
    }, (0,react.createElement)("span", null, "27 orders")), (0,react.createElement)(number, {
      key: "refunds",
      value: '$24.00',
      label: "Refunds",
      delta: -10.12,
      href: "/analytics/report",
      selected: true
    }), (0,react.createElement)(number, {
      key: "coupons",
      value: '$49.90',
      label: "Coupons",
      href: "/analytics/report"
    })];
  });
};
/* harmony default export */ const summary_story = ({
  title: 'WooCommerce Admin/components/SummaryList',
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

/***/ })

}]);