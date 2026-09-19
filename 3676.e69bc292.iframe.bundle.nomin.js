"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3676],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ modal_default)
});

// UNUSED EXPORTS: Modal

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-focus-on-mount/index.mjs
var use_focus_on_mount = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-focus-on-mount/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-constrained-tabbing/index.mjs
var use_constrained_tabbing = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-constrained-tabbing/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-focus-return/index.mjs
var use_focus_return = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-focus-return/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close.mjs
var library_close = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close.mjs");
;// ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/utils/assert-is-defined.mjs
// packages/dom/src/utils/assert-is-defined.ts
function assertIsDefined(val, name) {
  if (false) {}
}

//# sourceMappingURL=assert-is-defined.mjs.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/dom/get-computed-style.mjs
// packages/dom/src/dom/get-computed-style.js

function getComputedStyle(element) {
  assertIsDefined(
    element.ownerDocument.defaultView,
    "element.ownerDocument.defaultView"
  );
  return element.ownerDocument.defaultView.getComputedStyle(element);
}

//# sourceMappingURL=get-computed-style.mjs.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/dom/get-scroll-container.mjs
// packages/dom/src/dom/get-scroll-container.js

function getScrollContainer(node, direction = "vertical") {
  if (!node) {
    return void 0;
  }
  if (direction === "vertical" || direction === "all") {
    if (node.scrollHeight > node.clientHeight) {
      const { overflowY } = getComputedStyle(node);
      if (/(auto|scroll)/.test(overflowY)) {
        return node;
      }
    }
  }
  if (direction === "horizontal" || direction === "all") {
    if (node.scrollWidth > node.clientWidth) {
      const { overflowX } = getComputedStyle(node);
      if (/(auto|scroll)/.test(overflowX)) {
        return node;
      }
    }
  }
  if (node.ownerDocument === node.parentNode) {
    return node;
  }
  return getScrollContainer(
    /** @type {Element} */
    node.parentNode,
    direction
  );
}

//# sourceMappingURL=get-scroll-container.mjs.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/aria-helper.js
const LIVE_REGION_ARIA_ROLES = /* @__PURE__ */ new Set(["alert", "status", "log", "marquee", "timer"]);
const hiddenElementsByDepth = [];
function modalize(modalElement) {
  const elements = Array.from(document.body.children);
  const hiddenElements = [];
  hiddenElementsByDepth.push(hiddenElements);
  for (const element of elements) {
    if (element === modalElement) {
      continue;
    }
    if (elementShouldBeHidden(element)) {
      element.setAttribute("aria-hidden", "true");
      hiddenElements.push(element);
    }
  }
}
function elementShouldBeHidden(element) {
  const role = element.getAttribute("role");
  return !(element.tagName === "SCRIPT" || element.hasAttribute("hidden") || element.hasAttribute("aria-hidden") || element.hasAttribute("aria-live") || role && LIVE_REGION_ARIA_ROLES.has(role));
}
function unmodalize() {
  const hiddenElements = hiddenElementsByDepth.pop();
  if (!hiddenElements) {
    return;
  }
  for (const element of hiddenElements) {
    element.removeAttribute("aria-hidden");
  }
}

//# sourceMappingURL=aria-helper.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/style-provider/index.js
var style_provider = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/style-provider/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/with-ignore-ime-events.js
var with_ignore_ime_events = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/with-ignore-ime-events.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spacer/component.js + 1 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spacer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-reduced-motion/index.mjs
var use_reduced_motion = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-reduced-motion/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+warning@3.48.1/node_modules/@wordpress/warning/build-module/index.mjs + 1 modules
var warning_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+warning@3.48.1/node_modules/@wordpress/warning/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/use-modal-exit-animation.js




