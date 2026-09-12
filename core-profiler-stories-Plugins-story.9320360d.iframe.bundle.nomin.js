"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5239],{

/***/ "../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ interpolate)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/tokenize.js
function identifyToken(item) {
  // {{/example}}
  if (item.startsWith('{{/')) {
    return {
      type: 'componentClose',
      value: item.replace(/\W/g, '')
    };
  } // {{example /}}


  if (item.endsWith('/}}')) {
    return {
      type: 'componentSelfClosing',
      value: item.replace(/\W/g, '')
    };
  } // {{example}}


  if (item.startsWith('{{')) {
    return {
      type: 'componentOpen',
      value: item.replace(/\W/g, '')
    };
  }

  return {
    type: 'string',
    value: item
  };
}

function tokenize(mixedString) {
  const tokenStrings = mixedString.split(/(\{\{\/?\s*\w+\s*\/?\}\})/g); // split to components and strings

  return tokenStrings.map(identifyToken);
}
;// ../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js



function getCloseIndex(openIndex, tokens) {
  const openToken = tokens[openIndex];
  let nestLevel = 0;

  for (let i = openIndex + 1; i < tokens.length; i++) {
    const token = tokens[i];

    if (token.value === openToken.value) {
      if (token.type === 'componentOpen') {
        nestLevel++;
        continue;
      }

      if (token.type === 'componentClose') {
        if (nestLevel === 0) {
          return i;
        }

        nestLevel--;
      }
    }
  } // if we get this far, there was no matching close token


  throw new Error('Missing closing component token `' + openToken.value + '`');
}

function buildChildren(tokens, components) {
  let children = [];
  let openComponent;
  let openIndex;

  for (let i = 0; i < tokens.length; i++) {
    const token = tokens[i];

    if (token.type === 'string') {
      children.push(token.value);
      continue;
    } // component node should at least be set


    if (components[token.value] === undefined) {
      throw new Error(`Invalid interpolation, missing component node: \`${token.value}\``);
    } // should be either ReactElement or null (both type "object"), all other types deprecated


    if (typeof components[token.value] !== 'object') {
      throw new Error(`Invalid interpolation, component node must be a ReactElement or null: \`${token.value}\``);
    } // we should never see a componentClose token in this loop


    if (token.type === 'componentClose') {
      throw new Error(`Missing opening component token: \`${token.value}\``);
    }

    if (token.type === 'componentOpen') {
      openComponent = components[token.value];
      openIndex = i;
      break;
    } // componentSelfClosing token


    children.push(components[token.value]);
    continue;
  }

  if (openComponent) {
    const closeIndex = getCloseIndex(openIndex, tokens);
    const grandChildTokens = tokens.slice(openIndex + 1, closeIndex);
    const grandChildren = buildChildren(grandChildTokens, components);
    const clonedOpenComponent = /*#__PURE__*/(0,react.cloneElement)(openComponent, {}, grandChildren);
    children.push(clonedOpenComponent);

    if (closeIndex < tokens.length - 1) {
      const siblingTokens = tokens.slice(closeIndex + 1);
      const siblings = buildChildren(siblingTokens, components);
      children = children.concat(siblings);
    }
  }

  children = children.filter(Boolean);

  if (children.length === 0) {
    return null;
  }

  if (children.length === 1) {
    return children[0];
  }

  return /*#__PURE__*/(0,react.createElement)(react.Fragment, null, ...children);
}

function interpolate(options) {
  const {
    mixedString,
    components,
    throwErrors
  } = options;

  if (!components) {
    return mixedString;
  }

  if (typeof components !== 'object') {
    if (throwErrors) {
      throw new Error(`Interpolation Error: unable to process \`${mixedString}\` because components is not an object`);
    }

    return mixedString;
  }

  const tokens = tokenize(mixedString);

  try {
    return buildChildren(tokens, components);
  } catch (error) {
    if (throwErrors) {
      throw new Error(`Interpolation Error: unable to process \`${mixedString}\` because of error \`${error.message}\``);
    }

    return mixedString;
  }
}

