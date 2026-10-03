"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6383],{

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

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ tab_panel_default)
});

// UNUSED EXPORTS: TabPanel

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/E4DZA6YM.js
var E4DZA6YM = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/E4DZA6YM.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js
var _55FNNNML = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js
var TVXRYIJB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/OIOLJY4L.js
"use client";




// src/select/select-context.tsx

var ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [E4DZA6YM/* PopoverContextProvider */.wf, _55FNNNML/* CompositeContextProvider */.ws],
  [E4DZA6YM/* PopoverScopedContextProvider */.s1, _55FNNNML/* CompositeScopedContextProvider */.aN]
);
var useSelectContext = ctx.useContext;
var useSelectScopedContext = ctx.useScopedContext;
var useSelectProviderContext = ctx.useProviderContext;
var SelectContextProvider = ctx.ContextProvider;
var SelectScopedContextProvider = ctx.ScopedContextProvider;
var SelectItemCheckedContext = (0,react.createContext)(false);
var SelectHeadingContext = (0,react.createContext)(null);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js + 1 modules
var ZJG6VNPS = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/H55QERR5.js
"use client";




// src/combobox/combobox-context.tsx

var ComboboxListRoleContext = (0,react.createContext)(
  void 0
);
var H55QERR5_ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [E4DZA6YM/* PopoverContextProvider */.wf, _55FNNNML/* CompositeContextProvider */.ws],
  [E4DZA6YM/* PopoverScopedContextProvider */.s1, _55FNNNML/* CompositeScopedContextProvider */.aN]
);
var useComboboxContext = H55QERR5_ctx.useContext;
var useComboboxScopedContext = H55QERR5_ctx.useScopedContext;
var useComboboxProviderContext = H55QERR5_ctx.useProviderContext;
var ComboboxContextProvider = H55QERR5_ctx.ContextProvider;
var ComboboxScopedContextProvider = H55QERR5_ctx.ScopedContextProvider;
var ComboboxItemValueContext = (0,react.createContext)(
  void 0
);
var ComboboxItemCheckedContext = (0,react.createContext)(false);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js
var YAS7X7HB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js
var CEM7J6TT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js
var UNDE2QJS = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/KZX46JDB.js
var KZX46JDB = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/KZX46JDB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js
var XTZ53NXG = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js
var UWJK2WK2 = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/tab/tab-store.js
"use client";







