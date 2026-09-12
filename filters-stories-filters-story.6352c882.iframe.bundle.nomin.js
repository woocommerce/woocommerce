(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3719,5190,7877,9668],{

/***/ "../../packages/js/components/src/filters/stories/filters.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Examples: () => (/* binding */ Examples),
  "default": () => (/* binding */ filters_story)
});

// EXTERNAL MODULE: ../../packages/js/components/src/section/header.tsx
var header = __webpack_require__("../../packages/js/components/src/section/header.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/section/section.tsx
var section = __webpack_require__("../../packages/js/components/src/section/section.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 4 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var date_src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/currency/src/index.ts + 3 modules
var currency_src = __webpack_require__("../../packages/js/currency/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/advanced-filters/index.tsx + 7 modules
var advanced_filters = __webpack_require__("../../packages/js/components/src/advanced-filters/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/compare-filter/index.js + 1 modules
var compare_filter = __webpack_require__("../../packages/js/components/src/compare-filter/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/date-range-filter-picker/index.js + 3 modules
var date_range_filter_picker = __webpack_require__("../../packages/js/components/src/date-range-filter-picker/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/filter-picker/index.js
var filter_picker = __webpack_require__("../../packages/js/components/src/filter-picker/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/filters/index.js
/**
 * External dependencies
 */








/**
 * Internal dependencies
 */






/**
 * Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
 * or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.
 *
 * @return {Object} -
 */

class ReportFilters extends react.Component {
  constructor() {
    super();
    this.renderCard = this.renderCard.bind(this);
    this.onRangeSelect = this.onRangeSelect.bind(this);
  }
  renderCard(config) {
    const {
      siteLocale,
      advancedFilters,
      query,
      path,
      onAdvancedFilterAction,
      currency
    } = this.props;
    const {
      filters,
      param
    } = config;
    if (!query[param]) {
      return null;
    }
    if (query[param].indexOf('compare') === 0) {
      const filter = (0,lodash.find)(filters, {
        value: query[param]
      });
      if (!filter) {
        return null;
      }
      const {
        settings = {}
      } = filter;
      return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-filters__advanced-filters",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(compare_filter/* CompareFilter */.S, {
          path: path,
          query: query,
          ...settings
        })
      }, param);
    }
    if (query[param] === 'advanced') {
      return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-filters__advanced-filters",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(advanced_filters/* default */.A, {
          siteLocale: siteLocale,
          currency: currency,
          config: advancedFilters,
          path: path,
          query: query,
          onAdvancedFilterAction: onAdvancedFilterAction
        })
      }, param);
    }
  }
  onRangeSelect(data) {
    const {
      query,
      path,
      onDateSelect
    } = this.props;
    (0,src/* updateQueryString */.Ze)(data, path, query);
    onDateSelect(data);
  }
  getDateQuery(query) {
    const {
      period,
      compare,
      before,
      after
    } = (0,date_src/* getDateParamsFromQuery */.vW)(query);
    const {
      primary: primaryDate,
      secondary: secondaryDate
    } = (0,date_src/* getCurrentDates */.lI)(query);
    return {
      period,
      compare,
      before,
      after,
      primaryDate,
      secondaryDate
    };
  }
  render() {
    const {
      dateQuery,
      filters,
      query,
      path,
      showDatePicker,
      onFilterSelect,
      isoDateFormat,
      advancedFilters
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
        className: "screen-reader-text",
        children: (0,build_module.__)('Filters', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(section/* Section */.w, {
        component: "div",
        className: "woocommerce-filters",
        children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "woocommerce-filters__basic-filters",
          children: [showDatePicker && /*#__PURE__*/(0,jsx_runtime.jsx)(date_range_filter_picker/* default */.A, {
            dateQuery: dateQuery || this.getDateQuery(query),
            onRangeSelect: this.onRangeSelect,
            isoDateFormat: isoDateFormat
          }, JSON.stringify(query)), filters.map(config => {
            if (config.showFilters(query)) {
              return /*#__PURE__*/(0,jsx_runtime.jsx)(filter_picker/* default */.A, {
                config: config,
                advancedFilters: advancedFilters,
                query: query,
                path: path,
                onFilterSelect: onFilterSelect
              }, config.param);
            }
            return null;
          })]
        }), filters.map(this.renderCard)]
      })]
    });
  }
}
ReportFilters.defaultProps = {
  siteLocale: 'en_US',
  advancedFilters: {
    title: '',
    filters: {}
  },
  filters: [],
  query: {},
  showDatePicker: true,
  onDateSelect: () => {},
  currency: (0,currency_src/* CurrencyFactory */.uU)().getCurrencyConfig()
};
/* harmony default export */ const filters = (ReportFilters);
;
ReportFilters.__docgenInfo = {
  "description": "Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the \"basic\" filters, and `AdvancedFilters`\nor a comparison card if \"advanced\" or \"compare\" are picked from `FilterPicker`.\n\n@return {Object} -",
  "methods": [{
    "name": "renderCard",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "config",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onRangeSelect",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "data",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getDateQuery",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "query",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "ReportFilters",
  "props": {
    "siteLocale": {
      "defaultValue": {
        "value": "'en_US'",
        "computed": false
      },
      "description": "The locale of the site (passed through to `AdvancedFilters`)",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "advancedFilters": {
      "defaultValue": {
        "value": "{\n\ttitle: '',\n\tfilters: {},\n}",
        "computed": false
      },
      "description": "Config option passed through to `AdvancedFilters`",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "filters": {
      "defaultValue": {
        "value": "[]",
        "computed": false
      },
      "description": "Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.",
      "type": {
        "name": "array"
      },
      "required": false
    },
    "query": {
      "defaultValue": {
        "value": "{}",
        "computed": false
      },
      "description": "The query string represented in object form",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "showDatePicker": {
      "defaultValue": {
        "value": "true",
        "computed": false
      },
      "description": "Whether the date picker must be shown.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "onDateSelect": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "Function to be called after date selection.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "currency": {
      "defaultValue": {
        "value": "CurrencyFactory().getCurrencyConfig()",
        "computed": true
      },
      "description": "The currency formatting instance for the site.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "path": {
      "description": "The `path` parameter supplied by React-Router",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "onFilterSelect": {
      "description": "Function to be called after filter selection.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "onAdvancedFilterAction": {
      "description": "Function to be called after an advanced filter action has been taken.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "dateQuery": {
      "description": "The date query string represented in object form.",
      "type": {
        "name": "shape",
        "value": {
          "period": {
            "name": "string",
            "required": true
          },
          "compare": {
            "name": "string",
            "required": true
          },
          "before": {
            "name": "object",
            "required": false
          },
          "after": {
            "name": "object",
            "required": false
          },
          "primaryDate": {
            "name": "shape",
            "value": {
              "label": {
                "name": "string",
                "required": true
              },
              "range": {
                "name": "string",
                "required": true
              }
            },
            "required": true
          },
          "secondaryDate": {
            "name": "shape",
            "value": {
              "label": {
                "name": "string",
                "required": true
              },
              "range": {
                "name": "string",
                "required": true
              }
            },
            "required": false
          }
        }
      },
      "required": false
    },
    "isoDateFormat": {
      "description": "ISO date format string.",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/filters/stories/filters.story.js
/**
 * External dependencies
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
const CURRENCY = {
  code: 'USD',
  decimalSeparator: '.',
  precision: 2,
  priceFormat: '%1$s%2$s',
  symbol: '$',
  symbolPosition: 'left',
  thousandSeparator: ','
};

// Fetch store default date range and compose with date utility functions.
const defaultDateRange = 'period=month&compare=previous_year';
const storeGetDateParamsFromQuery = (0,lodash.partialRight)(date_src/* getDateParamsFromQuery */.vW, defaultDateRange);
const storeGetCurrentDates = (0,lodash.partialRight)(date_src/* getCurrentDates */.lI, defaultDateRange);

// Package date utilities for filter picker component.
const storeDate = {
  getDateParamsFromQuery: storeGetDateParamsFromQuery,
  getCurrentDates: storeGetCurrentDates,
  isoDateFormat: date_src/* isoDateFormat */.r3
};
const siteLocale = 'en_US';
const path = '';
const query = {};
const filters_story_filters = [{
  label: 'Show',
  staticParams: ['chart'],
  param: 'filter',
  showFilters: () => true,
  filters: [{
    label: 'All orders',
    value: 'all'
  }, {
    label: 'Advanced filters',
    value: 'advanced'
  }]
}];
const advancedFilters = {
  title: 'Orders Match <select/> Filters',
  filters: {
    status: {
      labels: {
        add: 'Order Status',
        remove: 'Remove order status filter',
        rule: 'Select an order status filter match',
        title: 'Order Status <rule/> <filter/>',
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
        title: 'Product <rule/> <filter/>',
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
        title: 'Customer is <filter/>',
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
        title: 'Item Quantity is <rule/> <filter/>'
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
        title: 'Subtotal is <rule/> <filter/>'
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
    }
  }
};
const compareFilter = {
  type: 'products',
  param: 'product',
  getLabels() {
    return Promise.resolve([]);
  },
  labels: {
    helpText: 'Select at least two products to compare',
    placeholder: 'Search for products to compare',
    title: 'Compare Products',
    update: 'Compare'
  }
};
const Examples = () => /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
    children: "Date picker only"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(section/* Section */.w, {
    component: false,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(filters, {
      path: path,
      query: query,
      storeDate: storeDate
    })
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
    children: "Date picker & more filters"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(section/* Section */.w, {
    component: false,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(filters, {
      filters: filters_story_filters,
      path: path,
      query: query,
      storeDate: storeDate
    })
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
    children: "Advanced filters"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(section/* Section */.w, {
    component: false,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(advanced_filters/* default */.A, {
      siteLocale: siteLocale,
      path: path,
      query: query,
      filterTitle: "Orders",
      config: advancedFilters,
      currency: CURRENCY
    })
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
    children: "Compare Filter"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(section/* Section */.w, {
    component: false,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(compare_filter/* CompareFilter */.S, {
      path: path,
      query: query,
      ...compareFilter
    })
  })]
});
/* harmony default export */ const filters_story = ({
  title: 'Components/ReportFilters',
  component: filters
});
Examples.parameters = {
  ...Examples.parameters,
  docs: {
    ...Examples.parameters?.docs,
    source: {
      originalSource: "() => <div>\n        <H>Date picker only</H>\n        <Section component={false}>\n            <ReportFilters path={path} query={query} storeDate={storeDate} />\n        </Section>\n\n        <H>Date picker & more filters</H>\n        <Section component={false}>\n            <ReportFilters filters={filters} path={path} query={query} storeDate={storeDate} />\n        </Section>\n\n        <H>Advanced filters</H>\n        <Section component={false}>\n            <AdvancedFilters siteLocale={siteLocale} path={path} query={query} filterTitle=\"Orders\" config={advancedFilters} currency={CURRENCY} />\n        </Section>\n\n        <H>Compare Filter</H>\n        <Section component={false}>\n            <CompareFilter path={path} query={query} {...compareFilter} />\n        </Section>\n    </div>",
      ...Examples.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ tab_panel_default)
});

// UNUSED EXPORTS: TabPanel

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/E4DZA6YM.js
var E4DZA6YM = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/E4DZA6YM.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js
var _55FNNNML = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/55FNNNML.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js
var TVXRYIJB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/OIOLJY4L.js
"use client";




// src/select/select-context.tsx

var ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [E4DZA6YM/* PopoverContextProvider */.wf, _55FNNNML/* CompositeContextProvider */.ws],
  [E4DZA6YM/* PopoverScopedContextProvider */.s1, _55FNNNML/* CompositeScopedContextProvider */.aN]
);
var useSelectContext = ctx.useContext;
var useSelectScopedContext = ctx.useScopedContext;
var useSelectProviderContext = ctx.useProviderContext;
var SelectContextProvider = ctx.ContextProvider;
var SelectScopedContextProvider = ctx.ScopedContextProvider;
var SelectItemCheckedContext = (0,react.createContext)(false);
var SelectHeadingContext = (0,react.createContext)(null);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js + 1 modules
var ZJG6VNPS = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZJG6VNPS.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/H55QERR5.js
"use client";




// src/combobox/combobox-context.tsx

var ComboboxListRoleContext = (0,react.createContext)(
  void 0
);
var H55QERR5_ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [E4DZA6YM/* PopoverContextProvider */.wf, _55FNNNML/* CompositeContextProvider */.ws],
  [E4DZA6YM/* PopoverScopedContextProvider */.s1, _55FNNNML/* CompositeScopedContextProvider */.aN]
);
var useComboboxContext = H55QERR5_ctx.useContext;
var useComboboxScopedContext = H55QERR5_ctx.useScopedContext;
var useComboboxProviderContext = H55QERR5_ctx.useProviderContext;
var ComboboxContextProvider = H55QERR5_ctx.ContextProvider;
var ComboboxScopedContextProvider = H55QERR5_ctx.ScopedContextProvider;
var ComboboxItemValueContext = (0,react.createContext)(
  void 0
);
var ComboboxItemCheckedContext = (0,react.createContext)(false);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js
var YAS7X7HB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js
var CEM7J6TT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js
var UNDE2QJS = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UNDE2QJS.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/KZX46JDB.js
var KZX46JDB = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/KZX46JDB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js
var XTZ53NXG = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js
var UWJK2WK2 = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/tab/tab-store.js
"use client";







// src/tab/tab-store.ts
function createTabStore({
  composite: parentComposite,
  combobox,
  ...props
} = {}) {
  const independentKeys = [
    "items",
    "renderedItems",
    "moves",
    "orientation",
    "virtualFocus",
    "includesBaseElement",
    "baseElement",
    "focusLoop",
    "focusShift",
    "focusWrap"
  ];
  const store = (0,XTZ53NXG/* mergeStore */.od)(
    props.store,
    (0,XTZ53NXG/* omit */.cJ)(parentComposite, independentKeys),
    (0,XTZ53NXG/* omit */.cJ)(combobox, independentKeys)
  );
  const syncState = store == null ? void 0 : store.getState();
  const composite = (0,UNDE2QJS/* createCompositeStore */.z)({
    ...props,
    store,
    // We need to explicitly set the default value of `includesBaseElement` to
    // `false` since we don't want the composite store to default it to `true`
    // when the activeId state is null, which could be the case when rendering
    // combobox with tab.
    includesBaseElement: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.includesBaseElement,
      syncState == null ? void 0 : syncState.includesBaseElement,
      false
    ),
    orientation: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.orientation,
      syncState == null ? void 0 : syncState.orientation,
      "horizontal"
    ),
    focusLoop: (0,UWJK2WK2/* defaultValue */.Jh)(props.focusLoop, syncState == null ? void 0 : syncState.focusLoop, true)
  });
  const panels = (0,KZX46JDB/* createCollectionStore */.I)();
  const initialState = {
    ...composite.getState(),
    selectedId: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.selectedId,
      syncState == null ? void 0 : syncState.selectedId,
      props.defaultSelectedId
    ),
    selectOnMove: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.selectOnMove,
      syncState == null ? void 0 : syncState.selectOnMove,
      true
    )
  };
  const tab = (0,XTZ53NXG/* createStore */.y$)(initialState, composite, store);
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* sync */.OH)(tab, ["moves"], () => {
      const { activeId, selectOnMove } = tab.getState();
      if (!selectOnMove) return;
      if (!activeId) return;
      const tabItem = composite.item(activeId);
      if (!tabItem) return;
      if (tabItem.dimmed) return;
      if (tabItem.disabled) return;
      tab.setState("selectedId", tabItem.id);
    })
  );
  let syncActiveId = true;
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* batch */.vA)(tab, ["selectedId"], (state, prev) => {
      if (!syncActiveId) {
        syncActiveId = true;
        return;
      }
      if (parentComposite && state.selectedId === prev.selectedId) return;
      tab.setState("activeId", state.selectedId);
    })
  );
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* sync */.OH)(tab, ["selectedId", "renderedItems"], (state) => {
      if (state.selectedId !== void 0) return;
      const { activeId, renderedItems } = tab.getState();
      const tabItem = composite.item(activeId);
      if (tabItem && !tabItem.disabled && !tabItem.dimmed) {
        tab.setState("selectedId", tabItem.id);
      } else {
        const tabItem2 = renderedItems.find(
          (item) => !item.disabled && !item.dimmed
        );
        tab.setState("selectedId", tabItem2 == null ? void 0 : tabItem2.id);
      }
    })
  );
  (0,XTZ53NXG/* setup */.mj)(
    tab,
    () => (0,XTZ53NXG/* sync */.OH)(tab, ["renderedItems"], (state) => {
      const tabs = state.renderedItems;
      if (!tabs.length) return;
      return (0,XTZ53NXG/* sync */.OH)(panels, ["renderedItems"], (state2) => {
        const items = state2.renderedItems;
        const hasOrphanPanels = items.some((panel) => !panel.tabId);
        if (!hasOrphanPanels) return;
        items.forEach((panel, i) => {
          if (panel.tabId) return;
          const tabItem = tabs[i];
          if (!tabItem) return;
          panels.renderItem({ ...panel, tabId: tabItem.id });
        });
      });
    })
  );
  let selectedIdFromSelectedValue = null;
  (0,XTZ53NXG/* setup */.mj)(tab, () => {
    const backupSelectedId = () => {
      selectedIdFromSelectedValue = tab.getState().selectedId;
    };
    const restoreSelectedId = () => {
      syncActiveId = false;
      tab.setState("selectedId", selectedIdFromSelectedValue);
    };
    if (parentComposite && "setSelectElement" in parentComposite) {
      return (0,UWJK2WK2/* chain */.cy)(
        (0,XTZ53NXG/* sync */.OH)(parentComposite, ["value"], backupSelectedId),
        (0,XTZ53NXG/* sync */.OH)(parentComposite, ["mounted"], restoreSelectedId)
      );
    }
    if (!combobox) return;
    return (0,UWJK2WK2/* chain */.cy)(
      (0,XTZ53NXG/* sync */.OH)(combobox, ["selectedValue"], backupSelectedId),
      (0,XTZ53NXG/* sync */.OH)(combobox, ["mounted"], restoreSelectedId)
    );
  });
  return {
    ...composite,
    ...tab,
    panels,
    setSelectedId: (id) => tab.setState("selectedId", id),
    select: (id) => {
      tab.setState("selectedId", id);
      composite.move(id);
    }
  };
}


;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/XTV6BHAW.js
"use client";






// src/tab/tab-store.ts


function useTabStoreProps(store, update, props) {
  (0,CEM7J6TT/* useUpdateEffect */.w5)(update, [props.composite, props.combobox]);
  store = (0,ZJG6VNPS/* useCompositeStoreProps */.YO)(store, update, props);
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "selectedId", "setSelectedId");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "selectOnMove");
  const [panels, updatePanels] = (0,YAS7X7HB/* useStore */.Pj)(() => store.panels, {});
  (0,CEM7J6TT/* useUpdateEffect */.w5)(updatePanels, [store, updatePanels]);
  return Object.assign(
    (0,react.useMemo)(() => ({ ...store, panels }), [store, panels]),
    { composite: props.composite, combobox: props.combobox }
  );
}
function useTabStore(props = {}) {
  const combobox = useComboboxContext();
  const composite = useSelectContext() || combobox;
  props = {
    ...props,
    composite: props.composite !== void 0 ? props.composite : composite,
    combobox: props.combobox !== void 0 ? props.combobox : combobox
  };
  const [store, update] = (0,YAS7X7HB/* useStore */.Pj)(createTabStore, props);
  return useTabStoreProps(store, update, props);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/F4SJNU4L.js
"use client";



// src/tab/tab-context.tsx
var F4SJNU4L_ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [_55FNNNML/* CompositeContextProvider */.ws],
  [_55FNNNML/* CompositeScopedContextProvider */.aN]
);
var useTabContext = F4SJNU4L_ctx.useContext;
var useTabScopedContext = F4SJNU4L_ctx.useScopedContext;
var useTabProviderContext = F4SJNU4L_ctx.useProviderContext;
var TabContextProvider = F4SJNU4L_ctx.ContextProvider;
var TabScopedContextProvider = F4SJNU4L_ctx.ScopedContextProvider;



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js
var _6PX47O7P = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6PX47O7P.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tab/tab-list.js
"use client";












// src/tab/tab-list.tsx


var TagName = "div";
var useTabList = (0,TVXRYIJB/* createHook */.ab)(
  function useTabList2({ store, ...props }) {
    const context = useTabProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const orientation = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => state.orientation === "both" ? void 0 : state.orientation
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, { value: store, children: element }),
      [store]
    );
    if (store.composite) {
      props = {
        focusable: false,
        ...props
      };
    }
    props = {
      role: "tablist",
      "aria-orientation": orientation,
      ...props
    };
    props = (0,_6PX47O7P/* useComposite */.T)({ store, ...props });
    return props;
  }
);
var TabList = (0,TVXRYIJB/* forwardRef */.Rf)(function TabList2(props) {
  const htmlProps = useTabList(props);
  return (0,TVXRYIJB/* createElement */.n)(TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js + 1 modules
var ZCYMVQGT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/ZCYMVQGT.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tab/tab.js
"use client";














// src/tab/tab.tsx



var tab_TagName = "button";
var useTab = (0,TVXRYIJB/* createHook */.ab)(function useTab2({
  store,
  getItem: getItemProp,
  ...props
}) {
  var _a;
  const context = useTabScopedContext();
  store = store || context;
  (0,UWJK2WK2/* invariant */.V1)(
    store,
     false && 0
  );
  const defaultId = (0,CEM7J6TT/* useId */.Bi)();
  const id = props.id || defaultId;
  const dimmed = (0,UWJK2WK2/* disabledFromProps */.$f)(props);
  const getItem = (0,react.useCallback)(
    (item) => {
      const nextItem = { ...item, dimmed };
      if (getItemProp) {
        return getItemProp(nextItem);
      }
      return nextItem;
    },
    [dimmed, getItemProp]
  );
  const onClickProp = props.onClick;
  const onClick = (0,CEM7J6TT/* useEvent */._q)((event) => {
    onClickProp == null ? void 0 : onClickProp(event);
    if (event.defaultPrevented) return;
    store == null ? void 0 : store.setSelectedId(id);
  });
  const panelId = (0,YAS7X7HB/* useStoreState */.O$)(
    store.panels,
    (state) => {
      var _a2;
      return (_a2 = state.items.find((item) => item.tabId === id)) == null ? void 0 : _a2.id;
    }
  );
  const shouldRegisterItem = defaultId ? props.shouldRegisterItem : false;
  const isActive = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => !!id && state.activeId === id
  );
  const selected = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => !!id && state.selectedId === id
  );
  const hasActiveItem = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => !!store.item(state.activeId)
  );
  const canRegisterComposedItem = isActive || selected && !hasActiveItem;
  const accessibleWhenDisabled = selected || ((_a = props.accessibleWhenDisabled) != null ? _a : true);
  const isWithinVirtualFocusComposite = (0,YAS7X7HB/* useStoreState */.O$)(
    store.combobox || store.composite,
    "virtualFocus"
  );
  if (isWithinVirtualFocusComposite) {
    props = {
      ...props,
      tabIndex: -1
    };
  }
  props = {
    role: "tab",
    "aria-selected": selected,
    "aria-controls": panelId || void 0,
    ...props,
    id,
    onClick
  };
  if (store.composite) {
    const defaultProps = {
      id,
      accessibleWhenDisabled,
      store: store.composite,
      shouldRegisterItem: canRegisterComposedItem && shouldRegisterItem,
      rowId: props.rowId,
      render: props.render
    };
    props = {
      ...props,
      render: /* @__PURE__ */ (0,jsx_runtime.jsx)(
        ZCYMVQGT/* CompositeItem */.l,
        {
          ...defaultProps,
          render: store.combobox && store.composite !== store.combobox ? /* @__PURE__ */ (0,jsx_runtime.jsx)(ZCYMVQGT/* CompositeItem */.l, { ...defaultProps, store: store.combobox }) : defaultProps.render
        }
      )
    };
  }
  props = (0,ZCYMVQGT/* useCompositeItem */.k)({
    store,
    ...props,
    accessibleWhenDisabled,
    getItem,
    shouldRegisterItem
  });
  return props;
});
var Tab = (0,TVXRYIJB/* memo */.ph)(
  (0,TVXRYIJB/* forwardRef */.Rf)(function Tab2(props) {
    const htmlProps = useTab(props);
    return (0,TVXRYIJB/* createElement */.n)(tab_TagName, htmlProps);
  })
);


// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/WLLNEL2X.js
var WLLNEL2X = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/WLLNEL2X.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FL7NPBCY.js
var FL7NPBCY = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FL7NPBCY.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IKWLDXMV.js
var IKWLDXMV = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IKWLDXMV.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js
var GR523XJ6 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js
var utils_focus = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tab/tab-panel.js
"use client";















// src/tab/tab-panel.tsx




