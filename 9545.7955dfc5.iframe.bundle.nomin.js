"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9545],{

/***/ "../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/collection/collection-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   I: () => (/* binding */ createCollectionStore)
/* harmony export */ });
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");


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
const itemIdCacheThreshold = 64;
function getCachedItemIds(items, cache) {
	if (items.length < itemIdCacheThreshold) {
		if (cache.ids) {
			cache.items = void 0;
			cache.ids = void 0;
		}
		return;
	}
	if (cache.items === items) return cache.ids;
	const ids = /* @__PURE__ */ new Set();
	for (const item of items) ids.add(item.id);
	cache.items = items;
	cache.ids = ids;
	return ids;
}
function setCachedItemIds(items, cache, ids) {
	if (items.length < itemIdCacheThreshold) {
		if (cache.ids) {
			cache.items = void 0;
			cache.ids = void 0;
		}
		return;
	}
	if (!ids) {
		ids = /* @__PURE__ */ new Set();
		for (const item of items) ids.add(item.id);
	}
	cache.items = items;
	cache.ids = ids;
}
/**
* Creates a collection store.
*/
function createCollectionStore(props = {}) {
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .throwOnConflictingProps */ .UE)(props, props.store);
	const syncState = props.store?.getState();
	const items = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .defaultValue */ .Jh)(props.items, syncState?.items, props.defaultItems, []);
	const syncPrivateStore = getPrivateStore(props.store);
	const collectionLookup = syncPrivateStore?.__unstableCollectionLookup ?? {
		controlledItems: new Map(items.map((item) => [item.id, item])),
		registeredItems: /* @__PURE__ */ new Map(),
		controlledItemsSource: items,
		propagatedItems: items
	};
	const { controlledItems, registeredItems } = collectionLookup;
	const itemIdCache = {};
	const renderedItemIdCache = {};
	const initialState = {
		items,
		renderedItems: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .defaultValue */ .Jh)(syncState?.renderedItems, [])
	};
	const collection = (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .createStore */ .y$)(initialState, props.store);
	const privateStore = Object.assign((0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .createStore */ .y$)({
		items,
		renderedItems: initialState.renderedItems
	}, syncPrivateStore), { __unstableCollectionLookup: collectionLookup });
	const sortItems = (renderedItems) => {
		const sortedItems = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .sortBasedOnDOMPosition */ .gH)(renderedItems, (i) => i.element);
		privateStore.setState("renderedItems", sortedItems);
		collection.setState("renderedItems", sortedItems);
	};
	const indexControlledItems = (items) => {
		if (items === collectionLookup.controlledItemsSource) return;
		collectionLookup.controlledItemsSource = items;
		controlledItems.clear();
		for (const item of items) controlledItems.set(item.id, item);
	};
	const setState = (key, value) => {
		if (key !== "items") {
			collection.setState(key, value);
			return;
		}
		collection.setState("items", (items) => {
			const nextItems = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .applyState */ .Qh)(value, items);
			if (nextItems === items) return items;
			collectionLookup.propagatedItems = nextItems;
			indexControlledItems(nextItems);
			return nextItems;
		});
	};
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(collection, () => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .init */ .Ts)(privateStore));
	if (!syncPrivateStore) {
		(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .subscribe */ .B1)(collection, ["items"], ({ items }) => {
			if (items === collectionLookup.propagatedItems) return;
			collectionLookup.propagatedItems = items;
			indexControlledItems(items);
		});
		(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(privateStore, () => {
			return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .batch */ .vA)(privateStore, ["items"], ({ items }) => {
				collectionLookup.propagatedItems = items;
				collection.setState("items", items);
			});
		});
	}
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(privateStore, () => {
		return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .batch */ .vA)(privateStore, ["renderedItems"], (state) => {
			if (state.renderedItems !== collection.getState().renderedItems) sortItems(state.renderedItems);
			if (typeof IntersectionObserver !== "function") return;
			let firstRun = true;
			let raf = 0;
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
	const createMergeItem = (key, cache, lookup) => {
		const registeredItems = lookup?.registeredItems;
		const controlledItems = lookup?.controlledItems;
		return (item) => {
			const previousRegisteredItem = registeredItems?.get(item.id);
			const wasKnown = !!previousRegisteredItem || !!controlledItems?.has(item.id);
			let prevItem;
			privateStore.setState(key, (items) => {
				const ids = !!cache.ids || items.length >= itemIdCacheThreshold && !wasKnown ? getCachedItemIds(items, cache) : void 0;
				const index = ids && !ids.has(item.id) ? -1 : items.findIndex(({ id }) => id === item.id);
				const nextItems = items.slice();
				if (index !== -1) {
					prevItem = items[index];
					const nextItem = {
						...prevItem,
						...item
					};
					nextItems[index] = nextItem;
					registeredItems?.set(item.id, nextItem);
				} else {
					nextItems.push(item);
					registeredItems?.set(item.id, item);
					ids?.add(item.id);
				}
				if (cache.ids || index === -1 && nextItems.length >= itemIdCacheThreshold) setCachedItemIds(nextItems, cache, ids);
				return nextItems;
			});
			const unmergeItem = () => {
				if (registeredItems) {
					if (previousRegisteredItem) registeredItems.set(item.id, previousRegisteredItem);
					else registeredItems.delete(item.id);
				}
				privateStore.setState(key, (items) => {
					const ids = cache.ids ? getCachedItemIds(items, cache) : void 0;
					if (!prevItem) {
						const nextItems = items.filter(({ id }) => id !== item.id);
						ids?.delete(item.id);
						if (cache.ids) setCachedItemIds(nextItems, cache, ids);
						return nextItems;
					}
					const index = items.findIndex(({ id }) => id === item.id);
					if (index === -1) return items;
					const nextItems = items.slice();
					nextItems[index] = prevItem;
					if (cache.ids) setCachedItemIds(nextItems, cache, ids);
					return nextItems;
				});
			};
			return unmergeItem;
		};
	};
	const registerItem = createMergeItem("items", itemIdCache, collectionLookup);
	const mergeRenderedItem = createMergeItem("renderedItems", renderedItemIdCache);
	return {
		...collection,
		setState,
		registerItem,
		renderItem: (item) => (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .chain */ .cy)(registerItem(item), mergeRenderedItem(item)),
		item: (id) => {
			if (!id) return null;
			if (!registeredItems.size) return controlledItems.get(id) ?? null;
			return registeredItems.get(id) ?? controlledItems.get(id) ?? null;
		},
		__unstablePrivateStore: privateStore
	};
}
//#endregion


//# sourceMappingURL=collection-store.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/composite/composite-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _d: () => (/* binding */ flipItems),
/* harmony export */   es: () => (/* binding */ groupItemsByRows),
/* harmony export */   oi: () => (/* binding */ findFirstEnabledItem),
/* harmony export */   zf: () => (/* binding */ createCompositeStore)
/* harmony export */ });
/* unused harmony export NULL_ITEM */
/* harmony import */ var _collection_collection_store_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/collection/collection-store.js");
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");



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
function findLastEnabledItem(items) {
	for (let i = items.length - 1; i >= 0; i -= 1) {
		const item = items[i];
		if (!item) continue;
		if (item.disabled) continue;
		return item;
	}
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
		const itemId = item.id;
		if (excludeId != null && itemId === excludeId) continue;
		return itemId;
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
const rowMapItemThreshold = 48;
const rowMapRowThreshold = 4;
function groupSmallItemsByRows(items) {
	const rows = [];
	let previousRow;
	let previousRowId;
	for (const item of items) {
		const rowId = item.rowId;
		if (previousRow && previousRowId === rowId) {
			previousRow.push(item);
			continue;
		}
		const row = rows.find((currentRow) => currentRow[0]?.rowId === rowId);
		if (row) {
			row.push(item);
			previousRow = row;
		} else {
			previousRow = [item];
			rows.push(previousRow);
		}
		previousRowId = rowId;
	}
	return rows;
}
function groupLargeItemsByRows(items) {
	const firstItem = items[0];
	if (!firstItem) return [];
	let itemIndex = 1;
	while (itemIndex < items.length && items[itemIndex]?.rowId === firstItem.rowId) itemIndex += 1;
	const firstRow = items.slice(0, itemIndex);
	if (itemIndex === items.length) return [firstRow];
	const rows = [firstRow];
	let rowsById;
	for (; itemIndex < items.length; itemIndex += 1) {
		const item = items[itemIndex];
		if (!item) continue;
		const row = rowsById ? rowsById.get(item.rowId) : rows.find((currentRow) => currentRow[0]?.rowId === item.rowId);
		if (row) {
			row.push(item);
			continue;
		}
		const newRow = [item];
		rows.push(newRow);
		if (rowsById) rowsById.set(item.rowId, newRow);
		else if (rows.length === rowMapRowThreshold) rowsById = new Map(rows.map((currentRow) => [currentRow[0]?.rowId, currentRow]));
	}
	return rows;
}
/**
* Creates a two-dimensional array with items grouped by their rowId's.
*/
function groupItemsByRows(items) {
	if (items.length >= rowMapItemThreshold) return groupLargeItemsByRows(items);
	return groupSmallItemsByRows(items);
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
	const compositeElement = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState?.compositeElement, syncState?.baseElement, null);
	const compositeElementInFocusOrder = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.compositeElementInFocusOrder, props.includesBaseElement, syncState?.compositeElementInFocusOrder, syncState?.includesBaseElement, activeId === null);
	const initialState = {
		...collection.getState(),
		id: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.id, syncState?.id) ?? `id-${Math.random().toString(36).slice(2, 8)}`,
		activeId,
		compositeElement,
		baseElement: compositeElement,
		compositeElementInFocusOrder,
		includesBaseElement: compositeElementInFocusOrder,
		moves: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState?.moves, 0),
		orientation: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.orientation, syncState?.orientation, "both"),
		rtl: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.rtl, syncState?.rtl, false),
		virtualFocus: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.virtualFocus, syncState?.virtualFocus, false),
		focusLoop: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusLoop, syncState?.focusLoop, false),
		focusWrap: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusWrap, syncState?.focusWrap, false),
		focusShift: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusShift, syncState?.focusShift, false)
	};
	const composite = (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .createStore */ .y$)(initialState, collection, props.store);
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .setup */ .mj)(composite, () => (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .chain */ .cy)((0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .sync */ .OH)(composite, ["compositeElement"], (state) => {
		composite.setState("baseElement", state.compositeElement);
	}), (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .sync */ .OH)(composite, ["baseElement"], (state) => {
		composite.setState("compositeElement", state.baseElement);
	}), (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .sync */ .OH)(composite, ["compositeElementInFocusOrder"], (state) => {
		composite.setState("includesBaseElement", state.compositeElementInFocusOrder);
	}), (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .sync */ .OH)(composite, ["includesBaseElement"], (state) => {
		composite.setState("compositeElementInFocusOrder", state.includesBaseElement);
	})));
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .setup */ .mj)(composite, () => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_2__/* .sync */ .OH)(composite, ["renderedItems", "activeId"], (state) => {
		composite.setState("activeId", (activeId) => {
			if (activeId !== void 0) return activeId;
			return findFirstEnabledItem(state.renderedItems)?.id;
		});
	}));
	const getNextId = (direction = "next", options = {}) => {
		const defaultState = composite.getState();
		const compositeElementInFocusOrder = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(options.compositeElementInFocusOrder, options.includesBaseElement, defaultState.compositeElementInFocusOrder);
		const { skip = 0, activeId = defaultState.activeId, focusShift = defaultState.focusShift, focusLoop = defaultState.focusLoop, focusWrap = defaultState.focusWrap, renderedItems = defaultState.renderedItems, rtl = defaultState.rtl } = options;
		const isVerticalDirection = direction === "up" || direction === "down";
		const isNextDirection = direction === "next" || direction === "down";
		const canReverse = isNextDirection ? rtl && !isVerticalDirection : !rtl || isVerticalDirection;
		const canShift = focusShift && !skip;
		if (!skip && !focusWrap && !compositeElementInFocusOrder && activeId != null) {
			if (!isVerticalDirection ? true : !canShift && !renderedItems.some((item) => item.rowId != null)) {
				let activeIndex = -1;
				if (renderedItems === defaultState.renderedItems) {
					const firstItem = renderedItems[0];
					if (firstItem && collection.item(firstItem.id) === firstItem) {
						const registeredItem = collection.item(activeId);
						if (registeredItem?.id === activeId) activeIndex = renderedItems.indexOf(registeredItem);
					}
				}
				if (activeIndex === -1) activeIndex = renderedItems.findIndex((item) => item.id === activeId);
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
		const hasNullItem = isNextDirection ? (!isGrid || isVerticalDirection) && canLoop && compositeElementInFocusOrder : isVerticalDirection ? compositeElementInFocusOrder : false;
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
		setCompositeElement: (element) => composite.setState("compositeElement", element),
		setBaseElement: (element) => composite.setState("compositeElement", element),
		setActiveId: (id) => composite.setState("activeId", id),
		move: (id) => {
			if (id === void 0) return;
			composite.setState("activeId", id);
			composite.setState("moves", (moves) => moves + 1);
		},
		first: () => findFirstEnabledItem(composite.getState().renderedItems)?.id,
		last: () => findLastEnabledItem(composite.getState().renderedItems)?.id,
		next: (options) => getNextIdFromOptions("next", options),
		previous: (options) => getNextIdFromOptions("previous", options),
		down: (options) => getNextIdFromOptions("down", options),
		up: (options) => getNextIdFromOptions("up", options)
	};
}
//#endregion


//# sourceMappingURL=composite-store.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/xFYjARz5.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t: () => (/* binding */ getMoveRequest)
/* harmony export */ });
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
"use client";


