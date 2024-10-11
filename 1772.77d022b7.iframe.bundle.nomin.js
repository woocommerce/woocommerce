"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1772],{

/***/ "../../packages/js/components/src/experimental-select-control/combo-box.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ ComboBox)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");


/**
 * External dependencies
 */



var ToggleButton = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(function (props, ref) {
  // using forwardRef here because getToggleButtonProps injects a ref prop
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)({
    className: "woocommerce-experimental-select-control__combox-box-toggle-button"
  }, props, {
    ref: ref
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A
  }));
});
var ComboBox = function ComboBox(_ref) {
  var children = _ref.children,
    comboBoxProps = _ref.comboBoxProps,
    _ref$getToggleButtonP = _ref.getToggleButtonProps,
    getToggleButtonProps = _ref$getToggleButtonP === void 0 ? function () {
      return {};
    } : _ref$getToggleButtonP,
    inputProps = _ref.inputProps,
    suffix = _ref.suffix,
    showToggleButton = _ref.showToggleButton;
  var inputRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  var maybeFocusInput = function maybeFocusInput(event) {
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
    // Disable reason: The click event is purely for accidental clicks around the input.
    // Keyboard users are still able to tab to and interact with elements in the combobox.
    /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: classnames__WEBPACK_IMPORTED_MODULE_1___default()('woocommerce-experimental-select-control__combo-box-wrapper', {
        'woocommerce-experimental-select-control__combo-box-wrapper--disabled': inputProps.disabled
      }),
      onMouseDown: maybeFocusInput
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "woocommerce-experimental-select-control__items-wrapper"
    }, children, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)({}, comboBoxProps, {
      className: "woocommerce-experimental-select-control__combox-box"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)({}, inputProps, {
      ref: function ref(node) {
        inputRef.current = node;
        if (typeof inputProps.ref === 'function') {
          inputProps.ref(node);
        }
      }
    })))), suffix && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "woocommerce-experimental-select-control__suffix"
    }, suffix), showToggleButton && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToggleButton, getToggleButtonProps()))
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

