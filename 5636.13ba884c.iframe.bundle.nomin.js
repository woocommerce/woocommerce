"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5636],{

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/VRAFYJQP.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   P$: () => (/* binding */ useToolbarProviderContext),
/* harmony export */   _N: () => (/* binding */ ToolbarScopedContextProvider),
/* harmony export */   wU: () => (/* binding */ useToolbarContext)
/* harmony export */ });
/* unused harmony exports useToolbarScopedContext, ToolbarContextProvider */
/* harmony import */ var _55FNNNML_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
"use client";



// src/toolbar/toolbar-context.tsx
var ctx = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__/* .createStoreContext */ .B0)(
  [_55FNNNML_js__WEBPACK_IMPORTED_MODULE_1__/* .CompositeContextProvider */ .ws],
  [_55FNNNML_js__WEBPACK_IMPORTED_MODULE_1__/* .CompositeScopedContextProvider */ .aN]
);
var useToolbarContext = ctx.useContext;
var useToolbarScopedContext = ctx.useScopedContext;
var useToolbarProviderContext = ctx.useProviderContext;
var ToolbarContextProvider = ctx.ContextProvider;
var ToolbarScopedContextProvider = ctx.ScopedContextProvider;




/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  L: () => (/* reexport */ speak)
});

// UNUSED EXPORTS: setup

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/script/add-container.js
function addContainer(ariaLive = "polite") {
  const container = document.createElement("div");
  container.id = `a11y-speak-${ariaLive}`;
  container.className = "a11y-speak-region";
  container.setAttribute(
    "style",
    "position:absolute;margin:-1px;padding:0;height:1px;width:1px;overflow:hidden;clip-path:inset(50%);border:0;word-wrap:normal !important;"
  );
  container.setAttribute("aria-live", ariaLive);
  container.setAttribute("aria-relevant", "additions text");
  container.setAttribute("aria-atomic", "true");
  const { body } = document;
  if (body) {
    body.appendChild(container);
  }
  return container;
}

//# sourceMappingURL=add-container.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/script/add-intro-text.js

function addIntroText() {
  const introText = document.createElement("p");
  introText.id = "a11y-speak-intro-text";
  introText.className = "a11y-speak-intro-text";
  introText.textContent = (0,i18n_build_module.__)("Notifications");
  introText.setAttribute(
    "style",
    "position:absolute;margin:-1px;padding:0;height:1px;width:1px;overflow:hidden;clip-path:inset(50%);border:0;word-wrap:normal !important;"
  );
  introText.setAttribute("hidden", "");
  const { body } = document;
  if (body) {
    body.appendChild(introText);
  }
  return introText;
}

//# sourceMappingURL=add-intro-text.js.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/shared/clear.js
function clear() {
  const regions = document.getElementsByClassName("a11y-speak-region");
  const introText = document.getElementById("a11y-speak-intro-text");
  for (let i = 0; i < regions.length; i++) {
    regions[i].textContent = "";
  }
  if (introText) {
    introText.setAttribute("hidden", "hidden");
  }
}

//# sourceMappingURL=clear.js.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/shared/filter-message.js
let previousMessage = "";
function filterMessage(message) {
  message = message.replace(/<[^<>]+>/g, " ");
  if (previousMessage === message) {
    message += "\xA0";
  }
  previousMessage = message;
  return message;
}

//# sourceMappingURL=filter-message.js.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/shared/index.js


function speak(message, ariaLive) {
  clear();
  message = filterMessage(message);
  const introText = document.getElementById("a11y-speak-intro-text");
  const containerAssertive = document.getElementById(
    "a11y-speak-assertive"
  );
  const containerPolite = document.getElementById("a11y-speak-polite");
  if (containerAssertive && ariaLive === "assertive") {
    containerAssertive.textContent = message;
  } else if (containerPolite) {
    containerPolite.textContent = message;
  }
  if (introText) {
    introText.removeAttribute("hidden");
  }
}

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/index.js




function setup() {
  const introText = document.getElementById("a11y-speak-intro-text");
  const containerAssertive = document.getElementById(
    "a11y-speak-assertive"
  );
  const containerPolite = document.getElementById("a11y-speak-polite");
  if (introText === null) {
    addIntroText();
  }
  if (containerAssertive === null) {
    addContainer("assertive");
  }
  if (containerPolite === null) {
    addContainer("polite");
  }
}
(0,build_module/* default */.A)(setup);

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown-menu/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ dropdown_menu_default)
});

// UNUSED EXPORTS: DropdownMenu

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/menu.mjs
// packages/icons/src/library/menu.tsx


var menu_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M5 5v1.5h14V5H5zm0 7.8h14v-1.5H5v1.5zM5 19h14v-1.5H5V19z" }) });

//# sourceMappingURL=menu.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js + 4 modules
var menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown-menu/index.js







