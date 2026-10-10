"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[8519],{

/***/ "../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/collection/collection-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   I: () => (/* binding */ createCollectionStore)
/* harmony export */ });
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.4/node_modules/@ariakit/store/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js");


//#region src/collection/collection-store.ts
function getCommonParent(items) {
	const firstItem = items.find((item) => !!item.element);
	const lastElement = [...items].reverse().find((item) => !!item.element)?.element;
	let parentElement = firstItem?.element?.parentElement;
	if (!lastElement) return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .YE)(parentElement).body;
	while (parentElement) {
		if (parentElement.contains(lastElement)) return parentElement;
		parentElement = parentElement.parentElement;
	}
	return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .YE)(parentElement).body;
}
function getPrivateStore(store) {
	return store?.__unstablePrivateStore;
}
/**
* Creates a collection store.
*/
function createCollectionStore(props = {}) {
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .throwOnConflictingProps */ .UE)(props, props.store);
	const syncState = props.store?.getState();
	const items = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .defaultValue */ .Jh)(props.items, syncState?.items, props.defaultItems, []);
	const itemsMap = new Map(items.map((item) => [item.id, item]));
	const initialState = {
		items,
		renderedItems: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .defaultValue */ .Jh)(syncState?.renderedItems, [])
	};
	const syncPrivateStore = getPrivateStore(props.store);
	const privateStore = (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .createStore */ .y$)({
		items,
		renderedItems: initialState.renderedItems
	}, syncPrivateStore);
	const collection = (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .createStore */ .y$)(initialState, props.store);
	const sortItems = (renderedItems) => {
		const sortedItems = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .sortBasedOnDOMPosition */ .gH)(renderedItems, (i) => i.element);
		privateStore.setState("renderedItems", sortedItems);
		collection.setState("renderedItems", sortedItems);
	};
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(collection, () => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .init */ .Ts)(privateStore));
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(privateStore, () => {
		return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .batch */ .vA)(privateStore, ["items"], (state) => {
			collection.setState("items", state.items);
		});
	});
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(privateStore, () => {
		return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .batch */ .vA)(privateStore, ["renderedItems"], (state) => {
			let firstRun = true;
			let raf = requestAnimationFrame(() => {
				const { renderedItems } = collection.getState();
				if (state.renderedItems === renderedItems) return;
				sortItems(state.renderedItems);
			});
			if (typeof IntersectionObserver !== "function") return () => cancelAnimationFrame(raf);
			const ioCallback = () => {
				if (firstRun) {
					firstRun = false;
					return;
				}
				cancelAnimationFrame(raf);
				raf = requestAnimationFrame(() => sortItems(state.renderedItems));
			};
			const root = getCommonParent(state.renderedItems);
			const observer = new IntersectionObserver(ioCallback, { root });
			for (const item of state.renderedItems) {
				if (!item.element) continue;
				observer.observe(item.element);
			}
			return () => {
				cancelAnimationFrame(raf);
				observer.disconnect();
			};
		});
	});
	const mergeItem = (item, setItems, canDeleteFromMap = false) => {
		let prevItem;
		setItems((items) => {
			const index = items.findIndex(({ id }) => id === item.id);
			const nextItems = items.slice();
			if (index !== -1) {
				prevItem = items[index];
				const nextItem = {
					...prevItem,
					...item
				};
				nextItems[index] = nextItem;
				itemsMap.set(item.id, nextItem);
			} else {
				nextItems.push(item);
				itemsMap.set(item.id, item);
			}
			return nextItems;
		});
		const unmergeItem = () => {
			setItems((items) => {
				if (!prevItem) {
					if (canDeleteFromMap) itemsMap.delete(item.id);
					return items.filter(({ id }) => id !== item.id);
				}
				const index = items.findIndex(({ id }) => id === item.id);
				if (index === -1) return items;
				const nextItems = items.slice();
				nextItems[index] = prevItem;
				itemsMap.set(item.id, prevItem);
				return nextItems;
			});
		};
		return unmergeItem;
	};
	const registerItem = (item) => mergeItem(item, (getItems) => privateStore.setState("items", getItems), true);
	return {
		...collection,
		registerItem,
		renderItem: (item) => (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .chain */ .cy)(registerItem(item), mergeItem(item, (getItems) => privateStore.setState("renderedItems", getItems))),
		item: (id) => {
			if (!id) return null;
			let item = itemsMap.get(id);
			if (!item) {
				const { items } = privateStore.getState();
				item = items.find((item) => item.id === id);
				if (item) itemsMap.set(id, item);
			}
			return item || null;
		},
		__unstablePrivateStore: privateStore
	};
}
//#endregion


//# sourceMappingURL=collection-store.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/composite/composite-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _d: () => (/* binding */ flipItems),
/* harmony export */   es: () => (/* binding */ groupItemsByRows),
/* harmony export */   oi: () => (/* binding */ findFirstEnabledItem),
/* harmony export */   zf: () => (/* binding */ createCompositeStore)
/* harmony export */ });
/* unused harmony export NULL_ITEM */
/* harmony import */ var _collection_collection_store_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/collection/collection-store.js");
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.4/node_modules/@ariakit/store/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js");



