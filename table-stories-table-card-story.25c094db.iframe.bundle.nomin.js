"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6933],{

/***/ "../../packages/js/components/src/ellipsis-menu/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var gridicons_dist_ellipsis__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/ellipsis.js");

/**
 * External dependencies
 */






/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */

var EllipsisMenu = function EllipsisMenu(_ref) {
  var label = _ref.label,
    renderContent = _ref.renderContent,
    className = _ref.className,
    onToggle = _ref.onToggle;
  if (!renderContent) {
    return null;
  }
  var renderEllipsis = function renderEllipsis(_ref2) {
    var toggleHandlerOverride = _ref2.onToggle,
      isOpen = _ref2.isOpen;
    var toggleClassname = classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-ellipsis-menu__toggle', {
      'is-opened': isOpen
    });
    return (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
      className: toggleClassname,
      onClick: function onClick(e) {
        if (onToggle) {
          onToggle(e);
        }
        if (toggleHandlerOverride) {
          toggleHandlerOverride();
        }
      },
      title: label,
      "aria-expanded": isOpen
    }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      icon: (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(gridicons_dist_ellipsis__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, null)
    }));
  };
  var renderMenu = function renderMenu(renderContentArgs) {
    return (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
      className: "woocommerce-ellipsis-menu__content"
    }, renderContent(renderContentArgs));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()(className, 'woocommerce-ellipsis-menu')
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    contentClassName: "woocommerce-ellipsis-menu__popover"
    // @ts-expect-error missing prop in types.
    ,

    popoverProps: {
      placement: 'bottom'
    },
    renderToggle: renderEllipsis,
    renderContent: renderMenu
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EllipsisMenu);
try {
    // @ts-ignore
    ellipsismenu.displayName = "ellipsismenu";
    // @ts-ignore
    ellipsismenu.__docgenInfo = { "description": "This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.", "displayName": "ellipsismenu", "props": { "label": { "defaultValue": null, "description": "The label shown when hovering/focusing on the icon button.", "name": "label", "required": true, "type": { "name": "string" } }, "renderContent": { "defaultValue": null, "description": "A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments.", "name": "renderContent", "required": false, "type": { "name": "((props: CallbackProps) => Element | ReactNode)" } }, "className": { "defaultValue": null, "description": "Classname to add to ellipsis menu.", "name": "className", "required": false, "type": { "name": "string" } }, "onToggle": { "defaultValue": null, "description": "Callback function when dropdown button is clicked, it provides the click event.", "name": "onToggle", "required": false, "type": { "name": "((e: MouseEvent<Element, MouseEvent> | KeyboardEvent<Element>) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/ellipsis-menu/index.tsx#ellipsismenu"] = { docgenInfo: ellipsismenu.__docgenInfo, name: "ellipsismenu", path: "../../packages/js/components/src/ellipsis-menu/index.tsx#ellipsismenu" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/ellipsis-menu/menu-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/form-toggle/index.js");
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js");

/**
 * External dependencies
 */



var MenuItem = function MenuItem(_ref) {
  var checked = _ref.checked,
    children = _ref.children,
    _ref$isCheckbox = _ref.isCheckbox,
    isCheckbox = _ref$isCheckbox === void 0 ? false : _ref$isCheckbox,
    _ref$isClickable = _ref.isClickable,
    isClickable = _ref$isClickable === void 0 ? false : _ref$isClickable,
    _ref$onInvoke = _ref.onInvoke,
    onInvoke = _ref$onInvoke === void 0 ? function () {} : _ref$onInvoke;
  var container = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  var onClick = function onClick(event) {
    if (isClickable) {
      event.preventDefault();
      onInvoke();
    }
  };
  var onKeyDown = function onKeyDown(event) {
    var eventTarget = event.target;
    if (eventTarget.isSameNode(event.currentTarget)) {
      if (event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_1__/* .ENTER */ .Fm || event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_1__/* .SPACE */ .t6) {
        event.preventDefault();
        onInvoke();
      }
      if (event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_1__.UP) {
        event.preventDefault();
      }
      if (event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_1__/* .DOWN */ .PX) {
        var _eventTarget$parentNo;
        event.preventDefault();
        var nextElementToFocus = eventTarget.nextSibling || ((_eventTarget$parentNo = eventTarget.parentNode) === null || _eventTarget$parentNo === void 0 ? void 0 : _eventTarget$parentNo.querySelector('.woocommerce-ellipsis-menu__item'));
        nextElementToFocus.focus();
      }
    }
  };
  if (isCheckbox) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      "aria-checked": checked,
      ref: container,
      role: "menuitemcheckbox",
      tabIndex: 0,
      onKeyDown: onKeyDown,
      onClick: onClick,
      className: "woocommerce-ellipsis-menu__item"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
      className: "components-toggle-control"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
      "aria-hidden": "true",
      checked: checked,
      onChange: onInvoke,
      onClick: function onClick(e) {
        return e.stopPropagation();
      },
      tabIndex: -1
    }), children));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    role: "menuitem",
    tabIndex: 0,
    onKeyDown: onKeyDown,
    onClick: onClick,
    className: "woocommerce-ellipsis-menu__item"
  }, children);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MenuItem);
