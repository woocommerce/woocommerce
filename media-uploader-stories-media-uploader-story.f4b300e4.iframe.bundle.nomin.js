"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1190],{

/***/ "../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  ButtonWithOnlyIcon: () => (/* binding */ ButtonWithOnlyIcon),
  DisabledDropZone: () => (/* binding */ DisabledDropZone),
  MaxUploadFileSize: () => (/* binding */ MaxUploadFileSize),
  "default": () => (/* binding */ media_uploader_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js + 4 modules
var notice = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/cloud-upload.js


var cloud_upload_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M17.3 10.1C17.3 7.60001 15.2 5.70001 12.5 5.70001C10.3 5.70001 8.4 7.10001 7.9 9.00001H7.7C5.7 9.00001 4 10.7 4 12.8C4 14.9 5.7 16.6 7.7 16.6H9.5V15.2H7.7C6.5 15.2 5.5 14.1 5.5 12.9C5.5 11.7 6.5 10.5 7.7 10.5H9L9.3 9.40001C9.7 8.10001 11 7.20001 12.5 7.20001C14.3 7.20001 15.8 8.50001 15.8 10.1V11.4L17.1 11.6C17.9 11.7 18.5 12.5 18.5 13.4C18.5 14.4 17.7 15.2 16.8 15.2H14.5V16.6H16.7C18.5 16.6 19.9 15.1 19.9 13.3C20 11.7 18.8 10.4 17.3 10.1Z M14.1245 14.2426L15.1852 13.182L12.0032 10L8.82007 13.1831L9.88072 14.2438L11.25 12.8745V18H12.75V12.8681L14.1245 14.2426Z" }) });

//# sourceMappingURL=cloud-upload.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js
var deprecated_36px_size = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/form-file-upload/index.js




function FormFileUpload({
  accept,
  children,
  multiple = false,
  onChange,
  onClick,
  render,
  ...props
}) {
  const ref = (0,react.useRef)(null);
  const openFileDialog = () => {
    ref.current?.click();
  };
  if (!render) {
    (0,deprecated_36px_size/* maybeWarnDeprecated36pxSize */.M)({
      componentName: "FormFileUpload",
      __next40pxDefaultSize: props.__next40pxDefaultSize,
      // @ts-expect-error - We don't "officially" support all Button props but this likely happens.
      size: props.size
    });
  }
  const ui = render ? render({
    openFileDialog
  }) : /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    onClick: openFileDialog,
    ...props,
    children
  });
  const compatAccept = accept?.includes("audio/*") ? `${accept}, audio/mp3, audio/x-m4a, audio/x-m4b, audio/x-m4p, audio/x-wav, audio/webm` : accept;
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
    className: "components-form-file-upload",
    children: [ui, /* @__PURE__ */ (0,jsx_runtime.jsx)("input", {
      type: "file",
      ref,
      multiple,
      style: {
        display: "none"
      },
      accept: compatAccept,
      onChange,
      onClick,
      "data-testid": "form-file-upload-input"
    })]
  });
}
var form_file_upload_default = FormFileUpload;

//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var build_module_svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/upload.mjs
// packages/icons/src/library/upload.tsx


var upload_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_svg/* Path */.wA, { d: "M18.5 15v3.5H13V6.7l4.5 4.1 1-1.1-6.2-5.8-5.8 5.8 1 1.1 4-4v11.7h-6V15H4v5h16v-5z" }) });

//# sourceMappingURL=upload.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs
var build_module_icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+dom@4.48.1/node_modules/@wordpress/dom/build-module/data-transfer.mjs
// packages/dom/src/data-transfer.js
function getFilesFromDataTransfer(dataTransfer) {
  const files = Array.from(dataTransfer.files);
  Array.from(dataTransfer.items).forEach((item) => {
    const file = item.getAsFile();
    if (file && !files.find(
      ({ name, type, size }) => name === file.name && type === file.type && size === file.size
    )) {
      files.push(file);
    }
  });
  return files;
}

//# sourceMappingURL=data-transfer.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs
var use_ref_effect = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-event/index.mjs
var use_event = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-event/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-drop-zone/index.mjs
// packages/compose/src/hooks/use-drop-zone/index.js


function useDropZone({
  dropZoneElement,
  isDisabled,
  onDrop: _onDrop,
  onDragStart: _onDragStart,
  onDragEnter: _onDragEnter,
  onDragLeave: _onDragLeave,
  onDragEnd: _onDragEnd,
  onDragOver: _onDragOver
}) {
  const onDropEvent = (0,use_event/* default */.A)(_onDrop);
  const onDragStartEvent = (0,use_event/* default */.A)(_onDragStart);
  const onDragEnterEvent = (0,use_event/* default */.A)(_onDragEnter);
  const onDragLeaveEvent = (0,use_event/* default */.A)(_onDragLeave);
  const onDragEndEvent = (0,use_event/* default */.A)(_onDragEnd);
  const onDragOverEvent = (0,use_event/* default */.A)(_onDragOver);
  return (0,use_ref_effect/* default */.A)(
    (elem) => {
      if (isDisabled) {
        return;
      }
      const element = dropZoneElement ?? elem;
      let isDragging = false;
      const { ownerDocument } = element;
      function isElementInZone(targetToCheck) {
        const { defaultView } = ownerDocument;
        if (!targetToCheck || !defaultView || !(targetToCheck instanceof defaultView.HTMLElement) || !element.contains(targetToCheck)) {
          return false;
        }
        let elementToCheck = targetToCheck;
        do {
          if (elementToCheck.dataset.isDropZone) {
            return elementToCheck === element;
          }
        } while (elementToCheck = elementToCheck.parentElement);
        return false;
      }
      function maybeDragStart(event) {
        if (isDragging) {
          return;
        }
        isDragging = true;
        ownerDocument.addEventListener("dragend", maybeDragEnd);
        ownerDocument.addEventListener("mousemove", maybeDragEnd);
        if (_onDragStart) {
          onDragStartEvent(event);
        }
      }
      function onDragEnter(event) {
        event.preventDefault();
        if (element.contains(
          /** @type {Node} */
          event.relatedTarget
        )) {
          return;
        }
        if (_onDragEnter) {
          onDragEnterEvent(event);
        }
      }
      function onDragOver(event) {
        if (!event.defaultPrevented && _onDragOver) {
          onDragOverEvent(event);
        }
        event.preventDefault();
      }
      function onDragLeave(event) {
        if (isElementInZone(event.relatedTarget)) {
          return;
        }
        if (_onDragLeave) {
          onDragLeaveEvent(event);
        }
      }
      function onDrop(event) {
        if (event.defaultPrevented) {
          return;
        }
        event.preventDefault();
        event.dataTransfer && event.dataTransfer.files.length;
        if (_onDrop) {
          onDropEvent(event);
        }
        maybeDragEnd(event);
      }
      function maybeDragEnd(event) {
        if (!isDragging) {
          return;
        }
        isDragging = false;
        ownerDocument.removeEventListener("dragend", maybeDragEnd);
        ownerDocument.removeEventListener("mousemove", maybeDragEnd);
        if (_onDragEnd) {
          onDragEndEvent(event);
        }
      }
      element.setAttribute("data-is-drop-zone", "true");
      element.addEventListener("drop", onDrop);
      element.addEventListener("dragenter", onDragEnter);
      element.addEventListener("dragover", onDragOver);
      element.addEventListener("dragleave", onDragLeave);
      ownerDocument.addEventListener("dragenter", maybeDragStart);
      return () => {
        element.removeAttribute("data-is-drop-zone");
        element.removeEventListener("drop", onDrop);
        element.removeEventListener("dragenter", onDragEnter);
        element.removeEventListener("dragover", onDragOver);
        element.removeEventListener("dragleave", onDragLeave);
        ownerDocument.removeEventListener("dragend", maybeDragEnd);
        ownerDocument.removeEventListener("mousemove", maybeDragEnd);
        ownerDocument.removeEventListener(
          "dragenter",
          maybeDragStart
        );
      };
    },
    [isDisabled, dropZoneElement]
    // Refresh when the passed in dropZoneElement changes.
  );
}

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/drop-zone/index.js