//#region src/composite/composite-store.ts
const NULL_ITEM = { id: null };
/**
* Finds the first enabled item.
*/
function findFirstEnabledItem(items, excludeId) {
	return items.find((item) => {
		if (excludeId) return !item.disabled && item.id !== excludeId;
		return !item.disabled;
	});
}
function getEnabledItems(items, excludeId) {
	return items.filter((item) => {
		if (excludeId) return !item.disabled && item.id !== excludeId;
		return !item.disabled;
	});
}
function getItemsInRow(items, rowId) {
	return items.filter((item) => item.rowId === rowId);
}
function findEnabledItemId({ items, fromIndex, step, rowId, excludeId }) {
	for (let i = fromIndex; i >= 0 && i < items.length; i += step) {
		const item = items[i];
		if (!item) continue;
		if (item.rowId !== rowId) continue;
		if (item.disabled) continue;
		if (excludeId != null && item.id === excludeId) continue;
		return item.id;
	}
}
/**
* Moves all the items before the passed `id` to the end of the array. This is
* useful when we want to loop through the items in the same row or column as
* the first items will be placed after the last items.
*
* The null item that's inserted when `shouldInsertNullItem` is set to `true`
* represents the composite container itself. When the active item is null, the
* composite container has focus.
*/
function flipItems(items, activeId, shouldInsertNullItem = false) {
	const index = items.findIndex((item) => item.id === activeId);
	return [
		...items.slice(index + 1),
		...shouldInsertNullItem ? [NULL_ITEM] : [],
		...items.slice(0, index)
	];
}
/**
* Creates a two-dimensional array with items grouped by their rowId's.
*/
function groupItemsByRows(items) {
	const rows = [];
	for (const item of items) {
		const row = rows.find((currentRow) => currentRow[0]?.rowId === item.rowId);
		if (row) row.push(item);
		else rows.push([item]);
	}
	return rows;
}
function getMaxRowLength(array) {
	let maxLength = 0;
	for (const { length } of array) if (length > maxLength) maxLength = length;
	return maxLength;
}
function createEmptyItem(rowId) {
	return {
		id: "__EMPTY_ITEM__",
		disabled: true,
		rowId
	};
}
function normalizeRows(rows, activeId, focusShift) {
	const maxLength = getMaxRowLength(rows);
	for (const row of rows) for (let i = 0; i < maxLength; i += 1) {
		const item = row[i];
		if (!item || focusShift && item.disabled) {
			const previousItem = i === 0 && focusShift ? findFirstEnabledItem(row) : row[i - 1];
			row[i] = previousItem && activeId !== previousItem.id && focusShift ? previousItem : createEmptyItem(previousItem?.rowId);
		}
	}
	return rows;
}
function verticalizeItems(items) {
	const rows = groupItemsByRows(items);
	const maxLength = getMaxRowLength(rows);
	const verticalized = [];
	for (let i = 0; i < maxLength; i += 1) for (const row of rows) {
		const item = row[i];
		if (item) verticalized.push({
			...item,
			rowId: item.rowId ? `${i}` : void 0
		});
	}
	return verticalized;
}
/**
* Creates a composite store.
*/
function createCompositeStore(props = {}) {
	const syncState = props.store?.getState();
	const collection = (0,_collection_collection_store_js__WEBPACK_IMPORTED_MODULE_0__/* .createCollectionStore */ .I)(props);
	const activeId = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.activeId, syncState?.activeId, props.defaultActiveId);
	const composite = (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .createStore */ .y$)({
		...collection.getState(),
		id: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.id, syncState?.id) ?? `id-${Math.random().toString(36).slice(2, 8)}`,
		activeId,
		baseElement: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState?.baseElement, null),
		includesBaseElement: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.includesBaseElement, syncState?.includesBaseElement, activeId === null),
		moves: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState?.moves, 0),
		orientation: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.orientation, syncState?.orientation, "both"),
		rtl: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.rtl, syncState?.rtl, false),
		virtualFocus: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.virtualFocus, syncState?.virtualFocus, false),
		focusLoop: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusLoop, syncState?.focusLoop, false),
		focusWrap: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusWrap, syncState?.focusWrap, false),
		focusShift: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusShift, syncState?.focusShift, false)
	}, collection, props.store);
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .setup */ .mj)(composite, () => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .sync */ .OH)(composite, ["renderedItems", "activeId"], (state) => {
		composite.setState("activeId", (activeId) => {
			if (activeId !== void 0) return activeId;
			return findFirstEnabledItem(state.renderedItems)?.id;
		});
	}));
	const getNextId = (direction = "next", options = {}) => {
		const defaultState = composite.getState();
		const { skip = 0, activeId = defaultState.activeId, focusShift = defaultState.focusShift, focusLoop = defaultState.focusLoop, focusWrap = defaultState.focusWrap, includesBaseElement = defaultState.includesBaseElement, renderedItems = defaultState.renderedItems, rtl = defaultState.rtl } = options;
		const isVerticalDirection = direction === "up" || direction === "down";
		const isNextDirection = direction === "next" || direction === "down";
		const canReverse = isNextDirection ? rtl && !isVerticalDirection : !rtl || isVerticalDirection;
		const canShift = focusShift && !skip;
		if (!skip && !focusWrap && !includesBaseElement && activeId != null) {
			if (!isVerticalDirection ? true : !canShift && !renderedItems.some((item) => item.rowId != null)) {
				const activeIndex = renderedItems.findIndex((item) => item.id === activeId);
				const activeItem = renderedItems[activeIndex];
				if (activeItem) {
					const step = canReverse ? -1 : 1;
					const nextId = findEnabledItemId({
						items: renderedItems,
						fromIndex: activeIndex + step,
						step,
						rowId: activeItem.rowId,
						excludeId: activeId
					});
					if (nextId !== void 0) return nextId;
					if (!(focusLoop && (isVerticalDirection ? focusLoop !== "horizontal" : focusLoop !== "vertical"))) return void 0;
					return findEnabledItemId({
						items: renderedItems,
						fromIndex: step === 1 ? 0 : renderedItems.length - 1,
						step,
						rowId: activeItem.rowId,
						excludeId: activeId
					});
				}
			}
		}
		let items = !isVerticalDirection ? renderedItems : (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .flatten2DArray */ .M9)(normalizeRows(groupItemsByRows(renderedItems), activeId, canShift));
		items = canReverse ? (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .reverseArray */ .q7)(items) : items;
		items = isVerticalDirection ? verticalizeItems(items) : items;
		if (activeId == null) return findFirstEnabledItem(items)?.id;
		const activeItem = items.find((item) => item.id === activeId);
		if (!activeItem) return findFirstEnabledItem(items)?.id;
		const isGrid = items.some((item) => item.rowId);
		const activeIndex = items.indexOf(activeItem);
		const nextItems = items.slice(activeIndex + 1);
		const nextItemsInRow = getItemsInRow(nextItems, activeItem.rowId);
		if (skip) {
			const nextEnabledItemsInRow = getEnabledItems(nextItemsInRow, activeId);
			return (nextEnabledItemsInRow.slice(skip)[0] || nextEnabledItemsInRow[nextEnabledItemsInRow.length - 1])?.id;
		}
		const canLoop = focusLoop && (isVerticalDirection ? focusLoop !== "horizontal" : focusLoop !== "vertical");
		const canWrap = isGrid && focusWrap && (isVerticalDirection ? focusWrap !== "horizontal" : focusWrap !== "vertical");
		const hasNullItem = isNextDirection ? (!isGrid || isVerticalDirection) && canLoop && includesBaseElement : isVerticalDirection ? includesBaseElement : false;
		if (canLoop) return findFirstEnabledItem(flipItems(canWrap && !hasNullItem ? items : getItemsInRow(items, activeItem.rowId), activeId, hasNullItem), activeId)?.id;
		if (canWrap) {
			const nextItem = findFirstEnabledItem(hasNullItem ? nextItemsInRow : nextItems, activeId);
			return hasNullItem ? nextItem?.id || null : nextItem?.id;
		}
		const nextItem = findFirstEnabledItem(nextItemsInRow, activeId);
		if (!nextItem && hasNullItem) return null;
		return nextItem?.id;
	};
	const getNextIdFromOptions = (direction, options) => {
		if (typeof options === "number") return getNextId(direction, { skip: options });
		return getNextId(direction, options);
	};
	return {
		...collection,
		...composite,
		setBaseElement: (element) => composite.setState("baseElement", element),
		setActiveId: (id) => composite.setState("activeId", id),
		move: (id) => {
			if (id === void 0) return;
			composite.setState("activeId", id);
			composite.setState("moves", (moves) => moves + 1);
		},
		first: () => findFirstEnabledItem(composite.getState().renderedItems)?.id,
		last: () => findFirstEnabledItem((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .reverseArray */ .q7)(composite.getState().renderedItems))?.id,
		next: (options) => getNextIdFromOptions("next", options),
		previous: (options) => getNextIdFromOptions("previous", options),
		down: (options) => getNextIdFromOptions("down", options),
		up: (options) => getNextIdFromOptions("up", options)
	};
}
//#endregion


