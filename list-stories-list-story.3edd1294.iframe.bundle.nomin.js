(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7860,2491],{

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "../../node_modules/.pnpm/memoizerific@1.11.3/node_modules/memoizerific sync recursive":
/***/ ((module) => {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/.pnpm/memoizerific@1.11.3/node_modules/memoizerific sync recursive";
module.exports = webpackEmptyContext;

/***/ }),

/***/ "../../packages/js/components/src/list/stories/list.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  BeforeAndAfter: () => (/* binding */ BeforeAndAfter),
  CustomStyleAndTags: () => (/* binding */ CustomStyleAndTags),
  Default: () => (/* binding */ Default),
  "default": () => (/* binding */ list_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-console@1.2.3_@storybook+addon-actions@7.6.19/node_modules/@storybook/addon-console/dist/index.js
var addon_console_dist = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-console@1.2.3_@storybook+addon-actions@7.6.19/node_modules/@storybook/addon-console/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-docs@7.6.19_@types+react-dom@17.0.25_@types+react@17.0.71_encoding@0.1.13_re_j5ticmbniq4dpeaytax4zse4vy/node_modules/@storybook/addon-docs/dist/index.mjs + 3 modules
var addon_docs_dist = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-docs@7.6.19_@types+react-dom@17.0.25_@types+react@17.0.71_encoding@0.1.13_re_j5ticmbniq4dpeaytax4zse4vy/node_modules/@storybook/addon-docs/dist/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-links@7.6.19_react@17.0.2/node_modules/@storybook/addon-links/dist/index.mjs + 1 modules
var addon_links_dist = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@7.6.19_react@17.0.2/node_modules/@storybook/addon-links/dist/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js + 1 modules
var TransitionGroup = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/CSSTransition.js + 3 modules
var CSSTransition = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4.5_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-transition-group/esm/CSSTransition.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var keycodes_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/list/list-item.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

function handleKeyDown(event, onClick) {
  if (typeof onClick === 'function' && event.keyCode === keycodes_build_module/* ENTER */.Fm) {
    onClick();
  }
}
function getItemLinkType(item) {
  var href = item.href,
    linkType = item.linkType;
  if (linkType) {
    return linkType;
  }
  return href ? 'external' : null;
}

/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list item
 */
function ListItem(props) {
  var item = props.item;
  var before = item.before,
    title = item.title,
    after = item.after,
    content = item.content,
    onClick = item.onClick,
    href = item.href,
    target = item.target,
    listItemTag = item.listItemTag;
  var hasAction = typeof onClick === 'function' || href;
  var InnerTag = href ? src_link/* default */.A : 'div';
  var innerTagProps = {
    className: 'woocommerce-list__item-inner',
    onClick: typeof onClick === 'function' ? onClick : null,
    'aria-disabled': hasAction ? 'false' : null,
    tabIndex: hasAction ? '0' : null,
    role: hasAction ? 'menuitem' : null,
    onKeyDown: function onKeyDown(e) {
      return hasAction ? handleKeyDown(e, onClick) : null;
    },
    target: href ? target : null,
    type: getItemLinkType(item),
    href: href,
    'data-list-item-tag': listItemTag
  };
  return (0,react.createElement)(InnerTag, innerTagProps, before && (0,react.createElement)("div", {
    className: "woocommerce-list__item-before"
  }, before), (0,react.createElement)("div", {
    className: "woocommerce-list__item-text"
  }, (0,react.createElement)("span", {
    className: "woocommerce-list__item-title"
  }, title), content && (0,react.createElement)("span", {
    className: "woocommerce-list__item-content"
  }, content)), after && (0,react.createElement)("div", {
    className: "woocommerce-list__item-after"
  }, after));
}
ListItem.propTypes = {
  /**
   * An array of list items.
   */
  item: prop_types_default().shape({
    /**
     * Content displayed after the list item text.
     */
    after: (prop_types_default()).node,
    /**
     * Content displayed before the list item text.
     */
    before: (prop_types_default()).node,
    /**
     * Additional class name to style the list item.
     */
    className: (prop_types_default()).string,
    /**
     * Content displayed beneath the list item title.
     */
    content: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).node]),
    /**
     * Href attribute used in a Link wrapped around the item.
     */
    href: (prop_types_default()).string,
    /**
     * Called when the list item is clicked.
     */
    onClick: (prop_types_default()).func,
    /**
     * Target attribute used for Link wrapper.
     */
    target: (prop_types_default()).string,
    /**
     * Title displayed for the list item.
     */
    title: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).node])
  }).isRequired
};
/* harmony default export */ const list_item = (ListItem);
;// CONCATENATED MODULE: ../../packages/js/components/src/list/index.js

/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list
 */