function mergeProps(defaultProps = {}, props = {}) {
  const mergedProps = {
    ...defaultProps,
    ...props
  };
  if (props.className && defaultProps.className) {
    mergedProps.className = (0,clsx/* default */.A)(props.className, defaultProps.className);
  }
  return mergedProps;
}
function isFunction(maybeFunc) {
  return typeof maybeFunc === "function";
}
function UnconnectedDropdownMenu(dropdownMenuProps) {
  const {
    children,
    className,
    controls,
    icon = menu_default,
    label,
    popoverProps,
    toggleProps,
    menuProps,
    disableOpenOnArrowDown = false,
    text,
    noIcons,
    open,
    defaultOpen,
    onToggle: onToggleProp,
    // Context
    variant
  } = (0,use_context_system/* useContextSystem */.A)(dropdownMenuProps, "DropdownMenu");
  if (!controls?.length && !isFunction(children)) {
    return null;
  }
  let controlSets;
  if (controls?.length) {
    controlSets = controls;
    if (!Array.isArray(controlSets[0])) {
      controlSets = [controls];
    }
  }
  const mergedPopoverProps = mergeProps({
    className: "components-dropdown-menu__popover",
    variant
  }, popoverProps);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(dropdown/* default */.A, {
    className,
    popoverProps: mergedPopoverProps,
    renderToggle: ({
      isOpen,
      onToggle
    }) => {
      var _toggleProps$showTool;
      const openOnArrowDown = (event) => {
        if (disableOpenOnArrowDown) {
          return;
        }
        if (!isOpen && event.code === "ArrowDown") {
          event.preventDefault();
          onToggle();
        }
      };
      const {
        as: Toggle = build_module_button/* default */.Ay,
        ...restToggleProps
      } = toggleProps !== null && toggleProps !== void 0 ? toggleProps : {};
      const mergedToggleProps = mergeProps({
        className: (0,clsx/* default */.A)("components-dropdown-menu__toggle", {
          "is-opened": isOpen
        })
      }, restToggleProps);
      return /* @__PURE__ */ (0,jsx_runtime.jsx)(Toggle, {
        ...mergedToggleProps,
        icon,
        onClick: (event) => {
          onToggle();
          if (mergedToggleProps.onClick) {
            mergedToggleProps.onClick(event);
          }
        },
        onKeyDown: (event) => {
          openOnArrowDown(event);
          if (mergedToggleProps.onKeyDown) {
            mergedToggleProps.onKeyDown(event);
          }
        },
        "aria-haspopup": "true",
        "aria-expanded": isOpen,
        label,
        text,
        showTooltip: (_toggleProps$showTool = toggleProps?.showTooltip) !== null && _toggleProps$showTool !== void 0 ? _toggleProps$showTool : true,
        children: mergedToggleProps.children
      });
    },
    renderContent: (props) => {
      const mergedMenuProps = mergeProps({
        "aria-label": label,
        className: (0,clsx/* default */.A)("components-dropdown-menu__menu", {
          "no-icons": noIcons
        })
      }, menuProps);
      return /* @__PURE__ */ (0,jsx_runtime.jsxs)(menu/* default */.Ay, {
        ...mergedMenuProps,
        role: "menu",
        children: [isFunction(children) ? children(props) : null, controlSets?.flatMap((controlSet, indexOfSet) => controlSet.map((control, indexOfControl) => /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          __next40pxDefaultSize: true,
          onClick: (event) => {
            event.stopPropagation();
            props.onClose();
            if (control.onClick) {
              control.onClick();
            }
          },
          className: (0,clsx/* default */.A)("components-dropdown-menu__menu-item", {
            "has-separator": indexOfSet > 0 && indexOfControl === 0,
            "is-active": control.isActive,
            "is-icon-only": !control.title
          }),
          icon: control.icon,
          label: control.label,
          "aria-checked": control.role === "menuitemcheckbox" || control.role === "menuitemradio" ? control.isActive : void 0,
          role: control.role === "menuitemcheckbox" || control.role === "menuitemradio" ? control.role : "menuitem",
          accessibleWhenDisabled: true,
          disabled: control.isDisabled,
          children: control.title
        }, [indexOfSet, indexOfControl].join())))]
      });
    },
    open,
    defaultOpen,
    onToggle: onToggleProp
  });
}
const DropdownMenu = (0,context_connect/* contextConnectWithoutRef */.zS)(UnconnectedDropdownMenu, "DropdownMenu");
var dropdown_menu_default = DropdownMenu;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ dropdown_default)
/* harmony export */ });
/* unused harmony export Dropdown */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
/* harmony import */ var _utils_hooks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js");
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");








