"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6755],{

/***/ "../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ interpolate)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/tokenize.js
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
;// ../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js



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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__("../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts
var linked_tree_utils = __webpack_require__("../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/tree.tsx + 7 modules
var tree = __webpack_require__("../../packages/js/components/src/experimental-tree-control/tree.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/experimental-tree-control/tree-control.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



const tree_control_TreeControl = (0,react.forwardRef)(function ForwardedTree({
  items,
  ...props
}, ref) {
  const linkedTree = (0,linked_tree_utils/* createLinkedTree */.YD)(items, props.createValue);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(tree/* Tree */.P, {
    ...props,
    ref: ref,
    items: linkedTree
  });
});
try {
    // @ts-ignore
    tree_control_TreeControl.displayName = "TreeControl";
    // @ts-ignore
    tree_control_TreeControl.__docgenInfo = { "description": "", "displayName": "TreeControl", "props": { "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "Item[]" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/tree-control.tsx#TreeControl"] = { docgenInfo: tree_control_TreeControl.__docgenInfo, name: "TreeControl", path: "../../packages/js/components/src/experimental-tree-control/tree-control.tsx#TreeControl" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/experimental-tree-control/tree.scss
// extracted by mini-css-extract-plugin

;// ../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



const listItems = [{
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
const SimpleTree = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    label: "Simple tree",
    id: "simple-tree",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_control_TreeControl, {
      id: "simple-tree",
      items: listItems
    })
  });
};
function shouldItemBeExpanded(item, filter) {
  if (!filter || !item.children?.length) return false;
  return item.children.some(child => {
    if (new RegExp(filter, 'ig').test(child.data.label)) {
      return true;
    }
    return shouldItemBeExpanded(child, filter);
  });
}
const ExpandOnFilter = () => {
  const [filter, setFilter] = (0,react.useState)('');
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
      value: filter,
      onChange: setFilter
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
      label: "Expand on filter",
      id: "expand-on-filter",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_control_TreeControl, {
        id: "expand-on-filter",
        items: listItems,
        shouldItemBeExpanded: item => shouldItemBeExpanded(item, filter)
      })
    })]
  });
};
const CustomItemLabel = () => {
  function renderCustomItemLabel(item) {
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      style: {
        display: 'flex',
        gap: 8
      },
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        style: {
          width: 36,
          height: 36,
          backgroundColor: '#ccc',
          borderRadius: 2
        }
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        style: {
          display: 'flex',
          flexDirection: 'column'
        },
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
          children: item.data.label
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("small", {
          children: "Some item description"
        })]
      })]
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    label: "Custom item label",
    id: "custom-item-label",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_control_TreeControl, {
      id: "custom-item-label",
      items: listItems,
      getItemLabel: renderCustomItemLabel
    })
  });
};
function getItemLabel(item, text) {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
    children: text ? (0,esm/* default */.A)({
      mixedString: item.data.label.replace(new RegExp(text, 'ig'), group => `{{bold}}${group}{{/bold}}`),
      components: {
        bold: /*#__PURE__*/(0,jsx_runtime.jsx)("b", {})
      }
    }) : item.data.label
  });
}
const CustomItemLabelOnSearch = () => {
  const [text, setText] = (0,react.useState)('');
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
      value: text,
      onChange: setText
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
      label: "Custom item label on search",
      id: "custom-item-label-on-search",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_control_TreeControl, {
        id: "custom-item-label-on-search",
        items: listItems,
        getItemLabel: item => getItemLabel(item, text),
        shouldItemBeExpanded: (0,react.useCallback)(item => shouldItemBeExpanded(item, text), [text])
      })
    })]
  });
};
const SelectionSingle = () => {
  const [selected, setSelected] = (0,react.useState)(listItems[1]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
      label: "Single selection",
      id: "single-selection",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_control_TreeControl, {
        id: "single-selection",
        items: listItems,
        selected: selected,
        onSelect: value => setSelected(value)
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("pre", {
      children: JSON.stringify(selected, null, 2)
    })]
  });
};
const SelectionMultiple = () => {
  const [selected, setSelected] = (0,react.useState)([listItems[0], listItems[1]]);
  function handleSelect(values) {
    setSelected(items => {
      const newItems = values.filter(({
        value
      }) => !items.some(item => item.value === value));
      return [...items, ...newItems];
    });
  }
  function handleRemove(values) {
    setSelected(items => items.filter(item => !values.some(({
      value
    }) => item.value === value)));
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
      label: "Multiple selection",
      id: "multiple-selection",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_control_TreeControl, {
        id: "multiple-selection",
        items: listItems,
        multiple: true,
        selected: selected,
        onSelect: handleSelect,
        onRemove: handleRemove
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("pre", {
      children: JSON.stringify(selected, null, 2)
    })]
  });
};
function getFirstMatchingItem(item, text, memo) {
  if (!text) return false;
  if (memo[text] === item.data.value) return true;
  const matcher = new RegExp(text, 'ig');
  if (matcher.test(item.data.label)) {
    if (!memo[text]) {
      memo[text] = item.data.value;
      return true;
    }
  }
  return false;
}
const HighlightFirstMatchingItem = () => {
  const [text, setText] = (0,react.useState)('');
  const memo = (0,react.useRef)({});
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
      value: text,
      onChange: setText
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
      label: "Highlight first matching item",
      id: "highlight-first-matching-item",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_control_TreeControl, {
        id: "highlight-first-matching-item",
        items: listItems,
        getItemLabel: item => getItemLabel(item, text),
        shouldItemBeExpanded: (0,react.useCallback)(item => shouldItemBeExpanded(item, text), [text]),
        shouldItemBeHighlighted: item => getFirstMatchingItem(item, text, memo.current)
      })
    })]
  });
};
/* harmony default export */ const tree_control_story = ({
  title: 'Experimental/TreeControl',
  component: tree_control_TreeControl
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
    TreeControl.displayName = "TreeControl";
    // @ts-ignore
    TreeControl.__docgenInfo = { "description": "", "displayName": "TreeControl", "props": { "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "Item[]" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#TreeControl"] = { docgenInfo: TreeControl.__docgenInfo, name: "TreeControl", path: "../../packages/js/components/src/experimental-tree-control/stories/tree-control.story.tsx#TreeControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ checkbox_control_default)
});

// UNUSED EXPORTS: CheckboxControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs
var use_ref_effect = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs
var library_reset = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.mjs
// packages/icons/src/library/check.tsx


var check_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M16.5 7.5 10 13.9l-2.5-2.4-1 1 3.5 3.6 7.5-7.6z" }) });

//# sourceMappingURL=check.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js








function CheckboxControl(props) {
  const {
    __nextHasNoMarginBottom,
    label,
    className,
    heading,
    checked,
    indeterminate,
    help,
    id: idProp,
    onChange,
    onClick,
    ...additionalProps
  } = props;
  if (heading) {
    (0,build_module/* default */.A)("`heading` prop in `CheckboxControl`", {
      alternative: "a separate element to implement a heading",
      since: "5.8"
    });
  }
  const [showCheckedIcon, setShowCheckedIcon] = (0,react.useState)(false);
  const [showIndeterminateIcon, setShowIndeterminateIcon] = (0,react.useState)(false);
  const ref = (0,use_ref_effect/* default */.A)((node) => {
    if (!node) {
      return;
    }
    node.indeterminate = !!indeterminate;
    setShowCheckedIcon(node.matches(":checked"));
    setShowIndeterminateIcon(node.matches(":indeterminate"));
  }, [checked, indeterminate]);
  const id = (0,use_instance_id/* default */.A)(CheckboxControl, "inspector-checkbox-control", idProp);
  const onChangeValue = (event) => onChange(event.target.checked);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "CheckboxControl",
    label: heading,
    id,
    help: help && /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
      className: "components-checkbox-control__help",
      children: help
    }),
    className: (0,clsx/* default */.A)("components-checkbox-control", className),
    children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(component/* default */.A, {
      spacing: 0,
      justify: "start",
      alignment: "top",
      children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)("span", {
        className: "components-checkbox-control__input-container",
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)("input", {
          ref,
          id,
          className: "components-checkbox-control__input",
          type: "checkbox",
          value: "1",
          onChange: onChangeValue,
          checked,
          "aria-describedby": !!help ? id + "__help" : void 0,
          onClick: (event) => {
            event.currentTarget.focus();
            onClick?.(event);
          },
          ...additionalProps
        }), showIndeterminateIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: library_reset/* default */.A,
          className: "components-checkbox-control__indeterminate",
          role: "presentation"
        }) : null, showCheckedIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: check_default,
          className: "components-checkbox-control__checked",
          role: "presentation"
        }) : null]
      }), label && /* @__PURE__ */ (0,jsx_runtime.jsx)("label", {
        className: "components-checkbox-control__label",
        htmlFor: id,
        children: label
      })]
    })
  });
}
var checkbox_control_default = CheckboxControl;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ text_control_default)
/* harmony export */ });
/* unused harmony export TextControl */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _utils_deprecated_36px_size__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");






