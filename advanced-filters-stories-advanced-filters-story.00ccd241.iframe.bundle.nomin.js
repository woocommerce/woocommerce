"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3388],{

/***/ "../../packages/js/components/src/advanced-filters/stories/advanced-filters.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/advanced-filters/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const ORDER_STATUSES = {
  cancelled: 'Cancelled',
  completed: 'Completed',
  failed: 'Failed',
  'on-hold': 'On hold',
  pending: 'Pending payment',
  processing: 'Processing',
  refunded: 'Refunded'
};
const siteLocale = 'en_US';
const currency = {
  code: 'USD',
  decimalSeparator: '.',
  precision: 2,
  priceFormat: '%1$s%2$s',
  symbol: '$',
  symbolPosition: 'left',
  thousandSeparator: ','
};
const path = new URL(document.location.href).searchParams.get('path') ?? '';
const query = {
  component: 'advanced-filters'
};
const advancedFilters = {
  title: 'Orders Match <select/> Filters',
  filters: {
    status: {
      labels: {
        add: 'Order Status',
        remove: 'Remove order status filter',
        rule: 'Select an order status filter match',
        title: '<title>Order Status</title> <rule/> <filter/>',
        filter: 'Select an order status'
      },
      rules: [{
        value: 'is',
        label: 'Is'
      }, {
        value: 'is_not',
        label: 'Is Not'
      }],
      input: {
        component: 'SelectControl',
        options: Object.keys(ORDER_STATUSES).map(key => ({
          value: key,
          label: ORDER_STATUSES[key]
        }))
      }
    },
    product: {
      labels: {
        add: 'Products',
        placeholder: 'Search products',
        remove: 'Remove products filter',
        rule: 'Select a product filter match',
        title: '<title>Product</title> <rule/> <filter/>',
        filter: 'Select products'
      },
      rules: [{
        value: 'includes',
        label: 'Includes'
      }, {
        value: 'excludes',
        label: 'Excludes'
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: () => Promise.resolve([])
      }
    },
    customer: {
      labels: {
        add: 'Customer type',
        remove: 'Remove customer filter',
        rule: 'Select a customer filter match',
        title: '<title>Customer is</title> <filter/>',
        filter: 'Select a customer type'
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'new',
          label: 'New'
        }, {
          value: 'returning',
          label: 'Returning'
        }],
        defaultOption: 'new'
      }
    },
    quantity: {
      labels: {
        add: 'Item Quantity',
        remove: 'Remove item quantity filter',
        rule: 'Select an item quantity filter match',
        title: '<title>Item Quantity is</title> <rule/> <filter/>'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number'
      }
    },
    subtotal: {
      labels: {
        add: 'Subtotal',
        remove: 'Remove subtotal filter',
        rule: 'Select a subtotal filter match',
        title: '<title>Subtotal is</title> <rule/> <filter/>'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number',
        type: 'currency'
      }
    },
    date: {
      labels: {
        add: 'Date',
        remove: 'Remove date filter',
        rule: 'Select a date filter match',
        title: '<title>Date</title> <rule/> <filter/>',
        filter: 'Select a transaction date'
      },
      rules: [{
        value: 'before',
        label: 'Before'
      }, {
        value: 'after',
        label: 'After'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Date'
      }
    }
  }
};
const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
  siteLocale: siteLocale,
  path: path,
  query: query,
  config: advancedFilters,
  currency: currency
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/AdvancedFilters',
  component: ___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <AdvancedFilters siteLocale={siteLocale} path={path} query={query} config={advancedFilters} currency={currency} />",
      ...Basic.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    AdvancedFilters.displayName = "AdvancedFilters";
    // @ts-ignore
    AdvancedFilters.__docgenInfo = { "description": "Displays a configurable set of filters which can modify query parameters.", "displayName": "AdvancedFilters", "props": { "config": { "defaultValue": null, "description": "The configuration object required to render filters.", "name": "config", "required": true, "type": { "name": "AdvancedFilterConfig" } }, "path": { "defaultValue": null, "description": "Name of this filter, used in translations.", "name": "path", "required": true, "type": { "name": "string" } }, "currency": { "defaultValue": null, "description": "The currency formatting instance for the site.", "name": "currency", "required": true, "type": { "name": "Currency" } }, "query": { "defaultValue": { value: "{}" }, "description": "The query string represented in object form.", "name": "query", "required": false, "type": { "name": "Query" } }, "onAdvancedFilterAction": { "defaultValue": { value: "() => {}" }, "description": "Function to be called after an advanced filter action has been taken.", "name": "onAdvancedFilterAction", "required": false, "type": { "name": "(action: AdvancedFilterAction, data?: Record<string, unknown> | ActiveFilter) => void" } }, "siteLocale": { "defaultValue": { value: "en_US" }, "description": "The locale for the site.", "name": "siteLocale", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/stories/advanced-filters.story.tsx#AdvancedFilters"] = { docgenInfo: AdvancedFilters.__docgenInfo, name: "AdvancedFilters", path: "../../packages/js/components/src/advanced-filters/stories/advanced-filters.story.tsx#AdvancedFilters" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);