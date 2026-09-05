"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2956],{

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/7PRQYBBV.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   M9: () => (/* binding */ flatten2DArray),
/* harmony export */   q7: () => (/* binding */ reverseArray)
/* harmony export */ });
/* unused harmony exports toArray, addItemToArray */
"use client";

// src/utils/array.ts
function toArray(arg) {
  if (Array.isArray(arg)) {
    return arg;
  }
  return typeof arg !== "undefined" ? [arg] : [];
}
function addItemToArray(array, item, index = -1) {
  if (!(index in array)) {
    return [...array, item];
  }
  return [...array.slice(0, index), item, ...array.slice(index)];
}
function flatten2DArray(array) {
  const flattened = [];
  for (const row of array) {
    flattened.push(...row);
  }
  return flattened;
}
function reverseArray(array) {
  return array.slice().reverse();
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/KZX46JDB.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   I: () => (/* binding */ createCollectionStore)
/* harmony export */ });
/* harmony import */ var _G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
/* harmony import */ var _XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
/* harmony import */ var _UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
"use client";




// src/collection/collection-store.ts
function getCommonParent(items) {
  var _a, _b;
  const firstItem = items.find((item) => !!item.element);
  const lastElement = (_a = [...items].reverse().find((item) => !!item.element)) == null ? void 0 : _a.element;
  let parentElement = (_b = firstItem == null ? void 0 : firstItem.element) == null ? void 0 : _b.parentElement;
  if (!lastElement) {
    return (0,_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .YE)(parentElement).body;
  }
  while (parentElement) {
    const parent = parentElement;
    if (parent.contains(lastElement)) {
      return parentElement;
    }
    parentElement = parentElement.parentElement;
  }
  return (0,_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .YE)(parentElement).body;
}
function getPrivateStore(store) {
  return store == null ? void 0 : store.__unstablePrivateStore;
}
function createCollectionStore(props = {}) {
  var _a;
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .throwOnConflictingProps */ .UE)(props, props.store);
  const syncState = (_a = props.store) == null ? void 0 : _a.getState();
  const items = (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_2__/* .defaultValue */ .Jh)(
    props.items,
    syncState == null ? void 0 : syncState.items,
    props.defaultItems,
    []
  );
  const itemsMap = new Map(items.map((item) => [item.id, item]));
  const initialState = {
    items,
    renderedItems: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_2__/* .defaultValue */ .Jh)(syncState == null ? void 0 : syncState.renderedItems, [])
  };
  const syncPrivateStore = getPrivateStore(props.store);
  const privateStore = (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .createStore */ .y$)(
    { items, renderedItems: initialState.renderedItems },
    syncPrivateStore
  );
  const collection = (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .createStore */ .y$)(initialState, props.store);
  const sortItems = (renderedItems) => {
    const sortedItems = (0,_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .sortBasedOnDOMPosition */ .gH)(renderedItems, (i) => i.element);
    privateStore.setState("renderedItems", sortedItems);
    collection.setState("renderedItems", sortedItems);
  };
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(collection, () => (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .init */ .Ts)(privateStore));
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(privateStore, () => {
    return (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .batch */ .vA)(privateStore, ["items"], (state) => {
      collection.setState("items", state.items);
    });
  });
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .setup */ .mj)(privateStore, () => {
    return (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_1__/* .batch */ .vA)(privateStore, ["renderedItems"], (state) => {
      let firstRun = true;
      let raf = requestAnimationFrame(() => {
        const { renderedItems } = collection.getState();
        if (state.renderedItems === renderedItems) return;
        sortItems(state.renderedItems);
      });
      if (typeof IntersectionObserver !== "function") {
        return () => cancelAnimationFrame(raf);
      }
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
    setItems((items2) => {
      const index = items2.findIndex(({ id }) => id === item.id);
      const nextItems = items2.slice();
      if (index !== -1) {
        prevItem = items2[index];
        const nextItem = { ...prevItem, ...item };
        nextItems[index] = nextItem;
        itemsMap.set(item.id, nextItem);
      } else {
        nextItems.push(item);
        itemsMap.set(item.id, item);
      }
      return nextItems;
    });
    const unmergeItem = () => {
      setItems((items2) => {
        if (!prevItem) {
          if (canDeleteFromMap) {
            itemsMap.delete(item.id);
          }
          return items2.filter(({ id }) => id !== item.id);
        }
        const index = items2.findIndex(({ id }) => id === item.id);
        if (index === -1) return items2;
        const nextItems = items2.slice();
        nextItems[index] = prevItem;
        itemsMap.set(item.id, prevItem);
        return nextItems;
      });
    };
    return unmergeItem;
  };
  const registerItem = (item) => mergeItem(
    item,
    (getItems) => privateStore.setState("items", getItems),
    true
  );
  return {
    ...collection,
    registerItem,
    renderItem: (item) => (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_2__/* .chain */ .cy)(
      registerItem(item),
      mergeItem(
        item,
        (getItems) => privateStore.setState("renderedItems", getItems)
      )
    ),
    item: (id) => {
      if (!id) return null;
      let item = itemsMap.get(id);
      if (!item) {
        const { items: items2 } = privateStore.getState();
        item = items2.find((item2) => item2.id === id);
        if (item) {
          itemsMap.set(id, item);
        }
      }
      return item || null;
    },
    // @ts-expect-error Internal
    __unstablePrivateStore: privateStore
  };
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   z: () => (/* binding */ createCompositeStore)
/* harmony export */ });
/* harmony import */ var _7PRQYBBV_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/7PRQYBBV.js");
/* harmony import */ var _KZX46JDB_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/KZX46JDB.js");
/* harmony import */ var _XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
/* harmony import */ var _UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
"use client";





// src/composite/composite-store.ts
var NULL_ITEM = { id: null };
function findFirstEnabledItem(items, excludeId) {
  return items.find((item) => {
    if (excludeId) {
      return !item.disabled && item.id !== excludeId;
    }
    return !item.disabled;
  });
}
function getEnabledItems(items, excludeId) {
  return items.filter((item) => {
    if (excludeId) {
      return !item.disabled && item.id !== excludeId;
    }
    return !item.disabled;
  });
}
function getItemsInRow(items, rowId) {
  return items.filter((item) => item.rowId === rowId);
}
function flipItems(items, activeId, shouldInsertNullItem = false) {
  const index = items.findIndex((item) => item.id === activeId);
  return [
    ...items.slice(index + 1),
    ...shouldInsertNullItem ? [NULL_ITEM] : [],
    ...items.slice(0, index)
  ];
}
function groupItemsByRows(items) {
  const rows = [];
  for (const item of items) {
    const row = rows.find((currentRow) => {
      var _a;
      return ((_a = currentRow[0]) == null ? void 0 : _a.rowId) === item.rowId;
    });
    if (row) {
      row.push(item);
    } else {
      rows.push([item]);
    }
  }
  return rows;
}
function getMaxRowLength(array) {
  let maxLength = 0;
  for (const { length } of array) {
    if (length > maxLength) {
      maxLength = length;
    }
  }
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
  for (const row of rows) {
    for (let i = 0; i < maxLength; i += 1) {
      const item = row[i];
      if (!item || focusShift && item.disabled) {
        const isFirst = i === 0;
        const previousItem = isFirst && focusShift ? findFirstEnabledItem(row) : row[i - 1];
        row[i] = previousItem && activeId !== previousItem.id && focusShift ? previousItem : createEmptyItem(previousItem == null ? void 0 : previousItem.rowId);
      }
    }
  }
  return rows;
}
function verticalizeItems(items) {
  const rows = groupItemsByRows(items);
  const maxLength = getMaxRowLength(rows);
  const verticalized = [];
  for (let i = 0; i < maxLength; i += 1) {
    for (const row of rows) {
      const item = row[i];
      if (item) {
        verticalized.push({
          ...item,
          // If there's no rowId, it means that it's not a grid composite, but
          // a single row instead. So, instead of verticalizing it, that is,
          // assigning a different rowId based on the column index, we keep it
          // undefined so they will be part of the same row. This is useful
          // when using up/down on one-dimensional composites.
          rowId: item.rowId ? `${i}` : void 0
        });
      }
    }
  }
  return verticalized;
}
function createCompositeStore(props = {}) {
  var _a;
  const syncState = (_a = props.store) == null ? void 0 : _a.getState();
  const collection = (0,_KZX46JDB_js__WEBPACK_IMPORTED_MODULE_0__/* .createCollectionStore */ .I)(props);
  const activeId = (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(
    props.activeId,
    syncState == null ? void 0 : syncState.activeId,
    props.defaultActiveId
  );
  const initialState = {
    ...collection.getState(),
    id: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(
      props.id,
      syncState == null ? void 0 : syncState.id,
      `id-${Math.random().toString(36).slice(2, 8)}`
    ),
    activeId,
    baseElement: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState == null ? void 0 : syncState.baseElement, null),
    includesBaseElement: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(
      props.includesBaseElement,
      syncState == null ? void 0 : syncState.includesBaseElement,
      activeId === null
    ),
    moves: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState == null ? void 0 : syncState.moves, 0),
    orientation: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(
      props.orientation,
      syncState == null ? void 0 : syncState.orientation,
      "both"
    ),
    rtl: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.rtl, syncState == null ? void 0 : syncState.rtl, false),
    virtualFocus: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(
      props.virtualFocus,
      syncState == null ? void 0 : syncState.virtualFocus,
      false
    ),
    focusLoop: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusLoop, syncState == null ? void 0 : syncState.focusLoop, false),
    focusWrap: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusWrap, syncState == null ? void 0 : syncState.focusWrap, false),
    focusShift: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.focusShift, syncState == null ? void 0 : syncState.focusShift, false)
  };
  const composite = (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_2__/* .createStore */ .y$)(initialState, collection, props.store);
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_2__/* .setup */ .mj)(
    composite,
    () => (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_2__/* .sync */ .OH)(composite, ["renderedItems", "activeId"], (state) => {
      composite.setState("activeId", (activeId2) => {
        var _a2;
        if (activeId2 !== void 0) return activeId2;
        return (_a2 = findFirstEnabledItem(state.renderedItems)) == null ? void 0 : _a2.id;
      });
    })
  );
  const getNextId = (direction = "next", options = {}) => {
    var _a2, _b;
    const defaultState = composite.getState();
    const {
      skip = 0,
      activeId: activeId2 = defaultState.activeId,
      focusShift = defaultState.focusShift,
      focusLoop = defaultState.focusLoop,
      focusWrap = defaultState.focusWrap,
      includesBaseElement = defaultState.includesBaseElement,
      renderedItems = defaultState.renderedItems,
      rtl = defaultState.rtl
    } = options;
    const isVerticalDirection = direction === "up" || direction === "down";
    const isNextDirection = direction === "next" || direction === "down";
    const canReverse = isNextDirection ? rtl && !isVerticalDirection : !rtl || isVerticalDirection;
    const canShift = focusShift && !skip;
    let items = !isVerticalDirection ? renderedItems : (0,_7PRQYBBV_js__WEBPACK_IMPORTED_MODULE_3__/* .flatten2DArray */ .M9)(
      normalizeRows(groupItemsByRows(renderedItems), activeId2, canShift)
    );
    items = canReverse ? (0,_7PRQYBBV_js__WEBPACK_IMPORTED_MODULE_3__/* .reverseArray */ .q7)(items) : items;
    items = isVerticalDirection ? verticalizeItems(items) : items;
    if (activeId2 == null) {
      return (_a2 = findFirstEnabledItem(items)) == null ? void 0 : _a2.id;
    }
    const activeItem = items.find((item) => item.id === activeId2);
    if (!activeItem) {
      return (_b = findFirstEnabledItem(items)) == null ? void 0 : _b.id;
    }
    const isGrid = items.some((item) => item.rowId);
    const activeIndex = items.indexOf(activeItem);
    const nextItems = items.slice(activeIndex + 1);
    const nextItemsInRow = getItemsInRow(nextItems, activeItem.rowId);
    if (skip) {
      const nextEnabledItemsInRow = getEnabledItems(nextItemsInRow, activeId2);
      const nextItem2 = nextEnabledItemsInRow.slice(skip)[0] || // If we can't find an item, just return the last one.
      nextEnabledItemsInRow[nextEnabledItemsInRow.length - 1];
      return nextItem2 == null ? void 0 : nextItem2.id;
    }
    const canLoop = focusLoop && (isVerticalDirection ? focusLoop !== "horizontal" : focusLoop !== "vertical");
    const canWrap = isGrid && focusWrap && (isVerticalDirection ? focusWrap !== "horizontal" : focusWrap !== "vertical");
    const hasNullItem = isNextDirection ? (!isGrid || isVerticalDirection) && canLoop && includesBaseElement : isVerticalDirection ? includesBaseElement : false;
    if (canLoop) {
      const loopItems = canWrap && !hasNullItem ? items : getItemsInRow(items, activeItem.rowId);
      const sortedItems = flipItems(loopItems, activeId2, hasNullItem);
      const nextItem2 = findFirstEnabledItem(sortedItems, activeId2);
      return nextItem2 == null ? void 0 : nextItem2.id;
    }
    if (canWrap) {
      const nextItem2 = findFirstEnabledItem(
        // We can use nextItems, which contains all the next items, including
        // items from other rows, to wrap between rows. However, if there is a
        // null item (the composite container), we'll only use the next items in
        // the row. So moving next from the last item will focus on the
        // composite container. On grid composites, horizontal navigation never
        // focuses on the composite container, only vertical.
        hasNullItem ? nextItemsInRow : nextItems,
        activeId2
      );
      const nextId = hasNullItem ? (nextItem2 == null ? void 0 : nextItem2.id) || null : nextItem2 == null ? void 0 : nextItem2.id;
      return nextId;
    }
    const nextItem = findFirstEnabledItem(nextItemsInRow, activeId2);
    if (!nextItem && hasNullItem) {
      return null;
    }
    return nextItem == null ? void 0 : nextItem.id;
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
    first: () => {
      var _a2;
      return (_a2 = findFirstEnabledItem(composite.getState().renderedItems)) == null ? void 0 : _a2.id;
    },
    last: () => {
      var _a2;
      return (_a2 = findFirstEnabledItem((0,_7PRQYBBV_js__WEBPACK_IMPORTED_MODULE_3__/* .reverseArray */ .q7)(composite.getState().renderedItems))) == null ? void 0 : _a2.id;
    },
    next: (options) => {
      if (options !== void 0 && typeof options === "number") {
        options = { skip: options };
      }
      return getNextId("next", options);
    },
    previous: (options) => {
      if (options !== void 0 && typeof options === "number") {
        options = { skip: options };
      }
      return getNextId("previous", options);
    },
    down: (options) => {
      if (options !== void 0 && typeof options === "number") {
        options = { skip: options };
      }
      return getNextId("down", options);
    },
    up: (options) => {
      if (options !== void 0 && typeof options === "number") {
        options = { skip: options };
      }
      return getNextId("up", options);
    }
  };
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $o: () => (/* binding */ CompositeRowContext),
/* harmony export */   Lf: () => (/* binding */ useCompositeProviderContext),
/* harmony export */   U0: () => (/* binding */ CompositeItemContext),
/* harmony export */   aN: () => (/* binding */ CompositeScopedContextProvider),
/* harmony export */   k: () => (/* binding */ useCompositeContext),
/* harmony export */   ws: () => (/* binding */ CompositeContextProvider)
/* harmony export */ });
/* unused harmony export useCompositeScopedContext */
/* harmony import */ var _FQHJBBMI_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FQHJBBMI.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";