//# sourceMappingURL=composite-store.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $I: () => (/* binding */ useCollectionContext),
/* harmony export */   LN: () => (/* binding */ CollectionContextProvider),
/* harmony export */   zX: () => (/* binding */ CollectionScopedContextProvider)
/* harmony export */ });
/* unused harmony exports useCollectionProviderContext, useCollectionScopedContext */
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
"use client";

//#region src/collection/collection-context.tsx
const ctx = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__/* .createStoreContext */ .B0)();
/**
* Returns the collection store from the nearest collection container.
* @example
* function CollectionItem() {
*   const store = useCollectionContext();
*
*   if (!store) {
*     throw new Error("CollectionItem must be wrapped in CollectionProvider");
*   }
*
*   // Use the store...
* }
*/
const useCollectionContext = ctx.useContext;
const useCollectionScopedContext = ctx.useScopedContext;
const useCollectionProviderContext = ctx.useProviderContext;
const CollectionContextProvider = ctx.ContextProvider;
const CollectionScopedContextProvider = ctx.ScopedContextProvider;
//#endregion


//# sourceMappingURL=collection-context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-item.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   v: () => (/* binding */ useCollectionItem)
/* harmony export */ });
/* unused harmony export CollectionItem */
/* harmony import */ var _collection_context_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";




//#region src/collection/collection-item.tsx
const TagName = "div";
/**
* Returns props to create a `CollectionItem` component. This hook will register
* the item in the collection store. If this hook is used in a component that is
* wrapped by `Collection` or a component that implements `useCollection`,
* there's no need to explicitly pass the `store` prop.
* @see https://ariakit.com/components/collection
* @example
* ```jsx
* const store = useCollectionStore();
* const props = useCollectionItem({ store });
* <Role {...props}>Item</Role>
* ```
*/
const useCollectionItem = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .createHook */ .ab)(function useCollectionItem({ store, shouldRegisterItem = true, getItem = _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .identity */ .D_, element, ...props }) {
	const context = (0,_collection_context_js__WEBPACK_IMPORTED_MODULE_3__/* .useCollectionContext */ .$I)();
	store = store || context;
	const id = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .useId */ .Bi)(props.id);
	const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(element);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		const element = ref.current;
		if (!id) return;
		if (!element) return;
		if (!shouldRegisterItem) return;
		const item = getItem({
			id,
			element
		});
		return store?.renderItem(item);
	}, [
		id,
		shouldRegisterItem,
		getItem,
		store
	]);
	props = {
		...props,
		ref: (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .useMergeRefs */ .SV)(ref, props.ref)
	};
	return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .removeUndefinedValues */ .HR)(props);
});
/**
* Renders an item in a collection. The collection store can be passed
* explicitly through the
* [`store`](https://ariakit.com/reference/collection-item#store) prop or
* implicitly through the parent
* [`Collection`](https://ariakit.com/reference/collection) component.
* @see https://ariakit.com/components/collection
* @example
* ```jsx
* const store = useCollectionStore();
* <CollectionItem store={store}>Item 1</CollectionItem>
* <CollectionItem store={store}>Item 2</CollectionItem>
* <CollectionItem store={store}>Item 3</CollectionItem>
* ```
*/
const CollectionItem = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .forwardRef */ .Rf)(function CollectionItem(props) {
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .createElement */ .n)(TagName, useCollectionItem(props));
});
//#endregion


//# sourceMappingURL=collection-item.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $o: () => (/* binding */ CompositeRowContext),
/* harmony export */   Lf: () => (/* binding */ useCompositeProviderContext),
/* harmony export */   SZ: () => (/* binding */ useCompositeScopedContext),
/* harmony export */   U0: () => (/* binding */ CompositeItemContext),
/* harmony export */   aN: () => (/* binding */ CompositeScopedContextProvider),
/* harmony export */   ws: () => (/* binding */ CompositeContextProvider)
/* harmony export */ });
/* unused harmony export useCompositeContext */
/* harmony import */ var _collection_collection_context_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";



//#region src/composite/composite-context.tsx
const ctx = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .createStoreContext */ .B0)([_collection_collection_context_js__WEBPACK_IMPORTED_MODULE_2__/* .CollectionContextProvider */ .LN], [_collection_collection_context_js__WEBPACK_IMPORTED_MODULE_2__/* .CollectionScopedContextProvider */ .zX]);
/**
* Returns the composite store from the nearest composite container.
* @example
* function CompositeItem() {
*   const store = useCompositeContext();
*
*   if (!store) {
*     throw new Error("CompositeItem must be wrapped in CompositeProvider");
*   }
*
*   // Use the store...
* }
*/
const useCompositeContext = ctx.useContext;
const useCompositeScopedContext = ctx.useScopedContext;
const useCompositeProviderContext = ctx.useProviderContext;
const CompositeContextProvider = ctx.ContextProvider;
const CompositeScopedContextProvider = ctx.ScopedContextProvider;
const CompositeItemContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(void 0);
const CompositeRowContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(void 0);
//#endregion


//# sourceMappingURL=composite-context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-item.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  l: () => (/* binding */ CompositeItem),
  k: () => (/* binding */ useCompositeItem)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/focusable/focusable.js
var focusable = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/focusable/focusable.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js
var utils_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/command/command.js
"use client";