//#region src/composite/__move-request.ts
const cancelled = Symbol("cancelled");
/**
* Tracks the current move per store. This has to outlive the component: `moves`
* only counts requests, so a fresh instance can't tell whether a move was
* consumed or what target a pending move asked for.
*/
const moveRequests = /* @__PURE__ */ new WeakMap();
function getMoveRequest(store, source) {
	const key = store.getState;
	const cached = moveRequests.get(key);
	if (cached) return cached;
	const sourceState = source?.getState();
	if (source && sourceState && (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(sourceState, "moves") && (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(sourceState, "activeId")) {
		const request = getMoveRequest(source);
		moveRequests.set(key, request);
		return request;
	}
	const request = {
		consumedBy: null,
		targetId: store.getState().activeId
	};
	moveRequests.set(key, request);
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_1__/* .sync */ .OH)(store, ["moves", "activeId"], (state, prevState) => {
		if (state.moves !== prevState.moves) {
			request.consumedBy = null;
			request.targetId = state.activeId;
			return;
		}
		if (state.activeId !== prevState.activeId) request.targetId = cancelled;
	});
	return request;
}
//#endregion


//# sourceMappingURL=xFYjARz5.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/button/utils.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Y: () => (/* binding */ withDefaultButtonType)
/* harmony export */ });
"use client";
//#region src/button/utils.ts
function withDefaultButtonType(props) {
	const { render, type } = props;
	if (render || type !== void 0) return props;
	return {
		...props,
		type: "button"
	};
}
//#endregion