try {
    // @ts-ignore
    menuitem.displayName = "menuitem";
    // @ts-ignore
    menuitem.__docgenInfo = { "description": "", "displayName": "menuitem", "props": { "checked": { "defaultValue": null, "description": "Whether the menu item is checked or not. Only relevant for menu items with `isCheckbox`.", "name": "checked", "required": false, "type": { "name": "boolean" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "isCheckbox": { "defaultValue": { value: "false" }, "description": "Whether the menu item is a checkbox (will render a FormToggle and use the `menuitemcheckbox` role).", "name": "isCheckbox", "required": false, "type": { "name": "boolean" } }, "isClickable": { "defaultValue": { value: "false" }, "description": "Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component\nhandles the click event.", "name": "isClickable", "required": false, "type": { "name": "boolean" } }, "onInvoke": { "defaultValue": { value: "() => {}" }, "description": "A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked\n(only if `isClickable` is set).", "name": "onInvoke", "required": false, "type": { "name": "(() => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/ellipsis-menu/menu-item.tsx#menuitem"] = { docgenInfo: menuitem.__docgenInfo, name: "menuitem", path: "../../packages/js/components/src/ellipsis-menu/menu-item.tsx#menuitem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/ellipsis-menu/menu-title.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

/**
 * External dependencies
 */



/**
 * `MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
 * (so this should not be used in place of the `EllipsisMenu` prop `label`).
 */

var MenuTitle = function MenuTitle(_ref) {
  var children = _ref.children;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "woocommerce-ellipsis-menu__title"
  }, children);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MenuTitle);
try {
    // @ts-ignore
    menutitle.displayName = "menutitle";
    // @ts-ignore
    menutitle.__docgenInfo = { "description": "`MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated\n(so this should not be used in place of the `EllipsisMenu` prop `label`).", "displayName": "menutitle", "props": { "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed as the content of this item.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/ellipsis-menu/menu-title.tsx#menutitle"] = { docgenInfo: menutitle.__docgenInfo, name: "menutitle", path: "../../packages/js/components/src/ellipsis-menu/menu-title.tsx#menutitle" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/pagination/page-size-picker.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $: () => (/* binding */ PageSizePicker),
/* harmony export */   v: () => (/* binding */ DEFAULT_PER_PAGE_OPTIONS)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
/* harmony import */ var core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_parse_int_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");






/**
 * External dependencies
 */


var DEFAULT_PER_PAGE_OPTIONS = [25, 50, 75, 100];
function PageSizePicker(_ref) {
  var perPage = _ref.perPage,
    currentPage = _ref.currentPage,
    total = _ref.total,
    setCurrentPage = _ref.setCurrentPage,
    _ref$setPerPageChange = _ref.setPerPageChange,
    setPerPageChange = _ref$setPerPageChange === void 0 ? function () {} : _ref$setPerPageChange,
    _ref$perPageOptions = _ref.perPageOptions,
    perPageOptions = _ref$perPageOptions === void 0 ? DEFAULT_PER_PAGE_OPTIONS : _ref$perPageOptions,
    _ref$label = _ref.label,
    label = _ref$label === void 0 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Rows per page', 'woocommerce') : _ref$label;
  function perPageChange(newPerPage) {
    setPerPageChange(parseInt(newPerPage, 10));
    var newMaxPage = Math.ceil(total / parseInt(newPerPage, 10));
    if (currentPage > newMaxPage) {
      setCurrentPage(newMaxPage);
    }
  }

  // @todo Replace this with a styleized Select drop-down/control?
  var pickerOptions = perPageOptions.map(function (option) {
    return {
      value: option.toString(),
      label: option.toString()
    };
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_6__.createElement)("div", {
    className: "woocommerce-pagination__per-page-picker"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_6__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
    label: label
    // @ts-expect-error outdated types file.
    ,

    labelPosition: "side",
    value: perPage.toString(),
    onChange: perPageChange,
    options: pickerOptions
  }));
}
try {
    // @ts-ignore
    PageSizePicker.displayName = "PageSizePicker";
    // @ts-ignore
    PageSizePicker.__docgenInfo = { "description": "", "displayName": "PageSizePicker", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "perPage": { "defaultValue": null, "description": "", "name": "perPage", "required": true, "type": { "name": "number" } }, "total": { "defaultValue": null, "description": "", "name": "total", "required": true, "type": { "name": "number" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"previous\" | \"next\" | \"goto\" | undefined) => void" } }, "setPerPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "setPerPageChange", "required": false, "type": { "name": "((perPage: number) => void)" } }, "perPageOptions": { "defaultValue": { value: "[ 25, 50, 75, 100 ]" }, "description": "", "name": "perPageOptions", "required": false, "type": { "name": "number[]" } }, "label": { "defaultValue": { value: "__( 'Rows per page', 'woocommerce' )" }, "description": "", "name": "label", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-size-picker.tsx#PageSizePicker"] = { docgenInfo: PageSizePicker.__docgenInfo, name: "PageSizePicker", path: "../../packages/js/components/src/pagination/page-size-picker.tsx#PageSizePicker" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/pagination/pagination.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  d: () => (/* binding */ Pagination)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-left.js
var chevron_left = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-right.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/pagination/page-arrows.tsx

/**
 * External dependencies
 */




function PageArrows(_ref) {
  var pageCount = _ref.pageCount,
    currentPage = _ref.currentPage,
    _ref$showPageArrowsLa = _ref.showPageArrowsLabel,
    showPageArrowsLabel = _ref$showPageArrowsLa === void 0 ? true : _ref$showPageArrowsLa,
    setCurrentPage = _ref.setCurrentPage;
  function previousPage(event) {
    event.stopPropagation();
    if (currentPage - 1 < 1) {
      return;
    }
    setCurrentPage(currentPage - 1, 'previous');
  }
  function nextPage(event) {
    event.stopPropagation();
    if (currentPage + 1 > pageCount) {
      return;
    }
    setCurrentPage(currentPage + 1, 'next');
  }
  if (pageCount <= 1) {
    return null;
  }
  var previousLinkClass = classnames_default()('woocommerce-pagination__link', {
    'is-active': currentPage > 1
  });
  var nextLinkClass = classnames_default()('woocommerce-pagination__link', {
    'is-active': currentPage < pageCount
  });
  return (0,react.createElement)("div", {
    className: "woocommerce-pagination__page-arrows"
  }, showPageArrowsLabel && (0,react.createElement)("span", {
    className: "woocommerce-pagination__page-arrows-label",
    role: "status",
    "aria-live": "polite"
  }, (0,build_module/* sprintf */.nv)( /* translators: 1: current page number, 2: total number of pages */
  (0,build_module.__)('Page %1$d of %2$d', 'woocommerce'), currentPage, pageCount)), (0,react.createElement)("div", {
    className: "woocommerce-pagination__page-arrows-buttons"
  }, (0,react.createElement)(build_module_button/* default */.A, {
    className: previousLinkClass,
    disabled: !(currentPage > 1),
    onClick: previousPage,
    label: (0,build_module.__)('Previous Page', 'woocommerce')
  }, (0,react.createElement)(icon/* default */.A, {
    icon: chevron_left/* default */.A
  })), (0,react.createElement)(build_module_button/* default */.A, {
    className: nextLinkClass,
    disabled: !(currentPage < pageCount),
    onClick: nextPage,
    label: (0,build_module.__)('Next Page', 'woocommerce')
  }, (0,react.createElement)(icon/* default */.A, {
    icon: chevron_right/* default */.A
  }))));
}
try {
    // @ts-ignore
    PageArrows.displayName = "PageArrows";
    // @ts-ignore
    PageArrows.__docgenInfo = { "description": "", "displayName": "PageArrows", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "pageCount": { "defaultValue": null, "description": "", "name": "pageCount", "required": true, "type": { "name": "number" } }, "showPageArrowsLabel": { "defaultValue": { value: "true" }, "description": "", "name": "showPageArrowsLabel", "required": false, "type": { "name": "boolean" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"previous\" | \"next\" | \"goto\" | undefined) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-arrows.tsx#PageArrows"] = { docgenInfo: PageArrows.__docgenInfo, name: "PageArrows", path: "../../packages/js/components/src/pagination/page-arrows.tsx#PageArrows" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js
var es_parse_int = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-finite.js
var es_number_is_finite = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-finite.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js
var es_number_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/pagination/page-picker.tsx





/**
 * External dependencies
 */




function PagePicker(_ref) {
  var pageCount = _ref.pageCount,
    currentPage = _ref.currentPage,
    setCurrentPage = _ref.setCurrentPage;
  var _useState = (0,react.useState)(currentPage),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    inputValue = _useState2[0],
    setInputValue = _useState2[1];
  function onInputChange(event) {
    setInputValue(parseInt(event.currentTarget.value, 10));
  }
  function onInputBlur(event) {
    var newPage = parseInt(event.target.value, 10);
    if (newPage !== currentPage && Number.isFinite(newPage) && newPage > 0 && pageCount && pageCount >= newPage) {
      setCurrentPage(newPage, 'goto');
    }
  }
  function selectInputValue(event) {
    event.currentTarget.select();
  }
  var isError = currentPage < 1 || currentPage > pageCount;
  var inputClass = classnames_default()('woocommerce-pagination__page-picker-input', {
    'has-error': isError
  });
  var instanceId = (0,lodash.uniqueId)('woocommerce-pagination-page-picker-');
  return (0,react.createElement)("div", {
    className: "woocommerce-pagination__page-picker"
  }, (0,react.createElement)("label", {
    htmlFor: instanceId,
    className: "woocommerce-pagination__page-picker-label"
  }, (0,build_module.__)('Go to page', 'woocommerce'), (0,react.createElement)("input", {
    id: instanceId,
    className: inputClass,
    "aria-invalid": isError,
    type: "number",
    onClick: selectInputValue,
    onChange: onInputChange,
    onBlur: onInputBlur,
    value: inputValue,
    min: 1,
    max: pageCount
  })));
}
try {
    // @ts-ignore
    PagePicker.displayName = "PagePicker";
    // @ts-ignore
    PagePicker.__docgenInfo = { "description": "", "displayName": "PagePicker", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "pageCount": { "defaultValue": null, "description": "", "name": "pageCount", "required": true, "type": { "name": "number" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"previous\" | \"next\" | \"goto\" | undefined) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-picker.tsx#PagePicker"] = { docgenInfo: PagePicker.__docgenInfo, name: "PagePicker", path: "../../packages/js/components/src/pagination/page-picker.tsx#PagePicker" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pagination/page-size-picker.tsx
var page_size_picker = __webpack_require__("../../packages/js/components/src/pagination/page-size-picker.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/pagination/pagination.tsx

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



function Pagination(_ref) {
  var page = _ref.page,
    _ref$onPageChange = _ref.onPageChange,
    onPageChange = _ref$onPageChange === void 0 ? function () {} : _ref$onPageChange,
    total = _ref.total,
    perPage = _ref.perPage,
    _ref$onPerPageChange = _ref.onPerPageChange,
    onPerPageChange = _ref$onPerPageChange === void 0 ? function () {} : _ref$onPerPageChange,
    _ref$showPagePicker = _ref.showPagePicker,
    showPagePicker = _ref$showPagePicker === void 0 ? true : _ref$showPagePicker,
    _ref$showPerPagePicke = _ref.showPerPagePicker,
    showPerPagePicker = _ref$showPerPagePicke === void 0 ? true : _ref$showPerPagePicke,
    _ref$showPageArrowsLa = _ref.showPageArrowsLabel,
    showPageArrowsLabel = _ref$showPageArrowsLa === void 0 ? true : _ref$showPageArrowsLa,
    className = _ref.className,
    _ref$perPageOptions = _ref.perPageOptions,
    perPageOptions = _ref$perPageOptions === void 0 ? page_size_picker/* DEFAULT_PER_PAGE_OPTIONS */.v : _ref$perPageOptions,
    children = _ref.children;
  var pageCount = Math.ceil(total / perPage);
  if (children && typeof children === 'function') {
    return children({
      pageCount: pageCount
    });
  }
  var classes = classnames_default()('woocommerce-pagination', className);
  if (pageCount <= 1) {
    return total > perPageOptions[0] && (0,react.createElement)("div", {
      className: classes
    }, (0,react.createElement)(page_size_picker/* PageSizePicker */.$, {
      currentPage: page,
      perPage: perPage,
      setCurrentPage: onPageChange,
      total: total,
      setPerPageChange: onPerPageChange,
      perPageOptions: perPageOptions
    })) || null;
  }
  return (0,react.createElement)("div", {
    className: classes
  }, (0,react.createElement)(PageArrows, {
    currentPage: page,
    pageCount: pageCount,
    showPageArrowsLabel: showPageArrowsLabel,
    setCurrentPage: onPageChange
  }), showPagePicker && (0,react.createElement)(PagePicker, {
    currentPage: page,
    pageCount: pageCount,
    setCurrentPage: onPageChange
  }), showPerPagePicker && (0,react.createElement)(page_size_picker/* PageSizePicker */.$, {
    currentPage: page,
    perPage: perPage,
    setCurrentPage: onPageChange,
    total: total,
    setPerPageChange: onPerPageChange,
    perPageOptions: perPageOptions
  }));
}
try {
    // @ts-ignore
    Pagination.displayName = "Pagination";
    // @ts-ignore
    Pagination.__docgenInfo = { "description": "", "displayName": "Pagination", "props": { "page": { "defaultValue": null, "description": "", "name": "page", "required": true, "type": { "name": "number" } }, "perPage": { "defaultValue": null, "description": "", "name": "perPage", "required": true, "type": { "name": "number" } }, "total": { "defaultValue": null, "description": "", "name": "total", "required": true, "type": { "name": "number" } }, "onPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onPageChange", "required": false, "type": { "name": "((page: number, action?: \"previous\" | \"next\" | \"goto\") => void)" } }, "onPerPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onPerPageChange", "required": false, "type": { "name": "((perPage: number) => void)" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "showPagePicker": { "defaultValue": { value: "true" }, "description": "", "name": "showPagePicker", "required": false, "type": { "name": "boolean" } }, "showPerPagePicker": { "defaultValue": { value: "true" }, "description": "", "name": "showPerPagePicker", "required": false, "type": { "name": "boolean" } }, "showPageArrowsLabel": { "defaultValue": { value: "true" }, "description": "", "name": "showPageArrowsLabel", "required": false, "type": { "name": "boolean" } }, "perPageOptions": { "defaultValue": { value: "[ 25, 50, 75, 100 ]" }, "description": "", "name": "perPageOptions", "required": false, "type": { "name": "number[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/pagination.tsx#Pagination"] = { docgenInfo: Pagination.__docgenInfo, name: "Pagination", path: "../../packages/js/components/src/pagination/pagination.tsx#Pagination" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/table/placeholder.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _table__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../packages/js/components/src/table/table.tsx");



var _excluded = ["query", "caption", "headers", "numberOfRows"];

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











/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

/**
 * `TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.
 */
var TablePlaceholder = function TablePlaceholder(_ref) {
  var query = _ref.query,
    caption = _ref.caption,
    headers = _ref.headers,
    _ref$numberOfRows = _ref.numberOfRows,
    numberOfRows = _ref$numberOfRows === void 0 ? 5 : _ref$numberOfRows,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)(_ref, _excluded);
  var rows = (0,lodash__WEBPACK_IMPORTED_MODULE_12__.range)(numberOfRows).map(function () {
    return headers.map(function () {
      return {
        display: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_14__.createElement)("span", {
          className: "is-placeholder"
        })
      };
    });
  });
  var tableProps = _objectSpread({
    query: query,
    caption: caption,
    headers: headers,
    numberOfRows: numberOfRows
  }, props);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_14__.createElement)(_table__WEBPACK_IMPORTED_MODULE_15__/* ["default"] */ .A, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_16__/* ["default"] */ .A)({
    ariaHidden: true,
    className: "is-loading",
    rows: rows
  }, tableProps));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TablePlaceholder);

/***/ }),

/***/ "../../packages/js/components/src/table/stories/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ge: () => (/* binding */ rows),
/* harmony export */   b3: () => (/* binding */ headers),
/* harmony export */   z: () => (/* binding */ summary)
/* harmony export */ });
var headers = [{
  key: 'month',
  label: 'Month'
}, {
  key: 'orders',
  label: 'Orders'
}, {
  key: 'revenue',
  label: 'Revenue'
}];
var rows = [[{
  display: 'January',
  value: 1
}, {
  display: 10,
  value: 10
}, {
  display: '$530.00',
  value: 530
}], [{
  display: 'February',
  value: 2
}, {
  display: 13,
  value: 13
}, {
  display: '$675.00',
  value: 675
}], [{
  display: 'March',
  value: 3
}, {
  display: 9,
  value: 9
}, {
  display: '$460.00',
  value: 460
}]];
var summary = [{
  label: 'Gross Income',
  value: '$830.00'
}, {
  label: 'Taxes',
  value: '$96.32'
}, {
  label: 'Shipping',
  value: '$50.00'
}];

/***/ }),

/***/ "../../packages/js/components/src/table/summary.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   W: () => (/* binding */ TableSummaryPlaceholder)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * A component to display summarized table data - the list of data passed in on a single line.
 */
var TableSummary = function TableSummary(_ref) {
  var data = _ref.data;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("ul", {
    className: "woocommerce-table__summary",
    role: "complementary"
  }, data.map(function (_ref2, i) {
    var label = _ref2.label,
      value = _ref2.value;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("li", {
      className: "woocommerce-table__summary-item",
      key: i
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
      className: "woocommerce-table__summary-value"
    }, value), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
      className: "woocommerce-table__summary-label"
    }, label));
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TableSummary);

/**
 * A component to display a placeholder box for `TableSummary`. There is no prop for this component.
 *
 * @return {Object} -
 */
var TableSummaryPlaceholder = function TableSummaryPlaceholder() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("ul", {
    className: "woocommerce-table__summary is-loading",
    role: "complementary"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("li", {
    className: "woocommerce-table__summary-item"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
    className: "is-placeholder"
  })));
};
try {
    // @ts-ignore
    summary.displayName = "summary";
    // @ts-ignore
    summary.__docgenInfo = { "description": "A component to display summarized table data - the list of data passed in on a single line.", "displayName": "summary", "props": { "data": { "defaultValue": null, "description": "", "name": "data", "required": true, "type": { "name": "{ label: string; value: ReactNode; }[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/summary.tsx#summary"] = { docgenInfo: summary.__docgenInfo, name: "summary", path: "../../packages/js/components/src/table/summary.tsx#summary" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    TableSummaryPlaceholder.displayName = "TableSummaryPlaceholder";
    // @ts-ignore
    TableSummaryPlaceholder.__docgenInfo = { "description": "A component to display a placeholder box for `TableSummary`. There is no prop for this component.", "displayName": "TableSummaryPlaceholder", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/summary.tsx#TableSummaryPlaceholder"] = { docgenInfo: TableSummaryPlaceholder.__docgenInfo, name: "TableSummaryPlaceholder", path: "../../packages/js/components/src/table/summary.tsx#TableSummaryPlaceholder" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/table/table.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");



var _excluded = ["instanceId", "headers", "rows", "ariaHidden", "caption", "className", "onSort", "query", "rowHeader", "rowKey", "emptyMessage"];






/**
 * External dependencies
 */










/**
 * Internal dependencies
 */

var ASC = 'asc';
var DESC = 'desc';
var getDisplay = function getDisplay(cell) {
  return cell.display || null;
};

/**
 * A table component, without the Card wrapper. This is a basic table display, sortable, but no default filtering.
 *
 * Row data should be passed to the component as a list of arrays, where each array is a row in the table.
 * Headers are passed in separately as an array of objects with column-related properties. For example,
 * this data would render the following table.
 *
 * ```js
 * const headers = [ { label: 'Month' }, { label: 'Orders' }, { label: 'Revenue' } ];
 * const rows = [
 * 	[
 * 		{ display: 'January', value: 1 },
 * 		{ display: 10, value: 10 },
 * 		{ display: '$530.00', value: 530 },
 * 	],
 * 	[
 * 		{ display: 'February', value: 2 },
 * 		{ display: 13, value: 13 },
 * 		{ display: '$675.00', value: 675 },
 * 	],
 * 	[
 * 		{ display: 'March', value: 3 },
 * 		{ display: 9, value: 9 },
 * 		{ display: '$460.00', value: 460 },
 * 	],
 * ]
 * ```
 *
 * |   Month  | Orders | Revenue |
 * | ---------|--------|---------|
 * | January  |     10 | $530.00 |
 * | February |     13 | $675.00 |
 * | March    |      9 | $460.00 |
 */

var Table = function Table(_ref) {
  var instanceId = _ref.instanceId,
    _ref$headers = _ref.headers,
    headers = _ref$headers === void 0 ? [] : _ref$headers,
    _ref$rows = _ref.rows,
    rows = _ref$rows === void 0 ? [] : _ref$rows,
    ariaHidden = _ref.ariaHidden,
    caption = _ref.caption,
    className = _ref.className,
    _ref$onSort = _ref.onSort,
    onSort = _ref$onSort === void 0 ? function (f) {
      return f;
    } : _ref$onSort,
    _ref$query = _ref.query,
    query = _ref$query === void 0 ? {} : _ref$query,
    rowHeader = _ref.rowHeader,
    rowKey = _ref.rowKey,
    emptyMessage = _ref.emptyMessage,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)(_ref, _excluded);
  var classNames = props.classNames;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_8__.useState)(undefined),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(_useState, 2),
    tabIndex = _useState2[0],
    setTabIndex = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_8__.useState)(false),
    _useState4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(_useState3, 2),
    isScrollableRight = _useState4[0],
    setIsScrollableRight = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_8__.useState)(false),
    _useState6 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(_useState5, 2),
    isScrollableLeft = _useState6[0],
    setIsScrollableLeft = _useState6[1];
  var container = (0,react__WEBPACK_IMPORTED_MODULE_8__.useRef)(null);
  if (classNames) {
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A)("Table component's classNames prop", {
      since: '11.1.0',
      version: '12.0.0',
      alternative: 'className',
      plugin: '@woocommerce/components'
    });
  }
  var classes = classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-table__table', classNames, className, {
    'is-scrollable-right': isScrollableRight,
    'is-scrollable-left': isScrollableLeft
  });
  var sortBy = function sortBy(key) {
    return function () {
      var currentKey = query.orderby || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
        defaultSort: true
      }), 'key', false);
      var currentDir = query.order || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
        key: currentKey
      }), 'defaultOrder', DESC);
      var dir = DESC;
      if (key === currentKey) {
        dir = DESC === currentDir ? ASC : DESC;
      }
      onSort(key, dir);
    };
  };
  var getRowKey = function getRowKey(row, index) {
    if (rowKey && typeof rowKey === 'function') {
      return rowKey(row, index);
    }
    return index;
  };
  var updateTableShadow = function updateTableShadow() {
    var table = container.current;
    if (table !== null && table !== void 0 && table.scrollWidth && table !== null && table !== void 0 && table.scrollHeight && table !== null && table !== void 0 && table.offsetWidth) {
      var scrolledToEnd = (table === null || table === void 0 ? void 0 : table.scrollWidth) - (table === null || table === void 0 ? void 0 : table.scrollLeft) <= (table === null || table === void 0 ? void 0 : table.offsetWidth);
      if (scrolledToEnd && isScrollableRight) {
        setIsScrollableRight(false);
      } else if (!scrolledToEnd && !isScrollableRight) {
        setIsScrollableRight(true);
      }
    }
    if (table !== null && table !== void 0 && table.scrollLeft) {
      var scrolledToStart = (table === null || table === void 0 ? void 0 : table.scrollLeft) <= 0;
      if (scrolledToStart && isScrollableLeft) {
        setIsScrollableLeft(false);
      } else if (!scrolledToStart && !isScrollableLeft) {
        setIsScrollableLeft(true);
      }
    }
  };
  var sortedBy = query.orderby || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
    defaultSort: true
  }), 'key', false);
  var sortDir = query.order || (0,lodash__WEBPACK_IMPORTED_MODULE_7__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_7__.find)(headers, {
    key: sortedBy
  }), 'defaultOrder', DESC);
  var hasData = !!rows.length;
  (0,react__WEBPACK_IMPORTED_MODULE_8__.useEffect)(function () {
    var _container$current, _container$current2;
    var scrollWidth = (_container$current = container.current) === null || _container$current === void 0 ? void 0 : _container$current.scrollWidth;
    var clientWidth = (_container$current2 = container.current) === null || _container$current2 === void 0 ? void 0 : _container$current2.clientWidth;
    if (scrollWidth === undefined || clientWidth === undefined) {
      return;
    }
    var scrollable = scrollWidth > clientWidth;
    setTabIndex(scrollable ? 0 : undefined);
    updateTableShadow();
    window.addEventListener('resize', updateTableShadow);
    return function () {
      window.removeEventListener('resize', updateTableShadow);
    };
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_8__.useEffect)(updateTableShadow, [headers, rows, emptyMessage]);
  return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("div", {
    className: classes,
    ref: container,
    tabIndex: tabIndex,
    "aria-hidden": ariaHidden,
    "aria-labelledby": "caption-".concat(instanceId),
    role: "group",
    onScroll: updateTableShadow
  }, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("table", null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("caption", {
    id: "caption-".concat(instanceId),
    className: "woocommerce-table__caption screen-reader-text"
  }, caption, tabIndex === 0 && (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("small", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('(scroll to see more)', 'woocommerce'))), (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tbody", null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tr", null, headers.map(function (header, i) {
    var cellClassName = header.cellClassName,
      isLeftAligned = header.isLeftAligned,
      isSortable = header.isSortable,
      isNumeric = header.isNumeric,
      key = header.key,
      label = header.label,
      screenReaderLabel = header.screenReaderLabel;
    var labelId = "header-".concat(instanceId, "-").concat(i);
    var thProps = {
      className: classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-table__header', cellClassName, {
        'is-left-aligned': isLeftAligned || !isNumeric,
        'is-sortable': isSortable,
        'is-sorted': sortedBy === key,
        'is-numeric': isNumeric
      })
    };
    if (isSortable) {
      thProps['aria-sort'] = 'none';
      if (sortedBy === key) {
        thProps['aria-sort'] = sortDir === ASC ? 'ascending' : 'descending';
      }
    }
    // We only sort by ascending if the col is already sorted descending
    var iconLabel = sortedBy === key && sortDir !== ASC ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /* translators: %s: column label */
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Sort by %s in ascending order', 'woocommerce'), screenReaderLabel || label) : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__/* .sprintf */ .nv)( /* translators: %s: column label */
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Sort by %s in descending order', 'woocommerce'), screenReaderLabel || label);
    var textLabel = (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(react__WEBPACK_IMPORTED_MODULE_8__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("span", {
      "aria-hidden": Boolean(screenReaderLabel)
    }, label), screenReaderLabel && (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("span", {
      className: "screen-reader-text"
    }, screenReaderLabel));
    return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("th", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A)({
      role: "columnheader",
      scope: "col",
      key: header.key || i
    }, thProps), isSortable ? (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(react__WEBPACK_IMPORTED_MODULE_8__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A, {
      "aria-describedby": labelId,
      onClick: hasData ? sortBy(key) : lodash__WEBPACK_IMPORTED_MODULE_7__.noop
    }, sortedBy === key && sortDir === ASC ? (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_15__/* ["default"] */ .A
    }) : (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_16__/* ["default"] */ .A
    }), textLabel), (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("span", {
      className: "screen-reader-text",
      id: labelId
    }, iconLabel)) : textLabel);
  })), hasData ? rows.map(function (row, i) {
    return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tr", {
      key: getRowKey(row, i)
    }, row.map(function (cell, j) {
      var _headers$j = headers[j],
        cellClassName = _headers$j.cellClassName,
        isLeftAligned = _headers$j.isLeftAligned,
        isNumeric = _headers$j.isNumeric;
      var isHeader = rowHeader === j;
      var Cell = isHeader ? 'th' : 'td';
      var cellClasses = classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-table__item', cellClassName, {
        'is-left-aligned': isLeftAligned || !isNumeric,
        'is-numeric': isNumeric,
        'is-sorted': sortedBy === headers[j].key
      });
      var cellKey = getRowKey(row, i).toString() + j;
      return (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)(Cell, {
        scope: isHeader ? 'row' : undefined,
        key: cellKey,
        className: cellClasses
      }, getDisplay(cell));
    }));
  }) : (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_8__.createElement)("td", {
    className: "woocommerce-table__empty-item",
    colSpan: headers.length
  }, emptyMessage !== null && emptyMessage !== void 0 ? emptyMessage : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('No data to display', 'woocommerce'))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_17__/* ["default"] */ .A)(Table));