//#region src/command/command.tsx
const TagName = "button";
function isNativeClick(event) {
	if (!event.isTrusted) return false;
	// istanbul ignore next: can't test trusted events yet
	const element = event.currentTarget;
	if (event.key === "Enter") return (0,utils_dist/* isButton */.Bm)(element) || element.tagName === "SUMMARY" || element.tagName === "A";
	if (event.key === " ") return (0,utils_dist/* isButton */.Bm)(element) || element.tagName === "SUMMARY" || element.tagName === "INPUT" || element.tagName === "SELECT";
	return false;
}
const symbol = Symbol("command");
/**
* Returns props to create a `Command` component. If the element is not a native
* clickable element (like a button), this hook will return additional props to
* make sure it's accessible.
* @see https://ariakit.com/components/command
* @example
* ```jsx
* const props = useCommand({ render: <div /> });
* <Role {...props}>Accessible button</Role>
* ```
*/
const useCommand = (0,dist/* createHook */.ab)(function useCommand({ clickOnEnter = true, clickOnSpace = true, ...props }) {
	const ref = (0,react.useRef)(null);
	const [isNativeButton, setIsNativeButton] = (0,react.useState)(false);
	(0,react.useEffect)(() => {
		if (!ref.current) return;
		setIsNativeButton((0,utils_dist/* isButton */.Bm)(ref.current));
	}, []);
	const [active, setActive] = (0,react.useState)(false);
	const activeRef = (0,react.useRef)(false);
	const disabled = (0,utils_dist/* disabledFromProps */.$f)(props);
	const [isDuplicate, metadataProps] = (0,dist/* useMetadataProps */.P1)(props, symbol, true);
	(0,dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!disabled) return;
		activeRef.current = false;
		setActive(false);
	}, [disabled]);
	const onKeyDownProp = props.onKeyDown;
	const onKeyDown = (0,dist/* useEvent */._q)((event) => {
		onKeyDownProp?.(event);
		const element = event.currentTarget;
		if (event.defaultPrevented) return;
		if (isDuplicate) return;
		if (disabled) return;
		if (!(0,utils_dist/* isSelfTarget */.uh)(event)) return;
		if ((0,utils_dist/* isTextField */.mB)(element)) return;
		if (element.isContentEditable) return;
		const isEnter = clickOnEnter && event.key === "Enter";
		const isSpace = clickOnSpace && event.key === " ";
		const shouldPreventEnter = event.key === "Enter" && !clickOnEnter;
		const shouldPreventSpace = event.key === " " && !clickOnSpace;
		if (shouldPreventEnter || shouldPreventSpace) {
			event.preventDefault();
			return;
		}
		if (isEnter || isSpace) {
			const nativeClick = isNativeClick(event);
			if (isEnter) {
				if (!nativeClick) {
					event.preventDefault();
					const { view, ...eventInit } = event;
					const click = () => (0,utils_dist/* fireClickEvent */.hY)(element, eventInit);
					if ((0,utils_dist/* isFirefox */.gm)()) (0,utils_dist/* queueBeforeEvent */.nz)(element, "keyup", click);
					else queueMicrotask(click);
				}
			} else if (isSpace) {
				activeRef.current = true;
				if (!nativeClick) {
					event.preventDefault();
					setActive(true);
				}
			}
		}
	});
	const onKeyUpProp = props.onKeyUp;
	const onKeyUp = (0,dist/* useEvent */._q)((event) => {
		onKeyUpProp?.(event);
		if (isDuplicate) return;
		const isSpace = clickOnSpace && event.key === " ";
		if (!activeRef.current || !isSpace) return;
		const nativeClick = isNativeClick(event);
		activeRef.current = false;
		if (!nativeClick) setActive(false);
		if (event.defaultPrevented) return;
		if (!(0,utils_dist/* isSelfTarget */.uh)(event)) return;
		if (disabled) return;
		if (event.metaKey) return;
		if (nativeClick) return;
		event.preventDefault();
		const element = event.currentTarget;
		const { view, ...eventInit } = event;
		queueMicrotask(() => (0,utils_dist/* fireClickEvent */.hY)(element, eventInit));
	});
	const onBlurProp = props.onBlur;
	const onBlur = (0,dist/* useEvent */._q)((event) => {
		onBlurProp?.(event);
		if (!activeRef.current) return;
		activeRef.current = false;
		setActive(false);
	});
	props = {
		"data-active": active || void 0,
		type: isNativeButton ? "button" : void 0,
		...metadataProps,
		...props,
		ref: (0,dist/* useMergeRefs */.SV)(ref, props.ref),
		onKeyDown,
		onKeyUp,
		onBlur
	};
	props = (0,focusable/* useFocusable */.W)(props);
	return props;
});
/**
* Renders a clickable element, which is a `button` by default, and inherits
* features from the [`Focusable`](https://ariakit.com/reference/focusable)
* component.
*
* If the base element isn't a native clickable one, this component will provide
* extra attributes and event handlers to ensure accessibility. It can be
* activated with the keyboard using the
* [`clickOnEnter`](https://ariakit.com/reference/command#clickonenter) and
* [`clickOnSpace`](https://ariakit.com/reference/command#clickonspace)
* props. Both are set to `true` by default.
* @see https://ariakit.com/components/command
* @example
* ```jsx
* <Command>Button</Command>
* ```
*/
const Command = (0,dist/* forwardRef */.Rf)(function Command(props) {
	return (0,dist/* createElement */.n)(TagName, useCommand(props));
});
//#endregion


//# sourceMappingURL=command.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-item.js
var collection_item = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-item.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-context.js
var composite_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-store@0.1.5_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js
var react_store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.5_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+store@0.1.4/node_modules/@ariakit/store/dist/index.js
var store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.4/node_modules/@ariakit/store/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-item.js
"use client";










