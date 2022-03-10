(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[18],{

/***/ 44:
/***/ (function(module, exports) {

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

module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ 578:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 579:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 580:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 664:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(40);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: ./client/analytics/settings/index.scss
var settings = __webpack_require__(578);

// EXTERNAL MODULE: ./client/analytics/settings/config.js + 1 modules
var config = __webpack_require__(276);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(44);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: ./client/analytics/settings/setting.scss
var settings_setting = __webpack_require__(579);

// CONCATENATED MODULE: ./client/analytics/settings/setting.js




/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



class setting_Setting extends external_wp_element_["Component"] {
  constructor(props) {
    super(props);

    defineProperty_default()(this, "renderInput", () => {
      const {
        handleChange,
        name,
        inputText,
        inputType,
        options,
        value,
        component
      } = this.props;
      const {
        disabled
      } = this.state;

      switch (inputType) {
        case 'checkboxGroup':
          return options.map(optionGroup => optionGroup.options.length > 0 && Object(external_wp_element_["createElement"])("div", {
            className: "woocommerce-setting__options-group",
            key: optionGroup.key,
            "aria-labelledby": name + '-label'
          }, optionGroup.label && Object(external_wp_element_["createElement"])("span", {
            className: "woocommerce-setting__options-group-label"
          }, optionGroup.label), this.renderCheckboxOptions(optionGroup.options)));

        case 'checkbox':
          return this.renderCheckboxOptions(options);

        case 'button':
          return Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
            isSecondary: true,
            onClick: this.handleInputCallback,
            disabled: disabled
          }, inputText);

        case 'component':
          const SettingComponent = component;
          return Object(external_wp_element_["createElement"])(SettingComponent, extends_default()({
            value: value,
            onChange: handleChange
          }, this.props));

        case 'text':
        default:
          const id = Object(external_lodash_["uniqueId"])(name);
          return Object(external_wp_element_["createElement"])("input", {
            id: id,
            type: "text",
            name: name,
            onChange: handleChange,
            value: value,
            placeholder: inputText,
            disabled: disabled
          });
      }
    });

    defineProperty_default()(this, "handleInputCallback", () => {
      const {
        createNotice,
        callback
      } = this.props;

      if (typeof callback !== 'function') {
        return;
      }

      return new Promise((resolve, reject) => {
        this.setState({
          disabled: true
        });
        callback(resolve, reject, createNotice);
      }).then(() => {
        this.setState({
          disabled: false
        });
      }).catch(() => {
        this.setState({
          disabled: false
        });
      });
    });

    this.state = {
      disabled: false
    };
  }

  renderCheckboxOptions(options) {
    const {
      handleChange,
      name,
      value
    } = this.props;
    const {
      disabled
    } = this.state;
    return options.map(option => {
      return Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
        key: name + '-' + option.value,
        label: option.label,
        name: name,
        checked: value && value.includes(option.value),
        onChange: checked => handleChange({
          target: {
            checked,
            name,
            type: 'checkbox',
            value: option.value
          }
        }),
        disabled: disabled
      });
    });
  }

  render() {
    const {
      helpText,
      label,
      name
    } = this.props;
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-setting"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-setting__label",
      id: name + '-label'
    }, label), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-setting__input"
    }, this.renderInput(), helpText && Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-setting__help"
    }, helpText)));
  }

}

