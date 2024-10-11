"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6755],{

/***/ "../../node_modules/.pnpm/@automattic+interpolate-components@1.2.1_@types+react@17.0.71_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ interpolate)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+interpolate-components@1.2.1_@types+react@17.0.71_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/tokenize.js
function identifyToken(item) {
  // {{/example}}
  if (item.startsWith('{{/')) {
    return {
      type: 'componentClose',
      value: item.replace(/\W/g, '')
    };
  } // {{example /}}


  if (item.endsWith('/}}')) {
    return {
      type: 'componentSelfClosing',
      value: item.replace(/\W/g, '')
    };
  } // {{example}}


  if (item.startsWith('{{')) {
    return {
      type: 'componentOpen',
      value: item.replace(/\W/g, '')
    };
  }

  return {
    type: 'string',
    value: item
  };
}

function tokenize(mixedString) {
  const tokenStrings = mixedString.split(/(\{\{\/?\s*\w+\s*\/?\}\})/g); // split to components and strings

  return tokenStrings.map(identifyToken);
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+interpolate-components@1.2.1_@types+react@17.0.71_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js



function getCloseIndex(openIndex, tokens) {
  const openToken = tokens[openIndex];
  let nestLevel = 0;

  for (let i = openIndex + 1; i < tokens.length; i++) {
    const token = tokens[i];

    if (token.value === openToken.value) {
      if (token.type === 'componentOpen') {
        nestLevel++;
        continue;
      }

      if (token.type === 'componentClose') {
        if (nestLevel === 0) {
          return i;
        }

        nestLevel--;
      }
    }
  } // if we get this far, there was no matching close token


  throw new Error('Missing closing component token `' + openToken.value + '`');
}

function buildChildren(tokens, components) {
  let children = [];
  let openComponent;
  let openIndex;

  for (let i = 0; i < tokens.length; i++) {
    const token = tokens[i];

    if (token.type === 'string') {
      children.push(token.value);
      continue;
    } // component node should at least be set


    if (components[token.value] === undefined) {
      throw new Error(`Invalid interpolation, missing component node: \`${token.value}\``);
    } // should be either ReactElement or null (both type "object"), all other types deprecated


    if (typeof components[token.value] !== 'object') {
      throw new Error(`Invalid interpolation, component node must be a ReactElement or null: \`${token.value}\``);
    } // we should never see a componentClose token in this loop


    if (token.type === 'componentClose') {
      throw new Error(`Missing opening component token: \`${token.value}\``);
    }

    if (token.type === 'componentOpen') {
      openComponent = components[token.value];
      openIndex = i;
      break;
    } // componentSelfClosing token


    children.push(components[token.value]);
    continue;
  }

  if (openComponent) {
    const closeIndex = getCloseIndex(openIndex, tokens);
    const grandChildTokens = tokens.slice(openIndex + 1, closeIndex);
    const grandChildren = buildChildren(grandChildTokens, components);
    const clonedOpenComponent = /*#__PURE__*/(0,react.cloneElement)(openComponent, {}, grandChildren);
    children.push(clonedOpenComponent);

    if (closeIndex < tokens.length - 1) {
      const siblingTokens = tokens.slice(closeIndex + 1);
      const siblings = buildChildren(siblingTokens, components);
      children = children.concat(siblings);
    }
  }

  children = children.filter(Boolean);

  if (children.length === 0) {
    return null;
  }

  if (children.length === 1) {
    return children[0];
  }

  return /*#__PURE__*/(0,react.createElement)(react.Fragment, null, ...children);
}

function interpolate(options) {
  const {
    mixedString,
    components,
    throwErrors
  } = options;

  if (!components) {
    return mixedString;
  }

  if (typeof components !== 'object') {
    if (throwErrors) {
      throw new Error(`Interpolation Error: unable to process \`${mixedString}\` because components is not an object`);
    }

    return mixedString;
  }

  const tokens = tokenize(mixedString);

  try {
    return buildChildren(tokens, components);
  } catch (error) {
    if (throwErrors) {
      throw new Error(`Interpolation Error: unable to process \`${mixedString}\` because of error \`${error.message}\``);
    }

    return mixedString;
  }
}

/***/ }),