var tab_panel_TagName = "div";
var useTabPanel = (0,TVXRYIJB/* createHook */.ab)(
  function useTabPanel2({
    store,
    unmountOnHide,
    tabId: tabIdProp,
    getItem: getItemProp,
    scrollRestoration,
    scrollElement,
    ...props
  }) {
    const context = useTabProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const id = (0,CEM7J6TT/* useId */.Bi)(props.id);
    const tabId = (0,YAS7X7HB/* useStoreState */.O$)(
      store.panels,
      () => {
        var _a;
        return tabIdProp || ((_a = store == null ? void 0 : store.panels.item(id)) == null ? void 0 : _a.tabId);
      }
    );
    const open = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => !!tabId && state.selectedId === tabId
    );
    const disclosure = (0,FL7NPBCY/* useDisclosureStore */.E)({ open });
    const mounted = (0,YAS7X7HB/* useStoreState */.O$)(disclosure, "mounted");
    const scrollPositionRef = (0,react.useRef)(
      /* @__PURE__ */ new Map()
    );
    const getScrollElement = (0,CEM7J6TT/* useEvent */._q)(() => {
      const panelElement = ref.current;
      if (!panelElement) return null;
      if (!scrollElement) return panelElement;
      if (typeof scrollElement === "function") {
        return scrollElement(panelElement);
      }
      if ("current" in scrollElement) {
        return scrollElement.current;
      }
      return scrollElement;
    });
    (0,react.useEffect)(() => {
      var _a, _b;
      if (!scrollRestoration) return;
      if (!mounted) return;
      const element = getScrollElement();
      if (!element) return;
      if (scrollRestoration === "reset") {
        element.scroll(0, 0);
        return;
      }
      if (!tabId) return;
      const position = scrollPositionRef.current.get(tabId);
      element.scroll((_a = position == null ? void 0 : position.x) != null ? _a : 0, (_b = position == null ? void 0 : position.y) != null ? _b : 0);
      const onScroll = () => {
        scrollPositionRef.current.set(tabId, {
          x: element.scrollLeft,
          y: element.scrollTop
        });
      };
      element.addEventListener("scroll", onScroll);
      return () => {
        element.removeEventListener("scroll", onScroll);
      };
    }, [scrollRestoration, mounted, tabId, getScrollElement, store]);
    const [hasTabbableChildren, setHasTabbableChildren] = (0,react.useState)(false);
    (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
      if (!mounted) return;
      const element = ref.current;
      if (!element) return;
      setHasTabbableChildren(!!(0,utils_focus/* getAllTabbableIn */.a9)(element).length);
    }, [mounted]);
    const getItem = (0,react.useCallback)(
      (item) => {
        const nextItem = { ...item, id: id || item.id, tabId: tabIdProp };
        if (getItemProp) {
          return getItemProp(nextItem);
        }
        return nextItem;
      },
      [id, tabIdProp, getItemProp]
    );
    const onKeyDownProp = props.onKeyDown;
    const onKeyDown = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onKeyDownProp == null ? void 0 : onKeyDownProp(event);
      if (event.defaultPrevented) return;
      if (!(store == null ? void 0 : store.composite)) return;
      const keyMap = {
        ArrowLeft: store.previous,
        ArrowRight: store.next,
        Home: store.first,
        End: store.last
      };
      const action = keyMap[event.key];
      if (!action) return;
      const { selectedId } = store.getState();
      const nextId = action({ activeId: selectedId });
      if (!nextId) return;
      event.preventDefault();
      store.move(nextId);
    });
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TabScopedContextProvider, { value: store, children: element }),
      [store]
    );
    props = {
      role: "tabpanel",
      "aria-labelledby": props["aria-label"] != null ? void 0 : tabId || void 0,
      ...props,
      id,
      children: unmountOnHide && !mounted ? null : props.children,
      ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref),
      onKeyDown
    };
    props = (0,GR523XJ6/* useFocusable */.W)({
      // If the tab panel is rendered as part of another composite widget such
      // as combobox, it should not be focusable.
      focusable: !store.composite && !hasTabbableChildren,
      ...props
    });
    props = (0,WLLNEL2X/* useDisclosureContent */.aT)({ store: disclosure, ...props });
    props = (0,IKWLDXMV/* useCollectionItem */.v)({ store: store.panels, ...props, getItem });
    return props;
  }
);
var TabPanel = (0,TVXRYIJB/* forwardRef */.Rf)(function TabPanel2(props) {
  const htmlProps = useTabPanel(props);
  return (0,TVXRYIJB/* createElement */.n)(tab_panel_TagName, htmlProps);
});


// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs
var use_previous = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js







const extractTabName = (id) => {
  if (typeof id === "undefined" || id === null) {
    return;
  }
  return id.match(/^tab-panel-[0-9]*-(.*)/)?.[1];
};
const UnforwardedTabPanel = ({
  className,
  children,
  tabs,
  selectOnMove = true,
  initialTabName,
  orientation = "horizontal",
  activeClass = "is-active",
  onSelect
}, ref) => {
  const instanceId = (0,use_instance_id/* default */.A)(tab_panel_TabPanel, "tab-panel");
  const prependInstanceId = (0,react.useCallback)((tabName) => {
    if (typeof tabName === "undefined") {
      return;
    }
    return `${instanceId}-${tabName}`;
  }, [instanceId]);
  const tabStore = useTabStore({
    setSelectedId: (newTabValue) => {
      if (typeof newTabValue === "undefined" || newTabValue === null) {
        return;
      }
      const newTab = tabs.find((t) => prependInstanceId(t.name) === newTabValue);
      if (newTab?.disabled || newTab === selectedTab) {
        return;
      }
      const simplifiedTabName = extractTabName(newTabValue);
      if (typeof simplifiedTabName === "undefined") {
        return;
      }
      onSelect?.(simplifiedTabName);
    },
    orientation,
    selectOnMove,
    defaultSelectedId: prependInstanceId(initialTabName),
    rtl: (0,build_module/* isRTL */.V8)()
  });
  const selectedTabName = extractTabName(YAS7X7HB/* useStoreState */.O$(tabStore, "selectedId"));
  const setTabStoreSelectedId = (0,react.useCallback)((tabName) => {
    tabStore.setState("selectedId", prependInstanceId(tabName));
  }, [prependInstanceId, tabStore]);
  const selectedTab = tabs.find(({
    name
  }) => name === selectedTabName);
  const previousSelectedTabName = (0,use_previous/* default */.A)(selectedTabName);
  (0,react.useEffect)(() => {
    if (previousSelectedTabName !== selectedTabName && selectedTabName === initialTabName && !!selectedTabName) {
      onSelect?.(selectedTabName);
    }
  }, [selectedTabName, initialTabName, onSelect, previousSelectedTabName]);
  (0,react.useLayoutEffect)(() => {
    if (selectedTab) {
      return;
    }
    const initialTab = tabs.find((tab) => tab.name === initialTabName);
    if (initialTabName && !initialTab) {
      return;
    }
    if (initialTab && !initialTab.disabled) {
      setTabStoreSelectedId(initialTab.name);
    } else {
      const firstEnabledTab = tabs.find((tab) => !tab.disabled);
      if (firstEnabledTab) {
        setTabStoreSelectedId(firstEnabledTab.name);
      }
    }
  }, [tabs, selectedTab, initialTabName, instanceId, setTabStoreSelectedId]);
  (0,react.useEffect)(() => {
    if (!selectedTab?.disabled) {
      return;
    }
    const firstEnabledTab = tabs.find((tab) => !tab.disabled);
    if (firstEnabledTab) {
      setTabStoreSelectedId(firstEnabledTab.name);
    }
  }, [tabs, selectedTab?.disabled, setTabStoreSelectedId, instanceId]);
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
    className,
    ref,
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(TabList, {
      store: tabStore,
      className: "components-tab-panel__tabs",
      children: tabs.map((tab) => {
        return /* @__PURE__ */ (0,jsx_runtime.jsx)(Tab, {
          id: prependInstanceId(tab.name),
          className: (0,clsx/* default */.A)("components-tab-panel__tabs-item", tab.className, {
            [activeClass]: tab.name === selectedTabName
          }),
          disabled: tab.disabled,
          "aria-controls": `${prependInstanceId(tab.name)}-view`,
          render: /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            __next40pxDefaultSize: true,
            icon: tab.icon,
            label: tab.icon && tab.title,
            showTooltip: !!tab.icon
          }),
          children: !tab.icon && tab.title
        }, tab.name);
      })
    }), selectedTab && /* @__PURE__ */ (0,jsx_runtime.jsx)(TabPanel, {
      id: `${prependInstanceId(selectedTab.name)}-view`,
      store: tabStore,
      tabId: prependInstanceId(selectedTab.name),
      className: "components-tab-panel__tab-content",
      children: children(selectedTab)
    })]
  });
};
const tab_panel_TabPanel = (0,react.forwardRef)(UnforwardedTabPanel);
var tab_panel_default = tab_panel_TabPanel;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-previous/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ usePrevious)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-previous/index.ts

function usePrevious(value) {
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(void 0);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    ref.current = value;
  }, [value]);
  return ref.current;
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  XC: () => (/* binding */ build_module_focus)
});

// UNUSED EXPORTS: __unstableStripHTML, computeCaretRect, documentHasSelection, documentHasTextSelection, documentHasUncollapsedSelection, getFilesFromDataTransfer, getOffsetParent, getPhrasingContentSchema, getRectangleFromRange, getScrollContainer, insertAfter, isEmpty, isEntirelySelected, isFormElement, isHorizontalEdge, isNumberInput, isPhrasingContent, isRTL, isSelectionForward, isTextContent, isTextField, isVerticalEdge, placeCaretAtHorizontalEdge, placeCaretAtVerticalEdge, remove, removeInvalidHTML, replace, replaceTag, safeHTML, unwrap, wrap

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/focusable.js
var focusable_namespaceObject = {};
__webpack_require__.r(focusable_namespaceObject);
__webpack_require__.d(focusable_namespaceObject, {
  find: () => (find)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/tabbable.js
var tabbable_namespaceObject = {};
__webpack_require__.r(tabbable_namespaceObject);
__webpack_require__.d(tabbable_namespaceObject, {
  find: () => (tabbable_find),
  findNext: () => (findNext),
  findPrevious: () => (findPrevious),
  isTabbableIndex: () => (isTabbableIndex)
});

;// ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/focusable.js
function buildSelector(sequential) {
  return [
    sequential ? '[tabindex]:not([tabindex^="-"])' : "[tabindex]",
    "a[href]",
    "button:not([disabled])",
    'input:not([type="hidden"]):not([disabled])',
    "select:not([disabled])",
    "textarea:not([disabled])",
    'iframe:not([tabindex^="-"])',
    "object",
    "embed",
    "summary",
    "area[href]",
    "[contenteditable]:not([contenteditable=false])"
  ].join(",");
}
function isVisible(element) {
  return element.offsetWidth > 0 || element.offsetHeight > 0 || element.getClientRects().length > 0;
}
function isValidFocusableArea(element) {
  const map = element.closest("map[name]");
  if (!map) {
    return false;
  }
  const img = element.ownerDocument.querySelector(
    'img[usemap="#' + map.name + '"]'
  );
  return !!img && isVisible(img);
}
function find(context, { sequential = false } = {}) {
  const elements = context.querySelectorAll(buildSelector(sequential));
  return Array.from(elements).filter((element) => {
    if (!isVisible(element)) {
      return false;
    }
    const { nodeName } = element;
    if ("AREA" === nodeName) {
      return isValidFocusableArea(
        /** @type {HTMLAreaElement} */
        element
      );
    }
    return true;
  });
}

//# sourceMappingURL=focusable.js.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/tabbable.js

function getTabIndex(element) {
  const tabIndex = element.getAttribute("tabindex");
  return tabIndex === null ? 0 : parseInt(tabIndex, 10);
}
function isTabbableIndex(element) {
  return getTabIndex(element) !== -1;
}
function createStatefulCollapseRadioGroup() {
  const CHOSEN_RADIO_BY_NAME = {};
  return function collapseRadioGroup(result, element) {
    const { nodeName, type, checked, name } = element;
    if (nodeName !== "INPUT" || type !== "radio" || !name) {
      return result.concat(element);
    }
    const hasChosen = CHOSEN_RADIO_BY_NAME.hasOwnProperty(name);
    const isChosen = checked || !hasChosen;
    if (!isChosen) {
      return result;
    }
    if (hasChosen) {
      const hadChosenElement = CHOSEN_RADIO_BY_NAME[name];
      result = result.filter((e) => e !== hadChosenElement);
    }
    CHOSEN_RADIO_BY_NAME[name] = element;
    return result.concat(element);
  };
}
function mapElementToObjectTabbable(element, index) {
  return { element, index };
}
function mapObjectTabbableToElement(object) {
  return object.element;
}
function compareObjectTabbables(a, b) {
  const aTabIndex = getTabIndex(a.element);
  const bTabIndex = getTabIndex(b.element);
  if (aTabIndex === bTabIndex) {
    return a.index - b.index;
  }
  return aTabIndex - bTabIndex;
}
function filterTabbable(focusables) {
  return focusables.filter(isTabbableIndex).map(mapElementToObjectTabbable).sort(compareObjectTabbables).map(mapObjectTabbableToElement).reduce(createStatefulCollapseRadioGroup(), []);
}
function tabbable_find(context) {
  return filterTabbable(find(context));
}
function findPrevious(element) {
  return filterTabbable(find(element.ownerDocument.body)).reverse().find(
    (focusable) => (
      // eslint-disable-next-line no-bitwise
      element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_PRECEDING
    )
  );
}
function findNext(element) {
  return filterTabbable(find(element.ownerDocument.body)).find(
    (focusable) => (
      // eslint-disable-next-line no-bitwise
      element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_FOLLOWING
    )
  );
}

//# sourceMappingURL=tabbable.js.map

;// ../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js


const build_module_focus = { focusable: focusable_namespaceObject, tabbable: tabbable_namespaceObject };




//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_left_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_left_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z" }) });

//# sourceMappingURL=chevron-left.js.map


/***/ }),

/***/ "../../packages/js/components/src/animation-slider/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.
 * All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition
 */

