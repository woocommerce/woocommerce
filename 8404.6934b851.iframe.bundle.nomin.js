"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[8404],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ dropdown_default)
/* harmony export */ });
/* unused harmony export Dropdown */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.50.0/node_modules/@wordpress/deprecated/build-module/index.mjs");
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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-context.js
var composite_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/popover/popover-context.js
var popover_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/popover/popover-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/combobox/combobox-context.js
"use client";




//#region src/combobox/combobox-context.tsx
const ComboboxListRoleContext = (0,react.createContext)(void 0);
const ctx = (0,dist/* createStoreContext */.B0)([popover_context/* PopoverContextProvider */.wf, composite_context/* CompositeContextProvider */.ws], [popover_context/* PopoverScopedContextProvider */.s1, composite_context/* CompositeScopedContextProvider */.aN]);
/**
* Returns the combobox store from the nearest combobox container.
* @example
* function Combobox() {
*   const store = useComboboxContext();
*
*   if (!store) {
*     throw new Error("Combobox must be wrapped in ComboboxProvider");
*   }
*
*   // Use the store...
* }
*/
const useComboboxContext = ctx.useContext;
const useComboboxScopedContext = ctx.useScopedContext;
const useComboboxProviderContext = ctx.useProviderContext;
const ComboboxContextProvider = ctx.ContextProvider;
const ComboboxScopedContextProvider = ctx.ScopedContextProvider;
const ComboboxItemValueContext = (0,react.createContext)(void 0);
const ComboboxItemCheckedContext = (0,react.createContext)(false);
//#endregion


//# sourceMappingURL=combobox-context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-store.js + 1 modules
var composite_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-store.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/select/select-context.js
"use client";




//#region src/select/select-context.tsx
const select_context_ctx = (0,dist/* createStoreContext */.B0)([popover_context/* PopoverContextProvider */.wf, composite_context/* CompositeContextProvider */.ws], [popover_context/* PopoverScopedContextProvider */.s1, composite_context/* CompositeScopedContextProvider */.aN]);
/**
* Returns the select store from the nearest select container.
* @example
* function Select() {
*   const store = useSelectContext();
*
*   if (!store) {
*     throw new Error("Select must be wrapped in SelectProvider");
*   }
*
*   // Use the store...
* }
*/
const useSelectContext = select_context_ctx.useContext;
const useSelectScopedContext = select_context_ctx.useScopedContext;
const useSelectProviderContext = select_context_ctx.useProviderContext;
const SelectContextProvider = select_context_ctx.ContextProvider;
const SelectScopedContextProvider = select_context_ctx.ScopedContextProvider;
const SelectItemCheckedContext = (0,react.createContext)(false);
const SelectHeadingContext = (0,react.createContext)(null);
//#endregion


//# sourceMappingURL=select-context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-store@0.1.5_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js
var react_store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.5_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/collection/collection-store.js
var collection_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/collection/collection-store.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/composite/composite-store.js
var composite_composite_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/composite/composite-store.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+store@0.1.4/node_modules/@ariakit/store/dist/index.js
var store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.4/node_modules/@ariakit/store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js
var utils_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/tab/tab-store.js




