"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7871],{

/***/ "../../plugins/woocommerce-admin/client/customize-store/design-with-ai/stories/ToneOfVoice.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ToneOfVoicePage: () => (/* binding */ ToneOfVoicePage),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _pages__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../plugins/woocommerce-admin/client/customize-store/design-with-ai/pages/index.tsx");
/* harmony import */ var _WithCustomizeYourStoreLayout__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../plugins/woocommerce-admin/client/customize-store/design-with-ai/stories/WithCustomizeYourStoreLayout.tsx");

/**
 * Internal dependencies
 */



var ToneOfVoicePage = function ToneOfVoicePage() {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_pages__WEBPACK_IMPORTED_MODULE_0__/* .ToneOfVoice */ .Mg, {
    context: {
      toneOfVoice: {
        choice: ''
      }
    },
    sendEvent: function sendEvent() {}
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/Application/Customize Store/Design with AI/Tone of Voice',
  component: _pages__WEBPACK_IMPORTED_MODULE_0__/* .ToneOfVoice */ .Mg,
  decorators: [_WithCustomizeYourStoreLayout__WEBPACK_IMPORTED_MODULE_2__/* .WithCustomizeYourStoreLayout */ .G]
});
ToneOfVoicePage.parameters = {
  ...ToneOfVoicePage.parameters,
  docs: {
    ...ToneOfVoicePage.parameters?.docs,
    source: {
      originalSource: "() => <ToneOfVoice context={{\n  toneOfVoice: {\n    choice: ''\n  }\n} as designWithAiStateMachineContext} sendEvent={() => {}} />",
      ...ToneOfVoicePage.parameters?.docs?.source
    }
  }
};

/***/ })

}]);