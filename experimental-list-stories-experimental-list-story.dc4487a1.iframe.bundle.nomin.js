"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4638],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _assertThisInitialized)
/* harmony export */ });
function _assertThisInitialized(e) {
  if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  return e;
}


/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _inheritsLoose)
});

;// ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js
function _setPrototypeOf(t, e) {
  return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) {
    return t.__proto__ = e, t;
  }, _setPrototypeOf(t, e);
}

;// ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js

function _inheritsLoose(t, o) {
  t.prototype = Object.create(o.prototype), t.prototype.constructor = t, _setPrototypeOf(t, o);
}


/***/ }),

/***/ "../../packages/js/experimental/src/experimental-list/stories/experimental-list.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  CollapsibleListExample: () => (/* binding */ CollapsibleListExample),
  Primary: () => (/* binding */ Primary),
  TaskItemExample: () => (/* binding */ TaskItemExample),
  "default": () => (/* binding */ experimental_list_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js + 1 modules
var TransitionGroup = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js + 3 modules
var CSSTransition = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/experimental/src/experimental-list/experimental-list.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const ExperimentalList = ({
  children,
  listType,
  animation = 'none',
  // Allow passing any other property overrides that are legal on an HTML element
  ...otherProps
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(TransitionGroup/* default */.A, {
    component: listType || 'ul',
    className: "woocommerce-experimental-list",
    ...otherProps,
    children: react.Children.map(children, child => {
      if ((0,react.isValidElement)(child)) {
        const {
          onExited,
          in: inTransition,
          enter,
          exit,
          ...remainingProps
        } = child.props;
        const animationProp = remainingProps.animation || animation;
        return /*#__PURE__*/(0,jsx_runtime.jsx)(CSSTransition/* default */.A, {
          timeout: 500,
          onExited: onExited,
          in: inTransition,
          enter: enter,
          exit: exit,
          classNames: "woocommerce-list__item",
          children: (0,react.cloneElement)(child, {
            animation: animationProp,
            ...remainingProps
          })
        });
      }
      return child;
      // TODO - create a less restrictive type definition for children of react-transition-group. React.Children.map seems incompatible with the type expected by `children`.
    })
  });
};
try {
    // @ts-ignore
    ExperimentalList.displayName = "ExperimentalList";
    // @ts-ignore
    ExperimentalList.__docgenInfo = { "description": "", "displayName": "ExperimentalList", "props": { "listType": { "defaultValue": null, "description": "", "name": "listType", "required": false, "type": { "name": "enum", "value": [{ "value": "\"ol\"" }, { "value": "\"ul\"" }] } }, "animation": { "defaultValue": { value: "none" }, "description": "", "name": "animation", "required": false, "type": { "name": "enum", "value": [{ "value": "\"slide-right\"" }, { "value": "\"none\"" }, { "value": "\"custom\"" }] } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/experimental/src/experimental-list/experimental-list.tsx#ExperimentalList"] = { docgenInfo: ExperimentalList.__docgenInfo, name: "ExperimentalList", path: "../../packages/js/experimental/src/experimental-list/experimental-list.tsx#ExperimentalList" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
;// ../../packages/js/experimental/src/experimental-list/experimental-list-item.tsx
/**
 * External dependencies
 */




function handleKeyDown(event, onClick) {
  if (typeof onClick === 'function' && event.keyCode === build_module/* ENTER */.Fm) {
    onClick(event);
  }
}
const ExperimentalListItem = ({
  children,
  disableGutters = false,
  animation = 'none',
  className = '',
  // extract out the props that must be passed down from TransitionGroup
  exit,
  enter,
  onExited,
  // in is a TS reserved keyword so can't be a variable name
  in: transitionIn,
  ...otherProps
}) => {
  // for styling purposes only
  const hasAction = !!otherProps?.onClick;
  const roleProps = hasAction ? {
    role: 'button',
    onKeyDown: e => handleKeyDown(e, otherProps.onClick),
    tabIndex: 0
  } : {};
  const tagClasses = (0,clsx/* default */.A)({
    'has-action': hasAction,
    'has-gutters': !disableGutters,
    // since there is only one valid animation right now, any other value disables them.
    'transitions-disabled': animation !== 'slide-right'
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(CSSTransition/* default */.A, {
    timeout: 500,
    classNames: className || 'woocommerce-list__item',
    in: transitionIn,
    exit: exit,
    enter: enter,
    onExited: onExited,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
      ...roleProps,
      ...otherProps,
      className: `woocommerce-experimental-list__item ${tagClasses} ${className}`,
      children: children
    })
  });
};
try {
    // @ts-ignore
    ExperimentalListItem.displayName = "ExperimentalListItem";
    // @ts-ignore
    ExperimentalListItem.__docgenInfo = { "description": "", "displayName": "ExperimentalListItem", "props": { "disableGutters": { "defaultValue": { value: "false" }, "description": "", "name": "disableGutters", "required": false, "type": { "name": "boolean" } }, "animation": { "defaultValue": { value: "none" }, "description": "", "name": "animation", "required": false, "type": { "name": "enum", "value": [{ "value": "\"slide-right\"" }, { "value": "\"none\"" }, { "value": "\"custom\"" }] } }, "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "in": { "defaultValue": null, "description": "", "name": "in", "required": false, "type": { "name": "boolean" } }, "exit": { "defaultValue": null, "description": "", "name": "exit", "required": false, "type": { "name": "boolean" } }, "enter": { "defaultValue": null, "description": "", "name": "enter", "required": false, "type": { "name": "boolean" } }, "onExited": { "defaultValue": null, "description": "", "name": "onExited", "required": false, "type": { "name": "(() => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/experimental/src/experimental-list/experimental-list-item.tsx#ExperimentalListItem"] = { docgenInfo: ExperimentalListItem.__docgenInfo, name: "ExperimentalListItem", path: "../../packages/js/experimental/src/experimental-list/experimental-list-item.tsx#ExperimentalListItem" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/Transition.js + 1 modules
var Transition = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/Transition.js");
;// ../../packages/js/experimental/src/experimental-list/collapsible-list/index.tsx
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



const defaultStyle = {
  transitionProperty: 'max-height',
  transitionDuration: '500ms',
  maxHeight: 0,
  overflow: 'hidden'
};
function getContainerHeight(collapseContainer) {
  let containerHeight = 0;
  if (collapseContainer) {
    for (const child of collapseContainer.children) {
      containerHeight += child.clientHeight;
      const style = window.getComputedStyle(child);
      containerHeight += parseInt(style.marginTop, 10) || 0;
      containerHeight += parseInt(style.marginBottom, 10) || 0;
    }
  }
  return containerHeight;
}

/**
 * This functions returns a new list of shown children depending on the new children updates.
 * If one is removed, it will remove it from the show array.
 * If one is added, it will add it back to the shown list, making use of the new children list to keep order.
 *
 * @param {Array.<import('react').ReactElement>} currentChildren      a list of the current children.
 * @param {Array.<import('react').ReactElement>} currentShownChildren a list of the current shown children.
 * @param {Array.<import('react').ReactElement>} newChildren          a list of the new children.
 * @return {Array.<import('react').ReactElement>} new list of children that should be shown.
 */
function getUpdatedShownChildren(currentChildren, currentShownChildren, newChildren) {
  if (newChildren.length < currentChildren.length) {
    const newChildrenKeys = newChildren.map(child => child.key);
    // Filter out removed child
    return currentShownChildren.filter(item => item.key && newChildrenKeys.includes(item.key));
  }
  const currentShownChildrenKeys = currentShownChildren.map(child => child.key);
  const currentChildrenKeys = currentChildren.map(child => child.key);
  // Add new child back in.
  return newChildren.filter(child => child.key && (currentShownChildrenKeys.includes(child.key) || !currentChildrenKeys.includes(child.key)));
}
const getTransitionStyle = (state, isCollapsed, elementRef) => {
  let maxHeight = 0;
  if ((state === 'entered' || state === 'entering') && elementRef) {
    maxHeight = getContainerHeight(elementRef);
  }
  const styles = {
    ...defaultStyle,
    maxHeight
  };

  // only include transition styles when entering or exiting.
  if (state !== 'entering' && state !== 'exiting') {
    delete styles.transitionDuration;
    delete styles.transition;
    delete styles.transitionProperty;
  }
  // Remove maxHeight when entered, so we do not need to worry about nested items changing height while expanded.
  if (state === 'entered' && !isCollapsed) {
    delete styles.maxHeight;
  }
  return styles;
};
const ExperimentalCollapsibleList = ({
  children,
  collapsed = true,
  collapseLabel,
  expandLabel,
  show = 0,
  onCollapse,
  onExpand,
  direction = 'up',
  ...listProps
}) => {
  const [isCollapsed, setCollapsed] = (0,react.useState)(collapsed);
  const [isTransitionComponentCollapsed, setTransitionComponentCollapsed] = (0,react.useState)(collapsed);
  const [footerLabels, setFooterLabels] = (0,react.useState)({
    collapse: collapseLabel,
    expand: expandLabel
  });
  const [displayedChildren, setDisplayedChildren] = (0,react.useState)({
    all: [],
    shown: [],
    hidden: []
  });
  const collapseContainerRef = (0,react.useRef)(null);
  const updateChildren = () => {
    let shownChildren = [];
    const allChildren = react.Children.map(children, child => (0,react.isValidElement)(child) && 'key' in child ? child : null) || [];
    let hiddenChildren = allChildren;
    if (show > 0) {
      shownChildren = allChildren.slice(0, show);
      hiddenChildren = allChildren.slice(show);
    }
    if (hiddenChildren.length > 0) {
      // Only update when footer will be shown, this way it won't update mid transition if the outer component
      // updates the label as well.
      setFooterLabels({
        expand: expandLabel,
        collapse: collapseLabel
      });
    }
    setDisplayedChildren({
      all: allChildren,
      shown: shownChildren,
      hidden: hiddenChildren
    });
  };

  // This allows for an extra render cycle that adds the maxHeight back in before the exiting transition.
  // This way the exiting transition still works correctly.
  (0,react.useEffect)(() => {
    setTransitionComponentCollapsed(isCollapsed);
  }, [isCollapsed]);
  (0,react.useEffect)(() => {
    const allChildren = react.Children.map(children, child => (0,react.isValidElement)(child) && 'key' in child ? child : null) || [];
    if (displayedChildren.all.length > 0 && isCollapsed && listProps.animation !== 'none') {
      setDisplayedChildren({
        ...displayedChildren,
        shown: getUpdatedShownChildren(displayedChildren.all, displayedChildren.shown, allChildren)
      });
      // Update the hidden children after the remove/add transition is done, making the transition less busy.
      setTimeout(() => {
        updateChildren();
      }, 500);
    } else {
      updateChildren();
    }
  }, [children]);
  const triggerCallbacks = newCollapseValue => {
    if (onCollapse && newCollapseValue) {
      onCollapse();
    }
    if (onExpand && !newCollapseValue) {
      onExpand();
    }
  };
  const clickHandler = (0,react.useCallback)(() => {
    setCollapsed(!isCollapsed);
    triggerCallbacks(!isCollapsed);
  }, [isCollapsed]);
  const listClasses = (0,clsx/* default */.A)(listProps.className || '', 'woocommerce-experimental-list');
  const wrapperClasses = (0,clsx/* default */.A)({
    'woocommerce-experimental-list-wrapper': !isCollapsed
  });
  const hiddenChildren = displayedChildren.hidden.length > 0 ? /*#__PURE__*/(0,jsx_runtime.jsxs)(ExperimentalListItem, {
    className: "list-item-collapse",
    onClick: clickHandler,
    animation: "none",
    disableGutters: true,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
      children: isCollapsed ? footerLabels.expand : footerLabels.collapse
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
      className: "list-item-collapse__icon",
      size: 30,
      icon: isCollapsed ? chevron_down/* default */.A : chevron_up/* default */.A
    })]
  }, "collapse-item") : null;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalList, {
    ...listProps,
    className: listClasses,
    children: [direction === 'down' && hiddenChildren, ...displayedChildren.shown, /*#__PURE__*/(0,jsx_runtime.jsx)(Transition/* default */.Ay, {
      timeout: 500,
      in: !isTransitionComponentCollapsed,
      mountOnEnter: true,
      unmountOnExit: false,
      children: state => {
        const transitionStyles = getTransitionStyle(state, isCollapsed, collapseContainerRef.current);
        return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: wrapperClasses,
          ref: collapseContainerRef,
          style: transitionStyles,
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(TransitionGroup/* default */.A, {
            className: "woocommerce-experimental-list",
            children: react.Children.map(displayedChildren.hidden, child => {
              const {
                onExited,
                in: inTransition,
                enter,
                exit,
                ...remainingProps
              } = child.props;
              const animationProp = remainingProps.animation || listProps.animation;
              return /*#__PURE__*/(0,jsx_runtime.jsx)(CSSTransition/* default */.A, {
                timeout: 500,
                onExited: onExited,
                in: inTransition,
                enter: enter,
                exit: exit,
                classNames: "woocommerce-list__item",
                children: (0,react.cloneElement)(child, {
                  animation: animationProp,
                  ...remainingProps
                })
              }, child.key);
            })
          })
        });
      }
    }, "remaining-children"), direction === 'up' && hiddenChildren]
  });
};
try {
    // @ts-ignore
    ExperimentalCollapsibleList.displayName = "ExperimentalCollapsibleList";
    // @ts-ignore
    ExperimentalCollapsibleList.__docgenInfo = { "description": "", "displayName": "ExperimentalCollapsibleList", "props": { "collapseLabel": { "defaultValue": null, "description": "", "name": "collapseLabel", "required": true, "type": { "name": "string" } }, "expandLabel": { "defaultValue": null, "description": "", "name": "expandLabel", "required": true, "type": { "name": "string" } }, "collapsed": { "defaultValue": { value: "true" }, "description": "", "name": "collapsed", "required": false, "type": { "name": "boolean" } }, "show": { "defaultValue": { value: "0" }, "description": "", "name": "show", "required": false, "type": { "name": "number" } }, "onCollapse": { "defaultValue": null, "description": "", "name": "onCollapse", "required": false, "type": { "name": "(() => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "(() => void)" } }, "direction": { "defaultValue": { value: "up" }, "description": "", "name": "direction", "required": false, "type": { "name": "enum", "value": [{ "value": "\"up\"" }, { "value": "\"down\"" }] } }, "listType": { "defaultValue": null, "description": "", "name": "listType", "required": false, "type": { "name": "enum", "value": [{ "value": "\"ol\"" }, { "value": "\"ul\"" }] } }, "animation": { "defaultValue": null, "description": "", "name": "animation", "required": false, "type": { "name": "enum", "value": [{ "value": "\"slide-right\"" }, { "value": "\"none\"" }, { "value": "\"custom\"" }] } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/experimental/src/experimental-list/collapsible-list/index.tsx#ExperimentalCollapsibleList"] = { docgenInfo: ExperimentalCollapsibleList.__docgenInfo, name: "ExperimentalCollapsibleList", path: "../../packages/js/experimental/src/experimental-list/collapsible-list/index.tsx#ExperimentalCollapsibleList" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.js
var check = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js + 42 modules
var build_module_tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/notice-outline.js
var notice_outline = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/notice-outline.js");
// EXTERNAL MODULE: ../../packages/js/components/src/ellipsis-menu/index.tsx
var ellipsis_menu = __webpack_require__("../../packages/js/components/src/ellipsis-menu/index.tsx");
// EXTERNAL MODULE: ../../packages/js/sanitize/src/index.ts + 3 modules
var src = __webpack_require__("../../packages/js/sanitize/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/experimental/src/text.ts
var src_text = __webpack_require__("../../packages/js/experimental/src/text.ts");
// EXTERNAL MODULE: ../../packages/js/experimental/src/vertical-css-transition/vertical-css-transition.tsx
var vertical_css_transition = __webpack_require__("../../packages/js/experimental/src/vertical-css-transition/vertical-css-transition.tsx");
;// ../../packages/js/experimental/src/experimental-list/task-item/index.tsx
/**
 * External dependencies
 */









/**
 * Internal dependencies
 */




const ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p', 'br'];
const ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download'];
const OptionalTaskTooltip = ({
  level,
  completed,
  children
}) => {
  let tooltip = '';
  if (level === 1 && !completed) {
    tooltip = (0,i18n_build_module.__)('This task is required to keep your store running', 'woocommerce');
  } else if (level === 2 && !completed) {
    tooltip = (0,i18n_build_module.__)('This task is required to set up your extension', 'woocommerce');
  }
  if (tooltip === '') {
    return children;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_tooltip/* default */.Ay, {
    text: tooltip,
    children: children
  });
};
const OptionalExpansionWrapper = ({
  children,
  expandable,
  expanded
}) => {
  if (!expandable) {
    return expanded ? /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
      children: children
    }) : null;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(vertical_css_transition/* VerticalCSSTransition */.H, {
    timeout: 500,
    in: expanded,
    classNames: "woocommerce-task-list__item-expandable-content",
    defaultStyle: {
      transitionProperty: 'max-height, opacity'
    },
    children: children
  });
};
const TaskItem = ({
  completed,
  inProgress,
  inProgressLabel,
  title,
  badge,
  onDelete,
  onCollapse,
  onDismiss,
  onSnooze,
  onExpand,
  onClick,
  additionalInfo,
  time,
  content,
  expandable = false,
  expanded = false,
  showActionButton,
  level = 3,
  action,
  actionLabel,
  secondaryAction,
  ...listItemProps
}) => {
  const [isTaskExpanded, setTaskExpanded] = (0,react.useState)(expanded);
  (0,react.useEffect)(() => {
    setTaskExpanded(expanded);
  }, [expanded]);
  const className = (0,clsx/* default */.A)('woocommerce-task-list__item', {
    complete: completed,
    expanded: isTaskExpanded,
    'level-2': level === 2 && !completed,
    'level-1': level === 1 && !completed
  });
  if (showActionButton === undefined) {
    showActionButton = expandable;
  }
  const showEllipsisMenu = (onDismiss || onSnooze) && !completed || onDelete && completed;
  const toggleActionVisibility = () => {
    setTaskExpanded(!isTaskExpanded);
    if (isTaskExpanded && onExpand) {
      onExpand();
    }
    if (!isTaskExpanded && onCollapse) {
      onCollapse();
    }
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(ExperimentalListItem, {
    disableGutters: true,
    className: className,
    onClick: expandable && showActionButton ? toggleActionVisibility : onClick,
    ...listItemProps,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(OptionalTaskTooltip, {
      level: level,
      completed: completed,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-task-list__item-before",
        children: level === 1 && !completed ? /*#__PURE__*/(0,jsx_runtime.jsx)(notice_outline/* default */.A, {
          size: 36
        }) : /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-task__icon",
          children: completed && /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
            icon: check/* default */.A
          })
        })
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-task-list__item-text",
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(src_text/* Text */.E, {
        as: "div",
        size: "14",
        lineHeight: completed ? '18px' : '20px',
        weight: completed ? 'normal' : '600',
        variant: completed ? 'body.small' : 'button',
        children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
          className: "woocommerce-task-list__item-title",
          children: [title, badge && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            className: "woocommerce-task-list__item-badge",
            children: badge
          })]
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(OptionalExpansionWrapper, {
          expandable: expandable,
          expanded: isTaskExpanded,
          children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
            className: "woocommerce-task-list__item-expandable-content",
            children: [content, expandable && !completed && additionalInfo && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
              className: "woocommerce-task__additional-info",
              dangerouslySetInnerHTML: {
                __html: (0,src/* sanitizeHTML */.p9)(additionalInfo, {
                  tags: ALLOWED_TAGS,
                  attr: ALLOWED_ATTR
                })
              }
            }), !completed && showActionButton && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
              className: "woocommerce-task-list__item-action",
              isPrimary: true,
              onClick: event => {
                event.stopPropagation();
                action(event, {
                  isExpanded: true
                });
              },
              children: actionLabel || title
            })]
          })
        }), !expandable && !completed && additionalInfo && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-task__additional-info",
          dangerouslySetInnerHTML: {
            __html: (0,src/* sanitizeHTML */.p9)(additionalInfo, {
              tags: ALLOWED_TAGS,
              attr: ALLOWED_ATTR
            })
          }
        }), time && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-task__estimated-time",
          children: time
        })]
      }), inProgress && inProgressLabel && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-task-list__item-progress",
        children: inProgressLabel
      })]
    }), (secondaryAction || showEllipsisMenu) && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-task-list__item-after",
      children: [secondaryAction, showEllipsisMenu && /*#__PURE__*/(0,jsx_runtime.jsx)(ellipsis_menu/* default */.A, {
        label: (0,i18n_build_module.__)('Task Options', 'woocommerce'),
        onToggle: e => e.stopPropagation(),
        renderContent: () => /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "woocommerce-task-card__section-controls",
          children: [onDismiss && !completed && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            onClick: e => {
              e.stopPropagation();
              onDismiss();
            },
            children: (0,i18n_build_module.__)('Dismiss', 'woocommerce')
          }), onSnooze && !completed && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            onClick: e => {
              e.stopPropagation();
              onSnooze();
            },
            children: (0,i18n_build_module.__)('Remind me later', 'woocommerce')
          }), onDelete && completed && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            onClick: e => {
              e.stopPropagation();
              onDelete();
            },
            children: (0,i18n_build_module.__)('Delete', 'woocommerce')
          })]
        })
      })]
    })]
  });
};
try {
    // @ts-ignore
    TaskItem.displayName = "TaskItem";
    // @ts-ignore
    TaskItem.__docgenInfo = { "description": "", "displayName": "TaskItem", "props": { "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "string" } }, "completed": { "defaultValue": null, "description": "", "name": "completed", "required": true, "type": { "name": "boolean" } }, "inProgress": { "defaultValue": null, "description": "", "name": "inProgress", "required": true, "type": { "name": "boolean" } }, "inProgressLabel": { "defaultValue": null, "description": "", "name": "inProgressLabel", "required": true, "type": { "name": "string" } }, "onClick": { "defaultValue": null, "description": "", "name": "onClick", "required": false, "type": { "name": "MouseEventHandler<HTMLElement>" } }, "onCollapse": { "defaultValue": null, "description": "", "name": "onCollapse", "required": false, "type": { "name": "(() => void)" } }, "onDelete": { "defaultValue": null, "description": "", "name": "onDelete", "required": false, "type": { "name": "(() => void)" } }, "onDismiss": { "defaultValue": null, "description": "", "name": "onDismiss", "required": false, "type": { "name": "(() => void)" } }, "onSnooze": { "defaultValue": null, "description": "", "name": "onSnooze", "required": false, "type": { "name": "(() => void)" } }, "onExpand": { "defaultValue": null, "description": "", "name": "onExpand", "required": false, "type": { "name": "(() => void)" } }, "badge": { "defaultValue": null, "description": "", "name": "badge", "required": false, "type": { "name": "string" } }, "additionalInfo": { "defaultValue": null, "description": "", "name": "additionalInfo", "required": false, "type": { "name": "string" } }, "time": { "defaultValue": null, "description": "", "name": "time", "required": false, "type": { "name": "string" } }, "content": { "defaultValue": null, "description": "", "name": "content", "required": true, "type": { "name": "string" } }, "expandable": { "defaultValue": { value: "false" }, "description": "", "name": "expandable", "required": false, "type": { "name": "boolean" } }, "expanded": { "defaultValue": { value: "false" }, "description": "", "name": "expanded", "required": false, "type": { "name": "boolean" } }, "showActionButton": { "defaultValue": null, "description": "", "name": "showActionButton", "required": false, "type": { "name": "boolean" } }, "level": { "defaultValue": { value: "3" }, "description": "", "name": "level", "required": false, "type": { "name": "enum", "value": [{ "value": "1" }, { "value": "2" }, { "value": "3" }] } }, "action": { "defaultValue": null, "description": "", "name": "action", "required": true, "type": { "name": "(event?: MouseEvent<Element, MouseEvent> | KeyboardEvent<Element> | undefined, args?: ActionArgs | undefined) => void" } }, "actionLabel": { "defaultValue": null, "description": "", "name": "actionLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "secondaryAction": { "defaultValue": null, "description": "", "name": "secondaryAction", "required": false, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/experimental/src/experimental-list/task-item/index.tsx#TaskItem"] = { docgenInfo: TaskItem.__docgenInfo, name: "TaskItem", path: "../../packages/js/experimental/src/experimental-list/task-item/index.tsx#TaskItem" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/experimental/src/experimental-list/stories/style.scss
// extracted by mini-css-extract-plugin

;// ../../packages/js/experimental/src/experimental-list/stories/experimental-list.story.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




/* harmony default export */ const experimental_list_story = ({
  title: 'Experimental/List',
  component: ExperimentalList,
  argTypes: {
    direction: {
      control: {
        type: 'select',
        options: ['up', 'down']
      }
    }
  }
});
const Template = args => /*#__PURE__*/(0,jsx_runtime.jsxs)(ExperimentalList, {
  ...args,
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
    disableGutters: true,
    onClick: () => {},
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "Without gutters no padding is added to the list item."
    })
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
    onClick: () => {},
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "Any markup can go here."
    })
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
    onClick: () => {},
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "Any markup can go here."
    })
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
    onClick: () => {},
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "Any markup can go here."
    })
  })]
});
const Primary = Template.bind({
  onClick: () => {}
});
Primary.args = {
  listType: 'ul',
  animation: 'slide-right'
};
const CollapsibleListExample = args => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(ExperimentalCollapsibleList, {
    collapseLabel: "Show less",
    expandLabel: "Show more items",
    show: 2,
    onCollapse: () => {
      // eslint-disable-next-line no-console
      console.log('collapsed');
    },
    onExpand: () => {
      // eslint-disable-next-line no-console
      console.log('expanded');
    },
    direction: "up",
    ...args,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
      onClick: () => {},
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        children: "Any markup can go here."
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
      onClick: () => {},
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        children: "Any markup can go here."
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
      onClick: () => {},
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        children: ["Any markup can go here.", /*#__PURE__*/(0,jsx_runtime.jsx)("br", {}), "Bigger task item", /*#__PURE__*/(0,jsx_runtime.jsx)("br", {}), "Another line"]
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
      onClick: () => {},
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        children: "Any markup can go here."
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ExperimentalListItem, {
      onClick: () => {},
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        children: "Any markup can go here."
      })
    })]
  });
};
CollapsibleListExample.storyName = 'List with CollapsibleListItem.';
const TaskItemExample = args => /*#__PURE__*/(0,jsx_runtime.jsxs)(ExperimentalList, {
  ...args,
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)(TaskItem, {
    action: () =>
    // eslint-disable-next-line no-console
    console.log('Primary action clicked'),
    actionLabel: "Primary action",
    completed: false,
    content: "Task content",
    expandable: true,
    expanded: true,
    level: 1,
    onClick: () =>
    // eslint-disable-next-line no-console
    console.log('Task clicked'),
    onCollapse: () =>
    // eslint-disable-next-line no-console
    console.log('Task will be expanded'),
    onExpand: () =>
    // eslint-disable-next-line no-console
    console.log('Task will be collapsed'),
    showActionButton: true,
    title: "A high-priority task"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(TaskItem, {
    action: () =>
    // eslint-disable-next-line no-console
    console.log('Primary action clicked'),
    actionLabel: "Primary action",
    completed: false,
    content: "Task content",
    expandable: false,
    expanded: true,
    level: 1,
    onClick: () =>
    // eslint-disable-next-line no-console
    console.log('Task clicked'),
    showActionButton: false,
    title: "A high-priority task without `Primary action`",
    badge: "Badge content"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(TaskItem, {
    action: () => {},
    completed: false,
    content: "Task content",
    expandable: false,
    expanded: true,
    level: 2,
    onClick: () =>
    // eslint-disable-next-line no-console
    console.log('Task clicked'),
    title: "Setup task",
    onDismiss: () =>
    // eslint-disable-next-line no-console
    console.log('Task dismissed'),
    onSnooze: () =>
    // eslint-disable-next-line no-console
    console.log('Task snoozed'),
    time: "5 minutes"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(TaskItem, {
    action: () => {},
    completed: false,
    content: "Task content",
    expandable: false,
    expanded: true,
    level: 3,
    onClick: () =>
    // eslint-disable-next-line no-console
    console.log('Task clicked'),
    title: "A low-priority task",
    onDismiss: () =>
    // eslint-disable-next-line no-console
    console.log('Task dismissed'),
    onSnooze: () =>
    // eslint-disable-next-line no-console
    console.log('Task snoozed'),
    time: "3 minutes"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(TaskItem, {
    action: () => {},
    completed: true,
    content: "Task content",
    expandable: false,
    expanded: true,
    level: 3,
    onClick: () =>
    // eslint-disable-next-line no-console
    console.log('Task clicked'),
    title: "Another low-priority task",
    onDelete: () =>
    // eslint-disable-next-line no-console
    console.log('Task deleted')
  })]
});
TaskItemExample.storyName = 'TaskItems.';
Primary.parameters = {
  ...Primary.parameters,
  docs: {
    ...Primary.parameters?.docs,
    source: {
      originalSource: "Template.bind({\n  onClick: () => {}\n})",
      ...Primary.parameters?.docs?.source
    }
  }
};
CollapsibleListExample.parameters = {
  ...CollapsibleListExample.parameters,
  docs: {
    ...CollapsibleListExample.parameters?.docs,
    source: {
      originalSource: "args => {\n  return <CollapsibleList collapseLabel=\"Show less\" expandLabel=\"Show more items\" show={2} onCollapse={() => {\n    // eslint-disable-next-line no-console\n    console.log('collapsed');\n  }} onExpand={() => {\n    // eslint-disable-next-line no-console\n    console.log('expanded');\n  }} direction=\"up\" {...args}>\n            <ListItem onClick={() => {}}>\n                <div>Any markup can go here.</div>\n            </ListItem>\n            <ListItem onClick={() => {}}>\n                <div>Any markup can go here.</div>\n            </ListItem>\n            <ListItem onClick={() => {}}>\n                <div>\n                    Any markup can go here.\n                    <br />\n                    Bigger task item\n                    <br />\n                    Another line\n                </div>\n            </ListItem>\n            <ListItem onClick={() => {}}>\n                <div>Any markup can go here.</div>\n            </ListItem>\n            <ListItem onClick={() => {}}>\n                <div>Any markup can go here.</div>\n            </ListItem>\n        </CollapsibleList>;\n}",
      ...CollapsibleListExample.parameters?.docs?.source
    }
  }
};
TaskItemExample.parameters = {
  ...TaskItemExample.parameters,
  docs: {
    ...TaskItemExample.parameters?.docs,
    source: {
      originalSource: "args => <List {...args}>\n        <TaskItem action={() =>\n  // eslint-disable-next-line no-console\n  console.log('Primary action clicked')} actionLabel=\"Primary action\" completed={false} content=\"Task content\" expandable={true} expanded={true} level={1} onClick={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task clicked')} onCollapse={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task will be expanded')} onExpand={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task will be collapsed')} showActionButton={true} title=\"A high-priority task\" />\n        <TaskItem action={() =>\n  // eslint-disable-next-line no-console\n  console.log('Primary action clicked')} actionLabel=\"Primary action\" completed={false} content=\"Task content\" expandable={false} expanded={true} level={1} onClick={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task clicked')} showActionButton={false} title=\"A high-priority task without `Primary action`\" badge=\"Badge content\" />\n        <TaskItem action={() => {}} completed={false} content=\"Task content\" expandable={false} expanded={true} level={2} onClick={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task clicked')} title=\"Setup task\" onDismiss={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task dismissed')} onSnooze={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task snoozed')} time=\"5 minutes\" />\n        <TaskItem action={() => {}} completed={false} content=\"Task content\" expandable={false} expanded={true} level={3} onClick={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task clicked')} title=\"A low-priority task\" onDismiss={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task dismissed')} onSnooze={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task snoozed')} time=\"3 minutes\" />\n        <TaskItem action={() => {}} completed={true} content=\"Task content\" expandable={false} expanded={true} level={3} onClick={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task clicked')} title=\"Another low-priority task\" onDelete={() =>\n  // eslint-disable-next-line no-console\n  console.log('Task deleted')} />\n    </List>",
      ...TaskItemExample.parameters?.docs?.source
    }
  }
};

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

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ menu_default)
});

// UNUSED EXPORTS: NavigableMenu, UnforwardedNavigableMenu

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dom@4.50.0/node_modules/@wordpress/dom/build-module/index.mjs + 2 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@4.50.0/node_modules/@wordpress/dom/build-module/index.mjs");
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
    const finder = onlyBrowserTabstops ? build_module/* focus */.XC.tabbable : build_module/* focus */.XC.focusable;
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

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   l: () => (/* binding */ COLORS)
/* harmony export */ });
/* unused harmony export default */
const white = "#fff";
const GRAY = {
  900: "#1e1e1e",
  800: "#2f2f2f",
  /** Meets 4.6:1 text contrast against white. */
  700: "#757575",
  /** Meets 3:1 UI or large text contrast against white. */
  600: "#949494",
  400: "#ccc",
  /** Used for most borders. */
  300: "#ddd",
  /** Used sparingly for light borders. */
  200: "#e0e0e0",
  /** Used for light gray backgrounds. */
  100: "#f0f0f0"
};
const ALERT = {
  yellow: "#f0b849",
  red: "#d94f4f",
  green: "#4ab866"
};
const THEME = {
  accent: `var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9))`,
  accentDarker10: `var(--wp-components-color-accent-darker-10, var(--wp-admin-theme-color-darker-10, #2145e6))`,
  accentDarker20: `var(--wp-components-color-accent-darker-20, var(--wp-admin-theme-color-darker-20, #183ad6))`,
  /** Used when placing text on the accent color. */
  accentInverted: `var(--wp-components-color-accent-inverted, ${white})`,
  background: `var(--wp-components-color-background, ${white})`,
  foreground: `var(--wp-components-color-foreground, ${GRAY[900]})`,
  /** Used when placing text on the foreground color. */
  foregroundInverted: `var(--wp-components-color-foreground-inverted, ${white})`,
  gray: {
    /** @deprecated Use `COLORS.theme.foreground` instead. */
    900: `var(--wp-components-color-foreground, ${GRAY[900]})`,
    800: `var(--wp-components-color-gray-800, ${GRAY[800]})`,
    700: `var(--wp-components-color-gray-700, ${GRAY[700]})`,
    600: `var(--wp-components-color-gray-600, ${GRAY[600]})`,
    400: `var(--wp-components-color-gray-400, ${GRAY[400]})`,
    300: `var(--wp-components-color-gray-300, ${GRAY[300]})`,
    200: `var(--wp-components-color-gray-200, ${GRAY[200]})`,
    100: `var(--wp-components-color-gray-100, ${GRAY[100]})`
  }
};
const UI = {
  background: THEME.background,
  backgroundDisabled: THEME.gray[100],
  border: THEME.gray[600],
  borderHover: THEME.gray[700],
  borderFocus: THEME.accent,
  borderDisabled: THEME.gray[400],
  textDisabled: THEME.gray[600],
  // Matches @wordpress/base-styles
  darkGrayPlaceholder: `color-mix(in srgb, ${THEME.foreground}, transparent 38%)`,
  lightGrayPlaceholder: `color-mix(in srgb, ${THEME.background}, transparent 35%)`
};
const COLORS = Object.freeze({
  /**
   * The main gray color object.
   *
   * @deprecated Use semantic aliases in `COLORS.ui` or theme-ready variables in `COLORS.theme.gray`.
   */
  gray: GRAY,
  // TODO: Stop exporting this when everything is migrated to `theme` or `ui`
  /**
   * @deprecated Prefer theme-ready variables in `COLORS.theme`.
   */
  white,
  alert: ALERT,
  /**
   * Theme-ready variables with fallbacks.
   *
   * Prefer semantic aliases in `COLORS.ui` when applicable.
   */
  theme: THEME,
  /**
   * Semantic aliases (prefer these over raw variables when applicable).
   */
  ui: UI
});
var colors_values_default = (/* unused pure expression or super */ null && (COLORS));

//# sourceMappingURL=colors-values.js.map


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

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GB: () => (/* binding */ ensureNumber),
/* harmony export */   J5: () => (/* binding */ isValueDefined),
/* harmony export */   r6: () => (/* binding */ isValueEmpty)
/* harmony export */ });
/* unused harmony exports getDefinedValue, stringToNumber */
function isValueDefined(value) {
  return value !== void 0 && value !== null;
}
function isValueEmpty(value) {
  const isEmptyString = value === "";
  return !isValueDefined(value) || isEmptyString;
}
function getDefinedValue(values = [], fallbackValue) {
  var _values$find;
  return (_values$find = values.find(isValueDefined)) !== null && _values$find !== void 0 ? _values$find : fallbackValue;
}
const stringToNumber = (value) => {
  return parseFloat(value);
};
const ensureNumber = (value) => {
  return typeof value === "string" ? stringToNumber(value) : value;
};

