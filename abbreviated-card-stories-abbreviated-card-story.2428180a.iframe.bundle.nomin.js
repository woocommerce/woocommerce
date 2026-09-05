"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2780],{

/***/ "../../packages/js/components/src/abbreviated-card/stories/abbreviated-card.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ abbreviated_card_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/page.js
var page = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/page.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js + 6 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var build_module_icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/abbreviated-card/index.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


const AbbreviatedCard = ({
  children,
  className,
  href,
  icon,
  onClick,
  type
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
    className: (0,clsx/* default */.A)('woocommerce-abbreviated-card', className),
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(card_body_component/* default */.A, {
      size: "none",
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)(src_link/* default */.A, {
        href: href,
        onClick: onClick,
        type: type,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-abbreviated-card__icon",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_icon/* default */.A, {
            icon: icon
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-abbreviated-card__content",
          children: children
        })]
      })
    })
  });
};
/* harmony default export */ const abbreviated_card = (AbbreviatedCard);
;
AbbreviatedCard.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "AbbreviatedCard",
  "props": {
    "children": {
      "description": "The Abbreviated Card content.",
      "type": {
        "name": "node"
      },
      "required": true
    },
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "href": {
      "description": "The resource to link to.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "icon": {
      "description": "Icon for the Abbreviated Card.",
      "type": {
        "name": "element"
      },
      "required": true
    },
    "onClick": {
      "description": "Called when the card is clicked.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "type": {
      "description": "Type of link.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'wp-admin'",
          "computed": false
        }, {
          "value": "'wc-admin'",
          "computed": false
        }, {
          "value": "'external'",
          "computed": false
        }]
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/abbreviated-card/stories/abbreviated-card.story.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsxs)(abbreviated_card, {
  icon: page/* default */.A,
  href: "#",
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)("h3", {
    children: "Title"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
    children: "Abbreviated card content"
  })]
});
/* harmony default export */ const abbreviated_card_story = ({
  title: 'Components/AbbreviatedCard',
  component: abbreviated_card
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <AbbreviatedCard icon={page} href=\"#\">\n        <h3>Title</h3>\n        <p>Abbreviated card content</p>\n    </AbbreviatedCard>",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
const Link = ({
  href,
  children,
  type = 'wc-admin',
  ...props
}) => {
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  const wcAdminLinkHandler = (onClick, event) => {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
      return;
    }
    event?.preventDefault();

    // If there is an onclick event, execute it.
    const onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if (event?.target instanceof Element) {
      const closestEventTarget = event.target.closest('a')?.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  const passProps = {
    ...props,
    'data-link-type': type
  };
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
    href: href,
    ...passProps,
    children: children
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Link);
try {
    // @ts-ignore
    Link.displayName = "Link";
    // @ts-ignore
    Link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "Link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#Link"] = { docgenInfo: Link.__docgenInfo, name: "Link", path: "../../packages/js/components/src/link/index.tsx#Link" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    link.displayName = "link";
    // @ts-ignore
    link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#link"] = { docgenInfo: link.__docgenInfo, name: "link", path: "../../packages/js/components/src/link/index.tsx#link" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);