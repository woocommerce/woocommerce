this["wc"] = this["wc"] || {}; this["wc"]["customerEffortScore"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = 497);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ 1:
/***/ (function(module, exports, __webpack_require__) {

/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

if (false) { var throwOnDirectAccess, ReactIs; } else {
  // By explicitly using `prop-types` you are opting into new production behavior.
  // http://fb.me/prop-types-in-prod
  module.exports = __webpack_require__(73)();
}


/***/ }),

/***/ 14:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ 19:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["experimental"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["components"]; }());

/***/ }),

/***/ 497:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "CustomerEffortScore", function() { return /* binding */ CustomerEffortScore; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/customer-effort-score/build-module/customer-feedback-modal/index.js
/**
 * External dependencies
 */





/**
 * Provides a modal requesting customer feedback.
 *
 * A label is displayed in the modal asking the customer to score the
 * difficulty completing a task. A group of radio buttons, styled with
 * emoji facial expressions, are used to provide a score between 1 and 5.
 *
 * A low score triggers a comments field to appear.
 *
 * Upon completion, the score and comments is sent to a callback function.
 *
 * @param {Object} props                       Component props.
 * @param {Function} props.recordScoreCallback Function to call when the results are sent.
 * @param {string} props.label                 Question to ask the customer.
 */

function CustomerFeedbackModal(_ref) {
  let {
    recordScoreCallback,
    label
  } = _ref;
  const options = [{
    label: Object(external_wp_i18n_["__"])('Very difficult', 'woocommerce-admin'),
    value: '1'
  }, {
    label: Object(external_wp_i18n_["__"])('Somewhat difficult', 'woocommerce-admin'),
    value: '2'
  }, {
    label: Object(external_wp_i18n_["__"])('Neutral', 'woocommerce-admin'),
    value: '3'
  }, {
    label: Object(external_wp_i18n_["__"])('Somewhat easy', 'woocommerce-admin'),
    value: '4'
  }, {
    label: Object(external_wp_i18n_["__"])('Very easy', 'woocommerce-admin'),
    value: '5'
  }];
  const [score, setScore] = Object(external_wp_element_["useState"])(NaN);
  const [comments, setComments] = Object(external_wp_element_["useState"])('');
  const [showNoScoreMessage, setShowNoScoreMessage] = Object(external_wp_element_["useState"])(false);
  const [isOpen, setOpen] = Object(external_wp_element_["useState"])(true);

  const closeModal = () => setOpen(false);

  const onRadioControlChange = value => {
    const valueAsInt = parseInt(value, 10);
    setScore(valueAsInt);
    setShowNoScoreMessage(!Number.isInteger(valueAsInt));
  };

  const sendScore = () => {
    if (!Number.isInteger(score)) {
      setShowNoScoreMessage(true);
      return;
    }

    setOpen(false);
    recordScoreCallback(score, comments);
  };

  if (!isOpen) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(external_wp_components_["Modal"], {
    className: "woocommerce-customer-effort-score",
    title: Object(external_wp_i18n_["__"])('Please share your feedback', 'woocommerce-admin'),
    onRequestClose: closeModal,
    shouldCloseOnClickOutside: false
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    variant: "subtitle.small",
    as: "p",
    weight: "600",
    size: "14",
    lineHeight: "20px"
  }, label), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-customer-effort-score__selection"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["RadioControl"], {
    selected: score.toString(10),
    options: options,
    onChange: onRadioControlChange
  })), (score === 1 || score === 2) && Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-customer-effort-score__comments"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["TextareaControl"], {
    label: Object(external_wp_i18n_["__"])('Comments (Optional)', 'woocommerce-admin'),
    help: Object(external_wp_i18n_["__"])('Your feedback will go to the WooCommerce development team', 'woocommerce-admin'),
    value: comments,
    onChange: value => setComments(value),
    rows: 5
  })), showNoScoreMessage && Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-customer-effort-score__errors",
    role: "alert"
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    variant: "body",
    as: "p"
  }, Object(external_wp_i18n_["__"])('Please provide feedback by selecting an option above.', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-customer-effort-score__buttons"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isTertiary: true,
    onClick: closeModal,
    name: "cancel"
  }, Object(external_wp_i18n_["__"])('Cancel', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isPrimary: true,
    onClick: sendScore,
    name: "send"
  }, Object(external_wp_i18n_["__"])('Send', 'woocommerce-admin'))));
}

CustomerFeedbackModal.propTypes = {
  recordScoreCallback: prop_types_default.a.func.isRequired,
  label: prop_types_default.a.string.isRequired
};
/* harmony default export */ var customer_feedback_modal = (CustomerFeedbackModal);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/customer-effort-score/build-module/index.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