function List(props) {
  var className = props.className,
    items = props.items,
    children = props.children;
  var listClassName = classnames_default()('woocommerce-list', className);
  (0,build_module/* default */.A)('List with items prop is deprecated', {
    version: '9.0.0',
    hint: 'See ExperimentalList / ExperimentalListItem for the new API that will replace this component in future versions.'
  });
  return (0,react.createElement)(TransitionGroup/* default */.A, {
    component: "ul",
    className: listClassName,
    role: "menu"
  }, items.map(function (item, index) {
    var itemClasses = item.className,
      href = item.href,
      key = item.key,
      onClick = item.onClick;
    var hasAction = typeof onClick === 'function' || href;
    var itemClassName = classnames_default()('woocommerce-list__item', itemClasses, {
      'has-action': hasAction
    });
    return (0,react.createElement)(CSSTransition/* default */.A, {
      key: key || index,
      timeout: 500,
      classNames: "woocommerce-list__item"
    }, (0,react.createElement)("li", {
      className: itemClassName
    }, children ? children(item, index) : (0,react.createElement)(list_item, {
      item: item
    })));
  }));
}
List.propTypes = {
  /**
   * Additional class name to style the component.
   */
  className: (prop_types_default()).string,
  /**
   * An array of list items.
   */
  items: prop_types_default().arrayOf(prop_types_default().shape({
    /**
     * Content displayed after the list item text.
     */
    after: (prop_types_default()).node,
    /**
     * Content displayed before the list item text.
     */
    before: (prop_types_default()).node,
    /**
     * Additional class name to style the list item.
     */
    className: (prop_types_default()).string,
    /**
     * Content displayed beneath the list item title.
     */
    content: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).node]),
    /**
     * Href attribute used in a Link wrapped around the item.
     */
    href: (prop_types_default()).string,
    /**
     * Called when the list item is clicked.
     */
    onClick: (prop_types_default()).func,
    /**
     * Target attribute used for Link wrapper.
     */
    target: (prop_types_default()).string,
    /**
     * Title displayed for the list item.
     */
    title: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).node]),
    /**
     * Unique key for list item.
     */
    key: (prop_types_default()).string
  }))
};
/* harmony default export */ const list = (List);
;// CONCATENATED MODULE: ../../packages/js/components/src/list/stories/style.scss
// extracted by mini-css-extract-plugin

;// CONCATENATED MODULE: ../../packages/js/components/src/list/stories/list.story.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


