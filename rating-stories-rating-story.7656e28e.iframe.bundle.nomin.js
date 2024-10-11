(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1346],{

/***/ "../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js":
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;
	var nativeCodeString = '[native code]';

	function classNames() {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg)) {
				if (arg.length) {
					var inner = classNames.apply(null, arg);
					if (inner) {
						classes.push(inner);
					}
				}
			} else if (argType === 'object') {
				if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
					classes.push(arg.toString());
					continue;
				}

				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/star.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-star",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 2l2.582 6.953L22 9.257l-5.822 4.602L18.18 21 12 16.891 5.82 21l2.002-7.141L2 9.257l7.418-.304z"})))}


/***/ }),

/***/ "../../packages/js/components/src/rating/stories/rating.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Default: () => (/* binding */ Default),
  "default": () => (/* binding */ rating_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/star.js
var star = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/star.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/rating/index.tsx

/**
 * External dependencies
 */



/**
 * Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a
 * rating in a scale between 0 and the prop `totalStars` (default 5).
 */
var Rating = function Rating(_ref) {
  var _ref$rating = _ref.rating,
    rating = _ref$rating === void 0 ? 0 : _ref$rating,
    _ref$totalStars = _ref.totalStars,
    totalStars = _ref$totalStars === void 0 ? 5 : _ref$totalStars,
    _ref$size = _ref.size,
    size = _ref$size === void 0 ? 18 : _ref$size,
    className = _ref.className,
    icon = _ref.icon,
    outlineIcon = _ref.outlineIcon;
  var stars = function stars(_icon) {
    var starStyles = {
      width: size + 'px',
      height: size + 'px'
    };
    var _stars = [];
    for (var i = 0; i < totalStars; i++) {
      var Icon = _icon || star/* default */.A;
      _stars.push((0,react.createElement)(Icon, {
        key: 'star-' + i,
        style: starStyles
      }));
    }
    return _stars;
  };
  var classes = classnames_default()('woocommerce-rating', className);
  var perStar = 100 / totalStars;
  var outlineStyles = {
    width: Math.round(perStar * rating) + '%'
  };
  var label = (0,build_module/* sprintf */.nv)( /* translators: %1$s: rating, %2$s: total number of stars */
  (0,build_module.__)('%1$s out of %2$s stars.', 'woocommerce'), rating, totalStars);
  return (0,react.createElement)("div", {
    className: classes,
    "aria-label": label
  }, stars(icon), (0,react.createElement)("div", {
    className: "woocommerce-rating__star-outline",
    style: outlineStyles
  }, stars(outlineIcon || icon)));
};
/* harmony default export */ const src_rating = (Rating);
try {
    // @ts-ignore
    rating.displayName = "rating";
    // @ts-ignore
    rating.__docgenInfo = { "description": "Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a\nrating in a scale between 0 and the prop `totalStars` (default 5).", "displayName": "rating", "props": { "rating": { "defaultValue": { value: "0" }, "description": "", "name": "rating", "required": false, "type": { "name": "number" } }, "totalStars": { "defaultValue": { value: "5" }, "description": "", "name": "totalStars", "required": false, "type": { "name": "number" } }, "size": { "defaultValue": { value: "18" }, "description": "", "name": "size", "required": false, "type": { "name": "number" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "icon": { "defaultValue": null, "description": "", "name": "icon", "required": false, "type": { "name": "ReactNode" } }, "outlineIcon": { "defaultValue": null, "description": "", "name": "outlineIcon", "required": false, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rating/index.tsx#rating"] = { docgenInfo: rating.__docgenInfo, name: "rating", path: "../../packages/js/components/src/rating/index.tsx#rating" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/rating/stories/rating.story.tsx

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/* harmony default export */ const rating_story = ({
  title: 'WooCommerce Admin/components/Rating',
  component: src_rating,
  args: {
    rating: 4.5,
    totalStars: 5,
    size: 18
  }
});
var Default = function Default(args) {
  return (0,react.createElement)(src_rating, args);
};
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "args => <Rating {...args} />",
      ...Default.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Default.displayName = "Default";
    // @ts-ignore
    Default.__docgenInfo = { "description": "", "displayName": "Default", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rating/stories/rating.story.tsx#Default"] = { docgenInfo: Default.__docgenInfo, name: "Default", path: "../../packages/js/components/src/rating/stories/rating.story.tsx#Default" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);