//# sourceMappingURL=utils.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $I: () => (/* binding */ useCollectionContext),
/* harmony export */   LN: () => (/* binding */ CollectionContextProvider),
/* harmony export */   zX: () => (/* binding */ CollectionScopedContextProvider)
/* harmony export */ });
/* unused harmony exports useCollectionProviderContext, useCollectionScopedContext */
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
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

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-item.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   v: () => (/* binding */ useCollectionItem)
/* harmony export */ });
/* unused harmony export CollectionItem */
/* harmony import */ var _collection_context_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
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
	return props;
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
	const htmlProps = useCollectionItem(props);
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .createElement */ .n)(TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=collection-item.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-context.js":
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
/* harmony import */ var _collection_collection_context_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
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

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-item.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  l: () => (/* binding */ CompositeItem),
  k: () => (/* binding */ useCompositeItem)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/button/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/button/utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/C4_D9PgF.js
var C4_D9PgF = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/C4_D9PgF.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js
var focusable = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js
var utils_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/command/command.js
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
	const type = props.type;
	(0,react.useEffect)(() => {
		const element = ref.current;
		if (!element) return;
		const nativeButton = (0,utils_dist/* isButton */.Bm)(element);
		if (type !== void 0 && nativeButton && element.type === "button") return;
		setIsNativeButton(nativeButton);
	}, [type]);
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
* [`clickOnSpace`](https://ariakit.com/reference/command#clickonspace) props.
* Both are set to `true` by default.
* @see https://ariakit.com/components/command
* @example
* ```jsx
* <Command>Button</Command>
* ```
*/
const Command = (0,dist/* forwardRef */.Rf)(function Command(props) {
	const htmlProps = useCommand((0,utils/* withDefaultButtonType */.Y)(props));
	return (0,dist/* createElement */.n)(TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=command.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-item.js
var collection_item = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-item.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-context.js
var composite_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/utils.js
var composite_utils = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/utils.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js
var react_store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js
var store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-item.js
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
		const itemElement = (0,composite_utils/* getEnabledItem */.hZ)(store, id)?.element;
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
	return (0,composite_utils/* isItem */.WZ)(store, event.target);
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
const useCompositeItem = (0,dist/* createHook */.ab)(function useCompositeItem({ store, rowId: rowIdProp, preventScrollOnKeyDown = false, moveOnKeyPress = true, tabbable = false, getItem: getItemProp, typeaheadText, "aria-setsize": ariaSetSizeProp, "aria-posinset": ariaPosInSetProp, unstable_scrollIntoView: scrollIntoView, ...props }) {
	const context = (0,composite_context/* useCompositeScopedContext */.SZ)();
	store = store || context;
	const accessibleWhenDisabled = (0,C4_D9PgF.t)(props);
	const id = (0,dist/* useId */.Bi)(props.id);
	const ref = (0,react.useRef)(null);
	const mountedElementRef = (0,react.useRef)(null);
	const markUnmountingRef = (0,react.useCallback)((element) => {
		const mountedElement = mountedElementRef.current;
		if (!element && mountedElement) (0,composite_utils/* markItemUnmounting */.nc)(mountedElement);
		if (element) (0,composite_utils/* markItemMounted */.Df)(element);
		mountedElementRef.current = element;
	}, []);
	const row = (0,react.useContext)(composite_context/* CompositeRowContext */.$o);
	const disabled = (0,utils_dist/* disabledFromProps */.$f)(props);
	const trulyDisabled = disabled && !accessibleWhenDisabled;
	const inactiveDisabled = disabled && props.focusable === false;
	const shouldRegisterItem = props.shouldRegisterItem;
	const getRowId = (state) => {
		if (rowIdProp) return rowIdProp;
		if (!state) return;
		if (!row?.compositeElement) return;
		if (row.compositeElement !== state.compositeElement) return;
		return row.id;
	};
	const { rowId, compositeElement, ariaSetSize, ariaPosInSet } = (0,react_store_dist/* useStoreStateObject */.PX)(store, ["compositeElement", "renderedItems"], {
		rowId: getRowId,
		compositeElement(state) {
			return state?.compositeElement || void 0;
		},
		ariaSetSize(state) {
			if (ariaSetSizeProp != null) return ariaSetSizeProp;
			if (!state) return;
			if (!row?.ariaSetSize) return;
			if (row.compositeElement !== state.compositeElement) return;
			return row.ariaSetSize;
		},
		ariaPosInSet(state) {
			if (ariaPosInSetProp != null) return ariaPosInSetProp;
			if (!state) return;
			if (!row?.ariaPosInSet) return;
			if (row.compositeElement !== state.compositeElement) return;
			const rowId = getRowId(state);
			const itemsInRow = state.renderedItems.filter((item) => item.rowId === rowId);
			return row.ariaPosInSet + itemsInRow.findIndex((item) => item.id === id);
		}
	});
	const { isActiveItem, isTabbable } = (0,react_store_dist/* useStoreStateObject */.PX)(store, [
		"activeId",
		"compositeElement",
		"renderedItems",
		"virtualFocus",
		"items"
	], {
		isActiveItem(state) {
			return !!state && state.activeId === id;
		},
		isTabbable(state) {
			if (!state) return true;
			if (!state.compositeElement && !state.renderedItems.length) return true;
			if (state.virtualFocus) return false;
			if (!state.renderedItems.length) return true;
			if (tabbable) return true;
			if (state.activeId === null) return false;
			const item = store?.item(state.activeId);
			if (item?.disabled) return true;
			if (!item?.element) return true;
			return state.activeId === id;
		}
	});
	const getItem = (0,react.useCallback)((item) => {
		const itemDisabled = (item.element ? (0,C4_D9PgF.o)(item.element) : void 0) ?? (inactiveDisabled || trulyDisabled);
		const nextItem = {
			...item,
			id: id || item.id,
			rowId,
			disabled: itemDisabled,
			children: item.element?.textContent,
			typeaheadText
		};
		if (getItemProp) return getItemProp(nextItem);
		return nextItem;
	}, [
		id,
		rowId,
		inactiveDisabled,
		trulyDisabled,
		typeaheadText,
		getItemProp
	]);
	const onFocusProp = props.onFocus;
	const hasFocusedComposite = (0,react.useRef)(false);
	const cancelScheduledFocusRedirectRef = (0,react.useRef)(null);
	const present = (0,composite_utils/* usePresentItem */.CM)(store);
	const onFocus = (0,dist/* useEvent */._q)((event) => {
		onFocusProp?.(event);
		if (event.defaultPrevented) return;
		if ((0,utils_dist/* isPortalEvent */.ho)(event)) return;
		if (!id) return;
		if (!store) return;
		if (targetIsAnotherItem(event, store)) return;
		const { virtualFocus, compositeElement } = store.getState();
		store.setActiveId(id);
		if ((0,utils_dist/* isTextbox */.Bj)(event.currentTarget)) (0,composite_utils/* selectTextField */.iT)(event.currentTarget);
		if (!virtualFocus) {
			if ((0,utils_dist/* isSelfTarget */.uh)(event) && store.item(id)) present({
				id,
				markedOnly: true,
				requireFocus: true,
				scrollIntoView
			});
			return;
		}
		if (!(0,utils_dist/* isSelfTarget */.uh)(event)) return;
		if (isEditableElement(event.currentTarget)) return;
		const redirectFocusToCompositeElement = (relatedTarget, compositeElement) => {
			if (!(0,utils_dist/* isFocusable */.tp)(compositeElement)) {
				if (false) {}
				return;
			}
			const fromComposite = relatedTarget === compositeElement || (0,composite_utils/* isItem */.WZ)(store, relatedTarget);
			if (store.item(id)) present({
				id,
				markedOnly: true,
				requireFocus: true,
				scrollIntoView
			});
			hasFocusedComposite.current = true;
			if (fromComposite) (0,composite_utils/* focusSilently */.hk)(compositeElement);
			else compositeElement.focus({ preventScroll: true });
		};
		if (compositeElement?.isConnected) {
			redirectFocusToCompositeElement(event.relatedTarget, compositeElement);
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
			const nextCompositeElement = state.compositeElement;
			if (!nextCompositeElement?.isConnected) return;
			cancelScheduledFocusRedirect();
			if (!state.virtualFocus) return;
			redirectFocusToCompositeElement(relatedTarget, nextCompositeElement);
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
			if (!state.compositeElement) return true;
			if (!(0,utils_dist/* isTextField */.mB)(state.compositeElement)) return true;
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
		compositeElement
	}), [id, compositeElement]);
	props = (0,dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(composite_context/* CompositeItemContext */.U0.Provider, {
		value: providerValue,
		children: element
	}), [providerValue]);
	props = {
		"data-active-item": isActiveItem || void 0,
		...props,
		id,
		ref: (0,dist/* useMergeRefs */.SV)(ref, markUnmountingRef, props.ref),
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
	return {
		...props,
		"aria-setsize": ariaSetSize,
		"aria-posinset": ariaPosInSet
	};
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
	const htmlProps = useCompositeItem((0,utils/* withDefaultButtonType */.Y)(props));
	return (0,dist/* createElement */.n)(composite_item_TagName, htmlProps);
}));
//#endregion


//# sourceMappingURL=composite-item.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  YC: () => (/* binding */ useCompositeStoreOptions),
  YO: () => (/* binding */ useCompositeStoreProps)
});

// UNUSED EXPORTS: useCompositeStore

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js
var react_store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/collection/collection-store.js
"use client";



//#region src/collection/collection-store.ts
function useCollectionStoreProps(store, update, props) {
	(0,dist/* useUpdateEffect */.w5)(update, [props.store, update]);
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "items", "setItems");
	return store;
}
function useCollectionStore(props = {}) {
	const [store, update] = useStore(Core.createCollectionStore, props);
	return useCollectionStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=collection-store.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/xFYjARz5.js
var xFYjARz5 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/xFYjARz5.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-store.js
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
	(0,xFYjARz5.t)(store, props.store);
	(0,react_store_dist/* useStoreProps */.Tz)(store, props, "activeId", "setActiveId");
	const focusOrderProps = { compositeElementInFocusOrder: props.compositeElementInFocusOrder ?? props.includesBaseElement };
	(0,react_store_dist/* useStoreProps */.Tz)(store, focusOrderProps, "compositeElementInFocusOrder");
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

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ useComposite)
/* harmony export */ });
/* unused harmony export Composite */
/* harmony import */ var _focusable_focusable_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js");
/* harmony import */ var _composite_context_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/composite-context.js");
/* harmony import */ var _chunks_CDh2UhnG_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/CDh2UhnG.js");
/* harmony import */ var _chunks_xFYjARz5_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/xFYjARz5.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/utils.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _ariakit_react_store__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
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
		const state = store.getState();
		const activeElement = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, state.activeId)?.element;
		if (!activeElement) return;
		const { view, ...eventInit } = event;
		if (activeElement !== previousElementRef?.current) activeElement.focus({ preventScroll: true });
		if (!(0,_chunks_CDh2UhnG_js__WEBPACK_IMPORTED_MODULE_5__.t)(activeElement, event.nativeEvent, eventInit)) event.preventDefault();
		if (event.currentTarget.contains(activeElement)) event.stopPropagation();
	});
}
function findFirstEnabledItemInTheLastRow(items) {
	return (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .findFirstEnabledItem */ .oi)((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .flatten2DArray */ .M9)((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .reverseArray */ .q7)((0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .groupItemsByRows */ .es)(items))));
}
/**
* Whether `instance` may act on the store's current move. An unconsumed request
* is available to whichever instance is around; a consumed one stays with the
* instance that consumed it, so its own effects can still run again.
*/
function mayActOnMove(store, instance) {
	const { consumedBy } = (0,_chunks_xFYjARz5_js__WEBPACK_IMPORTED_MODULE_6__.t)(store);
	return !consumedBy || consumedBy === instance;
}
/**
* Records that `instance` consumed the store's move `moves`, so no later
* instance replays it. It is per component instance rather than per composite
* element, because switching the `composite` prop off and on again mounts a
* fresh instance while the element itself survives. A consumption that arrives
* after the count has moved on is dropped, resets included, because it settles
* the request the count held when it started.
*/
function consumeMove(store, instance, moves) {
	if (store.getState().moves !== moves) return;
	(0,_chunks_xFYjARz5_js__WEBPACK_IMPORTED_MODULE_6__.t)(store).consumedBy = instance;
}
/**
* Renders nothing and reacts to the `moves` state by focusing on the active
* item. This lives in a separate memoized component so moving through items
* doesn't re-render the whole composite component, and composite re-renders
* don't re-render this component. It's only rendered when the `composite` prop
* is enabled, so this render-driving subscription doesn't run for non-composite
* widgets. The store hook tracks move requests separately.
*/
const CompositeFocusOnMove = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .memo */ .ph)(function CompositeFocusOnMove({ store, focusOnMove, previousElementRef, present, scrollIntoView }) {
	const moves = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "moves");
	const compositeElement = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "compositeElement");
	const instance = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => ({}), [store.getState]);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		const moveRequest = (0,_chunks_xFYjARz5_js__WEBPACK_IMPORTED_MODULE_6__.t)(store);
		if (!moves) return;
		if (!focusOnMove) return;
		if (!mayActOnMove(store, instance)) return;
		const { activeId } = store.getState();
		if (activeId == null) return;
		if (activeId !== moveRequest.targetId) return;
		return present({
			id: activeId,
			requireFocus: (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .ownsFocus */ .ic)(store),
			focus: true,
			scrollIntoView,
			onConsume: () => consumeMove(store, instance, moves)
		});
	}, [
		store,
		moves,
		focusOnMove,
		compositeElement,
		present,
		scrollIntoView,
		instance
	]);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
		const moveRequest = (0,_chunks_xFYjARz5_js__WEBPACK_IMPORTED_MODULE_6__.t)(store);
		if (!moves) return;
		if (!compositeElement) return;
		if (!mayActOnMove(store, instance)) return;
		const { activeId } = store.getState();
		if (!(activeId === null)) return;
		if (activeId !== moveRequest.targetId) return;
		consumeMove(store, instance, moves);
		const previousElement = previousElementRef.current;
		previousElementRef.current = null;
		if (previousElement) (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .fireBlurEvent */ .c$)(previousElement, { relatedTarget: compositeElement });
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .hasFocus */ .AJ)(compositeElement)) {
			if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isFocusable */ .tp)(compositeElement)) compositeElement.scrollIntoView({
				block: "nearest",
				inline: "nearest"
			});
			compositeElement.focus({ preventScroll: true });
		}
	}, [
		store,
		moves,
		compositeElement,
		previousElementRef,
		instance
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
const useComposite = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .createHook */ .ab)(function useComposite({ store, composite = true, focusOnMove = composite, moveOnKeyPress = true, unstable_scrollIntoView: scrollIntoView, ...props }) {
	const context = (0,_composite_context_js__WEBPACK_IMPORTED_MODULE_8__/* .useCompositeProviderContext */ .Lf)();
	store = store || context;
	(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .invariant */ .V1)(store,  false && 0);
	const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const previousElementRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const present = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .usePresentItem */ .CM)(store);
	const [, setCompositeElement] = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useTransactionState */ .XB)(composite ? store.setCompositeElement : null);
	const virtualFocus = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "virtualFocus");
	const activeId = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, composite && virtualFocus ? ["activeId"] : [], (state) => composite && virtualFocus ? state.activeId : null);
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
			if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event) && !(0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .isItem */ .WZ)(store, relatedTarget)) queueMicrotask(() => present({
				focus: true,
				markedOnly: true,
				requireFocus: true,
				scrollIntoView
			}));
		} else if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isSelfTarget */ .uh)(event)) {
			if (!(0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .isItem */ .WZ)(store, relatedTarget)) {
				const { activeId } = store.getState();
				if (activeId != null) present({
					id: activeId,
					markedOnly: true,
					requireFocus: true,
					scrollIntoView
				});
			}
			store.setActiveId(null);
		}
	});
	const onBlurCaptureProp = props.onBlurCapture;
	const onBlurCapture = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
		onBlurCaptureProp?.(event);
		if (event.defaultPrevented) return;
		if (!store) return;
		const { virtualFocus, activeId, compositeElement } = store.getState();
		if (!virtualFocus) return;
		if (event.currentTarget !== compositeElement) return;
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
		const { orientation, items, renderedItems, activeId, rtl } = store.getState();
		const activeItem = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, activeId);
		if (activeItem?.element?.isConnected) return;
		const elementlessActiveItem = activeItem && activeId != null;
		const movementItems = elementlessActiveItem ? items : renderedItems;
		const isVertical = orientation !== "horizontal";
		const isHorizontal = orientation !== "vertical";
		const grid = isGrid(movementItems);
		if ((event.key === "ArrowLeft" || event.key === "ArrowRight" || event.key === "Home" || event.key === "End") && (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(event.currentTarget)) return;
		const up = () => {
			if (elementlessActiveItem) return store.up({
				activeId,
				renderedItems: movementItems
			});
			if (grid) return findFirstEnabledItemInTheLastRow(renderedItems)?.id;
			return store?.last();
		};
		const right = () => {
			if (elementlessActiveItem) return store.next({
				activeId,
				renderedItems: movementItems
			});
			return rtl ? store.last() : store.first();
		};
		const down = () => {
			if (elementlessActiveItem) return store.down({
				activeId,
				renderedItems: movementItems
			});
			return store.first();
		};
		const left = () => {
			if (elementlessActiveItem) return store.previous({
				activeId,
				renderedItems: movementItems
			});
			return rtl ? store.first() : store.last();
		};
		const action = {
			ArrowUp: (grid || isVertical) && up,
			ArrowRight: (grid || isHorizontal) && right,
			ArrowDown: (grid || isVertical) && down,
			ArrowLeft: (grid || isHorizontal) && left,
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
	props = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useWrapElement */ .w7)(props, (element) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_composite_context_js__WEBPACK_IMPORTED_MODULE_8__/* .CompositeScopedContextProvider */ .aN, {
		value: store,
		children: [element, composite && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(CompositeFocusOnMove, {
			store,
			focusOnMove,
			previousElementRef,
			present,
			scrollIntoView
		})]
	}), [
		store,
		composite,
		focusOnMove,
		present
	]);
	props = {
		"aria-activedescendant": (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, composite && virtualFocus ? ["items"] : [], () => {
			if (!store) return;
			if (!composite) return;
			if (!virtualFocus) return;
			return (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__/* .getEnabledItem */ .hZ)(store, activeId)?.id;
		}),
		...props,
		ref: (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useMergeRefs */ .SV)(ref, setCompositeElement, props.ref),
		onKeyDownCapture,
		onKeyUpCapture,
		onFocusCapture,
		onFocus,
		onBlurCapture,
		onKeyDown
	};
	const focusable = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, composite && !virtualFocus ? ["activeId"] : [], (state) => composite && (virtualFocus || state.activeId === null));
	props = (0,_focusable_focusable_js__WEBPACK_IMPORTED_MODULE_9__/* .useFocusable */ .W)({
		focusable,
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
	const htmlProps = useComposite(props);
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .createElement */ .n)(TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=composite.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/composite/utils.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CM: () => (/* binding */ usePresentItem),
/* harmony export */   Df: () => (/* binding */ markItemMounted),
/* harmony export */   Qh: () => (/* binding */ silentlyFocused),
/* harmony export */   WZ: () => (/* binding */ isItem),
/* harmony export */   es: () => (/* binding */ groupItemsByRows),
/* harmony export */   hZ: () => (/* binding */ getEnabledItem),
/* harmony export */   hk: () => (/* binding */ focusSilently),
/* harmony export */   iT: () => (/* binding */ selectTextField),
/* harmony export */   ic: () => (/* binding */ ownsFocus),
/* harmony export */   nc: () => (/* binding */ markItemUnmounting),
/* harmony export */   oi: () => (/* binding */ findFirstEnabledItem)
/* harmony export */ });
/* unused harmony export flipItems */
/* harmony import */ var _chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/C4_D9PgF.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
/* harmony import */ var _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/composite/composite-store.js");
"use client";






