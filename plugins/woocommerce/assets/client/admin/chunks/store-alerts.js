(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[50],{

/***/ 503:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const chevronRight = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z"
}));
/* harmony default export */ __webpack_exports__["a"] = (chevronRight);
//# sourceMappingURL=chevron-right.js.map

/***/ }),

/***/ 505:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const chevronLeft = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z"
}));
/* harmony default export */ __webpack_exports__["a"] = (chevronLeft);
//# sourceMappingURL=chevron-left.js.map

/***/ }),

/***/ 546:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export ALLOWED_TAGS */
/* unused harmony export ALLOWED_ATTR */
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(53);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

const ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p', 'br'];
const ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download'];
/* harmony default export */ __webpack_exports__["a"] = (html => {
  return {
    __html: Object(dompurify__WEBPACK_IMPORTED_MODULE_0__["sanitize"])(html, {
      ALLOWED_TAGS,
      ALLOWED_ATTR
    })
  };
});

/***/ }),

/***/ 576:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 669:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "StoreAlerts", function() { return /* binding */ store_alerts_StoreAlerts; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@automattic+interpolate-components@1.2.0_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__(79);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(11);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__(116);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/chevron-left.js
var chevron_left = __webpack_require__(505);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__(503);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: ./client/lib/sanitize-html/index.js
var sanitize_html = __webpack_require__(546);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// CONCATENATED MODULE: ./client/layout/store-alerts/placeholder.js


/**
 * External dependencies
 */




class placeholder_StoreAlertsPlaceholder extends external_wp_element_["Component"] {
  render() {
    const {
      hasMultipleAlerts
    } = this.props;
    return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
      className: "woocommerce-store-alerts is-loading",
      "aria-hidden": true,
      size: null
    }, Object(external_wp_element_["createElement"])(external_wp_components_["CardHeader"], {
      isBorderless: true
    }, Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    }), hasMultipleAlerts && Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    })), Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], null, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-store-alerts__message"
    }, Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    }), Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    }))), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
      isBorderless: true
    }, Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    })));
  }

}

/* harmony default export */ var placeholder = (placeholder_StoreAlertsPlaceholder);
placeholder_StoreAlertsPlaceholder.propTypes = {
  /**
   * Whether multiple alerts exists.
   */
  hasMultipleAlerts: prop_types_default.a.bool
};
placeholder_StoreAlertsPlaceholder.defaultProps = {
  hasMultipleAlerts: false
};
// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// EXTERNAL MODULE: ./client/layout/store-alerts/style.scss
var style = __webpack_require__(576);

// CONCATENATED MODULE: ./client/layout/store-alerts/index.js


/**
 * External dependencies
 */












/**
 * Internal dependencies
 */





class store_alerts_StoreAlerts extends external_wp_element_["Component"] {
  constructor(props) {
    super(props);
    this.state = {
      currentIndex: 0
    };
    this.previousAlert = this.previousAlert.bind(this);
    this.nextAlert = this.nextAlert.bind(this);
  }

  previousAlert(event) {
    event.stopPropagation();
    const {
      currentIndex
    } = this.state;

    if (currentIndex > 0) {
      this.setState({
        currentIndex: currentIndex - 1
      });
    }
  }

  nextAlert(event) {
    event.stopPropagation();
    const alerts = this.getAlerts();
    const {
      currentIndex
    } = this.state;

    if (currentIndex < alerts.length - 1) {
      this.setState({
        currentIndex: currentIndex + 1
      });
    }
  }