/***/ "../../packages/js/components/src/experimental-select-control/menu-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   D: () => (/* binding */ MenuItem)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js");


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var MenuItem = function MenuItem(_ref) {
  var children = _ref.children,
    getItemProps = _ref.getItemProps,
    index = _ref.index,
    isActive = _ref.isActive,
    _ref$activeStyle = _ref.activeStyle,
    activeStyle = _ref$activeStyle === void 0 ? {
      backgroundColor: '#bde4ff'
    } : _ref$activeStyle,
    item = _ref.item,
    tooltipText = _ref.tooltipText,
    className = _ref.className;
  function renderListItem() {
    var itemProps = getItemProps({
      item: item,
      index: index
    });
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("li", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)({}, itemProps, {
      style: isActive ? activeStyle : itemProps.style,
      className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-experimental-select-control__menu-item', itemProps.className, className)
    }), children);
  }
  if (tooltipText) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
      text: tooltipText,
      position: "top center"
    }, renderListItem());
  }
  return renderListItem();
};
try {
    // @ts-ignore
    MenuItem.displayName = "MenuItem";
    // @ts-ignore
    MenuItem.__docgenInfo = { "description": "", "displayName": "MenuItem", "props": { "index": { "defaultValue": null, "description": "", "name": "index", "required": true, "type": { "name": "number" } }, "isActive": { "defaultValue": null, "description": "", "name": "isActive", "required": true, "type": { "name": "boolean" } }, "item": { "defaultValue": null, "description": "", "name": "item", "required": true, "type": { "name": "ItemType" } }, "getItemProps": { "defaultValue": null, "description": "", "name": "getItemProps", "required": true, "type": { "name": "getItemPropsType<ItemType>" } }, "activeStyle": { "defaultValue": { value: "{ backgroundColor: '#bde4ff' }" }, "description": "", "name": "activeStyle", "required": false, "type": { "name": "CSSProperties" } }, "tooltipText": { "defaultValue": null, "description": "", "name": "tooltipText", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/menu-item.tsx#MenuItem"] = { docgenInfo: MenuItem.__docgenInfo, name: "MenuItem", path: "../../packages/js/components/src/experimental-select-control/menu-item.tsx#MenuItem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/menu.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   W: () => (/* binding */ Menu),
/* harmony export */   c: () => (/* binding */ MenuSlot)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var Menu = function Menu(_ref) {
  var _selectControlMenuRef2;
  var children = _ref.children,
    getMenuProps = _ref.getMenuProps,
    isOpen = _ref.isOpen,
    className = _ref.className,
    _ref$position = _ref.position,
    position = _ref$position === void 0 ? 'bottom right' : _ref$position,
    _ref$scrollIntoViewOn = _ref.scrollIntoViewOnOpen,
    scrollIntoViewOnOpen = _ref$scrollIntoViewOn === void 0 ? false : _ref$scrollIntoViewOn;
  var selectControlMenuRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  var popoverRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useLayoutEffect)(function () {
    var _selectControlMenuRef, _popoverRef$current;
    var comboboxWrapper = (_selectControlMenuRef = selectControlMenuRef.current) === null || _selectControlMenuRef === void 0 ? void 0 : _selectControlMenuRef.closest('.woocommerce-experimental-select-control__combo-box-wrapper');
    var popoverContent = (_popoverRef$current = popoverRef.current) === null || _popoverRef$current === void 0 ? void 0 : _popoverRef$current.querySelector('.components-popover__content');
    if (comboboxWrapper && (comboboxWrapper === null || comboboxWrapper === void 0 ? void 0 : comboboxWrapper.clientWidth) > 0) {
      if (popoverContent) {
        popoverContent.style.width = "".concat(comboboxWrapper.getBoundingClientRect().width, "px");
      }
    }
  }, [selectControlMenuRef.current, (_selectControlMenuRef2 = selectControlMenuRef.current) === null || _selectControlMenuRef2 === void 0 ? void 0 : _selectControlMenuRef2.clientWidth, popoverRef.current]);

  // Scroll the selected item into view when the menu opens.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
    if (isOpen && scrollIntoViewOnOpen) {
      var _selectControlMenuRef3;
      (_selectControlMenuRef3 = selectControlMenuRef.current) === null || _selectControlMenuRef3 === void 0 || _selectControlMenuRef3.scrollIntoView();
    }
  }, [isOpen, scrollIntoViewOnOpen]);

  /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  /* Disabled because of the onmouseup on the ul element below. */
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    ref: selectControlMenuRef,
    className: "woocommerce-experimental-select-control__menu"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A
  // @ts-expect-error this prop does exist, see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L180.
  , {
    __unstableSlotName: "woocommerce-select-control-menu",
    focusOnMount: false,
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-experimental-select-control__popover-menu', {
      'is-open': isOpen,
      'has-results': _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Children.count(children) > 0
    }),
    position: position,
    animate: false,
    resize: false,
    ref: popoverRef
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("ul", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)({}, getMenuProps(), {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-experimental-select-control__popover-menu-container', className),
    onMouseUp: function onMouseUp(e) {
      return (
        // Fix to prevent select control dropdown from closing when selecting within the Popover.
        e.stopPropagation()
      );
    }
  }), isOpen && children))));
  /* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
};
var MenuSlot = function MenuSlot() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createPortal)((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    "aria-live": "off"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A.Slot, {
    name: "woocommerce-select-control-menu"
  })), document.body);
};
try {
    // @ts-ignore
    Menu.displayName = "Menu";
    // @ts-ignore
    Menu.__docgenInfo = { "description": "", "displayName": "Menu", "props": { "getMenuProps": { "defaultValue": null, "description": "", "name": "getMenuProps", "required": true, "type": { "name": "getMenuPropsType" } }, "isOpen": { "defaultValue": null, "description": "", "name": "isOpen", "required": true, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "position": { "defaultValue": { value: "bottom right" }, "description": "", "name": "position", "required": false, "type": { "name": "enum", "value": [{ "value": "\"top left\"" }, { "value": "\"top right\"" }, { "value": "\"top center\"" }, { "value": "\"middle left\"" }, { "value": "\"middle right\"" }, { "value": "\"middle center\"" }, { "value": "\"bottom left\"" }, { "value": "\"bottom right\"" }, { "value": "\"bottom center\"" }] } }, "scrollIntoViewOnOpen": { "defaultValue": { value: "false" }, "description": "", "name": "scrollIntoViewOnOpen", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/menu.tsx#Menu"] = { docgenInfo: Menu.__docgenInfo, name: "Menu", path: "../../packages/js/components/src/experimental-select-control/menu.tsx#Menu" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    MenuSlot.displayName = "MenuSlot";
    // @ts-ignore
    MenuSlot.__docgenInfo = { "description": "", "displayName": "MenuSlot", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/menu.tsx#MenuSlot"] = { docgenInfo: MenuSlot.__docgenInfo, name: "MenuSlot", path: "../../packages/js/components/src/experimental-select-control/menu.tsx#MenuSlot" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/select-control.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Y: () => (/* binding */ SelectControl),
  U: () => (/* binding */ selectControlStateChangeTypes)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/downshift@6.1.12_react@17.0.2/node_modules/downshift/dist/downshift.esm.js + 1 modules