const FRAME_ANIMATION_DURATION = config_values/* default */.A.transitionDuration;
const FRAME_ANIMATION_DURATION_NUMBER = Number.parseInt(config_values/* default */.A.transitionDuration);
const EXIT_ANIMATION_NAME = "components-modal__disappear-animation";
function useModalExitAnimation() {
  const frameRef = (0,react.useRef)();
  const [isAnimatingOut, setIsAnimatingOut] = (0,react.useState)(false);
  const isReducedMotion = (0,use_reduced_motion/* default */.A)();
  const closeModal = (0,react.useCallback)(() => new Promise((closeModalResolve) => {
    const frameEl = frameRef.current;
    if (isReducedMotion) {
      closeModalResolve();
      return;
    }
    if (!frameEl) {
      globalThis.SCRIPT_DEBUG === true ? (0,warning_build_module/* default */.A)("wp.components.Modal: the Modal component can't be closed with an exit animation because of a missing reference to the modal frame element.") : void 0;
      closeModalResolve();
      return;
    }
    let handleAnimationEnd;
    const startAnimation = () => new Promise((animationResolve) => {
      handleAnimationEnd = (e) => {
        if (e.animationName === EXIT_ANIMATION_NAME) {
          animationResolve();
        }
      };
      frameEl.addEventListener("animationend", handleAnimationEnd);
      setIsAnimatingOut(true);
    });
    const animationTimeout = () => new Promise((timeoutResolve) => {
      setTimeout(
        () => timeoutResolve(),
        // Allow an extra 20% of the animation duration for the
        // animationend event to fire, in case the animation frame is
        // slightly delayes by some other events in the event loop.
        FRAME_ANIMATION_DURATION_NUMBER * 1.2
      );
    });
    Promise.race([startAnimation(), animationTimeout()]).then(() => {
      if (handleAnimationEnd) {
        frameEl.removeEventListener("animationend", handleAnimationEnd);
      }
      setIsAnimatingOut(false);
      closeModalResolve();
    });
  }), [isReducedMotion]);
  return {
    overlayClassname: isAnimatingOut ? "is-animating-out" : void 0,
    frameRef,
    frameStyle: {
      "--modal-frame-animation-duration": `${FRAME_ANIMATION_DURATION}`
    },
    closeModal
  };
}

//# sourceMappingURL=use-modal-exit-animation.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/index.js













