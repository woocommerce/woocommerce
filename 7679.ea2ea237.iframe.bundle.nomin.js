"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7679],{

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

/***/ "../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  "default": () => (/* binding */ index_default)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/nonce.js
function createNonceMiddleware(nonce) {
  const middleware = (options, next) => {
    const { headers = {} } = options;
    for (const headerName in headers) {
      if (headerName.toLowerCase() === "x-wp-nonce" && headers[headerName] === middleware.nonce) {
        return next(options);
      }
    }
    return next({
      ...options,
      headers: {
        ...headers,
        "X-WP-Nonce": middleware.nonce
      }
    });
  };
  middleware.nonce = nonce;
  return middleware;
}
var nonce_default = createNonceMiddleware;

//# sourceMappingURL=nonce.js.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/namespace-endpoint.js
const namespaceAndEndpointMiddleware = (options, next) => {
  let path = options.path;
  let namespaceTrimmed, endpointTrimmed;
  if (typeof options.namespace === "string" && typeof options.endpoint === "string") {
    namespaceTrimmed = options.namespace.replace(/^\/|\/$/g, "");
    endpointTrimmed = options.endpoint.replace(/^\//, "");
    if (endpointTrimmed) {
      path = namespaceTrimmed + "/" + endpointTrimmed;
    } else {
      path = namespaceTrimmed;
    }
  }
  delete options.namespace;
  delete options.endpoint;
  return next({
    ...options,
    path
  });
};
var namespace_endpoint_default = namespaceAndEndpointMiddleware;

//# sourceMappingURL=namespace-endpoint.js.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/root-url.js

const createRootURLMiddleware = (rootURL) => (options, next) => {
  return namespace_endpoint_default(options, (optionsWithPath) => {
    let url = optionsWithPath.url;
    let path = optionsWithPath.path;
    let apiRoot;
    if (typeof path === "string") {
      apiRoot = rootURL;
      if (-1 !== rootURL.indexOf("?")) {
        path = path.replace("?", "&");
      }
      path = path.replace(/^\//, "");
      if ("string" === typeof apiRoot && -1 !== apiRoot.indexOf("?")) {
        path = path.replace("?", "&");
      }
      url = apiRoot + path;
    }
    return next({
      ...optionsWithPath,
      url
    });
  });
};
var root_url_default = createRootURLMiddleware;

//# sourceMappingURL=root-url.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/normalize-path.mjs
var normalize_path = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/normalize-path.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs + 2 modules
var get_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs + 1 modules
var add_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/preloading.js

function createPreloadingMiddleware(preloadedData) {
  const cache = Object.fromEntries(
    Object.entries(preloadedData).map(([path, data]) => [
      (0,normalize_path/* normalizePath */.F)(path),
      data
    ])
  );
  return (options, next) => {
    const { parse = true } = options;
    let rawPath = options.path;
    if (!rawPath && options.url) {
      const { rest_route: pathFromQuery, ...queryArgs } = (0,get_query_args/* getQueryArgs */.u)(
        options.url
      );
      if (typeof pathFromQuery === "string") {
        rawPath = (0,add_query_args/* addQueryArgs */.F)(pathFromQuery, queryArgs);
      }
    }
    if (typeof rawPath !== "string") {
      return next(options);
    }
    const method = options.method || "GET";
    const path = (0,normalize_path/* normalizePath */.F)(rawPath);
    if ("GET" === method && cache[path]) {
      const cacheData = cache[path];
      delete cache[path];
      return prepareResponse(cacheData, !!parse);
    } else if ("OPTIONS" === method && cache[method] && cache[method][path]) {
      const cacheData = cache[method][path];
      delete cache[method][path];
      return prepareResponse(cacheData, !!parse);
    }
    return next(options);
  };
}
function prepareResponse(responseData, parse) {
  if (parse) {
    return Promise.resolve(responseData.body);
  }
  try {
    return Promise.resolve(
      new window.Response(JSON.stringify(responseData.body), {
        status: 200,
        statusText: "OK",
        headers: responseData.headers
      })
    );
  } catch {
    Object.entries(
      responseData.headers
    ).forEach(([key, value]) => {
      if (key.toLowerCase() === "link") {
        responseData.headers[key] = value.replace(
          /<([^>]+)>/,
          (_, url) => `<${encodeURI(url)}>`
        );
      }
    });
    return Promise.resolve(
      parse ? responseData.body : new window.Response(JSON.stringify(responseData.body), {
        status: 200,
        statusText: "OK",
        headers: responseData.headers
      })
    );
  }
}
var preloading_default = createPreloadingMiddleware;

//# sourceMappingURL=preloading.js.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/fetch-all-middleware.js


const modifyQuery = ({ path, url, ...options }, queryArgs) => ({
  ...options,
  url: url && (0,add_query_args/* addQueryArgs */.F)(url, queryArgs),
  path: path && (0,add_query_args/* addQueryArgs */.F)(path, queryArgs)
});
const parseResponse = (response) => response.json ? response.json() : Promise.reject(response);
const parseLinkHeader = (linkHeader) => {
  if (!linkHeader) {
    return {};
  }
  const match = linkHeader.match(/<([^>]+)>; rel="next"/);
  return match ? {
    next: match[1]
  } : {};
};
const getNextPageUrl = (response) => {
  const { next } = parseLinkHeader(response.headers.get("link"));
  return next;
};
const requestContainsUnboundedQuery = (options) => {
  const pathIsUnbounded = !!options.path && options.path.indexOf("per_page=-1") !== -1;
  const urlIsUnbounded = !!options.url && options.url.indexOf("per_page=-1") !== -1;
  return pathIsUnbounded || urlIsUnbounded;
};
const fetchAllMiddleware = async (options, next) => {
  if (options.parse === false) {
    return next(options);
  }
  if (!requestContainsUnboundedQuery(options)) {
    return next(options);
  }
  const response = await index_default({
    ...modifyQuery(options, {
      per_page: 100
    }),
    // Ensure headers are returned for page 1.
    parse: false
  });
  const results = await parseResponse(response);
  if (!Array.isArray(results)) {
    return results;
  }
  let nextPage = getNextPageUrl(response);
  if (!nextPage) {
    return results;
  }
  let mergedResults = [].concat(results);
  while (nextPage) {
    const nextResponse = await index_default({
      ...options,
      // Ensure the URL for the next page is used instead of any provided path.
      path: void 0,
      url: nextPage,
      // Ensure we still get headers so we can identify the next page.
      parse: false
    });
    const nextResults = await parseResponse(nextResponse);
    mergedResults = mergedResults.concat(nextResults);
    nextPage = getNextPageUrl(nextResponse);
  }
  return mergedResults;
};
var fetch_all_middleware_default = fetchAllMiddleware;

//# sourceMappingURL=fetch-all-middleware.js.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/http-v1.js
const OVERRIDE_METHODS = /* @__PURE__ */ new Set(["PATCH", "PUT", "DELETE"]);
const DEFAULT_METHOD = "GET";
const httpV1Middleware = (options, next) => {
  const { method = DEFAULT_METHOD } = options;
  if (OVERRIDE_METHODS.has(method.toUpperCase())) {
    options = {
      ...options,
      headers: {
        ...options.headers,
        "X-HTTP-Method-Override": method,
        "Content-Type": "application/json"
      },
      method: "POST"
    };
  }
  return next(options);
};
var http_v1_default = httpV1Middleware;

//# sourceMappingURL=http-v1.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/has-query-arg.mjs
var has_query_arg = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/has-query-arg.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/user-locale.js

const userLocaleMiddleware = (options, next) => {
  if (typeof options.url === "string" && !(0,has_query_arg/* hasQueryArg */.d)(options.url, "_locale")) {
    options.url = (0,add_query_args/* addQueryArgs */.F)(options.url, { _locale: "user" });
  }
  if (typeof options.path === "string" && !(0,has_query_arg/* hasQueryArg */.d)(options.path, "_locale")) {
    options.path = (0,add_query_args/* addQueryArgs */.F)(options.path, { _locale: "user" });
  }
  return next(options);
};
var user_locale_default = userLocaleMiddleware;

//# sourceMappingURL=user-locale.js.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/utils/response.js

async function parseJsonAndNormalizeError(response) {
  try {
    return await response.json();
  } catch {
    throw {
      code: "invalid_json",
      message: (0,build_module.__)("The response is not a valid JSON response.")
    };
  }
}
async function parseResponseAndNormalizeError(response, shouldParseResponse = true) {
  if (!shouldParseResponse) {
    return response;
  }
  if (response.status === 204) {
    return null;
  }
  return await parseJsonAndNormalizeError(response);
}
async function parseAndThrowError(response, shouldParseResponse = true) {
  if (!shouldParseResponse) {
    throw response;
  }
  throw await parseJsonAndNormalizeError(response);
}

//# sourceMappingURL=response.js.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/media-upload.js


function isMediaUploadRequest(options) {
  const isCreateMethod = !!options.method && options.method === "POST";
  const isMediaEndpoint = !!options.path && options.path.indexOf("/wp/v2/media") !== -1 || !!options.url && options.url.indexOf("/wp/v2/media") !== -1;
  return isMediaEndpoint && isCreateMethod;
}
const mediaUploadMiddleware = (options, next) => {
  if (!isMediaUploadRequest(options)) {
    return next(options);
  }
  let retries = 0;
  const maxRetries = 5;
  const postProcess = (attachmentId) => {
    retries++;
    return next({
      path: `/wp/v2/media/${attachmentId}/post-process`,
      method: "POST",
      data: { action: "create-image-subsizes" },
      parse: false
    }).catch(() => {
      if (retries < maxRetries) {
        return postProcess(attachmentId);
      }
      next({
        path: `/wp/v2/media/${attachmentId}?force=true`,
        method: "DELETE"
      });
      return Promise.reject();
    });
  };
  return next({ ...options, parse: false }).catch((response) => {
    if (!(response instanceof globalThis.Response)) {
      return Promise.reject(response);
    }
    const attachmentId = response.headers.get(
      "x-wp-upload-attachment-id"
    );
    if (response.status >= 500 && response.status < 600 && attachmentId) {
      return postProcess(attachmentId).catch(() => {
        if (options.parse !== false) {
          return Promise.reject({
            code: "post_process",
            message: (0,build_module.__)(
              "Media upload failed. If this is a photo or a large image, please scale it down and try again."
            )
          });
        }
        return Promise.reject(response);
      });
    }
    return parseAndThrowError(response, options.parse);
  }).then(
    (response) => parseResponseAndNormalizeError(response, options.parse)
  );
};
var media_upload_default = mediaUploadMiddleware;

//# sourceMappingURL=media-upload.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs
var get_query_arg = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/remove-query-args.mjs
var remove_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/remove-query-args.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/middlewares/theme-preview.js

const createThemePreviewMiddleware = themePath => (options, next) => {
  if (typeof options.url === "string") {
    const wpThemePreview = (0,get_query_arg/* getQueryArg */.d)(options.url, "wp_theme_preview");
    if (wpThemePreview === void 0) {
      options.url = (0,add_query_args/* addQueryArgs */.F)(options.url, {
        wp_theme_preview: themePath
      });
    } else if (wpThemePreview === "") {
      options.url = (0,remove_query_args/* removeQueryArgs */.m)(options.url, "wp_theme_preview");
    }
  }
  if (typeof options.path === "string") {
    const wpThemePreview = (0,get_query_arg/* getQueryArg */.d)(options.path, "wp_theme_preview");
    if (wpThemePreview === void 0) {
      options.path = (0,add_query_args/* addQueryArgs */.F)(options.path, {
        wp_theme_preview: themePath
      });
    } else if (wpThemePreview === "") {
      options.path = (0,remove_query_args/* removeQueryArgs */.m)(options.path, "wp_theme_preview");
    }
  }
  return next(options);
};
var theme_preview_default = createThemePreviewMiddleware;

//# sourceMappingURL=theme-preview.js.map
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.33.1/node_modules/@wordpress/api-fetch/build-module/index.js











const DEFAULT_HEADERS = {
  // The backend uses the Accept header as a condition for considering an
  // incoming request as a REST request.
  //
  // See: https://core.trac.wordpress.org/ticket/44534
  Accept: "application/json, */*;q=0.1"
};
const DEFAULT_OPTIONS = {
  credentials: "include"
};
const middlewares = [
  user_locale_default,
  namespace_endpoint_default,
  http_v1_default,
  fetch_all_middleware_default
];
function registerMiddleware(middleware) {
  middlewares.unshift(middleware);
}
const defaultFetchHandler = (nextOptions) => {
  const { url, path, data, parse = true, ...remainingOptions } = nextOptions;
  let { body, headers } = nextOptions;
  headers = { ...DEFAULT_HEADERS, ...headers };
  if (data) {
    body = JSON.stringify(data);
    headers["Content-Type"] = "application/json";
  }
  const responsePromise = globalThis.fetch(
    // Fall back to explicitly passing `window.location` which is the behavior if `undefined` is passed.
    url || path || window.location.href,
    {
      ...DEFAULT_OPTIONS,
      ...remainingOptions,
      body,
      headers
    }
  );
  return responsePromise.then(
    (response) => {
      if (!response.ok) {
        return parseAndThrowError(response, parse);
      }
      return parseResponseAndNormalizeError(response, parse);
    },
    (err) => {
      if (err && err.name === "AbortError") {
        throw err;
      }
      if (!globalThis.navigator.onLine) {
        throw {
          code: "offline_error",
          message: (0,build_module.__)(
            "Unable to connect. Please check your Internet connection."
          )
        };
      }
      throw {
        code: "fetch_error",
        message: (0,build_module.__)(
          "Could not get a valid response from the server."
        )
      };
    }
  );
};
let fetchHandler = defaultFetchHandler;
function setFetchHandler(newFetchHandler) {
  fetchHandler = newFetchHandler;
}
const apiFetch = (options) => {
  const enhancedHandler = middlewares.reduceRight(
    (next, middleware) => {
      return (workingOptions) => middleware(workingOptions, next);
    },
    fetchHandler
  );
  return enhancedHandler(options).catch((error) => {
    if (error.code !== "rest_cookie_invalid_nonce") {
      return Promise.reject(error);
    }
    return globalThis.fetch(apiFetch.nonceEndpoint).then((response) => {
      if (!response.ok) {
        return Promise.reject(error);
      }
      return response.text();
    }).then((text) => {
      apiFetch.nonceMiddleware.nonce = text;
      return apiFetch(options);
    });
  });
};
apiFetch.use = registerMiddleware;
apiFetch.setFetchHandler = setFetchHandler;
apiFetch.createNonceMiddleware = nonce_default;
apiFetch.createPreloadingMiddleware = preloading_default;
apiFetch.createRootURLMiddleware = root_url_default;
apiFetch.fetchAllMiddleware = fetch_all_middleware_default;
apiFetch.mediaUploadMiddleware = media_upload_default;
apiFetch.createThemePreviewMiddleware = theme_preview_default;
var index_default = apiFetch;


//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  F: () => (/* binding */ addQueryArgs)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs + 2 modules
var get_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs
var build_query_string = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs");
;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-fragment.mjs
// packages/url/src/get-fragment.ts
function getFragment(url) {
  const matches = /^\S+?(#[^\s\?]*)/.exec(url);
  if (matches) {
    return matches[1];
  }
}

//# sourceMappingURL=get-fragment.mjs.map

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs
// packages/url/src/add-query-args.ts



function addQueryArgs(url = "", args) {
  if (!args || !Object.keys(args).length) {
    return url;
  }
  const fragment = getFragment(url) || "";
  let baseUrl = url.replace(fragment, "");
  const queryStringIndex = url.indexOf("?");
  if (queryStringIndex !== -1) {
    args = Object.assign((0,get_query_args/* getQueryArgs */.u)(url), args);
    baseUrl = baseUrl.substr(0, queryStringIndex);
  }
  return baseUrl + "?" + (0,build_query_string/* buildQueryString */.G)(args) + fragment;
}

//# sourceMappingURL=add-query-args.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   G: () => (/* binding */ buildQueryString)
/* harmony export */ });
// packages/url/src/build-query-string.ts
function buildQueryString(data) {
  let string = "";
  const stack = Object.entries(data);
  let pair;
  while (pair = stack.shift()) {
    let [key, value] = pair;
    const hasNestedData = Array.isArray(value) || value && value.constructor === Object;
    if (hasNestedData) {
      const valuePairs = Object.entries(value).reverse();
      for (const [member, memberValue] of valuePairs) {
        stack.unshift([`${key}[${member}]`, memberValue]);
      }
    } else if (value !== void 0) {
      if (value === null) {
        value = "";
      }
      string += "&" + [key, String(value)].map(encodeURIComponent).join("=");
    }
  }
  return string.substr(1);
}

//# sourceMappingURL=build-query-string.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   d: () => (/* binding */ getQueryArg)
/* harmony export */ });
/* harmony import */ var _get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
// packages/url/src/get-query-arg.ts