//#region src/composite/utils.ts
const flipItems = _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_1__/* .flipItems */ ._d;
const findFirstEnabledItem = _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_1__/* .findFirstEnabledItem */ .oi;
const groupItemsByRows = _ariakit_components_composite_composite_store__WEBPACK_IMPORTED_MODULE_1__/* .groupItemsByRows */ .es;
const unmountingItems = /* @__PURE__ */ new WeakSet();
/** Marks the brief window between a focused item's ref cleanup and removal. */
function markItemUnmounting(element) {
	if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .getActiveElement */ .bq)(element) !== element) return;
	unmountingItems.add(element);
	queueMicrotask(() => unmountingItems.delete(element));
}
/** Clears a transient ref cleanup when React keeps the same node mounted. */
function markItemMounted(element) {
	unmountingItems.delete(element);
}
/**
* Runs a callback while preserving the composite element's own scroll position.
*
* When virtual focus briefly moves DOM focus from a text input to an item and
* back, browsers reset the input's internal scroll position (e.g. scrollLeft).
* Only applies to text field composite elements, to avoid undoing an
* intentional scroll on scrollable containers.
*/
function withCompositeScrollPreserved(store, callback) {
	const { virtualFocus, compositeElement } = store.getState();
	if (!virtualFocus || !compositeElement || !(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(compositeElement)) {
		callback();
		return;
	}
	const savedScrollLeft = compositeElement.scrollLeft;
	const savedScrollTop = compositeElement.scrollTop;
	callback();
	compositeElement.scrollLeft = savedScrollLeft;
	compositeElement.scrollTop = savedScrollTop;
}
/**
* The popup the composite's items live in, when the composite store is also a
* popup store, as it is for combobox, select and menu.
*/
function getPopupElement(store) {
	const state = store.getState();
	if (!("contentElement" in state)) return null;
	return state.contentElement;
}
/**
* Whether DOM focus is currently inside the composite: on the composite element
* itself, on one of its items, or anywhere in its popup.
*/
function ownsFocus(store) {
	const { compositeElement } = store.getState();
	const activeElement = (0,_chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.r)(compositeElement);
	if (!activeElement) return false;
	if (compositeElement?.contains(activeElement)) return true;
	if (isItem(store, activeElement)) return true;
	return !!getPopupElement(store)?.contains(activeElement);
}
/**
* Focuses a composite item without scrolling, then brings it into view after
* any containing popup has been positioned. Returns a function that cancels a
* pending presentation.
*/
function presentItem({ store, id, focus, markedOnly, requireFocus, focusOwner, scrollIntoView, onConsume }) {
	let element = null;
	let resolvedId;
	let focused = false;
	let focusLeftElement = false;
	let done = false;
	let wasMounted = false;
	let wasOpen = false;
	const compositeAtStart = store.getState().compositeElement;
	const activeAtStart = (0,_chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.r)(compositeAtStart);
	const owner = requireFocus ? focusOwner || activeAtStart : null;
	const startedOnComposite = !!compositeAtStart?.contains(activeAtStart);
	const stillOwnsFocus = (target) => {
		if (!owner) return true;
		const activeElement = (0,_chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.r)(owner);
		if (activeElement === owner) return true;
		if (activeElement === target) return true;
		return ownsFocus(store);
	};
	const abandonedByState = (state) => {
		if ("mounted" in state) {
			if (state.mounted) wasMounted = true;
			else if (wasMounted) return true;
		}
		if ("open" in state) {
			if (state.open) wasOpen = true;
			else if (wasOpen) return true;
		}
		return id != null && state.activeId != null && state.activeId !== id;
	};
	const entersClosedPopup = (state, target) => {
		if (!("open" in state)) return false;
		if (state.open) return false;
		const popup = getPopupElement(store);
		if (!popup?.contains(target)) return false;
		const { compositeElement } = state;
		if (!compositeElement) return false;
		if (popup.contains(compositeElement)) return false;
		return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isVisible */ .zN)(target);
	};
	const resolveElement = (state) => {
		const item = getEnabledItem(store, resolvedId ?? (id === void 0 ? state.activeId : id));
		if (!item?.element?.isConnected) return null;
		resolvedId = item.id;
		return item.element;
	};
	let removeFocusListeners;
	let unsubscribe;
	const cancel = () => {
		done = true;
		removeFocusListeners?.();
		unsubscribe?.();
	};
	let consumed = false;
	const consume = () => {
		if (consumed) return;
		consumed = true;
		onConsume?.();
	};
	const settle = () => {
		if (done) return;
		consume();
		return cancel();
	};
	const present = () => {
		if (done) return;
		const state = store.getState();
		if (abandonedByState(state)) return settle();
		let restoreFocus = false;
		if (!element) {
			element = resolveElement(state);
			if (!element) return;
		} else if (!element.isConnected) {
			restoreFocus = focused && !focusLeftElement && (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .getActiveElement */ .bq)(element) === (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .getDocument */ .YE)(element).body;
			element = resolveElement(state);
			if (!element) return settle();
		}
		if (restoreFocus) focused = false;
		else if (!stillOwnsFocus(element)) return settle();
		const focusWithheld = focus && startedOnComposite && entersClosedPopup(state, element);
		if (focusWithheld) {
			const { activeId } = state;
			if (activeId != null && activeId !== resolvedId) return settle();
		}
		if (focus && !focused && !focusWithheld) {
			focused = true;
			consume();
			focusLeftElement = false;
			const itemElement = element;
			removeFocusListeners?.();
			const onBlur = () => {
				focusLeftElement = !unmountingItems.has(itemElement);
			};
			const onFocus = () => {
				focusLeftElement = false;
			};
			itemElement.addEventListener("blur", onBlur);
			itemElement.addEventListener("focus", onFocus);
			removeFocusListeners = () => {
				itemElement.removeEventListener("blur", onBlur);
				itemElement.removeEventListener("focus", onFocus);
			};
			withCompositeScrollPreserved(store, () => {
				itemElement.focus({ preventScroll: true });
			});
			if (done) return;
		}
		if (markedOnly && !element.hasAttribute("data-autofocus")) {
			if (focusWithheld) return;
			return settle();
		}
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isVisible */ .zN)(element)) return;
		if ("unstable_placing" in state && state.unstable_placing) return;
		if (!focusWithheld) settle();
		if (scrollIntoView) {
			scrollIntoView(element);
			return;
		}
		element.scrollIntoView({
			block: "nearest",
			inline: "nearest"
		});
	};
	unsubscribe = (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_4__/* .subscribe */ .B1)(store, [
		"activeId",
		"items",
		"mounted",
		"open",
		"unstable_placing"
	], present);
	present();
	return Object.assign(cancel, { settle });
}
/**
* Owns at most one pending presentation for the component's current store.
* Layout-effect ownership prevents store swaps or unmounts from stranding a
* request, while newer requests replace older ones. Layout setup runs before
* child passive effects can request presentation; cleanup clears ownership
* during unmount before later handlers or microtasks can enqueue work. The
* initial ref covers the first render before setup.
*
* See https://github.com/ariakit/ariakit/pull/7029
*/
function usePresentItem(store) {
	const cancelRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const ownerRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(store);
	const cancel = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(() => {
		cancelRef.current?.();
		cancelRef.current = null;
	}, []);
	const present = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)((params) => {
		if (!store) return;
		if (ownerRef.current !== store) return;
		cancelRef.current?.settle();
		cancelRef.current = null;
		const cancelCurrent = presentItem({
			store,
			...params
		});
		cancelRef.current = cancelCurrent;
		return cancelCurrent;
	}, [store]);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_5__/* .useSafeLayoutEffect */ .UQ)(() => {
		ownerRef.current = store;
		return () => {
			ownerRef.current = void 0;
			cancel();
		};
	}, [store, cancel]);
	return present;
}
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
	if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(element)) element.setSelectionRange(collapseToEnd ? element.value.length : 0, element.value.length);
	else if (element.isContentEditable) {
		const selection = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .getDocument */ .YE)(element).getSelection();
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