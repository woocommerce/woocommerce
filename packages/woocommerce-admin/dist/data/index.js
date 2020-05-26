this["wc"] = this["wc"] || {}; this["wc"]["data"] =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 705);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ }),

/***/ 13:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _defineProperty; });
function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

/***/ }),

/***/ 17:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ _toConsumableArray; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js
var arrayLikeToArray = __webpack_require__(37);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return Object(arrayLikeToArray["a" /* default */])(arr);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/iterableToArray.js
function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js
var unsupportedIterableToArray = __webpack_require__(52);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js
function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js




function _toConsumableArray(arr) {
  return _arrayWithoutHoles(arr) || _iterableToArray(arr) || Object(unsupportedIterableToArray["a" /* default */])(arr) || _nonIterableSpread();
}

/***/ }),

/***/ 19:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = this["lodash"]; }());

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ }),

/***/ 30:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["url"]; }());

/***/ }),

/***/ 37:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _arrayLikeToArray; });
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

/***/ }),

/***/ 52:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _unsupportedIterableToArray; });
/* harmony import */ var _arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(37);

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return Object(_arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(n);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return Object(_arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(o, minLen);
}

/***/ }),

/***/ 57:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["dataControls"]; }());

/***/ }),

/***/ 705:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "SETTINGS_STORE_NAME", function() { return /* reexport */ SETTINGS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "withSettingsHydration", function() { return /* reexport */ with_settings_hydration_withSettingsHydration; });
__webpack_require__.d(__webpack_exports__, "useSettings", function() { return /* reexport */ use_settings_useSettings; });
__webpack_require__.d(__webpack_exports__, "PLUGINS_STORE_NAME", function() { return /* reexport */ PLUGINS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "withPluginsHydration", function() { return /* reexport */ with_plugins_hydration_withPluginsHydration; });

// NAMESPACE OBJECT: ./packages/data/build-module/settings/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, "getSettingsGroupNames", function() { return selectors_getSettingsGroupNames; });
__webpack_require__.d(selectors_namespaceObject, "getSettings", function() { return selectors_getSettings; });
__webpack_require__.d(selectors_namespaceObject, "getDirtyKeys", function() { return getDirtyKeys; });
__webpack_require__.d(selectors_namespaceObject, "getIsDirty", function() { return selectors_getIsDirty; });
__webpack_require__.d(selectors_namespaceObject, "getSettingsForGroup", function() { return selectors_getSettingsForGroup; });
__webpack_require__.d(selectors_namespaceObject, "isGetSettingsRequesting", function() { return selectors_isGetSettingsRequesting; });
__webpack_require__.d(selectors_namespaceObject, "getSetting", function() { return getSetting; });
__webpack_require__.d(selectors_namespaceObject, "getLastSettingsErrorForGroup", function() { return selectors_getLastSettingsErrorForGroup; });
__webpack_require__.d(selectors_namespaceObject, "getSettingsError", function() { return selectors_getSettingsError; });

// NAMESPACE OBJECT: ./packages/data/build-module/settings/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, "updateSettingsForGroup", function() { return actions_updateSettingsForGroup; });
__webpack_require__.d(actions_namespaceObject, "updateErrorForGroup", function() { return updateErrorForGroup; });
__webpack_require__.d(actions_namespaceObject, "setIsRequesting", function() { return setIsRequesting; });
__webpack_require__.d(actions_namespaceObject, "clearIsDirty", function() { return actions_clearIsDirty; });
__webpack_require__.d(actions_namespaceObject, "updateAndPersistSettingsForGroup", function() { return actions_updateAndPersistSettingsForGroup; });
__webpack_require__.d(actions_namespaceObject, "persistSettingsForGroup", function() { return actions_persistSettingsForGroup; });
__webpack_require__.d(actions_namespaceObject, "clearSettings", function() { return clearSettings; });

// NAMESPACE OBJECT: ./packages/data/build-module/settings/resolvers.js
var resolvers_namespaceObject = {};
__webpack_require__.r(resolvers_namespaceObject);
__webpack_require__.d(resolvers_namespaceObject, "getSettings", function() { return resolvers_getSettings; });
__webpack_require__.d(resolvers_namespaceObject, "getSettingsForGroup", function() { return resolvers_getSettingsForGroup; });

// NAMESPACE OBJECT: ./packages/data/build-module/plugins/selectors.js
var plugins_selectors_namespaceObject = {};
__webpack_require__.r(plugins_selectors_namespaceObject);
__webpack_require__.d(plugins_selectors_namespaceObject, "getActivePlugins", function() { return getActivePlugins; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getInstalledPlugins", function() { return getInstalledPlugins; });
__webpack_require__.d(plugins_selectors_namespaceObject, "isPluginsRequesting", function() { return isPluginsRequesting; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getPluginsError", function() { return getPluginsError; });
__webpack_require__.d(plugins_selectors_namespaceObject, "isJetpackConnected", function() { return isJetpackConnected; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getJetpackConnectUrl", function() { return getJetpackConnectUrl; });

// NAMESPACE OBJECT: ./packages/data/build-module/plugins/actions.js
var plugins_actions_namespaceObject = {};
__webpack_require__.r(plugins_actions_namespaceObject);
__webpack_require__.d(plugins_actions_namespaceObject, "updateActivePlugins", function() { return actions_updateActivePlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "updateInstalledPlugins", function() { return actions_updateInstalledPlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "setIsRequesting", function() { return actions_setIsRequesting; });
__webpack_require__.d(plugins_actions_namespaceObject, "setError", function() { return setError; });
__webpack_require__.d(plugins_actions_namespaceObject, "updateIsJetpackConnected", function() { return actions_updateIsJetpackConnected; });
__webpack_require__.d(plugins_actions_namespaceObject, "updateJetpackConnectUrl", function() { return updateJetpackConnectUrl; });
__webpack_require__.d(plugins_actions_namespaceObject, "installPlugin", function() { return installPlugin; });
__webpack_require__.d(plugins_actions_namespaceObject, "activatePlugins", function() { return activatePlugins; });

// NAMESPACE OBJECT: ./packages/data/build-module/plugins/resolvers.js
var plugins_resolvers_namespaceObject = {};
__webpack_require__.r(plugins_resolvers_namespaceObject);
__webpack_require__.d(plugins_resolvers_namespaceObject, "getActivePlugins", function() { return resolvers_getActivePlugins; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "getInstalledPlugins", function() { return resolvers_getInstalledPlugins; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "isJetpackConnected", function() { return resolvers_isJetpackConnected; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "getJetpackConnectUrl", function() { return resolvers_getJetpackConnectUrl; });

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: external {"this":["wp","dataControls"]}
var external_this_wp_dataControls_ = __webpack_require__(57);

// CONCATENATED MODULE: ./packages/data/build-module/settings/constants.js
var STORE_NAME = 'wc/admin/settings';
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(17);

// CONCATENATED MODULE: ./packages/data/build-module/utils.js
function getResourceName(prefix, identifier) {
  var identifierString = JSON.stringify(identifier, Object.keys(identifier).sort());
  return "".concat(prefix, ":").concat(identifierString);
}
function getResourcePrefix(resourceName) {
  var hasPrefixIndex = resourceName.indexOf(':');
  return hasPrefixIndex < 0 ? resourceName : resourceName.substring(0, hasPrefixIndex);
}
function isResourcePrefix(resourceName, prefix) {
  var resourcePrefix = getResourcePrefix(resourceName);
  return resourcePrefix === prefix;
}
function getResourceIdentifier(resourceName) {
  var identifierString = resourceName.substring(resourceName.indexOf(':') + 1);
  return JSON.parse(identifierString);
}
// CONCATENATED MODULE: ./packages/data/build-module/settings/selectors.js

/**
 * Internal dependencies
 */


var selectors_getSettingsGroupNames = function getSettingsGroupNames(state) {
  var groupNames = new Set(Object.keys(state).map(function (resourceName) {
    return getResourcePrefix(resourceName);
  }));
  return Object(toConsumableArray["a" /* default */])(groupNames);
};
var selectors_getSettings = function getSettings(state, group) {
  var settings = {};
  var settingIds = state[group] && state[group].data || [];

  if (settingIds.length === 0) {
    return settings;
  }

  settingIds.forEach(function (id) {
    settings[id] = state[getResourceName(group, id)].data;
  });
  return settings;
};
var getDirtyKeys = function getDirtyKeys(state, group) {
  return state[group].dirty || [];
};
var selectors_getIsDirty = function getIsDirty(state, group) {
  var keys = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  var dirtyMap = getDirtyKeys(state, group); // if empty array bail

  if (dirtyMap.length === 0) {
    return false;
  } // if at least one of the keys is in the dirty map then the state is dirty
  // meaning it hasn't been persisted.


  return keys.some(function (key) {
    return dirtyMap.includes(key);
  });
};
var selectors_getSettingsForGroup = function getSettingsForGroup(state, group, keys) {
  var allSettings = selectors_getSettings(state, group);
  return keys.reduce(function (accumulator, key) {
    accumulator[key] = allSettings[key] || {};
    return accumulator;
  }, {});
};
var selectors_isGetSettingsRequesting = function isGetSettingsRequesting(state, group) {
  return state[group] && Boolean(state[group].isRequesting);
};
/**
 * Retrieves a setting value from the setting store.
 *
 * @export
 * @param {Object}   state                        State param added by wp.data.
 * @param {string}   group                        The settings group.
 * @param {string}   name                         The identifier for the setting.
 * @param {*}    [fallback=false]             The value to use as a fallback
 *                                                if the setting is not in the
 *                                                state.
 * @param {Function} [filter=( val ) => val]  	  A callback for filtering the
 *                                                value before it's returned.
 *                                                Receives both the found value
 *                                                (if it exists for the key) and
 *                                                the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */

function getSetting(state, group, name) {
  var fallback = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  var filter = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : function (val) {
    return val;
  };
  var resourceName = getResourceName(group, name);
  var value = state[resourceName] && state[resourceName].data || fallback;
  return filter(value, fallback);
}
var selectors_getLastSettingsErrorForGroup = function getLastSettingsErrorForGroup(state, group) {
  var settingsIds = state[group].data;

  if (settingsIds.length === 0) {
    return state[group].error;
  }

  return Object(toConsumableArray["a" /* default */])(settingsIds).pop().error;
};
var selectors_getSettingsError = function getSettingsError(state, group, id) {
  if (!id) {
    return state[group] && state[group].error || false;
  }

  return state[getResourceName(group, id)].error || false;
};
// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./packages/data/build-module/constants.js
var JETPACK_NAMESPACE = '/jetpack/v4';
var NAMESPACE = '/wc-analytics';
var WC_ADMIN_NAMESPACE = '/wc-admin';
var WCS_NAMESPACE = '/wc/v1'; // WCS endpoints like Stripe are not avaiable on later /wc versions
// WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter

var MAX_PER_PAGE = 100;
var SECOND = 1000;
var MINUTE = 60 * SECOND;
var HOUR = 60 * MINUTE;
var DEFAULT_REQUIREMENT = {
  timeout: 1 * MINUTE,
  freshness: 30 * MINUTE
};
var DEFAULT_ACTIONABLE_STATUSES = ['processing', 'on-hold'];
var QUERY_DEFAULTS = {
  pageSize: 25,
  period: 'month',
  compare: 'previous_year'
};
// CONCATENATED MODULE: ./packages/data/build-module/settings/action-types.js
var TYPES = {
  UPDATE_SETTINGS_FOR_GROUP: 'UPDATE_SETTINGS_FOR_GROUP',
  UPDATE_ERROR_FOR_GROUP: 'UPDATE_ERROR_FOR_GROUP',
  CLEAR_SETTINGS: 'CLEAR_SETTINGS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  CLEAR_IS_DIRTY: 'CLEAR_IS_DIRTY'
};
/* harmony default export */ var action_types = (TYPES);
// CONCATENATED MODULE: ./packages/data/build-module/settings/actions.js
var _marked = /*#__PURE__*/regeneratorRuntime.mark(actions_updateAndPersistSettingsForGroup),
    _marked2 = /*#__PURE__*/regeneratorRuntime.mark(actions_persistSettingsForGroup);
/**
 * External Dependencies
 */




/**
 * Internal Dependencies
 */




function actions_updateSettingsForGroup(group, data) {
  var time = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : new Date();
  return {
    type: action_types.UPDATE_SETTINGS_FOR_GROUP,
    group: group,
    data: data,
    time: time
  };
}
function updateErrorForGroup(group, data, error) {
  var time = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : new Date();
  return {
    type: action_types.UPDATE_ERROR_FOR_GROUP,
    group: group,
    data: data,
    error: error,
    time: time
  };
}
function setIsRequesting(group, isRequesting) {
  return {
    type: action_types.SET_IS_REQUESTING,
    group: group,
    isRequesting: isRequesting
  };
}
function actions_clearIsDirty(group) {
  return {
    type: action_types.CLEAR_IS_DIRTY,
    group: group
  };
} // allows updating and persisting immediately in one action.

function actions_updateAndPersistSettingsForGroup(group, data) {
  return regeneratorRuntime.wrap(function updateAndPersistSettingsForGroup$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return actions_updateSettingsForGroup(group, data);

        case 2:
          return _context.delegateYield(actions_persistSettingsForGroup(group), "t0", 3);

        case 3:
        case "end":
          return _context.stop();
      }
    }
  }, _marked);
} // this would replace setSettingsForGroup

function actions_persistSettingsForGroup(group) {
  var dirtyKeys, dirtyData, url, update, results;
  return regeneratorRuntime.wrap(function persistSettingsForGroup$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return setIsRequesting(group, true);

        case 2:
          _context2.next = 4;
          return Object(external_this_wp_dataControls_["select"])(STORE_NAME, 'getDirtyKeys', group);

        case 4:
          dirtyKeys = _context2.sent;

          if (!(dirtyKeys.length === 0)) {
            _context2.next = 9;
            break;
          }

          _context2.next = 8;
          return setIsRequesting(group, false);

        case 8:
          return _context2.abrupt("return");

        case 9:
          _context2.next = 11;
          return Object(external_this_wp_dataControls_["select"])(STORE_NAME, 'getSettingsForGroup', group, dirtyKeys);

        case 11:
          dirtyData = _context2.sent;
          url = "".concat(NAMESPACE, "/settings/").concat(group, "/batch");
          update = dirtyKeys.reduce(function (updates, key) {
            var u = Object.keys(dirtyData[key]).map(function (k) {
              return {
                id: k,
                value: dirtyData[key][k]
              };
            });
            return Object(external_lodash_["concat"])(updates, u);
          }, []);
          _context2.prev = 14;
          _context2.next = 17;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: url,
            method: 'POST',
            data: {
              update: update
            }
          });

        case 17:
          results = _context2.sent;

          if (results) {
            _context2.next = 20;
            break;
          }

          throw new Error('settings did not update');

        case 20:
          _context2.next = 22;
          return actions_clearIsDirty(group);

        case 22:
          _context2.next = 28;
          break;

        case 24:
          _context2.prev = 24;
          _context2.t0 = _context2["catch"](14);
          _context2.next = 28;
          return updateErrorForGroup(group, null, _context2.t0);

        case 28:
          _context2.next = 30;
          return setIsRequesting(group, false);

        case 30:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2, null, [[14, 24]]);
}
function clearSettings() {
  return {
    type: action_types.CLEAR_SETTINGS
  };
}
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(13);

// CONCATENATED MODULE: ./packages/data/build-module/settings/resolvers.js


var resolvers_marked = /*#__PURE__*/regeneratorRuntime.mark(resolvers_getSettings),
    resolvers_marked2 = /*#__PURE__*/regeneratorRuntime.mark(resolvers_getSettingsForGroup);
/**
 * External Dependencies
 */



/**
 * Internal dependencies
 */





function settingsToSettingsResource(settings) {
  return settings.reduce(function (resource, setting) {
    resource[setting.id] = setting.value;
    return resource;
  }, {});
}

function resolvers_getSettings(group) {
  var url, results, resource;
  return regeneratorRuntime.wrap(function getSettings$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return Object(external_this_wp_dataControls_["dispatch"])(STORE_NAME, 'setIsRequesting', group, true);

        case 2:
          _context.prev = 2;
          url = NAMESPACE + '/settings/' + group;
          _context.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context.sent;
          resource = settingsToSettingsResource(results);
          return _context.abrupt("return", actions_updateSettingsForGroup(group, Object(defineProperty["a" /* default */])({}, group, resource)));

        case 11:
          _context.prev = 11;
          _context.t0 = _context["catch"](2);
          return _context.abrupt("return", updateErrorForGroup(group, null, _context.t0.message));

        case 14:
        case "end":
          return _context.stop();
      }
    }
  }, resolvers_marked, null, [[2, 11]]);
}
function resolvers_getSettingsForGroup(group) {
  return regeneratorRuntime.wrap(function getSettingsForGroup$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          return _context2.abrupt("return", resolvers_getSettings(group));

        case 1:
        case "end":
          return _context2.stop();
      }
    }
  }, resolvers_marked2);
}
// CONCATENATED MODULE: ./packages/data/build-module/settings/reducer.js



function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }

  return keys;
}

