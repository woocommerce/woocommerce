"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3696],{

/***/ "../../packages/js/components/src/compare-filter/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  S: () => (/* binding */ CompareFilter)
});

// UNUSED EXPORTS: CompareButton

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js + 7 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 3 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js + 1 modules
var tooltip = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/tooltip/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/compare-filter/button.js
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
var CompareButton = function CompareButton(_ref) {
  var className = _ref.className,
    count = _ref.count,
    children = _ref.children,
    disabled = _ref.disabled,
    helpText = _ref.helpText,
    onClick = _ref.onClick;
  return !disabled && count < 2 ? (0,react.createElement)(tooltip/* default */.A, {
    text: helpText
  }, (0,react.createElement)("span", {
    className: className
  }, (0,react.createElement)(build_module_button/* default */.A, {
    className: "woocommerce-compare-button",
    disabled: true,
    isSecondary: true
  }, children))) : (0,react.createElement)(build_module_button/* default */.A, {
    className: classnames_default()('woocommerce-compare-button', className),
    onClick: onClick,
    disabled: disabled,
    isSecondary: true
  }, children);
};
CompareButton.propTypes = {
  /**
   * Additional CSS classes.
   */
  className: (prop_types_default()).string,
  /**
   * The count of items selected.
   */
  count: (prop_types_default()).number.isRequired,
  /**
   * The button content.
   */
  children: (prop_types_default()).node.isRequired,
  /**
   * Text displayed when hovering over a disabled button.
   */
  helpText: (prop_types_default()).string.isRequired,
  /**
   * The function called when the button is clicked.
   */
  onClick: (prop_types_default()).func.isRequired,
  /**
   * Whether the control is disabled or not.
   */
  disabled: (prop_types_default()).bool
};
/* harmony default export */ const compare_filter_button = (CompareButton);
// EXTERNAL MODULE: ../../packages/js/components/src/search/index.tsx + 14 modules
var search = __webpack_require__("../../packages/js/components/src/search/index.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental.js
var experimental = __webpack_require__("../../packages/js/components/src/experimental.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/compare-filter/index.js













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
 * Internal dependencies
 */





/**
 * Displays a card + search used to filter results as a comparison between objects.
 */
var CompareFilter = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(CompareFilter, _Component);
  var _super = _createSuper(CompareFilter);
  function CompareFilter(_ref) {
    var _this;
    var getLabels = _ref.getLabels,
      param = _ref.param,
      query = _ref.query;
    (0,classCallCheck/* default */.A)(this, CompareFilter);
    _this = _super.apply(this, arguments);
    _this.state = {
      selected: []
    };
    _this.clearQuery = _this.clearQuery.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateQuery = _this.updateQuery.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateLabels = _this.updateLabels.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onButtonClicked = _this.onButtonClicked.bind((0,assertThisInitialized/* default */.A)(_this));
    if (query[param]) {
      getLabels(query[param], query).then(_this.updateLabels);
    }
    return _this;
  }
  (0,createClass/* default */.A)(CompareFilter, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(_ref2, _ref3) {
      var prevParam = _ref2.param,
        prevQuery = _ref2.query;
      var prevSelected = _ref3.selected;
      var _this$props = this.props,
        getLabels = _this$props.getLabels,
        param = _this$props.param,
        query = _this$props.query;
      var selected = this.state.selected;
      if (prevParam !== param || prevSelected.length > 0 && selected.length === 0) {
        this.clearQuery();
        return;
      }
      var prevIds = (0,src/* getIdsFromQuery */.DF)(prevQuery[param]);
      var currentIds = (0,src/* getIdsFromQuery */.DF)(query[param]);
      if (!(0,lodash.isEqual)(prevIds.sort(), currentIds.sort())) {
        getLabels(query[param], query).then(this.updateLabels);
      }
    }
  }, {
    key: "clearQuery",
    value: function clearQuery() {
      var _this$props2 = this.props,
        param = _this$props2.param,
        path = _this$props2.path,
        query = _this$props2.query;
      this.setState({
        selected: []
      });
      (0,src/* updateQueryString */.Ze)((0,defineProperty/* default */.A)({}, param, undefined), path, query);
    }
  }, {
    key: "updateLabels",
    value: function updateLabels(selected) {
      this.setState({
        selected: selected
      });
    }
  }, {
    key: "updateQuery",
    value: function updateQuery() {
      var _this$props3 = this.props,
        param = _this$props3.param,
        path = _this$props3.path,
        query = _this$props3.query;
      var selected = this.state.selected;
      var idList = selected.map(function (p) {
        return p.key;
      });
      (0,src/* updateQueryString */.Ze)((0,defineProperty/* default */.A)({}, param, idList.join(',')), path, query);
    }
  }, {
    key: "onButtonClicked",
    value: function onButtonClicked(e) {
      this.updateQuery(e);
      if ((0,lodash.isFunction)(this.props.onClick)) {
        this.props.onClick(e);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props4 = this.props,
        labels = _this$props4.labels,
        type = _this$props4.type,
        autocompleter = _this$props4.autocompleter;
      var selected = this.state.selected;
      return (0,react.createElement)(component/* default */.A, {
        className: "woocommerce-filters__compare"
      }, (0,react.createElement)(card_header_component/* default */.A, null, (0,react.createElement)(experimental/* Text */.E, {
        variant: "subtitle.small",
        weight: "600",
        size: "14",
        lineHeight: "20px"
      }, labels.title)), (0,react.createElement)(card_body_component/* default */.A, null, (0,react.createElement)(search/* default */.A, {
        autocompleter: autocompleter,
        type: type,
        selected: selected,
        placeholder: labels.placeholder,
        onChange: function onChange(value) {
          _this2.setState({
            selected: value
          });
        }
      })), (0,react.createElement)(card_footer_component/* default */.A, {
        justify: "flex-start"
      }, (0,react.createElement)(compare_filter_button, {
        count: selected.length,
        helpText: labels.helpText,
        onClick: this.onButtonClicked
      }, labels.update), selected.length > 0 && (0,react.createElement)(build_module_button/* default */.A, {
        isLink: true,
        onClick: this.clearQuery
      }, (0,build_module.__)('Clear all', 'woocommerce'))));
    }
  }]);
  return CompareFilter;
}(react.Component);
CompareFilter.propTypes = {
  /**
   * Function used to fetch object labels via an API request, returns a Promise.
   */
  getLabels: (prop_types_default()).func.isRequired,
  /**
   * Object of localized labels.
   */
  labels: prop_types_default().shape({
    /**
     * Label for the search placeholder.
     */
    placeholder: (prop_types_default()).string,
    /**
     * Label for the card title.
     */
    title: (prop_types_default()).string,
    /**
     * Label for button which updates the URL/report.
     */
    update: (prop_types_default()).string
  }),
  /**
   * The parameter to use in the querystring.
   */
  param: (prop_types_default()).string.isRequired,
  /**
   * The `path` parameter supplied by React-Router
   */
  path: (prop_types_default()).string.isRequired,
  /**
   * The query string represented in object form
   */
  query: (prop_types_default()).object,
  /**
   * Which type of autocompleter should be used in the Search
   */
  type: (prop_types_default()).string.isRequired,
  /**
   * The custom autocompleter to be forwarded to the `Search` component.
   */
  autocompleter: (prop_types_default()).object
};
CompareFilter.defaultProps = {
  labels: {},
  query: {}
};

/***/ }),

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ Text)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text/component.js");
/**
 * External dependencies
 */


/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
var Text = _wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Text || _wordpress_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A;

/***/ }),

/***/ "../../packages/js/components/src/compare-filter/stories/compare-filter.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url.js");
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url-search-params.js");
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../packages/js/components/src/compare-filter/index.js");








/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var query = {};
var compareFilter = {
  type: 'products',
  param: 'product',
  getLabels: function getLabels() {
    return Promise.resolve([]);
  },
  labels: {
    helpText: 'Select at least two products to compare',
    placeholder: 'Search for products to compare',
    title: 'Compare Products',
    update: 'Compare'
  }
};
var Basic = function Basic(_ref) {
  var _ref$path = _ref.path,
    path = _ref$path === void 0 ? new URL(document.location).searchParams.get('path') : _ref$path;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_7__.createElement)(___WEBPACK_IMPORTED_MODULE_8__/* .CompareFilter */ .S, (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)({
    path: path,
    query: query
  }, compareFilter));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'WooCommerce Admin/components/CompareFilter',
  component: ___WEBPACK_IMPORTED_MODULE_8__/* .CompareFilter */ .S
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

/***/ })

}]);