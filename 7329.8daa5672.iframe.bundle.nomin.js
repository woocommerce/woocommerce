"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7329],{

/***/ "../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/_browser-chunks/chunk-4BE7D4DS.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   V: () => (/* binding */ __export)
/* harmony export */ });
var __defProp = Object.defineProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: !0 });
};




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+addon-docs@10.5._45e94a3c44ee92dcd221500f61a59dd9/node_modules/@storybook/addon-docs/dist/_browser-chunks/chunk-UAWMPV5J.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   P$: () => (/* binding */ __commonJS),
/* harmony export */   VA: () => (/* binding */ __export),
/* harmony export */   f1: () => (/* binding */ __toESM)
/* harmony export */ });
var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __getProtoOf = Object.getPrototypeOf, __hasOwnProp = Object.prototype.hasOwnProperty;
var __commonJS = (cb, mod) => function() {
  try {
    return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
  } catch (e) {
    throw mod = 0, e;
  }
};
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: !0 });
}, __copyProps = (to, from, except, desc) => {
  if (from && typeof from == "object" || typeof from == "function")
    for (let key of __getOwnPropNames(from))
      !__hasOwnProp.call(to, key) && key !== except && __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  return to;
};
var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
  // If the importer is in node compatibility mode or this is not an ESM
  // file that has been converted to a CommonJS file using a Babel-
  // compatible transform (i.e. "__esModule" has not been set), then set
  // "default" to the CommonJS "module.exports" for node compatibility.
  isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: !0 }) : target,
  mod
));




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-FX5LXYO2.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   S1: () => (/* binding */ PARAM_KEY),
/* harmony export */   VA: () => (/* binding */ __export)
/* harmony export */ });
/* unused harmony exports ADDON_ID, constants_default */
var __defProp = Object.defineProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: !0 });
};

// src/constants.ts
var ADDON_ID = "storybook/links", PARAM_KEY = "links", constants_default = {
  NAVIGATE: `${ADDON_ID}/navigate`,
  REQUEST: `${ADDON_ID}/request`,
  RECEIVE: `${ADDON_ID}/receive`
};




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-LAYPZ6CY.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   q9: () => (/* binding */ withLinks)
/* harmony export */ });
/* unused harmony exports navigate, hrefTo, linkTo */
/* harmony import */ var _chunk_FX5LXYO2_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-FX5LXYO2.js");
/* harmony import */ var storybook_internal_core_events__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("storybook/internal/core-events");
/* harmony import */ var storybook_internal_core_events__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(storybook_internal_core_events__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _storybook_global__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("@storybook/global");
/* harmony import */ var _storybook_global__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_storybook_global__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var storybook_preview_api__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("storybook/preview-api");
/* harmony import */ var storybook_preview_api__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(storybook_preview_api__WEBPACK_IMPORTED_MODULE_3__);


// src/utils.ts




var { document, HTMLElement } = _storybook_global__WEBPACK_IMPORTED_MODULE_2__.global;
function parseQuery(queryString) {
  let query = {}, pairs = (queryString[0] === "?" ? queryString.substring(1) : queryString).split("&").filter(Boolean);
  for (let i = 0; i < pairs.length; i++) {
    let pair = pairs[i].split("=");
    query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || "");
  }
  return query;
}
var navigate = (params) => storybook_preview_api__WEBPACK_IMPORTED_MODULE_3__.addons.getChannel().emit(storybook_internal_core_events__WEBPACK_IMPORTED_MODULE_1__.SELECT_STORY, params), hrefTo = (title, name) => new Promise((resolve) => {
  let { location } = document, existingId = parseQuery(location.search).id, titleToLink = title || existingId.split("--", 2)[0], path = `/story/${toId(titleToLink, name)}`, sbPath = location.pathname.replace(/iframe\.html$/, ""), url = `${location.origin + sbPath}?${Object.entries({ path }).map((item) => `${item[0]}=${item[1]}`).join("&")}`;
  resolve(url);
}), valueOrCall = (args) => (value) => typeof value == "function" ? value(...args) : value, linkTo = (idOrTitle, nameInput) => (...args) => {
  let resolver = valueOrCall(args), title = resolver(idOrTitle), name = nameInput ? resolver(nameInput) : !1;
  title?.match(/--/) && !name ? navigate({ storyId: title }) : name && title ? navigate({ kind: title, story: name }) : title ? navigate({ kind: title }) : name && navigate({ story: name });
}, linksListener = (e) => {
  let { target } = e;
  if (!(target instanceof HTMLElement))
    return;
  let element = target, { sbKind: kind, sbStory: story } = element.dataset;
  (kind || story) && (e.preventDefault(), navigate({ kind, story }));
}, hasListener = !1, on = () => {
  hasListener || (hasListener = !0, document.addEventListener("click", linksListener));
}, off = () => {
  hasListener && (hasListener = !1, document.removeEventListener("click", linksListener));
}, withLinks = (0,storybook_preview_api__WEBPACK_IMPORTED_MODULE_3__.makeDecorator)({
  name: "withLinks",
  parameterName: _chunk_FX5LXYO2_js__WEBPACK_IMPORTED_MODULE_0__/* .PARAM_KEY */ .S1,
  wrapper: (getStory, context) => (on(), storybook_preview_api__WEBPACK_IMPORTED_MODULE_3__.addons.getChannel().once(storybook_internal_core_events__WEBPACK_IMPORTED_MODULE_1__.STORY_CHANGED, off), getStory(context))
});




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-PZ2KFUGG.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   r: () => (/* binding */ decorators)
/* harmony export */ });
/* unused harmony export index_default */
/* harmony import */ var _chunk_LAYPZ6CY_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-LAYPZ6CY.js");
/* harmony import */ var _chunk_FX5LXYO2_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-FX5LXYO2.js");



// src/preview.ts
var preview_exports = {};
(0,_chunk_FX5LXYO2_js__WEBPACK_IMPORTED_MODULE_1__/* .__export */ .VA)(preview_exports, {
  decorators: () => decorators
});

// src/index.ts

var index_default = () => definePreviewAddon(preview_exports);

// src/preview.ts
var decorators = [_chunk_LAYPZ6CY_js__WEBPACK_IMPORTED_MODULE_0__/* .withLinks */ .q9];




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/preview.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  afterEach: () => (/* reexport */ afterEach),
  decorators: () => (/* reexport */ decorators),
  initialGlobals: () => (/* reexport */ initialGlobals),
  parameters: () => (/* reexport */ parameters)
});

;// ../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/_browser-chunks/chunk-7OPMK7TU.js
// src/constants.ts
var ADDON_ID = "storybook/a11y", PANEL_ID = `${ADDON_ID}/panel`, PARAM_KEY = "a11y", VISION_GLOBAL_KEY = "vision", UI_STATE_ID = (/* unused pure expression or super */ null && (`${ADDON_ID}/ui`)), RESULT = `${ADDON_ID}/result`, REQUEST = `${ADDON_ID}/request`, RUNNING = `${ADDON_ID}/running`, ERROR = `${ADDON_ID}/error`, MANUAL = `${ADDON_ID}/manual`, SELECT = `${ADDON_ID}/select`, DOCUMENTATION_LINK = "writing-tests/accessibility-testing", DOCUMENTATION_DISCREPANCY_LINK = (/* unused pure expression or super */ null && (`${DOCUMENTATION_LINK}#why-are-my-tests-failing-in-different-environments`));
var EVENTS = { RESULT, REQUEST, RUNNING, ERROR, MANUAL, SELECT }, STATUS_TYPE_ID_COMPONENT_TEST = "storybook/component-test", STATUS_TYPE_ID_A11Y = "storybook/a11y";

// src/visionSimulatorFilters.ts
var filters = {
  blurred: {
    label: "Blurred vision",
    filter: "blur(2px)",
    percentage: 22.9
  },
  deuteranomaly: {
    label: "Deuteranomaly",
    filter: 'url("#storybook-a11y-vision-deuteranomaly")',
    percentage: 2.7
  },
  deuteranopia: {
    label: "Deuteranopia",
    filter: 'url("#storybook-a11y-vision-deuteranopia")',
    percentage: 0.56
  },
  protanomaly: {
    label: "Protanomaly",
    filter: 'url("#storybook-a11y-vision-protanomaly")',
    percentage: 0.66
  },
  protanopia: {
    label: "Protanopia",
    filter: 'url("#storybook-a11y-vision-protanopia")',
    percentage: 0.59
  },
  tritanomaly: {
    label: "Tritanomaly",
    filter: 'url("#storybook-a11y-vision-tritanomaly")',
    percentage: 0.01
  },
  tritanopia: {
    label: "Tritanopia",
    filter: 'url("#storybook-a11y-vision-tritanopia")',
    percentage: 0.016
  },
  achromatopsia: {
    label: "Achromatopsia",
    filter: 'url("#storybook-a11y-vision-achromatopsia")',
    percentage: 1e-4
  },
  grayscale: {
    label: "Grayscale",
    filter: "grayscale(100%)"
  }
}, filterDefs = `<svg id="storybook-a11y-vision-filters" style="display: none !important;">
  <defs>
    <filter id="storybook-a11y-vision-protanopia">
      <feColorMatrix
        in="SourceGraphic"
        type="matrix"
        values="0.567, 0.433, 0, 0, 0 0.558, 0.442, 0, 0, 0 0, 0.242, 0.758, 0, 0 0, 0, 0, 1, 0"
      />
    </filter>
    <filter id="storybook-a11y-vision-protanomaly">
      <feColorMatrix
        in="SourceGraphic"
        type="matrix"
        values="0.817, 0.183, 0, 0, 0 0.333, 0.667, 0, 0, 0 0, 0.125, 0.875, 0, 0 0, 0, 0, 1, 0"
      />
    </filter>
    <filter id="storybook-a11y-vision-deuteranopia">
      <feColorMatrix
        in="SourceGraphic"
        type="matrix"
        values="0.625, 0.375, 0, 0, 0 0.7, 0.3, 0, 0, 0 0, 0.3, 0.7, 0, 0 0, 0, 0, 1, 0"
      />
    </filter>
    <filter id="storybook-a11y-vision-deuteranomaly">
      <feColorMatrix
        in="SourceGraphic"
        type="matrix"
        values="0.8, 0.2, 0, 0, 0 0.258, 0.742, 0, 0, 0 0, 0.142, 0.858, 0, 0 0, 0, 0, 1, 0"
      />
    </filter>
    <filter id="storybook-a11y-vision-tritanopia">
      <feColorMatrix
        in="SourceGraphic"
        type="matrix"
        values="0.95, 0.05,  0, 0, 0 0,  0.433, 0.567, 0, 0 0,  0.475, 0.525, 0, 0 0,  0, 0, 1, 0"
      />
    </filter>
    <filter id="storybook-a11y-vision-tritanomaly">
      <feColorMatrix
        in="SourceGraphic"
        type="matrix"
        values="0.967, 0.033, 0, 0, 0 0, 0.733, 0.267, 0, 0 0, 0.183, 0.817, 0, 0 0, 0, 0, 1, 0"
      />
    </filter>
    <filter id="storybook-a11y-vision-achromatopsia">
      <feColorMatrix
        in="SourceGraphic"
        type="matrix"
        values="0.299, 0.587, 0.114, 0, 0 0.299, 0.587, 0.114, 0, 0 0.299, 0.587, 0.114, 0, 0 0, 0, 0, 1, 0"
      />
    </filter>
  </defs>
</svg>`;



// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/_browser-chunks/chunk-4BE7D4DS.js
var chunk_4BE7D4DS = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/_browser-chunks/chunk-4BE7D4DS.js");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_TEST__"
var external_STORYBOOK_MODULE_TEST_ = __webpack_require__("storybook/test");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_CORE_EVENTS_PREVIEW_ERRORS__"
var external_STORYBOOK_MODULE_CORE_EVENTS_PREVIEW_ERRORS_ = __webpack_require__("storybook/internal/preview-errors");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_GLOBAL__"
var external_STORYBOOK_MODULE_GLOBAL_ = __webpack_require__("@storybook/global");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_PREVIEW_API__"
var external_STORYBOOK_MODULE_PREVIEW_API_ = __webpack_require__("storybook/preview-api");
;// ../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/_browser-chunks/chunk-P5J2FJ2Z.js



// src/preview.tsx
var preview_exports = {};
(0,chunk_4BE7D4DS/* __export */.V)(preview_exports, {
  afterEach: () => afterEach,
  decorators: () => decorators,
  initialGlobals: () => initialGlobals,
  parameters: () => parameters
});


// src/a11yRunner.ts




// src/a11yRunnerUtils.ts

var { document: document2 } = external_STORYBOOK_MODULE_GLOBAL_.global, withLinkPaths = (results, storyId) => {
  let pathname = document2.location.pathname.replace(/iframe\.html$/, ""), enhancedResults = { ...results };
  return ["incomplete", "passes", "violations"].forEach((key) => {
    Array.isArray(results[key]) && (enhancedResults[key] = results[key].map((result) => ({
      ...result,
      nodes: result.nodes.map((node, index) => {
        let id = `${key}.${result.id}.${index + 1}`, linkPath = `${pathname}?path=/story/${storyId}&addonPanel=${PANEL_ID}&a11ySelection=${id}`;
        return { id, ...node, linkPath };
      })
    })));
  }), enhancedResults;
};

// src/a11yRunner.ts
var { document: document3 } = external_STORYBOOK_MODULE_GLOBAL_.global, channel = external_STORYBOOK_MODULE_PREVIEW_API_.addons.getChannel(), DEFAULT_PARAMETERS = { config: {}, options: {} }, DISABLED_RULES = [
  // In component testing, landmarks are not always present
  // and the rule check can cause false positives
  "region"
], getDisabledRules = (rules = []) => {
  let disabledRules = {};
  for (let { id, enabled } of rules)
    !id || typeof enabled != "boolean" || (enabled ? delete disabledRules[id] : disabledRules[id] = { enabled: !1 });
  return disabledRules;
}, mergeDisabledRulesIntoRunOptions = (options, config) => {
  if (!options.runOnly)
    return options;
  let disabledRules = getDisabledRules(config.rules);
  return Object.keys(disabledRules).length === 0 ? options : {
    ...options,
    rules: {
      ...disabledRules,
      ...options.rules
    }
  };
}, queue = [], isRunning = !1, runNext = async () => {
  if (queue.length === 0) {
    isRunning = !1;
    return;
  }
  isRunning = !0;
  let next = queue.shift();
  next && await next(), runNext();
}, run = async (input = DEFAULT_PARAMETERS, storyId) => {
  let axe = (await __webpack_require__.e(/* import() */ 9361).then(__webpack_require__.t.bind(__webpack_require__, "../../node_modules/.pnpm/axe-core@4.11.3/node_modules/axe-core/axe.js", 23)))?.default || globalThis.axe, { config = {}, options = {} } = input;
  if (input.element)
    throw new external_STORYBOOK_MODULE_CORE_EVENTS_PREVIEW_ERRORS_.ElementA11yParameterError();
  let context = {
    include: document3?.body,
    exclude: [".sb-wrapper", "#storybook-docs", "#storybook-highlights-root"]
    // Internal Storybook elements that are always in the document
  };
  if (input.context) {
    let hasInclude = typeof input.context == "object" && "include" in input.context && input.context.include !== void 0, hasExclude = typeof input.context == "object" && "exclude" in input.context && input.context.exclude !== void 0;
    hasInclude ? context.include = input.context.include : !hasInclude && !hasExclude && (context.include = input.context), hasExclude && (context.exclude = context.exclude.concat(input.context.exclude));
  }
  axe.reset();
  let configWithDefault = {
    ...config,
    rules: [...DISABLED_RULES.map((id) => ({ id, enabled: !1 })), ...config?.rules ?? []]
  };
  axe.configure(configWithDefault);
  let optionsWithDisabledRules = mergeDisabledRulesIntoRunOptions(options, configWithDefault);
  return new Promise((resolve, reject) => {
    let highlightsRoot = document3?.getElementById("storybook-highlights-root");
    highlightsRoot && (highlightsRoot.style.display = "none");
    let task = async () => {
      try {
        let result = await axe.run(context, optionsWithDisabledRules), resultWithLinks = withLinkPaths(result, storyId);
        resolve(resultWithLinks);
      } catch (error) {
        reject(error);
      }
    };
    queue.push(task), isRunning || runNext(), highlightsRoot && (highlightsRoot.style.display = "");
  });
};
channel.on(EVENTS.MANUAL, async (storyId, input = DEFAULT_PARAMETERS) => {
  try {
    await (0,external_STORYBOOK_MODULE_PREVIEW_API_.waitForAnimations)();
    let result = await run(input, storyId), resultJson = JSON.parse(JSON.stringify(result));
    channel.emit(EVENTS.RESULT, resultJson, storyId);
  } catch (error) {
    channel.emit(EVENTS.ERROR, error);
  }
});

// src/utils.ts
function getIsVitestStandaloneRun() {
  try {
    return /* unsupported import.meta.env.VITEST_STORYBOOK */ undefined.VITEST_STORYBOOK === "false";
  } catch {
    return !1;
  }
}

// src/withVisionSimulator.ts

var knownFilters = Object.values(filters).map((f) => f.filter), knownFiltersRegExp = new RegExp(`\\b(${knownFilters.join("|")})\\b`, "g"), withVisionSimulator = (StoryFn, { globals }) => {
  let { vision } = globals, applyVisionFilter = (0,external_STORYBOOK_MODULE_PREVIEW_API_.useCallback)(() => {
    let existingFilters = document.body.style.filter.replaceAll(knownFiltersRegExp, "").trim(), visionFilter = filters[vision]?.filter;
    return visionFilter && document.body.classList.contains("sb-show-main") ? !existingFilters || existingFilters === "none" ? document.body.style.filter = visionFilter : document.body.style.filter = `${existingFilters} ${visionFilter}` : document.body.style.filter = existingFilters || "none", () => document.body.style.filter = existingFilters || "none";
  }, [vision]);
  return (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => {
    let cleanup = applyVisionFilter(), observer = new MutationObserver(() => applyVisionFilter());
    return observer.observe(document.body, { attributeFilter: ["class"] }), () => {
      cleanup(), observer.disconnect();
    };
  }, [applyVisionFilter]), (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => (document.body.insertAdjacentHTML("beforeend", filterDefs), () => {
    let filterDefsElement = document.getElementById("storybook-a11y-vision-filters");
    filterDefsElement?.parentElement?.removeChild(filterDefsElement);
  }), []), StoryFn();
};

// src/preview.tsx
var vitestMatchersExtended = !1, decorators = [withVisionSimulator], afterEach = async ({
  id: storyId,
  reporting,
  parameters: parameters2,
  globals,
  viewMode
}) => {
  let a11yParameter = parameters2.a11y, a11yGlobals = globals.a11y, shouldRunEnvironmentIndependent = !!!globals.ghostStories && a11yParameter?.disable !== !0 && a11yParameter?.test !== "off" && a11yGlobals?.manual !== !0, getMode = () => a11yParameter?.test === "todo" ? "warning" : "failed";
  if (shouldRunEnvironmentIndependent && viewMode === "story")
    try {
      let result = await run(a11yParameter, storyId);
      if (result) {
        let hasViolations = (result?.violations.length ?? 0) > 0;
        if (reporting.addReport({
          type: "a11y",
          version: 1,
          result,
          status: hasViolations ? getMode() : "passed"
        }), getIsVitestStandaloneRun() && hasViolations && getMode() === "failed") {
          if (!vitestMatchersExtended) {
            let { toHaveNoViolations } = await __webpack_require__.e(/* import() */ 603).then(__webpack_require__.bind(__webpack_require__, "../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/_browser-chunks/matchers-JIUQ3HVY.js"));
            external_STORYBOOK_MODULE_TEST_.expect.extend({ toHaveNoViolations }), vitestMatchersExtended = !0;
          }
          (0,external_STORYBOOK_MODULE_TEST_.expect)(result).toHaveNoViolations();
        }
      }
    } catch (e) {
      if (reporting.addReport({
        type: "a11y",
        version: 1,
        result: {
          error: e
        },
        status: "failed"
      }), getIsVitestStandaloneRun())
        throw e;
    }
}, initialGlobals = {
  a11y: {
    manual: !1
  },
  vision: void 0
}, parameters = {
  a11y: {
    test: "todo"
  }
};



;// ../../node_modules/.pnpm/@storybook+addon-a11y@10.5._d1e6ee1dbe1a50911864ad97b78d56ae/node_modules/@storybook/addon-a11y/dist/preview.js





/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+addon-docs@10.5._45e94a3c44ee92dcd221500f61a59dd9/node_modules/@storybook/addon-docs/dist/preview.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  parameters: () => (/* reexport */ parameters)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-docs@10.5._45e94a3c44ee92dcd221500f61a59dd9/node_modules/@storybook/addon-docs/dist/_browser-chunks/chunk-UAWMPV5J.js
var chunk_UAWMPV5J = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-docs@10.5._45e94a3c44ee92dcd221500f61a59dd9/node_modules/@storybook/addon-docs/dist/_browser-chunks/chunk-UAWMPV5J.js");
;// ../../node_modules/.pnpm/@storybook+addon-docs@10.5._45e94a3c44ee92dcd221500f61a59dd9/node_modules/@storybook/addon-docs/dist/_browser-chunks/chunk-6Z2O5XYJ.js


// src/preview.ts
var preview_exports = {};
(0,chunk_UAWMPV5J/* __export */.VA)(preview_exports, {
  parameters: () => parameters
});
var excludeTags = Object.entries(globalThis.TAGS_OPTIONS ?? {}).reduce(
  (acc, entry) => {
    let [tag, option] = entry;
    return option.excludeFromDocsStories && (acc[tag] = !0), acc;
  },
  {}
), parameters = {
  docs: {
    renderer: async () => {
      let { DocsRenderer } = await Promise.all(/* import() */[__webpack_require__.e(316), __webpack_require__.e(2534), __webpack_require__.e(2771)]).then(__webpack_require__.bind(__webpack_require__, "../../node_modules/.pnpm/@storybook+addon-docs@10.5._45e94a3c44ee92dcd221500f61a59dd9/node_modules/@storybook/addon-docs/dist/_browser-chunks/DocsRenderer-JROSPFPF.js"));
      return new DocsRenderer();
    },
    stories: {
      filter: (story) => (story.tags || []).filter((tag) => excludeTags[tag]).length === 0 && !story.parameters.docs?.disable
    }
  }
};



;// ../../node_modules/.pnpm/@storybook+addon-docs@10.5._45e94a3c44ee92dcd221500f61a59dd9/node_modules/@storybook/addon-docs/dist/preview.js




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/preview.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   decorators: () => (/* reexport safe */ _browser_chunks_chunk_PZ2KFUGG_js__WEBPACK_IMPORTED_MODULE_0__.r)
/* harmony export */ });
/* harmony import */ var _browser_chunks_chunk_PZ2KFUGG_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-PZ2KFUGG.js");
/* harmony import */ var _browser_chunks_chunk_LAYPZ6CY_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-LAYPZ6CY.js");
/* harmony import */ var _browser_chunks_chunk_FX5LXYO2_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-FX5LXYO2.js");





/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/entry-preview.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  applyDecorators: () => (/* reexport */ chunk_DDIRQRCA/* applyDecorators */.t),
  beforeAll: () => (/* reexport */ beforeAll),
  decorators: () => (/* reexport */ decorators),
  mount: () => (/* reexport */ mount),
  parameters: () => (/* reexport */ parameters),
  render: () => (/* reexport */ render),
  renderToCanvas: () => (/* reexport */ renderToCanvas)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-DDIRQRCA.js
var chunk_DDIRQRCA = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-DDIRQRCA.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js
var chunk_UAWMPV5J = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
var react_namespaceObject = /*#__PURE__*/__webpack_require__.t(react, 2);
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_PREVIEW_API__"
var external_STORYBOOK_MODULE_PREVIEW_API_ = __webpack_require__("storybook/preview-api");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_GLOBAL__"
var external_STORYBOOK_MODULE_GLOBAL_ = __webpack_require__("@storybook/global");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_TEST__"
var external_STORYBOOK_MODULE_TEST_ = __webpack_require__("storybook/test");
;// ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-7HUHAOMV.js



// src/entry-preview.tsx
var entry_preview_exports = {};
(0,chunk_UAWMPV5J/* __export */.VA)(entry_preview_exports, {
  applyDecorators: () => chunk_DDIRQRCA/* applyDecorators */.t,
  beforeAll: () => beforeAll,
  decorators: () => decorators,
  mount: () => mount,
  parameters: () => parameters,
  render: () => render,
  renderToCanvas: () => renderToCanvas
});





// src/act-compat.ts

var clonedReact = { ...react_namespaceObject };
function setReactActEnvironment(isReactActEnvironment) {
  globalThis.IS_REACT_ACT_ENVIRONMENT = isReactActEnvironment;
}
function getReactActEnvironment() {
  return globalThis.IS_REACT_ACT_ENVIRONMENT;
}
function withGlobalActEnvironment(actImplementation) {
  return (callback) => {
    let previousActEnvironment = getReactActEnvironment();
    setReactActEnvironment(!0);
    try {
      let callbackNeedsToBeAwaited = !1, actResult = actImplementation(() => {
        let result = callback();
        return result !== null && typeof result == "object" && typeof result.then == "function" && (callbackNeedsToBeAwaited = !0), result;
      });
      if (callbackNeedsToBeAwaited) {
        let thenable = actResult;
        return {
          then: (resolve, reject) => {
            thenable.then(
              (returnValue) => {
                setReactActEnvironment(previousActEnvironment), resolve(returnValue);
              },
              (error) => {
                setReactActEnvironment(previousActEnvironment), reject(error);
              }
            );
          }
        };
      } else
        return setReactActEnvironment(previousActEnvironment), actResult;
    } catch (error) {
      throw setReactActEnvironment(previousActEnvironment), error;
    }
  };
}
var getAct = async ({ disableAct = !1 } = {}) => {
  if (true)
    return (cb) => cb();
  let reactAct;
  if (typeof clonedReact.act == "function")
    reactAct = clonedReact.act;
  else {
    let deprecatedTestUtils = await Promise.all(/* import() */[__webpack_require__.e(316), __webpack_require__.e(3026)]).then(__webpack_require__.t.bind(__webpack_require__, "../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/test-utils.js", 19));
    reactAct = deprecatedTestUtils?.default?.act ?? deprecatedTestUtils.act;
  }
  return withGlobalActEnvironment(reactAct);
};

// src/render.tsx

var render = (args, context) => {
  let { id, component: Component } = context;
  if (!Component)
    throw new Error(
      `Unable to render story ${id} as the component annotation is missing from the default export`
    );
  return react.createElement(Component, { ...args });
};

// src/renderToCanvas.tsx


var { FRAMEWORK_OPTIONS } = external_STORYBOOK_MODULE_GLOBAL_.global, ErrorBoundary = class extends react.Component {
  constructor() {
    super(...arguments);
    this.state = { hasError: !1 };
  }
  static getDerivedStateFromError() {
    return { hasError: !0 };
  }
  componentDidMount() {
    let { hasError } = this.state, { showMain } = this.props;
    hasError || showMain();
  }
  componentDidCatch(err) {
    let { showException } = this.props;
    showException(err);
  }
  render() {
    let { hasError } = this.state, { children } = this.props;
    return hasError ? null : children;
  }
}, Wrapper = FRAMEWORK_OPTIONS?.strictMode ? react.StrictMode : react.Fragment, actQueue = [], isActing = !1, processActQueue = async () => {
  if (isActing || actQueue.length === 0)
    return;
  isActing = !0;
  let actTask = actQueue.shift();
  actTask && await actTask(), isActing = !1, processActQueue();
};
async function renderToCanvas({
  storyContext,
  unboundStoryFn,
  showMain,
  showException,
  forceRemount
}, canvasElement) {
  let { renderElement, unmountElement } = await Promise.all(/* import() */[__webpack_require__.e(316), __webpack_require__.e(2522)]).then(__webpack_require__.bind(__webpack_require__, "../../node_modules/.pnpm/@storybook+react-dom-shim@1_eb30881cc954c67b36a9fc52cf34a1a7/node_modules/@storybook/react-dom-shim/dist/react-18.js")), Story = unboundStoryFn, content = storyContext.parameters.__isPortableStory ? react.createElement(Story, { ...storyContext }) : react.createElement(ErrorBoundary, { key: storyContext.id, showMain, showException }, react.createElement(Story, { ...storyContext })), element = Wrapper ? react.createElement(Wrapper, null, content) : content;
  forceRemount && unmountElement(canvasElement);
  let act = await getAct({ disableAct: storyContext.viewMode === "docs" });
  return await new Promise(async (resolve, reject) => {
    actQueue.push(async () => {
      try {
        await act(async () => {
          await renderElement(element, canvasElement, storyContext?.parameters?.react?.rootOptions);
        }), resolve();
      } catch (e) {
        reject(e);
      }
    }), processActQueue();
  }), async () => {
    await act(() => {
      unmountElement(canvasElement);
    });
  };
}

// src/mount.ts
var mount = (context) => async (ui) => (ui != null && (context.originalStoryFn = () => ui), await context.renderToCanvas(), context.canvas);

// src/entry-preview.tsx
var decorators = [
  (story, context) => {
    if (!context.parameters?.react?.rsc)
      return story();
    let [major, minor] = react.version.split(".").map((part) => parseInt(part, 10));
    if (!Number.isInteger(major) || !Number.isInteger(minor))
      throw new Error("Unable to parse React version");
    if (major < 18 || major === 18 && minor < 3)
      throw new Error("React Server Components require React >= 18.3");
    return react.createElement(react.Suspense, null, story());
  },
  (story, context) => {
    if (context.tags?.includes(external_STORYBOOK_MODULE_PREVIEW_API_.Tag.TEST_FN) && !external_STORYBOOK_MODULE_GLOBAL_.global.FEATURES?.experimentalTestSyntax)
      throw new Error(
        "To use the experimental test function, you must enable the experimentalTestSyntax feature flag. See https://storybook.js.org/docs/api/main-config/main-config-features#experimentaltestsyntax"
      );
    return story();
  }
], parameters = {
  renderer: "react"
}, beforeAll = async () => {
  try {
    let act = await getAct();
    (0,external_STORYBOOK_MODULE_TEST_.configure)({
      unstable_advanceTimersWrapper: (cb) => act(cb),
      // For more context about why we need disable act warnings in waitFor:
      // https://github.com/reactwg/react-18/discussions/102
      asyncWrapper: async (cb) => {
        let previousActEnvironment = getReactActEnvironment();
        setReactActEnvironment(!1);
        try {
          let result = await cb();
          return await new Promise((resolve) => {
            setTimeout(() => {
              resolve();
            }, 0);
            let jestFakeTimers = getActiveJestFakeTimers(globalThis);
            jestFakeTimers && jestFakeTimers.advanceTimersByTime(0);
          }), result;
        } finally {
          setReactActEnvironment(previousActEnvironment);
        }
      },
      eventWrapper: (cb) => {
        let result;
        return act(() => (result = cb(), result)), result;
      }
    });
  } catch {
  }
};
function getActiveJestFakeTimers(g) {
  let jest = Reflect.get(g, "jest");
  return jest == null ? void 0 : /* legacy timers */ setTimeout._isMockFunction === !0 || // modern timers
  Object.prototype.hasOwnProperty.call(setTimeout, "clock") ? jest : void 0;
}



;// ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/entry-preview.js





/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-B3FR4PBN.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   HA: () => (/* binding */ reactElementToJsxString),
/* harmony export */   Jz: () => (/* binding */ isForwardRef),
/* harmony export */   Rf: () => (/* binding */ isMemo)
/* harmony export */ });
/* harmony import */ var _chunk_UAWMPV5J_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");


// ../../../node_modules/@base2/pretty-print-object/dist/index.js
var require_dist = (0,_chunk_UAWMPV5J_js__WEBPACK_IMPORTED_MODULE_0__/* .__commonJS */ .P$)({
  "../../../node_modules/@base2/pretty-print-object/dist/index.js"(exports) {
    "use strict";
    var __assign = exports && exports.__assign || function() {
      return __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s) Object.prototype.hasOwnProperty.call(s, p) && (t[p] = s[p]);
        }
        return t;
      }, __assign.apply(this, arguments);
    }, __spreadArrays = exports && exports.__spreadArrays || function() {
      for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
      for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
          r[k] = a[j];
      return r;
    };
    Object.defineProperty(exports, "__esModule", { value: !0 });
    var seen = [];
    function isObj(value) {
      var type = typeof value;
      return value !== null && (type === "object" || type === "function");
    }
    function isRegexp(value) {
      return Object.prototype.toString.call(value) === "[object RegExp]";
    }
    function getOwnEnumPropSymbols(object) {
      return Object.getOwnPropertySymbols(object).filter(function(keySymbol) {
        return Object.prototype.propertyIsEnumerable.call(object, keySymbol);
      });
    }
    function prettyPrint2(input, options, pad) {
      pad === void 0 && (pad = "");
      var defaultOptions = {
        indent: "	",
        singleQuotes: !0
      }, combinedOptions = __assign(__assign({}, defaultOptions), options), tokens;
      combinedOptions.inlineCharacterLimit === void 0 ? tokens = {
        newLine: `
`,
        newLineOrSpace: `
`,
        pad,
        indent: pad + combinedOptions.indent
      } : tokens = {
        newLine: "@@__PRETTY_PRINT_NEW_LINE__@@",
        newLineOrSpace: "@@__PRETTY_PRINT_NEW_LINE_OR_SPACE__@@",
        pad: "@@__PRETTY_PRINT_PAD__@@",
        indent: "@@__PRETTY_PRINT_INDENT__@@"
      };
      var expandWhiteSpace = function(string) {
        if (combinedOptions.inlineCharacterLimit === void 0)
          return string;
        var oneLined = string.replace(new RegExp(tokens.newLine, "g"), "").replace(new RegExp(tokens.newLineOrSpace, "g"), " ").replace(new RegExp(tokens.pad + "|" + tokens.indent, "g"), "");
        return oneLined.length <= combinedOptions.inlineCharacterLimit ? oneLined : string.replace(new RegExp(tokens.newLine + "|" + tokens.newLineOrSpace, "g"), `
`).replace(new RegExp(tokens.pad, "g"), pad).replace(new RegExp(tokens.indent, "g"), pad + combinedOptions.indent);
      };
      if (seen.indexOf(input) !== -1)
        return '"[Circular]"';
      if (input == null || typeof input == "number" || typeof input == "boolean" || typeof input == "function" || typeof input == "symbol" || isRegexp(input))
        return String(input);
      if (input instanceof Date)
        return "new Date('" + input.toISOString() + "')";
      if (Array.isArray(input)) {
        if (input.length === 0)
          return "[]";
        seen.push(input);
        var ret = "[" + tokens.newLine + input.map(function(el, i) {
          var eol = input.length - 1 === i ? tokens.newLine : "," + tokens.newLineOrSpace, value = prettyPrint2(el, combinedOptions, pad + combinedOptions.indent);
          return combinedOptions.transform && (value = combinedOptions.transform(input, i, value)), tokens.indent + value + eol;
        }).join("") + tokens.pad + "]";
        return seen.pop(), expandWhiteSpace(ret);
      }
      if (isObj(input)) {
        var objKeys_1 = __spreadArrays(Object.keys(input), getOwnEnumPropSymbols(input));
        if (combinedOptions.filter && (objKeys_1 = objKeys_1.filter(function(el) {
          return combinedOptions.filter && combinedOptions.filter(input, el);
        })), objKeys_1.length === 0)
          return "{}";
        seen.push(input);
        var ret = "{" + tokens.newLine + objKeys_1.map(function(el, i) {
          var eol = objKeys_1.length - 1 === i ? tokens.newLine : "," + tokens.newLineOrSpace, isSymbol = typeof el == "symbol", isClassic = !isSymbol && /^[a-z$_][a-z$_0-9]*$/i.test(el.toString()), key = isSymbol || isClassic ? el : prettyPrint2(el, combinedOptions), value = prettyPrint2(input[el], combinedOptions, pad + combinedOptions.indent);
          return combinedOptions.transform && (value = combinedOptions.transform(input, el, value)), tokens.indent + String(key) + ": " + value + eol;
        }).join("") + tokens.pad + "}";
        return seen.pop(), expandWhiteSpace(ret);
      }
      return input = String(input).replace(/[\r\n]/g, function(x) {
        return x === `
` ? "\\n" : "\\r";
      }), combinedOptions.singleQuotes ? (input = input.replace(/\\?'/g, "\\'"), "'" + input + "'") : (input = input.replace(/"/g, '\\"'), '"' + input + '"');
    }
    exports.prettyPrint = prettyPrint2;
  }
});

// ../../../node_modules/react-element-to-jsx-string/node_modules/react-is/cjs/react-is.production.min.js
var require_react_is_production_min = (0,_chunk_UAWMPV5J_js__WEBPACK_IMPORTED_MODULE_0__/* .__commonJS */ .P$)({
  "../../../node_modules/react-element-to-jsx-string/node_modules/react-is/cjs/react-is.production.min.js"(exports) {
    "use strict";
    var b = /* @__PURE__ */ Symbol.for("react.element"), c = /* @__PURE__ */ Symbol.for("react.portal"), d = /* @__PURE__ */ Symbol.for("react.fragment"), e = /* @__PURE__ */ Symbol.for("react.strict_mode"), f = /* @__PURE__ */ Symbol.for("react.profiler"), g = /* @__PURE__ */ Symbol.for("react.provider"), h = /* @__PURE__ */ Symbol.for("react.context"), k = /* @__PURE__ */ Symbol.for("react.server_context"), l = /* @__PURE__ */ Symbol.for("react.forward_ref"), m = /* @__PURE__ */ Symbol.for("react.suspense"), n = /* @__PURE__ */ Symbol.for("react.suspense_list"), p = /* @__PURE__ */ Symbol.for("react.memo"), q = /* @__PURE__ */ Symbol.for("react.lazy"), t = /* @__PURE__ */ Symbol.for("react.offscreen"), u;
    u = /* @__PURE__ */ Symbol.for("react.module.reference");
    function v(a) {
      if (typeof a == "object" && a !== null) {
        var r = a.$$typeof;
        switch (r) {
          case b:
            switch (a = a.type, a) {
              case d:
              case f:
              case e:
              case m:
              case n:
                return a;
              default:
                switch (a = a && a.$$typeof, a) {
                  case k:
                  case h:
                  case l:
                  case q:
                  case p:
                  case g:
                    return a;
                  default:
                    return r;
                }
            }
          case c:
            return r;
        }
      }
    }
    exports.ContextConsumer = h;
    exports.ContextProvider = g;
    exports.Element = b;
    exports.ForwardRef = l;
    exports.Fragment = d;
    exports.Lazy = q;
    exports.Memo = p;
    exports.Portal = c;
    exports.Profiler = f;
    exports.StrictMode = e;
    exports.Suspense = m;
    exports.SuspenseList = n;
    exports.isAsyncMode = function() {
      return !1;
    };
    exports.isConcurrentMode = function() {
      return !1;
    };
    exports.isContextConsumer = function(a) {
      return v(a) === h;
    };
    exports.isContextProvider = function(a) {
      return v(a) === g;
    };
    exports.isElement = function(a) {
      return typeof a == "object" && a !== null && a.$$typeof === b;
    };
    exports.isForwardRef = function(a) {
      return v(a) === l;
    };
    exports.isFragment = function(a) {
      return v(a) === d;
    };
    exports.isLazy = function(a) {
      return v(a) === q;
    };
    exports.isMemo = function(a) {
      return v(a) === p;
    };
    exports.isPortal = function(a) {
      return v(a) === c;
    };
    exports.isProfiler = function(a) {
      return v(a) === f;
    };
    exports.isStrictMode = function(a) {
      return v(a) === e;
    };
    exports.isSuspense = function(a) {
      return v(a) === m;
    };
    exports.isSuspenseList = function(a) {
      return v(a) === n;
    };
    exports.isValidElementType = function(a) {
      return typeof a == "string" || typeof a == "function" || a === d || a === f || a === e || a === m || a === n || a === t || typeof a == "object" && a !== null && (a.$$typeof === q || a.$$typeof === p || a.$$typeof === g || a.$$typeof === h || a.$$typeof === l || a.$$typeof === u || a.getModuleId !== void 0);
    };
    exports.typeOf = v;
  }
});

// ../../../node_modules/react-element-to-jsx-string/node_modules/react-is/cjs/react-is.development.js
var require_react_is_development = (0,_chunk_UAWMPV5J_js__WEBPACK_IMPORTED_MODULE_0__/* .__commonJS */ .P$)({
  "../../../node_modules/react-element-to-jsx-string/node_modules/react-is/cjs/react-is.development.js"(exports) {
    "use strict";
     false && 0;
  }
});

// ../../../node_modules/react-element-to-jsx-string/node_modules/react-is/index.js
var require_react_is = (0,_chunk_UAWMPV5J_js__WEBPACK_IMPORTED_MODULE_0__/* .__commonJS */ .P$)({
  "../../../node_modules/react-element-to-jsx-string/node_modules/react-is/index.js"(exports, module) {
    "use strict";
     true ? module.exports = require_react_is_production_min() : 0;
  }
});

// src/docs/lib/componentTypes.ts
var isMemo = (component) => component.$$typeof === /* @__PURE__ */ Symbol.for("react.memo"), isForwardRef = (component) => component.$$typeof === /* @__PURE__ */ Symbol.for("react.forward_ref");

// ../../../node_modules/is-plain-object/dist/is-plain-object.mjs
function isObject(o) {
  return Object.prototype.toString.call(o) === "[object Object]";
}
function isPlainObject(o) {
  var ctor, prot;
  return isObject(o) === !1 ? !1 : (ctor = o.constructor, ctor === void 0 ? !0 : (prot = ctor.prototype, !(isObject(prot) === !1 || prot.hasOwnProperty("isPrototypeOf") === !1)));
}

// ../../../node_modules/react-element-to-jsx-string/dist/esm/index.js
var import_pretty_print_object = (0,_chunk_UAWMPV5J_js__WEBPACK_IMPORTED_MODULE_0__/* .__toESM */ .f1)(require_dist()), import_react_is = (0,_chunk_UAWMPV5J_js__WEBPACK_IMPORTED_MODULE_0__/* .__toESM */ .f1)(require_react_is());


var spacer = (function(times, tabStop) {
  return times === 0 ? "" : new Array(times * tabStop).fill(" ").join("");
});
function _arrayLikeToArray(r, a) {
  (a == null || a > r.length) && (a = r.length);
  for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e];
  return n;
}
function _arrayWithoutHoles(r) {
  if (Array.isArray(r)) return _arrayLikeToArray(r);
}
function _defineProperty(e, r, t) {
  return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: !0,
    configurable: !0,
    writable: !0
  }) : e[r] = t, e;
}
function _iterableToArray(r) {
  if (typeof Symbol < "u" && r[Symbol.iterator] != null || r["@@iterator"] != null) return Array.from(r);
}
function _nonIterableSpread() {
  throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`);
}
function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function(r2) {
      return Object.getOwnPropertyDescriptor(e, r2).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread2(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = arguments[r] != null ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function(r2) {
      _defineProperty(e, r2, t[r2]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function(r2) {
      Object.defineProperty(e, r2, Object.getOwnPropertyDescriptor(t, r2));
    });
  }
  return e;
}
function _toConsumableArray(r) {
  return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread();
}
function _toPrimitive(t, r) {
  if (typeof t != "object" || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (e !== void 0) {
    var i = e.call(t, r || "default");
    if (typeof i != "object") return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (r === "string" ? String : Number)(t);
}
function _toPropertyKey(t) {
  var i = _toPrimitive(t, "string");
  return typeof i == "symbol" ? i : i + "";
}
function _typeof(o) {
  "@babel/helpers - typeof";
  return _typeof = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function(o2) {
    return typeof o2;
  } : function(o2) {
    return o2 && typeof Symbol == "function" && o2.constructor === Symbol && o2 !== Symbol.prototype ? "symbol" : typeof o2;
  }, _typeof(o);
}
function _unsupportedIterableToArray(r, a) {
  if (r) {
    if (typeof r == "string") return _arrayLikeToArray(r, a);
    var t = {}.toString.call(r).slice(8, -1);
    return t === "Object" && r.constructor && (t = r.constructor.name), t === "Map" || t === "Set" ? Array.from(r) : t === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0;
  }
}
function safeSortObject(value, seen) {
  if (value === null || _typeof(value) !== "object" || value instanceof Date || value instanceof RegExp)
    return value;
  if (react__WEBPACK_IMPORTED_MODULE_1__.isValidElement(value)) {
    var copyObj = _objectSpread2({}, value);
    return delete copyObj._owner, copyObj;
  }
  return seen.add(value), Array.isArray(value) ? value.map(function(v) {
    return safeSortObject(v, seen);
  }) : Object.keys(value).sort().reduce(function(result, key) {
    return key === "current" || seen.has(value[key]) ? result[key] = "[Circular]" : result[key] = safeSortObject(value[key], seen), result;
  }, {});
}
function sortObject(value) {
  return safeSortObject(value, /* @__PURE__ */ new WeakSet());
}
var createStringTreeNode = function(value) {
  return {
    type: "string",
    value
  };
}, createNumberTreeNode = function(value) {
  return {
    type: "number",
    value
  };
}, createReactElementTreeNode = function(displayName, props, defaultProps, childrens) {
  return {
    type: "ReactElement",
    displayName,
    props,
    defaultProps,
    childrens
  };
}, createReactFragmentTreeNode = function(key, childrens) {
  return {
    type: "ReactFragment",
    key,
    childrens
  };
}, supportFragment = !!react__WEBPACK_IMPORTED_MODULE_1__.Fragment, getFunctionTypeName = function(functionType) {
  return !functionType.name || functionType.name === "_default" ? "No Display Name" : functionType.name;
}, _getWrappedComponentDisplayName = function(Component) {
  switch (!0) {
    case !!Component.displayName:
      return Component.displayName;
    case Component.$$typeof === import_react_is.Memo:
      return _getWrappedComponentDisplayName(Component.type);
    case Component.$$typeof === import_react_is.ForwardRef:
      return _getWrappedComponentDisplayName(Component.render);
    default:
      return getFunctionTypeName(Component);
  }
}, getReactElementDisplayName = function(element) {
  switch (!0) {
    case typeof element.type == "string":
      return element.type;
    case typeof element.type == "function":
      return element.type.displayName ? element.type.displayName : getFunctionTypeName(element.type);
    case (0, import_react_is.isForwardRef)(element):
    case (0, import_react_is.isMemo)(element):
      return _getWrappedComponentDisplayName(element.type);
    case (0, import_react_is.isContextConsumer)(element):
      return "".concat(element.type._context.displayName || "Context", ".Consumer");
    case (0, import_react_is.isContextProvider)(element):
      return "".concat(element.type._context.displayName || "Context", ".Provider");
    case (0, import_react_is.isLazy)(element):
      return "Lazy";
    case (0, import_react_is.isProfiler)(element):
      return "Profiler";
    case (0, import_react_is.isStrictMode)(element):
      return "StrictMode";
    case (0, import_react_is.isSuspense)(element):
      return "Suspense";
    default:
      return "UnknownElementType";
  }
}, noChildren = function(propsValue, propName) {
  return propName !== "children";
}, onlyMeaningfulChildren = function(children) {
  return children !== !0 && children !== !1 && children !== null && children !== "";
}, filterProps = function(originalProps, cb) {
  var filteredProps = {};
  return Object.keys(originalProps).filter(function(key) {
    return cb(originalProps[key], key);
  }).forEach(function(key) {
    return filteredProps[key] = originalProps[key];
  }), filteredProps;
}, _parseReactElement = function(element, options) {
  var _options$displayName = options.displayName, displayNameFn = _options$displayName === void 0 ? getReactElementDisplayName : _options$displayName;
  if (typeof element == "string")
    return createStringTreeNode(element);
  if (typeof element == "number")
    return createNumberTreeNode(element);
  if (!react__WEBPACK_IMPORTED_MODULE_1__.isValidElement(element))
    throw new Error("react-element-to-jsx-string: Expected a React.Element, got `".concat(_typeof(element), "`"));
  var displayName = displayNameFn(element), props = filterProps(element.props, noChildren);
  element.ref !== null && (props.ref = element.ref);
  var key = element.key;
  typeof key == "string" && key.search(/^\./) && (props.key = key);
  var defaultProps = filterProps(element.type.defaultProps || {}, noChildren), childrens = react__WEBPACK_IMPORTED_MODULE_1__.Children.toArray(element.props.children).filter(onlyMeaningfulChildren).map(function(child) {
    return _parseReactElement(child, options);
  });
  return supportFragment && element.type === react__WEBPACK_IMPORTED_MODULE_1__.Fragment ? createReactFragmentTreeNode(key, childrens) : createReactElementTreeNode(displayName, props, defaultProps, childrens);
};
function noRefCheck() {
}
var inlineFunction = function(fn) {
  return fn.toString().split(`
`).map(function(line) {
    return line.trim();
  }).join("");
};
var defaultFunctionValue = inlineFunction, formatFunction = (function(fn, options) {
  var _options$functionValu = options.functionValue, functionValue = _options$functionValu === void 0 ? defaultFunctionValue : _options$functionValu, showFunctions = options.showFunctions;
  return functionValue(!showFunctions && functionValue === defaultFunctionValue ? noRefCheck : fn);
}), formatComplexDataStructure = (function(value, inline, lvl, options) {
  var normalizedValue = sortObject(value), stringifiedValue = (0, import_pretty_print_object.prettyPrint)(normalizedValue, {
    transform: function(currentObj, prop, originalResult) {
      var currentValue = currentObj[prop];
      return currentValue && (0,react__WEBPACK_IMPORTED_MODULE_1__.isValidElement)(currentValue) ? formatTreeNode(_parseReactElement(currentValue, options), !0, lvl, options) : typeof currentValue == "function" ? formatFunction(currentValue, options) : originalResult;
    }
  });
  return inline ? stringifiedValue.replace(/\s+/g, " ").replace(/{ /g, "{").replace(/ }/g, "}").replace(/\[ /g, "[").replace(/ ]/g, "]") : stringifiedValue.replace(/\t/g, spacer(1, options.tabStop)).replace(/\n([^$])/g, `
`.concat(spacer(lvl + 1, options.tabStop), "$1"));
}), escape$1 = function(s) {
  return s.replace(/"/g, "&quot;");
}, formatPropValue = function(propValue, inline, lvl, options) {
  if (typeof propValue == "number")
    return "{".concat(String(propValue), "}");
  if (typeof propValue == "string")
    return '"'.concat(escape$1(propValue), '"');
  if (_typeof(propValue) === "symbol") {
    var symbolDescription = propValue.valueOf().toString().replace(/Symbol\((.*)\)/, "$1");
    return symbolDescription ? "{Symbol('".concat(symbolDescription, "')}") : "{Symbol()}";
  }
  return typeof propValue == "function" ? "{".concat(formatFunction(propValue, options), "}") : (0,react__WEBPACK_IMPORTED_MODULE_1__.isValidElement)(propValue) ? "{".concat(formatTreeNode(_parseReactElement(propValue, options), !0, lvl, options), "}") : propValue instanceof Date ? isNaN(propValue.valueOf()) ? "{new Date(NaN)}" : '{new Date("'.concat(propValue.toISOString(), '")}') : isPlainObject(propValue) || Array.isArray(propValue) ? "{".concat(formatComplexDataStructure(propValue, inline, lvl, options), "}") : "{".concat(String(propValue), "}");
}, formatProp = (function(name, hasValue, value, hasDefaultValue, defaultValue, inline, lvl, options) {
  if (!hasValue && !hasDefaultValue)
    throw new Error('The prop "'.concat(name, '" has no value and no default: could not be formatted'));
  var usedValue = hasValue ? value : defaultValue, useBooleanShorthandSyntax = options.useBooleanShorthandSyntax, tabStop = options.tabStop, formattedPropValue = formatPropValue(usedValue, inline, lvl, options), attributeFormattedInline = " ", attributeFormattedMultiline = `
`.concat(spacer(lvl + 1, tabStop)), isMultilineAttribute = formattedPropValue.includes(`
`);
  return useBooleanShorthandSyntax && formattedPropValue === "{true}" ? (attributeFormattedInline += "".concat(name), attributeFormattedMultiline += "".concat(name)) : (attributeFormattedInline += "".concat(name, "=").concat(formattedPropValue), attributeFormattedMultiline += "".concat(name, "=").concat(formattedPropValue)), {
    attributeFormattedInline,
    attributeFormattedMultiline,
    isMultilineAttribute
  };
}), mergeSiblingPlainStringChildrenReducer = (function(previousNodes, currentNode) {
  var nodes = previousNodes.slice(0, previousNodes.length > 0 ? previousNodes.length - 1 : 0), previousNode = previousNodes[previousNodes.length - 1];
  return previousNode && (currentNode.type === "string" || currentNode.type === "number") && (previousNode.type === "string" || previousNode.type === "number") ? nodes.push(createStringTreeNode(String(previousNode.value) + String(currentNode.value))) : (previousNode && nodes.push(previousNode), nodes.push(currentNode)), nodes;
}), isKeyOrRefProps = function(propName) {
  return ["key", "ref"].includes(propName);
}, sortPropsByNames = (function(shouldSortUserProps) {
  return function(props) {
    var haveKeyProp = props.includes("key"), haveRefProp = props.includes("ref"), userPropsOnly = props.filter(function(oneProp) {
      return !isKeyOrRefProps(oneProp);
    }), sortedProps = _toConsumableArray(shouldSortUserProps ? userPropsOnly.sort() : userPropsOnly);
    return haveRefProp && sortedProps.unshift("ref"), haveKeyProp && sortedProps.unshift("key"), sortedProps;
  };
});
function createPropFilter(props, filter) {
  return Array.isArray(filter) ? function(key) {
    return filter.indexOf(key) === -1;
  } : function(key) {
    return filter(props[key], key);
  };
}
var compensateMultilineStringElementIndentation = function(element, formattedElement, inline, lvl, options) {
  var tabStop = options.tabStop;
  return element.type === "string" ? formattedElement.split(`
`).map(function(line, offset) {
    return offset === 0 ? line : "".concat(spacer(lvl, tabStop)).concat(line);
  }).join(`
`) : formattedElement;
}, formatOneChildren = function(inline, lvl, options) {
  return function(element) {
    return compensateMultilineStringElementIndentation(element, formatTreeNode(element, inline, lvl, options), inline, lvl, options);
  };
}, onlyPropsWithOriginalValue = function(defaultProps, props) {
  return function(propName) {
    var haveDefaultValue = Object.keys(defaultProps).includes(propName);
    return !haveDefaultValue || haveDefaultValue && defaultProps[propName] !== props[propName];
  };
}, isInlineAttributeTooLong = function(attributes, inlineAttributeString, lvl, tabStop, maxInlineAttributesLineLength) {
  return maxInlineAttributesLineLength ? spacer(lvl, tabStop).length + inlineAttributeString.length > maxInlineAttributesLineLength : attributes.length > 1;
}, shouldRenderMultilineAttr = function(attributes, inlineAttributeString, containsMultilineAttr, inline, lvl, tabStop, maxInlineAttributesLineLength) {
  return (isInlineAttributeTooLong(attributes, inlineAttributeString, lvl, tabStop, maxInlineAttributesLineLength) || containsMultilineAttr) && !inline;
}, formatReactElementNode = (function(node, inline, lvl, options) {
  var type = node.type, _node$displayName = node.displayName, displayName = _node$displayName === void 0 ? "" : _node$displayName, childrens = node.childrens, _node$props = node.props, props = _node$props === void 0 ? {} : _node$props, _node$defaultProps = node.defaultProps, defaultProps = _node$defaultProps === void 0 ? {} : _node$defaultProps;
  if (type !== "ReactElement")
    throw new Error('The "formatReactElementNode" function could only format node of type "ReactElement". Given:  '.concat(type));
  var filterProps3 = options.filterProps, maxInlineAttributesLineLength = options.maxInlineAttributesLineLength, showDefaultProps = options.showDefaultProps, sortProps = options.sortProps, tabStop = options.tabStop, out = "<".concat(displayName), outInlineAttr = out, outMultilineAttr = out, containsMultilineAttr = !1, visibleAttributeNames = [], propFilter = createPropFilter(props, filterProps3);
  Object.keys(props).filter(propFilter).filter(onlyPropsWithOriginalValue(defaultProps, props)).forEach(function(propName) {
    return visibleAttributeNames.push(propName);
  }), Object.keys(defaultProps).filter(propFilter).filter(function() {
    return showDefaultProps;
  }).filter(function(defaultPropName) {
    return !visibleAttributeNames.includes(defaultPropName);
  }).forEach(function(defaultPropName) {
    return visibleAttributeNames.push(defaultPropName);
  });
  var attributes = sortPropsByNames(sortProps)(visibleAttributeNames);
  if (attributes.forEach(function(attributeName) {
    var _formatProp = formatProp(attributeName, Object.keys(props).includes(attributeName), props[attributeName], Object.keys(defaultProps).includes(attributeName), defaultProps[attributeName], inline, lvl, options), attributeFormattedInline = _formatProp.attributeFormattedInline, attributeFormattedMultiline = _formatProp.attributeFormattedMultiline, isMultilineAttribute = _formatProp.isMultilineAttribute;
    isMultilineAttribute && (containsMultilineAttr = !0), outInlineAttr += attributeFormattedInline, outMultilineAttr += attributeFormattedMultiline;
  }), outMultilineAttr += `
`.concat(spacer(lvl, tabStop)), shouldRenderMultilineAttr(attributes, outInlineAttr, containsMultilineAttr, inline, lvl, tabStop, maxInlineAttributesLineLength) ? out = outMultilineAttr : out = outInlineAttr, childrens && childrens.length > 0) {
    var newLvl = lvl + 1;
    out += ">", inline || (out += `
`, out += spacer(newLvl, tabStop)), out += childrens.reduce(mergeSiblingPlainStringChildrenReducer, []).map(formatOneChildren(inline, newLvl, options)).join(inline ? "" : `
`.concat(spacer(newLvl, tabStop))), inline || (out += `
`, out += spacer(newLvl - 1, tabStop)), out += "</".concat(displayName, ">");
  } else
    isInlineAttributeTooLong(attributes, outInlineAttr, lvl, tabStop, maxInlineAttributesLineLength) || (out += " "), out += "/>";
  return out;
}), REACT_FRAGMENT_TAG_NAME_SHORT_SYNTAX = "", REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX = "React.Fragment", toReactElementTreeNode = function(displayName, key, childrens) {
  var props = {};
  return key && (props = {
    key
  }), {
    type: "ReactElement",
    displayName,
    props,
    defaultProps: {},
    childrens
  };
}, isKeyedFragment = function(_ref) {
  var key = _ref.key;
  return !!key;
}, hasNoChildren = function(_ref2) {
  var childrens = _ref2.childrens;
  return childrens.length === 0;
}, formatReactFragmentNode = (function(node, inline, lvl, options) {
  var type = node.type, key = node.key, childrens = node.childrens;
  if (type !== "ReactFragment")
    throw new Error('The "formatReactFragmentNode" function could only format node of type "ReactFragment". Given: '.concat(type));
  var useFragmentShortSyntax = options.useFragmentShortSyntax, displayName;
  return useFragmentShortSyntax ? hasNoChildren(node) || isKeyedFragment(node) ? displayName = REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX : displayName = REACT_FRAGMENT_TAG_NAME_SHORT_SYNTAX : displayName = REACT_FRAGMENT_TAG_NAME_EXPLICIT_SYNTAX, formatReactElementNode(toReactElementTreeNode(displayName, key, childrens), inline, lvl, options);
}), jsxStopChars = ["<", ">", "{", "}"], shouldBeEscaped = function(s) {
  return jsxStopChars.some(function(jsxStopChar) {
    return s.includes(jsxStopChar);
  });
}, escape2 = function(s) {
  return shouldBeEscaped(s) ? "{`".concat(s, "`}") : s;
}, preserveTrailingSpace = function(s) {
  var result = s;
  return result.endsWith(" ") && (result = result.replace(/^(.*?)(\s+)$/, "$1{'$2'}")), result.startsWith(" ") && (result = result.replace(/^(\s+)(.*)$/, "{'$1'}$2")), result;
}, formatTreeNode = (function(node, inline, lvl, options) {
  if (node.type === "number")
    return String(node.value);
  if (node.type === "string")
    return node.value ? "".concat(preserveTrailingSpace(escape2(String(node.value)))) : "";
  if (node.type === "ReactElement")
    return formatReactElementNode(node, inline, lvl, options);
  if (node.type === "ReactFragment")
    return formatReactFragmentNode(node, inline, lvl, options);
  throw new TypeError('Unknow format type "'.concat(node.type, '"'));
}), formatTree = (function(node, options) {
  return formatTreeNode(node, !1, 0, options);
}), reactElementToJsxString = function(element) {
  var _ref = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : {}, _ref$filterProps = _ref.filterProps, filterProps3 = _ref$filterProps === void 0 ? [] : _ref$filterProps, _ref$showDefaultProps = _ref.showDefaultProps, showDefaultProps = _ref$showDefaultProps === void 0 ? !0 : _ref$showDefaultProps, _ref$showFunctions = _ref.showFunctions, showFunctions = _ref$showFunctions === void 0 ? !1 : _ref$showFunctions, functionValue = _ref.functionValue, _ref$tabStop = _ref.tabStop, tabStop = _ref$tabStop === void 0 ? 2 : _ref$tabStop, _ref$useBooleanShorth = _ref.useBooleanShorthandSyntax, useBooleanShorthandSyntax = _ref$useBooleanShorth === void 0 ? !0 : _ref$useBooleanShorth, _ref$useFragmentShort = _ref.useFragmentShortSyntax, useFragmentShortSyntax = _ref$useFragmentShort === void 0 ? !0 : _ref$useFragmentShort, _ref$sortProps = _ref.sortProps, sortProps = _ref$sortProps === void 0 ? !0 : _ref$sortProps, maxInlineAttributesLineLength = _ref.maxInlineAttributesLineLength, displayName = _ref.displayName;
  if (!element)
    throw new Error("react-element-to-jsx-string: Expected a ReactElement");
  var options = {
    filterProps: filterProps3,
    showDefaultProps,
    showFunctions,
    functionValue,
    tabStop,
    useBooleanShorthandSyntax,
    useFragmentShortSyntax,
    sortProps,
    maxInlineAttributesLineLength,
    displayName
  };
  return formatTree(_parseReactElement(element, options), options);
};




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-DDIRQRCA.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t: () => (/* binding */ applyDecorators)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var storybook_preview_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("storybook/preview-api");
/* harmony import */ var storybook_preview_api__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(storybook_preview_api__WEBPACK_IMPORTED_MODULE_1__);
// src/applyDecorators.ts


var applyDecorators = (storyFn, decorators) => (0,storybook_preview_api__WEBPACK_IMPORTED_MODULE_1__.defaultDecorateStory)((context) => react__WEBPACK_IMPORTED_MODULE_0__.createElement(storyFn, context), decorators);




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   P$: () => (/* binding */ __commonJS),
/* harmony export */   VA: () => (/* binding */ __export),
/* harmony export */   f1: () => (/* binding */ __toESM)
/* harmony export */ });
var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __getProtoOf = Object.getPrototypeOf, __hasOwnProp = Object.prototype.hasOwnProperty;
var __commonJS = (cb, mod) => function() {
  try {
    return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
  } catch (e) {
    throw mod = 0, e;
  }
};
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: !0 });
}, __copyProps = (to, from, except, desc) => {
  if (from && typeof from == "object" || typeof from == "function")
    for (let key of __getOwnPropNames(from))
      !__hasOwnProp.call(to, key) && key !== except && __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  return to;
};
var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
  // If the importer is in node compatibility mode or this is not an ESM
  // file that has been converted to a CommonJS file using a Babel-
  // compatible transform (i.e. "__esModule" has not been set), then set
  // "default" to the CommonJS "module.exports" for node compatibility.
  isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: !0 }) : target,
  mod
));




/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/entry-preview-argtypes.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  argTypesEnhancers: () => (/* reexport */ argTypesEnhancers),
  parameters: () => (/* reexport */ parameters)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-B3FR4PBN.js
var chunk_B3FR4PBN = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-B3FR4PBN.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js
var chunk_UAWMPV5J = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-OA6XKWIN.js
var chunk_OA6XKWIN = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-OA6XKWIN.js");
;// ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-5GZAZJC7.js



// ../../../node_modules/estraverse/estraverse.js
var require_estraverse = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/estraverse/estraverse.js"(exports) {
    (function clone(exports2) {
      "use strict";
      var Syntax, VisitorOption, VisitorKeys, BREAK, SKIP, REMOVE;
      function deepCopy(obj) {
        var ret = {}, key, val;
        for (key in obj)
          obj.hasOwnProperty(key) && (val = obj[key], typeof val == "object" && val !== null ? ret[key] = deepCopy(val) : ret[key] = val);
        return ret;
      }
      function upperBound(array, func) {
        var diff, len, i, current;
        for (len = array.length, i = 0; len; )
          diff = len >>> 1, current = i + diff, func(array[current]) ? len = diff : (i = current + 1, len -= diff + 1);
        return i;
      }
      Syntax = {
        AssignmentExpression: "AssignmentExpression",
        AssignmentPattern: "AssignmentPattern",
        ArrayExpression: "ArrayExpression",
        ArrayPattern: "ArrayPattern",
        ArrowFunctionExpression: "ArrowFunctionExpression",
        AwaitExpression: "AwaitExpression",
        // CAUTION: It's deferred to ES7.
        BlockStatement: "BlockStatement",
        BinaryExpression: "BinaryExpression",
        BreakStatement: "BreakStatement",
        CallExpression: "CallExpression",
        CatchClause: "CatchClause",
        ChainExpression: "ChainExpression",
        ClassBody: "ClassBody",
        ClassDeclaration: "ClassDeclaration",
        ClassExpression: "ClassExpression",
        ComprehensionBlock: "ComprehensionBlock",
        // CAUTION: It's deferred to ES7.
        ComprehensionExpression: "ComprehensionExpression",
        // CAUTION: It's deferred to ES7.
        ConditionalExpression: "ConditionalExpression",
        ContinueStatement: "ContinueStatement",
        DebuggerStatement: "DebuggerStatement",
        DirectiveStatement: "DirectiveStatement",
        DoWhileStatement: "DoWhileStatement",
        EmptyStatement: "EmptyStatement",
        ExportAllDeclaration: "ExportAllDeclaration",
        ExportDefaultDeclaration: "ExportDefaultDeclaration",
        ExportNamedDeclaration: "ExportNamedDeclaration",
        ExportSpecifier: "ExportSpecifier",
        ExpressionStatement: "ExpressionStatement",
        ForStatement: "ForStatement",
        ForInStatement: "ForInStatement",
        ForOfStatement: "ForOfStatement",
        FunctionDeclaration: "FunctionDeclaration",
        FunctionExpression: "FunctionExpression",
        GeneratorExpression: "GeneratorExpression",
        // CAUTION: It's deferred to ES7.
        Identifier: "Identifier",
        IfStatement: "IfStatement",
        ImportExpression: "ImportExpression",
        ImportDeclaration: "ImportDeclaration",
        ImportDefaultSpecifier: "ImportDefaultSpecifier",
        ImportNamespaceSpecifier: "ImportNamespaceSpecifier",
        ImportSpecifier: "ImportSpecifier",
        Literal: "Literal",
        LabeledStatement: "LabeledStatement",
        LogicalExpression: "LogicalExpression",
        MemberExpression: "MemberExpression",
        MetaProperty: "MetaProperty",
        MethodDefinition: "MethodDefinition",
        ModuleSpecifier: "ModuleSpecifier",
        NewExpression: "NewExpression",
        ObjectExpression: "ObjectExpression",
        ObjectPattern: "ObjectPattern",
        PrivateIdentifier: "PrivateIdentifier",
        Program: "Program",
        Property: "Property",
        PropertyDefinition: "PropertyDefinition",
        RestElement: "RestElement",
        ReturnStatement: "ReturnStatement",
        SequenceExpression: "SequenceExpression",
        SpreadElement: "SpreadElement",
        Super: "Super",
        SwitchStatement: "SwitchStatement",
        SwitchCase: "SwitchCase",
        TaggedTemplateExpression: "TaggedTemplateExpression",
        TemplateElement: "TemplateElement",
        TemplateLiteral: "TemplateLiteral",
        ThisExpression: "ThisExpression",
        ThrowStatement: "ThrowStatement",
        TryStatement: "TryStatement",
        UnaryExpression: "UnaryExpression",
        UpdateExpression: "UpdateExpression",
        VariableDeclaration: "VariableDeclaration",
        VariableDeclarator: "VariableDeclarator",
        WhileStatement: "WhileStatement",
        WithStatement: "WithStatement",
        YieldExpression: "YieldExpression"
      }, VisitorKeys = {
        AssignmentExpression: ["left", "right"],
        AssignmentPattern: ["left", "right"],
        ArrayExpression: ["elements"],
        ArrayPattern: ["elements"],
        ArrowFunctionExpression: ["params", "body"],
        AwaitExpression: ["argument"],
        // CAUTION: It's deferred to ES7.
        BlockStatement: ["body"],
        BinaryExpression: ["left", "right"],
        BreakStatement: ["label"],
        CallExpression: ["callee", "arguments"],
        CatchClause: ["param", "body"],
        ChainExpression: ["expression"],
        ClassBody: ["body"],
        ClassDeclaration: ["id", "superClass", "body"],
        ClassExpression: ["id", "superClass", "body"],
        ComprehensionBlock: ["left", "right"],
        // CAUTION: It's deferred to ES7.
        ComprehensionExpression: ["blocks", "filter", "body"],
        // CAUTION: It's deferred to ES7.
        ConditionalExpression: ["test", "consequent", "alternate"],
        ContinueStatement: ["label"],
        DebuggerStatement: [],
        DirectiveStatement: [],
        DoWhileStatement: ["body", "test"],
        EmptyStatement: [],
        ExportAllDeclaration: ["source"],
        ExportDefaultDeclaration: ["declaration"],
        ExportNamedDeclaration: ["declaration", "specifiers", "source"],
        ExportSpecifier: ["exported", "local"],
        ExpressionStatement: ["expression"],
        ForStatement: ["init", "test", "update", "body"],
        ForInStatement: ["left", "right", "body"],
        ForOfStatement: ["left", "right", "body"],
        FunctionDeclaration: ["id", "params", "body"],
        FunctionExpression: ["id", "params", "body"],
        GeneratorExpression: ["blocks", "filter", "body"],
        // CAUTION: It's deferred to ES7.
        Identifier: [],
        IfStatement: ["test", "consequent", "alternate"],
        ImportExpression: ["source"],
        ImportDeclaration: ["specifiers", "source"],
        ImportDefaultSpecifier: ["local"],
        ImportNamespaceSpecifier: ["local"],
        ImportSpecifier: ["imported", "local"],
        Literal: [],
        LabeledStatement: ["label", "body"],
        LogicalExpression: ["left", "right"],
        MemberExpression: ["object", "property"],
        MetaProperty: ["meta", "property"],
        MethodDefinition: ["key", "value"],
        ModuleSpecifier: [],
        NewExpression: ["callee", "arguments"],
        ObjectExpression: ["properties"],
        ObjectPattern: ["properties"],
        PrivateIdentifier: [],
        Program: ["body"],
        Property: ["key", "value"],
        PropertyDefinition: ["key", "value"],
        RestElement: ["argument"],
        ReturnStatement: ["argument"],
        SequenceExpression: ["expressions"],
        SpreadElement: ["argument"],
        Super: [],
        SwitchStatement: ["discriminant", "cases"],
        SwitchCase: ["test", "consequent"],
        TaggedTemplateExpression: ["tag", "quasi"],
        TemplateElement: [],
        TemplateLiteral: ["quasis", "expressions"],
        ThisExpression: [],
        ThrowStatement: ["argument"],
        TryStatement: ["block", "handler", "finalizer"],
        UnaryExpression: ["argument"],
        UpdateExpression: ["argument"],
        VariableDeclaration: ["declarations"],
        VariableDeclarator: ["id", "init"],
        WhileStatement: ["test", "body"],
        WithStatement: ["object", "body"],
        YieldExpression: ["argument"]
      }, BREAK = {}, SKIP = {}, REMOVE = {}, VisitorOption = {
        Break: BREAK,
        Skip: SKIP,
        Remove: REMOVE
      };
      function Reference(parent, key) {
        this.parent = parent, this.key = key;
      }
      Reference.prototype.replace = function(node) {
        this.parent[this.key] = node;
      }, Reference.prototype.remove = function() {
        return Array.isArray(this.parent) ? (this.parent.splice(this.key, 1), !0) : (this.replace(null), !1);
      };
      function Element(node, path, wrap, ref) {
        this.node = node, this.path = path, this.wrap = wrap, this.ref = ref;
      }
      function Controller() {
      }
      Controller.prototype.path = function() {
        var i, iz, j, jz, result, element;
        function addToPath(result2, path2) {
          if (Array.isArray(path2))
            for (j = 0, jz = path2.length; j < jz; ++j)
              result2.push(path2[j]);
          else
            result2.push(path2);
        }
        if (!this.__current.path)
          return null;
        for (result = [], i = 2, iz = this.__leavelist.length; i < iz; ++i)
          element = this.__leavelist[i], addToPath(result, element.path);
        return addToPath(result, this.__current.path), result;
      }, Controller.prototype.type = function() {
        var node = this.current();
        return node.type || this.__current.wrap;
      }, Controller.prototype.parents = function() {
        var i, iz, result;
        for (result = [], i = 1, iz = this.__leavelist.length; i < iz; ++i)
          result.push(this.__leavelist[i].node);
        return result;
      }, Controller.prototype.current = function() {
        return this.__current.node;
      }, Controller.prototype.__execute = function(callback, element) {
        var previous, result;
        return result = void 0, previous = this.__current, this.__current = element, this.__state = null, callback && (result = callback.call(this, element.node, this.__leavelist[this.__leavelist.length - 1].node)), this.__current = previous, result;
      }, Controller.prototype.notify = function(flag) {
        this.__state = flag;
      }, Controller.prototype.skip = function() {
        this.notify(SKIP);
      }, Controller.prototype.break = function() {
        this.notify(BREAK);
      }, Controller.prototype.remove = function() {
        this.notify(REMOVE);
      }, Controller.prototype.__initialize = function(root, visitor) {
        this.visitor = visitor, this.root = root, this.__worklist = [], this.__leavelist = [], this.__current = null, this.__state = null, this.__fallback = null, visitor.fallback === "iteration" ? this.__fallback = Object.keys : typeof visitor.fallback == "function" && (this.__fallback = visitor.fallback), this.__keys = VisitorKeys, visitor.keys && (this.__keys = Object.assign(Object.create(this.__keys), visitor.keys));
      };
      function isNode2(node) {
        return node == null ? !1 : typeof node == "object" && typeof node.type == "string";
      }
      function isProperty(nodeType, key) {
        return (nodeType === Syntax.ObjectExpression || nodeType === Syntax.ObjectPattern) && key === "properties";
      }
      function candidateExistsInLeaveList(leavelist, candidate) {
        for (var i = leavelist.length - 1; i >= 0; --i)
          if (leavelist[i].node === candidate)
            return !0;
        return !1;
      }
      Controller.prototype.traverse = function(root, visitor) {
        var worklist, leavelist, element, node, nodeType, ret, key, current, current2, candidates, candidate, sentinel;
        for (this.__initialize(root, visitor), sentinel = {}, worklist = this.__worklist, leavelist = this.__leavelist, worklist.push(new Element(root, null, null, null)), leavelist.push(new Element(null, null, null, null)); worklist.length; ) {
          if (element = worklist.pop(), element === sentinel) {
            if (element = leavelist.pop(), ret = this.__execute(visitor.leave, element), this.__state === BREAK || ret === BREAK)
              return;
            continue;
          }
          if (element.node) {
            if (ret = this.__execute(visitor.enter, element), this.__state === BREAK || ret === BREAK)
              return;
            if (worklist.push(sentinel), leavelist.push(element), this.__state === SKIP || ret === SKIP)
              continue;
            if (node = element.node, nodeType = node.type || element.wrap, candidates = this.__keys[nodeType], !candidates)
              if (this.__fallback)
                candidates = this.__fallback(node);
              else
                throw new Error("Unknown node type " + nodeType + ".");
            for (current = candidates.length; (current -= 1) >= 0; )
              if (key = candidates[current], candidate = node[key], !!candidate) {
                if (Array.isArray(candidate)) {
                  for (current2 = candidate.length; (current2 -= 1) >= 0; )
                    if (candidate[current2] && !candidateExistsInLeaveList(leavelist, candidate[current2])) {
                      if (isProperty(nodeType, candidates[current]))
                        element = new Element(candidate[current2], [key, current2], "Property", null);
                      else if (isNode2(candidate[current2]))
                        element = new Element(candidate[current2], [key, current2], null, null);
                      else
                        continue;
                      worklist.push(element);
                    }
                } else if (isNode2(candidate)) {
                  if (candidateExistsInLeaveList(leavelist, candidate))
                    continue;
                  worklist.push(new Element(candidate, key, null, null));
                }
              }
          }
        }
      }, Controller.prototype.replace = function(root, visitor) {
        var worklist, leavelist, node, nodeType, target, element, current, current2, candidates, candidate, sentinel, outer, key;
        function removeElem(element2) {
          var i, key2, nextElem, parent;
          if (element2.ref.remove()) {
            for (key2 = element2.ref.key, parent = element2.ref.parent, i = worklist.length; i--; )
              if (nextElem = worklist[i], nextElem.ref && nextElem.ref.parent === parent) {
                if (nextElem.ref.key < key2)
                  break;
                --nextElem.ref.key;
              }
          }
        }
        for (this.__initialize(root, visitor), sentinel = {}, worklist = this.__worklist, leavelist = this.__leavelist, outer = {
          root
        }, element = new Element(root, null, null, new Reference(outer, "root")), worklist.push(element), leavelist.push(element); worklist.length; ) {
          if (element = worklist.pop(), element === sentinel) {
            if (element = leavelist.pop(), target = this.__execute(visitor.leave, element), target !== void 0 && target !== BREAK && target !== SKIP && target !== REMOVE && element.ref.replace(target), (this.__state === REMOVE || target === REMOVE) && removeElem(element), this.__state === BREAK || target === BREAK)
              return outer.root;
            continue;
          }
          if (target = this.__execute(visitor.enter, element), target !== void 0 && target !== BREAK && target !== SKIP && target !== REMOVE && (element.ref.replace(target), element.node = target), (this.__state === REMOVE || target === REMOVE) && (removeElem(element), element.node = null), this.__state === BREAK || target === BREAK)
            return outer.root;
          if (node = element.node, !!node && (worklist.push(sentinel), leavelist.push(element), !(this.__state === SKIP || target === SKIP))) {
            if (nodeType = node.type || element.wrap, candidates = this.__keys[nodeType], !candidates)
              if (this.__fallback)
                candidates = this.__fallback(node);
              else
                throw new Error("Unknown node type " + nodeType + ".");
            for (current = candidates.length; (current -= 1) >= 0; )
              if (key = candidates[current], candidate = node[key], !!candidate)
                if (Array.isArray(candidate)) {
                  for (current2 = candidate.length; (current2 -= 1) >= 0; )
                    if (candidate[current2]) {
                      if (isProperty(nodeType, candidates[current]))
                        element = new Element(candidate[current2], [key, current2], "Property", new Reference(candidate, current2));
                      else if (isNode2(candidate[current2]))
                        element = new Element(candidate[current2], [key, current2], null, new Reference(candidate, current2));
                      else
                        continue;
                      worklist.push(element);
                    }
                } else isNode2(candidate) && worklist.push(new Element(candidate, key, null, new Reference(node, key)));
          }
        }
        return outer.root;
      };
      function traverse(root, visitor) {
        var controller = new Controller();
        return controller.traverse(root, visitor);
      }
      function replace(root, visitor) {
        var controller = new Controller();
        return controller.replace(root, visitor);
      }
      function extendCommentRange(comment, tokens) {
        var target;
        return target = upperBound(tokens, function(token) {
          return token.range[0] > comment.range[0];
        }), comment.extendedRange = [comment.range[0], comment.range[1]], target !== tokens.length && (comment.extendedRange[1] = tokens[target].range[0]), target -= 1, target >= 0 && (comment.extendedRange[0] = tokens[target].range[1]), comment;
      }
      function attachComments(tree, providedComments, tokens) {
        var comments = [], comment, len, i, cursor;
        if (!tree.range)
          throw new Error("attachComments needs range information");
        if (!tokens.length) {
          if (providedComments.length) {
            for (i = 0, len = providedComments.length; i < len; i += 1)
              comment = deepCopy(providedComments[i]), comment.extendedRange = [0, tree.range[0]], comments.push(comment);
            tree.leadingComments = comments;
          }
          return tree;
        }
        for (i = 0, len = providedComments.length; i < len; i += 1)
          comments.push(extendCommentRange(deepCopy(providedComments[i]), tokens));
        return cursor = 0, traverse(tree, {
          enter: function(node) {
            for (var comment2; cursor < comments.length && (comment2 = comments[cursor], !(comment2.extendedRange[1] > node.range[0])); )
              comment2.extendedRange[1] === node.range[0] ? (node.leadingComments || (node.leadingComments = []), node.leadingComments.push(comment2), comments.splice(cursor, 1)) : cursor += 1;
            if (cursor === comments.length)
              return VisitorOption.Break;
            if (comments[cursor].extendedRange[0] > node.range[1])
              return VisitorOption.Skip;
          }
        }), cursor = 0, traverse(tree, {
          leave: function(node) {
            for (var comment2; cursor < comments.length && (comment2 = comments[cursor], !(node.range[1] < comment2.extendedRange[0])); )
              node.range[1] === comment2.extendedRange[0] ? (node.trailingComments || (node.trailingComments = []), node.trailingComments.push(comment2), comments.splice(cursor, 1)) : cursor += 1;
            if (cursor === comments.length)
              return VisitorOption.Break;
            if (comments[cursor].extendedRange[0] > node.range[1])
              return VisitorOption.Skip;
          }
        }), tree;
      }
      return exports2.Syntax = Syntax, exports2.traverse = traverse, exports2.replace = replace, exports2.attachComments = attachComments, exports2.VisitorKeys = VisitorKeys, exports2.VisitorOption = VisitorOption, exports2.Controller = Controller, exports2.cloneEnvironment = function() {
        return clone({});
      }, exports2;
    })(exports);
  }
});

// ../../../node_modules/esutils/lib/ast.js
var require_ast = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/esutils/lib/ast.js"(exports, module) {
    (function() {
      "use strict";
      function isExpression(node) {
        if (node == null)
          return !1;
        switch (node.type) {
          case "ArrayExpression":
          case "AssignmentExpression":
          case "BinaryExpression":
          case "CallExpression":
          case "ConditionalExpression":
          case "FunctionExpression":
          case "Identifier":
          case "Literal":
          case "LogicalExpression":
          case "MemberExpression":
          case "NewExpression":
          case "ObjectExpression":
          case "SequenceExpression":
          case "ThisExpression":
          case "UnaryExpression":
          case "UpdateExpression":
            return !0;
        }
        return !1;
      }
      function isIterationStatement(node) {
        if (node == null)
          return !1;
        switch (node.type) {
          case "DoWhileStatement":
          case "ForInStatement":
          case "ForStatement":
          case "WhileStatement":
            return !0;
        }
        return !1;
      }
      function isStatement(node) {
        if (node == null)
          return !1;
        switch (node.type) {
          case "BlockStatement":
          case "BreakStatement":
          case "ContinueStatement":
          case "DebuggerStatement":
          case "DoWhileStatement":
          case "EmptyStatement":
          case "ExpressionStatement":
          case "ForInStatement":
          case "ForStatement":
          case "IfStatement":
          case "LabeledStatement":
          case "ReturnStatement":
          case "SwitchStatement":
          case "ThrowStatement":
          case "TryStatement":
          case "VariableDeclaration":
          case "WhileStatement":
          case "WithStatement":
            return !0;
        }
        return !1;
      }
      function isSourceElement(node) {
        return isStatement(node) || node != null && node.type === "FunctionDeclaration";
      }
      function trailingStatement(node) {
        switch (node.type) {
          case "IfStatement":
            return node.alternate != null ? node.alternate : node.consequent;
          case "LabeledStatement":
          case "ForStatement":
          case "ForInStatement":
          case "WhileStatement":
          case "WithStatement":
            return node.body;
        }
        return null;
      }
      function isProblematicIfStatement(node) {
        var current;
        if (node.type !== "IfStatement" || node.alternate == null)
          return !1;
        current = node.consequent;
        do {
          if (current.type === "IfStatement" && current.alternate == null)
            return !0;
          current = trailingStatement(current);
        } while (current);
        return !1;
      }
      module.exports = {
        isExpression,
        isStatement,
        isIterationStatement,
        isSourceElement,
        isProblematicIfStatement,
        trailingStatement
      };
    })();
  }
});

// ../../../node_modules/esutils/lib/code.js
var require_code = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/esutils/lib/code.js"(exports, module) {
    (function() {
      "use strict";
      var ES6Regex, ES5Regex, NON_ASCII_WHITESPACES, IDENTIFIER_START, IDENTIFIER_PART, ch;
      ES5Regex = {
        // ECMAScript 5.1/Unicode v9.0.0 NonAsciiIdentifierStart:
        NonAsciiIdentifierStart: /[\xAA\xB5\xBA\xC0-\xD6\xD8-\xF6\xF8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0370-\u0374\u0376\u0377\u037A-\u037D\u037F\u0386\u0388-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u048A-\u052F\u0531-\u0556\u0559\u0561-\u0587\u05D0-\u05EA\u05F0-\u05F2\u0620-\u064A\u066E\u066F\u0671-\u06D3\u06D5\u06E5\u06E6\u06EE\u06EF\u06FA-\u06FC\u06FF\u0710\u0712-\u072F\u074D-\u07A5\u07B1\u07CA-\u07EA\u07F4\u07F5\u07FA\u0800-\u0815\u081A\u0824\u0828\u0840-\u0858\u08A0-\u08B4\u08B6-\u08BD\u0904-\u0939\u093D\u0950\u0958-\u0961\u0971-\u0980\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BD\u09CE\u09DC\u09DD\u09DF-\u09E1\u09F0\u09F1\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A59-\u0A5C\u0A5E\u0A72-\u0A74\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABD\u0AD0\u0AE0\u0AE1\u0AF9\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3D\u0B5C\u0B5D\u0B5F-\u0B61\u0B71\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BD0\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C39\u0C3D\u0C58-\u0C5A\u0C60\u0C61\u0C80\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBD\u0CDE\u0CE0\u0CE1\u0CF1\u0CF2\u0D05-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D\u0D4E\u0D54-\u0D56\u0D5F-\u0D61\u0D7A-\u0D7F\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0E01-\u0E30\u0E32\u0E33\u0E40-\u0E46\u0E81\u0E82\u0E84\u0E87\u0E88\u0E8A\u0E8D\u0E94-\u0E97\u0E99-\u0E9F\u0EA1-\u0EA3\u0EA5\u0EA7\u0EAA\u0EAB\u0EAD-\u0EB0\u0EB2\u0EB3\u0EBD\u0EC0-\u0EC4\u0EC6\u0EDC-\u0EDF\u0F00\u0F40-\u0F47\u0F49-\u0F6C\u0F88-\u0F8C\u1000-\u102A\u103F\u1050-\u1055\u105A-\u105D\u1061\u1065\u1066\u106E-\u1070\u1075-\u1081\u108E\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u1380-\u138F\u13A0-\u13F5\u13F8-\u13FD\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u16EE-\u16F8\u1700-\u170C\u170E-\u1711\u1720-\u1731\u1740-\u1751\u1760-\u176C\u176E-\u1770\u1780-\u17B3\u17D7\u17DC\u1820-\u1877\u1880-\u1884\u1887-\u18A8\u18AA\u18B0-\u18F5\u1900-\u191E\u1950-\u196D\u1970-\u1974\u1980-\u19AB\u19B0-\u19C9\u1A00-\u1A16\u1A20-\u1A54\u1AA7\u1B05-\u1B33\u1B45-\u1B4B\u1B83-\u1BA0\u1BAE\u1BAF\u1BBA-\u1BE5\u1C00-\u1C23\u1C4D-\u1C4F\u1C5A-\u1C7D\u1C80-\u1C88\u1CE9-\u1CEC\u1CEE-\u1CF1\u1CF5\u1CF6\u1D00-\u1DBF\u1E00-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u2071\u207F\u2090-\u209C\u2102\u2107\u210A-\u2113\u2115\u2119-\u211D\u2124\u2126\u2128\u212A-\u212D\u212F-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2160-\u2188\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CEE\u2CF2\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D80-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u2E2F\u3005-\u3007\u3021-\u3029\u3031-\u3035\u3038-\u303C\u3041-\u3096\u309D-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312D\u3131-\u318E\u31A0-\u31BA\u31F0-\u31FF\u3400-\u4DB5\u4E00-\u9FD5\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA61F\uA62A\uA62B\uA640-\uA66E\uA67F-\uA69D\uA6A0-\uA6EF\uA717-\uA71F\uA722-\uA788\uA78B-\uA7AE\uA7B0-\uA7B7\uA7F7-\uA801\uA803-\uA805\uA807-\uA80A\uA80C-\uA822\uA840-\uA873\uA882-\uA8B3\uA8F2-\uA8F7\uA8FB\uA8FD\uA90A-\uA925\uA930-\uA946\uA960-\uA97C\uA984-\uA9B2\uA9CF\uA9E0-\uA9E4\uA9E6-\uA9EF\uA9FA-\uA9FE\uAA00-\uAA28\uAA40-\uAA42\uAA44-\uAA4B\uAA60-\uAA76\uAA7A\uAA7E-\uAAAF\uAAB1\uAAB5\uAAB6\uAAB9-\uAABD\uAAC0\uAAC2\uAADB-\uAADD\uAAE0-\uAAEA\uAAF2-\uAAF4\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uAB30-\uAB5A\uAB5C-\uAB65\uAB70-\uABE2\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D\uFB1F-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE70-\uFE74\uFE76-\uFEFC\uFF21-\uFF3A\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC]/,
        // ECMAScript 5.1/Unicode v9.0.0 NonAsciiIdentifierPart:
        NonAsciiIdentifierPart: /[\xAA\xB5\xBA\xC0-\xD6\xD8-\xF6\xF8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0300-\u0374\u0376\u0377\u037A-\u037D\u037F\u0386\u0388-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u0483-\u0487\u048A-\u052F\u0531-\u0556\u0559\u0561-\u0587\u0591-\u05BD\u05BF\u05C1\u05C2\u05C4\u05C5\u05C7\u05D0-\u05EA\u05F0-\u05F2\u0610-\u061A\u0620-\u0669\u066E-\u06D3\u06D5-\u06DC\u06DF-\u06E8\u06EA-\u06FC\u06FF\u0710-\u074A\u074D-\u07B1\u07C0-\u07F5\u07FA\u0800-\u082D\u0840-\u085B\u08A0-\u08B4\u08B6-\u08BD\u08D4-\u08E1\u08E3-\u0963\u0966-\u096F\u0971-\u0983\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BC-\u09C4\u09C7\u09C8\u09CB-\u09CE\u09D7\u09DC\u09DD\u09DF-\u09E3\u09E6-\u09F1\u0A01-\u0A03\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A3C\u0A3E-\u0A42\u0A47\u0A48\u0A4B-\u0A4D\u0A51\u0A59-\u0A5C\u0A5E\u0A66-\u0A75\u0A81-\u0A83\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABC-\u0AC5\u0AC7-\u0AC9\u0ACB-\u0ACD\u0AD0\u0AE0-\u0AE3\u0AE6-\u0AEF\u0AF9\u0B01-\u0B03\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3C-\u0B44\u0B47\u0B48\u0B4B-\u0B4D\u0B56\u0B57\u0B5C\u0B5D\u0B5F-\u0B63\u0B66-\u0B6F\u0B71\u0B82\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BBE-\u0BC2\u0BC6-\u0BC8\u0BCA-\u0BCD\u0BD0\u0BD7\u0BE6-\u0BEF\u0C00-\u0C03\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C39\u0C3D-\u0C44\u0C46-\u0C48\u0C4A-\u0C4D\u0C55\u0C56\u0C58-\u0C5A\u0C60-\u0C63\u0C66-\u0C6F\u0C80-\u0C83\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBC-\u0CC4\u0CC6-\u0CC8\u0CCA-\u0CCD\u0CD5\u0CD6\u0CDE\u0CE0-\u0CE3\u0CE6-\u0CEF\u0CF1\u0CF2\u0D01-\u0D03\u0D05-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D-\u0D44\u0D46-\u0D48\u0D4A-\u0D4E\u0D54-\u0D57\u0D5F-\u0D63\u0D66-\u0D6F\u0D7A-\u0D7F\u0D82\u0D83\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0DCA\u0DCF-\u0DD4\u0DD6\u0DD8-\u0DDF\u0DE6-\u0DEF\u0DF2\u0DF3\u0E01-\u0E3A\u0E40-\u0E4E\u0E50-\u0E59\u0E81\u0E82\u0E84\u0E87\u0E88\u0E8A\u0E8D\u0E94-\u0E97\u0E99-\u0E9F\u0EA1-\u0EA3\u0EA5\u0EA7\u0EAA\u0EAB\u0EAD-\u0EB9\u0EBB-\u0EBD\u0EC0-\u0EC4\u0EC6\u0EC8-\u0ECD\u0ED0-\u0ED9\u0EDC-\u0EDF\u0F00\u0F18\u0F19\u0F20-\u0F29\u0F35\u0F37\u0F39\u0F3E-\u0F47\u0F49-\u0F6C\u0F71-\u0F84\u0F86-\u0F97\u0F99-\u0FBC\u0FC6\u1000-\u1049\u1050-\u109D\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u135D-\u135F\u1380-\u138F\u13A0-\u13F5\u13F8-\u13FD\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u16EE-\u16F8\u1700-\u170C\u170E-\u1714\u1720-\u1734\u1740-\u1753\u1760-\u176C\u176E-\u1770\u1772\u1773\u1780-\u17D3\u17D7\u17DC\u17DD\u17E0-\u17E9\u180B-\u180D\u1810-\u1819\u1820-\u1877\u1880-\u18AA\u18B0-\u18F5\u1900-\u191E\u1920-\u192B\u1930-\u193B\u1946-\u196D\u1970-\u1974\u1980-\u19AB\u19B0-\u19C9\u19D0-\u19D9\u1A00-\u1A1B\u1A20-\u1A5E\u1A60-\u1A7C\u1A7F-\u1A89\u1A90-\u1A99\u1AA7\u1AB0-\u1ABD\u1B00-\u1B4B\u1B50-\u1B59\u1B6B-\u1B73\u1B80-\u1BF3\u1C00-\u1C37\u1C40-\u1C49\u1C4D-\u1C7D\u1C80-\u1C88\u1CD0-\u1CD2\u1CD4-\u1CF6\u1CF8\u1CF9\u1D00-\u1DF5\u1DFB-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u200C\u200D\u203F\u2040\u2054\u2071\u207F\u2090-\u209C\u20D0-\u20DC\u20E1\u20E5-\u20F0\u2102\u2107\u210A-\u2113\u2115\u2119-\u211D\u2124\u2126\u2128\u212A-\u212D\u212F-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2160-\u2188\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D7F-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u2DE0-\u2DFF\u2E2F\u3005-\u3007\u3021-\u302F\u3031-\u3035\u3038-\u303C\u3041-\u3096\u3099\u309A\u309D-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312D\u3131-\u318E\u31A0-\u31BA\u31F0-\u31FF\u3400-\u4DB5\u4E00-\u9FD5\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA62B\uA640-\uA66F\uA674-\uA67D\uA67F-\uA6F1\uA717-\uA71F\uA722-\uA788\uA78B-\uA7AE\uA7B0-\uA7B7\uA7F7-\uA827\uA840-\uA873\uA880-\uA8C5\uA8D0-\uA8D9\uA8E0-\uA8F7\uA8FB\uA8FD\uA900-\uA92D\uA930-\uA953\uA960-\uA97C\uA980-\uA9C0\uA9CF-\uA9D9\uA9E0-\uA9FE\uAA00-\uAA36\uAA40-\uAA4D\uAA50-\uAA59\uAA60-\uAA76\uAA7A-\uAAC2\uAADB-\uAADD\uAAE0-\uAAEF\uAAF2-\uAAF6\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uAB30-\uAB5A\uAB5C-\uAB65\uAB70-\uABEA\uABEC\uABED\uABF0-\uABF9\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE00-\uFE0F\uFE20-\uFE2F\uFE33\uFE34\uFE4D-\uFE4F\uFE70-\uFE74\uFE76-\uFEFC\uFF10-\uFF19\uFF21-\uFF3A\uFF3F\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC]/
      }, ES6Regex = {
        // ECMAScript 6/Unicode v9.0.0 NonAsciiIdentifierStart:
        NonAsciiIdentifierStart: /[\xAA\xB5\xBA\xC0-\xD6\xD8-\xF6\xF8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0370-\u0374\u0376\u0377\u037A-\u037D\u037F\u0386\u0388-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u048A-\u052F\u0531-\u0556\u0559\u0561-\u0587\u05D0-\u05EA\u05F0-\u05F2\u0620-\u064A\u066E\u066F\u0671-\u06D3\u06D5\u06E5\u06E6\u06EE\u06EF\u06FA-\u06FC\u06FF\u0710\u0712-\u072F\u074D-\u07A5\u07B1\u07CA-\u07EA\u07F4\u07F5\u07FA\u0800-\u0815\u081A\u0824\u0828\u0840-\u0858\u08A0-\u08B4\u08B6-\u08BD\u0904-\u0939\u093D\u0950\u0958-\u0961\u0971-\u0980\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BD\u09CE\u09DC\u09DD\u09DF-\u09E1\u09F0\u09F1\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A59-\u0A5C\u0A5E\u0A72-\u0A74\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABD\u0AD0\u0AE0\u0AE1\u0AF9\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3D\u0B5C\u0B5D\u0B5F-\u0B61\u0B71\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BD0\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C39\u0C3D\u0C58-\u0C5A\u0C60\u0C61\u0C80\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBD\u0CDE\u0CE0\u0CE1\u0CF1\u0CF2\u0D05-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D\u0D4E\u0D54-\u0D56\u0D5F-\u0D61\u0D7A-\u0D7F\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0E01-\u0E30\u0E32\u0E33\u0E40-\u0E46\u0E81\u0E82\u0E84\u0E87\u0E88\u0E8A\u0E8D\u0E94-\u0E97\u0E99-\u0E9F\u0EA1-\u0EA3\u0EA5\u0EA7\u0EAA\u0EAB\u0EAD-\u0EB0\u0EB2\u0EB3\u0EBD\u0EC0-\u0EC4\u0EC6\u0EDC-\u0EDF\u0F00\u0F40-\u0F47\u0F49-\u0F6C\u0F88-\u0F8C\u1000-\u102A\u103F\u1050-\u1055\u105A-\u105D\u1061\u1065\u1066\u106E-\u1070\u1075-\u1081\u108E\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u1380-\u138F\u13A0-\u13F5\u13F8-\u13FD\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u16EE-\u16F8\u1700-\u170C\u170E-\u1711\u1720-\u1731\u1740-\u1751\u1760-\u176C\u176E-\u1770\u1780-\u17B3\u17D7\u17DC\u1820-\u1877\u1880-\u18A8\u18AA\u18B0-\u18F5\u1900-\u191E\u1950-\u196D\u1970-\u1974\u1980-\u19AB\u19B0-\u19C9\u1A00-\u1A16\u1A20-\u1A54\u1AA7\u1B05-\u1B33\u1B45-\u1B4B\u1B83-\u1BA0\u1BAE\u1BAF\u1BBA-\u1BE5\u1C00-\u1C23\u1C4D-\u1C4F\u1C5A-\u1C7D\u1C80-\u1C88\u1CE9-\u1CEC\u1CEE-\u1CF1\u1CF5\u1CF6\u1D00-\u1DBF\u1E00-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u2071\u207F\u2090-\u209C\u2102\u2107\u210A-\u2113\u2115\u2118-\u211D\u2124\u2126\u2128\u212A-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2160-\u2188\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CEE\u2CF2\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D80-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u3005-\u3007\u3021-\u3029\u3031-\u3035\u3038-\u303C\u3041-\u3096\u309B-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312D\u3131-\u318E\u31A0-\u31BA\u31F0-\u31FF\u3400-\u4DB5\u4E00-\u9FD5\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA61F\uA62A\uA62B\uA640-\uA66E\uA67F-\uA69D\uA6A0-\uA6EF\uA717-\uA71F\uA722-\uA788\uA78B-\uA7AE\uA7B0-\uA7B7\uA7F7-\uA801\uA803-\uA805\uA807-\uA80A\uA80C-\uA822\uA840-\uA873\uA882-\uA8B3\uA8F2-\uA8F7\uA8FB\uA8FD\uA90A-\uA925\uA930-\uA946\uA960-\uA97C\uA984-\uA9B2\uA9CF\uA9E0-\uA9E4\uA9E6-\uA9EF\uA9FA-\uA9FE\uAA00-\uAA28\uAA40-\uAA42\uAA44-\uAA4B\uAA60-\uAA76\uAA7A\uAA7E-\uAAAF\uAAB1\uAAB5\uAAB6\uAAB9-\uAABD\uAAC0\uAAC2\uAADB-\uAADD\uAAE0-\uAAEA\uAAF2-\uAAF4\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uAB30-\uAB5A\uAB5C-\uAB65\uAB70-\uABE2\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D\uFB1F-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE70-\uFE74\uFE76-\uFEFC\uFF21-\uFF3A\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC]|\uD800[\uDC00-\uDC0B\uDC0D-\uDC26\uDC28-\uDC3A\uDC3C\uDC3D\uDC3F-\uDC4D\uDC50-\uDC5D\uDC80-\uDCFA\uDD40-\uDD74\uDE80-\uDE9C\uDEA0-\uDED0\uDF00-\uDF1F\uDF30-\uDF4A\uDF50-\uDF75\uDF80-\uDF9D\uDFA0-\uDFC3\uDFC8-\uDFCF\uDFD1-\uDFD5]|\uD801[\uDC00-\uDC9D\uDCB0-\uDCD3\uDCD8-\uDCFB\uDD00-\uDD27\uDD30-\uDD63\uDE00-\uDF36\uDF40-\uDF55\uDF60-\uDF67]|\uD802[\uDC00-\uDC05\uDC08\uDC0A-\uDC35\uDC37\uDC38\uDC3C\uDC3F-\uDC55\uDC60-\uDC76\uDC80-\uDC9E\uDCE0-\uDCF2\uDCF4\uDCF5\uDD00-\uDD15\uDD20-\uDD39\uDD80-\uDDB7\uDDBE\uDDBF\uDE00\uDE10-\uDE13\uDE15-\uDE17\uDE19-\uDE33\uDE60-\uDE7C\uDE80-\uDE9C\uDEC0-\uDEC7\uDEC9-\uDEE4\uDF00-\uDF35\uDF40-\uDF55\uDF60-\uDF72\uDF80-\uDF91]|\uD803[\uDC00-\uDC48\uDC80-\uDCB2\uDCC0-\uDCF2]|\uD804[\uDC03-\uDC37\uDC83-\uDCAF\uDCD0-\uDCE8\uDD03-\uDD26\uDD50-\uDD72\uDD76\uDD83-\uDDB2\uDDC1-\uDDC4\uDDDA\uDDDC\uDE00-\uDE11\uDE13-\uDE2B\uDE80-\uDE86\uDE88\uDE8A-\uDE8D\uDE8F-\uDE9D\uDE9F-\uDEA8\uDEB0-\uDEDE\uDF05-\uDF0C\uDF0F\uDF10\uDF13-\uDF28\uDF2A-\uDF30\uDF32\uDF33\uDF35-\uDF39\uDF3D\uDF50\uDF5D-\uDF61]|\uD805[\uDC00-\uDC34\uDC47-\uDC4A\uDC80-\uDCAF\uDCC4\uDCC5\uDCC7\uDD80-\uDDAE\uDDD8-\uDDDB\uDE00-\uDE2F\uDE44\uDE80-\uDEAA\uDF00-\uDF19]|\uD806[\uDCA0-\uDCDF\uDCFF\uDEC0-\uDEF8]|\uD807[\uDC00-\uDC08\uDC0A-\uDC2E\uDC40\uDC72-\uDC8F]|\uD808[\uDC00-\uDF99]|\uD809[\uDC00-\uDC6E\uDC80-\uDD43]|[\uD80C\uD81C-\uD820\uD840-\uD868\uD86A-\uD86C\uD86F-\uD872][\uDC00-\uDFFF]|\uD80D[\uDC00-\uDC2E]|\uD811[\uDC00-\uDE46]|\uD81A[\uDC00-\uDE38\uDE40-\uDE5E\uDED0-\uDEED\uDF00-\uDF2F\uDF40-\uDF43\uDF63-\uDF77\uDF7D-\uDF8F]|\uD81B[\uDF00-\uDF44\uDF50\uDF93-\uDF9F\uDFE0]|\uD821[\uDC00-\uDFEC]|\uD822[\uDC00-\uDEF2]|\uD82C[\uDC00\uDC01]|\uD82F[\uDC00-\uDC6A\uDC70-\uDC7C\uDC80-\uDC88\uDC90-\uDC99]|\uD835[\uDC00-\uDC54\uDC56-\uDC9C\uDC9E\uDC9F\uDCA2\uDCA5\uDCA6\uDCA9-\uDCAC\uDCAE-\uDCB9\uDCBB\uDCBD-\uDCC3\uDCC5-\uDD05\uDD07-\uDD0A\uDD0D-\uDD14\uDD16-\uDD1C\uDD1E-\uDD39\uDD3B-\uDD3E\uDD40-\uDD44\uDD46\uDD4A-\uDD50\uDD52-\uDEA5\uDEA8-\uDEC0\uDEC2-\uDEDA\uDEDC-\uDEFA\uDEFC-\uDF14\uDF16-\uDF34\uDF36-\uDF4E\uDF50-\uDF6E\uDF70-\uDF88\uDF8A-\uDFA8\uDFAA-\uDFC2\uDFC4-\uDFCB]|\uD83A[\uDC00-\uDCC4\uDD00-\uDD43]|\uD83B[\uDE00-\uDE03\uDE05-\uDE1F\uDE21\uDE22\uDE24\uDE27\uDE29-\uDE32\uDE34-\uDE37\uDE39\uDE3B\uDE42\uDE47\uDE49\uDE4B\uDE4D-\uDE4F\uDE51\uDE52\uDE54\uDE57\uDE59\uDE5B\uDE5D\uDE5F\uDE61\uDE62\uDE64\uDE67-\uDE6A\uDE6C-\uDE72\uDE74-\uDE77\uDE79-\uDE7C\uDE7E\uDE80-\uDE89\uDE8B-\uDE9B\uDEA1-\uDEA3\uDEA5-\uDEA9\uDEAB-\uDEBB]|\uD869[\uDC00-\uDED6\uDF00-\uDFFF]|\uD86D[\uDC00-\uDF34\uDF40-\uDFFF]|\uD86E[\uDC00-\uDC1D\uDC20-\uDFFF]|\uD873[\uDC00-\uDEA1]|\uD87E[\uDC00-\uDE1D]/,
        // ECMAScript 6/Unicode v9.0.0 NonAsciiIdentifierPart:
        NonAsciiIdentifierPart: /[\xAA\xB5\xB7\xBA\xC0-\xD6\xD8-\xF6\xF8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0300-\u0374\u0376\u0377\u037A-\u037D\u037F\u0386-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u0483-\u0487\u048A-\u052F\u0531-\u0556\u0559\u0561-\u0587\u0591-\u05BD\u05BF\u05C1\u05C2\u05C4\u05C5\u05C7\u05D0-\u05EA\u05F0-\u05F2\u0610-\u061A\u0620-\u0669\u066E-\u06D3\u06D5-\u06DC\u06DF-\u06E8\u06EA-\u06FC\u06FF\u0710-\u074A\u074D-\u07B1\u07C0-\u07F5\u07FA\u0800-\u082D\u0840-\u085B\u08A0-\u08B4\u08B6-\u08BD\u08D4-\u08E1\u08E3-\u0963\u0966-\u096F\u0971-\u0983\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BC-\u09C4\u09C7\u09C8\u09CB-\u09CE\u09D7\u09DC\u09DD\u09DF-\u09E3\u09E6-\u09F1\u0A01-\u0A03\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A3C\u0A3E-\u0A42\u0A47\u0A48\u0A4B-\u0A4D\u0A51\u0A59-\u0A5C\u0A5E\u0A66-\u0A75\u0A81-\u0A83\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABC-\u0AC5\u0AC7-\u0AC9\u0ACB-\u0ACD\u0AD0\u0AE0-\u0AE3\u0AE6-\u0AEF\u0AF9\u0B01-\u0B03\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3C-\u0B44\u0B47\u0B48\u0B4B-\u0B4D\u0B56\u0B57\u0B5C\u0B5D\u0B5F-\u0B63\u0B66-\u0B6F\u0B71\u0B82\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BBE-\u0BC2\u0BC6-\u0BC8\u0BCA-\u0BCD\u0BD0\u0BD7\u0BE6-\u0BEF\u0C00-\u0C03\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C39\u0C3D-\u0C44\u0C46-\u0C48\u0C4A-\u0C4D\u0C55\u0C56\u0C58-\u0C5A\u0C60-\u0C63\u0C66-\u0C6F\u0C80-\u0C83\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBC-\u0CC4\u0CC6-\u0CC8\u0CCA-\u0CCD\u0CD5\u0CD6\u0CDE\u0CE0-\u0CE3\u0CE6-\u0CEF\u0CF1\u0CF2\u0D01-\u0D03\u0D05-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D-\u0D44\u0D46-\u0D48\u0D4A-\u0D4E\u0D54-\u0D57\u0D5F-\u0D63\u0D66-\u0D6F\u0D7A-\u0D7F\u0D82\u0D83\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0DCA\u0DCF-\u0DD4\u0DD6\u0DD8-\u0DDF\u0DE6-\u0DEF\u0DF2\u0DF3\u0E01-\u0E3A\u0E40-\u0E4E\u0E50-\u0E59\u0E81\u0E82\u0E84\u0E87\u0E88\u0E8A\u0E8D\u0E94-\u0E97\u0E99-\u0E9F\u0EA1-\u0EA3\u0EA5\u0EA7\u0EAA\u0EAB\u0EAD-\u0EB9\u0EBB-\u0EBD\u0EC0-\u0EC4\u0EC6\u0EC8-\u0ECD\u0ED0-\u0ED9\u0EDC-\u0EDF\u0F00\u0F18\u0F19\u0F20-\u0F29\u0F35\u0F37\u0F39\u0F3E-\u0F47\u0F49-\u0F6C\u0F71-\u0F84\u0F86-\u0F97\u0F99-\u0FBC\u0FC6\u1000-\u1049\u1050-\u109D\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u135D-\u135F\u1369-\u1371\u1380-\u138F\u13A0-\u13F5\u13F8-\u13FD\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u16EE-\u16F8\u1700-\u170C\u170E-\u1714\u1720-\u1734\u1740-\u1753\u1760-\u176C\u176E-\u1770\u1772\u1773\u1780-\u17D3\u17D7\u17DC\u17DD\u17E0-\u17E9\u180B-\u180D\u1810-\u1819\u1820-\u1877\u1880-\u18AA\u18B0-\u18F5\u1900-\u191E\u1920-\u192B\u1930-\u193B\u1946-\u196D\u1970-\u1974\u1980-\u19AB\u19B0-\u19C9\u19D0-\u19DA\u1A00-\u1A1B\u1A20-\u1A5E\u1A60-\u1A7C\u1A7F-\u1A89\u1A90-\u1A99\u1AA7\u1AB0-\u1ABD\u1B00-\u1B4B\u1B50-\u1B59\u1B6B-\u1B73\u1B80-\u1BF3\u1C00-\u1C37\u1C40-\u1C49\u1C4D-\u1C7D\u1C80-\u1C88\u1CD0-\u1CD2\u1CD4-\u1CF6\u1CF8\u1CF9\u1D00-\u1DF5\u1DFB-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u200C\u200D\u203F\u2040\u2054\u2071\u207F\u2090-\u209C\u20D0-\u20DC\u20E1\u20E5-\u20F0\u2102\u2107\u210A-\u2113\u2115\u2118-\u211D\u2124\u2126\u2128\u212A-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2160-\u2188\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D7F-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u2DE0-\u2DFF\u3005-\u3007\u3021-\u302F\u3031-\u3035\u3038-\u303C\u3041-\u3096\u3099-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312D\u3131-\u318E\u31A0-\u31BA\u31F0-\u31FF\u3400-\u4DB5\u4E00-\u9FD5\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA62B\uA640-\uA66F\uA674-\uA67D\uA67F-\uA6F1\uA717-\uA71F\uA722-\uA788\uA78B-\uA7AE\uA7B0-\uA7B7\uA7F7-\uA827\uA840-\uA873\uA880-\uA8C5\uA8D0-\uA8D9\uA8E0-\uA8F7\uA8FB\uA8FD\uA900-\uA92D\uA930-\uA953\uA960-\uA97C\uA980-\uA9C0\uA9CF-\uA9D9\uA9E0-\uA9FE\uAA00-\uAA36\uAA40-\uAA4D\uAA50-\uAA59\uAA60-\uAA76\uAA7A-\uAAC2\uAADB-\uAADD\uAAE0-\uAAEF\uAAF2-\uAAF6\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uAB30-\uAB5A\uAB5C-\uAB65\uAB70-\uABEA\uABEC\uABED\uABF0-\uABF9\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE00-\uFE0F\uFE20-\uFE2F\uFE33\uFE34\uFE4D-\uFE4F\uFE70-\uFE74\uFE76-\uFEFC\uFF10-\uFF19\uFF21-\uFF3A\uFF3F\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC]|\uD800[\uDC00-\uDC0B\uDC0D-\uDC26\uDC28-\uDC3A\uDC3C\uDC3D\uDC3F-\uDC4D\uDC50-\uDC5D\uDC80-\uDCFA\uDD40-\uDD74\uDDFD\uDE80-\uDE9C\uDEA0-\uDED0\uDEE0\uDF00-\uDF1F\uDF30-\uDF4A\uDF50-\uDF7A\uDF80-\uDF9D\uDFA0-\uDFC3\uDFC8-\uDFCF\uDFD1-\uDFD5]|\uD801[\uDC00-\uDC9D\uDCA0-\uDCA9\uDCB0-\uDCD3\uDCD8-\uDCFB\uDD00-\uDD27\uDD30-\uDD63\uDE00-\uDF36\uDF40-\uDF55\uDF60-\uDF67]|\uD802[\uDC00-\uDC05\uDC08\uDC0A-\uDC35\uDC37\uDC38\uDC3C\uDC3F-\uDC55\uDC60-\uDC76\uDC80-\uDC9E\uDCE0-\uDCF2\uDCF4\uDCF5\uDD00-\uDD15\uDD20-\uDD39\uDD80-\uDDB7\uDDBE\uDDBF\uDE00-\uDE03\uDE05\uDE06\uDE0C-\uDE13\uDE15-\uDE17\uDE19-\uDE33\uDE38-\uDE3A\uDE3F\uDE60-\uDE7C\uDE80-\uDE9C\uDEC0-\uDEC7\uDEC9-\uDEE6\uDF00-\uDF35\uDF40-\uDF55\uDF60-\uDF72\uDF80-\uDF91]|\uD803[\uDC00-\uDC48\uDC80-\uDCB2\uDCC0-\uDCF2]|\uD804[\uDC00-\uDC46\uDC66-\uDC6F\uDC7F-\uDCBA\uDCD0-\uDCE8\uDCF0-\uDCF9\uDD00-\uDD34\uDD36-\uDD3F\uDD50-\uDD73\uDD76\uDD80-\uDDC4\uDDCA-\uDDCC\uDDD0-\uDDDA\uDDDC\uDE00-\uDE11\uDE13-\uDE37\uDE3E\uDE80-\uDE86\uDE88\uDE8A-\uDE8D\uDE8F-\uDE9D\uDE9F-\uDEA8\uDEB0-\uDEEA\uDEF0-\uDEF9\uDF00-\uDF03\uDF05-\uDF0C\uDF0F\uDF10\uDF13-\uDF28\uDF2A-\uDF30\uDF32\uDF33\uDF35-\uDF39\uDF3C-\uDF44\uDF47\uDF48\uDF4B-\uDF4D\uDF50\uDF57\uDF5D-\uDF63\uDF66-\uDF6C\uDF70-\uDF74]|\uD805[\uDC00-\uDC4A\uDC50-\uDC59\uDC80-\uDCC5\uDCC7\uDCD0-\uDCD9\uDD80-\uDDB5\uDDB8-\uDDC0\uDDD8-\uDDDD\uDE00-\uDE40\uDE44\uDE50-\uDE59\uDE80-\uDEB7\uDEC0-\uDEC9\uDF00-\uDF19\uDF1D-\uDF2B\uDF30-\uDF39]|\uD806[\uDCA0-\uDCE9\uDCFF\uDEC0-\uDEF8]|\uD807[\uDC00-\uDC08\uDC0A-\uDC36\uDC38-\uDC40\uDC50-\uDC59\uDC72-\uDC8F\uDC92-\uDCA7\uDCA9-\uDCB6]|\uD808[\uDC00-\uDF99]|\uD809[\uDC00-\uDC6E\uDC80-\uDD43]|[\uD80C\uD81C-\uD820\uD840-\uD868\uD86A-\uD86C\uD86F-\uD872][\uDC00-\uDFFF]|\uD80D[\uDC00-\uDC2E]|\uD811[\uDC00-\uDE46]|\uD81A[\uDC00-\uDE38\uDE40-\uDE5E\uDE60-\uDE69\uDED0-\uDEED\uDEF0-\uDEF4\uDF00-\uDF36\uDF40-\uDF43\uDF50-\uDF59\uDF63-\uDF77\uDF7D-\uDF8F]|\uD81B[\uDF00-\uDF44\uDF50-\uDF7E\uDF8F-\uDF9F\uDFE0]|\uD821[\uDC00-\uDFEC]|\uD822[\uDC00-\uDEF2]|\uD82C[\uDC00\uDC01]|\uD82F[\uDC00-\uDC6A\uDC70-\uDC7C\uDC80-\uDC88\uDC90-\uDC99\uDC9D\uDC9E]|\uD834[\uDD65-\uDD69\uDD6D-\uDD72\uDD7B-\uDD82\uDD85-\uDD8B\uDDAA-\uDDAD\uDE42-\uDE44]|\uD835[\uDC00-\uDC54\uDC56-\uDC9C\uDC9E\uDC9F\uDCA2\uDCA5\uDCA6\uDCA9-\uDCAC\uDCAE-\uDCB9\uDCBB\uDCBD-\uDCC3\uDCC5-\uDD05\uDD07-\uDD0A\uDD0D-\uDD14\uDD16-\uDD1C\uDD1E-\uDD39\uDD3B-\uDD3E\uDD40-\uDD44\uDD46\uDD4A-\uDD50\uDD52-\uDEA5\uDEA8-\uDEC0\uDEC2-\uDEDA\uDEDC-\uDEFA\uDEFC-\uDF14\uDF16-\uDF34\uDF36-\uDF4E\uDF50-\uDF6E\uDF70-\uDF88\uDF8A-\uDFA8\uDFAA-\uDFC2\uDFC4-\uDFCB\uDFCE-\uDFFF]|\uD836[\uDE00-\uDE36\uDE3B-\uDE6C\uDE75\uDE84\uDE9B-\uDE9F\uDEA1-\uDEAF]|\uD838[\uDC00-\uDC06\uDC08-\uDC18\uDC1B-\uDC21\uDC23\uDC24\uDC26-\uDC2A]|\uD83A[\uDC00-\uDCC4\uDCD0-\uDCD6\uDD00-\uDD4A\uDD50-\uDD59]|\uD83B[\uDE00-\uDE03\uDE05-\uDE1F\uDE21\uDE22\uDE24\uDE27\uDE29-\uDE32\uDE34-\uDE37\uDE39\uDE3B\uDE42\uDE47\uDE49\uDE4B\uDE4D-\uDE4F\uDE51\uDE52\uDE54\uDE57\uDE59\uDE5B\uDE5D\uDE5F\uDE61\uDE62\uDE64\uDE67-\uDE6A\uDE6C-\uDE72\uDE74-\uDE77\uDE79-\uDE7C\uDE7E\uDE80-\uDE89\uDE8B-\uDE9B\uDEA1-\uDEA3\uDEA5-\uDEA9\uDEAB-\uDEBB]|\uD869[\uDC00-\uDED6\uDF00-\uDFFF]|\uD86D[\uDC00-\uDF34\uDF40-\uDFFF]|\uD86E[\uDC00-\uDC1D\uDC20-\uDFFF]|\uD873[\uDC00-\uDEA1]|\uD87E[\uDC00-\uDE1D]|\uDB40[\uDD00-\uDDEF]/
      };
      function isDecimalDigit(ch2) {
        return 48 <= ch2 && ch2 <= 57;
      }
      function isHexDigit(ch2) {
        return 48 <= ch2 && ch2 <= 57 || // 0..9
        97 <= ch2 && ch2 <= 102 || // a..f
        65 <= ch2 && ch2 <= 70;
      }
      function isOctalDigit(ch2) {
        return ch2 >= 48 && ch2 <= 55;
      }
      NON_ASCII_WHITESPACES = [
        5760,
        8192,
        8193,
        8194,
        8195,
        8196,
        8197,
        8198,
        8199,
        8200,
        8201,
        8202,
        8239,
        8287,
        12288,
        65279
      ];
      function isWhiteSpace(ch2) {
        return ch2 === 32 || ch2 === 9 || ch2 === 11 || ch2 === 12 || ch2 === 160 || ch2 >= 5760 && NON_ASCII_WHITESPACES.indexOf(ch2) >= 0;
      }
      function isLineTerminator(ch2) {
        return ch2 === 10 || ch2 === 13 || ch2 === 8232 || ch2 === 8233;
      }
      function fromCodePoint(cp) {
        if (cp <= 65535)
          return String.fromCharCode(cp);
        var cu1 = String.fromCharCode(Math.floor((cp - 65536) / 1024) + 55296), cu2 = String.fromCharCode((cp - 65536) % 1024 + 56320);
        return cu1 + cu2;
      }
      for (IDENTIFIER_START = new Array(128), ch = 0; ch < 128; ++ch)
        IDENTIFIER_START[ch] = ch >= 97 && ch <= 122 || // a..z
        ch >= 65 && ch <= 90 || // A..Z
        ch === 36 || ch === 95;
      for (IDENTIFIER_PART = new Array(128), ch = 0; ch < 128; ++ch)
        IDENTIFIER_PART[ch] = ch >= 97 && ch <= 122 || // a..z
        ch >= 65 && ch <= 90 || // A..Z
        ch >= 48 && ch <= 57 || // 0..9
        ch === 36 || ch === 95;
      function isIdentifierStartES5(ch2) {
        return ch2 < 128 ? IDENTIFIER_START[ch2] : ES5Regex.NonAsciiIdentifierStart.test(fromCodePoint(ch2));
      }
      function isIdentifierPartES5(ch2) {
        return ch2 < 128 ? IDENTIFIER_PART[ch2] : ES5Regex.NonAsciiIdentifierPart.test(fromCodePoint(ch2));
      }
      function isIdentifierStartES6(ch2) {
        return ch2 < 128 ? IDENTIFIER_START[ch2] : ES6Regex.NonAsciiIdentifierStart.test(fromCodePoint(ch2));
      }
      function isIdentifierPartES6(ch2) {
        return ch2 < 128 ? IDENTIFIER_PART[ch2] : ES6Regex.NonAsciiIdentifierPart.test(fromCodePoint(ch2));
      }
      module.exports = {
        isDecimalDigit,
        isHexDigit,
        isOctalDigit,
        isWhiteSpace,
        isLineTerminator,
        isIdentifierStartES5,
        isIdentifierPartES5,
        isIdentifierStartES6,
        isIdentifierPartES6
      };
    })();
  }
});

// ../../../node_modules/esutils/lib/keyword.js
var require_keyword = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/esutils/lib/keyword.js"(exports, module) {
    (function() {
      "use strict";
      var code = require_code();
      function isStrictModeReservedWordES6(id) {
        switch (id) {
          case "implements":
          case "interface":
          case "package":
          case "private":
          case "protected":
          case "public":
          case "static":
          case "let":
            return !0;
          default:
            return !1;
        }
      }
      function isKeywordES5(id, strict) {
        return !strict && id === "yield" ? !1 : isKeywordES6(id, strict);
      }
      function isKeywordES6(id, strict) {
        if (strict && isStrictModeReservedWordES6(id))
          return !0;
        switch (id.length) {
          case 2:
            return id === "if" || id === "in" || id === "do";
          case 3:
            return id === "var" || id === "for" || id === "new" || id === "try";
          case 4:
            return id === "this" || id === "else" || id === "case" || id === "void" || id === "with" || id === "enum";
          case 5:
            return id === "while" || id === "break" || id === "catch" || id === "throw" || id === "const" || id === "yield" || id === "class" || id === "super";
          case 6:
            return id === "return" || id === "typeof" || id === "delete" || id === "switch" || id === "export" || id === "import";
          case 7:
            return id === "default" || id === "finally" || id === "extends";
          case 8:
            return id === "function" || id === "continue" || id === "debugger";
          case 10:
            return id === "instanceof";
          default:
            return !1;
        }
      }
      function isReservedWordES5(id, strict) {
        return id === "null" || id === "true" || id === "false" || isKeywordES5(id, strict);
      }
      function isReservedWordES6(id, strict) {
        return id === "null" || id === "true" || id === "false" || isKeywordES6(id, strict);
      }
      function isRestrictedWord(id) {
        return id === "eval" || id === "arguments";
      }
      function isIdentifierNameES5(id) {
        var i, iz, ch;
        if (id.length === 0 || (ch = id.charCodeAt(0), !code.isIdentifierStartES5(ch)))
          return !1;
        for (i = 1, iz = id.length; i < iz; ++i)
          if (ch = id.charCodeAt(i), !code.isIdentifierPartES5(ch))
            return !1;
        return !0;
      }
      function decodeUtf16(lead, trail) {
        return (lead - 55296) * 1024 + (trail - 56320) + 65536;
      }
      function isIdentifierNameES6(id) {
        var i, iz, ch, lowCh, check;
        if (id.length === 0)
          return !1;
        for (check = code.isIdentifierStartES6, i = 0, iz = id.length; i < iz; ++i) {
          if (ch = id.charCodeAt(i), 55296 <= ch && ch <= 56319) {
            if (++i, i >= iz || (lowCh = id.charCodeAt(i), !(56320 <= lowCh && lowCh <= 57343)))
              return !1;
            ch = decodeUtf16(ch, lowCh);
          }
          if (!check(ch))
            return !1;
          check = code.isIdentifierPartES6;
        }
        return !0;
      }
      function isIdentifierES5(id, strict) {
        return isIdentifierNameES5(id) && !isReservedWordES5(id, strict);
      }
      function isIdentifierES6(id, strict) {
        return isIdentifierNameES6(id) && !isReservedWordES6(id, strict);
      }
      module.exports = {
        isKeywordES5,
        isKeywordES6,
        isReservedWordES5,
        isReservedWordES6,
        isRestrictedWord,
        isIdentifierNameES5,
        isIdentifierNameES6,
        isIdentifierES5,
        isIdentifierES6
      };
    })();
  }
});

// ../../../node_modules/esutils/lib/utils.js
var require_utils = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/esutils/lib/utils.js"(exports) {
    (function() {
      "use strict";
      exports.ast = require_ast(), exports.code = require_code(), exports.keyword = require_keyword();
    })();
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/base64.js
var require_base64 = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/base64.js"(exports) {
    var intToCharMap = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/".split("");
    exports.encode = function(number) {
      if (0 <= number && number < intToCharMap.length)
        return intToCharMap[number];
      throw new TypeError("Must be between 0 and 63: " + number);
    };
    exports.decode = function(charCode) {
      var bigA = 65, bigZ = 90, littleA = 97, littleZ = 122, zero = 48, nine = 57, plus = 43, slash = 47, littleOffset = 26, numberOffset = 52;
      return bigA <= charCode && charCode <= bigZ ? charCode - bigA : littleA <= charCode && charCode <= littleZ ? charCode - littleA + littleOffset : zero <= charCode && charCode <= nine ? charCode - zero + numberOffset : charCode == plus ? 62 : charCode == slash ? 63 : -1;
    };
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/base64-vlq.js
var require_base64_vlq = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/base64-vlq.js"(exports) {
    var base64 = require_base64(), VLQ_BASE_SHIFT = 5, VLQ_BASE = 1 << VLQ_BASE_SHIFT, VLQ_BASE_MASK = VLQ_BASE - 1, VLQ_CONTINUATION_BIT = VLQ_BASE;
    function toVLQSigned(aValue) {
      return aValue < 0 ? (-aValue << 1) + 1 : (aValue << 1) + 0;
    }
    function fromVLQSigned(aValue) {
      var isNegative = (aValue & 1) === 1, shifted = aValue >> 1;
      return isNegative ? -shifted : shifted;
    }
    exports.encode = function(aValue) {
      var encoded = "", digit, vlq = toVLQSigned(aValue);
      do
        digit = vlq & VLQ_BASE_MASK, vlq >>>= VLQ_BASE_SHIFT, vlq > 0 && (digit |= VLQ_CONTINUATION_BIT), encoded += base64.encode(digit);
      while (vlq > 0);
      return encoded;
    };
    exports.decode = function(aStr, aIndex, aOutParam) {
      var strLen = aStr.length, result = 0, shift = 0, continuation, digit;
      do {
        if (aIndex >= strLen)
          throw new Error("Expected more digits in base 64 VLQ value.");
        if (digit = base64.decode(aStr.charCodeAt(aIndex++)), digit === -1)
          throw new Error("Invalid base64 digit: " + aStr.charAt(aIndex - 1));
        continuation = !!(digit & VLQ_CONTINUATION_BIT), digit &= VLQ_BASE_MASK, result = result + (digit << shift), shift += VLQ_BASE_SHIFT;
      } while (continuation);
      aOutParam.value = fromVLQSigned(result), aOutParam.rest = aIndex;
    };
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/util.js
var require_util = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/util.js"(exports) {
    function getArg(aArgs, aName, aDefaultValue) {
      if (aName in aArgs)
        return aArgs[aName];
      if (arguments.length === 3)
        return aDefaultValue;
      throw new Error('"' + aName + '" is a required argument.');
    }
    exports.getArg = getArg;
    var urlRegexp = /^(?:([\w+\-.]+):)?\/\/(?:(\w+:\w+)@)?([\w.-]*)(?::(\d+))?(.*)$/, dataUrlRegexp = /^data:.+\,.+$/;
    function urlParse(aUrl) {
      var match = aUrl.match(urlRegexp);
      return match ? {
        scheme: match[1],
        auth: match[2],
        host: match[3],
        port: match[4],
        path: match[5]
      } : null;
    }
    exports.urlParse = urlParse;
    function urlGenerate(aParsedUrl) {
      var url = "";
      return aParsedUrl.scheme && (url += aParsedUrl.scheme + ":"), url += "//", aParsedUrl.auth && (url += aParsedUrl.auth + "@"), aParsedUrl.host && (url += aParsedUrl.host), aParsedUrl.port && (url += ":" + aParsedUrl.port), aParsedUrl.path && (url += aParsedUrl.path), url;
    }
    exports.urlGenerate = urlGenerate;
    function normalize(aPath) {
      var path = aPath, url = urlParse(aPath);
      if (url) {
        if (!url.path)
          return aPath;
        path = url.path;
      }
      for (var isAbsolute = exports.isAbsolute(path), parts = path.split(/\/+/), part, up = 0, i = parts.length - 1; i >= 0; i--)
        part = parts[i], part === "." ? parts.splice(i, 1) : part === ".." ? up++ : up > 0 && (part === "" ? (parts.splice(i + 1, up), up = 0) : (parts.splice(i, 2), up--));
      return path = parts.join("/"), path === "" && (path = isAbsolute ? "/" : "."), url ? (url.path = path, urlGenerate(url)) : path;
    }
    exports.normalize = normalize;
    function join(aRoot, aPath) {
      aRoot === "" && (aRoot = "."), aPath === "" && (aPath = ".");
      var aPathUrl = urlParse(aPath), aRootUrl = urlParse(aRoot);
      if (aRootUrl && (aRoot = aRootUrl.path || "/"), aPathUrl && !aPathUrl.scheme)
        return aRootUrl && (aPathUrl.scheme = aRootUrl.scheme), urlGenerate(aPathUrl);
      if (aPathUrl || aPath.match(dataUrlRegexp))
        return aPath;
      if (aRootUrl && !aRootUrl.host && !aRootUrl.path)
        return aRootUrl.host = aPath, urlGenerate(aRootUrl);
      var joined = aPath.charAt(0) === "/" ? aPath : normalize(aRoot.replace(/\/+$/, "") + "/" + aPath);
      return aRootUrl ? (aRootUrl.path = joined, urlGenerate(aRootUrl)) : joined;
    }
    exports.join = join;
    exports.isAbsolute = function(aPath) {
      return aPath.charAt(0) === "/" || urlRegexp.test(aPath);
    };
    function relative(aRoot, aPath) {
      aRoot === "" && (aRoot = "."), aRoot = aRoot.replace(/\/$/, "");
      for (var level = 0; aPath.indexOf(aRoot + "/") !== 0; ) {
        var index = aRoot.lastIndexOf("/");
        if (index < 0 || (aRoot = aRoot.slice(0, index), aRoot.match(/^([^\/]+:\/)?\/*$/)))
          return aPath;
        ++level;
      }
      return Array(level + 1).join("../") + aPath.substr(aRoot.length + 1);
    }
    exports.relative = relative;
    var supportsNullProto = (function() {
      var obj = /* @__PURE__ */ Object.create(null);
      return !("__proto__" in obj);
    })();
    function identity(s) {
      return s;
    }
    function toSetString(aStr) {
      return isProtoString(aStr) ? "$" + aStr : aStr;
    }
    exports.toSetString = supportsNullProto ? identity : toSetString;
    function fromSetString(aStr) {
      return isProtoString(aStr) ? aStr.slice(1) : aStr;
    }
    exports.fromSetString = supportsNullProto ? identity : fromSetString;
    function isProtoString(s) {
      if (!s)
        return !1;
      var length = s.length;
      if (length < 9 || s.charCodeAt(length - 1) !== 95 || s.charCodeAt(length - 2) !== 95 || s.charCodeAt(length - 3) !== 111 || s.charCodeAt(length - 4) !== 116 || s.charCodeAt(length - 5) !== 111 || s.charCodeAt(length - 6) !== 114 || s.charCodeAt(length - 7) !== 112 || s.charCodeAt(length - 8) !== 95 || s.charCodeAt(length - 9) !== 95)
        return !1;
      for (var i = length - 10; i >= 0; i--)
        if (s.charCodeAt(i) !== 36)
          return !1;
      return !0;
    }
    function compareByOriginalPositions(mappingA, mappingB, onlyCompareOriginal) {
      var cmp = strcmp(mappingA.source, mappingB.source);
      return cmp !== 0 || (cmp = mappingA.originalLine - mappingB.originalLine, cmp !== 0) || (cmp = mappingA.originalColumn - mappingB.originalColumn, cmp !== 0 || onlyCompareOriginal) || (cmp = mappingA.generatedColumn - mappingB.generatedColumn, cmp !== 0) || (cmp = mappingA.generatedLine - mappingB.generatedLine, cmp !== 0) ? cmp : strcmp(mappingA.name, mappingB.name);
    }
    exports.compareByOriginalPositions = compareByOriginalPositions;
    function compareByGeneratedPositionsDeflated(mappingA, mappingB, onlyCompareGenerated) {
      var cmp = mappingA.generatedLine - mappingB.generatedLine;
      return cmp !== 0 || (cmp = mappingA.generatedColumn - mappingB.generatedColumn, cmp !== 0 || onlyCompareGenerated) || (cmp = strcmp(mappingA.source, mappingB.source), cmp !== 0) || (cmp = mappingA.originalLine - mappingB.originalLine, cmp !== 0) || (cmp = mappingA.originalColumn - mappingB.originalColumn, cmp !== 0) ? cmp : strcmp(mappingA.name, mappingB.name);
    }
    exports.compareByGeneratedPositionsDeflated = compareByGeneratedPositionsDeflated;
    function strcmp(aStr1, aStr2) {
      return aStr1 === aStr2 ? 0 : aStr1 === null ? 1 : aStr2 === null ? -1 : aStr1 > aStr2 ? 1 : -1;
    }
    function compareByGeneratedPositionsInflated(mappingA, mappingB) {
      var cmp = mappingA.generatedLine - mappingB.generatedLine;
      return cmp !== 0 || (cmp = mappingA.generatedColumn - mappingB.generatedColumn, cmp !== 0) || (cmp = strcmp(mappingA.source, mappingB.source), cmp !== 0) || (cmp = mappingA.originalLine - mappingB.originalLine, cmp !== 0) || (cmp = mappingA.originalColumn - mappingB.originalColumn, cmp !== 0) ? cmp : strcmp(mappingA.name, mappingB.name);
    }
    exports.compareByGeneratedPositionsInflated = compareByGeneratedPositionsInflated;
    function parseSourceMapInput(str) {
      return JSON.parse(str.replace(/^\)]}'[^\n]*\n/, ""));
    }
    exports.parseSourceMapInput = parseSourceMapInput;
    function computeSourceURL(sourceRoot, sourceURL, sourceMapURL) {
      if (sourceURL = sourceURL || "", sourceRoot && (sourceRoot[sourceRoot.length - 1] !== "/" && sourceURL[0] !== "/" && (sourceRoot += "/"), sourceURL = sourceRoot + sourceURL), sourceMapURL) {
        var parsed = urlParse(sourceMapURL);
        if (!parsed)
          throw new Error("sourceMapURL could not be parsed");
        if (parsed.path) {
          var index = parsed.path.lastIndexOf("/");
          index >= 0 && (parsed.path = parsed.path.substring(0, index + 1));
        }
        sourceURL = join(urlGenerate(parsed), sourceURL);
      }
      return normalize(sourceURL);
    }
    exports.computeSourceURL = computeSourceURL;
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/array-set.js
var require_array_set = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/array-set.js"(exports) {
    var util = require_util(), has = Object.prototype.hasOwnProperty, hasNativeMap = typeof Map < "u";
    function ArraySet() {
      this._array = [], this._set = hasNativeMap ? /* @__PURE__ */ new Map() : /* @__PURE__ */ Object.create(null);
    }
    ArraySet.fromArray = function(aArray, aAllowDuplicates) {
      for (var set = new ArraySet(), i = 0, len = aArray.length; i < len; i++)
        set.add(aArray[i], aAllowDuplicates);
      return set;
    };
    ArraySet.prototype.size = function() {
      return hasNativeMap ? this._set.size : Object.getOwnPropertyNames(this._set).length;
    };
    ArraySet.prototype.add = function(aStr, aAllowDuplicates) {
      var sStr = hasNativeMap ? aStr : util.toSetString(aStr), isDuplicate = hasNativeMap ? this.has(aStr) : has.call(this._set, sStr), idx = this._array.length;
      (!isDuplicate || aAllowDuplicates) && this._array.push(aStr), isDuplicate || (hasNativeMap ? this._set.set(aStr, idx) : this._set[sStr] = idx);
    };
    ArraySet.prototype.has = function(aStr) {
      if (hasNativeMap)
        return this._set.has(aStr);
      var sStr = util.toSetString(aStr);
      return has.call(this._set, sStr);
    };
    ArraySet.prototype.indexOf = function(aStr) {
      if (hasNativeMap) {
        var idx = this._set.get(aStr);
        if (idx >= 0)
          return idx;
      } else {
        var sStr = util.toSetString(aStr);
        if (has.call(this._set, sStr))
          return this._set[sStr];
      }
      throw new Error('"' + aStr + '" is not in the set.');
    };
    ArraySet.prototype.at = function(aIdx) {
      if (aIdx >= 0 && aIdx < this._array.length)
        return this._array[aIdx];
      throw new Error("No element indexed by " + aIdx);
    };
    ArraySet.prototype.toArray = function() {
      return this._array.slice();
    };
    exports.ArraySet = ArraySet;
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/mapping-list.js
var require_mapping_list = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/mapping-list.js"(exports) {
    var util = require_util();
    function generatedPositionAfter(mappingA, mappingB) {
      var lineA = mappingA.generatedLine, lineB = mappingB.generatedLine, columnA = mappingA.generatedColumn, columnB = mappingB.generatedColumn;
      return lineB > lineA || lineB == lineA && columnB >= columnA || util.compareByGeneratedPositionsInflated(mappingA, mappingB) <= 0;
    }
    function MappingList() {
      this._array = [], this._sorted = !0, this._last = { generatedLine: -1, generatedColumn: 0 };
    }
    MappingList.prototype.unsortedForEach = function(aCallback, aThisArg) {
      this._array.forEach(aCallback, aThisArg);
    };
    MappingList.prototype.add = function(aMapping) {
      generatedPositionAfter(this._last, aMapping) ? (this._last = aMapping, this._array.push(aMapping)) : (this._sorted = !1, this._array.push(aMapping));
    };
    MappingList.prototype.toArray = function() {
      return this._sorted || (this._array.sort(util.compareByGeneratedPositionsInflated), this._sorted = !0), this._array;
    };
    exports.MappingList = MappingList;
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/source-map-generator.js
var require_source_map_generator = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/source-map-generator.js"(exports) {
    var base64VLQ = require_base64_vlq(), util = require_util(), ArraySet = require_array_set().ArraySet, MappingList = require_mapping_list().MappingList;
    function SourceMapGenerator(aArgs) {
      aArgs || (aArgs = {}), this._file = util.getArg(aArgs, "file", null), this._sourceRoot = util.getArg(aArgs, "sourceRoot", null), this._skipValidation = util.getArg(aArgs, "skipValidation", !1), this._sources = new ArraySet(), this._names = new ArraySet(), this._mappings = new MappingList(), this._sourcesContents = null;
    }
    SourceMapGenerator.prototype._version = 3;
    SourceMapGenerator.fromSourceMap = function(aSourceMapConsumer) {
      var sourceRoot = aSourceMapConsumer.sourceRoot, generator = new SourceMapGenerator({
        file: aSourceMapConsumer.file,
        sourceRoot
      });
      return aSourceMapConsumer.eachMapping(function(mapping) {
        var newMapping = {
          generated: {
            line: mapping.generatedLine,
            column: mapping.generatedColumn
          }
        };
        mapping.source != null && (newMapping.source = mapping.source, sourceRoot != null && (newMapping.source = util.relative(sourceRoot, newMapping.source)), newMapping.original = {
          line: mapping.originalLine,
          column: mapping.originalColumn
        }, mapping.name != null && (newMapping.name = mapping.name)), generator.addMapping(newMapping);
      }), aSourceMapConsumer.sources.forEach(function(sourceFile) {
        var sourceRelative = sourceFile;
        sourceRoot !== null && (sourceRelative = util.relative(sourceRoot, sourceFile)), generator._sources.has(sourceRelative) || generator._sources.add(sourceRelative);
        var content = aSourceMapConsumer.sourceContentFor(sourceFile);
        content != null && generator.setSourceContent(sourceFile, content);
      }), generator;
    };
    SourceMapGenerator.prototype.addMapping = function(aArgs) {
      var generated = util.getArg(aArgs, "generated"), original = util.getArg(aArgs, "original", null), source = util.getArg(aArgs, "source", null), name = util.getArg(aArgs, "name", null);
      this._skipValidation || this._validateMapping(generated, original, source, name), source != null && (source = String(source), this._sources.has(source) || this._sources.add(source)), name != null && (name = String(name), this._names.has(name) || this._names.add(name)), this._mappings.add({
        generatedLine: generated.line,
        generatedColumn: generated.column,
        originalLine: original != null && original.line,
        originalColumn: original != null && original.column,
        source,
        name
      });
    };
    SourceMapGenerator.prototype.setSourceContent = function(aSourceFile, aSourceContent) {
      var source = aSourceFile;
      this._sourceRoot != null && (source = util.relative(this._sourceRoot, source)), aSourceContent != null ? (this._sourcesContents || (this._sourcesContents = /* @__PURE__ */ Object.create(null)), this._sourcesContents[util.toSetString(source)] = aSourceContent) : this._sourcesContents && (delete this._sourcesContents[util.toSetString(source)], Object.keys(this._sourcesContents).length === 0 && (this._sourcesContents = null));
    };
    SourceMapGenerator.prototype.applySourceMap = function(aSourceMapConsumer, aSourceFile, aSourceMapPath) {
      var sourceFile = aSourceFile;
      if (aSourceFile == null) {
        if (aSourceMapConsumer.file == null)
          throw new Error(
            `SourceMapGenerator.prototype.applySourceMap requires either an explicit source file, or the source map's "file" property. Both were omitted.`
          );
        sourceFile = aSourceMapConsumer.file;
      }
      var sourceRoot = this._sourceRoot;
      sourceRoot != null && (sourceFile = util.relative(sourceRoot, sourceFile));
      var newSources = new ArraySet(), newNames = new ArraySet();
      this._mappings.unsortedForEach(function(mapping) {
        if (mapping.source === sourceFile && mapping.originalLine != null) {
          var original = aSourceMapConsumer.originalPositionFor({
            line: mapping.originalLine,
            column: mapping.originalColumn
          });
          original.source != null && (mapping.source = original.source, aSourceMapPath != null && (mapping.source = util.join(aSourceMapPath, mapping.source)), sourceRoot != null && (mapping.source = util.relative(sourceRoot, mapping.source)), mapping.originalLine = original.line, mapping.originalColumn = original.column, original.name != null && (mapping.name = original.name));
        }
        var source = mapping.source;
        source != null && !newSources.has(source) && newSources.add(source);
        var name = mapping.name;
        name != null && !newNames.has(name) && newNames.add(name);
      }, this), this._sources = newSources, this._names = newNames, aSourceMapConsumer.sources.forEach(function(sourceFile2) {
        var content = aSourceMapConsumer.sourceContentFor(sourceFile2);
        content != null && (aSourceMapPath != null && (sourceFile2 = util.join(aSourceMapPath, sourceFile2)), sourceRoot != null && (sourceFile2 = util.relative(sourceRoot, sourceFile2)), this.setSourceContent(sourceFile2, content));
      }, this);
    };
    SourceMapGenerator.prototype._validateMapping = function(aGenerated, aOriginal, aSource, aName) {
      if (aOriginal && typeof aOriginal.line != "number" && typeof aOriginal.column != "number")
        throw new Error(
          "original.line and original.column are not numbers -- you probably meant to omit the original mapping entirely and only map the generated position. If so, pass null for the original mapping instead of an object with empty or null values."
        );
      if (!(aGenerated && "line" in aGenerated && "column" in aGenerated && aGenerated.line > 0 && aGenerated.column >= 0 && !aOriginal && !aSource && !aName)) {
        if (aGenerated && "line" in aGenerated && "column" in aGenerated && aOriginal && "line" in aOriginal && "column" in aOriginal && aGenerated.line > 0 && aGenerated.column >= 0 && aOriginal.line > 0 && aOriginal.column >= 0 && aSource)
          return;
        throw new Error("Invalid mapping: " + JSON.stringify({
          generated: aGenerated,
          source: aSource,
          original: aOriginal,
          name: aName
        }));
      }
    };
    SourceMapGenerator.prototype._serializeMappings = function() {
      for (var previousGeneratedColumn = 0, previousGeneratedLine = 1, previousOriginalColumn = 0, previousOriginalLine = 0, previousName = 0, previousSource = 0, result = "", next, mapping, nameIdx, sourceIdx, mappings = this._mappings.toArray(), i = 0, len = mappings.length; i < len; i++) {
        if (mapping = mappings[i], next = "", mapping.generatedLine !== previousGeneratedLine)
          for (previousGeneratedColumn = 0; mapping.generatedLine !== previousGeneratedLine; )
            next += ";", previousGeneratedLine++;
        else if (i > 0) {
          if (!util.compareByGeneratedPositionsInflated(mapping, mappings[i - 1]))
            continue;
          next += ",";
        }
        next += base64VLQ.encode(mapping.generatedColumn - previousGeneratedColumn), previousGeneratedColumn = mapping.generatedColumn, mapping.source != null && (sourceIdx = this._sources.indexOf(mapping.source), next += base64VLQ.encode(sourceIdx - previousSource), previousSource = sourceIdx, next += base64VLQ.encode(mapping.originalLine - 1 - previousOriginalLine), previousOriginalLine = mapping.originalLine - 1, next += base64VLQ.encode(mapping.originalColumn - previousOriginalColumn), previousOriginalColumn = mapping.originalColumn, mapping.name != null && (nameIdx = this._names.indexOf(mapping.name), next += base64VLQ.encode(nameIdx - previousName), previousName = nameIdx)), result += next;
      }
      return result;
    };
    SourceMapGenerator.prototype._generateSourcesContent = function(aSources, aSourceRoot) {
      return aSources.map(function(source) {
        if (!this._sourcesContents)
          return null;
        aSourceRoot != null && (source = util.relative(aSourceRoot, source));
        var key = util.toSetString(source);
        return Object.prototype.hasOwnProperty.call(this._sourcesContents, key) ? this._sourcesContents[key] : null;
      }, this);
    };
    SourceMapGenerator.prototype.toJSON = function() {
      var map = {
        version: this._version,
        sources: this._sources.toArray(),
        names: this._names.toArray(),
        mappings: this._serializeMappings()
      };
      return this._file != null && (map.file = this._file), this._sourceRoot != null && (map.sourceRoot = this._sourceRoot), this._sourcesContents && (map.sourcesContent = this._generateSourcesContent(map.sources, map.sourceRoot)), map;
    };
    SourceMapGenerator.prototype.toString = function() {
      return JSON.stringify(this.toJSON());
    };
    exports.SourceMapGenerator = SourceMapGenerator;
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/binary-search.js
var require_binary_search = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/binary-search.js"(exports) {
    exports.GREATEST_LOWER_BOUND = 1;
    exports.LEAST_UPPER_BOUND = 2;
    function recursiveSearch(aLow, aHigh, aNeedle, aHaystack, aCompare, aBias) {
      var mid = Math.floor((aHigh - aLow) / 2) + aLow, cmp = aCompare(aNeedle, aHaystack[mid], !0);
      return cmp === 0 ? mid : cmp > 0 ? aHigh - mid > 1 ? recursiveSearch(mid, aHigh, aNeedle, aHaystack, aCompare, aBias) : aBias == exports.LEAST_UPPER_BOUND ? aHigh < aHaystack.length ? aHigh : -1 : mid : mid - aLow > 1 ? recursiveSearch(aLow, mid, aNeedle, aHaystack, aCompare, aBias) : aBias == exports.LEAST_UPPER_BOUND ? mid : aLow < 0 ? -1 : aLow;
    }
    exports.search = function(aNeedle, aHaystack, aCompare, aBias) {
      if (aHaystack.length === 0)
        return -1;
      var index = recursiveSearch(
        -1,
        aHaystack.length,
        aNeedle,
        aHaystack,
        aCompare,
        aBias || exports.GREATEST_LOWER_BOUND
      );
      if (index < 0)
        return -1;
      for (; index - 1 >= 0 && aCompare(aHaystack[index], aHaystack[index - 1], !0) === 0; )
        --index;
      return index;
    };
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/quick-sort.js
var require_quick_sort = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/quick-sort.js"(exports) {
    function swap(ary, x, y) {
      var temp = ary[x];
      ary[x] = ary[y], ary[y] = temp;
    }
    function randomIntInRange(low, high) {
      return Math.round(low + Math.random() * (high - low));
    }
    function doQuickSort(ary, comparator, p, r) {
      if (p < r) {
        var pivotIndex = randomIntInRange(p, r), i = p - 1;
        swap(ary, pivotIndex, r);
        for (var pivot = ary[r], j = p; j < r; j++)
          comparator(ary[j], pivot) <= 0 && (i += 1, swap(ary, i, j));
        swap(ary, i + 1, j);
        var q = i + 1;
        doQuickSort(ary, comparator, p, q - 1), doQuickSort(ary, comparator, q + 1, r);
      }
    }
    exports.quickSort = function(ary, comparator) {
      doQuickSort(ary, comparator, 0, ary.length - 1);
    };
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/source-map-consumer.js
var require_source_map_consumer = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/source-map-consumer.js"(exports) {
    var util = require_util(), binarySearch = require_binary_search(), ArraySet = require_array_set().ArraySet, base64VLQ = require_base64_vlq(), quickSort = require_quick_sort().quickSort;
    function SourceMapConsumer(aSourceMap, aSourceMapURL) {
      var sourceMap = aSourceMap;
      return typeof aSourceMap == "string" && (sourceMap = util.parseSourceMapInput(aSourceMap)), sourceMap.sections != null ? new IndexedSourceMapConsumer(sourceMap, aSourceMapURL) : new BasicSourceMapConsumer(sourceMap, aSourceMapURL);
    }
    SourceMapConsumer.fromSourceMap = function(aSourceMap, aSourceMapURL) {
      return BasicSourceMapConsumer.fromSourceMap(aSourceMap, aSourceMapURL);
    };
    SourceMapConsumer.prototype._version = 3;
    SourceMapConsumer.prototype.__generatedMappings = null;
    Object.defineProperty(SourceMapConsumer.prototype, "_generatedMappings", {
      configurable: !0,
      enumerable: !0,
      get: function() {
        return this.__generatedMappings || this._parseMappings(this._mappings, this.sourceRoot), this.__generatedMappings;
      }
    });
    SourceMapConsumer.prototype.__originalMappings = null;
    Object.defineProperty(SourceMapConsumer.prototype, "_originalMappings", {
      configurable: !0,
      enumerable: !0,
      get: function() {
        return this.__originalMappings || this._parseMappings(this._mappings, this.sourceRoot), this.__originalMappings;
      }
    });
    SourceMapConsumer.prototype._charIsMappingSeparator = function(aStr, index) {
      var c = aStr.charAt(index);
      return c === ";" || c === ",";
    };
    SourceMapConsumer.prototype._parseMappings = function(aStr, aSourceRoot) {
      throw new Error("Subclasses must implement _parseMappings");
    };
    SourceMapConsumer.GENERATED_ORDER = 1;
    SourceMapConsumer.ORIGINAL_ORDER = 2;
    SourceMapConsumer.GREATEST_LOWER_BOUND = 1;
    SourceMapConsumer.LEAST_UPPER_BOUND = 2;
    SourceMapConsumer.prototype.eachMapping = function(aCallback, aContext, aOrder) {
      var context = aContext || null, order = aOrder || SourceMapConsumer.GENERATED_ORDER, mappings;
      switch (order) {
        case SourceMapConsumer.GENERATED_ORDER:
          mappings = this._generatedMappings;
          break;
        case SourceMapConsumer.ORIGINAL_ORDER:
          mappings = this._originalMappings;
          break;
        default:
          throw new Error("Unknown order of iteration.");
      }
      var sourceRoot = this.sourceRoot;
      mappings.map(function(mapping) {
        var source = mapping.source === null ? null : this._sources.at(mapping.source);
        return source = util.computeSourceURL(sourceRoot, source, this._sourceMapURL), {
          source,
          generatedLine: mapping.generatedLine,
          generatedColumn: mapping.generatedColumn,
          originalLine: mapping.originalLine,
          originalColumn: mapping.originalColumn,
          name: mapping.name === null ? null : this._names.at(mapping.name)
        };
      }, this).forEach(aCallback, context);
    };
    SourceMapConsumer.prototype.allGeneratedPositionsFor = function(aArgs) {
      var line = util.getArg(aArgs, "line"), needle = {
        source: util.getArg(aArgs, "source"),
        originalLine: line,
        originalColumn: util.getArg(aArgs, "column", 0)
      };
      if (needle.source = this._findSourceIndex(needle.source), needle.source < 0)
        return [];
      var mappings = [], index = this._findMapping(
        needle,
        this._originalMappings,
        "originalLine",
        "originalColumn",
        util.compareByOriginalPositions,
        binarySearch.LEAST_UPPER_BOUND
      );
      if (index >= 0) {
        var mapping = this._originalMappings[index];
        if (aArgs.column === void 0)
          for (var originalLine = mapping.originalLine; mapping && mapping.originalLine === originalLine; )
            mappings.push({
              line: util.getArg(mapping, "generatedLine", null),
              column: util.getArg(mapping, "generatedColumn", null),
              lastColumn: util.getArg(mapping, "lastGeneratedColumn", null)
            }), mapping = this._originalMappings[++index];
        else
          for (var originalColumn = mapping.originalColumn; mapping && mapping.originalLine === line && mapping.originalColumn == originalColumn; )
            mappings.push({
              line: util.getArg(mapping, "generatedLine", null),
              column: util.getArg(mapping, "generatedColumn", null),
              lastColumn: util.getArg(mapping, "lastGeneratedColumn", null)
            }), mapping = this._originalMappings[++index];
      }
      return mappings;
    };
    exports.SourceMapConsumer = SourceMapConsumer;
    function BasicSourceMapConsumer(aSourceMap, aSourceMapURL) {
      var sourceMap = aSourceMap;
      typeof aSourceMap == "string" && (sourceMap = util.parseSourceMapInput(aSourceMap));
      var version = util.getArg(sourceMap, "version"), sources = util.getArg(sourceMap, "sources"), names = util.getArg(sourceMap, "names", []), sourceRoot = util.getArg(sourceMap, "sourceRoot", null), sourcesContent = util.getArg(sourceMap, "sourcesContent", null), mappings = util.getArg(sourceMap, "mappings"), file = util.getArg(sourceMap, "file", null);
      if (version != this._version)
        throw new Error("Unsupported version: " + version);
      sourceRoot && (sourceRoot = util.normalize(sourceRoot)), sources = sources.map(String).map(util.normalize).map(function(source) {
        return sourceRoot && util.isAbsolute(sourceRoot) && util.isAbsolute(source) ? util.relative(sourceRoot, source) : source;
      }), this._names = ArraySet.fromArray(names.map(String), !0), this._sources = ArraySet.fromArray(sources, !0), this._absoluteSources = this._sources.toArray().map(function(s) {
        return util.computeSourceURL(sourceRoot, s, aSourceMapURL);
      }), this.sourceRoot = sourceRoot, this.sourcesContent = sourcesContent, this._mappings = mappings, this._sourceMapURL = aSourceMapURL, this.file = file;
    }
    BasicSourceMapConsumer.prototype = Object.create(SourceMapConsumer.prototype);
    BasicSourceMapConsumer.prototype.consumer = SourceMapConsumer;
    BasicSourceMapConsumer.prototype._findSourceIndex = function(aSource) {
      var relativeSource = aSource;
      if (this.sourceRoot != null && (relativeSource = util.relative(this.sourceRoot, relativeSource)), this._sources.has(relativeSource))
        return this._sources.indexOf(relativeSource);
      var i;
      for (i = 0; i < this._absoluteSources.length; ++i)
        if (this._absoluteSources[i] == aSource)
          return i;
      return -1;
    };
    BasicSourceMapConsumer.fromSourceMap = function(aSourceMap, aSourceMapURL) {
      var smc = Object.create(BasicSourceMapConsumer.prototype), names = smc._names = ArraySet.fromArray(aSourceMap._names.toArray(), !0), sources = smc._sources = ArraySet.fromArray(aSourceMap._sources.toArray(), !0);
      smc.sourceRoot = aSourceMap._sourceRoot, smc.sourcesContent = aSourceMap._generateSourcesContent(
        smc._sources.toArray(),
        smc.sourceRoot
      ), smc.file = aSourceMap._file, smc._sourceMapURL = aSourceMapURL, smc._absoluteSources = smc._sources.toArray().map(function(s) {
        return util.computeSourceURL(smc.sourceRoot, s, aSourceMapURL);
      });
      for (var generatedMappings = aSourceMap._mappings.toArray().slice(), destGeneratedMappings = smc.__generatedMappings = [], destOriginalMappings = smc.__originalMappings = [], i = 0, length = generatedMappings.length; i < length; i++) {
        var srcMapping = generatedMappings[i], destMapping = new Mapping();
        destMapping.generatedLine = srcMapping.generatedLine, destMapping.generatedColumn = srcMapping.generatedColumn, srcMapping.source && (destMapping.source = sources.indexOf(srcMapping.source), destMapping.originalLine = srcMapping.originalLine, destMapping.originalColumn = srcMapping.originalColumn, srcMapping.name && (destMapping.name = names.indexOf(srcMapping.name)), destOriginalMappings.push(destMapping)), destGeneratedMappings.push(destMapping);
      }
      return quickSort(smc.__originalMappings, util.compareByOriginalPositions), smc;
    };
    BasicSourceMapConsumer.prototype._version = 3;
    Object.defineProperty(BasicSourceMapConsumer.prototype, "sources", {
      get: function() {
        return this._absoluteSources.slice();
      }
    });
    function Mapping() {
      this.generatedLine = 0, this.generatedColumn = 0, this.source = null, this.originalLine = null, this.originalColumn = null, this.name = null;
    }
    BasicSourceMapConsumer.prototype._parseMappings = function(aStr, aSourceRoot) {
      for (var generatedLine = 1, previousGeneratedColumn = 0, previousOriginalLine = 0, previousOriginalColumn = 0, previousSource = 0, previousName = 0, length = aStr.length, index = 0, cachedSegments = {}, temp = {}, originalMappings = [], generatedMappings = [], mapping, str, segment, end, value; index < length; )
        if (aStr.charAt(index) === ";")
          generatedLine++, index++, previousGeneratedColumn = 0;
        else if (aStr.charAt(index) === ",")
          index++;
        else {
          for (mapping = new Mapping(), mapping.generatedLine = generatedLine, end = index; end < length && !this._charIsMappingSeparator(aStr, end); end++)
            ;
          if (str = aStr.slice(index, end), segment = cachedSegments[str], segment)
            index += str.length;
          else {
            for (segment = []; index < end; )
              base64VLQ.decode(aStr, index, temp), value = temp.value, index = temp.rest, segment.push(value);
            if (segment.length === 2)
              throw new Error("Found a source, but no line and column");
            if (segment.length === 3)
              throw new Error("Found a source and line, but no column");
            cachedSegments[str] = segment;
          }
          mapping.generatedColumn = previousGeneratedColumn + segment[0], previousGeneratedColumn = mapping.generatedColumn, segment.length > 1 && (mapping.source = previousSource + segment[1], previousSource += segment[1], mapping.originalLine = previousOriginalLine + segment[2], previousOriginalLine = mapping.originalLine, mapping.originalLine += 1, mapping.originalColumn = previousOriginalColumn + segment[3], previousOriginalColumn = mapping.originalColumn, segment.length > 4 && (mapping.name = previousName + segment[4], previousName += segment[4])), generatedMappings.push(mapping), typeof mapping.originalLine == "number" && originalMappings.push(mapping);
        }
      quickSort(generatedMappings, util.compareByGeneratedPositionsDeflated), this.__generatedMappings = generatedMappings, quickSort(originalMappings, util.compareByOriginalPositions), this.__originalMappings = originalMappings;
    };
    BasicSourceMapConsumer.prototype._findMapping = function(aNeedle, aMappings, aLineName, aColumnName, aComparator, aBias) {
      if (aNeedle[aLineName] <= 0)
        throw new TypeError("Line must be greater than or equal to 1, got " + aNeedle[aLineName]);
      if (aNeedle[aColumnName] < 0)
        throw new TypeError("Column must be greater than or equal to 0, got " + aNeedle[aColumnName]);
      return binarySearch.search(aNeedle, aMappings, aComparator, aBias);
    };
    BasicSourceMapConsumer.prototype.computeColumnSpans = function() {
      for (var index = 0; index < this._generatedMappings.length; ++index) {
        var mapping = this._generatedMappings[index];
        if (index + 1 < this._generatedMappings.length) {
          var nextMapping = this._generatedMappings[index + 1];
          if (mapping.generatedLine === nextMapping.generatedLine) {
            mapping.lastGeneratedColumn = nextMapping.generatedColumn - 1;
            continue;
          }
        }
        mapping.lastGeneratedColumn = 1 / 0;
      }
    };
    BasicSourceMapConsumer.prototype.originalPositionFor = function(aArgs) {
      var needle = {
        generatedLine: util.getArg(aArgs, "line"),
        generatedColumn: util.getArg(aArgs, "column")
      }, index = this._findMapping(
        needle,
        this._generatedMappings,
        "generatedLine",
        "generatedColumn",
        util.compareByGeneratedPositionsDeflated,
        util.getArg(aArgs, "bias", SourceMapConsumer.GREATEST_LOWER_BOUND)
      );
      if (index >= 0) {
        var mapping = this._generatedMappings[index];
        if (mapping.generatedLine === needle.generatedLine) {
          var source = util.getArg(mapping, "source", null);
          source !== null && (source = this._sources.at(source), source = util.computeSourceURL(this.sourceRoot, source, this._sourceMapURL));
          var name = util.getArg(mapping, "name", null);
          return name !== null && (name = this._names.at(name)), {
            source,
            line: util.getArg(mapping, "originalLine", null),
            column: util.getArg(mapping, "originalColumn", null),
            name
          };
        }
      }
      return {
        source: null,
        line: null,
        column: null,
        name: null
      };
    };
    BasicSourceMapConsumer.prototype.hasContentsOfAllSources = function() {
      return this.sourcesContent ? this.sourcesContent.length >= this._sources.size() && !this.sourcesContent.some(function(sc) {
        return sc == null;
      }) : !1;
    };
    BasicSourceMapConsumer.prototype.sourceContentFor = function(aSource, nullOnMissing) {
      if (!this.sourcesContent)
        return null;
      var index = this._findSourceIndex(aSource);
      if (index >= 0)
        return this.sourcesContent[index];
      var relativeSource = aSource;
      this.sourceRoot != null && (relativeSource = util.relative(this.sourceRoot, relativeSource));
      var url;
      if (this.sourceRoot != null && (url = util.urlParse(this.sourceRoot))) {
        var fileUriAbsPath = relativeSource.replace(/^file:\/\//, "");
        if (url.scheme == "file" && this._sources.has(fileUriAbsPath))
          return this.sourcesContent[this._sources.indexOf(fileUriAbsPath)];
        if ((!url.path || url.path == "/") && this._sources.has("/" + relativeSource))
          return this.sourcesContent[this._sources.indexOf("/" + relativeSource)];
      }
      if (nullOnMissing)
        return null;
      throw new Error('"' + relativeSource + '" is not in the SourceMap.');
    };
    BasicSourceMapConsumer.prototype.generatedPositionFor = function(aArgs) {
      var source = util.getArg(aArgs, "source");
      if (source = this._findSourceIndex(source), source < 0)
        return {
          line: null,
          column: null,
          lastColumn: null
        };
      var needle = {
        source,
        originalLine: util.getArg(aArgs, "line"),
        originalColumn: util.getArg(aArgs, "column")
      }, index = this._findMapping(
        needle,
        this._originalMappings,
        "originalLine",
        "originalColumn",
        util.compareByOriginalPositions,
        util.getArg(aArgs, "bias", SourceMapConsumer.GREATEST_LOWER_BOUND)
      );
      if (index >= 0) {
        var mapping = this._originalMappings[index];
        if (mapping.source === needle.source)
          return {
            line: util.getArg(mapping, "generatedLine", null),
            column: util.getArg(mapping, "generatedColumn", null),
            lastColumn: util.getArg(mapping, "lastGeneratedColumn", null)
          };
      }
      return {
        line: null,
        column: null,
        lastColumn: null
      };
    };
    exports.BasicSourceMapConsumer = BasicSourceMapConsumer;
    function IndexedSourceMapConsumer(aSourceMap, aSourceMapURL) {
      var sourceMap = aSourceMap;
      typeof aSourceMap == "string" && (sourceMap = util.parseSourceMapInput(aSourceMap));
      var version = util.getArg(sourceMap, "version"), sections = util.getArg(sourceMap, "sections");
      if (version != this._version)
        throw new Error("Unsupported version: " + version);
      this._sources = new ArraySet(), this._names = new ArraySet();
      var lastOffset = {
        line: -1,
        column: 0
      };
      this._sections = sections.map(function(s) {
        if (s.url)
          throw new Error("Support for url field in sections not implemented.");
        var offset = util.getArg(s, "offset"), offsetLine = util.getArg(offset, "line"), offsetColumn = util.getArg(offset, "column");
        if (offsetLine < lastOffset.line || offsetLine === lastOffset.line && offsetColumn < lastOffset.column)
          throw new Error("Section offsets must be ordered and non-overlapping.");
        return lastOffset = offset, {
          generatedOffset: {
            // The offset fields are 0-based, but we use 1-based indices when
            // encoding/decoding from VLQ.
            generatedLine: offsetLine + 1,
            generatedColumn: offsetColumn + 1
          },
          consumer: new SourceMapConsumer(util.getArg(s, "map"), aSourceMapURL)
        };
      });
    }
    IndexedSourceMapConsumer.prototype = Object.create(SourceMapConsumer.prototype);
    IndexedSourceMapConsumer.prototype.constructor = SourceMapConsumer;
    IndexedSourceMapConsumer.prototype._version = 3;
    Object.defineProperty(IndexedSourceMapConsumer.prototype, "sources", {
      get: function() {
        for (var sources = [], i = 0; i < this._sections.length; i++)
          for (var j = 0; j < this._sections[i].consumer.sources.length; j++)
            sources.push(this._sections[i].consumer.sources[j]);
        return sources;
      }
    });
    IndexedSourceMapConsumer.prototype.originalPositionFor = function(aArgs) {
      var needle = {
        generatedLine: util.getArg(aArgs, "line"),
        generatedColumn: util.getArg(aArgs, "column")
      }, sectionIndex = binarySearch.search(
        needle,
        this._sections,
        function(needle2, section2) {
          var cmp = needle2.generatedLine - section2.generatedOffset.generatedLine;
          return cmp || needle2.generatedColumn - section2.generatedOffset.generatedColumn;
        }
      ), section = this._sections[sectionIndex];
      return section ? section.consumer.originalPositionFor({
        line: needle.generatedLine - (section.generatedOffset.generatedLine - 1),
        column: needle.generatedColumn - (section.generatedOffset.generatedLine === needle.generatedLine ? section.generatedOffset.generatedColumn - 1 : 0),
        bias: aArgs.bias
      }) : {
        source: null,
        line: null,
        column: null,
        name: null
      };
    };
    IndexedSourceMapConsumer.prototype.hasContentsOfAllSources = function() {
      return this._sections.every(function(s) {
        return s.consumer.hasContentsOfAllSources();
      });
    };
    IndexedSourceMapConsumer.prototype.sourceContentFor = function(aSource, nullOnMissing) {
      for (var i = 0; i < this._sections.length; i++) {
        var section = this._sections[i], content = section.consumer.sourceContentFor(aSource, !0);
        if (content)
          return content;
      }
      if (nullOnMissing)
        return null;
      throw new Error('"' + aSource + '" is not in the SourceMap.');
    };
    IndexedSourceMapConsumer.prototype.generatedPositionFor = function(aArgs) {
      for (var i = 0; i < this._sections.length; i++) {
        var section = this._sections[i];
        if (section.consumer._findSourceIndex(util.getArg(aArgs, "source")) !== -1) {
          var generatedPosition = section.consumer.generatedPositionFor(aArgs);
          if (generatedPosition) {
            var ret = {
              line: generatedPosition.line + (section.generatedOffset.generatedLine - 1),
              column: generatedPosition.column + (section.generatedOffset.generatedLine === generatedPosition.line ? section.generatedOffset.generatedColumn - 1 : 0)
            };
            return ret;
          }
        }
      }
      return {
        line: null,
        column: null
      };
    };
    IndexedSourceMapConsumer.prototype._parseMappings = function(aStr, aSourceRoot) {
      this.__generatedMappings = [], this.__originalMappings = [];
      for (var i = 0; i < this._sections.length; i++)
        for (var section = this._sections[i], sectionMappings = section.consumer._generatedMappings, j = 0; j < sectionMappings.length; j++) {
          var mapping = sectionMappings[j], source = section.consumer._sources.at(mapping.source);
          source = util.computeSourceURL(section.consumer.sourceRoot, source, this._sourceMapURL), this._sources.add(source), source = this._sources.indexOf(source);
          var name = null;
          mapping.name && (name = section.consumer._names.at(mapping.name), this._names.add(name), name = this._names.indexOf(name));
          var adjustedMapping = {
            source,
            generatedLine: mapping.generatedLine + (section.generatedOffset.generatedLine - 1),
            generatedColumn: mapping.generatedColumn + (section.generatedOffset.generatedLine === mapping.generatedLine ? section.generatedOffset.generatedColumn - 1 : 0),
            originalLine: mapping.originalLine,
            originalColumn: mapping.originalColumn,
            name
          };
          this.__generatedMappings.push(adjustedMapping), typeof adjustedMapping.originalLine == "number" && this.__originalMappings.push(adjustedMapping);
        }
      quickSort(this.__generatedMappings, util.compareByGeneratedPositionsDeflated), quickSort(this.__originalMappings, util.compareByOriginalPositions);
    };
    exports.IndexedSourceMapConsumer = IndexedSourceMapConsumer;
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/lib/source-node.js
var require_source_node = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/lib/source-node.js"(exports) {
    var SourceMapGenerator = require_source_map_generator().SourceMapGenerator, util = require_util(), REGEX_NEWLINE = /(\r?\n)/, NEWLINE_CODE = 10, isSourceNode = "$$$isSourceNode$$$";
    function SourceNode(aLine, aColumn, aSource, aChunks, aName) {
      this.children = [], this.sourceContents = {}, this.line = aLine ?? null, this.column = aColumn ?? null, this.source = aSource ?? null, this.name = aName ?? null, this[isSourceNode] = !0, aChunks != null && this.add(aChunks);
    }
    SourceNode.fromStringWithSourceMap = function(aGeneratedCode, aSourceMapConsumer, aRelativePath) {
      var node = new SourceNode(), remainingLines = aGeneratedCode.split(REGEX_NEWLINE), remainingLinesIndex = 0, shiftNextLine = function() {
        var lineContents = getNextLine(), newLine = getNextLine() || "";
        return lineContents + newLine;
        function getNextLine() {
          return remainingLinesIndex < remainingLines.length ? remainingLines[remainingLinesIndex++] : void 0;
        }
      }, lastGeneratedLine = 1, lastGeneratedColumn = 0, lastMapping = null;
      return aSourceMapConsumer.eachMapping(function(mapping) {
        if (lastMapping !== null)
          if (lastGeneratedLine < mapping.generatedLine)
            addMappingWithCode(lastMapping, shiftNextLine()), lastGeneratedLine++, lastGeneratedColumn = 0;
          else {
            var nextLine = remainingLines[remainingLinesIndex] || "", code = nextLine.substr(0, mapping.generatedColumn - lastGeneratedColumn);
            remainingLines[remainingLinesIndex] = nextLine.substr(mapping.generatedColumn - lastGeneratedColumn), lastGeneratedColumn = mapping.generatedColumn, addMappingWithCode(lastMapping, code), lastMapping = mapping;
            return;
          }
        for (; lastGeneratedLine < mapping.generatedLine; )
          node.add(shiftNextLine()), lastGeneratedLine++;
        if (lastGeneratedColumn < mapping.generatedColumn) {
          var nextLine = remainingLines[remainingLinesIndex] || "";
          node.add(nextLine.substr(0, mapping.generatedColumn)), remainingLines[remainingLinesIndex] = nextLine.substr(mapping.generatedColumn), lastGeneratedColumn = mapping.generatedColumn;
        }
        lastMapping = mapping;
      }, this), remainingLinesIndex < remainingLines.length && (lastMapping && addMappingWithCode(lastMapping, shiftNextLine()), node.add(remainingLines.splice(remainingLinesIndex).join(""))), aSourceMapConsumer.sources.forEach(function(sourceFile) {
        var content = aSourceMapConsumer.sourceContentFor(sourceFile);
        content != null && (aRelativePath != null && (sourceFile = util.join(aRelativePath, sourceFile)), node.setSourceContent(sourceFile, content));
      }), node;
      function addMappingWithCode(mapping, code) {
        if (mapping === null || mapping.source === void 0)
          node.add(code);
        else {
          var source = aRelativePath ? util.join(aRelativePath, mapping.source) : mapping.source;
          node.add(new SourceNode(
            mapping.originalLine,
            mapping.originalColumn,
            source,
            code,
            mapping.name
          ));
        }
      }
    };
    SourceNode.prototype.add = function(aChunk) {
      if (Array.isArray(aChunk))
        aChunk.forEach(function(chunk) {
          this.add(chunk);
        }, this);
      else if (aChunk[isSourceNode] || typeof aChunk == "string")
        aChunk && this.children.push(aChunk);
      else
        throw new TypeError(
          "Expected a SourceNode, string, or an array of SourceNodes and strings. Got " + aChunk
        );
      return this;
    };
    SourceNode.prototype.prepend = function(aChunk) {
      if (Array.isArray(aChunk))
        for (var i = aChunk.length - 1; i >= 0; i--)
          this.prepend(aChunk[i]);
      else if (aChunk[isSourceNode] || typeof aChunk == "string")
        this.children.unshift(aChunk);
      else
        throw new TypeError(
          "Expected a SourceNode, string, or an array of SourceNodes and strings. Got " + aChunk
        );
      return this;
    };
    SourceNode.prototype.walk = function(aFn) {
      for (var chunk, i = 0, len = this.children.length; i < len; i++)
        chunk = this.children[i], chunk[isSourceNode] ? chunk.walk(aFn) : chunk !== "" && aFn(chunk, {
          source: this.source,
          line: this.line,
          column: this.column,
          name: this.name
        });
    };
    SourceNode.prototype.join = function(aSep) {
      var newChildren, i, len = this.children.length;
      if (len > 0) {
        for (newChildren = [], i = 0; i < len - 1; i++)
          newChildren.push(this.children[i]), newChildren.push(aSep);
        newChildren.push(this.children[i]), this.children = newChildren;
      }
      return this;
    };
    SourceNode.prototype.replaceRight = function(aPattern, aReplacement) {
      var lastChild = this.children[this.children.length - 1];
      return lastChild[isSourceNode] ? lastChild.replaceRight(aPattern, aReplacement) : typeof lastChild == "string" ? this.children[this.children.length - 1] = lastChild.replace(aPattern, aReplacement) : this.children.push("".replace(aPattern, aReplacement)), this;
    };
    SourceNode.prototype.setSourceContent = function(aSourceFile, aSourceContent) {
      this.sourceContents[util.toSetString(aSourceFile)] = aSourceContent;
    };
    SourceNode.prototype.walkSourceContents = function(aFn) {
      for (var i = 0, len = this.children.length; i < len; i++)
        this.children[i][isSourceNode] && this.children[i].walkSourceContents(aFn);
      for (var sources = Object.keys(this.sourceContents), i = 0, len = sources.length; i < len; i++)
        aFn(util.fromSetString(sources[i]), this.sourceContents[sources[i]]);
    };
    SourceNode.prototype.toString = function() {
      var str = "";
      return this.walk(function(chunk) {
        str += chunk;
      }), str;
    };
    SourceNode.prototype.toStringWithSourceMap = function(aArgs) {
      var generated = {
        code: "",
        line: 1,
        column: 0
      }, map = new SourceMapGenerator(aArgs), sourceMappingActive = !1, lastOriginalSource = null, lastOriginalLine = null, lastOriginalColumn = null, lastOriginalName = null;
      return this.walk(function(chunk, original) {
        generated.code += chunk, original.source !== null && original.line !== null && original.column !== null ? ((lastOriginalSource !== original.source || lastOriginalLine !== original.line || lastOriginalColumn !== original.column || lastOriginalName !== original.name) && map.addMapping({
          source: original.source,
          original: {
            line: original.line,
            column: original.column
          },
          generated: {
            line: generated.line,
            column: generated.column
          },
          name: original.name
        }), lastOriginalSource = original.source, lastOriginalLine = original.line, lastOriginalColumn = original.column, lastOriginalName = original.name, sourceMappingActive = !0) : sourceMappingActive && (map.addMapping({
          generated: {
            line: generated.line,
            column: generated.column
          }
        }), lastOriginalSource = null, sourceMappingActive = !1);
        for (var idx = 0, length = chunk.length; idx < length; idx++)
          chunk.charCodeAt(idx) === NEWLINE_CODE ? (generated.line++, generated.column = 0, idx + 1 === length ? (lastOriginalSource = null, sourceMappingActive = !1) : sourceMappingActive && map.addMapping({
            source: original.source,
            original: {
              line: original.line,
              column: original.column
            },
            generated: {
              line: generated.line,
              column: generated.column
            },
            name: original.name
          })) : generated.column++;
      }), this.walkSourceContents(function(sourceFile, sourceContent) {
        map.setSourceContent(sourceFile, sourceContent);
      }), { code: generated.code, map };
    };
    exports.SourceNode = SourceNode;
  }
});

// ../../../node_modules/escodegen/node_modules/source-map/source-map.js
var require_source_map = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/node_modules/source-map/source-map.js"(exports) {
    exports.SourceMapGenerator = require_source_map_generator().SourceMapGenerator;
    exports.SourceMapConsumer = require_source_map_consumer().SourceMapConsumer;
    exports.SourceNode = require_source_node().SourceNode;
  }
});

// ../../../node_modules/escodegen/package.json
var require_package = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/package.json"(exports, module) {
    module.exports = {
      name: "escodegen",
      description: "ECMAScript code generator",
      homepage: "http://github.com/estools/escodegen",
      main: "escodegen.js",
      bin: {
        esgenerate: "./bin/esgenerate.js",
        escodegen: "./bin/escodegen.js"
      },
      files: [
        "LICENSE.BSD",
        "README.md",
        "bin",
        "escodegen.js",
        "package.json"
      ],
      version: "2.1.0",
      engines: {
        node: ">=6.0"
      },
      maintainers: [
        {
          name: "Yusuke Suzuki",
          email: "utatane.tea@gmail.com",
          web: "http://github.com/Constellation"
        }
      ],
      repository: {
        type: "git",
        url: "http://github.com/estools/escodegen.git"
      },
      dependencies: {
        estraverse: "^5.2.0",
        esutils: "^2.0.2",
        esprima: "^4.0.1"
      },
      optionalDependencies: {
        "source-map": "~0.6.1"
      },
      devDependencies: {
        acorn: "^8.0.4",
        bluebird: "^3.4.7",
        "bower-registry-client": "^1.0.0",
        chai: "^4.2.0",
        "chai-exclude": "^2.0.2",
        "commonjs-everywhere": "^0.9.7",
        gulp: "^4.0.2",
        "gulp-eslint": "^6.0.0",
        "gulp-mocha": "^7.0.2",
        minimist: "^1.2.5",
        optionator: "^0.9.1",
        semver: "^7.3.4"
      },
      license: "BSD-2-Clause",
      scripts: {
        test: "gulp travis",
        "unit-test": "gulp test",
        lint: "gulp lint",
        release: "node tools/release.js",
        "build-min": "./node_modules/.bin/cjsify -ma path: tools/entry-point.js > escodegen.browser.min.js",
        build: "./node_modules/.bin/cjsify -a path: tools/entry-point.js > escodegen.browser.js"
      }
    };
  }
});

// ../../../node_modules/escodegen/escodegen.js
var require_escodegen = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/escodegen/escodegen.js"(exports) {
    (function() {
      "use strict";
      var Syntax, Precedence, BinaryPrecedence, SourceNode, estraverse, esutils, base2, indent, json, renumber, hexadecimal, quotes, escapeless, newline, space, parentheses, semicolons, safeConcatenation, directive, extra, parse2, sourceMap, sourceCode, preserveBlankLines, FORMAT_MINIFY, FORMAT_DEFAULTS;
      estraverse = require_estraverse(), esutils = require_utils(), Syntax = estraverse.Syntax;
      function isExpression(node) {
        return CodeGenerator.Expression.hasOwnProperty(node.type);
      }
      function isStatement(node) {
        return CodeGenerator.Statement.hasOwnProperty(node.type);
      }
      Precedence = {
        Sequence: 0,
        Yield: 1,
        Assignment: 1,
        Conditional: 2,
        ArrowFunction: 2,
        Coalesce: 3,
        LogicalOR: 4,
        LogicalAND: 5,
        BitwiseOR: 6,
        BitwiseXOR: 7,
        BitwiseAND: 8,
        Equality: 9,
        Relational: 10,
        BitwiseSHIFT: 11,
        Additive: 12,
        Multiplicative: 13,
        Exponentiation: 14,
        Await: 15,
        Unary: 15,
        Postfix: 16,
        OptionalChaining: 17,
        Call: 18,
        New: 19,
        TaggedTemplate: 20,
        Member: 21,
        Primary: 22
      }, BinaryPrecedence = {
        "??": Precedence.Coalesce,
        "||": Precedence.LogicalOR,
        "&&": Precedence.LogicalAND,
        "|": Precedence.BitwiseOR,
        "^": Precedence.BitwiseXOR,
        "&": Precedence.BitwiseAND,
        "==": Precedence.Equality,
        "!=": Precedence.Equality,
        "===": Precedence.Equality,
        "!==": Precedence.Equality,
        is: Precedence.Equality,
        isnt: Precedence.Equality,
        "<": Precedence.Relational,
        ">": Precedence.Relational,
        "<=": Precedence.Relational,
        ">=": Precedence.Relational,
        in: Precedence.Relational,
        instanceof: Precedence.Relational,
        "<<": Precedence.BitwiseSHIFT,
        ">>": Precedence.BitwiseSHIFT,
        ">>>": Precedence.BitwiseSHIFT,
        "+": Precedence.Additive,
        "-": Precedence.Additive,
        "*": Precedence.Multiplicative,
        "%": Precedence.Multiplicative,
        "/": Precedence.Multiplicative,
        "**": Precedence.Exponentiation
      };
      var F_ALLOW_IN = 1, F_ALLOW_CALL = 2, F_ALLOW_UNPARATH_NEW = 4, F_FUNC_BODY = 8, F_DIRECTIVE_CTX = 16, F_SEMICOLON_OPT = 32, F_FOUND_COALESCE = 64, E_FTT = F_ALLOW_CALL | F_ALLOW_UNPARATH_NEW, E_TTF = F_ALLOW_IN | F_ALLOW_CALL, E_TTT = F_ALLOW_IN | F_ALLOW_CALL | F_ALLOW_UNPARATH_NEW, E_TFF = F_ALLOW_IN, E_FFT = F_ALLOW_UNPARATH_NEW, E_TFT = F_ALLOW_IN | F_ALLOW_UNPARATH_NEW, S_TFFF = F_ALLOW_IN, S_TFFT = F_ALLOW_IN | F_SEMICOLON_OPT, S_FFFF = 0, S_TFTF = F_ALLOW_IN | F_DIRECTIVE_CTX, S_TTFF = F_ALLOW_IN | F_FUNC_BODY;
      function getDefaultOptions() {
        return {
          indent: null,
          base: null,
          parse: null,
          comment: !1,
          format: {
            indent: {
              style: "    ",
              base: 0,
              adjustMultilineComment: !1
            },
            newline: `
`,
            space: " ",
            json: !1,
            renumber: !1,
            hexadecimal: !1,
            quotes: "single",
            escapeless: !1,
            compact: !1,
            parentheses: !0,
            semicolons: !0,
            safeConcatenation: !1,
            preserveBlankLines: !1
          },
          moz: {
            comprehensionExpressionStartsWithAssignment: !1,
            starlessGenerator: !1
          },
          sourceMap: null,
          sourceMapRoot: null,
          sourceMapWithCode: !1,
          directive: !1,
          raw: !0,
          verbatim: null,
          sourceCode: null
        };
      }
      function stringRepeat(str, num) {
        var result = "";
        for (num |= 0; num > 0; num >>>= 1, str += str)
          num & 1 && (result += str);
        return result;
      }
      function hasLineTerminator(str) {
        return /[\r\n]/g.test(str);
      }
      function endsWithLineTerminator(str) {
        var len = str.length;
        return len && esutils.code.isLineTerminator(str.charCodeAt(len - 1));
      }
      function merge(target, override) {
        var key;
        for (key in override)
          override.hasOwnProperty(key) && (target[key] = override[key]);
        return target;
      }
      function updateDeeply(target, override) {
        var key, val;
        function isHashObject(target2) {
          return typeof target2 == "object" && target2 instanceof Object && !(target2 instanceof RegExp);
        }
        for (key in override)
          override.hasOwnProperty(key) && (val = override[key], isHashObject(val) ? isHashObject(target[key]) ? updateDeeply(target[key], val) : target[key] = updateDeeply({}, val) : target[key] = val);
        return target;
      }
      function generateNumber(value) {
        var result, point, temp, exponent, pos;
        if (value !== value)
          throw new Error("Numeric literal whose value is NaN");
        if (value < 0 || value === 0 && 1 / value < 0)
          throw new Error("Numeric literal whose value is negative");
        if (value === 1 / 0)
          return json ? "null" : renumber ? "1e400" : "1e+400";
        if (result = "" + value, !renumber || result.length < 3)
          return result;
        for (point = result.indexOf("."), !json && result.charCodeAt(0) === 48 && point === 1 && (point = 0, result = result.slice(1)), temp = result, result = result.replace("e+", "e"), exponent = 0, (pos = temp.indexOf("e")) > 0 && (exponent = +temp.slice(pos + 1), temp = temp.slice(0, pos)), point >= 0 && (exponent -= temp.length - point - 1, temp = +(temp.slice(0, point) + temp.slice(point + 1)) + ""), pos = 0; temp.charCodeAt(temp.length + pos - 1) === 48; )
          --pos;
        return pos !== 0 && (exponent -= pos, temp = temp.slice(0, pos)), exponent !== 0 && (temp += "e" + exponent), (temp.length < result.length || hexadecimal && value > 1e12 && Math.floor(value) === value && (temp = "0x" + value.toString(16)).length < result.length) && +temp === value && (result = temp), result;
      }
      function escapeRegExpCharacter(ch, previousIsBackslash) {
        return (ch & -2) === 8232 ? (previousIsBackslash ? "u" : "\\u") + (ch === 8232 ? "2028" : "2029") : ch === 10 || ch === 13 ? (previousIsBackslash ? "" : "\\") + (ch === 10 ? "n" : "r") : String.fromCharCode(ch);
      }
      function generateRegExp(reg) {
        var match, result, flags, i, iz, ch, characterInBrack, previousIsBackslash;
        if (result = reg.toString(), reg.source) {
          if (match = result.match(/\/([^/]*)$/), !match)
            return result;
          for (flags = match[1], result = "", characterInBrack = !1, previousIsBackslash = !1, i = 0, iz = reg.source.length; i < iz; ++i)
            ch = reg.source.charCodeAt(i), previousIsBackslash ? (result += escapeRegExpCharacter(ch, previousIsBackslash), previousIsBackslash = !1) : (characterInBrack ? ch === 93 && (characterInBrack = !1) : ch === 47 ? result += "\\" : ch === 91 && (characterInBrack = !0), result += escapeRegExpCharacter(ch, previousIsBackslash), previousIsBackslash = ch === 92);
          return "/" + result + "/" + flags;
        }
        return result;
      }
      function escapeAllowedCharacter(code, next) {
        var hex;
        return code === 8 ? "\\b" : code === 12 ? "\\f" : code === 9 ? "\\t" : (hex = code.toString(16).toUpperCase(), json || code > 255 ? "\\u" + "0000".slice(hex.length) + hex : code === 0 && !esutils.code.isDecimalDigit(next) ? "\\0" : code === 11 ? "\\x0B" : "\\x" + "00".slice(hex.length) + hex);
      }
      function escapeDisallowedCharacter(code) {
        if (code === 92)
          return "\\\\";
        if (code === 10)
          return "\\n";
        if (code === 13)
          return "\\r";
        if (code === 8232)
          return "\\u2028";
        if (code === 8233)
          return "\\u2029";
        throw new Error("Incorrectly classified character");
      }
      function escapeDirective(str) {
        var i, iz, code, quote;
        for (quote = quotes === "double" ? '"' : "'", i = 0, iz = str.length; i < iz; ++i)
          if (code = str.charCodeAt(i), code === 39) {
            quote = '"';
            break;
          } else if (code === 34) {
            quote = "'";
            break;
          } else code === 92 && ++i;
        return quote + str + quote;
      }
      function escapeString(str) {
        var result = "", i, len, code, singleQuotes = 0, doubleQuotes = 0, single, quote;
        for (i = 0, len = str.length; i < len; ++i) {
          if (code = str.charCodeAt(i), code === 39)
            ++singleQuotes;
          else if (code === 34)
            ++doubleQuotes;
          else if (code === 47 && json)
            result += "\\";
          else if (esutils.code.isLineTerminator(code) || code === 92) {
            result += escapeDisallowedCharacter(code);
            continue;
          } else if (!esutils.code.isIdentifierPartES5(code) && (json && code < 32 || !json && !escapeless && (code < 32 || code > 126))) {
            result += escapeAllowedCharacter(code, str.charCodeAt(i + 1));
            continue;
          }
          result += String.fromCharCode(code);
        }
        if (single = !(quotes === "double" || quotes === "auto" && doubleQuotes < singleQuotes), quote = single ? "'" : '"', !(single ? singleQuotes : doubleQuotes))
          return quote + result + quote;
        for (str = result, result = quote, i = 0, len = str.length; i < len; ++i)
          code = str.charCodeAt(i), (code === 39 && single || code === 34 && !single) && (result += "\\"), result += String.fromCharCode(code);
        return result + quote;
      }
      function flattenToString(arr) {
        var i, iz, elem, result = "";
        for (i = 0, iz = arr.length; i < iz; ++i)
          elem = arr[i], result += Array.isArray(elem) ? flattenToString(elem) : elem;
        return result;
      }
      function toSourceNodeWhenNeeded(generated, node) {
        if (!sourceMap)
          return Array.isArray(generated) ? flattenToString(generated) : generated;
        if (node == null) {
          if (generated instanceof SourceNode)
            return generated;
          node = {};
        }
        return node.loc == null ? new SourceNode(null, null, sourceMap, generated, node.name || null) : new SourceNode(node.loc.start.line, node.loc.start.column, sourceMap === !0 ? node.loc.source || null : sourceMap, generated, node.name || null);
      }
      function noEmptySpace() {
        return space || " ";
      }
      function join(left, right) {
        var leftSource, rightSource, leftCharCode, rightCharCode;
        return leftSource = toSourceNodeWhenNeeded(left).toString(), leftSource.length === 0 ? [right] : (rightSource = toSourceNodeWhenNeeded(right).toString(), rightSource.length === 0 ? [left] : (leftCharCode = leftSource.charCodeAt(leftSource.length - 1), rightCharCode = rightSource.charCodeAt(0), (leftCharCode === 43 || leftCharCode === 45) && leftCharCode === rightCharCode || esutils.code.isIdentifierPartES5(leftCharCode) && esutils.code.isIdentifierPartES5(rightCharCode) || leftCharCode === 47 && rightCharCode === 105 ? [left, noEmptySpace(), right] : esutils.code.isWhiteSpace(leftCharCode) || esutils.code.isLineTerminator(leftCharCode) || esutils.code.isWhiteSpace(rightCharCode) || esutils.code.isLineTerminator(rightCharCode) ? [left, right] : [left, space, right]));
      }
      function addIndent(stmt) {
        return [base2, stmt];
      }
      function withIndent(fn) {
        var previousBase;
        previousBase = base2, base2 += indent, fn(base2), base2 = previousBase;
      }
      function calculateSpaces(str) {
        var i;
        for (i = str.length - 1; i >= 0 && !esutils.code.isLineTerminator(str.charCodeAt(i)); --i)
          ;
        return str.length - 1 - i;
      }
      function adjustMultilineComment(value, specialBase) {
        var array, i, len, line, j, spaces, previousBase, sn;
        for (array = value.split(/\r\n|[\r\n]/), spaces = Number.MAX_VALUE, i = 1, len = array.length; i < len; ++i) {
          for (line = array[i], j = 0; j < line.length && esutils.code.isWhiteSpace(line.charCodeAt(j)); )
            ++j;
          spaces > j && (spaces = j);
        }
        for (typeof specialBase < "u" ? (previousBase = base2, array[1][spaces] === "*" && (specialBase += " "), base2 = specialBase) : (spaces & 1 && --spaces, previousBase = base2), i = 1, len = array.length; i < len; ++i)
          sn = toSourceNodeWhenNeeded(addIndent(array[i].slice(spaces))), array[i] = sourceMap ? sn.join("") : sn;
        return base2 = previousBase, array.join(`
`);
      }
      function generateComment(comment, specialBase) {
        if (comment.type === "Line") {
          if (endsWithLineTerminator(comment.value))
            return "//" + comment.value;
          var result = "//" + comment.value;
          return preserveBlankLines || (result += `
`), result;
        }
        return extra.format.indent.adjustMultilineComment && /[\n\r]/.test(comment.value) ? adjustMultilineComment("/*" + comment.value + "*/", specialBase) : "/*" + comment.value + "*/";
      }
      function addComments(stmt, result) {
        var i, len, comment, save, tailingToStatement, specialBase, fragment, extRange, range, prevRange, prefix, infix, suffix, count;
        if (stmt.leadingComments && stmt.leadingComments.length > 0) {
          if (save = result, preserveBlankLines) {
            for (comment = stmt.leadingComments[0], result = [], extRange = comment.extendedRange, range = comment.range, prefix = sourceCode.substring(extRange[0], range[0]), count = (prefix.match(/\n/g) || []).length, count > 0 ? (result.push(stringRepeat(`
`, count)), result.push(addIndent(generateComment(comment)))) : (result.push(prefix), result.push(generateComment(comment))), prevRange = range, i = 1, len = stmt.leadingComments.length; i < len; i++)
              comment = stmt.leadingComments[i], range = comment.range, infix = sourceCode.substring(prevRange[1], range[0]), count = (infix.match(/\n/g) || []).length, result.push(stringRepeat(`
`, count)), result.push(addIndent(generateComment(comment))), prevRange = range;
            suffix = sourceCode.substring(range[1], extRange[1]), count = (suffix.match(/\n/g) || []).length, result.push(stringRepeat(`
`, count));
          } else
            for (comment = stmt.leadingComments[0], result = [], safeConcatenation && stmt.type === Syntax.Program && stmt.body.length === 0 && result.push(`
`), result.push(generateComment(comment)), endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) || result.push(`
`), i = 1, len = stmt.leadingComments.length; i < len; ++i)
              comment = stmt.leadingComments[i], fragment = [generateComment(comment)], endsWithLineTerminator(toSourceNodeWhenNeeded(fragment).toString()) || fragment.push(`
`), result.push(addIndent(fragment));
          result.push(addIndent(save));
        }
        if (stmt.trailingComments)
          if (preserveBlankLines)
            comment = stmt.trailingComments[0], extRange = comment.extendedRange, range = comment.range, prefix = sourceCode.substring(extRange[0], range[0]), count = (prefix.match(/\n/g) || []).length, count > 0 ? (result.push(stringRepeat(`
`, count)), result.push(addIndent(generateComment(comment)))) : (result.push(prefix), result.push(generateComment(comment)));
          else
            for (tailingToStatement = !endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()), specialBase = stringRepeat(" ", calculateSpaces(toSourceNodeWhenNeeded([base2, result, indent]).toString())), i = 0, len = stmt.trailingComments.length; i < len; ++i)
              comment = stmt.trailingComments[i], tailingToStatement ? (i === 0 ? result = [result, indent] : result = [result, specialBase], result.push(generateComment(comment, specialBase))) : result = [result, addIndent(generateComment(comment))], i !== len - 1 && !endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) && (result = [result, `
`]);
        return result;
      }
      function generateBlankLines(start, end, result) {
        var j, newlineCount = 0;
        for (j = start; j < end; j++)
          sourceCode[j] === `
` && newlineCount++;
        for (j = 1; j < newlineCount; j++)
          result.push(newline);
      }
      function parenthesize(text, current, should) {
        return current < should ? ["(", text, ")"] : text;
      }
      function generateVerbatimString(string) {
        var i, iz, result;
        for (result = string.split(/\r\n|\n/), i = 1, iz = result.length; i < iz; i++)
          result[i] = newline + base2 + result[i];
        return result;
      }
      function generateVerbatim(expr, precedence) {
        var verbatim, result, prec;
        return verbatim = expr[extra.verbatim], typeof verbatim == "string" ? result = parenthesize(generateVerbatimString(verbatim), Precedence.Sequence, precedence) : (result = generateVerbatimString(verbatim.content), prec = verbatim.precedence != null ? verbatim.precedence : Precedence.Sequence, result = parenthesize(result, prec, precedence)), toSourceNodeWhenNeeded(result, expr);
      }
      function CodeGenerator() {
      }
      CodeGenerator.prototype.maybeBlock = function(stmt, flags) {
        var result, noLeadingComment, that = this;
        return noLeadingComment = !extra.comment || !stmt.leadingComments, stmt.type === Syntax.BlockStatement && noLeadingComment ? [space, this.generateStatement(stmt, flags)] : stmt.type === Syntax.EmptyStatement && noLeadingComment ? ";" : (withIndent(function() {
          result = [
            newline,
            addIndent(that.generateStatement(stmt, flags))
          ];
        }), result);
      }, CodeGenerator.prototype.maybeBlockSuffix = function(stmt, result) {
        var ends = endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString());
        return stmt.type === Syntax.BlockStatement && (!extra.comment || !stmt.leadingComments) && !ends ? [result, space] : ends ? [result, base2] : [result, newline, base2];
      };
      function generateIdentifier(node) {
        return toSourceNodeWhenNeeded(node.name, node);
      }
      function generateAsyncPrefix(node, spaceRequired) {
        return node.async ? "async" + (spaceRequired ? noEmptySpace() : space) : "";
      }
      function generateStarSuffix(node) {
        var isGenerator = node.generator && !extra.moz.starlessGenerator;
        return isGenerator ? "*" + space : "";
      }
      function generateMethodPrefix(prop) {
        var func = prop.value, prefix = "";
        return func.async && (prefix += generateAsyncPrefix(func, !prop.computed)), func.generator && (prefix += generateStarSuffix(func) ? "*" : ""), prefix;
      }
      CodeGenerator.prototype.generatePattern = function(node, precedence, flags) {
        return node.type === Syntax.Identifier ? generateIdentifier(node) : this.generateExpression(node, precedence, flags);
      }, CodeGenerator.prototype.generateFunctionParams = function(node) {
        var i, iz, result, hasDefault;
        if (hasDefault = !1, node.type === Syntax.ArrowFunctionExpression && !node.rest && (!node.defaults || node.defaults.length === 0) && node.params.length === 1 && node.params[0].type === Syntax.Identifier)
          result = [generateAsyncPrefix(node, !0), generateIdentifier(node.params[0])];
        else {
          for (result = node.type === Syntax.ArrowFunctionExpression ? [generateAsyncPrefix(node, !1)] : [], result.push("("), node.defaults && (hasDefault = !0), i = 0, iz = node.params.length; i < iz; ++i)
            hasDefault && node.defaults[i] ? result.push(this.generateAssignment(node.params[i], node.defaults[i], "=", Precedence.Assignment, E_TTT)) : result.push(this.generatePattern(node.params[i], Precedence.Assignment, E_TTT)), i + 1 < iz && result.push("," + space);
          node.rest && (node.params.length && result.push("," + space), result.push("..."), result.push(generateIdentifier(node.rest))), result.push(")");
        }
        return result;
      }, CodeGenerator.prototype.generateFunctionBody = function(node) {
        var result, expr;
        return result = this.generateFunctionParams(node), node.type === Syntax.ArrowFunctionExpression && (result.push(space), result.push("=>")), node.expression ? (result.push(space), expr = this.generateExpression(node.body, Precedence.Assignment, E_TTT), expr.toString().charAt(0) === "{" && (expr = ["(", expr, ")"]), result.push(expr)) : result.push(this.maybeBlock(node.body, S_TTFF)), result;
      }, CodeGenerator.prototype.generateIterationForStatement = function(operator, stmt, flags) {
        var result = ["for" + (stmt.await ? noEmptySpace() + "await" : "") + space + "("], that = this;
        return withIndent(function() {
          stmt.left.type === Syntax.VariableDeclaration ? withIndent(function() {
            result.push(stmt.left.kind + noEmptySpace()), result.push(that.generateStatement(stmt.left.declarations[0], S_FFFF));
          }) : result.push(that.generateExpression(stmt.left, Precedence.Call, E_TTT)), result = join(result, operator), result = [join(
            result,
            that.generateExpression(stmt.right, Precedence.Assignment, E_TTT)
          ), ")"];
        }), result.push(this.maybeBlock(stmt.body, flags)), result;
      }, CodeGenerator.prototype.generatePropertyKey = function(expr, computed) {
        var result = [];
        return computed && result.push("["), result.push(this.generateExpression(expr, Precedence.Assignment, E_TTT)), computed && result.push("]"), result;
      }, CodeGenerator.prototype.generateAssignment = function(left, right, operator, precedence, flags) {
        return Precedence.Assignment < precedence && (flags |= F_ALLOW_IN), parenthesize(
          [
            this.generateExpression(left, Precedence.Call, flags),
            space + operator + space,
            this.generateExpression(right, Precedence.Assignment, flags)
          ],
          Precedence.Assignment,
          precedence
        );
      }, CodeGenerator.prototype.semicolon = function(flags) {
        return !semicolons && flags & F_SEMICOLON_OPT ? "" : ";";
      }, CodeGenerator.Statement = {
        BlockStatement: function(stmt, flags) {
          var range, content, result = ["{", newline], that = this;
          return withIndent(function() {
            stmt.body.length === 0 && preserveBlankLines && (range = stmt.range, range[1] - range[0] > 2 && (content = sourceCode.substring(range[0] + 1, range[1] - 1), content[0] === `
` && (result = ["{"]), result.push(content)));
            var i, iz, fragment, bodyFlags;
            for (bodyFlags = S_TFFF, flags & F_FUNC_BODY && (bodyFlags |= F_DIRECTIVE_CTX), i = 0, iz = stmt.body.length; i < iz; ++i)
              preserveBlankLines && (i === 0 && (stmt.body[0].leadingComments && (range = stmt.body[0].leadingComments[0].extendedRange, content = sourceCode.substring(range[0], range[1]), content[0] === `
` && (result = ["{"])), stmt.body[0].leadingComments || generateBlankLines(stmt.range[0], stmt.body[0].range[0], result)), i > 0 && !stmt.body[i - 1].trailingComments && !stmt.body[i].leadingComments && generateBlankLines(stmt.body[i - 1].range[1], stmt.body[i].range[0], result)), i === iz - 1 && (bodyFlags |= F_SEMICOLON_OPT), stmt.body[i].leadingComments && preserveBlankLines ? fragment = that.generateStatement(stmt.body[i], bodyFlags) : fragment = addIndent(that.generateStatement(stmt.body[i], bodyFlags)), result.push(fragment), endsWithLineTerminator(toSourceNodeWhenNeeded(fragment).toString()) || preserveBlankLines && i < iz - 1 && stmt.body[i + 1].leadingComments || result.push(newline), preserveBlankLines && i === iz - 1 && (stmt.body[i].trailingComments || generateBlankLines(stmt.body[i].range[1], stmt.range[1], result));
          }), result.push(addIndent("}")), result;
        },
        BreakStatement: function(stmt, flags) {
          return stmt.label ? "break " + stmt.label.name + this.semicolon(flags) : "break" + this.semicolon(flags);
        },
        ContinueStatement: function(stmt, flags) {
          return stmt.label ? "continue " + stmt.label.name + this.semicolon(flags) : "continue" + this.semicolon(flags);
        },
        ClassBody: function(stmt, flags) {
          var result = ["{", newline], that = this;
          return withIndent(function(indent2) {
            var i, iz;
            for (i = 0, iz = stmt.body.length; i < iz; ++i)
              result.push(indent2), result.push(that.generateExpression(stmt.body[i], Precedence.Sequence, E_TTT)), i + 1 < iz && result.push(newline);
          }), endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) || result.push(newline), result.push(base2), result.push("}"), result;
        },
        ClassDeclaration: function(stmt, flags) {
          var result, fragment;
          return result = ["class"], stmt.id && (result = join(result, this.generateExpression(stmt.id, Precedence.Sequence, E_TTT))), stmt.superClass && (fragment = join("extends", this.generateExpression(stmt.superClass, Precedence.Unary, E_TTT)), result = join(result, fragment)), result.push(space), result.push(this.generateStatement(stmt.body, S_TFFT)), result;
        },
        DirectiveStatement: function(stmt, flags) {
          return extra.raw && stmt.raw ? stmt.raw + this.semicolon(flags) : escapeDirective(stmt.directive) + this.semicolon(flags);
        },
        DoWhileStatement: function(stmt, flags) {
          var result = join("do", this.maybeBlock(stmt.body, S_TFFF));
          return result = this.maybeBlockSuffix(stmt.body, result), join(result, [
            "while" + space + "(",
            this.generateExpression(stmt.test, Precedence.Sequence, E_TTT),
            ")" + this.semicolon(flags)
          ]);
        },
        CatchClause: function(stmt, flags) {
          var result, that = this;
          return withIndent(function() {
            var guard;
            stmt.param ? (result = [
              "catch" + space + "(",
              that.generateExpression(stmt.param, Precedence.Sequence, E_TTT),
              ")"
            ], stmt.guard && (guard = that.generateExpression(stmt.guard, Precedence.Sequence, E_TTT), result.splice(2, 0, " if ", guard))) : result = ["catch"];
          }), result.push(this.maybeBlock(stmt.body, S_TFFF)), result;
        },
        DebuggerStatement: function(stmt, flags) {
          return "debugger" + this.semicolon(flags);
        },
        EmptyStatement: function(stmt, flags) {
          return ";";
        },
        ExportDefaultDeclaration: function(stmt, flags) {
          var result = ["export"], bodyFlags;
          return bodyFlags = flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF, result = join(result, "default"), isStatement(stmt.declaration) ? result = join(result, this.generateStatement(stmt.declaration, bodyFlags)) : result = join(result, this.generateExpression(stmt.declaration, Precedence.Assignment, E_TTT) + this.semicolon(flags)), result;
        },
        ExportNamedDeclaration: function(stmt, flags) {
          var result = ["export"], bodyFlags, that = this;
          return bodyFlags = flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF, stmt.declaration ? join(result, this.generateStatement(stmt.declaration, bodyFlags)) : (stmt.specifiers && (stmt.specifiers.length === 0 ? result = join(result, "{" + space + "}") : stmt.specifiers[0].type === Syntax.ExportBatchSpecifier ? result = join(result, this.generateExpression(stmt.specifiers[0], Precedence.Sequence, E_TTT)) : (result = join(result, "{"), withIndent(function(indent2) {
            var i, iz;
            for (result.push(newline), i = 0, iz = stmt.specifiers.length; i < iz; ++i)
              result.push(indent2), result.push(that.generateExpression(stmt.specifiers[i], Precedence.Sequence, E_TTT)), i + 1 < iz && result.push("," + newline);
          }), endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) || result.push(newline), result.push(base2 + "}")), stmt.source ? result = join(result, [
            "from" + space,
            // ModuleSpecifier
            this.generateExpression(stmt.source, Precedence.Sequence, E_TTT),
            this.semicolon(flags)
          ]) : result.push(this.semicolon(flags))), result);
        },
        ExportAllDeclaration: function(stmt, flags) {
          return [
            "export" + space,
            "*" + space,
            "from" + space,
            // ModuleSpecifier
            this.generateExpression(stmt.source, Precedence.Sequence, E_TTT),
            this.semicolon(flags)
          ];
        },
        ExpressionStatement: function(stmt, flags) {
          var result, fragment;
          function isClassPrefixed(fragment2) {
            var code;
            return fragment2.slice(0, 5) !== "class" ? !1 : (code = fragment2.charCodeAt(5), code === 123 || esutils.code.isWhiteSpace(code) || esutils.code.isLineTerminator(code));
          }
          function isFunctionPrefixed(fragment2) {
            var code;
            return fragment2.slice(0, 8) !== "function" ? !1 : (code = fragment2.charCodeAt(8), code === 40 || esutils.code.isWhiteSpace(code) || code === 42 || esutils.code.isLineTerminator(code));
          }
          function isAsyncPrefixed(fragment2) {
            var code, i, iz;
            if (fragment2.slice(0, 5) !== "async" || !esutils.code.isWhiteSpace(fragment2.charCodeAt(5)))
              return !1;
            for (i = 6, iz = fragment2.length; i < iz && esutils.code.isWhiteSpace(fragment2.charCodeAt(i)); ++i)
              ;
            return i === iz || fragment2.slice(i, i + 8) !== "function" ? !1 : (code = fragment2.charCodeAt(i + 8), code === 40 || esutils.code.isWhiteSpace(code) || code === 42 || esutils.code.isLineTerminator(code));
          }
          return result = [this.generateExpression(stmt.expression, Precedence.Sequence, E_TTT)], fragment = toSourceNodeWhenNeeded(result).toString(), fragment.charCodeAt(0) === 123 || // ObjectExpression
          isClassPrefixed(fragment) || isFunctionPrefixed(fragment) || isAsyncPrefixed(fragment) || directive && flags & F_DIRECTIVE_CTX && stmt.expression.type === Syntax.Literal && typeof stmt.expression.value == "string" ? result = ["(", result, ")" + this.semicolon(flags)] : result.push(this.semicolon(flags)), result;
        },
        ImportDeclaration: function(stmt, flags) {
          var result, cursor, that = this;
          return stmt.specifiers.length === 0 ? [
            "import",
            space,
            // ModuleSpecifier
            this.generateExpression(stmt.source, Precedence.Sequence, E_TTT),
            this.semicolon(flags)
          ] : (result = [
            "import"
          ], cursor = 0, stmt.specifiers[cursor].type === Syntax.ImportDefaultSpecifier && (result = join(result, [
            this.generateExpression(stmt.specifiers[cursor], Precedence.Sequence, E_TTT)
          ]), ++cursor), stmt.specifiers[cursor] && (cursor !== 0 && result.push(","), stmt.specifiers[cursor].type === Syntax.ImportNamespaceSpecifier ? result = join(result, [
            space,
            this.generateExpression(stmt.specifiers[cursor], Precedence.Sequence, E_TTT)
          ]) : (result.push(space + "{"), stmt.specifiers.length - cursor === 1 ? (result.push(space), result.push(this.generateExpression(stmt.specifiers[cursor], Precedence.Sequence, E_TTT)), result.push(space + "}" + space)) : (withIndent(function(indent2) {
            var i, iz;
            for (result.push(newline), i = cursor, iz = stmt.specifiers.length; i < iz; ++i)
              result.push(indent2), result.push(that.generateExpression(stmt.specifiers[i], Precedence.Sequence, E_TTT)), i + 1 < iz && result.push("," + newline);
          }), endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) || result.push(newline), result.push(base2 + "}" + space)))), result = join(result, [
            "from" + space,
            // ModuleSpecifier
            this.generateExpression(stmt.source, Precedence.Sequence, E_TTT),
            this.semicolon(flags)
          ]), result);
        },
        VariableDeclarator: function(stmt, flags) {
          var itemFlags = flags & F_ALLOW_IN ? E_TTT : E_FTT;
          return stmt.init ? [
            this.generateExpression(stmt.id, Precedence.Assignment, itemFlags),
            space,
            "=",
            space,
            this.generateExpression(stmt.init, Precedence.Assignment, itemFlags)
          ] : this.generatePattern(stmt.id, Precedence.Assignment, itemFlags);
        },
        VariableDeclaration: function(stmt, flags) {
          var result, i, iz, node, bodyFlags, that = this;
          result = [stmt.kind], bodyFlags = flags & F_ALLOW_IN ? S_TFFF : S_FFFF;
          function block() {
            for (node = stmt.declarations[0], extra.comment && node.leadingComments ? (result.push(`
`), result.push(addIndent(that.generateStatement(node, bodyFlags)))) : (result.push(noEmptySpace()), result.push(that.generateStatement(node, bodyFlags))), i = 1, iz = stmt.declarations.length; i < iz; ++i)
              node = stmt.declarations[i], extra.comment && node.leadingComments ? (result.push("," + newline), result.push(addIndent(that.generateStatement(node, bodyFlags)))) : (result.push("," + space), result.push(that.generateStatement(node, bodyFlags)));
          }
          return stmt.declarations.length > 1 ? withIndent(block) : block(), result.push(this.semicolon(flags)), result;
        },
        ThrowStatement: function(stmt, flags) {
          return [join(
            "throw",
            this.generateExpression(stmt.argument, Precedence.Sequence, E_TTT)
          ), this.semicolon(flags)];
        },
        TryStatement: function(stmt, flags) {
          var result, i, iz, guardedHandlers;
          if (result = ["try", this.maybeBlock(stmt.block, S_TFFF)], result = this.maybeBlockSuffix(stmt.block, result), stmt.handlers)
            for (i = 0, iz = stmt.handlers.length; i < iz; ++i)
              result = join(result, this.generateStatement(stmt.handlers[i], S_TFFF)), (stmt.finalizer || i + 1 !== iz) && (result = this.maybeBlockSuffix(stmt.handlers[i].body, result));
          else {
            for (guardedHandlers = stmt.guardedHandlers || [], i = 0, iz = guardedHandlers.length; i < iz; ++i)
              result = join(result, this.generateStatement(guardedHandlers[i], S_TFFF)), (stmt.finalizer || i + 1 !== iz) && (result = this.maybeBlockSuffix(guardedHandlers[i].body, result));
            if (stmt.handler)
              if (Array.isArray(stmt.handler))
                for (i = 0, iz = stmt.handler.length; i < iz; ++i)
                  result = join(result, this.generateStatement(stmt.handler[i], S_TFFF)), (stmt.finalizer || i + 1 !== iz) && (result = this.maybeBlockSuffix(stmt.handler[i].body, result));
              else
                result = join(result, this.generateStatement(stmt.handler, S_TFFF)), stmt.finalizer && (result = this.maybeBlockSuffix(stmt.handler.body, result));
          }
          return stmt.finalizer && (result = join(result, ["finally", this.maybeBlock(stmt.finalizer, S_TFFF)])), result;
        },
        SwitchStatement: function(stmt, flags) {
          var result, fragment, i, iz, bodyFlags, that = this;
          if (withIndent(function() {
            result = [
              "switch" + space + "(",
              that.generateExpression(stmt.discriminant, Precedence.Sequence, E_TTT),
              ")" + space + "{" + newline
            ];
          }), stmt.cases)
            for (bodyFlags = S_TFFF, i = 0, iz = stmt.cases.length; i < iz; ++i)
              i === iz - 1 && (bodyFlags |= F_SEMICOLON_OPT), fragment = addIndent(this.generateStatement(stmt.cases[i], bodyFlags)), result.push(fragment), endsWithLineTerminator(toSourceNodeWhenNeeded(fragment).toString()) || result.push(newline);
          return result.push(addIndent("}")), result;
        },
        SwitchCase: function(stmt, flags) {
          var result, fragment, i, iz, bodyFlags, that = this;
          return withIndent(function() {
            for (stmt.test ? result = [
              join("case", that.generateExpression(stmt.test, Precedence.Sequence, E_TTT)),
              ":"
            ] : result = ["default:"], i = 0, iz = stmt.consequent.length, iz && stmt.consequent[0].type === Syntax.BlockStatement && (fragment = that.maybeBlock(stmt.consequent[0], S_TFFF), result.push(fragment), i = 1), i !== iz && !endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) && result.push(newline), bodyFlags = S_TFFF; i < iz; ++i)
              i === iz - 1 && flags & F_SEMICOLON_OPT && (bodyFlags |= F_SEMICOLON_OPT), fragment = addIndent(that.generateStatement(stmt.consequent[i], bodyFlags)), result.push(fragment), i + 1 !== iz && !endsWithLineTerminator(toSourceNodeWhenNeeded(fragment).toString()) && result.push(newline);
          }), result;
        },
        IfStatement: function(stmt, flags) {
          var result, bodyFlags, semicolonOptional, that = this;
          return withIndent(function() {
            result = [
              "if" + space + "(",
              that.generateExpression(stmt.test, Precedence.Sequence, E_TTT),
              ")"
            ];
          }), semicolonOptional = flags & F_SEMICOLON_OPT, bodyFlags = S_TFFF, semicolonOptional && (bodyFlags |= F_SEMICOLON_OPT), stmt.alternate ? (result.push(this.maybeBlock(stmt.consequent, S_TFFF)), result = this.maybeBlockSuffix(stmt.consequent, result), stmt.alternate.type === Syntax.IfStatement ? result = join(result, ["else ", this.generateStatement(stmt.alternate, bodyFlags)]) : result = join(result, join("else", this.maybeBlock(stmt.alternate, bodyFlags)))) : result.push(this.maybeBlock(stmt.consequent, bodyFlags)), result;
        },
        ForStatement: function(stmt, flags) {
          var result, that = this;
          return withIndent(function() {
            result = ["for" + space + "("], stmt.init ? stmt.init.type === Syntax.VariableDeclaration ? result.push(that.generateStatement(stmt.init, S_FFFF)) : (result.push(that.generateExpression(stmt.init, Precedence.Sequence, E_FTT)), result.push(";")) : result.push(";"), stmt.test && (result.push(space), result.push(that.generateExpression(stmt.test, Precedence.Sequence, E_TTT))), result.push(";"), stmt.update && (result.push(space), result.push(that.generateExpression(stmt.update, Precedence.Sequence, E_TTT))), result.push(")");
          }), result.push(this.maybeBlock(stmt.body, flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF)), result;
        },
        ForInStatement: function(stmt, flags) {
          return this.generateIterationForStatement("in", stmt, flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF);
        },
        ForOfStatement: function(stmt, flags) {
          return this.generateIterationForStatement("of", stmt, flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF);
        },
        LabeledStatement: function(stmt, flags) {
          return [stmt.label.name + ":", this.maybeBlock(stmt.body, flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF)];
        },
        Program: function(stmt, flags) {
          var result, fragment, i, iz, bodyFlags;
          for (iz = stmt.body.length, result = [safeConcatenation && iz > 0 ? `
` : ""], bodyFlags = S_TFTF, i = 0; i < iz; ++i)
            !safeConcatenation && i === iz - 1 && (bodyFlags |= F_SEMICOLON_OPT), preserveBlankLines && (i === 0 && (stmt.body[0].leadingComments || generateBlankLines(stmt.range[0], stmt.body[i].range[0], result)), i > 0 && !stmt.body[i - 1].trailingComments && !stmt.body[i].leadingComments && generateBlankLines(stmt.body[i - 1].range[1], stmt.body[i].range[0], result)), fragment = addIndent(this.generateStatement(stmt.body[i], bodyFlags)), result.push(fragment), i + 1 < iz && !endsWithLineTerminator(toSourceNodeWhenNeeded(fragment).toString()) && (preserveBlankLines && stmt.body[i + 1].leadingComments || result.push(newline)), preserveBlankLines && i === iz - 1 && (stmt.body[i].trailingComments || generateBlankLines(stmt.body[i].range[1], stmt.range[1], result));
          return result;
        },
        FunctionDeclaration: function(stmt, flags) {
          return [
            generateAsyncPrefix(stmt, !0),
            "function",
            generateStarSuffix(stmt) || noEmptySpace(),
            stmt.id ? generateIdentifier(stmt.id) : "",
            this.generateFunctionBody(stmt)
          ];
        },
        ReturnStatement: function(stmt, flags) {
          return stmt.argument ? [join(
            "return",
            this.generateExpression(stmt.argument, Precedence.Sequence, E_TTT)
          ), this.semicolon(flags)] : ["return" + this.semicolon(flags)];
        },
        WhileStatement: function(stmt, flags) {
          var result, that = this;
          return withIndent(function() {
            result = [
              "while" + space + "(",
              that.generateExpression(stmt.test, Precedence.Sequence, E_TTT),
              ")"
            ];
          }), result.push(this.maybeBlock(stmt.body, flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF)), result;
        },
        WithStatement: function(stmt, flags) {
          var result, that = this;
          return withIndent(function() {
            result = [
              "with" + space + "(",
              that.generateExpression(stmt.object, Precedence.Sequence, E_TTT),
              ")"
            ];
          }), result.push(this.maybeBlock(stmt.body, flags & F_SEMICOLON_OPT ? S_TFFT : S_TFFF)), result;
        }
      }, merge(CodeGenerator.prototype, CodeGenerator.Statement), CodeGenerator.Expression = {
        SequenceExpression: function(expr, precedence, flags) {
          var result, i, iz;
          for (Precedence.Sequence < precedence && (flags |= F_ALLOW_IN), result = [], i = 0, iz = expr.expressions.length; i < iz; ++i)
            result.push(this.generateExpression(expr.expressions[i], Precedence.Assignment, flags)), i + 1 < iz && result.push("," + space);
          return parenthesize(result, Precedence.Sequence, precedence);
        },
        AssignmentExpression: function(expr, precedence, flags) {
          return this.generateAssignment(expr.left, expr.right, expr.operator, precedence, flags);
        },
        ArrowFunctionExpression: function(expr, precedence, flags) {
          return parenthesize(this.generateFunctionBody(expr), Precedence.ArrowFunction, precedence);
        },
        ConditionalExpression: function(expr, precedence, flags) {
          return Precedence.Conditional < precedence && (flags |= F_ALLOW_IN), parenthesize(
            [
              this.generateExpression(expr.test, Precedence.Coalesce, flags),
              space + "?" + space,
              this.generateExpression(expr.consequent, Precedence.Assignment, flags),
              space + ":" + space,
              this.generateExpression(expr.alternate, Precedence.Assignment, flags)
            ],
            Precedence.Conditional,
            precedence
          );
        },
        LogicalExpression: function(expr, precedence, flags) {
          return expr.operator === "??" && (flags |= F_FOUND_COALESCE), this.BinaryExpression(expr, precedence, flags);
        },
        BinaryExpression: function(expr, precedence, flags) {
          var result, leftPrecedence, rightPrecedence, currentPrecedence, fragment, leftSource;
          return currentPrecedence = BinaryPrecedence[expr.operator], leftPrecedence = expr.operator === "**" ? Precedence.Postfix : currentPrecedence, rightPrecedence = expr.operator === "**" ? currentPrecedence : currentPrecedence + 1, currentPrecedence < precedence && (flags |= F_ALLOW_IN), fragment = this.generateExpression(expr.left, leftPrecedence, flags), leftSource = fragment.toString(), leftSource.charCodeAt(leftSource.length - 1) === 47 && esutils.code.isIdentifierPartES5(expr.operator.charCodeAt(0)) ? result = [fragment, noEmptySpace(), expr.operator] : result = join(fragment, expr.operator), fragment = this.generateExpression(expr.right, rightPrecedence, flags), expr.operator === "/" && fragment.toString().charAt(0) === "/" || expr.operator.slice(-1) === "<" && fragment.toString().slice(0, 3) === "!--" ? (result.push(noEmptySpace()), result.push(fragment)) : result = join(result, fragment), expr.operator === "in" && !(flags & F_ALLOW_IN) ? ["(", result, ")"] : (expr.operator === "||" || expr.operator === "&&") && flags & F_FOUND_COALESCE ? ["(", result, ")"] : parenthesize(result, currentPrecedence, precedence);
        },
        CallExpression: function(expr, precedence, flags) {
          var result, i, iz;
          for (result = [this.generateExpression(expr.callee, Precedence.Call, E_TTF)], expr.optional && result.push("?."), result.push("("), i = 0, iz = expr.arguments.length; i < iz; ++i)
            result.push(this.generateExpression(expr.arguments[i], Precedence.Assignment, E_TTT)), i + 1 < iz && result.push("," + space);
          return result.push(")"), flags & F_ALLOW_CALL ? parenthesize(result, Precedence.Call, precedence) : ["(", result, ")"];
        },
        ChainExpression: function(expr, precedence, flags) {
          Precedence.OptionalChaining < precedence && (flags |= F_ALLOW_CALL);
          var result = this.generateExpression(expr.expression, Precedence.OptionalChaining, flags);
          return parenthesize(result, Precedence.OptionalChaining, precedence);
        },
        NewExpression: function(expr, precedence, flags) {
          var result, length, i, iz, itemFlags;
          if (length = expr.arguments.length, itemFlags = flags & F_ALLOW_UNPARATH_NEW && !parentheses && length === 0 ? E_TFT : E_TFF, result = join(
            "new",
            this.generateExpression(expr.callee, Precedence.New, itemFlags)
          ), !(flags & F_ALLOW_UNPARATH_NEW) || parentheses || length > 0) {
            for (result.push("("), i = 0, iz = length; i < iz; ++i)
              result.push(this.generateExpression(expr.arguments[i], Precedence.Assignment, E_TTT)), i + 1 < iz && result.push("," + space);
            result.push(")");
          }
          return parenthesize(result, Precedence.New, precedence);
        },
        MemberExpression: function(expr, precedence, flags) {
          var result, fragment;
          return result = [this.generateExpression(expr.object, Precedence.Call, flags & F_ALLOW_CALL ? E_TTF : E_TFF)], expr.computed ? (expr.optional && result.push("?."), result.push("["), result.push(this.generateExpression(expr.property, Precedence.Sequence, flags & F_ALLOW_CALL ? E_TTT : E_TFT)), result.push("]")) : (!expr.optional && expr.object.type === Syntax.Literal && typeof expr.object.value == "number" && (fragment = toSourceNodeWhenNeeded(result).toString(), fragment.indexOf(".") < 0 && !/[eExX]/.test(fragment) && esutils.code.isDecimalDigit(fragment.charCodeAt(fragment.length - 1)) && !(fragment.length >= 2 && fragment.charCodeAt(0) === 48) && result.push(" ")), result.push(expr.optional ? "?." : "."), result.push(generateIdentifier(expr.property))), parenthesize(result, Precedence.Member, precedence);
        },
        MetaProperty: function(expr, precedence, flags) {
          var result;
          return result = [], result.push(typeof expr.meta == "string" ? expr.meta : generateIdentifier(expr.meta)), result.push("."), result.push(typeof expr.property == "string" ? expr.property : generateIdentifier(expr.property)), parenthesize(result, Precedence.Member, precedence);
        },
        UnaryExpression: function(expr, precedence, flags) {
          var result, fragment, rightCharCode, leftSource, leftCharCode;
          return fragment = this.generateExpression(expr.argument, Precedence.Unary, E_TTT), space === "" ? result = join(expr.operator, fragment) : (result = [expr.operator], expr.operator.length > 2 ? result = join(result, fragment) : (leftSource = toSourceNodeWhenNeeded(result).toString(), leftCharCode = leftSource.charCodeAt(leftSource.length - 1), rightCharCode = fragment.toString().charCodeAt(0), ((leftCharCode === 43 || leftCharCode === 45) && leftCharCode === rightCharCode || esutils.code.isIdentifierPartES5(leftCharCode) && esutils.code.isIdentifierPartES5(rightCharCode)) && result.push(noEmptySpace()), result.push(fragment))), parenthesize(result, Precedence.Unary, precedence);
        },
        YieldExpression: function(expr, precedence, flags) {
          var result;
          return expr.delegate ? result = "yield*" : result = "yield", expr.argument && (result = join(
            result,
            this.generateExpression(expr.argument, Precedence.Yield, E_TTT)
          )), parenthesize(result, Precedence.Yield, precedence);
        },
        AwaitExpression: function(expr, precedence, flags) {
          var result = join(
            expr.all ? "await*" : "await",
            this.generateExpression(expr.argument, Precedence.Await, E_TTT)
          );
          return parenthesize(result, Precedence.Await, precedence);
        },
        UpdateExpression: function(expr, precedence, flags) {
          return expr.prefix ? parenthesize(
            [
              expr.operator,
              this.generateExpression(expr.argument, Precedence.Unary, E_TTT)
            ],
            Precedence.Unary,
            precedence
          ) : parenthesize(
            [
              this.generateExpression(expr.argument, Precedence.Postfix, E_TTT),
              expr.operator
            ],
            Precedence.Postfix,
            precedence
          );
        },
        FunctionExpression: function(expr, precedence, flags) {
          var result = [
            generateAsyncPrefix(expr, !0),
            "function"
          ];
          return expr.id ? (result.push(generateStarSuffix(expr) || noEmptySpace()), result.push(generateIdentifier(expr.id))) : result.push(generateStarSuffix(expr) || space), result.push(this.generateFunctionBody(expr)), result;
        },
        ArrayPattern: function(expr, precedence, flags) {
          return this.ArrayExpression(expr, precedence, flags, !0);
        },
        ArrayExpression: function(expr, precedence, flags, isPattern) {
          var result, multiline, that = this;
          return expr.elements.length ? (multiline = isPattern ? !1 : expr.elements.length > 1, result = ["[", multiline ? newline : ""], withIndent(function(indent2) {
            var i, iz;
            for (i = 0, iz = expr.elements.length; i < iz; ++i)
              expr.elements[i] ? (result.push(multiline ? indent2 : ""), result.push(that.generateExpression(expr.elements[i], Precedence.Assignment, E_TTT))) : (multiline && result.push(indent2), i + 1 === iz && result.push(",")), i + 1 < iz && result.push("," + (multiline ? newline : space));
          }), multiline && !endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) && result.push(newline), result.push(multiline ? base2 : ""), result.push("]"), result) : "[]";
        },
        RestElement: function(expr, precedence, flags) {
          return "..." + this.generatePattern(expr.argument);
        },
        ClassExpression: function(expr, precedence, flags) {
          var result, fragment;
          return result = ["class"], expr.id && (result = join(result, this.generateExpression(expr.id, Precedence.Sequence, E_TTT))), expr.superClass && (fragment = join("extends", this.generateExpression(expr.superClass, Precedence.Unary, E_TTT)), result = join(result, fragment)), result.push(space), result.push(this.generateStatement(expr.body, S_TFFT)), result;
        },
        MethodDefinition: function(expr, precedence, flags) {
          var result, fragment;
          return expr.static ? result = ["static" + space] : result = [], expr.kind === "get" || expr.kind === "set" ? fragment = [
            join(expr.kind, this.generatePropertyKey(expr.key, expr.computed)),
            this.generateFunctionBody(expr.value)
          ] : fragment = [
            generateMethodPrefix(expr),
            this.generatePropertyKey(expr.key, expr.computed),
            this.generateFunctionBody(expr.value)
          ], join(result, fragment);
        },
        Property: function(expr, precedence, flags) {
          return expr.kind === "get" || expr.kind === "set" ? [
            expr.kind,
            noEmptySpace(),
            this.generatePropertyKey(expr.key, expr.computed),
            this.generateFunctionBody(expr.value)
          ] : expr.shorthand ? expr.value.type === "AssignmentPattern" ? this.AssignmentPattern(expr.value, Precedence.Sequence, E_TTT) : this.generatePropertyKey(expr.key, expr.computed) : expr.method ? [
            generateMethodPrefix(expr),
            this.generatePropertyKey(expr.key, expr.computed),
            this.generateFunctionBody(expr.value)
          ] : [
            this.generatePropertyKey(expr.key, expr.computed),
            ":" + space,
            this.generateExpression(expr.value, Precedence.Assignment, E_TTT)
          ];
        },
        ObjectExpression: function(expr, precedence, flags) {
          var multiline, result, fragment, that = this;
          return expr.properties.length ? (multiline = expr.properties.length > 1, withIndent(function() {
            fragment = that.generateExpression(expr.properties[0], Precedence.Sequence, E_TTT);
          }), !multiline && !hasLineTerminator(toSourceNodeWhenNeeded(fragment).toString()) ? ["{", space, fragment, space, "}"] : (withIndent(function(indent2) {
            var i, iz;
            if (result = ["{", newline, indent2, fragment], multiline)
              for (result.push("," + newline), i = 1, iz = expr.properties.length; i < iz; ++i)
                result.push(indent2), result.push(that.generateExpression(expr.properties[i], Precedence.Sequence, E_TTT)), i + 1 < iz && result.push("," + newline);
          }), endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) || result.push(newline), result.push(base2), result.push("}"), result)) : "{}";
        },
        AssignmentPattern: function(expr, precedence, flags) {
          return this.generateAssignment(expr.left, expr.right, "=", precedence, flags);
        },
        ObjectPattern: function(expr, precedence, flags) {
          var result, i, iz, multiline, property, that = this;
          if (!expr.properties.length)
            return "{}";
          if (multiline = !1, expr.properties.length === 1)
            property = expr.properties[0], property.type === Syntax.Property && property.value.type !== Syntax.Identifier && (multiline = !0);
          else
            for (i = 0, iz = expr.properties.length; i < iz; ++i)
              if (property = expr.properties[i], property.type === Syntax.Property && !property.shorthand) {
                multiline = !0;
                break;
              }
          return result = ["{", multiline ? newline : ""], withIndent(function(indent2) {
            var i2, iz2;
            for (i2 = 0, iz2 = expr.properties.length; i2 < iz2; ++i2)
              result.push(multiline ? indent2 : ""), result.push(that.generateExpression(expr.properties[i2], Precedence.Sequence, E_TTT)), i2 + 1 < iz2 && result.push("," + (multiline ? newline : space));
          }), multiline && !endsWithLineTerminator(toSourceNodeWhenNeeded(result).toString()) && result.push(newline), result.push(multiline ? base2 : ""), result.push("}"), result;
        },
        ThisExpression: function(expr, precedence, flags) {
          return "this";
        },
        Super: function(expr, precedence, flags) {
          return "super";
        },
        Identifier: function(expr, precedence, flags) {
          return generateIdentifier(expr);
        },
        ImportDefaultSpecifier: function(expr, precedence, flags) {
          return generateIdentifier(expr.id || expr.local);
        },
        ImportNamespaceSpecifier: function(expr, precedence, flags) {
          var result = ["*"], id = expr.id || expr.local;
          return id && result.push(space + "as" + noEmptySpace() + generateIdentifier(id)), result;
        },
        ImportSpecifier: function(expr, precedence, flags) {
          var imported = expr.imported, result = [imported.name], local = expr.local;
          return local && local.name !== imported.name && result.push(noEmptySpace() + "as" + noEmptySpace() + generateIdentifier(local)), result;
        },
        ExportSpecifier: function(expr, precedence, flags) {
          var local = expr.local, result = [local.name], exported = expr.exported;
          return exported && exported.name !== local.name && result.push(noEmptySpace() + "as" + noEmptySpace() + generateIdentifier(exported)), result;
        },
        Literal: function(expr, precedence, flags) {
          var raw;
          if (expr.hasOwnProperty("raw") && parse2 && extra.raw)
            try {
              if (raw = parse2(expr.raw).body[0].expression, raw.type === Syntax.Literal && raw.value === expr.value)
                return expr.raw;
            } catch {
            }
          return expr.regex ? "/" + expr.regex.pattern + "/" + expr.regex.flags : typeof expr.value == "bigint" ? expr.value.toString() + "n" : expr.bigint ? expr.bigint + "n" : expr.value === null ? "null" : typeof expr.value == "string" ? escapeString(expr.value) : typeof expr.value == "number" ? generateNumber(expr.value) : typeof expr.value == "boolean" ? expr.value ? "true" : "false" : generateRegExp(expr.value);
        },
        GeneratorExpression: function(expr, precedence, flags) {
          return this.ComprehensionExpression(expr, precedence, flags);
        },
        ComprehensionExpression: function(expr, precedence, flags) {
          var result, i, iz, fragment, that = this;
          return result = expr.type === Syntax.GeneratorExpression ? ["("] : ["["], extra.moz.comprehensionExpressionStartsWithAssignment && (fragment = this.generateExpression(expr.body, Precedence.Assignment, E_TTT), result.push(fragment)), expr.blocks && withIndent(function() {
            for (i = 0, iz = expr.blocks.length; i < iz; ++i)
              fragment = that.generateExpression(expr.blocks[i], Precedence.Sequence, E_TTT), i > 0 || extra.moz.comprehensionExpressionStartsWithAssignment ? result = join(result, fragment) : result.push(fragment);
          }), expr.filter && (result = join(result, "if" + space), fragment = this.generateExpression(expr.filter, Precedence.Sequence, E_TTT), result = join(result, ["(", fragment, ")"])), extra.moz.comprehensionExpressionStartsWithAssignment || (fragment = this.generateExpression(expr.body, Precedence.Assignment, E_TTT), result = join(result, fragment)), result.push(expr.type === Syntax.GeneratorExpression ? ")" : "]"), result;
        },
        ComprehensionBlock: function(expr, precedence, flags) {
          var fragment;
          return expr.left.type === Syntax.VariableDeclaration ? fragment = [
            expr.left.kind,
            noEmptySpace(),
            this.generateStatement(expr.left.declarations[0], S_FFFF)
          ] : fragment = this.generateExpression(expr.left, Precedence.Call, E_TTT), fragment = join(fragment, expr.of ? "of" : "in"), fragment = join(fragment, this.generateExpression(expr.right, Precedence.Sequence, E_TTT)), ["for" + space + "(", fragment, ")"];
        },
        SpreadElement: function(expr, precedence, flags) {
          return [
            "...",
            this.generateExpression(expr.argument, Precedence.Assignment, E_TTT)
          ];
        },
        TaggedTemplateExpression: function(expr, precedence, flags) {
          var itemFlags = E_TTF;
          flags & F_ALLOW_CALL || (itemFlags = E_TFF);
          var result = [
            this.generateExpression(expr.tag, Precedence.Call, itemFlags),
            this.generateExpression(expr.quasi, Precedence.Primary, E_FFT)
          ];
          return parenthesize(result, Precedence.TaggedTemplate, precedence);
        },
        TemplateElement: function(expr, precedence, flags) {
          return expr.value.raw;
        },
        TemplateLiteral: function(expr, precedence, flags) {
          var result, i, iz;
          for (result = ["`"], i = 0, iz = expr.quasis.length; i < iz; ++i)
            result.push(this.generateExpression(expr.quasis[i], Precedence.Primary, E_TTT)), i + 1 < iz && (result.push("${" + space), result.push(this.generateExpression(expr.expressions[i], Precedence.Sequence, E_TTT)), result.push(space + "}"));
          return result.push("`"), result;
        },
        ModuleSpecifier: function(expr, precedence, flags) {
          return this.Literal(expr, precedence, flags);
        },
        ImportExpression: function(expr, precedence, flag) {
          return parenthesize([
            "import(",
            this.generateExpression(expr.source, Precedence.Assignment, E_TTT),
            ")"
          ], Precedence.Call, precedence);
        }
      }, merge(CodeGenerator.prototype, CodeGenerator.Expression), CodeGenerator.prototype.generateExpression = function(expr, precedence, flags) {
        var result, type;
        return type = expr.type || Syntax.Property, extra.verbatim && expr.hasOwnProperty(extra.verbatim) ? generateVerbatim(expr, precedence) : (result = this[type](expr, precedence, flags), extra.comment && (result = addComments(expr, result)), toSourceNodeWhenNeeded(result, expr));
      }, CodeGenerator.prototype.generateStatement = function(stmt, flags) {
        var result, fragment;
        return result = this[stmt.type](stmt, flags), extra.comment && (result = addComments(stmt, result)), fragment = toSourceNodeWhenNeeded(result).toString(), stmt.type === Syntax.Program && !safeConcatenation && newline === "" && fragment.charAt(fragment.length - 1) === `
` && (result = sourceMap ? toSourceNodeWhenNeeded(result).replaceRight(/\s+$/, "") : fragment.replace(/\s+$/, "")), toSourceNodeWhenNeeded(result, stmt);
      };
      function generateInternal(node) {
        var codegen;
        if (codegen = new CodeGenerator(), isStatement(node))
          return codegen.generateStatement(node, S_TFFF);
        if (isExpression(node))
          return codegen.generateExpression(node, Precedence.Sequence, E_TTT);
        throw new Error("Unknown node type: " + node.type);
      }
      function generate2(node, options) {
        var defaultOptions = getDefaultOptions(), result, pair;
        return options != null ? (typeof options.indent == "string" && (defaultOptions.format.indent.style = options.indent), typeof options.base == "number" && (defaultOptions.format.indent.base = options.base), options = updateDeeply(defaultOptions, options), indent = options.format.indent.style, typeof options.base == "string" ? base2 = options.base : base2 = stringRepeat(indent, options.format.indent.base)) : (options = defaultOptions, indent = options.format.indent.style, base2 = stringRepeat(indent, options.format.indent.base)), json = options.format.json, renumber = options.format.renumber, hexadecimal = json ? !1 : options.format.hexadecimal, quotes = json ? "double" : options.format.quotes, escapeless = options.format.escapeless, newline = options.format.newline, space = options.format.space, options.format.compact && (newline = space = indent = base2 = ""), parentheses = options.format.parentheses, semicolons = options.format.semicolons, safeConcatenation = options.format.safeConcatenation, directive = options.directive, parse2 = json ? null : options.parse, sourceMap = options.sourceMap, sourceCode = options.sourceCode, preserveBlankLines = options.format.preserveBlankLines && sourceCode !== null, extra = options, sourceMap && (exports.browser ? SourceNode = __webpack_require__.g.sourceMap.SourceNode : SourceNode = require_source_map().SourceNode), result = generateInternal(node), sourceMap ? (pair = result.toStringWithSourceMap({
          file: options.file,
          sourceRoot: options.sourceMapRoot
        }), options.sourceContent && pair.map.setSourceContent(
          options.sourceMap,
          options.sourceContent
        ), options.sourceMapWithCode ? pair : pair.map.toString()) : (pair = { code: result.toString(), map: null }, options.sourceMapWithCode ? pair : pair.code);
      }
      FORMAT_MINIFY = {
        indent: {
          style: "",
          base: 0
        },
        renumber: !0,
        hexadecimal: !0,
        quotes: "auto",
        escapeless: !0,
        compact: !0,
        parentheses: !1,
        semicolons: !1
      }, FORMAT_DEFAULTS = getDefaultOptions().format, exports.version = require_package().version, exports.generate = generate2, exports.attachComments = estraverse.attachComments, exports.Precedence = updateDeeply({}, Precedence), exports.browser = !1, exports.FORMAT_MINIFY = FORMAT_MINIFY, exports.FORMAT_DEFAULTS = FORMAT_DEFAULTS;
    })();
  }
});

// node_modules/acorn/dist/acorn.js
var require_acorn = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "node_modules/acorn/dist/acorn.js"(exports, module) {
    (function(global2, factory) {
      typeof exports == "object" && typeof module < "u" ? factory(exports) : typeof define == "function" && __webpack_require__.amdO ? define(["exports"], factory) : (global2 = global2 || self, factory(global2.acorn = {}));
    })(exports, (function(exports2) {
      "use strict";
      var reservedWords = {
        3: "abstract boolean byte char class double enum export extends final float goto implements import int interface long native package private protected public short static super synchronized throws transient volatile",
        5: "class enum extends super const export import",
        6: "enum",
        strict: "implements interface let package private protected public static yield",
        strictBind: "eval arguments"
      }, ecma5AndLessKeywords = "break case catch continue debugger default do else finally for function if return switch throw try var while with null true false instanceof typeof void delete new in this", keywords = {
        5: ecma5AndLessKeywords,
        "5module": ecma5AndLessKeywords + " export import",
        6: ecma5AndLessKeywords + " const class extends export import super"
      }, keywordRelationalOperator = /^in(stanceof)?$/, nonASCIIidentifierStartChars = "\xAA\xB5\xBA\xC0-\xD6\xD8-\xF6\xF8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0370-\u0374\u0376\u0377\u037A-\u037D\u037F\u0386\u0388-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u048A-\u052F\u0531-\u0556\u0559\u0560-\u0588\u05D0-\u05EA\u05EF-\u05F2\u0620-\u064A\u066E\u066F\u0671-\u06D3\u06D5\u06E5\u06E6\u06EE\u06EF\u06FA-\u06FC\u06FF\u0710\u0712-\u072F\u074D-\u07A5\u07B1\u07CA-\u07EA\u07F4\u07F5\u07FA\u0800-\u0815\u081A\u0824\u0828\u0840-\u0858\u0860-\u086A\u08A0-\u08B4\u08B6-\u08C7\u0904-\u0939\u093D\u0950\u0958-\u0961\u0971-\u0980\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BD\u09CE\u09DC\u09DD\u09DF-\u09E1\u09F0\u09F1\u09FC\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A59-\u0A5C\u0A5E\u0A72-\u0A74\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABD\u0AD0\u0AE0\u0AE1\u0AF9\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3D\u0B5C\u0B5D\u0B5F-\u0B61\u0B71\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BD0\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C39\u0C3D\u0C58-\u0C5A\u0C60\u0C61\u0C80\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBD\u0CDE\u0CE0\u0CE1\u0CF1\u0CF2\u0D04-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D\u0D4E\u0D54-\u0D56\u0D5F-\u0D61\u0D7A-\u0D7F\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0E01-\u0E30\u0E32\u0E33\u0E40-\u0E46\u0E81\u0E82\u0E84\u0E86-\u0E8A\u0E8C-\u0EA3\u0EA5\u0EA7-\u0EB0\u0EB2\u0EB3\u0EBD\u0EC0-\u0EC4\u0EC6\u0EDC-\u0EDF\u0F00\u0F40-\u0F47\u0F49-\u0F6C\u0F88-\u0F8C\u1000-\u102A\u103F\u1050-\u1055\u105A-\u105D\u1061\u1065\u1066\u106E-\u1070\u1075-\u1081\u108E\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u1380-\u138F\u13A0-\u13F5\u13F8-\u13FD\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u16EE-\u16F8\u1700-\u170C\u170E-\u1711\u1720-\u1731\u1740-\u1751\u1760-\u176C\u176E-\u1770\u1780-\u17B3\u17D7\u17DC\u1820-\u1878\u1880-\u18A8\u18AA\u18B0-\u18F5\u1900-\u191E\u1950-\u196D\u1970-\u1974\u1980-\u19AB\u19B0-\u19C9\u1A00-\u1A16\u1A20-\u1A54\u1AA7\u1B05-\u1B33\u1B45-\u1B4B\u1B83-\u1BA0\u1BAE\u1BAF\u1BBA-\u1BE5\u1C00-\u1C23\u1C4D-\u1C4F\u1C5A-\u1C7D\u1C80-\u1C88\u1C90-\u1CBA\u1CBD-\u1CBF\u1CE9-\u1CEC\u1CEE-\u1CF3\u1CF5\u1CF6\u1CFA\u1D00-\u1DBF\u1E00-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u2071\u207F\u2090-\u209C\u2102\u2107\u210A-\u2113\u2115\u2118-\u211D\u2124\u2126\u2128\u212A-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2160-\u2188\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CEE\u2CF2\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D80-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u3005-\u3007\u3021-\u3029\u3031-\u3035\u3038-\u303C\u3041-\u3096\u309B-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312F\u3131-\u318E\u31A0-\u31BF\u31F0-\u31FF\u3400-\u4DBF\u4E00-\u9FFC\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA61F\uA62A\uA62B\uA640-\uA66E\uA67F-\uA69D\uA6A0-\uA6EF\uA717-\uA71F\uA722-\uA788\uA78B-\uA7BF\uA7C2-\uA7CA\uA7F5-\uA801\uA803-\uA805\uA807-\uA80A\uA80C-\uA822\uA840-\uA873\uA882-\uA8B3\uA8F2-\uA8F7\uA8FB\uA8FD\uA8FE\uA90A-\uA925\uA930-\uA946\uA960-\uA97C\uA984-\uA9B2\uA9CF\uA9E0-\uA9E4\uA9E6-\uA9EF\uA9FA-\uA9FE\uAA00-\uAA28\uAA40-\uAA42\uAA44-\uAA4B\uAA60-\uAA76\uAA7A\uAA7E-\uAAAF\uAAB1\uAAB5\uAAB6\uAAB9-\uAABD\uAAC0\uAAC2\uAADB-\uAADD\uAAE0-\uAAEA\uAAF2-\uAAF4\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uAB30-\uAB5A\uAB5C-\uAB69\uAB70-\uABE2\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D\uFB1F-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE70-\uFE74\uFE76-\uFEFC\uFF21-\uFF3A\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC", nonASCIIidentifierChars = "\u200C\u200D\xB7\u0300-\u036F\u0387\u0483-\u0487\u0591-\u05BD\u05BF\u05C1\u05C2\u05C4\u05C5\u05C7\u0610-\u061A\u064B-\u0669\u0670\u06D6-\u06DC\u06DF-\u06E4\u06E7\u06E8\u06EA-\u06ED\u06F0-\u06F9\u0711\u0730-\u074A\u07A6-\u07B0\u07C0-\u07C9\u07EB-\u07F3\u07FD\u0816-\u0819\u081B-\u0823\u0825-\u0827\u0829-\u082D\u0859-\u085B\u08D3-\u08E1\u08E3-\u0903\u093A-\u093C\u093E-\u094F\u0951-\u0957\u0962\u0963\u0966-\u096F\u0981-\u0983\u09BC\u09BE-\u09C4\u09C7\u09C8\u09CB-\u09CD\u09D7\u09E2\u09E3\u09E6-\u09EF\u09FE\u0A01-\u0A03\u0A3C\u0A3E-\u0A42\u0A47\u0A48\u0A4B-\u0A4D\u0A51\u0A66-\u0A71\u0A75\u0A81-\u0A83\u0ABC\u0ABE-\u0AC5\u0AC7-\u0AC9\u0ACB-\u0ACD\u0AE2\u0AE3\u0AE6-\u0AEF\u0AFA-\u0AFF\u0B01-\u0B03\u0B3C\u0B3E-\u0B44\u0B47\u0B48\u0B4B-\u0B4D\u0B55-\u0B57\u0B62\u0B63\u0B66-\u0B6F\u0B82\u0BBE-\u0BC2\u0BC6-\u0BC8\u0BCA-\u0BCD\u0BD7\u0BE6-\u0BEF\u0C00-\u0C04\u0C3E-\u0C44\u0C46-\u0C48\u0C4A-\u0C4D\u0C55\u0C56\u0C62\u0C63\u0C66-\u0C6F\u0C81-\u0C83\u0CBC\u0CBE-\u0CC4\u0CC6-\u0CC8\u0CCA-\u0CCD\u0CD5\u0CD6\u0CE2\u0CE3\u0CE6-\u0CEF\u0D00-\u0D03\u0D3B\u0D3C\u0D3E-\u0D44\u0D46-\u0D48\u0D4A-\u0D4D\u0D57\u0D62\u0D63\u0D66-\u0D6F\u0D81-\u0D83\u0DCA\u0DCF-\u0DD4\u0DD6\u0DD8-\u0DDF\u0DE6-\u0DEF\u0DF2\u0DF3\u0E31\u0E34-\u0E3A\u0E47-\u0E4E\u0E50-\u0E59\u0EB1\u0EB4-\u0EBC\u0EC8-\u0ECD\u0ED0-\u0ED9\u0F18\u0F19\u0F20-\u0F29\u0F35\u0F37\u0F39\u0F3E\u0F3F\u0F71-\u0F84\u0F86\u0F87\u0F8D-\u0F97\u0F99-\u0FBC\u0FC6\u102B-\u103E\u1040-\u1049\u1056-\u1059\u105E-\u1060\u1062-\u1064\u1067-\u106D\u1071-\u1074\u1082-\u108D\u108F-\u109D\u135D-\u135F\u1369-\u1371\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17B4-\u17D3\u17DD\u17E0-\u17E9\u180B-\u180D\u1810-\u1819\u18A9\u1920-\u192B\u1930-\u193B\u1946-\u194F\u19D0-\u19DA\u1A17-\u1A1B\u1A55-\u1A5E\u1A60-\u1A7C\u1A7F-\u1A89\u1A90-\u1A99\u1AB0-\u1ABD\u1ABF\u1AC0\u1B00-\u1B04\u1B34-\u1B44\u1B50-\u1B59\u1B6B-\u1B73\u1B80-\u1B82\u1BA1-\u1BAD\u1BB0-\u1BB9\u1BE6-\u1BF3\u1C24-\u1C37\u1C40-\u1C49\u1C50-\u1C59\u1CD0-\u1CD2\u1CD4-\u1CE8\u1CED\u1CF4\u1CF7-\u1CF9\u1DC0-\u1DF9\u1DFB-\u1DFF\u203F\u2040\u2054\u20D0-\u20DC\u20E1\u20E5-\u20F0\u2CEF-\u2CF1\u2D7F\u2DE0-\u2DFF\u302A-\u302F\u3099\u309A\uA620-\uA629\uA66F\uA674-\uA67D\uA69E\uA69F\uA6F0\uA6F1\uA802\uA806\uA80B\uA823-\uA827\uA82C\uA880\uA881\uA8B4-\uA8C5\uA8D0-\uA8D9\uA8E0-\uA8F1\uA8FF-\uA909\uA926-\uA92D\uA947-\uA953\uA980-\uA983\uA9B3-\uA9C0\uA9D0-\uA9D9\uA9E5\uA9F0-\uA9F9\uAA29-\uAA36\uAA43\uAA4C\uAA4D\uAA50-\uAA59\uAA7B-\uAA7D\uAAB0\uAAB2-\uAAB4\uAAB7\uAAB8\uAABE\uAABF\uAAC1\uAAEB-\uAAEF\uAAF5\uAAF6\uABE3-\uABEA\uABEC\uABED\uABF0-\uABF9\uFB1E\uFE00-\uFE0F\uFE20-\uFE2F\uFE33\uFE34\uFE4D-\uFE4F\uFF10-\uFF19\uFF3F", nonASCIIidentifierStart = new RegExp("[" + nonASCIIidentifierStartChars + "]"), nonASCIIidentifier = new RegExp("[" + nonASCIIidentifierStartChars + nonASCIIidentifierChars + "]");
      nonASCIIidentifierStartChars = nonASCIIidentifierChars = null;
      var astralIdentifierStartCodes = [0, 11, 2, 25, 2, 18, 2, 1, 2, 14, 3, 13, 35, 122, 70, 52, 268, 28, 4, 48, 48, 31, 14, 29, 6, 37, 11, 29, 3, 35, 5, 7, 2, 4, 43, 157, 19, 35, 5, 35, 5, 39, 9, 51, 157, 310, 10, 21, 11, 7, 153, 5, 3, 0, 2, 43, 2, 1, 4, 0, 3, 22, 11, 22, 10, 30, 66, 18, 2, 1, 11, 21, 11, 25, 71, 55, 7, 1, 65, 0, 16, 3, 2, 2, 2, 28, 43, 28, 4, 28, 36, 7, 2, 27, 28, 53, 11, 21, 11, 18, 14, 17, 111, 72, 56, 50, 14, 50, 14, 35, 349, 41, 7, 1, 79, 28, 11, 0, 9, 21, 107, 20, 28, 22, 13, 52, 76, 44, 33, 24, 27, 35, 30, 0, 3, 0, 9, 34, 4, 0, 13, 47, 15, 3, 22, 0, 2, 0, 36, 17, 2, 24, 85, 6, 2, 0, 2, 3, 2, 14, 2, 9, 8, 46, 39, 7, 3, 1, 3, 21, 2, 6, 2, 1, 2, 4, 4, 0, 19, 0, 13, 4, 159, 52, 19, 3, 21, 2, 31, 47, 21, 1, 2, 0, 185, 46, 42, 3, 37, 47, 21, 0, 60, 42, 14, 0, 72, 26, 230, 43, 117, 63, 32, 7, 3, 0, 3, 7, 2, 1, 2, 23, 16, 0, 2, 0, 95, 7, 3, 38, 17, 0, 2, 0, 29, 0, 11, 39, 8, 0, 22, 0, 12, 45, 20, 0, 35, 56, 264, 8, 2, 36, 18, 0, 50, 29, 113, 6, 2, 1, 2, 37, 22, 0, 26, 5, 2, 1, 2, 31, 15, 0, 328, 18, 190, 0, 80, 921, 103, 110, 18, 195, 2749, 1070, 4050, 582, 8634, 568, 8, 30, 114, 29, 19, 47, 17, 3, 32, 20, 6, 18, 689, 63, 129, 74, 6, 0, 67, 12, 65, 1, 2, 0, 29, 6135, 9, 1237, 43, 8, 8952, 286, 50, 2, 18, 3, 9, 395, 2309, 106, 6, 12, 4, 8, 8, 9, 5991, 84, 2, 70, 2, 1, 3, 0, 3, 1, 3, 3, 2, 11, 2, 0, 2, 6, 2, 64, 2, 3, 3, 7, 2, 6, 2, 27, 2, 3, 2, 4, 2, 0, 4, 6, 2, 339, 3, 24, 2, 24, 2, 30, 2, 24, 2, 30, 2, 24, 2, 30, 2, 24, 2, 30, 2, 24, 2, 7, 2357, 44, 11, 6, 17, 0, 370, 43, 1301, 196, 60, 67, 8, 0, 1205, 3, 2, 26, 2, 1, 2, 0, 3, 0, 2, 9, 2, 3, 2, 0, 2, 0, 7, 0, 5, 0, 2, 0, 2, 0, 2, 2, 2, 1, 2, 0, 3, 0, 2, 0, 2, 0, 2, 0, 2, 0, 2, 1, 2, 0, 3, 3, 2, 6, 2, 3, 2, 3, 2, 0, 2, 9, 2, 16, 6, 2, 2, 4, 2, 16, 4421, 42717, 35, 4148, 12, 221, 3, 5761, 15, 7472, 3104, 541, 1507, 4938], astralIdentifierCodes = [509, 0, 227, 0, 150, 4, 294, 9, 1368, 2, 2, 1, 6, 3, 41, 2, 5, 0, 166, 1, 574, 3, 9, 9, 370, 1, 154, 10, 176, 2, 54, 14, 32, 9, 16, 3, 46, 10, 54, 9, 7, 2, 37, 13, 2, 9, 6, 1, 45, 0, 13, 2, 49, 13, 9, 3, 2, 11, 83, 11, 7, 0, 161, 11, 6, 9, 7, 3, 56, 1, 2, 6, 3, 1, 3, 2, 10, 0, 11, 1, 3, 6, 4, 4, 193, 17, 10, 9, 5, 0, 82, 19, 13, 9, 214, 6, 3, 8, 28, 1, 83, 16, 16, 9, 82, 12, 9, 9, 84, 14, 5, 9, 243, 14, 166, 9, 71, 5, 2, 1, 3, 3, 2, 0, 2, 1, 13, 9, 120, 6, 3, 6, 4, 0, 29, 9, 41, 6, 2, 3, 9, 0, 10, 10, 47, 15, 406, 7, 2, 7, 17, 9, 57, 21, 2, 13, 123, 5, 4, 0, 2, 1, 2, 6, 2, 0, 9, 9, 49, 4, 2, 1, 2, 4, 9, 9, 330, 3, 19306, 9, 135, 4, 60, 6, 26, 9, 1014, 0, 2, 54, 8, 3, 82, 0, 12, 1, 19628, 1, 5319, 4, 4, 5, 9, 7, 3, 6, 31, 3, 149, 2, 1418, 49, 513, 54, 5, 49, 9, 0, 15, 0, 23, 4, 2, 14, 1361, 6, 2, 16, 3, 6, 2, 1, 2, 4, 262, 6, 10, 9, 419, 13, 1495, 6, 110, 6, 6, 9, 4759, 9, 787719, 239];
      function isInAstralSet(code, set) {
        for (var pos = 65536, i = 0; i < set.length; i += 2) {
          if (pos += set[i], pos > code)
            return !1;
          if (pos += set[i + 1], pos >= code)
            return !0;
        }
      }
      function isIdentifierStart(code, astral) {
        return code < 65 ? code === 36 : code < 91 ? !0 : code < 97 ? code === 95 : code < 123 ? !0 : code <= 65535 ? code >= 170 && nonASCIIidentifierStart.test(String.fromCharCode(code)) : astral === !1 ? !1 : isInAstralSet(code, astralIdentifierStartCodes);
      }
      function isIdentifierChar(code, astral) {
        return code < 48 ? code === 36 : code < 58 ? !0 : code < 65 ? !1 : code < 91 ? !0 : code < 97 ? code === 95 : code < 123 ? !0 : code <= 65535 ? code >= 170 && nonASCIIidentifier.test(String.fromCharCode(code)) : astral === !1 ? !1 : isInAstralSet(code, astralIdentifierStartCodes) || isInAstralSet(code, astralIdentifierCodes);
      }
      var TokenType = function(label, conf) {
        conf === void 0 && (conf = {}), this.label = label, this.keyword = conf.keyword, this.beforeExpr = !!conf.beforeExpr, this.startsExpr = !!conf.startsExpr, this.isLoop = !!conf.isLoop, this.isAssign = !!conf.isAssign, this.prefix = !!conf.prefix, this.postfix = !!conf.postfix, this.binop = conf.binop || null, this.updateContext = null;
      };
      function binop(name, prec) {
        return new TokenType(name, { beforeExpr: !0, binop: prec });
      }
      var beforeExpr = { beforeExpr: !0 }, startsExpr = { startsExpr: !0 }, keywords$1 = {};
      function kw(name, options) {
        return options === void 0 && (options = {}), options.keyword = name, keywords$1[name] = new TokenType(name, options);
      }
      var types = {
        num: new TokenType("num", startsExpr),
        regexp: new TokenType("regexp", startsExpr),
        string: new TokenType("string", startsExpr),
        name: new TokenType("name", startsExpr),
        eof: new TokenType("eof"),
        // Punctuation token types.
        bracketL: new TokenType("[", { beforeExpr: !0, startsExpr: !0 }),
        bracketR: new TokenType("]"),
        braceL: new TokenType("{", { beforeExpr: !0, startsExpr: !0 }),
        braceR: new TokenType("}"),
        parenL: new TokenType("(", { beforeExpr: !0, startsExpr: !0 }),
        parenR: new TokenType(")"),
        comma: new TokenType(",", beforeExpr),
        semi: new TokenType(";", beforeExpr),
        colon: new TokenType(":", beforeExpr),
        dot: new TokenType("."),
        question: new TokenType("?", beforeExpr),
        questionDot: new TokenType("?."),
        arrow: new TokenType("=>", beforeExpr),
        template: new TokenType("template"),
        invalidTemplate: new TokenType("invalidTemplate"),
        ellipsis: new TokenType("...", beforeExpr),
        backQuote: new TokenType("`", startsExpr),
        dollarBraceL: new TokenType("${", { beforeExpr: !0, startsExpr: !0 }),
        // Operators. These carry several kinds of properties to help the
        // parser use them properly (the presence of these properties is
        // what categorizes them as operators).
        //
        // `binop`, when present, specifies that this operator is a binary
        // operator, and will refer to its precedence.
        //
        // `prefix` and `postfix` mark the operator as a prefix or postfix
        // unary operator.
        //
        // `isAssign` marks all of `=`, `+=`, `-=` etcetera, which act as
        // binary operators with a very low precedence, that should result
        // in AssignmentExpression nodes.
        eq: new TokenType("=", { beforeExpr: !0, isAssign: !0 }),
        assign: new TokenType("_=", { beforeExpr: !0, isAssign: !0 }),
        incDec: new TokenType("++/--", { prefix: !0, postfix: !0, startsExpr: !0 }),
        prefix: new TokenType("!/~", { beforeExpr: !0, prefix: !0, startsExpr: !0 }),
        logicalOR: binop("||", 1),
        logicalAND: binop("&&", 2),
        bitwiseOR: binop("|", 3),
        bitwiseXOR: binop("^", 4),
        bitwiseAND: binop("&", 5),
        equality: binop("==/!=/===/!==", 6),
        relational: binop("</>/<=/>=", 7),
        bitShift: binop("<</>>/>>>", 8),
        plusMin: new TokenType("+/-", { beforeExpr: !0, binop: 9, prefix: !0, startsExpr: !0 }),
        modulo: binop("%", 10),
        star: binop("*", 10),
        slash: binop("/", 10),
        starstar: new TokenType("**", { beforeExpr: !0 }),
        coalesce: binop("??", 1),
        // Keyword token types.
        _break: kw("break"),
        _case: kw("case", beforeExpr),
        _catch: kw("catch"),
        _continue: kw("continue"),
        _debugger: kw("debugger"),
        _default: kw("default", beforeExpr),
        _do: kw("do", { isLoop: !0, beforeExpr: !0 }),
        _else: kw("else", beforeExpr),
        _finally: kw("finally"),
        _for: kw("for", { isLoop: !0 }),
        _function: kw("function", startsExpr),
        _if: kw("if"),
        _return: kw("return", beforeExpr),
        _switch: kw("switch"),
        _throw: kw("throw", beforeExpr),
        _try: kw("try"),
        _var: kw("var"),
        _const: kw("const"),
        _while: kw("while", { isLoop: !0 }),
        _with: kw("with"),
        _new: kw("new", { beforeExpr: !0, startsExpr: !0 }),
        _this: kw("this", startsExpr),
        _super: kw("super", startsExpr),
        _class: kw("class", startsExpr),
        _extends: kw("extends", beforeExpr),
        _export: kw("export"),
        _import: kw("import", startsExpr),
        _null: kw("null", startsExpr),
        _true: kw("true", startsExpr),
        _false: kw("false", startsExpr),
        _in: kw("in", { beforeExpr: !0, binop: 7 }),
        _instanceof: kw("instanceof", { beforeExpr: !0, binop: 7 }),
        _typeof: kw("typeof", { beforeExpr: !0, prefix: !0, startsExpr: !0 }),
        _void: kw("void", { beforeExpr: !0, prefix: !0, startsExpr: !0 }),
        _delete: kw("delete", { beforeExpr: !0, prefix: !0, startsExpr: !0 })
      }, lineBreak = /\r\n?|\n|\u2028|\u2029/, lineBreakG = new RegExp(lineBreak.source, "g");
      function isNewLine(code, ecma2019String) {
        return code === 10 || code === 13 || !ecma2019String && (code === 8232 || code === 8233);
      }
      var nonASCIIwhitespace = /[\u1680\u2000-\u200a\u202f\u205f\u3000\ufeff]/, skipWhiteSpace = /(?:\s|\/\/.*|\/\*[^]*?\*\/)*/g, ref = Object.prototype, hasOwnProperty = ref.hasOwnProperty, toString = ref.toString;
      function has(obj, propName) {
        return hasOwnProperty.call(obj, propName);
      }
      var isArray = Array.isArray || (function(obj) {
        return toString.call(obj) === "[object Array]";
      });
      function wordsRegexp(words) {
        return new RegExp("^(?:" + words.replace(/ /g, "|") + ")$");
      }
      var Position = function(line, col) {
        this.line = line, this.column = col;
      };
      Position.prototype.offset = function(n) {
        return new Position(this.line, this.column + n);
      };
      var SourceLocation = function(p, start, end) {
        this.start = start, this.end = end, p.sourceFile !== null && (this.source = p.sourceFile);
      };
      function getLineInfo(input, offset) {
        for (var line = 1, cur = 0; ; ) {
          lineBreakG.lastIndex = cur;
          var match = lineBreakG.exec(input);
          if (match && match.index < offset)
            ++line, cur = match.index + match[0].length;
          else
            return new Position(line, offset - cur);
        }
      }
      var defaultOptions = {
        // `ecmaVersion` indicates the ECMAScript version to parse. Must be
        // either 3, 5, 6 (2015), 7 (2016), 8 (2017), 9 (2018), or 10
        // (2019). This influences support for strict mode, the set of
        // reserved words, and support for new syntax features. The default
        // is 10.
        ecmaVersion: 10,
        // `sourceType` indicates the mode the code should be parsed in.
        // Can be either `"script"` or `"module"`. This influences global
        // strict mode and parsing of `import` and `export` declarations.
        sourceType: "script",
        // `onInsertedSemicolon` can be a callback that will be called
        // when a semicolon is automatically inserted. It will be passed
        // the position of the comma as an offset, and if `locations` is
        // enabled, it is given the location as a `{line, column}` object
        // as second argument.
        onInsertedSemicolon: null,
        // `onTrailingComma` is similar to `onInsertedSemicolon`, but for
        // trailing commas.
        onTrailingComma: null,
        // By default, reserved words are only enforced if ecmaVersion >= 5.
        // Set `allowReserved` to a boolean value to explicitly turn this on
        // an off. When this option has the value "never", reserved words
        // and keywords can also not be used as property names.
        allowReserved: null,
        // When enabled, a return at the top level is not considered an
        // error.
        allowReturnOutsideFunction: !1,
        // When enabled, import/export statements are not constrained to
        // appearing at the top of the program.
        allowImportExportEverywhere: !1,
        // When enabled, await identifiers are allowed to appear at the top-level scope,
        // but they are still not allowed in non-async functions.
        allowAwaitOutsideFunction: !1,
        // When enabled, hashbang directive in the beginning of file
        // is allowed and treated as a line comment.
        allowHashBang: !1,
        // When `locations` is on, `loc` properties holding objects with
        // `start` and `end` properties in `{line, column}` form (with
        // line being 1-based and column 0-based) will be attached to the
        // nodes.
        locations: !1,
        // A function can be passed as `onToken` option, which will
        // cause Acorn to call that function with object in the same
        // format as tokens returned from `tokenizer().getToken()`. Note
        // that you are not allowed to call the parser from the
        // callback—that will corrupt its internal state.
        onToken: null,
        // A function can be passed as `onComment` option, which will
        // cause Acorn to call that function with `(block, text, start,
        // end)` parameters whenever a comment is skipped. `block` is a
        // boolean indicating whether this is a block (`/* */`) comment,
        // `text` is the content of the comment, and `start` and `end` are
        // character offsets that denote the start and end of the comment.
        // When the `locations` option is on, two more parameters are
        // passed, the full `{line, column}` locations of the start and
        // end of the comments. Note that you are not allowed to call the
        // parser from the callback—that will corrupt its internal state.
        onComment: null,
        // Nodes have their start and end characters offsets recorded in
        // `start` and `end` properties (directly on the node, rather than
        // the `loc` object, which holds line/column data. To also add a
        // [semi-standardized][range] `range` property holding a `[start,
        // end]` array with the same numbers, set the `ranges` option to
        // `true`.
        //
        // [range]: https://bugzilla.mozilla.org/show_bug.cgi?id=745678
        ranges: !1,
        // It is possible to parse multiple files into a single AST by
        // passing the tree produced by parsing the first file as
        // `program` option in subsequent parses. This will add the
        // toplevel forms of the parsed file to the `Program` (top) node
        // of an existing parse tree.
        program: null,
        // When `locations` is on, you can pass this to record the source
        // file in every node's `loc` object.
        sourceFile: null,
        // This value, if given, is stored in every node, whether
        // `locations` is on or off.
        directSourceFile: null,
        // When enabled, parenthesized expressions are represented by
        // (non-standard) ParenthesizedExpression nodes
        preserveParens: !1
      };
      function getOptions(opts) {
        var options = {};
        for (var opt in defaultOptions)
          options[opt] = opts && has(opts, opt) ? opts[opt] : defaultOptions[opt];
        if (options.ecmaVersion >= 2015 && (options.ecmaVersion -= 2009), options.allowReserved == null && (options.allowReserved = options.ecmaVersion < 5), isArray(options.onToken)) {
          var tokens = options.onToken;
          options.onToken = function(token) {
            return tokens.push(token);
          };
        }
        return isArray(options.onComment) && (options.onComment = pushComment(options, options.onComment)), options;
      }
      function pushComment(options, array) {
        return function(block, text, start, end, startLoc, endLoc) {
          var comment = {
            type: block ? "Block" : "Line",
            value: text,
            start,
            end
          };
          options.locations && (comment.loc = new SourceLocation(this, startLoc, endLoc)), options.ranges && (comment.range = [start, end]), array.push(comment);
        };
      }
      var SCOPE_TOP = 1, SCOPE_FUNCTION = 2, SCOPE_VAR = SCOPE_TOP | SCOPE_FUNCTION, SCOPE_ASYNC = 4, SCOPE_GENERATOR = 8, SCOPE_ARROW = 16, SCOPE_SIMPLE_CATCH = 32, SCOPE_SUPER = 64, SCOPE_DIRECT_SUPER = 128;
      function functionFlags(async, generator) {
        return SCOPE_FUNCTION | (async ? SCOPE_ASYNC : 0) | (generator ? SCOPE_GENERATOR : 0);
      }
      var BIND_NONE = 0, BIND_VAR = 1, BIND_LEXICAL = 2, BIND_FUNCTION = 3, BIND_SIMPLE_CATCH = 4, BIND_OUTSIDE = 5, Parser2 = function(options, input, startPos) {
        this.options = options = getOptions(options), this.sourceFile = options.sourceFile, this.keywords = wordsRegexp(keywords[options.ecmaVersion >= 6 ? 6 : options.sourceType === "module" ? "5module" : 5]);
        var reserved = "";
        if (options.allowReserved !== !0) {
          for (var v = options.ecmaVersion; !(reserved = reservedWords[v]); v--)
            ;
          options.sourceType === "module" && (reserved += " await");
        }
        this.reservedWords = wordsRegexp(reserved);
        var reservedStrict = (reserved ? reserved + " " : "") + reservedWords.strict;
        this.reservedWordsStrict = wordsRegexp(reservedStrict), this.reservedWordsStrictBind = wordsRegexp(reservedStrict + " " + reservedWords.strictBind), this.input = String(input), this.containsEsc = !1, startPos ? (this.pos = startPos, this.lineStart = this.input.lastIndexOf(`
`, startPos - 1) + 1, this.curLine = this.input.slice(0, this.lineStart).split(lineBreak).length) : (this.pos = this.lineStart = 0, this.curLine = 1), this.type = types.eof, this.value = null, this.start = this.end = this.pos, this.startLoc = this.endLoc = this.curPosition(), this.lastTokEndLoc = this.lastTokStartLoc = null, this.lastTokStart = this.lastTokEnd = this.pos, this.context = this.initialContext(), this.exprAllowed = !0, this.inModule = options.sourceType === "module", this.strict = this.inModule || this.strictDirective(this.pos), this.potentialArrowAt = -1, this.yieldPos = this.awaitPos = this.awaitIdentPos = 0, this.labels = [], this.undefinedExports = {}, this.pos === 0 && options.allowHashBang && this.input.slice(0, 2) === "#!" && this.skipLineComment(2), this.scopeStack = [], this.enterScope(SCOPE_TOP), this.regexpState = null;
      }, prototypeAccessors = { inFunction: { configurable: !0 }, inGenerator: { configurable: !0 }, inAsync: { configurable: !0 }, allowSuper: { configurable: !0 }, allowDirectSuper: { configurable: !0 }, treatFunctionsAsVar: { configurable: !0 } };
      Parser2.prototype.parse = function() {
        var node = this.options.program || this.startNode();
        return this.nextToken(), this.parseTopLevel(node);
      }, prototypeAccessors.inFunction.get = function() {
        return (this.currentVarScope().flags & SCOPE_FUNCTION) > 0;
      }, prototypeAccessors.inGenerator.get = function() {
        return (this.currentVarScope().flags & SCOPE_GENERATOR) > 0;
      }, prototypeAccessors.inAsync.get = function() {
        return (this.currentVarScope().flags & SCOPE_ASYNC) > 0;
      }, prototypeAccessors.allowSuper.get = function() {
        return (this.currentThisScope().flags & SCOPE_SUPER) > 0;
      }, prototypeAccessors.allowDirectSuper.get = function() {
        return (this.currentThisScope().flags & SCOPE_DIRECT_SUPER) > 0;
      }, prototypeAccessors.treatFunctionsAsVar.get = function() {
        return this.treatFunctionsAsVarInScope(this.currentScope());
      }, Parser2.prototype.inNonArrowFunction = function() {
        return (this.currentThisScope().flags & SCOPE_FUNCTION) > 0;
      }, Parser2.extend = function() {
        for (var plugins = [], len = arguments.length; len--; ) plugins[len] = arguments[len];
        for (var cls = this, i = 0; i < plugins.length; i++)
          cls = plugins[i](cls);
        return cls;
      }, Parser2.parse = function(input, options) {
        return new this(options, input).parse();
      }, Parser2.parseExpressionAt = function(input, pos, options) {
        var parser = new this(options, input, pos);
        return parser.nextToken(), parser.parseExpression();
      }, Parser2.tokenizer = function(input, options) {
        return new this(options, input);
      }, Object.defineProperties(Parser2.prototype, prototypeAccessors);
      var pp = Parser2.prototype, literal = /^(?:'((?:\\.|[^'\\])*?)'|"((?:\\.|[^"\\])*?)")/;
      pp.strictDirective = function(start) {
        for (; ; ) {
          skipWhiteSpace.lastIndex = start, start += skipWhiteSpace.exec(this.input)[0].length;
          var match = literal.exec(this.input.slice(start));
          if (!match)
            return !1;
          if ((match[1] || match[2]) === "use strict") {
            skipWhiteSpace.lastIndex = start + match[0].length;
            var spaceAfter = skipWhiteSpace.exec(this.input), end = spaceAfter.index + spaceAfter[0].length, next = this.input.charAt(end);
            return next === ";" || next === "}" || lineBreak.test(spaceAfter[0]) && !(/[(`.[+\-/*%<>=,?^&]/.test(next) || next === "!" && this.input.charAt(end + 1) === "=");
          }
          start += match[0].length, skipWhiteSpace.lastIndex = start, start += skipWhiteSpace.exec(this.input)[0].length, this.input[start] === ";" && start++;
        }
      }, pp.eat = function(type) {
        return this.type === type ? (this.next(), !0) : !1;
      }, pp.isContextual = function(name) {
        return this.type === types.name && this.value === name && !this.containsEsc;
      }, pp.eatContextual = function(name) {
        return this.isContextual(name) ? (this.next(), !0) : !1;
      }, pp.expectContextual = function(name) {
        this.eatContextual(name) || this.unexpected();
      }, pp.canInsertSemicolon = function() {
        return this.type === types.eof || this.type === types.braceR || lineBreak.test(this.input.slice(this.lastTokEnd, this.start));
      }, pp.insertSemicolon = function() {
        if (this.canInsertSemicolon())
          return this.options.onInsertedSemicolon && this.options.onInsertedSemicolon(this.lastTokEnd, this.lastTokEndLoc), !0;
      }, pp.semicolon = function() {
        !this.eat(types.semi) && !this.insertSemicolon() && this.unexpected();
      }, pp.afterTrailingComma = function(tokType, notNext) {
        if (this.type === tokType)
          return this.options.onTrailingComma && this.options.onTrailingComma(this.lastTokStart, this.lastTokStartLoc), notNext || this.next(), !0;
      }, pp.expect = function(type) {
        this.eat(type) || this.unexpected();
      }, pp.unexpected = function(pos) {
        this.raise(pos ?? this.start, "Unexpected token");
      };
      function DestructuringErrors() {
        this.shorthandAssign = this.trailingComma = this.parenthesizedAssign = this.parenthesizedBind = this.doubleProto = -1;
      }
      pp.checkPatternErrors = function(refDestructuringErrors, isAssign) {
        if (refDestructuringErrors) {
          refDestructuringErrors.trailingComma > -1 && this.raiseRecoverable(refDestructuringErrors.trailingComma, "Comma is not permitted after the rest element");
          var parens = isAssign ? refDestructuringErrors.parenthesizedAssign : refDestructuringErrors.parenthesizedBind;
          parens > -1 && this.raiseRecoverable(parens, "Parenthesized pattern");
        }
      }, pp.checkExpressionErrors = function(refDestructuringErrors, andThrow) {
        if (!refDestructuringErrors)
          return !1;
        var shorthandAssign = refDestructuringErrors.shorthandAssign, doubleProto = refDestructuringErrors.doubleProto;
        if (!andThrow)
          return shorthandAssign >= 0 || doubleProto >= 0;
        shorthandAssign >= 0 && this.raise(shorthandAssign, "Shorthand property assignments are valid only in destructuring patterns"), doubleProto >= 0 && this.raiseRecoverable(doubleProto, "Redefinition of __proto__ property");
      }, pp.checkYieldAwaitInDefaultParams = function() {
        this.yieldPos && (!this.awaitPos || this.yieldPos < this.awaitPos) && this.raise(this.yieldPos, "Yield expression cannot be a default value"), this.awaitPos && this.raise(this.awaitPos, "Await expression cannot be a default value");
      }, pp.isSimpleAssignTarget = function(expr) {
        return expr.type === "ParenthesizedExpression" ? this.isSimpleAssignTarget(expr.expression) : expr.type === "Identifier" || expr.type === "MemberExpression";
      };
      var pp$1 = Parser2.prototype;
      pp$1.parseTopLevel = function(node) {
        var exports3 = {};
        for (node.body || (node.body = []); this.type !== types.eof; ) {
          var stmt = this.parseStatement(null, !0, exports3);
          node.body.push(stmt);
        }
        if (this.inModule)
          for (var i = 0, list = Object.keys(this.undefinedExports); i < list.length; i += 1) {
            var name = list[i];
            this.raiseRecoverable(this.undefinedExports[name].start, "Export '" + name + "' is not defined");
          }
        return this.adaptDirectivePrologue(node.body), this.next(), node.sourceType = this.options.sourceType, this.finishNode(node, "Program");
      };
      var loopLabel = { kind: "loop" }, switchLabel = { kind: "switch" };
      pp$1.isLet = function(context) {
        if (this.options.ecmaVersion < 6 || !this.isContextual("let"))
          return !1;
        skipWhiteSpace.lastIndex = this.pos;
        var skip = skipWhiteSpace.exec(this.input), next = this.pos + skip[0].length, nextCh = this.input.charCodeAt(next);
        if (nextCh === 91)
          return !0;
        if (context)
          return !1;
        if (nextCh === 123)
          return !0;
        if (isIdentifierStart(nextCh, !0)) {
          for (var pos = next + 1; isIdentifierChar(this.input.charCodeAt(pos), !0); )
            ++pos;
          var ident = this.input.slice(next, pos);
          if (!keywordRelationalOperator.test(ident))
            return !0;
        }
        return !1;
      }, pp$1.isAsyncFunction = function() {
        if (this.options.ecmaVersion < 8 || !this.isContextual("async"))
          return !1;
        skipWhiteSpace.lastIndex = this.pos;
        var skip = skipWhiteSpace.exec(this.input), next = this.pos + skip[0].length;
        return !lineBreak.test(this.input.slice(this.pos, next)) && this.input.slice(next, next + 8) === "function" && (next + 8 === this.input.length || !isIdentifierChar(this.input.charAt(next + 8)));
      }, pp$1.parseStatement = function(context, topLevel, exports3) {
        var starttype = this.type, node = this.startNode(), kind;
        switch (this.isLet(context) && (starttype = types._var, kind = "let"), starttype) {
          case types._break:
          case types._continue:
            return this.parseBreakContinueStatement(node, starttype.keyword);
          case types._debugger:
            return this.parseDebuggerStatement(node);
          case types._do:
            return this.parseDoStatement(node);
          case types._for:
            return this.parseForStatement(node);
          case types._function:
            return context && (this.strict || context !== "if" && context !== "label") && this.options.ecmaVersion >= 6 && this.unexpected(), this.parseFunctionStatement(node, !1, !context);
          case types._class:
            return context && this.unexpected(), this.parseClass(node, !0);
          case types._if:
            return this.parseIfStatement(node);
          case types._return:
            return this.parseReturnStatement(node);
          case types._switch:
            return this.parseSwitchStatement(node);
          case types._throw:
            return this.parseThrowStatement(node);
          case types._try:
            return this.parseTryStatement(node);
          case types._const:
          case types._var:
            return kind = kind || this.value, context && kind !== "var" && this.unexpected(), this.parseVarStatement(node, kind);
          case types._while:
            return this.parseWhileStatement(node);
          case types._with:
            return this.parseWithStatement(node);
          case types.braceL:
            return this.parseBlock(!0, node);
          case types.semi:
            return this.parseEmptyStatement(node);
          case types._export:
          case types._import:
            if (this.options.ecmaVersion > 10 && starttype === types._import) {
              skipWhiteSpace.lastIndex = this.pos;
              var skip = skipWhiteSpace.exec(this.input), next = this.pos + skip[0].length, nextCh = this.input.charCodeAt(next);
              if (nextCh === 40 || nextCh === 46)
                return this.parseExpressionStatement(node, this.parseExpression());
            }
            return this.options.allowImportExportEverywhere || (topLevel || this.raise(this.start, "'import' and 'export' may only appear at the top level"), this.inModule || this.raise(this.start, "'import' and 'export' may appear only with 'sourceType: module'")), starttype === types._import ? this.parseImport(node) : this.parseExport(node, exports3);
          // If the statement does not start with a statement keyword or a
          // brace, it's an ExpressionStatement or LabeledStatement. We
          // simply start parsing an expression, and afterwards, if the
          // next token is a colon and the expression was a simple
          // Identifier node, we switch to interpreting it as a label.
          default:
            if (this.isAsyncFunction())
              return context && this.unexpected(), this.next(), this.parseFunctionStatement(node, !0, !context);
            var maybeName = this.value, expr = this.parseExpression();
            return starttype === types.name && expr.type === "Identifier" && this.eat(types.colon) ? this.parseLabeledStatement(node, maybeName, expr, context) : this.parseExpressionStatement(node, expr);
        }
      }, pp$1.parseBreakContinueStatement = function(node, keyword) {
        var isBreak = keyword === "break";
        this.next(), this.eat(types.semi) || this.insertSemicolon() ? node.label = null : this.type !== types.name ? this.unexpected() : (node.label = this.parseIdent(), this.semicolon());
        for (var i = 0; i < this.labels.length; ++i) {
          var lab = this.labels[i];
          if ((node.label == null || lab.name === node.label.name) && (lab.kind != null && (isBreak || lab.kind === "loop") || node.label && isBreak))
            break;
        }
        return i === this.labels.length && this.raise(node.start, "Unsyntactic " + keyword), this.finishNode(node, isBreak ? "BreakStatement" : "ContinueStatement");
      }, pp$1.parseDebuggerStatement = function(node) {
        return this.next(), this.semicolon(), this.finishNode(node, "DebuggerStatement");
      }, pp$1.parseDoStatement = function(node) {
        return this.next(), this.labels.push(loopLabel), node.body = this.parseStatement("do"), this.labels.pop(), this.expect(types._while), node.test = this.parseParenExpression(), this.options.ecmaVersion >= 6 ? this.eat(types.semi) : this.semicolon(), this.finishNode(node, "DoWhileStatement");
      }, pp$1.parseForStatement = function(node) {
        this.next();
        var awaitAt = this.options.ecmaVersion >= 9 && (this.inAsync || !this.inFunction && this.options.allowAwaitOutsideFunction) && this.eatContextual("await") ? this.lastTokStart : -1;
        if (this.labels.push(loopLabel), this.enterScope(0), this.expect(types.parenL), this.type === types.semi)
          return awaitAt > -1 && this.unexpected(awaitAt), this.parseFor(node, null);
        var isLet = this.isLet();
        if (this.type === types._var || this.type === types._const || isLet) {
          var init$1 = this.startNode(), kind = isLet ? "let" : this.value;
          return this.next(), this.parseVar(init$1, !0, kind), this.finishNode(init$1, "VariableDeclaration"), (this.type === types._in || this.options.ecmaVersion >= 6 && this.isContextual("of")) && init$1.declarations.length === 1 ? (this.options.ecmaVersion >= 9 && (this.type === types._in ? awaitAt > -1 && this.unexpected(awaitAt) : node.await = awaitAt > -1), this.parseForIn(node, init$1)) : (awaitAt > -1 && this.unexpected(awaitAt), this.parseFor(node, init$1));
        }
        var refDestructuringErrors = new DestructuringErrors(), init = this.parseExpression(!0, refDestructuringErrors);
        return this.type === types._in || this.options.ecmaVersion >= 6 && this.isContextual("of") ? (this.options.ecmaVersion >= 9 && (this.type === types._in ? awaitAt > -1 && this.unexpected(awaitAt) : node.await = awaitAt > -1), this.toAssignable(init, !1, refDestructuringErrors), this.checkLVal(init), this.parseForIn(node, init)) : (this.checkExpressionErrors(refDestructuringErrors, !0), awaitAt > -1 && this.unexpected(awaitAt), this.parseFor(node, init));
      }, pp$1.parseFunctionStatement = function(node, isAsync, declarationPosition) {
        return this.next(), this.parseFunction(node, FUNC_STATEMENT | (declarationPosition ? 0 : FUNC_HANGING_STATEMENT), !1, isAsync);
      }, pp$1.parseIfStatement = function(node) {
        return this.next(), node.test = this.parseParenExpression(), node.consequent = this.parseStatement("if"), node.alternate = this.eat(types._else) ? this.parseStatement("if") : null, this.finishNode(node, "IfStatement");
      }, pp$1.parseReturnStatement = function(node) {
        return !this.inFunction && !this.options.allowReturnOutsideFunction && this.raise(this.start, "'return' outside of function"), this.next(), this.eat(types.semi) || this.insertSemicolon() ? node.argument = null : (node.argument = this.parseExpression(), this.semicolon()), this.finishNode(node, "ReturnStatement");
      }, pp$1.parseSwitchStatement = function(node) {
        this.next(), node.discriminant = this.parseParenExpression(), node.cases = [], this.expect(types.braceL), this.labels.push(switchLabel), this.enterScope(0);
        for (var cur, sawDefault = !1; this.type !== types.braceR; )
          if (this.type === types._case || this.type === types._default) {
            var isCase = this.type === types._case;
            cur && this.finishNode(cur, "SwitchCase"), node.cases.push(cur = this.startNode()), cur.consequent = [], this.next(), isCase ? cur.test = this.parseExpression() : (sawDefault && this.raiseRecoverable(this.lastTokStart, "Multiple default clauses"), sawDefault = !0, cur.test = null), this.expect(types.colon);
          } else
            cur || this.unexpected(), cur.consequent.push(this.parseStatement(null));
        return this.exitScope(), cur && this.finishNode(cur, "SwitchCase"), this.next(), this.labels.pop(), this.finishNode(node, "SwitchStatement");
      }, pp$1.parseThrowStatement = function(node) {
        return this.next(), lineBreak.test(this.input.slice(this.lastTokEnd, this.start)) && this.raise(this.lastTokEnd, "Illegal newline after throw"), node.argument = this.parseExpression(), this.semicolon(), this.finishNode(node, "ThrowStatement");
      };
      var empty = [];
      pp$1.parseTryStatement = function(node) {
        if (this.next(), node.block = this.parseBlock(), node.handler = null, this.type === types._catch) {
          var clause = this.startNode();
          if (this.next(), this.eat(types.parenL)) {
            clause.param = this.parseBindingAtom();
            var simple2 = clause.param.type === "Identifier";
            this.enterScope(simple2 ? SCOPE_SIMPLE_CATCH : 0), this.checkLVal(clause.param, simple2 ? BIND_SIMPLE_CATCH : BIND_LEXICAL), this.expect(types.parenR);
          } else
            this.options.ecmaVersion < 10 && this.unexpected(), clause.param = null, this.enterScope(0);
          clause.body = this.parseBlock(!1), this.exitScope(), node.handler = this.finishNode(clause, "CatchClause");
        }
        return node.finalizer = this.eat(types._finally) ? this.parseBlock() : null, !node.handler && !node.finalizer && this.raise(node.start, "Missing catch or finally clause"), this.finishNode(node, "TryStatement");
      }, pp$1.parseVarStatement = function(node, kind) {
        return this.next(), this.parseVar(node, !1, kind), this.semicolon(), this.finishNode(node, "VariableDeclaration");
      }, pp$1.parseWhileStatement = function(node) {
        return this.next(), node.test = this.parseParenExpression(), this.labels.push(loopLabel), node.body = this.parseStatement("while"), this.labels.pop(), this.finishNode(node, "WhileStatement");
      }, pp$1.parseWithStatement = function(node) {
        return this.strict && this.raise(this.start, "'with' in strict mode"), this.next(), node.object = this.parseParenExpression(), node.body = this.parseStatement("with"), this.finishNode(node, "WithStatement");
      }, pp$1.parseEmptyStatement = function(node) {
        return this.next(), this.finishNode(node, "EmptyStatement");
      }, pp$1.parseLabeledStatement = function(node, maybeName, expr, context) {
        for (var i$1 = 0, list = this.labels; i$1 < list.length; i$1 += 1) {
          var label = list[i$1];
          label.name === maybeName && this.raise(expr.start, "Label '" + maybeName + "' is already declared");
        }
        for (var kind = this.type.isLoop ? "loop" : this.type === types._switch ? "switch" : null, i = this.labels.length - 1; i >= 0; i--) {
          var label$1 = this.labels[i];
          if (label$1.statementStart === node.start)
            label$1.statementStart = this.start, label$1.kind = kind;
          else
            break;
        }
        return this.labels.push({ name: maybeName, kind, statementStart: this.start }), node.body = this.parseStatement(context ? context.indexOf("label") === -1 ? context + "label" : context : "label"), this.labels.pop(), node.label = expr, this.finishNode(node, "LabeledStatement");
      }, pp$1.parseExpressionStatement = function(node, expr) {
        return node.expression = expr, this.semicolon(), this.finishNode(node, "ExpressionStatement");
      }, pp$1.parseBlock = function(createNewLexicalScope, node, exitStrict) {
        for (createNewLexicalScope === void 0 && (createNewLexicalScope = !0), node === void 0 && (node = this.startNode()), node.body = [], this.expect(types.braceL), createNewLexicalScope && this.enterScope(0); this.type !== types.braceR; ) {
          var stmt = this.parseStatement(null);
          node.body.push(stmt);
        }
        return exitStrict && (this.strict = !1), this.next(), createNewLexicalScope && this.exitScope(), this.finishNode(node, "BlockStatement");
      }, pp$1.parseFor = function(node, init) {
        return node.init = init, this.expect(types.semi), node.test = this.type === types.semi ? null : this.parseExpression(), this.expect(types.semi), node.update = this.type === types.parenR ? null : this.parseExpression(), this.expect(types.parenR), node.body = this.parseStatement("for"), this.exitScope(), this.labels.pop(), this.finishNode(node, "ForStatement");
      }, pp$1.parseForIn = function(node, init) {
        var isForIn = this.type === types._in;
        return this.next(), init.type === "VariableDeclaration" && init.declarations[0].init != null && (!isForIn || this.options.ecmaVersion < 8 || this.strict || init.kind !== "var" || init.declarations[0].id.type !== "Identifier") ? this.raise(
          init.start,
          (isForIn ? "for-in" : "for-of") + " loop variable declaration may not have an initializer"
        ) : init.type === "AssignmentPattern" && this.raise(init.start, "Invalid left-hand side in for-loop"), node.left = init, node.right = isForIn ? this.parseExpression() : this.parseMaybeAssign(), this.expect(types.parenR), node.body = this.parseStatement("for"), this.exitScope(), this.labels.pop(), this.finishNode(node, isForIn ? "ForInStatement" : "ForOfStatement");
      }, pp$1.parseVar = function(node, isFor, kind) {
        for (node.declarations = [], node.kind = kind; ; ) {
          var decl = this.startNode();
          if (this.parseVarId(decl, kind), this.eat(types.eq) ? decl.init = this.parseMaybeAssign(isFor) : kind === "const" && !(this.type === types._in || this.options.ecmaVersion >= 6 && this.isContextual("of")) ? this.unexpected() : decl.id.type !== "Identifier" && !(isFor && (this.type === types._in || this.isContextual("of"))) ? this.raise(this.lastTokEnd, "Complex binding patterns require an initialization value") : decl.init = null, node.declarations.push(this.finishNode(decl, "VariableDeclarator")), !this.eat(types.comma))
            break;
        }
        return node;
      }, pp$1.parseVarId = function(decl, kind) {
        decl.id = this.parseBindingAtom(), this.checkLVal(decl.id, kind === "var" ? BIND_VAR : BIND_LEXICAL, !1);
      };
      var FUNC_STATEMENT = 1, FUNC_HANGING_STATEMENT = 2, FUNC_NULLABLE_ID = 4;
      pp$1.parseFunction = function(node, statement, allowExpressionBody, isAsync) {
        this.initFunction(node), (this.options.ecmaVersion >= 9 || this.options.ecmaVersion >= 6 && !isAsync) && (this.type === types.star && statement & FUNC_HANGING_STATEMENT && this.unexpected(), node.generator = this.eat(types.star)), this.options.ecmaVersion >= 8 && (node.async = !!isAsync), statement & FUNC_STATEMENT && (node.id = statement & FUNC_NULLABLE_ID && this.type !== types.name ? null : this.parseIdent(), node.id && !(statement & FUNC_HANGING_STATEMENT) && this.checkLVal(node.id, this.strict || node.generator || node.async ? this.treatFunctionsAsVar ? BIND_VAR : BIND_LEXICAL : BIND_FUNCTION));
        var oldYieldPos = this.yieldPos, oldAwaitPos = this.awaitPos, oldAwaitIdentPos = this.awaitIdentPos;
        return this.yieldPos = 0, this.awaitPos = 0, this.awaitIdentPos = 0, this.enterScope(functionFlags(node.async, node.generator)), statement & FUNC_STATEMENT || (node.id = this.type === types.name ? this.parseIdent() : null), this.parseFunctionParams(node), this.parseFunctionBody(node, allowExpressionBody, !1), this.yieldPos = oldYieldPos, this.awaitPos = oldAwaitPos, this.awaitIdentPos = oldAwaitIdentPos, this.finishNode(node, statement & FUNC_STATEMENT ? "FunctionDeclaration" : "FunctionExpression");
      }, pp$1.parseFunctionParams = function(node) {
        this.expect(types.parenL), node.params = this.parseBindingList(types.parenR, !1, this.options.ecmaVersion >= 8), this.checkYieldAwaitInDefaultParams();
      }, pp$1.parseClass = function(node, isStatement) {
        this.next();
        var oldStrict = this.strict;
        this.strict = !0, this.parseClassId(node, isStatement), this.parseClassSuper(node);
        var classBody = this.startNode(), hadConstructor = !1;
        for (classBody.body = [], this.expect(types.braceL); this.type !== types.braceR; ) {
          var element = this.parseClassElement(node.superClass !== null);
          element && (classBody.body.push(element), element.type === "MethodDefinition" && element.kind === "constructor" && (hadConstructor && this.raise(element.start, "Duplicate constructor in the same class"), hadConstructor = !0));
        }
        return this.strict = oldStrict, this.next(), node.body = this.finishNode(classBody, "ClassBody"), this.finishNode(node, isStatement ? "ClassDeclaration" : "ClassExpression");
      }, pp$1.parseClassElement = function(constructorAllowsSuper) {
        var this$1 = this;
        if (this.eat(types.semi))
          return null;
        var method = this.startNode(), tryContextual = function(k, noLineBreak) {
          noLineBreak === void 0 && (noLineBreak = !1);
          var start = this$1.start, startLoc = this$1.startLoc;
          return this$1.eatContextual(k) ? this$1.type !== types.parenL && (!noLineBreak || !this$1.canInsertSemicolon()) ? !0 : (method.key && this$1.unexpected(), method.computed = !1, method.key = this$1.startNodeAt(start, startLoc), method.key.name = k, this$1.finishNode(method.key, "Identifier"), !1) : !1;
        };
        method.kind = "method", method.static = tryContextual("static");
        var isGenerator = this.eat(types.star), isAsync = !1;
        isGenerator || (this.options.ecmaVersion >= 8 && tryContextual("async", !0) ? (isAsync = !0, isGenerator = this.options.ecmaVersion >= 9 && this.eat(types.star)) : tryContextual("get") ? method.kind = "get" : tryContextual("set") && (method.kind = "set")), method.key || this.parsePropertyName(method);
        var key = method.key, allowsDirectSuper = !1;
        return !method.computed && !method.static && (key.type === "Identifier" && key.name === "constructor" || key.type === "Literal" && key.value === "constructor") ? (method.kind !== "method" && this.raise(key.start, "Constructor can't have get/set modifier"), isGenerator && this.raise(key.start, "Constructor can't be a generator"), isAsync && this.raise(key.start, "Constructor can't be an async method"), method.kind = "constructor", allowsDirectSuper = constructorAllowsSuper) : method.static && key.type === "Identifier" && key.name === "prototype" && this.raise(key.start, "Classes may not have a static property named prototype"), this.parseClassMethod(method, isGenerator, isAsync, allowsDirectSuper), method.kind === "get" && method.value.params.length !== 0 && this.raiseRecoverable(method.value.start, "getter should have no params"), method.kind === "set" && method.value.params.length !== 1 && this.raiseRecoverable(method.value.start, "setter should have exactly one param"), method.kind === "set" && method.value.params[0].type === "RestElement" && this.raiseRecoverable(method.value.params[0].start, "Setter cannot use rest params"), method;
      }, pp$1.parseClassMethod = function(method, isGenerator, isAsync, allowsDirectSuper) {
        return method.value = this.parseMethod(isGenerator, isAsync, allowsDirectSuper), this.finishNode(method, "MethodDefinition");
      }, pp$1.parseClassId = function(node, isStatement) {
        this.type === types.name ? (node.id = this.parseIdent(), isStatement && this.checkLVal(node.id, BIND_LEXICAL, !1)) : (isStatement === !0 && this.unexpected(), node.id = null);
      }, pp$1.parseClassSuper = function(node) {
        node.superClass = this.eat(types._extends) ? this.parseExprSubscripts() : null;
      }, pp$1.parseExport = function(node, exports3) {
        if (this.next(), this.eat(types.star))
          return this.options.ecmaVersion >= 11 && (this.eatContextual("as") ? (node.exported = this.parseIdent(!0), this.checkExport(exports3, node.exported.name, this.lastTokStart)) : node.exported = null), this.expectContextual("from"), this.type !== types.string && this.unexpected(), node.source = this.parseExprAtom(), this.semicolon(), this.finishNode(node, "ExportAllDeclaration");
        if (this.eat(types._default)) {
          this.checkExport(exports3, "default", this.lastTokStart);
          var isAsync;
          if (this.type === types._function || (isAsync = this.isAsyncFunction())) {
            var fNode = this.startNode();
            this.next(), isAsync && this.next(), node.declaration = this.parseFunction(fNode, FUNC_STATEMENT | FUNC_NULLABLE_ID, !1, isAsync);
          } else if (this.type === types._class) {
            var cNode = this.startNode();
            node.declaration = this.parseClass(cNode, "nullableID");
          } else
            node.declaration = this.parseMaybeAssign(), this.semicolon();
          return this.finishNode(node, "ExportDefaultDeclaration");
        }
        if (this.shouldParseExportStatement())
          node.declaration = this.parseStatement(null), node.declaration.type === "VariableDeclaration" ? this.checkVariableExport(exports3, node.declaration.declarations) : this.checkExport(exports3, node.declaration.id.name, node.declaration.id.start), node.specifiers = [], node.source = null;
        else {
          if (node.declaration = null, node.specifiers = this.parseExportSpecifiers(exports3), this.eatContextual("from"))
            this.type !== types.string && this.unexpected(), node.source = this.parseExprAtom();
          else {
            for (var i = 0, list = node.specifiers; i < list.length; i += 1) {
              var spec = list[i];
              this.checkUnreserved(spec.local), this.checkLocalExport(spec.local);
            }
            node.source = null;
          }
          this.semicolon();
        }
        return this.finishNode(node, "ExportNamedDeclaration");
      }, pp$1.checkExport = function(exports3, name, pos) {
        exports3 && (has(exports3, name) && this.raiseRecoverable(pos, "Duplicate export '" + name + "'"), exports3[name] = !0);
      }, pp$1.checkPatternExport = function(exports3, pat) {
        var type = pat.type;
        if (type === "Identifier")
          this.checkExport(exports3, pat.name, pat.start);
        else if (type === "ObjectPattern")
          for (var i = 0, list = pat.properties; i < list.length; i += 1) {
            var prop = list[i];
            this.checkPatternExport(exports3, prop);
          }
        else if (type === "ArrayPattern")
          for (var i$1 = 0, list$1 = pat.elements; i$1 < list$1.length; i$1 += 1) {
            var elt = list$1[i$1];
            elt && this.checkPatternExport(exports3, elt);
          }
        else type === "Property" ? this.checkPatternExport(exports3, pat.value) : type === "AssignmentPattern" ? this.checkPatternExport(exports3, pat.left) : type === "RestElement" ? this.checkPatternExport(exports3, pat.argument) : type === "ParenthesizedExpression" && this.checkPatternExport(exports3, pat.expression);
      }, pp$1.checkVariableExport = function(exports3, decls) {
        if (exports3)
          for (var i = 0, list = decls; i < list.length; i += 1) {
            var decl = list[i];
            this.checkPatternExport(exports3, decl.id);
          }
      }, pp$1.shouldParseExportStatement = function() {
        return this.type.keyword === "var" || this.type.keyword === "const" || this.type.keyword === "class" || this.type.keyword === "function" || this.isLet() || this.isAsyncFunction();
      }, pp$1.parseExportSpecifiers = function(exports3) {
        var nodes = [], first = !0;
        for (this.expect(types.braceL); !this.eat(types.braceR); ) {
          if (first)
            first = !1;
          else if (this.expect(types.comma), this.afterTrailingComma(types.braceR))
            break;
          var node = this.startNode();
          node.local = this.parseIdent(!0), node.exported = this.eatContextual("as") ? this.parseIdent(!0) : node.local, this.checkExport(exports3, node.exported.name, node.exported.start), nodes.push(this.finishNode(node, "ExportSpecifier"));
        }
        return nodes;
      }, pp$1.parseImport = function(node) {
        return this.next(), this.type === types.string ? (node.specifiers = empty, node.source = this.parseExprAtom()) : (node.specifiers = this.parseImportSpecifiers(), this.expectContextual("from"), node.source = this.type === types.string ? this.parseExprAtom() : this.unexpected()), this.semicolon(), this.finishNode(node, "ImportDeclaration");
      }, pp$1.parseImportSpecifiers = function() {
        var nodes = [], first = !0;
        if (this.type === types.name) {
          var node = this.startNode();
          if (node.local = this.parseIdent(), this.checkLVal(node.local, BIND_LEXICAL), nodes.push(this.finishNode(node, "ImportDefaultSpecifier")), !this.eat(types.comma))
            return nodes;
        }
        if (this.type === types.star) {
          var node$1 = this.startNode();
          return this.next(), this.expectContextual("as"), node$1.local = this.parseIdent(), this.checkLVal(node$1.local, BIND_LEXICAL), nodes.push(this.finishNode(node$1, "ImportNamespaceSpecifier")), nodes;
        }
        for (this.expect(types.braceL); !this.eat(types.braceR); ) {
          if (first)
            first = !1;
          else if (this.expect(types.comma), this.afterTrailingComma(types.braceR))
            break;
          var node$2 = this.startNode();
          node$2.imported = this.parseIdent(!0), this.eatContextual("as") ? node$2.local = this.parseIdent() : (this.checkUnreserved(node$2.imported), node$2.local = node$2.imported), this.checkLVal(node$2.local, BIND_LEXICAL), nodes.push(this.finishNode(node$2, "ImportSpecifier"));
        }
        return nodes;
      }, pp$1.adaptDirectivePrologue = function(statements) {
        for (var i = 0; i < statements.length && this.isDirectiveCandidate(statements[i]); ++i)
          statements[i].directive = statements[i].expression.raw.slice(1, -1);
      }, pp$1.isDirectiveCandidate = function(statement) {
        return statement.type === "ExpressionStatement" && statement.expression.type === "Literal" && typeof statement.expression.value == "string" && // Reject parenthesized strings.
        (this.input[statement.start] === '"' || this.input[statement.start] === "'");
      };
      var pp$2 = Parser2.prototype;
      pp$2.toAssignable = function(node, isBinding, refDestructuringErrors) {
        if (this.options.ecmaVersion >= 6 && node)
          switch (node.type) {
            case "Identifier":
              this.inAsync && node.name === "await" && this.raise(node.start, "Cannot use 'await' as identifier inside an async function");
              break;
            case "ObjectPattern":
            case "ArrayPattern":
            case "RestElement":
              break;
            case "ObjectExpression":
              node.type = "ObjectPattern", refDestructuringErrors && this.checkPatternErrors(refDestructuringErrors, !0);
              for (var i = 0, list = node.properties; i < list.length; i += 1) {
                var prop = list[i];
                this.toAssignable(prop, isBinding), prop.type === "RestElement" && (prop.argument.type === "ArrayPattern" || prop.argument.type === "ObjectPattern") && this.raise(prop.argument.start, "Unexpected token");
              }
              break;
            case "Property":
              node.kind !== "init" && this.raise(node.key.start, "Object pattern can't contain getter or setter"), this.toAssignable(node.value, isBinding);
              break;
            case "ArrayExpression":
              node.type = "ArrayPattern", refDestructuringErrors && this.checkPatternErrors(refDestructuringErrors, !0), this.toAssignableList(node.elements, isBinding);
              break;
            case "SpreadElement":
              node.type = "RestElement", this.toAssignable(node.argument, isBinding), node.argument.type === "AssignmentPattern" && this.raise(node.argument.start, "Rest elements cannot have a default value");
              break;
            case "AssignmentExpression":
              node.operator !== "=" && this.raise(node.left.end, "Only '=' operator can be used for specifying default value."), node.type = "AssignmentPattern", delete node.operator, this.toAssignable(node.left, isBinding);
            // falls through to AssignmentPattern
            case "AssignmentPattern":
              break;
            case "ParenthesizedExpression":
              this.toAssignable(node.expression, isBinding, refDestructuringErrors);
              break;
            case "ChainExpression":
              this.raiseRecoverable(node.start, "Optional chaining cannot appear in left-hand side");
              break;
            case "MemberExpression":
              if (!isBinding)
                break;
            default:
              this.raise(node.start, "Assigning to rvalue");
          }
        else refDestructuringErrors && this.checkPatternErrors(refDestructuringErrors, !0);
        return node;
      }, pp$2.toAssignableList = function(exprList, isBinding) {
        for (var end = exprList.length, i = 0; i < end; i++) {
          var elt = exprList[i];
          elt && this.toAssignable(elt, isBinding);
        }
        if (end) {
          var last = exprList[end - 1];
          this.options.ecmaVersion === 6 && isBinding && last && last.type === "RestElement" && last.argument.type !== "Identifier" && this.unexpected(last.argument.start);
        }
        return exprList;
      }, pp$2.parseSpread = function(refDestructuringErrors) {
        var node = this.startNode();
        return this.next(), node.argument = this.parseMaybeAssign(!1, refDestructuringErrors), this.finishNode(node, "SpreadElement");
      }, pp$2.parseRestBinding = function() {
        var node = this.startNode();
        return this.next(), this.options.ecmaVersion === 6 && this.type !== types.name && this.unexpected(), node.argument = this.parseBindingAtom(), this.finishNode(node, "RestElement");
      }, pp$2.parseBindingAtom = function() {
        if (this.options.ecmaVersion >= 6)
          switch (this.type) {
            case types.bracketL:
              var node = this.startNode();
              return this.next(), node.elements = this.parseBindingList(types.bracketR, !0, !0), this.finishNode(node, "ArrayPattern");
            case types.braceL:
              return this.parseObj(!0);
          }
        return this.parseIdent();
      }, pp$2.parseBindingList = function(close, allowEmpty, allowTrailingComma) {
        for (var elts = [], first = !0; !this.eat(close); )
          if (first ? first = !1 : this.expect(types.comma), allowEmpty && this.type === types.comma)
            elts.push(null);
          else {
            if (allowTrailingComma && this.afterTrailingComma(close))
              break;
            if (this.type === types.ellipsis) {
              var rest = this.parseRestBinding();
              this.parseBindingListItem(rest), elts.push(rest), this.type === types.comma && this.raise(this.start, "Comma is not permitted after the rest element"), this.expect(close);
              break;
            } else {
              var elem = this.parseMaybeDefault(this.start, this.startLoc);
              this.parseBindingListItem(elem), elts.push(elem);
            }
          }
        return elts;
      }, pp$2.parseBindingListItem = function(param) {
        return param;
      }, pp$2.parseMaybeDefault = function(startPos, startLoc, left) {
        if (left = left || this.parseBindingAtom(), this.options.ecmaVersion < 6 || !this.eat(types.eq))
          return left;
        var node = this.startNodeAt(startPos, startLoc);
        return node.left = left, node.right = this.parseMaybeAssign(), this.finishNode(node, "AssignmentPattern");
      }, pp$2.checkLVal = function(expr, bindingType, checkClashes) {
        switch (bindingType === void 0 && (bindingType = BIND_NONE), expr.type) {
          case "Identifier":
            bindingType === BIND_LEXICAL && expr.name === "let" && this.raiseRecoverable(expr.start, "let is disallowed as a lexically bound name"), this.strict && this.reservedWordsStrictBind.test(expr.name) && this.raiseRecoverable(expr.start, (bindingType ? "Binding " : "Assigning to ") + expr.name + " in strict mode"), checkClashes && (has(checkClashes, expr.name) && this.raiseRecoverable(expr.start, "Argument name clash"), checkClashes[expr.name] = !0), bindingType !== BIND_NONE && bindingType !== BIND_OUTSIDE && this.declareName(expr.name, bindingType, expr.start);
            break;
          case "ChainExpression":
            this.raiseRecoverable(expr.start, "Optional chaining cannot appear in left-hand side");
            break;
          case "MemberExpression":
            bindingType && this.raiseRecoverable(expr.start, "Binding member expression");
            break;
          case "ObjectPattern":
            for (var i = 0, list = expr.properties; i < list.length; i += 1) {
              var prop = list[i];
              this.checkLVal(prop, bindingType, checkClashes);
            }
            break;
          case "Property":
            this.checkLVal(expr.value, bindingType, checkClashes);
            break;
          case "ArrayPattern":
            for (var i$1 = 0, list$1 = expr.elements; i$1 < list$1.length; i$1 += 1) {
              var elem = list$1[i$1];
              elem && this.checkLVal(elem, bindingType, checkClashes);
            }
            break;
          case "AssignmentPattern":
            this.checkLVal(expr.left, bindingType, checkClashes);
            break;
          case "RestElement":
            this.checkLVal(expr.argument, bindingType, checkClashes);
            break;
          case "ParenthesizedExpression":
            this.checkLVal(expr.expression, bindingType, checkClashes);
            break;
          default:
            this.raise(expr.start, (bindingType ? "Binding" : "Assigning to") + " rvalue");
        }
      };
      var pp$3 = Parser2.prototype;
      pp$3.checkPropClash = function(prop, propHash, refDestructuringErrors) {
        if (!(this.options.ecmaVersion >= 9 && prop.type === "SpreadElement") && !(this.options.ecmaVersion >= 6 && (prop.computed || prop.method || prop.shorthand))) {
          var key = prop.key, name;
          switch (key.type) {
            case "Identifier":
              name = key.name;
              break;
            case "Literal":
              name = String(key.value);
              break;
            default:
              return;
          }
          var kind = prop.kind;
          if (this.options.ecmaVersion >= 6) {
            name === "__proto__" && kind === "init" && (propHash.proto && (refDestructuringErrors ? refDestructuringErrors.doubleProto < 0 && (refDestructuringErrors.doubleProto = key.start) : this.raiseRecoverable(key.start, "Redefinition of __proto__ property")), propHash.proto = !0);
            return;
          }
          name = "$" + name;
          var other = propHash[name];
          if (other) {
            var redefinition;
            kind === "init" ? redefinition = this.strict && other.init || other.get || other.set : redefinition = other.init || other[kind], redefinition && this.raiseRecoverable(key.start, "Redefinition of property");
          } else
            other = propHash[name] = {
              init: !1,
              get: !1,
              set: !1
            };
          other[kind] = !0;
        }
      }, pp$3.parseExpression = function(noIn, refDestructuringErrors) {
        var startPos = this.start, startLoc = this.startLoc, expr = this.parseMaybeAssign(noIn, refDestructuringErrors);
        if (this.type === types.comma) {
          var node = this.startNodeAt(startPos, startLoc);
          for (node.expressions = [expr]; this.eat(types.comma); )
            node.expressions.push(this.parseMaybeAssign(noIn, refDestructuringErrors));
          return this.finishNode(node, "SequenceExpression");
        }
        return expr;
      }, pp$3.parseMaybeAssign = function(noIn, refDestructuringErrors, afterLeftParse) {
        if (this.isContextual("yield")) {
          if (this.inGenerator)
            return this.parseYield(noIn);
          this.exprAllowed = !1;
        }
        var ownDestructuringErrors = !1, oldParenAssign = -1, oldTrailingComma = -1;
        refDestructuringErrors ? (oldParenAssign = refDestructuringErrors.parenthesizedAssign, oldTrailingComma = refDestructuringErrors.trailingComma, refDestructuringErrors.parenthesizedAssign = refDestructuringErrors.trailingComma = -1) : (refDestructuringErrors = new DestructuringErrors(), ownDestructuringErrors = !0);
        var startPos = this.start, startLoc = this.startLoc;
        (this.type === types.parenL || this.type === types.name) && (this.potentialArrowAt = this.start);
        var left = this.parseMaybeConditional(noIn, refDestructuringErrors);
        if (afterLeftParse && (left = afterLeftParse.call(this, left, startPos, startLoc)), this.type.isAssign) {
          var node = this.startNodeAt(startPos, startLoc);
          return node.operator = this.value, node.left = this.type === types.eq ? this.toAssignable(left, !1, refDestructuringErrors) : left, ownDestructuringErrors || (refDestructuringErrors.parenthesizedAssign = refDestructuringErrors.trailingComma = refDestructuringErrors.doubleProto = -1), refDestructuringErrors.shorthandAssign >= node.left.start && (refDestructuringErrors.shorthandAssign = -1), this.checkLVal(left), this.next(), node.right = this.parseMaybeAssign(noIn), this.finishNode(node, "AssignmentExpression");
        } else
          ownDestructuringErrors && this.checkExpressionErrors(refDestructuringErrors, !0);
        return oldParenAssign > -1 && (refDestructuringErrors.parenthesizedAssign = oldParenAssign), oldTrailingComma > -1 && (refDestructuringErrors.trailingComma = oldTrailingComma), left;
      }, pp$3.parseMaybeConditional = function(noIn, refDestructuringErrors) {
        var startPos = this.start, startLoc = this.startLoc, expr = this.parseExprOps(noIn, refDestructuringErrors);
        if (this.checkExpressionErrors(refDestructuringErrors))
          return expr;
        if (this.eat(types.question)) {
          var node = this.startNodeAt(startPos, startLoc);
          return node.test = expr, node.consequent = this.parseMaybeAssign(), this.expect(types.colon), node.alternate = this.parseMaybeAssign(noIn), this.finishNode(node, "ConditionalExpression");
        }
        return expr;
      }, pp$3.parseExprOps = function(noIn, refDestructuringErrors) {
        var startPos = this.start, startLoc = this.startLoc, expr = this.parseMaybeUnary(refDestructuringErrors, !1);
        return this.checkExpressionErrors(refDestructuringErrors) || expr.start === startPos && expr.type === "ArrowFunctionExpression" ? expr : this.parseExprOp(expr, startPos, startLoc, -1, noIn);
      }, pp$3.parseExprOp = function(left, leftStartPos, leftStartLoc, minPrec, noIn) {
        var prec = this.type.binop;
        if (prec != null && (!noIn || this.type !== types._in) && prec > minPrec) {
          var logical = this.type === types.logicalOR || this.type === types.logicalAND, coalesce = this.type === types.coalesce;
          coalesce && (prec = types.logicalAND.binop);
          var op = this.value;
          this.next();
          var startPos = this.start, startLoc = this.startLoc, right = this.parseExprOp(this.parseMaybeUnary(null, !1), startPos, startLoc, prec, noIn), node = this.buildBinary(leftStartPos, leftStartLoc, left, right, op, logical || coalesce);
          return (logical && this.type === types.coalesce || coalesce && (this.type === types.logicalOR || this.type === types.logicalAND)) && this.raiseRecoverable(this.start, "Logical expressions and coalesce expressions cannot be mixed. Wrap either by parentheses"), this.parseExprOp(node, leftStartPos, leftStartLoc, minPrec, noIn);
        }
        return left;
      }, pp$3.buildBinary = function(startPos, startLoc, left, right, op, logical) {
        var node = this.startNodeAt(startPos, startLoc);
        return node.left = left, node.operator = op, node.right = right, this.finishNode(node, logical ? "LogicalExpression" : "BinaryExpression");
      }, pp$3.parseMaybeUnary = function(refDestructuringErrors, sawUnary) {
        var startPos = this.start, startLoc = this.startLoc, expr;
        if (this.isContextual("await") && (this.inAsync || !this.inFunction && this.options.allowAwaitOutsideFunction))
          expr = this.parseAwait(), sawUnary = !0;
        else if (this.type.prefix) {
          var node = this.startNode(), update = this.type === types.incDec;
          node.operator = this.value, node.prefix = !0, this.next(), node.argument = this.parseMaybeUnary(null, !0), this.checkExpressionErrors(refDestructuringErrors, !0), update ? this.checkLVal(node.argument) : this.strict && node.operator === "delete" && node.argument.type === "Identifier" ? this.raiseRecoverable(node.start, "Deleting local variable in strict mode") : sawUnary = !0, expr = this.finishNode(node, update ? "UpdateExpression" : "UnaryExpression");
        } else {
          if (expr = this.parseExprSubscripts(refDestructuringErrors), this.checkExpressionErrors(refDestructuringErrors))
            return expr;
          for (; this.type.postfix && !this.canInsertSemicolon(); ) {
            var node$1 = this.startNodeAt(startPos, startLoc);
            node$1.operator = this.value, node$1.prefix = !1, node$1.argument = expr, this.checkLVal(expr), this.next(), expr = this.finishNode(node$1, "UpdateExpression");
          }
        }
        return !sawUnary && this.eat(types.starstar) ? this.buildBinary(startPos, startLoc, expr, this.parseMaybeUnary(null, !1), "**", !1) : expr;
      }, pp$3.parseExprSubscripts = function(refDestructuringErrors) {
        var startPos = this.start, startLoc = this.startLoc, expr = this.parseExprAtom(refDestructuringErrors);
        if (expr.type === "ArrowFunctionExpression" && this.input.slice(this.lastTokStart, this.lastTokEnd) !== ")")
          return expr;
        var result = this.parseSubscripts(expr, startPos, startLoc);
        return refDestructuringErrors && result.type === "MemberExpression" && (refDestructuringErrors.parenthesizedAssign >= result.start && (refDestructuringErrors.parenthesizedAssign = -1), refDestructuringErrors.parenthesizedBind >= result.start && (refDestructuringErrors.parenthesizedBind = -1)), result;
      }, pp$3.parseSubscripts = function(base2, startPos, startLoc, noCalls) {
        for (var maybeAsyncArrow = this.options.ecmaVersion >= 8 && base2.type === "Identifier" && base2.name === "async" && this.lastTokEnd === base2.end && !this.canInsertSemicolon() && base2.end - base2.start === 5 && this.potentialArrowAt === base2.start, optionalChained = !1; ; ) {
          var element = this.parseSubscript(base2, startPos, startLoc, noCalls, maybeAsyncArrow, optionalChained);
          if (element.optional && (optionalChained = !0), element === base2 || element.type === "ArrowFunctionExpression") {
            if (optionalChained) {
              var chainNode = this.startNodeAt(startPos, startLoc);
              chainNode.expression = element, element = this.finishNode(chainNode, "ChainExpression");
            }
            return element;
          }
          base2 = element;
        }
      }, pp$3.parseSubscript = function(base2, startPos, startLoc, noCalls, maybeAsyncArrow, optionalChained) {
        var optionalSupported = this.options.ecmaVersion >= 11, optional = optionalSupported && this.eat(types.questionDot);
        noCalls && optional && this.raise(this.lastTokStart, "Optional chaining cannot appear in the callee of new expressions");
        var computed = this.eat(types.bracketL);
        if (computed || optional && this.type !== types.parenL && this.type !== types.backQuote || this.eat(types.dot)) {
          var node = this.startNodeAt(startPos, startLoc);
          node.object = base2, node.property = computed ? this.parseExpression() : this.parseIdent(this.options.allowReserved !== "never"), node.computed = !!computed, computed && this.expect(types.bracketR), optionalSupported && (node.optional = optional), base2 = this.finishNode(node, "MemberExpression");
        } else if (!noCalls && this.eat(types.parenL)) {
          var refDestructuringErrors = new DestructuringErrors(), oldYieldPos = this.yieldPos, oldAwaitPos = this.awaitPos, oldAwaitIdentPos = this.awaitIdentPos;
          this.yieldPos = 0, this.awaitPos = 0, this.awaitIdentPos = 0;
          var exprList = this.parseExprList(types.parenR, this.options.ecmaVersion >= 8, !1, refDestructuringErrors);
          if (maybeAsyncArrow && !optional && !this.canInsertSemicolon() && this.eat(types.arrow))
            return this.checkPatternErrors(refDestructuringErrors, !1), this.checkYieldAwaitInDefaultParams(), this.awaitIdentPos > 0 && this.raise(this.awaitIdentPos, "Cannot use 'await' as identifier inside an async function"), this.yieldPos = oldYieldPos, this.awaitPos = oldAwaitPos, this.awaitIdentPos = oldAwaitIdentPos, this.parseArrowExpression(this.startNodeAt(startPos, startLoc), exprList, !0);
          this.checkExpressionErrors(refDestructuringErrors, !0), this.yieldPos = oldYieldPos || this.yieldPos, this.awaitPos = oldAwaitPos || this.awaitPos, this.awaitIdentPos = oldAwaitIdentPos || this.awaitIdentPos;
          var node$1 = this.startNodeAt(startPos, startLoc);
          node$1.callee = base2, node$1.arguments = exprList, optionalSupported && (node$1.optional = optional), base2 = this.finishNode(node$1, "CallExpression");
        } else if (this.type === types.backQuote) {
          (optional || optionalChained) && this.raise(this.start, "Optional chaining cannot appear in the tag of tagged template expressions");
          var node$2 = this.startNodeAt(startPos, startLoc);
          node$2.tag = base2, node$2.quasi = this.parseTemplate({ isTagged: !0 }), base2 = this.finishNode(node$2, "TaggedTemplateExpression");
        }
        return base2;
      }, pp$3.parseExprAtom = function(refDestructuringErrors) {
        this.type === types.slash && this.readRegexp();
        var node, canBeArrow = this.potentialArrowAt === this.start;
        switch (this.type) {
          case types._super:
            return this.allowSuper || this.raise(this.start, "'super' keyword outside a method"), node = this.startNode(), this.next(), this.type === types.parenL && !this.allowDirectSuper && this.raise(node.start, "super() call outside constructor of a subclass"), this.type !== types.dot && this.type !== types.bracketL && this.type !== types.parenL && this.unexpected(), this.finishNode(node, "Super");
          case types._this:
            return node = this.startNode(), this.next(), this.finishNode(node, "ThisExpression");
          case types.name:
            var startPos = this.start, startLoc = this.startLoc, containsEsc = this.containsEsc, id = this.parseIdent(!1);
            if (this.options.ecmaVersion >= 8 && !containsEsc && id.name === "async" && !this.canInsertSemicolon() && this.eat(types._function))
              return this.parseFunction(this.startNodeAt(startPos, startLoc), 0, !1, !0);
            if (canBeArrow && !this.canInsertSemicolon()) {
              if (this.eat(types.arrow))
                return this.parseArrowExpression(this.startNodeAt(startPos, startLoc), [id], !1);
              if (this.options.ecmaVersion >= 8 && id.name === "async" && this.type === types.name && !containsEsc)
                return id = this.parseIdent(!1), (this.canInsertSemicolon() || !this.eat(types.arrow)) && this.unexpected(), this.parseArrowExpression(this.startNodeAt(startPos, startLoc), [id], !0);
            }
            return id;
          case types.regexp:
            var value = this.value;
            return node = this.parseLiteral(value.value), node.regex = { pattern: value.pattern, flags: value.flags }, node;
          case types.num:
          case types.string:
            return this.parseLiteral(this.value);
          case types._null:
          case types._true:
          case types._false:
            return node = this.startNode(), node.value = this.type === types._null ? null : this.type === types._true, node.raw = this.type.keyword, this.next(), this.finishNode(node, "Literal");
          case types.parenL:
            var start = this.start, expr = this.parseParenAndDistinguishExpression(canBeArrow);
            return refDestructuringErrors && (refDestructuringErrors.parenthesizedAssign < 0 && !this.isSimpleAssignTarget(expr) && (refDestructuringErrors.parenthesizedAssign = start), refDestructuringErrors.parenthesizedBind < 0 && (refDestructuringErrors.parenthesizedBind = start)), expr;
          case types.bracketL:
            return node = this.startNode(), this.next(), node.elements = this.parseExprList(types.bracketR, !0, !0, refDestructuringErrors), this.finishNode(node, "ArrayExpression");
          case types.braceL:
            return this.parseObj(!1, refDestructuringErrors);
          case types._function:
            return node = this.startNode(), this.next(), this.parseFunction(node, 0);
          case types._class:
            return this.parseClass(this.startNode(), !1);
          case types._new:
            return this.parseNew();
          case types.backQuote:
            return this.parseTemplate();
          case types._import:
            return this.options.ecmaVersion >= 11 ? this.parseExprImport() : this.unexpected();
          default:
            this.unexpected();
        }
      }, pp$3.parseExprImport = function() {
        var node = this.startNode();
        this.containsEsc && this.raiseRecoverable(this.start, "Escape sequence in keyword import");
        var meta = this.parseIdent(!0);
        switch (this.type) {
          case types.parenL:
            return this.parseDynamicImport(node);
          case types.dot:
            return node.meta = meta, this.parseImportMeta(node);
          default:
            this.unexpected();
        }
      }, pp$3.parseDynamicImport = function(node) {
        if (this.next(), node.source = this.parseMaybeAssign(), !this.eat(types.parenR)) {
          var errorPos = this.start;
          this.eat(types.comma) && this.eat(types.parenR) ? this.raiseRecoverable(errorPos, "Trailing comma is not allowed in import()") : this.unexpected(errorPos);
        }
        return this.finishNode(node, "ImportExpression");
      }, pp$3.parseImportMeta = function(node) {
        this.next();
        var containsEsc = this.containsEsc;
        return node.property = this.parseIdent(!0), node.property.name !== "meta" && this.raiseRecoverable(node.property.start, "The only valid meta property for import is 'import.meta'"), containsEsc && this.raiseRecoverable(node.start, "'import.meta' must not contain escaped characters"), this.options.sourceType !== "module" && this.raiseRecoverable(node.start, "Cannot use 'import.meta' outside a module"), this.finishNode(node, "MetaProperty");
      }, pp$3.parseLiteral = function(value) {
        var node = this.startNode();
        return node.value = value, node.raw = this.input.slice(this.start, this.end), node.raw.charCodeAt(node.raw.length - 1) === 110 && (node.bigint = node.raw.slice(0, -1).replace(/_/g, "")), this.next(), this.finishNode(node, "Literal");
      }, pp$3.parseParenExpression = function() {
        this.expect(types.parenL);
        var val = this.parseExpression();
        return this.expect(types.parenR), val;
      }, pp$3.parseParenAndDistinguishExpression = function(canBeArrow) {
        var startPos = this.start, startLoc = this.startLoc, val, allowTrailingComma = this.options.ecmaVersion >= 8;
        if (this.options.ecmaVersion >= 6) {
          this.next();
          var innerStartPos = this.start, innerStartLoc = this.startLoc, exprList = [], first = !0, lastIsComma = !1, refDestructuringErrors = new DestructuringErrors(), oldYieldPos = this.yieldPos, oldAwaitPos = this.awaitPos, spreadStart;
          for (this.yieldPos = 0, this.awaitPos = 0; this.type !== types.parenR; )
            if (first ? first = !1 : this.expect(types.comma), allowTrailingComma && this.afterTrailingComma(types.parenR, !0)) {
              lastIsComma = !0;
              break;
            } else if (this.type === types.ellipsis) {
              spreadStart = this.start, exprList.push(this.parseParenItem(this.parseRestBinding())), this.type === types.comma && this.raise(this.start, "Comma is not permitted after the rest element");
              break;
            } else
              exprList.push(this.parseMaybeAssign(!1, refDestructuringErrors, this.parseParenItem));
          var innerEndPos = this.start, innerEndLoc = this.startLoc;
          if (this.expect(types.parenR), canBeArrow && !this.canInsertSemicolon() && this.eat(types.arrow))
            return this.checkPatternErrors(refDestructuringErrors, !1), this.checkYieldAwaitInDefaultParams(), this.yieldPos = oldYieldPos, this.awaitPos = oldAwaitPos, this.parseParenArrowList(startPos, startLoc, exprList);
          (!exprList.length || lastIsComma) && this.unexpected(this.lastTokStart), spreadStart && this.unexpected(spreadStart), this.checkExpressionErrors(refDestructuringErrors, !0), this.yieldPos = oldYieldPos || this.yieldPos, this.awaitPos = oldAwaitPos || this.awaitPos, exprList.length > 1 ? (val = this.startNodeAt(innerStartPos, innerStartLoc), val.expressions = exprList, this.finishNodeAt(val, "SequenceExpression", innerEndPos, innerEndLoc)) : val = exprList[0];
        } else
          val = this.parseParenExpression();
        if (this.options.preserveParens) {
          var par = this.startNodeAt(startPos, startLoc);
          return par.expression = val, this.finishNode(par, "ParenthesizedExpression");
        } else
          return val;
      }, pp$3.parseParenItem = function(item) {
        return item;
      }, pp$3.parseParenArrowList = function(startPos, startLoc, exprList) {
        return this.parseArrowExpression(this.startNodeAt(startPos, startLoc), exprList);
      };
      var empty$1 = [];
      pp$3.parseNew = function() {
        this.containsEsc && this.raiseRecoverable(this.start, "Escape sequence in keyword new");
        var node = this.startNode(), meta = this.parseIdent(!0);
        if (this.options.ecmaVersion >= 6 && this.eat(types.dot)) {
          node.meta = meta;
          var containsEsc = this.containsEsc;
          return node.property = this.parseIdent(!0), node.property.name !== "target" && this.raiseRecoverable(node.property.start, "The only valid meta property for new is 'new.target'"), containsEsc && this.raiseRecoverable(node.start, "'new.target' must not contain escaped characters"), this.inNonArrowFunction() || this.raiseRecoverable(node.start, "'new.target' can only be used in functions"), this.finishNode(node, "MetaProperty");
        }
        var startPos = this.start, startLoc = this.startLoc, isImport = this.type === types._import;
        return node.callee = this.parseSubscripts(this.parseExprAtom(), startPos, startLoc, !0), isImport && node.callee.type === "ImportExpression" && this.raise(startPos, "Cannot use new with import()"), this.eat(types.parenL) ? node.arguments = this.parseExprList(types.parenR, this.options.ecmaVersion >= 8, !1) : node.arguments = empty$1, this.finishNode(node, "NewExpression");
      }, pp$3.parseTemplateElement = function(ref2) {
        var isTagged = ref2.isTagged, elem = this.startNode();
        return this.type === types.invalidTemplate ? (isTagged || this.raiseRecoverable(this.start, "Bad escape sequence in untagged template literal"), elem.value = {
          raw: this.value,
          cooked: null
        }) : elem.value = {
          raw: this.input.slice(this.start, this.end).replace(/\r\n?/g, `
`),
          cooked: this.value
        }, this.next(), elem.tail = this.type === types.backQuote, this.finishNode(elem, "TemplateElement");
      }, pp$3.parseTemplate = function(ref2) {
        ref2 === void 0 && (ref2 = {});
        var isTagged = ref2.isTagged;
        isTagged === void 0 && (isTagged = !1);
        var node = this.startNode();
        this.next(), node.expressions = [];
        var curElt = this.parseTemplateElement({ isTagged });
        for (node.quasis = [curElt]; !curElt.tail; )
          this.type === types.eof && this.raise(this.pos, "Unterminated template literal"), this.expect(types.dollarBraceL), node.expressions.push(this.parseExpression()), this.expect(types.braceR), node.quasis.push(curElt = this.parseTemplateElement({ isTagged }));
        return this.next(), this.finishNode(node, "TemplateLiteral");
      }, pp$3.isAsyncProp = function(prop) {
        return !prop.computed && prop.key.type === "Identifier" && prop.key.name === "async" && (this.type === types.name || this.type === types.num || this.type === types.string || this.type === types.bracketL || this.type.keyword || this.options.ecmaVersion >= 9 && this.type === types.star) && !lineBreak.test(this.input.slice(this.lastTokEnd, this.start));
      }, pp$3.parseObj = function(isPattern, refDestructuringErrors) {
        var node = this.startNode(), first = !0, propHash = {};
        for (node.properties = [], this.next(); !this.eat(types.braceR); ) {
          if (first)
            first = !1;
          else if (this.expect(types.comma), this.options.ecmaVersion >= 5 && this.afterTrailingComma(types.braceR))
            break;
          var prop = this.parseProperty(isPattern, refDestructuringErrors);
          isPattern || this.checkPropClash(prop, propHash, refDestructuringErrors), node.properties.push(prop);
        }
        return this.finishNode(node, isPattern ? "ObjectPattern" : "ObjectExpression");
      }, pp$3.parseProperty = function(isPattern, refDestructuringErrors) {
        var prop = this.startNode(), isGenerator, isAsync, startPos, startLoc;
        if (this.options.ecmaVersion >= 9 && this.eat(types.ellipsis))
          return isPattern ? (prop.argument = this.parseIdent(!1), this.type === types.comma && this.raise(this.start, "Comma is not permitted after the rest element"), this.finishNode(prop, "RestElement")) : (this.type === types.parenL && refDestructuringErrors && (refDestructuringErrors.parenthesizedAssign < 0 && (refDestructuringErrors.parenthesizedAssign = this.start), refDestructuringErrors.parenthesizedBind < 0 && (refDestructuringErrors.parenthesizedBind = this.start)), prop.argument = this.parseMaybeAssign(!1, refDestructuringErrors), this.type === types.comma && refDestructuringErrors && refDestructuringErrors.trailingComma < 0 && (refDestructuringErrors.trailingComma = this.start), this.finishNode(prop, "SpreadElement"));
        this.options.ecmaVersion >= 6 && (prop.method = !1, prop.shorthand = !1, (isPattern || refDestructuringErrors) && (startPos = this.start, startLoc = this.startLoc), isPattern || (isGenerator = this.eat(types.star)));
        var containsEsc = this.containsEsc;
        return this.parsePropertyName(prop), !isPattern && !containsEsc && this.options.ecmaVersion >= 8 && !isGenerator && this.isAsyncProp(prop) ? (isAsync = !0, isGenerator = this.options.ecmaVersion >= 9 && this.eat(types.star), this.parsePropertyName(prop, refDestructuringErrors)) : isAsync = !1, this.parsePropertyValue(prop, isPattern, isGenerator, isAsync, startPos, startLoc, refDestructuringErrors, containsEsc), this.finishNode(prop, "Property");
      }, pp$3.parsePropertyValue = function(prop, isPattern, isGenerator, isAsync, startPos, startLoc, refDestructuringErrors, containsEsc) {
        if ((isGenerator || isAsync) && this.type === types.colon && this.unexpected(), this.eat(types.colon))
          prop.value = isPattern ? this.parseMaybeDefault(this.start, this.startLoc) : this.parseMaybeAssign(!1, refDestructuringErrors), prop.kind = "init";
        else if (this.options.ecmaVersion >= 6 && this.type === types.parenL)
          isPattern && this.unexpected(), prop.kind = "init", prop.method = !0, prop.value = this.parseMethod(isGenerator, isAsync);
        else if (!isPattern && !containsEsc && this.options.ecmaVersion >= 5 && !prop.computed && prop.key.type === "Identifier" && (prop.key.name === "get" || prop.key.name === "set") && this.type !== types.comma && this.type !== types.braceR && this.type !== types.eq) {
          (isGenerator || isAsync) && this.unexpected(), prop.kind = prop.key.name, this.parsePropertyName(prop), prop.value = this.parseMethod(!1);
          var paramCount = prop.kind === "get" ? 0 : 1;
          if (prop.value.params.length !== paramCount) {
            var start = prop.value.start;
            prop.kind === "get" ? this.raiseRecoverable(start, "getter should have no params") : this.raiseRecoverable(start, "setter should have exactly one param");
          } else
            prop.kind === "set" && prop.value.params[0].type === "RestElement" && this.raiseRecoverable(prop.value.params[0].start, "Setter cannot use rest params");
        } else this.options.ecmaVersion >= 6 && !prop.computed && prop.key.type === "Identifier" ? ((isGenerator || isAsync) && this.unexpected(), this.checkUnreserved(prop.key), prop.key.name === "await" && !this.awaitIdentPos && (this.awaitIdentPos = startPos), prop.kind = "init", isPattern ? prop.value = this.parseMaybeDefault(startPos, startLoc, prop.key) : this.type === types.eq && refDestructuringErrors ? (refDestructuringErrors.shorthandAssign < 0 && (refDestructuringErrors.shorthandAssign = this.start), prop.value = this.parseMaybeDefault(startPos, startLoc, prop.key)) : prop.value = prop.key, prop.shorthand = !0) : this.unexpected();
      }, pp$3.parsePropertyName = function(prop) {
        if (this.options.ecmaVersion >= 6) {
          if (this.eat(types.bracketL))
            return prop.computed = !0, prop.key = this.parseMaybeAssign(), this.expect(types.bracketR), prop.key;
          prop.computed = !1;
        }
        return prop.key = this.type === types.num || this.type === types.string ? this.parseExprAtom() : this.parseIdent(this.options.allowReserved !== "never");
      }, pp$3.initFunction = function(node) {
        node.id = null, this.options.ecmaVersion >= 6 && (node.generator = node.expression = !1), this.options.ecmaVersion >= 8 && (node.async = !1);
      }, pp$3.parseMethod = function(isGenerator, isAsync, allowDirectSuper) {
        var node = this.startNode(), oldYieldPos = this.yieldPos, oldAwaitPos = this.awaitPos, oldAwaitIdentPos = this.awaitIdentPos;
        return this.initFunction(node), this.options.ecmaVersion >= 6 && (node.generator = isGenerator), this.options.ecmaVersion >= 8 && (node.async = !!isAsync), this.yieldPos = 0, this.awaitPos = 0, this.awaitIdentPos = 0, this.enterScope(functionFlags(isAsync, node.generator) | SCOPE_SUPER | (allowDirectSuper ? SCOPE_DIRECT_SUPER : 0)), this.expect(types.parenL), node.params = this.parseBindingList(types.parenR, !1, this.options.ecmaVersion >= 8), this.checkYieldAwaitInDefaultParams(), this.parseFunctionBody(node, !1, !0), this.yieldPos = oldYieldPos, this.awaitPos = oldAwaitPos, this.awaitIdentPos = oldAwaitIdentPos, this.finishNode(node, "FunctionExpression");
      }, pp$3.parseArrowExpression = function(node, params, isAsync) {
        var oldYieldPos = this.yieldPos, oldAwaitPos = this.awaitPos, oldAwaitIdentPos = this.awaitIdentPos;
        return this.enterScope(functionFlags(isAsync, !1) | SCOPE_ARROW), this.initFunction(node), this.options.ecmaVersion >= 8 && (node.async = !!isAsync), this.yieldPos = 0, this.awaitPos = 0, this.awaitIdentPos = 0, node.params = this.toAssignableList(params, !0), this.parseFunctionBody(node, !0, !1), this.yieldPos = oldYieldPos, this.awaitPos = oldAwaitPos, this.awaitIdentPos = oldAwaitIdentPos, this.finishNode(node, "ArrowFunctionExpression");
      }, pp$3.parseFunctionBody = function(node, isArrowFunction, isMethod) {
        var isExpression = isArrowFunction && this.type !== types.braceL, oldStrict = this.strict, useStrict = !1;
        if (isExpression)
          node.body = this.parseMaybeAssign(), node.expression = !0, this.checkParams(node, !1);
        else {
          var nonSimple = this.options.ecmaVersion >= 7 && !this.isSimpleParamList(node.params);
          (!oldStrict || nonSimple) && (useStrict = this.strictDirective(this.end), useStrict && nonSimple && this.raiseRecoverable(node.start, "Illegal 'use strict' directive in function with non-simple parameter list"));
          var oldLabels = this.labels;
          this.labels = [], useStrict && (this.strict = !0), this.checkParams(node, !oldStrict && !useStrict && !isArrowFunction && !isMethod && this.isSimpleParamList(node.params)), this.strict && node.id && this.checkLVal(node.id, BIND_OUTSIDE), node.body = this.parseBlock(!1, void 0, useStrict && !oldStrict), node.expression = !1, this.adaptDirectivePrologue(node.body.body), this.labels = oldLabels;
        }
        this.exitScope();
      }, pp$3.isSimpleParamList = function(params) {
        for (var i = 0, list = params; i < list.length; i += 1) {
          var param = list[i];
          if (param.type !== "Identifier")
            return !1;
        }
        return !0;
      }, pp$3.checkParams = function(node, allowDuplicates) {
        for (var nameHash = {}, i = 0, list = node.params; i < list.length; i += 1) {
          var param = list[i];
          this.checkLVal(param, BIND_VAR, allowDuplicates ? null : nameHash);
        }
      }, pp$3.parseExprList = function(close, allowTrailingComma, allowEmpty, refDestructuringErrors) {
        for (var elts = [], first = !0; !this.eat(close); ) {
          if (first)
            first = !1;
          else if (this.expect(types.comma), allowTrailingComma && this.afterTrailingComma(close))
            break;
          var elt = void 0;
          allowEmpty && this.type === types.comma ? elt = null : this.type === types.ellipsis ? (elt = this.parseSpread(refDestructuringErrors), refDestructuringErrors && this.type === types.comma && refDestructuringErrors.trailingComma < 0 && (refDestructuringErrors.trailingComma = this.start)) : elt = this.parseMaybeAssign(!1, refDestructuringErrors), elts.push(elt);
        }
        return elts;
      }, pp$3.checkUnreserved = function(ref2) {
        var start = ref2.start, end = ref2.end, name = ref2.name;
        if (this.inGenerator && name === "yield" && this.raiseRecoverable(start, "Cannot use 'yield' as identifier inside a generator"), this.inAsync && name === "await" && this.raiseRecoverable(start, "Cannot use 'await' as identifier inside an async function"), this.keywords.test(name) && this.raise(start, "Unexpected keyword '" + name + "'"), !(this.options.ecmaVersion < 6 && this.input.slice(start, end).indexOf("\\") !== -1)) {
          var re = this.strict ? this.reservedWordsStrict : this.reservedWords;
          re.test(name) && (!this.inAsync && name === "await" && this.raiseRecoverable(start, "Cannot use keyword 'await' outside an async function"), this.raiseRecoverable(start, "The keyword '" + name + "' is reserved"));
        }
      }, pp$3.parseIdent = function(liberal, isBinding) {
        var node = this.startNode();
        return this.type === types.name ? node.name = this.value : this.type.keyword ? (node.name = this.type.keyword, (node.name === "class" || node.name === "function") && (this.lastTokEnd !== this.lastTokStart + 1 || this.input.charCodeAt(this.lastTokStart) !== 46) && this.context.pop()) : this.unexpected(), this.next(!!liberal), this.finishNode(node, "Identifier"), liberal || (this.checkUnreserved(node), node.name === "await" && !this.awaitIdentPos && (this.awaitIdentPos = node.start)), node;
      }, pp$3.parseYield = function(noIn) {
        this.yieldPos || (this.yieldPos = this.start);
        var node = this.startNode();
        return this.next(), this.type === types.semi || this.canInsertSemicolon() || this.type !== types.star && !this.type.startsExpr ? (node.delegate = !1, node.argument = null) : (node.delegate = this.eat(types.star), node.argument = this.parseMaybeAssign(noIn)), this.finishNode(node, "YieldExpression");
      }, pp$3.parseAwait = function() {
        this.awaitPos || (this.awaitPos = this.start);
        var node = this.startNode();
        return this.next(), node.argument = this.parseMaybeUnary(null, !1), this.finishNode(node, "AwaitExpression");
      };
      var pp$4 = Parser2.prototype;
      pp$4.raise = function(pos, message) {
        var loc = getLineInfo(this.input, pos);
        message += " (" + loc.line + ":" + loc.column + ")";
        var err = new SyntaxError(message);
        throw err.pos = pos, err.loc = loc, err.raisedAt = this.pos, err;
      }, pp$4.raiseRecoverable = pp$4.raise, pp$4.curPosition = function() {
        if (this.options.locations)
          return new Position(this.curLine, this.pos - this.lineStart);
      };
      var pp$5 = Parser2.prototype, Scope = function(flags) {
        this.flags = flags, this.var = [], this.lexical = [], this.functions = [];
      };
      pp$5.enterScope = function(flags) {
        this.scopeStack.push(new Scope(flags));
      }, pp$5.exitScope = function() {
        this.scopeStack.pop();
      }, pp$5.treatFunctionsAsVarInScope = function(scope) {
        return scope.flags & SCOPE_FUNCTION || !this.inModule && scope.flags & SCOPE_TOP;
      }, pp$5.declareName = function(name, bindingType, pos) {
        var redeclared = !1;
        if (bindingType === BIND_LEXICAL) {
          var scope = this.currentScope();
          redeclared = scope.lexical.indexOf(name) > -1 || scope.functions.indexOf(name) > -1 || scope.var.indexOf(name) > -1, scope.lexical.push(name), this.inModule && scope.flags & SCOPE_TOP && delete this.undefinedExports[name];
        } else if (bindingType === BIND_SIMPLE_CATCH) {
          var scope$1 = this.currentScope();
          scope$1.lexical.push(name);
        } else if (bindingType === BIND_FUNCTION) {
          var scope$2 = this.currentScope();
          this.treatFunctionsAsVar ? redeclared = scope$2.lexical.indexOf(name) > -1 : redeclared = scope$2.lexical.indexOf(name) > -1 || scope$2.var.indexOf(name) > -1, scope$2.functions.push(name);
        } else
          for (var i = this.scopeStack.length - 1; i >= 0; --i) {
            var scope$3 = this.scopeStack[i];
            if (scope$3.lexical.indexOf(name) > -1 && !(scope$3.flags & SCOPE_SIMPLE_CATCH && scope$3.lexical[0] === name) || !this.treatFunctionsAsVarInScope(scope$3) && scope$3.functions.indexOf(name) > -1) {
              redeclared = !0;
              break;
            }
            if (scope$3.var.push(name), this.inModule && scope$3.flags & SCOPE_TOP && delete this.undefinedExports[name], scope$3.flags & SCOPE_VAR)
              break;
          }
        redeclared && this.raiseRecoverable(pos, "Identifier '" + name + "' has already been declared");
      }, pp$5.checkLocalExport = function(id) {
        this.scopeStack[0].lexical.indexOf(id.name) === -1 && this.scopeStack[0].var.indexOf(id.name) === -1 && (this.undefinedExports[id.name] = id);
      }, pp$5.currentScope = function() {
        return this.scopeStack[this.scopeStack.length - 1];
      }, pp$5.currentVarScope = function() {
        for (var i = this.scopeStack.length - 1; ; i--) {
          var scope = this.scopeStack[i];
          if (scope.flags & SCOPE_VAR)
            return scope;
        }
      }, pp$5.currentThisScope = function() {
        for (var i = this.scopeStack.length - 1; ; i--) {
          var scope = this.scopeStack[i];
          if (scope.flags & SCOPE_VAR && !(scope.flags & SCOPE_ARROW))
            return scope;
        }
      };
      var Node = function(parser, pos, loc) {
        this.type = "", this.start = pos, this.end = 0, parser.options.locations && (this.loc = new SourceLocation(parser, loc)), parser.options.directSourceFile && (this.sourceFile = parser.options.directSourceFile), parser.options.ranges && (this.range = [pos, 0]);
      }, pp$6 = Parser2.prototype;
      pp$6.startNode = function() {
        return new Node(this, this.start, this.startLoc);
      }, pp$6.startNodeAt = function(pos, loc) {
        return new Node(this, pos, loc);
      };
      function finishNodeAt(node, type, pos, loc) {
        return node.type = type, node.end = pos, this.options.locations && (node.loc.end = loc), this.options.ranges && (node.range[1] = pos), node;
      }
      pp$6.finishNode = function(node, type) {
        return finishNodeAt.call(this, node, type, this.lastTokEnd, this.lastTokEndLoc);
      }, pp$6.finishNodeAt = function(node, type, pos, loc) {
        return finishNodeAt.call(this, node, type, pos, loc);
      };
      var TokContext = function(token, isExpr, preserveSpace, override, generator) {
        this.token = token, this.isExpr = !!isExpr, this.preserveSpace = !!preserveSpace, this.override = override, this.generator = !!generator;
      }, types$1 = {
        b_stat: new TokContext("{", !1),
        b_expr: new TokContext("{", !0),
        b_tmpl: new TokContext("${", !1),
        p_stat: new TokContext("(", !1),
        p_expr: new TokContext("(", !0),
        q_tmpl: new TokContext("`", !0, !0, function(p) {
          return p.tryReadTemplateToken();
        }),
        f_stat: new TokContext("function", !1),
        f_expr: new TokContext("function", !0),
        f_expr_gen: new TokContext("function", !0, !1, null, !0),
        f_gen: new TokContext("function", !1, !1, null, !0)
      }, pp$7 = Parser2.prototype;
      pp$7.initialContext = function() {
        return [types$1.b_stat];
      }, pp$7.braceIsBlock = function(prevType) {
        var parent = this.curContext();
        return parent === types$1.f_expr || parent === types$1.f_stat ? !0 : prevType === types.colon && (parent === types$1.b_stat || parent === types$1.b_expr) ? !parent.isExpr : prevType === types._return || prevType === types.name && this.exprAllowed ? lineBreak.test(this.input.slice(this.lastTokEnd, this.start)) : prevType === types._else || prevType === types.semi || prevType === types.eof || prevType === types.parenR || prevType === types.arrow ? !0 : prevType === types.braceL ? parent === types$1.b_stat : prevType === types._var || prevType === types._const || prevType === types.name ? !1 : !this.exprAllowed;
      }, pp$7.inGeneratorContext = function() {
        for (var i = this.context.length - 1; i >= 1; i--) {
          var context = this.context[i];
          if (context.token === "function")
            return context.generator;
        }
        return !1;
      }, pp$7.updateContext = function(prevType) {
        var update, type = this.type;
        type.keyword && prevType === types.dot ? this.exprAllowed = !1 : (update = type.updateContext) ? update.call(this, prevType) : this.exprAllowed = type.beforeExpr;
      }, types.parenR.updateContext = types.braceR.updateContext = function() {
        if (this.context.length === 1) {
          this.exprAllowed = !0;
          return;
        }
        var out = this.context.pop();
        out === types$1.b_stat && this.curContext().token === "function" && (out = this.context.pop()), this.exprAllowed = !out.isExpr;
      }, types.braceL.updateContext = function(prevType) {
        this.context.push(this.braceIsBlock(prevType) ? types$1.b_stat : types$1.b_expr), this.exprAllowed = !0;
      }, types.dollarBraceL.updateContext = function() {
        this.context.push(types$1.b_tmpl), this.exprAllowed = !0;
      }, types.parenL.updateContext = function(prevType) {
        var statementParens = prevType === types._if || prevType === types._for || prevType === types._with || prevType === types._while;
        this.context.push(statementParens ? types$1.p_stat : types$1.p_expr), this.exprAllowed = !0;
      }, types.incDec.updateContext = function() {
      }, types._function.updateContext = types._class.updateContext = function(prevType) {
        prevType.beforeExpr && prevType !== types.semi && prevType !== types._else && !(prevType === types._return && lineBreak.test(this.input.slice(this.lastTokEnd, this.start))) && !((prevType === types.colon || prevType === types.braceL) && this.curContext() === types$1.b_stat) ? this.context.push(types$1.f_expr) : this.context.push(types$1.f_stat), this.exprAllowed = !1;
      }, types.backQuote.updateContext = function() {
        this.curContext() === types$1.q_tmpl ? this.context.pop() : this.context.push(types$1.q_tmpl), this.exprAllowed = !1;
      }, types.star.updateContext = function(prevType) {
        if (prevType === types._function) {
          var index = this.context.length - 1;
          this.context[index] === types$1.f_expr ? this.context[index] = types$1.f_expr_gen : this.context[index] = types$1.f_gen;
        }
        this.exprAllowed = !0;
      }, types.name.updateContext = function(prevType) {
        var allowed = !1;
        this.options.ecmaVersion >= 6 && prevType !== types.dot && (this.value === "of" && !this.exprAllowed || this.value === "yield" && this.inGeneratorContext()) && (allowed = !0), this.exprAllowed = allowed;
      };
      var ecma9BinaryProperties = "ASCII ASCII_Hex_Digit AHex Alphabetic Alpha Any Assigned Bidi_Control Bidi_C Bidi_Mirrored Bidi_M Case_Ignorable CI Cased Changes_When_Casefolded CWCF Changes_When_Casemapped CWCM Changes_When_Lowercased CWL Changes_When_NFKC_Casefolded CWKCF Changes_When_Titlecased CWT Changes_When_Uppercased CWU Dash Default_Ignorable_Code_Point DI Deprecated Dep Diacritic Dia Emoji Emoji_Component Emoji_Modifier Emoji_Modifier_Base Emoji_Presentation Extender Ext Grapheme_Base Gr_Base Grapheme_Extend Gr_Ext Hex_Digit Hex IDS_Binary_Operator IDSB IDS_Trinary_Operator IDST ID_Continue IDC ID_Start IDS Ideographic Ideo Join_Control Join_C Logical_Order_Exception LOE Lowercase Lower Math Noncharacter_Code_Point NChar Pattern_Syntax Pat_Syn Pattern_White_Space Pat_WS Quotation_Mark QMark Radical Regional_Indicator RI Sentence_Terminal STerm Soft_Dotted SD Terminal_Punctuation Term Unified_Ideograph UIdeo Uppercase Upper Variation_Selector VS White_Space space XID_Continue XIDC XID_Start XIDS", ecma10BinaryProperties = ecma9BinaryProperties + " Extended_Pictographic", ecma11BinaryProperties = ecma10BinaryProperties, unicodeBinaryProperties = {
        9: ecma9BinaryProperties,
        10: ecma10BinaryProperties,
        11: ecma11BinaryProperties
      }, unicodeGeneralCategoryValues = "Cased_Letter LC Close_Punctuation Pe Connector_Punctuation Pc Control Cc cntrl Currency_Symbol Sc Dash_Punctuation Pd Decimal_Number Nd digit Enclosing_Mark Me Final_Punctuation Pf Format Cf Initial_Punctuation Pi Letter L Letter_Number Nl Line_Separator Zl Lowercase_Letter Ll Mark M Combining_Mark Math_Symbol Sm Modifier_Letter Lm Modifier_Symbol Sk Nonspacing_Mark Mn Number N Open_Punctuation Ps Other C Other_Letter Lo Other_Number No Other_Punctuation Po Other_Symbol So Paragraph_Separator Zp Private_Use Co Punctuation P punct Separator Z Space_Separator Zs Spacing_Mark Mc Surrogate Cs Symbol S Titlecase_Letter Lt Unassigned Cn Uppercase_Letter Lu", ecma9ScriptValues = "Adlam Adlm Ahom Ahom Anatolian_Hieroglyphs Hluw Arabic Arab Armenian Armn Avestan Avst Balinese Bali Bamum Bamu Bassa_Vah Bass Batak Batk Bengali Beng Bhaiksuki Bhks Bopomofo Bopo Brahmi Brah Braille Brai Buginese Bugi Buhid Buhd Canadian_Aboriginal Cans Carian Cari Caucasian_Albanian Aghb Chakma Cakm Cham Cham Cherokee Cher Common Zyyy Coptic Copt Qaac Cuneiform Xsux Cypriot Cprt Cyrillic Cyrl Deseret Dsrt Devanagari Deva Duployan Dupl Egyptian_Hieroglyphs Egyp Elbasan Elba Ethiopic Ethi Georgian Geor Glagolitic Glag Gothic Goth Grantha Gran Greek Grek Gujarati Gujr Gurmukhi Guru Han Hani Hangul Hang Hanunoo Hano Hatran Hatr Hebrew Hebr Hiragana Hira Imperial_Aramaic Armi Inherited Zinh Qaai Inscriptional_Pahlavi Phli Inscriptional_Parthian Prti Javanese Java Kaithi Kthi Kannada Knda Katakana Kana Kayah_Li Kali Kharoshthi Khar Khmer Khmr Khojki Khoj Khudawadi Sind Lao Laoo Latin Latn Lepcha Lepc Limbu Limb Linear_A Lina Linear_B Linb Lisu Lisu Lycian Lyci Lydian Lydi Mahajani Mahj Malayalam Mlym Mandaic Mand Manichaean Mani Marchen Marc Masaram_Gondi Gonm Meetei_Mayek Mtei Mende_Kikakui Mend Meroitic_Cursive Merc Meroitic_Hieroglyphs Mero Miao Plrd Modi Modi Mongolian Mong Mro Mroo Multani Mult Myanmar Mymr Nabataean Nbat New_Tai_Lue Talu Newa Newa Nko Nkoo Nushu Nshu Ogham Ogam Ol_Chiki Olck Old_Hungarian Hung Old_Italic Ital Old_North_Arabian Narb Old_Permic Perm Old_Persian Xpeo Old_South_Arabian Sarb Old_Turkic Orkh Oriya Orya Osage Osge Osmanya Osma Pahawh_Hmong Hmng Palmyrene Palm Pau_Cin_Hau Pauc Phags_Pa Phag Phoenician Phnx Psalter_Pahlavi Phlp Rejang Rjng Runic Runr Samaritan Samr Saurashtra Saur Sharada Shrd Shavian Shaw Siddham Sidd SignWriting Sgnw Sinhala Sinh Sora_Sompeng Sora Soyombo Soyo Sundanese Sund Syloti_Nagri Sylo Syriac Syrc Tagalog Tglg Tagbanwa Tagb Tai_Le Tale Tai_Tham Lana Tai_Viet Tavt Takri Takr Tamil Taml Tangut Tang Telugu Telu Thaana Thaa Thai Thai Tibetan Tibt Tifinagh Tfng Tirhuta Tirh Ugaritic Ugar Vai Vaii Warang_Citi Wara Yi Yiii Zanabazar_Square Zanb", ecma10ScriptValues = ecma9ScriptValues + " Dogra Dogr Gunjala_Gondi Gong Hanifi_Rohingya Rohg Makasar Maka Medefaidrin Medf Old_Sogdian Sogo Sogdian Sogd", ecma11ScriptValues = ecma10ScriptValues + " Elymaic Elym Nandinagari Nand Nyiakeng_Puachue_Hmong Hmnp Wancho Wcho", unicodeScriptValues = {
        9: ecma9ScriptValues,
        10: ecma10ScriptValues,
        11: ecma11ScriptValues
      }, data = {};
      function buildUnicodeData(ecmaVersion) {
        var d = data[ecmaVersion] = {
          binary: wordsRegexp(unicodeBinaryProperties[ecmaVersion] + " " + unicodeGeneralCategoryValues),
          nonBinary: {
            General_Category: wordsRegexp(unicodeGeneralCategoryValues),
            Script: wordsRegexp(unicodeScriptValues[ecmaVersion])
          }
        };
        d.nonBinary.Script_Extensions = d.nonBinary.Script, d.nonBinary.gc = d.nonBinary.General_Category, d.nonBinary.sc = d.nonBinary.Script, d.nonBinary.scx = d.nonBinary.Script_Extensions;
      }
      buildUnicodeData(9), buildUnicodeData(10), buildUnicodeData(11);
      var pp$8 = Parser2.prototype, RegExpValidationState = function(parser) {
        this.parser = parser, this.validFlags = "gim" + (parser.options.ecmaVersion >= 6 ? "uy" : "") + (parser.options.ecmaVersion >= 9 ? "s" : ""), this.unicodeProperties = data[parser.options.ecmaVersion >= 11 ? 11 : parser.options.ecmaVersion], this.source = "", this.flags = "", this.start = 0, this.switchU = !1, this.switchN = !1, this.pos = 0, this.lastIntValue = 0, this.lastStringValue = "", this.lastAssertionIsQuantifiable = !1, this.numCapturingParens = 0, this.maxBackReference = 0, this.groupNames = [], this.backReferenceNames = [];
      };
      RegExpValidationState.prototype.reset = function(start, pattern, flags) {
        var unicode = flags.indexOf("u") !== -1;
        this.start = start | 0, this.source = pattern + "", this.flags = flags, this.switchU = unicode && this.parser.options.ecmaVersion >= 6, this.switchN = unicode && this.parser.options.ecmaVersion >= 9;
      }, RegExpValidationState.prototype.raise = function(message) {
        this.parser.raiseRecoverable(this.start, "Invalid regular expression: /" + this.source + "/: " + message);
      }, RegExpValidationState.prototype.at = function(i, forceU) {
        forceU === void 0 && (forceU = !1);
        var s = this.source, l = s.length;
        if (i >= l)
          return -1;
        var c = s.charCodeAt(i);
        if (!(forceU || this.switchU) || c <= 55295 || c >= 57344 || i + 1 >= l)
          return c;
        var next = s.charCodeAt(i + 1);
        return next >= 56320 && next <= 57343 ? (c << 10) + next - 56613888 : c;
      }, RegExpValidationState.prototype.nextIndex = function(i, forceU) {
        forceU === void 0 && (forceU = !1);
        var s = this.source, l = s.length;
        if (i >= l)
          return l;
        var c = s.charCodeAt(i), next;
        return !(forceU || this.switchU) || c <= 55295 || c >= 57344 || i + 1 >= l || (next = s.charCodeAt(i + 1)) < 56320 || next > 57343 ? i + 1 : i + 2;
      }, RegExpValidationState.prototype.current = function(forceU) {
        return forceU === void 0 && (forceU = !1), this.at(this.pos, forceU);
      }, RegExpValidationState.prototype.lookahead = function(forceU) {
        return forceU === void 0 && (forceU = !1), this.at(this.nextIndex(this.pos, forceU), forceU);
      }, RegExpValidationState.prototype.advance = function(forceU) {
        forceU === void 0 && (forceU = !1), this.pos = this.nextIndex(this.pos, forceU);
      }, RegExpValidationState.prototype.eat = function(ch, forceU) {
        return forceU === void 0 && (forceU = !1), this.current(forceU) === ch ? (this.advance(forceU), !0) : !1;
      };
      function codePointToString(ch) {
        return ch <= 65535 ? String.fromCharCode(ch) : (ch -= 65536, String.fromCharCode((ch >> 10) + 55296, (ch & 1023) + 56320));
      }
      pp$8.validateRegExpFlags = function(state) {
        for (var validFlags = state.validFlags, flags = state.flags, i = 0; i < flags.length; i++) {
          var flag = flags.charAt(i);
          validFlags.indexOf(flag) === -1 && this.raise(state.start, "Invalid regular expression flag"), flags.indexOf(flag, i + 1) > -1 && this.raise(state.start, "Duplicate regular expression flag");
        }
      }, pp$8.validateRegExpPattern = function(state) {
        this.regexp_pattern(state), !state.switchN && this.options.ecmaVersion >= 9 && state.groupNames.length > 0 && (state.switchN = !0, this.regexp_pattern(state));
      }, pp$8.regexp_pattern = function(state) {
        state.pos = 0, state.lastIntValue = 0, state.lastStringValue = "", state.lastAssertionIsQuantifiable = !1, state.numCapturingParens = 0, state.maxBackReference = 0, state.groupNames.length = 0, state.backReferenceNames.length = 0, this.regexp_disjunction(state), state.pos !== state.source.length && (state.eat(
          41
          /* ) */
        ) && state.raise("Unmatched ')'"), (state.eat(
          93
          /* ] */
        ) || state.eat(
          125
          /* } */
        )) && state.raise("Lone quantifier brackets")), state.maxBackReference > state.numCapturingParens && state.raise("Invalid escape");
        for (var i = 0, list = state.backReferenceNames; i < list.length; i += 1) {
          var name = list[i];
          state.groupNames.indexOf(name) === -1 && state.raise("Invalid named capture referenced");
        }
      }, pp$8.regexp_disjunction = function(state) {
        for (this.regexp_alternative(state); state.eat(
          124
          /* | */
        ); )
          this.regexp_alternative(state);
        this.regexp_eatQuantifier(state, !0) && state.raise("Nothing to repeat"), state.eat(
          123
          /* { */
        ) && state.raise("Lone quantifier brackets");
      }, pp$8.regexp_alternative = function(state) {
        for (; state.pos < state.source.length && this.regexp_eatTerm(state); )
          ;
      }, pp$8.regexp_eatTerm = function(state) {
        return this.regexp_eatAssertion(state) ? (state.lastAssertionIsQuantifiable && this.regexp_eatQuantifier(state) && state.switchU && state.raise("Invalid quantifier"), !0) : (state.switchU ? this.regexp_eatAtom(state) : this.regexp_eatExtendedAtom(state)) ? (this.regexp_eatQuantifier(state), !0) : !1;
      }, pp$8.regexp_eatAssertion = function(state) {
        var start = state.pos;
        if (state.lastAssertionIsQuantifiable = !1, state.eat(
          94
          /* ^ */
        ) || state.eat(
          36
          /* $ */
        ))
          return !0;
        if (state.eat(
          92
          /* \ */
        )) {
          if (state.eat(
            66
            /* B */
          ) || state.eat(
            98
            /* b */
          ))
            return !0;
          state.pos = start;
        }
        if (state.eat(
          40
          /* ( */
        ) && state.eat(
          63
          /* ? */
        )) {
          var lookbehind = !1;
          if (this.options.ecmaVersion >= 9 && (lookbehind = state.eat(
            60
            /* < */
          )), state.eat(
            61
            /* = */
          ) || state.eat(
            33
            /* ! */
          ))
            return this.regexp_disjunction(state), state.eat(
              41
              /* ) */
            ) || state.raise("Unterminated group"), state.lastAssertionIsQuantifiable = !lookbehind, !0;
        }
        return state.pos = start, !1;
      }, pp$8.regexp_eatQuantifier = function(state, noError) {
        return noError === void 0 && (noError = !1), this.regexp_eatQuantifierPrefix(state, noError) ? (state.eat(
          63
          /* ? */
        ), !0) : !1;
      }, pp$8.regexp_eatQuantifierPrefix = function(state, noError) {
        return state.eat(
          42
          /* * */
        ) || state.eat(
          43
          /* + */
        ) || state.eat(
          63
          /* ? */
        ) || this.regexp_eatBracedQuantifier(state, noError);
      }, pp$8.regexp_eatBracedQuantifier = function(state, noError) {
        var start = state.pos;
        if (state.eat(
          123
          /* { */
        )) {
          var min = 0, max = -1;
          if (this.regexp_eatDecimalDigits(state) && (min = state.lastIntValue, state.eat(
            44
            /* , */
          ) && this.regexp_eatDecimalDigits(state) && (max = state.lastIntValue), state.eat(
            125
            /* } */
          )))
            return max !== -1 && max < min && !noError && state.raise("numbers out of order in {} quantifier"), !0;
          state.switchU && !noError && state.raise("Incomplete quantifier"), state.pos = start;
        }
        return !1;
      }, pp$8.regexp_eatAtom = function(state) {
        return this.regexp_eatPatternCharacters(state) || state.eat(
          46
          /* . */
        ) || this.regexp_eatReverseSolidusAtomEscape(state) || this.regexp_eatCharacterClass(state) || this.regexp_eatUncapturingGroup(state) || this.regexp_eatCapturingGroup(state);
      }, pp$8.regexp_eatReverseSolidusAtomEscape = function(state) {
        var start = state.pos;
        if (state.eat(
          92
          /* \ */
        )) {
          if (this.regexp_eatAtomEscape(state))
            return !0;
          state.pos = start;
        }
        return !1;
      }, pp$8.regexp_eatUncapturingGroup = function(state) {
        var start = state.pos;
        if (state.eat(
          40
          /* ( */
        )) {
          if (state.eat(
            63
            /* ? */
          ) && state.eat(
            58
            /* : */
          )) {
            if (this.regexp_disjunction(state), state.eat(
              41
              /* ) */
            ))
              return !0;
            state.raise("Unterminated group");
          }
          state.pos = start;
        }
        return !1;
      }, pp$8.regexp_eatCapturingGroup = function(state) {
        if (state.eat(
          40
          /* ( */
        )) {
          if (this.options.ecmaVersion >= 9 ? this.regexp_groupSpecifier(state) : state.current() === 63 && state.raise("Invalid group"), this.regexp_disjunction(state), state.eat(
            41
            /* ) */
          ))
            return state.numCapturingParens += 1, !0;
          state.raise("Unterminated group");
        }
        return !1;
      }, pp$8.regexp_eatExtendedAtom = function(state) {
        return state.eat(
          46
          /* . */
        ) || this.regexp_eatReverseSolidusAtomEscape(state) || this.regexp_eatCharacterClass(state) || this.regexp_eatUncapturingGroup(state) || this.regexp_eatCapturingGroup(state) || this.regexp_eatInvalidBracedQuantifier(state) || this.regexp_eatExtendedPatternCharacter(state);
      }, pp$8.regexp_eatInvalidBracedQuantifier = function(state) {
        return this.regexp_eatBracedQuantifier(state, !0) && state.raise("Nothing to repeat"), !1;
      }, pp$8.regexp_eatSyntaxCharacter = function(state) {
        var ch = state.current();
        return isSyntaxCharacter(ch) ? (state.lastIntValue = ch, state.advance(), !0) : !1;
      };
      function isSyntaxCharacter(ch) {
        return ch === 36 || ch >= 40 && ch <= 43 || ch === 46 || ch === 63 || ch >= 91 && ch <= 94 || ch >= 123 && ch <= 125;
      }
      pp$8.regexp_eatPatternCharacters = function(state) {
        for (var start = state.pos, ch = 0; (ch = state.current()) !== -1 && !isSyntaxCharacter(ch); )
          state.advance();
        return state.pos !== start;
      }, pp$8.regexp_eatExtendedPatternCharacter = function(state) {
        var ch = state.current();
        return ch !== -1 && ch !== 36 && !(ch >= 40 && ch <= 43) && ch !== 46 && ch !== 63 && ch !== 91 && ch !== 94 && ch !== 124 ? (state.advance(), !0) : !1;
      }, pp$8.regexp_groupSpecifier = function(state) {
        if (state.eat(
          63
          /* ? */
        )) {
          if (this.regexp_eatGroupName(state)) {
            state.groupNames.indexOf(state.lastStringValue) !== -1 && state.raise("Duplicate capture group name"), state.groupNames.push(state.lastStringValue);
            return;
          }
          state.raise("Invalid group");
        }
      }, pp$8.regexp_eatGroupName = function(state) {
        if (state.lastStringValue = "", state.eat(
          60
          /* < */
        )) {
          if (this.regexp_eatRegExpIdentifierName(state) && state.eat(
            62
            /* > */
          ))
            return !0;
          state.raise("Invalid capture group name");
        }
        return !1;
      }, pp$8.regexp_eatRegExpIdentifierName = function(state) {
        if (state.lastStringValue = "", this.regexp_eatRegExpIdentifierStart(state)) {
          for (state.lastStringValue += codePointToString(state.lastIntValue); this.regexp_eatRegExpIdentifierPart(state); )
            state.lastStringValue += codePointToString(state.lastIntValue);
          return !0;
        }
        return !1;
      }, pp$8.regexp_eatRegExpIdentifierStart = function(state) {
        var start = state.pos, forceU = this.options.ecmaVersion >= 11, ch = state.current(forceU);
        return state.advance(forceU), ch === 92 && this.regexp_eatRegExpUnicodeEscapeSequence(state, forceU) && (ch = state.lastIntValue), isRegExpIdentifierStart(ch) ? (state.lastIntValue = ch, !0) : (state.pos = start, !1);
      };
      function isRegExpIdentifierStart(ch) {
        return isIdentifierStart(ch, !0) || ch === 36 || ch === 95;
      }
      pp$8.regexp_eatRegExpIdentifierPart = function(state) {
        var start = state.pos, forceU = this.options.ecmaVersion >= 11, ch = state.current(forceU);
        return state.advance(forceU), ch === 92 && this.regexp_eatRegExpUnicodeEscapeSequence(state, forceU) && (ch = state.lastIntValue), isRegExpIdentifierPart(ch) ? (state.lastIntValue = ch, !0) : (state.pos = start, !1);
      };
      function isRegExpIdentifierPart(ch) {
        return isIdentifierChar(ch, !0) || ch === 36 || ch === 95 || ch === 8204 || ch === 8205;
      }
      pp$8.regexp_eatAtomEscape = function(state) {
        return this.regexp_eatBackReference(state) || this.regexp_eatCharacterClassEscape(state) || this.regexp_eatCharacterEscape(state) || state.switchN && this.regexp_eatKGroupName(state) ? !0 : (state.switchU && (state.current() === 99 && state.raise("Invalid unicode escape"), state.raise("Invalid escape")), !1);
      }, pp$8.regexp_eatBackReference = function(state) {
        var start = state.pos;
        if (this.regexp_eatDecimalEscape(state)) {
          var n = state.lastIntValue;
          if (state.switchU)
            return n > state.maxBackReference && (state.maxBackReference = n), !0;
          if (n <= state.numCapturingParens)
            return !0;
          state.pos = start;
        }
        return !1;
      }, pp$8.regexp_eatKGroupName = function(state) {
        if (state.eat(
          107
          /* k */
        )) {
          if (this.regexp_eatGroupName(state))
            return state.backReferenceNames.push(state.lastStringValue), !0;
          state.raise("Invalid named reference");
        }
        return !1;
      }, pp$8.regexp_eatCharacterEscape = function(state) {
        return this.regexp_eatControlEscape(state) || this.regexp_eatCControlLetter(state) || this.regexp_eatZero(state) || this.regexp_eatHexEscapeSequence(state) || this.regexp_eatRegExpUnicodeEscapeSequence(state, !1) || !state.switchU && this.regexp_eatLegacyOctalEscapeSequence(state) || this.regexp_eatIdentityEscape(state);
      }, pp$8.regexp_eatCControlLetter = function(state) {
        var start = state.pos;
        if (state.eat(
          99
          /* c */
        )) {
          if (this.regexp_eatControlLetter(state))
            return !0;
          state.pos = start;
        }
        return !1;
      }, pp$8.regexp_eatZero = function(state) {
        return state.current() === 48 && !isDecimalDigit(state.lookahead()) ? (state.lastIntValue = 0, state.advance(), !0) : !1;
      }, pp$8.regexp_eatControlEscape = function(state) {
        var ch = state.current();
        return ch === 116 ? (state.lastIntValue = 9, state.advance(), !0) : ch === 110 ? (state.lastIntValue = 10, state.advance(), !0) : ch === 118 ? (state.lastIntValue = 11, state.advance(), !0) : ch === 102 ? (state.lastIntValue = 12, state.advance(), !0) : ch === 114 ? (state.lastIntValue = 13, state.advance(), !0) : !1;
      }, pp$8.regexp_eatControlLetter = function(state) {
        var ch = state.current();
        return isControlLetter(ch) ? (state.lastIntValue = ch % 32, state.advance(), !0) : !1;
      };
      function isControlLetter(ch) {
        return ch >= 65 && ch <= 90 || ch >= 97 && ch <= 122;
      }
      pp$8.regexp_eatRegExpUnicodeEscapeSequence = function(state, forceU) {
        forceU === void 0 && (forceU = !1);
        var start = state.pos, switchU = forceU || state.switchU;
        if (state.eat(
          117
          /* u */
        )) {
          if (this.regexp_eatFixedHexDigits(state, 4)) {
            var lead = state.lastIntValue;
            if (switchU && lead >= 55296 && lead <= 56319) {
              var leadSurrogateEnd = state.pos;
              if (state.eat(
                92
                /* \ */
              ) && state.eat(
                117
                /* u */
              ) && this.regexp_eatFixedHexDigits(state, 4)) {
                var trail = state.lastIntValue;
                if (trail >= 56320 && trail <= 57343)
                  return state.lastIntValue = (lead - 55296) * 1024 + (trail - 56320) + 65536, !0;
              }
              state.pos = leadSurrogateEnd, state.lastIntValue = lead;
            }
            return !0;
          }
          if (switchU && state.eat(
            123
            /* { */
          ) && this.regexp_eatHexDigits(state) && state.eat(
            125
            /* } */
          ) && isValidUnicode(state.lastIntValue))
            return !0;
          switchU && state.raise("Invalid unicode escape"), state.pos = start;
        }
        return !1;
      };
      function isValidUnicode(ch) {
        return ch >= 0 && ch <= 1114111;
      }
      pp$8.regexp_eatIdentityEscape = function(state) {
        if (state.switchU)
          return this.regexp_eatSyntaxCharacter(state) ? !0 : state.eat(
            47
            /* / */
          ) ? (state.lastIntValue = 47, !0) : !1;
        var ch = state.current();
        return ch !== 99 && (!state.switchN || ch !== 107) ? (state.lastIntValue = ch, state.advance(), !0) : !1;
      }, pp$8.regexp_eatDecimalEscape = function(state) {
        state.lastIntValue = 0;
        var ch = state.current();
        if (ch >= 49 && ch <= 57) {
          do
            state.lastIntValue = 10 * state.lastIntValue + (ch - 48), state.advance();
          while ((ch = state.current()) >= 48 && ch <= 57);
          return !0;
        }
        return !1;
      }, pp$8.regexp_eatCharacterClassEscape = function(state) {
        var ch = state.current();
        if (isCharacterClassEscape(ch))
          return state.lastIntValue = -1, state.advance(), !0;
        if (state.switchU && this.options.ecmaVersion >= 9 && (ch === 80 || ch === 112)) {
          if (state.lastIntValue = -1, state.advance(), state.eat(
            123
            /* { */
          ) && this.regexp_eatUnicodePropertyValueExpression(state) && state.eat(
            125
            /* } */
          ))
            return !0;
          state.raise("Invalid property name");
        }
        return !1;
      };
      function isCharacterClassEscape(ch) {
        return ch === 100 || ch === 68 || ch === 115 || ch === 83 || ch === 119 || ch === 87;
      }
      pp$8.regexp_eatUnicodePropertyValueExpression = function(state) {
        var start = state.pos;
        if (this.regexp_eatUnicodePropertyName(state) && state.eat(
          61
          /* = */
        )) {
          var name = state.lastStringValue;
          if (this.regexp_eatUnicodePropertyValue(state)) {
            var value = state.lastStringValue;
            return this.regexp_validateUnicodePropertyNameAndValue(state, name, value), !0;
          }
        }
        if (state.pos = start, this.regexp_eatLoneUnicodePropertyNameOrValue(state)) {
          var nameOrValue = state.lastStringValue;
          return this.regexp_validateUnicodePropertyNameOrValue(state, nameOrValue), !0;
        }
        return !1;
      }, pp$8.regexp_validateUnicodePropertyNameAndValue = function(state, name, value) {
        has(state.unicodeProperties.nonBinary, name) || state.raise("Invalid property name"), state.unicodeProperties.nonBinary[name].test(value) || state.raise("Invalid property value");
      }, pp$8.regexp_validateUnicodePropertyNameOrValue = function(state, nameOrValue) {
        state.unicodeProperties.binary.test(nameOrValue) || state.raise("Invalid property name");
      }, pp$8.regexp_eatUnicodePropertyName = function(state) {
        var ch = 0;
        for (state.lastStringValue = ""; isUnicodePropertyNameCharacter(ch = state.current()); )
          state.lastStringValue += codePointToString(ch), state.advance();
        return state.lastStringValue !== "";
      };
      function isUnicodePropertyNameCharacter(ch) {
        return isControlLetter(ch) || ch === 95;
      }
      pp$8.regexp_eatUnicodePropertyValue = function(state) {
        var ch = 0;
        for (state.lastStringValue = ""; isUnicodePropertyValueCharacter(ch = state.current()); )
          state.lastStringValue += codePointToString(ch), state.advance();
        return state.lastStringValue !== "";
      };
      function isUnicodePropertyValueCharacter(ch) {
        return isUnicodePropertyNameCharacter(ch) || isDecimalDigit(ch);
      }
      pp$8.regexp_eatLoneUnicodePropertyNameOrValue = function(state) {
        return this.regexp_eatUnicodePropertyValue(state);
      }, pp$8.regexp_eatCharacterClass = function(state) {
        if (state.eat(
          91
          /* [ */
        )) {
          if (state.eat(
            94
            /* ^ */
          ), this.regexp_classRanges(state), state.eat(
            93
            /* ] */
          ))
            return !0;
          state.raise("Unterminated character class");
        }
        return !1;
      }, pp$8.regexp_classRanges = function(state) {
        for (; this.regexp_eatClassAtom(state); ) {
          var left = state.lastIntValue;
          if (state.eat(
            45
            /* - */
          ) && this.regexp_eatClassAtom(state)) {
            var right = state.lastIntValue;
            state.switchU && (left === -1 || right === -1) && state.raise("Invalid character class"), left !== -1 && right !== -1 && left > right && state.raise("Range out of order in character class");
          }
        }
      }, pp$8.regexp_eatClassAtom = function(state) {
        var start = state.pos;
        if (state.eat(
          92
          /* \ */
        )) {
          if (this.regexp_eatClassEscape(state))
            return !0;
          if (state.switchU) {
            var ch$1 = state.current();
            (ch$1 === 99 || isOctalDigit(ch$1)) && state.raise("Invalid class escape"), state.raise("Invalid escape");
          }
          state.pos = start;
        }
        var ch = state.current();
        return ch !== 93 ? (state.lastIntValue = ch, state.advance(), !0) : !1;
      }, pp$8.regexp_eatClassEscape = function(state) {
        var start = state.pos;
        if (state.eat(
          98
          /* b */
        ))
          return state.lastIntValue = 8, !0;
        if (state.switchU && state.eat(
          45
          /* - */
        ))
          return state.lastIntValue = 45, !0;
        if (!state.switchU && state.eat(
          99
          /* c */
        )) {
          if (this.regexp_eatClassControlLetter(state))
            return !0;
          state.pos = start;
        }
        return this.regexp_eatCharacterClassEscape(state) || this.regexp_eatCharacterEscape(state);
      }, pp$8.regexp_eatClassControlLetter = function(state) {
        var ch = state.current();
        return isDecimalDigit(ch) || ch === 95 ? (state.lastIntValue = ch % 32, state.advance(), !0) : !1;
      }, pp$8.regexp_eatHexEscapeSequence = function(state) {
        var start = state.pos;
        if (state.eat(
          120
          /* x */
        )) {
          if (this.regexp_eatFixedHexDigits(state, 2))
            return !0;
          state.switchU && state.raise("Invalid escape"), state.pos = start;
        }
        return !1;
      }, pp$8.regexp_eatDecimalDigits = function(state) {
        var start = state.pos, ch = 0;
        for (state.lastIntValue = 0; isDecimalDigit(ch = state.current()); )
          state.lastIntValue = 10 * state.lastIntValue + (ch - 48), state.advance();
        return state.pos !== start;
      };
      function isDecimalDigit(ch) {
        return ch >= 48 && ch <= 57;
      }
      pp$8.regexp_eatHexDigits = function(state) {
        var start = state.pos, ch = 0;
        for (state.lastIntValue = 0; isHexDigit(ch = state.current()); )
          state.lastIntValue = 16 * state.lastIntValue + hexToInt(ch), state.advance();
        return state.pos !== start;
      };
      function isHexDigit(ch) {
        return ch >= 48 && ch <= 57 || ch >= 65 && ch <= 70 || ch >= 97 && ch <= 102;
      }
      function hexToInt(ch) {
        return ch >= 65 && ch <= 70 ? 10 + (ch - 65) : ch >= 97 && ch <= 102 ? 10 + (ch - 97) : ch - 48;
      }
      pp$8.regexp_eatLegacyOctalEscapeSequence = function(state) {
        if (this.regexp_eatOctalDigit(state)) {
          var n1 = state.lastIntValue;
          if (this.regexp_eatOctalDigit(state)) {
            var n2 = state.lastIntValue;
            n1 <= 3 && this.regexp_eatOctalDigit(state) ? state.lastIntValue = n1 * 64 + n2 * 8 + state.lastIntValue : state.lastIntValue = n1 * 8 + n2;
          } else
            state.lastIntValue = n1;
          return !0;
        }
        return !1;
      }, pp$8.regexp_eatOctalDigit = function(state) {
        var ch = state.current();
        return isOctalDigit(ch) ? (state.lastIntValue = ch - 48, state.advance(), !0) : (state.lastIntValue = 0, !1);
      };
      function isOctalDigit(ch) {
        return ch >= 48 && ch <= 55;
      }
      pp$8.regexp_eatFixedHexDigits = function(state, length) {
        var start = state.pos;
        state.lastIntValue = 0;
        for (var i = 0; i < length; ++i) {
          var ch = state.current();
          if (!isHexDigit(ch))
            return state.pos = start, !1;
          state.lastIntValue = 16 * state.lastIntValue + hexToInt(ch), state.advance();
        }
        return !0;
      };
      var Token = function(p) {
        this.type = p.type, this.value = p.value, this.start = p.start, this.end = p.end, p.options.locations && (this.loc = new SourceLocation(p, p.startLoc, p.endLoc)), p.options.ranges && (this.range = [p.start, p.end]);
      }, pp$9 = Parser2.prototype;
      pp$9.next = function(ignoreEscapeSequenceInKeyword) {
        !ignoreEscapeSequenceInKeyword && this.type.keyword && this.containsEsc && this.raiseRecoverable(this.start, "Escape sequence in keyword " + this.type.keyword), this.options.onToken && this.options.onToken(new Token(this)), this.lastTokEnd = this.end, this.lastTokStart = this.start, this.lastTokEndLoc = this.endLoc, this.lastTokStartLoc = this.startLoc, this.nextToken();
      }, pp$9.getToken = function() {
        return this.next(), new Token(this);
      }, typeof Symbol < "u" && (pp$9[Symbol.iterator] = function() {
        var this$1 = this;
        return {
          next: function() {
            var token = this$1.getToken();
            return {
              done: token.type === types.eof,
              value: token
            };
          }
        };
      }), pp$9.curContext = function() {
        return this.context[this.context.length - 1];
      }, pp$9.nextToken = function() {
        var curContext = this.curContext();
        if ((!curContext || !curContext.preserveSpace) && this.skipSpace(), this.start = this.pos, this.options.locations && (this.startLoc = this.curPosition()), this.pos >= this.input.length)
          return this.finishToken(types.eof);
        if (curContext.override)
          return curContext.override(this);
        this.readToken(this.fullCharCodeAtPos());
      }, pp$9.readToken = function(code) {
        return isIdentifierStart(code, this.options.ecmaVersion >= 6) || code === 92 ? this.readWord() : this.getTokenFromCode(code);
      }, pp$9.fullCharCodeAtPos = function() {
        var code = this.input.charCodeAt(this.pos);
        if (code <= 55295 || code >= 57344)
          return code;
        var next = this.input.charCodeAt(this.pos + 1);
        return (code << 10) + next - 56613888;
      }, pp$9.skipBlockComment = function() {
        var startLoc = this.options.onComment && this.curPosition(), start = this.pos, end = this.input.indexOf("*/", this.pos += 2);
        if (end === -1 && this.raise(this.pos - 2, "Unterminated comment"), this.pos = end + 2, this.options.locations) {
          lineBreakG.lastIndex = start;
          for (var match; (match = lineBreakG.exec(this.input)) && match.index < this.pos; )
            ++this.curLine, this.lineStart = match.index + match[0].length;
        }
        this.options.onComment && this.options.onComment(
          !0,
          this.input.slice(start + 2, end),
          start,
          this.pos,
          startLoc,
          this.curPosition()
        );
      }, pp$9.skipLineComment = function(startSkip) {
        for (var start = this.pos, startLoc = this.options.onComment && this.curPosition(), ch = this.input.charCodeAt(this.pos += startSkip); this.pos < this.input.length && !isNewLine(ch); )
          ch = this.input.charCodeAt(++this.pos);
        this.options.onComment && this.options.onComment(
          !1,
          this.input.slice(start + startSkip, this.pos),
          start,
          this.pos,
          startLoc,
          this.curPosition()
        );
      }, pp$9.skipSpace = function() {
        loop: for (; this.pos < this.input.length; ) {
          var ch = this.input.charCodeAt(this.pos);
          switch (ch) {
            case 32:
            case 160:
              ++this.pos;
              break;
            case 13:
              this.input.charCodeAt(this.pos + 1) === 10 && ++this.pos;
            case 10:
            case 8232:
            case 8233:
              ++this.pos, this.options.locations && (++this.curLine, this.lineStart = this.pos);
              break;
            case 47:
              switch (this.input.charCodeAt(this.pos + 1)) {
                case 42:
                  this.skipBlockComment();
                  break;
                case 47:
                  this.skipLineComment(2);
                  break;
                default:
                  break loop;
              }
              break;
            default:
              if (ch > 8 && ch < 14 || ch >= 5760 && nonASCIIwhitespace.test(String.fromCharCode(ch)))
                ++this.pos;
              else
                break loop;
          }
        }
      }, pp$9.finishToken = function(type, val) {
        this.end = this.pos, this.options.locations && (this.endLoc = this.curPosition());
        var prevType = this.type;
        this.type = type, this.value = val, this.updateContext(prevType);
      }, pp$9.readToken_dot = function() {
        var next = this.input.charCodeAt(this.pos + 1);
        if (next >= 48 && next <= 57)
          return this.readNumber(!0);
        var next2 = this.input.charCodeAt(this.pos + 2);
        return this.options.ecmaVersion >= 6 && next === 46 && next2 === 46 ? (this.pos += 3, this.finishToken(types.ellipsis)) : (++this.pos, this.finishToken(types.dot));
      }, pp$9.readToken_slash = function() {
        var next = this.input.charCodeAt(this.pos + 1);
        return this.exprAllowed ? (++this.pos, this.readRegexp()) : next === 61 ? this.finishOp(types.assign, 2) : this.finishOp(types.slash, 1);
      }, pp$9.readToken_mult_modulo_exp = function(code) {
        var next = this.input.charCodeAt(this.pos + 1), size = 1, tokentype = code === 42 ? types.star : types.modulo;
        return this.options.ecmaVersion >= 7 && code === 42 && next === 42 && (++size, tokentype = types.starstar, next = this.input.charCodeAt(this.pos + 2)), next === 61 ? this.finishOp(types.assign, size + 1) : this.finishOp(tokentype, size);
      }, pp$9.readToken_pipe_amp = function(code) {
        var next = this.input.charCodeAt(this.pos + 1);
        if (next === code) {
          if (this.options.ecmaVersion >= 12) {
            var next2 = this.input.charCodeAt(this.pos + 2);
            if (next2 === 61)
              return this.finishOp(types.assign, 3);
          }
          return this.finishOp(code === 124 ? types.logicalOR : types.logicalAND, 2);
        }
        return next === 61 ? this.finishOp(types.assign, 2) : this.finishOp(code === 124 ? types.bitwiseOR : types.bitwiseAND, 1);
      }, pp$9.readToken_caret = function() {
        var next = this.input.charCodeAt(this.pos + 1);
        return next === 61 ? this.finishOp(types.assign, 2) : this.finishOp(types.bitwiseXOR, 1);
      }, pp$9.readToken_plus_min = function(code) {
        var next = this.input.charCodeAt(this.pos + 1);
        return next === code ? next === 45 && !this.inModule && this.input.charCodeAt(this.pos + 2) === 62 && (this.lastTokEnd === 0 || lineBreak.test(this.input.slice(this.lastTokEnd, this.pos))) ? (this.skipLineComment(3), this.skipSpace(), this.nextToken()) : this.finishOp(types.incDec, 2) : next === 61 ? this.finishOp(types.assign, 2) : this.finishOp(types.plusMin, 1);
      }, pp$9.readToken_lt_gt = function(code) {
        var next = this.input.charCodeAt(this.pos + 1), size = 1;
        return next === code ? (size = code === 62 && this.input.charCodeAt(this.pos + 2) === 62 ? 3 : 2, this.input.charCodeAt(this.pos + size) === 61 ? this.finishOp(types.assign, size + 1) : this.finishOp(types.bitShift, size)) : next === 33 && code === 60 && !this.inModule && this.input.charCodeAt(this.pos + 2) === 45 && this.input.charCodeAt(this.pos + 3) === 45 ? (this.skipLineComment(4), this.skipSpace(), this.nextToken()) : (next === 61 && (size = 2), this.finishOp(types.relational, size));
      }, pp$9.readToken_eq_excl = function(code) {
        var next = this.input.charCodeAt(this.pos + 1);
        return next === 61 ? this.finishOp(types.equality, this.input.charCodeAt(this.pos + 2) === 61 ? 3 : 2) : code === 61 && next === 62 && this.options.ecmaVersion >= 6 ? (this.pos += 2, this.finishToken(types.arrow)) : this.finishOp(code === 61 ? types.eq : types.prefix, 1);
      }, pp$9.readToken_question = function() {
        var ecmaVersion = this.options.ecmaVersion;
        if (ecmaVersion >= 11) {
          var next = this.input.charCodeAt(this.pos + 1);
          if (next === 46) {
            var next2 = this.input.charCodeAt(this.pos + 2);
            if (next2 < 48 || next2 > 57)
              return this.finishOp(types.questionDot, 2);
          }
          if (next === 63) {
            if (ecmaVersion >= 12) {
              var next2$1 = this.input.charCodeAt(this.pos + 2);
              if (next2$1 === 61)
                return this.finishOp(types.assign, 3);
            }
            return this.finishOp(types.coalesce, 2);
          }
        }
        return this.finishOp(types.question, 1);
      }, pp$9.getTokenFromCode = function(code) {
        switch (code) {
          // The interpretation of a dot depends on whether it is followed
          // by a digit or another two dots.
          case 46:
            return this.readToken_dot();
          // Punctuation tokens.
          case 40:
            return ++this.pos, this.finishToken(types.parenL);
          case 41:
            return ++this.pos, this.finishToken(types.parenR);
          case 59:
            return ++this.pos, this.finishToken(types.semi);
          case 44:
            return ++this.pos, this.finishToken(types.comma);
          case 91:
            return ++this.pos, this.finishToken(types.bracketL);
          case 93:
            return ++this.pos, this.finishToken(types.bracketR);
          case 123:
            return ++this.pos, this.finishToken(types.braceL);
          case 125:
            return ++this.pos, this.finishToken(types.braceR);
          case 58:
            return ++this.pos, this.finishToken(types.colon);
          case 96:
            if (this.options.ecmaVersion < 6)
              break;
            return ++this.pos, this.finishToken(types.backQuote);
          case 48:
            var next = this.input.charCodeAt(this.pos + 1);
            if (next === 120 || next === 88)
              return this.readRadixNumber(16);
            if (this.options.ecmaVersion >= 6) {
              if (next === 111 || next === 79)
                return this.readRadixNumber(8);
              if (next === 98 || next === 66)
                return this.readRadixNumber(2);
            }
          // Anything else beginning with a digit is an integer, octal
          // number, or float.
          case 49:
          case 50:
          case 51:
          case 52:
          case 53:
          case 54:
          case 55:
          case 56:
          case 57:
            return this.readNumber(!1);
          // Quotes produce strings.
          case 34:
          case 39:
            return this.readString(code);
          // Operators are parsed inline in tiny state machines. '=' (61) is
          // often referred to. `finishOp` simply skips the amount of
          // characters it is given as second argument, and returns a token
          // of the type given by its first argument.
          case 47:
            return this.readToken_slash();
          case 37:
          case 42:
            return this.readToken_mult_modulo_exp(code);
          case 124:
          case 38:
            return this.readToken_pipe_amp(code);
          case 94:
            return this.readToken_caret();
          case 43:
          case 45:
            return this.readToken_plus_min(code);
          case 60:
          case 62:
            return this.readToken_lt_gt(code);
          case 61:
          case 33:
            return this.readToken_eq_excl(code);
          case 63:
            return this.readToken_question();
          case 126:
            return this.finishOp(types.prefix, 1);
        }
        this.raise(this.pos, "Unexpected character '" + codePointToString$1(code) + "'");
      }, pp$9.finishOp = function(type, size) {
        var str = this.input.slice(this.pos, this.pos + size);
        return this.pos += size, this.finishToken(type, str);
      }, pp$9.readRegexp = function() {
        for (var escaped, inClass, start = this.pos; ; ) {
          this.pos >= this.input.length && this.raise(start, "Unterminated regular expression");
          var ch = this.input.charAt(this.pos);
          if (lineBreak.test(ch) && this.raise(start, "Unterminated regular expression"), escaped)
            escaped = !1;
          else {
            if (ch === "[")
              inClass = !0;
            else if (ch === "]" && inClass)
              inClass = !1;
            else if (ch === "/" && !inClass)
              break;
            escaped = ch === "\\";
          }
          ++this.pos;
        }
        var pattern = this.input.slice(start, this.pos);
        ++this.pos;
        var flagsStart = this.pos, flags = this.readWord1();
        this.containsEsc && this.unexpected(flagsStart);
        var state = this.regexpState || (this.regexpState = new RegExpValidationState(this));
        state.reset(start, pattern, flags), this.validateRegExpFlags(state), this.validateRegExpPattern(state);
        var value = null;
        try {
          value = new RegExp(pattern, flags);
        } catch {
        }
        return this.finishToken(types.regexp, { pattern, flags, value });
      }, pp$9.readInt = function(radix, len, maybeLegacyOctalNumericLiteral) {
        for (var allowSeparators = this.options.ecmaVersion >= 12 && len === void 0, isLegacyOctalNumericLiteral = maybeLegacyOctalNumericLiteral && this.input.charCodeAt(this.pos) === 48, start = this.pos, total = 0, lastCode = 0, i = 0, e = len ?? 1 / 0; i < e; ++i, ++this.pos) {
          var code = this.input.charCodeAt(this.pos), val = void 0;
          if (allowSeparators && code === 95) {
            isLegacyOctalNumericLiteral && this.raiseRecoverable(this.pos, "Numeric separator is not allowed in legacy octal numeric literals"), lastCode === 95 && this.raiseRecoverable(this.pos, "Numeric separator must be exactly one underscore"), i === 0 && this.raiseRecoverable(this.pos, "Numeric separator is not allowed at the first of digits"), lastCode = code;
            continue;
          }
          if (code >= 97 ? val = code - 97 + 10 : code >= 65 ? val = code - 65 + 10 : code >= 48 && code <= 57 ? val = code - 48 : val = 1 / 0, val >= radix)
            break;
          lastCode = code, total = total * radix + val;
        }
        return allowSeparators && lastCode === 95 && this.raiseRecoverable(this.pos - 1, "Numeric separator is not allowed at the last of digits"), this.pos === start || len != null && this.pos - start !== len ? null : total;
      };
      function stringToNumber(str, isLegacyOctalNumericLiteral) {
        return isLegacyOctalNumericLiteral ? parseInt(str, 8) : parseFloat(str.replace(/_/g, ""));
      }
      function stringToBigInt(str) {
        return typeof BigInt != "function" ? null : BigInt(str.replace(/_/g, ""));
      }
      pp$9.readRadixNumber = function(radix) {
        var start = this.pos;
        this.pos += 2;
        var val = this.readInt(radix);
        return val == null && this.raise(this.start + 2, "Expected number in radix " + radix), this.options.ecmaVersion >= 11 && this.input.charCodeAt(this.pos) === 110 ? (val = stringToBigInt(this.input.slice(start, this.pos)), ++this.pos) : isIdentifierStart(this.fullCharCodeAtPos()) && this.raise(this.pos, "Identifier directly after number"), this.finishToken(types.num, val);
      }, pp$9.readNumber = function(startsWithDot) {
        var start = this.pos;
        !startsWithDot && this.readInt(10, void 0, !0) === null && this.raise(start, "Invalid number");
        var octal = this.pos - start >= 2 && this.input.charCodeAt(start) === 48;
        octal && this.strict && this.raise(start, "Invalid number");
        var next = this.input.charCodeAt(this.pos);
        if (!octal && !startsWithDot && this.options.ecmaVersion >= 11 && next === 110) {
          var val$1 = stringToBigInt(this.input.slice(start, this.pos));
          return ++this.pos, isIdentifierStart(this.fullCharCodeAtPos()) && this.raise(this.pos, "Identifier directly after number"), this.finishToken(types.num, val$1);
        }
        octal && /[89]/.test(this.input.slice(start, this.pos)) && (octal = !1), next === 46 && !octal && (++this.pos, this.readInt(10), next = this.input.charCodeAt(this.pos)), (next === 69 || next === 101) && !octal && (next = this.input.charCodeAt(++this.pos), (next === 43 || next === 45) && ++this.pos, this.readInt(10) === null && this.raise(start, "Invalid number")), isIdentifierStart(this.fullCharCodeAtPos()) && this.raise(this.pos, "Identifier directly after number");
        var val = stringToNumber(this.input.slice(start, this.pos), octal);
        return this.finishToken(types.num, val);
      }, pp$9.readCodePoint = function() {
        var ch = this.input.charCodeAt(this.pos), code;
        if (ch === 123) {
          this.options.ecmaVersion < 6 && this.unexpected();
          var codePos = ++this.pos;
          code = this.readHexChar(this.input.indexOf("}", this.pos) - this.pos), ++this.pos, code > 1114111 && this.invalidStringToken(codePos, "Code point out of bounds");
        } else
          code = this.readHexChar(4);
        return code;
      };
      function codePointToString$1(code) {
        return code <= 65535 ? String.fromCharCode(code) : (code -= 65536, String.fromCharCode((code >> 10) + 55296, (code & 1023) + 56320));
      }
      pp$9.readString = function(quote) {
        for (var out = "", chunkStart = ++this.pos; ; ) {
          this.pos >= this.input.length && this.raise(this.start, "Unterminated string constant");
          var ch = this.input.charCodeAt(this.pos);
          if (ch === quote)
            break;
          ch === 92 ? (out += this.input.slice(chunkStart, this.pos), out += this.readEscapedChar(!1), chunkStart = this.pos) : (isNewLine(ch, this.options.ecmaVersion >= 10) && this.raise(this.start, "Unterminated string constant"), ++this.pos);
        }
        return out += this.input.slice(chunkStart, this.pos++), this.finishToken(types.string, out);
      };
      var INVALID_TEMPLATE_ESCAPE_ERROR = {};
      pp$9.tryReadTemplateToken = function() {
        this.inTemplateElement = !0;
        try {
          this.readTmplToken();
        } catch (err) {
          if (err === INVALID_TEMPLATE_ESCAPE_ERROR)
            this.readInvalidTemplateToken();
          else
            throw err;
        }
        this.inTemplateElement = !1;
      }, pp$9.invalidStringToken = function(position, message) {
        if (this.inTemplateElement && this.options.ecmaVersion >= 9)
          throw INVALID_TEMPLATE_ESCAPE_ERROR;
        this.raise(position, message);
      }, pp$9.readTmplToken = function() {
        for (var out = "", chunkStart = this.pos; ; ) {
          this.pos >= this.input.length && this.raise(this.start, "Unterminated template");
          var ch = this.input.charCodeAt(this.pos);
          if (ch === 96 || ch === 36 && this.input.charCodeAt(this.pos + 1) === 123)
            return this.pos === this.start && (this.type === types.template || this.type === types.invalidTemplate) ? ch === 36 ? (this.pos += 2, this.finishToken(types.dollarBraceL)) : (++this.pos, this.finishToken(types.backQuote)) : (out += this.input.slice(chunkStart, this.pos), this.finishToken(types.template, out));
          if (ch === 92)
            out += this.input.slice(chunkStart, this.pos), out += this.readEscapedChar(!0), chunkStart = this.pos;
          else if (isNewLine(ch)) {
            switch (out += this.input.slice(chunkStart, this.pos), ++this.pos, ch) {
              case 13:
                this.input.charCodeAt(this.pos) === 10 && ++this.pos;
              case 10:
                out += `
`;
                break;
              default:
                out += String.fromCharCode(ch);
                break;
            }
            this.options.locations && (++this.curLine, this.lineStart = this.pos), chunkStart = this.pos;
          } else
            ++this.pos;
        }
      }, pp$9.readInvalidTemplateToken = function() {
        for (; this.pos < this.input.length; this.pos++)
          switch (this.input[this.pos]) {
            case "\\":
              ++this.pos;
              break;
            case "$":
              if (this.input[this.pos + 1] !== "{")
                break;
            // falls through
            case "`":
              return this.finishToken(types.invalidTemplate, this.input.slice(this.start, this.pos));
          }
        this.raise(this.start, "Unterminated template");
      }, pp$9.readEscapedChar = function(inTemplate) {
        var ch = this.input.charCodeAt(++this.pos);
        switch (++this.pos, ch) {
          case 110:
            return `
`;
          // 'n' -> '\n'
          case 114:
            return "\r";
          // 'r' -> '\r'
          case 120:
            return String.fromCharCode(this.readHexChar(2));
          // 'x'
          case 117:
            return codePointToString$1(this.readCodePoint());
          // 'u'
          case 116:
            return "	";
          // 't' -> '\t'
          case 98:
            return "\b";
          // 'b' -> '\b'
          case 118:
            return "\v";
          // 'v' -> '\u000b'
          case 102:
            return "\f";
          // 'f' -> '\f'
          case 13:
            this.input.charCodeAt(this.pos) === 10 && ++this.pos;
          // '\r\n'
          case 10:
            return this.options.locations && (this.lineStart = this.pos, ++this.curLine), "";
          case 56:
          case 57:
            if (inTemplate) {
              var codePos = this.pos - 1;
              return this.invalidStringToken(
                codePos,
                "Invalid escape sequence in template string"
              ), null;
            }
          default:
            if (ch >= 48 && ch <= 55) {
              var octalStr = this.input.substr(this.pos - 1, 3).match(/^[0-7]+/)[0], octal = parseInt(octalStr, 8);
              return octal > 255 && (octalStr = octalStr.slice(0, -1), octal = parseInt(octalStr, 8)), this.pos += octalStr.length - 1, ch = this.input.charCodeAt(this.pos), (octalStr !== "0" || ch === 56 || ch === 57) && (this.strict || inTemplate) && this.invalidStringToken(
                this.pos - 1 - octalStr.length,
                inTemplate ? "Octal literal in template string" : "Octal literal in strict mode"
              ), String.fromCharCode(octal);
            }
            return isNewLine(ch) ? "" : String.fromCharCode(ch);
        }
      }, pp$9.readHexChar = function(len) {
        var codePos = this.pos, n = this.readInt(16, len);
        return n === null && this.invalidStringToken(codePos, "Bad character escape sequence"), n;
      }, pp$9.readWord1 = function() {
        this.containsEsc = !1;
        for (var word = "", first = !0, chunkStart = this.pos, astral = this.options.ecmaVersion >= 6; this.pos < this.input.length; ) {
          var ch = this.fullCharCodeAtPos();
          if (isIdentifierChar(ch, astral))
            this.pos += ch <= 65535 ? 1 : 2;
          else if (ch === 92) {
            this.containsEsc = !0, word += this.input.slice(chunkStart, this.pos);
            var escStart = this.pos;
            this.input.charCodeAt(++this.pos) !== 117 && this.invalidStringToken(this.pos, "Expecting Unicode escape sequence \\uXXXX"), ++this.pos;
            var esc = this.readCodePoint();
            (first ? isIdentifierStart : isIdentifierChar)(esc, astral) || this.invalidStringToken(escStart, "Invalid Unicode escape"), word += codePointToString$1(esc), chunkStart = this.pos;
          } else
            break;
          first = !1;
        }
        return word + this.input.slice(chunkStart, this.pos);
      }, pp$9.readWord = function() {
        var word = this.readWord1(), type = types.name;
        return this.keywords.test(word) && (type = keywords$1[word]), this.finishToken(type, word);
      };
      var version = "7.4.1";
      Parser2.acorn = {
        Parser: Parser2,
        version,
        defaultOptions,
        Position,
        SourceLocation,
        getLineInfo,
        Node,
        TokenType,
        tokTypes: types,
        keywordTypes: keywords$1,
        TokContext,
        tokContexts: types$1,
        isIdentifierChar,
        isIdentifierStart,
        Token,
        isNewLine,
        lineBreak,
        lineBreakG,
        nonASCIIwhitespace
      };
      function parse2(input, options) {
        return Parser2.parse(input, options);
      }
      function parseExpressionAt(input, pos, options) {
        return Parser2.parseExpressionAt(input, pos, options);
      }
      function tokenizer(input, options) {
        return Parser2.tokenizer(input, options);
      }
      exports2.Node = Node, exports2.Parser = Parser2, exports2.Position = Position, exports2.SourceLocation = SourceLocation, exports2.TokContext = TokContext, exports2.Token = Token, exports2.TokenType = TokenType, exports2.defaultOptions = defaultOptions, exports2.getLineInfo = getLineInfo, exports2.isIdentifierChar = isIdentifierChar, exports2.isIdentifierStart = isIdentifierStart, exports2.isNewLine = isNewLine, exports2.keywordTypes = keywords$1, exports2.lineBreak = lineBreak, exports2.lineBreakG = lineBreakG, exports2.nonASCIIwhitespace = nonASCIIwhitespace, exports2.parse = parse2, exports2.parseExpressionAt = parseExpressionAt, exports2.tokContexts = types$1, exports2.tokTypes = types, exports2.tokenizer = tokenizer, exports2.version = version, Object.defineProperty(exports2, "__esModule", { value: !0 });
    }));
  }
});

// node_modules/acorn-jsx/xhtml.js
var require_xhtml = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "node_modules/acorn-jsx/xhtml.js"(exports, module) {
    module.exports = {
      quot: '"',
      amp: "&",
      apos: "'",
      lt: "<",
      gt: ">",
      nbsp: "\xA0",
      iexcl: "\xA1",
      cent: "\xA2",
      pound: "\xA3",
      curren: "\xA4",
      yen: "\xA5",
      brvbar: "\xA6",
      sect: "\xA7",
      uml: "\xA8",
      copy: "\xA9",
      ordf: "\xAA",
      laquo: "\xAB",
      not: "\xAC",
      shy: "\xAD",
      reg: "\xAE",
      macr: "\xAF",
      deg: "\xB0",
      plusmn: "\xB1",
      sup2: "\xB2",
      sup3: "\xB3",
      acute: "\xB4",
      micro: "\xB5",
      para: "\xB6",
      middot: "\xB7",
      cedil: "\xB8",
      sup1: "\xB9",
      ordm: "\xBA",
      raquo: "\xBB",
      frac14: "\xBC",
      frac12: "\xBD",
      frac34: "\xBE",
      iquest: "\xBF",
      Agrave: "\xC0",
      Aacute: "\xC1",
      Acirc: "\xC2",
      Atilde: "\xC3",
      Auml: "\xC4",
      Aring: "\xC5",
      AElig: "\xC6",
      Ccedil: "\xC7",
      Egrave: "\xC8",
      Eacute: "\xC9",
      Ecirc: "\xCA",
      Euml: "\xCB",
      Igrave: "\xCC",
      Iacute: "\xCD",
      Icirc: "\xCE",
      Iuml: "\xCF",
      ETH: "\xD0",
      Ntilde: "\xD1",
      Ograve: "\xD2",
      Oacute: "\xD3",
      Ocirc: "\xD4",
      Otilde: "\xD5",
      Ouml: "\xD6",
      times: "\xD7",
      Oslash: "\xD8",
      Ugrave: "\xD9",
      Uacute: "\xDA",
      Ucirc: "\xDB",
      Uuml: "\xDC",
      Yacute: "\xDD",
      THORN: "\xDE",
      szlig: "\xDF",
      agrave: "\xE0",
      aacute: "\xE1",
      acirc: "\xE2",
      atilde: "\xE3",
      auml: "\xE4",
      aring: "\xE5",
      aelig: "\xE6",
      ccedil: "\xE7",
      egrave: "\xE8",
      eacute: "\xE9",
      ecirc: "\xEA",
      euml: "\xEB",
      igrave: "\xEC",
      iacute: "\xED",
      icirc: "\xEE",
      iuml: "\xEF",
      eth: "\xF0",
      ntilde: "\xF1",
      ograve: "\xF2",
      oacute: "\xF3",
      ocirc: "\xF4",
      otilde: "\xF5",
      ouml: "\xF6",
      divide: "\xF7",
      oslash: "\xF8",
      ugrave: "\xF9",
      uacute: "\xFA",
      ucirc: "\xFB",
      uuml: "\xFC",
      yacute: "\xFD",
      thorn: "\xFE",
      yuml: "\xFF",
      OElig: "\u0152",
      oelig: "\u0153",
      Scaron: "\u0160",
      scaron: "\u0161",
      Yuml: "\u0178",
      fnof: "\u0192",
      circ: "\u02C6",
      tilde: "\u02DC",
      Alpha: "\u0391",
      Beta: "\u0392",
      Gamma: "\u0393",
      Delta: "\u0394",
      Epsilon: "\u0395",
      Zeta: "\u0396",
      Eta: "\u0397",
      Theta: "\u0398",
      Iota: "\u0399",
      Kappa: "\u039A",
      Lambda: "\u039B",
      Mu: "\u039C",
      Nu: "\u039D",
      Xi: "\u039E",
      Omicron: "\u039F",
      Pi: "\u03A0",
      Rho: "\u03A1",
      Sigma: "\u03A3",
      Tau: "\u03A4",
      Upsilon: "\u03A5",
      Phi: "\u03A6",
      Chi: "\u03A7",
      Psi: "\u03A8",
      Omega: "\u03A9",
      alpha: "\u03B1",
      beta: "\u03B2",
      gamma: "\u03B3",
      delta: "\u03B4",
      epsilon: "\u03B5",
      zeta: "\u03B6",
      eta: "\u03B7",
      theta: "\u03B8",
      iota: "\u03B9",
      kappa: "\u03BA",
      lambda: "\u03BB",
      mu: "\u03BC",
      nu: "\u03BD",
      xi: "\u03BE",
      omicron: "\u03BF",
      pi: "\u03C0",
      rho: "\u03C1",
      sigmaf: "\u03C2",
      sigma: "\u03C3",
      tau: "\u03C4",
      upsilon: "\u03C5",
      phi: "\u03C6",
      chi: "\u03C7",
      psi: "\u03C8",
      omega: "\u03C9",
      thetasym: "\u03D1",
      upsih: "\u03D2",
      piv: "\u03D6",
      ensp: "\u2002",
      emsp: "\u2003",
      thinsp: "\u2009",
      zwnj: "\u200C",
      zwj: "\u200D",
      lrm: "\u200E",
      rlm: "\u200F",
      ndash: "\u2013",
      mdash: "\u2014",
      lsquo: "\u2018",
      rsquo: "\u2019",
      sbquo: "\u201A",
      ldquo: "\u201C",
      rdquo: "\u201D",
      bdquo: "\u201E",
      dagger: "\u2020",
      Dagger: "\u2021",
      bull: "\u2022",
      hellip: "\u2026",
      permil: "\u2030",
      prime: "\u2032",
      Prime: "\u2033",
      lsaquo: "\u2039",
      rsaquo: "\u203A",
      oline: "\u203E",
      frasl: "\u2044",
      euro: "\u20AC",
      image: "\u2111",
      weierp: "\u2118",
      real: "\u211C",
      trade: "\u2122",
      alefsym: "\u2135",
      larr: "\u2190",
      uarr: "\u2191",
      rarr: "\u2192",
      darr: "\u2193",
      harr: "\u2194",
      crarr: "\u21B5",
      lArr: "\u21D0",
      uArr: "\u21D1",
      rArr: "\u21D2",
      dArr: "\u21D3",
      hArr: "\u21D4",
      forall: "\u2200",
      part: "\u2202",
      exist: "\u2203",
      empty: "\u2205",
      nabla: "\u2207",
      isin: "\u2208",
      notin: "\u2209",
      ni: "\u220B",
      prod: "\u220F",
      sum: "\u2211",
      minus: "\u2212",
      lowast: "\u2217",
      radic: "\u221A",
      prop: "\u221D",
      infin: "\u221E",
      ang: "\u2220",
      and: "\u2227",
      or: "\u2228",
      cap: "\u2229",
      cup: "\u222A",
      int: "\u222B",
      there4: "\u2234",
      sim: "\u223C",
      cong: "\u2245",
      asymp: "\u2248",
      ne: "\u2260",
      equiv: "\u2261",
      le: "\u2264",
      ge: "\u2265",
      sub: "\u2282",
      sup: "\u2283",
      nsub: "\u2284",
      sube: "\u2286",
      supe: "\u2287",
      oplus: "\u2295",
      otimes: "\u2297",
      perp: "\u22A5",
      sdot: "\u22C5",
      lceil: "\u2308",
      rceil: "\u2309",
      lfloor: "\u230A",
      rfloor: "\u230B",
      lang: "\u2329",
      rang: "\u232A",
      loz: "\u25CA",
      spades: "\u2660",
      clubs: "\u2663",
      hearts: "\u2665",
      diams: "\u2666"
    };
  }
});

// node_modules/acorn-jsx/index.js
var require_acorn_jsx = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "node_modules/acorn-jsx/index.js"(exports, module) {
    "use strict";
    var XHTMLEntities = require_xhtml(), hexNumber = /^[\da-fA-F]+$/, decimalNumber = /^\d+$/, acornJsxMap = /* @__PURE__ */ new WeakMap();
    function getJsxTokens(acorn) {
      acorn = acorn.Parser.acorn || acorn;
      let acornJsx = acornJsxMap.get(acorn);
      if (!acornJsx) {
        let tt = acorn.tokTypes, TokContext = acorn.TokContext, TokenType = acorn.TokenType, tc_oTag = new TokContext("<tag", !1), tc_cTag = new TokContext("</tag", !1), tc_expr = new TokContext("<tag>...</tag>", !0, !0), tokContexts = {
          tc_oTag,
          tc_cTag,
          tc_expr
        }, tokTypes = {
          jsxName: new TokenType("jsxName"),
          jsxText: new TokenType("jsxText", { beforeExpr: !0 }),
          jsxTagStart: new TokenType("jsxTagStart", { startsExpr: !0 }),
          jsxTagEnd: new TokenType("jsxTagEnd")
        };
        tokTypes.jsxTagStart.updateContext = function() {
          this.context.push(tc_expr), this.context.push(tc_oTag), this.exprAllowed = !1;
        }, tokTypes.jsxTagEnd.updateContext = function(prevType) {
          let out = this.context.pop();
          out === tc_oTag && prevType === tt.slash || out === tc_cTag ? (this.context.pop(), this.exprAllowed = this.curContext() === tc_expr) : this.exprAllowed = !0;
        }, acornJsx = { tokContexts, tokTypes }, acornJsxMap.set(acorn, acornJsx);
      }
      return acornJsx;
    }
    function getQualifiedJSXName(object) {
      if (!object)
        return object;
      if (object.type === "JSXIdentifier")
        return object.name;
      if (object.type === "JSXNamespacedName")
        return object.namespace.name + ":" + object.name.name;
      if (object.type === "JSXMemberExpression")
        return getQualifiedJSXName(object.object) + "." + getQualifiedJSXName(object.property);
    }
    module.exports = function(options) {
      return options = options || {}, function(Parser2) {
        return plugin({
          allowNamespaces: options.allowNamespaces !== !1,
          allowNamespacedObjects: !!options.allowNamespacedObjects
        }, Parser2);
      };
    };
    Object.defineProperty(module.exports, "tokTypes", {
      get: function() {
        return getJsxTokens(require_acorn()).tokTypes;
      },
      configurable: !0,
      enumerable: !0
    });
    function plugin(options, Parser2) {
      let acorn = Parser2.acorn || require_acorn(), acornJsx = getJsxTokens(acorn), tt = acorn.tokTypes, tok = acornJsx.tokTypes, tokContexts = acorn.tokContexts, tc_oTag = acornJsx.tokContexts.tc_oTag, tc_cTag = acornJsx.tokContexts.tc_cTag, tc_expr = acornJsx.tokContexts.tc_expr, isNewLine = acorn.isNewLine, isIdentifierStart = acorn.isIdentifierStart, isIdentifierChar = acorn.isIdentifierChar;
      return class extends Parser2 {
        // Expose actual `tokTypes` and `tokContexts` to other plugins.
        static get acornJsx() {
          return acornJsx;
        }
        // Reads inline JSX contents token.
        jsx_readToken() {
          let out = "", chunkStart = this.pos;
          for (; ; ) {
            this.pos >= this.input.length && this.raise(this.start, "Unterminated JSX contents");
            let ch = this.input.charCodeAt(this.pos);
            switch (ch) {
              case 60:
              // '<'
              case 123:
                return this.pos === this.start ? ch === 60 && this.exprAllowed ? (++this.pos, this.finishToken(tok.jsxTagStart)) : this.getTokenFromCode(ch) : (out += this.input.slice(chunkStart, this.pos), this.finishToken(tok.jsxText, out));
              case 38:
                out += this.input.slice(chunkStart, this.pos), out += this.jsx_readEntity(), chunkStart = this.pos;
                break;
              case 62:
              // '>'
              case 125:
                this.raise(
                  this.pos,
                  "Unexpected token `" + this.input[this.pos] + "`. Did you mean `" + (ch === 62 ? "&gt;" : "&rbrace;") + '` or `{"' + this.input[this.pos] + '"}`?'
                );
              default:
                isNewLine(ch) ? (out += this.input.slice(chunkStart, this.pos), out += this.jsx_readNewLine(!0), chunkStart = this.pos) : ++this.pos;
            }
          }
        }
        jsx_readNewLine(normalizeCRLF) {
          let ch = this.input.charCodeAt(this.pos), out;
          return ++this.pos, ch === 13 && this.input.charCodeAt(this.pos) === 10 ? (++this.pos, out = normalizeCRLF ? `
` : `\r
`) : out = String.fromCharCode(ch), this.options.locations && (++this.curLine, this.lineStart = this.pos), out;
        }
        jsx_readString(quote) {
          let out = "", chunkStart = ++this.pos;
          for (; ; ) {
            this.pos >= this.input.length && this.raise(this.start, "Unterminated string constant");
            let ch = this.input.charCodeAt(this.pos);
            if (ch === quote) break;
            ch === 38 ? (out += this.input.slice(chunkStart, this.pos), out += this.jsx_readEntity(), chunkStart = this.pos) : isNewLine(ch) ? (out += this.input.slice(chunkStart, this.pos), out += this.jsx_readNewLine(!1), chunkStart = this.pos) : ++this.pos;
          }
          return out += this.input.slice(chunkStart, this.pos++), this.finishToken(tt.string, out);
        }
        jsx_readEntity() {
          let str = "", count = 0, entity, ch = this.input[this.pos];
          ch !== "&" && this.raise(this.pos, "Entity must start with an ampersand");
          let startPos = ++this.pos;
          for (; this.pos < this.input.length && count++ < 10; ) {
            if (ch = this.input[this.pos++], ch === ";") {
              str[0] === "#" ? str[1] === "x" ? (str = str.substr(2), hexNumber.test(str) && (entity = String.fromCharCode(parseInt(str, 16)))) : (str = str.substr(1), decimalNumber.test(str) && (entity = String.fromCharCode(parseInt(str, 10)))) : entity = XHTMLEntities[str];
              break;
            }
            str += ch;
          }
          return entity || (this.pos = startPos, "&");
        }
        // Read a JSX identifier (valid tag or attribute name).
        //
        // Optimized version since JSX identifiers can't contain
        // escape characters and so can be read as single slice.
        // Also assumes that first character was already checked
        // by isIdentifierStart in readToken.
        jsx_readWord() {
          let ch, start = this.pos;
          do
            ch = this.input.charCodeAt(++this.pos);
          while (isIdentifierChar(ch) || ch === 45);
          return this.finishToken(tok.jsxName, this.input.slice(start, this.pos));
        }
        // Parse next token as JSX identifier
        jsx_parseIdentifier() {
          let node = this.startNode();
          return this.type === tok.jsxName ? node.name = this.value : this.type.keyword ? node.name = this.type.keyword : this.unexpected(), this.next(), this.finishNode(node, "JSXIdentifier");
        }
        // Parse namespaced identifier.
        jsx_parseNamespacedName() {
          let startPos = this.start, startLoc = this.startLoc, name = this.jsx_parseIdentifier();
          if (!options.allowNamespaces || !this.eat(tt.colon)) return name;
          var node = this.startNodeAt(startPos, startLoc);
          return node.namespace = name, node.name = this.jsx_parseIdentifier(), this.finishNode(node, "JSXNamespacedName");
        }
        // Parses element name in any form - namespaced, member
        // or single identifier.
        jsx_parseElementName() {
          if (this.type === tok.jsxTagEnd) return "";
          let startPos = this.start, startLoc = this.startLoc, node = this.jsx_parseNamespacedName();
          for (this.type === tt.dot && node.type === "JSXNamespacedName" && !options.allowNamespacedObjects && this.unexpected(); this.eat(tt.dot); ) {
            let newNode = this.startNodeAt(startPos, startLoc);
            newNode.object = node, newNode.property = this.jsx_parseIdentifier(), node = this.finishNode(newNode, "JSXMemberExpression");
          }
          return node;
        }
        // Parses any type of JSX attribute value.
        jsx_parseAttributeValue() {
          switch (this.type) {
            case tt.braceL:
              let node = this.jsx_parseExpressionContainer();
              return node.expression.type === "JSXEmptyExpression" && this.raise(node.start, "JSX attributes must only be assigned a non-empty expression"), node;
            case tok.jsxTagStart:
            case tt.string:
              return this.parseExprAtom();
            default:
              this.raise(this.start, "JSX value should be either an expression or a quoted JSX text");
          }
        }
        // JSXEmptyExpression is unique type since it doesn't actually parse anything,
        // and so it should start at the end of last read token (left brace) and finish
        // at the beginning of the next one (right brace).
        jsx_parseEmptyExpression() {
          let node = this.startNodeAt(this.lastTokEnd, this.lastTokEndLoc);
          return this.finishNodeAt(node, "JSXEmptyExpression", this.start, this.startLoc);
        }
        // Parses JSX expression enclosed into curly brackets.
        jsx_parseExpressionContainer() {
          let node = this.startNode();
          return this.next(), node.expression = this.type === tt.braceR ? this.jsx_parseEmptyExpression() : this.parseExpression(), this.expect(tt.braceR), this.finishNode(node, "JSXExpressionContainer");
        }
        // Parses following JSX attribute name-value pair.
        jsx_parseAttribute() {
          let node = this.startNode();
          return this.eat(tt.braceL) ? (this.expect(tt.ellipsis), node.argument = this.parseMaybeAssign(), this.expect(tt.braceR), this.finishNode(node, "JSXSpreadAttribute")) : (node.name = this.jsx_parseNamespacedName(), node.value = this.eat(tt.eq) ? this.jsx_parseAttributeValue() : null, this.finishNode(node, "JSXAttribute"));
        }
        // Parses JSX opening tag starting after '<'.
        jsx_parseOpeningElementAt(startPos, startLoc) {
          let node = this.startNodeAt(startPos, startLoc);
          node.attributes = [];
          let nodeName = this.jsx_parseElementName();
          for (nodeName && (node.name = nodeName); this.type !== tt.slash && this.type !== tok.jsxTagEnd; )
            node.attributes.push(this.jsx_parseAttribute());
          return node.selfClosing = this.eat(tt.slash), this.expect(tok.jsxTagEnd), this.finishNode(node, nodeName ? "JSXOpeningElement" : "JSXOpeningFragment");
        }
        // Parses JSX closing tag starting after '</'.
        jsx_parseClosingElementAt(startPos, startLoc) {
          let node = this.startNodeAt(startPos, startLoc), nodeName = this.jsx_parseElementName();
          return nodeName && (node.name = nodeName), this.expect(tok.jsxTagEnd), this.finishNode(node, nodeName ? "JSXClosingElement" : "JSXClosingFragment");
        }
        // Parses entire JSX element, including it's opening tag
        // (starting after '<'), attributes, contents and closing tag.
        jsx_parseElementAt(startPos, startLoc) {
          let node = this.startNodeAt(startPos, startLoc), children = [], openingElement = this.jsx_parseOpeningElementAt(startPos, startLoc), closingElement = null;
          if (!openingElement.selfClosing) {
            contents: for (; ; )
              switch (this.type) {
                case tok.jsxTagStart:
                  if (startPos = this.start, startLoc = this.startLoc, this.next(), this.eat(tt.slash)) {
                    closingElement = this.jsx_parseClosingElementAt(startPos, startLoc);
                    break contents;
                  }
                  children.push(this.jsx_parseElementAt(startPos, startLoc));
                  break;
                case tok.jsxText:
                  children.push(this.parseExprAtom());
                  break;
                case tt.braceL:
                  children.push(this.jsx_parseExpressionContainer());
                  break;
                default:
                  this.unexpected();
              }
            getQualifiedJSXName(closingElement.name) !== getQualifiedJSXName(openingElement.name) && this.raise(
              closingElement.start,
              "Expected corresponding JSX closing tag for <" + getQualifiedJSXName(openingElement.name) + ">"
            );
          }
          let fragmentOrElement = openingElement.name ? "Element" : "Fragment";
          return node["opening" + fragmentOrElement] = openingElement, node["closing" + fragmentOrElement] = closingElement, node.children = children, this.type === tt.relational && this.value === "<" && this.raise(this.start, "Adjacent JSX elements must be wrapped in an enclosing tag"), this.finishNode(node, "JSX" + fragmentOrElement);
        }
        // Parse JSX text
        jsx_parseText() {
          let node = this.parseLiteral(this.value);
          return node.type = "JSXText", node;
        }
        // Parses entire JSX element from current position.
        jsx_parseElement() {
          let startPos = this.start, startLoc = this.startLoc;
          return this.next(), this.jsx_parseElementAt(startPos, startLoc);
        }
        parseExprAtom(refShortHandDefaultPos) {
          return this.type === tok.jsxText ? this.jsx_parseText() : this.type === tok.jsxTagStart ? this.jsx_parseElement() : super.parseExprAtom(refShortHandDefaultPos);
        }
        readToken(code) {
          let context = this.curContext();
          if (context === tc_expr) return this.jsx_readToken();
          if (context === tc_oTag || context === tc_cTag) {
            if (isIdentifierStart(code)) return this.jsx_readWord();
            if (code == 62)
              return ++this.pos, this.finishToken(tok.jsxTagEnd);
            if ((code === 34 || code === 39) && context == tc_oTag)
              return this.jsx_readString(code);
          }
          return code === 60 && this.exprAllowed && this.input.charCodeAt(this.pos + 1) !== 33 ? (++this.pos, this.finishToken(tok.jsxTagStart)) : super.readToken(code);
        }
        updateContext(prevType) {
          if (this.type == tt.braceL) {
            var curContext = this.curContext();
            curContext == tc_oTag ? this.context.push(tokContexts.b_expr) : curContext == tc_expr ? this.context.push(tokContexts.b_tmpl) : super.updateContext(prevType), this.exprAllowed = !0;
          } else if (this.type === tt.slash && prevType === tok.jsxTagStart)
            this.context.length -= 2, this.context.push(tc_cTag), this.exprAllowed = !1;
          else
            return super.updateContext(prevType);
        }
      };
    }
  }
});

// ../../../node_modules/html-tags/html-tags.json
var require_html_tags = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/html-tags/html-tags.json"(exports, module) {
    module.exports = [
      "a",
      "abbr",
      "address",
      "area",
      "article",
      "aside",
      "audio",
      "b",
      "base",
      "bdi",
      "bdo",
      "blockquote",
      "body",
      "br",
      "button",
      "canvas",
      "caption",
      "cite",
      "code",
      "col",
      "colgroup",
      "data",
      "datalist",
      "dd",
      "del",
      "details",
      "dfn",
      "dialog",
      "div",
      "dl",
      "dt",
      "em",
      "embed",
      "fieldset",
      "figcaption",
      "figure",
      "footer",
      "form",
      "h1",
      "h2",
      "h3",
      "h4",
      "h5",
      "h6",
      "head",
      "header",
      "hgroup",
      "hr",
      "html",
      "i",
      "iframe",
      "img",
      "input",
      "ins",
      "kbd",
      "label",
      "legend",
      "li",
      "link",
      "main",
      "map",
      "mark",
      "math",
      "menu",
      "menuitem",
      "meta",
      "meter",
      "nav",
      "noscript",
      "object",
      "ol",
      "optgroup",
      "option",
      "output",
      "p",
      "param",
      "picture",
      "pre",
      "progress",
      "q",
      "rb",
      "rp",
      "rt",
      "rtc",
      "ruby",
      "s",
      "samp",
      "script",
      "search",
      "section",
      "select",
      "slot",
      "small",
      "source",
      "span",
      "strong",
      "style",
      "sub",
      "summary",
      "sup",
      "svg",
      "table",
      "tbody",
      "td",
      "template",
      "textarea",
      "tfoot",
      "th",
      "thead",
      "time",
      "title",
      "tr",
      "track",
      "u",
      "ul",
      "var",
      "video",
      "wbr"
    ];
  }
});

// ../../../node_modules/html-tags/index.js
var require_html_tags2 = (0,chunk_UAWMPV5J/* __commonJS */.P$)({
  "../../../node_modules/html-tags/index.js"(exports, module) {
    "use strict";
    module.exports = require_html_tags();
  }
});

// src/entry-preview-argtypes.ts
var entry_preview_argtypes_exports = {};
(0,chunk_UAWMPV5J/* __export */.VA)(entry_preview_argtypes_exports, {
  argTypesEnhancers: () => argTypesEnhancers,
  parameters: () => parameters
});


// src/extractProps.ts


// src/docs/lib/defaultValues/createDefaultValue.ts


// src/docs/lib/captions.ts
var CUSTOM_CAPTION = "custom", OBJECT_CAPTION = "object", ARRAY_CAPTION = "array", CLASS_CAPTION = "class", FUNCTION_CAPTION = "func", ELEMENT_CAPTION = "element";

// src/docs/lib/generateCode.ts
var import_escodegen = (0,chunk_UAWMPV5J/* __toESM */.f1)(require_escodegen(), 1);

// ../../../node_modules/ts-dedent/esm/index.js
function dedent(templ) {
  for (var values = [], _i = 1; _i < arguments.length; _i++)
    values[_i - 1] = arguments[_i];
  var strings = Array.from(typeof templ == "string" ? [templ] : templ);
  strings[strings.length - 1] = strings[strings.length - 1].replace(/\r?\n([\t ]*)$/, "");
  var indentLengths = strings.reduce(function(arr, str) {
    var matches = str.match(/\n([\t ]+|(?!\s).)/g);
    return matches ? arr.concat(matches.map(function(match) {
      var _a, _b;
      return (_b = (_a = match.match(/[\t ]/g)) === null || _a === void 0 ? void 0 : _a.length) !== null && _b !== void 0 ? _b : 0;
    })) : arr;
  }, []);
  if (indentLengths.length) {
    var pattern_1 = new RegExp(`
[	 ]{` + Math.min.apply(Math, indentLengths) + "}", "g");
    strings = strings.map(function(str) {
      return str.replace(pattern_1, `
`);
    });
  }
  strings[0] = strings[0].replace(/^\r?\n/, "");
  var string = strings[0];
  return values.forEach(function(value, i) {
    var endentations = string.match(/(?:^|\n)( *)$/), endentation = endentations ? endentations[1] : "", indentedValue = value;
    typeof value == "string" && value.includes(`
`) && (indentedValue = String(value).split(`
`).map(function(str, i2) {
      return i2 === 0 ? str : "" + endentation + str;
    }).join(`
`)), string += indentedValue + strings[i + 1];
  }), string;
}

// src/docs/lib/generateCode.ts
var BASIC_OPTIONS = {
  format: {
    indent: {
      style: "  "
    },
    semicolons: !1
  }
}, COMPACT_OPTIONS = {
  ...BASIC_OPTIONS,
  format: {
    newline: ""
  }
}, PRETTY_OPTIONS = {
  ...BASIC_OPTIONS
};
function generateCode(ast, compact = !1) {
  return (0, import_escodegen.generate)(ast, compact ? COMPACT_OPTIONS : PRETTY_OPTIONS);
}
function generateObjectCode(ast, compact = !1) {
  return compact ? generateCompactObjectCode(ast) : generateCode(ast);
}
function generateCompactObjectCode(ast) {
  let result = generateCode(ast, !0);
  return result.endsWith(" }") || (result = `${result.slice(0, -1)} }`), result;
}
function generateArrayCode(ast, compact = !1) {
  return compact ? generateCompactArrayCode(ast) : generateMultilineArrayCode(ast);
}
function generateMultilineArrayCode(ast) {
  let result = generateCode(ast);
  return result.endsWith("  }]") && (result = dedent(result)), result;
}
function generateCompactArrayCode(ast) {
  let result = generateCode(ast, !0);
  return result.startsWith("[    ") && (result = result.replace("[    ", "[")), result;
}

// src/docs/lib/inspection/acornParser.ts
var import_acorn = (0,chunk_UAWMPV5J/* __toESM */.f1)(require_acorn(), 1), import_acorn_jsx = (0,chunk_UAWMPV5J/* __toESM */.f1)(require_acorn_jsx(), 1);

// ../../../node_modules/acorn-walk/dist/walk.mjs
function simple(node, visitors, baseVisitor, state, override) {
  baseVisitor || (baseVisitor = base), (function c(node2, st, override2) {
    var type = override2 || node2.type, found = visitors[type];
    baseVisitor[type](node2, st, c), found && found(node2, st);
  })(node, state, override);
}
function ancestor(node, visitors, baseVisitor, state, override) {
  var ancestors = [];
  baseVisitor || (baseVisitor = base), (function c(node2, st, override2) {
    var type = override2 || node2.type, found = visitors[type], isNew = node2 !== ancestors[ancestors.length - 1];
    isNew && ancestors.push(node2), baseVisitor[type](node2, st, c), found && found(node2, st || ancestors, ancestors), isNew && ancestors.pop();
  })(node, state, override);
}
function skipThrough(node, st, c) {
  c(node, st);
}
function ignore(_node, _st, _c) {
}
var base = {};
base.Program = base.BlockStatement = function(node, st, c) {
  for (var i = 0, list = node.body; i < list.length; i += 1) {
    var stmt = list[i];
    c(stmt, st, "Statement");
  }
};
base.Statement = skipThrough;
base.EmptyStatement = ignore;
base.ExpressionStatement = base.ParenthesizedExpression = base.ChainExpression = function(node, st, c) {
  return c(node.expression, st, "Expression");
};
base.IfStatement = function(node, st, c) {
  c(node.test, st, "Expression"), c(node.consequent, st, "Statement"), node.alternate && c(node.alternate, st, "Statement");
};
base.LabeledStatement = function(node, st, c) {
  return c(node.body, st, "Statement");
};
base.BreakStatement = base.ContinueStatement = ignore;
base.WithStatement = function(node, st, c) {
  c(node.object, st, "Expression"), c(node.body, st, "Statement");
};
base.SwitchStatement = function(node, st, c) {
  c(node.discriminant, st, "Expression");
  for (var i$1 = 0, list$1 = node.cases; i$1 < list$1.length; i$1 += 1) {
    var cs = list$1[i$1];
    cs.test && c(cs.test, st, "Expression");
    for (var i = 0, list = cs.consequent; i < list.length; i += 1) {
      var cons = list[i];
      c(cons, st, "Statement");
    }
  }
};
base.SwitchCase = function(node, st, c) {
  node.test && c(node.test, st, "Expression");
  for (var i = 0, list = node.consequent; i < list.length; i += 1) {
    var cons = list[i];
    c(cons, st, "Statement");
  }
};
base.ReturnStatement = base.YieldExpression = base.AwaitExpression = function(node, st, c) {
  node.argument && c(node.argument, st, "Expression");
};
base.ThrowStatement = base.SpreadElement = function(node, st, c) {
  return c(node.argument, st, "Expression");
};
base.TryStatement = function(node, st, c) {
  c(node.block, st, "Statement"), node.handler && c(node.handler, st), node.finalizer && c(node.finalizer, st, "Statement");
};
base.CatchClause = function(node, st, c) {
  node.param && c(node.param, st, "Pattern"), c(node.body, st, "Statement");
};
base.WhileStatement = base.DoWhileStatement = function(node, st, c) {
  c(node.test, st, "Expression"), c(node.body, st, "Statement");
};
base.ForStatement = function(node, st, c) {
  node.init && c(node.init, st, "ForInit"), node.test && c(node.test, st, "Expression"), node.update && c(node.update, st, "Expression"), c(node.body, st, "Statement");
};
base.ForInStatement = base.ForOfStatement = function(node, st, c) {
  c(node.left, st, "ForInit"), c(node.right, st, "Expression"), c(node.body, st, "Statement");
};
base.ForInit = function(node, st, c) {
  node.type === "VariableDeclaration" ? c(node, st) : c(node, st, "Expression");
};
base.DebuggerStatement = ignore;
base.FunctionDeclaration = function(node, st, c) {
  return c(node, st, "Function");
};
base.VariableDeclaration = function(node, st, c) {
  for (var i = 0, list = node.declarations; i < list.length; i += 1) {
    var decl = list[i];
    c(decl, st);
  }
};
base.VariableDeclarator = function(node, st, c) {
  c(node.id, st, "Pattern"), node.init && c(node.init, st, "Expression");
};
base.Function = function(node, st, c) {
  node.id && c(node.id, st, "Pattern");
  for (var i = 0, list = node.params; i < list.length; i += 1) {
    var param = list[i];
    c(param, st, "Pattern");
  }
  c(node.body, st, node.expression ? "Expression" : "Statement");
};
base.Pattern = function(node, st, c) {
  node.type === "Identifier" ? c(node, st, "VariablePattern") : node.type === "MemberExpression" ? c(node, st, "MemberPattern") : c(node, st);
};
base.VariablePattern = ignore;
base.MemberPattern = skipThrough;
base.RestElement = function(node, st, c) {
  return c(node.argument, st, "Pattern");
};
base.ArrayPattern = function(node, st, c) {
  for (var i = 0, list = node.elements; i < list.length; i += 1) {
    var elt = list[i];
    elt && c(elt, st, "Pattern");
  }
};
base.ObjectPattern = function(node, st, c) {
  for (var i = 0, list = node.properties; i < list.length; i += 1) {
    var prop = list[i];
    prop.type === "Property" ? (prop.computed && c(prop.key, st, "Expression"), c(prop.value, st, "Pattern")) : prop.type === "RestElement" && c(prop.argument, st, "Pattern");
  }
};
base.Expression = skipThrough;
base.ThisExpression = base.Super = base.MetaProperty = ignore;
base.ArrayExpression = function(node, st, c) {
  for (var i = 0, list = node.elements; i < list.length; i += 1) {
    var elt = list[i];
    elt && c(elt, st, "Expression");
  }
};
base.ObjectExpression = function(node, st, c) {
  for (var i = 0, list = node.properties; i < list.length; i += 1) {
    var prop = list[i];
    c(prop, st);
  }
};
base.FunctionExpression = base.ArrowFunctionExpression = base.FunctionDeclaration;
base.SequenceExpression = function(node, st, c) {
  for (var i = 0, list = node.expressions; i < list.length; i += 1) {
    var expr = list[i];
    c(expr, st, "Expression");
  }
};
base.TemplateLiteral = function(node, st, c) {
  for (var i = 0, list = node.quasis; i < list.length; i += 1) {
    var quasi = list[i];
    c(quasi, st);
  }
  for (var i$1 = 0, list$1 = node.expressions; i$1 < list$1.length; i$1 += 1) {
    var expr = list$1[i$1];
    c(expr, st, "Expression");
  }
};
base.TemplateElement = ignore;
base.UnaryExpression = base.UpdateExpression = function(node, st, c) {
  c(node.argument, st, "Expression");
};
base.BinaryExpression = base.LogicalExpression = function(node, st, c) {
  c(node.left, st, "Expression"), c(node.right, st, "Expression");
};
base.AssignmentExpression = base.AssignmentPattern = function(node, st, c) {
  c(node.left, st, "Pattern"), c(node.right, st, "Expression");
};
base.ConditionalExpression = function(node, st, c) {
  c(node.test, st, "Expression"), c(node.consequent, st, "Expression"), c(node.alternate, st, "Expression");
};
base.NewExpression = base.CallExpression = function(node, st, c) {
  if (c(node.callee, st, "Expression"), node.arguments)
    for (var i = 0, list = node.arguments; i < list.length; i += 1) {
      var arg = list[i];
      c(arg, st, "Expression");
    }
};
base.MemberExpression = function(node, st, c) {
  c(node.object, st, "Expression"), node.computed && c(node.property, st, "Expression");
};
base.ExportNamedDeclaration = base.ExportDefaultDeclaration = function(node, st, c) {
  node.declaration && c(node.declaration, st, node.type === "ExportNamedDeclaration" || node.declaration.id ? "Statement" : "Expression"), node.source && c(node.source, st, "Expression");
};
base.ExportAllDeclaration = function(node, st, c) {
  node.exported && c(node.exported, st), c(node.source, st, "Expression");
};
base.ImportDeclaration = function(node, st, c) {
  for (var i = 0, list = node.specifiers; i < list.length; i += 1) {
    var spec = list[i];
    c(spec, st);
  }
  c(node.source, st, "Expression");
};
base.ImportExpression = function(node, st, c) {
  c(node.source, st, "Expression");
};
base.ImportSpecifier = base.ImportDefaultSpecifier = base.ImportNamespaceSpecifier = base.Identifier = base.Literal = ignore;
base.TaggedTemplateExpression = function(node, st, c) {
  c(node.tag, st, "Expression"), c(node.quasi, st, "Expression");
};
base.ClassDeclaration = base.ClassExpression = function(node, st, c) {
  return c(node, st, "Class");
};
base.Class = function(node, st, c) {
  node.id && c(node.id, st, "Pattern"), node.superClass && c(node.superClass, st, "Expression"), c(node.body, st);
};
base.ClassBody = function(node, st, c) {
  for (var i = 0, list = node.body; i < list.length; i += 1) {
    var elt = list[i];
    c(elt, st);
  }
};
base.MethodDefinition = base.Property = function(node, st, c) {
  node.computed && c(node.key, st, "Expression"), c(node.value, st, "Expression");
};

// src/docs/lib/inspection/acornParser.ts
var ACORN_WALK_VISITORS = {
  // @ts-expect-error (Converted from ts-ignore)
  ...base,
  JSXElement: () => {
  }
}, acornParser = import_acorn.Parser.extend((0, import_acorn_jsx.default)());
function extractIdentifierName(identifierNode) {
  return identifierNode != null ? identifierNode.name : null;
}
function filterAncestors(ancestors) {
  return ancestors.filter((x) => x.type === "ObjectExpression" || x.type === "ArrayExpression");
}
function calculateNodeDepth(node) {
  let depths = [];
  return ancestor(
    // @ts-expect-error (Converted from ts-ignore)
    node,
    {
      ObjectExpression(_, ancestors) {
        depths.push(filterAncestors(ancestors).length);
      },
      ArrayExpression(_, ancestors) {
        depths.push(filterAncestors(ancestors).length);
      }
    },
    ACORN_WALK_VISITORS
  ), Math.max(...depths);
}
function parseIdentifier(identifierNode) {
  return {
    inferredType: {
      type: "Identifier" /* IDENTIFIER */,
      identifier: extractIdentifierName(identifierNode)
    },
    ast: identifierNode
  };
}
function parseLiteral(literalNode) {
  return {
    inferredType: { type: "Literal" /* LITERAL */ },
    ast: literalNode
  };
}
function parseFunction(funcNode) {
  let innerJsxElementNode;
  simple(
    // @ts-expect-error (Converted from ts-ignore)
    funcNode.body,
    {
      JSXElement(node) {
        innerJsxElementNode = node;
      }
    },
    ACORN_WALK_VISITORS
  );
  let inferredType = {
    type: innerJsxElementNode != null ? "Element" /* ELEMENT */ : "Function" /* FUNCTION */,
    params: funcNode.params,
    hasParams: funcNode.params.length !== 0
  }, identifierName = extractIdentifierName(funcNode.id);
  return identifierName != null && (inferredType.identifier = identifierName), {
    inferredType,
    ast: funcNode
  };
}
function parseClass(classNode) {
  let innerJsxElementNode;
  return simple(
    // @ts-expect-error (Converted from ts-ignore)
    classNode.body,
    {
      JSXElement(node) {
        innerJsxElementNode = node;
      }
    },
    ACORN_WALK_VISITORS
  ), {
    inferredType: {
      type: innerJsxElementNode != null ? "Element" /* ELEMENT */ : "Class" /* CLASS */,
      identifier: extractIdentifierName(classNode.id)
    },
    ast: classNode
  };
}
function parseJsxElement(jsxElementNode) {
  let inferredType = {
    type: "Element" /* ELEMENT */
  }, identifierName = extractIdentifierName(jsxElementNode.openingElement.name);
  return identifierName != null && (inferredType.identifier = identifierName), {
    inferredType,
    ast: jsxElementNode
  };
}
function parseCall(callNode) {
  let identifierNode = callNode.callee.type === "MemberExpression" ? callNode.callee.property : callNode.callee;
  return extractIdentifierName(identifierNode) === "shape" ? parseObject(callNode.arguments[0]) : null;
}
function parseObject(objectNode) {
  return {
    inferredType: { type: "Object" /* OBJECT */, depth: calculateNodeDepth(objectNode) },
    ast: objectNode
  };
}
function parseArray(arrayNode) {
  return {
    inferredType: { type: "Array" /* ARRAY */, depth: calculateNodeDepth(arrayNode) },
    ast: arrayNode
  };
}
function parseExpression(expression) {
  switch (expression.type) {
    case "Identifier":
      return parseIdentifier(expression);
    case "Literal":
      return parseLiteral(expression);
    case "FunctionExpression":
    case "ArrowFunctionExpression":
      return parseFunction(expression);
    case "ClassExpression":
      return parseClass(expression);
    case "JSXElement":
      return parseJsxElement(expression);
    case "CallExpression":
      return parseCall(expression);
    case "ObjectExpression":
      return parseObject(expression);
    case "ArrayExpression":
      return parseArray(expression);
    default:
      return null;
  }
}
function parse(value) {
  let ast = acornParser.parse(`(${value})`, { ecmaVersion: 2020 }), parsingResult = {
    inferredType: { type: "Unknown" /* UNKNOWN */ },
    ast
  };
  if (ast.body[0] != null) {
    let rootNode = ast.body[0];
    if (rootNode.type === "ExpressionStatement") {
      let expressionResult = parseExpression(rootNode.expression);
      expressionResult != null && (parsingResult = expressionResult);
    }
  }
  return parsingResult;
}

// src/docs/lib/inspection/inspectValue.ts
function inspectValue(value) {
  try {
    return { ...parse(value) };
  } catch {
  }
  return { inferredType: { type: "Unknown" /* UNKNOWN */ } };
}

// src/docs/lib/isHtmlTag.ts
var import_html_tags = (0,chunk_UAWMPV5J/* __toESM */.f1)(require_html_tags2(), 1);
function isHtmlTag(tagName) {
  return import_html_tags.default.includes(tagName.toLowerCase());
}

// src/docs/lib/defaultValues/generateArray.ts

function generateArray({ inferredType, ast }) {
  let { depth } = inferredType;
  if (depth <= 2) {
    let compactArray = generateArrayCode(ast, !0);
    if (!(0,chunk_OA6XKWIN/* isTooLongForDefaultValueSummary */.Sy)(compactArray))
      return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(compactArray);
  }
  return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(ARRAY_CAPTION, generateArrayCode(ast));
}

// src/docs/lib/defaultValues/generateObject.ts

function generateObject({ inferredType, ast }) {
  let { depth } = inferredType;
  if (depth === 1) {
    let compactObject = generateObjectCode(ast, !0);
    if (!(0,chunk_OA6XKWIN/* isTooLongForDefaultValueSummary */.Sy)(compactObject))
      return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(compactObject);
  }
  return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(OBJECT_CAPTION, generateObjectCode(ast));
}

// src/docs/lib/defaultValues/prettyIdentifier.ts
function getPrettyFuncIdentifier(identifier, hasArguments) {
  return hasArguments ? `${identifier}( ... )` : `${identifier}()`;
}
function getPrettyElementIdentifier(identifier) {
  return `<${identifier} />`;
}
function getPrettyIdentifier(inferredType) {
  let { type, identifier } = inferredType;
  switch (type) {
    case "Function" /* FUNCTION */:
      return getPrettyFuncIdentifier(identifier, inferredType.hasParams);
    case "Element" /* ELEMENT */:
      return getPrettyElementIdentifier(identifier);
    default:
      return identifier;
  }
}

// src/docs/lib/defaultValues/createDefaultValue.ts
function generateFunc({ inferredType, ast }) {
  let { identifier } = inferredType;
  if (identifier != null)
    return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(
      getPrettyIdentifier(inferredType),
      generateCode(ast)
    );
  let prettyCaption = generateCode(ast, !0);
  return (0,chunk_OA6XKWIN/* isTooLongForDefaultValueSummary */.Sy)(prettyCaption) ? (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(FUNCTION_CAPTION, generateCode(ast)) : (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(prettyCaption);
}
function generateElement(defaultValue, inspectionResult) {
  let { inferredType } = inspectionResult, { identifier } = inferredType;
  if (identifier != null && !isHtmlTag(identifier)) {
    let prettyIdentifier = getPrettyIdentifier(
      inferredType
    );
    return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(prettyIdentifier, defaultValue);
  }
  return (0,chunk_OA6XKWIN/* isTooLongForDefaultValueSummary */.Sy)(defaultValue) ? (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(ELEMENT_CAPTION, defaultValue) : (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(defaultValue);
}
function createDefaultValue(defaultValue) {
  try {
    let inspectionResult = inspectValue(defaultValue);
    switch (inspectionResult.inferredType.type) {
      case "Object" /* OBJECT */:
        return generateObject(inspectionResult);
      case "Function" /* FUNCTION */:
        return generateFunc(inspectionResult);
      case "Element" /* ELEMENT */:
        return generateElement(defaultValue, inspectionResult);
      case "Array" /* ARRAY */:
        return generateArray(inspectionResult);
      default:
        return null;
    }
  } catch (e) {
    console.error(e);
  }
  return null;
}

// src/docs/lib/defaultValues/createFromRawDefaultProp.ts


// ../../../node_modules/es-toolkit/dist/predicate/isPlainObject.mjs
function isPlainObject(value) {
  if (!value || typeof value != "object")
    return !1;
  let proto = Object.getPrototypeOf(value);
  return proto === null || proto === Object.prototype || Object.getPrototypeOf(proto) === null ? Object.prototype.toString.call(value) === "[object Object]" : !1;
}

// ../../../node_modules/es-toolkit/dist/predicate/isFunction.mjs
function isFunction(value) {
  return typeof value == "function";
}

// ../../../node_modules/es-toolkit/dist/predicate/isString.mjs
function isString(value) {
  return typeof value == "string";
}

// src/docs/lib/defaultValues/createFromRawDefaultProp.ts
var reactElementToJSXString = chunk_B3FR4PBN/* reactElementToJsxString */.HA;
function isReactElement(element) {
  return element.$$typeof != null;
}
function extractFunctionName(func, propName) {
  let { name } = func;
  return name !== "" && name !== "anonymous" && name !== propName ? name : null;
}
var stringResolver = (rawDefaultProp) => (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(JSON.stringify(rawDefaultProp));
function generateReactObject(rawDefaultProp) {
  let { type } = rawDefaultProp, { displayName } = type, jsx2 = reactElementToJSXString(rawDefaultProp, {});
  if (displayName != null) {
    let prettyIdentifier = getPrettyElementIdentifier(displayName);
    return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(prettyIdentifier, jsx2);
  }
  if (isString(type) && isHtmlTag(type)) {
    let jsxSummary = reactElementToJSXString(rawDefaultProp, { tabStop: 0 }).replace(/\r?\n|\r/g, "");
    if (!(0,chunk_OA6XKWIN/* isTooLongForDefaultValueSummary */.Sy)(jsxSummary))
      return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(jsxSummary);
  }
  return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(ELEMENT_CAPTION, jsx2);
}
var objectResolver = (rawDefaultProp) => {
  if (isReactElement(rawDefaultProp) && rawDefaultProp.type != null)
    return generateReactObject(rawDefaultProp);
  if (isPlainObject(rawDefaultProp)) {
    let inspectionResult = inspectValue(JSON.stringify(rawDefaultProp));
    return generateObject(inspectionResult);
  }
  if (Array.isArray(rawDefaultProp)) {
    let inspectionResult = inspectValue(JSON.stringify(rawDefaultProp));
    return generateArray(inspectionResult);
  }
  return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(OBJECT_CAPTION);
}, functionResolver = (rawDefaultProp, propDef) => {
  let isElement = !1, inspectionResult;
  if (isFunction(rawDefaultProp.render))
    isElement = !0;
  else if (rawDefaultProp.prototype != null && isFunction(rawDefaultProp.prototype.render))
    isElement = !0;
  else {
    let innerElement;
    try {
      inspectionResult = inspectValue(rawDefaultProp.toString());
      let { hasParams, params } = inspectionResult.inferredType;
      hasParams ? params.length === 1 && params[0].type === "ObjectPattern" && (innerElement = rawDefaultProp({})) : innerElement = rawDefaultProp(), innerElement != null && isReactElement(innerElement) && (isElement = !0);
    } catch {
    }
  }
  let funcName = extractFunctionName(rawDefaultProp, propDef.name);
  if (funcName != null) {
    if (isElement)
      return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(getPrettyElementIdentifier(funcName));
    inspectionResult != null && (inspectionResult = inspectValue(rawDefaultProp.toString()));
    let { hasParams } = inspectionResult.inferredType;
    return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(getPrettyFuncIdentifier(funcName, hasParams));
  }
  return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(isElement ? ELEMENT_CAPTION : FUNCTION_CAPTION);
}, defaultResolver = (rawDefaultProp) => (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(rawDefaultProp.toString()), DEFAULT_TYPE_RESOLVERS = {
  string: stringResolver,
  object: objectResolver,
  function: functionResolver,
  default: defaultResolver
};
function createTypeResolvers(customResolvers = {}) {
  return {
    ...DEFAULT_TYPE_RESOLVERS,
    ...customResolvers
  };
}
function createDefaultValueFromRawDefaultProp(rawDefaultProp, propDef, typeResolvers = DEFAULT_TYPE_RESOLVERS) {
  try {
    switch (typeof rawDefaultProp) {
      case "string":
        return typeResolvers.string(rawDefaultProp, propDef);
      case "object":
        return typeResolvers.object(rawDefaultProp, propDef);
      case "function":
        return typeResolvers.function(rawDefaultProp, propDef);
      default:
        return typeResolvers.default(rawDefaultProp, propDef);
    }
  } catch (e) {
    console.error(e);
  }
  return null;
}

// src/docs/propTypes/createType.ts


// src/docs/propTypes/generateFuncSignature.ts
function generateFuncSignature(params, returns) {
  let hasParams = params != null, hasReturns = returns != null;
  if (!hasParams && !hasReturns)
    return "";
  let funcParts = [];
  if (hasParams) {
    let funcParams = params.map((x) => {
      let prettyName = x.getPrettyName(), typeName = x.getTypeName();
      return typeName != null ? `${prettyName}: ${typeName}` : prettyName;
    });
    funcParts.push(`(${funcParams.join(", ")})`);
  } else
    funcParts.push("()");
  return hasReturns && funcParts.push(`=> ${returns.getTypeName()}`), funcParts.join(" ");
}
function generateShortFuncSignature(params, returns) {
  let hasParams = params != null, hasReturns = returns != null;
  if (!hasParams && !hasReturns)
    return "";
  let funcParts = [];
  return hasParams ? funcParts.push("( ... )") : funcParts.push("()"), hasReturns && funcParts.push(`=> ${returns.getTypeName()}`), funcParts.join(" ");
}
function toMultilineSignature(signature) {
  return signature.replace(/,/g, `,\r
`);
}

// src/docs/propTypes/createType.ts
var MAX_FUNC_LENGTH = 150;
function createTypeDef({
  name,
  short,
  compact,
  full,
  inferredType
}) {
  return {
    name,
    short,
    compact,
    full: full ?? short,
    inferredType
  };
}
function cleanPropTypes(value) {
  return value.replace(/PropTypes./g, "").replace(/.isRequired/g, "");
}
function splitIntoLines(value) {
  return value.split(/\r?\n/);
}
function prettyObject(ast, compact = !1) {
  return cleanPropTypes(generateObjectCode(ast, compact));
}
function prettyArray(ast, compact = !1) {
  return cleanPropTypes(generateCode(ast, compact));
}
function getCaptionForInspectionType(type) {
  switch (type) {
    case "Object" /* OBJECT */:
      return OBJECT_CAPTION;
    case "Array" /* ARRAY */:
      return ARRAY_CAPTION;
    case "Class" /* CLASS */:
      return CLASS_CAPTION;
    case "Function" /* FUNCTION */:
      return FUNCTION_CAPTION;
    case "Element" /* ELEMENT */:
      return ELEMENT_CAPTION;
    default:
      return CUSTOM_CAPTION;
  }
}
function generateTypeFromString(value, originalTypeName) {
  let { inferredType, ast } = inspectValue(value), { type } = inferredType, short, compact, full;
  switch (type) {
    case "Identifier" /* IDENTIFIER */:
    case "Literal" /* LITERAL */:
      short = value, compact = value;
      break;
    case "Object" /* OBJECT */: {
      let { depth } = inferredType;
      short = OBJECT_CAPTION, compact = depth === 1 ? prettyObject(ast, !0) : null, full = prettyObject(ast);
      break;
    }
    case "Element" /* ELEMENT */: {
      let { identifier } = inferredType;
      short = identifier != null && !isHtmlTag(identifier) ? identifier : ELEMENT_CAPTION, compact = splitIntoLines(value).length === 1 ? value : null, full = value;
      break;
    }
    case "Array" /* ARRAY */: {
      let { depth } = inferredType;
      short = ARRAY_CAPTION, compact = depth <= 2 ? prettyArray(ast, !0) : null, full = prettyArray(ast);
      break;
    }
    default:
      short = getCaptionForInspectionType(type), compact = splitIntoLines(value).length === 1 ? value : null, full = value;
      break;
  }
  return createTypeDef({
    name: originalTypeName,
    short,
    compact,
    full,
    inferredType: type
  });
}
function generateCustom({ raw }) {
  return raw != null ? generateTypeFromString(raw, "custom" /* CUSTOM */) : createTypeDef({
    name: "custom" /* CUSTOM */,
    short: CUSTOM_CAPTION,
    compact: CUSTOM_CAPTION
  });
}
function generateFunc2(extractedProp) {
  let { jsDocTags } = extractedProp;
  return jsDocTags != null && (jsDocTags.params != null || jsDocTags.returns != null) ? createTypeDef({
    name: "func" /* FUNC */,
    // @ts-expect-error (Converted from ts-ignore)
    short: generateShortFuncSignature(jsDocTags.params, jsDocTags.returns),
    compact: null,
    // @ts-expect-error (Converted from ts-ignore)
    full: generateFuncSignature(jsDocTags.params, jsDocTags.returns)
  }) : createTypeDef({
    name: "func" /* FUNC */,
    short: FUNCTION_CAPTION,
    compact: FUNCTION_CAPTION
  });
}
function generateShape(type, extractedProp) {
  let fields = Object.keys(type.value).map((key) => `${key}: ${generateType(type.value[key], extractedProp).full}`).join(", "), { inferredType, ast } = inspectValue(`{ ${fields} }`), { depth } = inferredType;
  return createTypeDef({
    name: "shape" /* SHAPE */,
    short: OBJECT_CAPTION,
    compact: depth === 1 && ast ? prettyObject(ast, !0) : null,
    full: ast ? prettyObject(ast) : null
  });
}
function objectOf(of) {
  return `objectOf(${of})`;
}
function generateObjectOf(type, extractedProp) {
  let { short, compact, full } = generateType(type.value, extractedProp);
  return createTypeDef({
    name: "objectOf" /* OBJECTOF */,
    short: objectOf(short),
    compact: compact != null ? objectOf(compact) : null,
    full: full && objectOf(full)
  });
}
function generateUnion(type, extractedProp) {
  if (Array.isArray(type.value)) {
    let values = type.value.reduce(
      (acc, v) => {
        let { short, compact, full } = generateType(v, extractedProp);
        return acc.short.push(short), acc.compact.push(compact), acc.full.push(full), acc;
      },
      { short: [], compact: [], full: [] }
    );
    return createTypeDef({
      name: "union" /* UNION */,
      short: values.short.join(" | "),
      compact: values.compact.every((x) => x != null) ? values.compact.join(" | ") : null,
      full: values.full.join(" | ")
    });
  }
  return createTypeDef({ name: "union" /* UNION */, short: type.value, compact: null });
}
function generateEnumValue({ value, computed }) {
  return computed ? generateTypeFromString(value, "enumvalue") : createTypeDef({ name: "enumvalue", short: value, compact: value });
}
function generateEnum(type) {
  if (Array.isArray(type.value)) {
    let values = type.value.reduce(
      (acc, v) => {
        let { short, compact, full } = generateEnumValue(v);
        return acc.short.push(short), acc.compact.push(compact), acc.full.push(full), acc;
      },
      { short: [], compact: [], full: [] }
    );
    return createTypeDef({
      name: "enum" /* ENUM */,
      short: values.short.join(" | "),
      compact: values.compact.every((x) => x != null) ? values.compact.join(" | ") : null,
      full: values.full.join(" | ")
    });
  }
  return createTypeDef({ name: "enum" /* ENUM */, short: type.value, compact: type.value });
}
function braceAfter(of) {
  return `${of}[]`;
}
function braceAround(of) {
  return `[${of}]`;
}
function createArrayOfObjectTypeDef(short, compact, full) {
  return createTypeDef({
    name: "arrayOf" /* ARRAYOF */,
    short: braceAfter(short),
    compact: compact != null ? braceAround(compact) : null,
    full: full && braceAround(full)
  });
}
function generateArray2(type, extractedProp) {
  let { name, short, compact, full, inferredType } = generateType(type.value, extractedProp);
  if (name === "custom" /* CUSTOM */) {
    if (inferredType === "Object" /* OBJECT */)
      return createArrayOfObjectTypeDef(short, compact, full);
  } else if (name === "shape" /* SHAPE */)
    return createArrayOfObjectTypeDef(short, compact, full);
  return createTypeDef({
    name: "arrayOf" /* ARRAYOF */,
    short: braceAfter(short),
    compact: braceAfter(short)
  });
}
function generateType(type, extractedProp) {
  try {
    switch (type.name) {
      case "custom" /* CUSTOM */:
        return generateCustom(type);
      case "func" /* FUNC */:
        return generateFunc2(extractedProp);
      case "shape" /* SHAPE */:
        return generateShape(type, extractedProp);
      case "instanceOf" /* INSTANCEOF */:
        return createTypeDef({
          name: "instanceOf" /* INSTANCEOF */,
          short: type.value,
          compact: type.value
        });
      case "objectOf" /* OBJECTOF */:
        return generateObjectOf(type, extractedProp);
      case "union" /* UNION */:
        return generateUnion(type, extractedProp);
      case "enum" /* ENUM */:
        return generateEnum(type);
      case "arrayOf" /* ARRAYOF */:
        return generateArray2(type, extractedProp);
      default:
        return createTypeDef({ name: type.name, short: type.name, compact: type.name });
    }
  } catch (e) {
    console.error(e);
  }
  return createTypeDef({ name: "unknown", short: "unknown", compact: "unknown" });
}
function createType(extractedProp) {
  let { type } = extractedProp.docgenInfo;
  if (type == null)
    return null;
  try {
    switch (type.name) {
      case "custom" /* CUSTOM */:
      case "shape" /* SHAPE */:
      case "instanceOf" /* INSTANCEOF */:
      case "objectOf" /* OBJECTOF */:
      case "union" /* UNION */:
      case "enum" /* ENUM */:
      case "arrayOf" /* ARRAYOF */: {
        let { short, compact, full } = generateType(type, extractedProp);
        return compact != null && !(0,chunk_OA6XKWIN/* isTooLongForTypeSummary */.i3)(compact) ? (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(compact) : full ? (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(short, full) : (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(short);
      }
      case "func" /* FUNC */: {
        let { short, full } = generateType(type, extractedProp), summary = short, detail;
        return full && full.length < MAX_FUNC_LENGTH ? summary = full : full && (detail = toMultilineSignature(full)), (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(summary, detail);
      }
      default:
        return null;
    }
  } catch (e) {
    console.error(e);
  }
  return null;
}

// src/docs/propTypes/rawDefaultPropResolvers.ts

var funcResolver = (rawDefaultProp, { name, type }) => {
  let isElement = type?.summary === "element" || type?.summary === "elementType", funcName = extractFunctionName(rawDefaultProp, name);
  if (funcName != null) {
    if (isElement)
      return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(getPrettyElementIdentifier(funcName));
    let { hasParams } = inspectValue(rawDefaultProp.toString()).inferredType;
    return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(getPrettyFuncIdentifier(funcName, hasParams));
  }
  return (0,chunk_OA6XKWIN/* createSummaryValue */.Ux)(isElement ? ELEMENT_CAPTION : FUNCTION_CAPTION);
}, rawDefaultPropTypeResolvers = createTypeResolvers({
  function: funcResolver
});

// src/docs/propTypes/sortProps.ts
function keepOriginalDefinitionOrder(extractedProps, component) {
  let { propTypes } = component;
  return propTypes != null ? Object.keys(propTypes).map((x) => extractedProps.find((y) => y.name === x)).filter(Boolean) : extractedProps;
}

// src/docs/propTypes/handleProp.ts
function enhancePropTypesProp(extractedProp, rawDefaultProp) {
  let { propDef } = extractedProp, newtype = createType(extractedProp);
  newtype != null && (propDef.type = newtype);
  let { defaultValue } = extractedProp.docgenInfo;
  if (defaultValue != null && defaultValue.value != null) {
    let newDefaultValue = createDefaultValue(defaultValue.value);
    newDefaultValue != null && (propDef.defaultValue = newDefaultValue);
  } else if (rawDefaultProp != null) {
    let newDefaultValue = createDefaultValueFromRawDefaultProp(
      rawDefaultProp,
      propDef,
      rawDefaultPropTypeResolvers
    );
    newDefaultValue != null && (propDef.defaultValue = newDefaultValue);
  }
  return propDef;
}
function enhancePropTypesProps(extractedProps, component) {
  let rawDefaultProps = component.defaultProps != null ? component.defaultProps : {}, enhancedProps = extractedProps.map(
    (x) => enhancePropTypesProp(x, rawDefaultProps[x.propDef.name])
  );
  return keepOriginalDefinitionOrder(enhancedProps, component);
}

// src/docs/typeScript/handleProp.ts
function enhanceTypeScriptProp(extractedProp, rawDefaultProp) {
  let { propDef } = extractedProp, { defaultValue } = extractedProp.docgenInfo;
  if (defaultValue != null && defaultValue.value != null) {
    let newDefaultValue = createDefaultValue(defaultValue.value);
    newDefaultValue != null && (propDef.defaultValue = newDefaultValue);
  } else if (rawDefaultProp != null) {
    let newDefaultValue = createDefaultValueFromRawDefaultProp(rawDefaultProp, propDef);
    newDefaultValue != null && (propDef.defaultValue = newDefaultValue);
  }
  return propDef;
}
function enhanceTypeScriptProps(extractedProps) {
  return extractedProps.map((prop) => enhanceTypeScriptProp(prop));
}

// src/extractProps.ts
function getPropDefs(component, section) {
  let processedComponent = component;
  !(0,chunk_OA6XKWIN/* hasDocgen */.TQ)(component) && !component.propTypes && (0,chunk_B3FR4PBN/* isMemo */.Rf)(component) && (processedComponent = component.type);
  let extractedProps = (0,chunk_OA6XKWIN/* extractComponentProps */.p6)(processedComponent, section);
  if (extractedProps.length === 0)
    return [];
  switch (extractedProps[0].typeSystem) {
    case chunk_OA6XKWIN/* TypeSystem */.YF.JAVASCRIPT:
      return enhancePropTypesProps(extractedProps, component);
    case chunk_OA6XKWIN/* TypeSystem */.YF.TYPESCRIPT:
      return enhanceTypeScriptProps(extractedProps);
    default:
      return extractedProps.map((x) => x.propDef);
  }
}
var extractProps = (component) => ({
  rows: getPropDefs(component, "props")
});

// src/extractArgTypes.ts
var extractArgTypes = (component) => {
  if (component) {
    let { rows } = extractProps(component);
    if (rows)
      return rows.reduce((acc, row) => {
        let {
          name,
          description,
          type,
          sbType,
          defaultValue: defaultSummary,
          jsDocTags,
          required
        } = row;
        return acc[name] = {
          name,
          description,
          type: { required, ...sbType },
          table: {
            type: type ?? void 0,
            jsDocTags,
            defaultValue: defaultSummary ?? void 0
          }
        }, acc;
      }, {});
  }
  return null;
};

// src/entry-preview-argtypes.ts
var parameters = {
  docs: {
    extractArgTypes,
    extractComponentDescription: chunk_OA6XKWIN/* extractComponentDescription */.rl
  }
}, argTypesEnhancers = [chunk_OA6XKWIN/* enhanceArgTypes */.C2];



;// ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/entry-preview-argtypes.js






/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/entry-preview-docs.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  applyDecorators: () => (/* reexport */ applyDecorators2),
  decorators: () => (/* reexport */ decorators),
  parameters: () => (/* reexport */ parameters)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-DDIRQRCA.js
var chunk_DDIRQRCA = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-DDIRQRCA.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-B3FR4PBN.js
var chunk_B3FR4PBN = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-B3FR4PBN.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js
var chunk_UAWMPV5J = __webpack_require__("../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-UAWMPV5J.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_CLIENT_LOGGER__"
var external_STORYBOOK_MODULE_CLIENT_LOGGER_ = __webpack_require__("storybook/internal/client-logger");
// EXTERNAL MODULE: ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-OA6XKWIN.js
var chunk_OA6XKWIN = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-OA6XKWIN.js");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_PREVIEW_API__"
var external_STORYBOOK_MODULE_PREVIEW_API_ = __webpack_require__("storybook/preview-api");
;// ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/_browser-chunks/chunk-CS62LUFX.js




// src/entry-preview-docs.ts
var entry_preview_docs_exports = {};
(0,chunk_UAWMPV5J/* __export */.VA)(entry_preview_docs_exports, {
  applyDecorators: () => applyDecorators2,
  decorators: () => decorators,
  parameters: () => parameters
});

// src/docs/jsxDecorator.tsx




var reactElementToJSXString = chunk_B3FR4PBN/* reactElementToJsxString */.HA, toPascalCase = (str) => str.charAt(0).toUpperCase() + str.slice(1), getReactSymbolName = (elementType) => (elementType.$$typeof || elementType).toString().replace(/^Symbol\((.*)\)$/, "$1").split(".").map((segment) => segment.split("_").map(toPascalCase).join("")).join(".");
function simplifyNodeForStringify(node) {
  if ((0,react.isValidElement)(node)) {
    let props = Object.keys(node.props).reduce((acc, cur) => (acc[cur] = simplifyNodeForStringify(node.props[cur]), acc), {});
    return {
      ...node,
      props,
      // @ts-expect-error (this is an internal or removed api)
      _owner: null
    };
  }
  return Array.isArray(node) ? node.map(simplifyNodeForStringify) : node;
}
var renderJsx = (code, options) => {
  if (typeof code > "u")
    return external_STORYBOOK_MODULE_CLIENT_LOGGER_.logger.warn("Too many skip or undefined component"), null;
  let renderedJSX = code, Type = renderedJSX.type;
  for (let i = 0; i < options?.skip; i += 1) {
    if (typeof renderedJSX > "u")
      return external_STORYBOOK_MODULE_CLIENT_LOGGER_.logger.warn("Cannot skip undefined element"), null;
    if (react.Children.count(renderedJSX) > 1)
      return external_STORYBOOK_MODULE_CLIENT_LOGGER_.logger.warn("Trying to skip an array of elements"), null;
    typeof renderedJSX.props.children > "u" ? (external_STORYBOOK_MODULE_CLIENT_LOGGER_.logger.warn("Not enough children to skip elements."), typeof renderedJSX.type == "function" && renderedJSX.type.name === "" && (renderedJSX = react.createElement(Type, { ...renderedJSX.props }))) : typeof renderedJSX.props.children == "function" ? renderedJSX = renderedJSX.props.children() : renderedJSX = renderedJSX.props.children;
  }
  let displayNameDefaults;
  typeof options?.displayName == "string" ? displayNameDefaults = { showFunctions: !0, displayName: () => options.displayName } : displayNameDefaults = {
    // To get exotic component names resolving properly
    displayName: (el) => {
      if (el.type.displayName)
        return el.type.displayName;
      if ((0,chunk_OA6XKWIN/* getDocgenSection */.UO)(el.type, "displayName"))
        return (0,chunk_OA6XKWIN/* getDocgenSection */.UO)(el.type, "displayName");
      if (el.type.render?.displayName)
        return el.type.render.displayName;
      if (typeof el.type == "symbol" || el.type.$$typeof && typeof el.type.$$typeof == "symbol")
        return getReactSymbolName(el.type);
      if (el.type.name && el.type.name !== "_default")
        return el.type.name;
      if (typeof el.type == "function") {
        let parent = options?.parentComponent;
        if (parent) {
          for (let key of Object.keys(parent))
            if (/^[A-Z]/.test(key) && parent[key] === el.type) {
              let parentName = parent.displayName || parent.name || "";
              return parentName ? `${parentName}.${key}` : key;
            }
        }
        return "No Display Name";
      } else return (0,chunk_B3FR4PBN/* isForwardRef */.Jz)(el.type) ? el.type.render.name : (0,chunk_B3FR4PBN/* isMemo */.Rf)(el.type) ? el.type.type.name : el.type;
    }
  };
  let opts = {
    ...displayNameDefaults,
    ...{
      filterProps: (value, key) => value !== void 0
    },
    ...options
  };
  return react.Children.map(code, (c) => {
    let child = typeof c == "number" ? c.toString() : c, string = (typeof reactElementToJSXString == "function" ? reactElementToJSXString : (
      // @ts-expect-error (Converted from ts-ignore)
      reactElementToJSXString.default
    ))(simplifyNodeForStringify(child), opts);
    if (string.indexOf("&quot;") > -1) {
      let matches = string.match(/\S+=\\"([^"]*)\\"/g);
      matches && matches.forEach((match) => {
        string = string.replace(match, match.replace(/&quot;/g, "'"));
      });
    }
    return string;
  }).join(`
`).replace(/function\s+noRefCheck\(\)\s*\{\}/g, "() => {}");
}, defaultOpts = {
  skip: 0,
  showFunctions: !1,
  enableBeautify: !0,
  showDefaultProps: !1
}, skipJsxRender = (context) => {
  let sourceParams = context?.parameters.docs?.source, isArgsStory = context?.parameters.__isArgsStory;
  return context?.parameters.__isPortableStory ? !0 : sourceParams?.type === chunk_OA6XKWIN/* SourceType */.Y1.DYNAMIC ? !1 : !isArgsStory || sourceParams?.code || sourceParams?.type === chunk_OA6XKWIN/* SourceType */.Y1.CODE;
}, isMdx = (node) => node.type?.displayName === "MDXCreateElement" && !!node.props?.mdxType, mdxToJsx = (node) => {
  if (!isMdx(node))
    return node;
  let { mdxType, originalType, children, ...rest } = node.props, jsxChildren = [];
  return children && (jsxChildren = (Array.isArray(children) ? children : [children]).map(mdxToJsx)), (0,react.createElement)(originalType, rest, ...jsxChildren);
}, jsxDecorator = (storyFn, context) => {
  let jsx = (0,external_STORYBOOK_MODULE_PREVIEW_API_.useRef)(void 0), story = storyFn(), skip = skipJsxRender(context), options = {
    ...defaultOpts,
    ...context?.parameters.jsx || {},
    parentComponent: context?.component
  }, storyJsx = context.originalStoryFn(context.args, context);
  return (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => {
    if (skip)
      return;
    let sourceJsx = mdxToJsx(storyJsx), rendered = renderJsx(sourceJsx, options);
    rendered && jsx.current !== rendered && ((0,external_STORYBOOK_MODULE_PREVIEW_API_.emitTransformCode)(rendered, context), jsx.current = rendered);
  }), story;
};

// src/docs/applyDecorators.ts
var applyDecorators2 = (storyFn, decorators2) => {
  let jsxIndex = decorators2.findIndex((d) => d.originalFn === jsxDecorator), reorderedDecorators = jsxIndex === -1 ? decorators2 : [...decorators2.splice(jsxIndex, 1), ...decorators2];
  return (0,chunk_DDIRQRCA/* applyDecorators */.t)(storyFn, reorderedDecorators);
};

// src/entry-preview-docs.ts
var useStaticServiceSnippets = "FEATURES" in globalThis && globalThis?.FEATURES?.experimentalDocgenServer, useCompileTimeSnippets = "FEATURES" in globalThis && globalThis?.FEATURES?.experimentalCodeExamples, decorators = useStaticServiceSnippets || useCompileTimeSnippets ? [] : [jsxDecorator], parameters = {
  docs: {
    story: { inline: !0 }
  }
};



;// ../../node_modules/.pnpm/@storybook+react@10.5.10_@t_5e61ce776bf95b0a37f4d3b03e7f13f7/node_modules/@storybook/react/dist/entry-preview-docs.js







/***/ }),

/***/ "../../node_modules/.pnpm/react@18.3.1/node_modules/react/cjs/react.production.min.js":
/***/ ((__unused_webpack_module, exports) => {

/**
 * @license React
 * react.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var l=Symbol.for("react.element"),n=Symbol.for("react.portal"),p=Symbol.for("react.fragment"),q=Symbol.for("react.strict_mode"),r=Symbol.for("react.profiler"),t=Symbol.for("react.provider"),u=Symbol.for("react.context"),v=Symbol.for("react.forward_ref"),w=Symbol.for("react.suspense"),x=Symbol.for("react.memo"),y=Symbol.for("react.lazy"),z=Symbol.iterator;function A(a){if(null===a||"object"!==typeof a)return null;a=z&&a[z]||a["@@iterator"];return"function"===typeof a?a:null}
var B={isMounted:function(){return!1},enqueueForceUpdate:function(){},enqueueReplaceState:function(){},enqueueSetState:function(){}},C=Object.assign,D={};function E(a,b,e){this.props=a;this.context=b;this.refs=D;this.updater=e||B}E.prototype.isReactComponent={};
E.prototype.setState=function(a,b){if("object"!==typeof a&&"function"!==typeof a&&null!=a)throw Error("setState(...): takes an object of state variables to update or a function which returns an object of state variables.");this.updater.enqueueSetState(this,a,b,"setState")};E.prototype.forceUpdate=function(a){this.updater.enqueueForceUpdate(this,a,"forceUpdate")};function F(){}F.prototype=E.prototype;function G(a,b,e){this.props=a;this.context=b;this.refs=D;this.updater=e||B}var H=G.prototype=new F;
H.constructor=G;C(H,E.prototype);H.isPureReactComponent=!0;var I=Array.isArray,J=Object.prototype.hasOwnProperty,K={current:null},L={key:!0,ref:!0,__self:!0,__source:!0};
function M(a,b,e){var d,c={},k=null,h=null;if(null!=b)for(d in void 0!==b.ref&&(h=b.ref),void 0!==b.key&&(k=""+b.key),b)J.call(b,d)&&!L.hasOwnProperty(d)&&(c[d]=b[d]);var g=arguments.length-2;if(1===g)c.children=e;else if(1<g){for(var f=Array(g),m=0;m<g;m++)f[m]=arguments[m+2];c.children=f}if(a&&a.defaultProps)for(d in g=a.defaultProps,g)void 0===c[d]&&(c[d]=g[d]);return{$$typeof:l,type:a,key:k,ref:h,props:c,_owner:K.current}}
function N(a,b){return{$$typeof:l,type:a.type,key:b,ref:a.ref,props:a.props,_owner:a._owner}}function O(a){return"object"===typeof a&&null!==a&&a.$$typeof===l}function escape(a){var b={"=":"=0",":":"=2"};return"$"+a.replace(/[=:]/g,function(a){return b[a]})}var P=/\/+/g;function Q(a,b){return"object"===typeof a&&null!==a&&null!=a.key?escape(""+a.key):b.toString(36)}
function R(a,b,e,d,c){var k=typeof a;if("undefined"===k||"boolean"===k)a=null;var h=!1;if(null===a)h=!0;else switch(k){case "string":case "number":h=!0;break;case "object":switch(a.$$typeof){case l:case n:h=!0}}if(h)return h=a,c=c(h),a=""===d?"."+Q(h,0):d,I(c)?(e="",null!=a&&(e=a.replace(P,"$&/")+"/"),R(c,b,e,"",function(a){return a})):null!=c&&(O(c)&&(c=N(c,e+(!c.key||h&&h.key===c.key?"":(""+c.key).replace(P,"$&/")+"/")+a)),b.push(c)),1;h=0;d=""===d?".":d+":";if(I(a))for(var g=0;g<a.length;g++){k=
a[g];var f=d+Q(k,g);h+=R(k,b,e,f,c)}else if(f=A(a),"function"===typeof f)for(a=f.call(a),g=0;!(k=a.next()).done;)k=k.value,f=d+Q(k,g++),h+=R(k,b,e,f,c);else if("object"===k)throw b=String(a),Error("Objects are not valid as a React child (found: "+("[object Object]"===b?"object with keys {"+Object.keys(a).join(", ")+"}":b)+"). If you meant to render a collection of children, use an array instead.");return h}
function S(a,b,e){if(null==a)return a;var d=[],c=0;R(a,d,"","",function(a){return b.call(e,a,c++)});return d}function T(a){if(-1===a._status){var b=a._result;b=b();b.then(function(b){if(0===a._status||-1===a._status)a._status=1,a._result=b},function(b){if(0===a._status||-1===a._status)a._status=2,a._result=b});-1===a._status&&(a._status=0,a._result=b)}if(1===a._status)return a._result.default;throw a._result;}
var U={current:null},V={transition:null},W={ReactCurrentDispatcher:U,ReactCurrentBatchConfig:V,ReactCurrentOwner:K};function X(){throw Error("act(...) is not supported in production builds of React.");}
exports.Children={map:S,forEach:function(a,b,e){S(a,function(){b.apply(this,arguments)},e)},count:function(a){var b=0;S(a,function(){b++});return b},toArray:function(a){return S(a,function(a){return a})||[]},only:function(a){if(!O(a))throw Error("React.Children.only expected to receive a single React element child.");return a}};exports.Component=E;exports.Fragment=p;exports.Profiler=r;exports.PureComponent=G;exports.StrictMode=q;exports.Suspense=w;
exports.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED=W;exports.act=X;
exports.cloneElement=function(a,b,e){if(null===a||void 0===a)throw Error("React.cloneElement(...): The argument must be a React element, but you passed "+a+".");var d=C({},a.props),c=a.key,k=a.ref,h=a._owner;if(null!=b){void 0!==b.ref&&(k=b.ref,h=K.current);void 0!==b.key&&(c=""+b.key);if(a.type&&a.type.defaultProps)var g=a.type.defaultProps;for(f in b)J.call(b,f)&&!L.hasOwnProperty(f)&&(d[f]=void 0===b[f]&&void 0!==g?g[f]:b[f])}var f=arguments.length-2;if(1===f)d.children=e;else if(1<f){g=Array(f);
for(var m=0;m<f;m++)g[m]=arguments[m+2];d.children=g}return{$$typeof:l,type:a.type,key:c,ref:k,props:d,_owner:h}};exports.createContext=function(a){a={$$typeof:u,_currentValue:a,_currentValue2:a,_threadCount:0,Provider:null,Consumer:null,_defaultValue:null,_globalName:null};a.Provider={$$typeof:t,_context:a};return a.Consumer=a};exports.createElement=M;exports.createFactory=function(a){var b=M.bind(null,a);b.type=a;return b};exports.createRef=function(){return{current:null}};
exports.forwardRef=function(a){return{$$typeof:v,render:a}};exports.isValidElement=O;exports.lazy=function(a){return{$$typeof:y,_payload:{_status:-1,_result:a},_init:T}};exports.memo=function(a,b){return{$$typeof:x,type:a,compare:void 0===b?null:b}};exports.startTransition=function(a){var b=V.transition;V.transition={};try{a()}finally{V.transition=b}};exports.unstable_act=X;exports.useCallback=function(a,b){return U.current.useCallback(a,b)};exports.useContext=function(a){return U.current.useContext(a)};
exports.useDebugValue=function(){};exports.useDeferredValue=function(a){return U.current.useDeferredValue(a)};exports.useEffect=function(a,b){return U.current.useEffect(a,b)};exports.useId=function(){return U.current.useId()};exports.useImperativeHandle=function(a,b,e){return U.current.useImperativeHandle(a,b,e)};exports.useInsertionEffect=function(a,b){return U.current.useInsertionEffect(a,b)};exports.useLayoutEffect=function(a,b){return U.current.useLayoutEffect(a,b)};
exports.useMemo=function(a,b){return U.current.useMemo(a,b)};exports.useReducer=function(a,b,e){return U.current.useReducer(a,b,e)};exports.useRef=function(a){return U.current.useRef(a)};exports.useState=function(a){return U.current.useState(a)};exports.useSyncExternalStore=function(a,b,e){return U.current.useSyncExternalStore(a,b,e)};exports.useTransition=function(){return U.current.useTransition()};exports.version="18.3.1";


/***/ }),

/***/ "../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/cjs/react.production.min.js");
} else {}


/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-2HVKLHKJ.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   V: () => (/* binding */ StorybookError)
/* harmony export */ });
// src/storybook-error.ts
function parseErrorCode({
  code,
  category
}) {
  let paddedCode = String(code).padStart(4, "0");
  return `SB_${category}_${paddedCode}`;
}
function appendErrorRef(url) {
  if (/^(?!.*storybook\.js\.org)|[?&]ref=error\b/.test(url))
    return url;
  try {
    let urlObj = new URL(url);
    return urlObj.searchParams.set("ref", "error"), urlObj.toString();
  } catch {
    return url;
  }
}
var StorybookError = class _StorybookError extends Error {
  constructor(props) {
    super(
      _StorybookError.getFullMessage(props),
      props.cause === void 0 ? void 0 : { cause: props.cause }
    );
    /**
     * Data associated with the error. Used to provide additional information in the error message or
     * to be passed to telemetry.
     */
    this.data = {};
    /** Flag used to easily determine if the error originates from Storybook. */
    this.fromStorybook = !0;
    /**
     * Flag used to determine if the error is handled by us and should therefore not be shown to the
     * user.
     */
    this.isHandledError = !1;
    /**
     * A collection of sub errors which relate to a parent error.
     *
     * Sub-errors are used to represent multiple related errors that occurred together. When a
     * StorybookError with sub-errors is sent to telemetry, both the parent error and each sub-error
     * are sent as separate telemetry events. This allows for better error tracking and debugging.
     *
     * @example
     *
     * ```ts
     * const error1 = new SomeError();
     * const error2 = new AnotherError();
     * const parentError = new ParentError({
     *   // ... other props
     *   subErrors: [error1, error2],
     * });
     * ```
     */
    this.subErrors = [];
    this.category = props.category, this.documentation = props.documentation ?? !1, this.code = props.code, this.isHandledError = props.isHandledError ?? !1, this.name = props.name, this.subErrors = props.subErrors ?? [];
  }
  get fullErrorCode() {
    return parseErrorCode({ code: this.code, category: this.category });
  }
  /** Overrides the default `Error.name` property in the format: SB_<CATEGORY>_<CODE>. */
  get name() {
    let errorName = this._name || this.constructor.name;
    return `${this.fullErrorCode} (${errorName})`;
  }
  set name(name) {
    this._name = name;
  }
  /** Generates the error message along with additional documentation link (if applicable). */
  static getFullMessage({
    documentation,
    code,
    category,
    message
  }) {
    let page;
    return documentation === !0 ? page = `https://storybook.js.org/error/${parseErrorCode({ code, category })}?ref=error` : typeof documentation == "string" ? page = appendErrorRef(documentation) : Array.isArray(documentation) && (page = `
${documentation.map((doc) => `	- ${appendErrorRef(doc)}`).join(`
`)}`), `${message}${page != null ? `

More info: ${page}
` : ""}`;
  }
};




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-3LY4VQVK.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ dedent)
/* harmony export */ });
// ../../node_modules/ts-dedent/esm/index.js
function dedent(templ) {
  for (var values = [], _i = 1; _i < arguments.length; _i++)
    values[_i - 1] = arguments[_i];
  var strings = Array.from(typeof templ == "string" ? [templ] : templ);
  strings[strings.length - 1] = strings[strings.length - 1].replace(/\r?\n([\t ]*)$/, "");
  var indentLengths = strings.reduce(function(arr, str) {
    var matches = str.match(/\n([\t ]+|(?!\s).)/g);
    return matches ? arr.concat(matches.map(function(match) {
      var _a, _b;
      return (_b = (_a = match.match(/[\t ]/g)) === null || _a === void 0 ? void 0 : _a.length) !== null && _b !== void 0 ? _b : 0;
    })) : arr;
  }, []);
  if (indentLengths.length) {
    var pattern_1 = new RegExp(`
[	 ]{` + Math.min.apply(Math, indentLengths) + "}", "g");
    strings = strings.map(function(str) {
      return str.replace(pattern_1, `
`);
    });
  }
  strings[0] = strings[0].replace(/^\r?\n/, "");
  var string = strings[0];
  return values.forEach(function(value, i) {
    var endentations = string.match(/(?:^|\n)( *)$/), endentation = endentations ? endentations[1] : "", indentedValue = value;
    typeof value == "string" && value.includes(`
`) && (indentedValue = String(value).split(`
`).map(function(str, i2) {
      return i2 === 0 ? str : "" + endentation + str;
    }).join(`
`)), string += indentedValue + strings[i + 1];
  }), string;
}




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-5GYAEUAU.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LG: () => (/* binding */ mapValues),
/* harmony export */   Up: () => (/* binding */ pick),
/* harmony export */   XQ: () => (/* binding */ mergeWith),
/* harmony export */   cJ: () => (/* binding */ omit),
/* harmony export */   fN: () => (/* binding */ pickBy),
/* harmony export */   kR: () => (/* binding */ toMerged)
/* harmony export */ });
/* unused harmony export cloneDeep */
/* harmony import */ var _chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-Z3B64JMR.js");


// ../../node_modules/es-toolkit/dist/object/mapValues.mjs
function mapValues(object, getNewValue) {
  let result = {}, keys = Object.keys(object);
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i], value = object[key];
    result[key] = getNewValue(value, key, object);
  }
  return result;
}

// ../../node_modules/es-toolkit/dist/_internal/isUnsafeProperty.mjs
function isUnsafeProperty(key) {
  return key === "__proto__";
}

// ../../node_modules/es-toolkit/dist/object/mergeWith.mjs
function mergeWith(target, source, merge2) {
  let sourceKeys = Object.keys(source);
  for (let i = 0; i < sourceKeys.length; i++) {
    let key = sourceKeys[i];
    if (isUnsafeProperty(key))
      continue;
    let sourceValue = source[key], targetValue = target[key], merged = merge2(targetValue, sourceValue, key, target, source);
    merged !== void 0 ? target[key] = merged : Array.isArray(sourceValue) ? Array.isArray(targetValue) ? target[key] = mergeWith(targetValue, sourceValue, merge2) : target[key] = mergeWith([], sourceValue, merge2) : (0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPlainObject */ .Qd)(sourceValue) ? (0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPlainObject */ .Qd)(targetValue) ? target[key] = mergeWith(targetValue, sourceValue, merge2) : target[key] = mergeWith({}, sourceValue, merge2) : (targetValue === void 0 || sourceValue !== void 0) && (target[key] = sourceValue);
  }
  return target;
}

// ../../node_modules/es-toolkit/dist/object/pick.mjs
function pick(obj, keys) {
  let result = {};
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i];
    Object.hasOwn(obj, key) && (result[key] = obj[key]);
  }
  return result;
}

// ../../node_modules/es-toolkit/dist/object/pickBy.mjs
function pickBy(obj, shouldPick) {
  let result = {}, keys = Object.keys(obj);
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i], value = obj[key];
    shouldPick(value, key) && (result[key] = value);
  }
  return result;
}

// ../../node_modules/es-toolkit/dist/object/clone.mjs
function clone(obj) {
  if ((0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPrimitive */ .sO)(obj))
    return obj;
  if (Array.isArray(obj) || (0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isTypedArray */ .iu)(obj) || obj instanceof ArrayBuffer || typeof SharedArrayBuffer < "u" && obj instanceof SharedArrayBuffer)
    return obj.slice(0);
  let prototype = Object.getPrototypeOf(obj), Constructor = prototype.constructor;
  if (obj instanceof Date || obj instanceof Map || obj instanceof Set)
    return new Constructor(obj);
  if (obj instanceof RegExp) {
    let newRegExp = new Constructor(obj);
    return newRegExp.lastIndex = obj.lastIndex, newRegExp;
  }
  if (obj instanceof DataView)
    return new Constructor(obj.buffer.slice(0));
  if (obj instanceof Error) {
    let newError = new Constructor(obj.message);
    return newError.stack = obj.stack, newError.name = obj.name, newError.cause = obj.cause, newError;
  }
  if (typeof File < "u" && obj instanceof File)
    return new Constructor([obj], obj.name, { type: obj.type, lastModified: obj.lastModified });
  if (typeof obj == "object") {
    let newObject = Object.create(prototype);
    return Object.assign(newObject, obj);
  }
  return obj;
}

// ../../node_modules/es-toolkit/dist/object/cloneDeepWith.mjs
function cloneDeepWithImpl(valueToClone, keyToClone, objectToClone, stack = /* @__PURE__ */ new Map(), cloneValue = void 0) {
  let cloned = cloneValue?.(valueToClone, keyToClone, objectToClone, stack);
  if (cloned !== void 0)
    return cloned;
  if (isPrimitive(valueToClone))
    return valueToClone;
  if (stack.has(valueToClone))
    return stack.get(valueToClone);
  if (Array.isArray(valueToClone)) {
    let result = new Array(valueToClone.length);
    stack.set(valueToClone, result);
    for (let i = 0; i < valueToClone.length; i++)
      result[i] = cloneDeepWithImpl(valueToClone[i], i, objectToClone, stack, cloneValue);
    return Object.hasOwn(valueToClone, "index") && (result.index = valueToClone.index), Object.hasOwn(valueToClone, "input") && (result.input = valueToClone.input), result;
  }
  if (valueToClone instanceof Date)
    return new Date(valueToClone.getTime());
  if (valueToClone instanceof RegExp) {
    let result = new RegExp(valueToClone.source, valueToClone.flags);
    return result.lastIndex = valueToClone.lastIndex, result;
  }
  if (valueToClone instanceof Map) {
    let result = /* @__PURE__ */ new Map();
    stack.set(valueToClone, result);
    for (let [key, value] of valueToClone)
      result.set(key, cloneDeepWithImpl(value, key, objectToClone, stack, cloneValue));
    return result;
  }
  if (valueToClone instanceof Set) {
    let result = /* @__PURE__ */ new Set();
    stack.set(valueToClone, result);
    for (let value of valueToClone)
      result.add(cloneDeepWithImpl(value, void 0, objectToClone, stack, cloneValue));
    return result;
  }
  if (typeof Buffer < "u" && Buffer.isBuffer(valueToClone))
    return valueToClone.subarray();
  if (isTypedArray(valueToClone)) {
    let result = new (Object.getPrototypeOf(valueToClone)).constructor(valueToClone.length);
    stack.set(valueToClone, result);
    for (let i = 0; i < valueToClone.length; i++)
      result[i] = cloneDeepWithImpl(valueToClone[i], i, objectToClone, stack, cloneValue);
    return result;
  }
  if (valueToClone instanceof ArrayBuffer || typeof SharedArrayBuffer < "u" && valueToClone instanceof SharedArrayBuffer)
    return valueToClone.slice(0);
  if (valueToClone instanceof DataView) {
    let result = new DataView(valueToClone.buffer.slice(0), valueToClone.byteOffset, valueToClone.byteLength);
    return stack.set(valueToClone, result), copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  if (typeof File < "u" && valueToClone instanceof File) {
    let result = new File([valueToClone], valueToClone.name, {
      type: valueToClone.type
    });
    return stack.set(valueToClone, result), copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  if (typeof Blob < "u" && valueToClone instanceof Blob) {
    let result = new Blob([valueToClone], { type: valueToClone.type });
    return stack.set(valueToClone, result), copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  if (valueToClone instanceof Error) {
    let result = new valueToClone.constructor();
    return stack.set(valueToClone, result), result.message = valueToClone.message, result.name = valueToClone.name, result.stack = valueToClone.stack, result.cause = valueToClone.cause, copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  if (valueToClone instanceof Boolean) {
    let result = new Boolean(valueToClone.valueOf());
    return stack.set(valueToClone, result), copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  if (valueToClone instanceof Number) {
    let result = new Number(valueToClone.valueOf());
    return stack.set(valueToClone, result), copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  if (valueToClone instanceof String) {
    let result = new String(valueToClone.valueOf());
    return stack.set(valueToClone, result), copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  if (typeof valueToClone == "object" && isCloneableObject(valueToClone)) {
    let result = Object.create(Object.getPrototypeOf(valueToClone));
    return stack.set(valueToClone, result), copyProperties(result, valueToClone, objectToClone, stack, cloneValue), result;
  }
  return valueToClone;
}
function copyProperties(target, source, objectToClone = target, stack, cloneValue) {
  let keys = [...Object.keys(source), ...getSymbols(source)];
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i], descriptor = Object.getOwnPropertyDescriptor(target, key);
    (descriptor == null || descriptor.writable) && (target[key] = cloneDeepWithImpl(source[key], key, objectToClone, stack, cloneValue));
  }
}
function isCloneableObject(object) {
  switch (getTag(object)) {
    case argumentsTag:
    case arrayTag:
    case arrayBufferTag:
    case dataViewTag:
    case booleanTag:
    case dateTag:
    case float32ArrayTag:
    case float64ArrayTag:
    case int8ArrayTag:
    case int16ArrayTag:
    case int32ArrayTag:
    case mapTag:
    case numberTag:
    case objectTag:
    case regexpTag:
    case setTag:
    case stringTag:
    case symbolTag:
    case uint8ArrayTag:
    case uint8ClampedArrayTag:
    case uint16ArrayTag:
    case uint32ArrayTag:
      return !0;
    default:
      return !1;
  }
}

// ../../node_modules/es-toolkit/dist/object/cloneDeep.mjs
function cloneDeep(obj) {
  return cloneDeepWithImpl(obj, void 0, obj, /* @__PURE__ */ new Map(), void 0);
}

// ../../node_modules/es-toolkit/dist/object/omit.mjs
function omit(obj, keys) {
  let result = { ...obj };
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i];
    delete result[key];
  }
  return result;
}

// ../../node_modules/es-toolkit/dist/string/words.mjs
var CASE_SPLIT_PATTERN = new RegExp("\\p{Lu}?\\p{Ll}+|[0-9]+|\\p{Lu}+(?!\\p{Ll})|\\p{Emoji_Presentation}|\\p{Extended_Pictographic}|\\p{L}+", "gu");

// ../../node_modules/es-toolkit/dist/object/toMerged.mjs
function toMerged(target, source) {
  return mergeWith(clone(target), source, function mergeRecursively(targetValue, sourceValue) {
    if (Array.isArray(sourceValue))
      return Array.isArray(targetValue) ? mergeWith(clone(targetValue), sourceValue, mergeRecursively) : mergeWith([], sourceValue, mergeRecursively);
    if ((0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPlainObject */ .Qd)(sourceValue))
      return (0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPlainObject */ .Qd)(targetValue) ? mergeWith(clone(targetValue), sourceValue, mergeRecursively) : mergeWith({}, sourceValue, mergeRecursively);
  });
}




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-FLUL445Z.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _N: () => (/* binding */ getServiceSubcomponentArgTypes),
/* harmony export */   mJ: () => (/* binding */ combineParameters),
/* harmony export */   vs: () => (/* binding */ mergeServiceArgTypes)
/* harmony export */ });
/* unused harmony exports inferArgTypes, filterArgTypes, inferControls */
/* harmony import */ var _chunk_5GYAEUAU_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-5GYAEUAU.js");
/* harmony import */ var _chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-Z3B64JMR.js");
/* harmony import */ var _chunk_3LY4VQVK_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-3LY4VQVK.js");
/* harmony import */ var storybook_internal_client_logger__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("storybook/internal/client-logger");
/* harmony import */ var storybook_internal_client_logger__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(storybook_internal_client_logger__WEBPACK_IMPORTED_MODULE_2__);




// src/preview-api/modules/store/parameters.ts
var combineParameters = (...parameterSets) => {
  let mergeKeys = {}, definedParametersSets = parameterSets.filter(Boolean), combined = definedParametersSets.reduce((acc, parameters) => (Object.entries(parameters).forEach(([key, value]) => {
    let existing = acc[key];
    Array.isArray(value) || typeof existing > "u" ? acc[key] = value : (0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPlainObject */ .Qd)(value) && (0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPlainObject */ .Qd)(existing) ? mergeKeys[key] = !0 : typeof value < "u" && (acc[key] = value);
  }), acc), {});
  return Object.keys(mergeKeys).forEach((key) => {
    let mergeValues = definedParametersSets.filter(Boolean).map((p) => p[key]).filter((value) => typeof value < "u");
    mergeValues.every((value) => (0,_chunk_Z3B64JMR_js__WEBPACK_IMPORTED_MODULE_0__/* .isPlainObject */ .Qd)(value)) ? combined[key] = combineParameters(...mergeValues) : combined[key] = mergeValues[mergeValues.length - 1];
  }), combined;
};

// src/preview-api/modules/store/filterArgTypes.ts
var matches = (name, descriptor) => Array.isArray(descriptor) ? descriptor.includes(name) : name.match(descriptor), filterArgTypes = (argTypes, include, exclude) => !include && !exclude ? argTypes : argTypes && (0,_chunk_5GYAEUAU_js__WEBPACK_IMPORTED_MODULE_1__/* .pickBy */ .fN)(argTypes, (argType, key) => {
  let name = argType.name || key.toString();
  return !!(!include || matches(name, include)) && (!exclude || !matches(name, exclude));
});

// src/preview-api/modules/store/inferControls.ts

var inferControl = (argType, name, matchers) => {
  let { type, options } = argType;
  if (type) {
    if (matchers.color && matchers.color.test(name)) {
      let controlType = type.name;
      if (controlType === "string")
        return { control: { type: "color" } };
      controlType !== "enum" && storybook_internal_client_logger__WEBPACK_IMPORTED_MODULE_2__.logger.warn(
        `Addon controls: Control of type color only supports string, received "${controlType}" instead`
      );
    }
    if (matchers.date && matchers.date.test(name))
      return { control: { type: "date" } };
    switch (type.name) {
      case "array":
        return { control: { type: "object" } };
      case "boolean":
        return { control: { type: "boolean" } };
      case "string":
        return { control: { type: "text" } };
      case "number":
        return { control: { type: "number" } };
      case "enum": {
        let { value } = type;
        return { control: { type: value?.length <= 5 ? "radio" : "select" }, options: value };
      }
      case "function":
      case "symbol":
        return null;
      default:
        return { control: { type: options ? "select" : "object" } };
    }
  }
}, inferControls = (context) => {
  let {
    argTypes,
    parameters: { __isArgsStory, controls: { include = null, exclude = null, matchers = {} } = {} }
  } = context;
  if (!__isArgsStory)
    return argTypes;
  let filteredArgTypes = filterArgTypes(argTypes, include, exclude), withControls = (0,_chunk_5GYAEUAU_js__WEBPACK_IMPORTED_MODULE_1__/* .mapValues */ .LG)(filteredArgTypes, (argType, name) => argType?.type && inferControl(argType, name.toString(), matchers));
  return combineParameters(withControls, filteredArgTypes);
};
inferControls.secondPass = !0;

// src/preview-api/modules/store/inferArgTypes.ts

var inferType = (value, name, visited, cache) => {
  let type = typeof value;
  switch (type) {
    case "boolean":
    case "string":
    case "number":
    case "function":
    case "symbol":
      return { name: type };
    default:
      break;
  }
  if (value) {
    if (cache.has(value))
      return cache.get(value);
    if (visited.has(value))
      return storybook_internal_client_logger__WEBPACK_IMPORTED_MODULE_2__.logger.warn((0,_chunk_3LY4VQVK_js__WEBPACK_IMPORTED_MODULE_3__/* .dedent */ .T)`
        We've detected a cycle in arg '${name}'. Args should be JSON-serializable.

        Consider using the mapping feature or fully custom args:
        - Mapping: https://storybook.js.org/docs/writing-stories/args#mapping-to-complex-arg-values
        - Custom args: https://storybook.js.org/docs/essentials/controls#fully-custom-args
      `), { name: "other", value: "cyclic object" };
    visited.add(value);
    let result;
    return Array.isArray(value) ? result = { name: "array", value: value.length > 0 ? inferType(value[0], name, visited, cache) : { name: "other", value: "unknown" } } : result = { name: "object", value: (0,_chunk_5GYAEUAU_js__WEBPACK_IMPORTED_MODULE_1__/* .mapValues */ .LG)(value, (field) => inferType(field, name, visited, cache)) }, visited.delete(value), cache.set(value, result), result;
  }
  return { name: "object", value: {} };
}, inferArgTypes = (context) => {
  let { id, argTypes: userArgTypes = {}, initialArgs = {} } = context, cache = /* @__PURE__ */ new Map(), argTypes = Object.fromEntries(
    Object.entries(initialArgs).filter(([key]) => !userArgTypes[key]?.type).map(([key, arg]) => [
      key,
      {
        name: key,
        type: inferType(arg, `${id}.${key}`, /* @__PURE__ */ new Set(), cache)
      }
    ])
  ), userArgTypesNames = (0,_chunk_5GYAEUAU_js__WEBPACK_IMPORTED_MODULE_1__/* .mapValues */ .LG)(userArgTypes, (argType, key) => ({
    name: key
  }));
  return combineParameters(argTypes, userArgTypesNames, userArgTypes);
};
inferArgTypes.secondPass = !0;

// src/docs-tools/argTypes/docgenServiceArgTypes.ts
function mergeServiceArgTypes({
  payload,
  storyId,
  parameters,
  initialArgs,
  customArgTypes
}) {
  let merged = combineParameters(payload.argTypes ?? {}, customArgTypes ?? {}), withInferredTypes = inferArgTypes({
    id: storyId,
    argTypes: merged,
    initialArgs: initialArgs ?? {}
  });
  return inferControls({
    argTypes: withInferredTypes,
    // The manager can render this before the preview reports `storyPrepared`, so `parameters` may be
    // undefined; `inferControls` reads `parameters.__isArgsStory` and would throw. An empty object
    // makes it a no-op until prepared parameters arrive and trigger a re-render.
    parameters: parameters ?? {}
  });
}
function getServiceSubcomponentArgTypes(payload) {
  return Object.fromEntries(
    Object.entries(payload.subcomponents ?? {}).flatMap(
      ([name, subcomponent]) => subcomponent.argTypes ? [[name, subcomponent.argTypes]] : []
    )
  );
}




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-IMSF75WX.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   P$: () => (/* binding */ __commonJS),
/* harmony export */   VA: () => (/* binding */ __export),
/* harmony export */   f1: () => (/* binding */ __toESM),
/* harmony export */   ki: () => (/* binding */ __require)
/* harmony export */ });
var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __getProtoOf = Object.getPrototypeOf, __hasOwnProp = Object.prototype.hasOwnProperty;
var __require = /* @__PURE__ */ ((x) =>  true ? __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks sync recursive") : 0)(function(x) {
  if (true) return __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks sync recursive").apply(this, arguments);
  throw Error('Dynamic require of "' + x + '" is not supported');
});
var __commonJS = (cb, mod) => function() {
  try {
    return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
  } catch (e) {
    throw mod = 0, e;
  }
};
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: !0 });
}, __copyProps = (to, from, except, desc) => {
  if (from && typeof from == "object" || typeof from == "function")
    for (let key of __getOwnPropNames(from))
      !__hasOwnProp.call(to, key) && key !== except && __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  return to;
};
var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
  // If the importer is in node compatibility mode or this is not an ESM
  // file that has been converted to a CommonJS file using a Babel-
  // compatible transform (i.e. "__esModule" has not been set), then set
  // "default" to the CommonJS "module.exports" for node compatibility.
  isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: !0 }) : target,
  mod
));




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-KUHYVZJN.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  HC: () => (/* binding */ OpenServiceMissingServiceError),
  Gy: () => (/* binding */ StatusTypeIdMismatchError)
});

// UNUSED EXPORTS: OpenServiceAsyncSchemaError, OpenServiceInvalidStaticPathError, OpenServiceLoadedDrainExceededError, OpenServiceMissingChannelError, OpenServiceRemoteCommandDisconnectedError, OpenServiceRemoteCommandUnhandledError, OpenServiceUnimplementedOperationError, OpenServiceValidationError

// EXTERNAL MODULE: ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-2HVKLHKJ.js
var chunk_2HVKLHKJ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-2HVKLHKJ.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-IMSF75WX.js
var chunk_IMSF75WX = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-IMSF75WX.js");
;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-SC67XZST.js


// ../../node_modules/picocolors/picocolors.browser.js
var require_picocolors_browser = (0,chunk_IMSF75WX/* __commonJS */.P$)({
  "../../node_modules/picocolors/picocolors.browser.js"(exports, module) {
    var x = String, create = function() {
      return { isColorSupported: !1, reset: x, bold: x, dim: x, italic: x, underline: x, inverse: x, hidden: x, strikethrough: x, black: x, red: x, green: x, yellow: x, blue: x, magenta: x, cyan: x, white: x, gray: x, bgBlack: x, bgRed: x, bgGreen: x, bgYellow: x, bgBlue: x, bgMagenta: x, bgCyan: x, bgWhite: x, blackBright: x, redBright: x, greenBright: x, yellowBright: x, blueBright: x, magentaBright: x, cyanBright: x, whiteBright: x, bgBlackBright: x, bgRedBright: x, bgGreenBright: x, bgYellowBright: x, bgBlueBright: x, bgMagentaBright: x, bgCyanBright: x, bgWhiteBright: x };
    };
    module.exports = create();
    module.exports.createColors = create;
  }
});



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-KUHYVZJN.js




// src/server-errors.ts
var import_picocolors = (0,chunk_IMSF75WX/* __toESM */.f1)(require_picocolors_browser(), 1);

// src/shared/open-service/errors.ts
function formatIssuePath(path) {
  return path?.length ? path.map((segment) => {
    let key = typeof segment == "object" && segment !== null && "key" in segment ? segment.key : segment;
    return typeof key == "number" ? `[${key}]` : `.${String(key)}`;
  }).join("").replace(/^\./, "") : "";
}
function formatIssues(issues) {
  return issues.map((issue) => {
    let path = formatIssuePath(issue.path);
    return path === "" ? issue.message : `${path}: ${issue.message}`;
  }).join(`
`);
}

// src/server-errors.ts
var OpenServiceValidationError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceValidationError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 5,
      message: `Invalid ${data.phase} for ${data.kind} "${data.serviceId}.${data.name}":
${formatIssues(
        data.issues
      )}`
    });
    this.data = data;
  }
};
var OpenServiceMissingServiceError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceMissingServiceError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 7,
      message: `No registered service with id "${data.serviceId}" exists in this environment.`
    });
    this.data = data;
  }
}, OpenServiceUnimplementedOperationError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceUnimplementedOperationError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 8,
      message: `${data.kind[0].toUpperCase()}${data.kind.slice(1)} "${data.serviceId}.${data.name}" is not implemented for this environment.`
    });
    this.data = data;
  }
}, OpenServiceInvalidStaticPathError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceInvalidStaticPathError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 10,
      message: `Invalid static path "${data.path}" for query "${data.serviceId}.${data.name}": use a relative path with forward slashes and no ".." segments.`
    });
    this.data = data;
  }
}, OpenServiceAsyncSchemaError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceAsyncSchemaError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 9,
      message: `Async schema for ${data.kind} "${data.serviceId}.${data.name}" (${data.phase}): query input and output schemas must validate synchronously.`
    });
    this.data = data;
  }
}, OpenServiceLoadedDrainExceededError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceLoadedDrainExceededError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 11,
      message: `Query "${data.serviceId}.${data.name}".loaded(...) did not settle after ${data.iterations} drain iterations. Check for handlers that keep discovering new dependencies after every state change.`
    });
    this.data = data;
  }
};
var OpenServiceMissingChannelError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data = {}) {
    super({
      name: "OpenServiceMissingChannelError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 13,
      message: data.serviceId ? `Cannot register service "${data.serviceId}": the Storybook addons channel is not installed in this runtime.` : "The Storybook addons channel is not installed in this runtime."
    });
    this.data = data;
  }
}, OpenServiceRemoteCommandDisconnectedError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceRemoteCommandDisconnectedError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 14,
      message: `Service "${data.serviceId}" was unregistered before a remote command resolved.`
    });
    this.data = data;
  }
}, OpenServiceRemoteCommandUnhandledError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "OpenServiceRemoteCommandUnhandledError",
      category: "CORE-COMMON" /* CORE_COMMON */,
      code: 15,
      message: `No runtime acknowledged remote command "${data.serviceId}.${data.commandName}"; its handler is not implemented in any connected runtime.`
    });
    this.data = data;
  }
};
var StatusTypeIdMismatchError = class extends chunk_2HVKLHKJ/* StorybookError */.V {
  constructor(data) {
    super({
      name: "StatusTypeIdMismatchError",
      category: "CORE-SERVER" /* CORE_SERVER */,
      code: 16,
      message: `Status has typeId "${data.status.typeId}" but was added to store with typeId "${data.typeId}". Full status: ${JSON.stringify(
        data.status,
        null,
        2
      )}`
    });
    this.data = data;
  }
};




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-OA6XKWIN.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   C2: () => (/* binding */ enhanceArgTypes),
/* harmony export */   Op: () => (/* binding */ SNIPPET_RENDERED),
/* harmony export */   Sy: () => (/* binding */ isTooLongForDefaultValueSummary),
/* harmony export */   TQ: () => (/* binding */ hasDocgen),
/* harmony export */   UO: () => (/* binding */ getDocgenSection),
/* harmony export */   Ux: () => (/* binding */ createSummaryValue),
/* harmony export */   Y1: () => (/* binding */ SourceType),
/* harmony export */   YF: () => (/* binding */ TypeSystem),
/* harmony export */   i3: () => (/* binding */ isTooLongForTypeSummary),
/* harmony export */   p6: () => (/* binding */ extractComponentProps),
/* harmony export */   rl: () => (/* binding */ extractComponentDescription)
/* harmony export */ });
/* unused harmony exports convert, isDefaultValueBlacklisted, str, isValidDocgenSection, getDocgenDescription, parseJsDoc, MAX_TYPE_SUMMARY_LENGTH, MAX_DEFAULT_VALUE_SUMMARY_LENGTH, normalizeNewlines, extractComponentSectionArray, extractComponentSectionObject, ADDON_ID, PANEL_ID, PARAM_KEY, shouldSkipStoryDocsEmit, shouldWaitForServiceSnippet */
/* harmony import */ var _chunk_FLUL445Z_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-FLUL445Z.js");
/* harmony import */ var _chunk_5GYAEUAU_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-5GYAEUAU.js");
/* harmony import */ var _chunk_IMSF75WX_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-IMSF75WX.js");
/* harmony import */ var storybook_internal_preview_errors__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("storybook/internal/preview-errors");
/* harmony import */ var storybook_internal_preview_errors__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(storybook_internal_preview_errors__WEBPACK_IMPORTED_MODULE_1__);




// ../../node_modules/jsdoc-type-pratt-parser/dist/index.js
var require_dist = (0,_chunk_IMSF75WX_js__WEBPACK_IMPORTED_MODULE_0__/* .__commonJS */ .P$)({
  "../../node_modules/jsdoc-type-pratt-parser/dist/index.js"(exports, module) {
    (function(global, factory) {
      typeof exports == "object" && typeof module < "u" ? factory(exports) : typeof define == "function" && __webpack_require__.amdO ? define(["exports"], factory) : (global = typeof globalThis < "u" ? globalThis : global || self, factory(global.jtpp = {}));
    })(exports, (function(exports2) {
      "use strict";
      function tokenToString(token) {
        return token.text !== void 0 && token.text !== "" ? `'${token.type}' with value '${token.text}'` : `'${token.type}'`;
      }
      class NoParsletFoundError extends Error {
        constructor(token) {
          super(`No parslet found for token: ${tokenToString(token)}`), this.token = token, Object.setPrototypeOf(this, NoParsletFoundError.prototype);
        }
        getToken() {
          return this.token;
        }
      }
      class EarlyEndOfParseError extends Error {
        constructor(token) {
          super(`The parsing ended early. The next token was: ${tokenToString(token)}`), this.token = token, Object.setPrototypeOf(this, EarlyEndOfParseError.prototype);
        }
        getToken() {
          return this.token;
        }
      }
      class UnexpectedTypeError extends Error {
        constructor(result, message) {
          let error = `Unexpected type: '${result.type}'.`;
          message !== void 0 && (error += ` Message: ${message}`), super(error), Object.setPrototypeOf(this, UnexpectedTypeError.prototype);
        }
      }
      function makePunctuationRule(type) {
        return (text) => text.startsWith(type) ? { type, text: type } : null;
      }
      function getQuoted(text) {
        let position = 0, char, mark = text[0], escaped = !1;
        if (mark !== "'" && mark !== '"')
          return null;
        for (; position < text.length; ) {
          if (position++, char = text[position], !escaped && char === mark) {
            position++;
            break;
          }
          escaped = !escaped && char === "\\";
        }
        if (char !== mark)
          throw new Error("Unterminated String");
        return text.slice(0, position);
      }
      let identifierStartRegex = new RegExp("[$_\\p{ID_Start}]|\\\\u\\p{Hex_Digit}{4}|\\\\u\\{0*(?:\\p{Hex_Digit}{1,5}|10\\p{Hex_Digit}{4})\\}", "u"), identifierContinueRegex = new RegExp("[$\\-\\p{ID_Continue}\\u200C\\u200D]|\\\\u\\p{Hex_Digit}{4}|\\\\u\\{0*(?:\\p{Hex_Digit}{1,5}|10\\p{Hex_Digit}{4})\\}", "u");
      function getIdentifier(text) {
        let char = text[0];
        if (!identifierStartRegex.test(char))
          return null;
        let position = 1;
        do {
          if (char = text[position], !identifierContinueRegex.test(char))
            break;
          position++;
        } while (position < text.length);
        return text.slice(0, position);
      }
      let numberRegex = /^(NaN|-?((\d*\.\d+|\d+)([Ee][+-]?\d+)?|Infinity))/;
      function getNumber(text) {
        var _a, _b;
        return (_b = (_a = numberRegex.exec(text)) === null || _a === void 0 ? void 0 : _a[0]) !== null && _b !== void 0 ? _b : null;
      }
      let identifierRule = (text) => {
        let value = getIdentifier(text);
        return value == null ? null : {
          type: "Identifier",
          text: value
        };
      };
      function makeKeyWordRule(type) {
        return (text) => {
          if (!text.startsWith(type))
            return null;
          let prepends = text[type.length];
          return prepends !== void 0 && identifierContinueRegex.test(prepends) ? null : {
            type,
            text: type
          };
        };
      }
      let stringValueRule = (text) => {
        let value = getQuoted(text);
        return value == null ? null : {
          type: "StringValue",
          text: value
        };
      }, eofRule = (text) => text.length > 0 ? null : {
        type: "EOF",
        text: ""
      }, numberRule = (text) => {
        let value = getNumber(text);
        return value === null ? null : {
          type: "Number",
          text: value
        };
      }, rules = [
        eofRule,
        makePunctuationRule("=>"),
        makePunctuationRule("("),
        makePunctuationRule(")"),
        makePunctuationRule("{"),
        makePunctuationRule("}"),
        makePunctuationRule("["),
        makePunctuationRule("]"),
        makePunctuationRule("|"),
        makePunctuationRule("&"),
        makePunctuationRule("<"),
        makePunctuationRule(">"),
        makePunctuationRule(","),
        makePunctuationRule(";"),
        makePunctuationRule("*"),
        makePunctuationRule("?"),
        makePunctuationRule("!"),
        makePunctuationRule("="),
        makePunctuationRule(":"),
        makePunctuationRule("..."),
        makePunctuationRule("."),
        makePunctuationRule("#"),
        makePunctuationRule("~"),
        makePunctuationRule("/"),
        makePunctuationRule("@"),
        makeKeyWordRule("undefined"),
        makeKeyWordRule("null"),
        makeKeyWordRule("function"),
        makeKeyWordRule("this"),
        makeKeyWordRule("new"),
        makeKeyWordRule("module"),
        makeKeyWordRule("event"),
        makeKeyWordRule("extends"),
        makeKeyWordRule("external"),
        makeKeyWordRule("infer"),
        makeKeyWordRule("typeof"),
        makeKeyWordRule("keyof"),
        makeKeyWordRule("readonly"),
        makeKeyWordRule("import"),
        makeKeyWordRule("is"),
        makeKeyWordRule("in"),
        makeKeyWordRule("asserts"),
        numberRule,
        identifierRule,
        stringValueRule
      ], breakingWhitespaceRegex = /^\s*\n\s*/;
      class Lexer {
        static create(text) {
          let current = this.read(text);
          text = current.text;
          let next = this.read(text);
          return text = next.text, new Lexer(text, void 0, current.token, next.token);
        }
        constructor(text, previous, current, next) {
          this.text = "", this.text = text, this.previous = previous, this.current = current, this.next = next;
        }
        static read(text, startOfLine = !1) {
          startOfLine = startOfLine || breakingWhitespaceRegex.test(text), text = text.trim();
          for (let rule of rules) {
            let partial = rule(text);
            if (partial !== null) {
              let token = Object.assign(Object.assign({}, partial), { startOfLine });
              return text = text.slice(token.text.length), { text, token };
            }
          }
          throw new Error("Unexpected Token " + text);
        }
        advance() {
          let next = Lexer.read(this.text);
          return new Lexer(next.text, this.current, this.next, next.token);
        }
      }
      function assertRootResult(result) {
        if (result === void 0)
          throw new Error("Unexpected undefined");
        if (result.type === "JsdocTypeKeyValue" || result.type === "JsdocTypeParameterList" || result.type === "JsdocTypeProperty" || result.type === "JsdocTypeReadonlyProperty" || result.type === "JsdocTypeObjectField" || result.type === "JsdocTypeJsdocObjectField" || result.type === "JsdocTypeIndexSignature" || result.type === "JsdocTypeMappedType" || result.type === "JsdocTypeTypeParameter")
          throw new UnexpectedTypeError(result);
        return result;
      }
      function assertPlainKeyValueOrRootResult(result) {
        return result.type === "JsdocTypeKeyValue" ? assertPlainKeyValueResult(result) : assertRootResult(result);
      }
      function assertPlainKeyValueOrNameResult(result) {
        return result.type === "JsdocTypeName" ? result : assertPlainKeyValueResult(result);
      }
      function assertPlainKeyValueResult(result) {
        if (result.type !== "JsdocTypeKeyValue")
          throw new UnexpectedTypeError(result);
        return result;
      }
      function assertNumberOrVariadicNameResult(result) {
        var _a;
        if (result.type === "JsdocTypeVariadic") {
          if (((_a = result.element) === null || _a === void 0 ? void 0 : _a.type) === "JsdocTypeName")
            return result;
          throw new UnexpectedTypeError(result);
        }
        if (result.type !== "JsdocTypeNumber" && result.type !== "JsdocTypeName")
          throw new UnexpectedTypeError(result);
        return result;
      }
      function assertArrayOrTupleResult(result) {
        if (result.type === "JsdocTypeTuple" || result.type === "JsdocTypeGeneric" && result.meta.brackets === "square")
          return result;
        throw new UnexpectedTypeError(result);
      }
      function isSquaredProperty(result) {
        return result.type === "JsdocTypeIndexSignature" || result.type === "JsdocTypeMappedType";
      }
      var Precedence;
      (function(Precedence2) {
        Precedence2[Precedence2.ALL = 0] = "ALL", Precedence2[Precedence2.PARAMETER_LIST = 1] = "PARAMETER_LIST", Precedence2[Precedence2.OBJECT = 2] = "OBJECT", Precedence2[Precedence2.KEY_VALUE = 3] = "KEY_VALUE", Precedence2[Precedence2.INDEX_BRACKETS = 4] = "INDEX_BRACKETS", Precedence2[Precedence2.UNION = 5] = "UNION", Precedence2[Precedence2.INTERSECTION = 6] = "INTERSECTION", Precedence2[Precedence2.PREFIX = 7] = "PREFIX", Precedence2[Precedence2.INFIX = 8] = "INFIX", Precedence2[Precedence2.TUPLE = 9] = "TUPLE", Precedence2[Precedence2.SYMBOL = 10] = "SYMBOL", Precedence2[Precedence2.OPTIONAL = 11] = "OPTIONAL", Precedence2[Precedence2.NULLABLE = 12] = "NULLABLE", Precedence2[Precedence2.KEY_OF_TYPE_OF = 13] = "KEY_OF_TYPE_OF", Precedence2[Precedence2.FUNCTION = 14] = "FUNCTION", Precedence2[Precedence2.ARROW = 15] = "ARROW", Precedence2[Precedence2.ARRAY_BRACKETS = 16] = "ARRAY_BRACKETS", Precedence2[Precedence2.GENERIC = 17] = "GENERIC", Precedence2[Precedence2.NAME_PATH = 18] = "NAME_PATH", Precedence2[Precedence2.PARENTHESIS = 19] = "PARENTHESIS", Precedence2[Precedence2.SPECIAL_TYPES = 20] = "SPECIAL_TYPES";
      })(Precedence || (Precedence = {}));
      class Parser {
        constructor(grammar, textOrLexer, baseParser) {
          this.grammar = grammar, typeof textOrLexer == "string" ? this._lexer = Lexer.create(textOrLexer) : this._lexer = textOrLexer, this.baseParser = baseParser;
        }
        get lexer() {
          return this._lexer;
        }
        /**
         * Parses a given string and throws an error if the parse ended before the end of the string.
         */
        parse() {
          let result = this.parseType(Precedence.ALL);
          if (this.lexer.current.type !== "EOF")
            throw new EarlyEndOfParseError(this.lexer.current);
          return result;
        }
        /**
         * Parses with the current lexer and asserts that the result is a {@link RootResult}.
         */
        parseType(precedence) {
          return assertRootResult(this.parseIntermediateType(precedence));
        }
        /**
         * The main parsing function. First it tries to parse the current state in the prefix step, and then it continues
         * to parse the state in the infix step.
         */
        parseIntermediateType(precedence) {
          let result = this.tryParslets(null, precedence);
          if (result === null)
            throw new NoParsletFoundError(this.lexer.current);
          return this.parseInfixIntermediateType(result, precedence);
        }
        /**
         * In the infix parsing step the parser continues to parse the current state with all parslets until none returns
         * a result.
         */
        parseInfixIntermediateType(left, precedence) {
          let result = this.tryParslets(left, precedence);
          for (; result !== null; )
            left = result, result = this.tryParslets(left, precedence);
          return left;
        }
        /**
         * Tries to parse the current state with all parslets in the grammar and returns the first non null result.
         */
        tryParslets(left, precedence) {
          for (let parslet of this.grammar) {
            let result = parslet(this, precedence, left);
            if (result !== null)
              return result;
          }
          return null;
        }
        /**
         * If the given type equals the current type of the {@link Lexer} advance the lexer. Return true if the lexer was
         * advanced.
         */
        consume(types) {
          return Array.isArray(types) || (types = [types]), types.includes(this.lexer.current.type) ? (this._lexer = this.lexer.advance(), !0) : !1;
        }
        acceptLexerState(parser) {
          this._lexer = parser.lexer;
        }
      }
      function isQuestionMarkUnknownType(next) {
        return next === "}" || next === "EOF" || next === "|" || next === "," || next === ")" || next === ">";
      }
      let nullableParslet = (parser, precedence, left) => {
        let type = parser.lexer.current.type, next = parser.lexer.next.type;
        return left == null && type === "?" && !isQuestionMarkUnknownType(next) || left != null && type === "?" ? (parser.consume("?"), left == null ? {
          type: "JsdocTypeNullable",
          element: parser.parseType(Precedence.NULLABLE),
          meta: {
            position: "prefix"
          }
        } : {
          type: "JsdocTypeNullable",
          element: assertRootResult(left),
          meta: {
            position: "suffix"
          }
        }) : null;
      };
      function composeParslet(options) {
        let parslet = (parser, curPrecedence, left) => {
          let type = parser.lexer.current.type, next = parser.lexer.next.type;
          if (left === null) {
            if ("parsePrefix" in options && options.accept(type, next))
              return options.parsePrefix(parser);
          } else if ("parseInfix" in options && options.precedence > curPrecedence && options.accept(type, next))
            return options.parseInfix(parser, left);
          return null;
        };
        return Object.defineProperty(parslet, "name", {
          value: options.name
        }), parslet;
      }
      let optionalParslet = composeParslet({
        name: "optionalParslet",
        accept: (type) => type === "=",
        precedence: Precedence.OPTIONAL,
        parsePrefix: (parser) => (parser.consume("="), {
          type: "JsdocTypeOptional",
          element: parser.parseType(Precedence.OPTIONAL),
          meta: {
            position: "prefix"
          }
        }),
        parseInfix: (parser, left) => (parser.consume("="), {
          type: "JsdocTypeOptional",
          element: assertRootResult(left),
          meta: {
            position: "suffix"
          }
        })
      }), numberParslet = composeParslet({
        name: "numberParslet",
        accept: (type) => type === "Number",
        parsePrefix: (parser) => {
          let value = parseFloat(parser.lexer.current.text);
          return parser.consume("Number"), {
            type: "JsdocTypeNumber",
            value
          };
        }
      }), parenthesisParslet = composeParslet({
        name: "parenthesisParslet",
        accept: (type) => type === "(",
        parsePrefix: (parser) => {
          if (parser.consume("("), parser.consume(")"))
            return {
              type: "JsdocTypeParameterList",
              elements: []
            };
          let result = parser.parseIntermediateType(Precedence.ALL);
          if (!parser.consume(")"))
            throw new Error("Unterminated parenthesis");
          return result.type === "JsdocTypeParameterList" ? result : result.type === "JsdocTypeKeyValue" ? {
            type: "JsdocTypeParameterList",
            elements: [result]
          } : {
            type: "JsdocTypeParenthesis",
            element: assertRootResult(result)
          };
        }
      }), specialTypesParslet = composeParslet({
        name: "specialTypesParslet",
        accept: (type, next) => type === "?" && isQuestionMarkUnknownType(next) || type === "null" || type === "undefined" || type === "*",
        parsePrefix: (parser) => {
          if (parser.consume("null"))
            return {
              type: "JsdocTypeNull"
            };
          if (parser.consume("undefined"))
            return {
              type: "JsdocTypeUndefined"
            };
          if (parser.consume("*"))
            return {
              type: "JsdocTypeAny"
            };
          if (parser.consume("?"))
            return {
              type: "JsdocTypeUnknown"
            };
          throw new Error("Unacceptable token: " + parser.lexer.current.text);
        }
      }), notNullableParslet = composeParslet({
        name: "notNullableParslet",
        accept: (type) => type === "!",
        precedence: Precedence.NULLABLE,
        parsePrefix: (parser) => (parser.consume("!"), {
          type: "JsdocTypeNotNullable",
          element: parser.parseType(Precedence.NULLABLE),
          meta: {
            position: "prefix"
          }
        }),
        parseInfix: (parser, left) => (parser.consume("!"), {
          type: "JsdocTypeNotNullable",
          element: assertRootResult(left),
          meta: {
            position: "suffix"
          }
        })
      });
      function createParameterListParslet({ allowTrailingComma }) {
        return composeParslet({
          name: "parameterListParslet",
          accept: (type) => type === ",",
          precedence: Precedence.PARAMETER_LIST,
          parseInfix: (parser, left) => {
            let elements = [
              assertPlainKeyValueOrRootResult(left)
            ];
            parser.consume(",");
            do
              try {
                let next = parser.parseIntermediateType(Precedence.PARAMETER_LIST);
                elements.push(assertPlainKeyValueOrRootResult(next));
              } catch (e) {
                if (e instanceof NoParsletFoundError)
                  break;
                throw e;
              }
            while (parser.consume(","));
            if (elements.length > 0 && elements.slice(0, -1).some((e) => e.type === "JsdocTypeVariadic"))
              throw new Error("Only the last parameter may be a rest parameter");
            return {
              type: "JsdocTypeParameterList",
              elements
            };
          }
        });
      }
      let genericParslet = composeParslet({
        name: "genericParslet",
        accept: (type, next) => type === "<" || type === "." && next === "<",
        precedence: Precedence.GENERIC,
        parseInfix: (parser, left) => {
          let dot = parser.consume(".");
          parser.consume("<");
          let objects = [], infer = !1;
          if (parser.consume("infer")) {
            infer = !0;
            let left2 = parser.parseIntermediateType(Precedence.SYMBOL);
            if (left2.type !== "JsdocTypeName")
              throw new UnexpectedTypeError(left2, "A typescript asserts always has to have a name on the left side.");
            objects.push(left2);
          } else
            do
              objects.push(parser.parseType(Precedence.PARAMETER_LIST));
            while (parser.consume(","));
          if (!parser.consume(">"))
            throw new Error("Unterminated generic parameter list");
          return Object.assign(Object.assign({ type: "JsdocTypeGeneric", left: assertRootResult(left), elements: objects }, infer ? { infer: !0 } : {}), { meta: {
            brackets: "angle",
            dot
          } });
        }
      }), unionParslet = composeParslet({
        name: "unionParslet",
        accept: (type) => type === "|",
        precedence: Precedence.UNION,
        parseInfix: (parser, left) => {
          parser.consume("|");
          let elements = [];
          do
            elements.push(parser.parseType(Precedence.UNION));
          while (parser.consume("|"));
          return {
            type: "JsdocTypeUnion",
            elements: [assertRootResult(left), ...elements]
          };
        }
      }), baseGrammar = [
        nullableParslet,
        optionalParslet,
        numberParslet,
        parenthesisParslet,
        specialTypesParslet,
        notNullableParslet,
        createParameterListParslet({
          allowTrailingComma: !0
        }),
        genericParslet,
        unionParslet,
        optionalParslet
      ];
      function createNamePathParslet({ allowSquareBracketsOnAnyType, allowJsdocNamePaths, pathGrammar: pathGrammar2 }) {
        return function(parser, precedence, left) {
          if (left == null || precedence >= Precedence.NAME_PATH)
            return null;
          let type = parser.lexer.current.type, next = parser.lexer.next.type;
          if (!(type === "." && next !== "<" || type === "[" && (allowSquareBracketsOnAnyType || left.type === "JsdocTypeName") || allowJsdocNamePaths && (type === "~" || type === "#")))
            return null;
          let pathType, brackets = !1;
          parser.consume(".") ? pathType = "property" : parser.consume("[") ? (pathType = "property-brackets", brackets = !0) : parser.consume("~") ? pathType = "inner" : (parser.consume("#"), pathType = "instance");
          let pathParser = pathGrammar2 !== null ? new Parser(pathGrammar2, parser.lexer, parser) : parser, parsed = pathParser.parseIntermediateType(Precedence.NAME_PATH);
          parser.acceptLexerState(pathParser);
          let right;
          switch (parsed.type) {
            case "JsdocTypeName":
              right = {
                type: "JsdocTypeProperty",
                value: parsed.value,
                meta: {
                  quote: void 0
                }
              };
              break;
            case "JsdocTypeNumber":
              right = {
                type: "JsdocTypeProperty",
                value: parsed.value.toString(10),
                meta: {
                  quote: void 0
                }
              };
              break;
            case "JsdocTypeStringValue":
              right = {
                type: "JsdocTypeProperty",
                value: parsed.value,
                meta: {
                  quote: parsed.meta.quote
                }
              };
              break;
            case "JsdocTypeSpecialNamePath":
              if (parsed.specialType === "event")
                right = parsed;
              else
                throw new UnexpectedTypeError(parsed, "Type 'JsdocTypeSpecialNamePath' is only allowed with specialType 'event'");
              break;
            default:
              throw new UnexpectedTypeError(parsed, "Expecting 'JsdocTypeName', 'JsdocTypeNumber', 'JsdocStringValue' or 'JsdocTypeSpecialNamePath'");
          }
          if (brackets && !parser.consume("]")) {
            let token = parser.lexer.current;
            throw new Error(`Unterminated square brackets. Next token is '${token.type}' with text '${token.text}'`);
          }
          return {
            type: "JsdocTypeNamePath",
            left: assertRootResult(left),
            right,
            pathType
          };
        };
      }
      function createNameParslet({ allowedAdditionalTokens }) {
        return composeParslet({
          name: "nameParslet",
          accept: (type) => type === "Identifier" || type === "this" || type === "new" || allowedAdditionalTokens.includes(type),
          parsePrefix: (parser) => {
            let { type, text } = parser.lexer.current;
            return parser.consume(type), {
              type: "JsdocTypeName",
              value: text
            };
          }
        });
      }
      let stringValueParslet = composeParslet({
        name: "stringValueParslet",
        accept: (type) => type === "StringValue",
        parsePrefix: (parser) => {
          let text = parser.lexer.current.text;
          return parser.consume("StringValue"), {
            type: "JsdocTypeStringValue",
            value: text.slice(1, -1),
            meta: {
              quote: text[0] === "'" ? "single" : "double"
            }
          };
        }
      });
      function createSpecialNamePathParslet({ pathGrammar: pathGrammar2, allowedTypes }) {
        return composeParslet({
          name: "specialNamePathParslet",
          accept: (type) => allowedTypes.includes(type),
          parsePrefix: (parser) => {
            let type = parser.lexer.current.type;
            if (parser.consume(type), !parser.consume(":"))
              return {
                type: "JsdocTypeName",
                value: type
              };
            let result, token = parser.lexer.current;
            if (parser.consume("StringValue"))
              result = {
                type: "JsdocTypeSpecialNamePath",
                value: token.text.slice(1, -1),
                specialType: type,
                meta: {
                  quote: token.text[0] === "'" ? "single" : "double"
                }
              };
            else {
              let value = "", allowed = ["Identifier", "@", "/"];
              for (; allowed.some((type2) => parser.consume(type2)); )
                value += token.text, token = parser.lexer.current;
              result = {
                type: "JsdocTypeSpecialNamePath",
                value,
                specialType: type,
                meta: {
                  quote: void 0
                }
              };
            }
            let moduleParser = new Parser(pathGrammar2, parser.lexer, parser), moduleResult = moduleParser.parseInfixIntermediateType(result, Precedence.ALL);
            return parser.acceptLexerState(moduleParser), assertRootResult(moduleResult);
          }
        });
      }
      let basePathGrammar = [
        createNameParslet({
          allowedAdditionalTokens: ["external", "module"]
        }),
        stringValueParslet,
        numberParslet,
        createNamePathParslet({
          allowSquareBracketsOnAnyType: !1,
          allowJsdocNamePaths: !0,
          pathGrammar: null
        })
      ], pathGrammar = [
        ...basePathGrammar,
        createSpecialNamePathParslet({
          allowedTypes: ["event"],
          pathGrammar: basePathGrammar
        })
      ];
      function getParameters(value) {
        let parameters;
        if (value.type === "JsdocTypeParameterList")
          parameters = value.elements;
        else if (value.type === "JsdocTypeParenthesis")
          parameters = [value.element];
        else
          throw new UnexpectedTypeError(value);
        return parameters.map((p) => assertPlainKeyValueOrRootResult(p));
      }
      function getUnnamedParameters(value) {
        let parameters = getParameters(value);
        if (parameters.some((p) => p.type === "JsdocTypeKeyValue"))
          throw new Error("No parameter should be named");
        return parameters;
      }
      function createFunctionParslet({ allowNamedParameters, allowNoReturnType, allowWithoutParenthesis, allowNewAsFunctionKeyword }) {
        return composeParslet({
          name: "functionParslet",
          accept: (type, next) => type === "function" || allowNewAsFunctionKeyword && type === "new" && next === "(",
          parsePrefix: (parser) => {
            let newKeyword = parser.consume("new");
            parser.consume("function");
            let hasParenthesis = parser.lexer.current.type === "(";
            if (!hasParenthesis) {
              if (!allowWithoutParenthesis)
                throw new Error("function is missing parameter list");
              return {
                type: "JsdocTypeName",
                value: "function"
              };
            }
            let result = {
              type: "JsdocTypeFunction",
              parameters: [],
              arrow: !1,
              constructor: newKeyword,
              parenthesis: hasParenthesis
            }, value = parser.parseIntermediateType(Precedence.FUNCTION);
            if (allowNamedParameters === void 0)
              result.parameters = getUnnamedParameters(value);
            else {
              if (newKeyword && value.type === "JsdocTypeFunction" && value.arrow)
                return result = value, result.constructor = !0, result;
              result.parameters = getParameters(value);
              for (let p of result.parameters)
                if (p.type === "JsdocTypeKeyValue" && !allowNamedParameters.includes(p.key))
                  throw new Error(`only allowed named parameters are ${allowNamedParameters.join(", ")} but got ${p.type}`);
            }
            if (parser.consume(":"))
              result.returnType = parser.parseType(Precedence.PREFIX);
            else if (!allowNoReturnType)
              throw new Error("function is missing return type");
            return result;
          }
        });
      }
      function createVariadicParslet({ allowPostfix, allowEnclosingBrackets }) {
        return composeParslet({
          name: "variadicParslet",
          accept: (type) => type === "...",
          precedence: Precedence.PREFIX,
          parsePrefix: (parser) => {
            parser.consume("...");
            let brackets = allowEnclosingBrackets && parser.consume("[");
            try {
              let element = parser.parseType(Precedence.PREFIX);
              if (brackets && !parser.consume("]"))
                throw new Error("Unterminated variadic type. Missing ']'");
              return {
                type: "JsdocTypeVariadic",
                element: assertRootResult(element),
                meta: {
                  position: "prefix",
                  squareBrackets: brackets
                }
              };
            } catch (e) {
              if (e instanceof NoParsletFoundError) {
                if (brackets)
                  throw new Error("Empty square brackets for variadic are not allowed.");
                return {
                  type: "JsdocTypeVariadic",
                  meta: {
                    position: void 0,
                    squareBrackets: !1
                  }
                };
              } else
                throw e;
            }
          },
          parseInfix: allowPostfix ? (parser, left) => (parser.consume("..."), {
            type: "JsdocTypeVariadic",
            element: assertRootResult(left),
            meta: {
              position: "suffix",
              squareBrackets: !1
            }
          }) : void 0
        });
      }
      let symbolParslet = composeParslet({
        name: "symbolParslet",
        accept: (type) => type === "(",
        precedence: Precedence.SYMBOL,
        parseInfix: (parser, left) => {
          if (left.type !== "JsdocTypeName")
            throw new Error("Symbol expects a name on the left side. (Reacting on '(')");
          parser.consume("(");
          let result = {
            type: "JsdocTypeSymbol",
            value: left.value
          };
          if (!parser.consume(")")) {
            let next = parser.parseIntermediateType(Precedence.SYMBOL);
            if (result.element = assertNumberOrVariadicNameResult(next), !parser.consume(")"))
              throw new Error("Symbol does not end after value");
          }
          return result;
        }
      }), arrayBracketsParslet = composeParslet({
        name: "arrayBracketsParslet",
        precedence: Precedence.ARRAY_BRACKETS,
        accept: (type, next) => type === "[" && next === "]",
        parseInfix: (parser, left) => (parser.consume("["), parser.consume("]"), {
          type: "JsdocTypeGeneric",
          left: {
            type: "JsdocTypeName",
            value: "Array"
          },
          elements: [
            assertRootResult(left)
          ],
          meta: {
            brackets: "square",
            dot: !1
          }
        })
      });
      function createObjectParslet({ objectFieldGrammar: objectFieldGrammar2, allowKeyTypes }) {
        return composeParslet({
          name: "objectParslet",
          accept: (type) => type === "{",
          parsePrefix: (parser) => {
            parser.consume("{");
            let result = {
              type: "JsdocTypeObject",
              meta: {
                separator: "comma"
              },
              elements: []
            };
            if (!parser.consume("}")) {
              let separator, fieldParser = new Parser(objectFieldGrammar2, parser.lexer, parser);
              for (; ; ) {
                fieldParser.acceptLexerState(parser);
                let field = fieldParser.parseIntermediateType(Precedence.OBJECT);
                parser.acceptLexerState(fieldParser), field === void 0 && allowKeyTypes && (field = parser.parseIntermediateType(Precedence.OBJECT));
                let optional = !1;
                if (field.type === "JsdocTypeNullable" && (optional = !0, field = field.element), field.type === "JsdocTypeNumber" || field.type === "JsdocTypeName" || field.type === "JsdocTypeStringValue") {
                  let quote2;
                  field.type === "JsdocTypeStringValue" && (quote2 = field.meta.quote), result.elements.push({
                    type: "JsdocTypeObjectField",
                    key: field.value.toString(),
                    right: void 0,
                    optional,
                    readonly: !1,
                    meta: {
                      quote: quote2
                    }
                  });
                } else if (field.type === "JsdocTypeObjectField" || field.type === "JsdocTypeJsdocObjectField")
                  result.elements.push(field);
                else
                  throw new UnexpectedTypeError(field);
                if (parser.lexer.current.startOfLine)
                  separator = "linebreak", parser.consume(",") || parser.consume(";");
                else if (parser.consume(","))
                  separator = "comma";
                else if (parser.consume(";"))
                  separator = "semicolon";
                else
                  break;
                if (parser.lexer.current.type === "}")
                  break;
              }
              if (result.meta.separator = separator ?? "comma", separator === "linebreak" && (result.meta.propertyIndent = "  "), !parser.consume("}"))
                throw new Error("Unterminated record type. Missing '}'");
            }
            return result;
          }
        });
      }
      function createObjectFieldParslet({ allowSquaredProperties, allowKeyTypes, allowReadonly, allowOptional }) {
        return composeParslet({
          name: "objectFieldParslet",
          precedence: Precedence.KEY_VALUE,
          accept: (type) => type === ":",
          parseInfix: (parser, left) => {
            var _a;
            let optional = !1, readonlyProperty = !1;
            allowOptional && left.type === "JsdocTypeNullable" && (optional = !0, left = left.element), allowReadonly && left.type === "JsdocTypeReadonlyProperty" && (readonlyProperty = !0, left = left.element);
            let parentParser = (_a = parser.baseParser) !== null && _a !== void 0 ? _a : parser;
            if (parentParser.acceptLexerState(parser), left.type === "JsdocTypeNumber" || left.type === "JsdocTypeName" || left.type === "JsdocTypeStringValue" || isSquaredProperty(left)) {
              if (isSquaredProperty(left) && !allowSquaredProperties)
                throw new UnexpectedTypeError(left);
              parentParser.consume(":");
              let quote2;
              left.type === "JsdocTypeStringValue" && (quote2 = left.meta.quote);
              let right = parentParser.parseType(Precedence.KEY_VALUE);
              return parser.acceptLexerState(parentParser), {
                type: "JsdocTypeObjectField",
                key: isSquaredProperty(left) ? left : left.value.toString(),
                right,
                optional,
                readonly: readonlyProperty,
                meta: {
                  quote: quote2
                }
              };
            } else {
              if (!allowKeyTypes)
                throw new UnexpectedTypeError(left);
              parentParser.consume(":");
              let right = parentParser.parseType(Precedence.KEY_VALUE);
              return parser.acceptLexerState(parentParser), {
                type: "JsdocTypeJsdocObjectField",
                left: assertRootResult(left),
                right
              };
            }
          }
        });
      }
      function createKeyValueParslet({ allowOptional, allowVariadic }) {
        return composeParslet({
          name: "keyValueParslet",
          precedence: Precedence.KEY_VALUE,
          accept: (type) => type === ":",
          parseInfix: (parser, left) => {
            let optional = !1, variadic = !1;
            if (allowOptional && left.type === "JsdocTypeNullable" && (optional = !0, left = left.element), allowVariadic && left.type === "JsdocTypeVariadic" && left.element !== void 0 && (variadic = !0, left = left.element), left.type !== "JsdocTypeName")
              throw new UnexpectedTypeError(left);
            parser.consume(":");
            let right = parser.parseType(Precedence.KEY_VALUE);
            return {
              type: "JsdocTypeKeyValue",
              key: left.value,
              right,
              optional,
              variadic
            };
          }
        });
      }
      let jsdocBaseGrammar = [
        ...baseGrammar,
        createFunctionParslet({
          allowWithoutParenthesis: !0,
          allowNamedParameters: ["this", "new"],
          allowNoReturnType: !0,
          allowNewAsFunctionKeyword: !1
        }),
        stringValueParslet,
        createSpecialNamePathParslet({
          allowedTypes: ["module", "external", "event"],
          pathGrammar
        }),
        createVariadicParslet({
          allowEnclosingBrackets: !0,
          allowPostfix: !0
        }),
        createNameParslet({
          allowedAdditionalTokens: ["keyof"]
        }),
        symbolParslet,
        arrayBracketsParslet,
        createNamePathParslet({
          allowSquareBracketsOnAnyType: !1,
          allowJsdocNamePaths: !0,
          pathGrammar
        })
      ], jsdocGrammar = [
        ...jsdocBaseGrammar,
        createObjectParslet({
          // jsdoc syntax allows full types as keys, so we need to pull in the full grammar here
          // we leave out the object type deliberately
          objectFieldGrammar: [
            createNameParslet({
              allowedAdditionalTokens: ["typeof", "module", "in"]
            }),
            createObjectFieldParslet({
              allowSquaredProperties: !1,
              allowKeyTypes: !0,
              allowOptional: !1,
              allowReadonly: !1
            }),
            ...jsdocBaseGrammar
          ],
          allowKeyTypes: !0
        }),
        createKeyValueParslet({
          allowOptional: !0,
          allowVariadic: !0
        })
      ], typeOfParslet = composeParslet({
        name: "typeOfParslet",
        accept: (type) => type === "typeof",
        parsePrefix: (parser) => (parser.consume("typeof"), {
          type: "JsdocTypeTypeof",
          element: parser.parseType(Precedence.KEY_OF_TYPE_OF)
        })
      }), objectFieldGrammar$1 = [
        createNameParslet({
          allowedAdditionalTokens: ["typeof", "module", "keyof", "event", "external", "in"]
        }),
        nullableParslet,
        optionalParslet,
        stringValueParslet,
        numberParslet,
        createObjectFieldParslet({
          allowSquaredProperties: !1,
          allowKeyTypes: !1,
          allowOptional: !1,
          allowReadonly: !1
        })
      ], closureGrammar = [
        ...baseGrammar,
        createObjectParslet({
          allowKeyTypes: !1,
          objectFieldGrammar: objectFieldGrammar$1
        }),
        createNameParslet({
          allowedAdditionalTokens: ["event", "external", "in"]
        }),
        typeOfParslet,
        createFunctionParslet({
          allowWithoutParenthesis: !1,
          allowNamedParameters: ["this", "new"],
          allowNoReturnType: !0,
          allowNewAsFunctionKeyword: !1
        }),
        createVariadicParslet({
          allowEnclosingBrackets: !1,
          allowPostfix: !1
        }),
        // additional name parslet is needed for some special cases
        createNameParslet({
          allowedAdditionalTokens: ["keyof"]
        }),
        createSpecialNamePathParslet({
          allowedTypes: ["module"],
          pathGrammar
        }),
        createNamePathParslet({
          allowSquareBracketsOnAnyType: !1,
          allowJsdocNamePaths: !0,
          pathGrammar
        }),
        createKeyValueParslet({
          allowOptional: !1,
          allowVariadic: !1
        }),
        symbolParslet
      ], assertsParslet = composeParslet({
        name: "assertsParslet",
        accept: (type) => type === "asserts",
        parsePrefix: (parser) => {
          parser.consume("asserts");
          let left = parser.parseIntermediateType(Precedence.SYMBOL);
          if (left.type !== "JsdocTypeName")
            throw new UnexpectedTypeError(left, "A typescript asserts always has to have a name on the left side.");
          return parser.consume("is") ? {
            type: "JsdocTypeAsserts",
            left,
            right: assertRootResult(parser.parseIntermediateType(Precedence.INFIX))
          } : {
            type: "JsdocTypeAssertsPlain",
            element: left
          };
        }
      });
      function createTupleParslet({ allowQuestionMark }) {
        return composeParslet({
          name: "tupleParslet",
          accept: (type) => type === "[",
          parsePrefix: (parser) => {
            parser.consume("[");
            let result = {
              type: "JsdocTypeTuple",
              elements: []
            };
            if (parser.consume("]"))
              return result;
            let typeList = parser.parseIntermediateType(Precedence.ALL);
            if (typeList.type === "JsdocTypeParameterList" ? typeList.elements[0].type === "JsdocTypeKeyValue" ? result.elements = typeList.elements.map(assertPlainKeyValueResult) : result.elements = typeList.elements.map(assertRootResult) : typeList.type === "JsdocTypeKeyValue" ? result.elements = [assertPlainKeyValueResult(typeList)] : result.elements = [assertRootResult(typeList)], !parser.consume("]"))
              throw new Error("Unterminated '['");
            if (result.elements.some((e) => e.type === "JsdocTypeUnknown"))
              throw new Error("Question mark in tuple not allowed");
            return result;
          }
        });
      }
      let keyOfParslet = composeParslet({
        name: "keyOfParslet",
        accept: (type) => type === "keyof",
        parsePrefix: (parser) => (parser.consume("keyof"), {
          type: "JsdocTypeKeyof",
          element: assertRootResult(parser.parseType(Precedence.KEY_OF_TYPE_OF))
        })
      }), importParslet = composeParslet({
        name: "importParslet",
        accept: (type) => type === "import",
        parsePrefix: (parser) => {
          if (parser.consume("import"), !parser.consume("("))
            throw new Error("Missing parenthesis after import keyword");
          let path = parser.parseType(Precedence.PREFIX);
          if (path.type !== "JsdocTypeStringValue")
            throw new Error("Only string values are allowed as paths for imports");
          if (!parser.consume(")"))
            throw new Error("Missing closing parenthesis after import keyword");
          return {
            type: "JsdocTypeImport",
            element: path
          };
        }
      }), readonlyPropertyParslet = composeParslet({
        name: "readonlyPropertyParslet",
        accept: (type) => type === "readonly",
        parsePrefix: (parser) => (parser.consume("readonly"), {
          type: "JsdocTypeReadonlyProperty",
          element: parser.parseIntermediateType(Precedence.KEY_VALUE)
        })
      }), arrowFunctionParslet = composeParslet({
        name: "arrowFunctionParslet",
        precedence: Precedence.ARROW,
        accept: (type) => type === "=>",
        parseInfix: (parser, left) => (parser.consume("=>"), {
          type: "JsdocTypeFunction",
          parameters: getParameters(left).map(assertPlainKeyValueOrNameResult),
          arrow: !0,
          constructor: !1,
          parenthesis: !0,
          returnType: parser.parseType(Precedence.OBJECT)
        })
      }), genericArrowFunctionParslet = composeParslet({
        name: "genericArrowFunctionParslet",
        accept: (type) => type === "<",
        parsePrefix: (parser) => {
          let typeParameters = [];
          parser.consume("<");
          do {
            let defaultValue, name = parser.parseIntermediateType(Precedence.SYMBOL);
            if (name.type === "JsdocTypeOptional" && (name = name.element, defaultValue = parser.parseType(Precedence.SYMBOL)), name.type !== "JsdocTypeName")
              throw new UnexpectedTypeError(name);
            let constraint;
            parser.consume("extends") && (constraint = parser.parseType(Precedence.SYMBOL), constraint.type === "JsdocTypeOptional" && (constraint = constraint.element, defaultValue = parser.parseType(Precedence.SYMBOL)));
            let typeParameter = {
              type: "JsdocTypeTypeParameter",
              name
            };
            if (constraint !== void 0 && (typeParameter.constraint = constraint), defaultValue !== void 0 && (typeParameter.defaultValue = defaultValue), typeParameters.push(typeParameter), parser.consume(">"))
              break;
          } while (parser.consume(","));
          let functionBase = parser.parseIntermediateType(Precedence.SYMBOL);
          return functionBase.typeParameters = typeParameters, functionBase;
        }
      }), intersectionParslet = composeParslet({
        name: "intersectionParslet",
        accept: (type) => type === "&",
        precedence: Precedence.INTERSECTION,
        parseInfix: (parser, left) => {
          parser.consume("&");
          let elements = [];
          do
            elements.push(parser.parseType(Precedence.INTERSECTION));
          while (parser.consume("&"));
          return {
            type: "JsdocTypeIntersection",
            elements: [assertRootResult(left), ...elements]
          };
        }
      }), predicateParslet = composeParslet({
        name: "predicateParslet",
        precedence: Precedence.INFIX,
        accept: (type) => type === "is",
        parseInfix: (parser, left) => {
          if (left.type !== "JsdocTypeName")
            throw new UnexpectedTypeError(left, "A typescript predicate always has to have a name on the left side.");
          return parser.consume("is"), {
            type: "JsdocTypePredicate",
            left,
            right: assertRootResult(parser.parseIntermediateType(Precedence.INFIX))
          };
        }
      }), objectSquaredPropertyParslet = composeParslet({
        name: "objectSquareBracketPropertyParslet",
        accept: (type) => type === "[",
        parsePrefix: (parser) => {
          if (parser.baseParser === void 0)
            throw new Error("Only allowed inside object grammar");
          parser.consume("[");
          let key = parser.lexer.current.text;
          parser.consume("Identifier");
          let result;
          if (parser.consume(":")) {
            let parentParser = parser.baseParser;
            parentParser.acceptLexerState(parser), result = {
              type: "JsdocTypeIndexSignature",
              key,
              right: parentParser.parseType(Precedence.INDEX_BRACKETS)
            }, parser.acceptLexerState(parentParser);
          } else if (parser.consume("in")) {
            let parentParser = parser.baseParser;
            parentParser.acceptLexerState(parser), result = {
              type: "JsdocTypeMappedType",
              key,
              right: parentParser.parseType(Precedence.ARRAY_BRACKETS)
            }, parser.acceptLexerState(parentParser);
          } else
            throw new Error("Missing ':' or 'in' inside square bracketed property.");
          if (!parser.consume("]"))
            throw new Error("Unterminated square brackets");
          return result;
        }
      }), readonlyArrayParslet = composeParslet({
        name: "readonlyArrayParslet",
        accept: (type) => type === "readonly",
        parsePrefix: (parser) => (parser.consume("readonly"), {
          type: "JsdocTypeReadonlyArray",
          element: assertArrayOrTupleResult(parser.parseIntermediateType(Precedence.ALL))
        })
      }), conditionalParslet = composeParslet({
        name: "conditionalParslet",
        precedence: Precedence.INFIX,
        accept: (type) => type === "extends",
        parseInfix: (parser, left) => {
          parser.consume("extends");
          let extendsType = parser.parseType(Precedence.KEY_OF_TYPE_OF).element, trueType = parser.parseType(Precedence.INFIX);
          return parser.consume(":"), {
            type: "JsdocTypeConditional",
            checksType: assertRootResult(left),
            extendsType,
            trueType,
            falseType: parser.parseType(Precedence.INFIX)
          };
        }
      }), objectFieldGrammar = [
        readonlyPropertyParslet,
        createNameParslet({
          allowedAdditionalTokens: ["typeof", "module", "keyof", "event", "external", "in"]
        }),
        nullableParslet,
        optionalParslet,
        stringValueParslet,
        numberParslet,
        createObjectFieldParslet({
          allowSquaredProperties: !0,
          allowKeyTypes: !1,
          allowOptional: !0,
          allowReadonly: !0
        }),
        objectSquaredPropertyParslet
      ], typescriptGrammar = [
        ...baseGrammar,
        createObjectParslet({
          allowKeyTypes: !1,
          objectFieldGrammar
        }),
        readonlyArrayParslet,
        typeOfParslet,
        keyOfParslet,
        importParslet,
        stringValueParslet,
        createFunctionParslet({
          allowWithoutParenthesis: !0,
          allowNoReturnType: !1,
          allowNamedParameters: ["this", "new", "args"],
          allowNewAsFunctionKeyword: !0
        }),
        createTupleParslet({
          allowQuestionMark: !1
        }),
        createVariadicParslet({
          allowEnclosingBrackets: !1,
          allowPostfix: !1
        }),
        assertsParslet,
        conditionalParslet,
        createNameParslet({
          allowedAdditionalTokens: ["event", "external", "in"]
        }),
        createSpecialNamePathParslet({
          allowedTypes: ["module"],
          pathGrammar
        }),
        arrayBracketsParslet,
        arrowFunctionParslet,
        genericArrowFunctionParslet,
        createNamePathParslet({
          allowSquareBracketsOnAnyType: !0,
          allowJsdocNamePaths: !1,
          pathGrammar
        }),
        intersectionParslet,
        predicateParslet,
        createKeyValueParslet({
          allowVariadic: !0,
          allowOptional: !0
        })
      ];
      function parse3(expression, mode) {
        switch (mode) {
          case "closure":
            return new Parser(closureGrammar, expression).parse();
          case "jsdoc":
            return new Parser(jsdocGrammar, expression).parse();
          case "typescript":
            return new Parser(typescriptGrammar, expression).parse();
        }
      }
      function tryParse(expression, modes = ["typescript", "closure", "jsdoc"]) {
        let error;
        for (let mode of modes)
          try {
            return parse3(expression, mode);
          } catch (e) {
            error = e;
          }
        throw error;
      }
      function transform(rules2, parseResult) {
        let rule = rules2[parseResult.type];
        if (rule === void 0)
          throw new Error(`In this set of transform rules exists no rule for type ${parseResult.type}.`);
        return rule(parseResult, (aParseResult) => transform(rules2, aParseResult));
      }
      function notAvailableTransform(parseResult) {
        throw new Error("This transform is not available. Are you trying the correct parsing mode?");
      }
      function extractSpecialParams(source) {
        let result = {
          params: []
        };
        for (let param of source.parameters)
          param.type === "JsdocTypeKeyValue" ? param.key === "this" ? result.this = param.right : param.key === "new" ? result.new = param.right : result.params.push(param) : result.params.push(param);
        return result;
      }
      function applyPosition(position, target, value) {
        return position === "prefix" ? value + target : target + value;
      }
      function quote(value, quote2) {
        switch (quote2) {
          case "double":
            return `"${value}"`;
          case "single":
            return `'${value}'`;
          case void 0:
            return value;
        }
      }
      function stringifyRules2() {
        return {
          JsdocTypeParenthesis: (result, transform2) => `(${result.element !== void 0 ? transform2(result.element) : ""})`,
          JsdocTypeKeyof: (result, transform2) => `keyof ${transform2(result.element)}`,
          JsdocTypeFunction: (result, transform2) => {
            var _a;
            if (result.arrow) {
              if (result.returnType === void 0)
                throw new Error("Arrow function needs a return type.");
              let stringified = `${result.typeParameters !== void 0 ? `<${(_a = result.typeParameters.map(transform2).join(", ")) !== null && _a !== void 0 ? _a : ""}>` : ""}(${result.parameters.map(transform2).join(", ")}) => ${transform2(result.returnType)}`;
              return result.constructor && (stringified = "new " + stringified), stringified;
            } else {
              let stringified = result.constructor ? "new" : "function";
              return result.parenthesis && (stringified += `(${result.parameters.map(transform2).join(", ")})`, result.returnType !== void 0 && (stringified += `: ${transform2(result.returnType)}`)), stringified;
            }
          },
          JsdocTypeName: (result) => result.value,
          JsdocTypeTuple: (result, transform2) => `[${result.elements.map(transform2).join(", ")}]`,
          JsdocTypeVariadic: (result, transform2) => result.meta.position === void 0 ? "..." : applyPosition(result.meta.position, transform2(result.element), "..."),
          JsdocTypeNamePath: (result, transform2) => {
            let left = transform2(result.left), right = transform2(result.right);
            switch (result.pathType) {
              case "inner":
                return `${left}~${right}`;
              case "instance":
                return `${left}#${right}`;
              case "property":
                return `${left}.${right}`;
              case "property-brackets":
                return `${left}[${right}]`;
            }
          },
          JsdocTypeStringValue: (result) => quote(result.value, result.meta.quote),
          JsdocTypeAny: () => "*",
          JsdocTypeGeneric: (result, transform2) => {
            if (result.meta.brackets === "square") {
              let element = result.elements[0], transformed = transform2(element);
              return element.type === "JsdocTypeUnion" || element.type === "JsdocTypeIntersection" ? `(${transformed})[]` : `${transformed}[]`;
            } else
              return `${transform2(result.left)}${result.meta.dot ? "." : ""}<${result.infer === !0 ? "infer " : ""}${result.elements.map(transform2).join(", ")}>`;
          },
          JsdocTypeImport: (result, transform2) => `import(${transform2(result.element)})`,
          JsdocTypeObjectField: (result, transform2) => {
            let text = "";
            return result.readonly && (text += "readonly "), typeof result.key == "string" ? text += quote(result.key, result.meta.quote) : text += transform2(result.key), result.optional && (text += "?"), result.right === void 0 ? text : text + `: ${transform2(result.right)}`;
          },
          JsdocTypeJsdocObjectField: (result, transform2) => `${transform2(result.left)}: ${transform2(result.right)}`,
          JsdocTypeKeyValue: (result, transform2) => {
            let text = result.key;
            return result.optional && (text += "?"), result.variadic && (text = "..." + text), result.right === void 0 ? text : text + `: ${transform2(result.right)}`;
          },
          JsdocTypeSpecialNamePath: (result) => `${result.specialType}:${quote(result.value, result.meta.quote)}`,
          JsdocTypeNotNullable: (result, transform2) => applyPosition(result.meta.position, transform2(result.element), "!"),
          JsdocTypeNull: () => "null",
          JsdocTypeNullable: (result, transform2) => applyPosition(result.meta.position, transform2(result.element), "?"),
          JsdocTypeNumber: (result) => result.value.toString(),
          JsdocTypeObject: (result, transform2) => {
            var _a, _b;
            return `{${(result.meta.separator === "linebreak" && result.elements.length > 1 ? `
` + ((_a = result.meta.propertyIndent) !== null && _a !== void 0 ? _a : "") : "") + result.elements.map(transform2).join(result.meta.separator === "comma" ? ", " : result.meta.separator === "linebreak" ? `
` + ((_b = result.meta.propertyIndent) !== null && _b !== void 0 ? _b : "") : "; ") + (result.meta.separator === "linebreak" && result.elements.length > 1 ? `
` : "")}}`;
          },
          JsdocTypeOptional: (result, transform2) => applyPosition(result.meta.position, transform2(result.element), "="),
          JsdocTypeSymbol: (result, transform2) => `${result.value}(${result.element !== void 0 ? transform2(result.element) : ""})`,
          JsdocTypeTypeof: (result, transform2) => `typeof ${transform2(result.element)}`,
          JsdocTypeUndefined: () => "undefined",
          JsdocTypeUnion: (result, transform2) => result.elements.map(transform2).join(" | "),
          JsdocTypeUnknown: () => "?",
          JsdocTypeIntersection: (result, transform2) => result.elements.map(transform2).join(" & "),
          JsdocTypeProperty: (result) => quote(result.value, result.meta.quote),
          JsdocTypePredicate: (result, transform2) => `${transform2(result.left)} is ${transform2(result.right)}`,
          JsdocTypeIndexSignature: (result, transform2) => `[${result.key}: ${transform2(result.right)}]`,
          JsdocTypeMappedType: (result, transform2) => `[${result.key} in ${transform2(result.right)}]`,
          JsdocTypeAsserts: (result, transform2) => `asserts ${transform2(result.left)} is ${transform2(result.right)}`,
          JsdocTypeReadonlyArray: (result, transform2) => `readonly ${transform2(result.element)}`,
          JsdocTypeAssertsPlain: (result, transform2) => `asserts ${transform2(result.element)}`,
          JsdocTypeConditional: (result, transform2) => `${transform2(result.checksType)} extends ${transform2(result.extendsType)} ? ${transform2(result.trueType)} : ${transform2(result.falseType)}`,
          JsdocTypeTypeParameter: (result, transform2) => `${transform2(result.name)}${result.constraint !== void 0 ? ` extends ${transform2(result.constraint)}` : ""}${result.defaultValue !== void 0 ? ` = ${transform2(result.defaultValue)}` : ""}`
        };
      }
      let storedStringifyRules = stringifyRules2();
      function stringify2(result) {
        return transform(storedStringifyRules, result);
      }
      let reservedWords = [
        "null",
        "true",
        "false",
        "break",
        "case",
        "catch",
        "class",
        "const",
        "continue",
        "debugger",
        "default",
        "delete",
        "do",
        "else",
        "export",
        "extends",
        "finally",
        "for",
        "function",
        "if",
        "import",
        "in",
        "instanceof",
        "new",
        "return",
        "super",
        "switch",
        "this",
        "throw",
        "try",
        "typeof",
        "var",
        "void",
        "while",
        "with",
        "yield"
      ];
      function makeName(value) {
        let result = {
          type: "NameExpression",
          name: value
        };
        return reservedWords.includes(value) && (result.reservedWord = !0), result;
      }
      let catharsisTransformRules = {
        JsdocTypeOptional: (result, transform2) => {
          let transformed = transform2(result.element);
          return transformed.optional = !0, transformed;
        },
        JsdocTypeNullable: (result, transform2) => {
          let transformed = transform2(result.element);
          return transformed.nullable = !0, transformed;
        },
        JsdocTypeNotNullable: (result, transform2) => {
          let transformed = transform2(result.element);
          return transformed.nullable = !1, transformed;
        },
        JsdocTypeVariadic: (result, transform2) => {
          if (result.element === void 0)
            throw new Error("dots without value are not allowed in catharsis mode");
          let transformed = transform2(result.element);
          return transformed.repeatable = !0, transformed;
        },
        JsdocTypeAny: () => ({
          type: "AllLiteral"
        }),
        JsdocTypeNull: () => ({
          type: "NullLiteral"
        }),
        JsdocTypeStringValue: (result) => makeName(quote(result.value, result.meta.quote)),
        JsdocTypeUndefined: () => ({
          type: "UndefinedLiteral"
        }),
        JsdocTypeUnknown: () => ({
          type: "UnknownLiteral"
        }),
        JsdocTypeFunction: (result, transform2) => {
          let params = extractSpecialParams(result), transformed = {
            type: "FunctionType",
            params: params.params.map(transform2)
          };
          return params.this !== void 0 && (transformed.this = transform2(params.this)), params.new !== void 0 && (transformed.new = transform2(params.new)), result.returnType !== void 0 && (transformed.result = transform2(result.returnType)), transformed;
        },
        JsdocTypeGeneric: (result, transform2) => ({
          type: "TypeApplication",
          applications: result.elements.map((o) => transform2(o)),
          expression: transform2(result.left)
        }),
        JsdocTypeSpecialNamePath: (result) => makeName(result.specialType + ":" + quote(result.value, result.meta.quote)),
        JsdocTypeName: (result) => result.value !== "function" ? makeName(result.value) : {
          type: "FunctionType",
          params: []
        },
        JsdocTypeNumber: (result) => makeName(result.value.toString()),
        JsdocTypeObject: (result, transform2) => {
          let transformed = {
            type: "RecordType",
            fields: []
          };
          for (let field of result.elements)
            field.type !== "JsdocTypeObjectField" && field.type !== "JsdocTypeJsdocObjectField" ? transformed.fields.push({
              type: "FieldType",
              key: transform2(field),
              value: void 0
            }) : transformed.fields.push(transform2(field));
          return transformed;
        },
        JsdocTypeObjectField: (result, transform2) => {
          if (typeof result.key != "string")
            throw new Error("Index signatures and mapped types are not supported");
          return {
            type: "FieldType",
            key: makeName(quote(result.key, result.meta.quote)),
            value: result.right === void 0 ? void 0 : transform2(result.right)
          };
        },
        JsdocTypeJsdocObjectField: (result, transform2) => ({
          type: "FieldType",
          key: transform2(result.left),
          value: transform2(result.right)
        }),
        JsdocTypeUnion: (result, transform2) => ({
          type: "TypeUnion",
          elements: result.elements.map((e) => transform2(e))
        }),
        JsdocTypeKeyValue: (result, transform2) => ({
          type: "FieldType",
          key: makeName(result.key),
          value: result.right === void 0 ? void 0 : transform2(result.right)
        }),
        JsdocTypeNamePath: (result, transform2) => {
          let leftResult = transform2(result.left), rightValue;
          result.right.type === "JsdocTypeSpecialNamePath" ? rightValue = transform2(result.right).name : rightValue = quote(result.right.value, result.right.meta.quote);
          let joiner = result.pathType === "inner" ? "~" : result.pathType === "instance" ? "#" : ".";
          return makeName(`${leftResult.name}${joiner}${rightValue}`);
        },
        JsdocTypeSymbol: (result) => {
          let value = "", element = result.element, trailingDots = !1;
          return element?.type === "JsdocTypeVariadic" && (element.meta.position === "prefix" ? value = "..." : trailingDots = !0, element = element.element), element?.type === "JsdocTypeName" ? value += element.value : element?.type === "JsdocTypeNumber" && (value += element.value.toString()), trailingDots && (value += "..."), makeName(`${result.value}(${value})`);
        },
        JsdocTypeParenthesis: (result, transform2) => transform2(assertRootResult(result.element)),
        JsdocTypeMappedType: notAvailableTransform,
        JsdocTypeIndexSignature: notAvailableTransform,
        JsdocTypeImport: notAvailableTransform,
        JsdocTypeKeyof: notAvailableTransform,
        JsdocTypeTuple: notAvailableTransform,
        JsdocTypeTypeof: notAvailableTransform,
        JsdocTypeIntersection: notAvailableTransform,
        JsdocTypeProperty: notAvailableTransform,
        JsdocTypePredicate: notAvailableTransform,
        JsdocTypeAsserts: notAvailableTransform,
        JsdocTypeReadonlyArray: notAvailableTransform,
        JsdocTypeAssertsPlain: notAvailableTransform,
        JsdocTypeConditional: notAvailableTransform,
        JsdocTypeTypeParameter: notAvailableTransform
      };
      function catharsisTransform(result) {
        return transform(catharsisTransformRules, result);
      }
      function getQuoteStyle(quote2) {
        switch (quote2) {
          case void 0:
            return "none";
          case "single":
            return "single";
          case "double":
            return "double";
        }
      }
      function getMemberType(type) {
        switch (type) {
          case "inner":
            return "INNER_MEMBER";
          case "instance":
            return "INSTANCE_MEMBER";
          case "property":
            return "MEMBER";
          case "property-brackets":
            return "MEMBER";
        }
      }
      function nestResults(type, results) {
        return results.length === 2 ? {
          type,
          left: results[0],
          right: results[1]
        } : {
          type,
          left: results[0],
          right: nestResults(type, results.slice(1))
        };
      }
      let jtpRules = {
        JsdocTypeOptional: (result, transform2) => ({
          type: "OPTIONAL",
          value: transform2(result.element),
          meta: {
            syntax: result.meta.position === "prefix" ? "PREFIX_EQUAL_SIGN" : "SUFFIX_EQUALS_SIGN"
          }
        }),
        JsdocTypeNullable: (result, transform2) => ({
          type: "NULLABLE",
          value: transform2(result.element),
          meta: {
            syntax: result.meta.position === "prefix" ? "PREFIX_QUESTION_MARK" : "SUFFIX_QUESTION_MARK"
          }
        }),
        JsdocTypeNotNullable: (result, transform2) => ({
          type: "NOT_NULLABLE",
          value: transform2(result.element),
          meta: {
            syntax: result.meta.position === "prefix" ? "PREFIX_BANG" : "SUFFIX_BANG"
          }
        }),
        JsdocTypeVariadic: (result, transform2) => {
          let transformed = {
            type: "VARIADIC",
            meta: {
              syntax: result.meta.position === "prefix" ? "PREFIX_DOTS" : result.meta.position === "suffix" ? "SUFFIX_DOTS" : "ONLY_DOTS"
            }
          };
          return result.element !== void 0 && (transformed.value = transform2(result.element)), transformed;
        },
        JsdocTypeName: (result) => ({
          type: "NAME",
          name: result.value
        }),
        JsdocTypeTypeof: (result, transform2) => ({
          type: "TYPE_QUERY",
          name: transform2(result.element)
        }),
        JsdocTypeTuple: (result, transform2) => ({
          type: "TUPLE",
          entries: result.elements.map(transform2)
        }),
        JsdocTypeKeyof: (result, transform2) => ({
          type: "KEY_QUERY",
          value: transform2(result.element)
        }),
        JsdocTypeImport: (result) => ({
          type: "IMPORT",
          path: {
            type: "STRING_VALUE",
            quoteStyle: getQuoteStyle(result.element.meta.quote),
            string: result.element.value
          }
        }),
        JsdocTypeUndefined: () => ({
          type: "NAME",
          name: "undefined"
        }),
        JsdocTypeAny: () => ({
          type: "ANY"
        }),
        JsdocTypeFunction: (result, transform2) => {
          let specialParams = extractSpecialParams(result), transformed = {
            type: result.arrow ? "ARROW" : "FUNCTION",
            params: specialParams.params.map((param) => {
              if (param.type === "JsdocTypeKeyValue") {
                if (param.right === void 0)
                  throw new Error("Function parameter without ':' is not expected to be 'KEY_VALUE'");
                return {
                  type: "NAMED_PARAMETER",
                  name: param.key,
                  typeName: transform2(param.right)
                };
              } else
                return transform2(param);
            }),
            new: null,
            returns: null
          };
          return specialParams.this !== void 0 ? transformed.this = transform2(specialParams.this) : result.arrow || (transformed.this = null), specialParams.new !== void 0 && (transformed.new = transform2(specialParams.new)), result.returnType !== void 0 && (transformed.returns = transform2(result.returnType)), transformed;
        },
        JsdocTypeGeneric: (result, transform2) => {
          let transformed = {
            type: "GENERIC",
            subject: transform2(result.left),
            objects: result.elements.map(transform2),
            meta: {
              syntax: result.meta.brackets === "square" ? "SQUARE_BRACKET" : result.meta.dot ? "ANGLE_BRACKET_WITH_DOT" : "ANGLE_BRACKET"
            }
          };
          return result.meta.brackets === "square" && result.elements[0].type === "JsdocTypeFunction" && !result.elements[0].parenthesis && (transformed.objects[0] = {
            type: "NAME",
            name: "function"
          }), transformed;
        },
        JsdocTypeObjectField: (result, transform2) => {
          if (typeof result.key != "string")
            throw new Error("Index signatures and mapped types are not supported");
          if (result.right === void 0)
            return {
              type: "RECORD_ENTRY",
              key: result.key,
              quoteStyle: getQuoteStyle(result.meta.quote),
              value: null,
              readonly: !1
            };
          let right = transform2(result.right);
          return result.optional && (right = {
            type: "OPTIONAL",
            value: right,
            meta: {
              syntax: "SUFFIX_KEY_QUESTION_MARK"
            }
          }), {
            type: "RECORD_ENTRY",
            key: result.key.toString(),
            quoteStyle: getQuoteStyle(result.meta.quote),
            value: right,
            readonly: !1
          };
        },
        JsdocTypeJsdocObjectField: () => {
          throw new Error("Keys may not be typed in jsdoctypeparser.");
        },
        JsdocTypeKeyValue: (result, transform2) => {
          if (result.right === void 0)
            return {
              type: "RECORD_ENTRY",
              key: result.key,
              quoteStyle: "none",
              value: null,
              readonly: !1
            };
          let right = transform2(result.right);
          return result.optional && (right = {
            type: "OPTIONAL",
            value: right,
            meta: {
              syntax: "SUFFIX_KEY_QUESTION_MARK"
            }
          }), {
            type: "RECORD_ENTRY",
            key: result.key,
            quoteStyle: "none",
            value: right,
            readonly: !1
          };
        },
        JsdocTypeObject: (result, transform2) => {
          let entries = [];
          for (let field of result.elements)
            (field.type === "JsdocTypeObjectField" || field.type === "JsdocTypeJsdocObjectField") && entries.push(transform2(field));
          return {
            type: "RECORD",
            entries
          };
        },
        JsdocTypeSpecialNamePath: (result) => {
          if (result.specialType !== "module")
            throw new Error(`jsdoctypeparser does not support type ${result.specialType} at this point.`);
          return {
            type: "MODULE",
            value: {
              type: "FILE_PATH",
              quoteStyle: getQuoteStyle(result.meta.quote),
              path: result.value
            }
          };
        },
        JsdocTypeNamePath: (result, transform2) => {
          let hasEventPrefix = !1, name, quoteStyle;
          result.right.type === "JsdocTypeSpecialNamePath" && result.right.specialType === "event" ? (hasEventPrefix = !0, name = result.right.value, quoteStyle = getQuoteStyle(result.right.meta.quote)) : (name = result.right.value, quoteStyle = getQuoteStyle(result.right.meta.quote));
          let transformed = {
            type: getMemberType(result.pathType),
            owner: transform2(result.left),
            name,
            quoteStyle,
            hasEventPrefix
          };
          if (transformed.owner.type === "MODULE") {
            let tModule = transformed.owner;
            return transformed.owner = transformed.owner.value, tModule.value = transformed, tModule;
          } else
            return transformed;
        },
        JsdocTypeUnion: (result, transform2) => nestResults("UNION", result.elements.map(transform2)),
        JsdocTypeParenthesis: (result, transform2) => ({
          type: "PARENTHESIS",
          value: transform2(assertRootResult(result.element))
        }),
        JsdocTypeNull: () => ({
          type: "NAME",
          name: "null"
        }),
        JsdocTypeUnknown: () => ({
          type: "UNKNOWN"
        }),
        JsdocTypeStringValue: (result) => ({
          type: "STRING_VALUE",
          quoteStyle: getQuoteStyle(result.meta.quote),
          string: result.value
        }),
        JsdocTypeIntersection: (result, transform2) => nestResults("INTERSECTION", result.elements.map(transform2)),
        JsdocTypeNumber: (result) => ({
          type: "NUMBER_VALUE",
          number: result.value.toString()
        }),
        JsdocTypeSymbol: notAvailableTransform,
        JsdocTypeProperty: notAvailableTransform,
        JsdocTypePredicate: notAvailableTransform,
        JsdocTypeMappedType: notAvailableTransform,
        JsdocTypeIndexSignature: notAvailableTransform,
        JsdocTypeAsserts: notAvailableTransform,
        JsdocTypeReadonlyArray: notAvailableTransform,
        JsdocTypeAssertsPlain: notAvailableTransform,
        JsdocTypeConditional: notAvailableTransform,
        JsdocTypeTypeParameter: notAvailableTransform
      };
      function jtpTransform(result) {
        return transform(jtpRules, result);
      }
      function identityTransformRules() {
        return {
          JsdocTypeIntersection: (result, transform2) => ({
            type: "JsdocTypeIntersection",
            elements: result.elements.map(transform2)
          }),
          JsdocTypeGeneric: (result, transform2) => ({
            type: "JsdocTypeGeneric",
            left: transform2(result.left),
            elements: result.elements.map(transform2),
            meta: {
              dot: result.meta.dot,
              brackets: result.meta.brackets
            }
          }),
          JsdocTypeNullable: (result) => result,
          JsdocTypeUnion: (result, transform2) => ({
            type: "JsdocTypeUnion",
            elements: result.elements.map(transform2)
          }),
          JsdocTypeUnknown: (result) => result,
          JsdocTypeUndefined: (result) => result,
          JsdocTypeTypeof: (result, transform2) => ({
            type: "JsdocTypeTypeof",
            element: transform2(result.element)
          }),
          JsdocTypeSymbol: (result, transform2) => {
            let transformed = {
              type: "JsdocTypeSymbol",
              value: result.value
            };
            return result.element !== void 0 && (transformed.element = transform2(result.element)), transformed;
          },
          JsdocTypeOptional: (result, transform2) => ({
            type: "JsdocTypeOptional",
            element: transform2(result.element),
            meta: {
              position: result.meta.position
            }
          }),
          JsdocTypeObject: (result, transform2) => ({
            type: "JsdocTypeObject",
            meta: {
              separator: "comma"
            },
            elements: result.elements.map(transform2)
          }),
          JsdocTypeNumber: (result) => result,
          JsdocTypeNull: (result) => result,
          JsdocTypeNotNullable: (result, transform2) => ({
            type: "JsdocTypeNotNullable",
            element: transform2(result.element),
            meta: {
              position: result.meta.position
            }
          }),
          JsdocTypeSpecialNamePath: (result) => result,
          JsdocTypeObjectField: (result, transform2) => ({
            type: "JsdocTypeObjectField",
            key: result.key,
            right: result.right === void 0 ? void 0 : transform2(result.right),
            optional: result.optional,
            readonly: result.readonly,
            meta: result.meta
          }),
          JsdocTypeJsdocObjectField: (result, transform2) => ({
            type: "JsdocTypeJsdocObjectField",
            left: transform2(result.left),
            right: transform2(result.right)
          }),
          JsdocTypeKeyValue: (result, transform2) => ({
            type: "JsdocTypeKeyValue",
            key: result.key,
            right: result.right === void 0 ? void 0 : transform2(result.right),
            optional: result.optional,
            variadic: result.variadic
          }),
          JsdocTypeImport: (result, transform2) => ({
            type: "JsdocTypeImport",
            element: transform2(result.element)
          }),
          JsdocTypeAny: (result) => result,
          JsdocTypeStringValue: (result) => result,
          JsdocTypeNamePath: (result) => result,
          JsdocTypeVariadic: (result, transform2) => {
            let transformed = {
              type: "JsdocTypeVariadic",
              meta: {
                position: result.meta.position,
                squareBrackets: result.meta.squareBrackets
              }
            };
            return result.element !== void 0 && (transformed.element = transform2(result.element)), transformed;
          },
          JsdocTypeTuple: (result, transform2) => ({
            type: "JsdocTypeTuple",
            elements: result.elements.map(transform2)
          }),
          JsdocTypeName: (result) => result,
          JsdocTypeFunction: (result, transform2) => {
            let transformed = {
              type: "JsdocTypeFunction",
              arrow: result.arrow,
              parameters: result.parameters.map(transform2),
              constructor: result.constructor,
              parenthesis: result.parenthesis
            };
            return result.returnType !== void 0 && (transformed.returnType = transform2(result.returnType)), transformed;
          },
          JsdocTypeKeyof: (result, transform2) => ({
            type: "JsdocTypeKeyof",
            element: transform2(result.element)
          }),
          JsdocTypeParenthesis: (result, transform2) => ({
            type: "JsdocTypeParenthesis",
            element: transform2(result.element)
          }),
          JsdocTypeProperty: (result) => result,
          JsdocTypePredicate: (result, transform2) => ({
            type: "JsdocTypePredicate",
            left: transform2(result.left),
            right: transform2(result.right)
          }),
          JsdocTypeIndexSignature: (result, transform2) => ({
            type: "JsdocTypeIndexSignature",
            key: result.key,
            right: transform2(result.right)
          }),
          JsdocTypeMappedType: (result, transform2) => ({
            type: "JsdocTypeMappedType",
            key: result.key,
            right: transform2(result.right)
          }),
          JsdocTypeAsserts: (result, transform2) => ({
            type: "JsdocTypeAsserts",
            left: transform2(result.left),
            right: transform2(result.right)
          }),
          JsdocTypeReadonlyArray: (result, transform2) => ({
            type: "JsdocTypeReadonlyArray",
            element: transform2(result.element)
          }),
          JsdocTypeAssertsPlain: (result, transform2) => ({
            type: "JsdocTypeAssertsPlain",
            element: transform2(result.element)
          }),
          JsdocTypeConditional: (result, transform2) => ({
            type: "JsdocTypeConditional",
            checksType: transform2(result.checksType),
            extendsType: transform2(result.extendsType),
            trueType: transform2(result.trueType),
            falseType: transform2(result.falseType)
          }),
          JsdocTypeTypeParameter: (result, transform2) => ({
            type: "JsdocTypeTypeParameter",
            name: transform2(result.name),
            constraint: result.constraint !== void 0 ? transform2(result.constraint) : void 0,
            defaultValue: result.defaultValue !== void 0 ? transform2(result.defaultValue) : void 0
          })
        };
      }
      let visitorKeys = {
        JsdocTypeAny: [],
        JsdocTypeFunction: ["parameters", "returnType"],
        JsdocTypeGeneric: ["left", "elements"],
        JsdocTypeImport: [],
        JsdocTypeIndexSignature: ["right"],
        JsdocTypeIntersection: ["elements"],
        JsdocTypeKeyof: ["element"],
        JsdocTypeKeyValue: ["right"],
        JsdocTypeMappedType: ["right"],
        JsdocTypeName: [],
        JsdocTypeNamePath: ["left", "right"],
        JsdocTypeNotNullable: ["element"],
        JsdocTypeNull: [],
        JsdocTypeNullable: ["element"],
        JsdocTypeNumber: [],
        JsdocTypeObject: ["elements"],
        JsdocTypeObjectField: ["right"],
        JsdocTypeJsdocObjectField: ["left", "right"],
        JsdocTypeOptional: ["element"],
        JsdocTypeParenthesis: ["element"],
        JsdocTypeSpecialNamePath: [],
        JsdocTypeStringValue: [],
        JsdocTypeSymbol: ["element"],
        JsdocTypeTuple: ["elements"],
        JsdocTypeTypeof: ["element"],
        JsdocTypeUndefined: [],
        JsdocTypeUnion: ["elements"],
        JsdocTypeUnknown: [],
        JsdocTypeVariadic: ["element"],
        JsdocTypeProperty: [],
        JsdocTypePredicate: ["left", "right"],
        JsdocTypeAsserts: ["left", "right"],
        JsdocTypeReadonlyArray: ["element"],
        JsdocTypeAssertsPlain: ["element"],
        JsdocTypeConditional: ["checksType", "extendsType", "trueType", "falseType"],
        JsdocTypeTypeParameter: ["name", "constraint", "defaultValue"]
      };
      function _traverse(node, parentNode, property, onEnter, onLeave) {
        onEnter?.(node, parentNode, property);
        let keysToVisit = visitorKeys[node.type];
        for (let key of keysToVisit) {
          let value = node[key];
          if (value !== void 0)
            if (Array.isArray(value))
              for (let element of value)
                _traverse(element, node, key, onEnter, onLeave);
            else
              _traverse(value, node, key, onEnter, onLeave);
        }
        onLeave?.(node, parentNode, property);
      }
      function traverse(node, onEnter, onLeave) {
        _traverse(node, void 0, void 0, onEnter, onLeave);
      }
      exports2.catharsisTransform = catharsisTransform, exports2.identityTransformRules = identityTransformRules, exports2.jtpTransform = jtpTransform, exports2.parse = parse3, exports2.stringify = stringify2, exports2.stringifyRules = stringifyRules2, exports2.transform = transform, exports2.traverse = traverse, exports2.tryParse = tryParse, exports2.visitorKeys = visitorKeys;
    }));
  }
});

// src/docs-tools/argTypes/convert/flow/convert.ts

var isLiteral = (type) => type.name === "literal", toEnumOption = (element) => element.value.replace(/['|"]/g, ""), convertSig = (type) => {
  switch (type.type) {
    case "function":
      return { name: "function" };
    case "object":
      let values = {};
      return type.signature.properties.forEach((prop) => {
        values[prop.key] = convert(prop.value);
      }), {
        name: "object",
        value: values
      };
    default:
      throw new storybook_internal_preview_errors__WEBPACK_IMPORTED_MODULE_1__.UnknownArgTypesError({ type, language: "Flow" });
  }
}, convert = (type) => {
  let { name, raw } = type, base = {};
  switch (typeof raw < "u" && (base.raw = raw), type.name) {
    case "literal":
      return { ...base, name: "other", value: type.value };
    case "string":
    case "number":
    case "symbol":
    case "boolean":
      return { ...base, name };
    case "Array":
      return { ...base, name: "array", value: type.elements.map(convert) };
    case "signature":
      return { ...base, ...convertSig(type) };
    case "union":
      return type.elements?.every(isLiteral) ? { ...base, name: "enum", value: type.elements?.map(toEnumOption) } : { ...base, name, value: type.elements?.map(convert) };
    case "intersection":
      return { ...base, name, value: type.elements?.map(convert) };
    default:
      return { ...base, name: "other", value: name };
  }
};

// src/docs-tools/argTypes/convert/utils.ts
var QUOTE_REGEX = /^['"]|['"]$/g, trimQuotes = (str2) => str2.replace(QUOTE_REGEX, ""), includesQuotes = (str2) => QUOTE_REGEX.test(str2), parseLiteral = (str2) => {
  let trimmedValue = trimQuotes(str2);
  return includesQuotes(str2) || Number.isNaN(Number(trimmedValue)) ? trimmedValue : Number(trimmedValue);
};

// src/docs-tools/argTypes/convert/proptypes/convert.ts
var SIGNATURE_REGEXP = /^\(.*\) => /, convert2 = (type) => {
  let { name, raw, computed, value } = type, base = {};
  switch (typeof raw < "u" && (base.raw = raw), name) {
    case "enum": {
      let values2 = computed ? value : value.map((v) => parseLiteral(v.value));
      return { ...base, name, value: values2 };
    }
    case "string":
    case "number":
    case "symbol":
      return { ...base, name };
    case "func":
      return { ...base, name: "function" };
    case "bool":
    case "boolean":
      return { ...base, name: "boolean" };
    case "arrayOf":
    case "array":
      return { ...base, name: "array", value: value && convert2(value) };
    case "object":
      return { ...base, name };
    case "objectOf":
      return { ...base, name, value: convert2(value) };
    case "shape":
    case "exact":
      let values = (0,_chunk_5GYAEUAU_js__WEBPACK_IMPORTED_MODULE_2__/* .mapValues */ .LG)(value, (field) => convert2(field));
      return { ...base, name: "object", value: values };
    case "union":
      return { ...base, name: "union", value: value.map((v) => convert2(v)) };
    default: {
      if (name?.indexOf("|") > 0)
        try {
          let literalValues = name.split("|").map((v) => JSON.parse(v));
          return { ...base, name: "enum", value: literalValues };
        } catch {
        }
      let otherVal = value ? `${name}(${value})` : name, otherName = SIGNATURE_REGEXP.test(name) ? "function" : "other";
      return { ...base, name: otherName, value: otherVal };
    }
  }
};

// src/docs-tools/argTypes/convert/typescript/convert.ts

var isLiteral2 = (type) => type.name === "literal", isUndefined = (type) => type.name === "undefined", convertSig2 = (type) => {
  switch (type.type) {
    case "function":
      return { name: "function" };
    case "object":
      let values = {};
      return type.signature.properties.forEach((prop) => {
        values[prop.key] = convert3(prop.value);
      }), {
        name: "object",
        value: values
      };
    default:
      throw new storybook_internal_preview_errors__WEBPACK_IMPORTED_MODULE_1__.UnknownArgTypesError({ type, language: "Typescript" });
  }
}, convert3 = (type) => {
  let { name, raw } = type, base = {};
  switch (typeof raw < "u" && (base.raw = raw), type.name) {
    case "string":
    case "number":
    case "symbol":
    case "boolean":
      return { ...base, name };
    case "Array":
      return { ...base, name: "array", value: type.elements.map(convert3) };
    case "signature":
      return { ...base, ...convertSig2(type) };
    case "union": {
      let nonUndefinedElements = type.elements.filter((element) => !isUndefined(element));
      if (nonUndefinedElements.length > 0 && nonUndefinedElements.every(isLiteral2)) {
        let literalElements = nonUndefinedElements.filter(isLiteral2);
        return {
          ...base,
          name: "enum",
          value: literalElements.map((element) => parseLiteral(element.value))
        };
      }
      return { ...base, name, value: type.elements.map(convert3) };
    }
    case "intersection":
      return { ...base, name, value: type.elements.map(convert3) };
    default:
      return { ...base, name: "other", value: name };
  }
};

// src/docs-tools/argTypes/convert/index.ts
var convert4 = (docgenInfo) => {
  let { type, tsType, flowType } = docgenInfo;
  try {
    if (type != null)
      return convert2(type);
    if (tsType != null)
      return convert3(tsType);
    if (flowType != null)
      return convert(flowType);
  } catch (err) {
    console.error(err);
  }
  return null;
};

// src/docs-tools/argTypes/docgen/types.ts
var TypeSystem = /* @__PURE__ */ ((TypeSystem2) => (TypeSystem2.JAVASCRIPT = "JavaScript", TypeSystem2.FLOW = "Flow", TypeSystem2.TYPESCRIPT = "TypeScript", TypeSystem2.UNKNOWN = "Unknown", TypeSystem2))(TypeSystem || {});

// src/docs-tools/argTypes/docgen/utils/defaultValue.ts
var BLACKLIST = ["null", "undefined"];
function isDefaultValueBlacklisted(value) {
  return BLACKLIST.some((x) => x === value);
}

// src/docs-tools/argTypes/docgen/utils/string.ts
var str = (obj) => {
  if (!obj)
    return "";
  if (typeof obj == "string")
    return obj;
  throw new Error(`Description: expected string, got: ${JSON.stringify(obj)}`);
};

// src/docs-tools/argTypes/docgen/utils/docgenInfo.ts
function hasDocgen(component) {
  return !!component.__docgenInfo;
}
function isValidDocgenSection(docgenSection) {
  return docgenSection != null && Object.keys(docgenSection).length > 0;
}
function getDocgenSection(component, section) {
  return hasDocgen(component) ? component.__docgenInfo[section] : null;
}
function getDocgenDescription(component) {
  return hasDocgen(component) ? str(component.__docgenInfo.description) : "";
}

// ../../node_modules/comment-parser/es6/primitives.js
var Markers;
(function(Markers2) {
  Markers2.start = "/**", Markers2.nostart = "/***", Markers2.delim = "*", Markers2.end = "*/";
})(Markers = Markers || (Markers = {}));

// ../../node_modules/comment-parser/es6/util.js
function isSpace(source) {
  return /^\s+$/.test(source);
}
function splitCR(source) {
  let matches = source.match(/\r+$/);
  return matches == null ? ["", source] : [source.slice(-matches[0].length), source.slice(0, -matches[0].length)];
}
function splitSpace(source) {
  let matches = source.match(/^\s+/);
  return matches == null ? ["", source] : [source.slice(0, matches[0].length), source.slice(matches[0].length)];
}
function splitLines(source) {
  return source.split(/\n/);
}
function seedSpec(spec = {}) {
  return Object.assign({ tag: "", name: "", type: "", optional: !1, description: "", problems: [], source: [] }, spec);
}
function seedTokens(tokens = {}) {
  return Object.assign({ start: "", delimiter: "", postDelimiter: "", tag: "", postTag: "", name: "", postName: "", type: "", postType: "", description: "", end: "", lineEnd: "" }, tokens);
}

// ../../node_modules/comment-parser/es6/parser/block-parser.js
var reTag = /^@\S+/;
function getParser({ fence = "```" } = {}) {
  let fencer = getFencer(fence), toggleFence = (source, isFenced) => fencer(source) ? !isFenced : isFenced;
  return function(source) {
    let sections = [[]], isFenced = !1;
    for (let line of source)
      reTag.test(line.tokens.description) && !isFenced ? sections.push([line]) : sections[sections.length - 1].push(line), isFenced = toggleFence(line.tokens.description, isFenced);
    return sections;
  };
}
function getFencer(fence) {
  return typeof fence == "string" ? (source) => source.split(fence).length % 2 === 0 : fence;
}

// ../../node_modules/comment-parser/es6/parser/source-parser.js
function getParser2({ startLine = 0, markers = Markers } = {}) {
  let block = null, num = startLine;
  return function(source) {
    let rest = source, tokens = seedTokens();
    if ([tokens.lineEnd, rest] = splitCR(rest), [tokens.start, rest] = splitSpace(rest), block === null && rest.startsWith(markers.start) && !rest.startsWith(markers.nostart) && (block = [], tokens.delimiter = rest.slice(0, markers.start.length), rest = rest.slice(markers.start.length), [tokens.postDelimiter, rest] = splitSpace(rest)), block === null)
      return num++, null;
    let isClosed = rest.trimRight().endsWith(markers.end);
    if (tokens.delimiter === "" && rest.startsWith(markers.delim) && !rest.startsWith(markers.end) && (tokens.delimiter = markers.delim, rest = rest.slice(markers.delim.length), [tokens.postDelimiter, rest] = splitSpace(rest)), isClosed) {
      let trimmed = rest.trimRight();
      tokens.end = rest.slice(trimmed.length - markers.end.length), rest = trimmed.slice(0, -markers.end.length);
    }
    if (tokens.description = rest, block.push({ number: num, source, tokens }), num++, isClosed) {
      let result = block.slice();
      return block = null, result;
    }
    return null;
  };
}

// ../../node_modules/comment-parser/es6/parser/spec-parser.js
function getParser3({ tokenizers }) {
  return function(source) {
    var _a;
    let spec = seedSpec({ source });
    for (let tokenize of tokenizers)
      if (spec = tokenize(spec), !((_a = spec.problems[spec.problems.length - 1]) === null || _a === void 0) && _a.critical)
        break;
    return spec;
  };
}

// ../../node_modules/comment-parser/es6/parser/tokenizers/tag.js
function tagTokenizer() {
  return (spec) => {
    let { tokens } = spec.source[0], match = tokens.description.match(/\s*(@(\S+))(\s*)/);
    return match === null ? (spec.problems.push({
      code: "spec:tag:prefix",
      message: 'tag should start with "@" symbol',
      line: spec.source[0].number,
      critical: !0
    }), spec) : (tokens.tag = match[1], tokens.postTag = match[3], tokens.description = tokens.description.slice(match[0].length), spec.tag = match[2], spec);
  };
}

// ../../node_modules/comment-parser/es6/parser/tokenizers/type.js
function typeTokenizer(spacing = "compact") {
  let join2 = getJoiner(spacing);
  return (spec) => {
    let curlies = 0, lines = [];
    for (let [i, { tokens }] of spec.source.entries()) {
      let type = "";
      if (i === 0 && tokens.description[0] !== "{")
        return spec;
      for (let ch of tokens.description)
        if (ch === "{" && curlies++, ch === "}" && curlies--, type += ch, curlies === 0)
          break;
      if (lines.push([tokens, type]), curlies === 0)
        break;
    }
    if (curlies !== 0)
      return spec.problems.push({
        code: "spec:type:unpaired-curlies",
        message: "unpaired curlies",
        line: spec.source[0].number,
        critical: !0
      }), spec;
    let parts = [], offset = lines[0][0].postDelimiter.length;
    for (let [i, [tokens, type]] of lines.entries())
      tokens.type = type, i > 0 && (tokens.type = tokens.postDelimiter.slice(offset) + type, tokens.postDelimiter = tokens.postDelimiter.slice(0, offset)), [tokens.postType, tokens.description] = splitSpace(tokens.description.slice(type.length)), parts.push(tokens.type);
    return parts[0] = parts[0].slice(1), parts[parts.length - 1] = parts[parts.length - 1].slice(0, -1), spec.type = join2(parts), spec;
  };
}
var trim = (x) => x.trim();
function getJoiner(spacing) {
  return spacing === "compact" ? (t) => t.map(trim).join("") : spacing === "preserve" ? (t) => t.join(`
`) : spacing;
}

// ../../node_modules/comment-parser/es6/parser/tokenizers/name.js
var isQuoted = (s) => s && s.startsWith('"') && s.endsWith('"');
function nameTokenizer() {
  let typeEnd = (num, { tokens }, i) => tokens.type === "" ? num : i;
  return (spec) => {
    let { tokens } = spec.source[spec.source.reduce(typeEnd, 0)], source = tokens.description.trimLeft(), quotedGroups = source.split('"');
    if (quotedGroups.length > 1 && quotedGroups[0] === "" && quotedGroups.length % 2 === 1)
      return spec.name = quotedGroups[1], tokens.name = `"${quotedGroups[1]}"`, [tokens.postName, tokens.description] = splitSpace(source.slice(tokens.name.length)), spec;
    let brackets = 0, name = "", optional = !1, defaultValue;
    for (let ch of source) {
      if (brackets === 0 && isSpace(ch))
        break;
      ch === "[" && brackets++, ch === "]" && brackets--, name += ch;
    }
    if (brackets !== 0)
      return spec.problems.push({
        code: "spec:name:unpaired-brackets",
        message: "unpaired brackets",
        line: spec.source[0].number,
        critical: !0
      }), spec;
    let nameToken = name;
    if (name[0] === "[" && name[name.length - 1] === "]") {
      optional = !0, name = name.slice(1, -1);
      let parts = name.split("=");
      if (name = parts[0].trim(), parts[1] !== void 0 && (defaultValue = parts.slice(1).join("=").trim()), name === "")
        return spec.problems.push({
          code: "spec:name:empty-name",
          message: "empty name",
          line: spec.source[0].number,
          critical: !0
        }), spec;
      if (defaultValue === "")
        return spec.problems.push({
          code: "spec:name:empty-default",
          message: "empty default value",
          line: spec.source[0].number,
          critical: !0
        }), spec;
      if (!isQuoted(defaultValue) && /=(?!>)/.test(defaultValue))
        return spec.problems.push({
          code: "spec:name:invalid-default",
          message: "invalid default value syntax",
          line: spec.source[0].number,
          critical: !0
        }), spec;
    }
    return spec.optional = optional, spec.name = name, tokens.name = nameToken, defaultValue !== void 0 && (spec.default = defaultValue), [tokens.postName, tokens.description] = splitSpace(source.slice(tokens.name.length)), spec;
  };
}

// ../../node_modules/comment-parser/es6/parser/tokenizers/description.js
function descriptionTokenizer(spacing = "compact", markers = Markers) {
  let join2 = getJoiner2(spacing);
  return (spec) => (spec.description = join2(spec.source, markers), spec);
}
function getJoiner2(spacing) {
  return spacing === "compact" ? compactJoiner : spacing === "preserve" ? preserveJoiner : spacing;
}
function compactJoiner(lines, markers = Markers) {
  return lines.map(({ tokens: { description } }) => description.trim()).filter((description) => description !== "").join(" ");
}
var lineNo = (num, { tokens }, i) => tokens.type === "" ? num : i, getDescription = ({ tokens }) => (tokens.delimiter === "" ? tokens.start : tokens.postDelimiter.slice(1)) + tokens.description;
function preserveJoiner(lines, markers = Markers) {
  if (lines.length === 0)
    return "";
  lines[0].tokens.description === "" && lines[0].tokens.delimiter === markers.start && (lines = lines.slice(1));
  let lastLine = lines[lines.length - 1];
  return lastLine !== void 0 && lastLine.tokens.description === "" && lastLine.tokens.end.endsWith(markers.end) && (lines = lines.slice(0, -1)), lines = lines.slice(lines.reduce(lineNo, 0)), lines.map(getDescription).join(`
`);
}

// ../../node_modules/comment-parser/es6/parser/index.js
function getParser4({ startLine = 0, fence = "```", spacing = "compact", markers = Markers, tokenizers = [
  tagTokenizer(),
  typeTokenizer(spacing),
  nameTokenizer(),
  descriptionTokenizer(spacing)
] } = {}) {
  if (startLine < 0 || startLine % 1 > 0)
    throw new Error("Invalid startLine");
  let parseSource = getParser2({ startLine, markers }), parseBlock = getParser({ fence }), parseSpec = getParser3({ tokenizers }), joinDescription = getJoiner2(spacing);
  return function(source) {
    let blocks = [];
    for (let line of splitLines(source)) {
      let lines = parseSource(line);
      if (lines === null)
        continue;
      let sections = parseBlock(lines), specs = sections.slice(1).map(parseSpec);
      blocks.push({
        description: joinDescription(sections[0], markers),
        tags: specs,
        source: lines,
        problems: specs.reduce((acc, spec) => acc.concat(spec.problems), [])
      });
    }
    return blocks;
  };
}

// ../../node_modules/comment-parser/es6/stringifier/index.js
function join(tokens) {
  return tokens.start + tokens.delimiter + tokens.postDelimiter + tokens.tag + tokens.postTag + tokens.type + tokens.postType + tokens.name + tokens.postName + tokens.description + tokens.end + tokens.lineEnd;
}
function getStringifier() {
  return (block) => block.source.map(({ tokens }) => join(tokens)).join(`
`);
}

// ../../node_modules/comment-parser/es6/stringifier/inspect.js
var zeroWidth = {
  line: 0,
  start: 0,
  delimiter: 0,
  postDelimiter: 0,
  tag: 0,
  postTag: 0,
  name: 0,
  postName: 0,
  type: 0,
  postType: 0,
  description: 0,
  end: 0,
  lineEnd: 0
};
var fields = Object.keys(zeroWidth);

// ../../node_modules/comment-parser/es6/index.js
function parse(source, options = {}) {
  return getParser4(options)(source);
}
var stringify = getStringifier();

// src/docs-tools/argTypes/jsdocParser.ts
var import_jsdoc_type_pratt_parser = (0,_chunk_IMSF75WX_js__WEBPACK_IMPORTED_MODULE_0__/* .__toESM */ .f1)(require_dist(), 1);
function containsJsDoc(value) {
  return value != null && value.includes("@");
}
function parse2(content) {
  let normalisedContent = `/**
` + (content ?? "").split(`
`).map((line) => ` * ${line}`).join(`
`) + `
*/`, ast = parse(normalisedContent, {
    spacing: "preserve"
  });
  if (!ast || ast.length === 0)
    throw new Error("Cannot parse JSDoc tags.");
  return ast[0];
}
var DEFAULT_OPTIONS = {
  tags: ["param", "arg", "argument", "returns", "ignore", "deprecated"]
}, parseJsDoc = (value, options = DEFAULT_OPTIONS) => {
  if (!containsJsDoc(value))
    return {
      includesJsDoc: !1,
      ignore: !1
    };
  let jsDocAst = parse2(value), extractedTags = extractJsDocTags(jsDocAst, options.tags);
  return extractedTags.ignore ? {
    includesJsDoc: !0,
    ignore: !0
  } : {
    includesJsDoc: !0,
    ignore: !1,
    // Always use the parsed description to ensure JSDoc is removed from the description.
    description: jsDocAst.description.trim(),
    extractedTags
  };
};
function extractJsDocTags(ast, tags) {
  let extractedTags = {
    params: null,
    deprecated: null,
    returns: null,
    ignore: !1
  };
  for (let tagSpec of ast.tags)
    if (!(tags !== void 0 && !tags.includes(tagSpec.tag)))
      if (tagSpec.tag === "ignore") {
        extractedTags.ignore = !0;
        break;
      } else
        switch (tagSpec.tag) {
          // arg & argument are aliases for param.
          case "param":
          case "arg":
          case "argument": {
            let paramTag = extractParam(tagSpec);
            paramTag != null && (extractedTags.params == null && (extractedTags.params = []), extractedTags.params.push(paramTag));
            break;
          }
          case "deprecated": {
            let deprecatedTag = extractDeprecated(tagSpec);
            deprecatedTag != null && (extractedTags.deprecated = deprecatedTag);
            break;
          }
          case "returns": {
            let returnsTag = extractReturns(tagSpec);
            returnsTag != null && (extractedTags.returns = returnsTag);
            break;
          }
          default:
            break;
        }
  return extractedTags;
}
function normaliseParamName(name) {
  return name.replace(/[\.-]$/, "");
}
function extractParam(tag) {
  if (!tag.name || tag.name === "-")
    return null;
  let type = extractType(tag.type);
  return {
    name: tag.name,
    type,
    description: normaliseDescription(tag.description),
    getPrettyName: () => normaliseParamName(tag.name),
    getTypeName: () => type ? extractTypeName(type) : null
  };
}
function extractDeprecated(tag) {
  return tag.name ? joinNameAndDescription(tag.name, tag.description) : null;
}
function joinNameAndDescription(name, desc) {
  let joined = name === "" ? desc : `${name} ${desc}`;
  return normaliseDescription(joined);
}
function normaliseDescription(text) {
  let normalised = text.replace(/^- /g, "").trim();
  return normalised === "" ? null : normalised;
}
function extractReturns(tag) {
  let type = extractType(tag.type);
  return type ? {
    type,
    description: joinNameAndDescription(tag.name, tag.description),
    getTypeName: () => extractTypeName(type)
  } : null;
}
var jsdocStringifyRules = (0, import_jsdoc_type_pratt_parser.stringifyRules)(), originalJsdocStringifyObject = jsdocStringifyRules.JsdocTypeObject;
jsdocStringifyRules.JsdocTypeAny = () => "any";
jsdocStringifyRules.JsdocTypeObject = (result, transform) => `(${originalJsdocStringifyObject(result, transform)})`;
jsdocStringifyRules.JsdocTypeOptional = (result, transform) => transform(result.element);
jsdocStringifyRules.JsdocTypeNullable = (result, transform) => transform(result.element);
jsdocStringifyRules.JsdocTypeNotNullable = (result, transform) => transform(result.element);
jsdocStringifyRules.JsdocTypeUnion = (result, transform) => result.elements.map(transform).join("|");
function extractType(typeString) {
  try {
    return (0, import_jsdoc_type_pratt_parser.parse)(typeString, "typescript");
  } catch {
    return null;
  }
}
function extractTypeName(type) {
  return (0, import_jsdoc_type_pratt_parser.transform)(jsdocStringifyRules, type);
}

// src/docs-tools/argTypes/utils.ts
var MAX_TYPE_SUMMARY_LENGTH = 90, MAX_DEFAULT_VALUE_SUMMARY_LENGTH = 50;
function isTooLongForTypeSummary(value) {
  return value.length > 90;
}
function isTooLongForDefaultValueSummary(value) {
  return value.length > 50;
}
function createSummaryValue(summary, detail) {
  return summary === detail ? { summary } : { summary, detail };
}
var normalizeNewlines = (string) => string.replace(/\\r\\n/g, "\\n");

// src/docs-tools/argTypes/docgen/flow/createDefaultValue.ts
function createDefaultValue(defaultValue, type) {
  if (defaultValue != null) {
    let { value } = defaultValue;
    if (!isDefaultValueBlacklisted(value))
      return isTooLongForDefaultValueSummary(value) ? createSummaryValue(type?.name, value) : createSummaryValue(value);
  }
  return null;
}

// src/docs-tools/argTypes/docgen/flow/createType.ts
function generateUnionElement({ name, value, elements, raw }) {
  return value ?? (elements != null ? elements.map(generateUnionElement).join(" | ") : raw ?? name);
}
function generateUnion({ name, raw, elements }) {
  return elements != null ? createSummaryValue(elements.map(generateUnionElement).join(" | ")) : raw != null ? createSummaryValue(raw.replace(/^\|\s*/, "")) : createSummaryValue(name);
}
function generateFuncSignature({ type, raw }) {
  return raw != null ? createSummaryValue(raw) : createSummaryValue(type);
}
function generateObjectSignature({ type, raw }) {
  return raw != null ? isTooLongForTypeSummary(raw) ? createSummaryValue(type, raw) : createSummaryValue(raw) : createSummaryValue(type);
}
function generateSignature(flowType) {
  let { type } = flowType;
  return type === "object" ? generateObjectSignature(flowType) : generateFuncSignature(flowType);
}
function generateDefault({ name, raw }) {
  return raw != null ? isTooLongForTypeSummary(raw) ? createSummaryValue(name, raw) : createSummaryValue(raw) : createSummaryValue(name);
}
function createType(type) {
  if (type == null)
    return null;
  switch (type.name) {
    case "union" /* UNION */:
      return generateUnion(type);
    case "signature" /* SIGNATURE */:
      return generateSignature(type);
    default:
      return generateDefault(type);
  }
}

// src/docs-tools/argTypes/docgen/flow/createPropDef.ts
var createFlowPropDef = (propName, docgenInfo) => {
  let { flowType, description, required, defaultValue } = docgenInfo;
  return {
    name: propName,
    type: createType(flowType),
    required,
    description,
    defaultValue: createDefaultValue(defaultValue ?? null, flowType ?? null)
  };
};

// src/docs-tools/argTypes/docgen/typeScript/createDefaultValue.ts
function createDefaultValue2({ defaultValue }) {
  if (defaultValue != null) {
    let { value } = defaultValue;
    if (!isDefaultValueBlacklisted(value))
      return createSummaryValue(value);
  }
  return null;
}

// src/docs-tools/argTypes/docgen/typeScript/createType.ts
function createType2({ tsType, required }) {
  if (tsType == null)
    return null;
  let typeName = tsType.name;
  return required || (typeName = typeName.replace(" | undefined", "")), createSummaryValue(
    ["Array", "Record", "signature"].includes(tsType.name) ? tsType.raw : typeName
  );
}

// src/docs-tools/argTypes/docgen/typeScript/createPropDef.ts
var createTsPropDef = (propName, docgenInfo) => {
  let { description, required } = docgenInfo;
  return {
    name: propName,
    type: createType2(docgenInfo),
    required,
    description,
    defaultValue: createDefaultValue2(docgenInfo)
  };
};

// src/docs-tools/argTypes/docgen/createPropDef.ts
function createType3(type) {
  return type != null ? createSummaryValue(type.name) : null;
}
function isReactDocgenTypescript(defaultValue) {
  let { computed, func } = defaultValue;
  return typeof computed > "u" && typeof func > "u";
}
function isStringValued(type) {
  return type ? type.name === "string" ? !0 : type.name === "enum" ? Array.isArray(type.value) && type.value.every(
    ({ value: tv }) => typeof tv == "string" && tv[0] === '"' && tv[tv.length - 1] === '"'
  ) : !1 : !1;
}
function createDefaultValue3(defaultValue, type) {
  if (defaultValue != null) {
    let { value } = defaultValue;
    if (!isDefaultValueBlacklisted(value))
      return isReactDocgenTypescript(defaultValue) && isStringValued(type) ? createSummaryValue(JSON.stringify(value)) : createSummaryValue(value);
  }
  return null;
}
function createBasicPropDef(name, type, docgenInfo) {
  let { description, required, defaultValue } = docgenInfo;
  return {
    name,
    type: createType3(type),
    required,
    description,
    defaultValue: createDefaultValue3(defaultValue, type)
  };
}
function applyJsDocResult(propDef, jsDocParsingResult) {
  if (jsDocParsingResult?.includesJsDoc) {
    let { description, extractedTags } = jsDocParsingResult;
    description != null && (propDef.description = jsDocParsingResult.description);
    let value = {
      ...extractedTags,
      params: extractedTags?.params?.map(
        (x) => ({
          name: x.getPrettyName(),
          description: x.description
        })
      )
    };
    Object.values(value).filter(Boolean).length > 0 && (propDef.jsDocTags = value);
  }
  return propDef;
}
var javaScriptFactory = (propName, docgenInfo, jsDocParsingResult) => {
  let propDef = createBasicPropDef(propName, docgenInfo.type, docgenInfo);
  return propDef.sbType = convert4(docgenInfo), applyJsDocResult(propDef, jsDocParsingResult);
}, tsFactory = (propName, docgenInfo, jsDocParsingResult) => {
  let propDef = createTsPropDef(propName, docgenInfo);
  return propDef.sbType = convert4(docgenInfo), applyJsDocResult(propDef, jsDocParsingResult);
}, flowFactory = (propName, docgenInfo, jsDocParsingResult) => {
  let propDef = createFlowPropDef(propName, docgenInfo);
  return propDef.sbType = convert4(docgenInfo), applyJsDocResult(propDef, jsDocParsingResult);
}, unknownFactory = (propName, docgenInfo, jsDocParsingResult) => {
  let propDef = createBasicPropDef(propName, { name: "unknown" }, docgenInfo);
  return applyJsDocResult(propDef, jsDocParsingResult);
}, getPropDefFactory = (typeSystem) => {
  switch (typeSystem) {
    case "JavaScript" /* JAVASCRIPT */:
      return javaScriptFactory;
    case "TypeScript" /* TYPESCRIPT */:
      return tsFactory;
    case "Flow" /* FLOW */:
      return flowFactory;
    default:
      return unknownFactory;
  }
};

// src/docs-tools/argTypes/docgen/extractDocgenProps.ts
var getTypeSystem = (docgenInfo) => docgenInfo.type != null ? "JavaScript" /* JAVASCRIPT */ : docgenInfo.flowType != null ? "Flow" /* FLOW */ : docgenInfo.tsType != null ? "TypeScript" /* TYPESCRIPT */ : "Unknown" /* UNKNOWN */, extractComponentSectionArray = (docgenSection) => {
  let typeSystem = getTypeSystem(docgenSection[0]), createPropDef = getPropDefFactory(typeSystem);
  return docgenSection.map((item) => {
    let sanitizedItem = item;
    return item.type?.elements && (sanitizedItem = {
      ...item,
      type: {
        ...item.type,
        value: item.type.elements
      }
    }), extractProp(sanitizedItem.name, sanitizedItem, typeSystem, createPropDef);
  });
}, extractComponentSectionObject = (docgenSection) => {
  let docgenPropsKeys = Object.keys(docgenSection), typeSystem = getTypeSystem(docgenSection[docgenPropsKeys[0]]), createPropDef = getPropDefFactory(typeSystem);
  return docgenPropsKeys.map((propName) => {
    let docgenInfo = docgenSection[propName];
    return docgenInfo != null ? extractProp(propName, docgenInfo, typeSystem, createPropDef) : null;
  }).filter(Boolean);
}, extractComponentProps = (component, section) => {
  let docgenSection = getDocgenSection(component, section);
  return isValidDocgenSection(docgenSection) ? Array.isArray(docgenSection) ? extractComponentSectionArray(docgenSection) : extractComponentSectionObject(docgenSection) : [];
};
function extractProp(propName, docgenInfo, typeSystem, createPropDef) {
  let jsDocParsingResult = parseJsDoc(docgenInfo.description);
  return jsDocParsingResult.includesJsDoc && jsDocParsingResult.ignore ? null : {
    propDef: createPropDef(propName, docgenInfo, jsDocParsingResult),
    jsDocTags: jsDocParsingResult.extractedTags,
    docgenInfo,
    typeSystem
  };
}
function extractComponentDescription(component) {
  return component != null ? getDocgenDescription(component) : "";
}

// src/docs-tools/argTypes/enhanceArgTypes.ts
var enhanceArgTypes = (context) => {
  let {
    component,
    argTypes: userArgTypes,
    parameters: { docs = {} }
  } = context, { extractArgTypes } = docs;
  if (!extractArgTypes || !component)
    return userArgTypes;
  let extractedArgTypes = extractArgTypes(component);
  return extractedArgTypes ? (0,_chunk_FLUL445Z_js__WEBPACK_IMPORTED_MODULE_3__/* .combineParameters */ .mJ)(extractedArgTypes, userArgTypes) : userArgTypes;
};

// src/docs-tools/shared.ts
var ADDON_ID = "storybook/docs", PANEL_ID = (/* unused pure expression or super */ null && (`${ADDON_ID}/panel`)), PARAM_KEY = "docs", SNIPPET_RENDERED = `${ADDON_ID}/snippet-rendered`, SourceType = /* @__PURE__ */ ((SourceType2) => (SourceType2.AUTO = "auto", SourceType2.CODE = "code", SourceType2.DYNAMIC = "dynamic", SourceType2))(SourceType || {});

// src/docs-tools/storyDocsCodePanel.ts
function shouldSkipStoryDocsEmit(parameters) {
  let sourceParams = parameters?.docs?.source, isArgsStory = parameters?.__isArgsStory;
  return parameters?.__isPortableStory ? !0 : sourceParams?.type === "dynamic" /* DYNAMIC */ ? !1 : !isArgsStory || sourceParams?.code !== void 0 || sourceParams?.type === "code" /* CODE */;
}
function shouldWaitForServiceSnippet(parameters, storyPrepared) {
  return globalThis.FEATURES?.experimentalDocgenServer ? !storyPrepared || !shouldSkipStoryDocsEmit(parameters) : !1;
}




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-Z3B64JMR.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  n4: () => (/* binding */ isEqual),
  Qd: () => (/* binding */ isPlainObject),
  sO: () => (/* binding */ isPrimitive),
  iu: () => (/* binding */ isTypedArray)
});

// UNUSED EXPORTS: argumentsTag, arrayBufferTag, arrayTag, booleanTag, dataViewTag, dateTag, float32ArrayTag, float64ArrayTag, getSymbols, getTag, int16ArrayTag, int32ArrayTag, int8ArrayTag, mapTag, numberTag, objectTag, regexpTag, setTag, stringTag, symbolTag, uint16ArrayTag, uint32ArrayTag, uint8ArrayTag, uint8ClampedArrayTag

;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-NZMVUW5T.js
// ../../node_modules/es-toolkit/dist/function/noop.mjs
function noop() {
}



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-Z3B64JMR.js


// ../../node_modules/es-toolkit/dist/predicate/isPlainObject.mjs
function isPlainObject(value) {
  if (!value || typeof value != "object")
    return !1;
  let proto = Object.getPrototypeOf(value);
  return proto === null || proto === Object.prototype || Object.getPrototypeOf(proto) === null ? Object.prototype.toString.call(value) === "[object Object]" : !1;
}

// ../../node_modules/es-toolkit/dist/compat/_internal/getSymbols.mjs
function getSymbols(object) {
  return Object.getOwnPropertySymbols(object).filter((symbol) => Object.prototype.propertyIsEnumerable.call(object, symbol));
}

// ../../node_modules/es-toolkit/dist/compat/_internal/getTag.mjs
function getTag(value) {
  return value == null ? value === void 0 ? "[object Undefined]" : "[object Null]" : Object.prototype.toString.call(value);
}

// ../../node_modules/es-toolkit/dist/compat/_internal/tags.mjs
var regexpTag = "[object RegExp]", stringTag = "[object String]", numberTag = "[object Number]", booleanTag = "[object Boolean]", argumentsTag = "[object Arguments]", symbolTag = "[object Symbol]", dateTag = "[object Date]", mapTag = "[object Map]", setTag = "[object Set]", arrayTag = "[object Array]", functionTag = "[object Function]", arrayBufferTag = "[object ArrayBuffer]", objectTag = "[object Object]", errorTag = "[object Error]", dataViewTag = "[object DataView]", uint8ArrayTag = "[object Uint8Array]", uint8ClampedArrayTag = "[object Uint8ClampedArray]", uint16ArrayTag = "[object Uint16Array]", uint32ArrayTag = "[object Uint32Array]", bigUint64ArrayTag = "[object BigUint64Array]", int8ArrayTag = "[object Int8Array]", int16ArrayTag = "[object Int16Array]", int32ArrayTag = "[object Int32Array]", bigInt64ArrayTag = "[object BigInt64Array]", float32ArrayTag = "[object Float32Array]", float64ArrayTag = "[object Float64Array]";

// ../../node_modules/es-toolkit/dist/compat/util/eq.mjs
function eq(value, other) {
  return value === other || Number.isNaN(value) && Number.isNaN(other);
}

// ../../node_modules/es-toolkit/dist/predicate/isEqualWith.mjs
function isEqualWith(a, b, areValuesEqual) {
  return isEqualWithImpl(a, b, void 0, void 0, void 0, void 0, areValuesEqual);
}
function isEqualWithImpl(a, b, property, aParent, bParent, stack, areValuesEqual) {
  let result = areValuesEqual(a, b, property, aParent, bParent, stack);
  if (result !== void 0)
    return result;
  if (typeof a == typeof b)
    switch (typeof a) {
      case "bigint":
      case "string":
      case "boolean":
      case "symbol":
      case "undefined":
        return a === b;
      case "number":
        return a === b || Object.is(a, b);
      case "function":
        return a === b;
      case "object":
        return areObjectsEqual(a, b, stack, areValuesEqual);
    }
  return areObjectsEqual(a, b, stack, areValuesEqual);
}
function areObjectsEqual(a, b, stack, areValuesEqual) {
  if (Object.is(a, b))
    return !0;
  let aTag = getTag(a), bTag = getTag(b);
  if (aTag === argumentsTag && (aTag = objectTag), bTag === argumentsTag && (bTag = objectTag), aTag !== bTag)
    return !1;
  switch (aTag) {
    case stringTag:
      return a.toString() === b.toString();
    case numberTag: {
      let x = a.valueOf(), y = b.valueOf();
      return eq(x, y);
    }
    case booleanTag:
    case dateTag:
    case symbolTag:
      return Object.is(a.valueOf(), b.valueOf());
    case regexpTag:
      return a.source === b.source && a.flags === b.flags;
    case functionTag:
      return a === b;
  }
  stack = stack ?? /* @__PURE__ */ new Map();
  let aStack = stack.get(a), bStack = stack.get(b);
  if (aStack != null && bStack != null)
    return aStack === b;
  stack.set(a, b), stack.set(b, a);
  try {
    switch (aTag) {
      case mapTag: {
        if (a.size !== b.size)
          return !1;
        for (let [key, value] of a.entries())
          if (!b.has(key) || !isEqualWithImpl(value, b.get(key), key, a, b, stack, areValuesEqual))
            return !1;
        return !0;
      }
      case setTag: {
        if (a.size !== b.size)
          return !1;
        let aValues = Array.from(a.values()), bValues = Array.from(b.values());
        for (let i = 0; i < aValues.length; i++) {
          let aValue = aValues[i], index = bValues.findIndex((bValue) => isEqualWithImpl(aValue, bValue, void 0, a, b, stack, areValuesEqual));
          if (index === -1)
            return !1;
          bValues.splice(index, 1);
        }
        return !0;
      }
      case arrayTag:
      case uint8ArrayTag:
      case uint8ClampedArrayTag:
      case uint16ArrayTag:
      case uint32ArrayTag:
      case bigUint64ArrayTag:
      case int8ArrayTag:
      case int16ArrayTag:
      case int32ArrayTag:
      case bigInt64ArrayTag:
      case float32ArrayTag:
      case float64ArrayTag: {
        if (typeof Buffer < "u" && Buffer.isBuffer(a) !== Buffer.isBuffer(b) || a.length !== b.length)
          return !1;
        for (let i = 0; i < a.length; i++)
          if (!isEqualWithImpl(a[i], b[i], i, a, b, stack, areValuesEqual))
            return !1;
        return !0;
      }
      case arrayBufferTag:
        return a.byteLength !== b.byteLength ? !1 : areObjectsEqual(new Uint8Array(a), new Uint8Array(b), stack, areValuesEqual);
      case dataViewTag:
        return a.byteLength !== b.byteLength || a.byteOffset !== b.byteOffset ? !1 : areObjectsEqual(new Uint8Array(a), new Uint8Array(b), stack, areValuesEqual);
      case errorTag:
        return a.name === b.name && a.message === b.message;
      case objectTag: {
        if (!(areObjectsEqual(a.constructor, b.constructor, stack, areValuesEqual) || isPlainObject(a) && isPlainObject(b)))
          return !1;
        let aKeys = [...Object.keys(a), ...getSymbols(a)], bKeys = [...Object.keys(b), ...getSymbols(b)];
        if (aKeys.length !== bKeys.length)
          return !1;
        for (let i = 0; i < aKeys.length; i++) {
          let propKey = aKeys[i], aProp = a[propKey];
          if (!Object.hasOwn(b, propKey))
            return !1;
          let bProp = b[propKey];
          if (!isEqualWithImpl(aProp, bProp, propKey, a, b, stack, areValuesEqual))
            return !1;
        }
        return !0;
      }
      default:
        return !1;
    }
  } finally {
    stack.delete(a), stack.delete(b);
  }
}

// ../../node_modules/es-toolkit/dist/predicate/isEqual.mjs
function isEqual(a, b) {
  return isEqualWith(a, b, noop);
}

// ../../node_modules/es-toolkit/dist/predicate/isPrimitive.mjs
function isPrimitive(value) {
  return value == null || typeof value != "object" && typeof value != "function";
}

// ../../node_modules/es-toolkit/dist/predicate/isTypedArray.mjs
function isTypedArray(x) {
  return ArrayBuffer.isView(x) && !(x instanceof DataView);
}




/***/ }),

/***/ "../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/csf/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  hX: () => (/* binding */ includeConditionalArg),
  bU: () => (/* binding */ isPreview),
  aj: () => (/* binding */ sanitize),
  Lr: () => (/* binding */ toId)
});

// UNUSED EXPORTS: combineTags, definePreview, definePreviewAddon, getCoreAnnotations, getStoryChildren, hasCoreAnnotations, isExportStory, isMeta, isStory, markAsComposedWithCoreAnnotations, parseKind, storyNameFromExport, toTestId

// EXTERNAL MODULE: ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-KUHYVZJN.js + 1 modules
var chunk_KUHYVZJN = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-KUHYVZJN.js");
;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-HYA6IF3K.js






// ../../node_modules/nanoid/index.browser.js
var nanoid = (size = 21) => crypto.getRandomValues(new Uint8Array(size)).reduce((id, byte) => (byte &= 63, byte < 36 ? id += byte.toString(36) : byte < 62 ? id += (byte - 26).toString(36).toUpperCase() : byte > 62 ? id += "-" : id += "_", id), "");

// ../../node_modules/valibot/dist/index.mjs
var store$4, DEFAULT_CONFIG = {
  lang: void 0,
  message: void 0,
  abortEarly: void 0,
  abortPipeEarly: void 0
};
function getGlobalConfig(config$1) {
  return !config$1 && !store$4 ? DEFAULT_CONFIG : {
    lang: config$1?.lang ?? store$4?.lang,
    message: config$1?.message,
    abortEarly: config$1?.abortEarly ?? store$4?.abortEarly,
    abortPipeEarly: config$1?.abortPipeEarly ?? store$4?.abortPipeEarly
  };
}
var store$3;
function getGlobalMessage(lang) {
  return store$3?.get(lang);
}
var store$2;
function getSchemaMessage(lang) {
  return store$2?.get(lang);
}
var store$1;
function getSpecificMessage(reference, lang) {
  return store$1?.get(reference)?.get(lang);
}
function _stringify(input) {
  let type = typeof input;
  return type === "string" ? `"${input}"` : type === "number" || type === "bigint" || type === "boolean" ? `${input}` : type === "object" || type === "function" ? (input && Object.getPrototypeOf(input)?.constructor?.name) ?? "null" : type;
}
function _addIssue(context, label, dataset, config$1, other) {
  let input = other && "input" in other ? other.input : dataset.value, expected = other?.expected ?? context.expects ?? null, received = other?.received ?? _stringify(input), issue = {
    kind: context.kind,
    type: context.type,
    input,
    expected,
    received,
    message: `Invalid ${label}: ${expected ? `Expected ${expected} but r` : "R"}eceived ${received}`,
    requirement: context.requirement,
    path: other?.path,
    issues: other?.issues,
    lang: config$1.lang,
    abortEarly: config$1.abortEarly,
    abortPipeEarly: config$1.abortPipeEarly
  }, isSchema = context.kind === "schema", message$1 = other?.message ?? context.message ?? getSpecificMessage(context.reference, issue.lang) ?? (isSchema ? getSchemaMessage(issue.lang) : null) ?? config$1.message ?? getGlobalMessage(issue.lang);
  message$1 !== void 0 && (issue.message = typeof message$1 == "function" ? message$1(issue) : message$1), isSchema && (dataset.typed = !1), dataset.issues ? dataset.issues.push(issue) : dataset.issues = [issue];
}
var _standardCache = /* @__PURE__ */ new WeakMap();
function _getStandardProps(context) {
  let cached = _standardCache.get(context);
  return cached || (cached = {
    version: 1,
    vendor: "valibot",
    validate(value$1) {
      return context["~run"]({ value: value$1 }, getGlobalConfig());
    }
  }, _standardCache.set(context, cached)), cached;
}
function _isValidObjectKey(object$1, key) {
  return Object.prototype.hasOwnProperty.call(object$1, key) && key !== "__proto__" && key !== "prototype" && key !== "constructor";
}
var EMOJI_REGEX = new RegExp("^(?:[\\u{1F1E6}-\\u{1F1FF}]{2}|\\u{1F3F4}[\\u{E0061}-\\u{E007A}]{2}[\\u{E0030}-\\u{E0039}\\u{E0061}-\\u{E007A}]{1,3}\\u{E007F}|(?:\\p{Emoji}\\uFE0F\\u20E3?|\\p{Emoji_Modifier_Base}\\p{Emoji_Modifier}?|(?![\\p{Emoji_Modifier_Base}\\u{1F1E6}-\\u{1F1FF}])\\p{Emoji_Presentation})(?:\\u200D(?:\\p{Emoji}\\uFE0F\\u20E3?|\\p{Emoji_Modifier_Base}\\p{Emoji_Modifier}?|(?![\\p{Emoji_Modifier_Base}\\u{1F1E6}-\\u{1F1FF}])\\p{Emoji_Presentation}))*)+$", "u");
function minValue(requirement, message$1) {
  return {
    kind: "validation",
    type: "min_value",
    reference: minValue,
    async: !1,
    expects: `>=${requirement instanceof Date ? requirement.toJSON() : _stringify(requirement)}`,
    requirement,
    message: message$1,
    "~run"(dataset, config$1) {
      return dataset.typed && !(dataset.value >= this.requirement) && _addIssue(this, "value", dataset, config$1, { received: dataset.value instanceof Date ? dataset.value.toJSON() : _stringify(dataset.value) }), dataset;
    }
  };
}
function safeInteger(message$1) {
  return {
    kind: "validation",
    type: "safe_integer",
    reference: safeInteger,
    async: !1,
    expects: null,
    requirement: Number.isSafeInteger,
    message: message$1,
    "~run"(dataset, config$1) {
      return dataset.typed && !this.requirement(dataset.value) && _addIssue(this, "safe integer", dataset, config$1), dataset;
    }
  };
}
function getFallback(schema, dataset, config$1) {
  return typeof schema.fallback == "function" ? schema.fallback(dataset, config$1) : schema.fallback;
}
function getDefault(schema, dataset, config$1) {
  return typeof schema.default == "function" ? schema.default(dataset, config$1) : schema.default;
}
function array(item, message$1) {
  return {
    kind: "schema",
    type: "array",
    reference: array,
    expects: "Array",
    async: !1,
    item,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      let input = dataset.value;
      if (Array.isArray(input)) {
        dataset.typed = !0, dataset.value = [];
        for (let key = 0; key < input.length; key++) {
          let value$1 = input[key], itemDataset = this.item["~run"]({ value: value$1 }, config$1);
          if (itemDataset.issues) {
            let pathItem = {
              type: "array",
              origin: "value",
              input,
              key,
              value: value$1
            };
            for (let issue of itemDataset.issues)
              issue.path ? issue.path.unshift(pathItem) : issue.path = [pathItem], dataset.issues?.push(issue);
            if (dataset.issues || (dataset.issues = itemDataset.issues), config$1.abortEarly) {
              dataset.typed = !1;
              break;
            }
          }
          itemDataset.typed || (dataset.typed = !1), dataset.value.push(itemDataset.value);
        }
      } else _addIssue(this, "type", dataset, config$1);
      return dataset;
    }
  };
}
function custom(check$1, message$1) {
  return {
    kind: "schema",
    type: "custom",
    reference: custom,
    expects: "unknown",
    async: !1,
    check: check$1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      return this.check(dataset.value) ? dataset.typed = !0 : _addIssue(this, "type", dataset, config$1), dataset;
    }
  };
}
function looseObject(entries$1, message$1) {
  return {
    kind: "schema",
    type: "loose_object",
    reference: looseObject,
    expects: "Object",
    async: !1,
    entries: entries$1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      let input = dataset.value;
      if (input && typeof input == "object") {
        dataset.typed = !0, dataset.value = {};
        for (let key in this.entries) {
          let valueSchema = this.entries[key];
          if (key in input || (valueSchema.type === "exact_optional" || valueSchema.type === "optional" || valueSchema.type === "nullish") && valueSchema.default !== void 0) {
            let value$1 = key in input ? input[key] : getDefault(valueSchema), valueDataset = valueSchema["~run"]({ value: value$1 }, config$1);
            if (valueDataset.issues) {
              let pathItem = {
                type: "object",
                origin: "value",
                input,
                key,
                value: value$1
              };
              for (let issue of valueDataset.issues)
                issue.path ? issue.path.unshift(pathItem) : issue.path = [pathItem], dataset.issues?.push(issue);
              if (dataset.issues || (dataset.issues = valueDataset.issues), config$1.abortEarly) {
                dataset.typed = !1;
                break;
              }
            }
            valueDataset.typed || (dataset.typed = !1), dataset.value[key] = valueDataset.value;
          } else if (valueSchema.fallback !== void 0) dataset.value[key] = getFallback(valueSchema);
          else if (valueSchema.type !== "exact_optional" && valueSchema.type !== "optional" && valueSchema.type !== "nullish" && (_addIssue(this, "key", dataset, config$1, {
            input: void 0,
            expected: `"${key}"`,
            path: [{
              type: "object",
              origin: "key",
              input,
              key,
              value: input[key]
            }]
          }), config$1.abortEarly))
            break;
        }
        if (!dataset.issues || !config$1.abortEarly)
          for (let key in input) _isValidObjectKey(input, key) && !(key in this.entries) && (dataset.value[key] = input[key]);
      } else _addIssue(this, "type", dataset, config$1);
      return dataset;
    }
  };
}
function number(message$1) {
  return {
    kind: "schema",
    type: "number",
    reference: number,
    expects: "number",
    async: !1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      return typeof dataset.value == "number" && !isNaN(dataset.value) ? dataset.typed = !0 : _addIssue(this, "type", dataset, config$1), dataset;
    }
  };
}
function object(entries$1, message$1) {
  return {
    kind: "schema",
    type: "object",
    reference: object,
    expects: "Object",
    async: !1,
    entries: entries$1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      let input = dataset.value;
      if (input && typeof input == "object") {
        dataset.typed = !0, dataset.value = {};
        for (let key in this.entries) {
          let valueSchema = this.entries[key];
          if (key in input || (valueSchema.type === "exact_optional" || valueSchema.type === "optional" || valueSchema.type === "nullish") && valueSchema.default !== void 0) {
            let value$1 = key in input ? input[key] : getDefault(valueSchema), valueDataset = valueSchema["~run"]({ value: value$1 }, config$1);
            if (valueDataset.issues) {
              let pathItem = {
                type: "object",
                origin: "value",
                input,
                key,
                value: value$1
              };
              for (let issue of valueDataset.issues)
                issue.path ? issue.path.unshift(pathItem) : issue.path = [pathItem], dataset.issues?.push(issue);
              if (dataset.issues || (dataset.issues = valueDataset.issues), config$1.abortEarly) {
                dataset.typed = !1;
                break;
              }
            }
            valueDataset.typed || (dataset.typed = !1), dataset.value[key] = valueDataset.value;
          } else if (valueSchema.fallback !== void 0) dataset.value[key] = getFallback(valueSchema);
          else if (valueSchema.type !== "exact_optional" && valueSchema.type !== "optional" && valueSchema.type !== "nullish" && (_addIssue(this, "key", dataset, config$1, {
            input: void 0,
            expected: `"${key}"`,
            path: [{
              type: "object",
              origin: "key",
              input,
              key,
              value: input[key]
            }]
          }), config$1.abortEarly))
            break;
        }
      } else _addIssue(this, "type", dataset, config$1);
      return dataset;
    }
  };
}
function optional(wrapped, default_) {
  return {
    kind: "schema",
    type: "optional",
    reference: optional,
    expects: `(${wrapped.expects} | undefined)`,
    async: !1,
    wrapped,
    default: default_,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      return dataset.value === void 0 && (this.default !== void 0 && (dataset.value = getDefault(this, dataset, config$1)), dataset.value === void 0) ? (dataset.typed = !0, dataset) : this.wrapped["~run"](dataset, config$1);
    }
  };
}
function record(key, value$1, message$1) {
  return {
    kind: "schema",
    type: "record",
    reference: record,
    expects: "Object",
    async: !1,
    key,
    value: value$1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      let input = dataset.value;
      if (input && typeof input == "object") {
        dataset.typed = !0, dataset.value = {};
        for (let entryKey in input) if (_isValidObjectKey(input, entryKey)) {
          let entryValue = input[entryKey], keyDataset = this.key["~run"]({ value: entryKey }, config$1);
          if (keyDataset.issues) {
            let pathItem = {
              type: "object",
              origin: "key",
              input,
              key: entryKey,
              value: entryValue
            };
            for (let issue of keyDataset.issues)
              issue.path = [pathItem], dataset.issues?.push(issue);
            if (dataset.issues || (dataset.issues = keyDataset.issues), config$1.abortEarly) {
              dataset.typed = !1;
              break;
            }
          }
          let valueDataset = this.value["~run"]({ value: entryValue }, config$1);
          if (valueDataset.issues) {
            let pathItem = {
              type: "object",
              origin: "value",
              input,
              key: entryKey,
              value: entryValue
            };
            for (let issue of valueDataset.issues)
              issue.path ? issue.path.unshift(pathItem) : issue.path = [pathItem], dataset.issues?.push(issue);
            if (dataset.issues || (dataset.issues = valueDataset.issues), config$1.abortEarly) {
              dataset.typed = !1;
              break;
            }
          }
          (!keyDataset.typed || !valueDataset.typed) && (dataset.typed = !1), keyDataset.typed && (dataset.value[keyDataset.value] = valueDataset.value);
        }
      } else _addIssue(this, "type", dataset, config$1);
      return dataset;
    }
  };
}
function string(message$1) {
  return {
    kind: "schema",
    type: "string",
    reference: string,
    expects: "string",
    async: !1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      return typeof dataset.value == "string" ? dataset.typed = !0 : _addIssue(this, "type", dataset, config$1), dataset;
    }
  };
}
function undefined_(message$1) {
  return {
    kind: "schema",
    type: "undefined",
    reference: undefined_,
    expects: "undefined",
    async: !1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      return dataset.value === void 0 ? dataset.typed = !0 : _addIssue(this, "type", dataset, config$1), dataset;
    }
  };
}
function unknown() {
  return {
    kind: "schema",
    type: "unknown",
    reference: unknown,
    expects: "unknown",
    async: !1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset) {
      return dataset.typed = !0, dataset;
    }
  };
}
function void_(message$1) {
  return {
    kind: "schema",
    type: "void",
    reference: void_,
    expects: "void",
    async: !1,
    message: message$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      return dataset.value === void 0 ? dataset.typed = !0 : _addIssue(this, "type", dataset, config$1), dataset;
    }
  };
}
function pipe(...pipe$1) {
  return {
    ...pipe$1[0],
    pipe: pipe$1,
    get "~standard"() {
      return _getStandardProps(this);
    },
    "~run"(dataset, config$1) {
      for (let item of pipe$1) if (item.kind !== "metadata") {
        if (dataset.issues && (item.kind === "schema" || item.kind === "transformation")) {
          dataset.typed = !1;
          break;
        }
        (!dataset.issues || !config$1.abortEarly && !config$1.abortPipeEarly) && (dataset = item["~run"](dataset, config$1));
      }
      return dataset;
    }
  };
}
function safeParse(schema, input, config$1) {
  let dataset = schema["~run"]({ value: input }, getGlobalConfig(config$1));
  return {
    typed: dataset.typed,
    success: !dataset.issues,
    output: dataset.value,
    issues: dataset.issues
  };
}

// src/shared/open-service/service-channel.ts
var SERVICE_SYNC_START = "services:sync-start", SERVICE_SYNC_START_REPLY = "services:sync-start-reply", SERVICE_PATCHES = "services:patches", SERVICE_COMMAND_INVOKE = "services:command-invoke", SERVICE_COMMAND_ACK = "services:command-ack", SERVICE_COMMAND_RESULT = "services:command-result", SERVICE_COMMAND_ERROR = "services:command-error", stateSnapshotSchema = custom(
  (value) => typeof value == "object" && value !== null && !Array.isArray(value)
), syncStartSchema = object({
  serviceId: string(),
  clientId: string()
}), stampedSnapshotSchema = object({
  serviceId: string(),
  state: stateSnapshotSchema,
  version: pipe(number(), safeInteger(), minValue(0)),
  clientId: string()
}), commandInvokeSchema = object({
  serviceId: string(),
  commandName: string(),
  input: optional(unknown()),
  callId: string(),
  clientId: string()
}), commandAckSchema = object({
  serviceId: string(),
  callId: string(),
  clientId: string()
}), commandResultSchema = object({
  serviceId: string(),
  callId: string(),
  result: optional(unknown()),
  clientId: string()
}), commandErrorSchema = object({
  serviceId: string(),
  callId: string(),
  error: custom(
    (value) => typeof value == "object" && value !== null && !Array.isArray(value)
  ),
  clientId: string()
});
function generateClientId() {
  return nanoid();
}

// ../../node_modules/@preact/signals-core/dist/signals-core.module.js
var i = /* @__PURE__ */ Symbol.for("preact-signals");
function t() {
  if (s > 1)
    s--;
  else {
    var i3, t2 = !1;
    for ((function() {
      var i4 = c;
      for (c = void 0; i4 !== void 0; )
        i4.S.v === i4.v && (i4.S.i = i4.i), i4 = i4.o;
    })(); h !== void 0; ) {
      var n3 = h;
      for (h = void 0, v++; n3 !== void 0; ) {
        var r2 = n3.u;
        if (n3.u = void 0, n3.f &= -3, !(8 & n3.f) && w(n3)) try {
          n3.c();
        } catch (n4) {
          t2 || (i3 = n4, t2 = !0);
        }
        n3 = r2;
      }
    }
    if (v = 0, s--, t2) throw i3;
  }
}
function n(i3) {
  if (s > 0) return i3();
  e = ++u, s++;
  try {
    return i3();
  } finally {
    t();
  }
}
var r = void 0;
function o(i3) {
  var t2 = r;
  r = void 0;
  try {
    return i3();
  } finally {
    r = t2;
  }
}
var f, h = void 0, s = 0, v = 0, u = 0, e = 0, c = void 0, d = 0;
function a(i3) {
  if (r !== void 0) {
    var t2 = i3.n;
    if (t2 === void 0 || t2.t !== r)
      return t2 = { i: 0, S: i3, p: r.s, n: void 0, t: r, e: void 0, x: void 0, r: t2 }, r.s !== void 0 && (r.s.n = t2), r.s = t2, i3.n = t2, 32 & r.f && i3.S(t2), t2;
    if (t2.i === -1)
      return t2.i = 0, t2.n !== void 0 && (t2.n.p = t2.p, t2.p !== void 0 && (t2.p.n = t2.n), t2.p = r.s, t2.n = void 0, r.s.n = t2, r.s = t2), t2;
  }
}
function l(i3, t2) {
  this.v = i3, this.i = 0, this.n = void 0, this.t = void 0, this.l = 0, this.W = t2?.watched, this.Z = t2?.unwatched, this.name = t2?.name;
}
l.prototype.brand = i;
l.prototype.h = function() {
  return !0;
};
l.prototype.S = function(i3) {
  var t2 = this, n3 = this.t;
  n3 !== i3 && i3.e === void 0 && (i3.x = n3, this.t = i3, n3 !== void 0 ? n3.e = i3 : o(function() {
    var i4;
    (i4 = t2.W) == null || i4.call(t2);
  }));
};
l.prototype.U = function(i3) {
  var t2 = this;
  if (this.t !== void 0) {
    var n3 = i3.e, r2 = i3.x;
    n3 !== void 0 && (n3.x = r2, i3.e = void 0), r2 !== void 0 && (r2.e = n3, i3.x = void 0), i3 === this.t && (this.t = r2, r2 === void 0 && o(function() {
      var i4;
      (i4 = t2.Z) == null || i4.call(t2);
    }));
  }
};
l.prototype.subscribe = function(i3) {
  var t2 = this;
  return j(function() {
    var n3 = t2.value, o3 = r;
    r = void 0;
    try {
      i3(n3);
    } finally {
      r = o3;
    }
  }, { name: "sub" });
};
l.prototype.valueOf = function() {
  return this.value;
};
l.prototype.toString = function() {
  return this.value + "";
};
l.prototype.toJSON = function() {
  return this.value;
};
l.prototype.peek = function() {
  var i3 = this;
  return o(function() {
    return i3.value;
  });
};
Object.defineProperty(l.prototype, "value", { get: function() {
  var i3 = a(this);
  return i3 !== void 0 && (i3.i = this.i), this.v;
}, set: function(i3) {
  if (i3 !== this.v) {
    if (v > 100) throw new Error("Cycle detected");
    (function(i4) {
      s !== 0 && v === 0 && i4.l !== e && (i4.l = e, c = { S: i4, v: i4.v, i: i4.i, o: c });
    })(this), this.v = i3, this.i++, d++, s++;
    try {
      for (var n3 = this.t; n3 !== void 0; n3 = n3.x) n3.t.N();
    } finally {
      t();
    }
  }
} });
function y(i3, t2) {
  return new l(i3, t2);
}
function w(i3) {
  for (var t2 = i3.s; t2 !== void 0; t2 = t2.n) if (t2.S.i !== t2.i || !t2.S.h() || t2.S.i !== t2.i) return !0;
  return !1;
}
function _(i3) {
  for (var t2 = i3.s; t2 !== void 0; t2 = t2.n) {
    var n3 = t2.S.n;
    if (n3 !== void 0 && (t2.r = n3), t2.S.n = t2, t2.i = -1, t2.n === void 0) {
      i3.s = t2;
      break;
    }
  }
}
function b(i3) {
  for (var t2 = i3.s, n3 = void 0; t2 !== void 0; ) {
    var r2 = t2.p;
    t2.i === -1 ? (t2.S.U(t2), r2 !== void 0 && (r2.n = t2.n), t2.n !== void 0 && (t2.n.p = r2)) : n3 = t2, t2.S.n = t2.r, t2.r !== void 0 && (t2.r = void 0), t2 = r2;
  }
  i3.s = n3;
}
function p(i3, t2) {
  l.call(this, void 0), this.x = i3, this.s = void 0, this.g = d - 1, this.f = 4, this.W = t2?.watched, this.Z = t2?.unwatched, this.name = t2?.name;
}
p.prototype = new l();
p.prototype.h = function() {
  if (this.f &= -3, 1 & this.f) return !1;
  if ((36 & this.f) == 32 || (this.f &= -5, this.g === d)) return !0;
  if (this.g = d, this.f |= 1, this.i > 0 && !w(this))
    return this.f &= -2, !0;
  var i3 = r;
  try {
    _(this), r = this;
    var t2 = this.x();
    (16 & this.f || this.v !== t2 || this.i === 0) && (this.v = t2, this.f &= -17, this.i++);
  } catch (i4) {
    this.v = i4, this.f |= 16, this.i++;
  }
  return r = i3, b(this), this.f &= -2, !0;
};
p.prototype.S = function(i3) {
  if (this.t === void 0) {
    this.f |= 36;
    for (var t2 = this.s; t2 !== void 0; t2 = t2.n) t2.S.S(t2);
  }
  l.prototype.S.call(this, i3);
};
p.prototype.U = function(i3) {
  if (this.t !== void 0 && (l.prototype.U.call(this, i3), this.t === void 0)) {
    this.f &= -33;
    for (var t2 = this.s; t2 !== void 0; t2 = t2.n) t2.S.U(t2);
  }
};
p.prototype.N = function() {
  if (!(2 & this.f)) {
    this.f |= 6;
    for (var i3 = this.t; i3 !== void 0; i3 = i3.x) i3.t.N();
  }
};
Object.defineProperty(p.prototype, "value", { get: function() {
  if (1 & this.f) throw new Error("Cycle detected");
  var i3 = a(this);
  if (this.h(), i3 !== void 0 && (i3.i = this.i), 16 & this.f) throw this.v;
  return this.v;
} });
function g(i3, t2) {
  return new p(i3, t2);
}
function S(i3) {
  var n3 = i3.m;
  if (i3.m = void 0, typeof n3 == "function") {
    s++;
    var o3 = r;
    r = void 0;
    try {
      n3();
    } catch (t2) {
      throw i3.f &= -2, i3.f |= 8, m(i3), t2;
    } finally {
      r = o3, t();
    }
  }
}
function m(i3) {
  for (var t2 = i3.s; t2 !== void 0; t2 = t2.n) t2.S.U(t2);
  i3.x = void 0, i3.s = void 0, S(i3);
}
function x(i3) {
  if (r !== this) throw new Error("Out-of-order effect");
  b(this), r = i3, this.f &= -2, 8 & this.f && m(this), t();
}
function E(i3, t2) {
  this.x = i3, this.m = void 0, this.s = void 0, this.u = void 0, this.f = 32, this.name = t2?.name, f && f.push(this);
}
E.prototype.c = function() {
  var i3 = this.S();
  try {
    if (8 & this.f || this.x === void 0) return;
    var t2 = this.x();
    typeof t2 == "function" && (this.m = t2);
  } finally {
    i3();
  }
};
E.prototype.S = function() {
  if (1 & this.f) throw new Error("Cycle detected");
  this.f |= 1, this.f &= -9, S(this), _(this), s++;
  var i3 = r;
  return r = this, x.bind(this, i3);
};
E.prototype.N = function() {
  2 & this.f || (this.f |= 2, this.u = h, h = this);
};
E.prototype.d = function() {
  this.f |= 8, 1 & this.f || m(this);
};
E.prototype.dispose = function() {
  this.d();
};
function j(i3, t2) {
  var n3 = new E(i3, t2);
  try {
    n3.c();
  } catch (i4) {
    throw n3.d(), i4;
  }
  var r2 = n3.d.bind(n3);
  return r2[Symbol.dispose] = r2, r2;
}

// ../../node_modules/deepsignal/core/dist/deepsignal-core.module.js
var n2 = /* @__PURE__ */ new WeakMap(), a2 = /* @__PURE__ */ new WeakMap(), o2 = /* @__PURE__ */ new WeakMap(), s2 = /* @__PURE__ */ new WeakSet(), u2 = /* @__PURE__ */ new WeakMap(), f2 = /^\$/, c2 = Object.getOwnPropertyDescriptor, i2 = !1, l2 = function(e2) {
  if (!R(e2)) throw new Error("This object can't be observed.");
  return a2.has(e2) || a2.set(e2, p2(e2, w2)), a2.get(e2);
};
var p2 = function(e2, t2) {
  var r2 = new Proxy(e2, t2);
  return s2.add(r2), r2;
}, v2 = function() {
  throw new Error("Don't mutate the signals directly.");
}, y2 = function(e2) {
  return function(s3, u3, l3) {
    var g2;
    if (i2) return Reflect.get(s3, u3, l3);
    var h2 = e2 || u3[0] === "$";
    if (!e2 && h2 && Array.isArray(s3)) {
      if (u3 === "$") return o2.has(s3) || o2.set(s3, p2(s3, d2)), o2.get(s3);
      h2 = u3 === "$length";
    }
    n2.has(l3) || n2.set(l3, /* @__PURE__ */ new Map());
    var v3 = n2.get(l3), y3 = h2 ? u3.replace(f2, "") : u3;
    if (v3.has(y3) || typeof ((g2 = c2(s3, y3)) == null ? void 0 : g2.get) != "function") {
      var m3 = Reflect.get(s3, y3, l3);
      if (h2 && typeof m3 == "function") return;
      if (typeof y3 == "symbol" && b2.has(y3)) return m3;
      v3.has(y3) || (R(m3) && (a2.has(m3) || a2.set(m3, p2(m3, w2)), m3 = a2.get(m3)), v3.set(y3, y(m3)));
    } else v3.set(y3, g(function() {
      return Reflect.get(s3, y3, l3);
    }));
    return h2 ? v3.get(y3) : v3.get(y3).value;
  };
}, w2 = { get: y2(!1), set: function(r2, o3, s3, i3) {
  var l3;
  if (typeof ((l3 = c2(r2, o3)) == null ? void 0 : l3.set) == "function") return Reflect.set(r2, o3, s3, i3);
  n2.has(i3) || n2.set(i3, /* @__PURE__ */ new Map());
  var g2 = n2.get(i3);
  if (o3[0] === "$") {
    s3 instanceof l || v2();
    var h2 = o3.replace(f2, "");
    return g2.set(h2, s3), Reflect.set(r2, h2, s3.peek(), i3);
  }
  var y3 = s3;
  R(s3) && (a2.has(s3) || a2.set(s3, p2(s3, w2)), y3 = a2.get(s3));
  var d3 = !(o3 in r2), b3 = Reflect.set(r2, o3, s3, i3);
  return g2.has(o3) ? g2.get(o3).value = y3 : g2.set(o3, y(y3)), d3 && u2.has(r2) && u2.get(r2).value++, Array.isArray(r2) && g2.has("length") && (g2.get("length").value = r2.length), b3;
}, deleteProperty: function(e2, t2) {
  t2[0] === "$" && v2();
  var r2 = n2.get(a2.get(e2)), o3 = Reflect.deleteProperty(e2, t2);
  return r2 && r2.has(t2) && (r2.get(t2).value = void 0), u2.has(e2) && u2.get(e2).value++, o3;
}, ownKeys: function(e2) {
  return u2.has(e2) || u2.set(e2, y(0)), u2._ = u2.get(e2).value, Reflect.ownKeys(e2);
} }, d2 = { get: y2(!0), set: v2, deleteProperty: v2 }, b2 = new Set(Object.getOwnPropertyNames(Symbol).map(function(e2) {
  return Symbol[e2];
}).filter(function(e2) {
  return typeof e2 == "symbol";
})), m2 = /* @__PURE__ */ new Set([Object, Array]), R = function(e2) {
  return typeof e2 == "object" && e2 !== null && m2.has(e2.constructor) && !s2.has(e2);
};

// src/shared/open-service/service-validation.ts
function rethrowAsync(error) {
  queueMicrotask(() => {
    throw error;
  });
}
async function validateSchema(schema, value, meta) {
  let validationResult = await schema["~standard"].validate(value);
  if (validationResult.issues)
    throw new OpenServiceValidationError({ ...meta, issues: validationResult.issues });
  return validationResult.value;
}
function validateSchemaSync(schema, value, meta) {
  let validationResult = schema["~standard"].validate(value);
  if (validationResult instanceof Promise)
    throw new OpenServiceAsyncSchemaError({
      kind: meta.kind,
      serviceId: meta.serviceId,
      name: meta.name,
      phase: meta.phase
    });
  if (validationResult.issues)
    throw new OpenServiceValidationError({ ...meta, issues: validationResult.issues });
  return validationResult.value;
}

// src/shared/open-service/query-runtime.ts
var MAX_DRAIN_ITERATIONS = 32, inFlightLoads = /* @__PURE__ */ new Map(), activeHandlerLoadSession, EMPTY_SET = /* @__PURE__ */ new Set();
function stableHash(value) {
  let encode = (raw) => {
    if (raw === void 0)
      return { __t: "undefined" };
    if (raw === null || typeof raw != "object")
      return raw;
    if (Array.isArray(raw))
      return raw.map(encode);
    let sorted = {};
    for (let k of Object.keys(raw).sort())
      sorted[k] = encode(raw[k]);
    return { __t: "object", value: sorted };
  };
  return JSON.stringify(encode(value));
}
function makeLoadKey(serviceId, queryName, validatedInput) {
  return `${serviceId}::${queryName}::${stableHash(validatedInput)}`;
}
function surfaceRejections(settlements) {
  let rejections = settlements.filter((s3) => s3.status === "rejected");
  if (rejections.length === 0)
    return;
  let [first, ...rest] = rejections.map((r2) => r2.reason);
  if (rest.length > 0 && first instanceof Error && Reflect.get(first, "cause") === void 0)
    try {
      Reflect.set(first, "cause", { aggregated: rest });
    } catch {
    }
  throw first;
}
async function drainCollector(collector, settledKeys, serviceId, queryName) {
  let iterations = 0;
  for (; collector.size > 0; ) {
    if (iterations++ > MAX_DRAIN_ITERATIONS)
      throw new OpenServiceLoadedDrainExceededError({
        serviceId,
        name: queryName,
        iterations: MAX_DRAIN_ITERATIONS
      });
    let pending = [...collector];
    collector.clear();
    let settlements = await Promise.allSettled(pending.map((entry) => entry.promise));
    if (settledKeys)
      for (let entry of pending)
        settledKeys.add(entry.key);
    surfaceRejections(settlements);
  }
}
function detachSnapshot(value) {
  return value === null || typeof value != "object" ? value : JSON.parse(JSON.stringify(value));
}
function validateQueryInput(refs, queryName, queryDef, input) {
  return validateSchemaSync(queryDef.input, input, {
    kind: "query",
    serviceId: refs.serviceId,
    name: queryName,
    phase: "input"
  });
}
function validateQueryOutput(refs, queryName, queryDef, output) {
  return validateSchemaSync(queryDef.output, output, {
    kind: "query",
    serviceId: refs.serviceId,
    name: queryName,
    phase: "output"
  });
}
function runHandlerSync(refs, queryName, queryDef, validatedInput, selfQueries, getService2) {
  if (!queryDef.handler)
    throw new OpenServiceUnimplementedOperationError({
      kind: "query",
      serviceId: refs.serviceId,
      name: queryName
    });
  let handlerCtx = { self: {
    get state() {
      return refs.state;
    },
    queries: selfQueries
  }, getService: getService2 };
  return queryDef.handler(validatedInput, handlerCtx);
}
function createQueryGet(refs, queryName, queryDef, selfQueries, fireLoad) {
  return function(input) {
    let validatedInput = validateQueryInput(
      refs,
      queryName,
      queryDef,
      arguments.length === 0 ? void 0 : input
    );
    return queryDef.load && fireLoad?.(validatedInput), validateQueryOutput(
      refs,
      queryName,
      queryDef,
      runHandlerSync(
        refs,
        queryName,
        queryDef,
        validatedInput,
        selfQueries,
        refs.registryApi.getService
      )
    );
  };
}
function triggerLoad(refs, queryName, queryDef, validatedInput, loadKey, parentAncestorChain) {
  let existing = inFlightLoads.get(loadKey);
  if (existing)
    return existing;
  let extendedChain = new Set(parentAncestorChain);
  extendedChain.add(loadKey);
  let promise = Promise.resolve().then(() => runLoadBody(refs, queryName, queryDef, validatedInput, extendedChain)).finally(() => {
    inFlightLoads.get(loadKey) === promise && inFlightLoads.delete(loadKey);
  });
  return inFlightLoads.set(loadKey, promise), promise;
}
async function runLoadBody(refs, queryName, queryDef, validatedInput, ancestorChain) {
  if (!queryDef.load)
    return;
  let collector = /* @__PURE__ */ new Set(), wrappedQueries = buildLoadWrappedQueries(refs, ancestorChain, collector), loadCtx = { self: {
    get state() {
      return refs.state;
    },
    queries: wrappedQueries,
    commands: refs.getLoadCommands()
  }, getService: refs.registryApi.getService };
  await Promise.resolve(queryDef.load(validatedInput, loadCtx)), await drainCollector(collector, void 0, refs.serviceId, queryName);
}
async function runReactiveLoad(refs, queryName, queryDef, validatedInput, isCurrent) {
  if (!queryDef.load)
    return;
  let loadCtx = { self: {
    get state() {
      return refs.state;
    },
    // Reactive-load reads must keep dependency loads warm. `.get()` on a default query is a pure
    // read and never fires a load, so the body sees fire-and-forget wrappers instead: reading a
    // dependency triggers its load (deduped while in flight) without awaiting a drain.
    queries: refs.reactiveLoadQueries,
    commands: refs.buildGatedCommands(isCurrent)
  }, getService: refs.registryApi.getService };
  await Promise.resolve(queryDef.load(validatedInput, loadCtx));
}
function buildReactiveLoadQueries(refs) {
  let wrappedQueries = {};
  for (let [name, queryDef] of refs.queryDefinitions) {
    let defaultQuery = refs.defaultQueries[name], get = createQueryGet(refs, name, queryDef, wrappedQueries, (validatedInput) => {
      let loadKey = makeLoadKey(refs.serviceId, name, validatedInput);
      triggerLoad(refs, name, queryDef, validatedInput, loadKey, EMPTY_SET).catch(rethrowAsync);
    });
    wrappedQueries[name] = {
      get,
      loaded: defaultQuery.loaded,
      subscribe: defaultQuery.subscribe
    };
  }
  return wrappedQueries;
}
function buildLoadWrappedQueries(refs, ancestorChain, collector) {
  let wrappedQueries = {};
  for (let [name, queryDef] of refs.queryDefinitions) {
    let defaultQuery = refs.defaultQueries[name], wrapped = {
      get: createQueryGet(refs, name, queryDef, wrappedQueries, (validatedInput) => {
        let loadKey = makeLoadKey(refs.serviceId, name, validatedInput), promise = triggerLoad(refs, name, queryDef, validatedInput, loadKey, ancestorChain);
        ancestorChain.has(loadKey) || collector.add({ key: loadKey, promise });
      }),
      loaded(input) {
        return runLoaded(
          refs,
          name,
          queryDef,
          arguments.length === 0 ? void 0 : input,
          ancestorChain
        );
      },
      subscribe: defaultQuery.subscribe
    };
    wrappedQueries[name] = wrapped;
  }
  return wrappedQueries;
}
async function runLoaded(refs, queryName, queryDef, rawInput, parentAncestorChain = EMPTY_SET) {
  let validatedInput = validateQueryInput(refs, queryName, queryDef, rawInput), loadKey = makeLoadKey(refs.serviceId, queryName, validatedInput), ancestorChain = new Set(parentAncestorChain);
  ancestorChain.add(loadKey);
  let session = {
    ancestorChain,
    collector: /* @__PURE__ */ new Set(),
    settledKeys: /* @__PURE__ */ new Set()
  };
  if (queryDef.load && !parentAncestorChain.has(loadKey)) {
    let promise = triggerLoad(
      refs,
      queryName,
      queryDef,
      validatedInput,
      loadKey,
      parentAncestorChain
    );
    session.collector.add({ key: loadKey, promise });
  }
  let iterations = 0, hasMoreWork = !0;
  for (; hasMoreWork; ) {
    if (iterations++ > MAX_DRAIN_ITERATIONS)
      throw new OpenServiceLoadedDrainExceededError({
        serviceId: refs.serviceId,
        name: queryName,
        iterations: MAX_DRAIN_ITERATIONS
      });
    for (; session.collector.size > 0; ) {
      let pending = [...session.collector];
      session.collector.clear();
      let settlements = await Promise.allSettled(pending.map((entry) => entry.promise));
      for (let entry of pending)
        session.settledKeys.add(entry.key);
      surfaceRejections(settlements);
    }
    let previousSession2 = activeHandlerLoadSession;
    activeHandlerLoadSession = session;
    try {
      runHandlerSync(
        refs,
        queryName,
        queryDef,
        validatedInput,
        refs.defaultQueries,
        refs.registryApi.getService
      );
    } catch {
    } finally {
      activeHandlerLoadSession = previousSession2;
    }
    hasMoreWork = session.collector.size > 0;
  }
  let previousSession = activeHandlerLoadSession;
  activeHandlerLoadSession = session;
  try {
    return validateQueryOutput(
      refs,
      queryName,
      queryDef,
      runHandlerSync(
        refs,
        queryName,
        queryDef,
        validatedInput,
        refs.defaultQueries,
        refs.registryApi.getService
      )
    );
  } finally {
    activeHandlerLoadSession = previousSession;
  }
}
function createDefaultQuery(refs, queryName, queryDef) {
  let resolveInput = (input, argsLength) => argsLength === 0 ? void 0 : input;
  return {
    get: createQueryGet(refs, queryName, queryDef, refs.defaultQueries, (validatedInput) => {
      let session = activeHandlerLoadSession;
      if (!session)
        return;
      let loadKey = makeLoadKey(refs.serviceId, queryName, validatedInput);
      if (!session.ancestorChain.has(loadKey) && !session.settledKeys.has(loadKey)) {
        let promise = triggerLoad(
          refs,
          queryName,
          queryDef,
          validatedInput,
          loadKey,
          session.ancestorChain
        );
        session.collector.add({ key: loadKey, promise });
      }
    }),
    loaded(input) {
      return runLoaded(refs, queryName, queryDef, resolveInput(input, arguments.length));
    },
    subscribe: ((...args) => {
      if (args.length === 1 && typeof args[0] == "function")
        return subscribeToQuery(
          refs,
          queryName,
          queryDef,
          void 0,
          void 0,
          args[0]
        );
      if (args.length === 2 && typeof args[0] == "function" && typeof args[1] == "function")
        return subscribeToQuery(
          refs,
          queryName,
          queryDef,
          void 0,
          args[0],
          args[1]
        );
      let [input, selectorOrCallback, maybeCallback] = args;
      return subscribeToQuery(
        refs,
        queryName,
        queryDef,
        input,
        maybeCallback ? selectorOrCallback : void 0,
        maybeCallback ?? selectorOrCallback
      );
    })
  };
}
function subscribeToQuery(refs, queryName, queryDef, rawInput, selector, callback) {
  let active = !0, validatedInput;
  try {
    validatedInput = validateQueryInput(refs, queryName, queryDef, rawInput);
  } catch (error) {
    return callback(
      buildQueryState(void 0, { status: "error", error: toError(error), loadStatus: "idle" })
    ), () => {
      active = !1;
    };
  }
  let lifecycle = y({
    status: queryDef.load ? "pending" : "success",
    error: void 0,
    loadStatus: "idle"
  }), loadTeardown;
  if (queryDef.load) {
    let epoch = 0;
    loadTeardown = j(() => {
      let myEpoch = ++epoch, isCurrent = () => myEpoch === epoch, previous = o(() => lifecycle.value);
      lifecycle.value = {
        status: previous.status,
        error: previous.error,
        loadStatus: "loading"
      }, runReactiveLoad(refs, queryName, queryDef, validatedInput, isCurrent).then(
        () => {
          isCurrent() && (lifecycle.value = { status: "success", error: void 0, loadStatus: "idle" });
        },
        (error) => {
          isCurrent() && (lifecycle.value = { status: "error", error: toError(error), loadStatus: "idle" });
        }
      );
    });
  }
  let comp = g(() => {
    let output = runHandlerSync(
      refs,
      queryName,
      queryDef,
      validatedInput,
      refs.defaultQueries,
      refs.registryApi.getService
    );
    if (selector) {
      let validated = o(() => validateQueryOutput(refs, queryName, queryDef, output));
      return selector(output), detachSnapshot(selector(validated));
    }
    return detachSnapshot(validateQueryOutput(refs, queryName, queryDef, output));
  }), hasEmitted = !1, lastEmitted, lastData, teardown = j(() => {
    let life = lifecycle.value, data, status = life.status, error = life.error;
    try {
      data = comp.value, lastData = data;
    } catch (handlerError) {
      data = lastData, status = "error", error = toError(handlerError);
    }
    if (!active)
      return;
    let state = buildQueryState(data, { status, error, loadStatus: life.loadStatus });
    hasEmitted && isEqual(state, lastEmitted) || (hasEmitted = !0, lastEmitted = state, callback(state));
  });
  return () => {
    active = !1, teardown(), loadTeardown?.();
  };
}
function buildQueries(refs) {
  let result = {};
  for (let [name, queryDef] of refs.queryDefinitions)
    result[name] = createDefaultQuery(refs, name, queryDef);
  return result;
}

// src/shared/open-service/service-sync.ts
function isNewer(incoming, local) {
  return incoming.version !== local.version ? incoming.version > local.version : incoming.clientId > local.clientId;
}
var FORBIDDEN_KEYS = /* @__PURE__ */ new Set(["__proto__", "constructor", "prototype"]);
function isPlainObject(value) {
  return typeof value == "object" && value !== null && !Array.isArray(value);
}
function applyStatePatch(target, source, options) {
  if (!options.preserveMissingKeys)
    for (let key of Object.keys(target))
      FORBIDDEN_KEYS.has(key) || Object.prototype.hasOwnProperty.call(source, key) || delete target[key];
  for (let key of Object.keys(source)) {
    if (FORBIDDEN_KEYS.has(key))
      continue;
    let sourceValue = source[key], tarvalue = target[key];
    isPlainObject(sourceValue) && isPlainObject(tarvalue) ? applyStatePatch(tarvalue, sourceValue, options) : tarvalue !== sourceValue && (target[key] = sourceValue);
  }
}
function createSnapshotReconciler(options) {
  let { setState, initialStamp } = options, localStamp = initialStamp;
  return {
    get stamp() {
      return localStamp;
    },
    advanceLocal(clientId) {
      return localStamp = { version: localStamp.version + 1, clientId }, localStamp;
    },
    tryAdopt(incoming, state) {
      return isNewer(incoming, localStamp) ? (localStamp = { version: incoming.version, clientId: incoming.clientId }, setState((current) => applyStatePatch(current, state, { preserveMissingKeys: !1 })), !0) : !1;
    }
  };
}

// src/shared/open-service/service-runtime.ts
function normalizeStaticStoragePath(serviceId, name, rawPath) {
  let segments = rawPath.replaceAll("\\", "/").split("/").filter((segment) => segment.length > 0 && segment !== ".");
  if (segments.length === 0 || segments.some((segment) => segment === ".."))
    throw new OpenServiceInvalidStaticPathError({ serviceId, name, path: rawPath });
  return segments.join("/");
}
function resolveStaticPath(serviceId, name, queryDef, input) {
  let rawPath = queryDef.staticPath(input), relativePath = normalizeStaticStoragePath(serviceId, name, rawPath);
  return `${serviceId}/${relativePath}`;
}
function createCommandSelf(state) {
  return {
    get state() {
      return state;
    },
    setState(mutate) {
      n(() => {
        mutate(state);
      });
    },
    queries: {},
    commands: {}
  };
}
function buildCommands(serviceId, commands, createCommandCtx) {
  return Object.fromEntries(
    Object.entries(commands).map(([name, def]) => [
      name,
      async (input) => {
        if (!def.handler)
          throw new OpenServiceUnimplementedOperationError({
            kind: "command",
            serviceId,
            name
          });
        let validatedInput = await validateSchema(def.input, input, {
          kind: "command",
          serviceId,
          name,
          phase: "input"
        }), output = await def.handler(validatedInput, createCommandCtx());
        return validateSchema(def.output, output, {
          kind: "command",
          serviceId,
          name,
          phase: "output"
        });
      }
    ])
  );
}
function buildQueryDefinitionsWithStaticLoader(serviceId, queries, staticLoader, setState) {
  return new Map(
    Object.entries(queries).map(([name, queryDef]) => {
      if (!queryDef.staticPath)
        return [name, queryDef];
      let { staticPath } = queryDef;
      return [
        name,
        {
          ...queryDef,
          load: async (input) => {
            let logicalPath = resolveStaticPath(serviceId, name, { staticPath }, input), snapshot = await staticLoader(logicalPath, {
              serviceId,
              queryName: name,
              input
            });
            setState((state) => {
              applyStatePatch(state, snapshot, {
                preserveMissingKeys: !0
              });
            });
          }
        }
      ];
    })
  );
}
function createServiceRuntime(def, runtimeOptions, initialState = def.initialState) {
  let rawState = initialState, state = l2(rawState), getStateSnapshot = () => structuredClone(rawState), commandSelf = createCommandSelf(state), { registryApi, staticLoader } = runtimeOptions, createCommandCtx = () => ({
    self: commandSelf,
    getService: registryApi.getService
  }), commands = buildCommands(def.id, def.commands, createCommandCtx);
  commandSelf.commands = commands;
  let loadCommands = commands, remoteCommandNames = /* @__PURE__ */ new Set(), queryDefinitions = staticLoader ? buildQueryDefinitionsWithStaticLoader(
    def.id,
    def.queries,
    staticLoader,
    commandSelf.setState
  ) : new Map(
    Object.entries(def.queries)
  ), defaultQueries = {}, reactiveLoadQueries = {}, buildGatedCommands = (isCurrent) => {
    let gatedSelf = {
      get state() {
        return state;
      },
      setState(mutate) {
        isCurrent() && n(() => {
          mutate(state);
        });
      },
      queries: defaultQueries,
      commands: {}
    }, gated = buildCommands(def.id, def.commands, () => ({
      self: gatedSelf,
      getService: registryApi.getService
    }));
    return gatedSelf.commands = gated, remoteCommandNames.size === 0 ? gated : Object.fromEntries(
      Object.keys(def.commands).map((name) => [
        name,
        remoteCommandNames.has(name) ? loadCommands[name] : gated[name]
      ])
    );
  }, refs = {
    serviceId: def.id,
    commandSelf,
    state,
    registryApi,
    queryDefinitions,
    defaultQueries,
    reactiveLoadQueries,
    getLoadCommands: () => loadCommands,
    buildGatedCommands
  }, builtQueries = buildQueries(refs);
  for (let [name, query] of Object.entries(builtQueries))
    defaultQueries[name] = query;
  for (let [name, query] of Object.entries(buildReactiveLoadQueries(refs)))
    reactiveLoadQueries[name] = query;
  commandSelf.queries = defaultQueries;
  let queries = defaultQueries, queryCtx = { self: {
    get state() {
      return state;
    },
    queries: defaultQueries
  }, getService: registryApi.getService }, loadCtxForStatic = {
    self: {
      get state() {
        return state;
      },
      queries: defaultQueries,
      commands
    },
    getService: registryApi.getService
  };
  return {
    getStateSnapshot,
    commandSelf,
    queryCtx,
    loadCtxForStatic,
    commands,
    queries,
    runLoadOnce: async (queryName, validatedInput) => {
      let queryDef = queryDefinitions.get(queryName);
      if (!queryDef || !queryDef.load)
        return;
      let loadKey = makeLoadKey(def.id, queryName, validatedInput), ancestorChain = /* @__PURE__ */ new Set([loadKey]), previous = inFlightLoads.get(loadKey), promise = Promise.resolve().then(() => runLoadBody(refs, queryName, queryDef, validatedInput, ancestorChain)).finally(() => {
        inFlightLoads.get(loadKey) === promise && (previous ? inFlightLoads.set(loadKey, previous) : inFlightLoads.delete(loadKey));
      });
      inFlightLoads.set(loadKey, promise), await promise;
    },
    attachChannelCommands: (channelCommands, implementedCommandNames) => {
      loadCommands = channelCommands, remoteCommandNames.clear();
      for (let name of Object.keys(def.commands))
        implementedCommandNames.has(name) || remoteCommandNames.add(name);
    }
  };
}

// src/shared/open-service/service-error-serialization.ts
var ERROR_MARKER = "__openServiceError__", RESERVED_KEYS = /* @__PURE__ */ new Set([ERROR_MARKER, "name", "message", "stack", "cause"]);
function isPlainObject2(value) {
  return typeof value == "object" && value !== null && !Array.isArray(value);
}
function isSerializedError(value) {
  return isPlainObject2(value) && value[ERROR_MARKER] === !0;
}
function toTransportSafe(value) {
  if (value instanceof Error)
    return serializeError(value);
  if (Array.isArray(value))
    return value.map((entry) => toTransportSafe(entry));
  if (isPlainObject2(value)) {
    let result = {};
    for (let [key, entry] of Object.entries(value))
      result[key] = toTransportSafe(entry);
    return result;
  }
  if (value === null)
    return null;
  let kind = typeof value;
  if (kind === "string" || kind === "number" || kind === "boolean")
    return value;
  if (kind === "bigint")
    return value.toString();
}
function serializeError(value) {
  if (!(value instanceof Error))
    return { [ERROR_MARKER]: !0, name: "Error", message: String(value) };
  let properties = {};
  for (let key of Object.keys(value))
    RESERVED_KEYS.has(key) || (properties[key] = toTransportSafe(value[key]));
  return {
    [ERROR_MARKER]: !0,
    name: value.name,
    message: value.message,
    ...value.stack !== void 0 ? { stack: value.stack } : {},
    ...value.cause !== void 0 ? { cause: toTransportSafe(value.cause) } : {},
    ...Object.keys(properties).length > 0 ? { properties } : {}
  };
}
function fromTransportSafe(value) {
  if (isSerializedError(value))
    return deserializeError(value);
  if (Array.isArray(value))
    return value.map((entry) => fromTransportSafe(entry));
  if (isPlainObject2(value)) {
    let result = {};
    for (let [key, entry] of Object.entries(value))
      result[key] = fromTransportSafe(entry);
    return result;
  }
  return value;
}
function deserializeError(serialized) {
  let error = new Error(serialized.message);
  if (error.name = serialized.name, serialized.stack !== void 0 && (error.stack = serialized.stack), "cause" in serialized && (error.cause = fromTransportSafe(serialized.cause)), serialized.properties)
    for (let [key, value] of Object.entries(serialized.properties))
      error[key] = fromTransportSafe(value);
  return error;
}

// src/shared/open-service/service-transport.ts
var REMOTE_COMMAND_ACK_TIMEOUT_MS = 300;
function wrapCommandsForBroadcast(commands, context) {
  let { serviceId, ownClientId, reconciler, getSnapshot, channel } = context;
  return Object.fromEntries(
    Object.entries(commands).map(([name, cmd]) => [
      name,
      async (input) => {
        let result = await cmd(input), stamp = reconciler.advanceLocal(ownClientId);
        return channel.emit(SERVICE_PATCHES, {
          serviceId,
          state: getSnapshot(),
          version: stamp.version,
          clientId: stamp.clientId
        }), result;
      }
    ])
  );
}
function connectRuntimeToChannel(context) {
  let { serviceId, ownClientId, reconciler, getSnapshot, channel, relay } = context, emitSyncStart = () => {
    channel.emit(SERVICE_SYNC_START, {
      serviceId,
      clientId: ownClientId
    });
  }, relayAdopted = () => {
    channel.emit(SERVICE_PATCHES, {
      serviceId,
      state: getSnapshot(),
      version: reconciler.stamp.version,
      clientId: reconciler.stamp.clientId
    });
  }, adoptPeerSnapshot = (snapshot) => {
    let adopted = reconciler.tryAdopt(
      { version: snapshot.version, clientId: snapshot.clientId },
      snapshot.state
    );
    return adopted && relay && relayAdopted(), adopted;
  }, onSyncStart = (payload) => {
    let request = safeParse(syncStartSchema, payload);
    !request.success || request.output.serviceId !== serviceId || request.output.clientId === ownClientId || channel.emit(SERVICE_SYNC_START_REPLY, {
      serviceId,
      state: getSnapshot(),
      version: reconciler.stamp.version,
      clientId: reconciler.stamp.clientId
    });
  }, onSyncStartReply = (payload) => {
    let snapshot = safeParse(stampedSnapshotSchema, payload);
    !snapshot.success || snapshot.output.serviceId !== serviceId || adoptPeerSnapshot(snapshot.output);
  }, onPatches = (payload) => {
    let snapshot = safeParse(stampedSnapshotSchema, payload);
    !snapshot.success || snapshot.output.serviceId !== serviceId || adoptPeerSnapshot(snapshot.output);
  };
  return channel.on(SERVICE_SYNC_START, onSyncStart), channel.on(SERVICE_SYNC_START_REPLY, onSyncStartReply), channel.on(SERVICE_PATCHES, onPatches), emitSyncStart(), relay && reconciler.stamp.version > 0 && relayAdopted(), () => {
    channel.off(SERVICE_SYNC_START, onSyncStart), channel.off(SERVICE_SYNC_START_REPLY, onSyncStartReply), channel.off(SERVICE_PATCHES, onPatches);
  };
}
function connectCommandTransport(context) {
  let { serviceId, ownClientId, channel, localCommands, implementedCommandNames, commandNames } = context, pending = /* @__PURE__ */ new Map(), settle = (callId, apply) => {
    let entry = pending.get(callId);
    entry && (pending.delete(callId), clearTimeout(entry.noAckTimer), apply(entry));
  }, onInvoke = (payload) => {
    let parsed = safeParse(commandInvokeSchema, payload);
    if (!parsed.success || parsed.output.serviceId !== serviceId || !implementedCommandNames.has(parsed.output.commandName))
      return;
    let invoke = parsed.output;
    channel.emit(SERVICE_COMMAND_ACK, {
      serviceId,
      callId: invoke.callId,
      clientId: ownClientId
    }), Promise.resolve().then(() => localCommands[invoke.commandName](invoke.input)).then(
      (result) => {
        channel.emit(SERVICE_COMMAND_RESULT, {
          serviceId,
          callId: invoke.callId,
          result,
          clientId: ownClientId
        });
      },
      (error) => {
        channel.emit(SERVICE_COMMAND_ERROR, {
          serviceId,
          callId: invoke.callId,
          error: serializeError(error),
          clientId: ownClientId
        });
      }
    );
  }, onResult = (payload) => {
    let result = safeParse(commandResultSchema, payload);
    !result.success || result.output.serviceId !== serviceId || settle(result.output.callId, (entry) => entry.resolve(result.output.result));
  }, onError = (payload) => {
    let failure = safeParse(commandErrorSchema, payload);
    !failure.success || failure.output.serviceId !== serviceId || settle(failure.output.callId, (entry) => entry.reject(deserializeError(failure.output.error)));
  }, onAck = (payload) => {
    let ack = safeParse(commandAckSchema, payload);
    if (!ack.success || ack.output.serviceId !== serviceId)
      return;
    let entry = pending.get(ack.output.callId);
    entry && clearTimeout(entry.noAckTimer);
  };
  channel.on(SERVICE_COMMAND_INVOKE, onInvoke), channel.on(SERVICE_COMMAND_RESULT, onResult), channel.on(SERVICE_COMMAND_ERROR, onError), channel.on(SERVICE_COMMAND_ACK, onAck);
  let requestRemote = (commandName, input) => {
    let callId = generateClientId();
    return new Promise((resolve, reject) => {
      let noAckTimer = setTimeout(() => {
        settle(
          callId,
          (entry) => entry.reject(
            new OpenServiceRemoteCommandUnhandledError({
              serviceId,
              commandName: entry.commandName
            })
          )
        );
      }, REMOTE_COMMAND_ACK_TIMEOUT_MS);
      pending.set(callId, { commandName, resolve, reject, noAckTimer }), channel.emit(SERVICE_COMMAND_INVOKE, {
        serviceId,
        commandName,
        input,
        callId,
        clientId: ownClientId
      });
    });
  }, commands = {};
  for (let name of commandNames)
    commands[name] = implementedCommandNames.has(name) ? localCommands[name] : (input) => requestRemote(name, input);
  return {
    commands,
    disconnect: () => {
      channel.off(SERVICE_COMMAND_INVOKE, onInvoke), channel.off(SERVICE_COMMAND_RESULT, onResult), channel.off(SERVICE_COMMAND_ERROR, onError), channel.off(SERVICE_COMMAND_ACK, onAck);
      for (let [, entry] of pending)
        clearTimeout(entry.noAckTimer), entry.reject(new OpenServiceRemoteCommandDisconnectedError({ serviceId }));
      pending.clear();
    }
  };
}
function connectServiceToChannel(context) {
  let {
    serviceId,
    ownClientId,
    reconciler,
    getSnapshot,
    channel,
    relay,
    commands,
    implementedCommandNames,
    commandNames,
    runtime
  } = context, broadcastCommands = wrapCommandsForBroadcast(commands, {
    serviceId,
    ownClientId,
    reconciler,
    getSnapshot,
    channel
  }), commandTransport = connectCommandTransport({
    serviceId,
    ownClientId,
    channel,
    localCommands: broadcastCommands,
    implementedCommandNames,
    commandNames
  }), disconnectSync = connectRuntimeToChannel({
    serviceId,
    ownClientId,
    reconciler,
    getSnapshot,
    channel,
    relay
  });
  return runtime.attachChannelCommands(commandTransport.commands, implementedCommandNames), {
    commands: commandTransport.commands,
    disconnect: () => {
      disconnectSync(), commandTransport.disconnect();
    }
  };
}

// src/shared/open-service/service-registry.ts
var REGISTRY_SYMBOL = /* @__PURE__ */ Symbol.for("storybook.open-service.registry");
function getRegistry() {
  let registryGlobal = globalThis;
  return registryGlobal[REGISTRY_SYMBOL] ??= /* @__PURE__ */ new Map(), registryGlobal[REGISTRY_SYMBOL];
}
function describeDefinition(definition) {
  return {
    id: definition.id,
    description: definition.description,
    queries: Object.fromEntries(
      Object.entries(definition.queries).filter(([, query]) => !query.internal).map(([name, query]) => [
        name,
        {
          name,
          description: query.description,
          input: query.input,
          output: query.output,
          ...query.staticPath ? { staticPath: !0 } : {}
        }
      ])
    ),
    commands: Object.fromEntries(
      Object.entries(definition.commands).filter(([, command]) => !command.internal).map(([name, command]) => [
        name,
        {
          name,
          description: command.description,
          input: command.input,
          output: command.output
        }
      ])
    )
  };
}
function summarizeDescriptor(descriptor) {
  return {
    id: descriptor.id,
    description: descriptor.description,
    queryNames: Object.keys(descriptor.queries),
    commandNames: Object.keys(descriptor.commands)
  };
}
function applyRegistration(definition, registration) {
  return registration ? {
    ...definition,
    queries: Object.fromEntries(
      Object.entries(definition.queries).map(([name, query]) => {
        let override = registration.queries?.[name];
        return [
          name,
          override && "staticInputs" in override ? { ...query, staticInputs: override.staticInputs } : query
        ];
      })
    ),
    commands: Object.fromEntries(
      Object.entries(definition.commands).map(([name, command]) => {
        let override = registration.commands?.[name];
        return [
          name,
          override && "handler" in override ? { ...command, handler: override.handler } : command
        ];
      })
    )
  } : definition;
}
var serviceRegistryApi = {
  listServices,
  describeService,
  getService: chunk_HYA6IF3K_getService
};
function chunk_HYA6IF3K_registerService(definition, registration, { relay = !1, staticLoader } = {}) {
  let registry = getRegistry(), existingEntry = registry.get(definition.id);
  if (existingEntry)
    return existingEntry.instance;
  let ownClientId = generateClientId(), resolvedDefinition = applyRegistration(definition, registration), runtime = createServiceRuntime(
    resolvedDefinition,
    { registryApi: serviceRegistryApi, staticLoader },
    structuredClone(resolvedDefinition.initialState)
  ), reconciler = createSnapshotReconciler({
    setState: (mutate) => runtime.commandSelf.setState((state) => mutate(state)),
    initialStamp: { version: 0, clientId: ownClientId }
  }), getSnapshot = () => runtime.getStateSnapshot(), descriptor = describeDefinition(resolvedDefinition), channel = getChannel();
  if (!channel)
    throw new OpenServiceMissingChannelError({ serviceId: definition.id });
  let implementedCommandNames = new Set(
    Object.entries(resolvedDefinition.commands).filter(([, command]) => typeof command.handler == "function").map(([name]) => name)
  ), { commands, disconnect } = connectServiceToChannel({
    serviceId: definition.id,
    ownClientId,
    reconciler,
    getSnapshot,
    channel,
    relay,
    commands: runtime.commands,
    implementedCommandNames,
    commandNames: Object.keys(resolvedDefinition.commands),
    runtime
  }), instance = {
    queries: runtime.queries,
    commands,
    ...serviceRegistryApi
  };
  return registry.set(definition.id, {
    definition: resolvedDefinition,
    instance,
    descriptor,
    summary: summarizeDescriptor(descriptor),
    disconnect
  }), instance;
}
async function listServices() {
  return Array.from(getRegistry().values()).filter(({ definition }) => !definition.internal).map(({ summary }) => summary);
}
async function describeService(serviceId) {
  let entry = getRegistry().get(serviceId);
  if (!entry)
    throw new chunk_KUHYVZJN/* OpenServiceMissingServiceError */.HC({ serviceId });
  return entry.descriptor;
}
function chunk_HYA6IF3K_getService(serviceId) {
  let entry = getRegistry().get(serviceId);
  if (!entry)
    throw new chunk_KUHYVZJN/* OpenServiceMissingServiceError */.HC({ serviceId });
  return entry.instance;
}

// src/shared/open-service/static-fetch.ts
var STATIC_SERVICES_PREFIX = "./services/";
function shouldUseBrowserStaticLoader() {
  return globalThis.CONFIG_TYPE === "PRODUCTION";
}
function createBrowserStaticLoader() {
  if (shouldUseBrowserStaticLoader())
    return async (logicalPath, context) => {
      let url = `${STATIC_SERVICES_PREFIX}${logicalPath}`, res;
      try {
        res = await fetch(url);
      } catch (cause) {
        throw new OpenServiceStaticSnapshotLoadError({ ...context, logicalPath, url, cause });
      }
      if (!res.ok) {
        let cause = { status: res.status, statusText: res.statusText };
        throw new OpenServiceStaticSnapshotLoadError({
          ...context,
          logicalPath,
          url,
          cause,
          status: res.status,
          statusText: res.statusText
        });
      }
      let snapshot;
      try {
        snapshot = await res.json();
      } catch (cause) {
        throw new OpenServiceStaticSnapshotLoadError({ ...context, logicalPath, url, cause });
      }
      if (snapshot && typeof snapshot == "object" && !Array.isArray(snapshot))
        return snapshot;
      throw new OpenServiceStaticSnapshotInvalidError({
        ...context,
        logicalPath,
        url,
        received: snapshot
      });
    };
}



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/shared/open-service/index.js




// src/shared/open-service/service-definition.ts
var defineService = (def) => def;


;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-64Y7BDV2.js


// src/measure/constants.ts
var ADDON_ID = "storybook/measure-addon", TOOL_ID = (/* unused pure expression or super */ null && (`${ADDON_ID}/tool`)), PARAM_KEY = "measureEnabled", EVENTS = {
  RESULT: `${ADDON_ID}/result`,
  REQUEST: `${ADDON_ID}/request`,
  CLEAR: `${ADDON_ID}/clear`
};

// src/outline/constants.ts
var ADDON_ID2 = "storybook/outline", PARAM_KEY2 = "outline";

// src/shared/open-service/services/docgen/definition.ts


// src/shared/open-service/services/docgen/paths.ts
function docgenQueryStaticPath(id) {
  return `${id}.json`;
}

// src/shared/open-service/services/docgen/definition.ts
var docgenInputSchema = object({ id: string() }), argTypesSchema = custom(
  (value) => typeof value == "object" && value !== null && !Array.isArray(value)
), docgenErrorSchema = object({
  name: string(),
  message: string()
}), docgenJsDocTagsSchema = record(string(), array(string())), docgenComponentFields = {
  name: string(),
  path: string(),
  description: optional(string()),
  summary: optional(string()),
  jsDocTags: docgenJsDocTagsSchema,
  argTypes: optional(argTypesSchema),
  error: optional(docgenErrorSchema)
}, docgenSubcomponentFields = {
  ...docgenComponentFields,
  import: optional(string())
}, docgenSubcomponentSchema = looseObject(docgenSubcomponentFields), docgenPayloadSchema = looseObject({
  id: string(),
  ...docgenComponentFields,
  subcomponents: optional(record(string(), docgenSubcomponentSchema))
}), docgenOutputSchema = optional(docgenPayloadSchema), chunk_64Y7BDV2_docgenServiceDef = defineService({
  id: "core/docgen",
  description: "Component documentation (name, description, props, JSDoc tags) keyed by component id.",
  initialState: { components: {} },
  queries: {
    docgen: {
      description: "Returns the docgen payload for one component id, or undefined when not loaded.",
      input: docgenInputSchema,
      output: docgenOutputSchema,
      handler: (input, ctx) => ctx.self.state.components[input.id],
      load: async (input, ctx) => {
        await ctx.self.commands.extractDocgen(input);
      },
      staticPath: (input) => docgenQueryStaticPath(input.id)
    },
    docgenForAllComponents: {
      description: "Returns docgen payloads for every component in the story index.",
      input: void_(),
      output: record(string(), docgenPayloadSchema),
      handler: (_input, ctx) => ctx.self.state.components,
      load: async (_input, ctx) => {
        await ctx.self.commands.extractAllDocgen(void 0);
      }
    }
  },
  commands: {
    extractDocgen: {
      description: "Resolves the story entry for a component id, runs the registered provider chain, writes the result into state, and returns it (or undefined when no provider produced docgen).",
      input: docgenInputSchema,
      output: docgenOutputSchema
      // Handler is supplied at registration time so it can close over the story index and the
      // composed experimental_docgenProvider chain.
    },
    extractAllDocgen: {
      description: "Extracts docgen for every component id in the story index by invoking `extractDocgen` for each.",
      input: undefined_(),
      output: void_()
      // Handler is supplied at registration time so it can close over the story index.
    }
  }
});



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-IWQGIXJS.js
// ../../node_modules/tiny-invariant/dist/esm/tiny-invariant.js
var isProduction = "production" === "production", prefix = "Invariant failed";
function invariant(condition, message) {
  if (!condition) {
    if (isProduction)
      throw new Error(prefix);
    var provided = typeof message == "function" ? message() : message, value = provided ? "".concat(prefix, ": ").concat(provided) : prefix;
    throw new Error(value);
  }
}



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-NQ3W4AUJ.js
// src/viewport/constants.ts
var chunk_NQ3W4AUJ_ADDON_ID = "storybook/viewport", chunk_NQ3W4AUJ_PARAM_KEY = "viewport", PANEL_ID = (/* unused pure expression or super */ null && (`${chunk_NQ3W4AUJ_ADDON_ID}/panel`)), chunk_NQ3W4AUJ_TOOL_ID = (/* unused pure expression or super */ null && (`${chunk_NQ3W4AUJ_ADDON_ID}/tool`)), RESPONSIVE_VIEWPORT_VALUE = "100pct-100pct";

// src/viewport/defaults.ts
var INITIAL_VIEWPORTS = {
  iphone5: {
    name: "iPhone 5",
    styles: {
      height: "568px",
      width: "320px"
    },
    type: "mobile"
  },
  iphone6: {
    name: "iPhone 6",
    styles: {
      height: "667px",
      width: "375px"
    },
    type: "mobile"
  },
  iphone6p: {
    name: "iPhone 6 Plus",
    styles: {
      height: "736px",
      width: "414px"
    },
    type: "mobile"
  },
  iphone8p: {
    name: "iPhone 8 Plus",
    styles: {
      height: "736px",
      width: "414px"
    },
    type: "mobile"
  },
  iphonex: {
    name: "iPhone X",
    styles: {
      height: "812px",
      width: "375px"
    },
    type: "mobile"
  },
  iphonexr: {
    name: "iPhone XR",
    styles: {
      height: "896px",
      width: "414px"
    },
    type: "mobile"
  },
  iphonexsmax: {
    name: "iPhone XS Max",
    styles: {
      height: "896px",
      width: "414px"
    },
    type: "mobile"
  },
  iphonese2: {
    name: "iPhone SE (2nd generation)",
    styles: {
      height: "667px",
      width: "375px"
    },
    type: "mobile"
  },
  iphone12mini: {
    name: "iPhone 12 mini",
    styles: {
      height: "812px",
      width: "375px"
    },
    type: "mobile"
  },
  iphone12: {
    name: "iPhone 12",
    styles: {
      height: "844px",
      width: "390px"
    },
    type: "mobile"
  },
  iphone12promax: {
    name: "iPhone 12 Pro Max",
    styles: {
      height: "926px",
      width: "428px"
    },
    type: "mobile"
  },
  iphoneSE3: {
    name: "iPhone SE 3rd generation",
    styles: {
      height: "667px",
      width: "375px"
    },
    type: "mobile"
  },
  iphone13: {
    name: "iPhone 13",
    styles: {
      height: "844px",
      width: "390px"
    },
    type: "mobile"
  },
  iphone13pro: {
    name: "iPhone 13 Pro",
    styles: {
      height: "844px",
      width: "390px"
    },
    type: "mobile"
  },
  iphone13promax: {
    name: "iPhone 13 Pro Max",
    styles: {
      height: "926px",
      width: "428px"
    },
    type: "mobile"
  },
  iphone14: {
    name: "iPhone 14",
    styles: {
      height: "844px",
      width: "390px"
    },
    type: "mobile"
  },
  iphone14pro: {
    name: "iPhone 14 Pro",
    styles: {
      height: "852px",
      width: "393px"
    },
    type: "mobile"
  },
  iphone14promax: {
    name: "iPhone 14 Pro Max",
    styles: {
      height: "932px",
      width: "430px"
    },
    type: "mobile"
  },
  ipad: {
    name: "iPad",
    styles: {
      height: "1024px",
      width: "768px"
    },
    type: "tablet"
  },
  ipad10p: {
    name: "iPad Pro 10.5-in",
    styles: {
      height: "1112px",
      width: "834px"
    },
    type: "tablet"
  },
  ipad11p: {
    name: "iPad Pro 11-in",
    styles: {
      height: "1194px",
      width: "834px"
    },
    type: "tablet"
  },
  ipad12p: {
    name: "iPad Pro 12.9-in",
    styles: {
      height: "1366px",
      width: "1024px"
    },
    type: "tablet"
  },
  galaxys5: {
    name: "Galaxy S5",
    styles: {
      height: "640px",
      width: "360px"
    },
    type: "mobile"
  },
  galaxys9: {
    name: "Galaxy S9",
    styles: {
      height: "740px",
      width: "360px"
    },
    type: "mobile"
  },
  nexus5x: {
    name: "Nexus 5X",
    styles: {
      height: "660px",
      width: "412px"
    },
    type: "mobile"
  },
  nexus6p: {
    name: "Nexus 6P",
    styles: {
      height: "732px",
      width: "412px"
    },
    type: "mobile"
  },
  pixel: {
    name: "Pixel",
    styles: {
      height: "960px",
      width: "540px"
    },
    type: "mobile"
  },
  pixelxl: {
    name: "Pixel XL",
    styles: {
      height: "1280px",
      width: "720px"
    },
    type: "mobile"
  }
}, DEFAULT_VIEWPORT = "responsive", MINIMAL_VIEWPORTS = {
  mobile1: {
    name: "Small mobile",
    styles: {
      height: "568px",
      width: "320px"
    },
    type: "mobile"
  },
  mobile2: {
    name: "Large mobile",
    styles: {
      height: "896px",
      width: "414px"
    },
    type: "mobile"
  },
  tablet: {
    name: "Tablet",
    styles: {
      height: "1112px",
      width: "834px"
    },
    type: "tablet"
  },
  desktop: {
    name: "Desktop",
    styles: {
      height: "1024px",
      width: "1280px"
    },
    type: "desktop"
  }
};



// EXTERNAL MODULE: ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-3LY4VQVK.js
var chunk_3LY4VQVK = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-3LY4VQVK.js");
;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-KJHJLCBK.js
// src/highlight/constants.ts
var chunk_KJHJLCBK_ADDON_ID = "storybook/highlight", HIGHLIGHT = `${chunk_KJHJLCBK_ADDON_ID}/add`, REMOVE_HIGHLIGHT = `${chunk_KJHJLCBK_ADDON_ID}/remove`, RESET_HIGHLIGHT = `${chunk_KJHJLCBK_ADDON_ID}/reset`, SCROLL_INTO_VIEW = `${chunk_KJHJLCBK_ADDON_ID}/scroll-into-view`, MAX_Z_INDEX = 2147483647, MIN_TOUCH_AREA_SIZE = 28;



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-6XWLIJQL.js
// src/actions/constants.ts
var chunk_6XWLIJQL_PARAM_KEY = "actions", chunk_6XWLIJQL_ADDON_ID = "storybook/actions", chunk_6XWLIJQL_PANEL_ID = (/* unused pure expression or super */ null && (`${chunk_6XWLIJQL_ADDON_ID}/panel`)), EVENT_ID = `${chunk_6XWLIJQL_ADDON_ID}/action-event`, CLEAR_ID = (/* unused pure expression or super */ null && (`${chunk_6XWLIJQL_ADDON_ID}/action-clear`)), CYCLIC_KEY = "$___storybook.isCyclic";



// EXTERNAL MODULE: external "__STORYBOOK_MODULE_CORE_EVENTS_PREVIEW_ERRORS__"
var external_STORYBOOK_MODULE_CORE_EVENTS_PREVIEW_ERRORS_ = __webpack_require__("storybook/internal/preview-errors");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_GLOBAL__"
var external_STORYBOOK_MODULE_GLOBAL_ = __webpack_require__("@storybook/global");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_PREVIEW_API__"
var external_STORYBOOK_MODULE_PREVIEW_API_ = __webpack_require__("storybook/preview-api");
;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-EUVGDK4H.js


// src/actions/runtime/configureActions.ts
var config = {
  depth: 10,
  clearOnStoryChange: !0,
  limit: 50
}, configureActions = (options = {}) => {
  Object.assign(config, options);
};

// src/actions/runtime/action.ts



var findProto = (obj, callback) => {
  let proto = Object.getPrototypeOf(obj);
  return !proto || callback(proto) ? proto : findProto(proto, callback);
}, isReactSyntheticEvent = (e) => !!(typeof e == "object" && e && findProto(e, (proto) => /^Synthetic(?:Base)?Event$/.test(proto.constructor.name)) && typeof e.persist == "function"), serializeArg = (a) => {
  if (isReactSyntheticEvent(a)) {
    let e = Object.create(
      a.constructor.prototype,
      Object.getOwnPropertyDescriptors(a)
    );
    e.persist();
    let viewDescriptor = Object.getOwnPropertyDescriptor(e, "view"), view = viewDescriptor?.value;
    return typeof view == "object" && view?.constructor.name === "Window" && Object.defineProperty(e, "view", {
      ...viewDescriptor,
      value: Object.create(view.constructor.prototype)
    }), e;
  }
  return a;
};
function action(name, options = {}) {
  let actionOptions = {
    ...config,
    ...options
  }, handler = function(...args) {
    if (options.implicit) {
      let storyRenderer = ("__STORYBOOK_PREVIEW__" in external_STORYBOOK_MODULE_GLOBAL_.global ? external_STORYBOOK_MODULE_GLOBAL_.global.__STORYBOOK_PREVIEW__ : void 0)?.storyRenders.find(
        (render) => render.phase === "playing" || render.phase === "rendering"
      );
      if (storyRenderer) {
        let deprecated = !globalThis?.FEATURES?.disallowImplicitActionsInRenderV8, error = new external_STORYBOOK_MODULE_CORE_EVENTS_PREVIEW_ERRORS_.ImplicitActionsDuringRendering({
          phase: storyRenderer.phase,
          name,
          deprecated
        });
        if (deprecated)
          console.warn(error);
        else
          throw error;
      }
    }
    let channel = external_STORYBOOK_MODULE_PREVIEW_API_.addons.getChannel(), id = Date.now().toString(36) + Math.random().toString(36).substring(2), minDepth = 5, serializedArgs = args.map(serializeArg), normalizedArgs = args.length > 1 ? serializedArgs : serializedArgs[0], actionDisplayToEmit = {
      id,
      count: 0,
      data: { name, args: normalizedArgs },
      options: {
        ...actionOptions,
        maxDepth: minDepth + (actionOptions.depth || 3)
      }
    };
    channel.emit(EVENT_ID, actionDisplayToEmit);
  };
  return handler.isAction = !0, handler.implicit = options.implicit, handler;
}

// src/actions/runtime/actions.ts
var actions = (...args) => {
  let options = config, names = args;
  names.length === 1 && Array.isArray(names[0]) && ([names] = names), names.length !== 1 && typeof names[names.length - 1] != "string" && (options = {
    ...config,
    ...names.pop()
  });
  let namesObject = names[0];
  (names.length !== 1 || typeof namesObject == "string") && (namesObject = {}, names.forEach((name) => {
    namesObject[name] = name;
  }));
  let actionsObject = {};
  return Object.keys(namesObject).forEach((name) => {
    actionsObject[name] = action(namesObject[name], options);
  }), actionsObject;
};



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-6YDLG6WW.js
// src/backgrounds/constants.ts
var chunk_6YDLG6WW_ADDON_ID = "storybook/background", chunk_6YDLG6WW_PARAM_KEY = "backgrounds", GRID_PARAM_KEY = "grid", chunk_6YDLG6WW_EVENTS = {
  UPDATE: `${chunk_6YDLG6WW_ADDON_ID}/update`
};

// src/backgrounds/defaults.ts
var DEFAULT_BACKGROUNDS = {
  light: { name: "light", value: "#F8F8F8" },
  dark: { name: "dark", value: "#333" }
};



// EXTERNAL MODULE: ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-IMSF75WX.js
var chunk_IMSF75WX = __webpack_require__("../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-IMSF75WX.js");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_TEST__"
var external_STORYBOOK_MODULE_TEST_ = __webpack_require__("storybook/test");
;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-ZUWEVLDX.js
// src/instrumenter/EVENTS.ts
var chunk_ZUWEVLDX_EVENTS = {
  CALL: "storybook/instrumenter/call",
  SYNC: "storybook/instrumenter/sync",
  START: "storybook/instrumenter/start",
  BACK: "storybook/instrumenter/back",
  GOTO: "storybook/instrumenter/goto",
  NEXT: "storybook/instrumenter/next",
  END: "storybook/instrumenter/end"
};

// src/instrumenter/types.ts
var CallStates = /* @__PURE__ */ ((CallStates2) => (CallStates2.DONE = "done", CallStates2.ERROR = "error", CallStates2.ACTIVE = "active", CallStates2.WAITING = "waiting", CallStates2))(CallStates || {});



;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/_browser-chunks/chunk-QGGIYUMM.js
// ../../node_modules/@vitest/utils/node_modules/tinyrainbow/dist/chunk-BVHSVHOK.js
var chunk_QGGIYUMM_f = {
  reset: [0, 0],
  bold: [1, 22, "\x1B[22m\x1B[1m"],
  dim: [2, 22, "\x1B[22m\x1B[2m"],
  italic: [3, 23],
  underline: [4, 24],
  inverse: [7, 27],
  hidden: [8, 28],
  strikethrough: [9, 29],
  black: [30, 39],
  red: [31, 39],
  green: [32, 39],
  yellow: [33, 39],
  blue: [34, 39],
  magenta: [35, 39],
  cyan: [36, 39],
  white: [37, 39],
  gray: [90, 39],
  bgBlack: [40, 49],
  bgRed: [41, 49],
  bgGreen: [42, 49],
  bgYellow: [43, 49],
  bgBlue: [44, 49],
  bgMagenta: [45, 49],
  bgCyan: [46, 49],
  bgWhite: [47, 49],
  blackBright: [90, 39],
  redBright: [91, 39],
  greenBright: [92, 39],
  yellowBright: [93, 39],
  blueBright: [94, 39],
  magentaBright: [95, 39],
  cyanBright: [96, 39],
  whiteBright: [97, 39],
  bgBlackBright: [100, 49],
  bgRedBright: [101, 49],
  bgGreenBright: [102, 49],
  bgYellowBright: [103, 49],
  bgBlueBright: [104, 49],
  bgMagentaBright: [105, 49],
  bgCyanBright: [106, 49],
  bgWhiteBright: [107, 49]
}, chunk_QGGIYUMM_h = Object.entries(chunk_QGGIYUMM_f);
function chunk_QGGIYUMM_a(n) {
  return String(n);
}
chunk_QGGIYUMM_a.open = "";
chunk_QGGIYUMM_a.close = "";
var B = chunk_QGGIYUMM_h.reduce(
  (n, [e]) => (n[e] = chunk_QGGIYUMM_a, n),
  { isColorSupported: !1 }
);
function C(n = !1) {
  let e = typeof process < "u" ? process : void 0, i = e?.env || {}, g = e?.argv || [];
  return !("NO_COLOR" in i || g.includes("--no-color")) && ("FORCE_COLOR" in i || g.includes("--color") || e?.platform === "win32" || n && i.TERM !== "dumb" || "CI" in i) || typeof window < "u" && !!window.chrome;
}
function chunk_QGGIYUMM_p(n = !1) {
  let e = C(n), i = (r, t, c, o) => {
    let l = "", s2 = 0;
    do
      l += r.substring(s2, o) + c, s2 = o + t.length, o = r.indexOf(t, s2);
    while (~o);
    return l + r.substring(s2);
  }, g = (r, t, c = r) => {
    let o = (l) => {
      let s2 = String(l), b = s2.indexOf(t, r.length);
      return ~b ? r + i(s2, t, c, b) + t : r + s2 + t;
    };
    return o.open = r, o.close = t, o;
  }, u = {
    isColorSupported: e
  }, d = (r) => `\x1B[${r}m`;
  for (let [r, t] of chunk_QGGIYUMM_h)
    u[r] = e ? g(
      d(t[0]),
      d(t[1]),
      t[2]
    ) : chunk_QGGIYUMM_a;
  return u;
}

// ../../node_modules/@vitest/utils/node_modules/tinyrainbow/dist/browser.js
var chunk_QGGIYUMM_s = chunk_QGGIYUMM_p();

// ../../node_modules/@vitest/utils/node_modules/@vitest/pretty-format/dist/index.js
function _mergeNamespaces(n, m2) {
  return m2.forEach(function(e) {
    e && typeof e != "string" && !Array.isArray(e) && Object.keys(e).forEach(function(k) {
      if (k !== "default" && !(k in n)) {
        var d = Object.getOwnPropertyDescriptor(e, k);
        Object.defineProperty(n, k, d.get ? d : {
          enumerable: !0,
          get: function() {
            return e[k];
          }
        });
      }
    });
  }), Object.freeze(n);
}
function getKeysOfEnumerableProperties(object, compareKeys) {
  let rawKeys = Object.keys(object), keys = compareKeys === null ? rawKeys : rawKeys.sort(compareKeys);
  if (Object.getOwnPropertySymbols)
    for (let symbol of Object.getOwnPropertySymbols(object))
      Object.getOwnPropertyDescriptor(object, symbol).enumerable && keys.push(symbol);
  return keys;
}
function printIteratorEntries(iterator, config, indentation, depth, refs, printer2, separator = ": ") {
  let result = "", width = 0, current = iterator.next();
  if (!current.done) {
    result += config.spacingOuter;
    let indentationNext = indentation + config.indent;
    for (; !current.done; ) {
      if (result += indentationNext, width++ === config.maxWidth) {
        result += "\u2026";
        break;
      }
      let name = printer2(current.value[0], config, indentationNext, depth, refs), value = printer2(current.value[1], config, indentationNext, depth, refs);
      result += name + separator + value, current = iterator.next(), current.done ? config.min || (result += ",") : result += `,${config.spacingInner}`;
    }
    result += config.spacingOuter + indentation;
  }
  return result;
}
function printIteratorValues(iterator, config, indentation, depth, refs, printer2) {
  let result = "", width = 0, current = iterator.next();
  if (!current.done) {
    result += config.spacingOuter;
    let indentationNext = indentation + config.indent;
    for (; !current.done; ) {
      if (result += indentationNext, width++ === config.maxWidth) {
        result += "\u2026";
        break;
      }
      result += printer2(current.value, config, indentationNext, depth, refs), current = iterator.next(), current.done ? config.min || (result += ",") : result += `,${config.spacingInner}`;
    }
    result += config.spacingOuter + indentation;
  }
  return result;
}
function printListItems(list, config, indentation, depth, refs, printer2) {
  let result = "";
  list = list instanceof ArrayBuffer ? new DataView(list) : list;
  let isDataView = (l) => l instanceof DataView, length = isDataView(list) ? list.byteLength : list.length;
  if (length > 0) {
    result += config.spacingOuter;
    let indentationNext = indentation + config.indent;
    for (let i = 0; i < length; i++) {
      if (result += indentationNext, i === config.maxWidth) {
        result += "\u2026";
        break;
      }
      (isDataView(list) || i in list) && (result += printer2(isDataView(list) ? list.getInt8(i) : list[i], config, indentationNext, depth, refs)), i < length - 1 ? result += `,${config.spacingInner}` : config.min || (result += ",");
    }
    result += config.spacingOuter + indentation;
  }
  return result;
}
function printObjectProperties(val, config, indentation, depth, refs, printer2) {
  let result = "", keys = getKeysOfEnumerableProperties(val, config.compareKeys);
  if (keys.length > 0) {
    result += config.spacingOuter;
    let indentationNext = indentation + config.indent;
    for (let i = 0; i < keys.length; i++) {
      let key = keys[i], name = printer2(key, config, indentationNext, depth, refs), value = printer2(val[key], config, indentationNext, depth, refs);
      result += `${indentationNext + name}: ${value}`, i < keys.length - 1 ? result += `,${config.spacingInner}` : config.min || (result += ",");
    }
    result += config.spacingOuter + indentation;
  }
  return result;
}
var asymmetricMatcher = typeof Symbol == "function" && Symbol.for ? /* @__PURE__ */ Symbol.for("jest.asymmetricMatcher") : 1267621, SPACE$2 = " ", serialize$5 = (val, config, indentation, depth, refs, printer2) => {
  let stringedValue = val.toString();
  if (stringedValue === "ArrayContaining" || stringedValue === "ArrayNotContaining")
    return ++depth > config.maxDepth ? `[${stringedValue}]` : `${stringedValue + SPACE$2}[${printListItems(val.sample, config, indentation, depth, refs, printer2)}]`;
  if (stringedValue === "ObjectContaining" || stringedValue === "ObjectNotContaining")
    return ++depth > config.maxDepth ? `[${stringedValue}]` : `${stringedValue + SPACE$2}{${printObjectProperties(val.sample, config, indentation, depth, refs, printer2)}}`;
  if (stringedValue === "StringMatching" || stringedValue === "StringNotMatching" || stringedValue === "StringContaining" || stringedValue === "StringNotContaining")
    return stringedValue + SPACE$2 + printer2(val.sample, config, indentation, depth, refs);
  if (typeof val.toAsymmetricMatcher != "function")
    throw new TypeError(`Asymmetric matcher ${val.constructor.name} does not implement toAsymmetricMatcher()`);
  return val.toAsymmetricMatcher();
}, test$5 = (val) => val && val.$$typeof === asymmetricMatcher, plugin$5 = {
  serialize: serialize$5,
  test: test$5
}, SPACE$1 = " ", OBJECT_NAMES = /* @__PURE__ */ new Set(["DOMStringMap", "NamedNodeMap"]), ARRAY_REGEXP = /^(?:HTML\w*Collection|NodeList)$/;
function testName(name) {
  return OBJECT_NAMES.has(name) || ARRAY_REGEXP.test(name);
}
var test$4 = (val) => val && val.constructor && !!val.constructor.name && testName(val.constructor.name);
function isNamedNodeMap(collection) {
  return collection.constructor.name === "NamedNodeMap";
}
var serialize$4 = (collection, config, indentation, depth, refs, printer2) => {
  let name = collection.constructor.name;
  return ++depth > config.maxDepth ? `[${name}]` : (config.min ? "" : name + SPACE$1) + (OBJECT_NAMES.has(name) ? `{${printObjectProperties(isNamedNodeMap(collection) ? [...collection].reduce((props, attribute) => (props[attribute.name] = attribute.value, props), {}) : { ...collection }, config, indentation, depth, refs, printer2)}}` : `[${printListItems([...collection], config, indentation, depth, refs, printer2)}]`);
}, plugin$4 = {
  serialize: serialize$4,
  test: test$4
};
function escapeHTML(str) {
  return str.replaceAll("<", "&lt;").replaceAll(">", "&gt;");
}
function printProps(keys, props, config, indentation, depth, refs, printer2) {
  let indentationNext = indentation + config.indent, colors = config.colors;
  return keys.map((key) => {
    let value = props[key], printed = printer2(value, config, indentationNext, depth, refs);
    return typeof value != "string" && (printed.includes(`
`) && (printed = config.spacingOuter + indentationNext + printed + config.spacingOuter + indentation), printed = `{${printed}}`), `${config.spacingInner + indentation + colors.prop.open + key + colors.prop.close}=${colors.value.open}${printed}${colors.value.close}`;
  }).join("");
}
function printChildren(children, config, indentation, depth, refs, printer2) {
  return children.map((child) => config.spacingOuter + indentation + (typeof child == "string" ? printText(child, config) : printer2(child, config, indentation, depth, refs))).join("");
}
function printText(text, config) {
  let contentColor = config.colors.content;
  return contentColor.open + escapeHTML(text) + contentColor.close;
}
function printComment(comment, config) {
  let commentColor = config.colors.comment;
  return `${commentColor.open}<!--${escapeHTML(comment)}-->${commentColor.close}`;
}
function printElement(type, printedProps, printedChildren, config, indentation) {
  let tagColor = config.colors.tag;
  return `${tagColor.open}<${type}${printedProps && tagColor.close + printedProps + config.spacingOuter + indentation + tagColor.open}${printedChildren ? `>${tagColor.close}${printedChildren}${config.spacingOuter}${indentation}${tagColor.open}</${type}` : `${printedProps && !config.min ? "" : " "}/`}>${tagColor.close}`;
}
function printElementAsLeaf(type, config) {
  let tagColor = config.colors.tag;
  return `${tagColor.open}<${type}${tagColor.close} \u2026${tagColor.open} />${tagColor.close}`;
}
var ELEMENT_NODE = 1, TEXT_NODE = 3, COMMENT_NODE = 8, FRAGMENT_NODE = 11, ELEMENT_REGEXP = /^(?:(?:HTML|SVG)\w*)?Element$/;
function testHasAttribute(val) {
  try {
    return typeof val.hasAttribute == "function" && val.hasAttribute("is");
  } catch {
    return !1;
  }
}
function testNode(val) {
  let constructorName = val.constructor.name, { nodeType, tagName } = val, isCustomElement = typeof tagName == "string" && tagName.includes("-") || testHasAttribute(val);
  return nodeType === ELEMENT_NODE && (ELEMENT_REGEXP.test(constructorName) || isCustomElement) || nodeType === TEXT_NODE && constructorName === "Text" || nodeType === COMMENT_NODE && constructorName === "Comment" || nodeType === FRAGMENT_NODE && constructorName === "DocumentFragment";
}
var test$3 = (val) => {
  var _val$constructor;
  return (val == null || (_val$constructor = val.constructor) === null || _val$constructor === void 0 ? void 0 : _val$constructor.name) && testNode(val);
};
function nodeIsText(node) {
  return node.nodeType === TEXT_NODE;
}
function nodeIsComment(node) {
  return node.nodeType === COMMENT_NODE;
}
function nodeIsFragment(node) {
  return node.nodeType === FRAGMENT_NODE;
}
var serialize$3 = (node, config, indentation, depth, refs, printer2) => {
  if (nodeIsText(node))
    return printText(node.data, config);
  if (nodeIsComment(node))
    return printComment(node.data, config);
  let type = nodeIsFragment(node) ? "DocumentFragment" : node.tagName.toLowerCase();
  return ++depth > config.maxDepth ? printElementAsLeaf(type, config) : printElement(type, printProps(nodeIsFragment(node) ? [] : Array.from(node.attributes, (attr) => attr.name).sort(), nodeIsFragment(node) ? {} : [...node.attributes].reduce((props, attribute) => (props[attribute.name] = attribute.value, props), {}), config, indentation + config.indent, depth, refs, printer2), printChildren(Array.prototype.slice.call(node.childNodes || node.children), config, indentation + config.indent, depth, refs, printer2), config, indentation);
}, plugin$3 = {
  serialize: serialize$3,
  test: test$3
}, IS_ITERABLE_SENTINEL = "@@__IMMUTABLE_ITERABLE__@@", IS_LIST_SENTINEL = "@@__IMMUTABLE_LIST__@@", IS_KEYED_SENTINEL = "@@__IMMUTABLE_KEYED__@@", IS_MAP_SENTINEL = "@@__IMMUTABLE_MAP__@@", IS_ORDERED_SENTINEL = "@@__IMMUTABLE_ORDERED__@@", IS_RECORD_SENTINEL = "@@__IMMUTABLE_RECORD__@@", IS_SEQ_SENTINEL = "@@__IMMUTABLE_SEQ__@@", IS_SET_SENTINEL = "@@__IMMUTABLE_SET__@@", IS_STACK_SENTINEL = "@@__IMMUTABLE_STACK__@@", getImmutableName = (name) => `Immutable.${name}`, printAsLeaf = (name) => `[${name}]`, SPACE = " ", LAZY = "\u2026";
function printImmutableEntries(val, config, indentation, depth, refs, printer2, type) {
  return ++depth > config.maxDepth ? printAsLeaf(getImmutableName(type)) : `${getImmutableName(type) + SPACE}{${printIteratorEntries(val.entries(), config, indentation, depth, refs, printer2)}}`;
}
function getRecordEntries(val) {
  let i = 0;
  return { next() {
    if (i < val._keys.length) {
      let key = val._keys[i++];
      return {
        done: !1,
        value: [key, val.get(key)]
      };
    }
    return {
      done: !0,
      value: void 0
    };
  } };
}
function printImmutableRecord(val, config, indentation, depth, refs, printer2) {
  let name = getImmutableName(val._name || "Record");
  return ++depth > config.maxDepth ? printAsLeaf(name) : `${name + SPACE}{${printIteratorEntries(getRecordEntries(val), config, indentation, depth, refs, printer2)}}`;
}
function printImmutableSeq(val, config, indentation, depth, refs, printer2) {
  let name = getImmutableName("Seq");
  return ++depth > config.maxDepth ? printAsLeaf(name) : val[IS_KEYED_SENTINEL] ? `${name + SPACE}{${val._iter || val._object ? printIteratorEntries(val.entries(), config, indentation, depth, refs, printer2) : LAZY}}` : `${name + SPACE}[${val._iter || val._array || val._collection || val._iterable ? printIteratorValues(val.values(), config, indentation, depth, refs, printer2) : LAZY}]`;
}
function printImmutableValues(val, config, indentation, depth, refs, printer2, type) {
  return ++depth > config.maxDepth ? printAsLeaf(getImmutableName(type)) : `${getImmutableName(type) + SPACE}[${printIteratorValues(val.values(), config, indentation, depth, refs, printer2)}]`;
}
var serialize$2 = (val, config, indentation, depth, refs, printer2) => val[IS_MAP_SENTINEL] ? printImmutableEntries(val, config, indentation, depth, refs, printer2, val[IS_ORDERED_SENTINEL] ? "OrderedMap" : "Map") : val[IS_LIST_SENTINEL] ? printImmutableValues(val, config, indentation, depth, refs, printer2, "List") : val[IS_SET_SENTINEL] ? printImmutableValues(val, config, indentation, depth, refs, printer2, val[IS_ORDERED_SENTINEL] ? "OrderedSet" : "Set") : val[IS_STACK_SENTINEL] ? printImmutableValues(val, config, indentation, depth, refs, printer2, "Stack") : val[IS_SEQ_SENTINEL] ? printImmutableSeq(val, config, indentation, depth, refs, printer2) : printImmutableRecord(val, config, indentation, depth, refs, printer2), test$2 = (val) => val && (val[IS_ITERABLE_SENTINEL] === !0 || val[IS_RECORD_SENTINEL] === !0), plugin$2 = {
  serialize: serialize$2,
  test: test$2
};
function getDefaultExportFromCjs(x) {
  return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, "default") ? x.default : x;
}
var reactIs$1 = { exports: {} }, reactIs_production = {};
var hasRequiredReactIs_production;
function requireReactIs_production() {
  if (hasRequiredReactIs_production) return reactIs_production;
  hasRequiredReactIs_production = 1;
  var REACT_ELEMENT_TYPE = /* @__PURE__ */ Symbol.for("react.transitional.element"), REACT_PORTAL_TYPE = /* @__PURE__ */ Symbol.for("react.portal"), REACT_FRAGMENT_TYPE = /* @__PURE__ */ Symbol.for("react.fragment"), REACT_STRICT_MODE_TYPE = /* @__PURE__ */ Symbol.for("react.strict_mode"), REACT_PROFILER_TYPE = /* @__PURE__ */ Symbol.for("react.profiler"), REACT_CONSUMER_TYPE = /* @__PURE__ */ Symbol.for("react.consumer"), REACT_CONTEXT_TYPE = /* @__PURE__ */ Symbol.for("react.context"), REACT_FORWARD_REF_TYPE = /* @__PURE__ */ Symbol.for("react.forward_ref"), REACT_SUSPENSE_TYPE = /* @__PURE__ */ Symbol.for("react.suspense"), REACT_SUSPENSE_LIST_TYPE = /* @__PURE__ */ Symbol.for("react.suspense_list"), REACT_MEMO_TYPE = /* @__PURE__ */ Symbol.for("react.memo"), REACT_LAZY_TYPE = /* @__PURE__ */ Symbol.for("react.lazy"), REACT_VIEW_TRANSITION_TYPE = /* @__PURE__ */ Symbol.for("react.view_transition"), REACT_CLIENT_REFERENCE = /* @__PURE__ */ Symbol.for("react.client.reference");
  function typeOf(object) {
    if (typeof object == "object" && object !== null) {
      var $$typeof = object.$$typeof;
      switch ($$typeof) {
        case REACT_ELEMENT_TYPE:
          switch (object = object.type, object) {
            case REACT_FRAGMENT_TYPE:
            case REACT_PROFILER_TYPE:
            case REACT_STRICT_MODE_TYPE:
            case REACT_SUSPENSE_TYPE:
            case REACT_SUSPENSE_LIST_TYPE:
            case REACT_VIEW_TRANSITION_TYPE:
              return object;
            default:
              switch (object = object && object.$$typeof, object) {
                case REACT_CONTEXT_TYPE:
                case REACT_FORWARD_REF_TYPE:
                case REACT_LAZY_TYPE:
                case REACT_MEMO_TYPE:
                  return object;
                case REACT_CONSUMER_TYPE:
                  return object;
                default:
                  return $$typeof;
              }
          }
        case REACT_PORTAL_TYPE:
          return $$typeof;
      }
    }
  }
  return reactIs_production.ContextConsumer = REACT_CONSUMER_TYPE, reactIs_production.ContextProvider = REACT_CONTEXT_TYPE, reactIs_production.Element = REACT_ELEMENT_TYPE, reactIs_production.ForwardRef = REACT_FORWARD_REF_TYPE, reactIs_production.Fragment = REACT_FRAGMENT_TYPE, reactIs_production.Lazy = REACT_LAZY_TYPE, reactIs_production.Memo = REACT_MEMO_TYPE, reactIs_production.Portal = REACT_PORTAL_TYPE, reactIs_production.Profiler = REACT_PROFILER_TYPE, reactIs_production.StrictMode = REACT_STRICT_MODE_TYPE, reactIs_production.Suspense = REACT_SUSPENSE_TYPE, reactIs_production.SuspenseList = REACT_SUSPENSE_LIST_TYPE, reactIs_production.isContextConsumer = function(object) {
    return typeOf(object) === REACT_CONSUMER_TYPE;
  }, reactIs_production.isContextProvider = function(object) {
    return typeOf(object) === REACT_CONTEXT_TYPE;
  }, reactIs_production.isElement = function(object) {
    return typeof object == "object" && object !== null && object.$$typeof === REACT_ELEMENT_TYPE;
  }, reactIs_production.isForwardRef = function(object) {
    return typeOf(object) === REACT_FORWARD_REF_TYPE;
  }, reactIs_production.isFragment = function(object) {
    return typeOf(object) === REACT_FRAGMENT_TYPE;
  }, reactIs_production.isLazy = function(object) {
    return typeOf(object) === REACT_LAZY_TYPE;
  }, reactIs_production.isMemo = function(object) {
    return typeOf(object) === REACT_MEMO_TYPE;
  }, reactIs_production.isPortal = function(object) {
    return typeOf(object) === REACT_PORTAL_TYPE;
  }, reactIs_production.isProfiler = function(object) {
    return typeOf(object) === REACT_PROFILER_TYPE;
  }, reactIs_production.isStrictMode = function(object) {
    return typeOf(object) === REACT_STRICT_MODE_TYPE;
  }, reactIs_production.isSuspense = function(object) {
    return typeOf(object) === REACT_SUSPENSE_TYPE;
  }, reactIs_production.isSuspenseList = function(object) {
    return typeOf(object) === REACT_SUSPENSE_LIST_TYPE;
  }, reactIs_production.isValidElementType = function(type) {
    return typeof type == "string" || typeof type == "function" || type === REACT_FRAGMENT_TYPE || type === REACT_PROFILER_TYPE || type === REACT_STRICT_MODE_TYPE || type === REACT_SUSPENSE_TYPE || type === REACT_SUSPENSE_LIST_TYPE || typeof type == "object" && type !== null && (type.$$typeof === REACT_LAZY_TYPE || type.$$typeof === REACT_MEMO_TYPE || type.$$typeof === REACT_CONTEXT_TYPE || type.$$typeof === REACT_CONSUMER_TYPE || type.$$typeof === REACT_FORWARD_REF_TYPE || type.$$typeof === REACT_CLIENT_REFERENCE || type.getModuleId !== void 0);
  }, reactIs_production.typeOf = typeOf, reactIs_production;
}
var reactIs_development$1 = {};
var hasRequiredReactIs_development$1;
function requireReactIs_development$1() {
  return hasRequiredReactIs_development$1 || (hasRequiredReactIs_development$1 = 1,  false && 0), reactIs_development$1;
}
var hasRequiredReactIs$1;
function requireReactIs$1() {
  return hasRequiredReactIs$1 || (hasRequiredReactIs$1 = 1,  true ? reactIs$1.exports = requireReactIs_production() : 0), reactIs$1.exports;
}
var reactIsExports$1 = requireReactIs$1(), index$1 = getDefaultExportFromCjs(reactIsExports$1), ReactIs19 = _mergeNamespaces({
  __proto__: null,
  default: index$1
}, [reactIsExports$1]), reactIs = { exports: {} }, reactIs_production_min = {};
var hasRequiredReactIs_production_min;
function requireReactIs_production_min() {
  if (hasRequiredReactIs_production_min) return reactIs_production_min;
  hasRequiredReactIs_production_min = 1;
  var b = /* @__PURE__ */ Symbol.for("react.element"), c = /* @__PURE__ */ Symbol.for("react.portal"), d = /* @__PURE__ */ Symbol.for("react.fragment"), e = /* @__PURE__ */ Symbol.for("react.strict_mode"), f2 = /* @__PURE__ */ Symbol.for("react.profiler"), g = /* @__PURE__ */ Symbol.for("react.provider"), h2 = /* @__PURE__ */ Symbol.for("react.context"), k = /* @__PURE__ */ Symbol.for("react.server_context"), l = /* @__PURE__ */ Symbol.for("react.forward_ref"), m2 = /* @__PURE__ */ Symbol.for("react.suspense"), n = /* @__PURE__ */ Symbol.for("react.suspense_list"), p2 = /* @__PURE__ */ Symbol.for("react.memo"), q = /* @__PURE__ */ Symbol.for("react.lazy"), t = /* @__PURE__ */ Symbol.for("react.offscreen"), u;
  u = /* @__PURE__ */ Symbol.for("react.module.reference");
  function v(a2) {
    if (typeof a2 == "object" && a2 !== null) {
      var r = a2.$$typeof;
      switch (r) {
        case b:
          switch (a2 = a2.type, a2) {
            case d:
            case f2:
            case e:
            case m2:
            case n:
              return a2;
            default:
              switch (a2 = a2 && a2.$$typeof, a2) {
                case k:
                case h2:
                case l:
                case q:
                case p2:
                case g:
                  return a2;
                default:
                  return r;
              }
          }
        case c:
          return r;
      }
    }
  }
  return reactIs_production_min.ContextConsumer = h2, reactIs_production_min.ContextProvider = g, reactIs_production_min.Element = b, reactIs_production_min.ForwardRef = l, reactIs_production_min.Fragment = d, reactIs_production_min.Lazy = q, reactIs_production_min.Memo = p2, reactIs_production_min.Portal = c, reactIs_production_min.Profiler = f2, reactIs_production_min.StrictMode = e, reactIs_production_min.Suspense = m2, reactIs_production_min.SuspenseList = n, reactIs_production_min.isAsyncMode = function() {
    return !1;
  }, reactIs_production_min.isConcurrentMode = function() {
    return !1;
  }, reactIs_production_min.isContextConsumer = function(a2) {
    return v(a2) === h2;
  }, reactIs_production_min.isContextProvider = function(a2) {
    return v(a2) === g;
  }, reactIs_production_min.isElement = function(a2) {
    return typeof a2 == "object" && a2 !== null && a2.$$typeof === b;
  }, reactIs_production_min.isForwardRef = function(a2) {
    return v(a2) === l;
  }, reactIs_production_min.isFragment = function(a2) {
    return v(a2) === d;
  }, reactIs_production_min.isLazy = function(a2) {
    return v(a2) === q;
  }, reactIs_production_min.isMemo = function(a2) {
    return v(a2) === p2;
  }, reactIs_production_min.isPortal = function(a2) {
    return v(a2) === c;
  }, reactIs_production_min.isProfiler = function(a2) {
    return v(a2) === f2;
  }, reactIs_production_min.isStrictMode = function(a2) {
    return v(a2) === e;
  }, reactIs_production_min.isSuspense = function(a2) {
    return v(a2) === m2;
  }, reactIs_production_min.isSuspenseList = function(a2) {
    return v(a2) === n;
  }, reactIs_production_min.isValidElementType = function(a2) {
    return typeof a2 == "string" || typeof a2 == "function" || a2 === d || a2 === f2 || a2 === e || a2 === m2 || a2 === n || a2 === t || typeof a2 == "object" && a2 !== null && (a2.$$typeof === q || a2.$$typeof === p2 || a2.$$typeof === g || a2.$$typeof === h2 || a2.$$typeof === l || a2.$$typeof === u || a2.getModuleId !== void 0);
  }, reactIs_production_min.typeOf = v, reactIs_production_min;
}
var reactIs_development = {};
var hasRequiredReactIs_development;
function requireReactIs_development() {
  return hasRequiredReactIs_development || (hasRequiredReactIs_development = 1,  false && 0), reactIs_development;
}
var hasRequiredReactIs;
function requireReactIs() {
  return hasRequiredReactIs || (hasRequiredReactIs = 1,  true ? reactIs.exports = requireReactIs_production_min() : 0), reactIs.exports;
}
var reactIsExports = requireReactIs(), index = getDefaultExportFromCjs(reactIsExports), ReactIs18 = _mergeNamespaces({
  __proto__: null,
  default: index
}, [reactIsExports]), reactIsMethods = [
  "isAsyncMode",
  "isConcurrentMode",
  "isContextConsumer",
  "isContextProvider",
  "isElement",
  "isForwardRef",
  "isFragment",
  "isLazy",
  "isMemo",
  "isPortal",
  "isProfiler",
  "isStrictMode",
  "isSuspense",
  "isSuspenseList",
  "isValidElementType"
], ReactIs = Object.fromEntries(reactIsMethods.map((m2) => [m2, (v) => ReactIs18[m2](v) || ReactIs19[m2](v)]));
function getChildren(arg, children = []) {
  if (Array.isArray(arg))
    for (let item of arg)
      getChildren(item, children);
  else arg != null && arg !== !1 && arg !== "" && children.push(arg);
  return children;
}
function getType(element) {
  let type = element.type;
  if (typeof type == "string")
    return type;
  if (typeof type == "function")
    return type.displayName || type.name || "Unknown";
  if (ReactIs.isFragment(element))
    return "React.Fragment";
  if (ReactIs.isSuspense(element))
    return "React.Suspense";
  if (typeof type == "object" && type !== null) {
    if (ReactIs.isContextProvider(element))
      return "Context.Provider";
    if (ReactIs.isContextConsumer(element))
      return "Context.Consumer";
    if (ReactIs.isForwardRef(element)) {
      if (type.displayName)
        return type.displayName;
      let functionName = type.render.displayName || type.render.name || "";
      return functionName === "" ? "ForwardRef" : `ForwardRef(${functionName})`;
    }
    if (ReactIs.isMemo(element)) {
      let functionName = type.displayName || type.type.displayName || type.type.name || "";
      return functionName === "" ? "Memo" : `Memo(${functionName})`;
    }
  }
  return "UNDEFINED";
}
function getPropKeys$1(element) {
  let { props } = element;
  return Object.keys(props).filter((key) => key !== "children" && props[key] !== void 0).sort();
}
var serialize$1 = (element, config, indentation, depth, refs, printer2) => ++depth > config.maxDepth ? printElementAsLeaf(getType(element), config) : printElement(getType(element), printProps(getPropKeys$1(element), element.props, config, indentation + config.indent, depth, refs, printer2), printChildren(getChildren(element.props.children), config, indentation + config.indent, depth, refs, printer2), config, indentation), test$1 = (val) => val != null && ReactIs.isElement(val), plugin$1 = {
  serialize: serialize$1,
  test: test$1
}, testSymbol = typeof Symbol == "function" && Symbol.for ? /* @__PURE__ */ Symbol.for("react.test.json") : 245830487;
function getPropKeys(object) {
  let { props } = object;
  return props ? Object.keys(props).filter((key) => props[key] !== void 0).sort() : [];
}
var serialize = (object, config, indentation, depth, refs, printer2) => ++depth > config.maxDepth ? printElementAsLeaf(object.type, config) : printElement(object.type, object.props ? printProps(getPropKeys(object), object.props, config, indentation + config.indent, depth, refs, printer2) : "", object.children ? printChildren(object.children, config, indentation + config.indent, depth, refs, printer2) : "", config, indentation), test = (val) => val && val.$$typeof === testSymbol, chunk_QGGIYUMM_plugin = {
  serialize,
  test
}, chunk_QGGIYUMM_toString = Object.prototype.toString, toISOString = Date.prototype.toISOString, errorToString = Error.prototype.toString, regExpToString = RegExp.prototype.toString;
function getConstructorName(val) {
  return typeof val.constructor == "function" && val.constructor.name || "Object";
}
function isWindow(val) {
  return typeof window < "u" && val === window;
}
var SYMBOL_REGEXP = /^Symbol\((.*)\)(.*)$/, NEWLINE_REGEXP = /\n/g, PrettyFormatPluginError = class extends Error {
  constructor(message, stack) {
    super(message), this.stack = stack, this.name = this.constructor.name;
  }
};
function isToStringedArrayType(toStringed) {
  return toStringed === "[object Array]" || toStringed === "[object ArrayBuffer]" || toStringed === "[object DataView]" || toStringed === "[object Float32Array]" || toStringed === "[object Float64Array]" || toStringed === "[object Int8Array]" || toStringed === "[object Int16Array]" || toStringed === "[object Int32Array]" || toStringed === "[object Uint8Array]" || toStringed === "[object Uint8ClampedArray]" || toStringed === "[object Uint16Array]" || toStringed === "[object Uint32Array]";
}
function printNumber(val) {
  return Object.is(val, -0) ? "-0" : String(val);
}
function printBigInt(val) {
  return `${val}n`;
}
function printFunction(val, printFunctionName) {
  return printFunctionName ? `[Function ${val.name || "anonymous"}]` : "[Function]";
}
function printSymbol(val) {
  return String(val).replace(SYMBOL_REGEXP, "Symbol($1)");
}
function printError(val) {
  return `[${errorToString.call(val)}]`;
}
function printBasicValue(val, printFunctionName, escapeRegex, escapeString) {
  if (val === !0 || val === !1)
    return `${val}`;
  if (val === void 0)
    return "undefined";
  if (val === null)
    return "null";
  let typeOf = typeof val;
  if (typeOf === "number")
    return printNumber(val);
  if (typeOf === "bigint")
    return printBigInt(val);
  if (typeOf === "string")
    return escapeString ? `"${val.replaceAll(/"|\\/g, "\\$&")}"` : `"${val}"`;
  if (typeOf === "function")
    return printFunction(val, printFunctionName);
  if (typeOf === "symbol")
    return printSymbol(val);
  let toStringed = chunk_QGGIYUMM_toString.call(val);
  return toStringed === "[object WeakMap]" ? "WeakMap {}" : toStringed === "[object WeakSet]" ? "WeakSet {}" : toStringed === "[object Function]" || toStringed === "[object GeneratorFunction]" ? printFunction(val, printFunctionName) : toStringed === "[object Symbol]" ? printSymbol(val) : toStringed === "[object Date]" ? Number.isNaN(+val) ? "Date { NaN }" : toISOString.call(val) : toStringed === "[object Error]" ? printError(val) : toStringed === "[object RegExp]" ? escapeRegex ? regExpToString.call(val).replaceAll(/[$()*+.?[\\\]^{|}]/g, "\\$&") : regExpToString.call(val) : val instanceof Error ? printError(val) : null;
}
function printComplexValue(val, config, indentation, depth, refs, hasCalledToJSON) {
  if (refs.includes(val))
    return "[Circular]";
  refs = [...refs], refs.push(val);
  let hitMaxDepth = ++depth > config.maxDepth, min = config.min;
  if (config.callToJSON && !hitMaxDepth && val.toJSON && typeof val.toJSON == "function" && !hasCalledToJSON)
    return printer(val.toJSON(), config, indentation, depth, refs, !0);
  let toStringed = chunk_QGGIYUMM_toString.call(val);
  return toStringed === "[object Arguments]" ? hitMaxDepth ? "[Arguments]" : `${min ? "" : "Arguments "}[${printListItems(val, config, indentation, depth, refs, printer)}]` : isToStringedArrayType(toStringed) ? hitMaxDepth ? `[${val.constructor.name}]` : `${min || !config.printBasicPrototype && val.constructor.name === "Array" ? "" : `${val.constructor.name} `}[${printListItems(val, config, indentation, depth, refs, printer)}]` : toStringed === "[object Map]" ? hitMaxDepth ? "[Map]" : `Map {${printIteratorEntries(val.entries(), config, indentation, depth, refs, printer, " => ")}}` : toStringed === "[object Set]" ? hitMaxDepth ? "[Set]" : `Set {${printIteratorValues(val.values(), config, indentation, depth, refs, printer)}}` : hitMaxDepth || isWindow(val) ? `[${getConstructorName(val)}]` : `${min || !config.printBasicPrototype && getConstructorName(val) === "Object" ? "" : `${getConstructorName(val)} `}{${printObjectProperties(val, config, indentation, depth, refs, printer)}}`;
}
var ErrorPlugin = {
  test: (val) => val && val instanceof Error,
  serialize(val, config, indentation, depth, refs, printer2) {
    if (refs.includes(val))
      return "[Circular]";
    refs = [...refs, val];
    let hitMaxDepth = ++depth > config.maxDepth, { message, cause, ...rest } = val, entries = {
      message,
      ...typeof cause < "u" ? { cause } : {},
      ...val instanceof AggregateError ? { errors: val.errors } : {},
      ...rest
    }, name = val.name !== "Error" ? val.name : getConstructorName(val);
    return hitMaxDepth ? `[${name}]` : `${name} {${printIteratorEntries(Object.entries(entries).values(), config, indentation, depth, refs, printer2)}}`;
  }
};
function isNewPlugin(plugin2) {
  return plugin2.serialize != null;
}
function printPlugin(plugin2, val, config, indentation, depth, refs) {
  let printed;
  try {
    printed = isNewPlugin(plugin2) ? plugin2.serialize(val, config, indentation, depth, refs, printer) : plugin2.print(val, (valChild) => printer(valChild, config, indentation, depth, refs), (str) => {
      let indentationNext = indentation + config.indent;
      return indentationNext + str.replaceAll(NEWLINE_REGEXP, `
${indentationNext}`);
    }, {
      edgeSpacing: config.spacingOuter,
      min: config.min,
      spacing: config.spacingInner
    }, config.colors);
  } catch (error) {
    throw new PrettyFormatPluginError(error.message, error.stack);
  }
  if (typeof printed != "string")
    throw new TypeError(`pretty-format: Plugin must return type "string" but instead returned "${typeof printed}".`);
  return printed;
}
function findPlugin(plugins2, val) {
  for (let plugin2 of plugins2)
    try {
      if (plugin2.test(val))
        return plugin2;
    } catch (error) {
      throw new PrettyFormatPluginError(error.message, error.stack);
    }
  return null;
}
function printer(val, config, indentation, depth, refs, hasCalledToJSON) {
  let plugin2 = findPlugin(config.plugins, val);
  if (plugin2 !== null)
    return printPlugin(plugin2, val, config, indentation, depth, refs);
  let basicResult = printBasicValue(val, config.printFunctionName, config.escapeRegex, config.escapeString);
  return basicResult !== null ? basicResult : printComplexValue(val, config, indentation, depth, refs, hasCalledToJSON);
}
var DEFAULT_THEME = {
  comment: "gray",
  content: "reset",
  prop: "yellow",
  tag: "cyan",
  value: "green"
}, DEFAULT_THEME_KEYS = Object.keys(DEFAULT_THEME), DEFAULT_OPTIONS = {
  callToJSON: !0,
  compareKeys: void 0,
  escapeRegex: !1,
  escapeString: !0,
  highlight: !1,
  indent: 2,
  maxDepth: Number.POSITIVE_INFINITY,
  maxWidth: Number.POSITIVE_INFINITY,
  min: !1,
  plugins: [],
  printBasicPrototype: !0,
  printFunctionName: !0,
  theme: DEFAULT_THEME
};
function validateOptions(options) {
  for (let key of Object.keys(options))
    if (!Object.prototype.hasOwnProperty.call(DEFAULT_OPTIONS, key))
      throw new Error(`pretty-format: Unknown option "${key}".`);
  if (options.min && options.indent !== void 0 && options.indent !== 0)
    throw new Error('pretty-format: Options "min" and "indent" cannot be used together.');
}
function getColorsHighlight() {
  return DEFAULT_THEME_KEYS.reduce((colors, key) => {
    let value = DEFAULT_THEME[key], color = value && chunk_QGGIYUMM_s[value];
    if (color && typeof color.close == "string" && typeof color.open == "string")
      colors[key] = color;
    else
      throw new Error(`pretty-format: Option "theme" has a key "${key}" whose value "${value}" is undefined in ansi-styles.`);
    return colors;
  }, /* @__PURE__ */ Object.create(null));
}
function getColorsEmpty() {
  return DEFAULT_THEME_KEYS.reduce((colors, key) => (colors[key] = {
    close: "",
    open: ""
  }, colors), /* @__PURE__ */ Object.create(null));
}
function getPrintFunctionName(options) {
  return options?.printFunctionName ?? DEFAULT_OPTIONS.printFunctionName;
}
function getEscapeRegex(options) {
  return options?.escapeRegex ?? DEFAULT_OPTIONS.escapeRegex;
}
function getEscapeString(options) {
  return options?.escapeString ?? DEFAULT_OPTIONS.escapeString;
}
function getConfig(options) {
  return {
    callToJSON: options?.callToJSON ?? DEFAULT_OPTIONS.callToJSON,
    colors: options?.highlight ? getColorsHighlight() : getColorsEmpty(),
    compareKeys: typeof options?.compareKeys == "function" || options?.compareKeys === null ? options.compareKeys : DEFAULT_OPTIONS.compareKeys,
    escapeRegex: getEscapeRegex(options),
    escapeString: getEscapeString(options),
    indent: options?.min ? "" : createIndent(options?.indent ?? DEFAULT_OPTIONS.indent),
    maxDepth: options?.maxDepth ?? DEFAULT_OPTIONS.maxDepth,
    maxWidth: options?.maxWidth ?? DEFAULT_OPTIONS.maxWidth,
    min: options?.min ?? DEFAULT_OPTIONS.min,
    plugins: options?.plugins ?? DEFAULT_OPTIONS.plugins,
    printBasicPrototype: options?.printBasicPrototype ?? !0,
    printFunctionName: getPrintFunctionName(options),
    spacingInner: options?.min ? " " : `
`,
    spacingOuter: options?.min ? "" : `
`
  };
}
function createIndent(indent) {
  return Array.from({ length: indent + 1 }).join(" ");
}
function format(val, options) {
  if (options && (validateOptions(options), options.plugins)) {
    let plugin2 = findPlugin(options.plugins, val);
    if (plugin2 !== null)
      return printPlugin(plugin2, val, getConfig(options), "", 0, []);
  }
  let basicResult = printBasicValue(val, getPrintFunctionName(options), getEscapeRegex(options), getEscapeString(options));
  return basicResult !== null ? basicResult : printComplexValue(val, getConfig(options), "", 0, []);
}
var plugins = {
  AsymmetricMatcher: plugin$5,
  DOMCollection: plugin$4,
  DOMElement: plugin$3,
  Immutable: plugin$2,
  ReactElement: plugin$1,
  ReactTestComponent: chunk_QGGIYUMM_plugin,
  Error: ErrorPlugin
};

// ../../node_modules/loupe/lib/helpers.js
var ansiColors = {
  bold: ["1", "22"],
  dim: ["2", "22"],
  italic: ["3", "23"],
  underline: ["4", "24"],
  // 5 & 6 are blinking
  inverse: ["7", "27"],
  hidden: ["8", "28"],
  strike: ["9", "29"],
  // 10-20 are fonts
  // 21-29 are resets for 1-9
  black: ["30", "39"],
  red: ["31", "39"],
  green: ["32", "39"],
  yellow: ["33", "39"],
  blue: ["34", "39"],
  magenta: ["35", "39"],
  cyan: ["36", "39"],
  white: ["37", "39"],
  brightblack: ["30;1", "39"],
  brightred: ["31;1", "39"],
  brightgreen: ["32;1", "39"],
  brightyellow: ["33;1", "39"],
  brightblue: ["34;1", "39"],
  brightmagenta: ["35;1", "39"],
  brightcyan: ["36;1", "39"],
  brightwhite: ["37;1", "39"],
  grey: ["90", "39"]
}, styles = {
  special: "cyan",
  number: "yellow",
  bigint: "yellow",
  boolean: "yellow",
  undefined: "grey",
  null: "bold",
  string: "green",
  symbol: "green",
  date: "magenta",
  regexp: "red"
}, truncator = "\u2026";
function colorise(value, styleType) {
  let color = ansiColors[styles[styleType]] || ansiColors[styleType] || "";
  return color ? `\x1B[${color[0]}m${String(value)}\x1B[${color[1]}m` : String(value);
}
function normaliseOptions({
  showHidden = !1,
  depth = 2,
  colors = !1,
  customInspect = !0,
  showProxy = !1,
  maxArrayLength = 1 / 0,
  breakLength = 1 / 0,
  seen = [],
  // eslint-disable-next-line no-shadow
  truncate: truncate2 = 1 / 0,
  stylize = String
} = {}, inspect3) {
  let options = {
    showHidden: !!showHidden,
    depth: Number(depth),
    colors: !!colors,
    customInspect: !!customInspect,
    showProxy: !!showProxy,
    maxArrayLength: Number(maxArrayLength),
    breakLength: Number(breakLength),
    truncate: Number(truncate2),
    seen,
    inspect: inspect3,
    stylize
  };
  return options.colors && (options.stylize = colorise), options;
}
function isHighSurrogate(char) {
  return char >= "\uD800" && char <= "\uDBFF";
}
function truncate(string, length, tail = truncator) {
  string = String(string);
  let tailLength = tail.length, stringLength = string.length;
  if (tailLength > length && stringLength > tailLength)
    return tail;
  if (stringLength > length && stringLength > tailLength) {
    let end = length - tailLength;
    return end > 0 && isHighSurrogate(string[end - 1]) && (end = end - 1), `${string.slice(0, end)}${tail}`;
  }
  return string;
}
function inspectList(list, options, inspectItem, separator = ", ") {
  inspectItem = inspectItem || options.inspect;
  let size = list.length;
  if (size === 0)
    return "";
  let originalLength = options.truncate, output = "", peek = "", truncated = "";
  for (let i = 0; i < size; i += 1) {
    let last = i + 1 === list.length, secondToLast = i + 2 === list.length;
    truncated = `${truncator}(${list.length - i})`;
    let value = list[i];
    options.truncate = originalLength - output.length - (last ? 0 : separator.length);
    let string = peek || inspectItem(value, options) + (last ? "" : separator), nextLength = output.length + string.length, truncatedLength = nextLength + truncated.length;
    if (last && nextLength > originalLength && output.length + truncated.length <= originalLength || !last && !secondToLast && truncatedLength > originalLength || (peek = last ? "" : inspectItem(list[i + 1], options) + (secondToLast ? "" : separator), !last && secondToLast && truncatedLength > originalLength && nextLength + peek.length > originalLength))
      break;
    if (output += string, !last && !secondToLast && nextLength + peek.length >= originalLength) {
      truncated = `${truncator}(${list.length - i - 1})`;
      break;
    }
    truncated = "";
  }
  return `${output}${truncated}`;
}
function quoteComplexKey(key) {
  return key.match(/^[a-zA-Z_][a-zA-Z_0-9]*$/) ? key : JSON.stringify(key).replace(/'/g, "\\'").replace(/\\"/g, '"').replace(/(^"|"$)/g, "'");
}
function inspectProperty([key, value], options) {
  return options.truncate -= 2, typeof key == "string" ? key = quoteComplexKey(key) : typeof key != "number" && (key = `[${options.inspect(key, options)}]`), options.truncate -= key.length, value = options.inspect(value, options), `${key}: ${value}`;
}

// ../../node_modules/loupe/lib/array.js
function inspectArray(array, options) {
  let nonIndexProperties = Object.keys(array).slice(array.length);
  if (!array.length && !nonIndexProperties.length)
    return "[]";
  options.truncate -= 4;
  let listContents = inspectList(array, options);
  options.truncate -= listContents.length;
  let propertyContents = "";
  return nonIndexProperties.length && (propertyContents = inspectList(nonIndexProperties.map((key) => [key, array[key]]), options, inspectProperty)), `[ ${listContents}${propertyContents ? `, ${propertyContents}` : ""} ]`;
}

// ../../node_modules/loupe/lib/typedarray.js
var getArrayName = (array) => typeof Buffer == "function" && array instanceof Buffer ? "Buffer" : array[Symbol.toStringTag] ? array[Symbol.toStringTag] : array.constructor.name;
function inspectTypedArray(array, options) {
  let name = getArrayName(array);
  options.truncate -= name.length + 4;
  let nonIndexProperties = Object.keys(array).slice(array.length);
  if (!array.length && !nonIndexProperties.length)
    return `${name}[]`;
  let output = "";
  for (let i = 0; i < array.length; i++) {
    let string = `${options.stylize(truncate(array[i], options.truncate), "number")}${i === array.length - 1 ? "" : ", "}`;
    if (options.truncate -= string.length, array[i] !== array.length && options.truncate <= 3) {
      output += `${truncator}(${array.length - array[i] + 1})`;
      break;
    }
    output += string;
  }
  let propertyContents = "";
  return nonIndexProperties.length && (propertyContents = inspectList(nonIndexProperties.map((key) => [key, array[key]]), options, inspectProperty)), `${name}[ ${output}${propertyContents ? `, ${propertyContents}` : ""} ]`;
}

// ../../node_modules/loupe/lib/date.js
function inspectDate(dateObject, options) {
  let stringRepresentation = dateObject.toJSON();
  if (stringRepresentation === null)
    return "Invalid Date";
  let split = stringRepresentation.split("T"), date = split[0];
  return options.stylize(`${date}T${truncate(split[1], options.truncate - date.length - 1)}`, "date");
}

// ../../node_modules/loupe/lib/function.js
function inspectFunction(func, options) {
  let functionType = func[Symbol.toStringTag] || "Function", name = func.name;
  return name ? options.stylize(`[${functionType} ${truncate(name, options.truncate - 11)}]`, "special") : options.stylize(`[${functionType}]`, "special");
}

// ../../node_modules/loupe/lib/map.js
function inspectMapEntry([key, value], options) {
  return options.truncate -= 4, key = options.inspect(key, options), options.truncate -= key.length, value = options.inspect(value, options), `${key} => ${value}`;
}
function mapToEntries(map) {
  let entries = [];
  return map.forEach((value, key) => {
    entries.push([key, value]);
  }), entries;
}
function inspectMap(map, options) {
  return map.size === 0 ? "Map{}" : (options.truncate -= 7, `Map{ ${inspectList(mapToEntries(map), options, inspectMapEntry)} }`);
}

// ../../node_modules/loupe/lib/number.js
var chunk_QGGIYUMM_isNaN = Number.isNaN || ((i) => i !== i);
function inspectNumber(number, options) {
  return chunk_QGGIYUMM_isNaN(number) ? options.stylize("NaN", "number") : number === 1 / 0 ? options.stylize("Infinity", "number") : number === -1 / 0 ? options.stylize("-Infinity", "number") : number === 0 ? options.stylize(1 / number === 1 / 0 ? "+0" : "-0", "number") : options.stylize(truncate(String(number), options.truncate), "number");
}

// ../../node_modules/loupe/lib/bigint.js
function inspectBigInt(number, options) {
  let nums = truncate(number.toString(), options.truncate - 1);
  return nums !== truncator && (nums += "n"), options.stylize(nums, "bigint");
}

// ../../node_modules/loupe/lib/regexp.js
function inspectRegExp(value, options) {
  let flags = value.toString().split("/")[2], sourceLength = options.truncate - (2 + flags.length), source = value.source;
  return options.stylize(`/${truncate(source, sourceLength)}/${flags}`, "regexp");
}

// ../../node_modules/loupe/lib/set.js
function arrayFromSet(set) {
  let values = [];
  return set.forEach((value) => {
    values.push(value);
  }), values;
}
function inspectSet(set, options) {
  return set.size === 0 ? "Set{}" : (options.truncate -= 7, `Set{ ${inspectList(arrayFromSet(set), options)} }`);
}

// ../../node_modules/loupe/lib/string.js
var stringEscapeChars = new RegExp("['\\u0000-\\u001f\\u007f-\\u009f\\u00ad\\u0600-\\u0604\\u070f\\u17b4\\u17b5\\u200c-\\u200f\\u2028-\\u202f\\u2060-\\u206f\\ufeff\\ufff0-\\uffff]", "g"), escapeCharacters = {
  "\b": "\\b",
  "	": "\\t",
  "\n": "\\n",
  "\f": "\\f",
  "\r": "\\r",
  "'": "\\'",
  "\\": "\\\\"
}, hex = 16, unicodeLength = 4;
function chunk_QGGIYUMM_escape(char) {
  return escapeCharacters[char] || `\\u${`0000${char.charCodeAt(0).toString(hex)}`.slice(-unicodeLength)}`;
}
function inspectString(string, options) {
  return stringEscapeChars.test(string) && (string = string.replace(stringEscapeChars, chunk_QGGIYUMM_escape)), options.stylize(`'${truncate(string, options.truncate - 2)}'`, "string");
}

// ../../node_modules/loupe/lib/symbol.js
function inspectSymbol(value) {
  return "description" in Symbol.prototype ? value.description ? `Symbol(${value.description})` : "Symbol()" : value.toString();
}

// ../../node_modules/loupe/lib/promise.js
var getPromiseValue = () => "Promise{\u2026}";
try {
  let { getPromiseDetails, kPending, kRejected } = process.binding("util");
  Array.isArray(getPromiseDetails(Promise.resolve())) && (getPromiseValue = (value, options) => {
    let [state, innerValue] = getPromiseDetails(value);
    return state === kPending ? "Promise{<pending>}" : `Promise${state === kRejected ? "!" : ""}{${options.inspect(innerValue, options)}}`;
  });
} catch {
}
var promise_default = getPromiseValue;

// ../../node_modules/loupe/lib/object.js
function inspectObject(object, options) {
  let properties = Object.getOwnPropertyNames(object), symbols = Object.getOwnPropertySymbols ? Object.getOwnPropertySymbols(object) : [];
  if (properties.length === 0 && symbols.length === 0)
    return "{}";
  if (options.truncate -= 4, options.seen = options.seen || [], options.seen.includes(object))
    return "[Circular]";
  options.seen.push(object);
  let propertyContents = inspectList(properties.map((key) => [key, object[key]]), options, inspectProperty), symbolContents = inspectList(symbols.map((key) => [key, object[key]]), options, inspectProperty);
  options.seen.pop();
  let sep = "";
  return propertyContents && symbolContents && (sep = ", "), `{ ${propertyContents}${sep}${symbolContents} }`;
}

// ../../node_modules/loupe/lib/class.js
var toStringTag = typeof Symbol < "u" && Symbol.toStringTag ? Symbol.toStringTag : !1;
function inspectClass(value, options) {
  let name = "";
  return toStringTag && toStringTag in value && (name = value[toStringTag]), name = name || value.constructor.name, (!name || name === "_class") && (name = "<Anonymous Class>"), options.truncate -= name.length, `${name}${inspectObject(value, options)}`;
}

// ../../node_modules/loupe/lib/arguments.js
function inspectArguments(args, options) {
  return args.length === 0 ? "Arguments[]" : (options.truncate -= 13, `Arguments[ ${inspectList(args, options)} ]`);
}

// ../../node_modules/loupe/lib/error.js
var errorKeys = [
  "stack",
  "line",
  "column",
  "name",
  "message",
  "fileName",
  "lineNumber",
  "columnNumber",
  "number",
  "description",
  "cause"
];
function inspectObject2(error, options) {
  let properties = Object.getOwnPropertyNames(error).filter((key) => errorKeys.indexOf(key) === -1), name = error.name;
  options.truncate -= name.length;
  let message = "";
  if (typeof error.message == "string" ? message = truncate(error.message, options.truncate) : properties.unshift("message"), message = message ? `: ${message}` : "", options.truncate -= message.length + 5, options.seen = options.seen || [], options.seen.includes(error))
    return "[Circular]";
  options.seen.push(error);
  let propertyContents = inspectList(properties.map((key) => [key, error[key]]), options, inspectProperty);
  return `${name}${message}${propertyContents ? ` { ${propertyContents} }` : ""}`;
}

// ../../node_modules/loupe/lib/html.js
function inspectAttribute([key, value], options) {
  return options.truncate -= 3, value ? `${options.stylize(String(key), "yellow")}=${options.stylize(`"${value}"`, "string")}` : `${options.stylize(String(key), "yellow")}`;
}
function inspectNodeCollection(collection, options) {
  return inspectList(collection, options, inspectNode, `
`);
}
function inspectNode(node, options) {
  switch (node.nodeType) {
    case 1:
      return inspectHTML(node, options);
    case 3:
      return options.inspect(node.data, options);
    default:
      return options.inspect(node, options);
  }
}
function inspectHTML(element, options) {
  let properties = element.getAttributeNames(), name = element.tagName.toLowerCase(), head = options.stylize(`<${name}`, "special"), headClose = options.stylize(">", "special"), tail = options.stylize(`</${name}>`, "special");
  options.truncate -= name.length * 2 + 5;
  let propertyContents = "";
  properties.length > 0 && (propertyContents += " ", propertyContents += inspectList(properties.map((key) => [key, element.getAttribute(key)]), options, inspectAttribute, " ")), options.truncate -= propertyContents.length;
  let truncate2 = options.truncate, children = inspectNodeCollection(element.children, options);
  return children && children.length > truncate2 && (children = `${truncator}(${element.children.length})`), `${head}${propertyContents}${headClose}${children}${tail}`;
}

// ../../node_modules/loupe/lib/index.js
var symbolsSupported = typeof Symbol == "function" && typeof Symbol.for == "function", chaiInspect = symbolsSupported ? /* @__PURE__ */ Symbol.for("chai/inspect") : "@@chai/inspect", nodeInspect = /* @__PURE__ */ Symbol.for("nodejs.util.inspect.custom"), constructorMap = /* @__PURE__ */ new WeakMap(), stringTagMap = {}, baseTypesMap = {
  undefined: (value, options) => options.stylize("undefined", "undefined"),
  null: (value, options) => options.stylize("null", "null"),
  boolean: (value, options) => options.stylize(String(value), "boolean"),
  Boolean: (value, options) => options.stylize(String(value), "boolean"),
  number: inspectNumber,
  Number: inspectNumber,
  bigint: inspectBigInt,
  BigInt: inspectBigInt,
  string: inspectString,
  String: inspectString,
  function: inspectFunction,
  Function: inspectFunction,
  symbol: inspectSymbol,
  // A Symbol polyfill will return `Symbol` not `symbol` from typedetect
  Symbol: inspectSymbol,
  Array: inspectArray,
  Date: inspectDate,
  Map: inspectMap,
  Set: inspectSet,
  RegExp: inspectRegExp,
  Promise: promise_default,
  // WeakSet, WeakMap are totally opaque to us
  WeakSet: (value, options) => options.stylize("WeakSet{\u2026}", "special"),
  WeakMap: (value, options) => options.stylize("WeakMap{\u2026}", "special"),
  Arguments: inspectArguments,
  Int8Array: inspectTypedArray,
  Uint8Array: inspectTypedArray,
  Uint8ClampedArray: inspectTypedArray,
  Int16Array: inspectTypedArray,
  Uint16Array: inspectTypedArray,
  Int32Array: inspectTypedArray,
  Uint32Array: inspectTypedArray,
  Float32Array: inspectTypedArray,
  Float64Array: inspectTypedArray,
  Generator: () => "",
  DataView: () => "",
  ArrayBuffer: () => "",
  Error: inspectObject2,
  HTMLCollection: inspectNodeCollection,
  NodeList: inspectNodeCollection
}, inspectCustom = (value, options, type) => chaiInspect in value && typeof value[chaiInspect] == "function" ? value[chaiInspect](options) : nodeInspect in value && typeof value[nodeInspect] == "function" ? value[nodeInspect](options.depth, options) : "inspect" in value && typeof value.inspect == "function" ? value.inspect(options.depth, options) : "constructor" in value && constructorMap.has(value.constructor) ? constructorMap.get(value.constructor)(value, options) : stringTagMap[type] ? stringTagMap[type](value, options) : "", toString2 = Object.prototype.toString;
function inspect(value, opts = {}) {
  let options = normaliseOptions(opts, inspect), { customInspect } = options, type = value === null ? "null" : typeof value;
  if (type === "object" && (type = toString2.call(value).slice(8, -1)), type in baseTypesMap)
    return baseTypesMap[type](value, options);
  if (customInspect && value) {
    let output = inspectCustom(value, options, type);
    if (output)
      return typeof output == "string" ? output : inspect(output, options);
  }
  let proto = value ? Object.getPrototypeOf(value) : !1;
  return proto === Object.prototype || proto === null ? inspectObject(value, options) : value && typeof HTMLElement == "function" && value instanceof HTMLElement ? inspectHTML(value, options) : "constructor" in value ? value.constructor !== Object ? inspectClass(value, options) : inspectObject(value, options) : value === Object(value) ? inspectObject(value, options) : options.stylize(String(value), type);
}

// ../../node_modules/@vitest/utils/dist/chunk-_commonjsHelpers.js
var { AsymmetricMatcher, DOMCollection, DOMElement, Immutable, ReactElement, ReactTestComponent } = plugins, PLUGINS = [
  ReactTestComponent,
  ReactElement,
  DOMElement,
  DOMCollection,
  Immutable,
  AsymmetricMatcher
];
function stringify(object, maxDepth = 10, { maxLength, ...options } = {}) {
  let MAX_LENGTH = maxLength ?? 1e4, result;
  try {
    result = format(object, {
      maxDepth,
      escapeString: !1,
      plugins: PLUGINS,
      ...options
    });
  } catch {
    result = format(object, {
      callToJSON: !1,
      maxDepth,
      escapeString: !1,
      plugins: PLUGINS,
      ...options
    });
  }
  return result.length >= MAX_LENGTH && maxDepth > 1 ? stringify(object, Math.floor(Math.min(maxDepth, Number.MAX_SAFE_INTEGER) / 2), {
    maxLength,
    ...options
  }) : result;
}
var formatRegExp = /%[sdjifoOc%]/g;
function format2(...args) {
  if (typeof args[0] != "string") {
    let objects = [];
    for (let i2 = 0; i2 < args.length; i2++)
      objects.push(inspect2(args[i2], {
        depth: 0,
        colors: !1
      }));
    return objects.join(" ");
  }
  let len = args.length, i = 1, template = args[0], str = String(template).replace(formatRegExp, (x) => {
    if (x === "%%")
      return "%";
    if (i >= len)
      return x;
    switch (x) {
      case "%s": {
        let value = args[i++];
        return typeof value == "bigint" ? `${value.toString()}n` : typeof value == "number" && value === 0 && 1 / value < 0 ? "-0" : typeof value == "object" && value !== null ? typeof value.toString == "function" && value.toString !== Object.prototype.toString ? value.toString() : inspect2(value, {
          depth: 0,
          colors: !1
        }) : String(value);
      }
      case "%d": {
        let value = args[i++];
        return typeof value == "bigint" ? `${value.toString()}n` : Number(value).toString();
      }
      case "%i": {
        let value = args[i++];
        return typeof value == "bigint" ? `${value.toString()}n` : Number.parseInt(String(value)).toString();
      }
      case "%f":
        return Number.parseFloat(String(args[i++])).toString();
      case "%o":
        return inspect2(args[i++], {
          showHidden: !0,
          showProxy: !0
        });
      case "%O":
        return inspect2(args[i++]);
      case "%c":
        return i++, "";
      case "%j":
        try {
          return JSON.stringify(args[i++]);
        } catch (err) {
          let m2 = err.message;
          if (m2.includes("circular structure") || m2.includes("cyclic structures") || m2.includes("cyclic object"))
            return "[Circular]";
          throw err;
        }
      default:
        return x;
    }
  });
  for (let x = args[i]; i < len; x = args[++i])
    x === null || typeof x != "object" ? str += ` ${x}` : str += ` ${inspect2(x)}`;
  return str;
}
function inspect2(obj, options = {}) {
  return options.truncate === 0 && (options.truncate = Number.POSITIVE_INFINITY), inspect(obj, options);
}
function getDefaultExportFromCjs2(x) {
  return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, "default") ? x.default : x;
}

// ../../node_modules/@vitest/utils/dist/helpers.js
function assertTypes(value, name, types) {
  let receivedType = typeof value;
  if (!types.includes(receivedType))
    throw new TypeError(`${name} value must be ${types.join(" or ")}, received "${receivedType}"`);
}
function isObject(item) {
  return item != null && typeof item == "object" && !Array.isArray(item);
}
function isFinalObj(obj) {
  return obj === Object.prototype || obj === Function.prototype || obj === RegExp.prototype;
}
function getType2(value) {
  return Object.prototype.toString.apply(value).slice(8, -1);
}
function collectOwnProperties(obj, collector) {
  let collect = typeof collector == "function" ? collector : (key) => collector.add(key);
  Object.getOwnPropertyNames(obj).forEach(collect), Object.getOwnPropertySymbols(obj).forEach(collect);
}
function getOwnProperties(obj) {
  let ownProps = /* @__PURE__ */ new Set();
  return isFinalObj(obj) ? [] : (collectOwnProperties(obj, ownProps), Array.from(ownProps));
}
var defaultCloneOptions = { forceWritable: !1 };
function deepClone(val, options = defaultCloneOptions) {
  return clone(val, /* @__PURE__ */ new WeakMap(), options);
}
function clone(val, seen, options = defaultCloneOptions) {
  let k, out;
  if (seen.has(val))
    return seen.get(val);
  if (Array.isArray(val)) {
    for (out = Array.from({ length: k = val.length }), seen.set(val, out); k--; )
      out[k] = clone(val[k], seen, options);
    return out;
  }
  if (Object.prototype.toString.call(val) === "[object Object]") {
    out = Object.create(Object.getPrototypeOf(val)), seen.set(val, out);
    let props = getOwnProperties(val);
    for (let k2 of props) {
      let descriptor = Object.getOwnPropertyDescriptor(val, k2);
      if (!descriptor)
        continue;
      let cloned = clone(val[k2], seen, options);
      options.forceWritable ? Object.defineProperty(out, k2, {
        enumerable: descriptor.enumerable,
        configurable: !0,
        writable: !0,
        value: cloned
      }) : "get" in descriptor ? Object.defineProperty(out, k2, {
        ...descriptor,
        get() {
          return cloned;
        }
      }) : Object.defineProperty(out, k2, {
        ...descriptor,
        value: cloned
      });
    }
    return out;
  }
  return val;
}
function noop() {
}

// ../../node_modules/@vitest/utils/dist/diff.js
var DIFF_DELETE = -1, DIFF_INSERT = 1, DIFF_EQUAL = 0, Diff = class {
  0;
  1;
  constructor(op, text) {
    this[0] = op, this[1] = text;
  }
};
function diff_commonPrefix(text1, text2) {
  if (!text1 || !text2 || text1.charAt(0) !== text2.charAt(0))
    return 0;
  let pointermin = 0, pointermax = Math.min(text1.length, text2.length), pointermid = pointermax, pointerstart = 0;
  for (; pointermin < pointermid; )
    text1.substring(pointerstart, pointermid) === text2.substring(pointerstart, pointermid) ? (pointermin = pointermid, pointerstart = pointermin) : pointermax = pointermid, pointermid = Math.floor((pointermax - pointermin) / 2 + pointermin);
  return pointermid;
}
function diff_commonSuffix(text1, text2) {
  if (!text1 || !text2 || text1.charAt(text1.length - 1) !== text2.charAt(text2.length - 1))
    return 0;
  let pointermin = 0, pointermax = Math.min(text1.length, text2.length), pointermid = pointermax, pointerend = 0;
  for (; pointermin < pointermid; )
    text1.substring(text1.length - pointermid, text1.length - pointerend) === text2.substring(text2.length - pointermid, text2.length - pointerend) ? (pointermin = pointermid, pointerend = pointermin) : pointermax = pointermid, pointermid = Math.floor((pointermax - pointermin) / 2 + pointermin);
  return pointermid;
}
function diff_commonOverlap_(text1, text2) {
  let text1_length = text1.length, text2_length = text2.length;
  if (text1_length === 0 || text2_length === 0)
    return 0;
  text1_length > text2_length ? text1 = text1.substring(text1_length - text2_length) : text1_length < text2_length && (text2 = text2.substring(0, text1_length));
  let text_length = Math.min(text1_length, text2_length);
  if (text1 === text2)
    return text_length;
  let best = 0, length = 1;
  for (; ; ) {
    let pattern = text1.substring(text_length - length), found = text2.indexOf(pattern);
    if (found === -1)
      return best;
    length += found, (found === 0 || text1.substring(text_length - length) === text2.substring(0, length)) && (best = length, length++);
  }
}
function diff_cleanupSemantic(diffs) {
  let changes = !1, equalities = [], equalitiesLength = 0, lastEquality = null, pointer = 0, length_insertions1 = 0, length_deletions1 = 0, length_insertions2 = 0, length_deletions2 = 0;
  for (; pointer < diffs.length; )
    diffs[pointer][0] === DIFF_EQUAL ? (equalities[equalitiesLength++] = pointer, length_insertions1 = length_insertions2, length_deletions1 = length_deletions2, length_insertions2 = 0, length_deletions2 = 0, lastEquality = diffs[pointer][1]) : (diffs[pointer][0] === DIFF_INSERT ? length_insertions2 += diffs[pointer][1].length : length_deletions2 += diffs[pointer][1].length, lastEquality && lastEquality.length <= Math.max(length_insertions1, length_deletions1) && lastEquality.length <= Math.max(length_insertions2, length_deletions2) && (diffs.splice(equalities[equalitiesLength - 1], 0, new Diff(DIFF_DELETE, lastEquality)), diffs[equalities[equalitiesLength - 1] + 1][0] = DIFF_INSERT, equalitiesLength--, equalitiesLength--, pointer = equalitiesLength > 0 ? equalities[equalitiesLength - 1] : -1, length_insertions1 = 0, length_deletions1 = 0, length_insertions2 = 0, length_deletions2 = 0, lastEquality = null, changes = !0)), pointer++;
  for (changes && diff_cleanupMerge(diffs), diff_cleanupSemanticLossless(diffs), pointer = 1; pointer < diffs.length; ) {
    if (diffs[pointer - 1][0] === DIFF_DELETE && diffs[pointer][0] === DIFF_INSERT) {
      let deletion = diffs[pointer - 1][1], insertion = diffs[pointer][1], overlap_length1 = diff_commonOverlap_(deletion, insertion), overlap_length2 = diff_commonOverlap_(insertion, deletion);
      overlap_length1 >= overlap_length2 ? (overlap_length1 >= deletion.length / 2 || overlap_length1 >= insertion.length / 2) && (diffs.splice(pointer, 0, new Diff(DIFF_EQUAL, insertion.substring(0, overlap_length1))), diffs[pointer - 1][1] = deletion.substring(0, deletion.length - overlap_length1), diffs[pointer + 1][1] = insertion.substring(overlap_length1), pointer++) : (overlap_length2 >= deletion.length / 2 || overlap_length2 >= insertion.length / 2) && (diffs.splice(pointer, 0, new Diff(DIFF_EQUAL, deletion.substring(0, overlap_length2))), diffs[pointer - 1][0] = DIFF_INSERT, diffs[pointer - 1][1] = insertion.substring(0, insertion.length - overlap_length2), diffs[pointer + 1][0] = DIFF_DELETE, diffs[pointer + 1][1] = deletion.substring(overlap_length2), pointer++), pointer++;
    }
    pointer++;
  }
}
var nonAlphaNumericRegex_ = /[^a-z0-9]/i, whitespaceRegex_ = /\s/, linebreakRegex_ = /[\r\n]/, blanklineEndRegex_ = /\n\r?\n$/, blanklineStartRegex_ = /^\r?\n\r?\n/;
function diff_cleanupSemanticLossless(diffs) {
  let pointer = 1;
  for (; pointer < diffs.length - 1; ) {
    if (diffs[pointer - 1][0] === DIFF_EQUAL && diffs[pointer + 1][0] === DIFF_EQUAL) {
      let equality1 = diffs[pointer - 1][1], edit = diffs[pointer][1], equality2 = diffs[pointer + 1][1], commonOffset = diff_commonSuffix(equality1, edit);
      if (commonOffset) {
        let commonString = edit.substring(edit.length - commonOffset);
        equality1 = equality1.substring(0, equality1.length - commonOffset), edit = commonString + edit.substring(0, edit.length - commonOffset), equality2 = commonString + equality2;
      }
      let bestEquality1 = equality1, bestEdit = edit, bestEquality2 = equality2, bestScore = diff_cleanupSemanticScore_(equality1, edit) + diff_cleanupSemanticScore_(edit, equality2);
      for (; edit.charAt(0) === equality2.charAt(0); ) {
        equality1 += edit.charAt(0), edit = edit.substring(1) + equality2.charAt(0), equality2 = equality2.substring(1);
        let score = diff_cleanupSemanticScore_(equality1, edit) + diff_cleanupSemanticScore_(edit, equality2);
        score >= bestScore && (bestScore = score, bestEquality1 = equality1, bestEdit = edit, bestEquality2 = equality2);
      }
      diffs[pointer - 1][1] !== bestEquality1 && (bestEquality1 ? diffs[pointer - 1][1] = bestEquality1 : (diffs.splice(pointer - 1, 1), pointer--), diffs[pointer][1] = bestEdit, bestEquality2 ? diffs[pointer + 1][1] = bestEquality2 : (diffs.splice(pointer + 1, 1), pointer--));
    }
    pointer++;
  }
}
function diff_cleanupMerge(diffs) {
  diffs.push(new Diff(DIFF_EQUAL, ""));
  let pointer = 0, count_delete = 0, count_insert = 0, text_delete = "", text_insert = "", commonlength;
  for (; pointer < diffs.length; )
    switch (diffs[pointer][0]) {
      case DIFF_INSERT:
        count_insert++, text_insert += diffs[pointer][1], pointer++;
        break;
      case DIFF_DELETE:
        count_delete++, text_delete += diffs[pointer][1], pointer++;
        break;
      case DIFF_EQUAL:
        count_delete + count_insert > 1 ? (count_delete !== 0 && count_insert !== 0 && (commonlength = diff_commonPrefix(text_insert, text_delete), commonlength !== 0 && (pointer - count_delete - count_insert > 0 && diffs[pointer - count_delete - count_insert - 1][0] === DIFF_EQUAL ? diffs[pointer - count_delete - count_insert - 1][1] += text_insert.substring(0, commonlength) : (diffs.splice(0, 0, new Diff(DIFF_EQUAL, text_insert.substring(0, commonlength))), pointer++), text_insert = text_insert.substring(commonlength), text_delete = text_delete.substring(commonlength)), commonlength = diff_commonSuffix(text_insert, text_delete), commonlength !== 0 && (diffs[pointer][1] = text_insert.substring(text_insert.length - commonlength) + diffs[pointer][1], text_insert = text_insert.substring(0, text_insert.length - commonlength), text_delete = text_delete.substring(0, text_delete.length - commonlength))), pointer -= count_delete + count_insert, diffs.splice(pointer, count_delete + count_insert), text_delete.length && (diffs.splice(pointer, 0, new Diff(DIFF_DELETE, text_delete)), pointer++), text_insert.length && (diffs.splice(pointer, 0, new Diff(DIFF_INSERT, text_insert)), pointer++), pointer++) : pointer !== 0 && diffs[pointer - 1][0] === DIFF_EQUAL ? (diffs[pointer - 1][1] += diffs[pointer][1], diffs.splice(pointer, 1)) : pointer++, count_insert = 0, count_delete = 0, text_delete = "", text_insert = "";
        break;
    }
  diffs[diffs.length - 1][1] === "" && diffs.pop();
  let changes = !1;
  for (pointer = 1; pointer < diffs.length - 1; )
    diffs[pointer - 1][0] === DIFF_EQUAL && diffs[pointer + 1][0] === DIFF_EQUAL && (diffs[pointer][1].substring(diffs[pointer][1].length - diffs[pointer - 1][1].length) === diffs[pointer - 1][1] ? (diffs[pointer][1] = diffs[pointer - 1][1] + diffs[pointer][1].substring(0, diffs[pointer][1].length - diffs[pointer - 1][1].length), diffs[pointer + 1][1] = diffs[pointer - 1][1] + diffs[pointer + 1][1], diffs.splice(pointer - 1, 1), changes = !0) : diffs[pointer][1].substring(0, diffs[pointer + 1][1].length) === diffs[pointer + 1][1] && (diffs[pointer - 1][1] += diffs[pointer + 1][1], diffs[pointer][1] = diffs[pointer][1].substring(diffs[pointer + 1][1].length) + diffs[pointer + 1][1], diffs.splice(pointer + 1, 1), changes = !0)), pointer++;
  changes && diff_cleanupMerge(diffs);
}
function diff_cleanupSemanticScore_(one, two) {
  if (!one || !two)
    return 6;
  let char1 = one.charAt(one.length - 1), char2 = two.charAt(0), nonAlphaNumeric1 = char1.match(nonAlphaNumericRegex_), nonAlphaNumeric2 = char2.match(nonAlphaNumericRegex_), whitespace1 = nonAlphaNumeric1 && char1.match(whitespaceRegex_), whitespace2 = nonAlphaNumeric2 && char2.match(whitespaceRegex_), lineBreak1 = whitespace1 && char1.match(linebreakRegex_), lineBreak2 = whitespace2 && char2.match(linebreakRegex_), blankLine1 = lineBreak1 && one.match(blanklineEndRegex_), blankLine2 = lineBreak2 && two.match(blanklineStartRegex_);
  return blankLine1 || blankLine2 ? 5 : lineBreak1 || lineBreak2 ? 4 : nonAlphaNumeric1 && !whitespace1 && whitespace2 ? 3 : whitespace1 || whitespace2 ? 2 : nonAlphaNumeric1 || nonAlphaNumeric2 ? 1 : 0;
}
var NO_DIFF_MESSAGE = "Compared values have no visual difference.", SIMILAR_MESSAGE = "Compared values serialize to the same structure.\nPrinting internal object structure without calling `toJSON` instead.", build = {}, hasRequiredBuild;
function requireBuild() {
  if (hasRequiredBuild) return build;
  hasRequiredBuild = 1, Object.defineProperty(build, "__esModule", {
    value: !0
  }), build.default = diffSequence;
  let pkg = "diff-sequences", NOT_YET_SET = 0, countCommonItemsF = (aIndex, aEnd, bIndex, bEnd, isCommon) => {
    let nCommon = 0;
    for (; aIndex < aEnd && bIndex < bEnd && isCommon(aIndex, bIndex); )
      aIndex += 1, bIndex += 1, nCommon += 1;
    return nCommon;
  }, countCommonItemsR = (aStart, aIndex, bStart, bIndex, isCommon) => {
    let nCommon = 0;
    for (; aStart <= aIndex && bStart <= bIndex && isCommon(aIndex, bIndex); )
      aIndex -= 1, bIndex -= 1, nCommon += 1;
    return nCommon;
  }, extendPathsF = (d, aEnd, bEnd, bF, isCommon, aIndexesF, iMaxF) => {
    let iF = 0, kF = -d, aFirst = aIndexesF[iF], aIndexPrev1 = aFirst;
    aIndexesF[iF] += countCommonItemsF(
      aFirst + 1,
      aEnd,
      bF + aFirst - kF + 1,
      bEnd,
      isCommon
    );
    let nF = d < iMaxF ? d : iMaxF;
    for (iF += 1, kF += 2; iF <= nF; iF += 1, kF += 2) {
      if (iF !== d && aIndexPrev1 < aIndexesF[iF])
        aFirst = aIndexesF[iF];
      else if (aFirst = aIndexPrev1 + 1, aEnd <= aFirst)
        return iF - 1;
      aIndexPrev1 = aIndexesF[iF], aIndexesF[iF] = aFirst + countCommonItemsF(aFirst + 1, aEnd, bF + aFirst - kF + 1, bEnd, isCommon);
    }
    return iMaxF;
  }, extendPathsR = (d, aStart, bStart, bR, isCommon, aIndexesR, iMaxR) => {
    let iR = 0, kR = d, aFirst = aIndexesR[iR], aIndexPrev1 = aFirst;
    aIndexesR[iR] -= countCommonItemsR(
      aStart,
      aFirst - 1,
      bStart,
      bR + aFirst - kR - 1,
      isCommon
    );
    let nR = d < iMaxR ? d : iMaxR;
    for (iR += 1, kR -= 2; iR <= nR; iR += 1, kR -= 2) {
      if (iR !== d && aIndexesR[iR] < aIndexPrev1)
        aFirst = aIndexesR[iR];
      else if (aFirst = aIndexPrev1 - 1, aFirst < aStart)
        return iR - 1;
      aIndexPrev1 = aIndexesR[iR], aIndexesR[iR] = aFirst - countCommonItemsR(
        aStart,
        aFirst - 1,
        bStart,
        bR + aFirst - kR - 1,
        isCommon
      );
    }
    return iMaxR;
  }, extendOverlappablePathsF = (d, aStart, aEnd, bStart, bEnd, isCommon, aIndexesF, iMaxF, aIndexesR, iMaxR, division) => {
    let bF = bStart - aStart, aLength = aEnd - aStart, baDeltaLength = bEnd - bStart - aLength, kMinOverlapF = -baDeltaLength - (d - 1), kMaxOverlapF = -baDeltaLength + (d - 1), aIndexPrev1 = NOT_YET_SET, nF = d < iMaxF ? d : iMaxF;
    for (let iF = 0, kF = -d; iF <= nF; iF += 1, kF += 2) {
      let insert = iF === 0 || iF !== d && aIndexPrev1 < aIndexesF[iF], aLastPrev = insert ? aIndexesF[iF] : aIndexPrev1, aFirst = insert ? aLastPrev : aLastPrev + 1, bFirst = bF + aFirst - kF, nCommonF = countCommonItemsF(
        aFirst + 1,
        aEnd,
        bFirst + 1,
        bEnd,
        isCommon
      ), aLast = aFirst + nCommonF;
      if (aIndexPrev1 = aIndexesF[iF], aIndexesF[iF] = aLast, kMinOverlapF <= kF && kF <= kMaxOverlapF) {
        let iR = (d - 1 - (kF + baDeltaLength)) / 2;
        if (iR <= iMaxR && aIndexesR[iR] - 1 <= aLast) {
          let bLastPrev = bF + aLastPrev - (insert ? kF + 1 : kF - 1), nCommonR = countCommonItemsR(
            aStart,
            aLastPrev,
            bStart,
            bLastPrev,
            isCommon
          ), aIndexPrevFirst = aLastPrev - nCommonR, bIndexPrevFirst = bLastPrev - nCommonR, aEndPreceding = aIndexPrevFirst + 1, bEndPreceding = bIndexPrevFirst + 1;
          division.nChangePreceding = d - 1, d - 1 === aEndPreceding + bEndPreceding - aStart - bStart ? (division.aEndPreceding = aStart, division.bEndPreceding = bStart) : (division.aEndPreceding = aEndPreceding, division.bEndPreceding = bEndPreceding), division.nCommonPreceding = nCommonR, nCommonR !== 0 && (division.aCommonPreceding = aEndPreceding, division.bCommonPreceding = bEndPreceding), division.nCommonFollowing = nCommonF, nCommonF !== 0 && (division.aCommonFollowing = aFirst + 1, division.bCommonFollowing = bFirst + 1);
          let aStartFollowing = aLast + 1, bStartFollowing = bFirst + nCommonF + 1;
          return division.nChangeFollowing = d - 1, d - 1 === aEnd + bEnd - aStartFollowing - bStartFollowing ? (division.aStartFollowing = aEnd, division.bStartFollowing = bEnd) : (division.aStartFollowing = aStartFollowing, division.bStartFollowing = bStartFollowing), !0;
        }
      }
    }
    return !1;
  }, extendOverlappablePathsR = (d, aStart, aEnd, bStart, bEnd, isCommon, aIndexesF, iMaxF, aIndexesR, iMaxR, division) => {
    let bR = bEnd - aEnd, aLength = aEnd - aStart, baDeltaLength = bEnd - bStart - aLength, kMinOverlapR = baDeltaLength - d, kMaxOverlapR = baDeltaLength + d, aIndexPrev1 = NOT_YET_SET, nR = d < iMaxR ? d : iMaxR;
    for (let iR = 0, kR = d; iR <= nR; iR += 1, kR -= 2) {
      let insert = iR === 0 || iR !== d && aIndexesR[iR] < aIndexPrev1, aLastPrev = insert ? aIndexesR[iR] : aIndexPrev1, aFirst = insert ? aLastPrev : aLastPrev - 1, bFirst = bR + aFirst - kR, nCommonR = countCommonItemsR(
        aStart,
        aFirst - 1,
        bStart,
        bFirst - 1,
        isCommon
      ), aLast = aFirst - nCommonR;
      if (aIndexPrev1 = aIndexesR[iR], aIndexesR[iR] = aLast, kMinOverlapR <= kR && kR <= kMaxOverlapR) {
        let iF = (d + (kR - baDeltaLength)) / 2;
        if (iF <= iMaxF && aLast - 1 <= aIndexesF[iF]) {
          let bLast = bFirst - nCommonR;
          if (division.nChangePreceding = d, d === aLast + bLast - aStart - bStart ? (division.aEndPreceding = aStart, division.bEndPreceding = bStart) : (division.aEndPreceding = aLast, division.bEndPreceding = bLast), division.nCommonPreceding = nCommonR, nCommonR !== 0 && (division.aCommonPreceding = aLast, division.bCommonPreceding = bLast), division.nChangeFollowing = d - 1, d === 1)
            division.nCommonFollowing = 0, division.aStartFollowing = aEnd, division.bStartFollowing = bEnd;
          else {
            let bLastPrev = bR + aLastPrev - (insert ? kR - 1 : kR + 1), nCommonF = countCommonItemsF(
              aLastPrev,
              aEnd,
              bLastPrev,
              bEnd,
              isCommon
            );
            division.nCommonFollowing = nCommonF, nCommonF !== 0 && (division.aCommonFollowing = aLastPrev, division.bCommonFollowing = bLastPrev);
            let aStartFollowing = aLastPrev + nCommonF, bStartFollowing = bLastPrev + nCommonF;
            d - 1 === aEnd + bEnd - aStartFollowing - bStartFollowing ? (division.aStartFollowing = aEnd, division.bStartFollowing = bEnd) : (division.aStartFollowing = aStartFollowing, division.bStartFollowing = bStartFollowing);
          }
          return !0;
        }
      }
    }
    return !1;
  }, divide = (nChange, aStart, aEnd, bStart, bEnd, isCommon, aIndexesF, aIndexesR, division) => {
    let bF = bStart - aStart, bR = bEnd - aEnd, aLength = aEnd - aStart, bLength = bEnd - bStart, baDeltaLength = bLength - aLength, iMaxF = aLength, iMaxR = aLength;
    if (aIndexesF[0] = aStart - 1, aIndexesR[0] = aEnd, baDeltaLength % 2 === 0) {
      let dMin = (nChange || baDeltaLength) / 2, dMax = (aLength + bLength) / 2;
      for (let d = 1; d <= dMax; d += 1)
        if (iMaxF = extendPathsF(d, aEnd, bEnd, bF, isCommon, aIndexesF, iMaxF), d < dMin)
          iMaxR = extendPathsR(d, aStart, bStart, bR, isCommon, aIndexesR, iMaxR);
        else if (
          // If a reverse path overlaps a forward path in the same diagonal,
          // return a division of the index intervals at the middle change.
          extendOverlappablePathsR(
            d,
            aStart,
            aEnd,
            bStart,
            bEnd,
            isCommon,
            aIndexesF,
            iMaxF,
            aIndexesR,
            iMaxR,
            division
          )
        )
          return;
    } else {
      let dMin = ((nChange || baDeltaLength) + 1) / 2, dMax = (aLength + bLength + 1) / 2, d = 1;
      for (iMaxF = extendPathsF(d, aEnd, bEnd, bF, isCommon, aIndexesF, iMaxF), d += 1; d <= dMax; d += 1)
        if (iMaxR = extendPathsR(
          d - 1,
          aStart,
          bStart,
          bR,
          isCommon,
          aIndexesR,
          iMaxR
        ), d < dMin)
          iMaxF = extendPathsF(d, aEnd, bEnd, bF, isCommon, aIndexesF, iMaxF);
        else if (
          // If a forward path overlaps a reverse path in the same diagonal,
          // return a division of the index intervals at the middle change.
          extendOverlappablePathsF(
            d,
            aStart,
            aEnd,
            bStart,
            bEnd,
            isCommon,
            aIndexesF,
            iMaxF,
            aIndexesR,
            iMaxR,
            division
          )
        )
          return;
    }
    throw new Error(
      `${pkg}: no overlap aStart=${aStart} aEnd=${aEnd} bStart=${bStart} bEnd=${bEnd}`
    );
  }, findSubsequences = (nChange, aStart, aEnd, bStart, bEnd, transposed, callbacks, aIndexesF, aIndexesR, division) => {
    if (bEnd - bStart < aEnd - aStart) {
      if (transposed = !transposed, transposed && callbacks.length === 1) {
        let { foundSubsequence: foundSubsequence2, isCommon: isCommon2 } = callbacks[0];
        callbacks[1] = {
          foundSubsequence: (nCommon, bCommon, aCommon) => {
            foundSubsequence2(nCommon, aCommon, bCommon);
          },
          isCommon: (bIndex, aIndex) => isCommon2(aIndex, bIndex)
        };
      }
      let tStart = aStart, tEnd = aEnd;
      aStart = bStart, aEnd = bEnd, bStart = tStart, bEnd = tEnd;
    }
    let { foundSubsequence, isCommon } = callbacks[transposed ? 1 : 0];
    divide(
      nChange,
      aStart,
      aEnd,
      bStart,
      bEnd,
      isCommon,
      aIndexesF,
      aIndexesR,
      division
    );
    let {
      nChangePreceding,
      aEndPreceding,
      bEndPreceding,
      nCommonPreceding,
      aCommonPreceding,
      bCommonPreceding,
      nCommonFollowing,
      aCommonFollowing,
      bCommonFollowing,
      nChangeFollowing,
      aStartFollowing,
      bStartFollowing
    } = division;
    aStart < aEndPreceding && bStart < bEndPreceding && findSubsequences(
      nChangePreceding,
      aStart,
      aEndPreceding,
      bStart,
      bEndPreceding,
      transposed,
      callbacks,
      aIndexesF,
      aIndexesR,
      division
    ), nCommonPreceding !== 0 && foundSubsequence(nCommonPreceding, aCommonPreceding, bCommonPreceding), nCommonFollowing !== 0 && foundSubsequence(nCommonFollowing, aCommonFollowing, bCommonFollowing), aStartFollowing < aEnd && bStartFollowing < bEnd && findSubsequences(
      nChangeFollowing,
      aStartFollowing,
      aEnd,
      bStartFollowing,
      bEnd,
      transposed,
      callbacks,
      aIndexesF,
      aIndexesR,
      division
    );
  }, validateLength = (name, arg) => {
    if (typeof arg != "number")
      throw new TypeError(`${pkg}: ${name} typeof ${typeof arg} is not a number`);
    if (!Number.isSafeInteger(arg))
      throw new RangeError(`${pkg}: ${name} value ${arg} is not a safe integer`);
    if (arg < 0)
      throw new RangeError(`${pkg}: ${name} value ${arg} is a negative integer`);
  }, validateCallback = (name, arg) => {
    let type = typeof arg;
    if (type !== "function")
      throw new TypeError(`${pkg}: ${name} typeof ${type} is not a function`);
  };
  function diffSequence(aLength, bLength, isCommon, foundSubsequence) {
    validateLength("aLength", aLength), validateLength("bLength", bLength), validateCallback("isCommon", isCommon), validateCallback("foundSubsequence", foundSubsequence);
    let nCommonF = countCommonItemsF(0, aLength, 0, bLength, isCommon);
    if (nCommonF !== 0 && foundSubsequence(nCommonF, 0, 0), aLength !== nCommonF || bLength !== nCommonF) {
      let aStart = nCommonF, bStart = nCommonF, nCommonR = countCommonItemsR(
        aStart,
        aLength - 1,
        bStart,
        bLength - 1,
        isCommon
      ), aEnd = aLength - nCommonR, bEnd = bLength - nCommonR, nCommonFR = nCommonF + nCommonR;
      aLength !== nCommonFR && bLength !== nCommonFR && findSubsequences(
        0,
        aStart,
        aEnd,
        bStart,
        bEnd,
        !1,
        [
          {
            foundSubsequence,
            isCommon
          }
        ],
        [NOT_YET_SET],
        [NOT_YET_SET],
        {
          aCommonFollowing: NOT_YET_SET,
          aCommonPreceding: NOT_YET_SET,
          aEndPreceding: NOT_YET_SET,
          aStartFollowing: NOT_YET_SET,
          bCommonFollowing: NOT_YET_SET,
          bCommonPreceding: NOT_YET_SET,
          bEndPreceding: NOT_YET_SET,
          bStartFollowing: NOT_YET_SET,
          nChangeFollowing: NOT_YET_SET,
          nChangePreceding: NOT_YET_SET,
          nCommonFollowing: NOT_YET_SET,
          nCommonPreceding: NOT_YET_SET
        }
      ), nCommonR !== 0 && foundSubsequence(nCommonR, aEnd, bEnd);
    }
  }
  return build;
}
var buildExports = requireBuild(), diffSequences = getDefaultExportFromCjs2(buildExports);
function formatTrailingSpaces(line, trailingSpaceFormatter) {
  return line.replace(/\s+$/, (match) => trailingSpaceFormatter(match));
}
function printDiffLine(line, isFirstOrLast, color, indicator, trailingSpaceFormatter, emptyFirstOrLastLinePlaceholder) {
  return line.length !== 0 ? color(`${indicator} ${formatTrailingSpaces(line, trailingSpaceFormatter)}`) : indicator !== " " ? color(indicator) : isFirstOrLast && emptyFirstOrLastLinePlaceholder.length !== 0 ? color(`${indicator} ${emptyFirstOrLastLinePlaceholder}`) : "";
}
function printDeleteLine(line, isFirstOrLast, { aColor, aIndicator, changeLineTrailingSpaceColor, emptyFirstOrLastLinePlaceholder }) {
  return printDiffLine(line, isFirstOrLast, aColor, aIndicator, changeLineTrailingSpaceColor, emptyFirstOrLastLinePlaceholder);
}
function printInsertLine(line, isFirstOrLast, { bColor, bIndicator, changeLineTrailingSpaceColor, emptyFirstOrLastLinePlaceholder }) {
  return printDiffLine(line, isFirstOrLast, bColor, bIndicator, changeLineTrailingSpaceColor, emptyFirstOrLastLinePlaceholder);
}
function printCommonLine(line, isFirstOrLast, { commonColor, commonIndicator, commonLineTrailingSpaceColor, emptyFirstOrLastLinePlaceholder }) {
  return printDiffLine(line, isFirstOrLast, commonColor, commonIndicator, commonLineTrailingSpaceColor, emptyFirstOrLastLinePlaceholder);
}
function createPatchMark(aStart, aEnd, bStart, bEnd, { patchColor }) {
  return patchColor(`@@ -${aStart + 1},${aEnd - aStart} +${bStart + 1},${bEnd - bStart} @@`);
}
function joinAlignedDiffsNoExpand(diffs, options) {
  let iLength = diffs.length, nContextLines = options.contextLines, nContextLines2 = nContextLines + nContextLines, jLength = iLength, hasExcessAtStartOrEnd = !1, nExcessesBetweenChanges = 0, i = 0;
  for (; i !== iLength; ) {
    let iStart = i;
    for (; i !== iLength && diffs[i][0] === DIFF_EQUAL; )
      i += 1;
    if (iStart !== i)
      if (iStart === 0)
        i > nContextLines && (jLength -= i - nContextLines, hasExcessAtStartOrEnd = !0);
      else if (i === iLength) {
        let n = i - iStart;
        n > nContextLines && (jLength -= n - nContextLines, hasExcessAtStartOrEnd = !0);
      } else {
        let n = i - iStart;
        n > nContextLines2 && (jLength -= n - nContextLines2, nExcessesBetweenChanges += 1);
      }
    for (; i !== iLength && diffs[i][0] !== DIFF_EQUAL; )
      i += 1;
  }
  let hasPatch = nExcessesBetweenChanges !== 0 || hasExcessAtStartOrEnd;
  nExcessesBetweenChanges !== 0 ? jLength += nExcessesBetweenChanges + 1 : hasExcessAtStartOrEnd && (jLength += 1);
  let jLast = jLength - 1, lines = [], jPatchMark = 0;
  hasPatch && lines.push("");
  let aStart = 0, bStart = 0, aEnd = 0, bEnd = 0, pushCommonLine = (line) => {
    let j = lines.length;
    lines.push(printCommonLine(line, j === 0 || j === jLast, options)), aEnd += 1, bEnd += 1;
  }, pushDeleteLine = (line) => {
    let j = lines.length;
    lines.push(printDeleteLine(line, j === 0 || j === jLast, options)), aEnd += 1;
  }, pushInsertLine = (line) => {
    let j = lines.length;
    lines.push(printInsertLine(line, j === 0 || j === jLast, options)), bEnd += 1;
  };
  for (i = 0; i !== iLength; ) {
    let iStart = i;
    for (; i !== iLength && diffs[i][0] === DIFF_EQUAL; )
      i += 1;
    if (iStart !== i)
      if (iStart === 0) {
        i > nContextLines && (iStart = i - nContextLines, aStart = iStart, bStart = iStart, aEnd = aStart, bEnd = bStart);
        for (let iCommon = iStart; iCommon !== i; iCommon += 1)
          pushCommonLine(diffs[iCommon][1]);
      } else if (i === iLength) {
        let iEnd = i - iStart > nContextLines ? iStart + nContextLines : i;
        for (let iCommon = iStart; iCommon !== iEnd; iCommon += 1)
          pushCommonLine(diffs[iCommon][1]);
      } else {
        let nCommon = i - iStart;
        if (nCommon > nContextLines2) {
          let iEnd = iStart + nContextLines;
          for (let iCommon = iStart; iCommon !== iEnd; iCommon += 1)
            pushCommonLine(diffs[iCommon][1]);
          lines[jPatchMark] = createPatchMark(aStart, aEnd, bStart, bEnd, options), jPatchMark = lines.length, lines.push("");
          let nOmit = nCommon - nContextLines2;
          aStart = aEnd + nOmit, bStart = bEnd + nOmit, aEnd = aStart, bEnd = bStart;
          for (let iCommon = i - nContextLines; iCommon !== i; iCommon += 1)
            pushCommonLine(diffs[iCommon][1]);
        } else
          for (let iCommon = iStart; iCommon !== i; iCommon += 1)
            pushCommonLine(diffs[iCommon][1]);
      }
    for (; i !== iLength && diffs[i][0] === DIFF_DELETE; )
      pushDeleteLine(diffs[i][1]), i += 1;
    for (; i !== iLength && diffs[i][0] === DIFF_INSERT; )
      pushInsertLine(diffs[i][1]), i += 1;
  }
  return hasPatch && (lines[jPatchMark] = createPatchMark(aStart, aEnd, bStart, bEnd, options)), lines.join(`
`);
}
function joinAlignedDiffsExpand(diffs, options) {
  return diffs.map((diff2, i, diffs2) => {
    let line = diff2[1], isFirstOrLast = i === 0 || i === diffs2.length - 1;
    switch (diff2[0]) {
      case DIFF_DELETE:
        return printDeleteLine(line, isFirstOrLast, options);
      case DIFF_INSERT:
        return printInsertLine(line, isFirstOrLast, options);
      default:
        return printCommonLine(line, isFirstOrLast, options);
    }
  }).join(`
`);
}
var noColor = (string) => string, DIFF_CONTEXT_DEFAULT = 5, DIFF_TRUNCATE_THRESHOLD_DEFAULT = 0;
function getDefaultOptions() {
  return {
    aAnnotation: "Expected",
    aColor: chunk_QGGIYUMM_s.green,
    aIndicator: "-",
    bAnnotation: "Received",
    bColor: chunk_QGGIYUMM_s.red,
    bIndicator: "+",
    changeColor: chunk_QGGIYUMM_s.inverse,
    changeLineTrailingSpaceColor: noColor,
    commonColor: chunk_QGGIYUMM_s.dim,
    commonIndicator: " ",
    commonLineTrailingSpaceColor: noColor,
    compareKeys: void 0,
    contextLines: DIFF_CONTEXT_DEFAULT,
    emptyFirstOrLastLinePlaceholder: "",
    expand: !1,
    includeChangeCounts: !1,
    omitAnnotationLines: !1,
    patchColor: chunk_QGGIYUMM_s.yellow,
    printBasicPrototype: !1,
    truncateThreshold: DIFF_TRUNCATE_THRESHOLD_DEFAULT,
    truncateAnnotation: "... Diff result is truncated",
    truncateAnnotationColor: noColor
  };
}
function getCompareKeys(compareKeys) {
  return compareKeys && typeof compareKeys == "function" ? compareKeys : void 0;
}
function getContextLines(contextLines) {
  return typeof contextLines == "number" && Number.isSafeInteger(contextLines) && contextLines >= 0 ? contextLines : DIFF_CONTEXT_DEFAULT;
}
function normalizeDiffOptions(options = {}) {
  return {
    ...getDefaultOptions(),
    ...options,
    compareKeys: getCompareKeys(options.compareKeys),
    contextLines: getContextLines(options.contextLines)
  };
}
function isEmptyString(lines) {
  return lines.length === 1 && lines[0].length === 0;
}
function countChanges(diffs) {
  let a2 = 0, b = 0;
  return diffs.forEach((diff2) => {
    switch (diff2[0]) {
      case DIFF_DELETE:
        a2 += 1;
        break;
      case DIFF_INSERT:
        b += 1;
        break;
    }
  }), {
    a: a2,
    b
  };
}
function printAnnotation({ aAnnotation, aColor, aIndicator, bAnnotation, bColor, bIndicator, includeChangeCounts, omitAnnotationLines }, changeCounts) {
  if (omitAnnotationLines)
    return "";
  let aRest = "", bRest = "";
  if (includeChangeCounts) {
    let aCount = String(changeCounts.a), bCount = String(changeCounts.b), baAnnotationLengthDiff = bAnnotation.length - aAnnotation.length, aAnnotationPadding = " ".repeat(Math.max(0, baAnnotationLengthDiff)), bAnnotationPadding = " ".repeat(Math.max(0, -baAnnotationLengthDiff)), baCountLengthDiff = bCount.length - aCount.length, aCountPadding = " ".repeat(Math.max(0, baCountLengthDiff)), bCountPadding = " ".repeat(Math.max(0, -baCountLengthDiff));
    aRest = `${aAnnotationPadding}  ${aIndicator} ${aCountPadding}${aCount}`, bRest = `${bAnnotationPadding}  ${bIndicator} ${bCountPadding}${bCount}`;
  }
  let a2 = `${aIndicator} ${aAnnotation}${aRest}`, b = `${bIndicator} ${bAnnotation}${bRest}`;
  return `${aColor(a2)}
${bColor(b)}

`;
}
function printDiffLines(diffs, truncated, options) {
  return printAnnotation(options, countChanges(diffs)) + (options.expand ? joinAlignedDiffsExpand(diffs, options) : joinAlignedDiffsNoExpand(diffs, options)) + (truncated ? options.truncateAnnotationColor(`
${options.truncateAnnotation}`) : "");
}
function diffLinesUnified(aLines, bLines, options) {
  let normalizedOptions = normalizeDiffOptions(options), [diffs, truncated] = diffLinesRaw(isEmptyString(aLines) ? [] : aLines, isEmptyString(bLines) ? [] : bLines, normalizedOptions);
  return printDiffLines(diffs, truncated, normalizedOptions);
}
function diffLinesUnified2(aLinesDisplay, bLinesDisplay, aLinesCompare, bLinesCompare, options) {
  if (isEmptyString(aLinesDisplay) && isEmptyString(aLinesCompare) && (aLinesDisplay = [], aLinesCompare = []), isEmptyString(bLinesDisplay) && isEmptyString(bLinesCompare) && (bLinesDisplay = [], bLinesCompare = []), aLinesDisplay.length !== aLinesCompare.length || bLinesDisplay.length !== bLinesCompare.length)
    return diffLinesUnified(aLinesDisplay, bLinesDisplay, options);
  let [diffs, truncated] = diffLinesRaw(aLinesCompare, bLinesCompare, options), aIndex = 0, bIndex = 0;
  return diffs.forEach((diff2) => {
    switch (diff2[0]) {
      case DIFF_DELETE:
        diff2[1] = aLinesDisplay[aIndex], aIndex += 1;
        break;
      case DIFF_INSERT:
        diff2[1] = bLinesDisplay[bIndex], bIndex += 1;
        break;
      default:
        diff2[1] = bLinesDisplay[bIndex], aIndex += 1, bIndex += 1;
    }
  }), printDiffLines(diffs, truncated, normalizeDiffOptions(options));
}
function diffLinesRaw(aLines, bLines, options) {
  let truncate2 = options?.truncateThreshold ?? !1, truncateThreshold = Math.max(Math.floor(options?.truncateThreshold ?? 0), 0), aLength = truncate2 ? Math.min(aLines.length, truncateThreshold) : aLines.length, bLength = truncate2 ? Math.min(bLines.length, truncateThreshold) : bLines.length, truncated = aLength !== aLines.length || bLength !== bLines.length, isCommon = (aIndex2, bIndex2) => aLines[aIndex2] === bLines[bIndex2], diffs = [], aIndex = 0, bIndex = 0;
  for (diffSequences(aLength, bLength, isCommon, (nCommon, aCommon, bCommon) => {
    for (; aIndex !== aCommon; aIndex += 1)
      diffs.push(new Diff(DIFF_DELETE, aLines[aIndex]));
    for (; bIndex !== bCommon; bIndex += 1)
      diffs.push(new Diff(DIFF_INSERT, bLines[bIndex]));
    for (; nCommon !== 0; nCommon -= 1, aIndex += 1, bIndex += 1)
      diffs.push(new Diff(DIFF_EQUAL, bLines[bIndex]));
  }); aIndex !== aLength; aIndex += 1)
    diffs.push(new Diff(DIFF_DELETE, aLines[aIndex]));
  for (; bIndex !== bLength; bIndex += 1)
    diffs.push(new Diff(DIFF_INSERT, bLines[bIndex]));
  return [diffs, truncated];
}
function getType3(value) {
  if (value === void 0)
    return "undefined";
  if (value === null)
    return "null";
  if (Array.isArray(value))
    return "array";
  if (typeof value == "boolean")
    return "boolean";
  if (typeof value == "function")
    return "function";
  if (typeof value == "number")
    return "number";
  if (typeof value == "string")
    return "string";
  if (typeof value == "bigint")
    return "bigint";
  if (typeof value == "object") {
    if (value != null) {
      if (value.constructor === RegExp)
        return "regexp";
      if (value.constructor === Map)
        return "map";
      if (value.constructor === Set)
        return "set";
      if (value.constructor === Date)
        return "date";
    }
    return "object";
  } else if (typeof value == "symbol")
    return "symbol";
  throw new Error(`value of unknown type: ${value}`);
}
function getNewLineSymbol(string) {
  return string.includes(`\r
`) ? `\r
` : `
`;
}
function diffStrings(a2, b, options) {
  let truncate2 = options?.truncateThreshold ?? !1, truncateThreshold = Math.max(Math.floor(options?.truncateThreshold ?? 0), 0), aLength = a2.length, bLength = b.length;
  if (truncate2) {
    let aMultipleLines = a2.includes(`
`), bMultipleLines = b.includes(`
`), aNewLineSymbol = getNewLineSymbol(a2), bNewLineSymbol = getNewLineSymbol(b), _a = aMultipleLines ? `${a2.split(aNewLineSymbol, truncateThreshold).join(aNewLineSymbol)}
` : a2, _b = bMultipleLines ? `${b.split(bNewLineSymbol, truncateThreshold).join(bNewLineSymbol)}
` : b;
    aLength = _a.length, bLength = _b.length;
  }
  let truncated = aLength !== a2.length || bLength !== b.length, isCommon = (aIndex2, bIndex2) => a2[aIndex2] === b[bIndex2], aIndex = 0, bIndex = 0, diffs = [];
  return diffSequences(aLength, bLength, isCommon, (nCommon, aCommon, bCommon) => {
    aIndex !== aCommon && diffs.push(new Diff(DIFF_DELETE, a2.slice(aIndex, aCommon))), bIndex !== bCommon && diffs.push(new Diff(DIFF_INSERT, b.slice(bIndex, bCommon))), aIndex = aCommon + nCommon, bIndex = bCommon + nCommon, diffs.push(new Diff(DIFF_EQUAL, b.slice(bCommon, bIndex)));
  }), aIndex !== aLength && diffs.push(new Diff(DIFF_DELETE, a2.slice(aIndex))), bIndex !== bLength && diffs.push(new Diff(DIFF_INSERT, b.slice(bIndex))), [diffs, truncated];
}
function concatenateRelevantDiffs(op, diffs, changeColor) {
  return diffs.reduce((reduced, diff2) => reduced + (diff2[0] === DIFF_EQUAL ? diff2[1] : diff2[0] === op && diff2[1].length !== 0 ? changeColor(diff2[1]) : ""), "");
}
var ChangeBuffer = class {
  op;
  line;
  lines;
  changeColor;
  constructor(op, changeColor) {
    this.op = op, this.line = [], this.lines = [], this.changeColor = changeColor;
  }
  pushSubstring(substring) {
    this.pushDiff(new Diff(this.op, substring));
  }
  pushLine() {
    this.lines.push(this.line.length !== 1 ? new Diff(this.op, concatenateRelevantDiffs(this.op, this.line, this.changeColor)) : this.line[0][0] === this.op ? this.line[0] : new Diff(this.op, this.line[0][1])), this.line.length = 0;
  }
  isLineEmpty() {
    return this.line.length === 0;
  }
  // Minor input to buffer.
  pushDiff(diff2) {
    this.line.push(diff2);
  }
  // Main input to buffer.
  align(diff2) {
    let string = diff2[1];
    if (string.includes(`
`)) {
      let substrings = string.split(`
`), iLast = substrings.length - 1;
      substrings.forEach((substring, i) => {
        i < iLast ? (this.pushSubstring(substring), this.pushLine()) : substring.length !== 0 && this.pushSubstring(substring);
      });
    } else
      this.pushDiff(diff2);
  }
  // Output from buffer.
  moveLinesTo(lines) {
    this.isLineEmpty() || this.pushLine(), lines.push(...this.lines), this.lines.length = 0;
  }
}, CommonBuffer = class {
  deleteBuffer;
  insertBuffer;
  lines;
  constructor(deleteBuffer, insertBuffer) {
    this.deleteBuffer = deleteBuffer, this.insertBuffer = insertBuffer, this.lines = [];
  }
  pushDiffCommonLine(diff2) {
    this.lines.push(diff2);
  }
  pushDiffChangeLines(diff2) {
    let isDiffEmpty = diff2[1].length === 0;
    (!isDiffEmpty || this.deleteBuffer.isLineEmpty()) && this.deleteBuffer.pushDiff(diff2), (!isDiffEmpty || this.insertBuffer.isLineEmpty()) && this.insertBuffer.pushDiff(diff2);
  }
  flushChangeLines() {
    this.deleteBuffer.moveLinesTo(this.lines), this.insertBuffer.moveLinesTo(this.lines);
  }
  // Input to buffer.
  align(diff2) {
    let op = diff2[0], string = diff2[1];
    if (string.includes(`
`)) {
      let substrings = string.split(`
`), iLast = substrings.length - 1;
      substrings.forEach((substring, i) => {
        if (i === 0) {
          let subdiff = new Diff(op, substring);
          this.deleteBuffer.isLineEmpty() && this.insertBuffer.isLineEmpty() ? (this.flushChangeLines(), this.pushDiffCommonLine(subdiff)) : (this.pushDiffChangeLines(subdiff), this.flushChangeLines());
        } else i < iLast ? this.pushDiffCommonLine(new Diff(op, substring)) : substring.length !== 0 && this.pushDiffChangeLines(new Diff(op, substring));
      });
    } else
      this.pushDiffChangeLines(diff2);
  }
  // Output from buffer.
  getLines() {
    return this.flushChangeLines(), this.lines;
  }
};
function getAlignedDiffs(diffs, changeColor) {
  let deleteBuffer = new ChangeBuffer(DIFF_DELETE, changeColor), insertBuffer = new ChangeBuffer(DIFF_INSERT, changeColor), commonBuffer = new CommonBuffer(deleteBuffer, insertBuffer);
  return diffs.forEach((diff2) => {
    switch (diff2[0]) {
      case DIFF_DELETE:
        deleteBuffer.align(diff2);
        break;
      case DIFF_INSERT:
        insertBuffer.align(diff2);
        break;
      default:
        commonBuffer.align(diff2);
    }
  }), commonBuffer.getLines();
}
function hasCommonDiff(diffs, isMultiline) {
  if (isMultiline) {
    let iLast = diffs.length - 1;
    return diffs.some((diff2, i) => diff2[0] === DIFF_EQUAL && (i !== iLast || diff2[1] !== `
`));
  }
  return diffs.some((diff2) => diff2[0] === DIFF_EQUAL);
}
function diffStringsUnified(a2, b, options) {
  if (a2 !== b && a2.length !== 0 && b.length !== 0) {
    let isMultiline = a2.includes(`
`) || b.includes(`
`), [diffs, truncated] = diffStringsRaw(isMultiline ? `${a2}
` : a2, isMultiline ? `${b}
` : b, !0, options);
    if (hasCommonDiff(diffs, isMultiline)) {
      let optionsNormalized = normalizeDiffOptions(options), lines = getAlignedDiffs(diffs, optionsNormalized.changeColor);
      return printDiffLines(lines, truncated, optionsNormalized);
    }
  }
  return diffLinesUnified(a2.split(`
`), b.split(`
`), options);
}
function diffStringsRaw(a2, b, cleanup, options) {
  let [diffs, truncated] = diffStrings(a2, b, options);
  return cleanup && diff_cleanupSemantic(diffs), [diffs, truncated];
}
function getCommonMessage(message, options) {
  let { commonColor } = normalizeDiffOptions(options);
  return commonColor(message);
}
var { AsymmetricMatcher: AsymmetricMatcher2, DOMCollection: DOMCollection2, DOMElement: DOMElement2, Immutable: Immutable2, ReactElement: ReactElement2, ReactTestComponent: ReactTestComponent2 } = plugins, PLUGINS2 = [
  ReactTestComponent2,
  ReactElement2,
  DOMElement2,
  DOMCollection2,
  Immutable2,
  AsymmetricMatcher2,
  plugins.Error
], FORMAT_OPTIONS = {
  maxDepth: 20,
  plugins: PLUGINS2
}, FALLBACK_FORMAT_OPTIONS = {
  callToJSON: !1,
  maxDepth: 8,
  plugins: PLUGINS2
};
function diff(a2, b, options) {
  if (Object.is(a2, b))
    return "";
  let aType = getType3(a2), expectedType = aType, omitDifference = !1;
  if (aType === "object" && typeof a2.asymmetricMatch == "function") {
    if (a2.$$typeof !== /* @__PURE__ */ Symbol.for("jest.asymmetricMatcher") || typeof a2.getExpectedType != "function")
      return;
    expectedType = a2.getExpectedType(), omitDifference = expectedType === "string";
  }
  if (expectedType !== getType3(b)) {
    let truncate2 = function(s2) {
      return s2.length <= MAX_LENGTH ? s2 : `${s2.slice(0, MAX_LENGTH)}...`;
    }, { aAnnotation, aColor, aIndicator, bAnnotation, bColor, bIndicator } = normalizeDiffOptions(options), formatOptions = getFormatOptions(FALLBACK_FORMAT_OPTIONS, options), aDisplay = format(a2, formatOptions), bDisplay = format(b, formatOptions), MAX_LENGTH = 1e5;
    aDisplay = truncate2(aDisplay), bDisplay = truncate2(bDisplay);
    let aDiff = `${aColor(`${aIndicator} ${aAnnotation}:`)} 
${aDisplay}`, bDiff = `${bColor(`${bIndicator} ${bAnnotation}:`)} 
${bDisplay}`;
    return `${aDiff}

${bDiff}`;
  }
  if (!omitDifference)
    switch (aType) {
      case "string":
        return diffLinesUnified(a2.split(`
`), b.split(`
`), options);
      case "boolean":
      case "number":
        return comparePrimitive(a2, b, options);
      case "map":
        return compareObjects(sortMap(a2), sortMap(b), options);
      case "set":
        return compareObjects(sortSet(a2), sortSet(b), options);
      default:
        return compareObjects(a2, b, options);
    }
}
function comparePrimitive(a2, b, options) {
  let aFormat = format(a2, FORMAT_OPTIONS), bFormat = format(b, FORMAT_OPTIONS);
  return aFormat === bFormat ? "" : diffLinesUnified(aFormat.split(`
`), bFormat.split(`
`), options);
}
function sortMap(map) {
  return new Map(Array.from(map.entries()).sort());
}
function sortSet(set) {
  return new Set(Array.from(set.values()).sort());
}
function compareObjects(a2, b, options) {
  let difference, hasThrown = !1;
  try {
    let formatOptions = getFormatOptions(FORMAT_OPTIONS, options);
    difference = getObjectsDifference(a2, b, formatOptions, options);
  } catch {
    hasThrown = !0;
  }
  let noDiffMessage = getCommonMessage(NO_DIFF_MESSAGE, options);
  if (difference === void 0 || difference === noDiffMessage) {
    let formatOptions = getFormatOptions(FALLBACK_FORMAT_OPTIONS, options);
    difference = getObjectsDifference(a2, b, formatOptions, options), difference !== noDiffMessage && !hasThrown && (difference = `${getCommonMessage(SIMILAR_MESSAGE, options)}

${difference}`);
  }
  return difference;
}
function getFormatOptions(formatOptions, options) {
  let { compareKeys, printBasicPrototype, maxDepth } = normalizeDiffOptions(options);
  return {
    ...formatOptions,
    compareKeys,
    printBasicPrototype,
    maxDepth: maxDepth ?? formatOptions.maxDepth
  };
}
function getObjectsDifference(a2, b, formatOptions, options) {
  let formatOptionsZeroIndent = {
    ...formatOptions,
    indent: 0
  }, aCompare = format(a2, formatOptionsZeroIndent), bCompare = format(b, formatOptionsZeroIndent);
  if (aCompare === bCompare)
    return getCommonMessage(NO_DIFF_MESSAGE, options);
  {
    let aDisplay = format(a2, formatOptions), bDisplay = format(b, formatOptions);
    return diffLinesUnified2(aDisplay.split(`
`), bDisplay.split(`
`), aCompare.split(`
`), bCompare.split(`
`), options);
  }
}
var MAX_DIFF_STRING_LENGTH = 2e4;
function isAsymmetricMatcher(data) {
  return getType2(data) === "Object" && typeof data.asymmetricMatch == "function";
}
function isReplaceable(obj1, obj2) {
  let obj1Type = getType2(obj1), obj2Type = getType2(obj2);
  return obj1Type === obj2Type && (obj1Type === "Object" || obj1Type === "Array");
}
function printDiffOrStringify(received, expected, options) {
  let { aAnnotation, bAnnotation } = normalizeDiffOptions(options);
  if (typeof expected == "string" && typeof received == "string" && expected.length > 0 && received.length > 0 && expected.length <= MAX_DIFF_STRING_LENGTH && received.length <= MAX_DIFF_STRING_LENGTH && expected !== received) {
    if (expected.includes(`
`) || received.includes(`
`))
      return diffStringsUnified(expected, received, options);
    let [diffs] = diffStringsRaw(expected, received, !0), hasCommonDiff2 = diffs.some((diff2) => diff2[0] === DIFF_EQUAL), printLabel = getLabelPrinter(aAnnotation, bAnnotation), expectedLine = printLabel(aAnnotation) + printExpected(getCommonAndChangedSubstrings(diffs, DIFF_DELETE, hasCommonDiff2)), receivedLine = printLabel(bAnnotation) + printReceived(getCommonAndChangedSubstrings(diffs, DIFF_INSERT, hasCommonDiff2));
    return `${expectedLine}
${receivedLine}`;
  }
  let clonedExpected = deepClone(expected, { forceWritable: !0 }), clonedReceived = deepClone(received, { forceWritable: !0 }), { replacedExpected, replacedActual } = replaceAsymmetricMatcher(clonedReceived, clonedExpected);
  return diff(replacedExpected, replacedActual, options);
}
function replaceAsymmetricMatcher(actual, expected, actualReplaced = /* @__PURE__ */ new WeakSet(), expectedReplaced = /* @__PURE__ */ new WeakSet()) {
  return actual instanceof Error && expected instanceof Error && typeof actual.cause < "u" && typeof expected.cause > "u" ? (delete actual.cause, {
    replacedActual: actual,
    replacedExpected: expected
  }) : isReplaceable(actual, expected) ? actualReplaced.has(actual) || expectedReplaced.has(expected) ? {
    replacedActual: actual,
    replacedExpected: expected
  } : (actualReplaced.add(actual), expectedReplaced.add(expected), getOwnProperties(expected).forEach((key) => {
    let expectedValue = expected[key], actualValue = actual[key];
    if (isAsymmetricMatcher(expectedValue))
      expectedValue.asymmetricMatch(actualValue) && (actual[key] = expectedValue);
    else if (isAsymmetricMatcher(actualValue))
      actualValue.asymmetricMatch(expectedValue) && (expected[key] = actualValue);
    else if (isReplaceable(actualValue, expectedValue)) {
      let replaced = replaceAsymmetricMatcher(actualValue, expectedValue, actualReplaced, expectedReplaced);
      actual[key] = replaced.replacedActual, expected[key] = replaced.replacedExpected;
    }
  }), {
    replacedActual: actual,
    replacedExpected: expected
  }) : {
    replacedActual: actual,
    replacedExpected: expected
  };
}
function getLabelPrinter(...strings) {
  let maxLength = strings.reduce((max, string) => string.length > max ? string.length : max, 0);
  return (string) => `${string}: ${" ".repeat(maxLength - string.length)}`;
}
var SPACE_SYMBOL = "\xB7";
function replaceTrailingSpaces(text) {
  return text.replace(/\s+$/gm, (spaces) => SPACE_SYMBOL.repeat(spaces.length));
}
function printReceived(object) {
  return chunk_QGGIYUMM_s.red(replaceTrailingSpaces(stringify(object)));
}
function printExpected(value) {
  return chunk_QGGIYUMM_s.green(replaceTrailingSpaces(stringify(value)));
}
function getCommonAndChangedSubstrings(diffs, op, hasCommonDiff2) {
  return diffs.reduce((reduced, diff2) => reduced + (diff2[0] === DIFF_EQUAL ? diff2[1] : diff2[0] === op ? hasCommonDiff2 ? chunk_QGGIYUMM_s.inverse(diff2[1]) : diff2[1] : ""), "");
}

// ../../node_modules/@vitest/utils/dist/error.js
var IS_RECORD_SYMBOL = "@@__IMMUTABLE_RECORD__@@", IS_COLLECTION_SYMBOL = "@@__IMMUTABLE_ITERABLE__@@";
function isImmutable(v) {
  return v && (v[IS_COLLECTION_SYMBOL] || v[IS_RECORD_SYMBOL]);
}
var OBJECT_PROTO = Object.getPrototypeOf({});
function getUnserializableMessage(err) {
  return err instanceof Error ? `<unserializable>: ${err.message}` : typeof err == "string" ? `<unserializable>: ${err}` : "<unserializable>";
}
function serializeValue(val, seen = /* @__PURE__ */ new WeakMap()) {
  if (!val || typeof val == "string")
    return val;
  if (val instanceof Error && "toJSON" in val && typeof val.toJSON == "function") {
    let jsonValue = val.toJSON();
    return jsonValue && jsonValue !== val && typeof jsonValue == "object" && (typeof val.message == "string" && safe(() => jsonValue.message ?? (jsonValue.message = val.message)), typeof val.stack == "string" && safe(() => jsonValue.stack ?? (jsonValue.stack = val.stack)), typeof val.name == "string" && safe(() => jsonValue.name ?? (jsonValue.name = val.name)), val.cause != null && safe(() => jsonValue.cause ?? (jsonValue.cause = serializeValue(val.cause, seen)))), serializeValue(jsonValue, seen);
  }
  if (typeof val == "function")
    return `Function<${val.name || "anonymous"}>`;
  if (typeof val == "symbol")
    return val.toString();
  if (typeof val != "object")
    return val;
  if (typeof Buffer < "u" && val instanceof Buffer)
    return `<Buffer(${val.length}) ...>`;
  if (typeof Uint8Array < "u" && val instanceof Uint8Array)
    return `<Uint8Array(${val.length}) ...>`;
  if (isImmutable(val))
    return serializeValue(val.toJSON(), seen);
  if (val instanceof Promise || val.constructor && val.constructor.prototype === "AsyncFunction")
    return "Promise";
  if (typeof Element < "u" && val instanceof Element)
    return val.tagName;
  if (typeof val.asymmetricMatch == "function")
    return `${val.toString()} ${format2(val.sample)}`;
  if (typeof val.toJSON == "function")
    return serializeValue(val.toJSON(), seen);
  if (seen.has(val))
    return seen.get(val);
  if (Array.isArray(val)) {
    let clone2 = new Array(val.length);
    return seen.set(val, clone2), val.forEach((e, i) => {
      try {
        clone2[i] = serializeValue(e, seen);
      } catch (err) {
        clone2[i] = getUnserializableMessage(err);
      }
    }), clone2;
  } else {
    let clone2 = /* @__PURE__ */ Object.create(null);
    seen.set(val, clone2);
    let obj = val;
    for (; obj && obj !== OBJECT_PROTO; )
      Object.getOwnPropertyNames(obj).forEach((key) => {
        if (!(key in clone2))
          try {
            clone2[key] = serializeValue(val[key], seen);
          } catch (err) {
            delete clone2[key], clone2[key] = getUnserializableMessage(err);
          }
      }), obj = Object.getPrototypeOf(obj);
    return clone2;
  }
}
function safe(fn) {
  try {
    return fn();
  } catch {
  }
}
function normalizeErrorMessage(message) {
  return message.replace(/__(vite_ssr_import|vi_import)_\d+__\./g, "");
}
function processError(_err, diffOptions, seen = /* @__PURE__ */ new WeakSet()) {
  if (!_err || typeof _err != "object")
    return { message: String(_err) };
  let err = _err;
  (err.showDiff || err.showDiff === void 0 && err.expected !== void 0 && err.actual !== void 0) && (err.diff = printDiffOrStringify(err.actual, err.expected, {
    ...diffOptions,
    ...err.diffOptions
  })), "expected" in err && typeof err.expected != "string" && (err.expected = stringify(err.expected, 10)), "actual" in err && typeof err.actual != "string" && (err.actual = stringify(err.actual, 10));
  try {
    typeof err.message == "string" && (err.message = normalizeErrorMessage(err.message));
  } catch {
  }
  try {
    !seen.has(err) && typeof err.cause == "object" && (seen.add(err), err.cause = processError(err.cause, diffOptions, seen));
  } catch {
  }
  try {
    return serializeValue(err);
  } catch (e) {
    return serializeValue(new Error(`Failed to fully serialize error: ${e?.message}
Inner error message: ${err?.message}`));
  }
}



// EXTERNAL MODULE: external "__STORYBOOK_MODULE_CLIENT_LOGGER__"
var external_STORYBOOK_MODULE_CLIENT_LOGGER_ = __webpack_require__("storybook/internal/client-logger");
// EXTERNAL MODULE: external "__STORYBOOK_MODULE_CORE_EVENTS__"
var external_STORYBOOK_MODULE_CORE_EVENTS_ = __webpack_require__("storybook/internal/core-events");
;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/instrumenter/index.js




// src/instrumenter/instrumenter.ts




// src/instrumenter/preview-api.ts
var addons = globalThis.__STORYBOOK_ADDONS_PREVIEW;

// src/instrumenter/instrumenter.ts
var alreadyCompletedException = new Error(
  "This function ran after the play function completed. Did you forget to `await` it?"
), instrumenter_isObject = (o) => Object.prototype.toString.call(o) === "[object Object]", isModule = (o) => Object.prototype.toString.call(o) === "[object Module]", isInstrumentable = (o) => {
  if (!instrumenter_isObject(o) && !isModule(o))
    return !1;
  if (o.constructor === void 0)
    return !0;
  let proto = o.constructor.prototype;
  return !!instrumenter_isObject(proto);
}, construct = (obj) => {
  try {
    return new obj.constructor();
  } catch {
    return {};
  }
}, getInitialState = () => ({
  renderPhase: "preparing",
  isDebugging: !1,
  isPlaying: !1,
  isLocked: !1,
  cursor: 0,
  calls: [],
  shadowCalls: [],
  callRefsByResult: /* @__PURE__ */ new Map(),
  chainedCallIds: /* @__PURE__ */ new Set(),
  ancestors: [],
  playUntil: void 0,
  resolvers: {},
  syncTimeout: void 0
}), getRetainedState = (state, isDebugging = !1) => {
  let calls = (isDebugging ? state.shadowCalls : state.calls).filter((call) => call.retain);
  if (!calls.length)
    return;
  let callRefsByResult = new Map(
    Array.from(state.callRefsByResult.entries()).filter(([, ref]) => ref.retain)
  );
  return { cursor: calls.length, calls, callRefsByResult };
}, Instrumenter = class {
  constructor() {
    this.detached = !1;
    this.initialized = !1;
    // State is tracked per story to deal with multiple stories on the same canvas (i.e. docs mode)
    this.state = {};
    this.loadParentWindowState = () => {
      try {
        this.state = external_STORYBOOK_MODULE_GLOBAL_.global.window?.parent?.__STORYBOOK_ADDON_INTERACTIONS_INSTRUMENTER_STATE__ || {};
      } catch {
        this.detached = !0;
      }
    };
    this.updateParentWindowState = () => {
      try {
        external_STORYBOOK_MODULE_GLOBAL_.global.window.parent.__STORYBOOK_ADDON_INTERACTIONS_INSTRUMENTER_STATE__ = this.state;
      } catch {
        this.detached = !0;
      }
    };
    this.loadParentWindowState();
    let resetState = ({
      storyId,
      renderPhase,
      isPlaying = !0,
      isDebugging = !1
    }) => {
      let state = this.getState(storyId);
      this.setState(storyId, {
        ...getInitialState(),
        ...getRetainedState(state, isDebugging),
        renderPhase: renderPhase || state.renderPhase,
        shadowCalls: isDebugging ? state.shadowCalls : [],
        chainedCallIds: isDebugging ? state.chainedCallIds : /* @__PURE__ */ new Set(),
        playUntil: isDebugging ? state.playUntil : void 0,
        isPlaying,
        isDebugging
      }), this.sync(storyId);
    }, start = (channel) => ({ storyId, playUntil }) => {
      this.getState(storyId).isDebugging || this.setState(storyId, ({ calls }) => ({
        calls: [],
        shadowCalls: calls.map((call) => ({ ...call, status: "waiting" /* WAITING */ })),
        isDebugging: !0
      }));
      let log = this.getLog(storyId);
      this.setState(storyId, ({ shadowCalls }) => {
        if (playUntil || !log.length)
          return { playUntil };
        let firstRowIndex = shadowCalls.findIndex((call) => call.id === log[0].callId);
        return {
          playUntil: shadowCalls.slice(0, firstRowIndex).filter((call) => call.interceptable && !call.ancestors?.length).slice(-1)[0]?.id
        };
      }), channel.emit(external_STORYBOOK_MODULE_CORE_EVENTS_.FORCE_REMOUNT, { storyId, isDebugging: !0 });
    }, back = (channel) => ({ storyId }) => {
      let log = this.getLog(storyId).filter((call) => !call.ancestors?.length), last = log.reduceRight((res, item, index) => res >= 0 || item.status === "waiting" /* WAITING */ ? res : index, -1);
      start(channel)({ storyId, playUntil: log[last - 1]?.callId });
    }, goto = (channel) => ({ storyId, callId }) => {
      let { calls, shadowCalls, resolvers } = this.getState(storyId), call = calls.find(({ id }) => id === callId), shadowCall = shadowCalls.find(({ id }) => id === callId);
      if (!call && shadowCall && Object.values(resolvers).length > 0) {
        let nextId = this.getLog(storyId).find((c) => c.status === "waiting" /* WAITING */)?.callId;
        shadowCall.id !== nextId && this.setState(storyId, { playUntil: shadowCall.id }), Object.values(resolvers).forEach((resolve) => resolve());
      } else
        start(channel)({ storyId, playUntil: callId });
    }, next = (channel) => ({ storyId }) => {
      let { resolvers } = this.getState(storyId);
      if (Object.values(resolvers).length > 0)
        Object.values(resolvers).forEach((resolve) => resolve());
      else {
        let nextId = this.getLog(storyId).find((c) => c.status === "waiting" /* WAITING */)?.callId;
        nextId ? start(channel)({ storyId, playUntil: nextId }) : end({ storyId });
      }
    }, end = ({ storyId }) => {
      this.setState(storyId, { playUntil: void 0, isDebugging: !1 }), Object.values(this.getState(storyId).resolvers).forEach((resolve) => resolve());
    }, renderPhaseChanged = ({
      storyId,
      newPhase
    }) => {
      let { isDebugging } = this.getState(storyId);
      if (newPhase === "preparing" && isDebugging)
        return resetState({ storyId, renderPhase: newPhase, isDebugging });
      if (newPhase === "playing")
        return resetState({ storyId, renderPhase: newPhase, isDebugging });
      newPhase === "played" ? this.setState(storyId, {
        renderPhase: newPhase,
        isLocked: !1,
        isPlaying: !1,
        isDebugging: !1
      }) : newPhase === "errored" ? this.setState(storyId, {
        renderPhase: newPhase,
        isLocked: !1,
        isPlaying: !1
      }) : newPhase === "aborted" ? this.setState(storyId, {
        renderPhase: newPhase,
        isLocked: !0,
        isPlaying: !1
      }) : this.setState(storyId, {
        renderPhase: newPhase
      }), this.sync(storyId);
    };
    addons && addons.ready().then(() => {
      this.channel = addons.getChannel(), this.channel.on(external_STORYBOOK_MODULE_CORE_EVENTS_.FORCE_REMOUNT, resetState), this.channel.on(external_STORYBOOK_MODULE_CORE_EVENTS_.STORY_RENDER_PHASE_CHANGED, renderPhaseChanged), this.channel.on(external_STORYBOOK_MODULE_CORE_EVENTS_.SET_CURRENT_STORY, () => {
        this.initialized ? this.cleanup() : this.initialized = !0;
      }), this.channel.on(chunk_ZUWEVLDX_EVENTS.START, start(this.channel)), this.channel.on(chunk_ZUWEVLDX_EVENTS.BACK, back(this.channel)), this.channel.on(chunk_ZUWEVLDX_EVENTS.GOTO, goto(this.channel)), this.channel.on(chunk_ZUWEVLDX_EVENTS.NEXT, next(this.channel)), this.channel.on(chunk_ZUWEVLDX_EVENTS.END, end);
    });
  }
  getState(storyId) {
    return this.state[storyId] || getInitialState();
  }
  setState(storyId, update) {
    if (storyId) {
      let state = this.getState(storyId), patch = typeof update == "function" ? update(state) : update;
      this.state = { ...this.state, [storyId]: { ...state, ...patch } }, this.updateParentWindowState();
    }
  }
  cleanup() {
    this.state = Object.entries(this.state).reduce(
      (acc, [storyId, state]) => {
        let retainedState = getRetainedState(state);
        return retainedState && (acc[storyId] = Object.assign(getInitialState(), retainedState)), acc;
      },
      {}
    );
    let payload = { controlStates: {
      detached: this.detached,
      start: !1,
      back: !1,
      goto: !1,
      next: !1,
      end: !1
    }, logItems: [] };
    this.channel?.emit(chunk_ZUWEVLDX_EVENTS.SYNC, payload), this.updateParentWindowState();
  }
  getLog(storyId) {
    let { calls, shadowCalls } = this.getState(storyId), merged = [...shadowCalls];
    calls.forEach((call, index) => {
      merged[index] = call;
    });
    let seen = /* @__PURE__ */ new Set();
    return merged.reduceRight((acc, call) => (call.args.forEach((arg) => {
      arg?.__callId__ && seen.add(arg.__callId__);
    }), call.path.forEach((node) => {
      node.__callId__ && seen.add(node.__callId__);
    }), (call.interceptable || call.exception) && !seen.has(call.id) && (acc.unshift({ callId: call.id, status: call.status, ancestors: call.ancestors }), seen.add(call.id)), acc), []);
  }
  // Traverses the object structure to recursively patch all function properties.
  // Returns the original object, or a new object with the same constructor,
  // depending on whether it should mutate.
  instrument(obj, options, depth = 0) {
    if (!isInstrumentable(obj))
      return obj;
    let { mutate = !1, path = [] } = options, keys = options.getKeys ? options.getKeys(obj, depth) : Object.keys(obj);
    return depth += 1, keys.reduce(
      (acc, key) => {
        let descriptor = getPropertyDescriptor(obj, key);
        if (typeof descriptor?.get == "function") {
          if (descriptor.configurable) {
            let getter = () => descriptor?.get?.bind(obj)?.();
            Object.defineProperty(acc, key, {
              get: () => this.instrument(getter(), { ...options, path: path.concat(key) }, depth)
            });
          }
          return acc;
        }
        let value = obj[key];
        return typeof value != "function" ? (acc[key] = this.instrument(value, { ...options, path: path.concat(key) }, depth), acc) : "__originalFn__" in value && typeof value.__originalFn__ == "function" ? (acc[key] = value, acc) : (acc[key] = (...args) => this.track(key, value, obj, args, options), acc[key].__originalFn__ = value, Object.defineProperty(acc[key], "name", { value: key, writable: !1 }), Object.keys(value).length > 0 && Object.assign(
          acc[key],
          this.instrument({ ...value }, { ...options, path: path.concat(key) }, depth)
        ), acc);
      },
      mutate ? obj : construct(obj)
    );
  }
  // Monkey patch an object method to record calls.
  // Returns a function that invokes the original function, records the invocation ("call") and
  // returns the original result.
  track(method, fn, object, args, options) {
    let storyId = args?.[0]?.__storyId__ || external_STORYBOOK_MODULE_GLOBAL_.global.__STORYBOOK_PREVIEW__?.selectionStore?.selection?.storyId, { cursor, ancestors } = this.getState(storyId);
    this.setState(storyId, { cursor: cursor + 1 });
    let id = `${ancestors.slice(-1)[0] || storyId} [${cursor}] ${method}`, { path = [], intercept = !1, retain = !1 } = options, interceptable = typeof intercept == "function" ? intercept(method, path) : intercept, call = { id, cursor, storyId, ancestors, path, method, args, interceptable, retain }, result = (interceptable && !ancestors.length ? this.intercept : this.invoke).call(this, fn, object, call, options);
    return this.instrument(result, { ...options, mutate: !0, path: [{ __callId__: call.id }] });
  }
  intercept(fn, object, call, options) {
    let { chainedCallIds, isDebugging, playUntil } = this.getState(call.storyId), isChainedUpon = chainedCallIds.has(call.id);
    return !isDebugging || isChainedUpon || playUntil ? (playUntil === call.id && this.setState(call.storyId, { playUntil: void 0 }), this.invoke(fn, object, call, options)) : new Promise((resolve) => {
      this.setState(call.storyId, ({ resolvers }) => ({
        isLocked: !1,
        resolvers: { ...resolvers, [call.id]: resolve }
      }));
    }).then(() => (this.setState(call.storyId, (state) => {
      let { [call.id]: _, ...resolvers } = state.resolvers;
      return { isLocked: !0, resolvers };
    }), this.invoke(fn, object, call, options)));
  }
  invoke(fn, object, call, options) {
    let { callRefsByResult, renderPhase } = this.getState(call.storyId), maximumDepth = 25, serializeValues = (value, depth, seen) => {
      if (seen.includes(value))
        return "[Circular]";
      if (seen = [...seen, value], depth > maximumDepth)
        return "...";
      if (callRefsByResult.has(value))
        return callRefsByResult.get(value);
      if (value instanceof Array)
        return value.map((it) => serializeValues(it, ++depth, seen));
      if (value instanceof Date)
        return { __date__: { value: value.toISOString() } };
      if (value instanceof Error) {
        let { name, message, stack } = value;
        return { __error__: { name, message, stack } };
      }
      if (value instanceof RegExp) {
        let { flags, source } = value;
        return { __regexp__: { flags, source } };
      }
      if (value instanceof external_STORYBOOK_MODULE_GLOBAL_.global.window?.HTMLElement) {
        let { prefix, localName, id, classList, innerText } = value, classNames = Array.from(classList);
        return { __element__: { prefix, localName, id, classNames, innerText } };
      }
      return typeof value == "function" ? {
        __function__: { name: "getMockName" in value ? value.getMockName() : value.name }
      } : typeof value == "symbol" ? { __symbol__: { description: value.description } } : typeof value == "object" && value?.constructor?.name && value?.constructor?.name !== "Object" ? { __class__: { name: value.constructor.name } } : Object.prototype.toString.call(value) === "[object Object]" ? Object.fromEntries(
        Object.entries(value).map(([key, val]) => [key, serializeValues(val, ++depth, seen)])
      ) : value;
    }, info = {
      ...call,
      args: call.args.map((arg) => serializeValues(arg, 0, []))
    };
    call.path.forEach((ref) => {
      ref?.__callId__ && this.setState(call.storyId, ({ chainedCallIds }) => ({
        chainedCallIds: new Set(Array.from(chainedCallIds).concat(ref.__callId__))
      }));
    });
    let handleException = (e) => {
      if (e instanceof Error) {
        let { name, message, stack, callId = call.id } = e, {
          showDiff = void 0,
          diff = void 0,
          actual = void 0,
          expected = void 0
        } = e.name === "AssertionError" ? processError(e) : e, exception = { name, message, stack, callId, showDiff, diff, actual, expected };
        if (this.update({ ...info, status: "error" /* ERROR */, exception }), this.setState(call.storyId, (state) => ({
          callRefsByResult: new Map([
            ...Array.from(state.callRefsByResult.entries()),
            [e, { __callId__: call.id, retain: call.retain }]
          ])
        })), call.ancestors?.length)
          throw Object.prototype.hasOwnProperty.call(e, "callId") || Object.defineProperty(e, "callId", { value: call.id }), e;
      }
      throw e;
    };
    try {
      if (renderPhase === "played" && !call.retain)
        throw alreadyCompletedException;
      let finalArgs = (options.getArgs ? options.getArgs(call, this.getState(call.storyId)) : call.args).map((arg) => typeof arg != "function" || isClass(arg) || Object.keys(arg).length ? arg : (...args) => {
        let { cursor, ancestors } = this.getState(call.storyId);
        this.setState(call.storyId, { cursor: 0, ancestors: [...ancestors, call.id] });
        let restore = () => this.setState(call.storyId, { cursor, ancestors }), willRestore = !1;
        try {
          let res = arg(...args);
          return res instanceof Promise ? (willRestore = !0, res.finally(restore)) : res;
        } finally {
          willRestore || restore();
        }
      }), result = fn.apply(object, finalArgs);
      return result && ["object", "function", "symbol"].includes(typeof result) && this.setState(call.storyId, (state) => ({
        callRefsByResult: new Map([
          ...Array.from(state.callRefsByResult.entries()),
          [result, { __callId__: call.id, retain: call.retain }]
        ])
      })), this.update({
        ...info,
        status: result instanceof Promise ? "active" /* ACTIVE */ : "done" /* DONE */
      }), result instanceof Promise ? result.then((value) => (this.update({ ...info, status: "done" /* DONE */ }), value), handleException) : result;
    } catch (e) {
      return handleException(e);
    }
  }
  // Sends the call info to the manager and synchronizes the log.
  update(call) {
    this.channel?.emit(chunk_ZUWEVLDX_EVENTS.CALL, call), this.setState(call.storyId, ({ calls }) => {
      let callsById = calls.concat(call).reduce((a, c) => Object.assign(a, { [c.id]: c }), {});
      return {
        // Calls are sorted to ensure parent calls always come before calls in their callback.
        calls: Object.values(callsById).sort(
          (a, b) => a.id.localeCompare(b.id, void 0, { numeric: !0 })
        )
      };
    }), this.sync(call.storyId);
  }
  // Builds a log of interceptable calls and control states and sends it to the manager.
  // Uses a 0ms debounce because this might get called many times in one tick.
  sync(storyId) {
    let synchronize = () => {
      let { isLocked, isPlaying } = this.getState(storyId), logItems = this.getLog(storyId), pausedAt = logItems.filter(({ ancestors }) => !ancestors.length).find((item) => item.status === "waiting" /* WAITING */)?.callId, hasActive = logItems.some((item) => item.status === "active" /* ACTIVE */);
      if (this.detached || isLocked || hasActive || logItems.length === 0) {
        let payload2 = { controlStates: {
          detached: this.detached,
          start: !1,
          back: !1,
          goto: !1,
          next: !1,
          end: !1
        }, logItems };
        this.channel?.emit(chunk_ZUWEVLDX_EVENTS.SYNC, payload2);
        return;
      }
      let hasPrevious = logItems.some(
        (item) => item.status === "done" /* DONE */ || item.status === "error" /* ERROR */
      ), payload = { controlStates: {
        detached: this.detached,
        start: hasPrevious,
        back: hasPrevious,
        goto: !0,
        next: isPlaying,
        end: isPlaying
      }, logItems, pausedAt };
      this.channel?.emit(chunk_ZUWEVLDX_EVENTS.SYNC, payload);
    };
    this.setState(storyId, ({ syncTimeout }) => (clearTimeout(syncTimeout), { syncTimeout: setTimeout(synchronize, 0) }));
  }
};
function instrument(obj, options = {}) {
  try {
    let forceInstrument = !1, skipInstrument = !1;
    return external_STORYBOOK_MODULE_GLOBAL_.global.window?.location?.search?.includes("instrument=true") ? forceInstrument = !0 : external_STORYBOOK_MODULE_GLOBAL_.global.window?.location?.search?.includes("instrument=false") && (skipInstrument = !0), external_STORYBOOK_MODULE_GLOBAL_.global.window?.parent === external_STORYBOOK_MODULE_GLOBAL_.global.window && !forceInstrument || skipInstrument ? obj : (external_STORYBOOK_MODULE_GLOBAL_.global.window && !external_STORYBOOK_MODULE_GLOBAL_.global.window.__STORYBOOK_ADDON_INTERACTIONS_INSTRUMENTER__ && (external_STORYBOOK_MODULE_GLOBAL_.global.window.__STORYBOOK_ADDON_INTERACTIONS_INSTRUMENTER__ = new Instrumenter()), (external_STORYBOOK_MODULE_GLOBAL_.global.window?.__STORYBOOK_ADDON_INTERACTIONS_INSTRUMENTER__).instrument(obj, options));
  } catch (e) {
    return external_STORYBOOK_MODULE_CLIENT_LOGGER_.once.warn(e), obj;
  }
}
function getPropertyDescriptor(obj, propName) {
  let target = obj;
  for (; target != null; ) {
    let descriptor = Object.getOwnPropertyDescriptor(target, propName);
    if (descriptor)
      return descriptor;
    target = Object.getPrototypeOf(target);
  }
}
function isClass(obj) {
  if (typeof obj != "function")
    return !1;
  let descriptor = Object.getOwnPropertyDescriptor(obj, "prototype");
  return descriptor ? !descriptor.writable : !1;
}


;// ../../node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/dist/csf/index.js






























// ../../node_modules/@ngard/tiny-isequal/index.js
var require_tiny_isequal = (0,chunk_IMSF75WX/* __commonJS */.P$)({
  "../../node_modules/@ngard/tiny-isequal/index.js"(exports) {
    Object.defineProperty(exports, "__esModule", { value: !0 }), exports.isEqual = /* @__PURE__ */ (function() {
      var e = Object.prototype.toString, r = Object.getPrototypeOf, t = Object.getOwnPropertySymbols ? function(e2) {
        return Object.keys(e2).concat(Object.getOwnPropertySymbols(e2));
      } : Object.keys;
      return function(n, a) {
        return (function n2(a2, c, u) {
          var i, s, l, o = e.call(a2), f = e.call(c);
          if (a2 === c) return !0;
          if (a2 == null || c == null) return !1;
          if (u.indexOf(a2) > -1 && u.indexOf(c) > -1) return !0;
          if (u.push(a2, c), o != f || (i = t(a2), s = t(c), i.length != s.length || i.some(function(e2) {
            return !n2(a2[e2], c[e2], u);
          }))) return !1;
          switch (o.slice(8, -1)) {
            case "Symbol":
              return a2.valueOf() == c.valueOf();
            case "Date":
            case "Number":
              return +a2 == +c || +a2 != +a2 && +c != +c;
            case "RegExp":
            case "Function":
            case "String":
            case "Boolean":
              return "" + a2 == "" + c;
            case "Set":
            case "Map":
              i = a2.entries(), s = c.entries();
              do
                if (!n2((l = i.next()).value, s.next().value, u)) return !1;
              while (!l.done);
              return !0;
            case "ArrayBuffer":
              a2 = new Uint8Array(a2), c = new Uint8Array(c);
            case "DataView":
              a2 = new Uint8Array(a2.buffer), c = new Uint8Array(c.buffer);
            case "Float32Array":
            case "Float64Array":
            case "Int8Array":
            case "Int16Array":
            case "Int32Array":
            case "Uint8Array":
            case "Uint16Array":
            case "Uint32Array":
            case "Uint8ClampedArray":
            case "Arguments":
            case "Array":
              if (a2.length != c.length) return !1;
              for (l = 0; l < a2.length; l++) if ((l in a2 || l in c) && (l in a2 != l in c || !n2(a2[l], c[l], u))) return !1;
              return !0;
            case "Object":
              return n2(r(a2), r(c), u);
            default:
              return !1;
          }
        })(n, a, []);
      };
    })();
  }
});

// src/csf/toStartCaseStr.ts
function toStartCaseStr(str) {
  return str.replace(/_/g, " ").replace(/-/g, " ").replace(/\./g, " ").replace(/([^\n])([A-Z])([a-z])/g, (str2, $1, $2, $3) => `${$1} ${$2}${$3}`).replace(/([a-z])([A-Z])/g, (str2, $1, $2) => `${$1} ${$2}`).replace(/([a-z])([0-9])/gi, (str2, $1, $2) => `${$1} ${$2}`).replace(/([0-9])([a-z])/gi, (str2, $1, $2) => `${$1} ${$2}`).replace(/(\s|^)(\w)/g, (str2, $1, $2) => `${$1}${$2.toUpperCase()}`).replace(/ +/g, " ").trim();
}

// src/csf/export-story.ts
function matches(storyKey, arrayOrRegex) {
  return Array.isArray(arrayOrRegex) ? arrayOrRegex.includes(storyKey) : storyKey.match(arrayOrRegex);
}
var storyNameFromExport = (key) => toStartCaseStr(key);
function isExportStory(key, { includeStories, excludeStories }) {
  return (
    // Babel's CommonJS interop adds __esModule; it is not a CSF story export.
    key !== "__esModule" && (!includeStories || matches(key, includeStories)) && (!excludeStories || !matches(key, excludeStories))
  );
}

// src/csf/includeConditionalArg.ts
var import_tiny_isequal = (0,chunk_IMSF75WX/* __toESM */.f1)(require_tiny_isequal(), 1), count = (vals) => vals.map((v) => typeof v < "u").filter(Boolean).length, testValue = (cond, value) => {
  let { exists, eq, neq, truthy } = cond;
  if (count([exists, eq, neq, truthy]) > 1)
    throw new Error(`Invalid conditional test ${JSON.stringify({ exists, eq, neq })}`);
  if (typeof eq < "u")
    return (0, import_tiny_isequal.isEqual)(value, eq);
  if (typeof neq < "u")
    return !(0, import_tiny_isequal.isEqual)(value, neq);
  if (typeof exists < "u") {
    let valueExists = typeof value < "u";
    return exists ? valueExists : !valueExists;
  }
  return (typeof truthy > "u" ? !0 : truthy) ? !!value : !value;
}, includeConditionalArg = (argType, args, globals) => {
  if (!argType.if)
    return !0;
  let { arg, global: global5 } = argType.if;
  if (count([arg, global5]) !== 1)
    throw new Error(`Invalid conditional value ${JSON.stringify({ arg, global: global5 })}`);
  let value = arg ? args[arg] : globals[global5];
  return testValue(argType.if, value);
};

// src/csf/csf-factories.ts


// src/actions/preview.ts


// src/actions/addArgs.ts
var addArgs_exports = {};
(0,chunk_IMSF75WX/* __export */.VA)(addArgs_exports, {
  argsEnhancers: () => argsEnhancers
});

// src/actions/addArgsHelpers.ts
var isInInitialArgs = (name, initialArgs) => typeof initialArgs[name] > "u" && !(name in initialArgs), inferActionsFromArgTypesRegex = (context) => {
  let {
    initialArgs,
    argTypes,
    id,
    parameters: { actions }
  } = context;
  if (!actions || actions.disable || !actions.argTypesRegex || !argTypes)
    return {};
  let argTypesRegex = new RegExp(actions.argTypesRegex);
  return Object.entries(argTypes).filter(
    ([name]) => !!argTypesRegex.test(name)
  ).reduce((acc, [name, argType]) => (isInInitialArgs(name, initialArgs) && (acc[name] = action(name, { implicit: !0, id })), acc), {});
}, addActionsFromArgTypes = (context) => {
  let {
    initialArgs,
    argTypes,
    parameters: { actions }
  } = context;
  return actions?.disable || !argTypes ? {} : Object.entries(argTypes).filter(([name, argType]) => !!argType.action).reduce((acc, [name, argType]) => (isInInitialArgs(name, initialArgs) && (acc[name] = action(typeof argType.action == "string" ? argType.action : name)), acc), {});
};

// src/actions/addArgs.ts
var argsEnhancers = [
  addActionsFromArgTypes,
  inferActionsFromArgTypesRegex
];

// src/actions/loaders.ts
var loaders_exports = {};
(0,chunk_IMSF75WX/* __export */.VA)(loaders_exports, {
  loaders: () => loaders
});

var subscribed = !1, logActionsWhenMockCalled = (context) => {
  let { parameters: parameters2 } = context;
  parameters2?.actions?.disable || subscribed || ((0,external_STORYBOOK_MODULE_TEST_.onMockCall)((mock, args) => {
    let name = mock.getMockName();
    name !== "spy" && name !== "vi.fn()" && (!/^next\/.*::/.test(name) || [
      "next/router::useRouter()",
      "next/navigation::useRouter()",
      "next/navigation::redirect",
      "next/link::Link",
      "next/cache::",
      "next/headers::cookies().set",
      "next/headers::cookies().delete",
      "next/headers::headers().set",
      "next/headers::headers().delete"
    ].some((prefix) => name.startsWith(prefix))) && action(name)(args);
  }), subscribed = !0);
}, loaders = [logActionsWhenMockCalled];

// src/actions/preview.ts
var preview_default = () => definePreviewAddon({
  ...addArgs_exports,
  ...loaders_exports
});

// src/backgrounds/preview.ts


// src/backgrounds/decorator.ts


// src/backgrounds/utils.ts
var { document: document2 } = globalThis, isReduceMotionEnabled = () => globalThis?.matchMedia ? !!globalThis.matchMedia("(prefers-reduced-motion: reduce)")?.matches : !1, clearStyles = (selector) => {
  (Array.isArray(selector) ? selector : [selector]).forEach(clearStyle);
}, clearStyle = (selector) => {
  if (!document2)
    return;
  let element = document2.getElementById(selector);
  element && element.parentElement && element.parentElement.removeChild(element);
}, addGridStyle = (selector, css) => {
  if (!document2)
    return;
  let existingStyle = document2.getElementById(selector);
  if (existingStyle)
    existingStyle.innerHTML !== css && (existingStyle.innerHTML = css);
  else {
    let style = document2.createElement("style");
    style.setAttribute("id", selector), style.innerHTML = css, document2.head.appendChild(style);
  }
}, addBackgroundStyle = (selector, css, storyId) => {
  if (!document2)
    return;
  let existingStyle = document2.getElementById(selector);
  if (existingStyle)
    existingStyle.innerHTML !== css && (existingStyle.innerHTML = css);
  else {
    let style = document2.createElement("style");
    style.setAttribute("id", selector), style.innerHTML = css;
    let gridStyleSelector = `addon-backgrounds-grid${storyId ? `-docs-${storyId}` : ""}`, existingGridStyle = document2.getElementById(gridStyleSelector);
    existingGridStyle ? existingGridStyle.parentElement?.insertBefore(style, existingGridStyle) : document2.head.appendChild(style);
  }
};

// src/backgrounds/decorator.ts
var defaultGrid = {
  cellSize: 100,
  cellAmount: 10,
  opacity: 0.8
}, BG_SELECTOR_BASE = "addon-backgrounds", GRID_SELECTOR_BASE = "addon-backgrounds-grid", transitionStyle = isReduceMotionEnabled() ? "" : "transition: background-color 0.3s;", withBackgroundAndGrid = (StoryFn, context) => {
  let { globals = {}, parameters: parameters2 = {}, viewMode, id } = context, {
    options = DEFAULT_BACKGROUNDS,
    disable,
    grid = defaultGrid
  } = parameters2[chunk_6YDLG6WW_PARAM_KEY] || {}, data = globals[chunk_6YDLG6WW_PARAM_KEY] || {}, backgroundName = typeof data == "string" ? data : data?.value, item = backgroundName ? options[backgroundName] : void 0, value = typeof item == "string" ? item : item?.value || "transparent", showGrid = typeof data == "string" ? !1 : data.grid || !1, shownBackground = !!item && !disable, backgroundSelector = viewMode === "docs" ? `#anchor--${id} .docs-story, #anchor--primary--${id} .docs-story` : ".sb-show-main", gridSelector = viewMode === "docs" ? `#anchor--${id} .docs-story, #anchor--primary--${id} .docs-story` : ".sb-show-main", isLayoutPadded = parameters2.layout === void 0 || parameters2.layout === "padded", defaultOffset = viewMode === "docs" ? 20 : isLayoutPadded ? 16 : 0, { cellAmount, cellSize, opacity, offsetX = defaultOffset, offsetY = defaultOffset } = grid, backgroundSelectorId = viewMode === "docs" ? `${BG_SELECTOR_BASE}-docs-${id}` : `${BG_SELECTOR_BASE}-color`, backgroundTarget = viewMode === "docs" ? id : null;
  (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => {
    let backgroundStyles = `
    ${backgroundSelector} {
      background: ${value} !important;
      ${transitionStyle}
      }`;
    if (!shownBackground) {
      clearStyles(backgroundSelectorId);
      return;
    }
    addBackgroundStyle(backgroundSelectorId, backgroundStyles, backgroundTarget);
  }, [backgroundSelector, backgroundSelectorId, backgroundTarget, shownBackground, value]);
  let gridSelectorId = viewMode === "docs" ? `${GRID_SELECTOR_BASE}-docs-${id}` : `${GRID_SELECTOR_BASE}`;
  return (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => {
    if (!showGrid) {
      clearStyles(gridSelectorId);
      return;
    }
    let gridSize = [
      `${cellSize * cellAmount}px ${cellSize * cellAmount}px`,
      `${cellSize * cellAmount}px ${cellSize * cellAmount}px`,
      `${cellSize}px ${cellSize}px`,
      `${cellSize}px ${cellSize}px`
    ].join(", "), gridStyles = `
        ${gridSelector} {
          background-size: ${gridSize} !important;
          background-position: ${offsetX}px ${offsetY}px, ${offsetX}px ${offsetY}px, ${offsetX}px ${offsetY}px, ${offsetX}px ${offsetY}px !important;
          background-blend-mode: difference !important;
          background-image: linear-gradient(rgba(130, 130, 130, ${opacity}) 1px, transparent 1px),
           linear-gradient(90deg, rgba(130, 130, 130, ${opacity}) 1px, transparent 1px),
           linear-gradient(rgba(130, 130, 130, ${opacity / 2}) 1px, transparent 1px),
           linear-gradient(90deg, rgba(130, 130, 130, ${opacity / 2}) 1px, transparent 1px) !important;
        }
      `;
    addGridStyle(gridSelectorId, gridStyles);
  }, [cellAmount, cellSize, gridSelector, gridSelectorId, showGrid, offsetX, offsetY, opacity]), StoryFn();
};

// src/backgrounds/preview.ts
var decorators = globalThis.FEATURES?.backgrounds ? [withBackgroundAndGrid] : [], parameters = {
  [chunk_6YDLG6WW_PARAM_KEY]: {
    grid: {
      cellSize: 20,
      opacity: 0.5,
      cellAmount: 5
    },
    disable: !1
  }
}, initialGlobals = {
  [chunk_6YDLG6WW_PARAM_KEY]: { value: void 0, grid: !1 }
}, preview_default2 = () => definePreviewAddon2({
  decorators,
  parameters,
  initialGlobals
});

// src/component-testing/preview.ts


var { step } = instrument(
  {
    // It seems like the label is unused, but the instrumenter has access to it
    // The context will be bounded later in StoryRender, so that the user can write just:
    // await step("label", (context) => {
    //   // labeled step
    // });
    step: async (label, play, context) => play(context)
  },
  { intercept: !0 }
), preview_default3 = () => definePreviewAddon3({
  parameters: {
    throwPlayFunctionExceptions: !1
  },
  runStep: step
});

// src/core-server/utils/ghost-stories/test-annotations.ts

var isEmptyRender = (element) => {
  let style = getComputedStyle(element), rect = element.getBoundingClientRect();
  return !(rect.width > 0 && rect.height > 0 && style.visibility !== "hidden" && Number(style.opacity) > 0 && style.display !== "none");
}, afterEach = async ({ reporting, canvasElement, globals }) => {
  try {
    if (!globals.renderAnalysis?.enabled)
      return;
    let emptyRender = isEmptyRender(canvasElement.firstElementChild ?? canvasElement);
    emptyRender && reporting.addReport({
      type: "render-analysis",
      version: 1,
      result: {
        emptyRender
      },
      status: "warning"
    });
  } catch {
  }
}, test_annotations_default = () => definePreviewAddon4({ afterEach });

// src/highlight/preview.ts



// src/highlight/useHighlights.ts


// src/highlight/icons.ts
var iconPaths = {
  chevronLeft: [
    "M9.10355 10.1464C9.29882 10.3417 9.29882 10.6583 9.10355 10.8536C8.90829 11.0488 8.59171 11.0488 8.39645 10.8536L4.89645 7.35355C4.70118 7.15829 4.70118 6.84171 4.89645 6.64645L8.39645 3.14645C8.59171 2.95118 8.90829 2.95118 9.10355 3.14645C9.29882 3.34171 9.29882 3.65829 9.10355 3.85355L5.95711 7L9.10355 10.1464Z"
  ],
  chevronRight: [
    "M4.89645 10.1464C4.70118 10.3417 4.70118 10.6583 4.89645 10.8536C5.09171 11.0488 5.40829 11.0488 5.60355 10.8536L9.10355 7.35355C9.29882 7.15829 9.29882 6.84171 9.10355 6.64645L5.60355 3.14645C5.40829 2.95118 5.09171 2.95118 4.89645 3.14645C4.70118 3.34171 4.70118 3.65829 4.89645 3.85355L8.04289 7L4.89645 10.1464Z"
  ],
  info: [
    "M7 5.5a.5.5 0 01.5.5v4a.5.5 0 01-1 0V6a.5.5 0 01.5-.5zM7 4.5A.75.75 0 107 3a.75.75 0 000 1.5z",
    "M7 14A7 7 0 107 0a7 7 0 000 14zm0-1A6 6 0 107 1a6 6 0 000 12z"
  ],
  shareAlt: [
    "M2 1.004a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1v-4.5a.5.5 0 00-1 0v4.5H2v-10h4.5a.5.5 0 000-1H2z",
    "M7.354 7.357L12 2.711v1.793a.5.5 0 001 0v-3a.5.5 0 00-.5-.5h-3a.5.5 0 100 1h1.793L6.646 6.65a.5.5 0 10.708.707z"
  ]
};

// src/highlight/utils.ts
var svgElements = "svg,path,rect,circle,line,polyline,polygon,ellipse,text".split(","), createElement = (type, props = {}, children) => {
  let element = svgElements.includes(type) ? document.createElementNS("http://www.w3.org/2000/svg", type) : document.createElement(type);
  return Object.entries(props).forEach(([key, val]) => {
    /[A-Z]/.test(key) ? (key === "onClick" && (element.addEventListener("click", val), element.addEventListener("keydown", (e) => {
      (e.key === "Enter" || e.key === " ") && (e.preventDefault(), val());
    })), key === "onMouseEnter" && element.addEventListener("mouseenter", val), key === "onMouseLeave" && element.addEventListener("mouseleave", val)) : element.setAttribute(key, val);
  }), children?.forEach((child) => {
    if (!(child == null || child === !1))
      try {
        element.appendChild(child);
      } catch {
        element.appendChild(document.createTextNode(String(child)));
      }
  }), element;
}, createIcon = (name) => iconPaths[name] && createElement(
  "svg",
  { width: "14", height: "14", viewBox: "0 0 14 14", xmlns: "http://www.w3.org/2000/svg" },
  iconPaths[name].map(
    (d) => createElement("path", {
      fill: "currentColor",
      "fill-rule": "evenodd",
      "clip-rule": "evenodd",
      d
    })
  )
), normalizeOptions = (options) => {
  if ("elements" in options) {
    let { elements, color, style } = options;
    return {
      id: void 0,
      priority: 0,
      selectors: elements,
      styles: {
        outline: `2px ${style} ${color}`,
        outlineOffset: "2px",
        boxShadow: "0 0 0 6px rgba(255,255,255,0.6)"
      },
      menu: void 0
    };
  }
  let { menu, ...rest } = options;
  return {
    id: void 0,
    priority: 0,
    styles: {
      outline: "2px dashed #029cfd"
    },
    ...rest,
    menu: Array.isArray(menu) ? menu.every(Array.isArray) ? menu : [menu] : void 0
  };
}, isFunction = (obj) => obj instanceof Function, state = /* @__PURE__ */ new Map(), listeners = /* @__PURE__ */ new Map(), teardowns = /* @__PURE__ */ new Map(), useStore = (initialValue) => {
  let key = /* @__PURE__ */ Symbol();
  return listeners.set(key, []), state.set(key, initialValue), { get: () => state.get(key), set: (update) => {
    let current = state.get(key), next = isFunction(update) ? update(current) : update;
    next !== current && (state.set(key, next), listeners.get(key)?.forEach((listener) => {
      teardowns.get(listener)?.(), teardowns.set(listener, listener(next));
    }));
  }, subscribe: (listener) => (listeners.get(key)?.push(listener), () => {
    let list = listeners.get(key);
    list && listeners.set(
      key,
      list.filter((l) => l !== listener)
    );
  }), teardown: () => {
    listeners.get(key)?.forEach((listener) => {
      teardowns.get(listener)?.(), teardowns.delete(listener);
    }), listeners.delete(key), state.delete(key);
  } };
}, mapElements = (highlights) => {
  let root = document.getElementById("storybook-root"), map = /* @__PURE__ */ new Map();
  for (let highlight of highlights) {
    let { priority = 0 } = highlight;
    for (let selector of highlight.selectors) {
      let elements = [
        ...document.querySelectorAll(
          // Elements matching the selector, excluding storybook elements and their descendants.
          // Necessary to find portaled elements (e.g. children of `body`).
          `:is(${selector}):not([id^="storybook-"], [id^="storybook-"] *, [class^="sb-"], [class^="sb-"] *)`
        ),
        // Elements matching the selector inside the storybook root, as these were excluded above.
        ...root?.querySelectorAll(selector) || []
      ];
      for (let element of elements) {
        let existing = map.get(element);
        (!existing || existing.priority <= priority) && map.set(element, {
          ...highlight,
          priority,
          selectors: Array.from(new Set((existing?.selectors || []).concat(selector)))
        });
      }
    }
  }
  return map;
}, mapBoxes = (elements) => Array.from(elements.entries()).map(([element, { selectors, styles, hoverStyles, focusStyles, menu }]) => {
  let { top, left, width, height } = element.getBoundingClientRect(), { position } = getComputedStyle(element);
  return {
    element,
    selectors,
    styles,
    hoverStyles,
    focusStyles,
    menu,
    top: position === "fixed" ? top : top + window.scrollY,
    left: position === "fixed" ? left : left + window.scrollX,
    width,
    height
  };
}).sort((a, b) => b.width * b.height - a.width * a.height), isOverMenu = (menuElement, coordinates) => {
  let menu = menuElement.getBoundingClientRect(), { x, y } = coordinates;
  return menu?.top && menu?.left && x >= menu.left && x <= menu.left + menu.width && y >= menu.top && y <= menu.top + menu.height;
}, isTargeted = (box, boxElement, coordinates) => {
  if (!boxElement || !coordinates)
    return !1;
  let { left, top, width, height } = box;
  height < MIN_TOUCH_AREA_SIZE && (top = top - Math.round((MIN_TOUCH_AREA_SIZE - height) / 2), height = MIN_TOUCH_AREA_SIZE), width < MIN_TOUCH_AREA_SIZE && (left = left - Math.round((MIN_TOUCH_AREA_SIZE - width) / 2), width = MIN_TOUCH_AREA_SIZE), boxElement.style.position === "fixed" && (left += window.scrollX, top += window.scrollY);
  let { x, y } = coordinates;
  return x >= left && x <= left + width && y >= top && y <= top + height;
}, keepInViewport = (element, targetCoordinates, options = {}) => {
  let { x, y } = targetCoordinates, { margin = 5, topOffset = 0, centered = !1 } = options, { scrollX, scrollY, innerHeight: windowHeight, innerWidth: windowWidth } = window, top = Math.min(
    element.style.position === "fixed" ? y - scrollY : y,
    windowHeight - element.clientHeight - margin - topOffset + scrollY
  ), leftOffset = centered ? element.clientWidth / 2 : 0, left = element.style.position === "fixed" ? Math.max(Math.min(x - scrollX, windowWidth - leftOffset - margin), leftOffset + margin) : Math.max(
    Math.min(x, windowWidth - leftOffset - margin + scrollX),
    leftOffset + margin + scrollX
  );
  Object.assign(element.style, {
    ...left !== x && { left: `${left}px` },
    ...top !== y && { top: `${top}px` }
  });
}, showPopover = (element) => {
  window.HTMLElement.prototype.hasOwnProperty("showPopover") && element.showPopover();
}, hidePopover = (element) => {
  window.HTMLElement.prototype.hasOwnProperty("showPopover") && element.hidePopover();
}, getEventDetails = (target) => ({
  top: target.top,
  left: target.left,
  width: target.width,
  height: target.height,
  selectors: target.selectors,
  element: {
    attributes: Object.fromEntries(
      Array.from(target.element.attributes).map((attr) => [attr.name, attr.value])
    ),
    localName: target.element.localName,
    tagName: target.element.tagName,
    outerHTML: target.element.outerHTML
  }
});

// src/highlight/useHighlights.ts
var menuId = "storybook-highlights-menu", rootId = "storybook-highlights-root", storybookRootId = "storybook-root", useHighlights = (channel) => {
  if (globalThis.__STORYBOOK_HIGHLIGHT_INITIALIZED)
    return;
  globalThis.__STORYBOOK_HIGHLIGHT_INITIALIZED = !0;
  let { document: document3 } = globalThis, highlights = useStore([]), elements = useStore(/* @__PURE__ */ new Map()), boxes = useStore([]), clickCoords = useStore(), hoverCoords = useStore(), targets = useStore([]), hovered = useStore([]), focused = useStore(), selected = useStore(), root = document3.getElementById(rootId);
  highlights.subscribe(() => {
    root || (root = createElement("div", { id: rootId }), document3.body.appendChild(root));
  }), highlights.subscribe((value) => {
    let storybookRoot = document3.getElementById(storybookRootId);
    if (!storybookRoot)
      return;
    elements.set(mapElements(value));
    let observer = new MutationObserver(() => elements.set(mapElements(value)));
    return observer.observe(storybookRoot, { subtree: !0, childList: !0 }), () => {
      observer.disconnect();
    };
  }), elements.subscribe((value) => {
    let updateBoxes = () => requestAnimationFrame(() => boxes.set(mapBoxes(value))), observer = new ResizeObserver(updateBoxes);
    observer.observe(document3.body), Array.from(value.keys()).forEach((element) => observer.observe(element));
    let scrollers = Array.from(document3.body.querySelectorAll("*")).filter((el) => {
      let { overflow, overflowX, overflowY } = window.getComputedStyle(el);
      return ["auto", "scroll"].some((o) => [overflow, overflowX, overflowY].includes(o));
    });
    return scrollers.forEach((element) => element.addEventListener("scroll", updateBoxes)), () => {
      observer.disconnect(), scrollers.forEach((element) => element.removeEventListener("scroll", updateBoxes));
    };
  }), elements.subscribe((value) => {
    let sticky = Array.from(value.keys()).filter(({ style }) => style.position === "sticky"), updateBoxes = () => requestAnimationFrame(() => {
      boxes.set(
        (current) => current.map((box) => {
          if (sticky.includes(box.element)) {
            let { top, left } = box.element.getBoundingClientRect();
            return { ...box, top: top + window.scrollY, left: left + window.scrollX };
          }
          return box;
        })
      );
    });
    return document3.addEventListener("scroll", updateBoxes), () => document3.removeEventListener("scroll", updateBoxes);
  }), elements.subscribe((value) => {
    targets.set((t) => t.filter(({ element }) => value.has(element)));
  }), targets.subscribe((value) => {
    value.length ? (selected.set((s) => value.some((t) => t.element === s?.element) ? s : void 0), focused.set((s) => value.some((t) => t.element === s?.element) ? s : void 0)) : (selected.set(void 0), focused.set(void 0), clickCoords.set(void 0));
  });
  let styleElementByHighlight = new Map(/* @__PURE__ */ new Map());
  highlights.subscribe((value) => {
    value.forEach(({ keyframes }) => {
      if (keyframes) {
        let style = styleElementByHighlight.get(keyframes);
        style || (style = document3.createElement("style"), style.setAttribute("data-highlight", "keyframes"), styleElementByHighlight.set(keyframes, style), document3.head.appendChild(style)), style.innerHTML = keyframes;
      }
    }), styleElementByHighlight.forEach((style, keyframes) => {
      value.some((v) => v.keyframes === keyframes) || (style.remove(), styleElementByHighlight.delete(keyframes));
    });
  });
  let boxElementByTargetElement = new Map(/* @__PURE__ */ new Map());
  boxes.subscribe((value) => {
    value.forEach((box) => {
      let boxElement = boxElementByTargetElement.get(box.element);
      if (root && !boxElement) {
        let props = {
          popover: "manual",
          "data-highlight-dimensions": `w${box.width.toFixed(0)}h${box.height.toFixed(0)}`,
          "data-highlight-coordinates": `x${box.left.toFixed(0)}y${box.top.toFixed(0)}`
        };
        boxElement = root.appendChild(
          createElement("div", props, [createElement("div")])
        ), boxElementByTargetElement.set(box.element, boxElement);
      }
    }), boxElementByTargetElement.forEach((box, element) => {
      value.some(({ element: e }) => e === element) || (box.remove(), boxElementByTargetElement.delete(element));
    });
  }), boxes.subscribe((value) => {
    let targetable = value.filter((box) => box.menu);
    if (!targetable.length)
      return;
    let onClick = (event) => {
      requestAnimationFrame(() => {
        let menu = document3.getElementById(menuId), coords = { x: event.pageX, y: event.pageY };
        if (menu && !isOverMenu(menu, coords)) {
          let results = targetable.filter((box) => {
            let boxElement = boxElementByTargetElement.get(box.element);
            return isTargeted(box, boxElement, coords);
          });
          clickCoords.set(results.length ? coords : void 0), targets.set(results);
        }
      });
    };
    return document3.addEventListener("click", onClick), () => document3.removeEventListener("click", onClick);
  });
  let updateHovered = () => {
    let menu = document3.getElementById(menuId), coords = hoverCoords.get();
    !coords || menu && isOverMenu(menu, coords) || hovered.set((current) => {
      let update = boxes.get().filter((box) => {
        let boxElement = boxElementByTargetElement.get(box.element);
        return isTargeted(box, boxElement, coords);
      }), existing = current.filter((box) => update.includes(box)), additions = update.filter((box) => !current.includes(box)), hasRemovals = current.length - existing.length;
      return additions.length || hasRemovals ? [...existing, ...additions] : current;
    });
  };
  hoverCoords.subscribe(updateHovered), boxes.subscribe(updateHovered);
  let updateBoxStyles = () => {
    let selectedElement = selected.get(), targetElements = selectedElement ? [selectedElement] : targets.get(), focusedElement = targetElements.length === 1 ? targetElements[0] : focused.get(), isMenuOpen = clickCoords.get() !== void 0;
    boxes.get().forEach((box) => {
      let boxElement = boxElementByTargetElement.get(box.element);
      if (boxElement) {
        let isFocused = focusedElement === box, isHovered = isMenuOpen ? focusedElement ? isFocused : targetElements.includes(box) : hovered.get()?.includes(box);
        Object.assign(boxElement.style, {
          animation: "none",
          background: "transparent",
          border: "none",
          boxSizing: "border-box",
          outline: "none",
          outlineOffset: "0px",
          ...box.styles,
          ...isHovered ? box.hoverStyles : {},
          ...isFocused ? box.focusStyles : {},
          position: getComputedStyle(box.element).position === "fixed" ? "fixed" : "absolute",
          zIndex: MAX_Z_INDEX - 10,
          top: `${box.top}px`,
          left: `${box.left}px`,
          width: `${box.width}px`,
          height: `${box.height}px`,
          margin: 0,
          padding: 0,
          cursor: box.menu && isHovered ? "pointer" : "default",
          pointerEvents: box.menu ? "auto" : "none",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          overflow: "visible"
        }), Object.assign(boxElement.children[0].style, {
          width: "100%",
          height: "100%",
          minHeight: `${MIN_TOUCH_AREA_SIZE}px`,
          minWidth: `${MIN_TOUCH_AREA_SIZE}px`,
          boxSizing: "content-box",
          padding: boxElement.style.outlineWidth || "0px"
        }), showPopover(boxElement);
      }
    });
  };
  boxes.subscribe(updateBoxStyles), targets.subscribe(updateBoxStyles), hovered.subscribe(updateBoxStyles), focused.subscribe(updateBoxStyles), selected.subscribe(updateBoxStyles);
  let renderMenu = () => {
    if (!root)
      return;
    let menu = document3.getElementById(menuId);
    if (menu)
      menu.innerHTML = "";
    else {
      let props = { id: menuId, popover: "manual" };
      menu = root.appendChild(createElement("div", props)), root.appendChild(
        createElement("style", {}, [
          `
            #${menuId} {
              position: absolute;
              z-index: ${MAX_Z_INDEX};
              width: 300px;
              padding: 0px;
              margin: 15px 0 0 0;
              transform: translateX(-50%);
              font-family: "Nunito Sans", -apple-system, ".SFNSText-Regular", "San Francisco", BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Helvetica, Arial, sans-serif;
              font-size: 12px;
              background: white;
              border: none;
              border-radius: 6px;
              box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.05), 0 5px 15px 0 rgba(0, 0, 0, 0.1);
              color: #2E3438;
            }
            #${menuId} ul {
              list-style: none;
              margin: 0;
              padding: 0;
            }
            #${menuId} > ul {
              max-height: 300px;
              overflow-y: auto;
              padding: 4px 0;
            }
            #${menuId} li {
              padding: 0 4px;
              margin: 0;
            }
            #${menuId} li > :not(ul) {
              display: flex;
              padding: 8px;
              margin: 0;
              align-items: center;
              gap: 8px;
              border-radius: 4px;
            }
            #${menuId} button {
              width: 100%;
              border: 0;
              background: transparent;
              color: inherit;
              text-align: left;
              font-family: inherit;
              font-size: inherit;
            }
            #${menuId} button:focus-visible {
              outline-color: #029CFD;
            }
            #${menuId} button:hover {
              background: rgba(2, 156, 253, 0.07);
              color: #029CFD;
              cursor: pointer;
            }
            #${menuId} li code {
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
              line-height: 16px;
              font-size: 11px;
            }
            #${menuId} li svg {
              flex-shrink: 0;
              margin: 1px;
              color: #73828C;
            }
            #${menuId} li > button:hover svg, #${menuId} li > button:focus-visible svg {
              color: #029CFD;
            }
            #${menuId} .element-list li svg {
              display: none;
            }
            #${menuId} li.selectable svg, #${menuId} li.selected svg {
              display: block;
            }
            #${menuId} .menu-list {
              border-top: 1px solid rgba(38, 85, 115, 0.15);
            }
            #${menuId} .menu-list > li:not(:last-child) {
              padding-bottom: 4px;
              margin-bottom: 4px;
              border-bottom: 1px solid rgba(38, 85, 115, 0.15);
            }
            #${menuId} .menu-items, #${menuId} .menu-items li {
              padding: 0;
            }
            #${menuId} .menu-item {
              display: flex;
            }
            #${menuId} .menu-item-content {
              display: flex;
              flex-direction: column;
              flex-grow: 1;
            }
          `
        ])
      );
    }
    let selectedElement = selected.get(), elementList = selectedElement ? [selectedElement] : targets.get();
    if (elementList.length && (menu.style.position = getComputedStyle(elementList[0].element).position === "fixed" ? "fixed" : "absolute", menu.appendChild(
      createElement(
        "ul",
        { class: "element-list" },
        elementList.map((target) => {
          let selectable = elementList.length > 1 && !!target.menu?.some(
            (group) => group.some(
              (item) => !item.selectors || item.selectors.some((s) => target.selectors.includes(s))
            )
          ), props = selectable ? {
            class: "selectable",
            onClick: () => selected.set(target),
            onMouseEnter: () => focused.set(target),
            onMouseLeave: () => focused.set(void 0)
          } : selectedElement ? { class: "selected", onClick: () => selected.set(void 0) } : {}, asButton = selectable || selectedElement;
          return createElement("li", props, [
            createElement(asButton ? "button" : "div", asButton ? { type: "button" } : {}, [
              selectedElement ? createIcon("chevronLeft") : null,
              createElement("code", {}, [target.element.outerHTML]),
              selectable ? createIcon("chevronRight") : null
            ])
          ]);
        })
      )
    )), selected.get() || targets.get().length === 1) {
      let target = selected.get() || targets.get()[0], menuGroups = target.menu?.filter(
        (group) => group.some(
          (item) => !item.selectors || item.selectors.some((s) => target.selectors.includes(s))
        )
      );
      menuGroups?.length && menu.appendChild(
        createElement(
          "ul",
          { class: "menu-list" },
          menuGroups.map(
            (menuItems) => createElement("li", {}, [
              createElement(
                "ul",
                { class: "menu-items" },
                menuItems.map(
                  ({ id, title, description, iconLeft, iconRight, clickEvent: event }) => {
                    let onClick = event && (() => channel.emit(event, id, getEventDetails(target)));
                    return createElement("li", {}, [
                      createElement(
                        onClick ? "button" : "div",
                        onClick ? { class: "menu-item", type: "button", onClick } : { class: "menu-item" },
                        [
                          iconLeft ? createIcon(iconLeft) : null,
                          createElement("div", { class: "menu-item-content" }, [
                            createElement(description ? "strong" : "span", {}, [title]),
                            description && createElement("span", {}, [description])
                          ]),
                          iconRight ? createIcon(iconRight) : null
                        ]
                      )
                    ]);
                  }
                )
              )
            ])
          )
        )
      );
    }
    let coords = clickCoords.get();
    coords ? (Object.assign(menu.style, {
      display: "block",
      left: `${menu.style.position === "fixed" ? coords.x - window.scrollX : coords.x}px`,
      top: `${menu.style.position === "fixed" ? coords.y - window.scrollY : coords.y}px`
    }), showPopover(menu), requestAnimationFrame(() => keepInViewport(menu, coords, { topOffset: 15, centered: !0 }))) : (hidePopover(menu), Object.assign(menu.style, { display: "none" }));
  };
  targets.subscribe(renderMenu), selected.subscribe(renderMenu);
  let addHighlight = (highlight) => {
    let info = normalizeOptions(highlight);
    highlights.set((value) => {
      let others = info.id ? value.filter((h) => h.id !== info.id) : value;
      return info.selectors?.length ? [...others, info] : others;
    });
  }, removeHighlight = (id) => {
    id && highlights.set((value) => value.filter((h) => h.id !== id));
  }, resetState = () => {
    highlights.set([]), elements.set(/* @__PURE__ */ new Map()), boxes.set([]), clickCoords.set(void 0), hoverCoords.set(void 0), targets.set([]), hovered.set([]), focused.set(void 0), selected.set(void 0);
  }, removeTimeout, scrollIntoView = (target, options) => {
    let id = "scrollIntoView-highlight";
    clearTimeout(removeTimeout), removeHighlight(id);
    let element = document3.querySelector(target);
    if (!element) {
      console.warn(`Cannot scroll into view: ${target} not found`);
      return;
    }
    element.scrollIntoView({ behavior: "smooth", block: "center", ...options });
    let keyframeName = `kf-${Math.random().toString(36).substring(2, 15)}`;
    highlights.set((value) => [
      ...value,
      {
        id,
        priority: 1e3,
        selectors: [target],
        styles: {
          outline: "2px solid #1EA7FD",
          outlineOffset: "-1px",
          animation: `${keyframeName} 3s linear forwards`
        },
        keyframes: `@keyframes ${keyframeName} {
          0% { outline: 2px solid #1EA7FD; }
          20% { outline: 2px solid #1EA7FD00; }
          40% { outline: 2px solid #1EA7FD; }
          60% { outline: 2px solid #1EA7FD00; }
          80% { outline: 2px solid #1EA7FD; }
          100% { outline: 2px solid #1EA7FD00; }
        }`
      }
    ]), removeTimeout = setTimeout(() => removeHighlight(id), 3500);
  }, onMouseMove = (event) => {
    requestAnimationFrame(() => hoverCoords.set({ x: event.pageX, y: event.pageY }));
  };
  document3.body.addEventListener("mousemove", onMouseMove), channel.on(HIGHLIGHT, addHighlight), channel.on(REMOVE_HIGHLIGHT, removeHighlight), channel.on(RESET_HIGHLIGHT, resetState), channel.on(SCROLL_INTO_VIEW, scrollIntoView), channel.on(external_STORYBOOK_MODULE_CORE_EVENTS_.STORY_RENDER_PHASE_CHANGED, ({ newPhase }) => {
    newPhase === "loading" && resetState();
  });
};

// src/highlight/preview.ts
globalThis?.FEATURES?.highlight && external_STORYBOOK_MODULE_PREVIEW_API_.addons?.ready && external_STORYBOOK_MODULE_PREVIEW_API_.addons.ready().then(useHighlights);
var preview_default4 = () => definePreviewAddon5({});

// src/measure/preview.ts


// src/measure/withMeasure.ts


// src/measure/box-model/canvas.ts

function getDocumentWidthAndHeight() {
  let container = external_STORYBOOK_MODULE_GLOBAL_.global.document.documentElement, height = Math.max(container.scrollHeight, container.offsetHeight);
  return { width: Math.max(container.scrollWidth, container.offsetWidth), height };
}
function createCanvas() {
  let canvas = external_STORYBOOK_MODULE_GLOBAL_.global.document.createElement("canvas");
  canvas.id = "storybook-addon-measure";
  let context = canvas.getContext("2d");
  invariant(context != null);
  let { width, height } = getDocumentWidthAndHeight();
  return setCanvasWidthAndHeight(canvas, context, { width, height }), canvas.style.position = "absolute", canvas.style.left = "0", canvas.style.top = "0", canvas.style.zIndex = "2147483647", canvas.style.pointerEvents = "none", external_STORYBOOK_MODULE_GLOBAL_.global.document.body.appendChild(canvas), { canvas, context, width, height };
}
function setCanvasWidthAndHeight(canvas, context, { width, height }) {
  canvas.style.width = `${width}px`, canvas.style.height = `${height}px`;
  let scale = external_STORYBOOK_MODULE_GLOBAL_.global.window.devicePixelRatio;
  canvas.width = Math.floor(width * scale), canvas.height = Math.floor(height * scale), context.scale(scale, scale);
}
var state2 = {};
function init() {
  state2.canvas || (state2 = createCanvas());
}
function clear() {
  state2.context && state2.context.clearRect(0, 0, state2.width ?? 0, state2.height ?? 0);
}
function draw(callback) {
  clear(), callback(state2.context);
}
function rescale() {
  invariant(state2.canvas, "Canvas should exist in the state."), invariant(state2.context, "Context should exist in the state."), setCanvasWidthAndHeight(state2.canvas, state2.context, { width: 0, height: 0 });
  let { width, height } = getDocumentWidthAndHeight();
  setCanvasWidthAndHeight(state2.canvas, state2.context, { width, height }), state2.width = width, state2.height = height;
}
function destroy() {
  state2.canvas && (clear(), state2.canvas.parentNode?.removeChild(state2.canvas), state2 = {});
}

// src/measure/box-model/visualizer.ts


// src/measure/box-model/labels.ts
var colors = {
  margin: "#f6b26b",
  border: "#ffe599",
  padding: "#93c47d",
  content: "#6fa8dc",
  text: "#232020"
}, labelPadding = 6;
function roundedRect(context, { x, y, w, h, r }) {
  x = x - w / 2, y = y - h / 2, w < 2 * r && (r = w / 2), h < 2 * r && (r = h / 2), context.beginPath(), context.moveTo(x + r, y), context.arcTo(x + w, y, x + w, y + h, r), context.arcTo(x + w, y + h, x, y + h, r), context.arcTo(x, y + h, x, y, r), context.arcTo(x, y, x + w, y, r), context.closePath();
}
function positionCoordinate(position, { padding, border, width, height, top, left }) {
  let contentWidth = width - border.left - border.right - padding.left - padding.right, contentHeight = height - padding.top - padding.bottom - border.top - border.bottom, x = left + border.left + padding.left, y = top + border.top + padding.top;
  return position === "top" ? x += contentWidth / 2 : position === "right" ? (x += contentWidth, y += contentHeight / 2) : position === "bottom" ? (x += contentWidth / 2, y += contentHeight) : position === "left" ? y += contentHeight / 2 : position === "center" && (x += contentWidth / 2, y += contentHeight / 2), { x, y };
}
function offset(type, position, { margin, border, padding }, labelPaddingSize, external) {
  let shift = (dir) => 0, offsetX = 0, offsetY = 0, locationMultiplier = external ? 1 : 0.5, labelPaddingShift = external ? labelPaddingSize * 2 : 0;
  return type === "padding" ? shift = (dir) => padding[dir] * locationMultiplier + labelPaddingShift : type === "border" ? shift = (dir) => padding[dir] + border[dir] * locationMultiplier + labelPaddingShift : type === "margin" && (shift = (dir) => padding[dir] + border[dir] + margin[dir] * locationMultiplier + labelPaddingShift), position === "top" ? offsetY = -shift("top") : position === "right" ? offsetX = shift("right") : position === "bottom" ? offsetY = shift("bottom") : position === "left" && (offsetX = -shift("left")), { offsetX, offsetY };
}
function collide(a, b) {
  return Math.abs(a.x - b.x) < Math.abs(a.w + b.w) / 2 && Math.abs(a.y - b.y) < Math.abs(a.h + b.h) / 2;
}
function overlapAdjustment(position, currentRect, prevRect) {
  return position === "top" ? currentRect.y = prevRect.y - prevRect.h - labelPadding : position === "right" ? currentRect.x = prevRect.x + prevRect.w / 2 + labelPadding + currentRect.w / 2 : position === "bottom" ? currentRect.y = prevRect.y + prevRect.h + labelPadding : position === "left" && (currentRect.x = prevRect.x - prevRect.w / 2 - labelPadding - currentRect.w / 2), { x: currentRect.x, y: currentRect.y };
}
function textWithRect(context, type, { x, y, w, h }, text) {
  return roundedRect(context, { x, y, w, h, r: 3 }), context.fillStyle = `${colors[type]}dd`, context.fill(), context.strokeStyle = colors[type], context.stroke(), context.fillStyle = colors.text, context.fillText(text, x, y), roundedRect(context, { x, y, w, h, r: 3 }), context.fillStyle = `${colors[type]}dd`, context.fill(), context.strokeStyle = colors[type], context.stroke(), context.fillStyle = colors.text, context.fillText(text, x, y), { x, y, w, h };
}
function configureText(context, text) {
  context.font = "600 12px monospace", context.textBaseline = "middle", context.textAlign = "center";
  let metrics = context.measureText(text), actualHeight = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent, w = metrics.width + labelPadding * 2, h = actualHeight + labelPadding * 2;
  return { w, h };
}
function drawLabel(context, measurements, { type, position = "center", text }, prevRect, external = !1) {
  let { x, y } = positionCoordinate(position, measurements), { offsetX, offsetY } = offset(type, position, measurements, labelPadding + 1, external);
  x += offsetX, y += offsetY;
  let { w, h } = configureText(context, text);
  if (prevRect && collide({ x, y, w, h }, prevRect)) {
    let adjusted = overlapAdjustment(position, { x, y, w, h }, prevRect);
    x = adjusted.x, y = adjusted.y;
  }
  return textWithRect(context, type, { x, y, w, h }, text);
}
function floatingOffset(alignment, { w, h }) {
  let deltaW = w * 0.5 + labelPadding, deltaH = h * 0.5 + labelPadding;
  return {
    offsetX: (alignment.x === "left" ? -1 : 1) * deltaW,
    offsetY: (alignment.y === "top" ? -1 : 1) * deltaH
  };
}
function drawFloatingLabel(context, measurements, { type, text }) {
  let { floatingAlignment: floatingAlignment2, extremities } = measurements, x = extremities[floatingAlignment2.x], y = extremities[floatingAlignment2.y], { w, h } = configureText(context, text), { offsetX, offsetY } = floatingOffset(floatingAlignment2, {
    w,
    h
  });
  return x += offsetX, y += offsetY, textWithRect(context, type, { x, y, w, h }, text);
}
function drawStack(context, measurements, stack, external) {
  let rects = [];
  stack.forEach((l, idx) => {
    let rect = external && l.position === "center" ? drawFloatingLabel(context, measurements, l) : drawLabel(context, measurements, l, rects[idx - 1], external);
    rects[idx] = rect;
  });
}
function labelStacks(context, measurements, labels, externalLabels) {
  let stacks = labels.reduce((acc, l) => (Object.prototype.hasOwnProperty.call(acc, l.position) || (acc[l.position] = []), acc[l.position]?.push(l), acc), {});
  stacks.top && drawStack(context, measurements, stacks.top, externalLabels), stacks.right && drawStack(context, measurements, stacks.right, externalLabels), stacks.bottom && drawStack(context, measurements, stacks.bottom, externalLabels), stacks.left && drawStack(context, measurements, stacks.left, externalLabels), stacks.center && drawStack(context, measurements, stacks.center, externalLabels);
}

// src/measure/box-model/visualizer.ts
var colors2 = {
  margin: "#f6b26ba8",
  border: "#ffe599a8",
  padding: "#93c47d8c",
  content: "#6fa8dca8"
}, SMALL_NODE_SIZE = 30;
function pxToNumber(px) {
  return parseInt(px.replace("px", ""), 10);
}
function round(value) {
  return Number.isInteger(value) ? value : value.toFixed(2);
}
function filterZeroValues(labels) {
  return labels.filter((l) => l.text !== 0 && l.text !== "0");
}
function floatingAlignment(extremities) {
  let windowExtremities = {
    top: external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollY,
    bottom: external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollY + external_STORYBOOK_MODULE_GLOBAL_.global.window.innerHeight,
    left: external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollX,
    right: external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollX + external_STORYBOOK_MODULE_GLOBAL_.global.window.innerWidth
  }, distances = {
    top: Math.abs(windowExtremities.top - extremities.top),
    bottom: Math.abs(windowExtremities.bottom - extremities.bottom),
    left: Math.abs(windowExtremities.left - extremities.left),
    right: Math.abs(windowExtremities.right - extremities.right)
  };
  return {
    x: distances.left > distances.right ? "left" : "right",
    y: distances.top > distances.bottom ? "top" : "bottom"
  };
}
function measureElement(element) {
  let style = external_STORYBOOK_MODULE_GLOBAL_.global.getComputedStyle(element), { top, left, right, bottom, width, height } = element.getBoundingClientRect(), {
    marginTop,
    marginBottom,
    marginLeft,
    marginRight,
    paddingTop,
    paddingBottom,
    paddingLeft,
    paddingRight,
    borderBottomWidth,
    borderTopWidth,
    borderLeftWidth,
    borderRightWidth
  } = style;
  top = top + external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollY, left = left + external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollX, bottom = bottom + external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollY, right = right + external_STORYBOOK_MODULE_GLOBAL_.global.window.scrollX;
  let margin = {
    top: pxToNumber(marginTop),
    bottom: pxToNumber(marginBottom),
    left: pxToNumber(marginLeft),
    right: pxToNumber(marginRight)
  }, padding = {
    top: pxToNumber(paddingTop),
    bottom: pxToNumber(paddingBottom),
    left: pxToNumber(paddingLeft),
    right: pxToNumber(paddingRight)
  }, border = {
    top: pxToNumber(borderTopWidth),
    bottom: pxToNumber(borderBottomWidth),
    left: pxToNumber(borderLeftWidth),
    right: pxToNumber(borderRightWidth)
  }, extremities = {
    top: top - margin.top,
    bottom: bottom + margin.bottom,
    left: left - margin.left,
    right: right + margin.right
  };
  return {
    margin,
    padding,
    border,
    top,
    left,
    bottom,
    right,
    width,
    height,
    extremities,
    floatingAlignment: floatingAlignment(extremities)
  };
}
function drawMargin(context, { margin, width, height, top, left, bottom, right }) {
  let marginHeight = height + margin.bottom + margin.top;
  context.fillStyle = colors2.margin, context.fillRect(left, top - margin.top, width, margin.top), context.fillRect(right, top - margin.top, margin.right, marginHeight), context.fillRect(left, bottom, width, margin.bottom), context.fillRect(left - margin.left, top - margin.top, margin.left, marginHeight);
  let marginLabels = [
    {
      type: "margin",
      text: round(margin.top),
      position: "top"
    },
    {
      type: "margin",
      text: round(margin.right),
      position: "right"
    },
    {
      type: "margin",
      text: round(margin.bottom),
      position: "bottom"
    },
    {
      type: "margin",
      text: round(margin.left),
      position: "left"
    }
  ];
  return filterZeroValues(marginLabels);
}
function drawPadding(context, { padding, border, width, height, top, left, bottom, right }) {
  let paddingWidth = width - border.left - border.right, paddingHeight = height - padding.top - padding.bottom - border.top - border.bottom;
  context.fillStyle = colors2.padding, context.fillRect(left + border.left, top + border.top, paddingWidth, padding.top), context.fillRect(
    right - padding.right - border.right,
    top + padding.top + border.top,
    padding.right,
    paddingHeight
  ), context.fillRect(
    left + border.left,
    bottom - padding.bottom - border.bottom,
    paddingWidth,
    padding.bottom
  ), context.fillRect(left + border.left, top + padding.top + border.top, padding.left, paddingHeight);
  let paddingLabels = [
    {
      type: "padding",
      text: padding.top,
      position: "top"
    },
    {
      type: "padding",
      text: padding.right,
      position: "right"
    },
    {
      type: "padding",
      text: padding.bottom,
      position: "bottom"
    },
    {
      type: "padding",
      text: padding.left,
      position: "left"
    }
  ];
  return filterZeroValues(paddingLabels);
}
function drawBorder(context, { border, width, height, top, left, bottom, right }) {
  let borderHeight = height - border.top - border.bottom;
  context.fillStyle = colors2.border, context.fillRect(left, top, width, border.top), context.fillRect(left, bottom - border.bottom, width, border.bottom), context.fillRect(left, top + border.top, border.left, borderHeight), context.fillRect(right - border.right, top + border.top, border.right, borderHeight);
  let borderLabels = [
    {
      type: "border",
      text: border.top,
      position: "top"
    },
    {
      type: "border",
      text: border.right,
      position: "right"
    },
    {
      type: "border",
      text: border.bottom,
      position: "bottom"
    },
    {
      type: "border",
      text: border.left,
      position: "left"
    }
  ];
  return filterZeroValues(borderLabels);
}
function drawContent(context, { padding, border, width, height, top, left }) {
  let contentWidth = width - border.left - border.right - padding.left - padding.right, contentHeight = height - padding.top - padding.bottom - border.top - border.bottom;
  return context.fillStyle = colors2.content, context.fillRect(
    left + border.left + padding.left,
    top + border.top + padding.top,
    contentWidth,
    contentHeight
  ), [
    {
      type: "content",
      position: "center",
      text: `${round(contentWidth)} x ${round(contentHeight)}`
    }
  ];
}
function drawBoxModel(element) {
  return (context) => {
    if (element && context) {
      let measurements = measureElement(element), marginLabels = drawMargin(context, measurements), paddingLabels = drawPadding(context, measurements), borderLabels = drawBorder(context, measurements), contentLabels = drawContent(context, measurements), externalLabels = measurements.width <= SMALL_NODE_SIZE * 3 || measurements.height <= SMALL_NODE_SIZE;
      labelStacks(
        context,
        measurements,
        [...contentLabels, ...paddingLabels, ...borderLabels, ...marginLabels],
        externalLabels
      );
    }
  };
}
function drawSelectedElement(element) {
  draw(drawBoxModel(element));
}

// src/measure/util.ts

var deepElementFromPoint = (x, y) => {
  let element = external_STORYBOOK_MODULE_GLOBAL_.global.document.elementFromPoint(x, y), crawlShadows = (node) => {
    if (node && node.shadowRoot) {
      let nestedElement = node.shadowRoot.elementFromPoint(x, y);
      return node.isEqualNode(nestedElement) ? node : nestedElement.shadowRoot ? crawlShadows(nestedElement) : nestedElement;
    }
    return node;
  };
  return crawlShadows(element) || element;
};

// src/measure/withMeasure.ts
var nodeAtPointerRef, pointer = { x: 0, y: 0 };
function findAndDrawElement(x, y) {
  nodeAtPointerRef = deepElementFromPoint(x, y), drawSelectedElement(nodeAtPointerRef);
}
var withMeasure = (StoryFn, context) => {
  let { measureEnabled } = context.globals || {}, measureActive = measureEnabled && !context.parameters?.measure?.disable;
  return (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => {
    if (typeof globalThis.document > "u")
      return;
    let onPointerMove = (event) => {
      window.requestAnimationFrame(() => {
        event.stopPropagation(), pointer.x = event.clientX, pointer.y = event.clientY;
      });
    };
    return globalThis.document.addEventListener("pointermove", onPointerMove), () => {
      globalThis.document.removeEventListener("pointermove", onPointerMove);
    };
  }, []), (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => {
    let onPointerOver = (event) => {
      window.requestAnimationFrame(() => {
        event.stopPropagation(), findAndDrawElement(event.clientX, event.clientY);
      });
    }, onResize = () => {
      window.requestAnimationFrame(() => {
        rescale();
      });
    };
    return context.viewMode === "story" && measureActive && (globalThis.document.addEventListener("pointerover", onPointerOver), init(), globalThis.window.addEventListener("resize", onResize), findAndDrawElement(pointer.x, pointer.y)), () => {
      globalThis.window.removeEventListener("resize", onResize), destroy();
    };
  }, [measureActive, context.viewMode]), StoryFn();
};

// src/measure/preview.ts
var decorators2 = globalThis.FEATURES?.measure ? [withMeasure] : [], initialGlobals2 = {
  [PARAM_KEY]: !1
}, preview_default5 = () => definePreviewAddon6({
  decorators: decorators2,
  initialGlobals: initialGlobals2
});

// src/outline/preview.ts


// src/outline/withOutline.ts


// src/outline/helpers.ts

var clearStyles2 = (selector) => {
  (Array.isArray(selector) ? selector : [selector]).forEach(clearStyle2);
}, clearStyle2 = (input) => {
  let selector = typeof input == "string" ? input : input.join(""), element = external_STORYBOOK_MODULE_GLOBAL_.global.document.getElementById(selector);
  element && element.parentElement && element.parentElement.removeChild(element);
}, addOutlineStyles = (selector, css) => {
  let existingStyle = external_STORYBOOK_MODULE_GLOBAL_.global.document.getElementById(selector);
  if (existingStyle)
    existingStyle.innerHTML !== css && (existingStyle.innerHTML = css);
  else {
    let style = external_STORYBOOK_MODULE_GLOBAL_.global.document.createElement("style");
    style.setAttribute("id", selector), style.innerHTML = css, external_STORYBOOK_MODULE_GLOBAL_.global.document.head.appendChild(style);
  }
};

// src/outline/outlineCSS.ts
function outlineCSS(selector) {
  return (0,chunk_3LY4VQVK/* dedent */.T)`
    ${selector} body {
      outline: 1px solid #2980b9 !important;
    }

    ${selector} article {
      outline: 1px solid #3498db !important;
    }

    ${selector} nav {
      outline: 1px solid #0088c3 !important;
    }

    ${selector} aside {
      outline: 1px solid #33a0ce !important;
    }

    ${selector} section {
      outline: 1px solid #66b8da !important;
    }

    ${selector} header {
      outline: 1px solid #99cfe7 !important;
    }

    ${selector} footer {
      outline: 1px solid #cce7f3 !important;
    }

    ${selector} h1 {
      outline: 1px solid #162544 !important;
    }

    ${selector} h2 {
      outline: 1px solid #314e6e !important;
    }

    ${selector} h3 {
      outline: 1px solid #3e5e85 !important;
    }

    ${selector} h4 {
      outline: 1px solid #449baf !important;
    }

    ${selector} h5 {
      outline: 1px solid #c7d1cb !important;
    }

    ${selector} h6 {
      outline: 1px solid #4371d0 !important;
    }

    ${selector} main {
      outline: 1px solid #2f4f90 !important;
    }

    ${selector} address {
      outline: 1px solid #1a2c51 !important;
    }

    ${selector} div {
      outline: 1px solid #036cdb !important;
    }

    ${selector} p {
      outline: 1px solid #ac050b !important;
    }

    ${selector} hr {
      outline: 1px solid #ff063f !important;
    }

    ${selector} pre {
      outline: 1px solid #850440 !important;
    }

    ${selector} blockquote {
      outline: 1px solid #f1b8e7 !important;
    }

    ${selector} ol {
      outline: 1px solid #ff050c !important;
    }

    ${selector} ul {
      outline: 1px solid #d90416 !important;
    }

    ${selector} li {
      outline: 1px solid #d90416 !important;
    }

    ${selector} dl {
      outline: 1px solid #fd3427 !important;
    }

    ${selector} dt {
      outline: 1px solid #ff0043 !important;
    }

    ${selector} dd {
      outline: 1px solid #e80174 !important;
    }

    ${selector} figure {
      outline: 1px solid #ff00bb !important;
    }

    ${selector} figcaption {
      outline: 1px solid #bf0032 !important;
    }

    ${selector} table {
      outline: 1px solid #00cc99 !important;
    }

    ${selector} caption {
      outline: 1px solid #37ffc4 !important;
    }

    ${selector} thead {
      outline: 1px solid #98daca !important;
    }

    ${selector} tbody {
      outline: 1px solid #64a7a0 !important;
    }

    ${selector} tfoot {
      outline: 1px solid #22746b !important;
    }

    ${selector} tr {
      outline: 1px solid #86c0b2 !important;
    }

    ${selector} th {
      outline: 1px solid #a1e7d6 !important;
    }

    ${selector} td {
      outline: 1px solid #3f5a54 !important;
    }

    ${selector} col {
      outline: 1px solid #6c9a8f !important;
    }

    ${selector} colgroup {
      outline: 1px solid #6c9a9d !important;
    }

    ${selector} button {
      outline: 1px solid #da8301 !important;
    }

    ${selector} datalist {
      outline: 1px solid #c06000 !important;
    }

    ${selector} fieldset {
      outline: 1px solid #d95100 !important;
    }

    ${selector} form {
      outline: 1px solid #d23600 !important;
    }

    ${selector} input {
      outline: 1px solid #fca600 !important;
    }

    ${selector} keygen {
      outline: 1px solid #b31e00 !important;
    }

    ${selector} label {
      outline: 1px solid #ee8900 !important;
    }

    ${selector} legend {
      outline: 1px solid #de6d00 !important;
    }

    ${selector} meter {
      outline: 1px solid #e8630c !important;
    }

    ${selector} optgroup {
      outline: 1px solid #b33600 !important;
    }

    ${selector} option {
      outline: 1px solid #ff8a00 !important;
    }

    ${selector} output {
      outline: 1px solid #ff9619 !important;
    }

    ${selector} progress {
      outline: 1px solid #e57c00 !important;
    }

    ${selector} select {
      outline: 1px solid #e26e0f !important;
    }

    ${selector} textarea {
      outline: 1px solid #cc5400 !important;
    }

    ${selector} details {
      outline: 1px solid #33848f !important;
    }

    ${selector} summary {
      outline: 1px solid #60a1a6 !important;
    }

    ${selector} command {
      outline: 1px solid #438da1 !important;
    }

    ${selector} menu {
      outline: 1px solid #449da6 !important;
    }

    ${selector} del {
      outline: 1px solid #bf0000 !important;
    }

    ${selector} ins {
      outline: 1px solid #400000 !important;
    }

    ${selector} img {
      outline: 1px solid #22746b !important;
    }

    ${selector} iframe {
      outline: 1px solid #64a7a0 !important;
    }

    ${selector} embed {
      outline: 1px solid #98daca !important;
    }

    ${selector} object {
      outline: 1px solid #00cc99 !important;
    }

    ${selector} param {
      outline: 1px solid #37ffc4 !important;
    }

    ${selector} video {
      outline: 1px solid #6ee866 !important;
    }

    ${selector} audio {
      outline: 1px solid #027353 !important;
    }

    ${selector} source {
      outline: 1px solid #012426 !important;
    }

    ${selector} canvas {
      outline: 1px solid #a2f570 !important;
    }

    ${selector} track {
      outline: 1px solid #59a600 !important;
    }

    ${selector} map {
      outline: 1px solid #7be500 !important;
    }

    ${selector} area {
      outline: 1px solid #305900 !important;
    }

    ${selector} a {
      outline: 1px solid #ff62ab !important;
    }

    ${selector} em {
      outline: 1px solid #800b41 !important;
    }

    ${selector} strong {
      outline: 1px solid #ff1583 !important;
    }

    ${selector} i {
      outline: 1px solid #803156 !important;
    }

    ${selector} b {
      outline: 1px solid #cc1169 !important;
    }

    ${selector} u {
      outline: 1px solid #ff0430 !important;
    }

    ${selector} s {
      outline: 1px solid #f805e3 !important;
    }

    ${selector} small {
      outline: 1px solid #d107b2 !important;
    }

    ${selector} abbr {
      outline: 1px solid #4a0263 !important;
    }

    ${selector} q {
      outline: 1px solid #240018 !important;
    }

    ${selector} cite {
      outline: 1px solid #64003c !important;
    }

    ${selector} dfn {
      outline: 1px solid #b4005a !important;
    }

    ${selector} sub {
      outline: 1px solid #dba0c8 !important;
    }

    ${selector} sup {
      outline: 1px solid #cc0256 !important;
    }

    ${selector} time {
      outline: 1px solid #d6606d !important;
    }

    ${selector} code {
      outline: 1px solid #e04251 !important;
    }

    ${selector} kbd {
      outline: 1px solid #5e001f !important;
    }

    ${selector} samp {
      outline: 1px solid #9c0033 !important;
    }

    ${selector} var {
      outline: 1px solid #d90047 !important;
    }

    ${selector} mark {
      outline: 1px solid #ff0053 !important;
    }

    ${selector} bdi {
      outline: 1px solid #bf3668 !important;
    }

    ${selector} bdo {
      outline: 1px solid #6f1400 !important;
    }

    ${selector} ruby {
      outline: 1px solid #ff7b93 !important;
    }

    ${selector} rt {
      outline: 1px solid #ff2f54 !important;
    }

    ${selector} rp {
      outline: 1px solid #803e49 !important;
    }

    ${selector} span {
      outline: 1px solid #cc2643 !important;
    }

    ${selector} br {
      outline: 1px solid #db687d !important;
    }

    ${selector} wbr {
      outline: 1px solid #db175b !important;
    }`;
}

// src/outline/withOutline.ts
var withOutline = (StoryFn, context) => {
  let globals = context.globals || {}, isActive = !context.parameters?.[PARAM_KEY2]?.disable && [!0, "true"].includes(globals[PARAM_KEY2]), isInDocs = context.viewMode === "docs", outlineStyles = (0,external_STORYBOOK_MODULE_PREVIEW_API_.useMemo)(() => outlineCSS(isInDocs ? '[data-story-block="true"]' : ".sb-show-main"), [context]);
  return (0,external_STORYBOOK_MODULE_PREVIEW_API_.useEffect)(() => {
    let selectorId = isInDocs ? `addon-outline-docs-${context.id}` : "addon-outline";
    return isActive ? addOutlineStyles(selectorId, outlineStyles) : clearStyles2(selectorId), () => {
      clearStyles2(selectorId);
    };
  }, [isActive, outlineStyles, context]), StoryFn();
};

// src/outline/preview.ts
var decorators3 = globalThis.FEATURES?.outline ? [withOutline] : [], initialGlobals3 = {
  [PARAM_KEY2]: !1
}, preview_default6 = () => definePreviewAddon7({ decorators: decorators3, initialGlobals: initialGlobals3 });

// src/shared/open-service/services/docgen/preview.ts

var preview_default7 = () => definePreviewAddon8({
  beforeAll: () => {
    globalThis.FEATURES?.experimentalDocgenServer && registerService(docgenServiceDef);
  }
});

// src/shared/open-service/services/story-docs/preview.ts


// src/shared/open-service/services/story-docs/definition.ts


// src/shared/open-service/services/story-docs/paths.ts
function storyDocsQueryStaticPath(id) {
  return `${id}.json`;
}

// src/shared/open-service/services/story-docs/definition.ts
var storyDocsInputSchema = object({ id: string() }), storyDocsErrorSchema = object({
  name: string(),
  message: string()
}), storyDocSchema = object({
  id: string(),
  name: string(),
  snippet: optional(string()),
  description: optional(string()),
  summary: optional(string()),
  error: optional(storyDocsErrorSchema)
}), storyDocsPayloadSchema = object({
  id: string(),
  name: string(),
  path: string(),
  import: optional(string()),
  stories: record(string(), storyDocSchema),
  error: optional(storyDocsErrorSchema)
}), storyDocsOutputSchema = optional(storyDocsPayloadSchema), storyDocsServiceDef = defineService({
  id: "core/story-docs",
  description: "Story documentation (snippets, descriptions, imports) keyed by component id.",
  initialState: { components: {} },
  queries: {
    storyDocs: {
      description: "Returns the story-docs payload for one component id, or undefined when not loaded.",
      input: storyDocsInputSchema,
      output: storyDocsOutputSchema,
      handler: (input, ctx) => Object.hasOwn(ctx.self.state.components, input.id) ? ctx.self.state.components[input.id] : void 0,
      load: async (input, ctx) => {
        await ctx.self.commands.extractStoryDocs(input);
      },
      staticPath: (input) => storyDocsQueryStaticPath(input.id)
    },
    storyDocsForAllComponents: {
      description: "Returns story-docs payloads for every component in the story index.",
      input: void_(),
      output: record(string(), storyDocsPayloadSchema),
      handler: (_input, ctx) => ctx.self.state.components,
      load: async (_input, ctx) => {
        await ctx.self.commands.extractAllStoryDocs(void 0);
      }
    }
  },
  commands: {
    extractStoryDocs: {
      description: "Resolves the story entry for a component id, runs the registered provider chain, writes the result into state, and returns it (or undefined when no provider produced story docs).",
      input: storyDocsInputSchema,
      output: storyDocsOutputSchema
    },
    extractAllStoryDocs: {
      description: "Extracts story docs for every component id in the story index by invoking `extractStoryDocs` for each.",
      input: undefined_(),
      output: void_()
    }
  }
});

// src/shared/open-service/services/story-docs/story-docs-source-before-each.ts

function storyDocsSourceBeforeEach(context) {
  if (!globalThis.FEATURES?.experimentalDocgenServer || shouldSkipStoryDocsEmit(context.parameters))
    return;
  let service = (() => {
    try {
      return getService("core/story-docs");
    } catch {
      return;
    }
  })();
  if (!service)
    return;
  let storyId = context.id, componentId = storyId.split("--")[0], cancelled = !1, codePanelSnippetPromise = service.queries.storyDocs.loaded({ id: componentId }).then((payload) => {
    if (cancelled)
      return;
    let source = selectSnippetForStory(payload, storyId) ?? context.parameters?.docs?.source?.originalSource;
    if (source !== void 0)
      return emitTransformCode(source, context);
  });
  return () => (cancelled = !0, codePanelSnippetPromise);
}

// src/shared/open-service/services/story-docs/preview.ts
var preview_default8 = () => "FEATURES" in globalThis && globalThis.FEATURES?.experimentalDocgenServer ? definePreviewAddon9({
  beforeAll: () => {
    registerService(storyDocsServiceDef);
  },
  beforeEach: storyDocsSourceBeforeEach
}) : definePreviewAddon9({});

// src/test/preview.ts



var resetAllMocksLoader = ({ parameters: parameters2 }) => {
  parameters2?.test?.mockReset === !0 ? resetAllMocks() : parameters2?.test?.clearMocks === !0 ? clearAllMocks() : parameters2?.test?.restoreMocks !== !1 && restoreAllMocks();
}, traverseArgs = (value, depth = 0, key) => {
  if (depth > 5 || value == null)
    return value;
  if (isMockFunction(value))
    return key && value.mockName(key), value;
  if (typeof value == "function" && "isAction" in value && value.isAction && !("implicit" in value && value.implicit)) {
    let mock = fn(value);
    return key && mock.mockName(key), mock;
  }
  if (Array.isArray(value)) {
    depth++;
    for (let i = 0; i < value.length; i++)
      Object.getOwnPropertyDescriptor(value, i)?.writable && (value[i] = traverseArgs(value[i], depth));
    return value;
  }
  if (typeof value == "object" && value.constructor === Object) {
    depth++;
    for (let [k, v] of Object.entries(value))
      Object.getOwnPropertyDescriptor(value, k)?.writable && (value[k] = traverseArgs(v, depth, k));
    return value;
  }
  return value;
}, nameSpiesAndWrapActionsInSpies = ({ initialArgs }) => {
  traverseArgs(initialArgs);
}, patchedFocus = (/* unused pure expression or super */ null && (!1)), enhanceContext = async (context) => {
  globalThis.HTMLElement && context.canvasElement instanceof globalThis.HTMLElement && (context.canvas = within(context.canvasElement));
  try {
    let clipboard = globalThis.window?.navigator?.clipboard;
    if (clipboard && (context.userEvent = instrument2(
      { userEvent: uninstrumentedUserEvent.setup() },
      {
        intercept: !0,
        getKeys: (obj) => Object.keys(obj).filter((key) => key !== "eventWrapper")
      }
    ).userEvent, Object.defineProperty(globalThis.window.navigator, "clipboard", {
      get: () => clipboard,
      configurable: !0
    }), !patchedFocus)) {
      let originalFocus = HTMLElement.prototype.focus, currentFocus = HTMLElement.prototype.focus, noopFocus = () => {
      }, focusingElements = /* @__PURE__ */ new Set();
      Object.defineProperties(HTMLElement.prototype, {
        focus: {
          configurable: !0,
          set: (newFocus) => {
            currentFocus = newFocus;
          },
          get() {
            if (this === HTMLElement.prototype)
              return currentFocus;
            let browsingContext;
            try {
              browsingContext = this.ownerDocument?.defaultView;
            } catch {
              return currentFocus;
            }
            return browsingContext ? focusingElements.has(this) ? originalFocus : (focusingElements.add(this), setTimeout(() => focusingElements.delete(this), 0), currentFocus) : noopFocus;
          }
        }
      }), patchedFocus = !0;
    }
  } catch {
  }
}, preview_default9 = () => definePreviewAddon10({
  loaders: [resetAllMocksLoader, nameSpiesAndWrapActionsInSpies, enhanceContext]
});

// src/viewport/preview.ts

var initialGlobals4 = {
  [chunk_NQ3W4AUJ_PARAM_KEY]: { value: void 0, isRotated: !1 }
}, preview_default10 = () => definePreviewAddon11({
  initialGlobals: initialGlobals4
});

// src/csf/core-annotations.ts
var CORE_ANNOTATIONS_COMPOSED = /* @__PURE__ */ (/* unused pure expression or super */ null && (Symbol.for("storybook.internal.composedWithCoreAnnotations")));
function markAsComposedWithCoreAnnotations(annotations) {
  return Object.defineProperty(annotations, CORE_ANNOTATIONS_COMPOSED, {
    value: !0,
    enumerable: !1,
    configurable: !0,
    writable: !0
  }), annotations;
}
function hasCoreAnnotations(annotations) {
  return annotations != null && typeof annotations == "object" && annotations[CORE_ANNOTATIONS_COMPOSED] === !0;
}
function getCoreAnnotations() {
  return [
    // @ts-expect-error CJS fallback
    (preview_default5.default ?? preview_default5)(),
    // @ts-expect-error CJS fallback
    (preview_default2.default ?? preview_default2)(),
    // @ts-expect-error CJS fallback
    (preview_default4.default ?? preview_default4)(),
    // @ts-expect-error CJS fallback
    (preview_default6.default ?? preview_default6)(),
    // @ts-expect-error CJS fallback
    (preview_default10.default ?? preview_default10)(),
    // @ts-expect-error CJS fallback
    (preview_default.default ?? preview_default)(),
    // @ts-expect-error CJS fallback
    (preview_default3.default ?? preview_default3)(),
    // @ts-expect-error CJS fallback
    (preview_default9.default ?? preview_default9)(),
    // @ts-expect-error CJS fallback
    (test_annotations_default.default ?? test_annotations_default)(),
    // @ts-expect-error CJS fallback
    (preview_default7.default ?? preview_default7)(),
    // @ts-expect-error CJS fallback
    (preview_default8.default ?? preview_default8)()
  ];
}

// src/csf/csf-factories.ts
function definePreview(input) {
  let composed, preview = {
    _tag: "Preview",
    input,
    get composed() {
      if (composed)
        return composed;
      let { addons: addons2, ...rest } = input;
      return composed = markAsComposedWithCoreAnnotations(
        normalizeProjectAnnotations(
          composeConfigs([...getCoreAnnotations(), ...addons2 ?? [], rest])
        )
      ), composed;
    },
    type() {
      return this;
    },
    meta(meta) {
      return defineMeta(meta, this);
    }
  };
  return globalThis.globalProjectAnnotations = preview.composed, preview;
}
function definePreviewAddon12(preview) {
  return preview;
}
function isPreview(input) {
  return input != null && typeof input == "object" && "_tag" in input && input?._tag === "Preview";
}
function isMeta(input) {
  return input != null && typeof input == "object" && "_tag" in input && input?._tag === "Meta";
}
function defineMeta(input, preview) {
  return {
    _tag: "Meta",
    input: { ...input, parameters: { ...input.parameters, csfFactory: !0 } },
    preview,
    // @ts-expect-error hard
    story(story = {}) {
      return defineStory(typeof story == "function" ? { render: story } : story, this);
    }
  };
}
function isStory(input) {
  return input != null && typeof input == "object" && "_tag" in input && input?._tag === "Story";
}
function defineStory(input, meta) {
  let composed, compose = () => (composed || (composed = composeStory(
    input,
    meta.input,
    void 0,
    meta.preview.composed
  )), composed), __children = [];
  return {
    _tag: "Story",
    input,
    meta,
    // @ts-expect-error this is a private property used only once in renderers/react/src/preview
    __compose: compose,
    __children,
    get composed() {
      let composed2 = compose(), { args, argTypes, parameters: parameters2, id, tags, globals, storyName: name } = composed2;
      return { args, argTypes, parameters: parameters2, id, tags, name, globals };
    },
    get play() {
      return input.play ?? meta.input?.play ?? (async () => {
      });
    },
    async run(context) {
      await compose().run(context);
    },
    test(name, overridesOrTestFn, testFn) {
      let annotations = typeof overridesOrTestFn != "function" ? overridesOrTestFn : {}, testFunction = typeof overridesOrTestFn != "function" ? testFn : overridesOrTestFn, play = mountDestructured(this.play) || mountDestructured(testFunction) ? (
        // mount needs to be explicitly destructured
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        async ({ mount, context }) => {
          await this.play?.(context), await testFunction(context);
        }
      ) : async (context) => {
        await this.play?.(context), await testFunction(context);
      }, test = this.extend({
        ...annotations,
        name,
        tags: [Tag.TEST_FN, `!${Tag.AUTODOCS}`, ...annotations.tags ?? []],
        play
      });
      return __children.push(test), test;
    },
    extend(input2) {
      return defineStory(
        {
          ...this.input,
          ...input2,
          args: { ...this.input.args || {}, ...input2.args },
          argTypes: combineParameters(this.input.argTypes, input2.argTypes),
          afterEach: [
            ...normalizeArrays(this.input?.afterEach ?? []),
            ...normalizeArrays(input2.afterEach ?? [])
          ],
          beforeEach: [
            ...normalizeArrays(this.input?.beforeEach ?? []),
            ...normalizeArrays(input2.beforeEach ?? [])
          ],
          decorators: [
            ...normalizeArrays(this.input?.decorators ?? []),
            ...normalizeArrays(input2.decorators ?? [])
          ],
          globals: { ...this.input.globals, ...input2.globals },
          loaders: [
            ...normalizeArrays(this.input?.loaders ?? []),
            ...normalizeArrays(input2.loaders ?? [])
          ],
          parameters: combineParameters(this.input.parameters, input2.parameters),
          tags: combineTags(...this.input.tags ?? [], ...input2.tags ?? [])
        },
        this.meta
      );
    }
  };
}
function getStoryChildren(story) {
  return "__children" in story ? story.__children : [];
}

// src/csf/index.ts
var sanitize = (string2) => string2.toLowerCase().replace(/[ ’–—―′¿'`~!@#$%^&*()_|+\-=?;:'",.<>\{\}\[\]\\\/]/gi, "-").replace(/-+/g, "-").replace(/^-+/, "").replace(/-+$/, ""), sanitizeSafe = (string2, part) => {
  let sanitized = sanitize(string2);
  if (sanitized === "")
    throw new Error(`Invalid ${part} '${string2}', must include alphanumeric characters`);
  return sanitized;
}, toId = (kind, name) => `${sanitizeSafe(kind, "kind")}${name ? `--${sanitizeSafe(name, "name")}` : ""}`, toTestId = (parentId, testName) => `${parentId}:${sanitizeSafe(testName, "test")}`, parseKind = (kind, { rootSeparator, groupSeparator }) => {
  let [root, remainder] = kind.split(rootSeparator, 2), groups = (remainder || kind).split(groupSeparator).filter((i) => !!i);
  return {
    root: remainder ? root : null,
    groups
  };
}, combineTags2 = (...tags) => {
  let result = tags.reduce((acc, tag) => (tag.startsWith("!") ? acc.delete(tag.slice(1)) : acc.add(tag), acc), /* @__PURE__ */ new Set());
  return Array.from(result);
};



/***/ })

}]);