//#region src/tab/tab-store.ts
function getFocusedTab(items) {
	const activeElement = items[0]?.element?.ownerDocument.activeElement;
	if (!activeElement) return;
	return items.find((item) => item.element === activeElement);
}
function getTabById(items, id) {
	if (id == null) return;
	return items.find((item) => item.id === id);
}
function isEnabledTab(item) {
	if (!item) return false;
	if (item.disabled) return false;
	if (item.dimmed) return false;
	return true;
}
function createPanelsStore() {
	const panels = (0,collection_store/* createCollectionStore */.I)();
	const panelsByTabId = /* @__PURE__ */ new Map();
	(0,store_dist/* sync */.OH)(panels, ["items"], (state) => {
		panelsByTabId.clear();
		for (const panel of state.items) {
			const { tabId } = panel;
			if (tabId == null) continue;
			if (panelsByTabId.has(tabId)) continue;
			panelsByTabId.set(tabId, panel);
		}
	});
	return {
		panels,
		panel: (tabId) => {
			if (tabId == null) return null;
			return panelsByTabId.get(tabId) || null;
		}
	};
}
function createTabStore({ composite: parentComposite, combobox, ...props } = {}) {
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
	const store = (0,store_dist/* mergeStore */.od)(props.store, (0,store_dist/* omit */.cJ)(parentComposite, independentKeys), (0,store_dist/* omit */.cJ)(combobox, independentKeys));
	const syncState = store?.getState();
	const composite = (0,composite_composite_store/* createCompositeStore */.zf)({
		...props,
		store,
		includesBaseElement: (0,utils_dist/* defaultValue */.Jh)(props.includesBaseElement, syncState?.includesBaseElement, false),
		orientation: (0,utils_dist/* defaultValue */.Jh)(props.orientation, syncState?.orientation, "horizontal"),
		focusLoop: (0,utils_dist/* defaultValue */.Jh)(props.focusLoop, syncState?.focusLoop, true)
	});
	const { panels, panel } = createPanelsStore();
	const tab = (0,store_dist/* createStore */.y$)({
		...composite.getState(),
		selectedId: (0,utils_dist/* defaultValue */.Jh)(props.selectedId, syncState?.selectedId, props.defaultSelectedId),
		selectOnMove: (0,utils_dist/* defaultValue */.Jh)(props.selectOnMove, syncState?.selectOnMove, true)
	}, composite, store);
	(0,store_dist/* setup */.mj)(tab, () => (0,store_dist/* sync */.OH)(tab, ["moves"], () => {
		const { activeId, selectOnMove } = tab.getState();
		if (!selectOnMove) return;
		if (!activeId) return;
		const tabItem = composite.item(activeId);
		if (!isEnabledTab(tabItem)) return;
		tab.setState("selectedId", tabItem.id);
	}));
	let pendingRestore = false;
	let restoredSelectedId;
	(0,store_dist/* setup */.mj)(tab, () => {
		pendingRestore = false;
		return (0,store_dist/* batch */.vA)(tab, ["selectedId"], (state, prev) => {
			if (pendingRestore) {
				pendingRestore = false;
				if (state.selectedId === restoredSelectedId) return;
			}
			if (parentComposite && state.selectedId === prev.selectedId) return;
			const { activeId, renderedItems } = tab.getState();
			const focusedTab = getFocusedTab(renderedItems);
			const selectedTab = getTabById(renderedItems, state.selectedId);
			if (focusedTab && isEnabledTab(selectedTab) && activeId !== selectedTab.id) {
				composite.move(selectedTab.id);
				return;
			}
			tab.setState("activeId", state.selectedId);
		});
	});
	(0,store_dist/* setup */.mj)(tab, () => (0,store_dist/* sync */.OH)(tab, ["selectedId", "renderedItems"], (state) => {
		if (state.selectedId !== void 0) return;
		const { activeId, renderedItems } = tab.getState();
		const tabItem = composite.item(activeId);
		if (isEnabledTab(tabItem)) tab.setState("selectedId", tabItem.id);
		else {
			const tabItem = renderedItems.find(isEnabledTab);
			tab.setState("selectedId", tabItem?.id);
		}
	}));
	(0,store_dist/* setup */.mj)(tab, () => (0,store_dist/* sync */.OH)(tab, ["renderedItems"], (state) => {
		const tabs = state.renderedItems;
		if (!tabs.length) return;
		return (0,store_dist/* sync */.OH)(panels, ["renderedItems"], (state) => {
			const items = state.renderedItems;
			if (!items.some((panel) => !panel.tabId)) return;
			items.forEach((panel, i) => {
				if (panel.tabId) return;
				const tabItem = tabs[i];
				if (!tabItem) return;
				panels.renderItem({
					...panel,
					tabId: tabItem.id
				});
			});
		});
	}));
	let selectedIdFromSelectedValue = null;
	(0,store_dist/* setup */.mj)(tab, () => {
		const backupSelectedId = () => {
			selectedIdFromSelectedValue = tab.getState().selectedId;
		};
		const restoreSelectedId = () => {
			const { selectedId } = tab.getState();
			if (selectedId === selectedIdFromSelectedValue) return;
			pendingRestore = true;
			restoredSelectedId = selectedIdFromSelectedValue;
			tab.setState("selectedId", selectedIdFromSelectedValue);
		};
		if (parentComposite && "setSelectElement" in parentComposite) return (0,utils_dist/* chain */.cy)((0,store_dist/* sync */.OH)(parentComposite, ["value"], backupSelectedId), (0,store_dist/* sync */.OH)(parentComposite, ["mounted"], restoreSelectedId));
		if (!combobox) return;
		return (0,utils_dist/* chain */.cy)((0,store_dist/* sync */.OH)(combobox, ["selectedValue"], backupSelectedId), (0,store_dist/* sync */.OH)(combobox, ["mounted"], restoreSelectedId));
	});
	return {
		...composite,
		...tab,
		panels,
		panel,
		setSelectedId: (id) => tab.setState("selectedId", id),
		select: (id) => {
			tab.setState("selectedId", id);
			composite.move(id);
		}
	};
}
//#endregion


