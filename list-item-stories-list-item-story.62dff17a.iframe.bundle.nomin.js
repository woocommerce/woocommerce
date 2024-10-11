(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[8010],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(obj, key, value) {
  key = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(key);
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _extends)
/* harmony export */ });
function _extends() {
  _extends = Object.assign ? Object.assign.bind() : function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];
      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }
    return target;
  };
  return _extends.apply(this, arguments);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _toPropertyKey)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPrimitive.js

function _toPrimitive(input, hint) {
  if ((0,esm_typeof/* default */.A)(input) !== "object" || input === null) return input;
  var prim = input[Symbol.toPrimitive];
  if (prim !== undefined) {
    var res = prim.call(input, hint || "default");
    if ((0,esm_typeof/* default */.A)(res) !== "object") return res;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (hint === "string" ? String : Number)(input);
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js


function _toPropertyKey(arg) {
  var key = _toPrimitive(arg, "string");
  return (0,esm_typeof/* default */.A)(key) === "symbol" ? key : String(key);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/draggable/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ Draggable)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);


/**
 * WordPress dependencies
 */

/**
 * External dependencies
 */


const dragImageClass = 'components-draggable__invisible-drag-image';
const cloneWrapperClass = 'components-draggable__clone';
const clonePadding = 0;
const bodyClass = 'is-dragging-components-draggable';
/**
 * @typedef RenderProp
 * @property {(event: import('react').DragEvent) => void} onDraggableStart `onDragStart` handler.
 * @property {(event: import('react').DragEvent) => void} onDraggableEnd   `onDragEnd` handler.
 */

/**
 * @typedef Props
 * @property {(props: RenderProp) => JSX.Element | null}  children                         Children.
 * @property {(event: import('react').DragEvent) => void} [onDragStart]                    Callback when dragging starts.
 * @property {(event: import('react').DragEvent) => void} [onDragOver]                     Callback when dragging happens over the document.
 * @property {(event: import('react').DragEvent) => void} [onDragEnd]                      Callback when dragging ends.
 * @property {string}                                     [cloneClassname]                 Classname for the cloned element.
 * @property {string}                                     [elementId]                      ID for the element.
 * @property {any}                                        [transferData]                   Transfer data for the drag event.
 * @property {string}                                     [__experimentalTransferDataType] The transfer data type to set.
 * @property {import('react').ReactNode}                  __experimentalDragComponent      Component to show when dragging.
 */

/**
 * @param {Props} props
 * @return {JSX.Element} A draggable component.
 */

function Draggable(_ref) {
  let {
    children,
    onDragStart,
    onDragOver,
    onDragEnd,
    cloneClassname,
    elementId,
    transferData,
    __experimentalTransferDataType: transferDataType = 'text',
    __experimentalDragComponent: dragComponent
  } = _ref;

  /** @type {import('react').MutableRefObject<HTMLDivElement | null>} */
  const dragComponentRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const cleanup = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(() => {});
  /**
   * Removes the element clone, resets cursor, and removes drag listener.
   *
   * @param {import('react').DragEvent} event The non-custom DragEvent.
   */

  function end(event) {
    event.preventDefault();
    cleanup.current();

    if (onDragEnd) {
      onDragEnd(event);
    }
  }
  /**
   * This method does a couple of things:
   *
   * - Clones the current element and spawns clone over original element.
   * - Adds a fake temporary drag image to avoid browser defaults.
   * - Sets transfer data.
   * - Adds dragover listener.
   *
   * @param {import('react').DragEvent} event The non-custom DragEvent.
   */


  function start(event) {
    // @ts-ignore We know that ownerDocument does exist on an Element
    const {
      ownerDocument
    } = event.target;
    event.dataTransfer.setData(transferDataType, JSON.stringify(transferData));
    const cloneWrapper = ownerDocument.createElement('div'); // Reset position to 0,0. Natural stacking order will position this lower, even with a transform otherwise.

    cloneWrapper.style.top = 0;
    cloneWrapper.style.left = 0;
    const dragImage = ownerDocument.createElement('div'); // Set a fake drag image to avoid browser defaults. Remove from DOM
    // right after. event.dataTransfer.setDragImage is not supported yet in
    // IE, we need to check for its existence first.

    if ('function' === typeof event.dataTransfer.setDragImage) {
      dragImage.classList.add(dragImageClass);
      ownerDocument.body.appendChild(dragImage);
      event.dataTransfer.setDragImage(dragImage, 0, 0);
    }

    cloneWrapper.classList.add(cloneWrapperClass);

    if (cloneClassname) {
      cloneWrapper.classList.add(cloneClassname);
    }

    let x = 0;
    let y = 0; // If a dragComponent is defined, the following logic will clone the
    // HTML node and inject it into the cloneWrapper.

    if (dragComponentRef.current) {
      // Position dragComponent at the same position as the cursor.
      x = event.clientX;
      y = event.clientY;
      cloneWrapper.style.transform = `translate( ${x}px, ${y}px )`;
      const clonedDragComponent = ownerDocument.createElement('div');
      clonedDragComponent.innerHTML = dragComponentRef.current.innerHTML;
      cloneWrapper.appendChild(clonedDragComponent); // Inject the cloneWrapper into the DOM.

      ownerDocument.body.appendChild(cloneWrapper);
    } else {
      const element = ownerDocument.getElementById(elementId); // Prepare element clone and append to element wrapper.

      const elementRect = element.getBoundingClientRect();
      const elementWrapper = element.parentNode;
      const elementTopOffset = parseInt(elementRect.top, 10);
      const elementLeftOffset = parseInt(elementRect.left, 10);
      cloneWrapper.style.width = `${elementRect.width + clonePadding * 2}px`;
      const clone = element.cloneNode(true);
      clone.id = `clone-${elementId}`; // Position clone right over the original element (20px padding).

      x = elementLeftOffset - clonePadding;
      y = elementTopOffset - clonePadding;
      cloneWrapper.style.transform = `translate( ${x}px, ${y}px )`; // Hack: Remove iFrames as it's causing the embeds drag clone to freeze.

      Array.from(clone.querySelectorAll('iframe')).forEach(child => child.parentNode.removeChild(child));
      cloneWrapper.appendChild(clone); // Inject the cloneWrapper into the DOM.

      elementWrapper.appendChild(cloneWrapper);
    } // Mark the current cursor coordinates.


    let cursorLeft = event.clientX;
    let cursorTop = event.clientY;
    /**
     * @param {import('react').DragEvent<Element>} e
     */

    function over(e) {
      // Skip doing any work if mouse has not moved.
      if (cursorLeft === e.clientX && cursorTop === e.clientY) {
        return;
      }

      const nextX = x + e.clientX - cursorLeft;
      const nextY = y + e.clientY - cursorTop;
      cloneWrapper.style.transform = `translate( ${nextX}px, ${nextY}px )`;
      cursorLeft = e.clientX;
      cursorTop = e.clientY;
      x = nextX;
      y = nextY;

      if (onDragOver) {
        onDragOver(e);
      }
    } // Aim for 60fps (16 ms per frame) for now. We can potentially use requestAnimationFrame (raf) instead,
    // note that browsers may throttle raf below 60fps in certain conditions.


    const throttledDragOver = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.throttle)(over, 16);
    ownerDocument.addEventListener('dragover', throttledDragOver); // Update cursor to 'grabbing', document wide.

    ownerDocument.body.classList.add(bodyClass); // Allow the Synthetic Event to be accessed from asynchronous code.
    // https://reactjs.org/docs/events.html#event-pooling

    event.persist();
    /** @type {number | undefined} */

    let timerId;

    if (onDragStart) {
      timerId = setTimeout(() => onDragStart(event));
    }

    cleanup.current = () => {
      // Remove drag clone.
      if (cloneWrapper && cloneWrapper.parentNode) {
        cloneWrapper.parentNode.removeChild(cloneWrapper);
      }

      if (dragImage && dragImage.parentNode) {
        dragImage.parentNode.removeChild(dragImage);
      } // Reset cursor.


      ownerDocument.body.classList.remove(bodyClass);
      ownerDocument.removeEventListener('dragover', throttledDragOver);
      clearTimeout(timerId);
    };
  }

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => () => {
    cleanup.current();
  }, []);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Fragment, null, children({
    onDraggableStart: start,
    onDraggableEnd: end
  }), dragComponent && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: "components-draggable-drag-component-root",
    style: {
      display: 'none'
    },
    ref: dragComponentRef
  }, dragComponent));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../packages/js/components/src/list-item/list-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

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