function _objectSpread(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(Object(source), true).forEach(function (key) {
        Object(defineProperty["a" /* default */])(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }

  return target;
}
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




var reducer_updateGroupDataInNewState = function updateGroupDataInNewState(newState, _ref) {
  var group = _ref.group,
      groupIds = _ref.groupIds,
      data = _ref.data,
      time = _ref.time,
      error = _ref.error;
  groupIds.forEach(function (id) {
    newState[getResourceName(group, id)] = {
      data: data[id],
      lastReceived: time,
      error: error
    };
  });
  return newState;
};

var reducer_receiveSettings = function receiveSettings() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

  var _ref2 = arguments.length > 1 ? arguments[1] : undefined,
      type = _ref2.type,
      group = _ref2.group,
      data = _ref2.data,
      error = _ref2.error,
      time = _ref2.time,
      isRequesting = _ref2.isRequesting;

  var newState = {};

  switch (type) {
    case action_types.SET_IS_REQUESTING:
      state = _objectSpread({}, state, Object(defineProperty["a" /* default */])({}, group, _objectSpread({}, state[group], {
        isRequesting: isRequesting
      })));
      break;

    case action_types.CLEAR_IS_DIRTY:
      state = _objectSpread({}, state, Object(defineProperty["a" /* default */])({}, group, _objectSpread({}, state[group], {
        dirty: []
      })));
      break;

    case action_types.UPDATE_SETTINGS_FOR_GROUP:
    case action_types.UPDATE_ERROR_FOR_GROUP:
      var groupIds = data ? Object.keys(data) : [];

      if (data === null) {
        state = _objectSpread({}, state, Object(defineProperty["a" /* default */])({}, group, {
          data: state[group] ? state[group].data : [],
          error: error,
          lastReceived: time
        }));
      } else {
        state = _objectSpread({}, state, Object(defineProperty["a" /* default */])({}, group, {
          data: state[group] && state[group].data ? [].concat(Object(toConsumableArray["a" /* default */])(state[group].data), Object(toConsumableArray["a" /* default */])(groupIds)) : groupIds,
          error: error,
          lastReceived: time,
          isRequesting: false,
          dirty: state[group] && state[group].dirty ? Object(external_lodash_["union"])(state[group].dirty, groupIds) : groupIds
        }), reducer_updateGroupDataInNewState(newState, {
          group: group,
          groupIds: groupIds,
          data: data,
          time: time,
          error: error
        }));
      }

      break;

    case action_types.CLEAR_SETTINGS:
      state = {};
  }

  return state;
};

/* harmony default export */ var reducer = (reducer_receiveSettings);
// CONCATENATED MODULE: ./packages/data/build-module/settings/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_this_wp_data_["registerStore"])(STORE_NAME, {
  reducer: reducer,
  actions: actions_namespaceObject,
  controls: external_this_wp_dataControls_["controls"],
  selectors: selectors_namespaceObject,
  resolvers: resolvers_namespaceObject
});
var SETTINGS_STORE_NAME = STORE_NAME;
// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// CONCATENATED MODULE: ./packages/data/build-module/settings/with-settings-hydration.js

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var with_settings_hydration_withSettingsHydration = function withSettingsHydration(group, settings) {
  return function (OriginalComponent) {
    return function (props) {
      var settingsRef = Object(external_this_wp_element_["useRef"])(settings);
      Object(external_this_wp_data_["useSelect"])(function (select, registry) {
        if (!settingsRef.current) {
          return;
        }

        var _select = select(STORE_NAME),
            isResolving = _select.isResolving,
            hasFinishedResolution = _select.hasFinishedResolution;

        var _registry$dispatch = registry.dispatch(STORE_NAME),
            startResolution = _registry$dispatch.startResolution,
            finishResolution = _registry$dispatch.finishResolution,
            updateSettingsForGroup = _registry$dispatch.updateSettingsForGroup,
            clearIsDirty = _registry$dispatch.clearIsDirty;

        if (!isResolving('getSettings', [group]) && !hasFinishedResolution('getSettings', [group])) {
          startResolution('getSettings', [group]);
          updateSettingsForGroup(group, settingsRef.current);
          clearIsDirty(group);
          finishResolution('getSettings', [group]);
        }
      }, []);
      return Object(external_this_wp_element_["createElement"])(OriginalComponent, props);
    };
  };
};
// CONCATENATED MODULE: ./packages/data/build-module/settings/use-settings.js


