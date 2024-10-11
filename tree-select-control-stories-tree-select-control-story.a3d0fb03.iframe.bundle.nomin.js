"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5826],{

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

/***/ "../../packages/js/components/src/tree-select-control/stories/tree-select-control.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Base: () => (/* binding */ Base),
  "default": () => (/* binding */ tree_select_control_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.map.js
var es_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js
var es_string_trim = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.flat-map.js
var es_array_flat_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.flat-map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.unscopables.flat-map.js
var es_array_unscopables_flat_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.unscopables.flat-map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.every.js
var es_array_every = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.every.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js + 2 modules
var dom_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@3.6.1/node_modules/@wordpress/dom/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-outside/index.js
var use_focus_outside = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-focus-outside/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/useIsEqualRefValue.js
/**
 * External dependencies
 */



/**
 * Stores value in a ref. In subsequent render, value will be compared with ref.current using `isEqual` comparison.
 * If it is equal, returns ref.current; else, set ref.current to be value.
 *
 * This is useful for objects used in hook dependencies.
 *
 * @param {*} value Value to be stored in ref.
 * @return {*} Value stored in ref.
 */
var useIsEqualRefValue = function useIsEqualRefValue(value) {
  var optionsRef = (0,react.useRef)(value);
  if (!(0,lodash.isEqual)(optionsRef.current, value)) {
    optionsRef.current = value;
  }
  return optionsRef.current;
};
/* harmony default export */ const tree_select_control_useIsEqualRefValue = (useIsEqualRefValue);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/tags.js





/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


/**
 * A list of tags to display selected items.
 *
 * @param {Object}   props                    The component props
 * @param {Object[]} [props.tags=[]]          The tags
 * @param {Function} props.onChange           The method called when a tag is removed
 * @param {boolean}  props.disabled           True if the plugin is disabled
 * @param {number}   [props.maxVisibleTags=0] The maximum number of tags to show. 0 or less than 0 evaluates to "Show All".
 */
var Tags = function Tags(_ref) {
  var _ref$tags = _ref.tags,
    tags = _ref$tags === void 0 ? [] : _ref$tags,
    disabled = _ref.disabled,
    _ref$maxVisibleTags = _ref.maxVisibleTags,
    maxVisibleTags = _ref$maxVisibleTags === void 0 ? 0 : _ref$maxVisibleTags,
    _ref$onChange = _ref.onChange,
    onChange = _ref$onChange === void 0 ? function () {} : _ref$onChange;
  var _useState = (0,react.useState)(false),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    showAll = _useState2[0],
    setShowAll = _useState2[1];
  var maxTags = Math.max(0, maxVisibleTags);
  var shouldShowAll = showAll || !maxTags;
  var visibleTags = shouldShowAll ? tags : tags.slice(0, maxTags);
  if (!tags.length) {
    return null;
  }

  /**
   * Callback to remove a Tag.
   * The function is defined this way because in the WooCommerce Tag Component the remove logic
   * is defined as `onClick={ remove(key) }` hence we need to do this to avoid calling remove function
   * on each render.
   *
   * @param {string} key The key for the Tag to be deleted
   */
  var remove = function remove(key) {
    return function () {
      if (disabled) {
        return;
      }
      onChange(tags.filter(function (tag) {
        return tag.id !== key;
      }));
    };
  };
  return (0,react.createElement)("div", {
    className: "woocommerce-tree-select-control__tags"
  }, visibleTags.map(function (item, i) {
    if (!item.label) {
      return null;
    }
    var screenReaderLabel = (0,build_module/* sprintf */.nv)(
    // translators: 1: Tag Label, 2: Current Tag index, 3: Total amount of tags.
    (0,build_module.__)('%1$s (%2$d of %3$d)', 'woocommerce'), item.label, i + 1, tags.length);
    return (0,react.createElement)(tag/* default */.A, {
      key: item.id,
      id: item.id,
      label: item.label,
      screenReaderLabel: screenReaderLabel,
      remove: remove
    });
  }), maxTags > 0 && tags.length > maxTags && (0,react.createElement)(build_module_button/* default */.A, {
    isTertiary: true,
    className: "woocommerce-tree-select-control__show-more",
    onClick: function onClick() {
      setShowAll(!showAll);
    }
  }, showAll ? (0,build_module.__)('Show less', 'woocommerce') : (0,build_module/* sprintf */.nv)(
  // translators: %d: The number of extra tags to show
  (0,build_module.__)('+ %d more', 'woocommerce'), tags.length - maxTags)));
};
/* harmony default export */ const tree_select_control_tags = (Tags);
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/constants.js
var ROOT_VALUE = '__WC_TREE_SELECT_COMPONENT_ROOT__';
var BACKSPACE = 'Backspace';
var ESCAPE = 'Escape';
var ENTER = 'Enter';
var ARROW_UP = 'ArrowUp';
var ARROW_DOWN = 'ArrowDown';
var ARROW_LEFT = 'ArrowLeft';
var ARROW_RIGHT = 'ArrowRight';
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/control.js

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



/**
 * The Control Component renders a search input and also the Tags.
 * It also triggers the setExpand for expanding the options tree on click.
 *
 * @param {Object}   props                       Component props
 * @param {Array}    props.tags                  Array of tags
 * @param {string}   props.instanceId            Id of the component
 * @param {string}   props.placeholder           Placeholder of the search input
 * @param {boolean}  props.isExpanded            True if the tree is expanded
 * @param {boolean}  props.alwaysShowPlaceholder Will always show placeholder (default: false)
 * @param {boolean}  props.disabled              True if the component is disabled
 * @param {number}   props.maxVisibleTags        The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to "Show All".
 * @param {string}   props.value                 The current input value
 * @param {Function} props.onFocus               On Focus Callback
 * @param {Function} props.onTagsChange          Callback when the Tags change
 * @param {Function} props.onInputChange         Callback when the Input value changes
 * @param {Function} [props.onControlClick]      Callback when clicking on the control.
 * @return {JSX.Element} The rendered component
 */
var Control = (0,react.forwardRef)(function (_ref, ref) {
  var _ref$tags = _ref.tags,
    tags = _ref$tags === void 0 ? [] : _ref$tags,
    instanceId = _ref.instanceId,
    placeholder = _ref.placeholder,
    isExpanded = _ref.isExpanded,
    disabled = _ref.disabled,
    maxVisibleTags = _ref.maxVisibleTags,
    _ref$value = _ref.value,
    value = _ref$value === void 0 ? '' : _ref$value,
    _ref$onFocus = _ref.onFocus,
    onFocus = _ref$onFocus === void 0 ? function () {} : _ref$onFocus,
    _ref$onTagsChange = _ref.onTagsChange,
    onTagsChange = _ref$onTagsChange === void 0 ? function () {} : _ref$onTagsChange,
    _ref$onInputChange = _ref.onInputChange,
    onInputChange = _ref$onInputChange === void 0 ? function () {} : _ref$onInputChange,
    _ref$onControlClick = _ref.onControlClick,
    onControlClick = _ref$onControlClick === void 0 ? lodash.noop : _ref$onControlClick,
    _ref$alwaysShowPlaceh = _ref.alwaysShowPlaceholder,
    alwaysShowPlaceholder = _ref$alwaysShowPlaceh === void 0 ? false : _ref$alwaysShowPlaceh;
  var hasTags = tags.length > 0;
  var showPlaceholder = alwaysShowPlaceholder ? true : !hasTags && !isExpanded;

  /**
   * Handles keydown event
   *
   * Keys:
   * When key down is BACKSPACE. Delete the last tag.
   *
   * @param {Event} event Event object
   */
  var handleKeydown = function handleKeydown(event) {
    if (BACKSPACE === event.key) {
      if (value) return;
      onTagsChange(tags.slice(0, -1));
      event.preventDefault();
    }
  };
  return (
    /**
     * ESLint Disable reason
     * https://github.com/woocommerce/woocommerce-admin/blob/main/packages/components/src/select-control/control.js#L200
     */
    /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,react.createElement)("div", {
      className: classnames_default()('components-base-control', 'woocommerce-tree-select-control__control', {
        'is-disabled': disabled,
        'has-tags': hasTags
      }),
      onClick: function onClick(e) {
        ref.current.focus();
        onControlClick(e);
      }
    }, hasTags && (0,react.createElement)(tree_select_control_tags, {
      disabled: disabled,
      tags: tags,
      maxVisibleTags: maxVisibleTags,
      onChange: onTagsChange
    }), (0,react.createElement)("div", {
      className: "components-base-control__field"
    }, (0,react.createElement)("input", {
      ref: ref,
      id: "woocommerce-tree-select-control-".concat(instanceId, "__control-input"),
      type: "search",
      placeholder: showPlaceholder ? placeholder : '',
      autoComplete: "off",
      className: "woocommerce-tree-select-control__control-input",
      role: "combobox",
      "aria-autocomplete": "list",
      value: value,
      "aria-expanded": isExpanded,
      disabled: disabled,
      onFocus: onFocus,
      onChange: onInputChange,
      onKeyDown: handleKeydown
    })))
  );
});
/* harmony default export */ const control = (Control);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js + 2 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/check.js
var check = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/check.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/checkbox.js


