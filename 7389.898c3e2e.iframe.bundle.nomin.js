(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7389,9028,7111,964],{

/***/ "../../packages/js/components/src/advanced-filters/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ advanced_filters)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.match.js
var es_string_match = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.match.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.splice.js
var es_array_splice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.splice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js
var es_object_entries = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js
var web_timers = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js
var es_string_search = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js + 1 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/select-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js + 7 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/add-outline.js
var add_outline = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/add-outline.js");
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 3 modules
var src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/cross-small.js
var cross_small = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@17.0.2/node_modules/gridicons/dist/cross-small.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/esnext.string.replace-all.js
var esnext_string_replace_all = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/esnext.string.replace-all.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js
var es_string_trim = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js
var es_string_starts_with = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.ends-with.js
var es_string_ends_with = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.ends-with.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js
var create_interpolate_element = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@4.4.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/utils.js










/**
 * External dependencies
 */




/**
 * DOM Node.textContent for React components
 * See: https://github.com/rwu823/react-addons-text-content/blob/master/src/index.js
 *
 * @param {Array<string|Node>} components array of components
 *
 * @return {string} concatenated text content of all nodes
 */
function textContent(components) {
  var text = '';
  var toText = function toText(component) {
    if ((0,lodash.isString)(component) || (0,lodash.isNumber)(component)) {
      text += component;
    } else if ((0,lodash.isArray)(component)) {
      component.forEach(toText);
    } else if (component && component.props) {
      var children = component.props.children;
      if ((0,lodash.isArray)(children)) {
        children.forEach(toText);
      } else {
        toText(children);
      }
    }
  };
  toText(components);
  return text;
}

/**
 * This function processes an input string, checks for deprecated interpolation formatting, and
 * modifies it to conform to the new standard.
 * The deprecated interpolation formatting is `{{element}}...{{/element}}`, and the new standard
 * formatting is `<element>...</element>`.
 *
 * @param {string} interpolatedString The interpolation string to be parsed.
 *
 * @return {string}  Fixed interpolation string.
 */
function getInterpolatedString(interpolatedString) {
  var regex = /(\{\{)(\/?\s*\w+\s*\/?)(\}\})/g;
  var replacedString = interpolatedString.replaceAll(regex, function (match, p1, p2) {
    var inner = p2.trim();
    var replacement;
    if (inner.startsWith('/')) {
      // Closing tag
      replacement = "</".concat(inner.slice(1), ">");
    } else if (inner.endsWith('/')) {
      // Self-closing tag
      replacement = "<".concat(inner.slice(0, -1), "/>");
    } else {
      // Opening tag
      replacement = "<".concat(inner, ">");
    }
    return replacement;
  });
  if (replacedString !== interpolatedString) {
    (0,deprecated_build_module/* default */.A)('Old interpolation string format `{{element}}...{{/element}}` or `{{element/}}`', {
      since: '7.8',
      alternative: 'new interpolation string format `<element>...</element>` or `<element/>`',
      link: 'https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/components/src/advanced-filters/README.md'
    });
  }
  return replacedString;
}

/**
 * This function creates an interpolation element that is backwards compatible.
 *
 * @param {string} interpolatedString The interpolation string to be parsed and transformed.
 * @param {Object} conversionMap      The map used for the conversion to create the interpolate element.
 *
 * @return {Element} A React element that is the result of applying the transformation.
 */
function backwardsCompatibleCreateInterpolateElement(interpolatedString, conversionMap) {
  return (0,create_interpolate_element/* default */.A)(getInterpolatedString(interpolatedString), conversionMap);
}
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/select-filter.js










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

var SelectFilter = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(SelectFilter, _Component);
  var _super = _createSuper(SelectFilter);
  function SelectFilter(_ref) {
    var _this;
    var filter = _ref.filter,
      config = _ref.config,
      onFilterChange = _ref.onFilterChange;
    (0,classCallCheck/* default */.A)(this, SelectFilter);
    _this = _super.apply(this, arguments);
    var options = config.input.options;
    _this.state = {
      options: options
    };
    _this.updateOptions = _this.updateOptions.bind((0,assertThisInitialized/* default */.A)(_this));
    if (!options && config.input.getOptions) {
      config.input.getOptions().then(_this.updateOptions).then(function (returnedOptions) {
        if (!filter.value) {
          var value = (0,src/* getDefaultOptionValue */.Am)(config, returnedOptions);
          onFilterChange({
            property: 'value',
            value: value
          });
        }
      });
    }
    return _this;
  }
  (0,createClass/* default */.A)(SelectFilter, [{
    key: "updateOptions",
    value: function updateOptions(options) {
      this.setState({
        options: options
      });
      return options;
    }
  }, {
    key: "getScreenReaderText",
    value: function getScreenReaderText(filter, config) {
      if (filter.value === '') {
        return '';
      }
      var rule = (0,lodash.find)(config.rules, {
        value: filter.rule
      }) || {};
      var value = (0,lodash.find)(config.input.options, {
        value: filter.value
      }) || {};
      return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
        filter: (0,react.createElement)(react.Fragment, null, value.label),
        rule: (0,react.createElement)(react.Fragment, null, rule.label),
        title: (0,react.createElement)(react.Fragment, null)
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
        className = _this$props.className,
        config = _this$props.config,
        filter = _this$props.filter,
        onFilterChange = _this$props.onFilterChange,
        isEnglish = _this$props.isEnglish;
      var options = this.state.options;
      var rule = filter.rule,
        value = filter.value;
      var labels = config.labels,
        rules = config.rules;
      var children = backwardsCompatibleCreateInterpolateElement(labels.title, {
        title: (0,react.createElement)("span", {
          className: className
        }),
        rule: (0,react.createElement)(select_control/* default */.A, {
          className: classnames_default()(className, 'woocommerce-filters-advanced__rule'),
          options: rules,
          value: rule,
          onChange: function onChange(selectedValue) {
            return onFilterChange({
              property: 'rule',
              value: selectedValue
            });
          },
          "aria-label": labels.rule
        }),
        filter: options ? (0,react.createElement)(select_control/* default */.A, {
          className: classnames_default()(className, 'woocommerce-filters-advanced__input'),
          options: options,
          value: value,
          onChange: function onChange(selectedValue) {
            return onFilterChange({
              property: 'value',
              value: selectedValue
            });
          },
          "aria-label": labels.filter
        }) : (0,react.createElement)(spinner/* default */.A, null)
      });
      var screenReaderText = this.getScreenReaderText(filter, config);

      /*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
      return (0,react.createElement)("fieldset", {
        className: "woocommerce-filters-advanced__line-item",
        tabIndex: "0"
      }, (0,react.createElement)("legend", {
        className: "screen-reader-text"
      }, labels.add || ''), (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        })
      }, children), screenReaderText && (0,react.createElement)("span", {
        className: "screen-reader-text"
      }, screenReaderText));
      /*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
    }
  }]);
  return SelectFilter;
}(react.Component);
SelectFilter.propTypes = {
  /**
   * The configuration object for the single filter to be rendered.
   */
  config: prop_types_default().shape({
    labels: prop_types_default().shape({
      rule: (prop_types_default()).string,
      title: (prop_types_default()).string,
      filter: (prop_types_default()).string
    }),
    rules: prop_types_default().arrayOf((prop_types_default()).object),
    input: (prop_types_default()).object
  }).isRequired,
  /**
   * The activeFilter handed down by AdvancedFilters.
   */
  filter: prop_types_default().shape({
    key: (prop_types_default()).string,
    rule: (prop_types_default()).string,
    value: (prop_types_default()).string
  }).isRequired,
  /**
   * Function to be called on update.
   */
  onFilterChange: (prop_types_default()).func.isRequired
};
/* harmony default export */ const select_filter = (SelectFilter);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.every.js
var es_array_every = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.every.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../packages/js/components/src/search/index.tsx + 14 modules
var search = __webpack_require__("../../packages/js/components/src/search/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/search-filter.js
















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









function search_filter_createSuper(Derived) {
  var hasNativeReflectConstruct = search_filter_isNativeReflectConstruct();
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
function search_filter_isNativeReflectConstruct() {
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


var SearchFilter = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(SearchFilter, _Component);
  var _super = search_filter_createSuper(SearchFilter);
  function SearchFilter(_ref) {
    var _this;
    var filter = _ref.filter,
      config = _ref.config,
      query = _ref.query;
    (0,classCallCheck/* default */.A)(this, SearchFilter);
    _this = _super.apply(this, arguments);
    _this.onSearchChange = _this.onSearchChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.state = {
      selected: []
    };
    _this.updateLabels = _this.updateLabels.bind((0,assertThisInitialized/* default */.A)(_this));
    if (filter.value.length) {
      config.input.getLabels(filter.value, query).then(function (selected) {
        var selectedWithKeys = selected.map(function (s) {
          return _objectSpread({
            key: s.id
          }, s);
        });
        _this.updateLabels(selectedWithKeys);
      });
    }
    return _this;
  }
  (0,createClass/* default */.A)(SearchFilter, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
        config = _this$props.config,
        filter = _this$props.filter,
        query = _this$props.query;
      var prevFilter = prevProps.filter;
      if (filter.value.length && !(0,lodash.isEqual)(prevFilter, filter)) {
        var selected = this.state.selected;
        var ids = selected.map(function (item) {
          return item.key;
        });
        var filterIds = (0,src/* getIdsFromQuery */.DF)(filter.value);
        var hasNewIds = filterIds.every(function (id) {
          return !ids.includes(id);
        });
        if (hasNewIds) {
          config.input.getLabels(filter.value, query).then(this.updateLabels);
        }
      }
    }
  }, {
    key: "updateLabels",
    value: function updateLabels(selected) {
      var prevIds = this.state.selected.map(function (item) {
        return item.key;
      });
      var ids = selected.map(function (item) {
        return item.key;
      });
      if (!(0,lodash.isEqual)(ids.sort(), prevIds.sort())) {
        this.setState({
          selected: selected
        });
      }
    }
  }, {
    key: "onSearchChange",
    value: function onSearchChange(values) {
      this.setState({
        selected: values
      });
      var onFilterChange = this.props.onFilterChange;
      var idList = values.map(function (value) {
        return value.key;
      }).join(',');
      onFilterChange({
        property: 'value',
        value: idList
      });
    }
  }, {
    key: "getScreenReaderText",
    value: function getScreenReaderText(filter, config) {
      var selected = this.state.selected;
      if (selected.length === 0) {
        return '';
      }
      var rule = (0,lodash.find)(config.rules, {
        value: filter.rule
      }) || {};
      var filterStr = selected.map(function (item) {
        return item.label;
      }).join(', ');
      return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
        filter: (0,react.createElement)(react.Fragment, null, filterStr),
        rule: (0,react.createElement)(react.Fragment, null, rule.label),
        title: (0,react.createElement)(react.Fragment, null)
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
        className = _this$props2.className,
        config = _this$props2.config,
        filter = _this$props2.filter,
        onFilterChange = _this$props2.onFilterChange,
        isEnglish = _this$props2.isEnglish;
      var selected = this.state.selected;
      var rule = filter.rule;
      var input = config.input,
        labels = config.labels,
        rules = config.rules;
      var children = backwardsCompatibleCreateInterpolateElement(labels.title, {
        title: (0,react.createElement)("span", {
          className: className
        }),
        rule: (0,react.createElement)(select_control/* default */.A, {
          className: classnames_default()(className, 'woocommerce-filters-advanced__rule'),
          options: rules,
          value: rule,
          onChange: function onChange(value) {
            return onFilterChange({
              property: 'rule',
              value: value
            });
          },
          "aria-label": labels.rule
        }),
        filter: (0,react.createElement)(search/* default */.A, {
          className: classnames_default()(className, 'woocommerce-filters-advanced__input'),
          onChange: this.onSearchChange,
          type: input.type,
          autocompleter: input.autocompleter,
          placeholder: labels.placeholder,
          selected: selected,
          inlineTags: true,
          "aria-label": labels.filter
        })
      });
      var screenReaderText = this.getScreenReaderText(filter, config);

      /*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
      return (0,react.createElement)("fieldset", {
        className: "woocommerce-filters-advanced__line-item",
        tabIndex: "0"
      }, (0,react.createElement)("legend", {
        className: "screen-reader-text"
      }, labels.add || ''), (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        })
      }, children), screenReaderText && (0,react.createElement)("span", {
        className: "screen-reader-text"
      }, screenReaderText));
      /*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
    }
  }]);
  return SearchFilter;
}(react.Component);
SearchFilter.propTypes = {
  /**
   * The configuration object for the single filter to be rendered.
   */
  config: prop_types_default().shape({
    labels: prop_types_default().shape({
      placeholder: (prop_types_default()).string,
      rule: (prop_types_default()).string,
      title: (prop_types_default()).string
    }),
    rules: prop_types_default().arrayOf((prop_types_default()).object),
    input: (prop_types_default()).object
  }).isRequired,
  /**
   * The activeFilter handed down by AdvancedFilters.
   */
  filter: prop_types_default().shape({
    key: (prop_types_default()).string,
    rule: (prop_types_default()).string,
    value: (prop_types_default()).string
  }).isRequired,
  /**
   * Function to be called on update.
   */
  onFilterChange: (prop_types_default()).func.isRequired,
  /**
   * The query string represented in object form.
   */
  query: (prop_types_default()).object
};
/* harmony default export */ const search_filter = (SearchFilter);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../packages/js/currency/src/index.ts + 3 modules
var currency_src = __webpack_require__("../../packages/js/currency/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/components/src/text-control-with-affixes/index.js
var text_control_with_affixes = __webpack_require__("../../packages/js/components/src/text-control-with-affixes/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/number-filter.js










function number_filter_createSuper(Derived) {
  var hasNativeReflectConstruct = number_filter_isNativeReflectConstruct();
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
function number_filter_isNativeReflectConstruct() {
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


var NumberFilter = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(NumberFilter, _Component);
  var _super = number_filter_createSuper(NumberFilter);
  function NumberFilter() {
    (0,classCallCheck/* default */.A)(this, NumberFilter);
    return _super.apply(this, arguments);
  }
  (0,createClass/* default */.A)(NumberFilter, [{
    key: "getBetweenString",
    value: function getBetweenString() {
      return (0,build_module._x)('<rangeStart/><span> and </span><rangeEnd/>', 'Numerical range inputs arranged on a single line', 'woocommerce');
    }
  }, {
    key: "getScreenReaderText",
    value: function getScreenReaderText(filter, config) {
      var currency = this.props.currency;
      var rule = (0,lodash.find)(config.rules, {
        value: filter.rule
      }) || {};
      var _ref = (0,lodash.isArray)(filter.value) ? filter.value : [filter.value],
        _ref2 = (0,slicedToArray/* default */.A)(_ref, 2),
        rangeStart = _ref2[0],
        rangeEnd = _ref2[1];

      // Return nothing if we're missing input(s)
      if (!rangeStart || rule.value === 'between' && !rangeEnd) {
        return '';
      }
      var inputType = (0,lodash.get)(config, ['input', 'type'], 'number');
      if (inputType === 'currency') {
        var _CurrencyFactory = (0,currency_src/* CurrencyFactory */.uU)(currency),
          formatAmount = _CurrencyFactory.formatAmount;
        rangeStart = formatAmount(rangeStart);
        rangeEnd = formatAmount(rangeEnd);
      }
      var filterStr = rangeStart;
      if (rule.value === 'between') {
        filterStr = backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
          rangeStart: (0,react.createElement)(react.Fragment, null, rangeStart),
          rangeEnd: (0,react.createElement)(react.Fragment, null, rangeEnd),
          span: (0,react.createElement)(react.Fragment, null)
        });
      }
      return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
        filter: (0,react.createElement)(react.Fragment, null, filterStr),
        rule: (0,react.createElement)(react.Fragment, null, rule.label),
        title: (0,react.createElement)(react.Fragment, null)
      }));
    }
  }, {
    key: "getFormControl",
    value: function getFormControl(_ref3) {
      var type = _ref3.type,
        value = _ref3.value,
        label = _ref3.label,
        onChange = _ref3.onChange,
        currencySymbol = _ref3.currencySymbol,
        symbolPosition = _ref3.symbolPosition;
      if (type === 'currency') {
        return symbolPosition.indexOf('right') === 0 ? (0,react.createElement)(text_control_with_affixes/* default */.A, {
          suffix: (0,react.createElement)("span", null, currencySymbol),
          className: "woocommerce-filters-advanced__input",
          type: "number",
          value: value || '',
          "aria-label": label,
          onChange: onChange
        }) : (0,react.createElement)(text_control_with_affixes/* default */.A, {
          prefix: (0,react.createElement)("span", null, currencySymbol),
          className: "woocommerce-filters-advanced__input",
          type: "number",
          value: value || '',
          "aria-label": label,
          onChange: onChange
        });
      }
      return (0,react.createElement)(text_control/* default */.A, {
        className: "woocommerce-filters-advanced__input",
        type: "number",
        value: value || '',
        "aria-label": label,
        onChange: onChange
      });
    }
  }, {
    key: "getFilterInputs",
    value: function getFilterInputs() {
      var _this$props = this.props,
        config = _this$props.config,
        filter = _this$props.filter,
        onFilterChange = _this$props.onFilterChange,
        currency = _this$props.currency;
      var currencySymbol = currency.symbol,
        symbolPosition = currency.symbolPosition;
      if (filter.rule === 'between') {
        return this.getRangeInput();
      }
      var inputType = (0,lodash.get)(config, ['input', 'type'], 'number');
      var _ref4 = (0,lodash.isArray)(filter.value) ? filter.value : [filter.value],
        _ref5 = (0,slicedToArray/* default */.A)(_ref4, 2),
        rangeStart = _ref5[0],
        rangeEnd = _ref5[1];
      if (Boolean(rangeEnd)) {
        // If there's a value for rangeEnd, we've just changed from "between"
        // to "less than" or "more than" and need to transition the value
        onFilterChange({
          property: 'value',
          value: rangeStart || rangeEnd
        });
      }
      var labelFormat = '';
      if (filter.rule === 'lessthan') {
        /* translators: Sentence fragment, "maximum amount" refers to a numeric value the field must be less than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
        labelFormat = (0,build_module._x)('%(field)s maximum amount', 'maximum value input', 'woocommerce');
      } else {
        /* translators: Sentence fragment, "minimum amount" refers to a numeric value the field must be more than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
        labelFormat = (0,build_module._x)('%(field)s minimum amount', 'minimum value input', 'woocommerce');
      }
      return this.getFormControl({
        type: inputType,
        value: rangeStart || rangeEnd,
        label: (0,build_module/* sprintf */.nv)(labelFormat, {
          field: (0,lodash.get)(config, ['labels', 'add'])
        }),
        onChange: function onChange(value) {
          return onFilterChange({
            property: 'value',
            value: value
          });
        },
        currencySymbol: currencySymbol,
        symbolPosition: symbolPosition
      });
    }
  }, {
    key: "getRangeInput",
    value: function getRangeInput() {
      var _this$props2 = this.props,
        config = _this$props2.config,
        filter = _this$props2.filter,
        onFilterChange = _this$props2.onFilterChange,
        currency = _this$props2.currency;
      var currencySymbol = currency.symbol,
        symbolPosition = currency.symbolPosition;
      var inputType = (0,lodash.get)(config, ['input', 'type'], 'number');
      var _ref6 = (0,lodash.isArray)(filter.value) ? filter.value : [filter.value],
        _ref7 = (0,slicedToArray/* default */.A)(_ref6, 2),
        rangeStart = _ref7[0],
        rangeEnd = _ref7[1];
      var rangeStartOnChange = function rangeStartOnChange(newRangeStart) {
        onFilterChange({
          property: 'value',
          key: [newRangeStart, rangeEnd]
        });
      };
      var rangeEndOnChange = function rangeEndOnChange(newRangeEnd) {
        onFilterChange({
          property: 'value',
          key: [rangeStart, newRangeEnd]
        });
      };
      return backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
        rangeStart: this.getFormControl({
          type: inputType,
          value: rangeStart || '',
          label: (0,build_module/* sprintf */.nv)( /* translators: Sentence fragment, "range start" refers to the first of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
          (0,build_module.__)('%(field)s range start', 'woocommerce'), {
            field: (0,lodash.get)(config, ['labels', 'add'])
          }),
          onChange: rangeStartOnChange,
          currencySymbol: currencySymbol,
          symbolPosition: symbolPosition
        }),
        rangeEnd: this.getFormControl({
          type: inputType,
          value: rangeEnd || '',
          label: (0,build_module/* sprintf */.nv)( /* translators: Sentence fragment, "range end" refers to the second of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
          (0,build_module.__)('%(field)s range end', 'woocommerce'), {
            field: (0,lodash.get)(config, ['labels', 'add'])
          }),
          onChange: rangeEndOnChange,
          currencySymbol: currencySymbol,
          symbolPosition: symbolPosition
        }),
        span: (0,react.createElement)("span", {
          className: "separator"
        })
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
        className = _this$props3.className,
        config = _this$props3.config,
        filter = _this$props3.filter,
        onFilterChange = _this$props3.onFilterChange,
        isEnglish = _this$props3.isEnglish;
      var rule = filter.rule;
      var labels = config.labels,
        rules = config.rules;
      var children = backwardsCompatibleCreateInterpolateElement(labels.title, {
        title: (0,react.createElement)("span", {
          className: className
        }),
        rule: (0,react.createElement)(select_control/* default */.A, {
          className: classnames_default()(className, 'woocommerce-filters-advanced__rule'),
          options: rules,
          value: rule,
          onChange: function onChange(value) {
            return onFilterChange({
              property: 'rule',
              value: value
            });
          },
          "aria-label": labels.rule
        }),
        filter: (0,react.createElement)("div", {
          className: classnames_default()(className, 'woocommerce-filters-advanced__input-range', {
            'is-between': rule === 'between'
          })
        }, this.getFilterInputs())
      });
      var screenReaderText = this.getScreenReaderText(filter, config);

      /*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
      return (0,react.createElement)("fieldset", {
        className: "woocommerce-filters-advanced__line-item",
        tabIndex: "0"
      }, (0,react.createElement)("legend", {
        className: "screen-reader-text"
      }, labels.add || ''), (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        })
      }, children), screenReaderText && (0,react.createElement)("span", {
        className: "screen-reader-text"
      }, screenReaderText));
      /*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
    }
  }]);
  return NumberFilter;
}(react.Component);
/* harmony default export */ const number_filter = (NumberFilter);
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var date_src = __webpack_require__("../../packages/js/date/src/index.ts");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../packages/js/components/src/calendar/date-picker.js
var date_picker = __webpack_require__("../../packages/js/components/src/calendar/date-picker.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/date-filter.js

















function date_filter_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function date_filter_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? date_filter_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : date_filter_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}






function date_filter_createSuper(Derived) {
  var hasNativeReflectConstruct = date_filter_isNativeReflectConstruct();
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
function date_filter_isNativeReflectConstruct() {
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


var dateStringFormat = (0,build_module.__)('MMM D, YYYY', 'woocommerce');
var dateFormat = (0,build_module.__)('MM/DD/YYYY', 'woocommerce');
var DateFilter = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(DateFilter, _Component);
  var _super = date_filter_createSuper(DateFilter);
  function DateFilter(_ref) {
    var _this;
    var filter = _ref.filter;
    (0,classCallCheck/* default */.A)(this, DateFilter);
    _this = _super.apply(this, arguments);
    var _ref2 = Array.isArray(filter.value) ? filter.value : [null, filter.value],
      _ref3 = (0,slicedToArray/* default */.A)(_ref2, 2),
      isoAfter = _ref3[0],
      isoBefore = _ref3[1];
    var after = isoAfter ? (0,date_src/* toMoment */.sf)(date_src/* isoDateFormat */.r3, isoAfter) : null;
    var before = isoBefore ? (0,date_src/* toMoment */.sf)(date_src/* isoDateFormat */.r3, isoBefore) : null;
    _this.state = {
      before: before,
      beforeText: before ? before.format(dateFormat) : '',
      beforeError: null,
      after: after,
      afterText: after ? after.format(dateFormat) : '',
      afterError: null,
      rule: filter.rule
    };
    _this.onSingleDateChange = _this.onSingleDateChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onRangeDateChange = _this.onRangeDateChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onRuleChange = _this.onRuleChange.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(DateFilter, [{
    key: "getBetweenString",
    value: function getBetweenString() {
      return (0,build_module._x)('<after/><span> and </span><before/>', 'Date range inputs arranged on a single line', 'woocommerce');
    }
  }, {
    key: "getScreenReaderText",
    value: function getScreenReaderText(filterRule, config) {
      var rule = (0,lodash.find)(config.rules, {
        value: filterRule
      }) || {};
      var _this$state = this.state,
        before = _this$state.before,
        after = _this$state.after;

      // Return nothing if we're missing input(s)
      if (!before || rule.value === 'between' && !after) {
        return '';
      }
      var filterStr = before.format(dateStringFormat);
      if (rule.value === 'between') {
        filterStr = backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
          after: (0,react.createElement)(react.Fragment, null, after.format(dateStringFormat)),
          before: (0,react.createElement)(react.Fragment, null, before.format(dateStringFormat)),
          span: (0,react.createElement)(react.Fragment, null)
        });
      }
      return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
        filter: (0,react.createElement)(react.Fragment, null, filterStr),
        rule: (0,react.createElement)(react.Fragment, null, rule.label),
        title: (0,react.createElement)(react.Fragment, null)
      }));
    }
  }, {
    key: "onSingleDateChange",
    value: function onSingleDateChange(_ref4) {
      var date = _ref4.date,
        text = _ref4.text,
        error = _ref4.error;
      var onFilterChange = this.props.onFilterChange;
      this.setState({
        before: date,
        beforeText: text,
        beforeError: error
      });
      if (date) {
        onFilterChange({
          property: 'value',
          value: date.format(date_src/* isoDateFormat */.r3)
        });
      }
    }
  }, {
    key: "onRangeDateChange",
    value: function onRangeDateChange(input, _ref5) {
      var date = _ref5.date,
        text = _ref5.text,
        error = _ref5.error;
      var onFilterChange = this.props.onFilterChange;
      this.setState((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)({}, input, date), input + 'Text', text), input + 'Error', error));
      if (date) {
        var _this$state2 = this.state,
          before = _this$state2.before,
          after = _this$state2.after;
        var nextAfter = null;
        var nextBefore = null;
        if (input === 'after') {
          nextAfter = date.format(date_src/* isoDateFormat */.r3);
          nextBefore = before ? before.format(date_src/* isoDateFormat */.r3) : null;
        }
        if (input === 'before') {
          nextAfter = after ? after.format(date_src/* isoDateFormat */.r3) : null;
          nextBefore = date.format(date_src/* isoDateFormat */.r3);
        }
        if (nextAfter && nextBefore) {
          onFilterChange({
            property: 'value',
            value: [nextAfter, nextBefore]
          });
        }
      }
    }
  }, {
    key: "onRuleChange",
    value: function onRuleChange(newRule) {
      var onFilterChange = this.props.onFilterChange;
      var rule = this.state.rule;
      var newDateState = null;
      var shouldResetValue = false;
      if ([rule, newRule].includes('between')) {
        newDateState = {
          before: null,
          beforeText: '',
          beforeError: null,
          after: null,
          afterText: '',
          afterError: null
        };
        shouldResetValue = true;
      }
      this.setState(date_filter_objectSpread({
        rule: newRule
      }, newDateState));
      onFilterChange({
        property: 'rule',
        value: newRule,
        shouldResetValue: shouldResetValue
      });
    }
  }, {
    key: "isFutureDate",
    value: function isFutureDate(dateString) {
      return moment_default()().isBefore(moment_default()(dateString), 'day');
    }
  }, {
    key: "getFormControl",
    value: function getFormControl(_ref6) {
      var date = _ref6.date,
        error = _ref6.error,
        onUpdate = _ref6.onUpdate,
        text = _ref6.text;
      return (0,react.createElement)(date_picker/* default */.A, {
        date: date,
        dateFormat: dateFormat,
        error: error,
        isInvalidDate: this.isFutureDate,
        onUpdate: onUpdate,
        text: text
      });
    }
  }, {
    key: "getRangeInput",
    value: function getRangeInput() {
      var _this$state3 = this.state,
        before = _this$state3.before,
        beforeText = _this$state3.beforeText,
        beforeError = _this$state3.beforeError,
        after = _this$state3.after,
        afterText = _this$state3.afterText,
        afterError = _this$state3.afterError;
      return backwardsCompatibleCreateInterpolateElement(this.getBetweenString(), {
        after: this.getFormControl({
          date: after,
          error: afterError,
          onUpdate: (0,lodash.partial)(this.onRangeDateChange, 'after'),
          text: afterText
        }),
        before: this.getFormControl({
          date: before,
          error: beforeError,
          onUpdate: (0,lodash.partial)(this.onRangeDateChange, 'before'),
          text: beforeText
        }),
        span: (0,react.createElement)("span", {
          className: "separator"
        })
      });
    }
  }, {
    key: "getFilterInputs",
    value: function getFilterInputs() {
      var _this$state4 = this.state,
        before = _this$state4.before,
        beforeText = _this$state4.beforeText,
        beforeError = _this$state4.beforeError,
        rule = _this$state4.rule;
      if (rule === 'between') {
        return this.getRangeInput();
      }
      return this.getFormControl({
        date: before,
        error: beforeError,
        onUpdate: this.onSingleDateChange,
        text: beforeText
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
        className = _this$props.className,
        config = _this$props.config,
        isEnglish = _this$props.isEnglish;
      var rule = this.state.rule;
      var labels = config.labels,
        rules = config.rules;
      var screenReaderText = this.getScreenReaderText(rule, config);
      var children = backwardsCompatibleCreateInterpolateElement(labels.title, {
        title: (0,react.createElement)("span", {
          className: className
        }),
        rule: (0,react.createElement)(select_control/* default */.A, {
          className: classnames_default()(className, 'woocommerce-filters-advanced__rule'),
          options: rules,
          value: rule,
          onChange: this.onRuleChange,
          "aria-label": labels.rule
        }),
        filter: (0,react.createElement)("div", {
          className: classnames_default()(className, 'woocommerce-filters-advanced__input-range', {
            'is-between': rule === 'between'
          })
        }, this.getFilterInputs())
      });
      /*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
      return (0,react.createElement)("fieldset", {
        className: "woocommerce-filters-advanced__line-item",
        tabIndex: "0"
      }, (0,react.createElement)("legend", {
        className: "screen-reader-text"
      }, labels.add || ''), (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-filters-advanced__fieldset', {
          'is-english': isEnglish
        })
      }, children), screenReaderText && (0,react.createElement)("span", {
        className: "screen-reader-text"
      }, screenReaderText));
      /*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
    }
  }]);
  return DateFilter;
}(react.Component);
/* harmony default export */ const date_filter = (DateFilter);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+api-fetch@6.3.1/node_modules/@wordpress/api-fetch/build-module/index.js + 12 modules
var api_fetch_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+api-fetch@6.3.1/node_modules/@wordpress/api-fetch/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var src_select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/attribute-filter.js









/**
 * External dependencies
 */







/**
 * Internal dependencies
 */



var getScreenReaderText = function getScreenReaderText(_ref) {
  var attributeTerms = _ref.attributeTerms,
    config = _ref.config,
    filter = _ref.filter,
    selectedAttribute = _ref.selectedAttribute,
    selectedAttributeTerm = _ref.selectedAttributeTerm;
  if (!attributeTerms || attributeTerms.length === 0 || !selectedAttribute || selectedAttribute.length === 0 || selectedAttributeTerm === '') {
    return '';
  }
  var rule = Array.isArray(config.rules) ? config.rules.find(function (configRule) {
    return configRule.value === filter.rule;
  }) || {} : {};
  var attributeName = selectedAttribute[0].label;
  var termObject = attributeTerms.find(function (_ref2) {
    var key = _ref2.key;
    return key === selectedAttributeTerm;
  });
  var attributeTerm = termObject && termObject.label;
  if (!attributeName || !attributeTerm) {
    return '';
  }
  var filterStr = backwardsCompatibleCreateInterpolateElement( /* eslint-disable-next-line max-len */
  /* translators: Sentence fragment describing a product attribute match. Example: "Color Is Not Blue" - attribute = Color, equals = Is Not, value = Blue */
  (0,build_module.__)('<attribute/> <equals/> <value/>', 'woocommerce'), {
    attribute: (0,react.createElement)(react.Fragment, null, attributeName),
    equals: (0,react.createElement)(react.Fragment, null, rule.label),
    value: (0,react.createElement)(react.Fragment, null, attributeTerm)
  });
  return textContent(backwardsCompatibleCreateInterpolateElement(config.labels.title, {
    filter: (0,react.createElement)(react.Fragment, null, filterStr),
    rule: (0,react.createElement)(react.Fragment, null),
    title: (0,react.createElement)(react.Fragment, null)
  }));
};
var AttributeFilter = function AttributeFilter(props) {
  var className = props.className,
    config = props.config,
    filter = props.filter,
    isEnglish = props.isEnglish,
    onFilterChange = props.onFilterChange;
  var rule = filter.rule,
    value = filter.value;
  var labels = config.labels,
    rules = config.rules;
  var _useState = (0,react.useState)([]),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    selectedAttribute = _useState2[0],
    setSelectedAttribute = _useState2[1];

  // Set selected attribute from filter value (in query string).
  (0,react.useEffect)(function () {
    if (!selectedAttribute.length && Array.isArray(value) && value[0]) {
      (0,api_fetch_build_module/* default */.A)({
        path: "/wc-analytics/products/attributes/".concat(value[0])
      }).then(function (_ref3) {
        var id = _ref3.id,
          name = _ref3.name;
        return [{
          key: id.toString(),
          label: name
        }];
      }).then(setSelectedAttribute);
    }
  }, [value, selectedAttribute]);
  var _useState3 = (0,react.useState)([]),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    attributeTerms = _useState4[0],
    setAttributeTerms = _useState4[1];

  // Fetch all product attributes on mount.
  (0,react.useEffect)(function () {
    if (!selectedAttribute.length) {
      return;
    }
    setAttributeTerms(false);
    (0,api_fetch_build_module/* default */.A)({
      path: "/wc-analytics/products/attributes/".concat(selectedAttribute[0].key, "/terms?per_page=100")
    }).then(function (terms) {
      return terms.map(function (_ref4) {
        var id = _ref4.id,
          name = _ref4.name;
        return {
          key: id.toString(),
          label: name
        };
      });
    }).then(setAttributeTerms);
  }, [selectedAttribute]);
  var _useState5 = (0,react.useState)(Array.isArray(value) ? value[1] || '' : ''),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    selectedAttributeTerm = _useState6[0],
    setSelectedAttributeTerm = _useState6[1];
  var screenReaderText = getScreenReaderText({
    attributeTerms: attributeTerms,
    config: config,
    filter: filter,
    selectedAttribute: selectedAttribute,
    selectedAttributeTerm: selectedAttributeTerm
  });

  /*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
  return (0,react.createElement)("fieldset", {
    className: "woocommerce-filters-advanced__line-item",
    tabIndex: "0"
  }, (0,react.createElement)("legend", {
    className: "screen-reader-text"
  }, labels.add || ''), (0,react.createElement)("div", {
    className: classnames_default()('woocommerce-filters-advanced__fieldset', {
      'is-english': isEnglish
    })
  }, backwardsCompatibleCreateInterpolateElement(labels.title, {
    title: (0,react.createElement)("span", {
      className: className
    }),
    rule: (0,react.createElement)(select_control/* default */.A, {
      className: classnames_default()(className, 'woocommerce-filters-advanced__rule'),
      options: rules,
      value: rule,
      onChange: function onChange(selectedValue) {
        return onFilterChange({
          property: 'rule',
          value: selectedValue
        });
      },
      "aria-label": labels.rule
    }),
    filter: (0,react.createElement)("div", {
      className: classnames_default()(className, 'woocommerce-filters-advanced__attribute-fieldset')
    }, !Array.isArray(value) || !value.length || selectedAttribute.length ? (0,react.createElement)(search/* default */.A, {
      className: "woocommerce-filters-advanced__input woocommerce-search",
      onChange: function onChange(_ref5) {
        var _ref6 = (0,slicedToArray/* default */.A)(_ref5, 1),
          attr = _ref6[0];
        setSelectedAttribute(attr ? [attr] : []);
        setSelectedAttributeTerm('');
        onFilterChange({
          property: 'value',
          value: [attr && attr.key].filter(Boolean)
        });
      },
      type: "attributes",
      placeholder: (0,build_module.__)('Attribute name', 'woocommerce'),
      multiple: false,
      selected: selectedAttribute,
      inlineTags: true,
      "aria-label": (0,build_module.__)('Attribute name', 'woocommerce')
    }) : (0,react.createElement)(spinner/* default */.A, null), selectedAttribute.length > 0 && (attributeTerms.length ? (0,react.createElement)(react.Fragment, null, (0,react.createElement)("span", {
      className: "woocommerce-filters-advanced__attribute-field-separator"
    }, "="), (0,react.createElement)(src_select_control/* default */.A, {
      className: "woocommerce-filters-advanced__input woocommerce-search",
      placeholder: (0,build_module.__)('Attribute value', 'woocommerce'),
      inlineTags: true,
      isSearchable: true,
      multiple: false,
      showAllOnFocus: true,
      options: attributeTerms,
      selected: selectedAttributeTerm,
      onChange: function onChange(term) {
        // Clearing the input using delete/backspace causes an empty array to be passed here.
        if (typeof term !== 'string') {
          term = '';
        }
        setSelectedAttributeTerm(term);
        onFilterChange({
          property: 'value',
          value: [selectedAttribute[0].key, term].filter(Boolean)
        });
      }
    })) : (0,react.createElement)(spinner/* default */.A, null)))
  })), screenReaderText && (0,react.createElement)("span", {
    className: "screen-reader-text"
  }, screenReaderText));
  /*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
};
AttributeFilter.propTypes = {
  /**
   * The configuration object for the single filter to be rendered.
   */
  config: prop_types_default().shape({
    labels: prop_types_default().shape({
      rule: (prop_types_default()).string,
      title: (prop_types_default()).string,
      filter: (prop_types_default()).string
    }),
    rules: prop_types_default().arrayOf((prop_types_default()).object),
    input: (prop_types_default()).object
  }).isRequired,
  /**
   * The activeFilter handed down by AdvancedFilters.
   */
  filter: prop_types_default().shape({
    key: (prop_types_default()).string,
    rule: (prop_types_default()).string,
    value: prop_types_default().arrayOf(prop_types_default().oneOfType([(prop_types_default()).string, (prop_types_default()).number]))
  }).isRequired,
  /**
   * Function to be called on update.
   */
  onFilterChange: (prop_types_default()).func.isRequired
};
/* harmony default export */ const attribute_filter = (AttributeFilter);
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/item.js

function item_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function item_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? item_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : item_ownKeys(Object(t)).forEach(function (r) {
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





var AdvancedFilterItem = function AdvancedFilterItem(props) {
  var config = props.config,
    currency = props.currency,
    filterValue = props.filter,
    isEnglish = props.isEnglish,
    onFilterChange = props.onFilterChange,
    query = props.query,
    removeFilter = props.removeFilter;
  var key = filterValue.key;
  var filterConfig = config.filters[key];
  var _filterConfig = filterConfig,
    input = _filterConfig.input,
    labels = _filterConfig.labels;
  var componentMap = {
    Currency: number_filter,
    Date: date_filter,
    Number: number_filter,
    ProductAttribute: attribute_filter,
    Search: search_filter,
    SelectControl: select_filter
  };
  if (!componentMap.hasOwnProperty(input.component)) {
    return;
  }
  if (input.component === 'Currency') {
    filterConfig = item_objectSpread(item_objectSpread({}, filterConfig), {
      input: {
        type: 'currency',
        component: 'Currency'
      }
    });
  }
  var FilterComponent = componentMap[input.component];
  return (0,react.createElement)("li", {
    className: "woocommerce-filters-advanced__list-item"
  }, (0,react.createElement)(FilterComponent, {
    className: "woocommerce-filters-advanced__fieldset-item",
    currency: currency,
    filter: filterValue,
    config: filterConfig,
    onFilterChange: onFilterChange,
    isEnglish: isEnglish,
    query: query
  }), (0,react.createElement)(build_module_button/* default */.A, {
    className: classnames_default()('woocommerce-filters-advanced__line-item', 'woocommerce-filters-advanced__remove'),
    label: labels.remove,
    onClick: removeFilter
  }, (0,react.createElement)(cross_small/* default */.A, null)));
};
/* harmony default export */ const item = (AdvancedFilterItem);
// EXTERNAL MODULE: ../../packages/js/components/src/experimental.js
var experimental = __webpack_require__("../../packages/js/components/src/experimental.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/advanced-filters/index.js


















function advanced_filters_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function advanced_filters_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? advanced_filters_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : advanced_filters_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
















function advanced_filters_createSuper(Derived) {
  var hasNativeReflectConstruct = advanced_filters_isNativeReflectConstruct();
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
function advanced_filters_isNativeReflectConstruct() {
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




var matches = [{
  value: 'all',
  label: (0,build_module.__)('All', 'woocommerce')
}, {
  value: 'any',
  label: (0,build_module.__)('Any', 'woocommerce')
}];

/**
 * Displays a configurable set of filters which can modify query parameters.
 */
var AdvancedFilters = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(AdvancedFilters, _Component);
  var _super = advanced_filters_createSuper(AdvancedFilters);
  function AdvancedFilters(_ref) {
    var _this;
    var query = _ref.query,
      config = _ref.config;
    (0,classCallCheck/* default */.A)(this, AdvancedFilters);
    _this = _super.apply(this, arguments);
    _this.instanceCounts = {};
    var filtersFromQuery = (0,src/* getActiveFiltersFromQuery */.Q$)(query, config.filters);
    // @todo: This causes rerenders when instance numbers don't match (from adding/remove before updating query string).
    var activeFilters = filtersFromQuery.map(function (filter) {
      if (config.filters[filter.key].allowMultiple) {
        filter.instance = _this.getInstanceNumber(filter.key);
      }
      return filter;
    });
    _this.state = {
      match: query.match || 'all',
      activeFilters: activeFilters
    };
    _this.filterListRef = (0,react.createRef)();
    _this.onMatchChange = _this.onMatchChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onFilterChange = _this.onFilterChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.getAvailableFilters = _this.getAvailableFilters.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.addFilter = _this.addFilter.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.removeFilter = _this.removeFilter.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.clearFilters = _this.clearFilters.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.getUpdateHref = _this.getUpdateHref.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onFilter = _this.onFilter.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(AdvancedFilters, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this2 = this;
      var _this$props = this.props,
        config = _this$props.config,
        query = _this$props.query;
      var prevQuery = prevProps.query;
      if (!(0,lodash.isEqual)(prevQuery, query)) {
        var filtersFromQuery = (0,src/* getActiveFiltersFromQuery */.Q$)(query, config.filters);

        // Update all multiple instance counts.
        this.instanceCounts = {};
        // @todo: This causes rerenders when instance numbers don't match (from adding/remove before updating query string).
        var activeFilters = filtersFromQuery.map(function (filter) {
          if (config.filters[filter.key].allowMultiple) {
            filter.instance = _this2.getInstanceNumber(filter.key);
          }
          return filter;
        });

        /* eslint-disable react/no-did-update-set-state */
        this.setState({
          activeFilters: activeFilters
        });
        /* eslint-enable react/no-did-update-set-state */
      }
    }
  }, {
    key: "getInstanceNumber",
    value: function getInstanceNumber(key) {
      if (!this.instanceCounts.hasOwnProperty(key)) {
        this.instanceCounts[key] = 1;
      }
      return this.instanceCounts[key]++;
    }
  }, {
    key: "onMatchChange",
    value: function onMatchChange(match) {
      var onAdvancedFilterAction = this.props.onAdvancedFilterAction;
      this.setState({
        match: match
      });
      onAdvancedFilterAction('match', {
        match: match
      });
    }
  }, {
    key: "onFilterChange",
    value: function onFilterChange(index, _ref2) {
      var property = _ref2.property,
        value = _ref2.value,
        _ref2$shouldResetValu = _ref2.shouldResetValue,
        shouldResetValue = _ref2$shouldResetValu === void 0 ? false : _ref2$shouldResetValu;
      var newActiveFilters = (0,toConsumableArray/* default */.A)(this.state.activeFilters);
      newActiveFilters[index] = advanced_filters_objectSpread(advanced_filters_objectSpread({}, newActiveFilters[index]), {}, (0,defineProperty/* default */.A)({}, property, value), shouldResetValue === true ? {
        value: null
      } : {});
      this.setState({
        activeFilters: newActiveFilters
      });
    }
  }, {
    key: "removeFilter",
    value: function removeFilter(index) {
      var onAdvancedFilterAction = this.props.onAdvancedFilterAction;
      var activeFilters = (0,toConsumableArray/* default */.A)(this.state.activeFilters);
      onAdvancedFilterAction('remove', activeFilters[index]);
      activeFilters.splice(index, 1);
      this.setState({
        activeFilters: activeFilters
      });
      if (activeFilters.length === 0) {
        var history = (0,src/* getHistory */.JK)();
        history.push(this.getUpdateHref([]));
      }
    }
  }, {
    key: "getTitle",
    value: function getTitle() {
      var match = this.state.match;
      var config = this.props.config;
      return backwardsCompatibleCreateInterpolateElement(config.title, {
        select: (0,react.createElement)(select_control/* default */.A, {
          className: "woocommerce-filters-advanced__title-select",
          options: matches,
          value: match,
          onChange: this.onMatchChange,
          "aria-label": (0,build_module.__)('Choose to apply any or all filters', 'woocommerce')
        })
      });
    }
  }, {
    key: "getAvailableFilters",
    value: function getAvailableFilters() {
      var config = this.props.config;
      var activeFilterKeys = this.state.activeFilters.map(function (f) {
        return f.key;
      });

      // Get filter objects with keys.
      var allFilters = Object.entries(config.filters).map(function (_ref3) {
        var _ref4 = (0,slicedToArray/* default */.A)(_ref3, 2),
          key = _ref4[0],
          value = _ref4[1];
        return advanced_filters_objectSpread({
          key: key
        }, value);
      });

      // Available filters are those that allow multiple instances or are not already active.
      var availableFilters = allFilters.filter(function (filter) {
        return filter.allowMultiple || !activeFilterKeys.includes(filter.key);
      });

      // Sort filters by their add label.
      availableFilters.sort(function (a, b) {
        return a.labels.add.localeCompare(b.labels.add);
      });
      return availableFilters;
    }
  }, {
    key: "addFilter",
    value: function addFilter(key, onClose) {
      var _this3 = this;
      var _this$props2 = this.props,
        onAdvancedFilterAction = _this$props2.onAdvancedFilterAction,
        config = _this$props2.config;
      var filterConfig = config.filters[key];
      var newFilter = {
        key: key
      };
      if (Array.isArray(filterConfig.rules) && filterConfig.rules.length) {
        newFilter.rule = filterConfig.rules[0].value;
      }
      if (filterConfig.input && filterConfig.input.options) {
        newFilter.value = (0,src/* getDefaultOptionValue */.Am)(filterConfig, filterConfig.input.options);
      }
      if (filterConfig.input && filterConfig.input.component === 'Search') {
        newFilter.value = '';
      }
      if (filterConfig.allowMultiple) {
        newFilter.instance = this.getInstanceNumber(key);
      }
      this.setState(function (state) {
        return {
          activeFilters: [].concat((0,toConsumableArray/* default */.A)(state.activeFilters), [newFilter])
        };
      });
      onAdvancedFilterAction('add', newFilter);
      onClose();
      // after render, focus the newly added filter's first focusable element
      setTimeout(function () {
        var addedFilter = _this3.filterListRef.current.querySelector('li:last-of-type fieldset');
        addedFilter.focus();
      });
    }
  }, {
    key: "clearFilters",
    value: function clearFilters() {
      var onAdvancedFilterAction = this.props.onAdvancedFilterAction;
      onAdvancedFilterAction('clear_all');
      this.setState({
        activeFilters: [],
        match: 'all'
      });
    }
  }, {
    key: "getUpdateHref",
    value: function getUpdateHref(activeFilters, matchValue) {
      var _this$props3 = this.props,
        path = _this$props3.path,
        query = _this$props3.query,
        config = _this$props3.config;
      var updatedQuery = (0,src/* getQueryFromActiveFilters */.Sz)(activeFilters, query, config.filters);
      var match = matchValue === 'all' ? undefined : matchValue;
      return (0,src/* getNewPath */.Gy)(advanced_filters_objectSpread(advanced_filters_objectSpread({}, updatedQuery), {}, {
        match: match
      }), path, query);
    }
  }, {
    key: "isEnglish",
    value: function isEnglish() {
      return /en[-|_]/.test(this.props.siteLocale);
    }
  }, {
    key: "onFilter",
    value: function onFilter() {
      var _this$props4 = this.props,
        onAdvancedFilterAction = _this$props4.onAdvancedFilterAction,
        query = _this$props4.query,
        config = _this$props4.config;
      var _this$state = this.state,
        activeFilters = _this$state.activeFilters,
        match = _this$state.match;
      var updatedQuery = (0,src/* getQueryFromActiveFilters */.Sz)(activeFilters, query, config.filters);
      onAdvancedFilterAction('filter', advanced_filters_objectSpread(advanced_filters_objectSpread({}, updatedQuery), {}, {
        match: match
      }));
    }
  }, {
    key: "orderFilters",
    value: function orderFilters(a, b) {
      var qs = window.location.search;
      var aPos = qs.indexOf(a.key);
      var bPos = qs.indexOf(b.key);
      // If either isn't in the url, it means its just been added, so leave it as is.
      if (aPos === -1 || bPos === -1) {
        return 0;
      }
      // Otherwise use the url to determine order in which filter was added.
      return aPos - bPos;
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;
      var _this$props5 = this.props,
        config = _this$props5.config,
        query = _this$props5.query,
        currency = _this$props5.currency;
      var _this$state2 = this.state,
        activeFilters = _this$state2.activeFilters,
        match = _this$state2.match;
      var availableFilters = this.getAvailableFilters();
      var updateHref = this.getUpdateHref(activeFilters, match);
      var updateDisabled = 'admin.php' + window.location.search === updateHref || activeFilters.length === 0;
      var isEnglish = this.isEnglish();
      return (0,react.createElement)(component/* default */.A, {
        className: "woocommerce-filters-advanced",
        size: "small"
      }, (0,react.createElement)(card_header_component/* default */.A, {
        justify: "flex-start"
      }, (0,react.createElement)(experimental/* Text */.E, {
        variant: "subtitle.small",
        as: "div",
        weight: "600",
        size: "14",
        lineHeight: "20px",
        isBlock: "false"
      }, this.getTitle())), !!activeFilters.length && (0,react.createElement)(card_body_component/* default */.A, {
        size: null
      }, (0,react.createElement)("ul", {
        className: "woocommerce-filters-advanced__list",
        ref: this.filterListRef
      }, activeFilters.sort(this.orderFilters).map(function (filter, idx) {
        var instance = filter.instance,
          key = filter.key;
        return (0,react.createElement)(item, {
          key: key + (instance || ''),
          config: config,
          currency: currency,
          filter: filter,
          isEnglish: isEnglish,
          onFilterChange: (0,lodash.partial)(_this4.onFilterChange, idx),
          query: query,
          removeFilter: function removeFilter() {
            return _this4.removeFilter(idx);
          }
        });
      }))), availableFilters.length > 0 && (0,react.createElement)(card_body_component/* default */.A, null, (0,react.createElement)("div", {
        className: "woocommerce-filters-advanced__add-filter"
      }, (0,react.createElement)(dropdown/* default */.A, {
        className: "woocommerce-filters-advanced__add-filter-dropdown",
        popoverProps: {
          placement: 'bottom'
        },
        renderToggle: function renderToggle(_ref5) {
          var isOpen = _ref5.isOpen,
            onToggle = _ref5.onToggle;
          return (0,react.createElement)(build_module_button/* default */.A, {
            className: "woocommerce-filters-advanced__add-button",
            onClick: onToggle,
            "aria-expanded": isOpen
          }, (0,react.createElement)(add_outline/* default */.A, null), (0,build_module.__)('Add a filter', 'woocommerce'));
        },
        renderContent: function renderContent(_ref6) {
          var onClose = _ref6.onClose;
          return (0,react.createElement)("ul", {
            className: "woocommerce-filters-advanced__add-dropdown"
          }, availableFilters.map(function (filter) {
            return (0,react.createElement)("li", {
              key: filter.key
            }, (0,react.createElement)(build_module_button/* default */.A, {
              onClick: (0,lodash.partial)(_this4.addFilter, filter.key, onClose)
            }, filter.labels.add));
          }));
        }
      }))), (0,react.createElement)(card_footer_component/* default */.A, {
        align: "center"
      }, (0,react.createElement)("div", {
        className: "woocommerce-filters-advanced__controls"
      }, updateDisabled && (0,react.createElement)(build_module_button/* default */.A, {
        isPrimary: true,
        disabled: true
      }, (0,build_module.__)('Filter', 'woocommerce')), !updateDisabled && (0,react.createElement)(src_link/* default */.A, {
        className: "components-button is-primary is-button",
        type: "wc-admin",
        href: updateHref,
        onClick: this.onFilter
      }, (0,build_module.__)('Filter', 'woocommerce')), activeFilters.length > 0 && (0,react.createElement)(src_link/* default */.A, {
        type: "wc-admin",
        href: this.getUpdateHref([]),
        onClick: this.clearFilters
      }, (0,build_module.__)('Clear all filters', 'woocommerce')))));
    }
  }]);
  return AdvancedFilters;
}(react.Component);
AdvancedFilters.propTypes = {
  /**
   * The configuration object required to render filters.
   */
  config: prop_types_default().shape({
    title: (prop_types_default()).string,
    filters: prop_types_default().objectOf(prop_types_default().shape({
      labels: prop_types_default().shape({
        add: (prop_types_default()).string,
        remove: (prop_types_default()).string,
        rule: (prop_types_default()).string,
        title: (prop_types_default()).string,
        filter: (prop_types_default()).string
      }),
      rules: prop_types_default().arrayOf((prop_types_default()).object),
      input: (prop_types_default()).object
    }))
  }).isRequired,
  /**
   * Name of this filter, used in translations.
   */
  path: (prop_types_default()).string.isRequired,
  /**
   * The query string represented in object form.
   */
  query: (prop_types_default()).object,
  /**
   * Function to be called after an advanced filter action has been taken.
   */
  onAdvancedFilterAction: (prop_types_default()).func,
  /**
   * The locale for the site.
   */
  siteLocale: (prop_types_default()).string,
  /**
   * The currency formatting instance for the site.
   */
  currency: (prop_types_default()).object.isRequired
};
AdvancedFilters.defaultProps = {
  query: {},
  onAdvancedFilterAction: function onAdvancedFilterAction() {},
  siteLocale: 'en_US'
};
/* harmony default export */ const advanced_filters = (AdvancedFilters);

/***/ }),

/***/ "../../packages/js/components/src/calendar/date-picker.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_features_object_assign__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/features/object/assign.js");
/* harmony import */ var core_js_features_object_assign__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_features_object_assign__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_features_array_from__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/features/array/from.js");
/* harmony import */ var core_js_features_array_from__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_features_array_from__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/date.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../packages/js/date/src/index.ts");
/* harmony import */ var _input__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__("../../packages/js/components/src/calendar/input.js");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__("../../packages/js/components/src/section/section.tsx");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__("../../packages/js/components/src/section/header.tsx");









function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(this, result);
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


var DatePicker = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A)(DatePicker, _Component);
  var _super = _createSuper(DatePicker);
  function DatePicker(props) {
    var _this;
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A)(this, DatePicker);
    _this = _super.call(this, props);
    _this.onDateChange = _this.onDateChange.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)(_this));
    _this.onInputChange = _this.onInputChange.bind((0,_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A)(_this));
    return _this;
  }
  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A)(DatePicker, [{
    key: "handleFocus",
    value: function handleFocus(isOpen, onToggle) {
      if (!isOpen) {
        onToggle();
      }
    }
  }, {
    key: "handleBlur",
    value: function handleBlur(isOpen, onToggle, event) {
      var _event$relatedTarget, _event$currentTarget;
      if (!isOpen) {
        return;
      }
      var relatedTargetParent = (_event$relatedTarget = event.relatedTarget) === null || _event$relatedTarget === void 0 ? void 0 : _event$relatedTarget.closest('.components-dropdown');
      var currentTargetParent = (_event$currentTarget = event.currentTarget) === null || _event$currentTarget === void 0 ? void 0 : _event$currentTarget.closest('.components-dropdown');
      if (!relatedTargetParent || relatedTargetParent !== currentTargetParent) {
        onToggle();
      }
    }
  }, {
    key: "onDateChange",
    value: function onDateChange(onToggle, dateString) {
      var _this$props = this.props,
        onUpdate = _this$props.onUpdate,
        dateFormat = _this$props.dateFormat;
      var date = moment__WEBPACK_IMPORTED_MODULE_9___default()(dateString);
      onUpdate({
        date: date,
        text: dateString ? date.format(dateFormat) : '',
        error: null
      });
      onToggle();
    }
  }, {
    key: "onInputChange",
    value: function onInputChange(event) {
      var value = event.target.value;
      var dateFormat = this.props.dateFormat;
      var date = (0,_woocommerce_date__WEBPACK_IMPORTED_MODULE_10__/* .toMoment */ .sf)(dateFormat, value);
      var error = date ? null : _woocommerce_date__WEBPACK_IMPORTED_MODULE_10__/* .dateValidationMessages */ .Y6.invalid;
      this.props.onUpdate({
        date: date,
        text: value,
        error: value.length > 0 ? error : null
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props2 = this.props,
        date = _this$props2.date,
        disabled = _this$props2.disabled,
        text = _this$props2.text,
        dateFormat = _this$props2.dateFormat,
        error = _this$props2.error,
        isInvalidDate = _this$props2.isInvalidDate,
        _this$props2$popoverP = _this$props2.popoverProps,
        popoverProps = _this$props2$popoverP === void 0 ? {
          inline: true
        } : _this$props2$popoverP;
      if (!popoverProps.placement) {
        popoverProps.placement = 'bottom';
      }
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_15__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_16__/* ["default"] */ .A, {
        focusOnMount: false,
        popoverProps: popoverProps,
        renderToggle: function renderToggle(_ref) {
          var isOpen = _ref.isOpen,
            onToggle = _ref.onToggle;
          return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_15__.createElement)(_input__WEBPACK_IMPORTED_MODULE_17__/* ["default"] */ .A, {
            disabled: disabled,
            value: text,
            onChange: _this2.onInputChange,
            onBlur: (0,lodash__WEBPACK_IMPORTED_MODULE_8__.partial)(_this2.handleBlur, isOpen, onToggle),
            dateFormat: dateFormat,
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)('Choose a date', 'woocommerce'),
            error: error,
            describedBy: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__/* .sprintf */ .nv)( /* translators: %s: date format specification */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)('Date input describing a selected date in format %s', 'woocommerce'), dateFormat),
            onFocus: (0,lodash__WEBPACK_IMPORTED_MODULE_8__.partial)(_this2.handleFocus, isOpen, onToggle),
            "aria-expanded": isOpen,
            focusOnMount: false,
            errorPosition: "top center"
          });
        },
        renderContent: function renderContent(_ref2) {
          var onToggle = _ref2.onToggle;
          return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_15__.createElement)(_section__WEBPACK_IMPORTED_MODULE_18__/* .Section */ .w, {
            component: false
          }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_15__.createElement)(_section__WEBPACK_IMPORTED_MODULE_19__.H, {
            className: "woocommerce-calendar__date-picker-title"
          }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)('select a date', 'woocommerce')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_15__.createElement)("div", {
            className: "woocommerce-calendar__react-dates is-core-datepicker"
          }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_15__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_20__/* ["default"] */ .A, {
            currentDate: date instanceof (moment__WEBPACK_IMPORTED_MODULE_9___default()) ? date.toDate() : date,
            onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_8__.partial)(_this2.onDateChange, onToggle)
            // onMonthPreviewed is required to prevent a React error from happening.
            ,

            onMonthPreviewed: lodash__WEBPACK_IMPORTED_MODULE_8__.noop,
            isInvalidDate: isInvalidDate
          })));
        }
      });
    }
  }]);
  return DatePicker;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_15__.Component);