function use_settings_ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }

  return keys;
}

function use_settings_objectSpread(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      use_settings_ownKeys(Object(source), true).forEach(function (key) {
        Object(defineProperty["a" /* default */])(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      use_settings_ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }

  return target;
}
/**
 * External dependencies
 */





var use_settings_useSettings = function useSettings(group) {
  var settingsKeys = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];

  var _useSelect = Object(external_this_wp_data_["useSelect"])(function (select) {
    var _select = select(STORE_NAME),
        getLastSettingsErrorForGroup = _select.getLastSettingsErrorForGroup,
        getSettingsForGroup = _select.getSettingsForGroup,
        getIsDirty = _select.getIsDirty,
        isGetSettingsRequesting = _select.isGetSettingsRequesting;

    return {
      requestedSettings: getSettingsForGroup(group, settingsKeys),
      settingsError: Boolean(getLastSettingsErrorForGroup(group)),
      isRequesting: isGetSettingsRequesting(group),
      isDirty: getIsDirty(group, settingsKeys)
    };
  }, [group, settingsKeys]),
      requestedSettings = _useSelect.requestedSettings,
      settingsError = _useSelect.settingsError,
      isRequesting = _useSelect.isRequesting,
      isDirty = _useSelect.isDirty;

  var _useDispatch = Object(external_this_wp_data_["useDispatch"])(STORE_NAME),
      persistSettingsForGroup = _useDispatch.persistSettingsForGroup,
      updateAndPersistSettingsForGroup = _useDispatch.updateAndPersistSettingsForGroup,
      updateSettingsForGroup = _useDispatch.updateSettingsForGroup;

  var updateSettings = Object(external_this_wp_element_["useCallback"])(function (name, data) {
    updateSettingsForGroup(group, Object(defineProperty["a" /* default */])({}, name, data));
  }, [group]);
  var persistSettings = Object(external_this_wp_element_["useCallback"])(function () {
    // this action would simply persist all settings marked as dirty in the
    // store state and then remove the dirty record in the isDirtyMap
    persistSettingsForGroup(group);
  }, [group]);
  var updateAndPersistSettings = Object(external_this_wp_element_["useCallback"])(function (name, data) {
    updateAndPersistSettingsForGroup(group, Object(defineProperty["a" /* default */])({}, name, data));
  }, [group]);
  return use_settings_objectSpread({
    settingsError: settingsError,
    isRequesting: isRequesting,
    isDirty: isDirty
  }, requestedSettings, {
    persistSettings: persistSettings,
    updateAndPersistSettings: updateAndPersistSettings,
    updateSettings: updateSettings
  });
};
// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// CONCATENATED MODULE: ./packages/data/build-module/plugins/constants.js
/**
 * External dependencies
 */

var constants_STORE_NAME = 'wc/admin/plugins';
/**
 * Plugin slugs and names as key/value pairs.
 */

var pluginNames = {
  'facebook-for-woocommerce': Object(external_this_wp_i18n_["__"])('Facebook for WooCommerce', 'woocommerce'),
  jetpack: Object(external_this_wp_i18n_["__"])('Jetpack', 'woocommerce'),
  'klarna-checkout-for-woocommerce': Object(external_this_wp_i18n_["__"])('Klarna Checkout for WooCommerce', 'woocommerce'),
  'klarna-payments-for-woocommerce': Object(external_this_wp_i18n_["__"])('Klarna Payments for WooCommerce', 'woocommerce'),
  'mailchimp-for-woocommerce': Object(external_this_wp_i18n_["__"])('Mailchimp for WooCommerce', 'woocommerce'),
  'woocommerce-gateway-paypal-express-checkout': Object(external_this_wp_i18n_["__"])('WooCommerce PayPal', 'woocommerce'),
  'woocommerce-gateway-stripe': Object(external_this_wp_i18n_["__"])('WooCommerce Stripe', 'woocommerce'),
  'woocommerce-payfast-gateway': Object(external_this_wp_i18n_["__"])('WooCommerce PayFast', 'woocommerce'),
  'woocommerce-payments': Object(external_this_wp_i18n_["__"])('WooCommerce Payments', 'woocommerce'),
  'woocommerce-services': Object(external_this_wp_i18n_["__"])('WooCommerce Services', 'woocommerce'),
  'woocommerce-shipstation-integration': Object(external_this_wp_i18n_["__"])('WooCommerce ShipStation Gateway', 'woocommerce'),
  'kliken-marketing-for-google': Object(external_this_wp_i18n_["__"])('Google Ads', 'woocommerce')
};
// CONCATENATED MODULE: ./packages/data/build-module/plugins/selectors.js
var getActivePlugins = function getActivePlugins(state) {
  return state.active || [];
};
var getInstalledPlugins = function getInstalledPlugins(state) {
  return state.installed || [];
};
var isPluginsRequesting = function isPluginsRequesting(state, selector) {
  return state.requesting[selector] || false;
};
var getPluginsError = function getPluginsError(state, selector) {
  return state.errors[selector] || false;
};
var isJetpackConnected = function isJetpackConnected(state) {
  return state.jetpackConnection;
};
var getJetpackConnectUrl = function getJetpackConnectUrl(state, query) {
  return state.jetpackConnectUrls[query.redirect_url];
};
// CONCATENATED MODULE: ./packages/data/build-module/plugins/action-types.js
var action_types_TYPES = {
  UPDATE_ACTIVE_PLUGINS: 'UPDATE_ACTIVE_PLUGINS',
  UPDATE_INSTALLED_PLUGINS: 'UPDATE_INSTALLED_PLUGINS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  SET_ERROR: 'SET_ERROR',
  UPDATE_JETPACK_CONNECTION: 'UPDATE_JETPACK_CONNECTION',
  UPDATE_JETPACK_CONNECT_URL: 'UPDATE_JETPACK_CONNECT_URL'
};
/* harmony default export */ var plugins_action_types = (action_types_TYPES);
// CONCATENATED MODULE: ./packages/data/build-module/plugins/actions.js
var actions_marked = /*#__PURE__*/regeneratorRuntime.mark(installPlugin),
    actions_marked2 = /*#__PURE__*/regeneratorRuntime.mark(activatePlugins);
/**
 * External Dependencies
 */




/**
 * Internal Dependencies
 */




function actions_updateActivePlugins(active) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: plugins_action_types.UPDATE_ACTIVE_PLUGINS,
    active: active,
    replace: replace
  };
}
function actions_updateInstalledPlugins(installed) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: plugins_action_types.UPDATE_INSTALLED_PLUGINS,
    installed: installed,
    replace: replace
  };
}
function actions_setIsRequesting(selector, isRequesting) {
  return {
    type: plugins_action_types.SET_IS_REQUESTING,
    selector: selector,
    isRequesting: isRequesting
  };
}
function setError(selector, error) {
  return {
    type: plugins_action_types.SET_ERROR,
    selector: selector,
    error: error
  };
}
function actions_updateIsJetpackConnected(jetpackConnection) {
  return {
    type: plugins_action_types.UPDATE_JETPACK_CONNECTION,
    jetpackConnection: jetpackConnection
  };
}
function updateJetpackConnectUrl(redirectUrl, jetpackConnectUrl) {
  return {
    type: plugins_action_types.UPDATE_JETPACK_CONNECT_URL,
    jetpackConnectUrl: jetpackConnectUrl,
    redirectUrl: redirectUrl
  };
}

