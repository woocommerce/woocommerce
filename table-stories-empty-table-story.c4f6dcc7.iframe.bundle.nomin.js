"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1750],{

/***/ "../../packages/js/components/src/table/stories/empty-table.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ empty_table_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/table/empty.tsx

/**
 * External dependencies
 */


/**
 * `EmptyTable` displays a blank space with an optional message passed as a children node
 * with the purpose of replacing a table with no rows.
 * It mimics the same height a table would have according to the `numberOfRows` prop.
 */
var EmptyTable = function EmptyTable(_ref) {
  var children = _ref.children,
    _ref$numberOfRows = _ref.numberOfRows,
    numberOfRows = _ref$numberOfRows === void 0 ? 5 : _ref$numberOfRows;
  return (0,react.createElement)("div", {
    className: "woocommerce-table is-empty",
    style: {
      '--number-of-rows': numberOfRows
    }
  }, children);
};
/* harmony default export */ const table_empty = (EmptyTable);
try {
    // @ts-ignore
    empty.displayName = "empty";
    // @ts-ignore
    empty.__docgenInfo = { "description": "`EmptyTable` displays a blank space with an optional message passed as a children node\nwith the purpose of replacing a table with no rows.\nIt mimics the same height a table would have according to the `numberOfRows` prop.", "displayName": "empty", "props": { "numberOfRows": { "defaultValue": { value: "5" }, "description": "An integer with the number of rows the box should occupy.", "name": "numberOfRows", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/empty.tsx#empty"] = { docgenInfo: empty.__docgenInfo, name: "empty", path: "../../packages/js/components/src/table/empty.tsx#empty" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/table/stories/empty-table.story.tsx

/**
 * External dependencies
 */

var Basic = function Basic() {
  return (0,react.createElement)(table_empty, null, "There are no entries.");
};
/* harmony default export */ const empty_table_story = ({
  title: 'WooCommerce Admin/components/EmptyTable',
  component: table_empty
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <EmptyTable>There are no entries.</EmptyTable>",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ })

}]);