var _excluded = ["option", "checked", "className"];
/**
 * External dependencies
 */


/**
 * @typedef {import('./index').Option} Option
 */

/**
 * Renders a custom Checkbox
 *
 * @param {Object}  props           Component properties
 * @param {Option}  props.option    Option for the checkbox
 * @param {string}  props.className The className for the component
 * @param {boolean} props.checked   Defines if the checkbox is checked
 * @return {JSX.Element|null} The Checkbox component
 */
var Checkbox = function Checkbox(_ref) {
  var _option$key, _option$key2;
  var option = _ref.option,
    checked = _ref.checked,
    className = _ref.className,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  return (0,react.createElement)("div", {
    className: className
  }, (0,react.createElement)("div", {
    className: "components-base-control__field"
  }, (0,react.createElement)("span", {
    className: "components-checkbox-control__input-container"
  }, (0,react.createElement)("input", (0,esm_extends/* default */.A)({
    id: "inspector-checkbox-control-".concat((_option$key = option.key) !== null && _option$key !== void 0 ? _option$key : option.value),
    className: "components-checkbox-control__input",
    type: "checkbox",
    tabIndex: "-1",
    value: option.value,
    checked: checked
  }, props)), checked && (0,react.createElement)(icon/* default */.A, {
    icon: check/* default */.A,
    role: "presentation",
    className: "components-checkbox-control__checked"
  })), (0,react.createElement)("label", {
    className: "components-checkbox-control__label",
    htmlFor: "inspector-checkbox-control-".concat((_option$key2 = option.key) !== null && _option$key2 !== void 0 ? _option$key2 : option.value)
  }, option.label)));
};
/* harmony default export */ const tree_select_control_checkbox = (Checkbox);
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/options.js

