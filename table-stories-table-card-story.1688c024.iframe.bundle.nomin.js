"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6933],{

/***/ "../../packages/js/components/src/table/stories/table-card.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Actions: () => (/* binding */ Actions),
  Basic: () => (/* binding */ Basic),
  TablePreface: () => (/* binding */ TablePreface),
  WideTable: () => (/* binding */ WideTable),
  "default": () => (/* binding */ table_card_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js + 6 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js
var text_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../packages/js/components/src/ellipsis-menu/index.tsx
var ellipsis_menu = __webpack_require__("../../packages/js/components/src/ellipsis-menu/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/ellipsis-menu/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/ellipsis-menu/menu-item.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/ellipsis-menu/menu-title.tsx
var menu_title = __webpack_require__("../../packages/js/components/src/ellipsis-menu/menu-title.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/pagination/pagination.tsx + 2 modules
var pagination = __webpack_require__("../../packages/js/components/src/pagination/pagination.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/table/table.tsx
var table_table = __webpack_require__("../../packages/js/components/src/table/table.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/table/placeholder.tsx
var placeholder = __webpack_require__("../../packages/js/components/src/table/placeholder.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/table/summary.tsx
var table_summary = __webpack_require__("../../packages/js/components/src/table/summary.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/table/index.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */








const defaultOnQueryChange = () => () => {};
const defaultOnColumnsChange = () => {};
/**
 * This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
 * It accepts `headers` for column headers, and `rows` for the table content.
 * `rowHeader` can be used to define the index of the row header (or false if no header).
 *
 * `TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 * This includes filtering and comparison functionality for report pages.
 */
const TableCard = ({
  actions,
  className,
  hasSearch,
  tablePreface,
  headers = [],
  ids,
  isLoading = false,
  onQueryChange = defaultOnQueryChange,
  onColumnsChange = defaultOnColumnsChange,
  onSort,
  query = {},
  rowHeader = 0,
  rows = [],
  rowsPerPage,
  showMenu = true,
  summary,
  title,
  totalRows,
  rowKey,
  emptyMessage = undefined,
  ...props
}) => {
  // eslint-disable-next-line no-console
  const getShowCols = (_headers = []) => {
    return _headers.map(({
      key,
      visible
    }) => {
      if (typeof visible === 'undefined' || visible) {
        return key;
      }
      return false;
    }).filter(Boolean);
  };
  const [showCols, setShowCols] = (0,react.useState)(getShowCols(headers));
  const onColumnToggle = key => {
    return () => {
      const hasKey = showCols.includes(key);
      if (hasKey) {
        // Handle hiding a sorted column
        if (query.orderby === key) {
          const defaultSort = (0,lodash.find)(headers, {
            defaultSort: true
          }) || (0,lodash.first)(headers) || {
            key: undefined
          };
          onQueryChange('sort')(defaultSort.key, 'desc');
        }
        const newShowCols = (0,lodash.without)(showCols, key);
        onColumnsChange(newShowCols, key);
        setShowCols(newShowCols);
      } else {
        const newShowCols = [...showCols, key];
        onColumnsChange(newShowCols, key);
        setShowCols(newShowCols);
      }
    };
  };
  const onPageChange = (newPage, direction) => {
    if (props.onPageChange) {
      props.onPageChange(newPage, direction);
    }
    if (onQueryChange) {
      onQueryChange('paged')(newPage.toString(), direction);
    }
  };
  const allHeaders = headers;
  const visibleHeaders = headers.filter(({
    key
  }) => showCols.includes(key));
  const visibleRows = rows.map(row => {
    return headers.map(({
      key
    }, i) => {
      return showCols.includes(key) && row[i];
    }).filter(Boolean);
  });
  const classes = (0,clsx/* default */.A)('woocommerce-table', className, {
    'has-actions': !!actions,
    'has-menu': showMenu,
    'has-search': hasSearch
  });
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
    className: classes,
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(card_header_component/* default */.A, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_component/* default */.A, {
        size: 16,
        weight: 600,
        as: "h2",
        color: "#23282d",
        children: title
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-table__actions",
        children: actions
      }), showMenu && /*#__PURE__*/(0,jsx_runtime.jsx)(ellipsis_menu/* default */.A, {
        label: (0,build_module.__)('Choose which values to display', 'woocommerce'),
        placement: "bottom-end",
        renderContent: () => /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)(menu_title/* default */.A, {
            children: (0,build_module.__)('Columns:', 'woocommerce')
          }), allHeaders.map(({
            key,
            label,
            required
          }) => {
            if (required) {
              return null;
            }
            return /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* default */.A, {
              checked: showCols.includes(key),
              isCheckbox: true,
              isClickable: true,
              onInvoke: key !== undefined ? onColumnToggle(key) : undefined,
              children: label
            }, key);
          })]
        })
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(card_body_component/* default */.A, {
      size: "none",
      children: [tablePreface && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-table__preface",
        children: tablePreface
      }), isLoading ? /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "screen-reader-text",
          children: (0,build_module.__)('Your requested data is loading', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(placeholder/* default */.A, {
          numberOfRows: rowsPerPage,
          headers: visibleHeaders,
          rowHeader: rowHeader,
          caption: title,
          query: query
        })]
      }) : /*#__PURE__*/(0,jsx_runtime.jsx)(table_table/* default */.A, {
        rows: visibleRows,
        headers: visibleHeaders,
        rowHeader: rowHeader,
        caption: title,
        query: query,
        onSort: onSort || onQueryChange('sort'),
        rowKey: rowKey,
        emptyMessage: emptyMessage
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(card_footer_component/* default */.A, {
      justify: "center",
      children: isLoading ? /*#__PURE__*/(0,jsx_runtime.jsx)(table_summary/* TableSummaryPlaceholder */.W, {}) : /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(pagination/* Pagination */.d, {
          page: parseInt(query.paged, 10) || 1,
          perPage: rowsPerPage,
          total: totalRows,
          onPageChange: onPageChange,
          onPerPageChange: perPage => onQueryChange('per_page')(perPage.toString())
        }, parseInt(query.paged, 10) || 1), summary && /*#__PURE__*/(0,jsx_runtime.jsx)(table_summary/* default */.A, {
          data: summary
        })]
      })
    })]
  });
};
/* harmony default export */ const src_table = (TableCard);
try {
    // @ts-ignore
    table.displayName = "table";
    // @ts-ignore
    table.__docgenInfo = { "description": "This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).\nIt accepts `headers` for column headers, and `rows` for the table content.\n`rowHeader` can be used to define the index of the row header (or false if no header).\n\n`TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.\nThis includes filtering and comparison functionality for report pages.", "displayName": "table", "props": { "rowKey": { "defaultValue": null, "description": "The rowKey used for the key value on each row, a function that returns the key.\nDefaults to index.", "name": "rowKey", "required": false, "type": { "name": "((row: TableRow[], index: number) => number)" } }, "emptyMessage": { "defaultValue": { value: "undefined" }, "description": "Customize the message to show when there are no rows in the table.", "name": "emptyMessage", "required": false, "type": { "name": "string" } }, "query": { "defaultValue": { value: "{}" }, "description": "The query string represented in object form", "name": "query", "required": false, "type": { "name": "QueryProps" } }, "rowHeader": { "defaultValue": { value: "0" }, "description": "Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col\nis checkboxes, for example). Set to false to disable row headers.", "name": "rowHeader", "required": false, "type": { "name": "number | false" } }, "headers": { "defaultValue": { value: "[]" }, "description": "An array of column headers (see `Table` props).", "name": "headers", "required": false, "type": { "name": "TableHeader[]" } }, "rows": { "defaultValue": { value: "[]" }, "description": "An array of arrays of display/value object pairs (see `Table` props).", "name": "rows", "required": false, "type": { "name": "TableRow[][]" } }, "className": { "defaultValue": null, "description": "Additional CSS classes.", "name": "className", "required": false, "type": { "name": "string" } }, "onSort": { "defaultValue": null, "description": "A function called when sortable table headers are clicked, gets the `header.key` as argument.", "name": "onSort", "required": false, "type": { "name": "((key: string, direction: string) => void)" } }, "actions": { "defaultValue": null, "description": "An array of custom React nodes that is placed at the top right corner.", "name": "actions", "required": false, "type": { "name": "ReactNode[]" } }, "hasSearch": { "defaultValue": null, "description": "If a search is provided in actions and should reorder actions on mobile.", "name": "hasSearch", "required": false, "type": { "name": "boolean" } }, "tablePreface": { "defaultValue": null, "description": "Content to be displayed before the table but after the header.", "name": "tablePreface", "required": false, "type": { "name": "ReactNode" } }, "ids": { "defaultValue": null, "description": "A list of IDs, matching to the row list so that ids[ 0 ] contains the object ID for the object displayed in row[ 0 ].", "name": "ids", "required": false, "type": { "name": "number[]" } }, "isLoading": { "defaultValue": { value: "false" }, "description": "Defines if the table contents are loading.\nIt will display `TablePlaceholder` component instead of `Table` if that's the case.", "name": "isLoading", "required": false, "type": { "name": "boolean" } }, "onQueryChange": { "defaultValue": { value: "() => () => {}" }, "description": "A function which returns a callback function to update the query string for a given `param`.", "name": "onQueryChange", "required": false, "type": { "name": "((param: string) => (...props: any) => void)" } }, "onColumnsChange": { "defaultValue": { value: "() => {}" }, "description": "A function which returns a callback function which is called upon the user changing the visibility of columns.", "name": "onColumnsChange", "required": false, "type": { "name": "((showCols: string[], key?: string) => void)" } }, "onPageChange": { "defaultValue": null, "description": "A callback function that is invoked when the current page is changed.", "name": "onPageChange", "required": false, "type": { "name": "((newPage: number, direction?: \"next\" | \"previous\" | \"goto\") => void)" } }, "rowsPerPage": { "defaultValue": null, "description": "The total number of rows to display per page.", "name": "rowsPerPage", "required": true, "type": { "name": "number" } }, "showMenu": { "defaultValue": { value: "true" }, "description": "Boolean to determine whether or not ellipsis menu is shown.", "name": "showMenu", "required": false, "type": { "name": "boolean" } }, "summary": { "defaultValue": null, "description": "An array of objects with `label` & `value` properties, which display in a line under the table.\nOptional, can be left off to show no summary.", "name": "summary", "required": false, "type": { "name": "{ label: string; value: ReactNode; }[]" } }, "title": { "defaultValue": null, "description": "The title used in the card header, also used as the caption for the content in this table.", "name": "title", "required": true, "type": { "name": "string" } }, "totalRows": { "defaultValue": null, "description": "The total number of rows (across all pages).", "name": "totalRows", "required": true, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/index.tsx#table"] = { docgenInfo: table.__docgenInfo, name: "table", path: "../../packages/js/components/src/table/index.tsx#table" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js + 4 modules
var notice = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/table/stories/index.ts
var stories = __webpack_require__("../../packages/js/components/src/table/stories/index.ts");
;// ../../packages/js/components/src/table/stories/table-card.story.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


// Create headers with many columns to trigger horizontal scrolling

const wideHeaders = [{
  key: 'month',
  label: 'Month'
}, {
  key: 'orders',
  label: 'Orders'
}, {
  key: 'revenue',
  label: 'Revenue'
}, {
  key: 'profit',
  label: 'Profit'
}, {
  key: 'taxes',
  label: 'Taxes'
}, {
  key: 'shipping',
  label: 'Shipping'
}, {
  key: 'discounts',
  label: 'Discounts'
}, {
  key: 'refunds',
  label: 'Refunds'
}, {
  key: 'fees',
  label: 'Fees'
}, {
  key: 'net',
  label: 'Net Revenue'
}];

// Create rows with many columns
const wideRows = [[{
  display: 'January',
  value: 1
}, {
  display: 10,
  value: 10
}, {
  display: '$530.00',
  value: 530
}, {
  display: '$450.00',
  value: 450
}, {
  display: '$80.00',
  value: 80
}, {
  display: '$25.00',
  value: 25
}, {
  display: '$15.00',
  value: 15
}, {
  display: '$0.00',
  value: 0
}, {
  display: '$5.00',
  value: 5
}, {
  display: '$405.00',
  value: 405
}], [{
  display: 'February',
  value: 2
}, {
  display: 13,
  value: 13
}, {
  display: '$675.00',
  value: 675
}, {
  display: '$580.00',
  value: 580
}, {
  display: '$95.00',
  value: 95
}, {
  display: '$30.00',
  value: 30
}, {
  display: '$20.00',
  value: 20
}, {
  display: '$0.00',
  value: 0
}, {
  display: '$8.00',
  value: 8
}, {
  display: '$517.00',
  value: 517
}], [{
  display: 'March',
  value: 3
}, {
  display: 9,
  value: 9
}, {
  display: '$460.00',
  value: 460
}, {
  display: '$390.00',
  value: 390
}, {
  display: '$70.00',
  value: 70
}, {
  display: '$22.00',
  value: 22
}, {
  display: '$18.00',
  value: 18
}, {
  display: '$0.00',
  value: 0
}, {
  display: '$6.00',
  value: 6
}, {
  display: '$344.00',
  value: 344
}]];
const TableCardExample = () => {
  const [{
    query
  }, setState] = (0,react.useState)({
    query: {
      paged: 1
    }
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(src_table, {
    title: "Revenue last week",
    rows: stories/* rows */.Ge,
    headers: stories/* headers */.b3,
    onQueryChange: param => value => setState({
      // @ts-expect-error: ignore for storybook
      query: {
        [param]: value
      }
    }),
    query: query,
    rowsPerPage: 7,
    totalRows: 10,
    summary: stories/* summary */.z
  });
};
const TableCardWithActionsExample = () => {
  const [{
    query
  }, setState] = (0,react.useState)({
    query: {
      paged: 1
    }
  });
  const [action1Text, setAction1Text] = (0,react.useState)('Action 1');
  const [action2Text, setAction2Text] = (0,react.useState)('Action 2');
  return /*#__PURE__*/(0,jsx_runtime.jsx)(src_table, {
    actions: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      onClick: () => {
        setAction1Text('Action 1 Clicked');
      },
      children: action1Text
    }, 0), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      onClick: () => {
        setAction2Text('Action 2 Clicked');
      },
      children: action2Text
    }, 0)],
    title: "Revenue last week",
    rows: stories/* rows */.Ge,
    headers: stories/* headers */.b3,
    onQueryChange: param => value => setState({
      // @ts-expect-error: ignore for storybook
      query: {
        [param]: value
      }
    }),
    query: query,
    rowsPerPage: 7,
    totalRows: 10,
    summary: stories/* summary */.z
  });
};
const TableCardWithTablePrefaceExample = () => {
  const [{
    query
  }, setState] = (0,react.useState)({
    query: {
      paged: 1
    }
  });
  const [showNotice, setShowNotice] = (0,react.useState)(true);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(src_table, {
    title: "Revenue last week",
    rows: stories/* rows */.Ge,
    headers: stories/* headers */.b3,
    tablePreface: showNotice && /*#__PURE__*/(0,jsx_runtime.jsx)(notice/* default */.A, {
      status: "info",
      isDismissible: true,
      onRemove: () => setShowNotice(false),
      children: "This is an important notice about the table"
    }),
    onQueryChange: param => value => setState({
      // @ts-expect-error: ignore for storybook
      query: {
        [param]: value
      }
    }),
    query: query,
    rowsPerPage: 7,
    totalRows: 10,
    summary: stories/* summary */.z
  });
};
const TableCardWideExample = () => {
  const [{
    query
  }, setState] = (0,react.useState)({
    query: {
      paged: 1
    }
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(src_table, {
    title: "Revenue with many columns (test horizontal scroll)",
    rows: wideRows,
    headers: wideHeaders,
    onQueryChange: param => value => setState({
      // @ts-expect-error: ignore for storybook
      query: {
        [param]: value
      }
    }),
    query: query,
    rowsPerPage: 7,
    totalRows: 10,
    summary: stories/* summary */.z
  });
};
const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(TableCardExample, {});
const Actions = () => /*#__PURE__*/(0,jsx_runtime.jsx)(TableCardWithActionsExample, {});
const TablePreface = () => /*#__PURE__*/(0,jsx_runtime.jsx)(TableCardWithTablePrefaceExample, {});
const WideTable = () => /*#__PURE__*/(0,jsx_runtime.jsx)(TableCardWideExample, {});
/* harmony default export */ const table_card_story = ({
  title: 'Components/TableCard',
  component: src_table
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <TableCardExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};
Actions.parameters = {
  ...Actions.parameters,
  docs: {
    ...Actions.parameters?.docs,
    source: {
      originalSource: "() => <TableCardWithActionsExample />",
      ...Actions.parameters?.docs?.source
    }
  }
};
TablePreface.parameters = {
  ...TablePreface.parameters,
  docs: {
    ...TablePreface.parameters?.docs,
    source: {
      originalSource: "() => <TableCardWithTablePrefaceExample />",
      ...TablePreface.parameters?.docs?.source
    }
  }
};
WideTable.parameters = {
  ...WideTable.parameters,
  docs: {
    ...WideTable.parameters?.docs,
    source: {
      originalSource: "() => <TableCardWideExample />",
      ...WideTable.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/ellipsis-menu/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var gridicons_dist_ellipsis__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/ellipsis.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */






/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */

const EllipsisMenu = ({
  label,
  renderContent,
  className,
  onToggle,
  // if set bottom-start, it will fallback to bottom-end / top-end / top-start
  // if it's bottom, it will fallback to only top
  placement = 'bottom-start',
  focusOnMount = 'firstElement'
}) => {
  if (!renderContent) {
    return null;
  }
  const renderEllipsis = ({
    onToggle: toggleHandlerOverride,
    isOpen
  }) => {
    const toggleClassname = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-ellipsis-menu__toggle', {
      'is-opened': isOpen
    });
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
      className: toggleClassname,
      onClick: e => {
        if (onToggle) {
          onToggle(e);
        }
        if (toggleHandlerOverride) {
          toggleHandlerOverride();
        }
      },
      title: label,
      "aria-expanded": isOpen,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
        icon: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(gridicons_dist_ellipsis__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A, {})
      })
    });
  };
  const handleMenuKeyDown = event => {
    // Prevent page scroll when navigating menu with arrow keys.
    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
      event.preventDefault();
    }
  };
  const renderMenu = renderContentArgs => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Ay, {
    className: "woocommerce-ellipsis-menu__content",
    onKeyDown: handleMenuKeyDown,
    children: renderContent(renderContentArgs)
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, 'woocommerce-ellipsis-menu'),
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
      contentClassName: "woocommerce-ellipsis-menu__popover",
      popoverProps: {
        placement,
        focusOnMount
      },
      renderToggle: renderEllipsis,
      renderContent: renderMenu
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EllipsisMenu);
try {
    // @ts-ignore
    ellipsismenu.displayName = "ellipsismenu";
    // @ts-ignore
    ellipsismenu.__docgenInfo = { "description": "This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.", "displayName": "ellipsismenu", "props": { "label": { "defaultValue": null, "description": "The label shown when hovering/focusing on the icon button.", "name": "label", "required": true, "type": { "name": "string" } }, "renderContent": { "defaultValue": null, "description": "A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments.", "name": "renderContent", "required": false, "type": { "name": "((props: CallbackProps) => Element | ReactNode)" } }, "className": { "defaultValue": null, "description": "Classname to add to ellipsis menu.", "name": "className", "required": false, "type": { "name": "string" } }, "onToggle": { "defaultValue": null, "description": "Callback function when dropdown button is clicked, it provides the click event.", "name": "onToggle", "required": false, "type": { "name": "((e: MouseEvent<Element, MouseEvent> | KeyboardEvent<Element>) => void)" } }, "placement": { "defaultValue": { value: "bottom-start" }, "description": "Placement of the dropdown menu. Default is 'bottom-start'.", "name": "placement", "required": false, "type": { "name": "any" } }, "focusOnMount": { "defaultValue": { value: "firstElement" }, "description": "By default, the first menu item will receive focus. This is the same as setting this prop to \"firstElement\".\nSpecifying a true value will focus the container instead.\nSpecifying a false value disables the focus handling entirely\n(this should only be done when an appropriately accessible\nsubstitute behavior exists).", "name": "focusOnMount", "required": false, "type": { "name": "boolean | \"firstElement\"" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/ellipsis-menu/index.tsx#ellipsismenu"] = { docgenInfo: ellipsismenu.__docgenInfo, name: "ellipsismenu", path: "../../packages/js/components/src/ellipsis-menu/index.tsx#ellipsismenu" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/ellipsis-menu/menu-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/form-toggle/index.js");
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const MenuItem = ({
  checked,
  children,
  isCheckbox = false,
  isClickable = false,
  onInvoke = () => {}
}) => {
  const container = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const onClick = event => {
    if (isClickable) {
      event.preventDefault();
      onInvoke();
    }
  };
  const onKeyDown = event => {
    const eventTarget = event.target;
    if (eventTarget.isSameNode(event.currentTarget)) {
      if (event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_2__/* .ENTER */ .Fm || event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_2__/* .SPACE */ .t6) {
        event.preventDefault();
        onInvoke();
      }
      if (event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_2__.UP) {
        event.preventDefault();
      }
      if (event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_2__/* .DOWN */ .PX) {
        event.preventDefault();
        const nextElementToFocus = eventTarget.nextSibling || eventTarget.parentNode?.querySelector('.woocommerce-ellipsis-menu__item');
        nextElementToFocus.focus();
      }
    }
  };
  if (isCheckbox) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
      "aria-checked": checked,
      ref: container,
      role: "menuitemcheckbox",
      tabIndex: 0,
      onKeyDown: onKeyDown,
      onClick: onClick,
      className: "woocommerce-ellipsis-menu__item",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
        className: "components-toggle-control",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .Ay, {
          "aria-hidden": "true",
          checked: checked,
          onChange: onInvoke,
          onClick: e => e.stopPropagation(),
          tabIndex: -1
        }), children]
      })
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
    role: "menuitem",
    tabIndex: 0,
    onKeyDown: onKeyDown,
    onClick: onClick,
    className: "woocommerce-ellipsis-menu__item",
    children: children
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MenuItem);
try {
    // @ts-ignore
    menuitem.displayName = "menuitem";
    // @ts-ignore
    menuitem.__docgenInfo = { "description": "", "displayName": "menuitem", "props": { "checked": { "defaultValue": null, "description": "Whether the menu item is checked or not. Only relevant for menu items with `isCheckbox`.", "name": "checked", "required": false, "type": { "name": "boolean" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "isCheckbox": { "defaultValue": { value: "false" }, "description": "Whether the menu item is a checkbox (will render a FormToggle and use the `menuitemcheckbox` role).", "name": "isCheckbox", "required": false, "type": { "name": "boolean" } }, "isClickable": { "defaultValue": { value: "false" }, "description": "Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component\nhandles the click event.", "name": "isClickable", "required": false, "type": { "name": "boolean" } }, "onInvoke": { "defaultValue": { value: "() => {}" }, "description": "A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked\n(only if `isClickable` is set).", "name": "onInvoke", "required": false, "type": { "name": "(() => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/ellipsis-menu/menu-item.tsx#menuitem"] = { docgenInfo: menuitem.__docgenInfo, name: "menuitem", path: "../../packages/js/components/src/ellipsis-menu/menu-item.tsx#menuitem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/ellipsis-menu/menu-title.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");

/**
 * External dependencies
 */

/**
 * `MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
 * (so this should not be used in place of the `EllipsisMenu` prop `label`).
 */

const MenuTitle = ({
  children
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: "woocommerce-ellipsis-menu__title",
    children: children
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MenuTitle);
try {
    // @ts-ignore
    menutitle.displayName = "menutitle";
    // @ts-ignore
    menutitle.__docgenInfo = { "description": "`MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated\n(so this should not be used in place of the `EllipsisMenu` prop `label`).", "displayName": "menutitle", "props": { "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed as the content of this item.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/ellipsis-menu/menu-title.tsx#menutitle"] = { docgenInfo: menutitle.__docgenInfo, name: "menutitle", path: "../../packages/js/components/src/ellipsis-menu/menu-title.tsx#menutitle" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/pagination/page-size-picker.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $: () => (/* binding */ PageSizePicker),
/* harmony export */   v: () => (/* binding */ DEFAULT_PER_PAGE_OPTIONS)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



const DEFAULT_PER_PAGE_OPTIONS = [25, 50, 75, 100];
function PageSizePicker({
  perPage,
  currentPage,
  total,
  setCurrentPage,
  setPerPageChange = () => {},
  perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
  label = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Rows per page', 'woocommerce')
}) {
  function perPageChange(newPerPage) {
    setPerPageChange(parseInt(newPerPage, 10));
    const newMaxPage = Math.ceil(total / parseInt(newPerPage, 10));
    if (currentPage > newMaxPage) {
      setCurrentPage(newMaxPage);
    }
  }

  // @todo Replace this with a styleized Select drop-down/control?
  const pickerOptions = perPageOptions.map(option => {
    return {
      value: option.toString(),
      label: option.toString()
    };
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
    className: "woocommerce-pagination__per-page-picker",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      __next40pxDefaultSize: true,
      label: label,
      labelPosition: "side",
      value: perPage.toString(),
      onChange: perPageChange,
      options: pickerOptions
    })
  });
}
try {
    // @ts-ignore
    PageSizePicker.displayName = "PageSizePicker";
    // @ts-ignore
    PageSizePicker.__docgenInfo = { "description": "", "displayName": "PageSizePicker", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "perPage": { "defaultValue": null, "description": "", "name": "perPage", "required": true, "type": { "name": "number" } }, "total": { "defaultValue": null, "description": "", "name": "total", "required": true, "type": { "name": "number" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"next\" | \"previous\" | \"goto\" | undefined) => void" } }, "setPerPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "setPerPageChange", "required": false, "type": { "name": "((perPage: number) => void)" } }, "perPageOptions": { "defaultValue": { value: "[ 25, 50, 75, 100 ]" }, "description": "", "name": "perPageOptions", "required": false, "type": { "name": "number[]" } }, "label": { "defaultValue": { value: "__( 'Rows per page', 'woocommerce' )" }, "description": "", "name": "label", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-size-picker.tsx#PageSizePicker"] = { docgenInfo: PageSizePicker.__docgenInfo, name: "PageSizePicker", path: "../../packages/js/components/src/pagination/page-size-picker.tsx#PageSizePicker" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/pagination/pagination.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  d: () => (/* binding */ Pagination)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js
var chevron_left = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/pagination/page-arrows.tsx
/**
 * External dependencies
 */





function PageArrows({
  pageCount,
  currentPage,
  showPageArrowsLabel = true,
  setCurrentPage
}) {
  function previousPage(event) {
    event.stopPropagation();
    if (currentPage - 1 < 1) {
      return;
    }
    setCurrentPage(currentPage - 1, 'previous');
  }
  function nextPage(event) {
    event.stopPropagation();
    if (currentPage + 1 > pageCount) {
      return;
    }
    setCurrentPage(currentPage + 1, 'next');
  }
  if (pageCount <= 1) {
    return null;
  }
  const previousLinkClass = (0,clsx/* default */.A)('woocommerce-pagination__link', {
    'is-active': currentPage > 1
  });
  const nextLinkClass = (0,clsx/* default */.A)('woocommerce-pagination__link', {
    'is-active': currentPage < pageCount
  });
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-pagination__page-arrows",
    children: [showPageArrowsLabel && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "woocommerce-pagination__page-arrows-label",
      role: "status",
      "aria-live": "polite",
      children: (0,build_module/* sprintf */.nv)(/* translators: 1: current page number, 2: total number of pages */
      (0,build_module.__)('Page %1$d of %2$d', 'woocommerce'), currentPage, pageCount)
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-pagination__page-arrows-buttons",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        className: previousLinkClass,
        disabled: !(currentPage > 1),
        onClick: previousPage,
        label: (0,build_module.__)('Previous Page', 'woocommerce'),
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: (0,build_module/* isRTL */.V8)() ? chevron_right/* default */.A : chevron_left/* default */.A
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        className: nextLinkClass,
        disabled: !(currentPage < pageCount),
        onClick: nextPage,
        label: (0,build_module.__)('Next Page', 'woocommerce'),
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: (0,build_module/* isRTL */.V8)() ? chevron_left/* default */.A : chevron_right/* default */.A
        })
      })]
    })]
  });
}
try {
    // @ts-ignore
    PageArrows.displayName = "PageArrows";
    // @ts-ignore
    PageArrows.__docgenInfo = { "description": "", "displayName": "PageArrows", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "pageCount": { "defaultValue": null, "description": "", "name": "pageCount", "required": true, "type": { "name": "number" } }, "showPageArrowsLabel": { "defaultValue": { value: "true" }, "description": "", "name": "showPageArrowsLabel", "required": false, "type": { "name": "boolean" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"next\" | \"previous\" | \"goto\" | undefined) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-arrows.tsx#PageArrows"] = { docgenInfo: PageArrows.__docgenInfo, name: "PageArrows", path: "../../packages/js/components/src/pagination/page-arrows.tsx#PageArrows" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
;// ../../packages/js/components/src/pagination/page-picker.tsx
/**
 * External dependencies
 */





function PagePicker({
  pageCount,
  currentPage,
  setCurrentPage
}) {
  const [inputValue, setInputValue] = (0,react.useState)(currentPage);
  function onInputChange(event) {
    setInputValue(parseInt(event.currentTarget.value, 10));
  }
  function onInputBlur(event) {
    const newPage = parseInt(event.target.value, 10);
    if (newPage !== currentPage && Number.isFinite(newPage) && newPage > 0 && pageCount && pageCount >= newPage) {
      setCurrentPage(newPage, 'goto');
    }
  }
  function selectInputValue(event) {
    event.currentTarget.select();
  }
  const isError = currentPage < 1 || currentPage > pageCount;
  const inputClass = (0,clsx/* default */.A)('woocommerce-pagination__page-picker-input', {
    'has-error': isError
  });
  const instanceId = (0,lodash.uniqueId)('woocommerce-pagination-page-picker-');
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "woocommerce-pagination__page-picker",
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)("label", {
      htmlFor: instanceId,
      className: "woocommerce-pagination__page-picker-label",
      children: [(0,build_module.__)('Go to page', 'woocommerce'), /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
        id: instanceId,
        className: inputClass,
        "aria-invalid": isError,
        type: "number",
        onClick: selectInputValue,
        onChange: onInputChange,
        onBlur: onInputBlur,
        value: inputValue,
        min: 1,
        max: pageCount
      })]
    })
  });
}
try {
    // @ts-ignore
    PagePicker.displayName = "PagePicker";
    // @ts-ignore
    PagePicker.__docgenInfo = { "description": "", "displayName": "PagePicker", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "pageCount": { "defaultValue": null, "description": "", "name": "pageCount", "required": true, "type": { "name": "number" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"next\" | \"previous\" | \"goto\" | undefined) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-picker.tsx#PagePicker"] = { docgenInfo: PagePicker.__docgenInfo, name: "PagePicker", path: "../../packages/js/components/src/pagination/page-picker.tsx#PagePicker" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pagination/page-size-picker.tsx
var page_size_picker = __webpack_require__("../../packages/js/components/src/pagination/page-size-picker.tsx");
;// ../../packages/js/components/src/pagination/pagination.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




function Pagination({
  page,
  onPageChange = () => {},
  total,
  perPage,
  onPerPageChange = () => {},
  showPagePicker = true,
  showPerPagePicker = true,
  showPageArrowsLabel = true,
  className,
  perPageOptions = page_size_picker/* DEFAULT_PER_PAGE_OPTIONS */.v,
  children
}) {
  const pageCount = Math.ceil(total / perPage);
  if (children && typeof children === 'function') {
    return children({
      pageCount
    });
  }
  const classes = (0,clsx/* default */.A)('woocommerce-pagination', className);
  if (pageCount <= 1) {
    return total > perPageOptions[0] && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: classes,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(page_size_picker/* PageSizePicker */.$, {
        currentPage: page,
        perPage: perPage,
        setCurrentPage: onPageChange,
        total: total,
        setPerPageChange: onPerPageChange,
        perPageOptions: perPageOptions
      })
    }) || null;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: classes,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(PageArrows, {
      currentPage: page,
      pageCount: pageCount,
      showPageArrowsLabel: showPageArrowsLabel,
      setCurrentPage: onPageChange
    }), showPagePicker && /*#__PURE__*/(0,jsx_runtime.jsx)(PagePicker, {
      currentPage: page,
      pageCount: pageCount,
      setCurrentPage: onPageChange
    }), showPerPagePicker && /*#__PURE__*/(0,jsx_runtime.jsx)(page_size_picker/* PageSizePicker */.$, {
      currentPage: page,
      perPage: perPage,
      setCurrentPage: onPageChange,
      total: total,
      setPerPageChange: onPerPageChange,
      perPageOptions: perPageOptions
    })]
  });
}
try {
    // @ts-ignore
    Pagination.displayName = "Pagination";
    // @ts-ignore
    Pagination.__docgenInfo = { "description": "", "displayName": "Pagination", "props": { "page": { "defaultValue": null, "description": "", "name": "page", "required": true, "type": { "name": "number" } }, "perPage": { "defaultValue": null, "description": "", "name": "perPage", "required": true, "type": { "name": "number" } }, "total": { "defaultValue": null, "description": "", "name": "total", "required": true, "type": { "name": "number" } }, "onPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onPageChange", "required": false, "type": { "name": "((page: number, action?: \"next\" | \"previous\" | \"goto\") => void)" } }, "onPerPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onPerPageChange", "required": false, "type": { "name": "((perPage: number) => void)" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "showPagePicker": { "defaultValue": { value: "true" }, "description": "", "name": "showPagePicker", "required": false, "type": { "name": "boolean" } }, "showPerPagePicker": { "defaultValue": { value: "true" }, "description": "", "name": "showPerPagePicker", "required": false, "type": { "name": "boolean" } }, "showPageArrowsLabel": { "defaultValue": { value: "true" }, "description": "", "name": "showPageArrowsLabel", "required": false, "type": { "name": "boolean" } }, "perPageOptions": { "defaultValue": { value: "[ 25, 50, 75, 100 ]" }, "description": "", "name": "perPageOptions", "required": false, "type": { "name": "number[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/pagination.tsx#Pagination"] = { docgenInfo: Pagination.__docgenInfo, name: "Pagination", path: "../../packages/js/components/src/pagination/pagination.tsx#Pagination" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/table/placeholder.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _table__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/table/table.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


/**
 * `TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.
 */
const TablePlaceholder = ({
  query,
  caption,
  headers,
  numberOfRows = 5,
  ...props
}) => {
  const rows = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.range)(numberOfRows).map(() => headers.map(() => ({
    display: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "is-placeholder"
    })
  })));
  const tableProps = {
    query,
    caption,
    headers,
    numberOfRows,
    ...props
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_table__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    ariaHidden: true,
    className: "is-loading",
    rows: rows,
    ...tableProps
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TablePlaceholder);
try {
    // @ts-ignore
    placeholder.displayName = "placeholder";
    // @ts-ignore
    placeholder.__docgenInfo = { "description": "`TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.", "displayName": "placeholder", "props": { "query": { "defaultValue": null, "description": "An object of the query parameters passed to the page", "name": "query", "required": false, "type": { "name": "QueryProps" } }, "caption": { "defaultValue": null, "description": "A label for the content in this table.", "name": "caption", "required": true, "type": { "name": "string" } }, "numberOfRows": { "defaultValue": { value: "5" }, "description": "An integer with the number of rows to display.", "name": "numberOfRows", "required": false, "type": { "name": "number" } }, "rowHeader": { "defaultValue": null, "description": "Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col\nis checkboxes, for example). Set to false to disable row headers.", "name": "rowHeader", "required": false, "type": { "name": "number | false" } }, "headers": { "defaultValue": null, "description": "An array of column headers (see `Table` props).", "name": "headers", "required": true, "type": { "name": "TableHeader[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/placeholder.tsx#placeholder"] = { docgenInfo: placeholder.__docgenInfo, name: "placeholder", path: "../../packages/js/components/src/table/placeholder.tsx#placeholder" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/table/stories/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ge: () => (/* binding */ rows),
/* harmony export */   b3: () => (/* binding */ headers),
/* harmony export */   z: () => (/* binding */ summary)
/* harmony export */ });
const headers = [{
  key: 'month',
  label: 'Month'
}, {
  key: 'orders',
  label: 'Orders'
}, {
  key: 'revenue',
  label: 'Revenue'
}];
const rows = [[{
  display: 'January',
  value: 1
}, {
  display: 10,
  value: 10
}, {
  display: '$530.00',
  value: 530
}], [{
  display: 'February',
  value: 2
}, {
  display: 13,
  value: 13
}, {
  display: '$675.00',
  value: 675
}], [{
  display: 'March',
  value: 3
}, {
  display: 9,
  value: 9
}, {
  display: '$460.00',
  value: 460
}]];
const summary = [{
  label: 'Gross Income',
  value: '$830.00'
}, {
  label: 'Taxes',
  value: '$96.32'
}, {
  label: 'Shipping',
  value: '$50.00'
}];

/***/ }),

/***/ "../../packages/js/components/src/table/summary.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   W: () => (/* binding */ TableSummaryPlaceholder)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * A component to display summarized table data - the list of data passed in on a single line.
 */
const TableSummary = ({
  data
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("ul", {
    className: "woocommerce-table__summary",
    role: "complementary",
    children: data.map(({
      label,
      value
    }, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("li", {
      className: "woocommerce-table__summary-item",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        className: "woocommerce-table__summary-value",
        children: value
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        className: "woocommerce-table__summary-label",
        children: label
      })]
    }, i))
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TableSummary);

/**
 * A component to display a placeholder box for `TableSummary`. There is no prop for this component.
 *
 * @return {Object} -
 */
const TableSummaryPlaceholder = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("ul", {
    className: "woocommerce-table__summary is-loading",
    role: "complementary",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("li", {
      className: "woocommerce-table__summary-item",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        className: "is-placeholder"
      })
    })
  });
};
try {
    // @ts-ignore
    summary.displayName = "summary";
    // @ts-ignore
    summary.__docgenInfo = { "description": "A component to display summarized table data - the list of data passed in on a single line.", "displayName": "summary", "props": { "data": { "defaultValue": null, "description": "", "name": "data", "required": true, "type": { "name": "{ label: string; value: ReactNode; }[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/summary.tsx#summary"] = { docgenInfo: summary.__docgenInfo, name: "summary", path: "../../packages/js/components/src/table/summary.tsx#summary" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    TableSummaryPlaceholder.displayName = "TableSummaryPlaceholder";
    // @ts-ignore
    TableSummaryPlaceholder.__docgenInfo = { "description": "A component to display a placeholder box for `TableSummary`. There is no prop for this component.", "displayName": "TableSummaryPlaceholder", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/summary.tsx#TableSummaryPlaceholder"] = { docgenInfo: TableSummaryPlaceholder.__docgenInfo, name: "TableSummaryPlaceholder", path: "../../packages/js/components/src/table/summary.tsx#TableSummaryPlaceholder" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/table/table.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */









/**
 * Internal dependencies
 */

const ASC = 'asc';
const DESC = 'desc';
const getDisplay = cell => cell.display || null;

/**
 * A table component, without the Card wrapper. This is a basic table display, sortable, but no default filtering.
 *
 * Row data should be passed to the component as a list of arrays, where each array is a row in the table.
 * Headers are passed in separately as an array of objects with column-related properties. For example,
 * this data would render the following table.
 *
 * ```js
 * const headers = [ { label: 'Month' }, { label: 'Orders' }, { label: 'Revenue' } ];
 * const rows = [
 * 	[
 * 		{ display: 'January', value: 1 },
 * 		{ display: 10, value: 10 },
 * 		{ display: '$530.00', value: 530 },
 * 	],
 * 	[
 * 		{ display: 'February', value: 2 },
 * 		{ display: 13, value: 13 },
 * 		{ display: '$675.00', value: 675 },
 * 	],
 * 	[
 * 		{ display: 'March', value: 3 },
 * 		{ display: 9, value: 9 },
 * 		{ display: '$460.00', value: 460 },
 * 	],
 * ]
 * ```
 *
 * |   Month  | Orders | Revenue |
 * | ---------|--------|---------|
 * | January  |     10 | $530.00 |
 * | February |     13 | $675.00 |
 * | March    |      9 | $460.00 |
 */

const Table = ({
  instanceId,
  headers = [],
  rows = [],
  ariaHidden,
  caption,
  className,
  onSort = f => f,
  query = {},
  rowHeader,
  rowKey,
  emptyMessage,
  ...props
}) => {
  const {
    classNames
  } = props;
  const [tabIndex, setTabIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(undefined);
  const [isScrollableRight, setIsScrollableRight] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  const [isScrollableLeft, setIsScrollableLeft] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  const container = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useRef)(null);
  if (classNames) {
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(`Table component's classNames prop`, {
      since: '11.1.0',
      version: '12.0.0',
      alternative: 'className',
      plugin: '@woocommerce/components'
    });
  }
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)('woocommerce-table__table', classNames, className, {
    'is-scrollable-right': isScrollableRight,
    'is-scrollable-left': isScrollableLeft
  });
  const sortBy = key => {
    return () => {
      const currentKey = query.orderby || (0,lodash__WEBPACK_IMPORTED_MODULE_1__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(headers, {
        defaultSort: true
      }), 'key', false);
      const currentDir = query.order || (0,lodash__WEBPACK_IMPORTED_MODULE_1__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(headers, {
        key: currentKey
      }), 'defaultOrder', DESC);
      let dir = DESC;
      if (key === currentKey) {
        dir = DESC === currentDir ? ASC : DESC;
      }
      onSort(key, dir);
    };
  };
  const getRowKey = (row, index) => {
    if (rowKey && typeof rowKey === 'function') {
      return rowKey(row, index);
    }
    return index;
  };
  const updateTableShadow = () => {
    const table = container.current;
    if (!table) {
      return;
    }

    // Get current dimensions
    const scrollWidth = table.scrollWidth;
    const offsetWidth = table.offsetWidth;
    const scrollLeft = table.scrollLeft;

    // Check if the table is actually scrollable
    const isTableScrollable = scrollWidth > offsetWidth;

    // If table is not scrollable, remove all scroll indicators
    if (!isTableScrollable) {
      setIsScrollableRight(false);
      setIsScrollableLeft(false);
      // Reset scroll position when table is no longer scrollable
      if (scrollLeft !== 0) {
        table.scrollLeft = 0;
      }
      return;
    }

    // Calculate scroll states
    const scrolledToEnd = scrollWidth - scrollLeft <= offsetWidth;
    const scrolledToStart = scrollLeft === 0;

    // Update scroll indicators based on current state
    setIsScrollableRight(!scrolledToEnd);
    setIsScrollableLeft(!scrolledToStart);
  };
  const sortedBy = query.orderby || (0,lodash__WEBPACK_IMPORTED_MODULE_1__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(headers, {
    defaultSort: true
  }), 'key', false);
  const sortDir = query.order || (0,lodash__WEBPACK_IMPORTED_MODULE_1__.get)((0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(headers, {
    key: sortedBy
  }), 'defaultOrder', DESC);
  const hasData = !!rows.length;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    const scrollWidth = container.current?.scrollWidth;
    const clientWidth = container.current?.clientWidth;
    if (scrollWidth === undefined || clientWidth === undefined) {
      return;
    }
    const scrollable = scrollWidth > clientWidth;
    setTabIndex(scrollable ? 0 : undefined);
    updateTableShadow();
    const handleResize = () => {
      // Use requestAnimationFrame to ensure DOM has updated
      requestAnimationFrame(() => {
        updateTableShadow();
      });
    };
    window.addEventListener('resize', handleResize);
    return () => {
      window.removeEventListener('resize', handleResize);
    };
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(updateTableShadow, [headers, rows, emptyMessage]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
    className: classes,
    ref: container,
    tabIndex: tabIndex,
    "aria-hidden": ariaHidden,
    "aria-labelledby": `caption-${instanceId}`,
    role: "group",
    onScroll: updateTableShadow,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("table", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("caption", {
        id: `caption-${instanceId}`,
        className: "woocommerce-table__caption screen-reader-text",
        children: [caption, tabIndex === 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("small", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('(scroll to see more)', 'woocommerce')
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("tbody", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("tr", {
          children: headers.map((header, i) => {
            const {
              cellClassName,
              isLeftAligned,
              isSortable,
              isNumeric,
              key,
              label,
              screenReaderLabel
            } = header;
            const labelId = `header-${instanceId}-${i}`;
            const thProps = {
              className: (0,clsx__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)('woocommerce-table__header', cellClassName, {
                'is-left-aligned': isLeftAligned || !isNumeric,
                'is-sortable': isSortable,
                'is-sorted': sortedBy === key,
                'is-numeric': isNumeric
              })
            };
            if (isSortable) {
              thProps['aria-sort'] = 'none';
              if (sortedBy === key) {
                thProps['aria-sort'] = sortDir === ASC ? 'ascending' : 'descending';
              }
            }
            // We only sort by ascending if the col is already sorted descending
            const iconLabel = sortedBy === key && sortDir !== ASC ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %s: column label */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Sort by %s in ascending order', 'woocommerce'), screenReaderLabel ?? (typeof label === 'string' ? label : '')) : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %s: column label */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Sort by %s in descending order', 'woocommerce'), screenReaderLabel ?? (typeof label === 'string' ? label : ''));
            const textLabel = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                "aria-hidden": Boolean(screenReaderLabel),
                children: label
              }), screenReaderLabel && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                className: "screen-reader-text",
                children: screenReaderLabel
              })]
            });
            return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("th", {
              role: "columnheader",
              scope: "col",
              ...thProps,
              children: isSortable ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
                  "aria-describedby": labelId,
                  onClick: hasData ? sortBy(key) : lodash__WEBPACK_IMPORTED_MODULE_1__.noop,
                  children: [sortedBy === key && sortDir === ASC ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
                    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A
                  }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
                    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A
                  }), textLabel]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                  className: "screen-reader-text",
                  id: labelId,
                  children: iconLabel
                })]
              }) : textLabel
            }, header.key || i);
          })
        }), hasData ? rows.map((row, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("tr", {
          children: row.map((cell, j) => {
            const {
              cellClassName,
              isLeftAligned,
              isNumeric
            } = headers[j];
            const isHeader = rowHeader === j;
            const Cell = isHeader ? 'th' : 'td';
            const cellClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)('woocommerce-table__item', cellClassName, {
              'is-left-aligned': isLeftAligned || !isNumeric,
              'is-numeric': isNumeric,
              'is-sorted': sortedBy === headers[j].key
            });
            const cellKey = getRowKey(row, i).toString() + j;
            return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(Cell, {
              scope: isHeader ? 'row' : undefined,
              className: cellClasses,
              children: getDisplay(cell)
            }, cellKey);
          })
        }, getRowKey(row, i))) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("tr", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("td", {
            className: "woocommerce-table__empty-item",
            colSpan: headers.length,
            children: emptyMessage ?? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No data to display', 'woocommerce')
          })
        })]
      })]
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(Table));
try {
    // @ts-ignore
    table.displayName = "table";
    // @ts-ignore
    table.__docgenInfo = { "description": "", "displayName": "table", "props": { "classNames": { "defaultValue": null, "description": "Additional classnames", "name": "classNames", "required": false, "type": { "name": "string | Record<string, string>" } }, "className": { "defaultValue": null, "description": "Additional CSS classes.", "name": "className", "required": false, "type": { "name": "string" } }, "caption": { "defaultValue": null, "description": "A label for the content in this table", "name": "caption", "required": false, "type": { "name": "string" } }, "query": { "defaultValue": { value: "{}" }, "description": "The query string represented in object form", "name": "query", "required": false, "type": { "name": "QueryProps" } }, "headers": { "defaultValue": { value: "[]" }, "description": "An array of column headers (see `Table` props).", "name": "headers", "required": false, "type": { "name": "TableHeader[]" } }, "rows": { "defaultValue": { value: "[]" }, "description": "An array of arrays of display/value object pairs (see `Table` props).", "name": "rows", "required": false, "type": { "name": "TableRow[][]" } }, "rowKey": { "defaultValue": null, "description": "The rowKey used for the key value on each row, a function that returns the key.\nDefaults to index.", "name": "rowKey", "required": false, "type": { "name": "((row: TableRow[], index: number) => number)" } }, "emptyMessage": { "defaultValue": null, "description": "Customize the message to show when there are no rows in the table.", "name": "emptyMessage", "required": false, "type": { "name": "string" } }, "rowHeader": { "defaultValue": null, "description": "Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col\nis checkboxes, for example). Set to false to disable row headers.", "name": "rowHeader", "required": false, "type": { "name": "number | false" } }, "onSort": { "defaultValue": { value: "( f ) => f" }, "description": "A function called when sortable table headers are clicked, gets the `header.key` as argument.", "name": "onSort", "required": false, "type": { "name": "((key: string, direction: string) => void)" } }, "ariaHidden": { "defaultValue": null, "description": "Controls whether this component is hidden from screen readers. Used by the loading state, before there is data to read.\nDon't use this on real tables unless the table data is loaded elsewhere on the page.", "name": "ariaHidden", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/table/table.tsx#table"] = { docgenInfo: table.__docgenInfo, name: "table", path: "../../packages/js/components/src/table/table.tsx#table" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);