"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6],{

/***/ "../../packages/js/components/src/flag/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var emoji_flags__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/emoji-flags@1.3.0/node_modules/emoji-flags/index.js");
/* harmony import */ var emoji_flags__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(emoji_flags__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */






/**
 * Use the `Flag` component to display a country's flag using the operating system's emojis.
 *
 * @param {Object}  props
 * @param {string}  props.code
 * @param {Object}  props.order
 * @param {string}  props.className
 * @param {string}  props.size
 * @param {boolean} props.hideFromScreenReader
 * @return {Object} - React component.
 */
var Flag = function Flag(_ref) {
  var code = _ref.code,
    order = _ref.order,
    className = _ref.className,
    size = _ref.size,
    hideFromScreenReader = _ref.hideFromScreenReader;
  var classes = classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-flag', className);
  var _code = code || 'unknown';
  if (order && order.shipping && order.shipping.country) {
    _code = order.shipping.country;
  } else if (order && order.billing && order.billing.country) {
    _code = order.billing.country;
  }
  var inlineStyles = {
    fontSize: size
  };
  var emoji = (0,lodash__WEBPACK_IMPORTED_MODULE_2__.get)(emoji_flags__WEBPACK_IMPORTED_MODULE_1___default().countryCode(_code), 'emoji');
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("div", {
    className: classes,
    style: inlineStyles,
    "aria-hidden": hideFromScreenReader
  }, emoji && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("span", null, emoji), !emoji && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createElement)("span", {
    className: "woocommerce-flag__fallback"
  }, "Invalid country flag"));
};
Flag.propTypes = {
  /**
   * Two letter, three letter or three digit country code.
   */
  code: (prop_types__WEBPACK_IMPORTED_MODULE_4___default().string),
  /**
   * An order can be passed instead of `code` and the code will automatically be pulled from the billing or shipping data.
   */
  order: (prop_types__WEBPACK_IMPORTED_MODULE_4___default().object),
  /**
   * Additional CSS classes.
   */
  className: (prop_types__WEBPACK_IMPORTED_MODULE_4___default().string),
  /**
   * Supply a font size to be applied to the emoji flag.
   */
  size: (prop_types__WEBPACK_IMPORTED_MODULE_4___default().number)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Flag);

/***/ }),

