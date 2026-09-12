"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3696],{

/***/ "../../packages/js/components/src/compare-filter/stories/compare-filter.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/compare-filter/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const query = {};
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
const Basic = ({
  path = new URL(document.location).searchParams.get('path')
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_1__/* .CompareFilter */ .S, {
  path: path,
  query: query,
  ...compareFilter
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/CompareFilter',
  component: ___WEBPACK_IMPORTED_MODULE_1__/* .CompareFilter */ .S
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "({\n  path = new URL(document.location).searchParams.get('path')\n}) => <CompareFilter path={path} query={query} {...compareFilter} />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/compare-filter/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


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

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */


/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
const Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Text || _wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A;

/***/ }),

/***/ "../../packages/js/components/src/search/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/components/src/search/search.tsx");
/**
 * Internal dependencies
 */


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_search__WEBPACK_IMPORTED_MODULE_0__/* .Search */ .v);
try {
    // @ts-ignore
    Search.displayName = "Search";
    // @ts-ignore
    Search.__docgenInfo = { "description": "A search box which autocompletes results while typing, allowing for the user to select an existing object\n(product, order, customer, etc). Currently only products are supported.", "displayName": "Search", "props": { "allowFreeTextSearch": { "defaultValue": { value: "false" }, "description": "Render additional options in the autocompleter to allow free text entering depending on the type.", "name": "allowFreeTextSearch", "required": false, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((value: Option | OptionCompletionValue[]) => unknown)" } }, "type": { "defaultValue": null, "description": "The object type to be used in searching.", "name": "type", "required": true, "type": { "name": "enum", "value": [{ "value": "\"custom\"" }, { "value": "\"countries\"" }, { "value": "\"attributes\"" }, { "value": "\"categories\"" }, { "value": "\"coupons\"" }, { "value": "\"customerNames\"" }, { "value": "\"customers\"" }, { "value": "\"downloadIps\"" }, { "value": "\"emails\"" }, { "value": "\"orders\"" }, { "value": "\"products\"" }, { "value": "\"registeredCustomers\"" }, { "value": "\"taxes\"" }, { "value": "\"usernames\"" }, { "value": "\"variableProducts\"" }, { "value": "\"variations\"" }] } }, "autocompleter": { "defaultValue": null, "description": "The custom autocompleter to be used in searching when type is 'custom'", "name": "autocompleter", "required": false, "type": { "name": "AutoCompleter" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "string | { key: string; label: string; }[]" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "staticResults": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticResults", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": { value: "false" }, "description": "Whether the control is disabled or not.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "true" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/search/index.tsx#Search"] = { docgenInfo: Search.__docgenInfo, name: "Search", path: "../../packages/js/components/src/search/index.tsx#Search" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);