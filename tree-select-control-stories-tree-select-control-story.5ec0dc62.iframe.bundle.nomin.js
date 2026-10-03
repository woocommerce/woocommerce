"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5826],{

/***/ "../../packages/js/components/src/tree-select-control/stories/tree-select-control.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Base: () => (/* binding */ Base),
  "default": () => (/* binding */ tree_select_control_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js + 2 modules
var dom_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-focus-outside/index.js
var use_focus_outside = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-focus-outside/index.js");
;// ../../packages/js/components/src/tree-select-control/useIsEqualRefValue.js
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
const useIsEqualRefValue = value => {
  const optionsRef = (0,react.useRef)(value);
  if (!(0,lodash.isEqual)(optionsRef.current, value)) {
    optionsRef.current = value;
  }
  return optionsRef.current;
};
/* harmony default export */ const tree_select_control_useIsEqualRefValue = (useIsEqualRefValue);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/tree-select-control/tags.js
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

const Tags = ({
  tags = [],
  disabled,
  maxVisibleTags = 0,
  onChange = () => {}
}) => {
  const [showAll, setShowAll] = (0,react.useState)(false);
  const maxTags = Math.max(0, maxVisibleTags);
  const shouldShowAll = showAll || !maxTags;
  const visibleTags = shouldShowAll ? tags : tags.slice(0, maxTags);
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
  const remove = key => {
    return () => {
      if (disabled) {
        return;
      }
      onChange(tags.filter(tag => tag.id !== key));
    };
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-tree-select-control__tags",
    children: [visibleTags.map((item, i) => {
      if (!item.label) {
        return null;
      }
      const screenReaderLabel = (0,build_module/* sprintf */.nv)(
      // translators: 1: Tag Label, 2: Current Tag index, 3: Total amount of tags.
      (0,build_module.__)('%1$s (%2$d of %3$d)', 'woocommerce'), item.label, i + 1, tags.length);
      return /*#__PURE__*/(0,jsx_runtime.jsx)(tag/* default */.A, {
        id: item.id,
        label: item.label,
        screenReaderLabel: screenReaderLabel,
        remove: remove
      }, item.id);
    }), maxTags > 0 && tags.length > maxTags && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      isTertiary: true,
      className: "woocommerce-tree-select-control__show-more",
      onClick: () => {
        setShowAll(!showAll);
      },
      children: showAll ? (0,build_module.__)('Show less', 'woocommerce') : (0,build_module/* sprintf */.nv)(
      // translators: %d: The number of extra tags to show
      (0,build_module.__)('+ %d more', 'woocommerce'), tags.length - maxTags)
    })]
  });
};
/* harmony default export */ const tree_select_control_tags = (Tags);
;
Tags.__docgenInfo = {
  "description": "A list of tags to display selected items.\n\n@param {Object}   props                    The component props\n@param {Object[]} [props.tags=[]]          The tags\n@param {Function} props.onChange           The method called when a tag is removed\n@param {boolean}  props.disabled           True if the plugin is disabled\n@param {number}   [props.maxVisibleTags=0] The maximum number of tags to show. 0 or less than 0 evaluates to \"Show All\".",
  "methods": [],
  "displayName": "Tags",
  "props": {
    "tags": {
      "defaultValue": {
        "value": "[]",
        "computed": false
      },
      "required": false
    },
    "maxVisibleTags": {
      "defaultValue": {
        "value": "0",
        "computed": false
      },
      "required": false
    },
    "onChange": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/tree-select-control/constants.js
const ROOT_VALUE = '__WC_TREE_SELECT_COMPONENT_ROOT__';
const BACKSPACE = 'Backspace';
const ESCAPE = 'Escape';
const ENTER = 'Enter';
const ARROW_UP = 'ArrowUp';
const ARROW_DOWN = 'ArrowDown';
const ARROW_LEFT = 'ArrowLeft';
const ARROW_RIGHT = 'ArrowRight';
;// ../../packages/js/components/src/tree-select-control/control.js
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

const Control = (0,react.forwardRef)(({
  tags = [],
  instanceId,
  placeholder,
  isExpanded,
  disabled,
  maxVisibleTags,
  value = '',
  onFocus = () => {},
  onTagsChange = () => {},
  onInputChange = () => {},
  onControlClick = lodash.noop,
  alwaysShowPlaceholder = false
}, ref) => {
  const hasTags = tags.length > 0;
  const showPlaceholder = alwaysShowPlaceholder ? true : !hasTags && !isExpanded;

  /**
   * Handles keydown event
   *
   * Keys:
   * When key down is BACKSPACE. Delete the last tag.
   *
   * @param {Event} event Event object
   */
  const handleKeydown = event => {
    if (BACKSPACE === event.key) {
      if (value) return;
      onTagsChange(tags.slice(0, -1));
      event.preventDefault();
    }
  };
  return (
    /*#__PURE__*/
    /**
     * ESLint Disable reason
     * https://github.com/woocommerce/woocommerce-admin/blob/main/packages/components/src/select-control/control.js#L200
     */
    /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('components-base-control', 'woocommerce-tree-select-control__control', {
        'is-disabled': disabled,
        'has-tags': hasTags
      }),
      onClick: e => {
        ref.current.focus();
        onControlClick(e);
      },
      children: [hasTags && /*#__PURE__*/(0,jsx_runtime.jsx)(tree_select_control_tags, {
        disabled: disabled,
        tags: tags,
        maxVisibleTags: maxVisibleTags,
        onChange: onTagsChange
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "components-base-control__field",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
          ref: ref,
          id: `woocommerce-tree-select-control-${instanceId}__control-input`,
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
        })
      })]
    })
  );
});
/* harmony default export */ const control = (Control);
;
Control.__docgenInfo = {
  "description": "The Control Component renders a search input and also the Tags.\nIt also triggers the setExpand for expanding the options tree on click.\n\n@param {Object}   props                       Component props\n@param {Array}    props.tags                  Array of tags\n@param {string}   props.instanceId            Id of the component\n@param {string}   props.placeholder           Placeholder of the search input\n@param {boolean}  props.isExpanded            True if the tree is expanded\n@param {boolean}  props.alwaysShowPlaceholder Will always show placeholder (default: false)\n@param {boolean}  props.disabled              True if the component is disabled\n@param {number}   props.maxVisibleTags        The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to \"Show All\".\n@param {string}   props.value                 The current input value\n@param {Function} props.onFocus               On Focus Callback\n@param {Function} props.onTagsChange          Callback when the Tags change\n@param {Function} props.onInputChange         Callback when the Input value changes\n@param {Function} [props.onControlClick]      Callback when clicking on the control.\n@return {JSX.Element} The rendered component",
  "methods": [],
  "displayName": "Control",
  "props": {
    "tags": {
      "defaultValue": {
        "value": "[]",
        "computed": false
      },
      "required": false
    },
    "value": {
      "defaultValue": {
        "value": "''",
        "computed": false
      },
      "required": false
    },
    "onFocus": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "required": false
    },
    "onTagsChange": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "required": false
    },
    "onInputChange": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "required": false
    },
    "onControlClick": {
      "defaultValue": {
        "value": "noop",
        "computed": true
      },
      "required": false
    },
    "alwaysShowPlaceholder": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.js