function getQueryArg(url, arg) {
  return (0,_get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__/* .getQueryArgs */ .u)(url)[arg];
}

//# sourceMappingURL=get-query-arg.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  u: () => (/* binding */ getQueryArgs)
});

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/safe-decode-uri-component.mjs
// packages/url/src/safe-decode-uri-component.ts
function safeDecodeURIComponent(uriComponent) {
  try {
    return decodeURIComponent(uriComponent);
  } catch {
    return uriComponent;
  }
}

//# sourceMappingURL=safe-decode-uri-component.mjs.map

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-string.mjs
// packages/url/src/get-query-string.ts
function getQueryString(url) {
  let query;
  try {
    query = new URL(url, "http://example.com").search.substring(1);
  } catch {
  }
  if (query) {
    return query;
  }
}

//# sourceMappingURL=get-query-string.mjs.map

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs
// packages/url/src/get-query-args.ts


function setPath(object, path, value) {
  const length = path.length;
  const lastIndex = length - 1;
  for (let i = 0; i < length; i++) {
    let key = path[i];
    if (!key && Array.isArray(object)) {
      key = object.length.toString();
    }
    key = ["__proto__", "constructor", "prototype"].includes(key) ? key.toUpperCase() : key;
    const isNextKeyArrayIndex = !isNaN(Number(path[i + 1]));
    object[key] = i === lastIndex ? (
      // If at end of path, assign the intended value.
      value
    ) : (
      // Otherwise, advance to the next object in the path, creating
      // it if it does not yet exist.
      object[key] || (isNextKeyArrayIndex ? [] : {})
    );
    if (Array.isArray(object[key]) && !isNextKeyArrayIndex) {
      object[key] = { ...object[key] };
    }
    object = object[key];
  }
}
function getQueryArgs(url) {
  return (getQueryString(url) || "").replace(/\+/g, "%20").split("&").reduce((accumulator, keyValue) => {
    const [key, value = ""] = keyValue.split("=").filter(Boolean).map(safeDecodeURIComponent);
    if (key) {
      const segments = key.replace(/\]/g, "").split("[");
      setPath(accumulator, segments, value);
    }
    return accumulator;
  }, /* @__PURE__ */ Object.create(null));
}

