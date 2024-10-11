(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9028],{

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

/***/ "?bbf9":
/***/ (() => {

/* (ignored) */

/***/ })

}]);