/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/stories/Plugins.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  InstallationError: () => (/* binding */ InstallationError),
  InstallationErrorBanner: () => (/* binding */ InstallationErrorBanner),
  InstallationNoPermissionError: () => (/* binding */ InstallationNoPermissionError),
  TermsOfService: () => (/* binding */ TermsOfService),
  "default": () => (/* binding */ Plugins_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-media-query/index.js

const matchMediaCache = /* @__PURE__ */ new Map();
function getMediaQueryList(query) {
  if (!query) {
    return null;
  }
  let match = matchMediaCache.get(query);
  if (match) {
    return match;
  }
  if (typeof window !== "undefined" && typeof window.matchMedia === "function") {
    match = window.matchMedia(query);
    matchMediaCache.set(query, match);
    return match;
  }
  return null;
}
function useMediaQuery(query) {
  const source = (0,react.useMemo)(() => {
    const mediaQueryList = getMediaQueryList(query);
    return {
      /** @type {(onStoreChange: () => void) => () => void} */
      subscribe(onStoreChange) {
        if (!mediaQueryList) {
          return () => {
          };
        }
        mediaQueryList.addEventListener?.("change", onStoreChange);
        return () => {
          mediaQueryList.removeEventListener?.(
            "change",
            onStoreChange
          );
        };
      },
      getValue() {
        return mediaQueryList?.matches ?? false;
      }
    };
  }, [query]);
  return (0,react.useSyncExternalStore)(
    source.subscribe,
    source.getValue,
    () => false
  );
}

//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx
var heading = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx + 2 modules
var navigation = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
// EXTERNAL MODULE: ../../packages/js/sanitize/src/index.ts + 3 modules
var src = __webpack_require__("../../packages/js/sanitize/src/index.ts");
;// ../../plugins/woocommerce/client/admin/client/lib/sanitize-html/index.js
/**
 * External dependencies
 */

/* harmony default export */ const sanitize_html = (html => {
  return {
    __html: (0,src/* sanitizeHTML */.p9)(html)
  };
});
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-card/plugin-card.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



const PluginCard = ({
  plugin: {
    is_activated: installed = false,
    image_url: imageUrl,
    key: pluginKey,
    label: title,
    description,
    learn_more_link: learnMoreLinkUrl
  },
  onChange = () => {},
  disabled = false,
  checked = false,
  children
}) => {
  let learnMoreLink = null;
  const slug = pluginKey.replace(':alt', '');
  react.Children.forEach(children, child => {
    if ((0,react.isValidElement)(child) && child.type === PluginCard.LearnMoreLink) {
      learnMoreLink = (0,react.cloneElement)(child, {
        // @ts-expect-error -- @types/react is deficient here
        learnMoreLink: learnMoreLinkUrl
      });
    }
  });
  const descriptionText = (0,react.useMemo)(() => {
    const descriptionElement = document.createElement('div');
    descriptionElement.innerHTML = description;
    return descriptionElement.textContent || '';
  }, [description]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("label", {
    className: (0,clsx/* default */.A)('woocommerce-profiler-plugins-plugin-card', {
      'is-installed': installed,
      disabled
    }),
    "data-slug": slug,
    htmlFor: `${pluginKey}-checkbox`,
    children: [!installed && /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control/* default */.A, {
      __nextHasNoMarginBottom: true,
      id: `${pluginKey}-checkbox`,
      className: "woocommerce-profiler__checkbox",
      disabled: disabled,
      checked: checked,
      onChange: event => {
        if (!disabled) {
          onChange(event);
        }
      }
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-plugins-plugin-card-main",
      children: [imageUrl ? /*#__PURE__*/(0,jsx_runtime.jsx)("img", {
        className: "woocommerce-profiler-plugins-plugin-card-logo",
        src: imageUrl,
        alt: pluginKey
      }) : null, /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-profiler-plugins-plugin-card-content",
        children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: (0,clsx/* default */.A)('woocommerce-profiler-plugins-plugin-card-text-header', {
            installed
          }),
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)("h3", {
            className: "woocommerce-profiler-plugins-plugin-card-title",
            children: title
          }), installed && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            children: (0,build_module.__)('Installed', 'woocommerce')
          })]
        }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "woocommerce-profiler-plugins-plugin-card-text",
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
            dangerouslySetInnerHTML: sanitize_html(description),
            title: descriptionText
          }), learnMoreLink]
        })]
      })]
    })]
  });
};
PluginCard.LearnMoreLink = ({
  learnMoreLink,
  onClick
}) => /*#__PURE__*/(0,jsx_runtime.jsx)(src_link/* Link */.N, {
  onClick: event => {
    if (typeof onClick === 'function') {
      onClick(event);
    }
  },
  href: learnMoreLink ?? '',
  target: "_blank",
  type: "external",
  children: (0,build_module.__)('Learn More', 'woocommerce')
});
try {
    // @ts-ignore
    PluginCard.displayName = "PluginCard";
    // @ts-ignore
    PluginCard.__docgenInfo = { "description": "", "displayName": "PluginCard", "props": { "plugin": { "defaultValue": null, "description": "", "name": "plugin", "required": true, "type": { "name": "Pick<Extension, \"label\" | \"key\" | \"description\" | \"is_activated\" | \"image_url\" | \"learn_more_link\">" } }, "installed": { "defaultValue": null, "description": "", "name": "installed", "required": false, "type": { "name": "boolean" } }, "onChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onChange", "required": false, "type": { "name": "((arg0: unknown) => void)" } }, "disabled": { "defaultValue": { value: "false" }, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "checked": { "defaultValue": { value: "false" }, "description": "", "name": "checked", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-card/plugin-card.tsx#PluginCard"] = { docgenInfo: PluginCard.__docgenInfo, name: "PluginCard", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-card/plugin-card.tsx#PluginCard" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    LearnMoreLink.displayName = "PluginCard.LearnMoreLink";
    // @ts-ignore
    LearnMoreLink.__docgenInfo = { "description": "", "displayName": "PluginCard.LearnMoreLink", "props": { "learnMoreLink": { "defaultValue": null, "description": "", "name": "learnMoreLink", "required": false, "type": { "name": "any" } }, "onClick": { "defaultValue": null, "description": "", "name": "onClick", "required": false, "type": { "name": "MouseEventHandler<HTMLAnchorElement>" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-card/plugin-card.tsx#PluginCard.LearnMoreLink"] = { docgenInfo: PluginCard.LearnMoreLink.__docgenInfo, name: "PluginCard.LearnMoreLink", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-card/plugin-card.tsx#PluginCard.LearnMoreLink" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ./setting.mock.js
var setting_mock = __webpack_require__("./setting.mock.js");
;// ../../plugins/woocommerce/client/admin/client/utils/index.js
/**
 * External dependencies
 */



/**
 * Get the URL params.
 *
 * @param {string} locationSearch - Querystring part of a URL, including the question mark (?).
 * @return {Object} - URL params.
 */
function getUrlParams(locationSearch) {
  if (locationSearch) {
    return locationSearch.substr(1).split('&').reduce((params, query) => {
      const chunks = query.split('=');
      const key = chunks[0];
      let value = decodeURIComponent(chunks[1]);
      value = isNaN(Number(value)) ? value : Number(value);
      return params[key] = value, params;
    }, {});
  }
  return {};
}

/**
 * Get the current screen name.
 *
 * @return {string} - Screen name.
 */
function getScreenName() {
  let screenName = '';
  const {
    page,
    path,
    post_type: postType
  } = getUrlParams(window.location.search);
  if (page) {
    const currentPage = page === 'wc-admin' ? 'home_screen' : page;
    screenName = path ? path.replace(/\//g, '_').substring(1) : currentPage;
  } else if (postType) {
    screenName = postType;
  }
  return screenName;
}

/**
 * Similar to filter, but return two arrays separated by a partitioner function
 *
 * @param {Array}    arr         - Original array of values.
 * @param {Function} partitioner - Function to return truthy/falsy values to separate items in array.
 *
 * @return {Array} - Array of two arrays, first including truthy values, and second including falsy.
 */
const sift = (arr, partitioner) => arr.reduce((all, curr) => {
  all[!!partitioner(curr) ? 0 : 1].push(curr);
  return all;
}, [[], []]);
const timeFrames = [{
  name: '0-2s',
  max: 2
}, {
  name: '2-5s',
  max: 5
}, {
  name: '5-10s',
  max: 10
}, {
  name: '10-15s',
  max: 15
}, {
  name: '15-20s',
  max: 20
}, {
  name: '20-30s',
  max: 30
}, {
  name: '30-60s',
  max: 60
}, {
  name: '>60s'
}];

/**
 * Returns time frame for a given time in milliseconds.
 *
 * @param {number} timeInMs - time in milliseconds
 *
 * @return {string} - Time frame.
 */
const getTimeFrame = timeInMs => {
  for (const timeFrame of timeFrames) {
    if (!timeFrame.max) {
      return timeFrame.name;
    }
    if (timeInMs < timeFrame.max * 1000) {
      return timeFrame.name;
    }
  }
};

/**
 * Goes into fullscreen mode when the component is loaded
 *
 * @param {string[]} classes - classes to add to document.body
 */
const useFullScreen = classes => {
  useEffect(() => {
    const hasToolbarClass = document.documentElement.classList.contains('wp-toolbar');
    document.body.classList.remove('woocommerce-admin-is-loading');
    document.body.classList.add(classes);
    document.body.classList.add('woocommerce-admin-full-screen');
    document.body.classList.add('is-wp-toolbar-disabled');
    if (hasToolbarClass) {
      document.documentElement.classList.remove('wp-toolbar');
    }
    return () => {
      document.body.classList.remove(classes);
      document.body.classList.remove('woocommerce-admin-full-screen');
      document.body.classList.remove('is-wp-toolbar-disabled');
      if (hasToolbarClass) {
        document.documentElement.classList.add('wp-toolbar');
      }
    };
  });
};

/**
 * Creates a proxy object that warns when accessing deprecated properties.
 *
 * Example object:
 * {
 *   prop1: "test",
 *   prop2: {
 *     prop3: "test"
 *   }
 * }
 *
 * Example messages object:
 * {
 *   prop1: {
 *     prop2: 'Deprecation message'
 *   }
 * }
 *
 * Accessing `obj.prop1.prop2` will trigger a warning in the console.
 *
 * @param {Object} obj           - The object to wrap with a proxy.
 * @param {Object} messages      - Deprecation messages for specific properties.
 * @param {string} [basePath=''] - Internal tracking for property paths.
 * @return {Proxy} A proxied object with deprecation warnings.
 */
function createDeprecatedPropertiesProxy(obj, messages, basePath = '') {
  // If not a plain object or array, return as is
  if (typeof obj !== 'object' || obj === null) {
    return obj;
  }
  return new Proxy(obj, {
    get(target, prop, receiver) {
      const value = Reflect.get(target, prop, receiver);

      // Handle array methods and properties
      if (Array.isArray(target) && (prop === 'length' || prop === Symbol.iterator)) {
        return value;
      }
      let nextPath = basePath;

      // Only handle deprecation warnings for string, number, and boolean property names
      if (typeof prop === 'string' || typeof prop === 'number' || typeof prop === 'boolean') {
        nextPath = basePath ? `${basePath}.${String(prop)}` : String(prop);

        // Retrieve the deprecation message (if exists)
        const deprecationMessage = nextPath.split('.').reduce((acc, key) => {
          return acc && typeof acc === 'object' ? acc[key] : undefined;
        }, messages);
        if (typeof deprecationMessage === 'string') {
          console.warn(deprecationMessage); // eslint-disable-line no-console
        }
      }

      // Recursively wrap objects to maintain deprecation checks
      return value && typeof value === 'object' ? createDeprecatedPropertiesProxy(value, messages, nextPath) : value;
    }
  });
}
;// ../../plugins/woocommerce/client/admin/client/utils/admin-settings.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


// Remove mutable data from settings object to prevent access. Data stores should be used instead.
const mutableSources = ['wcAdminSettings', 'preloadSettings'];
const adminSettings = (0,setting_mock/* getSetting */.P)('admin', {});
const ADMIN_SETTINGS_SOURCE = Object.keys(adminSettings).reduce((source, key) => {
  if (!mutableSources.includes(key)) {
    source[key] = adminSettings[key];
  }
  return source;
}, {});
const deprecatedAdminProperties = {
  onboarding: {
    profile: 'Deprecated: wcSettings.admin.onboarding.profile is deprecated. It is planned to be released in WooCommerce 10.0.0. Please use `getProfileItems` from the onboarding store. See https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/data/src/onboarding for more information.',
    euCountries: 'Deprecated: wcSettings.admin.onboarding.euCountries is deprecated. Please use `/wc/v3/data/continents/eu` from the REST API. See https://developer.woocommerce.com/docs/apis/rest-api/v3/data/#list-all-continents for more information.',
    localInfo: 'Deprecated: wcSettings.admin.onboarding.localInfo is deprecated. Please use `include WC()->plugin_path() . "/i18n/locale-info.php"` instead.',
    currencySymbols: '"Deprecated: wcSettings.admin.onboarding.currencySymbols is deprecated. Please use get_woocommerce_currency_symbols() function instead.'
  }
};

/**
 * Retrieves a setting value from the setting state.
 *
 * @param {string}   name                    The identifier for the setting.
 * @param {*}        [fallback=false]        The value to use as a fallback
 *                                           if the setting is not in the
 *                                           state.
 * @param {Function} [filter=( val ) => val] A callback for filtering the
 *                                           value before it's returned.
 *                                           Receives both the found value
 *                                           (if it exists for the key) and
 *                                           the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */
function getAdminSetting(name, fallback = false, filter = val => val, deprecatedProperties = deprecatedAdminProperties) {
  if (mutableSources.includes(name)) {
    throw new Error((0,build_module.__)('Mutable settings should be accessed via data store.', 'woocommerce'));
  }
  const value = ADMIN_SETTINGS_SOURCE.hasOwnProperty(name) ? ADMIN_SETTINGS_SOURCE[name] : fallback;
  const filtered = filter(value, fallback);

  // Return proxied object if the requested object has deprecated properties.
  return deprecatedProperties?.[name] && "production" === 'development' ? 0 : filtered;
}
const ADMIN_URL = (0,setting_mock/* getSetting */.P)('adminUrl');
const COUNTRIES = (0,setting_mock/* getSetting */.P)('countries');
const CURRENCY = (0,setting_mock/* getSetting */.P)('currency');
const LOCALE = (0,setting_mock/* getSetting */.P)('locale');
const SITE_TITLE = (0,setting_mock/* getSetting */.P)('siteTitle');
const WC_ASSET_URL = (0,setting_mock/* getSetting */.P)('wcAssetUrl');
const ORDER_STATUSES = getAdminSetting('orderStatuses');

/**
 * Sets a value to a property on the settings state.
 *
 * NOTE: This feature is to be removed in favour of data stores when a full migration
 * is complete.
 *
 * @deprecated
 *
 * @param {string}   name                    The setting property key for the
 *                                           setting being mutated.
 * @param {*}        value                   The value to set.
 * @param {Function} [filter=( val ) => val] Allows for providing a callback
 *                                           to sanitize the setting (eg.
 *                                           ensure it's a number)
 */
function setAdminSetting(name, value, filter = val => val) {
  if (mutableSources.includes(name)) {
    throw new Error(__('Mutable settings should be mutated via data store.', 'woocommerce'));
  }
  ADMIN_SETTINGS_SOURCE[name] = filter(value);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__("../../node_modules/.pnpm/@automattic+interpolate-com_7b304205dcf17f8e715b5fe54c220b84/node_modules/@automattic/interpolate-components/dist/esm/index.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-error-banner/PluginErrorBanner.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



const PluginErrorBanner = ({
  pluginsInstallationPermissionsFailure,
  pluginsInstallationErrors,
  pluginsSlugToName = {},
  onClick
}) => {
  let installationErrorMessage;
  switch (true) {
    case pluginsInstallationPermissionsFailure:
    case pluginsInstallationErrors?.some(
    // it really shouldn't get here since permissions are pre-checked. but we'll check for 403 just to be safe.
    e => e.errorDetails?.data?.data?.status === 403 // 403 is the code representing rest_authorization_required_code()
    ):
      installationErrorMessage = (0,build_module.__)('You do not have permissions to manage plugins. Please contact your site administrator.', 'woocommerce');
      break;
    default:
      installationErrorMessage =
      // Translators: %s is a list of plugins that does not need to be translated
      (0,build_module.__)('Oops! We encountered a problem while installing %s. {{link}}Please try again{{/link}}.', 'woocommerce');
      break;
  }
  const failedPluginNames = [...new Set((pluginsInstallationErrors || []).map(
  // Use the plugin name if available, otherwise use the plugin slug
  error => pluginsSlugToName[error.plugin] || error.plugin))];
  return /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
    className: "plugin-error",
    children: (0,esm/* default */.A)({
      mixedString: (0,build_module/* sprintf */.nv)(installationErrorMessage, joinWithAnd(failedPluginNames).map(composeListFormatParts).join('')),
      components: {
        span: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {}),
        link: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          variant: "link",
          onClick: onClick
        })
      }
    })
  });
};
try {
    // @ts-ignore
    PluginErrorBanner.displayName = "PluginErrorBanner";
    // @ts-ignore
    PluginErrorBanner.__docgenInfo = { "description": "", "displayName": "PluginErrorBanner", "props": { "pluginsInstallationPermissionsFailure": { "defaultValue": null, "description": "", "name": "pluginsInstallationPermissionsFailure", "required": false, "type": { "name": "boolean" } }, "pluginsInstallationErrors": { "defaultValue": null, "description": "", "name": "pluginsInstallationErrors", "required": false, "type": { "name": "PluginInstallError[]" } }, "pluginsSlugToName": { "defaultValue": { value: "{}" }, "description": "", "name": "pluginsSlugToName", "required": false, "type": { "name": "Record<string, string>" } }, "onClick": { "defaultValue": null, "description": "", "name": "onClick", "required": false, "type": { "name": "(() => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-error-banner/PluginErrorBanner.tsx#PluginErrorBanner"] = { docgenInfo: PluginErrorBanner.__docgenInfo, name: "PluginErrorBanner", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-error-banner/PluginErrorBanner.tsx#PluginErrorBanner" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-terms-of-service/PluginsTermsOfService.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const PluginsTermsOfService = ({
  selectedPlugins
}) => {
  const pluginsWithTOS = selectedPlugins.filter(plugin => ['jetpack', 'woocommerce-services:tax', 'woocommerce-shipping', 'woocommerce-tax', 'woocommerce-payments'].includes(plugin.key));
  if (!pluginsWithTOS.length) {
    return null;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
    className: "woocommerce-profiler-plugins-jetpack-agreement",
    children: (0,esm/* default */.A)({
      mixedString: (0,build_module/* sprintf */.nv)(/* translators: %s: a list of plugins, e.g. Jetpack */
      (0,build_module._n)('By installing %s plugin for free you agree to our {{link}}Terms of Service{{/link}}.', 'By installing %s plugins for free you agree to our {{link}}Terms of Service{{/link}}.', pluginsWithTOS.length, 'woocommerce'), joinWithAnd(pluginsWithTOS.map(plugin => plugin.name)).map(composeListFormatParts).join('')),
      components: {
        span: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {}),
        link: /*#__PURE__*/(0,jsx_runtime.jsx)(src_link/* Link */.N, {
          href: "https://wordpress.com/tos/",
          target: "_blank",
          type: "external"
        })
      }
    })
  });
};
try {
    // @ts-ignore
    PluginsTermsOfService.displayName = "PluginsTermsOfService";
    // @ts-ignore
    PluginsTermsOfService.__docgenInfo = { "description": "", "displayName": "PluginsTermsOfService", "props": { "selectedPlugins": { "defaultValue": null, "description": "", "name": "selectedPlugins", "required": true, "type": { "name": "ExtensionList" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-terms-of-service/PluginsTermsOfService.tsx#PluginsTermsOfService"] = { docgenInfo: PluginsTermsOfService.__docgenInfo, name: "PluginsTermsOfService", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/components/plugin-terms-of-service/PluginsTermsOfService.tsx#PluginsTermsOfService" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/Plugins.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */








const currentLocale = (getAdminSetting('locale')?.siteLocale || 'en_US').replaceAll('_', '-');
const joinWithAnd = (items, locale = currentLocale) => {
  try {
    return new Intl.ListFormat(locale, {
      style: 'long',
      type: 'conjunction'
    }).formatToParts(items);
  } catch (error) {
    // Fallback to English
    return new Intl.ListFormat('en-US', {
      style: 'long',
      type: 'conjunction'
    }).formatToParts(items);
  }
};
const composeListFormatParts = part => {
  if (part.type === 'element') {
    return '{{span}}' + part.value + '{{/span}}';
  }
  return part.value;
};
const computePluginsSelection = (availablePlugins, selectedPlugins) => {
  const selectedPluginSlugs = Array.from(selectedPlugins).map(plugin => plugin.key.replace(':alt', ''));
  const pluginsShown = [];
  const pluginsUnselected = [];
  availablePlugins.forEach(plugin => {
    const pluginSlug = plugin.key.replace(':alt', '');
    pluginsShown.push(pluginSlug);
    if (!plugin.is_activated && !selectedPluginSlugs.includes(pluginSlug)) {
      pluginsUnselected.push(pluginSlug);
    }
  });
  return {
    pluginsShown,
    pluginsUnselected,
    selectedPluginSlugs
  };
};
const Plugins_Plugins = ({
  context,
  navigationProgress,
  sendEvent
}) => {
  const [selectedPlugins, setSelectedPlugins] = (0,react.useState)(new Set(context.pluginsAvailable.filter(context.pluginsInstallationErrors.length ? plugin => context.pluginsSelected.includes(plugin.key) // if there was previously an error, retrieve previous selection
  : plugin => !plugin.is_activated // initialise selection with all plugins that haven't been installed
  )));
  const setSelectedPlugin = plugin => {
    if (selectedPlugins.has(plugin)) {
      selectedPlugins.delete(plugin);
    } else {
      selectedPlugins.add(plugin);
    }
    setSelectedPlugins(new Set(selectedPlugins));
  };
  const skipPluginsPage = () => {
    return sendEvent({
      type: 'PLUGINS_PAGE_SKIPPED'
    });
  };
  const completedPluginsPageWithoutSelectingPlugins = () => {
    return sendEvent({
      type: 'PLUGINS_PAGE_COMPLETED_WITHOUT_SELECTING_PLUGINS'
    });
  };
  const submitInstallationRequest = () => {
    const {
      pluginsShown,
      pluginsUnselected,
      selectedPluginSlugs
    } = computePluginsSelection(context.pluginsAvailable, selectedPlugins);
    return sendEvent({
      type: 'PLUGINS_INSTALLATION_REQUESTED',
      payload: {
        pluginsShown,
        pluginsSelected: selectedPluginSlugs,
        pluginsUnselected
      }
    });
  };
  const pluginsCardRowCount = Math.ceil(context.pluginsAvailable.length / 2);
  const pluginsSlugToName = (0,react.useMemo)(() => context.pluginsAvailable.reduce((acc, plugin) => {
    acc[plugin.key] = plugin.name;
    return acc;
  }, {}), [context.pluginsAvailable]);
  const baseHeight = 350;
  const rowHeight = 100; // include the gap between the cards
  const listHeight = baseHeight + rowHeight * pluginsCardRowCount;
  const shouldShowStickyFooter = useMediaQuery(`(max-height: ${listHeight}px)`);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-profiler-plugins",
    "data-testid": "core-profiler-plugins",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(navigation/* Navigation */.V, {
      percentage: navigationProgress,
      onSkip: skipPluginsPage
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-page__content woocommerce-profiler-plugins__content",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(heading/* Heading */.D, {
        className: "woocommerce-profiler__stepper-heading",
        title: (0,build_module.__)('Get a boost with our free features', 'woocommerce'),
        subTitle: (0,build_module.__)('No commitment required – you can remove them at any time.', 'woocommerce')
      }), context.pluginsInstallationErrors.length > 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(PluginErrorBanner, {
        pluginsInstallationErrors: context.pluginsInstallationErrors,
        pluginsSlugToName: pluginsSlugToName,
        onClick: submitInstallationRequest
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-profiler-plugins__list', {
          'sticky-footer': shouldShowStickyFooter
        }),
        children: context.pluginsAvailable.map(plugin => {
          const {
            key: pluginSlug
          } = plugin;
          return /*#__PURE__*/(0,jsx_runtime.jsx)(PluginCard, {
            plugin: plugin,
            onChange: () => {
              if (!plugin.is_activated) {
                setSelectedPlugin(plugin);
              }
            },
            checked: selectedPlugins.has(plugin)
          }, pluginSlug);
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: (0,clsx/* default */.A)('woocommerce-profiler-plugins__footer', {
          'sticky-footer': shouldShowStickyFooter
        }),
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-profiler-plugins-continue-button-container",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            className: "woocommerce-profiler-plugins-continue-button",
            variant: "primary",
            onClick: selectedPlugins.size > 0 ? submitInstallationRequest : completedPluginsPageWithoutSelectingPlugins,
            children: (0,build_module.__)('Continue', 'woocommerce')
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(PluginsTermsOfService, {
          selectedPlugins: Array.from(selectedPlugins)
        })]
      })]
    })]
  });
};
try {
    // @ts-ignore
    composeListFormatParts.displayName = "composeListFormatParts";
    // @ts-ignore
    composeListFormatParts.__docgenInfo = { "description": "", "displayName": "composeListFormatParts", "props": { "type": { "defaultValue": null, "description": "", "name": "type", "required": true, "type": { "name": "string" } }, "value": { "defaultValue": null, "description": "", "name": "value", "required": true, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/Plugins.tsx#composeListFormatParts"] = { docgenInfo: composeListFormatParts.__docgenInfo, name: "composeListFormatParts", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/Plugins.tsx#composeListFormatParts" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Plugins_Plugins.displayName = "Plugins";
    // @ts-ignore
    Plugins_Plugins.__docgenInfo = { "description": "", "displayName": "Plugins", "props": { "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "Pick<CoreProfilerStateMachineContext, \"pluginsAvailable\" | \"pluginsInstallationErrors\" | \"pluginsSelected\">" } }, "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(payload: PluginsInstallationRequestedEvent | PluginsPageSkippedEvent | PluginsPageCompletedWithoutSelectingPluginsEvent | PluginsLearnMoreLinkClickedEvent) => void" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/Plugins.tsx#Plugins"] = { docgenInfo: Plugins_Plugins.__docgenInfo, name: "Plugins", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/Plugins.tsx#Plugins" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/NoPermissions.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */






/** Page to be shown when the user does not have permissions to install plugins */

const NoPermissionsError = ({
  context,
  navigationProgress,
  sendEvent
}) => {
  const skipPluginsPage = () => {
    return sendEvent({
      type: 'PLUGINS_PAGE_SKIPPED'
    });
  };
  const pluginsCardRowCount = Math.ceil(context.pluginsAvailable.length / 2);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-profiler-plugins",
    "data-testid": "core-profiler-plugins",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(navigation/* Navigation */.V, {
      percentage: navigationProgress,
      onSkip: skipPluginsPage
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-page__content woocommerce-profiler-plugins__content",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(heading/* Heading */.D, {
        className: "woocommerce-profiler__stepper-heading",
        title: (0,build_module.__)('Get a boost with our free features', 'woocommerce'),
        subTitle: (0,build_module.__)('No commitment required – you can remove them at any time.', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(PluginErrorBanner, {
        pluginsInstallationPermissionsFailure: true
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-profiler-plugins__list', `rows-${pluginsCardRowCount}`),
        children: context.pluginsAvailable.map(plugin => {
          const {
            key: pluginSlug,
            learn_more_link: learnMoreLink
          } = plugin;
          return /*#__PURE__*/(0,jsx_runtime.jsx)(PluginCard, {
            plugin: plugin,
            checked: false,
            disabled: true,
            children: learnMoreLink && /*#__PURE__*/(0,jsx_runtime.jsx)(PluginCard.LearnMoreLink, {
              onClick: () => {
                sendEvent({
                  type: 'PLUGINS_LEARN_MORE_LINK_CLICKED',
                  payload: {
                    plugin: pluginSlug,
                    learnMoreLink
                  }
                });
              }
            })
          }, pluginSlug);
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: (0,clsx/* default */.A)('woocommerce-profiler-plugins__footer', `rows-${pluginsCardRowCount}`),
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-profiler-plugins-continue-button-container",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            className: "woocommerce-profiler-plugins-continue-button",
            variant: "primary",
            onClick: skipPluginsPage,
            children: (0,build_module.__)('Continue', 'woocommerce')
          })
        })
      })]
    })]
  });
};
try {
    // @ts-ignore
    NoPermissionsError.displayName = "NoPermissionsError";
    // @ts-ignore
    NoPermissionsError.__docgenInfo = { "description": "Page to be shown when the user does not have permissions to install plugins", "displayName": "NoPermissionsError", "props": { "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "Pick<CoreProfilerStateMachineContext, \"pluginsAvailable\" | \"currentUser\">" } }, "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(payload: PluginsInstallationRequestedEvent | PluginsPageSkippedEvent | PluginsLearnMoreLinkClickedEvent) => void" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/NoPermissions.tsx#NoPermissionsError"] = { docgenInfo: NoPermissionsError.__docgenInfo, name: "NoPermissionsError", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/Plugins/NoPermissions.tsx#NoPermissionsError" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx
var WithSetupWizardLayout = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/stories/Plugins.story.tsx
/**
 * Internal dependencies
 */







const plugins = [{
  name: 'Jetpack',
  description: 'Get auto real-time backups, malware scans, and spam protection.',
  is_visible: true,
  is_built_by_wc: false,
  min_wp_version: '6.0',
  key: 'jetpack',
  label: 'Enhance security with Jetpack',
  image_url: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/obw-free-extensions/images/core-profiler/logo-jetpack.svg',
  learn_more_link: 'https://woocommerce.com/products/jetpack',
  install_priority: 8,
  is_installed: true,
  is_activated: true,
  manage_url: ''
}, {
  name: 'Pinterest for WooCommerce',
  description: 'Get your products in front of a highly engaged audience.',
  image_url: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/obw-free-extensions/images/core-profiler/logo-pinterest.svg',
  manage_url: 'admin.php?page=wc-admin&path=%2Fpinterest%2Flanding',
  is_built_by_wc: true,
  min_php_version: '7.3',
  key: 'pinterest-for-woocommerce',
  label: 'Showcase your products with Pinterest',
  learn_more_link: 'https://woocommerce.com/products/pinterest-for-woocommerce',
  install_priority: 2,
  is_visible: true,
  is_installed: false,
  is_activated: false
}];
const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(Plugins_Plugins, {
  sendEvent: () => {},
  navigationProgress: 80,
  context: {
    pluginsAvailable: plugins,
    pluginsSelected: [],
    pluginsInstallationErrors: []
  }
});
const InstallationError = () => /*#__PURE__*/(0,jsx_runtime.jsx)(Plugins_Plugins, {
  sendEvent: () => {},
  navigationProgress: 80,
  context: {
    pluginsAvailable: plugins,
    pluginsSelected: [],
    pluginsInstallationErrors: [{
      plugin: 'Jetpack',
      errorDetails: {
        data: {
          code: 'plugin_install_failed',
          data: {
            status: 403
          }
        }
      },
      error: 'Installation failed'
    }]
  }
});
const TermsOfService = () => /*#__PURE__*/(0,jsx_runtime.jsx)(PluginsTermsOfService, {
  selectedPlugins: plugins
});
const InstallationErrorBanner = () => /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
  className: "woocommerce-profiler-plugins",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(PluginErrorBanner, {
    pluginsInstallationPermissionsFailure: false,
    pluginsInstallationErrors: [{
      plugin: 'Jetpack',
      errorDetails: {
        data: {
          code: 'plugin_install_failed',
          data: {
            status: 403
          }
        }
      },
      error: 'Installation failed'
    }],
    onClick: () => {}
  })
});
const InstallationNoPermissionError = () => /*#__PURE__*/(0,jsx_runtime.jsx)(NoPermissionsError, {
  sendEvent: () => {},
  navigationProgress: 80,
  context: {
    pluginsAvailable: plugins
  }
});
/* harmony default export */ const Plugins_story = ({
  title: 'WooCommerce Admin/Core Profiler/Plugins',
  component: Plugins_Plugins,
  decorators: [WithSetupWizardLayout/* WithSetupWizardLayout */.b]
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <Plugins sendEvent={() => {}} navigationProgress={80} context={{\n  pluginsAvailable: plugins,\n  pluginsSelected: [],\n  pluginsInstallationErrors: []\n}} />",
      ...Basic.parameters?.docs?.source
    }
  }
};
InstallationError.parameters = {
  ...InstallationError.parameters,
  docs: {
    ...InstallationError.parameters?.docs,
    source: {
      originalSource: "() => <Plugins sendEvent={() => {}} navigationProgress={80} context={{\n  pluginsAvailable: plugins,\n  pluginsSelected: [],\n  pluginsInstallationErrors: [{\n    plugin: 'Jetpack',\n    errorDetails: {\n      data: {\n        code: 'plugin_install_failed',\n        data: {\n          status: 403\n        }\n      }\n    },\n    error: 'Installation failed'\n  }]\n}} />",
      ...InstallationError.parameters?.docs?.source
    }
  }
};
TermsOfService.parameters = {
  ...TermsOfService.parameters,
  docs: {
    ...TermsOfService.parameters?.docs,
    source: {
      originalSource: "() => <PluginsTermsOfService selectedPlugins={plugins} />",
      ...TermsOfService.parameters?.docs?.source
    }
  }
};
InstallationErrorBanner.parameters = {
  ...InstallationErrorBanner.parameters,
  docs: {
    ...InstallationErrorBanner.parameters?.docs,
    source: {
      originalSource: "() => <div className=\"woocommerce-profiler-plugins\">\n        <PluginErrorBanner pluginsInstallationPermissionsFailure={false} pluginsInstallationErrors={[{\n    plugin: 'Jetpack',\n    errorDetails: {\n      data: {\n        code: 'plugin_install_failed',\n        data: {\n          status: 403\n        }\n      }\n    },\n    error: 'Installation failed'\n  }]} onClick={() => {}} />\n    </div>",
      ...InstallationErrorBanner.parameters?.docs?.source
    }
  }
};
InstallationNoPermissionError.parameters = {
  ...InstallationNoPermissionError.parameters,
  docs: {
    ...InstallationNoPermissionError.parameters?.docs,
    source: {
      originalSource: "() => <NoPermissionsError sendEvent={() => {}} navigationProgress={80} context={{\n  pluginsAvailable: plugins\n}} />",
      ...InstallationNoPermissionError.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Plugins.displayName = "Plugins";
    // @ts-ignore
    Plugins.__docgenInfo = { "description": "", "displayName": "Plugins", "props": { "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "Pick<CoreProfilerStateMachineContext, \"pluginsAvailable\" | \"pluginsInstallationErrors\" | \"pluginsSelected\">" } }, "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(payload: PluginsInstallationRequestedEvent | PluginsPageSkippedEvent | PluginsPageCompletedWithoutSelectingPluginsEvent | PluginsLearnMoreLinkClickedEvent) => void" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/stories/Plugins.story.tsx#Plugins"] = { docgenInfo: Plugins.__docgenInfo, name: "Plugins", path: "../../plugins/woocommerce/client/admin/client/core-profiler/stories/Plugins.story.tsx#Plugins" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ checkbox_control_default)
});

// UNUSED EXPORTS: CheckboxControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs
var use_ref_effect = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs
var library_reset = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.mjs
// packages/icons/src/library/check.tsx


var check_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M16.5 7.5 10 13.9l-2.5-2.4-1 1 3.5 3.6 7.5-7.6z" }) });

//# sourceMappingURL=check.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js








function CheckboxControl(props) {
  const {
    __nextHasNoMarginBottom,
    label,
    className,
    heading,
    checked,
    indeterminate,
    help,
    id: idProp,
    onChange,
    onClick,
    ...additionalProps
  } = props;
  if (heading) {
    (0,build_module/* default */.A)("`heading` prop in `CheckboxControl`", {
      alternative: "a separate element to implement a heading",
      since: "5.8"
    });
  }
  const [showCheckedIcon, setShowCheckedIcon] = (0,react.useState)(false);
  const [showIndeterminateIcon, setShowIndeterminateIcon] = (0,react.useState)(false);
  const ref = (0,use_ref_effect/* default */.A)((node) => {
    if (!node) {
      return;
    }
    node.indeterminate = !!indeterminate;
    setShowCheckedIcon(node.matches(":checked"));
    setShowIndeterminateIcon(node.matches(":indeterminate"));
  }, [checked, indeterminate]);
  const id = (0,use_instance_id/* default */.A)(CheckboxControl, "inspector-checkbox-control", idProp);
  const onChangeValue = (event) => onChange(event.target.checked);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "CheckboxControl",
    label: heading,
    id,
    help: help && /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
      className: "components-checkbox-control__help",
      children: help
    }),
    className: (0,clsx/* default */.A)("components-checkbox-control", className),
    children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(component/* default */.A, {
      spacing: 0,
      justify: "start",
      alignment: "top",
      children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)("span", {
        className: "components-checkbox-control__input-container",
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)("input", {
          ref,
          id,
          className: "components-checkbox-control__input",
          type: "checkbox",
          value: "1",
          onChange: onChangeValue,
          checked,
          "aria-describedby": !!help ? id + "__help" : void 0,
          onClick: (event) => {
            event.currentTarget.focus();
            onClick?.(event);
          },
          ...additionalProps
        }), showIndeterminateIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: library_reset/* default */.A,
          className: "components-checkbox-control__indeterminate",
          role: "presentation"
        }) : null, showCheckedIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: check_default,
          className: "components-checkbox-control__checked",
          role: "presentation"
        }) : null]
      }), label && /* @__PURE__ */ (0,jsx_runtime.jsx)("label", {
        className: "components-checkbox-control__label",
        htmlFor: id,
        children: label
      })]
    })
  });
}
var checkbox_control_default = CheckboxControl;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GB: () => (/* binding */ ensureNumber),
/* harmony export */   J5: () => (/* binding */ isValueDefined),
/* harmony export */   r6: () => (/* binding */ isValueEmpty)
/* harmony export */ });
/* unused harmony exports getDefinedValue, stringToNumber */
function isValueDefined(value) {
  return value !== void 0 && value !== null;
}
function isValueEmpty(value) {
  const isEmptyString = value === "";
  return !isValueDefined(value) || isEmptyString;
}
function getDefinedValue(values = [], fallbackValue) {
  var _values$find;
  return (_values$find = values.find(isValueDefined)) !== null && _values$find !== void 0 ? _values$find : fallbackValue;
}
const stringToNumber = (value) => {
  return parseFloat(value);
};
const ensureNumber = (value) => {
  return typeof value === "string" ? stringToNumber(value) : value;
};

