"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1406],{

/***/ "../../packages/js/components/src/image-upload/stories/image-upload.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ image_upload_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/upload.js


var upload_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M18.5 15v3.5H13V6.7l4.5 4.1 1-1.1-6.2-5.8-5.8 5.8 1 1.1 4-4v11.7h-6V15H4v5h16v-5z" }) });

//# sourceMappingURL=upload.js.map

;// ../../packages/js/components/src/image-upload/index.js
/**
 * External dependencies
 */






class ImageUpload extends react.Component {
  constructor() {
    super(...arguments);
    this.state = {
      frame: false
    };
    this.openModal = this.openModal.bind(this);
    this.handleImageSelect = this.handleImageSelect.bind(this);
    this.removeImage = this.removeImage.bind(this);
  }
  openModal() {
    if (this.state.frame) {
      this.state.frame.open();
      return;
    }
    const frame = wp.media({
      title: (0,build_module.__)('Select or upload image', 'woocommerce'),
      button: {
        text: (0,build_module.__)('Select', 'woocommerce')
      },
      library: {
        type: 'image'
      },
      multiple: false
    });
    frame.on('select', this.handleImageSelect);
    frame.open();
    this.setState({
      frame
    });
  }
  handleImageSelect() {
    const {
      onChange
    } = this.props;
    const attachment = this.state.frame.state().get('selection').first().toJSON();
    onChange(attachment);
  }
  removeImage() {
    const {
      onChange
    } = this.props;
    onChange(null);
  }
  render() {
    const {
      className,
      image
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [!!image && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: (0,clsx/* default */.A)('woocommerce-image-upload', 'has-image', className),
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-image-upload__image-preview",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)("img", {
            src: image.url,
            alt: ""
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          isSecondary: true,
          className: "woocommerce-image-upload__remove-image",
          onClick: this.removeImage,
          children: (0,build_module.__)('Remove image', 'woocommerce')
        })]
      }), !image && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-image-upload', 'no-image', className),
        children: /*#__PURE__*/(0,jsx_runtime.jsxs)(build_module_button/* default */.Ay, {
          className: "woocommerce-image-upload__add-image",
          onClick: this.openModal,
          isSecondary: true,
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
            icon: upload_default
          }), (0,build_module.__)('Add an image', 'woocommerce')]
        })
      })]
    });
  }
}
/* harmony default export */ const image_upload = (ImageUpload);
;
ImageUpload.__docgenInfo = {
  "description": "",
  "methods": [{
    "name": "openModal",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "handleImageSelect",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "removeImage",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "ImageUpload"
};
;// ../../packages/js/components/src/image-upload/stories/image-upload.story.js
/**
 * External dependencies
 */



const ImageUploadExample = () => {
  const [image, setImage] = (0,react.useState)(null);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(image_upload, {
    image: image,
    onChange: _image => setImage(_image)
  });
};
const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(ImageUploadExample, {});
/* harmony default export */ const image_upload_story = ({
  title: 'Components/ImageUpload',
  component: image_upload
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <ImageUploadExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

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

//# sourceMappingURL=index.js.map


/***/ })

}]);