// src/composite/composite-context.tsx

var ctx = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__/* .createStoreContext */ .B0)(
  [_FQHJBBMI_js__WEBPACK_IMPORTED_MODULE_2__/* .CollectionContextProvider */ .LN],
  [_FQHJBBMI_js__WEBPACK_IMPORTED_MODULE_2__/* .CollectionScopedContextProvider */ .zX]
);
var useCompositeContext = ctx.useContext;
var useCompositeScopedContext = ctx.useScopedContext;
var useCompositeProviderContext = ctx.useProviderContext;
var CompositeContextProvider = ctx.ContextProvider;
var CompositeScopedContextProvider = ctx.ScopedContextProvider;
var CompositeItemContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(
  void 0
);
var CompositeRowContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(
  void 0
);




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ useComposite)
/* harmony export */ });
/* unused harmony export Composite */
/* harmony import */ var _7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/7NJRHOSP.js");
/* harmony import */ var _55FNNNML_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js");
/* harmony import */ var _GR523XJ6_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js");
/* harmony import */ var _YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
/* harmony import */ var _CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
/* harmony import */ var _ariakit_core_utils_array__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/7PRQYBBV.js");
/* harmony import */ var _ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
/* harmony import */ var _ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js");
/* harmony import */ var _ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js");
/* harmony import */ var _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
"use client";