//#region src/composite/composite-item.tsx
const composite_item_TagName = "button";
function isEditableElement(element) {
	if ((0,utils_dist/* isTextbox */.Bj)(element)) return true;
	return element.tagName === "INPUT" && !(0,utils_dist/* isButton */.Bm)(element);
}
function getNextPageOffset(scrollingElement, pageUp = false) {
	const height = scrollingElement.clientHeight;
	const { top } = scrollingElement.getBoundingClientRect();
	const pageSize = Math.max(height * .875, height - 40) * 1.5;
	const pageOffset = pageUp ? height - pageSize + top : pageSize + top;
	if (scrollingElement.tagName === "HTML") return pageOffset + scrollingElement.scrollTop;
	return pageOffset;
}
function getItemOffset(itemElement, pageUp = false) {
	const { top } = itemElement.getBoundingClientRect();
	if (pageUp) return top + itemElement.clientHeight;
	return top;
}
function findNextPageItemId(element, store, next, pageUp = false) {
	if (!store) return;
	if (!next) return;
	const { renderedItems } = store.getState();
	const scrollingElement = (0,utils_dist/* getScrollingElement */.qj)(element);
	if (!scrollingElement) return;
	const nextPageOffset = getNextPageOffset(scrollingElement, pageUp);
	let id;
	let prevDifference;
	for (let i = 0; i < renderedItems.length; i += 1) {
		const previousId = id;
		id = next(i);
		if (!id) break;
		if (id === previousId) continue;
		const itemElement = (0,utils/* getEnabledItem */.hZ)(store, id)?.element;
		if (!itemElement) continue;
		const difference = getItemOffset(itemElement, pageUp) - nextPageOffset;
		const absDifference = Math.abs(difference);
		if (pageUp && difference <= 0 || !pageUp && difference >= 0) {
			if (prevDifference !== void 0 && prevDifference < absDifference) id = previousId;
			break;
		}
		prevDifference = absDifference;
	}
	return id;
}
function targetIsAnotherItem(event, store) {
	if ((0,utils_dist/* isSelfTarget */.uh)(event)) return false;
	return (0,utils/* isItem */.WZ)(store, event.target);
}
/**
* Returns props to create a `CompositeItem` component.
* @see https://ariakit.com/components/composite
* @example
* ```jsx
* const store = useCompositeStore();
* const props = useCompositeItem({ store });
* <Role {...props}>Item 1</Role>
* ```
*/
const useCompositeItem = (0,dist/* createHook */.ab)(function useCompositeItem({ store, rowId: rowIdProp, preventScrollOnKeyDown = false, moveOnKeyPress = true, tabbable = false, getItem: getItemProp, "aria-setsize": ariaSetSizeProp, "aria-posinset": ariaPosInSetProp, ...props }) {
	const context = (0,composite_context/* useCompositeScopedContext */.SZ)();
	store = store || context;
	const id = (0,dist/* useId */.Bi)(props.id);
	const ref = (0,react.useRef)(null);
	const row = (0,react.useContext)(composite_context/* CompositeRowContext */.$o);
	const trulyDisabled = (0,utils_dist/* disabledFromProps */.$f)(props) && !props.accessibleWhenDisabled;
	const shouldRegisterItem = props.shouldRegisterItem;
	const getRowId = (state) => {
		if (rowIdProp) return rowIdProp;
		if (!state) return;
		if (!row?.baseElement) return;
		if (row.baseElement !== state.baseElement) return;
		return row.id;
	};
	const { rowId, baseElement, isActiveItem, ariaSetSize, ariaPosInSet, isTabbable } = (0,react_store_dist/* useStoreStateObject */.PX)(store, {
		rowId: getRowId,
		baseElement(state) {
			return state?.baseElement || void 0;
		},
		isActiveItem(state) {
			return !!state && state.activeId === id;
		},
		ariaSetSize(state) {
			if (ariaSetSizeProp != null) return ariaSetSizeProp;
			if (!state) return;
			if (!row?.ariaSetSize) return;
			if (row.baseElement !== state.baseElement) return;
			return row.ariaSetSize;
		},
		ariaPosInSet(state) {
			if (ariaPosInSetProp != null) return ariaPosInSetProp;
			if (!state) return;
			if (!row?.ariaPosInSet) return;
			if (row.baseElement !== state.baseElement) return;
			const rowId = getRowId(state);
			const itemsInRow = state.renderedItems.filter((item) => item.rowId === rowId);
			return row.ariaPosInSet + itemsInRow.findIndex((item) => item.id === id);
		},
		isTabbable(state) {
			if (!state?.renderedItems.length) return true;
			if (state.virtualFocus) return false;
			if (tabbable) return true;
			if (state.activeId === null) return false;
			const item = store?.item(state.activeId);
			if (item?.disabled) return true;
			if (!item?.element) return true;
			return state.activeId === id;
		}
	});
	const getItem = (0,react.useCallback)((item) => {
		const nextItem = {
			...item,
			id: id || item.id,
			rowId,
			disabled: trulyDisabled,
			children: item.element?.textContent
		};
		if (getItemProp) return getItemProp(nextItem);
		return nextItem;
	}, [
		id,
		rowId,
		trulyDisabled,
		getItemProp
	]);
	const onFocusProp = props.onFocus;
	const hasFocusedComposite = (0,react.useRef)(false);
	const cancelScheduledFocusRedirectRef = (0,react.useRef)(null);
	const onFocus = (0,dist/* useEvent */._q)((event) => {
		onFocusProp?.(event);
		if (event.defaultPrevented) return;
		if ((0,utils_dist/* isPortalEvent */.ho)(event)) return;
		if (!id) return;
		if (!store) return;
		if (targetIsAnotherItem(event, store)) return;
		const { virtualFocus, baseElement } = store.getState();
		store.setActiveId(id);
		if ((0,utils_dist/* isTextbox */.Bj)(event.currentTarget)) (0,utils/* selectTextField */.iT)(event.currentTarget);
		if (!virtualFocus) return;
		if (!(0,utils_dist/* isSelfTarget */.uh)(event)) return;
		if (isEditableElement(event.currentTarget)) return;
		const redirectFocusToBaseElement = (currentTarget, relatedTarget, baseElement) => {
			if ((0,utils_dist/* isSafari */.nr)() && currentTarget.hasAttribute("data-autofocus")) currentTarget.scrollIntoView({
				block: "nearest",
				inline: "nearest"
			});
			hasFocusedComposite.current = true;
			if (relatedTarget === baseElement || (0,utils/* isItem */.WZ)(store, relatedTarget)) (0,utils/* focusSilently */.hk)(baseElement);
			else baseElement.focus();
		};
		if (baseElement?.isConnected) {
			redirectFocusToBaseElement(event.currentTarget, event.relatedTarget, baseElement);
			return;
		}
		if (shouldRegisterItem === false) return;
		const { currentTarget, relatedTarget } = event;
		const cancelScheduledFocusRedirect = () => {
			cancelScheduledFocusRedirectRef.current?.();
			cancelScheduledFocusRedirectRef.current = null;
		};
		cancelScheduledFocusRedirect();
		cancelScheduledFocusRedirectRef.current = (0,store_dist/* subscribe */.B1)(store, null, () => {
			if ((0,utils_dist/* getActiveElement */.bq)(currentTarget) !== currentTarget) {
				cancelScheduledFocusRedirect();
				return;
			}
			const state = store.getState();
			const nextBaseElement = state.baseElement;
			if (!nextBaseElement?.isConnected) return;
			cancelScheduledFocusRedirect();
			if (!state.virtualFocus) return;
			redirectFocusToBaseElement(currentTarget, relatedTarget, nextBaseElement);
		});
	});
	const onBlurCaptureProp = props.onBlurCapture;
	const onBlurCapture = (0,dist/* useEvent */._q)((event) => {
		onBlurCaptureProp?.(event);
		if (event.defaultPrevented) return;
		if ((store?.getState())?.virtualFocus && hasFocusedComposite.current) {
			hasFocusedComposite.current = false;
			event.preventDefault();
			event.stopPropagation();
		}
	});
	const onKeyDownProp = props.onKeyDown;
	const preventScrollOnKeyDownProp = (0,dist/* useBooleanEvent */.O4)(preventScrollOnKeyDown);
	const moveOnKeyPressProp = (0,dist/* useBooleanEvent */.O4)(moveOnKeyPress);
	const onKeyDown = (0,dist/* useEvent */._q)((event) => {
		onKeyDownProp?.(event);
		if (event.defaultPrevented) return;
		if (!(0,utils_dist/* isSelfTarget */.uh)(event)) return;
		if (!store) return;
		const { currentTarget } = event;
		const state = store.getState();
		const isGrid = !!store.item(id)?.rowId;
		const isVertical = state.orientation !== "horizontal";
		const isHorizontal = state.orientation !== "vertical";
		const canHomeEnd = () => {
			if (isGrid) return true;
			if (isHorizontal) return true;
			if (!state.baseElement) return true;
			if (!(0,utils_dist/* isTextField */.mB)(state.baseElement)) return true;
			return false;
		};
		const action = {
			ArrowUp: (isGrid || isVertical) && store.up,
			ArrowRight: (isGrid || isHorizontal) && store.next,
			ArrowDown: (isGrid || isVertical) && store.down,
			ArrowLeft: (isGrid || isHorizontal) && store.previous,
			Home: () => {
				if (!canHomeEnd()) return;
				if (!isGrid || event.ctrlKey) return store?.first();
				return store?.previous(-1);
			},
			End: () => {
				if (!canHomeEnd()) return;
				if (!isGrid || event.ctrlKey) return store?.last();
				return store?.next(-1);
			},
			PageUp: () => {
				return findNextPageItemId(currentTarget, store, store?.up, true);
			},
			PageDown: () => {
				return findNextPageItemId(currentTarget, store, store?.down);
			}
		}[event.key];
		if (action) {
			if ((0,utils_dist/* isTextbox */.Bj)(currentTarget)) {
				const selection = (0,utils_dist/* getTextboxSelection */.Zy)(currentTarget);
				const isLeft = isHorizontal && event.key === "ArrowLeft";
				const isRight = isHorizontal && event.key === "ArrowRight";
				const isUp = isVertical && event.key === "ArrowUp";
				const isDown = isVertical && event.key === "ArrowDown";
				if (isRight || isDown) {
					const { length: valueLength } = (0,utils_dist/* getTextboxValue */.Mk)(currentTarget);
					if (selection.end !== valueLength) return;
				} else if ((isLeft || isUp) && selection.start !== 0) return;
			}
			const nextId = action();
			if (preventScrollOnKeyDownProp(event) || nextId !== void 0) {
				if (!moveOnKeyPressProp(event)) return;
				event.preventDefault();
				store.move(nextId);
			}
		}
	});
	const providerValue = (0,react.useMemo)(() => ({
		id,
		baseElement
	}), [id, baseElement]);
	props = (0,dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(composite_context/* CompositeItemContext */.U0.Provider, {
		value: providerValue,
		children: element
	}), [providerValue]);
	props = {
		"data-active-item": isActiveItem || void 0,
		...props,
		id,
		ref: (0,dist/* useMergeRefs */.SV)(ref, props.ref),
		tabIndex: isTabbable ? props.tabIndex : -1,
		onFocus,
		onBlurCapture,
		onKeyDown
	};
	props = useCommand(props);
	props = (0,collection_item/* useCollectionItem */.v)({
		store,
		...props,
		getItem,
		shouldRegisterItem: id ? shouldRegisterItem : false
	});
	return (0,utils_dist/* removeUndefinedValues */.HR)({
		...props,
		"aria-setsize": ariaSetSize,
		"aria-posinset": ariaPosInSet
	});
});
/**
* Renders a focusable item as part of a composite widget. The `tabindex`
* attribute is automatically managed by this component based on the
* [`virtualFocus`](https://ariakit.com/reference/composite-provider#virtualfocus)
* option.
*
* When this component receives DOM focus or is virtually focused (when the
* [`virtualFocus`](https://ariakit.com/reference/composite-provider#virtualfocus)
* option is set to `true`), the element will automatically receive the
* [`data-active-item`](https://ariakit.com/guide/styling#data-active-item)
* attribute. This can be used to style the focused item, no matter the focus
* approach employed.
* @see https://ariakit.com/components/composite
* @example
* ```jsx {3-5}
* <CompositeProvider>
*   <Composite>
*     <CompositeItem>Item 1</CompositeItem>
*     <CompositeItem>Item 2</CompositeItem>
*     <CompositeItem>Item 3</CompositeItem>
*   </Composite>
* </CompositeProvider>
* ```
*/
const CompositeItem = (0,dist/* memo */.ph)((0,dist/* forwardRef */.Rf)(function CompositeItem(props) {
	return (0,dist/* createElement */.n)(composite_item_TagName, useCompositeItem(props));
}));
//#endregion