const ModalContext = (0,react.createContext)(/* @__PURE__ */ new Set());
ModalContext.displayName = "ModalContext";
const bodyOpenClasses = /* @__PURE__ */ new Map();
function UnforwardedModal(props, forwardedRef) {
  const {
    bodyOpenClassName = "modal-open",
    role = "dialog",
    title = null,
    focusOnMount = true,
    shouldCloseOnEsc = true,
    shouldCloseOnClickOutside = true,
    isDismissible = true,
    /* Accessibility. */
    aria = {
      labelledby: void 0,
      describedby: void 0
    },
    onRequestClose,
    icon,
    closeButtonLabel,
    children,
    style,
    overlayClassName: overlayClassnameProp,
    className,
    contentLabel,
    onKeyDown,
    isFullScreen = false,
    size,
    headerActions = null,
    __experimentalHideHeader = false
  } = props;
  const ref = (0,react.useRef)();
  const instanceId = (0,use_instance_id/* default */.A)(Modal);
  const headingId = title ? `components-modal-header-${instanceId}` : aria.labelledby;
  const focusOnMountRef = (0,use_focus_on_mount/* useFocusOnMount */.u)(focusOnMount === "firstContentElement" ? "firstElement" : focusOnMount);
  const constrainedTabbingRef = (0,use_constrained_tabbing/* default */.A)();
  const focusReturnRef = (0,use_focus_return/* default */.A)();
  const contentRef = (0,react.useRef)(null);
  const childrenContainerRef = (0,react.useRef)(null);
  const [hasScrolledContent, setHasScrolledContent] = (0,react.useState)(false);
  const [hasScrollableContent, setHasScrollableContent] = (0,react.useState)(false);
  let sizeClass;
  if (isFullScreen || size === "fill") {
    sizeClass = "is-full-screen";
  } else if (size) {
    sizeClass = `has-size-${size}`;
  }
  const isContentScrollable = (0,react.useCallback)(() => {
    if (!contentRef.current) {
      return;
    }
    const closestScrollContainer = getScrollContainer(contentRef.current);
    if (contentRef.current === closestScrollContainer) {
      setHasScrollableContent(true);
    } else {
      setHasScrollableContent(false);
    }
  }, [contentRef]);
  (0,react.useEffect)(() => {
    modalize(ref.current);
    return () => unmodalize();
  }, []);
  const onRequestCloseRef = (0,react.useRef)();
  (0,react.useEffect)(() => {
    onRequestCloseRef.current = onRequestClose;
  }, [onRequestClose]);
  const dismissers = (0,react.useContext)(ModalContext);
  const [nestedDismissers] = (0,react.useState)(() => /* @__PURE__ */ new Set());
  (0,react.useEffect)(() => {
    dismissers.add(onRequestCloseRef);
    for (const dismisser of dismissers) {
      if (dismisser !== onRequestCloseRef) {
        dismisser.current?.();
      }
    }
    return () => {
      for (const dismisser of nestedDismissers) {
        dismisser.current?.();
      }
      dismissers.delete(onRequestCloseRef);
    };
  }, [dismissers, nestedDismissers]);
  (0,react.useEffect)(() => {
    var _bodyOpenClasses$get;
    const theClass = bodyOpenClassName;
    const oneMore = 1 + ((_bodyOpenClasses$get = bodyOpenClasses.get(theClass)) !== null && _bodyOpenClasses$get !== void 0 ? _bodyOpenClasses$get : 0);
    bodyOpenClasses.set(theClass, oneMore);
    document.body.classList.add(bodyOpenClassName);
    return () => {
      const oneLess = bodyOpenClasses.get(theClass) - 1;
      if (oneLess === 0) {
        document.body.classList.remove(theClass);
        bodyOpenClasses.delete(theClass);
      } else {
        bodyOpenClasses.set(theClass, oneLess);
      }
    };
  }, [bodyOpenClassName]);
  const {
    closeModal,
    frameRef,
    frameStyle,
    overlayClassname
  } = useModalExitAnimation();
  (0,react.useLayoutEffect)(() => {
    if (!window.ResizeObserver || !childrenContainerRef.current) {
      return;
    }
    const resizeObserver = new ResizeObserver(isContentScrollable);
    resizeObserver.observe(childrenContainerRef.current);
    isContentScrollable();
    return () => {
      resizeObserver.disconnect();
    };
  }, [isContentScrollable, childrenContainerRef]);
  function handleEscapeKeyDown(event) {
    if (shouldCloseOnEsc && (event.code === "Escape" || event.key === "Escape") && !event.defaultPrevented) {
      event.preventDefault();
      closeModal().then(() => onRequestClose(event));
    }
  }
  const onContentContainerScroll = (0,react.useCallback)((e) => {
    var _e$currentTarget$scro;
    const scrollY = (_e$currentTarget$scro = e?.currentTarget?.scrollTop) !== null && _e$currentTarget$scro !== void 0 ? _e$currentTarget$scro : -1;
    if (!hasScrolledContent && scrollY > 0) {
      setHasScrolledContent(true);
    } else if (hasScrolledContent && scrollY <= 0) {
      setHasScrolledContent(false);
    }
  }, [hasScrolledContent]);
  let pressTarget = null;
  const overlayPressHandlers = {
    onPointerDown: (event) => {
      if (event.target === event.currentTarget) {
        pressTarget = event.target;
        event.preventDefault();
      }
    },
    // Closes the modal with two exceptions. 1. Opening the context menu on
    // the overlay. 2. Pressing on the overlay then dragging the pointer
    // over the modal and releasing. Due to the modal being a child of the
    // overlay, such a gesture is a `click` on the overlay and cannot be
    // excepted by a `click` handler. Thus the tactic of handling
    // `pointerup` and comparing its target to that of the `pointerdown`.
    onPointerUp: ({
      target,
      button
    }) => {
      const isSameTarget = target === pressTarget;
      pressTarget = null;
      if (button === 0 && isSameTarget) {
        closeModal().then(() => onRequestClose());
      }
    }
  };
  const modal = (
    // eslint-disable-next-line jsx-a11y/no-static-element-interactions
    /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
      ref: (0,use_merge_refs/* default */.A)([ref, forwardedRef]),
      className: (0,clsx/* default */.A)("components-modal__screen-overlay", overlayClassname, overlayClassnameProp),
      onKeyDown: (0,with_ignore_ime_events/* withIgnoreIMEEvents */.n)(handleEscapeKeyDown),
      ...shouldCloseOnClickOutside ? overlayPressHandlers : {},
      children: /* @__PURE__ */ (0,jsx_runtime.jsx)(style_provider/* default */.A, {
        document,
        children: /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
          className: (0,clsx/* default */.A)("components-modal__frame", sizeClass, className),
          style: {
            ...frameStyle,
            ...style
          },
          ref: (0,use_merge_refs/* default */.A)([frameRef, constrainedTabbingRef, focusReturnRef, focusOnMount !== "firstContentElement" ? focusOnMountRef : null]),
          role,
          "aria-label": contentLabel,
          "aria-labelledby": contentLabel ? void 0 : headingId,
          "aria-describedby": aria.describedby,
          tabIndex: -1,
          onKeyDown,
          children: /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
            className: (0,clsx/* default */.A)("components-modal__content", {
              "hide-header": __experimentalHideHeader,
              "is-scrollable": hasScrollableContent,
              "has-scrolled-content": hasScrolledContent
            }),
            role: "document",
            onScroll: onContentContainerScroll,
            ref: contentRef,
            "aria-label": hasScrollableContent ? (0,build_module.__)("Scrollable section") : void 0,
            tabIndex: hasScrollableContent ? 0 : void 0,
            children: [!__experimentalHideHeader && /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
              className: "components-modal__header",
              children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
                className: "components-modal__header-heading-container",
                children: [icon && /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
                  className: "components-modal__icon-container",
                  "aria-hidden": true,
                  children: icon
                }), title && /* @__PURE__ */ (0,jsx_runtime.jsx)("h1", {
                  id: headingId,
                  className: "components-modal__header-heading",
                  children: title
                })]
              }), headerActions, isDismissible && /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
                children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
                  marginBottom: 0,
                  marginLeft: 2
                }), /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  size: "compact",
                  onClick: (event) => closeModal().then(() => onRequestClose(event)),
                  icon: library_close/* default */.A,
                  label: closeButtonLabel || (0,build_module.__)("Close")
                })]
              })]
            }), /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
              ref: (0,use_merge_refs/* default */.A)([childrenContainerRef, focusOnMount === "firstContentElement" ? focusOnMountRef : null]),
              children
            })]
          })
        })
      })
    })
  );
  return (0,react_dom.createPortal)(/* @__PURE__ */ (0,jsx_runtime.jsx)(ModalContext.Provider, {
    value: nestedDismissers,
    children: modal
  }), document.body);
}
const Modal = (0,react.forwardRef)(UnforwardedModal);
var modal_default = Modal;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/rtl.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ rtl)
/* harmony export */ });
/* unused harmony export convertLTRToRTL */
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");


