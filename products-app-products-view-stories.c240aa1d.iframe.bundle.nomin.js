"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6628],{

/***/ "../../packages/js/product-editor/src/products-app/products-view.stories.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Default: () => (/* binding */ Default),
/* harmony export */   __namedExportsOrder: () => (/* binding */ __namedExportsOrder),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_dataviews__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+dataviews@4.4.1_@emotion+is-prop-valid@1.2.1_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/dataviews/build-module/components/dataviews/index.js");
/* harmony import */ var _utilites_product_data_view_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../packages/js/product-editor/src/products-app/utilites/product-data-view-data.tsx");





/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

// ProductView component is just a wrapper around DataViews component. Currently, it is needed to experiment with the DataViews component in isolation.
// We expect that this component will be removed in the future, instead it will be used the component used in Products App.
var ProductsView = function ProductsView(_ref) {
  var fields = _ref.fields,
    view = _ref.view,
    productsData = _ref.productsData,
    paginationInfo = _ref.paginationInfo,
    defaultLayouts = _ref.defaultLayouts,
    onChangeView = _ref.onChangeView;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(_wordpress_dataviews__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
    data: productsData,
    fields: fields,
    view: view,
    onChangeView: onChangeView,
    paginationInfo: paginationInfo,
    defaultLayouts: defaultLayouts,
    getItemId: function getItemId(item) {
      return item.name;
    }
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Product App/Products View',
  component: ProductsView
});

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
var Template = function Template(args) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createElement)(ProductsView, args);
};
var Default = Template.bind({});
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
Default.args = {
  productsData: _utilites_product_data_view_data__WEBPACK_IMPORTED_MODULE_6__/* .PRODUCTS_DATA */ .O$,
  fields: _utilites_product_data_view_data__WEBPACK_IMPORTED_MODULE_6__/* .PRODUCT_FIELDS */ .QH,
  view: {
    type: 'list',
    fields: _utilites_product_data_view_data__WEBPACK_IMPORTED_MODULE_6__/* .PRODUCT_FIELDS_KEYS */ .jz.filter(function (field) {
      return field !== 'downloads' && field !== 'categories' && field !== 'images';
    })
  },
  defaultLayouts: {
    list: {
      type: 'list'
    }
  },
  paginationInfo: {
    totalPages: 1,
    totalItems: 10
  },
  onChangeView: function onChangeView() {}
};
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "(args: unknown) => <ProductsView {...args} />",
      ...Default.parameters?.docs?.source
    }
  }
};;const __namedExportsOrder = ["Default"];
try {
    // @ts-ignore
    Default.displayName = "Default";
    // @ts-ignore
    Default.__docgenInfo = { "description": "", "displayName": "Default", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/product-editor/src/products-app/products-view.stories.tsx#Default"] = { docgenInfo: Default.__docgenInfo, name: "Default", path: "../../packages/js/product-editor/src/products-app/products-view.stories.tsx#Default" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);