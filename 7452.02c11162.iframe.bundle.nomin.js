"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7452],{

/***/ "../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   TN: () => (/* binding */ toggleNode),
/* harmony export */   VW: () => (/* binding */ countNumberOfNodes),
/* harmony export */   YD: () => (/* binding */ createLinkedTree),
/* harmony export */   g_: () => (/* binding */ getNodeDataByIndex),
/* harmony export */   p_: () => (/* binding */ getVisibleNodeIndex)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var core_js_modules_es_array_some_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
/* harmony import */ var core_js_modules_es_array_some_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_some_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_25__);

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
      (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
function _createForOfIteratorHelper(o, allowArrayLike) {
  var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
  if (!it) {
    if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      var F = function F() {};
      return {
        s: F,
        n: function n() {
          if (i >= o.length) return {
            done: true
          };
          return {
            done: false,
            value: o[i++]
          };
        },
        e: function e(_e) {
          throw _e;
        },
        f: F
      };
    }
    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }
  var normalCompletion = true,
    didErr = false,
    err;
  return {
    s: function s() {
      it = it.call(o);
    },
    n: function n() {
      var step = it.next();
      normalCompletion = step.done;
      return step;
    },
    e: function e(_e2) {
      didErr = true;
      err = _e2;
    },
    f: function f() {
      try {
        if (!normalCompletion && it["return"] != null) it["return"]();
      } finally {
        if (didErr) throw err;
      }
    }
  };
}
function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}

























/**
 * Internal dependencies
 */

var shouldItemBeExpanded = function shouldItemBeExpanded(item, createValue) {
  var _item$children;
  if (!createValue || !((_item$children = item.children) !== null && _item$children !== void 0 && _item$children.length)) return false;
  return item.children.some(function (child) {
    if (new RegExp(createValue || '', 'ig').test(child.data.label)) {
      return true;
    }
    return shouldItemBeExpanded(child, createValue);
  });
};
function findChildren(items) {
  var memo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  var parent = arguments.length > 2 ? arguments[2] : undefined;
  var createValue = arguments.length > 3 ? arguments[3] : undefined;
  var children = [];
  var others = [];
  items.forEach(function (item) {
    if (item.parent === parent) {
      children.push(item);
    } else {
      others.push(item);
    }
    memo[item.value] = {
      parent: undefined,
      data: item,
      children: []
    };
  });
  return children.map(function (child) {
    var linkedTree = memo[child.value];
    linkedTree.parent = child.parent ? memo[child.parent] : undefined;
    linkedTree.children = findChildren(others, memo, child.value, createValue);
    linkedTree.data.isExpanded = linkedTree.children.length === 0 ? true : shouldItemBeExpanded(linkedTree, createValue);
    return linkedTree;
  });
}
function populateIndexes(linkedTree) {
  var startCount = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var count = startCount;
  function populate(tree) {
    var _iterator = _createForOfIteratorHelper(tree),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var node = _step.value;
        node.index = count;
        count++;
        if (node.children) {
          count = populate(node.children);
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
    return count;
  }
  populate(linkedTree);
  return linkedTree;
}

// creates a linked tree from an array of Items
function createLinkedTree(items, value) {
  var augmentedItems = items.map(function (i) {
    return _objectSpread(_objectSpread({}, i), {}, {
      isExpanded: false
    });
  });
  return populateIndexes(findChildren(augmentedItems, {}, undefined, value));
}

// Toggles the expanded state of a node in a linked tree
function toggleNode(tree, number, value) {
  return tree.map(function (node) {
    return _objectSpread(_objectSpread({}, node), {}, {
      children: node.children ? toggleNode(node.children, number, value) : node.children,
      data: _objectSpread(_objectSpread({}, node.data), {}, {
        isExpanded: node.index === number ? value : node.data.isExpanded
      })
    }, node.parent ? {
      parent: _objectSpread(_objectSpread({}, node.parent), {}, {
        data: _objectSpread(_objectSpread({}, node.parent.data), {}, {
          isExpanded: node.parent.index === number ? value : node.parent.data.isExpanded
        })
      })
    } : {});
  });
}

// Gets the index of the next/previous visible node in the linked tree
function getVisibleNodeIndex(tree, highlightedIndex, direction) {
  if (direction === 'down') {
    var _iterator2 = _createForOfIteratorHelper(tree),
      _step2;
    try {
      for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
        var node = _step2.value;
        if (!node.parent || node.parent.data.isExpanded) {
          if (node.index !== undefined && node.index >= highlightedIndex) {
            return node.index;
          }
          var visibleNodeIndex = getVisibleNodeIndex(node.children, highlightedIndex, direction);
          if (visibleNodeIndex !== undefined) {
            return visibleNodeIndex;
          }
        }
      }
    } catch (err) {
      _iterator2.e(err);
    } finally {
      _iterator2.f();
    }
  } else {
    for (var i = tree.length - 1; i >= 0; i--) {
      var _node = tree[i];
      if (!_node.parent || _node.parent.data.isExpanded) {
        var _visibleNodeIndex = getVisibleNodeIndex(_node.children, highlightedIndex, direction);
        if (_visibleNodeIndex !== undefined) {
          return _visibleNodeIndex;
        }
        if (_node.index !== undefined && _node.index <= highlightedIndex) {
          return _node.index;
        }
      }
    }
  }
  return undefined;
}

