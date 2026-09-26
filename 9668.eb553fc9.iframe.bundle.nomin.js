"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9668],{

/***/ "../../packages/js/components/src/select-control/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ select_control)
});

// UNUSED EXPORTS: SelectControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js + 1 modules
var with_spoken_messages = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js
var with_focus_outside = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js + 1 modules
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js
var keycodes_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-window@1.8.11_react-d_73e00b938ef46e831e536da690e6cf36/node_modules/react-window/dist/index.esm.js + 1 modules
var index_esm = __webpack_require__("../../node_modules/.pnpm/react-window@1.8.11_react-d_73e00b938ef46e831e536da690e6cf36/node_modules/react-window/dist/index.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/select-control/list.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */

const VirtualOption = ({
  index,
  style,
  data
}) => {
  const {
    options,
    selectedIndex,
    instanceId,
    onSelect,
    getOptionRef
  } = data;
  const option = options[index];
  return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    ref: getOptionRef(index),
    id: `woocommerce-select-control__option-${instanceId}-${option.key}`,
    role: "option",
    "aria-selected": index === selectedIndex,
    "aria-setsize": options.length,
    "aria-posinset": index + 1,
    disabled: option.isDisabled,
    className: (0,clsx/* default */.A)('woocommerce-select-control__option', {
      'is-selected': index === selectedIndex
    }),
    onClick: () => onSelect(option),
    tabIndex: -1,
    style: style,
    children: option.label
  }, option.key);
};

/**
 * A list box that displays filtered options after search.
 */