setting_Setting.propTypes = {
  /**
   * A callback that is fired after actionable items, such as buttons.
   */
  callback: prop_types_default.a.func,

  /**
   * Function assigned to the onChange of all inputs.
   */
  handleChange: prop_types_default.a.func.isRequired,

  /**
   * Optional help text displayed underneath the setting.
   */
  helpText: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.array]),

  /**
   * Text used as placeholder or button text in the input area.
   */
  inputText: prop_types_default.a.string,

  /**
   * Type of input to use; defaults to a text input.
   */
  inputType: prop_types_default.a.oneOf(['button', 'checkbox', 'checkboxGroup', 'text', 'component']),

  /**
   * Label used for describing the setting.
   */
  label: prop_types_default.a.string.isRequired,

  /**
   * Setting slug applied to input names.
   */
  name: prop_types_default.a.string.isRequired,

  /**
   * Array of options used for when the `inputType` allows multiple selections.
   */
  options: prop_types_default.a.arrayOf(prop_types_default.a.shape({
    /**
     * Input value for this option.
     */
    value: prop_types_default.a.string,

    /**
     * Label for this option or above a group for a group `inputType`.
     */
    label: prop_types_default.a.string,

    /**
     * Description used for screen readers.
     */
    description: prop_types_default.a.string,

    /**
     * Key used for a group `inputType`.
     */
    key: prop_types_default.a.string,

    /**
     * Nested options for a group `inputType`.
     */
    options: prop_types_default.a.array
  })),

  /**
   * The string value used for the input or array of items if the input allows multiselection.
   */
  value: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.array])
};
/* harmony default export */ var analytics_settings_setting = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice
  };
}))(setting_Setting));
// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(11);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/utils.js
/**
 * External dependencies
 */


const formatParams = (dateFormat, period, skipChecked) => {
  const params = {};

  if (skipChecked) {
    params.skip_existing = true;
  }

  if (period.label !== 'all') {
    if (period.label === 'custom') {
      const daysDifference = external_moment_default()().diff(external_moment_default()(period.date, dateFormat), 'days', true);
      params.days = Math.floor(daysDifference);
    } else {
      params.days = parseInt(period.label, 10);
    }
  }

  return params;
};
const getStatus = _ref => {
  let {
    cacheNeedsClearing,
    customersProgress,
    customersTotal,
    isError,
    inProgress,
    ordersProgress,
    ordersTotal
  } = _ref;

  if (isError) {
    return 'error';
  }

  if (inProgress) {
    if (Object(external_lodash_["isNil"])(customersProgress) || Object(external_lodash_["isNil"])(ordersProgress) || Object(external_lodash_["isNil"])(customersTotal) || Object(external_lodash_["isNil"])(ordersTotal) || cacheNeedsClearing) {
      return 'initializing';
    }

    if (customersProgress < customersTotal) {
      return 'customers';
    }

    if (ordersProgress < ordersTotal) {
      return 'orders';
    }

    return 'finalizing';
  }

  if (customersTotal > 0 || ordersTotal > 0) {
    if (customersProgress === customersTotal && ordersProgress === ordersTotal) {
      return 'finished';
    }

    return 'ready';
  }

  return 'nothing';
};
// EXTERNAL MODULE: external ["wp","url"]
var external_wp_url_ = __webpack_require__(16);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/actions.js


/**
 * External dependencies
 */








/**
 * Internal dependencies
 */