function UnforwardedTextControl(props, ref) {
  const {
    __nextHasNoMarginBottom,
    __next40pxDefaultSize = false,
    label,
    hideLabelFromVision,
    value,
    help,
    id: idProp,
    className,
    onChange,
    type = "text",
    ...additionalProps
  } = props;
  const id = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(TextControl, "inspector-text-control", idProp);
  const onChangeValue = (event) => onChange(event.target.value);
  (0,_utils_deprecated_36px_size__WEBPACK_IMPORTED_MODULE_2__/* .maybeWarnDeprecated36pxSize */ .M)({
    componentName: "TextControl",
    size: void 0,
    __next40pxDefaultSize
  });
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_base_control__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "TextControl",
    label,
    hideLabelFromVision,
    id,
    help,
    className,
    children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)("components-text-control__input", {
        "is-next-40px-default-size": __next40pxDefaultSize
      }),
      type,
      id,
      value,
      onChange: onChangeValue,
      "aria-describedby": !!help ? id + "__help" : void 0,
      ref,
      ...additionalProps
    })
  });
}
const TextControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.forwardRef)(UnforwardedTextControl);
var text_control_default = TextControl;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   M: () => (/* binding */ maybeWarnDeprecated36pxSize)
/* harmony export */ });
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");

function maybeWarnDeprecated36pxSize({
  componentName,
  __next40pxDefaultSize,
  size,
  __shouldNotWarnDeprecated36pxSize
}) {
  if (__shouldNotWarnDeprecated36pxSize || __next40pxDefaultSize || size !== void 0 && size !== "default") {
    return;
  }
  (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(`36px default size for wp.components.${componentName}`, {
    since: "6.8",
    version: "7.1",
    hint: "Set the `__next40pxDefaultSize` prop to true to start opting into the new default size, which will become the default in a future version."
  });
}

//# sourceMappingURL=deprecated-36px-size.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GB: () => (/* binding */ ensureNumber),
/* harmony export */   J5: () => (/* binding */ isValueDefined),
/* harmony export */   r6: () => (/* binding */ isValueEmpty)
/* harmony export */ });
/* unused harmony exports getDefinedValue, stringToNumber */
function isValueDefined(value) {
  return value !== void 0 && value !== null;
}
function isValueEmpty(value) {
  const isEmptyString = value === "";
  return !isValueDefined(value) || isEmptyString;
}
function getDefinedValue(values = [], fallbackValue) {
  var _values$find;
  return (_values$find = values.find(isValueDefined)) !== null && _values$find !== void 0 ? _values$find : fallbackValue;
}
const stringToNumber = (value) => {
  return parseFloat(value);
};
const ensureNumber = (value) => {
  return typeof value === "string" ? stringToNumber(value) : value;
};