DatePicker.propTypes = {
  /**
   * A moment date object representing the selected date. `null` for no selection.
   */
  date: (prop_types__WEBPACK_IMPORTED_MODULE_21___default().object),
  /**
   * Whether the input is disabled.
   */
  disabled: (prop_types__WEBPACK_IMPORTED_MODULE_21___default().bool),
  /**
   * The date in human-readable format. Displayed in the text input.
   */
  text: (prop_types__WEBPACK_IMPORTED_MODULE_21___default().string),
  /**
   * A string error message, shown to the user.
   */
  error: (prop_types__WEBPACK_IMPORTED_MODULE_21___default().string),
  /**
   * A function called upon selection of a date or input change.
   */
  onUpdate: (prop_types__WEBPACK_IMPORTED_MODULE_21___default().func).isRequired,
  /**
   * The date format in moment.js-style tokens.
   */
  dateFormat: (prop_types__WEBPACK_IMPORTED_MODULE_21___default().string).isRequired,
  /**
   * A function to determine if a day on the calendar is not valid
   */
  isInvalidDate: (prop_types__WEBPACK_IMPORTED_MODULE_21___default().func)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DatePicker);

/***/ }),

/***/ "../../packages/js/components/src/calendar/input.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/calendar.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_6__);
/**
 * External dependencies
 */






