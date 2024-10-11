"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7111],{

/***/ "../../packages/js/components/src/select-control/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ select_control)
});

// UNUSED EXPORTS: SelectControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js
var inherits = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js
var es_array_find_index = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js
var es_string_search = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js
var es_string_trim = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js
var with_spoken_messages = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js
var with_focus_outside = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var keycodes_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/list.tsx













function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function _isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;
  try {
    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */
/**
 * A list box that displays filtered options after search.
 */
var List = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(List, _Component);
  var _super = _createSuper(List);
  function List(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, List);
    _this = _super.call(this, props);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "optionRefs", void 0);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "listbox", void 0);
    _this.handleKeyDown = _this.handleKeyDown.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.select = _this.select.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.optionRefs = {};
    _this.listbox = (0,react.createRef)();
    return _this;
  }
  (0,createClass/* default */.A)(List, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
        options = _this$props.options,
        selectedIndex = _this$props.selectedIndex;

      // Remove old option refs to avoid memory leaks.
      if (!(0,lodash.isEqual)(options, prevProps.options)) {
        this.optionRefs = {};
      }
      if (selectedIndex !== prevProps.selectedIndex && (0,lodash.isNumber)(selectedIndex)) {
        this.scrollToOption(selectedIndex);
      }
    }
  }, {
    key: "getOptionRef",
    value: function getOptionRef(index) {
      if (!this.optionRefs.hasOwnProperty(index)) {
        this.optionRefs[index] = (0,react.createRef)();
      }
      return this.optionRefs[index];
    }
  }, {
    key: "select",
    value: function select(option) {
      var onSelect = this.props.onSelect;
      if (option.isDisabled) {
        return;
      }
      onSelect(option);
    }
  }, {
    key: "scrollToOption",
    value: function scrollToOption(index) {
      var listbox = this.listbox.current;
      if (!listbox) {
        return;
      }
      if (listbox.scrollHeight <= listbox.clientHeight) {
        return;
      }
      if (!this.optionRefs[index]) {
        return;
      }
      var option = this.optionRefs[index].current;
      if (!option) {
        // eslint-disable-next-line no-console
        console.warn('Option not found, index:', index);
        return;
      }
      var scrollBottom = listbox.clientHeight + listbox.scrollTop;
      var elementBottom = option.offsetTop + option.offsetHeight;
      if (elementBottom > scrollBottom) {
        listbox.scrollTop = elementBottom - listbox.clientHeight;
      } else if (option.offsetTop < listbox.scrollTop) {
        listbox.scrollTop = option.offsetTop;
      }
    }
  }, {
    key: "handleKeyDown",
    value: function handleKeyDown(event) {
      var _this$props2 = this.props,
        decrementSelectedIndex = _this$props2.decrementSelectedIndex,
        incrementSelectedIndex = _this$props2.incrementSelectedIndex,
        options = _this$props2.options,
        onSearch = _this$props2.onSearch,
        selectedIndex = _this$props2.selectedIndex,
        setExpanded = _this$props2.setExpanded;
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
  }, {
    key: "toggleKeyEvents",
    value: function toggleKeyEvents(isListening) {
      var node = this.props.node;
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
      var handler = isListening ? 'addEventListener' : 'removeEventListener';
      node[handler]('keydown', this.handleKeyDown, true);
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      var selectedIndex = this.props.selectedIndex;
      if ((0,lodash.isNumber)(selectedIndex) && selectedIndex > -1) {
        this.scrollToOption(selectedIndex);
      }
      this.toggleKeyEvents(true);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.toggleKeyEvents(false);
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props3 = this.props,
        instanceId = _this$props3.instanceId,
        listboxId = _this$props3.listboxId,
        options = _this$props3.options,
        selectedIndex = _this$props3.selectedIndex,
        staticList = _this$props3.staticList;
      var listboxClasses = classnames_default()('woocommerce-select-control__listbox', {
        'is-static': staticList
      });
      return (0,react.createElement)("div", {
        ref: this.listbox,
        id: listboxId,
        role: "listbox",
        className: listboxClasses,
        tabIndex: -1
      }, options.map(function (option, index) {
        return (0,react.createElement)(build_module_button/* default */.A, {
          ref: _this2.getOptionRef(index),
          key: option.key,
          id: "woocommerce-select-control__option-".concat(instanceId, "-").concat(option.key),
          role: "option",
          "aria-selected": index === selectedIndex,
          disabled: option.isDisabled,
          className: classnames_default()('woocommerce-select-control__option', {
            'is-selected': index === selectedIndex
          }),
          onClick: function onClick() {
            return _this2.select(option);
          },
          tabIndex: -1
        }, option.label);
      }));
    }
  }]);
  return List;
}(react.Component);
/* harmony default export */ const list = (List);
try {
    // @ts-ignore
    List.displayName = "List";
    // @ts-ignore
    List.__docgenInfo = { "description": "A list box that displays filtered options after search.", "displayName": "List", "props": { "listboxId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "instanceId", "required": true, "type": { "name": "number" } }, "node": { "defaultValue": null, "description": "Parent node to bind keyboard events to.", "name": "node", "required": true, "type": { "name": "HTMLElement | null" } }, "onSelect": { "defaultValue": null, "description": "Function to execute when an option is selected.", "name": "onSelect", "required": true, "type": { "name": "(option: Option) => void" } }, "options": { "defaultValue": null, "description": "Array of options to display.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "selectedIndex": { "defaultValue": null, "description": "Integer for the currently selected item.", "name": "selectedIndex", "required": true, "type": { "name": "number | null | undefined" } }, "staticList": { "defaultValue": null, "description": "Bool to determine if the list should be positioned absolutely or statically.", "name": "staticList", "required": true, "type": { "name": "boolean" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "onSearch": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "onSearch", "required": true, "type": { "name": "(option: string | null) => void" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the list should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/list.tsx#List"] = { docgenInfo: List.__docgenInfo, name: "List", path: "../../packages/js/components/src/select-control/list.tsx#List" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js
var cancel_circle_filled = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js");
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/tags.tsx














function tags_createSuper(Derived) {
  var hasNativeReflectConstruct = tags_isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function tags_isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;
  try {
    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */

/**
 * A list of tags to display selected items.
 */
var Tags = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(Tags, _Component);
  var _super = tags_createSuper(Tags);
  function Tags(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, Tags);
    _this = _super.call(this, props);
    _this.removeAll = _this.removeAll.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.removeResult = _this.removeResult.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(Tags, [{
    key: "removeAll",
    value: function removeAll() {
      var onChange = this.props.onChange;
      onChange([]);
    }
  }, {
    key: "removeResult",
    value: function removeResult(key) {
      var _this2 = this;
      return function () {
        var _this2$props = _this2.props,
          selected = _this2$props.selected,
          onChange = _this2$props.onChange;
        if (!(0,lodash.isArray)(selected)) {
          return;
        }
        var i = (0,lodash.findIndex)(selected, {
          key: key
        });
        onChange([].concat((0,toConsumableArray/* default */.A)(selected.slice(0, i)), (0,toConsumableArray/* default */.A)(selected.slice(i + 1))));
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;
      var _this$props = this.props,
        selected = _this$props.selected,
        showClearButton = _this$props.showClearButton;
      if (!(0,lodash.isArray)(selected) || !selected.length) {
        return null;
      }
      return (0,react.createElement)(react.Fragment, null, (0,react.createElement)("div", {
        className: "woocommerce-select-control__tags"
      }, selected.map(function (item, i) {
        if (!item.label) {
          return null;
        }
        var screenReaderLabel = (0,build_module/* sprintf */.nv)( /* translators: %1$s: tag label, %2$s: tag number, %3$s: total number of tags */
        (0,build_module.__)('%1$s (%2$s of %3$s)', 'woocommerce'), item.label, i + 1, selected.length);
        return (0,react.createElement)(tag/* default */.A, {
          key: item.key,
          id: item.key,
          label: item.label
          // @ts-expect-error key is a string or undefined here
          ,

          remove: _this3.removeResult,
          screenReaderLabel: screenReaderLabel
        });
      })), showClearButton && (0,react.createElement)(build_module_button/* default */.A, {
        className: "woocommerce-select-control__clear",
        isLink: true,
        onClick: this.removeAll
      }, (0,react.createElement)(icon/* default */.A, {
        icon: cancel_circle_filled/* default */.A,
        className: "clear-icon"
      }), (0,react.createElement)("span", {
        className: "screen-reader-text"
      }, (0,build_module.__)('Clear all', 'woocommerce'))));
    }
  }]);
  return Tags;
}(react.Component);
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/search.js
var search = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/search.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/control.tsx













function control_createSuper(Derived) {
  var hasNativeReflectConstruct = control_isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function control_isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;
  try {
    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */

/**
 * A search control to allow user input to filter the options.
 */
var Control = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(Control, _Component);
  var _super = control_createSuper(Control);
  function Control(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, Control);
    _this = _super.call(this, props);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "input", void 0);
    _this.state = {
      isActive: false
    };
    _this.input = (0,react.createRef)();
    _this.updateSearch = _this.updateSearch.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onFocus = _this.onFocus.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onBlur = _this.onBlur.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onKeyDown = _this.onKeyDown.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(Control, [{
    key: "updateSearch",
    value: function updateSearch(onSearch) {
      return function (event) {
        onSearch(event.target.value);
      };
    }
  }, {
    key: "onFocus",
    value: function onFocus(onSearch) {
      var _this2 = this;
      var _this$props = this.props,
        isSearchable = _this$props.isSearchable,
        setExpanded = _this$props.setExpanded,
        showAllOnFocus = _this$props.showAllOnFocus,
        updateSearchOptions = _this$props.updateSearchOptions;
      return function (event) {
        _this2.setState({
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
  }, {
    key: "onBlur",
    value: function onBlur() {
      var onBlur = this.props.onBlur;
      if (typeof onBlur === 'function') {
        onBlur();
      }
      this.setState({
        isActive: false
      });
    }
  }, {
    key: "onKeyDown",
    value: function onKeyDown(event) {
      var _this$props2 = this.props,
        decrementSelectedIndex = _this$props2.decrementSelectedIndex,
        incrementSelectedIndex = _this$props2.incrementSelectedIndex,
        selected = _this$props2.selected,
        onChange = _this$props2.onChange,
        query = _this$props2.query,
        setExpanded = _this$props2.setExpanded;
      if (keycodes_build_module/* BACKSPACE */.G_ === event.keyCode && !query && (0,lodash.isArray)(selected) && selected.length) {
        onChange((0,toConsumableArray/* default */.A)(selected.slice(0, -1)));
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
  }, {
    key: "renderButton",
    value: function renderButton() {
      var _this$props3 = this.props,
        multiple = _this$props3.multiple,
        selected = _this$props3.selected;
      if (multiple || !(0,lodash.isArray)(selected) || !selected.length) {
        return null;
      }
      return (0,react.createElement)("div", {
        className: "woocommerce-select-control__control-value"
      }, selected[0].label);
    }
  }, {
    key: "renderInput",
    value: function renderInput() {
      var _this$props$ariaLabel;
      var _this$props4 = this.props,
        activeId = _this$props4.activeId,
        disabled = _this$props4.disabled,
        hasTags = _this$props4.hasTags,
        inlineTags = _this$props4.inlineTags,
        instanceId = _this$props4.instanceId,
        isExpanded = _this$props4.isExpanded,
        isSearchable = _this$props4.isSearchable,
        listboxId = _this$props4.listboxId,
        onSearch = _this$props4.onSearch,
        placeholder = _this$props4.placeholder,
        searchInputType = _this$props4.searchInputType,
        autoComplete = _this$props4.autoComplete;
      var isActive = this.state.isActive;
      return (0,react.createElement)("input", {
        autoComplete: autoComplete || 'off',
        className: "woocommerce-select-control__control-input",
        id: "woocommerce-select-control-".concat(instanceId, "__control-input"),
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
        "aria-describedby": hasTags && inlineTags ? "search-inline-input-".concat(instanceId) : undefined,
        disabled: disabled,
        "aria-label": (_this$props$ariaLabel = this.props.ariaLabel) !== null && _this$props$ariaLabel !== void 0 ? _this$props$ariaLabel : this.props.label
      });
    }
  }, {
    key: "getInputValue",
    value: function getInputValue() {
      var _this$props5 = this.props,
        inlineTags = _this$props5.inlineTags,
        isFocused = _this$props5.isFocused,
        isSearchable = _this$props5.isSearchable,
        multiple = _this$props5.multiple,
        query = _this$props5.query,
        selected = _this$props5.selected;
      var selectedValue = (0,lodash.isArray)(selected) && selected.length ? selected[0].label : '';

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
  }, {
    key: "render",
    value: function render() {
      var _this$getInputValue,
        _this3 = this;
      var _this$props6 = this.props,
        className = _this$props6.className,
        disabled = _this$props6.disabled,
        hasTags = _this$props6.hasTags,
        help = _this$props6.help,
        inlineTags = _this$props6.inlineTags,
        instanceId = _this$props6.instanceId,
        isSearchable = _this$props6.isSearchable,
        label = _this$props6.label,
        query = _this$props6.query,
        onChange = _this$props6.onChange,
        showClearButton = _this$props6.showClearButton;
      var isActive = this.state.isActive;
      return (
        // Disable reason: The div below visually simulates an input field. Its
        // child input is the actual input and responds accordingly to all keyboard
        // events, but click events need to be passed onto the child input. There
        // is no appropriate aria role for describing this situation, which is only
        // for the benefit of sighted users.
        /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
        (0,react.createElement)("div", {
          className: classnames_default()('components-base-control', 'woocommerce-select-control__control', className, {
            empty: !query || query.length === 0,
            'is-active': isActive,
            'has-tags': inlineTags && hasTags,
            'with-value': (_this$getInputValue = this.getInputValue()) === null || _this$getInputValue === void 0 ? void 0 : _this$getInputValue.length,
            'has-error': !!help,
            'is-disabled': disabled
          }),
          onClick: function onClick(event) {
            // Don't focus the input if the click event is from the error message.
            if (
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore - event.target.className is not in the type definition.
            event.target.className !== 'components-base-control__help' && _this3.input.current) {
              _this3.input.current.focus();
            }
          }
        }, isSearchable && (0,react.createElement)(icon/* default */.A, {
          className: "woocommerce-select-control__control-icon",
          icon: search/* default */.A
        }), inlineTags && (0,react.createElement)(tags, {
          onChange: onChange,
          showClearButton: showClearButton,
          selected: this.props.selected
        }), (0,react.createElement)("div", {
          className: "components-base-control__field"
        }, !!label && (0,react.createElement)("label", {
          htmlFor: "woocommerce-select-control-".concat(instanceId, "__control-input"),
          className: "components-base-control__label"
        }, label), this.renderInput(), inlineTags && (0,react.createElement)("span", {
          id: "search-inline-input-".concat(instanceId),
          className: "screen-reader-text"
        }, (0,build_module.__)('Move backward for selected items', 'woocommerce')), !!help && (0,react.createElement)("p", {
          id: "woocommerce-select-control-".concat(instanceId, "__help"),
          className: "components-base-control__help"
        }, help)))
        /* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
      );
    }
  }]);
  return Control;
}(react.Component);
/* harmony default export */ const control = (Control);
try {
    // @ts-ignore
    Control.displayName = "Control";
    // @ts-ignore
    Control.__docgenInfo = { "description": "A search control to allow user input to filter the options.", "displayName": "Control", "props": { "hasTags": { "defaultValue": null, "description": "Bool to determine if tags should be rendered.", "name": "hasTags", "required": false, "type": { "name": "boolean" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "string | Element" } }, "inlineTags": { "defaultValue": null, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": null, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "instanceId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "listboxId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "Function called when the input is blurred.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": true, "type": { "name": "(selected: Option[]) => void" } }, "onSearch": { "defaultValue": null, "description": "Function called when input field is changed or focused.", "name": "onSearch", "required": true, "type": { "name": "(query: string) => void" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "query": { "defaultValue": null, "description": "Search query entered by user.", "name": "query", "required": false, "type": { "name": "string | null" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values. If the label of the selected\nvalue is omitted, the Tag of that value will not be rendered inside the\nsearch box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "showAllOnFocus": { "defaultValue": null, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": null, "description": "Control input autocomplete field, defaults: off.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the control should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } }, "updateSearchOptions": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "updateSearchOptions", "required": true, "type": { "name": "(query: string) => void" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "multiple": { "defaultValue": null, "description": "Multi-select mode allows multiple options to be selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "isFocused": { "defaultValue": null, "description": "Is the control currently focused.", "name": "isFocused", "required": false, "type": { "name": "boolean" } }, "activeId": { "defaultValue": null, "description": "ID for accessibility purposes. aria-activedescendant will be set to this value.", "name": "activeId", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "Disable the control.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "isExpanded": { "defaultValue": null, "description": "Is the control currently expanded. This is for accessibility purposes.", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": null, "description": "The type of input to use for the search field.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "ariaLabel": { "defaultValue": null, "description": "The aria label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Class name to be added to the input.", "name": "className", "required": false, "type": { "name": "string" } }, "showClearButton": { "defaultValue": null, "description": "Show the clear button.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/control.tsx#Control"] = { docgenInfo: Control.__docgenInfo, name: "Control", path: "../../packages/js/components/src/select-control/control.tsx#Control" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/index.tsx



































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
function select_control_createSuper(Derived) {
  var hasNativeReflectConstruct = select_control_isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function select_control_isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;
  try {
    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */




var initialState = {
  isExpanded: false,
  isFocused: false,
  query: '',
  searchOptions: []
};

/**
 * A search box which filters options while typing,
 * allowing a user to select from an option from a filtered list.
 */
var SelectControl = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(SelectControl, _Component);
  var _super = select_control_createSuper(SelectControl);
  function SelectControl(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, SelectControl);
    _this = _super.call(this, props);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "node", null);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "activePromise", null);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "cacheSearchOptions", []);
    var selected = props.selected,
      options = props.options,
      excludeSelectedOptions = props.excludeSelectedOptions;
    _this.state = _objectSpread(_objectSpread({}, initialState), {}, {
      searchOptions: [],
      selectedIndex: selected && options !== null && options !== void 0 && options.length && !excludeSelectedOptions ? options.findIndex(function (option) {
        return option.key === selected;
      }) : null
    });
    _this.bindNode = _this.bindNode.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.decrementSelectedIndex = _this.decrementSelectedIndex.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.incrementSelectedIndex = _this.incrementSelectedIndex.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onAutofillChange = _this.onAutofillChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateSearchOptions = (0,lodash.debounce)(_this.updateSearchOptions.bind((0,assertThisInitialized/* default */.A)(_this)), props.searchDebounceTime);
    _this.search = _this.search.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.selectOption = _this.selectOption.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.setExpanded = _this.setExpanded.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.setNewValue = _this.setNewValue.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(SelectControl, [{
    key: "bindNode",
    value: function bindNode(node) {
      this.node = node;
    }
  }, {
    key: "reset",
    value: function reset() {
      var selected = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.getSelected();
      var _this$props = this.props,
        multiple = _this$props.multiple,
        excludeSelectedOptions = _this$props.excludeSelectedOptions;
      var newState = _objectSpread({}, initialState);
      // Reset selectedIndex if single selection.
      if (!multiple && (0,lodash.isArray)(selected) && selected.length && selected[0].key) {
        newState.selectedIndex = !excludeSelectedOptions ? this.props.options.findIndex(function (i) {
          return i.key === selected[0].key;
        }) : null;
      }
      this.setState(newState);
    }
  }, {
    key: "handleFocusOutside",
    value: function handleFocusOutside() {
      this.reset();
    }
  }, {
    key: "hasMultiple",
    value: function hasMultiple() {
      var _this$props2 = this.props,
        multiple = _this$props2.multiple,
        selected = _this$props2.selected;
      if (!multiple) {
        return false;
      }
      if (Array.isArray(selected)) {
        return selected.some(function (item) {
          return Boolean(item.label);
        });
      }
      return Boolean(selected);
    }
  }, {
    key: "getSelected",
    value: function getSelected() {
      var _this$props3 = this.props,
        multiple = _this$props3.multiple,
        options = _this$props3.options,
        selected = _this$props3.selected;

      // Return the passed value if an array is provided.
      if (multiple || Array.isArray(selected)) {
        return selected;
      }
      var selectedOption = options.find(function (option) {
        return option.key === selected;
      });
      return selectedOption ? [selectedOption] : [];
    }
  }, {
    key: "selectOption",
    value: function selectOption(option) {
      var _this$props4 = this.props,
        multiple = _this$props4.multiple,
        selected = _this$props4.selected;
      var newSelected = multiple && (0,lodash.isArray)(selected) ? [].concat((0,toConsumableArray/* default */.A)(selected), [option]) : [option];
      this.reset(newSelected);
      var oldSelected = Array.isArray(selected) ? selected : [{
        key: selected
      }];
      var isSelected = oldSelected.findIndex(function (val) {
        return val.key === option.key;
      });
      if (isSelected === -1) {
        this.setNewValue(newSelected);
      }

      // After selecting option, the list will reset and we'd need to correct selectedIndex.
      var newSelectedIndex = this.props.excludeSelectedOptions ?
      // Since we're excluding the selected option, invalidate selection
      // so re-focusing wont immediately set it to the neighbouring option.
      null : this.getOptions().findIndex(function (i) {
        return i.key === option.key;
      });
      this.setState({
        selectedIndex: newSelectedIndex
      });
    }
  }, {
    key: "setNewValue",
    value: function setNewValue(newValue) {
      var _this$props5 = this.props,
        onChange = _this$props5.onChange,
        selected = _this$props5.selected,
        multiple = _this$props5.multiple;
      var query = this.state.query;
      // Trigger a change if the selected value is different and pass back
      // an array or string depending on the original value.
      if (multiple || Array.isArray(selected)) {
        onChange(newValue, query);
      } else {
        onChange(newValue.length > 0 ? newValue[0].key : '', query);
      }
    }
  }, {
    key: "decrementSelectedIndex",
    value: function decrementSelectedIndex() {
      var selectedIndex = this.state.selectedIndex;
      var options = this.getOptions();
      var nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex === 0 ? options.length : selectedIndex) - 1 : options.length - 1;
      this.setState({
        selectedIndex: nextSelectedIndex
      });
    }
  }, {
    key: "incrementSelectedIndex",
    value: function incrementSelectedIndex() {
      var selectedIndex = this.state.selectedIndex;
      var options = this.getOptions();
      var nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex + 1) % options.length : 0;
      this.setState({
        selectedIndex: nextSelectedIndex
      });
    }
  }, {
    key: "announce",
    value: function announce(searchOptions) {
      var debouncedSpeak = this.props.debouncedSpeak;
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
  }, {
    key: "getOptions",
    value: function getOptions() {
      var _this$props6 = this.props,
        isSearchable = _this$props6.isSearchable,
        options = _this$props6.options,
        excludeSelectedOptions = _this$props6.excludeSelectedOptions;
      var searchOptions = this.state.searchOptions;
      var selected = this.getSelected();
      var selectedKeys = (0,lodash.isArray)(selected) ? selected.map(function (option) {
        return option.key;
      }) : [];
      var shownOptions = isSearchable ? searchOptions : options;
      if (excludeSelectedOptions) {
        return shownOptions === null || shownOptions === void 0 ? void 0 : shownOptions.filter(function (option) {
          return !selectedKeys.includes(option.key);
        });
      }
      return shownOptions;
    }
  }, {
    key: "getOptionsByQuery",
    value: function getOptionsByQuery(options, query) {
      var _this$props7 = this.props,
        getSearchExpression = _this$props7.getSearchExpression,
        maxResults = _this$props7.maxResults,
        onFilter = _this$props7.onFilter;
      var filtered = [];

      // Create a regular expression to filter the options.
      var expression = getSearchExpression((0,lodash.escapeRegExp)(query ? query.trim() : ''));
      var search = expression ? new RegExp(expression, 'i') : /^$/;
      for (var i = 0; i < options.length; i++) {
        var option = options[i];

        // Merge label into keywords
        var _option$keywords = option.keywords,
          keywords = _option$keywords === void 0 ? [] : _option$keywords;
        if (typeof option.label === 'string') {
          keywords = [].concat((0,toConsumableArray/* default */.A)(keywords), [option.label]);
        }
        var isMatch = keywords.some(function (keyword) {
          return search.test(keyword);
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
  }, {
    key: "setExpanded",
    value: function setExpanded(value) {
      this.setState({
        isExpanded: value
      });
    }
  }, {
    key: "search",
    value: function search(query) {
      var _this2 = this;
      var cacheSearchOptions = this.cacheSearchOptions || [];
      var searchOptions = query !== null && !query.length && !this.props.hideBeforeSearch ? cacheSearchOptions : this.getOptionsByQuery(cacheSearchOptions, query);
      this.setState({
        query: query,
        isFocused: true,
        searchOptions: searchOptions,
        selectedIndex: query && (query === null || query === void 0 ? void 0 : query.length) > 0 ? null : this.state.selectedIndex // Only reset selectedIndex if we're actually searching.
      }, function () {
        var _this2$getOptions;
        _this2.setState({
          isExpanded: Boolean((_this2$getOptions = _this2.getOptions()) === null || _this2$getOptions === void 0 ? void 0 : _this2$getOptions.length)
        });
      });
      this.updateSearchOptions(query);
    }
  }, {
    key: "updateSearchOptions",
    value: function updateSearchOptions(query) {
      var _this3 = this;
      var _this$props8 = this.props,
        hideBeforeSearch = _this$props8.hideBeforeSearch,
        options = _this$props8.options,
        onSearch = _this$props8.onSearch;
      var promise = this.activePromise = Promise.resolve(onSearch(options, query)).then(function (promiseOptions) {
        if (promise !== _this3.activePromise) {
          // Another promise has become active since this one was asked to resolve, so do nothing,
          // or else we might end triggering a race condition updating the state.
          return;
        }
        _this3.cacheSearchOptions = promiseOptions;

        // Get all options if `hideBeforeSearch` is enabled and query is not null.
        var searchOptions = query !== null && !query.length && !hideBeforeSearch ? promiseOptions : _this3.getOptionsByQuery(promiseOptions, query);
        _this3.setState({
          searchOptions: searchOptions,
          selectedIndex: query && (query === null || query === void 0 ? void 0 : query.length) > 0 ? null : _this3.state.selectedIndex // Only reset selectedIndex if we're actually searching.
        }, function () {
          _this3.setState({
            isExpanded: Boolean(_this3.getOptions().length)
          });
          _this3.announce(searchOptions);
        });
      });
    }
  }, {
    key: "onAutofillChange",
    value: function onAutofillChange(event) {
      var options = this.props.options;
      var searchOptions = this.getOptionsByQuery(options, event.target.value);
      if (searchOptions.length === 1) {
        this.selectOption(searchOptions[0]);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props9 = this.props,
        autofill = _this$props9.autofill,
        children = _this$props9.children,
        className = _this$props9.className,
        disabled = _this$props9.disabled,
        controlClassName = _this$props9.controlClassName,
        inlineTags = _this$props9.inlineTags,
        instanceId = _this$props9.instanceId,
        isSearchable = _this$props9.isSearchable,
        options = _this$props9.options;
      var _this$state = this.state,
        isExpanded = _this$state.isExpanded,
        isFocused = _this$state.isFocused,
        selectedIndex = _this$state.selectedIndex;
      var hasMultiple = this.hasMultiple();
      var _ref = (0,lodash.isNumber)(selectedIndex) && options[selectedIndex] || {},
        _ref$key = _ref.key,
        selectedKey = _ref$key === void 0 ? '' : _ref$key;
      var listboxId = isExpanded ? "woocommerce-select-control__listbox-".concat(instanceId) : undefined;
      var activeId = isExpanded ? "woocommerce-select-control__option-".concat(instanceId, "-").concat(selectedKey) : undefined;
      return (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-select-control', className, {
          'has-inline-tags': hasMultiple && inlineTags,
          'is-focused': isFocused,
          'is-searchable': isSearchable
        }),
        ref: this.bindNode
      }, autofill && (0,react.createElement)("input", {
        onChange: this.onAutofillChange,
        name: autofill,
        type: "text",
        className: "woocommerce-select-control__autofill-input",
        tabIndex: -1
      }), children, (0,react.createElement)(control, {
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
        hasTags: hasMultiple,
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
      }), !inlineTags && hasMultiple && (0,react.createElement)(tags, {
        onChange: this.props.onChange,
        showClearButton: this.props.showClearButton,
        selected: this.getSelected()
      }), isExpanded && (0,react.createElement)(list, {
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
        setExpanded: this.setExpanded
      }));
    }
  }]);
  return SelectControl;
}(react.Component);
(0,defineProperty/* default */.A)(SelectControl, "defaultProps", {
  excludeSelectedOptions: true,
  getSearchExpression: lodash.identity,
  inlineTags: false,
  isSearchable: false,
  onChange: lodash.noop,
  onFilter: lodash.identity,
  onSearch: function onSearch(options) {
    return Promise.resolve(options);
  },
  maxResults: 0,
  multiple: false,
  searchDebounceTime: 0,
  searchInputType: 'search',
  selected: [],
  showAllOnFocus: false,
  showClearButton: false,
  hideBeforeSearch: false,
  staticList: false,
  autoComplete: 'off'
});
/* harmony default export */ const select_control = ((0,compose/* default */.A)(with_spoken_messages/* default */.A, with_instance_id/* default */.A, with_focus_outside/* default */.A // this MUST be the innermost HOC as it calls handleFocusOutside
)(SelectControl));
try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "A search box which filters options while typing,\nallowing a user to select from an option from a filtered list.", "displayName": "SelectControl", "props": { "autofill": { "defaultValue": null, "description": "Name to use for the autofill field, not used if no string is passed.", "name": "autofill", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed before the `Control` of this component.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "controlClassName": { "defaultValue": null, "description": "Class name applied to control wrapper.", "name": "controlClassName", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "Allow the select options to be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "excludeSelectedOptions": { "defaultValue": { value: "true" }, "description": "Exclude already selected options from the options list.", "name": "excludeSelectedOptions", "required": false, "type": { "name": "boolean" } }, "onFilter": { "defaultValue": null, "description": "Add or remove items to the list of options after filtering,\npassed the array of filtered options and should return an array of options.", "name": "onFilter", "required": false, "type": { "name": "((options: Option[], query: string | null) => Option[])" } }, "getSearchExpression": { "defaultValue": null, "description": "Function to add regex expression to the filter the results, passed the search query.", "name": "getSearchExpression", "required": false, "type": { "name": "((query: string) => RegExp)" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "string | Element" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": { value: "false" }, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((selected: string | Option[], query?: string | null) => void)" } }, "onSearch": { "defaultValue": { value: "( options: Option[] ) => Promise.resolve( options )" }, "description": "Function run after search query is updated, passed previousOptions and query,\nshould return a promise with an array of updated options.", "name": "onSearch", "required": false, "type": { "name": "((previousOptions: Option[], query: string | null) => Promise<Option[]>)" } }, "options": { "defaultValue": null, "description": "An array of objects for the options list.  The option along with its key, label and\nvalue will be returned in the onChange event.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "searchDebounceTime": { "defaultValue": { value: "0" }, "description": "Time in milliseconds to debounce the search function after typing.", "name": "searchDebounceTime", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "maxResults": { "defaultValue": { value: "0" }, "description": "A limit for the number of results shown in the options menu.  Set to 0 for no limit.", "name": "maxResults", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": { value: "false" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": { value: "search" }, "description": "The input type for the search box control.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "hideBeforeSearch": { "defaultValue": { value: "false" }, "description": "Only show list options after typing a search query.", "name": "hideBeforeSearch", "required": false, "type": { "name": "boolean" } }, "showAllOnFocus": { "defaultValue": { value: "false" }, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "staticList": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticList", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": { value: "off" }, "description": "autocomplete prop for the Control input field.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "Instance ID for the component.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "debouncedSpeak": { "defaultValue": null, "description": "From withSpokenMessages", "name": "debouncedSpeak", "required": false, "type": { "name": "((message: string, assertive?: string) => void)" } }, "ariaLabel": { "defaultValue": null, "description": "aria-label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "On Blur callback.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/index.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/select-control/index.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/tag/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/close-small.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");


/**
 * External dependencies
 */







var Tag = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(function (_ref, removeButtonRef) {
  var id = _ref.id,
    label = _ref.label,
    popoverContents = _ref.popoverContents,
    remove = _ref.remove,
    screenReaderLabel = _ref.screenReaderLabel,
    className = _ref.className;
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(_useState, 2),
    isVisible = _useState2[0],
    setIsVisible = _useState2[1];
  var instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(Tag);
  screenReaderLabel = screenReaderLabel || label;
  if (!label) {
    // A null label probably means something went wrong
    // @todo Maybe this should be a loading indicator?
    return null;
  }
  label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__/* .decodeEntities */ .S)(label);
  var classes = classnames__WEBPACK_IMPORTED_MODULE_1___default()('woocommerce-tag', className, {
    'has-remove': !!remove
  });
  var labelId = "woocommerce-tag__label-".concat(instanceId);
  var labelTextNode = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: "screen-reader-text"
  }, screenReaderLabel), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    "aria-hidden": "true"
  }, label));
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: classes
  }, popoverContents ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    className: "woocommerce-tag__text",
    id: labelId,
    onClick: function onClick() {
      return setIsVisible(true);
    }
  }, labelTextNode) : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: "woocommerce-tag__text",
    id: labelId
  }, labelTextNode), popoverContents && isVisible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
    onClose: function onClose() {
      return setIsVisible(false);
    }
  }, popoverContents), remove && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    className: "woocommerce-tag__remove",
    ref: removeButtonRef,
    onClick: remove(id),
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(
    // translators: %s is the name of the tag being removed.
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Remove %s', 'woocommerce'), label),
    "aria-describedby": labelId
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A,
    size: 20,
    className: "clear-icon"
  })));
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