class AnimationSlider extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  constructor() {
    super();
    this.state = {
      animate: null
    };
    this.container = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createRef)();
    this.onExited = this.onExited.bind(this);
  }
  onExited() {
    const {
      onExited
    } = this.props;
    if (onExited) {
      onExited(this.container.current);
    }
  }
  render() {
    const {
      children,
      animationKey,
      animate
    } = this.props;
    const containerClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-slide-animation', animate && `animate-${animate}`);
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: containerClasses,
      ref: this.container,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_transition_group__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_transition_group__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
          timeout: 200,
          classNames: "slide",
          ...this.props,
          onExited: this.onExited,
          children: status => children({
            status
          })
        }, animationKey)
      })
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AnimationSlider);
;
AnimationSlider.__docgenInfo = {
  "description": "This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.\nAll other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition",
  "methods": [{
    "name": "onExited",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "AnimationSlider",
  "props": {
    "children": {
      "description": "A function returning rendered content with argument status, reflecting `CSSTransition` status.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "animationKey": {
      "description": "A unique identifier for each slideable page.",
      "type": {
        "name": "any"
      },
      "required": true
    },
    "animate": {
      "description": "null, 'left', 'right', to designate which direction to slide on a change.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "null",
          "computed": false
        }, {
          "value": "'left'",
          "computed": false
        }, {
          "value": "'right'",
          "computed": false
        }]
      },
      "required": false
    },
    "onExited": {
      "description": "A function to be executed after a transition is complete, passing the containing ref as the argument.",
      "type": {
        "name": "func"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/calendar/date-range.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date_range)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/object/assign.js
var object_assign = __webpack_require__("../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/object/assign.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/array/from.js
var from = __webpack_require__("../../node_modules/.pnpm/core-js@3.49.0/node_modules/core-js/features/array/from.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/index.js
var react_dates = __webpack_require__("../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js + 29 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/initialize.js
var initialize = __webpack_require__("../../node_modules/.pnpm/react-dates@21.8.0_@babel+r_3f032592274ed6d887ae7f3314d2479d/node_modules/react-dates/initialize.js");
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/input.js
var input = __webpack_require__("../../packages/js/components/src/calendar/input.js");
;// ../../packages/js/components/src/calendar/phrases.js
/**
 * External dependencies
 */

/* harmony default export */ const phrases = ({
  calendarLabel: (0,build_module.__)('Calendar', 'woocommerce'),
  closeDatePicker: (0,build_module.__)('Close', 'woocommerce'),
  focusStartDate: (0,build_module.__)('Interact with the calendar and select start and end dates.', 'woocommerce'),
  clearDate: (0,build_module.__)('Clear Date', 'woocommerce'),
  clearDates: (0,build_module.__)('Clear Dates', 'woocommerce'),
  jumpToPrevMonth: (0,build_module.__)('Move backward to switch to the previous month.', 'woocommerce'),
  jumpToNextMonth: (0,build_module.__)('Move forward to switch to the next month.', 'woocommerce'),
  enterKey: (0,build_module.__)('Enter key', 'woocommerce'),
  leftArrowRightArrow: (0,build_module.__)('Right and left arrow keys', 'woocommerce'),
  upArrowDownArrow: (0,build_module.__)('up and down arrow keys', 'woocommerce'),
  pageUpPageDown: (0,build_module.__)('page up and page down keys', 'woocommerce'),
  homeEnd: (0,build_module.__)('Home and end keys', 'woocommerce'),
  escape: (0,build_module.__)('Escape key', 'woocommerce'),
  questionMark: (0,build_module.__)('Question mark', 'woocommerce'),
  selectFocusedDate: (0,build_module.__)('Select the date in focus.', 'woocommerce'),
  moveFocusByOneDay: (0,build_module.__)('Move backward (left) and forward (right) by one day.', 'woocommerce'),
  moveFocusByOneWeek: (0,build_module.__)('Move backward (up) and forward (down) by one week.', 'woocommerce'),
  moveFocusByOneMonth: (0,build_module.__)('Switch months.', 'woocommerce'),
  moveFocustoStartAndEndOfWeek: (0,build_module.__)('Go to the first or last day of a week.', 'woocommerce'),
  returnFocusToInput: (0,build_module.__)('Return to the date input field.', 'woocommerce'),
  keyboardNavigationInstructions: (0,build_module.__)('Press the down arrow key to interact with the calendar and select a date.', 'woocommerce'),
  chooseAvailableStartDate: ({
    date
  }) => /* translators: %s: start date */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('Select %s as a start date.', 'woocommerce'), date),
  chooseAvailableEndDate: ({
    date
  }) => /* translators: %s: end date */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('Select %s as an end date.', 'woocommerce'), date),
  chooseAvailableDate: ({
    date
  }) => date,
  dateIsUnavailable: ({
    date
  }) => /* translators: %s: unavailable date which was selected */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('%s is not selectable.', 'woocommerce'), date),
  dateIsSelected: ({
    date
  }) => /* translators: %s: selected date successfully */
  (0,build_module/* sprintf */.nv)((0,build_module.__)('Selected. %s', 'woocommerce'), date)
});
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/calendar/date-range.js
/**
 * External dependencies
 */












// ^^ The above: Turn on react-dates classes/styles, see https://github.com/airbnb/react-dates#initialize.

/**
 * Internal dependencies
 */



const isRTL = () => document.documentElement.dir === 'rtl';
// Blur event sources
const CONTAINER_DIV = 'container';
const NEXT_MONTH_CLICK = 'onNextMonthClick';
const PREV_MONTH_CLICK = 'onPrevMonthClick';

/**
 * This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.
 */
class DateRange extends react.Component {
  constructor(props) {
    super(props);
    this.onDatesChange = this.onDatesChange.bind(this);
    this.onFocusChange = this.onFocusChange.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
    this.nodeRef = (0,react.createRef)();
    this.keepFocusInside = this.keepFocusInside.bind(this);
  }

  /*
   * Todo: We should remove this function when possible.
   * It is kept because focus is lost when we click on the previous and next
   * month buttons or clicking on a date in the calendar.
   * This focus loss closes the date picker popover.
   * Ideally we should add an upstream commit on react-dates to fix this issue.
   *
   * See: https://github.com/WordPress/gutenberg/pull/17201.
   */
  keepFocusInside(blurSource, e) {
    if (!this.nodeRef.current) {
      return;
    }
    const {
      losesFocusTo
    } = this.props;

    // Blur triggered internal to the DayPicker component.
    if (CONTAINER_DIV === blurSource && e.target && (e.target.classList.contains('DayPickerNavigation_button') || e.target.classList.contains('CalendarDay')) && (
    // Allow other DayPicker elements to take focus.
    !e.relatedTarget || !e.relatedTarget.classList.contains('DayPickerNavigation_button') && !e.relatedTarget.classList.contains('CalendarDay'))) {
      // Allow other DayPicker elements to take focus.
      if (e.relatedTarget && (e.relatedTarget.classList.contains('DayPickerNavigation_button') || e.relatedTarget.classList.contains('CalendarDay'))) {
        return;
      }

      // Allow elements inside a specified ref to take focus.
      if (e.relatedTarget && losesFocusTo && losesFocusTo.contains(e.relatedTarget)) {
        return;
      }

      // DayPickerNavigation or CalendarDay mouseUp() is blurring,
      // so switch focus to the DayPicker's focus region.
      const focusRegion = this.nodeRef.current.querySelector('.DayPicker_focusRegion');
      if (focusRegion) {
        focusRegion.focus();
      }
      return;
    }

    // Blur triggered after next/prev click callback props.
    if (PREV_MONTH_CLICK === blurSource || NEXT_MONTH_CLICK === blurSource) {
      // DayPicker's updateStateAfterMonthTransition() is about to blur
      // the activeElement, so focus a DayPickerNavigation button so the next
      // blur event gets fixed by the above logic path.
      const focusRegion = this.nodeRef.current.querySelector('.DayPickerNavigation_button');
      if (focusRegion) {
        focusRegion.focus();
      }
    }
  }
  onDatesChange({
    startDate,
    endDate
  }) {
    const {
      onUpdate,
      shortDateFormat
    } = this.props;
    onUpdate({
      after: startDate,
      before: endDate,
      afterText: startDate ? startDate.format(shortDateFormat) : '',
      beforeText: endDate ? endDate.format(shortDateFormat) : '',
      afterError: null,
      beforeError: null
    });
  }
  onFocusChange(focusedInput) {
    this.props.onUpdate({
      focusedInput: !focusedInput ? 'startDate' : focusedInput
    });
  }
  onInputChange(input, event) {
    const value = event.target.value;
    const {
      after,
      before,
      shortDateFormat
    } = this.props;
    const {
      date,
      error
    } = (0,src/* validateDateInputForRange */.t_)(input, value, before, after, shortDateFormat);
    this.props.onUpdate({
      [input]: date,
      [input + 'Text']: value,
      [input + 'Error']: value.length > 0 ? error : null
    });
  }
  setTnitialVisibleMonth(isDoubleCalendar, before) {
    return () => {
      const isValidMoment = before && moment_default().isMoment(before) && before.isValid();
      const visibleDate = isValidMoment ? before : moment_default()();
      if (isDoubleCalendar) {
        return visibleDate.clone().subtract(1, 'month');
      }
      return visibleDate;
    };
  }
  render() {
    const {
      after,
      before,
      focusedInput,
      afterText,
      beforeText,
      afterError,
      beforeError,
      shortDateFormat,
      shortDateFormatPlaceholder,
      isViewportMobile,
      isViewportSmall,
      isInvalidDate
    } = this.props;
    const isDoubleCalendar = isViewportMobile && !isViewportSmall;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('woocommerce-calendar', {
        'is-mobile': isViewportMobile
      }),
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-calendar__inputs",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(input/* default */.A, {
          value: afterText,
          onChange: (0,lodash.partial)(this.onInputChange, 'after'),
          dateFormat: shortDateFormatPlaceholder || shortDateFormat,
          label: (0,build_module.__)('Start Date', 'woocommerce'),
          error: afterError,
          describedBy: (0,build_module/* sprintf */.nv)(/* translators: %s: date format specification */
          (0,build_module.__)("Date input describing a selected date range's start date in format %s", 'woocommerce'), shortDateFormatPlaceholder || shortDateFormat),
          onFocus: () => this.onFocusChange('startDate')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-calendar__inputs-to",
          children: (0,build_module.__)('to', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(input/* default */.A, {
          value: beforeText,
          onChange: (0,lodash.partial)(this.onInputChange, 'before'),
          dateFormat: shortDateFormatPlaceholder || shortDateFormat,
          label: (0,build_module.__)('End Date', 'woocommerce'),
          error: beforeError,
          describedBy: (0,build_module/* sprintf */.nv)(/* translators: %s: date format specification */
          (0,build_module.__)("Date input describing a selected date range's end date in format %s", 'woocommerce'), shortDateFormatPlaceholder || shortDateFormat),
          onFocus: () => this.onFocusChange('endDate')
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-calendar__react-dates",
        ref: this.nodeRef,
        onBlur: (0,lodash.partial)(this.keepFocusInside, CONTAINER_DIV),
        tabIndex: -1,
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(react_dates.DayPickerRangeController, {
          onNextMonthClick: (0,lodash.partial)(this.keepFocusInside, NEXT_MONTH_CLICK),
          onPrevMonthClick: (0,lodash.partial)(this.keepFocusInside, PREV_MONTH_CLICK),
          onDatesChange: this.onDatesChange,
          onFocusChange: this.onFocusChange,
          focusedInput: focusedInput,
          startDate: after,
          endDate: before,
          orientation: 'horizontal',
          numberOfMonths: isDoubleCalendar ? 2 : 1,
          isOutsideRange: date => {
            return isInvalidDate && isInvalidDate(date.toDate());
          },
          minimumNights: 0,
          hideKeyboardShortcutsPanel: true,
          noBorder: true,
          isRTL: isRTL(),
          initialVisibleMonth: this.setTnitialVisibleMonth(isDoubleCalendar, before),
          phrases: phrases
        })
      })]
    });
  }
}
/* harmony default export */ const date_range = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium',
  isViewportSmall: '< small'
})(DateRange));
;
DateRange.__docgenInfo = {
  "description": "This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.",
  "methods": [{
    "name": "keepFocusInside",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "blurSource",
      "optional": false,
      "type": null
    }, {
      "name": "e",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onDatesChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "{ startDate, endDate }",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onFocusChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "focusedInput",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onInputChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "input",
      "optional": false,
      "type": null
    }, {
      "name": "event",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "setTnitialVisibleMonth",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "isDoubleCalendar",
      "optional": false,
      "type": null
    }, {
      "name": "before",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "DateRange",
  "props": {
    "after": {
      "description": "A moment date object representing the selected start. `null` for no selection.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "afterError": {
      "description": "A string error message, shown to the user.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "afterText": {
      "description": "The start date in human-readable format. Displayed in the text input.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "before": {
      "description": "A moment date object representing the selected end. `null` for no selection.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "beforeError": {
      "description": "A string error message, shown to the user.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "beforeText": {
      "description": "The end date in human-readable format. Displayed in the text input.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "focusedInput": {
      "description": "String identifying which is the currently focused input (start or end).",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "isInvalidDate": {
      "description": "A function to determine if a day on the calendar is not valid",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "onUpdate": {
      "description": "A function called upon selection of a date.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "shortDateFormat": {
      "description": "The date format in moment.js-style tokens.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "shortDateFormatPlaceholder": {
      "description": "The date format in human-readable format.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "losesFocusTo": {
      "description": "A ref that the DateRange can lose focus to.\nSee: https://github.com/woocommerce/woocommerce-admin/pull/2929.",
      "type": {
        "name": "instanceOf",
        "value": "Element"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/calendar/input.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/calendar.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */







const DateInput = ({
  disabled = false,
  value,
  onChange,
  dateFormat,
  label,
  describedBy,
  error,
  onFocus = () => {},
  onBlur = () => {},
  onKeyDown = lodash__WEBPACK_IMPORTED_MODULE_0__.noop,
  errorPosition = 'bottom center'
}) => {
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-calendar__input', {
    'is-empty': value.length === 0,
    'is-error': error
  });
  const id = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.uniqueId)('_woo-dates-input');
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    className: classes,
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
      type: "text",
      className: "woocommerce-calendar__input-text",
      value: value,
      onChange: onChange,
      "aria-label": label,
      id: id,
      "aria-describedby": `${id}-message`,
      placeholder: dateFormat.toLowerCase(),
      onFocus: onFocus,
      onBlur: onBlur,
      onKeyDown: onKeyDown,
      disabled: disabled
    }), error && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
      className: "woocommerce-calendar__input-error",
      focusOnMount: false,
      position: errorPosition,
      children: error
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A,
      className: "calendar-icon"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("p", {
      className: "screen-reader-text",
      id: `${id}-message`,
      children: error || describedBy
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DateInput);
;
DateInput.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "DateInput",
  "props": {
    "disabled": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "onFocus": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "onBlur": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "onKeyDown": {
      "defaultValue": {
        "value": "noop",
        "computed": true
      },
      "description": "",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "errorPosition": {
      "defaultValue": {
        "value": "'bottom center'",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "value": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onChange": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "dateFormat": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "label": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "describedBy": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "error": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/compare-filter/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  S: () => (/* binding */ CompareFilter)
});

// UNUSED EXPORTS: CompareButton

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js + 6 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 4 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js + 40 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/compare-filter/button.js
/**
 * External dependencies
 */





/**
 * A button used when comparing items, if `count` is less than 2 a hoverable tooltip is added with `helpText`.
 *
 * @param {Object}   props
 * @param {string}   props.className
 * @param {number}   props.count
 * @param {Node}     props.children
 * @param {boolean}  props.disabled
 * @param {string}   props.helpText
 * @param {Function} props.onClick
 * @return {Object} -
 */

const CompareButton = ({
  className,
  count,
  children,
  disabled,
  helpText,
  onClick
}) => !disabled && count < 2 ? /*#__PURE__*/(0,jsx_runtime.jsx)(tooltip/* default */.Ay, {
  text: helpText,
  children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
    className: className,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      className: "woocommerce-compare-button",
      disabled: true,
      isSecondary: true,
      children: children
    })
  })
}) : /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
  className: (0,clsx/* default */.A)('woocommerce-compare-button', className),
  onClick: onClick,
  disabled: disabled,
  isSecondary: true,
  children: children
});
/* harmony default export */ const compare_filter_button = (CompareButton);
;
CompareButton.__docgenInfo = {
  "description": "A button used when comparing items, if `count` is less than 2 a hoverable tooltip is added with `helpText`.\n\n@param {Object}   props\n@param {string}   props.className\n@param {number}   props.count\n@param {Node}     props.children\n@param {boolean}  props.disabled\n@param {string}   props.helpText\n@param {Function} props.onClick\n@return {Object} -",
  "methods": [],
  "displayName": "CompareButton",
  "props": {
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "count": {
      "description": "The count of items selected.",
      "type": {
        "name": "number"
      },
      "required": true
    },
    "children": {
      "description": "The button content.",
      "type": {
        "name": "node"
      },
      "required": true
    },
    "helpText": {
      "description": "Text displayed when hovering over a disabled button.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "onClick": {
      "description": "The function called when the button is clicked.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "disabled": {
      "description": "Whether the control is disabled or not.",
      "type": {
        "name": "bool"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../packages/js/components/src/search/index.tsx
var search = __webpack_require__("../../packages/js/components/src/search/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental.js
var experimental = __webpack_require__("../../packages/js/components/src/experimental.js");
;// ../../packages/js/components/src/compare-filter/index.js
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */






/**
 * Displays a card + search used to filter results as a comparison between objects.
 */
class CompareFilter extends react.Component {
  constructor({
    getLabels,
    param,
    query
  }) {
    super(...arguments);
    this.state = {
      selected: []
    };
    this.clearQuery = this.clearQuery.bind(this);
    this.updateQuery = this.updateQuery.bind(this);
    this.updateLabels = this.updateLabels.bind(this);
    this.onButtonClicked = this.onButtonClicked.bind(this);
    if (query[param]) {
      getLabels(query[param], query).then(this.updateLabels);
    }
  }
  componentDidUpdate({
    param: prevParam,
    query: prevQuery
  }, {
    selected: prevSelected
  }) {
    const {
      getLabels,
      param,
      query
    } = this.props;
    const {
      selected
    } = this.state;
    if (prevParam !== param || prevSelected.length > 0 && selected.length === 0) {
      this.clearQuery();
      return;
    }
    const prevIds = (0,src/* getIdsFromQuery */.DF)(prevQuery[param]);
    const currentIds = (0,src/* getIdsFromQuery */.DF)(query[param]);
    if (!(0,lodash.isEqual)(prevIds.sort(), currentIds.sort())) {
      getLabels(query[param], query).then(this.updateLabels);
    }
  }
  clearQuery() {
    const {
      param,
      path,
      query
    } = this.props;
    this.setState({
      selected: []
    });
    (0,src/* updateQueryString */.Ze)({
      [param]: undefined
    }, path, query);
  }
  updateLabels(selected) {
    this.setState({
      selected
    });
  }
  updateQuery() {
    const {
      param,
      path,
      query
    } = this.props;
    const {
      selected
    } = this.state;
    const idList = selected.map(p => p.key);
    (0,src/* updateQueryString */.Ze)({
      [param]: idList.join(',')
    }, path, query);
  }
  onButtonClicked(e) {
    this.updateQuery(e);
    if ((0,lodash.isFunction)(this.props.onClick)) {
      this.props.onClick(e);
    }
  }
  render() {
    const {
      labels,
      type,
      autocompleter
    } = this.props;
    const {
      selected
    } = this.state;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
      className: "woocommerce-filters__compare",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(card_header_component/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(experimental/* Text */.E, {
          variant: "subtitle.small",
          weight: "600",
          size: "14",
          lineHeight: "20px",
          children: labels.title
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(card_body_component/* default */.A, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(search/* default */.A, {
          autocompleter: autocompleter,
          type: type,
          selected: selected,
          placeholder: labels.placeholder,
          onChange: value => {
            this.setState({
              selected: value
            });
          }
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(card_footer_component/* default */.A, {
        justify: "flex-start",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(compare_filter_button, {
          count: selected.length,
          helpText: labels.helpText,
          onClick: this.onButtonClicked,
          children: labels.update
        }), selected.length > 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          isLink: true,
          onClick: this.clearQuery,
          children: (0,build_module.__)('Clear all', 'woocommerce')
        })]
      })]
    });
  }
}
CompareFilter.defaultProps = {
  labels: {},
  query: {}
};
;
CompareFilter.__docgenInfo = {
  "description": "Displays a card + search used to filter results as a comparison between objects.",
  "methods": [{
    "name": "clearQuery",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "updateLabels",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selected",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "updateQuery",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "onButtonClicked",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "e",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "CompareFilter",
  "props": {
    "labels": {
      "defaultValue": {
        "value": "{}",
        "computed": false
      },
      "description": "Object of localized labels.",
      "type": {
        "name": "shape",
        "value": {
          "placeholder": {
            "name": "string",
            "description": "Label for the search placeholder.",
            "required": false
          },
          "title": {
            "name": "string",
            "description": "Label for the card title.",
            "required": false
          },
          "update": {
            "name": "string",
            "description": "Label for button which updates the URL/report.",
            "required": false
          }
        }
      },
      "required": false
    },
    "query": {
      "defaultValue": {
        "value": "{}",
        "computed": false
      },
      "description": "The query string represented in object form",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "getLabels": {
      "description": "Function used to fetch object labels via an API request, returns a Promise.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "param": {
      "description": "The parameter to use in the querystring.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "path": {
      "description": "The `path` parameter supplied by React-Router",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "type": {
      "description": "Which type of autocompleter should be used in the Search",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "autocompleter": {
      "description": "The custom autocompleter to be forwarded to the `Search` component.",
      "type": {
        "name": "object"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/date-range-filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date_range_filter_picker)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js + 29 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js + 8 modules
var tab_panel = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tab-panel/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/segmented-selection/index.js
var segmented_selection = __webpack_require__("../../packages/js/components/src/segmented-selection/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/date-range-filter-picker/compare-periods.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


class ComparePeriods extends react.Component {
  render() {
    const {
      onSelect,
      compare
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsx)(segmented_selection/* default */.A, {
      options: src/* periods */.RE,
      selected: compare,
      onSelect: onSelect,
      name: "compare",
      legend: (0,build_module.__)('compare to', 'woocommerce')
    });
  }
}
/* harmony default export */ const compare_periods = (ComparePeriods);
;
ComparePeriods.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "ComparePeriods",
  "props": {
    "onSelect": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "compare": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/date-range.js + 1 modules
var date_range = __webpack_require__("../../packages/js/components/src/calendar/date-range.js");
// EXTERNAL MODULE: ../../packages/js/components/src/section/header.tsx
var header = __webpack_require__("../../packages/js/components/src/section/header.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/section/section.tsx
var section = __webpack_require__("../../packages/js/components/src/section/section.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
;// ../../packages/js/components/src/date-range-filter-picker/preset-periods.js
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


class PresetPeriods extends react.Component {
  render() {
    const {
      onSelect,
      period
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsx)(segmented_selection/* default */.A, {
      options: (0,lodash.filter)(src/* presetValues */.Ad, preset => preset.value !== 'custom'),
      selected: period,
      onSelect: onSelect,
      name: "period",
      legend: (0,build_module.__)('select a preset period', 'woocommerce')
    });
  }
}
/* harmony default export */ const preset_periods = (PresetPeriods);
;
PresetPeriods.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "PresetPeriods",
  "props": {
    "onSelect": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "period": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/date-range-filter-picker/content.js
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





class DatePickerContent extends react.Component {
  constructor() {
    super();
    this.onTabSelect = this.onTabSelect.bind(this);
    this.controlsRef = (0,react.createRef)();
  }
  onTabSelect(tab) {
    const {
      onUpdate,
      period
    } = this.props;

    /**
     * If the period is `custom` and the user switches tabs to view the presets,
     * then a preset should be selected. This logic selects the default, otherwise
     * `custom` value for period will result in no selection.
     */
    if (tab === 'period' && period === 'custom') {
      onUpdate({
        period: 'today'
      });
    }
  }
  isFutureDate(dateString) {
    return moment_default()().isBefore(moment_default()(dateString), 'day');
  }
  render() {
    const {
      period,
      compare,
      after,
      before,
      onUpdate,
      onClose,
      onSelect,
      isValidSelection,
      resetCustomValues,
      focusedInput,
      afterText,
      beforeText,
      afterError,
      beforeError,
      shortDateFormat,
      shortDateFormatPlaceholder
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
        className: "screen-reader-text",
        tabIndex: "0",
        children: (0,build_module.__)('Select date range and comparison', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(section/* Section */.w, {
        component: false,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
          className: "woocommerce-filters-date__text",
          children: (0,build_module.__)('select a date range', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(tab_panel/* default */.A, {
          tabs: [{
            name: 'period',
            title: (0,build_module.__)('Presets', 'woocommerce'),
            className: 'woocommerce-filters-date__tab'
          }, {
            name: 'custom',
            title: (0,build_module.__)('Custom', 'woocommerce'),
            className: 'woocommerce-filters-date__tab'
          }],
          className: "woocommerce-filters-date__tabs",
          activeClass: "is-active",
          initialTabName: period === 'custom' ? 'custom' : 'period',
          onSelect: this.onTabSelect,
          children: selected => /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
            children: [selected.name === 'period' && /*#__PURE__*/(0,jsx_runtime.jsx)(preset_periods, {
              onSelect: onUpdate,
              period: period
            }), selected.name === 'custom' && /*#__PURE__*/(0,jsx_runtime.jsx)(date_range/* default */.A, {
              after: after,
              before: before,
              onUpdate: onUpdate,
              isInvalidDate: this.isFutureDate,
              focusedInput: focusedInput,
              afterText: afterText,
              beforeText: beforeText,
              afterError: afterError,
              beforeError: beforeError,
              shortDateFormat: shortDateFormat,
              shortDateFormatPlaceholder: shortDateFormatPlaceholder,
              losesFocusTo: this.controlsRef.current
            }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
              className: (0,clsx/* default */.A)('woocommerce-filters-date__content-controls', {
                'is-custom': selected.name === 'custom'
              }),
              ref: this.controlsRef,
              children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
                className: "woocommerce-filters-date__text",
                children: (0,build_module.__)('compare to', 'woocommerce')
              }), /*#__PURE__*/(0,jsx_runtime.jsx)(compare_periods, {
                onSelect: onUpdate,
                compare: compare
              }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
                className: "woocommerce-filters-date__button-group",
                children: [selected.name === 'custom' && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  className: "woocommerce-filters-date__button",
                  isSecondary: true,
                  onClick: resetCustomValues,
                  disabled: !(after || before),
                  children: (0,build_module.__)('Reset', 'woocommerce')
                }), isValidSelection(selected.name) ? /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  className: "woocommerce-filters-date__button",
                  onClick: onSelect(selected.name, onClose),
                  isPrimary: true,
                  children: (0,build_module.__)('Update', 'woocommerce')
                }) : /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
                  className: "woocommerce-filters-date__button",
                  isPrimary: true,
                  disabled: true,
                  children: (0,build_module.__)('Update', 'woocommerce')
                })]
              })]
            })]
          })
        })]
      })]
    });
  }
}
/* harmony default export */ const content = (DatePickerContent);
;
DatePickerContent.__docgenInfo = {
  "description": "",
  "methods": [{
    "name": "onTabSelect",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "tab",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "isFutureDate",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "dateString",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "DatePickerContent",
  "props": {
    "period": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "compare": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "onUpdate": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "onClose": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "onSelect": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "resetCustomValues": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "focusedInput": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "afterText": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "beforeText": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "afterError": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "beforeError": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "shortDateFormat": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "shortDateFormatPlaceholder": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../packages/js/components/src/dropdown-button/index.js
var dropdown_button = __webpack_require__("../../packages/js/components/src/dropdown-button/index.js");
;// ../../packages/js/components/src/date-range-filter-picker/index.js
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */



const shortDateFormatPlaceholder = (0,build_module.__)('MM/DD/YYYY', 'woocommerce');
const shortDateFormat = 'MM/DD/YYYY';

/**
 * Select a range of dates or single dates.
 */
class DateRangeFilterPicker extends react.Component {
  constructor(props) {
    super(props);
    this.state = this.getResetState();
    this.update = this.update.bind(this);
    this.onSelect = this.onSelect.bind(this);
    this.isValidSelection = this.isValidSelection.bind(this);
    this.resetCustomValues = this.resetCustomValues.bind(this);
  }
  formatDate(date, format) {
    if (date && date._isAMomentObject && date.isValid() && typeof date.format === 'function') {
      return date.format(format);
    }
    return '';
  }
  getResetState() {
    const {
      period,
      compare,
      before,
      after
    } = this.props.dateQuery;
    return {
      period,
      compare,
      before,
      after,
      focusedInput: 'startDate',
      afterText: this.formatDate(after, shortDateFormat),
      beforeText: this.formatDate(before, shortDateFormat),
      afterError: null,
      beforeError: null
    };
  }
  update(update) {
    this.setState(update);
  }
  onSelect(selectedTab, onClose) {
    const {
      isoDateFormat,
      onRangeSelect
    } = this.props;
    return event => {
      const {
        period,
        compare,
        after,
        before
      } = this.state;
      const data = {
        period: selectedTab === 'custom' ? 'custom' : period,
        compare
      };
      if (selectedTab === 'custom') {
        data.after = this.formatDate(after, isoDateFormat);
        data.before = this.formatDate(before, isoDateFormat);
      } else {
        data.after = undefined;
        data.before = undefined;
      }
      onRangeSelect(data);
      onClose(event);
    };
  }
  getButtonLabel() {
    const {
      primaryDate,
      secondaryDate
    } = this.props.dateQuery;
    return [`${primaryDate.label} (${primaryDate.range})`, `${(0,build_module.__)('vs.', 'woocommerce')} ${secondaryDate.label} (${secondaryDate.range})`];
  }
  isValidSelection(selectedTab) {
    const {
      compare,
      after,
      before
    } = this.state;
    if (selectedTab === 'custom') {
      return compare && after && before;
    }
    return true;
  }
  resetCustomValues() {
    this.setState({
      after: null,
      before: null,
      focusedInput: 'startDate',
      afterText: '',
      beforeText: '',
      afterError: null,
      beforeError: null
    });
  }
  render() {
    const {
      period,
      compare,
      after,
      before,
      focusedInput,
      afterText,
      beforeText,
      afterError,
      beforeError
    } = this.state;
    const {
      isViewportMobile,
      focusOnMount = true,
      popoverProps = {
        inline: true
      }
    } = this.props;
    if (!popoverProps.placement) {
      popoverProps.placement = 'bottom';
    }
    const contentClasses = (0,clsx/* default */.A)('woocommerce-filters-date__content', {
      'is-mobile': isViewportMobile
    });
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-filters-filter",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "woocommerce-filters-label",
        children: (0,build_module.__)('Date range', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown/* default */.A, {
        contentClassName: contentClasses,
        expandOnMobile: true,
        focusOnMount: focusOnMount,
        popoverProps: popoverProps,
        renderToggle: ({
          isOpen,
          onToggle
        }) => /*#__PURE__*/(0,jsx_runtime.jsx)(dropdown_button/* default */.A, {
          onClick: onToggle,
          isOpen: isOpen,
          labels: this.getButtonLabel()
        }),
        renderContent: ({
          onClose
        }) => /*#__PURE__*/(0,jsx_runtime.jsx)(content, {
          period: period,
          compare: compare,
          after: after,
          before: before,
          onUpdate: this.update,
          onClose: onClose,
          onSelect: this.onSelect,
          isValidSelection: this.isValidSelection,
          resetCustomValues: this.resetCustomValues,
          focusedInput: focusedInput,
          afterText: afterText,
          beforeText: beforeText,
          afterError: afterError,
          beforeError: beforeError,
          shortDateFormat: shortDateFormat,
          shortDateFormatPlaceholder: shortDateFormatPlaceholder
        })
      })]
    });
  }
}
/* harmony default export */ const date_range_filter_picker = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium'
})(DateRangeFilterPicker));
;
DateRangeFilterPicker.__docgenInfo = {
  "description": "Select a range of dates or single dates.",
  "methods": [{
    "name": "formatDate",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "date",
      "optional": false,
      "type": null
    }, {
      "name": "format",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getResetState",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "update",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "update",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onSelect",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selectedTab",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getButtonLabel",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "isValidSelection",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selectedTab",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "resetCustomValues",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "DateRangeFilterPicker",
  "props": {
    "onRangeSelect": {
      "description": "Callback called when selection is made.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "dateQuery": {
      "description": "The date query string represented in object form.",
      "type": {
        "name": "shape",
        "value": {
          "period": {
            "name": "string",
            "required": true
          },
          "compare": {
            "name": "string",
            "required": true
          },
          "before": {
            "name": "object",
            "required": false
          },
          "after": {
            "name": "object",
            "required": false
          },
          "primaryDate": {
            "name": "shape",
            "value": {
              "label": {
                "name": "string",
                "required": true
              },
              "range": {
                "name": "string",
                "required": true
              }
            },
            "required": true
          },
          "secondaryDate": {
            "name": "shape",
            "value": {
              "label": {
                "name": "string",
                "required": true
              },
              "range": {
                "name": "string",
                "required": true
              }
            },
            "required": true
          }
        }
      },
      "required": true
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/dropdown-button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */






/**
 * A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays
 * single or multiple lines rendered as `<span/>` elements.
 *
 * @param {Object} props Props passed to component.
 * @return {Object} -
 */

const DropdownButton = props => {
  const {
    labels,
    isOpen,
    ...otherProps
  } = props;
  const buttonClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-dropdown-button', {
    'is-open': isOpen,
    'is-multi-line': labels.length > 1
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
    className: buttonClasses,
    "aria-expanded": isOpen,
    ...otherProps,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: "woocommerce-dropdown-button__labels",
      children: labels.map((label, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        children: (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__/* .decodeEntities */ .S)(label)
      }, i))
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DropdownButton);
;
DropdownButton.__docgenInfo = {
  "description": "A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays\nsingle or multiple lines rendered as `<span/>` elements.\n\n@param {Object} props Props passed to component.\n@return {Object} -",
  "methods": [],
  "displayName": "DropdownButton",
  "props": {
    "labels": {
      "description": "An array of elements to be rendered as the content of the button.",
      "type": {
        "name": "array"
      },
      "required": true
    },
    "isOpen": {
      "description": "Boolean describing if the dropdown in open or not.",
      "type": {
        "name": "bool"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export DEFAULT_FILTER */
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var _animation_slider__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../packages/js/components/src/animation-slider/index.js");
/* harmony import */ var _dropdown_button__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../packages/js/components/src/dropdown-button/index.js");
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../packages/js/components/src/search/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */










/**
 * Internal dependencies
 */




const DEFAULT_FILTER = 'all';

/**
 * Modify a url query parameter via a dropdown selection of configurable options.
 * This component manipulates the `filter` query parameter.
 */
class FilterPicker extends _wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Component {
  constructor(props) {
    super(props);
    const selectedFilter = this.getFilter();
    this.state = {
      nav: selectedFilter.path || [],
      animate: null,
      selectedTag: null
    };
    this.selectSubFilter = this.selectSubFilter.bind(this);
    this.getVisibleFilters = this.getVisibleFilters.bind(this);
    this.updateSelectedTag = this.updateSelectedTag.bind(this);
    this.onTagChange = this.onTagChange.bind(this);
    this.onContentMount = this.onContentMount.bind(this);
    this.goBack = this.goBack.bind(this);
    if (selectedFilter.settings && selectedFilter.settings.getLabels) {
      const {
        query
      } = this.props;
      const {
        param: filterParam,
        getLabels
      } = selectedFilter.settings;
      getLabels(query[filterParam], query).then(this.updateSelectedTag);
    }
  }
  componentDidUpdate({
    query: prevQuery
  }) {
    const {
      query: nextQuery,
      config
    } = this.props;
    if (prevQuery[config.param] !== nextQuery[[config.param]]) {
      const selectedFilter = this.getFilter();
      if (selectedFilter && selectedFilter.component === 'Search') {
        /* eslint-disable react/no-did-update-set-state */
        this.setState({
          nav: selectedFilter.path || []
        });
        /* eslint-enable react/no-did-update-set-state */
        const {
          param: filterParam,
          getLabels
        } = selectedFilter.settings;
        getLabels(nextQuery[filterParam], nextQuery).then(this.updateSelectedTag);
      }
    }
  }
  updateSelectedTag(tags) {
    this.setState({
      selectedTag: tags[0]
    });
  }
  getFilter(value) {
    const {
      config,
      query
    } = this.props;
    const allFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .flattenFilters */ .SI)(config.filters);
    value = value || query[config.param] || config.defaultValue || DEFAULT_FILTER;
    return (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(allFilters, {
      value
    }) || {};
  }
  getButtonLabel(selectedFilter) {
    if (selectedFilter.component === 'Search') {
      const {
        selectedTag
      } = this.state;
      return [selectedTag && selectedTag.label, (0,lodash__WEBPACK_IMPORTED_MODULE_1__.get)(selectedFilter, 'settings.labels.button')];
    }
    return selectedFilter ? [selectedFilter.label] : [];
  }
  getVisibleFilters(filters, nav) {
    if (nav.length === 0) {
      return filters;
    }
    const value = nav[0];
    const nextFilters = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(filters, {
      value
    });
    return this.getVisibleFilters(nextFilters && nextFilters.subFilters, nav.slice(1));
  }
  selectSubFilter(value) {
    // Add the value onto the nav path
    this.setState(prevState => ({
      nav: [...prevState.nav, value],
      animate: 'left'
    }));
  }
  goBack() {
    // Remove the last item from the nav path
    this.setState(prevState => ({
      nav: prevState.nav.slice(0, -1),
      animate: 'right'
    }));
  }
  getAllFilterParams() {
    const {
      config
    } = this.props;
    const params = [];
    const getParam = filters => {
      filters.forEach(filter => {
        if (filter.settings && !params.includes(filter.settings.param)) {
          params.push(filter.settings.param);
        }
        if (filter.subFilters) {
          getParam(filter.subFilters);
        }
      });
    };
    getParam(config.filters);
    return params;
  }
  update(value, additionalQueries = {}) {
    const {
      path,
      query,
      config,
      onFilterSelect,
      advancedFilters
    } = this.props;
    let update = {
      [config.param]: (config.defaultValue || DEFAULT_FILTER) === value ? undefined : value,
      ...additionalQueries
    };
    // Keep any url parameters as designated by the config
    config.staticParams.forEach(param => {
      update[param] = query[param];
    });

    // Remove all of this filter's params not associated with the update while
    // leaving any other params from any other filter an extension may have added.
    this.getAllFilterParams().forEach(param => {
      if (!update[param]) {
        // Explicitly give value of undefined so it can be removed from the query.
        update[param] = undefined;
      }
    });

    // If the main filter is being set to anything but advanced, remove any advancedFilters.
    if (config.param === 'filter' && value !== 'advanced') {
      const resetAdvancedFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .getQueryFromActiveFilters */ .Sz)([], query, advancedFilters.filters || {});
      update = {
        ...update,
        ...resetAdvancedFilters
      };
    }
    (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .updateQueryString */ .Ze)(update, path, query);
    onFilterSelect(update);
  }
  onTagChange(filter, onClose, config, tags) {
    const tag = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.last)(tags);
    const {
      value,
      settings
    } = filter;
    const {
      param: filterParam
    } = settings;
    if (tag) {
      this.update(value, {
        [filterParam]: tag.key
      });
      onClose();
    } else {
      this.update(config.defaultValue || DEFAULT_FILTER);
    }
    this.updateSelectedTag([tag]);
  }
  renderButton(filter, onClose, config) {
    if (filter.component) {
      const {
        type,
        labels,
        autocompleter
      } = filter.settings;
      const persistedFilter = this.getFilter();
      const selectedTag = persistedFilter.value === filter.value ? this.state.selectedTag : null;
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_search__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
        autocompleter: autocompleter,
        className: "woocommerce-filters-filter__search",
        type: type,
        placeholder: labels.placeholder,
        selected: selectedTag ? [selectedTag] : [],
        onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partial)(this.onTagChange, filter, onClose, config),
        inlineTags: true,
        staticResults: true
      });
    }
    const selectFilter = event => {
      onClose(event);
      this.update(filter.value, filter.query || {});
      this.setState({
        selectedTag: null
      });
    };
    const selectSubFilter = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partial)(this.selectSubFilter, filter.value);
    const selectedFilter = this.getFilter();
    const buttonIsSelected = selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_1__.includes)(selectedFilter.path, filter.value);
    const onClick = event => {
      if (buttonIsSelected) {
        // Don't navigate if the button is already selected.
        onClose(event);
        return;
      }
      if (filter.subFilters) {
        selectSubFilter(event);
        return;
      }
      selectFilter(event);
    };
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-filters-filter__button",
      onClick: onClick,
      children: filter.label
    });
  }
  onContentMount(content) {
    const {
      nav
    } = this.state;
    const parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
    const focusableIndex = parentFilter ? 1 : 0;
    const focusable = _wordpress_dom__WEBPACK_IMPORTED_MODULE_7__/* .focus */ .XC.tabbable.find(content)[focusableIndex];
    setTimeout(() => {
      focusable.focus();
    }, 0);
  }
  render() {
    const {
      config
    } = this.props;
    const {
      nav,
      animate
    } = this.state;
    const visibleFilters = this.getVisibleFilters(config.filters, nav);
    const parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
    const selectedFilter = this.getFilter();
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      className: "woocommerce-filters-filter",
      children: [config.label && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "woocommerce-filters-label",
        children: config.label
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
        contentClassName: "woocommerce-filters-filter__content",
        popoverProps: {
          placement: 'bottom'
        },
        expandOnMobile: true,
        headerTitle: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('filter report to show:', 'woocommerce'),
        renderToggle: ({
          isOpen,
          onToggle
        }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_dropdown_button__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A, {
          onClick: onToggle,
          isOpen: isOpen,
          labels: this.getButtonLabel(selectedFilter)
        }),
        renderContent: ({
          onClose
        }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_animation_slider__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A, {
          animationKey: nav,
          animate: animate,
          onExited: this.onContentMount,
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("ul", {
            className: "woocommerce-filters-filter__content-list",
            children: [parentFilter && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
              className: "woocommerce-filters-filter__content-list-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
                className: "woocommerce-filters-filter__button",
                onClick: this.goBack,
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A, {
                  icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A
                }), parentFilter.label]
              })
            }), visibleFilters.map(filter => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
              className: (0,clsx__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)('woocommerce-filters-filter__content-list-item', {
                'is-selected': selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_1__.includes)(selectedFilter.path, filter.value)
              }),
              children: this.renderButton(filter, onClose, config)
            }, filter.value))]
          })
        })
      })]
    });
  }
}
FilterPicker.defaultProps = {
  query: {},
  onFilterSelect: () => {}
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FilterPicker);
;
FilterPicker.__docgenInfo = {
  "description": "Modify a url query parameter via a dropdown selection of configurable options.\nThis component manipulates the `filter` query parameter.",
  "methods": [{
    "name": "updateSelectedTag",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "tags",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getFilter",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getButtonLabel",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selectedFilter",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getVisibleFilters",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filters",
      "optional": false,
      "type": null
    }, {
      "name": "nav",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "selectSubFilter",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "goBack",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getAllFilterParams",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "update",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }, {
      "name": "additionalQueries",
      "optional": true,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onTagChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filter",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }, {
      "name": "config",
      "optional": false,
      "type": null
    }, {
      "name": "tags",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "renderButton",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filter",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }, {
      "name": "config",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onContentMount",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "content",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "FilterPicker",
  "props": {
    "query": {
      "defaultValue": {
        "value": "{}",
        "computed": false
      },
      "description": "The query string represented in object form.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "onFilterSelect": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "Function to be called after filter selection.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "config": {
      "description": "An array of filters and subFilters to construct the menu.",
      "type": {
        "name": "shape",
        "value": {
          "label": {
            "name": "string",
            "description": "A label above the filter selector.",
            "required": false
          },
          "staticParams": {
            "name": "array",
            "description": "Url parameters to persist when selecting a new filter.",
            "required": true
          },
          "param": {
            "name": "string",
            "description": "The url parameter this filter will modify.",
            "required": true
          },
          "defaultValue": {
            "name": "string",
            "description": "The default parameter value to use instead of 'all'.",
            "required": false
          },
          "showFilters": {
            "name": "func",
            "description": "Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.",
            "required": true
          },
          "filters": {
            "name": "arrayOf",
            "value": {
              "name": "shape",
              "value": {
                "chartMode": {
                  "name": "enum",
                  "value": [{
                    "value": "'item-comparison'",
                    "computed": false
                  }, {
                    "value": "'time-comparison'",
                    "computed": false
                  }],
                  "description": "The chart display mode to use for charts displayed when this filter is active.",
                  "required": false
                },
                "component": {
                  "name": "string",
                  "description": "A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.",
                  "required": false
                },
                "label": {
                  "name": "string",
                  "description": "The label for this filter. Optional only for custom component filters.",
                  "required": false
                },
                "path": {
                  "name": "string",
                  "description": "An array representing the \"path\" to this filter, if nested.",
                  "required": false
                },
                "subFilters": {
                  "name": "array",
                  "description": "An array of more filter objects that act as \"children\" to this item.\nThis set of filters is shown if the parent filter is clicked.",
                  "required": false
                },
                "value": {
                  "name": "string",
                  "description": "The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.",
                  "required": true
                }
              }
            },
            "description": "An array of filter a user can select.",
            "required": false
          }
        }
      },
      "required": true
    },
    "path": {
      "description": "The `path` parameter supplied by React-Router.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "advancedFilters": {
      "description": "Advanced Filters configuration object.",
      "type": {
        "name": "object"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/segmented-selection/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * Create a panel of styled selectable options rendering stylized checkboxes and labels
 */

class SegmentedSelection extends _wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Component {
  render() {
    const {
      className,
      options,
      selected,
      onSelect,
      name,
      legend
    } = this.props;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("fieldset", {
      className: "woocommerce-segmented-selection",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("legend", {
        className: "screen-reader-text",
        children: legend
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(className, 'woocommerce-segmented-selection__container'),
        children: options.map(({
          value,
          label
        }) => {
          if (!value || !label) {
            return null;
          }
          const id = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.uniqueId)(`${value}_`);
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
            className: "woocommerce-segmented-selection__item",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
              className: "woocommerce-segmented-selection__input",
              type: "radio",
              name: name,
              id: id,
              checked: selected === value,
              onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(onSelect, {
                [name]: value
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("label", {
              htmlFor: id,
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
                className: "woocommerce-segmented-selection__label",
                children: label
              })
            })]
          }, value);
        })
      })]
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SegmentedSelection);
;
SegmentedSelection.__docgenInfo = {
  "description": "Create a panel of styled selectable options rendering stylized checkboxes and labels",
  "methods": [],
  "displayName": "SegmentedSelection",
  "props": {
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "options": {
      "description": "An Array of options to render. The array needs to be composed of objects with properties `label` and `value`.",
      "type": {
        "name": "arrayOf",
        "value": {
          "name": "shape",
          "value": {
            "value": {
              "name": "string",
              "required": true
            },
            "label": {
              "name": "string",
              "required": true
            }
          }
        }
      },
      "required": true
    },
    "selected": {
      "description": "Value of selected item.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onSelect": {
      "description": "Callback to be executed after selection",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "name": {
      "description": "This will be the key in the key and value arguments supplied to `onSelect`.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "legend": {
      "description": "Create a legend visible to screen readers.",
      "type": {
        "name": "string"
      },
      "required": true
    }
  }
};

/***/ }),

/***/ "../../packages/js/navigation/src/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  SI: () => (/* reexport */ flattenFilters),
  Q$: () => (/* reexport */ getActiveFiltersFromQuery),
  Am: () => (/* reexport */ getDefaultOptionValue),
  JK: () => (/* reexport */ history_getHistory),
  DF: () => (/* binding */ getIdsFromQuery),
  Gy: () => (/* reexport */ getNewPath),
  $Z: () => (/* reexport */ url_getQuery),
  Sz: () => (/* reexport */ getQueryFromActiveFilters),
  Ze: () => (/* binding */ updateQueryString)
});

// UNUSED EXPORTS: addHistoryListener, getPath, getPersistedQuery, getQueryExcludedScreens, getQueryExcludedScreensUrlUpdate, getScreenFromPath, getSearchWords, getSetOfIdsFromQuery, getUrlKey, isWCAdmin, navigateTo, onQueryChange, parseAdminUrl, pathIsExcluded, useConfirmUnsavedChanges, useQuery

// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.33.1/node_modules/@wordpress/hooks/build-module/index.js + 10 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.33.1/node_modules/@wordpress/hooks/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/history@5.3.0/node_modules/history/index.js
var node_modules_history = __webpack_require__("../../node_modules/.pnpm/history@5.3.0/node_modules/history/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/qs@6.15.1/node_modules/qs/lib/index.js
var lib = __webpack_require__("../../node_modules/.pnpm/qs@6.15.1/node_modules/qs/lib/index.js");
;// ../../packages/js/navigation/src/history.ts
/**
 * External dependencies
 */



// See https://github.com/ReactTraining/react-router/blob/master/FAQ.md#how-do-i-access-the-history-object-outside-of-components
// ^ This is a bit outdated but there's no newer documentation - the replacement for this is to use <unstable_HistoryRouter /> https://reactrouter.com/docs/en/v6/routers/history-router

/**
 * Extension of history.BrowserHistory but also adds { pathname: string } to the location object.
 */

let _history;

/**
 * Recreate `history` to coerce React Router into accepting path arguments found in query
 * parameter `path`, allowing a url hash to be avoided. Since hash portions of the url are
 * not sent server side, full route information can be detected by the server.
 *
 * `<Router />` and `<Switch />` components use `history.location()` to match a url with a route.
 * Since they don't parse query arguments, recreate `get location` to return a `pathname` with the
 * query path argument's value.
 *
 * In react-router v6, { basename } is no longer a parameter in createBrowserHistory(), and the
 * replacement is to use basename in the <Route> component.
 *
 * @return {Object} React-router history object with `get location` modified.
 */
function history_getHistory() {
  if (!_history) {
    const browserHistory = (0,node_modules_history/* createBrowserHistory */.zR)();
    let locationStack = [browserHistory.location];
    const updateNextLocationStack = (action, location) => {
      switch (action) {
        case 'POP':
          locationStack = locationStack.slice(0, locationStack.length - 1);
          break;
        case 'PUSH':
          locationStack = [...locationStack, location];
          break;
        case 'REPLACE':
          locationStack = [...locationStack.slice(0, locationStack.length - 1), location];
          break;
      }
    };
    _history = {
      get action() {
        return browserHistory.action;
      },
      get location() {
        const {
          location
        } = browserHistory;
        const query = (0,lib.parse)(location.search.substring(1));
        let pathname;
        if (query && typeof query.path === 'string') {
          pathname = query.path;
        } else if (query && query.path && typeof query.path !== 'string') {
          // this branch was added when converting to TS as it is technically possible for a query.path to not be a string.
          // eslint-disable-next-line no-console
          console.warn(`Query path parameter should be a string but instead was: ${query.path}, undefined behaviour may occur.`);
          pathname = query.path; // ts override only, no coercion going on
        } else {
          pathname = '/';
        }
        return {
          ...location,
          pathname
        };
      },
      get __experimentalLocationStack() {
        return [...locationStack];
      },
      createHref: browserHistory.createHref,
      push: browserHistory.push,
      replace: browserHistory.replace,
      go: browserHistory.go,
      back: browserHistory.back,
      forward: browserHistory.forward,
      block: browserHistory.block,
      listen(listener) {
        return browserHistory.listen(() => {
          listener({
            action: this.action,
            location: this.location
          });
        });
      }
    };
    browserHistory.listen(() => updateNextLocationStack(_history.action, _history.location));
  }
  return _history;
}

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.33.1/node_modules/@wordpress/url/build-module/add-query-args.js + 5 modules
var add_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.33.1/node_modules/@wordpress/url/build-module/add-query-args.js");
;// ../../packages/js/navigation/src/url.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


/**
 * Get the current path from history.
 *
 * @return {string}  Current path.
 */
const url_getPath = () => history_getHistory().location.pathname;

/**
 * Get the current query string, parsed into an object, from history.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
function url_getQuery() {
  const search = history_getHistory().location.search;
  if (search.length) {
    return (0,lib.parse)(search.substring(1)) || {};
  }
  return {};
}

/**
 * Return a URL with set query parameters.
 *
 * @param {Object} query        object of params to be updated.
 * @param {string} path         Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 * @param {string} page         Page key (defaults to "wc-admin")
 * @return {string}  Updated URL merging query params into existing params.
 */
function getNewPath(query, path = url_getPath(), currentQuery = url_getQuery(), page = 'wc-admin') {
  const args = {
    page,
    ...currentQuery,
    ...query
  };
  if (path !== '/') {
    args.path = path;
  }
  return (0,add_query_args/* addQueryArgs */.F)('admin.php', args);
}

/**
 * Returns a parsed object for an absolute or relative admin URL.
 *
 * @param {*} url - the url to test.
 * @return {URL} - the URL object of the given url.
 */
const url_parseAdminUrl = url => {
  if (url.startsWith('http')) {
    return new URL(url);
  }
  return /^\/?[a-z0-9]+.php/i.test(url) ? new URL(`${window.wcSettings.adminUrl}${url}`) : new URL(getAdminLink(getNewPath({}, url, {})));
};
;// ../../packages/js/navigation/src/filters.js
/**
 * External dependencies
 */


/**
 * Get the url query key from the filter key and rule.
 *
 * @param {string} key  - filter key.
 * @param {string} rule - filter rule.
 * @return {string} - url query key.
 */
function getUrlKey(key, rule) {
  if (rule && rule.length) {
    return `${key}_${rule}`;
  }
  return key;
}

/**
 * Collapse an array of filter values with subFilters into a 1-dimensional array.
 *
 * @param {Array} filters Set of filters with possible subfilters.
 * @return {Array} Flattened array of all filters.
 */
function flattenFilters(filters) {
  const allFilters = [];
  filters.forEach(f => {
    if (!f.subFilters) {
      allFilters.push(f);
    } else {
      allFilters.push((0,lodash.omit)(f, 'subFilters'));
      const subFilters = flattenFilters(f.subFilters);
      allFilters.push(...subFilters);
    }
  });
  return allFilters;
}

/**
 * Describe activeFilter object.
 *
 * @typedef {Object} activeFilter
 * @property {string} key    - filter key.
 * @property {string} [rule] - a modifying rule for a filter, eg 'includes' or 'is_not'.
 * @property {string} value  - filter value(s).
 */

/**
 * Given a query object, return an array of activeFilters, if any.
 *
 * @param {Object} query  - query object
 * @param {Object} config - config object
 * @return {Array} - array of activeFilters
 */
function getActiveFiltersFromQuery(query, config) {
  return Object.keys(config).reduce((activeFilters, configKey) => {
    const filter = config[configKey];
    if (filter.rules) {
      // Get all rules found in the query string.
      const matches = filter.rules.filter(rule => query.hasOwnProperty(getUrlKey(configKey, rule.value)));
      if (matches.length) {
        if (filter.allowMultiple) {
          // If rules were found in the query string, and this filter supports
          // multiple instances, add all matches to the active filters array.
          matches.forEach(match => {
            const value = query[getUrlKey(configKey, match.value)];
            value.forEach(filterValue => {
              activeFilters.push({
                key: configKey,
                rule: match.value,
                value: filterValue
              });
            });
          });
        } else {
          // If the filter is a single instance, just process the first rule match.
          const value = query[getUrlKey(configKey, matches[0].value)];
          activeFilters.push({
            key: configKey,
            rule: matches[0].value,
            value
          });
        }
      }
    } else if (query[configKey]) {
      // If the filter doesn't have rules, but allows multiples.
      if (filter.allowMultiple) {
        const value = query[configKey];
        value.forEach(filterValue => {
          activeFilters.push({
            key: configKey,
            value: filterValue
          });
        });
      } else {
        // Filter with no rules and only one instance.
        activeFilters.push({
          key: configKey,
          value: query[configKey]
        });
      }
    }
    return activeFilters;
  }, []);
}

/**
 * Get the default option's value from the configuration object for a given filter. The first
 * option is used as default if no `defaultOption` is provided.
 *
 * @param {Object} config  - a filter config object.
 * @param {Array}  options - select options.
 * @return {string|undefined}  - the value of the default option.
 */
function getDefaultOptionValue(config, options) {
  const {
    defaultOption
  } = config.input;
  if (config.input.defaultOption) {
    const option = (0,lodash.find)(options, {
      value: defaultOption
    });
    if (!option) {
      /* eslint-disable no-console */
      console.warn(`invalid defaultOption ${defaultOption} supplied to ${config.labels.add}`);
      /* eslint-enable */
      return undefined;
    }
    return option.value;
  }
  return (0,lodash.get)(options, [0, 'value']);
}

/**
 * Given activeFilters, create a new query object to update the url. Use previousFilters to
 * Remove unused params.
 *
 * @param {Array}  activeFilters - Array of activeFilters shown in the UI
 * @param {Object} query         - the current url query object
 * @param {Object} config        - config object
 * @return {Object} - query object representing the new parameters
 */
function getQueryFromActiveFilters(activeFilters, query, config) {
  const previousFilters = getActiveFiltersFromQuery(query, config);
  const previousData = previousFilters.reduce((data, filter) => {
    data[getUrlKey(filter.key, filter.rule)] = undefined;
    return data;
  }, {});
  const nextData = activeFilters.reduce((data, filter) => {
    if (filter.rule === 'between' && (!Array.isArray(filter.value) || filter.value.some(value => !value))) {
      return data;
    }
    if (filter.value) {
      const urlKey = getUrlKey(filter.key, filter.rule);
      if (config[filter.key] && config[filter.key].allowMultiple) {
        if (!data.hasOwnProperty(urlKey)) {
          data[urlKey] = [];
        }
        data[urlKey].push(filter.value);
      } else {
        data[urlKey] = filter.value;
      }
    }
    return data;
  }, {});
  return {
    ...previousData,
    ...nextData
  };
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
;// ../../packages/js/navigation/src/hooks/use-confirm-unsaved-changes.ts
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const useConfirmUnsavedChanges = (hasUnsavedChanges, shouldConfirm, message) => {
  const confirmMessage = useMemo(() => message ?? __('Changes you made may not be saved.', 'woocommerce'), [message]);
  const history = getHistory();

  // This effect prevent react router from navigate and show
  // a confirmation message. It's a work around to beforeunload
  // because react router does not triggers that event.
  useEffect(() => {
    if (hasUnsavedChanges) {
      const push = history.push;
      history.push = (...args) => {
        const fromUrl = history.location;
        const toUrl = parseAdminUrl(args[0]);
        if (typeof shouldConfirm === 'function' && !shouldConfirm(toUrl, fromUrl)) {
          push(...args);
          return;
        }

        /* eslint-disable-next-line no-alert */
        const result = window.confirm(confirmMessage);
        if (result !== false) {
          push(...args);
        }
      };
      return () => {
        history.push = push;
      };
    }
  }, [history, hasUnsavedChanges, confirmMessage]);

  // This effect listens to the native beforeunload event to show
  // a confirmation message; note that the message shown is
  // a generic browser-specified string; not the custom one shown
  // when using react router.
  useEffect(() => {
    if (hasUnsavedChanges) {
      function onBeforeUnload(event) {
        event.preventDefault();
        return event.returnValue = confirmMessage;
      }
      window.addEventListener('beforeunload', onBeforeUnload, {
        capture: true
      });
      return () => {
        window.removeEventListener('beforeunload', onBeforeUnload, {
          capture: true
        });
      };
    }
  }, [hasUnsavedChanges, confirmMessage]);
};
;// ../../packages/js/navigation/src/index.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



// Expose history so all uses get the same history object.


// Export all filter utilities



// Export all hooks

const TIME_EXCLUDED_SCREENS_FILTER = 'woocommerce_admin_time_excluded_screens';
const NAVIGATION_UPDATE_EXCLUDED_SCREENS_FILTER = 'woocommerce_admin_nav_update_excluded_screens';

/**
 * Gets query parameters that should persist between screens or updates
 * to reports, such as filtering.
 *
 * @param {Object} query Query containing the parameters.
 * @return {Object} Object containing the persisted queries.
 */
const getPersistedQuery = (query = getQuery()) => {
  /**
   * Filter persisted queries. These query parameters remain in the url when other parameters are updated.
   *
   * @filter woocommerce_admin_persisted_queries
   * @param {Array.<string>} persistedQueries Array of persisted queries.
   */
  const params = applyFilters('woocommerce_admin_persisted_queries', ['period', 'compare', 'before', 'after', 'interval', 'type']);
  return pick(query, params);
};

/**
 * Get array of screens that should ignore persisted queries
 *
 * @return {Array} Array containing list of screens
 */
const getQueryExcludedScreens = () => applyFilters(TIME_EXCLUDED_SCREENS_FILTER, ['stock', 'settings', 'customers', 'homescreen']);

/**
 * Get array of screens that should ignore nav menu URL updates.
 *
 * @return {Array} Array containing list of screens
 */
const getQueryExcludedScreensUrlUpdate = () => applyFilters(NAVIGATION_UPDATE_EXCLUDED_SCREENS_FILTER, ['extensions']);

/**
 * Retrieve a string 'name' representing the current screen
 *
 * @param {Object} path Path to resolve, default to current
 * @return {string} Screen name
 */
const getScreenFromPath = (path = getPath()) => {
  return path === '/' ? 'homescreen' : path.replace('/analytics', '').replace('/', '');
};

/**
 * Get an array of IDs from a comma-separated query parameter.
 *
 * @param {string} [queryString=''] string value extracted from URL.
 * @return {Set<number>} List of IDs converted to a set of integers.
 */
function getSetOfIdsFromQuery(queryString = '') {
  return new Set(
  // Return only unique ids.
  queryString.split(',').map(id => parseInt(id, 10)).filter(id => !isNaN(id)));
}

/**
 * Updates the query parameters of the current page.
 *
 * @param {Object} query        object of params to be updated.
 * @param {string} path         Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 * @param {string} page         Page key (defaults to "wc-admin")
 */
function updateQueryString(query, path = url_getPath(), currentQuery = url_getQuery(), page = 'wc-admin') {
  const newPath = getNewPath(query, path, currentQuery, page);
  history_getHistory().push(newPath);
}

/**
 * Adds a listener that runs on history change.
 *
 * @param {Function} listener Listener to add on history change.
 * @return {Function} Function to remove listeners.
 */
const addHistoryListener = listener => {
  // Monkey patch pushState to allow trigger the pushstate event listener.

  window.wcNavigation = window.wcNavigation ?? {};
  if (!window.wcNavigation.historyPatched) {
    (history => {
      const pushState = history.pushState;
      const replaceState = history.replaceState;
      history.pushState = function (state) {
        const pushStateEvent = new CustomEvent('pushstate', {
          state
        });
        window.dispatchEvent(pushStateEvent);
        return pushState.apply(history, arguments);
      };
      history.replaceState = function (state) {
        const replaceStateEvent = new CustomEvent('replacestate', {
          state
        });
        window.dispatchEvent(replaceStateEvent);
        return replaceState.apply(history, arguments);
      };
      window.wcNavigation.historyPatched = true;
    })(window.history);
  }
  window.addEventListener('popstate', listener);
  window.addEventListener('pushstate', listener);
  window.addEventListener('replacestate', listener);
  return () => {
    window.removeEventListener('popstate', listener);
    window.removeEventListener('pushstate', listener);
    window.removeEventListener('replacestate', listener);
  };
};

/**
 * Given a path, return whether it is an excluded screen
 *
 * @param {Object} path Path to check
 *
 * @return {boolean} Boolean representing whether path is excluded
 */
const pathIsExcluded = path => getQueryExcludedScreens().includes(getScreenFromPath(path));

/**
 * Get an array of IDs from a comma-separated query parameter.
 *
 * @param {string} [queryString=''] string value extracted from URL.
 * @return {Array<number>} List of IDs converted to an array of unique integers.
 */
function getIdsFromQuery(queryString = '') {
  return [...getSetOfIdsFromQuery(queryString)];
}

/**
 * Get an array of searched words given a query.
 *
 * @param {Object} query Query object.
 * @return {Array} List of search words.
 */
function getSearchWords(query = getQuery()) {
  if (typeof query !== 'object') {
    throw new Error('Invalid parameter passed to getSearchWords, it expects an object or no parameters.');
  }
  const {
    search
  } = query;
  if (!search) {
    return [];
  }
  if (typeof search !== 'string') {
    throw new Error("Invalid 'search' type. getSearchWords expects query's 'search' property to be a string.");
  }
  return search.split(',').map(searchWord => searchWord.replace('%2C', ','));
}

/**
 * Like getQuery but in useHook format for easy usage in React functional components
 *
 * @return {Record<string, string>} Current query object, defaults to empty object.
 */
const useQuery = () => {
  const [queryState, setQueryState] = useState({});
  const [locationChanged, setLocationChanged] = useState(true);
  useLayoutEffect(() => {
    return addHistoryListener(() => {
      setLocationChanged(true);
    });
  }, []);
  useEffect(() => {
    if (locationChanged) {
      const query = getQuery();
      setQueryState(query);
      setLocationChanged(false);
    }
  }, [locationChanged]);
  return queryState;
};

/**
 * This function returns an event handler for the given `param`
 *
 * @param {string} param The parameter in the querystring which should be updated (ex `page`, `per_page`)
 * @param {string} path  Relative path (defaults to current path).
 * @param {string} query object of current query params (defaults to current querystring).
 * @return {Function} A callback which will update `param` to the passed value when called.
 */
function onQueryChange(param, path = getPath(), query = getQuery()) {
  switch (param) {
    case 'sort':
      return (key, dir) => updateQueryString({
        orderby: key,
        order: dir
      }, path, query);
    case 'compare':
      return (key, queryParam, ids) => updateQueryString({
        [queryParam]: `compare-${key}`,
        [key]: ids,
        search: undefined
      }, path, query);
    default:
      return value => updateQueryString({
        [param]: value
      }, path, query);
  }
}

/**
 * Determines if a URL is a WC admin url.
 *
 * @param {*} url - the url to test
 * @return {boolean} true if the url is a wc-admin URL
 */
const isWCAdmin = (url = window.location.href) => {
  return /admin.php\?page=wc-admin/.test(url);
};

/**
 * A utility function that navigates to a page, using a redirect
 * or the router as appropriate.
 *
 * @param {Object} args     - All arguments.
 * @param {string} args.url - Relative path or absolute url to navigate to
 */
const navigateTo = ({
  url
}) => {
  const parsedUrl = parseAdminUrl(url);
  if (isWCAdmin() && isWCAdmin(String(parsedUrl))) {
    window.document.documentElement.scrollTop = 0;
    getHistory().push(`admin.php${parsedUrl.search}`);
    return;
  }
  window.location.href = String(parsedUrl);
};

/***/ }),

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
const Level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(2);

try {
    // @ts-ignore
    Context.displayName = "Context";
    // @ts-ignore
    Context.__docgenInfo = { "description": "Context lets components pass information deep down without explicitly\npassing props.\n\nCreated from {@link createContext}", "displayName": "Context", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/context.tsx#Context"] = { docgenInfo: Context.__docgenInfo, name: "Context", path: "../../packages/js/components/src/section/context.tsx#Context" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/header.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H: () => (/* binding */ H)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
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
  const level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$);
  const Heading = 'h' + Math.min(level, 6);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Heading, {
    ...props
  });
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
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 */
const Section = ({
  component,
  children,
  ...props
}) => {
  const Component = component || 'div';
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Consumer, {
    children: level => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Provider, {
      value: level + 1,
      children: component === false ? children : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Component, {
        ...props,
        children: children
      })
    })
  });
};
try {
    // @ts-ignore
    Section.displayName = "Section";
    // @ts-ignore
    Section.__docgenInfo = { "description": "The section wrapper, used to indicate a sub-section (and change the header level context).", "displayName": "Section", "props": { "component": { "defaultValue": null, "description": "The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component.", "name": "component", "required": false, "type": { "name": "string | false | ComponentType<{ className?: string; }>" } }, "className": { "defaultValue": null, "description": "Optional classname", "name": "className", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "The children inside this section, rendered in the `component`. This increases the context level for the next heading used.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/section.tsx#Section"] = { docgenInfo: Section.__docgenInfo, name: "Section", path: "../../packages/js/components/src/section/section.tsx#Section" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/select-control/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ select_control)
});

// UNUSED EXPORTS: SelectControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js + 1 modules
var with_spoken_messages = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js
var with_focus_outside = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js + 1 modules
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js
var keycodes_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-window@1.8.11_react-d_73e00b938ef46e831e536da690e6cf36/node_modules/react-window/dist/index.esm.js + 1 modules
var index_esm = __webpack_require__("../../node_modules/.pnpm/react-window@1.8.11_react-d_73e00b938ef46e831e536da690e6cf36/node_modules/react-window/dist/index.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/select-control/list.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */

const VirtualOption = ({
  index,
  style,
  data
}) => {
  const {
    options,
    selectedIndex,
    instanceId,
    onSelect,
    getOptionRef
  } = data;
  const option = options[index];
  return /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    ref: getOptionRef(index),
    id: `woocommerce-select-control__option-${instanceId}-${option.key}`,
    role: "option",
    "aria-selected": index === selectedIndex,
    "aria-setsize": options.length,
    "aria-posinset": index + 1,
    disabled: option.isDisabled,
    className: (0,clsx/* default */.A)('woocommerce-select-control__option', {
      'is-selected': index === selectedIndex
    }),
    onClick: () => onSelect(option),
    tabIndex: -1,
    style: style,
    children: option.label
  }, option.key);
};