var DateInput = function DateInput(_ref) {
  var _ref$disabled = _ref.disabled,
    disabled = _ref$disabled === void 0 ? false : _ref$disabled,
    value = _ref.value,
    onChange = _ref.onChange,
    dateFormat = _ref.dateFormat,
    label = _ref.label,
    describedBy = _ref.describedBy,
    error = _ref.error,
    _ref$onFocus = _ref.onFocus,
    onFocus = _ref$onFocus === void 0 ? function () {} : _ref$onFocus,
    _ref$onBlur = _ref.onBlur,
    onBlur = _ref$onBlur === void 0 ? function () {} : _ref$onBlur,
    _ref$onKeyDown = _ref.onKeyDown,
    onKeyDown = _ref$onKeyDown === void 0 ? lodash__WEBPACK_IMPORTED_MODULE_1__.noop : _ref$onKeyDown,
    _ref$errorPosition = _ref.errorPosition,
    errorPosition = _ref$errorPosition === void 0 ? 'bottom center' : _ref$errorPosition;
  var classes = classnames__WEBPACK_IMPORTED_MODULE_0___default()('woocommerce-calendar__input', {
    'is-empty': value.length === 0,
    'is-error': error
  });
  var id = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.uniqueId)('_woo-dates-input');
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    className: classes
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("input", {
    type: "text",
    className: "woocommerce-calendar__input-text",
    value: value,
    onChange: onChange,
    "aria-label": label,
    id: id,
    "aria-describedby": "".concat(id, "-message"),
    placeholder: dateFormat.toLowerCase(),
    onFocus: onFocus,
    onBlur: onBlur,
    onKeyDown: onKeyDown,
    disabled: disabled
  }), error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
    className: "woocommerce-calendar__input-error",
    focusOnMount: false,
    position: errorPosition
  }, error), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A,
    className: "calendar-icon"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("p", {
    className: "screen-reader-text",
    id: "".concat(id, "-message")
  }, error || describedBy));
};
DateInput.propTypes = {
  disabled: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().bool),
  value: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string),
  onChange: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func).isRequired,
  dateFormat: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string).isRequired,
  label: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string).isRequired,
  describedBy: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string).isRequired,
  error: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string),
  errorPosition: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().string),
  onFocus: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func),
  onBlur: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func),
  onKeyDown: (prop_types__WEBPACK_IMPORTED_MODULE_6___default().func)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DateInput);