/***/ "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  CustomItemLabel: () => (/* binding */ CustomItemLabel),
  CustomItemLabelOnSearch: () => (/* binding */ CustomItemLabelOnSearch),
  ExpandOnFilter: () => (/* binding */ ExpandOnFilter),
  HighlightFirstMatchingItem: () => (/* binding */ HighlightFirstMatchingItem),
  SelectionMultiple: () => (/* binding */ SelectionMultiple),
  SelectionSingle: () => (/* binding */ SelectionSingle),
  SimpleTree: () => (/* binding */ SimpleTree),
  "default": () => (/* binding */ tree_control_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+interpolate-components@1.2.1_@types+react@17.0.71_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__("../../node_modules/.pnpm/@automattic+interpolate-components@1.2.1_@types+react@17.0.71_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js + 1 modules
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts
var linked_tree_utils = __webpack_require__("../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/tree.tsx + 7 modules
var tree = __webpack_require__("../../packages/js/components/src/experimental-tree-control/tree.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/tree-control.tsx


var _excluded = ["items"];

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var TreeControl = /*#__PURE__*/(0,react.forwardRef)(function ForwardedTree(_ref, ref) {
  var items = _ref.items,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  var linkedTree = (0,linked_tree_utils/* createLinkedTree */.YD)(items, props.createValue);
  return (0,react.createElement)(tree/* Tree */.P, (0,esm_extends/* default */.A)({}, props, {
    ref: ref,
    items: linkedTree
  }));
});
try {
    // @ts-ignore
    TreeControl.displayName = "TreeControl";
    // @ts-ignore
    TreeControl.__docgenInfo = { "description": "", "displayName": "TreeControl", "props": { "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "Item[]" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/tree-control.tsx#TreeControl"] = { docgenInfo: TreeControl.__docgenInfo, name: "TreeControl", path: "../../packages/js/components/src/experimental-tree-control/tree-control.tsx#TreeControl" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/tree.scss
// extracted by mini-css-extract-plugin

;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx











/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


var listItems = [{
  value: '1',
  label: 'Technology'
}, {
  value: '1.1',
  label: 'Notebooks',
  parent: '1'
}, {
  value: '1.2',
  label: 'Phones',
  parent: '1'
}, {
  value: '1.2.1',
  label: 'iPhone',
  parent: '1.2'
}, {
  value: '1.2.1.1',
  label: 'iPhone 14 Pro',
  parent: '1.2.1'
}, {
  value: '1.2.1.2',
  label: 'iPhone 14 Pro Max',
  parent: '1.2.1'
}, {
  value: '1.2.2',
  label: 'Samsung',
  parent: '1.2'
}, {
  value: '1.2.2.1',
  label: 'Samsung Galaxy 22 Plus',
  parent: '1.2.2'
}, {
  value: '1.2.2.2',
  label: 'Samsung Galaxy 22 Ultra',
  parent: '1.2.2'
}, {
  value: '1.3',
  label: 'Wearables',
  parent: '1'
}, {
  value: '2',
  label: 'Hardware'
}, {
  value: '2.1',
  label: 'CPU',
  parent: '2'
}, {
  value: '2.2',
  label: 'GPU',
  parent: '2'
}, {
  value: '2.3',
  label: 'Memory RAM',
  parent: '2'
}, {
  value: '3',
  label: 'Other'
}];
var SimpleTree = function SimpleTree() {
  return (0,react.createElement)(base_control/* default */.Ay, {
    label: "Simple tree",
    id: "simple-tree"
  }, (0,react.createElement)(TreeControl, {
    id: "simple-tree",
    items: listItems
  }));
};
function _shouldItemBeExpanded(item, filter) {
  var _item$children;
  if (!filter || !((_item$children = item.children) !== null && _item$children !== void 0 && _item$children.length)) return false;
  return item.children.some(function (child) {
    if (new RegExp(filter, 'ig').test(child.data.label)) {
      return true;
    }
    return _shouldItemBeExpanded(child, filter);
  });
}
var ExpandOnFilter = function ExpandOnFilter() {
  var _useState = (0,react.useState)(''),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    filter = _useState2[0],
    setFilter = _useState2[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(text_control/* default */.A, {
    value: filter,
    onChange: setFilter
  }), (0,react.createElement)(base_control/* default */.Ay, {
    label: "Expand on filter",
    id: "expand-on-filter"
  }, (0,react.createElement)(TreeControl, {
    id: "expand-on-filter",
    items: listItems,
    shouldItemBeExpanded: function shouldItemBeExpanded(item) {
      return _shouldItemBeExpanded(item, filter);
    }
  })));
};
var CustomItemLabel = function CustomItemLabel() {
  function renderCustomItemLabel(item) {
    return (0,react.createElement)("div", {
      style: {
        display: 'flex',
        gap: 8
      }
    }, (0,react.createElement)("div", {
      style: {
        width: 36,
        height: 36,
        backgroundColor: '#ccc',
        borderRadius: 2
      }
    }), (0,react.createElement)("div", {
      style: {
        display: 'flex',
        flexDirection: 'column'
      }
    }, (0,react.createElement)("strong", null, item.data.label), (0,react.createElement)("small", null, "Some item description")));
  }
  return (0,react.createElement)(base_control/* default */.Ay, {
    label: "Custom item label",
    id: "custom-item-label"
  }, (0,react.createElement)(TreeControl, {
    id: "custom-item-label",
    items: listItems,
    getItemLabel: renderCustomItemLabel
  }));
};
function _getItemLabel(item, text) {
  return (0,react.createElement)("span", null, text ? (0,esm/* default */.A)({
    mixedString: item.data.label.replace(new RegExp(text, 'ig'), function (group) {
      return "{{bold}}".concat(group, "{{/bold}}");
    }),
    components: {
      bold: (0,react.createElement)("b", null)
    }
  }) : item.data.label);
}
var CustomItemLabelOnSearch = function CustomItemLabelOnSearch() {
  var _useState3 = (0,react.useState)(''),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    text = _useState4[0],
    setText = _useState4[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(text_control/* default */.A, {
    value: text,
    onChange: setText
  }), (0,react.createElement)(base_control/* default */.Ay, {
    label: "Custom item label on search",
    id: "custom-item-label-on-search"
  }, (0,react.createElement)(TreeControl, {
    id: "custom-item-label-on-search",
    items: listItems,
    getItemLabel: function getItemLabel(item) {
      return _getItemLabel(item, text);
    },
    shouldItemBeExpanded: (0,react.useCallback)(function (item) {
      return _shouldItemBeExpanded(item, text);
    }, [text])
  })));
};
var SelectionSingle = function SelectionSingle() {
  var _useState5 = (0,react.useState)(listItems[1]),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    selected = _useState6[0],
    setSelected = _useState6[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(base_control/* default */.Ay, {
    label: "Single selection",
    id: "single-selection"
  }, (0,react.createElement)(TreeControl, {
    id: "single-selection",
    items: listItems,
    selected: selected,
    onSelect: function onSelect(value) {
      return setSelected(value);
    }
  })), (0,react.createElement)("pre", null, JSON.stringify(selected, null, 2)));
};
var SelectionMultiple = function SelectionMultiple() {
  var _useState7 = (0,react.useState)([listItems[0], listItems[1]]),
    _useState8 = (0,slicedToArray/* default */.A)(_useState7, 2),
    selected = _useState8[0],
    setSelected = _useState8[1];
  function handleSelect(values) {
    setSelected(function (items) {
      var newItems = values.filter(function (_ref) {
        var value = _ref.value;
        return !items.some(function (item) {
          return item.value === value;
        });
      });
      return [].concat((0,toConsumableArray/* default */.A)(items), (0,toConsumableArray/* default */.A)(newItems));
    });
  }
  function handleRemove(values) {
    setSelected(function (items) {
      return items.filter(function (item) {
        return !values.some(function (_ref2) {
          var value = _ref2.value;
          return item.value === value;
        });
      });
    });
  }
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(base_control/* default */.Ay, {
    label: "Multiple selection",
    id: "multiple-selection"
  }, (0,react.createElement)(TreeControl, {
    id: "multiple-selection",
    items: listItems,
    multiple: true,
    selected: selected,
    onSelect: handleSelect,
    onRemove: handleRemove
  })), (0,react.createElement)("pre", null, JSON.stringify(selected, null, 2)));
};
function getFirstMatchingItem(item, text, memo) {
  if (!text) return false;
  if (memo[text] === item.data.value) return true;
  var matcher = new RegExp(text, 'ig');
  if (matcher.test(item.data.label)) {
    if (!memo[text]) {
      memo[text] = item.data.value;
      return true;
    }
  }
  return false;
}
var HighlightFirstMatchingItem = function HighlightFirstMatchingItem() {
  var _useState9 = (0,react.useState)(''),
    _useState10 = (0,slicedToArray/* default */.A)(_useState9, 2),
    text = _useState10[0],
    setText = _useState10[1];
  var memo = (0,react.useRef)({});
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(text_control/* default */.A, {
    value: text,
    onChange: setText
  }), (0,react.createElement)(base_control/* default */.Ay, {
    label: "Highlight first matching item",
    id: "highlight-first-matching-item"
  }, (0,react.createElement)(TreeControl, {
    id: "highlight-first-matching-item",
    items: listItems,
    getItemLabel: function getItemLabel(item) {
      return _getItemLabel(item, text);
    },
    shouldItemBeExpanded: (0,react.useCallback)(function (item) {
      return _shouldItemBeExpanded(item, text);
    }, [text]),
    shouldItemBeHighlighted: function shouldItemBeHighlighted(item) {
      return getFirstMatchingItem(item, text, memo.current);
    }
  })));
};
/* harmony default export */ const tree_control_story = ({
  title: 'WooCommerce Admin/experimental/TreeControl',
  component: TreeControl
});
SimpleTree.parameters = {
  ...SimpleTree.parameters,
  docs: {
    ...SimpleTree.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <BaseControl label=\"Simple tree\" id=\"simple-tree\">\n            <TreeControl id=\"simple-tree\" items={listItems} />\n        </BaseControl>;\n}",
      ...SimpleTree.parameters?.docs?.source
    }
  }
};
ExpandOnFilter.parameters = {
  ...ExpandOnFilter.parameters,
  docs: {
    ...ExpandOnFilter.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [filter, setFilter] = useState('');\n  return <>\n            <TextControl value={filter} onChange={setFilter} />\n            <BaseControl label=\"Expand on filter\" id=\"expand-on-filter\">\n                <TreeControl id=\"expand-on-filter\" items={listItems} shouldItemBeExpanded={item => shouldItemBeExpanded(item, filter)} />\n            </BaseControl>\n        </>;\n}",
      ...ExpandOnFilter.parameters?.docs?.source
    }
  }
};
CustomItemLabel.parameters = {
  ...CustomItemLabel.parameters,
  docs: {
    ...CustomItemLabel.parameters?.docs,
    source: {
      originalSource: "() => {\n  function renderCustomItemLabel(item: LinkedTree) {\n    return <div style={{\n      display: 'flex',\n      gap: 8\n    }}>\n                <div style={{\n        width: 36,\n        height: 36,\n        backgroundColor: '#ccc',\n        borderRadius: 2\n      }} />\n                <div style={{\n        display: 'flex',\n        flexDirection: 'column'\n      }}>\n                    <strong>{item.data.label}</strong>\n                    <small>Some item description</small>\n                </div>\n            </div>;\n  }\n  return <BaseControl label=\"Custom item label\" id=\"custom-item-label\">\n            <TreeControl id=\"custom-item-label\" items={listItems} getItemLabel={renderCustomItemLabel} />\n        </BaseControl>;\n}",
      ...CustomItemLabel.parameters?.docs?.source
    }
  }
};
CustomItemLabelOnSearch.parameters = {
  ...CustomItemLabelOnSearch.parameters,
  docs: {
    ...CustomItemLabelOnSearch.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [text, setText] = useState('');\n  return <>\n            <TextControl value={text} onChange={setText} />\n            <BaseControl label=\"Custom item label on search\" id=\"custom-item-label-on-search\">\n                <TreeControl id=\"custom-item-label-on-search\" items={listItems} getItemLabel={item => getItemLabel(item, text)} shouldItemBeExpanded={useCallback(item => shouldItemBeExpanded(item, text), [text])} />\n            </BaseControl>\n        </>;\n}",
      ...CustomItemLabelOnSearch.parameters?.docs?.source
    }
  }
};
SelectionSingle.parameters = {
  ...SelectionSingle.parameters,
  docs: {
    ...SelectionSingle.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState(listItems[1]);\n  return <>\n            <BaseControl label=\"Single selection\" id=\"single-selection\">\n                <TreeControl id=\"single-selection\" items={listItems} selected={selected} onSelect={(value: Item) => setSelected(value)} />\n            </BaseControl>\n\n            <pre>{JSON.stringify(selected, null, 2)}</pre>\n        </>;\n}",
      ...SelectionSingle.parameters?.docs?.source
    }
  }
};
SelectionMultiple.parameters = {
  ...SelectionMultiple.parameters,
  docs: {
    ...SelectionMultiple.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState([listItems[0], listItems[1]]);\n  function handleSelect(values: Item[]) {\n    setSelected(items => {\n      const newItems = values.filter(({\n        value\n      }) => !items.some(item => item.value === value));\n      return [...items, ...newItems];\n    });\n  }\n  function handleRemove(values: Item[]) {\n    setSelected(items => items.filter(item => !values.some(({\n      value\n    }) => item.value === value)));\n  }\n  return <>\n            <BaseControl label=\"Multiple selection\" id=\"multiple-selection\">\n                <TreeControl id=\"multiple-selection\" items={listItems} multiple selected={selected} onSelect={handleSelect} onRemove={handleRemove} />\n            </BaseControl>\n\n            <pre>{JSON.stringify(selected, null, 2)}</pre>\n        </>;\n}",
      ...SelectionMultiple.parameters?.docs?.source
    }
  }
};
HighlightFirstMatchingItem.parameters = {
  ...HighlightFirstMatchingItem.parameters,
  docs: {
    ...HighlightFirstMatchingItem.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [text, setText] = useState('');\n  const memo = useRef<Record<string, string>>({});\n  return <>\n            <TextControl value={text} onChange={setText} />\n            <BaseControl label=\"Highlight first matching item\" id=\"highlight-first-matching-item\">\n                <TreeControl id=\"highlight-first-matching-item\" items={listItems} getItemLabel={item => getItemLabel(item, text)} shouldItemBeExpanded={useCallback(item => shouldItemBeExpanded(item, text), [text])} shouldItemBeHighlighted={item => getFirstMatchingItem(item, text, memo.current)} />\n            </BaseControl>\n        </>;\n}",
      ...HighlightFirstMatchingItem.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    SimpleTree.displayName = "SimpleTree";
    // @ts-ignore
    SimpleTree.__docgenInfo = { "description": "", "displayName": "SimpleTree", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#SimpleTree"] = { docgenInfo: SimpleTree.__docgenInfo, name: "SimpleTree", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#SimpleTree" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ExpandOnFilter.displayName = "ExpandOnFilter";
    // @ts-ignore
    ExpandOnFilter.__docgenInfo = { "description": "", "displayName": "ExpandOnFilter", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#ExpandOnFilter"] = { docgenInfo: ExpandOnFilter.__docgenInfo, name: "ExpandOnFilter", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#ExpandOnFilter" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    CustomItemLabel.displayName = "CustomItemLabel";
    // @ts-ignore
    CustomItemLabel.__docgenInfo = { "description": "", "displayName": "CustomItemLabel", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#CustomItemLabel"] = { docgenInfo: CustomItemLabel.__docgenInfo, name: "CustomItemLabel", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#CustomItemLabel" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    CustomItemLabelOnSearch.displayName = "CustomItemLabelOnSearch";
    // @ts-ignore
    CustomItemLabelOnSearch.__docgenInfo = { "description": "", "displayName": "CustomItemLabelOnSearch", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#CustomItemLabelOnSearch"] = { docgenInfo: CustomItemLabelOnSearch.__docgenInfo, name: "CustomItemLabelOnSearch", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#CustomItemLabelOnSearch" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    SelectionSingle.displayName = "SelectionSingle";
    // @ts-ignore
    SelectionSingle.__docgenInfo = { "description": "", "displayName": "SelectionSingle", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#SelectionSingle"] = { docgenInfo: SelectionSingle.__docgenInfo, name: "SelectionSingle", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#SelectionSingle" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    SelectionMultiple.displayName = "SelectionMultiple";
    // @ts-ignore
    SelectionMultiple.__docgenInfo = { "description": "", "displayName": "SelectionMultiple", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#SelectionMultiple"] = { docgenInfo: SelectionMultiple.__docgenInfo, name: "SelectionMultiple", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#SelectionMultiple" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    HighlightFirstMatchingItem.displayName = "HighlightFirstMatchingItem";
    // @ts-ignore
    HighlightFirstMatchingItem.__docgenInfo = { "description": "", "displayName": "HighlightFirstMatchingItem", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#HighlightFirstMatchingItem"] = { docgenInfo: HighlightFirstMatchingItem.__docgenInfo, name: "HighlightFirstMatchingItem", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#HighlightFirstMatchingItem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);