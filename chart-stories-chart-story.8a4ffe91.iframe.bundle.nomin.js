(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5750],{

/***/ "../../packages/js/components/src/section/context.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $: () => (/* binding */ Level)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */


/**
 * Context container for heading level. We start at 2 because the `h1` is defined in <Header />
 *
 * See https://medium.com/@Heydon/managing-heading-levels-in-design-systems-18be9a746fa3
 */
var Level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(2);


/***/ }),

/***/ "../../packages/js/components/src/section/header.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H: () => (/* binding */ H)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
 * (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
 * heading level.
 *
 * @type {HTMLElement}
 */
function H(props) {
  var level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$);
  var Heading = 'h' + Math.min(level, 6);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Heading, props);
}
try {
    // @ts-ignore
    H.displayName = "H";
    // @ts-ignore
    H.__docgenInfo = { "description": "These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels\n(`h2`, `h3`, \u2026) you can use `<H />` to create \"section headings\", which look to the parent `<Section />`s for the appropriate\nheading level.", "displayName": "H", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/header.tsx#H"] = { docgenInfo: H.__docgenInfo, name: "H", path: "../../packages/js/components/src/section/header.tsx#H" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/section.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   w: () => (/* binding */ Section)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");

var _excluded = ["component", "children"];

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 */
var Section = function Section(_ref) {
  var component = _ref.component,
    children = _ref.children,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(_ref, _excluded);
  var Component = component || 'div';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$.Consumer, null, function (level) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$.Provider, {
      value: level + 1
    }, component === false ? children : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(Component, props, children));
  });
};
try {
    // @ts-ignore
    Section.displayName = "Section";
    // @ts-ignore
    Section.__docgenInfo = { "description": "The section wrapper, used to indicate a sub-section (and change the header level context).", "displayName": "Section", "props": { "component": { "defaultValue": null, "description": "The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component.", "name": "component", "required": false, "type": { "name": "string | false | ComponentType<{}>" } }, "className": { "defaultValue": null, "description": "Optional classname", "name": "className", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "The children inside this section, rendered in the `component`. This increases the context level for the next heading used.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/section.tsx#Section"] = { docgenInfo: Section.__docgenInfo, name: "Section", path: "../../packages/js/components/src/section/section.tsx#Section" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale sync recursive ^\\.\\/.*$":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./af": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/af.js",
	"./af.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/af.js",
	"./ar": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar.js",
	"./ar-dz": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-ma.js",
	"./ar-sa": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar-tn.js",
	"./ar.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ar.js",
	"./az": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/az.js",
	"./az.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/az.js",
	"./be": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/be.js",
	"./be.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/be.js",
	"./bg": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bg.js",
	"./bg.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bg.js",
	"./bm": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bm.js",
	"./bm.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bm.js",
	"./bn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn.js",
	"./bn-bd": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn-bd.js",
	"./bn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bn.js",
	"./bo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bo.js",
	"./bo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bo.js",
	"./br": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/br.js",
	"./br.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/br.js",
	"./bs": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bs.js",
	"./bs.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/bs.js",
	"./ca": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ca.js",
	"./ca.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ca.js",
	"./cs": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cs.js",
	"./cs.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cs.js",
	"./cv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cv.js",
	"./cv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cv.js",
	"./cy": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cy.js",
	"./cy.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/cy.js",
	"./da": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/da.js",
	"./da.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/da.js",
	"./de": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de.js",
	"./de-at": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-at.js",
	"./de-at.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-at.js",
	"./de-ch": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de-ch.js",
	"./de.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/de.js",
	"./dv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/dv.js",
	"./dv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/dv.js",
	"./el": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/el.js",
	"./el.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/el.js",
	"./en-au": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-au.js",
	"./en-au.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-au.js",
	"./en-ca": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ca.js",
	"./en-gb": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-gb.js",
	"./en-ie": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-ie.js",
	"./en-il": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-il.js",
	"./en-il.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-il.js",
	"./en-in": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-in.js",
	"./en-in.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-in.js",
	"./en-nz": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-nz.js",
	"./en-sg": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/en-sg.js",
	"./eo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eo.js",
	"./eo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eo.js",
	"./es": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es.js",
	"./es-do": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-do.js",
	"./es-do.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-do.js",
	"./es-mx": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-mx.js",
	"./es-us": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-us.js",
	"./es-us.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es-us.js",
	"./es.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/es.js",
	"./et": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/et.js",
	"./et.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/et.js",
	"./eu": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eu.js",
	"./eu.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/eu.js",
	"./fa": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fa.js",
	"./fa.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fa.js",
	"./fi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fi.js",
	"./fi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fi.js",
	"./fil": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fil.js",
	"./fil.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fil.js",
	"./fo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fo.js",
	"./fo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fo.js",
	"./fr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr.js",
	"./fr-ca": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr-ch.js",
	"./fr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fr.js",
	"./fy": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fy.js",
	"./fy.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/fy.js",
	"./ga": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ga.js",
	"./ga.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ga.js",
	"./gd": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gd.js",
	"./gd.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gd.js",
	"./gl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gl.js",
	"./gl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gl.js",
	"./gom-deva": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gom-latn.js",
	"./gu": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gu.js",
	"./gu.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/gu.js",
	"./he": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/he.js",
	"./he.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/he.js",
	"./hi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hi.js",
	"./hi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hi.js",
	"./hr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hr.js",
	"./hr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hr.js",
	"./hu": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hu.js",
	"./hu.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hu.js",
	"./hy-am": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/hy-am.js",
	"./id": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/id.js",
	"./id.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/id.js",
	"./is": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/is.js",
	"./is.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/is.js",
	"./it": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it.js",
	"./it-ch": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it-ch.js",
	"./it.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/it.js",
	"./ja": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ja.js",
	"./ja.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ja.js",
	"./jv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/jv.js",
	"./jv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/jv.js",
	"./ka": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ka.js",
	"./ka.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ka.js",
	"./kk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kk.js",
	"./kk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kk.js",
	"./km": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/km.js",
	"./km.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/km.js",
	"./kn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kn.js",
	"./kn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/kn.js",
	"./ko": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ko.js",
	"./ko.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ko.js",
	"./ku": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ku.js",
	"./ku.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ku.js",
	"./ky": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ky.js",
	"./ky.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ky.js",
	"./lb": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lb.js",
	"./lb.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lb.js",
	"./lo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lo.js",
	"./lo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lo.js",
	"./lt": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lt.js",
	"./lt.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lt.js",
	"./lv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lv.js",
	"./lv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/lv.js",
	"./me": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/me.js",
	"./me.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/me.js",
	"./mi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mi.js",
	"./mi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mi.js",
	"./mk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mk.js",
	"./mk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mk.js",
	"./ml": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ml.js",
	"./ml.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ml.js",
	"./mn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mn.js",
	"./mn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mn.js",
	"./mr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mr.js",
	"./mr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mr.js",
	"./ms": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms.js",
	"./ms-my": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms-my.js",
	"./ms.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ms.js",
	"./mt": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mt.js",
	"./mt.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/mt.js",
	"./my": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/my.js",
	"./my.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/my.js",
	"./nb": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nb.js",
	"./nb.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nb.js",
	"./ne": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ne.js",
	"./ne.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ne.js",
	"./nl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl.js",
	"./nl-be": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl-be.js",
	"./nl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nl.js",
	"./nn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nn.js",
	"./nn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/nn.js",
	"./oc-lnc": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pa-in.js",
	"./pl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pl.js",
	"./pl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pl.js",
	"./pt": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt.js",
	"./pt-br": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt-br.js",
	"./pt.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/pt.js",
	"./ro": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ro.js",
	"./ro.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ro.js",
	"./ru": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ru.js",
	"./ru.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ru.js",
	"./sd": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sd.js",
	"./sd.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sd.js",
	"./se": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/se.js",
	"./se.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/se.js",
	"./si": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/si.js",
	"./si.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/si.js",
	"./sk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sk.js",
	"./sk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sk.js",
	"./sl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sl.js",
	"./sl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sl.js",
	"./sq": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sq.js",
	"./sq.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sq.js",
	"./sr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr.js",
	"./sr-cyrl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sr.js",
	"./ss": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ss.js",
	"./ss.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ss.js",
	"./sv": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sv.js",
	"./sv.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sv.js",
	"./sw": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sw.js",
	"./sw.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/sw.js",
	"./ta": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ta.js",
	"./ta.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ta.js",
	"./te": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/te.js",
	"./te.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/te.js",
	"./tet": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tet.js",
	"./tet.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tet.js",
	"./tg": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tg.js",
	"./tg.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tg.js",
	"./th": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/th.js",
	"./th.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/th.js",
	"./tk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tk.js",
	"./tk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tk.js",
	"./tl-ph": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tl-ph.js",
	"./tlh": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tlh.js",
	"./tlh.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tlh.js",
	"./tr": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tr.js",
	"./tr.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tr.js",
	"./tzl": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzl.js",
	"./tzl.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzl.js",
	"./tzm": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm.js",
	"./tzm-latn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/tzm.js",
	"./ug-cn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ug-cn.js",
	"./uk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uk.js",
	"./uk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uk.js",
	"./ur": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ur.js",
	"./ur.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/ur.js",
	"./uz": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz.js",
	"./uz-latn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz-latn.js",
	"./uz.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/uz.js",
	"./vi": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/vi.js",
	"./vi.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/vi.js",
	"./x-pseudo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/x-pseudo.js",
	"./yo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/yo.js",
	"./yo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/yo.js",
	"./zh-cn": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale/zh-tw.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ }),

/***/ "../../packages/js/components/src/chart/stories/chart.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Default: () => (/* binding */ Default),
  "default": () => (/* binding */ chart_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js
var es_object_entries = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js
var es_parse_int = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-format@1.4.5/node_modules/d3-format/src/defaultLocale.js + 8 modules
var defaultLocale = __webpack_require__("../../node_modules/.pnpm/d3-format@1.4.5/node_modules/d3-format/src/defaultLocale.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/line-graph.js
var line_graph = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/line-graph.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/stats-alt.js
var stats_alt = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/stats-alt.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js + 1 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/menu.js + 1 modules
var menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-scale-chromatic@1.5.0/node_modules/d3-scale-chromatic/src/sequential-multi/viridis.js + 1 modules
var viridis = __webpack_require__("../../node_modules/.pnpm/d3-scale-chromatic@1.5.0/node_modules/d3-scale-chromatic/src/sequential-multi/viridis.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js
var memoize_one_esm = __webpack_require__("../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js + 6 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/dompurify@2.4.7/node_modules/dompurify/dist/purify.js
var purify = __webpack_require__("../../node_modules/.pnpm/dompurify@2.4.7/node_modules/dompurify/dist/purify.js");
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 3 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/placeholder.js







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
 * `ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.
 */
var ChartPlaceholder = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(ChartPlaceholder, _Component);
  var _super = _createSuper(ChartPlaceholder);
  function ChartPlaceholder() {
    (0,classCallCheck/* default */.A)(this, ChartPlaceholder);
    return _super.apply(this, arguments);
  }
  (0,createClass/* default */.A)(ChartPlaceholder, [{
    key: "render",
    value: function render() {
      var height = this.props.height;
      return (0,react.createElement)("div", {
        "aria-hidden": "true",
        className: "woocommerce-chart-placeholder",
        style: {
          height: height
        }
      }, (0,react.createElement)(spinner/* default */.A, null));
    }
  }]);
  return ChartPlaceholder;
}(react.Component);
ChartPlaceholder.propTypes = {
  height: (prop_types_default()).number
};
ChartPlaceholder.defaultProps = {
  height: 0
};
/* harmony default export */ const placeholder = (ChartPlaceholder);
// EXTERNAL MODULE: ../../packages/js/components/src/section/header.tsx
var header = __webpack_require__("../../packages/js/components/src/section/header.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/section/section.tsx
var section = __webpack_require__("../../packages/js/components/src/section/section.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.set.js
var es_set = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.set.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-time-format@2.3.0/node_modules/d3-time-format/src/defaultLocale.js + 4 modules
var src_defaultLocale = __webpack_require__("../../node_modules/.pnpm/d3-time-format@2.3.0/node_modules/d3-time-format/src/defaultLocale.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/index.js














/**
 * External dependencies
 */




/**
 * Allows an overriding formatter or defaults to d3Format or d3TimeFormat
 *
 * @param {string|Function} format    - either a format string for the D3 formatters or an overriding formatting method
 * @param {Function}        formatter - default d3Format or another formatting method, which accepts the string `format`
 * @return {Function} to be used to format an input given the format and formatter
 */
var getFormatter = function getFormatter(format) {
  var formatter = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : defaultLocale/* format */.GP;
  return typeof format === 'function' ? format : formatter(format);
};

/**
 * Returns an array of unique keys contained in the data.
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Array} Array of unique keys.
 */
var getUniqueKeys = function getUniqueKeys(data) {
  var keys = new Set(data.reduce(function (acc, curr) {
    return acc.concat(Object.keys(curr));
  }, []));
  return (0,toConsumableArray/* default */.A)(keys).filter(function (key) {
    return key !== 'date';
  });
};

/**
 * Describes `getOrderedKeys`
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Array} Array of unique category keys ordered by cumulative total value
 */
var getOrderedKeys = function getOrderedKeys(data) {
  var keys = getUniqueKeys(data);
  return keys.map(function (key) {
    return {
      key: key,
      focus: true,
      total: data.reduce(function (a, c) {
        return a + c[key].value;
      }, 0),
      visible: true
    };
  }).sort(function (a, b) {
    return b.total - a.total;
  });
};

/**
 * Describes `getUniqueDates`
 *
 * @param {Array}  data       - the chart component's `data` prop.
 * @param {string} dateParser - D3 time format
 * @return {Array} an array of unique date values sorted from earliest to latest
 */
var getUniqueDates = function getUniqueDates(data, dateParser) {
  var parseDate = (0,src_defaultLocale/* utcParse */.GY)(dateParser);
  var dates = new Set(data.map(function (d) {
    return d.date;
  }));
  return (0,toConsumableArray/* default */.A)(dates).sort(function (a, b) {
    return parseDate(a) - parseDate(b);
  });
};

/**
 * Check whether data is empty.
 *
 * @param {Array}  data      - the chart component's `data` prop.
 * @param {number} baseValue - base value to test data values against.
 * @return {boolean} `false` if there was at least one data value different than
 * the baseValue.
 */
var isDataEmpty = function isDataEmpty(data) {
  var baseValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  for (var i = 0; i < data.length; i++) {
    for (var _i = 0, _Object$entries = Object.entries(data[i]); _i < _Object$entries.length; _i++) {
      var _Object$entries$_i = (0,slicedToArray/* default */.A)(_Object$entries[_i], 2),
        key = _Object$entries$_i[0],
        item = _Object$entries$_i[1];
      if (key !== 'date' && !(0,lodash.isNil)(item.value) && item.value !== baseValue) {
        return false;
      }
    }
  }
  return true;
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js
var es_string_starts_with = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js + 10 modules
var hooks_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/constants.js
// This is the max number of items that can be selected/shown on a chart at one time.
// If this number changes, the color scale also needs to be adjusted.
var selectionLimit = 10;
var colorScales = [[], [0.5], [0.333, 0.667], [0.2, 0.5, 0.8], [0.12, 0.375, 0.625, 0.88], [0, 0.25, 0.5, 0.75, 1], [0, 0.2, 0.4, 0.6, 0.8, 1], [0, 0.16, 0.32, 0.48, 0.64, 0.8, 1], [0, 0.14, 0.28, 0.42, 0.56, 0.7, 0.84, 1], [0, 0.12, 0.24, 0.36, 0.48, 0.6, 0.72, 0.84, 1], [0, 0.11, 0.22, 0.33, 0.44, 0.55, 0.66, 0.77, 0.88, 1]];
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/color.js




/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var getColor = function getColor(orderedKeys, colorScheme) {
  return function (key) {
    var len = orderedKeys.length > selectionLimit ? selectionLimit : orderedKeys.length;
    var idx = (0,lodash.findIndex)(orderedKeys, function (d) {
      return d.key === key;
    });

    /**
     * Color to be used for a chart item.
     *
     * @filter woocommerce_admin_chart_item_color
     * @example
     * addFilter(
     * 	'woocommerce_admin_chart_item_color',
     * 	'example',
     * ( idx ) => {
     * 	const colorScales = [
     *	  "#0A2F51",
     *	  "#0E4D64",
     *	  "#137177",
     *	  "#188977",
     *	];
     * 	return colorScales[ idx ] || false;
     * });
     *
     */
    var color = (0,hooks_build_module/* applyFilters */.W5)('woocommerce_admin_chart_item_color', idx, key, orderedKeys);
    if (color && color.toString().startsWith('#')) {
      return color;
    }
    var keyValue = idx <= selectionLimit - 1 ? colorScales[len][idx] : 0;
    return colorScheme(keyValue);
  };
};
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/legend.js










function legend_createSuper(Derived) {
  var hasNativeReflectConstruct = legend_isNativeReflectConstruct();
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
function legend_isNativeReflectConstruct() {
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
 * A legend specifically designed for the WooCommerce admin charts.
 */
var D3Legend = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(D3Legend, _Component);
  var _super = legend_createSuper(D3Legend);
  function D3Legend() {
    var _this;
    (0,classCallCheck/* default */.A)(this, D3Legend);
    _this = _super.call(this);
    _this.listRef = (0,react.createRef)();
    _this.state = {
      isScrollable: false
    };
    return _this;
  }
  (0,createClass/* default */.A)(D3Legend, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.updateListScroll();
      window.addEventListener('resize', this.updateListScroll);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      window.removeEventListener('resize', this.updateListScroll);
    }
  }, {
    key: "updateListScroll",
    value: function updateListScroll() {
      if (!this || !this.listRef) {
        return;
      }
      var list = this.listRef.current;
      var scrolledToEnd = list.scrollHeight - list.scrollTop <= list.offsetHeight;
      this.setState({
        isScrollable: !scrolledToEnd
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
        colorScheme = _this$props.colorScheme,
        data = _this$props.data,
        handleLegendHover = _this$props.handleLegendHover,
        handleLegendToggle = _this$props.handleLegendToggle,
        interactive = _this$props.interactive,
        legendDirection = _this$props.legendDirection,
        legendValueFormat = _this$props.legendValueFormat,
        instanceId = _this$props.instanceId,
        totalLabel = _this$props.totalLabel;
      var isScrollable = this.state.isScrollable;
      var visibleData = data.filter(function (key) {
        return key.visible;
      });
      var numberOfRowsVisible = visibleData.length;
      var showTotalLabel = legendDirection === 'column' && data.length > selectionLimit && totalLabel;
      var keys = data.length > selectionLimit ? visibleData : data;
      return (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-legend', "woocommerce-legend__direction-".concat(legendDirection), {
          'has-total': showTotalLabel,
          'is-scrollable': isScrollable
        }, this.props.className)
      }, (0,react.createElement)("ul", {
        className: "woocommerce-legend__list",
        ref: this.listRef,
        onScroll: showTotalLabel ? this.updateListScroll : null
      }, data.map(function (row) {
        return (0,react.createElement)("li", {
          className: classnames_default()('woocommerce-legend__item', {
            'woocommerce-legend__item-checked': row.visible
          }),
          key: row.key,
          id: "woocommerce-legend-".concat(instanceId, "__item__").concat(row.key),
          onMouseEnter: handleLegendHover,
          onMouseLeave: handleLegendHover,
          onBlur: handleLegendHover,
          onFocus: handleLegendHover
        }, (0,react.createElement)("button", {
          role: "checkbox",
          "aria-checked": row.visible ? 'true' : 'false',
          onClick: handleLegendToggle,
          id: "woocommerce-legend-".concat(instanceId, "__item-button__").concat(row.key),
          disabled: row.visible && numberOfRowsVisible <= 1 || !row.visible && numberOfRowsVisible >= selectionLimit || !interactive,
          title: numberOfRowsVisible >= selectionLimit ? (0,build_module/* sprintf */.nv)( /* translators: %d: number of items selected */
          (0,build_module.__)('You may select up to %d items.', 'woocommerce'), selectionLimit) : ''
        }, (0,react.createElement)("div", {
          className: "woocommerce-legend__item-container"
        }, (0,react.createElement)("span", {
          className: classnames_default()('woocommerce-legend__item-checkmark', {
            'woocommerce-legend__item-checkmark-checked': row.visible
          }),
          style: row.visible ? {
            color: getColor(keys, colorScheme)(row.key)
          } : null
        }), (0,react.createElement)("span", {
          className: "woocommerce-legend__item-title"
        }, row.label), (0,react.createElement)("span", {
          className: "woocommerce-legend__item-total"
        }, getFormatter(legendValueFormat)(row.total)))));
      })), showTotalLabel && (0,react.createElement)("div", {
        className: "woocommerce-legend__total"
      }, totalLabel));
    }
  }]);
  return D3Legend;
}(react.Component);
D3Legend.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * A chromatic color function to be passed down to d3.
   */
  colorScheme: (prop_types_default()).func,
  /**
   * An array of `orderedKeys`.
   */
  data: (prop_types_default()).array.isRequired,
  /**
   * Handles `onClick` event.
   */
  handleLegendToggle: (prop_types_default()).func,
  /**
   * Handles `onMouseEnter`/`onMouseLeave` events.
   */
  handleLegendHover: (prop_types_default()).func,
  /**
   * Determines whether or not you can click on the legend
   */
  interactive: (prop_types_default()).bool,
  /**
   * Display legend items as a `row` or `column` inside a flex-box.
   */
  legendDirection: prop_types_default().oneOf(['row', 'column']),
  /**
   * A number formatting string or function to format the value displayed in the legend.
   */
  legendValueFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * Label to describe the legend items. It will be displayed in the legend of
   * comparison charts when there are many.
   */
  totalLabel: (prop_types_default()).string,
  // from withInstanceId
  instanceId: (prop_types_default()).number
};
D3Legend.defaultProps = {
  interactive: true,
  legendDirection: 'row',
  legendValueFormat: ','
};
/* harmony default export */ const d3chart_legend = ((0,with_instance_id/* default */.A)(D3Legend));
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/select.js + 40 modules
var src_select = __webpack_require__("../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/select.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/d3base/index.js








function d3base_createSuper(Derived) {
  var hasNativeReflectConstruct = d3base_isNativeReflectConstruct();
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
function d3base_isNativeReflectConstruct() {
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
 * Provides foundation to use D3 within React.
 *
 * React is responsible for determining when a chart should be updated (e.g. whenever data changes or the browser is
 * resized), while D3 is responsible for the actual rendering of the chart (which is performed via DOM operations that
 * happen outside of React's control).
 *
 * This component makes use of new lifecycle methods that come with React 16.3. Thus, while this component (i.e. the
 * container of the chart) is rendered during the 'render phase' the chart itself is only rendered during the 'commit
 * phase' (i.e. in 'componentDidMount' and 'componentDidUpdate' methods).
 */
var D3Base = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(D3Base, _Component);
  var _super = d3base_createSuper(D3Base);
  function D3Base(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, D3Base);
    _this = _super.call(this, props);
    _this.chartRef = (0,react.createRef)();
    return _this;
  }
  (0,createClass/* default */.A)(D3Base, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.drawUpdatedChart();
    }
  }, {
    key: "shouldComponentUpdate",
    value: function shouldComponentUpdate(nextProps) {
      return this.props.className !== nextProps.className || !(0,lodash.isEqual)(this.props.data, nextProps.data) || !(0,lodash.isEqual)(this.props.orderedKeys, nextProps.orderedKeys) || this.props.drawChart !== nextProps.drawChart || this.props.height !== nextProps.height || this.props.chartType !== nextProps.chartType || this.props.width !== nextProps.width;
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate() {
      this.drawUpdatedChart();
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.deleteChart();
    }
  }, {
    key: "delayedScroll",
    value: function delayedScroll() {
      var tooltip = this.props.tooltip;
      return (0,lodash.throttle)(function () {
        // eslint-disable-next-line no-unused-expressions
        tooltip && tooltip.hide();
      }, 300);
    }
  }, {
    key: "deleteChart",
    value: function deleteChart() {
      (0,src_select/* default */.A)(this.chartRef.current).selectAll('svg').remove();
    }

    /**
     * Renders the chart, or triggers a rendering by updating the list of params.
     */
  }, {
    key: "drawUpdatedChart",
    value: function drawUpdatedChart() {
      var drawChart = this.props.drawChart;
      var svg = this.getContainer();
      drawChart(svg);
    }
  }, {
    key: "getContainer",
    value: function getContainer() {
      var _this$props = this.props,
        className = _this$props.className,
        height = _this$props.height,
        width = _this$props.width;
      this.deleteChart();
      var svg = (0,src_select/* default */.A)(this.chartRef.current).append('svg').attr('viewBox', "0 0 ".concat(width, " ").concat(height)).attr('height', height).attr('width', width).attr('preserveAspectRatio', 'xMidYMid meet');
      if (className) {
        svg.attr('class', "".concat(className, "__viewbox"));
      }
      return svg.append('g');
    }
  }, {
    key: "render",
    value: function render() {
      var className = this.props.className;
      return (0,react.createElement)("div", {
        className: classnames_default()('d3-base', className),
        ref: this.chartRef,
        onScroll: this.delayedScroll()
      });
    }
  }]);
  return D3Base;
}(react.Component);

D3Base.propTypes = {
  className: (prop_types_default()).string,
  data: (prop_types_default()).array,
  orderedKeys: (prop_types_default()).array,
  // required to detect changes in data
  tooltip: (prop_types_default()).object,
  chartType: (prop_types_default()).string
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js
var es_number_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-finite.js
var es_number_is_finite = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-finite.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-scale@2.2.2/node_modules/d3-scale/src/index.js + 58 modules
var d3_scale_src = __webpack_require__("../../node_modules/.pnpm/d3-scale@2.2.2/node_modules/d3-scale/src/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/scales.js









/**
 * External dependencies
 */



/**
 * Describes getXScale
 *
 * @param {Array}   uniqueDates - from `getUniqueDates`
 * @param {number}  width       - calculated width of the charting space
 * @param {boolean} compact     - whether the chart must be compact (without padding
                                between days)
 * @return {Function} a D3 scale of the dates
 */
var getXScale = function getXScale(uniqueDates, width) {
  var compact = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  return (0,d3_scale_src/* scaleBand */.WH)().domain(uniqueDates).range([0, width]).paddingInner(compact ? 0 : 0.1);
};

/**
 * Describes getXGroupScale
 *
 * @param {Array}    orderedKeys - from `getOrderedKeys`
 * @param {Function} xScale      - from `getXScale`
 * @param {boolean}  compact     - whether the chart must be compact (without padding
                                 between days)
 * @return {Function} a D3 scale for each category within the xScale range
 */
var getXGroupScale = function getXGroupScale(orderedKeys, xScale) {
  var compact = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  return (0,d3_scale_src/* scaleBand */.WH)().domain(orderedKeys.filter(function (d) {
    return d.visible;
  }).map(function (d) {
    return d.key;
  })).rangeRound([0, xScale.bandwidth()]).padding(compact ? 0 : 0.07);
};

/**
 * Describes getXLineScale
 *
 * @param {Array}  uniqueDates - from `getUniqueDates`
 * @param {number} width       - calculated width of the charting space
 * @return {Function} a D3 scaletime for each date
 */
var getXLineScale = function getXLineScale(uniqueDates, width) {
  return (0,d3_scale_src/* scaleTime */.w7)().domain([moment_default()(uniqueDates[0], 'YYYY-MM-DD HH:mm').toDate(), moment_default()(uniqueDates[uniqueDates.length - 1], 'YYYY-MM-DD HH:mm').toDate()]).rangeRound([0, width]);
};
var getYValueLimits = function getYValueLimits(data) {
  var maxYValue = Number.NEGATIVE_INFINITY;
  var minYValue = Number.POSITIVE_INFINITY;
  data.forEach(function (d) {
    for (var _i = 0, _Object$entries = Object.entries(d); _i < _Object$entries.length; _i++) {
      var _Object$entries$_i = (0,slicedToArray/* default */.A)(_Object$entries[_i], 2),
        key = _Object$entries$_i[0],
        item = _Object$entries$_i[1];
      if (key !== 'date' && Number.isFinite(item.value) && item.value > maxYValue) {
        maxYValue = item.value;
      }
      if (key !== 'date' && Number.isFinite(item.value) && item.value < minYValue) {
        minYValue = item.value;
      }
    }
  });
  return {
    upper: maxYValue,
    lower: minYValue
  };
};
var calculateStep = function calculateStep(minValue, maxValue) {
  if (!Number.isFinite(minValue) || !Number.isFinite(maxValue)) {
    return 1;
  }
  if (maxValue === 0 && minValue === 0) {
    return 1 / 3;
  }
  var maxAbsValue = Math.max(-minValue, maxValue);
  var maxLimit = 4 / 3 * maxAbsValue;
  var pow3Y =
  // eslint-disable-next-line no-bitwise
  Math.pow(10, (Math.log(maxLimit) * Math.LOG10E + 1 | 0) - 2) * 3;
  var step = Math.ceil(maxLimit / pow3Y) * pow3Y / 3;
  if (maxValue < 1 && minValue > -1) {
    return Math.round(step * 4) / 4;
  }
  return Math.ceil(step);
};

/**
 * Returns the lower and upper limits of the Y scale and the calculated step to use in the axis, rounding
 * them to the nearest thousand, ten-thousand, million etc. In case it is a decimal number, ceils it.
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Object} Object containing the `lower` and `upper` limits and a `step` value.
 */
var getYScaleLimits = function getYScaleLimits(data) {
  var _getYValueLimits = getYValueLimits(data),
    minValue = _getYValueLimits.lower,
    maxValue = _getYValueLimits.upper;
  var step = calculateStep(minValue, maxValue);
  var limits = {
    lower: 0,
    upper: 0,
    step: step
  };
  if (Number.isFinite(minValue) || minValue < 0) {
    limits.lower = Math.floor(minValue / step) * step;
    if (limits.lower === minValue && minValue !== 0) {
      limits.lower -= step;
    }
  }
  if (Number.isFinite(maxValue) || maxValue > 0) {
    limits.upper = Math.ceil(maxValue / step) * step;
    if (limits.upper === maxValue && maxValue !== 0) {
      limits.upper += step;
    }
  }
  return limits;
};

/**
 * Describes getYScale
 *
 * @param {number} height - calculated height of the charting space
 * @param {number} yMin   - minimum y value
 * @param {number} yMax   - maximum y value
 * @return {Function} the D3 linear scale from 0 to the value from `getYMax`
 */
var getYScale = function getYScale(height, yMin, yMax) {
  return (0,d3_scale_src/* scaleLinear */.m4)().domain([Math.min(yMin, 0), yMax === 0 && yMin === 0 ? 1 : Math.max(yMax, 0)]).rangeRound([height, 0]);
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js
var es_string_split = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-axis@1.0.12/node_modules/d3-axis/src/index.js + 3 modules
var d3_axis_src = __webpack_require__("../../node_modules/.pnpm/d3-axis@1.0.12/node_modules/d3-axis/src/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/breakpoints.js
var smallBreak = 783;
var wideBreak = 1365;
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/axis-x.js











/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var dayTicksThreshold = 63;
var weekTicksThreshold = 9;
var mediumBreak = 1130;
var smallPoints = 7;
var mediumPoints = 12;
var largePoints = 16;
var mostPoints = 31;

/**
 * Calculate the maximum number of ticks allowed in the x-axis based on the width and mode of the chart
 *
 * @param {number} width - calculated page width
 * @param {string} mode  - item-comparison or time-comparison
 * @return {number} number of x-axis ticks based on width and chart mode
 */
var calculateMaxXTicks = function calculateMaxXTicks(width, mode) {
  if (width < smallBreak) {
    return smallPoints;
  } else if (width >= smallBreak && width <= mediumBreak) {
    return mediumPoints;
  } else if (width > mediumBreak && width <= wideBreak) {
    if (mode === 'time-comparison') {
      return largePoints;
    } else if (mode === 'item-comparison') {
      return mediumPoints;
    }
  } else if (width > wideBreak) {
    if (mode === 'time-comparison') {
      return mostPoints;
    } else if (mode === 'item-comparison') {
      return largePoints;
    }
  }
  return largePoints;
};

/**
 * Filter out irrelevant dates so only the first date of each month is kept.
 *
 * @param {Array} dates - string dates.
 * @return {Array} Filtered dates.
 */
var getFirstDatePerMonth = function getFirstDatePerMonth(dates) {
  return dates.filter(function (date, i) {
    return i === 0 || moment_default()(date).toDate().getMonth() !== moment_default()(dates[i - 1]).toDate().getMonth();
  });
};

/**
 * Given an array of dates, returns true if the first and last one belong to the same day.
 *
 * @param {Array} dates - an array of dates
 * @return {boolean} whether the first and last date are different hours from the same date.
 */
var areDatesInTheSameDay = function areDatesInTheSameDay(dates) {
  var firstDate = moment_default()(dates[0]).toDate();
  var lastDate = moment_default()(dates[dates.length - 1]).toDate();
  return firstDate.getDate() === lastDate.getDate() && firstDate.getMonth() === lastDate.getMonth() && firstDate.getFullYear() === lastDate.getFullYear();
};

/**
 * Describes `smallestFactor`
 *
 * @param {number} inputNum - any double or integer
 * @return {number} smallest factor of num
 */
var getFactors = function getFactors(inputNum) {
  var numFactors = [];
  for (var i = 1; i <= Math.floor(Math.sqrt(inputNum)); i++) {
    if (inputNum % i === 0) {
      numFactors.push(i);
      // eslint-disable-next-line no-unused-expressions
      inputNum / i !== i && numFactors.push(inputNum / i);
    }
  }
  numFactors.sort(function (x, y) {
    return x - y;
  }); // numeric sort

  return numFactors;
};

/**
 * Calculates the increment factor between ticks so there aren't more than maxTicks.
 *
 * @param {Array}  uniqueDates - all the unique dates from the input data for the chart
 * @param {number} maxTicks    - maximum number of ticks that can be displayed in the x-axis
 * @return {number} x-axis ticks increment factor
 */
var calculateXTicksIncrementFactor = function calculateXTicksIncrementFactor(uniqueDates, maxTicks) {
  var factors = [];
  var i = 1;
  // First we get all the factors of the length of the uniqueDates array
  // if the number is a prime number or near prime (with 3 factors) then we
  // step down by 1 integer and try again.
  while (factors.length <= 3) {
    factors = getFactors(uniqueDates.length - i);
    i += 1;
  }
  return factors.find(function (f) {
    return uniqueDates.length / f < maxTicks;
  });
};

/**
 * Get x-axis ticks given the unique dates and the increment factor.
 *
 * @param {Array}  uniqueDates     - all the unique dates from the input data for the chart
 * @param {number} incrementFactor - increment factor for the visible ticks.
 * @return {Array} Ticks for the x-axis.
 */
var getXTicksFromIncrementFactor = function getXTicksFromIncrementFactor(uniqueDates, incrementFactor) {
  var ticks = [];
  for (var idx = 0; idx < uniqueDates.length; idx = idx + incrementFactor) {
    ticks.push(uniqueDates[idx]);
  }

  // If the first date is missing from the ticks array, add it back in.
  if (ticks[0] !== uniqueDates[0]) {
    ticks.unshift(uniqueDates[0]);
  }
  return ticks;
};

/**
 * Returns ticks for the x-axis.
 *
 * @param {Array}  uniqueDates - all the unique dates from the input data for the chart
 * @param {number} width       - calculated page width
 * @param {string} mode        - item-comparison or time-comparison
 * @param {string} interval    - string of the interval used in the graph (hour, day, week...)
 * @return {number} number of x-axis ticks based on width and chart mode
 */
var getXTicks = function getXTicks(uniqueDates, width, mode, interval) {
  var maxTicks = calculateMaxXTicks(width, mode);
  if (uniqueDates.length >= dayTicksThreshold && interval === 'day' || uniqueDates.length >= weekTicksThreshold && interval === 'week') {
    uniqueDates = getFirstDatePerMonth(uniqueDates);
  }
  if (uniqueDates.length <= maxTicks || interval === 'hour' && areDatesInTheSameDay(uniqueDates) && width > smallBreak) {
    return uniqueDates;
  }
  var incrementFactor = calculateXTicksIncrementFactor(uniqueDates, maxTicks);
  return getXTicksFromIncrementFactor(uniqueDates, incrementFactor);
};

/**
 * Compares 2 strings and returns a list of words that are unique from s2
 *
 * @param {string}        s1        - base string to compare against
 * @param {string}        s2        - string to compare against the base string
 * @param {string|Object} splitChar - character or RegExp to use to deliminate words
 * @return {Array} of unique words that appear in s2 but not in s1, the base string
 */
var compareStrings = function compareStrings(s1, s2) {
  var splitChar = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : new RegExp([' |,'], 'g');
  var string1 = s1.split(splitChar);
  var string2 = s2.split(splitChar);
  var diff = [];
  var _long = s1.length > s2.length ? string1 : string2;
  for (var x = 0; x < _long.length; x++) {
    // eslint-disable-next-line no-unused-expressions
    string1[x] !== string2[x] && diff.push(string2[x]);
  }
  return diff;
};
var removeDuplicateDates = function removeDuplicateDates(d, i, ticks, formatter) {
  var monthDate = moment_default()(d).toDate();
  var prevMonth = i !== 0 ? ticks[i - 1] : ticks[i];
  prevMonth = prevMonth instanceof Date ? prevMonth : moment_default()(prevMonth).toDate();
  return i === 0 ? formatter(monthDate) : compareStrings(formatter(prevMonth), formatter(monthDate)).join(' ');
};
var drawXAxis = function drawXAxis(node, params, scales, formats) {
  var height = scales.yScale.range()[0];
  var ticks = getXTicks(params.uniqueDates, scales.xScale.range()[1], params.mode, params.interval);
  if (params.chartType === 'line') {
    ticks = ticks.map(function (d) {
      return moment_default()(d).toDate();
    });
  }
  node.append('g').attr('class', 'axis').attr('aria-hidden', 'true').attr('transform', "translate(0, ".concat(height, ")")).call((0,d3_axis_src/* axisBottom */.l7)(scales.xScale).tickValues(ticks).tickFormat(function (d, i) {
    return params.interval === 'hour' ? formats.xFormat(d instanceof Date ? d : moment_default()(d).toDate()) : removeDuplicateDates(d, i, ticks, formats.xFormat);
  }));
  node.append('g').attr('class', 'axis axis-month').attr('aria-hidden', 'true').attr('transform', "translate(0, ".concat(height + 14, ")")).call((0,d3_axis_src/* axisBottom */.l7)(scales.xScale).tickValues(ticks).tickFormat(function (d, i) {
    return removeDuplicateDates(d, i, ticks, formats.x2Format);
  }));
  node.append('g').attr('class', 'pipes').attr('transform', "translate(0, ".concat(height, ")")).call((0,d3_axis_src/* axisBottom */.l7)(scales.xScale).tickValues(ticks).tickSize(5).tickFormat(''));
};
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/axis-y.js


/**
 * External dependencies
 */

var calculateYGridValues = function calculateYGridValues(numberOfTicks, limit, roundValues) {
  var grids = [];
  for (var i = 0; i < numberOfTicks; i++) {
    var val = (i + 1) / numberOfTicks * limit;
    var rVal = roundValues ? Math.round(val) : val;
    if (grids[grids.length - 1] !== rVal) {
      grids.push(rVal);
    }
  }
  return grids;
};
var getNegativeYGrids = function getNegativeYGrids(yMin, step) {
  if (yMin >= 0) {
    return [];
  }
  var numberOfTicks = Math.ceil(-yMin / step);
  return calculateYGridValues(numberOfTicks, yMin, yMin < -1);
};
var getPositiveYGrids = function getPositiveYGrids(yMax, step) {
  if (yMax <= 0) {
    return [];
  }
  var numberOfTicks = Math.ceil(yMax / step);
  return calculateYGridValues(numberOfTicks, yMax, yMax > 1);
};
var getYGrids = function getYGrids(yMin, yMax, step) {
  return [0].concat((0,toConsumableArray/* default */.A)(getNegativeYGrids(yMin, step)), (0,toConsumableArray/* default */.A)(getPositiveYGrids(yMax, step)));
};
var drawYAxis = function drawYAxis(node, scales, formats, margin, isRTL) {
  var yGrids = getYGrids(scales.yScale.domain()[0], scales.yScale.domain()[1], scales.step);
  var width = scales.xScale.range()[1];
  var xPosition = isRTL ? width + margin.left + margin.right / 2 - 15 : -margin.left / 2 - 15;
  var withPositiveValuesClass = scales.yMin >= 0 || scales.yMax > 0 ? ' with-positive-ticks' : '';
  node.append('g').attr('class', 'grid' + withPositiveValuesClass).attr('transform', "translate(-".concat(margin.left, ", 0)")).call((0,d3_axis_src/* axisLeft */.V4)(scales.yScale).tickValues(yGrids).tickSize(-width - margin.left - margin.right).tickFormat(''));
  node.append('g').attr('class', 'axis y-axis').attr('aria-hidden', 'true').attr('transform', 'translate(' + xPosition + ', 12)').attr('text-anchor', 'start').call((0,d3_axis_src/* axisLeft */.V4)(scales.yScale).tickValues(scales.yMax === 0 && scales.yMin === 0 ? [yGrids[0]] : yGrids).tickFormat(function (d) {
    if (d > -1 && d < 1 && formats.yBelow1Format) {
      return formats.yBelow1Format(d);
    }
    return formats.yFormat(d);
  }));
};
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/axis.js
/**
 * Internal dependencies
 */


var drawAxis = function drawAxis(node, params, scales, formats, margin, isRTL) {
  drawXAxis(node, params, scales, formats);
  drawYAxis(node, scales, formats, margin, isRTL);
  node.selectAll('.domain').remove();
  node.selectAll('.axis .tick line').remove();
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/selection/on.js
var on = __webpack_require__("../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/selection/on.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/bar-chart.js





/**
 * External dependencies
 */



var drawBars = function drawBars(node, data, params, scales, formats, tooltip) {
  var height = scales.yScale.range()[0];
  var barGroup = node.append('g').attr('class', 'bars').selectAll('g').data(data).enter().append('g').attr('transform', function (d) {
    return "translate(".concat(scales.xScale(d.date), ", 0)");
  }).attr('class', 'bargroup').attr('role', 'region').attr('aria-label', function (d) {
    return params.mode === 'item-comparison' ? formats.screenReaderFormat(d.date instanceof Date ? d.date : moment_default()(d.date).toDate()) : null;
  });
  barGroup.append('rect').attr('class', 'barfocus').attr('x', 0).attr('y', 0).attr('width', scales.xGroupScale.range()[1]).attr('height', height).attr('opacity', '0').on('mouseover', function (d, i, nodes) {
    tooltip.show(data.find(function (e) {
      return e.date === d.date;
    }), on/* event */.f0.target, nodes[i].parentNode);
  }).on('mouseout', function () {
    return tooltip.hide();
  });
  var basePosition = scales.yScale(0);
  barGroup.selectAll('.bar').data(function (d) {
    return params.visibleKeys.map(function (row) {
      return {
        key: row.key,
        focus: row.focus,
        value: (0,lodash.get)(d, [row.key, 'value'], 0),
        label: row.label,
        visible: row.visible,
        date: d.date
      };
    });
  }).enter().append('rect').attr('class', 'bar').attr('x', function (d) {
    return scales.xGroupScale(d.key);
  }).attr('y', function (d) {
    return Math.min(basePosition, scales.yScale(d.value));
  }).attr('width', scales.xGroupScale.bandwidth()).attr('height', function (d) {
    return Math.abs(basePosition - scales.yScale(d.value));
  }).attr('fill', function (d) {
    return params.getColor(d.key);
  }).attr('pointer-events', 'none').attr('tabindex', '0').attr('aria-label', function (d) {
    var label = d.label || d.key;
    if (params.mode === 'time-comparison') {
      var dayData = data.find(function (e) {
        return e.date === d.date;
      });
      label = formats.screenReaderFormat(moment_default()(dayData[d.key].labelDate).toDate());
    }
    return "".concat(label, " ").concat(tooltip.valueFormat(d.value));
  }).style('opacity', function (d) {
    var opacity = d.focus ? 1 : 0.1;
    return d.visible ? opacity : 0;
  }).on('focus', function (d, i, nodes) {
    var targetNode = d.value > 0 ? on/* event */.f0.target : on/* event */.f0.target.parentNode;
    tooltip.show(data.find(function (e) {
      return e.date === d.date;
    }), targetNode, nodes[i].parentNode);
  }).on('blur', function () {
    return tooltip.hide();
  });
};
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reverse.js
var es_array_reverse = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reverse.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-shape@1.3.7/node_modules/d3-shape/src/line.js + 4 modules
var line = __webpack_require__("../../node_modules/.pnpm/d3-shape@1.3.7/node_modules/d3-shape/src/line.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/line-chart.js









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


/**
 * Describes getDateSpaces
 *
 * @param {Array}    data        - The chart component's `data` prop.
 * @param {Array}    uniqueDates - from `getUniqueDates`
 * @param {Array}    visibleKeys - visible keys from the input data for the chart
 * @param {number}   width       - calculated width of the charting space
 * @param {Function} xScale      - from `getXLineScale`
 * @return {Array} that includes the date, start (x position) and width to mode the mouseover rectangles
 */
var getDateSpaces = function getDateSpaces(data, uniqueDates, visibleKeys, width, xScale) {
  var reversedKeys = visibleKeys.slice().reverse();
  return uniqueDates.map(function (d, i) {
    var datapoints = (0,lodash.first)(data.filter(function (item) {
      return item.date === d;
    }));
    var xNow = xScale(moment_default()(d).toDate());
    var xPrev = i >= 1 ? xScale(moment_default()(uniqueDates[i - 1]).toDate()) : xScale(moment_default()(uniqueDates[0]).toDate());
    var xNext = i < uniqueDates.length - 1 ? xScale(moment_default()(uniqueDates[i + 1]).toDate()) : xScale(moment_default()(uniqueDates[uniqueDates.length - 1]).toDate());
    var xWidth = i === 0 ? xNext - xNow : xNow - xPrev;
    var xStart = i === 0 ? 0 : xNow - xWidth / 2;
    xWidth = i === 0 || i === uniqueDates.length - 1 ? xWidth / 2 : xWidth;
    return {
      date: d,
      start: uniqueDates.length > 1 ? xStart : 0,
      width: uniqueDates.length > 1 ? xWidth : width,
      values: reversedKeys.map(function (_ref) {
        var key = _ref.key;
        var datapoint = datapoints[key];
        if (!datapoint) {
          return null;
        }
        return {
          key: key,
          value: datapoint.value,
          date: d
        };
      }).filter(Boolean)
    };
  });
};

/**
 * Describes getLine
 *
 * @param {Function} xScale - from `getXLineScale`.
 * @param {Function} yScale - from `getYScale`.
 * @return {Function} the D3 line function for plotting all category values
 */
var getLine = function getLine(xScale, yScale) {
  return (0,line/* default */.A)().x(function (d) {
    return xScale(moment_default()(d.date).toDate());
  }).y(function (d) {
    return yScale(d.value);
  });
};

/**
 * Describes `getLineData`
 *
 * @param {Array} data        - The chart component's `data` prop.
 * @param {Array} orderedKeys - from `getOrderedKeys`.
 * @return {Array} an array objects with a category `key` and an array of `values` with `date` and `value` properties
 */
var getLineData = function getLineData(data, orderedKeys) {
  return orderedKeys.map(function (row) {
    return {
      key: row.key,
      focus: row.focus,
      visible: row.visible,
      label: row.label,
      values: data.map(function (d) {
        return {
          // To have the same X-axis scale, we use the same dates for all lines.
          date: d.date,
          // To have actual date for the screenReader, we need to use label date.
          labelDate: d[row.key].labelDate,
          focus: row.focus,
          value: (0,lodash.get)(d, [row.key, 'value'], 0),
          visible: row.visible
        };
      })
    };
  });
};
var drawLines = function drawLines(node, data, params, scales, formats, tooltip) {
  var height = scales.yScale.range()[0];
  var width = scales.xScale.range()[1];
  var line = getLine(scales.xScale, scales.yScale);
  var lineData = getLineData(data, params.visibleKeys);
  var series = node.append('g').attr('class', 'lines').selectAll('.line-g').data(lineData.filter(function (d) {
    return d.visible;
  }).reverse()).enter().append('g').attr('class', 'line-g').attr('role', 'region').attr('aria-label', function (d) {
    return d.label || d.key;
  });
  var dateSpaces = getDateSpaces(data, params.uniqueDates, params.visibleKeys, width, scales.xScale);
  var lineStroke = width <= wideBreak || params.uniqueDates.length > 50 ? 2 : 3;
  lineStroke = width <= smallBreak ? 1.25 : lineStroke;
  var dotRadius = width <= wideBreak ? 4 : 6;

  // eslint-disable-next-line no-unused-expressions
  params.uniqueDates.length > 1 && series.append('path').attr('fill', 'none').attr('stroke-width', lineStroke).attr('stroke-linejoin', 'round').attr('stroke-linecap', 'round').attr('stroke', function (d) {
    return params.getColor(d.key);
  }).style('opacity', function (d) {
    var opacity = d.focus ? 1 : 0.1;
    return d.visible ? opacity : 0;
  }).attr('d', function (d) {
    return line(d.values);
  });
  var minDataPointSpacing = 36;
  // eslint-disable-next-line no-unused-expressions
  width / params.uniqueDates.length > minDataPointSpacing && series.selectAll('circle').data(function (d, i) {
    return d.values.map(function (row) {
      return _objectSpread(_objectSpread({}, row), {}, {
        i: i,
        visible: d.visible,
        key: d.key
      });
    });
  }).enter().append('circle').attr('r', dotRadius).attr('fill', function (d) {
    return params.getColor(d.key);
  }).attr('stroke', '#fff').attr('stroke-width', lineStroke + 1).style('opacity', function (d) {
    var opacity = d.focus ? 1 : 0.1;
    return d.visible ? opacity : 0;
  }).attr('cx', function (d) {
    return scales.xScale(moment_default()(d.date).toDate());
  }).attr('cy', function (d) {
    return scales.yScale(d.value);
  }).attr('tabindex', '0').attr('role', 'graphics-symbol').attr('aria-label', function (d) {
    var label = formats.screenReaderFormat(d.labelDate instanceof Date ? d.labelDate : moment_default()(d.labelDate).toDate());
    return "".concat(label, " ").concat(tooltip.valueFormat(d.value));
  }).on('focus', function (d, i, nodes) {
    tooltip.show(data.find(function (e) {
      return e.date === d.date;
    }), nodes[i].parentNode, on/* event */.f0.target);
  }).on('blur', function () {
    return tooltip.hide();
  });
  var focus = node.append('g').attr('class', 'focusspaces').selectAll('.focus').data(dateSpaces).enter().append('g').attr('class', 'focus');
  var focusGrid = focus.append('g').attr('class', 'focus-grid').attr('opacity', '0');
  focusGrid.append('line').attr('x1', function (d) {
    return scales.xScale(moment_default()(d.date).toDate());
  }).attr('y1', 0).attr('x2', function (d) {
    return scales.xScale(moment_default()(d.date).toDate());
  }).attr('y2', height);
  focusGrid.selectAll('circle').data(function (d) {
    return d.values;
  }).enter().append('circle').attr('r', dotRadius + 2).attr('fill', function (d) {
    return params.getColor(d.key);
  }).attr('stroke', '#fff').attr('stroke-width', lineStroke + 2).attr('cx', function (d) {
    return scales.xScale(moment_default()(d.date).toDate());
  }).attr('cy', function (d) {
    return scales.yScale(d.value);
  });
  focus.append('rect').attr('class', 'focus-g').attr('x', function (d) {
    return d.start;
  }).attr('y', 0).attr('width', function (d) {
    return d.width;
  }).attr('height', height).attr('opacity', 0).on('mouseover', function (d, i, nodes) {
    var isTooltipLeftAligned = (i === 0 || i === dateSpaces.length - 1) && params.uniqueDates.length > 1;
    var elementWidthRatio = isTooltipLeftAligned ? 0 : 0.5;
    tooltip.show(data.find(function (e) {
      return e.date === d.date;
    }), on/* event */.f0.target, nodes[i].parentNode, elementWidthRatio);
  }).on('mouseout', function () {
    return tooltip.hide();
  });
};
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/utils/tooltip.js





/**
 * External dependencies
 */


var ChartTooltip = /*#__PURE__*/function () {
  function ChartTooltip() {
    (0,classCallCheck/* default */.A)(this, ChartTooltip);
    this.ref = null;
    this.chart = null;
    this.position = '';
    this.title = '';
    this.labelFormat = '';
    this.valueFormat = '';
    this.visibleKeys = '';
    this.getColor = null;
    this.margin = 24;
  }
  (0,createClass/* default */.A)(ChartTooltip, [{
    key: "calculateXPosition",
    value: function calculateXPosition(elementCoords, chartCoords, elementWidthRatio) {
      var tooltipSize = this.ref.getBoundingClientRect();
      var d3BaseCoords = this.ref.parentNode.querySelector('.d3-base').getBoundingClientRect();
      var leftMargin = Math.max(d3BaseCoords.left, chartCoords.left);
      if (this.position === 'below') {
        return Math.max(this.margin, Math.min(elementCoords.left + elementCoords.width * 0.5 - tooltipSize.width / 2 - leftMargin, d3BaseCoords.width - tooltipSize.width - this.margin));
      }
      var xPosition = elementCoords.left + elementCoords.width * elementWidthRatio + this.margin - leftMargin;
      if (xPosition + tooltipSize.width + this.margin > d3BaseCoords.width) {
        return Math.max(this.margin, elementCoords.left + elementCoords.width * (1 - elementWidthRatio) - tooltipSize.width - this.margin - leftMargin);
      }
      return xPosition;
    }
  }, {
    key: "calculateYPosition",
    value: function calculateYPosition(elementCoords, chartCoords) {
      if (this.position === 'below') {
        return chartCoords.height;
      }
      var tooltipSize = this.ref.getBoundingClientRect();
      var yPosition = elementCoords.top + this.margin - chartCoords.top;
      if (yPosition + tooltipSize.height + this.margin > chartCoords.height) {
        return Math.max(0, elementCoords.top - tooltipSize.height - this.margin - chartCoords.top);
      }
      return yPosition;
    }
  }, {
    key: "calculatePosition",
    value: function calculatePosition(element) {
      var elementWidthRatio = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
      var elementCoords = element.getBoundingClientRect();
      var chartCoords = this.chart.getBoundingClientRect();
      if (this.position === 'below') {
        elementWidthRatio = 0;
      }
      return {
        x: this.calculateXPosition(elementCoords, chartCoords, elementWidthRatio),
        y: this.calculateYPosition(elementCoords, chartCoords)
      };
    }
  }, {
    key: "hide",
    value: function hide() {
      (0,src_select/* default */.A)(this.chart).selectAll('.barfocus, .focus-grid').attr('opacity', '0');
      (0,src_select/* default */.A)(this.ref).style('visibility', 'hidden');
    }
  }, {
    key: "getTooltipRowLabel",
    value: function getTooltipRowLabel(d, row) {
      if (d[row.key].labelDate) {
        return this.labelFormat(moment_default()(d[row.key].labelDate).toDate());
      }
      return row.label || row.key;
    }
  }, {
    key: "show",
    value: function show(d, triggerElement, parentNode) {
      var _this = this;
      var elementWidthRatio = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 1;
      if (!this.visibleKeys.length) {
        return;
      }
      (0,src_select/* default */.A)(parentNode).select('.focus-grid, .barfocus').attr('opacity', '1');
      var position = this.calculatePosition(triggerElement, elementWidthRatio);
      var keys = this.visibleKeys.map(function (row) {
        return "\n\t\t\t\t\t<li class=\"key-row\">\n\t\t\t\t\t\t<div class=\"key-container\">\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"key-color\"\n\t\t\t\t\t\t\t\tstyle=\"background-color: ".concat(_this.getColor(row.key), "\">\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<span class=\"key-key\">").concat(_this.getTooltipRowLabel(d, row), "</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<span class=\"key-value\">").concat(_this.valueFormat(d[row.key].value), "</span>\n\t\t\t\t\t</li>\n\t\t\t\t");
      });
      var tooltipTitle = this.title ? this.title : this.labelFormat(moment_default()(d.date).toDate());
      (0,src_select/* default */.A)(this.ref).style('left', position.x + 'px').style('top', position.y + 'px').style('visibility', 'visible').html("\n\t\t\t\t<div>\n\t\t\t\t\t<h4>".concat(tooltipTitle, "</h4>\n\t\t\t\t\t<ul>\n\t\t\t\t\t").concat(keys.join(''), "\n\t\t\t\t\t</ul>\n\t\t\t\t</div>\n\t\t\t"));
    }
  }]);
  return ChartTooltip;
}();
/* harmony default export */ const utils_tooltip = (ChartTooltip);
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/d3chart/chart.js












function chart_createSuper(Derived) {
  var hasNativeReflectConstruct = chart_isNativeReflectConstruct();
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
function chart_isNativeReflectConstruct() {
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









var isRTL = function isRTL() {
  return document.documentElement.dir === 'rtl';
};

/**
 * A simple D3 line and bar chart component for timeseries data in React.
 */
var D3Chart = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(D3Chart, _Component);
  var _super = chart_createSuper(D3Chart);
  function D3Chart(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, D3Chart);
    _this = _super.call(this, props);
    _this.drawChart = _this.drawChart.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.getParams = _this.getParams.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.tooltipRef = (0,react.createRef)();
    return _this;
  }
  (0,createClass/* default */.A)(D3Chart, [{
    key: "getFormatParams",
    value: function getFormatParams() {
      var _this$props = this.props,
        screenReaderFormat = _this$props.screenReaderFormat,
        xFormat = _this$props.xFormat,
        x2Format = _this$props.x2Format,
        yFormat = _this$props.yFormat,
        yBelow1Format = _this$props.yBelow1Format;
      return {
        screenReaderFormat: getFormatter(screenReaderFormat, src_defaultLocale/* timeFormat */.DC),
        xFormat: getFormatter(xFormat, src_defaultLocale/* timeFormat */.DC),
        x2Format: getFormatter(x2Format, src_defaultLocale/* timeFormat */.DC),
        yBelow1Format: getFormatter(yBelow1Format),
        yFormat: getFormatter(yFormat)
      };
    }
  }, {
    key: "getScaleParams",
    value: function getScaleParams(uniqueDates) {
      var _this$props2 = this.props,
        data = _this$props2.data,
        height = _this$props2.height,
        orderedKeys = _this$props2.orderedKeys,
        chartType = _this$props2.chartType;
      var margin = this.getMargin();
      var adjHeight = height - margin.top - margin.bottom;
      var adjWidth = this.getWidth() - margin.left - margin.right;
      var _getYScaleLimits = getYScaleLimits(data),
        yMax = _getYScaleLimits.upper,
        yMin = _getYScaleLimits.lower,
        step = _getYScaleLimits.step;
      var yScale = getYScale(adjHeight, yMin, yMax);
      if (chartType === 'line') {
        return {
          step: step,
          xScale: getXLineScale(uniqueDates, adjWidth),
          yMax: yMax,
          yMin: yMin,
          yScale: yScale
        };
      }
      var compact = this.shouldBeCompact();
      var xScale = getXScale(uniqueDates, adjWidth, compact);
      return {
        step: step,
        xGroupScale: getXGroupScale(orderedKeys, xScale, compact),
        xScale: xScale,
        yMax: yMax,
        yMin: yMin,
        yScale: yScale
      };
    }
  }, {
    key: "getParams",
    value: function getParams(uniqueDates) {
      var _this$props3 = this.props,
        chartType = _this$props3.chartType,
        colorScheme = _this$props3.colorScheme,
        data = _this$props3.data,
        interval = _this$props3.interval,
        mode = _this$props3.mode,
        orderedKeys = _this$props3.orderedKeys;
      var newOrderedKeys = orderedKeys || getOrderedKeys(data);
      var visibleKeys = newOrderedKeys.filter(function (key) {
        return key.visible;
      });
      var colorKeys = newOrderedKeys.length > selectionLimit ? visibleKeys : newOrderedKeys;
      return {
        getColor: getColor(colorKeys, colorScheme),
        interval: interval,
        mode: mode,
        chartType: chartType,
        uniqueDates: uniqueDates,
        visibleKeys: visibleKeys
      };
    }
  }, {
    key: "createTooltip",
    value: function createTooltip(chart, getColorFunction, visibleKeys) {
      var _this$props4 = this.props,
        tooltipLabelFormat = _this$props4.tooltipLabelFormat,
        tooltipPosition = _this$props4.tooltipPosition,
        tooltipTitle = _this$props4.tooltipTitle,
        tooltipValueFormat = _this$props4.tooltipValueFormat;
      var tooltip = new utils_tooltip();
      tooltip.ref = this.tooltipRef.current;
      tooltip.chart = chart;
      tooltip.position = tooltipPosition;
      tooltip.title = tooltipTitle;
      tooltip.labelFormat = getFormatter(tooltipLabelFormat, src_defaultLocale/* timeFormat */.DC);
      tooltip.valueFormat = getFormatter(tooltipValueFormat);
      tooltip.visibleKeys = visibleKeys;
      tooltip.getColor = getColorFunction;
      this.tooltip = tooltip;
    }
  }, {
    key: "drawChart",
    value: function drawChart(node) {
      var _this$props5 = this.props,
        data = _this$props5.data,
        dateParser = _this$props5.dateParser,
        chartType = _this$props5.chartType;
      var margin = this.getMargin();
      var uniqueDates = getUniqueDates(data, dateParser);
      var formats = this.getFormatParams();
      var params = this.getParams(uniqueDates);
      var scales = this.getScaleParams(uniqueDates);
      var g = node.attr('id', 'chart').append('g').attr('transform', "translate(".concat(margin.left, ", ").concat(margin.top, ")"));
      this.createTooltip(g.node(), params.getColor, params.visibleKeys);
      drawAxis(g, params, scales, formats, margin, isRTL());
      // eslint-disable-next-line no-unused-expressions
      chartType === 'line' && drawLines(g, data, params, scales, formats, this.tooltip);
      // eslint-disable-next-line no-unused-expressions
      chartType === 'bar' && drawBars(g, data, params, scales, formats, this.tooltip);
    }
  }, {
    key: "shouldBeCompact",
    value: function shouldBeCompact() {
      var _this$props6 = this.props,
        data = _this$props6.data,
        chartType = _this$props6.chartType,
        width = _this$props6.width;
      if (chartType !== 'bar') {
        return false;
      }
      var margin = this.getMargin();
      var widthWithoutMargins = width - margin.left - margin.right;
      var columnsPerDate = data && data.length ? Object.keys(data[0]).length - 1 : 0;
      var minimumWideWidth = data.length * (columnsPerDate + 1);
      return widthWithoutMargins < minimumWideWidth;
    }
  }, {
    key: "getMargin",
    value: function getMargin() {
      var margin = this.props.margin;
      if (isRTL()) {
        return {
          bottom: margin.bottom,
          left: margin.right,
          right: margin.left,
          top: margin.top
        };
      }
      return margin;
    }
  }, {
    key: "getWidth",
    value: function getWidth() {
      var _this$props7 = this.props,
        data = _this$props7.data,
        chartType = _this$props7.chartType,
        width = _this$props7.width;
      if (chartType !== 'bar') {
        return width;
      }
      var margin = this.getMargin();
      var columnsPerDate = data && data.length ? Object.keys(data[0]).length - 1 : 0;
      var minimumWidth = this.shouldBeCompact() ? data.length * columnsPerDate : data.length * (columnsPerDate + 1);
      return Math.max(width, minimumWidth + margin.left + margin.right);
    }
  }, {
    key: "getEmptyMessage",
    value: function getEmptyMessage() {
      var _this$props8 = this.props,
        baseValue = _this$props8.baseValue,
        data = _this$props8.data,
        emptyMessage = _this$props8.emptyMessage;
      if (emptyMessage && isDataEmpty(data, baseValue)) {
        return (0,react.createElement)("div", {
          className: "d3-chart__empty-message"
        }, emptyMessage);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props9 = this.props,
        className = _this$props9.className,
        data = _this$props9.data,
        height = _this$props9.height,
        orderedKeys = _this$props9.orderedKeys,
        chartType = _this$props9.chartType;
      var computedWidth = this.getWidth();
      return (0,react.createElement)("div", {
        className: classnames_default()('d3-chart__container', className),
        style: {
          height: height
        }
      }, this.getEmptyMessage(), (0,react.createElement)("div", {
        className: "d3-chart__tooltip",
        ref: this.tooltipRef
      }), (0,react.createElement)(D3Base, {
        className: classnames_default()(className),
        data: data,
        drawChart: this.drawChart,
        height: height,
        orderedKeys: orderedKeys,
        tooltip: this.tooltip,
        chartType: chartType,
        width: computedWidth
      }));
    }
  }]);
  return D3Chart;
}(react.Component);
D3Chart.propTypes = {
  /**
   * Base chart value. If no data value is different than the baseValue, the
   * `emptyMessage` will be displayed if provided.
   */
  baseValue: (prop_types_default()).number,
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * A chromatic color function to be passed down to d3.
   */
  colorScheme: (prop_types_default()).func,
  /**
   * An array of data.
   */
  data: (prop_types_default()).array.isRequired,
  /**
   * Format to parse dates into d3 time format
   */
  dateParser: (prop_types_default()).string.isRequired,
  /**
   * The message to be displayed if there is no data to render. If no message is provided,
   * nothing will be displayed.
   */
  emptyMessage: (prop_types_default()).string,
  /**
   * Height of the `svg`.
   */
  height: (prop_types_default()).number,
  /**
   * Interval specification (hourly, daily, weekly etc.)
   */
  interval: prop_types_default().oneOf(['hour', 'day', 'week', 'month', 'quarter', 'year']),
  /**
   * Margins for axis and chart padding.
   */
  margin: prop_types_default().shape({
    bottom: (prop_types_default()).number,
    left: (prop_types_default()).number,
    right: (prop_types_default()).number,
    top: (prop_types_default()).number
  }),
  /**
   * `items-comparison` (default) or `time-comparison`, this is used to generate correct
   * ARIA properties.
   */
  mode: prop_types_default().oneOf(['item-comparison', 'time-comparison']),
  /**
   * A datetime formatting string or overriding function to format the screen reader labels.
   */
  screenReaderFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * The list of labels for this chart.
   */
  orderedKeys: (prop_types_default()).array,
  /**
   * A datetime formatting string or overriding function to format the tooltip label.
   */
  tooltipLabelFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * A number formatting string or function to format the value displayed in the tooltips.
   */
  tooltipValueFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * The position where to render the tooltip can be `over` the chart or `below` the chart.
   */
  tooltipPosition: prop_types_default().oneOf(['below', 'over']),
  /**
   * A string to use as a title for the tooltip. Takes preference over `tooltipFormat`.
   */
  tooltipTitle: (prop_types_default()).string,
  /**
   * Chart type of either `line` or `bar`.
   */
  chartType: prop_types_default().oneOf(['bar', 'line']),
  /**
   * Width of the `svg`.
   */
  width: (prop_types_default()).number,
  /**
   * A datetime formatting string or function, passed to d3TimeFormat.
   */
  xFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * A datetime formatting string or function, passed to d3TimeFormat.
   */
  x2Format: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * A number formatting string or function for numbers between -1 and 1, passed to d3Format.
   * If missing, `yFormat` will be used.
   */
  yBelow1Format: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * A number formatting string or function, passed to d3Format.
   */
  yFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func])
};
D3Chart.defaultProps = {
  baseValue: 0,
  data: [],
  dateParser: '%Y-%m-%dT%H:%M:%S',
  height: 200,
  margin: {
    bottom: 30,
    left: 40,
    right: 0,
    top: 20
  },
  mode: 'time-comparison',
  screenReaderFormat: '%B %-d, %Y',
  tooltipPosition: 'over',
  tooltipLabelFormat: '%B %-d, %Y',
  tooltipValueFormat: ',',
  chartType: 'line',
  width: 600,
  xFormat: '%Y-%m-%d',
  x2Format: '',
  yBelow1Format: '.3~f',
  yFormat: '.3~s'
};
/* harmony default export */ const chart = (D3Chart);
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/index.js






















function src_chart_createSuper(Derived) {
  var hasNativeReflectConstruct = src_chart_isNativeReflectConstruct();
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
function src_chart_isNativeReflectConstruct() {
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





function getD3CurrencyFormat(symbol, position) {
  switch (position) {
    case 'left_space':
      return [symbol + ' ', ''];
    case 'right':
      return ['', symbol];
    case 'right_space':
      return ['', ' ' + symbol];
    case 'left':
    default:
      return [symbol, ''];
  }
}

/**
 * A chart container using d3, to display timeseries data with an interactive legend.
 */
var Chart = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(Chart, _Component);
  var _super = src_chart_createSuper(Chart);
  function Chart(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, Chart);
    _this = _super.call(this, props);
    _this.chartBodyRef = (0,react.createRef)();
    var dataKeys = _this.getDataKeys();
    _this.state = {
      focusedKeys: [],
      visibleKeys: dataKeys.slice(0, selectionLimit),
      width: 0
    };
    _this.prevDataKeys = dataKeys.sort();
    _this.handleTypeToggle = _this.handleTypeToggle.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.handleLegendToggle = _this.handleLegendToggle.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.handleLegendHover = _this.handleLegendHover.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateDimensions = _this.updateDimensions.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.getVisibleData = (0,memoize_one_esm/* default */.A)(_this.getVisibleData);
    _this.getOrderedKeys = (0,memoize_one_esm/* default */.A)(_this.getOrderedKeys);
    _this.setInterval = _this.setInterval.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(Chart, [{
    key: "getDataKeys",
    value: function getDataKeys() {
      var _this$props = this.props,
        data = _this$props.data,
        filterParam = _this$props.filterParam,
        mode = _this$props.mode,
        query = _this$props.query;
      if (mode === 'item-comparison') {
        var selectedIds = filterParam ? (0,src/* getIdsFromQuery */.DF)(query[filterParam]) : [];
        return this.getOrderedKeys([], [], selectedIds).map(function (orderedItem) {
          return orderedItem.key;
        });
      }
      return getUniqueKeys(data);
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate() {
      var data = this.props.data;
      if (!data || !data.length) {
        return;
      }
      var uniqueKeys = getUniqueKeys(data).sort();
      if (!(0,lodash.isEqual)(uniqueKeys, this.prevDataKeys)) {
        var dataKeys = this.getDataKeys();
        this.prevDataKeys = uniqueKeys;
        /* eslint-disable react/no-did-update-set-state */
        this.setState({
          visibleKeys: dataKeys.slice(0, selectionLimit)
        });
        /* eslint-enable react/no-did-update-set-state */
      }
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      this.updateDimensions();
      this.setD3DefaultFormat();
      window.addEventListener('resize', this.updateDimensions);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      window.removeEventListener('resize', this.updateDimensions);
    }
  }, {
    key: "setD3DefaultFormat",
    value: function setD3DefaultFormat() {
      var _this$props$currency = this.props.currency,
        currencySymbol = _this$props$currency.symbol,
        symbolPosition = _this$props$currency.symbolPosition,
        decimal = _this$props$currency.decimalSeparator,
        thousands = _this$props$currency.thousandSeparator;
      (0,defaultLocale/* default */.Ay)({
        decimal: decimal,
        thousands: thousands,
        grouping: [3],
        currency: getD3CurrencyFormat(currencySymbol, symbolPosition)
      });
    }
  }, {
    key: "getOrderedKeys",
    value: function getOrderedKeys(focusedKeys, visibleKeys) {
      var selectedIds = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
      var _this$props2 = this.props,
        data = _this$props2.data,
        legendTotals = _this$props2.legendTotals,
        mode = _this$props2.mode;
      if (!data || data.length === 0) {
        return [];
      }
      var uniqueKeys = data.reduce(function (accum, curr) {
        Object.entries(curr).forEach(function (_ref) {
          var _ref2 = (0,slicedToArray/* default */.A)(_ref, 2),
            key = _ref2[0],
            value = _ref2[1];
          if (key !== 'date' && !accum[key]) {
            accum[key] = value.label;
          }
        });
        return accum;
      }, {});
      var updatedKeys = Object.entries(uniqueKeys).map(function (_ref3) {
        var _ref4 = (0,slicedToArray/* default */.A)(_ref3, 2),
          key = _ref4[0],
          label = _ref4[1];
        label = (0,purify.sanitize)(label, {
          ALLOWED_TAGS: []
        });
        return {
          focus: focusedKeys.length === 0 || focusedKeys.includes(key),
          key: key,
          label: label,
          total: legendTotals && typeof legendTotals[key] !== 'undefined' ? legendTotals[key] : data.reduce(function (a, c) {
            return a + c[key].value;
          }, 0),
          visible: visibleKeys.includes(key)
        };
      });
      if (mode === 'item-comparison') {
        return updatedKeys.sort(function (a, b) {
          return b.total - a.total;
        }).filter(function (key) {
          return key.total > 0 || selectedIds.includes(parseInt(key.key, 10));
        });
      }
      return updatedKeys;
    }
  }, {
    key: "handleTypeToggle",
    value: function handleTypeToggle(chartType) {
      if (this.props.chartType !== chartType) {
        var _this$props3 = this.props,
          path = _this$props3.path,
          query = _this$props3.query;
        (0,src/* updateQueryString */.Ze)({
          chartType: chartType
        }, path, query);
      }
    }
  }, {
    key: "handleLegendToggle",
    value: function handleLegendToggle(event) {
      var interactiveLegend = this.props.interactiveLegend;
      if (!interactiveLegend) {
        return;
      }
      var key = event.currentTarget.id.split('_').pop();
      var _this$state = this.state,
        focusedKeys = _this$state.focusedKeys,
        visibleKeys = _this$state.visibleKeys;
      if (visibleKeys.includes(key)) {
        this.setState({
          focusedKeys: (0,lodash.without)(focusedKeys, key),
          visibleKeys: (0,lodash.without)(visibleKeys, key)
        });
      } else {
        this.setState({
          focusedKeys: focusedKeys.concat([key]),
          visibleKeys: visibleKeys.concat([key])
        });
      }
    }
  }, {
    key: "handleLegendHover",
    value: function handleLegendHover(event) {
      if (event.type === 'mouseleave' || event.type === 'blur') {
        this.setState({
          focusedKeys: []
        });
      } else if (event.type === 'mouseenter' || event.type === 'focus') {
        var key = event.currentTarget.id.split('__').pop();
        this.setState({
          focusedKeys: [key]
        });
      }
    }
  }, {
    key: "updateDimensions",
    value: function updateDimensions() {
      this.setState({
        width: this.chartBodyRef.current.offsetWidth
      });
    }
  }, {
    key: "getVisibleData",
    value: function getVisibleData(data, orderedKeys) {
      var visibleKeys = orderedKeys.filter(function (d) {
        return d.visible;
      });
      return data.map(function (d) {
        var newRow = {
          date: d.date
        };
        visibleKeys.forEach(function (row) {
          newRow[row.key] = d[row.key];
        });
        return newRow;
      });
    }
  }, {
    key: "setInterval",
    value: function setInterval(interval) {
      var _this$props4 = this.props,
        path = _this$props4.path,
        query = _this$props4.query;
      (0,src/* updateQueryString */.Ze)({
        interval: interval
      }, path, query);
    }
  }, {
    key: "renderIntervalSelector",
    value: function renderIntervalSelector() {
      var _this$props5 = this.props,
        interval = _this$props5.interval,
        allowedIntervals = _this$props5.allowedIntervals;
      if (!allowedIntervals || allowedIntervals.length < 1) {
        return null;
      }
      var intervalLabels = {
        hour: (0,build_module.__)('By hour', 'woocommerce'),
        day: (0,build_module.__)('By day', 'woocommerce'),
        week: (0,build_module.__)('By week', 'woocommerce'),
        month: (0,build_module.__)('By month', 'woocommerce'),
        quarter: (0,build_module.__)('By quarter', 'woocommerce'),
        year: (0,build_module.__)('By year', 'woocommerce')
      };
      return (0,react.createElement)("div", {
        className: "woocommerce-chart__interval-select"
      }, (0,react.createElement)(select_control/* default */.A, {
        value: interval,
        options: allowedIntervals.map(function (allowedInterval) {
          return {
            value: allowedInterval,
            label: intervalLabels[allowedInterval]
          };
        }),
        onChange: this.setInterval
      }));
    }
  }, {
    key: "getChartHeight",
    value: function getChartHeight() {
      var _this$props6 = this.props,
        isViewportLarge = _this$props6.isViewportLarge,
        isViewportMobile = _this$props6.isViewportMobile;
      if (isViewportMobile) {
        return 180;
      }
      if (isViewportLarge) {
        return 300;
      }
      return 220;
    }
  }, {
    key: "getLegendPosition",
    value: function getLegendPosition() {
      var _this$props7 = this.props,
        legendPosition = _this$props7.legendPosition,
        mode = _this$props7.mode,
        isViewportWide = _this$props7.isViewportWide;
      if (legendPosition) {
        return legendPosition;
      }
      if (isViewportWide && mode === 'time-comparison') {
        return 'top';
      }
      if (isViewportWide && mode === 'item-comparison') {
        return 'side';
      }
      return 'bottom';
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state2 = this.state,
        focusedKeys = _this$state2.focusedKeys,
        visibleKeys = _this$state2.visibleKeys,
        width = _this$state2.width;
      var _this$props8 = this.props,
        baseValue = _this$props8.baseValue,
        chartType = _this$props8.chartType,
        data = _this$props8.data,
        dateParser = _this$props8.dateParser,
        emptyMessage = _this$props8.emptyMessage,
        filterParam = _this$props8.filterParam,
        interactiveLegend = _this$props8.interactiveLegend,
        interval = _this$props8.interval,
        isRequesting = _this$props8.isRequesting,
        isViewportLarge = _this$props8.isViewportLarge,
        itemsLabel = _this$props8.itemsLabel,
        mode = _this$props8.mode,
        query = _this$props8.query,
        screenReaderFormat = _this$props8.screenReaderFormat,
        showHeaderControls = _this$props8.showHeaderControls,
        title = _this$props8.title,
        tooltipLabelFormat = _this$props8.tooltipLabelFormat,
        tooltipValueFormat = _this$props8.tooltipValueFormat,
        tooltipTitle = _this$props8.tooltipTitle,
        valueType = _this$props8.valueType,
        xFormat = _this$props8.xFormat,
        x2Format = _this$props8.x2Format,
        yBelow1Format = _this$props8.yBelow1Format,
        yFormat = _this$props8.yFormat;
      var selectedIds = filterParam ? (0,src/* getIdsFromQuery */.DF)(query[filterParam]) : [];
      var orderedKeys = this.getOrderedKeys(focusedKeys, visibleKeys, selectedIds);
      var visibleData = isRequesting ? null : this.getVisibleData(data, orderedKeys);
      var legendPosition = this.getLegendPosition();
      var legendDirection = legendPosition === 'top' ? 'row' : 'column';
      var chartDirection = legendPosition === 'side' ? 'row' : 'column';
      var chartHeight = this.getChartHeight();
      var legend = legendPosition !== 'hidden' && isRequesting ? null : (0,react.createElement)(d3chart_legend, {
        colorScheme: viridis/* default */.Ay,
        data: orderedKeys,
        handleLegendHover: this.handleLegendHover,
        handleLegendToggle: this.handleLegendToggle,
        interactive: interactiveLegend,
        legendDirection: legendDirection,
        legendValueFormat: tooltipValueFormat,
        totalLabel: (0,build_module/* sprintf */.nv)(itemsLabel, orderedKeys.length)
      });
      var margin = {
        bottom: 50,
        left: 80,
        right: 30,
        top: 0
      };
      var d3chartYFormat = yFormat;
      var d3chartYBelow1Format = yBelow1Format;
      if (!yFormat) {
        switch (valueType) {
          case 'average':
            d3chartYFormat = ',.0f';
            break;
          case 'currency':
            d3chartYFormat = '$.3~s';
            d3chartYBelow1Format = '$.3~f';
            break;
          case 'number':
            d3chartYFormat = ',.0f';
            break;
        }
      }
      return (0,react.createElement)("div", {
        className: "woocommerce-chart"
      }, showHeaderControls && (0,react.createElement)("div", {
        className: "woocommerce-chart__header"
      }, (0,react.createElement)(header.H, {
        className: "woocommerce-chart__title"
      }, title), legendPosition === 'top' && legend, this.renderIntervalSelector(), (0,react.createElement)(menu/* default */.A, {
        className: "woocommerce-chart__types",
        orientation: "horizontal",
        role: "menubar"
      }, (0,react.createElement)(build_module_button/* default */.A, {
        className: classnames_default()('woocommerce-chart__type-button', {
          'woocommerce-chart__type-button-selected': chartType === 'line'
        }),
        title: (0,build_module.__)('Line chart', 'woocommerce'),
        "aria-checked": chartType === 'line',
        role: "menuitemradio",
        tabIndex: chartType === 'line' ? 0 : -1,
        onClick: (0,lodash.partial)(this.handleTypeToggle, 'line')
      }, (0,react.createElement)(line_graph/* default */.A, null)), (0,react.createElement)(build_module_button/* default */.A, {
        className: classnames_default()('woocommerce-chart__type-button', {
          'woocommerce-chart__type-button-selected': chartType === 'bar'
        }),
        title: (0,build_module.__)('Bar chart', 'woocommerce'),
        "aria-checked": chartType === 'bar',
        role: "menuitemradio",
        tabIndex: chartType === 'bar' ? 0 : -1,
        onClick: (0,lodash.partial)(this.handleTypeToggle, 'bar')
      }, (0,react.createElement)(stats_alt/* default */.A, null)))), (0,react.createElement)(section/* Section */.w, {
        component: false
      }, (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-chart__body', "woocommerce-chart__body-".concat(chartDirection)),
        ref: this.chartBodyRef
      }, legendPosition === 'side' && legend, isRequesting && (0,react.createElement)(react.Fragment, null, (0,react.createElement)("span", {
        className: "screen-reader-text"
      }, (0,build_module.__)('Your requested data is loading', 'woocommerce')), (0,react.createElement)(placeholder, {
        height: chartHeight
      })), !isRequesting && width > 0 && (0,react.createElement)(chart, {
        baseValue: baseValue,
        chartType: chartType,
        colorScheme: viridis/* default */.Ay,
        data: visibleData,
        dateParser: dateParser,
        height: chartHeight,
        emptyMessage: emptyMessage,
        interval: interval,
        margin: margin,
        mode: mode,
        orderedKeys: orderedKeys,
        screenReaderFormat: screenReaderFormat,
        tooltipLabelFormat: tooltipLabelFormat,
        tooltipValueFormat: tooltipValueFormat,
        tooltipPosition: isViewportLarge ? 'over' : 'below',
        tooltipTitle: tooltipTitle,
        valueType: valueType,
        width: chartDirection === 'row' ? width - 320 : width,
        xFormat: xFormat,
        x2Format: x2Format,
        yBelow1Format: d3chartYBelow1Format,
        yFormat: d3chartYFormat
      })), legendPosition === 'bottom' && (0,react.createElement)("div", {
        className: "woocommerce-chart__footer"
      }, legend)));
    }
  }]);
  return Chart;
}(react.Component);
Chart.propTypes = {
  /**
   * Allowed intervals to show in a dropdown.
   */
  allowedIntervals: (prop_types_default()).array,
  /**
   * Base chart value. If no data value is different than the baseValue, the
   * `emptyMessage` will be displayed if provided.
   */
  baseValue: (prop_types_default()).number,
  /**
   * Chart type of either `line` or `bar`.
   */
  chartType: prop_types_default().oneOf(['bar', 'line']),
  /**
   * An array of data.
   */
  data: (prop_types_default()).array.isRequired,
  /**
   * Format to parse dates into d3 time format
   */
  dateParser: (prop_types_default()).string.isRequired,
  /**
   * The message to be displayed if there is no data to render. If no message is provided,
   * nothing will be displayed.
   */
  emptyMessage: (prop_types_default()).string,
  /**
   * Name of the param used to filter items. If specified, it will be used, in combination
   * with query, to detect which elements are being used by the current filter and must be
   * displayed even if their value is 0.
   */
  filterParam: (prop_types_default()).string,
  /**
   * Label describing the legend items.
   */
  itemsLabel: (prop_types_default()).string,
  /**
   * `item-comparison` (default) or `time-comparison`, this is used to generate correct
   * ARIA properties.
   */
  mode: prop_types_default().oneOf(['item-comparison', 'time-comparison']),
  /**
   * Current path
   */
  path: (prop_types_default()).string,
  /**
   * The query string represented in object form
   */
  query: (prop_types_default()).object,
  /**
   * Whether the legend items can be activated/deactivated.
   */
  interactiveLegend: (prop_types_default()).bool,
  /**
   * Interval specification (hourly, daily, weekly etc).
   */
  interval: prop_types_default().oneOf(['hour', 'day', 'week', 'month', 'quarter', 'year']),
  /**
   * Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.
   */
  intervalData: (prop_types_default()).object,
  /**
   * Render a chart placeholder to signify an in-flight data request.
   */
  isRequesting: (prop_types_default()).bool,
  /**
   * Position the legend must be displayed in. If it's not defined, it's calculated
   * depending on the viewport width and the mode.
   */
  legendPosition: prop_types_default().oneOf(['bottom', 'side', 'top', 'hidden']),
  /**
   * Values to overwrite the legend totals. If not defined, the sum of all line values will be used.
   */
  legendTotals: (prop_types_default()).object,
  /**
   * A datetime formatting string or overriding function to format the screen reader labels.
   */
  screenReaderFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * Whether header UI controls must be displayed.
   */
  showHeaderControls: (prop_types_default()).bool,
  /**
   * A title describing this chart.
   */
  title: (prop_types_default()).string,
  /**
   * A datetime formatting string or overriding function to format the tooltip label.
   */
  tooltipLabelFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * A number formatting string or function to format the value displayed in the tooltips.
   */
  tooltipValueFormat: prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).func]),
  /**
   * A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`.
   */
  tooltipTitle: (prop_types_default()).string,
  /**
   * What type of data is to be displayed? Number, Average, String?
   */
  valueType: (prop_types_default()).string,
  /**
   * A datetime formatting string, passed to d3TimeFormat.
   */
  xFormat: (prop_types_default()).string,
  /**
   * A datetime formatting string, passed to d3TimeFormat.
   */
  x2Format: (prop_types_default()).string,
  /**
   * A number formatting string, passed to d3Format.
   */
  yBelow1Format: (prop_types_default()).string,
  /**
   * A number formatting string, passed to d3Format.
   */
  yFormat: (prop_types_default()).string,
  /**
   * A currency object passed to d3Format.
   */
  currency: (prop_types_default()).object
};
Chart.defaultProps = {
  baseValue: 0,
  chartType: 'line',
  data: [],
  dateParser: '%Y-%m-%dT%H:%M:%S',
  interactiveLegend: true,
  interval: 'day',
  isRequesting: false,
  mode: 'time-comparison',
  screenReaderFormat: '%B %-d, %Y',
  showHeaderControls: true,
  tooltipLabelFormat: '%B %-d, %Y',
  tooltipValueFormat: ',',
  xFormat: '%d',
  x2Format: '%b %Y',
  currency: {
    symbol: '$',
    symbolPosition: 'left',
    decimalSeparator: '.',
    thousandSeparator: ','
  }
};
/* harmony default export */ const src_chart = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium',
  isViewportLarge: '>= large',
  isViewportWide: '>= wide'
})(Chart));
;// CONCATENATED MODULE: ../../packages/js/components/src/chart/stories/chart.story.js

/**
 * Internal dependencies
 */

var data = [{
  date: '2018-05-30T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 21599
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 38537
  },
  Cap: {
    label: 'Cap',
    value: 106010
  },
  Tshirt: {
    label: 'Tshirt',
    value: 26784
  },
  Jeans: {
    label: 'Jeans',
    value: 35645
  },
  Headphones: {
    label: 'Headphones',
    value: 19500
  },
  Lamp: {
    label: 'Lamp',
    value: 21599
  },
  Socks: {
    label: 'Socks',
    value: 32572
  },
  Mug: {
    label: 'Mug',
    value: 10991
  },
  Case: {
    label: 'Case',
    value: 35537
  }
}, {
  date: '2018-05-31T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 14205
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 24721
  },
  Cap: {
    label: 'Cap',
    value: 70131
  },
  Tshirt: {
    label: 'Tshirt',
    value: 16784
  },
  Jeans: {
    label: 'Jeans',
    value: 25645
  },
  Headphones: {
    label: 'Headphones',
    value: 39500
  },
  Lamp: {
    label: 'Lamp',
    value: 15599
  },
  Socks: {
    label: 'Socks',
    value: 27572
  },
  Mug: {
    label: 'Mug',
    value: 110991
  },
  Case: {
    label: 'Case',
    value: 21537
  }
}, {
  date: '2018-06-01T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 10581
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 19991
  },
  Cap: {
    label: 'Cap',
    value: 53552
  },
  Tshirt: {
    label: 'Tshirt',
    value: 41784
  },
  Jeans: {
    label: 'Jeans',
    value: 17645
  },
  Headphones: {
    label: 'Headphones',
    value: 22500
  },
  Lamp: {
    label: 'Lamp',
    value: 25599
  },
  Socks: {
    label: 'Socks',
    value: 14572
  },
  Mug: {
    label: 'Mug',
    value: 20991
  },
  Case: {
    label: 'Case',
    value: 11537
  }
}, {
  date: '2018-06-02T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 9250
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 16072
  },
  Cap: {
    label: 'Cap',
    value: 47821
  },
  Tshirt: {
    label: 'Tshirt',
    value: 18784
  },
  Jeans: {
    label: 'Jeans',
    value: 29645
  },
  Headphones: {
    label: 'Headphones',
    value: 24500
  },
  Lamp: {
    label: 'Lamp',
    value: 18599
  },
  Socks: {
    label: 'Socks',
    value: 23572
  },
  Mug: {
    label: 'Mug',
    value: 20991
  },
  Case: {
    label: 'Case',
    value: 16537
  }
}];
/* harmony default export */ const chart_story = ({
  title: 'WooCommerce Admin/components/Chart',
  component: src_chart,
  args: {
    legendPosition: undefined
  },
  argTypes: {
    legendPosition: {
      control: {
        type: 'select'
      },
      options: [undefined, 'bottom', 'side', 'top', 'hidden']
    }
  }
});
var Default = function Default(_ref) {
  var legendPosition = _ref.legendPosition;
  return (0,react.createElement)(src_chart, {
    data: data,
    legendPosition: legendPosition
  });
};
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "({\n  legendPosition\n}) => <Chart data={data} legendPosition={legendPosition} />",
      ...Default.parameters?.docs?.source
    }
  }
};

/***/ })

}]);