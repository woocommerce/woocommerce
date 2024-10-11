"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6024],{

/***/ "../../plugins/woocommerce-admin/client/customize-store/design-with-ai/stories/BusinessInfoDescription.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   BusinessInfoDescriptionPage: () => (/* binding */ BusinessInfoDescriptionPage),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _pages__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../plugins/woocommerce-admin/client/customize-store/design-with-ai/pages/index.tsx");
/* harmony import */ var _WithCustomizeYourStoreLayout__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../plugins/woocommerce-admin/client/customize-store/design-with-ai/stories/WithCustomizeYourStoreLayout.tsx");

/**
 * Internal dependencies
 */



var BusinessInfoDescriptionPage = function BusinessInfoDescriptionPage() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_pages__WEBPACK_IMPORTED_MODULE_0__/* .BusinessInfoDescription */ .Zr, {
    context: {
      businessInfoDescription: {
        descriptionText: ''
      }
    },
    sendEvent: function sendEvent() {}
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/Application/Customize Store/Design with AI/Business Info Description',
  component: _pages__WEBPACK_IMPORTED_MODULE_0__/* .BusinessInfoDescription */ .Zr,
  decorators: [_WithCustomizeYourStoreLayout__WEBPACK_IMPORTED_MODULE_2__/* .WithCustomizeYourStoreLayout */ .G]
});
BusinessInfoDescriptionPage.parameters = {
  ...BusinessInfoDescriptionPage.parameters,
  docs: {
    ...BusinessInfoDescriptionPage.parameters?.docs,
    source: {
      originalSource: "() => <BusinessInfoDescription context={{\n  businessInfoDescription: {\n    descriptionText: ''\n  }\n} as designWithAiStateMachineContext} sendEvent={() => {}} />",
      ...BusinessInfoDescriptionPage.parameters?.docs?.source
    }
  }
};

/***/ })

}]);