//# sourceMappingURL=values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ use_instance_id_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

const instanceMap = /* @__PURE__ */ new WeakMap();
function createId(object) {
  const instances = instanceMap.get(object) || 0;
  instanceMap.set(object, instances + 1);
  return instances;
}
function useInstanceId(object, prefix, preferredId) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    if (preferredId) {
      return preferredId;
    }
    const id = createId(object);
    return prefix ? `${prefix}-${id}` : id;
  }, [object, preferredId, prefix]);
}
var use_instance_id_default = useInstanceId;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useMergeRefs)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

function assignRef(ref, value) {
  if (typeof ref === "function") {
    ref(value);
  } else if (ref && ref.hasOwnProperty("current")) {
    ref.current = value;
  }
}
function useMergeRefs(refs) {
  const element = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const isAttachedRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const didElementChangeRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const previousRefsRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)([]);
  const currentRefsRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(refs);
  currentRefsRef.current = refs;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect)(() => {
    if (didElementChangeRef.current === false && isAttachedRef.current === true) {
      refs.forEach((ref, index) => {
        const previousRef = previousRefsRef.current[index];
        if (ref !== previousRef) {
          assignRef(previousRef, null);
          assignRef(ref, element.current);
        }
      });
    }
    previousRefsRef.current = refs;
  }, refs);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect)(() => {
    didElementChangeRef.current = false;
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)((value) => {
    assignRef(element, value);
    didElementChangeRef.current = true;
    isAttachedRef.current = value !== null;
    const refsToAssign = value ? currentRefsRef.current : previousRefsRef.current;
    for (const ref of refsToAssign) {
      assignRef(ref, value);
    }
  }, []);
}

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useRefEffect)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-ref-effect/index.ts

