"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4377],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/tab-panel/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ tab_panel_default)
});

// UNUSED EXPORTS: TabPanel

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/popover/popover-context.js
var popover_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/popover/popover-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-context.js
var composite_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/combobox/combobox-context.js
"use client";




//#region src/combobox/combobox-context.tsx
const ComboboxListRoleContext = (0,react.createContext)(null);
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
const ComboboxHeadingContext = (0,react.createContext)(null);
//#endregion


//# sourceMappingURL=combobox-context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-store.js + 1 modules
var composite_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-store.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/select/select-context.js
"use client";




//#region src/select/select-context.tsx
const select_context_ctx = (0,dist/* createStoreContext */.B0)([popover_context/* PopoverContextProvider */.wf, composite_context/* CompositeContextProvider */.ws], [popover_context/* PopoverScopedContextProvider */.s1, composite_context/* CompositeScopedContextProvider */.aN]);
/**
* Returns the select store from the nearest select container.
* @deprecated Use
*   [`useComboboxContext`](https://ariakit.com/reference/use-combobox-context)
*   instead.
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
/** @deprecated Use `useComboboxScopedContext` instead. */
const useSelectScopedContext = select_context_ctx.useScopedContext;
/** @deprecated Use `useComboboxProviderContext` instead. */
const useSelectProviderContext = select_context_ctx.useProviderContext;
/** @deprecated Use `ComboboxContextProvider` instead. */
const SelectContextProvider = select_context_ctx.ContextProvider;
/** @deprecated Use `ComboboxScopedContextProvider` instead. */
const SelectScopedContextProvider = select_context_ctx.ScopedContextProvider;
/** @deprecated Use `ComboboxItemCheckedContext` instead. */
const SelectItemCheckedContext = (0,react.createContext)(false);
/** @deprecated Use `ComboboxHeadingContext` instead. */
const SelectHeadingContext = (0,react.createContext)(null);
//#endregion


//# sourceMappingURL=select-context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js
var react_store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/collection/collection-store.js
var collection_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/collection/collection-store.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/composite/composite-store.js
var composite_composite_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/composite/composite-store.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js
var store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js
var utils_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/tab/tab-store.js