//# sourceMappingURL=tab-store.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/tab/tab-store.js
"use client";







//#region src/tab/tab-store.ts
function useTabStoreProps(store, update, props) {
	(0,dist/* useUpdateEffect */.w5)(update, [props.composite, props.combobox]);
	store = (0,composite_store/* useCompositeStoreProps */.YO)(store, update, props);
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "selectedId", "setSelectedId");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "selectOnMove");
	const [panels, updatePanels] = (0,react_store_dist/* useStore */.Pj)(() => store.panels, {});
	(0,dist/* useUpdateEffect */.w5)(updatePanels, [store, updatePanels]);
	return Object.assign((0,react.useMemo)(() => ({
		...store,
		panels
	}), [store, panels]), {
		composite: props.composite,
		combobox: props.combobox
	});
}
/**
* Creates a tab store to control the state of
* [Tab](https://ariakit.com/components/tab) components.
* @see https://ariakit.com/components/tab
* @example
* ```jsx
* const tab = useTabStore();
*
* <TabList store={tab}>
*   <Tab>Tab 1</Tab>
*   <Tab>Tab 2</Tab>
* </TabList>
* <TabPanel store={tab}>Panel 1</TabPanel>
* <TabPanel store={tab}>Panel 2</TabPanel>
* ```
*/
function useTabStore(props = {}) {
	const combobox = useComboboxContext();
	const composite = useSelectContext() || combobox;
	props = {
		...props,
		composite: props.composite !== void 0 ? props.composite : composite,
		combobox: props.combobox !== void 0 ? props.combobox : combobox
	};
	const [store, update] = (0,react_store_dist/* useStore */.Pj)(createTabStore, props);
	return useTabStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=tab-store.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite.js
var composite = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/tab/tab-context.js
"use client";


//#region src/tab/tab-context.tsx
const tab_context_ctx = (0,dist/* createStoreContext */.B0)([composite_context/* CompositeContextProvider */.ws], [composite_context/* CompositeScopedContextProvider */.aN]);
/**
* Returns the tab store from the nearest tab container.
* @example
* function Tab() {
*   const store = useTabContext();
*
*   if (!store) {
*     throw new Error("Tab must be wrapped in TabProvider");
*   }
*
*   // Use the store...
* }
*/
const useTabContext = tab_context_ctx.useContext;
const useTabScopedContext = tab_context_ctx.useScopedContext;
const useTabProviderContext = tab_context_ctx.useProviderContext;
const TabContextProvider = tab_context_ctx.ContextProvider;
const TabScopedContextProvider = tab_context_ctx.ScopedContextProvider;
//#endregion


//# sourceMappingURL=tab-context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/tab/tab-list.js
"use client";






//#region src/tab/tab-list.tsx
const TagName = "div";
/**
* Returns props to create a `TabList` component.
* @see https://ariakit.com/components/tab
* @example
* ```jsx
* const store = useTabStore();
* const props = useTabList({ store });
* <Role {...props}>
*   <Tab>Tab 1</Tab>
*   <Tab>Tab 2</Tab>
* </Role>
* <TabPanel store={store}>Panel 1</TabPanel>
* <TabPanel store={store}>Panel 2</TabPanel>
* ```
*/
const useTabList = (0,dist/* createHook */.ab)(function useTabList({ store, ...props }) {
	const context = useTabProviderContext();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	const orientation = (0,react_store_dist/* useStoreState */.O$)(store, (state) => state.orientation === "both" ? void 0 : state.orientation);
	props = (0,dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, {
		value: store,
		children: element
	}), [store]);
	if (store.composite) props = {
		focusable: false,
		...props
	};
	props = {
		role: "tablist",
		"aria-orientation": orientation,
		...props
	};
	props = (0,composite/* useComposite */.T)({
		store,
		...props
	});
	return props;
});
/**
* Renders a composite tab list wrapper for
* [`Tab`](https://ariakit.com/reference/tab) elements.
* @see https://ariakit.com/components/tab
* @example
* ```jsx {2-5}
* <TabProvider>
*   <TabList>
*     <Tab>Tab 1</Tab>
*     <Tab>Tab 2</Tab>
*   </TabList>
*   <TabPanel>Panel 1</TabPanel>
*   <TabPanel>Panel 2</TabPanel>
* </TabProvider>
* ```
*/
const TabList = (0,dist/* forwardRef */.Rf)(function TabList(props) {
	return (0,dist/* createElement */.n)(TagName, useTabList(props));
});
//#endregion