//# sourceMappingURL=composite-item.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  YC: () => (/* binding */ useCompositeStoreOptions),
  YO: () => (/* binding */ useCompositeStoreProps)
});

// UNUSED EXPORTS: useCompositeStore

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-store@0.1.5_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js
var react_store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.5_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/collection/collection-store.js
"use client";



//#region src/collection/collection-store.ts
function useCollectionStoreProps(store, update, props) {
	(0,dist/* useUpdateEffect */.w5)(update, [props.store]);
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "items", "setItems");
	return store;
}
function useCollectionStore(props = {}) {
	const [store, update] = useStore(Core.createCollectionStore, props);
	return useCollectionStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=collection-store.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-store.js
"use client";




//#region src/composite/composite-store.ts
function useCompositeStoreOptions(props) {
	return {
		id: (0,dist/* useId */.Bi)(props.id),
		...props
	};
}
function useCompositeStoreProps(store, update, props) {
	store = useCollectionStoreProps(store, update, props);
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "activeId", "setActiveId");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "includesBaseElement");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "virtualFocus");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "orientation");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "rtl");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "focusLoop");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "focusWrap");
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "focusShift");
	return store;
}
function useCompositeStore(props = {}) {
	props = useCompositeStoreOptions(props);
	const [store, update] = useStore(Core.createCompositeStore, props);
	return useCompositeStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=composite-store.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ useComposite)
/* harmony export */ });
/* unused harmony export Composite */
/* harmony import */ var _focusable_focusable_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/focusable/focusable.js");
/* harmony import */ var _composite_context_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/composite-context.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/utils.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.0_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.5_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
"use client";