//#region src/tab/tab-store.ts
function getFocusedTab(items) {
	const element = items[0]?.element;
	if (!element) return;
	const activeElement = (0,utils_dist/* getActiveElement */.bq)(element);
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
		"compositeElementInFocusOrder",
		"includesBaseElement",
		"compositeElement",
		"baseElement",
		"focusLoop",
		"focusShift",
		"focusWrap",
		"selectOnMove"
	];
	const store = (0,store_dist/* mergeStore */.od)(props.store, (0,store_dist/* omit */.cJ)(parentComposite, independentKeys), (0,store_dist/* omit */.cJ)(combobox, independentKeys));
	const syncState = store?.getState();
	const composite = (0,composite_composite_store/* createCompositeStore */.zf)({
		...props,
		store,
		compositeElementInFocusOrder: (0,utils_dist/* defaultValue */.Jh)(props.compositeElementInFocusOrder, props.includesBaseElement, syncState?.compositeElementInFocusOrder, syncState?.includesBaseElement, false),
		orientation: (0,utils_dist/* defaultValue */.Jh)(props.orientation, syncState?.orientation, "horizontal"),
		focusLoop: (0,utils_dist/* defaultValue */.Jh)(props.focusLoop, syncState?.focusLoop, true)
	});
	const { panels, panel } = createPanelsStore();
	const initialState = {
		...composite.getState(),
		selectedId: (0,utils_dist/* defaultValue */.Jh)(props.selectedId, syncState?.selectedId, props.defaultSelectedId),
		selectOnMove: (0,utils_dist/* defaultValue */.Jh)(props.selectOnMove, syncState?.selectOnMove, true)
	};
	const tab = (0,store_dist/* createStore */.y$)(initialState, composite, store);
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
			if (activeId === state.selectedId) return;
			const focusedTab = getFocusedTab(renderedItems);
			const selectedTab = getTabById(renderedItems, state.selectedId);
			if (focusedTab && isEnabledTab(selectedTab)) {
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
		const isParentCombobox = !!parentComposite && "setSelectedValue" in parentComposite;
		const parentCombobox = isParentCombobox ? parentComposite : combobox;
		if (parentComposite && !isParentCombobox && "setSelectElement" in parentComposite) return (0,utils_dist/* chain */.cy)((0,store_dist/* sync */.OH)(parentComposite, ["value"], backupSelectedId), (0,store_dist/* sync */.OH)(parentComposite, ["mounted"], restoreSelectedId));
		if (!parentCombobox) return;
		return (0,utils_dist/* chain */.cy)((0,store_dist/* sync */.OH)(parentCombobox, ["selectedValue"], backupSelectedId), (0,store_dist/* sync */.OH)(parentCombobox, ["mounted"], restoreSelectedId));
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
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tab/tab-store.js
"use client";







//#region src/tab/tab-store.ts
function useTabStoreProps(store, update, props) {
	(0,dist/* useUpdateEffect */.w5)(update, [
		props.composite,
		props.combobox,
		update
	]);
	const compositeStore = (0,composite_store/* useCompositeStoreProps */.YO)(store, update, props);
	(0,react_store_dist/* useStoreProps */.Tz)(compositeStore, props, "selectedId", "setSelectedId");
	(0,react_store_dist/* useStoreProps */.Tz)(compositeStore, props, "selectOnMove");
	const [panels, updatePanels] = (0,react_store_dist/* useStore */.Pj)(() => compositeStore.panels, {});
	(0,dist/* useUpdateEffect */.w5)(updatePanels, [compositeStore, updatePanels]);
	return Object.assign((0,react.useMemo)(() => ({
		...compositeStore,
		panels
	}), [compositeStore, panels]), {
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
	const storeProps = {
		...props,
		composite: props.composite !== void 0 ? props.composite : composite,
		combobox: props.combobox !== void 0 ? props.combobox : combobox
	};
	const [store, update] = (0,react_store_dist/* useStore */.Pj)(createTabStore, storeProps);
	return useTabStoreProps(store, update, storeProps);
}
//#endregion


//# sourceMappingURL=tab-store.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite.js
var composite = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tab/tab-context.js
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
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tab/tab-list.js
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
	const orientation = (0,react_store_dist/* useStoreState */.O$)(store, ["orientation"], (state) => state.orientation === "both" ? void 0 : state.orientation);
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
	const htmlProps = useTabList(props);
	return (0,dist/* createElement */.n)(TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=tab-list.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/button/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/button/utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-item.js + 1 modules
var composite_item = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-item.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tab/tab.js
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
	const panelId = (0,react_store_dist/* useStoreState */.O$)(store.panels, ["items"], () => store.panel(id)?.id);
	const shouldRegisterItem = defaultId ? props.shouldRegisterItem : false;
	const selected = (0,react_store_dist/* useStoreState */.O$)(store, ["selectedId"], (state) => !!id && state.selectedId === id);
	const canRegisterComposedItem = (0,react_store_dist/* useStoreState */.O$)(store.composite ? store : void 0, [
		"activeId",
		"items",
		"selectedId"
	], (state) => {
		if (!state) return false;
		if (!id) return false;
		if (state.activeId === id) return true;
		if (state.selectedId !== id) return false;
		return !store.item(state.activeId);
	});
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
	const htmlProps = useTab((0,utils/* withDefaultButtonType */.Y)(props));
	return (0,dist/* createElement */.n)(tab_TagName, htmlProps);
}));
//#endregion


//# sourceMappingURL=tab.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js
var focusable = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-item.js
var collection_item = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-item.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-content.js
var disclosure_content = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-content.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-store.js
var disclosure_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-store.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tab/tab-panel.js
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
	const tabId = (0,react_store_dist/* useStoreState */.O$)(tabIdProp ? void 0 : store.panels, ["items"], () => tabIdProp || store?.panels.item(id)?.tabId);
	const open = (0,react_store_dist/* useStoreState */.O$)(store, ["selectedId"], (state) => !!tabId && state.selectedId === tabId);
	const disclosure = (0,disclosure_store/* useDisclosureStore */.E)({ open });
	const mounted = (0,react_store_dist/* useStoreState */.O$)(disclosure, "mounted");
	const scrollPositionRef = (0,react.useRef)(null);
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
		let scrollPositions = scrollPositionRef.current;
		if (!scrollPositions) {
			scrollPositions = /* @__PURE__ */ new Map();
			scrollPositionRef.current = scrollPositions;
		}
		const position = scrollPositions.get(tabId);
		element.scroll(position?.x ?? 0, position?.y ?? 0);
		const onScroll = () => {
			scrollPositions.set(tabId, {
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
	const htmlProps = useTabPanel(props);
	return (0,dist/* createElement */.n)(tab_panel_TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=tab-panel.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs
var use_previous = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.30.0/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.30.0/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/button/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._3e0e7f1259ab8de23e09ab96db165893/node_modules/@wordpress/components/build-module/tab-panel/index.js







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


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ }

}]);