const UnconnectedDropdown = (props, forwardedRef) => {
  const {
    renderContent,
    renderToggle,
    className,
    contentClassName,
    expandOnMobile,
    headerTitle,
    focusOnMount,
    popoverProps,
    onClose,
    onToggle,
    style,
    open,
    defaultOpen,
    // Deprecated props
    position,
    // From context system
    variant
  } = (0,_context__WEBPACK_IMPORTED_MODULE_1__/* .useContextSystem */ .A)(props, "Dropdown");
  if (position !== void 0) {
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)("`position` prop in wp.components.Dropdown", {
      since: "6.2",
      alternative: "`popoverProps.placement` prop",
      hint: "Note that the `position` prop will override any values passed through the `popoverProps.placement` prop."
    });
  }
  const [fallbackPopoverAnchor, setFallbackPopoverAnchor] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null);
  const containerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useRef)();
  const [isOpen, setIsOpen] = (0,_utils_hooks__WEBPACK_IMPORTED_MODULE_4__/* .useControlledValue */ .j)({
    defaultValue: defaultOpen,
    value: open,
    onChange: onToggle
  });
  function closeIfFocusOutside() {
    if (!containerRef.current) {
      return;
    }
    const {
      ownerDocument
    } = containerRef.current;
    const dialog = ownerDocument?.activeElement?.closest('[role="dialog"]');
    if (!containerRef.current.contains(ownerDocument.activeElement) && (!dialog || dialog.contains(containerRef.current))) {
      close();
    }
  }
  function close() {
    onClose?.();
    setIsOpen(false);
  }
  const args = {
    isOpen: !!isOpen,
    onToggle: () => setIsOpen(!isOpen),
    onClose: close
  };
  const popoverPropsHaveAnchor = !!popoverProps?.anchor || // Note: `anchorRef`, `getAnchorRect` and `anchorRect` are deprecated and
  // be removed from `Popover` from WordPress 6.3
  !!popoverProps?.anchorRef || !!popoverProps?.getAnchorRect || !!popoverProps?.anchorRect;
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    className,
    ref: (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)([containerRef, forwardedRef, setFallbackPopoverAnchor]),
    tabIndex: -1,
    style,
    children: [renderToggle(args), isOpen && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_popover__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      position,
      onClose: close,
      onFocusOutside: closeIfFocusOutside,
      expandOnMobile,
      headerTitle,
      focusOnMount,
      offset: 13,
      anchor: !popoverPropsHaveAnchor ? fallbackPopoverAnchor : void 0,
      variant,
      ...popoverProps,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)("components-dropdown__content", popoverProps?.className, contentClassName),
      children: renderContent(args)
    })]
  });
};
const Dropdown = (0,_context__WEBPACK_IMPORTED_MODULE_8__/* .contextConnect */ .KZ)(UnconnectedDropdown, "Dropdown");
var dropdown_default = Dropdown;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/index.js":
/***/ (() => {




































































































































//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/menu-group/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ menu_group_default)
/* harmony export */ });
/* unused harmony export MenuGroup */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");




function MenuGroup(props) {
  const {
    children,
    className = "",
    label,
    hideSeparator
  } = props;
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(MenuGroup);
  if (!_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Children.count(children)) {
    return null;
  }
  const labelId = `components-menu-group-label-${instanceId}`;
  const classNames = (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(className, "components-menu-group", {
    "has-hidden-separator": hideSeparator
  });
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    className: classNames,
    children: [label && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: "components-menu-group__label",
      id: labelId,
      "aria-hidden": "true",
      children: label
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      role: "group",
      "aria-labelledby": label ? labelId : void 0,
      children
    })]
  });
}
var menu_group_default = MenuGroup;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/menu-item/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ menu_item_default)
/* harmony export */ });
/* unused harmony export MenuItem */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _shortcut__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/shortcut/index.js");
/* harmony import */ var _button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _icon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");






function UnforwardedMenuItem(props, ref) {
  let {
    children,
    info,
    className,
    icon,
    iconPosition = "right",
    shortcut,
    isSelected,
    role = "menuitem",
    suffix,
    ...buttonProps
  } = props;
  className = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)("components-menu-item__button", className);
  if (info) {
    children = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("span", {
      className: "components-menu-item__info-wrapper",
      children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        className: "components-menu-item__item",
        children
      }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        className: "components-menu-item__info",
        children: info
      })]
    });
  }
  if (icon && typeof icon !== "string") {
    icon = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.cloneElement)(icon, {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)("components-menu-items__item-icon", {
        "has-icon-right": iconPosition === "right"
      })
    });
  }
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(_button__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
    __next40pxDefaultSize: true,
    ref,
    "aria-checked": role === "menuitemcheckbox" || role === "menuitemradio" ? isSelected : void 0,
    role,
    icon: iconPosition === "left" ? icon : void 0,
    className,
    accessibleWhenDisabled: true,
    ...buttonProps,
    children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
      className: "components-menu-item__item",
      children
    }), !suffix && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_shortcut__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      className: "components-menu-item__shortcut",
      shortcut
    }), !suffix && icon && iconPosition === "right" && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_icon__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
      icon
    }), suffix]
  });
}
const MenuItem = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(UnforwardedMenuItem);
var menu_item_default = MenuItem;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ menu_default)
});

