"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2721],{

/***/ "../../packages/js/components/src/experimental-select-tree-control/stories/select-tree-control.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  MultipleSelectTree: () => (/* binding */ MultipleSelectTree),
  SingleSelectTree: () => (/* binding */ SingleSelectTree),
  SingleWithinModalUsingBodyDropdownPlacement: () => (/* binding */ SingleWithinModalUsingBodyDropdownPlacement),
  "default": () => (/* binding */ select_tree_control_story)
});

// NAMESPACE OBJECT: ../../packages/js/components/src/experimental-select-tree-control/select-tree-menu.tsx
var select_tree_menu_namespaceObject = {};
__webpack_require__.r(select_tree_menu_namespaceObject);
__webpack_require__.d(select_tree_menu_namespaceObject, {
  e: () => (SelectTreeMenu)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/index.js + 11 modules
var slot_fill = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/index.js + 5 modules
var modal = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js
var close_small = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/index.js + 5 modules
var a11y_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts
var linked_tree_utils = __webpack_require__("../../packages/js/components/src/experimental-tree-control/linked-tree-utils.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/selected-items.tsx
var selected_items = __webpack_require__("../../packages/js/components/src/experimental-select-control/selected-items.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/combo-box.tsx
var combo_box = __webpack_require__("../../packages/js/components/src/experimental-select-control/combo-box.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/suffix-icon.tsx
var suffix_icon = __webpack_require__("../../packages/js/components/src/experimental-select-control/suffix-icon.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js + 257 modules
var popover = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-tree-control/tree.tsx + 7 modules
var tree = __webpack_require__("../../packages/js/components/src/experimental-tree-control/tree.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/experimental-select-tree-control/select-tree-menu.tsx
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


const SelectTreeMenu = ({
  isEventOutside,
  isLoading,
  isOpen,
  className,
  position = 'bottom center',
  scrollIntoViewOnOpen = false,
  items,
  treeRef: ref,
  onClose = () => {},
  onEscape,
  shouldShowCreateButton,
  onFirstItemLoop,
  onExpand,
  ...props
}) => {
  const [boundingRect, setBoundingRect] = (0,react.useState)();
  const selectControlMenuRef = (0,react.useRef)(null);
  (0,react.useLayoutEffect)(() => {
    if (selectControlMenuRef.current?.parentElement && selectControlMenuRef.current?.parentElement.clientWidth > 0) {
      setBoundingRect(selectControlMenuRef.current.parentElement.getBoundingClientRect());
    }
  }, [selectControlMenuRef.current, selectControlMenuRef.current?.clientWidth]);

  // Scroll the selected item into view when the menu opens.
  (0,react.useEffect)(() => {
    if (isOpen && scrollIntoViewOnOpen) {
      selectControlMenuRef.current?.scrollIntoView?.();
    }
  }, [isOpen, scrollIntoViewOnOpen]);
  const shouldItemBeExpanded = item => {
    if (!props.createValue || !item.children?.length) return false;
    return item.children.some(child => {
      if (new RegExp((0,lodash.escapeRegExp)(props.createValue || ''), 'ig').test(child.data.label)) {
        return true;
      }
      return shouldItemBeExpanded(child);
    });
  };

  /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  /* Disabled because of the onmouseup on the ul element below. */
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    ref: selectControlMenuRef,
    className: "woocommerce-experimental-select-tree-control__menu",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(popover/* default */.Ay, {
        focusOnMount: false,
        inline: true,
        className: (0,clsx/* default */.A)('woocommerce-experimental-select-tree-control__popover-menu', className, {
          'is-open': isOpen,
          'has-results': items.length > 0
        }),
        position: position,
        flip: false,
        resize: false,
        animate: false,
        onFocusOutside: event => {
          if (isEventOutside(event)) {
            onClose();
          }
        },
        children: isOpen && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          children: isLoading ? /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
            style: {
              width: boundingRect?.width
            },
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {})
          }) : /*#__PURE__*/(0,jsx_runtime.jsx)(tree/* Tree */.P, {
            ...props,
            ref: ref,
            items: items,
            onTreeBlur: onClose,
            onExpand: onExpand,
            shouldItemBeExpanded: shouldItemBeExpanded,
            shouldShowCreateButton: shouldShowCreateButton,
            onFirstItemLoop: onFirstItemLoop,
            onEscape: onEscape,
            style: {
              width: boundingRect?.width
            }
          })
        })
      })
    })
  });
  /* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
};
try {
    // @ts-ignore
    SelectTreeMenu.displayName = "SelectTreeMenu";
    // @ts-ignore
    SelectTreeMenu.__docgenInfo = { "description": "", "displayName": "SelectTreeMenu", "props": { "isEventOutside": { "defaultValue": null, "description": "", "name": "isEventOutside", "required": true, "type": { "name": "(event: SyntheticEvent<Element, Event>) => boolean" } }, "isOpen": { "defaultValue": null, "description": "", "name": "isOpen", "required": true, "type": { "name": "boolean" } }, "isLoading": { "defaultValue": null, "description": "", "name": "isLoading", "required": false, "type": { "name": "boolean" } }, "position": { "defaultValue": { value: "bottom center" }, "description": "", "name": "position", "required": false, "type": { "name": "enum", "value": [{ "value": "\"top left\"" }, { "value": "\"top right\"" }, { "value": "\"top center\"" }, { "value": "\"middle left\"" }, { "value": "\"middle right\"" }, { "value": "\"middle center\"" }, { "value": "\"bottom left\"" }, { "value": "\"bottom right\"" }, { "value": "\"bottom center\"" }, { "value": "\"top\"" }, { "value": "\"bottom\"" }, { "value": "\"middle\"" }, { "value": "\"top center top\"" }, { "value": "\"top center bottom\"" }, { "value": "\"top center left\"" }, { "value": "\"top center right\"" }, { "value": "\"top left top\"" }, { "value": "\"top left bottom\"" }, { "value": "\"top left left\"" }, { "value": "\"top left right\"" }, { "value": "\"top right top\"" }, { "value": "\"top right bottom\"" }, { "value": "\"top right left\"" }, { "value": "\"top right right\"" }, { "value": "\"bottom center top\"" }, { "value": "\"bottom center bottom\"" }, { "value": "\"bottom center left\"" }, { "value": "\"bottom center right\"" }, { "value": "\"bottom left top\"" }, { "value": "\"bottom left bottom\"" }, { "value": "\"bottom left left\"" }, { "value": "\"bottom left right\"" }, { "value": "\"bottom right top\"" }, { "value": "\"bottom right bottom\"" }, { "value": "\"bottom right left\"" }, { "value": "\"bottom right right\"" }, { "value": "\"middle center top\"" }, { "value": "\"middle center bottom\"" }, { "value": "\"middle center left\"" }, { "value": "\"middle center right\"" }, { "value": "\"middle left top\"" }, { "value": "\"middle left bottom\"" }, { "value": "\"middle left left\"" }, { "value": "\"middle left right\"" }, { "value": "\"middle right top\"" }, { "value": "\"middle right bottom\"" }, { "value": "\"middle right left\"" }, { "value": "\"middle right right\"" }] } }, "scrollIntoViewOnOpen": { "defaultValue": { value: "false" }, "description": "", "name": "scrollIntoViewOnOpen", "required": false, "type": { "name": "boolean" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "LinkedTree[]" } }, "treeRef": { "defaultValue": null, "description": "", "name": "treeRef", "required": false, "type": { "name": "ForwardedRef<HTMLOListElement>" } }, "onClose": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onClose", "required": false, "type": { "name": "(() => void)" } }, "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-tree-control/select-tree-menu.tsx#SelectTreeMenu"] = { docgenInfo: SelectTreeMenu.__docgenInfo, name: "SelectTreeMenu", path: "../../packages/js/components/src/experimental-select-tree-control/select-tree-menu.tsx#SelectTreeMenu" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/utils.tsx
/**
 * External dependencies
 */