function HistoricalDataActions(_ref) {
  let {
    clearStatusAndTotalsCache,
    createNotice,
    dateFormat,
    importDate,
    onImportStarted,
    selectedPeriod,
    stopImport,
    skipChecked,
    status,
    setImportStarted,
    updateImportation
  } = _ref;

  const onStartImport = () => {
    const path = Object(external_wp_url_["addQueryArgs"])('/wc-analytics/reports/import', formatParams(dateFormat, selectedPeriod, skipChecked));

    const errorMessage = Object(external_wp_i18n_["__"])('There was a problem rebuilding your report data.', 'woocommerce-admin');

    const importStarted = true;
    makeQuery(path, errorMessage, importStarted);
    onImportStarted();
  };

  const onStopImport = () => {
    stopImport();
    const path = '/wc-analytics/reports/import/cancel';

    const errorMessage = Object(external_wp_i18n_["__"])('There was a problem stopping your current import.', 'woocommerce-admin');

    makeQuery(path, errorMessage);
  };

  const makeQuery = function (path, errorMessage) {
    let importStarted = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
    updateImportation(path, importStarted).then(response => {
      if (response.status === 'success') {
        createNotice('success', response.message);
      } else {
        createNotice('error', errorMessage);
        setImportStarted(false);
        stopImport();
      }
    }).catch(error => {
      if (error && error.message) {
        createNotice('error', error.message);
        setImportStarted(false);
        stopImport();
      }
    });
  };

  const deletePreviousData = () => {
    const path = '/wc-analytics/reports/import/delete';

    const errorMessage = Object(external_wp_i18n_["__"])('There was a problem deleting your previous data.', 'woocommerce-admin');

    makeQuery(path, errorMessage);
    Object(external_wc_tracks_["recordEvent"])('analytics_import_delete_previous');
    setImportStarted(false);
  };

  const reimportData = () => {
    setImportStarted(false); // We need to clear the cache of the selectors `getImportTotals` and `getImportStatus`

    clearStatusAndTotalsCache();
  };

  const getActions = () => {
    const importDisabled = status !== 'ready'; // An import is currently in progress

    if (['initializing', 'customers', 'orders', 'finalizing'].includes(status)) {
      return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        className: "woocommerce-settings-historical-data__action-button",
        isPrimary: true,
        onClick: onStopImport
      }, Object(external_wp_i18n_["__"])('Stop Import', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-setting__help woocommerce-settings-historical-data__action-help"
      }, Object(external_wp_i18n_["__"])('Imported data will not be lost if the import is stopped.', 'woocommerce-admin'), Object(external_wp_element_["createElement"])("br", null), Object(external_wp_i18n_["__"])('Navigating away from this page will not affect the import.', 'woocommerce-admin')));
    }

    if (['ready', 'nothing'].includes(status)) {
      if (importDate) {
        return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
          isPrimary: true,
          onClick: onStartImport,
          disabled: importDisabled
        }, Object(external_wp_i18n_["__"])('Start', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
          isSecondary: true,
          onClick: deletePreviousData
        }, Object(external_wp_i18n_["__"])('Delete Previously Imported Data', 'woocommerce-admin')));
      }

      return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        isPrimary: true,
        onClick: onStartImport,
        disabled: importDisabled
      }, Object(external_wp_i18n_["__"])('Start', 'woocommerce-admin')));
    }

    if (status === 'error') {
      createNotice('error', Object(external_wp_i18n_["__"])('Something went wrong with the importation process.', 'woocommerce-admin'));
    } // Has imported all possible data


    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isSecondary: true,
      onClick: reimportData
    }, Object(external_wp_i18n_["__"])('Re-import Data', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isSecondary: true,
      onClick: deletePreviousData
    }, Object(external_wp_i18n_["__"])('Delete Previously Imported Data', 'woocommerce-admin')));
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-settings__actions woocommerce-settings-historical-data__actions"
  }, getActions());
}

/* harmony default export */ var actions = (Object(external_wp_compose_["compose"])([Object(external_wp_data_["withSelect"])(select => {
  const {
    getFormSettings
  } = select(external_wc_data_["IMPORT_STORE_NAME"]);
  const {
    period: selectedPeriod,
    skipPrevious: skipChecked
  } = getFormSettings();
  return {
    selectedPeriod,
    skipChecked
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateImportation,
    setImportStarted
  } = dispatch(external_wc_data_["IMPORT_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice,
    setImportStarted,
    updateImportation
  };
})])(HistoricalDataActions));
// EXTERNAL MODULE: external ["wc","date"]
var external_wc_date_ = __webpack_require__(20);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/period-selector.js


/**
 * External dependencies
 */








function HistoricalDataPeriodSelector(_ref) {
  let {
    dateFormat,
    disabled,
    setImportPeriod,
    value
  } = _ref;

  const onSelectChange = val => {
    setImportPeriod(val);
  };

  const onDatePickerChange = val => {
    const dateModified = true;

    if (val.date && val.date.isValid) {
      setImportPeriod(val.date.format(dateFormat), dateModified);
    } else {
      setImportPeriod(val.text, dateModified);
    }
  };

  const getDatePickerError = momentDate => {
    if (!momentDate.isValid() || value.date.length !== dateFormat.length) {
      return external_wc_date_["dateValidationMessages"].invalid;
    }

    if (momentDate.isAfter(new Date(), 'day')) {
      return external_wc_date_["dateValidationMessages"].future;
    }

    return null;
  };

  const getDatePicker = () => {
    const momentDate = external_moment_default()(value.date, dateFormat);
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-settings-historical-data__column"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-settings-historical-data__column-label"
    }, Object(external_wp_i18n_["__"])('Beginning on', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_components_["DatePicker"], {
      date: momentDate.isValid() ? momentDate.toDate() : null,
      dateFormat: dateFormat,
      disabled: disabled,
      error: getDatePickerError(momentDate),
      isInvalidDate: date => external_moment_default()(date).isAfter(new Date(), 'day'),
      onUpdate: onDatePickerChange,
      text: value.date
    }));
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-settings-historical-data__columns"
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-settings-historical-data__column"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["SelectControl"], {
    label: Object(external_wp_i18n_["__"])('Import historical data', 'woocommerce-admin'),
    value: value.label,
    disabled: disabled,
    onChange: onSelectChange,
    options: [{
      label: 'All',
      value: 'all'
    }, {
      label: 'Last 365 days',
      value: '365'
    }, {
      label: 'Last 90 days',
      value: '90'
    }, {
      label: 'Last 30 days',
      value: '30'
    }, {
      label: 'Last 7 days',
      value: '7'
    }, {
      label: 'Last 24 hours',
      value: '1'
    }, {
      label: 'Custom',
      value: 'custom'
    }]
  })), value.label === 'custom' && getDatePicker());
}