class List extends react.Component {
  constructor(props) {
    super(props);
    this.handleKeyDown = this.handleKeyDown.bind(this);
    this.select = this.select.bind(this);
    this.optionRefs = {};
    this.listbox = (0,react.createRef)();
    this.listRef = (0,react.createRef)();
  }
  componentDidUpdate(prevProps) {
    const {
      options,
      selectedIndex,
      virtualScroll
    } = this.props;

    // Remove old option refs to avoid memory leaks.
    if (!(0,lodash.isEqual)(options, prevProps.options)) {
      this.optionRefs = {};
    }
    if (selectedIndex !== prevProps.selectedIndex && (0,lodash.isNumber)(selectedIndex)) {
      if (virtualScroll && this.listRef.current) {
        this.listRef.current.scrollToItem(selectedIndex, 'smart');
      } else {
        this.scrollToOption(selectedIndex);
      }
    }
  }
  getOptionRef(index) {
    if (!this.optionRefs.hasOwnProperty(index)) {
      this.optionRefs[index] = (0,react.createRef)();
    }
    return this.optionRefs[index];
  }
  select(option) {
    const {
      onSelect
    } = this.props;
    if (option.isDisabled) {
      return;
    }
    onSelect(option);
  }
  scrollToOption(index) {
    const listbox = this.listbox.current;
    if (!listbox) {
      return;
    }
    if (listbox.scrollHeight <= listbox.clientHeight) {
      return;
    }
    if (!this.optionRefs[index]) {
      return;
    }
    const option = this.optionRefs[index].current;
    if (!option) {
      // eslint-disable-next-line no-console
      console.warn('Option not found, index:', index);
      return;
    }
    const scrollBottom = listbox.clientHeight + listbox.scrollTop;
    const elementBottom = option.offsetTop + option.offsetHeight;
    if (elementBottom > scrollBottom) {
      listbox.scrollTop = elementBottom - listbox.clientHeight;
    } else if (option.offsetTop < listbox.scrollTop) {
      listbox.scrollTop = option.offsetTop;
    }
  }
  handleKeyDown(event) {
    const {
      decrementSelectedIndex,
      incrementSelectedIndex,
      options,
      onSearch,
      selectedIndex,
      setExpanded
    } = this.props;
    if (options.length === 0) {
      return;
    }
    switch (event.keyCode) {
      case keycodes_build_module.UP:
        decrementSelectedIndex();
        event.preventDefault();
        event.stopPropagation();
        break;
      case keycodes_build_module/* DOWN */.PX:
        incrementSelectedIndex();
        event.preventDefault();
        event.stopPropagation();
        break;
      case keycodes_build_module/* ENTER */.Fm:
        if ((0,lodash.isNumber)(selectedIndex) && options[selectedIndex]) {
          this.select(options[selectedIndex]);
        }
        event.preventDefault();
        event.stopPropagation();
        break;
      case keycodes_build_module/* LEFT */.M3:
      case keycodes_build_module/* RIGHT */.NS:
        setExpanded(false);
        break;
      case keycodes_build_module/* ESCAPE */._f:
        setExpanded(false);
        onSearch(null);
        return;
      case keycodes_build_module/* TAB */.wn:
        if ((0,lodash.isNumber)(selectedIndex) && options[selectedIndex]) {
          this.select(options[selectedIndex]);
        }
        setExpanded(false);
        break;
      default:
    }
  }
  toggleKeyEvents(isListening) {
    const {
      node
    } = this.props;
    if (!node) {
      // eslint-disable-next-line no-console
      console.warn('No node to bind events to.');
      return;
    }

    // This exists because we must capture ENTER key presses before RichText.
    // It seems that react fires the simulated capturing events after the
    // native browser event has already bubbled so we can't stopPropagation
    // and avoid RichText getting the event from TinyMCE, hence we must
    // register a native event handler.
    const handler = isListening ? 'addEventListener' : 'removeEventListener';
    node[handler]('keydown', this.handleKeyDown, true);
  }
  componentDidMount() {
    const {
      selectedIndex
    } = this.props;
    if ((0,lodash.isNumber)(selectedIndex) && selectedIndex > -1) {
      if (this.props.virtualScroll && this.listRef.current) {
        this.listRef.current.scrollToItem(selectedIndex, 'smart');
      } else {
        this.scrollToOption(selectedIndex);
      }
    }
    this.toggleKeyEvents(true);
  }
  componentWillUnmount() {
    this.toggleKeyEvents(false);
  }
  render() {
    const {
      instanceId,
      listboxId,
      options,
      selectedIndex,
      staticList,
      virtualScroll,
      virtualItemHeight = 35,
      virtualListHeight = 300
    } = this.props;
    const listboxClasses = (0,clsx/* default */.A)('woocommerce-select-control__listbox', {
      'is-static': staticList,
      'is-virtual': virtualScroll
    });
    if (virtualScroll) {
      return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        id: listboxId,
        role: "listbox",
        className: listboxClasses,
        tabIndex: -1,
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(index_esm/* FixedSizeList */.Y1, {
          ref: this.listRef,
          height: Math.min(virtualListHeight, options.length * virtualItemHeight),
          width: "100%",
          itemCount: options.length,
          itemSize: virtualItemHeight,
          itemData: {
            options,
            selectedIndex,
            instanceId,
            onSelect: this.select,
            getOptionRef: this.getOptionRef.bind(this)
          },
          children: VirtualOption
        })
      });
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      ref: this.listbox,
      id: listboxId,
      role: "listbox",
      className: listboxClasses,
      tabIndex: -1,
      children: options.map((option, index) => /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        ref: this.getOptionRef(index),
        id: `woocommerce-select-control__option-${instanceId}-${option.key}`,
        role: "option",
        "aria-selected": index === selectedIndex,
        disabled: option.isDisabled,
        className: (0,clsx/* default */.A)('woocommerce-select-control__option', {
          'is-selected': index === selectedIndex
        }),
        onClick: () => this.select(option),
        tabIndex: -1,
        children: option.label
      }, option.key))
    });
  }
}
/* harmony default export */ const list = (List);
try {
    // @ts-ignore
    List.displayName = "List";
    // @ts-ignore
    List.__docgenInfo = { "description": "A list box that displays filtered options after search.", "displayName": "List", "props": { "listboxId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "instanceId", "required": true, "type": { "name": "number" } }, "node": { "defaultValue": null, "description": "Parent node to bind keyboard events to.", "name": "node", "required": true, "type": { "name": "HTMLElement | null" } }, "onSelect": { "defaultValue": null, "description": "Function to execute when an option is selected.", "name": "onSelect", "required": true, "type": { "name": "(option: Option) => void" } }, "options": { "defaultValue": null, "description": "Array of options to display.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "selectedIndex": { "defaultValue": null, "description": "Integer for the currently selected item.", "name": "selectedIndex", "required": true, "type": { "name": "number | null | undefined" } }, "staticList": { "defaultValue": null, "description": "Bool to determine if the list should be positioned absolutely or statically.", "name": "staticList", "required": true, "type": { "name": "boolean" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "onSearch": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "onSearch", "required": true, "type": { "name": "(option: string | null) => void" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the list should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } }, "virtualScroll": { "defaultValue": null, "description": "Enable virtual scrolling for large lists of options.", "name": "virtualScroll", "required": false, "type": { "name": "boolean" } }, "virtualItemHeight": { "defaultValue": null, "description": "Height in pixels for each virtual item.", "name": "virtualItemHeight", "required": false, "type": { "name": "number" } }, "virtualListHeight": { "defaultValue": null, "description": "Maximum height in pixels for the virtualized list.", "name": "virtualListHeight", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/list.tsx#List"] = { docgenInfo: List.__docgenInfo, name: "List", path: "../../packages/js/components/src/select-control/list.tsx#List" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js
var cancel_circle_filled = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js");
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
;// ../../packages/js/components/src/select-control/tags.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


/**
 * A list of tags to display selected items.
 */
class Tags extends react.Component {
  constructor(props) {
    super(props);
    this.removeAll = this.removeAll.bind(this);
    this.removeResult = this.removeResult.bind(this);
  }
  removeAll() {
    const {
      onChange
    } = this.props;
    onChange([]);
  }
  removeResult(key) {
    return () => {
      const {
        selected,
        onChange
      } = this.props;
      if (!(0,lodash.isArray)(selected)) {
        return;
      }
      const i = (0,lodash.findIndex)(selected, {
        key
      });
      onChange([...selected.slice(0, i), ...selected.slice(i + 1)]);
    };
  }
  render() {
    const {
      selected,
      showClearButton
    } = this.props;
    if (!(0,lodash.isArray)(selected) || !selected.length) {
      return null;
    }
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-select-control__tags",
        children: selected.map((item, i) => {
          if (!item.label) {
            return null;
          }
          const screenReaderLabel = (0,build_module/* sprintf */.nv)(/* translators: %1$s: tag label, %2$s: tag number, %3$s: total number of tags */
          (0,build_module.__)('%1$s (%2$s of %3$s)', 'woocommerce'), item.label, (i + 1).toString(), selected.length.toString());
          return /*#__PURE__*/(0,jsx_runtime.jsx)(tag/* default */.A, {
            id: item.key,
            label: item.label
            // @ts-expect-error key is a string or undefined here
            ,
            remove: this.removeResult,
            screenReaderLabel: screenReaderLabel
          }, item.key);
        })
      }), showClearButton && /*#__PURE__*/(0,jsx_runtime.jsxs)(build_module_button/* default */.Ay, {
        className: "woocommerce-select-control__clear",
        isLink: true,
        onClick: this.removeAll,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: cancel_circle_filled/* default */.A,
          className: "clear-icon"
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "screen-reader-text",
          children: (0,build_module.__)('Clear all', 'woocommerce')
        })]
      })]
    });
  }
}
/* harmony default export */ const tags = (Tags);
try {
    // @ts-ignore
    Tags.displayName = "Tags";
    // @ts-ignore
    Tags.__docgenInfo = { "description": "A list of tags to display selected items.", "displayName": "Tags", "props": { "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": true, "type": { "name": "(selected: Option[]) => void" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values. If the label of the selected\nvalue is omitted, the Tag of that value will not be rendered inside the\nsearch box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "showClearButton": { "defaultValue": null, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/tags.tsx#Tags"] = { docgenInfo: Tags.__docgenInfo, name: "Tags", path: "../../packages/js/components/src/select-control/tags.tsx#Tags" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/search.js
var search = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/search.js");
;// ../../packages/js/components/src/select-control/control.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


/**
 * A search control to allow user input to filter the options.
 */
class Control extends react.Component {
  constructor(props) {
    super(props);
    this.state = {
      isActive: false
    };
    this.input = (0,react.createRef)();
    this.updateSearch = this.updateSearch.bind(this);
    this.onFocus = this.onFocus.bind(this);
    this.onBlur = this.onBlur.bind(this);
    this.onKeyDown = this.onKeyDown.bind(this);
  }
  updateSearch(onSearch) {
    return event => {
      onSearch(event.target.value);
    };
  }
  onFocus(onSearch) {
    const {
      isSearchable,
      setExpanded,
      showAllOnFocus,
      updateSearchOptions
    } = this.props;
    return event => {
      this.setState({
        isActive: true
      });
      if (isSearchable && showAllOnFocus) {
        event.target.select();
        updateSearchOptions('');
      } else if (isSearchable) {
        onSearch(event.target.value);
      } else {
        setExpanded(true);
      }
    };
  }
  onBlur() {
    const {
      onBlur
    } = this.props;
    if (typeof onBlur === 'function') {
      onBlur();
    }
    this.setState({
      isActive: false
    });
  }
  onKeyDown(event) {
    const {
      decrementSelectedIndex,
      incrementSelectedIndex,
      selected,
      onChange,
      query,
      setExpanded
    } = this.props;
    if (keycodes_build_module/* BACKSPACE */.G_ === event.keyCode && !query && (0,lodash.isArray)(selected) && selected.length) {
      onChange([...selected.slice(0, -1)]);
    }
    if (keycodes_build_module/* DOWN */.PX === event.keyCode) {
      incrementSelectedIndex();
      setExpanded(true);
      event.preventDefault();
      event.stopPropagation();
    }
    if (keycodes_build_module.UP === event.keyCode) {
      decrementSelectedIndex();
      setExpanded(true);
      event.preventDefault();
      event.stopPropagation();
    }
  }
  renderButton() {
    const {
      multiple,
      selected
    } = this.props;
    if (multiple || !(0,lodash.isArray)(selected) || !selected.length) {
      return null;
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-select-control__control-value",
      children: selected[0].label
    });
  }
  renderInput() {
    const {
      activeId,
      disabled,
      hasTags,
      inlineTags,
      instanceId,
      isExpanded,
      isSearchable,
      listboxId,
      onSearch,
      placeholder,
      searchInputType,
      autoComplete
    } = this.props;
    const {
      isActive
    } = this.state;
    return /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
      autoComplete: autoComplete || 'off',
      className: "woocommerce-select-control__control-input",
      id: `woocommerce-select-control-${instanceId}__control-input`,
      ref: this.input,
      type: isSearchable ? searchInputType : 'button',
      value: this.getInputValue(),
      placeholder: isActive ? placeholder : '',
      onChange: this.updateSearch(onSearch),
      onFocus: this.onFocus(onSearch),
      onBlur: this.onBlur,
      onKeyDown: this.onKeyDown,
      role: "combobox",
      "aria-autocomplete": "list",
      "aria-expanded": isExpanded,
      "aria-haspopup": "true",
      "aria-owns": listboxId,
      "aria-controls": listboxId,
      "aria-activedescendant": activeId,
      "aria-describedby": hasTags && inlineTags ? `search-inline-input-${instanceId}` : undefined,
      disabled: disabled,
      "aria-label": this.props.ariaLabel ?? this.props.label
    });
  }
  getInputValue() {
    const {
      inlineTags,
      isFocused,
      isSearchable,
      multiple,
      query,
      selected
    } = this.props;
    const selectedValue = (0,lodash.isArray)(selected) && selected.length && typeof selected[0].label === 'string' ? selected[0].label : '';

    // Show the selected value for simple select dropdowns.
    if (!multiple && !isFocused && !inlineTags) {
      return selectedValue;
    }

    // Show the search query when focused on searchable controls.
    if (isSearchable && isFocused && query) {
      return query;
    }
    return '';
  }
  render() {
    const {
      className,
      disabled,
      hasTags,
      help,
      inlineTags,
      instanceId,
      isSearchable,
      label,
      query,
      onChange,
      showClearButton
    } = this.props;
    const {
      isActive
    } = this.state;
    return (
      /*#__PURE__*/
      // Disable reason: The div below visually simulates an input field. Its
      // child input is the actual input and responds accordingly to all keyboard
      // events, but click events need to be passed onto the child input. There
      // is no appropriate aria role for describing this situation, which is only
      // for the benefit of sighted users.
      /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
      (0,jsx_runtime.jsxs)("div", {
        className: (0,clsx/* default */.A)('components-base-control', 'woocommerce-select-control__control', className, {
          empty: !query || query.length === 0,
          'is-active': isActive,
          'has-tags': inlineTags && hasTags,
          'with-value': this.getInputValue()?.length,
          'has-error': !!help,
          'is-disabled': disabled
        }),
        onClick: event => {
          // Don't focus the input if the click event is from the error message.
          if (
          // eslint-disable-next-line @typescript-eslint/ban-ts-comment
          // @ts-ignore - event.target.className is not in the type definition.
          event.target.className !== 'components-base-control__help' && this.input.current) {
            this.input.current.focus();
          }
        },
        children: [isSearchable && /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          className: "woocommerce-select-control__control-icon",
          icon: search/* default */.A
        }), inlineTags && /*#__PURE__*/(0,jsx_runtime.jsx)(tags, {
          onChange: onChange,
          showClearButton: showClearButton,
          selected: this.props.selected
        }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "components-base-control__field",
          children: [!!label && /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
            htmlFor: `woocommerce-select-control-${instanceId}__control-input`,
            className: "components-base-control__label",
            children: label
          }), this.renderInput(), inlineTags && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            id: `search-inline-input-${instanceId}`,
            className: "screen-reader-text",
            children: (0,build_module.__)('Move backward for selected items', 'woocommerce')
          }), !!help && /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
            id: `woocommerce-select-control-${instanceId}__help`,
            className: "components-base-control__help",
            children: help
          })]
        })]
      })
      /* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    );
  }
}
/* harmony default export */ const control = (Control);
try {
    // @ts-ignore
    Control.displayName = "Control";
    // @ts-ignore
    Control.__docgenInfo = { "description": "A search control to allow user input to filter the options.", "displayName": "Control", "props": { "hasTags": { "defaultValue": null, "description": "Bool to determine if tags should be rendered.", "name": "hasTags", "required": false, "type": { "name": "boolean" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "inlineTags": { "defaultValue": null, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": null, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "instanceId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "listboxId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "Function called when the input is blurred.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": true, "type": { "name": "(selected: Option[]) => void" } }, "onSearch": { "defaultValue": null, "description": "Function called when input field is changed or focused.", "name": "onSearch", "required": true, "type": { "name": "(query: string) => void" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "query": { "defaultValue": null, "description": "Search query entered by user.", "name": "query", "required": false, "type": { "name": "string | null" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values. If the label of the selected\nvalue is omitted, the Tag of that value will not be rendered inside the\nsearch box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "showAllOnFocus": { "defaultValue": null, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": null, "description": "Control input autocomplete field, defaults: off.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the control should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } }, "updateSearchOptions": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "updateSearchOptions", "required": true, "type": { "name": "(query: string) => void" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "multiple": { "defaultValue": null, "description": "Multi-select mode allows multiple options to be selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "isFocused": { "defaultValue": null, "description": "Is the control currently focused.", "name": "isFocused", "required": false, "type": { "name": "boolean" } }, "activeId": { "defaultValue": null, "description": "ID for accessibility purposes. aria-activedescendant will be set to this value.", "name": "activeId", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "Disable the control.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "isExpanded": { "defaultValue": null, "description": "Is the control currently expanded. This is for accessibility purposes.", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": null, "description": "The type of input to use for the search field.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "ariaLabel": { "defaultValue": null, "description": "The aria label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Class name to be added to the input.", "name": "className", "required": false, "type": { "name": "string" } }, "showClearButton": { "defaultValue": null, "description": "Show the clear button.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/control.tsx#Control"] = { docgenInfo: Control.__docgenInfo, name: "Control", path: "../../packages/js/components/src/select-control/control.tsx#Control" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/select-control/index.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





const initialState = {
  isExpanded: false,
  isFocused: false,
  query: '',
  searchOptions: []
};

/**
 * A search box which filters options while typing,
 * allowing a user to select from an option from a filtered list.
 */
class SelectControl extends react.Component {
  static defaultProps = {
    ignoreDiacritics: false,
    excludeSelectedOptions: true,
    getSearchExpression: lodash.identity,
    inlineTags: false,
    isSearchable: false,
    onChange: lodash.noop,
    onFilter: lodash.identity,
    onSearch: options => Promise.resolve(options),
    maxResults: 0,
    multiple: false,
    searchDebounceTime: 0,
    searchInputType: 'search',
    selected: [],
    showAllOnFocus: false,
    showClearButton: false,
    hideBeforeSearch: false,
    staticList: false,
    autoComplete: 'off',
    virtualScroll: false,
    virtualItemHeight: 35,
    virtualListHeight: 300
  };
  node = null;
  activePromise = null;
  cacheSearchOptions = [];
  constructor(props) {
    super(props);
    const {
      selected,
      options,
      excludeSelectedOptions
    } = props;
    this.state = {
      ...initialState,
      searchOptions: [],
      selectedIndex: selected && options?.length && !excludeSelectedOptions ? options.findIndex(option => option.key === selected) : null
    };
    this.bindNode = this.bindNode.bind(this);
    this.decrementSelectedIndex = this.decrementSelectedIndex.bind(this);
    this.incrementSelectedIndex = this.incrementSelectedIndex.bind(this);
    this.onAutofillChange = this.onAutofillChange.bind(this);
    this.updateSearchOptions = (0,lodash.debounce)(this.updateSearchOptions.bind(this), props.searchDebounceTime);
    this.search = this.search.bind(this);
    this.selectOption = this.selectOption.bind(this);
    this.setExpanded = this.setExpanded.bind(this);
    this.setNewValue = this.setNewValue.bind(this);
  }
  componentDidUpdate(prevProps) {
    const {
      selected
    } = this.props;
    if (selected !== prevProps.selected) {
      this.reset(selected);
    }
  }
  bindNode(node) {
    this.node = node;
  }
  reset(selected = this.getSelected()) {
    const {
      multiple,
      excludeSelectedOptions
    } = this.props;
    const newState = {
      ...initialState
    };
    // Reset selectedIndex if single selection.
    if (!multiple && (0,lodash.isArray)(selected) && selected.length && selected[0].key) {
      newState.selectedIndex = !excludeSelectedOptions ? this.props.options.findIndex(i => i.key === selected[0].key) : null;
    }
    this.setState(newState);
  }
  handleFocusOutside() {
    this.reset();
  }
  hasMultiple() {
    const {
      multiple,
      selected
    } = this.props;
    if (!multiple) {
      return false;
    }
    if (Array.isArray(selected)) {
      return selected.some(item => Boolean(item.label));
    }
    return Boolean(selected);
  }
  hasTags() {
    const selected = this.getSelected();
    return Array.isArray(selected) && selected.some(item => Boolean(item.label));
  }
  getSelected() {
    const {
      multiple,
      options,
      selected
    } = this.props;

    // Return the passed value if an array is provided.
    if (multiple || Array.isArray(selected)) {
      return selected;
    }
    const selectedOption = options.find(option => option.key === selected);
    return selectedOption ? [selectedOption] : [];
  }
  selectOption(option) {
    const {
      multiple,
      selected
    } = this.props;
    const newSelected = multiple && (0,lodash.isArray)(selected) ? [...selected, option] : [option];
    this.reset(newSelected);
    const oldSelected = Array.isArray(selected) ? selected : [{
      key: selected
    }];
    const isSelected = oldSelected.findIndex(val => val.key === option.key);
    if (isSelected === -1) {
      this.setNewValue(newSelected);
    }

    // After selecting option, the list will reset and we'd need to correct selectedIndex.
    const newSelectedIndex = this.props.excludeSelectedOptions ?
    // Since we're excluding the selected option, invalidate selection
    // so re-focusing wont immediately set it to the neighbouring option.
    null : this.getOptions().findIndex(i => i.key === option.key);
    this.setState({
      selectedIndex: newSelectedIndex
    });
  }
  setNewValue(newValue) {
    const {
      onChange,
      selected,
      multiple
    } = this.props;
    const {
      query
    } = this.state;
    // Trigger a change if the selected value is different and pass back
    // an array or string depending on the original value.
    if (multiple || Array.isArray(selected)) {
      onChange(newValue, query);
    } else {
      onChange(newValue.length > 0 ? newValue[0].key : '', query);
    }
  }
  decrementSelectedIndex() {
    const {
      selectedIndex
    } = this.state;
    const options = this.getOptions();
    const nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex === 0 ? options.length : selectedIndex) - 1 : options.length - 1;
    this.setState({
      selectedIndex: nextSelectedIndex
    });
  }
  incrementSelectedIndex() {
    const {
      selectedIndex
    } = this.state;
    const options = this.getOptions();
    const nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex + 1) % options.length : 0;
    this.setState({
      selectedIndex: nextSelectedIndex
    });
  }
  announce(searchOptions) {
    const {
      debouncedSpeak
    } = this.props;
    if (!debouncedSpeak) {
      return;
    }
    if (!!searchOptions.length) {
      debouncedSpeak((0,build_module/* sprintf */.nv)(
      // translators: %d: number of results.
      (0,build_module._n)('%d result found, use up and down arrow keys to navigate.', '%d results found, use up and down arrow keys to navigate.', searchOptions.length, 'woocommerce'), searchOptions.length), 'assertive');
    } else {
      debouncedSpeak((0,build_module.__)('No results.', 'woocommerce'), 'assertive');
    }
  }
  getOptions() {
    const {
      isSearchable,
      options,
      excludeSelectedOptions
    } = this.props;
    const {
      searchOptions
    } = this.state;
    const selected = this.getSelected();
    const selectedKeys = (0,lodash.isArray)(selected) ? selected.map(option => option.key) : [];
    const shownOptions = isSearchable ? searchOptions : options;
    if (excludeSelectedOptions) {
      return shownOptions?.filter(option => !selectedKeys.includes(option.key));
    }
    return shownOptions;
  }
  getOptionsByQuery(options, query) {
    const {
      getSearchExpression,
      maxResults,
      onFilter,
      ignoreDiacritics
    } = this.props;
    const filtered = [];

    // Create a regular expression to filter the options.
    const baseQuery = query ? query.trim() : '';
    const normalizedQuery = ignoreDiacritics ? baseQuery.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : baseQuery;
    const expression = getSearchExpression((0,lodash.escapeRegExp)(normalizedQuery));
    const search = expression ? new RegExp(expression, 'i') : /^$/;
    for (let i = 0; i < options.length; i++) {
      const option = options[i];

      // Merge label into keywords
      let {
        keywords = []
      } = option;
      if (typeof option.label === 'string') {
        keywords = [...keywords, option.label];
      }
      const isMatch = keywords.some(keyword => {
        const normalizedKeyword = ignoreDiacritics ? keyword.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : keyword;
        return search.test(normalizedKeyword);
      });
      if (!isMatch) {
        continue;
      }
      filtered.push(option);

      // Abort early if max reached
      if (maxResults && filtered.length === maxResults) {
        break;
      }
    }
    return onFilter(filtered, query);
  }
  setExpanded(value) {
    this.setState({
      isExpanded: value
    });
  }
  search(query) {
    const cacheSearchOptions = this.cacheSearchOptions || [];
    const searchOptions = query !== null && !query.length && !this.props.hideBeforeSearch ? cacheSearchOptions : this.getOptionsByQuery(cacheSearchOptions, query);
    this.setState({
      query,
      isFocused: true,
      searchOptions,
      selectedIndex: query && query?.length > 0 ? null : this.state.selectedIndex // Only reset selectedIndex if we're actually searching.
    }, () => {
      this.setState({
        isExpanded: Boolean(this.getOptions()?.length)
      });
    });
    this.updateSearchOptions(query);
  }
  updateSearchOptions(query) {
    const {
      hideBeforeSearch,
      options,
      onSearch
    } = this.props;
    const promise = this.activePromise = Promise.resolve(onSearch(options, query)).then(promiseOptions => {
      if (promise !== this.activePromise) {
        // Another promise has become active since this one was asked to resolve, so do nothing,
        // or else we might end triggering a race condition updating the state.
        return;
      }
      this.cacheSearchOptions = promiseOptions;

      // Get all options if `hideBeforeSearch` is enabled and query is not null.
      const searchOptions = query !== null && !query.length && !hideBeforeSearch ? promiseOptions : this.getOptionsByQuery(promiseOptions, query);
      this.setState({
        searchOptions,
        selectedIndex: query && query?.length > 0 ? null : this.state.selectedIndex // Only reset selectedIndex if we're actually searching.
      }, () => {
        this.setState({
          isExpanded: Boolean(this.getOptions().length)
        });
        this.announce(searchOptions);
      });
    });
  }
  onAutofillChange(event) {
    const {
      options
    } = this.props;
    const searchOptions = this.getOptionsByQuery(options, event.target.value);
    if (searchOptions.length === 1) {
      this.selectOption(searchOptions[0]);
    }
  }
  render() {
    const {
      autofill,
      children,
      className,
      disabled,
      controlClassName,
      inlineTags,
      instanceId,
      isSearchable,
      options,
      virtualScroll,
      virtualItemHeight,
      virtualListHeight
    } = this.props;
    const {
      isExpanded,
      isFocused,
      selectedIndex
    } = this.state;
    const hasMultiple = this.hasMultiple();
    const hasTags = this.hasTags();
    const {
      key: selectedKey = ''
    } = (0,lodash.isNumber)(selectedIndex) && options[selectedIndex] || {};
    const listboxId = isExpanded ? `woocommerce-select-control__listbox-${instanceId}` : undefined;
    const activeId = isExpanded ? `woocommerce-select-control__option-${instanceId}-${selectedKey}` : undefined;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('woocommerce-select-control', className, {
        'has-inline-tags': hasTags && inlineTags,
        'is-focused': isFocused,
        'is-searchable': isSearchable
      }),
      ref: this.bindNode,
      children: [autofill && /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
        onChange: this.onAutofillChange,
        name: autofill,
        type: "text",
        className: "woocommerce-select-control__autofill-input",
        tabIndex: -1
      }), children, /*#__PURE__*/(0,jsx_runtime.jsx)(control, {
        help: this.props.help,
        label: this.props.label,
        inlineTags: inlineTags,
        isSearchable: isSearchable,
        isFocused: isFocused,
        instanceId: instanceId,
        searchInputType: this.props.searchInputType,
        query: this.state.query,
        placeholder: this.props.placeholder,
        autoComplete: this.props.autoComplete,
        multiple: this.props.multiple,
        ariaLabel: this.props.ariaLabel,
        onBlur: this.props.onBlur,
        showAllOnFocus: this.props.showAllOnFocus,
        activeId: activeId,
        className: controlClassName,
        disabled: disabled,
        hasTags: hasTags,
        isExpanded: isExpanded,
        listboxId: listboxId,
        onSearch: this.search,
        selected: this.getSelected(),
        onChange: this.setNewValue,
        setExpanded: this.setExpanded,
        updateSearchOptions: this.updateSearchOptions,
        decrementSelectedIndex: this.decrementSelectedIndex,
        incrementSelectedIndex: this.incrementSelectedIndex,
        showClearButton: this.props.showClearButton
      }), !inlineTags && hasMultiple && /*#__PURE__*/(0,jsx_runtime.jsx)(tags, {
        onChange: this.props.onChange,
        showClearButton: this.props.showClearButton,
        selected: this.getSelected()
      }), isExpanded && /*#__PURE__*/(0,jsx_runtime.jsx)(list, {
        instanceId: instanceId,
        selectedIndex: selectedIndex,
        staticList: this.props.staticList,
        listboxId: listboxId,
        node: this.node,
        onSelect: this.selectOption,
        onSearch: this.search,
        options: this.getOptions(),
        decrementSelectedIndex: this.decrementSelectedIndex,
        incrementSelectedIndex: this.incrementSelectedIndex,
        setExpanded: this.setExpanded,
        virtualScroll: virtualScroll,
        virtualItemHeight: virtualItemHeight,
        virtualListHeight: virtualListHeight
      })]
    });
  }
}
/* harmony default export */ const select_control = ((0,compose/* default */.A)(with_spoken_messages/* default */.A, with_instance_id/* default */.A, with_focus_outside/* default */.A // this MUST be the innermost HOC as it calls handleFocusOutside
)(SelectControl));
try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "A search box which filters options while typing,\nallowing a user to select from an option from a filtered list.", "displayName": "SelectControl", "props": { "autofill": { "defaultValue": null, "description": "Name to use for the autofill field, not used if no string is passed.", "name": "autofill", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed before the `Control` of this component.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "controlClassName": { "defaultValue": null, "description": "Class name applied to control wrapper.", "name": "controlClassName", "required": false, "type": { "name": "string" } }, "ignoreDiacritics": { "defaultValue": { value: "false" }, "description": "Whether to ignore diacritics when matching search queries.\nIf true, both the user\u2019s query and all option keywords are normalised to their base characters.", "name": "ignoreDiacritics", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": null, "description": "Allow the select options to be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "excludeSelectedOptions": { "defaultValue": { value: "true" }, "description": "Exclude already selected options from the options list.", "name": "excludeSelectedOptions", "required": false, "type": { "name": "boolean" } }, "onFilter": { "defaultValue": null, "description": "Add or remove items to the list of options after filtering,\npassed the array of filtered options and should return an array of options.", "name": "onFilter", "required": false, "type": { "name": "((options: Option[], query: string | null) => Option[])" } }, "getSearchExpression": { "defaultValue": null, "description": "Function to add regex expression to the filter the results, passed the search query.", "name": "getSearchExpression", "required": false, "type": { "name": "((query: string) => string | RegExp | null)" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": { value: "false" }, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((selected: string | Option[], query?: string | null) => void)" } }, "onSearch": { "defaultValue": { value: "( options: Option[] ) => Promise.resolve( options )" }, "description": "Function run after search query is updated, passed previousOptions and query,\nshould return a promise with an array of updated options.", "name": "onSearch", "required": false, "type": { "name": "((previousOptions: Option[], query: string | null) => Promise<Option[]>)" } }, "options": { "defaultValue": null, "description": "An array of objects for the options list.  The option along with its key, label and\nvalue will be returned in the onChange event.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "searchDebounceTime": { "defaultValue": { value: "0" }, "description": "Time in milliseconds to debounce the search function after typing.", "name": "searchDebounceTime", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "maxResults": { "defaultValue": { value: "0" }, "description": "A limit for the number of results shown in the options menu.  Set to 0 for no limit.", "name": "maxResults", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": { value: "false" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": { value: "search" }, "description": "The input type for the search box control.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "hideBeforeSearch": { "defaultValue": { value: "false" }, "description": "Only show list options after typing a search query.", "name": "hideBeforeSearch", "required": false, "type": { "name": "boolean" } }, "showAllOnFocus": { "defaultValue": { value: "false" }, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "staticList": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticList", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": { value: "off" }, "description": "autocomplete prop for the Control input field.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "Instance ID for the component.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "debouncedSpeak": { "defaultValue": null, "description": "From withSpokenMessages", "name": "debouncedSpeak", "required": false, "type": { "name": "((message: string, assertive?: string) => void)" } }, "ariaLabel": { "defaultValue": null, "description": "aria-label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "On Blur callback.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "virtualScroll": { "defaultValue": { value: "false" }, "description": "Enable virtual scrolling for large lists of options.", "name": "virtualScroll", "required": false, "type": { "name": "boolean" } }, "virtualItemHeight": { "defaultValue": { value: "35" }, "description": "Height in pixels for each virtual item. Required when virtualScroll is true.", "name": "virtualItemHeight", "required": false, "type": { "name": "number" } }, "virtualListHeight": { "defaultValue": { value: "300" }, "description": "Maximum height in pixels for the virtualized list. Default is 300.", "name": "virtualListHeight", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/index.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/select-control/index.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    selectcontrol.displayName = "selectcontrol";
    // @ts-ignore
    selectcontrol.__docgenInfo = { "description": "", "displayName": "selectcontrol", "props": { "autofill": { "defaultValue": null, "description": "Name to use for the autofill field, not used if no string is passed.", "name": "autofill", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed before the `Control` of this component.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "controlClassName": { "defaultValue": null, "description": "Class name applied to control wrapper.", "name": "controlClassName", "required": false, "type": { "name": "string" } }, "ignoreDiacritics": { "defaultValue": null, "description": "Whether to ignore diacritics when matching search queries.\nIf true, both the user\u2019s query and all option keywords are normalised to their base characters.", "name": "ignoreDiacritics", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": null, "description": "Allow the select options to be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "excludeSelectedOptions": { "defaultValue": null, "description": "Exclude already selected options from the options list.", "name": "excludeSelectedOptions", "required": false, "type": { "name": "boolean" } }, "onFilter": { "defaultValue": null, "description": "Add or remove items to the list of options after filtering,\npassed the array of filtered options and should return an array of options.", "name": "onFilter", "required": false, "type": { "name": "((options: Option[], query: string | null) => Option[])" } }, "getSearchExpression": { "defaultValue": null, "description": "Function to add regex expression to the filter the results, passed the search query.", "name": "getSearchExpression", "required": false, "type": { "name": "((query: string) => string | RegExp | null)" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "inlineTags": { "defaultValue": null, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": null, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((selected: string | Option[], query?: string | null) => void)" } }, "onSearch": { "defaultValue": null, "description": "Function run after search query is updated, passed previousOptions and query,\nshould return a promise with an array of updated options.", "name": "onSearch", "required": false, "type": { "name": "((previousOptions: Option[], query: string | null) => Promise<Option[]>)" } }, "options": { "defaultValue": null, "description": "An array of objects for the options list.  The option along with its key, label and\nvalue will be returned in the onChange event.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "searchDebounceTime": { "defaultValue": null, "description": "Time in milliseconds to debounce the search function after typing.", "name": "searchDebounceTime", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "maxResults": { "defaultValue": null, "description": "A limit for the number of results shown in the options menu.  Set to 0 for no limit.", "name": "maxResults", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": null, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": null, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": null, "description": "The input type for the search box control.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "hideBeforeSearch": { "defaultValue": null, "description": "Only show list options after typing a search query.", "name": "hideBeforeSearch", "required": false, "type": { "name": "boolean" } }, "showAllOnFocus": { "defaultValue": null, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "staticList": { "defaultValue": null, "description": "Render results list positioned statically instead of absolutely.", "name": "staticList", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": null, "description": "autocomplete prop for the Control input field.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "Instance ID for the component.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "debouncedSpeak": { "defaultValue": null, "description": "From withSpokenMessages", "name": "debouncedSpeak", "required": false, "type": { "name": "((message: string, assertive?: string) => void)" } }, "ariaLabel": { "defaultValue": null, "description": "aria-label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "On Blur callback.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "virtualScroll": { "defaultValue": null, "description": "Enable virtual scrolling for large lists of options.", "name": "virtualScroll", "required": false, "type": { "name": "boolean" } }, "virtualItemHeight": { "defaultValue": null, "description": "Height in pixels for each virtual item. Required when virtualScroll is true.", "name": "virtualItemHeight", "required": false, "type": { "name": "number" } }, "virtualListHeight": { "defaultValue": null, "description": "Maximum height in pixels for the virtualized list. Default is 300.", "name": "virtualListHeight", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/index.tsx#selectcontrol"] = { docgenInfo: selectcontrol.__docgenInfo, name: "selectcontrol", path: "../../packages/js/components/src/select-control/index.tsx#selectcontrol" };
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