/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



/**
 * @typedef {import('./index').InnerOption} InnerOption
 */

/**
 * This component renders a list of options and its children recursively
 *
 * @param {Object}                        props                    Component parameters
 * @param {InnerOption[]}                 props.options            List of options to be rendered
 * @param {InnerOption}                   props.parent             Parent option
 * @param {Function}                      props.onChange           Callback when an option changes
 * @param {Function}                      [props.onExpanderClick]  Callback when an expander is clicked.
 * @param {(option: InnerOption) => void} [props.onToggleExpanded] Callback when requesting an expander to be toggled.
 */
var Options = function Options(_ref) {
  var _ref$options = _ref.options,
    options = _ref$options === void 0 ? [] : _ref$options,
    _ref$onChange = _ref.onChange,
    _onChange = _ref$onChange === void 0 ? function () {} : _ref$onChange,
    _ref$onExpanderClick = _ref.onExpanderClick,
    onExpanderClick = _ref$onExpanderClick === void 0 ? lodash.noop : _ref$onExpanderClick,
    _ref$onToggleExpanded = _ref.onToggleExpanded,
    onToggleExpanded = _ref$onToggleExpanded === void 0 ? lodash.noop : _ref$onToggleExpanded,
    _ref$parent = _ref.parent,
    parent = _ref$parent === void 0 ? null : _ref$parent;
  /**
   * Alters the node with some keys for accessibility
   * ArrowRight - Expands the node
   * ArrowLeft - Collapses the node
   *
   * @param {Event}       event  The KeyDown event
   * @param {InnerOption} option The option where the event happened
   */
  var handleKeyDown = function handleKeyDown(event, option) {
    if (!option.hasChildren) {
      return;
    }
    if (event.key === ARROW_RIGHT && !option.expanded) {
      onToggleExpanded(option);
    } else if (event.key === ARROW_LEFT && option.expanded) {
      onToggleExpanded(option);
    }
  };
  return options.map(function (option) {
    var _option$key;
    var isRoot = option.value === ROOT_VALUE;
    var hasChildren = option.hasChildren,
      checked = option.checked,
      partialChecked = option.partialChecked,
      expanded = option.expanded;
    if (!(option !== null && option !== void 0 && option.value)) return null;
    if (!isRoot && !(option !== null && option !== void 0 && option.isVisible)) return null;
    return (0,react.createElement)("div", {
      key: "".concat((_option$key = option.key) !== null && _option$key !== void 0 ? _option$key : option.value),
      role: hasChildren ? 'treegroup' : 'treeitem',
      "aria-expanded": hasChildren ? expanded : undefined,
      className: classnames_default()('woocommerce-tree-select-control__node', hasChildren && 'has-children')
    }, (0,react.createElement)(component/* default */.A, {
      justify: "flex-start"
    }, !isRoot && (0,react.createElement)("button", {
      className: classnames_default()('woocommerce-tree-select-control__expander', !hasChildren && 'is-hidden'),
      tabIndex: "-1",
      onClick: function onClick(e) {
        e.preventDefault();
        onExpanderClick(e);
        onToggleExpanded(option);
      }
    }, (0,react.createElement)(icon/* default */.A, {
      icon: expanded ? chevron_up/* default */.A : chevron_down/* default */.A
    })), (0,react.createElement)(tree_select_control_checkbox, {
      className: classnames_default()('components-base-control', 'woocommerce-tree-select-control__option', partialChecked && 'is-partially-checked'),
      option: option,
      checked: checked,
      onChange: function onChange(e) {
        _onChange(e.target.checked, option, parent);
      },
      onKeyDown: function onKeyDown(e) {
        handleKeyDown(e, option);
      }
    })), hasChildren && expanded && (0,react.createElement)("div", {
      className: classnames_default()('woocommerce-tree-select-control__children', isRoot && 'woocommerce-tree-select-control__main')
    }, (0,react.createElement)(Options, {
      options: option.children,
      onChange: _onChange,
      onExpanderClick: onExpanderClick,
      onToggleExpanded: onToggleExpanded,
      parent: option
    })));
  });
};
/* harmony default export */ const tree_select_control_options = (Options);
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/index.js