var check = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.js");
;// ../../packages/js/components/src/tree-select-control/checkbox.js
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

const Checkbox = ({
  option,
  checked,
  className,
  ...props
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: className,
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "components-base-control__field",
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
        className: "components-checkbox-control__input-container",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("input", {
          id: `inspector-checkbox-control-${option.key ?? option.value}`,
          className: "components-checkbox-control__input",
          type: "checkbox",
          tabIndex: "-1",
          value: option.value,
          checked: checked,
          ...props
        }), checked && /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: check/* default */.A,
          role: "presentation",
          className: "components-checkbox-control__checked"
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
        className: "components-checkbox-control__label",
        htmlFor: `inspector-checkbox-control-${option.key ?? option.value}`,
        children: option.label
      })]
    })
  });
};
/* harmony default export */ const tree_select_control_checkbox = (Checkbox);
;
Checkbox.__docgenInfo = {
  "description": "Renders a custom Checkbox\n\n@param {Object}  props           Component properties\n@param {Option}  props.option    Option for the checkbox\n@param {string}  props.className The className for the component\n@param {boolean} props.checked   Defines if the checkbox is checked\n@return {JSX.Element|null} The Checkbox component",
  "methods": [],
  "displayName": "Checkbox"
};
;// ../../packages/js/components/src/tree-select-control/options.js
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