/**
 * A list box that displays filtered options after search.
 */
class List extends react.Component {
  constructor(props) {
    super(props);
    this.handleKeyDown = this.handleKeyDown.bind(this);
    this.select = this.select.bind(this);
    this.optionRefs = {};
    this.listbox = (0,react.createRef)();
    this.listRef = (0,react.createRef)();
  }
  componentDidUpdate(prevProps) {
    const {
      options,
      selectedIndex,
      virtualScroll
    } = this.props;

    // Remove old option refs to avoid memory leaks.
    if (!(0,lodash.isEqual)(options, prevProps.options)) {
      this.optionRefs = {};
    }
    if (selectedIndex !== prevProps.selectedIndex && (0,lodash.isNumber)(selectedIndex)) {
      if (virtualScroll && this.listRef.current) {
        this.listRef.current.scrollToItem(selectedIndex, 'smart');
      } else {
        this.scrollToOption(selectedIndex);
      }
    }
  }
  getOptionRef(index) {
    if (!this.optionRefs.hasOwnProperty(index)) {
      this.optionRefs[index] = (0,react.createRef)();
    }
    return this.optionRefs[index];
  }
  select(option) {
    const {
      onSelect
    } = this.props;
    if (option.isDisabled) {
      return;
    }
    onSelect(option);
  }
  scrollToOption(index) {
    const listbox = this.listbox.current;
    if (!listbox) {
      return;
    }
    if (listbox.scrollHeight <= listbox.clientHeight) {
      return;
    }
    if (!this.optionRefs[index]) {
      return;
    }
    const option = this.optionRefs[index].current;
    if (!option) {
      // eslint-disable-next-line no-console
      console.warn('Option not found, index:', index);
      return;
    }
    const scrollBottom = listbox.clientHeight + listbox.scrollTop;
    const elementBottom = option.offsetTop + option.offsetHeight;
    if (elementBottom > scrollBottom) {
      listbox.scrollTop = elementBottom - listbox.clientHeight;
    } else if (option.offsetTop < listbox.scrollTop) {
      listbox.scrollTop = option.offsetTop;
    }
  }
  handleKeyDown(event) {
    const {
      decrementSelectedIndex,
      incrementSelectedIndex,
      options,
      onSearch,
      selectedIndex,
      setExpanded
    } = this.props;
    if (options.length === 0) {
      return;
    }
    switch (event.keyCode) {
      case keycodes_build_module.UP:
        decrementSelectedIndex();
        event.preventDefault();
        event.stopPropagation();
        break;
      case keycodes_build_module/* DOWN */.PX:
        incrementSelectedIndex();
        event.preventDefault();
        event.stopPropagation();
        break;
      case keycodes_build_module/* ENTER */.Fm:
        if ((0,lodash.isNumber)(selectedIndex) && options[selectedIndex]) {
          this.select(options[selectedIndex]);
        }
        event.preventDefault();
        event.stopPropagation();
        break;
      case keycodes_build_module/* LEFT */.M3:
      case keycodes_build_module/* RIGHT */.NS:
        setExpanded(false);
        break;
      case keycodes_build_module/* ESCAPE */._f:
        setExpanded(false);
        onSearch(null);
        return;
      case keycodes_build_module/* TAB */.wn:
        if ((0,lodash.isNumber)(selectedIndex) && options[selectedIndex]) {
          this.select(options[selectedIndex]);
        }
        setExpanded(false);
        break;
      default:
    }
  }
  toggleKeyEvents(isListening) {
    const {
      node
    } = this.props;
    if (!node) {
      // eslint-disable-next-line no-console
      console.warn('No node to bind events to.');
      return;
    }

    // This exists because we must capture ENTER key presses before RichText.
    // It seems that react fires the simulated capturing events after the
    // native browser event has already bubbled so we can't stopPropagation
    // and avoid RichText getting the event from TinyMCE, hence we must
    // register a native event handler.
    const handler = isListening ? 'addEventListener' : 'removeEventListener';
    node[handler]('keydown', this.handleKeyDown, true);
  }
  componentDidMount() {
    const {
      selectedIndex
    } = this.props;
    if ((0,lodash.isNumber)(selectedIndex) && selectedIndex > -1) {
      if (this.props.virtualScroll && this.listRef.current) {
        this.listRef.current.scrollToItem(selectedIndex, 'smart');
      } else {
        this.scrollToOption(selectedIndex);
      }
    }
    this.toggleKeyEvents(true);
  }
  componentWillUnmount() {
    this.toggleKeyEvents(false);
  }
  render() {
    const {
      instanceId,
      listboxId,
      options,
      selectedIndex,
      staticList,
      virtualScroll,
      virtualItemHeight = 35,
      virtualListHeight = 300
    } = this.props;
    const listboxClasses = (0,clsx/* default */.A)('woocommerce-select-control__listbox', {
      'is-static': staticList,
      'is-virtual': virtualScroll
    });
    if (virtualScroll) {
      return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        id: listboxId,
        role: "listbox",
        className: listboxClasses,
        tabIndex: -1,
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(index_esm/* FixedSizeList */.Y1, {
          ref: this.listRef,
          height: Math.min(virtualListHeight, options.length * virtualItemHeight),
          width: "100%",
          itemCount: options.length,
          itemSize: virtualItemHeight,
          itemData: {
            options,
            selectedIndex,
            instanceId,
            onSelect: this.select,
            getOptionRef: this.getOptionRef.bind(this)
          },
          children: VirtualOption
        })
      });
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      ref: this.listbox,
      id: listboxId,
      role: "listbox",
      className: listboxClasses,
      tabIndex: -1,
      children: options.map((option, index) => /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        ref: this.getOptionRef(index),
        id: `woocommerce-select-control__option-${instanceId}-${option.key}`,
        role: "option",
        "aria-selected": index === selectedIndex,
        disabled: option.isDisabled,
        className: (0,clsx/* default */.A)('woocommerce-select-control__option', {
          'is-selected': index === selectedIndex
        }),
        onClick: () => this.select(option),
        tabIndex: -1,
        children: option.label
      }, option.key))
    });
  }
}
/* harmony default export */ const list = (List);
try {
    // @ts-ignore
    List.displayName = "List";
    // @ts-ignore
    List.__docgenInfo = { "description": "A list box that displays filtered options after search.", "displayName": "List", "props": { "listboxId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "instanceId", "required": true, "type": { "name": "number" } }, "node": { "defaultValue": null, "description": "Parent node to bind keyboard events to.", "name": "node", "required": true, "type": { "name": "HTMLElement | null" } }, "onSelect": { "defaultValue": null, "description": "Function to execute when an option is selected.", "name": "onSelect", "required": true, "type": { "name": "(option: Option) => void" } }, "options": { "defaultValue": null, "description": "Array of options to display.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "selectedIndex": { "defaultValue": null, "description": "Integer for the currently selected item.", "name": "selectedIndex", "required": true, "type": { "name": "number | null | undefined" } }, "staticList": { "defaultValue": null, "description": "Bool to determine if the list should be positioned absolutely or statically.", "name": "staticList", "required": true, "type": { "name": "boolean" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "onSearch": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "onSearch", "required": true, "type": { "name": "(option: string | null) => void" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the list should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } }, "virtualScroll": { "defaultValue": null, "description": "Enable virtual scrolling for large lists of options.", "name": "virtualScroll", "required": false, "type": { "name": "boolean" } }, "virtualItemHeight": { "defaultValue": null, "description": "Height in pixels for each virtual item.", "name": "virtualItemHeight", "required": false, "type": { "name": "number" } }, "virtualListHeight": { "defaultValue": null, "description": "Maximum height in pixels for the virtualized list.", "name": "virtualListHeight", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/list.tsx#List"] = { docgenInfo: List.__docgenInfo, name: "List", path: "../../packages/js/components/src/select-control/list.tsx#List" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js
var cancel_circle_filled = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js");
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
;// ../../packages/js/components/src/select-control/tags.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


/**
 * A list of tags to display selected items.
 */
class Tags extends react.Component {
  constructor(props) {
    super(props);
    this.removeAll = this.removeAll.bind(this);
    this.removeResult = this.removeResult.bind(this);
  }
  removeAll() {
    const {
      onChange
    } = this.props;
    onChange([]);
  }
  removeResult(key) {
    return () => {
      const {
        selected,
        onChange
      } = this.props;
      if (!(0,lodash.isArray)(selected)) {
        return;
      }
      const i = (0,lodash.findIndex)(selected, {
        key
      });
      onChange([...selected.slice(0, i), ...selected.slice(i + 1)]);
    };
  }
  render() {
    const {
      selected,
      showClearButton
    } = this.props;
    if (!(0,lodash.isArray)(selected) || !selected.length) {
      return null;
    }
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-select-control__tags",
        children: selected.map((item, i) => {
          if (!item.label) {
            return null;
          }
          const screenReaderLabel = (0,build_module/* sprintf */.nv)(/* translators: %1$s: tag label, %2$s: tag number, %3$s: total number of tags */
          (0,build_module.__)('%1$s (%2$s of %3$s)', 'woocommerce'), item.label, (i + 1).toString(), selected.length.toString());
          return /*#__PURE__*/(0,jsx_runtime.jsx)(tag/* default */.A, {
            id: item.key,
            label: item.label
            // @ts-expect-error key is a string or undefined here
            ,
            remove: this.removeResult,
            screenReaderLabel: screenReaderLabel
          }, item.key);
        })
      }), showClearButton && /*#__PURE__*/(0,jsx_runtime.jsxs)(build_module_button/* default */.Ay, {
        className: "woocommerce-select-control__clear",
        isLink: true,
        onClick: this.removeAll,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: cancel_circle_filled/* default */.A,
          className: "clear-icon"
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "screen-reader-text",
          children: (0,build_module.__)('Clear all', 'woocommerce')
        })]
      })]
    });
  }
}
/* harmony default export */ const tags = (Tags);
try {
    // @ts-ignore
    Tags.displayName = "Tags";
    // @ts-ignore
    Tags.__docgenInfo = { "description": "A list of tags to display selected items.", "displayName": "Tags", "props": { "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": true, "type": { "name": "(selected: Option[]) => void" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values. If the label of the selected\nvalue is omitted, the Tag of that value will not be rendered inside the\nsearch box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "showClearButton": { "defaultValue": null, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/tags.tsx#Tags"] = { docgenInfo: Tags.__docgenInfo, name: "Tags", path: "../../packages/js/components/src/select-control/tags.tsx#Tags" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/search.js
var search = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/search.js");
;// ../../packages/js/components/src/select-control/control.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


/**
 * A search control to allow user input to filter the options.
 */
class Control extends react.Component {
  constructor(props) {
    super(props);
    this.state = {
      isActive: false
    };
    this.input = (0,react.createRef)();
    this.updateSearch = this.updateSearch.bind(this);
    this.onFocus = this.onFocus.bind(this);
    this.onBlur = this.onBlur.bind(this);
    this.onKeyDown = this.onKeyDown.bind(this);
  }
  updateSearch(onSearch) {
    return event => {
      onSearch(event.target.value);
    };
  }
  onFocus(onSearch) {
    const {
      isSearchable,
      setExpanded,
      showAllOnFocus,
      updateSearchOptions
    } = this.props;
    return event => {
      this.setState({
        isActive: true
      });
      if (isSearchable && showAllOnFocus) {
        event.target.select();
        updateSearchOptions('');
      } else if (isSearchable) {
        onSearch(event.target.value);
      } else {
        setExpanded(true);
      }
    };
  }
  onBlur() {
    const {
      onBlur
    } = this.props;
    if (typeof onBlur === 'function') {
      onBlur();
    }
    this.setState({
      isActive: false
    });
  }
  onKeyDown(event) {
    const {
      decrementSelectedIndex,
      incrementSelectedIndex,
      selected,
      onChange,
      query,
      setExpanded
    } = this.props;
    if (keycodes_build_module/* BACKSPACE */.G_ === event.keyCode && !query && (0,lodash.isArray)(selected) && selected.length) {
      onChange([...selected.slice(0, -1)]);
    }
    if (keycodes_build_module/* DOWN */.PX === event.keyCode) {
      incrementSelectedIndex();
      setExpanded(true);
      event.preventDefault();
      event.stopPropagation();
    }
    if (keycodes_build_module.UP === event.keyCode) {
      decrementSelectedIndex();
      setExpanded(true);
      event.preventDefault();
      event.stopPropagation();
    }
  }
  renderButton() {
    const {
      multiple,
      selected
    } = this.props;
    if (multiple || !(0,lodash.isArray)(selected) || !selected.length) {
      return null;
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-select-control__control-value",
      children: selected[0].label
    });
  }
  renderInput() {
    const {
      activeId,
      disabled,
      hasTags,
      inlineTags,
      instanceId,
      isExpanded,
      isSearchable,
      listboxId,
      onSearch,
      placeholder,
      searchInputType,
      autoComplete
    } = this.props;
    const {
      isActive
    } = this.state;
    return /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
      autoComplete: autoComplete || 'off',
      className: "woocommerce-select-control__control-input",
      id: `woocommerce-select-control-${instanceId}__control-input`,
      ref: this.input,
      type: isSearchable ? searchInputType : 'button',
      value: this.getInputValue(),
      placeholder: isActive ? placeholder : '',
      onChange: this.updateSearch(onSearch),
      onFocus: this.onFocus(onSearch),
      onBlur: this.onBlur,
      onKeyDown: this.onKeyDown,
      role: "combobox",
      "aria-autocomplete": "list",
      "aria-expanded": isExpanded,
      "aria-haspopup": "true",
      "aria-owns": listboxId,
      "aria-controls": listboxId,
      "aria-activedescendant": activeId,
      "aria-describedby": hasTags && inlineTags ? `search-inline-input-${instanceId}` : undefined,
      disabled: disabled,
      "aria-label": this.props.ariaLabel ?? this.props.label
    });
  }
  getInputValue() {
    const {
      inlineTags,
      isFocused,
      isSearchable,
      multiple,
      query,
      selected
    } = this.props;
    const selectedValue = (0,lodash.isArray)(selected) && selected.length && typeof selected[0].label === 'string' ? selected[0].label : '';

    // Show the selected value for simple select dropdowns.
    if (!multiple && !isFocused && !inlineTags) {
      return selectedValue;
    }

    // Show the search query when focused on searchable controls.
    if (isSearchable && isFocused && query) {
      return query;
    }
    return '';
  }
  render() {
    const {
      className,
      disabled,
      hasTags,
      help,
      inlineTags,
      instanceId,
      isSearchable,
      label,
      query,
      onChange,
      showClearButton
    } = this.props;
    const {
      isActive
    } = this.state;
    return (
      /*#__PURE__*/
      // Disable reason: The div below visually simulates an input field. Its
      // child input is the actual input and responds accordingly to all keyboard
      // events, but click events need to be passed onto the child input. There
      // is no appropriate aria role for describing this situation, which is only
      // for the benefit of sighted users.
      /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
      (0,jsx_runtime.jsxs)("div", {
        className: (0,clsx/* default */.A)('components-base-control', 'woocommerce-select-control__control', className, {
          empty: !query || query.length === 0,
          'is-active': isActive,
          'has-tags': inlineTags && hasTags,
          'with-value': this.getInputValue()?.length,
          'has-error': !!help,
          'is-disabled': disabled
        }),
        onClick: event => {
          // Don't focus the input if the click event is from the error message.
          if (
          // eslint-disable-next-line @typescript-eslint/ban-ts-comment
          // @ts-ignore - event.target.className is not in the type definition.
          event.target.className !== 'components-base-control__help' && this.input.current) {
            this.input.current.focus();
          }
        },
        children: [isSearchable && /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          className: "woocommerce-select-control__control-icon",
          icon: search/* default */.A
        }), inlineTags && /*#__PURE__*/(0,jsx_runtime.jsx)(tags, {
          onChange: onChange,
          showClearButton: showClearButton,
          selected: this.props.selected
        }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "components-base-control__field",
          children: [!!label && /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
            htmlFor: `woocommerce-select-control-${instanceId}__control-input`,
            className: "components-base-control__label",
            children: label
          }), this.renderInput(), inlineTags && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            id: `search-inline-input-${instanceId}`,
            className: "screen-reader-text",
            children: (0,build_module.__)('Move backward for selected items', 'woocommerce')
          }), !!help && /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
            id: `woocommerce-select-control-${instanceId}__help`,
            className: "components-base-control__help",
            children: help
          })]
        })]
      })
      /* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    );
  }
}
/* harmony default export */ const control = (Control);
try {
    // @ts-ignore
    Control.displayName = "Control";
    // @ts-ignore
    Control.__docgenInfo = { "description": "A search control to allow user input to filter the options.", "displayName": "Control", "props": { "hasTags": { "defaultValue": null, "description": "Bool to determine if tags should be rendered.", "name": "hasTags", "required": false, "type": { "name": "boolean" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "inlineTags": { "defaultValue": null, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": null, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "instanceId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "listboxId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "Function called when the input is blurred.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": true, "type": { "name": "(selected: Option[]) => void" } }, "onSearch": { "defaultValue": null, "description": "Function called when input field is changed or focused.", "name": "onSearch", "required": true, "type": { "name": "(query: string) => void" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "query": { "defaultValue": null, "description": "Search query entered by user.", "name": "query", "required": false, "type": { "name": "string | null" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values. If the label of the selected\nvalue is omitted, the Tag of that value will not be rendered inside the\nsearch box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "showAllOnFocus": { "defaultValue": null, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": null, "description": "Control input autocomplete field, defaults: off.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the control should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } }, "updateSearchOptions": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "updateSearchOptions", "required": true, "type": { "name": "(query: string) => void" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "multiple": { "defaultValue": null, "description": "Multi-select mode allows multiple options to be selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "isFocused": { "defaultValue": null, "description": "Is the control currently focused.", "name": "isFocused", "required": false, "type": { "name": "boolean" } }, "activeId": { "defaultValue": null, "description": "ID for accessibility purposes. aria-activedescendant will be set to this value.", "name": "activeId", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "Disable the control.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "isExpanded": { "defaultValue": null, "description": "Is the control currently expanded. This is for accessibility purposes.", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": null, "description": "The type of input to use for the search field.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "ariaLabel": { "defaultValue": null, "description": "The aria label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Class name to be added to the input.", "name": "className", "required": false, "type": { "name": "string" } }, "showClearButton": { "defaultValue": null, "description": "Show the clear button.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/control.tsx#Control"] = { docgenInfo: Control.__docgenInfo, name: "Control", path: "../../packages/js/components/src/select-control/control.tsx#Control" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/select-control/index.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





const initialState = {
  isExpanded: false,
  isFocused: false,
  query: '',
  searchOptions: []
};

/**
 * A search box which filters options while typing,
 * allowing a user to select from an option from a filtered list.
 */
class SelectControl extends react.Component {
  static defaultProps = {
    ignoreDiacritics: false,
    excludeSelectedOptions: true,
    getSearchExpression: lodash.identity,
    inlineTags: false,
    isSearchable: false,
    onChange: lodash.noop,
    onFilter: lodash.identity,
    onSearch: options => Promise.resolve(options),
    maxResults: 0,
    multiple: false,
    searchDebounceTime: 0,
    searchInputType: 'search',
    selected: [],
    showAllOnFocus: false,
    showClearButton: false,
    hideBeforeSearch: false,
    staticList: false,
    autoComplete: 'off',
    virtualScroll: false,
    virtualItemHeight: 35,
    virtualListHeight: 300
  };
  node = null;
  activePromise = null;
  cacheSearchOptions = [];
  constructor(props) {
    super(props);
    const {
      selected,
      options,
      excludeSelectedOptions
    } = props;
    this.state = {
      ...initialState,
      searchOptions: [],
      selectedIndex: selected && options?.length && !excludeSelectedOptions ? options.findIndex(option => option.key === selected) : null
    };
    this.bindNode = this.bindNode.bind(this);
    this.decrementSelectedIndex = this.decrementSelectedIndex.bind(this);
    this.incrementSelectedIndex = this.incrementSelectedIndex.bind(this);
    this.onAutofillChange = this.onAutofillChange.bind(this);
    this.updateSearchOptions = (0,lodash.debounce)(this.updateSearchOptions.bind(this), props.searchDebounceTime);
    this.search = this.search.bind(this);
    this.selectOption = this.selectOption.bind(this);
    this.setExpanded = this.setExpanded.bind(this);
    this.setNewValue = this.setNewValue.bind(this);
  }
  componentDidUpdate(prevProps) {
    const {
      selected
    } = this.props;
    if (selected !== prevProps.selected) {
      this.reset(selected);
    }
  }
  bindNode(node) {
    this.node = node;
  }
  reset(selected = this.getSelected()) {
    const {
      multiple,
      excludeSelectedOptions
    } = this.props;
    const newState = {
      ...initialState
    };
    // Reset selectedIndex if single selection.
    if (!multiple && (0,lodash.isArray)(selected) && selected.length && selected[0].key) {
      newState.selectedIndex = !excludeSelectedOptions ? this.props.options.findIndex(i => i.key === selected[0].key) : null;
    }
    this.setState(newState);
  }
  handleFocusOutside() {
    this.reset();
  }
  hasMultiple() {
    const {
      multiple,
      selected
    } = this.props;
    if (!multiple) {
      return false;
    }
    if (Array.isArray(selected)) {
      return selected.some(item => Boolean(item.label));
    }
    return Boolean(selected);
  }
  hasTags() {
    const selected = this.getSelected();
    return Array.isArray(selected) && selected.some(item => Boolean(item.label));
  }
  getSelected() {
    const {
      multiple,
      options,
      selected
    } = this.props;

    // Return the passed value if an array is provided.
    if (multiple || Array.isArray(selected)) {
      return selected;
    }
    const selectedOption = options.find(option => option.key === selected);
    return selectedOption ? [selectedOption] : [];
  }
  selectOption(option) {
    const {
      multiple,
      selected
    } = this.props;
    const newSelected = multiple && (0,lodash.isArray)(selected) ? [...selected, option] : [option];
    this.reset(newSelected);
    const oldSelected = Array.isArray(selected) ? selected : [{
      key: selected
    }];
    const isSelected = oldSelected.findIndex(val => val.key === option.key);
    if (isSelected === -1) {
      this.setNewValue(newSelected);
    }

    // After selecting option, the list will reset and we'd need to correct selectedIndex.
    const newSelectedIndex = this.props.excludeSelectedOptions ?
    // Since we're excluding the selected option, invalidate selection
    // so re-focusing wont immediately set it to the neighbouring option.
    null : this.getOptions().findIndex(i => i.key === option.key);
    this.setState({
      selectedIndex: newSelectedIndex
    });
  }
  setNewValue(newValue) {
    const {
      onChange,
      selected,
      multiple
    } = this.props;
    const {
      query
    } = this.state;
    // Trigger a change if the selected value is different and pass back
    // an array or string depending on the original value.
    if (multiple || Array.isArray(selected)) {
      onChange(newValue, query);
    } else {
      onChange(newValue.length > 0 ? newValue[0].key : '', query);
    }
  }
  decrementSelectedIndex() {
    const {
      selectedIndex
    } = this.state;
    const options = this.getOptions();
    const nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex === 0 ? options.length : selectedIndex) - 1 : options.length - 1;
    this.setState({
      selectedIndex: nextSelectedIndex
    });
  }
  incrementSelectedIndex() {
    const {
      selectedIndex
    } = this.state;
    const options = this.getOptions();
    const nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex + 1) % options.length : 0;
    this.setState({
      selectedIndex: nextSelectedIndex
    });
  }
  announce(searchOptions) {
    const {
      debouncedSpeak
    } = this.props;
    if (!debouncedSpeak) {
      return;
    }
    if (!!searchOptions.length) {
      debouncedSpeak((0,build_module/* sprintf */.nv)(
      // translators: %d: number of results.
      (0,build_module._n)('%d result found, use up and down arrow keys to navigate.', '%d results found, use up and down arrow keys to navigate.', searchOptions.length, 'woocommerce'), searchOptions.length), 'assertive');
    } else {
      debouncedSpeak((0,build_module.__)('No results.', 'woocommerce'), 'assertive');
    }
  }
  getOptions() {
    const {
      isSearchable,
      options,
      excludeSelectedOptions
    } = this.props;
    const {
      searchOptions
    } = this.state;
    const selected = this.getSelected();
    const selectedKeys = (0,lodash.isArray)(selected) ? selected.map(option => option.key) : [];
    const shownOptions = isSearchable ? searchOptions : options;
    if (excludeSelectedOptions) {
      return shownOptions?.filter(option => !selectedKeys.includes(option.key));
    }
    return shownOptions;
  }
  getOptionsByQuery(options, query) {
    const {
      getSearchExpression,
      maxResults,
      onFilter,
      ignoreDiacritics
    } = this.props;
    const filtered = [];

    // Create a regular expression to filter the options.
    const baseQuery = query ? query.trim() : '';
    const normalizedQuery = ignoreDiacritics ? baseQuery.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : baseQuery;
    const expression = getSearchExpression((0,lodash.escapeRegExp)(normalizedQuery));
    const search = expression ? new RegExp(expression, 'i') : /^$/;
    for (let i = 0; i < options.length; i++) {
      const option = options[i];

      // Merge label into keywords
      let {
        keywords = []
      } = option;
      if (typeof option.label === 'string') {
        keywords = [...keywords, option.label];
      }
      const isMatch = keywords.some(keyword => {
        const normalizedKeyword = ignoreDiacritics ? keyword.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : keyword;
        return search.test(normalizedKeyword);
      });
      if (!isMatch) {
        continue;
      }
      filtered.push(option);

      // Abort early if max reached
      if (maxResults && filtered.length === maxResults) {
        break;
      }
    }
    return onFilter(filtered, query);
  }
  setExpanded(value) {
    this.setState({
      isExpanded: value
    });
  }
  search(query) {
    const cacheSearchOptions = this.cacheSearchOptions || [];
    const searchOptions = query !== null && !query.length && !this.props.hideBeforeSearch ? cacheSearchOptions : this.getOptionsByQuery(cacheSearchOptions, query);
    this.setState({
      query,
      isFocused: true,
      searchOptions,
      selectedIndex: query && query?.length > 0 ? null : this.state.selectedIndex // Only reset selectedIndex if we're actually searching.
    }, () => {
      this.setState({
        isExpanded: Boolean(this.getOptions()?.length)
      });
    });
    this.updateSearchOptions(query);
  }
  updateSearchOptions(query) {
    const {
      hideBeforeSearch,
      options,
      onSearch
    } = this.props;
    const promise = this.activePromise = Promise.resolve(onSearch(options, query)).then(promiseOptions => {
      if (promise !== this.activePromise) {
        // Another promise has become active since this one was asked to resolve, so do nothing,
        // or else we might end triggering a race condition updating the state.
        return;
      }
      this.cacheSearchOptions = promiseOptions;

      // Get all options if `hideBeforeSearch` is enabled and query is not null.
      const searchOptions = query !== null && !query.length && !hideBeforeSearch ? promiseOptions : this.getOptionsByQuery(promiseOptions, query);
      this.setState({
        searchOptions,
        selectedIndex: query && query?.length > 0 ? null : this.state.selectedIndex // Only reset selectedIndex if we're actually searching.
      }, () => {
        this.setState({
          isExpanded: Boolean(this.getOptions().length)
        });
        this.announce(searchOptions);
      });
    });
  }
  onAutofillChange(event) {
    const {
      options
    } = this.props;
    const searchOptions = this.getOptionsByQuery(options, event.target.value);
    if (searchOptions.length === 1) {
      this.selectOption(searchOptions[0]);
    }
  }
  render() {
    const {
      autofill,
      children,
      className,
      disabled,
      controlClassName,
      inlineTags,
      instanceId,
      isSearchable,
      options,
      virtualScroll,
      virtualItemHeight,
      virtualListHeight
    } = this.props;
    const {
      isExpanded,
      isFocused,
      selectedIndex
    } = this.state;
    const hasMultiple = this.hasMultiple();
    const hasTags = this.hasTags();
    const {
      key: selectedKey = ''
    } = (0,lodash.isNumber)(selectedIndex) && options[selectedIndex] || {};
    const listboxId = isExpanded ? `woocommerce-select-control__listbox-${instanceId}` : undefined;
    const activeId = isExpanded ? `woocommerce-select-control__option-${instanceId}-${selectedKey}` : undefined;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('woocommerce-select-control', className, {
        'has-inline-tags': hasTags && inlineTags,
        'is-focused': isFocused,
        'is-searchable': isSearchable
      }),
      ref: this.bindNode,
      children: [autofill && /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
        onChange: this.onAutofillChange,
        name: autofill,
        type: "text",
        className: "woocommerce-select-control__autofill-input",
        tabIndex: -1
      }), children, /*#__PURE__*/(0,jsx_runtime.jsx)(control, {
        help: this.props.help,
        label: this.props.label,
        inlineTags: inlineTags,
        isSearchable: isSearchable,
        isFocused: isFocused,
        instanceId: instanceId,
        searchInputType: this.props.searchInputType,
        query: this.state.query,
        placeholder: this.props.placeholder,
        autoComplete: this.props.autoComplete,
        multiple: this.props.multiple,
        ariaLabel: this.props.ariaLabel,
        onBlur: this.props.onBlur,
        showAllOnFocus: this.props.showAllOnFocus,
        activeId: activeId,
        className: controlClassName,
        disabled: disabled,
        hasTags: hasTags,
        isExpanded: isExpanded,
        listboxId: listboxId,
        onSearch: this.search,
        selected: this.getSelected(),
        onChange: this.setNewValue,
        setExpanded: this.setExpanded,
        updateSearchOptions: this.updateSearchOptions,
        decrementSelectedIndex: this.decrementSelectedIndex,
        incrementSelectedIndex: this.incrementSelectedIndex,
        showClearButton: this.props.showClearButton
      }), !inlineTags && hasMultiple && /*#__PURE__*/(0,jsx_runtime.jsx)(tags, {
        onChange: this.props.onChange,
        showClearButton: this.props.showClearButton,
        selected: this.getSelected()
      }), isExpanded && /*#__PURE__*/(0,jsx_runtime.jsx)(list, {
        instanceId: instanceId,
        selectedIndex: selectedIndex,
        staticList: this.props.staticList,
        listboxId: listboxId,
        node: this.node,
        onSelect: this.selectOption,
        onSearch: this.search,
        options: this.getOptions(),
        decrementSelectedIndex: this.decrementSelectedIndex,
        incrementSelectedIndex: this.incrementSelectedIndex,
        setExpanded: this.setExpanded,
        virtualScroll: virtualScroll,
        virtualItemHeight: virtualItemHeight,
        virtualListHeight: virtualListHeight
      })]
    });
  }
}
/* harmony default export */ const select_control = ((0,compose/* default */.A)(with_spoken_messages/* default */.A, with_instance_id/* default */.A, with_focus_outside/* default */.A // this MUST be the innermost HOC as it calls handleFocusOutside
)(SelectControl));
try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "A search box which filters options while typing,\nallowing a user to select from an option from a filtered list.", "displayName": "SelectControl", "props": { "autofill": { "defaultValue": null, "description": "Name to use for the autofill field, not used if no string is passed.", "name": "autofill", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed before the `Control` of this component.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "controlClassName": { "defaultValue": null, "description": "Class name applied to control wrapper.", "name": "controlClassName", "required": false, "type": { "name": "string" } }, "ignoreDiacritics": { "defaultValue": { value: "false" }, "description": "Whether to ignore diacritics when matching search queries.\nIf true, both the user\u2019s query and all option keywords are normalised to their base characters.", "name": "ignoreDiacritics", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": null, "description": "Allow the select options to be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "excludeSelectedOptions": { "defaultValue": { value: "true" }, "description": "Exclude already selected options from the options list.", "name": "excludeSelectedOptions", "required": false, "type": { "name": "boolean" } }, "onFilter": { "defaultValue": null, "description": "Add or remove items to the list of options after filtering,\npassed the array of filtered options and should return an array of options.", "name": "onFilter", "required": false, "type": { "name": "((options: Option[], query: string | null) => Option[])" } }, "getSearchExpression": { "defaultValue": null, "description": "Function to add regex expression to the filter the results, passed the search query.", "name": "getSearchExpression", "required": false, "type": { "name": "((query: string) => string | RegExp | null)" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": { value: "false" }, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((selected: string | Option[], query?: string | null) => void)" } }, "onSearch": { "defaultValue": { value: "( options: Option[] ) => Promise.resolve( options )" }, "description": "Function run after search query is updated, passed previousOptions and query,\nshould return a promise with an array of updated options.", "name": "onSearch", "required": false, "type": { "name": "((previousOptions: Option[], query: string | null) => Promise<Option[]>)" } }, "options": { "defaultValue": null, "description": "An array of objects for the options list.  The option along with its key, label and\nvalue will be returned in the onChange event.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "searchDebounceTime": { "defaultValue": { value: "0" }, "description": "Time in milliseconds to debounce the search function after typing.", "name": "searchDebounceTime", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "maxResults": { "defaultValue": { value: "0" }, "description": "A limit for the number of results shown in the options menu.  Set to 0 for no limit.", "name": "maxResults", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": { value: "false" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": { value: "search" }, "description": "The input type for the search box control.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "hideBeforeSearch": { "defaultValue": { value: "false" }, "description": "Only show list options after typing a search query.", "name": "hideBeforeSearch", "required": false, "type": { "name": "boolean" } }, "showAllOnFocus": { "defaultValue": { value: "false" }, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "staticList": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticList", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": { value: "off" }, "description": "autocomplete prop for the Control input field.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "Instance ID for the component.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "debouncedSpeak": { "defaultValue": null, "description": "From withSpokenMessages", "name": "debouncedSpeak", "required": false, "type": { "name": "((message: string, assertive?: string) => void)" } }, "ariaLabel": { "defaultValue": null, "description": "aria-label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "On Blur callback.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "virtualScroll": { "defaultValue": { value: "false" }, "description": "Enable virtual scrolling for large lists of options.", "name": "virtualScroll", "required": false, "type": { "name": "boolean" } }, "virtualItemHeight": { "defaultValue": { value: "35" }, "description": "Height in pixels for each virtual item. Required when virtualScroll is true.", "name": "virtualItemHeight", "required": false, "type": { "name": "number" } }, "virtualListHeight": { "defaultValue": { value: "300" }, "description": "Maximum height in pixels for the virtualized list. Default is 300.", "name": "virtualListHeight", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/index.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/select-control/index.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    selectcontrol.displayName = "selectcontrol";
    // @ts-ignore
    selectcontrol.__docgenInfo = { "description": "", "displayName": "selectcontrol", "props": { "autofill": { "defaultValue": null, "description": "Name to use for the autofill field, not used if no string is passed.", "name": "autofill", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed before the `Control` of this component.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "controlClassName": { "defaultValue": null, "description": "Class name applied to control wrapper.", "name": "controlClassName", "required": false, "type": { "name": "string" } }, "ignoreDiacritics": { "defaultValue": null, "description": "Whether to ignore diacritics when matching search queries.\nIf true, both the user\u2019s query and all option keywords are normalised to their base characters.", "name": "ignoreDiacritics", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": null, "description": "Allow the select options to be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "excludeSelectedOptions": { "defaultValue": null, "description": "Exclude already selected options from the options list.", "name": "excludeSelectedOptions", "required": false, "type": { "name": "boolean" } }, "onFilter": { "defaultValue": null, "description": "Add or remove items to the list of options after filtering,\npassed the array of filtered options and should return an array of options.", "name": "onFilter", "required": false, "type": { "name": "((options: Option[], query: string | null) => Option[])" } }, "getSearchExpression": { "defaultValue": null, "description": "Function to add regex expression to the filter the results, passed the search query.", "name": "getSearchExpression", "required": false, "type": { "name": "((query: string) => string | RegExp | null)" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "inlineTags": { "defaultValue": null, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": null, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((selected: string | Option[], query?: string | null) => void)" } }, "onSearch": { "defaultValue": null, "description": "Function run after search query is updated, passed previousOptions and query,\nshould return a promise with an array of updated options.", "name": "onSearch", "required": false, "type": { "name": "((previousOptions: Option[], query: string | null) => Promise<Option[]>)" } }, "options": { "defaultValue": null, "description": "An array of objects for the options list.  The option along with its key, label and\nvalue will be returned in the onChange event.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "searchDebounceTime": { "defaultValue": null, "description": "Time in milliseconds to debounce the search function after typing.", "name": "searchDebounceTime", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "maxResults": { "defaultValue": null, "description": "A limit for the number of results shown in the options menu.  Set to 0 for no limit.", "name": "maxResults", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": null, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": null, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": null, "description": "The input type for the search box control.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "hideBeforeSearch": { "defaultValue": null, "description": "Only show list options after typing a search query.", "name": "hideBeforeSearch", "required": false, "type": { "name": "boolean" } }, "showAllOnFocus": { "defaultValue": null, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "staticList": { "defaultValue": null, "description": "Render results list positioned statically instead of absolutely.", "name": "staticList", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": null, "description": "autocomplete prop for the Control input field.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "Instance ID for the component.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "debouncedSpeak": { "defaultValue": null, "description": "From withSpokenMessages", "name": "debouncedSpeak", "required": false, "type": { "name": "((message: string, assertive?: string) => void)" } }, "ariaLabel": { "defaultValue": null, "description": "aria-label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "On Blur callback.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "virtualScroll": { "defaultValue": null, "description": "Enable virtual scrolling for large lists of options.", "name": "virtualScroll", "required": false, "type": { "name": "boolean" } }, "virtualItemHeight": { "defaultValue": null, "description": "Height in pixels for each virtual item. Required when virtualScroll is true.", "name": "virtualItemHeight", "required": false, "type": { "name": "number" } }, "virtualListHeight": { "defaultValue": null, "description": "Maximum height in pixels for the virtualized list. Default is 300.", "name": "virtualListHeight", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/index.tsx#selectcontrol"] = { docgenInfo: selectcontrol.__docgenInfo, name: "selectcontrol", path: "../../packages/js/components/src/select-control/index.tsx#selectcontrol" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/tag/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */








const Tag = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(({
  id,
  label,
  popoverContents,
  remove,
  screenReaderLabel,
  className
}, removeButtonRef) => {
  const [isVisible, setIsVisible] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Tag).toString();
  const labelId = `woocommerce-tag__label-${instanceId}`;
  screenReaderLabel = screenReaderLabel || label;
  if (!label) {
    // A null label probably means something went wrong
    // @todo Maybe this should be a loading indicator?
    return null;
  }
  label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__/* .decodeEntities */ .S)(label);
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)('woocommerce-tag', className, {
    'has-remove': !!remove
  });
  const labelTextNode = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "screen-reader-text",
      children: screenReaderLabel
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      "aria-hidden": "true",
      children: label
    })]
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("span", {
    className: classes,
    children: [popoverContents ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__text",
      id: labelId,
      onClick: () => setIsVisible(true),
      children: labelTextNode
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "woocommerce-tag__text",
      id: labelId,
      children: labelTextNode
    }), popoverContents && isVisible && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .Ay, {
      onClose: () => setIsVisible(false),
      children: popoverContents
    }), remove && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__remove",
      ref: removeButtonRef,
      onClick: remove(id),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(
      // translators: %s is the name of the tag being removed.
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Remove %s', 'woocommerce'), label),
      "aria-describedby": labelId,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A,
        size: 20,
        className: "clear-icon"
      })
    })]
  });
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tag);
try {
    // @ts-ignore
    tag.displayName = "tag";
    // @ts-ignore
    tag.__docgenInfo = { "description": "", "displayName": "tag", "props": { "label": { "defaultValue": null, "description": "The name for this item, displayed as the tag's text.", "name": "label", "required": true, "type": { "name": "string" } }, "id": { "defaultValue": null, "description": "A unique ID for this item. This is used to identify the item when the remove button is clicked.", "name": "id", "required": false, "type": { "name": "string | number" } }, "popoverContents": { "defaultValue": null, "description": "Contents to display on click in a popover", "name": "popoverContents", "required": false, "type": { "name": "ReactNode" } }, "remove": { "defaultValue": null, "description": "A function called when the remove X is clicked. If not used, no X icon will display.", "name": "remove", "required": false, "type": { "name": "((id: string | number) => MouseEventHandler<HTMLButtonElement>)" } }, "screenReaderLabel": { "defaultValue": null, "description": "A more descriptive label for screen reader users. Defaults to the `name` prop.", "name": "screenReaderLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Additional CSS classes.", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tag/index.tsx#tag"] = { docgenInfo: tag.__docgenInfo, name: "tag", path: "../../packages/js/components/src/tag/index.tsx#tag" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/date/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ad: () => (/* binding */ presetValues),
/* harmony export */   RE: () => (/* binding */ periods),
/* harmony export */   Y6: () => (/* binding */ dateValidationMessages),
/* harmony export */   lI: () => (/* binding */ getCurrentDates),
/* harmony export */   r3: () => (/* binding */ isoDateFormat),
/* harmony export */   sf: () => (/* binding */ toMoment),
/* harmony export */   t_: () => (/* binding */ validateDateInputForRange),
/* harmony export */   vW: () => (/* binding */ getDateParamsFromQuery)
/* harmony export */ });
/* unused harmony exports defaultDateTimeFormat, appendTimestamp, getRangeLabel, getStoreTimeZoneMoment, getLastPeriod, getCurrentPeriod, getDateDifferenceInDays, getPreviousDate, getAllowedIntervalsForQuery, getIntervalForQuery, getChartTypeForQuery, dayTicksThreshold, weekTicksThreshold, defaultTableDateFormat, getDateFormatsForIntervalD3, getDateFormatsForIntervalPhp, getDateFormatsForInterval, loadLocaleData, isLeapYear, containsLeapYear */
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var date_fns_tz__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/date-fns-tz@3.2.0_date-fns@4.1.0/node_modules/date-fns-tz/dist/esm/index.js");
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+date@5.33.1/node_modules/@wordpress/date/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/qs@6.15.1/node_modules/qs/lib/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(qs__WEBPACK_IMPORTED_MODULE_5__);
/**
 * External dependencies
 */






const isoDateFormat = 'YYYY-MM-DD';
const defaultDateTimeFormat = 'YYYY-MM-DDTHH:mm:ss';

/**
 * DateValue Object
 *
 * @typedef  {Object} DateValue - DateValue data about the selected period.
 * @property {moment.Moment} primaryStart   - Primary start of the date range.
 * @property {moment.Moment} primaryEnd     - Primary end of the date range.
 * @property {moment.Moment} secondaryStart - Secondary start of the date range.
 * @property {moment.Moment} secondaryEnd   - Secondary End of the date range.
 */

/**
 * DataPickerOptions Object
 *
 * @typedef  {Object}  DataPickerOptions - Describes the date range supplied by the date picker.
 * @property {string}        label  - The translated value of the period.
 * @property {string}        range  - The human readable value of a date range.
 * @property {moment.Moment} after  - Start of the date range.
 * @property {moment.Moment} before - End of the date range.
 */

/**
 * DateParams Object
 *
 * @typedef {Object} DateParams - date parameters derived from query parameters.
 * @property {string}             period  - period value, ie `last_week`
 * @property {string}             compare - compare valuer, ie previous_year
 * @param    {moment.Moment|null} after   - If the period supplied is "custom", this is the after date
 * @param    {moment.Moment|null} before  - If the period supplied is "custom", this is the before date
 */

const presetValues = [{
  value: 'today',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Today', 'woocommerce')
}, {
  value: 'yesterday',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Yesterday', 'woocommerce')
}, {
  value: 'week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Week to date', 'woocommerce')
}, {
  value: 'last_week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last week', 'woocommerce')
}, {
  value: 'month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Month to date', 'woocommerce')
}, {
  value: 'last_month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last month', 'woocommerce')
}, {
  value: 'quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Quarter to date', 'woocommerce')
}, {
  value: 'last_quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last quarter', 'woocommerce')
}, {
  value: 'year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Year to date', 'woocommerce')
}, {
  value: 'last_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Last year', 'woocommerce')
}, {
  value: 'custom',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Custom', 'woocommerce')
}];
const periods = [{
  value: 'previous_period',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Previous period', 'woocommerce')
}, {
  value: 'previous_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Previous year', 'woocommerce')
}];
const isValidMomentInput = input => moment__WEBPACK_IMPORTED_MODULE_0___default()(input).isValid();