/***/ "../../packages/js/components/src/product-image/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ product_image)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/product-image/placeholder.tsx
// eslint-disable-next-line max-len
var placeholderWhiteBackground = "data:image/svg+xml;utf8,%3Csvg width='421' height='421' xmlns='http://www.w3.org/2000/svg' xmlns:svg='http://www.w3.org/2000/svg'%3E%3Cstyle type='text/css'%3E.st0%7Bfill:url(%23SVGID_1_);stroke:%23717275;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10%7D .st1%7Bfill:%23FFFFFF;%7D .st2%7Bfill:%23717275;%7D .st3%7Bfill:%23DCDDE0;stroke:%23717275;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;%7D%3C/style%3E%3CradialGradient cx='105.8248' cy='287.7805' gradientUnits='userSpaceOnUse' id='SVGID_1_' r='372.6935'%3E%3Cstop offset='0.2613' stop-color='%23DCDDE0'/%3E%3Cstop offset='0.633' stop-color='%23D8DADD'/%3E%3Cstop offset='0.9665' stop-color='%23CECFD3'/%3E%3Cstop offset='1' stop-color='%23CCCED2'/%3E%3C/radialGradient%3E%3Cg class='layer' display='inline'%3E%3Ctitle%3ELayer 2%3C/title%3E%3Crect fill='%23ffffff' height='417.99996' id='svg_7' stroke-dasharray='null' stroke-linecap='null' stroke-linejoin='null' stroke-width='null' width='417.99996' x='1.50002' y='1.5'/%3E%3C/g%3E%3Cg class='layer' display='inline'%3E%3Ctitle%3ELayer 1%3C/title%3E%3Cg id='svg_2'/%3E%3Cg id='svg_6'%3E%3Cpath class='st0' d='m330.44409,336.12693c-0.12194,0.36582 0,0.67068 0.30485,0.79262c1.40232,-0.79262 3.17047,-1.0365 4.63377,-0.48776c0.67068,-1.46329 0.12194,-2.43882 0.06097,-3.90212c-0.91456,-15.66945 -0.73165,-31.9486 -0.73165,-47.73998c0,-16.34012 -0.30485,-32.74121 0.54874,-48.9594c0.79262,-15.9743 1.89009,-31.5218 1.28038,-47.55707c-0.60971,-15.79139 -0.06097,-31.70471 0.73165,-47.37416c0.36582,-8.04812 0.79262,-15.66945 0.36582,-23.77854c-0.48776,-9.08462 -0.36582,-21.88845 -0.36582,-30.97307c0,-1.0365 0.18291,-1.82912 -0.79262,-2.43882c-0.79262,-0.48776 -1.52427,-0.42679 -2.43882,-0.42679c-2.49979,0 -4.87765,-0.36582 -7.37744,-0.36582c-1.52427,0 -2.86562,0 -4.32891,0.30485c-0.79262,0.12194 -1.52427,0.06097 -2.31688,0.06097c-0.85359,0 -1.52427,0.18291 -2.31688,0.36582c-6.88968,1.15844 -15.73042,2.49979 -22.62009,1.76815c-6.88968,-0.73165 -13.71839,-0.67068 -20.54709,-2.01203c-6.46288,-1.28038 -12.92577,-0.42679 -19.38865,-0.42679c-4.146,0 -8.23103,0 -12.37703,0c-3.96309,0 -7.80424,-0.73165 -11.8283,-0.73165c-6.52385,0.06097 -13.10868,0.12194 -19.63253,0.36582c-4.51182,0.18291 -8.84074,0 -13.35256,0.42679c-6.82871,0.60971 -13.77936,1.09747 -20.66903,1.0365c-3.59727,0 -7.07259,0.24388 -10.60889,-0.42679c-3.17047,-0.60971 -6.58483,-0.12194 -9.7553,0.18291c-3.96309,0.36582 -7.86521,1.0365 -11.8283,1.0365c-3.84115,-0.06097 -7.74327,-0.85359 -11.58441,-0.85359c-4.38988,-0.12194 -8.59686,0.42679 -12.98674,0.12194c-5.36541,-0.36582 -10.60889,-0.06097 -15.91333,-0.12194c-0.91456,0 -1.34135,0.24388 -2.13397,0.36582c-0.79262,0.12194 -1.40232,0.36582 -2.19494,0.36582c-0.85359,0 -1.34135,-0.30485 -2.13397,-0.36582c-1.64621,-0.18291 -5.91415,-0.06097 -7.49938,-0.48776c-1.70718,-0.42679 -3.41435,-0.91456 -5.12153,-1.34135c-1.40232,1.34135 -1.52427,3.5363 -1.64621,5.48735c-0.85359,21.58359 -0.73165,43.16719 -0.60971,64.75078c0,4.99959 0.06097,9.99918 0.60971,14.9378c0.42679,3.84115 1.15844,7.6823 1.46329,11.52344c0.48776,6.219 -0.06097,12.49897 -0.36582,18.71798c-0.36582,7.31647 -0.36582,14.63295 0.06097,21.94942c0.97553,18.90089 -0.48776,40.30157 -0.79262,59.20246c-0.36582,18.41312 -0.67068,37.1311 3.90212,54.99549c4.63377,-1.82912 17.13274,1.15844 22.55912,1.40232c5.85318,0.24388 11.8283,0.30485 17.74245,0.30485c6.76774,0 13.59644,-0.36582 20.30321,0c8.47491,0.42679 16.09624,2.31688 24.63212,1.82912c4.146,-0.24388 8.65783,0.36582 12.68189,0.36582c3.29241,0 9.87724,-1.09747 12.25509,0.73165c8.77977,0 20.18127,0.12194 28.90007,-0.73165c9.08462,-0.85359 19.38865,-1.21941 28.47327,-0.36582c7.37744,0.73165 14.45003,1.34135 21.82748,0.36582c4.63377,-0.60971 9.14559,-1.09747 13.9013,-1.09747c4.32891,0 8.292,-1.58524 12.49897,-1.46329c4.63377,0.12194 9.38947,1.40232 14.02324,1.89009c3.04853,0.30485 9.63336,-2.49979 12.49897,-1.21941l-0.00003,-0.00001z' fill='black' id='svg_1'/%3E%3Cpath class='st1' d='m313.79912,275.40021c-1.09747,0 -3.90212,-4.20697 -4.99959,-5.85318c-0.42679,-0.67068 -0.79262,-1.21941 -1.0365,-1.52427c-1.76815,-2.25591 -4.02406,-4.51182 -6.219,-6.6458c-0.67068,-0.67068 -1.40232,-1.40232 -2.073,-2.073c-3.5363,-3.59727 -9.02365,-7.98715 -13.16965,-9.51141c-1.40232,-0.48776 -2.56077,-1.21941 -3.71921,-1.82912c-2.19494,-1.28038 -4.20697,-2.43882 -7.49938,-2.49979c-0.30485,0 -0.67068,0 -0.97553,0c-3.78018,0 -7.49938,0.48776 -11.34053,1.09747c-7.19453,1.09747 -16.82789,7.49938 -21.52262,14.32809c-0.73165,1.09747 -1.15844,2.31688 -1.21941,3.5363c-2.31688,-0.54874 -5.6093,-3.17047 -7.62133,-4.75571c-0.30485,-0.24388 -0.60971,-0.48776 -0.85359,-0.67068c-2.74368,-2.13397 -4.75571,-4.5728 -6.95065,-7.2555c-1.28038,-1.52427 -2.62174,-3.1095 -4.08503,-4.63377c-7.92618,-8.17006 -16.88886,-15.48653 -25.54668,-22.55912c-4.51182,-3.65824 -9.14559,-7.43841 -13.59644,-11.27956c-5.79221,-4.99959 -10.365,-9.81627 -14.45003,-15.12071c-0.42679,-0.54874 -0.85359,-1.09747 -1.21941,-1.64621c-1.58524,-2.13397 -3.23144,-4.26794 -5.12153,-6.15803c-0.67068,-0.67068 -2.31688,-1.89009 -4.20697,-3.23144c-2.98756,-2.19494 -7.56035,-5.48735 -7.92618,-6.70677c-0.42679,-1.40232 -3.5363,-3.5363 -3.59727,-3.5363c-0.85359,-0.48776 -1.76815,-1.40232 -1.82912,-2.62174l-0.06097,-0.85359l-0.79262,0.36582c-2.86562,1.34135 -4.93862,3.41435 -7.07259,5.6093c-0.36582,0.36582 -0.67068,0.73165 -1.0365,1.0365c-5.67027,5.73124 -11.15762,11.64539 -16.27915,17.49856c-2.49979,2.86562 -5.12153,6.03609 -7.31647,9.38947c-0.60971,0.91456 -1.15844,2.01203 -1.70718,3.17047c-0.91456,1.89009 -1.89009,3.84115 -3.04853,4.99959c-0.36582,-0.54874 -0.73165,-1.64621 -0.91456,-2.13397c-0.06097,-0.18291 -0.12194,-0.42679 -0.18291,-0.54874c-1.28038,-3.17047 -0.79262,-6.52385 -0.30485,-10.06015c0.36582,-2.49979 0.73165,-4.99959 0.48776,-7.62133c-0.12194,-0.97553 -0.30485,-2.01203 -0.48776,-3.04853c-0.60971,-3.47532 -1.21941,-7.01162 -0.73165,-10.24306c1.21941,-9.63336 2.43882,-19.51059 3.04853,-29.32686c0.97553,-9.69433 0.42679,-18.83992 -0.18291,-28.53424c-0.12194,-2.43882 -0.30485,-4.99959 -0.42679,-7.49938c0,-1.0365 -0.06097,-2.13397 -0.06097,-3.17047c-0.12194,-3.17047 -0.24388,-6.219 0.60971,-9.20656c0.18291,-0.12194 0.48776,-0.30485 0.85359,-0.36582l2.37785,1.0365c2.25591,0.91456 5.1825,1.40232 8.90171,1.40232c3.41435,0 7.01162,-0.36582 10.54791,-0.73165c3.23144,-0.30485 6.34094,-0.60971 9.02365,-0.60971c0.67068,0 1.28038,0 1.89009,0.06097c1.52427,0.12194 3.04853,0.30485 4.51182,0.48776c2.13397,0.24388 4.38988,0.54874 6.58483,0.54874c0.54874,0 1.09747,0 1.58524,0c3.90212,0 7.92618,-0.18291 11.76733,-0.36582c3.17047,-0.12194 6.46288,-0.30485 9.69433,-0.36582c5.48735,-0.06097 11.09665,-0.36582 16.46206,-0.60971c2.25591,-0.12194 4.45085,-0.24388 6.70677,-0.30485c4.38988,-0.18291 8.90171,-0.18291 13.23062,-0.18291c3.1095,0 6.34094,0 9.57238,-0.06097c6.6458,-0.18291 13.35256,-0.54874 19.81545,-0.97553c6.88968,-0.42679 14.08421,-0.85359 21.09583,-0.97553c1.76815,-0.06097 3.5363,-0.06097 5.24347,-0.06097c3.84115,-0.06097 7.80424,-0.06097 11.76733,-0.30485c2.25591,-0.12194 4.5728,-0.36582 6.82871,-0.54874c3.78018,-0.36582 7.74327,-0.67068 11.58441,-0.67068c1.40232,0 2.74368,0.06097 4.02406,0.12194c1.89009,0.12194 3.78018,0.48776 5.79221,0.85359c2.62174,0.48776 5.30444,0.91456 7.92618,0.91456c0.67068,0 1.34135,-0.06097 1.95106,-0.12194c-0.73165,1.40232 -0.73165,3.17047 -0.67068,4.08503c0.12194,2.31688 0.12194,4.81668 0.12194,7.19453c0,9.38947 -0.97553,18.29118 -2.01203,27.74163c-0.36582,3.23144 -0.73165,6.6458 -1.0365,9.99918c-0.60971,13.65742 0,28.10745 0.60971,40.8503l0,4.63377c0.30485,4.146 0,8.23103 -0.30485,12.13315c-0.30485,3.90212 -0.60971,7.92618 -0.30485,11.95024c1.28038,12.07218 1.82912,23.71757 1.64621,34.6313l0.42679,10.60889l0.06097,0.06097c0.48776,1.34135 0.97553,6.95065 -0.24388,8.77977c-0.36582,0.42679 -0.60971,0.48776 -0.79262,0.48776l0,0l-0.00004,0.00002z' id='svg_3'/%3E%3Cpath class='st2' d='m296.54444,101.02428c1.40232,0 2.68271,0.06097 3.96309,0.12194c1.82912,0.12194 3.71921,0.48776 5.73124,0.79262c2.62174,0.48776 5.36541,0.97553 8.04812,0.97553c0.36582,0 0.67068,0 1.0365,0c-0.36582,1.21941 -0.36582,2.49979 -0.36582,3.41435c0.12194,2.31688 0.12194,4.75571 0.12194,7.13356c0,9.38947 -0.97553,18.29118 -2.01203,27.68065c-0.36582,3.29241 -0.73165,6.6458 -1.0365,9.99918l0,0l0,0c-0.60971,13.59644 0,28.10745 0.60971,40.8503l0,4.63377l0,0.06097l0,0.06097c0.30485,4.02406 0,8.10909 -0.30485,12.01121c-0.30485,3.90212 -0.60971,7.98715 -0.30485,12.01121l0,0l0,0c1.28038,12.01121 1.82912,23.65659 1.64621,34.50936l0,0.06097l0,0.06097l0.42679,10.48694l0,0.18291l0.06097,0.18291c0.48776,1.34135 0.85359,6.70677 -0.18291,8.292c-0.06097,0.06097 -0.12194,0.18291 -0.18291,0.18291c-1.0365,-0.36582 -3.65824,-4.26794 -4.51182,-5.54833c-0.48776,-0.67068 -0.79262,-1.21941 -1.09747,-1.58524c-1.82912,-2.31688 -4.08503,-4.51182 -6.219,-6.70677c-0.67068,-0.67068 -1.40232,-1.34135 -2.073,-2.073c-3.59727,-3.65824 -9.14559,-8.04812 -13.41353,-9.63336c-1.34135,-0.48776 -2.49979,-1.15844 -3.59727,-1.82912c-2.13397,-1.21941 -4.32891,-2.49979 -7.80424,-2.62174c-0.30485,0 -0.67068,0 -0.97553,0c-3.84115,0 -7.56035,0.48776 -11.46247,1.09747c-7.37744,1.09747 -17.19371,7.62133 -21.94942,14.57197c-0.67068,0.97553 -1.09747,2.01203 -1.28038,3.1095c-2.13397,-0.79262 -4.93862,-3.04853 -6.70677,-4.45085c-0.30485,-0.24388 -0.60971,-0.48776 -0.85359,-0.67068c-2.68271,-2.073 -4.69474,-4.51182 -6.88968,-7.13356c-1.28038,-1.52427 -2.62174,-3.1095 -4.08503,-4.69474c-7.92618,-8.17006 -16.88886,-15.48653 -25.60765,-22.55912c-4.51182,-3.65824 -9.14559,-7.43841 -13.53547,-11.27956c-5.73124,-4.99959 -10.30403,-9.7553 -14.38906,-15.05974c-0.42679,-0.54874 -0.85359,-1.09747 -1.21941,-1.64621c-1.58524,-2.13397 -3.29241,-4.32891 -5.1825,-6.219c-0.73165,-0.73165 -2.31688,-1.89009 -4.26794,-3.29241c-2.37785,-1.70718 -7.31647,-5.30444 -7.6823,-6.40191c-0.48776,-1.70718 -3.84115,-3.90212 -3.84115,-3.90212c-0.67068,-0.42679 -1.46329,-1.15844 -1.52427,-2.13397l-0.12194,-1.70718l-1.58524,0.73165c-2.98756,1.34135 -5.1825,3.59727 -7.2555,5.73124c-0.36582,0.36582 -0.67068,0.73165 -1.0365,1.0365c-5.67027,5.73124 -11.15762,11.64539 -16.27915,17.49856c-2.56077,2.92659 -5.1825,6.09706 -7.37744,9.45044c-0.60971,0.91456 -1.15844,2.073 -1.76815,3.29241c-0.73165,1.46329 -1.46329,3.04853 -2.37785,4.146c-0.18291,-0.48776 -0.42679,-0.97553 -0.48776,-1.28038c-0.06097,-0.24388 -0.18291,-0.42679 -0.24388,-0.54874c-1.21941,-2.98756 -0.79262,-6.27997 -0.24388,-9.69433c0.36582,-2.49979 0.73165,-5.12153 0.48776,-7.74327l0,0l0,0c-0.12194,-0.97553 -0.30485,-1.95106 -0.48776,-2.98756c-0.60971,-3.41435 -1.15844,-6.95065 -0.73165,-10.06015c1.21941,-9.63336 2.43882,-19.51059 3.04853,-29.32686c0.97553,-9.7553 0.42679,-18.90089 -0.18291,-28.65618c-0.12194,-2.43882 -0.30485,-4.93862 -0.42679,-7.43841c0,-1.09747 -0.06097,-2.13397 -0.06097,-3.23144c-0.12194,-3.04853 -0.18291,-5.97512 0.54874,-8.84074c0.06097,-0.06097 0.18291,-0.06097 0.24388,-0.12194l2.25591,0.97553c2.31688,0.97553 5.30444,1.46329 9.14559,1.46329c3.41435,0 7.07259,-0.36582 10.60889,-0.73165c3.23144,-0.30485 6.27997,-0.60971 8.96268,-0.60971c0.67068,0 1.28038,0 1.82912,0.06097c1.46329,0.12194 2.98756,0.30485 4.45085,0.42679c2.19494,0.24388 4.38988,0.54874 6.6458,0.54874c0.54874,0 1.09747,0 1.58524,0c3.96309,0 7.92618,-0.18291 11.76733,-0.36582c3.17047,-0.12194 6.46288,-0.30485 9.69433,-0.36582c5.48735,-0.06097 11.09665,-0.36582 16.46206,-0.60971c2.25591,-0.12194 4.45085,-0.24388 6.70677,-0.30485c4.38988,-0.18291 8.84074,-0.18291 13.16965,-0.18291c3.1095,0 6.40191,0 9.57238,-0.06097c6.6458,-0.18291 13.35256,-0.54874 19.87642,-0.97553c6.88968,-0.42679 14.02324,-0.85359 21.09583,-0.97553c1.76815,-0.06097 3.5363,-0.06097 5.24347,-0.06097c3.84115,-0.06097 7.86521,-0.06097 11.76733,-0.30485c2.31688,-0.12194 4.63377,-0.36582 6.88968,-0.54874c3.78018,-0.30485 7.74327,-0.67068 11.52344,-0.67068l0,0m0,-1.21941c-6.15803,0 -12.31606,0.85359 -18.47409,1.21941c-5.67027,0.30485 -11.34053,0.24388 -16.94983,0.36582c-13.65742,0.24388 -27.25386,1.58524 -40.97225,1.95106c-7.62133,0.18291 -15.18168,-0.06097 -22.80301,0.30485c-7.74327,0.30485 -15.42556,0.79262 -23.16883,0.91456c-7.13356,0.12194 -14.26712,0.73165 -21.46165,0.73165c-0.54874,0 -1.0365,0 -1.58524,0c-3.71921,-0.06097 -7.37744,-0.73165 -11.03568,-1.0365c-0.60971,-0.06097 -1.21941,-0.06097 -1.89009,-0.06097c-5.6093,0 -13.04771,1.34135 -19.51059,1.34135c-3.23144,0 -6.27997,-0.30485 -8.7188,-1.34135l-2.49979,-1.09747c-0.91456,0 -1.52427,0.60971 -1.52427,0.60971c-1.21941,4.26794 -0.60971,8.53588 -0.60971,12.80383c0.60971,12.19412 1.82912,23.77854 0.60971,35.97266c-0.60971,9.7553 -1.82912,19.51059 -3.04853,29.26589c-0.60971,4.26794 0.60971,9.14559 1.21941,13.41353c0.60971,6.09706 -2.43882,12.19412 -0.12194,17.80342c0.30485,0.73165 0.97553,2.80465 1.64621,3.29241c2.31688,-1.70718 3.65824,-6.15803 5.30444,-8.7188c2.13397,-3.29241 4.75571,-6.46288 7.2555,-9.3285c5.24347,-5.97512 10.66986,-11.8283 16.21818,-17.43759c2.49979,-2.49979 4.69474,-5.06056 7.92618,-6.58483c0.12194,1.34135 1.0365,2.43882 2.13397,3.1095c0.24388,0.12194 2.98756,2.073 3.29241,3.17047c0.60971,2.19494 10.42597,8.35297 12.25509,10.24306c2.37785,2.37785 4.32891,5.12153 6.34094,7.74327c4.38988,5.67027 9.14559,10.54791 14.57197,15.24265c12.98674,11.27956 27.07095,21.40068 39.08216,33.77771c3.90212,4.02406 6.6458,8.47491 11.09665,11.88927c2.31688,1.82912 6.6458,5.48735 9.51141,5.67027c-0.12194,-1.40232 0.36582,-2.74368 1.15844,-3.90212c4.32891,-6.34094 13.65742,-12.92577 21.09583,-14.08421c3.65824,-0.54874 7.49938,-1.09747 11.27956,-1.09747c0.30485,0 0.60971,0 0.97553,0c4.81668,0.12194 6.95065,2.80465 10.97471,4.32891c4.26794,1.58524 9.69433,6.03609 12.98674,9.38947c2.74368,2.80465 5.85318,5.67027 8.17006,8.65783c0.97553,1.28038 4.63377,7.56035 6.52385,7.56035c0,0 0.06097,0 0.06097,0c2.74368,-0.18291 2.073,-8.41394 1.46329,-10.12112l-0.42679,-10.48694c0.18291,-11.52344 -0.42679,-23.10786 -1.64621,-34.69227c-0.60971,-7.92618 1.21941,-15.85236 0.60971,-24.02242l0,-4.69474c-0.60971,-13.35256 -1.21941,-27.3758 -0.60971,-40.78933c1.21941,-12.80383 3.04853,-24.99795 3.04853,-37.80177c0,-2.43882 0,-4.87765 -0.12194,-7.19453c-0.06097,-1.58524 0.12194,-3.84115 1.52427,-4.87765c-1.09747,0.24388 -2.25591,0.30485 -3.41435,0.30485c-4.5728,0 -9.26753,-1.46329 -13.65742,-1.76815c-1.34135,0.12194 -2.68271,0.06097 -4.08503,0.06097l0,0l0.00003,0zm22.80301,1.09747c-0.67068,0 -1.21941,0.18291 -1.64621,0.48776c0.60971,-0.12194 1.21941,-0.30485 1.82912,-0.48776c-0.06097,0 -0.12194,0 -0.18291,0l0,0z' id='svg_4'/%3E%3Cpath class='st3' d='m235.75674,146.69126c-3.1095,3.41435 -4.38988,9.81627 -4.81668,14.20615c-0.60971,6.03609 -1.46329,10.97471 2.74368,15.66945c4.63377,5.12153 12.55994,9.87724 19.20574,11.64539c3.47532,0.97553 7.49938,-0.73165 10.7918,-1.40232c7.92618,-1.70718 11.95024,-6.52385 15.42556,-13.9013c4.20697,-8.77977 0.67068,-15.73042 -2.86562,-23.96145c-3.84115,-8.90171 -15.5475,-12.92577 -24.81504,-11.70636c-3.78018,0.48776 -5.91415,2.80465 -8.90171,4.87765c-1.64621,1.15844 -6.52385,2.80465 -6.76774,4.5728l0.00001,-0.00001z' id='svg_5'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E";
// eslint-disable-next-line max-len
var placeholderTransparentBackground = "data:image/svg+xml;utf8,%3Csvg version='1.1' id='Layer_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' viewBox='0 0 421 421' style='enable-background:new 0 0 421 421;' xml:space='preserve'%3E%3Cstyle type='text/css'%3E .st0%7Bfill:url(%23SVGID_1_);stroke:%23717275;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;%7D .st1%7Bfill:%23FFFFFF;%7D .st2%7Bfill:%23717275;%7D .st3%7Bfill:%23DCDDE0;stroke:%23717275;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;%7D%0A%3C/style%3E%3CradialGradient id='SVGID_1_' cx='105.8248' cy='287.7805' r='372.6935' gradientUnits='userSpaceOnUse'%3E%3Cstop offset='0.2613' style='stop-color:%23DCDDE0'/%3E%3Cstop offset='0.633' style='stop-color:%23D8DADD'/%3E%3Cstop offset='0.9665' style='stop-color:%23CECFD3'/%3E%3Cstop offset='1' style='stop-color:%23CCCED2'/%3E%3C/radialGradient%3E%3Cpath class='st0' d='M407.2,416.7c-0.2,0.6,0,1.1,0.5,1.3c2.3-1.3,5.2-1.7,7.6-0.8c1.1-2.4,0.2-4,0.1-6.4 c-1.5-25.7-1.2-52.4-1.2-78.3c0-26.8-0.5-53.7,0.9-80.3c1.3-26.2,3.1-51.7,2.1-78c-1-25.9-0.1-52,1.2-77.7c0.6-13.2,1.3-25.7,0.6-39 c-0.8-14.9-0.6-35.9-0.6-50.8c0-1.7,0.3-3-1.3-4c-1.3-0.8-2.5-0.7-4-0.7c-4.1,0-8-0.6-12.1-0.6c-2.5,0-4.7,0-7.1,0.5 c-1.3,0.2-2.5,0.1-3.8,0.1c-1.4,0-2.5,0.3-3.8,0.6c-11.3,1.9-25.8,4.1-37.1,2.9c-11.3-1.2-22.5-1.1-33.7-3.3 c-10.6-2.1-21.2-0.7-31.8-0.7c-6.8,0-13.5,0-20.3,0c-6.5,0-12.8-1.2-19.4-1.2c-10.7,0.1-21.5,0.2-32.2,0.6c-7.4,0.3-14.5,0-21.9,0.7 c-11.2,1-22.6,1.8-33.9,1.7c-5.9,0-11.6,0.4-17.4-0.7c-5.2-1-10.8-0.2-16,0.3c-6.5,0.6-12.9,1.7-19.4,1.7c-6.3-0.1-12.7-1.4-19-1.4 C77,3,70.1,3.9,62.9,3.4c-8.8-0.6-17.4-0.1-26.1-0.2c-1.5,0-2.2,0.4-3.5,0.6C32,4,31,4.4,29.7,4.4c-1.4,0-2.2-0.5-3.5-0.6 C23.5,3.5,16.5,3.7,13.9,3c-2.8-0.7-5.6-1.5-8.4-2.2C3.2,3,3,6.6,2.8,9.8C1.4,45.2,1.6,80.6,1.8,116c0,8.2,0.1,16.4,1,24.5 c0.7,6.3,1.9,12.6,2.4,18.9c0.8,10.2-0.1,20.5-0.6,30.7c-0.6,12-0.6,24,0.1,36c1.6,31-0.8,66.1-1.3,97.1 c-0.6,30.2-1.1,60.9,6.4,90.2c7.6-3,28.1,1.9,37,2.3c9.6,0.4,19.4,0.5,29.1,0.5c11.1,0,22.3-0.6,33.3,0c13.9,0.7,26.4,3.8,40.4,3 c6.8-0.4,14.2,0.6,20.8,0.6c5.4,0,16.2-1.8,20.1,1.2c14.4,0,33.1,0.2,47.4-1.2c14.9-1.4,31.8-2,46.7-0.6c12.1,1.2,23.7,2.2,35.8,0.6 c7.6-1,15-1.8,22.8-1.8c7.1,0,13.6-2.6,20.5-2.4c7.6,0.2,15.4,2.3,23,3.1C391.7,419.2,402.5,414.6,407.2,416.7z'/%3E%3Cg%3E%3Cpath class='st1' d='M379.9,317.1c-1.8,0-6.4-6.9-8.2-9.6c-0.7-1.1-1.3-2-1.7-2.5c-2.9-3.7-6.6-7.4-10.2-10.9 c-1.1-1.1-2.3-2.3-3.4-3.4c-5.8-5.9-14.8-13.1-21.6-15.6c-2.3-0.8-4.2-2-6.1-3c-3.6-2.1-6.9-4-12.3-4.1c-0.5,0-1.1,0-1.6,0 c-6.2,0-12.3,0.8-18.6,1.8c-11.8,1.8-27.6,12.3-35.3,23.5c-1.2,1.8-1.9,3.8-2,5.8c-3.8-0.9-9.2-5.2-12.5-7.8 c-0.5-0.4-1-0.8-1.4-1.1c-4.5-3.5-7.8-7.5-11.4-11.9c-2.1-2.5-4.3-5.1-6.7-7.6c-13-13.4-27.7-25.4-41.9-37 c-7.4-6-15-12.2-22.3-18.5c-9.5-8.2-17-16.1-23.7-24.8c-0.7-0.9-1.4-1.8-2-2.7c-2.6-3.5-5.3-7-8.4-10.1c-1.1-1.1-3.8-3.1-6.9-5.3 c-4.9-3.6-12.4-9-13-11c-0.7-2.3-5.8-5.8-5.9-5.8c-1.4-0.8-2.9-2.3-3-4.3l-0.1-1.4l-1.3,0.6c-4.7,2.2-8.1,5.6-11.6,9.2 c-0.6,0.6-1.1,1.2-1.7,1.7c-9.3,9.4-18.3,19.1-26.7,28.7c-4.1,4.7-8.4,9.9-12,15.4c-1,1.5-1.9,3.3-2.8,5.2c-1.5,3.1-3.1,6.3-5,8.2 c-0.6-0.9-1.2-2.7-1.5-3.5c-0.1-0.3-0.2-0.7-0.3-0.9c-2.1-5.2-1.3-10.7-0.5-16.5c0.6-4.1,1.2-8.2,0.8-12.5c-0.2-1.6-0.5-3.3-0.8-5 c-1-5.7-2-11.5-1.2-16.8c2-15.8,4-32,5-48.1c1.6-15.9,0.7-30.9-0.3-46.8c-0.2-4-0.5-8.2-0.7-12.3c0-1.7-0.1-3.5-0.1-5.2 c-0.2-5.2-0.4-10.2,1-15.1c0.3-0.2,0.8-0.5,1.4-0.6l3.9,1.7c3.7,1.5,8.5,2.3,14.6,2.3c5.6,0,11.5-0.6,17.3-1.2 c5.3-0.5,10.4-1,14.8-1c1.1,0,2.1,0,3.1,0.1c2.5,0.2,5,0.5,7.4,0.8c3.5,0.4,7.2,0.9,10.8,0.9c0.9,0,1.8,0,2.6,0 c6.4,0,13-0.3,19.3-0.6c5.2-0.2,10.6-0.5,15.9-0.6c9-0.1,18.2-0.6,27-1c3.7-0.2,7.3-0.4,11-0.5c7.2-0.3,14.6-0.3,21.7-0.3 c5.1,0,10.4,0,15.7-0.1c10.9-0.3,21.9-0.9,32.5-1.6c11.3-0.7,23.1-1.4,34.6-1.6c2.9-0.1,5.8-0.1,8.6-0.1c6.3-0.1,12.8-0.1,19.3-0.5 c3.7-0.2,7.5-0.6,11.2-0.9c6.2-0.6,12.7-1.1,19-1.1c2.3,0,4.5,0.1,6.6,0.2c3.1,0.2,6.2,0.8,9.5,1.4c4.3,0.8,8.7,1.5,13,1.5 c1.1,0,2.2-0.1,3.2-0.2c-1.2,2.3-1.2,5.2-1.1,6.7c0.2,3.8,0.2,7.9,0.2,11.8c0,15.4-1.6,30-3.3,45.5c-0.6,5.3-1.2,10.9-1.7,16.4 c-1,22.4,0,46.1,1,67l0,7.6c0.5,6.8,0,13.5-0.5,19.9c-0.5,6.4-1,13-0.5,19.6c2.1,19.8,3,38.9,2.7,56.8l0.7,17.4l0.1,0.1 c0.8,2.2,1.6,11.4-0.4,14.4C380.6,317,380.2,317.1,379.9,317.1L379.9,317.1z'/%3E%3Cpath class='st2' d='M351.6,31.1c2.3,0,4.4,0.1,6.5,0.2c3,0.2,6.1,0.8,9.4,1.3c4.3,0.8,8.8,1.6,13.2,1.6c0.6,0,1.1,0,1.7,0 c-0.6,2-0.6,4.1-0.6,5.6c0.2,3.8,0.2,7.8,0.2,11.7c0,15.4-1.6,30-3.3,45.4c-0.6,5.4-1.2,10.9-1.7,16.4l0,0l0,0c-1,22.3,0,46.1,1,67 v7.6v0.1l0,0.1c0.5,6.6,0,13.3-0.5,19.7c-0.5,6.4-1,13.1-0.5,19.7l0,0l0,0c2.1,19.7,3,38.8,2.7,56.6l0,0.1l0,0.1l0.7,17.2l0,0.3 l0.1,0.3c0.8,2.2,1.4,11-0.3,13.6c-0.1,0.1-0.2,0.3-0.3,0.3c-1.7-0.6-6-7-7.4-9.1c-0.8-1.1-1.3-2-1.8-2.6c-3-3.8-6.7-7.4-10.2-11 c-1.1-1.1-2.3-2.2-3.4-3.4c-5.9-6-15-13.2-22-15.8c-2.2-0.8-4.1-1.9-5.9-3c-3.5-2-7.1-4.1-12.8-4.3c-0.5,0-1.1,0-1.6,0 c-6.3,0-12.4,0.8-18.8,1.8c-12.1,1.8-28.2,12.5-36,23.9c-1.1,1.6-1.8,3.3-2.1,5.1c-3.5-1.3-8.1-5-11-7.3c-0.5-0.4-1-0.8-1.4-1.1 c-4.4-3.4-7.7-7.4-11.3-11.7c-2.1-2.5-4.3-5.1-6.7-7.7c-13-13.4-27.7-25.4-42-37c-7.4-6-15-12.2-22.2-18.5 c-9.4-8.2-16.9-16-23.6-24.7c-0.7-0.9-1.4-1.8-2-2.7c-2.6-3.5-5.4-7.1-8.5-10.2c-1.2-1.2-3.8-3.1-7-5.4c-3.9-2.8-12-8.7-12.6-10.5 c-0.8-2.8-6.3-6.4-6.3-6.4c-1.1-0.7-2.4-1.9-2.5-3.5l-0.2-2.8l-2.6,1.2c-4.9,2.2-8.5,5.9-11.9,9.4c-0.6,0.6-1.1,1.2-1.7,1.7 c-9.3,9.4-18.3,19.1-26.7,28.7c-4.2,4.8-8.5,10-12.1,15.5c-1,1.5-1.9,3.4-2.9,5.4c-1.2,2.4-2.4,5-3.9,6.8c-0.3-0.8-0.7-1.6-0.8-2.1 c-0.1-0.4-0.3-0.7-0.4-0.9c-2-4.9-1.3-10.3-0.4-15.9c0.6-4.1,1.2-8.4,0.8-12.7l0,0l0,0c-0.2-1.6-0.5-3.2-0.8-4.9 c-1-5.6-1.9-11.4-1.2-16.5c2-15.8,4-32,5-48.1c1.6-16,0.7-31-0.3-47c-0.2-4-0.5-8.1-0.7-12.2c0-1.8-0.1-3.5-0.1-5.3 c-0.2-5-0.3-9.8,0.9-14.5c0.1-0.1,0.3-0.1,0.4-0.2l3.7,1.6c3.8,1.6,8.7,2.4,15,2.4c5.6,0,11.6-0.6,17.4-1.2c5.3-0.5,10.3-1,14.7-1 c1.1,0,2.1,0,3,0.1c2.4,0.2,4.9,0.5,7.3,0.7c3.6,0.4,7.2,0.9,10.9,0.9c0.9,0,1.8,0,2.6,0c6.5,0,13-0.3,19.3-0.6 c5.2-0.2,10.6-0.5,15.9-0.6c9-0.1,18.2-0.6,27-1c3.7-0.2,7.3-0.4,11-0.5c7.2-0.3,14.5-0.3,21.6-0.3c5.1,0,10.5,0,15.7-0.1 c10.9-0.3,21.9-0.9,32.6-1.6c11.3-0.7,23-1.4,34.6-1.6c2.9-0.1,5.8-0.1,8.6-0.1c6.3-0.1,12.9-0.1,19.3-0.5 c3.8-0.2,7.6-0.6,11.3-0.9C338.9,31.7,345.4,31.1,351.6,31.1L351.6,31.1 M351.6,29.1c-10.1,0-20.2,1.4-30.3,2 c-9.3,0.5-18.6,0.4-27.8,0.6c-22.4,0.4-44.7,2.6-67.2,3.2c-12.5,0.3-24.9-0.1-37.4,0.5c-12.7,0.5-25.3,1.3-38,1.5 c-11.7,0.2-23.4,1.2-35.2,1.2c-0.9,0-1.7,0-2.6,0c-6.1-0.1-12.1-1.2-18.1-1.7c-1-0.1-2-0.1-3.1-0.1c-9.2,0-21.4,2.2-32,2.2 c-5.3,0-10.3-0.5-14.3-2.2l-4.1-1.8c-1.5,0-2.5,1-2.5,1c-2,7-1,14-1,21c1,20,3,39,1,59c-1,16-3,32-5,48c-1,7,1,15,2,22 c1,10-4,20-0.2,29.2c0.5,1.2,1.6,4.6,2.7,5.4c3.8-2.8,6-10.1,8.7-14.3c3.5-5.4,7.8-10.6,11.9-15.3c8.6-9.8,17.5-19.4,26.6-28.6 c4.1-4.1,7.7-8.3,13-10.8c0.2,2.2,1.7,4,3.5,5.1c0.4,0.2,4.9,3.4,5.4,5.2c1,3.6,17.1,13.7,20.1,16.8c3.9,3.9,7.1,8.4,10.4,12.7 c7.2,9.3,15,17.3,23.9,25c21.3,18.5,44.4,35.1,64.1,55.4c6.4,6.6,10.9,13.9,18.2,19.5c3.8,3,10.9,9,15.6,9.3 c-0.2-2.3,0.6-4.5,1.9-6.4c7.1-10.4,22.4-21.2,34.6-23.1c6-0.9,12.3-1.8,18.5-1.8c0.5,0,1,0,1.6,0c7.9,0.2,11.4,4.6,18,7.1 c7,2.6,15.9,9.9,21.3,15.4c4.5,4.6,9.6,9.3,13.4,14.2c1.6,2.1,7.6,12.4,10.7,12.4c0,0,0.1,0,0.1,0c4.5-0.3,3.4-13.8,2.4-16.6 l-0.7-17.2c0.3-18.9-0.7-37.9-2.7-56.9c-1-13,2-26,1-39.4v-7.7c-1-21.9-2-44.9-1-66.9c2-21,5-41,5-62c0-4,0-8-0.2-11.8 c-0.1-2.6,0.2-6.3,2.5-8c-1.8,0.4-3.7,0.5-5.6,0.5c-7.5,0-15.2-2.4-22.4-2.9C356.1,29.2,353.9,29.1,351.6,29.1L351.6,29.1z M389,30.9c-1.1,0-2,0.3-2.7,0.8c1-0.2,2-0.5,3-0.8C389.2,30.9,389.1,30.9,389,30.9L389,30.9z'/%3E%3C/g%3E%3Cpath class='st3' d='M251.9,106c-5.1,5.6-7.2,16.1-7.9,23.3c-1,9.9-2.4,18,4.5,25.7c7.6,8.4,20.6,16.2,31.5,19.1 c5.7,1.6,12.3-1.2,17.7-2.3c13-2.8,19.6-10.7,25.3-22.8c6.9-14.4,1.1-25.8-4.7-39.3c-6.3-14.6-25.5-21.2-40.7-19.2 c-6.2,0.8-9.7,4.6-14.6,8C260.3,100.4,252.3,103.1,251.9,106z'/%3E%3C/svg%3E";