/* harmony default export */ var period_selector = (Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    setImportPeriod
  } = dispatch(external_wc_data_["IMPORT_STORE_NAME"]);
  return {
    setImportPeriod
  };
})(HistoricalDataPeriodSelector));
// CONCATENATED MODULE: ./client/analytics/settings/historical-data/progress.js


/**
 * External dependencies
 */



function HistoricalDataProgress(_ref) {
  let {
    label,
    progress,
    total
  } = _ref;
  const labelText = Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('Imported %(label)s', 'woocommerce-admin'), {
    label
  });
  const labelCounters = !Object(external_lodash_["isNil"])(total) ? Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('%(progress)s of %(total)s', 'woocommerce-admin'), {
    progress: progress || 0,
    total
  }) : null;
  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-settings-historical-data__progress"
  }, Object(external_wp_element_["createElement"])("span", {
    className: "woocommerce-settings-historical-data__progress-label"
  }, labelText), labelCounters && Object(external_wp_element_["createElement"])("span", {
    className: "woocommerce-settings-historical-data__progress-label"
  }, labelCounters), Object(external_wp_element_["createElement"])("progress", {
    className: "woocommerce-settings-historical-data__progress-bar",
    max: total,
    value: progress || 0
  }));
}

/* harmony default export */ var historical_data_progress = (HistoricalDataProgress);
// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/status.js


/**
 * External dependencies
 */




const HISTORICAL_DATA_STATUS_FILTER = 'woocommerce_admin_import_status';