/**
 * Adds timestamp to a string date.
 *
 * @param {moment.Moment} date      - Date as a moment object.
 * @param {string}        timeOfDay - Either `start`, `now` or `end` of the day.
 * @return {string} - String date with timestamp attached.
 */
const appendTimestamp = (date, timeOfDay) => {
  if (timeOfDay === 'start') {
    return date.startOf('day').format(defaultDateTimeFormat);
  }
  if (timeOfDay === 'now') {
    // Set seconds to 00 to avoid consecutives calls happening before the previous
    // one finished.
    return date.format(defaultDateTimeFormat);
  }
  if (timeOfDay === 'end') {
    return date.endOf('day').format(defaultDateTimeFormat);
  }
  throw new Error('appendTimestamp requires second parameter to be either `start`, `now` or `end`');
};

/**
 * Convert a string to Moment object
 *
 * @param {string}  format - localized date string format
 * @param {unknown} str    - date string or moment object
 * @return {moment.Moment|null} - Moment object representing given string
 */
function toMoment(format, str) {
  if (moment__WEBPACK_IMPORTED_MODULE_0___default().isMoment(str)) {
    return str.isValid() ? str : null;
  }
  if (typeof str === 'string') {
    const date = moment__WEBPACK_IMPORTED_MODULE_0___default()(str, [isoDateFormat, format], true);
    return date.isValid() ? date : null;
  }
  throw new Error('toMoment requires a string to be passed as an argument');
}