//# sourceMappingURL=get-query-args.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/has-query-arg.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   d: () => (/* binding */ hasQueryArg)
/* harmony export */ });
/* harmony import */ var _get_query_arg_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs");
// packages/url/src/has-query-arg.ts

function hasQueryArg(url, arg) {
  return (0,_get_query_arg_mjs__WEBPACK_IMPORTED_MODULE_0__/* .getQueryArg */ .d)(url, arg) !== void 0;
}

//# sourceMappingURL=has-query-arg.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/normalize-path.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   F: () => (/* binding */ normalizePath)
/* harmony export */ });
// packages/url/src/normalize-path.ts
function normalizePath(path) {
  const split = path.split("?");
  const query = split[1];
  const base = split[0];
  if (!query) {
    return base;
  }
  return base + "?" + query.split("&").map((entry) => entry.split("=")).map((pair) => pair.map(decodeURIComponent)).sort((a, b) => a[0].localeCompare(b[0])).map((pair) => pair.map(encodeURIComponent)).map((pair) => pair.join("=")).join("&");
}

//# sourceMappingURL=normalize-path.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/remove-query-args.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   m: () => (/* binding */ removeQueryArgs)
/* harmony export */ });
/* harmony import */ var _get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
/* harmony import */ var _build_query_string_mjs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs");
// packages/url/src/remove-query-args.ts


function removeQueryArgs(url, ...args) {
  const fragment = url.replace(/^[^#]*/, "");
  url = url.replace(/#.*/, "");
  const queryStringIndex = url.indexOf("?");
  if (queryStringIndex === -1) {
    return url + fragment;
  }
  const query = (0,_get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__/* .getQueryArgs */ .u)(url);
  const baseURL = url.substr(0, queryStringIndex);
  args.forEach((arg) => delete query[arg]);
  const queryString = (0,_build_query_string_mjs__WEBPACK_IMPORTED_MODULE_1__/* .buildQueryString */ .G)(query);
  const updatedUrl = queryString ? baseURL + "?" + queryString : baseURL;
  return updatedUrl + fragment;
}

//# sourceMappingURL=remove-query-args.mjs.map


/***/ })

}]);