// src/composite/composite.tsx







var TagName = "div";
function isGrid(items) {
  return items.some((item) => !!item.rowId);
}
function isPrintableKey(event) {
  const target = event.target;
  if (target && !(0,_ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(target)) return false;
  return event.key.length === 1 && !event.ctrlKey && !event.metaKey;
}
function isModifierKey(event) {
  return event.key === "Shift" || event.key === "Control" || event.key === "Alt" || event.key === "Meta";
}
function useKeyboardEventProxy(store, onKeyboardEvent, previousElementRef) {
  return (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
    var _a;
    onKeyboardEvent == null ? void 0 : onKeyboardEvent(event);
    if (event.defaultPrevented) return;
    if (event.isPropagationStopped()) return;
    if (!(0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event)) return;
    if (isModifierKey(event)) return;
    if (isPrintableKey(event)) return;
    const state = store.getState();
    const activeElement = (_a = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .getEnabledItem */ .hZ)(store, state.activeId)) == null ? void 0 : _a.element;
    if (!activeElement) return;
    const { view, ...eventInit } = event;
    const previousElement = previousElementRef == null ? void 0 : previousElementRef.current;
    if (activeElement !== previousElement) {
      activeElement.focus();
    }
    if (!(0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .fireKeyboardEvent */ .sz)(activeElement, event.type, eventInit)) {
      event.preventDefault();
    }
    if (event.currentTarget.contains(activeElement)) {
      event.stopPropagation();
    }
  });
}
function findFirstEnabledItemInTheLastRow(items) {
  return (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .findFirstEnabledItem */ .oi)(
    (0,_ariakit_core_utils_array__WEBPACK_IMPORTED_MODULE_6__/* .flatten2DArray */ .M9)((0,_ariakit_core_utils_array__WEBPACK_IMPORTED_MODULE_6__/* .reverseArray */ .q7)((0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .groupItemsByRows */ .es)(items)))
  );
}
function withBaseScrollPreserved(store, callback) {
  const { virtualFocus, baseElement } = store.getState();
  if (!virtualFocus || !baseElement || !(0,_ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(baseElement)) {
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
  const activeItem = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(
    store,
    (state) => (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .getEnabledItem */ .hZ)(store, state.activeId)
  );
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const activeElement = activeItem == null ? void 0 : activeItem.element;
    if (!scheduled) return;
    if (!activeElement) return;
    setScheduled(false);
    withBaseScrollPreserved(store, () => {
      activeElement.focus({ preventScroll: true });
    });
  }, [store, activeItem, scheduled]);
  return schedule;
}
var useComposite = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_8__/* .createHook */ .ab)(
  function useComposite2({
    store,
    composite = true,
    focusOnMove = composite,
    moveOnKeyPress = true,
    ...props
  }) {
    const context = (0,_55FNNNML_js__WEBPACK_IMPORTED_MODULE_9__/* .useCompositeProviderContext */ .Lf)();
    store = store || context;
    (0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_10__/* .invariant */ .V1)(
      store,
       false && 0
    );
    const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
    const previousElementRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
    const scheduleFocus = useScheduleFocus(store);
    const moves = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "moves");
    const [, setBaseElement] = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useTransactionState */ .XB)(
      composite ? store.setBaseElement : null
    );
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      var _a;
      if (!store) return;
      if (!moves) return;
      if (!composite) return;
      if (!focusOnMove) return;
      const { activeId: activeId2 } = store.getState();
      const itemElement = (_a = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .getEnabledItem */ .hZ)(store, activeId2)) == null ? void 0 : _a.element;
      if (!itemElement) return;
      withBaseScrollPreserved(store, () => (0,_ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_11__/* .focusIntoView */ .WA)(itemElement));
    }, [store, moves, composite, focusOnMove]);
    (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
      if (!store) return;
      if (!moves) return;
      if (!composite) return;
      const { baseElement, activeId: activeId2 } = store.getState();
      const isSelfAcive = activeId2 === null;
      if (!isSelfAcive) return;
      if (!baseElement) return;
      const previousElement = previousElementRef.current;
      previousElementRef.current = null;
      if (previousElement) {
        (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .fireBlurEvent */ .c$)(previousElement, { relatedTarget: baseElement });
      }
      if (!(0,_ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_11__/* .hasFocus */ .AJ)(baseElement)) {
        baseElement.focus();
      }
    }, [store, moves, composite]);
    const activeId = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "activeId");
    const virtualFocus = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "virtualFocus");
    (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
      var _a;
      if (!store) return;
      if (!composite) return;
      if (!virtualFocus) return;
      const previousElement = previousElementRef.current;
      previousElementRef.current = null;
      if (!previousElement) return;
      const activeElement = (_a = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .getEnabledItem */ .hZ)(store, activeId)) == null ? void 0 : _a.element;
      const relatedTarget = activeElement || (0,_ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_2__/* .getActiveElement */ .bq)(previousElement);
      if (relatedTarget === previousElement) return;
      (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .fireBlurEvent */ .c$)(previousElement, { relatedTarget });
    }, [store, activeId, virtualFocus, composite]);
    const onKeyDownCapture = useKeyboardEventProxy(
      store,
      props.onKeyDownCapture,
      previousElementRef
    );
    const onKeyUpCapture = useKeyboardEventProxy(
      store,
      props.onKeyUpCapture,
      previousElementRef
    );
    const onFocusCaptureProp = props.onFocusCapture;
    const onFocusCapture = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
      onFocusCaptureProp == null ? void 0 : onFocusCaptureProp(event);
      if (event.defaultPrevented) return;
      if (!store) return;
      const { virtualFocus: virtualFocus2 } = store.getState();
      if (!virtualFocus2) return;
      const previousActiveElement = event.relatedTarget;
      const isSilentlyFocused = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .silentlyFocused */ .Qh)(event.currentTarget);
      if ((0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event) && isSilentlyFocused) {
        event.stopPropagation();
        previousElementRef.current = previousActiveElement;
      }
    });
    const onFocusProp = props.onFocus;
    const onFocus = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
      onFocusProp == null ? void 0 : onFocusProp(event);
      if (event.defaultPrevented) return;
      if (!composite) return;
      if (!store) return;
      const { relatedTarget } = event;
      const { virtualFocus: virtualFocus2 } = store.getState();
      if (virtualFocus2) {
        if ((0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event) && !(0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .isItem */ .WZ)(store, relatedTarget)) {
          queueMicrotask(scheduleFocus);
        }
      } else if ((0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event)) {
        store.setActiveId(null);
      }
    });
    const onBlurCaptureProp = props.onBlurCapture;
    const onBlurCapture = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
      var _a;
      onBlurCaptureProp == null ? void 0 : onBlurCaptureProp(event);
      if (event.defaultPrevented) return;
      if (!store) return;
      const { virtualFocus: virtualFocus2, activeId: activeId2 } = store.getState();
      if (!virtualFocus2) return;
      const activeElement = (_a = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .getEnabledItem */ .hZ)(store, activeId2)) == null ? void 0 : _a.element;
      const nextActiveElement = event.relatedTarget;
      const nextActiveElementIsItem = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .isItem */ .WZ)(store, nextActiveElement);
      const previousElement = previousElementRef.current;
      previousElementRef.current = null;
      if ((0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event) && nextActiveElementIsItem) {
        if (nextActiveElement === activeElement) {
          if (previousElement && previousElement !== nextActiveElement) {
            (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .fireBlurEvent */ .c$)(previousElement, event);
          }
        } else if (activeElement) {
          (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .fireBlurEvent */ .c$)(activeElement, event);
        } else if (previousElement) {
          (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .fireBlurEvent */ .c$)(previousElement, event);
        }
        event.stopPropagation();
      } else {
        const targetIsItem = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .isItem */ .WZ)(store, event.target);
        if (!targetIsItem && activeElement) {
          (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .fireBlurEvent */ .c$)(activeElement, event);
        }
      }
    });
    const onKeyDownProp = props.onKeyDown;
    const moveOnKeyPressProp = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useBooleanEvent */ .O4)(moveOnKeyPress);
    const onKeyDown = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useEvent */ ._q)((event) => {
      var _a;
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (event.nativeEvent.isComposing) return;
      if (event.defaultPrevented) return;
      if (!store) return;
      if (!(0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event)) return;
      const { orientation, renderedItems, activeId: activeId2 } = store.getState();
      const activeItem = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .getEnabledItem */ .hZ)(store, activeId2);
      if ((_a = activeItem == null ? void 0 : activeItem.element) == null ? void 0 : _a.isConnected) return;
      const isVertical = orientation !== "horizontal";
      const isHorizontal = orientation !== "vertical";
      const grid = isGrid(renderedItems);
      const isHorizontalKey = event.key === "ArrowLeft" || event.key === "ArrowRight" || event.key === "Home" || event.key === "End";
      if (isHorizontalKey && (0,_ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_2__/* .isTextField */ .mB)(event.currentTarget)) return;
      const up = () => {
        if (grid) {
          const item = findFirstEnabledItemInTheLastRow(renderedItems);
          return item == null ? void 0 : item.id;
        }
        return store == null ? void 0 : store.last();
      };
      const keyMap = {
        ArrowUp: (grid || isVertical) && up,
        ArrowRight: (grid || isHorizontal) && store.first,
        ArrowDown: (grid || isVertical) && store.first,
        ArrowLeft: (grid || isHorizontal) && store.last,
        Home: store.first,
        End: store.last,
        PageUp: store.first,
        PageDown: store.last
      };
      const action = keyMap[event.key];
      if (action) {
        const id = action();
        if (id !== void 0) {
          if (!moveOnKeyPressProp(event)) return;
          event.preventDefault();
          store.move(id);
        }
      }
    });
    props = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useWrapElement */ .w7)(
      props,
      (element) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_55FNNNML_js__WEBPACK_IMPORTED_MODULE_9__/* .CompositeContextProvider */ .ws, { value: store, children: element }),
      [store]
    );
    const activeDescendant = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, (state) => {
      var _a;
      if (!store) return;
      if (!composite) return;
      if (!state.virtualFocus) return;
      return (_a = (0,_7NJRHOSP_js__WEBPACK_IMPORTED_MODULE_5__/* .getEnabledItem */ .hZ)(store, state.activeId)) == null ? void 0 : _a.id;
    });
    props = {
      "aria-activedescendant": activeDescendant,
      ...props,
      ref: (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_3__/* .useMergeRefs */ .SV)(ref, setBaseElement, props.ref),
      onKeyDownCapture,
      onKeyUpCapture,
      onFocusCapture,
      onFocus,
      onBlurCapture,
      onKeyDown
    };
    const focusable = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(
      store,
      (state) => composite && (state.virtualFocus || state.activeId === null)
    );
    props = (0,_GR523XJ6_js__WEBPACK_IMPORTED_MODULE_12__/* .useFocusable */ .W)({ focusable, ...props });
    return props;
  }
);
var Composite = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_8__/* .forwardRef */ .Rf)(function Composite2(props) {
  const htmlProps = useComposite(props);
  return (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_8__/* .createElement */ .n)(TagName, htmlProps);
});




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/7NJRHOSP.js":
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
/* harmony import */ var _ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
"use client";

