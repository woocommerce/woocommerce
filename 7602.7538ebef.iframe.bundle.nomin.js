"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7602],{

/***/ "../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/disclosure/disclosure-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ createDisclosureStore)
/* harmony export */ });
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");


//#region src/disclosure/disclosure-store.ts
function getParentHideRequest(store) {
	const onHideRequest = store?.unstable_onHideRequest;
	const requestHide = store?.unstable_requestHide;
	if (!onHideRequest) return;
	if (!requestHide) return;
	return {
		onHideRequest,
		requestHide
	};
}
/**
* Creates a disclosure store.
*/
function createDisclosureStore(props = {}) {
	const store = props.store || props.disclosure ? (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .mergeStore */ .od)(props.store, (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .omit */ .cJ)(props.disclosure, ["contentElement", "disclosureElement"])) : void 0;
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .throwOnConflictingProps */ .UE)(props, store);
	const syncState = store?.getState();
	const open = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.open, syncState?.open, props.defaultOpen, false);
	const animated = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.animated, syncState?.animated, false);
	const initialState = {
		open,
		animated,
		animating: !!animated && open,
		mounted: open,
		contentElement: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState?.contentElement, null),
		disclosureElement: (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState?.disclosureElement, null)
	};
	const disclosure = store ? (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .createStore */ .y$)(initialState, store) : (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .createStore */ .y$)(initialState);
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .setup */ .mj)(disclosure, () => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .sync */ .OH)(disclosure, ["animated", "animating"], (state) => {
		if (state.animated) return;
		disclosure.setState("animating", false);
	}));
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .setup */ .mj)(disclosure, () => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .subscribe */ .B1)(disclosure, ["open"], () => {
		if (!disclosure.getState().animated) return;
		disclosure.setState("animating", true);
	}));
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .setup */ .mj)(disclosure, () => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .sync */ .OH)(disclosure, ["open", "animating"], (state) => {
		disclosure.setState("mounted", state.open || state.animating);
	}));
	const parentHideRequest = getParentHideRequest(props.store);
	const hideHandlers = /* @__PURE__ */ new Map();
	const onHideRequest = parentHideRequest?.onHideRequest ?? ((handler) => {
		if (handler === requestHide) return () => {};
		hideHandlers.set(handler, (hideHandlers.get(handler) ?? 0) + 1);
		return () => {
			const count = hideHandlers.get(handler);
			if (count == null) return;
			if (count > 1) {
				hideHandlers.set(handler, count - 1);
				return;
			}
			hideHandlers.delete(handler);
		};
	});
	const requestHide = parentHideRequest?.requestHide ?? ((hide) => {
		const handlers = [...hideHandlers.keys()];
		const runHandler = (index) => {
			const handler = handlers[index];
			if (!handler) {
				hide();
				return;
			}
			handler(() => runHandler(index + 1));
		};
		runHandler(0);
	});
	(0,_ariakit_store__WEBPACK_IMPORTED_MODULE_0__/* .setup */ .mj)(disclosure, () => {
		if (!props.disclosure) return;
		return onHideRequest(props.disclosure.unstable_requestHide);
	});
	const setOpen = (value) => {
		const { open } = disclosure.getState();
		const nextOpen = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .applyState */ .Qh)(value, open);
		if (!open || nextOpen) {
			disclosure.setState("open", nextOpen);
			return;
		}
		requestHide(() => disclosure.setState("open", false));
	};
	return {
		...disclosure,
		disclosure: props.disclosure,
		setOpen,
		show: () => disclosure.setState("open", true),
		hide: () => setOpen(false),
		toggle: () => setOpen((open) => !open),
		unstable_onHideRequest: onHideRequest,
		unstable_requestHide: requestHide,
		stopAnimation: () => disclosure.setState("animating", false),
		setContentElement: (value) => disclosure.setState("contentElement", value),
		setDisclosureElement: (value) => disclosure.setState("disclosureElement", value)
	};
}
//#endregion


//# sourceMappingURL=disclosure-store.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/C4_D9PgF.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ isCompositeMoveKey),
/* harmony export */   c: () => (/* binding */ trulyDisabledFromElement),
/* harmony export */   i: () => (/* binding */ hasTreeFocus),
/* harmony export */   n: () => (/* binding */ accessibleWhenDisabledSymbol),
/* harmony export */   o: () => (/* binding */ resolvedTrulyDisabledFromElement),
/* harmony export */   r: () => (/* binding */ getTreeActiveElement),
/* harmony export */   s: () => (/* binding */ trulyDisabledAttribute),
/* harmony export */   t: () => (/* binding */ accessibleWhenDisabledFromProps)
/* harmony export */ });
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
"use client";

//#region src/focusable/__utils.ts
const accessibleWhenDisabledSymbol = Symbol("accessibleWhenDisabled");
function accessibleWhenDisabledFromProps(props) {
	return props.accessibleWhenDisabled ?? props.onLoadedMetadataCapture?.[accessibleWhenDisabledSymbol];
}
const trulyDisabledAttribute = "data-truly-disabled";
/**
* Checks whether Focusable resolved this element as disabled and not keyboard
* accessible, based on the exact boolean value it stamps on the element.
*/
function trulyDisabledFromElement(element) {
	return element.getAttribute(trulyDisabledAttribute) === "true";
}
/**
* Returns the exact disabled result stamped by an active Focusable, or
* undefined when no active Focusable resolved the element.
*/
function resolvedTrulyDisabledFromElement(element) {
	const value = element.getAttribute(trulyDisabledAttribute);
	if (value === "true") return true;
	if (value === "false") return false;
}
function isCompositeMoveKey(key) {
	return key === "ArrowUp" || key === "ArrowRight" || key === "ArrowDown" || key === "ArrowLeft" || key === "Home" || key === "End" || key === "PageUp" || key === "PageDown";
}
function isShadowRoot(node) {
	return node.nodeType === node.DOCUMENT_FRAGMENT_NODE && "host" in node;
}
/**
* Returns the focused element as seen from the tree that contains `node`.
* Inside a shadow tree, the document reports the shadow host as its active
* element, so only the shadow root knows which element in it has focus. Focus
* outside that tree, such as in a portal, is still read from the document.
*/
function getTreeActiveElement(node) {
	const root = node?.getRootNode();
	if (root && isShadowRoot(root) && root.activeElement) return root.activeElement;
	return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .getActiveElement */ .bq)(node);
}
/**
* Like `hasFocus`, but also sees focus on `element` when it is inside a shadow
* tree.
*/
function hasTreeFocus(element) {
	return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .hasFocus */ .AJ)(element) || getTreeActiveElement(element) === element;
}
//#endregion


//# sourceMappingURL=C4_D9PgF.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/CDh2UhnG.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   n: () => (/* binding */ getKeyboardEventSource),
/* harmony export */   t: () => (/* binding */ fireProxiedKeyboardEvent)
/* harmony export */ });
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
"use client";

//#region src/composite/__keyboard-event-proxy.ts
const sourceEvents = /* @__PURE__ */ new WeakMap();
/**
* Dispatches a copy of a keyboard event on an element and records the event it
* copies as its source. Returns whether the copy's default action was not
* prevented.
*/
function fireProxiedKeyboardEvent(element, source, eventInit) {
	const { KeyboardEvent } = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .getWindow */ .zk)(element);
	const event = new KeyboardEvent(source.type, eventInit);
	sourceEvents.set(event, getKeyboardEventSource(source));
	return element.dispatchEvent(event);
}
/**
* Returns the event that the user triggered for a keyboard event that Composite
* dispatched again, or the event itself.
*/
function getKeyboardEventSource(event) {
	return sourceEvents.get(event) ?? event;
}
//#endregion


//# sourceMappingURL=CDh2UhnG.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/dialog-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   XP: () => (/* binding */ DialogScopedContextProvider),
/* harmony export */   YH: () => (/* binding */ DialogHeadingContext),
/* harmony export */   Zg: () => (/* binding */ DialogContextProvider),
/* harmony export */   cH: () => (/* binding */ useDialogProviderContext),
/* harmony export */   oL: () => (/* binding */ DialogDescriptionContext)
/* harmony export */ });
/* unused harmony exports useDialogContext, useDialogScopedContext */
/* harmony import */ var _disclosure_disclosure_context_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";



//#region src/dialog/dialog-context.tsx
const ctx = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_1__/* .createStoreContext */ .B0)([_disclosure_disclosure_context_js__WEBPACK_IMPORTED_MODULE_2__/* .DisclosureContextProvider */ .sI], [_disclosure_disclosure_context_js__WEBPACK_IMPORTED_MODULE_2__/* .DisclosureScopedContextProvider */ .WT]);
/**
* Returns the dialog store from the nearest dialog container.
* @example
* function Dialog() {
*   const store = useDialogContext();
*
*   if (!store) {
*     throw new Error("Dialog must be wrapped in DialogProvider");
*   }
*
*   // Use the store...
* }
*/
const useDialogContext = ctx.useContext;
const useDialogScopedContext = ctx.useScopedContext;
const useDialogProviderContext = ctx.useProviderContext;
const DialogContextProvider = ctx.ContextProvider;
const DialogScopedContextProvider = ctx.ScopedContextProvider;
const DialogHeadingContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(void 0);
const DialogDescriptionContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(void 0);
//#endregion


//# sourceMappingURL=dialog-context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-content.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   aT: () => (/* binding */ useDisclosureContent),
/* harmony export */   dK: () => (/* binding */ isHidden)
/* harmony export */ });
/* unused harmony export DisclosureContent */
/* harmony import */ var _disclosure_context_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-context.js");
/* harmony import */ var _dialog_dialog_context_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/dialog-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _ariakit_react_store__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
"use client";








//#region src/disclosure/disclosure-content.tsx
const TagName = "div";
function afterTimeout(timeoutMs, cb) {
	const timeoutId = setTimeout(cb, timeoutMs);
	return () => clearTimeout(timeoutId);
}
function parseCSSTime(time) {
	const value = time?.trim() || "0s";
	const multiplier = value.endsWith("ms") ? 1 : 1e3;
	const parsed = Number.parseFloat(value) * multiplier;
	return Number.isNaN(parsed) ? 0 : parsed;
}
function getEndTime(names, delays, durations) {
	const nameList = names.split(",");
	const delayList = delays.split(",");
	const durationList = durations.split(",");
	let endTime = 0;
	for (const [index, name] of nameList.entries()) {
		if (name.trim() === "none") continue;
		const delay = parseCSSTime(delayList[index % delayList.length]);
		const duration = parseCSSTime(durationList[index % durationList.length]);
		endTime = Math.max(endTime, delay + duration);
	}
	return endTime;
}
function getElementEndTime(element) {
	const { transitionProperty, transitionDuration, transitionDelay, animationName, animationDuration, animationDelay } = getComputedStyle(element);
	return Math.max(getEndTime(transitionProperty, transitionDelay, transitionDuration), getEndTime(animationName, animationDelay, animationDuration));
}
function isHidden(mounted, hidden, alwaysVisible) {
	return !alwaysVisible && hidden !== false && (!mounted || !!hidden);
}
/**
* Returns props to create a `DislosureContent` component.
* @see https://ariakit.com/components/disclosure
* @example
* ```jsx
* const store = useDisclosureStore();
* const props = useDisclosureContent({ store });
* <Disclosure store={store}>Disclosure</Disclosure>
* <Role {...props}>Content</Role>
* ```
*/
const useDisclosureContent = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .createHook */ .ab)(function useDisclosureContent({ store, alwaysVisible, unstable_otherElementRef: otherElementRef, ...props }) {
	const context = (0,_disclosure_context_js__WEBPACK_IMPORTED_MODULE_4__/* .useDisclosureProviderContext */ .vO)();
	store = store || context;
	(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_5__/* .invariant */ .V1)(store,  false && 0);
	const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const id = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useId */ .Bi)(props.id);
	const [transition, setTransition] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
	const { open, mounted, animated, contentElement } = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_6__/* .useStoreStateObject */ .PX)(store, {
		open: "open",
		mounted: "mounted",
		animated: "animated",
		contentElement: "contentElement"
	});
	const otherElement = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_6__/* .useStoreState */ .O$)(store.disclosure, "contentElement");
	const hasClosedRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
		if (!ref.current) return;
		store?.setContentElement(ref.current);
	}, [store]);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
		let previousAnimated;
		store?.setState("animated", (animated) => {
			previousAnimated = animated;
			return true;
		});
		return () => {
			if (previousAnimated === void 0) return;
			store?.setState("animated", previousAnimated);
		};
	}, [store]);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
		if (!animated) {
			if (!open) {
				hasClosedRef.current = true;
				setTransition(null);
			} else if (hasClosedRef.current) {
				hasClosedRef.current = false;
				setTransition("enter");
			}
			return;
		}
		if (!contentElement?.isConnected) {
			setTransition(null);
			return;
		}
		return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_5__/* .afterPaint */ .ny)(() => {
			setTransition(open ? "enter" : mounted ? "leave" : null);
		});
	}, [
		animated,
		contentElement,
		open,
		mounted
	]);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useSafeLayoutEffect */ .UQ)(() => {
		if (!store) return;
		if (!animated) return;
		if (!transition) return;
		if (!contentElement) return;
		const stopAnimation = () => store?.setState("animating", false);
		const stopAnimationSync = () => (0,react_dom__WEBPACK_IMPORTED_MODULE_2__.flushSync)(stopAnimation);
		if (transition === "leave" && open) return;
		if (transition === "enter" && !open) return;
		if (typeof animated === "number") return afterTimeout(animated, stopAnimationSync);
		const elements = [contentElement];
		if (otherElement) elements.push(otherElement);
		const relatedElement = otherElementRef?.current;
		if (relatedElement) elements.push(relatedElement);
		const timeout = Math.max(...elements.map(getElementEndTime));
		if (!timeout) {
			if (transition === "enter") store.setState("animated", false);
			stopAnimation();
			return;
		}
		return afterTimeout(Math.max(timeout - 1e3 / 60, 0), stopAnimationSync);
	}, [
		store,
		animated,
		contentElement,
		otherElement,
		otherElementRef,
		open,
		transition
	]);
	props = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useWrapElement */ .w7)(props, (element) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_dialog_dialog_context_js__WEBPACK_IMPORTED_MODULE_7__/* .DialogScopedContextProvider */ .XP, {
		value: store,
		children: element
	}), [store]);
	const hidden = isHidden(mounted, props.hidden, alwaysVisible);
	const styleProp = props.style;
	const style = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
		if (hidden) return {
			...styleProp,
			display: "none"
		};
		return styleProp;
	}, [hidden, styleProp]);
	props = {
		"data-open": open || void 0,
		"data-enter": transition === "enter" || void 0,
		"data-leave": transition === "leave" || void 0,
		hidden,
		...props,
		id,
		ref: (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .useMergeRefs */ .SV)(id ? store.setContentElement : null, ref, props.ref),
		style
	};
	return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_5__/* .removeUndefinedValues */ .HR)(props);
});
const DisclosureContentImpl = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .forwardRef */ .Rf)(function DisclosureContentImpl(props) {
	const htmlProps = useDisclosureContent(props);
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .createElement */ .n)(TagName, htmlProps);
});
/**
* Renders an element that can be shown or hidden by a
* [`Disclosure`](https://ariakit.com/components/disclosure) component.
* @see https://ariakit.com/components/disclosure
* @example
* ```jsx {3}
* <DisclosureProvider>
*   <Disclosure>Disclosure</Disclosure>
*   <DisclosureContent>Content</DisclosureContent>
* </DisclosureProvider>
* ```
*/
const DisclosureContent = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_3__/* .forwardRef */ .Rf)(function DisclosureContent({ unmountOnHide, ...props }) {
	const context = (0,_disclosure_context_js__WEBPACK_IMPORTED_MODULE_4__/* .useDisclosureProviderContext */ .vO)();
	const store = props.store || context;
	if ((0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_6__/* .useStoreState */ .O$)(store, ["mounted"], (state) => !unmountOnHide || state?.mounted) === false) return null;
	return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(DisclosureContentImpl, { ...props });
});
//#endregion


//# sourceMappingURL=disclosure-content.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WT: () => (/* binding */ DisclosureScopedContextProvider),
/* harmony export */   sI: () => (/* binding */ DisclosureContextProvider),
/* harmony export */   vO: () => (/* binding */ useDisclosureProviderContext)
/* harmony export */ });
/* unused harmony exports useDisclosureContext, useDisclosureScopedContext */
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
"use client";

//#region src/disclosure/disclosure-context.tsx
const ctx = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__/* .createStoreContext */ .B0)();
/**
* Returns the disclosure store from the nearest disclosure container.
* @example
* function Disclosure() {
*   const store = useDisclosureContext();
*
*   if (!store) {
*     throw new Error("Disclosure must be wrapped in DisclosureProvider");
*   }
*
*   // Use the store...
* }
*/
const useDisclosureContext = ctx.useContext;
const useDisclosureScopedContext = ctx.useScopedContext;
const useDisclosureProviderContext = ctx.useProviderContext;
const DisclosureContextProvider = ctx.ContextProvider;
const DisclosureScopedContextProvider = ctx.ScopedContextProvider;
//#endregion


//# sourceMappingURL=disclosure-context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-store.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ useDisclosureStore),
/* harmony export */   W: () => (/* binding */ useDisclosureStoreProps)
/* harmony export */ });
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_react_store__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
/* harmony import */ var _ariakit_components_disclosure_disclosure_store__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/disclosure/disclosure-store.js");
"use client";



//#region src/disclosure/disclosure-store.ts
function useDisclosureStoreProps(store, update, props) {
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__/* .useUpdateEffect */ .w5)(update, [
		props.store,
		props.disclosure,
		update
	]);
	(0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_1__/* .useStoreProps */ .Tz)(store, props, "open", "setOpen");
	(0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_1__/* .useStoreProps */ .Tz)(store, props, "mounted", "setMounted");
	(0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_1__/* .useStoreProps */ .Tz)(store, props, "animated");
	return Object.assign(store, { disclosure: props.disclosure });
}
/**
* Creates a disclosure store to control the state of
* [Disclosure](https://ariakit.com/components/disclosure) components.
* @see https://ariakit.com/components/disclosure
* @example
* ```jsx
* const disclosure = useDisclosureStore();
*
* <Disclosure store={disclosure}>Disclosure</Disclosure>
* <DisclosureContent store={disclosure}>Content</DisclosureContent>
* ```
*/
function useDisclosureStore(props = {}) {
	const [store, update] = (0,_ariakit_react_store__WEBPACK_IMPORTED_MODULE_1__/* .useStore */ .Pj)(_ariakit_components_disclosure_disclosure_store__WEBPACK_IMPORTED_MODULE_2__/* .createDisclosureStore */ .h, props);
	return useDisclosureStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=disclosure-store.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   g: () => (/* binding */ FocusableContext)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";

//#region src/focusable/focusable-context.tsx
const FocusableContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(true);
//#endregion


//# sourceMappingURL=focusable-context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   W: () => (/* binding */ useFocusable)
/* harmony export */ });
/* unused harmony export Focusable */
/* harmony import */ var _chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/C4_D9PgF.js");
/* harmony import */ var _focusable_context_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";





//#region src/focusable/focusable.tsx
const TagName = "div";
const isSafariBrowser = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isSafari */ .nr)();
const nativeTabbableMask = 1;
const supportsDisabledMask = 2;
const defaultElementCapabilities = 3;
const alwaysFocusVisibleInputTypes = [
	"text",
	"search",
	"url",
	"tel",
	"email",
	"password",
	"number",
	"date",
	"month",
	"week",
	"time",
	"datetime",
	"datetime-local"
];
function isAlwaysFocusVisible(element) {
	const { tagName, readOnly, type } = element;
	if (tagName === "TEXTAREA" && !readOnly) return true;
	if (tagName === "SELECT" && !readOnly) return true;
	if (tagName === "INPUT" && !readOnly) return alwaysFocusVisibleInputTypes.includes(type);
	if (element.isContentEditable) return true;
	if (element.getAttribute("role") === "combobox" && element.dataset.name) return true;
	return false;
}
function isNativeTabbable(tagName) {
	if (!tagName) return true;
	return tagName === "button" || tagName === "summary" || tagName === "input" || tagName === "select" || tagName === "textarea" || tagName === "a";
}
function supportsDisabledAttribute(tagName) {
	if (!tagName) return true;
	return tagName === "button" || tagName === "input" || tagName === "select" || tagName === "textarea";
}
function getElementCapabilities(tagName) {
	let capabilities = 0;
	if (isNativeTabbable(tagName)) capabilities |= nativeTabbableMask;
	if (supportsDisabledAttribute(tagName)) capabilities |= supportsDisabledMask;
	return capabilities;
}
function isNativeSubmitControl(element) {
	if (element.tagName === "BUTTON") {
		const { type } = element;
		return type === "submit";
	}
	if (element.tagName === "INPUT") {
		const { type } = element;
		return type === "submit" || type === "image";
	}
	return false;
}
function getTabIndex({ focusable, trulyDisabled, nativeTabbable, supportsDisabled, safariTabIndex, tabIndexProp }) {
	if (!focusable) return tabIndexProp;
	if (trulyDisabled) {
		if (nativeTabbable && !supportsDisabled) return -1;
		return;
	}
	if (nativeTabbable) {
		if (safariTabIndex && tabIndexProp == null) return 0;
		return tabIndexProp;
	}
	return tabIndexProp ?? 0;
}
function useDisableEvent(onEvent, disabled) {
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
		onEvent?.(event);
		if (event.defaultPrevented) return;
		if (disabled) {
			event.stopPropagation();
			event.preventDefault();
		}
	});
}
let hasInstalledGlobalEventListeners = false;
let isKeyboardModality = true;
function onGlobalMouseDown(event) {
	const target = event.target;
	if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isElement */ .vq)(target) && !target.hasAttribute("data-focus-visible")) isKeyboardModality = false;
}
function onGlobalKeyDown(event) {
	if ((0,_chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.a)(event.key)) {
		isKeyboardModality = true;
		return;
	}
	if (event.metaKey) return;
	if (event.ctrlKey) return;
	if (event.altKey) {
		if (!isSafariBrowser) return;
		if (event.key !== "Tab") return;
	}
	isKeyboardModality = true;
}
/**
* Returns props to create a `Focusable` component.
* @see https://ariakit.com/components/focusable
* @example
* ```jsx
* const props = useFocusable();
* <Role {...props}>Focusable</Role>
* ```
*/
const useFocusable = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .createHook */ .ab)(function useFocusable({ focusable = true, accessibleWhenDisabled, autoFocus, onFocusVisible, ...props }) {
	const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const [parentAccessibleWhenDisabled, metadataProps] = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useMetadataProps */ .P1)(props, _chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.n, accessibleWhenDisabled);
	accessibleWhenDisabled ??= parentAccessibleWhenDisabled;
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		if (!focusable) return;
		if (hasInstalledGlobalEventListeners) return;
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .addGlobalEventListener */ .dg)("mousedown", onGlobalMouseDown, true);
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .addGlobalEventListener */ .dg)("keydown", onGlobalKeyDown, true);
		hasInstalledGlobalEventListeners = true;
	}, [focusable]);
	const disabled = focusable && (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .disabledFromProps */ .$f)(props);
	const trulyDisabled = disabled && !accessibleWhenDisabled;
	const [focusVisible, setFocusVisible] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
	const focusVisibleRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
	const nativeSubmitObserverCleanupRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
	const cleanupFocusVisible = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((element) => {
		nativeSubmitObserverCleanupRef.current?.();
		nativeSubmitObserverCleanupRef.current = null;
		focusVisibleRef.current = false;
		element?.removeAttribute("data-focus-visible");
	});
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		if (focusable && !trulyDisabled) return;
		cleanupFocusVisible(ref.current);
		if (focusVisible) setFocusVisible(false);
	}, [
		focusable,
		trulyDisabled,
		focusVisible,
		cleanupFocusVisible
	]);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		if (!focusable) return;
		if (!focusVisible) return;
		const element = ref.current;
		if (!element) return;
		if (typeof IntersectionObserver === "undefined") return;
		const observer = new IntersectionObserver(() => {
			if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isFocusable */ .tp)(element)) {
				focusVisibleRef.current = false;
				setFocusVisible(false);
			}
		});
		observer.observe(element);
		return () => observer.disconnect();
	}, [focusable, focusVisible]);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		return () => nativeSubmitObserverCleanupRef.current?.();
	}, []);
	const onKeyPressCapture = useDisableEvent(props.onKeyPressCapture, disabled);
	const onMouseDownCapture = useDisableEvent(props.onMouseDownCapture, disabled);
	const onClickCapture = useDisableEvent(props.onClickCapture, disabled);
	const onAuxClickCapture = useDisableEvent(props.onAuxClickCapture, disabled);
	const handleFocusVisible = (event, currentTarget) => {
		if (currentTarget) event.currentTarget = currentTarget;
		if (!focusable) return;
		const element = event.currentTarget;
		if (!element) return;
		if (!(0,_chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.i)(element)) return;
		onFocusVisible?.(event);
		if (event.defaultPrevented) return;
		element.dataset.focusVisible = "true";
		focusVisibleRef.current = true;
		if (isNativeSubmitControl(element)) {
			nativeSubmitObserverCleanupRef.current?.();
			nativeSubmitObserverCleanupRef.current = null;
			if (typeof IntersectionObserver !== "undefined") {
				const observer = new IntersectionObserver(() => {
					if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isFocusable */ .tp)(element)) return;
					cleanupFocusVisible(element);
				});
				observer.observe(element);
				nativeSubmitObserverCleanupRef.current = () => observer.disconnect();
			}
			return;
		}
		setFocusVisible(true);
	};
	const onKeyDownCaptureProp = props.onKeyDownCapture;
	const onKeyDownCapture = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
		onKeyDownCaptureProp?.(event);
		if (event.defaultPrevented) return;
		if (!focusable) return;
		if (focusVisible) return;
		if (focusVisibleRef.current) return;
		if ((event.metaKey || event.altKey || event.ctrlKey) && !(0,_chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.a)(event.key)) return;
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isSelfTarget */ .uh)(event)) return;
		const element = event.currentTarget;
		const applyFocusVisible = () => handleFocusVisible(event, element);
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .queueBeforeEvent */ .nz)(element, "focusout", applyFocusVisible);
	});
	const onFocusCaptureProp = props.onFocusCapture;
	const onFocusCapture = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
		onFocusCaptureProp?.(event);
		if (event.defaultPrevented) return;
		if (!focusable) return;
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isSelfTarget */ .uh)(event)) {
			setFocusVisible(false);
			return;
		}
		const element = event.currentTarget;
		const applyFocusVisible = () => handleFocusVisible(event, element);
		if (isKeyboardModality || isAlwaysFocusVisible(event.target)) (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .queueBeforeEvent */ .nz)(event.target, "focusout", applyFocusVisible);
		else setFocusVisible(false);
	});
	const onBlurProp = props.onBlur;
	const onBlur = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
		onBlurProp?.(event);
		if (!focusable) return;
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isFocusEventOutside */ .aG)(event)) return;
		cleanupFocusVisible(event.currentTarget);
		setFocusVisible(false);
	});
	const autoFocusOnShow = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_focusable_context_js__WEBPACK_IMPORTED_MODULE_4__/* .FocusableContext */ .g);
	const autoFocusRef = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((element) => {
		if (!focusable) return;
		if (!autoFocus) return;
		if (!element) return;
		if (!autoFocusOnShow) return;
		queueMicrotask(() => {
			if ((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .hasFocus */ .AJ)(element)) return;
			if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isFocusable */ .tp)(element)) return;
			element.focus();
		});
	});
	const [elementCapabilities, setElementCapabilities] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(defaultElementCapabilities);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useSafeLayoutEffect */ .UQ)(() => {
		const element = ref.current;
		if (!element) return;
		const nextCapabilities = getElementCapabilities(element.tagName.toLowerCase());
		if (nextCapabilities === defaultElementCapabilities) return;
		setElementCapabilities(nextCapabilities);
	}, []);
	const nativeTabbable = focusable && !!(elementCapabilities & nativeTabbableMask);
	const supportsDisabled = focusable && !!(elementCapabilities & supportsDisabledMask);
	const [safariTabIndex, setSafariTabIndex] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
	if (isSafariBrowser) (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		if (!focusable) return;
		const element = ref.current;
		if (!element) return;
		const { type } = element;
		const isNativeCheckboxOrRadio = element.tagName === "INPUT" && (type === "checkbox" || type === "radio");
		setSafariTabIndex((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .isButton */ .Bm)(element) || isNativeCheckboxOrRadio);
	}, [focusable]);
	const styleProp = props.style;
	const style = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
		if (trulyDisabled) return {
			pointerEvents: "none",
			...styleProp
		};
		return styleProp;
	}, [trulyDisabled, styleProp]);
	props = {
		"data-focus-visible": focusable && focusVisible || void 0,
		"data-autofocus": autoFocus || void 0,
		"aria-disabled": disabled || void 0,
		...props,
		...metadataProps,
		ref: (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .useMergeRefs */ .SV)(ref, autoFocusRef, props.ref),
		style,
		tabIndex: getTabIndex({
			focusable,
			trulyDisabled,
			nativeTabbable,
			supportsDisabled,
			safariTabIndex,
			tabIndexProp: props.tabIndex
		}),
		disabled: supportsDisabled && trulyDisabled ? true : void 0,
		[_chunks_C4_D9PgF_js__WEBPACK_IMPORTED_MODULE_3__.s]: disabled ? trulyDisabled : void 0,
		contentEditable: disabled ? void 0 : props.contentEditable,
		onKeyPressCapture,
		onClickCapture,
		onAuxClickCapture,
		onMouseDownCapture,
		onKeyDownCapture,
		onFocusCapture,
		onBlur
	};
	return (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_1__/* .removeUndefinedValues */ .HR)(props);
});
/**
* Renders a focusable element. When this element gains keyboard focus, it gets
* a
* [`data-focus-visible`](https://ariakit.com/guide/styling#data-focus-visible)
* attribute and triggers the
* [`onFocusVisible`](https://ariakit.com/reference/focusable#onfocusvisible)
* prop.
*
* The `Focusable` component supports the
* [`disabled`](https://ariakit.com/reference/focusable#disabled) prop for all
* elements, even those not supporting the native `disabled` attribute. Disabled
* elements using the `Focusable` component may be still accessible via keyboard
* by using the the
* [`accessibleWhenDisabled`](https://ariakit.com/reference/focusable#accessiblewhendisabled)
* prop.
* @see https://ariakit.com/components/focusable
* @example
* ```jsx
* <Focusable>Focusable</Focusable>
* ```
*/
const Focusable = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .forwardRef */ .Rf)(function Focusable(props) {
	const htmlProps = useFocusable(props);
	return (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_2__/* .createElement */ .n)(TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=focusable.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/popover/popover-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   s1: () => (/* binding */ PopoverScopedContextProvider),
/* harmony export */   wf: () => (/* binding */ PopoverContextProvider),
/* harmony export */   zG: () => (/* binding */ usePopoverProviderContext)
/* harmony export */ });
/* unused harmony exports usePopoverContext, usePopoverScopedContext */
/* harmony import */ var _dialog_dialog_context_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/dialog-context.js");
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
"use client";


//#region src/popover/popover-context.tsx
const ctx = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_0__/* .createStoreContext */ .B0)([_dialog_dialog_context_js__WEBPACK_IMPORTED_MODULE_1__/* .DialogContextProvider */ .Zg], [_dialog_dialog_context_js__WEBPACK_IMPORTED_MODULE_1__/* .DialogScopedContextProvider */ .XP]);
/**
* Returns the popover store from the nearest popover container.
* @example
* function Popover() {
*   const store = usePopoverContext();
*
*   if (!store) {
*     throw new Error("Popover must be wrapped in PopoverProvider");
*   }
*
*   // Use the store...
* }
*/
const usePopoverContext = ctx.useContext;
const usePopoverScopedContext = ctx.useScopedContext;
const usePopoverProviderContext = ctx.useProviderContext;
const PopoverContextProvider = ctx.ContextProvider;
const PopoverScopedContextProvider = ctx.ScopedContextProvider;
//#endregion


//# sourceMappingURL=popover-context.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   O$: () => (/* binding */ useStoreState),
/* harmony export */   PX: () => (/* binding */ useStoreStateObject),
/* harmony export */   Pj: () => (/* binding */ useStore),
/* harmony export */   Tz: () => (/* binding */ useStoreProps)
/* harmony export */ });
/* harmony import */ var _ariakit_react_utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
/* harmony import */ var _ariakit_store__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var use_sync_external_store_shim__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/shim/index.js");
"use client";





//#region src/index.tsx
const noopSubscribe = () => () => {};
function isStoreKeyArray(value) {
	return Array.isArray(value);
}
function isSameValue(value, other) {
	return value === other || value !== value && other !== other;
}
function hasSameStoreKeys(keys, otherKeys) {
	if (keys?.length !== otherKeys.length) return false;
	for (let index = 0; index < keys.length; index += 1) {
		const key = keys[index];
		if (isSameValue(key, otherKeys[index])) continue;
		return false;
	}
	return true;
}
function useStableStoreKeys(keys) {
	const [stableKeys, setStableKeys] = react__WEBPACK_IMPORTED_MODULE_0__.useState(() => keys === null ? null : [...keys]);
	if (keys === null) {
		if (stableKeys !== null) setStableKeys(null);
		return null;
	}
	if (hasSameStoreKeys(stableKeys, keys)) return stableKeys;
	const nextKeys = [...keys];
	setStableKeys(nextKeys);
	return nextKeys;
}
function useStoreState(store, keyOrKeysOrSelector = _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .identity */ .D_, selector) {
	const subscriptionKeys = useStableStoreKeys(isStoreKeyArray(keyOrKeysOrSelector) ? keyOrKeysOrSelector : typeof keyOrKeysOrSelector === "function" ? null : [keyOrKeysOrSelector]);
	const storeSubscribe = react__WEBPACK_IMPORTED_MODULE_0__.useCallback((callback) => {
		if (!store) return noopSubscribe();
		if (subscriptionKeys?.length === 0) return noopSubscribe();
		return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_3__/* .subscribe */ .B1)(store, subscriptionKeys, callback);
	}, [store, subscriptionKeys]);
	const getSnapshot = () => {
		const state = store?.getState();
		if (isStoreKeyArray(keyOrKeysOrSelector)) return selector?.(state);
		if (typeof keyOrKeysOrSelector === "function") return keyOrKeysOrSelector(state);
		if (!state) return;
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .hasOwnProperty */ .mQ)(state, keyOrKeysOrSelector)) return;
		return state[keyOrKeysOrSelector];
	};
	return (0,use_sync_external_store_shim__WEBPACK_IMPORTED_MODULE_1__.useSyncExternalStore)(storeSubscribe, getSnapshot, getSnapshot);
}
function getStoreStateObjectKeys(object, selectorKeys) {
	const keys = [];
	let hasSelector = false;
	for (const prop in object) {
		const keyOrSelector = object[prop];
		if (keyOrSelector === void 0) continue;
		if (typeof keyOrSelector === "function") {
			hasSelector = true;
			continue;
		}
		if (keys.includes(keyOrSelector)) continue;
		keys.push(keyOrSelector);
	}
	if (!hasSelector) return keys;
	if (!selectorKeys) return null;
	for (const key of selectorKeys) {
		if (keys.includes(key)) continue;
		keys.push(key);
	}
	return keys;
}
function useStoreStateObject(store, objectOrKeys, object) {
	const stateObject = isStoreKeyArray(objectOrKeys) ? object ?? {} : objectOrKeys;
	const selectorKeys = isStoreKeyArray(objectOrKeys) ? objectOrKeys : void 0;
	const objRef = react__WEBPACK_IMPORTED_MODULE_0__.useRef({});
	const subscriptionKeys = useStableStoreKeys(getStoreStateObjectKeys(stateObject, selectorKeys));
	const storeSubscribe = react__WEBPACK_IMPORTED_MODULE_0__.useCallback((callback) => {
		if (!store) return noopSubscribe();
		if (subscriptionKeys?.length === 0) return noopSubscribe();
		return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_3__/* .subscribe */ .B1)(store, subscriptionKeys, callback);
	}, [store, subscriptionKeys]);
	const getSnapshot = () => {
		const state = store?.getState();
		let updated = false;
		const obj = objRef.current;
		for (const prop in stateObject) {
			const keyOrSelector = stateObject[prop];
			if (keyOrSelector === void 0) continue;
			if (typeof keyOrSelector === "function") {
				const value = keyOrSelector(state);
				if (!Object.is(value, obj[prop])) {
					obj[prop] = value;
					updated = true;
				}
			}
			if (typeof keyOrSelector === "function") continue;
			const value = state && (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .hasOwnProperty */ .mQ)(state, keyOrSelector) ? state[keyOrSelector] : void 0;
			if (!Object.is(value, obj[prop])) {
				obj[prop] = value;
				updated = true;
			}
		}
		if (updated) objRef.current = { ...obj };
		return objRef.current;
	};
	return (0,use_sync_external_store_shim__WEBPACK_IMPORTED_MODULE_1__.useSyncExternalStore)(storeSubscribe, getSnapshot, getSnapshot);
}
/**
* Synchronizes the store with the props, including parent store props.
* @param store The store to synchronize.
* @param props The props to synchronize with.
* @param key The key of the value prop.
* @param setKey The key of the setValue prop.
*/
function useStoreProps(store, props, key, setKey) {
	const value = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .hasOwnProperty */ .mQ)(props, key) ? props[key] : void 0;
	const setValue = setKey ? props[setKey] : void 0;
	const hasSetValue = !!setValue;
	const propsRef = (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_4__/* .useLiveRef */ .hX)({
		value,
		setValue
	});
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_4__/* .useSafeLayoutEffect */ .UQ)(() => {
		if (!hasSetValue) return;
		return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_3__/* .subscribe */ .B1)(store, [key], (state, prev) => {
			const { value, setValue } = propsRef.current;
			if (!setValue) return;
			if (isSameValue(state[key], prev[key])) return;
			if (isSameValue(state[key], value)) return;
			setValue(state[key]);
		});
	}, [
		store,
		key,
		hasSetValue,
		propsRef
	]);
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_4__/* .useSafeLayoutEffect */ .UQ)(() => {
		if (value === void 0) return;
		store.setState(key, value);
		return (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_3__/* .batch */ .vA)(store, [key], () => {
			if (value === void 0) return;
			store.setState(key, value);
		});
	});
}
/**
* Creates a React store from a core store object and returns a tuple with the
* store and a function to update the store.
* @param createStore A function that receives the props and returns a core
*   store object.
* @param props The props to pass to the createStore function.
*/
function useStore(createStore, props) {
	const [store, setStore] = react__WEBPACK_IMPORTED_MODULE_0__.useState(() => createStore(props));
	(0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_4__/* .useSafeLayoutEffect */ .UQ)(() => (0,_ariakit_store__WEBPACK_IMPORTED_MODULE_3__/* .init */ .Ts)(store), [store]);
	const useState = react__WEBPACK_IMPORTED_MODULE_0__.useCallback((keyOrSelector) => useStoreState(store, keyOrSelector), [store]);
	return [react__WEBPACK_IMPORTED_MODULE_0__.useMemo(() => ({
		...store,
		useState
	}), [store, useState]), (0,_ariakit_react_utils__WEBPACK_IMPORTED_MODULE_4__/* .useEvent */ ._q)(() => {
		setStore((store) => createStore({
			...props,
			...store.getState()
		}));
	})];
}
//#endregion


//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

var react__WEBPACK_IMPORTED_MODULE_0___namespace_cache;
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   B0: () => (/* binding */ createStoreContext),
/* harmony export */   Bi: () => (/* binding */ useId),
/* harmony export */   CH: () => (/* binding */ useForceUpdate),
/* harmony export */   Cy: () => (/* binding */ useAttribute),
/* harmony export */   O4: () => (/* binding */ useBooleanEvent),
/* harmony export */   P$: () => (/* binding */ useIsMouseMoving),
/* harmony export */   P1: () => (/* binding */ useMetadataProps),
/* harmony export */   Rf: () => (/* binding */ forwardRef),
/* harmony export */   SV: () => (/* binding */ useMergeRefs),
/* harmony export */   TE: () => (/* binding */ usePortalRef),
/* harmony export */   UQ: () => (/* binding */ useSafeLayoutEffect),
/* harmony export */   XB: () => (/* binding */ useTransactionState),
/* harmony export */   _q: () => (/* binding */ useEvent),
/* harmony export */   ab: () => (/* binding */ createHook),
/* harmony export */   cZ: () => (/* binding */ setRef),
/* harmony export */   hX: () => (/* binding */ useLiveRef),
/* harmony export */   n: () => (/* binding */ createElement),
/* harmony export */   ph: () => (/* binding */ memo),
/* harmony export */   vO: () => (/* binding */ useTagName),
/* harmony export */   w5: () => (/* binding */ useUpdateEffect),
/* harmony export */   w7: () => (/* binding */ useWrapElement)
/* harmony export */ });
/* unused harmony exports getRefProperty, isValidElementWithRef, mergeProps, useDeferredValue, useInitialValue, useUpdateLayoutEffect */
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
"use client";




//#region src/misc.ts
/**
* Helpers for working with refs, elements, and props.
* @module General utilities
*/
/**
* Sets both a function and object React ref.
*
* Returns a callback ref cleanup function when one is provided.
*/
function setRef(ref, value) {
	if (typeof ref === "function") {
		const cleanup = ref(value);
		if (typeof cleanup === "function") return cleanup;
	} else if (ref) ref.current = value;
}
/**
* Checks if an element is a valid React element with a ref.
*/
function isValidElementWithRef(element) {
	if (!element) return false;
	if (!(0,react__WEBPACK_IMPORTED_MODULE_0__.isValidElement)(element)) return false;
	if ("ref" in element.props) return true;
	if ("ref" in element) return true;
	return false;
}
/**
* Gets the ref property from a React element.
*/
function getRefProperty(element) {
	if (!isValidElementWithRef(element)) return null;
	return { ...element.props }.ref || element.ref;
}
/**
* Merges two sets of props.
*/
function mergeProps(base, overrides) {
	const props = { ...base };
	for (const key in overrides) {
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .hasOwnProperty */ .mQ)(overrides, key)) continue;
		if (key === "__proto__") continue;
		if (key === "className") {
			const prop = "className";
			const baseClass = base[prop];
			const overrideClass = overrides[prop];
			if (baseClass && overrideClass) props[prop] = `${baseClass} ${overrideClass}`;
			else props[prop] = overrideClass || baseClass;
			continue;
		}
		if (key === "style") {
			const prop = "style";
			props[prop] = base[prop] ? {
				...base[prop],
				...overrides[prop]
			} : overrides[prop];
			continue;
		}
		const overrideValue = overrides[key];
		if (overrideValue === void 0) continue;
		if (key.startsWith("on")) {
			if (typeof overrideValue !== "function") continue;
			const baseValue = base[key];
			if (typeof baseValue === "function") {
				props[key] = (...args) => {
					overrideValue(...args);
					baseValue(...args);
				};
				continue;
			}
		}
		props[key] = overrideValue;
	}
	return props;
}
//#endregion
//#region src/hooks.ts
/**
* React hooks for refs, events, ids, effects, and element metadata.
* @module Hooks
*/
const _React = { .../*#__PURE__*/ (react__WEBPACK_IMPORTED_MODULE_0___namespace_cache || (react__WEBPACK_IMPORTED_MODULE_0___namespace_cache = __webpack_require__.t(react__WEBPACK_IMPORTED_MODULE_0__, 2))) };
const useReactId = _React.useId;
const useReactDeferredValue = _React.useDeferredValue;
const useEventUpdate = _React.useInsertionEffect ?? ((callback) => callback());
/**
* `React.useLayoutEffect` that fallbacks to `React.useEffect` on server side.
*/
const useSafeLayoutEffect = _ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .canUseDOM */ .Sw ? react__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect : react__WEBPACK_IMPORTED_MODULE_0__.useEffect;
/**
* Returns a value that never changes even if the argument is updated.
* @example
* function Component({ prop }) {
*   const initialProp = useInitialValue(prop);
* }
*/
function useInitialValue(value) {
	const [initialValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(value);
	return initialValue;
}
/**
* Creates a `React.RefObject` that is constantly updated with the incoming
* value.
* @example
* function Component({ prop }) {
*   const propRef = useLiveRef(prop);
* }
*/
function useLiveRef(value) {
	const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(value);
	useSafeLayoutEffect(() => {
		ref.current = value;
	});
	return ref;
}
/**
* Creates a stable callback function that has access to the latest state and
* can be used within event handlers and effect callbacks. Throws when used in
* the render phase.
* @example
* function Component(props) {
*   const onClick = useEvent(props.onClick);
*   React.useEffect(() => {}, [onClick]);
* }
*/
function useEvent(callback) {
	const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(() => {
		throw new Error("Cannot call an event handler while rendering.");
	});
	useEventUpdate(() => {
		ref.current = callback;
	});
	return (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)((...args) => ref.current?.(...args), []);
}
/**
* Creates a React state that calls a callback function whenever the state
* changes and rolls back to the previous state on cleanup.
*/
function useTransactionState(callback) {
	const [state, setState] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
	useSafeLayoutEffect(() => {
		if (state == null) return;
		if (!callback) return;
		let prevState = null;
		callback((prev) => {
			prevState = prev;
			return state;
		});
		return () => {
			callback(prevState);
		};
	}, [state, callback]);
	return [state, setState];
}
/**
* Merges React Refs into a single memoized function ref so you can pass it to
* an element.
* @example
* const Component = React.forwardRef((props, ref) => {
*   const internalRef = React.useRef();
*   return <div {...props} ref={useMergeRefs(internalRef, ref)} />;
* });
*/
function useMergeRefs(...refs) {
	return (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
		if (!refs.some(Boolean)) return;
		return (value) => {
			const refEffects = [];
			for (const ref of refs) {
				if (!ref) continue;
				const cleanup = setRef(ref, value);
				refEffects.push({
					ref,
					cleanup: typeof cleanup === "function" ? cleanup : void 0
				});
			}
			if (!refEffects.some((effect) => effect.cleanup)) return;
			return () => {
				for (const { ref, cleanup } of refEffects) if (cleanup) cleanup();
				else setRef(ref, null);
			};
		};
	}, refs);
}
function useIdPolyfill(defaultId) {
	const [id, setId] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(defaultId);
	useSafeLayoutEffect(() => {
		if (defaultId || id) return;
		const random = Math.random().toString(36).slice(2, 8);
		setId(`id-${random}`);
	}, [defaultId, id]);
	return defaultId || id;
}
function useReactIdWithDefault(defaultId) {
	const id = useReactId();
	return defaultId || id;
}
const useCompatibleId = useReactId ? useReactIdWithDefault : useIdPolyfill;
/**
* Generates a unique ID. Uses React's useId if available.
*/
function useId(defaultId) {
	return useCompatibleId(defaultId);
}
function useDeferredValuePolyfill(value) {
	const [deferredValue, setDeferredValue] = useState(value);
	useEffect(() => {
		const raf = requestAnimationFrame(() => setDeferredValue(value));
		return () => cancelAnimationFrame(raf);
	}, [value]);
	return deferredValue;
}
const useCompatibleDeferredValue = (/* unused pure expression or super */ null && (useReactDeferredValue ?? useDeferredValuePolyfill));
/**
* Uses React's useDeferredValue if available.
*/
function useDeferredValue(value) {
	return useCompatibleDeferredValue(value);
}
/**
* Returns the tag name by parsing an element ref.
* @example
* function Component(props) {
*   const ref = React.useRef();
*   const tagName = useTagName(ref, "button"); // div
*   return <div ref={ref} {...props} />;
* }
*/
function useTagName(refOrElement, type) {
	const stringOrUndefined = (type) => {
		if (typeof type !== "string") return;
		return type;
	};
	const [tagName, setTagName] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(() => stringOrUndefined(type));
	useSafeLayoutEffect(() => {
		const element = refOrElement && "current" in refOrElement ? refOrElement.current : refOrElement;
		setTagName(element?.tagName.toLowerCase() || stringOrUndefined(type));
	}, [refOrElement, type]);
	return tagName;
}
/**
* Returns the attribute value of an element.
* @example
* function Component(props) {
*   const ref = React.useRef();
*   const role = useAttribute(ref, "role", props.role);
*   return <div ref={ref} {...props} />;
* }
*/
function useAttribute(refOrElement, attributeName, defaultValue) {
	const initialValue = useInitialValue(defaultValue);
	const [attribute, setAttribute] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(initialValue);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		const element = refOrElement && "current" in refOrElement ? refOrElement.current : refOrElement;
		if (!element) return;
		const callback = () => {
			const value = element.getAttribute(attributeName);
			setAttribute(value == null ? initialValue : value);
		};
		const observer = new MutationObserver(callback);
		observer.observe(element, { attributeFilter: [attributeName] });
		callback();
		return () => observer.disconnect();
	}, [
		refOrElement,
		attributeName,
		initialValue
	]);
	return attribute;
}
/**
* A `React.useEffect` that will not run on the first render.
*/
function useUpdateEffect(effect, deps) {
	const mounted = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		if (mounted.current) return effect();
		mounted.current = true;
	}, deps);
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => () => {
		mounted.current = false;
	}, []);
}
/**
* A `React.useLayoutEffect` that will not run on the first render.
*/
function useUpdateLayoutEffect(effect, deps) {
	const mounted = useRef(false);
	useSafeLayoutEffect(() => {
		if (mounted.current) return effect();
		mounted.current = true;
	}, deps);
	useSafeLayoutEffect(() => () => {
		mounted.current = false;
	}, []);
}
/**
* A React hook similar to `useState` and `useReducer`, but with the only
* purpose of re-rendering the component.
*/
function useForceUpdate() {
	return (0,react__WEBPACK_IMPORTED_MODULE_0__.useReducer)(() => [], []);
}
/**
* Returns an event callback similar to `useEvent`, but this also accepts a
* boolean value, which will be turned into a function.
*/
function useBooleanEvent(booleanOrCallback) {
	return useEvent(typeof booleanOrCallback === "function" ? booleanOrCallback : () => booleanOrCallback);
}
/**
* Returns props with an additional `wrapElement` prop.
*/
function useWrapElement(props, callback, deps = []) {
	const wrapElement = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)((element) => {
		if (props.wrapElement) element = props.wrapElement(element);
		return callback(element);
	}, [...deps, props.wrapElement]);
	return {
		...props,
		wrapElement
	};
}
/**
* Merges the portalRef prop and returns a `domReady` to be used in the
* components that use Portal underneath.
*/
function usePortalRef(portalProp = false, portalRefProp) {
	const [portalNode, setPortalNode] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
	return {
		portalRef: useMergeRefs(setPortalNode, portalRefProp),
		portalNode,
		domReady: !portalProp || portalNode
	};
}
/**
* A hook that passes metadata props around without leaking them to the DOM.
*/
function useMetadataProps(props, key, value) {
	const parent = props.onLoadedMetadataCapture;
	const onLoadedMetadataCapture = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
		return Object.assign(_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .noop */ .lQ.bind(null), parent, ...value !== void 0 ? [{ [key]: value }] : []);
	}, [
		parent,
		key,
		value
	]);
	return [parent?.[key], { onLoadedMetadataCapture }];
}
let hasInstalledGlobalEventListeners = false;
/**
* Returns a function that checks whether the mouse is moving.
*/
function useIsMouseMoving() {
	(0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
		if (hasInstalledGlobalEventListeners) return;
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .addGlobalEventListener */ .dg)("mousemove", setMouseMoving, true);
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .addGlobalEventListener */ .dg)("mousedown", resetMouseMoving, true);
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .addGlobalEventListener */ .dg)("mouseup", resetMouseMoving, true);
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .addGlobalEventListener */ .dg)("keydown", resetMouseMoving, true);
		(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .addGlobalEventListener */ .dg)("scroll", resetMouseMoving, true);
		hasInstalledGlobalEventListeners = true;
	}, []);
	return useEvent(() => mouseMoving);
}
let mouseMoving = false;
let previousScreenX = 0;
let previousScreenY = 0;
function hasMouseMovement(event) {
	const movementX = event.movementX || event.screenX - previousScreenX;
	const movementY = event.movementY || event.screenY - previousScreenY;
	previousScreenX = event.screenX;
	previousScreenY = event.screenY;
	return movementX || movementY || "production" === "test";
}
function setMouseMoving(event) {
	if (!hasMouseMovement(event)) return;
	mouseMoving = true;
}
function resetMouseMoving() {
	mouseMoving = false;
}
//#endregion
//#region src/system.tsx
/**
* The same as `React.forwardRef` but passes the `ref` as a prop and returns a
* component with the same generic type.
*
* Props holding `undefined` are dropped, so passing one behaves the same as
* omitting it and the component keeps the value it computes for itself.
*/
function forwardRef(render) {
	const Role = react__WEBPACK_IMPORTED_MODULE_0__.forwardRef((props, ref) => render((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_2__/* .removeUndefinedValues */ .HR)({
		...props,
		ref
	})));
	Role.displayName = render.displayName || render.name;
	return Role;
}
/**
* The same as `React.memo` but returns a component with the same generic type.
*/
function memo(Component, propsAreEqual) {
	return react__WEBPACK_IMPORTED_MODULE_0__.memo(Component, propsAreEqual);
}
/**
* Creates a React element that supports the `render` and `wrapElement` props.
*/
function createElement(Type, props) {
	const { wrapElement, render, ...rest } = props;
	const mergedRef = useMergeRefs(props.ref, getRefProperty(render));
	let element;
	if (react__WEBPACK_IMPORTED_MODULE_0__.isValidElement(render)) {
		const renderProps = {
			...render.props,
			ref: mergedRef
		};
		element = react__WEBPACK_IMPORTED_MODULE_0__.cloneElement(render, mergeProps(rest, renderProps));
	} else if (render) element = render(rest);
	else element = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(Type, { ...rest });
	if (wrapElement) return wrapElement(element);
	return element;
}
/**
* Creates a component hook that accepts props and returns props so they can be
* passed to a React element.
*/
function createHook(useProps) {
	const useRole = (props = {}) => {
		return useProps(props);
	};
	useRole.displayName = useProps.name;
	return useRole;
}
/**
* Creates an Ariakit store context with hooks and provider components.
*/
function createStoreContext(providers = [], scopedProviders = []) {
	const context = react__WEBPACK_IMPORTED_MODULE_0__.createContext(void 0);
	const scopedContext = react__WEBPACK_IMPORTED_MODULE_0__.createContext(void 0);
	const useContext = () => react__WEBPACK_IMPORTED_MODULE_0__.useContext(context);
	const useScopedContext = (onlyScoped = false) => {
		const scoped = react__WEBPACK_IMPORTED_MODULE_0__.useContext(scopedContext);
		const store = useContext();
		if (onlyScoped) return scoped;
		return scoped || store;
	};
	const useProviderContext = () => {
		const scoped = react__WEBPACK_IMPORTED_MODULE_0__.useContext(scopedContext);
		const store = useContext();
		if (scoped && scoped === store) return;
		return store;
	};
	const ContextProvider = (props) => {
		return providers.reduceRight((children, Provider) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(Provider, {
			...props,
			children
		}), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(context.Provider, { ...props }));
	};
	const ScopedContextProvider = (props) => {
		return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(ContextProvider, {
			...props,
			children: scopedProviders.reduceRight((children, Provider) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(Provider, {
				...props,
				children
			}), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(scopedContext.Provider, { ...props }))
		});
	};
	return {
		context,
		scopedContext,
		useContext,
		useScopedContext,
		useProviderContext,
		ContextProvider,
		ScopedContextProvider
	};
}
//#endregion


//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   B1: () => (/* binding */ subscribe),
/* harmony export */   OH: () => (/* binding */ sync),
/* harmony export */   Ts: () => (/* binding */ init),
/* harmony export */   UE: () => (/* binding */ throwOnConflictingProps),
/* harmony export */   cJ: () => (/* binding */ omit),
/* harmony export */   mj: () => (/* binding */ setup),
/* harmony export */   od: () => (/* binding */ mergeStore),
/* harmony export */   vA: () => (/* binding */ batch),
/* harmony export */   y$: () => (/* binding */ createStore)
/* harmony export */ });
/* unused harmony export pick */
/* harmony import */ var _ariakit_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");

//#region src/index.ts
function getInternal(store, key) {
	const internals = store.__unstableInternals;
	(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .invariant */ .V1)(internals, "Invalid store");
	return internals[key];
}
function hasUpdatedKey(keys, updatedKey) {
	if (!keys) return true;
	for (const currentKey of keys) if (updatedKey instanceof Set) {
		if (updatedKey.has(currentKey)) return true;
	} else if (isSameValue(currentKey, updatedKey)) return true;
	return false;
}
function isSameValue(value, other) {
	return value === other || value !== value && other !== other;
}
function getCleanupPrevState(prevState, state, stateBeforeCleanup, updatedKey) {
	let cleanupPrevState;
	for (const key of (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .getKeys */ .xD)(state)) {
		if (isSameValue(state[key], stateBeforeCleanup[key])) continue;
		if (updatedKey !== void 0 && hasUpdatedKey([key], updatedKey)) continue;
		cleanupPrevState ??= { ...prevState };
		cleanupPrevState[key] = state[key];
	}
	return cleanupPrevState;
}
const MAX_REPAIR_PASSES = 100;
function addKeyedListener(map, keys, listener) {
	if (!keys) return;
	for (const key of keys) {
		let listeners = map.get(key);
		if (!listeners) {
			listeners = /* @__PURE__ */ new Set();
			map.set(key, listeners);
		}
		listeners.add(listener);
	}
}
function deleteKeyedListener(map, keys, listener) {
	if (!map) return;
	if (!keys) return;
	for (const key of keys) {
		const listeners = map.get(key);
		if (!listeners) continue;
		listeners.delete(listener);
		if (!listeners.size) map.delete(key);
	}
}
function getFastPathNotifiedListeners(frame) {
	const notifiedListeners = /* @__PURE__ */ new Set();
	const currentListener = frame.currentListener;
	if (!currentListener) return notifiedListeners;
	for (const listener of frame.keyedListeners) {
		notifiedListeners.add(listener);
		if (listener === currentListener) return notifiedListeners;
	}
	notifiedListeners.clear();
	notifiedListeners.add(currentListener);
	return notifiedListeners;
}
function preserveFastPathNotifiedListeners(frame) {
	frame.notifiedListeners ??= getFastPathNotifiedListeners(frame);
}
function hasFastPathPassedListener(frame, listener) {
	if (!frame.currentListener) return false;
	let foundCurrentKeyedListener = false;
	for (const currentListener of frame.keyedListeners) {
		if (currentListener === frame.currentListener) {
			foundCurrentKeyedListener = true;
			continue;
		}
		if (!foundCurrentKeyedListener) continue;
		if (currentListener === listener) return false;
	}
	let foundListener = false;
	for (const currentListener of frame.group.listeners) {
		if (currentListener === frame.currentListener) return foundListener;
		if (currentListener === listener) foundListener = true;
	}
	return false;
}
function preserveFastPathFrames(fastPathFrames, group, listener) {
	for (const frame of fastPathFrames) {
		if (frame.group !== group) continue;
		if (frame.recovering) continue;
		if (listener && !frame.keyedListeners.has(listener)) continue;
		preserveFastPathNotifiedListeners(frame);
		for (const currentListener of frame.group.listeners) {
			if (currentListener === frame.currentListener) break;
			if (!hasFastPathPassedListener(frame, currentListener)) continue;
			frame.notifiedListeners?.add(currentListener);
		}
	}
}
function preserveFastPathPassedListeners(fastPathFrames, group, listener) {
	for (const frame of fastPathFrames) {
		if (frame.group !== group) continue;
		if (frame.recovering) continue;
		if (!hasFastPathPassedListener(frame, listener)) continue;
		preserveFastPathNotifiedListeners(frame);
		frame.notifiedListeners?.add(listener);
	}
}
function preserveFastPathPassedKeyedListeners({ fastPathFrames, group, keys, listener }) {
	const wasRegistered = group.listeners.has(listener);
	for (const frame of fastPathFrames) {
		if (frame.group !== group) continue;
		if (frame.recovering) continue;
		if (!keys.includes(frame.updatedKey)) continue;
		if (hasFastPathPassedListener(frame, listener)) {
			preserveFastPathNotifiedListeners(frame);
			frame.notifiedListeners?.add(listener);
		} else if (wasRegistered) {
			preserveFastPathNotifiedListeners(frame);
			frame.recoverToLive = true;
		}
	}
}
function clearFastPathNotifiedListener(fastPathFrames, group, listener) {
	for (const frame of fastPathFrames) {
		if (frame.group !== group) continue;
		frame.notifiedListeners?.delete(listener);
	}
}
function addFastPathKeyedListener({ fastPathFrames, group, keys, listener }) {
	for (const frame of fastPathFrames) {
		if (frame.group !== group) continue;
		if (frame.recovering) continue;
		if (!keys.includes(frame.updatedKey)) continue;
		frame.keyedListeners.add(listener);
	}
}
function runPendingCleanup(group, listener) {
	if (!group.disposables.size) return;
	const cleanup = group.disposables.get(listener);
	if (!cleanup) return;
	group.disposables.delete(listener);
	cleanup();
}
function setListenerCleanup(group, listener, cleanup) {
	const currentCleanup = group.disposables.get(listener);
	if (!currentCleanup) {
		group.disposables.set(listener, cleanup);
		return;
	}
	group.disposables.set(listener, () => {
		currentCleanup();
		cleanup();
	});
}
function notifyStoreListener(group, listener, state, prevState, getState, updatedKey) {
	if (group.suspendCounts?.has(listener)) return;
	const { disposables } = group;
	const cleanup = disposables.size ? disposables.get(listener) : void 0;
	if (cleanup) {
		disposables.delete(listener);
		const stateBeforeCleanup = state;
		cleanup();
		state = getState?.() ?? state;
		if (state !== stateBeforeCleanup) prevState = getCleanupPrevState(prevState, state, stateBeforeCleanup, updatedKey) ?? prevState;
	}
	const result = listener(state, prevState);
	if (result) setListenerCleanup(group, listener, result);
}
function runLiveListeners({ group, getState, prevState, updatedKey, notifiedListeners }) {
	const allKeysListeners = group.allKeysListeners;
	for (const listener of group.listeners) {
		if (notifiedListeners?.has(listener)) continue;
		if (!allKeysListeners?.has(listener)) {
			if (!hasUpdatedKey(group.listenerKeys.get(listener), updatedKey)) continue;
		}
		notifiedListeners?.add(listener);
		notifyStoreListener(group, listener, getState(), prevState, getState, updatedKey);
	}
}
/**
* Creates a store.
* @param initialState Initial state.
* @param stores Stores to extend.
*/
function createStore(initialState, ...stores) {
	let state = initialState;
	let prevStateBatch = state;
	let destroy = _ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .noop */ .lQ;
	let batchPending = false;
	let inDispatch = false;
	let updatedKeys = /* @__PURE__ */ new Set();
	const instances = /* @__PURE__ */ new Set();
	const setups = /* @__PURE__ */ new Set();
	const syncListenerGroup = {
		listeners: /* @__PURE__ */ new Set(),
		disposables: /* @__PURE__ */ new Map(),
		listenerKeys: /* @__PURE__ */ new WeakMap()
	};
	const batchListenerGroup = {
		listeners: /* @__PURE__ */ new Set(),
		disposables: /* @__PURE__ */ new Map(),
		listenerKeys: /* @__PURE__ */ new WeakMap()
	};
	const storeSetup = (callback) => {
		setups.add(callback);
		return () => setups.delete(callback);
	};
	const storeInit = () => {
		const initializedInstances = instances.size;
		const instance = Symbol();
		instances.add(instance);
		const maybeDestroy = () => {
			if (!instances.delete(instance)) return;
			if (instances.size) return;
			destroy();
		};
		if (initializedInstances) return maybeDestroy;
		const stateKeys = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .getKeys */ .xD)(state);
		const desyncs = [];
		for (const store of stores) {
			const storeState = store?.getState?.();
			if (!storeState) continue;
			const keys = stateKeys.filter((key) => (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(storeState, key));
			if (!keys.length) continue;
			if (stores.length === 1 || keys.length === stateKeys.length) {
				for (const key of keys) desyncs.push(sync(store, [key], (state) => {
					setState(key, state[key], true);
				}));
				continue;
			}
			desyncs.push(subscribe(store, keys, (state, prevState) => {
				for (const key of keys) {
					if (state[key] === prevState[key]) continue;
					setState(key, state[key], true);
				}
			}));
			for (const key of keys) {
				const liveState = store?.getState?.();
				if (!liveState) continue;
				setState(key, liveState[key], true);
			}
		}
		const teardowns = [];
		for (const setup of setups) teardowns.push(setup());
		const cleanups = stores.map(init);
		destroy = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .chain */ .cy)(...desyncs, ...teardowns, ...cleanups);
		return maybeDestroy;
	};
	const deleteListenerIndexes = (group, listener, keys) => {
		if (keys === void 0) return;
		if (keys) deleteKeyedListener(group.listenersByKey, keys, listener);
		else group.allKeysListeners?.delete(listener);
	};
	const fastPathFrames = [];
	const registerListener = (keys, listener, group = syncListenerGroup) => {
		const listenerKeysValue = keys ? [...keys] : null;
		const wasRegistered = group.listeners.has(listener);
		if (!wasRegistered) clearFastPathNotifiedListener(fastPathFrames, group, listener);
		if (!listenerKeysValue) {
			if (wasRegistered) preserveFastPathFrames(fastPathFrames, group);
			preserveFastPathPassedListeners(fastPathFrames, group, listener);
		} else preserveFastPathPassedKeyedListeners({
			fastPathFrames,
			group,
			keys: listenerKeysValue,
			listener
		});
		if (wasRegistered) {
			preserveFastPathFrames(fastPathFrames, group, listener);
			deleteListenerIndexes(group, listener, group.listenerKeys.get(listener));
		}
		group.listeners.add(listener);
		if (listenerKeysValue) {
			group.listenersByKey ??= /* @__PURE__ */ new Map();
			addKeyedListener(group.listenersByKey, listenerKeysValue, listener);
			addFastPathKeyedListener({
				fastPathFrames,
				group,
				keys: listenerKeysValue,
				listener
			});
		} else {
			group.allKeysListeners ??= /* @__PURE__ */ new Set();
			group.allKeysListeners.add(listener);
		}
		group.listenerKeys.set(listener, listenerKeysValue);
		return () => {
			const cleanup = group.disposables.get(listener);
			group.disposables.delete(listener);
			preserveFastPathFrames(fastPathFrames, group, listener);
			const currentKeys = group.listenerKeys.get(listener);
			deleteListenerIndexes(group, listener, listenerKeysValue);
			if (currentKeys !== listenerKeysValue) deleteListenerIndexes(group, listener, currentKeys);
			group.listenerKeys.delete(listener);
			group.listeners.delete(listener);
			cleanup?.();
		};
	};
	const storeSubscribe = (keys, listener) => registerListener(keys, listener);
	const runInitialListener = (group, listener, prevState) => {
		const shouldSuspend = group.listeners.has(listener);
		if (shouldSuspend) {
			group.suspendCounts ??= /* @__PURE__ */ new Map();
			const count = group.suspendCounts.get(listener) ?? 0;
			group.suspendCounts.set(listener, count + 1);
		}
		let cleanupPrevState;
		try {
			const stateBeforeCleanups = state;
			runPendingCleanup(group, listener);
			if (state !== stateBeforeCleanups) cleanupPrevState = getCleanupPrevState(prevState, state, stateBeforeCleanups);
			const cleanup = listener(state, cleanupPrevState ?? prevState);
			if (cleanup) setListenerCleanup(group, listener, cleanup);
		} finally {
			if (shouldSuspend) {
				const suspendCounts = group.suspendCounts;
				const count = suspendCounts?.get(listener);
				if (count && count > 1) suspendCounts?.set(listener, count - 1);
				else suspendCounts?.delete(listener);
				if (!suspendCounts?.size) delete group.suspendCounts;
			}
		}
	};
	const storeSync = (keys, listener) => {
		runInitialListener(syncListenerGroup, listener, state);
		return registerListener(keys, listener);
	};
	const storeBatch = (keys, listener) => {
		if (!batchListenerGroup.listeners.size && !inDispatch) prevStateBatch = state;
		runInitialListener(batchListenerGroup, listener, prevStateBatch);
		return registerListener(keys, listener, batchListenerGroup);
	};
	const storePick = (keys) => createStore((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .pick */ .Up)(state, keys), finalStore);
	const storeOmit = (keys) => createStore((0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .omit */ .cJ)(state, keys), finalStore);
	const getState = () => state;
	const runListeners = (group, prevState, updatedKey) => {
		if (!(updatedKey instanceof Set) && !group.allKeysListeners?.size) {
			const keyedListeners = group.listenersByKey?.get(updatedKey);
			if (!keyedListeners) return;
			const frame = {
				group,
				keyedListeners,
				updatedKey,
				currentListener: null
			};
			fastPathFrames.push(frame);
			try {
				for (const listener of keyedListeners) {
					if (frame.notifiedListeners?.has(listener)) continue;
					frame.currentListener = listener;
					frame.notifiedListeners?.add(listener);
					notifyStoreListener(group, listener, state, prevState, getState, updatedKey);
					if (!group.allKeysListeners?.size && !frame.recoverToLive) continue;
					const notifiedListeners = frame.notifiedListeners ?? getFastPathNotifiedListeners(frame);
					frame.notifiedListeners = notifiedListeners;
					frame.recovering = true;
					runLiveListeners({
						group,
						getState,
						prevState,
						updatedKey,
						notifiedListeners
					});
					return;
				}
			} finally {
				fastPathFrames.pop();
			}
			return;
		}
		runLiveListeners({
			group,
			getState,
			prevState,
			updatedKey
		});
	};
	const setState = (key, value, fromStores = false) => {
		if (!(0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(state, key)) return;
		const currentValue = state[key];
		const nextValue = (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .applyState */ .Qh)(value, () => currentValue);
		if (isSameValue(nextValue, currentValue)) return;
		const wasInDispatch = inDispatch;
		inDispatch = true;
		const prevState = state;
		const nextState = {
			...state,
			[key]: nextValue
		};
		state = nextState;
		let superseded = false;
		try {
			if (!fromStores && stores.length) {
				for (const store of stores) {
					store?.setState?.(key, nextValue);
					if (isSameValue(state[key], nextValue)) continue;
					superseded = true;
					break;
				}
				if (superseded) {
					let pass = 0;
					for (; pass < MAX_REPAIR_PASSES; pass += 1) {
						let changed = false;
						for (const store of stores) {
							const previousValue = state[key];
							store?.setState?.(key, previousValue);
							if (!isSameValue(state[key], previousValue)) changed = true;
						}
						if (!changed) break;
					}
					if (false) {}
				}
			}
			if (!superseded) {
				const listenerPrevState = state === nextState ? prevState : {
					...state,
					[key]: prevState[key]
				};
				runListeners(syncListenerGroup, listenerPrevState, key);
			}
		} finally {
			inDispatch = wasInDispatch;
		}
		if (!batchListenerGroup.listeners.size) {
			if (!inDispatch) prevStateBatch = state;
			return;
		}
		updatedKeys.add(key);
		if (batchPending) return;
		batchPending = true;
		queueMicrotask(() => {
			batchPending = false;
			const snapshot = state;
			const updatedKeysSnapshot = updatedKeys;
			updatedKeys = /* @__PURE__ */ new Set();
			const prevStateBatchBefore = prevStateBatch;
			runListeners(batchListenerGroup, prevStateBatchBefore, updatedKeysSnapshot);
			if (prevStateBatch === prevStateBatchBefore) prevStateBatch = snapshot;
		});
	};
	const finalStore = {
		getState,
		setState,
		__unstableInternals: {
			setup: storeSetup,
			init: storeInit,
			subscribe: storeSubscribe,
			sync: storeSync,
			batch: storeBatch,
			pick: storePick,
			omit: storeOmit
		}
	};
	return finalStore;
}
/**
* Register a callback function that's called when the store is initialized.
*/
function setup(store, ...args) {
	if (!store) return;
	return getInternal(store, "setup")(...args);
}
/**
* Function that should be called when the store is initialized.
*/
function init(store, ...args) {
	if (!store) return;
	return getInternal(store, "init")(...args);
}
/**
* Registers a listener function that's called after state changes in the store.
*/
function subscribe(store, ...args) {
	if (!store) return;
	return getInternal(store, "subscribe")(...args);
}
/**
* Registers a listener function that's called immediately and synchronously
* whenever the store state changes.
*/
function sync(store, ...args) {
	if (!store) return;
	return getInternal(store, "sync")(...args);
}
/**
* Registers a listener function that's called immediately and after a batch of
* state changes in the store.
*/
function batch(store, ...args) {
	if (!store) return;
	return getInternal(store, "batch")(...args);
}
/**
* Creates a new store with a subset of the current store state and keeps them
* in sync.
*/
function omit(store, ...args) {
	if (!store) return;
	return getInternal(store, "omit")(...args);
}
/**
* Creates a new store with a subset of the current store state and keeps them
* in sync.
*/
function pick(store, ...args) {
	if (!store) return;
	return getInternal(store, "pick")(...args);
}
/**
* Merges multiple stores into a single store.
*/
function mergeStore(...stores) {
	const initialState = {};
	for (const store of stores) {
		const nextState = store?.getState?.();
		if (nextState) Object.assign(initialState, nextState);
	}
	const store = createStore(initialState, ...stores);
	return Object.assign({}, ...stores, store);
}
/**
* Throws when a store prop is passed in conjunction with a default state.
*/
function throwOnConflictingProps(props, store) {
	if (true) return;
	if (!store) return;
	const defaultKeys = Object.entries(props).filter(([key, value]) => key.startsWith("default") && value !== void 0).map(([key]) => {
		const stateKey = key.replace("default", "");
		return `${stateKey[0]?.toLowerCase() || ""}${stateKey.slice(1)}`;
	});
	if (!defaultKeys.length) return;
	const storeState = store.getState();
	if (!defaultKeys.filter((key) => (0,_ariakit_utils__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(storeState, key)).length) return;
	throw new Error(`Passing a store prop in conjunction with a default state is not supported.

const store = useSelectStore();
<SelectProvider store={store} defaultValue="Apple" />
                ^             ^

Instead, pass the default state to the topmost store:

const store = useSelectStore({ defaultValue: "Apple" });
<SelectProvider store={store} />

See https://github.com/ariakit/ariakit/pull/2745 for more details.

If there's a particular need for this, please submit a feature request at https://github.com/ariakit/ariakit
`);
}
//#endregion


//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $f: () => (/* binding */ disabledFromProps),
/* harmony export */   AJ: () => (/* binding */ hasFocus),
/* harmony export */   Bj: () => (/* binding */ isTextbox),
/* harmony export */   Bm: () => (/* binding */ isButton),
/* harmony export */   D_: () => (/* binding */ identity),
/* harmony export */   HR: () => (/* binding */ removeUndefinedValues),
/* harmony export */   Jh: () => (/* binding */ defaultValue),
/* harmony export */   LC: () => (/* binding */ getFirstTabbableIn),
/* harmony export */   Ll: () => (/* binding */ isNode),
/* harmony export */   M9: () => (/* binding */ flatten2DArray),
/* harmony export */   Mk: () => (/* binding */ getTextboxValue),
/* harmony export */   QZ: () => (/* binding */ restoreFocusIn),
/* harmony export */   Qh: () => (/* binding */ applyState),
/* harmony export */   Sw: () => (/* binding */ canUseDOM),
/* harmony export */   Up: () => (/* binding */ pick),
/* harmony export */   V1: () => (/* binding */ invariant),
/* harmony export */   XJ: () => (/* binding */ getPreviousTabbable),
/* harmony export */   YE: () => (/* binding */ getDocument),
/* harmony export */   Zy: () => (/* binding */ getTextboxSelection),
/* harmony export */   a9: () => (/* binding */ getAllTabbableIn),
/* harmony export */   aG: () => (/* binding */ isFocusEventOutside),
/* harmony export */   bq: () => (/* binding */ getActiveElement),
/* harmony export */   c$: () => (/* binding */ fireBlurEvent),
/* harmony export */   cJ: () => (/* binding */ omit),
/* harmony export */   cX: () => (/* binding */ isMac),
/* harmony export */   cy: () => (/* binding */ chain),
/* harmony export */   dg: () => (/* binding */ addGlobalEventListener),
/* harmony export */   f8: () => (/* binding */ isApple),
/* harmony export */   gH: () => (/* binding */ sortBasedOnDOMPosition),
/* harmony export */   gR: () => (/* binding */ contains),
/* harmony export */   gm: () => (/* binding */ isFirefox),
/* harmony export */   gw: () => (/* binding */ disableFocusIn),
/* harmony export */   hY: () => (/* binding */ fireClickEvent),
/* harmony export */   ho: () => (/* binding */ isPortalEvent),
/* harmony export */   lQ: () => (/* binding */ noop),
/* harmony export */   lp: () => (/* binding */ disabledFromElement),
/* harmony export */   mB: () => (/* binding */ isTextField),
/* harmony export */   mQ: () => (/* binding */ hasOwnProperty),
/* harmony export */   nr: () => (/* binding */ isSafari),
/* harmony export */   ny: () => (/* binding */ afterPaint),
/* harmony export */   nz: () => (/* binding */ queueBeforeEvent),
/* harmony export */   oW: () => (/* binding */ hasFocusWithin),
/* harmony export */   q7: () => (/* binding */ reverseArray),
/* harmony export */   qj: () => (/* binding */ getScrollingElement),
/* harmony export */   tp: () => (/* binding */ isFocusable),
/* harmony export */   uh: () => (/* binding */ isSelfTarget),
/* harmony export */   vF: () => (/* binding */ getNextTabbable),
/* harmony export */   vq: () => (/* binding */ isElement),
/* harmony export */   xD: () => (/* binding */ getKeys),
/* harmony export */   zN: () => (/* binding */ isVisible),
/* harmony export */   zO: () => (/* binding */ isFalsyBooleanCallback),
/* harmony export */   zk: () => (/* binding */ getWindow)
/* harmony export */ });
/* unused harmony exports UndoManager, addItemToArray, beforePaint, createUndoManager, cx, disableFocus, fireEvent, fireFocusEvent, fireKeyboardEvent, focusIfNeeded, focusIntoView, getAllFocusable, getAllFocusableIn, getAllTabbable, getClosestFocusable, getFirstFocusable, getFirstFocusableIn, getFirstTabbable, getInputType, getItemRoleByPopupRole, getLastTabbable, getLastTabbableIn, getNextTabbableIn, getPopupItemRole, getPopupRole, getPreviousTabbableIn, isDownloading, isEmpty, isFrame, isInputEvent, isInteger, isObject, isOpeningInNewTab, isPartiallyHidden, isTabbable, isTouchDevice, normalizeString, scrollIntoViewIfNeeded, setSelectionRange, shallowEqual, toArray, warnOnce */
//#region src/array.ts
/**
* Array helpers used by Ariakit packages.
* @module Array utilities
*/
/**
* Transforms `arg` into an array if it's not already.
* @example
* toArray("a"); // ["a"]
* toArray(["a"]); // ["a"]
*/
function toArray(arg) {
	if (Array.isArray(arg)) return arg;
	return typeof arg !== "undefined" ? [arg] : [];
}
/**
* Immutably adds an item to an array.
* @example
* addItemToArray(["a", "b", "d"], "c", 2); // ["a", "b", "c", "d"]
* @returns {Array} A new array with the item in the passed array index.
*/
function addItemToArray(array, item, index = -1) {
	if (!(index in array)) return [...array, item];
	return [
		...array.slice(0, index),
		item,
		...array.slice(index)
	];
}
/**
* Flattens a 2D array into a one-dimensional array.
* @example
* flatten2DArray([["a"], ["b"], ["c"]]); // ["a", "b", "c"]
*
* @returns {Array} A one-dimensional array.
*/
function flatten2DArray(array) {
	const flattened = [];
	for (const row of array) flattened.push(...row);
	return flattened;
}
/**
* Immutably reverses an array.
* @example
* reverseArray(["a", "b", "c"]); // ["c", "b", "a"]
* @returns {Array} Reversed array.
*/
function reverseArray(array) {
	return array.slice().reverse();
}
//#endregion
//#region src/misc.ts
/**
* Empty function.
*/
function noop(..._) {}
/**
* Compares two objects.
* @example
* shallowEqual({ a: "a" }, {}); // false
* shallowEqual({ a: "a" }, { b: "b" }); // false
* shallowEqual({ a: "a" }, { a: "a" }); // true
* shallowEqual({ a: "a" }, { a: "a", b: "b" }); // false
*/
function shallowEqual(a, b) {
	if (a === b) return true;
	if (!a) return false;
	if (!b) return false;
	if (typeof a !== "object") return false;
	if (typeof b !== "object") return false;
	const aKeys = Object.keys(a);
	const bKeys = Object.keys(b);
	const { length } = aKeys;
	if (bKeys.length !== length) return false;
	for (const key of aKeys) if (a[key] !== b[key]) return false;
	return true;
}
/**
* Receives a `setState` argument and calls it with `currentValue` if it's a
* function. Otherwise return the argument as the new value.
* @example
* applyState((value) => value + 1, 1); // 2
* applyState(2, 1); // 2
*/
function applyState(argument, currentValue) {
	if (isUpdater(argument)) return argument(isLazyValue(currentValue) ? currentValue() : currentValue);
	return argument;
}
function isUpdater(argument) {
	return typeof argument === "function";
}
function isLazyValue(value) {
	return typeof value === "function";
}
/**
* Checks whether `arg` is an object or not.
* @returns {boolean}
*/
function isObject(arg) {
	return typeof arg === "object" && arg != null;
}
/**
* Checks whether `arg` is empty or not.
* @example
* isEmpty([]); // true
* isEmpty(["a"]); // false
* isEmpty({}); // true
* isEmpty({ a: "a" }); // false
* isEmpty(); // true
* isEmpty(null); // true
* isEmpty(undefined); // true
* isEmpty(""); // true
*/
function isEmpty(arg) {
	if (Array.isArray(arg)) return !arg.length;
	if (isObject(arg)) return !Object.keys(arg).length;
	if (arg == null) return true;
	if (arg === "") return true;
	return false;
}
/**
* Checks whether `arg` is an integer or not.
* @example
* isInteger(1); // true
* isInteger(1.5); // false
* isInteger("1"); // true
* isInteger("1.5"); // false
*/
function isInteger(arg) {
	if (typeof arg === "number") return Math.floor(arg) === arg;
	return String(Math.floor(Number(arg))) === arg;
}
/**
* Checks whether `prop` is an own property of `obj` or not.
*/
function hasOwnProperty(object, prop) {
	if (typeof Object.hasOwn === "function") return Object.hasOwn(object, prop);
	return Object.prototype.hasOwnProperty.call(object, prop);
}
/**
* Receives functions as arguments and returns a new function that calls all.
*/
function chain(...fns) {
	return (...args) => {
		for (const fn of fns) if (typeof fn === "function") fn(...args);
	};
}
/**
* Returns a string with the truthy values of `args` separated by space.
*/
function cx(...args) {
	return args.filter(Boolean).join(" ") || void 0;
}
/**
* Removes diacritics from a string.
*/
function normalizeString(str) {
	return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}
/**
* Omits specific keys from an object.
* @example
* omit({ a: "a", b: "b" }, ["a"]); // { b: "b" }
*/
function omit(object, keys) {
	const result = { ...object };
	for (const key of keys) if (hasOwnProperty(result, key)) delete result[key];
	return result;
}
/**
* Picks specific keys from an object.
* @example
* pick({ a: "a", b: "b" }, ["a"]); // { a: "a" }
*/
function pick(object, paths) {
	const result = {};
	for (const key of paths) if (hasOwnProperty(object, key)) result[key] = object[key];
	return result;
}
/**
* Returns the same argument.
*/
function identity(value) {
	return value;
}
/**
* Runs right before the next paint.
*/
function beforePaint(cb = noop) {
	const raf = requestAnimationFrame(cb);
	return () => cancelAnimationFrame(raf);
}
/**
* Runs after the next paint.
*/
function afterPaint(cb = noop) {
	let raf = requestAnimationFrame(() => {
		raf = requestAnimationFrame(cb);
	});
	return () => cancelAnimationFrame(raf);
}
const defaultWarnOnceKey = {};
const warningMessages = /* @__PURE__ */ new WeakMap();
/**
* Logs a warning only once for each message and key.
* @example
* if (process.env.NODE_ENV !== "production") {
*   warnOnce("Warning");
* }
*/
function warnOnce(message, key) {
	const warningKey = key || defaultWarnOnceKey;
	let messages = warningMessages.get(warningKey);
	if (!messages) {
		messages = /* @__PURE__ */ new Set();
		warningMessages.set(warningKey, messages);
	}
	if (messages.has(message)) return;
	messages.add(message);
	console.warn(message);
}
/**
* Asserts that a condition is true, otherwise throws an error.
* @example
* invariant(
*   condition,
*   process.env.NODE_ENV !== "production" && "Invariant failed"
* );
*/
function invariant(condition, message) {
	if (condition) return;
	if (typeof message !== "string") throw new Error("Invariant failed");
	throw new Error(message);
}
/**
* Similar to `Object.keys` but returns a type-safe array of keys.
*/
function getKeys(obj) {
	return Object.keys(obj);
}
/**
* Checks whether a boolean event prop (e.g., hideOnInteractOutside) was
* intentionally set to false, either with a boolean value or a callback that
* returns false.
*/
function isFalsyBooleanCallback(booleanOrCallback, ...args) {
	const result = typeof booleanOrCallback === "function" ? booleanOrCallback(...args) : booleanOrCallback;
	if (result == null) return false;
	return !result;
}
/**
* Checks whether something is disabled or not based on its props.
*/
function disabledFromProps(props) {
	return props.disabled || props["aria-disabled"] === true || props["aria-disabled"] === "true";
}
/**
* Checks whether something is disabled or not based on its DOM attributes.
*/
function disabledFromElement(element) {
	if (element.getAttribute("aria-disabled") === "true") return true;
	if ("disabled" in element && element.disabled === true) return true;
	return false;
}
/**
* Removes undefined values from an object. Only own properties are copied, so
* an inherited enumerable property never becomes an own property of the result.
* A `__proto__` key is dropped because assigning it would replace the prototype
* of the result instead of adding a property to it.
*/
function removeUndefinedValues(obj) {
	const result = {};
	for (const key in obj) {
		if (!hasOwnProperty(obj, key)) continue;
		if (key === "__proto__") continue;
		if (obj[key] !== void 0) result[key] = obj[key];
	}
	return result;
}
function defaultValue(...values) {
	for (const value of values) if (value !== void 0) return value;
}
//#endregion
//#region src/dom.ts
/**
* DOM helpers for browser, iframe, text input, popup, and scrolling behavior.
* @module DOM utilities
*/
/**
* It's `true` if it is running in a browser environment or `false` if it is not
* (SSR).
* @example
* const title = canUseDOM ? document.title : "";
*/
const canUseDOM = checkIsBrowser();
function checkIsBrowser() {
	return typeof window !== "undefined" && !!window.document?.createElement;
}
function lookupGetter(prototype, name) {
	let target = prototype;
	while (target) {
		const descriptor = Object.getOwnPropertyDescriptor(target, name);
		if (descriptor?.get) return descriptor.get;
		target = Object.getPrototypeOf(target);
	}
}
const nodeTypeGetter = canUseDOM ? lookupGetter(Object.getPrototypeOf(window.document), "nodeType") : void 0;
const activeElementGetter = canUseDOM ? lookupGetter(Object.getPrototypeOf(window.document), "activeElement") : void 0;
function isWindow(value) {
	if (!value) return false;
	return value.window === value;
}
function isDocument(value) {
	if (!value) return false;
	if (!nodeTypeGetter) return value.nodeType === 9;
	try {
		return nodeTypeGetter.call(value) === 9;
	} catch {
		return false;
	}
}
function ownsDocument(view, ownerDocument) {
	try {
		return view.document === ownerDocument;
	} catch {
		return false;
	}
}
/**
* Returns the document `node` belongs to, or the current one when it has none.
*/
function getDocument(node) {
	if (!node) return document;
	if (isDocument(node)) return node;
	if (isWindow(node)) return node.document;
	const nodeDocument = node.ownerDocument;
	if (isDocument(nodeDocument)) return nodeDocument;
	return document;
}
/**
* Returns the window `node` belongs to, or the current one when it has none.
*/
function getWindow(node) {
	if (!node) return self;
	if (isWindow(node)) return node;
	const nodeDocument = getDocument(node);
	const { defaultView } = nodeDocument;
	if (isWindow(defaultView) && ownsDocument(defaultView, nodeDocument)) return defaultView;
	return window;
}
/**
* Returns the focused element for `node`'s document, resolving into a focused
* frame's own document unless `frame` is `false`.
* @example
* // Focus inside a frame, as the frame element rather than the inner element.
* getActiveElement(document.getElementById("dialog"), { frame: false });
*/
function getActiveElement(node, { frame = true, activeDescendant = false } = {}) {
	const ownerDocument = getDocument(node);
	const activeElement = activeElementGetter ? activeElementGetter.call(ownerDocument) : ownerDocument.activeElement;
	if (!activeElement?.nodeName) return null;
	if (frame && isFrame(activeElement) && activeElement.contentDocument?.body) return getActiveElement(activeElement.contentDocument.body, {
		frame,
		activeDescendant
	});
	if (activeDescendant) {
		const id = activeElement.getAttribute("aria-activedescendant");
		if (id) {
			const element = getDocument(activeElement).getElementById(id);
			if (element) return element;
		}
	}
	return activeElement;
}
/**
* Similar to `Element.prototype.contains`, but a little bit faster when
* `element` is the same as `child`.
* @example
* contains(
*   document.getElementById("parent"),
*   document.getElementById("child")
* );
*/
function contains(parent, child) {
	return parent === child || parent.contains(child);
}
/**
* Checks whether the given event target is an element.
*
* `event.target` and `event.relatedTarget` are `EventTarget`s, which aren't
* necessarily elements — for example `window` or an `XMLHttpRequest` when an
* event is dispatched programmatically. Calling `Element`-only methods such as
* `hasAttribute` on those throws, so guard with this before treating them as
* elements. When you only need a `Node` — for example to call `contains` — use
* `isNode` instead.
*
* It tests `nodeType` rather than `instanceof Element` so that elements coming
* from same-origin child frames (which `addGlobalEventListener` also listens
* on) aren't wrongly rejected for belonging to a different realm.
* @example
* if (isElement(event.target)) {
*   event.target.hasAttribute("data-active");
* }
*/
function isElement(target) {
	return target?.nodeType === 1;
}
/**
* Checks whether the given event target is a node.
*
* Like `isElement`, but only requires the target to be a `Node` rather than an
* element — useful before calling `contains`, which accepts any node. It still
* rejects non-node `EventTarget`s (such as `window` or an `XMLHttpRequest`)
* that would make `contains` throw.
* @example
* if (isNode(event.target)) {
*   contains(element, event.target);
* }
*/
function isNode(target) {
	return typeof target?.nodeType === "number";
}
/**
* Checks whether `element` is a frame element.
*/
function isFrame(element) {
	return element.tagName === "IFRAME";
}
/**
* Checks whether `element` is a native HTML button element.
* @example
* isButton(document.querySelector("button")); // true
* isButton(document.querySelector("input[type='button']")); // true
* isButton(document.querySelector("div")); // false
* isButton(document.querySelector("input[type='text']")); // false
* isButton(document.querySelector("div[role='button']")); // false
*/
function isButton(element) {
	const tagName = element.tagName.toLowerCase();
	if (tagName === "button") return true;
	if (tagName === "input" && element.type) return buttonInputTypes.indexOf(element.type) !== -1;
	return false;
}
const buttonInputTypes = [
	"button",
	"color",
	"file",
	"image",
	"reset",
	"submit"
];
/**
* Checks if the element is visible or not.
*/
function isVisible(element) {
	if (typeof element.checkVisibility === "function") return element.checkVisibility();
	const htmlElement = element;
	return htmlElement.offsetWidth > 0 || htmlElement.offsetHeight > 0 || element.getClientRects().length > 0;
}
/**
* Check whether the given element is a text field, where text field is defined
* by the ability to select within the input.
* @example
* isTextField(document.querySelector("div")); // false
* isTextField(document.querySelector("input")); // true
* isTextField(document.querySelector("input[type='button']")); // false
* isTextField(document.querySelector("textarea")); // true
*/
function isTextField(element) {
	try {
		if (element.tagName === "TEXTAREA") return true;
		if (element.tagName !== "INPUT") return false;
		return element.selectionStart !== null;
	} catch (_error) {
		return false;
	}
}
/**
* Check whether the given element is a text field or a content editable
* element.
*/
function isTextbox(element) {
	return element.isContentEditable || isTextField(element);
}
/**
* Returns the value of the text field or content editable element as a string.
*/
function getTextboxValue(element) {
	if (isTextField(element)) return element.value;
	if (element.isContentEditable) {
		const range = getDocument(element).createRange();
		range.selectNodeContents(element);
		return range.toString();
	}
	return "";
}
/**
* Returns the start and end offsets of the selection in the element.
*/
function getTextboxSelection(element) {
	let start = 0;
	let end = 0;
	if (isTextField(element)) {
		start = element.selectionStart || 0;
		end = element.selectionEnd || 0;
	} else if (element.isContentEditable) {
		const selection = getDocument(element).getSelection();
		if (selection?.rangeCount && selection.anchorNode && contains(element, selection.anchorNode) && selection.focusNode && contains(element, selection.focusNode)) {
			const range = selection.getRangeAt(0);
			const nextRange = range.cloneRange();
			nextRange.selectNodeContents(element);
			nextRange.setEnd(range.startContainer, range.startOffset);
			start = nextRange.toString().length;
			nextRange.setEnd(range.endContainer, range.endOffset);
			end = nextRange.toString().length;
		}
	}
	return {
		start,
		end
	};
}
const allowedPopupRoles = (/* unused pure expression or super */ null && ([
	"dialog",
	"menu",
	"listbox",
	"tree",
	"grid"
]));
const itemRoleByPopupRole = {
	menu: "menuitem",
	listbox: "option",
	tree: "treeitem"
};
/**
* Returns the popup role from the element's role attribute, if it has one.
*/
function getPopupRole(element, fallback) {
	const role = element?.getAttribute("role");
	if (role && allowedPopupRoles.indexOf(role) !== -1) return role;
	return fallback;
}
/**
* Returns the item role based on the popup role.
*/
function getItemRoleByPopupRole(popupRole) {
	if (popupRole == null) return;
	if (!hasOwnProperty(itemRoleByPopupRole, popupRole)) return;
	return itemRoleByPopupRole[popupRole];
}
/**
* Returns the item role attribute based on the popup's role.
*/
function getPopupItemRole(element, fallback) {
	const popupRole = getPopupRole(element);
	if (typeof popupRole !== "string") return fallback;
	return getItemRoleByPopupRole(popupRole) ?? fallback;
}
/**
* Calls `element.scrollIntoView()` if the element is hidden or partly hidden in
* the viewport.
*/
function scrollIntoViewIfNeeded(element, arg) {
	if (isPartiallyHidden(element) && "scrollIntoView" in element) element.scrollIntoView(arg);
}
/**
* Returns the scrolling container element of a given element.
*/
function getScrollingElement(element) {
	if (!element) return null;
	const isScrollableOverflow = (overflow) => {
		if (overflow === "auto") return true;
		if (overflow === "scroll") return true;
		return false;
	};
	if (element.clientHeight && element.scrollHeight > element.clientHeight) {
		const { overflowY } = getComputedStyle(element);
		if (isScrollableOverflow(overflowY)) return element;
	} else if (element.clientWidth && element.scrollWidth > element.clientWidth) {
		const { overflowX } = getComputedStyle(element);
		if (isScrollableOverflow(overflowX)) return element;
	}
	const doc = getDocument(element);
	return getScrollingElement(element.parentElement) || doc.scrollingElement || doc.body;
}
/**
* Determines whether an element is hidden or partially hidden in the viewport.
*/
function isPartiallyHidden(element) {
	const elementRect = element.getBoundingClientRect();
	const scroller = getScrollingElement(element);
	if (!scroller) return false;
	const scrollerRect = scroller.getBoundingClientRect();
	const isHTML = scroller.tagName === "HTML";
	const scrollerTop = isHTML ? scrollerRect.top + scroller.scrollTop : scrollerRect.top;
	const scrollerBottom = isHTML ? scroller.clientHeight : scrollerRect.bottom;
	const scrollerLeft = isHTML ? scrollerRect.left + scroller.scrollLeft : scrollerRect.left;
	const scrollerRight = isHTML ? scroller.clientWidth : scrollerRect.right;
	const top = elementRect.top < scrollerTop;
	const left = elementRect.left < scrollerLeft;
	const bottom = elementRect.bottom > scrollerBottom;
	const right = elementRect.right > scrollerRight;
	return top || left || bottom || right;
}
/**
* SelectionRange only works on a few types of input. Calling
* `setSelectionRange` on an unsupported input type may throw an error on
* certain browsers. To avoid it, we check if its type supports SelectionRange
* first. It will be a noop to non-supported types until we find a workaround.
*
* @see
* https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/setSelectionRange
*/
function setSelectionRange(element, ...args) {
	if (/text|search|password|tel|url/i.test(element.type)) element.setSelectionRange(...args);
}
/**
* Sort the items based on their DOM position.
*/
function sortBasedOnDOMPosition(items, getElement) {
	const pairs = items.map((item, index) => [index, item]);
	let isOrderDifferent = false;
	pairs.sort(([indexA, a], [indexB, b]) => {
		const elementA = getElement(a);
		const elementB = getElement(b);
		if (elementA === elementB) return 0;
		if (!elementA || !elementB) return 0;
		if (isElementPreceding(elementA, elementB)) {
			if (indexA > indexB) isOrderDifferent = true;
			return -1;
		}
		if (indexA < indexB) isOrderDifferent = true;
		return 1;
	});
	if (isOrderDifferent) return pairs.map(([_, item]) => item);
	return items;
}
function isElementPreceding(a, b) {
	return Boolean(b.compareDocumentPosition(a) & Node.DOCUMENT_POSITION_PRECEDING);
}
//#endregion
//#region src/platform.ts
/**
* Browser and platform detection helpers.
* @module Platform utilities
*/
/**
* Detects if the device has touch capabilities.
*/
function isTouchDevice() {
	return canUseDOM && !!navigator.maxTouchPoints;
}
/**
* Detects Apple device.
*/
function isApple() {
	if (!canUseDOM) return false;
	return /mac|iphone|ipad|ipod/i.test(navigator.platform);
}
/**
* Detects Safari browser.
*/
function isSafari() {
	return canUseDOM && isApple() && /apple/i.test(navigator.vendor);
}
/**
* Detects Firefox browser.
*/
function isFirefox() {
	return canUseDOM && /firefox\//i.test(navigator.userAgent);
}
/**
* Detects Mac computer.
*/
function isMac() {
	return canUseDOM && navigator.platform.startsWith("Mac") && !isTouchDevice();
}
//#endregion
//#region src/events.ts
/**
* Event helpers for dispatching and interpreting browser events.
* @module Event utilities
*/
/**
* Returns `true` if `event` has been fired within a React Portal element.
*/
function isPortalEvent(event) {
	const { currentTarget, target } = event;
	if (!currentTarget) return false;
	if (!isNode(target)) return true;
	return !contains(currentTarget, target);
}
/**
* Returns `true` if `event.target` and `event.currentTarget` are the same.
*/
function isSelfTarget(event) {
	return event.target === event.currentTarget;
}
function isActivatableNavigationTarget(element) {
	if (!isElement(element)) return false;
	const target = element;
	const tagName = target.tagName.toLowerCase();
	if (tagName === "a") return true;
	if (tagName === "button" && target.type === "submit") return true;
	if (tagName === "input" && target.type === "submit") return true;
	return false;
}
/**
* Checks whether the user event is triggering a page navigation in a new tab.
*/
function isOpeningInNewTab(event) {
	const isAppleDevice = isApple();
	if (isAppleDevice && !event.metaKey) return false;
	if (!isAppleDevice && !event.ctrlKey) return false;
	return isActivatableNavigationTarget(event.currentTarget);
}
/**
* Checks whether the user event is triggering a download.
*/
function isDownloading(event) {
	if (!event.altKey) return false;
	return isActivatableNavigationTarget(event.currentTarget);
}
/**
* Creates and dispatches an event.
* @example
* fireEvent(document.getElementById("id"), "blur", {
*   bubbles: true,
*   cancelable: true,
* });
*/
function fireEvent(element, type, eventInit) {
	const { Event } = getWindow(element);
	const event = new Event(type, eventInit);
	return element.dispatchEvent(event);
}
/**
* Creates and dispatches a blur event.
* @example
* fireBlurEvent(document.getElementById("id"));
*/
function fireBlurEvent(element, eventInit) {
	const { FocusEvent } = getWindow(element);
	const event = new FocusEvent("blur", eventInit);
	const defaultAllowed = element.dispatchEvent(event);
	const bubbleInit = {
		...eventInit,
		bubbles: true
	};
	element.dispatchEvent(new FocusEvent("focusout", bubbleInit));
	return defaultAllowed;
}
/**
* Creates and dispatches a focus event.
* @example
* fireFocusEvent(document.getElementById("id"));
*/
function fireFocusEvent(element, eventInit) {
	const { FocusEvent } = getWindow(element);
	const event = new FocusEvent("focus", eventInit);
	const defaultAllowed = element.dispatchEvent(event);
	const bubbleInit = {
		...eventInit,
		bubbles: true
	};
	element.dispatchEvent(new FocusEvent("focusin", bubbleInit));
	return defaultAllowed;
}
/**
* Creates and dispatches a keyboard event.
* @example
* fireKeyboardEvent(document.getElementById("id"), "keydown", {
*   key: "ArrowDown",
*   shiftKey: true,
* });
*/
function fireKeyboardEvent(element, type, eventInit) {
	const { KeyboardEvent } = getWindow(element);
	const event = new KeyboardEvent(type, eventInit);
	return element.dispatchEvent(event);
}
const resetAttributes = {
	width: true,
	height: true,
	pressure: true,
	tangentialPressure: true,
	tiltX: true,
	tiltY: true,
	twist: true,
	altitudeAngle: true,
	azimuthAngle: true,
	isPrimary: true,
	coalescedEvents: true,
	predictedEvents: true,
	persistentDeviceId: true
};
function getClickEventInit(view, eventInit) {
	const init = eventInit ?? {};
	const WindowConstructor = view.Window;
	const eventView = typeof WindowConstructor === "function" && view instanceof WindowConstructor ? view : null;
	return new Proxy({}, { get(_target, key) {
		if (key === "view") return eventView;
		if (key === "composed") return true;
		if (key === "pointerId") return init.pointerId ?? -1;
		if (key === "pointerType") return init.pointerType ?? "";
		if (hasOwnProperty(resetAttributes, key)) return void 0;
		return Reflect.get(init, key);
	} });
}
/**
* Creates and dispatches a click event.
*
* The event is a `PointerEvent` built by the window that owns the element, the
* way browsers dispatch it, falling back to a `MouseEvent` where that window
* has no `PointerEvent`. Its `view` is that window when it is a genuine
* `Window`, or `null` otherwise, and it is composed. It reports no pointer
* behind the click unless the caller passes one, and reports every other
* pointer attribute at its default value, the way a click always does.
* @example
* fireClickEvent(document.getElementById("id"));
*/
function fireClickEvent(element, eventInit) {
	const view = getWindow(element);
	const event = new (view.PointerEvent ?? view.MouseEvent)("click", getClickEventInit(view, eventInit));
	return element.dispatchEvent(event);
}
/**
* Checks whether the focus/blur event is happening from/to outside of the
* container element.
* @example
* const element = document.getElementById("id");
* element.addEventListener("blur", (event) => {
*   if (isFocusEventOutside(event)) {
*     // ...
*   }
* });
*/
function isFocusEventOutside(event, container) {
	const containerElement = container || event.currentTarget;
	const relatedTarget = event.relatedTarget;
	return !isNode(relatedTarget) || !contains(containerElement, relatedTarget);
}
/**
* Returns the `inputType` property of the event, if available.
*/
function getInputType(event) {
	const nativeEvent = "nativeEvent" in event ? event.nativeEvent : event;
	if (!nativeEvent) return;
	if (!("inputType" in nativeEvent)) return;
	if (typeof nativeEvent.inputType !== "string") return;
	return nativeEvent.inputType;
}
/**
* Checks whether the event is an input event.
*/
function isInputEvent(event) {
	return event.type === "input";
}
/**
* Runs a callback on the next animation frame, but before a certain event.
*/
function queueBeforeEvent(element, type, callback, timeout) {
	const createTimer = (callback) => {
		if (timeout) {
			const timerId = setTimeout(callback, timeout);
			return () => clearTimeout(timerId);
		}
		const timerId = requestAnimationFrame(callback);
		return () => cancelAnimationFrame(timerId);
	};
	const cancelTimer = createTimer(() => {
		element.removeEventListener(type, callSync, true);
		callback();
	});
	const callSync = () => {
		cancelTimer();
		callback();
	};
	element.addEventListener(type, callSync, {
		once: true,
		capture: true
	});
	return () => {
		cancelTimer();
		element.removeEventListener(type, callSync, true);
	};
}
/**
* Adds a global event listener, including on child frames.
*/
function addGlobalEventListener(type, listener, options, scope = window) {
	const children = [];
	try {
		scope.document.addEventListener(type, listener, options);
		for (const frame of Array.from(scope.frames)) children.push(addGlobalEventListener(type, listener, options, frame));
	} catch {}
	const removeEventListener = () => {
		try {
			scope.document.removeEventListener(type, listener, options);
		} catch {}
		for (const remove of children) remove();
	};
	return removeEventListener;
}
//#endregion
//#region src/focus.ts
/**
* Focus management helpers for focusable and tabbable elements.
* @module Focus utilities
*/
const selector = "input:not([type='hidden']):not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], button:not([disabled]), [tabindex], summary, iframe, object, embed, area[href], audio[controls], video[controls], [contenteditable]:not([contenteditable='false'])";
function hasNegativeTabIndex(element) {
	return Number.parseInt(element.getAttribute("tabindex") || "0", 10) < 0;
}
/**
* Checks whether `element` is focusable or not.
* @example
* isFocusable(document.querySelector("input")); // true
* isFocusable(document.querySelector("input[tabindex='-1']")); // true
* isFocusable(document.querySelector("input[hidden]")); // false
* isFocusable(document.querySelector("input:disabled")); // false
*/
function isFocusable(element) {
	if (!element.matches(selector)) return false;
	if (!isVisible(element)) return false;
	if (element.closest("[inert]")) return false;
	return true;
}
/**
* Checks whether `element` is tabbable or not.
* @example
* isTabbable(document.querySelector("input")); // true
* isTabbable(document.querySelector("input[tabindex='-1']")); // false
* isTabbable(document.querySelector("input[hidden]")); // false
* isTabbable(document.querySelector("input:disabled")); // false
*/
function isTabbable(element) {
	if (!isFocusable(element)) return false;
	if (hasNegativeTabIndex(element)) return false;
	if (!("form" in element)) return true;
	if (!element.form) return true;
	if (element.checked) return true;
	if (element.type !== "radio") return true;
	const radioGroup = element.form.elements.namedItem(element.name);
	if (!radioGroup) return true;
	if (!("length" in radioGroup)) return true;
	const activeElement = getActiveElement(element);
	if (!activeElement) return true;
	if (activeElement === element) return true;
	if (!("form" in activeElement)) return true;
	if (activeElement.form !== element.form) return true;
	if (!("name" in activeElement)) return true;
	if (activeElement.name !== element.name) return true;
	return false;
}
/**
* Returns all the focusable elements in `container`.
*/
function getAllFocusableIn(container, includeContainer) {
	const elements = Array.from(container.querySelectorAll(selector));
	if (includeContainer) elements.unshift(container);
	const focusableElements = elements.filter(isFocusable);
	focusableElements.forEach((element, i) => {
		if (!isFrame(element)) return;
		const frameBody = element.contentDocument?.body;
		if (!frameBody) return;
		focusableElements.splice(i, 1, ...getAllFocusableIn(frameBody));
	});
	return focusableElements;
}
/**
* Returns all the focusable elements in the document.
*/
function getAllFocusable(includeBody) {
	return getAllFocusableIn(document.body, includeBody);
}
/**
* Returns the first focusable element in `container`.
*/
function getFirstFocusableIn(container, includeContainer) {
	const [first] = getAllFocusableIn(container, includeContainer);
	return first || null;
}
/**
* Returns the first focusable element in the document.
*/
function getFirstFocusable(includeBody) {
	return getFirstFocusableIn(document.body, includeBody);
}
/**
* Returns all the tabbable elements in `container`, including the container
* itself.
*/
function getAllTabbableIn(container, includeContainer, fallbackToFocusable) {
	const tabbableElements = Array.from(container.querySelectorAll(selector)).filter(isTabbable);
	if (includeContainer && isTabbable(container)) tabbableElements.unshift(container);
	tabbableElements.forEach((element, i) => {
		if (!isFrame(element)) return;
		const frameBody = element.contentDocument?.body;
		if (!frameBody) return;
		const allFrameTabbable = getAllTabbableIn(frameBody, false, fallbackToFocusable);
		tabbableElements.splice(i, 1, ...allFrameTabbable);
	});
	if (!tabbableElements.length && fallbackToFocusable) return getAllFocusableIn(container);
	return tabbableElements;
}
/**
* Returns all the tabbable elements in the document.
*/
function getAllTabbable(fallbackToFocusable) {
	return getAllTabbableIn(document.body, false, fallbackToFocusable);
}
/**
* Returns the first tabbable element in `container`, including the container
* itself if it's tabbable.
*/
function getFirstTabbableIn(container, includeContainer, fallbackToFocusable) {
	if (includeContainer && isTabbable(container)) {
		if (!isFrame(container)) return container;
		const containerFrameBody = container.contentDocument?.body;
		if (!containerFrameBody) return container;
		const frameTabbable = getFirstTabbableIn(containerFrameBody, false, fallbackToFocusable);
		if (frameTabbable) return frameTabbable;
	}
	const elements = container.querySelectorAll(selector);
	for (const element of elements) {
		if (!isTabbable(element)) continue;
		if (isFrame(element)) {
			const frameBody = element.contentDocument?.body;
			if (!frameBody) return element;
			const frameTabbable = getFirstTabbableIn(frameBody, false, fallbackToFocusable);
			if (frameTabbable) return frameTabbable;
			continue;
		}
		return element;
	}
	if (fallbackToFocusable) return getFirstFocusableIn(container);
	return null;
}
/**
* Returns the first tabbable element in the document.
*/
function getFirstTabbable(fallbackToFocusable) {
	return getFirstTabbableIn(document.body, false, fallbackToFocusable);
}
/**
* Returns the last tabbable element in `container`, including the container
* itself if it's tabbable.
*/
function getLastTabbableIn(container, includeContainer, fallbackToFocusable) {
	const allTabbable = getAllTabbableIn(container, includeContainer, fallbackToFocusable);
	return allTabbable[allTabbable.length - 1] || null;
}
/**
* Returns the last tabbable element in the document.
*/
function getLastTabbable(fallbackToFocusable) {
	return getLastTabbableIn(document.body, false, fallbackToFocusable);
}
function getTabbableInDirection({ container, includeContainer, reverse, fallbackToEdge, fallbackToFocusable }) {
	const activeElement = getActiveElement(container);
	const allFocusable = getAllFocusableIn(container, includeContainer);
	if (reverse) allFocusable.reverse();
	const activeIndex = allFocusable.indexOf(activeElement);
	const candidates = allFocusable.slice(activeIndex + 1);
	return candidates.find(isTabbable) || (fallbackToEdge ? allFocusable.find(isTabbable) : null) || (fallbackToFocusable ? candidates[0] : null) || null;
}
/**
* Returns the next tabbable element in `container`.
*/
function getNextTabbableIn(container, includeContainer, fallbackToFirst, fallbackToFocusable) {
	return getTabbableInDirection({
		container,
		includeContainer,
		fallbackToEdge: fallbackToFirst,
		fallbackToFocusable
	});
}
/**
* Returns the next tabbable element in the document.
*/
function getNextTabbable(fallbackToFirst, fallbackToFocusable) {
	return getNextTabbableIn(document.body, false, fallbackToFirst, fallbackToFocusable);
}
/**
* Returns the previous tabbable element in `container`.
*
*/
function getPreviousTabbableIn(container, includeContainer, fallbackToLast, fallbackToFocusable) {
	return getTabbableInDirection({
		container,
		includeContainer,
		reverse: true,
		fallbackToEdge: fallbackToLast,
		fallbackToFocusable
	});
}
/**
* Returns the previous tabbable element in the document.
*/
function getPreviousTabbable(fallbackToLast, fallbackToFocusable) {
	return getPreviousTabbableIn(document.body, false, fallbackToLast, fallbackToFocusable);
}
/**
* Returns the closest focusable element.
*/
function getClosestFocusable(element) {
	while (element && !isFocusable(element)) element = element.parentElement?.closest(selector) ?? null;
	return element || null;
}
/**
* Checks if `element` has focus. Elements that are referenced by
* `aria-activedescendant` are also considered.
* @example
* hasFocus(document.getElementById("id"));
*/
function hasFocus(element) {
	const activeElement = getActiveElement(element);
	if (!activeElement) return false;
	if (activeElement === element) return true;
	const activeDescendant = activeElement.getAttribute("aria-activedescendant");
	if (!activeDescendant) return false;
	return activeDescendant === element.id;
}
/**
* Checks if `element` has focus within. Elements that are referenced by
* `aria-activedescendant` are also considered.
* @example
* hasFocusWithin(document.getElementById("id"));
*/
function hasFocusWithin(element) {
	const activeElement = getActiveElement(element);
	if (!activeElement) return false;
	if (contains(element, activeElement)) return true;
	const activeDescendant = activeElement.getAttribute("aria-activedescendant");
	if (!activeDescendant) return false;
	if (!("id" in element)) return false;
	if (activeDescendant === element.id) return true;
	return !!element.querySelector(`#${CSS.escape(activeDescendant)}`);
}
/**
* Focus on an element only if it's not already focused.
*/
function focusIfNeeded(element) {
	if (!hasFocusWithin(element) && isFocusable(element)) element.focus();
}
/**
* Disable focus on `element`.
*/
function disableFocus(element) {
	const currentTabindex = element.getAttribute("tabindex") ?? "";
	element.setAttribute("data-tabindex", currentTabindex);
	element.setAttribute("tabindex", "-1");
}
/**
* Makes elements inside container not tabbable.
*/
function disableFocusIn(container, includeContainer) {
	const tabbableElements = getAllTabbableIn(container, includeContainer);
	for (const element of tabbableElements) disableFocus(element);
}
/**
* Restores tabbable elements inside container that were affected by
* disableFocusIn.
*/
function restoreFocusIn(container) {
	const elements = container.querySelectorAll("[data-tabindex]");
	const restoreTabIndex = (element) => {
		const tabindex = element.getAttribute("data-tabindex");
		element.removeAttribute("data-tabindex");
		if (tabindex) element.setAttribute("tabindex", tabindex);
		else element.removeAttribute("tabindex");
	};
	if (container.hasAttribute("data-tabindex")) restoreTabIndex(container);
	for (const element of elements) restoreTabIndex(element);
}
/**
* Focus on element and scroll into view.
*/
function focusIntoView(element, options) {
	if (!("scrollIntoView" in element)) element.focus();
	else {
		element.focus({ preventScroll: true });
		element.scrollIntoView({
			block: "nearest",
			inline: "nearest",
			...options
		});
	}
}
//#endregion
//#region src/undo.ts
function createUndoCallback(callback) {
	return async () => {
		const redo = await callback?.();
		return createUndoCallback(async () => {
			await redo?.();
			return callback;
		});
	};
}
/**
* Shared undo manager instance.
*/
const UndoManager = createUndoManager();
/**
* Creates an undo manager with undo and redo stacks.
*/
function createUndoManager({ limit = 100 } = {}) {
	const undoStack = [];
	let redoStack = [];
	let currentGroup = null;
	const canUndo = () => undoStack.length > 0;
	const canRedo = () => redoStack.length > 0;
	const undo = async () => {
		if (!canUndo()) return;
		currentGroup = null;
		redoStack.push(await undoStack.pop()?.());
	};
	const redo = async () => {
		if (!canRedo()) return;
		currentGroup = null;
		undoStack.push(await redoStack.pop()?.());
	};
	const execute = async (callback, group) => {
		if (!callback) return;
		const sameGroup = group === currentGroup;
		currentGroup = group ?? null;
		const nextIndex = sameGroup ? Math.max(0, undoStack.length - 1) : undoStack.length;
		const undoCallback = await callback();
		if (!undoCallback) return;
		redoStack = [];
		const currentUndo = undoStack[nextIndex];
		undoStack[nextIndex] = createUndoCallback(async () => {
			await undoCallback?.();
			const currentRedo = await currentUndo?.();
			return async () => {
				await currentRedo?.();
				await callback?.();
			};
		});
		while (undoStack.length > limit) undoStack.shift();
	};
	return {
		canUndo,
		canRedo,
		undo,
		redo,
		execute
	};
}
//#endregion


//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.dom.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $4: () => (/* binding */ getParentNode),
/* harmony export */   CP: () => (/* binding */ getNodeScroll),
/* harmony export */   L9: () => (/* binding */ getComputedStyle),
/* harmony export */   Lv: () => (/* binding */ isTableElement),
/* harmony export */   Tc: () => (/* binding */ isWebKit),
/* harmony export */   Tf: () => (/* binding */ isTopLayer),
/* harmony export */   ZU: () => (/* binding */ isOverflowElement),
/* harmony export */   _m: () => (/* binding */ getFrameElement),
/* harmony export */   ep: () => (/* binding */ getDocumentElement),
/* harmony export */   eu: () => (/* binding */ isLastTraversableNode),
/* harmony export */   gJ: () => (/* binding */ getContainingBlock),
/* harmony export */   mq: () => (/* binding */ getNodeName),
/* harmony export */   sQ: () => (/* binding */ isContainingBlock),
/* harmony export */   sb: () => (/* binding */ isHTMLElement),
/* harmony export */   v9: () => (/* binding */ getOverflowAncestors),
/* harmony export */   vq: () => (/* binding */ isElement),
/* harmony export */   zk: () => (/* binding */ getWindow)
/* harmony export */ });
/* unused harmony exports getNearestOverflowAncestor, isNode, isShadowRoot */
function hasWindow() {
  return typeof window !== 'undefined';
}
function getNodeName(node) {
  if (isNode(node)) {
    return (node.nodeName || '').toLowerCase();
  }
  // Mocked nodes in testing environments may not be instances of Node. By
  // returning `#document` an infinite loop won't occur.
  // https://github.com/floating-ui/floating-ui/issues/2317
  return '#document';
}
function getWindow(node) {
  var _node$ownerDocument;
  return (node == null || (_node$ownerDocument = node.ownerDocument) == null ? void 0 : _node$ownerDocument.defaultView) || window;
}
function getDocumentElement(node) {
  var _ref;
  return (_ref = (isNode(node) ? node.ownerDocument : node.document) || window.document) == null ? void 0 : _ref.documentElement;
}
function isNode(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Node || value instanceof getWindow(value).Node;
}
function isElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Element || value instanceof getWindow(value).Element;
}
function isHTMLElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof HTMLElement || value instanceof getWindow(value).HTMLElement;
}
function isShadowRoot(value) {
  if (!hasWindow() || typeof ShadowRoot === 'undefined') {
    return false;
  }
  return value instanceof ShadowRoot || value instanceof getWindow(value).ShadowRoot;
}
function isOverflowElement(element) {
  const {
    overflow,
    overflowX,
    overflowY,
    display
  } = getComputedStyle(element);
  return /auto|scroll|overlay|hidden|clip/.test(overflow + overflowY + overflowX) && display !== 'inline' && display !== 'contents';
}
function isTableElement(element) {
  return /^(table|td|th)$/.test(getNodeName(element));
}
function isTopLayer(element) {
  try {
    if (element.matches(':popover-open')) {
      return true;
    }
  } catch (_e) {
    // no-op
  }
  try {
    return element.matches(':modal');
  } catch (_e) {
    return false;
  }
}
const willChangeRe = /transform|translate|scale|rotate|perspective|filter/;
const containRe = /paint|layout|strict|content/;
const isNotNone = value => !!value && value !== 'none';
let isWebKitValue;
function isContainingBlock(elementOrCss) {
  const css = isElement(elementOrCss) ? getComputedStyle(elementOrCss) : elementOrCss;

  // https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_block#identifying_the_containing_block
  // https://drafts.csswg.org/css-transforms-2/#individual-transforms
  return isNotNone(css.transform) || isNotNone(css.translate) || isNotNone(css.scale) || isNotNone(css.rotate) || isNotNone(css.perspective) || !isWebKit() && (isNotNone(css.backdropFilter) || isNotNone(css.filter)) || willChangeRe.test(css.willChange || '') || containRe.test(css.contain || '');
}
function getContainingBlock(element) {
  let currentNode = getParentNode(element);
  while (isHTMLElement(currentNode) && !isLastTraversableNode(currentNode)) {
    if (isContainingBlock(currentNode)) {
      return currentNode;
    } else if (isTopLayer(currentNode)) {
      return null;
    }
    currentNode = getParentNode(currentNode);
  }
  return null;
}
function isWebKit() {
  if (isWebKitValue == null) {
    isWebKitValue = typeof CSS !== 'undefined' && CSS.supports && CSS.supports('-webkit-backdrop-filter', 'none');
  }
  return isWebKitValue;
}
function isLastTraversableNode(node) {
  return /^(html|body|#document)$/.test(getNodeName(node));
}
function getComputedStyle(element) {
  return getWindow(element).getComputedStyle(element);
}
function getNodeScroll(element) {
  if (isElement(element)) {
    return {
      scrollLeft: element.scrollLeft,
      scrollTop: element.scrollTop
    };
  }
  return {
    scrollLeft: element.scrollX,
    scrollTop: element.scrollY
  };
}
function getParentNode(node) {
  if (getNodeName(node) === 'html') {
    return node;
  }
  const result =
  // Step into the shadow DOM of the parent of a slotted node.
  node.assignedSlot ||
  // DOM Element detected.
  node.parentNode ||
  // ShadowRoot detected.
  isShadowRoot(node) && node.host ||
  // Fallback.
  getDocumentElement(node);
  return isShadowRoot(result) ? result.host : result;
}
function getNearestOverflowAncestor(node) {
  const parentNode = getParentNode(node);
  if (isLastTraversableNode(parentNode)) {
    return (node.ownerDocument || node).body;
  }
  if (isHTMLElement(parentNode) && isOverflowElement(parentNode)) {
    return parentNode;
  }
  return getNearestOverflowAncestor(parentNode);
}
function getOverflowAncestors(node, list, traverseIframes) {
  var _node$ownerDocument2;
  if (list === void 0) {
    list = [];
  }
  if (traverseIframes === void 0) {
    traverseIframes = true;
  }
  const scrollableAncestor = getNearestOverflowAncestor(node);
  const isBody = scrollableAncestor === ((_node$ownerDocument2 = node.ownerDocument) == null ? void 0 : _node$ownerDocument2.body);
  const win = getWindow(scrollableAncestor);
  if (isBody) {
    const frameElement = getFrameElement(win);
    return list.concat(win, win.visualViewport || [], isOverflowElement(scrollableAncestor) ? scrollableAncestor : [], frameElement && traverseIframes ? getOverflowAncestors(frameElement) : []);
  } else {
    return list.concat(scrollableAncestor, getOverflowAncestors(scrollableAncestor, [], traverseIframes));
  }
}
function getFrameElement(win) {
  return win.parent && Object.getPrototypeOf(win.parent) ? win.frameElement : null;
}




/***/ }),

/***/ "../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   B1: () => (/* binding */ rectToClientRect),
/* harmony export */   C0: () => (/* binding */ getSide),
/* harmony export */   Dz: () => (/* binding */ getAlignmentAxis),
/* harmony export */   Jx: () => (/* binding */ createCoords),
/* harmony export */   LI: () => (/* binding */ round),
/* harmony export */   PG: () => (/* binding */ getOppositeAxis),
/* harmony export */   RI: () => (/* binding */ floor),
/* harmony export */   Sg: () => (/* binding */ getAlignment),
/* harmony export */   T9: () => (/* binding */ max),
/* harmony export */   TV: () => (/* binding */ getSideAxis),
/* harmony export */   WJ: () => (/* binding */ getExpandedPlacements),
/* harmony export */   _3: () => (/* binding */ evaluate),
/* harmony export */   bV: () => (/* binding */ getOppositePlacement),
/* harmony export */   jk: () => (/* binding */ min),
/* harmony export */   lP: () => (/* binding */ getOppositeAxisPlacements),
/* harmony export */   nI: () => (/* binding */ getPaddingObject),
/* harmony export */   qE: () => (/* binding */ clamp),
/* harmony export */   sq: () => (/* binding */ getAxisLength),
/* harmony export */   w7: () => (/* binding */ getAlignmentSides)
/* harmony export */ });
/* unused harmony exports alignments, expandPaddingObject, getOppositeAlignmentPlacement, placements, sides */
/**
 * Custom positioning reference element.
 * @see https://floating-ui.com/docs/virtual-elements
 */

const sides = (/* unused pure expression or super */ null && (['top', 'right', 'bottom', 'left']));
const alignments = (/* unused pure expression or super */ null && (['start', 'end']));
const placements = /*#__PURE__*/(/* unused pure expression or super */ null && (sides.reduce((acc, side) => acc.concat(side, side + "-" + alignments[0], side + "-" + alignments[1]), [])));
const min = Math.min;
const max = Math.max;
const round = Math.round;
const floor = Math.floor;
const createCoords = v => ({
  x: v,
  y: v
});
const oppositeSideMap = {
  left: 'right',
  right: 'left',
  bottom: 'top',
  top: 'bottom'
};
function clamp(start, value, end) {
  return max(start, min(value, end));
}
function evaluate(value, param) {
  return typeof value === 'function' ? value(param) : value;
}
function getSide(placement) {
  return placement.split('-')[0];
}
function getAlignment(placement) {
  return placement.split('-')[1];
}
function getOppositeAxis(axis) {
  return axis === 'x' ? 'y' : 'x';
}
function getAxisLength(axis) {
  return axis === 'y' ? 'height' : 'width';
}
function getSideAxis(placement) {
  const firstChar = placement[0];
  return firstChar === 't' || firstChar === 'b' ? 'y' : 'x';
}
function getAlignmentAxis(placement) {
  return getOppositeAxis(getSideAxis(placement));
}
function getAlignmentSides(placement, rects, rtl) {
  if (rtl === void 0) {
    rtl = false;
  }
  const alignment = getAlignment(placement);
  const alignmentAxis = getAlignmentAxis(placement);
  const length = getAxisLength(alignmentAxis);
  let mainAlignmentSide = alignmentAxis === 'x' ? alignment === (rtl ? 'end' : 'start') ? 'right' : 'left' : alignment === 'start' ? 'bottom' : 'top';
  if (rects.reference[length] > rects.floating[length]) {
    mainAlignmentSide = getOppositePlacement(mainAlignmentSide);
  }
  return [mainAlignmentSide, getOppositePlacement(mainAlignmentSide)];
}
function getExpandedPlacements(placement) {
  const oppositePlacement = getOppositePlacement(placement);
  return [getOppositeAlignmentPlacement(placement), oppositePlacement, getOppositeAlignmentPlacement(oppositePlacement)];
}
function getOppositeAlignmentPlacement(placement) {
  return placement.includes('start') ? placement.replace('start', 'end') : placement.replace('end', 'start');
}
const lrPlacement = ['left', 'right'];
const rlPlacement = ['right', 'left'];
const tbPlacement = ['top', 'bottom'];
const btPlacement = ['bottom', 'top'];
function getSideList(side, isStart, rtl) {
  switch (side) {
    case 'top':
    case 'bottom':
      if (rtl) return isStart ? rlPlacement : lrPlacement;
      return isStart ? lrPlacement : rlPlacement;
    case 'left':
    case 'right':
      return isStart ? tbPlacement : btPlacement;
    default:
      return [];
  }
}
function getOppositeAxisPlacements(placement, flipAlignment, direction, rtl) {
  const alignment = getAlignment(placement);
  let list = getSideList(getSide(placement), direction === 'start', rtl);
  if (alignment) {
    list = list.map(side => side + "-" + alignment);
    if (flipAlignment) {
      list = list.concat(list.map(getOppositeAlignmentPlacement));
    }
  }
  return list;
}
function getOppositePlacement(placement) {
  const side = getSide(placement);
  return oppositeSideMap[side] + placement.slice(side.length);
}
function expandPaddingObject(padding) {
  var _padding$top, _padding$right, _padding$bottom, _padding$left;
  return {
    top: (_padding$top = padding.top) != null ? _padding$top : 0,
    right: (_padding$right = padding.right) != null ? _padding$right : 0,
    bottom: (_padding$bottom = padding.bottom) != null ? _padding$bottom : 0,
    left: (_padding$left = padding.left) != null ? _padding$left : 0
  };
}
function getPaddingObject(padding) {
  return typeof padding !== 'number' ? expandPaddingObject(padding) : {
    top: padding,
    right: padding,
    bottom: padding,
    left: padding
  };
}
function rectToClientRect(rect) {
  const {
    x,
    y,
    width,
    height
  } = rect;
  return {
    width,
    height,
    top: y,
    left: x,
    right: x + width,
    bottom: y + height,
    x,
    y
  };
}




/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ay: () => (/* binding */ button_default)
/* harmony export */ });
/* unused harmony exports Button, UnforwardedButton */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var _tooltip__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/tooltip/index.js");
/* harmony import */ var _icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/icon/index.js");
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
/* harmony import */ var _popover_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/popover/utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");









const disabledEventsOnDisabledButton = ["onMouseDown", "onClick"];
function useDeprecatedProps({
  __experimentalIsFocusable,
  isDefault,
  isPrimary,
  isSecondary,
  isTertiary,
  isLink,
  isPressed,
  isSmall,
  size,
  variant,
  describedBy,
  ...otherProps
}) {
  let computedSize = size;
  let computedVariant = variant;
  const newProps = {
    accessibleWhenDisabled: __experimentalIsFocusable,
    // @todo Mark `isPressed` as deprecated
    "aria-pressed": isPressed,
    description: describedBy
  };
  if (isSmall) {
    var _computedSize;
    (_computedSize = computedSize) !== null && _computedSize !== void 0 ? _computedSize : computedSize = "small";
  }
  if (isPrimary) {
    var _computedVariant;
    (_computedVariant = computedVariant) !== null && _computedVariant !== void 0 ? _computedVariant : computedVariant = "primary";
  }
  if (isTertiary) {
    var _computedVariant2;
    (_computedVariant2 = computedVariant) !== null && _computedVariant2 !== void 0 ? _computedVariant2 : computedVariant = "tertiary";
  }
  if (isSecondary) {
    var _computedVariant3;
    (_computedVariant3 = computedVariant) !== null && _computedVariant3 !== void 0 ? _computedVariant3 : computedVariant = "secondary";
  }
  if (isDefault) {
    var _computedVariant4;
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)("wp.components.Button `isDefault` prop", {
      since: "5.4",
      alternative: 'variant="secondary"'
    });
    (_computedVariant4 = computedVariant) !== null && _computedVariant4 !== void 0 ? _computedVariant4 : computedVariant = "secondary";
  }
  if (isLink) {
    var _computedVariant5;
    (_computedVariant5 = computedVariant) !== null && _computedVariant5 !== void 0 ? _computedVariant5 : computedVariant = "link";
  }
  return {
    ...newProps,
    ...otherProps,
    size: computedSize,
    variant: computedVariant
  };
}
function UnforwardedButton(props, ref) {
  const {
    __next40pxDefaultSize,
    accessibleWhenDisabled,
    isBusy,
    isDestructive,
    className,
    disabled,
    icon,
    iconPosition = "left",
    iconSize,
    showTooltip,
    tooltipPosition,
    shortcut,
    label,
    children,
    size = "default",
    text,
    variant,
    description,
    ...buttonOrAnchorProps
  } = useDeprecatedProps(props);
  const {
    href,
    target,
    "aria-checked": ariaChecked,
    "aria-pressed": ariaPressed,
    "aria-selected": ariaSelected,
    ...additionalProps
  } = "href" in buttonOrAnchorProps ? buttonOrAnchorProps : {
    href: void 0,
    target: void 0,
    ...buttonOrAnchorProps
  };
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(Button, "components-button__description");
  const hasChildren = "string" === typeof children && !!children || Array.isArray(children) && children?.[0] && children[0] !== null && // Tooltip should not considered as a child
  children?.[0]?.props?.className !== "components-tooltip";
  const truthyAriaPressedValues = [true, "true", "mixed"];
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)("components-button", className, {
    "is-next-40px-default-size": __next40pxDefaultSize,
    "is-secondary": variant === "secondary",
    "is-primary": variant === "primary",
    "is-small": size === "small",
    "is-compact": size === "compact",
    "is-tertiary": variant === "tertiary",
    "is-pressed": truthyAriaPressedValues.includes(ariaPressed),
    "is-pressed-mixed": ariaPressed === "mixed",
    "is-busy": isBusy,
    "is-link": variant === "link",
    "is-destructive": isDestructive,
    "has-text": !!icon && (hasChildren || text),
    "has-icon": !!icon,
    "has-icon-right": iconPosition === "right"
  });
  const trulyDisabled = disabled && !accessibleWhenDisabled;
  const Tag = href !== void 0 && !disabled ? "a" : "button";
  const buttonProps = Tag === "button" ? {
    type: "button",
    disabled: trulyDisabled,
    "aria-checked": ariaChecked,
    "aria-pressed": ariaPressed,
    "aria-selected": ariaSelected
  } : {};
  const anchorProps = Tag === "a" ? {
    href,
    target
  } : {};
  const disableEventProps = {};
  if (disabled && accessibleWhenDisabled) {
    buttonProps["aria-disabled"] = true;
    anchorProps["aria-disabled"] = true;
    for (const disabledEvent of disabledEventsOnDisabledButton) {
      disableEventProps[disabledEvent] = (event) => {
        if (event) {
          event.stopPropagation();
          event.preventDefault();
        }
      };
    }
  }
  const shouldShowTooltip = !trulyDisabled && // An explicit tooltip is passed or...
  (showTooltip && !!label || // There's a shortcut or...
  !!shortcut || // There's a label and...
  !!label && // The children are empty and...
  !children?.length && // The tooltip is not explicitly disabled.
  false !== showTooltip);
  const descriptionId = description ? instanceId : void 0;
  const describedById = additionalProps["aria-describedby"] || descriptionId;
  const commonProps = {
    className: classes,
    "aria-label": additionalProps["aria-label"] || label,
    "aria-describedby": describedById,
    ref
  };
  const elementChildren = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    children: [icon && iconPosition === "left" && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_icon__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      icon,
      size: iconSize
    }), text && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
      children: text
    }), children, icon && iconPosition === "right" && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_icon__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      icon,
      size: iconSize
    })]
  });
  const element = Tag === "a" ? /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
    ...anchorProps,
    ...additionalProps,
    ...disableEventProps,
    ...commonProps,
    children: elementChildren
  }) : /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
    ...buttonProps,
    ...additionalProps,
    ...disableEventProps,
    ...commonProps,
    children: elementChildren
  });
  const tooltipProps = shouldShowTooltip ? {
    text: children?.length && description ? description : label,
    shortcut,
    placement: tooltipPosition && // Convert legacy `position` values to be used with the new `placement` prop
    (0,_popover_utils__WEBPACK_IMPORTED_MODULE_5__/* .positionToPlacement */ .YK)(tooltipPosition)
  } : {};
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_tooltip__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      ...tooltipProps,
      children: element
    }), description && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_visually_hidden__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
      children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        id: descriptionId,
        children: description
      })
    })]
  });
}
const Button = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_8__.forwardRef)(UnforwardedButton);
var button_default = Button;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ icon_default)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.57._32fe7e38935c407cea8bd9e57e6c56c9/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.57._32fe7e38935c407cea8bd9e57e6c56c9/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/dashicon/index.js

function Dashicon({
  icon,
  className,
  size = 20,
  style = {},
  ...extraProps
}) {
  const iconClass = ["dashicon", "dashicons", "dashicons-" + icon, className].filter(Boolean).join(" ");
  const sizeStyles = (
    // using `!=` to catch both 20 and "20"
    // eslint-disable-next-line eqeqeq
    20 != size ? {
      fontSize: `${size}px`,
      width: `${size}px`,
      height: `${size}px`
    } : {}
  );
  const styles = {
    ...sizeStyles,
    ...style
  };
  return /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
    className: iconClass,
    style: styles,
    ...extraProps
  });
}
var dashicon_default = Dashicon;

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/icon/index.js




function Icon({
  icon = null,
  size = "string" === typeof icon ? 20 : 24,
  ...additionalProps
}) {
  if ("string" === typeof icon) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(dashicon_default, {
      icon,
      size,
      ...additionalProps
    });
  }
  if ((0,react.isValidElement)(icon) && dashicon_default === icon.type) {
    return (0,react.cloneElement)(icon, {
      ...additionalProps
    });
  }
  if ("function" === typeof icon) {
    return (0,react.createElement)(icon, {
      size,
      ...additionalProps
    });
  }
  if (icon && (icon.type === "svg" || icon.type === svg/* SVG */.t4)) {
    const appliedProps = {
      ...icon.props,
      width: size,
      height: size,
      ...additionalProps
    };
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, {
      ...appliedProps
    });
  }
  if ((0,react.isValidElement)(icon)) {
    return (0,react.cloneElement)(icon, {
      // @ts-ignore Just forwarding the size prop along
      size,
      width: size,
      height: size,
      ...additionalProps
    });
  }
  return icon;
}
var icon_default = Icon;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/popover/utils.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Vn: () => (/* binding */ placementToMotionAnimationProps),
/* harmony export */   WS: () => (/* binding */ computePopoverPosition),
/* harmony export */   YK: () => (/* binding */ positionToPlacement),
/* harmony export */   _G: () => (/* binding */ getReferenceElement)
/* harmony export */ });
const POSITION_TO_PLACEMENT = {
  bottom: "bottom",
  top: "top",
  "middle left": "left",
  "middle right": "right",
  "bottom left": "bottom-end",
  "bottom center": "bottom",
  "bottom right": "bottom-start",
  "top left": "top-end",
  "top center": "top",
  "top right": "top-start",
  "middle left left": "left",
  "middle left right": "left",
  "middle left bottom": "left-end",
  "middle left top": "left-start",
  "middle right left": "right",
  "middle right right": "right",
  "middle right bottom": "right-end",
  "middle right top": "right-start",
  "bottom left left": "bottom-end",
  "bottom left right": "bottom-end",
  "bottom left bottom": "bottom-end",
  "bottom left top": "bottom-end",
  "bottom center left": "bottom",
  "bottom center right": "bottom",
  "bottom center bottom": "bottom",
  "bottom center top": "bottom",
  "bottom right left": "bottom-start",
  "bottom right right": "bottom-start",
  "bottom right bottom": "bottom-start",
  "bottom right top": "bottom-start",
  "top left left": "top-end",
  "top left right": "top-end",
  "top left bottom": "top-end",
  "top left top": "top-end",
  "top center left": "top",
  "top center right": "top",
  "top center bottom": "top",
  "top center top": "top",
  "top right left": "top-start",
  "top right right": "top-start",
  "top right bottom": "top-start",
  "top right top": "top-start",
  // `middle`/`middle center [corner?]` positions are associated to a fallback
  // `bottom` placement because there aren't any corresponding placement values.
  middle: "bottom",
  "middle center": "bottom",
  "middle center bottom": "bottom",
  "middle center left": "bottom",
  "middle center right": "bottom",
  "middle center top": "bottom"
};
const positionToPlacement = (position) => {
  var _POSITION_TO_PLACEMEN;
  return (_POSITION_TO_PLACEMEN = POSITION_TO_PLACEMENT[position]) !== null && _POSITION_TO_PLACEMEN !== void 0 ? _POSITION_TO_PLACEMEN : "bottom";
};
const PLACEMENT_TO_ANIMATION_ORIGIN = {
  top: {
    originX: 0.5,
    originY: 1
  },
  // open from bottom, center
  "top-start": {
    originX: 0,
    originY: 1
  },
  // open from bottom, left
  "top-end": {
    originX: 1,
    originY: 1
  },
  // open from bottom, right
  right: {
    originX: 0,
    originY: 0.5
  },
  // open from middle, left
  "right-start": {
    originX: 0,
    originY: 0
  },
  // open from top, left
  "right-end": {
    originX: 0,
    originY: 1
  },
  // open from bottom, left
  bottom: {
    originX: 0.5,
    originY: 0
  },
  // open from top, center
  "bottom-start": {
    originX: 0,
    originY: 0
  },
  // open from top, left
  "bottom-end": {
    originX: 1,
    originY: 0
  },
  // open from top, right
  left: {
    originX: 1,
    originY: 0.5
  },
  // open from middle, right
  "left-start": {
    originX: 1,
    originY: 0
  },
  // open from top, right
  "left-end": {
    originX: 1,
    originY: 1
  },
  // open from bottom, right
  overlay: {
    originX: 0.5,
    originY: 0.5
  }
  // open from center, center
};
const placementToMotionAnimationProps = (placement) => {
  const translateProp = placement.startsWith("top") || placement.startsWith("bottom") ? "translateY" : "translateX";
  const translateDirection = placement.startsWith("top") || placement.startsWith("left") ? 1 : -1;
  return {
    style: PLACEMENT_TO_ANIMATION_ORIGIN[placement],
    initial: {
      opacity: 0,
      scale: 0,
      [translateProp]: `${2 * translateDirection}em`
    },
    animate: {
      opacity: 1,
      scale: 1,
      [translateProp]: 0
    },
    transition: {
      duration: 0.1,
      ease: [0, 0, 0.2, 1]
    }
  };
};
function isTopBottom(anchorRef) {
  return !!anchorRef?.top;
}
function isRef(anchorRef) {
  return !!anchorRef?.current;
}
const getReferenceElement = ({
  anchor,
  anchorRef,
  anchorRect,
  getAnchorRect,
  fallbackReferenceElement
}) => {
  var _referenceElement;
  let referenceElement = null;
  if (anchor) {
    referenceElement = anchor;
  } else if (isTopBottom(anchorRef)) {
    referenceElement = {
      getBoundingClientRect() {
        const topRect = anchorRef.top.getBoundingClientRect();
        const bottomRect = anchorRef.bottom.getBoundingClientRect();
        return new window.DOMRect(topRect.x, topRect.y, topRect.width, bottomRect.bottom - topRect.top);
      }
    };
  } else if (isRef(anchorRef)) {
    referenceElement = anchorRef.current;
  } else if (anchorRef) {
    referenceElement = anchorRef;
  } else if (anchorRect) {
    referenceElement = {
      getBoundingClientRect() {
        return anchorRect;
      }
    };
  } else if (getAnchorRect) {
    referenceElement = {
      getBoundingClientRect() {
        var _rect$x, _rect$y, _rect$width, _rect$height;
        const rect = getAnchorRect(fallbackReferenceElement);
        return new window.DOMRect((_rect$x = rect.x) !== null && _rect$x !== void 0 ? _rect$x : rect.left, (_rect$y = rect.y) !== null && _rect$y !== void 0 ? _rect$y : rect.top, (_rect$width = rect.width) !== null && _rect$width !== void 0 ? _rect$width : rect.right - rect.left, (_rect$height = rect.height) !== null && _rect$height !== void 0 ? _rect$height : rect.bottom - rect.top);
      }
    };
  } else if (fallbackReferenceElement) {
    referenceElement = fallbackReferenceElement.parentElement;
  }
  return (_referenceElement = referenceElement) !== null && _referenceElement !== void 0 ? _referenceElement : null;
};
const computePopoverPosition = (c) => c === null || Number.isNaN(c) ? void 0 : Math.round(c);

//# sourceMappingURL=utils.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/shortcut/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ shortcut_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");

function Shortcut(props) {
  const {
    shortcut,
    className
  } = props;
  if (!shortcut) {
    return null;
  }
  let displayText;
  let ariaLabel;
  if (typeof shortcut === "string") {
    displayText = shortcut;
  }
  if (shortcut !== null && typeof shortcut === "object") {
    displayText = shortcut.display;
    ariaLabel = shortcut.ariaLabel;
  }
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
    className,
    "aria-label": ariaLabel,
    children: displayText
  });
}
var shortcut_default = Shortcut;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/tooltip/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ tooltip_default)
});

// UNUSED EXPORTS: TOOLTIP_DELAY, Tooltip

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-store.js
var disclosure_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-store.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-store@0.1.12_react@18.3.1/node_modules/@ariakit/react-store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/disclosure/disclosure-store.js
var disclosure_disclosure_store = __webpack_require__("../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/disclosure/disclosure-store.js");
;// ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/dialog/dialog-store.js

//#region src/dialog/dialog-store.ts
/**
* Creates a dialog store.
*/
function createDialogStore(props = {}) {
	return (0,disclosure_disclosure_store/* createDisclosureStore */.h)(props);
}
//#endregion


//# sourceMappingURL=dialog-store.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/dialog-store.js
"use client";



//#region src/dialog/dialog-store.ts
function useDialogStoreProps(store, update, props) {
	return (0,disclosure_store/* useDisclosureStoreProps */.W)(store, update, props);
}
/**
* Creates a dialog store to control the state of
* [Dialog](https://ariakit.com/components/dialog) components.
* @see https://ariakit.com/components/dialog
* @example
* ```jsx
* const dialog = useDialogStore();
*
* <button onClick={dialog.toggle}>Open dialog</button>
* <Dialog store={dialog}>Content</Dialog>
* ```
*/
function useDialogStore(props = {}) {
	const [store, update] = (0,dist/* useStore */.Pj)(createDialogStore, props);
	return useDialogStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=dialog-store.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js
var react_utils_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-utils@0.2.7_react@18.3.1/node_modules/@ariakit/react-utils/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/popover/popover-store.js
"use client";




//#region src/popover/popover-store.ts
function usePopoverStoreProps(store, update, props) {
	(0,react_utils_dist/* useUpdateEffect */.w5)(update, [props.popover, update]);
	(0,dist/* useStoreProps */.Tz)(store, props, "placement");
	return useDialogStoreProps(store, update, props);
}
/**
* Creates a popover store to control the state of
* [Popover](https://ariakit.com/components/popover) components.
* @see https://ariakit.com/components/popover
* @example
* ```jsx
* const popover = usePopoverStore();
* <PopoverDisclosure store={popover}>Disclosure</PopoverDisclosure>
* <Popover store={popover}>Popover</Popover>
* ```
*/
function usePopoverStore(props = {}) {
	const [store, update] = useStore(Core.createPopoverStore, props);
	return usePopoverStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=popover-store.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/hovercard/hovercard-store.js
"use client";



//#region src/hovercard/hovercard-store.ts
function useHovercardStoreProps(store, update, props) {
	(0,dist/* useStoreProps */.Tz)(store, props, "timeout");
	(0,dist/* useStoreProps */.Tz)(store, props, "showTimeout");
	(0,dist/* useStoreProps */.Tz)(store, props, "hideTimeout");
	return usePopoverStoreProps(store, update, props);
}
/**
* Creates a hovercard store to control the state of
* [Hovercard](https://ariakit.com/reference/hovercard) components.
* @see https://ariakit.com/components/hovercard
* @example
* ```jsx
* const hovercard = useHovercardStore({ placement: "top" });
*
* <HovercardAnchor store={hovercard}>@username</HovercardAnchor>
* <Hovercard store={hovercard}>Details</Hovercard>
* ```
*/
function useHovercardStore(props = {}) {
	const [store, update] = useStore(Core.createHovercardStore, props);
	return useHovercardStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=hovercard-store.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js
var store_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+store@0.1.10/node_modules/@ariakit/store/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js
var utils_dist = __webpack_require__("../../node_modules/.pnpm/@ariakit+utils@0.2.1/node_modules/@ariakit/utils/dist/index.js");
;// ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/popover/popover-store.js



//#region src/popover/popover-store.ts
/**
* Creates a popover store.
*/
function createPopoverStore({ popover: otherPopover, ...props } = {}) {
	const store = (0,store_dist/* mergeStore */.od)(props.store, (0,store_dist/* omit */.cJ)(otherPopover, [
		"arrowElement",
		"anchorElement",
		"contentElement",
		"popoverElement",
		"disclosureElement",
		"unstable_placing"
	]));
	(0,store_dist/* throwOnConflictingProps */.UE)(props, store);
	const syncState = store?.getState();
	const dialog = createDialogStore({
		...props,
		store
	});
	const placement = (0,utils_dist/* defaultValue */.Jh)(props.placement, syncState?.placement, "bottom");
	const initialState = {
		...dialog.getState(),
		placement,
		currentPlacement: placement,
		anchorElement: (0,utils_dist/* defaultValue */.Jh)(syncState?.anchorElement, null),
		popoverElement: (0,utils_dist/* defaultValue */.Jh)(syncState?.popoverElement, null),
		arrowElement: (0,utils_dist/* defaultValue */.Jh)(syncState?.arrowElement, null),
		rendered: Symbol("rendered"),
		unstable_placing: false
	};
	const popover = (0,store_dist/* createStore */.y$)(initialState, dialog, store);
	let syncedAnchorElement = initialState.anchorElement === initialState.disclosureElement ? initialState.anchorElement : null;
	(0,store_dist/* setup */.mj)(popover, () => (0,store_dist/* sync */.OH)(popover, ["anchorElement", "disclosureElement"], (state) => {
		if (state.anchorElement && state.anchorElement !== syncedAnchorElement) {
			syncedAnchorElement = null;
			return;
		}
		syncedAnchorElement = state.disclosureElement;
		popover.setState("anchorElement", syncedAnchorElement);
	}));
	(0,store_dist/* setup */.mj)(popover, () => otherPopover?.unstable_onHideRequest(dialog.unstable_requestHide));
	return {
		...dialog,
		...popover,
		setAnchorElement: (element) => popover.setState("anchorElement", element),
		setPopoverElement: (element) => popover.setState("popoverElement", element),
		setArrowElement: (element) => popover.setState("arrowElement", element),
		render: () => popover.setState("rendered", Symbol("rendered"))
	};
}
//#endregion


//# sourceMappingURL=popover-store.js.map
;// ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/hovercard/hovercard-store.js



//#region src/hovercard/hovercard-store.ts
/**
* Creates a hovercard store.
*/
function createHovercardStore(props = {}) {
	const syncState = props.store?.getState();
	const popover = createPopoverStore({
		...props,
		placement: (0,utils_dist/* defaultValue */.Jh)(props.placement, syncState?.placement, "bottom")
	});
	const timeout = (0,utils_dist/* defaultValue */.Jh)(props.timeout, syncState?.timeout, 500);
	const initialState = {
		...popover.getState(),
		timeout,
		showTimeout: (0,utils_dist/* defaultValue */.Jh)(props.showTimeout, syncState?.showTimeout),
		hideTimeout: (0,utils_dist/* defaultValue */.Jh)(props.hideTimeout, syncState?.hideTimeout),
		autoFocusOnShow: (0,utils_dist/* defaultValue */.Jh)(syncState?.autoFocusOnShow, false)
	};
	const hovercard = (0,store_dist/* createStore */.y$)(initialState, popover, props.store);
	return {
		...popover,
		...hovercard,
		setAutoFocusOnShow: (value) => hovercard.setState("autoFocusOnShow", value)
	};
}
//#endregion


//# sourceMappingURL=hovercard-store.js.map
;// ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/tooltip/tooltip-store.js



//#region src/tooltip/tooltip-store.ts
/**
* Creates a tooltip store.
*/
function createTooltipStore(props = {}) {
	if (false) {}
	const syncState = props.store?.getState();
	const hovercard = createHovercardStore({
		...props,
		placement: (0,utils_dist/* defaultValue */.Jh)(props.placement, syncState?.placement, "top"),
		hideTimeout: (0,utils_dist/* defaultValue */.Jh)(props.hideTimeout, syncState?.hideTimeout, 0)
	});
	const initialState = {
		...hovercard.getState(),
		type: (0,utils_dist/* defaultValue */.Jh)(props.type, syncState?.type, "description"),
		skipTimeout: (0,utils_dist/* defaultValue */.Jh)(props.skipTimeout, syncState?.skipTimeout, 300)
	};
	const tooltip = (0,store_dist/* createStore */.y$)(initialState, hovercard, props.store);
	return {
		...hovercard,
		...tooltip
	};
}
//#endregion


//# sourceMappingURL=tooltip-store.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tooltip/tooltip-store.js
"use client";



//#region src/tooltip/tooltip-store.ts
function useTooltipStoreProps(store, update, props) {
	(0,dist/* useStoreProps */.Tz)(store, props, "type");
	(0,dist/* useStoreProps */.Tz)(store, props, "skipTimeout");
	return useHovercardStoreProps(store, update, props);
}
/**
* Creates a tooltip store to control the state of
* [Tooltip](https://ariakit.com/components/tooltip) components.
* @see https://ariakit.com/components/tooltip
* @example
* ```jsx
* const tooltip = useTooltipStore();
*
* <TooltipAnchor store={tooltip}>Anchor</TooltipAnchor>
* <Tooltip store={tooltip}>Tooltip</Tooltip>
* ```
*/
function useTooltipStore(props = {}) {
	const [store, update] = (0,dist/* useStore */.Pj)(createTooltipStore, props);
	return useTooltipStoreProps(store, update, props);
}
//#endregion


//# sourceMappingURL=tooltip-store.js.map
;// ../../node_modules/.pnpm/@ariakit+components@0.1.14/node_modules/@ariakit/components/dist/role/role.js
//#region src/role/role.ts
/**
* Standard, non-obsolete HTML tags supported by React and Solid, plus svg.
* Excludes platform-specific intrinsic tags.
*/
const role_elements = [
	"a",
	"abbr",
	"address",
	"area",
	"article",
	"aside",
	"audio",
	"b",
	"base",
	"bdi",
	"bdo",
	"blockquote",
	"body",
	"br",
	"button",
	"canvas",
	"caption",
	"cite",
	"code",
	"col",
	"colgroup",
	"data",
	"datalist",
	"dd",
	"del",
	"details",
	"dfn",
	"dialog",
	"div",
	"dl",
	"dt",
	"em",
	"embed",
	"fieldset",
	"figcaption",
	"figure",
	"footer",
	"form",
	"h1",
	"h2",
	"h3",
	"h4",
	"h5",
	"h6",
	"head",
	"header",
	"hgroup",
	"hr",
	"html",
	"i",
	"iframe",
	"img",
	"input",
	"ins",
	"kbd",
	"label",
	"legend",
	"li",
	"link",
	"main",
	"map",
	"mark",
	"menu",
	"meta",
	"meter",
	"nav",
	"noscript",
	"object",
	"ol",
	"optgroup",
	"option",
	"output",
	"p",
	"picture",
	"pre",
	"progress",
	"q",
	"rp",
	"rt",
	"ruby",
	"s",
	"samp",
	"script",
	"section",
	"select",
	"slot",
	"small",
	"source",
	"span",
	"strong",
	"style",
	"sub",
	"summary",
	"sup",
	"table",
	"tbody",
	"td",
	"template",
	"textarea",
	"tfoot",
	"th",
	"thead",
	"time",
	"title",
	"tr",
	"track",
	"u",
	"ul",
	"var",
	"video",
	"wbr",
	"svg"
];
//#endregion


//# sourceMappingURL=role.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/role/role.js
"use client";


//#region src/role/role.tsx
const TagName = "div";
/**
* Returns props to create a `Role` component.
* @see https://ariakit.com/components/role
* @example
* ```jsx
* const props = useRole();
* <Role {...props} />
* ```
*/
const useRole = (0,react_utils_dist/* createHook */.ab)(function useRole(props) {
	return props;
});
/**
* Renders an abstract element that supports the `render` prop and a
* `wrapElement` prop that can be used to wrap the underlying element with React
* Portal, Context or other component types.
* @see https://ariakit.com/components/role
* @example
* ```jsx
* <Role render={<div />} />
* ```
*/
const Role = (0,react_utils_dist/* forwardRef */.Rf)(function Role(props) {
	return (0,react_utils_dist/* createElement */.n)(TagName, props);
});
Object.assign(Role, role_elements.reduce((acc, element) => {
	acc[element] = (0,react_utils_dist/* forwardRef */.Rf)(function Role(props) {
		return (0,react_utils_dist/* createElement */.n)(element, props);
	});
	return acc;
}, {}));
//#endregion


//# sourceMappingURL=role.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/C4_D9PgF.js
var C4_D9PgF = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/C4_D9PgF.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js
var focusable_focusable = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/Dv0S4FLv.js
"use client";





//#region src/hovercard/__hovercard-trigger.tsx
/**
* Returns props that add hovercard trigger behavior to an element.
* @see https://ariakit.com/components/hovercard
*/
const useHovercardTrigger = (0,react_utils_dist/* createHook */.ab)(function useHovercardTrigger({ store, showOnHover = true, unstable_showOnHoverWhenDisabled: showOnHoverWhenDisabled = true, setAnchorElement = false, ...props }) {
	const disabled = (0,utils_dist/* disabledFromProps */.$f)(props);
	const focusable = props.focusable !== false;
	const triggerRef = (0,react.useRef)(null);
	const showTimeoutRef = (0,react.useRef)(0);
	const isTrulyDisabled = (0,react_utils_dist/* useEvent */._q)((element) => {
		if (disabled && !focusable) return true;
		return (0,C4_D9PgF.c)(element);
	});
	(0,react.useEffect)(() => () => window.clearTimeout(showTimeoutRef.current), []);
	(0,react.useEffect)(() => {
		const onMouseLeave = (event) => {
			const trigger = triggerRef.current;
			if (!trigger) return;
			if (event.target !== trigger) return;
			window.clearTimeout(showTimeoutRef.current);
			showTimeoutRef.current = 0;
		};
		return (0,utils_dist/* addGlobalEventListener */.dg)("mouseleave", onMouseLeave, true);
	}, []);
	const onMouseMoveProp = props.onMouseMove;
	const showOnHoverProp = (0,react_utils_dist/* useBooleanEvent */.O4)(showOnHover);
	const showOnHoverWhenDisabledProp = (0,react_utils_dist/* useBooleanEvent */.O4)(showOnHoverWhenDisabled);
	const isMouseMoving = (0,react_utils_dist/* useIsMouseMoving */.P$)();
	const onMouseMove = (0,react_utils_dist/* useEvent */._q)((event) => {
		onMouseMoveProp?.(event);
		if (event.defaultPrevented) return;
		if (showTimeoutRef.current) return;
		if (!isMouseMoving()) return;
		const element = event.currentTarget;
		if (isTrulyDisabled(element)) return;
		if (disabled || (0,utils_dist/* disabledFromElement */.lp)(element)) {
			if (!showOnHoverWhenDisabledProp(event)) return;
		}
		if (!showOnHoverProp(event)) return;
		if (setAnchorElement) store.setAnchorElement(element);
		store.setDisclosureElement(element);
		const { showTimeout, timeout } = store.getState();
		const showHovercard = () => {
			showTimeoutRef.current = 0;
			if (!isMouseMoving()) return;
			if (isTrulyDisabled(element)) return;
			if (setAnchorElement) store.setAnchorElement(element);
			store.show();
			queueMicrotask(() => {
				store.setDisclosureElement(element);
			});
		};
		const timeoutMs = showTimeout ?? timeout;
		if (timeoutMs === 0) showHovercard();
		else showTimeoutRef.current = window.setTimeout(showHovercard, timeoutMs);
	});
	const onClickProp = props.onClick;
	const onClick = (0,react_utils_dist/* useEvent */._q)((event) => {
		onClickProp?.(event);
		window.clearTimeout(showTimeoutRef.current);
		showTimeoutRef.current = 0;
	});
	const ref = (0,react.useCallback)((element) => {
		triggerRef.current = element;
		if (!setAnchorElement) return;
		const { anchorElement } = store.getState();
		if (anchorElement?.isConnected) return;
		store.setAnchorElement(element);
	}, [store, setAnchorElement]);
	props = {
		...props,
		ref: (0,react_utils_dist/* useMergeRefs */.SV)(ref, props.ref),
		onMouseMove,
		onClick
	};
	props = (0,focusable_focusable/* useFocusable */.W)(props);
	return props;
});
//#endregion


//# sourceMappingURL=Dv0S4FLv.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/popover/popover-context.js
var popover_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/popover/popover-context.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/hovercard/hovercard-context.js
"use client";


//#region src/hovercard/hovercard-context.tsx
const ctx = (0,react_utils_dist/* createStoreContext */.B0)([popover_context/* PopoverContextProvider */.wf], [popover_context/* PopoverScopedContextProvider */.s1]);
/**
* Returns the hovercard store from the nearest hovercard container.
* @example
* function Hovercard() {
*   const store = useHovercardContext();
*
*   if (!store) {
*     throw new Error("Hovercard must be wrapped in HovercardProvider");
*   }
*
*   // Use the store...
* }
*/
const useHovercardContext = ctx.useContext;
const useHovercardScopedContext = ctx.useScopedContext;
const useHovercardProviderContext = ctx.useProviderContext;
const HovercardContextProvider = ctx.ContextProvider;
const HovercardScopedContextProvider = ctx.ScopedContextProvider;
//#endregion


//# sourceMappingURL=hovercard-context.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/hovercard/hovercard-anchor.js
"use client";




//#region src/hovercard/hovercard-anchor.tsx
const hovercard_anchor_TagName = "a";
/**
* Returns props to create a `HovercardAnchor` component.
* @see https://ariakit.com/components/hovercard
* @example
* ```jsx
* const store = useHovercardStore();
* const props = useHovercardAnchor({ store });
* <Role {...props} render={<a />}>@username</Role>
* <Hovercard store={store}>Details</Hovercard>
* ```
*/
const useHovercardAnchor = (0,react_utils_dist/* createHook */.ab)(function useHovercardAnchor({ store, showOnHover = true, ...props }) {
	const context = useHovercardProviderContext();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	return useHovercardTrigger({
		store,
		showOnHover,
		setAnchorElement: true,
		...props
	});
});
/**
* Renders an anchor element that will open a
* [`Hovercard`](https://ariakit.com/reference/hovercard) popup on hover.
* @see https://ariakit.com/components/hovercard
* @example
* ```jsx {2}
* <HovercardProvider>
*   <HovercardAnchor>@username</HovercardAnchor>
*   <Hovercard>Details</Hovercard>
* </HovercardProvider>
* ```
*/
const HovercardAnchor = (0,react_utils_dist/* forwardRef */.Rf)(function HovercardAnchor(props) {
	const htmlProps = useHovercardAnchor(props);
	return (0,react_utils_dist/* createElement */.n)(hovercard_anchor_TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=hovercard-anchor.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tooltip/tooltip-context.js
"use client";


//#region src/tooltip/tooltip-context.tsx
const tooltip_context_ctx = (0,react_utils_dist/* createStoreContext */.B0)([HovercardContextProvider], [HovercardScopedContextProvider]);
/**
* Returns the tooltip store from the nearest tooltip container.
* @example
* function Tooltip() {
*   const store = useTooltipContext();
*
*   if (!store) {
*     throw new Error("Tooltip must be wrapped in TooltipProvider");
*   }
*
*   // Use the store...
* }
*/
const useTooltipContext = tooltip_context_ctx.useContext;
const useTooltipScopedContext = tooltip_context_ctx.useScopedContext;
const useTooltipProviderContext = tooltip_context_ctx.useProviderContext;
const TooltipContextProvider = tooltip_context_ctx.ContextProvider;
const TooltipScopedContextProvider = tooltip_context_ctx.ScopedContextProvider;
//#endregion


//# sourceMappingURL=tooltip-context.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tooltip/tooltip-anchor.js
"use client";







//#region src/tooltip/tooltip-anchor.tsx
const tooltip_anchor_TagName = "div";
const globalStore = (0,store_dist/* createStore */.y$)({ activeStore: null });
const hidingStores = /* @__PURE__ */ new WeakSet();
function createRemoveStoreCallback(store) {
	return () => {
		const { activeStore } = globalStore.getState();
		if (activeStore !== store) return;
		globalStore.setState("activeStore", null);
	};
}
function isEscapeKeyEvent(event) {
	if (!("key" in event)) return false;
	return event.key === "Escape";
}
function hideStore(store) {
	if (!store) return;
	hidingStores.add(store);
	store.hide();
	queueMicrotask(() => hidingStores.delete(store));
}
/**
* Returns props to create a `TooltipAnchor` component.
* @see https://ariakit.com/components/tooltip
* @example
* ```jsx
* const store = useToolTipStore();
* const props = useTooltipAnchor({ store });
* <Role {...props}>Anchor</Role>
* <Tooltip store={store}>Tooltip</Tooltip>
* ```
*/
const useTooltipAnchor = (0,react_utils_dist/* createHook */.ab)(function useTooltipAnchor({ store, showOnHover = true, ...props }) {
	const context = useTooltipProviderContext();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	const canShowOnHoverRef = (0,react.useRef)(false);
	(0,react.useEffect)(() => {
		return (0,store_dist/* sync */.OH)(store, ["mounted"], (state) => {
			if (state.mounted) return;
			canShowOnHoverRef.current = false;
		});
	}, [store]);
	const returningFocusRef = (0,react.useRef)(false);
	const returnedFocusRef = (0,react.useRef)(null);
	(0,react.useEffect)(() => {
		return (0,store_dist/* sync */.OH)(store, ["open"], (state) => {
			if (state.open) return;
			if (!store) return;
			const { contentElement } = store.getState();
			if (!contentElement) return;
			if (!(0,utils_dist/* hasFocusWithin */.oW)(contentElement)) return;
			returningFocusRef.current = true;
			setTimeout(() => {
				returningFocusRef.current = false;
			});
		});
	}, [store]);
	const onFocusProp = props.onFocus;
	const onFocus = (0,react_utils_dist/* useEvent */._q)((event) => {
		onFocusProp?.(event);
		if (!returningFocusRef.current) return;
		returningFocusRef.current = false;
		returnedFocusRef.current = event.nativeEvent;
	});
	(0,react.useEffect)(() => {
		if (!store) return;
		const removeStore = createRemoveStoreCallback(store);
		return (0,utils_dist/* chain */.cy)(removeStore, (0,store_dist/* sync */.OH)(store, ["mounted"], (state) => {
			if (!store) return;
			if (state.mounted) {
				const { activeStore } = globalStore.getState();
				if (activeStore !== store) {
					if (hidingStores.has(store)) return;
					hideStore(activeStore);
				}
				return globalStore.setState("activeStore", store);
			}
			const id = setTimeout(removeStore, store.getState().skipTimeout);
			return () => clearTimeout(id);
		}));
	}, [store]);
	const onMouseEnterProp = props.onMouseEnter;
	const onMouseEnter = (0,react_utils_dist/* useEvent */._q)((event) => {
		onMouseEnterProp?.(event);
		canShowOnHoverRef.current = true;
	});
	const onFocusVisibleProp = props.onFocusVisible;
	const onFocusVisible = (0,react_utils_dist/* useEvent */._q)((event) => {
		onFocusVisibleProp?.(event);
		if (event.defaultPrevented) return;
		if (isEscapeKeyEvent(event.nativeEvent)) return;
		if (event.nativeEvent === returnedFocusRef.current) return;
		store?.setAnchorElement(event.currentTarget);
		store?.show();
	});
	const onBlurProp = props.onBlur;
	const onBlur = (0,react_utils_dist/* useEvent */._q)((event) => {
		onBlurProp?.(event);
		if (event.defaultPrevented) return;
		const { activeStore } = globalStore.getState();
		canShowOnHoverRef.current = false;
		if (activeStore === store) globalStore.setState("activeStore", null);
	});
	const labelElement = (0,dist/* useStoreState */.O$)(store, ["type", "contentElement"], (state) => state.type === "label" ? state.contentElement : null);
	(0,react_utils_dist/* useAttribute */.Cy)(labelElement, "id");
	const labelledBy = labelElement?.id;
	props = {
		"aria-labelledby": props["aria-label"] == null ? labelledBy : void 0,
		...props,
		onMouseEnter,
		onFocus,
		onFocusVisible,
		onBlur
	};
	props = useHovercardAnchor({
		store,
		showOnHover(event) {
			if (!canShowOnHoverRef.current) return false;
			if ((0,utils_dist/* isFalsyBooleanCallback */.zO)(showOnHover, event)) return false;
			const { activeStore } = globalStore.getState();
			if (!activeStore) return true;
			store?.show();
			return false;
		},
		...props
	});
	return props;
});
/**
* Renders a reference element for a
* [`Tooltip`](https://ariakit.com/reference/tooltip), which is triggered by
* focusing or hovering over the anchor.
*
* The tooltip is strictly for visual purposes. It's your responsibility to
* ensure the anchor element has an accessible name. See [Tooltip anchors must
* have accessible
* names](https://ariakit.com/components/tooltip#tooltip-anchors-must-have-accessible-names)
* @see https://ariakit.com/components/tooltip
* @example
* ```jsx {2}
* <TooltipProvider>
*   <TooltipAnchor>Anchor</TooltipAnchor>
*   <Tooltip>Tooltip</Tooltip>
* </TooltipProvider>
* ```
*/
const TooltipAnchor = (0,react_utils_dist/* forwardRef */.Rf)(function TooltipAnchor(props) {
	const htmlProps = useTooltipAnchor(props);
	return (0,react_utils_dist/* createElement */.n)(tooltip_anchor_TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=tooltip-anchor.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/dialog-context.js
var dialog_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/dialog-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/CDh2UhnG.js
var CDh2UhnG = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/CDh2UhnG.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-content.js
var disclosure_content = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/disclosure/disclosure-content.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable-context.js
var focusable_context = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focusable/focusable-container.js
"use client";



//#region src/focusable/focusable-container.tsx
const focusable_container_TagName = "div";
/**
* Returns props to create a `FocusableContainer` component.
* @see https://ariakit.com/components/focusable
* @example
* ```jsx
* const props = useFocusableContainer();
* <Role {...props} />
* ```
*/
const useFocusableContainer = (0,react_utils_dist/* createHook */.ab)(function useFocusableContainer({ autoFocusOnShow = true, ...props }) {
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(focusable_context/* FocusableContext */.g.Provider, {
		value: autoFocusOnShow,
		children: element
	}), [autoFocusOnShow]);
	return props;
});
/**
* Renders a div that wraps
* [`Focusable`](https://ariakit.com/reference/focusable) components and
* controls whether they can be auto-focused.
* @see https://ariakit.com/components/focusable
* @example
* ```jsx
* <FocusableContainer autoFocusOnShow={false}>
*   <Focusable autoFocus />
* </FocusableContainer>
* ```
*/
const FocusableContainer = (0,react_utils_dist/* forwardRef */.Rf)(function FocusableContainer(props) {
	const htmlProps = useFocusableContainer(props);
	return (0,react_utils_dist/* createElement */.n)(focusable_container_TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=focusable-container.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/heading/heading-context.js
"use client";

//#region src/heading/heading-context.tsx
const HeadingContext = (0,react.createContext)(0);
//#endregion


//# sourceMappingURL=heading-context.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/heading/heading-level.js
"use client";



//#region src/heading/heading-level.tsx
/**
* A component that sets the heading level for its children. It doesn't render
* any HTML element, just sets the
* [`level`](https://ariakit.com/reference/heading-level#level) prop on the
* context.
* @see https://ariakit.com/components/heading
* @example
* ```jsx
* <HeadingLevel>
*   <Heading>Heading 1</Heading>
*   <HeadingLevel>
*     <Heading>Heading 2</Heading>
*   </HeadingLevel>
* </HeadingLevel>
* ```
*/
function HeadingLevel({ level, children }) {
	const contextLevel = (0,react.useContext)(HeadingContext);
	const nextLevel = Math.max(Math.min(level || contextLevel + 1, 6), 1);
	return /* @__PURE__ */ (0,jsx_runtime.jsx)(HeadingContext.Provider, {
		value: nextLevel,
		children
	});
}
//#endregion


//# sourceMappingURL=heading-level.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/visually-hidden/visually-hidden.js
"use client";

//#region src/visually-hidden/visually-hidden.tsx
const visually_hidden_TagName = "span";
/**
* Returns styles to visually hide an element while keeping it accessible to
* screen readers.
*/
function getVisuallyHiddenStyle(style) {
	return {
		borderWidth: 0,
		clipPath: "inset(50%)",
		height: "1px",
		margin: "-1px",
		overflow: "hidden",
		padding: 0,
		position: "absolute",
		whiteSpace: "nowrap",
		width: "1px",
		...style
	};
}
/**
* Returns props to create a `VisuallyHidden` component. When applying the props
* returned by this hook to a component, the component will be visually hidden,
* but still accessible to screen readers.
* @see https://ariakit.com/components/visually-hidden
* @example
* ```jsx
* const props = useVisuallyHidden();
* <a href="#">
*   Learn more<Role {...props}> about the Solar System</Role>.
* </a>
* ```
*/
const useVisuallyHidden = (0,react_utils_dist/* createHook */.ab)(function useVisuallyHidden(props) {
	props = {
		...props,
		style: getVisuallyHiddenStyle(props.style)
	};
	return props;
});
/**
* Renders an element that's visually hidden, but still accessible to screen
* readers.
* @see https://ariakit.com/components/visually-hidden
* @example
* ```jsx
* <a href="#">
*   Learn more<VisuallyHidden> about the Solar System</VisuallyHidden>.
* </a>
* ```
*/
const VisuallyHidden = (0,react_utils_dist/* forwardRef */.Rf)(function VisuallyHidden(props) {
	const htmlProps = useVisuallyHidden(props);
	return (0,react_utils_dist/* createElement */.n)(visually_hidden_TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=visually-hidden.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/focus-trap/focus-trap.js
"use client";


//#region src/focus-trap/focus-trap.tsx
const focus_trap_TagName = "span";
/**
* Returns props to create a `FocusTrap` component.
* @see https://ariakit.com/components/focus-trap
* @example
* ```jsx
* const props = useFocusTrap();
* <Role {...props} />
* ```
*/
const useFocusTrap = (0,react_utils_dist/* createHook */.ab)(function useFocusTrap(props) {
	props = {
		"data-focus-trap": "",
		tabIndex: 0,
		"aria-hidden": true,
		...props,
		style: {
			position: "fixed",
			top: 0,
			left: 0,
			...props.style
		}
	};
	props = useVisuallyHidden(props);
	return props;
});
/**
* Renders a focus trap element.
* @see https://ariakit.com/components/focus-trap
* @example
* ```jsx
* <FocusTrap onFocus={focusSomethingElse} />
* ```
*/
const FocusTrap = (0,react_utils_dist/* forwardRef */.Rf)(function FocusTrap(props) {
	const htmlProps = useFocusTrap(props);
	return (0,react_utils_dist/* createElement */.n)(focus_trap_TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=focus-trap.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/portal/portal-context.js
"use client";

//#region src/portal/portal-context.tsx
/**
* Stores the element that will contain the portal. By default, it will be the
* body of the document.
* @example
* ```jsx
* const container = document.getElementById("container");
*
* function App() {
*   return (
*     <PortalContext.Provider value={container}>
*       <Portal />
*     </PortalContext.Provider>
*   );
* }
* ```
*/
const PortalContext = (0,react.createContext)(null);
//#endregion


//# sourceMappingURL=portal-context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/portal/portal.js
"use client";







//#region src/portal/portal.tsx
const portal_TagName = "div";
function getRootElement(element) {
	const doc = (0,utils_dist/* getDocument */.YE)(element);
	const { fullscreenElement } = doc;
	const HTMLElementClass = (0,utils_dist/* getWindow */.zk)(element).HTMLElement;
	if (HTMLElementClass && fullscreenElement instanceof HTMLElementClass) return fullscreenElement;
	return doc.body;
}
function getPortalElement(element, portalElement) {
	if (!portalElement) return (0,utils_dist/* getDocument */.YE)(element).createElement("div");
	if (typeof portalElement === "function") return portalElement(element);
	return portalElement;
}
function getRandomId(prefix = "id") {
	return `${prefix ? `${prefix}-` : ""}${Math.random().toString(36).slice(2, 8)}`;
}
function queueFocus(element) {
	queueMicrotask(() => {
		element?.focus();
	});
}
function attachPortalRef(ref, node) {
	return {
		ref,
		node,
		cleanup: (0,react_utils_dist/* setRef */.cZ)(ref, node)
	};
}
function detachPortalRef(attached) {
	if (typeof attached.cleanup === "function") attached.cleanup();
	else (0,react_utils_dist/* setRef */.cZ)(attached.ref, null);
}
/**
* Returns props to create a `Portal` component.
* @see https://ariakit.com/components/portal
* @example
* ```jsx
* const props = usePortal();
* <Role {...props}>Content</Role>
* ```
*/
const usePortal = (0,react_utils_dist/* createHook */.ab)(function usePortal({ preserveTabOrder, preserveTabOrderAnchor, portalElement, portalRef, portal = true, ...props }) {
	const ref = (0,react.useRef)(null);
	const refProp = (0,react_utils_dist/* useMergeRefs */.SV)(ref, props.ref);
	const context = (0,react.useContext)(PortalContext);
	const [portalNode, setPortalNode] = (0,react.useState)(null);
	const [anchorPortalNode, setAnchorPortalNode] = (0,react.useState)(null);
	const outerBeforeRef = (0,react.useRef)(null);
	const innerBeforeRef = (0,react.useRef)(null);
	const innerAfterRef = (0,react.useRef)(null);
	const outerAfterRef = (0,react.useRef)(null);
	const portalRefProp = (0,react_utils_dist/* useLiveRef */.hX)(portalRef);
	const attachedPortalRefRef = (0,react.useRef)(null);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		const element = ref.current;
		if (!element || !portal) {
			setPortalNode(null);
			return;
		}
		const portalEl = getPortalElement(element, portalElement);
		if (!portalEl) {
			setPortalNode(null);
			return;
		}
		const isPortalInDocument = portalEl.isConnected;
		if (!isPortalInDocument) (context || getRootElement(element)).appendChild(portalEl);
		if (!portalEl.id) portalEl.id = element.id ? `portal/${element.id}` : getRandomId();
		setPortalNode(portalEl);
		attachedPortalRefRef.current = attachPortalRef(portalRefProp.current, portalEl);
		return () => {
			const attached = attachedPortalRefRef.current;
			if (attached) {
				attachedPortalRefRef.current = null;
				detachPortalRef(attached);
			}
			if (!isPortalInDocument) portalEl.remove();
		};
	}, [
		portal,
		portalElement,
		context,
		portalRefProp
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		const attached = attachedPortalRefRef.current;
		if (!attached) return;
		if (attached.ref === portalRef) return;
		detachPortalRef(attached);
		attachedPortalRefRef.current = attachPortalRef(portalRef, attached.node);
	}, [portalRef]);
	(0,react.useEffect)(() => {
		if (!portalNode) return;
		if (context) return;
		if (portalElement) return;
		const doc = (0,utils_dist/* getDocument */.YE)(portalNode);
		const onFullscreenChange = () => {
			const rootElement = getRootElement(portalNode);
			if (portalNode.parentElement !== rootElement) rootElement.appendChild(portalNode);
		};
		if (portalNode.isConnected) onFullscreenChange();
		doc.addEventListener("fullscreenchange", onFullscreenChange);
		return () => {
			doc.removeEventListener("fullscreenchange", onFullscreenChange);
		};
	}, [
		portalNode,
		context,
		portalElement
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!portal) return;
		if (!preserveTabOrder) return;
		if (!preserveTabOrderAnchor) return;
		const element = (0,utils_dist/* getDocument */.YE)(preserveTabOrderAnchor).createElement("span");
		element.style.position = "fixed";
		preserveTabOrderAnchor.insertAdjacentElement("afterend", element);
		setAnchorPortalNode(element);
		return () => {
			element.remove();
			setAnchorPortalNode(null);
		};
	}, [
		portal,
		preserveTabOrder,
		preserveTabOrderAnchor
	]);
	(0,react.useEffect)(() => {
		if (!portalNode) return;
		if (!preserveTabOrder) return;
		let raf = 0;
		const onFocus = (event) => {
			if (!(0,utils_dist/* isFocusEventOutside */.aG)(event)) return;
			const focusing = event.type === "focusin";
			cancelAnimationFrame(raf);
			if (focusing) return (0,utils_dist/* restoreFocusIn */.QZ)(portalNode);
			raf = requestAnimationFrame(() => {
				(0,utils_dist/* disableFocusIn */.gw)(portalNode, true);
			});
		};
		portalNode.addEventListener("focusin", onFocus, true);
		portalNode.addEventListener("focusout", onFocus, true);
		return () => {
			cancelAnimationFrame(raf);
			portalNode.removeEventListener("focusin", onFocus, true);
			portalNode.removeEventListener("focusout", onFocus, true);
		};
	}, [portalNode, preserveTabOrder]);
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => {
		element = /* @__PURE__ */ (0,jsx_runtime.jsx)(PortalContext.Provider, {
			value: portalNode || context,
			children: element
		});
		if (!portal) return element;
		if (!portalNode) return /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
			ref: refProp,
			id: props.id,
			style: { position: "fixed" },
			hidden: true
		});
		element = /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [
			preserveTabOrder && /* @__PURE__ */ (0,jsx_runtime.jsx)(FocusTrap, {
				ref: innerBeforeRef,
				"data-focus-trap": props.id,
				className: "__focus-trap-inner-before",
				onFocus: (event) => {
					if ((0,utils_dist/* isFocusEventOutside */.aG)(event, portalNode)) queueFocus((0,utils_dist/* getNextTabbable */.vF)());
					else queueFocus(outerBeforeRef.current);
				}
			}),
			element,
			preserveTabOrder && /* @__PURE__ */ (0,jsx_runtime.jsx)(FocusTrap, {
				ref: innerAfterRef,
				"data-focus-trap": props.id,
				className: "__focus-trap-inner-after",
				onFocus: (event) => {
					if ((0,utils_dist/* isFocusEventOutside */.aG)(event, portalNode)) queueFocus((0,utils_dist/* getPreviousTabbable */.XJ)());
					else queueFocus(outerAfterRef.current);
				}
			})
		] });
		element = (0,react_dom.createPortal)(element, portalNode);
		let preserveTabOrderElement = /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [
			preserveTabOrder && /* @__PURE__ */ (0,jsx_runtime.jsx)(FocusTrap, {
				ref: outerBeforeRef,
				"data-focus-trap": props.id,
				className: "__focus-trap-outer-before",
				onFocus: (event) => {
					if (!(event.relatedTarget === outerAfterRef.current) && (0,utils_dist/* isFocusEventOutside */.aG)(event, portalNode)) queueFocus(innerBeforeRef.current);
					else queueFocus((0,utils_dist/* getPreviousTabbable */.XJ)());
				}
			}),
			preserveTabOrder && /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
				"aria-owns": portalNode.id,
				style: { position: "fixed" }
			}),
			preserveTabOrder && /* @__PURE__ */ (0,jsx_runtime.jsx)(FocusTrap, {
				ref: outerAfterRef,
				"data-focus-trap": props.id,
				className: "__focus-trap-outer-after",
				onFocus: (event) => {
					if ((0,utils_dist/* isFocusEventOutside */.aG)(event, portalNode)) queueFocus(innerAfterRef.current);
					else {
						const nextTabbable = (0,utils_dist/* getNextTabbable */.vF)();
						if (nextTabbable === innerBeforeRef.current) {
							requestAnimationFrame(() => (0,utils_dist/* getNextTabbable */.vF)()?.focus());
							return;
						}
						queueFocus(nextTabbable);
					}
				}
			})
		] });
		if (anchorPortalNode && preserveTabOrder) preserveTabOrderElement = (0,react_dom.createPortal)(preserveTabOrderElement, anchorPortalNode);
		return /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [preserveTabOrderElement, element] });
	}, [
		portalNode,
		context,
		portal,
		props.id,
		preserveTabOrder,
		anchorPortalNode
	]);
	props = {
		...props,
		ref: refProp
	};
	return props;
});
/**
* Renders an element using [React
* Portal](https://react.dev/reference/react-dom/createPortal).
*
* By default, the portal element is a `div` element appended to the
* `document.body` element. You can customize this with the
* [`portalElement`](https://ariakit.com/reference/portal#portalelement) prop.
*
* The
* [`preserveTabOrder`](https://ariakit.com/reference/portal#preservetaborder)
* prop allows this component to manage the tab order of the elements. It
* ensures the tab order remains consistent with the original location where the
* portal was rendered in the React tree, instead of the final location in the
* DOM. The
* [`preserveTabOrderAnchor`](https://ariakit.com/reference/portal#preservetaborderanchor)
* prop can specify a different location from which the tab order is preserved.
* @see https://ariakit.com/components/portal
* @example
* ```jsx
* <Portal>Content</Portal>
* ```
*/
const Portal = (0,react_utils_dist/* forwardRef */.Rf)(function Portal(props) {
	const htmlProps = usePortal(props);
	return (0,react_utils_dist/* createElement */.n)(portal_TagName, htmlProps);
});
//#endregion


//# sourceMappingURL=portal.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/is-backdrop.js
"use client";
//#region src/dialog/utils/is-backdrop.ts
function isBackdrop(element, ...ids) {
	if (!element) return false;
	const backdrop = element.getAttribute("data-backdrop");
	if (backdrop == null) return false;
	if (backdrop === "") return true;
	if (backdrop === "true") return true;
	if (!ids.length) return true;
	return ids.some((id) => backdrop === id);
}
//#endregion


//# sourceMappingURL=is-backdrop.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/orchestrate.js
"use client";
//#region src/dialog/utils/orchestrate.ts
const cleanups = /* @__PURE__ */ new WeakMap();
function flushCleanupStack(elementCleanups, key, stack) {
	while (stack.length) {
		const entry = stack[stack.length - 1];
		if (!entry) {
			elementCleanups.delete(key);
			return;
		}
		if (!entry.disposed) return;
		stack.pop();
		entry.cleanup();
	}
	elementCleanups.delete(key);
}
function orchestrate(element, key, setup) {
	let elementCleanups = cleanups.get(element);
	if (!elementCleanups) {
		elementCleanups = /* @__PURE__ */ new Map();
		cleanups.set(element, elementCleanups);
	}
	const stack = elementCleanups.get(key) ?? [];
	const entry = {
		cleanup: setup(),
		disposed: false
	};
	if (!stack.length) elementCleanups.set(key, stack);
	stack.push(entry);
	return () => {
		if (!stack.includes(entry)) return;
		entry.disposed = true;
		flushCleanupStack(elementCleanups, key, stack);
	};
}
function setAttribute(element, attr, value) {
	const setup = () => {
		const previousValue = element.getAttribute(attr);
		element.setAttribute(attr, value);
		return () => {
			if (previousValue == null) element.removeAttribute(attr);
			else element.setAttribute(attr, previousValue);
		};
	};
	return orchestrate(element, attr, setup);
}
function setProperty(element, property, value) {
	const setup = () => {
		const exists = property in element;
		const previousValue = element[property];
		element[property] = value;
		return () => {
			if (!exists) delete element[property];
			else element[property] = previousValue;
		};
	};
	return orchestrate(element, property, setup);
}
function assignStyle(element, style) {
	if (!element) return () => {};
	const setup = () => {
		const prevStyle = element.style.cssText;
		Object.assign(element.style, style);
		return () => {
			element.style.cssText = prevStyle;
		};
	};
	return orchestrate(element, "style", setup);
}
function setCSSProperty(element, property, value) {
	if (!element) return () => {};
	const setup = () => {
		const previousValue = element.style.getPropertyValue(property);
		const previousPriority = element.style.getPropertyPriority(property);
		element.style.setProperty(property, value);
		return () => {
			if (previousValue) element.style.setProperty(property, previousValue, previousPriority);
			else element.style.removeProperty(property);
		};
	};
	return orchestrate(element, property, setup);
}
//#endregion


//# sourceMappingURL=orchestrate.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/tree-cleanup.js
"use client";



//#region src/dialog/utils/tree-cleanup.ts
/**
* Finds marks in the owning dialog's current walk. IDs cannot identify their
* owner because dialogs in different roots can share an ID.
* https://github.com/ariakit/ariakit/issues/7726
*/
function isElementMarkedBy(element, cleanups) {
	if (!cleanups) return false;
	if (cleanups.get(element)?.has("ancestorMark")) return true;
	do {
		if (cleanups.get(element)?.has("mark")) return true;
		if (!element.parentElement) return false;
		element = element.parentElement;
	} while (true);
}
const insideElements = /* @__PURE__ */ new WeakMap();
function getPropertyName(id = "", kind = "outside") {
	return `__ariakit-dialog-${kind}${id ? `-${id}` : ""}`;
}
function markElement(element, id = "") {
	return (0,utils_dist/* chain */.cy)(setProperty(element, getPropertyName(), true), setProperty(element, getPropertyName(id), true));
}
function markAncestor(element, id = "") {
	return (0,utils_dist/* chain */.cy)(setProperty(element, getPropertyName("", "ancestor"), true), setProperty(element, getPropertyName(id, "ancestor"), true));
}
/**
* Marks elements the dialog knows about at open time (the dialog itself,
* persistent elements, and nested dialogs), so outside event listeners can
* recognize them as inside before the dialog has been focused.
* @see https://github.com/ariakit/ariakit/issues/6344
*/
function markTreeInside(dialog, elements) {
	const marker = /* @__PURE__ */ new WeakSet();
	insideElements.set(dialog, marker);
	for (const element of elements) if (element) marker.add(element);
	return () => {
		if (insideElements.get(dialog) !== marker) return;
		insideElements.delete(dialog);
	};
}
function isElementInside(element, dialog) {
	const marker = insideElements.get(dialog);
	if (!marker) return false;
	do {
		if (marker.has(element)) return true;
		if (!element.parentElement) return false;
		element = element.parentElement;
	} while (true);
}
function isElementMarked(element, id) {
	const ancestorProperty = getPropertyName(id, "ancestor");
	if (element[ancestorProperty]) return true;
	const elementProperty = getPropertyName(id);
	do {
		if (element[elementProperty]) return true;
		if (!element.parentElement) return false;
		element = element.parentElement;
	} while (true);
}
function startCleanupWalk(previousCleanups) {
	return {
		cleanups: /* @__PURE__ */ new Map(),
		previousCleanups
	};
}
/**
* Adds the cleanup of an element to the walk. If the previous walk has a
* cleanup for the same element and kind, the function moves that cleanup and
* does not call `setup`.
*/
function addCleanup({ walk, element, kind, setup }) {
	const elementCleanups = walk.cleanups.get(element) ?? /* @__PURE__ */ new Map();
	if (elementCleanups.has(kind)) return;
	walk.cleanups.set(element, elementCleanups);
	const previousElementCleanups = walk.previousCleanups?.get(element);
	const previousCleanup = previousElementCleanups?.get(kind);
	previousElementCleanups?.delete(kind);
	elementCleanups.set(kind, previousCleanup ?? setup());
}
/**
* Restores the cleanups that the walk did not keep from the previous walk. They
* belong to elements that are no longer part of the walk.
*/
function finishCleanupWalk(walk) {
	if (walk.previousCleanups) restoreCleanups(walk.previousCleanups);
	return walk.cleanups;
}
function addElementMarkCleanup({ walk, element, id, ids }) {
	if (isBackdrop(element, ...ids)) return;
	addCleanup({
		walk,
		element,
		kind: "mark",
		setup: () => markElement(element, id)
	});
}
function addAncestorMarkCleanup({ walk, ancestor, element, id }) {
	if (element.hasAttribute("data-dialog") && element.id !== id) return;
	addCleanup({
		walk,
		element: ancestor,
		kind: "ancestorMark",
		setup: () => markAncestor(ancestor, id)
	});
}
function restoreCleanups(cleanups) {
	for (const elementCleanups of cleanups.values()) for (const cleanup of elementCleanups.values()) cleanup();
	cleanups.clear();
}
//#endregion


//# sourceMappingURL=tree-cleanup.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/CexKSKpV.js
"use client";


//#region src/dialog/utils/__walk-tree-snapshot.ts
function getSnapshotPropertyName(id) {
	return `__ariakit-dialog-snapshot-${id}`;
}
/**
* Adds elements to the snapshot of the dialog. The snapshot takes every element
* outside the dialog, so the list can be long. React can also replace the
* element of another dialog that was already open when the snapshot was taken,
* and the new element isn't in the snapshot.
*/
function addToWalkTreeSnapshot(id, elements) {
	const propertyName = getSnapshotPropertyName(id);
	const cleanups = [];
	for (const element of elements) cleanups.push(setProperty(element, propertyName, true));
	return () => {
		for (const cleanup of cleanups) cleanup();
	};
}
//#endregion
//#region src/dialog/utils/walk-tree-outside.ts
const ignoreTags = ["SCRIPT", "STYLE"];
function inSnapshot(id, element) {
	const doc = (0,utils_dist/* getDocument */.YE)(element);
	const propertyName = getSnapshotPropertyName(id);
	if (!doc.body[propertyName]) return true;
	do {
		if (element === doc.body) return false;
		if (element[propertyName]) return true;
		if (!element.parentElement) return false;
		element = element.parentElement;
	} while (true);
}
function shouldWalkElement(id, element, ignoredElements) {
	if (ignoreTags.includes(element.tagName)) return false;
	if (!inSnapshot(id, element)) return false;
	return !ignoredElements.some((enabledElement) => enabledElement && (0,utils_dist/* contains */.gR)(element, enabledElement));
}
function walkTreeOutside(id, elements, callback, ancestorCallback) {
	for (let element of elements) {
		if (!element?.isConnected) continue;
		const hasAncestorAlready = elements.some((maybeAncestor) => {
			if (!maybeAncestor) return false;
			if (maybeAncestor === element) return false;
			return maybeAncestor.contains(element);
		});
		const doc = (0,utils_dist/* getDocument */.YE)(element);
		const originalElement = element;
		while (element.parentElement && element !== doc.body) {
			ancestorCallback?.(element.parentElement, originalElement);
			if (!hasAncestorAlready) {
				for (const child of element.parentElement.children) if (shouldWalkElement(id, child, elements)) callback(child, originalElement);
			}
			element = element.parentElement;
		}
	}
}
function createWalkTreeSnapshot(id, elements) {
	const { body } = (0,utils_dist/* getDocument */.YE)(elements[0]);
	const snapshotElements = [];
	walkTreeOutside(id, elements, (element) => {
		snapshotElements.push(element);
	});
	return (0,utils_dist/* chain */.cy)(setProperty(body, getSnapshotPropertyName(id), true), addToWalkTreeSnapshot(id, snapshotElements));
}
//#endregion


//# sourceMappingURL=CexKSKpV.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/mark-tree-outside.js
"use client";


//#region src/dialog/utils/mark-tree-outside.ts
function markTreeOutside(id, elements, previousCleanups) {
	const walk = startCleanupWalk(previousCleanups);
	const ids = elements.map((el) => el?.id);
	walkTreeOutside(id, elements, (element) => {
		addElementMarkCleanup({
			walk,
			element,
			id,
			ids
		});
	}, (ancestor, element) => {
		addAncestorMarkCleanup({
			walk,
			ancestor,
			element,
			id
		});
	});
	return finishCleanupWalk(walk);
}
//#endregion


//# sourceMappingURL=mark-tree-outside.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/dialog-backdrop.js
"use client";








//#region src/dialog/dialog-backdrop.tsx
function DialogBackdrop({ store, backdrop, backdropRef, alwaysVisible, hidden }) {
	const ref = (0,react.useRef)(null);
	const disclosure = (0,disclosure_store/* useDisclosureStore */.E)({ disclosure: store });
	const contentElement = (0,dist/* useStoreState */.O$)(store, "contentElement");
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		const backdrop = ref.current;
		const dialog = contentElement;
		if (!backdrop) return;
		if (!dialog) return;
		backdrop.style.zIndex = getComputedStyle(dialog).zIndex;
	}, [contentElement]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		const id = contentElement?.id;
		if (!id) return;
		const backdrop = ref.current;
		if (!backdrop) return;
		return markAncestor(backdrop, id);
	}, [contentElement]);
	const props = (0,disclosure_content/* useDisclosureContent */.aT)({
		ref: (0,react_utils_dist/* useMergeRefs */.SV)(ref, backdropRef),
		store: disclosure,
		role: "presentation",
		"data-backdrop": contentElement?.id || "",
		alwaysVisible,
		...hidden != null && { hidden },
		style: {
			position: "fixed",
			top: 0,
			right: 0,
			bottom: 0,
			left: 0
		}
	});
	if (!backdrop) return null;
	if ((0,react.isValidElement)(backdrop)) return /* @__PURE__ */ (0,jsx_runtime.jsx)(Role, {
		...props,
		render: backdrop
	});
	const Component = typeof backdrop !== "boolean" ? backdrop : "div";
	return /* @__PURE__ */ (0,jsx_runtime.jsx)(Role, {
		...props,
		render: /* @__PURE__ */ (0,jsx_runtime.jsx)(Component, {})
	});
}
//#endregion


//# sourceMappingURL=dialog-backdrop.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/DztuoDGI.js
"use client";
//#region src/dialog/utils/__is-hidden-dismiss.ts
function isHiddenDismiss(element, ...ids) {
	if (!element) return false;
	const dismiss = element.getAttribute("data-dialog-hidden-dismiss");
	if (dismiss == null) return false;
	if (dismiss === "") return true;
	if (!ids.length) return true;
	return ids.some((id) => dismiss === id);
}
//#endregion


//# sourceMappingURL=DztuoDGI.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/supports-inert.js
"use client";
//#region src/dialog/utils/supports-inert.ts
function supportsInert() {
	return "inert" in HTMLElement.prototype;
}
//#endregion


//# sourceMappingURL=supports-inert.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/disable-accessibility-tree-outside.js
"use client";

//#region src/dialog/utils/disable-accessibility-tree-outside.ts
function hideElementFromAccessibilityTree(element) {
	return setAttribute(element, "aria-hidden", "true");
}
//#endregion


//# sourceMappingURL=disable-accessibility-tree-outside.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/is-focus-trap.js
"use client";
//#region src/dialog/utils/is-focus-trap.ts
function isFocusTrap(element, ...ids) {
	if (!element) return false;
	const attr = element.getAttribute("data-focus-trap");
	if (attr == null) return false;
	if (!ids.length) return true;
	if (attr === "") return false;
	return ids.some((id) => attr === id);
}
//#endregion


//# sourceMappingURL=is-focus-trap.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/disable-tree.js
"use client";









//#region src/dialog/utils/disable-tree.ts
function disableTree(element, ignoredElements) {
	if (!("style" in element)) return utils_dist/* noop */.lQ;
	if (supportsInert()) return setProperty(element, "inert", true);
	const enableElements = (0,utils_dist/* getAllTabbableIn */.a9)(element, true).map((element) => {
		if (ignoredElements?.some((el) => el && (0,utils_dist/* contains */.gR)(el, element))) return utils_dist/* noop */.lQ;
		const restoreFocusMethod = orchestrate(element, "focus", () => {
			element.focus = utils_dist/* noop */.lQ;
			return () => {
				delete element.focus;
			};
		});
		return (0,utils_dist/* chain */.cy)(setAttribute(element, "tabindex", "-1"), restoreFocusMethod);
	});
	return (0,utils_dist/* chain */.cy)(...enableElements, hideElementFromAccessibilityTree(element), assignStyle(element, {
		pointerEvents: "none",
		userSelect: "none",
		cursor: "default"
	}));
}
function addDisabledElementCleanup({ walk, element, elements, ids }) {
	if (isBackdrop(element, ...ids)) return;
	if (isFocusTrap(element, ...ids)) return;
	if (isHiddenDismiss(element, ...ids)) return;
	addCleanup({
		walk,
		element,
		kind: "disable",
		setup: () => disableTree(element, elements)
	});
}
function addRoleNoneCleanup(walk, ancestor, elements) {
	if (!ancestor.hasAttribute("role")) return;
	if (elements.some((el) => el && (0,utils_dist/* contains */.gR)(el, ancestor))) return;
	addCleanup({
		walk,
		element: ancestor,
		kind: "role",
		setup: () => setAttribute(ancestor, "role", "none")
	});
}
function markAndDisableTreeOutside(id, elements, previousCleanups) {
	if (previousCleanups && !supportsInert()) restoreCleanups(previousCleanups);
	const walk = startCleanupWalk(previousCleanups);
	const ids = elements.map((el) => el?.id);
	walkTreeOutside(id, elements, (element) => {
		addElementMarkCleanup({
			walk,
			element,
			id,
			ids
		});
		addDisabledElementCleanup({
			walk,
			element,
			elements,
			ids
		});
	}, (ancestor, element) => {
		addAncestorMarkCleanup({
			walk,
			ancestor,
			element,
			id
		});
		addRoleNoneCleanup(walk, ancestor, elements);
	});
	return finishCleanupWalk(walk);
}
//#endregion


//# sourceMappingURL=disable-tree.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/use-previous-mouse-down-ref.js
"use client";


//#region src/dialog/utils/use-previous-mouse-down-ref.ts
/**
* Returns an element followed by its iframe hosts up to the highest readable
* document.
*/
function getFrameChain(element) {
	const chain = [element];
	while (true) {
		let frameElement;
		try {
			frameElement = (0,utils_dist/* getWindow */.zk)(element).frameElement;
		} catch {
			return chain;
		}
		if (!(0,utils_dist/* isElement */.vq)(frameElement)) return chain;
		chain.push(frameElement);
		element = frameElement;
	}
}
/**
* Returns the full composed path and readable iframe host chain for positive
* dialog membership, plus a target projected into the reference element's root
* for outside-tree marks.
*/
function getEventTargets(event, referenceElement) {
	const elements = event.composedPath().filter(utils_dist/* isElement */.vq);
	if (!elements.length && (0,utils_dist/* isElement */.vq)(event.target)) elements.push(event.target);
	const composedTarget = elements[0];
	if (composedTarget) {
		for (const frameElement of getFrameChain(composedTarget).slice(1)) if (!elements.includes(frameElement)) elements.push(frameElement);
	}
	const root = referenceElement?.getRootNode();
	return {
		elements,
		rootTarget: root ? elements.find((target) => target.getRootNode() === root) || event.target : elements[0] || event.target
	};
}
/**
* Stores both event-target views from the latest mousedown so opening clicks
* and drag releases are classified from their mousedown origin.
*/
function usePreviousMouseDownRef(enabled, scope, referenceElement) {
	const previousMouseDownRef = (0,react.useRef)(null);
	(0,react.useEffect)(() => {
		if (!enabled) {
			previousMouseDownRef.current = null;
			return;
		}
		const onMouseDown = (event) => {
			previousMouseDownRef.current = getEventTargets(event, referenceElement);
		};
		return (0,utils_dist/* addGlobalEventListener */.dg)("mousedown", onMouseDown, true, scope);
	}, [
		enabled,
		scope,
		referenceElement
	]);
	return previousMouseDownRef;
}
//#endregion


//# sourceMappingURL=use-previous-mouse-down-ref.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/use-hide-on-interact-outside.js
"use client";







//#region src/dialog/utils/use-hide-on-interact-outside.ts
function getHighestReadableWindow(element) {
	const highestElement = getFrameChain(element).at(-1);
	return (0,utils_dist/* getWindow */.zk)(highestElement ?? element);
}
function isInDocument(target) {
	return target.isConnected;
}
function isDisclosure(disclosure, target) {
	if (!disclosure) return false;
	if ((0,utils_dist/* contains */.gR)(disclosure, target)) return true;
	const activeId = target.getAttribute("aria-activedescendant");
	if (activeId) {
		const activeElement = (0,utils_dist/* getDocument */.YE)(target).getElementById(activeId);
		if (activeElement) return (0,utils_dist/* contains */.gR)(disclosure, activeElement);
	}
	return false;
}
function isMouseEventOnDialog(event, dialog) {
	if (!("clientY" in event)) return false;
	const rect = dialog.getBoundingClientRect();
	if (rect.width === 0 || rect.height === 0) return false;
	return rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;
}
function isElementWithinDialog(target, contentElement, disclosureElement) {
	if ((0,utils_dist/* contains */.gR)(contentElement, target)) return true;
	if (isDisclosure(disclosureElement, target)) return true;
	if (target.hasAttribute("data-focus-trap")) return true;
	if (isHiddenDismiss(target, contentElement.id)) return true;
	return isElementInside(target, contentElement);
}
function isEventInsideDialog(targets, contentElement, disclosureElement) {
	return targets.elements.some((target) => isElementWithinDialog(target, contentElement, disclosureElement));
}
function useEventOutside({ store, type, listener, capture, open, contentElement, focusedRef }) {
	const callListener = (0,react_utils_dist/* useEvent */._q)(listener);
	(0,react.useEffect)(() => {
		if (!open) return;
		const onEvent = (event) => {
			const { contentElement, disclosureElement } = store.getState();
			if (!contentElement) return;
			const targets = getEventTargets(event, contentElement);
			const composedTarget = targets.elements[0];
			const target = targets.rootTarget;
			if (!(0,utils_dist/* isElement */.vq)(target)) return;
			if (composedTarget && !isInDocument(composedTarget)) return;
			if (!isInDocument(target)) return;
			if (isEventInsideDialog(targets, contentElement, disclosureElement)) return;
			const contentDocument = (0,utils_dist/* getDocument */.YE)(contentElement);
			if ((0,utils_dist/* getDocument */.YE)(target) !== contentDocument) {
				callListener(event);
				return;
			}
			if ((0,utils_dist/* getDocument */.YE)(composedTarget ?? target) === contentDocument && isMouseEventOnDialog(event, contentElement)) return;
			if (focusedRef.current && !isElementMarked(target, contentElement.id)) return;
			callListener(event);
		};
		const win = contentElement ? getHighestReadableWindow(contentElement) : void 0;
		return (0,utils_dist/* addGlobalEventListener */.dg)(type, onEvent, capture, win);
	}, [
		open,
		capture,
		store,
		type,
		callListener,
		contentElement,
		focusedRef
	]);
}
function shouldHideOnInteractOutside(hideOnInteractOutside, event) {
	if (typeof hideOnInteractOutside === "function") return hideOnInteractOutside(event);
	return !!hideOnInteractOutside;
}
function useHideOnInteractOutside({ store, hideOnInteractOutside, domReady, interactedOutsideRef, focusedStoreRef }) {
	const open = (0,dist/* useStoreState */.O$)(store, "open");
	const contentElement = (0,dist/* useStoreState */.O$)(store, "contentElement");
	const eventWindow = contentElement ? getHighestReadableWindow(contentElement) : void 0;
	const previousMouseDownRef = usePreviousMouseDownRef(open, eventWindow, contentElement);
	const focusedRef = (0,react.useRef)(false);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!open) return;
		if (!domReady) return;
		if (!contentElement) return;
		focusedRef.current = false;
		const onFocus = () => {
			focusedRef.current = true;
			focusedStoreRef.current = store;
		};
		contentElement.addEventListener("focusin", onFocus, true);
		return () => contentElement.removeEventListener("focusin", onFocus, true);
	}, [
		open,
		domReady,
		contentElement,
		store,
		focusedStoreRef
	]);
	const props = {
		store,
		capture: true,
		open,
		contentElement,
		focusedRef
	};
	const hide = () => {
		store.hide();
		if (!store.getState().open) return;
		interactedOutsideRef.current = false;
	};
	useEventOutside({
		...props,
		type: "click",
		listener: (event) => {
			const { contentElement, disclosureElement } = store.getState();
			const previousMouseDown = previousMouseDownRef.current;
			if (!previousMouseDown) return;
			if (!contentElement) return;
			if (isEventInsideDialog(previousMouseDown, contentElement, disclosureElement)) return;
			const previousRootTarget = previousMouseDown.rootTarget;
			if (!(0,utils_dist/* isElement */.vq)(previousRootTarget)) return;
			if ((0,utils_dist/* getDocument */.YE)(previousRootTarget) === (0,utils_dist/* getDocument */.YE)(contentElement) && !isElementMarked(previousRootTarget, contentElement.id)) return;
			if (!shouldHideOnInteractOutside(hideOnInteractOutside, event)) return;
			interactedOutsideRef.current = true;
			hide();
		}
	});
	useEventOutside({
		...props,
		type: "focusin",
		listener: (event) => {
			const { contentElement } = store.getState();
			if (!contentElement) return;
			if (event.target === (0,utils_dist/* getDocument */.YE)(contentElement)) return;
			if (!shouldHideOnInteractOutside(hideOnInteractOutside, event)) return;
			const target = event.target;
			if ((0,utils_dist/* isElement */.vq)(target) && (0,utils_dist/* getDocument */.YE)(target) !== (0,utils_dist/* getDocument */.YE)(contentElement)) interactedOutsideRef.current = true;
			hide();
		}
	});
	useEventOutside({
		...props,
		type: "contextmenu",
		listener: (event) => {
			if (!shouldHideOnInteractOutside(hideOnInteractOutside, event)) return;
			interactedOutsideRef.current = true;
			hide();
		}
	});
}
//#endregion


//# sourceMappingURL=use-hide-on-interact-outside.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/use-nested-dialogs.js
"use client";





//#region src/dialog/utils/use-nested-dialogs.tsx
const NestedDialogsContext = (0,react.createContext)({});
function useNestedDialogs(store) {
	const context = (0,react.useContext)(NestedDialogsContext);
	const [dialogs, setDialogs] = (0,react.useState)([]);
	const add = (0,react.useCallback)((dialog) => {
		setDialogs((dialogs) => [...dialogs, dialog]);
		return (0,utils_dist/* chain */.cy)(context.add?.(dialog), () => {
			setDialogs((dialogs) => dialogs.filter((d) => d !== dialog));
		});
	}, [context]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		return (0,store_dist/* sync */.OH)(store, ["open", "contentElement"], (state) => {
			if (!state.open) return;
			if (!state.contentElement) return;
			return context.add?.(store);
		});
	}, [store, context]);
	const providerValue = (0,react.useMemo)(() => ({
		store,
		add
	}), [store, add]);
	return {
		wrapElement: (0,react.useCallback)((element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(NestedDialogsContext.Provider, {
			value: providerValue,
			children: element
		}), [providerValue]),
		nestedDialogs: dialogs
	};
}
//#endregion


//# sourceMappingURL=use-nested-dialogs.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/use-root-dialog.js
"use client";




//#region src/dialog/utils/use-root-dialog.ts
function useRootDialog({ attribute, contentId, contentElement, enabled }) {
	const [updated, retry] = (0,react_utils_dist/* useForceUpdate */.CH)();
	const isRootDialog = (0,react.useCallback)(() => {
		if (!enabled) return false;
		if (!contentElement) return false;
		const { body } = (0,utils_dist/* getDocument */.YE)(contentElement);
		const id = body.getAttribute(attribute);
		return !id || id === contentId;
	}, [
		updated,
		enabled,
		contentElement,
		attribute,
		contentId
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!enabled) return;
		if (!contentId) return;
		if (!contentElement) return;
		const { body } = (0,utils_dist/* getDocument */.YE)(contentElement);
		if (isRootDialog()) {
			body.setAttribute(attribute, contentId);
			return () => body.removeAttribute(attribute);
		}
		const observer = new MutationObserver(() => (0,react_dom.flushSync)(retry));
		observer.observe(body, { attributeFilter: [attribute] });
		return () => observer.disconnect();
	}, [
		updated,
		enabled,
		contentId,
		contentElement,
		isRootDialog,
		attribute,
		retry
	]);
	return isRootDialog;
}
//#endregion


//# sourceMappingURL=use-root-dialog.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/dialog/utils/use-prevent-body-scroll.js
"use client";





//#region src/dialog/utils/use-prevent-body-scroll.ts
const isIOS = (0,utils_dist/* isApple */.f8)() && !(0,utils_dist/* isMac */.cX)();
const useLockEffect = isIOS ? react.useEffect : react_utils_dist/* useSafeLayoutEffect */.UQ;
function supportsScrollbarGutter(win) {
	const { CSS } = win;
	return !!CSS?.supports("scrollbar-gutter", "stable");
}
function getPaddingProperty(documentElement) {
	const documentLeft = documentElement.getBoundingClientRect().left;
	return Math.round(documentLeft) + documentElement.scrollLeft ? "paddingLeft" : "paddingRight";
}
function usePreventBodyScroll(contentElement, contentId, enabled) {
	const isRootDialog = useRootDialog({
		attribute: "data-dialog-prevent-body-scroll",
		contentElement,
		contentId,
		enabled
	});
	useLockEffect(() => {
		if (!isRootDialog()) return;
		if (!contentElement) return;
		const doc = (0,utils_dist/* getDocument */.YE)(contentElement);
		const win = (0,utils_dist/* getWindow */.zk)(contentElement);
		const { documentElement, body } = doc;
		const cssScrollbarWidth = documentElement.style.getPropertyValue("--scrollbar-width");
		const scrollbarWidth = cssScrollbarWidth ? Number.parseInt(cssScrollbarWidth, 10) : win.innerWidth - documentElement.clientWidth;
		const setStyle = () => {
			const computedStyle = win.getComputedStyle(documentElement);
			const scrollbarGutter = computedStyle.getPropertyValue("scrollbar-gutter");
			const hasGutter = scrollbarGutter.includes("stable");
			const isOverflowVisible = (value) => !value || value === "visible";
			const htmlScrolls = !isOverflowVisible(computedStyle.getPropertyValue("overflow-x")) || !isOverflowVisible(computedStyle.getPropertyValue("overflow-y"));
			const hideHtmlOverflow = () => (0,utils_dist/* chain */.cy)(setCSSProperty(documentElement, "overflow-x", "hidden"), setCSSProperty(documentElement, "overflow-y", "hidden"));
			const withHiddenHtmlOverflow = (restoreStyle) => {
				if (!htmlScrolls) return restoreStyle;
				return (0,utils_dist/* chain */.cy)(restoreStyle, hideHtmlOverflow());
			};
			if (!hasGutter && !scrollbarWidth) return withHiddenHtmlOverflow(assignStyle(body, { overflow: "hidden" }));
			if (hasGutter || supportsScrollbarGutter(win)) return (0,utils_dist/* chain */.cy)(setCSSProperty(documentElement, "scrollbar-gutter", hasGutter ? scrollbarGutter : "stable"), hideHtmlOverflow());
			return withHiddenHtmlOverflow((0,utils_dist/* chain */.cy)(setCSSProperty(documentElement, "--scrollbar-width", `${scrollbarWidth}px`), assignStyle(body, {
				overflow: "hidden",
				[getPaddingProperty(documentElement)]: `${scrollbarWidth}px`
			})));
		};
		const setIOSStyle = () => {
			const { scrollX, scrollY, visualViewport } = win;
			const offsetLeft = visualViewport?.offsetLeft ?? 0;
			const offsetTop = visualViewport?.offsetTop ?? 0;
			const restoreStyle = assignStyle(body, {
				position: "fixed",
				overflow: "hidden",
				top: `${-(scrollY - Math.floor(offsetTop))}px`,
				left: `${-(scrollX - Math.floor(offsetLeft))}px`,
				right: "0",
				[getPaddingProperty(documentElement)]: `${scrollbarWidth}px`
			});
			return () => {
				restoreStyle();
				// istanbul ignore next: JSDOM doesn't implement window.scrollTo
				if (true) win.scrollTo({
					left: scrollX,
					top: scrollY,
					behavior: "instant"
				});
			};
		};
		if (isIOS) return setIOSStyle();
		const restore = setStyle();
		return () => {
			queueMicrotask(restore);
		};
	}, [isRootDialog, contentElement]);
}
//#endregion


//# sourceMappingURL=use-prevent-body-scroll.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/DpKu70iZ.js
"use client";


























//#region src/dialog/utils/__captured-disclosures.ts
const capturedDisclosures = /* @__PURE__ */ new WeakSet();
function captureDisclosure(element) {
	capturedDisclosures.add(element);
}
/**
* Whether a dialog captured this element as a fallback opener rather than the
* application naming one. A captured element only happened to have focus, so it
* can't be assumed to close the dialog the way a disclosure does.
*/
function isCapturedDisclosure(element) {
	return capturedDisclosures.has(element);
}
//#endregion
//#region src/dialog/utils/__open-dialogs.ts
const openDialogs = /* @__PURE__ */ new Map();
/**
* Adds the dialog after the dialogs that are already open. The returned
* function removes it. With `onEarlierDialogElementChange`, the dialog learns
* when the element of a dialog that opened before it changes.
*/
function addOpenDialog(dialogRef, options = {}) {
	openDialogs.set(dialogRef, options);
	return () => {
		openDialogs.delete(dialogRef);
	};
}
/**
* Returns the elements of the dialogs that opened before the given dialog,
* except the ones that contain it.
*/
function getEarlierOpenDialogElements(dialogRef) {
	const elements = [];
	for (const openDialogRef of openDialogs.keys()) {
		if (openDialogRef === dialogRef) break;
		const { current: element } = openDialogRef;
		if (!element?.isConnected) continue;
		if (dialogRef.current && (0,utils_dist/* contains */.gR)(element, dialogRef.current)) continue;
		elements.push(element);
	}
	return elements;
}
/**
* Tells the dialogs that opened after the given dialog that its element
* changed.
*/
function notifyOpenDialogElementChange(dialogRef) {
	let foundDialog = false;
	for (const [openDialogRef, options] of openDialogs) {
		if (openDialogRef === dialogRef) {
			foundDialog = true;
			continue;
		}
		if (!foundDialog) continue;
		options.onEarlierDialogElementChange?.();
	}
}
/**
* Returns whether something is above the given dialog, which is the case when
* the dialog is marked.
*
* The exception is two dialogs that mark each other, because a dialog also
* marks a dialog that's in the DOM while hidden and opens later. In that case,
* the dialog that opened last is above the other one.
* https://github.com/ariakit/ariakit/issues/7647
*/
function hasDialogAbove(dialogRef) {
	const dialog = dialogRef.current;
	if (!dialog) return false;
	if (!isElementMarked(dialog)) return false;
	const cleanups = openDialogs.get(dialogRef)?.getOutsideCleanups?.();
	let openedAfterDialog = false;
	let isAboveEarlierDialog = false;
	for (const [openDialogRef, options] of openDialogs) {
		if (openDialogRef === dialogRef) {
			openedAfterDialog = true;
			continue;
		}
		const openDialog = openDialogRef.current;
		if (!openDialog) continue;
		if (!isElementMarkedBy(dialog, options.getOutsideCleanups?.())) continue;
		if (openedAfterDialog) return true;
		if (!isElementMarkedBy(openDialog, cleanups)) return true;
		isAboveEarlierDialog = true;
	}
	return !(openedAfterDialog && isAboveEarlierDialog);
}
//#endregion
//#region src/dialog/dialog.tsx
const DpKu70iZ_TagName = "div";
const isSafariBrowser = (0,utils_dist/* isSafari */.nr)();
const openModalPortals = /* @__PURE__ */ new WeakSet();
const escapeKeyPressesThatHid = /* @__PURE__ */ new WeakSet();
function isAlreadyFocusingAnotherElement(dialog) {
	const activeElement = (0,utils_dist/* getActiveElement */.bq)(dialog);
	if (!activeElement) return false;
	if (dialog && (0,utils_dist/* contains */.gR)(dialog, activeElement)) return false;
	if (isHiddenDismiss(activeElement, dialog?.id)) return false;
	if ((0,utils_dist/* isFocusable */.tp)(activeElement)) return true;
	return false;
}
function getDeepestActiveElement(element) {
	let activeElement = element;
	while (activeElement.shadowRoot) {
		const nextElement = activeElement.shadowRoot.activeElement;
		if (!(0,utils_dist/* isElement */.vq)(nextElement)) break;
		activeElement = nextElement;
	}
	return activeElement;
}
function isElementInDialog(element, dialog, disclosureElement) {
	if ((0,utils_dist/* contains */.gR)(dialog, element)) return true;
	if (disclosureElement && (0,utils_dist/* contains */.gR)(disclosureElement, element)) return true;
	return isElementInside(element, dialog);
}
function hasOwnDismiss(dialog) {
	const dismisses = dialog.querySelectorAll("[data-dialog-dismiss]");
	for (const dismiss of dismisses) if (dismiss.closest("[data-dialog]") === dialog) return true;
	return false;
}
function getElementFromProp(prop, focusable = false) {
	if (!prop) return null;
	const element = "current" in prop ? prop.current : prop;
	if (!element) return null;
	if (focusable) return (0,utils_dist/* isFocusable */.tp)(element) ? element : null;
	return element;
}
/**
* Returns default modal portals that are later in the same root and still
* pending in the current layout pass. Portals in the established stack are
* excluded so this only coordinates the dialogs opening in the same cohort.
*/
function getLaterOpenModalPortals(dialog) {
	if (!dialog.isConnected) return [];
	const root = dialog.getRootNode();
	const dialogs = root.querySelectorAll("[data-dialog][data-dialog-portal][data-open]");
	const portals = [];
	let foundDialog = false;
	for (const currentDialog of dialogs) {
		if (currentDialog === dialog) {
			foundDialog = true;
			continue;
		}
		if (!foundDialog) continue;
		const portalId = currentDialog.getAttribute("data-dialog-portal");
		if (!portalId) continue;
		const portal = root.getElementById(portalId);
		if (!portal || !(0,utils_dist/* contains */.gR)(portal, currentDialog)) continue;
		if (openModalPortals.has(portal)) continue;
		portals.push(portal);
	}
	return portals;
}
/**
* Returns props to create a `Dialog` component.
* @see https://ariakit.com/components/dialog
* @example
* ```jsx
* const store = useDialogStore();
* const props = useDialog({ store });
* <Role {...props}>Dialog</Role>
* ```
*/
const useDialog = (0,react_utils_dist/* createHook */.ab)(function useDialog({ store: storeProp, open: openProp, onClose, focusable = true, modal = true, portal = modal, backdrop = modal, hideOnEscape = true, hideOnInteractOutside = true, getPersistentElements, preventBodyScroll = modal, autoFocusOnShow = true, autoFocusOnHide = true, initialFocus, finalFocus, unmountOnHide, unstable_treeSnapshotKey, ...props }) {
	const context = (0,dialog_context/* useDialogProviderContext */.cH)();
	const ref = (0,react.useRef)(null);
	const backdropRef = (0,react.useRef)(null);
	const hasDefaultModalPortal = modal && portal && !props.portalElement;
	const handlingHideRef = (0,react.useRef)(false);
	const dispatchClose = (0,react_utils_dist/* useEvent */._q)(() => {
		const dialog = ref.current;
		if (!dialog) return true;
		const event = new Event("close", {
			bubbles: false,
			cancelable: true
		});
		if (onClose) dialog.addEventListener("close", onClose, { once: true });
		dialog.dispatchEvent(event);
		return !event.defaultPrevented;
	});
	const store = useDialogStore({
		store: storeProp || context,
		open: openProp,
		setOpen(open) {
			if (open) return;
			if (handlingHideRef.current) return;
			if (dispatchClose()) return;
			store.setOpen(true);
		}
	});
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		return store.unstable_onHideRequest((hide) => {
			if (handlingHideRef.current) return;
			handlingHideRef.current = true;
			try {
				if (!dispatchClose()) return;
				hide();
			} finally {
				handlingHideRef.current = false;
			}
		});
	}, [store, dispatchClose]);
	const { portalRef, portalNode, domReady } = (0,react_utils_dist/* usePortalRef */.TE)(portal, props.portalRef);
	const preserveTabOrderProp = props.preserveTabOrder;
	const preserveTabOrder = (0,dist/* useStoreState */.O$)(store, ["mounted"], (state) => preserveTabOrderProp && !modal && state.mounted);
	const id = (0,react_utils_dist/* useId */.Bi)(props.id);
	const open = (0,dist/* useStoreState */.O$)(store, "open");
	const mounted = (0,dist/* useStoreState */.O$)(store, "mounted");
	const contentElement = (0,dist/* useStoreState */.O$)(store, "contentElement");
	const hidden = (0,disclosure_content/* isHidden */.dK)(mounted, props.hidden, props.alwaysVisible);
	usePreventBodyScroll(contentElement, id, preventBodyScroll && !hidden);
	const interactedOutsideRef = (0,react.useRef)(false);
	const focusedStoreRef = (0,react.useRef)(null);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		return (0,store_dist/* sync */.OH)(store, ["open"], (state) => {
			if (!state.open) {
				focusedStoreRef.current = null;
				return;
			}
			interactedOutsideRef.current = false;
			if (focusedStoreRef.current === store) return;
			focusedStoreRef.current = null;
		});
	}, [store]);
	useHideOnInteractOutside({
		store,
		hideOnInteractOutside,
		domReady,
		interactedOutsideRef,
		focusedStoreRef
	});
	const { wrapElement, nestedDialogs } = useNestedDialogs(store);
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, wrapElement, [wrapElement]);
	const lastMousedownRef = (0,react.useRef)(null);
	if (isSafariBrowser) (0,react.useEffect)(() => {
		if (!domReady) return;
		const dialog = ref.current;
		if (!dialog) return;
		const doc = (0,utils_dist/* getDocument */.YE)(dialog);
		const onMousedown = (event) => {
			lastMousedownRef.current = event.target;
		};
		doc.addEventListener("mousedown", onMousedown, true);
		return () => {
			doc.removeEventListener("mousedown", onMousedown, true);
		};
	}, [domReady]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!open) return;
		if (focusedStoreRef.current === store) return;
		const dialog = ref.current;
		const hasNamedDisclosure = () => {
			const { disclosureElement } = store.getState();
			if (!disclosureElement) return false;
			if (isCapturedDisclosure(disclosureElement)) return false;
			if (!disclosureElement.isConnected) return false;
			if (dialog && (0,utils_dist/* contains */.gR)(dialog, disclosureElement)) return false;
			return true;
		};
		if (hasNamedDisclosure()) return;
		const setCapturedDisclosure = (element) => {
			captureDisclosure(element);
			store.setDisclosureElement(element);
		};
		const activeElement = (0,utils_dist/* getActiveElement */.bq)(dialog, { activeDescendant: true });
		if (!activeElement) return;
		if (activeElement.tagName === "BODY") {
			const fallback = lastMousedownRef.current;
			lastMousedownRef.current = null;
			if (!fallback?.isConnected) return;
			if (!(0,utils_dist/* isFocusable */.tp)(fallback)) return;
			if (dialog && (0,utils_dist/* contains */.gR)(dialog, fallback)) return;
			setCapturedDisclosure(fallback);
			return;
		}
		if (dialog && (0,utils_dist/* contains */.gR)(dialog, activeElement)) return;
		setCapturedDisclosure(activeElement);
	}, [store, open]);
	(0,react.useEffect)(() => {
		if (!mounted) return;
		if (!domReady) return;
		const dialog = ref.current;
		if (!dialog) return;
		const win = (0,utils_dist/* getWindow */.zk)(dialog);
		const viewport = win.visualViewport || win;
		const setViewportHeight = () => {
			const height = win.visualViewport?.height ?? win.innerHeight;
			dialog.style.setProperty("--dialog-viewport-height", `${height}px`);
		};
		setViewportHeight();
		viewport.addEventListener("resize", setViewportHeight);
		return () => {
			viewport.removeEventListener("resize", setViewportHeight);
		};
	}, [mounted, domReady]);
	const [needsHiddenDismiss, setNeedsHiddenDismiss] = (0,react.useState)(false);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!(modal && open && domReady) || !contentElement) {
			setNeedsHiddenDismiss(false);
			return;
		}
		const update = () => {
			setNeedsHiddenDismiss(!hasOwnDismiss(contentElement));
		};
		update();
		const { MutationObserver } = (0,utils_dist/* getWindow */.zk)(contentElement);
		const observer = new MutationObserver(update);
		observer.observe(contentElement, {
			childList: true,
			subtree: true
		});
		return () => observer.disconnect();
	}, [
		modal,
		open,
		domReady,
		contentElement
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!supportsInert()) return;
		if (open) return;
		if (!mounted) return;
		if (!domReady) return;
		const dialog = ref.current;
		if (!dialog) return;
		return disableTree(dialog);
	}, [
		open,
		mounted,
		domReady
	]);
	const canTakeTreeSnapshot = open && domReady;
	const openingCohortRef = (0,react.useRef)(null);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!id || !hasDefaultModalPortal || !canTakeTreeSnapshot || !portalNode) {
			openingCohortRef.current = null;
			return;
		}
		const dialog = ref.current;
		if (!dialog || !(0,utils_dist/* contains */.gR)(portalNode, dialog)) {
			openingCohortRef.current = null;
			return;
		}
		if (openingCohortRef.current?.portal !== portalNode) openingCohortRef.current = {
			portal: portalNode,
			peers: getLaterOpenModalPortals(dialog)
		};
		openModalPortals.add(portalNode);
		return () => {
			openModalPortals.delete(portalNode);
		};
	}, [
		id,
		canTakeTreeSnapshot,
		hasDefaultModalPortal,
		portalNode
	]);
	const isOpenAndReady = !!canTakeTreeSnapshot;
	const [earlierDialogElementChanges, setEarlierDialogElementChanges] = (0,react.useState)(0);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!isOpenAndReady) return;
		return addOpenDialog(ref, {
			getOutsideCleanups: () => treeRef.current?.outsideCleanups,
			onEarlierDialogElementChange: () => {
				setEarlierDialogElementChanges((count) => count + 1);
			}
		});
	}, [isOpenAndReady]);
	const previousContentElementRef = (0,react.useRef)(null);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!isOpenAndReady) {
			previousContentElementRef.current = null;
			return;
		}
		if (!contentElement) return;
		const previousContentElement = previousContentElementRef.current;
		previousContentElementRef.current = contentElement;
		if (!previousContentElement) return;
		if (previousContentElement === contentElement) return;
		notifyOpenDialogElementChange(ref);
	}, [isOpenAndReady, contentElement]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!id) return;
		if (!canTakeTreeSnapshot) return;
		const dialog = ref.current;
		return createWalkTreeSnapshot(id, [dialog]);
	}, [
		id,
		canTakeTreeSnapshot,
		unstable_treeSnapshotKey
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!id) return;
		if (!canTakeTreeSnapshot) return;
		const earlierDialogs = getEarlierOpenDialogElements(ref);
		return addToWalkTreeSnapshot(id, earlierDialogs);
	}, [
		id,
		canTakeTreeSnapshot,
		earlierDialogElementChanges
	]);
	const getPersistentElementsProp = (0,react_utils_dist/* useEvent */._q)(getPersistentElements);
	const treeRef = (0,react.useRef)(null);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		return () => {
			const tree = treeRef.current;
			if (!tree) return;
			treeRef.current = null;
			tree.restoreInsideMarks();
			restoreCleanups(tree.outsideCleanups);
		};
	}, [
		id,
		store,
		canTakeTreeSnapshot,
		contentElement,
		modal,
		hasDefaultModalPortal,
		getPersistentElementsProp,
		unstable_treeSnapshotKey
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!id) return;
		if (!canTakeTreeSnapshot) return;
		const { disclosureElement } = store.getState();
		const dialog = contentElement ?? ref.current;
		if (!dialog) return;
		const allElements = [
			dialog,
			...getPersistentElementsProp() || [],
			...openingCohortRef.current?.peers || [],
			...nestedDialogs.map((dialog) => dialog.getState().contentElement)
		];
		const previousTree = treeRef.current;
		previousTree?.restoreInsideMarks();
		const restoreInsideMarks = markTreeInside(dialog, allElements);
		const outsideCleanups = modal ? markAndDisableTreeOutside(id, allElements, previousTree?.outsideCleanups) : markTreeOutside(id, [disclosureElement, ...allElements], previousTree?.outsideCleanups);
		treeRef.current = {
			restoreInsideMarks,
			outsideCleanups
		};
	}, [
		id,
		store,
		canTakeTreeSnapshot,
		contentElement,
		modal,
		hasDefaultModalPortal,
		getPersistentElementsProp,
		nestedDialogs,
		unstable_treeSnapshotKey,
		earlierDialogElementChanges
	]);
	const mayAutoFocusOnShow = !!autoFocusOnShow;
	const autoFocusOnShowProp = (0,react_utils_dist/* useBooleanEvent */.O4)(autoFocusOnShow);
	const [autoFocusEnabled, setAutoFocusEnabled] = (0,react.useState)(false);
	(0,react.useEffect)(() => {
		if (!open) return;
		if (!mayAutoFocusOnShow) return;
		if (!domReady) return;
		if (!contentElement?.isConnected) return;
		const element = getElementFromProp(initialFocus, true) || contentElement.querySelector("[data-autofocus=true],[autofocus]") || (0,utils_dist/* getFirstTabbableIn */.LC)(contentElement, true, portal && preserveTabOrder) || contentElement;
		const isElementFocusable = (0,utils_dist/* isFocusable */.tp)(element);
		if (!autoFocusOnShowProp(isElementFocusable ? element : null)) return;
		setAutoFocusEnabled(true);
		queueMicrotask(() => {
			const { open, disclosureElement } = store.getState();
			if (!open) return;
			const activeElement = (0,utils_dist/* getActiveElement */.bq)(contentElement, { frame: false });
			const deepestActiveElement = activeElement && getDeepestActiveElement(activeElement);
			if (focusedStoreRef.current === store && activeElement && deepestActiveElement && (0,utils_dist/* isFocusable */.tp)(deepestActiveElement) && !isElementInDialog(activeElement, contentElement, disclosureElement) && !isElementInDialog(deepestActiveElement, contentElement, disclosureElement)) return;
			if ((0,utils_dist/* isFocusable */.tp)(element)) element.scrollIntoView({
				block: "nearest",
				inline: "nearest"
			});
			element.focus({ preventScroll: true });
		});
	}, [
		open,
		mayAutoFocusOnShow,
		domReady,
		contentElement,
		initialFocus,
		portal,
		preserveTabOrder,
		store,
		autoFocusOnShowProp,
		focusedStoreRef
	]);
	const mayAutoFocusOnHide = !!autoFocusOnHide;
	const autoFocusOnHideProp = (0,react_utils_dist/* useBooleanEvent */.O4)(autoFocusOnHide);
	const [hasOpened, setHasOpened] = (0,react.useState)(false);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!open) return;
		setHasOpened(true);
		return () => setHasOpened(false);
	}, [open]);
	const focusOnHide = (0,react.useCallback)((dialog, retry = true) => {
		if (interactedOutsideRef.current) return;
		const { disclosureElement } = store.getState();
		if (isAlreadyFocusingAnotherElement(dialog)) return;
		let element = getElementFromProp(finalFocus) || disclosureElement;
		if (element?.id) {
			const doc = (0,utils_dist/* getDocument */.YE)(element);
			const selector = `[aria-activedescendant="${element.id}"]`;
			const composite = doc.querySelector(selector);
			if (composite) element = composite;
		}
		if (element && !(0,utils_dist/* isFocusable */.tp)(element)) {
			const maybeParentDialog = element.closest("[data-dialog]");
			if (maybeParentDialog?.id) {
				const doc = (0,utils_dist/* getDocument */.YE)(maybeParentDialog);
				const selector = `[aria-controls~="${maybeParentDialog.id}"]`;
				const control = doc.querySelector(selector);
				if (control) element = control;
			}
		}
		const isElementFocusable = element && (0,utils_dist/* isFocusable */.tp)(element);
		if (!isElementFocusable && retry) {
			requestAnimationFrame(() => focusOnHide(dialog, false));
			return;
		}
		if (!autoFocusOnHideProp(isElementFocusable ? element : null)) return;
		if (!isElementFocusable) return;
		element?.focus();
	}, [
		store,
		finalFocus,
		autoFocusOnHideProp
	]);
	const focusedOnHideRef = (0,react.useRef)(false);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (open) return;
		if (!hasOpened) return;
		if (!mayAutoFocusOnHide) return;
		const dialog = ref.current;
		focusedOnHideRef.current = true;
		focusOnHide(dialog);
	}, [
		open,
		hasOpened,
		domReady,
		mayAutoFocusOnHide,
		focusOnHide
	]);
	(0,react.useEffect)(() => {
		if (!hasOpened) return;
		if (!mayAutoFocusOnHide) return;
		const dialog = ref.current;
		return () => {
			if (focusedOnHideRef.current) {
				focusedOnHideRef.current = false;
				return;
			}
			focusOnHide(dialog);
		};
	}, [
		hasOpened,
		mayAutoFocusOnHide,
		focusOnHide
	]);
	const hideOnEscapeProp = (0,react_utils_dist/* useBooleanEvent */.O4)(hideOnEscape);
	const [escapeEvents] = (0,react.useState)(() => /* @__PURE__ */ new WeakMap());
	const [escapeKeyPresses] = (0,react.useState)(() => /* @__PURE__ */ new WeakMap());
	const onKeyDownProp = props.onKeyDown;
	const onKeyDownCaptureProp = props.onKeyDownCapture;
	const onFocusCaptureProp = props.onFocusCapture;
	const onFocusCapture = (0,react_utils_dist/* useEvent */._q)((event) => {
		onFocusCaptureProp?.(event);
		if (!store.getState().open) return;
		const target = event.target;
		if (!(0,utils_dist/* isNode */.Ll)(target)) return;
		if (!(0,utils_dist/* contains */.gR)(event.currentTarget, target)) return;
		focusedStoreRef.current = store;
	});
	const acceptEscape = (0,react_utils_dist/* useEvent */._q)((event) => {
		if (event.key !== "Escape") return false;
		if (!event.bubbles) return false;
		const result = escapeEvents.get(event);
		if (result) {
			if (event.defaultPrevented && !result.defaultPrevented) return false;
			return result.accepted;
		}
		if (event.defaultPrevented) return false;
		const dialog = ref.current;
		if (!mounted) return false;
		if (!dialog) return false;
		const source = (0,CDh2UhnG.n)(event);
		let keyPress = escapeKeyPresses.get(source);
		if (!keyPress) {
			keyPress = {
				accepted: !hasDialogAbove(ref) && !escapeKeyPressesThatHid.has(source) && hideOnEscapeProp(event),
				hidden: false
			};
			escapeKeyPresses.set(source, keyPress);
		}
		escapeEvents.set(event, {
			accepted: keyPress.accepted,
			defaultPrevented: event.defaultPrevented
		});
		return keyPress.accepted;
	});
	const hideOnEscapeEvent = (0,react_utils_dist/* useEvent */._q)((event) => {
		const accepted = acceptEscape(event);
		escapeEvents.delete(event);
		if (!accepted) return false;
		const source = (0,CDh2UhnG.n)(event);
		const keyPress = escapeKeyPresses.get(source);
		if (keyPress?.hidden) return true;
		if (keyPress) keyPress.hidden = true;
		escapeKeyPressesThatHid.add(source);
		store.hide();
		return true;
	});
	const onKeyDown = (0,react_utils_dist/* useEvent */._q)((event) => {
		const nativeEvent = event.nativeEvent;
		const wasPropagationStopped = nativeEvent.cancelBubble;
		onKeyDownProp?.(event);
		if (wasPropagationStopped) {
			escapeEvents.delete(nativeEvent);
			return;
		}
		if (!hideOnEscapeEvent(nativeEvent)) return;
		event.stopPropagation();
	});
	const onKeyDownCapture = (0,react_utils_dist/* useEvent */._q)((event) => {
		const nativeEvent = event.nativeEvent;
		const wasPropagationStopped = nativeEvent.cancelBubble;
		onKeyDownCaptureProp?.(event);
		if (wasPropagationStopped) {
			escapeEvents.delete(nativeEvent);
			return;
		}
		if (!acceptEscape(nativeEvent)) return;
		if (!(event.isPropagationStopped() || nativeEvent.cancelBubble)) return;
		hideOnEscapeEvent(nativeEvent);
	});
	(0,react.useEffect)(() => {
		if (!domReady) return;
		if (!mounted) return;
		const onDocumentKeyDownCapture = (event) => {
			if (event.key !== "Escape") return;
			if (!event.bubbles) return;
			if (event.cancelBubble) {
				escapeEvents.delete(event);
				return;
			}
			if (escapeEvents.has(event)) return;
			const dialog = ref.current;
			if (!dialog) return;
			const targets = [event.target, event.composedPath()[0]].filter(utils_dist/* isNode */.Ll);
			const { disclosureElement } = store.getState();
			const isValidTarget = (target) => {
				if ((0,utils_dist/* isElement */.vq)(target) && target.tagName === "BODY") return true;
				if ((0,utils_dist/* contains */.gR)(dialog, target)) return true;
				if (!disclosureElement) return true;
				if ((0,utils_dist/* contains */.gR)(disclosureElement, target)) return true;
				if ((0,utils_dist/* isElement */.vq)(target) && isElementMarked(target, dialog.id)) return true;
				if ((0,utils_dist/* isElement */.vq)(target) && isHiddenDismiss(target, dialog.id)) return true;
				return false;
			};
			if (!targets.some(isValidTarget)) return;
			if (!acceptEscape(event)) return;
			if (!event.cancelBubble) return;
			hideOnEscapeEvent(event);
		};
		const onDocumentKeyDown = (event) => {
			if (!escapeEvents.has(event)) return;
			if (event.cancelBubble) {
				escapeEvents.delete(event);
				return;
			}
			if (!hideOnEscapeEvent(event)) return;
			event.stopPropagation();
		};
		const win = contentElement ? (0,utils_dist/* getWindow */.zk)(contentElement) : void 0;
		return (0,utils_dist/* chain */.cy)((0,utils_dist/* addGlobalEventListener */.dg)("keydown", onDocumentKeyDownCapture, true, win), (0,utils_dist/* addGlobalEventListener */.dg)("keydown", onDocumentKeyDown, false, win));
	}, [
		store,
		domReady,
		mounted,
		contentElement,
		hideOnEscapeEvent,
		acceptEscape,
		escapeEvents
	]);
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(HeadingLevel, {
		level: modal ? 1 : void 0,
		children: element
	}), [modal]);
	const hiddenProp = props.hidden;
	const alwaysVisible = props.alwaysVisible;
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [needsHiddenDismiss && /* @__PURE__ */ (0,jsx_runtime.jsx)("button", {
		type: "button",
		tabIndex: -1,
		"data-dialog-hidden-dismiss": id || "",
		style: getVisuallyHiddenStyle(),
		onClick: store.hide,
		children: "Dismiss popup"
	}), element] }), [
		needsHiddenDismiss,
		id,
		store
	]);
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [!!backdrop && /* @__PURE__ */ (0,jsx_runtime.jsx)(DialogBackdrop, {
		store,
		backdrop,
		backdropRef,
		hidden: hiddenProp,
		alwaysVisible
	}), element] }), [
		store,
		backdrop,
		hiddenProp,
		alwaysVisible
	]);
	const [headingId, setHeadingId] = (0,react.useState)();
	const [descriptionId, setDescriptionId] = (0,react.useState)();
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(dialog_context/* DialogScopedContextProvider */.XP, {
		value: store,
		children: /* @__PURE__ */ (0,jsx_runtime.jsx)(dialog_context/* DialogHeadingContext */.YH.Provider, {
			value: setHeadingId,
			children: /* @__PURE__ */ (0,jsx_runtime.jsx)(dialog_context/* DialogDescriptionContext */.oL.Provider, {
				value: setDescriptionId,
				children: element
			})
		})
	}), [store]);
	props = {
		"data-dialog": "",
		role: "dialog",
		tabIndex: focusable ? -1 : void 0,
		"aria-labelledby": props["aria-label"] != null ? void 0 : headingId,
		"aria-describedby": descriptionId,
		...props,
		"data-dialog-portal": hasDefaultModalPortal ? portalNode?.id : void 0,
		id,
		ref: (0,react_utils_dist/* useMergeRefs */.SV)(ref, props.ref),
		onFocusCapture,
		onKeyDown,
		onKeyDownCapture
	};
	props = useFocusableContainer({
		...props,
		autoFocusOnShow: autoFocusEnabled
	});
	props = (0,disclosure_content/* useDisclosureContent */.aT)({
		store,
		...props,
		unstable_otherElementRef: backdropRef
	});
	props = (0,focusable_focusable/* useFocusable */.W)({
		...props,
		focusable
	});
	props = usePortal({
		portal,
		...props,
		portalRef,
		preserveTabOrder
	});
	return props;
});
function createDialogComponent(Component, useProviderContext = dialog_context/* useDialogProviderContext */.cH) {
	return (0,react_utils_dist/* forwardRef */.Rf)(function DialogComponent(props) {
		const context = useProviderContext();
		const store = props.store || context;
		if (!(0,dist/* useStoreState */.O$)(store, ["mounted"], (state) => !props.unmountOnHide || state?.mounted || !!props.open)) return null;
		return /* @__PURE__ */ (0,jsx_runtime.jsx)(Component, { ...props });
	});
}
const DialogWithStore = createDialogComponent((0,react_utils_dist/* forwardRef */.Rf)(function DialogImpl(props) {
	const htmlProps = useDialog(props);
	return (0,react_utils_dist/* createElement */.n)(DpKu70iZ_TagName, htmlProps);
}), dialog_context/* useDialogProviderContext */.cH);
const DialogWithInternalStore = (0,react_utils_dist/* forwardRef */.Rf)(function DialogWithInternalStore(props) {
	const store = useDialogStore({ open: props.open });
	return /* @__PURE__ */ (0,jsx_runtime.jsx)(DialogWithStore, {
		...props,
		store
	});
});
/**
* Renders a dialog similar to the native `dialog` element that's rendered in a
* [`portal`](https://ariakit.com/reference/dialog#portal) by default.
*
* The dialog can be either
* [`modal`](https://ariakit.com/reference/dialog#modal) or non-modal. The
* visibility state can be controlled with the
* [`open`](https://ariakit.com/reference/dialog#open) and
* [`onClose`](https://ariakit.com/reference/dialog#onclose) props.
* @see https://ariakit.com/components/dialog
* @example
* ```jsx {4-6}
* const [open, setOpen] = useState(false);
*
* <button onClick={() => setOpen(true)}>Open dialog</button>
* <Dialog open={open} onClose={() => setOpen(false)}>
*   Dialog
* </Dialog>
* ```
*/
const Dialog = (0,react_utils_dist/* forwardRef */.Rf)(function Dialog(props) {
	const context = (0,dialog_context/* useDialogProviderContext */.cH)();
	if (props.store || context || !props.unmountOnHide) return /* @__PURE__ */ (0,jsx_runtime.jsx)(DialogWithStore, { ...props });
	return /* @__PURE__ */ (0,jsx_runtime.jsx)(DialogWithInternalStore, { ...props });
});
//#endregion


//# sourceMappingURL=DpKu70iZ.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/B3k5_R1L.js
"use client";
//#region src/popover/__utils.ts
function getBasePlacement(placement) {
	return placement.split("-")[0];
}
//#endregion


//# sourceMappingURL=B3k5_R1L.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.mjs
var floating_ui_utils = __webpack_require__("../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.mjs");
;// ../../node_modules/.pnpm/@floating-ui+core@1.8.0/node_modules/@floating-ui/core/dist/floating-ui.core.mjs



function computeCoordsFromPlacement(_ref, placement, rtl) {
  let {
    reference,
    floating
  } = _ref;
  const sideAxis = (0,floating_ui_utils/* getSideAxis */.TV)(placement);
  const alignmentAxis = (0,floating_ui_utils/* getAlignmentAxis */.Dz)(placement);
  const alignLength = (0,floating_ui_utils/* getAxisLength */.sq)(alignmentAxis);
  const side = (0,floating_ui_utils/* getSide */.C0)(placement);
  const isVertical = sideAxis === 'y';
  const commonX = reference.x + reference.width / 2 - floating.width / 2;
  const commonY = reference.y + reference.height / 2 - floating.height / 2;
  const commonAlign = reference[alignLength] / 2 - floating[alignLength] / 2;
  let coords;
  switch (side) {
    case 'top':
      coords = {
        x: commonX,
        y: reference.y - floating.height
      };
      break;
    case 'bottom':
      coords = {
        x: commonX,
        y: reference.y + reference.height
      };
      break;
    case 'right':
      coords = {
        x: reference.x + reference.width,
        y: commonY
      };
      break;
    case 'left':
      coords = {
        x: reference.x - floating.width,
        y: commonY
      };
      break;
    default:
      coords = {
        x: reference.x,
        y: reference.y
      };
  }
  const alignment = (0,floating_ui_utils/* getAlignment */.Sg)(placement);
  if (alignment) {
    coords[alignmentAxis] += commonAlign * (alignment === 'end' ? 1 : -1) * (rtl && isVertical ? -1 : 1);
  }
  return coords;
}

/**
 * Resolves with an object of overflow side offsets that determine how much the
 * element is overflowing a given clipping boundary on each side.
 * - positive = overflowing the boundary by that number of pixels
 * - negative = how many pixels left before it will overflow
 * - 0 = lies flush with the boundary
 * @see https://floating-ui.com/docs/detectOverflow
 */
async function detectOverflow(state, options) {
  var _await$platform$isEle;
  if (options === void 0) {
    options = {};
  }
  const {
    x,
    y,
    platform,
    rects,
    elements,
    strategy
  } = state;
  const {
    boundary = 'clippingAncestors',
    rootBoundary = 'viewport',
    elementContext = 'floating',
    altBoundary = false,
    padding = 0
  } = (0,floating_ui_utils/* evaluate */._3)(options, state);
  const paddingObject = (0,floating_ui_utils/* getPaddingObject */.nI)(padding);
  const altContext = elementContext === 'floating' ? 'reference' : 'floating';
  const element = elements[altBoundary ? altContext : elementContext];
  const clippingClientRect = (0,floating_ui_utils/* rectToClientRect */.B1)(await platform.getClippingRect({
    element: ((_await$platform$isEle = await (platform.isElement == null ? void 0 : platform.isElement(element))) != null ? _await$platform$isEle : true) ? element : element.contextElement || (await (platform.getDocumentElement == null ? void 0 : platform.getDocumentElement(elements.floating))),
    boundary,
    rootBoundary,
    strategy
  }));
  const rect = elementContext === 'floating' ? {
    x,
    y,
    width: rects.floating.width,
    height: rects.floating.height
  } : rects.reference;
  const offsetParent = await (platform.getOffsetParent == null ? void 0 : platform.getOffsetParent(elements.floating));
  const offsetScale = (await (platform.isElement == null ? void 0 : platform.isElement(offsetParent))) && (await (platform.getScale == null ? void 0 : platform.getScale(offsetParent))) || {
    x: 1,
    y: 1
  };
  const elementClientRect = (0,floating_ui_utils/* rectToClientRect */.B1)(platform.convertOffsetParentRelativeRectToViewportRelativeRect ? await platform.convertOffsetParentRelativeRectToViewportRelativeRect({
    elements,
    rect,
    offsetParent,
    strategy
  }) : rect);
  return {
    top: (clippingClientRect.top - elementClientRect.top + paddingObject.top) / offsetScale.y,
    bottom: (elementClientRect.bottom - clippingClientRect.bottom + paddingObject.bottom) / offsetScale.y,
    left: (clippingClientRect.left - elementClientRect.left + paddingObject.left) / offsetScale.x,
    right: (elementClientRect.right - clippingClientRect.right + paddingObject.right) / offsetScale.x
  };
}

// Maximum number of resets that can occur before bailing to avoid infinite reset loops.
const MAX_RESET_COUNT = 50;

/**
 * Computes the `x` and `y` coordinates that will place the floating element
 * next to a given reference element.
 *
 * This export does not have any `platform` interface logic. You will need to
 * write one for the platform you are using Floating UI with.
 */
const computePosition = async (reference, floating, config) => {
  const {
    placement = 'bottom',
    strategy = 'absolute',
    middleware = [],
    platform
  } = config;
  const platformWithDetectOverflow = platform.detectOverflow ? platform : {
    ...platform,
    detectOverflow
  };
  const rtl = await (platform.isRTL == null ? void 0 : platform.isRTL(floating));
  let rects = await platform.getElementRects({
    reference,
    floating,
    strategy
  });
  let {
    x,
    y
  } = computeCoordsFromPlacement(rects, placement, rtl);
  let statefulPlacement = placement;
  let resetCount = 0;
  const middlewareData = {};
  for (let i = 0; i < middleware.length; i++) {
    const currentMiddleware = middleware[i];
    if (!currentMiddleware) {
      continue;
    }
    const {
      name,
      fn
    } = currentMiddleware;
    const {
      x: nextX,
      y: nextY,
      data,
      reset
    } = await fn({
      x,
      y,
      initialPlacement: placement,
      placement: statefulPlacement,
      strategy,
      middlewareData,
      rects,
      platform: platformWithDetectOverflow,
      elements: {
        reference,
        floating
      }
    });
    x = nextX != null ? nextX : x;
    y = nextY != null ? nextY : y;
    middlewareData[name] = {
      ...middlewareData[name],
      ...data
    };
    if (reset && resetCount < MAX_RESET_COUNT) {
      resetCount++;
      if (typeof reset === 'object') {
        if (reset.placement) {
          statefulPlacement = reset.placement;
        }
        if (reset.rects) {
          rects = reset.rects === true ? await platform.getElementRects({
            reference,
            floating,
            strategy
          }) : reset.rects;
        }
        ({
          x,
          y
        } = computeCoordsFromPlacement(rects, statefulPlacement, rtl));
      }
      i = -1;
    }
  }
  return {
    x,
    y,
    placement: statefulPlacement,
    strategy,
    middlewareData
  };
};

/**
 * Provides data to position an inner element of the floating element so that it
 * appears centered to the reference element.
 * @see https://floating-ui.com/docs/arrow
 */
const arrow = options => ({
  name: 'arrow',
  options,
  async fn(state) {
    const {
      x,
      y,
      placement,
      rects,
      platform,
      elements,
      middlewareData
    } = state;
    // Since `element` is required, we don't Partial<> the type.
    const {
      element,
      padding = 0
    } = (0,floating_ui_utils/* evaluate */._3)(options, state) || {};
    if (element == null) {
      return {};
    }
    const paddingObject = (0,floating_ui_utils/* getPaddingObject */.nI)(padding);
    const coords = {
      x,
      y
    };
    const axis = (0,floating_ui_utils/* getAlignmentAxis */.Dz)(placement);
    const length = (0,floating_ui_utils/* getAxisLength */.sq)(axis);
    const arrowDimensions = await platform.getDimensions(element);
    const isYAxis = axis === 'y';
    const minProp = isYAxis ? 'top' : 'left';
    const maxProp = isYAxis ? 'bottom' : 'right';
    const clientProp = isYAxis ? 'clientHeight' : 'clientWidth';
    const endDiff = rects.reference[length] + rects.reference[axis] - coords[axis] - rects.floating[length];
    const startDiff = coords[axis] - rects.reference[axis];
    const arrowOffsetParent = await (platform.getOffsetParent == null ? void 0 : platform.getOffsetParent(element));
    let clientSize = arrowOffsetParent ? arrowOffsetParent[clientProp] : 0;

    // DOM platform can return `window` as the `offsetParent`.
    if (!clientSize || !(await (platform.isElement == null ? void 0 : platform.isElement(arrowOffsetParent)))) {
      clientSize = elements.floating[clientProp] || rects.floating[length];
    }
    const centerToReference = endDiff / 2 - startDiff / 2;

    // If the padding is large enough that it causes the arrow to no longer be
    // centered, modify the padding so that it is centered.
    const largestPossiblePadding = clientSize / 2 - arrowDimensions[length] / 2 - 1;
    const minPadding = (0,floating_ui_utils/* min */.jk)(paddingObject[minProp], largestPossiblePadding);
    const maxPadding = (0,floating_ui_utils/* min */.jk)(paddingObject[maxProp], largestPossiblePadding);

    // Make sure the arrow doesn't overflow the floating element if the center
    // point is outside the floating element's bounds.
    const max = clientSize - arrowDimensions[length] - maxPadding;
    const center = clientSize / 2 - arrowDimensions[length] / 2 + centerToReference;
    const offset = (0,floating_ui_utils/* clamp */.qE)(minPadding, center, max);

    // If the reference is small enough that the arrow's padding causes it to
    // to point to nothing for an aligned placement, adjust the offset of the
    // floating element itself. To ensure `shift()` continues to take action,
    // a single reset is performed when this is true.
    const shouldAddOffset = !middlewareData.arrow && (0,floating_ui_utils/* getAlignment */.Sg)(placement) != null && center !== offset && rects.reference[length] / 2 - (center < minPadding ? minPadding : maxPadding) - arrowDimensions[length] / 2 < 0;
    const alignmentOffset = shouldAddOffset ? center < minPadding ? center - minPadding : center - max : 0;
    return {
      [axis]: coords[axis] + alignmentOffset,
      data: {
        [axis]: offset,
        centerOffset: center - offset - alignmentOffset,
        ...(shouldAddOffset && {
          alignmentOffset
        })
      },
      reset: shouldAddOffset
    };
  }
});

function getPlacementList(alignment, autoAlignment, allowedPlacements) {
  const allowedPlacementsSortedByAlignment = alignment ? [...allowedPlacements.filter(placement => getAlignment(placement) === alignment), ...allowedPlacements.filter(placement => getAlignment(placement) !== alignment)] : allowedPlacements.filter(placement => getSide(placement) === placement);
  return allowedPlacementsSortedByAlignment.filter(placement => {
    if (alignment) {
      return getAlignment(placement) === alignment || (autoAlignment ? getOppositeAlignmentPlacement(placement) !== placement : false);
    }
    return true;
  });
}
/**
 * Optimizes the visibility of the floating element by choosing the placement
 * that has the most space available automatically, without needing to specify a
 * preferred placement. Alternative to `flip`.
 * @see https://floating-ui.com/docs/autoPlacement
 */
const autoPlacement = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'autoPlacement',
    options,
    async fn(state) {
      var _middlewareData$autoP, _middlewareData$autoP2, _placementsThatFitOnE;
      const {
        rects,
        middlewareData,
        placement,
        platform,
        elements
      } = state;
      const {
        crossAxis = false,
        alignment,
        allowedPlacements = placements,
        autoAlignment = true,
        ...detectOverflowOptions
      } = evaluate(options, state);
      const placements$1 = alignment !== undefined || allowedPlacements === placements ? getPlacementList(alignment || null, autoAlignment, allowedPlacements) : allowedPlacements;
      const currentIndex = ((_middlewareData$autoP = middlewareData.autoPlacement) == null ? void 0 : _middlewareData$autoP.index) || 0;
      const currentPlacement = placements$1[currentIndex];
      if (currentPlacement == null) {
        return {};
      }

      // Make `computeCoords` start from the right place.
      if (placement !== currentPlacement) {
        return {
          reset: {
            placement: placements$1[0]
          }
        };
      }
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const alignmentSides = getAlignmentSides(currentPlacement, rects, await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating)));
      const currentOverflows = [overflow[getSide(currentPlacement)], overflow[alignmentSides[0]], overflow[alignmentSides[1]]];
      const allOverflows = [...(((_middlewareData$autoP2 = middlewareData.autoPlacement) == null ? void 0 : _middlewareData$autoP2.overflows) || []), {
        placement: currentPlacement,
        overflows: currentOverflows
      }];
      const nextPlacement = placements$1[currentIndex + 1];

      // There are more placements to check.
      if (nextPlacement) {
        return {
          data: {
            index: currentIndex + 1,
            overflows: allOverflows
          },
          reset: {
            placement: nextPlacement
          }
        };
      }
      const placementsSortedByMostSpace = allOverflows.map(d => {
        const alignment = getAlignment(d.placement);
        return [d.placement, alignment && crossAxis ?
        // Check along the mainAxis and main crossAxis side.
        d.overflows.slice(0, 2).reduce((acc, v) => acc + v, 0) :
        // Check only the mainAxis.
        d.overflows[0], d.overflows];
      }).sort((a, b) => a[1] - b[1]);
      const placementsThatFitOnEachSide = placementsSortedByMostSpace.filter(d => d[2].slice(0,
      // Aligned placements should not check their opposite crossAxis
      // side.
      getAlignment(d[0]) ? 2 : 3).every(v => v <= 0));
      const resetPlacement = ((_placementsThatFitOnE = placementsThatFitOnEachSide[0]) == null ? void 0 : _placementsThatFitOnE[0]) || placementsSortedByMostSpace[0][0];
      if (resetPlacement !== placement) {
        return {
          data: {
            index: currentIndex + 1,
            overflows: allOverflows
          },
          reset: {
            placement: resetPlacement
          }
        };
      }
      return {};
    }
  };
};

/**
 * Optimizes the visibility of the floating element by flipping the `placement`
 * in order to keep it in view when the preferred placement(s) will overflow the
 * clipping boundary. Alternative to `autoPlacement`.
 * @see https://floating-ui.com/docs/flip
 */
const flip = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'flip',
    options,
    async fn(state) {
      var _middlewareData$arrow, _middlewareData$flip;
      const {
        placement,
        middlewareData,
        rects,
        initialPlacement,
        platform,
        elements
      } = state;
      const {
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = true,
        fallbackPlacements: specifiedFallbackPlacements,
        fallbackStrategy = 'bestFit',
        fallbackAxisSideDirection = 'none',
        flipAlignment = true,
        ...detectOverflowOptions
      } = (0,floating_ui_utils/* evaluate */._3)(options, state);

      // If a reset by the arrow was caused due to an alignment offset being
      // added, we should skip any logic now since `flip()` has already done its
      // work.
      // https://github.com/floating-ui/floating-ui/issues/2549#issuecomment-1719601643
      if ((_middlewareData$arrow = middlewareData.arrow) != null && _middlewareData$arrow.alignmentOffset) {
        return {};
      }
      const side = (0,floating_ui_utils/* getSide */.C0)(placement);
      const initialSideAxis = (0,floating_ui_utils/* getSideAxis */.TV)(initialPlacement);
      const isBasePlacement = (0,floating_ui_utils/* getSide */.C0)(initialPlacement) === initialPlacement;
      const rtl = await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating));
      const fallbackPlacements = specifiedFallbackPlacements || (isBasePlacement || !flipAlignment ? [(0,floating_ui_utils/* getOppositePlacement */.bV)(initialPlacement)] : (0,floating_ui_utils/* getExpandedPlacements */.WJ)(initialPlacement));
      const hasFallbackAxisSideDirection = fallbackAxisSideDirection !== 'none';
      if (!specifiedFallbackPlacements && hasFallbackAxisSideDirection) {
        fallbackPlacements.push(...(0,floating_ui_utils/* getOppositeAxisPlacements */.lP)(initialPlacement, flipAlignment, fallbackAxisSideDirection, rtl));
      }
      const placements = [initialPlacement, ...fallbackPlacements];
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const overflows = [];
      let overflowsData = ((_middlewareData$flip = middlewareData.flip) == null ? void 0 : _middlewareData$flip.overflows) || [];
      if (checkMainAxis) {
        overflows.push(overflow[side]);
      }
      if (checkCrossAxis) {
        const sides = (0,floating_ui_utils/* getAlignmentSides */.w7)(placement, rects, rtl);
        overflows.push(overflow[sides[0]], overflow[sides[1]]);
      }
      overflowsData = [...overflowsData, {
        placement,
        overflows
      }];

      // One or more sides is overflowing.
      if (!overflows.every(side => side <= 0)) {
        var _middlewareData$flip2, _overflowsData$filter;
        const nextIndex = (((_middlewareData$flip2 = middlewareData.flip) == null ? void 0 : _middlewareData$flip2.index) || 0) + 1;
        const nextPlacement = placements[nextIndex];
        if (nextPlacement) {
          const ignoreCrossAxisOverflow = checkCrossAxis === 'alignment' ? initialSideAxis !== (0,floating_ui_utils/* getSideAxis */.TV)(nextPlacement) : false;
          if (!ignoreCrossAxisOverflow ||
          // We leave the current main axis only if every placement on that axis
          // overflows the main axis.
          overflowsData.every(d => (0,floating_ui_utils/* getSideAxis */.TV)(d.placement) === initialSideAxis ? d.overflows[0] > 0 : true)) {
            // Try next placement and re-run the lifecycle.
            return {
              data: {
                index: nextIndex,
                overflows: overflowsData
              },
              reset: {
                placement: nextPlacement
              }
            };
          }
        }

        // First, find the candidates that fit on the mainAxis side of overflow,
        // then find the placement that fits the best on the main crossAxis side.
        let resetPlacement = (_overflowsData$filter = overflowsData.filter(d => d.overflows[0] <= 0).sort((a, b) => a.overflows[1] - b.overflows[1])[0]) == null ? void 0 : _overflowsData$filter.placement;

        // Otherwise fallback.
        if (!resetPlacement) {
          switch (fallbackStrategy) {
            case 'bestFit':
              {
                var _overflowsData$filter2;
                const placement = (_overflowsData$filter2 = overflowsData.filter(d => {
                  if (hasFallbackAxisSideDirection) {
                    const currentSideAxis = (0,floating_ui_utils/* getSideAxis */.TV)(d.placement);
                    return currentSideAxis === initialSideAxis ||
                    // Create a bias to the `y` side axis due to horizontal
                    // reading directions favoring greater width.
                    currentSideAxis === 'y';
                  }
                  return true;
                }).map(d => [d.placement, d.overflows.filter(overflow => overflow > 0).reduce((acc, overflow) => acc + overflow, 0)]).sort((a, b) => a[1] - b[1])[0]) == null ? void 0 : _overflowsData$filter2[0];
                if (placement) {
                  resetPlacement = placement;
                }
                break;
              }
            case 'initialPlacement':
              resetPlacement = initialPlacement;
              break;
          }
        }
        if (placement !== resetPlacement) {
          return {
            reset: {
              placement: resetPlacement
            }
          };
        }
      }
      return {};
    }
  };
};

function getSideOffsets(overflow, rect) {
  return {
    top: overflow.top - rect.height,
    right: overflow.right - rect.width,
    bottom: overflow.bottom - rect.height,
    left: overflow.left - rect.width
  };
}
function isAnySideFullyClipped(overflow) {
  return sides.some(side => overflow[side] >= 0);
}
/**
 * Provides data to hide the floating element in applicable situations, such as
 * when it is not in the same clipping context as the reference element.
 * @see https://floating-ui.com/docs/hide
 */
const hide = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'hide',
    options,
    async fn(state) {
      const {
        rects,
        platform
      } = state;
      const {
        strategy = 'referenceHidden',
        ...detectOverflowOptions
      } = evaluate(options, state);
      switch (strategy) {
        case 'referenceHidden':
          {
            const overflow = await platform.detectOverflow(state, {
              ...detectOverflowOptions,
              elementContext: 'reference'
            });
            const offsets = getSideOffsets(overflow, rects.reference);
            return {
              data: {
                referenceHiddenOffsets: offsets,
                referenceHidden: isAnySideFullyClipped(offsets)
              }
            };
          }
        case 'escaped':
          {
            const overflow = await platform.detectOverflow(state, {
              ...detectOverflowOptions,
              altBoundary: true
            });
            const offsets = getSideOffsets(overflow, rects.floating);
            return {
              data: {
                escapedOffsets: offsets,
                escaped: isAnySideFullyClipped(offsets)
              }
            };
          }
        default:
          {
            return {};
          }
      }
    }
  };
};

function getBoundingRect(rects) {
  const minX = min(...rects.map(rect => rect.left));
  const minY = min(...rects.map(rect => rect.top));
  const maxX = max(...rects.map(rect => rect.right));
  const maxY = max(...rects.map(rect => rect.bottom));
  return {
    x: minX,
    y: minY,
    width: maxX - minX,
    height: maxY - minY
  };
}
function getRectsByLine(rects) {
  const sortedRects = rects.slice().sort((a, b) => a.y - b.y);
  const groups = [];
  let prevRect = null;
  for (let i = 0; i < sortedRects.length; i++) {
    const rect = sortedRects[i];
    if (!prevRect || rect.y - prevRect.y > prevRect.height / 2) {
      groups.push([rect]);
    } else {
      groups[groups.length - 1].push(rect);
    }
    prevRect = rect;
  }
  return groups.map(rect => rectToClientRect(getBoundingRect(rect)));
}
/**
 * Provides improved positioning for inline reference elements that can span
 * over multiple lines, such as hyperlinks or range selections.
 * @see https://floating-ui.com/docs/inline
 */
const inline = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'inline',
    options,
    async fn(state) {
      const {
        placement,
        elements,
        rects,
        platform,
        strategy
      } = state;
      // A MouseEvent's client{X,Y} coords can be up to 2 pixels off a
      // ClientRect's bounds, despite the event listener being triggered. A
      // padding of 2 seems to handle this issue.
      const {
        padding = 2,
        x,
        y
      } = evaluate(options, state);
      const nativeClientRects = Array.from((await (platform.getClientRects == null ? void 0 : platform.getClientRects(elements.reference))) || []);

      // No rects (e.g. a hidden or detached reference, or a collapsed range) —
      // keep the existing reference rect rather than resetting to an invalid
      // one with non-finite values.
      if (!nativeClientRects.length) {
        return {};
      }
      const clientRects = getRectsByLine(nativeClientRects);
      const fallback = rectToClientRect(getBoundingRect(nativeClientRects));
      const paddingObject = getPaddingObject(padding);
      function getBoundingClientRect() {
        // There are two rects and they are disjoined.
        if (clientRects.length === 2 && (clientRects[0].left > clientRects[1].right || clientRects[1].left > clientRects[0].right) && x != null && y != null) {
          // Find the first rect in which the point is fully inside.
          return clientRects.find(rect => x > rect.left - paddingObject.left && x < rect.right + paddingObject.right && y > rect.top - paddingObject.top && y < rect.bottom + paddingObject.bottom) || fallback;
        }

        // There are 2 or more connected rects.
        if (clientRects.length >= 2) {
          if (getSideAxis(placement) === 'y') {
            const firstRect = clientRects[0];
            const lastRect = clientRects[clientRects.length - 1];
            const isTop = getSide(placement) === 'top';
            const top = firstRect.top;
            const bottom = lastRect.bottom;
            const left = isTop ? firstRect.left : lastRect.left;
            const right = isTop ? firstRect.right : lastRect.right;
            return rectToClientRect({
              x: left,
              y: top,
              width: right - left,
              height: bottom - top
            });
          }
          const isLeftSide = getSide(placement) === 'left';
          const maxRight = max(...clientRects.map(rect => rect.right));
          const minLeft = min(...clientRects.map(rect => rect.left));
          const measureRects = clientRects.filter(rect => isLeftSide ? rect.left === minLeft : rect.right === maxRight);
          const top = measureRects[0].top;
          const bottom = measureRects[measureRects.length - 1].bottom;
          return rectToClientRect({
            x: minLeft,
            y: top,
            width: maxRight - minLeft,
            height: bottom - top
          });
        }
        return fallback;
      }
      const resetRects = await platform.getElementRects({
        reference: {
          getBoundingClientRect
        },
        floating: elements.floating,
        strategy
      });
      if (rects.reference.x !== resetRects.reference.x || rects.reference.y !== resetRects.reference.y || rects.reference.width !== resetRects.reference.width || rects.reference.height !== resetRects.reference.height) {
        return {
          reset: {
            rects: resetRects
          }
        };
      }
      return {};
    }
  };
};

const originSides = /*#__PURE__*/new Set(['left', 'top']);

// For type backwards-compatibility, the `OffsetOptions` type was also
// Derivable.

async function convertValueToCoords(state, options) {
  const {
    placement,
    platform,
    elements
  } = state;
  const rtl = await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating));
  const side = (0,floating_ui_utils/* getSide */.C0)(placement);
  const alignment = (0,floating_ui_utils/* getAlignment */.Sg)(placement);
  const isVertical = (0,floating_ui_utils/* getSideAxis */.TV)(placement) === 'y';
  const mainAxisMulti = originSides.has(side) ? -1 : 1;
  const crossAxisMulti = rtl && isVertical ? -1 : 1;
  const rawValue = (0,floating_ui_utils/* evaluate */._3)(options, state);

  // eslint-disable-next-line prefer-const
  let {
    mainAxis,
    crossAxis,
    alignmentAxis
  } = typeof rawValue === 'number' ? {
    mainAxis: rawValue,
    crossAxis: 0,
    alignmentAxis: null
  } : {
    mainAxis: rawValue.mainAxis || 0,
    crossAxis: rawValue.crossAxis || 0,
    alignmentAxis: rawValue.alignmentAxis
  };
  if (alignment && typeof alignmentAxis === 'number') {
    crossAxis = alignment === 'end' ? alignmentAxis * -1 : alignmentAxis;
  }
  return isVertical ? {
    x: crossAxis * crossAxisMulti,
    y: mainAxis * mainAxisMulti
  } : {
    x: mainAxis * mainAxisMulti,
    y: crossAxis * crossAxisMulti
  };
}

/**
 * Modifies the placement by translating the floating element along the
 * specified axes.
 * A number (shorthand for `mainAxis` or distance), or an axes configuration
 * object may be passed.
 * @see https://floating-ui.com/docs/offset
 */
const offset = function (options) {
  if (options === void 0) {
    options = 0;
  }
  return {
    name: 'offset',
    options,
    async fn(state) {
      var _middlewareData$offse, _middlewareData$arrow;
      const {
        x,
        y,
        placement,
        middlewareData
      } = state;
      const diffCoords = await convertValueToCoords(state, options);

      // If the placement is the same and the arrow caused an alignment offset
      // then we don't need to change the positioning coordinates.
      if (placement === ((_middlewareData$offse = middlewareData.offset) == null ? void 0 : _middlewareData$offse.placement) && (_middlewareData$arrow = middlewareData.arrow) != null && _middlewareData$arrow.alignmentOffset) {
        return {};
      }
      return {
        x: x + diffCoords.x,
        y: y + diffCoords.y,
        data: {
          ...diffCoords,
          placement
        }
      };
    }
  };
};

/**
 * Optimizes the visibility of the floating element by shifting it in order to
 * keep it in view when it will overflow the clipping boundary.
 * @see https://floating-ui.com/docs/shift
 */
const shift = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'shift',
    options,
    async fn(state) {
      const {
        x,
        y,
        placement,
        platform
      } = state;
      const {
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = false,
        limiter = {
          fn: _ref => {
            let {
              x,
              y
            } = _ref;
            return {
              x,
              y
            };
          }
        },
        ...detectOverflowOptions
      } = (0,floating_ui_utils/* evaluate */._3)(options, state);
      const coords = {
        x,
        y
      };
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const crossAxis = (0,floating_ui_utils/* getSideAxis */.TV)(placement);
      const mainAxis = (0,floating_ui_utils/* getOppositeAxis */.PG)(crossAxis);
      let mainAxisCoord = coords[mainAxis];
      let crossAxisCoord = coords[crossAxis];
      const clampCoord = (axis, coord) => (0,floating_ui_utils/* clamp */.qE)(coord + overflow[axis === 'y' ? 'top' : 'left'], coord, coord - overflow[axis === 'y' ? 'bottom' : 'right']);
      if (checkMainAxis) {
        mainAxisCoord = clampCoord(mainAxis, mainAxisCoord);
      }
      if (checkCrossAxis) {
        crossAxisCoord = clampCoord(crossAxis, crossAxisCoord);
      }
      const limitedCoords = limiter.fn({
        ...state,
        [mainAxis]: mainAxisCoord,
        [crossAxis]: crossAxisCoord
      });
      return {
        ...limitedCoords,
        data: {
          x: limitedCoords.x - x,
          y: limitedCoords.y - y,
          enabled: {
            [mainAxis]: checkMainAxis,
            [crossAxis]: checkCrossAxis
          }
        }
      };
    }
  };
};
/**
 * Built-in `limiter` that will stop `shift()` at a certain point.
 */
const limitShift = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    options,
    fn(state) {
      var _rawOffset$mainAxis, _rawOffset$crossAxis;
      const {
        x,
        y,
        placement,
        rects,
        middlewareData
      } = state;
      const {
        offset = 0,
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = true
      } = (0,floating_ui_utils/* evaluate */._3)(options, state);
      const coords = {
        x,
        y
      };
      const crossAxis = (0,floating_ui_utils/* getSideAxis */.TV)(placement);
      const mainAxis = (0,floating_ui_utils/* getOppositeAxis */.PG)(crossAxis);
      let mainAxisCoord = coords[mainAxis];
      let crossAxisCoord = coords[crossAxis];
      const rawOffset = (0,floating_ui_utils/* evaluate */._3)(offset, state);
      const computedOffset = typeof rawOffset === 'number' ? {
        mainAxis: rawOffset,
        crossAxis: 0
      } : {
        mainAxis: (_rawOffset$mainAxis = rawOffset.mainAxis) != null ? _rawOffset$mainAxis : 0,
        crossAxis: (_rawOffset$crossAxis = rawOffset.crossAxis) != null ? _rawOffset$crossAxis : 0
      };
      if (checkMainAxis) {
        const len = mainAxis === 'y' ? 'height' : 'width';
        const limitMin = rects.reference[mainAxis] - rects.floating[len] + computedOffset.mainAxis;
        const limitMax = rects.reference[mainAxis] + rects.reference[len] - computedOffset.mainAxis;
        if (mainAxisCoord < limitMin) {
          mainAxisCoord = limitMin;
        } else if (mainAxisCoord > limitMax) {
          mainAxisCoord = limitMax;
        }
      }
      if (checkCrossAxis) {
        var _middlewareData$offse, _middlewareData$offse2;
        const len = mainAxis === 'y' ? 'width' : 'height';
        const isOriginSide = originSides.has((0,floating_ui_utils/* getSide */.C0)(placement));
        const limitMin = rects.reference[crossAxis] - rects.floating[len] + (isOriginSide ? ((_middlewareData$offse = middlewareData.offset) == null ? void 0 : _middlewareData$offse[crossAxis]) || 0 : 0) + (isOriginSide ? 0 : computedOffset.crossAxis);
        const limitMax = rects.reference[crossAxis] + rects.reference[len] + (isOriginSide ? 0 : ((_middlewareData$offse2 = middlewareData.offset) == null ? void 0 : _middlewareData$offse2[crossAxis]) || 0) - (isOriginSide ? computedOffset.crossAxis : 0);
        if (crossAxisCoord < limitMin) {
          crossAxisCoord = limitMin;
        } else if (crossAxisCoord > limitMax) {
          crossAxisCoord = limitMax;
        }
      }
      return {
        [mainAxis]: mainAxisCoord,
        [crossAxis]: crossAxisCoord
      };
    }
  };
};

// Method syntax keeps callback parameters bivariant, but expressing the
// explicit `| undefined` required by `exactOptionalPropertyTypes` needs
// property syntax, which is contravariant under `strictFunctionTypes`.
// Extracting the function from a method position restores that bivariance so
// consumers can still assign callbacks with narrower parameter types.

/**
 * Provides data that allows you to change the size of the floating element —
 * for instance, prevent it from overflowing the clipping boundary or match the
 * width of the reference element.
 * @see https://floating-ui.com/docs/size
 */
const size = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'size',
    options,
    async fn(state) {
      const {
        placement,
        rects,
        platform,
        elements
      } = state;
      const {
        apply = () => {},
        ...detectOverflowOptions
      } = (0,floating_ui_utils/* evaluate */._3)(options, state);
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const side = (0,floating_ui_utils/* getSide */.C0)(placement);
      const alignment = (0,floating_ui_utils/* getAlignment */.Sg)(placement);
      const isYAxis = (0,floating_ui_utils/* getSideAxis */.TV)(placement) === 'y';
      const {
        width,
        height
      } = rects.floating;
      let heightSide;
      let widthSide;
      if (side === 'top' || side === 'bottom') {
        heightSide = side;
        widthSide = alignment === ((await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating))) ? 'start' : 'end') ? 'left' : 'right';
      } else {
        widthSide = side;
        heightSide = alignment === 'end' ? 'top' : 'bottom';
      }
      const maximumClippingHeight = height - overflow.top - overflow.bottom;
      const maximumClippingWidth = width - overflow.left - overflow.right;
      const overflowAvailableHeight = (0,floating_ui_utils/* min */.jk)(height - overflow[heightSide], maximumClippingHeight);
      const overflowAvailableWidth = (0,floating_ui_utils/* min */.jk)(width - overflow[widthSide], maximumClippingWidth);
      const shiftData = state.middlewareData.shift;
      const noShift = !shiftData;
      let availableHeight = overflowAvailableHeight;
      let availableWidth = overflowAvailableWidth;
      if (shiftData != null && shiftData.enabled.x) {
        availableWidth = maximumClippingWidth;
      }
      if (shiftData != null && shiftData.enabled.y) {
        availableHeight = maximumClippingHeight;
      }
      if (noShift && !alignment) {
        if (isYAxis) {
          availableWidth = width - 2 * (0,floating_ui_utils/* max */.T9)(overflow.left, overflow.right);
        } else {
          availableHeight = height - 2 * (0,floating_ui_utils/* max */.T9)(overflow.top, overflow.bottom);
        }
      }
      await apply({
        ...state,
        availableWidth,
        availableHeight
      });
      const nextDimensions = await platform.getDimensions(elements.floating);
      if (width !== nextDimensions.width || height !== nextDimensions.height) {
        return {
          reset: {
            rects: true
          }
        };
      }
      return {};
    }
  };
};



// EXTERNAL MODULE: ../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.dom.mjs
var floating_ui_utils_dom = __webpack_require__("../../node_modules/.pnpm/@floating-ui+utils@0.2.12/node_modules/@floating-ui/utils/dist/floating-ui.utils.dom.mjs");
;// ../../node_modules/.pnpm/@floating-ui+dom@1.8.0/node_modules/@floating-ui/dom/dist/floating-ui.dom.mjs





function getCssDimensions(element) {
  const css = (0,floating_ui_utils_dom/* getComputedStyle */.L9)(element);
  // In testing environments, the `width` and `height` properties are empty
  // strings for SVG elements, returning NaN. Fallback to `0` in this case.
  let width = parseFloat(css.width) || 0;
  let height = parseFloat(css.height) || 0;
  const hasOffset = (0,floating_ui_utils_dom/* isHTMLElement */.sb)(element);
  const offsetWidth = hasOffset ? element.offsetWidth : width;
  const offsetHeight = hasOffset ? element.offsetHeight : height;
  const shouldFallback = (0,floating_ui_utils/* round */.LI)(width) !== offsetWidth || (0,floating_ui_utils/* round */.LI)(height) !== offsetHeight;
  if (shouldFallback) {
    width = offsetWidth;
    height = offsetHeight;
  }
  return {
    width,
    height,
    $: shouldFallback
  };
}

function unwrapElement(element) {
  return !(0,floating_ui_utils_dom/* isElement */.vq)(element) ? element.contextElement : element;
}

function getScale(element) {
  const domElement = unwrapElement(element);
  if (!(0,floating_ui_utils_dom/* isHTMLElement */.sb)(domElement)) {
    return (0,floating_ui_utils/* createCoords */.Jx)(1);
  }
  const rect = domElement.getBoundingClientRect();
  const {
    width,
    height,
    $
  } = getCssDimensions(domElement);
  let x = ($ ? (0,floating_ui_utils/* round */.LI)(rect.width) : rect.width) / width;
  let y = ($ ? (0,floating_ui_utils/* round */.LI)(rect.height) : rect.height) / height;

  // 0, NaN, or Infinity should always fallback to 1.

  if (!x || !Number.isFinite(x)) {
    x = 1;
  }
  if (!y || !Number.isFinite(y)) {
    y = 1;
  }
  return {
    x,
    y
  };
}

const noOffsets = /*#__PURE__*/(0,floating_ui_utils/* createCoords */.Jx)(0);
function getVisualOffsets(element) {
  const win = (0,floating_ui_utils_dom/* getWindow */.zk)(element);
  if (!(0,floating_ui_utils_dom/* isWebKit */.Tc)() || !win.visualViewport) {
    return noOffsets;
  }
  return {
    x: win.visualViewport.offsetLeft,
    y: win.visualViewport.offsetTop
  };
}
function shouldAddVisualOffsets(element, isFixed, floatingOffsetParent) {
  if (isFixed === void 0) {
    isFixed = false;
  }
  return !!floatingOffsetParent && isFixed && floatingOffsetParent === (0,floating_ui_utils_dom/* getWindow */.zk)(element);
}

function getBoundingClientRect(element, includeScale, isFixedStrategy, offsetParent) {
  if (includeScale === void 0) {
    includeScale = false;
  }
  if (isFixedStrategy === void 0) {
    isFixedStrategy = false;
  }
  const clientRect = element.getBoundingClientRect();
  const domElement = unwrapElement(element);
  let scale = (0,floating_ui_utils/* createCoords */.Jx)(1);
  if (includeScale) {
    if (offsetParent) {
      if ((0,floating_ui_utils_dom/* isElement */.vq)(offsetParent)) {
        scale = getScale(offsetParent);
      }
    } else {
      scale = getScale(element);
    }
  }
  const visualOffsets = shouldAddVisualOffsets(domElement, isFixedStrategy, offsetParent) ? getVisualOffsets(domElement) : (0,floating_ui_utils/* createCoords */.Jx)(0);
  let x = (clientRect.left + visualOffsets.x) / scale.x;
  let y = (clientRect.top + visualOffsets.y) / scale.y;
  let width = clientRect.width / scale.x;
  let height = clientRect.height / scale.y;
  if (domElement && offsetParent) {
    const win = (0,floating_ui_utils_dom/* getWindow */.zk)(domElement);
    const offsetWin = (0,floating_ui_utils_dom/* isElement */.vq)(offsetParent) ? (0,floating_ui_utils_dom/* getWindow */.zk)(offsetParent) : offsetParent;
    let currentWin = win;
    let currentIFrame = (0,floating_ui_utils_dom/* getFrameElement */._m)(currentWin);
    while (currentIFrame && offsetWin !== currentWin) {
      const iframeScale = getScale(currentIFrame);
      const iframeRect = currentIFrame.getBoundingClientRect();
      const css = (0,floating_ui_utils_dom/* getComputedStyle */.L9)(currentIFrame);
      const left = iframeRect.left + (currentIFrame.clientLeft + parseFloat(css.paddingLeft)) * iframeScale.x;
      const top = iframeRect.top + (currentIFrame.clientTop + parseFloat(css.paddingTop)) * iframeScale.y;
      x *= iframeScale.x;
      y *= iframeScale.y;
      width *= iframeScale.x;
      height *= iframeScale.y;
      x += left;
      y += top;
      currentWin = (0,floating_ui_utils_dom/* getWindow */.zk)(currentIFrame);
      currentIFrame = (0,floating_ui_utils_dom/* getFrameElement */._m)(currentWin);
    }
  }
  return (0,floating_ui_utils/* rectToClientRect */.B1)({
    width,
    height,
    x,
    y
  });
}

// If <html> has a CSS width greater than the viewport, then this will be
// incorrect for RTL.
function getWindowScrollBarX(element, rect) {
  const leftScroll = (0,floating_ui_utils_dom/* getNodeScroll */.CP)(element).scrollLeft;
  if (!rect) {
    return getBoundingClientRect((0,floating_ui_utils_dom/* getDocumentElement */.ep)(element)).left + leftScroll;
  }
  return rect.left + leftScroll;
}

function getHTMLOffset(documentElement, scroll) {
  const htmlRect = documentElement.getBoundingClientRect();
  const x = htmlRect.left + scroll.scrollLeft - getWindowScrollBarX(documentElement, htmlRect);
  const y = htmlRect.top + scroll.scrollTop;
  return {
    x,
    y
  };
}

function convertOffsetParentRelativeRectToViewportRelativeRect(_ref) {
  let {
    elements,
    rect,
    offsetParent,
    strategy
  } = _ref;
  const isFixed = strategy === 'fixed';
  const documentElement = (0,floating_ui_utils_dom/* getDocumentElement */.ep)(offsetParent);
  const topLayer = elements ? (0,floating_ui_utils_dom/* isTopLayer */.Tf)(elements.floating) : false;
  if (offsetParent === documentElement || topLayer && isFixed) {
    return rect;
  }
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  let scale = (0,floating_ui_utils/* createCoords */.Jx)(1);
  const offsets = (0,floating_ui_utils/* createCoords */.Jx)(0);
  const isOffsetParentAnElement = (0,floating_ui_utils_dom/* isHTMLElement */.sb)(offsetParent);
  if (isOffsetParentAnElement || !isFixed) {
    if ((0,floating_ui_utils_dom/* getNodeName */.mq)(offsetParent) !== 'body' || (0,floating_ui_utils_dom/* isOverflowElement */.ZU)(documentElement)) {
      scroll = (0,floating_ui_utils_dom/* getNodeScroll */.CP)(offsetParent);
    }
    if (isOffsetParentAnElement) {
      const offsetRect = getBoundingClientRect(offsetParent);
      scale = getScale(offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    }
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : (0,floating_ui_utils/* createCoords */.Jx)(0);
  return {
    width: rect.width * scale.x,
    height: rect.height * scale.y,
    x: rect.x * scale.x - scroll.scrollLeft * scale.x + offsets.x + htmlOffset.x,
    y: rect.y * scale.y - scroll.scrollTop * scale.y + offsets.y + htmlOffset.y
  };
}

function getClientRects(element) {
  return element.getClientRects ? Array.from(element.getClientRects()) : [];
}

// Gets the entire size of the scrollable document area, even extending outside
// of the `<html>` and `<body>` rect bounds if horizontally scrollable.
function getDocumentRect(html) {
  const scroll = (0,floating_ui_utils_dom/* getNodeScroll */.CP)(html);
  const body = html.ownerDocument.body;
  const width = (0,floating_ui_utils/* max */.T9)(html.scrollWidth, html.clientWidth, body.scrollWidth, body.clientWidth);
  const height = (0,floating_ui_utils/* max */.T9)(html.scrollHeight, html.clientHeight, body.scrollHeight, body.clientHeight);
  let x = -scroll.scrollLeft + getWindowScrollBarX(html);
  const y = -scroll.scrollTop;
  if ((0,floating_ui_utils_dom/* getComputedStyle */.L9)(body).direction === 'rtl') {
    x += (0,floating_ui_utils/* max */.T9)(html.clientWidth, body.clientWidth) - width;
  }
  return {
    width,
    height,
    x,
    y
  };
}

// Safety check: ensure the scrollbar space is reasonable in case this
// calculation is affected by unusual styles.
// Most scrollbars leave 15-18px of space.
const SCROLLBAR_MAX = 25;
function getViewportRect(element, strategy, rootBoundary) {
  if (rootBoundary === void 0) {
    rootBoundary = 'viewport';
  }
  const isLayoutViewport = rootBoundary === 'layoutViewport';
  const win = (0,floating_ui_utils_dom/* getWindow */.zk)(element);
  const html = (0,floating_ui_utils_dom/* getDocumentElement */.ep)(element);
  const visualViewport = win.visualViewport;
  let width = html.clientWidth;
  let height = html.clientHeight;
  let x = 0;
  let y = 0;
  if (visualViewport) {
    // Client coordinates are relative to the layout viewport, except in
    // WebKit with an `absolute` strategy, where they are relative to the
    // visual viewport.
    const layoutRelativeClientCoords = !(0,floating_ui_utils_dom/* isWebKit */.Tc)() || strategy === 'fixed';
    if (isLayoutViewport) {
      if (!layoutRelativeClientCoords) {
        x = -visualViewport.offsetLeft;
        y = -visualViewport.offsetTop;
      }
    } else {
      width = visualViewport.width;
      height = visualViewport.height;
      if (layoutRelativeClientCoords) {
        x = visualViewport.offsetLeft;
        y = visualViewport.offsetTop;
      }
    }
  }
  const windowScrollbarX = getWindowScrollBarX(html);
  // `scrollbar-gutter: stable` on the <html> reserves gutter space that shrinks
  // the visual width but isn't reflected in `html.clientWidth`, so subtract it.
  // Only the inline-end (right) gutter can hold the scrollbar; `both-edges` also
  // reserves an empty inline-start gutter that clips nothing, so exclude just
  // the one scrollbar-side gutter — halve the measured (two-gutter) total. A
  // left-side scrollbar (`windowScrollbarX > 0`) is already handled by
  // `getHTMLOffset`/`visualViewport.width`; skip it here.
  if (windowScrollbarX <= 0) {
    const doc = html.ownerDocument;
    const body = doc.body;
    const bodyStyles = getComputedStyle(body);
    const bodyMarginInline = doc.compatMode === 'CSS1Compat' ? parseFloat(bodyStyles.marginLeft) + parseFloat(bodyStyles.marginRight) || 0 : 0;
    const reservedWidth = Math.abs(html.clientWidth - body.clientWidth - bodyMarginInline);
    const gutter = getComputedStyle(html).scrollbarGutter === 'stable both-edges' ? reservedWidth / 2 : reservedWidth;
    if (gutter <= SCROLLBAR_MAX) {
      width -= gutter;
    }
  }
  return {
    width,
    height,
    x,
    y
  };
}

// Returns the inner client rect, subtracting scrollbars if present.
function getInnerBoundingClientRect(element, strategy) {
  const clientRect = getBoundingClientRect(element, true, strategy === 'fixed');
  const top = clientRect.top + element.clientTop;
  const left = clientRect.left + element.clientLeft;
  const scale = getScale(element);
  const width = element.clientWidth * scale.x;
  const height = element.clientHeight * scale.y;
  const x = left * scale.x;
  const y = top * scale.y;
  return {
    width,
    height,
    x,
    y
  };
}
function getClientRectFromClippingAncestor(element, clippingAncestor, strategy) {
  let rect;
  if (clippingAncestor === 'viewport' || clippingAncestor === 'layoutViewport') {
    rect = getViewportRect(element, strategy, clippingAncestor);
  } else if (clippingAncestor === 'document') {
    rect = getDocumentRect((0,floating_ui_utils_dom/* getDocumentElement */.ep)(element));
  } else if ((0,floating_ui_utils_dom/* isElement */.vq)(clippingAncestor)) {
    rect = getInnerBoundingClientRect(clippingAncestor, strategy);
  } else {
    const visualOffsets = getVisualOffsets(element);
    rect = {
      x: clippingAncestor.x - visualOffsets.x,
      y: clippingAncestor.y - visualOffsets.y,
      width: clippingAncestor.width,
      height: clippingAncestor.height
    };
  }
  return (0,floating_ui_utils/* rectToClientRect */.B1)(rect);
}

// A "clipping ancestor" is an `overflow` element with the characteristic of
// clipping (or hiding) child elements. This returns all clipping ancestors
// of the given element up the tree.
function getClippingElementAncestors(element, cache) {
  const cachedResult = cache.get(element);
  if (cachedResult) {
    return cachedResult;
  }
  let result = (0,floating_ui_utils_dom/* getOverflowAncestors */.v9)(element, [], false).filter(el => (0,floating_ui_utils_dom/* isElement */.vq)(el) && (0,floating_ui_utils_dom/* getNodeName */.mq)(el) !== 'body');
  let lastKeptComputedStyle = null;
  const elementIsFixed = (0,floating_ui_utils_dom/* getComputedStyle */.L9)(element).position === 'fixed';
  let currentNode = elementIsFixed ? (0,floating_ui_utils_dom/* getParentNode */.$4)(element) : element;

  // https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_block#identifying_the_containing_block
  while ((0,floating_ui_utils_dom/* isElement */.vq)(currentNode) && !(0,floating_ui_utils_dom/* isLastTraversableNode */.eu)(currentNode)) {
    const computedStyle = (0,floating_ui_utils_dom/* getComputedStyle */.L9)(currentNode);
    const currentNodeIsContaining = (0,floating_ui_utils_dom/* isContainingBlock */.sQ)(currentNode);
    // Position of the containing block chain below the current node. A fixed
    // element whose containing block hasn't been found yet is a fixed chain.
    const lastPosition = lastKeptComputedStyle ? lastKeptComputedStyle.position : elementIsFixed ? 'fixed' : '';

    // A non-containing ancestor does not clip the element when the chain
    // below it escapes it: a fixed chain escapes all ancestors up to the
    // next containing block, an absolute chain escapes static ancestors.
    const shouldDropCurrentNode = !currentNodeIsContaining && (lastPosition === 'fixed' || lastPosition === 'absolute' && computedStyle.position === 'static');
    if (shouldDropCurrentNode) {
      // Drop non-containing blocks.
      result = result.filter(ancestor => ancestor !== currentNode);
    } else {
      // The kept node carries the chain position for the next iteration.
      lastKeptComputedStyle = computedStyle;
    }
    currentNode = (0,floating_ui_utils_dom/* getParentNode */.$4)(currentNode);
  }
  cache.set(element, result);
  return result;
}

// Gets the maximum area that the element is visible in due to any number of
// clipping ancestors.
function getClippingRect(_ref) {
  let {
    element,
    boundary,
    rootBoundary,
    strategy
  } = _ref;
  const elementClippingAncestors = boundary === 'clippingAncestors' ? (0,floating_ui_utils_dom/* isTopLayer */.Tf)(element) ? [] : getClippingElementAncestors(element, this._c) : [].concat(boundary);
  const clippingAncestors = [...elementClippingAncestors, rootBoundary];
  const firstRect = getClientRectFromClippingAncestor(element, clippingAncestors[0], strategy);
  let top = firstRect.top;
  let right = firstRect.right;
  let bottom = firstRect.bottom;
  let left = firstRect.left;
  for (let i = 1; i < clippingAncestors.length; i++) {
    const rect = getClientRectFromClippingAncestor(element, clippingAncestors[i], strategy);
    top = (0,floating_ui_utils/* max */.T9)(rect.top, top);
    right = (0,floating_ui_utils/* min */.jk)(rect.right, right);
    bottom = (0,floating_ui_utils/* min */.jk)(rect.bottom, bottom);
    left = (0,floating_ui_utils/* max */.T9)(rect.left, left);
  }
  return {
    width: right - left,
    height: bottom - top,
    x: left,
    y: top
  };
}

function getDimensions(element) {
  const {
    width,
    height
  } = getCssDimensions(element);
  return {
    width,
    height
  };
}

function getRectRelativeToOffsetParent(element, offsetParent, strategy) {
  const isOffsetParentAnElement = (0,floating_ui_utils_dom/* isHTMLElement */.sb)(offsetParent);
  const documentElement = (0,floating_ui_utils_dom/* getDocumentElement */.ep)(offsetParent);
  const isFixed = strategy === 'fixed';
  const rect = getBoundingClientRect(element, true, isFixed, offsetParent);
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  const offsets = (0,floating_ui_utils/* createCoords */.Jx)(0);
  if (isOffsetParentAnElement || !isFixed) {
    if ((0,floating_ui_utils_dom/* getNodeName */.mq)(offsetParent) !== 'body' || (0,floating_ui_utils_dom/* isOverflowElement */.ZU)(documentElement)) {
      scroll = (0,floating_ui_utils_dom/* getNodeScroll */.CP)(offsetParent);
    }
    if (isOffsetParentAnElement) {
      const offsetRect = getBoundingClientRect(offsetParent, true, isFixed, offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    }
  }

  // If the <body> scrollbar appears on the left (e.g. RTL systems). Use
  // Firefox with layout.scrollbar.side = 3 in about:config to test this.
  if (!isOffsetParentAnElement && documentElement) {
    offsets.x = getWindowScrollBarX(documentElement);
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : (0,floating_ui_utils/* createCoords */.Jx)(0);
  const x = rect.left + scroll.scrollLeft - offsets.x - htmlOffset.x;
  const y = rect.top + scroll.scrollTop - offsets.y - htmlOffset.y;
  return {
    x,
    y,
    width: rect.width,
    height: rect.height
  };
}

function isStaticPositioned(element) {
  return (0,floating_ui_utils_dom/* getComputedStyle */.L9)(element).position === 'static';
}

function getTrueOffsetParent(element, polyfill) {
  if (!(0,floating_ui_utils_dom/* isHTMLElement */.sb)(element) || (0,floating_ui_utils_dom/* getComputedStyle */.L9)(element).position === 'fixed') {
    return null;
  }
  if (polyfill) {
    return polyfill(element);
  }
  let rawOffsetParent = element.offsetParent;

  // Firefox returns the <html> element as the offsetParent if it's non-static,
  // while Chrome and Safari return the <body> element. The <body> element must
  // be used to perform the correct calculations even if the <html> element is
  // non-static.
  if ((0,floating_ui_utils_dom/* getDocumentElement */.ep)(element) === rawOffsetParent) {
    rawOffsetParent = rawOffsetParent.ownerDocument.body;
  }
  return rawOffsetParent;
}

// Gets the closest ancestor positioned element. Handles some edge cases,
// such as table ancestors and cross browser bugs.
function getOffsetParent(element, polyfill) {
  const win = (0,floating_ui_utils_dom/* getWindow */.zk)(element);
  if ((0,floating_ui_utils_dom/* isTopLayer */.Tf)(element)) {
    return win;
  }
  if (!(0,floating_ui_utils_dom/* isHTMLElement */.sb)(element)) {
    let svgOffsetParent = (0,floating_ui_utils_dom/* getParentNode */.$4)(element);
    while (svgOffsetParent && !(0,floating_ui_utils_dom/* isLastTraversableNode */.eu)(svgOffsetParent)) {
      if ((0,floating_ui_utils_dom/* isElement */.vq)(svgOffsetParent) && !isStaticPositioned(svgOffsetParent)) {
        return svgOffsetParent;
      }
      svgOffsetParent = (0,floating_ui_utils_dom/* getParentNode */.$4)(svgOffsetParent);
    }
    return win;
  }
  let offsetParent = getTrueOffsetParent(element, polyfill);
  while (offsetParent && (0,floating_ui_utils_dom/* isTableElement */.Lv)(offsetParent) && isStaticPositioned(offsetParent)) {
    offsetParent = getTrueOffsetParent(offsetParent, polyfill);
  }
  if (offsetParent && (0,floating_ui_utils_dom/* isLastTraversableNode */.eu)(offsetParent) && isStaticPositioned(offsetParent) && !(0,floating_ui_utils_dom/* isContainingBlock */.sQ)(offsetParent)) {
    return win;
  }
  return offsetParent || (0,floating_ui_utils_dom/* getContainingBlock */.gJ)(element) || win;
}

const getElementRects = async function (data) {
  const getOffsetParentFn = this.getOffsetParent || getOffsetParent;
  const getDimensionsFn = this.getDimensions;
  const floatingDimensions = await getDimensionsFn(data.floating);
  return {
    reference: getRectRelativeToOffsetParent(data.reference, await getOffsetParentFn(data.floating), data.strategy),
    floating: {
      x: 0,
      y: 0,
      width: floatingDimensions.width,
      height: floatingDimensions.height
    }
  };
};

function isRTL(element) {
  return (0,floating_ui_utils_dom/* getComputedStyle */.L9)(element).direction === 'rtl';
}

const platform = {
  convertOffsetParentRelativeRectToViewportRelativeRect,
  getDocumentElement: floating_ui_utils_dom/* getDocumentElement */.ep,
  getClippingRect,
  getOffsetParent,
  getElementRects,
  getClientRects,
  getDimensions,
  getScale,
  isElement: floating_ui_utils_dom/* isElement */.vq,
  isRTL
};

function rectsAreEqual(a, b) {
  return a.x === b.x && a.y === b.y && a.width === b.width && a.height === b.height;
}

// https://samthor.au/2021/observing-dom/
function observeMove(element, onMove, ancestorResize) {
  let io = null;
  let timeoutId;
  const root = (0,floating_ui_utils_dom/* getDocumentElement */.ep)(element);
  function cleanup() {
    var _io;
    clearTimeout(timeoutId);
    (_io = io) == null || _io.disconnect();
    io = null;
  }
  function refresh(skip, threshold) {
    if (skip === void 0) {
      skip = false;
    }
    if (threshold === void 0) {
      threshold = 1;
    }
    cleanup();
    const elementRectForRootMargin = element.getBoundingClientRect();
    const {
      left,
      top,
      width,
      height
    } = elementRectForRootMargin;
    if (!skip) {
      onMove();
    }
    if (!width || !height) {
      return;
    }
    const insetTop = (0,floating_ui_utils/* floor */.RI)(top);
    const insetRight = (0,floating_ui_utils/* floor */.RI)(root.clientWidth - (left + width));
    const insetBottom = (0,floating_ui_utils/* floor */.RI)(root.clientHeight - (top + height));
    const insetLeft = (0,floating_ui_utils/* floor */.RI)(left);
    const rootMargin = -insetTop + "px " + -insetRight + "px " + -insetBottom + "px " + -insetLeft + "px";
    const options = {
      rootMargin,
      threshold: (0,floating_ui_utils/* max */.T9)(0, (0,floating_ui_utils/* min */.jk)(1, threshold)) || 1
    };
    let isFirstUpdate = true;
    function handleObserve(entries) {
      const ratio = entries[0].intersectionRatio;

      // The entry is a snapshot, so the reference may have moved since the
      // intersection was computed (under performance constraints, or between
      // consecutive frames of a multi-frame layout shift). The reported ratio
      // and the observed area are stale in that case and cannot be trusted to
      // detect subsequent movement, so refresh regardless of the ratio.
      if (!rectsAreEqual(elementRectForRootMargin, element.getBoundingClientRect())) {
        return refresh();
      }
      if (ratio !== threshold) {
        if (!isFirstUpdate) {
          return refresh();
        }
        if (!ratio) {
          // If the reference is clipped in place, the ratio is 0. Throttle
          // the refresh to prevent an infinite loop of updates.
          timeoutId = setTimeout(() => {
            refresh(false, 1e-7);
          }, 1000);
        } else {
          refresh(false, ratio);
        }
      }
      isFirstUpdate = false;
    }

    // Older browsers don't support a `document` as the root and will throw an
    // error.
    try {
      io = new IntersectionObserver(handleObserve, {
        ...options,
        // Handle <iframe>s
        root: root.ownerDocument
      });
    } catch (_e) {
      io = new IntersectionObserver(handleObserve, options);
    }
    io.observe(element);
  }
  const win = (0,floating_ui_utils_dom/* getWindow */.zk)(element);
  // The window is a resize ancestor, so when `ancestorResize` is enabled its
  // listener already runs the update on resize. Here we only need to rebuild
  // the `IntersectionObserver` for the new root size, skipping a redundant
  // update. When `ancestorResize` is disabled, this becomes the sole update.
  const handleResize = () => refresh(ancestorResize);
  win.addEventListener('resize', handleResize);
  refresh(true);
  return () => {
    win.removeEventListener('resize', handleResize);
    cleanup();
  };
}

/**
 * Automatically updates the position of the floating element when necessary.
 * Should only be called when the floating element is mounted on the DOM or
 * visible on the screen.
 * @returns cleanup function that should be invoked when the floating element is
 * removed from the DOM or hidden from the screen.
 * @see https://floating-ui.com/docs/autoUpdate
 */
function autoUpdate(reference, floating, update, options) {
  if (options === void 0) {
    options = {};
  }
  const {
    ancestorScroll = true,
    ancestorResize = true,
    elementResize = typeof ResizeObserver === 'function',
    layoutShift = typeof IntersectionObserver === 'function',
    animationFrame = false
  } = options;
  const referenceEl = unwrapElement(reference);
  const ancestors = ancestorScroll || ancestorResize ? [...(referenceEl ? (0,floating_ui_utils_dom/* getOverflowAncestors */.v9)(referenceEl) : []), ...(floating ? (0,floating_ui_utils_dom/* getOverflowAncestors */.v9)(floating) : [])] : [];
  ancestors.forEach(ancestor => {
    ancestorScroll && ancestor.addEventListener('scroll', update);
    ancestorResize && ancestor.addEventListener('resize', update);
  });
  const cleanupIo = referenceEl && layoutShift ? observeMove(referenceEl, update, ancestorResize) : null;
  let reobserveFrame = -1;
  let resizeObserver = null;
  if (elementResize) {
    resizeObserver = new ResizeObserver(_ref => {
      let [firstEntry] = _ref;
      if (firstEntry && firstEntry.target === referenceEl && resizeObserver && floating) {
        // Prevent update loops when using the `size` middleware.
        // https://github.com/floating-ui/floating-ui/issues/1740
        resizeObserver.unobserve(floating);
        cancelAnimationFrame(reobserveFrame);
        reobserveFrame = requestAnimationFrame(() => {
          var _resizeObserver;
          (_resizeObserver = resizeObserver) == null || _resizeObserver.observe(floating);
        });
      }
      update();
    });
    if (referenceEl && !animationFrame) {
      resizeObserver.observe(referenceEl);
    }
    if (floating) {
      resizeObserver.observe(floating);
    }
  }
  let frameId;
  let prevRefRect = animationFrame ? getBoundingClientRect(reference) : null;
  if (animationFrame) {
    frameLoop();
  }
  function frameLoop() {
    const nextRefRect = getBoundingClientRect(reference);
    if (prevRefRect && !rectsAreEqual(prevRefRect, nextRefRect)) {
      update();
    }
    prevRefRect = nextRefRect;
    frameId = requestAnimationFrame(frameLoop);
  }
  update();
  return () => {
    var _resizeObserver2;
    ancestors.forEach(ancestor => {
      ancestorScroll && ancestor.removeEventListener('scroll', update);
      ancestorResize && ancestor.removeEventListener('resize', update);
    });
    cleanupIo == null || cleanupIo();
    (_resizeObserver2 = resizeObserver) == null || _resizeObserver2.disconnect();
    resizeObserver = null;
    if (animationFrame) {
      cancelAnimationFrame(frameId);
    }
  };
}

/**
 * Resolves with an object of overflow side offsets that determine how much the
 * element is overflowing a given clipping boundary on each side.
 * - positive = overflowing the boundary by that number of pixels
 * - negative = how many pixels left before it will overflow
 * - 0 = lies flush with the boundary
 * @see https://floating-ui.com/docs/detectOverflow
 */
const floating_ui_dom_detectOverflow = (/* unused pure expression or super */ null && (detectOverflow$1));

/**
 * Modifies the placement by translating the floating element along the
 * specified axes.
 * A number (shorthand for `mainAxis` or distance), or an axes configuration
 * object may be passed.
 * @see https://floating-ui.com/docs/offset
 */
const floating_ui_dom_offset = offset;

/**
 * Optimizes the visibility of the floating element by choosing the placement
 * that has the most space available automatically, without needing to specify a
 * preferred placement. Alternative to `flip`.
 * @see https://floating-ui.com/docs/autoPlacement
 */
const floating_ui_dom_autoPlacement = (/* unused pure expression or super */ null && (autoPlacement$1));

/**
 * Optimizes the visibility of the floating element by shifting it in order to
 * keep it in view when it will overflow the clipping boundary.
 * @see https://floating-ui.com/docs/shift
 */
const floating_ui_dom_shift = shift;

/**
 * Optimizes the visibility of the floating element by flipping the `placement`
 * in order to keep it in view when the preferred placement(s) will overflow the
 * clipping boundary. Alternative to `autoPlacement`.
 * @see https://floating-ui.com/docs/flip
 */
const floating_ui_dom_flip = flip;

/**
 * Provides data that allows you to change the size of the floating element —
 * for instance, prevent it from overflowing the clipping boundary or match the
 * width of the reference element.
 * @see https://floating-ui.com/docs/size
 */
const floating_ui_dom_size = size;

/**
 * Provides data to hide the floating element in applicable situations, such as
 * when it is not in the same clipping context as the reference element.
 * @see https://floating-ui.com/docs/hide
 */
const floating_ui_dom_hide = (/* unused pure expression or super */ null && (hide$1));

/**
 * Provides data to position an inner element of the floating element so that it
 * appears centered to the reference element.
 * @see https://floating-ui.com/docs/arrow
 */
const floating_ui_dom_arrow = arrow;

/**
 * Provides improved positioning for inline reference elements that can span
 * over multiple lines, such as hyperlinks or range selections.
 * @see https://floating-ui.com/docs/inline
 */
const floating_ui_dom_inline = (/* unused pure expression or super */ null && (inline$1));

/**
 * Built-in `limiter` that will stop `shift()` at a certain point.
 */
const floating_ui_dom_limitShift = limitShift;

/**
 * Computes the `x` and `y` coordinates that will place the floating element
 * next to a given reference element.
 */
const floating_ui_dom_computePosition = (reference, floating, options) => {
  // This caches the expensive `getClippingElementAncestors` function so that
  // multiple lifecycle resets re-use the same result. It only lives for a
  // single call. If other functions become expensive, we can add them as well.
  const cache = new Map();
  const mergedOptions = options != null ? options : {};
  const platformWithCache = {
    ...platform,
    ...mergedOptions.platform,
    _c: cache
  };
  return computePosition(reference, floating, {
    ...mergedOptions,
    platform: platformWithCache
  });
};



;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/popover/popover.js
"use client";











//#region src/popover/popover.tsx
const popover_TagName = "div";
/**
* How many mounted popovers are currently asserting `unstable_placing` on each
* store. The state belongs to the store, so a StrictMode pair, a remount, a
* keyed replacement and a store swap are all answered by the same question:
* does anyone still own it once the deferred reset runs?
*/
const placingWriters = /* @__PURE__ */ new WeakMap();
function createDOMRect(x = 0, y = 0, width = 0, height = 0) {
	if (typeof DOMRect === "function") return new DOMRect(x, y, width, height);
	const rect = {
		x,
		y,
		width,
		height,
		top: y,
		right: x + width,
		bottom: y + height,
		left: x
	};
	return {
		...rect,
		toJSON: () => rect
	};
}
function getDOMRect(anchorRect) {
	if (!anchorRect) return createDOMRect();
	const { x, y, width, height } = anchorRect;
	return createDOMRect(x, y, width, height);
}
function getAnchorElement(anchorElement, getAnchorRect) {
	return {
		contextElement: anchorElement || void 0,
		getBoundingClientRect: () => {
			const anchor = anchorElement;
			const anchorRect = getAnchorRect?.(anchor);
			if (anchorRect || !anchor) return getDOMRect(anchorRect);
			return anchor.getBoundingClientRect();
		}
	};
}
function isValidPlacement(flip) {
	return /^(?:top|bottom|left|right)(?:-(?:start|end))?$/.test(flip);
}
function roundByDPR(value) {
	const dpr = window.devicePixelRatio || 1;
	return Math.round(value * dpr) / dpr;
}
function getOverflowPaddingValue(padding) {
	if (typeof padding === "number") return padding;
	return Math.max(padding.left ?? 0, padding.right ?? 0);
}
function getOffsetMiddleware(arrowElement, props) {
	return floating_ui_dom_offset(({ placement }) => {
		const arrowOffset = (arrowElement?.clientHeight || 0) / 2;
		const finalGutter = typeof props.gutter === "number" ? props.gutter + arrowOffset : props.gutter ?? arrowOffset;
		return {
			crossAxis: !!!placement.split("-")[1] ? props.shift : void 0,
			mainAxis: finalGutter,
			alignmentAxis: props.shift
		};
	});
}
function getFlipMiddleware(props) {
	if (props.flip === false) return;
	const fallbackPlacements = typeof props.flip === "string" ? props.flip.split(" ") : void 0;
	(0,utils_dist/* invariant */.V1)(!fallbackPlacements || fallbackPlacements.every(isValidPlacement),  false && 0);
	return floating_ui_dom_flip({
		padding: props.overflowPadding,
		fallbackPlacements
	});
}
function getShiftMiddleware(props) {
	if (!props.slide && !props.overlap) return;
	return floating_ui_dom_shift({
		mainAxis: props.slide,
		crossAxis: props.overlap,
		padding: props.overflowPadding,
		limiter: floating_ui_dom_limitShift()
	});
}
function getSizeMiddleware(props, shouldCancel) {
	return floating_ui_dom_size({
		padding: props.overflowPadding,
		apply({ elements, availableWidth, availableHeight, rects }) {
			if (shouldCancel?.()) return;
			const wrapper = elements.floating;
			const referenceWidth = Math.round(rects.reference.width);
			availableWidth = Math.floor(availableWidth);
			availableHeight = Math.floor(availableHeight);
			wrapper.style.setProperty("--popover-anchor-width", `${referenceWidth}px`);
			wrapper.style.setProperty("--popover-available-width", `${availableWidth}px`);
			wrapper.style.setProperty("--popover-available-height", `${availableHeight}px`);
			if (props.sameWidth) wrapper.style.width = `${referenceWidth}px`;
			if (props.fitViewport) {
				wrapper.style.maxWidth = `${availableWidth}px`;
				wrapper.style.maxHeight = `${availableHeight}px`;
			}
		}
	});
}
function getArrowMiddleware(arrowElement, props) {
	if (!arrowElement) return;
	return floating_ui_dom_arrow({
		element: arrowElement,
		padding: props.arrowPadding
	});
}
/**
* Returns props to create a `Popover` component.
* @see https://ariakit.com/components/popover
* @example
* ```jsx
* const store = usePopoverStore();
* const props = usePopover({ store });
* <Role {...props}>Popover</Role>
* ```
*/
const usePopover = (0,react_utils_dist/* createHook */.ab)(function usePopover({ store, modal = false, portal = modal, preserveTabOrder = true, autoFocusOnShow = true, wrapperProps, fixed = false, flip = true, shift = 0, slide = true, overlap = false, sameWidth = false, fitViewport = false, gutter, arrowPadding = 4, overflowPadding = 8, getAnchorRect, updatePosition, ...props }) {
	const context = (0,popover_context/* usePopoverProviderContext */.zG)();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	const arrowElement = (0,dist/* useStoreState */.O$)(store, "arrowElement");
	const anchorElement = (0,dist/* useStoreState */.O$)(store, "anchorElement");
	const shouldPreserveTabOrder = preserveTabOrder && portal && !modal;
	const disclosureElement = (0,dist/* useStoreState */.O$)(store, shouldPreserveTabOrder ? ["disclosureElement"] : [], (state) => shouldPreserveTabOrder ? state.disclosureElement : null);
	const popoverElement = (0,dist/* useStoreState */.O$)(store, "popoverElement");
	const placing = (0,dist/* useStoreState */.O$)(store, "unstable_placing");
	const contentElement = (0,dist/* useStoreState */.O$)(store, "contentElement");
	const placement = (0,dist/* useStoreState */.O$)(store, "placement");
	const mounted = (0,dist/* useStoreState */.O$)(store, "mounted");
	const rendered = (0,dist/* useStoreState */.O$)(store, "rendered");
	const defaultArrowElementRef = (0,react.useRef)(null);
	const positioned = !placing;
	const { portalRef, domReady } = (0,react_utils_dist/* usePortalRef */.TE)(portal, props.portalRef);
	const getAnchorRectProp = (0,react_utils_dist/* useEvent */._q)(getAnchorRect);
	const updatePositionProp = (0,react_utils_dist/* useEvent */._q)(updatePosition);
	const hasCustomUpdatePosition = !!updatePosition;
	const overflowPaddingTop = typeof overflowPadding === "number" ? overflowPadding : overflowPadding.top ?? 0;
	const overflowPaddingRight = typeof overflowPadding === "number" ? overflowPadding : overflowPadding.right ?? 0;
	const overflowPaddingBottom = typeof overflowPadding === "number" ? overflowPadding : overflowPadding.bottom ?? 0;
	const overflowPaddingLeft = typeof overflowPadding === "number" ? overflowPadding : overflowPadding.left ?? 0;
	const hiddenWhileUnmounted = !mounted && (0,disclosure_content/* isHidden */.dK)(mounted, props.hidden, props.alwaysVisible);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (mounted) store?.setState("unstable_placing", true);
		if (!popoverElement?.isConnected) return;
		const positioningPadding = {
			top: overflowPaddingTop,
			right: overflowPaddingRight,
			bottom: overflowPaddingBottom,
			left: overflowPaddingLeft
		};
		popoverElement.style.setProperty("--popover-overflow-padding", `${getOverflowPaddingValue(positioningPadding)}px`);
		if (hiddenWhileUnmounted && !hasCustomUpdatePosition) return;
		const anchor = getAnchorElement(anchorElement, getAnchorRectProp);
		let canceled = false;
		let placed = false;
		const shouldCancelUpdate = () => {
			if (canceled) return true;
			if (!popoverElement.isConnected) return true;
			return false;
		};
		const updatePosition = async () => {
			if (shouldCancelUpdate()) return;
			if (!mounted) return;
			if (!arrowElement) defaultArrowElementRef.current = defaultArrowElementRef.current || document.createElement("div");
			const arrow = arrowElement || defaultArrowElementRef.current;
			const middleware = [
				getOffsetMiddleware(arrow, {
					gutter,
					shift
				}),
				getFlipMiddleware({
					flip,
					overflowPadding: positioningPadding
				}),
				getShiftMiddleware({
					slide,
					shift,
					overlap,
					overflowPadding: positioningPadding
				}),
				getArrowMiddleware(arrow, { arrowPadding }),
				getSizeMiddleware({
					sameWidth,
					fitViewport,
					overflowPadding: positioningPadding
				}, shouldCancelUpdate)
			];
			const pos = await floating_ui_dom_computePosition(anchor, popoverElement, {
				placement,
				strategy: fixed ? "fixed" : "absolute",
				middleware
			});
			if (shouldCancelUpdate()) return;
			store?.setState("currentPlacement", pos.placement);
			const x = roundByDPR(pos.x);
			const y = roundByDPR(pos.y);
			Object.assign(popoverElement.style, {
				top: "0",
				left: "0",
				transform: `translate3d(${x}px,${y}px,0)`
			});
			placed = true;
			if (arrow && pos.middlewareData.arrow) {
				const { x: arrowX, y: arrowY } = pos.middlewareData.arrow;
				const side = getBasePlacement(pos.placement);
				const centerX = arrow.clientWidth / 2;
				const centerY = arrow.clientHeight / 2;
				const originX = arrowX != null ? arrowX + centerX : -centerX;
				const originY = arrowY != null ? arrowY + centerY : -centerY;
				popoverElement.style.setProperty("--popover-transform-origin", {
					top: `${originX}px calc(100% + ${centerY}px)`,
					bottom: `${originX}px ${-centerY}px`,
					left: `calc(100% + ${centerX}px) ${originY}px`,
					right: `${-centerX}px ${originY}px`
				}[side]);
				Object.assign(arrow.style, {
					left: arrowX != null ? `${arrowX}px` : "",
					top: arrowY != null ? `${arrowY}px` : "",
					right: "",
					bottom: "",
					[side]: "100%"
				});
			}
		};
		const update = async () => {
			if (shouldCancelUpdate()) return;
			try {
				if (hasCustomUpdatePosition) await updatePositionProp({ updatePosition });
				else await updatePosition();
			} catch (error) {
				if (placed && !shouldCancelUpdate()) store?.setState("unstable_placing", false);
				throw error;
			}
			if (shouldCancelUpdate()) return;
			store?.setState("unstable_placing", false);
		};
		const cancelAutoUpdate = autoUpdate(anchor, popoverElement, update, { elementResize: typeof ResizeObserver === "function" });
		return () => {
			canceled = true;
			cancelAutoUpdate();
		};
	}, [
		store,
		rendered,
		popoverElement,
		arrowElement,
		anchorElement,
		placement,
		mounted,
		hiddenWhileUnmounted,
		domReady,
		fixed,
		flip,
		shift,
		slide,
		overlap,
		sameWidth,
		fitViewport,
		gutter,
		arrowPadding,
		overflowPaddingTop,
		overflowPaddingRight,
		overflowPaddingBottom,
		overflowPaddingLeft,
		getAnchorRectProp,
		hasCustomUpdatePosition,
		updatePositionProp
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!mounted) return;
		if (!domReady) return;
		if (!popoverElement?.isConnected) return;
		if (!contentElement?.isConnected) return;
		const applyZIndex = () => {
			popoverElement.style.zIndex = getComputedStyle(contentElement).zIndex;
		};
		applyZIndex();
		let raf = requestAnimationFrame(() => {
			raf = requestAnimationFrame(applyZIndex);
		});
		return () => cancelAnimationFrame(raf);
	}, [
		mounted,
		domReady,
		popoverElement,
		contentElement
	]);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (!store) return;
		const currentStore = store;
		placingWriters.set(currentStore, (placingWriters.get(currentStore) ?? 0) + 1);
		const desync = (0,store_dist/* sync */.OH)(currentStore, ["mounted"], (state) => {
			currentStore.setState("unstable_placing", state.mounted);
		});
		return () => {
			desync();
			placingWriters.set(currentStore, (placingWriters.get(currentStore) ?? 1) - 1);
			queueMicrotask(() => {
				if (placingWriters.get(currentStore)) return;
				currentStore.setState("unstable_placing", false);
			});
		};
	}, [store]);
	const position = fixed ? "fixed" : "absolute";
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
		...wrapperProps,
		style: {
			position,
			top: 0,
			left: 0,
			width: "max-content",
			...wrapperProps?.style
		},
		ref: store?.setPopoverElement,
		children: element
	}), [
		store,
		position,
		wrapperProps
	]);
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(popover_context/* PopoverScopedContextProvider */.s1, {
		value: store,
		children: element
	}), [store]);
	props = {
		"data-placing": placing || void 0,
		...props,
		style: {
			position: "relative",
			...props.style
		}
	};
	props = useDialog({
		store,
		modal,
		portal,
		preserveTabOrder,
		preserveTabOrderAnchor: disclosureElement || anchorElement,
		autoFocusOnShow: positioned && autoFocusOnShow,
		...props,
		portalRef
	});
	return props;
});
/**
* Renders a popover element that's automatically positioned relative to an
* anchor element.
* @see https://ariakit.com/components/popover
* @example
* ```jsx {3}
* <PopoverProvider>
*   <PopoverDisclosure>Disclosure</PopoverDisclosure>
*   <Popover>Popover</Popover>
* </PopoverProvider>
* ```
*/
const Popover = createDialogComponent((0,react_utils_dist/* forwardRef */.Rf)(function Popover(props) {
	const htmlProps = usePopover(props);
	return (0,react_utils_dist/* createElement */.n)(popover_TagName, htmlProps);
}), popover_context/* usePopoverProviderContext */.zG);
//#endregion


//# sourceMappingURL=popover.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/hovercard/utils/polygon.js
"use client";
//#region src/hovercard/utils/polygon.ts
function getEventPoint(event) {
	return [event.clientX, event.clientY];
}
function isPointInPolygon(point, polygon) {
	const [x, y] = point;
	let inside = false;
	const length = polygon.length;
	for (let l = length, i = 0, j = l - 1; i < l; j = i++) {
		const currentPoint = polygon[i];
		const previousPoint = polygon[j];
		const vertexPoint = polygon[j === 0 ? l - 1 : j - 1];
		if (currentPoint == null) return false;
		if (previousPoint == null) return false;
		if (vertexPoint == null) return false;
		const [xi, yi] = currentPoint;
		const [xj, yj] = previousPoint;
		const [, vy] = vertexPoint;
		const where = (yi - yj) * (x - xi) - (xi - xj) * (y - yi);
		if (yj < yi) {
			if (y >= yj && y < yi) {
				if (where === 0) return true;
				if (where > 0) {
					if (y === yj) {
						if (y > vy) inside = !inside;
					} else inside = !inside;
				}
			}
		} else if (yi < yj) {
			if (y > yi && y <= yj) {
				if (where === 0) return true;
				if (where < 0) {
					if (y === yj) {
						if (y < vy) inside = !inside;
					} else inside = !inside;
				}
			}
		} else if (y === yi && (x >= xj && x <= xi || x >= xi && x <= xj)) return true;
	}
	return inside;
}
function getEnterPointPlacement(enterPoint, rect) {
	const { top, right, bottom, left } = rect;
	const [x, y] = enterPoint;
	return [x < left ? "left" : x > right ? "right" : null, y < top ? "top" : y > bottom ? "bottom" : null];
}
function getElementPolygon(element, enterPoint) {
	const rect = element.getBoundingClientRect();
	const { top, right, bottom, left } = rect;
	const [x, y] = getEnterPointPlacement(enterPoint, rect);
	const polygon = [enterPoint];
	if (x) {
		if (y !== "top") polygon.push([x === "left" ? left : right, top]);
		polygon.push([x === "left" ? right : left, top]);
		polygon.push([x === "left" ? right : left, bottom]);
		if (y !== "bottom") polygon.push([x === "left" ? left : right, bottom]);
	} else if (y === "top") {
		polygon.push([left, top]);
		polygon.push([left, bottom]);
		polygon.push([right, bottom]);
		polygon.push([right, top]);
	} else {
		polygon.push([left, bottom]);
		polygon.push([left, top]);
		polygon.push([right, top]);
		polygon.push([right, bottom]);
	}
	return polygon;
}
//#endregion


//# sourceMappingURL=polygon.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/__chunks/CW2hYOhU.js
"use client";










//#region src/dialog/utils/__use-escape-close.ts
/**
* Wraps the `hideOnEscape` and `onClose` props of a dialog to call
* `onEscapeClose` once a close that Escape requested goes through. The
* `hideOnEscape` wrapper only decides whether Escape closes the dialog, so the
* work that follows a close doesn't run when `onClose` prevents it.
* https://github.com/ariakit/ariakit/issues/7622
*/
function useEscapeClose({ hideOnEscape, onClose, onEscapeClose }) {
	const acceptedEscapeRef = (0,react.useRef)(null);
	return {
		hideOnEscape: (0,react_utils_dist/* useEvent */._q)((event) => {
			if ((0,utils_dist/* isFalsyBooleanCallback */.zO)(hideOnEscape, event)) return false;
			const acceptedEscape = {};
			acceptedEscapeRef.current = acceptedEscape;
			setTimeout(() => {
				if (acceptedEscapeRef.current !== acceptedEscape) return;
				acceptedEscapeRef.current = null;
			});
			return true;
		}),
		onClose: (0,react_utils_dist/* useEvent */._q)((event) => {
			const acceptedEscape = acceptedEscapeRef.current;
			acceptedEscapeRef.current = null;
			onClose?.(event);
			if (event.defaultPrevented) return;
			if (!acceptedEscape) return;
			onEscapeClose();
		})
	};
}
//#endregion
//#region src/hovercard/hovercard.tsx
const CW2hYOhU_TagName = "div";
function isMovingOnHovercard(path, card, hoverElements, nested) {
	if ((0,utils_dist/* hasFocusWithin */.oW)(card)) return true;
	if (path.includes(card)) return true;
	if (hoverElements.some((element) => element && path.includes(element))) return true;
	if (nested?.some((card) => (0,utils_dist/* hasFocusWithin */.oW)(card) || path.includes(card))) return true;
	return false;
}
function disableEventOnTransit({ event, element, enterPointRef, disablePointerEvents, refreshEnterPoint = false }) {
	const enterPoint = enterPointRef.current;
	if (!enterPoint) return false;
	const currentPoint = getEventPoint(event);
	const polygon = getElementPolygon(element, enterPoint);
	if (!isPointInPolygon(currentPoint, polygon)) return false;
	if (refreshEnterPoint) enterPointRef.current = currentPoint;
	if (disablePointerEvents(event)) {
		event.preventDefault();
		event.stopPropagation();
	}
	return true;
}
function useAutoFocusOnHide({ store, ...props }) {
	const [autoFocusOnHide, setAutoFocusOnHide] = (0,react.useState)(false);
	const mounted = (0,dist/* useStoreState */.O$)(store, "mounted");
	(0,react.useEffect)(() => {
		if (!mounted) setAutoFocusOnHide(false);
	}, [mounted]);
	const onFocusProp = props.onFocus;
	const onFocus = (0,react_utils_dist/* useEvent */._q)((event) => {
		onFocusProp?.(event);
		if (event.defaultPrevented) return;
		setAutoFocusOnHide(true);
	});
	const finalFocusRef = (0,react.useRef)(null);
	(0,react.useEffect)(() => {
		return (0,store_dist/* sync */.OH)(store, ["anchorElement"], (state) => {
			finalFocusRef.current = state.anchorElement;
		});
	}, [store]);
	props = {
		autoFocusOnHide,
		finalFocus: finalFocusRef,
		...props,
		onFocus
	};
	return props;
}
const NestedHovercardContext = (0,react.createContext)(null);
/**
* Returns props to create a `Hovercard` component.
* @see https://ariakit.com/components/hovercard
* @example
* ```jsx
* const store = useHovercardStore();
* const props = useHovercard({ store });
* <HovercardAnchor store={store}>@username</HovercardAnchor>
* <Role {...props}>Details</Role>
* ```
*/
const useHovercard = (0,react_utils_dist/* createHook */.ab)(function useHovercard({ store, modal = false, portal = modal, hideOnEscape = true, hideOnHoverOutside = true, disablePointerEventsOnApproach = !!hideOnHoverOutside, ...props }) {
	const context = useHovercardProviderContext();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	const ref = (0,react.useRef)(null);
	const nestedHovercardsRef = (0,react.useRef)([]);
	const hideTimeoutRef = (0,react.useRef)(0);
	const enterPointRef = (0,react.useRef)(null);
	const { portalRef, domReady } = (0,react_utils_dist/* usePortalRef */.TE)(portal, props.portalRef);
	const isMouseMoving = (0,react_utils_dist/* useIsMouseMoving */.P$)();
	const mayHideOnHoverOutside = !!hideOnHoverOutside;
	const hideOnHoverOutsideProp = (0,react_utils_dist/* useBooleanEvent */.O4)(hideOnHoverOutside);
	const mayDisablePointerEvents = !!disablePointerEventsOnApproach;
	const disablePointerEventsProp = (0,react_utils_dist/* useBooleanEvent */.O4)(disablePointerEventsOnApproach);
	const open = (0,dist/* useStoreState */.O$)(store, "open");
	const mounted = (0,dist/* useStoreState */.O$)(store, "mounted");
	const clearHideTimeout = (0,react.useCallback)(() => {
		window.clearTimeout(hideTimeoutRef.current);
		hideTimeoutRef.current = 0;
	}, []);
	(0,react.useEffect)(() => {
		if (!domReady) return;
		if (!mounted) return;
		if (!mayHideOnHoverOutside && !mayDisablePointerEvents) return;
		const element = ref.current;
		if (!element) return;
		const onMouseMove = (event) => {
			if (!store) return;
			if (!isMouseMoving()) return;
			const { anchorElement, disclosureElement, hideTimeout, timeout } = store.getState();
			const path = event.composedPath();
			const hoverElements = [anchorElement, disclosureElement];
			const hoveredElement = hoverElements.find((element) => element && path.includes(element));
			if (isMovingOnHovercard(path, element, hoverElements, nestedHovercardsRef.current)) {
				enterPointRef.current = hoveredElement ? getEventPoint(event) : null;
				clearHideTimeout();
				return;
			}
			if (hideTimeoutRef.current) return;
			if (disableEventOnTransit({
				event,
				element,
				enterPointRef,
				disablePointerEvents: disablePointerEventsProp,
				refreshEnterPoint: true
			})) return;
			if (!hideOnHoverOutsideProp(event)) return;
			hideTimeoutRef.current = window.setTimeout(() => {
				hideTimeoutRef.current = 0;
				store?.hide();
			}, hideTimeout ?? timeout);
		};
		return (0,utils_dist/* chain */.cy)((0,utils_dist/* addGlobalEventListener */.dg)("mousemove", onMouseMove, true), clearHideTimeout);
	}, [
		store,
		isMouseMoving,
		domReady,
		mounted,
		mayHideOnHoverOutside,
		mayDisablePointerEvents,
		nestedHovercardsRef,
		clearHideTimeout,
		disablePointerEventsProp,
		hideOnHoverOutsideProp
	]);
	(0,react.useEffect)(() => {
		if (!domReady) return;
		if (!mounted) return;
		if (!mayDisablePointerEvents) return;
		const disableEvent = (event) => {
			const element = ref.current;
			if (!element) return;
			disableEventOnTransit({
				event,
				element,
				enterPointRef,
				disablePointerEvents: disablePointerEventsProp
			});
		};
		return (0,utils_dist/* chain */.cy)((0,utils_dist/* addGlobalEventListener */.dg)("mouseenter", disableEvent, true), (0,utils_dist/* addGlobalEventListener */.dg)("mouseover", disableEvent, true), (0,utils_dist/* addGlobalEventListener */.dg)("mouseout", disableEvent, true), (0,utils_dist/* addGlobalEventListener */.dg)("mouseleave", disableEvent, true));
	}, [
		domReady,
		mounted,
		mayDisablePointerEvents,
		disablePointerEventsProp
	]);
	(0,react.useEffect)(() => {
		if (!domReady) return;
		if (open) return;
		store?.setAutoFocusOnShow(false);
	}, [
		store,
		domReady,
		open
	]);
	const openRef = (0,react_utils_dist/* useLiveRef */.hX)(open);
	(0,react.useEffect)(() => {
		if (!domReady) return;
		return () => {
			if (!openRef.current) store?.setAutoFocusOnShow(false);
		};
	}, [store, domReady]);
	const registerOnParent = (0,react.useContext)(NestedHovercardContext);
	(0,react_utils_dist/* useSafeLayoutEffect */.UQ)(() => {
		if (modal) return;
		if (!portal) return;
		if (!mounted) return;
		if (!domReady) return;
		const element = ref.current;
		if (!element) return;
		return registerOnParent?.(element);
	}, [
		modal,
		portal,
		mounted,
		domReady,
		registerOnParent
	]);
	const registerNestedHovercard = (0,react.useCallback)((element) => {
		clearHideTimeout();
		nestedHovercardsRef.current = [...nestedHovercardsRef.current, element];
		const parentUnregister = registerOnParent?.(element);
		return () => {
			clearHideTimeout();
			nestedHovercardsRef.current = nestedHovercardsRef.current.filter((item) => item !== element);
			parentUnregister?.();
		};
	}, [clearHideTimeout, registerOnParent]);
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(HovercardScopedContextProvider, {
		value: store,
		children: /* @__PURE__ */ (0,jsx_runtime.jsx)(NestedHovercardContext.Provider, {
			value: registerNestedHovercard,
			children: element
		})
	}), [store, registerNestedHovercard]);
	props = {
		...props,
		ref: (0,react_utils_dist/* useMergeRefs */.SV)(ref, props.ref)
	};
	props = useAutoFocusOnHide({
		store,
		...props
	});
	const autoFocusOnShow = (0,dist/* useStoreState */.O$)(store, ["autoFocusOnShow"], (state) => modal || state.autoFocusOnShow);
	const escapeClose = useEscapeClose({
		hideOnEscape,
		onClose: props.onClose,
		onEscapeClose() {
			requestAnimationFrame(() => {
				requestAnimationFrame(() => {
					if (!store?.getState().open) return;
					store.hide();
				});
			});
		}
	});
	props = usePopover({
		store,
		modal,
		portal,
		autoFocusOnShow,
		...props,
		portalRef,
		hideOnEscape: escapeClose.hideOnEscape,
		onClose: escapeClose.onClose
	});
	return props;
});
/**
* Renders a hovercard element, which is a popover that's usually made visible
* by hovering the mouse cursor over a
* [`HovercardAnchor`](https://ariakit.com/reference/hovercard-anchor).
* @see https://ariakit.com/components/hovercard
* @example
* ```jsx {3}
* <HovercardProvider>
*   <HovercardAnchor>@username</HovercardAnchor>
*   <Hovercard>Details</Hovercard>
* </HovercardProvider>
* ```
*/
const Hovercard = createDialogComponent((0,react_utils_dist/* forwardRef */.Rf)(function Hovercard(props) {
	const htmlProps = useHovercard(props);
	return (0,react_utils_dist/* createElement */.n)(CW2hYOhU_TagName, htmlProps);
}), useHovercardProviderContext);
//#endregion


//# sourceMappingURL=CW2hYOhU.js.map
;// ../../node_modules/.pnpm/@ariakit+react-components@0_ee93ae478ccc4fd5e7ba350a885f9708/node_modules/@ariakit/react-components/dist/tooltip/tooltip.js
"use client";







//#region src/tooltip/tooltip.tsx
const tooltip_TagName = "div";
/**
* Returns props to create a `Tooltip` component.
* @see https://ariakit.com/components/tooltip
* @example
* ```jsx
* const store = useToolTipStore();
* const props = useTooltip({ store });
* <TooltipAnchor store={store}>Anchor</TooltipAnchor>
* <Role {...props}>Tooltip</Role>
* ```
*/
const useTooltip = (0,react_utils_dist/* createHook */.ab)(function useTooltip({ store, portal = true, gutter = 8, preserveTabOrder = false, hideOnHoverOutside = true, hideOnInteractOutside = true, ...props }) {
	const context = useTooltipProviderContext();
	store = store || context;
	(0,utils_dist/* invariant */.V1)(store,  false && 0);
	props = (0,react_utils_dist/* useWrapElement */.w7)(props, (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TooltipScopedContextProvider, {
		value: store,
		children: element
	}), [store]);
	props = {
		role: (0,dist/* useStoreState */.O$)(store, ["type"], (state) => state.type === "description" ? "tooltip" : "none"),
		...props
	};
	props = useHovercard({
		...props,
		store,
		portal,
		gutter,
		preserveTabOrder,
		hideOnHoverOutside(event) {
			if ((0,utils_dist/* isFalsyBooleanCallback */.zO)(hideOnHoverOutside, event)) return false;
			const anchorElement = store?.getState().anchorElement;
			if (!anchorElement) return true;
			if ("focusVisible" in anchorElement.dataset) return false;
			return true;
		},
		hideOnInteractOutside: (event) => {
			if ((0,utils_dist/* isFalsyBooleanCallback */.zO)(hideOnInteractOutside, event)) return false;
			const anchorElement = store?.getState().anchorElement;
			if (!anchorElement) return true;
			if ((0,utils_dist/* contains */.gR)(anchorElement, event.target)) return false;
			return true;
		}
	});
	return props;
});
/**
* Renders a tooltip element that visually describes a
* [`TooltipAnchor`](https://ariakit.com/reference/tooltip-anchor) when it
* receives focus or is hovered.
*
* The tooltip is strictly for visual purposes. It's your responsibility to
* ensure the anchor element has an accessible name. See [Tooltip anchors must
* have accessible
* names](https://ariakit.com/components/tooltip#tooltip-anchors-must-have-accessible-names)
* @see https://ariakit.com/components/tooltip
* @example
* ```jsx {3}
* <TooltipProvider>
*   <TooltipAnchor>Anchor</TooltipAnchor>
*   <Tooltip>Tooltip</Tooltip>
* </TooltipProvider>
* ```
*/
const Tooltip = createDialogComponent((0,react_utils_dist/* forwardRef */.Rf)(function Tooltip(props) {
	const htmlProps = useTooltip(props);
	return (0,react_utils_dist/* createElement */.n)(tooltip_TagName, htmlProps);
}), useTooltipProviderContext);
//#endregion


//# sourceMappingURL=tooltip.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/shortcut/index.js
var build_module_shortcut = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/shortcut/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/popover/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/popover/utils.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/tooltip/index.js








const TooltipInternalContext = (0,react.createContext)({
  isNestedInTooltip: false
});
TooltipInternalContext.displayName = "TooltipInternalContext";
const TOOLTIP_DELAY = 700;
const CONTEXT_VALUE = {
  isNestedInTooltip: true
};
function UnforwardedTooltip(props, ref) {
  const {
    children,
    className,
    delay = TOOLTIP_DELAY,
    hideOnClick = true,
    placement,
    position,
    shortcut,
    text,
    ...restProps
  } = props;
  const {
    isNestedInTooltip
  } = (0,react.useContext)(TooltipInternalContext);
  const baseId = (0,use_instance_id/* default */.A)(tooltip_Tooltip, "tooltip");
  const describedById = text || shortcut ? baseId : void 0;
  const isOnlyChild = react.Children.count(children) === 1;
  if (!isOnlyChild) {
    if (false) {}
  }
  let computedPlacement;
  if (placement !== void 0) {
    computedPlacement = placement;
  } else if (position !== void 0) {
    computedPlacement = (0,utils/* positionToPlacement */.YK)(position);
    (0,build_module/* default */.A)("`position` prop in wp.components.tooltip", {
      since: "6.4",
      alternative: "`placement` prop"
    });
  }
  computedPlacement = computedPlacement || "bottom";
  const tooltipStore = useTooltipStore({
    placement: computedPlacement,
    showTimeout: delay
  });
  const mounted = dist/* useStoreState */.O$(tooltipStore, "mounted");
  if (isNestedInTooltip) {
    return isOnlyChild ? /* @__PURE__ */ (0,jsx_runtime.jsx)(Role, {
      ...restProps,
      render: children
    }) : children;
  }
  function addDescribedById(element) {
    return describedById && mounted && element.props["aria-describedby"] === void 0 && element.props["aria-label"] !== text ? (0,react.cloneElement)(element, {
      "aria-describedby": describedById
    }) : element;
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(TooltipInternalContext.Provider, {
    value: CONTEXT_VALUE,
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(TooltipAnchor, {
      onClick: hideOnClick ? tooltipStore.hide : void 0,
      store: tooltipStore,
      render: isOnlyChild ? addDescribedById(children) : void 0,
      ref,
      children: isOnlyChild ? void 0 : children
    }), isOnlyChild && (text || shortcut) && /* @__PURE__ */ (0,jsx_runtime.jsxs)(Tooltip, {
      ...restProps,
      className: (0,clsx/* default */.A)("components-tooltip", className),
      unmountOnHide: true,
      gutter: 4,
      id: describedById,
      overflowPadding: 0.5,
      store: tooltipStore,
      children: [text, shortcut && /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_shortcut/* default */.A, {
        className: text ? "components-tooltip__shortcut" : "",
        shortcut
      })]
    })]
  });
}
const tooltip_Tooltip = (0,react.forwardRef)(UnforwardedTooltip);
var tooltip_default = tooltip_Tooltip;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/visually-hidden/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ component_default)
});

// UNUSED EXPORTS: VisuallyHidden

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/context/context-connect.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/visually-hidden/styles.js
const visuallyHidden = {
  border: 0,
  clip: "rect(1px, 1px, 1px, 1px)",
  WebkitClipPath: "inset( 50% )",
  clipPath: "inset( 50% )",
  height: "1px",
  margin: "-1px",
  overflow: "hidden",
  padding: 0,
  position: "absolute",
  width: "1px",
  wordWrap: "normal"
};

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/visually-hidden/component.js




function UnconnectedVisuallyHidden(props, forwardedRef) {
  const {
    style: styleProp,
    ...contextProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "VisuallyHidden");
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    ref: forwardedRef,
    ...contextProps,
    style: {
      ...visuallyHidden,
      ...styleProp || {}
    }
  });
}
const VisuallyHidden = (0,context_connect/* contextConnect */.KZ)(UnconnectedVisuallyHidden, "VisuallyHidden");
var component_default = VisuallyHidden;

//# sourceMappingURL=component.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ use_instance_id_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-instance-id/index.ts

var instanceMap = /* @__PURE__ */ new WeakMap();
function createId(object) {
  const instances = instanceMap.get(object) || 0;
  instanceMap.set(object, instances + 1);
  return instances;
}
function useInstanceId(object, prefix, preferredId) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    if (preferredId) {
      return preferredId;
    }
    const id = createId(object);
    return prefix ? `${prefix}-${id}` : id;
  }, [object, preferredId, prefix]);
}
var use_instance_id_default = useInstanceId;

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@4.57.0/node_modules/@wordpress/deprecated/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.57.0/node_modules/@wordpress/hooks/build-module/index.mjs");
// packages/deprecated/src/index.ts

var logged = /* @__PURE__ */ Object.create(null);
function deprecated(feature, options = {}) {
  const { since, version, alternative, plugin, link, hint } = options;
  const pluginMessage = plugin ? ` from ${plugin}` : "";
  const sinceMessage = since ? ` since version ${since}` : "";
  const versionMessage = version ? ` and will be removed${pluginMessage} in version ${version}` : "";
  const useInsteadMessage = alternative ? ` Please use ${alternative} instead.` : "";
  const linkMessage = link ? ` See: ${link}` : "";
  const hintMessage = hint ? ` Note: ${hint}` : "";
  const message = `${feature} is deprecated${sinceMessage}${versionMessage}.${useInsteadMessage}${linkMessage}${hintMessage}`;
  if (message in logged) {
    return;
  }
  (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)("deprecated", feature, options, message);
  console.warn(message);
  logged[message] = true;
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+primitives@4.57._32fe7e38935c407cea8bd9e57e6c56c9/node_modules/@wordpress/primitives/build-module/svg/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t4: () => (/* binding */ SVG),
/* harmony export */   wA: () => (/* binding */ Path)
/* harmony export */ });
/* unused harmony exports Circle, Defs, G, Line, LinearGradient, Polygon, RadialGradient, Rect, Stop */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/primitives/src/svg/index.jsx



var Circle = (props) => createElement("circle", props);
var G = (props) => createElement("g", props);
var Line = (props) => createElement("line", props);
var Path = (props) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("path", props);
var Polygon = (props) => createElement("polygon", props);
var Rect = (props) => createElement("rect", props);
var Defs = (props) => createElement("defs", props);
var RadialGradient = (props) => createElement("radialGradient", props);
var LinearGradient = (props) => createElement("linearGradient", props);
var Stop = (props) => createElement("stop", props);
var SVG = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.forwardRef)(
  /**
   * @param {SVGProps}                          props isPressed indicates whether the SVG should appear as pressed.
   *                                                  Other props will be passed through to svg component.
   * @param {React.ForwardedRef<SVGSVGElement>} ref   The forwarded ref to the SVG element.
   *
   * @return {React.JSX.Element} Stop component
   */
  ({ className, isPressed, ...props }, ref) => {
    const appliedProps = {
      ...props,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, { "is-pressed": isPressed }) || void 0,
      "aria-hidden": true,
      focusable: false
    };
    return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("svg", { ...appliedProps, ref });
  }
);
SVG.displayName = "SVG";

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export clsx */
function r(e){var t,f,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(f=r(e[t]))&&(n&&(n+=" "),n+=f)}else for(f in e)e[f]&&(n&&(n+=" "),n+=f);return n}function clsx(){for(var e,t,f=0,n="",o=arguments.length;f<o;f++)(e=arguments[f])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (clsx);

/***/ }),

/***/ "../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/cjs/use-sync-external-store-shim.production.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

/**
 * @license React
 * use-sync-external-store-shim.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */


var React = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
function is(x, y) {
  return (x === y && (0 !== x || 1 / x === 1 / y)) || (x !== x && y !== y);
}
var objectIs = "function" === typeof Object.is ? Object.is : is,
  useState = React.useState,
  useEffect = React.useEffect,
  useLayoutEffect = React.useLayoutEffect,
  useDebugValue = React.useDebugValue;
function useSyncExternalStore$2(subscribe, getSnapshot) {
  var value = getSnapshot(),
    _useState = useState({ inst: { value: value, getSnapshot: getSnapshot } }),
    inst = _useState[0].inst,
    forceUpdate = _useState[1];
  useLayoutEffect(
    function () {
      inst.value = value;
      inst.getSnapshot = getSnapshot;
      checkIfSnapshotChanged(inst) && forceUpdate({ inst: inst });
    },
    [subscribe, value, getSnapshot]
  );
  useEffect(
    function () {
      checkIfSnapshotChanged(inst) && forceUpdate({ inst: inst });
      return subscribe(function () {
        checkIfSnapshotChanged(inst) && forceUpdate({ inst: inst });
      });
    },
    [subscribe]
  );
  useDebugValue(value);
  return value;
}
function checkIfSnapshotChanged(inst) {
  var latestGetSnapshot = inst.getSnapshot;
  inst = inst.value;
  try {
    var nextValue = latestGetSnapshot();
    return !objectIs(inst, nextValue);
  } catch (error) {
    return !0;
  }
}
function useSyncExternalStore$1(subscribe, getSnapshot) {
  return getSnapshot();
}
var shim =
  "undefined" === typeof window ||
  "undefined" === typeof window.document ||
  "undefined" === typeof window.document.createElement
    ? useSyncExternalStore$1
    : useSyncExternalStore$2;
exports.useSyncExternalStore =
  void 0 !== React.useSyncExternalStore ? React.useSyncExternalStore : shim;


/***/ }),

/***/ "../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/shim/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/cjs/use-sync-external-store-shim.production.js");
} else {}


/***/ })

}]);