/***/ }),

/***/ "../../packages/js/components/src/experimental.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "../../packages/js/components/src/text-control-with-affixes/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_5__);









var _excluded = ["label", "value", "help", "className", "instanceId", "onChange", "onClick", "prefix", "suffix", "type", "disabled"];

function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(this, result);
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
 * This component is essentially a wrapper (really a reimplementation) around the
 * TextControl component that adds support for affixes, i.e. the ability to display
 * a fixed part either at the beginning or at the end of the text input.
 */
var TextControlWithAffixes = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)(TextControlWithAffixes, _Component);
  var _super = _createSuper(TextControlWithAffixes);
  function TextControlWithAffixes(props) {
    var _this;
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)(this, TextControlWithAffixes);
    _this = _super.call(this, props);
    _this.state = {
      isFocused: false
    };
    return _this;
  }
  (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)(TextControlWithAffixes, [{
    key: "handleFocusOutside",
    value: function handleFocusOutside() {
      this.setState({
        isFocused: false
      });
    }
  }, {
    key: "handleOnClick",
    value: function handleOnClick(event, onClick) {
      this.setState({
        isFocused: true
      });
      if (typeof onClick === 'function') {
        onClick(event);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props = this.props,
        label = _this$props.label,
        value = _this$props.value,
        help = _this$props.help,
        className = _this$props.className,
        instanceId = _this$props.instanceId,
        onChange = _this$props.onChange,
        _onClick = _this$props.onClick,
        prefix = _this$props.prefix,
        suffix = _this$props.suffix,
        type = _this$props.type,
        disabled = _this$props.disabled,
        props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A)(_this$props, _excluded);
      var isFocused = this.state.isFocused;
      var id = "inspector-text-control-with-affixes-".concat(instanceId);
      var onChangeValue = function onChangeValue(event) {
        return onChange(event.target.value);
      };
      var describedby = [];
      if (help) {
        describedby.push("".concat(id, "__help"));
      }
      if (prefix) {
        describedby.push("".concat(id, "__prefix"));
      }
      if (suffix) {
        describedby.push("".concat(id, "__suffix"));
      }
      var baseControlClasses = classnames__WEBPACK_IMPORTED_MODULE_5___default()(className, {
        'with-value': value !== '',
        empty: value === '',
        active: isFocused && !disabled
      });
      var affixesClasses = classnames__WEBPACK_IMPORTED_MODULE_5___default()('text-control-with-affixes', {
        'text-control-with-prefix': prefix,
        'text-control-with-suffix': suffix,
        disabled: disabled
      });
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .Ay, {
        label: label,
        id: id,
        help: help,
        className: baseControlClasses,
        onClick: function onClick(event) {
          return _this2.handleOnClick(event, _onClick);
        }
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.createElement)("div", {
        className: affixesClasses
      }, prefix && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.createElement)("span", {
        id: "".concat(id, "__prefix"),
        className: "text-control-with-affixes__prefix"
      }, prefix), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.createElement)("input", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A)({
        className: "components-text-control__input",
        type: type,
        id: id,
        value: value,
        onChange: onChangeValue,
        "aria-describedby": describedby.join(' '),
        disabled: disabled,
        onFocus: function onFocus() {
          return _this2.setState({
            isFocused: true
          });
        }
      }, props)), suffix && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.createElement)("span", {
        id: "".concat(id, "__suffix"),
        className: "text-control-with-affixes__suffix"
      }, suffix)));
    }
  }]);
  return TextControlWithAffixes;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__.Component);
TextControlWithAffixes.defaultProps = {
  type: 'text'
};
TextControlWithAffixes.propTypes = {
  /**
   * If this property is added, a label will be generated using label property as the content.
   */
  label: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * If this property is added, a help text will be generated using help property as the content.
   */
  help: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * Type of the input element to render. Defaults to "text".
   */
  type: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * The current value of the input.
   */
  value: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string).isRequired,
  /**
   * The class that will be added with "components-base-control" to the classes of the wrapper div.
   * If no className is passed only components-base-control is used.
   */
  className: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().string),
  /**
   * A function that receives the value of the input.
   */
  onChange: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().func).isRequired,
  /**
   * Markup to be inserted at the beginning of the input.
   */
  prefix: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().node),
  /**
   * Markup to be appended at the end of the input.
   */
  suffix: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().node),
  /**
   * Whether or not the input is disabled.
   */
  disabled: (prop_types__WEBPACK_IMPORTED_MODULE_13___default().bool)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A)([_wordpress_compose__WEBPACK_IMPORTED_MODULE_15__/* ["default"] */ .A, _wordpress_components__WEBPACK_IMPORTED_MODULE_16__/* ["default"] */ .A // this MUST be the innermost HOC as it calls handleFocusOutside
])(TextControlWithAffixes));

/***/ }),

/***/ "../../packages/js/navigation/src/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Oo: () => (/* binding */ addHistoryListener),
  SI: () => (/* reexport */ flattenFilters),
  Q$: () => (/* reexport */ getActiveFiltersFromQuery),
  Am: () => (/* reexport */ getDefaultOptionValue),
  JK: () => (/* reexport */ history_getHistory),
  DF: () => (/* binding */ getIdsFromQuery),
  Gy: () => (/* binding */ getNewPath),
  aK: () => (/* binding */ getPersistedQuery),
  $Z: () => (/* binding */ getQuery),
  Sz: () => (/* reexport */ getQueryFromActiveFilters),
  Ze: () => (/* binding */ updateQueryString)
});

// UNUSED EXPORTS: getPath, getQueryExcludedScreens, getScreenFromPath, getSearchWords, getSetOfIdsFromQuery, getUrlKey, isWCAdmin, navigateTo, onQueryChange, parseAdminUrl, pathIsExcluded, useConfirmUnsavedChanges, useQuery

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js
var es_string_search = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.set.js
var es_set = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.set.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js
var es_parse_int = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js
var es_string_starts_with = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url.js
var web_url = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url-search-params.js
var web_url_search_params = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url-search-params.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@3.7.1/node_modules/@wordpress/url/build-module/add-query-args.js + 3 modules
var add_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@3.7.1/node_modules/@wordpress/url/build-module/add-query-args.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/qs@6.11.2/node_modules/qs/lib/index.js
var lib = __webpack_require__("../../node_modules/.pnpm/qs@6.11.2/node_modules/qs/lib/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js + 10 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/history@5.3.0/node_modules/history/index.js
var node_modules_history = __webpack_require__("../../node_modules/.pnpm/history@5.3.0/node_modules/history/index.js");
;// CONCATENATED MODULE: ../../packages/js/navigation/src/history.ts












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



// See https://github.com/ReactTraining/react-router/blob/master/FAQ.md#how-do-i-access-the-history-object-outside-of-components
// ^ This is a bit outdated but there's no newer documentation - the replacement for this is to use <unstable_HistoryRouter /> https://reactrouter.com/docs/en/v6/routers/history-router

/**
 * Extension of history.BrowserHistory but also adds { pathname: string } to the location object.
 */

var _history;

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
    var browserHistory = (0,node_modules_history/* createBrowserHistory */.zR)();
    var locationStack = [browserHistory.location];
    var updateNextLocationStack = function updateNextLocationStack(action, location) {
      switch (action) {
        case 'POP':
          locationStack = locationStack.slice(0, locationStack.length - 1);
          break;
        case 'PUSH':
          locationStack = [].concat((0,toConsumableArray/* default */.A)(locationStack), [location]);
          break;
        case 'REPLACE':
          locationStack = [].concat((0,toConsumableArray/* default */.A)(locationStack.slice(0, locationStack.length - 1)), [location]);
          break;
      }
    };
    _history = {
      get action() {
        return browserHistory.action;
      },
      get location() {
        var location = browserHistory.location;
        var query = (0,lib.parse)(location.search.substring(1));
        var pathname;
        if (query && typeof query.path === 'string') {
          pathname = query.path;
        } else if (query && query.path && typeof query.path !== 'string') {
          // this branch was added when converting to TS as it is technically possible for a query.path to not be a string.
          // eslint-disable-next-line no-console
          console.warn("Query path parameter should be a string but instead was: ".concat(query.path, ", undefined behaviour may occur."));
          pathname = query.path; // ts override only, no coercion going on
        } else {
          pathname = '/';
        }
        return _objectSpread(_objectSpread({}, location), {}, {
          pathname: pathname
        });
      },
      get __experimentalLocationStack() {
        return (0,toConsumableArray/* default */.A)(locationStack);
      },
      createHref: browserHistory.createHref,
      push: browserHistory.push,
      replace: browserHistory.replace,
      go: browserHistory.go,
      back: browserHistory.back,
      forward: browserHistory.forward,
      block: browserHistory.block,
      listen: function listen(listener) {
        var _this = this;
        return browserHistory.listen(function () {
          listener({
            action: _this.action,
            location: _this.location
          });
        });
      }
    };
    browserHistory.listen(function () {
      return updateNextLocationStack(_history.action, _history.location);
    });
  }
  return _history;
}

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
;// CONCATENATED MODULE: ../../packages/js/navigation/src/filters.js