//# sourceMappingURL=tab-list.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-item.js + 1 modules
var composite_item = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-item.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/tab/tab.js
"use client";







//#region src/tab/tab.tsx
const tab_TagName = "button";
/**
* Returns props to create a `Tab` component.
* @see https://ariakit.com/components/tab
* @example
* ```jsx
* const store = useTabStore();
* const props = useTab({ store });
* <TabList store={store}>
*   <Role {...props}>Tab 1</Role>
* </TabList>
* <TabPanel store={store}>Panel 1</TabPanel>
* ```
*/
const useTab = (0,dist/* createHook */.ab)(function useTab({ store, getItem: getItemProp, ...props }) {
	const context = useTabScopedContext();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	const defaultId = (0,dist/* useId */.Bi)();
	const id = props.id || defaultId;
	const dimmed = (0,utils_dist/* disabledFromProps */.$f)(props);
	const getItem = (0,react.useCallback)((item) => {
		const nextItem = {
			...item,
			dimmed
		};
		if (getItemProp) return getItemProp(nextItem);
		return nextItem;
	}, [dimmed, getItemProp]);
	const onClickProp = props.onClick;
	const onClick = (0,dist/* useEvent */._q)((event) => {
		onClickProp?.(event);
		if (event.defaultPrevented) return;
		store?.setSelectedId(id);
	});
	const panelId = (0,react_store_dist/* useStoreState */.O$)(store.panels, () => store.panel(id)?.id);
	const shouldRegisterItem = defaultId ? props.shouldRegisterItem : false;
	const isActive = (0,react_store_dist/* useStoreState */.O$)(store, (state) => !!id && state.activeId === id);
	const selected = (0,react_store_dist/* useStoreState */.O$)(store, (state) => !!id && state.selectedId === id);
	const hasActiveItem = (0,react_store_dist/* useStoreState */.O$)(store, (state) => !!store.item(state.activeId));
	const canRegisterComposedItem = isActive || selected && !hasActiveItem;
	const accessibleWhenDisabled = selected || (props.accessibleWhenDisabled ?? true);
	if ((0,react_store_dist/* useStoreState */.O$)(store.combobox || store.composite, "virtualFocus")) props = {
		...props,
		tabIndex: -1
	};
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
			render: /* @__PURE__ */ (0,jsx_runtime.jsx)(composite_item/* CompositeItem */.l, {
				...defaultProps,
				render: store.combobox && store.composite !== store.combobox ? /* @__PURE__ */ (0,jsx_runtime.jsx)(composite_item/* CompositeItem */.l, {
					...defaultProps,
					store: store.combobox
				}) : defaultProps.render
			})
		};
	}
	props = (0,composite_item/* useCompositeItem */.k)({
		store,
		...props,
		accessibleWhenDisabled,
		getItem,
		shouldRegisterItem
	});
	return props;
});
/**
* Renders a tab element inside a
* [`TabList`](https://ariakit.com/reference/tab-list) wrapper.
* @see https://ariakit.com/components/tab
* @example
* ```jsx {3,4}
* <TabProvider>
*   <TabList>
*     <Tab>Tab 1</Tab>
*     <Tab>Tab 2</Tab>
*   </TabList>
*   <TabPanel>Panel 1</TabPanel>
*   <TabPanel>Panel 2</TabPanel>
* </TabProvider>
* ```
*/
const Tab = (0,dist/* memo */.ph)((0,dist/* forwardRef */.Rf)(function Tab(props) {
	return (0,dist/* createElement */.n)(tab_TagName, useTab(props));
}));
//#endregion


