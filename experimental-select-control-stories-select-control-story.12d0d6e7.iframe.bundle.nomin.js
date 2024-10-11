"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4087],{

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

/***/ "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Async: () => (/* binding */ Async),
  AsyncWithoutListeningFilterEvents: () => (/* binding */ AsyncWithoutListeningFilterEvents),
  CustomItemType: () => (/* binding */ CustomItemType),
  CustomRender: () => (/* binding */ CustomRender),
  CustomSuffix: () => (/* binding */ CustomSuffix),
  CustomSuffixIcon: () => (/* binding */ CustomSuffixIcon),
  DefaultSuffix: () => (/* binding */ DefaultSuffix),
  ExternalTags: () => (/* binding */ ExternalTags),
  FuzzyMatching: () => (/* binding */ FuzzyMatching),
  Multiple: () => (/* binding */ Multiple),
  NoSuffix: () => (/* binding */ NoSuffix),
  Single: () => (/* binding */ Single),
  SingleWithinModalUsingBodyDropdownPlacement: () => (/* binding */ SingleWithinModalUsingBodyDropdownPlacement),
  ToggleButton: () => (/* binding */ ToggleButton),
  "default": () => (/* binding */ select_control_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js");
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);
// EXTERNAL MODULE: ../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js
var runtime = __webpack_require__("../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js
var web_timers = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js + 8 modules
var slot_fill = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/modal/index.js + 1 modules
var modal = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/modal/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/tag.js
var tag = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/tag.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu-item.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/select-control.tsx + 1 modules
var select_control = __webpack_require__("../../packages/js/components/src/experimental-select-control/select-control.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.finally.js
var es_promise_finally = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.finally.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js
var use_debounce = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/suffix-icon.tsx
var suffix_icon = __webpack_require__("../../packages/js/components/src/experimental-select-control/suffix-icon.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-select-control/hooks/use-async-filter.tsx






/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


var DEFAULT_DEBOUNCE_TIME = 250;
function useAsyncFilter(_ref) {
  var filter = _ref.filter,
    onFilterStart = _ref.onFilterStart,
    onFilterEnd = _ref.onFilterEnd,
    onFilterError = _ref.onFilterError,
    debounceTime = _ref.debounceTime;
  var _useState = (0,react.useState)(false),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    isFetching = _useState2[0],
    setIsFetching = _useState2[1];
  var handleInputChange = (0,react.useCallback)(function handleInputChangeCallback(value) {
    if (typeof filter === 'function') {
      if (typeof onFilterStart === 'function') onFilterStart(value);
      setIsFetching(true);
      filter(value).then(function (filteredItems) {
        if (typeof onFilterEnd === 'function') onFilterEnd(filteredItems, value);
      })["catch"](function (error) {
        if (typeof onFilterError === 'function') onFilterError(error, value);
      })["finally"](function () {
        setIsFetching(false);
      });
    }
  }, [filter, onFilterStart, onFilterEnd, onFilterError]);
  return {
    isFetching: isFetching,
    suffix: isFetching === true ? (0,react.createElement)(suffix_icon/* SuffixIcon */.f, {
      icon: (0,react.createElement)(spinner/* default */.A, null)
    }) : undefined,
    getFilteredItems: function getFilteredItems(items) {
      return items;
    },
    onInputChange: (0,use_debounce/* default */.A)(handleInputChange, typeof debounceTime === 'number' ? debounceTime : DEFAULT_DEBOUNCE_TIME)
  };
}
try {
    // @ts-ignore
    useasyncfilter.displayName = "useasyncfilter";
    // @ts-ignore
    useasyncfilter.__docgenInfo = { "description": "", "displayName": "useasyncfilter", "props": { "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "(value?: string | undefined) => Promise<T[]>" } }, "onFilterStart": { "defaultValue": null, "description": "", "name": "onFilterStart", "required": false, "type": { "name": "((value?: string) => void)" } }, "onFilterEnd": { "defaultValue": null, "description": "", "name": "onFilterEnd", "required": false, "type": { "name": "((filteredItems: T[], value?: string) => void)" } }, "onFilterError": { "defaultValue": null, "description": "", "name": "onFilterError", "required": false, "type": { "name": "((error: Error, value?: string) => void)" } }, "debounceTime": { "defaultValue": null, "description": "", "name": "debounceTime", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/hooks/use-async-filter.tsx#useasyncfilter"] = { docgenInfo: useasyncfilter.__docgenInfo, name: "useasyncfilter", path: "../../packages/js/components/src/experimental-select-control/hooks/use-async-filter.tsx#useasyncfilter" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu.tsx
var menu = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx






var _excluded = ["isFetching"],
  _excluded2 = ["isFetching"];

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
 * Internal dependencies
 */





var sampleItems = [{
  value: 'apple',
  label: 'Apple'
}, {
  value: 'pear',
  label: 'Pear'
}, {
  value: 'orange',
  label: 'Orange'
}, {
  value: 'grape',
  label: 'Grape'
}, {
  value: 'banana',
  label: 'Banana'
}];
var Single = function Single() {
  var _useState = (0,react.useState)(sampleItems[1]),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    selected = _useState2[0],
    setSelected = _useState2[1];
  return (0,react.createElement)(react.Fragment, null, "Selected: ", JSON.stringify(selected), (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Single value",
    selected: selected,
    onSelect: function onSelect(item) {
      return item && setSelected(item);
    },
    onRemove: function onRemove() {
      return setSelected(null);
    }
  }));
};
var Multiple = function Multiple() {
  var _useState3 = (0,react.useState)([sampleItems[0], sampleItems[2]]),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    selected = _useState4[0],
    setSelected = _useState4[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(select_control/* SelectControl */.Y, {
    multiple: true,
    items: sampleItems,
    label: "Multiple values",
    selected: selected,
    onSelect: function onSelect(item) {
      return Array.isArray(selected) && setSelected([].concat((0,toConsumableArray/* default */.A)(selected), [item]));
    },
    onRemove: function onRemove(item) {
      return setSelected(selected.filter(function (i) {
        return i !== item;
      }));
    }
  }));
};
var ExternalTags = function ExternalTags() {
  var _useState5 = (0,react.useState)([]),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    selected = _useState6[0],
    setSelected = _useState6[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(select_control/* SelectControl */.Y, {
    multiple: true,
    hasExternalTags: true,
    items: sampleItems,
    label: "External tags",
    selected: selected,
    onSelect: function onSelect(item) {
      return Array.isArray(selected) && setSelected([].concat((0,toConsumableArray/* default */.A)(selected), [item]));
    },
    onRemove: function onRemove(item) {
      return setSelected(selected.filter(function (i) {
        return i !== item;
      }));
    }
  }));
};
var FuzzyMatching = function FuzzyMatching() {
  var _useState7 = (0,react.useState)([]),
    _useState8 = (0,slicedToArray/* default */.A)(_useState7, 2),
    selected = _useState8[0],
    setSelected = _useState8[1];
  var getFilteredItems = function getFilteredItems(allItems, inputValue, selectedItems) {
    var pattern = '.*' + inputValue.toLowerCase().split('').join('.*') + '.*';
    var re = new RegExp(pattern);
    return allItems.filter(function (item) {
      if (selectedItems.indexOf(item) >= 0) {
        return false;
      }
      return re.test(item.label.toLowerCase());
    });
  };
  return (0,react.createElement)(select_control/* SelectControl */.Y, {
    multiple: true,
    getFilteredItems: getFilteredItems,
    items: sampleItems,
    label: "Fuzzy matching",
    selected: selected,
    onSelect: function onSelect(item) {
      return setSelected([].concat((0,toConsumableArray/* default */.A)(selected), [item]));
    },
    onRemove: function onRemove(item) {
      return setSelected(selected.filter(function (i) {
        return i !== item;
      }));
    }
  });
};
var Async = function Async() {
  var _useState9 = (0,react.useState)(null),
    _useState10 = (0,slicedToArray/* default */.A)(_useState9, 2),
    selectedItem = _useState10[0],
    setSelectedItem = _useState10[1];
  var _useState11 = (0,react.useState)([]),
    _useState12 = (0,slicedToArray/* default */.A)(_useState11, 2),
    fetchedItems = _useState12[0],
    setFetchedItems = _useState12[1];
  var filter = (0,react.useCallback)(function () {
    var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    return new Promise(function (resolve) {
      setTimeout(function () {
        var filteredItems = [].concat(sampleItems).sort(function (a, b) {
          return a.label.localeCompare(b.label);
        }).filter(function (_ref) {
          var label = _ref.label;
          return label.toLowerCase().includes(value.toLowerCase());
        });
        resolve(filteredItems);
      }, 1500);
    });
  }, [selectedItem]);
  var _useAsyncFilter = useAsyncFilter({
      filter: filter,
      onFilterStart: function onFilterStart() {
        setFetchedItems([]);
      },
      onFilterEnd: function onFilterEnd(filteredItems) {
        setFetchedItems(filteredItems);
      }
    }),
    isFetching = _useAsyncFilter.isFetching,
    selectProps = (0,objectWithoutProperties/* default */.A)(_useAsyncFilter, _excluded);
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(select_control/* SelectControl */.Y, (0,esm_extends/* default */.A)({}, selectProps, {
    label: "Async",
    items: fetchedItems,
    selected: selectedItem,
    placeholder: "Start typing...",
    onSelect: setSelectedItem,
    onRemove: function onRemove() {
      return setSelectedItem(null);
    }
  }), function (_ref2) {
    var items = _ref2.items,
      isOpen = _ref2.isOpen,
      highlightedIndex = _ref2.highlightedIndex,
      getItemProps = _ref2.getItemProps,
      getMenuProps = _ref2.getMenuProps;
    return (0,react.createElement)(menu/* Menu */.W, {
      isOpen: isOpen,
      getMenuProps: getMenuProps
    }, isFetching ? (0,react.createElement)(spinner/* default */.A, null) : items.map(function (item, index) {
      return (0,react.createElement)(menu_item/* MenuItem */.D, {
        key: "".concat(item.value).concat(index),
        index: index,
        isActive: highlightedIndex === index,
        item: item,
        getItemProps: getItemProps
      }, item.label);
    }));
  }));
};
var AsyncWithoutListeningFilterEvents = function AsyncWithoutListeningFilterEvents() {
  var _useState13 = (0,react.useState)(null),
    _useState14 = (0,slicedToArray/* default */.A)(_useState13, 2),
    selectedItem = _useState14[0],
    setSelectedItem = _useState14[1];
  var _useState15 = (0,react.useState)([]),
    _useState16 = (0,slicedToArray/* default */.A)(_useState15, 2),
    fetchedItems = _useState16[0],
    setFetchedItems = _useState16[1];
  var filter = (0,react.useCallback)( /*#__PURE__*/(0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee() {
    var value,
      _args = arguments;
    return regenerator_default().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          value = _args.length > 0 && _args[0] !== undefined ? _args[0] : '';
          setFetchedItems([]);
          return _context.abrupt("return", new Promise(function (resolve) {
            setTimeout(function () {
              var filteredItems = [].concat(sampleItems).sort(function (a, b) {
                return a.label.localeCompare(b.label);
              }).filter(function (_ref4) {
                var label = _ref4.label;
                return label.toLowerCase().includes(value.toLowerCase());
              });
              resolve(filteredItems);
            }, 1500);
          }).then(function (filteredItems) {
            setFetchedItems(filteredItems);
            return filteredItems;
          }));
        case 3:
        case "end":
          return _context.stop();
      }
    }, _callee);
  })), [selectedItem]);
  var _useAsyncFilter2 = useAsyncFilter({
      filter: filter
    }),
    isFetching = _useAsyncFilter2.isFetching,
    selectProps = (0,objectWithoutProperties/* default */.A)(_useAsyncFilter2, _excluded2);
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(select_control/* SelectControl */.Y, (0,esm_extends/* default */.A)({}, selectProps, {
    label: "Async",
    items: fetchedItems,
    selected: selectedItem,
    placeholder: "Start typing...",
    onSelect: setSelectedItem,
    onRemove: function onRemove() {
      return setSelectedItem(null);
    }
  }), function (_ref5) {
    var items = _ref5.items,
      isOpen = _ref5.isOpen,
      highlightedIndex = _ref5.highlightedIndex,
      getItemProps = _ref5.getItemProps,
      getMenuProps = _ref5.getMenuProps;
    return (0,react.createElement)(menu/* Menu */.W, {
      isOpen: isOpen,
      getMenuProps: getMenuProps
    }, isFetching ? (0,react.createElement)(spinner/* default */.A, null) : items.map(function (item, index) {
      return (0,react.createElement)(menu_item/* MenuItem */.D, {
        key: "".concat(item.value).concat(index),
        index: index,
        isActive: highlightedIndex === index,
        item: item,
        getItemProps: getItemProps
      }, item.label);
    }));
  }));
};
var CustomRender = function CustomRender() {
  var _useState17 = (0,react.useState)([sampleItems[0]]),
    _useState18 = (0,slicedToArray/* default */.A)(_useState17, 2),
    selected = _useState18[0],
    setSelected = _useState18[1];
  var onRemove = function onRemove(item) {
    setSelected(selected.filter(function (i) {
      return i !== item;
    }));
  };
  var onSelect = function onSelect(item) {
    var isSelected = selected.find(function (i) {
      return i.value === item.value;
    });
    if (isSelected) {
      onRemove(item);
      return;
    }
    setSelected([].concat((0,toConsumableArray/* default */.A)(selected), [item]));
  };
  var getFilteredItems = function getFilteredItems(allItems, inputValue, selectedItems, getItemLabel) {
    var escapedInputValue = inputValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    var re = new RegExp(escapedInputValue, 'gi');
    return allItems.filter(function (item) {
      return re.test(getItemLabel(item).toLowerCase());
    });
  };
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(select_control/* SelectControl */.Y, {
    multiple: true,
    label: "Custom render",
    items: sampleItems,
    selected: selected,
    onSelect: onSelect,
    onRemove: onRemove,
    getFilteredItems: getFilteredItems,
    stateReducer: function stateReducer(state, actionAndChanges) {
      var changes = actionAndChanges.changes,
        type = actionAndChanges.type;
      switch (type) {
        case select_control/* selectControlStateChangeTypes */.U.ControlledPropUpdatedSelectedItem:
          return _objectSpread(_objectSpread({}, changes), {}, {
            inputValue: state.inputValue
          });
        case select_control/* selectControlStateChangeTypes */.U.ItemClick:
          return _objectSpread(_objectSpread({}, changes), {}, {
            isOpen: true,
            inputValue: state.inputValue,
            highlightedIndex: state.highlightedIndex
          });
        default:
          return changes;
      }
    }
  }, function (_ref6) {
    var items = _ref6.items,
      highlightedIndex = _ref6.highlightedIndex,
      getItemProps = _ref6.getItemProps,
      getMenuProps = _ref6.getMenuProps,
      isOpen = _ref6.isOpen;
    return (0,react.createElement)(menu/* Menu */.W, {
      isOpen: isOpen,
      getMenuProps: getMenuProps
    }, items.map(function (item, index) {
      var isSelected = selected.includes(item);
      return (0,react.createElement)(menu_item/* MenuItem */.D, {
        key: "".concat(item.value),
        index: index,
        isActive: highlightedIndex === index,
        item: item,
        getItemProps: getItemProps
      }, (0,react.createElement)(react.Fragment, null, (0,react.createElement)(checkbox_control/* default */.A, {
        onChange: function onChange() {
          return null;
        },
        checked: isSelected,
        label: (0,react.createElement)("span", {
          style: {
            fontWeight: isSelected ? 'bold' : 'normal'
          }
        }, item.label)
      })));
    }));
  }));
};
var customItems = [{
  itemId: 1,
  user: {
    name: 'Joe',
    email: 'joe@a8c.com',
    id: 32
  }
}, {
  itemId: 2,
  user: {
    name: 'Jen',
    id: 16
  }
}, {
  itemId: 3,
  user: {
    name: 'Jared',
    id: 112
  }
}];
var CustomItemType = function CustomItemType() {
  var _useState19 = (0,react.useState)([]),
    _useState20 = (0,slicedToArray/* default */.A)(_useState19, 2),
    selected = _useState20[0],
    setSelected = _useState20[1];
  return (0,react.createElement)(react.Fragment, null, "Selected: ", JSON.stringify(selected), (0,react.createElement)(select_control/* SelectControl */.Y, {
    multiple: true,
    items: customItems,
    label: "CustomItemType value",
    selected: selected,
    onSelect: function onSelect(item) {
      return setSelected(Array.isArray(selected) ? [].concat((0,toConsumableArray/* default */.A)(selected), [item]) : [item]);
    },
    onRemove: function onRemove(item) {
      return setSelected((selected === null || selected === void 0 ? void 0 : selected.filter(function (i) {
        return i !== item;
      })) || []);
    },
    getItemLabel: function getItemLabel(item) {
      return (item === null || item === void 0 ? void 0 : item.user.name) || '';
    },
    getItemValue: function getItemValue(item) {
      return String(item === null || item === void 0 ? void 0 : item.itemId);
    }
  }));
};
var SingleWithinModalUsingBodyDropdownPlacement = function SingleWithinModalUsingBodyDropdownPlacement() {
  var _useState21 = (0,react.useState)(true),
    _useState22 = (0,slicedToArray/* default */.A)(_useState21, 2),
    isOpen = _useState22[0],
    setOpen = _useState22[1];
  var _useState23 = (0,react.useState)(),
    _useState24 = (0,slicedToArray/* default */.A)(_useState23, 2),
    selected = _useState24[0],
    setSelected = _useState24[1];
  var _useState25 = (0,react.useState)(),
    _useState26 = (0,slicedToArray/* default */.A)(_useState25, 2),
    selectedTwo = _useState26[0],
    setSelectedTwo = _useState26[1];
  return (0,react.createElement)(slot_fill/* Provider */.Kq, null, "Selected: ", JSON.stringify(selected), (0,react.createElement)(build_module_button/* default */.A, {
    onClick: function onClick() {
      return setOpen(true);
    }
  }, "Show Dropdown in Modal"), isOpen && (0,react.createElement)(modal/* default */.A, {
    title: "Dropdown Modal",
    onRequestClose: function onRequestClose() {
      return setOpen(false);
    }
  }, (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Single value",
    selected: selected,
    onSelect: function onSelect(item) {
      return item && setSelected(item);
    },
    onRemove: function onRemove() {
      return setSelected(null);
    }
  }), (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Single value",
    selected: selectedTwo,
    onSelect: function onSelect(item) {
      return item && setSelectedTwo(item);
    },
    onRemove: function onRemove() {
      return setSelectedTwo(null);
    }
  })), (0,react.createElement)(menu/* MenuSlot */.c, null));
};
var DefaultSuffix = function DefaultSuffix() {
  var _useState27 = (0,react.useState)(sampleItems[1]),
    _useState28 = (0,slicedToArray/* default */.A)(_useState27, 2),
    selected = _useState28[0],
    setSelected = _useState28[1];
  return (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Default suffix",
    selected: selected,
    onSelect: function onSelect(item) {
      return item && setSelected(item);
    },
    onRemove: function onRemove() {
      return setSelected(null);
    }
  });
};
var CustomSuffixIcon = function CustomSuffixIcon() {
  var _useState29 = (0,react.useState)(sampleItems[1]),
    _useState30 = (0,slicedToArray/* default */.A)(_useState29, 2),
    selected = _useState30[0],
    setSelected = _useState30[1];
  return (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Custom suffix icon",
    selected: selected,
    onSelect: function onSelect(item) {
      return item && setSelected(item);
    },
    onRemove: function onRemove() {
      return setSelected(null);
    },
    suffix: (0,react.createElement)(suffix_icon/* SuffixIcon */.f, {
      icon: tag/* default */.A
    })
  });
};
var NoSuffix = function NoSuffix() {
  var _useState31 = (0,react.useState)(sampleItems[1]),
    _useState32 = (0,slicedToArray/* default */.A)(_useState31, 2),
    selected = _useState32[0],
    setSelected = _useState32[1];
  return (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "No suffix",
    selected: selected,
    onSelect: function onSelect(item) {
      return item && setSelected(item);
    },
    onRemove: function onRemove() {
      return setSelected(null);
    },
    suffix: null
  });
};
var CustomSuffix = function CustomSuffix() {
  var _useState33 = (0,react.useState)(sampleItems[1]),
    _useState34 = (0,slicedToArray/* default */.A)(_useState33, 2),
    selected = _useState34[0],
    setSelected = _useState34[1];
  return (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Custom suffix",
    selected: selected,
    onSelect: function onSelect(item) {
      return item && setSelected(item);
    },
    onRemove: function onRemove() {
      return setSelected(null);
    },
    suffix: (0,react.createElement)("div", {
      style: {
        background: 'red',
        height: '100%'
      }
    }, "Suffix!")
  });
};
var ToggleButton = function ToggleButton() {
  var _useState35 = (0,react.useState)(),
    _useState36 = (0,slicedToArray/* default */.A)(_useState35, 2),
    selected = _useState36[0],
    setSelected = _useState36[1];
  return (0,react.createElement)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Has toggle button",
    selected: selected,
    onSelect: function onSelect(item) {
      return item && setSelected(item);
    },
    onRemove: function onRemove() {
      return setSelected(null);
    },
    suffix: null,
    showToggleButton: true,
    __experimentalOpenMenuOnFocus: true
  });
};
/* harmony default export */ const select_control_story = ({
  title: 'WooCommerce Admin/experimental/SelectControl',
  component: select_control/* SelectControl */.Y
});
Single.parameters = {
  ...Single.parameters,
  docs: {
    ...Single.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <>\n            Selected: {JSON.stringify(selected)}\n            <SelectControl items={sampleItems} label=\"Single value\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} />\n        </>;\n}",
      ...Single.parameters?.docs?.source
    }
  }
};
Multiple.parameters = {
  ...Multiple.parameters,
  docs: {
    ...Multiple.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([sampleItems[0], sampleItems[2]]);\n  return <>\n            <SelectControl multiple items={sampleItems} label=\"Multiple values\" selected={selected} onSelect={item => Array.isArray(selected) && setSelected([...selected, item])} onRemove={item => setSelected(selected.filter(i => i !== item))} />\n        </>;\n}",
      ...Multiple.parameters?.docs?.source
    }
  }
};
ExternalTags.parameters = {
  ...ExternalTags.parameters,
  docs: {
    ...ExternalTags.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([]);\n  return <>\n            <SelectControl multiple hasExternalTags items={sampleItems} label=\"External tags\" selected={selected} onSelect={item => Array.isArray(selected) && setSelected([...selected, item])} onRemove={item => setSelected(selected.filter(i => i !== item))} />\n        </>;\n}",
      ...ExternalTags.parameters?.docs?.source
    }
  }
};
FuzzyMatching.parameters = {
  ...FuzzyMatching.parameters,
  docs: {
    ...FuzzyMatching.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([]);\n  const getFilteredItems = (allItems: DefaultItemType[], inputValue: string, selectedItems: DefaultItemType[]) => {\n    const pattern = '.*' + inputValue.toLowerCase().split('').join('.*') + '.*';\n    const re = new RegExp(pattern);\n    return allItems.filter(item => {\n      if (selectedItems.indexOf(item) >= 0) {\n        return false;\n      }\n      return re.test(item.label.toLowerCase());\n    });\n  };\n  return <SelectControl multiple getFilteredItems={getFilteredItems} items={sampleItems} label=\"Fuzzy matching\" selected={selected} onSelect={item => setSelected([...selected, item])} onRemove={item => setSelected(selected.filter(i => i !== item))} />;\n}",
      ...FuzzyMatching.parameters?.docs?.source
    }
  }
};
Async.parameters = {
  ...Async.parameters,
  docs: {
    ...Async.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selectedItem, setSelectedItem] = useState<DefaultItemType | null>(null);\n  const [fetchedItems, setFetchedItems] = useState<DefaultItemType[]>([]);\n  const filter = useCallback((value = '') => new Promise<DefaultItemType[]>(resolve => {\n    setTimeout(() => {\n      const filteredItems = [...sampleItems].sort((a, b) => a.label.localeCompare(b.label)).filter(({\n        label\n      }) => label.toLowerCase().includes(value.toLowerCase()));\n      resolve(filteredItems);\n    }, 1500);\n  }), [selectedItem]);\n  const {\n    isFetching,\n    ...selectProps\n  } = useAsyncFilter<DefaultItemType>({\n    filter,\n    onFilterStart() {\n      setFetchedItems([]);\n    },\n    onFilterEnd(filteredItems) {\n      setFetchedItems(filteredItems);\n    }\n  });\n  return <>\n            <SelectControl<DefaultItemType> {...selectProps} label=\"Async\" items={fetchedItems} selected={selectedItem} placeholder=\"Start typing...\" onSelect={setSelectedItem} onRemove={() => setSelectedItem(null)}>\n                {({\n        items,\n        isOpen,\n        highlightedIndex,\n        getItemProps,\n        getMenuProps\n      }) => {\n        return <Menu isOpen={isOpen} getMenuProps={getMenuProps}>\n                            {isFetching ? <Spinner /> : items.map((item, index: number) => <MenuItem key={`${item.value}${index}`} index={index} isActive={highlightedIndex === index} item={item} getItemProps={getItemProps}>\n                                        {item.label}\n                                    </MenuItem>)}\n                        </Menu>;\n      }}\n            </SelectControl>\n        </>;\n}",
      ...Async.parameters?.docs?.source
    }
  }
};
AsyncWithoutListeningFilterEvents.parameters = {
  ...AsyncWithoutListeningFilterEvents.parameters,
  docs: {
    ...AsyncWithoutListeningFilterEvents.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selectedItem, setSelectedItem] = useState<DefaultItemType | null>(null);\n  const [fetchedItems, setFetchedItems] = useState<DefaultItemType[]>([]);\n  const filter = useCallback(async (value = '') => {\n    setFetchedItems([]);\n    return new Promise<DefaultItemType[]>(resolve => {\n      setTimeout(() => {\n        const filteredItems = [...sampleItems].sort((a, b) => a.label.localeCompare(b.label)).filter(({\n          label\n        }) => label.toLowerCase().includes(value.toLowerCase()));\n        resolve(filteredItems);\n      }, 1500);\n    }).then(filteredItems => {\n      setFetchedItems(filteredItems);\n      return filteredItems;\n    });\n  }, [selectedItem]);\n  const {\n    isFetching,\n    ...selectProps\n  } = useAsyncFilter<DefaultItemType>({\n    filter\n  });\n  return <>\n            <SelectControl<DefaultItemType> {...selectProps} label=\"Async\" items={fetchedItems} selected={selectedItem} placeholder=\"Start typing...\" onSelect={setSelectedItem} onRemove={() => setSelectedItem(null)}>\n                {({\n        items,\n        isOpen,\n        highlightedIndex,\n        getItemProps,\n        getMenuProps\n      }) => {\n        return <Menu isOpen={isOpen} getMenuProps={getMenuProps}>\n                            {isFetching ? <Spinner /> : items.map((item, index: number) => <MenuItem key={`${item.value}${index}`} index={index} isActive={highlightedIndex === index} item={item} getItemProps={getItemProps}>\n                                        {item.label}\n                                    </MenuItem>)}\n                        </Menu>;\n      }}\n            </SelectControl>\n        </>;\n}",
      ...AsyncWithoutListeningFilterEvents.parameters?.docs?.source
    }
  }
};
CustomRender.parameters = {
  ...CustomRender.parameters,
  docs: {
    ...CustomRender.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([sampleItems[0]]);\n  const onRemove = item => {\n    setSelected(selected.filter(i => i !== item));\n  };\n  const onSelect = item => {\n    const isSelected = selected.find(i => i.value === item.value);\n    if (isSelected) {\n      onRemove(item);\n      return;\n    }\n    setSelected([...selected, item]);\n  };\n  const getFilteredItems = (allItems: DefaultItemType[], inputValue: string, selectedItems: DefaultItemType[], getItemLabel: getItemLabelType<DefaultItemType>) => {\n    const escapedInputValue = inputValue.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&');\n    const re = new RegExp(escapedInputValue, 'gi');\n    return allItems.filter(item => {\n      return re.test(getItemLabel(item).toLowerCase());\n    });\n  };\n  return <>\n            <SelectControl multiple label=\"Custom render\" items={sampleItems} selected={selected} onSelect={onSelect} onRemove={onRemove} getFilteredItems={getFilteredItems} stateReducer={(state, actionAndChanges) => {\n      const {\n        changes,\n        type\n      } = actionAndChanges;\n      switch (type) {\n        case selectControlStateChangeTypes.ControlledPropUpdatedSelectedItem:\n          return {\n            ...changes,\n            inputValue: state.inputValue\n          };\n        case selectControlStateChangeTypes.ItemClick:\n          return {\n            ...changes,\n            isOpen: true,\n            inputValue: state.inputValue,\n            highlightedIndex: state.highlightedIndex\n          };\n        default:\n          return changes;\n      }\n    }}>\n                {({\n        items,\n        highlightedIndex,\n        getItemProps,\n        getMenuProps,\n        isOpen\n      }) => {\n        return <Menu isOpen={isOpen} getMenuProps={getMenuProps}>\n                            {items.map((item, index: number) => {\n            const isSelected = selected.includes(item);\n            return <MenuItem key={`${item.value}`} index={index} isActive={highlightedIndex === index} item={item} getItemProps={getItemProps}>\n                                        <>\n                                            <CheckboxControl onChange={() => null} checked={isSelected} label={<span style={{\n                  fontWeight: isSelected ? 'bold' : 'normal'\n                }}>\n                                                        {item.label}\n                                                    </span>} />\n                                        </>\n                                    </MenuItem>;\n          })}\n                        </Menu>;\n      }}\n            </SelectControl>\n        </>;\n}",
      ...CustomRender.parameters?.docs?.source
    }
  }
};
CustomItemType.parameters = {
  ...CustomItemType.parameters,
  docs: {
    ...CustomItemType.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<Array<CustomItemType>>>([]);\n  return <>\n            Selected: {JSON.stringify(selected)}\n            <SelectControl<CustomItemType> multiple items={customItems} label=\"CustomItemType value\" selected={selected} onSelect={item => setSelected(Array.isArray(selected) ? [...selected, item] : [item])} onRemove={item => setSelected(selected?.filter(i => i !== item) || [])} getItemLabel={item => item?.user.name || ''} getItemValue={item => String(item?.itemId)} />\n        </>;\n}",
      ...CustomItemType.parameters?.docs?.source
    }
  }
};
SingleWithinModalUsingBodyDropdownPlacement.parameters = {
  ...SingleWithinModalUsingBodyDropdownPlacement.parameters,
  docs: {
    ...SingleWithinModalUsingBodyDropdownPlacement.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [isOpen, setOpen] = useState(true);\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>();\n  const [selectedTwo, setSelectedTwo] = useState<SelectedType<DefaultItemType>>();\n  return <SlotFillProvider>\n            Selected: {JSON.stringify(selected)}\n            <Button onClick={() => setOpen(true)}>\n                Show Dropdown in Modal\n            </Button>\n            {isOpen && <Modal title=\"Dropdown Modal\" onRequestClose={() => setOpen(false)}>\n                    <SelectControl items={sampleItems} label=\"Single value\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} />\n                    <SelectControl items={sampleItems} label=\"Single value\" selected={selectedTwo} onSelect={item => item && setSelectedTwo(item)} onRemove={() => setSelectedTwo(null)} />\n                </Modal>}\n            <MenuSlot />\n        </SlotFillProvider>;\n}",
      ...SingleWithinModalUsingBodyDropdownPlacement.parameters?.docs?.source
    }
  }
};
DefaultSuffix.parameters = {
  ...DefaultSuffix.parameters,
  docs: {
    ...DefaultSuffix.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"Default suffix\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} />;\n}",
      ...DefaultSuffix.parameters?.docs?.source
    }
  }
};
CustomSuffixIcon.parameters = {
  ...CustomSuffixIcon.parameters,
  docs: {
    ...CustomSuffixIcon.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"Custom suffix icon\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={<SuffixIcon icon={tag} />} />;\n}",
      ...CustomSuffixIcon.parameters?.docs?.source
    }
  }
};
NoSuffix.parameters = {
  ...NoSuffix.parameters,
  docs: {
    ...NoSuffix.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"No suffix\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={null} />;\n}",
      ...NoSuffix.parameters?.docs?.source
    }
  }
};
CustomSuffix.parameters = {
  ...CustomSuffix.parameters,
  docs: {
    ...CustomSuffix.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"Custom suffix\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={<div style={{\n    background: 'red',\n    height: '100%'\n  }}>\n                    Suffix!\n                </div>} />;\n}",
      ...CustomSuffix.parameters?.docs?.source
    }
  }
};
ToggleButton.parameters = {
  ...ToggleButton.parameters,
  docs: {
    ...ToggleButton.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>();\n  return <SelectControl items={sampleItems} label=\"Has toggle button\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={null} showToggleButton={true} __experimentalOpenMenuOnFocus={true} />;\n}",
      ...ToggleButton.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Single.displayName = "Single";
    // @ts-ignore
    Single.__docgenInfo = { "description": "", "displayName": "Single", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#Single"] = { docgenInfo: Single.__docgenInfo, name: "Single", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#Single" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Multiple.displayName = "Multiple";
    // @ts-ignore
    Multiple.__docgenInfo = { "description": "", "displayName": "Multiple", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#Multiple"] = { docgenInfo: Multiple.__docgenInfo, name: "Multiple", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#Multiple" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ExternalTags.displayName = "ExternalTags";
    // @ts-ignore
    ExternalTags.__docgenInfo = { "description": "", "displayName": "ExternalTags", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#ExternalTags"] = { docgenInfo: ExternalTags.__docgenInfo, name: "ExternalTags", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#ExternalTags" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    FuzzyMatching.displayName = "FuzzyMatching";
    // @ts-ignore
    FuzzyMatching.__docgenInfo = { "description": "", "displayName": "FuzzyMatching", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#FuzzyMatching"] = { docgenInfo: FuzzyMatching.__docgenInfo, name: "FuzzyMatching", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#FuzzyMatching" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Async.displayName = "Async";
    // @ts-ignore
    Async.__docgenInfo = { "description": "", "displayName": "Async", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#Async"] = { docgenInfo: Async.__docgenInfo, name: "Async", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#Async" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    AsyncWithoutListeningFilterEvents.displayName = "AsyncWithoutListeningFilterEvents";
    // @ts-ignore
    AsyncWithoutListeningFilterEvents.__docgenInfo = { "description": "", "displayName": "AsyncWithoutListeningFilterEvents", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#AsyncWithoutListeningFilterEvents"] = { docgenInfo: AsyncWithoutListeningFilterEvents.__docgenInfo, name: "AsyncWithoutListeningFilterEvents", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#AsyncWithoutListeningFilterEvents" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    CustomRender.displayName = "CustomRender";
    // @ts-ignore
    CustomRender.__docgenInfo = { "description": "", "displayName": "CustomRender", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomRender"] = { docgenInfo: CustomRender.__docgenInfo, name: "CustomRender", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomRender" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    CustomItemType.displayName = "CustomItemType";
    // @ts-ignore
    CustomItemType.__docgenInfo = { "description": "", "displayName": "CustomItemType", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomItemType"] = { docgenInfo: CustomItemType.__docgenInfo, name: "CustomItemType", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomItemType" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    SingleWithinModalUsingBodyDropdownPlacement.displayName = "SingleWithinModalUsingBodyDropdownPlacement";
    // @ts-ignore
    SingleWithinModalUsingBodyDropdownPlacement.__docgenInfo = { "description": "", "displayName": "SingleWithinModalUsingBodyDropdownPlacement", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#SingleWithinModalUsingBodyDropdownPlacement"] = { docgenInfo: SingleWithinModalUsingBodyDropdownPlacement.__docgenInfo, name: "SingleWithinModalUsingBodyDropdownPlacement", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#SingleWithinModalUsingBodyDropdownPlacement" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    DefaultSuffix.displayName = "DefaultSuffix";
    // @ts-ignore
    DefaultSuffix.__docgenInfo = { "description": "", "displayName": "DefaultSuffix", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#DefaultSuffix"] = { docgenInfo: DefaultSuffix.__docgenInfo, name: "DefaultSuffix", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#DefaultSuffix" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    CustomSuffixIcon.displayName = "CustomSuffixIcon";
    // @ts-ignore
    CustomSuffixIcon.__docgenInfo = { "description": "", "displayName": "CustomSuffixIcon", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomSuffixIcon"] = { docgenInfo: CustomSuffixIcon.__docgenInfo, name: "CustomSuffixIcon", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomSuffixIcon" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    NoSuffix.displayName = "NoSuffix";
    // @ts-ignore
    NoSuffix.__docgenInfo = { "description": "", "displayName": "NoSuffix", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#NoSuffix"] = { docgenInfo: NoSuffix.__docgenInfo, name: "NoSuffix", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#NoSuffix" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    CustomSuffix.displayName = "CustomSuffix";
    // @ts-ignore
    CustomSuffix.__docgenInfo = { "description": "", "displayName": "CustomSuffix", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomSuffix"] = { docgenInfo: CustomSuffix.__docgenInfo, name: "CustomSuffix", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#CustomSuffix" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ToggleButton.displayName = "ToggleButton";
    // @ts-ignore
    ToggleButton.__docgenInfo = { "description": "", "displayName": "ToggleButton", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#ToggleButton"] = { docgenInfo: ToggleButton.__docgenInfo, name: "ToggleButton", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#ToggleButton" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);