function getPluginErrorMessage(action, plugin) {
  var pluginName = pluginNames[plugin] || plugin;

  switch (action) {
    case 'install':
      return Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('There was an error installing %s. Please try again.', 'woocommerce'), pluginName);

    case 'connect':
      return Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('There was an error connecting to %s. Please try again.', 'woocommerce'), pluginName);

    case 'activate':
    default:
      return Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('There was an error activating %s. Please try again.', 'woocommerce'), pluginName);
  }
}

function installPlugin(plugin) {
  var results, errorMsg;
  return regeneratorRuntime.wrap(function installPlugin$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return actions_setIsRequesting('installPlugin', true);

        case 2:
          _context.prev = 2;
          _context.next = 5;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: "".concat(WC_ADMIN_NAMESPACE, "/plugins/install"),
            method: 'POST',
            data: {
              plugin: plugin
            }
          });

        case 5:
          results = _context.sent;

          if (!(results && results.status === 'success')) {
            _context.next = 10;
            break;
          }

          _context.next = 9;
          return actions_updateInstalledPlugins(null, results.slug);

        case 9:
          return _context.abrupt("return", results);

        case 10:
          throw new Error();

        case 13:
          _context.prev = 13;
          _context.t0 = _context["catch"](2);
          errorMsg = getPluginErrorMessage('install', plugin);
          _context.next = 18;
          return setError('installPlugin', errorMsg);

        case 18:
          return _context.abrupt("return", errorMsg);

        case 19:
        case "end":
          return _context.stop();
      }
    }
  }, actions_marked, null, [[2, 13]]);
}
function activatePlugins(plugins) {
  var results;
  return regeneratorRuntime.wrap(function activatePlugins$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return actions_setIsRequesting('activatePlugins', true);

        case 2:
          _context2.prev = 2;
          _context2.next = 5;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: "".concat(WC_ADMIN_NAMESPACE, "/plugins/activate"),
            method: 'POST',
            data: {
              plugins: plugins.join(',')
            }
          });

        case 5:
          results = _context2.sent;

          if (!(results && results.status === 'success')) {
            _context2.next = 10;
            break;
          }

          _context2.next = 9;
          return actions_updateActivePlugins(results.activatedPlugins);

        case 9:
          return _context2.abrupt("return", results);

        case 10:
          throw new Error();

        case 13:
          _context2.prev = 13;
          _context2.t0 = _context2["catch"](2);
          _context2.next = 17;
          return setError('activatePlugins', _context2.t0);

        case 17:
          return _context2.abrupt("return", plugins.map(function (plugin) {
            return getPluginErrorMessage('activate', plugin);
          }));

        case 18:
        case "end":
          return _context2.stop();
      }
    }
  }, actions_marked2, null, [[2, 13]]);
}
// EXTERNAL MODULE: external {"this":["wp","url"]}
var external_this_wp_url_ = __webpack_require__(30);