;// CONCATENATED MODULE: ../../packages/js/components/src/product-image/index.tsx


var _excluded = ["product", "width", "height", "className", "alt"];

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

/**
 * Use `ProductImage` to display a product's or variation's featured image.
 * If no image can be found, a placeholder matching the front-end image
 * placeholder will be displayed.
 */

var ProductImage = function ProductImage(_ref) {
  var product = _ref.product,
    _ref$width = _ref.width,
    width = _ref$width === void 0 ? 33 : _ref$width,
    _ref$height = _ref.height,
    height = _ref$height === void 0 ? 33 : _ref$height,
    _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    alt = _ref.alt,
    props = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  // The first returned image from the API is the featured/product image.
  var productImage = (0,lodash.get)(product, ['images', 0]) || (0,lodash.get)(product, ['image']);
  var src = productImage && productImage.src || false;
  var altText = alt || productImage && productImage.alt || '';
  var classes = classnames_default()('woocommerce-product-image', className, {
    'is-placeholder': !src
  });
  return (0,react.createElement)("img", (0,esm_extends/* default */.A)({
    className: classes,
    src: src || placeholderWhiteBackground,
    width: width,
    height: height,
    alt: altText
  }, props));
};
/* harmony default export */ const product_image = (ProductImage);

/***/ }),