//# sourceMappingURL=values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Fm: () => (/* binding */ ENTER),
/* harmony export */   G_: () => (/* binding */ BACKSPACE),
/* harmony export */   M3: () => (/* binding */ LEFT),
/* harmony export */   NS: () => (/* binding */ RIGHT),
/* harmony export */   PX: () => (/* binding */ DOWN),
/* harmony export */   UP: () => (/* binding */ UP),
/* harmony export */   _f: () => (/* binding */ ESCAPE),
/* harmony export */   t6: () => (/* binding */ SPACE),
/* harmony export */   wn: () => (/* binding */ TAB)
/* harmony export */ });
/* unused harmony exports ALT, COMMAND, CTRL, DELETE, END, F10, HOME, PAGEDOWN, PAGEUP, SHIFT, ZERO, displayShortcut, displayShortcutList, isKeyboardEvent, modifiers, rawShortcut, shortcutAriaLabel */
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs");


const BACKSPACE = 8;
const TAB = 9;
const ENTER = 13;
const ESCAPE = 27;
const SPACE = 32;
const PAGEUP = 33;
const PAGEDOWN = 34;
const END = 35;
const HOME = 36;
const LEFT = 37;
const UP = 38;
const RIGHT = 39;
const DOWN = 40;
const DELETE = 46;
const F10 = 121;
const ALT = "alt";
const CTRL = "ctrl";
const COMMAND = "meta";
const SHIFT = "shift";
const ZERO = 48;
function capitaliseFirstCharacter(string) {
  return string.length < 2 ? string.toUpperCase() : string.charAt(0).toUpperCase() + string.slice(1);
}
function mapValues(object, mapFn) {
  return Object.fromEntries(
    Object.entries(object).map(([key, value]) => [
      key,
      mapFn(value)
    ])
  );
}
const modifiers = {
  primary: (_isApple) => _isApple() ? [COMMAND] : [CTRL],
  primaryShift: (_isApple) => _isApple() ? [SHIFT, COMMAND] : [CTRL, SHIFT],
  primaryAlt: (_isApple) => _isApple() ? [ALT, COMMAND] : [CTRL, ALT],
  secondary: (_isApple) => _isApple() ? [SHIFT, ALT, COMMAND] : [CTRL, SHIFT, ALT],
  access: (_isApple) => _isApple() ? [CTRL, ALT] : [SHIFT, ALT],
  ctrl: () => [CTRL],
  alt: () => [ALT],
  ctrlShift: () => [CTRL, SHIFT],
  shift: () => [SHIFT],
  shiftAlt: () => [SHIFT, ALT],
  undefined: () => []
};
const rawShortcut = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(modifiers, (modifier) => {
  return (character, _isApple = isAppleOS) => {
    return [...modifier(_isApple), character.toLowerCase()].join(
      "+"
    );
  };
})));
const displayShortcutList = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(
  modifiers,
  (modifier) => {
    return (character, _isApple = isAppleOS) => {
      const isApple = _isApple();
      const replacementKeyMap = {
        [ALT]: isApple ? "\u2325" : "Alt",
        [CTRL]: isApple ? "\u2303" : "Ctrl",
        // Make sure ⌃ is the U+2303 UP ARROWHEAD unicode character and not the caret character.
        [COMMAND]: "\u2318",
        [SHIFT]: isApple ? "\u21E7" : "Shift"
      };
      const modifierKeys = modifier(_isApple).reduce(
        (accumulator, key) => {
          const replacementKey = replacementKeyMap[key] ?? key;
          if (isApple) {
            return [...accumulator, replacementKey];
          }
          return [...accumulator, replacementKey, "+"];
        },
        []
      );
      return [
        ...modifierKeys,
        capitaliseFirstCharacter(character)
      ];
    };
  }
)));
const displayShortcut = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(
  displayShortcutList,
  (shortcutList) => {
    return (character, _isApple = isAppleOS) => shortcutList(character, _isApple).join("");
  }
)));
const shortcutAriaLabel = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(modifiers, (modifier) => {
  return (character, _isApple = isAppleOS) => {
    const isApple = _isApple();
    const replacementKeyMap = {
      [SHIFT]: "Shift",
      [COMMAND]: isApple ? "Command" : "Control",
      [CTRL]: "Control",
      [ALT]: isApple ? "Option" : "Alt",
      /* translators: comma as in the character ',' */
      ",": __("Comma"),
      /* translators: period as in the character '.' */
      ".": __("Period"),
      /* translators: backtick as in the character '`' */
      "`": __("Backtick"),
      /* translators: tilde as in the character '~' */
      "~": __("Tilde")
    };
    return [...modifier(_isApple), character].map(
      (key) => capitaliseFirstCharacter(replacementKeyMap[key] ?? key)
    ).join(isApple ? " " : " + ");
  };
})));
function getEventModifiers(event) {
  return [ALT, CTRL, COMMAND, SHIFT].filter(
    (key) => event[`${key}Key`]
  );
}
const isKeyboardEvent = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(modifiers, (getModifiers) => {
  return (event, character, _isApple = isAppleOS) => {
    const mods = getModifiers(_isApple);
    const eventMods = getEventModifiers(event);
    const replacementWithShiftKeyMap = {
      Comma: ",",
      Backslash: "\\",
      // Windows returns `\` for both IntlRo and IntlYen.
      IntlRo: "\\",
      IntlYen: "\\"
    };
    const modsDiff = mods.filter(
      (mod) => !eventMods.includes(mod)
    );
    const eventModsDiff = eventMods.filter(
      (mod) => !mods.includes(mod)
    );
    if (modsDiff.length > 0 || eventModsDiff.length > 0) {
      return false;
    }
    let key = event.key.toLowerCase();
    if (!character) {
      return mods.includes(key);
    }
    if (event.altKey && character.length === 1) {
      key = String.fromCharCode(event.keyCode).toLowerCase();
    }
    if (event.shiftKey && character.length === 1 && replacementWithShiftKeyMap[event.code]) {
      key = replacementWithShiftKeyMap[event.code];
    }
    if (character === "del") {
      character = "delete";
    }
    return key === character.toLowerCase();
  };
})));

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../packages/js/components/src/ellipsis-menu/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var gridicons_dist_ellipsis__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/ellipsis.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */






/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */

const EllipsisMenu = ({
  label,
  renderContent,
  className,
  onToggle,
  // if set bottom-start, it will fallback to bottom-end / top-end / top-start
  // if it's bottom, it will fallback to only top
  placement = 'bottom-start',
  focusOnMount = 'firstElement'
}) => {
  if (!renderContent) {
    return null;
  }
  const renderEllipsis = ({
    onToggle: toggleHandlerOverride,
    isOpen
  }) => {
    const toggleClassname = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-ellipsis-menu__toggle', {
      'is-opened': isOpen
    });
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
      className: toggleClassname,
      onClick: e => {
        if (onToggle) {
          onToggle(e);
        }
        if (toggleHandlerOverride) {
          toggleHandlerOverride();
        }
      },
      title: label,
      "aria-expanded": isOpen,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
        icon: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(gridicons_dist_ellipsis__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A, {})
      })
    });
  };
  const handleMenuKeyDown = event => {
    // Prevent page scroll when navigating menu with arrow keys.
    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
      event.preventDefault();
    }
  };
  const renderMenu = renderContentArgs => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Ay, {
    className: "woocommerce-ellipsis-menu__content",
    onKeyDown: handleMenuKeyDown,
    children: renderContent(renderContentArgs)
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, 'woocommerce-ellipsis-menu'),
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
      contentClassName: "woocommerce-ellipsis-menu__popover",
      popoverProps: {
        placement,
        focusOnMount
      },
      renderToggle: renderEllipsis,
      renderContent: renderMenu
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EllipsisMenu);
try {
    // @ts-ignore
    ellipsismenu.displayName = "ellipsismenu";
    // @ts-ignore
    ellipsismenu.__docgenInfo = { "description": "This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.", "displayName": "ellipsismenu", "props": { "label": { "defaultValue": null, "description": "The label shown when hovering/focusing on the icon button.", "name": "label", "required": true, "type": { "name": "string" } }, "renderContent": { "defaultValue": null, "description": "A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments.", "name": "renderContent", "required": false, "type": { "name": "((props: CallbackProps) => Element | ReactNode)" } }, "className": { "defaultValue": null, "description": "Classname to add to ellipsis menu.", "name": "className", "required": false, "type": { "name": "string" } }, "onToggle": { "defaultValue": null, "description": "Callback function when dropdown button is clicked, it provides the click event.", "name": "onToggle", "required": false, "type": { "name": "((e: MouseEvent<Element, MouseEvent> | KeyboardEvent<Element>) => void)" } }, "placement": { "defaultValue": { value: "bottom-start" }, "description": "Placement of the dropdown menu. Default is 'bottom-start'.", "name": "placement", "required": false, "type": { "name": "any" } }, "focusOnMount": { "defaultValue": { value: "firstElement" }, "description": "By default, the first menu item will receive focus. This is the same as setting this prop to \"firstElement\".\nSpecifying a true value will focus the container instead.\nSpecifying a false value disables the focus handling entirely\n(this should only be done when an appropriately accessible\nsubstitute behavior exists).", "name": "focusOnMount", "required": false, "type": { "name": "boolean | \"firstElement\"" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/ellipsis-menu/index.tsx#ellipsismenu"] = { docgenInfo: ellipsismenu.__docgenInfo, name: "ellipsismenu", path: "../../packages/js/components/src/ellipsis-menu/index.tsx#ellipsismenu" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/experimental/src/text.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */
 // eslint-disable-line @wordpress/no-unsafe-wp-apis

// Preserve permissive prop types of the original JS barrel shim.
const Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A; // eslint-disable-line @typescript-eslint/no-explicit-any

/***/ }),

/***/ "../../packages/js/experimental/src/vertical-css-transition/vertical-css-transition.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H: () => (/* binding */ VerticalCSSTransition)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



function getContainerHeight(container) {
  let containerHeight = 0;
  for (const child of container.children) {
    containerHeight += child.clientHeight;
    const style = window.getComputedStyle(child);
    containerHeight += parseInt(style.marginTop, 10) || 0;
    containerHeight += parseInt(style.marginBottom, 10) || 0;
  }
  return containerHeight;
}

/**
 * VerticalCSSTransition is a wrapper for CSSTransition, automatically adding a vertical height transition.
 * The maxHeight is calculated through JS, something CSS does not support.
 */
const VerticalCSSTransition = ({
  children,
  defaultStyle,
  ...props
}) => {
  const [containerHeight, setContainerHeight] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(0);
  const [transitionIn, setTransitionIn] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(props.in || false);
  const cssTransitionRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const collapseContainerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)(containerElement => {
    if (containerElement) {
      setContainerHeight(getContainerHeight(containerElement));
    }
  }, [children]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setTransitionIn(props.in || false);
  }, [props.in]);
  const getTimeouts = () => {
    const {
      timeout
    } = props;
    let exit, enter, appear;
    if (typeof timeout === 'number') {
      exit = enter = appear = timeout;
    }
    if (timeout !== undefined && typeof timeout !== 'number') {
      exit = timeout.exit;
      enter = timeout.enter;
      appear = timeout.appear !== undefined ? timeout.appear : enter;
    }
    return {
      exit,
      enter,
      appear
    };
  };
  const transitionStyles = {
    entered: {
      maxHeight: containerHeight
    },
    entering: {
      maxHeight: containerHeight
    },
    exiting: {
      maxHeight: 0
    },
    exited: {
      maxHeight: 0
    }
  };
  const getTransitionStyle = state => {
    const timeouts = getTimeouts();
    const appearing = cssTransitionRef.current && cssTransitionRef.current.context && cssTransitionRef.current.context.isMounting;
    let duration;
    if (state.startsWith('enter')) {
      duration = timeouts[appearing ? 'enter' : 'appear'];
    } else {
      duration = timeouts.exit;
    }
    const styles = {
      transitionProperty: 'max-height',
      transitionDuration: duration === undefined ? '500ms' : duration + 'ms',
      overflow: 'hidden',
      ...(defaultStyle || {}),
      ...(state in transitionStyles ? transitionStyles[state] : {})
    };
    // only include transition styles when entering or exiting.
    if (state !== 'entering' && state !== 'exiting') {
      delete styles.transitionDuration;
      delete styles.transition;
      delete styles.transitionProperty;
    }
    // Remove maxHeight when entered, so we do not need to worry about nested items changing height while expanded.
    if (state === 'entered' && props.in) {
      delete styles.maxHeight;
    }
    return styles;
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_transition_group__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    ...props,
    in: transitionIn,
    ref: cssTransitionRef,
    children: state => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: "vertical-css-transition-container",
      style: getTransitionStyle(state),
      ref: collapseContainerRef,
      children: children
    })
  });
};
try {
    // @ts-ignore
    VerticalCSSTransition.displayName = "VerticalCSSTransition";
    // @ts-ignore
    VerticalCSSTransition.__docgenInfo = { "description": "VerticalCSSTransition is a wrapper for CSSTransition, automatically adding a vertical height transition.\nThe maxHeight is calculated through JS, something CSS does not support.", "displayName": "VerticalCSSTransition", "props": { "classNames": { "defaultValue": null, "description": "The animation `classNames` applied to the component as it enters or exits.\nA single name can be provided and it will be suffixed for each stage: e.g.\n\n`classNames=\"fade\"` applies `fade-enter`, `fade-enter-active`,\n`fade-exit`, `fade-exit-active`, `fade-appear`, and `fade-appear-active`.\n\nEach individual classNames can also be specified independently like:\n\n```js\nclassNames={{\n  appear: 'my-appear',\n  appearActive: 'my-appear-active',\n  appearDone: 'my-appear-done',\n  enter: 'my-enter',\n  enterActive: 'my-enter-active',\n  enterDone: 'my-enter-done',\n  exit: 'my-exit',\n  exitActive: 'my-exit-active',\n  exitDone: 'my-exit-done'\n}}\n```", "name": "classNames", "required": false, "type": { "name": "string | CSSTransitionClassNames" } }, "defaultStyle": { "defaultValue": null, "description": "", "name": "defaultStyle", "required": false, "type": { "name": "CSSProperties" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/experimental/src/vertical-css-transition/vertical-css-transition.tsx#VerticalCSSTransition"] = { docgenInfo: VerticalCSSTransition.__docgenInfo, name: "VerticalCSSTransition", path: "../../packages/js/experimental/src/vertical-css-transition/vertical-css-transition.tsx#VerticalCSSTransition" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/sanitize/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  p9: () => (/* reexport */ sanitize_sanitizeHTML)
});

// UNUSED EXPORTS: DEFAULT_ALLOWED_ATTR, DEFAULT_ALLOWED_TAGS, getTrustedTypesPolicy

// EXTERNAL MODULE: ../../node_modules/.pnpm/dompurify@3.4.14/node_modules/dompurify/dist/purify.es.mjs
var purify_es = __webpack_require__("../../node_modules/.pnpm/dompurify@3.4.14/node_modules/dompurify/dist/purify.es.mjs");
;// ../../packages/js/sanitize/src/noop-trusted-types-policy.ts
/**
 * External dependencies
 */

/**
 * The type for our no-op trusted types policy.
 */

/**
 * Cached no-op policy instance to avoid duplicate creation.
 */
let noopPolicyInstance;
function getNoopTrustedTypesPolicy() {
  if (noopPolicyInstance !== undefined) {
    return noopPolicyInstance;
  }
  if (typeof window === 'undefined' || !window.trustedTypes) {
    noopPolicyInstance = null;
    return null;
  }
  try {
    noopPolicyInstance = window.trustedTypes.createPolicy('woocommerce-sanitize-noop', {
      createHTML: input => input
    });
  } catch (error) {
    noopPolicyInstance = null;
    // eslint-disable-next-line no-console
    console.warn('Failed to create "woocommerce-sanitize-noop" trusted type policy:', error);
  }
  return noopPolicyInstance;
}
;// ../../packages/js/sanitize/src/sanitize.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Default allowed HTML tags for basic sanitization.
 */
const DEFAULT_ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p', 'br', 'abbr'];

/**
 * Default allowed HTML attributes for basic sanitization.
 */
const DEFAULT_ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download', 'title'];

/**
 * The set of supported return type kinds for sanitized content.
 * These are the configuration values you can pass via `returnType`.
 */

/**
 * Mapping between `SanitizeReturnKind` and the actual returned value types.
 */

/**
 * Union of the concrete value types this sanitizer can return.
 * Useful when you want to accept any possible sanitizer output.
 */

/**
 * Configuration options for HTML sanitization.
 */

/**
 * Sanitizes HTML content using DOMPurify with default allowed tags and attributes.
 *
 * @param html   - The HTML content to sanitize.
 * @param config - Optional configuration for allowed tags and attributes.
 *
 * @return Sanitized HTML content.
 */
function sanitize_sanitizeHTML(html, config) {
  const allowedTags = config?.tags || DEFAULT_ALLOWED_TAGS;
  const allowedAttr = config?.attr || DEFAULT_ALLOWED_ATTR;
  const purifyConfig = {
    ALLOWED_TAGS: [...allowedTags],
    ALLOWED_ATTR: [...allowedAttr]
  };

  // Provide a no-op TT policy (when supported) to prevent DOMPurify from
  // creating its internal policy and emitting warnings with multiple instances.
  const ttNoopPolicy = getNoopTrustedTypesPolicy();
  if (ttNoopPolicy) {
    purifyConfig.TRUSTED_TYPES_POLICY = ttNoopPolicy;
  }

  // Only pass a single RETURN_* flag if a non-string return type is requested
  if (config?.returnType === 'HTMLBodyElement') {
    purifyConfig.RETURN_DOM = true;
  } else if (config?.returnType === 'DocumentFragment') {
    purifyConfig.RETURN_DOM_FRAGMENT = true;
  }
  return purify_es/* default */.A.sanitize(html ?? '', purifyConfig);
}
;// ../../packages/js/sanitize/src/trusted-types-policy.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * The type for our trusted types policy.
 */

/**
 * Cached policy instance to ensure it's only created once.
 */
let policyInstance;

/**
 * Get or create a trusted types policy for DOMPurify.
 *
 * @return TrustedTypePolicy object or null if not supported.
 */
function getTrustedTypesPolicy() {
  if (policyInstance !== undefined) {
    return policyInstance;
  }
  if (typeof window === 'undefined' || !window.trustedTypes) {
    policyInstance = null;
    return null;
  }
  try {
    policyInstance = window.trustedTypes.createPolicy('woocommerce-sanitize', {
      createHTML: input => sanitizeHTML(input)
    });
  } catch (error) {
    policyInstance = null;
    // eslint-disable-next-line no-console
    console.warn('Failed to create "woocommerce-sanitize" trusted type policy:', error);
  }
  return policyInstance;
}
;// ../../packages/js/sanitize/src/index.ts



/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/ellipsis.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-ellipsis",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M7 12a2 2 0 11-4.001-.001A2 2 0 017 12zm12-2a2 2 0 10.001 4.001A2 2 0 0019 10zm-7 0a2 2 0 10.001 4.001A2 2 0 0012 10z"})))}