// CONCATENATED MODULE: ./packages/data/build-module/plugins/resolvers.js
var plugins_resolvers_marked = /*#__PURE__*/regeneratorRuntime.mark(resolvers_getActivePlugins),
    plugins_resolvers_marked2 = /*#__PURE__*/regeneratorRuntime.mark(resolvers_getInstalledPlugins),
    _marked3 = /*#__PURE__*/regeneratorRuntime.mark(resolvers_isJetpackConnected),
    _marked4 = /*#__PURE__*/regeneratorRuntime.mark(resolvers_getJetpackConnectUrl);
/**
 * External Dependencies
 */




/**
 * Internal dependencies
 */



function resolvers_getActivePlugins() {
  var url, results;
  return regeneratorRuntime.wrap(function getActivePlugins$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return actions_setIsRequesting('getActivePlugins', true);

        case 2:
          _context.prev = 2;
          url = WC_ADMIN_NAMESPACE + '/plugins/active';
          _context.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context.sent;
          _context.next = 9;
          return actions_updateActivePlugins(results.plugins, true);

        case 9:
          _context.next = 15;
          break;

        case 11:
          _context.prev = 11;
          _context.t0 = _context["catch"](2);
          _context.next = 15;
          return setError('getActivePlugins', _context.t0);

        case 15:
        case "end":
          return _context.stop();
      }
    }
  }, plugins_resolvers_marked, null, [[2, 11]]);
}
function resolvers_getInstalledPlugins() {
  var url, results;
  return regeneratorRuntime.wrap(function getInstalledPlugins$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return actions_setIsRequesting('getInstalledPlugins', true);

        case 2:
          _context2.prev = 2;
          url = WC_ADMIN_NAMESPACE + '/plugins/installed';
          _context2.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context2.sent;
          _context2.next = 9;
          return actions_updateInstalledPlugins(results, true);

        case 9:
          _context2.next = 15;
          break;

        case 11:
          _context2.prev = 11;
          _context2.t0 = _context2["catch"](2);
          _context2.next = 15;
          return setError('getInstalledPlugins', _context2.t0);

        case 15:
        case "end":
          return _context2.stop();
      }
    }
  }, plugins_resolvers_marked2, null, [[2, 11]]);
}
function resolvers_isJetpackConnected() {
  var url, results;
  return regeneratorRuntime.wrap(function isJetpackConnected$(_context3) {
    while (1) {
      switch (_context3.prev = _context3.next) {
        case 0:
          _context3.next = 2;
          return actions_setIsRequesting('isJetpackConnected', true);

        case 2:
          _context3.prev = 2;
          url = JETPACK_NAMESPACE + '/connection';
          _context3.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context3.sent;
          _context3.next = 9;
          return actions_updateIsJetpackConnected(results.isActive);

        case 9:
          _context3.next = 15;
          break;

        case 11:
          _context3.prev = 11;
          _context3.t0 = _context3["catch"](2);
          _context3.next = 15;
          return setError('isJetpackConnected', _context3.t0);

        case 15:
          _context3.next = 17;
          return actions_setIsRequesting('isJetpackConnected', false);

        case 17:
        case "end":
          return _context3.stop();
      }
    }
  }, _marked3, null, [[2, 11]]);
}
function resolvers_getJetpackConnectUrl(query) {
  var url, results;
  return regeneratorRuntime.wrap(function getJetpackConnectUrl$(_context4) {
    while (1) {
      switch (_context4.prev = _context4.next) {
        case 0:
          _context4.next = 2;
          return actions_setIsRequesting('getJetpackConnectUrl', true);

        case 2:
          _context4.prev = 2;
          url = Object(external_this_wp_url_["addQueryArgs"])(WC_ADMIN_NAMESPACE + '/plugins/connect-jetpack', query);
          _context4.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context4.sent;
          _context4.next = 9;
          return updateJetpackConnectUrl(query.redirect_url, results.connectAction);

        case 9:
          _context4.next = 15;
          break;

        case 11:
          _context4.prev = 11;
          _context4.t0 = _context4["catch"](2);
          _context4.next = 15;
          return setError('getJetpackConnectUrl', _context4.t0);

        case 15:
          _context4.next = 17;
          return actions_setIsRequesting('getJetpackConnectUrl', false);

        case 17:
        case "end":
          return _context4.stop();
      }
    }
  }, _marked4, null, [[2, 11]]);
}
// CONCATENATED MODULE: ./packages/data/build-module/plugins/reducer.js