  renderActions(alert) {
    const {
      triggerNoteAction,
      updateNote
    } = this.props;
    const actions = alert.actions.map(action => {
      return Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        key: action.name,
        isPrimary: action.primary,
        isSecondary: !action.primary,
        href: action.url || undefined,
        onClick: () => triggerNoteAction(alert.id, action.id)
      }, action.label);
    }); // TODO: should "next X" be the start, or exactly 1X from the current date?

    const snoozeOptions = [{
      value: external_moment_default()().add(4, 'hours').unix().toString(),
      label: Object(external_wp_i18n_["__"])('Later Today', 'woocommerce-admin')
    }, {
      value: external_moment_default()().add(1, 'day').hour(9).minute(0).second(0).millisecond(0).unix().toString(),
      label: Object(external_wp_i18n_["__"])('Tomorrow', 'woocommerce-admin')
    }, {
      value: external_moment_default()().add(1, 'week').hour(9).minute(0).second(0).millisecond(0).unix().toString(),
      label: Object(external_wp_i18n_["__"])('Next Week', 'woocommerce-admin')
    }, {
      value: external_moment_default()().add(1, 'month').hour(9).minute(0).second(0).millisecond(0).unix().toString(),
      label: Object(external_wp_i18n_["__"])('Next Month', 'woocommerce-admin')
    }];

    const setReminderDate = snoozeOption => {
      updateNote(alert.id, {
        status: 'snoozed',
        date_reminder: snoozeOption.value
      });
      const eventProps = {
        alert_name: alert.name,
        alert_title: alert.title,
        snooze_duration: snoozeOption.value,
        snooze_label: snoozeOption.label
      };
      Object(external_wc_tracks_["recordEvent"])('store_alert_snooze', eventProps);
    };

    const snooze = alert.is_snoozable && Object(external_wp_element_["createElement"])(external_wp_components_["SelectControl"], {
      className: "woocommerce-store-alerts__snooze",
      options: [{
        label: Object(external_wp_i18n_["__"])('Remind Me Later', 'woocommerce-admin'),
        value: '0'
      }, ...snoozeOptions],
      onChange: value => {
        if (value === '0') {
          return;
        }

        const reminderOption = snoozeOptions.find(option => option.value === value);
        const reminderDate = {
          value,
          label: reminderOption && reminderOption.label
        };
        setReminderDate(reminderDate);
      }
    });

    if (actions || snooze) {
      return Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-store-alerts__actions"
      }, actions, snooze);
    }
  }

  getAlerts() {
    return (this.props.alerts || []).filter(note => note.status === 'unactioned');
  }

  render() {
    const alerts = this.getAlerts();
    const preloadAlertCount = Object(admin_settings["d" /* getAdminSetting */])('alertCount', 0, count => parseInt(count, 10));

    if (preloadAlertCount > 0 && this.props.isLoading) {
      return Object(external_wp_element_["createElement"])(placeholder, {
        hasMultipleAlerts: preloadAlertCount > 1
      });
    } else if (alerts.length === 0) {
      return null;
    }

    const {
      currentIndex
    } = this.state;
    const numberOfAlerts = alerts.length;
    const alert = alerts[currentIndex];
    const type = alert.type;
    const className = classnames_default()('woocommerce-store-alerts', {
      'is-alert-error': type === 'error',
      'is-alert-update': type === 'update'
    });
    return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
      className: className,
      size: null
    }, Object(external_wp_element_["createElement"])(external_wp_components_["CardHeader"], {
      isBorderless: true
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "title.medium",
      as: "h2",
      size: "24",
      lineHeight: "32px"
    }, alert.title), numberOfAlerts > 1 && Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-store-alerts__pagination"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      onClick: this.previousAlert,
      disabled: currentIndex === 0,
      label: Object(external_wp_i18n_["__"])('Previous Alert', 'woocommerce-admin')
    }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: chevron_left["a" /* default */],
      className: "arrow-left-icon"
    })), Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-store-alerts__pagination-label",
      role: "status",
      "aria-live": "polite"
    }, Object(esm["a" /* default */])({
      mixedString: Object(external_wp_i18n_["__"])('{{current /}} of {{total /}}', 'woocommerce-admin'),
      components: {
        current: Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, currentIndex + 1),
        total: Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, numberOfAlerts)
      }
    })), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      onClick: this.nextAlert,
      disabled: numberOfAlerts - 1 === currentIndex,
      label: Object(external_wp_i18n_["__"])('Next Alert', 'woocommerce-admin')
    }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: chevron_right["a" /* default */],
      className: "arrow-right-icon"
    })))), Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], null, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-store-alerts__message",
      dangerouslySetInnerHTML: Object(sanitize_html["a" /* default */])(alert.content)
    })), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
      isBorderless: true
    }, this.renderActions(alert)));
  }

}
const ALERTS_QUERY = {
  page: 1,
  per_page: external_wc_data_["QUERY_DEFAULTS"].pageSize,
  type: 'error,update',
  status: 'unactioned'
};
/* harmony default export */ var store_alerts = __webpack_exports__["default"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getNotes,
    isResolving
  } = select(external_wc_data_["NOTES_STORE_NAME"]); // Filter out notes that may have been marked actioned or not delayed after the initial request

  const alerts = getNotes(ALERTS_QUERY);
  const isLoading = isResolving('getNotes', [ALERTS_QUERY]);
  return {
    alerts,
    isLoading
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    triggerNoteAction,
    updateNote
  } = dispatch(external_wc_data_["NOTES_STORE_NAME"]);
  return {
    triggerNoteAction,
    updateNote
  };
}))(store_alerts_StoreAlerts));

/***/ })

}]);