/**
 * Returns an object with the children and props that will be used by `cloneElement`. They will change depending on the
 * type of children passed in.
 *
 * @param {Node}   children    - Node children.
 * @param {number} order       - Node order.
 * @param {Array}  props       - Fill props.
 * @param {Object} injectProps - Props to inject.
 * @return {Object} Object with the keys: children and props.
 */
function getChildrenAndProps(children, order, props, injectProps) {
  if (typeof children === 'function') {
    return {
      children: children({
        ...props,
        order,
        ...injectProps
      }),
      props: {
        order,
        ...injectProps
      }
    };
  } else if (isValidElement(children)) {
    // This checks whether 'children' is a react element or a standard HTML element.
    if (typeof children?.type === 'function') {
      return {
        children,
        props: {
          ...props,
          order,
          ...injectProps
        }
      };
    }
    return {
      children: children,
      props: {
        order,
        ...injectProps
      }
    };
  }
  throw Error('Invalid children type');
}

/**
 * Ordered fill item.
 *
 * @param {Node}   children    - Node children.
 * @param {number} order       - Node order.
 * @param {Array}  props       - Fill props.
 * @param {Object} injectProps - Props to inject.
 * @return {Node} Node.
 */
function createOrderedChildren(children, order, props, injectProps) {
  const {
    children: childrenToRender,
    props: propsToRender
  } = getChildrenAndProps(children, order, props, injectProps);
  if (!childrenToRender || typeof childrenToRender === 'string') {
    return childrenToRender;
  }
  return cloneElement(childrenToRender, propsToRender);
}


/**
 * Sort fills by order for slot children.
 *
 * @param {Array} fills - slot's `Fill`s.
 * @return {Node} Node.
 */