//# sourceMappingURL=values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useRefEffect)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-ref-effect/index.ts

function useRefEffect(callback, dependencies) {
  const cleanupRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(void 0);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)((node) => {
    if (node) {
      cleanupRef.current = callback(node);
    } else if (cleanupRef.current) {
      cleanupRef.current();
    }
  }, dependencies);
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/icons/src/icon/index.ts

var icon_default = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(
  ({ icon, size = 24, ...props }, ref) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
      width: size,
      height: size,
      ...props,
      ref
    });
  }
);

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ reset_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/icons/src/library/reset.tsx


var reset_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M7 11.5h10V13H7z" }) });

//# sourceMappingURL=reset.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t4: () => (/* binding */ SVG),
/* harmony export */   wA: () => (/* binding */ Path)
/* harmony export */ });
/* unused harmony exports Circle, Defs, G, Line, LinearGradient, Polygon, RadialGradient, Rect, Stop */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/primitives/src/svg/index.js



var Circle = (props) => createElement("circle", props);
var G = (props) => createElement("g", props);
var Line = (props) => createElement("line", props);
var Path = (props) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("path", props);
var Polygon = (props) => createElement("polygon", props);
var Rect = (props) => createElement("rect", props);
var Defs = (props) => createElement("defs", props);
var RadialGradient = (props) => createElement("radialGradient", props);
var LinearGradient = (props) => createElement("linearGradient", props);
var Stop = (props) => createElement("stop", props);
var SVG = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.forwardRef)(
  /**
   * @param {SVGProps}                          props isPressed indicates whether the SVG should appear as pressed.
   *                                                  Other props will be passed through to svg component.
   * @param {React.ForwardedRef<SVGSVGElement>} ref   The forwarded ref to the SVG element.
   *
   * @return {React.JSX.Element} Stop component
   */
  ({ className, isPressed, ...props }, ref) => {
    const appliedProps = {
      ...props,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, { "is-pressed": isPressed }) || void 0,
      "aria-hidden": true,
      focusable: false
    };
    return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("svg", { ...appliedProps, ref });
  }
);
SVG.displayName = "SVG";

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "./setting.mock.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
const Link = ({
  href,
  children,
  type = 'wc-admin',
  ...props
}) => {
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  const wcAdminLinkHandler = (onClick, event) => {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
      return;
    }
    event?.preventDefault();

    // If there is an onclick event, execute it.
    const onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if (event?.target instanceof Element) {
      const closestEventTarget = event.target.closest('a')?.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  const passProps = {
    ...props,
    'data-link-type': type
  };
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
    href: href,
    ...passProps,
    children: children
  });
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