function DropZoneComponent({
  className,
  icon = upload_default,
  label,
  onFilesDrop,
  onHTMLDrop,
  onDrop,
  isEligible = () => true,
  ...restProps
}) {
  const [isDraggingOverDocument, setIsDraggingOverDocument] = (0,react.useState)();
  const [isDraggingOverElement, setIsDraggingOverElement] = (0,react.useState)();
  const [isActive, setIsActive] = (0,react.useState)();
  const ref = useDropZone({
    onDrop(event) {
      if (!event.dataTransfer) {
        return;
      }
      const files = getFilesFromDataTransfer(event.dataTransfer);
      const html = event.dataTransfer.getData("text/html");
      if (html && onHTMLDrop) {
        onHTMLDrop(html);
      } else if (files.length && onFilesDrop) {
        onFilesDrop(files);
      } else if (onDrop) {
        onDrop(event);
      }
    },
    onDragStart(event) {
      setIsDraggingOverDocument(true);
      if (!event.dataTransfer) {
        return;
      }
      if (event.dataTransfer.types.includes("text/html")) {
        setIsActive(!!onHTMLDrop);
      } else if (
        // Check for the types because sometimes the files themselves
        // are only available on drop.
        event.dataTransfer.types.includes("Files") || getFilesFromDataTransfer(event.dataTransfer).length > 0
      ) {
        setIsActive(!!onFilesDrop);
      } else {
        setIsActive(!!onDrop && isEligible(event.dataTransfer));
      }
    },
    onDragEnd() {
      setIsDraggingOverElement(false);
      setIsDraggingOverDocument(false);
      setIsActive(void 0);
    },
    onDragEnter() {
      setIsDraggingOverElement(true);
    },
    onDragLeave() {
      setIsDraggingOverElement(false);
    }
  });
  const classes = (0,clsx/* default */.A)("components-drop-zone", className, {
    "is-active": isActive,
    "is-dragging-over-document": isDraggingOverDocument,
    "is-dragging-over-element": isDraggingOverElement
  });
  return /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
    ...restProps,
    ref,
    className: classes,
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
      className: "components-drop-zone__content",
      children: /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
        className: "components-drop-zone__content-inner",
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
          icon,
          className: "components-drop-zone__content-icon"
        }), /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
          className: "components-drop-zone__content-text",
          children: label ? label : (0,i18n_build_module.__)("Drop files to upload")
        })]
      })
    })
  });
}
var drop_zone_default = DropZoneComponent;

//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/index.js + 28 modules
var media_utils_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/index.js");
;// ../../packages/js/components/src/media-uploader/media-uploader.tsx
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */

const DEFAULT_ALLOWED_MEDIA_TYPES = ['image'];
const MediaUploader = ({
  allowedMediaTypes = DEFAULT_ALLOWED_MEDIA_TYPES,
  buttonText = (0,build_module.__)('Choose images', 'woocommerce'),
  buttonProps,
  hasDropZone = true,
  label = (0,build_module.__)('Drag images here or click to upload', 'woocommerce'),
  maxUploadFileSize = 10000000,
  MediaUploadComponent = media_utils_build_module/* MediaUpload */.Q8,
  multipleSelect = false,
  value,
  onError = () => null,
  onFileUploadChange = () => null,
  onMediaGalleryOpen = () => null,
  onUpload = () => null,
  onSelect = () => null,
  uploadMedia = media_utils_build_module/* uploadMedia */.o7,
  additionalData
}) => {
  const multiple = Boolean(multipleSelect);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(form_file_upload_default, {
    accept: allowedMediaTypes.toString(),
    multiple: multiple,
    onChange: ({
      currentTarget
    }) => {
      uploadMedia({
        allowedTypes: allowedMediaTypes,
        filesList: Array.from(currentTarget.files ?? []),
        maxUploadFileSize,
        // Runtime passes UploadError (with code + file), not plain Error.
        onError: onError,
        onFileChange(files) {
          onFileUploadChange(multiple ? files : files[0]);
        },
        additionalData
      });
    },
    render: ({
      openFileDialog
    }) => /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-form-file-upload",
      onKeyPress: () => {},
      tabIndex: 0,
      role: "button",
      onClick: event => {
        const {
          target
        } = event;
        // is the click on the button from MediaUploadComponent or on the div?
        if (!target.closest('button')) {
          openFileDialog();
        }
      },
      onBlur: () => {},
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-media-uploader",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-media-uploader__label",
          children: label
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(MediaUploadComponent, {
          value: value,
          onSelect: onSelect,
          allowedTypes: allowedMediaTypes,
          multiple: multipleSelect,
          render: ({
            open
          }) => buttonText || buttonProps ? /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            variant: "secondary",
            onClick: () => {
              onMediaGalleryOpen();
              open();
            },
            ...buttonProps,
            children: buttonText
          }) : /*#__PURE__*/(0,jsx_runtime.jsx)(react.Fragment, {})
        }), hasDropZone && /*#__PURE__*/(0,jsx_runtime.jsx)(drop_zone_default, {
          onFilesDrop: droppedFiles => uploadMedia({
            allowedTypes: allowedMediaTypes,
            filesList: droppedFiles,
            maxUploadFileSize,
            // Runtime passes UploadError (with code + file), not plain Error.
            onError: onError,
            onFileChange(files) {
              onUpload(multiple ? files : files[0]);
            },
            additionalData
          })
        })]
      })
    })
  });
};
try {
    // @ts-ignore
    MediaUploader.displayName = "MediaUploader";
    // @ts-ignore
    MediaUploader.__docgenInfo = { "description": "", "displayName": "MediaUploader", "props": { "allowedMediaTypes": { "defaultValue": { value: "[ 'image' ]" }, "description": "", "name": "allowedMediaTypes", "required": false, "type": { "name": "string[]" } }, "buttonText": { "defaultValue": { value: "__( 'Choose images', 'woocommerce' )" }, "description": "", "name": "buttonText", "required": false, "type": { "name": "string" } }, "buttonProps": { "defaultValue": null, "description": "", "name": "buttonProps", "required": false, "type": { "name": "((ButtonProps & DeprecatedButtonProps) & RefAttributes<any>)" } }, "hasDropZone": { "defaultValue": { value: "true" }, "description": "", "name": "hasDropZone", "required": false, "type": { "name": "boolean" } }, "icon": { "defaultValue": null, "description": "", "name": "icon", "required": false, "type": { "name": "Element" } }, "label": { "defaultValue": { value: "__( 'Drag images here or click to upload', 'woocommerce' )" }, "description": "", "name": "label", "required": false, "type": { "name": "string | Element" } }, "maxUploadFileSize": { "defaultValue": { value: "10000000" }, "description": "", "name": "maxUploadFileSize", "required": false, "type": { "name": "number" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": false, "type": { "name": "MediaUploadComponentType" } }, "multipleSelect": { "defaultValue": { value: "false" }, "description": "", "name": "multipleSelect", "required": false, "type": { "name": "string | boolean" } }, "value": { "defaultValue": null, "description": "", "name": "value", "required": false, "type": { "name": "number | number[]" } }, "onSelect": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelect", "required": false, "type": { "name": "((value: Attachment[] | ({ id: number; } & { [k: string]: any; })) => void)" } }, "onError": { "defaultValue": { value: "() => null" }, "description": "", "name": "onError", "required": false, "type": { "name": "MediaUploaderErrorCallback" } }, "onMediaGalleryOpen": { "defaultValue": { value: "() => null" }, "description": "", "name": "onMediaGalleryOpen", "required": false, "type": { "name": "(() => void)" } }, "onUpload": { "defaultValue": { value: "() => null" }, "description": "", "name": "onUpload", "required": false, "type": { "name": "((files: Attachment | Attachment[]) => void)" } }, "onFileUploadChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onFileUploadChange", "required": false, "type": { "name": "((files: Attachment | Attachment[]) => void)" } }, "uploadMedia": { "defaultValue": null, "description": "", "name": "uploadMedia", "required": false, "type": { "name": "(({ wpAllowedMimeTypes, allowedTypes, additionalData, filesList, maxUploadFileSize, onError, onFileChange, signal, multiple, }: UploadMediaArgs) => void)" } }, "additionalData": { "defaultValue": null, "description": "", "name": "additionalData", "required": false, "type": { "name": "Record<string, unknown>" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/media-uploader.tsx#MediaUploader"] = { docgenInfo: MediaUploader.__docgenInfo, name: "MediaUploader", path: "../../packages/js/components/src/media-uploader/media-uploader.tsx#MediaUploader" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx
var mock_media_uploader = __webpack_require__("../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx");
;// ../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



const ImageGallery = ({
  images
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    style: {
      marginBottom: '16px'
    },
    children: images.map((image, index) => {
      return /*#__PURE__*/(0,jsx_runtime.jsx)("img", {
        alt: image.alt,
        src: image.url,
        style: {
          maxWidth: '100px',
          marginRight: '16px'
        }
      }, index);
    })
  });
};
const readImage = file => {
  return new Promise(resolve => {
    const fileReader = new FileReader();
    fileReader.onload = function (event) {
      const image = {
        alt: 'Temporary image',
        url: event?.target?.result
      };
      resolve(image);
    };
    fileReader.readAsDataURL(file);
  });
};
const mockUploadMedia = async ({
  filesList,
  onFileChange
}) => {
  // The values sent by the FormFileUpload and the DropZone components are different.
  // This is why we need to transform everything into an array.
  const images = await Promise.all(filesList.map(file => {
    if (typeof file === 'object') {
      return readImage(file);
    }
    return {};
  }));
  onFileChange?.(images);
};
const Basic = () => {
  const [images, setImages] = (0,react.useState)([]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ImageGallery, {
      images: images
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(MediaUploader, {
      MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
      onSelect: file => setImages([...images, file]),
      onError: () => null,
      onFileUploadChange: files => setImages([...images, ...(Array.isArray(files) ? files : [files])]),
      onUpload: files => setImages([...images, ...(Array.isArray(files) ? files : [files])]),
      uploadMedia: mockUploadMedia
    })]
  });
};
const DisabledDropZone = () => {
  const [images, setImages] = (0,react.useState)([]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(ImageGallery, {
      images: images
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(MediaUploader, {
      hasDropZone: false,
      label: 'Click the button below to upload',
      MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
      onFileUploadChange: files => setImages([...images, ...(Array.isArray(files) ? files : [files])]),
      onSelect: file => setImages([...images, file]),
      onError: () => null,
      uploadMedia: mockUploadMedia
    })]
  });
};
const MaxUploadFileSize = () => {
  const [error, setError] = (0,react.useState)(null);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [error && /*#__PURE__*/(0,jsx_runtime.jsx)(notice/* default */.A, {
      isDismissible: false,
      status: 'error',
      children: error
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(MediaUploader, {
      maxUploadFileSize: 1000,
      MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
      onSelect: () => null,
      onError: e => setError(e.message),
      onUpload: () => null
    })]
  });
};
const ButtonWithOnlyIcon = () => {
  const [error, setError] = (0,react.useState)(null);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [error && /*#__PURE__*/(0,jsx_runtime.jsx)(notice/* default */.A, {
      isDismissible: false,
      status: 'error',
      children: error
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(MediaUploader, {
      maxUploadFileSize: 1000,
      buttonProps: {
        icon: cloud_upload_default,
        iconSize: 32,
        variant: 'tertiary',
        'aria-label': 'Upload media'
      },
      buttonText: "",
      MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
      onSelect: () => null,
      onError: e => setError(e.message),
      onUpload: () => null
    })]
  });
};
/* harmony default export */ const media_uploader_story = ({
  title: 'Components/MediaUploader',
  component: Basic
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [images, setImages] = useState<Attachment[]>([]);\n  return <>\n            <ImageGallery images={images} />\n            <MediaUploader MediaUploadComponent={MockMediaUpload} onSelect={file => setImages([...images, file as Attachment])} onError={() => null} onFileUploadChange={files => setImages([...images, ...(Array.isArray(files) ? files : [files])])} onUpload={files => setImages([...images, ...(Array.isArray(files) ? files : [files])])} uploadMedia={mockUploadMedia} />\n        </>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
DisabledDropZone.parameters = {
  ...DisabledDropZone.parameters,
  docs: {
    ...DisabledDropZone.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [images, setImages] = useState<Attachment[]>([]);\n  return <>\n            <ImageGallery images={images} />\n            <MediaUploader hasDropZone={false} label={'Click the button below to upload'} MediaUploadComponent={MockMediaUpload} onFileUploadChange={files => setImages([...images, ...(Array.isArray(files) ? files : [files])])} onSelect={file => setImages([...images, file as Attachment])} onError={() => null} uploadMedia={mockUploadMedia} />\n        </>;\n}",
      ...DisabledDropZone.parameters?.docs?.source
    }
  }
};
MaxUploadFileSize.parameters = {
  ...MaxUploadFileSize.parameters,
  docs: {
    ...MaxUploadFileSize.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [error, setError] = useState<string | null>(null);\n  return <>\n            {error && <Notice isDismissible={false} status={'error'}>\n                    {error}\n                </Notice>}\n\n            <MediaUploader maxUploadFileSize={1000} MediaUploadComponent={MockMediaUpload} onSelect={() => null} onError={e => setError(e.message)} onUpload={() => null} />\n        </>;\n}",
      ...MaxUploadFileSize.parameters?.docs?.source
    }
  }
};
ButtonWithOnlyIcon.parameters = {
  ...ButtonWithOnlyIcon.parameters,
  docs: {
    ...ButtonWithOnlyIcon.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [error, setError] = useState<string | null>(null);\n  return <>\n            {error && <Notice isDismissible={false} status={'error'}>\n                    {error}\n                </Notice>}\n\n            <MediaUploader maxUploadFileSize={1000} buttonProps={{\n      icon: cloudUpload,\n      iconSize: 32,\n      variant: 'tertiary',\n      'aria-label': 'Upload media'\n    }} buttonText=\"\" MediaUploadComponent={MockMediaUpload} onSelect={() => null} onError={e => setError(e.message)} onUpload={() => null} />\n        </>;\n}",
      ...ButtonWithOnlyIcon.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  L: () => (/* reexport */ speak)
});

// UNUSED EXPORTS: setup

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/script/add-container.mjs
// packages/a11y/src/script/add-container.ts
function addContainer(ariaLive = "polite") {
  const container = document.createElement("div");
  container.id = `a11y-speak-${ariaLive}`;
  container.className = "a11y-speak-region";
  container.setAttribute(
    "style",
    "position:absolute;margin:-1px;padding:0;height:1px;width:1px;overflow:hidden;clip-path:inset(50%);border:0;word-wrap:normal !important;word-break:normal !important;"
  );
  container.setAttribute("aria-live", ariaLive);
  container.setAttribute("aria-relevant", "additions text");
  container.setAttribute("aria-atomic", "true");
  const { body } = document;
  if (body) {
    body.appendChild(container);
  }
  return container;
}

//# sourceMappingURL=add-container.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs + 2 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/script/add-intro-text.mjs
// packages/a11y/src/script/add-intro-text.ts

function addIntroText() {
  const introText = document.createElement("p");
  introText.id = "a11y-speak-intro-text";
  introText.className = "a11y-speak-intro-text";
  introText.textContent = (0,i18n_build_module.__)("Notifications");
  introText.setAttribute(
    "style",
    "position:absolute;margin:-1px;padding:0;height:1px;width:1px;overflow:hidden;clip-path:inset(50%);border:0;word-wrap:normal !important;word-break:normal !important;"
  );
  introText.setAttribute("hidden", "");
  const { body } = document;
  if (body) {
    body.appendChild(introText);
  }
  return introText;
}

//# sourceMappingURL=add-intro-text.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/shared/clear.mjs
// packages/a11y/src/shared/clear.ts
function clear() {
  const regions = document.getElementsByClassName("a11y-speak-region");
  const introText = document.getElementById("a11y-speak-intro-text");
  for (let i = 0; i < regions.length; i++) {
    regions[i].textContent = "";
  }
  if (introText) {
    introText.setAttribute("hidden", "hidden");
  }
}

//# sourceMappingURL=clear.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/shared/filter-message.mjs
// packages/a11y/src/shared/filter-message.ts
var previousMessage = "";
function filterMessage(message) {
  message = message.replace(/<[^<>]+>/g, " ");
  if (previousMessage === message) {
    message += "\xA0";
  }
  previousMessage = message;
  return message;
}

//# sourceMappingURL=filter-message.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/shared/index.mjs
// packages/a11y/src/shared/index.ts


function speak(message, ariaLive) {
  clear();
  message = filterMessage(message);
  const introText = document.getElementById("a11y-speak-intro-text");
  const containerAssertive = document.getElementById(
    "a11y-speak-assertive"
  );
  const containerPolite = document.getElementById("a11y-speak-polite");
  if (containerAssertive && ariaLive === "assertive") {
    containerAssertive.textContent = message;
  } else if (containerPolite) {
    containerPolite.textContent = message;
  }
  if (introText) {
    introText.removeAttribute("hidden");
  }
}

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs
// packages/a11y/src/index.ts




function setup() {
  const introText = document.getElementById("a11y-speak-intro-text");
  const containerAssertive = document.getElementById(
    "a11y-speak-assertive"
  );
  const containerPolite = document.getElementById("a11y-speak-polite");
  if (introText === null) {
    addIntroText();
  }
  if (containerAssertive === null) {
    addContainer("assertive");
  }
  if (containerPolite === null) {
    addContainer("polite");
  }
}
(0,build_module/* default */.A)(setup);

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ notice_default)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs
var is_plain_object = __webpack_require__("../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/param-case@3.0.4/node_modules/param-case/dist.es2015/index.js + 1 modules
var dist_es2015 = __webpack_require__("../../node_modules/.pnpm/param-case@3.0.4/node_modules/param-case/dist.es2015/index.js");
;// ../../node_modules/.pnpm/@wordpress+escape-html@3.48.1/node_modules/@wordpress/escape-html/build-module/escape-greater.mjs
// packages/escape-html/src/escape-greater.ts
function __unstableEscapeGreaterThan(value) {
  return value.replace(/>/g, "&gt;");
}

//# sourceMappingURL=escape-greater.mjs.map

;// ../../node_modules/.pnpm/@wordpress+escape-html@3.48.1/node_modules/@wordpress/escape-html/build-module/index.mjs
// packages/escape-html/src/index.ts

var REGEXP_INVALID_ATTRIBUTE_NAME = /[\u007F-\u009F "'>/="\uFDD0-\uFDEF]/;
function escapeAmpersand(value) {
  return value.replace(/&(?!([a-z0-9]+|#[0-9]+|#x[a-f0-9]+);)/gi, "&amp;");
}
function escapeQuotationMark(value) {
  return value.replace(/"/g, "&quot;");
}
function escapeLessThan(value) {
  return value.replace(/</g, "&lt;");
}
function escapeAttribute(value) {
  return __unstableEscapeGreaterThan(
    escapeLessThan(escapeQuotationMark(escapeAmpersand(value)))
  );
}
function escapeHTML(value) {
  return escapeLessThan(escapeAmpersand(value));
}
function escapeEditableHTML(value) {
  return escapeLessThan(value.replace(/&/g, "&amp;"));
}
function isValidAttributeName(name) {
  return !REGEXP_INVALID_ATTRIBUTE_NAME.test(name);
}

//# sourceMappingURL=index.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@wordpress+element@6.45.0/node_modules/@wordpress/element/build-module/raw-html.mjs
// packages/element/src/raw-html.ts

function RawHTML({ children, ...props }) {
  let rawHtml = "";
  react.Children.toArray(children).forEach((child) => {
    if (typeof child === "string" && child.trim() !== "") {
      rawHtml += child;
    }
  });
  return (0,react.createElement)("div", {
    dangerouslySetInnerHTML: { __html: rawHtml },
    ...props
  });
}

//# sourceMappingURL=raw-html.mjs.map

;// ../../node_modules/.pnpm/@wordpress+element@6.45.0/node_modules/@wordpress/element/build-module/serialize.mjs
// packages/element/src/serialize.ts





var Context = (0,react.createContext)(void 0);
Context.displayName = "ElementContext";
var { Provider, Consumer } = Context;
var ForwardRef = (0,react.forwardRef)(() => {
  return null;
});
var ATTRIBUTES_TYPES = /* @__PURE__ */ new Set(["string", "boolean", "number"]);
var SELF_CLOSING_TAGS = /* @__PURE__ */ new Set([
  "area",
  "base",
  "br",
  "col",
  "command",
  "embed",
  "hr",
  "img",
  "input",
  "keygen",
  "link",
  "meta",
  "param",
  "source",
  "track",
  "wbr"
]);
var BOOLEAN_ATTRIBUTES = /* @__PURE__ */ new Set([
  "allowfullscreen",
  "allowpaymentrequest",
  "allowusermedia",
  "async",
  "autofocus",
  "autoplay",
  "checked",
  "controls",
  "default",
  "defer",
  "disabled",
  "download",
  "formnovalidate",
  "hidden",
  "ismap",
  "itemscope",
  "loop",
  "multiple",
  "muted",
  "nomodule",
  "novalidate",
  "open",
  "playsinline",
  "readonly",
  "required",
  "reversed",
  "selected",
  "typemustmatch"
]);
var ENUMERATED_ATTRIBUTES = /* @__PURE__ */ new Set([
  "autocapitalize",
  "autocomplete",
  "charset",
  "contenteditable",
  "crossorigin",
  "decoding",
  "dir",
  "draggable",
  "enctype",
  "formenctype",
  "formmethod",
  "http-equiv",
  "inputmode",
  "kind",
  "method",
  "preload",
  "scope",
  "shape",
  "spellcheck",
  "translate",
  "type",
  "wrap"
]);
var CSS_PROPERTIES_SUPPORTS_UNITLESS = /* @__PURE__ */ new Set([
  "animation",
  "animationIterationCount",
  "baselineShift",
  "borderImageOutset",
  "borderImageSlice",
  "borderImageWidth",
  "columnCount",
  "cx",
  "cy",
  "fillOpacity",
  "flexGrow",
  "flexShrink",
  "floodOpacity",
  "fontWeight",
  "gridColumnEnd",
  "gridColumnStart",
  "gridRowEnd",
  "gridRowStart",
  "lineHeight",
  "opacity",
  "order",
  "orphans",
  "r",
  "rx",
  "ry",
  "shapeImageThreshold",
  "stopOpacity",
  "strokeDasharray",
  "strokeDashoffset",
  "strokeMiterlimit",
  "strokeOpacity",
  "strokeWidth",
  "tabSize",
  "widows",
  "x",
  "y",
  "zIndex",
  "zoom"
]);
function hasPrefix(string, prefixes) {
  return prefixes.some((prefix) => string.indexOf(prefix) === 0);
}
function isInternalAttribute(attribute) {
  return "key" === attribute || "children" === attribute;
}
function getNormalAttributeValue(attribute, value) {
  switch (attribute) {
    case "style":
      return renderStyle(value);
  }
  return value;
}
var SVG_ATTRIBUTE_WITH_DASHES_LIST = [
  "accentHeight",
  "alignmentBaseline",
  "arabicForm",
  "baselineShift",
  "capHeight",
  "clipPath",
  "clipRule",
  "colorInterpolation",
  "colorInterpolationFilters",
  "colorProfile",
  "colorRendering",
  "dominantBaseline",
  "enableBackground",
  "fillOpacity",
  "fillRule",
  "floodColor",
  "floodOpacity",
  "fontFamily",
  "fontSize",
  "fontSizeAdjust",
  "fontStretch",
  "fontStyle",
  "fontVariant",
  "fontWeight",
  "glyphName",
  "glyphOrientationHorizontal",
  "glyphOrientationVertical",
  "horizAdvX",
  "horizOriginX",
  "imageRendering",
  "letterSpacing",
  "lightingColor",
  "markerEnd",
  "markerMid",
  "markerStart",
  "overlinePosition",
  "overlineThickness",
  "paintOrder",
  "panose1",
  "pointerEvents",
  "renderingIntent",
  "shapeRendering",
  "stopColor",
  "stopOpacity",
  "strikethroughPosition",
  "strikethroughThickness",
  "strokeDasharray",
  "strokeDashoffset",
  "strokeLinecap",
  "strokeLinejoin",
  "strokeMiterlimit",
  "strokeOpacity",
  "strokeWidth",
  "textAnchor",
  "textDecoration",
  "textRendering",
  "underlinePosition",
  "underlineThickness",
  "unicodeBidi",
  "unicodeRange",
  "unitsPerEm",
  "vAlphabetic",
  "vHanging",
  "vIdeographic",
  "vMathematical",
  "vectorEffect",
  "vertAdvY",
  "vertOriginX",
  "vertOriginY",
  "wordSpacing",
  "writingMode",
  "xmlnsXlink",
  "xHeight"
].reduce(
  (map, attribute) => {
    map[attribute.toLowerCase()] = attribute;
    return map;
  },
  {}
);
var CASE_SENSITIVE_SVG_ATTRIBUTES = [
  "allowReorder",
  "attributeName",
  "attributeType",
  "autoReverse",
  "baseFrequency",
  "baseProfile",
  "calcMode",
  "clipPathUnits",
  "contentScriptType",
  "contentStyleType",
  "diffuseConstant",
  "edgeMode",
  "externalResourcesRequired",
  "filterRes",
  "filterUnits",
  "glyphRef",
  "gradientTransform",
  "gradientUnits",
  "kernelMatrix",
  "kernelUnitLength",
  "keyPoints",
  "keySplines",
  "keyTimes",
  "lengthAdjust",
  "limitingConeAngle",
  "markerHeight",
  "markerUnits",
  "markerWidth",
  "maskContentUnits",
  "maskUnits",
  "numOctaves",
  "pathLength",
  "patternContentUnits",
  "patternTransform",
  "patternUnits",
  "pointsAtX",
  "pointsAtY",
  "pointsAtZ",
  "preserveAlpha",
  "preserveAspectRatio",
  "primitiveUnits",
  "refX",
  "refY",
  "repeatCount",
  "repeatDur",
  "requiredExtensions",
  "requiredFeatures",
  "specularConstant",
  "specularExponent",
  "spreadMethod",
  "startOffset",
  "stdDeviation",
  "stitchTiles",
  "suppressContentEditableWarning",
  "suppressHydrationWarning",
  "surfaceScale",
  "systemLanguage",
  "tableValues",
  "targetX",
  "targetY",
  "textLength",
  "viewBox",
  "viewTarget",
  "xChannelSelector",
  "yChannelSelector"
].reduce(
  (map, attribute) => {
    map[attribute.toLowerCase()] = attribute;
    return map;
  },
  {}
);
var SVG_ATTRIBUTES_WITH_COLONS = [
  "xlink:actuate",
  "xlink:arcrole",
  "xlink:href",
  "xlink:role",
  "xlink:show",
  "xlink:title",
  "xlink:type",
  "xml:base",
  "xml:lang",
  "xml:space",
  "xmlns:xlink"
].reduce(
  (map, attribute) => {
    map[attribute.replace(":", "").toLowerCase()] = attribute;
    return map;
  },
  {}
);
function getNormalAttributeName(attribute) {
  switch (attribute) {
    case "htmlFor":
      return "for";
    case "className":
      return "class";
  }
  const attributeLowerCase = attribute.toLowerCase();
  if (CASE_SENSITIVE_SVG_ATTRIBUTES[attributeLowerCase]) {
    return CASE_SENSITIVE_SVG_ATTRIBUTES[attributeLowerCase];
  } else if (SVG_ATTRIBUTE_WITH_DASHES_LIST[attributeLowerCase]) {
    return (0,dist_es2015/* paramCase */.c)(
      SVG_ATTRIBUTE_WITH_DASHES_LIST[attributeLowerCase]
    );
  } else if (SVG_ATTRIBUTES_WITH_COLONS[attributeLowerCase]) {
    return SVG_ATTRIBUTES_WITH_COLONS[attributeLowerCase];
  }
  return attributeLowerCase;
}
function getNormalStylePropertyName(property) {
  if (property.startsWith("--")) {
    return property;
  }
  if (hasPrefix(property, ["ms", "O", "Moz", "Webkit"])) {
    return "-" + (0,dist_es2015/* paramCase */.c)(property);
  }
  return (0,dist_es2015/* paramCase */.c)(property);
}
function getNormalStylePropertyValue(property, value) {
  if (typeof value === "number" && 0 !== value && !hasPrefix(property, ["--"]) && !CSS_PROPERTIES_SUPPORTS_UNITLESS.has(property)) {
    return value + "px";
  }
  return value;
}
function renderElement(element, context, legacyContext = {}) {
  if (null === element || void 0 === element || false === element) {
    return "";
  }
  if (Array.isArray(element)) {
    return renderChildren(element, context, legacyContext);
  }
  switch (typeof element) {
    case "string":
      return escapeHTML(element);
    case "number":
      return element.toString();
  }
  const { type, props } = element;
  switch (type) {
    case react.StrictMode:
    case react.Fragment:
      return renderChildren(props.children, context, legacyContext);
    case RawHTML:
      const { children, ...wrapperProps } = props;
      return renderNativeComponent(
        !Object.keys(wrapperProps).length ? null : "div",
        {
          ...wrapperProps,
          dangerouslySetInnerHTML: { __html: children }
        },
        context,
        legacyContext
      );
  }
  switch (typeof type) {
    case "string":
      return renderNativeComponent(type, props, context, legacyContext);
    case "function":
      if (type.prototype && typeof type.prototype.render === "function") {
        return renderComponent(type, props, context, legacyContext);
      }
      return renderElement(
        type(props, legacyContext),
        context,
        legacyContext
      );
  }
  switch (type && type.$$typeof) {
    case Provider.$$typeof:
      return renderChildren(props.children, props.value, legacyContext);
    case Consumer.$$typeof:
      return renderElement(
        props.children(context || type._currentValue),
        context,
        legacyContext
      );
    case ForwardRef.$$typeof:
      return renderElement(
        type.render(props),
        context,
        legacyContext
      );
  }
  return "";
}
function renderNativeComponent(type, props, context, legacyContext = {}) {
  let content = "";
  if (type === "textarea" && props.hasOwnProperty("value")) {
    content = renderChildren(props.value, context, legacyContext);
    const { value, ...restProps } = props;
    props = restProps;
  } else if (props.dangerouslySetInnerHTML && typeof props.dangerouslySetInnerHTML.__html === "string") {
    content = props.dangerouslySetInnerHTML.__html;
  } else if (typeof props.children !== "undefined") {
    content = renderChildren(props.children, context, legacyContext);
  }
  if (!type) {
    return content;
  }
  const attributes = renderAttributes(props);
  if (SELF_CLOSING_TAGS.has(type)) {
    return "<" + type + attributes + "/>";
  }
  return "<" + type + attributes + ">" + content + "</" + type + ">";
}
function renderComponent(Component, props, context, legacyContext = {}) {
  const instance = new Component(props, legacyContext);
  if (typeof instance.getChildContext === "function") {
    Object.assign(legacyContext, instance.getChildContext());
  }
  const html = renderElement(instance.render(), context, legacyContext);
  return html;
}
function renderChildren(children, context, legacyContext = {}) {
  let result = "";
  const childrenArray = Array.isArray(children) ? children : [children];
  for (let i = 0; i < childrenArray.length; i++) {
    const child = childrenArray[i];
    result += renderElement(child, context, legacyContext);
  }
  return result;
}
function renderAttributes(props) {
  let result = "";
  for (const key in props) {
    const attribute = getNormalAttributeName(key);
    if (!isValidAttributeName(attribute)) {
      continue;
    }
    let value = getNormalAttributeValue(key, props[key]);
    if (!ATTRIBUTES_TYPES.has(typeof value)) {
      continue;
    }
    if (isInternalAttribute(key)) {
      continue;
    }
    const isBooleanAttribute = BOOLEAN_ATTRIBUTES.has(attribute);
    if (isBooleanAttribute && value === false) {
      continue;
    }
    const isMeaningfulAttribute = isBooleanAttribute || hasPrefix(key, ["data-", "aria-"]) || ENUMERATED_ATTRIBUTES.has(attribute);
    if (typeof value === "boolean" && !isMeaningfulAttribute) {
      continue;
    }
    result += " " + attribute;
    if (isBooleanAttribute) {
      continue;
    }
    if (typeof value === "string") {
      value = escapeAttribute(value);
    }
    result += '="' + value + '"';
  }
  return result;
}
function renderStyle(style) {
  if (!(0,is_plain_object/* isPlainObject */.Q)(style)) {
    return style;
  }
  let result;
  const styleObj = style;
  for (const property in styleObj) {
    const value = styleObj[property];
    if (null === value || void 0 === value) {
      continue;
    }
    if (result) {
      result += ";";
    } else {
      result = "";
    }
    const normalName = getNormalStylePropertyName(property);
    const normalValue = getNormalStylePropertyValue(property, value);
    result += normalName + ":" + normalValue;
  }
  return result;
}
var serialize_default = renderElement;

//# sourceMappingURL=serialize.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs + 5 modules
var a11y_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close.mjs
var library_close = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js + 1 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js








const noop = () => {
};
function useSpokenMessage(message, politeness) {
  const spokenMessage = typeof message === "string" ? message : serialize_default(message);
  (0,react.useEffect)(() => {
    if (spokenMessage) {
      (0,a11y_build_module/* speak */.L)(spokenMessage, politeness);
    }
  }, [spokenMessage, politeness]);
}
function getDefaultPoliteness(status) {
  switch (status) {
    case "success":
    case "warning":
    case "info":
      return "polite";
    // The default will also catch the 'error' status.
    default:
      return "assertive";
  }
}
function getStatusLabel(status) {
  switch (status) {
    case "warning":
      return (0,build_module.__)("Warning notice");
    case "info":
      return (0,build_module.__)("Information notice");
    case "error":
      return (0,build_module.__)("Error notice");
    // The default will also catch the 'success' status.
    default:
      return (0,build_module.__)("Notice");
  }
}
function Notice({
  className,
  status = "info",
  children,
  spokenMessage = children,
  onRemove = noop,
  isDismissible = true,
  actions = [],
  politeness = getDefaultPoliteness(status),
  __unstableHTML,
  // onDismiss is a callback executed when the notice is dismissed.
  // It is distinct from onRemove, which _looks_ like a callback but is
  // actually the function to call to remove the notice from the UI.
  onDismiss = noop
}) {
  useSpokenMessage(spokenMessage, politeness);
  const classes = (0,clsx/* default */.A)(className, "components-notice", "is-" + status, {
    "is-dismissible": isDismissible
  });
  if (__unstableHTML && typeof children === "string") {
    children = /* @__PURE__ */ (0,jsx_runtime.jsx)(RawHTML, {
      children
    });
  }
  const onDismissNotice = () => {
    onDismiss();
    onRemove();
  };
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
    className: classes,
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
      children: getStatusLabel(status)
    }), /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
      className: "components-notice__content",
      children: [children, /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
        className: "components-notice__actions",
        children: actions.map(({
          className: buttonCustomClasses,
          label,
          isPrimary,
          variant,
          noDefaultClasses = false,
          onClick,
          url
        }, index) => {
          let computedVariant = variant;
          if (variant !== "primary" && !noDefaultClasses) {
            computedVariant = !url ? "secondary" : "link";
          }
          if (typeof computedVariant === "undefined" && isPrimary) {
            computedVariant = "primary";
          }
          return /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            __next40pxDefaultSize: true,
            href: url,
            variant: computedVariant,
            onClick: url ? void 0 : onClick,
            className: (0,clsx/* default */.A)("components-notice__action", buttonCustomClasses),
            children: label
          }, index);
        })
      })]
    }), isDismissible && /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      size: "small",
      className: "components-notice__dismiss",
      icon: library_close/* default */.A,
      label: (0,build_module.__)("Close"),
      onClick: onDismissNotice
    })]
  });
}
var notice_default = Notice;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ config_values_default)
/* harmony export */ });
/* harmony import */ var _space__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
/* harmony import */ var _colors_values__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");