// src/tab/tab-store.ts
function createTabStore({
  composite: parentComposite,
  combobox,
  ...props
} = {}) {
  const independentKeys = [
    "items",
    "renderedItems",
    "moves",
    "orientation",
    "virtualFocus",
    "includesBaseElement",
    "baseElement",
    "focusLoop",
    "focusShift",
    "focusWrap"
  ];
  const store = (0,XTZ53NXG/* mergeStore */.od)(
    props.store,
    (0,XTZ53NXG/* omit */.cJ)(parentComposite, independentKeys),
    (0,XTZ53NXG/* omit */.cJ)(combobox, independentKeys)
  );
  const syncState = store == null ? void 0 : store.getState();
  const composite = (0,UNDE2QJS/* createCompositeStore */.z)({
    ...props,
    store,
    // We need to explicitly set the default value of `includesBaseElement` to
    // `false` since we don't want the composite store to default it to `true`
    // when the activeId state is null, which could be the case when rendering
    // combobox with tab.
    includesBaseElement: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.includesBaseElement,
      syncState == null ? void 0 : syncState.includesBaseElement,
      false
    ),
    orientation: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.orientation,
      syncState == null ? void 0 : syncState.orientation,
      "horizontal"
    ),
    focusLoop: (0,UWJK2WK2/* defaultValue */.Jh)(props.focusLoop, syncState == null ? void 0 : syncState.focusLoop, true)
  });
  const panels = (0,KZX46JDB/* createCollectionStore */.I)();
  const initialState = {
    ...composite.getState(),
    selectedId: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.selectedId,
      syncState == null ? void 0 : syncState.selectedId,
      props.defaultSelectedId
    ),
    selectOnMove: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.selectOnMove,
      syncState == null ? void 0 : syncState.selectOnMove,
      true
    )
  };
  const tab = (0,XTZ53NXG/* createStore */.y$)(initialState, composite, store);
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* sync */.OH)(tab, ["moves"], () => {
      const { activeId, selectOnMove } = tab.getState();
      if (!selectOnMove) return;
      if (!activeId) return;
      const tabItem = composite.item(activeId);
      if (!tabItem) return;
      if (tabItem.dimmed) return;
      if (tabItem.disabled) return;
      tab.setState("selectedId", tabItem.id);
    })
  );
  let syncActiveId = true;
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* batch */.vA)(tab, ["selectedId"], (state, prev) => {
      if (!syncActiveId) {
        syncActiveId = true;
        return;
      }
      if (parentComposite && state.selectedId === prev.selectedId) return;
      tab.setState("activeId", state.selectedId);
    })
  );
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* sync */.OH)(tab, ["selectedId", "renderedItems"], (state) => {
      if (state.selectedId !== void 0) return;
      const { activeId, renderedItems } = tab.getState();
      const tabItem = composite.item(activeId);
      if (tabItem && !tabItem.disabled && !tabItem.dimmed) {
        tab.setState("selectedId", tabItem.id);
      } else {
        const tabItem2 = renderedItems.find(
          (item) => !item.disabled && !item.dimmed
        );
        tab.setState("selectedId", tabItem2 == null ? void 0 : tabItem2.id);
      }
    })
  );
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* sync */.OH)(tab, ["renderedItems"], (state) => {
      const tabs = state.renderedItems;
      if (!tabs.length) return;
      return (0,XTZ53NXG/* sync */.OH)(panels, ["renderedItems"], (state2) => {
        const items = state2.renderedItems;
        const hasOrphanPanels = items.some((panel) => !panel.tabId);
        if (!hasOrphanPanels) return;
        items.forEach((panel, i) => {
          if (panel.tabId) return;
          const tabItem = tabs[i];
          if (!tabItem) return;
          panels.renderItem({ ...panel, tabId: tabItem.id });
        });
      });
    })
  );
  let selectedIdFromSelectedValue = null;
  (0,XTZ53NXG/* setup */.mj)(tab, () => {
    const backupSelectedId = () => {
      selectedIdFromSelectedValue = tab.getState().selectedId;
    };
    const restoreSelectedId = () => {
      syncActiveId = false;
      tab.setState("selectedId", selectedIdFromSelectedValue);
    };
    if (parentComposite && "setSelectElement" in parentComposite) {
      return (0,UWJK2WK2/* chain */.cy)(
        (0,XTZ53NXG/* sync */.OH)(parentComposite, ["value"], backupSelectedId),
        (0,XTZ53NXG/* sync */.OH)(parentComposite, ["mounted"], restoreSelectedId)
      );
    }
    if (!combobox) return;
    return (0,UWJK2WK2/* chain */.cy)(
      (0,XTZ53NXG/* sync */.OH)(combobox, ["selectedValue"], backupSelectedId),
      (0,XTZ53NXG/* sync */.OH)(combobox, ["mounted"], restoreSelectedId)
    );
  });
  return {
    ...composite,
    ...tab,
    panels,
    setSelectedId: (id) => tab.setState("selectedId", id),
    select: (id) => {
      tab.setState("selectedId", id);
      composite.move(id);
    }
  };
}


;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/XTV6BHAW.js
"use client";






// src/tab/tab-store.ts


function useTabStoreProps(store, update, props) {
  (0,CEM7J6TT/* useUpdateEffect */.w5)(update, [props.composite, props.combobox]);
  store = (0,ZJG6VNPS/* useCompositeStoreProps */.YO)(store, update, props);
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "selectedId", "setSelectedId");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "selectOnMove");
  const [panels, updatePanels] = (0,YAS7X7HB/* useStore */.Pj)(() => store.panels, {});
  (0,CEM7J6TT/* useUpdateEffect */.w5)(updatePanels, [store, updatePanels]);
  return Object.assign(
    (0,react.useMemo)(() => ({ ...store, panels }), [store, panels]),
    { composite: props.composite, combobox: props.combobox }
  );
}
function useTabStore(props = {}) {
  const combobox = useComboboxContext();
  const composite = useSelectContext() || combobox;
  props = {
    ...props,
    composite: props.composite !== void 0 ? props.composite : composite,
    combobox: props.combobox !== void 0 ? props.combobox : combobox
  };
  const [store, update] = (0,YAS7X7HB/* useStore */.Pj)(createTabStore, props);
  return useTabStoreProps(store, update, props);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/F4SJNU4L.js
"use client";



// src/tab/tab-context.tsx
var F4SJNU4L_ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [_55FNNNML/* CompositeContextProvider */.ws],
  [_55FNNNML/* CompositeScopedContextProvider */.aN]
);
var useTabContext = F4SJNU4L_ctx.useContext;
var useTabScopedContext = F4SJNU4L_ctx.useScopedContext;
var useTabProviderContext = F4SJNU4L_ctx.useProviderContext;
var TabContextProvider = F4SJNU4L_ctx.ContextProvider;
var TabScopedContextProvider = F4SJNU4L_ctx.ScopedContextProvider;



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js
var _6PX47O7P = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tab/tab-list.js
"use client";