/***/ "../../packages/js/sanitize/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  p9: () => (/* reexport */ sanitize_sanitizeHTML)
});

// UNUSED EXPORTS: DEFAULT_ALLOWED_ATTR, DEFAULT_ALLOWED_TAGS, getTrustedTypesPolicy

// EXTERNAL MODULE: ../../node_modules/.pnpm/dompurify@3.4.14/node_modules/dompurify/dist/purify.es.mjs
var purify_es = __webpack_require__("../../node_modules/.pnpm/dompurify@3.4.14/node_modules/dompurify/dist/purify.es.mjs");
;// ../../packages/js/sanitize/src/noop-trusted-types-policy.ts
/**
 * External dependencies
 */

/**
 * The type for our no-op trusted types policy.
 */

/**
 * Cached no-op policy instance to avoid duplicate creation.
 */
let noopPolicyInstance;
function getNoopTrustedTypesPolicy() {
  if (noopPolicyInstance !== undefined) {
    return noopPolicyInstance;
  }
  if (typeof window === 'undefined' || !window.trustedTypes) {
    noopPolicyInstance = null;
    return null;
  }
  try {
    noopPolicyInstance = window.trustedTypes.createPolicy('woocommerce-sanitize-noop', {
      createHTML: input => input
    });
  } catch (error) {
    noopPolicyInstance = null;
    // eslint-disable-next-line no-console
    console.warn('Failed to create "woocommerce-sanitize-noop" trusted type policy:', error);
  }
  return noopPolicyInstance;
}
;// ../../packages/js/sanitize/src/sanitize.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Default allowed HTML tags for basic sanitization.
 */