// Counts the number of nodes in a LinkedTree
function countNumberOfNodes(linkedTree) {
  var count = 0;
  var _iterator3 = _createForOfIteratorHelper(linkedTree),
    _step3;
  try {
    for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
      var node = _step3.value;
      count++;
      if (node.children) {
        count += countNumberOfNodes(node.children);
      }
    }
  } catch (err) {
    _iterator3.e(err);
  } finally {
    _iterator3.f();
  }
  return count;
}

// Gets the data of a node by its index
function getNodeDataByIndex(linkedTree, index) {
  var _iterator4 = _createForOfIteratorHelper(linkedTree),
    _step4;
  try {
    for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
      var node = _step4.value;
      if (node.index === index) {
        return node.data;
      }
      if (node.children) {
        var child = getNodeDataByIndex(node.children, index);
        if (child) {
          return child;
        }
      }
    }
  } catch (err) {
    _iterator4.e(err);
  } finally {
    _iterator4.f();
  }
  return undefined;
}

/***/ }),

/***/ "../../packages/js/components/src/experimental-tree-control/tree.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  P: () => (/* binding */ Tree)
});

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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/plus.js
var plus = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/plus.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/hooks/use-tree.ts












var _excluded = ["items", "level", "role", "multiple", "selected", "getItemLabel", "shouldItemBeExpanded", "shouldItemBeHighlighted", "onSelect", "onRemove", "shouldNotRecursivelySelect", "createValue", "onTreeBlur", "onCreateNew", "shouldShowCreateButton", "onFirstItemLoop", "onEscape", "highlightedIndex", "onExpand"];
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

