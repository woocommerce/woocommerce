"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3585],{

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

/***/ "../../packages/js/components/src/pill/pill.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ Pill)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _experimental__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/experimental.js");
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

function Pill(_ref) {
  var children = _ref.children,
    _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_experimental__WEBPACK_IMPORTED_MODULE_2__/* .Text */ .E, {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-pill', className),
    variant: "caption",
    as: "span",
    size: "12",
    lineHeight: "16px"
  }, children);
}

/***/ }),

/***/ "../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   I: () => (/* binding */ MockMediaUpload)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/modal/index.js");




/**
 * External dependencies
 */



var MockMediaUpload = function MockMediaUpload(_ref) {
  var onSelect = _ref.onSelect,
    render = _ref.render;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(_useState, 2),
    isOpen = _useState2[0],
    setOpen = _useState2[1];
  return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(react__WEBPACK_IMPORTED_MODULE_1__.Fragment, null, render({
    open: function open() {
      return setOpen(true);
    }
  }), isOpen && (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
    title: "Media Modal",
    onRequestClose: function onRequestClose(event) {
      setOpen(false);
      event.stopPropagation();
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("p", null, "Use the default built-in", ' ', (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("code", null, "MediaUploadComponent"), " prop to render the WP Media Modal."), Array.apply(void 0, (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(Array(3))).map(function (n, i) {
    return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("button", {
      key: i,
      onClick: function onClick(event) {
        onSelect({
          alt: 'Random',
          url: "https://picsum.photos/200?i=".concat(i)
        });
        setOpen(false);
        event.stopPropagation();
      },
      style: {
        marginRight: '16px'
      }
    }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("img", {
      src: "https://picsum.photos/200?i=".concat(i),
      alt: "Random",
      style: {
        maxWidth: '100px'
      }
    }));
  })));
};
try {
    // @ts-ignore
    MockMediaUpload.displayName = "MockMediaUpload";
    // @ts-ignore
    MockMediaUpload.__docgenInfo = { "description": "", "displayName": "MockMediaUpload", "props": { "onSelect": { "defaultValue": null, "description": "", "name": "onSelect", "required": true, "type": { "name": "any" } }, "render": { "defaultValue": null, "description": "", "name": "render", "required": true, "type": { "name": "any" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx#MockMediaUpload"] = { docgenInfo: MockMediaUpload.__docgenInfo, name: "MockMediaUpload", path: "../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx#MockMediaUpload" };
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

/***/ "../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  Columns: () => (/* binding */ Columns),
  Cover: () => (/* binding */ Cover),
  "default": () => (/* binding */ image_gallery_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.repeat.js
var es_string_repeat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.repeat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+media-utils@3.4.1/node_modules/@wordpress/media-utils/build-module/index.js + 5 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+media-utils@3.4.1/node_modules/@wordpress/media-utils/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/utils.ts
var utils = __webpack_require__("../../packages/js/components/src/sortable/utils.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/image-gallery/image-gallery-wrapper.tsx

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var ImageGalleryWrapper = function ImageGalleryWrapper(_ref) {
  var children = _ref.children,
    _ref$allowDragging = _ref.allowDragging,
    allowDragging = _ref$allowDragging === void 0 ? true : _ref$allowDragging,
    _ref$onDragStart = _ref.onDragStart,
    _onDragStart = _ref$onDragStart === void 0 ? function () {
      return null;
    } : _ref$onDragStart,
    _ref$onDragEnd = _ref.onDragEnd,
    _onDragEnd = _ref$onDragEnd === void 0 ? function () {
      return null;
    } : _ref$onDragEnd,
    _ref$onDragOver = _ref.onDragOver,
    onDragOver = _ref$onDragOver === void 0 ? function () {
      return null;
    } : _ref$onDragOver,
    _ref$updateOrderedChi = _ref.updateOrderedChildren,
    updateOrderedChildren = _ref$updateOrderedChi === void 0 ? function () {
      return null;
    } : _ref$updateOrderedChi;
  if (allowDragging) {
    return (0,react.createElement)(sortable/* Sortable */.L, {
      isHorizontal: true,
      onOrderChange: function onOrderChange(items) {
        updateOrderedChildren(items);
      },
      onDragStart: function onDragStart(event) {
        _onDragStart(event);
      },
      onDragEnd: function onDragEnd(event) {
        _onDragEnd(event);
      },
      onDragOver: onDragOver
    }, children);
  }
  return (0,react.createElement)("div", {
    className: "woocommerce-image-gallery__wrapper"
  }, children);
};
try {
    // @ts-ignore
    ImageGalleryWrapper.displayName = "ImageGalleryWrapper";
    // @ts-ignore
    ImageGalleryWrapper.__docgenInfo = { "description": "", "displayName": "ImageGalleryWrapper", "props": { "allowDragging": { "defaultValue": { value: "true" }, "description": "", "name": "allowDragging", "required": false, "type": { "name": "boolean" } }, "onDragStart": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragStart", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragEnd": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragEnd", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragOver": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragOver", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "updateOrderedChildren": { "defaultValue": { value: "() => null" }, "description": "", "name": "updateOrderedChildren", "required": false, "type": { "name": "((items: Element[]) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-wrapper.tsx#ImageGalleryWrapper"] = { docgenInfo: ImageGalleryWrapper.__docgenInfo, name: "ImageGalleryWrapper", path: "../../packages/js/components/src/image-gallery/image-gallery-wrapper.tsx#ImageGalleryWrapper" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-left.js
var chevron_left = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-right.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/trash.js
var trash = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/trash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar/index.js + 1 modules
var toolbar = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar-group/index.js + 2 modules
var toolbar_group = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar-group/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar-button/index.js + 1 modules
var toolbar_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar-button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar-item/index.js
var toolbar_item = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/toolbar-item/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable-handle.tsx + 1 modules
var sortable_handle = __webpack_require__("../../packages/js/components/src/sortable/sortable-handle.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown-menu/index.js + 1 modules
var dropdown_menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown-menu/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/menu-group/index.js
var menu_group = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/menu-group/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/menu-item/index.js
var menu_item = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/menu-item/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/more-vertical.js
var more_vertical = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/more-vertical.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/image-gallery/image-gallery-toolbar-dropdown.tsx


var _excluded = ["children", "onReplace", "onRemove", "canRemove", "removeBlockLabel", "MediaUploadComponent"];


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */

var POPOVER_PROPS = {
  className: 'woocommerce-image-gallery__toolbar-dropdown-popover',
  placement: 'bottom-start'
};
function ImageGalleryToolbarDropdown(_ref) {
  var children = _ref.children,
    onReplace = _ref.onReplace,
    onRemove = _ref.onRemove,
    canRemove = _ref.canRemove,
    removeBlockLabel = _ref.removeBlockLabel,
    _ref$MediaUploadCompo = _ref.MediaUploadComponent,
    MediaUploadComponent = _ref$MediaUploadCompo === void 0 ? build_module/* MediaUpload */.Q : _ref$MediaUploadCompo,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  return (0,react.createElement)(dropdown_menu/* default */.A, (0,esm_extends/* default */.A)({
    icon: more_vertical/* default */.A,
    label: (0,i18n_build_module.__)('Options', 'woocommerce'),
    className: "woocommerce-image-gallery__toolbar-dropdown",
    popoverProps: POPOVER_PROPS
  }, props), function (_ref2) {
    var onClose = _ref2.onClose;
    return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(menu_group/* default */.A, null, (0,react.createElement)(MediaUploadComponent, {
      onSelect: function onSelect(media) {
        onReplace(media);
        onClose();
      },
      allowedTypes: ['image'],
      render: function render(_ref3) {
        var open = _ref3.open;
        return (0,react.createElement)(menu_item/* default */.A, {
          onClick: function onClick() {
            open();
          }
        }, (0,i18n_build_module.__)('Replace', 'woocommerce'));
      }
    })), typeof children === 'function' ? children({
      onClose: onClose
    }) : react.Children.map(children, function (child) {
      return (0,react.isValidElement)(child) && (0,react.cloneElement)(child, {
        onClose: onClose
      });
    }), canRemove && (0,react.createElement)(menu_group/* default */.A, null, (0,react.createElement)(menu_item/* default */.A, {
      onClick: function onClick() {
        onClose();
        onRemove();
      }
    }, removeBlockLabel || (0,i18n_build_module.__)('Remove', 'woocommerce'))));
  });
}
try {
    // @ts-ignore
    ImageGalleryToolbarDropdown.displayName = "ImageGalleryToolbarDropdown";
    // @ts-ignore
    ImageGalleryToolbarDropdown.__docgenInfo = { "description": "", "displayName": "ImageGalleryToolbarDropdown", "props": { "onReplace": { "defaultValue": null, "description": "", "name": "onReplace", "required": true, "type": { "name": "(media: { id: number; } & MediaItem) => void" } }, "onRemove": { "defaultValue": null, "description": "", "name": "onRemove", "required": true, "type": { "name": "() => void" } }, "canRemove": { "defaultValue": null, "description": "", "name": "canRemove", "required": false, "type": { "name": "boolean" } }, "removeBlockLabel": { "defaultValue": null, "description": "", "name": "removeBlockLabel", "required": false, "type": { "name": "string" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": true, "type": { "name": "MediaUploadComponentType" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-toolbar-dropdown.tsx#ImageGalleryToolbarDropdown"] = { docgenInfo: ImageGalleryToolbarDropdown.__docgenInfo, name: "ImageGalleryToolbarDropdown", path: "../../packages/js/components/src/image-gallery/image-gallery-toolbar-dropdown.tsx#ImageGalleryToolbarDropdown" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/image-gallery/image-gallery-toolbar.tsx


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


var ImageGalleryToolbar = function ImageGalleryToolbar(_ref) {
  var childIndex = _ref.childIndex,
    _ref$allowDragging = _ref.allowDragging,
    allowDragging = _ref$allowDragging === void 0 ? true : _ref$allowDragging,
    moveItem = _ref.moveItem,
    removeItem = _ref.removeItem,
    replaceItem = _ref.replaceItem,
    setToolBarItem = _ref.setToolBarItem,
    lastChild = _ref.lastChild,
    value = _ref.value,
    _ref$MediaUploadCompo = _ref.MediaUploadComponent,
    MediaUploadComponent = _ref$MediaUploadCompo === void 0 ? build_module/* MediaUpload */.Q : _ref$MediaUploadCompo;
  var moveNext = function moveNext() {
    moveItem(childIndex, childIndex + 1);
  };
  var movePrevious = function movePrevious() {
    moveItem(childIndex, childIndex - 1);
  };
  var setAsCoverImage = function setAsCoverImage(coverIndex) {
    moveItem(coverIndex, 0);
    setToolBarItem(null);
  };
  var isCoverItem = childIndex === 0;
  return (0,react.createElement)("div", {
    className: "woocommerce-image-gallery__toolbar"
  }, (0,react.createElement)(toolbar/* default */.A, {
    onClick: function onClick(e) {
      return e.stopPropagation();
    },
    label: (0,i18n_build_module.__)('Options', 'woocommerce'),
    id: "options-toolbar"
  }, !isCoverItem && (0,react.createElement)(toolbar_group/* default */.A, null, allowDragging && (0,react.createElement)(toolbar_button/* default */.A, {
    icon: function icon() {
      return (0,react.createElement)(sortable_handle/* SortableHandle */.D, {
        itemIndex: childIndex
      });
    },
    label: (0,i18n_build_module.__)('Drag to reorder', 'woocommerce')
  }), (0,react.createElement)(toolbar_button/* default */.A, {
    disabled: childIndex < 2,
    onClick: function onClick() {
      return movePrevious();
    },
    icon: chevron_left/* default */.A,
    label: (0,i18n_build_module.__)('Move previous', 'woocommerce')
  }), (0,react.createElement)(toolbar_button/* default */.A, {
    onClick: function onClick() {
      return moveNext();
    },
    icon: chevron_right/* default */.A,
    label: (0,i18n_build_module.__)('Move next', 'woocommerce'),
    disabled: lastChild
  })), !isCoverItem && (0,react.createElement)(toolbar_group/* default */.A, null, (0,react.createElement)(toolbar_button/* default */.A, {
    onClick: function onClick() {
      return setAsCoverImage(childIndex);
    },
    label: (0,i18n_build_module.__)('Set as cover', 'woocommerce')
  }, (0,i18n_build_module.__)('Set as cover', 'woocommerce'))), isCoverItem && (0,react.createElement)(toolbar_group/* default */.A, {
    className: "woocommerce-image-gallery__toolbar-media"
  }, (0,react.createElement)(MediaUploadComponent, {
    value: value,
    onSelect: function onSelect(media) {
      return replaceItem(childIndex, media);
    },
    allowedTypes: ['image'],
    render: function render(_ref2) {
      var open = _ref2.open;
      return (0,react.createElement)(toolbar_button/* default */.A, {
        onClick: open
      }, (0,i18n_build_module.__)('Replace', 'woocommerce'));
    }
  })), isCoverItem && (0,react.createElement)(toolbar_group/* default */.A, null, (0,react.createElement)(toolbar_button/* default */.A, {
    onClick: function onClick() {
      return removeItem(childIndex);
    },
    icon: trash/* default */.A,
    label: (0,i18n_build_module.__)('Remove', 'woocommerce')
  })), !isCoverItem && (0,react.createElement)(toolbar_group/* default */.A, null, (0,react.createElement)(toolbar_item/* default */.A, null, function (toggleProps) {
    return (0,react.createElement)(ImageGalleryToolbarDropdown, (0,esm_extends/* default */.A)({
      canRemove: true,
      onRemove: function onRemove() {
        return removeItem(childIndex);
      },
      onReplace: function onReplace(media) {
        return replaceItem(childIndex, media);
      },
      MediaUploadComponent: MediaUploadComponent
    }, toggleProps));
  }))));
};
try {
    // @ts-ignore
    ImageGalleryToolbar.displayName = "ImageGalleryToolbar";
    // @ts-ignore
    ImageGalleryToolbar.__docgenInfo = { "description": "", "displayName": "ImageGalleryToolbar", "props": { "childIndex": { "defaultValue": null, "description": "", "name": "childIndex", "required": true, "type": { "name": "number" } }, "allowDragging": { "defaultValue": { value: "true" }, "description": "", "name": "allowDragging", "required": false, "type": { "name": "boolean" } }, "value": { "defaultValue": null, "description": "", "name": "value", "required": false, "type": { "name": "number" } }, "moveItem": { "defaultValue": null, "description": "", "name": "moveItem", "required": true, "type": { "name": "(fromIndex: number, toIndex: number) => void" } }, "removeItem": { "defaultValue": null, "description": "", "name": "removeItem", "required": true, "type": { "name": "(removeIndex: number) => void" } }, "replaceItem": { "defaultValue": null, "description": "", "name": "replaceItem", "required": true, "type": { "name": "(replaceIndex: number, media: { id: number; } & MediaItem) => void" } }, "setToolBarItem": { "defaultValue": null, "description": "", "name": "setToolBarItem", "required": true, "type": { "name": "(key: string | null) => void" } }, "lastChild": { "defaultValue": null, "description": "", "name": "lastChild", "required": true, "type": { "name": "boolean" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": true, "type": { "name": "MediaUploadComponentType" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-toolbar.tsx#ImageGalleryToolbar"] = { docgenInfo: ImageGalleryToolbar.__docgenInfo, name: "ImageGalleryToolbar", path: "../../packages/js/components/src/image-gallery/image-gallery-toolbar.tsx#ImageGalleryToolbar" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/image-gallery/image-gallery.tsx






/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



var ImageGallery = function ImageGallery(_ref) {
  var children = _ref.children,
    _ref$columns = _ref.columns,
    columns = _ref$columns === void 0 ? 4 : _ref$columns,
    _ref$allowDragging = _ref.allowDragging,
    allowDragging = _ref$allowDragging === void 0 ? true : _ref$allowDragging,
    _ref$onSelectAsCover = _ref.onSelectAsCover,
    onSelectAsCover = _ref$onSelectAsCover === void 0 ? function () {
      return null;
    } : _ref$onSelectAsCover,
    _ref$onOrderChange = _ref.onOrderChange,
    onOrderChange = _ref$onOrderChange === void 0 ? function () {
      return null;
    } : _ref$onOrderChange,
    _ref$onRemove = _ref.onRemove,
    onRemove = _ref$onRemove === void 0 ? function () {
      return null;
    } : _ref$onRemove,
    _ref$onReplace = _ref.onReplace,
    onReplace = _ref$onReplace === void 0 ? function () {
      return null;
    } : _ref$onReplace,
    _ref$MediaUploadCompo = _ref.MediaUploadComponent,
    MediaUploadComponent = _ref$MediaUploadCompo === void 0 ? build_module/* MediaUpload */.Q : _ref$MediaUploadCompo,
    _ref$onDragStart = _ref.onDragStart,
    _onDragStart = _ref$onDragStart === void 0 ? function () {
      return null;
    } : _ref$onDragStart,
    _ref$onDragEnd = _ref.onDragEnd,
    _onDragEnd = _ref$onDragEnd === void 0 ? function () {
      return null;
    } : _ref$onDragEnd,
    _ref$onDragOver = _ref.onDragOver,
    onDragOver = _ref$onDragOver === void 0 ? function () {
      return null;
    } : _ref$onDragOver;
  var _useState = (0,react.useState)(null),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    activeToolbarKey = _useState2[0],
    setActiveToolbarKey = _useState2[1];
  var _useState3 = (0,react.useState)(false),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    isDragging = _useState4[0],
    setIsDragging = _useState4[1];
  var childElements = (0,react.useMemo)(function () {
    return react.Children.toArray(children);
  }, [children]);
  function cloneChild(child, childIndex) {
    var key = child.key || String(childIndex);
    var isToolbarVisible = key === activeToolbarKey;
    return (0,react.cloneElement)(child, {
      key: key,
      isDraggable: allowDragging && !child.props.isCover,
      className: classnames_default()({
        'is-toolbar-visible': isToolbarVisible
      }),
      onClick: function onClick() {
        setActiveToolbarKey(isToolbarVisible ? null : key);
      },
      onBlur: function onBlur(event) {
        if (isDragging || event.currentTarget.contains(event.relatedTarget) || event.relatedTarget && event.relatedTarget.closest('.media-modal, .components-modal__frame') || event.relatedTarget &&
        // Check if not a button within the toolbar is clicked, to prevent hiding the toolbar.
        event.relatedTarget.closest('.woocommerce-image-gallery__toolbar') || event.relatedTarget &&
        // Prevent toolbar from hiding if the dropdown is clicked within the toolbar.
        event.relatedTarget.closest('.woocommerce-image-gallery__toolbar-dropdown-popover')) {
          return;
        }
        setActiveToolbarKey(null);
      }
    }, isToolbarVisible && (0,react.createElement)(ImageGalleryToolbar, {
      value: child.props.id,
      allowDragging: allowDragging,
      childIndex: childIndex,
      lastChild: childIndex === childElements.length - 1,
      moveItem: function moveItem(fromIndex, toIndex) {
        onOrderChange((0,utils/* moveIndex */.e6)(fromIndex, toIndex, childElements));
      },
      removeItem: function removeItem(removeIndex) {
        onRemove({
          removeIndex: removeIndex,
          removedItem: childElements[removeIndex]
        });
      },
      replaceItem: function replaceItem(replaceIndex, media) {
        onReplace({
          replaceIndex: replaceIndex,
          media: media
        });
      },
      setToolBarItem: function setToolBarItem(toolBarItem) {
        onSelectAsCover(activeToolbarKey);
        setActiveToolbarKey(toolBarItem);
      },
      MediaUploadComponent: MediaUploadComponent
    }));
  }
  return (0,react.createElement)("div", {
    className: "woocommerce-image-gallery",
    style: {
      gridTemplateColumns: 'min-content '.repeat(columns)
    }
  }, (0,react.createElement)(ImageGalleryWrapper, {
    allowDragging: allowDragging,
    updateOrderedChildren: onOrderChange,
    onDragStart: function onDragStart(event) {
      setIsDragging(true);
      _onDragStart(event);
    },
    onDragEnd: function onDragEnd(event) {
      setIsDragging(false);
      _onDragEnd(event);
    },
    onDragOver: onDragOver
  }, childElements.map(cloneChild)));
};
try {
    // @ts-ignore
    ImageGallery.displayName = "ImageGallery";
    // @ts-ignore
    ImageGallery.__docgenInfo = { "description": "", "displayName": "ImageGallery", "props": { "columns": { "defaultValue": { value: "4" }, "description": "", "name": "columns", "required": false, "type": { "name": "number" } }, "onRemove": { "defaultValue": { value: "() => null" }, "description": "", "name": "onRemove", "required": false, "type": { "name": "((props: { removeIndex: number; removedItem: Element; }) => void)" } }, "onReplace": { "defaultValue": { value: "() => null" }, "description": "", "name": "onReplace", "required": false, "type": { "name": "((props: { replaceIndex: number; media: { id: number; } & MediaItem; }) => void)" } }, "allowDragging": { "defaultValue": { value: "true" }, "description": "", "name": "allowDragging", "required": false, "type": { "name": "boolean" } }, "onSelectAsCover": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelectAsCover", "required": false, "type": { "name": "((itemId: string | null) => void)" } }, "onOrderChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onOrderChange", "required": false, "type": { "name": "((items: Element[]) => void)" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": false, "type": { "name": "MediaUploadComponentType" } }, "onDragStart": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragStart", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragEnd": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragEnd", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragOver": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragOver", "required": false, "type": { "name": "(DragEventHandler<HTMLLIElement> & DragEventHandler<HTMLDivElement>)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery.tsx#ImageGallery"] = { docgenInfo: ImageGallery.__docgenInfo, name: "ImageGallery", path: "../../packages/js/components/src/image-gallery/image-gallery.tsx#ImageGallery" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pill/pill.js
var pill = __webpack_require__("../../packages/js/components/src/pill/pill.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/sortable/non-sortable-item.tsx
/**
 * External dependencies
 */

var NonSortableItem = function NonSortableItem(_ref) {
  var _children$props;
  var children = _ref.children;
  if (children === null) {
    return children;
  }
  return (0,react.cloneElement)(children, {
    className: "".concat(((_children$props = children.props) === null || _children$props === void 0 ? void 0 : _children$props.className) || '', " non-sortable-item")
  });
};
try {
    // @ts-ignore
    NonSortableItem.displayName = "NonSortableItem";
    // @ts-ignore
    NonSortableItem.__docgenInfo = { "description": "", "displayName": "NonSortableItem", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/non-sortable-item.tsx#NonSortableItem"] = { docgenInfo: NonSortableItem.__docgenInfo, name: "NonSortableItem", path: "../../packages/js/components/src/sortable/non-sortable-item.tsx#NonSortableItem" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/conditional-wrapper/conditional-wrapper.tsx
var ConditionalWrapper = function ConditionalWrapper(_ref) {
  var condition = _ref.condition,
    wrapper = _ref.wrapper,
    children = _ref.children;
  return condition ? wrapper(children) : children;
};
try {
    // @ts-ignore
    ConditionalWrapper.displayName = "ConditionalWrapper";
    // @ts-ignore
    ConditionalWrapper.__docgenInfo = { "description": "", "displayName": "ConditionalWrapper", "props": { "condition": { "defaultValue": null, "description": "", "name": "condition", "required": true, "type": { "name": "boolean" } }, "wrapper": { "defaultValue": null, "description": "", "name": "wrapper", "required": true, "type": { "name": "(children: T) => Element" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/conditional-wrapper/conditional-wrapper.tsx#ConditionalWrapper"] = { docgenInfo: ConditionalWrapper.__docgenInfo, name: "ConditionalWrapper", path: "../../packages/js/components/src/conditional-wrapper/conditional-wrapper.tsx#ConditionalWrapper" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/image-gallery/image-gallery-item.tsx

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



var ImageGalleryItem = function ImageGalleryItem(_ref) {
  var id = _ref.id,
    alt = _ref.alt,
    _ref$isCover = _ref.isCover,
    isCover = _ref$isCover === void 0 ? false : _ref$isCover,
    _ref$isDraggable = _ref.isDraggable,
    isDraggable = _ref$isDraggable === void 0 ? true : _ref$isDraggable,
    src = _ref.src,
    _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    _ref$onClick = _ref.onClick,
    _onClick = _ref$onClick === void 0 ? function () {
      return null;
    } : _ref$onClick,
    _ref$onBlur = _ref.onBlur,
    _onBlur = _ref$onBlur === void 0 ? function () {
      return null;
    } : _ref$onBlur,
    children = _ref.children;
  return (0,react.createElement)(ConditionalWrapper, {
    condition: !isDraggable,
    wrapper: function wrapper(wrappedChildren) {
      return (0,react.createElement)(NonSortableItem, null, wrappedChildren);
    }
  }, (0,react.createElement)("div", {
    className: "woocommerce-image-gallery__item ".concat(className),
    onKeyPress: function onKeyPress() {},
    tabIndex: 0,
    role: "button",
    onClick: function onClick(event) {
      return _onClick(event);
    },
    onBlur: function onBlur(event) {
      return _onBlur(event);
    }
  }, children, isDraggable ? (0,react.createElement)(sortable_handle/* SortableHandle */.D, null, (0,react.createElement)("img", {
    alt: alt,
    src: src,
    id: id
  })) : (0,react.createElement)(react.Fragment, null, isCover && (0,react.createElement)(pill/* Pill */.a, null, (0,i18n_build_module.__)('Cover', 'woocommerce')), (0,react.createElement)("img", {
    alt: alt,
    src: src,
    id: id
  }))));
};
try {
    // @ts-ignore
    ImageGalleryItem.displayName = "ImageGalleryItem";
    // @ts-ignore
    ImageGalleryItem.__docgenInfo = { "description": "", "displayName": "ImageGalleryItem", "props": { "id": { "defaultValue": null, "description": "", "name": "id", "required": false, "type": { "name": "string" } }, "alt": { "defaultValue": null, "description": "", "name": "alt", "required": true, "type": { "name": "string" } }, "isCover": { "defaultValue": { value: "false" }, "description": "", "name": "isCover", "required": false, "type": { "name": "boolean" } }, "isDraggable": { "defaultValue": { value: "true" }, "description": "", "name": "isDraggable", "required": false, "type": { "name": "boolean" } }, "src": { "defaultValue": null, "description": "", "name": "src", "required": true, "type": { "name": "string" } }, "displayToolbar": { "defaultValue": null, "description": "", "name": "displayToolbar", "required": false, "type": { "name": "boolean" } }, "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "onClick": { "defaultValue": { value: "() => null" }, "description": "", "name": "onClick", "required": false, "type": { "name": "((() => void) & MouseEventHandler<HTMLDivElement>)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-item.tsx#ImageGalleryItem"] = { docgenInfo: ImageGalleryItem.__docgenInfo, name: "ImageGalleryItem", path: "../../packages/js/components/src/image-gallery/image-gallery-item.tsx#ImageGalleryItem" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx
var mock_media_uploader = __webpack_require__("../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var Basic = function Basic() {
  return (0,react.createElement)(ImageGallery, {
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    onReplace: function onReplace(_ref) {
      var replaceIndex = _ref.replaceIndex;
      return (
        // eslint-disable-next-line no-console
        console.info("Item ".concat(replaceIndex, " replaced"))
      );
    },
    onRemove: function onRemove(_ref2) {
      var removeIndex = _ref2.removeIndex;
      // eslint-disable-next-line no-console
      console.info("Item ".concat(removeIndex, " removed"));
    },
    onOrderChange: function onOrderChange() {
      // eslint-disable-next-line no-console
      console.info("Order changed");
    }
  }, (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 1",
    src: "https://picsum.photos/id/137/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 2",
    src: "https://picsum.photos/id/208/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 3",
    src: "https://picsum.photos/id/24/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 4",
    src: "https://picsum.photos/id/58/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 5",
    src: "https://picsum.photos/id/309/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 6",
    src: "https://picsum.photos/id/46/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 7",
    src: "https://picsum.photos/id/8/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 8",
    src: "https://picsum.photos/id/101/200/200"
  }));
};
var Cover = function Cover() {
  return (0,react.createElement)(ImageGallery, {
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I
  }, (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 1",
    src: "https://picsum.photos/id/137/200/200",
    isCover: true
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 2",
    src: "https://picsum.photos/id/208/200/200"
  }));
};
var Columns = function Columns() {
  return (0,react.createElement)(ImageGallery, {
    columns: 3,
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I
  }, (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 1",
    src: "https://picsum.photos/id/137/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 2",
    src: "https://picsum.photos/id/208/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 3",
    src: "https://picsum.photos/id/24/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 4",
    src: "https://picsum.photos/id/58/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 5",
    src: "https://picsum.photos/id/309/200/200"
  }), (0,react.createElement)(ImageGalleryItem, {
    alt: "Random image 6",
    src: "https://picsum.photos/id/46/200/200"
  }));
};
/* harmony default export */ const image_gallery_story = ({
  title: 'WooCommerce Admin/components/ImageGallery',
  component: ImageGallery
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <ImageGallery MediaUploadComponent={MockMediaUpload} onReplace={({\n    replaceIndex\n  }) =>\n  // eslint-disable-next-line no-console\n  console.info(`Item ${replaceIndex} replaced`)} onRemove={({\n    removeIndex\n  }) => {\n    // eslint-disable-next-line no-console\n    console.info(`Item ${removeIndex} removed`);\n  }} onOrderChange={() => {\n    // eslint-disable-next-line no-console\n    console.info(`Order changed`);\n  }}>\n            <ImageGalleryItem alt=\"Random image 1\" src=\"https://picsum.photos/id/137/200/200\" />\n            <ImageGalleryItem alt=\"Random image 2\" src=\"https://picsum.photos/id/208/200/200\" />\n            <ImageGalleryItem alt=\"Random image 3\" src=\"https://picsum.photos/id/24/200/200\" />\n            <ImageGalleryItem alt=\"Random image 4\" src=\"https://picsum.photos/id/58/200/200\" />\n            <ImageGalleryItem alt=\"Random image 5\" src=\"https://picsum.photos/id/309/200/200\" />\n            <ImageGalleryItem alt=\"Random image 6\" src=\"https://picsum.photos/id/46/200/200\" />\n            <ImageGalleryItem alt=\"Random image 7\" src=\"https://picsum.photos/id/8/200/200\" />\n            <ImageGalleryItem alt=\"Random image 8\" src=\"https://picsum.photos/id/101/200/200\" />\n        </ImageGallery>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
Cover.parameters = {
  ...Cover.parameters,
  docs: {
    ...Cover.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <ImageGallery MediaUploadComponent={MockMediaUpload}>\n            <ImageGalleryItem alt=\"Random image 1\" src=\"https://picsum.photos/id/137/200/200\" isCover />\n            <ImageGalleryItem alt=\"Random image 2\" src=\"https://picsum.photos/id/208/200/200\" />\n        </ImageGallery>;\n}",
      ...Cover.parameters?.docs?.source
    }
  }
};
Columns.parameters = {
  ...Columns.parameters,
  docs: {
    ...Columns.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <ImageGallery columns={3} MediaUploadComponent={MockMediaUpload}>\n            <ImageGalleryItem alt=\"Random image 1\" src=\"https://picsum.photos/id/137/200/200\" />\n            <ImageGalleryItem alt=\"Random image 2\" src=\"https://picsum.photos/id/208/200/200\" />\n            <ImageGalleryItem alt=\"Random image 3\" src=\"https://picsum.photos/id/24/200/200\" />\n            <ImageGalleryItem alt=\"Random image 4\" src=\"https://picsum.photos/id/58/200/200\" />\n            <ImageGalleryItem alt=\"Random image 5\" src=\"https://picsum.photos/id/309/200/200\" />\n            <ImageGalleryItem alt=\"Random image 6\" src=\"https://picsum.photos/id/46/200/200\" />\n        </ImageGallery>;\n}",
      ...Columns.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Basic.displayName = "Basic";
    // @ts-ignore
    Basic.__docgenInfo = { "description": "", "displayName": "Basic", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#Basic"] = { docgenInfo: Basic.__docgenInfo, name: "Basic", path: "../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#Basic" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Cover.displayName = "Cover";
    // @ts-ignore
    Cover.__docgenInfo = { "description": "", "displayName": "Cover", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#Cover"] = { docgenInfo: Cover.__docgenInfo, name: "Cover", path: "../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#Cover" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Columns.displayName = "Columns";
    // @ts-ignore
    Columns.__docgenInfo = { "description": "", "displayName": "Columns", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#Columns"] = { docgenInfo: Columns.__docgenInfo, name: "Columns", path: "../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#Columns" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);