// src/tab/tab-list.tsx


var TagName = "div";
var useTabList = (0,TVXRYIJB/* createHook */.ab)(
  function useTabList2({ store, ...props }) {
    const context = useTabProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const orientation = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => state.orientation === "both" ? void 0 : state.orientation
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, { value: store, children: element }),
      [store]
    );
    if (store.composite) {
      props = {
        focusable: false,
        ...props
      };
    }
    props = {
      role: "tablist",
      "aria-orientation": orientation,
      ...props
    };
    props = (0,_6PX47O7P/* useComposite */.T)({ store, ...props });
    return props;
  }
);
var TabList = (0,TVXRYIJB/* forwardRef */.Rf)(function TabList2(props) {
  const htmlProps = useTabList(props);
  return (0,TVXRYIJB/* createElement */.n)(TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js + 1 modules
var ZCYMVQGT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tab/tab.js
"use client";














// src/tab/tab.tsx



var tab_TagName = "button";
var useTab = (0,TVXRYIJB/* createHook */.ab)(function useTab2({
  store,
  getItem: getItemProp,
  ...props
}) {
  var _a;
  const context = useTabScopedContext();
  store = store || context;
  (0,UWJK2WK2/* invariant */.V1)(
    store,
     false && 0
  );
  const defaultId = (0,CEM7J6TT/* useId */.Bi)();
  const id = props.id || defaultId;
  const dimmed = (0,UWJK2WK2/* disabledFromProps */.$f)(props);
  const getItem = (0,react.useCallback)(
    (item) => {
      const nextItem = { ...item, dimmed };
      if (getItemProp) {
        return getItemProp(nextItem);
      }
      return nextItem;
    },
    [dimmed, getItemProp]
  );
  const onClickProp = props.onClick;
  const onClick = (0,CEM7J6TT/* useEvent */._q)((event) => {
    onClickProp == null ? void 0 : onClickProp(event);
    if (event.defaultPrevented) return;
    store == null ? void 0 : store.setSelectedId(id);
  });
  const panelId = (0,YAS7X7HB/* useStoreState */.O$)(
    store.panels,
    (state) => {
      var _a2;
      return (_a2 = state.items.find((item) => item.tabId === id)) == null ? void 0 : _a2.id;
    }
  );
  const shouldRegisterItem = defaultId ? props.shouldRegisterItem : false;
  const isActive = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => !!id && state.activeId === id
  );
  const selected = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => !!id && state.selectedId === id
  );
  const hasActiveItem = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => !!store.item(state.activeId)
  );
  const canRegisterComposedItem = isActive || selected && !hasActiveItem;
  const accessibleWhenDisabled = selected || ((_a = props.accessibleWhenDisabled) != null ? _a : true);
  const isWithinVirtualFocusComposite = (0,YAS7X7HB/* useStoreState */.O$)(
    store.combobox || store.composite,
    "virtualFocus"
  );
  if (isWithinVirtualFocusComposite) {
    props = {
      ...props,
      tabIndex: -1
    };
  }
  props = {
    role: "tab",
    "aria-selected": selected,
    "aria-controls": panelId || void 0,
    ...props,
    id,
    onClick
  };
  if (store.composite) {
    const defaultProps = {
      id,
      accessibleWhenDisabled,
      store: store.composite,
      shouldRegisterItem: canRegisterComposedItem && shouldRegisterItem,
      rowId: props.rowId,
      render: props.render
    };
    props = {
      ...props,
      render: /* @__PURE__ */ (0,jsx_runtime.jsx)(
        ZCYMVQGT/* CompositeItem */.l,
        {
          ...defaultProps,
          render: store.combobox && store.composite !== store.combobox ? /* @__PURE__ */ (0,jsx_runtime.jsx)(ZCYMVQGT/* CompositeItem */.l, { ...defaultProps, store: store.combobox }) : defaultProps.render
        }
      )
    };
  }
  props = (0,ZCYMVQGT/* useCompositeItem */.k)({
    store,
    ...props,
    accessibleWhenDisabled,
    getItem,
    shouldRegisterItem
  });
  return props;
});
var Tab = (0,TVXRYIJB/* memo */.ph)(
  (0,TVXRYIJB/* forwardRef */.Rf)(function Tab2(props) {
    const htmlProps = useTab(props);
    return (0,TVXRYIJB/* createElement */.n)(tab_TagName, htmlProps);
  })
);


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/WLLNEL2X.js
var WLLNEL2X = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/WLLNEL2X.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FL7NPBCY.js
var FL7NPBCY = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FL7NPBCY.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IKWLDXMV.js
var IKWLDXMV = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IKWLDXMV.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js
var GR523XJ6 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js
var utils_focus = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tab/tab-panel.js
"use client";