var tree_select_control_excluded = ["children"];























/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





/**
 * @typedef {Object} CommonOption
 * @property {string} value The value for the option
 * @property {string} [key] Optional unique key for the Option. It will fallback to the value property if not defined
 */

/**
 * @typedef {Object} BaseOption
 * @property {string}   label      The label for the option
 * @property {Option[]} [children] The children Option objects
 *
 * @typedef {CommonOption & BaseOption} Option
 */

/**
 * @typedef {Object} BaseInnerOption
 * @property {string|JSX.Element}      label          The label string or label with highlighted react element for the option.
 * @property {InnerOption[]|undefined} children       The children options. The options are filtered if in searching.
 * @property {boolean}                 hasChildren    Whether this option has children.
 * @property {InnerOption[]}           leaves         All leaf options that are flattened under this option. The options are filtered if in searching.
 * @property {boolean}                 checked        Whether this option is checked.
 * @property {boolean}                 partialChecked Whether this option is partially checked.
 * @property {boolean}                 expanded       Whether this option is expanded.
 * @property {boolean}                 parent         The parent of the current option
 * @typedef {CommonOption & BaseInnerOption} InnerOption
 */

/**
 * Renders a component with a searchable control, tags and a tree selector.
 *
 * @param {Object}                     props                              Component props.
 * @param {string}                     [props.id]                         Component id
 * @param {string}                     [props.label]                      Label for the component
 * @param {string | false}             [props.selectAllLabel]             Label for the Select All root element. False for disable.
 * @param {string}                     [props.help]                       Help text under the select input.
 * @param {string}                     [props.placeholder]                Placeholder for the search control input
 * @param {string}                     [props.className]                  The class name for this component
 * @param {boolean}                    [props.disabled]                   Disables the component
 * @param {boolean}                    [props.includeParent]              Includes parent with selection.
 * @param {boolean}                    [props.individuallySelectParent]   Considers parent as a single item (default: false).
 * @param {boolean}                    [props.alwaysShowPlaceholder]      Will always show placeholder (default: false)
 * @param {Option[]}                   [props.options]                    Options to show in the component
 * @param {string[]}                   [props.value]                      Selected values
 * @param {number}                     [props.maxVisibleTags]             The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to "Show All".
 * @param {Function}                   [props.onChange]                   Callback when the selector changes
 * @param {(visible: boolean) => void} [props.onDropdownVisibilityChange] Callback when the visibility of the dropdown options is changed.
 * @param {Function}                   [props.onInputChange]              Callback when the selector changes
 * @param {number}                     [props.minFilterQueryLength]       Minimum input length to filter results by.
 * @param {boolean}                    [props.clearOnSelect]              Clear input on select (default: true).
 * @return {JSX.Element} The component
 */