//#region src/composite/composite.tsx
const TagName = "div";
function isGrid(items) {
	return items.some((item) => !!item.rowId);
}
function isPrintableKey(event) {
	const target = event.target;
	if (target && !(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(target)) return false;
	return event.key.length === 1 && !event.ctrlKey && !event.metaKey;
}
function isModifierKey(event) {
	return event.key === "Shift" || event.key === "Control" || event.key === "Alt" || event.key === "Meta";
}
function useKeyboardEventProxy(store, onKeyboardEvent, previousElementRef) {
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
		onKeyboardEvent?.(event);
		if (event.defaultPrevented) return;
		if (event.isPropagationStopped()) return;
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event)) return;
		if (isModifierKey(event)) return;
		if (isPrintableKey(event)) return;
		const activeElement = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, store.getState().activeId)?.element;
		if (!activeElement) return;
		const { view, ...eventInit } = event;
		if (activeElement !== previousElementRef?.current) activeElement.focus();
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireKeyboardEvent */ .sz)(activeElement, event.type, eventInit)) event.preventDefault();
		if (event.currentTarget.contains(activeElement)) event.stopPropagation();
	});
}
function findFirstEnabledItemInTheLastRow(items) {
	return (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .findFirstEnabledItem */ .oi)((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .flatten2DArray */ .M9)((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .reverseArray */ .q7)((0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .groupItemsByRows */ .es)(items))));
}
function withBaseScrollPreserved(store, callback) {
	const { virtualFocus, baseElement } = store.getState();
	if (!virtualFocus || !baseElement || !(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(baseElement)) {
		callback();
		return;
	}
	const savedScrollLeft = baseElement.scrollLeft;
	const savedScrollTop = baseElement.scrollTop;
	callback();
	baseElement.scrollLeft = savedScrollLeft;
	baseElement.scrollTop = savedScrollTop;
}
function useScheduleFocus(store) {
	const [scheduled, setScheduled] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
	const schedule = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(() => setScheduled(true), []);
	const activeItem = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__/* .useStoreState */ .O$)(store, (state) => scheduled ? (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, state.activeId) : null);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		const activeElement = activeItem?.element;
		if (!scheduled) return;
		if (!activeElement) return;
		setScheduled(false);
		withBaseScrollPreserved(store, () => {
			activeElement.focus({ preventScroll: true });
		});
	}, [
		store,
		activeItem,
		scheduled
	]);
	return schedule;
}
/**
* Renders nothing and reacts to the `moves` state by focusing on the active
* item. This lives in a separate memoized component so moving through items
* doesn't re-render the whole composite component, and composite re-renders
* don't re-render this component. It's only rendered when the `composite` prop
* is enabled, so the `moves` subscription doesn't run for non-composite
* widgets.
*/
const CompositeFocusOnMove = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .memo */ .ph)(function CompositeFocusOnMove({ store, focusOnMove, previousElementRef }) {
	const moves = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__/* .useStoreState */ .O$)(store, "moves");
	const baseElement = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__/* .useStoreState */ .O$)(store, "baseElement");
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		if (!moves) return;
		if (!focusOnMove) return;
		const { activeId } = store.getState();
		const itemElement = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, activeId)?.element;
		if (!itemElement) return;
		withBaseScrollPreserved(store, () => (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .focusIntoView */ .WA)(itemElement));
	}, [
		store,
		moves,
		focusOnMove
	]);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
		if (!moves) return;
		if (!baseElement) return;
		const { activeId } = store.getState();
		if (!(activeId === null)) return;
		const previousElement = previousElementRef.current;
		previousElementRef.current = null;
		if (previousElement) (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireBlurEvent */ .c$)(previousElement, { relatedTarget: baseElement });
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .hasFocus */ .AJ)(baseElement)) baseElement.focus();
	}, [
		store,
		moves,
		baseElement
	]);
	return null;
});
/**
* Returns props to create a `Composite` component.
* @see https://ariakit.com/components/composite
* @example
* ```jsx
* const store = useCompositeStore();
* const props = useComposite({ store });
* <Role {...props}>
*   <CompositeItem>Item 1</CompositeItem>
*   <CompositeItem>Item 2</CompositeItem>
* </Role>
* ```
*/
const useComposite = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .createHook */ .ab)(function useComposite({ store, composite = true, focusOnMove = composite, moveOnKeyPress = true, ...props }) {
	const context = (0,_composite_context_js__WEBPACK_IMPORTED_MODULE_6__/* .useCompositeProviderContext */ .Lf)();
	store = store || context;
	(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .invariant */ .V1)(store,  false && 0);
	const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const previousElementRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const scheduleFocus = useScheduleFocus(store);
	const [, setBaseElement] = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useTransactionState */ .XB)(composite ? store.setBaseElement : null);
	const virtualFocus = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__/* .useStoreState */ .O$)(store, "virtualFocus");
	const activeId = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__/* .useStoreState */ .O$)(store, (state) => state.virtualFocus ? state.activeId : null);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
		if (!store) return;
		if (!composite) return;
		if (!virtualFocus) return;
		const previousElement = previousElementRef.current;
		previousElementRef.current = null;
		if (!previousElement) return;
		const relatedTarget = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, activeId)?.element || (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .getActiveElement */ .bq)(previousElement);
		if (relatedTarget === previousElement) return;
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireBlurEvent */ .c$)(previousElement, { relatedTarget });
	}, [
		store,
		activeId,
		virtualFocus,
		composite
	]);
	const onKeyDownCapture = useKeyboardEventProxy(store, props.onKeyDownCapture, previousElementRef);
	const onKeyUpCapture = useKeyboardEventProxy(store, props.onKeyUpCapture, previousElementRef);
	const onFocusCaptureProp = props.onFocusCapture;
	const onFocusCapture = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
		onFocusCaptureProp?.(event);
		if (event.defaultPrevented) return;
		if (!store) return;
		const { virtualFocus } = store.getState();
		if (!virtualFocus) return;
		const previousActiveElement = event.relatedTarget;
		const isSilentlyFocused = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .silentlyFocused */ .Qh)(event.currentTarget);
		if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event) && isSilentlyFocused) {
			event.stopPropagation();
			previousElementRef.current = previousActiveElement;
		}
	});
	const onFocusProp = props.onFocus;
	const onFocus = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
		onFocusProp?.(event);
		if (event.defaultPrevented) return;
		if (!composite) return;
		if (!store) return;
		const { relatedTarget } = event;
		const { virtualFocus } = store.getState();
		if (virtualFocus) {
			if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event) && !(0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .isItem */ .WZ)(store, relatedTarget)) queueMicrotask(scheduleFocus);
		} else if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event)) store.setActiveId(null);
	});
	const onBlurCaptureProp = props.onBlurCapture;
	const onBlurCapture = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
		onBlurCaptureProp?.(event);
		if (event.defaultPrevented) return;
		if (!store) return;
		const { virtualFocus, activeId } = store.getState();
		if (!virtualFocus) return;
		const activeElement = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, activeId)?.element;
		const nextActiveElement = event.relatedTarget;
		const nextActiveElementIsItem = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .isItem */ .WZ)(store, nextActiveElement);
		const previousElement = previousElementRef.current;
		previousElementRef.current = null;
		if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event) && nextActiveElementIsItem) {
			if (nextActiveElement === activeElement) {
				if (previousElement && previousElement !== nextActiveElement) (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireBlurEvent */ .c$)(previousElement, event);
			} else if (activeElement) (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireBlurEvent */ .c$)(activeElement, event);
			else if (previousElement) (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireBlurEvent */ .c$)(previousElement, event);
			event.stopPropagation();
		} else if (!(0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .isItem */ .WZ)(store, event.target) && activeElement) (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireBlurEvent */ .c$)(activeElement, event);
	});
	const onKeyDownProp = props.onKeyDown;
	const moveOnKeyPressProp = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useBooleanEvent */ .O4)(moveOnKeyPress);
	const onKeyDown = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
		onKeyDownProp?.(event);
		if (event.nativeEvent.isComposing) return;
		if (event.defaultPrevented) return;
		if (!store) return;
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event)) return;
		const { orientation, renderedItems, activeId, rtl } = store.getState();
		if ((0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, activeId)?.element?.isConnected) return;
		const isVertical = orientation !== "horizontal";
		const isHorizontal = orientation !== "vertical";
		const grid = isGrid(renderedItems);
		if ((event.key === "ArrowLeft" || event.key === "ArrowRight" || event.key === "Home" || event.key === "End") && (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(event.currentTarget)) return;
		const up = () => {
			if (grid) return findFirstEnabledItemInTheLastRow(renderedItems)?.id;
			return store?.last();
		};
		const action = {
			ArrowUp: (grid || isVertical) && up,
			ArrowRight: (grid || isHorizontal) && (rtl ? store.last : store.first),
			ArrowDown: (grid || isVertical) && store.first,
			ArrowLeft: (grid || isHorizontal) && (rtl ? store.first : store.last),
			Home: store.first,
			End: store.last,
			PageUp: store.first,
			PageDown: store.last
		}[event.key];
		if (action) {
			const id = action();
			if (id !== void 0) {
				if (!moveOnKeyPressProp(event)) return;
				event.preventDefault();
				store.move(id);
			}
		}
	});
	props = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useWrapElement */ .w7)(props, (element) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_composite_context_js__WEBPACK_IMPORTED_MODULE_6__/* .CompositeScopedContextProvider */ .aN, {
		value: store,
		children: [element, composite && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(CompositeFocusOnMove, {
			store,
			focusOnMove,
			previousElementRef
		})]
	}), [
		store,
		composite,
		focusOnMove
	]);
	props = {
		"aria-activedescendant": (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__/* .useStoreState */ .O$)(store, (state) => {
			if (!store) return;
			if (!composite) return;
			if (!state.virtualFocus) return;
			return (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, state.activeId)?.id;
		}),
		...props,
		ref: (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useMergeRefs */ .SV)(ref, setBaseElement, props.ref),
		onKeyDownCapture,
		onKeyUpCapture,
		onFocusCapture,
		onFocus,
		onBlurCapture,
		onKeyDown
	};
	props = (0,_focusable_focusable_js__WEBPACK_IMPORTED_MODULE_7__/* .useFocusable */ .W)({
		focusable: (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_5__/* .useStoreState */ .O$)(store, (state) => composite && (state.virtualFocus || state.activeId === null)),
		...props
	});
	return props;
});
/**
* Renders a composite widget.
* @see https://ariakit.com/components/composite
* @example
* ```jsx
* const composite = useCompositeStore();
* <Composite store={composite}>
*   <CompositeItem>Item 1</CompositeItem>
*   <CompositeItem>Item 2</CompositeItem>
* </Composite>
* ```
*/
const Composite = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .forwardRef */ .Rf)(function Composite(props) {
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .createElement */ .n)(TagName, useComposite(props));
});
//#endregion