/**
 * Expands moment's localized format tokens ("L", "LL", "ll", ...) into the
 * underlying format the locale defines for them.
 *
 * Moment resolves those tokens only while formatting, so a day rendered through
 * one is invisible to the day token scan below and the range end would be
 * dropped. This mirrors moment's own expansion, including its pass limit, so
 * the expanded format renders exactly what the original one would.
 *
 * @param {string}        format     - localized date string format
 * @param {moment.Locale} localeData - locale the format will be rendered with
 * @return {string} - format string with its localized tokens expanded, leaving
 *                      escaped and bracketed ones as the literals they are
 */
function expandLocalizedFormat(format, localeData) {
  // Bracketed sections and backslash escapes are moment's literals, so an "L"
  // inside one is text; matching them first leaves them untouched, as
  // `longDateFormat` has no entry for them.
  const localizedTokens = /\[[^[]*\]|\\?(?:LTS|LT|LL?L?L?|l{1,4})/g;
  let expanded = format;
  // An expansion can itself hold localized tokens; moment allows six passes.
  let passes = 6;
  while (passes-- > 0) {
    localizedTokens.lastIndex = 0;
    if (!localizedTokens.test(expanded)) {
      break;
    }
    expanded = expanded.replace(localizedTokens, token => localeData.longDateFormat(token) || token);
  }
  return expanded;
}

/**
 * Renders the month and weekday names of a moment format string into escaped
 * literals.
 *
 * Moment picks the grammatical form of both names by pattern-testing the
 * format string while rendering: month choosers look for a day token next to
 * the month one, and Ukrainian renders the genitive weekday whenever a
 * bracketed literal sits before "dddd" - exactly the shape the substitutions
 * here leave behind. Months and weekdays are the only tokens moment resolves
 * against the format, so rendering every name in one pass, against the format
 * as the locale received it, settles each choice before any substitution can
 * flip one.
 *
 * @param {string}        format     - localized date string format
 * @param {moment.Moment} date       - date whose month and weekday to render
 * @param {moment.Locale} localeData - locale the format will be rendered with
 * @return {string} - format string with its month and weekday tokens escaped
 */
function escapeNameTokens(format, date, localeData) {
  // Backslash escapes and bracketed sections are moment's literals, so an
  // "M" or "d" inside one is text. A backslash escapes the whole token that
  // follows it; the escaped alternatives mirror moment's own tokens. "MM",
  // "M", "Mo", "do" and "d" render digits, which carry no grammar.
  return format.replace(/\\(?:Mo|MM?M?M?|ddd?d?|do?)|\\.|\[[^\]]*\]|M{3,4}|d{2,4}/g, token => {
    if (token.startsWith('M')) {
      const name = token.length === 4 ? localeData.months(date, format) : localeData.monthsShort(date, format);
      return `[${name}]`;
    }
    if (!token.startsWith('d')) {
      return token;
    }
    if (token.length === 4) {
      return `[${localeData.weekdays(date, format)}]`;
    }
    const name = token.length === 3 ? localeData.weekdaysShort(date) : localeData.weekdaysMin(date);
    return `[${name}]`;
  });
}