var TreeSelectControl = function TreeSelectControl(_ref) {
  var id = _ref.id,
    label = _ref.label,
    _ref$selectAllLabel = _ref.selectAllLabel,
    selectAllLabel = _ref$selectAllLabel === void 0 ? (0,build_module.__)('All', 'woocommerce') : _ref$selectAllLabel,
    help = _ref.help,
    placeholder = _ref.placeholder,
    className = _ref.className,
    disabled = _ref.disabled,
    _ref$options = _ref.options,
    options = _ref$options === void 0 ? [] : _ref$options,
    _ref$value = _ref.value,
    value = _ref$value === void 0 ? [] : _ref$value,
    maxVisibleTags = _ref.maxVisibleTags,
    _ref$onChange = _ref.onChange,
    onChange = _ref$onChange === void 0 ? function () {} : _ref$onChange,
    _ref$onDropdownVisibi = _ref.onDropdownVisibilityChange,
    onDropdownVisibilityChange = _ref$onDropdownVisibi === void 0 ? lodash.noop : _ref$onDropdownVisibi,
    _ref$onInputChange = _ref.onInputChange,
    onInputChange = _ref$onInputChange === void 0 ? lodash.noop : _ref$onInputChange,
    _ref$includeParent = _ref.includeParent,
    includeParent = _ref$includeParent === void 0 ? false : _ref$includeParent,
    _ref$individuallySele = _ref.individuallySelectParent,
    individuallySelectParent = _ref$individuallySele === void 0 ? false : _ref$individuallySele,
    _ref$alwaysShowPlaceh = _ref.alwaysShowPlaceholder,
    alwaysShowPlaceholder = _ref$alwaysShowPlaceh === void 0 ? false : _ref$alwaysShowPlaceh,
    _ref$minFilterQueryLe = _ref.minFilterQueryLength,
    minFilterQueryLength = _ref$minFilterQueryLe === void 0 ? 3 : _ref$minFilterQueryLe,
    _ref$clearOnSelect = _ref.clearOnSelect,
    clearOnSelect = _ref$clearOnSelect === void 0 ? true : _ref$clearOnSelect;
  var instanceId = (0,use_instance_id/* default */.A)(TreeSelectControl);
  instanceId = id !== null && id !== void 0 ? id : instanceId;
  var _useState = (0,react.useState)(false),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    treeVisible = _useState2[0],
    setTreeVisible = _useState2[1];
  var _useState3 = (0,react.useState)([]),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    nodesExpanded = _useState4[0],
    setNodesExpanded = _useState4[1];
  var _useState5 = (0,react.useState)(''),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    inputControlValue = _useState6[0],
    setInputControlValue = _useState6[1];
  var controlRef = (0,react.useRef)();
  var dropdownRef = (0,react.useRef)();
  var onDropdownVisibilityChangeRef = (0,react.useRef)();
  onDropdownVisibilityChangeRef.current = onDropdownVisibilityChange;

  // We will save in a REF previous search filter queries to avoid re-query the tree and save performance
  var cacheRef = (0,react.useRef)({
    filteredOptionsMap: new Map()
  });
  cacheRef.current.expandedValues = nodesExpanded;
  cacheRef.current.selectedValues = value;
  var showTree = !disabled && treeVisible;
  var root = selectAllLabel !== false ? {
    label: selectAllLabel,
    value: ROOT_VALUE,
    children: options
  } : null;
  var treeOptions = tree_select_control_useIsEqualRefValue(root ? [root] : options);
  var focusOutside = (0,use_focus_outside/* default */.A)(function () {
    setTreeVisible(false);
  });
  var filterQuery = inputControlValue.trim().toLowerCase();
  // we only trigger the filter when there are more than 3 characters in the input.
  var filter = filterQuery.length >= minFilterQueryLength ? filterQuery : '';

  /**
   * Optimizes the performance for getting the tags info
   */
  var optionsRepository = (0,react.useMemo)(function () {
    var repository = {};

    // Clear cache if options change
    cacheRef.current.filteredOptionsMap.clear();
    function loadOption(option, parentId) {
      var _option$children, _option$key;
      option.parent = parentId;
      (_option$children = option.children) === null || _option$children === void 0 || _option$children.forEach(function (el) {
        return loadOption(el, option.value);
      });
      repository[(_option$key = option.key) !== null && _option$key !== void 0 ? _option$key : option.value] = option;
    }
    treeOptions.forEach(loadOption);
    return repository;
  }, [treeOptions]);

  /*
   * Perform the search query filter in the Tree options
   *
   * 1. Check if the search query is already cached and return it if so.
   * 2. Deep copy the tree with adding properties for rendering.
   * 3. In case of filter, we apply the filter option function to the tree.
   * 4. In the filter function we also highlight the label with the matching letters
   * 5. Finally we set the cache with the obtained results and apply the filters
   */
  var filteredOptions = (0,react.useMemo)(function () {
    var cache = cacheRef.current;
    var cachedFilteredOptions = cache.filteredOptionsMap.get(filter);
    if (cachedFilteredOptions) {
      return cachedFilteredOptions;
    }
    var isSearching = Boolean(filter);
    var highlightOptionLabel = function highlightOptionLabel(optionLabel, matchPosition) {
      var matchLength = matchPosition + filter.length;
      if (!isSearching) return optionLabel;
      return (0,react.createElement)("span", null, (0,react.createElement)("span", null, optionLabel.substring(0, matchPosition)), (0,react.createElement)("strong", null, optionLabel.substring(matchPosition, matchLength)), (0,react.createElement)("span", null, optionLabel.substring(matchLength)));
    };
    var descriptors = {
      hasChildren: {
        /**
         * Returns whether this option has children.
         *
         * @return {boolean} True if has children, false otherwise.
         */
        get: function get() {
          var _this$children;
          return ((_this$children = this.children) === null || _this$children === void 0 ? void 0 : _this$children.length) > 0;
        }
      },
      leaves: {
        /**
         * Return all leaf options flattened under this option. The options are filtered if in searching.
         *
         * @return {InnerOption[]} All leaf options that are flattened under this option. The options are filtered if in searching.
         */
        get: function get() {
          if (!this.hasChildren) {
            return [];
          }
          return this.children.flatMap(function (option) {
            if (option.hasChildren) {
              return includeParent && option.value !== ROOT_VALUE ? [option].concat((0,toConsumableArray/* default */.A)(option.leaves)) : option.leaves;
            }
            return option;
          });
        }
      },
      checked: {
        /**
         * Returns whether this option is checked.
         * A leaf option is checked if its value is selected.
         * A parent option is checked if all leaves are checked.
         *
         * @return {boolean} True if checked, false otherwise.
         */
        get: function get() {
          if (includeParent && this.value !== ROOT_VALUE || individuallySelectParent) {
            return cache.selectedValues.includes(this.value);
          }
          if (this.hasChildren) {
            return this.leaves.every(function (opt) {
              return opt.checked;
            });
          }
          return cache.selectedValues.includes(this.value);
        }
      },
      partialChecked: {
        /**
         * Returns whether this option is partially checked.
         * A leaf option always returns false.
         * A parent option is partially checked if at least one but not all leaves are checked.
         *
         * @return {boolean} True if partially checked, false otherwise.
         */
        get: function get() {
          if (!this.hasChildren) {
            return false;
          }
          return !this.checked && this.children.some(function (opt) {
            return opt.checked || opt.partialChecked;
          });
        }
      },
      isVisible: {
        /**
         * Returns whether this option should be visible based on search.
         * All options are visible when not searching. Otherwise, true if this option is
         * a search result or it has a descendent that is being searched for.
         *
         * @return {boolean} True if option should be visible, false otherwise.
         */
        get: function get() {
          // everything is visible when not searching.
          if (!isSearching) {
            return true;
          }

          // Exit true if this is searched result.
          if (this.isSearchResult) {
            return true;
          }

          // If any children are search results, remain visible.
          if (this.hasChildren) {
            return this.children.some(function (opt) {
              return opt.isVisible;
            });
          }
          return this.leaves.some(function (opt) {
            return opt.isSearchResult;
          });
        }
      },
      isSearchResult: {
        /**
         * Returns whether this option is a searched result.
         *
         * @return {boolean} True if option is being searched, false otherwise.
         */
        get: function get() {
          if (!isSearching) {
            return false;
          }
          return !!this.filterMatch;
        }
      },
      expanded: {
        /**
         * Returns whether this option is expanded.
         * A leaf option always returns false.
         *
         * @return {boolean} True if expanded, false otherwise.
         */
        get: function get() {
          return isSearching && this.isVisible || this.value === ROOT_VALUE || cache.expandedValues.includes(this.value);
        }
      }
    };

    /**
     * Decompose accented characters into their composable parts, then remove accents.
     * See https://www.unicode.org/reports/tr15/ and https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/normalize.
     */
    var removeAccents = function removeAccents(str) {
      return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    };
    var reduceOptions = function reduceOptions(acc, _ref2) {
      var _ref2$children = _ref2.children,
        children = _ref2$children === void 0 ? [] : _ref2$children,
        option = (0,objectWithoutProperties/* default */.A)(_ref2, tree_select_control_excluded);
      if (children.length) {
        option.children = children.reduce(reduceOptions, []);
      }
      if (isSearching) {
        var labelWithAccentsRemoved = removeAccents(option.label);
        var filterWithAccentsRemoved = removeAccents(filter);
        var match = labelWithAccentsRemoved.toLowerCase().indexOf(filterWithAccentsRemoved);
        if (match > -1) {
          option.label = highlightOptionLabel(option.label, match);
          option.filterMatch = true;
        }
      }
      Object.defineProperties(option, descriptors);
      acc.push(option);
      return acc;
    };
    var filteredTreeOptions = treeOptions.reduce(reduceOptions, []);
    cache.filteredOptionsMap.set(filter, filteredTreeOptions);
    return filteredTreeOptions;
  }, [treeOptions, filter]);

  /**
   * Handle key down events in the component
   *
   * Keys:
   * If key down is ESCAPE. Collapse the tree
   * If key down is ENTER. Expand the tree
   * If key down is ARROW_UP. Navigate up to the previous option
   * If key down is ARROW_DOWN. Navigate down to the next option
   * If key down is ARROW_DOWN. Navigate down to the next option
   *
   * @param {Event} event The key down event
   */
  var onKeyDown = function onKeyDown(event) {
    if (disabled) return;
    if (ESCAPE === event.key) {
      setTreeVisible(false);
    }
    if (ENTER === event.key) {
      setTreeVisible(true);
      if (event.target.type === 'checkbox') {
        event.target.click();
      }
      event.preventDefault();
    }
    var stepDict = (0,defineProperty/* default */.A)((0,defineProperty/* default */.A)({}, ARROW_UP, -1), ARROW_DOWN, 1);
    var step = stepDict[event.key];
    if (step && dropdownRef.current && filteredOptions.length) {
      var elements = dom_build_module/* focus */.XC.focusable.find(dropdownRef.current).filter(function (el) {
        return el.type === 'checkbox';
      });
      var currentIndex = elements.indexOf(event.target);
      var index = Math.max(currentIndex + step, -1) % elements.length;
      elements.at(index).focus();
      event.preventDefault();
    }
  };
  (0,react.useEffect)(function () {
    onDropdownVisibilityChangeRef.current(showTree);
  }, [showTree]);

  /**
   * Get formatted Tags from the selected values.
   *
   * @return {Array<{id: string, label: string|undefined}>} An array of Tags
   */
  var tags = (0,react.useMemo)(function () {
    if (!options.length) {
      return [];
    }
    return value.map(function (key) {
      var option = optionsRepository[key];
      return {
        id: key,
        label: option === null || option === void 0 ? void 0 : option.label
      };
    });
  }, [optionsRepository, value, options]);

  /**
   * Handle click event on the option expander
   *
   * @param {Event} e The click event object
   */
  var handleExpanderClick = function handleExpanderClick(e) {
    var elements = dom_build_module/* focus */.XC.focusable.find(dropdownRef.current);
    var index = elements.indexOf(e.currentTarget) + 1;
    elements[index].focus();
  };

  /**
   * Expands/Collapses the Option
   *
   * @param {InnerOption} option The option to be expanded or collapsed.
   */
  var handleToggleExpanded = function handleToggleExpanded(option) {
    setNodesExpanded(option.expanded ? nodesExpanded.filter(function (el) {
      return option.value !== el;
    }) : [].concat((0,toConsumableArray/* default */.A)(nodesExpanded), [option.value]));
  };

  /**
   * Handles a change of a child element.
   *
   * @param {boolean}     checked Indicates if the item should be checked
   * @param {InnerOption} option  The option to change
   * @param {InnerOption} parent  The options parent (could be null)
   */
  var handleSingleChange = function handleSingleChange(checked, option, parent) {
    var newValue = checked ? [].concat((0,toConsumableArray/* default */.A)(value), [option.value]) : value.filter(function (el) {
      return el !== option.value;
    });
    if (includeParent && parent && parent.value !== ROOT_VALUE && parent.children && parent.children.every(function (child) {
      return newValue.includes(child.value);
    }) && !newValue.includes(parent.value)) {
      newValue.push(parent.value);
    }
    onChange(newValue);
  };

  /**
   * Handles a change of a Parent element.
   *
   * @param {boolean}     checked Indicates if the item should be checked
   * @param {InnerOption} option  The option to change
   */
  var handleParentChange = function handleParentChange(checked, option) {
    var newValue;
    var changedValues = individuallySelectParent ? [option.value] : option.leaves.filter(function (opt) {
      return opt.checked !== checked;
    }).map(function (opt) {
      return opt.value;
    });
    /**
     * If includeParent is true, we need to add the parent value to the array of
     * changed values. However, if for some reason includeParent AND individuallySelectParent
     * are both set to true, we want to avoid duplicating the parent value in the array.
     */
    if (includeParent && !individuallySelectParent && option.value !== ROOT_VALUE) {
      changedValues.push(option.value);
    }
    if (checked) {
      if (!option.expanded) {
        handleToggleExpanded(option);
      }
      newValue = value.concat(changedValues);
    } else {
      newValue = value.filter(function (el) {
        return !changedValues.includes(el);
      });
    }
    onChange(newValue);
  };

  /**
   * Handles a change on the Tree options. Could be a click on a parent option
   * or a child option
   *
   * @param {boolean}     checked Indicates if the item should be checked
   * @param {InnerOption} option  The option to change
   * @param {InnerOption} parent  The options parent (could be null)
   */
  var handleOptionsChange = function handleOptionsChange(checked, option, parent) {
    if (option.hasChildren) {
      handleParentChange(checked, option);
    } else {
      handleSingleChange(checked, option, parent);
    }
    if (clearOnSelect) {
      onInputChange('');
      setInputControlValue('');
      if (!nodesExpanded.includes(option.parent)) {
        controlRef.current.focus();
      }
    }
  };

  /**
   * Handles a change of a Tag element. We map them to Value format.
   *
   * @param {Array} newTags List of new tags
   */
  var handleTagsChange = function handleTagsChange(newTags) {
    onChange((0,toConsumableArray/* default */.A)(newTags.map(function (el) {
      return el.id;
    })));
  };

  /**
   * Prepares and sets the search filter.
   * Filters of less than 3 characters are not considered, so we convert them to ''
   *
   * @param {Event} e Event returned by the On Change function in the Input control
   */
  var handleOnInputChange = function handleOnInputChange(e) {
    setTreeVisible(true);
    onInputChange(e.target.value);
    setInputControlValue(e.target.value);
  };
  return (
    // eslint-disable-next-line jsx-a11y/no-static-element-interactions
    (0,react.createElement)("div", (0,esm_extends/* default */.A)({}, focusOutside, {
      onKeyDown: onKeyDown,
      className: classnames_default()('woocommerce-tree-select-control', className)
    }), !!label && (0,react.createElement)("label", {
      htmlFor: "woocommerce-tree-select-control-".concat(instanceId, "__control-input"),
      className: "woocommerce-tree-select-control__label"
    }, label), (0,react.createElement)(control, {
      ref: controlRef,
      disabled: disabled,
      tags: tags,
      isExpanded: showTree,
      onFocus: function onFocus() {
        setTreeVisible(true);
      },
      onControlClick: function onControlClick() {
        setTreeVisible(true);
      },
      instanceId: instanceId,
      placeholder: placeholder,
      label: label,
      maxVisibleTags: maxVisibleTags,
      value: inputControlValue,
      onTagsChange: handleTagsChange,
      onInputChange: handleOnInputChange,
      alwaysShowPlaceholder: alwaysShowPlaceholder
    }), showTree && (0,react.createElement)("div", {
      ref: dropdownRef,
      className: "woocommerce-tree-select-control__tree",
      role: "tree",
      tabIndex: "-1"
    }, (0,react.createElement)(tree_select_control_options, {
      options: filteredOptions,
      onChange: handleOptionsChange,
      onExpanderClick: handleExpanderClick,
      onToggleExpanded: handleToggleExpanded
    })), help && (0,react.createElement)("div", {
      className: "woocommerce-tree-select-control__help"
    }, help))
  );
};
/* harmony default export */ const tree_select_control = (TreeSelectControl);
;// CONCATENATED MODULE: ../../packages/js/components/src/tree-select-control/stories/tree-select-control.story.js