// src/tab/tab-panel.tsx




var tab_panel_TagName = "div";
var useTabPanel = (0,TVXRYIJB/* createHook */.ab)(
  function useTabPanel2({
    store,
    unmountOnHide,
    tabId: tabIdProp,
    getItem: getItemProp,
    scrollRestoration,
    scrollElement,
    ...props
  }) {
    const context = useTabProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const id = (0,CEM7J6TT/* useId */.Bi)(props.id);
    const tabId = (0,YAS7X7HB/* useStoreState */.O$)(
      store.panels,
      () => {
        var _a;
        return tabIdProp || ((_a = store == null ? void 0 : store.panels.item(id)) == null ? void 0 : _a.tabId);
      }
    );
    const open = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => !!tabId && state.selectedId === tabId
    );
    const disclosure = (0,FL7NPBCY/* useDisclosureStore */.E)({ open });
    const mounted = (0,YAS7X7HB/* useStoreState */.O$)(disclosure, "mounted");
    const scrollPositionRef = (0,react.useRef)(
      /* @__PURE__ */ new Map()
    );
    const getScrollElement = (0,CEM7J6TT/* useEvent */._q)(() => {
      const panelElement = ref.current;
      if (!panelElement) return null;
      if (!scrollElement) return panelElement;
      if (typeof scrollElement === "function") {
        return scrollElement(panelElement);
      }
      if ("current" in scrollElement) {
        return scrollElement.current;
      }
      return scrollElement;
    });
    (0,react.useEffect)(() => {
      var _a, _b;
      if (!scrollRestoration) return;
      if (!mounted) return;
      const element = getScrollElement();
      if (!element) return;
      if (scrollRestoration === "reset") {
        element.scroll(0, 0);
        return;
      }
      if (!tabId) return;
      const position = scrollPositionRef.current.get(tabId);
      element.scroll((_a = position == null ? void 0 : position.x) != null ? _a : 0, (_b = position == null ? void 0 : position.y) != null ? _b : 0);
      const onScroll = () => {
        scrollPositionRef.current.set(tabId, {
          x: element.scrollLeft,
          y: element.scrollTop
        });
      };
      element.addEventListener("scroll", onScroll);
      return () => {
        element.removeEventListener("scroll", onScroll);
      };
    }, [scrollRestoration, mounted, tabId, getScrollElement, store]);
    const [hasTabbableChildren, setHasTabbableChildren] = (0,react.useState)(false);
    (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
      if (!mounted) return;
      const element = ref.current;
      if (!element) return;
      setHasTabbableChildren(!!(0,utils_focus/* getAllTabbableIn */.a9)(element).length);
    }, [mounted]);
    const getItem = (0,react.useCallback)(
      (item) => {
        const nextItem = { ...item, id: id || item.id, tabId: tabIdProp };
        if (getItemProp) {
          return getItemProp(nextItem);
        }
        return nextItem;
      },
      [id, tabIdProp, getItemProp]
    );
    const onKeyDownProp = props.onKeyDown;
    const onKeyDown = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (event.defaultPrevented) return;
      if (!(store == null ? void 0 : store.composite)) return;
      const keyMap = {
        ArrowLeft: store.previous,
        ArrowRight: store.next,
        Home: store.first,
        End: store.last
      };
      const action = keyMap[event.key];
      if (!action) return;
      const { selectedId } = store.getState();
      const nextId = action({ activeId: selectedId });
      if (!nextId) return;
      event.preventDefault();
      store.move(nextId);
    });
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, { value: store, children: element }),
      [store]
    );
    props = {
      role: "tabpanel",
      "aria-labelledby": props["aria-label"] != null ? void 0 : tabId || void 0,
      ...props,
      id,
      children: unmountOnHide && !mounted ? null : props.children,
      ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref),
      onKeyDown
    };
    props = (0,GR523XJ6/* useFocusable */.W)({
      // If the tab panel is rendered as part of another composite widget such
      // as combobox, it should not be focusable.
      focusable: !store.composite && !hasTabbableChildren,
      ...props
    });
    props = (0,WLLNEL2X/* useDisclosureContent */.aT)({ store: disclosure, ...props });
    props = (0,IKWLDXMV/* useCollectionItem */.v)({ store: store.panels, ...props, getItem });
    return props;
  }
);
var TabPanel = (0,TVXRYIJB/* forwardRef */.Rf)(function TabPanel2(props) {
  const htmlProps = useTabPanel(props);
  return (0,TVXRYIJB/* createElement */.n)(tab_panel_TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs
var use_previous = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js







const extractTabName = (id) => {
  if (typeof id === "undefined" || id === null) {
    return;
  }
  return id.match(/^tab-panel-[0-9]*-(.*)/)?.[1];
};
const UnforwardedTabPanel = ({
  className,
  children,
  tabs,
  selectOnMove = true,
  initialTabName,
  orientation = "horizontal",
  activeClass = "is-active",
  onSelect
}, ref) => {
  const instanceId = (0,use_instance_id/* default */.A)(tab_panel_TabPanel, "tab-panel");
  const prependInstanceId = (0,react.useCallback)((tabName) => {
    if (typeof tabName === "undefined") {
      return;
    }
    return `${instanceId}-${tabName}`;
  }, [instanceId]);
  const tabStore = useTabStore({
    setSelectedId: (newTabValue) => {
      if (typeof newTabValue === "undefined" || newTabValue === null) {
        return;
      }
      const newTab = tabs.find((t) => prependInstanceId(t.name) === newTabValue);
      if (newTab?.disabled || newTab === selectedTab) {
        return;
      }
      const simplifiedTabName = extractTabName(newTabValue);
      if (typeof simplifiedTabName === "undefined") {
        return;
      }
      onSelect?.(simplifiedTabName);
    },
    orientation,
    selectOnMove,
    defaultSelectedId: prependInstanceId(initialTabName),
    rtl: (0,build_module/* isRTL */.V8)()
  });
  const selectedTabName = extractTabName(YAS7X7HB/* useStoreState */.O$(tabStore, "selectedId"));
  const setTabStoreSelectedId = (0,react.useCallback)((tabName) => {
    tabStore.setState("selectedId", prependInstanceId(tabName));
  }, [prependInstanceId, tabStore]);
  const selectedTab = tabs.find(({
    name
  }) => name === selectedTabName);
  const previousSelectedTabName = (0,use_previous/* default */.A)(selectedTabName);
  (0,react.useEffect)(() => {
    if (previousSelectedTabName !== selectedTabName && selectedTabName === initialTabName && !!selectedTabName) {
      onSelect?.(selectedTabName);
    }
  }, [selectedTabName, initialTabName, onSelect, previousSelectedTabName]);
  (0,react.useLayoutEffect)(() => {
    if (selectedTab) {
      return;
    }
    const initialTab = tabs.find((tab) => tab.name === initialTabName);
    if (initialTabName && !initialTab) {
      return;
    }
    if (initialTab && !initialTab.disabled) {
      setTabStoreSelectedId(initialTab.name);
    } else {
      const firstEnabledTab = tabs.find((tab) => !tab.disabled);
      if (firstEnabledTab) {
        setTabStoreSelectedId(firstEnabledTab.name);
      }
    }
  }, [tabs, selectedTab, initialTabName, instanceId, setTabStoreSelectedId]);
  (0,react.useEffect)(() => {
    if (!selectedTab?.disabled) {
      return;
    }
    const firstEnabledTab = tabs.find((tab) => !tab.disabled);
    if (firstEnabledTab) {
      setTabStoreSelectedId(firstEnabledTab.name);
    }
  }, [tabs, selectedTab?.disabled, setTabStoreSelectedId, instanceId]);
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
    className,
    ref,
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(TabList, {
      store: tabStore,
      className: "components-tab-panel__tabs",
      children: tabs.map((tab) => {
        return /* @__PURE__ */ (0,jsx_runtime.jsx)(Tab, {
          id: prependInstanceId(tab.name),
          className: (0,clsx/* default */.A)("components-tab-panel__tabs-item", tab.className, {
            [activeClass]: tab.name === selectedTabName
          }),
          disabled: tab.disabled,
          "aria-controls": `${prependInstanceId(tab.name)}-view`,
          render: /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            __next40pxDefaultSize: true,
            icon: tab.icon,
            label: tab.icon && tab.title,
            showTooltip: !!tab.icon
          }),
          children: !tab.icon && tab.title
        }, tab.name);
      })
    }), selectedTab && /* @__PURE__ */ (0,jsx_runtime.jsx)(TabPanel, {
      id: `${prependInstanceId(selectedTab.name)}-view`,
      store: tabStore,
      tabId: prependInstanceId(selectedTab.name),
      className: "components-tab-panel__tab-content",
      children: children(selectedTab)
    })]
  });
};
const tab_panel_TabPanel = (0,react.forwardRef)(UnforwardedTabPanel);
var tab_panel_default = tab_panel_TabPanel;

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

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ usePrevious)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-previous/index.ts