function useTree(_ref) {
  var items = _ref.items,
    _ref$level = _ref.level,
    level = _ref$level === void 0 ? 1 : _ref$level,
    _ref$role = _ref.role,
    role = _ref$role === void 0 ? 'listbox' : _ref$role,
    multiple = _ref.multiple,
    selected = _ref.selected,
    getItemLabel = _ref.getItemLabel,
    shouldItemBeExpanded = _ref.shouldItemBeExpanded,
    shouldItemBeHighlighted = _ref.shouldItemBeHighlighted,
    onSelect = _ref.onSelect,
    onRemove = _ref.onRemove,
    shouldNotRecursivelySelect = _ref.shouldNotRecursivelySelect,
    createValue = _ref.createValue,
    onTreeBlur = _ref.onTreeBlur,
    onCreateNew = _ref.onCreateNew,
    shouldShowCreateButton = _ref.shouldShowCreateButton,
    onFirstItemLoop = _ref.onFirstItemLoop,
    onEscape = _ref.onEscape,
    highlightedIndex = _ref.highlightedIndex,
    onExpand = _ref.onExpand,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  return {
    level: level,
    items: items,
    treeProps: _objectSpread(_objectSpread({}, props), {}, {
      role: role
    }),
    treeItemProps: {
      level: level,
      multiple: multiple,
      selected: selected,
      getLabel: getItemLabel,
      shouldItemBeExpanded: shouldItemBeExpanded,
      shouldItemBeHighlighted: shouldItemBeHighlighted,
      shouldNotRecursivelySelect: shouldNotRecursivelySelect,
      onSelect: onSelect,
      onRemove: onRemove
    }
  };
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js
var html_entities_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/hooks/use-expander.ts

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

function useExpander(_ref) {
  var shouldItemBeExpanded = _ref.shouldItemBeExpanded,
    item = _ref.item;
  var _useState = (0,react.useState)(false),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    isExpanded = _useState2[0],
    setExpanded = _useState2[1];
  (0,react.useEffect)(function () {
    var _item$children;
    if ((_item$children = item.children) !== null && _item$children !== void 0 && _item$children.length && typeof shouldItemBeExpanded === 'function' && !isExpanded) {
      setExpanded(shouldItemBeExpanded(item));
    }
  }, [item, shouldItemBeExpanded]);
  function onExpand() {
    setExpanded(true);
  }
  function onCollapse() {
    setExpanded(false);
  }
  function onToggleExpand() {
    setExpanded(function (prev) {
      return !prev;
    });
  }
  return {
    isExpanded: isExpanded,
    onExpand: onExpand,
    onCollapse: onCollapse,
    onToggleExpand: onToggleExpand
  };
}
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/hooks/use-highlighter.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

function useHighlighter(_ref) {
  var item = _ref.item,
    multiple = _ref.multiple,
    checkedStatus = _ref.checkedStatus,
    shouldItemBeHighlighted = _ref.shouldItemBeHighlighted;
  var isHighlighted = (0,react.useMemo)(function () {
    if (typeof shouldItemBeHighlighted === 'function') {
      if (multiple || item.children.length === 0) {
        return shouldItemBeHighlighted(item);
      }
    }
    if (!multiple) {
      return checkedStatus === 'checked';
    }
  }, [item, multiple, checkedStatus, shouldItemBeHighlighted]);
  return {
    isHighlighted: isHighlighted
  };
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.from.js
var es_array_from = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.from.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js
var es_symbol_description = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.iterator.js
var es_symbol_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/hooks/use-keyboard.ts














function _createForOfIteratorHelper(o, allowArrayLike) {
  var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
  if (!it) {
    if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      var F = function F() {};
      return {
        s: F,
        n: function n() {
          if (i >= o.length) return {
            done: true
          };
          return {
            done: false,
            value: o[i++]
          };
        },
        e: function e(_e) {
          throw _e;
        },
        f: F
      };
    }
    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }
  var normalCompletion = true,
    didErr = false,
    err;
  return {
    s: function s() {
      it = it.call(o);
    },
    n: function n() {
      var step = it.next();
      normalCompletion = step.done;
      return step;
    },
    e: function e(_e2) {
      didErr = true;
      err = _e2;
    },
    f: function f() {
      try {
        if (!normalCompletion && it["return"] != null) it["return"]();
      } finally {
        if (didErr) throw err;
      }
    }
  };
}
function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

function getFirstChild(currentHeading) {
  var parentTreeItem = currentHeading === null || currentHeading === void 0 ? void 0 : currentHeading.closest('.experimental-woocommerce-tree-item');
  var firstSubTreeItem = parentTreeItem === null || parentTreeItem === void 0 ? void 0 : parentTreeItem.querySelector('.experimental-woocommerce-tree > .experimental-woocommerce-tree-item');
  var label = firstSubTreeItem === null || firstSubTreeItem === void 0 ? void 0 : firstSubTreeItem.querySelector('.experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label');
  return label !== null && label !== void 0 ? label : null;
}
function getFirstAncestor(currentHeading) {
  var parentTree = currentHeading === null || currentHeading === void 0 ? void 0 : currentHeading.closest('.experimental-woocommerce-tree');
  var grandParentTreeItem = parentTree === null || parentTree === void 0 ? void 0 : parentTree.closest('.experimental-woocommerce-tree-item');
  var label = grandParentTreeItem === null || grandParentTreeItem === void 0 ? void 0 : grandParentTreeItem.querySelector('.experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label');
  return label !== null && label !== void 0 ? label : null;
}
function getAllHeadings(currentHeading) {
  var rootTree = currentHeading.closest('.experimental-woocommerce-tree--level-1');
  return rootTree === null || rootTree === void 0 ? void 0 : rootTree.querySelectorAll('.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading');
}
var step = {
  ArrowDown: 1,
  ArrowUp: -1
};
function getNextFocusableElement(currentHeading, code) {
  var _step$code;
  var headingsNodeList = getAllHeadings(currentHeading);
  if (!headingsNodeList) return null;
  var currentHeadingIndex = 0;
  var _iterator = _createForOfIteratorHelper(headingsNodeList.values()),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var _heading = _step.value;
      if (_heading === currentHeading) break;
      currentHeadingIndex++;
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  if (currentHeadingIndex < 0 || currentHeadingIndex >= headingsNodeList.length) {
    return null;
  }
  var heading = headingsNodeList.item(currentHeadingIndex + ((_step$code = step[code]) !== null && _step$code !== void 0 ? _step$code : 0));
  return heading === null || heading === void 0 ? void 0 : heading.querySelector('.experimental-woocommerce-tree-item__label');
}
function getFirstFocusableElement(currentHeading) {
  var headingsNodeList = getAllHeadings(currentHeading);
  if (!headingsNodeList) return null;
  return headingsNodeList.item(0).querySelector('.experimental-woocommerce-tree-item__label');
}
function getLastFocusableElement(currentHeading) {
  var headingsNodeList = getAllHeadings(currentHeading);
  if (!headingsNodeList) return null;
  return headingsNodeList.item(headingsNodeList.length - 1).querySelector('.experimental-woocommerce-tree-item__label');
}
function useKeyboard(_ref) {
  var item = _ref.item,
    isExpanded = _ref.isExpanded,
    onExpand = _ref.onExpand,
    onCollapse = _ref.onCollapse,
    onToggleExpand = _ref.onToggleExpand,
    onLastItemLoop = _ref.onLastItemLoop,
    onFirstItemLoop = _ref.onFirstItemLoop;
  function onKeyDown(event) {
    if (event.code === 'ArrowRight') {
      event.preventDefault();
      if (item.children.length > 0) {
        if (isExpanded) {
          var element = getFirstChild(event.currentTarget);
          return element === null || element === void 0 ? void 0 : element.focus();
        }
        onExpand();
      }
    }
    if (event.code === 'ArrowLeft') {
      event.preventDefault();
      if (!isExpanded && item.parent) {
        var _element = getFirstAncestor(event.currentTarget);
        return _element === null || _element === void 0 ? void 0 : _element.focus();
      }
      if (item.children.length > 0) {
        onCollapse();
      }
    }
    if (event.code === 'Enter') {
      event.preventDefault();
      if (item.children.length > 0) {
        onToggleExpand();
      }
    }
    if (event.code === 'ArrowDown' || event.code === 'ArrowUp') {
      event.preventDefault();
      var _element2 = getNextFocusableElement(event.currentTarget, event.code);
      _element2 === null || _element2 === void 0 || _element2.focus();
      if (event.code === 'ArrowDown' && !_element2 && onLastItemLoop) {
        onLastItemLoop(event);
      }
      if (event.code === 'ArrowUp' && !_element2 && onFirstItemLoop) {
        onFirstItemLoop(event);
      }
    }
    if (event.code === 'Home') {
      event.preventDefault();
      var _element3 = getFirstFocusableElement(event.currentTarget);
      _element3 === null || _element3 === void 0 || _element3.focus();
    }
    if (event.code === 'End') {
      event.preventDefault();
      var _element4 = getLastFocusableElement(event.currentTarget);
      _element4 === null || _element4 === void 0 || _element4.focus();
    }
  }
  return {
    onKeyDown: onKeyDown
  };
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/hooks/use-selection.ts




















function use_selection_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function use_selection_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? use_selection_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : use_selection_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
function use_selection_createForOfIteratorHelper(o, allowArrayLike) {
  var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
  if (!it) {
    if (Array.isArray(o) || (it = use_selection_unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      var F = function F() {};
      return {
        s: F,
        n: function n() {
          if (i >= o.length) return {
            done: true
          };
          return {
            done: false,
            value: o[i++]
          };
        },
        e: function e(_e) {
          throw _e;
        },
        f: F
      };
    }
    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }
  var normalCompletion = true,
    didErr = false,
    err;
  return {
    s: function s() {
      it = it.call(o);
    },
    n: function n() {
      var step = it.next();
      normalCompletion = step.done;
      return step;
    },
    e: function e(_e2) {
      didErr = true;
      err = _e2;
    },
    f: function f() {
      try {
        if (!normalCompletion && it["return"] != null) it["return"]();
      } finally {
        if (didErr) throw err;
      }
    }
  };
}
function use_selection_unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return use_selection_arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return use_selection_arrayLikeToArray(o, minLen);
}
function use_selection_arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}








/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var selectedItemsMap = {};
var indeterminateMemo = {};
function getDeepChildren(item) {
  if (item.children.length) {
    var children = item.children.map(function (_ref) {
      var data = _ref.data;
      return data;
    });
    item.children.forEach(function (child) {
      children.push.apply(children, (0,toConsumableArray/* default */.A)(getDeepChildren(child)));
    });
    return children;
  }
  return [];
}
function isIndeterminate(selectedItems, children) {
  var memo = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : indeterminateMemo;
  if (children !== null && children !== void 0 && children.length) {
    var _iterator = use_selection_createForOfIteratorHelper(children),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var child = _step.value;
        if (child.data.value in indeterminateMemo) {
          return true;
        }
        var isChildSelected = child.data.value in selectedItems;
        if (!isChildSelected || isIndeterminate(selectedItems, child.children, memo)) {
          indeterminateMemo[child.data.value] = true;
          return true;
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  }
  return false;
}
function mapSelectedItems() {
  var selected = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var selectedArray = Array.isArray(selected) ? selected : [selected];
  return selectedArray.reduce(function (map, selectedItem, index) {
    return use_selection_objectSpread(use_selection_objectSpread({}, map), {}, (0,defineProperty/* default */.A)({}, selectedItem.value, index));
  }, {});
}
function hasSelectedSibblingChildren(children, values, selectedItems) {
  return children.some(function (child) {
    var isChildSelected = child.data.value in selectedItems;
    if (!isChildSelected) return false;
    return !values.some(function (childValue) {
      return childValue.value === child.data.value;
    });
  });
}
function useSelection(_ref2) {
  var item = _ref2.item,
    multiple = _ref2.multiple,
    shouldNotRecursivelySelect = _ref2.shouldNotRecursivelySelect,
    selected = _ref2.selected,
    level = _ref2.level,
    index = _ref2.index,
    onSelect = _ref2.onSelect,
    onRemove = _ref2.onRemove;
  var selectedItems = (0,react.useMemo)(function () {
    if (level === 1 && index === 0) {
      selectedItemsMap = mapSelectedItems(selected);
      indeterminateMemo = {};
    }
    return selectedItemsMap;
  }, [selected, level, index]);
  var checkedStatus = (0,react.useMemo)(function () {
    if (item.data.value in selectedItems) {
      if (multiple && !shouldNotRecursivelySelect && isIndeterminate(selectedItems, item.children)) {
        return 'indeterminate';
      }
      return 'checked';
    }
    return 'unchecked';
  }, [selectedItems, item, multiple]);
  function onSelectChild(checked) {
    var value = item.data;
    if (multiple) {
      value = [item.data];
      if (item.children.length && !shouldNotRecursivelySelect) {
        var _value;
        (_value = value).push.apply(_value, (0,toConsumableArray/* default */.A)(getDeepChildren(item)));
      }
    }
    if (checked) {
      if (typeof onSelect === 'function') {
        onSelect(value);
      }
    } else if (typeof onRemove === 'function') {
      onRemove(value);
    }
  }
  function onSelectChildren(value) {
    if (typeof onSelect !== 'function') return;
    if (multiple && !shouldNotRecursivelySelect) {
      value = [item.data].concat((0,toConsumableArray/* default */.A)(value));
    }
    onSelect(value);
  }
  function onRemoveChildren(value) {
    var _item$children;
    if (typeof onRemove !== 'function') return;
    if (multiple && (_item$children = item.children) !== null && _item$children !== void 0 && _item$children.length && !shouldNotRecursivelySelect) {
      var hasSelectedSibbling = hasSelectedSibblingChildren(item.children, value, selectedItems);
      if (!hasSelectedSibbling) {
        value = [item.data].concat((0,toConsumableArray/* default */.A)(value));
      }
    }
    onRemove(value);
  }
  return {
    multiple: multiple,
    selected: selected,
    checkedStatus: checkedStatus,
    onSelectChild: onSelectChild,
    onSelectChildren: onSelectChildren,
    onRemoveChildren: onRemoveChildren
  };
}
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/hooks/use-tree-item.ts












var use_tree_item_excluded = ["item", "level", "multiple", "shouldNotRecursivelySelect", "selected", "index", "getLabel", "shouldItemBeExpanded", "shouldItemBeHighlighted", "onSelect", "onRemove", "isExpanded", "onCreateNew", "shouldShowCreateButton", "onLastItemLoop", "onFirstItemLoop", "onTreeBlur", "onEscape", "highlightedIndex", "isHighlighted", "onExpand"];
function use_tree_item_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function use_tree_item_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? use_tree_item_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : use_tree_item_ownKeys(Object(t)).forEach(function (r) {
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





function useTreeItem(_ref) {
  var item = _ref.item,
    level = _ref.level,
    multiple = _ref.multiple,
    shouldNotRecursivelySelect = _ref.shouldNotRecursivelySelect,
    selected = _ref.selected,
    index = _ref.index,
    getLabel = _ref.getLabel,
    shouldItemBeExpanded = _ref.shouldItemBeExpanded,
    shouldItemBeHighlighted = _ref.shouldItemBeHighlighted,
    onSelect = _ref.onSelect,
    onRemove = _ref.onRemove,
    isExpanded = _ref.isExpanded,
    onCreateNew = _ref.onCreateNew,
    shouldShowCreateButton = _ref.shouldShowCreateButton,
    onLastItemLoop = _ref.onLastItemLoop,
    onFirstItemLoop = _ref.onFirstItemLoop,
    onTreeBlur = _ref.onTreeBlur,
    onEscape = _ref.onEscape,
    highlightedIndex = _ref.highlightedIndex,
    isHighlighted = _ref.isHighlighted,
    onExpand = _ref.onExpand,
    props = (0,objectWithoutProperties/* default */.A)(_ref, use_tree_item_excluded);
  var nextLevel = level + 1;
  var expander = useExpander({
    item: item,
    shouldItemBeExpanded: shouldItemBeExpanded
  });
  var selection = useSelection({
    item: item,
    multiple: multiple,
    selected: selected,
    level: level,
    index: index,
    onSelect: onSelect,
    onRemove: onRemove,
    shouldNotRecursivelySelect: shouldNotRecursivelySelect
  });
  var highlighter = useHighlighter({
    item: item,
    checkedStatus: selection.checkedStatus,
    multiple: multiple,
    shouldItemBeHighlighted: shouldItemBeHighlighted
  });
  var subTreeId = "experimental-woocommerce-tree__group-".concat((0,use_instance_id/* default */.A)(useTreeItem));
  var _useKeyboard = useKeyboard(use_tree_item_objectSpread(use_tree_item_objectSpread({}, expander), {}, {
      onLastItemLoop: onLastItemLoop,
      onFirstItemLoop: onFirstItemLoop,
      item: item
    })),
    onKeyDown = _useKeyboard.onKeyDown;
  return {
    item: item,
    level: nextLevel,
    expander: expander,
    selection: selection,
    highlighter: highlighter,
    getLabel: getLabel,
    treeItemProps: use_tree_item_objectSpread(use_tree_item_objectSpread({}, props), {}, {
      id: 'woocommerce-experimental-tree-control__menu-item-' + item.index,
      role: 'option'
    }),
    headingProps: {
      role: 'treeitem',
      'aria-selected': selection.checkedStatus !== 'unchecked',
      'aria-expanded': item.children.length ? item.data.isExpanded : undefined,
      'aria-owns': item.children.length && item.data.isExpanded ? subTreeId : undefined,
      style: {
        '--level': level
      },
      onKeyDown: onKeyDown
    },
    treeProps: {
      id: subTreeId,
      items: item.children,
      level: nextLevel,
      multiple: selection.multiple,
      selected: selection.selected,
      role: 'group',
      'aria-label': item.data.label,
      getItemLabel: getLabel,
      shouldItemBeExpanded: shouldItemBeExpanded,
      shouldItemBeHighlighted: shouldItemBeHighlighted,
      shouldNotRecursivelySelect: shouldNotRecursivelySelect,
      onSelect: selection.onSelectChildren,
      onRemove: selection.onRemoveChildren
    }
  };
}
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/tree-item.tsx













function tree_item_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function tree_item_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? tree_item_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : tree_item_ownKeys(Object(t)).forEach(function (r) {
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


var TreeItem = /*#__PURE__*/(0,react.forwardRef)(function ForwardedTreeItem(props, ref) {
  var _item$children;
  var _useTreeItem = useTreeItem(tree_item_objectSpread(tree_item_objectSpread({}, props), {}, {
      ref: ref
    })),
    item = _useTreeItem.item,
    treeItemProps = _useTreeItem.treeItemProps,
    headingProps = _useTreeItem.headingProps,
    treeProps = _useTreeItem.treeProps,
    selection = _useTreeItem.selection,
    getLabel = _useTreeItem.getLabel;
  function handleKeyDown(event) {
    if (event.key === 'Escape' && props.onEscape) {
      event.preventDefault();
      props.onEscape();
    } else if (event.key === 'ArrowLeft') {
      if (item.index !== undefined) {
        var _props$onExpand;
        (_props$onExpand = props.onExpand) === null || _props$onExpand === void 0 || _props$onExpand.call(props, item.index, false);
      }
    } else if (event.key === 'ArrowRight') {
      if (item.index !== undefined) {
        var _props$onExpand2;
        (_props$onExpand2 = props.onExpand) === null || _props$onExpand2 === void 0 || _props$onExpand2.call(props, item.index, true);
      }
    }
  }
  return (0,react.createElement)("li", (0,esm_extends/* default */.A)({}, treeItemProps, {
    className: classnames_default()(treeItemProps.className, 'experimental-woocommerce-tree-item', {
      'experimental-woocommerce-tree-item--highlighted': props.isHighlighted
    })
  }), (0,react.createElement)("div", (0,esm_extends/* default */.A)({}, headingProps, {
    className: "experimental-woocommerce-tree-item__heading"
  }), (0,react.createElement)("label", {
    className: "experimental-woocommerce-tree-item__label"
  }, selection.multiple ? (0,react.createElement)(checkbox_control/* default */.A, {
    indeterminate: selection.checkedStatus === 'indeterminate',
    checked: selection.checkedStatus === 'checked',
    onChange: selection.onSelectChild,
    onKeyDown: handleKeyDown
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore __nextHasNoMarginBottom is a valid prop
    ,

    __nextHasNoMarginBottom: true
  }) : (0,react.createElement)("input", {
    type: "checkbox",
    className: "experimental-woocommerce-tree-item__checkbox",
    checked: selection.checkedStatus === 'checked',
    onChange: function onChange(event) {
      return selection.onSelectChild(event.target.checked);
    },
    onKeyDown: handleKeyDown
  }), typeof getLabel === 'function' ? getLabel(item) : (0,react.createElement)("span", null, (0,html_entities_build_module/* decodeEntities */.S)(item.data.label))), Boolean((_item$children = item.children) === null || _item$children === void 0 ? void 0 : _item$children.length) && (0,react.createElement)("div", {
    className: "experimental-woocommerce-tree-item__expander"
  }, (0,react.createElement)(build_module_button/* default */.A, {
    icon: item.data.isExpanded ? chevron_up/* default */.A : chevron_down/* default */.A,
    onClick: function onClick() {
      if (item.index !== undefined) {
        var _props$onExpand3;
        (_props$onExpand3 = props.onExpand) === null || _props$onExpand3 === void 0 || _props$onExpand3.call(props, item.index, !item.data.isExpanded);
      }
    },
    onKeyDown: handleKeyDown,
    className: "experimental-woocommerce-tree-item__expander",
    "aria-label": item.data.isExpanded ? (0,build_module.__)('Collapse', 'woocommerce') : (0,build_module.__)('Expand', 'woocommerce')
  }))), Boolean(item.children.length) && item.data.isExpanded && (0,react.createElement)(Tree, (0,esm_extends/* default */.A)({}, treeProps, {
    highlightedIndex: props.highlightedIndex,
    onExpand: props.onExpand,
    onEscape: props.onEscape
  })));
});
try {
    // @ts-ignore
    TreeItem.displayName = "TreeItem";
    // @ts-ignore
    TreeItem.__docgenInfo = { "description": "", "displayName": "TreeItem", "props": { "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "index": { "defaultValue": null, "description": "", "name": "index", "required": true, "type": { "name": "number" } }, "level": { "defaultValue": null, "description": "", "name": "level", "required": true, "type": { "name": "number" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "item": { "defaultValue": null, "description": "", "name": "item", "required": true, "type": { "name": "LinkedTree" } }, "isFocused": { "defaultValue": null, "description": "", "name": "isFocused", "required": false, "type": { "name": "boolean" } }, "isHighlighted": { "defaultValue": null, "description": "", "name": "isHighlighted", "required": false, "type": { "name": "boolean" } }, "getLabel": { "defaultValue": null, "description": "", "name": "getLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "onLastItemLoop": { "defaultValue": null, "description": "", "name": "onLastItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/tree-item.tsx#TreeItem"] = { docgenInfo: TreeItem.__docgenInfo, name: "TreeItem", path: "../../packages/js/components/src/experimental-tree-control/tree-item.tsx#TreeItem" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts
var linked_tree_utils = __webpack_require__("../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/tree.tsx














function tree_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function tree_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? tree_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : tree_ownKeys(Object(t)).forEach(function (r) {
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



var Tree = /*#__PURE__*/(0,react.forwardRef)(function ForwardedTree(props, forwardedRef) {
  var rootListRef = (0,react.useRef)(null);
  var ref = (0,use_merge_refs/* default */.A)([rootListRef, forwardedRef]);
  var _useTree = useTree(tree_objectSpread(tree_objectSpread({}, props), {}, {
      ref: ref
    })),
    level = _useTree.level,
    items = _useTree.items,
    treeProps = _useTree.treeProps,
    treeItemProps = _useTree.treeItemProps;
  var numberOfItems = (0,linked_tree_utils/* countNumberOfNodes */.VW)(items);
  var isCreateButtonVisible = props.shouldShowCreateButton && props.shouldShowCreateButton(props.createValue);
  return (0,react.createElement)(react.Fragment, null, items.length || isCreateButtonVisible ? (0,react.createElement)("ol", (0,esm_extends/* default */.A)({}, treeProps, {
    className: classnames_default()(treeProps.className, 'experimental-woocommerce-tree', "experimental-woocommerce-tree--level-".concat(level))
  }), items.map(function (child, index) {
    return (0,react.createElement)(TreeItem, (0,esm_extends/* default */.A)({}, treeItemProps, {
      isHighlighted: props.highlightedIndex === child.index,
      onExpand: props.onExpand,
      highlightedIndex: props.highlightedIndex,
      isExpanded: child.data.isExpanded,
      key: child.data.value,
      item: child,
      index: index
      // Button ref is not working, so need to use CSS directly
      ,

      onLastItemLoop: function onLastItemLoop() {
        var _rootListRef$current;
        (_rootListRef$current = rootListRef.current) === null || _rootListRef$current === void 0 || (_rootListRef$current = _rootListRef$current.closest('ol[role="listbox"]')) === null || _rootListRef$current === void 0 || (_rootListRef$current = _rootListRef$current.parentElement) === null || _rootListRef$current === void 0 || (_rootListRef$current = _rootListRef$current.querySelector('.experimental-woocommerce-tree__button')) === null || _rootListRef$current === void 0 || _rootListRef$current.focus();
      },
      onFirstItemLoop: props.onFirstItemLoop,
      onEscape: props.onEscape
    }));
  })) : null, isCreateButtonVisible && (0,react.createElement)(build_module_button/* default */.A, {
    id: 'woocommerce-experimental-tree-control__menu-item-' + numberOfItems,
    className: classnames_default()('experimental-woocommerce-tree__button', {
      'experimental-woocommerce-tree__button--highlighted': props.highlightedIndex === numberOfItems
    }),
    onClick: function onClick() {
      if (props.onCreateNew) {
        props.onCreateNew();
      }
      if (props.onTreeBlur) {
        props.onTreeBlur();
      }
    }
    // Component's event type definition is not working
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ,

    onKeyDown: function onKeyDown(event) {
      if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
        event.preventDefault();
        if (event.key === 'ArrowUp') {
          var _allHeadings;
          var allHeadings = event.nativeEvent.srcElement.previousSibling.querySelectorAll('.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading');
          (_allHeadings = allHeadings[allHeadings.length - 1]) === null || _allHeadings === void 0 || (_allHeadings = _allHeadings.querySelector('.experimental-woocommerce-tree-item__label')) === null || _allHeadings === void 0 || _allHeadings.focus();
        }
      } else if (event.key === 'Escape' && props.onEscape) {
        event.preventDefault();
        props.onEscape();
      }
    }
  }, (0,react.createElement)(icon/* default */.A, {
    icon: plus/* default */.A,
    size: 20
  }), props.createValue ? (0,build_module/* sprintf */.nv)( /* translators: %s: create value */
  (0,build_module.__)('Create "%s"', 'woocommerce'), props.createValue) : (0,build_module.__)('Create new', 'woocommerce')));
});
try {
    // @ts-ignore
    Tree.displayName = "Tree";
    // @ts-ignore
    Tree.__docgenInfo = { "description": "", "displayName": "Tree", "props": { "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "LinkedTree[]" } }, "level": { "defaultValue": null, "description": "", "name": "level", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/tree.tsx#Tree"] = { docgenInfo: Tree.__docgenInfo, name: "Tree", path: "../../packages/js/components/src/experimental-tree-control/tree.tsx#Tree" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);