function HistoricalDataStatus(_ref) {
  let {
    importDate,
    status
  } = _ref;

  /**
   * Historical data import statuses.
   *
   * @filter woocommerce_admin_import_status
   *
   * @param {Object} statuses Import statuses.
   * @param {string} statuses.nothing Nothing to import.
   * @param {string} statuses.ready Ready to import.
   * @param {Array} statuses.initializing Initializing string and spinner.
   * @param {Array} statuses.customers Importing customers string and spinner.
   * @param {Array} statuses.orders Importing orders string and spinner.
   * @param {Array} statuses.finalizing Finalizing string and spinner.
   * @param {string} statuses.finished Message displayed after import.
   */
  const statusLabels = Object(external_wp_hooks_["applyFilters"])(HISTORICAL_DATA_STATUS_FILTER, {
    nothing: Object(external_wp_i18n_["__"])('Nothing To Import', 'woocommerce-admin'),
    ready: Object(external_wp_i18n_["__"])('Ready To Import', 'woocommerce-admin'),
    initializing: [Object(external_wp_i18n_["__"])('Initializing', 'woocommerce-admin'), Object(external_wp_element_["createElement"])(external_wp_components_["Spinner"], {
      key: "spinner"
    })],
    customers: [Object(external_wp_i18n_["__"])('Importing Customers', 'woocommerce-admin'), Object(external_wp_element_["createElement"])(external_wp_components_["Spinner"], {
      key: "spinner"
    })],
    orders: [Object(external_wp_i18n_["__"])('Importing Orders', 'woocommerce-admin'), Object(external_wp_element_["createElement"])(external_wp_components_["Spinner"], {
      key: "spinner"
    })],
    finalizing: [Object(external_wp_i18n_["__"])('Finalizing', 'woocommerce-admin'), Object(external_wp_element_["createElement"])(external_wp_components_["Spinner"], {
      key: "spinner"
    })],
    finished: importDate === -1 ? Object(external_wp_i18n_["__"])('All historical data imported', 'woocommerce-admin') : Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('Historical data from %s onward imported', 'woocommerce-admin'), // @todo The date formatting should be localized ( 'll' ), but this is currently broken in Gutenberg.
    // See https://github.com/WordPress/gutenberg/issues/12626 for details.
    external_moment_default()(importDate).format('YYYY-MM-DD'))
  });
  return Object(external_wp_element_["createElement"])("span", {
    className: "woocommerce-settings-historical-data__status"
  }, Object(external_wp_i18n_["__"])('Status:', 'woocommerce-admin') + ' ', statusLabels[status]);
}

/* harmony default export */ var historical_data_status = (HistoricalDataStatus);
// CONCATENATED MODULE: ./client/analytics/settings/historical-data/skip-checkbox.js


/**
 * External dependencies
 */





function HistoricalDataSkipCheckbox(_ref) {
  let {
    checked,
    disabled,
    setSkipPrevious
  } = _ref;

  const skipChange = value => {
    setSkipPrevious(value);
  };

  return Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
    className: "woocommerce-settings-historical-data__skip-checkbox",
    checked: checked,
    disabled: disabled,
    label: Object(external_wp_i18n_["__"])('Skip previously imported customers and orders', 'woocommerce-admin'),
    onChange: skipChange
  });
}

/* harmony default export */ var skip_checkbox = (Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    setSkipPrevious
  } = dispatch(external_wc_data_["IMPORT_STORE_NAME"]);
  return {
    setSkipPrevious
  };
})(HistoricalDataSkipCheckbox));
// EXTERNAL MODULE: ./client/analytics/settings/historical-data/style.scss
var style = __webpack_require__(580);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/layout.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */









class layout_HistoricalDataLayout extends external_wp_element_["Component"] {
  render() {
    const {
      customersProgress,
      customersTotal,
      dateFormat,
      importDate,
      inProgress,
      lastImportStartTimestamp,
      clearStatusAndTotalsCache,
      ordersProgress,
      ordersTotal,
      onImportStarted,
      period,
      stopImport,
      skipChecked,
      status
    } = this.props;
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wc_components_["SectionHeader"], {
      title: Object(external_wp_i18n_["__"])('Import historical data', 'woocommerce-admin')
    }), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-settings__wrapper"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-setting"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-setting__input"
    }, Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-setting__help"
    }, Object(external_wp_i18n_["__"])('This tool populates historical analytics data by processing customers ' + 'and orders created prior to activating WooCommerce Admin.', 'woocommerce-admin')), status !== 'finished' && Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(period_selector, {
      dateFormat: dateFormat,
      disabled: inProgress,
      value: period
    }), Object(external_wp_element_["createElement"])(skip_checkbox, {
      disabled: inProgress,
      checked: skipChecked
    }), Object(external_wp_element_["createElement"])(historical_data_progress, {
      label: Object(external_wp_i18n_["__"])('Registered Customers', 'woocommerce-admin'),
      progress: customersProgress,
      total: customersTotal
    }), Object(external_wp_element_["createElement"])(historical_data_progress, {
      label: Object(external_wp_i18n_["__"])('Orders and Refunds', 'woocommerce-admin'),
      progress: ordersProgress,
      total: ordersTotal
    })), Object(external_wp_element_["createElement"])(historical_data_status, {
      importDate: importDate,
      status: status
    })))), Object(external_wp_element_["createElement"])(actions, {
      clearStatusAndTotalsCache: clearStatusAndTotalsCache,
      dateFormat: dateFormat,
      importDate: importDate,
      lastImportStartTimestamp: lastImportStartTimestamp,
      onImportStarted: onImportStarted,
      stopImport: stopImport,
      status: status
    }));
  }

}