function filters_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function filters_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? filters_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : filters_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}














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
    return "".concat(key, "_").concat(rule);
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
  var allFilters = [];
  filters.forEach(function (f) {
    if (!f.subFilters) {
      allFilters.push(f);
    } else {
      allFilters.push((0,lodash.omit)(f, 'subFilters'));
      var subFilters = flattenFilters(f.subFilters);
      allFilters.push.apply(allFilters, (0,toConsumableArray/* default */.A)(subFilters));
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
  return Object.keys(config).reduce(function (activeFilters, configKey) {
    var filter = config[configKey];
    if (filter.rules) {
      // Get all rules found in the query string.
      var matches = filter.rules.filter(function (rule) {
        return query.hasOwnProperty(getUrlKey(configKey, rule.value));
      });
      if (matches.length) {
        if (filter.allowMultiple) {
          // If rules were found in the query string, and this filter supports
          // multiple instances, add all matches to the active filters array.
          matches.forEach(function (match) {
            var value = query[getUrlKey(configKey, match.value)];
            value.forEach(function (filterValue) {
              activeFilters.push({
                key: configKey,
                rule: match.value,
                value: filterValue
              });
            });
          });
        } else {
          // If the filter is a single instance, just process the first rule match.
          var value = query[getUrlKey(configKey, matches[0].value)];
          activeFilters.push({
            key: configKey,
            rule: matches[0].value,
            value: value
          });
        }
      }
    } else if (query[configKey]) {
      // If the filter doesn't have rules, but allows multiples.
      if (filter.allowMultiple) {
        var _value = query[configKey];
        _value.forEach(function (filterValue) {
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
  var defaultOption = config.input.defaultOption;
  if (config.input.defaultOption) {
    var option = (0,lodash.find)(options, {
      value: defaultOption
    });
    if (!option) {
      /* eslint-disable no-console */
      console.warn("invalid defaultOption ".concat(defaultOption, " supplied to ").concat(config.labels.add));
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
  var previousFilters = getActiveFiltersFromQuery(query, config);
  var previousData = previousFilters.reduce(function (data, filter) {
    data[getUrlKey(filter.key, filter.rule)] = undefined;
    return data;
  }, {});
  var nextData = activeFilters.reduce(function (data, filter) {
    if (filter.rule === 'between' && (!Array.isArray(filter.value) || filter.value.some(function (value) {
      return !value;
    }))) {
      return data;
    }
    if (filter.value) {
      var urlKey = getUrlKey(filter.key, filter.rule);
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
  return filters_objectSpread(filters_objectSpread({}, previousData), nextData);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/navigation/src/hooks/use-confirm-unsaved-changes.ts
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var useConfirmUnsavedChanges = function useConfirmUnsavedChanges(hasUnsavedChanges, shouldConfirm, message) {
  var confirmMessage = useMemo(function () {
    return message !== null && message !== void 0 ? message : __('Changes you made may not be saved.', 'woocommerce');
  }, [message]);
  var history = getHistory();

  // This effect prevent react router from navigate and show
  // a confirmation message. It's a work around to beforeunload
  // because react router does not triggers that event.
  useEffect(function () {
    if (hasUnsavedChanges) {
      var push = history.push;
      history.push = function () {
        var fromUrl = history.location;
        var toUrl = parseAdminUrl(arguments.length <= 0 ? undefined : arguments[0]);
        if (typeof shouldConfirm === 'function' && !shouldConfirm(toUrl, fromUrl)) {
          push.apply(void 0, arguments);
          return;
        }

        /* eslint-disable-next-line no-alert */
        var result = window.confirm(confirmMessage);
        if (result !== false) {
          push.apply(void 0, arguments);
        }
      };
      return function () {
        history.push = push;
      };
    }
  }, [history, hasUnsavedChanges, confirmMessage]);

  // This effect listens to the native beforeunload event to show
  // a confirmation message; note that the message shown is
  // a generic browser-specified string; not the custom one shown
  // when using react router.
  useEffect(function () {
    if (hasUnsavedChanges) {
      var onBeforeUnload = function onBeforeUnload(event) {
        event.preventDefault();
        return event.returnValue = confirmMessage;
      };
      window.addEventListener('beforeunload', onBeforeUnload, {
        capture: true
      });
      return function () {
        window.removeEventListener('beforeunload', onBeforeUnload, {
          capture: true
        });
      };
    }
  }, [hasUnsavedChanges, confirmMessage]);
};
;// CONCATENATED MODULE: ../../packages/js/navigation/src/index.js












function src_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function src_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? src_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : src_ownKeys(Object(t)).forEach(function (r) {
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


// Expose history so all uses get the same history object.


// Export all filter utilities


// Export all hooks

var TIME_EXCLUDED_SCREENS_FILTER = 'woocommerce_admin_time_excluded_screens';

/**
 * Get the current path from history.
 *
 * @return {string}  Current path.
 */
var getPath = function getPath() {
  return history_getHistory().location.pathname;
};

/**
 * Get the current query string, parsed into an object, from history.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
function getQuery() {
  var search = history_getHistory().location.search;
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
function getNewPath(query) {
  var path = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : getPath();
  var currentQuery = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : getQuery();
  var page = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'wc-admin';
  var args = src_objectSpread(src_objectSpread({
    page: page
  }, currentQuery), query);
  if (path !== '/') {
    args.path = path;
  }
  return (0,add_query_args/* addQueryArgs */.F)('admin.php', args);
}

/**
 * Gets query parameters that should persist between screens or updates
 * to reports, such as filtering.
 *
 * @param {Object} query Query containing the parameters.
 * @return {Object} Object containing the persisted queries.
 */
var getPersistedQuery = function getPersistedQuery() {
  var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getQuery();
  /**
   * Filter persisted queries. These query parameters remain in the url when other parameters are updated.
   *
   * @filter woocommerce_admin_persisted_queries
   * @param {Array.<string>} persistedQueries Array of persisted queries.
   */
  var params = (0,build_module/* applyFilters */.W5)('woocommerce_admin_persisted_queries', ['period', 'compare', 'before', 'after', 'interval', 'type']);
  return (0,lodash.pick)(query, params);
};

/**
 * Get array of screens that should ignore persisted queries
 *
 * @return {Array} Array containing list of screens
 */
var getQueryExcludedScreens = function getQueryExcludedScreens() {
  return applyFilters(TIME_EXCLUDED_SCREENS_FILTER, ['stock', 'settings', 'customers', 'homescreen']);
};

/**
 * Retrieve a string 'name' representing the current screen
 *
 * @param {Object} path Path to resolve, default to current
 * @return {string} Screen name
 */
var getScreenFromPath = function getScreenFromPath() {
  var path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getPath();
  return path === '/' ? 'homescreen' : path.replace('/analytics', '').replace('/', '');
};

/**
 * Get an array of IDs from a comma-separated query parameter.
 *
 * @param {string} [queryString=''] string value extracted from URL.
 * @return {Set<number>} List of IDs converted to a set of integers.
 */
function getSetOfIdsFromQuery() {
  var queryString = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  return new Set(
  // Return only unique ids.
  queryString.split(',').map(function (id) {
    return parseInt(id, 10);
  }).filter(function (id) {
    return !isNaN(id);
  }));
}

/**
 * Updates the query parameters of the current page.
 *
 * @param {Object} query        object of params to be updated.
 * @param {string} path         Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 * @param {string} page         Page key (defaults to "wc-admin")
 */
function updateQueryString(query) {
  var path = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : getPath();
  var currentQuery = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : getQuery();
  var page = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'wc-admin';
  var newPath = getNewPath(query, path, currentQuery, page);
  history_getHistory().push(newPath);
}

/**
 * Adds a listener that runs on history change.
 *
 * @param {Function} listener Listener to add on history change.
 * @return {Function} Function to remove listeners.
 */
var addHistoryListener = function addHistoryListener(listener) {
  var _window$wcNavigation;
  // Monkey patch pushState to allow trigger the pushstate event listener.

  window.wcNavigation = (_window$wcNavigation = window.wcNavigation) !== null && _window$wcNavigation !== void 0 ? _window$wcNavigation : {};
  if (!window.wcNavigation.historyPatched) {
    (function (history) {
      var pushState = history.pushState;
      var replaceState = history.replaceState;
      history.pushState = function (state) {
        var pushStateEvent = new CustomEvent('pushstate', {
          state: state
        });
        window.dispatchEvent(pushStateEvent);
        return pushState.apply(history, arguments);
      };
      history.replaceState = function (state) {
        var replaceStateEvent = new CustomEvent('replacestate', {
          state: state
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
  return function () {
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
var pathIsExcluded = function pathIsExcluded(path) {
  return getQueryExcludedScreens().includes(getScreenFromPath(path));
};

/**
 * Get an array of IDs from a comma-separated query parameter.
 *
 * @param {string} [queryString=''] string value extracted from URL.
 * @return {Array<number>} List of IDs converted to an array of unique integers.
 */
function getIdsFromQuery() {
  var queryString = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  return (0,toConsumableArray/* default */.A)(getSetOfIdsFromQuery(queryString));
}

/**
 * Get an array of searched words given a query.
 *
 * @param {Object} query Query object.
 * @return {Array} List of search words.
 */
function getSearchWords() {
  var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getQuery();
  if (_typeof(query) !== 'object') {
    throw new Error('Invalid parameter passed to getSearchWords, it expects an object or no parameters.');
  }
  var search = query.search;
  if (!search) {
    return [];
  }
  if (typeof search !== 'string') {
    throw new Error("Invalid 'search' type. getSearchWords expects query's 'search' property to be a string.");
  }
  return search.split(',').map(function (searchWord) {
    return searchWord.replace('%2C', ',');
  });
}

/**
 * Like getQuery but in useHook format for easy usage in React functional components
 *
 * @return {Record<string, string>} Current query object, defaults to empty object.
 */
var useQuery = function useQuery() {
  var _useState = useState({}),
    _useState2 = _slicedToArray(_useState, 2),
    queryState = _useState2[0],
    setQueryState = _useState2[1];
  var _useState3 = useState(true),
    _useState4 = _slicedToArray(_useState3, 2),
    locationChanged = _useState4[0],
    setLocationChanged = _useState4[1];
  useLayoutEffect(function () {
    return addHistoryListener(function () {
      setLocationChanged(true);
    });
  }, []);
  useEffect(function () {
    if (locationChanged) {
      var query = getQuery();
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
function onQueryChange(param) {
  var path = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : getPath();
  var query = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : getQuery();
  switch (param) {
    case 'sort':
      return function (key, dir) {
        return updateQueryString({
          orderby: key,
          order: dir
        }, path, query);
      };
    case 'compare':
      return function (key, queryParam, ids) {
        return updateQueryString(_defineProperty(_defineProperty(_defineProperty({}, queryParam, "compare-".concat(key)), key, ids), "search", undefined), path, query);
      };
    default:
      return function (value) {
        return updateQueryString(_defineProperty({}, param, value), path, query);
      };
  }
}

/**
 * Determines if a URL is a WC admin url.
 *
 * @param {*} url - the url to test
 * @return {boolean} true if the url is a wc-admin URL
 */
var isWCAdmin = function isWCAdmin() {
  var url = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : window.location.href;
  return /admin.php\?page=wc-admin/.test(url);
};

/**
 * Returns a parsed object for an absolute or relative admin URL.
 *
 * @param {*} url - the url to test.
 * @return {URL} - the URL object of the given url.
 */
var src_parseAdminUrl = function parseAdminUrl(url) {
  if (url.startsWith('http')) {
    return new URL(url);
  }
  return /^\/?[a-z0-9]+.php/i.test(url) ? new URL("".concat(window.wcSettings.adminUrl).concat(url)) : new URL(getAdminLink(getNewPath({}, url, {})));
};

/**
 * A utility function that navigates to a page, using a redirect
 * or the router as appropriate.
 *
 * @param {Object} args     - All arguments.
 * @param {string} args.url - Relative path or absolute url to navigate to
 */
var navigateTo = function navigateTo(_ref) {
  var url = _ref.url;
  var parsedUrl = src_parseAdminUrl(url);
  if (isWCAdmin() && isWCAdmin(String(parsedUrl))) {
    window.document.documentElement.scrollTop = 0;
    getHistory().push("admin.php".concat(parsedUrl.search));
    return;
  }
  window.location.href = String(parsedUrl);
};

/***/ }),

/***/ "./setting.mock.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   P: () => (/* binding */ getSetting)
/* harmony export */ });
// @woocommerce/settings mocked module for storybook webpack resolve.alias config
// see ./webpack.config.js

function getSetting() {
  return {};
}

/***/ }),

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../packages/js/navigation/src/index.js");













var _excluded = ["href", "children", "type"];

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
      (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */




// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
var Link = function Link(_ref) {
  var href = _ref.href,
    children = _ref.children,
    _ref$type = _ref.type,
    type = _ref$type === void 0 ? 'wc-admin' : _ref$type,
    props = (0,_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A)(_ref, _excluded);
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  var wcAdminLinkHandler = function wcAdminLinkHandler(onClick, event) {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event !== null && event !== void 0 && event.ctrlKey || event !== null && event !== void 0 && event.metaKey || event !== null && event !== void 0 && event.altKey || event !== null && event !== void 0 && event.shiftKey) {
      return;
    }
    event === null || event === void 0 || event.preventDefault();

    // If there is an onclick event, execute it.
    var onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if ((event === null || event === void 0 ? void 0 : event.target) instanceof Element) {
      var _event$target$closest;
      var closestEventTarget = (_event$target$closest = event.target.closest('a')) === null || _event$target$closest === void 0 ? void 0 : _event$target$closest.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  var passProps = _objectSpread(_objectSpread({}, props), {}, {
    'data-link-type': type
  });
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_11__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_13__.createElement)("a", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_15__/* ["default"] */ .A)({
    href: href
  }, passProps), children);
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

/***/ "../../packages/js/components/src/select-control/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ select_control)
});

// UNUSED EXPORTS: SelectControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js
var es_array_find_index = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js
var es_string_search = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js
var es_string_trim = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js
var with_spoken_messages = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js
var with_focus_outside = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var keycodes_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@3.6.1/node_modules/@wordpress/keycodes/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/list.tsx













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
 * A list box that displays filtered options after search.
 */
var List = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(List, _Component);
  var _super = _createSuper(List);
  function List(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, List);
    _this = _super.call(this, props);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "optionRefs", void 0);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "listbox", void 0);
    _this.handleKeyDown = _this.handleKeyDown.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.select = _this.select.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.optionRefs = {};
    _this.listbox = (0,react.createRef)();
    return _this;
  }
  (0,createClass/* default */.A)(List, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
        options = _this$props.options,
        selectedIndex = _this$props.selectedIndex;

      // Remove old option refs to avoid memory leaks.
      if (!(0,lodash.isEqual)(options, prevProps.options)) {
        this.optionRefs = {};
      }
      if (selectedIndex !== prevProps.selectedIndex && (0,lodash.isNumber)(selectedIndex)) {
        this.scrollToOption(selectedIndex);
      }
    }
  }, {
    key: "getOptionRef",
    value: function getOptionRef(index) {
      if (!this.optionRefs.hasOwnProperty(index)) {
        this.optionRefs[index] = (0,react.createRef)();
      }
      return this.optionRefs[index];
    }
  }, {
    key: "select",
    value: function select(option) {
      var onSelect = this.props.onSelect;
      if (option.isDisabled) {
        return;
      }
      onSelect(option);
    }
  }, {
    key: "scrollToOption",
    value: function scrollToOption(index) {
      var listbox = this.listbox.current;
      if (!listbox) {
        return;
      }
      if (listbox.scrollHeight <= listbox.clientHeight) {
        return;
      }
      if (!this.optionRefs[index]) {
        return;
      }
      var option = this.optionRefs[index].current;
      if (!option) {
        // eslint-disable-next-line no-console
        console.warn('Option not found, index:', index);
        return;
      }
      var scrollBottom = listbox.clientHeight + listbox.scrollTop;
      var elementBottom = option.offsetTop + option.offsetHeight;
      if (elementBottom > scrollBottom) {
        listbox.scrollTop = elementBottom - listbox.clientHeight;
      } else if (option.offsetTop < listbox.scrollTop) {
        listbox.scrollTop = option.offsetTop;
      }
    }
  }, {
    key: "handleKeyDown",
    value: function handleKeyDown(event) {
      var _this$props2 = this.props,
        decrementSelectedIndex = _this$props2.decrementSelectedIndex,
        incrementSelectedIndex = _this$props2.incrementSelectedIndex,
        options = _this$props2.options,
        onSearch = _this$props2.onSearch,
        selectedIndex = _this$props2.selectedIndex,
        setExpanded = _this$props2.setExpanded;
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
  }, {
    key: "toggleKeyEvents",
    value: function toggleKeyEvents(isListening) {
      var node = this.props.node;
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
      var handler = isListening ? 'addEventListener' : 'removeEventListener';
      node[handler]('keydown', this.handleKeyDown, true);
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      var selectedIndex = this.props.selectedIndex;
      if ((0,lodash.isNumber)(selectedIndex) && selectedIndex > -1) {
        this.scrollToOption(selectedIndex);
      }
      this.toggleKeyEvents(true);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.toggleKeyEvents(false);
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;
      var _this$props3 = this.props,
        instanceId = _this$props3.instanceId,
        listboxId = _this$props3.listboxId,
        options = _this$props3.options,
        selectedIndex = _this$props3.selectedIndex,
        staticList = _this$props3.staticList;
      var listboxClasses = classnames_default()('woocommerce-select-control__listbox', {
        'is-static': staticList
      });
      return (0,react.createElement)("div", {
        ref: this.listbox,
        id: listboxId,
        role: "listbox",
        className: listboxClasses,
        tabIndex: -1
      }, options.map(function (option, index) {
        return (0,react.createElement)(build_module_button/* default */.A, {
          ref: _this2.getOptionRef(index),
          key: option.key,
          id: "woocommerce-select-control__option-".concat(instanceId, "-").concat(option.key),
          role: "option",
          "aria-selected": index === selectedIndex,
          disabled: option.isDisabled,
          className: classnames_default()('woocommerce-select-control__option', {
            'is-selected': index === selectedIndex
          }),
          onClick: function onClick() {
            return _this2.select(option);
          },
          tabIndex: -1
        }, option.label);
      }));
    }
  }]);
  return List;
}(react.Component);
/* harmony default export */ const list = (List);
try {
    // @ts-ignore
    List.displayName = "List";
    // @ts-ignore
    List.__docgenInfo = { "description": "A list box that displays filtered options after search.", "displayName": "List", "props": { "listboxId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "instanceId", "required": true, "type": { "name": "number" } }, "node": { "defaultValue": null, "description": "Parent node to bind keyboard events to.", "name": "node", "required": true, "type": { "name": "HTMLElement | null" } }, "onSelect": { "defaultValue": null, "description": "Function to execute when an option is selected.", "name": "onSelect", "required": true, "type": { "name": "(option: Option) => void" } }, "options": { "defaultValue": null, "description": "Array of options to display.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "selectedIndex": { "defaultValue": null, "description": "Integer for the currently selected item.", "name": "selectedIndex", "required": true, "type": { "name": "number | null | undefined" } }, "staticList": { "defaultValue": null, "description": "Bool to determine if the list should be positioned absolutely or statically.", "name": "staticList", "required": true, "type": { "name": "boolean" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "onSearch": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "onSearch", "required": true, "type": { "name": "(option: string | null) => void" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the list should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/list.tsx#List"] = { docgenInfo: List.__docgenInfo, name: "List", path: "../../packages/js/components/src/select-control/list.tsx#List" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js
var cancel_circle_filled = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/cancel-circle-filled.js");
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/tags.tsx














function tags_createSuper(Derived) {
  var hasNativeReflectConstruct = tags_isNativeReflectConstruct();
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
function tags_isNativeReflectConstruct() {
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
 * A list of tags to display selected items.
 */
var Tags = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(Tags, _Component);
  var _super = tags_createSuper(Tags);
  function Tags(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, Tags);
    _this = _super.call(this, props);
    _this.removeAll = _this.removeAll.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.removeResult = _this.removeResult.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(Tags, [{
    key: "removeAll",
    value: function removeAll() {
      var onChange = this.props.onChange;
      onChange([]);
    }
  }, {
    key: "removeResult",
    value: function removeResult(key) {
      var _this2 = this;
      return function () {
        var _this2$props = _this2.props,
          selected = _this2$props.selected,
          onChange = _this2$props.onChange;
        if (!(0,lodash.isArray)(selected)) {
          return;
        }
        var i = (0,lodash.findIndex)(selected, {
          key: key
        });
        onChange([].concat((0,toConsumableArray/* default */.A)(selected.slice(0, i)), (0,toConsumableArray/* default */.A)(selected.slice(i + 1))));
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;
      var _this$props = this.props,
        selected = _this$props.selected,
        showClearButton = _this$props.showClearButton;
      if (!(0,lodash.isArray)(selected) || !selected.length) {
        return null;
      }
      return (0,react.createElement)(react.Fragment, null, (0,react.createElement)("div", {
        className: "woocommerce-select-control__tags"
      }, selected.map(function (item, i) {
        if (!item.label) {
          return null;
        }
        var screenReaderLabel = (0,build_module/* sprintf */.nv)( /* translators: %1$s: tag label, %2$s: tag number, %3$s: total number of tags */
        (0,build_module.__)('%1$s (%2$s of %3$s)', 'woocommerce'), item.label, i + 1, selected.length);
        return (0,react.createElement)(tag/* default */.A, {
          key: item.key,
          id: item.key,
          label: item.label
          // @ts-expect-error key is a string or undefined here
          ,

          remove: _this3.removeResult,
          screenReaderLabel: screenReaderLabel
        });
      })), showClearButton && (0,react.createElement)(build_module_button/* default */.A, {
        className: "woocommerce-select-control__clear",
        isLink: true,
        onClick: this.removeAll
      }, (0,react.createElement)(icon/* default */.A, {
        icon: cancel_circle_filled/* default */.A,
        className: "clear-icon"
      }), (0,react.createElement)("span", {
        className: "screen-reader-text"
      }, (0,build_module.__)('Clear all', 'woocommerce'))));
    }
  }]);
  return Tags;
}(react.Component);
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/search.js
var search = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/search.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/control.tsx













function control_createSuper(Derived) {
  var hasNativeReflectConstruct = control_isNativeReflectConstruct();
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
function control_isNativeReflectConstruct() {
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
 * A search control to allow user input to filter the options.
 */
var Control = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(Control, _Component);
  var _super = control_createSuper(Control);
  function Control(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, Control);
    _this = _super.call(this, props);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "input", void 0);
    _this.state = {
      isActive: false
    };
    _this.input = (0,react.createRef)();
    _this.updateSearch = _this.updateSearch.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onFocus = _this.onFocus.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onBlur = _this.onBlur.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onKeyDown = _this.onKeyDown.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(Control, [{
    key: "updateSearch",
    value: function updateSearch(onSearch) {
      return function (event) {
        onSearch(event.target.value);
      };
    }
  }, {
    key: "onFocus",
    value: function onFocus(onSearch) {
      var _this2 = this;
      var _this$props = this.props,
        isSearchable = _this$props.isSearchable,
        setExpanded = _this$props.setExpanded,
        showAllOnFocus = _this$props.showAllOnFocus,
        updateSearchOptions = _this$props.updateSearchOptions;
      return function (event) {
        _this2.setState({
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
  }, {
    key: "onBlur",
    value: function onBlur() {
      var onBlur = this.props.onBlur;
      if (typeof onBlur === 'function') {
        onBlur();
      }
      this.setState({
        isActive: false
      });
    }
  }, {
    key: "onKeyDown",
    value: function onKeyDown(event) {
      var _this$props2 = this.props,
        decrementSelectedIndex = _this$props2.decrementSelectedIndex,
        incrementSelectedIndex = _this$props2.incrementSelectedIndex,
        selected = _this$props2.selected,
        onChange = _this$props2.onChange,
        query = _this$props2.query,
        setExpanded = _this$props2.setExpanded;
      if (keycodes_build_module/* BACKSPACE */.G_ === event.keyCode && !query && (0,lodash.isArray)(selected) && selected.length) {
        onChange((0,toConsumableArray/* default */.A)(selected.slice(0, -1)));
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
  }, {
    key: "renderButton",
    value: function renderButton() {
      var _this$props3 = this.props,
        multiple = _this$props3.multiple,
        selected = _this$props3.selected;
      if (multiple || !(0,lodash.isArray)(selected) || !selected.length) {
        return null;
      }
      return (0,react.createElement)("div", {
        className: "woocommerce-select-control__control-value"
      }, selected[0].label);
    }
  }, {
    key: "renderInput",
    value: function renderInput() {
      var _this$props$ariaLabel;
      var _this$props4 = this.props,
        activeId = _this$props4.activeId,
        disabled = _this$props4.disabled,
        hasTags = _this$props4.hasTags,
        inlineTags = _this$props4.inlineTags,
        instanceId = _this$props4.instanceId,
        isExpanded = _this$props4.isExpanded,
        isSearchable = _this$props4.isSearchable,
        listboxId = _this$props4.listboxId,
        onSearch = _this$props4.onSearch,
        placeholder = _this$props4.placeholder,
        searchInputType = _this$props4.searchInputType,
        autoComplete = _this$props4.autoComplete;
      var isActive = this.state.isActive;
      return (0,react.createElement)("input", {
        autoComplete: autoComplete || 'off',
        className: "woocommerce-select-control__control-input",
        id: "woocommerce-select-control-".concat(instanceId, "__control-input"),
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
        "aria-describedby": hasTags && inlineTags ? "search-inline-input-".concat(instanceId) : undefined,
        disabled: disabled,
        "aria-label": (_this$props$ariaLabel = this.props.ariaLabel) !== null && _this$props$ariaLabel !== void 0 ? _this$props$ariaLabel : this.props.label
      });
    }
  }, {
    key: "getInputValue",
    value: function getInputValue() {
      var _this$props5 = this.props,
        inlineTags = _this$props5.inlineTags,
        isFocused = _this$props5.isFocused,
        isSearchable = _this$props5.isSearchable,
        multiple = _this$props5.multiple,
        query = _this$props5.query,
        selected = _this$props5.selected;
      var selectedValue = (0,lodash.isArray)(selected) && selected.length ? selected[0].label : '';

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
  }, {
    key: "render",
    value: function render() {
      var _this$getInputValue,
        _this3 = this;
      var _this$props6 = this.props,
        className = _this$props6.className,
        disabled = _this$props6.disabled,
        hasTags = _this$props6.hasTags,
        help = _this$props6.help,
        inlineTags = _this$props6.inlineTags,
        instanceId = _this$props6.instanceId,
        isSearchable = _this$props6.isSearchable,
        label = _this$props6.label,
        query = _this$props6.query,
        onChange = _this$props6.onChange,
        showClearButton = _this$props6.showClearButton;
      var isActive = this.state.isActive;
      return (
        // Disable reason: The div below visually simulates an input field. Its
        // child input is the actual input and responds accordingly to all keyboard
        // events, but click events need to be passed onto the child input. There
        // is no appropriate aria role for describing this situation, which is only
        // for the benefit of sighted users.
        /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
        (0,react.createElement)("div", {
          className: classnames_default()('components-base-control', 'woocommerce-select-control__control', className, {
            empty: !query || query.length === 0,
            'is-active': isActive,
            'has-tags': inlineTags && hasTags,
            'with-value': (_this$getInputValue = this.getInputValue()) === null || _this$getInputValue === void 0 ? void 0 : _this$getInputValue.length,
            'has-error': !!help,
            'is-disabled': disabled
          }),
          onClick: function onClick(event) {
            // Don't focus the input if the click event is from the error message.
            if (
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore - event.target.className is not in the type definition.
            event.target.className !== 'components-base-control__help' && _this3.input.current) {
              _this3.input.current.focus();
            }
          }
        }, isSearchable && (0,react.createElement)(icon/* default */.A, {
          className: "woocommerce-select-control__control-icon",
          icon: search/* default */.A
        }), inlineTags && (0,react.createElement)(tags, {
          onChange: onChange,
          showClearButton: showClearButton,
          selected: this.props.selected
        }), (0,react.createElement)("div", {
          className: "components-base-control__field"
        }, !!label && (0,react.createElement)("label", {
          htmlFor: "woocommerce-select-control-".concat(instanceId, "__control-input"),
          className: "components-base-control__label"
        }, label), this.renderInput(), inlineTags && (0,react.createElement)("span", {
          id: "search-inline-input-".concat(instanceId),
          className: "screen-reader-text"
        }, (0,build_module.__)('Move backward for selected items', 'woocommerce')), !!help && (0,react.createElement)("p", {
          id: "woocommerce-select-control-".concat(instanceId, "__help"),
          className: "components-base-control__help"
        }, help)))
        /* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
      );
    }
  }]);
  return Control;
}(react.Component);
/* harmony default export */ const control = (Control);
try {
    // @ts-ignore
    Control.displayName = "Control";
    // @ts-ignore
    Control.__docgenInfo = { "description": "A search control to allow user input to filter the options.", "displayName": "Control", "props": { "hasTags": { "defaultValue": null, "description": "Bool to determine if tags should be rendered.", "name": "hasTags", "required": false, "type": { "name": "boolean" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "string | Element" } }, "inlineTags": { "defaultValue": null, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": null, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "instanceId": { "defaultValue": null, "description": "ID of the main SelectControl instance.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "listboxId": { "defaultValue": null, "description": "ID used for a11y in the listbox.", "name": "listboxId", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "Function called when the input is blurred.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": true, "type": { "name": "(selected: Option[]) => void" } }, "onSearch": { "defaultValue": null, "description": "Function called when input field is changed or focused.", "name": "onSearch", "required": true, "type": { "name": "(query: string) => void" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "query": { "defaultValue": null, "description": "Search query entered by user.", "name": "query", "required": false, "type": { "name": "string | null" } }, "selected": { "defaultValue": null, "description": "An array of objects describing selected values. If the label of the selected\nvalue is omitted, the Tag of that value will not be rendered inside the\nsearch box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "showAllOnFocus": { "defaultValue": null, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": null, "description": "Control input autocomplete field, defaults: off.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "setExpanded": { "defaultValue": null, "description": "Function to execute when the control should be expanded or collapsed.", "name": "setExpanded", "required": true, "type": { "name": "(expanded: boolean) => void" } }, "updateSearchOptions": { "defaultValue": null, "description": "Function to execute when the search value changes.", "name": "updateSearchOptions", "required": true, "type": { "name": "(query: string) => void" } }, "decrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should decrement the selected index.", "name": "decrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "incrementSelectedIndex": { "defaultValue": null, "description": "Function to execute when keyboard navigation should increment the selected index.", "name": "incrementSelectedIndex", "required": true, "type": { "name": "() => void" } }, "multiple": { "defaultValue": null, "description": "Multi-select mode allows multiple options to be selected.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "isFocused": { "defaultValue": null, "description": "Is the control currently focused.", "name": "isFocused", "required": false, "type": { "name": "boolean" } }, "activeId": { "defaultValue": null, "description": "ID for accessibility purposes. aria-activedescendant will be set to this value.", "name": "activeId", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "Disable the control.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "isExpanded": { "defaultValue": null, "description": "Is the control currently expanded. This is for accessibility purposes.", "name": "isExpanded", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": null, "description": "The type of input to use for the search field.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "ariaLabel": { "defaultValue": null, "description": "The aria label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Class name to be added to the input.", "name": "className", "required": false, "type": { "name": "string" } }, "showClearButton": { "defaultValue": null, "description": "Show the clear button.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/control.tsx#Control"] = { docgenInfo: Control.__docgenInfo, name: "Control", path: "../../packages/js/components/src/select-control/control.tsx#Control" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/select-control/index.tsx



































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
function select_control_createSuper(Derived) {
  var hasNativeReflectConstruct = select_control_isNativeReflectConstruct();
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
function select_control_isNativeReflectConstruct() {
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




var initialState = {
  isExpanded: false,
  isFocused: false,
  query: '',
  searchOptions: []
};

/**
 * A search box which filters options while typing,
 * allowing a user to select from an option from a filtered list.
 */
var SelectControl = /*#__PURE__*/function (_Component) {
  (0,inherits/* default */.A)(SelectControl, _Component);
  var _super = select_control_createSuper(SelectControl);
  function SelectControl(props) {
    var _this;
    (0,classCallCheck/* default */.A)(this, SelectControl);
    _this = _super.call(this, props);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "node", null);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "activePromise", null);
    (0,defineProperty/* default */.A)((0,assertThisInitialized/* default */.A)(_this), "cacheSearchOptions", []);
    var selected = props.selected,
      options = props.options,
      excludeSelectedOptions = props.excludeSelectedOptions;
    _this.state = _objectSpread(_objectSpread({}, initialState), {}, {
      searchOptions: [],
      selectedIndex: selected && options !== null && options !== void 0 && options.length && !excludeSelectedOptions ? options.findIndex(function (option) {
        return option.key === selected;
      }) : null
    });
    _this.bindNode = _this.bindNode.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.decrementSelectedIndex = _this.decrementSelectedIndex.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.incrementSelectedIndex = _this.incrementSelectedIndex.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.onAutofillChange = _this.onAutofillChange.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.updateSearchOptions = (0,lodash.debounce)(_this.updateSearchOptions.bind((0,assertThisInitialized/* default */.A)(_this)), props.searchDebounceTime);
    _this.search = _this.search.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.selectOption = _this.selectOption.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.setExpanded = _this.setExpanded.bind((0,assertThisInitialized/* default */.A)(_this));
    _this.setNewValue = _this.setNewValue.bind((0,assertThisInitialized/* default */.A)(_this));
    return _this;
  }
  (0,createClass/* default */.A)(SelectControl, [{
    key: "bindNode",
    value: function bindNode(node) {
      this.node = node;
    }
  }, {
    key: "reset",
    value: function reset() {
      var selected = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.getSelected();
      var _this$props = this.props,
        multiple = _this$props.multiple,
        excludeSelectedOptions = _this$props.excludeSelectedOptions;
      var newState = _objectSpread({}, initialState);
      // Reset selectedIndex if single selection.
      if (!multiple && (0,lodash.isArray)(selected) && selected.length && selected[0].key) {
        newState.selectedIndex = !excludeSelectedOptions ? this.props.options.findIndex(function (i) {
          return i.key === selected[0].key;
        }) : null;
      }
      this.setState(newState);
    }
  }, {
    key: "handleFocusOutside",
    value: function handleFocusOutside() {
      this.reset();
    }
  }, {
    key: "hasMultiple",
    value: function hasMultiple() {
      var _this$props2 = this.props,
        multiple = _this$props2.multiple,
        selected = _this$props2.selected;
      if (!multiple) {
        return false;
      }
      if (Array.isArray(selected)) {
        return selected.some(function (item) {
          return Boolean(item.label);
        });
      }
      return Boolean(selected);
    }
  }, {
    key: "getSelected",
    value: function getSelected() {
      var _this$props3 = this.props,
        multiple = _this$props3.multiple,
        options = _this$props3.options,
        selected = _this$props3.selected;

      // Return the passed value if an array is provided.
      if (multiple || Array.isArray(selected)) {
        return selected;
      }
      var selectedOption = options.find(function (option) {
        return option.key === selected;
      });
      return selectedOption ? [selectedOption] : [];
    }
  }, {
    key: "selectOption",
    value: function selectOption(option) {
      var _this$props4 = this.props,
        multiple = _this$props4.multiple,
        selected = _this$props4.selected;
      var newSelected = multiple && (0,lodash.isArray)(selected) ? [].concat((0,toConsumableArray/* default */.A)(selected), [option]) : [option];
      this.reset(newSelected);
      var oldSelected = Array.isArray(selected) ? selected : [{
        key: selected
      }];
      var isSelected = oldSelected.findIndex(function (val) {
        return val.key === option.key;
      });
      if (isSelected === -1) {
        this.setNewValue(newSelected);
      }

      // After selecting option, the list will reset and we'd need to correct selectedIndex.
      var newSelectedIndex = this.props.excludeSelectedOptions ?
      // Since we're excluding the selected option, invalidate selection
      // so re-focusing wont immediately set it to the neighbouring option.
      null : this.getOptions().findIndex(function (i) {
        return i.key === option.key;
      });
      this.setState({
        selectedIndex: newSelectedIndex
      });
    }
  }, {
    key: "setNewValue",
    value: function setNewValue(newValue) {
      var _this$props5 = this.props,
        onChange = _this$props5.onChange,
        selected = _this$props5.selected,
        multiple = _this$props5.multiple;
      var query = this.state.query;
      // Trigger a change if the selected value is different and pass back
      // an array or string depending on the original value.
      if (multiple || Array.isArray(selected)) {
        onChange(newValue, query);
      } else {
        onChange(newValue.length > 0 ? newValue[0].key : '', query);
      }
    }
  }, {
    key: "decrementSelectedIndex",
    value: function decrementSelectedIndex() {
      var selectedIndex = this.state.selectedIndex;
      var options = this.getOptions();
      var nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex === 0 ? options.length : selectedIndex) - 1 : options.length - 1;
      this.setState({
        selectedIndex: nextSelectedIndex
      });
    }
  }, {
    key: "incrementSelectedIndex",
    value: function incrementSelectedIndex() {
      var selectedIndex = this.state.selectedIndex;
      var options = this.getOptions();
      var nextSelectedIndex = (0,lodash.isNumber)(selectedIndex) ? (selectedIndex + 1) % options.length : 0;
      this.setState({
        selectedIndex: nextSelectedIndex
      });
    }
  }, {
    key: "announce",
    value: function announce(searchOptions) {
      var debouncedSpeak = this.props.debouncedSpeak;
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
  }, {
    key: "getOptions",
    value: function getOptions() {
      var _this$props6 = this.props,
        isSearchable = _this$props6.isSearchable,
        options = _this$props6.options,
        excludeSelectedOptions = _this$props6.excludeSelectedOptions;
      var searchOptions = this.state.searchOptions;
      var selected = this.getSelected();
      var selectedKeys = (0,lodash.isArray)(selected) ? selected.map(function (option) {
        return option.key;
      }) : [];
      var shownOptions = isSearchable ? searchOptions : options;
      if (excludeSelectedOptions) {
        return shownOptions === null || shownOptions === void 0 ? void 0 : shownOptions.filter(function (option) {
          return !selectedKeys.includes(option.key);
        });
      }
      return shownOptions;
    }
  }, {
    key: "getOptionsByQuery",
    value: function getOptionsByQuery(options, query) {
      var _this$props7 = this.props,
        getSearchExpression = _this$props7.getSearchExpression,
        maxResults = _this$props7.maxResults,
        onFilter = _this$props7.onFilter;
      var filtered = [];

      // Create a regular expression to filter the options.
      var expression = getSearchExpression((0,lodash.escapeRegExp)(query ? query.trim() : ''));
      var search = expression ? new RegExp(expression, 'i') : /^$/;
      for (var i = 0; i < options.length; i++) {
        var option = options[i];

        // Merge label into keywords
        var _option$keywords = option.keywords,
          keywords = _option$keywords === void 0 ? [] : _option$keywords;
        if (typeof option.label === 'string') {
          keywords = [].concat((0,toConsumableArray/* default */.A)(keywords), [option.label]);
        }
        var isMatch = keywords.some(function (keyword) {
          return search.test(keyword);
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
  }, {
    key: "setExpanded",
    value: function setExpanded(value) {
      this.setState({
        isExpanded: value
      });
    }
  }, {
    key: "search",
    value: function search(query) {
      var _this2 = this;
      var cacheSearchOptions = this.cacheSearchOptions || [];
      var searchOptions = query !== null && !query.length && !this.props.hideBeforeSearch ? cacheSearchOptions : this.getOptionsByQuery(cacheSearchOptions, query);
      this.setState({
        query: query,
        isFocused: true,
        searchOptions: searchOptions,
        selectedIndex: query && (query === null || query === void 0 ? void 0 : query.length) > 0 ? null : this.state.selectedIndex // Only reset selectedIndex if we're actually searching.
      }, function () {
        var _this2$getOptions;
        _this2.setState({
          isExpanded: Boolean((_this2$getOptions = _this2.getOptions()) === null || _this2$getOptions === void 0 ? void 0 : _this2$getOptions.length)
        });
      });
      this.updateSearchOptions(query);
    }
  }, {
    key: "updateSearchOptions",
    value: function updateSearchOptions(query) {
      var _this3 = this;
      var _this$props8 = this.props,
        hideBeforeSearch = _this$props8.hideBeforeSearch,
        options = _this$props8.options,
        onSearch = _this$props8.onSearch;
      var promise = this.activePromise = Promise.resolve(onSearch(options, query)).then(function (promiseOptions) {
        if (promise !== _this3.activePromise) {
          // Another promise has become active since this one was asked to resolve, so do nothing,
          // or else we might end triggering a race condition updating the state.
          return;
        }
        _this3.cacheSearchOptions = promiseOptions;

        // Get all options if `hideBeforeSearch` is enabled and query is not null.
        var searchOptions = query !== null && !query.length && !hideBeforeSearch ? promiseOptions : _this3.getOptionsByQuery(promiseOptions, query);
        _this3.setState({
          searchOptions: searchOptions,
          selectedIndex: query && (query === null || query === void 0 ? void 0 : query.length) > 0 ? null : _this3.state.selectedIndex // Only reset selectedIndex if we're actually searching.
        }, function () {
          _this3.setState({
            isExpanded: Boolean(_this3.getOptions().length)
          });
          _this3.announce(searchOptions);
        });
      });
    }
  }, {
    key: "onAutofillChange",
    value: function onAutofillChange(event) {
      var options = this.props.options;
      var searchOptions = this.getOptionsByQuery(options, event.target.value);
      if (searchOptions.length === 1) {
        this.selectOption(searchOptions[0]);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props9 = this.props,
        autofill = _this$props9.autofill,
        children = _this$props9.children,
        className = _this$props9.className,
        disabled = _this$props9.disabled,
        controlClassName = _this$props9.controlClassName,
        inlineTags = _this$props9.inlineTags,
        instanceId = _this$props9.instanceId,
        isSearchable = _this$props9.isSearchable,
        options = _this$props9.options;
      var _this$state = this.state,
        isExpanded = _this$state.isExpanded,
        isFocused = _this$state.isFocused,
        selectedIndex = _this$state.selectedIndex;
      var hasMultiple = this.hasMultiple();
      var _ref = (0,lodash.isNumber)(selectedIndex) && options[selectedIndex] || {},
        _ref$key = _ref.key,
        selectedKey = _ref$key === void 0 ? '' : _ref$key;
      var listboxId = isExpanded ? "woocommerce-select-control__listbox-".concat(instanceId) : undefined;
      var activeId = isExpanded ? "woocommerce-select-control__option-".concat(instanceId, "-").concat(selectedKey) : undefined;
      return (0,react.createElement)("div", {
        className: classnames_default()('woocommerce-select-control', className, {
          'has-inline-tags': hasMultiple && inlineTags,
          'is-focused': isFocused,
          'is-searchable': isSearchable
        }),
        ref: this.bindNode
      }, autofill && (0,react.createElement)("input", {
        onChange: this.onAutofillChange,
        name: autofill,
        type: "text",
        className: "woocommerce-select-control__autofill-input",
        tabIndex: -1
      }), children, (0,react.createElement)(control, {
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
        hasTags: hasMultiple,
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
      }), !inlineTags && hasMultiple && (0,react.createElement)(tags, {
        onChange: this.props.onChange,
        showClearButton: this.props.showClearButton,
        selected: this.getSelected()
      }), isExpanded && (0,react.createElement)(list, {
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
        setExpanded: this.setExpanded
      }));
    }
  }]);
  return SelectControl;
}(react.Component);
(0,defineProperty/* default */.A)(SelectControl, "defaultProps", {
  excludeSelectedOptions: true,
  getSearchExpression: lodash.identity,
  inlineTags: false,
  isSearchable: false,
  onChange: lodash.noop,
  onFilter: lodash.identity,
  onSearch: function onSearch(options) {
    return Promise.resolve(options);
  },
  maxResults: 0,
  multiple: false,
  searchDebounceTime: 0,
  searchInputType: 'search',
  selected: [],
  showAllOnFocus: false,
  showClearButton: false,
  hideBeforeSearch: false,
  staticList: false,
  autoComplete: 'off'
});
/* harmony default export */ const select_control = ((0,compose/* default */.A)(with_spoken_messages/* default */.A, with_instance_id/* default */.A, with_focus_outside/* default */.A // this MUST be the innermost HOC as it calls handleFocusOutside
)(SelectControl));
try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "A search box which filters options while typing,\nallowing a user to select from an option from a filtered list.", "displayName": "SelectControl", "props": { "autofill": { "defaultValue": null, "description": "Name to use for the autofill field, not used if no string is passed.", "name": "autofill", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "A renderable component (or string) which will be displayed before the `Control` of this component.", "name": "children", "required": false, "type": { "name": "ReactNode" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "controlClassName": { "defaultValue": null, "description": "Class name applied to control wrapper.", "name": "controlClassName", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "Allow the select options to be disabled.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "excludeSelectedOptions": { "defaultValue": { value: "true" }, "description": "Exclude already selected options from the options list.", "name": "excludeSelectedOptions", "required": false, "type": { "name": "boolean" } }, "onFilter": { "defaultValue": null, "description": "Add or remove items to the list of options after filtering,\npassed the array of filtered options and should return an array of options.", "name": "onFilter", "required": false, "type": { "name": "((options: Option[], query: string | null) => Option[])" } }, "getSearchExpression": { "defaultValue": null, "description": "Function to add regex expression to the filter the results, passed the search query.", "name": "getSearchExpression", "required": false, "type": { "name": "((query: string) => RegExp)" } }, "help": { "defaultValue": null, "description": "Help text to be appended beneath the input.", "name": "help", "required": false, "type": { "name": "string | Element" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "isSearchable": { "defaultValue": { value: "false" }, "description": "Allow the select options to be filtered by search input.", "name": "isSearchable", "required": false, "type": { "name": "boolean" } }, "label": { "defaultValue": null, "description": "A label to use for the main input.", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((selected: string | Option[], query?: string | null) => void)" } }, "onSearch": { "defaultValue": { value: "( options: Option[] ) => Promise.resolve( options )" }, "description": "Function run after search query is updated, passed previousOptions and query,\nshould return a promise with an array of updated options.", "name": "onSearch", "required": false, "type": { "name": "((previousOptions: Option[], query: string | null) => Promise<Option[]>)" } }, "options": { "defaultValue": null, "description": "An array of objects for the options list.  The option along with its key, label and\nvalue will be returned in the onChange event.", "name": "options", "required": true, "type": { "name": "Option[]" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "searchDebounceTime": { "defaultValue": { value: "0" }, "description": "Time in milliseconds to debounce the search function after typing.", "name": "searchDebounceTime", "required": false, "type": { "name": "number" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "Selected" } }, "maxResults": { "defaultValue": { value: "0" }, "description": "A limit for the number of results shown in the options menu.  Set to 0 for no limit.", "name": "maxResults", "required": false, "type": { "name": "number" } }, "multiple": { "defaultValue": { value: "false" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "searchInputType": { "defaultValue": { value: "search" }, "description": "The input type for the search box control.", "name": "searchInputType", "required": false, "type": { "name": "HTMLInputTypeAttribute" } }, "hideBeforeSearch": { "defaultValue": { value: "false" }, "description": "Only show list options after typing a search query.", "name": "hideBeforeSearch", "required": false, "type": { "name": "boolean" } }, "showAllOnFocus": { "defaultValue": { value: "false" }, "description": "Show all options on focusing, even if a query exists.", "name": "showAllOnFocus", "required": false, "type": { "name": "boolean" } }, "staticList": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticList", "required": false, "type": { "name": "boolean" } }, "autoComplete": { "defaultValue": { value: "off" }, "description": "autocomplete prop for the Control input field.", "name": "autoComplete", "required": false, "type": { "name": "string" } }, "instanceId": { "defaultValue": null, "description": "Instance ID for the component.", "name": "instanceId", "required": false, "type": { "name": "number" } }, "debouncedSpeak": { "defaultValue": null, "description": "From withSpokenMessages", "name": "debouncedSpeak", "required": false, "type": { "name": "((message: string, assertive?: string) => void)" } }, "ariaLabel": { "defaultValue": null, "description": "aria-label for the search input.", "name": "ariaLabel", "required": false, "type": { "name": "string" } }, "onBlur": { "defaultValue": null, "description": "On Blur callback.", "name": "onBlur", "required": false, "type": { "name": "(() => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/select-control/index.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/select-control/index.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/tag/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/close-small.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");


/**
 * External dependencies
 */







var Tag = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(function (_ref, removeButtonRef) {
  var id = _ref.id,
    label = _ref.label,
    popoverContents = _ref.popoverContents,
    remove = _ref.remove,
    screenReaderLabel = _ref.screenReaderLabel,
    className = _ref.className;
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(_useState, 2),
    isVisible = _useState2[0],
    setIsVisible = _useState2[1];
  var instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)(Tag);
  screenReaderLabel = screenReaderLabel || label;
  if (!label) {
    // A null label probably means something went wrong
    // @todo Maybe this should be a loading indicator?
    return null;
  }
  label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__/* .decodeEntities */ .S)(label);
  var classes = classnames__WEBPACK_IMPORTED_MODULE_1___default()('woocommerce-tag', className, {
    'has-remove': !!remove
  });
  var labelId = "woocommerce-tag__label-".concat(instanceId);
  var labelTextNode = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: "screen-reader-text"
  }, screenReaderLabel), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    "aria-hidden": "true"
  }, label));
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: classes
  }, popoverContents ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    className: "woocommerce-tag__text",
    id: labelId,
    onClick: function onClick() {
      return setIsVisible(true);
    }
  }, labelTextNode) : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)("span", {
    className: "woocommerce-tag__text",
    id: labelId
  }, labelTextNode), popoverContents && isVisible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
    onClose: function onClose() {
      return setIsVisible(false);
    }
  }, popoverContents), remove && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
    className: "woocommerce-tag__remove",
    ref: removeButtonRef,
    onClick: remove(id),
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(
    // translators: %s is the name of the tag being removed.
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Remove %s', 'woocommerce'), label),
    "aria-describedby": labelId
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A,
    size: 20,
    className: "clear-icon"
  })));
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

/***/ "../../packages/js/currency/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  uU: () => (/* reexport */ utils_CurrencyFactory)
});

// UNUSED EXPORTS: CurrencyContext, default, getCurrencyData, getFilteredCurrencyInstance

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js
var es_parse_int = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-float.js
var es_parse_float = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-float.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-nan.js
var es_number_is_nan = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-nan.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js
var es_number_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.to-fixed.js
var es_number_to_fixed = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.to-fixed.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@3.6.1/node_modules/@wordpress/html-entities/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-finite.js
var es_number_is_finite = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.is-finite.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js
var es_string_split = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.parse-float.js
var es_number_parse_float = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.number.parse-float.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/locutus@2.0.16/node_modules/locutus/php/strings/number_format.js
var number_format = __webpack_require__("../../node_modules/.pnpm/locutus@2.0.16/node_modules/locutus/php/strings/number_format.js");
var number_format_default = /*#__PURE__*/__webpack_require__.n(number_format);
;// CONCATENATED MODULE: ../../packages/js/number/src/index.ts











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
      _defineProperty(e, r, t[r]);
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
 * Number formatting configuration object
 *
 * @typedef {Object} NumberConfig
 * @property {number|string|null} [precision]         Decimal precision.
 * @property {string}             [decimalSeparator]  Decimal separator.
 * @property {string}             [thousandSeparator] Character used to separate thousands groups.
 */

/**
 * Formats a number using site's current locale
 *
 * @see http://locutus.io/php/strings/number_format/
 * @param {NumberConfig}  numberConfig Number formatting configuration object.
 * @param {number|string} number       number to format
 * @return {string} A formatted string.
 */
function numberFormat(_ref, number) {
  var _ref$precision = _ref.precision,
    precision = _ref$precision === void 0 ? null : _ref$precision,
    _ref$decimalSeparator = _ref.decimalSeparator,
    decimalSeparator = _ref$decimalSeparator === void 0 ? '.' : _ref$decimalSeparator,
    _ref$thousandSeparato = _ref.thousandSeparator,
    thousandSeparator = _ref$thousandSeparato === void 0 ? ',' : _ref$thousandSeparato;
  if (number === undefined) {
    return '';
  }
  if (typeof number !== 'number') {
    number = parseFloat(number);
  }
  if (isNaN(number)) {
    return '';
  }
  var parsedPrecision = precision === null ? NaN : Number(precision);
  if (isNaN(parsedPrecision)) {
    var _number$toString$spli = number.toString().split('.'),
      _number$toString$spli2 = (0,slicedToArray/* default */.A)(_number$toString$spli, 2),
      decimals = _number$toString$spli2[1];
    parsedPrecision = decimals ? decimals.length : 0;
  }
  return number_format_default()(number, parsedPrecision, decimalSeparator, thousandSeparator);
}

/**
 * Formats a number as average or number string according to the given `type`.
 *  - `type = 'average'` returns a rounded `Number`
 *  - `type = 'number'` returns a formatted `String`
 *
 * @param {NumberConfig} numberConfig number formatting configuration object.
 * @param {string}       type         of number to format, `'average'` or `'number'`
 * @param {number}       value        to format.
 * @return {string | number | null} A formatted string.
 */
function formatValue(numberConfig, type, value) {
  if (!Number.isFinite(value)) {
    return null;
  }
  switch (type) {
    case 'average':
      return Math.round(value);
    case 'number':
      return numberFormat(_objectSpread(_objectSpread({}, numberConfig), {}, {
        precision: null
      }), value);
  }
  return null;
}

/**
 * Calculates the delta/percentage change between two numbers.
 *
 * @param {number} primaryValue   the value to calculate change for.
 * @param {number} secondaryValue the baseline which to calculdate the change against.
 * @return {?number} Percent change between the primaryValue from the secondaryValue.
 */
function calculateDelta(primaryValue, secondaryValue) {
  if (!Number.isFinite(primaryValue) || !Number.isFinite(secondaryValue)) {
    return null;
  }
  if (secondaryValue === 0) {
    return 0;
  }
  return Math.round((primaryValue - secondaryValue) / secondaryValue * 100);
}

/**
 * Parse a string into a number using site's current config
 *
 * @param {NumberConfig} numberConfig Number formatting configuration object.
 * @param {string}       value        value to parse
 * @return {string} A parsed number.
 */
function parseNumber(_ref2, value) {
  var _ref2$precision = _ref2.precision,
    precision = _ref2$precision === void 0 ? null : _ref2$precision,
    _ref2$decimalSeparato = _ref2.decimalSeparator,
    decimalSeparator = _ref2$decimalSeparato === void 0 ? '.' : _ref2$decimalSeparato,
    _ref2$thousandSeparat = _ref2.thousandSeparator,
    thousandSeparator = _ref2$thousandSeparat === void 0 ? ',' : _ref2$thousandSeparat;
  if (typeof value !== 'string' || value === '') {
    return '';
  }
  var parsedPrecision = precision === null ? NaN : Number(precision);
  if (isNaN(parsedPrecision)) {
    var _value$split = value.split(decimalSeparator),
      _value$split2 = _slicedToArray(_value$split, 2),
      decimals = _value$split2[1];
    parsedPrecision = decimals ? decimals.length : 0;
  }
  var parsedValue = value;
  if (thousandSeparator) {
    parsedValue = parsedValue.replace(new RegExp("\\".concat(thousandSeparator), 'g'), '');
  }
  if (decimalSeparator) {
    parsedValue = parsedValue.replace(new RegExp("\\".concat(decimalSeparator), 'g'), '.');
  }
  return Number.parseFloat(parsedValue).toFixed(parsedPrecision);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/currency/src/utils.tsx











function utils_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function utils_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? utils_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : utils_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}










/**
 * External dependencies
 */






/**
 * @typedef {import('@woocommerce/number').NumberConfig} NumberConfig
 */
/**
 * @typedef {Object} CurrencyProps
 * @property {string} code           Currency ISO code.
 * @property {string} symbol         Symbol, can be multi-character. Should be in plain text, w/o HTML markup. HTML entities will be decoded.
 * @property {string} symbolPosition Where the symbol should be relative to the amount. One of `'left' | 'right' | 'left_space | 'right_space'`.
 * @typedef {NumberConfig & CurrencyProps} CurrencyConfig
 */

/**
 *
 * @param {CurrencyConfig} currencySetting
 * @return {Object} currency object
 */
var CurrencyFactoryBase = function CurrencyFactoryBase(currencySetting) {
  var currency;
  function stripTags(str) {
    // sanitize Polyfill - see https://github.com/WordPress/WordPress/blob/master/wp-includes/js/wp-sanitize.js
    var strippedStr = str.replace(/<!--[\s\S]*?(-->|$)/g, '').replace(/<(script|style)[^>]*>[\s\S]*?(<\/\1>|$)/gi, '').replace(/<\/?[a-z][\s\S]*?(>|$)/gi, '');
    if (strippedStr !== str) {
      return stripTags(strippedStr);
    }
    return strippedStr;
  }

  /**
   * Get the default price format from a currency.
   *
   * @param {CurrencyConfig} config Currency configuration.
   * @return {string} Price format.
   */
  function getPriceFormat(config) {
    if (config.priceFormat) {
      return stripTags(config.priceFormat.toString());
    }
    switch (config.symbolPosition) {
      case 'left':
        return '%1$s%2$s';
      case 'right':
        return '%2$s%1$s';
      case 'left_space':
        return '%1$s %2$s';
      case 'right_space':
        return '%2$s %1$s';
    }
    return '%1$s%2$s';
  }
  function setCurrency(setting) {
    var defaultCurrency = {
      code: 'USD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    };
    var config = utils_objectSpread(utils_objectSpread({}, defaultCurrency), setting);
    var precision = config.precision;
    if (precision === null) {
      // eslint-disable-next-line no-console
      console.warn('Currency precision is null');
      // eslint-enable-next-line no-console

      precision = NaN;
    } else if (typeof precision === 'string') {
      precision = parseInt(precision, 10);
    }
    currency = {
      code: config.code.toString(),
      symbol: (0,build_module/* decodeEntities */.S)(config.symbol.toString()),
      symbolPosition: config.symbolPosition.toString(),
      decimalSeparator: config.decimalSeparator.toString(),
      priceFormat: getPriceFormat(config),
      thousandSeparator: config.thousandSeparator.toString(),
      precision: precision
    };
  }

  /**
   * Formats money value.
   *
   * @param {number|string} number          number to format
   * @param {boolean}       [useCode=false] Set to `true` to use the currency code instead of the symbol.
   * @return {?string} A formatted string.
   */
  function formatAmount(number) {
    var useCode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    var formattedNumber = numberFormat(currency, number);
    if (formattedNumber === '') {
      return formattedNumber;
    }
    var _currency = currency,
      priceFormat = _currency.priceFormat,
      symbol = _currency.symbol,
      code = _currency.code;

    // eslint-disable-next-line @wordpress/valid-sprintf
    return (0,i18n_build_module/* sprintf */.nv)(priceFormat, useCode ? code : symbol, formattedNumber);
  }

  /**
   * Formats money value.
   *
   * @deprecated
   * @param {number|string} number number to format
   * @return {?string} A formatted string.
   */
  function formatCurrency(number) {
    (0,deprecated_build_module/* default */.A)('Currency().formatCurrency', {
      version: '5.0.0',
      alternative: 'Currency().formatAmount',
      plugin: 'WooCommerce',
      hint: '`formatAmount` accepts the same arguments as formatCurrency'
    });
    return formatAmount(number);
  }

  /**
   * Get formatted data for a country from supplied locale and symbol info.
   *
   * @param {string} countryCode     Country code.
   * @param {Object} localeInfo      Locale info by country code.
   * @param {Object} currencySymbols Currency symbols by symbol code. HTML entities will be decoded.
   * @return {CurrencyConfig | {}} Formatted currency data for country.
   */
  function getDataForCountry(countryCode) {
    var localeInfo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var currencySymbols = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    var countryInfo = localeInfo[countryCode];
    if (!countryInfo) {
      return {};
    }
    var symbol = currencySymbols[countryInfo.currency_code];
    if (!symbol) {
      return {};
    }
    return {
      code: countryInfo.currency_code,
      symbol: (0,build_module/* decodeEntities */.S)(symbol),
      symbolPosition: countryInfo.currency_pos,
      thousandSeparator: countryInfo.thousand_sep,
      decimalSeparator: countryInfo.decimal_sep,
      precision: countryInfo.num_decimals
    };
  }
  setCurrency(currencySetting);
  return {
    getCurrencyConfig: function getCurrencyConfig() {
      return utils_objectSpread({}, currency);
    },
    getDataForCountry: getDataForCountry,
    setCurrency: setCurrency,
    formatAmount: formatAmount,
    formatCurrency: formatCurrency,
    getPriceFormat: getPriceFormat,
    /**
     * Get the rounded decimal value of a number at the precision used for the current currency.
     * This is a work-around for fraction-cents, meant to be used like `wc_format_decimal`
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {number} The original number rounded to a decimal point
     */
    formatDecimal: function formatDecimal(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }
      if (Number.isNaN(number)) {
        return 0;
      }
      var _currency2 = currency,
        precision = _currency2.precision;
      return Math.round(number * Math.pow(10, precision)) / Math.pow(10, precision);
    },
    /**
     * Get the string representation of a floating point number to the precision used by the current currency.
     * This is different from `formatAmount` by not returning the currency symbol.
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {string}               The original number rounded to a decimal point
     */
    formatDecimalString: function formatDecimalString(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }
      if (Number.isNaN(number)) {
        return '';
      }
      var _currency3 = currency,
        precision = _currency3.precision;
      return number.toFixed(precision);
    },
    /**
     * Render a currency for display in a component.
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {Node|string} The number formatted as currency and rendered for display.
     */
    render: function render(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }
      if (number < 0) {
        return (0,react.createElement)("span", {
          className: "is-negative"
        }, formatAmount(number));
      }
      return formatAmount(number);
    }
  };
};
var utils_CurrencyFactory = CurrencyFactoryBase;

/**
 * Returns currency data by country/region. Contains code, symbol, position, thousands separator, decimal separator, and precision.
 *
 * Dev Note: When adding new currencies below, the exchange rate array should also be updated in WooCommerce Admin's `business-details.js`.
 *
 * @deprecated
 * @return {Object} Currency data.
 */
function getCurrencyData() {
  deprecated('getCurrencyData', {
    version: '3.1.0',
    alternative: 'CurrencyFactory.getDataForCountry',
    plugin: 'WooCommerce Admin',
    hint: 'Pass in the country, locale data, and symbol info to use getDataForCountry'
  });

  // See https://github.com/woocommerce/woocommerce-admin/issues/3101.
  return {
    US: {
      code: 'USD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    EU: {
      code: 'EUR',
      symbol: '€',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    IN: {
      code: 'INR',
      symbol: '₹',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    GB: {
      code: 'GBP',
      symbol: '£',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    BR: {
      code: 'BRL',
      symbol: 'R$',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    VN: {
      code: 'VND',
      symbol: '₫',
      symbolPosition: 'right',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 1
    },
    ID: {
      code: 'IDR',
      symbol: 'Rp',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 0
    },
    BD: {
      code: 'BDT',
      symbol: '৳',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 0
    },
    PK: {
      code: 'PKR',
      symbol: '₨',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    RU: {
      code: 'RUB',
      symbol: '₽',
      symbolPosition: 'right',
      thousandSeparator: ' ',
      decimalSeparator: ',',
      precision: 2
    },
    TR: {
      code: 'TRY',
      symbol: '₺',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    MX: {
      code: 'MXN',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    CA: {
      code: 'CAD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    }
  };
}
try {
    // @ts-ignore
    getCurrencyData.displayName = "getCurrencyData";
    // @ts-ignore
    getCurrencyData.__docgenInfo = { "description": "Returns currency data by country/region. Contains code, symbol, position, thousands separator, decimal separator, and precision.\n\nDev Note: When adding new currencies below, the exchange rate array should also be updated in WooCommerce Admin's `business-details.js`.", "displayName": "getCurrencyData", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/currency/src/utils.tsx#getCurrencyData"] = { docgenInfo: getCurrencyData.__docgenInfo, name: "getCurrencyData", path: "../../packages/js/currency/src/utils.tsx#getCurrencyData" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    utils_CurrencyFactory.displayName = "CurrencyFactory";
    // @ts-ignore
    utils_CurrencyFactory.__docgenInfo = { "description": "", "displayName": "CurrencyFactory", "props": { "precision": { "defaultValue": null, "description": "", "name": "precision", "required": false, "type": { "name": "string | number | null" } }, "decimalSeparator": { "defaultValue": null, "description": "", "name": "decimalSeparator", "required": false, "type": { "name": "string" } }, "thousandSeparator": { "defaultValue": null, "description": "", "name": "thousandSeparator", "required": false, "type": { "name": "string" } }, "code": { "defaultValue": null, "description": "", "name": "code", "required": false, "type": { "name": "string" } }, "symbol": { "defaultValue": null, "description": "", "name": "symbol", "required": false, "type": { "name": "string" } }, "symbolPosition": { "defaultValue": null, "description": "", "name": "symbolPosition", "required": false, "type": { "name": "enum", "value": [{ "value": "\"left\"" }, { "value": "\"right\"" }, { "value": "\"left_space\"" }, { "value": "\"right_space\"" }] } }, "priceFormat": { "defaultValue": null, "description": "", "name": "priceFormat", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/currency/src/utils.tsx#CurrencyFactory"] = { docgenInfo: utils_CurrencyFactory.__docgenInfo, name: "CurrencyFactory", path: "../../packages/js/currency/src/utils.tsx#CurrencyFactory" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js + 10 modules
var hooks_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js");
// EXTERNAL MODULE: ./setting.mock.js
var setting_mock = __webpack_require__("./setting.mock.js");
;// CONCATENATED MODULE: ../../packages/js/currency/src/currency-context.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var CURRENCY = (0,setting_mock/* getSetting */.P)('currency');
var appCurrency = utils_CurrencyFactory(CURRENCY);
var getFilteredCurrencyInstance = function getFilteredCurrencyInstance(query) {
  var config = appCurrency.getCurrencyConfig();
  /**
   * Filter the currency context. This affects all WooCommerce Admin currency formatting.
   *
   * @filter woocommerce_admin_report_currency
   * @param {Object} config Currency configuration.
   * @param {Object} query  Url query parameters.
   */
  var filteredConfig = applyFilters('woocommerce_admin_report_currency', config, query);
  return CurrencyFactory(filteredConfig);
};
var CurrencyContext = (0,react.createContext)(appCurrency // default value
);
;// CONCATENATED MODULE: ../../packages/js/currency/src/index.ts
/**
 * Internal dependencies
 */

/* harmony default export */ const src = ((/* unused pure expression or super */ null && (CurrencyFactory)));



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
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
/* harmony import */ var core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/qs@6.11.2/node_modules/qs/lib/index.js");
/* harmony import */ var qs__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(qs__WEBPACK_IMPORTED_MODULE_10__);







/**
 * External dependencies
 */




var isoDateFormat = 'YYYY-MM-DD';
var defaultDateTimeFormat = 'YYYY-MM-DDTHH:mm:ss';

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

var presetValues = [{
  value: 'today',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Today', 'woocommerce')
}, {
  value: 'yesterday',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Yesterday', 'woocommerce')
}, {
  value: 'week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Week to date', 'woocommerce')
}, {
  value: 'last_week',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last week', 'woocommerce')
}, {
  value: 'month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Month to date', 'woocommerce')
}, {
  value: 'last_month',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last month', 'woocommerce')
}, {
  value: 'quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Quarter to date', 'woocommerce')
}, {
  value: 'last_quarter',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last quarter', 'woocommerce')
}, {
  value: 'year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Year to date', 'woocommerce')
}, {
  value: 'last_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Last year', 'woocommerce')
}, {
  value: 'custom',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Custom', 'woocommerce')
}];
var periods = [{
  value: 'previous_period',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Previous period', 'woocommerce')
}, {
  value: 'previous_year',
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Previous year', 'woocommerce')
}];
var isValidMomentInput = function isValidMomentInput(input) {
  return moment__WEBPACK_IMPORTED_MODULE_7___default()(input).isValid();
};

/**
 * Adds timestamp to a string date.
 *
 * @param {moment.Moment} date      - Date as a moment object.
 * @param {string}        timeOfDay - Either `start`, `now` or `end` of the day.
 * @return {string} - String date with timestamp attached.
 */
var appendTimestamp = function appendTimestamp(date, timeOfDay) {
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
  if (moment__WEBPACK_IMPORTED_MODULE_7___default().isMoment(str)) {
    return str.isValid() ? str : null;
  }
  if (typeof str === 'string') {
    var date = moment__WEBPACK_IMPORTED_MODULE_7___default()(str, [isoDateFormat, format], true);
    return date.isValid() ? date : null;
  }
  throw new Error('toMoment requires a string to be passed as an argument');
}

/**
 * Given two dates, derive a string representation
 *
 * @param {moment.Moment} after  - start date
 * @param {moment.Moment} before - end date
 * @return {string} - text value for the supplied date range
 */
function getRangeLabel(after, before) {
  var isSameYear = after.year() === before.year();
  var isSameMonth = isSameYear && after.month() === before.month();
  var isSameDay = isSameYear && isSameMonth && after.isSame(before, 'day');
  var fullDateFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('MMM D, YYYY', 'woocommerce');
  if (isSameDay) {
    return after.format(fullDateFormat);
  } else if (isSameMonth) {
    var afterDate = after.date();
    return after.format(fullDateFormat).replace(String(afterDate), "".concat(afterDate, " - ").concat(before.date()));
  } else if (isSameYear) {
    var monthDayFormat = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('MMM D', 'woocommerce');
    return "".concat(after.format(monthDayFormat), " - ").concat(before.format(fullDateFormat));
  }
  return "".concat(after.format(fullDateFormat), " - ").concat(before.format(fullDateFormat));
}

/**
 * Gets the current time in the store time zone if set.
 *
 * @return {string} - Datetime string.
 */
function getStoreTimeZoneMoment() {
  if (!window.wcSettings || !window.wcSettings.timeZone) {
    return moment__WEBPACK_IMPORTED_MODULE_7___default()();
  }
  if (['+', '-'].includes(window.wcSettings.timeZone.charAt(0))) {
    return moment__WEBPACK_IMPORTED_MODULE_7___default()().utcOffset(window.wcSettings.timeZone);
  }
  return moment__WEBPACK_IMPORTED_MODULE_7___default()().tz(window.wcSettings.timeZone);
}

/**
 * Get a DateValue object for a period prior to the current period.
 *
 * @param {moment.DurationInputArg2} period  - the chosen period
 * @param {string}                   compare - `previous_period` or `previous_year`
 * @return {DateValue} - DateValue data about the selected period
 */
function getLastPeriod(period, compare) {
  var primaryStart = getStoreTimeZoneMoment().startOf(period).subtract(1, period);
  var primaryEnd = primaryStart.clone().endOf(period);
  var secondaryStart;
  var secondaryEnd;
  if (compare === 'previous_period') {
    if (period === 'year') {
      // Subtract two entire periods for years to take into account leap year
      secondaryStart = moment__WEBPACK_IMPORTED_MODULE_7___default()().startOf(period).subtract(2, period);
      secondaryEnd = secondaryStart.clone().endOf(period);
    } else {
      // Otherwise, use days in primary period to figure out how far to go back
      // This is necessary for calculating weeks instead of using `endOf`.
      var daysDiff = primaryEnd.diff(primaryStart, 'days');
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
  return {
    primaryStart: primaryStart,
    primaryEnd: primaryEnd,
    secondaryStart: secondaryStart,
    secondaryEnd: secondaryEnd
  };
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
  var primaryStart = getStoreTimeZoneMoment().startOf(period);
  var primaryEnd = getStoreTimeZoneMoment();
  var daysSoFar = primaryEnd.diff(primaryStart, 'days');
  var secondaryStart;
  var secondaryEnd;
  if (compare === 'previous_period') {
    secondaryStart = primaryStart.clone().subtract(1, period);
    secondaryEnd = primaryEnd.clone().subtract(1, period);
  } else {
    secondaryStart = primaryStart.clone().subtract(1, 'years');
    // Set the end time to 23:59:59.
    secondaryEnd = secondaryStart.clone().add(daysSoFar + 1, 'days').subtract(1, 'seconds');
  }
  return {
    primaryStart: primaryStart,
    primaryEnd: primaryEnd,
    secondaryStart: secondaryStart,
    secondaryEnd: secondaryEnd
  };
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
var getDateValue = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.memoize)(function (period, compare, after, before) {
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
      var difference = before.diff(after, 'days');
      if (compare === 'previous_period') {
        var secondaryEnd = after.clone().subtract(1, 'days');
        var secondaryStart = secondaryEnd.clone().subtract(difference, 'days');
        return {
          primaryStart: after,
          primaryEnd: before,
          secondaryStart: secondaryStart,
          secondaryEnd: secondaryEnd
        };
      }
      return {
        primaryStart: after,
        primaryEnd: before,
        secondaryStart: after.clone().subtract(1, 'years'),
        secondaryEnd: before.clone().subtract(1, 'years')
      };
  }
}, function (period, compare, after, before) {
  return [period, compare, after && after.format(), before && before.format()].join(':');
});

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
var getDateParamsFromQueryMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.memoize)(function (period, compare, after, before, defaultDateRange) {
  if (period && compare) {
    return {
      period: period,
      compare: compare,
      after: after ? moment__WEBPACK_IMPORTED_MODULE_7___default()(after) : null,
      before: before ? moment__WEBPACK_IMPORTED_MODULE_7___default()(before) : null
    };
  }
  var queryDefaults = (0,qs__WEBPACK_IMPORTED_MODULE_10__.parse)(defaultDateRange.replace(/&amp;/g, '&'));
  if (typeof queryDefaults.period !== 'string') {
    /* eslint-disable no-console */
    console.warn("Unexpected default period type ".concat(queryDefaults.period));
    /* eslint-enable no-console */
    queryDefaults.period = '';
  }
  if (typeof queryDefaults.compare !== 'string') {
    /* eslint-disable no-console */
    console.warn("Unexpected default compare type ".concat(queryDefaults.compare));
    /* eslint-enable no-console */
    queryDefaults.compare = '';
  }
  return {
    period: queryDefaults.period,
    compare: queryDefaults.compare,
    after: queryDefaults.after && isValidMomentInput(queryDefaults.after) ? moment__WEBPACK_IMPORTED_MODULE_7___default()(queryDefaults.after) : null,
    before: queryDefaults.before && isValidMomentInput(queryDefaults.before) ? moment__WEBPACK_IMPORTED_MODULE_7___default()(queryDefaults.before) : null
  };
}, function (period, compare, after, before, defaultDateRange) {
  return [period, compare, after, before, defaultDateRange].join(':');
});

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
var getDateParamsFromQuery = function getDateParamsFromQuery(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=month&compare=previous_year';
  var period = query.period,
    compare = query.compare,
    after = query.after,
    before = query.before;
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
var getCurrentDatesMemoized = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.memoize)(function (period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) {
  var primaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.find)(presetValues, function (item) {
    return item.value === period;
  });
  if (!primaryItem) {
    throw new Error("Cannot find period: ".concat(period));
  }
  var secondaryItem = (0,lodash__WEBPACK_IMPORTED_MODULE_8__.find)(periods, function (item) {
    return item.value === compare;
  });
  if (!secondaryItem) {
    throw new Error("Cannot find compare: ".concat(compare));
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
}, function (period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd) {
  return [period, compare, primaryStart && primaryStart.format(), primaryEnd && primaryEnd.format(), secondaryStart && secondaryStart.format(), secondaryEnd && secondaryEnd.format()].join(':');
});

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
var getCurrentDates = function getCurrentDates(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=month&compare=previous_year';
  var _getDateParamsFromQue = getDateParamsFromQuery(query, defaultDateRange),
    period = _getDateParamsFromQue.period,
    compare = _getDateParamsFromQue.compare,
    after = _getDateParamsFromQue.after,
    before = _getDateParamsFromQue.before;
  var dateValue = getDateValue(period, compare, after, before);
  if (!dateValue) {
    throw Error('Invalid date range');
  }
  var primaryStart = dateValue.primaryStart,
    primaryEnd = dateValue.primaryEnd,
    secondaryStart = dateValue.secondaryStart,
    secondaryEnd = dateValue.secondaryEnd;
  return getCurrentDatesMemoized(period, compare, primaryStart, primaryEnd, secondaryStart, secondaryEnd);
};

/**
 * Calculates the date difference between two dates. Used in calculating a matching date for previous period.
 *
 * @param {string} date  - Date to compare
 * @param {string} date2 - Secondary date to compare
 * @return {number}  - Difference in days.
 */
var getDateDifferenceInDays = function getDateDifferenceInDays(date, date2) {
  var _date = moment(date);
  var _date2 = moment(date2);
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
var getPreviousDate = function getPreviousDate(date, date1, date2) {
  var compare = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'previous_year';
  var interval = arguments.length > 4 ? arguments[4] : undefined;
  var dateMoment = moment(date);
  if (compare === 'previous_year') {
    return dateMoment.clone().subtract(1, 'years');
  }
  var _date1 = moment(date1);
  var _date2 = moment(date2);
  var difference = _date1.diff(_date2, interval);
  return dateMoment.clone().subtract(difference, interval);
};

/**
 * Returns the allowed selectable intervals for a specific query.
 *
 * @param {Query}  query            Current query
 * @param {string} defaultDateRange - the store's default date range
 * @return {Array} Array containing allowed intervals.
 */
function getAllowedIntervalsForQuery(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=&compare=previous_year';
  var _getDateParamsFromQue2 = getDateParamsFromQuery(query, defaultDateRange),
    period = _getDateParamsFromQue2.period;
  var allowed = [];
  if (period === 'custom') {
    var _getCurrentDates = getCurrentDates(query),
      primary = _getCurrentDates.primary;
    var differenceInDays = getDateDifferenceInDays(primary.before, primary.after);
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
function getIntervalForQuery(query) {
  var defaultDateRange = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'period=&compare=previous_year';
  var allowed = getAllowedIntervalsForQuery(query, defaultDateRange);
  var defaultInterval = allowed[0];
  var current = query.interval || defaultInterval;
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
function getChartTypeForQuery(_ref) {
  var chartType = _ref.chartType;
  if (chartType !== undefined && ['line', 'bar'].includes(chartType)) {
    return chartType;
  }
  return 'line';
}
var dayTicksThreshold = 63;
var weekTicksThreshold = 9;
var defaultTableDateFormat = 'm/d/Y';

/**
 * Returns d3 date formats for the current interval.
 * See https://github.com/d3/d3-time-format for chart formats.
 *
 * @param {string} interval Interval to get date formats for.
 * @param {number} [ticks]  Number of ticks the axis will have.
 * @return {string} Current interval.
 */
function getDateFormatsForIntervalD3(interval) {
  var ticks = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var screenReaderFormat = '%B %-d, %Y';
  var tooltipLabelFormat = '%B %-d, %Y';
  var xFormat = '%Y-%m-%d';
  var x2Format = '%b %Y';
  var tableFormat = defaultTableDateFormat;
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
    screenReaderFormat: screenReaderFormat,
    tooltipLabelFormat: tooltipLabelFormat,
    xFormat: xFormat,
    x2Format: x2Format,
    tableFormat: tableFormat
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
function getDateFormatsForIntervalPhp(interval) {
  var ticks = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var screenReaderFormat = 'F j, Y';
  var tooltipLabelFormat = 'F j, Y';
  var xFormat = 'Y-m-d';
  var x2Format = 'M Y';
  var tableFormat = defaultTableDateFormat;
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
      var escapedWeekOfStr = __('Week of', 'woocommerce').replace(/(\w)/g, '\\$1');
      screenReaderFormat = "".concat(escapedWeekOfStr, " F j, Y");
      tooltipLabelFormat = "".concat(escapedWeekOfStr, " F j, Y");
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
    screenReaderFormat: screenReaderFormat,
    tooltipLabelFormat: tooltipLabelFormat,
    xFormat: xFormat,
    x2Format: x2Format,
    tableFormat: tableFormat
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
function getDateFormatsForInterval(interval) {
  var ticks = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var option = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {
    type: 'd3'
  };
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
function loadLocaleData(_ref2) {
  var userLocale = _ref2.userLocale,
    weekdaysShort = _ref2.weekdaysShort;
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
var dateValidationMessages = {
  invalid: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Invalid date', 'woocommerce'),
  future: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Select a date in the past', 'woocommerce'),
  startAfterEnd: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Start date must be before end date', 'woocommerce'),
  endBeforeStart: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Start date must be before end date', 'woocommerce')
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
  var date = toMoment(format, value);
  if (!date) {
    return {
      date: null,
      error: dateValidationMessages.invalid
    };
  }
  if (moment__WEBPACK_IMPORTED_MODULE_7___default()().isBefore(date, 'day')) {
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
    date: date
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
  var startYear = new Date(startDate).getFullYear();
  var endYear = new Date(endDate).getFullYear();
  if (!isNaN(startYear) && !isNaN(endYear)) {
    // Check each year in the range
    for (var year = startYear; year <= endYear; year++) {
      if (isLeapYear(year)) {
        return true;
      }
    }
  }
  return false; // No leap years in the range or invalid date
}

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

/***/ "?bbf9":
/***/ (() => {

/* (ignored) */

/***/ })

}]);