const DEFAULT_ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p', 'br', 'abbr'];

/**
 * Default allowed HTML attributes for basic sanitization.
 */
const DEFAULT_ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download', 'title'];

/**
 * The set of supported return type kinds for sanitized content.
 * These are the configuration values you can pass via `returnType`.
 */

/**
 * Mapping between `SanitizeReturnKind` and the actual returned value types.
 */

/**
 * Union of the concrete value types this sanitizer can return.
 * Useful when you want to accept any possible sanitizer output.
 */

/**
 * Configuration options for HTML sanitization.
 */

/**
 * Sanitizes HTML content using DOMPurify with default allowed tags and attributes.
 *
 * @param html   - The HTML content to sanitize.
 * @param config - Optional configuration for allowed tags and attributes.
 *
 * @return Sanitized HTML content.
 */
function sanitize_sanitizeHTML(html, config) {
  const allowedTags = config?.tags || DEFAULT_ALLOWED_TAGS;
  const allowedAttr = config?.attr || DEFAULT_ALLOWED_ATTR;
  const purifyConfig = {
    ALLOWED_TAGS: [...allowedTags],
    ALLOWED_ATTR: [...allowedAttr]
  };

  // Provide a no-op TT policy (when supported) to prevent DOMPurify from
  // creating its internal policy and emitting warnings with multiple instances.
  const ttNoopPolicy = getNoopTrustedTypesPolicy();
  if (ttNoopPolicy) {
    purifyConfig.TRUSTED_TYPES_POLICY = ttNoopPolicy;
  }

  // Only pass a single RETURN_* flag if a non-string return type is requested
  if (config?.returnType === 'HTMLBodyElement') {
    purifyConfig.RETURN_DOM = true;
  } else if (config?.returnType === 'DocumentFragment') {
    purifyConfig.RETURN_DOM_FRAGMENT = true;
  }
  return purify_es/* default */.A.sanitize(html ?? '', purifyConfig);
}
;// ../../packages/js/sanitize/src/trusted-types-policy.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * The type for our trusted types policy.
 */