/* harmony default export */ var layout = (Object(external_wp_data_["withSelect"])((select, props) => {
  const {
    getImportError,
    getImportStatus,
    getImportTotals
  } = select(external_wc_data_["IMPORT_STORE_NAME"]);
  const {
    activeImport,
    cacheNeedsClearing,
    dateFormat,
    inProgress,
    onImportStarted,
    onImportFinished,
    period,
    startStatusCheckInterval,
    skipChecked
  } = props;
  const params = formatParams(dateFormat, period, skipChecked);
  const {
    customers,
    orders,
    lastImportStartTimestamp
  } = getImportTotals(params);
  const {
    customers: customersStatus,
    imported_from: importDate,
    is_importing: isImporting,
    orders: ordersStatus
  } = getImportStatus(lastImportStartTimestamp);
  const {
    imported: customersProgress,
    total: customersTotal
  } = customersStatus || {};
  const {
    imported: ordersProgress,
    total: ordersTotal
  } = ordersStatus || {};
  const isError = Boolean(getImportError(lastImportStartTimestamp) || getImportError(params));
  const hasImportStarted = Boolean(!lastImportStartTimestamp && !inProgress && isImporting === true);

  if (hasImportStarted) {
    onImportStarted();
  }

  const hasImportFinished = Boolean(inProgress && !cacheNeedsClearing && isImporting === false && (customersTotal > 0 || ordersTotal > 0) && customersProgress === customersTotal && ordersProgress === ordersTotal);
  let response = {
    customersTotal: customers,
    isError,
    ordersTotal: orders
  };

  if (activeImport) {
    response = {
      cacheNeedsClearing,
      customersProgress,
      customersTotal: Object(external_lodash_["isNil"])(customersTotal) ? customers : customersTotal,
      inProgress,
      isError,
      ordersProgress,
      ordersTotal: Object(external_lodash_["isNil"])(ordersTotal) ? orders : ordersTotal
    };
  }

  const status = getStatus(response);

  if (status === 'initializing') {
    startStatusCheckInterval();
  }

  if (hasImportFinished) {
    onImportFinished();
  }

  return { ...response,
    importDate,
    status
  };
})(layout_HistoricalDataLayout));
// CONCATENATED MODULE: ./client/analytics/settings/historical-data/index.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */




class historical_data_HistoricalData extends external_wp_element_["Component"] {
  constructor() {
    super(...arguments);
    this.dateFormat = Object(external_wp_i18n_["__"])('MM/DD/YYYY', 'woocommerce-admin');
    this.intervalId = -1;
    this.lastImportStopTimestamp = 0;
    this.cacheNeedsClearing = true;
    this.onImportFinished = this.onImportFinished.bind(this);
    this.onImportStarted = this.onImportStarted.bind(this);
    this.clearStatusAndTotalsCache = this.clearStatusAndTotalsCache.bind(this);
    this.stopImport = this.stopImport.bind(this);
    this.startStatusCheckInterval = this.startStatusCheckInterval.bind(this);
    this.cancelStatusCheckInterval = this.cancelStatusCheckInterval.bind(this);
  }

  startStatusCheckInterval() {
    if (this.intervalId < 0) {
      this.cacheNeedsClearing = true;
      this.intervalId = setInterval(() => {
        this.clearCache('getImportStatus');
      }, 3 * external_wc_data_["SECOND"]);
    }
  }

  cancelStatusCheckInterval() {
    clearInterval(this.intervalId);
    this.intervalId = -1;
  }