// UNUSED EXPORTS: NavigableMenu, UnforwardedNavigableMenu

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/focusable.mjs
var focusable_namespaceObject = {};
__webpack_require__.r(focusable_namespaceObject);
__webpack_require__.d(focusable_namespaceObject, {
  find: () => (find)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/tabbable.mjs
var tabbable_namespaceObject = {};
__webpack_require__.r(tabbable_namespaceObject);
__webpack_require__.d(tabbable_namespaceObject, {
  find: () => (tabbable_find),
  findNext: () => (findNext),
  findPrevious: () => (findPrevious),
  isTabbableIndex: () => (isTabbableIndex)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/focusable.mjs
// packages/dom/src/focusable.js
function buildSelector(sequential) {
  return [
    sequential ? '[tabindex]:not([tabindex^="-"])' : "[tabindex]",
    "a[href]",
    "button:not([disabled])",
    'input:not([type="hidden"]):not([disabled])',
    "select:not([disabled])",
    "textarea:not([disabled])",
    'iframe:not([tabindex^="-"])',
    "object",
    "embed",
    "summary",
    "area[href]",
    "[contenteditable]:not([contenteditable=false])"
  ].join(",");
}
function isVisible(element) {
  return element.offsetWidth > 0 || element.offsetHeight > 0 || element.getClientRects().length > 0;
}
function isValidFocusableArea(element) {
  const map = element.closest("map[name]");
  if (!map) {
    return false;
  }
  const img = element.ownerDocument.querySelector(
    'img[usemap="#' + map.name + '"]'
  );
  return !!img && isVisible(img);
}
function find(context, { sequential = false } = {}) {
  const elements = context.querySelectorAll(buildSelector(sequential));
  return Array.from(elements).filter((element) => {
    if (!isVisible(element)) {
      return false;
    }
    if (element.closest("[inert]")) {
      return false;
    }
    const { nodeName } = element;
    if ("AREA" === nodeName) {
      return isValidFocusableArea(
        /** @type {HTMLAreaElement} */
        element
      );
    }
    return true;
  });
}

//# sourceMappingURL=focusable.mjs.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/tabbable.mjs
// packages/dom/src/tabbable.js

function getTabIndex(element) {
  const tabIndex = element.getAttribute("tabindex");
  return tabIndex === null ? 0 : parseInt(tabIndex, 10);
}
function isTabbableIndex(element) {
  return getTabIndex(element) !== -1;
}
function createStatefulCollapseRadioGroup() {
  const CHOSEN_RADIO_BY_NAME = {};
  return function collapseRadioGroup(result, element) {
    const { nodeName, type, checked, name } = element;
    if (nodeName !== "INPUT" || type !== "radio" || !name) {
      return result.concat(element);
    }
    const hasChosen = CHOSEN_RADIO_BY_NAME.hasOwnProperty(name);
    const isChosen = checked || !hasChosen;
    if (!isChosen) {
      return result;
    }
    if (hasChosen) {
      const hadChosenElement = CHOSEN_RADIO_BY_NAME[name];
      result = result.filter((e) => e !== hadChosenElement);
    }
    CHOSEN_RADIO_BY_NAME[name] = element;
    return result.concat(element);
  };
}
function mapElementToObjectTabbable(element, index) {
  return { element, index };
}
function mapObjectTabbableToElement(object) {
  return object.element;
}
function compareObjectTabbables(a, b) {
  const aTabIndex = getTabIndex(a.element);
  const bTabIndex = getTabIndex(b.element);
  if (aTabIndex === bTabIndex) {
    return a.index - b.index;
  }
  return aTabIndex - bTabIndex;
}
function filterTabbable(focusables) {
  return focusables.filter(isTabbableIndex).map(mapElementToObjectTabbable).sort(compareObjectTabbables).map(mapObjectTabbableToElement).reduce(createStatefulCollapseRadioGroup(), []);
}
function tabbable_find(context) {
  return filterTabbable(find(context));
}
function findPrevious(element) {
  return filterTabbable(find(element.ownerDocument.body)).reverse().find(
    (focusable) => (
      // eslint-disable-next-line no-bitwise
      element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_PRECEDING
    )
  );
}
function findNext(element) {
  return filterTabbable(find(element.ownerDocument.body)).find(
    (focusable) => (
      // eslint-disable-next-line no-bitwise
      element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_FOLLOWING
    )
  );
}

//# sourceMappingURL=tabbable.mjs.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/index.mjs
// packages/dom/src/index.js





var build_module_focus = { focusable: focusable_namespaceObject, tabbable: tabbable_namespaceObject };

//# sourceMappingURL=index.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/container.js



const noop = () => {
};
const MENU_ITEM_ROLES = ["menuitem", "menuitemradio", "menuitemcheckbox"];
function cycleValue(value, total, offset) {
  const nextValue = value + offset;
  if (nextValue < 0) {
    return total + nextValue;
  } else if (nextValue >= total) {
    return nextValue - total;
  }
  return nextValue;
}
class NavigableContainer extends react.Component {
  constructor(args) {
    super(args);
    this.onKeyDown = this.onKeyDown.bind(this);
    this.bindContainer = this.bindContainer.bind(this);
    this.getFocusableContext = this.getFocusableContext.bind(this);
    this.getFocusableIndex = this.getFocusableIndex.bind(this);
  }
  componentDidMount() {
    if (!this.container) {
      return;
    }
    this.container.addEventListener("keydown", this.onKeyDown);
  }
  componentWillUnmount() {
    if (!this.container) {
      return;
    }
    this.container.removeEventListener("keydown", this.onKeyDown);
  }
  bindContainer(ref) {
    const {
      forwardedRef
    } = this.props;
    this.container = ref;
    if (typeof forwardedRef === "function") {
      forwardedRef(ref);
    } else if (forwardedRef && "current" in forwardedRef) {
      forwardedRef.current = ref;
    }
  }
  getFocusableContext(target) {
    if (!this.container) {
      return null;
    }
    const {
      onlyBrowserTabstops
    } = this.props;
    const finder = onlyBrowserTabstops ? build_module_focus.tabbable : build_module_focus.focusable;
    const focusables = finder.find(this.container);
    const index = this.getFocusableIndex(focusables, target);
    if (index > -1 && target) {
      return {
        index,
        target,
        focusables
      };
    }
    return null;
  }
  getFocusableIndex(focusables, target) {
    return focusables.indexOf(target);
  }
  onKeyDown(event) {
    if (this.props.onKeyDown) {
      this.props.onKeyDown(event);
    }
    const {
      getFocusableContext
    } = this;
    const {
      cycle = true,
      eventToOffset,
      onNavigate = noop,
      stopNavigationEvents
    } = this.props;
    const offset = eventToOffset(event);
    if (offset !== void 0 && stopNavigationEvents) {
      event.stopImmediatePropagation();
      const targetRole = event.target?.getAttribute("role");
      const targetHasMenuItemRole = !!targetRole && MENU_ITEM_ROLES.includes(targetRole);
      if (targetHasMenuItemRole) {
        event.preventDefault();
      }
    }
    if (!offset) {
      return;
    }
    const activeElement = event.target?.ownerDocument?.activeElement;
    if (!activeElement) {
      return;
    }
    const context = getFocusableContext(activeElement);
    if (!context) {
      return;
    }
    const {
      index,
      focusables
    } = context;
    const nextIndex = cycle ? cycleValue(index, focusables.length, offset) : index + offset;
    if (nextIndex >= 0 && nextIndex < focusables.length) {
      focusables[nextIndex].focus();
      onNavigate(nextIndex, focusables[nextIndex]);
      if (event.code === "Tab") {
        event.preventDefault();
      }
    }
  }
  render() {
    const {
      children,
      stopNavigationEvents,
      eventToOffset,
      onNavigate,
      onKeyDown,
      cycle,
      onlyBrowserTabstops,
      forwardedRef,
      ...restProps
    } = this.props;
    return /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
      ref: this.bindContainer,
      ...restProps,
      children
    });
  }
}
const forwardedNavigableContainer = (props, ref) => {
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(NavigableContainer, {
    ...props,
    forwardedRef: ref
  });
};
forwardedNavigableContainer.displayName = "NavigableContainer";
var container_default = (0,react.forwardRef)(forwardedNavigableContainer);

//# sourceMappingURL=container.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js



function UnforwardedNavigableMenu({
  role = "menu",
  orientation = "vertical",
  ...rest
}, ref) {
  const eventToOffset = (evt) => {
    const {
      code
    } = evt;
    let next = ["ArrowDown"];
    let previous = ["ArrowUp"];
    if (orientation === "horizontal") {
      next = ["ArrowRight"];
      previous = ["ArrowLeft"];
    }
    if (orientation === "both") {
      next = ["ArrowRight", "ArrowDown"];
      previous = ["ArrowLeft", "ArrowUp"];
    }
    if (next.includes(code)) {
      return 1;
    } else if (previous.includes(code)) {
      return -1;
    } else if (["ArrowDown", "ArrowUp", "ArrowLeft", "ArrowRight"].includes(code)) {
      return 0;
    }
    return void 0;
  };
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(container_default, {
    ref,
    stopNavigationEvents: true,
    onlyBrowserTabstops: false,
    role,
    "aria-orientation": role !== "presentation" && (orientation === "vertical" || orientation === "horizontal") ? orientation : void 0,
    eventToOffset,
    ...rest
  });
}
const NavigableMenu = (0,react.forwardRef)(UnforwardedNavigableMenu);
var menu_default = NavigableMenu;

//# sourceMappingURL=menu.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ component_default)
/* harmony export */ });
/* unused harmony export Text */
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
/* harmony import */ var _view__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
/* harmony import */ var _hook__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/hook.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");