/***/ "../../packages/js/components/src/search/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ search)
});

// UNUSED EXPORTS: Search

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js
var inherits = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js");
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);
// EXTERNAL MODULE: ../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js
var runtime = __webpack_require__("../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@3.7.1/node_modules/@wordpress/url/build-module/add-query-args.js + 3 modules
var add_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@3.7.1/node_modules/@wordpress/url/build-module/add-query-args.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+api-fetch@6.3.1/node_modules/@wordpress/api-fetch/build-module/index.js + 12 modules
var api_fetch_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+api-fetch@6.3.1/node_modules/@wordpress/api-fetch/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+interpolate-components@1.2.1_@types+react@17.0.71_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__("../../node_modules/.pnpm/@automattic+interpolate-components@1.2.1_@types+react@17.0.71_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js
var es_string_trim = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js
var html_entities_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/utils.ts









/**
 * External dependencies
 */



/**
 * Parse a string suggestion, split apart by where the first matching query is.
 * Used to display matched partial in bold.
 *
 * @param {string} suggestion The item's label as returned from the API.
 * @param {string} query      The search term to match in the string.
 * @return {Object} A list in three parts: before, match, and after.
 */
function computeSuggestionMatch(suggestion, query) {
  if (!query) {
    return null;
  }
  var indexOfMatch = suggestion.toLocaleLowerCase().indexOf(query.toLocaleLowerCase());
  return {
    suggestionBeforeMatch: (0,html_entities_build_module/* decodeEntities */.S)(suggestion.substring(0, indexOfMatch)),
    suggestionMatch: (0,html_entities_build_module/* decodeEntities */.S)(suggestion.substring(indexOfMatch, indexOfMatch + query.length)),
    suggestionAfterMatch: (0,html_entities_build_module/* decodeEntities */.S)(suggestion.substring(indexOfMatch + query.length))
  };
}
function getTaxCode(tax) {
  return [tax.country, tax.state, tax.name || (0,build_module.__)('TAX', 'woocommerce'), tax.priority].filter(Boolean).map(function (item) {
    return item === null || item === void 0 ? void 0 : item.toString().toUpperCase().trim();
  }).join('-');
}
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/attributes.tsx


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var completer = {
  name: 'attributes',
  className: 'woocommerce-search__product-result',
  options: function options(search) {
    var query = search ? {
      search: search,
      per_page: 10,
      orderby: 'count'
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/products/attributes', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(attribute) {
    return attribute.id;
  },
  getOptionKeywords: function getOptionKeywords(attribute) {
    return [attribute.name];
  },
  getFreeTextOptions: function getFreeTextOptions(query) {
    var label = (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name"
    }, (0,esm/* default */.A)({
      mixedString: (0,build_module.__)('All attributes with names that include {{query /}}', 'woocommerce'),
      components: {
        query: (0,react.createElement)("strong", {
          className: "components-form-token-field__suggestion-match"
        }, query)
      }
    }));
    var nameOption = {
      key: 'name',
      label: label,
      value: {
        id: query,
        name: query
      }
    };
    return [nameOption];
  },
  getOptionLabel: function getOptionLabel(attribute, query) {
    var match = computeSuggestionMatch(attribute.name, query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": attribute.name
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(attribute) {
    var value = {
      key: attribute.id,
      label: attribute.name
    };
    return value;
  }
};
/* harmony default export */ const attributes = (completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/categories.tsx


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var categories_completer = {
  name: 'categories',
  className: 'woocommerce-search__product-result',
  options: function options(search) {
    var query = search ? {
      search: search,
      per_page: 10,
      orderby: 'count'
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/products/categories', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(category) {
    return category.id;
  },
  getOptionKeywords: function getOptionKeywords(cat) {
    return [cat.name];
  },
  getFreeTextOptions: function getFreeTextOptions(query) {
    var label = (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name"
    }, (0,esm/* default */.A)({
      mixedString: (0,build_module.__)('All categories with titles that include {{query /}}', 'woocommerce'),
      components: {
        query: (0,react.createElement)("strong", {
          className: "components-form-token-field__suggestion-match"
        }, query)
      }
    }));
    var titleOption = {
      key: 'title',
      label: label,
      value: {
        id: query,
        name: query
      }
    };
    return [titleOption];
  },
  getOptionLabel: function getOptionLabel(cat, query) {
    var match = computeSuggestionMatch(cat.name, query);
    // @todo Bring back ProductImage, but allow for product category image
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": cat.name
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(cat) {
    var value = {
      key: cat.id,
      label: cat.name
    };
    return value;
  }
};
/* harmony default export */ const categories = (categories_completer);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../packages/js/components/src/flag/index.js
var flag = __webpack_require__("../../packages/js/components/src/flag/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/countries.tsx






/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


// Cache countries to avoid repeated requests.
var allCountries = null;
var isCountries = function isCountries(value) {
  return Array.isArray(value) && value.length > 0 && (0,esm_typeof/* default */.A)(value[0]) === 'object' && typeof value[0].code === 'string' && typeof value[0].name === 'string';
};
var countries_completer = {
  name: 'countries',
  className: 'woocommerce-search__country-result',
  isDebounced: true,
  options: function options() {
    // Returned cached countries if we've already received them.
    if (allCountries) {
      return Promise.resolve(allCountries);
    }
    // Make the request for country data.
    return (0,api_fetch_build_module/* default */.A)({
      path: '/wc-analytics/data/countries'
    }).then(function (result) {
      if (isCountries(result)) {
        // Cache the response.
        allCountries = result;
        return allCountries;
      }

      // If the response is not valid, return an empty array.
      // eslint-disable-next-line no-console
      console.warn('Invalid countries response', result);
      return [];
    });
  },
  getOptionIdentifier: function getOptionIdentifier(country) {
    return country.code;
  },
  getSearchExpression: function getSearchExpression(query) {
    return '^' + query;
  },
  getOptionKeywords: function getOptionKeywords(country) {
    return [country.code, (0,html_entities_build_module/* decodeEntities */.S)(country.name)];
  },
  getOptionLabel: function getOptionLabel(country, query) {
    var name = (0,html_entities_build_module/* decodeEntities */.S)(country.name);
    var match = computeSuggestionMatch(name, query);
    return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(flag/* default */.A, {
      key: "thumbnail",
      className: "woocommerce-search__result-thumbnail",
      code: country.code
      // @ts-expect-error TODO: migrate Flag component.
      ,

      size: 18,
      hideFromScreenReader: true
    }), (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": name
    }, query ? (0,react.createElement)(react.Fragment, null, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch) : name));
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(country) {
    var value = {
      key: country.code,
      label: (0,html_entities_build_module/* decodeEntities */.S)(country.name)
    };
    return value;
  }
};
/* harmony default export */ const countries = (countries_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/coupons.tsx

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var coupons_completer = {
  name: 'coupons',
  className: 'woocommerce-search__coupon-result',
  options: function options(search) {
    var query = search ? {
      search: search,
      per_page: 10
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/coupons', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(coupon) {
    return coupon.id;
  },
  getOptionKeywords: function getOptionKeywords(coupon) {
    return [coupon.code];
  },
  getFreeTextOptions: function getFreeTextOptions(query) {
    var label = (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name"
    }, (0,esm/* default */.A)({
      mixedString: (0,build_module.__)('All coupons with codes that include {{query /}}', 'woocommerce'),
      components: {
        query: (0,react.createElement)("strong", {
          className: "components-form-token-field__suggestion-match"
        }, query)
      }
    }));
    var codeOption = {
      key: 'code',
      label: label,
      value: {
        id: query,
        code: query
      }
    };
    return [codeOption];
  },
  getOptionLabel: function getOptionLabel(coupon, query) {
    var match = computeSuggestionMatch(coupon.code, query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": coupon.code
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(coupon) {
    var value = {
      key: coupon.id,
      label: coupon.code
    };
    return value;
  }
};
/* harmony default export */ const coupons = (coupons_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/customers.tsx


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var customers_completer = {
  name: 'customers',
  className: 'woocommerce-search__customers-result',
  options: function options(name) {
    var query = name ? {
      search: name,
      searchby: 'name',
      per_page: 10
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/customers', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(customer) {
    return customer.id;
  },
  getOptionKeywords: function getOptionKeywords(customer) {
    return [customer.name];
  },
  getFreeTextOptions: function getFreeTextOptions(query) {
    var label = (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name"
    }, (0,esm/* default */.A)({
      mixedString: (0,build_module.__)('All customers with names that include {{query /}}', 'woocommerce'),
      components: {
        query: (0,react.createElement)("strong", {
          className: "components-form-token-field__suggestion-match"
        }, query)
      }
    }));
    var nameOption = {
      key: 'name',
      label: label,
      value: {
        id: query,
        name: query
      }
    };
    return [nameOption];
  },
  getOptionLabel: function getOptionLabel(customer, query) {
    var match = computeSuggestionMatch(customer.name, query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": customer.name
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(customer) {
    return {
      key: customer.id,
      label: customer.name
    };
  }
};
/* harmony default export */ const customers = (customers_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/download-ips.tsx

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var download_ips_completer = {
  name: 'download-ips',
  className: 'woocommerce-search__download-ip-result',
  options: function options(match) {
    var query = match ? {
      match: match
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/data/download-ips', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(download) {
    return download.user_ip_address;
  },
  getOptionKeywords: function getOptionKeywords(download) {
    return [download.user_ip_address];
  },
  getOptionLabel: function getOptionLabel(download, query) {
    var match = computeSuggestionMatch(download.user_ip_address, query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": download.user_ip_address
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  getOptionCompletion: function getOptionCompletion(download) {
    return {
      key: download.user_ip_address,
      label: download.user_ip_address
    };
  }
};
/* harmony default export */ const download_ips = (download_ips_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/emails.tsx

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var emails_completer = {
  name: 'emails',
  className: 'woocommerce-search__emails-result',
  options: function options(search) {
    var query = search ? {
      search: search,
      searchby: 'email',
      per_page: 10
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/customers', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(customer) {
    return customer.id;
  },
  getOptionKeywords: function getOptionKeywords(customer) {
    return [customer.email];
  },
  getOptionLabel: function getOptionLabel(customer, query) {
    var match = computeSuggestionMatch(customer.email, query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": customer.email
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(customer) {
    return {
      key: customer.id,
      label: customer.email
    };
  }
};
/* harmony default export */ const emails = (emails_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/orders.tsx

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var orders_completer = {
  name: 'orders',
  className: 'woocommerce-search__order-result',
  options: function options(search) {
    var query = search ? {
      number: search,
      per_page: 10
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/orders', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(order) {
    return order.id;
  },
  getOptionKeywords: function getOptionKeywords(order) {
    return ['#' + order.number];
  },
  getOptionLabel: function getOptionLabel(order, query) {
    var match = computeSuggestionMatch('#' + order.number, query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": '#' + order.number
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  getOptionCompletion: function getOptionCompletion(order) {
    return {
      key: order.id,
      label: '#' + order.number
    };
  }
};
/* harmony default export */ const orders = (orders_completer);
// EXTERNAL MODULE: ../../packages/js/components/src/product-image/index.tsx + 1 modules
var product_image = __webpack_require__("../../packages/js/components/src/product-image/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/product.tsx


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


var product_completer = {
  name: 'products',
  className: 'woocommerce-search__product-result',
  options: function options(search) {
    var query = search ? {
      search: search,
      per_page: 10,
      orderby: 'popularity'
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/products', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(product) {
    return product.id;
  },
  getOptionKeywords: function getOptionKeywords(product) {
    return [product.name, product.sku];
  },
  getFreeTextOptions: function getFreeTextOptions(query) {
    var label = (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name"
    }, (0,esm/* default */.A)({
      mixedString: (0,build_module.__)('All products with titles that include {{query /}}', 'woocommerce'),
      components: {
        query: (0,react.createElement)("strong", {
          className: "components-form-token-field__suggestion-match"
        }, query)
      }
    }));
    var titleOption = {
      key: 'title',
      label: label,
      value: {
        id: query,
        name: query
      }
    };
    return [titleOption];
  },
  getOptionLabel: function getOptionLabel(product, query) {
    var match = computeSuggestionMatch(product.name, query);
    return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(product_image/* default */.A, {
      key: "thumbnail",
      className: "woocommerce-search__result-thumbnail",
      product: product,
      width: 18,
      height: 18,
      alt: ""
    }), (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": product.name
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch));
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(product) {
    var value = {
      key: product.id,
      label: product.name
    };
    return value;
  }
};
/* harmony default export */ const product = (product_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/taxes.tsx

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var taxes_completer = {
  name: 'taxes',
  className: 'woocommerce-search__tax-result',
  options: function options(search) {
    var query = search ? {
      code: search,
      per_page: 10
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/taxes', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(tax) {
    return tax.id;
  },
  getOptionKeywords: function getOptionKeywords(tax) {
    return [tax.id, getTaxCode(tax)];
  },
  getFreeTextOptions: function getFreeTextOptions(query) {
    var label = (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name"
    }, (0,esm/* default */.A)({
      mixedString: (0,build_module.__)('All taxes with codes that include {{query /}}', 'woocommerce'),
      components: {
        query: (0,react.createElement)("strong", {
          className: "components-form-token-field__suggestion-match"
        }, query)
      }
    }));
    var codeOption = {
      key: 'code',
      label: label,
      value: {
        id: query,
        name: query
      }
    };
    return [codeOption];
  },
  getOptionLabel: function getOptionLabel(tax, query) {
    var match = computeSuggestionMatch(getTaxCode(tax), query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": tax.code
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(tax) {
    var value = {
      key: tax.id,
      label: getTaxCode(tax)
    };
    return value;
  }
};
/* harmony default export */ const taxes = (taxes_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/usernames.tsx

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var usernames_completer = {
  name: 'usernames',
  className: 'woocommerce-search__usernames-result',
  options: function options(search) {
    var query = search ? {
      search: search,
      searchby: 'username',
      per_page: 10
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/customers', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(customer) {
    return customer.id;
  },
  getOptionKeywords: function getOptionKeywords(customer) {
    return [customer.username];
  },
  getOptionLabel: function getOptionLabel(customer, query) {
    var match = computeSuggestionMatch(customer.username, query);
    return (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": customer.username
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch);
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(customer) {
    return {
      key: customer.id,
      label: customer.username
    };
  }
};
/* harmony default export */ const usernames = (usernames_completer);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/variable-product.tsx











function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var variable_product_completer = _objectSpread(_objectSpread({}, product), {}, {
  name: 'products',
  options: function options(search) {
    var query = search ? {
      search: search,
      per_page: 10,
      orderby: 'popularity',
      type: 'variable'
    } : {};
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/products', query)
    });
  }
});
/* harmony default export */ const variable_product = (variable_product_completer);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js
var es_symbol_description = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js");
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 3 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/search/autocompleters/variations.tsx









/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


/**
 * Create a variation name by concatenating each of the variation's
 * attribute option strings.
 *
 * @param {Object} variation            - variation returned by the api
 * @param {Array}  variation.attributes - attribute objects, with option property.
 * @param {string} variation.name       - name of variation.
 * @return {string} - formatted variation name
 */
function getVariationName(_ref) {
  var attributes = _ref.attributes,
    name = _ref.name;
  var separator = window.wcSettings.variationTitleAttributesSeparator || ' - ';
  if (name.indexOf(separator) > -1) {
    return name;
  }
  var attributeList = attributes.map(function (_ref2) {
    var option = _ref2.option;
    return option;
  }).join(', ');
  return attributeList ? name + separator + attributeList : name;
}
var variations_completer = {
  name: 'variations',
  className: 'woocommerce-search__product-result',
  options: function options(search) {
    var query = search ? {
      search: search,
      per_page: 30,
      _fields: ['attributes', 'description', 'id', 'name', 'sku']
    } : {};
    var product = (0,src/* getQuery */.$Z)().products;

    // Product was specified, search only its variations.
    if (product) {
      if (product.includes(',')) {
        // eslint-disable-next-line no-console
        console.warn('Invalid product id supplied to Variations autocompleter');
      }
      return (0,api_fetch_build_module/* default */.A)({
        path: (0,add_query_args/* addQueryArgs */.F)("/wc-analytics/products/".concat(product, "/variations"), query)
      });
    }

    // Product was not specified, search all variations.
    return (0,api_fetch_build_module/* default */.A)({
      path: (0,add_query_args/* addQueryArgs */.F)('/wc-analytics/variations', query)
    });
  },
  isDebounced: true,
  getOptionIdentifier: function getOptionIdentifier(variation) {
    return variation.id;
  },
  getOptionKeywords: function getOptionKeywords(variation) {
    return [getVariationName(variation), variation.sku];
  },
  getOptionLabel: function getOptionLabel(variation, query) {
    var match = computeSuggestionMatch(getVariationName(variation), query);
    return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(product_image/* default */.A, {
      key: "thumbnail",
      className: "woocommerce-search__result-thumbnail",
      product: variation,
      width: 18,
      height: 18,
      alt: ""
    }), (0,react.createElement)("span", {
      key: "name",
      className: "woocommerce-search__result-name",
      "aria-label": variation.description
    }, match === null || match === void 0 ? void 0 : match.suggestionBeforeMatch, (0,react.createElement)("strong", {
      className: "components-form-token-field__suggestion-match"
    }, match === null || match === void 0 ? void 0 : match.suggestionMatch), match === null || match === void 0 ? void 0 : match.suggestionAfterMatch));
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion: function getOptionCompletion(variation) {
    return {
      key: variation.id,
      label: getVariationName(variation)
    };
  }
};
/* harmony default export */ const variations = (variations_completer);
;// CONCATENATED MODULE: ../../packages/js/components/src/search/index.tsx






















function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function _isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;
  try {
    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


/**
 * A search box which autocompletes results while typing, allowing for the user to select an existing object
 * (product, order, customer, etc). Currently only products are supported.
 */
var Search = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(Search, _Component);
  var _super = _createSuper(Search);
  function Search(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, Search);
    _this = _super.call(this, props);
    _this.state = {
      options: []
    };
    _this.appendFreeTextSearch = _this.appendFreeTextSearch.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.fetchOptions = _this.fetchOptions.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateSelected = _this.updateSelected.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(Search, [{
    key: "getAutocompleter",
    value: function getAutocompleter() {
      switch (this.props.type) {
        case 'attributes':
          return attributes;
        case 'categories':
          return categories;
        case 'countries':
          return countries;
        case 'coupons':
          return coupons;
        case 'customers':
          return customers;
        case 'downloadIps':
          return download_ips;
        case 'emails':
          return emails;
        case 'orders':
          return orders;
        case 'products':
          return product;
        case 'taxes':
          return taxes;
        case 'usernames':
          return usernames;
        case 'variableProducts':
          return variable_product;
        case 'variations':
          return variations;
        case 'custom':
          if (!this.props.autocompleter || (0,esm_typeof/* default */.A)(this.props.autocompleter) !== 'object') {
            throw new Error("Invalid autocompleter provided to Search component, it requires a completer object when using 'custom' type.");
          }
          return this.props.autocompleter;
        default:
          throw new Error("No autocompleter found for type: ".concat(this.props.type));
      }
    }
  }, {
    key: "getFormattedOptions",
    value: function getFormattedOptions(options, query) {
      var autocompleter = this.getAutocompleter();
      var formattedOptions = [];
      options.forEach(function (option) {
        var formattedOption = {
          key: autocompleter.getOptionIdentifier(option),
          label: autocompleter.getOptionLabel(option, query),
          keywords: autocompleter.getOptionKeywords(option).filter(Boolean),
          value: option
        };
        formattedOptions.push(formattedOption);
      });
      return formattedOptions;
    }
  }, {
    key: "fetchOptions",
    value: function fetchOptions(previousOptions, query) {
      var _this2 = this;
      if (!query) {
        return [];
      }
      var autocompleterOptions = this.getAutocompleter().options;

      // Support arrays, sync- & async functions that returns an array.
      var resolvedOptions = Promise.resolve(typeof autocompleterOptions === 'function' ? autocompleterOptions(query) : autocompleterOptions || []);
      return resolvedOptions.then( /*#__PURE__*/function () {
        var _ref = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee(response) {
          var options;
          return regenerator_default().wrap(function _callee$(_context) {
            while (1) switch (_context.prev = _context.next) {
              case 0:
                options = _this2.getFormattedOptions(response, query);
                _this2.setState({
                  options: options
                });
                return _context.abrupt("return", options);
              case 3:
              case "end":
                return _context.stop();
            }
          }, _callee);
        }));
        return function (_x) {
          return _ref.apply(this, arguments);
        };
      }());
    }
  }, {
    key: "updateSelected",
    value: function updateSelected(selected) {
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      var _this$props$onChange = this.props.onChange,
        onChange = _this$props$onChange === void 0 ? function (_option) {} : _this$props$onChange;
      var autocompleter = this.getAutocompleter();
      var formattedSelections = selected.map(function (option) {
        return option.value ? autocompleter.getOptionCompletion(option.value) : option;
      });
      onChange(formattedSelections);
    }
  }, {
    key: "appendFreeTextSearch",
    value: function appendFreeTextSearch(options, query) {
      var allowFreeTextSearch = this.props.allowFreeTextSearch;
      if (!query || !query.length) {
        return [];
      }
      var autocompleter = this.getAutocompleter();
      if (!allowFreeTextSearch || typeof autocompleter.getFreeTextOptions !== 'function') {
        return options;
      }
      return [].concat((0,toConsumableArray/* default */.A)(autocompleter.getFreeTextOptions(query)), (0,toConsumableArray/* default */.A)(options));
    }
  }, {
    key: "render",
    value: function render() {
      var autocompleter = this.getAutocompleter();
      var _this$props = this.props,
        className = _this$props.className,
        inlineTags = _this$props.inlineTags,
        placeholder = _this$props.placeholder,
        selected = _this$props.selected,
        showClearButton = _this$props.showClearButton,
        staticResults = _this$props.staticResults,
        disabled = _this$props.disabled,
        multiple = _this$props.multiple;
      var options = this.state.options;
      var inputType = autocompleter.inputType ? autocompleter.inputType : 'text';
      return (0,react.createElement)("div", null, (0,react.createElement)(select_control/* default */.A, {
        className: classnames_default()('woocommerce-search', className, {
          'is-static-results': staticResults
        }),
        disabled: disabled,
        hideBeforeSearch: true,
        inlineTags: inlineTags,
        isSearchable: true,
        getSearchExpression: autocompleter.getSearchExpression,
        multiple: multiple,
        placeholder: placeholder,
        onChange: this.updateSelected,
        onFilter: this.appendFreeTextSearch,
        onSearch: this.fetchOptions,
        options: options,
        searchDebounceTime: 500,
        searchInputType: inputType,
        selected: selected,
        showClearButton: showClearButton
      }));
    }
  }]);
  return Search;
}(react.Component);
(0,defineProperty/* default */.A)(Search, "propTypes", {
  /**
   * Render additional options in the autocompleter to allow free text entering depending on the type.
   */
  allowFreeTextSearch: (prop_types_default()).bool,
  /**
   * Class name applied to parent div.
   */
  className: (prop_types_default()).string,
  /**
   * Function called when selected results change, passed result list.
   */
  onChange: (prop_types_default()).func,
  /**
   * The object type to be used in searching.
   */
  type: prop_types_default().oneOf(['attributes', 'categories', 'countries', 'coupons', 'customers', 'downloadIps', 'emails', 'orders', 'products', 'taxes', 'usernames', 'variableProducts', 'variations', 'custom']).isRequired,
  /**
   * The custom autocompleter to be used in searching when type is 'custom'
   */
  autocompleter: (prop_types_default()).object,
  /**
   * A placeholder for the search input.
   */
  placeholder: (prop_types_default()).string,
  /**
   * An array of objects describing selected values or optionally a string for a single value.
   * If the label of the selected value is omitted, the Tag of that value will not
   * be rendered inside the search box.
   */
  selected: prop_types_default().oneOfType([(prop_types_default()).string, prop_types_default().arrayOf(prop_types_default().shape({
    key: prop_types_default().oneOfType([(prop_types_default()).number, (prop_types_default()).string]).isRequired,
    label: (prop_types_default()).string
  }))]),
  /**
   * Render tags inside input, otherwise render below input.
   */
  inlineTags: (prop_types_default()).bool,
  /**
   * Render a 'Clear' button next to the input box to remove its contents.
   */
  showClearButton: (prop_types_default()).bool,
  /**
   * Render results list positioned statically instead of absolutely.
   */
  staticResults: (prop_types_default()).bool,
  /**
   * Whether the control is disabled or not.
   */
  disabled: (prop_types_default()).bool,
  /**
   * Allow multiple option selections.
   */
  multiple: (prop_types_default()).bool
});
(0,defineProperty/* default */.A)(Search, "defaultProps", {
  allowFreeTextSearch: false,
  onChange: lodash.noop,
  selected: [],
  inlineTags: false,
  showClearButton: false,
  staticResults: false,
  disabled: false,
  multiple: true
});
/* harmony default export */ const search = (Search);
try {
    // @ts-ignore
    Search.displayName = "Search";
    // @ts-ignore
    Search.__docgenInfo = { "description": "A search box which autocompletes results while typing, allowing for the user to select an existing object\n(product, order, customer, etc). Currently only products are supported.", "displayName": "Search", "props": { "type": { "defaultValue": null, "description": "", "name": "type", "required": true, "type": { "name": "enum", "value": [{ "value": "\"products\"" }, { "value": "\"countries\"" }, { "value": "\"categories\"" }, { "value": "\"custom\"" }, { "value": "\"attributes\"" }, { "value": "\"coupons\"" }, { "value": "\"customers\"" }, { "value": "\"downloadIps\"" }, { "value": "\"emails\"" }, { "value": "\"orders\"" }, { "value": "\"taxes\"" }, { "value": "\"usernames\"" }, { "value": "\"variableProducts\"" }, { "value": "\"variations\"" }] } }, "allowFreeTextSearch": { "defaultValue": { value: "false" }, "description": "", "name": "allowFreeTextSearch", "required": false, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": false, "type": { "name": "((value: Option | OptionCompletionValue[]) => void)" } }, "autocompleter": { "defaultValue": null, "description": "", "name": "autocompleter", "required": false, "type": { "name": "AutoCompleter" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": { value: "[]" }, "description": "", "name": "selected", "required": false, "type": { "name": "string | { key: string | number; label?: string; }[]" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "staticResults": { "defaultValue": { value: "false" }, "description": "", "name": "staticResults", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": { value: "false" }, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "true" }, "description": "", "name": "multiple", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/search/index.tsx#Search"] = { docgenInfo: Search.__docgenInfo, name: "Search", path: "../../packages/js/components/src/search/index.tsx#Search" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);