  clearCache(resolver, query) {
    const {
      invalidateResolution,
      lastImportStartTimestamp
    } = this.props;
    const preparedQuery = resolver === 'getImportStatus' ? lastImportStartTimestamp : query;
    invalidateResolution(resolver, [preparedQuery]).then(() => {
      this.cacheNeedsClearing = false;
    });
  }

  stopImport() {
    this.cancelStatusCheckInterval();
    this.lastImportStopTimestamp = Date.now();
  }

  onImportFinished() {
    const {
      debouncedSpeak
    } = this.props;

    if (!this.cacheNeedsClearing) {
      debouncedSpeak('Import complete');
      this.stopImport();
    }
  }

  onImportStarted() {
    const {
      notes,
      setImportStarted,
      updateNote
    } = this.props;
    const historicalDataNote = notes.find(note => note.name === 'wc-admin-historical-data');

    if (historicalDataNote) {
      updateNote(historicalDataNote.id, {
        status: 'actioned'
      });
    }

    setImportStarted(true);
  }

  clearStatusAndTotalsCache() {
    const {
      selectedPeriod,
      skipChecked
    } = this.props;
    const params = formatParams(this.dateFormat, selectedPeriod, skipChecked);
    this.clearCache('getImportTotals', params);
    this.clearCache('getImportStatus');
  }

  isImportationInProgress() {
    const {
      lastImportStartTimestamp
    } = this.props;
    return typeof lastImportStartTimestamp !== 'undefined' && typeof this.lastImportStopTimestamp === 'undefined' || lastImportStartTimestamp > this.lastImportStopTimestamp;
  }

  render() {
    const {
      activeImport,
      createNotice,
      lastImportStartTimestamp,
      selectedPeriod,
      skipChecked
    } = this.props;
    return Object(external_wp_element_["createElement"])(layout, {
      activeImport: activeImport,
      cacheNeedsClearing: this.cacheNeedsClearing,
      createNotice: createNotice,
      dateFormat: this.dateFormat,
      inProgress: this.isImportationInProgress(),
      onImportFinished: this.onImportFinished,
      onImportStarted: this.onImportStarted,
      lastImportStartTimestamp: lastImportStartTimestamp,
      clearStatusAndTotalsCache: this.clearStatusAndTotalsCache,
      period: selectedPeriod,
      skipChecked: skipChecked,
      startStatusCheckInterval: this.startStatusCheckInterval,
      stopImport: this.stopImport
    });
  }

}

/* harmony default export */ var historical_data = (Object(external_wp_compose_["compose"])([Object(external_wp_data_["withSelect"])(select => {
  const {
    getNotes
  } = select(external_wc_data_["NOTES_STORE_NAME"]);
  const {
    getImportStarted,
    getFormSettings
  } = select(external_wc_data_["IMPORT_STORE_NAME"]);
  const notesQuery = {
    page: 1,
    per_page: external_wc_data_["QUERY_DEFAULTS"].pageSize,
    type: 'update',
    status: 'unactioned'
  };
  const notes = getNotes(notesQuery);
  const {
    activeImport,
    lastImportStartTimestamp
  } = getImportStarted();
  const {
    period: selectedPeriod,
    skipPrevious: skipChecked
  } = getFormSettings();
  return {
    activeImport,
    lastImportStartTimestamp,
    notes,
    selectedPeriod,
    skipChecked
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateNote
  } = dispatch(external_wc_data_["NOTES_STORE_NAME"]);
  const {
    invalidateResolution,
    setImportStarted
  } = dispatch(external_wc_data_["IMPORT_STORE_NAME"]);
  return {
    invalidateResolution,
    setImportStarted,
    updateNote
  };
}), external_wp_components_["withSpokenMessages"]])(historical_data_HistoricalData));
// CONCATENATED MODULE: ./client/analytics/settings/index.js



/**
 * External dependencies
 */








/**
 * Internal dependencies
 */