function UnconnectedText(props, forwardedRef) {
  const textProps = (0,_hook__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(props);
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_view__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    as: "span",
    ...textProps,
    ref: forwardedRef
  });
}
const Text = (0,_context__WEBPACK_IMPORTED_MODULE_3__/* .contextConnect */ .KZ)(UnconnectedText, "Text");
var component_default = Text;

//# sourceMappingURL=component.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ toolbar_button_default)
});

// UNUSED EXPORTS: ToolbarButton

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js + 1 modules
var toolbar_item = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js
var toolbar_context = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-button/toolbar-button-container.js

const ToolbarButtonContainer = ({
  children,
  className
}) => /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
  className,
  children
});
var toolbar_button_container_default = ToolbarButtonContainer;

//# sourceMappingURL=toolbar-button-container.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-button/index.js







function useDeprecatedProps({
  isDisabled,
  ...otherProps
}) {
  return {
    disabled: isDisabled,
    ...otherProps
  };
}
function UnforwardedToolbarButton(props, ref) {
  const {
    children,
    className,
    containerClassName,
    extraProps,
    isActive,
    title,
    ...restProps
  } = useDeprecatedProps(props);
  const accessibleToolbarState = (0,react.useContext)(toolbar_context/* default */.A);
  if (!accessibleToolbarState) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_button_container_default, {
      className: containerClassName,
      children: /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        ref,
        icon: restProps.icon,
        size: "compact",
        label: title,
        shortcut: restProps.shortcut,
        "data-subscript": restProps.subscript,
        onClick: (event) => {
          event.stopPropagation();
          if (restProps.onClick) {
            restProps.onClick(event);
          }
        },
        className: (0,clsx/* default */.A)("components-toolbar__control", className),
        isPressed: isActive,
        accessibleWhenDisabled: true,
        "data-toolbar-item": true,
        ...extraProps,
        ...restProps,
        children
      })
    });
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_item/* default */.A, {
    className: (0,clsx/* default */.A)("components-toolbar-button", className),
    ...extraProps,
    ...restProps,
    ref,
    children: (toolbarItemProps) => /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      size: "compact",
      label: title,
      isPressed: isActive,
      ...toolbarItemProps,
      children
    })
  });
}
const ToolbarButton = (0,react.forwardRef)(UnforwardedToolbarButton);
var toolbar_button_default = ToolbarButton;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ toolbar_context_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

const ToolbarContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(void 0);
ToolbarContext.displayName = "ToolbarContext";
var toolbar_context_default = ToolbarContext;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ toolbar_group_default)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-button/index.js + 1 modules
var toolbar_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/toolbar-group-container.js

const ToolbarGroupContainer = ({
  className,
  children,
  ...props
}) => /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
  className,
  ...props,
  children
});
var toolbar_group_container_default = ToolbarGroupContainer;

//# sourceMappingURL=toolbar-group-container.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown-menu/index.js + 1 modules
var dropdown_menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown-menu/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js
var toolbar_context = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js + 1 modules
var toolbar_item = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/toolbar-group-collapsed.js





function ToolbarGroupCollapsed({
  controls = [],
  toggleProps,
  ...props
}) {
  const accessibleToolbarState = (0,react.useContext)(toolbar_context/* default */.A);
  const renderDropdownMenu = (internalToggleProps) => /* @__PURE__ */ (0,jsx_runtime.jsx)(dropdown_menu/* default */.A, {
    controls,
    toggleProps: {
      ...internalToggleProps,
      "data-toolbar-item": true
    },
    ...props
  });
  if (accessibleToolbarState) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_item/* default */.A, {
      ...toggleProps,
      children: renderDropdownMenu
    });
  }
  return renderDropdownMenu(toggleProps);
}
var toolbar_group_collapsed_default = ToolbarGroupCollapsed;

//# sourceMappingURL=toolbar-group-collapsed.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/index.js







function isNestedArray(arr) {
  return Array.isArray(arr) && Array.isArray(arr[0]);
}
function ToolbarGroup({
  controls = [],
  children,
  className,
  isCollapsed,
  title,
  ...props
}) {
  const accessibleToolbarState = (0,react.useContext)(toolbar_context/* default */.A);
  if ((!controls || !controls.length) && !children) {
    return null;
  }
  const finalClassName = (0,clsx/* default */.A)(
    // Unfortunately, there's legacy code referencing to `.components-toolbar`
    // So we can't get rid of it
    accessibleToolbarState ? "components-toolbar-group" : "components-toolbar",
    className
  );
  let controlSets;
  if (isNestedArray(controls)) {
    controlSets = controls;
  } else {
    controlSets = [controls];
  }
  if (isCollapsed) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_group_collapsed_default, {
      label: title,
      controls: controlSets,
      className: finalClassName,
      children,
      ...props
    });
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(toolbar_group_container_default, {
    className: finalClassName,
    ...props,
    children: [controlSets?.flatMap((controlSet, indexOfSet) => controlSet.map((control, indexOfControl) => /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_button/* default */.A, {
      containerClassName: indexOfSet > 0 && indexOfControl === 0 ? "has-left-divider" : void 0,
      ...control
    }, [indexOfSet, indexOfControl].join()))), children]
  });
}
var toolbar_group_default = ToolbarGroup;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ toolbar_item_default)
});