/**
 * Swaps the day of month token of a moment format string for an escaped literal.
 *
 * Substituting in the format instead of in the formatted date keeps the value
 * away from the rest of the localized output: Japanese renders October as
 * "10月", where replacing the day "1" lands on the month instead, and locales
 * with non-Latin digits never match a Latin day number at all.
 *
 * @param {string}   format      - localized date string format
 * @param {Function} replacement - builds the literal text to render in place of
 *                               the day, from the token it replaces
 * @return {string|null} - format string, or null when it holds no day token
 */
function replaceDayToken(format, replacement) {
  let replaced = false;
  // Backslash escapes and bracketed sections are moment's literals, so a "D"
  // inside one is text. A backslash escapes the whole token that follows it,
  // not just its first character; the escaped alternatives mirror moment's
  // own day tokens, so a longer run of "D"s leaves the rest live.
  const dayRangeFormat = format.replace(/\\(?:Do|DDDo|DD?D?D?)|\\.|\[[^\]]*\]|D+o?/g, token => {
    // Runs longer than "DD" are day of year tokens, not day of month.
    const dayDigits = token.endsWith('o') ? token.length - 1 : token.length;
    if (replaced || token.startsWith('[') || token.startsWith('\\') || dayDigits > 2) {
      return token;
    }
    replaced = true;
    return `[${replacement(token)}]`;
  });
  return replaced ? dayRangeFormat : null;
}

/**
 * Given two dates, derive a string representation
 *
 * @param {moment.Moment} after  - start date
 * @param {moment.Moment} before - end date
 * @return {string} - text value for the supplied date range
 */
function getRangeLabel(after, before) {
  const isSameYear = after.year() === before.year();
  const isSameMonth = isSameYear && after.month() === before.month();
  const isSameDay = isSameYear && isSameMonth && after.isSame(before, 'day');
  const fullDateFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('MMM D, YYYY', 'woocommerce');
  if (isSameDay) {
    return after.format(fullDateFormat);
  } else if (isSameMonth) {
    // Formatting each day through the token it replaces keeps whatever the
    // format asked for, such as the zero padding of "DD" or the ordinal of "Do".
    // Everything else still renders from `after`, so a weekday, week number
    // or time in the format stays the one the range starts on.
    const localeData = after.localeData();
    const dayRangeFormat = replaceDayToken(escapeNameTokens(expandLocalizedFormat(fullDateFormat, localeData), after, localeData), dayToken => `${after.format(dayToken)} - ${before.format(dayToken)}`);

    // No day of month token to swap: the format either omits the day or
    // holds only a day of year token, which is left alone. Either way the
    // shared month is as much of the range as this format can carry.
    if (dayRangeFormat === null) {
      return after.format(fullDateFormat);
    }
    return after.format(dayRangeFormat);
  } else if (isSameYear) {
    const monthDayFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('MMM D', 'woocommerce');
    return `${after.format(monthDayFormat)} - ${before.format(fullDateFormat)}`;
  }
  return `${after.format(fullDateFormat)} - ${before.format(fullDateFormat)}`;
}

/**
 * Reads the configured store time zone from `wcSettings`.
 *
 * @return {string | undefined} - IANA zone name or `±HH:mm` offset, if set.
 */
function getStoreTimeZoneSetting() {
  // Optional chaining does not protect the free `window` reference, so guard
  // it for non-browser environments before falling back to the local moment.
  if (typeof window === 'undefined') {
    return undefined;
  }
  return window.wcSettings?.timeZone || window.wcSettings?.admin?.timeZone;
}

/**
 * Gets the current time in the store time zone if set.
 *
 * @return {moment.Moment} - Moment object in the store time zone.
 */
function getStoreTimeZoneMoment() {
  const timeZone = getStoreTimeZoneSetting();
  if (typeof timeZone !== 'string' || timeZone.length === 0) {
    return moment__WEBPACK_IMPORTED_MODULE_0___default()();
  }
  if (['+', '-'].includes(timeZone.charAt(0))) {
    return moment__WEBPACK_IMPORTED_MODULE_0___default()().utcOffset(timeZone);
  }

  // Named IANA zone (e.g. `America/New_York`). Resolve the current UTC
  // offset with `date-fns-tz` (which uses the browser `Intl` API) rather
  // than `moment-timezone`'s `.tz()`: the admin build externalises
  // `moment-timezone` to the global `window.moment`, so a plugin replacing
  // `window.moment` strips `.tz` and crashes Analytics (#64020).
  const offsetInMinutes = (0,date_fns_tz__WEBPACK_IMPORTED_MODULE_1__/* .getTimezoneOffset */ .Zn)(timeZone) / 60000;
  if (Number.isNaN(offsetInMinutes)) {
    return moment__WEBPACK_IMPORTED_MODULE_0___default()();
  }
  return moment__WEBPACK_IMPORTED_MODULE_0___default()().utcOffset(offsetInMinutes);
}

/**
 * Re-applies the store time zone's UTC offset for a moment's own date, keeping
 * its wall-clock time. `getStoreTimeZoneMoment` resolves a named IANA zone's
 * offset for "now", so a range boundary in a different DST period (e.g. last
 * year/quarter) would otherwise be an hour off; this corrects each boundary
 * against its own date. Fixed `±HH:mm` offsets and the no-zone case are
 * returned unchanged (#64020).
 *
 * @param {moment.Moment} date - The moment to anchor.
 * @return {moment.Moment} - The anchored moment.
 */
function anchorToStoreTimeZone(date) {
  const timeZone = getStoreTimeZoneSetting();
  if (typeof timeZone !== 'string' || timeZone.length === 0 || ['+', '-'].includes(timeZone.charAt(0))) {
    return date;
  }
  const offsetInMinutes = (0,date_fns_tz__WEBPACK_IMPORTED_MODULE_1__/* .getTimezoneOffset */ .Zn)(timeZone, date.toDate()) / 60000;
  return Number.isNaN(offsetInMinutes) ? date : date.utcOffset(offsetInMinutes, true);
}

/**
 * Anchors every boundary of a date range to the store time zone.
 * See {@link anchorToStoreTimeZone}.
 *
 * @param {DateValue} range - The computed range.
 * @return {DateValue} - The range with each boundary anchored.
 */
function anchorRangeToStoreTimeZone(range) {
  return {
    primaryStart: anchorToStoreTimeZone(range.primaryStart),
    primaryEnd: anchorToStoreTimeZone(range.primaryEnd),
    secondaryStart: anchorToStoreTimeZone(range.secondaryStart),
    secondaryEnd: anchorToStoreTimeZone(range.secondaryEnd)
  };
}

/**
 * Aligns the moment locale's start of the week with the WordPress
 * "Week Starts On" setting. WordPress core applies the setting to the moment
 * locale, but `wp.date.setSettings` then redefines the locale without a `week`
 * key, resetting the start of the week to Sunday; without this correction,
 * week ranges and calendar layouts ignore the setting.
 */
function ensureMomentStartOfWeek() {
  const startOfWeek = (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_2__.getSettings)().l10n?.startOfWeek;
  if (typeof startOfWeek !== 'number' || !Number.isInteger(startOfWeek) || startOfWeek < 0 || startOfWeek > 6) {
    return;
  }
  if (moment__WEBPACK_IMPORTED_MODULE_0___default().localeData().firstDayOfWeek() !== startOfWeek) {
    moment__WEBPACK_IMPORTED_MODULE_0___default().updateLocale(moment__WEBPACK_IMPORTED_MODULE_0___default().locale(), {
      week: {
        dow: startOfWeek
      }
    });
  }
}
ensureMomentStartOfWeek();

/**
 * Get a DateValue object for a period prior to the current period.
 *
 * @param {moment.DurationInputArg2} period  - the chosen period
 * @param {string}                   compare - `previous_period` or `previous_year`
 * @return {DateValue} - DateValue data about the selected period
 */
function getLastPeriod(period, compare) {
  ensureMomentStartOfWeek();
  const primaryStart = getStoreTimeZoneMoment().startOf(period).subtract(1, period);
  const primaryEnd = primaryStart.clone().endOf(period);
  let secondaryStart;
  let secondaryEnd;
  if (compare === 'previous_period') {
    if (period === 'year') {
      // Subtract two entire periods for years to take into account leap year
      secondaryStart = moment__WEBPACK_IMPORTED_MODULE_0___default()().startOf(period).subtract(2, period);
      secondaryEnd = secondaryStart.clone().endOf(period);
    } else {
      // Otherwise, use days in primary period to figure out how far to go back
      // This is necessary for calculating weeks instead of using `endOf`.
      const daysDiff = primaryEnd.diff(primaryStart, 'days');
      secondaryEnd = primaryStart.clone().subtract(1, 'days');
      secondaryStart = secondaryEnd.clone().subtract(daysDiff, 'days');
    }
  } else if (period === 'week') {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    secondaryEnd = primaryEnd.clone().subtract(1, 'years');
  } else {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    secondaryEnd = secondaryStart.clone().endOf(period);
  }

  // When the period is month, be sure to force end of month to take into account leap year
  if (period === 'month') {
    secondaryEnd = secondaryEnd.clone().endOf('month');
  }
  return anchorRangeToStoreTimeZone({
    primaryStart,
    primaryEnd,
    secondaryStart,
    secondaryEnd
  });
}

/**
 * Get a DateValue object for a current period. The period begins on the first day of the period,
 * and ends on the current day.
 *
 * @param {moment.DurationInputArg2} period  - the chosen period
 * @param {string}                   compare - `previous_period` or `previous_year`
 * @return {DateValue} - DateValue data about the selected period
 */
function getCurrentPeriod(period, compare) {
  ensureMomentStartOfWeek();
  const primaryStart = getStoreTimeZoneMoment().startOf(period);
  const primaryEnd = getStoreTimeZoneMoment();
  const daysSoFar = primaryEnd.diff(primaryStart, 'days');
  let secondaryStart;
  let secondaryEnd;
  if (compare === 'previous_period') {
    secondaryStart = primaryStart.clone().subtract(1, period);
    secondaryEnd = primaryEnd.clone().subtract(1, period);
  } else {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    // Set the end time to 23:59:59.
    secondaryEnd = secondaryStart.clone().add(daysSoFar + 1, 'days').subtract(1, 'seconds');
  }
  return anchorRangeToStoreTimeZone({
    primaryStart,
    primaryEnd,
    secondaryStart,
    secondaryEnd
  });
}

/**
 * Get a DateValue object for a period described by a period, compare value, and start/end
 * dates, for custom dates.
 *
 * @param {string}             period   - the chosen period
 * @param {string}             compare  - `previous_period` or `previous_year`
 * @param {moment.Moment|null} [after]  - after date if custom period
 * @param {moment.Moment|null} [before] - before date if custom period
 * @return {DateValue} - DateValue data about the selected period
 */
const getDateValue = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.memoize)((period, compare, after, before) => {
  switch (period) {
    case 'today':
      return getCurrentPeriod('day', compare);
    case 'yesterday':
      return getLastPeriod('day', compare);
    case 'week':
      return getCurrentPeriod('week', compare);
    case 'last_week':
      return getLastPeriod('week', compare);
    case 'month':
      return getCurrentPeriod('month', compare);
    case 'last_month':
      return getLastPeriod('month', compare);
    case 'quarter':
      return getCurrentPeriod('quarter', compare);
    case 'last_quarter':
      return getLastPeriod('quarter', compare);
    case 'year':
      return getCurrentPeriod('year', compare);
    case 'last_year':
      return getLastPeriod('year', compare);
    case 'custom':
      if (!after || !before) {
        throw Error('Custom date range requires both after and before dates.');
      }
      const difference = before.diff(after, 'days');
      if (compare === 'previous_period') {
        const secondaryEnd = after.clone().subtract(1, 'days');
        const secondaryStart = secondaryEnd.clone().subtract(difference, 'days');
        return {
          primaryStart: after,
          primaryEnd: before,
          secondaryStart,
          secondaryEnd
        };
      }
      return {
        primaryStart: after,
        primaryEnd: before,
        secondaryStart: after.clone().subtract(1, 'years'),
        secondaryEnd: before.clone().subtract(1, 'years')
      };
  }
}, (period, compare, after, before) => [period, compare, after && after.format(), before && before.format()].join(':'));

/**
 * Memoized internal logic of getDateParamsFromQuery().
 *
 * @param {string|undefined} period           - period value, ie `last_week`
 * @param {string|undefined} compare          - compare value, ie `previous_year`
 * @param {string|undefined} after            - date in iso date format, ie `2018-07-03`
 * @param {string|undefined} before           - date in iso date format, ie `2018-07-03`
 * @param {string}           defaultDateRange - the store's default date range
 * @return {DateParams} - date parameters derived from query parameters with added defaults
 */
const getDateParamsFromQueryMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.memoize)((period, compare, after, before, defaultDateRange) => {
  if (period && compare) {
    return {
      period,
      compare,
      after: after ? moment__WEBPACK_IMPORTED_MODULE_0___default()(after) : null,
      before: before ? moment__WEBPACK_IMPORTED_MODULE_0___default()(before) : null
    };
  }
  const queryDefaults = (0,qs__WEBPACK_IMPORTED_MODULE_5__.parse)(defaultDateRange.replace(/&amp;/g, '&'));
  if (typeof queryDefaults.period !== 'string') {
    /* eslint-disable no-console */
    console.warn(`Unexpected default period type ${queryDefaults.period}`);
    /* eslint-enable no-console */
    queryDefaults.period = '';
  }
  if (typeof queryDefaults.compare !== 'string') {
    /* eslint-disable no-console */
    console.warn(`Unexpected default compare type ${queryDefaults.compare}`);
    /* eslint-enable no-console */
    queryDefaults.compare = '';
  }
  return {
    period: queryDefaults.period,
    compare: queryDefaults.compare,
    after: queryDefaults.after && isValidMomentInput(queryDefaults.after) ? moment__WEBPACK_IMPORTED_MODULE_0___default()(queryDefaults.after) : null,
    before: queryDefaults.before && isValidMomentInput(queryDefaults.before) ? moment__WEBPACK_IMPORTED_MODULE_0___default()(queryDefaults.before) : null
  };
}, (period, compare, after, before, defaultDateRange) => [period, compare, after, before, defaultDateRange].join(':'));

/**
 * Add default date-related parameters to a query object
 *
 * @param {Object} query            - query object
 * @param {string} query.period     - period value, ie `last_week`
 * @param {string} query.compare    - compare value, ie `previous_year`
 * @param {string} query.after      - date in iso date format, ie `2018-07-03`
 * @param {string} query.before     - date in iso date format, ie `2018-07-03`
 * @param {string} defaultDateRange - the store's default date range
 * @return {DateParams} - date parameters derived from query parameters with added defaults
 */
const getDateParamsFromQuery = (query, defaultDateRange = 'period=month&compare=previous_year') => {
  const {
    period,
    compare,
    after,
    before
  } = query;
  return getDateParamsFromQueryMemoized(period, compare, after, before, defaultDateRange);
};

/**
 * Memoized internal logic of getCurrentDates().
 *
 * @param {string|undefined} period         - period value, ie `last_week`
 * @param {string|undefined} compare        - compare value, ie `previous_year`
 * @param {Object}           primaryStart   - primary query start DateTime, in Moment instance.
 * @param {Object}           primaryEnd     - primary query start DateTime, in Moment instance.
 * @param {Object}           secondaryStart - secondary query start DateTime, in Moment instance.
 * @param {Object}           secondaryEnd   - secondary query start DateTime, in Moment instance.
 * @return {{primary: DataPickerOptions, secondary: DataPickerOptions}} - Primary and secondary DataPickerOptions objects
 */
const getCurrentDatesMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.memoize)((period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) => {
  const primaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.find)(presetValues, item => item.value === period);
  if (!primaryItem) {
    throw new Error(`Cannot find period: ${period}`);
  }
  const secondaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_3__.find)(periods, item => item.value === compare);
  if (!secondaryItem) {
    throw new Error(`Cannot find compare: ${compare}`);
  }
  return {
    primary: {
      label: primaryItem.label,
      range: getRangeLabel(primaryStart, primaryEnd),
      after: primaryStart,
      before: primaryEnd
    },
    secondary: {
      label: secondaryItem.label,
      range: getRangeLabel(secondaryStart, secondaryEnd),
      after: secondaryStart,
      before: secondaryEnd
    }
  };
}, (period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) => [period, compare, primaryStart && primaryStart.format(), primaryEnd && primaryEnd.format(), secondaryStart && secondaryStart.format(), secondaryEnd && secondaryEnd.format()].join(':'));

/**
 * Get Date Value Objects for a primary and secondary date range
 *
 * @param {Object} query            - query object
 * @param {string} query.period     - period value, ie `last_week`
 * @param {string} query.compare    - compare value, ie `previous_year`
 * @param {string} query.after      - date in iso date format, ie `2018-07-03`
 * @param {string} query.before     - date in iso date format, ie `2018-07-03`
 * @param {string} defaultDateRange - the store's default date range
 * @return {{primary: DataPickerOptions, secondary: DataPickerOptions}} - Primary and secondary DataPickerOptions objects
 */
const getCurrentDates = (query, defaultDateRange = 'period=month&compare=previous_year') => {
  const {
    period,
    compare,
    after,
    before
  } = getDateParamsFromQuery(query, defaultDateRange);
  const dateValue = getDateValue(period, compare, after, before);
  if (!dateValue) {
    throw Error('Invalid date range');
  }
  const {
    primaryStart,
    primaryEnd,
    secondaryStart,
    secondaryEnd
  } = dateValue;
  return getCurrentDatesMemoized(period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd);
};

/**
 * Calculates the date difference between two dates. Used in calculating a matching date for previous period.
 *
 * @param {string} date  - Date to compare
 * @param {string} date2 - Secondary date to compare
 * @return {number}  - Difference in days.
 */
const getDateDifferenceInDays = (date, date2) => {
  const _date = moment(date);
  const _date2 = moment(date2);
  return _date.diff(_date2, 'days');
};

/**
 * Get the previous date for either the previous period of year.
 *
 * @param {string}                 date     - Base date
 * @param {string}                 date1    - primary start
 * @param {string}                 date2    - secondary start
 * @param {string}                 compare  - `previous_period`  or `previous_year`
 * @param {moment.unitOfTime.Diff} interval - interval
 * @return {Object}  - Calculated date
 */
const getPreviousDate = (date, date1, date2, compare = 'previous_year', interval) => {
  const dateMoment = moment(date);
  if (compare === 'previous_year') {
    return dateMoment.clone().subtract(1, 'years');
  }
  const _date1 = moment(date1);
  const _date2 = moment(date2);
  const difference = _date1.diff(_date2, interval);
  return dateMoment.clone().subtract(difference, interval);
};

/**
 * Returns the allowed selectable intervals for a specific query.
 *
 * @param {Query}  query            Current query
 * @param {string} defaultDateRange - the store's default date range
 * @return {Array} Array containing allowed intervals.
 */
