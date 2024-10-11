(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2068,9028,964],{

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

/***/ "../../packages/js/components/src/rich-text-editor/stories/rich-text-editor.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  MultipleEditors: () => (/* binding */ MultipleEditors),
  "default": () => (/* binding */ rich_text_editor_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/registry.js + 2 modules
var registry = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/registry.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/components/registry-provider/context.js
var context = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/components/registry-provider/context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+core-data@4.4.5_react@17.0.2/node_modules/@wordpress/core-data/build-module/index.js + 25 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+core-data@4.4.5_react@17.0.2/node_modules/@wordpress/core-data/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+block-editor@9.8.0_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/block-editor/build-module/index.js + 683 modules
var block_editor_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+block-editor@9.8.0_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/@wordpress/block-editor/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js + 1 modules
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js + 8 modules
var slot_fill = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/slot-fill/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js + 8 modules
var popover = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+media-utils@3.4.1/node_modules/@wordpress/media-utils/build-module/index.js + 5 modules
var media_utils_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+media-utils@3.4.1/node_modules/@wordpress/media-utils/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/data/src/index.ts + 169 modules
var src = __webpack_require__("../../packages/js/data/src/index.ts");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keyboard-shortcuts@3.4.1_react@17.0.2/node_modules/@wordpress/keyboard-shortcuts/build-module/index.js + 8 modules
var keyboard_shortcuts_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keyboard-shortcuts@3.4.1_react@17.0.2/node_modules/@wordpress/keyboard-shortcuts/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-dispatch/use-dispatch.js
var use_dispatch = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-dispatch/use-dispatch.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-select/index.js + 7 modules
var use_select = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-select/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.4.1_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+blocks@11.21.0_react@17.0.2/node_modules/@wordpress/blocks/build-module/index.js + 67 modules
var blocks_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+blocks@11.21.0_react@17.0.2/node_modules/@wordpress/blocks/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/rich-text-editor/editor-writing-flow.tsx

/**
 * External dependencies
 */





var EditorWritingFlow = function EditorWritingFlow(_ref) {
  var blocks = _ref.blocks,
    onChange = _ref.onChange,
    _ref$placeholder = _ref.placeholder,
    placeholder = _ref$placeholder === void 0 ? '' : _ref$placeholder;
  var instanceId = (0,use_instance_id/* default */.A)(EditorWritingFlow);
  var firstBlock = blocks[0];
  var isEmpty = !blocks.length;
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore This action is available in the block editor data store.
  var _useDispatch = (0,use_dispatch/* default */.A)(block_editor_build_module/* store */.M_),
    insertBlock = _useDispatch.insertBlock,
    selectBlock = _useDispatch.selectBlock,
    __unstableSetEditorMode = _useDispatch.__unstableSetEditorMode;
  var _useSelect = (0,use_select/* default */.A)(function (select) {
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore This selector is available in the block editor data store.
      var _select = select(block_editor_build_module/* store */.M_),
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds,
        __unstableGetEditorMode = _select.__unstableGetEditorMode;
      return {
        editorMode: __unstableGetEditorMode(),
        selectedBlockClientIds: getSelectedBlockClientIds()
      };
    }, []),
    selectedBlockClientIds = _useSelect.selectedBlockClientIds,
    editorMode = _useSelect.editorMode;

  // This is a workaround to prevent focusing the block on initialization.
  // Changing to a mode other than "edit" ensures that no initial position
  // is found and no element gets subsequently focused.
  // See https://github.com/WordPress/gutenberg/blob/411b6eee8376e31bf9db4c15c92a80524ae38e9b/packages/block-editor/src/components/block-list/use-block-props/use-focus-first-element.js#L42
  var setEditorIsInitializing = function setEditorIsInitializing(isInitializing) {
    if (typeof __unstableSetEditorMode !== 'function') {
      return;
    }
    __unstableSetEditorMode(isInitializing ? 'initialized' : 'edit');
  };
  (0,react.useEffect)(function () {
    if (selectedBlockClientIds !== null && selectedBlockClientIds !== void 0 && selectedBlockClientIds.length || !firstBlock) {
      return;
    }
    setEditorIsInitializing(true);
    selectBlock(firstBlock.clientId);
  }, [firstBlock, selectedBlockClientIds]);
  (0,react.useEffect)(function () {
    if (isEmpty) {
      var initialBlock = (0,blocks_build_module/* createBlock */.Wv)('core/paragraph', {
        content: '',
        placeholder: placeholder
      });
      insertBlock(initialBlock);
      onChange([initialBlock]);
    }
  }, [isEmpty]);
  var maybeSetEditMode = function maybeSetEditMode() {
    if (editorMode === 'edit') {
      return;
    }
    setEditorIsInitializing(false);
  };
  return /* Gutenberg handles the keyboard events when focusing the content editable area. */(
    /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,react.createElement)("div", {
      className: "woocommerce-rich-text-editor__writing-flow",
      id: "woocommerce-rich-text-editor__writing-flow-".concat(instanceId),
      style: {
        cursor: isEmpty ? 'text' : 'initial'
      }
    }, (0,react.createElement)(block_editor_build_module/* BlockTools */.LJ, null, (0,react.createElement)(block_editor_build_module/* WritingFlow */.Pe
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore These are forwarded as props to the WritingFlow component.
    , {
      onClick: maybeSetEditMode,
      onFocus: maybeSetEditMode
    }, (0,react.createElement)(block_editor_build_module/* ObserveTyping */.us, null, (0,react.createElement)(block_editor_build_module/* BlockList */.m9, null)))))
    /* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
  );
};
try {
    // @ts-ignore
    EditorWritingFlow.displayName = "EditorWritingFlow";
    // @ts-ignore
    EditorWritingFlow.__docgenInfo = { "description": "", "displayName": "EditorWritingFlow", "props": { "blocks": { "defaultValue": null, "description": "", "name": "blocks", "required": true, "type": { "name": "BlockInstance<{ [k: string]: any; }>[]" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(changes: BlockInstance<{ [k: string]: any; }>[]) => void" } }, "placeholder": { "defaultValue": { value: "" }, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rich-text-editor/editor-writing-flow.tsx#EditorWritingFlow"] = { docgenInfo: EditorWritingFlow.__docgenInfo, name: "EditorWritingFlow", path: "../../packages/js/components/src/rich-text-editor/editor-writing-flow.tsx#EditorWritingFlow" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/rich-text-editor/rich-text-editor.tsx













var _excluded = ["onError"];

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







// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group


/**
 * Internal dependencies
 */

var RichTextEditor = function RichTextEditor(_ref) {
  var blocks = _ref.blocks,
    label = _ref.label,
    onChange = _ref.onChange,
    _ref$placeholder = _ref.placeholder,
    placeholder = _ref$placeholder === void 0 ? '' : _ref$placeholder;
  var blocksRef = (0,react.useRef)(blocks);
  var _useUser = (0,src/* useUser */.Jd)(),
    currentUserCan = _useUser.currentUserCan;
  var _useState = (0,react.useState)(0),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    setRefresh = _useState2[1];

  // If there is a props change we need to update the ref and force re-render.
  // Note: Because this component is memoized and because we don't re-render
  // when this component initiates a change, a prop change won't force the re-render
  // you'd expect. A change to the blocks must come from outside the editor.
  var forceRerender = function forceRerender() {
    setRefresh(function (refresh) {
      return refresh + 1;
    });
  };
  (0,react.useEffect)(function () {
    blocksRef.current = blocks;
    forceRerender();
  }, [blocks]);
  var debounceChange = (0,lodash.debounce)(function (updatedBlocks) {
    onChange(updatedBlocks);
    blocksRef.current = updatedBlocks;
    forceRerender();
  }, 200);
  var mediaUpload = currentUserCan('upload_files') ? function (_ref2) {
    var _onError = _ref2.onError,
      rest = (0,objectWithoutProperties/* default */.A)(_ref2, _excluded);
    (0,media_utils_build_module/* uploadMedia */.o)(
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore The upload function passes the remaining required props.
    _objectSpread({
      onError: function onError(_ref3) {
        var message = _ref3.message;
        return _onError(message);
      }
    }, rest));
  } : undefined;
  return (0,react.createElement)("div", {
    className: "woocommerce-rich-text-editor"
  }, label && (0,react.createElement)(base_control/* default.VisualLabel */.Ay.VisualLabel, null, label), (0,react.createElement)(slot_fill/* Provider */.Kq, null, (0,react.createElement)(block_editor_build_module/* BlockEditorProvider */.oS, {
    value: blocksRef.current,
    settings: {
      bodyPlaceholder: '',
      hasFixedToolbar: true,
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore This property was recently added in the block editor data store.
      __experimentalClearBlockSelection: false,
      mediaUpload: mediaUpload
    },
    onInput: debounceChange,
    onChange: debounceChange
  }, (0,react.createElement)(keyboard_shortcuts_build_module/* ShortcutProvider */.Ee, null, (0,react.createElement)(EditorWritingFlow, {
    blocks: blocksRef.current,
    onChange: onChange,
    placeholder: placeholder
  })), (0,react.createElement)(popover/* default */.A.Slot, null))));
};
try {
    // @ts-ignore
    RichTextEditor.displayName = "RichTextEditor";
    // @ts-ignore
    RichTextEditor.__docgenInfo = { "description": "", "displayName": "RichTextEditor", "props": { "blocks": { "defaultValue": null, "description": "", "name": "blocks", "required": true, "type": { "name": "BlockInstance<{ [k: string]: any; }>[]" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(changes: BlockInstance<{ [k: string]: any; }>[]) => void" } }, "entryId": { "defaultValue": null, "description": "", "name": "entryId", "required": false, "type": { "name": "string" } }, "placeholder": { "defaultValue": { value: "" }, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rich-text-editor/rich-text-editor.tsx#RichTextEditor"] = { docgenInfo: RichTextEditor.__docgenInfo, name: "RichTextEditor", path: "../../packages/js/components/src/rich-text-editor/rich-text-editor.tsx#RichTextEditor" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/rich-text-editor/stories/rich-text-editor.story.tsx

/**
 * External dependencies
 */


// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group


/**
 * Internal dependencies
 */

var rich_text_editor_story_registry = (0,registry/* createRegistry */.I)();
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
rich_text_editor_story_registry.register(build_module/* store */.M_);
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
rich_text_editor_story_registry.register(block_editor_build_module/* store */.M_);
var Basic = function Basic() {
  return (0,react.createElement)(context/* default */.Ay, {
    value: rich_text_editor_story_registry
  }, (0,react.createElement)(RichTextEditor, {
    blocks: [],
    onChange: function onChange() {
      return null;
    }
  }));
};
var MultipleEditors = function MultipleEditors() {
  return (0,react.createElement)(context/* default */.Ay, {
    value: rich_text_editor_story_registry
  }, (0,react.createElement)(RichTextEditor, {
    blocks: [],
    onChange: function onChange() {
      return null;
    }
  }), (0,react.createElement)("br", null), (0,react.createElement)(RichTextEditor, {
    blocks: [],
    onChange: function onChange() {
      return null;
    }
  }));
};
/* harmony default export */ const rich_text_editor_story = ({
  title: 'WooCommerce Admin/experimental/RichTextEditor',
  component: RichTextEditor
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <RegistryProvider value={registry}>\n            <RichTextEditor blocks={[]} onChange={() => null} />\n        </RegistryProvider>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
MultipleEditors.parameters = {
  ...MultipleEditors.parameters,
  docs: {
    ...MultipleEditors.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <RegistryProvider value={registry}>\n            <RichTextEditor blocks={[]} onChange={() => null} />\n            <br />\n            <RichTextEditor blocks={[]} onChange={() => null} />\n        </RegistryProvider>;\n}",
      ...MultipleEditors.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Basic.displayName = "Basic";
    // @ts-ignore
    Basic.__docgenInfo = { "description": "", "displayName": "Basic", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rich-text-editor/stories/rich-text-editor.story.tsx#Basic"] = { docgenInfo: Basic.__docgenInfo, name: "Basic", path: "../../packages/js/components/src/rich-text-editor/stories/rich-text-editor.story.tsx#Basic" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    MultipleEditors.displayName = "MultipleEditors";
    // @ts-ignore
    MultipleEditors.__docgenInfo = { "description": "", "displayName": "MultipleEditors", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rich-text-editor/stories/rich-text-editor.story.tsx#MultipleEditors"] = { docgenInfo: MultipleEditors.__docgenInfo, name: "MultipleEditors", path: "../../packages/js/components/src/rich-text-editor/stories/rich-text-editor.story.tsx#MultipleEditors" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "?bbf9":
/***/ (() => {

/* (ignored) */

/***/ })

}]);