"use strict";

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

"use strict";
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

"use strict";
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

/***/ "../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js":
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;
	var nativeCodeString = '[native code]';

	function classNames() {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg)) {
				if (arg.length) {
					var inner = classNames.apply(null, arg);
					if (inner) {
						classes.push(inner);
					}
				}
			} else if (argType === 'object') {
				if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
					classes.push(arg.toString());
					continue;
				}

				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $forEach = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").forEach);
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");

var STRICT_METHOD = arrayMethodIsStrict('forEach');

// `Array.prototype.forEach` method implementation
// https://tc39.es/ecma262/#sec-array.prototype.foreach
module.exports = !STRICT_METHOD ? function forEach(callbackfn /* , thisArg */) {
  return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
} : [].forEach;


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $filter = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").filter);
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');

// `Array.prototype.filter` method
// https://tc39.es/ecma262/#sec-array.prototype.filter
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  filter: function filter(callbackfn /* , thisArg */) {
    return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");

// `Array.prototype.forEach` method
// https://tc39.es/ecma262/#sec-array.prototype.foreach
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
$({ target: 'Array', proto: true, forced: [].forEach !== forEach }, {
  forEach: forEach
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperties = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-properties.js").f);

// `Object.defineProperties` method
// https://tc39.es/ecma262/#sec-object.defineproperties
// eslint-disable-next-line es/no-object-defineproperties -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperties !== defineProperties, sham: !DESCRIPTORS }, {
  defineProperties: defineProperties
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperty = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-property.js").f);

// `Object.defineProperty` method
// https://tc39.es/ecma262/#sec-object.defineproperty
// eslint-disable-next-line es/no-object-defineproperty -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperty !== defineProperty, sham: !DESCRIPTORS }, {
  defineProperty: defineProperty
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var nativeGetOwnPropertyDescriptor = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js").f);
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");

var FORCED = !DESCRIPTORS || fails(function () { nativeGetOwnPropertyDescriptor(1); });

// `Object.getOwnPropertyDescriptor` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptor
$({ target: 'Object', stat: true, forced: FORCED, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptor: function getOwnPropertyDescriptor(it, key) {
    return nativeGetOwnPropertyDescriptor(toIndexedObject(it), key);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var ownKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/own-keys.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var getOwnPropertyDescriptorModule = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js");
var createProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-property.js");

// `Object.getOwnPropertyDescriptors` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptors
$({ target: 'Object', stat: true, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptors: function getOwnPropertyDescriptors(object) {
    var O = toIndexedObject(object);
    var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
    var keys = ownKeys(O);
    var result = {};
    var index = 0;
    var key, descriptor;
    while (keys.length > index) {
      descriptor = getOwnPropertyDescriptor(O, key = keys[index++]);
      if (descriptor !== undefined) createProperty(result, key, descriptor);
    }
    return result;
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var nativeKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-keys.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");

var FAILS_ON_PRIMITIVES = fails(function () { nativeKeys(1); });

// `Object.keys` method
// https://tc39.es/ecma262/#sec-object.keys
$({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES }, {
  keys: function keys(it) {
    return nativeKeys(toObject(it));
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var DOMIterables = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-iterables.js");
var DOMTokenListPrototype = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-token-list-prototype.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");
var createNonEnumerableProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-non-enumerable-property.js");

var handlePrototype = function (CollectionPrototype) {
  // some Chrome versions have non-configurable methods on DOMTokenList
  if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
    createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
  } catch (error) {
    CollectionPrototype.forEach = forEach;
  }
};

for (var COLLECTION_NAME in DOMIterables) {
  if (DOMIterables[COLLECTION_NAME]) {
    handlePrototype(global[COLLECTION_NAME] && global[COLLECTION_NAME].prototype);
  }
}

handlePrototype(DOMTokenListPrototype);


/***/ }),

/***/ "../../packages/js/components/src/list-item/stories/list-item.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   Draggable: () => (/* binding */ Draggable),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/list-item/list-item.tsx");
/* harmony import */ var _sortable__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var Basic = function Basic() {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 2"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 3"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 4"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 5"));
};
var Draggable = function Draggable() {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_sortable__WEBPACK_IMPORTED_MODULE_2__/* .Sortable */ .L, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 2"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c, null, "Item 3"));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/components/ListItem',
  component: ___WEBPACK_IMPORTED_MODULE_1__/* .ListItem */ .c
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <>\n            <ListItem>Item 1</ListItem>\n            <ListItem>Item 2</ListItem>\n            <ListItem>Item 3</ListItem>\n            <ListItem>Item 4</ListItem>\n            <ListItem>Item 5</ListItem>\n        </>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
Draggable.parameters = {
  ...Draggable.parameters,
  docs: {
    ...Draggable.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <Sortable>\n            <ListItem>Item 1</ListItem>\n            <ListItem>Item 2</ListItem>\n            <ListItem>Item 3</ListItem>\n        </Sortable>;\n}",
      ...Draggable.parameters?.docs?.source
    }
  }
};

/***/ })

}]);