// src/composite/utils.ts

var NULL_ITEM = { id: null };
function flipItems(items, activeId, shouldInsertNullItem = false) {
  const index = items.findIndex((item) => item.id === activeId);
  return [
    ...items.slice(index + 1),
    ...shouldInsertNullItem ? [NULL_ITEM] : [],
    ...items.slice(0, index)
  ];
}
function findFirstEnabledItem(items, excludeId) {
  return items.find((item) => {
    if (excludeId) {
      return !item.disabled && item.id !== excludeId;
    }
    return !item.disabled;
  });
}
function getEnabledItem(store, id) {
  if (!id) return null;
  return store.item(id) || null;
}
function groupItemsByRows(items) {
  const rows = [];
  for (const item of items) {
    const row = rows.find((currentRow) => {
      var _a;
      return ((_a = currentRow[0]) == null ? void 0 : _a.rowId) === item.rowId;
    });
    if (row) {
      row.push(item);
    } else {
      rows.push([item]);
    }
  }
  return rows;
}
function selectTextField(element, collapseToEnd = false) {
  if ((0,_ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_0__/* .isTextField */ .mB)(element)) {
    element.setSelectionRange(
      collapseToEnd ? element.value.length : 0,
      element.value.length
    );
  } else if (element.isContentEditable) {
    const selection = (0,_ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .YE)(element).getSelection();
    selection == null ? void 0 : selection.selectAllChildren(element);
    if (collapseToEnd) {
      selection == null ? void 0 : selection.collapseToEnd();
    }
  }
}
var FOCUS_SILENTLY = /* @__PURE__ */ Symbol("FOCUS_SILENTLY");
function focusSilently(element) {
  element[FOCUS_SILENTLY] = true;
  element.focus({ preventScroll: true });
}
function silentlyFocused(element) {
  const isSilentlyFocused = element[FOCUS_SILENTLY];
  delete element[FOCUS_SILENTLY];
  return isSilentlyFocused;
}
function isItem(store, element, exclude) {
  if (!element) return false;
  if (element === exclude) return false;
  const item = store.item(element.id);
  if (!item) return false;
  if (exclude && item.element === exclude) return false;
  return true;
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FQHJBBMI.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $I: () => (/* binding */ useCollectionContext),
/* harmony export */   LN: () => (/* binding */ CollectionContextProvider),
/* harmony export */   zX: () => (/* binding */ CollectionScopedContextProvider)
/* harmony export */ });
/* unused harmony exports useCollectionScopedContext, useCollectionProviderContext */
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
"use client";


