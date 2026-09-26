"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3585],{

/***/ "../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  Columns: () => (/* binding */ Columns),
  Cover: () => (/* binding */ Cover),
  "default": () => (/* binding */ image_gallery_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/index.js + 28 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/utils.ts
var utils = __webpack_require__("../../packages/js/components/src/sortable/utils.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/image-gallery/image-gallery-wrapper.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const ImageGalleryWrapper = ({
  children,
  allowDragging = true,
  onDragStart = () => null,
  onDragEnd = () => null,
  onDragOver = () => null,
  updateOrderedChildren = () => null
}) => {
  if (allowDragging) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(sortable/* Sortable */.L, {
      isHorizontal: true,
      onOrderChange: items => {
        updateOrderedChildren(items);
      },
      onDragStart: event => {
        onDragStart(event);
      },
      onDragEnd: event => {
        onDragEnd(event);
      },
      onDragOver: onDragOver,
      children: children
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "woocommerce-image-gallery__wrapper",
    children: children
  });
};
try {
    // @ts-ignore
    ImageGalleryWrapper.displayName = "ImageGalleryWrapper";
    // @ts-ignore
    ImageGalleryWrapper.__docgenInfo = { "description": "", "displayName": "ImageGalleryWrapper", "props": { "allowDragging": { "defaultValue": { value: "true" }, "description": "", "name": "allowDragging", "required": false, "type": { "name": "boolean" } }, "onDragStart": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragStart", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragEnd": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragEnd", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragOver": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragOver", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "updateOrderedChildren": { "defaultValue": { value: "() => null" }, "description": "", "name": "updateOrderedChildren", "required": false, "type": { "name": "((items: Element[]) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-wrapper.tsx#ImageGalleryWrapper"] = { docgenInfo: ImageGalleryWrapper.__docgenInfo, name: "ImageGalleryWrapper", path: "../../packages/js/components/src/image-gallery/image-gallery-wrapper.tsx#ImageGalleryWrapper" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js
var chevron_left = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/trash.js
var trash = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/trash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar/index.js + 4 modules
var toolbar = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/index.js + 2 modules
var toolbar_group = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-group/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-button/index.js + 1 modules
var toolbar_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js + 1 modules
var toolbar_item = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/toolbar/toolbar-item/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable-handle.tsx + 1 modules
var sortable_handle = __webpack_require__("../../packages/js/components/src/sortable/sortable-handle.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown-menu/index.js + 1 modules
var dropdown_menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown-menu/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/menu-group/index.js
var menu_group = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/menu-group/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/menu-item/index.js
var menu_item = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/menu-item/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/more-vertical.js
var more_vertical = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/more-vertical.js");
;// ../../packages/js/components/src/image-gallery/image-gallery-toolbar-dropdown.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */

const POPOVER_PROPS = {
  className: 'woocommerce-image-gallery__toolbar-dropdown-popover',
  placement: 'bottom-start'
};
function ImageGalleryToolbarDropdown({
  children,
  onReplace,
  onRemove,
  canRemove,
  removeBlockLabel,
  MediaUploadComponent = build_module/* MediaUpload */.Q8,
  ...props
}) {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown_menu/* default */.A, {
    icon: more_vertical/* default */.A,
    label: (0,i18n_build_module.__)('Options', 'woocommerce'),
    className: "woocommerce-image-gallery__toolbar-dropdown",
    popoverProps: POPOVER_PROPS,
    ...props,
    children: ({
      onClose
    }) => /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(menu_group/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(MediaUploadComponent, {
          onSelect: media => {
            onReplace(media);
            onClose();
          },
          allowedTypes: ['image'],
          render: ({
            open
          }) => /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* default */.A, {
            onClick: () => {
              open();
            },
            children: (0,i18n_build_module.__)('Replace', 'woocommerce')
          })
        })
      }), typeof children === 'function' ? children({
        onClose
      }) : react.Children.map(children, child => (0,react.isValidElement)(child) && (0,react.cloneElement)(child, {
        onClose
      })), canRemove && /*#__PURE__*/(0,jsx_runtime.jsx)(menu_group/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* default */.A, {
          onClick: () => {
            onClose();
            onRemove();
          },
          children: removeBlockLabel || (0,i18n_build_module.__)('Remove', 'woocommerce')
        })
      })]
    })
  });
}
try {
    // @ts-ignore
    ImageGalleryToolbarDropdown.displayName = "ImageGalleryToolbarDropdown";
    // @ts-ignore
    ImageGalleryToolbarDropdown.__docgenInfo = { "description": "", "displayName": "ImageGalleryToolbarDropdown", "props": { "onReplace": { "defaultValue": null, "description": "", "name": "onReplace", "required": true, "type": { "name": "(media: { id: number; } & BetterOmit<RestAttachment, \"title\" | \"alt_text\" | \"source_url\" | \"caption\"> & { alt: string; caption: string; title: string | undefined; url: string; poster?: string | undefined; }) => void" } }, "onRemove": { "defaultValue": null, "description": "", "name": "onRemove", "required": true, "type": { "name": "() => void" } }, "canRemove": { "defaultValue": null, "description": "", "name": "canRemove", "required": false, "type": { "name": "boolean" } }, "removeBlockLabel": { "defaultValue": null, "description": "", "name": "removeBlockLabel", "required": false, "type": { "name": "string" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": true, "type": { "name": "MediaUploadComponentType" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-toolbar-dropdown.tsx#ImageGalleryToolbarDropdown"] = { docgenInfo: ImageGalleryToolbarDropdown.__docgenInfo, name: "ImageGalleryToolbarDropdown", path: "../../packages/js/components/src/image-gallery/image-gallery-toolbar-dropdown.tsx#ImageGalleryToolbarDropdown" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/image-gallery/image-gallery-toolbar.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



const ImageGalleryToolbar = ({
  childIndex,
  allowDragging = true,
  moveItem,
  removeItem,
  replaceItem,
  setToolBarItem,
  lastChild,
  value,
  MediaUploadComponent = build_module/* MediaUpload */.Q8
}) => {
  const moveNext = () => {
    moveItem(childIndex, childIndex + 1);
  };
  const movePrevious = () => {
    moveItem(childIndex, childIndex - 1);
  };
  const setAsCoverImage = coverIndex => {
    moveItem(coverIndex, 0);
    setToolBarItem(null);
  };
  const isCoverItem = childIndex === 0;
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "woocommerce-image-gallery__toolbar",
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)(toolbar/* default */.A, {
      onClick: e => e.stopPropagation(),
      label: (0,i18n_build_module.__)('Options', 'woocommerce'),
      id: "options-toolbar",
      children: [!isCoverItem && /*#__PURE__*/(0,jsx_runtime.jsxs)(toolbar_group/* default */.A, {
        children: [allowDragging && /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_button/* default */.A, {
          icon: () => /*#__PURE__*/(0,jsx_runtime.jsx)(sortable_handle/* SortableHandle */.D, {
            itemIndex: childIndex
          }),
          label: (0,i18n_build_module.__)('Drag to reorder', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_button/* default */.A, {
          disabled: childIndex < 2,
          onClick: () => movePrevious(),
          icon: chevron_left/* default */.A,
          label: (0,i18n_build_module.__)('Move previous', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_button/* default */.A, {
          onClick: () => moveNext(),
          icon: chevron_right/* default */.A,
          label: (0,i18n_build_module.__)('Move next', 'woocommerce'),
          disabled: lastChild
        })]
      }), !isCoverItem && /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_group/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_button/* default */.A, {
          onClick: () => setAsCoverImage(childIndex),
          label: (0,i18n_build_module.__)('Set as cover', 'woocommerce'),
          children: (0,i18n_build_module.__)('Set as cover', 'woocommerce')
        })
      }), isCoverItem && /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_group/* default */.A, {
        className: "woocommerce-image-gallery__toolbar-media",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(MediaUploadComponent, {
          value: value,
          onSelect: media => replaceItem(childIndex, media),
          allowedTypes: ['image'],
          render: ({
            open
          }) => /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_button/* default */.A, {
            onClick: open,
            children: (0,i18n_build_module.__)('Replace', 'woocommerce')
          })
        })
      }), isCoverItem && /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_group/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_button/* default */.A, {
          onClick: () => removeItem(childIndex),
          icon: trash/* default */.A,
          label: (0,i18n_build_module.__)('Remove', 'woocommerce')
        })
      }), !isCoverItem && /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_group/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(toolbar_item/* default */.A, {
          children: toggleProps => /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryToolbarDropdown, {
            canRemove: true,
            onRemove: () => removeItem(childIndex),
            onReplace: media => replaceItem(childIndex, media),
            MediaUploadComponent: MediaUploadComponent,
            ...toggleProps
          })
        })
      })]
    })
  });
};
try {
    // @ts-ignore
    ImageGalleryToolbar.displayName = "ImageGalleryToolbar";
    // @ts-ignore
    ImageGalleryToolbar.__docgenInfo = { "description": "", "displayName": "ImageGalleryToolbar", "props": { "childIndex": { "defaultValue": null, "description": "", "name": "childIndex", "required": true, "type": { "name": "number" } }, "allowDragging": { "defaultValue": { value: "true" }, "description": "", "name": "allowDragging", "required": false, "type": { "name": "boolean" } }, "value": { "defaultValue": null, "description": "", "name": "value", "required": false, "type": { "name": "number" } }, "moveItem": { "defaultValue": null, "description": "", "name": "moveItem", "required": true, "type": { "name": "(fromIndex: number, toIndex: number) => void" } }, "removeItem": { "defaultValue": null, "description": "", "name": "removeItem", "required": true, "type": { "name": "(removeIndex: number) => void" } }, "replaceItem": { "defaultValue": null, "description": "", "name": "replaceItem", "required": true, "type": { "name": "(replaceIndex: number, media: { id: number; } & BetterOmit<RestAttachment, \"title\" | \"alt_text\" | \"source_url\" | \"caption\"> & { alt: string; caption: string; title: string | undefined; url: string; poster?: string | undefined; }) => void" } }, "setToolBarItem": { "defaultValue": null, "description": "", "name": "setToolBarItem", "required": true, "type": { "name": "(key: string | null) => void" } }, "lastChild": { "defaultValue": null, "description": "", "name": "lastChild", "required": true, "type": { "name": "boolean" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": true, "type": { "name": "MediaUploadComponentType" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-toolbar.tsx#ImageGalleryToolbar"] = { docgenInfo: ImageGalleryToolbar.__docgenInfo, name: "ImageGalleryToolbar", path: "../../packages/js/components/src/image-gallery/image-gallery-toolbar.tsx#ImageGalleryToolbar" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/image-gallery/image-gallery.tsx
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




const image_gallery_ImageGallery = ({
  children,
  columns = 4,
  allowDragging = true,
  onSelectAsCover = () => null,
  onOrderChange = () => null,
  onRemove = () => null,
  onReplace = () => null,
  MediaUploadComponent = build_module/* MediaUpload */.Q8,
  onDragStart = () => null,
  onDragEnd = () => null,
  onDragOver = () => null
}) => {
  const [activeToolbarKey, setActiveToolbarKey] = (0,react.useState)(null);
  const [isDragging, setIsDragging] = (0,react.useState)(false);
  const childElements = (0,react.useMemo)(() => react.Children.toArray(children), [children]);
  function cloneChild(child, childIndex) {
    const key = child.key || String(childIndex);
    const isToolbarVisible = key === activeToolbarKey;
    return (0,react.cloneElement)(child, {
      key,
      isDraggable: allowDragging && !child.props.isCover,
      className: (0,clsx/* default */.A)({
        'is-toolbar-visible': isToolbarVisible
      }),
      onClick() {
        setActiveToolbarKey(isToolbarVisible ? null : key);
      },
      onBlur(event) {
        if (isDragging || event.currentTarget.contains(event.relatedTarget) || event.relatedTarget && event.relatedTarget.closest('.media-modal, .components-modal__frame') || event.relatedTarget &&
        // Check if not a button within the toolbar is clicked, to prevent hiding the toolbar.
        event.relatedTarget.closest('.woocommerce-image-gallery__toolbar') || event.relatedTarget &&
        // Prevent toolbar from hiding if the dropdown is clicked within the toolbar.
        event.relatedTarget.closest('.woocommerce-image-gallery__toolbar-dropdown-popover')) {
          return;
        }
        setActiveToolbarKey(null);
      }
    }, isToolbarVisible && /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryToolbar, {
      value: child.props.id,
      allowDragging: allowDragging,
      childIndex: childIndex,
      lastChild: childIndex === childElements.length - 1,
      moveItem: (fromIndex, toIndex) => {
        onOrderChange((0,utils/* moveIndex */.e6)(fromIndex, toIndex, childElements));
      },
      removeItem: removeIndex => {
        onRemove({
          removeIndex,
          removedItem: childElements[removeIndex]
        });
      },
      replaceItem: (replaceIndex, media) => {
        onReplace({
          replaceIndex,
          media
        });
      },
      setToolBarItem: toolBarItem => {
        onSelectAsCover(activeToolbarKey);
        setActiveToolbarKey(toolBarItem);
      },
      MediaUploadComponent: MediaUploadComponent
    }));
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "woocommerce-image-gallery",
    style: {
      gridTemplateColumns: 'min-content '.repeat(columns)
    },
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryWrapper, {
      allowDragging: allowDragging,
      updateOrderedChildren: onOrderChange,
      onDragStart: event => {
        setIsDragging(true);
        onDragStart(event);
      },
      onDragEnd: event => {
        setIsDragging(false);
        onDragEnd(event);
      },
      onDragOver: onDragOver,
      children: childElements.map(cloneChild)
    })
  });
};
try {
    // @ts-ignore
    image_gallery_ImageGallery.displayName = "ImageGallery";
    // @ts-ignore
    image_gallery_ImageGallery.__docgenInfo = { "description": "", "displayName": "ImageGallery", "props": { "columns": { "defaultValue": { value: "4" }, "description": "", "name": "columns", "required": false, "type": { "name": "number" } }, "onRemove": { "defaultValue": { value: "() => null" }, "description": "", "name": "onRemove", "required": false, "type": { "name": "((props: { removeIndex: number; removedItem: Element; }) => void)" } }, "onReplace": { "defaultValue": { value: "() => null" }, "description": "", "name": "onReplace", "required": false, "type": { "name": "((props: { replaceIndex: number; media: { id: number; } & BetterOmit<RestAttachment, \"title\" | \"alt_text\" | \"source_url\" | \"caption\"> & { alt: string; caption: string; title: string; url: string; poster?: string; }; }) => void) | undefined" } }, "allowDragging": { "defaultValue": { value: "true" }, "description": "", "name": "allowDragging", "required": false, "type": { "name": "boolean" } }, "onSelectAsCover": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelectAsCover", "required": false, "type": { "name": "((itemId: string | null) => void)" } }, "onOrderChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onOrderChange", "required": false, "type": { "name": "((items: Element[]) => void)" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": false, "type": { "name": "MediaUploadComponentType" } }, "onDragStart": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragStart", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragEnd": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragEnd", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragOver": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragOver", "required": false, "type": { "name": "(DragEventHandler<HTMLLIElement> & DragEventHandler<HTMLDivElement>)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery.tsx#ImageGallery"] = { docgenInfo: image_gallery_ImageGallery.__docgenInfo, name: "ImageGallery", path: "../../packages/js/components/src/image-gallery/image-gallery.tsx#ImageGallery" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pill/pill.js
var pill = __webpack_require__("../../packages/js/components/src/pill/pill.js");
;// ../../packages/js/components/src/sortable/non-sortable-item.tsx
/**
 * External dependencies
 */

const NonSortableItem = ({
  children
}) => {
  if (children === null) {
    return children;
  }
  return (0,react.cloneElement)(children, {
    className: `${children.props?.className || ''} non-sortable-item`
  });
};
try {
    // @ts-ignore
    NonSortableItem.displayName = "NonSortableItem";
    // @ts-ignore
    NonSortableItem.__docgenInfo = { "description": "", "displayName": "NonSortableItem", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/non-sortable-item.tsx#NonSortableItem"] = { docgenInfo: NonSortableItem.__docgenInfo, name: "NonSortableItem", path: "../../packages/js/components/src/sortable/non-sortable-item.tsx#NonSortableItem" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/conditional-wrapper/conditional-wrapper.tsx
const ConditionalWrapper = ({
  condition,
  wrapper,
  children
}) => condition ? wrapper(children) : children;
try {
    // @ts-ignore
    ConditionalWrapper.displayName = "ConditionalWrapper";
    // @ts-ignore
    ConditionalWrapper.__docgenInfo = { "description": "", "displayName": "ConditionalWrapper", "props": { "condition": { "defaultValue": null, "description": "", "name": "condition", "required": true, "type": { "name": "boolean" } }, "wrapper": { "defaultValue": null, "description": "", "name": "wrapper", "required": true, "type": { "name": "(children: T) => Element" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/conditional-wrapper/conditional-wrapper.tsx#ConditionalWrapper"] = { docgenInfo: ConditionalWrapper.__docgenInfo, name: "ConditionalWrapper", path: "../../packages/js/components/src/conditional-wrapper/conditional-wrapper.tsx#ConditionalWrapper" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/image-gallery/image-gallery-item.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




const ImageGalleryItem = ({
  id,
  alt,
  isCover = false,
  isDraggable = true,
  src,
  className = '',
  onClick = () => null,
  onBlur = () => null,
  children
}) => /*#__PURE__*/(0,jsx_runtime.jsx)(ConditionalWrapper, {
  condition: !isDraggable,
  wrapper: wrappedChildren => /*#__PURE__*/(0,jsx_runtime.jsx)(NonSortableItem, {
    children: wrappedChildren
  }),
  children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: `woocommerce-image-gallery__item ${className}`,
    onKeyPress: () => {},
    tabIndex: 0,
    role: "button",
    onClick: event => onClick(event),
    onBlur: event => onBlur(event),
    children: [children, isDraggable ? /*#__PURE__*/(0,jsx_runtime.jsx)(sortable_handle/* SortableHandle */.D, {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("img", {
        alt: alt,
        src: src,
        id: id
      })
    }) : /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [isCover && /*#__PURE__*/(0,jsx_runtime.jsx)(pill/* Pill */.a, {
        children: (0,i18n_build_module.__)('Cover', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("img", {
        alt: alt,
        src: src,
        id: id
      })]
    })]
  })
});
try {
    // @ts-ignore
    ImageGalleryItem.displayName = "ImageGalleryItem";
    // @ts-ignore
    ImageGalleryItem.__docgenInfo = { "description": "", "displayName": "ImageGalleryItem", "props": { "id": { "defaultValue": null, "description": "", "name": "id", "required": false, "type": { "name": "string" } }, "alt": { "defaultValue": null, "description": "", "name": "alt", "required": true, "type": { "name": "string" } }, "isCover": { "defaultValue": { value: "false" }, "description": "", "name": "isCover", "required": false, "type": { "name": "boolean" } }, "isDraggable": { "defaultValue": { value: "true" }, "description": "", "name": "isDraggable", "required": false, "type": { "name": "boolean" } }, "src": { "defaultValue": null, "description": "", "name": "src", "required": true, "type": { "name": "string" } }, "displayToolbar": { "defaultValue": null, "description": "", "name": "displayToolbar", "required": false, "type": { "name": "boolean" } }, "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "onClick": { "defaultValue": { value: "() => null" }, "description": "", "name": "onClick", "required": false, "type": { "name": "((() => void) & MouseEventHandler<HTMLDivElement>)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/image-gallery-item.tsx#ImageGalleryItem"] = { docgenInfo: ImageGalleryItem.__docgenInfo, name: "ImageGalleryItem", path: "../../packages/js/components/src/image-gallery/image-gallery-item.tsx#ImageGalleryItem" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx
var mock_media_uploader = __webpack_require__("../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx");
;// ../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const Basic = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(image_gallery_ImageGallery, {
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    onReplace: ({
      replaceIndex
    }) =>
    // eslint-disable-next-line no-console
    console.info(`Item ${replaceIndex} replaced`),
    onRemove: ({
      removeIndex
    }) => {
      // eslint-disable-next-line no-console
      console.info(`Item ${removeIndex} removed`);
    },
    onOrderChange: () => {
      // eslint-disable-next-line no-console
      console.info(`Order changed`);
    },
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 1",
      src: "https://picsum.photos/id/137/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 2",
      src: "https://picsum.photos/id/208/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 3",
      src: "https://picsum.photos/id/24/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 4",
      src: "https://picsum.photos/id/58/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 5",
      src: "https://picsum.photos/id/309/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 6",
      src: "https://picsum.photos/id/46/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 7",
      src: "https://picsum.photos/id/8/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 8",
      src: "https://picsum.photos/id/101/200/200"
    })]
  });
};
const Cover = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(image_gallery_ImageGallery, {
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 1",
      src: "https://picsum.photos/id/137/200/200",
      isCover: true
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 2",
      src: "https://picsum.photos/id/208/200/200"
    })]
  });
};
const Columns = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(image_gallery_ImageGallery, {
    columns: 3,
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 1",
      src: "https://picsum.photos/id/137/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 2",
      src: "https://picsum.photos/id/208/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 3",
      src: "https://picsum.photos/id/24/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 4",
      src: "https://picsum.photos/id/58/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 5",
      src: "https://picsum.photos/id/309/200/200"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(ImageGalleryItem, {
      alt: "Random image 6",
      src: "https://picsum.photos/id/46/200/200"
    })]
  });
};
/* harmony default export */ const image_gallery_story = ({
  title: 'Components/ImageGallery',
  component: image_gallery_ImageGallery
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <ImageGallery MediaUploadComponent={MockMediaUpload} onReplace={({\n    replaceIndex\n  }) =>\n  // eslint-disable-next-line no-console\n  console.info(`Item ${replaceIndex} replaced`)} onRemove={({\n    removeIndex\n  }) => {\n    // eslint-disable-next-line no-console\n    console.info(`Item ${removeIndex} removed`);\n  }} onOrderChange={() => {\n    // eslint-disable-next-line no-console\n    console.info(`Order changed`);\n  }}>\n            <ImageGalleryItem alt=\"Random image 1\" src=\"https://picsum.photos/id/137/200/200\" />\n            <ImageGalleryItem alt=\"Random image 2\" src=\"https://picsum.photos/id/208/200/200\" />\n            <ImageGalleryItem alt=\"Random image 3\" src=\"https://picsum.photos/id/24/200/200\" />\n            <ImageGalleryItem alt=\"Random image 4\" src=\"https://picsum.photos/id/58/200/200\" />\n            <ImageGalleryItem alt=\"Random image 5\" src=\"https://picsum.photos/id/309/200/200\" />\n            <ImageGalleryItem alt=\"Random image 6\" src=\"https://picsum.photos/id/46/200/200\" />\n            <ImageGalleryItem alt=\"Random image 7\" src=\"https://picsum.photos/id/8/200/200\" />\n            <ImageGalleryItem alt=\"Random image 8\" src=\"https://picsum.photos/id/101/200/200\" />\n        </ImageGallery>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
Cover.parameters = {
  ...Cover.parameters,
  docs: {
    ...Cover.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <ImageGallery MediaUploadComponent={MockMediaUpload}>\n            <ImageGalleryItem alt=\"Random image 1\" src=\"https://picsum.photos/id/137/200/200\" isCover />\n            <ImageGalleryItem alt=\"Random image 2\" src=\"https://picsum.photos/id/208/200/200\" />\n        </ImageGallery>;\n}",
      ...Cover.parameters?.docs?.source
    }
  }
};
Columns.parameters = {
  ...Columns.parameters,
  docs: {
    ...Columns.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <ImageGallery columns={3} MediaUploadComponent={MockMediaUpload}>\n            <ImageGalleryItem alt=\"Random image 1\" src=\"https://picsum.photos/id/137/200/200\" />\n            <ImageGalleryItem alt=\"Random image 2\" src=\"https://picsum.photos/id/208/200/200\" />\n            <ImageGalleryItem alt=\"Random image 3\" src=\"https://picsum.photos/id/24/200/200\" />\n            <ImageGalleryItem alt=\"Random image 4\" src=\"https://picsum.photos/id/58/200/200\" />\n            <ImageGalleryItem alt=\"Random image 5\" src=\"https://picsum.photos/id/309/200/200\" />\n            <ImageGalleryItem alt=\"Random image 6\" src=\"https://picsum.photos/id/46/200/200\" />\n        </ImageGallery>;\n}",
      ...Columns.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    ImageGallery.displayName = "ImageGallery";
    // @ts-ignore
    ImageGallery.__docgenInfo = { "description": "", "displayName": "ImageGallery", "props": { "columns": { "defaultValue": { value: "4" }, "description": "", "name": "columns", "required": false, "type": { "name": "number" } }, "onRemove": { "defaultValue": { value: "() => null" }, "description": "", "name": "onRemove", "required": false, "type": { "name": "((props: { removeIndex: number; removedItem: Element; }) => void)" } }, "onReplace": { "defaultValue": { value: "() => null" }, "description": "", "name": "onReplace", "required": false, "type": { "name": "((props: { replaceIndex: number; media: { id: number; } & BetterOmit<RestAttachment, \"title\" | \"alt_text\" | \"source_url\" | \"caption\"> & { alt: string; caption: string; title: string; url: string; poster?: string; }; }) => void) | undefined" } }, "allowDragging": { "defaultValue": { value: "true" }, "description": "", "name": "allowDragging", "required": false, "type": { "name": "boolean" } }, "onSelectAsCover": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelectAsCover", "required": false, "type": { "name": "((itemId: string | null) => void)" } }, "onOrderChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onOrderChange", "required": false, "type": { "name": "((items: Element[]) => void)" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": false, "type": { "name": "MediaUploadComponentType" } }, "onDragStart": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragStart", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragEnd": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragEnd", "required": false, "type": { "name": "DragEventHandler<HTMLDivElement>" } }, "onDragOver": { "defaultValue": { value: "() => null" }, "description": "", "name": "onDragOver", "required": false, "type": { "name": "(DragEventHandler<HTMLLIElement> & DragEventHandler<HTMLDivElement>)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#ImageGallery"] = { docgenInfo: ImageGallery.__docgenInfo, name: "ImageGallery", path: "../../packages/js/components/src/image-gallery/stories/image-gallery.story.tsx#ImageGallery" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */


/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
const Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Text || _wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A;

/***/ }),

/***/ "../../packages/js/components/src/pill/pill.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ Pill)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _experimental__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/experimental.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


function Pill({
  children,
  className = ''
}) {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_experimental__WEBPACK_IMPORTED_MODULE_1__/* .Text */ .E, {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-pill', className),
    variant: "caption",
    as: "span",
    size: "12",
    lineHeight: "16px",
    children: children
  });
}
;
Pill.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "Pill",
  "props": {
    "className": {
      "defaultValue": {
        "value": "''",
        "computed": false
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   I: () => (/* binding */ MockMediaUpload)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const MockMediaUpload = ({
  onSelect,
  render
}) => {
  const [isOpen, setOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    children: [render({
      open: () => setOpen(true)
    }), isOpen && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      title: "Media Modal",
      onRequestClose: event => {
        setOpen(false);
        event.stopPropagation();
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("p", {
        children: ["Use the default built-in", ' ', /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("code", {
          children: "MediaUploadComponent"
        }), " prop to render the WP Media Modal."]
      }), Array(...Array(3)).map((n, i) => {
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
          onClick: event => {
            onSelect({
              alt: 'Random',
              url: `https://picsum.photos/200?i=${i}`
            });
            setOpen(false);
            event.stopPropagation();
          },
          style: {
            marginRight: '16px'
          },
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("img", {
            src: `https://picsum.photos/200?i=${i}`,
            alt: "Random",
            style: {
              maxWidth: '100px'
            }
          })
        }, i);
      })]
    })]
  });
};
try {
    // @ts-ignore
    MockMediaUpload.displayName = "MockMediaUpload";
    // @ts-ignore
    MockMediaUpload.__docgenInfo = { "description": "", "displayName": "MockMediaUpload", "props": { "onSelect": { "defaultValue": null, "description": "", "name": "onSelect", "required": true, "type": { "name": "any" } }, "render": { "defaultValue": null, "description": "", "name": "render", "required": true, "type": { "name": "any" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx#MockMediaUpload"] = { docgenInfo: MockMediaUpload.__docgenInfo, name: "MockMediaUpload", path: "../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx#MockMediaUpload" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/sortable-handle.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  D: () => (/* binding */ SortableHandle)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/sortable/draggable-icon.tsx

/**
 * External dependencies
 */

const DraggableIcon = () => /*#__PURE__*/(0,jsx_runtime.jsxs)("svg", {
  width: "8",
  height: "14",
  viewBox: "0 0 8 14",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg",
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    y: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    y: "12",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    x: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    x: "6",
    y: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    x: "6",
    y: "12",
    width: "2",
    height: "2",
    fill: "#757575"
  })]
});
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
;// ../../packages/js/components/src/sortable/sortable-handle.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



const SortableHandle = ({
  children,
  itemIndex
}) => {
  const {
    onDragStart,
    onDragEnd
  } = (0,react.useContext)(sortable/* SortableContext */.g);
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "woocommerce-sortable__handle",
    draggable: true,
    onDragStart: onDragStart,
    onDragEnd: onDragEnd,
    "data-index": itemIndex,
    children: children ? children : /*#__PURE__*/(0,jsx_runtime.jsx)(DraggableIcon, {})
  });
};
try {
    // @ts-ignore
    SortableHandle.displayName = "SortableHandle";
    // @ts-ignore
    SortableHandle.__docgenInfo = { "description": "", "displayName": "SortableHandle", "props": { "itemIndex": { "defaultValue": null, "description": "", "name": "itemIndex", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable-handle.tsx#SortableHandle"] = { docgenInfo: SortableHandle.__docgenInfo, name: "SortableHandle", path: "../../packages/js/components/src/sortable/sortable-handle.tsx#SortableHandle" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/sortable.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   L: () => (/* binding */ Sortable),
/* harmony export */   g: () => (/* binding */ SortableContext)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var uuid__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/v4.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../packages/js/components/src/sortable/utils.ts");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */


const THROTTLE_TIME = 16;
const SortableContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createContext)({});
const Sortable = ({
  children,
  isHorizontal = false,
  onDragEnd = () => null,
  onDragOver = () => null,
  onDragStart = () => null,
  onOrderChange = () => null,
  className,
  role = 'listbox',
  ...props
}) => {
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useRef)(null);
  const [items, setItems] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)([]);
  const [selectedIndex, setSelectedIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(-1);
  const [dragIndex, setDragIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(null);
  const [dropIndex, setDropIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    if (!children) {
      return;
    }
    setItems(Array.isArray(children) ? children : [children]);
  }, [children]);
  const resetIndexes = () => {
    setTimeout(() => {
      setDragIndex(null);
      setDropIndex(null);
    }, THROTTLE_TIME);
  };
  const persistItemOrder = () => {
    if (dropIndex !== null && dragIndex !== null && dropIndex !== dragIndex) {
      const nextItems = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .moveIndex */ .e6)(dragIndex, dropIndex, items);
      setItems(nextItems);
      onOrderChange(nextItems);
    }
    resetIndexes();
  };
  const handleDragStart = (event, index) => {
    setDropIndex(index);
    setDragIndex(index);
    onDragStart(event);
  };
  const handleDragEnd = event => {
    persistItemOrder();
    onDragEnd(event);
  };
  const handleDragOver = (event, index) => {
    if (dragIndex === null) {
      return;
    }

    // Items before the current item cause a one off error when
    // removed from the old array and spliced into the new array.
    // TODO: Issue with dragging into same position having to do with isBefore returning true initially.
    let targetIndex = dragIndex < index ? index : index + 1;
    if ((0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isBefore */ .Y8)(event, isHorizontal)) {
      targetIndex--;
    }
    setDropIndex(targetIndex);
    onDragOver(event);
  };
  const throttledHandleDragOver = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useCallback)((0,lodash__WEBPACK_IMPORTED_MODULE_2__.throttle)(handleDragOver, THROTTLE_TIME), [dragIndex]);
  const handleKeyDown = event => {
    const {
      key
    } = event;
    const isSelecting = dragIndex === null || dropIndex === null;
    const selectedLabel = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getItemName */ .H0)(ref.current, selectedIndex);

    // Select or drop on spacebar press.
    if (key === ' ') {
      if (isSelecting) {
        (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/** Translators: Selected item label */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s selected, use up and down arrow keys to reorder', 'woocommerce'), selectedLabel ?? ''), 'assertive');
        setDragIndex(selectedIndex);
        setDropIndex(selectedIndex);
        return;
      }
      setSelectedIndex(dropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s dropped, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel ?? '', dropIndex + 1, items.length), 'assertive');
      persistItemOrder();
      return;
    }
    if (key === 'ArrowUp') {
      if (isSelecting) {
        setSelectedIndex((0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getPreviousIndex */ .S1)(selectedIndex, items.length));
        return;
      }
      const previousDropIndex = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getPreviousIndex */ .S1)(dropIndex, items.length);
      setDropIndex(previousDropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel ?? '', previousDropIndex + 1, items.length), 'assertive');
      return;
    }
    if (key === 'ArrowDown') {
      if (isSelecting) {
        setSelectedIndex((0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getNextIndex */ .g0)(selectedIndex, items.length));
        return;
      }
      const nextDropIndex = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getNextIndex */ .g0)(dropIndex, items.length);
      setDropIndex(nextDropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel ?? '', nextDropIndex + 1, items.length), 'assertive');
      return;
    }
    if (key === 'Escape') {
      resetIndexes();
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Reordering cancelled. Restoring the original list order', 'woocommerce'), 'assertive');
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(SortableContext.Provider, {
    value: {},
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      ...props,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)('woocommerce-sortable', className, {
        'is-dragging': dragIndex !== null,
        'is-horizontal': isHorizontal
      }),
      ref: ref,
      role: role,
      children: items.map((child, index) => {
        const isDragging = index === dragIndex;
        if (child.props.className && child.props.className.indexOf('non-sortable-item') !== -1) {
          return child;
        }
        const itemClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)(child.props.className, {
          'is-dragging-over-after': (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isDraggingOverAfter */ .Km)(index, dragIndex, dropIndex),
          'is-dragging-over-before': (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isDraggingOverBefore */ .PZ)(index, dragIndex, dropIndex),
          'is-last-droppable': (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isLastDroppable */ .Ib)(index, dragIndex, items.length)
        });
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.cloneElement)(child, {
          key: child.key || index,
          className: itemClasses,
          id: `${index}-${(0,uuid__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)()}`,
          index,
          isDragging,
          isSelected: selectedIndex === index,
          onDragEnd: handleDragEnd,
          onDragStart: event => handleDragStart(event, index),
          onDragOver: event => {
            event.preventDefault();
            throttledHandleDragOver(event, index);
          },
          onKeyDown: event => handleKeyDown(event)
        });
      })
    })
  });
};
try {
    // @ts-ignore
    Sortable.displayName = "Sortable";
    // @ts-ignore
    Sortable.__docgenInfo = { "description": "", "displayName": "Sortable", "props": { "isHorizontal": { "defaultValue": { value: "false" }, "description": "", "name": "isHorizontal", "required": false, "type": { "name": "boolean" } }, "onOrderChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onOrderChange", "required": false, "type": { "name": "((items: Element[]) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable.tsx#Sortable"] = { docgenInfo: Sortable.__docgenInfo, name: "Sortable", path: "../../packages/js/components/src/sortable/sortable.tsx#Sortable" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/utils.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H0: () => (/* binding */ getItemName),
/* harmony export */   Ib: () => (/* binding */ isLastDroppable),
/* harmony export */   Km: () => (/* binding */ isDraggingOverAfter),
/* harmony export */   PZ: () => (/* binding */ isDraggingOverBefore),
/* harmony export */   S1: () => (/* binding */ getPreviousIndex),
/* harmony export */   Y8: () => (/* binding */ isBefore),
/* harmony export */   e6: () => (/* binding */ moveIndex),
/* harmony export */   g0: () => (/* binding */ getNextIndex)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/**
 * External dependencies
 */

/**
 * Move an item from an index in an array to a new index.s
 *
 * @param fromIndex Index to move the item from.
 * @param toIndex   Index to move the item to.
 * @param arr       The array to copy.
 * @return array
 */
const moveIndex = (fromIndex, toIndex, arr) => {
  const newArr = [...arr];
  const item = arr[fromIndex];
  newArr.splice(fromIndex, 1);
  newArr.splice(toIndex, 0, item);
  return newArr;
};

/**
 * Check whether the mouse is over the first half of the event target.
 *
 * @param event        Drag event.
 * @param isHorizontal Check horizontally or vertically.
 * @return boolean
 */
const isBefore = (event, isHorizontal = false) => {
  const target = event.target;
  if (isHorizontal) {
    const middle = target.offsetWidth / 2;
    const rect = target.getBoundingClientRect();
    const relativeX = event.clientX - rect.left;
    return relativeX < middle;
  }
  const middle = target.offsetHeight / 2;
  const rect = target.getBoundingClientRect();
  const relativeY = event.clientY - rect.top;
  return relativeY < middle;
};
const isDraggingOverAfter = (index, dragIndex, dropIndex) => {
  if (dragIndex === null) {
    return false;
  }
  if (dragIndex < index) {
    return dropIndex === index;
  }
  return dropIndex === index + 1;
};
const isDraggingOverBefore = (index, dragIndex, dropIndex) => {
  if (dragIndex === null) {
    return false;
  }
  if (dragIndex < index) {
    return dropIndex === index - 1;
  }
  return dropIndex === index;
};
const isLastDroppable = (index, dragIndex, itemCount) => {
  if (dragIndex === index) {
    return false;
  }
  if (index === itemCount - 1) {
    return true;
  }
  if (dragIndex === itemCount - 1 && index === itemCount - 2) {
    return true;
  }
  return false;
};
const getNextIndex = (currentIndex, itemCount) => {
  let index = currentIndex + 1;
  if (index > itemCount - 1) {
    index = 0;
  }
  return index;
};
const getPreviousIndex = (currentIndex, itemCount) => {
  let index = currentIndex - 1;
  if (index < 0) {
    index = itemCount - 1;
  }
  return index;
};
const getItemName = (parentNode, index) => {
  const listItemNode = parentNode?.childNodes[index];
  if (index === null || !listItemNode) {
    return null;
  }
  if (listItemNode.querySelector('[aria-label]')) {
    return listItemNode.querySelector('[aria-label]')?.ariaLabel;
  }
  if (listItemNode.textContent) {
    return listItemNode.textContent;
  }
  if (listItemNode.querySelector('[alt]')) {
    return listItemNode.querySelector('[alt]').alt;
  }
  return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Item', 'woocommerce');
};

/***/ })

}]);