// UNUSED EXPORTS: ToolbarItem

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/VRAFYJQP.js
var VRAFYJQP = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/VRAFYJQP.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js + 1 modules
var ZCYMVQGT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js
var TVXRYIJB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/AYY52AFU.js
"use client";




// src/toolbar/toolbar-item.tsx
var TagName = "button";
var useToolbarItem = (0,TVXRYIJB/* createHook */.ab)(
  function useToolbarItem2({ store, ...props }) {
    const context = (0,VRAFYJQP/* useToolbarContext */.wU)();
    store = store || context;
    props = (0,ZCYMVQGT/* useCompositeItem */.k)({ store, ...props });
    return props;
  }
);
var ToolbarItem = (0,TVXRYIJB/* memo */.ph)(
  (0,TVXRYIJB/* forwardRef */.Rf)(function ToolbarItem2(props) {
    const htmlProps = useToolbarItem(props);
    return (0,TVXRYIJB/* createElement */.n)(TagName, htmlProps);
  })
);



// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+warning@3.48.1/node_modules/@wordpress/warning/build-module/index.mjs + 1 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+warning@3.48.1/node_modules/@wordpress/warning/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js
var toolbar_context = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js





function UnforwardedToolbarItem({
  children,
  as: Component,
  ...props
}, ref) {
  const accessibleToolbarStore = (0,react.useContext)(toolbar_context/* default */.A);
  const isRenderProp = typeof children === "function";
  if (!isRenderProp && !Component) {
    globalThis.SCRIPT_DEBUG === true ? (0,build_module/* default */.A)("`ToolbarItem` is a generic headless component. You must pass either a `children` prop as a function or an `as` prop as a component. See https://developer.wordpress.org/block-editor/components/toolbar-item/") : void 0;
    return null;
  }
  const allProps = {
    ...props,
    ref,
    "data-toolbar-item": true
  };
  if (!accessibleToolbarStore) {
    if (Component) {
      return /* @__PURE__ */ (0,jsx_runtime.jsx)(Component, {
        ...allProps,
        children
      });
    }
    if (!isRenderProp) {
      return null;
    }
    return children(allProps);
  }
  const render = isRenderProp ? children : Component && /* @__PURE__ */ (0,jsx_runtime.jsx)(Component, {
    children
  });
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(ToolbarItem, {
    accessibleWhenDisabled: true,
    ...allProps,
    store: accessibleToolbarStore,
    render
  });
}
const toolbar_item_ToolbarItem = (0,react.forwardRef)(UnforwardedToolbarItem);
var toolbar_item_default = toolbar_item_ToolbarItem;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ toolbar_default)
});

// UNUSED EXPORTS: Toolbar

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/index.js + 2 modules
var toolbar_group = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js + 1 modules
var ZJG6VNPS = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js
var YAS7X7HB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js
var UNDE2QJS = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js
var UWJK2WK2 = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/toolbar/toolbar-store.js
"use client";







// src/toolbar/toolbar-store.ts
function createToolbarStore(props = {}) {
  var _a;
  const syncState = (_a = props.store) == null ? void 0 : _a.getState();
  return (0,UNDE2QJS/* createCompositeStore */.z)({
    ...props,
    orientation: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.orientation,
      syncState == null ? void 0 : syncState.orientation,
      "horizontal"
    ),
    focusLoop: (0,UWJK2WK2/* defaultValue */.Jh)(props.focusLoop, syncState == null ? void 0 : syncState.focusLoop, true)
  });
}


;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/NTWYSJMG.js
"use client";



// src/toolbar/toolbar-store.ts