const Options = ({
  options = [],
  onChange = () => {},
  onExpanderClick = lodash.noop,
  onToggleExpanded = lodash.noop,
  parent = null
}) => {
  /**
   * Alters the node with some keys for accessibility
   * ArrowRight - Expands the node
   * ArrowLeft - Collapses the node
   *
   * @param {Event}       event  The KeyDown event
   * @param {InnerOption} option The option where the event happened
   */
  const handleKeyDown = (event, option) => {
    if (!option.hasChildren) {
      return;
    }
    if (event.key === ARROW_RIGHT && !option.expanded) {
      onToggleExpanded(option);
    } else if (event.key === ARROW_LEFT && option.expanded) {
      onToggleExpanded(option);
    }
  };
  return options.map(option => {
    const isRoot = option.value === ROOT_VALUE;
    const {
      hasChildren,
      checked,
      partialChecked,
      expanded
    } = option;
    if (!option?.value) return null;
    if (!isRoot && !option?.isVisible) return null;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      role: hasChildren ? 'treegroup' : 'treeitem',
      "aria-expanded": hasChildren ? expanded : undefined,
      className: (0,clsx/* default */.A)('woocommerce-tree-select-control__node', hasChildren && 'has-children'),
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
        justify: "flex-start",
        children: [!isRoot && /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
          className: (0,clsx/* default */.A)('woocommerce-tree-select-control__expander', !hasChildren && 'is-hidden'),
          tabIndex: "-1",
          onClick: e => {
            e.preventDefault();
            onExpanderClick(e);
            onToggleExpanded(option);
          },
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
            icon: expanded ? chevron_up/* default */.A : chevron_down/* default */.A
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(tree_select_control_checkbox, {
          className: (0,clsx/* default */.A)('components-base-control', 'woocommerce-tree-select-control__option', partialChecked && 'is-partially-checked'),
          option: option,
          checked: checked,
          onChange: e => {
            onChange(e.target.checked, option, parent);
          },
          onKeyDown: e => {
            handleKeyDown(e, option);
          }
        })]
      }), hasChildren && expanded && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-tree-select-control__children', isRoot && 'woocommerce-tree-select-control__main'),
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(Options, {
          options: option.children,
          onChange: onChange,
          onExpanderClick: onExpanderClick,
          onToggleExpanded: onToggleExpanded,
          parent: option
        })
      })]
    }, `${option.key ?? option.value}`);
  });
};
/* harmony default export */ const tree_select_control_options = (Options);
;// ../../packages/js/components/src/tree-select-control/index.js
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