/**
 * Cached policy instance to ensure it's only created once.
 */
let policyInstance;

/**
 * Get or create a trusted types policy for DOMPurify.
 *
 * @return TrustedTypePolicy object or null if not supported.
 */
function getTrustedTypesPolicy() {
  if (policyInstance !== undefined) {
    return policyInstance;
  }
  if (typeof window === 'undefined' || !window.trustedTypes) {
    policyInstance = null;
    return null;
  }
  try {
    policyInstance = window.trustedTypes.createPolicy('woocommerce-sanitize', {
      createHTML: input => sanitizeHTML(input)
    });
  } catch (error) {
    policyInstance = null;
    // eslint-disable-next-line no-console
    console.warn('Failed to create "woocommerce-sanitize" trusted type policy:', error);
  }
  return policyInstance;
}
;// ../../packages/js/sanitize/src/index.ts



/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   D: () => (/* binding */ Heading)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const Heading = ({
  className,
  title,
  subTitle
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-profiler-heading', className),
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h1", {
      className: "woocommerce-profiler-heading__title",
      children: title
    }), subTitle && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
      className: "woocommerce-profiler-heading__subtitle",
      children: subTitle
    })]
  });
};
try {
    // @ts-ignore
    Heading.displayName = "Heading";
    // @ts-ignore
    Heading.__docgenInfo = { "description": "", "displayName": "Heading", "props": { "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "string | Element" } }, "subTitle": { "defaultValue": null, "description": "", "name": "subTitle", "required": false, "type": { "name": "string | Element" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx#Heading"] = { docgenInfo: Heading.__docgenInfo, name: "Heading", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx#Heading" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  V: () => (/* binding */ Navigation)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/woologo.tsx

/* eslint-disable max-len */
const WooLogo = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("svg", {
    width: "91",
    height: "24",
    viewBox: "0 0 91 24",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg",
    className: "wc-icon wc-icon__woo-logo new-branding",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("path", {
      d: "M79.0537 0C72.2755 0 67.0874 5.10851 67.0874 12C67.0874 18.8915 72.2755 24 79.0537 24C85.832 24 91.0002 18.8915 91.0002 12C91.0002 5.10851 85.7923 0 79.0537 0ZM79.0537 16.6277C76.5094 16.6277 74.7602 14.6644 74.7602 12C74.7602 9.33555 76.4895 7.37228 79.0537 7.37228C81.6179 7.37228 83.3473 9.33555 83.3473 12C83.3473 14.6644 81.5981 16.6277 79.0537 16.6277Z",
      fill: "#873DFF"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("path", {
      d: "M53.7285 0C46.9503 0 41.7622 5.10851 41.7622 12C41.7622 18.8915 46.9701 24 53.7285 24C60.4869 24 65.675 18.8915 65.675 12C65.675 5.10851 60.4671 0 53.7285 0ZM53.7285 16.6277C51.1842 16.6277 49.435 14.6644 49.435 12C49.435 9.33555 51.1643 7.37228 53.7285 7.37228C56.2928 7.37228 58.0221 9.33555 58.0221 12C58.0221 14.6644 56.2928 16.6277 53.7285 16.6277Z",
      fill: "#873DFF"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("path", {
      d: "M11.688 24C14.3715 24 16.5183 22.6577 18.1483 19.5726L21.7461 12.7813V18.5509C21.7461 21.9365 23.9327 24 27.3317 24C29.9556 24 31.8837 22.798 33.792 19.5726L42.1207 5.44908C43.9494 2.36394 42.6574 0 38.6421 0C36.4953 0 35.1039 0.721201 33.8516 3.08514L28.107 13.9232V4.28714C28.107 1.40234 26.7553 0 24.2308 0C22.2629 0 20.6926 0.861435 19.4602 3.26544L14.0535 13.9032V4.38731C14.0535 1.30217 12.8012 0 9.74004 0H3.53822C1.19266 0 0 1.10184 0 3.14524C0 5.18864 1.23241 6.33054 3.53822 6.33054H6.08255V18.5309C6.10243 21.9365 8.3486 24 11.688 24Z",
      fill: "#873DFF"
    })]
  });
};
/* eslint-enable max-len */

/* harmony default export */ const woologo = (WooLogo);
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/progress-bar/progress-bar.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const ProgressBar = ({
  className = '',
  percent = 0,
  color = '#674399',
  bgcolor = 'var(--wp-admin-theme-color)'
}) => {
  const containerStyles = {
    backgroundColor: bgcolor
  };
  const fillerStyles = {
    backgroundColor: color,
    width: `${percent}%`,
    display: percent === 0 ? 'none' : 'inherit'
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: `woocommerce-profiler-progress-bar ${className}`,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-profiler-progress-bar__container",
      style: containerStyles,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-progress-bar__filler",
        style: fillerStyles
      })
    })
  });
};
/* harmony default export */ const progress_bar = (ProgressBar);
try {
    // @ts-ignore
    progressbar.displayName = "progressbar";
    // @ts-ignore
    progressbar.__docgenInfo = { "description": "", "displayName": "progressbar", "props": { "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "percent": { "defaultValue": { value: "0" }, "description": "", "name": "percent", "required": false, "type": { "name": "number" } }, "color": { "defaultValue": { value: "#674399" }, "description": "", "name": "color", "required": false, "type": { "name": "string" } }, "bgcolor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "", "name": "bgcolor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/progress-bar/progress-bar.tsx#progressbar"] = { docgenInfo: progressbar.__docgenInfo, name: "progressbar", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/progress-bar/progress-bar.tsx#progressbar" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




const Navigation = ({
  percentage = 0,
  onSkip,
  skipText = (0,build_module.__)('Skip this step', 'woocommerce'),
  showProgress = true,
  showLogo = true,
  classNames = {},
  progressBarColor = 'var(--wp-admin-theme-color)'
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: (0,clsx/* default */.A)('woocommerce-profiler-navigation-container', classNames),
    children: [showProgress && /*#__PURE__*/(0,jsx_runtime.jsx)(progress_bar, {
      className: 'progress-bar',
      percent: percentage,
      color: progressBarColor,
      bgcolor: 'transparent'
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-navigation",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-navigation-col-left",
        children: showLogo && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "woologo",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(woologo, {})
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-navigation-col-right",
        children: typeof onSkip === 'function' && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          onClick: onSkip,
          className: (0,clsx/* default */.A)('woocommerce-profiler-navigation-skip-link', classNames.mobile ? 'mobile' : ''),
          isLink: true,
          children: skipText
        })
      })]
    })]
  });
};
try {
    // @ts-ignore
    Navigation.displayName = "Navigation";
    // @ts-ignore
    Navigation.__docgenInfo = { "description": "", "displayName": "Navigation", "props": { "onSkip": { "defaultValue": null, "description": "", "name": "onSkip", "required": false, "type": { "name": "(() => void)" } }, "percentage": { "defaultValue": { value: "0" }, "description": "", "name": "percentage", "required": false, "type": { "name": "number" } }, "previous": { "defaultValue": null, "description": "", "name": "previous", "required": false, "type": { "name": "string" } }, "showProgress": { "defaultValue": { value: "true" }, "description": "", "name": "showProgress", "required": false, "type": { "name": "boolean" } }, "showLogo": { "defaultValue": { value: "true" }, "description": "", "name": "showLogo", "required": false, "type": { "name": "boolean" } }, "classNames": { "defaultValue": { value: "{}" }, "description": "", "name": "classNames", "required": false, "type": { "name": "{ mobile?: boolean; }" } }, "skipText": { "defaultValue": { value: "__( 'Skip this step', 'woocommerce' )" }, "description": "", "name": "skipText", "required": false, "type": { "name": "string" } }, "progressBarColor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "", "name": "progressBarColor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx#Navigation"] = { docgenInfo: Navigation.__docgenInfo, name: "Navigation", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx#Navigation" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   b: () => (/* binding */ WithSetupWizardLayout)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");

const WithSetupWizardLayout = Story => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: "woocommerce-profile-wizard__body woocommerce-admin-full-screen",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Story, {})
  });
};
try {
    // @ts-ignore
    WithSetupWizardLayout.displayName = "WithSetupWizardLayout";
    // @ts-ignore
    WithSetupWizardLayout.__docgenInfo = { "description": "", "displayName": "WithSetupWizardLayout", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx#WithSetupWizardLayout"] = { docgenInfo: WithSetupWizardLayout.__docgenInfo, name: "WithSetupWizardLayout", path: "../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx#WithSetupWizardLayout" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);