function usePrevious(value) {
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(void 0);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    ref.current = value;
  }, [value]);
  return ref.current;
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ createHigherOrderComponent)
/* harmony export */ });
/* harmony import */ var change_case__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js");
// packages/compose/src/utils/create-higher-order-component/index.ts

function createHigherOrderComponent(mapComponent, modifierName) {
  return (Inner) => {
    const Outer = mapComponent(Inner);
    Outer.displayName = hocName(modifierName, Inner);
    return Outer;
  };
}
var hocName = (name, Inner) => {
  const inner = Inner.displayName || Inner.name || "Component";
  const outer = (0,change_case__WEBPACK_IMPORTED_MODULE_0__/* .pascalCase */ .fL)(name ?? "");
  return `${outer}(${inner})`;
};

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/debounce/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   s: () => (/* binding */ debounce)
/* harmony export */ });
// packages/compose/src/utils/debounce/index.ts
var debounce = (func, wait, options) => {
  let lastArgs;
  let lastThis;
  let maxWait = 0;
  let result;
  let timerId;
  let lastCallTime;
  let lastInvokeTime = 0;
  let leading = false;
  let maxing = false;
  let trailing = true;
  if (options) {
    leading = !!options.leading;
    maxing = "maxWait" in options;
    if (options.maxWait !== void 0) {
      maxWait = Math.max(options.maxWait, wait);
    }
    trailing = "trailing" in options ? !!options.trailing : trailing;
  }
  function invokeFunc(time) {
    const args = lastArgs;
    const thisArg = lastThis;
    lastArgs = void 0;
    lastThis = void 0;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }
  function startTimer(pendingFunc, waitTime) {
    timerId = setTimeout(pendingFunc, waitTime);
  }
  function cancelTimer() {
    if (timerId !== void 0) {
      clearTimeout(timerId);
    }
  }
  function leadingEdge(time) {
    lastInvokeTime = time;
    startTimer(timerExpired, wait);
    return leading ? invokeFunc(time) : result;
  }
  function getTimeSinceLastCall(time) {
    return time - (lastCallTime || 0);
  }
  function remainingWait(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime;
    const timeWaiting = wait - timeSinceLastCall;
    return maxing ? Math.min(timeWaiting, maxWait - timeSinceLastInvoke) : timeWaiting;
  }
  function shouldInvoke(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime;
    return lastCallTime === void 0 || timeSinceLastCall >= wait || timeSinceLastCall < 0 || maxing && timeSinceLastInvoke >= maxWait;
  }
  function timerExpired() {
    const time = Date.now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    startTimer(timerExpired, remainingWait(time));
    return void 0;
  }
  function clearTimer() {
    timerId = void 0;
  }
  function trailingEdge(time) {
    clearTimer();
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = void 0;
    return result;
  }
  function cancel() {
    cancelTimer();
    lastInvokeTime = 0;
    clearTimer();
    lastArgs = lastCallTime = lastThis = void 0;
  }
  function flush() {
    return pending() ? trailingEdge(Date.now()) : result;
  }
  function pending() {
    return timerId !== void 0;
  }
  function debounced(...args) {
    const time = Date.now();
    const isInvoking = shouldInvoke(time);
    lastArgs = args;
    lastThis = this;
    lastCallTime = time;
    if (isInvoking) {
      if (!pending()) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        startTimer(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (!pending()) {
      startTimer(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  debounced.pending = pending;
  return debounced;
};

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

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

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

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/calendar.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ calendar_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var calendar_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z" }) });

//# sourceMappingURL=calendar.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/hoist-non-react-statics@3.3.2/node_modules/hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var reactIs = __webpack_require__("../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/index.js");

/**
 * Copyright 2015, Yahoo! Inc.
 * Copyrights licensed under the New BSD License. See the accompanying LICENSE file for terms.
 */
var REACT_STATICS = {
  childContextTypes: true,
  contextType: true,
  contextTypes: true,
  defaultProps: true,
  displayName: true,
  getDefaultProps: true,
  getDerivedStateFromError: true,
  getDerivedStateFromProps: true,
  mixins: true,
  propTypes: true,
  type: true
};
var KNOWN_STATICS = {
  name: true,
  length: true,
  prototype: true,
  caller: true,
  callee: true,
  arguments: true,
  arity: true
};
var FORWARD_REF_STATICS = {
  '$$typeof': true,
  render: true,
  defaultProps: true,
  displayName: true,
  propTypes: true
};
var MEMO_STATICS = {
  '$$typeof': true,
  compare: true,
  defaultProps: true,
  displayName: true,
  propTypes: true,
  type: true
};
var TYPE_STATICS = {};
TYPE_STATICS[reactIs.ForwardRef] = FORWARD_REF_STATICS;
TYPE_STATICS[reactIs.Memo] = MEMO_STATICS;

function getStatics(component) {
  // React v16.11 and below
  if (reactIs.isMemo(component)) {
    return MEMO_STATICS;
  } // React v16.12 and above


  return TYPE_STATICS[component['$$typeof']] || REACT_STATICS;
}

var defineProperty = Object.defineProperty;
var getOwnPropertyNames = Object.getOwnPropertyNames;
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
var getPrototypeOf = Object.getPrototypeOf;
var objectPrototype = Object.prototype;
function hoistNonReactStatics(targetComponent, sourceComponent, blacklist) {
  if (typeof sourceComponent !== 'string') {
    // don't hoist over string (html) components
    if (objectPrototype) {
      var inheritedComponent = getPrototypeOf(sourceComponent);

      if (inheritedComponent && inheritedComponent !== objectPrototype) {
        hoistNonReactStatics(targetComponent, inheritedComponent, blacklist);
      }
    }

    var keys = getOwnPropertyNames(sourceComponent);

    if (getOwnPropertySymbols) {
      keys = keys.concat(getOwnPropertySymbols(sourceComponent));
    }

    var targetStatics = getStatics(targetComponent);
    var sourceStatics = getStatics(sourceComponent);

    for (var i = 0; i < keys.length; ++i) {
      var key = keys[i];

      if (!KNOWN_STATICS[key] && !(blacklist && blacklist[key]) && !(sourceStatics && sourceStatics[key]) && !(targetStatics && targetStatics[key])) {
        var descriptor = getOwnPropertyDescriptor(sourceComponent, key);

        try {
          // Avoid failures from read-only properties
          defineProperty(targetComponent, key, descriptor);
        } catch (e) {}
      }
    }
  }

  return targetComponent;
}

module.exports = hoistNonReactStatics;


/***/ }),

/***/ "../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   fL: () => (/* binding */ pascalCase)
/* harmony export */ });
/* unused harmony exports pascalCaseTransform, pascalCaseTransformMerge */
/* harmony import */ var tslib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/tslib@2.8.1/node_modules/tslib/tslib.es6.mjs");
/* harmony import */ var no_case__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/no-case@3.0.4/node_modules/no-case/dist.es2015/index.js");


function pascalCaseTransform(input, index) {
    var firstChar = input.charAt(0);
    var lowerChars = input.substr(1).toLowerCase();
    if (index > 0 && firstChar >= "0" && firstChar <= "9") {
        return "_" + firstChar + lowerChars;
    }
    return "" + firstChar.toUpperCase() + lowerChars;
}
function pascalCaseTransformMerge(input) {
    return input.charAt(0).toUpperCase() + input.slice(1).toLowerCase();
}
function pascalCase(input, options) {
    if (options === void 0) { options = {}; }
    return (0,no_case__WEBPACK_IMPORTED_MODULE_0__/* .noCase */ .W)(input, (0,tslib__WEBPACK_IMPORTED_MODULE_1__/* .__assign */ .Cl)({ delimiter: "", transform: pascalCaseTransform }, options));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/cjs/react-is.production.min.js":
/***/ ((__unused_webpack_module, exports) => {

/** @license React v16.13.1
 * react-is.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var b="function"===typeof Symbol&&Symbol.for,c=b?Symbol.for("react.element"):60103,d=b?Symbol.for("react.portal"):60106,e=b?Symbol.for("react.fragment"):60107,f=b?Symbol.for("react.strict_mode"):60108,g=b?Symbol.for("react.profiler"):60114,h=b?Symbol.for("react.provider"):60109,k=b?Symbol.for("react.context"):60110,l=b?Symbol.for("react.async_mode"):60111,m=b?Symbol.for("react.concurrent_mode"):60111,n=b?Symbol.for("react.forward_ref"):60112,p=b?Symbol.for("react.suspense"):60113,q=b?
Symbol.for("react.suspense_list"):60120,r=b?Symbol.for("react.memo"):60115,t=b?Symbol.for("react.lazy"):60116,v=b?Symbol.for("react.block"):60121,w=b?Symbol.for("react.fundamental"):60117,x=b?Symbol.for("react.responder"):60118,y=b?Symbol.for("react.scope"):60119;
function z(a){if("object"===typeof a&&null!==a){var u=a.$$typeof;switch(u){case c:switch(a=a.type,a){case l:case m:case e:case g:case f:case p:return a;default:switch(a=a&&a.$$typeof,a){case k:case n:case t:case r:case h:return a;default:return u}}case d:return u}}}function A(a){return z(a)===m}exports.AsyncMode=l;exports.ConcurrentMode=m;exports.ContextConsumer=k;exports.ContextProvider=h;exports.Element=c;exports.ForwardRef=n;exports.Fragment=e;exports.Lazy=t;exports.Memo=r;exports.Portal=d;
exports.Profiler=g;exports.StrictMode=f;exports.Suspense=p;exports.isAsyncMode=function(a){return A(a)||z(a)===l};exports.isConcurrentMode=A;exports.isContextConsumer=function(a){return z(a)===k};exports.isContextProvider=function(a){return z(a)===h};exports.isElement=function(a){return"object"===typeof a&&null!==a&&a.$$typeof===c};exports.isForwardRef=function(a){return z(a)===n};exports.isFragment=function(a){return z(a)===e};exports.isLazy=function(a){return z(a)===t};
exports.isMemo=function(a){return z(a)===r};exports.isPortal=function(a){return z(a)===d};exports.isProfiler=function(a){return z(a)===g};exports.isStrictMode=function(a){return z(a)===f};exports.isSuspense=function(a){return z(a)===p};
exports.isValidElementType=function(a){return"string"===typeof a||"function"===typeof a||a===e||a===m||a===g||a===f||a===p||a===q||"object"===typeof a&&null!==a&&(a.$$typeof===t||a.$$typeof===r||a.$$typeof===h||a.$$typeof===k||a.$$typeof===n||a.$$typeof===w||a.$$typeof===x||a.$$typeof===y||a.$$typeof===v)};exports.typeOf=z;


/***/ }),

/***/ "../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/cjs/react-is.production.min.js");
} else {}


/***/ })

}]);