function reducer_ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }

  return keys;
}

function reducer_objectSpread(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      reducer_ownKeys(Object(source), true).forEach(function (key) {
        Object(defineProperty["a" /* default */])(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      reducer_ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }

  return target;
}
/**
 * External Dependencies
 */



/**
 * Internal dependencies
 */



var reducer_plugins = function plugins() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    active: [],
    installed: [],
    requesting: {},
    errors: {},
    jetpackConnectUrls: {}
  };

  var _ref = arguments.length > 1 ? arguments[1] : undefined,
      type = _ref.type,
      active = _ref.active,
      installed = _ref.installed,
      selector = _ref.selector,
      isRequesting = _ref.isRequesting,
      error = _ref.error,
      jetpackConnection = _ref.jetpackConnection,
      redirectUrl = _ref.redirectUrl,
      jetpackConnectUrl = _ref.jetpackConnectUrl,
      replace = _ref.replace;

  switch (type) {
    case plugins_action_types.UPDATE_ACTIVE_PLUGINS:
      state = reducer_objectSpread({}, state, {
        active: replace ? active : Object(external_lodash_["concat"])(state.active, active),
        requesting: reducer_objectSpread({}, state.requesting, {
          getActivePlugins: false,
          activatePlugins: false
        }),
        errors: reducer_objectSpread({}, state.errors, {
          getActivePlugins: false,
          activatePlugins: false
        })
      });
      break;

    case plugins_action_types.UPDATE_INSTALLED_PLUGINS:
      state = reducer_objectSpread({}, state, {
        installed: replace ? installed : Object(external_lodash_["concat"])(state.installed, installed),
        requesting: reducer_objectSpread({}, state.requesting, {
          getInstalledPlugins: false,
          installPlugin: false
        }),
        errors: reducer_objectSpread({}, state.errors, {
          getInstalledPlugins: false,
          installPlugin: false
        })
      });
      break;

    case plugins_action_types.SET_IS_REQUESTING:
      state = reducer_objectSpread({}, state, {
        requesting: reducer_objectSpread({}, state.requesting, Object(defineProperty["a" /* default */])({}, selector, isRequesting))
      });
      break;

    case plugins_action_types.SET_ERROR:
      state = reducer_objectSpread({}, state, {
        requesting: reducer_objectSpread({}, state.requesting, Object(defineProperty["a" /* default */])({}, selector, false)),
        errors: reducer_objectSpread({}, state.errors, Object(defineProperty["a" /* default */])({}, selector, error))
      });
      break;

    case plugins_action_types.UPDATE_JETPACK_CONNECTION:
      state = reducer_objectSpread({}, state, {
        jetpackConnection: jetpackConnection
      });
      break;

    case plugins_action_types.UPDATE_JETPACK_CONNECT_URL:
      state = reducer_objectSpread({}, state, {
        jetpackConnectUrls: reducer_objectSpread({}, state.jetpackConnectUrl, Object(defineProperty["a" /* default */])({}, redirectUrl, jetpackConnectUrl))
      });
  }

  return state;
};

