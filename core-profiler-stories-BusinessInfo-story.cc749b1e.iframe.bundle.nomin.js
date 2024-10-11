"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9891],{

/***/ "../../plugins/woocommerce-admin/client/core-profiler/components/heading/heading.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   D: () => (/* binding */ Heading)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var Heading = function Heading(_ref) {
  var className = _ref.className,
    title = _ref.title,
    subTitle = _ref.subTitle;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-profiler-heading', className)
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
    className: "woocommerce-profiler-heading__title"
  }, title), subTitle && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
    className: "woocommerce-profiler-heading__subtitle"
  }, subTitle));
};
try {
    // @ts-ignore
    Heading.displayName = "Heading";
    // @ts-ignore
    Heading.__docgenInfo = { "description": "", "displayName": "Heading", "props": { "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "string | Element" } }, "subTitle": { "defaultValue": null, "description": "", "name": "subTitle", "required": false, "type": { "name": "string | Element" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce-admin/client/core-profiler/components/heading/heading.tsx#Heading"] = { docgenInfo: Heading.__docgenInfo, name: "Heading", path: "../../plugins/woocommerce-admin/client/core-profiler/components/heading/heading.tsx#Heading" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce-admin/client/core-profiler/components/navigation/navigation.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  V: () => (/* binding */ Navigation)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
;// CONCATENATED MODULE: ../../plugins/woocommerce-admin/client/core-profiler/components/navigation/woologo.tsx

/* eslint-disable max-len */
var WooLogo = function WooLogo() {
  return (0,react.createElement)("svg", {
    preserveAspectRatio: "xMidYMid",
    version: "1.1",
    viewBox: "0 0 256 153",
    xmlns: "http://www.w3.org/2000/svg",
    className: "wc-icon wc-icon__woo-logo"
  }, (0,react.createElement)("path", {
    d: "m23.759 0h208.38c13.187 0 23.863 10.675 23.863 23.863v79.542c0 13.187-10.675 23.863-23.863 23.863h-74.727l10.257 25.118-45.109-25.118h-98.695c-13.187 0-23.863-10.675-23.863-23.863v-79.542c-0.10466-13.083 10.571-23.863 23.758-23.863z",
    fill: "#7f54b3"
  }), (0,react.createElement)("path", {
    d: "m14.578 21.75c1.4569-1.9772 3.6423-3.0179 6.5561-3.226 5.3073-0.41626 8.3252 2.0813 9.0537 7.4927 3.226 21.75 6.7642 40.169 10.511 55.259l22.79-43.395c2.0813-3.9545 4.6829-6.0358 7.8049-6.2439 4.5789-0.3122 7.3886 2.6016 8.5333 8.7415 2.6016 13.841 5.9317 25.6 9.8862 35.59 2.7057-26.433 7.2846-45.476 13.737-57.236 1.561-2.9138 3.8504-4.3707 6.8683-4.5789 2.3935-0.20813 4.5789 0.52033 6.5561 2.0813 1.9772 1.561 3.0179 3.5382 3.226 5.9317 0.10406 1.8732-0.20813 3.4341-1.0407 4.9951-4.0585 7.4927-7.3886 20.085-10.094 37.567-2.6016 16.963-3.5382 30.179-2.9138 39.649 0.20813 2.6016-0.20813 4.8911-1.2488 6.8683-1.2488 2.2894-3.122 3.5382-5.5154 3.7463-2.7057 0.20813-5.5154-1.0406-8.2211-3.8504-9.678-9.8862-17.379-24.663-22.998-44.332-6.7642 13.32-11.759 23.311-14.985 29.971-6.1398 11.759-11.343 17.795-15.714 18.107-2.8098 0.20813-5.2033-2.1854-7.2846-7.1805-5.3073-13.633-11.031-39.961-17.171-78.985-0.41626-2.7057 0.20813-5.0992 1.665-6.9724zm223.64 16.338c-3.7463-6.5561-9.2618-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0.10406-8.1171-1.665-14.881-4.9951-20.501zm-13.112 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93659-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0 2.7057-0.20813 5.3073-0.72846 7.7008zm-52.033-28.826c-3.7463-6.5561-9.3659-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0-8.1171-1.665-14.881-4.9951-20.501zm-13.216 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93658-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0.10406 2.7057-0.20813 5.3073-0.72846 7.7008z",
    fill: "#fff"
  }));
};
/* eslint-enable max-len */

/* harmony default export */ const woologo = (WooLogo);
;// CONCATENATED MODULE: ../../plugins/woocommerce-admin/client/core-profiler/components/progress-bar/progress-bar.tsx

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


var ProgressBar = function ProgressBar(_ref) {
  var _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    _ref$percent = _ref.percent,
    percent = _ref$percent === void 0 ? 0 : _ref$percent,
    _ref$color = _ref.color,
    color = _ref$color === void 0 ? '#674399' : _ref$color,
    _ref$bgcolor = _ref.bgcolor,
    bgcolor = _ref$bgcolor === void 0 ? 'var(--wp-admin-theme-color)' : _ref$bgcolor;
  var containerStyles = {
    backgroundColor: bgcolor
  };
  var fillerStyles = {
    backgroundColor: color,
    width: "".concat(percent, "%"),
    display: percent === 0 ? 'none' : 'inherit'
  };
  return (0,react.createElement)("div", {
    className: "woocommerce-profiler-progress-bar ".concat(className)
  }, (0,react.createElement)("div", {
    className: "woocommerce-profiler-progress-bar__container",
    style: containerStyles
  }, (0,react.createElement)("div", {
    className: "woocommerce-profiler-progress-bar__filler",
    style: fillerStyles
  })));
};
/* harmony default export */ const progress_bar = (ProgressBar);
try {
    // @ts-ignore
    progressbar.displayName = "progressbar";
    // @ts-ignore
    progressbar.__docgenInfo = { "description": "", "displayName": "progressbar", "props": { "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "percent": { "defaultValue": { value: "0" }, "description": "", "name": "percent", "required": false, "type": { "name": "number" } }, "color": { "defaultValue": { value: "#674399" }, "description": "", "name": "color", "required": false, "type": { "name": "string" } }, "bgcolor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "", "name": "bgcolor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce-admin/client/core-profiler/components/progress-bar/progress-bar.tsx#progressbar"] = { docgenInfo: progressbar.__docgenInfo, name: "progressbar", path: "../../plugins/woocommerce-admin/client/core-profiler/components/progress-bar/progress-bar.tsx#progressbar" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../plugins/woocommerce-admin/client/core-profiler/components/navigation/navigation.tsx

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



var Navigation = function Navigation(_ref) {
  var _ref$percentage = _ref.percentage,
    percentage = _ref$percentage === void 0 ? 0 : _ref$percentage,
    onSkip = _ref.onSkip,
    _ref$skipText = _ref.skipText,
    skipText = _ref$skipText === void 0 ? (0,build_module.__)('Skip this step', 'woocommerce') : _ref$skipText,
    _ref$showProgress = _ref.showProgress,
    showProgress = _ref$showProgress === void 0 ? true : _ref$showProgress,
    _ref$showLogo = _ref.showLogo,
    showLogo = _ref$showLogo === void 0 ? true : _ref$showLogo,
    _ref$classNames = _ref.classNames,
    classNames = _ref$classNames === void 0 ? {} : _ref$classNames,
    _ref$progressBarColor = _ref.progressBarColor,
    progressBarColor = _ref$progressBarColor === void 0 ? 'var(--wp-admin-theme-color)' : _ref$progressBarColor;
  return (0,react.createElement)("div", {
    className: (0,clsx/* default */.A)('woocommerce-profiler-navigation-container', classNames)
  }, showProgress && (0,react.createElement)(progress_bar, {
    className: 'progress-bar',
    percent: percentage,
    color: progressBarColor,
    bgcolor: 'transparent'
  }), (0,react.createElement)("div", {
    className: "woocommerce-profiler-navigation"
  }, (0,react.createElement)("div", {
    className: "woocommerce-profiler-navigation-col-left"
  }, showLogo && (0,react.createElement)("span", {
    className: "woologo"
  }, (0,react.createElement)(woologo, null))), (0,react.createElement)("div", {
    className: "woocommerce-profiler-navigation-col-right"
  }, typeof onSkip === 'function' && (0,react.createElement)(build_module_button/* default */.A, {
    onClick: onSkip,
    className: (0,clsx/* default */.A)('woocommerce-profiler-navigation-skip-link', classNames.mobile ? 'mobile' : ''),
    isLink: true
  }, skipText))));
};
try {
    // @ts-ignore
    Navigation.displayName = "Navigation";
    // @ts-ignore
    Navigation.__docgenInfo = { "description": "", "displayName": "Navigation", "props": { "onSkip": { "defaultValue": null, "description": "", "name": "onSkip", "required": false, "type": { "name": "(() => void)" } }, "percentage": { "defaultValue": { value: "0" }, "description": "", "name": "percentage", "required": false, "type": { "name": "number" } }, "previous": { "defaultValue": null, "description": "", "name": "previous", "required": false, "type": { "name": "string" } }, "showProgress": { "defaultValue": { value: "true" }, "description": "", "name": "showProgress", "required": false, "type": { "name": "boolean" } }, "showLogo": { "defaultValue": { value: "true" }, "description": "", "name": "showLogo", "required": false, "type": { "name": "boolean" } }, "classNames": { "defaultValue": { value: "{}" }, "description": "", "name": "classNames", "required": false, "type": { "name": "{ mobile?: boolean; }" } }, "skipText": { "defaultValue": { value: "__( 'Skip this step', 'woocommerce' )" }, "description": "", "name": "skipText", "required": false, "type": { "name": "string" } }, "progressBarColor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "", "name": "progressBarColor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce-admin/client/core-profiler/components/navigation/navigation.tsx#Navigation"] = { docgenInfo: Navigation.__docgenInfo, name: "Navigation", path: "../../plugins/woocommerce-admin/client/core-profiler/components/navigation/navigation.tsx#Navigation" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce-admin/client/core-profiler/stories/WithSetupWizardLayout.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   b: () => (/* binding */ WithSetupWizardLayout)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var WithSetupWizardLayout = function WithSetupWizardLayout(Story) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "woocommerce-profile-wizard__body woocommerce-admin-full-screen"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Story, null));
};
try {
    // @ts-ignore
    WithSetupWizardLayout.displayName = "WithSetupWizardLayout";
    // @ts-ignore
    WithSetupWizardLayout.__docgenInfo = { "description": "", "displayName": "WithSetupWizardLayout", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce-admin/client/core-profiler/stories/WithSetupWizardLayout.tsx#WithSetupWizardLayout"] = { docgenInfo: WithSetupWizardLayout.__docgenInfo, name: "WithSetupWizardLayout", path: "../../plugins/woocommerce-admin/client/core-profiler/stories/WithSetupWizardLayout.tsx#WithSetupWizardLayout" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce-admin/client/core-profiler/stories/BusinessInfo.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ BusinessInfo_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/notice/index.js + 2 modules
var notice = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/notice/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+components@2.1.1_@types+react@17.0.71_@wordpress+data@6.6.1_react@17.0.2__react-d_midwt6ur2tpvzu6mq7vyq64idi/node_modules/@automattic/components/dist/esm/forms/form-input-validation/index.js + 6 modules
var form_input_validation = __webpack_require__("../../node_modules/.pnpm/@automattic+components@2.1.1_@types+react@17.0.71_@wordpress+data@6.6.1_react@17.0.2__react-d_midwt6ur2tpvzu6mq7vyq64idi/node_modules/@automattic/components/dist/esm/forms/form-input-validation/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js
var create_interpolate_element = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js");
// EXTERNAL MODULE: ../../packages/js/onboarding/build-module/index.js + 39 modules
var onboarding_build_module = __webpack_require__("../../packages/js/onboarding/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js
var html_entities_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/zod@3.22.4/node_modules/zod/lib/index.mjs
var lib = __webpack_require__("../../node_modules/.pnpm/zod@3.22.4/node_modules/zod/lib/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../plugins/woocommerce-admin/client/core-profiler/components/heading/heading.tsx
var heading = __webpack_require__("../../plugins/woocommerce-admin/client/core-profiler/components/heading/heading.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce-admin/client/core-profiler/components/navigation/navigation.tsx + 2 modules
var navigation = __webpack_require__("../../plugins/woocommerce-admin/client/core-profiler/components/navigation/navigation.tsx");
;// CONCATENATED MODULE: ../../plugins/woocommerce-admin/client/core-profiler/pages/BusinessInfo.tsx







/**
 * External dependencies
 */











/**
 * Internal dependencies
 */




/** These are some store names that are known to be set by default and not likely to be used as actual names */
var POSSIBLY_DEFAULT_STORE_NAMES = (/* unused pure expression or super */ null && ([undefined, 'woocommerce', 'Site Title', '']));
var industryChoices = [{
  label: (0,build_module.__)('Clothing and accessories', 'woocommerce'),
  key: 'clothing_and_accessories'
}, {
  label: (0,build_module.__)('Food and drink', 'woocommerce'),
  key: 'food_and_drink'
}, {
  label: (0,build_module.__)('Electronics and computers', 'woocommerce'),
  key: 'electronics_and_computers'
}, {
  label: (0,build_module.__)('Health and beauty', 'woocommerce'),
  key: 'health_and_beauty'
}, {
  label: (0,build_module.__)('Education and learning', 'woocommerce'),
  key: 'education_and_learning'
}, {
  label: (0,build_module.__)('Home, furniture and garden', 'woocommerce'),
  key: 'home_furniture_and_garden'
}, {
  label: (0,build_module.__)('Arts and crafts', 'woocommerce'),
  key: 'arts_and_crafts'
}, {
  label: (0,build_module.__)('Sports and recreation', 'woocommerce'),
  key: 'sports_and_recreation'
}, {
  label: (0,build_module.__)('Other', 'woocommerce'),
  key: 'other'
}];
var selectIndustryMapping = {
  im_just_starting_my_business: (0,build_module.__)('What type of products or services do you plan to sell?', 'woocommerce'),
  im_already_selling: (0,build_module.__)('Which industry is your business in?', 'woocommerce'),
  im_setting_up_a_store_for_a_client: (0,build_module.__)('Which industry is your client’s business in?', 'woocommerce')
};
var BusinessInfo = function BusinessInfo(_ref) {
  var context = _ref.context,
    navigationProgress = _ref.navigationProgress,
    sendEvent = _ref.sendEvent;
  var geolocatedLocation = context.geolocatedLocation,
    businessChoice = context.userProfile.businessChoice,
    businessInfo = context.businessInfo,
    countries = context.countries,
    _context$onboardingPr = context.onboardingProfile,
    _context$onboardingPr2 = _context$onboardingPr === void 0 ? {} : _context$onboardingPr,
    _context$onboardingPr3 = _context$onboardingPr2.is_store_country_set,
    isStoreCountrySet = _context$onboardingPr3 === void 0 ? false : _context$onboardingPr3,
    _context$onboardingPr4 = _context$onboardingPr2.industry,
    industryFromOnboardingProfile = _context$onboardingPr4 === void 0 ? [] : _context$onboardingPr4,
    _context$onboardingPr5 = _context$onboardingPr2.business_choice,
    businessChoiceFromOnboardingProfile = _context$onboardingPr5 === void 0 ? '' : _context$onboardingPr5,
    _context$onboardingPr6 = _context$onboardingPr2.is_agree_marketing,
    isOptInMarketingFromOnboardingProfile = _context$onboardingPr6 === void 0 ? false : _context$onboardingPr6,
    _context$onboardingPr7 = _context$onboardingPr2.store_email,
    storeEmailAddressFromOnboardingProfile = _context$onboardingPr7 === void 0 ? '' : _context$onboardingPr7,
    currentUserEmail = context.currentUserEmail;
  var _useState = (0,react.useState)(businessInfo.storeName || ''),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    storeName = _useState2[0],
    setStoreName = _useState2[1];
  var _useState3 = (0,react.useState)({
      key: '',
      label: ''
    }),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    storeCountry = _useState4[0],
    setStoreCountry = _useState4[1];
  (0,react.useEffect)(function () {
    if (isStoreCountrySet) {
      var previouslyStoredCountryOption = countries.find(function (country) {
        return country.key === businessInfo.location;
      });
      setStoreCountry(previouslyStoredCountryOption || {
        key: '',
        label: ''
      });
    }
  }, [businessInfo.location, countries, isStoreCountrySet]);
  var _useState5 = (0,react.useState)({
      key: '',
      label: ''
    }),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    geolocationMatch = _useState6[0],
    setGeolocationMatch = _useState6[1];
  (0,react.useEffect)(function () {
    if (geolocatedLocation) {
      var foundCountryOption = (0,onboarding_build_module/* findCountryOption */.b$)(countries, geolocatedLocation);
      if (foundCountryOption) {
        setGeolocationMatch(foundCountryOption);
        if (!isStoreCountrySet) {
          setStoreCountry(foundCountryOption);
        }
      }
    }
  }, [countries, isStoreCountrySet, geolocatedLocation]);
  var geolocationOverruled = geolocatedLocation && (0,onboarding_build_module/* getCountry */.JJ)(storeCountry.key) !== (0,onboarding_build_module/* getCountry */.JJ)(geolocationMatch.key);
  var _useState7 = (0,react.useState)(industryFromOnboardingProfile ? industryChoices.find(function (choice) {
      return choice.key === industryFromOnboardingProfile[0];
    }) : undefined),
    _useState8 = (0,slicedToArray/* default */.A)(_useState7, 2),
    industry = _useState8[0],
    setIndustry = _useState8[1];
  var selectCountryLabel = (0,build_module.__)('Select country/region', 'woocommerce');
  var selectIndustryQuestionLabel = selectIndustryMapping[businessChoice || businessChoiceFromOnboardingProfile || 'im_just_starting_my_business'];
  var _useState9 = (0,react.useState)(false),
    _useState10 = (0,slicedToArray/* default */.A)(_useState9, 2),
    dismissedGeolocationNotice = _useState10[0],
    setDismissedGeolocationNotice = _useState10[1];
  var _useState11 = (0,react.useState)(false),
    _useState12 = (0,slicedToArray/* default */.A)(_useState11, 2),
    hasSubmitted = _useState12[0],
    setHasSubmitted = _useState12[1];
  var _useState13 = (0,react.useState)(false),
    _useState14 = (0,slicedToArray/* default */.A)(_useState13, 2),
    isEmailInvalid = _useState14[0],
    setIsEmailInvalid = _useState14[1];
  var _useState15 = (0,react.useState)(storeEmailAddressFromOnboardingProfile || currentUserEmail || ''),
    _useState16 = (0,slicedToArray/* default */.A)(_useState15, 2),
    storeEmailAddress = _useState16[0],
    setEmailAddress = _useState16[1];
  var _useState17 = (0,react.useState)(isOptInMarketingFromOnboardingProfile || false),
    _useState18 = (0,slicedToArray/* default */.A)(_useState17, 2),
    isOptInMarketing = _useState18[0],
    setIsOptInMarketing = _useState18[1];
  var _useState19 = (0,react.useState)(false),
    _useState20 = (0,slicedToArray/* default */.A)(_useState19, 2),
    doValidate = _useState20[0],
    setDoValidate = _useState20[1];
  (0,react.useEffect)(function () {
    if (doValidate) {
      var parseEmail = lib.z.string().email().safeParse(storeEmailAddress);
      setIsEmailInvalid(isOptInMarketing && !parseEmail.success);
      setDoValidate(false);
    }
  }, [isOptInMarketing, doValidate, storeEmailAddress]);
  return (0,react.createElement)("div", {
    className: "woocommerce-profiler-business-information",
    "data-testid": "core-profiler-business-information"
  }, (0,react.createElement)(navigation/* Navigation */.V, {
    percentage: navigationProgress
  }), (0,react.createElement)("div", {
    className: "woocommerce-profiler-page__content woocommerce-profiler-business-information__content"
  }, (0,react.createElement)(heading/* Heading */.D, {
    className: "woocommerce-profiler__stepper-heading",
    title: (0,build_module.__)('Tell us a bit about your store', 'woocommerce'),
    subTitle: (0,build_module.__)('We’ll use this information to help you set up payments, shipping, and taxes, as well as recommending the best theme for your store.', 'woocommerce')
  }), (0,react.createElement)("form", {
    className: "woocommerce-profiler-business-information-form",
    autoComplete: "off"
  }, (0,react.createElement)(text_control/* default */.A, {
    className: "woocommerce-profiler-business-info-store-name",
    onChange: function onChange(value) {
      setStoreName(value);
    },
    value: (0,html_entities_build_module/* decodeEntities */.S)(storeName),
    label: (0,react.createElement)(react.Fragment, null, (0,build_module.__)('Give your store a name', 'woocommerce')),
    placeholder: (0,build_module.__)('Ex. My awesome store', 'woocommerce')
  }), (0,react.createElement)("p", {
    className: "woocommerce-profiler-question-subtext"
  }, (0,build_module.__)('Don’t worry — you can always change it later!', 'woocommerce')), (0,react.createElement)("p", {
    className: "woocommerce-profiler-question-label"
  }, selectIndustryQuestionLabel), (0,react.createElement)(select_control/* default */.A, {
    className: "woocommerce-profiler-select-control__industry",
    instanceId: 1,
    placeholder: (0,build_module.__)('Select an industry', 'woocommerce'),
    label: (0,build_module.__)('Select an industry', 'woocommerce'),
    options: industryChoices,
    excludeSelectedOptions: false,
    help: (0,react.createElement)(icon/* default */.A, {
      icon: chevron_down/* default */.A
    }),
    onChange: function onChange(results) {
      if (results.length) {
        setIndustry(results[0]);
      }
    },
    selected: industry ? [industry] : [],
    showAllOnFocus: true,
    isSearchable: true
  }), (0,react.createElement)("p", {
    className: "woocommerce-profiler-question-label"
  }, (0,build_module.__)('Where is your store located?', 'woocommerce'), (0,react.createElement)("span", {
    className: "woocommerce-profiler-question-required"
  }, '*')), (0,react.createElement)(select_control/* default */.A, {
    className: "woocommerce-profiler-select-control__country",
    instanceId: 2,
    placeholder: selectCountryLabel,
    label: storeCountry.key === '' ? selectCountryLabel : '',
    getSearchExpression: function getSearchExpression(query) {
      return new RegExp('(^' + query + '| — (' + query + '))', 'i');
    },
    options: countries,
    excludeSelectedOptions: false,
    help: (0,react.createElement)(icon/* default */.A, {
      icon: chevron_down/* default */.A
    }),
    onChange: function onChange(results) {
      if (results.length) {
        setStoreCountry(results[0]);
      }
    },
    selected: storeCountry ? [storeCountry] : [],
    showAllOnFocus: true,
    isSearchable: true
  }), countries.length === 0 && (0,react.createElement)(notice/* default */.A, {
    className: "woocommerce-profiler-select-control__country-error",
    isDismissible: false,
    status: "error"
  }, (0,create_interpolate_element/* default */.A)((0,build_module.__)('Oops! We encountered a problem while fetching the list of countries to choose from. <retryButton/> or <skipButton/>', 'woocommerce'), {
    retryButton: (0,react.createElement)(build_module_button/* default */.A, {
      onClick: function onClick() {
        sendEvent({
          type: 'RETRY_PRE_BUSINESS_INFO'
        });
      },
      variant: "tertiary"
    }, (0,build_module.__)('Please try again', 'woocommerce')),
    skipButton: (0,react.createElement)(build_module_button/* default */.A, {
      onClick: function onClick() {
        sendEvent({
          type: 'SKIP_BUSINESS_INFO_STEP'
        });
      },
      variant: "tertiary"
    }, (0,build_module.__)('skip this step', 'woocommerce'))
  })), (0,react.createElement)("div", {
    className: "woocommerce-profiler-select-control__country-spacer"
  }), geolocationOverruled && !dismissedGeolocationNotice && (0,react.createElement)(notice/* default */.A, {
    className: "woocommerce-profiler-geolocation-notice",
    onRemove: function onRemove() {
      return setDismissedGeolocationNotice(true);
    },
    status: "warning"
  }, (0,react.createElement)("p", null, (0,create_interpolate_element/* default */.A)((0,build_module.__)(
  // translators: first tag is filled with the country name detected by geolocation, second tag is the country name selected by the user
  'It looks like you’re located in <geolocatedCountry></geolocatedCountry>. Are you sure you want to create a store in <selectedCountry></selectedCountry>?', 'woocommerce'), {
    geolocatedCountry: (0,react.createElement)(build_module_button/* default */.A, {
      className: "geolocation-notice-geolocated-country",
      variant: "link",
      onClick: function onClick() {
        return setStoreCountry(geolocationMatch);
      }
    }, geolocatedLocation === null || geolocatedLocation === void 0 ? void 0 : geolocatedLocation.country_long),
    selectedCountry: (0,react.createElement)("span", {
      className: "geolocation-notice-selected-country"
    }, storeCountry.label)
  })), (0,react.createElement)("p", null, (0,build_module.__)('Setting up your store in the wrong country may lead to the following issues: ', 'woocommerce')), (0,react.createElement)("ul", {
    className: "woocommerce-profiler-geolocation-notice__list"
  }, (0,react.createElement)("li", null, (0,build_module.__)('Tax and duty obligations', 'woocommerce')), (0,react.createElement)("li", null, (0,build_module.__)('Payment issues', 'woocommerce')), (0,react.createElement)("li", null, (0,build_module.__)('Shipping issues', 'woocommerce')))), (0,react.createElement)(react.Fragment, null, (0,react.createElement)(text_control/* default */.A, {
    className: (0,clsx/* default */.A)('woocommerce-profiler-business-info-email-adddress', {
      'is-error': isEmailInvalid
    }),
    onChange: function onChange(value) {
      if (isEmailInvalid) {
        setDoValidate(true); // trigger validation as we want to feedback to the user as soon as it becomes valid
      }
      setEmailAddress(value);
    },
    onBlur: function onBlur() {
      setDoValidate(true);
    },
    value: (0,html_entities_build_module/* decodeEntities */.S)(storeEmailAddress),
    label: (0,react.createElement)(react.Fragment, null, (0,build_module.__)('Your email address', 'woocommerce'), isOptInMarketing && (0,react.createElement)("span", {
      className: "woocommerce-profiler-question-required"
    }, '*')),
    placeholder: (0,build_module.__)('wordpress@example.com', 'woocommerce')
  }), isEmailInvalid && (0,react.createElement)(form_input_validation/* default */.A, {
    isError: true,
    text: (0,build_module.__)('This email is not valid.', 'woocommerce')
  }), (0,react.createElement)(checkbox_control/* default */.A, {
    className: "core-profiler__checkbox",
    label: (0,build_module.__)('Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox.', 'woocommerce'),
    checked: isOptInMarketing,
    onChange: function onChange(isChecked) {
      setIsOptInMarketing(isChecked);
      setDoValidate(true);
    }
  }))), (0,react.createElement)("div", {
    className: "woocommerce-profiler-button-container"
  }, (0,react.createElement)(build_module_button/* default */.A, {
    className: "woocommerce-profiler-button",
    variant: "primary",
    disabled: !storeCountry.key || isEmailInvalid,
    onClick: function onClick() {
      sendEvent({
        type: 'BUSINESS_INFO_COMPLETED',
        payload: {
          storeName: storeName,
          industry: industry === null || industry === void 0 ? void 0 : industry.key,
          storeLocation: storeCountry.key,
          geolocationOverruled: geolocationOverruled || false,
          isOptInMarketing: isOptInMarketing,
          storeEmailAddress: storeEmailAddress
        }
      });
      setHasSubmitted(true);
    }
  }, hasSubmitted ? (0,react.createElement)(spinner/* default */.A, null) : (0,build_module.__)('Continue', 'woocommerce')))));
};
try {
    // @ts-ignore
    POSSIBLY_DEFAULT_STORE_NAMES.displayName = "POSSIBLY_DEFAULT_STORE_NAMES";
    // @ts-ignore
    POSSIBLY_DEFAULT_STORE_NAMES.__docgenInfo = { "description": "These are some store names that are known to be set by default and not likely to be used as actual names", "displayName": "POSSIBLY_DEFAULT_STORE_NAMES", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce-admin/client/core-profiler/pages/BusinessInfo.tsx#POSSIBLY_DEFAULT_STORE_NAMES"] = { docgenInfo: POSSIBLY_DEFAULT_STORE_NAMES.__docgenInfo, name: "POSSIBLY_DEFAULT_STORE_NAMES", path: "../../plugins/woocommerce-admin/client/core-profiler/pages/BusinessInfo.tsx#POSSIBLY_DEFAULT_STORE_NAMES" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    BusinessInfo.displayName = "BusinessInfo";
    // @ts-ignore
    BusinessInfo.__docgenInfo = { "description": "", "displayName": "BusinessInfo", "props": { "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "BusinessInfoContextProps" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } }, "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(event: BusinessInfoEvent) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce-admin/client/core-profiler/pages/BusinessInfo.tsx#BusinessInfo"] = { docgenInfo: BusinessInfo.__docgenInfo, name: "BusinessInfo", path: "../../plugins/woocommerce-admin/client/core-profiler/pages/BusinessInfo.tsx#BusinessInfo" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../plugins/woocommerce-admin/client/core-profiler/stories/WithSetupWizardLayout.tsx
var WithSetupWizardLayout = __webpack_require__("../../plugins/woocommerce-admin/client/core-profiler/stories/WithSetupWizardLayout.tsx");
;// CONCATENATED MODULE: ../../plugins/woocommerce-admin/client/core-profiler/stories/BusinessInfo.story.tsx

/**
 * Internal dependencies
 */



var Basic = function Basic() {
  return (0,react.createElement)(BusinessInfo, {
    sendEvent: function sendEvent() {},
    navigationProgress: 60,
    context: {
      geolocatedLocation: {
        latitude: '-37.83961',
        longitude: '144.94228',
        country_short: 'AU',
        country_long: 'Australia',
        region: 'Victoria',
        city: 'Port Melbourne'
      },
      userProfile: {},
      businessInfo: {},
      countries: [{
        key: 'US',
        label: 'United States'
      }],
      onboardingProfile: {
        is_store_country_set: false,
        industry: ['clothing_and_accessories'],
        business_choice: 'im_just_starting_my_business'
      }
    }
  });
};
/* harmony default export */ const BusinessInfo_story = ({
  title: 'WooCommerce Admin/Application/Core Profiler/Business Info',
  component: BusinessInfo,
  decorators: [WithSetupWizardLayout/* WithSetupWizardLayout */.b]
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <BusinessInfo sendEvent={() => {}} navigationProgress={60} context={{\n  geolocatedLocation: {\n    latitude: '-37.83961',\n    longitude: '144.94228',\n    country_short: 'AU',\n    country_long: 'Australia',\n    region: 'Victoria',\n    city: 'Port Melbourne'\n  },\n  userProfile: {},\n  businessInfo: {},\n  countries: [{\n    key: 'US',\n    label: 'United States'\n  }],\n  onboardingProfile: {\n    is_store_country_set: false,\n    industry: ['clothing_and_accessories'],\n    business_choice: 'im_just_starting_my_business'\n  }\n}} />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ })

}]);