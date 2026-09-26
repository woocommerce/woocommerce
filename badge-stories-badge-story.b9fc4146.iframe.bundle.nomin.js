"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6698],{

/***/ "../../packages/js/components/src/badge/stories/badge.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Primary: () => (/* binding */ Primary),
  "default": () => (/* binding */ badge_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js + 6 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/badge/index.tsx

/**
 * External dependencies
 */

const badge_Badge = ({
  count,
  className = '',
  ...props
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
    className: `woocommerce-badge ${className}`,
    ...props,
    children: count
  });
};
try {
    // @ts-ignore
    badge_Badge.displayName = "Badge";
    // @ts-ignore
    badge_Badge.__docgenInfo = { "description": "", "displayName": "Badge", "props": { "count": { "defaultValue": null, "description": "", "name": "count", "required": true, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/badge/index.tsx#Badge"] = { docgenInfo: badge_Badge.__docgenInfo, name: "Badge", path: "../../packages/js/components/src/badge/index.tsx#Badge" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/badge/stories/badge.story.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const Template = args => /*#__PURE__*/(0,jsx_runtime.jsx)(component/* default */.A, {
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(card_body_component/* default */.A, {
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(badge_Badge, {
      ...args
    })
  })
});
const Primary = Template.bind({});
Primary.args = {
  count: 15
};
/* harmony default export */ const badge_story = ({
  title: 'Components/Badge',
  component: badge_Badge
});
Primary.parameters = {
  ...Primary.parameters,
  docs: {
    ...Primary.parameters?.docs,
    source: {
      originalSource: "args => <Card>\n        <CardBody>\n            <Badge {...args} />\n        </CardBody>\n    </Card>",
      ...Primary.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Badge.displayName = "Badge";
    // @ts-ignore
    Badge.__docgenInfo = { "description": "", "displayName": "Badge", "props": { "count": { "defaultValue": null, "description": "", "name": "count", "required": true, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/badge/stories/badge.story.tsx#Badge"] = { docgenInfo: Badge.__docgenInfo, name: "Badge", path: "../../packages/js/components/src/badge/stories/badge.story.tsx#Badge" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);