function logItemClick(event) {
  var a = event.currentTarget;
  var itemDescription = a.href ? "[".concat(a.textContent, "](").concat(a.href, ") ").concat(a.dataset.linkType) : "[".concat(a.textContent, "]");
  var itemTag = a.dataset.listItemTag ? "'".concat(a.dataset.listItemTag, "'") : 'not set';
  var logMessage = "[".concat(itemDescription, " item clicked (tag: ").concat(itemTag, ")");

  // eslint-disable-next-line no-console
  console.log(logMessage);
  event.preventDefault();
  return false;
}
/* harmony default export */ const list_story = ({
  title: 'WooCommerce Admin/components/List',
  component: list,
  decorators: [function (storyFn, context) {
    return (0,addon_console_dist/* withConsole */.QW)()(storyFn)(context);
  }, addon_links_dist/* withLinks */.q9],
  parameters: {
    docs: {
      page: function page() {
        return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(addon_docs_dist/* Title */.hE, null), (0,react.createElement)(addon_docs_dist/* Subtitle */.Pd, null), (0,react.createElement)(addon_docs_dist/* Description */.VY, {
          markdown: "[deprecated] and will be replaced by\n\t\t\t\t\t\t<a\n\t\t\t\t\t\t\tdata-sb-kind=\"woocommerce-admin-experimental-list\"\n\t\t\t\t\t\t\tdata-sb-story=\"default\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\tExperimentalList\n\t\t\t\t\t\t</a>"
        }), (0,react.createElement)(addon_docs_dist/* Primary */.Tn, null), (0,react.createElement)(addon_docs_dist/* ArgsTable */.uY, {
          story: addon_docs_dist/* PRIMARY_STORY */.h1
        }), (0,react.createElement)(addon_docs_dist/* Stories */.om, null));
      }
    }
  }
});
var Default = function Default() {
  var listItems = [{
    title: 'WooCommerce.com',
    href: 'https://woocommerce.com',
    onClick: logItemClick
  }, {
    title: 'WordPress.org',
    href: 'https://wordpress.org',
    onClick: logItemClick
  }, {
    title: 'A list item with no action'
  }, {
    title: 'Click me!',
    content: 'An alert will be triggered.',
    onClick: function onClick(event) {
      // eslint-disable-next-line no-alert
      window.alert('List item clicked');
      return logItemClick(event);
    }
  }];
  return (0,react.createElement)(list, {
    items: listItems
  });
};
Default.storyName = 'Default (deprecated)';
var BeforeAndAfter = function BeforeAndAfter() {
  var listItems = [{
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "cart"
    }),
    after: (0,react.createElement)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WooCommerce.com',
    href: 'https://woocommerce.com',
    onClick: logItemClick
  }, {
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "my-sites"
    }),
    after: (0,react.createElement)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WordPress.org',
    href: 'https://wordpress.org',
    onClick: logItemClick
  }, {
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "link-break"
    }),
    title: 'A list item with no action',
    description: 'List item description text'
  }, {
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "notice"
    }),
    title: 'Click me!',
    content: 'An alert will be triggered.',
    onClick: function onClick(event) {
      // eslint-disable-next-line no-alert
      window.alert('List item clicked');
      return logItemClick(event);
    }
  }];
  return (0,react.createElement)(list, {
    items: listItems
  });
};
BeforeAndAfter.storyName = 'Before and after (deprecated)';
var CustomStyleAndTags = function CustomStyleAndTags() {
  var listItems = [{
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "cart"
    }),
    after: (0,react.createElement)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WooCommerce.com',
    href: 'https://woocommerce.com',
    onClick: logItemClick,
    listItemTag: 'woo.com-link'
  }, {
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "my-sites"
    }),
    after: (0,react.createElement)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WordPress.org',
    href: 'https://wordpress.org',
    onClick: logItemClick,
    listItemTag: 'wordpress.org-link'
  }, {
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "link-break"
    }),
    title: 'A list item with no action'
  }, {
    before: (0,react.createElement)(dist/* default */.A, {
      icon: "notice"
    }),
    title: 'Click me!',
    content: 'An alert will be triggered.',
    onClick: function onClick(event) {
      // eslint-disable-next-line no-alert
      window.alert('List item clicked');
      return logItemClick(event);
    },
    listItemTag: 'click-me'
  }];
  return (0,react.createElement)(list, {
    items: listItems,
    className: "storybook-custom-list"
  });
};
CustomStyleAndTags.storyName = 'Custom style and tags (deprecated)';
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "() => {\n  const listItems = [{\n    title: 'WooCommerce.com',\n    href: 'https://woocommerce.com',\n    onClick: logItemClick\n  }, {\n    title: 'WordPress.org',\n    href: 'https://wordpress.org',\n    onClick: logItemClick\n  }, {\n    title: 'A list item with no action'\n  }, {\n    title: 'Click me!',\n    content: 'An alert will be triggered.',\n    onClick: event => {\n      // eslint-disable-next-line no-alert\n      window.alert('List item clicked');\n      return logItemClick(event);\n    }\n  }];\n  return <List items={listItems} />;\n}",
      ...Default.parameters?.docs?.source
    }
  }
};
BeforeAndAfter.parameters = {
  ...BeforeAndAfter.parameters,
  docs: {
    ...BeforeAndAfter.parameters?.docs,
    source: {
      originalSource: "() => {\n  const listItems = [{\n    before: <Gridicon icon=\"cart\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WooCommerce.com',\n    href: 'https://woocommerce.com',\n    onClick: logItemClick\n  }, {\n    before: <Gridicon icon=\"my-sites\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WordPress.org',\n    href: 'https://wordpress.org',\n    onClick: logItemClick\n  }, {\n    before: <Gridicon icon=\"link-break\" />,\n    title: 'A list item with no action',\n    description: 'List item description text'\n  }, {\n    before: <Gridicon icon=\"notice\" />,\n    title: 'Click me!',\n    content: 'An alert will be triggered.',\n    onClick: event => {\n      // eslint-disable-next-line no-alert\n      window.alert('List item clicked');\n      return logItemClick(event);\n    }\n  }];\n  return <List items={listItems} />;\n}",
      ...BeforeAndAfter.parameters?.docs?.source
    }
  }
};
CustomStyleAndTags.parameters = {
  ...CustomStyleAndTags.parameters,
  docs: {
    ...CustomStyleAndTags.parameters?.docs,
    source: {
      originalSource: "() => {\n  const listItems = [{\n    before: <Gridicon icon=\"cart\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WooCommerce.com',\n    href: 'https://woocommerce.com',\n    onClick: logItemClick,\n    listItemTag: 'woo.com-link'\n  }, {\n    before: <Gridicon icon=\"my-sites\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WordPress.org',\n    href: 'https://wordpress.org',\n    onClick: logItemClick,\n    listItemTag: 'wordpress.org-link'\n  }, {\n    before: <Gridicon icon=\"link-break\" />,\n    title: 'A list item with no action'\n  }, {\n    before: <Gridicon icon=\"notice\" />,\n    title: 'Click me!',\n    content: 'An alert will be triggered.',\n    onClick: event => {\n      // eslint-disable-next-line no-alert\n      window.alert('List item clicked');\n      return logItemClick(event);\n    },\n    listItemTag: 'click-me'\n  }];\n  return <List items={listItems} className=\"storybook-custom-list\" />;\n}",
      ...CustomStyleAndTags.parameters?.docs?.source
    }
  }
};

/***/ })

}]);