(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3719],{

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

/***/ "?9f28":
/***/ (() => {

/* (ignored) */

/***/ })

}]);