function useToolbarStoreProps(store, update, props) {
  return (0,ZJG6VNPS/* useCompositeStoreProps */.YO)(store, update, props);
}
function useToolbarStore(props = {}) {
  const [store, update] = (0,YAS7X7HB/* useStore */.Pj)(createToolbarStore, props);
  return useToolbarStoreProps(store, update, props);
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/VRAFYJQP.js
var VRAFYJQP = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/VRAFYJQP.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js
var _6PX47O7P = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js
var TVXRYIJB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js
var CEM7J6TT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/toolbar/toolbar.js
"use client";















// src/toolbar/toolbar.tsx

var TagName = "div";
var useToolbar = (0,TVXRYIJB/* createHook */.ab)(
  function useToolbar2({
    store: storeProp,
    orientation: orientationProp,
    virtualFocus,
    focusLoop,
    rtl,
    ...props
  }) {
    const context = (0,VRAFYJQP/* useToolbarProviderContext */.P$)();
    storeProp = storeProp || context;
    const store = useToolbarStore({
      store: storeProp,
      orientation: orientationProp,
      virtualFocus,
      focusLoop,
      rtl
    });
    const orientation = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => state.orientation === "both" ? void 0 : state.orientation
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(VRAFYJQP/* ToolbarScopedContextProvider */._N, { value: store, children: element }),
      [store]
    );
    props = {
      role: "toolbar",
      "aria-orientation": orientation,
      ...props
    };
    props = (0,_6PX47O7P/* useComposite */.T)({ store, ...props });
    return props;
  }
);
var Toolbar = (0,TVXRYIJB/* forwardRef */.Rf)(function Toolbar2(props) {
  const htmlProps = useToolbar(props);
  return (0,TVXRYIJB/* createElement */.n)(TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js
var toolbar_context = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-context/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar/toolbar-container.js





function UnforwardedToolbarContainer({
  label,
  ...props
}, ref) {
  const toolbarStore = useToolbarStore({
    focusLoop: true,
    rtl: (0,i18n_build_module/* isRTL */.V8)()
  });
  return (
    // This will provide state for `ToolbarButton`'s
    /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_context/* default */.A.Provider, {
      value: toolbarStore,
      children: /* @__PURE__ */ (0,jsx_runtime.jsx)(Toolbar, {
        ref,
        "aria-label": label,
        store: toolbarStore,
        ...props
      })
    })
  );
}
const ToolbarContainer = (0,react.forwardRef)(UnforwardedToolbarContainer);
var toolbar_container_default = ToolbarContainer;

//# sourceMappingURL=toolbar-container.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-system-provider.js + 1 modules
var context_system_provider = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-system-provider.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar/index.js







function UnforwardedToolbar({
  className,
  label,
  variant,
  ...props
}, ref) {
  const isVariantDefined = variant !== void 0;
  const contextSystemValue = (0,react.useMemo)(() => {
    if (isVariantDefined) {
      return {};
    }
    return {
      DropdownMenu: {
        variant: "toolbar"
      },
      Dropdown: {
        variant: "toolbar"
      },
      Menu: {
        variant: "toolbar"
      }
    };
  }, [isVariantDefined]);
  if (!label) {
    (0,build_module/* default */.A)("Using Toolbar without label prop", {
      since: "5.6",
      alternative: "ToolbarGroup component",
      link: "https://developer.wordpress.org/block-editor/components/toolbar/"
    });
    const {
      title: _title,
      ...restProps
    } = props;
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_group/* default */.A, {
      isCollapsed: false,
      ...restProps,
      className
    });
  }
  const finalClassName = (0,clsx/* default */.A)("components-accessible-toolbar", className, variant && `is-${variant}`);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(context_system_provider/* ContextSystemProvider */.c7, {
    value: contextSystemValue,
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)(toolbar_container_default, {
      className: finalClassName,
      label,
      ref,
      ...props
    })
  });
}
const toolbar_Toolbar = (0,react.forwardRef)(UnforwardedToolbar);
var toolbar_default = toolbar_Toolbar;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-controlled-value.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   j: () => (/* binding */ useControlledValue)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

function useControlledValue({
  defaultValue,
  onChange,
  value: valueProp
}) {
  const hasValue = typeof valueProp !== "undefined";
  const initialValue = hasValue ? valueProp : defaultValue;
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(initialValue);
  const value = hasValue ? valueProp : state;
  const uncontrolledSetValue = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)((nextValue, ...args) => {
    setState(nextValue);
    onChange?.(nextValue, ...args);
  }, [onChange]);
  let setValue;
  if (hasValue && typeof onChange === "function") {
    setValue = onChange;
  } else if (!hasValue && typeof onChange === "function") {
    setValue = uncontrolledSetValue;
  } else {
    setValue = setState;
  }
  return [value, setValue];
}

//# sourceMappingURL=use-controlled-value.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_left_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_left_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z" }) });

//# sourceMappingURL=chevron-left.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_right_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_right_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z" }) });

//# sourceMappingURL=chevron-right.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/more-vertical.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ more_vertical_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var more_vertical_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z" }) });

//# sourceMappingURL=more-vertical.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/trash.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ trash_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var trash_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(
  _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA,
  {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M12 5.5A2.25 2.25 0 0 0 9.878 7h4.244A2.251 2.251 0 0 0 12 5.5ZM12 4a3.751 3.751 0 0 0-3.675 3H5v1.5h1.27l.818 8.997a2.75 2.75 0 0 0 2.739 2.501h4.347a2.75 2.75 0 0 0 2.738-2.5L17.73 8.5H19V7h-3.325A3.751 3.751 0 0 0 12 4Zm4.224 4.5H7.776l.806 8.861a1.25 1.25 0 0 0 1.245 1.137h4.347a1.25 1.25 0 0 0 1.245-1.137l.805-8.861Z"
  }
) });

//# sourceMappingURL=trash.js.map


/***/ })

}]);