/***/ }),

/***/ "../../packages/js/components/src/table/stories/table-card.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Actions: () => (/* binding */ Actions),
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ table_card_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js
var es_parse_int = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js + 7 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js + 9 modules
var text_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../packages/js/components/src/ellipsis-menu/index.tsx
var ellipsis_menu = __webpack_require__("../../packages/js/components/src/ellipsis-menu/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/ellipsis-menu/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/ellipsis-menu/menu-item.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/ellipsis-menu/menu-title.tsx
var menu_title = __webpack_require__("../../packages/js/components/src/ellipsis-menu/menu-title.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/pagination/pagination.tsx + 2 modules
var pagination = __webpack_require__("../../packages/js/components/src/pagination/pagination.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/table/table.tsx
var table = __webpack_require__("../../packages/js/components/src/table/table.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/table/placeholder.tsx
var placeholder = __webpack_require__("../../packages/js/components/src/table/placeholder.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/table/summary.tsx
var table_summary = __webpack_require__("../../packages/js/components/src/table/summary.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/table/index.tsx



var _excluded = ["actions", "className", "hasSearch", "headers", "ids", "isLoading", "onQueryChange", "onColumnsChange", "onSort", "query", "rowHeader", "rows", "rowsPerPage", "showMenu", "summary", "title", "totalRows", "rowKey", "emptyMessage"];










/**
 * External dependencies
 */







/**
 * Internal dependencies
 */







var defaultOnQueryChange = function defaultOnQueryChange() {
  return function () {};
};
var defaultOnColumnsChange = function defaultOnColumnsChange() {};
/**
 * This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
 * It accepts `headers` for column headers, and `rows` for the table content.
 * `rowHeader` can be used to define the index of the row header (or false if no header).
 *
 * `TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 * This includes filtering and comparison functionality for report pages.
 */
var TableCard = function TableCard(_ref) {
  var actions = _ref.actions,
    className = _ref.className,
    hasSearch = _ref.hasSearch,
    _ref$headers = _ref.headers,
    headers = _ref$headers === void 0 ? [] : _ref$headers,
    ids = _ref.ids,
    _ref$isLoading = _ref.isLoading,
    isLoading = _ref$isLoading === void 0 ? false : _ref$isLoading,
    _ref$onQueryChange = _ref.onQueryChange,
    onQueryChange = _ref$onQueryChange === void 0 ? defaultOnQueryChange : _ref$onQueryChange,
    _ref$onColumnsChange = _ref.onColumnsChange,
    onColumnsChange = _ref$onColumnsChange === void 0 ? defaultOnColumnsChange : _ref$onColumnsChange,
    onSort = _ref.onSort,
    _ref$query = _ref.query,
    query = _ref$query === void 0 ? {} : _ref$query,
    _ref$rowHeader = _ref.rowHeader,
    rowHeader = _ref$rowHeader === void 0 ? 0 : _ref$rowHeader,
    _ref$rows = _ref.rows,
    rows = _ref$rows === void 0 ? [] : _ref$rows,
    rowsPerPage = _ref.rowsPerPage,
    _ref$showMenu = _ref.showMenu,
    showMenu = _ref$showMenu === void 0 ? true : _ref$showMenu,
    summary = _ref.summary,
    title = _ref.title,
    totalRows = _ref.totalRows,
    rowKey = _ref.rowKey,
    _ref$emptyMessage = _ref.emptyMessage,
    emptyMessage = _ref$emptyMessage === void 0 ? undefined : _ref$emptyMessage,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  // eslint-disable-next-line no-console
  var getShowCols = function getShowCols() {
    var _headers = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
    return _headers.map(function (_ref2) {
      var key = _ref2.key,
        visible = _ref2.visible;
      if (typeof visible === 'undefined' || visible) {
        return key;
      }
      return false;
    }).filter(Boolean);
  };
  var _useState = (0,react.useState)(getShowCols(headers)),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    showCols = _useState2[0],
    setShowCols = _useState2[1];
  var onColumnToggle = function onColumnToggle(key) {
    return function () {
      var hasKey = showCols.includes(key);
      if (hasKey) {
        // Handle hiding a sorted column
        if (query.orderby === key) {
          var defaultSort = (0,lodash.find)(headers, {
            defaultSort: true
          }) || (0,lodash.first)(headers) || {
            key: undefined
          };
          onQueryChange('sort')(defaultSort.key, 'desc');
        }
        var newShowCols = (0,lodash.without)(showCols, key);
        onColumnsChange(newShowCols, key);
        setShowCols(newShowCols);
      } else {
        var _newShowCols = [].concat((0,toConsumableArray/* default */.A)(showCols), [key]);
        onColumnsChange(_newShowCols, key);
        setShowCols(_newShowCols);
      }
    };
  };
  var onPageChange = function onPageChange(newPage, direction) {
    if (props.onPageChange) {
      props.onPageChange(newPage, direction);
    }
    if (onQueryChange) {
      onQueryChange('paged')(newPage.toString(), direction);
    }
  };
  var allHeaders = headers;
  var visibleHeaders = headers.filter(function (_ref3) {
    var key = _ref3.key;
    return showCols.includes(key);
  });
  var visibleRows = rows.map(function (row) {
    return headers.map(function (_ref4, i) {
      var key = _ref4.key;
      return showCols.includes(key) && row[i];
    }).filter(Boolean);
  });
  var classes = classnames_default()('woocommerce-table', className, {
    'has-actions': !!actions,
    'has-menu': showMenu,
    'has-search': hasSearch
  });
  return (0,react.createElement)(component/* default */.A, {
    className: classes
  }, (0,react.createElement)(card_header_component/* default */.A, null, (0,react.createElement)(text_component/* default */.A, {
    size: 16,
    weight: 600,
    as: "h2",
    color: "#23282d"
  }, title), (0,react.createElement)("div", {
    className: "woocommerce-table__actions"
  }, actions), showMenu && (0,react.createElement)(ellipsis_menu/* default */.A, {
    label: (0,build_module.__)('Choose which values to display', 'woocommerce'),
    renderContent: function renderContent() {
      return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(menu_title/* default */.A, null, (0,build_module.__)('Columns:', 'woocommerce')), allHeaders.map(function (_ref5) {
        var key = _ref5.key,
          label = _ref5.label,
          required = _ref5.required;
        if (required) {
          return null;
        }
        return (0,react.createElement)(menu_item/* default */.A, {
          checked: showCols.includes(key),
          isCheckbox: true,
          isClickable: true,
          key: key,
          onInvoke: key !== undefined ? onColumnToggle(key) : undefined
        }, label);
      }));
    }
  })), (0,react.createElement)(card_body_component/* default */.A, {
    size: null
  }, isLoading ? (0,react.createElement)(react.Fragment, null, (0,react.createElement)("span", {
    className: "screen-reader-text"
  }, (0,build_module.__)('Your requested data is loading', 'woocommerce')), (0,react.createElement)(placeholder/* default */.A, {
    numberOfRows: rowsPerPage,
    headers: visibleHeaders,
    rowHeader: rowHeader,
    caption: title,
    query: query
  })) : (0,react.createElement)(table/* default */.A, {
    rows: visibleRows,
    headers: visibleHeaders,
    rowHeader: rowHeader,
    caption: title,
    query: query,
    onSort: onSort || onQueryChange('sort'),
    rowKey: rowKey,
    emptyMessage: emptyMessage
  })), (0,react.createElement)(card_footer_component/* default */.A, {
    justify: "center"
  }, isLoading ? (0,react.createElement)(table_summary/* TableSummaryPlaceholder */.W, null) : (0,react.createElement)(react.Fragment, null, (0,react.createElement)(pagination/* Pagination */.d, {
    key: parseInt(query.paged, 10) || 1,
    page: parseInt(query.paged, 10) || 1,
    perPage: rowsPerPage,
    total: totalRows,
    onPageChange: onPageChange,
    onPerPageChange: function onPerPageChange(perPage) {
      return onQueryChange('per_page')(perPage.toString());
    }
  }), summary && (0,react.createElement)(table_summary/* default */.A, {
    data: summary
  }))));
};
/* harmony default export */ const src_table = (TableCard);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/table/stories/index.ts
var stories = __webpack_require__("../../packages/js/components/src/table/stories/index.ts");
;// CONCATENATED MODULE: ../../packages/js/components/src/table/stories/table-card.story.tsx



/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var TableCardExample = function TableCardExample() {
  var _useState = (0,react.useState)({
      query: {
        paged: 1
      }
    }),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    query = _useState2[0].query,
    setState = _useState2[1];
  return (0,react.createElement)(src_table, {
    title: "Revenue last week",
    rows: stories/* rows */.Ge,
    headers: stories/* headers */.b3,
    onQueryChange: function onQueryChange(param) {
      return function (value) {
        return setState({
          // @ts-expect-error: ignore for storybook
          query: (0,defineProperty/* default */.A)({}, param, value)
        });
      };
    },
    query: query,
    rowsPerPage: 7,
    totalRows: 10,
    summary: stories/* summary */.z
  });
};
var TableCardWithActionsExample = function TableCardWithActionsExample() {
  var _useState3 = (0,react.useState)({
      query: {
        paged: 1
      }
    }),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    query = _useState4[0].query,
    setState = _useState4[1];
  var _useState5 = (0,react.useState)('Action 1'),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    action1Text = _useState6[0],
    setAction1Text = _useState6[1];
  var _useState7 = (0,react.useState)('Action 2'),
    _useState8 = (0,slicedToArray/* default */.A)(_useState7, 2),
    action2Text = _useState8[0],
    setAction2Text = _useState8[1];
  return (0,react.createElement)(src_table, {
    actions: [(0,react.createElement)(build_module_button/* default */.A, {
      key: 0,
      onClick: function onClick() {
        setAction1Text('Action 1 Clicked');
      }
    }, action1Text), (0,react.createElement)(build_module_button/* default */.A, {
      key: 0,
      onClick: function onClick() {
        setAction2Text('Action 2 Clicked');
      }
    }, action2Text)],
    title: "Revenue last week",
    rows: stories/* rows */.Ge,
    headers: stories/* headers */.b3,
    onQueryChange: function onQueryChange(param) {
      return function (value) {
        return setState({
          // @ts-expect-error: ignore for storybook
          query: (0,defineProperty/* default */.A)({}, param, value)
        });
      };
    },
    query: query,
    rowsPerPage: 7,
    totalRows: 10,
    summary: stories/* summary */.z
  });
};
var Basic = function Basic() {
  return (0,react.createElement)(TableCardExample, null);
};
var Actions = function Actions() {
  return (0,react.createElement)(TableCardWithActionsExample, null);
};
/* harmony default export */ const table_card_story = ({
  title: 'WooCommerce Admin/components/TableCard',
  component: src_table
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <TableCardExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};
Actions.parameters = {
  ...Actions.parameters,
  docs: {
    ...Actions.parameters?.docs,
    source: {
      originalSource: "() => <TableCardWithActionsExample />",
      ...Actions.parameters?.docs?.source
    }
  }
};

/***/ })

}]);