// src/collection/collection-context.tsx
var ctx = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__/* .createStoreContext */ .B0)();
var useCollectionContext = ctx.useContext;
var useCollectionScopedContext = ctx.useScopedContext;
var useCollectionProviderContext = ctx.useProviderContext;
var CollectionContextProvider = ctx.ContextProvider;
var CollectionScopedContextProvider = ctx.ScopedContextProvider;




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IKWLDXMV.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   v: () => (/* binding */ useCollectionItem)
/* harmony export */ });
/* unused harmony export CollectionItem */
/* harmony import */ var _FQHJBBMI_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FQHJBBMI.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
/* harmony import */ var _CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
/* harmony import */ var _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";




// src/collection/collection-item.tsx


var TagName = "div";
var useCollectionItem = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__/* .createHook */ .ab)(
  function useCollectionItem2({
    store,
    shouldRegisterItem = true,
    getItem = _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__/* .identity */ .D_,
    // @ts-expect-error This prop may come from a collection renderer.
    element,
    ...props
  }) {
    const context = (0,_FQHJBBMI_js__WEBPACK_IMPORTED_MODULE_3__/* .useCollectionContext */ .$I)();
    store = store || context;
    const id = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__/* .useId */ .Bi)(props.id);
    const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(element);
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      const element2 = ref.current;
      if (!id) return;
      if (!element2) return;
      if (!shouldRegisterItem) return;
      const item = getItem({ id, element: element2 });
      return store == null ? void 0 : store.renderItem(item);
    }, [id, shouldRegisterItem, getItem, store]);
    props = {
      ...props,
      ref: (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__/* .useMergeRefs */ .SV)(ref, props.ref)
    };
    return (0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__/* .removeUndefinedValues */ .HR)(props);
  }
);
var CollectionItem = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__/* .forwardRef */ .Rf)(function CollectionItem2(props) {
  const htmlProps = useCollectionItem(props);
  return (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__/* .createElement */ .n)(TagName, htmlProps);
});




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  l: () => (/* binding */ CompositeItem),
  k: () => (/* binding */ useCompositeItem)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/7NJRHOSP.js
