(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[26],{

/***/ 551:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 654:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(551);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_2__);


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const CustomizableDashboard = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["lazy"])(() => __webpack_require__.e(/* import() | customizable-dashboard */ 25).then(__webpack_require__.bind(null, 652)));

class Dashboard extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"] {
  render() {
    const {
      path,
      query
    } = this.props;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Suspense"], {
      fallback: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Spinner"], null)
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(CustomizableDashboard, {
      query: query,
      path: path
    }));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Dashboard);

/***/ })

}]);