function useRefEffect(callback, dependencies) {
  const cleanupRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(void 0);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)((node) => {
    if (node) {
      cleanupRef.current = callback(node);
    } else if (cleanupRef.current) {
      cleanupRef.current();
    }
  }, dependencies);
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   S: () => (/* binding */ decodeEntities)
/* harmony export */ });
let _decodeTextArea;
function decodeEntities(html) {
  if ("string" !== typeof html || -1 === html.indexOf("&")) {
    return html;
  }
  if (void 0 === _decodeTextArea) {
    if (document.implementation && document.implementation.createHTMLDocument) {
      _decodeTextArea = document.implementation.createHTMLDocument("").createElement("textarea");
    } else {
      _decodeTextArea = document.createElement("textarea");
    }
  }
  _decodeTextArea.innerHTML = html;
  const decoded = _decodeTextArea.textContent ?? "";
  _decodeTextArea.innerHTML = "";
  return decoded;
}

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_down_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_down_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z" }) });

//# sourceMappingURL=chevron-down.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_up_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_up_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" }) });

//# sourceMappingURL=chevron-up.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/plus.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ plus_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var plus_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z" }) });

//# sourceMappingURL=plus.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/icons/src/icon/index.ts

var icon_default = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(
  ({ icon, size = 24, ...props }, ref) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
      width: size,
      height: size,
      ...props,
      ref
    });
  }
);

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ reset_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/icons/src/library/reset.tsx


var reset_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M7 11.5h10V13H7z" }) });

//# sourceMappingURL=reset.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t4: () => (/* binding */ SVG),
/* harmony export */   wA: () => (/* binding */ Path)
/* harmony export */ });
/* unused harmony exports Circle, Defs, G, Line, LinearGradient, Polygon, RadialGradient, Rect, Stop */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/primitives/src/svg/index.js



var Circle = (props) => createElement("circle", props);
var G = (props) => createElement("g", props);
var Line = (props) => createElement("line", props);
var Path = (props) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("path", props);
var Polygon = (props) => createElement("polygon", props);
var Rect = (props) => createElement("rect", props);
var Defs = (props) => createElement("defs", props);
var RadialGradient = (props) => createElement("radialGradient", props);
var LinearGradient = (props) => createElement("linearGradient", props);
var Stop = (props) => createElement("stop", props);
var SVG = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.forwardRef)(
  /**
   * @param {SVGProps}                          props isPressed indicates whether the SVG should appear as pressed.
   *                                                  Other props will be passed through to svg component.
   * @param {React.ForwardedRef<SVGSVGElement>} ref   The forwarded ref to the SVG element.
   *
   * @return {React.JSX.Element} Stop component
   */
  ({ className, isPressed, ...props }, ref) => {
    const appliedProps = {
      ...props,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, { "is-pressed": isPressed }) || void 0,
      "aria-hidden": true,
      focusable: false
    };
    return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("svg", { ...appliedProps, ref });
  }
);
SVG.displayName = "SVG";

//# sourceMappingURL=index.mjs.map


/***/ })

}]);