var _7NJRHOSP = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/7NJRHOSP.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IKWLDXMV.js
var IKWLDXMV = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IKWLDXMV.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js
var _55FNNNML = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js
var GR523XJ6 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js
var TVXRYIJB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js
var CEM7J6TT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js
var G7XPWBXK = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js
var events = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js
var UWJK2WK2 = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/GMGLSF2B.js
var GMGLSF2B = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/GMGLSF2B.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GHQLK2M5.js
"use client";




// src/command/command.tsx





var TagName = "button";
function isNativeClick(event) {
  if (!event.isTrusted) return false;
  const element = event.currentTarget;
  if (event.key === "Enter") {
    return (0,G7XPWBXK/* isButton */.Bm)(element) || element.tagName === "SUMMARY" || element.tagName === "A";
  }
  if (event.key === " ") {
    return (0,G7XPWBXK/* isButton */.Bm)(element) || element.tagName === "SUMMARY" || element.tagName === "INPUT" || element.tagName === "SELECT";
  }
  return false;
}
var symbol = /* @__PURE__ */ Symbol("command");
var useCommand = (0,TVXRYIJB/* createHook */.ab)(
  function useCommand2({ clickOnEnter = true, clickOnSpace = true, ...props }) {
    const ref = (0,react.useRef)(null);
    const [isNativeButton, setIsNativeButton] = (0,react.useState)(false);
    (0,react.useEffect)(() => {
      if (!ref.current) return;
      setIsNativeButton((0,G7XPWBXK/* isButton */.Bm)(ref.current));
    }, []);
    const [active, setActive] = (0,react.useState)(false);
    const activeRef = (0,react.useRef)(false);
    const disabled = (0,UWJK2WK2/* disabledFromProps */.$f)(props);
    const [isDuplicate, metadataProps] = (0,CEM7J6TT/* useMetadataProps */.P1)(props, symbol, true);
    const onKeyDownProp = props.onKeyDown;
    const onKeyDown = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      const element = event.currentTarget;
      if (event.defaultPrevented) return;
      if (isDuplicate) return;
      if (disabled) return;
      if (!(0,events/* isSelfTarget */.uh)(event)) return;
      if ((0,G7XPWBXK/* isTextField */.mB)(element)) return;
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
            const click = () => (0,events/* fireClickEvent */.hY)(element, eventInit);
            if ((0,GMGLSF2B/* isFirefox */.gm)()) {
              (0,events/* queueBeforeEvent */.nz)(element, "keyup", click);
            } else {
              queueMicrotask(click);
            }
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
    const onKeyUp = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onKeyUpProp == null ? void 0 : onKeyUpProp(event);
      if (event.defaultPrevented) return;
      if (isDuplicate) return;
      if (disabled) return;
      if (event.metaKey) return;
      const isSpace = clickOnSpace && event.key === " ";
      if (activeRef.current && isSpace) {
        activeRef.current = false;
        if (!isNativeClick(event)) {
          event.preventDefault();
          setActive(false);
          const element = event.currentTarget;
          const { view, ...eventInit } = event;
          queueMicrotask(() => (0,events/* fireClickEvent */.hY)(element, eventInit));
        }
      }
    });
    props = {
      "data-active": active || void 0,
      type: isNativeButton ? "button" : void 0,
      ...metadataProps,
      ...props,
      ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref),
      onKeyDown,
      onKeyUp
    };
    props = (0,GR523XJ6/* useFocusable */.W)(props);
    return props;
  }
);
var Command = (0,TVXRYIJB/* forwardRef */.Rf)(function Command2(props) {
  const htmlProps = useCommand(props);
  return (0,TVXRYIJB/* createElement */.n)(TagName, htmlProps);
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js
var YAS7X7HB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js
"use client";








// src/composite/composite-item.tsx






var ZCYMVQGT_TagName = "button";
function isEditableElement(element) {
  if ((0,G7XPWBXK/* isTextbox */.Bj)(element)) return true;
  return element.tagName === "INPUT" && !(0,G7XPWBXK/* isButton */.Bm)(element);
}
function getNextPageOffset(scrollingElement, pageUp = false) {
  const height = scrollingElement.clientHeight;
  const { top } = scrollingElement.getBoundingClientRect();
  const pageSize = Math.max(height * 0.875, height - 40) * 1.5;
  const pageOffset = pageUp ? height - pageSize + top : pageSize + top;
  if (scrollingElement.tagName === "HTML") {
    return pageOffset + scrollingElement.scrollTop;
  }
  return pageOffset;
}
function getItemOffset(itemElement, pageUp = false) {
  const { top } = itemElement.getBoundingClientRect();
  if (pageUp) {
    return top + itemElement.clientHeight;
  }
  return top;
}
function findNextPageItemId(element, store, next, pageUp = false) {
  var _a;
  if (!store) return;
  if (!next) return;
  const { renderedItems } = store.getState();
  const scrollingElement = (0,G7XPWBXK/* getScrollingElement */.qj)(element);
  if (!scrollingElement) return;
  const nextPageOffset = getNextPageOffset(scrollingElement, pageUp);
  let id;
  let prevDifference;
  for (let i = 0; i < renderedItems.length; i += 1) {
    const previousId = id;
    id = next(i);
    if (!id) break;
    if (id === previousId) continue;
    const itemElement = (_a = (0,_7NJRHOSP/* getEnabledItem */.hZ)(store, id)) == null ? void 0 : _a.element;
    if (!itemElement) continue;
    const itemOffset = getItemOffset(itemElement, pageUp);
    const difference = itemOffset - nextPageOffset;
    const absDifference = Math.abs(difference);
    if (pageUp && difference <= 0 || !pageUp && difference >= 0) {
      if (prevDifference !== void 0 && prevDifference < absDifference) {
        id = previousId;
      }
      break;
    }
    prevDifference = absDifference;
  }
  return id;
}
function targetIsAnotherItem(event, store) {
  if ((0,events/* isSelfTarget */.uh)(event)) return false;
  return (0,_7NJRHOSP/* isItem */.WZ)(store, event.target);
}
var useCompositeItem = (0,TVXRYIJB/* createHook */.ab)(
  function useCompositeItem2({
    store,
    rowId: rowIdProp,
    preventScrollOnKeyDown = false,
    moveOnKeyPress = true,
    tabbable = false,
    getItem: getItemProp,
    "aria-setsize": ariaSetSizeProp,
    "aria-posinset": ariaPosInSetProp,
    ...props
  }) {
    const context = (0,_55FNNNML/* useCompositeContext */.k)();
    store = store || context;
    const id = (0,CEM7J6TT/* useId */.Bi)(props.id);
    const ref = (0,react.useRef)(null);
    const row = (0,react.useContext)(_55FNNNML/* CompositeRowContext */.$o);
    const disabled = (0,UWJK2WK2/* disabledFromProps */.$f)(props);
    const trulyDisabled = disabled && !props.accessibleWhenDisabled;
    const {
      rowId,
      baseElement,
      isActiveItem,
      ariaSetSize,
      ariaPosInSet,
      isTabbable
    } = (0,YAS7X7HB/* useStoreStateObject */.PX)(store, {
      rowId(state) {
        if (rowIdProp) return rowIdProp;
        if (!state) return;
        if (!(row == null ? void 0 : row.baseElement)) return;
        if (row.baseElement !== state.baseElement) return;
        return row.id;
      },
      baseElement(state) {
        return (state == null ? void 0 : state.baseElement) || void 0;
      },
      isActiveItem(state) {
        return !!state && state.activeId === id;
      },
      ariaSetSize(state) {
        if (ariaSetSizeProp != null) return ariaSetSizeProp;
        if (!state) return;
        if (!(row == null ? void 0 : row.ariaSetSize)) return;
        if (row.baseElement !== state.baseElement) return;
        return row.ariaSetSize;
      },
      ariaPosInSet(state) {
        if (ariaPosInSetProp != null) return ariaPosInSetProp;
        if (!state) return;
        if (!(row == null ? void 0 : row.ariaPosInSet)) return;
        if (row.baseElement !== state.baseElement) return;
        const itemsInRow = state.renderedItems.filter(
          (item) => item.rowId === rowId
        );
        return row.ariaPosInSet + itemsInRow.findIndex((item) => item.id === id);
      },
      isTabbable(state) {
        if (!(state == null ? void 0 : state.renderedItems.length)) return true;
        if (state.virtualFocus) return false;
        if (tabbable) return true;
        if (state.activeId === null) return false;
        const item = store == null ? void 0 : store.item(state.activeId);
        if (item == null ? void 0 : item.disabled) return true;
        if (!(item == null ? void 0 : item.element)) return true;
        return state.activeId === id;
      }
    });
    const getItem = (0,react.useCallback)(
      (item) => {
        var _a;
        const nextItem = {
          ...item,
          id: id || item.id,
          rowId,
          disabled: !!trulyDisabled,
          children: (_a = item.element) == null ? void 0 : _a.textContent
        };
        if (getItemProp) {
          return getItemProp(nextItem);
        }
        return nextItem;
      },
      [id, rowId, trulyDisabled, getItemProp]
    );
    const onFocusProp = props.onFocus;
    const hasFocusedComposite = (0,react.useRef)(false);
    const onFocus = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onFocusProp == null ? void 0 : onFocusProp(event);
      if (event.defaultPrevented) return;
      if ((0,events/* isPortalEvent */.ho)(event)) return;
      if (!id) return;
      if (!store) return;
      if (targetIsAnotherItem(event, store)) return;
      const { virtualFocus, baseElement: baseElement2 } = store.getState();
      store.setActiveId(id);
      if ((0,G7XPWBXK/* isTextbox */.Bj)(event.currentTarget)) {
        (0,_7NJRHOSP/* selectTextField */.iT)(event.currentTarget);
      }
      if (!virtualFocus) return;
      if (!(0,events/* isSelfTarget */.uh)(event)) return;
      if (isEditableElement(event.currentTarget)) return;
      if (!(baseElement2 == null ? void 0 : baseElement2.isConnected)) return;
      if ((0,GMGLSF2B/* isSafari */.nr)() && event.currentTarget.hasAttribute("data-autofocus")) {
        event.currentTarget.scrollIntoView({
          block: "nearest",
          inline: "nearest"
        });
      }
      hasFocusedComposite.current = true;
      const fromComposite = event.relatedTarget === baseElement2 || (0,_7NJRHOSP/* isItem */.WZ)(store, event.relatedTarget);
      if (fromComposite) {
        (0,_7NJRHOSP/* focusSilently */.hk)(baseElement2);
      } else {
        baseElement2.focus();
      }
    });
    const onBlurCaptureProp = props.onBlurCapture;
    const onBlurCapture = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onBlurCaptureProp == null ? void 0 : onBlurCaptureProp(event);
      if (event.defaultPrevented) return;
      const state = store == null ? void 0 : store.getState();
      if ((state == null ? void 0 : state.virtualFocus) && hasFocusedComposite.current) {
        hasFocusedComposite.current = false;
        event.preventDefault();
        event.stopPropagation();
      }
    });
    const onKeyDownProp = props.onKeyDown;
    const preventScrollOnKeyDownProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(preventScrollOnKeyDown);
    const moveOnKeyPressProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(moveOnKeyPress);
    const onKeyDown = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (event.defaultPrevented) return;
      if (!(0,events/* isSelfTarget */.uh)(event)) return;
      if (!store) return;
      const { currentTarget } = event;
      const state = store.getState();
      const item = store.item(id);
      const isGrid = !!(item == null ? void 0 : item.rowId);
      const isVertical = state.orientation !== "horizontal";
      const isHorizontal = state.orientation !== "vertical";
      const canHomeEnd = () => {
        if (isGrid) return true;
        if (isHorizontal) return true;
        if (!state.baseElement) return true;
        if (!(0,G7XPWBXK/* isTextField */.mB)(state.baseElement)) return true;
        return false;
      };
      const keyMap = {
        ArrowUp: (isGrid || isVertical) && store.up,
        ArrowRight: (isGrid || isHorizontal) && store.next,
        ArrowDown: (isGrid || isVertical) && store.down,
        ArrowLeft: (isGrid || isHorizontal) && store.previous,
        Home: () => {
          if (!canHomeEnd()) return;
          if (!isGrid || event.ctrlKey) {
            return store == null ? void 0 : store.first();
          }
          return store == null ? void 0 : store.previous(-1);
        },
        End: () => {
          if (!canHomeEnd()) return;
          if (!isGrid || event.ctrlKey) {
            return store == null ? void 0 : store.last();
          }
          return store == null ? void 0 : store.next(-1);
        },
        PageUp: () => {
          return findNextPageItemId(currentTarget, store, store == null ? void 0 : store.up, true);
        },
        PageDown: () => {
          return findNextPageItemId(currentTarget, store, store == null ? void 0 : store.down);
        }
      };
      const action = keyMap[event.key];
      if (action) {
        if ((0,G7XPWBXK/* isTextbox */.Bj)(currentTarget)) {
          const selection = (0,G7XPWBXK/* getTextboxSelection */.Zy)(currentTarget);
          const isLeft = isHorizontal && event.key === "ArrowLeft";
          const isRight = isHorizontal && event.key === "ArrowRight";
          const isUp = isVertical && event.key === "ArrowUp";
          const isDown = isVertical && event.key === "ArrowDown";
          if (isRight || isDown) {
            const { length: valueLength } = (0,G7XPWBXK/* getTextboxValue */.Mk)(currentTarget);
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
    const providerValue = (0,react.useMemo)(
      () => ({ id, baseElement }),
      [id, baseElement]
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(_55FNNNML/* CompositeItemContext */.U0.Provider, { value: providerValue, children: element }),
      [providerValue]
    );
    props = {
      "data-active-item": isActiveItem || void 0,
      ...props,
      id,
      ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref),
      tabIndex: isTabbable ? props.tabIndex : -1,
      onFocus,
      onBlurCapture,
      onKeyDown
    };
    props = useCommand(props);
    props = (0,IKWLDXMV/* useCollectionItem */.v)({
      store,
      ...props,
      getItem,
      shouldRegisterItem: id ? props.shouldRegisterItem : false
    });
    return (0,UWJK2WK2/* removeUndefinedValues */.HR)({
      ...props,
      "aria-setsize": ariaSetSize,
      "aria-posinset": ariaPosInSet
    });
  }
);
var CompositeItem = (0,TVXRYIJB/* memo */.ph)(
  (0,TVXRYIJB/* forwardRef */.Rf)(function CompositeItem2(props) {
    const htmlProps = useCompositeItem(props);
    return (0,TVXRYIJB/* createElement */.n)(ZCYMVQGT_TagName, htmlProps);
  })
);




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  YO: () => (/* binding */ useCompositeStoreProps)
});

// UNUSED EXPORTS: useCompositeStore, useCompositeStoreOptions

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js
var YAS7X7HB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js
var CEM7J6TT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6S37ITHJ.js
"use client";



// src/collection/collection-store.ts

function useCollectionStoreProps(store, update, props) {
  (0,CEM7J6TT/* useUpdateEffect */.w5)(update, [props.store]);
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "items", "setItems");
  return store;
}
function useCollectionStore(props = {}) {
  const [store, update] = useStore(Core.createCollectionStore, props);
  return useCollectionStoreProps(store, update, props);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js
"use client";




// src/composite/composite-store.ts

function useCompositeStoreOptions(props) {
  const id = useId(props.id);
  return { id, ...props };
}
function useCompositeStoreProps(store, update, props) {
  store = useCollectionStoreProps(store, update, props);
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "activeId", "setActiveId");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "includesBaseElement");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "virtualFocus");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "orientation");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "rtl");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "focusLoop");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "focusWrap");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "focusShift");
  return store;
}
function useCompositeStore(props = {}) {
  props = useCompositeStoreOptions(props);
  const [store, update] = useStore(Core.createCompositeStore, props);
  return useCompositeStoreProps(store, update, props);
}




/***/ })

}]);