function getAllowedIntervalsForQuery(query, defaultDateRange = 'period=&compare=previous_year') {
  const {
    period
  } = getDateParamsFromQuery(query, defaultDateRange);
  let allowed = [];
  if (period === 'custom') {
    const {
      primary
    } = getCurrentDates(query);
    const differenceInDays = getDateDifferenceInDays(primary.before, primary.after);
    if (differenceInDays >= 365) {
      allowed = ['day', 'week', 'month', 'quarter', 'year'];
    } else if (differenceInDays >= 90) {
      allowed = ['day', 'week', 'month', 'quarter'];
    } else if (differenceInDays >= 28) {
      allowed = ['day', 'week', 'month'];
    } else if (differenceInDays >= 7) {
      allowed = ['day', 'week'];
    } else if (differenceInDays > 1 && differenceInDays < 7) {
      allowed = ['day'];
    } else {
      allowed = ['hour', 'day'];
    }
  } else {
    switch (period) {
      case 'today':
      case 'yesterday':
        allowed = ['hour', 'day'];
        break;
      case 'week':
      case 'last_week':
        allowed = ['day'];
        break;
      case 'month':
      case 'last_month':
        allowed = ['day', 'week'];
        break;
      case 'quarter':
      case 'last_quarter':
        allowed = ['day', 'week', 'month'];
        break;
      case 'year':
      case 'last_year':
        allowed = ['day', 'week', 'month', 'quarter'];
        break;
      default:
        allowed = ['day'];
        break;
    }
  }
  return allowed;
}

/**
 * Returns the current interval to use.
 *
 * @param {Query}  query            Current query
 * @param {string} defaultDateRange - the store's default date range
 * @return {string} Current interval.
 */
function getIntervalForQuery(query, defaultDateRange = 'period=&compare=previous_year') {
  const allowed = getAllowedIntervalsForQuery(query, defaultDateRange);
  const defaultInterval = allowed[0];
  let current = query.interval || defaultInterval;
  if (query.interval && !allowed.includes(query.interval)) {
    current = defaultInterval;
  }
  return current;
}

/**
 * Returns the current chart type to use.
 *
 * @param {Query}  query           Current query
 * @param {string} query.chartType
 * @return {string} Current chart type.
 */
function getChartTypeForQuery({
  chartType
}) {
  if (chartType !== undefined && ['line', 'bar'].includes(chartType)) {
    return chartType;
  }
  return 'line';
}
const dayTicksThreshold = 63;
const weekTicksThreshold = 9;
const defaultTableDateFormat = 'm/d/Y';

/**
 * Returns d3 date formats for the current interval.
 * See https://github.com/d3/d3-time-format for chart formats.
 *
 * @param {string} interval Interval to get date formats for.
 * @param {number} [ticks]  Number of ticks the axis will have.
 * @return {string} Current interval.
 */
function getDateFormatsForIntervalD3(interval, ticks = 0) {
  let screenReaderFormat = '%B %-d, %Y';
  let tooltipLabelFormat = '%B %-d, %Y';
  let xFormat = '%Y-%m-%d';
  let x2Format = '%b %Y';
  let tableFormat = defaultTableDateFormat;
  switch (interval) {
    case 'hour':
      screenReaderFormat = '%_I%p %B %-d, %Y';
      tooltipLabelFormat = '%_I%p %b %-d, %Y';
      xFormat = '%_I%p';
      x2Format = '%b %-d, %Y';
      tableFormat = 'h A';
      break;
    case 'day':
      if (ticks < dayTicksThreshold) {
        xFormat = '%-d';
      } else {
        xFormat = '%b';
        x2Format = '%Y';
      }
      break;
    case 'week':
      if (ticks < weekTicksThreshold) {
        xFormat = '%-d';
        x2Format = '%b %Y';
      } else {
        xFormat = '%b';
        x2Format = '%Y';
      }
      // eslint-disable-next-line @wordpress/i18n-translator-comments
      screenReaderFormat = __('Week of %B %-d, %Y', 'woocommerce');
      // eslint-disable-next-line @wordpress/i18n-translator-comments
      tooltipLabelFormat = __('Week of %B %-d, %Y', 'woocommerce');
      break;
    case 'quarter':
    case 'month':
      screenReaderFormat = '%B %Y';
      tooltipLabelFormat = '%B %Y';
      xFormat = '%b';
      x2Format = '%Y';
      break;
    case 'year':
      screenReaderFormat = '%Y';
      tooltipLabelFormat = '%Y';
      xFormat = '%Y';
      break;
  }
  return {
    screenReaderFormat,
    tooltipLabelFormat,
    xFormat,
    x2Format,
    tableFormat
  };
}

/**
 * Returns php date formats for the current interval.
 * See see https://www.php.net/manual/en/datetime.format.php.
 *
 * @param {string} interval Interval to get date formats for.
 * @param {number} [ticks]  Number of ticks the axis will have.
 * @return {string} Current interval.
 */
function getDateFormatsForIntervalPhp(interval, ticks = 0) {
  let screenReaderFormat = 'F j, Y';
  let tooltipLabelFormat = 'F j, Y';
  let xFormat = 'Y-m-d';
  let x2Format = 'M Y';
  let tableFormat = defaultTableDateFormat;
  switch (interval) {
    case 'hour':
      screenReaderFormat = 'gA F j, Y';
      tooltipLabelFormat = 'gA M j, Y';
      xFormat = 'gA';
      x2Format = 'M j, Y';
      tableFormat = 'h A';
      break;
    case 'day':
      if (ticks < dayTicksThreshold) {
        xFormat = 'j';
      } else {
        xFormat = 'M';
        x2Format = 'Y';
      }
      break;
    case 'week':
      if (ticks < weekTicksThreshold) {
        xFormat = 'j';
        x2Format = 'M Y';
      } else {
        xFormat = 'M';
        x2Format = 'Y';
      }

      // Since some alphabet letters have php associated formats, we need to escape them first.
      const escapedWeekOfStr = __('Week of', 'woocommerce').replace(/(\w)/g, '\\$1');
      screenReaderFormat = `${escapedWeekOfStr} F j, Y`;
      tooltipLabelFormat = `${escapedWeekOfStr} F j, Y`;
      break;
    case 'quarter':
    case 'month':
      screenReaderFormat = 'F Y';
      tooltipLabelFormat = 'F Y';
      xFormat = 'M';
      x2Format = 'Y';
      break;
    case 'year':
      screenReaderFormat = 'Y';
      tooltipLabelFormat = 'Y';
      xFormat = 'Y';
      break;
  }
  return {
    screenReaderFormat,
    tooltipLabelFormat,
    xFormat,
    x2Format,
    tableFormat
  };
}

/**
 * Returns date formats for the current interval.
 *
 * @param {string} interval      Interval to get date formats for.
 * @param {number} [ticks]       Number of ticks the axis will have.
 * @param {Object} [option]      Options
 * @param {string} [option.type] Date format type, d3 or php, defaults to d3.
 * @return {string} Current interval.
 */
function getDateFormatsForInterval(interval, ticks = 0, option = {
  type: 'd3'
}) {
  switch (option.type) {
    case 'php':
      return getDateFormatsForIntervalPhp(interval, ticks);
    case 'd3':
    default:
      return getDateFormatsForIntervalD3(interval, ticks);
  }
}

/**
 * Gutenberg's moment instance is loaded with i18n values, which are
 * PHP date formats, ie 'LLL: "F j, Y g:i a"'. Override those with translations
 * of moment style js formats.
 *
 * @param {Object} config               Locale config object, from store settings.
 * @param {string} config.userLocale
 * @param {Array}  config.weekdaysShort
 */
function loadLocaleData({
  userLocale,
  weekdaysShort
}) {
  // Don't update if the wp locale hasn't been set yet, like in unit tests, for instance.
  if (moment.locale() !== 'en') {
    moment.updateLocale(userLocale, {
      longDateFormat: {
        L: __('MM/DD/YYYY', 'woocommerce'),
        LL: __('MMMM D, YYYY', 'woocommerce'),
        LLL: __('D MMMM YYYY LT', 'woocommerce'),
        LLLL: __('dddd, D MMMM YYYY LT', 'woocommerce'),
        LT: __('HH:mm', 'woocommerce'),
        // Set LTS to default LTS locale format because we don't have a specific format for it.
        // Reference https://github.com/moment/moment/blob/develop/dist/moment.js
        LTS: 'h:mm:ss A'
      },
      weekdaysMin: weekdaysShort
    });
  }
}
const dateValidationMessages = {
  invalid: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Invalid date', 'woocommerce'),
  future: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Select a date in the past', 'woocommerce'),
  startAfterEnd: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Start date must be before end date', 'woocommerce'),
  endBeforeStart: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Start date must be before end date', 'woocommerce')
};

/**
 * @typedef {Object} validatedDate
 * @property {Object|null} date  - A resulting Moment date object or null, if invalid
 * @property {string}      error - An optional error message if date is invalid
 */

/**
 * Validate text input supplied for a date range.
 *
 * @param {string}      type     - Designate beginning or end of range, eg `before` or `after`.
 * @param {string}      value    - User input value
 * @param {Object|null} [before] - If already designated, the before date parameter
 * @param {Object|null} [after]  - If already designated, the after date parameter
 * @param {string}      format   - The expected date format in a user's locale
 * @return {Object} validatedDate - validated date object
 */
function validateDateInputForRange(type, value, before, after, format) {
  const date = toMoment(format, value);
  if (!date) {
    return {
      date: null,
      error: dateValidationMessages.invalid
    };
  }
  if (moment__WEBPACK_IMPORTED_MODULE_0___default()().isBefore(date, 'day')) {
    return {
      date: null,
      error: dateValidationMessages.future
    };
  }
  if (type === 'after' && before && date.isAfter(before, 'day')) {
    return {
      date: null,
      error: dateValidationMessages.startAfterEnd
    };
  }
  if (type === 'before' && after && date.isBefore(after, 'day')) {
    return {
      date: null,
      error: dateValidationMessages.endBeforeStart
    };
  }
  return {
    date
  };
}

/**
 * Checks whether the year is a leap year.
 *
 * @param  year Year to check
 * @return {boolean} True if leap year
 */
function isLeapYear(year) {
  return year % 4 === 0 && year % 100 !== 0 || year % 400 === 0;
}

/**
 * Checks whether a date range contains leap year.
 *
 * @param {string} startDate Start date
 * @param {string} endDate   End date
 * @return {boolean} True if date range contains a leap year
 */
function containsLeapYear(startDate, endDate) {
  // Parse the input dates to get the years
  const startYear = new Date(startDate).getFullYear();
  const endYear = new Date(endDate).getFullYear();
  if (!isNaN(startYear) && !isNaN(endYear)) {
    // Check each year in the range
    for (let year = startYear; year <= endYear; year++) {
      if (isLeapYear(year)) {
        return true;
      }
    }
  }
  return false; // No leap years in the range or invalid date
}

/***/ }),

/***/ "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale sync recursive ^\\.\\/.*$":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./af": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/af.js",
	"./af.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/af.js",
	"./ar": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar.js",
	"./ar-dz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ma.js",
	"./ar-ps": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ps.js",
	"./ar-ps.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ps.js",
	"./ar-sa": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-tn.js",
	"./ar.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar.js",
	"./az": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/az.js",
	"./az.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/az.js",
	"./be": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/be.js",
	"./be.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/be.js",
	"./bg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bg.js",
	"./bg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bg.js",
	"./bm": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bm.js",
	"./bm.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bm.js",
	"./bn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn.js",
	"./bn-bd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn-bd.js",
	"./bn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn.js",
	"./bo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bo.js",
	"./bo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bo.js",
	"./br": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/br.js",
	"./br.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/br.js",
	"./bs": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bs.js",
	"./bs.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bs.js",
	"./ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ca.js",
	"./ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ca.js",
	"./cs": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cs.js",
	"./cs.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cs.js",
	"./cv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cv.js",
	"./cv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cv.js",
	"./cy": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cy.js",
	"./cy.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cy.js",
	"./da": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/da.js",
	"./da.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/da.js",
	"./de": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de.js",
	"./de-at": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-at.js",
	"./de-at.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-at.js",
	"./de-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-ch.js",
	"./de.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de.js",
	"./dv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/dv.js",
	"./dv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/dv.js",
	"./el": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/el.js",
	"./el.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/el.js",
	"./en-au": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-au.js",
	"./en-au.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-au.js",
	"./en-ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ca.js",
	"./en-gb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-gb.js",
	"./en-ie": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ie.js",
	"./en-il": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-il.js",
	"./en-il.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-il.js",
	"./en-in": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-in.js",
	"./en-in.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-in.js",
	"./en-nz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-nz.js",
	"./en-sg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-sg.js",
	"./eo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eo.js",
	"./eo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eo.js",
	"./es": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es.js",
	"./es-do": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-do.js",
	"./es-do.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-do.js",
	"./es-mx": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-mx.js",
	"./es-us": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-us.js",
	"./es-us.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-us.js",
	"./es.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es.js",
	"./et": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/et.js",
	"./et.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/et.js",
	"./eu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eu.js",
	"./eu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eu.js",
	"./fa": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fa.js",
	"./fa.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fa.js",
	"./fi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fi.js",
	"./fi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fi.js",
	"./fil": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fil.js",
	"./fil.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fil.js",
	"./fo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fo.js",
	"./fo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fo.js",
	"./fr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr.js",
	"./fr-ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ch.js",
	"./fr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr.js",
	"./fy": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fy.js",
	"./fy.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fy.js",
	"./ga": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ga.js",
	"./ga.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ga.js",
	"./gd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gd.js",
	"./gd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gd.js",
	"./gl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gl.js",
	"./gl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gl.js",
	"./gom-deva": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-latn.js",
	"./gu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gu.js",
	"./gu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gu.js",
	"./he": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/he.js",
	"./he.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/he.js",
	"./hi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hi.js",
	"./hi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hi.js",
	"./hr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hr.js",
	"./hr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hr.js",
	"./hu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hu.js",
	"./hu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hu.js",
	"./hy-am": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hy-am.js",
	"./id": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/id.js",
	"./id.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/id.js",
	"./is": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/is.js",
	"./is.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/is.js",
	"./it": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it.js",
	"./it-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it-ch.js",
	"./it.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it.js",
	"./ja": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ja.js",
	"./ja.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ja.js",
	"./jv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/jv.js",
	"./jv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/jv.js",
	"./ka": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ka.js",
	"./ka.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ka.js",
	"./kk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kk.js",
	"./kk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kk.js",
	"./km": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/km.js",
	"./km.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/km.js",
	"./kn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kn.js",
	"./kn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kn.js",
	"./ko": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ko.js",
	"./ko.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ko.js",
	"./ku": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku.js",
	"./ku-kmr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku-kmr.js",
	"./ku-kmr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku-kmr.js",
	"./ku.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku.js",
	"./ky": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ky.js",
	"./ky.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ky.js",
	"./lb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lb.js",
	"./lb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lb.js",
	"./lo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lo.js",
	"./lo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lo.js",
	"./lt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lt.js",
	"./lt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lt.js",
	"./lv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lv.js",
	"./lv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lv.js",
	"./me": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/me.js",
	"./me.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/me.js",
	"./mi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mi.js",
	"./mi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mi.js",
	"./mk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mk.js",
	"./mk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mk.js",
	"./ml": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ml.js",
	"./ml.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ml.js",
	"./mn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mn.js",
	"./mn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mn.js",
	"./mr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mr.js",
	"./mr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mr.js",
	"./ms": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms.js",
	"./ms-my": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms-my.js",
	"./ms.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms.js",
	"./mt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mt.js",
	"./mt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mt.js",
	"./my": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/my.js",
	"./my.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/my.js",
	"./nb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nb.js",
	"./nb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nb.js",
	"./ne": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ne.js",
	"./ne.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ne.js",
	"./nl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl.js",
	"./nl-be": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl-be.js",
	"./nl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl.js",
	"./nn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nn.js",
	"./nn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nn.js",
	"./oc-lnc": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pa-in.js",
	"./pl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pl.js",
	"./pl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pl.js",
	"./pt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt.js",
	"./pt-br": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt-br.js",
	"./pt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt.js",
	"./ro": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ro.js",
	"./ro.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ro.js",
	"./ru": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ru.js",
	"./ru.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ru.js",
	"./sd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sd.js",
	"./sd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sd.js",
	"./se": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/se.js",
	"./se.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/se.js",
	"./si": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/si.js",
	"./si.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/si.js",
	"./sk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sk.js",
	"./sk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sk.js",
	"./sl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sl.js",
	"./sl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sl.js",
	"./sq": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sq.js",
	"./sq.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sq.js",
	"./sr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr.js",
	"./sr-cyrl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr.js",
	"./ss": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ss.js",
	"./ss.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ss.js",
	"./sv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sv.js",
	"./sv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sv.js",
	"./sw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sw.js",
	"./sw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sw.js",
	"./ta": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ta.js",
	"./ta.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ta.js",
	"./te": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/te.js",
	"./te.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/te.js",
	"./tet": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tet.js",
	"./tet.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tet.js",
	"./tg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tg.js",
	"./tg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tg.js",
	"./th": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/th.js",
	"./th.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/th.js",
	"./tk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tk.js",
	"./tk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tk.js",
	"./tl-ph": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tl-ph.js",
	"./tlh": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tlh.js",
	"./tlh.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tlh.js",
	"./tr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tr.js",
	"./tr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tr.js",
	"./tzl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzl.js",
	"./tzl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzl.js",
	"./tzm": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm.js",
	"./tzm-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm.js",
	"./ug-cn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ug-cn.js",
	"./uk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uk.js",
	"./uk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uk.js",
	"./ur": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ur.js",
	"./ur.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ur.js",
	"./uz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz.js",
	"./uz-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz-latn.js",
	"./uz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz.js",
	"./vi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/vi.js",
	"./vi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/vi.js",
	"./x-pseudo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/x-pseudo.js",
	"./yo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/yo.js",
	"./yo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/yo.js",
	"./zh-cn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-tw.js"
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
webpackContext.id = "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ }),

/***/ "../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ esm_TransitionGroup)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js + 1 modules
var inheritsLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js");
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/utils/ChildMapping.js

/**
 * Given `this.props.children`, return an object mapping key to child.
 *
 * @param {*} children `this.props.children`
 * @return {object} Mapping of key to child
 */

function getChildMapping(children, mapFn) {
  var mapper = function mapper(child) {
    return mapFn && (0,react.isValidElement)(child) ? mapFn(child) : child;
  };

  var result = Object.create(null);
  if (children) react.Children.map(children, function (c) {
    return c;
  }).forEach(function (child) {
    // run the map function here instead so that the key is the computed one
    result[child.key] = mapper(child);
  });
  return result;
}
/**
 * When you're adding or removing children some may be added or removed in the
 * same render pass. We want to show *both* since we want to simultaneously
 * animate elements in and out. This function takes a previous set of keys
 * and a new set of keys and merges them with its best guess of the correct
 * ordering. In the future we may expose some of the utilities in
 * ReactMultiChild to make this easy, but for now React itself does not
 * directly have this concept of the union of prevChildren and nextChildren
 * so we implement it here.
 *
 * @param {object} prev prev children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @param {object} next next children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @return {object} a key set that contains all keys in `prev` and all keys
 * in `next` in a reasonable order.
 */

function mergeChildMappings(prev, next) {
  prev = prev || {};
  next = next || {};

  function getValueForKey(key) {
    return key in next ? next[key] : prev[key];
  } // For each key of `next`, the list of keys to insert before that key in
  // the combined list


  var nextKeysPending = Object.create(null);
  var pendingKeys = [];

  for (var prevKey in prev) {
    if (prevKey in next) {
      if (pendingKeys.length) {
        nextKeysPending[prevKey] = pendingKeys;
        pendingKeys = [];
      }
    } else {
      pendingKeys.push(prevKey);
    }
  }

  var i;
  var childMapping = {};

  for (var nextKey in next) {
    if (nextKeysPending[nextKey]) {
      for (i = 0; i < nextKeysPending[nextKey].length; i++) {
        var pendingNextKey = nextKeysPending[nextKey][i];
        childMapping[nextKeysPending[nextKey][i]] = getValueForKey(pendingNextKey);
      }
    }

    childMapping[nextKey] = getValueForKey(nextKey);
  } // Finally, add the keys which didn't appear before any key in `next`


  for (i = 0; i < pendingKeys.length; i++) {
    childMapping[pendingKeys[i]] = getValueForKey(pendingKeys[i]);
  }

  return childMapping;
}

function getProp(child, prop, props) {
  return props[prop] != null ? props[prop] : child.props[prop];
}

function getInitialChildMapping(props, onExited) {
  return getChildMapping(props.children, function (child) {
    return (0,react.cloneElement)(child, {
      onExited: onExited.bind(null, child),
      in: true,
      appear: getProp(child, 'appear', props),
      enter: getProp(child, 'enter', props),
      exit: getProp(child, 'exit', props)
    });
  });
}
function getNextChildMapping(nextProps, prevChildMapping, onExited) {
  var nextChildMapping = getChildMapping(nextProps.children);
  var children = mergeChildMappings(prevChildMapping, nextChildMapping);
  Object.keys(children).forEach(function (key) {
    var child = children[key];
    if (!(0,react.isValidElement)(child)) return;
    var hasPrev = (key in prevChildMapping);
    var hasNext = (key in nextChildMapping);
    var prevChild = prevChildMapping[key];
    var isLeaving = (0,react.isValidElement)(prevChild) && !prevChild.props.in; // item is new (entering)

    if (hasNext && (!hasPrev || isLeaving)) {
      // console.log('entering', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: true,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    } else if (!hasNext && hasPrev && !isLeaving) {
      // item is old (exiting)
      // console.log('leaving', key)
      children[key] = (0,react.cloneElement)(child, {
        in: false
      });
    } else if (hasNext && hasPrev && (0,react.isValidElement)(prevChild)) {
      // item hasn't changed transition states
      // copy over the last transition props;
      // console.log('unchanged', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: prevChild.props.in,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    }
  });
  return children;
}
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js









var values = Object.values || function (obj) {
  return Object.keys(obj).map(function (k) {
    return obj[k];
  });
};

var defaultProps = {
  component: 'div',
  childFactory: function childFactory(child) {
    return child;
  }
};
/**
 * The `<TransitionGroup>` component manages a set of transition components
 * (`<Transition>` and `<CSSTransition>`) in a list. Like with the transition
 * components, `<TransitionGroup>` is a state machine for managing the mounting
 * and unmounting of components over time.
 *
 * Consider the example below. As items are removed or added to the TodoList the
 * `in` prop is toggled automatically by the `<TransitionGroup>`.
 *
 * Note that `<TransitionGroup>`  does not define any animation behavior!
 * Exactly _how_ a list item animates is up to the individual transition
 * component. This means you can mix and match animations across different list
 * items.
 */

var TransitionGroup = /*#__PURE__*/function (_React$Component) {
  (0,inheritsLoose/* default */.A)(TransitionGroup, _React$Component);

  function TransitionGroup(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;

    var handleExited = _this.handleExited.bind((0,assertThisInitialized/* default */.A)(_this)); // Initial children should all be entering, dependent on appear


    _this.state = {
      contextValue: {
        isMounting: true
      },
      handleExited: handleExited,
      firstRender: true
    };
    return _this;
  }

  var _proto = TransitionGroup.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this.mounted = true;
    this.setState({
      contextValue: {
        isMounting: false
      }
    });
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    this.mounted = false;
  };

  TransitionGroup.getDerivedStateFromProps = function getDerivedStateFromProps(nextProps, _ref) {
    var prevChildMapping = _ref.children,
        handleExited = _ref.handleExited,
        firstRender = _ref.firstRender;
    return {
      children: firstRender ? getInitialChildMapping(nextProps, handleExited) : getNextChildMapping(nextProps, prevChildMapping, handleExited),
      firstRender: false
    };
  } // node is `undefined` when user provided `nodeRef` prop
  ;

  _proto.handleExited = function handleExited(child, node) {
    var currentChildMapping = getChildMapping(this.props.children);
    if (child.key in currentChildMapping) return;

    if (child.props.onExited) {
      child.props.onExited(node);
    }

    if (this.mounted) {
      this.setState(function (state) {
        var children = (0,esm_extends/* default */.A)({}, state.children);

        delete children[child.key];
        return {
          children: children
        };
      });
    }
  };

  _proto.render = function render() {
    var _this$props = this.props,
        Component = _this$props.component,
        childFactory = _this$props.childFactory,
        props = (0,objectWithoutPropertiesLoose/* default */.A)(_this$props, ["component", "childFactory"]);

    var contextValue = this.state.contextValue;
    var children = values(this.state.children).map(childFactory);
    delete props.appear;
    delete props.enter;
    delete props.exit;

    if (Component === null) {
      return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
        value: contextValue
      }, children);
    }

    return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
      value: contextValue
    }, /*#__PURE__*/react.createElement(Component, props, children));
  };

  return TransitionGroup;
}(react.Component);

TransitionGroup.propTypes =  false ? 0 : {};
TransitionGroup.defaultProps = defaultProps;
/* harmony default export */ const esm_TransitionGroup = (TransitionGroup);

/***/ }),

/***/ "?9f28":
/***/ (() => {

/* (ignored) */

/***/ })

}]);