//# sourceMappingURL=tab.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/focusable/focusable.js
var focusable = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/focusable/focusable.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-item.js
var collection_item = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-item.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/disclosure/disclosure-content.js
var disclosure_content = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/disclosure/disclosure-content.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/disclosure/disclosure-store.js
var disclosure_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/disclosure/disclosure-store.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/tab/tab-panel.js
"use client";










//#region src/tab/tab-panel.tsx
const tab_panel_TagName = "div";
/**
* Returns props to create a `TabPanel` component.
* @see https://ariakit.com/components/tab
* @example
* ```jsx
* const store = useTabStore();
* const props = useTabPanel({ store });
* <TabList store={store}>
*   <Tab>Tab 1</Tab>
* </TabList>
* <Role {...props}>Panel 1</Role>
* ```
*/
const useTabPanel = (0,dist/* createHook */.ab)(function useTabPanel({ store, unmountOnHide, tabId: tabIdProp, getItem: getItemProp, scrollRestoration, scrollElement, ...props }) {
	const context = useTabProviderContext();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	const ref = (0,react.useRef)(null);
	const id = (0,dist/* useId */.Bi)(props.id);
	const tabId = (0,react_store_dist/* useStoreState */.O$)(store.panels, () => tabIdProp || store?.panels.item(id)?.tabId);
	const disclosure = (0,disclosure_store/* useDisclosureStore */.E)({ open: (0,react_store_dist/* useStoreState */.O$)(store, (state) => !!tabId && state.selectedId === tabId) });
	const mounted = (0,react_store_dist/* useStoreState */.O$)(disclosure, "mounted");
	const scrollPositionRef = (0,react.useRef)(/* @__PURE__ */ new Map());
	const getScrollElement = (0,dist/* useEvent */._q)(() => {
		const panelElement = ref.current;
		if (!panelElement) return null;
		if (!scrollElement) return panelElement;
		if (typeof scrollElement === "function") return scrollElement(panelElement);
		if ("current" in scrollElement) return scrollElement.current;
		return scrollElement;
	});
	(0,react.useEffect)(() => {
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
		element.scroll(position?.x ?? 0, position?.y ?? 0);
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
	}, [
		scrollRestoration,
		mounted,
		tabId,
		getScrollElement
	]);
	const [hasTabbableChildren, setHasTabbableChildren] = (0,react.useState)(false);
	(0,dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!mounted) return;
		const element = ref.current;
		if (!element) return;
		setHasTabbableChildren(!!(0,utils_dist/* getFirstTabbableIn */.LC)(element));
	}, [mounted, tabId]);
	const getItem = (0,react.useCallback)((item) => {
		const nextItem = {
			...item,
			id: id || item.id,
			tabId: tabIdProp
		};
		if (getItemProp) return getItemProp(nextItem);
		return nextItem;
	}, [
		id,
		tabIdProp,
		getItemProp
	]);
	const onKeyDownProp = props.onKeyDown;
	const onKeyDown = (0,dist/* useEvent */._q)((event) => {
		onKeyDownProp?.(event);
		if (event.defaultPrevented) return;
		if (!store?.composite) return;
		const action = {
			ArrowLeft: store.previous,
			ArrowRight: store.next,
			Home: store.first,
			End: store.last
		}[event.key];
		if (!action) return;
		const { selectedId } = store.getState();
		const nextId = action({ activeId: selectedId });
		if (!nextId) return;
		event.preventDefault();
		store.move(nextId);
	});
	props = (0,dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, {
		value: store,
		children: element
	}), [store]);
	props = {
		role: "tabpanel",
		"aria-labelledby": props["aria-label"] != null ? void 0 : tabId || void 0,
		...props,
		id,
		children: unmountOnHide && !mounted ? null : props.children,
		ref: (0,dist/* useMergeRefs */.SV)(ref, props.ref),
		onKeyDown
	};
	props = (0,focusable/* useFocusable */.W)({
		focusable: !store.composite && !hasTabbableChildren,
		...props
	});
	props = (0,disclosure_content/* useDisclosureContent */.aT)({
		store: disclosure,
		...props
	});
	props = (0,collection_item/* useCollectionItem */.v)({
		store: store.panels,
		...props,
		getItem
	});
	return props;
});
/**
* Renders a tab panel element that's controlled by a
* [`Tab`](https://ariakit.com/reference/tab) component.
*
* If the [`tabId`](https://ariakit.com/reference/tab-panel#tabid) prop isn't
* provided, the tab panel will automatically associate with a
* [`Tab`](https://ariakit.com/reference/tab) based on its position in the DOM.
* Alternatively, you can render a single tab panel with a dynamic
* [`tabId`](https://ariakit.com/reference/tab-panel#tabid) value pointing to
* the selected tab.
* @see https://ariakit.com/components/tab
* @example
* ```jsx {6,7}
* <TabProvider>
*   <TabList>
*     <Tab>Tab 1</Tab>
*     <Tab>Tab 2</Tab>
*   </TabList>
*   <TabPanel>Panel 1</TabPanel>
*   <TabPanel>Panel 2</TabPanel>
* </TabProvider>
* ```
*/
const TabPanel = (0,dist/* forwardRef */.Rf)(function TabPanel(props) {
	return (0,dist/* createElement */.n)(tab_panel_TagName, useTabPanel(props));
});
//#endregion