/* harmony default export */ var plugins_reducer = (reducer_plugins);
// CONCATENATED MODULE: ./packages/data/build-module/plugins/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_this_wp_data_["registerStore"])(constants_STORE_NAME, {
  reducer: plugins_reducer,
  actions: plugins_actions_namespaceObject,
  controls: external_this_wp_dataControls_["controls"],
  selectors: plugins_selectors_namespaceObject,
  resolvers: plugins_resolvers_namespaceObject
});
var PLUGINS_STORE_NAME = constants_STORE_NAME;
// CONCATENATED MODULE: ./packages/data/build-module/plugins/with-plugins-hydration.js

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var with_plugins_hydration_withPluginsHydration = function withPluginsHydration(data) {
  return function (OriginalComponent) {
    return function (props) {
      var dataRef = Object(external_this_wp_element_["useRef"])(data);
      Object(external_this_wp_data_["useSelect"])(function (select, registry) {
        if (!dataRef.current) {
          return;
        }

        var _select = select(constants_STORE_NAME),
            isResolving = _select.isResolving,
            hasFinishedResolution = _select.hasFinishedResolution;

        var _registry$dispatch = registry.dispatch(constants_STORE_NAME),
            startResolution = _registry$dispatch.startResolution,
            finishResolution = _registry$dispatch.finishResolution,
            updateActivePlugins = _registry$dispatch.updateActivePlugins,
            updateInstalledPlugins = _registry$dispatch.updateInstalledPlugins,
            updateIsJetpackConnected = _registry$dispatch.updateIsJetpackConnected;

        if (!isResolving('getActivePlugins', []) && !hasFinishedResolution('getActivePlugins', [])) {
          startResolution('getActivePlugins', []);
          startResolution('getInstalledPlugins', []);
          startResolution('isJetpackConnected', []);
          updateActivePlugins(dataRef.current.activePlugins, true);
          updateInstalledPlugins(dataRef.current.installedPlugins, true);
          updateIsJetpackConnected(dataRef.current.jetpackStatus && dataRef.current.jetpackStatus.isActive);
          finishResolution('getActivePlugins', []);
          finishResolution('getInstalledPlugins', []);
          finishResolution('isJetpackConnected', []);
        }
      }, []);
      return Object(external_this_wp_element_["createElement"])(OriginalComponent, props);
    };
  };
};
// CONCATENATED MODULE: ./packages/data/build-module/index.js






/***/ })

/******/ });
