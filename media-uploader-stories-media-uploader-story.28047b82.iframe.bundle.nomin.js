"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1190],{

/***/ "../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   I: () => (/* binding */ MockMediaUpload)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/modal/index.js");




/**
 * External dependencies
 */



var MockMediaUpload = function MockMediaUpload(_ref) {
  var onSelect = _ref.onSelect,
    render = _ref.render;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(_useState, 2),
    isOpen = _useState2[0],
    setOpen = _useState2[1];
  return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(react__WEBPACK_IMPORTED_MODULE_1__.Fragment, null, render({
    open: function open() {
      return setOpen(true);
    }
  }), isOpen && (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
    title: "Media Modal",
    onRequestClose: function onRequestClose(event) {
      setOpen(false);
      event.stopPropagation();
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("p", null, "Use the default built-in", ' ', (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("code", null, "MediaUploadComponent"), " prop to render the WP Media Modal."), Array.apply(void 0, (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(Array(3))).map(function (n, i) {
    return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("button", {
      key: i,
      onClick: function onClick(event) {
        onSelect({
          alt: 'Random',
          url: "https://picsum.photos/200?i=".concat(i)
        });
        setOpen(false);
        event.stopPropagation();
      },
      style: {
        marginRight: '16px'
      }
    }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("img", {
      src: "https://picsum.photos/200?i=".concat(i),
      alt: "Random",
      style: {
        maxWidth: '100px'
      }
    }));
  })));
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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js");
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);
// EXTERNAL MODULE: ../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js
var runtime = __webpack_require__("../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/notice/index.js + 2 modules
var notice = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/notice/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/cloud-upload.js
var cloud_upload = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/cloud-upload.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/form-file-upload/index.js
var form_file_upload = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/form-file-upload/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/drop-zone/index.js + 8 modules
var drop_zone = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/drop-zone/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+media-utils@3.4.1/node_modules/@wordpress/media-utils/build-module/index.js + 5 modules
var media_utils_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+media-utils@3.4.1/node_modules/@wordpress/media-utils/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/media-uploader/media-uploader.tsx





/**
 * External dependencies
 */





/**
 * Internal dependencies
 */

var DEFAULT_ALLOWED_MEDIA_TYPES = ['image'];
var MediaUploader = function MediaUploader(_ref) {
  var _ref$allowedMediaType = _ref.allowedMediaTypes,
    allowedMediaTypes = _ref$allowedMediaType === void 0 ? DEFAULT_ALLOWED_MEDIA_TYPES : _ref$allowedMediaType,
    _ref$buttonText = _ref.buttonText,
    buttonText = _ref$buttonText === void 0 ? (0,build_module.__)('Choose images', 'woocommerce') : _ref$buttonText,
    buttonProps = _ref.buttonProps,
    _ref$hasDropZone = _ref.hasDropZone,
    hasDropZone = _ref$hasDropZone === void 0 ? true : _ref$hasDropZone,
    _ref$label = _ref.label,
    label = _ref$label === void 0 ? (0,build_module.__)('Drag images here or click to upload', 'woocommerce') : _ref$label,
    _ref$maxUploadFileSiz = _ref.maxUploadFileSize,
    maxUploadFileSize = _ref$maxUploadFileSiz === void 0 ? 10000000 : _ref$maxUploadFileSiz,
    _ref$MediaUploadCompo = _ref.MediaUploadComponent,
    MediaUploadComponent = _ref$MediaUploadCompo === void 0 ? media_utils_build_module/* MediaUpload */.Q : _ref$MediaUploadCompo,
    _ref$multipleSelect = _ref.multipleSelect,
    multipleSelect = _ref$multipleSelect === void 0 ? false : _ref$multipleSelect,
    value = _ref.value,
    _ref$onError = _ref.onError,
    onError = _ref$onError === void 0 ? function () {
      return null;
    } : _ref$onError,
    _ref$onFileUploadChan = _ref.onFileUploadChange,
    onFileUploadChange = _ref$onFileUploadChan === void 0 ? function () {
      return null;
    } : _ref$onFileUploadChan,
    _ref$onMediaGalleryOp = _ref.onMediaGalleryOpen,
    onMediaGalleryOpen = _ref$onMediaGalleryOp === void 0 ? function () {
      return null;
    } : _ref$onMediaGalleryOp,
    _ref$onUpload = _ref.onUpload,
    onUpload = _ref$onUpload === void 0 ? function () {
      return null;
    } : _ref$onUpload,
    _ref$onSelect = _ref.onSelect,
    onSelect = _ref$onSelect === void 0 ? function () {
      return null;
    } : _ref$onSelect,
    _ref$uploadMedia = _ref.uploadMedia,
    uploadMedia = _ref$uploadMedia === void 0 ? media_utils_build_module/* uploadMedia */.o : _ref$uploadMedia,
    additionalData = _ref.additionalData;
  var multiple = Boolean(multipleSelect);
  return (0,react.createElement)(form_file_upload/* default */.A, {
    accept: allowedMediaTypes.toString(),
    multiple: multiple,
    onChange: function onChange(_ref2) {
      var currentTarget = _ref2.currentTarget;
      uploadMedia({
        allowedTypes: allowedMediaTypes,
        filesList: currentTarget.files,
        maxUploadFileSize: maxUploadFileSize,
        onError: onError,
        onFileChange: function onFileChange(files) {
          onFileUploadChange(multiple ? files : files[0]);
        },
        additionalData: additionalData
      });
    },
    render: function render(_ref3) {
      var openFileDialog = _ref3.openFileDialog;
      return (0,react.createElement)("div", {
        className: "woocommerce-form-file-upload",
        onKeyPress: function onKeyPress() {},
        tabIndex: 0,
        role: "button",
        onClick: function onClick(event) {
          var target = event.target;
          // is the click on the button from MediaUploadComponent or on the div?
          if (!target.closest('button')) {
            openFileDialog();
          }
        },
        onBlur: function onBlur() {}
      }, (0,react.createElement)("div", {
        className: "woocommerce-media-uploader"
      }, (0,react.createElement)("div", {
        className: "woocommerce-media-uploader__label"
      }, label), (0,react.createElement)(MediaUploadComponent, {
        value: value,
        onSelect: onSelect,
        allowedTypes: allowedMediaTypes
        // @ts-expect-error - TODO multiple also accepts string.
        ,

        multiple: multipleSelect,
        render: function render(_ref4) {
          var open = _ref4.open;
          return buttonText || buttonProps ? (0,react.createElement)(build_module_button/* default */.A, (0,esm_extends/* default */.A)({
            variant: "secondary",
            onClick: function onClick() {
              onMediaGalleryOpen();
              open();
            }
          }, buttonProps), buttonText) : (0,react.createElement)(react.Fragment, null);
        }
      }), hasDropZone && (0,react.createElement)(drop_zone/* default */.A, {
        onFilesDrop: function onFilesDrop(droppedFiles) {
          return uploadMedia({
            allowedTypes: allowedMediaTypes,
            filesList: droppedFiles,
            maxUploadFileSize: maxUploadFileSize,
            onError: onError,
            onFileChange: function onFileChange(files) {
              onUpload(multiple ? files : files[0]);
            },
            additionalData: additionalData
          });
        }
      })));
    }
  });
};
try {
    // @ts-ignore
    MediaUploader.displayName = "MediaUploader";
    // @ts-ignore
    MediaUploader.__docgenInfo = { "description": "", "displayName": "MediaUploader", "props": { "allowedMediaTypes": { "defaultValue": { value: "[ 'image' ]" }, "description": "", "name": "allowedMediaTypes", "required": false, "type": { "name": "string[]" } }, "buttonText": { "defaultValue": { value: "__( 'Choose images', 'woocommerce' )" }, "description": "", "name": "buttonText", "required": false, "type": { "name": "string" } }, "buttonProps": { "defaultValue": null, "description": "", "name": "buttonProps", "required": false, "type": { "name": "Props" } }, "hasDropZone": { "defaultValue": { value: "true" }, "description": "", "name": "hasDropZone", "required": false, "type": { "name": "boolean" } }, "icon": { "defaultValue": null, "description": "", "name": "icon", "required": false, "type": { "name": "Element" } }, "label": { "defaultValue": { value: "__( 'Drag images here or click to upload', 'woocommerce' )" }, "description": "", "name": "label", "required": false, "type": { "name": "string | Element" } }, "maxUploadFileSize": { "defaultValue": { value: "10000000" }, "description": "", "name": "maxUploadFileSize", "required": false, "type": { "name": "number" } }, "MediaUploadComponent": { "defaultValue": null, "description": "", "name": "MediaUploadComponent", "required": false, "type": { "name": "(<T extends boolean = false>(props: Props<T>) => Element)" } }, "multipleSelect": { "defaultValue": { value: "false" }, "description": "", "name": "multipleSelect", "required": false, "type": { "name": "string | boolean" } }, "value": { "defaultValue": null, "description": "", "name": "value", "required": false, "type": { "name": "number | number[]" } }, "onSelect": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelect", "required": false, "type": { "name": "((value: MediaItem[] | ({ id: number; } & { [k: string]: any; })) => void)" } }, "onError": { "defaultValue": { value: "() => null" }, "description": "", "name": "onError", "required": false, "type": { "name": "MediaUploaderErrorCallback" } }, "onMediaGalleryOpen": { "defaultValue": { value: "() => null" }, "description": "", "name": "onMediaGalleryOpen", "required": false, "type": { "name": "(() => void)" } }, "onUpload": { "defaultValue": { value: "() => null" }, "description": "", "name": "onUpload", "required": false, "type": { "name": "((files: MediaItem | MediaItem[]) => void)" } }, "onFileUploadChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onFileUploadChange", "required": false, "type": { "name": "((files: MediaItem | MediaItem[]) => void)" } }, "uploadMedia": { "defaultValue": null, "description": "", "name": "uploadMedia", "required": false, "type": { "name": "((options: UploadMediaOptions) => Promise<void>)" } }, "additionalData": { "defaultValue": null, "description": "", "name": "additionalData", "required": false, "type": { "name": "Record<string, unknown>" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/media-uploader.tsx#MediaUploader"] = { docgenInfo: MediaUploader.__docgenInfo, name: "MediaUploader", path: "../../packages/js/components/src/media-uploader/media-uploader.tsx#MediaUploader" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx
var mock_media_uploader = __webpack_require__("../../packages/js/components/src/media-uploader/stories/mock-media-uploader.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx















/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


var ImageGallery = function ImageGallery(_ref) {
  var images = _ref.images;
  return (0,react.createElement)("div", {
    style: {
      marginBottom: '16px'
    }
  }, images.map(function (image, index) {
    return (0,react.createElement)("img", {
      key: index,
      alt: image.alt,
      src: image.url,
      style: {
        maxWidth: '100px',
        marginRight: '16px'
      }
    });
  }));
};
var readImage = function readImage(file) {
  return new Promise(function (resolve) {
    var fileReader = new FileReader();
    fileReader.onload = function (event) {
      var _event$target;
      var image = {
        alt: 'Temporary image',
        url: event === null || event === void 0 || (_event$target = event.target) === null || _event$target === void 0 ? void 0 : _event$target.result
      };
      resolve(image);
    };
    fileReader.readAsDataURL(file);
  });
};
var mockUploadMedia = /*#__PURE__*/function () {
  var _ref3 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee(_ref2) {
    var filesList, onFileChange, list, images;
    return regenerator_default().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          filesList = _ref2.filesList, onFileChange = _ref2.onFileChange;
          _context.next = 3;
          return Object.keys(filesList).map(function (key) {
            return filesList[key];
          });
        case 3:
          list = _context.sent;
          _context.next = 6;
          return Promise.all(list.map(function (file) {
            if ((0,esm_typeof/* default */.A)(file) === 'object') {
              return readImage(file);
            }
            return {};
          }));
        case 6:
          images = _context.sent;
          onFileChange(images);
        case 8:
        case "end":
          return _context.stop();
      }
    }, _callee);
  }));
  return function mockUploadMedia(_x) {
    return _ref3.apply(this, arguments);
  };
}();
var Basic = function Basic() {
  var _useState = (0,react.useState)([]),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    images = _useState2[0],
    setImages = _useState2[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(ImageGallery, {
    images: images
  }), (0,react.createElement)(MediaUploader, {
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    onSelect: function onSelect(file) {
      return setImages([].concat((0,toConsumableArray/* default */.A)(images), [file]));
    },
    onError: function onError() {
      return null;
    },
    onFileUploadChange: function onFileUploadChange(files) {
      return setImages([].concat((0,toConsumableArray/* default */.A)(images), (0,toConsumableArray/* default */.A)(files)));
    },
    onUpload: function onUpload(files) {
      return setImages([].concat((0,toConsumableArray/* default */.A)(images), (0,toConsumableArray/* default */.A)(files)));
    },
    uploadMedia: mockUploadMedia
  }));
};
var DisabledDropZone = function DisabledDropZone() {
  var _useState3 = (0,react.useState)([]),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    images = _useState4[0],
    setImages = _useState4[1];
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(ImageGallery, {
    images: images
  }), (0,react.createElement)(MediaUploader, {
    hasDropZone: false,
    label: 'Click the button below to upload',
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    onFileUploadChange: function onFileUploadChange(files) {
      return setImages([].concat((0,toConsumableArray/* default */.A)(images), (0,toConsumableArray/* default */.A)(files)));
    },
    onSelect: function onSelect(file) {
      return setImages([].concat((0,toConsumableArray/* default */.A)(images), [file]));
    },
    onError: function onError() {
      return null;
    },
    uploadMedia: mockUploadMedia
  }));
};
var MaxUploadFileSize = function MaxUploadFileSize() {
  var _useState5 = (0,react.useState)(null),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    error = _useState6[0],
    setError = _useState6[1];
  return (0,react.createElement)(react.Fragment, null, error && (0,react.createElement)(notice/* default */.A, {
    isDismissible: false,
    status: 'error'
  }, error), (0,react.createElement)(MediaUploader, {
    maxUploadFileSize: 1000,
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    onSelect: function onSelect() {
      return null;
    },
    onError: function onError(e) {
      return setError(e.message);
    },
    onUpload: function onUpload() {
      return null;
    }
  }));
};
var ButtonWithOnlyIcon = function ButtonWithOnlyIcon() {
  var _useState7 = (0,react.useState)(null),
    _useState8 = (0,slicedToArray/* default */.A)(_useState7, 2),
    error = _useState8[0],
    setError = _useState8[1];
  return (0,react.createElement)(react.Fragment, null, error && (0,react.createElement)(notice/* default */.A, {
    isDismissible: false,
    status: 'error'
  }, error), (0,react.createElement)(MediaUploader, {
    maxUploadFileSize: 1000,
    buttonProps: {
      icon: cloud_upload/* default */.A,
      iconSize: 32,
      variant: 'tertiary',
      'aria-label': 'Upload media'
    },
    buttonText: "",
    MediaUploadComponent: mock_media_uploader/* MockMediaUpload */.I,
    onSelect: function onSelect() {
      return null;
    },
    onError: function onError(e) {
      return setError(e.message);
    },
    onUpload: function onUpload() {
      return null;
    }
  }));
};
/* harmony default export */ const media_uploader_story = ({
  title: 'WooCommerce Admin/components/MediaUploader',
  component: Basic
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [images, setImages] = useState<File[]>([]);\n  return <>\n            <ImageGallery images={images} />\n            <MediaUploader MediaUploadComponent={MockMediaUpload} onSelect={file => setImages([...images, file])} onError={() => null} onFileUploadChange={files => setImages([...images, ...files])} onUpload={files => setImages([...images, ...files])} uploadMedia={mockUploadMedia} />\n        </>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
DisabledDropZone.parameters = {
  ...DisabledDropZone.parameters,
  docs: {
    ...DisabledDropZone.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [images, setImages] = useState<File[]>([]);\n  return <>\n            <ImageGallery images={images} />\n            <MediaUploader hasDropZone={false} label={'Click the button below to upload'} MediaUploadComponent={MockMediaUpload} onFileUploadChange={files => setImages([...images, ...files])} onSelect={file => setImages([...images, file])} onError={() => null} uploadMedia={mockUploadMedia} />\n        </>;\n}",
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
try {
    // @ts-ignore
    Basic.displayName = "Basic";
    // @ts-ignore
    Basic.__docgenInfo = { "description": "", "displayName": "Basic", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#Basic"] = { docgenInfo: Basic.__docgenInfo, name: "Basic", path: "../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#Basic" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    DisabledDropZone.displayName = "DisabledDropZone";
    // @ts-ignore
    DisabledDropZone.__docgenInfo = { "description": "", "displayName": "DisabledDropZone", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#DisabledDropZone"] = { docgenInfo: DisabledDropZone.__docgenInfo, name: "DisabledDropZone", path: "../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#DisabledDropZone" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    MaxUploadFileSize.displayName = "MaxUploadFileSize";
    // @ts-ignore
    MaxUploadFileSize.__docgenInfo = { "description": "", "displayName": "MaxUploadFileSize", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#MaxUploadFileSize"] = { docgenInfo: MaxUploadFileSize.__docgenInfo, name: "MaxUploadFileSize", path: "../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#MaxUploadFileSize" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ButtonWithOnlyIcon.displayName = "ButtonWithOnlyIcon";
    // @ts-ignore
    ButtonWithOnlyIcon.__docgenInfo = { "description": "", "displayName": "ButtonWithOnlyIcon", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#ButtonWithOnlyIcon"] = { docgenInfo: ButtonWithOnlyIcon.__docgenInfo, name: "ButtonWithOnlyIcon", path: "../../packages/js/components/src/media-uploader/stories/media-uploader.story.tsx#ButtonWithOnlyIcon" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);