const CONTROL_HEIGHT = "36px";
const CONTROL_PROPS = {
  // These values should be shared with TextControl.
  controlPaddingX: 12,
  controlPaddingXSmall: 8,
  controlPaddingXLarge: 12 * 1.3334,
  // TODO: Deprecate
  controlBoxShadowFocus: `0 0 0 0.5px ${_colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.theme.accent}`,
  controlHeight: CONTROL_HEIGHT,
  controlHeightXSmall: `calc( ${CONTROL_HEIGHT} * 0.6 )`,
  controlHeightSmall: `calc( ${CONTROL_HEIGHT} * 0.8 )`,
  controlHeightLarge: `calc( ${CONTROL_HEIGHT} * 1.2 )`,
  controlHeightXLarge: `calc( ${CONTROL_HEIGHT} * 1.4 )`
};
var config_values_default = Object.assign({}, CONTROL_PROPS, {
  colorDivider: "rgba(0, 0, 0, 0.1)",
  colorScrollbarThumb: "rgba(0, 0, 0, 0.2)",
  colorScrollbarThumbHover: "rgba(0, 0, 0, 0.5)",
  colorScrollbarTrack: "rgba(0, 0, 0, 0.04)",
  elevationIntensity: 1,
  radiusXSmall: "1px",
  radiusSmall: "2px",
  radiusMedium: "4px",
  radiusLarge: "8px",
  radiusFull: "9999px",
  radiusRound: "50%",
  borderWidth: "1px",
  borderWidthFocus: "1.5px",
  borderWidthTab: "4px",
  spinnerSize: 16,
  fontSize: "13px",
  fontSizeH1: "calc(2.44 * 13px)",
  fontSizeH2: "calc(1.95 * 13px)",
  fontSizeH3: "calc(1.56 * 13px)",
  fontSizeH4: "calc(1.25 * 13px)",
  fontSizeH5: "13px",
  fontSizeH6: "calc(0.8 * 13px)",
  fontSizeInputMobile: "16px",
  fontSizeMobile: "15px",
  fontSizeSmall: "calc(0.92 * 13px)",
  fontSizeXSmall: "calc(0.75 * 13px)",
  fontLineHeightBase: "1.4",
  fontWeight: "normal",
  fontWeightHeading: "600",
  gridBase: "4px",
  cardPaddingXSmall: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(2)}`,
  cardPaddingSmall: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)}`,
  cardPaddingMedium: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)} ${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)}`,
  cardPaddingLarge: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)} ${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(8)}`,
  elevationXSmall: `0 1px 1px rgba(0, 0, 0, 0.03), 0 1px 2px rgba(0, 0, 0, 0.02), 0 3px 3px rgba(0, 0, 0, 0.02), 0 4px 4px rgba(0, 0, 0, 0.01)`,
  elevationSmall: `0 1px 2px rgba(0, 0, 0, 0.05), 0 2px 3px rgba(0, 0, 0, 0.04), 0 6px 6px rgba(0, 0, 0, 0.03), 0 8px 8px rgba(0, 0, 0, 0.02)`,
  elevationMedium: `0 2px 3px rgba(0, 0, 0, 0.05), 0 4px 5px rgba(0, 0, 0, 0.04), 0 12px 12px rgba(0, 0, 0, 0.03), 0 16px 16px rgba(0, 0, 0, 0.02)`,
  elevationLarge: `0 5px 15px rgba(0, 0, 0, 0.08), 0 15px 27px rgba(0, 0, 0, 0.07), 0 30px 36px rgba(0, 0, 0, 0.04), 0 50px 43px rgba(0, 0, 0, 0.02)`,
  surfaceBackgroundColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.white,
  surfaceBackgroundSubtleColor: "#F3F3F3",
  surfaceBackgroundTintColor: "#F5F5F5",
  surfaceBorderColor: "rgba(0, 0, 0, 0.1)",
  surfaceBorderBoldColor: "rgba(0, 0, 0, 0.15)",
  surfaceBorderSubtleColor: "rgba(0, 0, 0, 0.05)",
  surfaceBackgroundTertiaryColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.white,
  surfaceColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.white,
  transitionDuration: "200ms",
  transitionDurationFast: "160ms",
  transitionDurationFaster: "120ms",
  transitionDurationFastest: "100ms",
  transitionTimingFunction: "cubic-bezier(0.08, 0.52, 0.52, 1)",
  transitionTimingFunctionControl: "cubic-bezier(0.12, 0.8, 0.32, 1)"
});

//# sourceMappingURL=config-values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/deprecated-36px-size.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   M: () => (/* binding */ maybeWarnDeprecated36pxSize)
/* harmony export */ });
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");

function maybeWarnDeprecated36pxSize({
  componentName,
  __next40pxDefaultSize,
  size,
  __shouldNotWarnDeprecated36pxSize
}) {
  if (__shouldNotWarnDeprecated36pxSize || __next40pxDefaultSize || size !== void 0 && size !== "default") {
    return;
  }
  (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(`36px default size for wp.components.${componentName}`, {
    since: "6.8",
    version: "7.1",
    hint: "Set the `__next40pxDefaultSize` prop to true to start opting into the new default size, which will become the default in a future version."
  });
}

//# sourceMappingURL=deprecated-36px-size.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-event/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useEvent)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-event/index.ts

function useEvent(callback) {
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(() => {
    throw new Error(
      "Callbacks created with `useEvent` cannot be called during rendering."
    );
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useInsertionEffect)(() => {
    ref.current = callback;
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(
    (...args) => ref.current?.(...args),
    []
  );
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/icons/src/icon/index.ts

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

//# sourceMappingURL=index.mjs.map


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

/***/ })

}]);