/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/notice-outline.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-notice-outline",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 4c4.411 0 8 3.589 8 8s-3.589 8-8 8-8-3.589-8-8 3.589-8 8-8m0-2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 13h-2v2h2v-2zm-2-2h2l.5-6h-3l.5 6z"})))}


/***/ }),

/***/ "../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ esm_TransitionGroup)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js + 1 modules
var inheritsLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js");
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/utils/ChildMapping.js

/**
 * Given `this.props.children`, return an object mapping key to child.
 *
 * @param {*} children `this.props.children`
 * @return {object} Mapping of key to child
 */

function getChildMapping(children, mapFn) {
  var mapper = function mapper(child) {
    return mapFn && (0,react.isValidElement)(child) ? mapFn(child) : child;
  };

  var result = Object.create(null);
  if (children) react.Children.map(children, function (c) {
    return c;
  }).forEach(function (child) {
    // run the map function here instead so that the key is the computed one
    result[child.key] = mapper(child);
  });
  return result;
}
/**
 * When you're adding or removing children some may be added or removed in the
 * same render pass. We want to show *both* since we want to simultaneously
 * animate elements in and out. This function takes a previous set of keys
 * and a new set of keys and merges them with its best guess of the correct
 * ordering. In the future we may expose some of the utilities in
 * ReactMultiChild to make this easy, but for now React itself does not
 * directly have this concept of the union of prevChildren and nextChildren
 * so we implement it here.
 *
 * @param {object} prev prev children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @param {object} next next children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @return {object} a key set that contains all keys in `prev` and all keys
 * in `next` in a reasonable order.
 */