//# sourceMappingURL=tab-panel.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs
var use_previous = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs");
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
  const selectedTabName = extractTabName(react_store_dist/* useStoreState */.O$(tabStore, "selectedId"));
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

/***/ "../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  __: () => (/* reexport */ __),
  _n: () => (/* reexport */ _n),
  _x: () => (/* reexport */ _x),
  V8: () => (/* reexport */ isRTL),
  nv: () => (/* reexport */ sprintf)
});

// UNUSED EXPORTS: _nx, createI18n, defaultI18n, getLocaleData, hasTranslation, resetLocaleData, setLocaleData, subscribe

// EXTERNAL MODULE: ../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js
var src = __webpack_require__("../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/sprintf.js

function sprintf(format, ...args) {
  return (0,src/* default */.A)(format, ...args);
}

//# sourceMappingURL=sprintf.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js + 4 modules
var node_modules_tannin = __webpack_require__("../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/create-i18n.js

const DEFAULT_LOCALE_DATA = {
  "": {
    plural_forms(n) {
      return n === 1 ? 0 : 1;
    }
  }
};
const I18N_HOOK_REGEXP = /^i18n\.(n?gettext|has_translation)(_|$)/;
const createI18n = (initialData, initialDomain, hooks) => {
  const tannin = new node_modules_tannin/* default */.A({});
  const listeners = /* @__PURE__ */ new Set();
  const notifyListeners = () => {
    listeners.forEach((listener) => listener());
  };
  const subscribe = (callback) => {
    listeners.add(callback);
    return () => listeners.delete(callback);
  };
  const getLocaleData = (domain = "default") => tannin.data[domain];
  const doSetLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data
    };
    tannin.data[domain][""] = {
      ...DEFAULT_LOCALE_DATA[""],
      ...tannin.data[domain]?.[""]
    };
    delete tannin.pluralForms[domain];
  };
  const setLocaleData = (data, domain) => {
    doSetLocaleData(data, domain);
    notifyListeners();
  };
  const addLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data,
      // Populate default domain configuration (supported locale date which omits
      // a plural forms expression).
      "": {
        ...DEFAULT_LOCALE_DATA[""],
        ...tannin.data[domain]?.[""],
        ...data?.[""]
      }
    };
    delete tannin.pluralForms[domain];
    notifyListeners();
  };
  const resetLocaleData = (data, domain) => {
    tannin.data = {};
    tannin.pluralForms = {};
    setLocaleData(data, domain);
  };
  const dcnpgettext = (domain = "default", context, single, plural, number) => {
    if (!tannin.data[domain]) {
      doSetLocaleData(void 0, domain);
    }
    return tannin.dcnpgettext(domain, context, single, plural, number);
  };
  const getFilterDomain = (domain) => domain || "default";
  const __ = (text, domain) => {
    let translation = dcnpgettext(domain, void 0, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext",
      translation,
      text,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_" + getFilterDomain(domain),
      translation,
      text,
      domain
    );
  };
  const _x = (text, context, domain) => {
    let translation = dcnpgettext(domain, context, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext_with_context",
      translation,
      text,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_with_context_" + getFilterDomain(domain),
      translation,
      text,
      context,
      domain
    );
  };
  const _n = (single, plural, number, domain) => {
    let translation = dcnpgettext(
      domain,
      void 0,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext",
      translation,
      single,
      plural,
      number,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      domain
    );
  };
  const _nx = (single, plural, number, context, domain) => {
    let translation = dcnpgettext(
      domain,
      context,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext_with_context",
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_with_context_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
  };
  const isRTL = () => {
    return "rtl" === _x("ltr", "text direction");
  };
  const hasTranslation = (single, context, domain) => {
    const key = context ? context + "" + single : single;
    let result = !!tannin.data?.[domain ?? "default"]?.[key];
    if (hooks) {
      result = hooks.applyFilters(
        "i18n.has_translation",
        result,
        single,
        context,
        domain
      );
      result = hooks.applyFilters(
        "i18n.has_translation_" + getFilterDomain(domain),
        result,
        single,
        context,
        domain
      );
    }
    return result;
  };
  if (initialData) {
    setLocaleData(initialData, initialDomain);
  }
  if (hooks) {
    const onHookAddedOrRemoved = (hookName) => {
      if (I18N_HOOK_REGEXP.test(hookName)) {
        notifyListeners();
      }
    };
    hooks.addAction("hookAdded", "core/i18n", onHookAddedOrRemoved);
    hooks.addAction("hookRemoved", "core/i18n", onHookAddedOrRemoved);
  }
  return {
    getLocaleData,
    setLocaleData,
    addLocaleData,
    resetLocaleData,
    subscribe,
    __,
    _x,
    _n,
    _nx,
    isRTL,
    hasTranslation
  };
};

//# sourceMappingURL=create-i18n.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs + 10 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/default-i18n.js


const i18n = createI18n(void 0, void 0, build_module/* defaultHooks */.se);
var default_i18n_default = (/* unused pure expression or super */ null && (i18n));
const getLocaleData = i18n.getLocaleData.bind(i18n);
const setLocaleData = i18n.setLocaleData.bind(i18n);
const resetLocaleData = i18n.resetLocaleData.bind(i18n);
const subscribe = i18n.subscribe.bind(i18n);
const __ = i18n.__.bind(i18n);
const _x = i18n._x.bind(i18n);
const _n = i18n._n.bind(i18n);
const _nx = i18n._nx.bind(i18n);
const isRTL = i18n.isRTL.bind(i18n);
const hasTranslation = i18n.hasTranslation.bind(i18n);

//# sourceMappingURL=default-i18n.js.map

;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js




//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js":
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

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/calendar.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ calendar_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


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