//# sourceMappingURL=composite.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_c942b97430df21eb1cbe19f44c2806d9/node_modules/@ariakit/react-components/dist/composite/utils.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Qh: () => (/* binding */ silentlyFocused),
/* harmony export */   WZ: () => (/* binding */ isItem),
/* harmony export */   es: () => (/* binding */ groupItemsByRows),
/* harmony export */   hZ: () => (/* binding */ getEnabledItem),
/* harmony export */   hk: () => (/* binding */ focusSilently),
/* harmony export */   iT: () => (/* binding */ selectTextField),
/* harmony export */   oi: () => (/* binding */ findFirstEnabledItem)
/* harmony export */ });
/* unused harmony export flipItems */
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.1.4/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.5/node_modules/@ariakit/components/dist/composite/composite-store.js");
"use client";


//#region src/composite/utils.ts
const flipItems = _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_0__/* .flipItems */ ._d;
const findFirstEnabledItem = _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_0__/* .findFirstEnabledItem */ .oi;
const groupItemsByRows = _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_0__/* .groupItemsByRows */ .es;
/**
* Returns the store item with the given id (enabled or not), or `null`.
*/
function getEnabledItem(store, id) {
	if (!id) return null;
	return store.item(id) || null;
}
/**
* Selects text field contents even if it's a content editable element.
*/
function selectTextField(element, collapseToEnd = false) {
	if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isTextField */ .mB)(element)) element.setSelectionRange(collapseToEnd ? element.value.length : 0, element.value.length);
	else if (element.isContentEditable) {
		const selection = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .getDocument */ .YE)(element).getSelection();
		selection?.selectAllChildren(element);
		if (collapseToEnd) selection?.collapseToEnd();
	}
}
const FOCUS_SILENTLY = Symbol("FOCUS_SILENTLY");
/**
* Focus an element with a flag. The `silentlyFocused` function needs to be
* called later to check if the focus was silenced and to reset this state.
*/
function focusSilently(element) {
	element[FOCUS_SILENTLY] = true;
	element.focus({ preventScroll: true });
}
/**
* Checks whether the element has been focused with the `focusSilently` function
* and resets the state.
*/
function silentlyFocused(element) {
	const isSilentlyFocused = element[FOCUS_SILENTLY];
	delete element[FOCUS_SILENTLY];
	return isSilentlyFocused;
}
/**
* Determines whether the element is a composite item.
*/
function isItem(store, element, exclude) {
	if (!element) return false;
	if (element === exclude) return false;
	const item = store.item(element.id);
	if (!item) return false;
	if (exclude && item.element === exclude) return false;
	return true;
}
//#endregion


//# sourceMappingURL=utils.js.map

/***/ })

}]);