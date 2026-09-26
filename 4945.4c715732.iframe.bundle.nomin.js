"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4945],{

/***/ "../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   TN: () => (/* binding */ toggleNode),
/* harmony export */   VW: () => (/* binding */ countNumberOfNodes),
/* harmony export */   YD: () => (/* binding */ createLinkedTree),
/* harmony export */   g_: () => (/* binding */ getNodeDataByIndex),
/* harmony export */   p_: () => (/* binding */ getVisibleNodeIndex)
/* harmony export */ });
/**
 * Internal dependencies
 */

const shouldItemBeExpanded = (item, createValue) => {
  if (!createValue || !item.children?.length) return false;
  return item.children.some(child => {
    if (new RegExp(createValue || '', 'ig').test(child.data.label)) {
      return true;
    }
    return shouldItemBeExpanded(child, createValue);
  });
};
function findChildren(items, memo = {}, parent, createValue) {
  const children = [];
  const others = [];
  items.forEach(item => {
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
  return children.map(child => {
    const linkedTree = memo[child.value];
    linkedTree.parent = child.parent ? memo[child.parent] : undefined;
    linkedTree.children = findChildren(others, memo, child.value, createValue);
    linkedTree.data.isExpanded = linkedTree.children.length === 0 ? true : shouldItemBeExpanded(linkedTree, createValue);
    return linkedTree;
  });
}
function populateIndexes(linkedTree, startCount = 0) {
  let count = startCount;
  function populate(tree) {
    for (const node of tree) {
      node.index = count;
      count++;
      if (node.children) {
        count = populate(node.children);
      }
    }
    return count;
  }
  populate(linkedTree);
  return linkedTree;
}

// creates a linked tree from an array of Items
function createLinkedTree(items, value) {
  const augmentedItems = items.map(i => ({
    ...i,
    isExpanded: false
  }));
  return populateIndexes(findChildren(augmentedItems, {}, undefined, value));
}

// Toggles the expanded state of a node in a linked tree
function toggleNode(tree, number, value) {
  return tree.map(node => {
    return {
      ...node,
      children: node.children ? toggleNode(node.children, number, value) : node.children,
      data: {
        ...node.data,
        isExpanded: node.index === number ? value : node.data.isExpanded
      },
      ...(node.parent ? {
        parent: {
          ...node.parent,
          data: {
            ...node.parent.data,
            isExpanded: node.parent.index === number ? value : node.parent.data.isExpanded
          }
        }
      } : {})
    };
  });
}

// Gets the index of the next/previous visible node in the linked tree
function getVisibleNodeIndex(tree, highlightedIndex, direction) {
  if (direction === 'down') {
    for (const node of tree) {
      if (!node.parent || node.parent.data.isExpanded) {
        if (node.index !== undefined && node.index >= highlightedIndex) {
          return node.index;
        }
        const visibleNodeIndex = getVisibleNodeIndex(node.children, highlightedIndex, direction);
        if (visibleNodeIndex !== undefined) {
          return visibleNodeIndex;
        }
      }
    }
  } else {
    for (let i = tree.length - 1; i >= 0; i--) {
      const node = tree[i];
      if (!node.parent || node.parent.data.isExpanded) {
        const visibleNodeIndex = getVisibleNodeIndex(node.children, highlightedIndex, direction);
        if (visibleNodeIndex !== undefined) {
          return visibleNodeIndex;
        }
        if (node.index !== undefined && node.index <= highlightedIndex) {
          return node.index;
        }
      }
    }
  }
  return undefined;
}

// Counts the number of nodes in a LinkedTree
function countNumberOfNodes(linkedTree) {
  let count = 0;
  for (const node of linkedTree) {
    count++;
    if (node.children) {
      count += countNumberOfNodes(node.children);
    }
  }
  return count;
}

// Gets the data of a node by its index
function getNodeDataByIndex(linkedTree, index) {
  for (const node of linkedTree) {
    if (node.index === index) {
      return node.data;
    }
    if (node.children) {
      const child = getNodeDataByIndex(node.children, index);
      if (child) {
        return child;
      }
    }
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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/plus.js
var plus = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/plus.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.js");
;// ../../packages/js/components/src/experimental-tree-control/hooks/use-tree.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

function useTree({
  items,
  level = 1,
  role = 'listbox',
  multiple,
  selected,
  getItemLabel,
  shouldItemBeExpanded,
  shouldItemBeHighlighted,
  onSelect,
  onRemove,
  shouldNotRecursivelySelect,
  createValue,
  onTreeBlur,
  onCreateNew,
  shouldShowCreateButton,
  onFirstItemLoop,
  onEscape,
  highlightedIndex,
  onExpand,
  ...props
}) {
  return {
    level,
    items,
    treeProps: {
      ...props,
      role
    },
    treeItemProps: {
      level,
      multiple,
      selected,
      getLabel: getItemLabel,
      shouldItemBeExpanded,
      shouldItemBeHighlighted,
      shouldNotRecursivelySelect,
      onSelect,
      onRemove
    }
  };
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js
var html_entities_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
;// ../../packages/js/components/src/experimental-tree-control/hooks/use-expander.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

function useExpander({
  shouldItemBeExpanded,
  item
}) {
  const [isExpanded, setExpanded] = (0,react.useState)(false);
  (0,react.useEffect)(() => {
    if (item.children?.length && typeof shouldItemBeExpanded === 'function' && !isExpanded) {
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
    setExpanded(prev => !prev);
  }
  return {
    isExpanded,
    onExpand,
    onCollapse,
    onToggleExpand
  };
}
;// ../../packages/js/components/src/experimental-tree-control/hooks/use-highlighter.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

function useHighlighter({
  item,
  multiple,
  checkedStatus,
  shouldItemBeHighlighted
}) {
  const isHighlighted = (0,react.useMemo)(() => {
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
    isHighlighted
  };
}
;// ../../packages/js/components/src/experimental-tree-control/hooks/use-keyboard.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

function getFirstChild(currentHeading) {
  const parentTreeItem = currentHeading?.closest('.experimental-woocommerce-tree-item');
  const firstSubTreeItem = parentTreeItem?.querySelector('.experimental-woocommerce-tree > .experimental-woocommerce-tree-item');
  const label = firstSubTreeItem?.querySelector('.experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label');
  return label ?? null;
}
function getFirstAncestor(currentHeading) {
  const parentTree = currentHeading?.closest('.experimental-woocommerce-tree');
  const grandParentTreeItem = parentTree?.closest('.experimental-woocommerce-tree-item');
  const label = grandParentTreeItem?.querySelector('.experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label');
  return label ?? null;
}
function getAllHeadings(currentHeading) {
  const rootTree = currentHeading.closest('.experimental-woocommerce-tree--level-1');
  return rootTree?.querySelectorAll('.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading');
}
const step = {
  ArrowDown: 1,
  ArrowUp: -1
};
function getNextFocusableElement(currentHeading, code) {
  const headingsNodeList = getAllHeadings(currentHeading);
  if (!headingsNodeList) return null;
  let currentHeadingIndex = 0;
  for (const heading of headingsNodeList.values()) {
    if (heading === currentHeading) break;
    currentHeadingIndex++;
  }
  if (currentHeadingIndex < 0 || currentHeadingIndex >= headingsNodeList.length) {
    return null;
  }
  const heading = headingsNodeList.item(currentHeadingIndex + (step[code] ?? 0));
  return heading?.querySelector('.experimental-woocommerce-tree-item__label');
}
function getFirstFocusableElement(currentHeading) {
  const headingsNodeList = getAllHeadings(currentHeading);
  if (!headingsNodeList) return null;
  return headingsNodeList.item(0).querySelector('.experimental-woocommerce-tree-item__label');
}
function getLastFocusableElement(currentHeading) {
  const headingsNodeList = getAllHeadings(currentHeading);
  if (!headingsNodeList) return null;
  return headingsNodeList.item(headingsNodeList.length - 1).querySelector('.experimental-woocommerce-tree-item__label');
}
function useKeyboard({
  item,
  isExpanded,
  onExpand,
  onCollapse,
  onToggleExpand,
  onLastItemLoop,
  onFirstItemLoop
}) {
  function onKeyDown(event) {
    if (event.code === 'ArrowRight') {
      event.preventDefault();
      if (item.children.length > 0) {
        if (isExpanded) {
          const element = getFirstChild(event.currentTarget);
          return element?.focus();
        }
        onExpand();
      }
    }
    if (event.code === 'ArrowLeft') {
      event.preventDefault();
      if (!isExpanded && item.parent) {
        const element = getFirstAncestor(event.currentTarget);
        return element?.focus();
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
      const element = getNextFocusableElement(event.currentTarget, event.code);
      element?.focus();
      if (event.code === 'ArrowDown' && !element && onLastItemLoop) {
        onLastItemLoop(event);
      }
      if (event.code === 'ArrowUp' && !element && onFirstItemLoop) {
        onFirstItemLoop(event);
      }
    }
    if (event.code === 'Home') {
      event.preventDefault();
      const element = getFirstFocusableElement(event.currentTarget);
      element?.focus();
    }
    if (event.code === 'End') {
      event.preventDefault();
      const element = getLastFocusableElement(event.currentTarget);
      element?.focus();
    }
  }
  return {
    onKeyDown
  };
}
;// ../../packages/js/components/src/experimental-tree-control/hooks/use-selection.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

let selectedItemsMap = {};
let indeterminateMemo = {};
function getDeepChildren(item) {
  if (item.children.length) {
    const children = item.children.map(({
      data
    }) => data);
    item.children.forEach(child => {
      children.push(...getDeepChildren(child));
    });
    return children;
  }
  return [];
}
function isIndeterminate(selectedItems, children, memo = indeterminateMemo) {
  if (children?.length) {
    for (const child of children) {
      if (child.data.value in indeterminateMemo) {
        return true;
      }
      const isChildSelected = child.data.value in selectedItems;
      if (!isChildSelected || isIndeterminate(selectedItems, child.children, memo)) {
        indeterminateMemo[child.data.value] = true;
        return true;
      }
    }
  }
  return false;
}
function mapSelectedItems(selected = []) {
  const selectedArray = Array.isArray(selected) ? selected : [selected];
  return selectedArray.reduce((map, selectedItem, index) => ({
    ...map,
    [selectedItem.value]: index
  }), {});
}
function hasSelectedSibblingChildren(children, values, selectedItems) {
  return children.some(child => {
    const isChildSelected = child.data.value in selectedItems;
    if (!isChildSelected) return false;
    return !values.some(childValue => childValue.value === child.data.value);
  });
}
function useSelection({
  item,
  multiple,
  shouldNotRecursivelySelect,
  selected,
  level,
  index,
  onSelect,
  onRemove
}) {
  const selectedItems = (0,react.useMemo)(() => {
    if (level === 1 && index === 0) {
      selectedItemsMap = mapSelectedItems(selected);
      indeterminateMemo = {};
    }
    return selectedItemsMap;
  }, [selected, level, index]);
  const checkedStatus = (0,react.useMemo)(() => {
    if (item.data.value in selectedItems) {
      if (multiple && !shouldNotRecursivelySelect && isIndeterminate(selectedItems, item.children)) {
        return 'indeterminate';
      }
      return 'checked';
    }
    return 'unchecked';
  }, [selectedItems, item, multiple]);
  function onSelectChild(checked) {
    let value = item.data;
    if (multiple) {
      value = [item.data];
      if (item.children.length && !shouldNotRecursivelySelect) {
        value.push(...getDeepChildren(item));
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
      value = [item.data, ...value];
    }
    onSelect(value);
  }
  function onRemoveChildren(value) {
    if (typeof onRemove !== 'function') return;
    if (multiple && item.children?.length && !shouldNotRecursivelySelect) {
      const hasSelectedSibbling = hasSelectedSibblingChildren(item.children, value, selectedItems);
      if (!hasSelectedSibbling) {
        value = [item.data, ...value];
      }
    }
    onRemove(value);
  }
  return {
    multiple,
    selected,
    checkedStatus,
    onSelectChild,
    onSelectChildren,
    onRemoveChildren
  };
}
;// ../../packages/js/components/src/experimental-tree-control/hooks/use-tree-item.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





function useTreeItem({
  item,
  level,
  multiple,
  shouldNotRecursivelySelect,
  selected,
  index,
  getLabel,
  shouldItemBeExpanded,
  shouldItemBeHighlighted,
  onSelect,
  onRemove,
  isExpanded,
  onCreateNew,
  shouldShowCreateButton,
  onLastItemLoop,
  onFirstItemLoop,
  onTreeBlur,
  onEscape,
  highlightedIndex,
  isHighlighted,
  onExpand,
  ...props
}) {
  const nextLevel = level + 1;
  const expander = useExpander({
    item,
    shouldItemBeExpanded
  });
  const selection = useSelection({
    item,
    multiple,
    selected,
    level,
    index,
    onSelect,
    onRemove,
    shouldNotRecursivelySelect
  });
  const highlighter = useHighlighter({
    item,
    checkedStatus: selection.checkedStatus,
    multiple,
    shouldItemBeHighlighted
  });
  const subTreeId = `experimental-woocommerce-tree__group-${(0,use_instance_id/* default */.A)(useTreeItem)}`;
  const {
    onKeyDown
  } = useKeyboard({
    ...expander,
    onLastItemLoop,
    onFirstItemLoop,
    item
  });
  return {
    item,
    level: nextLevel,
    expander,
    selection,
    highlighter,
    getLabel,
    treeItemProps: {
      ...props,
      id: 'woocommerce-experimental-tree-control__menu-item-' + item.index,
      role: 'option'
    },
    headingProps: {
      role: 'treeitem',
      'aria-selected': selection.checkedStatus !== 'unchecked',
      'aria-expanded': item.children.length ? item.data.isExpanded : undefined,
      'aria-owns': item.children.length && item.data.isExpanded ? subTreeId : undefined,
      style: {
        '--level': level
      },
      onKeyDown
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
      shouldItemBeExpanded,
      shouldItemBeHighlighted,
      shouldNotRecursivelySelect,
      onSelect: selection.onSelectChildren,
      onRemove: selection.onRemoveChildren
    }
  };
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/experimental-tree-control/tree-item.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */



const TreeItem = (0,react.forwardRef)(function ForwardedTreeItem(props, ref) {
  const {
    item,
    treeItemProps,
    headingProps,
    treeProps,
    selection,
    getLabel
  } = useTreeItem({
    ...props,
    ref
  });
  function handleKeyDown(event) {
    if (event.key === 'Escape' && props.onEscape) {
      event.preventDefault();
      props.onEscape();
    } else if (event.key === 'ArrowLeft') {
      if (item.index !== undefined) {
        props.onExpand?.(item.index, false);
      }
    } else if (event.key === 'ArrowRight') {
      if (item.index !== undefined) {
        props.onExpand?.(item.index, true);
      }
    }
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("li", {
    ...treeItemProps,
    className: (0,clsx/* default */.A)(treeItemProps.className, 'experimental-woocommerce-tree-item', {
      'experimental-woocommerce-tree-item--highlighted': props.isHighlighted
    }),
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      ...headingProps,
      className: "experimental-woocommerce-tree-item__heading",
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("label", {
        className: "experimental-woocommerce-tree-item__label",
        children: [selection.multiple ? /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control/* default */.A, {
          indeterminate: selection.checkedStatus === 'indeterminate',
          checked: selection.checkedStatus === 'checked',
          onChange: selection.onSelectChild,
          onKeyDown: handleKeyDown,
          __nextHasNoMarginBottom: true
        }) : /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
          type: "checkbox",
          className: "experimental-woocommerce-tree-item__checkbox",
          checked: selection.checkedStatus === 'checked',
          onChange: event => selection.onSelectChild(event.target.checked),
          onKeyDown: handleKeyDown
        }), typeof getLabel === 'function' ? getLabel(item) : /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: (0,html_entities_build_module/* decodeEntities */.S)(item.data.label)
        })]
      }), Boolean(item.children?.length) && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "experimental-woocommerce-tree-item__expander",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          icon: item.data.isExpanded ? chevron_up/* default */.A : chevron_down/* default */.A,
          onClick: () => {
            if (item.index !== undefined) {
              props.onExpand?.(item.index, !item.data.isExpanded);
            }
          },
          onKeyDown: handleKeyDown,
          className: "experimental-woocommerce-tree-item__expander",
          "aria-label": item.data.isExpanded ? (0,build_module.__)('Collapse', 'woocommerce') : (0,build_module.__)('Expand', 'woocommerce')
        })
      })]
    }), Boolean(item.children.length) && item.data.isExpanded && /*#__PURE__*/(0,jsx_runtime.jsx)(Tree, {
      ...treeProps,
      highlightedIndex: props.highlightedIndex,
      onExpand: props.onExpand,
      onEscape: props.onEscape
    })]
  });
});
try {
    // @ts-ignore
    TreeItem.displayName = "TreeItem";
    // @ts-ignore
    TreeItem.__docgenInfo = { "description": "", "displayName": "TreeItem", "props": { "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "level": { "defaultValue": null, "description": "", "name": "level", "required": true, "type": { "name": "number" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "index": { "defaultValue": null, "description": "", "name": "index", "required": true, "type": { "name": "number" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "item": { "defaultValue": null, "description": "", "name": "item", "required": true, "type": { "name": "LinkedTree" } }, "isFocused": { "defaultValue": null, "description": "", "name": "isFocused", "required": false, "type": { "name": "boolean" } }, "isHighlighted": { "defaultValue": null, "description": "", "name": "isHighlighted", "required": false, "type": { "name": "boolean" } }, "getLabel": { "defaultValue": null, "description": "", "name": "getLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "onLastItemLoop": { "defaultValue": null, "description": "", "name": "onLastItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/tree-item.tsx#TreeItem"] = { docgenInfo: TreeItem.__docgenInfo, name: "TreeItem", path: "../../packages/js/components/src/experimental-tree-control/tree-item.tsx#TreeItem" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts
var linked_tree_utils = __webpack_require__("../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts");
;// ../../packages/js/components/src/experimental-tree-control/tree.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */




const Tree = (0,react.forwardRef)(function ForwardedTree(props, forwardedRef) {
  const rootListRef = (0,react.useRef)(null);
  const ref = (0,use_merge_refs/* default */.A)([rootListRef, forwardedRef]);
  const {
    level,
    items,
    treeProps,
    treeItemProps
  } = useTree({
    ...props,
    ref
  });
  const numberOfItems = (0,linked_tree_utils/* countNumberOfNodes */.VW)(items);
  const isCreateButtonVisible = props.shouldShowCreateButton && props.shouldShowCreateButton(props.createValue);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [items.length || isCreateButtonVisible ? /*#__PURE__*/(0,jsx_runtime.jsx)("ol", {
      ...treeProps,
      className: (0,clsx/* default */.A)(treeProps.className, 'experimental-woocommerce-tree', `experimental-woocommerce-tree--level-${level}`),
      children: items.map((child, index) => /*#__PURE__*/(0,react.createElement)(TreeItem, {
        ...treeItemProps,
        isHighlighted: props.highlightedIndex === child.index,
        onExpand: props.onExpand,
        highlightedIndex: props.highlightedIndex,
        isExpanded: child.data.isExpanded,
        key: child.data.value,
        item: child,
        index: index
        // Button ref is not working, so need to use CSS directly
        ,
        onLastItemLoop: () => {
          rootListRef.current?.closest('ol[role="listbox"]')?.parentElement?.querySelector('.experimental-woocommerce-tree__button')?.focus();
        },
        onFirstItemLoop: props.onFirstItemLoop,
        onEscape: props.onEscape
      }))
    }) : null, isCreateButtonVisible && /*#__PURE__*/(0,jsx_runtime.jsxs)(build_module_button/* default */.Ay, {
      id: 'woocommerce-experimental-tree-control__menu-item-' + numberOfItems,
      className: (0,clsx/* default */.A)('experimental-woocommerce-tree__button', {
        'experimental-woocommerce-tree__button--highlighted': props.highlightedIndex === numberOfItems
      }),
      onClick: () => {
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
      onKeyDown: event => {
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
          event.preventDefault();
          if (event.key === 'ArrowUp') {
            const allHeadings = event.nativeEvent.srcElement.previousSibling.querySelectorAll('.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading');
            allHeadings[allHeadings.length - 1]?.querySelector('.experimental-woocommerce-tree-item__label')?.focus();
          }
        } else if (event.key === 'Escape' && props.onEscape) {
          event.preventDefault();
          props.onEscape();
        }
      },
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
        icon: plus/* default */.A,
        size: 20
      }), props.createValue ? (0,build_module/* sprintf */.nv)(/* translators: %s: create value */
      (0,build_module.__)('Create "%s"', 'woocommerce'), props.createValue) : (0,build_module.__)('Create new', 'woocommerce')]
    })]
  });
});
try {
    // @ts-ignore
    Tree.displayName = "Tree";
    // @ts-ignore
    Tree.__docgenInfo = { "description": "", "displayName": "Tree", "props": { "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "level": { "defaultValue": null, "description": "", "name": "level", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "LinkedTree[]" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-tree-control/tree.tsx#Tree"] = { docgenInfo: Tree.__docgenInfo, name: "Tree", path: "../../packages/js/components/src/experimental-tree-control/tree.tsx#Tree" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);