function mergeChildMappings(prev, next) {
  prev = prev || {};
  next = next || {};

  function getValueForKey(key) {
    return key in next ? next[key] : prev[key];
  } // For each key of `next`, the list of keys to insert before that key in
  // the combined list


  var nextKeysPending = Object.create(null);
  var pendingKeys = [];

  for (var prevKey in prev) {
    if (prevKey in next) {
      if (pendingKeys.length) {
        nextKeysPending[prevKey] = pendingKeys;
        pendingKeys = [];
      }
    } else {
      pendingKeys.push(prevKey);
    }
  }

  var i;
  var childMapping = {};

  for (var nextKey in next) {
    if (nextKeysPending[nextKey]) {
      for (i = 0; i < nextKeysPending[nextKey].length; i++) {
        var pendingNextKey = nextKeysPending[nextKey][i];
        childMapping[nextKeysPending[nextKey][i]] = getValueForKey(pendingNextKey);
      }
    }

    childMapping[nextKey] = getValueForKey(nextKey);
  } // Finally, add the keys which didn't appear before any key in `next`


  for (i = 0; i < pendingKeys.length; i++) {
    childMapping[pendingKeys[i]] = getValueForKey(pendingKeys[i]);
  }

  return childMapping;
}

function getProp(child, prop, props) {
  return props[prop] != null ? props[prop] : child.props[prop];
}