const noop = () => {};
/**
 * Use `CustomerEffortScore` to gather a customer effort score.
 *
 * NOTE: This should live in @woocommerce/customer-effort-score to allow
 * reuse.
 *
 * @param {Object} props                             Component props.
 * @param {Function} props.recordScoreCallback       Function to call when the score should be recorded.
 * @param {string} props.label                       The label displayed in the modal.
 * @param {Function} props.createNotice              Create a notice (snackbar).
 * @param {Function} props.onNoticeShownCallback     Function to call when the notice is shown.
 * @param {Function} props.onNoticeDismissedCallback Function to call when the notice is dismissed.
 * @param {Function} props.onModalShownCallback      Function to call when the modal is shown.
 * @param {Object} props.icon                        Icon (React component) to be shown on the notice.
 */


function CustomerEffortScore(_ref) {
  let {
    recordScoreCallback,
    label,
    createNotice,
    onNoticeShownCallback = noop,
    onNoticeDismissedCallback = noop,
    onModalShownCallback = noop,
    icon
  } = _ref;
  const [shouldCreateNotice, setShouldCreateNotice] = Object(external_wp_element_["useState"])(true);
  const [visible, setVisible] = Object(external_wp_element_["useState"])(false);
  Object(external_wp_element_["useEffect"])(() => {
    if (!shouldCreateNotice) {
      return;
    }

    createNotice('success', label, {
      actions: [{
        label: Object(external_wp_i18n_["__"])('Give feedback', 'woocommerce-admin'),
        onClick: () => {
          setVisible(true);
          onModalShownCallback();
        }
      }],
      icon,
      explicitDismiss: true,
      onDismiss: onNoticeDismissedCallback
    });
    setShouldCreateNotice(false);
    onNoticeShownCallback();
  }, [shouldCreateNotice]);

  if (shouldCreateNotice) {
    return null;
  }

  if (!visible) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(customer_feedback_modal, {
    label: label,
    recordScoreCallback: recordScoreCallback
  });
}
CustomerEffortScore.propTypes = {
  /**
   * The function to call to record the score.
   */
  recordScoreCallback: prop_types_default.a.func.isRequired,

  /**
   * The label displayed in the modal.
   */
  label: prop_types_default.a.string.isRequired,

  /**
   * Create a notice (snackbar).
   */
  createNotice: prop_types_default.a.func.isRequired,

  /**
   * The function to call when the notice is shown.
   */
  onNoticeShownCallback: prop_types_default.a.func,

  /**
   * The function to call when the notice is dismissed.
   */
  onNoticeDismissedCallback: prop_types_default.a.func,

  /**
   * The function to call when the modal is shown.
   */
  onModalShownCallback: prop_types_default.a.func,

  /**
   * Icon (React component) to be displayed.
   */
  icon: prop_types_default.a.element
};
/* harmony default export */ var build_module = __webpack_exports__["default"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices2');
  return {
    createNotice
  };
}))(CustomerEffortScore));

/***/ }),

/***/ 73:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



var ReactPropTypesSecret = __webpack_require__(74);

function emptyFunction() {}
function emptyFunctionWithReset() {}
emptyFunctionWithReset.resetWarningCache = emptyFunction;

module.exports = function() {
  function shim(props, propName, componentName, location, propFullName, secret) {
    if (secret === ReactPropTypesSecret) {
      // It is still safe when called from React.
      return;
    }
    var err = new Error(
      'Calling PropTypes validators directly is not supported by the `prop-types` package. ' +
      'Use PropTypes.checkPropTypes() to call them. ' +
      'Read more at http://fb.me/use-check-prop-types'
    );
    err.name = 'Invariant Violation';
    throw err;
  };
  shim.isRequired = shim;
  function getShim() {
    return shim;
  };
  // Important!
  // Keep this list in sync with production version in `./factoryWithTypeCheckers.js`.
  var ReactPropTypes = {
    array: shim,
    bigint: shim,
    bool: shim,
    func: shim,
    number: shim,
    object: shim,
    string: shim,
    symbol: shim,

    any: shim,
    arrayOf: getShim,
    element: shim,
    elementType: shim,
    instanceOf: getShim,
    node: shim,
    objectOf: getShim,
    oneOf: getShim,
    oneOfType: getShim,
    shape: getShim,
    exact: getShim,

    checkPropTypes: emptyFunctionWithReset,
    resetWarningCache: emptyFunction
  };

  ReactPropTypes.PropTypes = ReactPropTypes;

  return ReactPropTypes;
};


/***/ }),

/***/ 74:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



var ReactPropTypesSecret = 'SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED';

module.exports = ReactPropTypesSecret;


/***/ }),

/***/ 8:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["data"]; }());

/***/ })

/******/ });