const TreeSelectControl = ({
  id,
  label,
  selectAllLabel = (0,build_module.__)('All', 'woocommerce'),
  help,
  placeholder,
  className,
  disabled,
  options = [],
  value = [],
  maxVisibleTags,
  onChange = () => {},
  onDropdownVisibilityChange = lodash.noop,
  onInputChange = lodash.noop,
  includeParent = false,
  individuallySelectParent = false,
  alwaysShowPlaceholder = false,
  minFilterQueryLength = 3,
  clearOnSelect = true
}) => {
  let instanceId = (0,use_instance_id/* default */.A)(TreeSelectControl);
  instanceId = id ?? instanceId;
  const [treeVisible, setTreeVisible] = (0,react.useState)(false);
  const [nodesExpanded, setNodesExpanded] = (0,react.useState)([]);
  const [inputControlValue, setInputControlValue] = (0,react.useState)('');
  const controlRef = (0,react.useRef)();
  const dropdownRef = (0,react.useRef)();
  const onDropdownVisibilityChangeRef = (0,react.useRef)();
  onDropdownVisibilityChangeRef.current = onDropdownVisibilityChange;

  // We will save in a REF previous search filter queries to avoid re-query the tree and save performance
  const cacheRef = (0,react.useRef)({
    filteredOptionsMap: new Map()
  });
  cacheRef.current.expandedValues = nodesExpanded;
  cacheRef.current.selectedValues = value;
  const showTree = !disabled && treeVisible;
  const root = selectAllLabel !== false ? {
    label: selectAllLabel,
    value: ROOT_VALUE,
    children: options
  } : null;
  const treeOptions = tree_select_control_useIsEqualRefValue(root ? [root] : options);
  const focusOutside = (0,use_focus_outside/* default */.A)(() => {
    setTreeVisible(false);
  });
  const filterQuery = inputControlValue.trim().toLowerCase();
  // we only trigger the filter when there are more than 3 characters in the input.
  const filter = filterQuery.length >= minFilterQueryLength ? filterQuery : '';

  /**
   * Optimizes the performance for getting the tags info
   */
  const optionsRepository = (0,react.useMemo)(() => {
    const repository = {};

    // Clear cache if options change
    cacheRef.current.filteredOptionsMap.clear();
    function loadOption(option, parentId) {
      option.parent = parentId;
      option.children?.forEach(el => loadOption(el, option.value));
      repository[option.key ?? option.value] = option;
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
  const filteredOptions = (0,react.useMemo)(() => {
    const {
      current: cache
    } = cacheRef;
    const cachedFilteredOptions = cache.filteredOptionsMap.get(filter);
    if (cachedFilteredOptions) {
      return cachedFilteredOptions;
    }
    const isSearching = Boolean(filter);
    const highlightOptionLabel = (optionLabel, matchPosition) => {
      const matchLength = matchPosition + filter.length;
      if (!isSearching) return optionLabel;
      return /*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: optionLabel.substring(0, matchPosition)
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
          children: optionLabel.substring(matchPosition, matchLength)
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: optionLabel.substring(matchLength)
        })]
      });
    };
    const descriptors = {
      hasChildren: {
        /**
         * Returns whether this option has children.
         *
         * @return {boolean} True if has children, false otherwise.
         */
        get() {
          return this.children?.length > 0;
        }
      },
      leaves: {
        /**
         * Return all leaf options flattened under this option. The options are filtered if in searching.
         *
         * @return {InnerOption[]} All leaf options that are flattened under this option. The options are filtered if in searching.
         */
        get() {
          if (!this.hasChildren) {
            return [];
          }
          return this.children.flatMap(option => {
            if (option.hasChildren) {
              return includeParent && option.value !== ROOT_VALUE ? [option, ...option.leaves] : option.leaves;
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
        get() {
          if (includeParent && this.value !== ROOT_VALUE || individuallySelectParent) {
            return cache.selectedValues.includes(this.value);
          }
          if (this.hasChildren) {
            return this.leaves.every(opt => opt.checked);
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
        get() {
          if (!this.hasChildren) {
            return false;
          }
          return !this.checked && this.children.some(opt => opt.checked || opt.partialChecked);
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
        get() {
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
            return this.children.some(opt => opt.isVisible);
          }
          return this.leaves.some(opt => opt.isSearchResult);
        }
      },
      isSearchResult: {
        /**
         * Returns whether this option is a searched result.
         *
         * @return {boolean} True if option is being searched, false otherwise.
         */
        get() {
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
        get() {
          return isSearching && this.isVisible || this.value === ROOT_VALUE || cache.expandedValues.includes(this.value);
        }
      }
    };

    /**
     * Decompose accented characters into their composable parts, then remove accents.
     * See https://www.unicode.org/reports/tr15/ and https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/normalize.
     */
    const removeAccents = str => {
      return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    };
    const reduceOptions = (acc, {
      children = [],
      ...option
    }) => {
      if (children.length) {
        option.children = children.reduce(reduceOptions, []);
      }
      if (isSearching) {
        const labelWithAccentsRemoved = removeAccents(option.label);
        const filterWithAccentsRemoved = removeAccents(filter);
        const match = labelWithAccentsRemoved.toLowerCase().indexOf(filterWithAccentsRemoved);
        if (match > -1) {
          option.label = highlightOptionLabel(option.label, match);
          option.filterMatch = true;
        }
      }
      Object.defineProperties(option, descriptors);
      acc.push(option);
      return acc;
    };
    const filteredTreeOptions = treeOptions.reduce(reduceOptions, []);
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
  const onKeyDown = event => {
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
    const stepDict = {
      [ARROW_UP]: -1,
      [ARROW_DOWN]: 1
    };
    const step = stepDict[event.key];
    if (step && dropdownRef.current && filteredOptions.length) {
      const elements = dom_build_module/* focus */.XC.focusable.find(dropdownRef.current).filter(el => el.type === 'checkbox');
      const currentIndex = elements.indexOf(event.target);
      const index = Math.max(currentIndex + step, -1) % elements.length;
      elements.at(index).focus();
      event.preventDefault();
    }
  };
  (0,react.useEffect)(() => {
    onDropdownVisibilityChangeRef.current(showTree);
  }, [showTree]);

  /**
   * Get formatted Tags from the selected values.
   *
   * @return {Array<{id: string, label: string|undefined}>} An array of Tags
   */
  const tags = (0,react.useMemo)(() => {
    if (!options.length) {
      return [];
    }
    return value.map(key => {
      const option = optionsRepository[key];
      return {
        id: key,
        label: option?.label
      };
    });
  }, [optionsRepository, value, options]);

  /**
   * Handle click event on the option expander
   *
   * @param {Event} e The click event object
   */
  const handleExpanderClick = e => {
    const elements = dom_build_module/* focus */.XC.focusable.find(dropdownRef.current);
    const index = elements.indexOf(e.currentTarget) + 1;
    elements[index].focus();
  };

  /**
   * Expands/Collapses the Option
   *
   * @param {InnerOption} option The option to be expanded or collapsed.
   */
  const handleToggleExpanded = option => {
    setNodesExpanded(option.expanded ? nodesExpanded.filter(el => option.value !== el) : [...nodesExpanded, option.value]);
  };

  /**
   * Handles a change of a child element.
   *
   * @param {boolean}     checked Indicates if the item should be checked
   * @param {InnerOption} option  The option to change
   * @param {InnerOption} parent  The options parent (could be null)
   */
  const handleSingleChange = (checked, option, parent) => {
    const newValue = checked ? [...value, option.value] : value.filter(el => el !== option.value);
    if (includeParent && parent && parent.value !== ROOT_VALUE && parent.children && parent.children.every(child => newValue.includes(child.value)) && !newValue.includes(parent.value)) {
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
  const handleParentChange = (checked, option) => {
    let newValue;
    const changedValues = individuallySelectParent ? [option.value] : option.leaves.filter(opt => opt.checked !== checked).map(opt => opt.value);
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
      newValue = value.filter(el => !changedValues.includes(el));
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
  const handleOptionsChange = (checked, option, parent) => {
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
  const handleTagsChange = newTags => {
    onChange([...newTags.map(el => el.id)]);
  };

  /**
   * Prepares and sets the search filter.
   * Filters of less than 3 characters are not considered, so we convert them to ''
   *
   * @param {Event} e Event returned by the On Change function in the Input control
   */
  const handleOnInputChange = e => {
    setTreeVisible(true);
    onInputChange(e.target.value);
    setInputControlValue(e.target.value);
  };
  return (
    /*#__PURE__*/
    // eslint-disable-next-line jsx-a11y/no-static-element-interactions
    (0,jsx_runtime.jsxs)("div", {
      ...focusOutside,
      onKeyDown: onKeyDown,
      className: (0,clsx/* default */.A)('woocommerce-tree-select-control', className),
      children: [!!label && /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
        htmlFor: `woocommerce-tree-select-control-${instanceId}__control-input`,
        className: "woocommerce-tree-select-control__label",
        children: label
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(control, {
        ref: controlRef,
        disabled: disabled,
        tags: tags,
        isExpanded: showTree,
        onFocus: () => {
          setTreeVisible(true);
        },
        onControlClick: () => {
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
      }), showTree && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        ref: dropdownRef,
        className: "woocommerce-tree-select-control__tree",
        role: "tree",
        tabIndex: "-1",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(tree_select_control_options, {
          options: filteredOptions,
          onChange: handleOptionsChange,
          onExpanderClick: handleExpanderClick,
          onToggleExpanded: handleToggleExpanded
        })
      }), help && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-tree-select-control__help",
        children: help
      })]
    })
  );
};
/* harmony default export */ const tree_select_control = (TreeSelectControl);
;
TreeSelectControl.__docgenInfo = {
  "description": "Renders a component with a searchable control, tags and a tree selector.\n\n@param {Object}                     props                              Component props.\n@param {string}                     [props.id]                         Component id\n@param {string}                     [props.label]                      Label for the component\n@param {string | false}             [props.selectAllLabel]             Label for the Select All root element. False for disable.\n@param {string}                     [props.help]                       Help text under the select input.\n@param {string}                     [props.placeholder]                Placeholder for the search control input\n@param {string}                     [props.className]                  The class name for this component\n@param {boolean}                    [props.disabled]                   Disables the component\n@param {boolean}                    [props.includeParent]              Includes parent with selection.\n@param {boolean}                    [props.individuallySelectParent]   Considers parent as a single item (default: false).\n@param {boolean}                    [props.alwaysShowPlaceholder]      Will always show placeholder (default: false)\n@param {Option[]}                   [props.options]                    Options to show in the component\n@param {string[]}                   [props.value]                      Selected values\n@param {number}                     [props.maxVisibleTags]             The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to \"Show All\".\n@param {Function}                   [props.onChange]                   Callback when the selector changes\n@param {(visible: boolean) => void} [props.onDropdownVisibilityChange] Callback when the visibility of the dropdown options is changed.\n@param {Function}                   [props.onInputChange]              Callback when the selector changes\n@param {number}                     [props.minFilterQueryLength]       Minimum input length to filter results by.\n@param {boolean}                    [props.clearOnSelect]              Clear input on select (default: true).\n@return {JSX.Element} The component",
  "methods": [],
  "displayName": "TreeSelectControl",
  "props": {
    "selectAllLabel": {
      "defaultValue": {
        "value": "__( 'All', 'woocommerce' )",
        "computed": true
      },
      "required": false
    },
    "options": {
      "defaultValue": {
        "value": "[]",
        "computed": false
      },
      "required": false
    },
    "value": {
      "defaultValue": {
        "value": "[]",
        "computed": false
      },
      "required": false
    },
    "onChange": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "required": false
    },
    "onDropdownVisibilityChange": {
      "defaultValue": {
        "value": "noop",
        "computed": true
      },
      "required": false
    },
    "onInputChange": {
      "defaultValue": {
        "value": "noop",
        "computed": true
      },
      "required": false
    },
    "includeParent": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "required": false
    },
    "individuallySelectParent": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "required": false
    },
    "alwaysShowPlaceholder": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "required": false
    },
    "minFilterQueryLength": {
      "defaultValue": {
        "value": "3",
        "computed": false
      },
      "required": false
    },
    "clearOnSelect": {
      "defaultValue": {
        "value": "true",
        "computed": false
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/tree-select-control/stories/tree-select-control.story.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const treeSelectControlOptions = [{
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
const Template = args => {
  const [selected, setSelected] = (0,react.useState)(['ES']);
  (0,react.useEffect)(() => {
    if (args.onChange) {
      args.onChange(selected);
    }
  }, [selected]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(tree_select_control, {
    ...args,
    value: selected,
    onChange: setSelected
  });
};
const Base = Template.bind({});
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
  title: 'Components/TreeSelectControl',
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