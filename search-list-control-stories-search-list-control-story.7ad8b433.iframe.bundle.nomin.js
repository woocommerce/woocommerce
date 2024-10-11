"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5854],{

/***/ "../../packages/js/components/src/tag/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/close-small.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");


/**
 * External dependencies
 */







var Tag = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(function (_ref, removeButtonRef) {
  var id = _ref.id,
    label = _ref.label,
    popoverContents = _ref.popoverContents,
    remove = _ref.remove,
    screenReaderLabel = _ref.screenReaderLabel,
    className = _ref.className;
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(_useState, 2),
    isVisible = _useState2[0],
    setIsVisible = _useState2[1];
  var instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(Tag);
  screenReaderLabel = screenReaderLabel || label;
  if (!label) {
    // A null label probably means something went wrong
    // @todo Maybe this should be a loading indicator?
    return null;
  }
  label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__/* .decodeEntities */ .S)(label);
  var classes = classnames__WEBPACK_IMPORTED_MODULE_1___default()('woocommerce-tag', className, {
    'has-remove': !!remove
  });
  var labelId = "woocommerce-tag__label-".concat(instanceId);
  var labelTextNode = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: "screen-reader-text"
  }, screenReaderLabel), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    "aria-hidden": "true"
  }, label));
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: classes
  }, popoverContents ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    className: "woocommerce-tag__text",
    id: labelId,
    onClick: function onClick() {
      return setIsVisible(true);
    }
  }, labelTextNode) : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: "woocommerce-tag__text",
    id: labelId
  }, labelTextNode), popoverContents && isVisible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
    onClose: function onClose() {
      return setIsVisible(false);
    }
  }, popoverContents), remove && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    className: "woocommerce-tag__remove",
    ref: removeButtonRef,
    onClick: remove(id),
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(
    // translators: %s is the name of the tag being removed.
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Remove %s', 'woocommerce'), label),
    "aria-describedby": labelId
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A,
    size: 20,
    className: "clear-icon"
  })));
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tag);
try {
    // @ts-ignore
    tag.displayName = "tag";
    // @ts-ignore
    tag.__docgenInfo = { "description": "", "displayName": "tag", "props": { "label": { "defaultValue": null, "description": "The name for this item, displayed as the tag's text.", "name": "label", "required": true, "type": { "name": "string" } }, "id": { "defaultValue": null, "description": "A unique ID for this item. This is used to identify the item when the remove button is clicked.", "name": "id", "required": false, "type": { "name": "string | number" } }, "popoverContents": { "defaultValue": null, "description": "Contents to display on click in a popover", "name": "popoverContents", "required": false, "type": { "name": "ReactNode" } }, "remove": { "defaultValue": null, "description": "A function called when the remove X is clicked. If not used, no X icon will display.", "name": "remove", "required": false, "type": { "name": "((id: string | number) => MouseEventHandler<HTMLButtonElement>)" } }, "screenReaderLabel": { "defaultValue": null, "description": "A more descriptive label for screen reader users. Defaults to the `name` prop.", "name": "screenReaderLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Additional CSS classes.", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tag/index.tsx#tag"] = { docgenInfo: tag.__docgenInfo, name: "tag", path: "../../packages/js/components/src/tag/index.tsx#tag" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/search-list-control/stories/search-list-control.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ search_list_control_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js
var es_string_search = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js
var with_spoken_messages = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/notice-outline.js
var notice_outline = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/notice-outline.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
;// CONCATENATED MODULE: ../../packages/js/components/src/search-list-control/hierarchy.js


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
      (0,defineProperty/* default */.A)(e, r, t[r]);
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
 * Returns terms in a tree form.
 *
 * @param {Array} filteredList Array of terms, possibly a subset of all terms, in flat format.
 * @param {Array} list         Array of the full list of terms, defaults to the filteredList.
 *
 * @return {Array} Array of terms in tree format.
 */
function buildTermsTree(filteredList) {
  var list = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : filteredList;
  var termsByParent = (0,lodash.groupBy)(filteredList, 'parent');
  var listById = (0,lodash.keyBy)(list, 'id');
  var getParentsName = function getParentsName() {
    var term = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    if (!term.parent) {
      return term.name ? [term.name] : [];
    }
    var parentName = getParentsName(listById[term.parent]);
    return [].concat((0,toConsumableArray/* default */.A)(parentName), [term.name]);
  };
  var fillWithChildren = function fillWithChildren(terms) {
    return terms.map(function (term) {
      var children = termsByParent[term.id];
      delete termsByParent[term.id];
      return _objectSpread(_objectSpread({}, term), {}, {
        breadcrumbs: getParentsName(listById[term.parent]),
        children: children && children.length ? fillWithChildren(children) : []
      });
    });
  };
  var tree = fillWithChildren(termsByParent['0'] || []);
  delete termsByParent['0'];

  // anything left in termsByParent has no visible parent
  (0,lodash.forEach)(termsByParent, function (terms) {
    tree.push.apply(tree, (0,toConsumableArray/* default */.A)(fillWithChildren(terms || [])));
  });
  return tree;
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js
var es_string_split = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/search-list-control/item.js


var _excluded = ["countLabel", "className", "depth", "controlId", "item", "isSelected", "isSingle", "onSelect", "search"];









/**
 * External dependencies
 */



function getHighlightedName(name, search) {
  if (!search) {
    return name;
  }
  var re = new RegExp((0,lodash.escapeRegExp)(search), 'ig');
  var nameParts = name.split(re);
  return nameParts.map(function (part, i) {
    if (i === 0) {
      return part;
    }
    return (0,react.createElement)(react.Fragment, {
      key: i
    }, (0,react.createElement)("strong", null, search), part);
  });
}
function getBreadcrumbsForDisplay(breadcrumbs) {
  if (breadcrumbs.length === 1) {
    return (0,lodash.first)(breadcrumbs);
  }
  if (breadcrumbs.length === 2) {
    return (0,lodash.first)(breadcrumbs) + ' › ' + (0,lodash.last)(breadcrumbs);
  }
  return (0,lodash.first)(breadcrumbs) + ' … ' + (0,lodash.last)(breadcrumbs);
}
var SearchListItem = function SearchListItem(_ref) {
  var countLabel = _ref.countLabel,
    className = _ref.className,
    _ref$depth = _ref.depth,
    depth = _ref$depth === void 0 ? 0 : _ref$depth,
    _ref$controlId = _ref.controlId,
    controlId = _ref$controlId === void 0 ? '' : _ref$controlId,
    item = _ref.item,
    isSelected = _ref.isSelected,
    isSingle = _ref.isSingle,
    onSelect = _ref.onSelect,
    _ref$search = _ref.search,
    search = _ref$search === void 0 ? '' : _ref$search,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  var showCount = !(0,lodash.isNil)(countLabel) || !(0,lodash.isNil)(item.count);
  var classes = [className, 'woocommerce-search-list__item'];
  classes.push("depth-".concat(depth));
  if (isSingle) {
    classes.push('is-radio-button');
  }
  if (showCount) {
    classes.push('has-count');
  }
  var hasBreadcrumbs = item.breadcrumbs && item.breadcrumbs.length;
  var name = props.name || "search-list-item-".concat(controlId);
  var id = "".concat(name, "-").concat(item.id);
  return (0,react.createElement)("label", {
    htmlFor: id,
    className: classes.join(' ')
  }, isSingle ? (0,react.createElement)("input", (0,esm_extends/* default */.A)({
    type: "radio",
    id: id,
    name: name,
    value: item.value,
    onChange: onSelect(item),
    checked: isSelected,
    className: "woocommerce-search-list__item-input"
  }, props)) : (0,react.createElement)("input", (0,esm_extends/* default */.A)({
    type: "checkbox",
    id: id,
    name: name,
    value: item.value,
    onChange: onSelect(item),
    checked: isSelected,
    className: "woocommerce-search-list__item-input"
  }, props)), (0,react.createElement)("span", {
    className: "woocommerce-search-list__item-label"
  }, hasBreadcrumbs ? (0,react.createElement)("span", {
    className: "woocommerce-search-list__item-prefix"
  }, getBreadcrumbsForDisplay(item.breadcrumbs)) : null, (0,react.createElement)("span", {
    className: "woocommerce-search-list__item-name"
  }, getHighlightedName(item.name, search))), !!showCount && (0,react.createElement)("span", {
    className: "woocommerce-search-list__item-count"
  }, countLabel || item.count));
};
SearchListItem.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * Label to display in the count bubble. Takes preference over `item.count`.
   */
  countLabel: (prop_types_default()).node,
  /**
   * Unique id of the parent control.
   */
  controlId: (prop_types_default()).node,
  /**
   * Depth, non-zero if the list is hierarchical.
   */
  depth: (prop_types_default()).number,
  /**
   * Current item to display.
   */
  item: (prop_types_default()).object,
  /**
   * Name of the inputs. Used to group input controls together. See:
   * https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-name
   * If not provided, a default name will be generated using the controlId.
   */
  name: (prop_types_default()).string,
  /**
   * Whether this item is selected.
   */
  isSelected: (prop_types_default()).bool,
  /**
   * Whether this should only display a single item (controls radio vs checkbox icon).
   */
  isSingle: (prop_types_default()).bool,
  /**
   * Callback for selecting the item.
   */
  onSelect: (prop_types_default()).func,
  /**
   * Search string, used to highlight the substring in the item name.
   */
  search: (prop_types_default()).string
};
/* harmony default export */ const item = (SearchListItem);
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/search-list-control/index.js











function search_list_control_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function search_list_control_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? search_list_control_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : search_list_control_ownKeys(Object(t)).forEach(function (r) {
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



var defaultMessages = {
  clear: (0,build_module.__)('Clear all selected items', 'woocommerce'),
  noItems: (0,build_module.__)('No items found.', 'woocommerce'),
  /* translators: %s: search term */
  noResults: (0,build_module.__)('No results for %s', 'woocommerce'),
  search: (0,build_module.__)('Search for items', 'woocommerce'),
  selected: function selected(n) {
    return (0,build_module/* sprintf */.nv)( /* translators: Number of items selected from list. */
    (0,build_module._n)('%d item selected', '%d items selected', n, 'woocommerce'), n);
  },
  updated: (0,build_module.__)('Search results updated.', 'woocommerce')
};

/**
 * Component to display a searchable, selectable list of items.
 *
 * @param {Object} props
 */
var SearchListControl = function SearchListControl(props) {
  var _useState = (0,react.useState)(props.search || ''),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    searchValue = _useState2[0],
    setSearchValue = _useState2[1];
  var isSingle = props.isSingle,
    isLoading = props.isLoading,
    onChange = props.onChange,
    selected = props.selected,
    instanceId = props.instanceId,
    propsMessages = props.messages,
    isCompact = props.isCompact,
    debouncedSpeak = props.debouncedSpeak,
    onSearch = props.onSearch,
    _props$className = props.className,
    className = _props$className === void 0 ? '' : _props$className;
  var messages = search_list_control_objectSpread(search_list_control_objectSpread({}, defaultMessages), propsMessages);
  (0,react.useEffect)(function () {
    if (typeof onSearch === 'function') {
      onSearch(searchValue);
    }
  }, [onSearch, searchValue]);
  var onRemove = function onRemove(id) {
    return function () {
      if (isSingle) {
        onChange([]);
      }
      var i = (0,lodash.findIndex)(selected, {
        id: id
      });
      onChange([].concat((0,toConsumableArray/* default */.A)(selected.slice(0, i)), (0,toConsumableArray/* default */.A)(selected.slice(i + 1))));
    };
  };
  var isSelected = function isSelected(item) {
    return (0,lodash.findIndex)(selected, {
      id: item.id
    }) !== -1;
  };
  var getFilteredList = function getFilteredList(list, search) {
    var isHierarchical = props.isHierarchical;
    if (!search) {
      return isHierarchical ? buildTermsTree(list) : list;
    }
    var re = new RegExp((0,lodash.escapeRegExp)(search), 'i');
    debouncedSpeak(messages.updated);
    var filteredList = list.map(function (item) {
      return re.test(item.name) ? item : false;
    }).filter(Boolean);
    return isHierarchical ? buildTermsTree(filteredList, list) : filteredList;
  };
  var onSelect = function onSelect(item) {
    return function () {
      if (isSelected(item)) {
        onRemove(item.id)();
        return;
      }
      if (isSingle) {
        onChange([item]);
      } else {
        onChange([].concat((0,toConsumableArray/* default */.A)(selected), [item]));
      }
    };
  };
  var defaultRenderItem = function defaultRenderItem(args) {
    return (0,react.createElement)(item, args);
  };
  var renderList = function renderList(list) {
    var depth = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
    var renderItem = props.renderItem || defaultRenderItem;
    if (!list) {
      return null;
    }
    return list.map(function (item) {
      return (0,react.createElement)(react.Fragment, {
        key: item.id
      }, (0,react.createElement)("li", null, renderItem({
        item: item,
        isSelected: isSelected(item),
        onSelect: onSelect,
        isSingle: isSingle,
        search: searchValue,
        depth: depth,
        controlId: instanceId
      })), renderList(item.children, depth + 1));
    });
  };
  var renderListSection = function renderListSection() {
    if (isLoading) {
      return (0,react.createElement)("div", {
        className: "woocommerce-search-list__list is-loading"
      }, (0,react.createElement)(spinner/* default */.A, null));
    }
    var list = getFilteredList(props.list, searchValue);
    if (!list.length) {
      return (0,react.createElement)("div", {
        className: "woocommerce-search-list__list is-not-found"
      }, (0,react.createElement)("span", {
        className: "woocommerce-search-list__not-found-icon"
      }, (0,react.createElement)(notice_outline/* default */.A, {
        role: "img",
        "aria-hidden": "true",
        focusable: "false"
      })), (0,react.createElement)("span", {
        className: "woocommerce-search-list__not-found-text"
      }, searchValue ?
      // eslint-disable-next-line @wordpress/valid-sprintf
      (0,build_module/* sprintf */.nv)(messages.noResults, searchValue) : messages.noItems));
    }
    return (0,react.createElement)("ul", {
      className: "woocommerce-search-list__list"
    }, renderList(list));
  };
  var renderSelectedSection = function renderSelectedSection() {
    if (isLoading || isSingle || !selected) {
      return null;
    }
    var selectedCount = selected.length;
    return (0,react.createElement)("div", {
      className: "woocommerce-search-list__selected"
    }, (0,react.createElement)("div", {
      className: "woocommerce-search-list__selected-header"
    }, (0,react.createElement)("strong", null, messages.selected(selectedCount)), selectedCount > 0 ? (0,react.createElement)(build_module_button/* default */.A, {
      isLink: true,
      isDestructive: true,
      onClick: onChange([]),
      "aria-label": messages.clear
    }, (0,build_module.__)('Clear all', 'woocommerce')) : null), selectedCount > 0 ? (0,react.createElement)("ul", null, selected.map(function (item, i) {
      return (0,react.createElement)("li", {
        key: i
      }, (0,react.createElement)(tag/* default */.A, {
        label: item.name,
        id: item.id,
        remove: onRemove
      }));
    })) : null);
  };
  return (0,react.createElement)("div", {
    className: classnames_default()('woocommerce-search-list', className, {
      'is-compact': isCompact
    })
  }, renderSelectedSection(), (0,react.createElement)("div", {
    className: "woocommerce-search-list__search"
  }, (0,react.createElement)(text_control/* default */.A, {
    label: messages.search,
    type: "search",
    value: searchValue,
    onChange: function onChange(value) {
      return setSearchValue(value);
    }
  })), renderListSection());
};
SearchListControl.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * Whether it should be displayed in a compact way, so it occupies less space.
   */
  isCompact: (prop_types_default()).bool,
  /**
   * Whether the list of items is hierarchical or not. If true, each list item is expected to
   * have a parent property.
   */
  isHierarchical: (prop_types_default()).bool,
  /**
   * Whether the list of items is still loading.
   */
  isLoading: (prop_types_default()).bool,
  /**
   * Restrict selections to one item.
   */
  isSingle: (prop_types_default()).bool,
  /**
   * A complete list of item objects, each with id, name properties. This is displayed as a
   * clickable/keyboard-able list, and possibly filtered by the search term (searches name).
   */
  list: prop_types_default().arrayOf(prop_types_default().shape({
    id: (prop_types_default()).number,
    name: (prop_types_default()).string
  })),
  /**
   * Messages displayed or read to the user. Configure these to reflect your object type.
   * See `defaultMessages` above for examples.
   */
  messages: prop_types_default().shape({
    /**
     * A more detailed label for the "Clear all" button, read to screen reader users.
     */
    clear: (prop_types_default()).string,
    /**
     * Message to display when the list is empty (implies nothing loaded from the server
     * or parent component).
     */
    noItems: (prop_types_default()).string,
    /**
     * Message to display when no matching results are found. %s is the search term.
     */
    noResults: (prop_types_default()).string,
    /**
     * Label for the search input
     */
    search: (prop_types_default()).string,
    /**
     * Label for the selected items. This is actually a function, so that we can pass
     * through the count of currently selected items.
     */
    selected: (prop_types_default()).func,
    /**
     * Label indicating that search results have changed, read to screen reader users.
     */
    updated: (prop_types_default()).string
  }),
  /**
   * Callback fired when selected items change, whether added, cleared, or removed.
   * Passed an array of item objects (as passed in via props.list).
   */
  onChange: (prop_types_default()).func.isRequired,
  /**
   * Callback fired when the search field is used.
   */
  onSearch: (prop_types_default()).func,
  /**
   * Callback to render each item in the selection list, allows any custom object-type rendering.
   */
  renderItem: (prop_types_default()).func,
  /**
   * The list of currently selected items.
   */
  selected: (prop_types_default()).array.isRequired,
  // from withSpokenMessages
  debouncedSpeak: (prop_types_default()).func,
  // from withInstanceId
  instanceId: (prop_types_default()).number
};
/* harmony default export */ const search_list_control = ((0,compose/* default */.A)([with_spoken_messages/* default */.A, with_instance_id/* default */.A])(SearchListControl));
;// CONCATENATED MODULE: ../../packages/js/components/src/search-list-control/stories/search-list-control.story.js



function search_list_control_story_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function search_list_control_story_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? search_list_control_story_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : search_list_control_story_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}











/**
 * External dependencies
 */


var SearchListControlExample = function SearchListControlExample(_ref) {
  var showCount = _ref.showCount,
    isCompact = _ref.isCompact,
    isSingle = _ref.isSingle;
  var _useState = (0,react.useState)([]),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    selected = _useState2[0],
    setSelected = _useState2[1];
  var _useState3 = (0,react.useState)(false),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    loading = _useState4[0],
    setLoading = _useState4[1];
  var list = [{
    id: 1,
    name: 'Apricots'
  }, {
    id: 2,
    name: 'Clementine'
  }, {
    id: 3,
    name: 'Elderberry'
  }, {
    id: 4,
    name: 'Guava'
  }, {
    id: 5,
    name: 'Lychee'
  }, {
    id: 6,
    name: 'Mulberry'
  }];
  var counts = [3, 1, 1, 5, 2, 0];
  if (showCount) {
    list = list.map(function (item, i) {
      return search_list_control_story_objectSpread(search_list_control_story_objectSpread({}, item), {}, {
        count: counts[i]
      });
    });
  }
  return (0,react.createElement)("div", null, (0,react.createElement)("button", {
    onClick: function onClick() {
      return setLoading(!loading);
    }
  }, "Toggle loading state"), (0,react.createElement)(search_list_control, {
    list: list,
    isCompact: isCompact,
    isLoading: loading,
    selected: selected,
    onChange: function onChange(items) {
      return setSelected(items);
    },
    isSingle: isSingle
  }));
};
var Basic = function Basic(args) {
  return (0,react.createElement)(SearchListControlExample, args);
};
/* harmony default export */ const search_list_control_story = ({
  title: 'WooCommerce Admin/components/SearchListControl',
  component: search_list_control,
  args: {
    showCount: false,
    isCompact: false,
    isSingle: false
  },
  argTypes: {
    showCount: {
      control: {
        type: 'boolean'
      }
    },
    isCompact: {
      control: {
        type: 'boolean'
      }
    },
    isSingle: {
      control: {
        type: 'boolean'
      }
    }
  }
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "args => <SearchListControlExample {...args} />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ })

}]);