function getInitialChildMapping(props, onExited) {
  return getChildMapping(props.children, function (child) {
    return (0,react.cloneElement)(child, {
      onExited: onExited.bind(null, child),
      in: true,
      appear: getProp(child, 'appear', props),
      enter: getProp(child, 'enter', props),
      exit: getProp(child, 'exit', props)
    });
  });
}
function getNextChildMapping(nextProps, prevChildMapping, onExited) {
  var nextChildMapping = getChildMapping(nextProps.children);
  var children = mergeChildMappings(prevChildMapping, nextChildMapping);
  Object.keys(children).forEach(function (key) {
    var child = children[key];
    if (!(0,react.isValidElement)(child)) return;
    var hasPrev = (key in prevChildMapping);
    var hasNext = (key in nextChildMapping);
    var prevChild = prevChildMapping[key];
    var isLeaving = (0,react.isValidElement)(prevChild) && !prevChild.props.in; // item is new (entering)

    if (hasNext && (!hasPrev || isLeaving)) {
      // console.log('entering', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: true,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    } else if (!hasNext && hasPrev && !isLeaving) {
      // item is old (exiting)
      // console.log('leaving', key)
      children[key] = (0,react.cloneElement)(child, {
        in: false
      });
    } else if (hasNext && hasPrev && (0,react.isValidElement)(prevChild)) {
      // item hasn't changed transition states
      // copy over the last transition props;
      // console.log('unchanged', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: prevChild.props.in,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    }
  });
  return children;
}
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js









var values = Object.values || function (obj) {
  return Object.keys(obj).map(function (k) {
    return obj[k];
  });
};

var defaultProps = {
  component: 'div',
  childFactory: function childFactory(child) {
    return child;
  }
};
/**
 * The `<TransitionGroup>` component manages a set of transition components
 * (`<Transition>` and `<CSSTransition>`) in a list. Like with the transition
 * components, `<TransitionGroup>` is a state machine for managing the mounting
 * and unmounting of components over time.
 *
 * Consider the example below. As items are removed or added to the TodoList the
 * `in` prop is toggled automatically by the `<TransitionGroup>`.
 *
 * Note that `<TransitionGroup>`  does not define any animation behavior!
 * Exactly _how_ a list item animates is up to the individual transition
 * component. This means you can mix and match animations across different list
 * items.
 */

var TransitionGroup = /*#__PURE__*/function (_React$Component) {
  (0,inheritsLoose/* default */.A)(TransitionGroup, _React$Component);

  function TransitionGroup(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;

    var handleExited = _this.handleExited.bind((0,assertThisInitialized/* default */.A)(_this)); // Initial children should all be entering, dependent on appear


    _this.state = {
      contextValue: {
        isMounting: true
      },
      handleExited: handleExited,
      firstRender: true
    };
    return _this;
  }

  var _proto = TransitionGroup.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this.mounted = true;
    this.setState({
      contextValue: {
        isMounting: false
      }
    });
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    this.mounted = false;
  };

  TransitionGroup.getDerivedStateFromProps = function getDerivedStateFromProps(nextProps, _ref) {
    var prevChildMapping = _ref.children,
        handleExited = _ref.handleExited,
        firstRender = _ref.firstRender;
    return {
      children: firstRender ? getInitialChildMapping(nextProps, handleExited) : getNextChildMapping(nextProps, prevChildMapping, handleExited),
      firstRender: false
    };
  } // node is `undefined` when user provided `nodeRef` prop
  ;

  _proto.handleExited = function handleExited(child, node) {
    var currentChildMapping = getChildMapping(this.props.children);
    if (child.key in currentChildMapping) return;

    if (child.props.onExited) {
      child.props.onExited(node);
    }

    if (this.mounted) {
      this.setState(function (state) {
        var children = (0,esm_extends/* default */.A)({}, state.children);

        delete children[child.key];
        return {
          children: children
        };
      });
    }
  };

  _proto.render = function render() {
    var _this$props = this.props,
        Component = _this$props.component,
        childFactory = _this$props.childFactory,
        props = (0,objectWithoutPropertiesLoose/* default */.A)(_this$props, ["component", "childFactory"]);

    var contextValue = this.state.contextValue;
    var children = values(this.state.children).map(childFactory);
    delete props.appear;
    delete props.enter;
    delete props.exit;

    if (Component === null) {
      return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
        value: contextValue
      }, children);
    }

    return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
      value: contextValue
    }, /*#__PURE__*/react.createElement(Component, props, children));
  };

  return TransitionGroup;
}(react.Component);

TransitionGroup.propTypes =  false ? 0 : {};
TransitionGroup.defaultProps = defaultProps;
/* harmony default export */ const esm_TransitionGroup = (TransitionGroup);

/***/ })

}]);