const Settings = _ref => {
  let {
    createNotice,
    query
  } = _ref;
  const {
    settingsError,
    isRequesting,
    isDirty,
    persistSettings,
    updateAndPersistSettings,
    updateSettings,
    wcAdminSettings
  } = Object(external_wc_data_["useSettings"])('wc_admin', ['wcAdminSettings']);
  const hasSaved = Object(external_wp_element_["useRef"])(false);
  Object(external_wp_element_["useEffect"])(() => {
    function warnIfUnsavedChanges(event) {
      if (isDirty) {
        event.returnValue = Object(external_wp_i18n_["__"])('You have unsaved changes. If you proceed, they will be lost.', 'woocommerce-admin');
        return event.returnValue;
      }
    }

    window.addEventListener('beforeunload', warnIfUnsavedChanges);
    return () => window.removeEventListener('beforeunload', warnIfUnsavedChanges);
  }, [isDirty]);
  Object(external_wp_element_["useEffect"])(() => {
    if (isRequesting) {
      hasSaved.current = true;
      return;
    }

    if (!isRequesting && hasSaved.current) {
      if (!settingsError) {
        createNotice('success', Object(external_wp_i18n_["__"])('Your settings have been successfully saved.', 'woocommerce-admin'));
      } else {
        createNotice('error', Object(external_wp_i18n_["__"])('There was an error saving your settings. Please try again.', 'woocommerce-admin'));
      }

      hasSaved.current = false;
    }
  }, [isRequesting, settingsError, createNotice]);

  const resetDefaults = () => {
    if ( // eslint-disable-next-line no-alert
    window.confirm(Object(external_wp_i18n_["__"])('Are you sure you want to reset all settings to default values?', 'woocommerce-admin'))) {
      const resetSettings = Object.keys(config["b" /* config */]).reduce((result, setting) => {
        result[setting] = config["b" /* config */][setting].defaultValue;
        return result;
      }, {});
      updateAndPersistSettings('wcAdminSettings', resetSettings);
      Object(external_wc_tracks_["recordEvent"])('analytics_settings_reset_defaults');
    }
  };

  const saveChanges = () => {
    persistSettings();
    Object(external_wc_tracks_["recordEvent"])('analytics_settings_save', wcAdminSettings); // On save, reset persisted query properties of Nav Menu links to default

    query.period = undefined;
    query.compare = undefined;
    query.before = undefined;
    query.after = undefined;
    query.interval = undefined;
    query.type = undefined;
    window.wpNavMenuUrlUpdate(query);
  };

  const handleInputChange = e => {
    const {
      checked,
      name,
      type,
      value
    } = e.target;
    const nextSettings = { ...wcAdminSettings
    };

    if (type === 'checkbox') {
      if (checked) {
        nextSettings[name] = [...nextSettings[name], value];
      } else {
        nextSettings[name] = nextSettings[name].filter(v => v !== value);
      }
    } else {
      nextSettings[name] = value;
    }

    updateSettings('wcAdminSettings', nextSettings);
  };

  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wc_components_["SectionHeader"], {
    title: Object(external_wp_i18n_["__"])('Analytics settings', 'woocommerce-admin')
  }), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-settings__wrapper"
  }, Object.keys(config["b" /* config */]).map(setting => Object(external_wp_element_["createElement"])(analytics_settings_setting, extends_default()({
    handleChange: handleInputChange,
    value: wcAdminSettings[setting],
    key: setting,
    name: setting
  }, config["b" /* config */][setting]))), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-settings__actions"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isSecondary: true,
    onClick: resetDefaults
  }, Object(external_wp_i18n_["__"])('Reset defaults', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isPrimary: true,
    isBusy: isRequesting,
    onClick: saveChanges
  }, Object(external_wp_i18n_["__"])('Save settings', 'woocommerce-admin')))), query.import === 'true' ? Object(external_wp_element_["createElement"])(external_wc_components_["ScrollTo"], {
    offset: "-56"
  }, Object(external_wp_element_["createElement"])(historical_data, {
    createNotice: createNotice
  })) : Object(external_wp_element_["createElement"])(historical_data, {
    createNotice: createNotice
  }));
};

/* harmony default export */ var analytics_settings = __webpack_exports__["default"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice
  };
}))(Settings));

/***/ })

}]);