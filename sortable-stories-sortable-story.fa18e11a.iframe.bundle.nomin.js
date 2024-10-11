"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5264],{

/***/ "../../packages/js/components/src/list-item/list-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  c: () => (/* binding */ ListItem)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable-handle.tsx + 1 modules
var sortable_handle = __webpack_require__("../../packages/js/components/src/sortable/sortable-handle.tsx");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/draggable/index.js
var draggable = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/draggable/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/sortable/sortable-item.tsx













var _excluded = ["id", "children", "className", "isDragging", "isSelected", "onDragStart", "onDragEnd", "role"];

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

var SortableItem = function SortableItem(_ref) {
  var id = _ref.id,
    children = _ref.children,
    className = _ref.className,
    _ref$isDragging = _ref.isDragging,
    isDragging = _ref$isDragging === void 0 ? false : _ref$isDragging,
    _ref$isSelected = _ref.isSelected,
    isSelected = _ref$isSelected === void 0 ? false : _ref$isSelected,
    _ref$onDragStart = _ref.onDragStart,
    onDragStart = _ref$onDragStart === void 0 ? function () {
      return null;
    } : _ref$onDragStart,
    _ref$onDragEnd = _ref.onDragEnd,
    onDragEnd = _ref$onDragEnd === void 0 ? function () {
      return null;
    } : _ref$onDragEnd,
    _ref$role = _ref.role,
    role = _ref$role === void 0 ? 'listitem' : _ref$role,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  var ref = (0,react.useRef)(null);
  var sortableContext = (0,react.useContext)(sortable/* SortableContext */.g);
  var handleDragStart = function handleDragStart(event) {
    onDragStart(event);
  };
  var handleDragEnd = function handleDragEnd(event) {
    event.preventDefault();
    onDragEnd(event);
  };
  (0,react.useEffect)(function () {
    if (isSelected && ref.current) {
      ref.current.focus();
    }
  }, [isSelected]);
  return (0,react.createElement)("div", (0,esm_extends/* default */.A)({}, props, {
    "aria-selected": isSelected,
    className: classnames_default()('woocommerce-sortable__item', className, {
      'is-dragging': isDragging,
      'is-selected': isSelected
    }),
    id: "woocommerce-sortable__item-".concat(id),
    role: role,
    onDrop: function onDrop(event) {
      return event.preventDefault();
    },
    ref: ref,
    tabIndex: isSelected ? 0 : -1
    // eslint-disable-next-line jsx-a11y/aria-props
    ,

    "aria-description": (0,build_module.__)('Press spacebar to reorder', 'woocommerce')
  }), (0,react.createElement)(draggable/* default */.A, {
    elementId: "woocommerce-sortable__item-".concat(id),
    transferData: {},
    onDragStart: handleDragStart,
    onDragEnd: handleDragEnd
  }, function (_ref2) {
    var onDraggableStart = _ref2.onDraggableStart,
      onDraggableEnd = _ref2.onDraggableEnd;
    return (0,react.createElement)(sortable/* SortableContext */.g.Provider, {
      value: _objectSpread(_objectSpread({}, sortableContext), {}, {
        onDragStart: onDraggableStart,
        onDragEnd: onDraggableEnd
      })
    }, children);
  }));
};
try {
    // @ts-ignore
    SortableItem.displayName = "SortableItem";
    // @ts-ignore
    SortableItem.__docgenInfo = { "description": "", "displayName": "SortableItem", "props": { "index": { "defaultValue": null, "description": "", "name": "index", "required": true, "type": { "name": "number" } }, "isDragging": { "defaultValue": { value: "false" }, "description": "", "name": "isDragging", "required": false, "type": { "name": "boolean" } }, "isSelected": { "defaultValue": { value: "false" }, "description": "", "name": "isSelected", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable-item.tsx#SortableItem"] = { docgenInfo: SortableItem.__docgenInfo, name: "SortableItem", path: "../../packages/js/components/src/sortable/sortable-item.tsx#SortableItem" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/list-item/list-item.tsx


var list_item_excluded = ["children", "className", "index", "onDragStart", "onDragEnd"];

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


var ListItem = function ListItem(_ref) {
  var children = _ref.children,
    className = _ref.className,
    _ref$index = _ref.index,
    index = _ref$index === void 0 ? 0 : _ref$index,
    onDragStart = _ref.onDragStart,
    onDragEnd = _ref.onDragEnd,
    props = (0,objectWithoutProperties/* default */.A)(_ref, list_item_excluded);
  var isDraggable = onDragEnd && onDragStart;
  return (0,react.createElement)(SortableItem, (0,esm_extends/* default */.A)({}, props, {
    index: index,
    className: classnames_default()('woocommerce-list-item', className)
  }), isDraggable && (0,react.createElement)(sortable_handle/* SortableHandle */.D, null), children);
};
try {
    // @ts-ignore
    ListItem.displayName = "ListItem";
    // @ts-ignore
    ListItem.__docgenInfo = { "description": "", "displayName": "ListItem", "props": { "isDragging": { "defaultValue": null, "description": "", "name": "isDragging", "required": false, "type": { "name": "boolean" } }, "isSelected": { "defaultValue": null, "description": "", "name": "isSelected", "required": false, "type": { "name": "boolean" } }, "index": { "defaultValue": { value: "0" }, "description": "", "name": "index", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/list-item/list-item.tsx#ListItem"] = { docgenInfo: ListItem.__docgenInfo, name: "ListItem", path: "../../packages/js/components/src/list-item/list-item.tsx#ListItem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/sortable-handle.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  D: () => (/* binding */ SortableHandle)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/sortable/draggable-icon.tsx

/**
 * External dependencies
 */

var DraggableIcon = function DraggableIcon() {
  return (0,react.createElement)("svg", {
    width: "8",
    height: "14",
    viewBox: "0 0 8 14",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, (0,react.createElement)("rect", {
    width: "2",
    height: "2",
    fill: "#757575"
  }), (0,react.createElement)("rect", {
    y: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), (0,react.createElement)("rect", {
    y: "12",
    width: "2",
    height: "2",
    fill: "#757575"
  }), (0,react.createElement)("rect", {
    x: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), (0,react.createElement)("rect", {
    x: "6",
    y: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), (0,react.createElement)("rect", {
    x: "6",
    y: "12",
    width: "2",
    height: "2",
    fill: "#757575"
  }));
};
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/sortable/sortable-handle.tsx

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var SortableHandle = function SortableHandle(_ref) {
  var children = _ref.children,
    itemIndex = _ref.itemIndex;
  var _useContext = (0,react.useContext)(sortable/* SortableContext */.g),
    onDragStart = _useContext.onDragStart,
    onDragEnd = _useContext.onDragEnd;
  return (0,react.createElement)("div", {
    className: "woocommerce-sortable__handle",
    draggable: true,
    onDragStart: onDragStart,
    onDragEnd: onDragEnd,
    "data-index": itemIndex
  }, children ? children : (0,react.createElement)(DraggableIcon, null));
};
try {
    // @ts-ignore
    SortableHandle.displayName = "SortableHandle";
    // @ts-ignore
    SortableHandle.__docgenInfo = { "description": "", "displayName": "SortableHandle", "props": { "itemIndex": { "defaultValue": null, "description": "", "name": "itemIndex", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable-handle.tsx#SortableHandle"] = { docgenInfo: SortableHandle.__docgenInfo, name: "SortableHandle", path: "../../packages/js/components/src/sortable/sortable-handle.tsx#SortableHandle" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/sortable.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   L: () => (/* binding */ Sortable),
/* harmony export */   g: () => (/* binding */ SortableContext)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js");
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_index_of_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
/* harmony import */ var core_js_modules_es_array_index_of_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_index_of_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_a11y__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+a11y@3.6.1/node_modules/@wordpress/a11y/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var uuid__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/v4.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../packages/js/components/src/sortable/utils.ts");



var _excluded = ["children", "isHorizontal", "onDragEnd", "onDragOver", "onDragStart", "onOrderChange", "className", "role"];






/**
 * External dependencies
 */







/**
 * Internal dependencies
 */

var THROTTLE_TIME = 16;
var SortableContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.createContext)({});
var Sortable = function Sortable(_ref) {
  var children = _ref.children,
    _ref$isHorizontal = _ref.isHorizontal,
    isHorizontal = _ref$isHorizontal === void 0 ? false : _ref$isHorizontal,
    _ref$onDragEnd = _ref.onDragEnd,
    onDragEnd = _ref$onDragEnd === void 0 ? function () {
      return null;
    } : _ref$onDragEnd,
    _ref$onDragOver = _ref.onDragOver,
    onDragOver = _ref$onDragOver === void 0 ? function () {
      return null;
    } : _ref$onDragOver,
    _ref$onDragStart = _ref.onDragStart,
    onDragStart = _ref$onDragStart === void 0 ? function () {
      return null;
    } : _ref$onDragStart,
    _ref$onOrderChange = _ref.onOrderChange,
    onOrderChange = _ref$onOrderChange === void 0 ? function () {
      return null;
    } : _ref$onOrderChange,
    className = _ref.className,
    _ref$role = _ref.role,
    role = _ref$role === void 0 ? 'listbox' : _ref$role,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(_ref, _excluded);
  var ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.useRef)(null);
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.useState)([]),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A)(_useState, 2),
    items = _useState2[0],
    setItems = _useState2[1];
  var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.useState)(-1),
    _useState4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A)(_useState3, 2),
    selectedIndex = _useState4[0],
    setSelectedIndex = _useState4[1];
  var _useState5 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.useState)(null),
    _useState6 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A)(_useState5, 2),
    dragIndex = _useState6[0],
    setDragIndex = _useState6[1];
  var _useState7 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.useState)(null),
    _useState8 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A)(_useState7, 2),
    dropIndex = _useState8[0],
    setDropIndex = _useState8[1];
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.useEffect)(function () {
    if (!children) {
      return;
    }
    setItems(Array.isArray(children) ? children : [children]);
  }, [children]);
  var resetIndexes = function resetIndexes() {
    setTimeout(function () {
      setDragIndex(null);
      setDropIndex(null);
    }, THROTTLE_TIME);
  };
  var persistItemOrder = function persistItemOrder() {
    if (dropIndex !== null && dragIndex !== null && dropIndex !== dragIndex) {
      var nextItems = (0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .moveIndex */ .e6)(dragIndex, dropIndex, items);
      setItems(nextItems);
      onOrderChange(nextItems);
    }
    resetIndexes();
  };
  var handleDragStart = function handleDragStart(event, index) {
    setDropIndex(index);
    setDragIndex(index);
    onDragStart(event);
  };
  var handleDragEnd = function handleDragEnd(event) {
    persistItemOrder();
    onDragEnd(event);
  };
  var handleDragOver = function handleDragOver(event, index) {
    if (dragIndex === null) {
      return;
    }

    // Items before the current item cause a one off error when
    // removed from the old array and spliced into the new array.
    // TODO: Issue with dragging into same position having to do with isBefore returning true initially.
    var targetIndex = dragIndex < index ? index : index + 1;
    if ((0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .isBefore */ .Y8)(event, isHorizontal)) {
      targetIndex--;
    }
    setDropIndex(targetIndex);
    onDragOver(event);
  };
  var throttledHandleDragOver = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.useCallback)((0,lodash__WEBPACK_IMPORTED_MODULE_8__.throttle)(handleDragOver, THROTTLE_TIME), [dragIndex]);
  var handleKeyDown = function handleKeyDown(event) {
    var key = event.key;
    var isSelecting = dragIndex === null || dropIndex === null;
    var selectedLabel = (0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .getItemName */ .H0)(ref.current, selectedIndex);

    // Select or drop on spacebar press.
    if (key === ' ') {
      if (isSelecting) {
        (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_7__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /** Translators: Selected item label */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('%s selected, use up and down arrow keys to reorder', 'woocommerce'), selectedLabel), 'assertive');
        setDragIndex(selectedIndex);
        setDropIndex(selectedIndex);
        return;
      }
      setSelectedIndex(dropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_7__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('%1$s dropped, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel, dropIndex + 1, items.length), 'assertive');
      persistItemOrder();
      return;
    }
    if (key === 'ArrowUp') {
      if (isSelecting) {
        setSelectedIndex((0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .getPreviousIndex */ .S1)(selectedIndex, items.length));
        return;
      }
      var previousDropIndex = (0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .getPreviousIndex */ .S1)(dropIndex, items.length);
      setDropIndex(previousDropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_7__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('%1$s, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel, previousDropIndex + 1, items.length), 'assertive');
      return;
    }
    if (key === 'ArrowDown') {
      if (isSelecting) {
        setSelectedIndex((0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .getNextIndex */ .g0)(selectedIndex, items.length));
        return;
      }
      var nextDropIndex = (0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .getNextIndex */ .g0)(dropIndex, items.length);
      setDropIndex(nextDropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_7__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('%1$s, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel, nextDropIndex + 1, items.length), 'assertive');
      return;
    }
    if (key === 'Escape') {
      resetIndexes();
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_7__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Reordering cancelled. Restoring the original list order', 'woocommerce'), 'assertive');
    }
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.createElement)(SortableContext.Provider, {
    value: {}
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.createElement)("div", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)({}, props, {
    className: classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-sortable', className, {
      'is-dragging': dragIndex !== null,
      'is-horizontal': isHorizontal
    }),
    ref: ref,
    role: role
  }), items.map(function (child, index) {
    var isDragging = index === dragIndex;
    if (child.props.className && child.props.className.indexOf('non-sortable-item') !== -1) {
      return child;
    }
    var itemClasses = classnames__WEBPACK_IMPORTED_MODULE_6___default()(child.props.className, {
      'is-dragging-over-after': (0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .isDraggingOverAfter */ .Km)(index, dragIndex, dropIndex),
      'is-dragging-over-before': (0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .isDraggingOverBefore */ .PZ)(index, dragIndex, dropIndex),
      'is-last-droppable': (0,_utils__WEBPACK_IMPORTED_MODULE_12__/* .isLastDroppable */ .Ib)(index, dragIndex, items.length)
    });
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_9__.cloneElement)(child, {
      key: child.key || index,
      className: itemClasses,
      id: "".concat(index, "-").concat((0,uuid__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A)()),
      index: index,
      isDragging: isDragging,
      isSelected: selectedIndex === index,
      onDragEnd: handleDragEnd,
      onDragStart: function onDragStart(event) {
        return handleDragStart(event, index);
      },
      onDragOver: function onDragOver(event) {
        event.preventDefault();
        throttledHandleDragOver(event, index);
      },
      onKeyDown: function onKeyDown(event) {
        return handleKeyDown(event);
      }
    });
  })));
};
try {
    // @ts-ignore
    Sortable.displayName = "Sortable";
    // @ts-ignore
    Sortable.__docgenInfo = { "description": "", "displayName": "Sortable", "props": { "isHorizontal": { "defaultValue": { value: "false" }, "description": "", "name": "isHorizontal", "required": false, "type": { "name": "boolean" } }, "onOrderChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onOrderChange", "required": false, "type": { "name": "((items: Element[]) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable.tsx#Sortable"] = { docgenInfo: Sortable.__docgenInfo, name: "Sortable", path: "../../packages/js/components/src/sortable/sortable.tsx#Sortable" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/utils.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H0: () => (/* binding */ getItemName),
/* harmony export */   Ib: () => (/* binding */ isLastDroppable),
/* harmony export */   Km: () => (/* binding */ isDraggingOverAfter),
/* harmony export */   PZ: () => (/* binding */ isDraggingOverBefore),
/* harmony export */   S1: () => (/* binding */ getPreviousIndex),
/* harmony export */   Y8: () => (/* binding */ isBefore),
/* harmony export */   e6: () => (/* binding */ moveIndex),
/* harmony export */   g0: () => (/* binding */ getNextIndex)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
/* harmony import */ var core_js_modules_es_array_splice_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.splice.js");
/* harmony import */ var core_js_modules_es_array_splice_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_splice_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");


/**
 * External dependencies
 */

/**
 * Move an item from an index in an array to a new index.s
 *
 * @param fromIndex Index to move the item from.
 * @param toIndex   Index to move the item to.
 * @param arr       The array to copy.
 * @return array
 */
var moveIndex = function moveIndex(fromIndex, toIndex, arr) {
  var newArr = (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(arr);
  var item = arr[fromIndex];
  newArr.splice(fromIndex, 1);
  newArr.splice(toIndex, 0, item);
  return newArr;
};

/**
 * Check whether the mouse is over the first half of the event target.
 *
 * @param event        Drag event.
 * @param isHorizontal Check horizontally or vertically.
 * @return boolean
 */
var isBefore = function isBefore(event) {
  var isHorizontal = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  var target = event.target;
  if (isHorizontal) {
    var _middle = target.offsetWidth / 2;
    var _rect = target.getBoundingClientRect();
    var relativeX = event.clientX - _rect.left;
    return relativeX < _middle;
  }
  var middle = target.offsetHeight / 2;
  var rect = target.getBoundingClientRect();
  var relativeY = event.clientY - rect.top;
  return relativeY < middle;
};
var isDraggingOverAfter = function isDraggingOverAfter(index, dragIndex, dropIndex) {
  if (dragIndex === null) {
    return false;
  }
  if (dragIndex < index) {
    return dropIndex === index;
  }
  return dropIndex === index + 1;
};
var isDraggingOverBefore = function isDraggingOverBefore(index, dragIndex, dropIndex) {
  if (dragIndex === null) {
    return false;
  }
  if (dragIndex < index) {
    return dropIndex === index - 1;
  }
  return dropIndex === index;
};
var isLastDroppable = function isLastDroppable(index, dragIndex, itemCount) {
  if (dragIndex === index) {
    return false;
  }
  if (index === itemCount - 1) {
    return true;
  }
  if (dragIndex === itemCount - 1 && index === itemCount - 2) {
    return true;
  }
  return false;
};
var getNextIndex = function getNextIndex(currentIndex, itemCount) {
  var index = currentIndex + 1;
  if (index > itemCount - 1) {
    index = 0;
  }
  return index;
};
var getPreviousIndex = function getPreviousIndex(currentIndex, itemCount) {
  var index = currentIndex - 1;
  if (index < 0) {
    index = itemCount - 1;
  }
  return index;
};
var getItemName = function getItemName(parentNode, index) {
  var listItemNode = parentNode === null || parentNode === void 0 ? void 0 : parentNode.childNodes[index];
  if (index === null || !listItemNode) {
    return null;
  }
  if (listItemNode.querySelector('[aria-label]')) {
    var _listItemNode$querySe;
    return (_listItemNode$querySe = listItemNode.querySelector('[aria-label]')) === null || _listItemNode$querySe === void 0 ? void 0 : _listItemNode$querySe.ariaLabel;
  }
  if (listItemNode.textContent) {
    return listItemNode.textContent;
  }
  if (listItemNode.querySelector('[alt]')) {
    return listItemNode.querySelector('[alt]').alt;
  }
  return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Item', 'woocommerce');
};

/***/ }),

/***/ "../../packages/js/components/src/sortable/stories/sortable.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   CustomHandle: () => (/* binding */ CustomHandle),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/wordpress.js");
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../packages/js/components/src/sortable/sortable-handle.tsx");
/* harmony import */ var _list_item__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../packages/js/components/src/list-item/list-item.tsx");


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


var Basic = function Basic() {
  return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(___WEBPACK_IMPORTED_MODULE_2__/* .Sortable */ .L, {
    onOrderChange: function onOrderChange(items) {
      return (
        // eslint-disable-next-line no-console
        console.log('Order changed: ' + items.map(function (item) {
          return item.key;
        }))
      );
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_list_item__WEBPACK_IMPORTED_MODULE_3__/* .ListItem */ .c, {
    key: 'item-1'
  }, "Item 1"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_list_item__WEBPACK_IMPORTED_MODULE_3__/* .ListItem */ .c, {
    key: 'item-2'
  }, "Item 2"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_list_item__WEBPACK_IMPORTED_MODULE_3__/* .ListItem */ .c, {
    key: 'item-3'
  }, "Item 3"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_list_item__WEBPACK_IMPORTED_MODULE_3__/* .ListItem */ .c, {
    key: 'item-4'
  }, "Item 4"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_list_item__WEBPACK_IMPORTED_MODULE_3__/* .ListItem */ .c, {
    key: 'item-5'
  }, "Item 5"));
};
var CustomHandle = function CustomHandle() {
  var CustomListItem = function CustomListItem(_ref) {
    var children = _ref.children;
    return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(react__WEBPACK_IMPORTED_MODULE_1__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(___WEBPACK_IMPORTED_MODULE_4__/* .SortableHandle */ .D, null, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A,
      size: 16
    })), children);
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(___WEBPACK_IMPORTED_MODULE_2__/* .Sortable */ .L, null, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(CustomListItem, {
    key: "item-1"
  }, "Item 1"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(CustomListItem, {
    key: "item-2"
  }, "Item 2"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(CustomListItem, {
    key: "item-3"
  }, "Item 3"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(CustomListItem, {
    key: "item-4"
  }, "Item 4"), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(CustomListItem, {
    key: "item-5"
  }, "Item 5"));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/components/Sortable',
  component: ___WEBPACK_IMPORTED_MODULE_2__/* .Sortable */ .L
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <Sortable onOrderChange={items =>\n  // eslint-disable-next-line no-console\n  console.log('Order changed: ' + items.map(item => item.key))}>\n            <ListItem key={'item-1'}>Item 1</ListItem>\n            <ListItem key={'item-2'}>Item 2</ListItem>\n            <ListItem key={'item-3'}>Item 3</ListItem>\n            <ListItem key={'item-4'}>Item 4</ListItem>\n            <ListItem key={'item-5'}>Item 5</ListItem>\n        </Sortable>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
CustomHandle.parameters = {
  ...CustomHandle.parameters,
  docs: {
    ...CustomHandle.parameters?.docs,
    source: {
      originalSource: "() => {\n  type CustomListItemProps = {\n    children: React.ReactNode;\n    onDragEnd?: DragEventHandler<Element>;\n    onDragStart?: DragEventHandler<Element>;\n  };\n  const CustomListItem = ({\n    children\n  }: CustomListItemProps) => {\n    return <>\n                <SortableHandle>\n                    <Icon icon={wordpress} size={16} />\n                </SortableHandle>\n                {children}\n            </>;\n  };\n  return <Sortable>\n            <CustomListItem key=\"item-1\">Item 1</CustomListItem>\n            <CustomListItem key=\"item-2\">Item 2</CustomListItem>\n            <CustomListItem key=\"item-3\">Item 3</CustomListItem>\n            <CustomListItem key=\"item-4\">Item 4</CustomListItem>\n            <CustomListItem key=\"item-5\">Item 5</CustomListItem>\n        </Sortable>;\n}",
      ...CustomHandle.parameters?.docs?.source
    }
  }
};

/***/ })

}]);