/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var treeSelectControlOptions = [{
  value: 'EU',
  label: 'Europe',
  children: [{
    value: 'ES',
    label: 'Spain'
  }, {
    value: 'FR',
    label: 'France'
  }, {
    value: 'TR',
    label: 'Türkiye'
  }, {
    key: 'FR-Colonies',
    value: 'FR-C',
    label: 'France (Colonies)'
  }]
}, {
  value: 'AS',
  label: 'Asia',
  children: [{
    value: 'JP',
    label: 'Japan',
    children: [{
      value: 'TO',
      label: 'Tokyo',
      children: [{
        value: 'SI',
        label: 'Shibuya'
      }, {
        value: 'GI',
        label: 'Ginza'
      }]
    }, {
      value: 'OK',
      label: 'Okinawa'
    }]
  }, {
    value: 'CH',
    label: 'China'
  }, {
    value: 'MY',
    label: 'Malaysia',
    children: [{
      value: 'KU',
      label: 'Kuala Lumpur'
    }]
  }]
}, {
  value: 'NA',
  label: 'North America',
  children: [{
    value: 'US',
    label: 'United States',
    children: [{
      value: 'NY',
      label: 'New York'
    }, {
      value: 'TX',
      label: 'Texas'
    }, {
      value: 'GE',
      label: 'Georgia'
    }]
  }, {
    value: 'CA',
    label: 'Canada'
  }]
}];
var Template = function Template(args) {
  var _useState = (0,react.useState)(['ES']),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    selected = _useState2[0],
    setSelected = _useState2[1];
  (0,react.useEffect)(function () {
    if (args.onChange) {
      args.onChange(selected);
    }
  }, [selected]);
  return (0,react.createElement)(tree_select_control, (0,esm_extends/* default */.A)({}, args, {
    value: selected,
    onChange: setSelected
  }));
};
var Base = Template.bind({});
Base.args = {
  id: 'my-id',
  label: 'Select Countries',
  placeholder: 'Search countries',
  disabled: false,
  options: treeSelectControlOptions,
  maxVisibleTags: 3,
  selectAllLabel: 'All countries',
  includeParent: false,
  alwaysShowPlaceholder: false,
  individuallySelectParent: false,
  clearOnSelect: true
};
Base.argTypes = {
  onInputChange: {
    action: 'onInputChange'
  },
  onChange: {
    action: 'onChange'
  }
};
/* harmony default export */ const tree_select_control_story = ({
  title: 'WooCommerce Admin/components/TreeSelectControl',
  component: tree_select_control
});
Base.parameters = {
  ...Base.parameters,
  docs: {
    ...Base.parameters?.docs,
    source: {
      originalSource: "args => {\n  const [selected, setSelected] = useState(['ES']);\n  useEffect(() => {\n    if (args.onChange) {\n      args.onChange(selected);\n    }\n  }, [selected]);\n  return <TreeSelectControl {...args} value={selected} onChange={setSelected} />;\n}",
      ...Base.parameters?.docs?.source
    }
  }
};

/***/ })

}]);