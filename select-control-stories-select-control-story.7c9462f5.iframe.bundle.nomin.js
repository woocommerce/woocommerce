"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2752],{

/***/ "../../packages/js/components/src/select-control/stories/select-control.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   SingleSelectWithInlineTag: () => (/* binding */ SingleSelectWithInlineTag),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const options = [{
  key: 'apple',
  label: 'Apple',
  value: {
    id: 'apple'
  }
}, {
  key: 'apricot',
  label: 'Apricot',
  value: {
    id: 'apricot'
  }
}, {
  key: 'banana',
  label: 'Banana',
  keywords: ['best', 'fruit'],
  value: {
    id: 'banana'
  }
}, {
  key: 'blueberry',
  label: 'Blueberry',
  value: {
    id: 'blueberry'
  }
}, {
  key: 'cherry',
  label: 'Cherry',
  value: {
    id: 'cherry'
  }
}, {
  key: 'cantaloupe',
  label: 'Cantaloupe',
  value: {
    id: 'cantaloupe'
  }
}, {
  key: 'dragonfruit',
  label: 'Dragon Fruit',
  value: {
    id: 'dragonfruit'
  }
}, {
  key: 'elderberry',
  label: 'Elderberry',
  value: {
    id: 'elderberry'
  }
}];

// Create a larger list of options for virtual scrolling example
const manyOptions = Array.from({
  length: 2000
}, (_, index) => {
  const key = `option-${index + 1}`;
  return {
    key,
    label: `Option ${index + 1}`,
    value: {
      id: key
    }
  };
});
const SelectControlExample = () => {
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    simpleSelected: [],
    simpleMultipleSelected: [],
    singleSelected: [],
    singleSelectedShowAll: [],
    multipleSelected: [],
    inlineSelected: [],
    allOptionsIncludingSelected: options[options.length - 1].key,
    virtualScrollSelected: [],
    disabledSelected: [{
      key: 'apple',
      label: 'Apple',
      value: {
        id: 'apple'
      }
    }, {
      key: 'banana',
      label: 'Banana',
      value: {
        id: 'banana'
      }
    }],
    disabledInlineSelected: [{
      key: 'apple',
      label: 'Apple',
      value: {
        id: 'apple'
      }
    }, {
      key: 'banana',
      label: 'Banana',
      value: {
        id: 'banana'
      }
    }]
  });
  const {
    simpleSelected,
    simpleMultipleSelected,
    singleSelected,
    singleSelectedShowAll,
    multipleSelected,
    inlineSelected,
    allOptionsIncludingSelected,
    virtualScrollSelected,
    disabledSelected,
    disabledInlineSelected
  } = state;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Simple single value",
      onChange: selected => setState({
        ...state,
        simpleSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: simpleSelected
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Multiple values",
      multiple: true,
      onChange: selected => setState({
        ...state,
        simpleMultipleSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: simpleMultipleSelected
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Show all options with default selected",
      onChange: selected => setState({
        ...state,
        allOptionsIncludingSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: allOptionsIncludingSelected,
      showAllOnFocus: true,
      isSearchable: true,
      excludeSelectedOptions: false
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Single value searchable",
      isSearchable: true,
      onChange: selected => setState({
        ...state,
        singleSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: singleSelected
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Single value searchable with options on refocus",
      isSearchable: true,
      onChange: selected => setState({
        ...state,
        singleSelectedShowAll: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: singleSelectedShowAll,
      showAllOnFocus: true
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Inline tags searchable",
      isSearchable: true,
      multiple: true,
      inlineTags: true,
      onChange: selected => setState({
        ...state,
        inlineSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: inlineSelected
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      hideBeforeSearch: true,
      isSearchable: true,
      label: "Hidden options before search",
      multiple: true,
      onChange: selected => setState({
        ...state,
        multipleSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: multipleSelected,
      showClearButton: true
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Virtual scrolling with many options",
      isSearchable: true,
      onChange: selected => setState({
        ...state,
        virtualScrollSelected: selected
      }),
      options: manyOptions,
      placeholder: "Start typing to filter options...",
      selected: virtualScrollSelected,
      showAllOnFocus: true,
      virtualScroll: true,
      virtualItemHeight: 56,
      virtualListHeight: 56 * 6
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Disabled select control",
      isSearchable: true,
      multiple: true,
      disabled: true,
      onChange: selected => setState({
        ...state,
        disabledSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: disabledSelected,
      showClearButton: true
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("br", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      label: "Disabled select control with inline tags",
      isSearchable: true,
      multiple: true,
      disabled: true,
      inlineTags: true,
      onChange: selected => setState({
        ...state,
        disabledInlineSelected: selected
      }),
      options: options,
      placeholder: "Start typing to filter options...",
      selected: disabledInlineSelected,
      showClearButton: true
    })]
  });
};
const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(SelectControlExample, {});
const SingleSelectWithInlineTag = () => {
  const [selected, setSelected] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([options[0]]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    label: "Single value with inline tag",
    isSearchable: true,
    inlineTags: true,
    multiple: false,
    onChange: setSelected,
    options: options,
    placeholder: "Start typing to filter options...",
    selected: selected
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/SelectControl',
  component: ___WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <SelectControlExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};
SingleSelectWithInlineTag.parameters = {
  ...SingleSelectWithInlineTag.parameters,
  docs: {
    ...SingleSelectWithInlineTag.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState([options[0]]);\n  return <SelectControl label=\"Single value with inline tag\" isSearchable inlineTags multiple={false} onChange={setSelected} options={options} placeholder=\"Start typing to filter options...\" selected={selected} />;\n}",
      ...SingleSelectWithInlineTag.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    selectcontrol.displayName = "selectcontrol";
    // @ts-ignore
    selectcontrol.__docgenInfo = { "description": "", "displayName": "selectcontrol", "props": { "autofill": { "defaultValue": null, "description": "Name to use for the autofill field, not used if no string is passed.", "name": "autofill", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed before the `Control` of this component.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "controlClassName": { "defaultValue": null, "description": "Class name applied to control wrapper.", "name": "controlClassName", "required": false, "type": { "name": "string" } }, "ignoreDiacritics": { "defaultValue": null, "description": "Whether to ignore diacritics when matching search queries.\nIf true, both the user\u2019s query and all option keywords are normalised to their base characters.", "name": "ignoreDiacritics", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": null, "description": "Allow the select options to be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "excludeSelectedOptions": { "defaultValue": null, "description": "Exclude already selected options from the options list.", "name": "excludeSelectedOptions", "required": false, "type": { "name": "boolean" } }, "onFilter": { "defaultValue": null, "description": "Add or remove items to the list of options after filtering,\npassed the array of filtered options and should return an array of options.", "name": "onFilter", "required": false, "type": { "name": "((options: Option[], query: string | null) => Option[])" } }, "getSearchExpression": { "defaultValue": null, "description": "Function to add regex expression to the filter the results, passed the search query.", "name": "getSearchExpression", "required": false, "type": { "name": "((query: string) => string | RegExp | null)" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "ReactNode" } }, "inlineTags": { "defaultValue": null, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": null, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((selected: string | Option[], query?: string | null) => void)" } }, "onSearch": { "defaultValue": null, "description": "Function run after search query is updated, passed previousOptions and query,\nshould return a promise with an array of updated options.", "name": "onSearch", "required": false, "type": { "name": "((previousOptions: Option[], query: string | null) => Promise<Option[]>)" } }, "options": { "defaultValue": null, "description": "An array of objects for the options list.  The option along with its key, label and\nvalue will be returned in the onChange event.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "searchDebounceTime": { "defaultValue": null, "description": "Time in milliseconds to debounce the search function after typing.", "name": "searchDebounceTime", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "maxResults": { "defaultValue": null, "description": "A limit for the number of results shown in the options menu.  Set to 0 for no limit.", "name": "maxResults", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": null, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": null, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": null, "description": "The input type for the search box control.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "hideBeforeSearch": { "defaultValue": null, "description": "Only show list options after typing a search query.", "name": "hideBeforeSearch", "required": false, "type": { "name": "boolean" } }, "showAllOnFocus": { "defaultValue": null, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "staticList": { "defaultValue": null, "description": "Render results list positioned statically instead of absolutely.", "name": "staticList", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": null, "description": "autocomplete prop for the Control input field.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "Instance ID for the component.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "debouncedSpeak": { "defaultValue": null, "description": "From withSpokenMessages", "name": "debouncedSpeak", "required": false, "type": { "name": "((message: string, assertive?: string) => void)" } }, "ariaLabel": { "defaultValue": null, "description": "aria-label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "On Blur callback.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "virtualScroll": { "defaultValue": null, "description": "Enable virtual scrolling for large lists of options.", "name": "virtualScroll", "required": false, "type": { "name": "boolean" } }, "virtualItemHeight": { "defaultValue": null, "description": "Height in pixels for each virtual item. Required when virtualScroll is true.", "name": "virtualItemHeight", "required": false, "type": { "name": "number" } }, "virtualListHeight": { "defaultValue": null, "description": "Maximum height in pixels for the virtualized list. Default is 300.", "name": "virtualListHeight", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/stories/select-control.story.tsx#selectcontrol"] = { docgenInfo: selectcontrol.__docgenInfo, name: "selectcontrol", path: "../../packages/js/components/src/select-control/stories/select-control.story.tsx#selectcontrol" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  __: () => (/* reexport */ __),
  _n: () => (/* reexport */ _n),
  _x: () => (/* reexport */ _x),
  V8: () => (/* reexport */ isRTL),
  nv: () => (/* reexport */ sprintf)
});

// UNUSED EXPORTS: _nx, createI18n, defaultI18n, getLocaleData, hasTranslation, resetLocaleData, setLocaleData, subscribe

// EXTERNAL MODULE: ../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js
var src = __webpack_require__("../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/sprintf.js

function sprintf(format, ...args) {
  return (0,src/* default */.A)(format, ...args);
}

//# sourceMappingURL=sprintf.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js + 4 modules
var node_modules_tannin = __webpack_require__("../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/create-i18n.js

const DEFAULT_LOCALE_DATA = {
  "": {
    plural_forms(n) {
      return n === 1 ? 0 : 1;
    }
  }
};
const I18N_HOOK_REGEXP = /^i18n\.(n?gettext|has_translation)(_|$)/;
const createI18n = (initialData, initialDomain, hooks) => {
  const tannin = new node_modules_tannin/* default */.A({});
  const listeners = /* @__PURE__ */ new Set();
  const notifyListeners = () => {
    listeners.forEach((listener) => listener());
  };
  const subscribe = (callback) => {
    listeners.add(callback);
    return () => listeners.delete(callback);
  };
  const getLocaleData = (domain = "default") => tannin.data[domain];
  const doSetLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data
    };
    tannin.data[domain][""] = {
      ...DEFAULT_LOCALE_DATA[""],
      ...tannin.data[domain]?.[""]
    };
    delete tannin.pluralForms[domain];
  };
  const setLocaleData = (data, domain) => {
    doSetLocaleData(data, domain);
    notifyListeners();
  };
  const addLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data,
      // Populate default domain configuration (supported locale date which omits
      // a plural forms expression).
      "": {
        ...DEFAULT_LOCALE_DATA[""],
        ...tannin.data[domain]?.[""],
        ...data?.[""]
      }
    };
    delete tannin.pluralForms[domain];
    notifyListeners();
  };
  const resetLocaleData = (data, domain) => {
    tannin.data = {};
    tannin.pluralForms = {};
    setLocaleData(data, domain);
  };
  const dcnpgettext = (domain = "default", context, single, plural, number) => {
    if (!tannin.data[domain]) {
      doSetLocaleData(void 0, domain);
    }
    return tannin.dcnpgettext(domain, context, single, plural, number);
  };
  const getFilterDomain = (domain) => domain || "default";
  const __ = (text, domain) => {
    let translation = dcnpgettext(domain, void 0, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext",
      translation,
      text,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_" + getFilterDomain(domain),
      translation,
      text,
      domain
    );
  };
  const _x = (text, context, domain) => {
    let translation = dcnpgettext(domain, context, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext_with_context",
      translation,
      text,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_with_context_" + getFilterDomain(domain),
      translation,
      text,
      context,
      domain
    );
  };
  const _n = (single, plural, number, domain) => {
    let translation = dcnpgettext(
      domain,
      void 0,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext",
      translation,
      single,
      plural,
      number,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      domain
    );
  };
  const _nx = (single, plural, number, context, domain) => {
    let translation = dcnpgettext(
      domain,
      context,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext_with_context",
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_with_context_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
  };
  const isRTL = () => {
    return "rtl" === _x("ltr", "text direction");
  };
  const hasTranslation = (single, context, domain) => {
    const key = context ? context + "" + single : single;
    let result = !!tannin.data?.[domain ?? "default"]?.[key];
    if (hooks) {
      result = hooks.applyFilters(
        "i18n.has_translation",
        result,
        single,
        context,
        domain
      );
      result = hooks.applyFilters(
        "i18n.has_translation_" + getFilterDomain(domain),
        result,
        single,
        context,
        domain
      );
    }
    return result;
  };
  if (initialData) {
    setLocaleData(initialData, initialDomain);
  }
  if (hooks) {
    const onHookAddedOrRemoved = (hookName) => {
      if (I18N_HOOK_REGEXP.test(hookName)) {
        notifyListeners();
      }
    };
    hooks.addAction("hookAdded", "core/i18n", onHookAddedOrRemoved);
    hooks.addAction("hookRemoved", "core/i18n", onHookAddedOrRemoved);
  }
  return {
    getLocaleData,
    setLocaleData,
    addLocaleData,
    resetLocaleData,
    subscribe,
    __,
    _x,
    _n,
    _nx,
    isRTL,
    hasTranslation
  };
};

//# sourceMappingURL=create-i18n.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs + 10 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/default-i18n.js


const i18n = createI18n(void 0, void 0, build_module/* defaultHooks */.se);
var default_i18n_default = (/* unused pure expression or super */ null && (i18n));
const getLocaleData = i18n.getLocaleData.bind(i18n);
const setLocaleData = i18n.setLocaleData.bind(i18n);
const resetLocaleData = i18n.resetLocaleData.bind(i18n);
const subscribe = i18n.subscribe.bind(i18n);
const __ = i18n.__.bind(i18n);
const _x = i18n._x.bind(i18n);
const _n = i18n._n.bind(i18n);
const _nx = i18n._nx.bind(i18n);
const isRTL = i18n.isRTL.bind(i18n);
const hasTranslation = i18n.hasTranslation.bind(i18n);

//# sourceMappingURL=default-i18n.js.map

;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js




//# sourceMappingURL=index.js.map


/***/ })

}]);