const sortFillsByOrder = fills => {
  // Copy fills array here because its type is readonly array that doesn't have .sort method in Typescript definition.
  const sortedFills = Children.toArray(fills).sort((a, b) => {
    if (typeof a === 'object' && 'key' in a && typeof b === 'object' && 'key' in b) {
      return a.props.order - b.props.order;
    }
    return 0;
  });
  return /*#__PURE__*/_jsx(Fragment, {
    children: sortedFills
  });
};
const escapeHTML = string => {
  return string.replace(/&/g, '&amp;').replace(/>/g, '&gt;').replace(/</g, '&lt;');
};
try {
    // @ts-ignore
    createOrderedChildren.displayName = "createOrderedChildren";
    // @ts-ignore
    createOrderedChildren.__docgenInfo = { "description": "Ordered fill item.", "displayName": "createOrderedChildren", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/utils.tsx#createOrderedChildren"] = { docgenInfo: createOrderedChildren.__docgenInfo, name: "createOrderedChildren", path: "../../packages/js/components/src/utils.tsx#createOrderedChildren" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    sortFillsByOrder.displayName = "sortFillsByOrder";
    // @ts-ignore
    sortFillsByOrder.__docgenInfo = { "description": "Sort fills by order for slot children.", "displayName": "sortFillsByOrder", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/utils.tsx#sortFillsByOrder"] = { docgenInfo: sortFillsByOrder.__docgenInfo, name: "sortFillsByOrder", path: "../../packages/js/components/src/utils.tsx#sortFillsByOrder" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    escapeHTML.displayName = "escapeHTML";
    // @ts-ignore
    escapeHTML.__docgenInfo = { "description": "", "displayName": "escapeHTML", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/utils.tsx#escapeHTML"] = { docgenInfo: escapeHTML.__docgenInfo, name: "escapeHTML", path: "../../packages/js/components/src/utils.tsx#escapeHTML" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/experimental-select-tree-control/select-tree.tsx
/**
 * External dependencies
 */









/**
 * Internal dependencies
 */







function isBlurEvent(event) {
  return event.type === 'blur';
}
const select_tree_SelectTree = function SelectTree({
  items,
  treeRef: ref,
  isLoading,
  disabled,
  initialInputValue,
  onInputChange,
  shouldShowCreateButton,
  help,
  isClearingAllowed = false,
  onClear = () => {},
  ...props
}) {
  const [linkedTree, setLinkedTree] = (0,react.useState)([]);
  const [highlightedIndex, setHighlightedIndex] = (0,react.useState)(-1);

  // whenever the items change, the linked tree needs to be recalculated
  (0,react.useEffect)(() => {
    setLinkedTree((0,linked_tree_utils/* createLinkedTree */.YD)(items, props.createValue));
  }, [items.length]);

  // reset highlighted index when the input value changes
  (0,react.useEffect)(() => setHighlightedIndex(-1), [props.createValue]);
  const selectTreeInstanceId = (0,use_instance_id/* default */.A)(SelectTree, 'woocommerce-experimental-select-tree-control__dropdown');
  const menuInstanceId = (0,use_instance_id/* default */.A)(SelectTree, 'woocommerce-select-tree-control__menu');
  const selectedItemsFocusHandle = (0,react.useRef)(null);
  function isEventOutside(event) {
    let target = event.currentTarget;
    if (isBlurEvent(event)) {
      target = event.relatedTarget;
    }
    const isInsideSelect = document.getElementById(selectTreeInstanceId)?.contains(target);
    const isInsidePopover = document.getElementById(menuInstanceId)?.closest('.woocommerce-experimental-select-tree-control__popover-menu')?.contains(target);
    const isInRemoveTag = target?.classList.contains('woocommerce-tag__remove');
    return !isInsideSelect && !isInRemoveTag && !isInsidePopover;
  }
  const recalculateInputValue = () => {
    if (onInputChange) {
      if (!props.multiple && props.selected) {
        onInputChange(props.selected.label);
      } else {
        onInputChange('');
      }
    }
  };
  const focusOnInput = () => {
    document.querySelector(`#${props.id}-input`)?.focus();
  };
  const [isFocused, setIsFocused] = (0,react.useState)(false);
  const [isOpen, setIsOpen] = (0,react.useState)(false);
  const [inputValue, setInputValue] = (0,react.useState)('');
  const isReadOnly = !isOpen && !isFocused;
  (0,react.useEffect)(() => {
    if (initialInputValue !== undefined && isFocused) {
      setInputValue(initialInputValue);
    }
  }, [isFocused]);

  // Scroll the newly highlighted item into view
  (0,react.useEffect)(() => document.querySelector('.experimental-woocommerce-tree-item--highlighted')?.scrollIntoView?.({
    block: 'nearest'
  }), [highlightedIndex]);
  let placeholder = '';
  if (Array.isArray(props.selected)) {
    placeholder = props.selected.length === 0 ? props.placeholder : '';
  } else if (props.selected) {
    placeholder = props.placeholder;
  }

  // reset highlighted index when the input value changes
  (0,react.useEffect)(() => {
    if (highlightedIndex === items.length && !shouldShowCreateButton?.(props.createValue)) {
      setHighlightedIndex(items.length - 1);
    }
  }, [props.createValue]);
  const inputProps = {
    className: 'woocommerce-experimental-select-control__input',
    id: `${props.id}-input`,
    'aria-autocomplete': 'list',
    'aria-activedescendant': highlightedIndex >= 0 ? `woocommerce-experimental-tree-control__menu-item-${highlightedIndex}` : undefined,
    'aria-controls': menuInstanceId,
    'aria-owns': menuInstanceId,
    role: 'combobox',
    autoComplete: 'off',
    'aria-expanded': isOpen,
    'aria-haspopup': 'tree',
    disabled,
    onFocus: event => {
      if (props.multiple) {
        (0,a11y_build_module/* speak */.L)((0,i18n_build_module.__)('To select existing items, type its exact label and separate with commas or the Enter key.', 'woocommerce'));
      }
      if (!isOpen) {
        setIsOpen(true);
      }
      setIsFocused(true);
      if (Array.isArray(props.selected) && props.selected?.some(item => item.label === event.target.value)) {
        setInputValue('');
      }
    },
    onBlur: event => {
      event.preventDefault();
      if (isEventOutside(event)) {
        setIsOpen(false);
        setIsFocused(false);
        recalculateInputValue();
      }
    },
    onKeyDown: event => {
      setIsOpen(true);
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (
        // is advancing from the last menu item to the create button
        highlightedIndex === items.length - 1 && shouldShowCreateButton?.(props.createValue)) {
          setHighlightedIndex(items.length);
        } else {
          const visibleNodeIndex = (0,linked_tree_utils/* getVisibleNodeIndex */.p_)(linkedTree, Math.min(highlightedIndex + 1, items.length), 'down');
          if (visibleNodeIndex !== undefined) {
            setHighlightedIndex(visibleNodeIndex);
          }
        }
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (highlightedIndex > 0) {
          const visibleNodeIndex = (0,linked_tree_utils/* getVisibleNodeIndex */.p_)(linkedTree, Math.max(highlightedIndex - 1, -1), 'up');
          if (visibleNodeIndex !== undefined) {
            setHighlightedIndex(visibleNodeIndex);
          }
        } else {
          setHighlightedIndex(-1);
        }
      } else if (event.key === 'Tab' || event.key === 'Escape') {
        setIsOpen(false);
        recalculateInputValue();
      } else if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        if (highlightedIndex === items.length && shouldShowCreateButton) {
          props.onCreateNew?.();
        } else if (
        // is selecting an item
        highlightedIndex !== -1) {
          const nodeData = (0,linked_tree_utils/* getNodeDataByIndex */.g_)(linkedTree, highlightedIndex);
          if (!nodeData) {
            return;
          }
          if (props.multiple && Array.isArray(props.selected)) {
            if (!Boolean(props.selected.find(i => i.label === nodeData.label))) {
              if (props.onSelect) {
                props.onSelect(nodeData);
              }
            } else if (props.onRemove) {
              props.onRemove(nodeData);
            }
            setInputValue('');
          } else {
            onInputChange?.(nodeData.label);
            props.onSelect?.(nodeData);
            setIsOpen(false);
            setIsFocused(false);
            focusOnInput();
          }
        } else if (inputValue) {
          // no highlighted item, but there is an input value, check if it matches any item

          const item = items.find(i => i.label === escapeHTML(inputValue));
          const isAlreadySelected = Array.isArray(props.selected) ? Boolean(props.selected.find(i => i.label === escapeHTML(inputValue))) : props.selected?.label === escapeHTML(inputValue);
          if (item && !isAlreadySelected) {
            props.onSelect?.(item);
            setInputValue('');
            recalculateInputValue();
          }
        }
      } else if (event.key === 'Backspace' &&
      // test if the cursor is at the beginning of the input with nothing selected
      event.target.selectionStart === 0 && event.target.selectionEnd === 0 && selectedItemsFocusHandle.current) {
        selectedItemsFocusHandle.current();
      } else if (event.key === 'ArrowRight') {
        setLinkedTree((0,linked_tree_utils/* toggleNode */.TN)(linkedTree, highlightedIndex, true));
      } else if (event.key === 'ArrowLeft') {
        setLinkedTree((0,linked_tree_utils/* toggleNode */.TN)(linkedTree, highlightedIndex, false));
      } else if (event.key === 'Home') {
        event.preventDefault();
        setHighlightedIndex(0);
      } else if (event.key === 'End') {
        event.preventDefault();
        setHighlightedIndex(items.length - 1);
      }
    },
    onChange: event => {
      if (onInputChange) {
        onInputChange(event.target.value);
      }
      setInputValue(event.target.value);
    },
    placeholder,
    value: inputValue
  };
  const handleClear = () => {
    if (isClearingAllowed) {
      onClear();
    }
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    id: selectTreeInstanceId,
    className: `woocommerce-experimental-select-tree-control__dropdown`,
    tabIndex: -1,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: (0,clsx/* default */.A)('woocommerce-experimental-select-control', {
        'is-read-only': isReadOnly,
        'is-focused': isFocused,
        'is-multiple': props.multiple,
        'has-selected-items': Array.isArray(props.selected) && props.selected.length
      }),
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(base_control/* default */.Ay, {
        label: props.label,
        id: `${props.id}-input`,
        help: props.multiple && !help ? (0,i18n_build_module.__)('Separate with commas or the Enter key.', 'woocommerce') : help,
        children: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
          children: [props.multiple ? /*#__PURE__*/(0,jsx_runtime.jsx)(combo_box/* ComboBox */.a, {
            comboBoxProps: {
              className: 'woocommerce-experimental-select-control__combo-box-wrapper'
            },
            inputProps: inputProps,
            suffix: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
              className: "woocommerce-experimental-select-control__suffix-items",
              children: [isClearingAllowed && isOpen && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                label: (0,i18n_build_module.__)('Remove all', 'woocommerce'),
                onClick: handleClear,
                children: /*#__PURE__*/(0,jsx_runtime.jsx)(suffix_icon/* SuffixIcon */.f, {
                  className: "woocommerce-experimental-select-control__icon-clear",
                  icon: close_small/* default */.A
                })
              }), /*#__PURE__*/(0,jsx_runtime.jsx)(suffix_icon/* SuffixIcon */.f, {
                icon: isOpen ? chevron_up/* default */.A : chevron_down/* default */.A
              })]
            }),
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(selected_items/* SelectedItems */.K, {
              isReadOnly: isReadOnly,
              ref: selectedItemsFocusHandle,
              items: !Array.isArray(props.selected) ? [props.selected] : props.selected,
              getItemLabel: item => item?.label || '',
              getItemValue: item => item?.value || '',
              onRemove: item => {
                if (item && !Array.isArray(item) && props.onRemove) {
                  props.onRemove(item);
                }
              },
              onBlur: event => {
                if (isEventOutside(event)) {
                  setIsOpen(false);
                  setIsFocused(false);
                }
              },
              onSelectedItemsEnd: focusOnInput,
              getSelectedItemProps: () => ({})
            })
          }) : /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
            ...inputProps,
            value: (0,build_module/* decodeEntities */.S)(props.createValue || ''),
            onChange: value => {
              if (onInputChange) onInputChange(value);
              const item = items.find(i => i.label === escapeHTML(value));
              if (props.onSelect && item) {
                props.onSelect(item);
              }
              if (!value && props.onRemove) {
                props.onRemove(props.selected);
              }
            }
          }), /*#__PURE__*/(0,jsx_runtime.jsx)(SelectTreeMenu, {
            ...props,
            onSelect: item => {
              if (!props.multiple && onInputChange) {
                onInputChange(item.label);
                setIsOpen(false);
                setIsFocused(false);
                focusOnInput();
              }
              if (props.onSelect) {
                props.onSelect(item);
              }
            },
            id: menuInstanceId,
            ref: ref,
            isEventOutside: isEventOutside,
            isLoading: isLoading,
            isOpen: isOpen,
            highlightedIndex: highlightedIndex,
            onExpand: (index, value) => {
              setLinkedTree((0,linked_tree_utils/* toggleNode */.TN)(linkedTree, index, value));
            },
            items: linkedTree,
            shouldShowCreateButton: shouldShowCreateButton,
            onEscape: () => {
              focusOnInput();
              setIsOpen(false);
            },
            onClose: () => {
              setIsOpen(false);
            },
            onFirstItemLoop: focusOnInput
          })]
        })
      })
    })
  });
};
try {
    // @ts-ignore
    select_tree_SelectTree.displayName = "SelectTree";
    // @ts-ignore
    select_tree_SelectTree.__docgenInfo = { "description": "", "displayName": "SelectTree", "props": { "id": { "defaultValue": null, "description": "", "name": "id", "required": true, "type": { "name": "string" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "treeRef": { "defaultValue": null, "description": "", "name": "treeRef", "required": false, "type": { "name": "ForwardedRef<HTMLOListElement>" } }, "isLoading": { "defaultValue": null, "description": "", "name": "isLoading", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string | Element" } }, "help": { "defaultValue": null, "description": "", "name": "help", "required": false, "type": { "name": "string | Element" } }, "onInputChange": { "defaultValue": null, "description": "", "name": "onInputChange", "required": false, "type": { "name": "((value: string) => void)" } }, "initialInputValue": { "defaultValue": null, "description": "", "name": "initialInputValue", "required": false, "type": { "name": "string" } }, "isClearingAllowed": { "defaultValue": { value: "false" }, "description": "", "name": "isClearingAllowed", "required": false, "type": { "name": "boolean" } }, "onClear": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onClear", "required": false, "type": { "name": "(() => void)" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "Item[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-tree-control/select-tree.tsx#SelectTree"] = { docgenInfo: select_tree_SelectTree.__docgenInfo, name: "SelectTree", path: "../../packages/js/components/src/experimental-select-tree-control/select-tree.tsx#SelectTree" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/experimental-select-tree-control/stories/select-tree-control.story.tsx
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
const filterItems = (items, searchValue) => {
  const filteredItems = items.filter(e => e.label.includes(searchValue));
  const itemsToIterate = [...filteredItems];
  while (itemsToIterate.length > 0) {
    // The filter should include the parents of the filtered items
    const element = itemsToIterate.pop();
    if (element) {
      const parent = listItems.find(item => item.value === element.parent);
      if (parent && !filteredItems.includes(parent)) {
        filteredItems.push(parent);
        itemsToIterate.push(parent);
      }
    }
  }
  return filteredItems;
};
const MultipleSelectTree = () => {
  const [value, setValue] = React.useState('');
  const [selected, setSelected] = React.useState([]);
  const items = filterItems(listItems, value);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_tree_SelectTree, {
    id: "multiple-select-tree",
    label: "Multiple Select Tree",
    multiple: true,
    items: items,
    selected: selected,
    shouldNotRecursivelySelect: true,
    shouldShowCreateButton: typedValue => !value || listItems.findIndex(item => item.label === typedValue) === -1,
    createValue: value
    // eslint-disable-next-line no-alert
    ,

    onCreateNew: () => alert('create new called'),
    onInputChange: a => setValue(a || ''),
    onSelect: selectedItems => {
      if (Array.isArray(selectedItems)) {
        setSelected([...selected, ...selectedItems]);
      }
    },
    onRemove: removedItems => {
      const newValues = Array.isArray(removedItems) ? selected.filter(item => !removedItems.some(({
        value: removedValue
      }) => item.value === removedValue)) : selected.filter(item => item.value !== removedItems.value);
      setSelected(newValues);
    }
  });
};
const SingleWithinModalUsingBodyDropdownPlacement = () => {
  const [isOpen, setOpen] = (0,react.useState)(true);
  const [value, setValue] = (0,react.useState)('');
  const [selected, setSelected] = (0,react.useState)([]);
  const items = filterItems(listItems, value);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(slot_fill/* Provider */.Kq, {
    children: ["Selected: ", JSON.stringify(selected), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      onClick: () => setOpen(true),
      children: "Show Dropdown in Modal"
    }), isOpen && /*#__PURE__*/(0,jsx_runtime.jsx)(modal/* default */.A, {
      title: "Dropdown Modal",
      onRequestClose: () => setOpen(false),
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_tree_SelectTree, {
        id: "multiple-select-tree",
        label: "Multiple Select Tree",
        multiple: true,
        items: items,
        selected: selected,
        shouldNotRecursivelySelect: true,
        shouldShowCreateButton: typedValue => !value || listItems.findIndex(item => item.label === typedValue) === -1,
        createValue: value
        // eslint-disable-next-line no-alert
        ,

        onCreateNew: () => alert('create new called'),
        onInputChange: a => setValue(a || ''),
        onSelect: selectedItems => {
          if (Array.isArray(selectedItems)) {
            setSelected([...selected, ...selectedItems]);
          }
        },
        onRemove: removedItems => {
          const newValues = Array.isArray(removedItems) ? selected.filter(item => !removedItems.some(({
            value: removedValue
          }) => item.value === removedValue)) : selected.filter(item => item.value !== removedItems.value);
          setSelected(newValues);
        }
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(select_tree_menu_namespaceObject.SelectTreeMenuSlot, {})]
  });
};
const SingleSelectTree = () => {
  const [value, setValue] = React.useState('');
  const [selected, setSelected] = React.useState();
  const items = filterItems(listItems, value);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_tree_SelectTree, {
    id: "single-select-tree",
    label: "Single Select Tree",
    items: items,
    selected: selected,
    shouldNotRecursivelySelect: true,
    shouldShowCreateButton: typedValue => !value || listItems.findIndex(item => item.label === typedValue) === -1,
    createValue: value
    // eslint-disable-next-line no-alert
    ,

    onCreateNew: () => alert('create new called'),
    onInputChange: a => setValue(a || ''),
    onSelect: selectedItems => {
      setSelected(selectedItems);
    },
    onRemove: () => setSelected(undefined)
  });
};
/* harmony default export */ const select_tree_control_story = ({
  title: 'Experimental/SelectTreeControl',
  component: select_tree_SelectTree
});
MultipleSelectTree.parameters = {
  ...MultipleSelectTree.parameters,
  docs: {
    ...MultipleSelectTree.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [value, setValue] = React.useState('');\n  const [selected, setSelected] = React.useState<Item[]>([]);\n  const items = filterItems(listItems, value);\n  return <SelectTree id=\"multiple-select-tree\" label=\"Multiple Select Tree\" multiple items={items} selected={selected} shouldNotRecursivelySelect shouldShowCreateButton={typedValue => !value || listItems.findIndex(item => item.label === typedValue) === -1} createValue={value}\n  // eslint-disable-next-line no-alert\n  onCreateNew={() => alert('create new called')} onInputChange={a => setValue(a || '')} onSelect={selectedItems => {\n    if (Array.isArray(selectedItems)) {\n      setSelected([...selected, ...selectedItems]);\n    }\n  }} onRemove={removedItems => {\n    const newValues = Array.isArray(removedItems) ? selected.filter(item => !removedItems.some(({\n      value: removedValue\n    }) => item.value === removedValue)) : selected.filter(item => item.value !== removedItems.value);\n    setSelected(newValues);\n  }} />;\n}",
      ...MultipleSelectTree.parameters?.docs?.source
    }
  }
};
SingleWithinModalUsingBodyDropdownPlacement.parameters = {
  ...SingleWithinModalUsingBodyDropdownPlacement.parameters,
  docs: {
    ...SingleWithinModalUsingBodyDropdownPlacement.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [isOpen, setOpen] = useState(true);\n  const [value, setValue] = useState('');\n  const [selected, setSelected] = useState<Item[]>([]);\n  const items = filterItems(listItems, value);\n  return <SlotFillProvider>\n            Selected: {JSON.stringify(selected)}\n            <Button onClick={() => setOpen(true)}>\n                Show Dropdown in Modal\n            </Button>\n            {isOpen && <Modal title=\"Dropdown Modal\" onRequestClose={() => setOpen(false)}>\n                    <SelectTree id=\"multiple-select-tree\" label=\"Multiple Select Tree\" multiple items={items} selected={selected} shouldNotRecursivelySelect shouldShowCreateButton={typedValue => !value || listItems.findIndex(item => item.label === typedValue) === -1} createValue={value}\n      // eslint-disable-next-line no-alert\n      onCreateNew={() => alert('create new called')} onInputChange={a => setValue(a || '')} onSelect={selectedItems => {\n        if (Array.isArray(selectedItems)) {\n          setSelected([...selected, ...selectedItems]);\n        }\n      }} onRemove={removedItems => {\n        const newValues = Array.isArray(removedItems) ? selected.filter(item => !removedItems.some(({\n          value: removedValue\n        }) => item.value === removedValue)) : selected.filter(item => item.value !== removedItems.value);\n        setSelected(newValues);\n      }} />\n                </Modal>}\n            <SelectTreeMenuSlot />\n        </SlotFillProvider>;\n}",
      ...SingleWithinModalUsingBodyDropdownPlacement.parameters?.docs?.source
    }
  }
};
SingleSelectTree.parameters = {
  ...SingleSelectTree.parameters,
  docs: {
    ...SingleSelectTree.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [value, setValue] = React.useState('');\n  const [selected, setSelected] = React.useState<Item | undefined>();\n  const items = filterItems(listItems, value);\n  return <SelectTree id=\"single-select-tree\" label=\"Single Select Tree\" items={items} selected={selected} shouldNotRecursivelySelect shouldShowCreateButton={typedValue => !value || listItems.findIndex(item => item.label === typedValue) === -1} createValue={value}\n  // eslint-disable-next-line no-alert\n  onCreateNew={() => alert('create new called')} onInputChange={a => setValue(a || '')} onSelect={selectedItems => {\n    setSelected(selectedItems as Item);\n  }} onRemove={() => setSelected(undefined)} />;\n}",
      ...SingleSelectTree.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    SelectTree.displayName = "SelectTree";
    // @ts-ignore
    SelectTree.__docgenInfo = { "description": "", "displayName": "SelectTree", "props": { "id": { "defaultValue": null, "description": "", "name": "id", "required": true, "type": { "name": "string" } }, "selected": { "defaultValue": null, "description": "It contains one item if `multiple` value is false or\na list of items if it is true.", "name": "selected", "required": false, "type": { "name": "Item | Item[]" } }, "treeRef": { "defaultValue": null, "description": "", "name": "treeRef", "required": false, "type": { "name": "ForwardedRef<HTMLOListElement>" } }, "isLoading": { "defaultValue": null, "description": "", "name": "isLoading", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string | Element" } }, "help": { "defaultValue": null, "description": "", "name": "help", "required": false, "type": { "name": "string | Element" } }, "onInputChange": { "defaultValue": null, "description": "", "name": "onInputChange", "required": false, "type": { "name": "((value: string) => void)" } }, "initialInputValue": { "defaultValue": null, "description": "", "name": "initialInputValue", "required": false, "type": { "name": "string" } }, "isClearingAllowed": { "defaultValue": { value: "false" }, "description": "", "name": "isClearingAllowed", "required": false, "type": { "name": "boolean" } }, "onClear": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onClear", "required": false, "type": { "name": "(() => void)" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "onSelect": { "defaultValue": null, "description": "When `multiple` is true and a child item is selected, all its\nancestors and its descendants are also selected. If it's false\nonly the clicked item is selected.\n@param value The selection", "name": "onSelect", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "((index: number, value: boolean) => void)" } }, "multiple": { "defaultValue": null, "description": "Whether the tree items are single or multiple selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onRemove": { "defaultValue": null, "description": "When `multiple` is true and a child item is unselected, all its\nancestors (if no sibblings are selected) and its descendants\nare also unselected. If it's false only the clicked item is\nunselected.\n@param value The unselection", "name": "onRemove", "required": false, "type": { "name": "((value: Item | Item[]) => void)" } }, "highlightedIndex": { "defaultValue": null, "description": "", "name": "highlightedIndex", "required": false, "type": { "name": "number" } }, "shouldNotRecursivelySelect": { "defaultValue": null, "description": "In `multiple` mode, when this flag is also set, selecting children does\nnot select their parents and selecting parents does not select their children.", "name": "shouldNotRecursivelySelect", "required": false, "type": { "name": "boolean" } }, "createValue": { "defaultValue": null, "description": "The value to be used for comparison to determine if 'create new' button should be shown.", "name": "createValue", "required": false, "type": { "name": "string" } }, "onCreateNew": { "defaultValue": null, "description": "Called when the 'create new' button is clicked.", "name": "onCreateNew", "required": false, "type": { "name": "(() => void)" } }, "shouldShowCreateButton": { "defaultValue": null, "description": "If passed, shows create button if return from callback is true", "name": "shouldShowCreateButton", "required": false, "type": { "name": "((value?: string) => boolean)" } }, "isExpanded": { "defaultValue": null, "description": "", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "shouldItemBeHighlighted": { "defaultValue": null, "description": "It provides a way to determine whether the current rendering\nitem is highlighted or not from outside the tree.\n@example <Tree\n\tshouldItemBeHighlighted={ isFirstChild }\n/>\n@param item The current linked tree item, useful to\ntraverse the entire linked tree from this item.\n@see {@link LinkedTree }", "name": "shouldItemBeHighlighted", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "onTreeBlur": { "defaultValue": null, "description": "Called when the create button is clicked to help closing any related popover.", "name": "onTreeBlur", "required": false, "type": { "name": "(() => void)" } }, "onFirstItemLoop": { "defaultValue": null, "description": "", "name": "onFirstItemLoop", "required": false, "type": { "name": "((event: KeyboardEvent<HTMLDivElement>) => void)" } }, "onEscape": { "defaultValue": null, "description": "Called when the escape key is pressed.", "name": "onEscape", "required": false, "type": { "name": "(() => void)" } }, "getItemLabel": { "defaultValue": null, "description": "It gives a way to render a different Element as the\ntree item label.\n@example <Tree\n\tgetItemLabel={ ( item ) => <span>${ item.data.label }</span> }\n/>\n@param item The current rendering tree item\n@see {@link LinkedTree }", "name": "getItemLabel", "required": false, "type": { "name": "((item: LinkedTree) => Element)" } }, "shouldItemBeExpanded": { "defaultValue": null, "description": "Return if the tree item passed in should be expanded.\n@example <Tree\n\tshouldItemBeExpanded={\n\t\t( item ) => checkExpanded( item, filter )\n\t}\n/>\n@param item The tree item to determine if should be expanded.\n@see {@link LinkedTree }", "name": "shouldItemBeExpanded", "required": false, "type": { "name": "((item: LinkedTree) => boolean)" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "Item[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-tree-control/stories/select-tree-control.story.tsx#SelectTree"] = { docgenInfo: SelectTree.__docgenInfo, name: "SelectTree", path: "../../packages/js/components/src/experimental-select-tree-control/stories/select-tree-control.story.tsx#SelectTree" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/combo-box.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ ComboBox)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const ToggleButton = (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, ref) => {
  // using forwardRef here because getToggleButtonProps injects a ref prop
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
    className: "woocommerce-experimental-select-control__combox-box-toggle-button",
    ...props,
    ref: ref,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A
    })
  });
});
const ComboBox = ({
  children,
  comboBoxProps,
  getToggleButtonProps = () => ({}),
  inputProps,
  suffix,
  showToggleButton
}) => {
  const inputRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const maybeFocusInput = event => {
    if (!inputRef || !inputRef.current) {
      return;
    }
    if (document.activeElement !== inputRef.current) {
      event.preventDefault();
      inputRef.current.focus();
      event.stopPropagation();
    }
  };
  return (
    /*#__PURE__*/
    // Disable reason: The click event is purely for accidental clicks around the input.
    // Keyboard users are still able to tab to and interact with elements in the combobox.
    /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)('woocommerce-experimental-select-control__combo-box-wrapper', {
        'woocommerce-experimental-select-control__combo-box-wrapper--disabled': inputProps.disabled
      }),
      onMouseDown: maybeFocusInput,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
        className: "woocommerce-experimental-select-control__items-wrapper",
        children: [children, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
          ...comboBoxProps,
          className: "woocommerce-experimental-select-control__combox-box",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
            ...inputProps,
            ref: node => {
              inputRef.current = node;
              if (typeof inputProps.ref === 'function') {
                inputProps.ref(node);
              }
            }
          })
        })]
      }), suffix && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: "woocommerce-experimental-select-control__suffix",
        children: suffix
      }), showToggleButton && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(ToggleButton, {
        ...getToggleButtonProps()
      })]
    })
  );
};
try {
    // @ts-ignore
    ComboBox.displayName = "ComboBox";
    // @ts-ignore
    ComboBox.__docgenInfo = { "description": "", "displayName": "ComboBox", "props": { "comboBoxProps": { "defaultValue": null, "description": "", "name": "comboBoxProps", "required": true, "type": { "name": "DetailedHTMLProps<HTMLAttributes<HTMLDivElement>, HTMLDivElement>" } }, "inputProps": { "defaultValue": null, "description": "", "name": "inputProps", "required": true, "type": { "name": "DetailedHTMLProps<InputHTMLAttributes<HTMLInputElement>, HTMLInputElement>" } }, "getToggleButtonProps": { "defaultValue": { value: "() => ( {} )" }, "description": "", "name": "getToggleButtonProps", "required": false, "type": { "name": "(() => Omit<DetailedHTMLProps<ButtonHTMLAttributes<HTMLButtonElement>, HTMLButtonElement>, \"ref\">)" } }, "suffix": { "defaultValue": null, "description": "", "name": "suffix", "required": false, "type": { "name": "Element | null" } }, "showToggleButton": { "defaultValue": null, "description": "", "name": "showToggleButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/combo-box.tsx#ComboBox"] = { docgenInfo: ComboBox.__docgenInfo, name: "ComboBox", path: "../../packages/js/components/src/experimental-select-control/combo-box.tsx#ComboBox" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/selected-items.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   K: () => (/* binding */ SelectedItems)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _tag__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


const PrivateSelectedItems = ({
  isReadOnly,
  items,
  getItemLabel,
  getItemValue,
  getSelectedItemProps,
  onRemove,
  onBlur,
  onSelectedItemsEnd
}, ref) => {
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__selected-items', {
    'is-read-only': isReadOnly
  });
  const lastRemoveButtonRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useRef)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useImperativeHandle)(ref, () => {
    return () => lastRemoveButtonRef.current?.focus();
  }, []);
  if (isReadOnly) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: classes,
      children: items.map(item => {
        return (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__/* .decodeEntities */ .S)(getItemLabel(item));
      }).join(', ')
    });
  }
  const focusSibling = event => {
    const selectedItem = event.target.closest('.woocommerce-experimental-select-control__selected-item');
    const sibling = event.key === 'ArrowLeft' || event.key === 'Backspace' ? selectedItem?.previousSibling : selectedItem?.nextSibling;
    if (sibling) {
      sibling.querySelector('.woocommerce-tag__remove')?.focus();
      return true;
    }
    return false;
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: classes,
    children: items.map((item, index) => {
      return (
        /*#__PURE__*/
        // Disable reason: We prevent the default action to keep the input focused on click.
        // Keyboard users are unaffected by this change.
        /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
        (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
          className: "woocommerce-experimental-select-control__selected-item",
          ...getSelectedItemProps({
            selectedItem: item,
            index
          }),
          onMouseDown: event => {
            event.preventDefault();
          },
          onClick: event => {
            event.preventDefault();
          },
          onKeyDown: event => {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
              const focused = focusSibling(event);
              if (!focused && event.key === 'ArrowRight' && onSelectedItemsEnd) {
                onSelectedItemsEnd();
              }
            } else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
              event.preventDefault(); // prevent unwanted scroll
            } else if (event.key === 'Backspace') {
              onRemove(item);
              focusSibling(event);
            }
          },
          onBlur: onBlur,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_tag__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
            id: getItemValue(item),
            remove: () => () => onRemove(item),
            label: getItemLabel(item),
            ref: index === items.length - 1 ? lastRemoveButtonRef : undefined
          })
        }, `selected-item-${index}`)
      );
    })
  });
};
const SelectedItems = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(PrivateSelectedItems);
try {
    // @ts-ignore
    SelectedItems.displayName = "SelectedItems";
    // @ts-ignore
    SelectedItems.__docgenInfo = { "description": "", "displayName": "SelectedItems", "props": { "isReadOnly": { "defaultValue": null, "description": "", "name": "isReadOnly", "required": true, "type": { "name": "boolean" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "ItemType[]" } }, "getItemLabel": { "defaultValue": null, "description": "", "name": "getItemLabel", "required": true, "type": { "name": "getItemLabelType<ItemType>" } }, "getItemValue": { "defaultValue": null, "description": "", "name": "getItemValue", "required": true, "type": { "name": "getItemValueType<ItemType>" } }, "getSelectedItemProps": { "defaultValue": null, "description": "", "name": "getSelectedItemProps", "required": true, "type": { "name": "({ selectedItem: any, index: any }: { selectedItem: any; index: any; }) => { [key: string]: string; }" } }, "onRemove": { "defaultValue": null, "description": "", "name": "onRemove", "required": true, "type": { "name": "(item: ItemType) => void" } }, "onBlur": { "defaultValue": null, "description": "", "name": "onBlur", "required": false, "type": { "name": "((event: FocusEvent<Element, Element>) => void)" } }, "onSelectedItemsEnd": { "defaultValue": null, "description": "", "name": "onSelectedItemsEnd", "required": false, "type": { "name": "(() => void)" } }, "ref": { "defaultValue": null, "description": "", "name": "ref", "required": false, "type": { "name": "ForwardedRef<SelectedItemFocusHandle>" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/selected-items.tsx#SelectedItems"] = { docgenInfo: SelectedItems.__docgenInfo, name: "SelectedItems", path: "../../packages/js/components/src/experimental-select-control/selected-items.tsx#SelectedItems" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/suffix-icon.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ SuffixIcon)
/* harmony export */ });
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const SuffixIcon = ({
  className = '',
  icon
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__suffix-icon', className),
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      icon: icon,
      size: 24
    })
  });
};
try {
    // @ts-ignore
    SuffixIcon.displayName = "SuffixIcon";
    // @ts-ignore
    SuffixIcon.__docgenInfo = { "description": "", "displayName": "SuffixIcon", "props": { "icon": { "defaultValue": null, "description": "", "name": "icon", "required": true, "type": { "name": "Element" } }, "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/suffix-icon.tsx#SuffixIcon"] = { docgenInfo: SuffixIcon.__docgenInfo, name: "SuffixIcon", path: "../../packages/js/components/src/experimental-select-control/suffix-icon.tsx#SuffixIcon" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/tag/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */








const Tag = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(({
  id,
  label,
  popoverContents,
  remove,
  screenReaderLabel,
  className
}, removeButtonRef) => {
  const [isVisible, setIsVisible] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Tag).toString();
  const labelId = `woocommerce-tag__label-${instanceId}`;
  screenReaderLabel = screenReaderLabel || label;
  if (!label) {
    // A null label probably means something went wrong
    // @todo Maybe this should be a loading indicator?
    return null;
  }
  label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__/* .decodeEntities */ .S)(label);
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)('woocommerce-tag', className, {
    'has-remove': !!remove
  });
  const labelTextNode = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "screen-reader-text",
      children: screenReaderLabel
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      "aria-hidden": "true",
      children: label
    })]
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("span", {
    className: classes,
    children: [popoverContents ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__text",
      id: labelId,
      onClick: () => setIsVisible(true),
      children: labelTextNode
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "woocommerce-tag__text",
      id: labelId,
      children: labelTextNode
    }), popoverContents && isVisible && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .Ay, {
      onClose: () => setIsVisible(false),
      children: popoverContents
    }), remove && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__remove",
      ref: removeButtonRef,
      onClick: remove(id),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(
      // translators: %s is the name of the tag being removed.
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Remove %s', 'woocommerce'), label),
      "aria-describedby": labelId,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A,
        size: 20,
        className: "clear-icon"
      })
    })]
  });
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

/***/ })

}]);