var downshift_esm = __webpack_require__("../../node_modules/.pnpm/downshift@6.1.12_react@17.0.2/node_modules/downshift/dist/downshift.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/selected-items.tsx
var selected_items = __webpack_require__("../../packages/js/components/src/experimental-select-control/selected-items.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/combo-box.tsx
var combo_box = __webpack_require__("../../packages/js/components/src/experimental-select-control/combo-box.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu.tsx
var menu = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu-item.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/suffix-icon.tsx
var suffix_icon = __webpack_require__("../../packages/js/components/src/experimental-select-control/suffix-icon.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-select-control/utils.ts







/**
 * Internal dependencies
 */

function isDefaultItemType(item) {
  return Boolean(item) && item.label !== undefined && item.value !== undefined;
}
var defaultGetItemLabel = function defaultGetItemLabel(item) {
  if (isDefaultItemType(item)) {
    return item.label;
  }
  return '';
};
var defaultGetItemValue = function defaultGetItemValue(item) {
  if (isDefaultItemType(item)) {
    return item.value;
  }
  return '';
};
var defaultGetFilteredItems = function defaultGetFilteredItems(allItems, inputValue, selectedItems, getItemLabel) {
  var escapedInputValue = inputValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  var re = new RegExp(escapedInputValue, 'gi');
  return allItems.filter(function (item) {
    return selectedItems.indexOf(item) < 0 && re.test(getItemLabel(item).toLowerCase());
  });
};
;// CONCATENATED MODULE: ../../packages/js/components/src/experimental-select-control/select-control.tsx




var _excluded = ["inputValue"];

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







var selectControlStateChangeTypes = downshift_esm/* useCombobox */.Bp.stateChangeTypes;
function SelectControl(_ref) {
  var _ref$getItemLabel = _ref.getItemLabel,
    getItemLabel = _ref$getItemLabel === void 0 ? defaultGetItemLabel : _ref$getItemLabel,
    _ref$getItemValue = _ref.getItemValue,
    getItemValue = _ref$getItemValue === void 0 ? defaultGetItemValue : _ref$getItemValue,
    _ref$hasExternalTags = _ref.hasExternalTags,
    hasExternalTags = _ref$hasExternalTags === void 0 ? false : _ref$hasExternalTags,
    _ref$children = _ref.children,
    children = _ref$children === void 0 ? function (_ref2) {
      var renderItems = _ref2.items,
        highlightedIndex = _ref2.highlightedIndex,
        getItemProps = _ref2.getItemProps,
        getMenuProps = _ref2.getMenuProps,
        isOpen = _ref2.isOpen;
      return (0,react.createElement)(menu/* Menu */.W, {
        getMenuProps: getMenuProps,
        isOpen: isOpen
      }, renderItems.map(function (item, index) {
        return (0,react.createElement)(menu_item/* MenuItem */.D, {
          key: "".concat(getItemValue(item)).concat(index),
          index: index,
          isActive: highlightedIndex === index,
          item: item,
          getItemProps: getItemProps
        }, getItemLabel(item));
      }));
    } : _ref$children,
    _ref$multiple = _ref.multiple,
    multiple = _ref$multiple === void 0 ? false : _ref$multiple,
    items = _ref.items,
    label = _ref.label,
    _ref$getFilteredItems = _ref.getFilteredItems,
    getFilteredItems = _ref$getFilteredItems === void 0 ? defaultGetFilteredItems : _ref$getFilteredItems,
    _ref$onInputChange = _ref.onInputChange,
    onInputChange = _ref$onInputChange === void 0 ? function () {
      return null;
    } : _ref$onInputChange,
    _ref$onRemove = _ref.onRemove,
    onRemove = _ref$onRemove === void 0 ? function () {
      return null;
    } : _ref$onRemove,
    _ref$onSelect = _ref.onSelect,
    onSelect = _ref$onSelect === void 0 ? function () {
      return null;
    } : _ref$onSelect,
    _ref$onFocus = _ref.onFocus,
    _onFocus = _ref$onFocus === void 0 ? function () {
      return null;
    } : _ref$onFocus,
    _ref$onKeyDown = _ref.onKeyDown,
    onKeyDown = _ref$onKeyDown === void 0 ? function () {
      return null;
    } : _ref$onKeyDown,
    _ref$stateReducer = _ref.stateReducer,
    _stateReducer = _ref$stateReducer === void 0 ? function (state, actionAndChanges) {
      return actionAndChanges.changes;
    } : _ref$stateReducer,
    placeholder = _ref.placeholder,
    selected = _ref.selected,
    className = _ref.className,
    disabled = _ref.disabled,
    _ref$inputProps = _ref.inputProps,
    inputProps = _ref$inputProps === void 0 ? {} : _ref$inputProps,
    _ref$suffix = _ref.suffix,
    suffix = _ref$suffix === void 0 ? (0,react.createElement)(suffix_icon/* SuffixIcon */.f, {
      icon: chevron_down/* default */.A
    }) : _ref$suffix,
    _ref$showToggleButton = _ref.showToggleButton,
    showToggleButton = _ref$showToggleButton === void 0 ? false : _ref$showToggleButton,
    _ref$readOnlyWhenClos = _ref.readOnlyWhenClosed,
    readOnlyWhenClosed = _ref$readOnlyWhenClos === void 0 ? true : _ref$readOnlyWhenClos,
    _ref$__experimentalOp = _ref.__experimentalOpenMenuOnFocus,
    __experimentalOpenMenuOnFocus = _ref$__experimentalOp === void 0 ? false : _ref$__experimentalOp;
  var _useState = (0,react.useState)(false),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    isFocused = _useState2[0],
    setIsFocused = _useState2[1];
  var _useState3 = (0,react.useState)(''),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    inputValue = _useState4[0],
    setInputValue = _useState4[1];
  var instanceId = (0,use_instance_id/* default */.A)(SelectControl, 'woocommerce-experimental-select-control');
  var innerInputClassName = 'woocommerce-experimental-select-control__input';
  var selectControlWrapperRef = (0,react.useRef)(null);
  var selectedItems = selected === null ? [] : selected;
  selectedItems = Array.isArray(selectedItems) ? selectedItems : [selectedItems].filter(Boolean);
  var singleSelectedItem = !multiple && selectedItems.length ? selectedItems[0] : null;
  var filteredItems = getFilteredItems(items, inputValue, selectedItems, getItemLabel);
  var _useMultipleSelection = (0,downshift_esm/* useMultipleSelection */.mH)({
      itemToString: getItemLabel,
      selectedItems: selectedItems
    }),
    getSelectedItemProps = _useMultipleSelection.getSelectedItemProps,
    getDropdownProps = _useMultipleSelection.getDropdownProps,
    removeSelectedItem = _useMultipleSelection.removeSelectedItem;
  (0,react.useEffect)(function () {
    if (multiple) {
      return;
    }
    setInputValue(getItemLabel(singleSelectedItem));
  }, [getItemLabel, multiple, singleSelectedItem]);
  var _useCombobox = (0,downshift_esm/* useCombobox */.Bp)({
      id: instanceId,
      initialSelectedItem: singleSelectedItem,
      inputValue: inputValue,
      items: filteredItems,
      selectedItem: multiple ? null : singleSelectedItem,
      itemToString: getItemLabel,
      onSelectedItemChange: function onSelectedItemChange(_ref3) {
        var selectedItem = _ref3.selectedItem;
        if (selectedItem) {
          onSelect(selectedItem);
        } else if (singleSelectedItem) {
          onRemove(singleSelectedItem);
        }
      },
      onInputValueChange: function onInputValueChange(_ref4) {
        var value = _ref4.inputValue,
          changes = (0,objectWithoutProperties/* default */.A)(_ref4, _excluded);
        if (value !== undefined) {
          setInputValue(value);
          onInputChange(value, changes);
        }
      },
      // @ts-expect-error We're allowed to use the property.
      stateReducer: function stateReducer(state, actionAndChanges) {
        var _changes$inputValue, _changes$inputValue2, _newChanges;
        var changes = actionAndChanges.changes,
          type = actionAndChanges.type;
        var newChanges;
        switch (type) {
          case selectControlStateChangeTypes.InputBlur:
            // Set input back to selected item if there is a selected item, blank otherwise.
            newChanges = _objectSpread(_objectSpread({}, changes), {}, {
              selectedItem: !((_changes$inputValue = changes.inputValue) !== null && _changes$inputValue !== void 0 && _changes$inputValue.length) && !multiple ? null : changes.selectedItem,
              inputValue: changes.selectedItem === state.selectedItem && (_changes$inputValue2 = changes.inputValue) !== null && _changes$inputValue2 !== void 0 && _changes$inputValue2.length && !multiple ? getItemLabel(comboboxSingleSelectedItem) : ''
            });
            break;
          case selectControlStateChangeTypes.InputKeyDownEnter:
          case selectControlStateChangeTypes.FunctionSelectItem:
          case selectControlStateChangeTypes.ItemClick:
            if (changes.selectedItem && multiple) {
              newChanges = _objectSpread(_objectSpread({}, changes), {}, {
                inputValue: ''
              });
            }
            break;
          default:
            break;
        }
        return _stateReducer(state, _objectSpread(_objectSpread({}, actionAndChanges), {}, {
          changes: (_newChanges = newChanges) !== null && _newChanges !== void 0 ? _newChanges : changes
        }));
      }
    }),
    isOpen = _useCombobox.isOpen,
    getLabelProps = _useCombobox.getLabelProps,
    getMenuProps = _useCombobox.getMenuProps,
    getToggleButtonProps = _useCombobox.getToggleButtonProps,
    getInputProps = _useCombobox.getInputProps,
    getComboboxProps = _useCombobox.getComboboxProps,
    highlightedIndex = _useCombobox.highlightedIndex,
    getItemProps = _useCombobox.getItemProps,
    selectItem = _useCombobox.selectItem,
    comboboxSingleSelectedItem = _useCombobox.selectedItem,
    openMenu = _useCombobox.openMenu,
    closeMenu = _useCombobox.closeMenu;
  var isEventOutside = function isEventOutside(event) {
    var selectControlWrapperElement = selectControlWrapperRef.current;
    var menuElement = document.getElementById("".concat(instanceId, "-menu"));
    var parentPopoverMenuElement = menuElement === null || menuElement === void 0 ? void 0 : menuElement.closest('.woocommerce-experimental-select-control__popover-menu');
    return !(selectControlWrapperElement !== null && selectControlWrapperElement !== void 0 && selectControlWrapperElement.contains(event.relatedTarget)) && !(parentPopoverMenuElement !== null && parentPopoverMenuElement !== void 0 && parentPopoverMenuElement.contains(event.relatedTarget));
  };
  var onRemoveItem = function onRemoveItem(item) {
    selectItem(null);
    removeSelectedItem(item);
    onRemove(item);
  };
  var isReadOnly = readOnlyWhenClosed && !isOpen && !isFocused;
  var selectedItemTags = multiple ? (0,react.createElement)(selected_items/* SelectedItems */.K, {
    items: selectedItems,
    isReadOnly: isReadOnly,
    getItemLabel: getItemLabel,
    getItemValue: getItemValue,
    getSelectedItemProps: getSelectedItemProps,
    onRemove: onRemoveItem
  }) : null;
  return (0,react.createElement)("div", {
    id: instanceId,
    ref: selectControlWrapperRef,
    className: classnames_default()('woocommerce-experimental-select-control', className, {
      'is-read-only': isReadOnly,
      'is-focused': isFocused,
      'is-multiple': multiple,
      'has-selected-items': selectedItems.length
    })
  }, label && (0,react.createElement)("label", (0,esm_extends/* default */.A)({}, getLabelProps(), {
    className: "woocommerce-experimental-select-control__label"
  }), label), (0,react.createElement)(combo_box/* ComboBox */.a, {
    comboBoxProps: getComboboxProps(),
    getToggleButtonProps: getToggleButtonProps,
    inputProps: getInputProps(_objectSpread(_objectSpread({}, getDropdownProps({
      preventKeyAction: isOpen
    })), {}, {
      className: innerInputClassName,
      onFocus: function onFocus() {
        setIsFocused(true);
        _onFocus({
          inputValue: inputValue
        });
        if (__experimentalOpenMenuOnFocus) {
          openMenu();
        }
      },
      onBlur: function onBlur(event) {
        if (isEventOutside(event)) {
          setIsFocused(false);
        }
      },
      onKeyDown: onKeyDown,
      placeholder: placeholder,
      disabled: disabled
    }, inputProps)),
    suffix: suffix,
    showToggleButton: showToggleButton
  }, (0,react.createElement)(react.Fragment, null, children({
    items: filteredItems,
    highlightedIndex: highlightedIndex,
    getItemProps: getItemProps,
    getMenuProps: getMenuProps,
    isOpen: isOpen,
    getItemLabel: getItemLabel,
    getItemValue: getItemValue,
    selectItem: selectItem,
    setInputValue: setInputValue,
    openMenu: openMenu,
    closeMenu: closeMenu
  }), !hasExternalTags && selectedItemTags)), hasExternalTags && selectedItemTags);
}

try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "", "displayName": "SelectControl", "props": { "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "ItemType[]" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string | Element" } }, "getItemLabel": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.label;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemLabel", "required": false, "type": { "name": "getItemLabelType<ItemType>" } }, "getItemValue": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.value;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemValue", "required": false, "type": { "name": "getItemValueType<ItemType>" } }, "getFilteredItems": { "defaultValue": { value: "< ItemType >(\n\tallItems: ItemType[],\n\tinputValue: string,\n\tselectedItems: ItemType[],\n\tgetItemLabel: getItemLabelType< ItemType >\n) => {\n\tconst escapedInputValue = inputValue.replace(\n\t\t/[.*+?^${}()|[\\]\\\\]/g,\n\t\t'\\\\$&'\n\t);\n\tconst re = new RegExp( escapedInputValue, 'gi' );\n\n\treturn allItems.filter( ( item ) => {\n\t\treturn (\n\t\t\tselectedItems.indexOf( item ) < 0 &&\n\t\t\tre.test( getItemLabel( item ).toLowerCase() )\n\t\t);\n\t} );\n}" }, "description": "", "name": "getFilteredItems", "required": false, "type": { "name": "((allItems: ItemType[], inputValue: string, selectedItems: ItemType[], getItemLabel: getItemLabelType<ItemType>) => ItemType[])" } }, "hasExternalTags": { "defaultValue": { value: "false" }, "description": "", "name": "hasExternalTags", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "false" }, "description": "", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onInputChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onInputChange", "required": false, "type": { "name": "((value: string, changes: Partial<Omit<UseComboboxState<ItemType>, \"inputValue\">>) => void)" } }, "onRemove": { "defaultValue": { value: "() => null" }, "description": "", "name": "onRemove", "required": false, "type": { "name": "((item: ItemType) => void)" } }, "onSelect": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelect", "required": false, "type": { "name": "((selected: ItemType) => void)" } }, "onKeyDown": { "defaultValue": { value: "() => null" }, "description": "", "name": "onKeyDown", "required": false, "type": { "name": "((e: KeyboardEvent) => void)" } }, "onFocus": { "defaultValue": { value: "() => null" }, "description": "", "name": "onFocus", "required": false, "type": { "name": "((data: { inputValue: string; }) => void)" } }, "stateReducer": { "defaultValue": { value: "( state, actionAndChanges ) => actionAndChanges.changes" }, "description": "", "name": "stateReducer", "required": false, "type": { "name": "((state: UseComboboxState<ItemType | null>, actionAndChanges: UseComboboxStateChangeOptions<ItemType | null>) => Partial<...>)" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": null, "description": "", "name": "selected", "required": true, "type": { "name": "ItemType | ItemType[] | null" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "inputProps": { "defaultValue": { value: "{}" }, "description": "", "name": "inputProps", "required": false, "type": { "name": "GetInputPropsOptions" } }, "suffix": { "defaultValue": { value: "<SuffixIcon icon={ chevronDown } />" }, "description": "", "name": "suffix", "required": false, "type": { "name": "Element | null" } }, "showToggleButton": { "defaultValue": { value: "false" }, "description": "", "name": "showToggleButton", "required": false, "type": { "name": "boolean" } }, "readOnlyWhenClosed": { "defaultValue": { value: "true" }, "description": "", "name": "readOnlyWhenClosed", "required": false, "type": { "name": "boolean" } }, "__experimentalOpenMenuOnFocus": { "defaultValue": { value: "false" }, "description": "This is a feature already implemented in downshift@7.0.0 through the\nreducer. In order for us to use it this prop is added temporarily until\ncurrent downshift version get updated.\n@see https://www.downshift-js.com/use-multiple-selection#usage-with-combobox", "name": "__experimentalOpenMenuOnFocus", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/select-control.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/experimental-select-control/select-control.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/selected-items.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   K: () => (/* binding */ SelectedItems)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _tag__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../packages/js/components/src/tag/index.tsx");




/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var PrivateSelectedItems = function PrivateSelectedItems(_ref, ref) {
  var isReadOnly = _ref.isReadOnly,
    items = _ref.items,
    getItemLabel = _ref.getItemLabel,
    getItemValue = _ref.getItemValue,
    getSelectedItemProps = _ref.getSelectedItemProps,
    onRemove = _ref.onRemove,
    onBlur = _ref.onBlur,
    onSelectedItemsEnd = _ref.onSelectedItemsEnd;
  var classes = classnames__WEBPACK_IMPORTED_MODULE_2___default()('woocommerce-experimental-select-control__selected-items', {
    'is-read-only': isReadOnly
  });
  var lastRemoveButtonRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useRef)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useImperativeHandle)(ref, function () {
    return function () {
      var _lastRemoveButtonRef$;
      return (_lastRemoveButtonRef$ = lastRemoveButtonRef.current) === null || _lastRemoveButtonRef$ === void 0 ? void 0 : _lastRemoveButtonRef$.focus();
    };
  }, []);
  if (isReadOnly) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("div", {
      className: classes
    }, items.map(function (item) {
      return (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__/* .decodeEntities */ .S)(getItemLabel(item));
    }).join(', '));
  }
  var focusSibling = function focusSibling(event) {
    var selectedItem = event.target.closest('.woocommerce-experimental-select-control__selected-item');
    var sibling = event.key === 'ArrowLeft' || event.key === 'Backspace' ? selectedItem === null || selectedItem === void 0 ? void 0 : selectedItem.previousSibling : selectedItem === null || selectedItem === void 0 ? void 0 : selectedItem.nextSibling;
    if (sibling) {
      var _querySelector;
      (_querySelector = sibling.querySelector('.woocommerce-tag__remove')) === null || _querySelector === void 0 || _querySelector.focus();
      return true;
    }
    return false;
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("div", {
    className: classes
  }, items.map(function (item, index) {
    return (
      // Disable reason: We prevent the default action to keep the input focused on click.
      // Keyboard users are unaffected by this change.
      /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("div", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)({
        key: "selected-item-".concat(index),
        className: "woocommerce-experimental-select-control__selected-item"
      }, getSelectedItemProps({
        selectedItem: item,
        index: index
      }), {
        onMouseDown: function onMouseDown(event) {
          event.preventDefault();
        },
        onClick: function onClick(event) {
          event.preventDefault();
        },
        onKeyDown: function onKeyDown(event) {
          if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            var focused = focusSibling(event);
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
        onBlur: onBlur
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)(_tag__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
        id: getItemValue(item),
        remove: function remove() {
          return function () {
            return onRemove(item);
          };
        },
        label: getItemLabel(item),
        ref: index === items.length - 1 ? lastRemoveButtonRef : undefined
      }))
    );
  }));
};
var SelectedItems = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.forwardRef)(PrivateSelectedItems);
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
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);

/**
 * External dependencies
 */



var SuffixIcon = function SuffixIcon(_ref) {
  var _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    icon = _ref.icon;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-experimental-select-control__suffix-icon', className)
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    icon: icon,
    size: 24
  }));
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

/***/ })

}]);