const LOWER_LEFT_REGEXP = new RegExp(/-left/g);
const LOWER_RIGHT_REGEXP = new RegExp(/-right/g);
const UPPER_LEFT_REGEXP = new RegExp(/Left/g);
const UPPER_RIGHT_REGEXP = new RegExp(/Right/g);
function getConvertedKey(key) {
  if (key === "left") {
    return "right";
  }
  if (key === "right") {
    return "left";
  }
  if (LOWER_LEFT_REGEXP.test(key)) {
    return key.replace(LOWER_LEFT_REGEXP, "-right");
  }
  if (LOWER_RIGHT_REGEXP.test(key)) {
    return key.replace(LOWER_RIGHT_REGEXP, "-left");
  }
  if (UPPER_LEFT_REGEXP.test(key)) {
    return key.replace(UPPER_LEFT_REGEXP, "Right");
  }
  if (UPPER_RIGHT_REGEXP.test(key)) {
    return key.replace(UPPER_RIGHT_REGEXP, "Left");
  }
  return key;
}
const convertLTRToRTL = (ltrStyles = {}) => {
  return Object.fromEntries(Object.entries(ltrStyles).map(([key, value]) => [getConvertedKey(key), value]));
};
function rtl(ltrStyles = {}, rtlStyles) {
  return () => {
    if (rtlStyles) {
      return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .isRTL */ .V8)() ? /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)(rtlStyles,  true ? "" : 0,  true ? "" : 0) : /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)(ltrStyles,  true ? "" : 0,  true ? "" : 0);
    }
    return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .isRTL */ .V8)() ? /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)(convertLTRToRTL(ltrStyles),  true ? "" : 0,  true ? "" : 0) : /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_1__/* .css */ .AH)(ltrStyles,  true ? "" : 0,  true ? "" : 0);
  };
}
rtl.watch = () => (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .isRTL */ .V8)();

//# sourceMappingURL=rtl.js.map


/***/ })

}]);