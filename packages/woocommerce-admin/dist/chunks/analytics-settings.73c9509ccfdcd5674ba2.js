(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["analytics-settings"],{

/***/ "./client/analytics/settings/historical-data/actions.js":
/*!**************************************************************!*\
  !*** ./client/analytics/settings/historical-data/actions.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");


/**
 * External dependencies
 */




function HistoricalDataActions(_ref) {
  var importDate = _ref.importDate,
      onDeletePreviousData = _ref.onDeletePreviousData,
      onReimportData = _ref.onReimportData,
      onStartImport = _ref.onStartImport,
      onStopImport = _ref.onStopImport,
      status = _ref.status;

  var getActions = function getActions() {
    var importDisabled = status !== 'ready'; // An import is currently in progress

    if (['initializing', 'customers', 'orders', 'finalizing'].includes(status)) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Button"], {
        className: "woocommerce-settings-historical-data__action-button",
        isPrimary: true,
        onClick: onStopImport
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Stop Import', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
        className: "woocommerce-setting__help woocommerce-settings-historical-data__action-help"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Imported data will not be lost if the import is stopped.', 'woocommerce'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Navigating away from this page will not affect the import.', 'woocommerce')));
    }

    if (['ready', 'nothing'].includes(status)) {
      if (importDate) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Button"], {
          isPrimary: true,
          onClick: onStartImport,
          disabled: importDisabled
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Start', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Button"], {
          isSecondary: true,
          onClick: onDeletePreviousData
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Delete Previously Imported Data', 'woocommerce')));
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Button"], {
        isPrimary: true,
        onClick: onStartImport,
        disabled: importDisabled
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Start', 'woocommerce')));
    } // Has imported all possible data


    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Button"], {
      isSecondary: true,
      onClick: onReimportData
    }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Re-import Data', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Button"], {
      isSecondary: true,
      onClick: onDeletePreviousData
    }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Delete Previously Imported Data', 'woocommerce')));
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-settings__actions woocommerce-settings-historical-data__actions"
  }, getActions());
}

/* harmony default export */ __webpack_exports__["default"] = (HistoricalDataActions);

/***/ }),

/***/ "./client/analytics/settings/historical-data/index.js":
/*!************************************************************!*\
  !*** ./client/analytics/settings/historical-data/index.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! moment */ "moment");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./utils */ "./client/analytics/settings/historical-data/utils.js");
/* harmony import */ var _layout__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./layout */ "./client/analytics/settings/historical-data/layout.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");









function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */








/**
 * Internal dependencies
 */







var HistoricalData = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(HistoricalData, _Component);

  var _super = _createSuper(HistoricalData);

  function HistoricalData() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, HistoricalData);

    _this = _super.apply(this, arguments);
    _this.dateFormat = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('MM/DD/YYYY', 'woocommerce');
    _this.state = {
      // Whether there is an active import (which might have been stopped)
      // that matches the period and skipChecked settings
      activeImport: null,
      lastImportStartTimestamp: 0,
      lastImportStopTimestamp: 0,
      period: {
        date: moment__WEBPACK_IMPORTED_MODULE_12___default()().format(_this.dateFormat),
        label: 'all'
      },
      skipChecked: true
    };
    _this.makeQuery = _this.makeQuery.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onImportFinished = _this.onImportFinished.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onImportStarted = _this.onImportStarted.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onDeletePreviousData = _this.onDeletePreviousData.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onReimportData = _this.onReimportData.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onStartImport = _this.onStartImport.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onStopImport = _this.onStopImport.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onDateChange = _this.onDateChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onPeriodChange = _this.onPeriodChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onSkipChange = _this.onSkipChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(HistoricalData, [{
    key: "makeQuery",
    value: function makeQuery(path, errorMessage) {
      var _this2 = this;

      var createNotice = this.props.createNotice;
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
        path: path,
        method: 'POST'
      }).then(function (response) {
        if (response.status === 'success') {
          createNotice('success', response.message);
        } else {
          createNotice('error', errorMessage);

          _this2.setState({
            activeImport: false,
            lastImportStopTimestamp: Date.now()
          });
        }
      }).catch(function (error) {
        if (error && error.message) {
          createNotice('error', error.message);

          _this2.setState({
            activeImport: false,
            lastImportStopTimestamp: Date.now()
          });
        }
      });
    }
  }, {
    key: "onImportFinished",
    value: function onImportFinished() {
      var debouncedSpeak = this.props.debouncedSpeak;
      debouncedSpeak('Import complete');
      this.setState({
        lastImportStopTimestamp: Date.now()
      });
    }
  }, {
    key: "onImportStarted",
    value: function onImportStarted() {
      var _this$props = this.props,
          notes = _this$props.notes,
          updateNote = _this$props.updateNote;
      var historicalDataNote = notes.find(function (note) {
        return note.name === 'wc-admin-historical-data';
      });

      if (historicalDataNote) {
        updateNote(historicalDataNote.id, {
          status: 'actioned'
        });
      }

      this.setState({
        activeImport: true,
        lastImportStartTimestamp: Date.now()
      });
    }
  }, {
    key: "onDeletePreviousData",
    value: function onDeletePreviousData() {
      var path = '/wc-analytics/reports/import/delete';

      var errorMessage = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('There was a problem deleting your previous data.', 'woocommerce');

      this.makeQuery(path, errorMessage);
      this.setState({
        activeImport: false
      });
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_18__["recordEvent"])('analytics_import_delete_previous');
    }
  }, {
    key: "onReimportData",
    value: function onReimportData() {
      this.setState({
        activeImport: false
      });
    }
  }, {
    key: "onStartImport",
    value: function onStartImport() {
      var _this$state = this.state,
          period = _this$state.period,
          skipChecked = _this$state.skipChecked;
      var path = Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__["addQueryArgs"])('/wc-analytics/reports/import', Object(_utils__WEBPACK_IMPORTED_MODULE_15__["formatParams"])(this.dateFormat, period, skipChecked));

      var errorMessage = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('There was a problem rebuilding your report data.', 'woocommerce');

      this.makeQuery(path, errorMessage);
      this.onImportStarted();
    }
  }, {
    key: "onStopImport",
    value: function onStopImport() {
      this.setState({
        lastImportStopTimestamp: Date.now()
      });
      var path = '/wc-analytics/reports/import/cancel';

      var errorMessage = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('There was a problem stopping your current import.', 'woocommerce');

      this.makeQuery(path, errorMessage);
    }
  }, {
    key: "onPeriodChange",
    value: function onPeriodChange(val) {
      this.setState({
        activeImport: false,
        period: _objectSpread(_objectSpread({}, this.state.period), {}, {
          label: val
        })
      });
    }
  }, {
    key: "onDateChange",
    value: function onDateChange(val) {
      this.setState({
        activeImport: false,
        period: {
          date: val,
          label: 'custom'
        }
      });
    }
  }, {
    key: "onSkipChange",
    value: function onSkipChange(val) {
      this.setState({
        activeImport: false,
        skipChecked: val
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state2 = this.state,
          activeImport = _this$state2.activeImport,
          lastImportStartTimestamp = _this$state2.lastImportStartTimestamp,
          lastImportStopTimestamp = _this$state2.lastImportStopTimestamp,
          period = _this$state2.period,
          skipChecked = _this$state2.skipChecked;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_layout__WEBPACK_IMPORTED_MODULE_16__["default"], {
        activeImport: activeImport,
        dateFormat: this.dateFormat,
        onImportFinished: this.onImportFinished,
        onImportStarted: this.onImportStarted,
        lastImportStartTimestamp: lastImportStartTimestamp,
        lastImportStopTimestamp: lastImportStopTimestamp,
        onPeriodChange: this.onPeriodChange,
        onDateChange: this.onDateChange,
        onSkipChange: this.onSkipChange,
        onDeletePreviousData: this.onDeletePreviousData,
        onReimportData: this.onReimportData,
        onStartImport: this.onStartImport,
        onStopImport: this.onStopImport,
        period: period,
        skipChecked: skipChecked
      });
    }
  }]);

  return HistoricalData;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_11__["compose"])([Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__["default"])(function (select) {
  var _select = select('wc-api'),
      getNotes = _select.getNotes;

  var notesQuery = {
    page: 1,
    per_page: wc_api_constants__WEBPACK_IMPORTED_MODULE_17__["QUERY_DEFAULTS"].pageSize,
    type: 'update',
    status: 'unactioned'
  };
  var notes = getNotes(notesQuery);
  return {
    notes: notes
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateNote = _dispatch.updateNote;

  return {
    updateNote: updateNote
  };
}), _wordpress_components__WEBPACK_IMPORTED_MODULE_14__["withSpokenMessages"]])(HistoricalData));

/***/ }),

/***/ "./client/analytics/settings/historical-data/layout.js":
/*!*************************************************************!*\
  !*** ./client/analytics/settings/historical-data/layout.js ***!
  \*************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _fresh_data_framework__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @fresh-data/framework */ "./node_modules/@fresh-data/framework/es/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./utils */ "./client/analytics/settings/historical-data/utils.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./actions */ "./client/analytics/settings/historical-data/actions.js");
/* harmony import */ var _period_selector__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./period-selector */ "./client/analytics/settings/historical-data/period-selector.js");
/* harmony import */ var _progress__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./progress */ "./client/analytics/settings/historical-data/progress.js");
/* harmony import */ var _status__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./status */ "./client/analytics/settings/historical-data/status.js");
/* harmony import */ var _skip_checkbox__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./skip-checkbox */ "./client/analytics/settings/historical-data/skip-checkbox.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./style.scss */ "./client/analytics/settings/historical-data/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_18__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */











var HistoricalDataLayout = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(HistoricalDataLayout, _Component);

  var _super = _createSuper(HistoricalDataLayout);

  function HistoricalDataLayout() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, HistoricalDataLayout);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(HistoricalDataLayout, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          customersProgress = _this$props.customersProgress,
          customersTotal = _this$props.customersTotal,
          dateFormat = _this$props.dateFormat,
          importDate = _this$props.importDate,
          inProgress = _this$props.inProgress,
          onPeriodChange = _this$props.onPeriodChange,
          onDateChange = _this$props.onDateChange,
          onSkipChange = _this$props.onSkipChange,
          onDeletePreviousData = _this$props.onDeletePreviousData,
          onReimportData = _this$props.onReimportData,
          onStartImport = _this$props.onStartImport,
          onStopImport = _this$props.onStopImport,
          ordersProgress = _this$props.ordersProgress,
          ordersTotal = _this$props.ordersTotal,
          period = _this$props.period,
          skipChecked = _this$props.skipChecked;
      var status = Object(_utils__WEBPACK_IMPORTED_MODULE_11__["getStatus"])({
        customersProgress: customersProgress,
        customersTotal: customersTotal,
        inProgress: inProgress,
        ordersProgress: ordersProgress,
        ordersTotal: ordersTotal
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["SectionHeader"], {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Import Historical Data', 'woocommerce')
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-settings__wrapper"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-setting"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-setting__input"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", {
        className: "woocommerce-setting__help"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('This tool populates historical analytics data by processing customers ' + 'and orders created prior to activating WooCommerce Admin.', 'woocommerce')), status !== 'finished' && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_period_selector__WEBPACK_IMPORTED_MODULE_13__["default"], {
        dateFormat: dateFormat,
        disabled: inProgress,
        onPeriodChange: onPeriodChange,
        onDateChange: onDateChange,
        value: period
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_skip_checkbox__WEBPACK_IMPORTED_MODULE_16__["default"], {
        disabled: inProgress,
        checked: skipChecked,
        onChange: onSkipChange
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_progress__WEBPACK_IMPORTED_MODULE_14__["default"], {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Registered Customers', 'woocommerce'),
        progress: customersProgress,
        total: customersTotal
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_progress__WEBPACK_IMPORTED_MODULE_14__["default"], {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Orders and Refunds', 'woocommerce'),
        progress: ordersProgress,
        total: ordersTotal
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_status__WEBPACK_IMPORTED_MODULE_15__["default"], {
        importDate: importDate,
        status: status
      })))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_actions__WEBPACK_IMPORTED_MODULE_12__["default"], {
        importDate: importDate,
        onDeletePreviousData: onDeletePreviousData,
        onReimportData: onReimportData,
        onStartImport: onStartImport,
        onStopImport: onStopImport,
        status: status
      }));
    }
  }]);

  return HistoricalDataLayout;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_17__["default"])(function (select, props) {
  var _select = select('wc-api'),
      getImportStatus = _select.getImportStatus,
      isGetImportStatusRequesting = _select.isGetImportStatusRequesting,
      getImportTotals = _select.getImportTotals;

  var activeImport = props.activeImport,
      dateFormat = props.dateFormat,
      lastImportStartTimestamp = props.lastImportStartTimestamp,
      lastImportStopTimestamp = props.lastImportStopTimestamp,
      onImportStarted = props.onImportStarted,
      onImportFinished = props.onImportFinished,
      period = props.period,
      skipChecked = props.skipChecked;
  var inProgress = typeof lastImportStartTimestamp !== 'undefined' && typeof lastImportStopTimestamp === 'undefined' || lastImportStartTimestamp > lastImportStopTimestamp;
  var params = Object(_utils__WEBPACK_IMPORTED_MODULE_11__["formatParams"])(dateFormat, period, skipChecked); // Use timestamp to invalidate previous totals when the import finished/stopped

  var _getImportTotals = getImportTotals(params, lastImportStopTimestamp),
      customers = _getImportTotals.customers,
      orders = _getImportTotals.orders;

  var requirement = inProgress ? {
    freshness: 3 * _fresh_data_framework__WEBPACK_IMPORTED_MODULE_8__["SECOND"],
    timeout: 3 * _fresh_data_framework__WEBPACK_IMPORTED_MODULE_8__["SECOND"]
  } : wc_api_constants__WEBPACK_IMPORTED_MODULE_10__["DEFAULT_REQUIREMENT"]; // Use timestamp to invalidate previous status when a new import starts

  var _getImportStatus = getImportStatus(lastImportStartTimestamp, requirement),
      customersStatus = _getImportStatus.customers,
      importDate = _getImportStatus.imported_from,
      isImporting = _getImportStatus.is_importing,
      ordersStatus = _getImportStatus.orders;

  var _ref = customersStatus || {},
      customersProgress = _ref.imported,
      customersTotal = _ref.total;

  var _ref2 = ordersStatus || {},
      ordersProgress = _ref2.imported,
      ordersTotal = _ref2.total;

  var isStatusLoading = isGetImportStatusRequesting(lastImportStartTimestamp);
  var hasImportStarted = Boolean(!lastImportStartTimestamp && !isStatusLoading && !inProgress && isImporting === true);

  if (hasImportStarted) {
    onImportStarted();
  }

  var hasImportFinished = Boolean(!isStatusLoading && inProgress && isImporting === false && (customersProgress === customersTotal && customersTotal > 0 || ordersProgress === ordersTotal && ordersTotal > 0));

  if (hasImportFinished) {
    onImportFinished();
  }

  if (!activeImport) {
    return {
      customersTotal: customers,
      importDate: importDate,
      ordersTotal: orders
    };
  }

  return {
    customersProgress: customersProgress,
    customersTotal: Object(lodash__WEBPACK_IMPORTED_MODULE_7__["isNil"])(customersTotal) ? customers : customersTotal,
    importDate: importDate,
    inProgress: inProgress,
    ordersProgress: ordersProgress,
    ordersTotal: Object(lodash__WEBPACK_IMPORTED_MODULE_7__["isNil"])(ordersTotal) ? orders : ordersTotal
  };
})(HistoricalDataLayout));

/***/ }),

/***/ "./client/analytics/settings/historical-data/period-selector.js":
/*!**********************************************************************!*\
  !*** ./client/analytics/settings/historical-data/period-selector.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! moment */ "moment");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! lib/date */ "./client/lib/date.js");


/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */




function HistoricalDataPeriodSelector(_ref) {
  var dateFormat = _ref.dateFormat,
      disabled = _ref.disabled,
      onDateChange = _ref.onDateChange,
      onPeriodChange = _ref.onPeriodChange,
      value = _ref.value;

  var onSelectChange = function onSelectChange(val) {
    onPeriodChange(val);
  };

  var onDatePickerChange = function onDatePickerChange(val) {
    if (val.date && val.date.isValid) {
      onDateChange(val.date.format(dateFormat));
    } else {
      onDateChange(val.text);
    }
  };

  var getDatePickerError = function getDatePickerError(momentDate) {
    if (!momentDate.isValid() || value.date.length !== dateFormat.length) {
      return lib_date__WEBPACK_IMPORTED_MODULE_5__["dateValidationMessages"].invalid;
    }

    if (momentDate.isAfter(new Date(), 'day')) {
      return lib_date__WEBPACK_IMPORTED_MODULE_5__["dateValidationMessages"].future;
    }

    return null;
  };

  var getDatePicker = function getDatePicker() {
    var momentDate = moment__WEBPACK_IMPORTED_MODULE_2___default()(value.date, dateFormat);
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-settings-historical-data__column"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-settings-historical-data__column-label"
    }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Beginning on', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_4__["DatePicker"], {
      date: momentDate.isValid() ? momentDate.toDate() : null,
      dateFormat: dateFormat,
      disabled: disabled,
      error: getDatePickerError(momentDate),
      isInvalidDate: function isInvalidDate(date) {
        return moment__WEBPACK_IMPORTED_MODULE_2___default()(date).isAfter(new Date(), 'day');
      },
      onUpdate: onDatePickerChange,
      text: value.date
    }));
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-settings-historical-data__columns"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-settings-historical-data__column"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["SelectControl"], {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Import Historical Data', 'woocommerce'),
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

/* harmony default export */ __webpack_exports__["default"] = (HistoricalDataPeriodSelector);

/***/ }),

/***/ "./client/analytics/settings/historical-data/progress.js":
/*!***************************************************************!*\
  !*** ./client/analytics/settings/historical-data/progress.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);


/**
 * External dependencies
 */



function HistoricalDataProgress(_ref) {
  var label = _ref.label,
      progress = _ref.progress,
      total = _ref.total;
  var labelText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Imported %(label)s', 'woocommerce'), {
    label: label
  });
  var labelCounters = !Object(lodash__WEBPACK_IMPORTED_MODULE_2__["isNil"])(total) ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('%(progress)s of %(total)s', 'woocommerce'), {
    progress: progress || 0,
    total: total
  }) : null;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-settings-historical-data__progress"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: "woocommerce-settings-historical-data__progress-label"
  }, labelText), labelCounters && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: "woocommerce-settings-historical-data__progress-label"
  }, labelCounters), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("progress", {
    className: "woocommerce-settings-historical-data__progress-bar",
    max: total,
    value: progress || 0
  }));
}

/* harmony default export */ __webpack_exports__["default"] = (HistoricalDataProgress);

/***/ }),

/***/ "./client/analytics/settings/historical-data/skip-checkbox.js":
/*!********************************************************************!*\
  !*** ./client/analytics/settings/historical-data/skip-checkbox.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");


/**
 * External dependencies
 */



function HistoricalDataSkipCheckbox(_ref) {
  var checked = _ref.checked,
      disabled = _ref.disabled,
      onChange = _ref.onChange;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["CheckboxControl"], {
    className: "woocommerce-settings-historical-data__skip-checkbox",
    checked: checked,
    disabled: disabled,
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Skip previously imported customers and orders', 'woocommerce'),
    onChange: onChange
  });
}

/* harmony default export */ __webpack_exports__["default"] = (HistoricalDataSkipCheckbox);

/***/ }),

/***/ "./client/analytics/settings/historical-data/status.js":
/*!*************************************************************!*\
  !*** ./client/analytics/settings/historical-data/status.js ***!
  \*************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! moment */ "moment");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__);


/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */


var HISTORICAL_DATA_STATUS_FILTER = 'woocommerce_admin_import_status';

function HistoricalDataStatus(_ref) {
  var importDate = _ref.importDate,
      status = _ref.status;
  var statusLabels = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__["applyFilters"])(HISTORICAL_DATA_STATUS_FILTER, {
    nothing: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Nothing To Import', 'woocommerce'),
    ready: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Ready To Import', 'woocommerce'),
    initializing: [Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Initializing', 'woocommerce'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__["Spinner"], {
      key: "spinner"
    })],
    customers: [Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Importing Customers', 'woocommerce'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__["Spinner"], {
      key: "spinner"
    })],
    orders: [Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Importing Orders', 'woocommerce'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__["Spinner"], {
      key: "spinner"
    })],
    finalizing: [Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Finalizing', 'woocommerce'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__["Spinner"], {
      key: "spinner"
    })],
    finished: importDate === -1 ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('All historical data imported', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Historical data from %s onward imported', 'woocommerce'), // @todo The date formatting should be localized ( 'll' ), but this is currently broken in Gutenberg.
    // See https://github.com/WordPress/gutenberg/issues/12626 for details.
    moment__WEBPACK_IMPORTED_MODULE_3___default()(importDate).format('YYYY-MM-DD'))
  });
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: "woocommerce-settings-historical-data__status"
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Status:', 'woocommerce') + ' ', statusLabels[status]);
}

/* harmony default export */ __webpack_exports__["default"] = (Object(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__["useFilters"])(HISTORICAL_DATA_STATUS_FILTER)(HistoricalDataStatus));

/***/ }),

/***/ "./client/analytics/settings/historical-data/style.scss":
/*!**************************************************************!*\
  !*** ./client/analytics/settings/historical-data/style.scss ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/analytics/settings/historical-data/utils.js":
/*!************************************************************!*\
  !*** ./client/analytics/settings/historical-data/utils.js ***!
  \************************************************************/
/*! exports provided: formatParams, getStatus */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "formatParams", function() { return formatParams; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getStatus", function() { return getStatus; });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! moment */ "moment");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_1__);
/**
 * External dependencies
 */


var formatParams = function formatParams(dateFormat, period, skipChecked) {
  var params = {};

  if (skipChecked) {
    params.skip_existing = true;
  }

  if (period.label !== 'all') {
    if (period.label === 'custom') {
      var daysDifference = moment__WEBPACK_IMPORTED_MODULE_1___default()().diff(moment__WEBPACK_IMPORTED_MODULE_1___default()(period.date, dateFormat), 'days', true);
      params.days = Math.floor(daysDifference);
    } else {
      params.days = parseInt(period.label, 10);
    }
  }

  return params;
};
var getStatus = function getStatus(_ref) {
  var customersProgress = _ref.customersProgress,
      customersTotal = _ref.customersTotal,
      inProgress = _ref.inProgress,
      ordersProgress = _ref.ordersProgress,
      ordersTotal = _ref.ordersTotal;

  if (inProgress) {
    if (Object(lodash__WEBPACK_IMPORTED_MODULE_0__["isNil"])(customersProgress) || Object(lodash__WEBPACK_IMPORTED_MODULE_0__["isNil"])(ordersProgress) || Object(lodash__WEBPACK_IMPORTED_MODULE_0__["isNil"])(customersTotal) || Object(lodash__WEBPACK_IMPORTED_MODULE_0__["isNil"])(ordersTotal)) {
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

/***/ }),

/***/ "./client/analytics/settings/index.js":
/*!********************************************!*\
  !*** ./client/analytics/settings/index.js ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./index.scss */ "./client/analytics/settings/index.scss");
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_index_scss__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _config__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./config */ "./client/analytics/settings/config.js");
/* harmony import */ var _setting__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./setting */ "./client/analytics/settings/setting.js");
/* harmony import */ var _historical_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./historical-data */ "./client/analytics/settings/historical-data/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");





function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */






var SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';

var Settings = function Settings(_ref) {
  var createNotice = _ref.createNotice,
      query = _ref.query;

  var _useSettings = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_9__["useSettings"])('wc_admin', ['wcAdminSettings']),
      settingsError = _useSettings.settingsError,
      isRequesting = _useSettings.isRequesting,
      isDirty = _useSettings.isDirty,
      persistSettings = _useSettings.persistSettings,
      updateAndPersistSettings = _useSettings.updateAndPersistSettings,
      updateSettings = _useSettings.updateSettings,
      wcAdminSettings = _useSettings.wcAdminSettings;

  var hasSaved = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useRef"])(false);
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useEffect"])(function () {
    function warnIfUnsavedChanges(event) {
      if (isDirty) {
        event.returnValue = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('You have unsaved changes. If you proceed, they will be lost.', 'woocommerce');
        return event.returnValue;
      }
    }

    window.addEventListener('beforeunload', warnIfUnsavedChanges);
    return function () {
      return window.removeEventListener('beforeunload', warnIfUnsavedChanges);
    };
  }, [isDirty]);
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["useEffect"])(function () {
    if (isRequesting) {
      hasSaved.current = true;
      return;
    }

    if (!isRequesting && hasSaved.current) {
      if (!settingsError) {
        createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Your settings have been successfully saved.', 'woocommerce'));
      } else {
        createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('There was an error saving your settings.  Please try again.', 'woocommerce'));
      }

      hasSaved.current = false;
    }
  }, [isRequesting, settingsError, createNotice]);

  var resetDefaults = function resetDefaults() {
    if ( // eslint-disable-next-line no-alert
    window.confirm(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Are you sure you want to reset all settings to default values?', 'woocommerce'))) {
      var resetSettings = Object.keys(_config__WEBPACK_IMPORTED_MODULE_11__["config"]).reduce(function (result, setting) {
        result[setting] = _config__WEBPACK_IMPORTED_MODULE_11__["config"][setting].defaultValue;
        return result;
      }, {});
      updateAndPersistSettings('wcAdminSettings', resetSettings);
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_settings_reset_defaults');
    }
  };

  var saveChanges = function saveChanges() {
    persistSettings();
    Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_settings_save', wcAdminSettings); // On save, reset persisted query properties of Nav Menu links to default

    query.period = undefined;
    query.compare = undefined;
    query.before = undefined;
    query.after = undefined;
    query.interval = undefined;
    query.type = undefined;
    window.wpNavMenuUrlUpdate(query);
  };

  var handleInputChange = function handleInputChange(e) {
    var _e$target = e.target,
        checked = _e$target.checked,
        name = _e$target.name,
        type = _e$target.type,
        value = _e$target.value;

    var nextSettings = _objectSpread({}, wcAdminSettings);

    if (type === 'checkbox') {
      if (checked) {
        nextSettings[name] = [].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1___default()(nextSettings[name]), [value]);
      } else {
        nextSettings[name] = nextSettings[name].filter(function (v) {
          return v !== value;
        });
      }
    } else {
      nextSettings[name] = value;
    }

    updateSettings('wcAdminSettings', nextSettings);
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["SectionHeader"], {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Analytics Settings', 'woocommerce')
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("div", {
    className: "woocommerce-settings__wrapper"
  }, Object.keys(_config__WEBPACK_IMPORTED_MODULE_11__["config"]).map(function (setting) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_setting__WEBPACK_IMPORTED_MODULE_12__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
      handleChange: handleInputChange,
      value: wcAdminSettings[setting],
      key: setting,
      name: setting
    }, _config__WEBPACK_IMPORTED_MODULE_11__["config"][setting]));
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("div", {
    className: "woocommerce-settings__actions"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Button"], {
    isSecondary: true,
    onClick: resetDefaults
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Reset Defaults', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Button"], {
    isPrimary: true,
    isBusy: isRequesting,
    onClick: saveChanges
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Save Settings', 'woocommerce')))), query.import === 'true' ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["ScrollTo"], {
    offset: "-56"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_historical_data__WEBPACK_IMPORTED_MODULE_13__["default"], {
    createNotice: createNotice
  })) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_historical_data__WEBPACK_IMPORTED_MODULE_13__["default"], {
    createNotice: createNotice
  }));
};

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_6__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_7__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(Object(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["useFilters"])(SETTINGS_FILTER)(Settings)));

/***/ }),

/***/ "./client/analytics/settings/index.scss":
/*!**********************************************!*\
  !*** ./client/analytics/settings/index.scss ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/analytics/settings/setting.js":
/*!**********************************************!*\
  !*** ./client/analytics/settings/setting.js ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _setting_scss__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./setting.scss */ "./client/analytics/settings/setting.scss");
/* harmony import */ var _setting_scss__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_setting_scss__WEBPACK_IMPORTED_MODULE_12__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



var Setting = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(Setting, _Component);

  var _super = _createSuper(Setting);

  function Setting(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, Setting);

    _this = _super.call(this, props);

    _this.renderInput = function () {
      var _this$props = _this.props,
          handleChange = _this$props.handleChange,
          name = _this$props.name,
          inputText = _this$props.inputText,
          inputType = _this$props.inputType,
          options = _this$props.options,
          value = _this$props.value,
          component = _this$props.component;
      var disabled = _this.state.disabled;

      switch (inputType) {
        case 'checkboxGroup':
          return options.map(function (optionGroup) {
            return optionGroup.options.length > 0 && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
              className: "woocommerce-setting__options-group",
              key: optionGroup.key,
              "aria-labelledby": name + '-label'
            }, optionGroup.label && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
              className: "woocommerce-setting__options-group-label"
            }, optionGroup.label), _this.renderCheckboxOptions(optionGroup.options));
          });

        case 'checkbox':
          return _this.renderCheckboxOptions(options);

        case 'button':
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__["Button"], {
            isSecondary: true,
            onClick: _this.handleInputCallback,
            disabled: disabled
          }, inputText);

        case 'component':
          var SettingComponent = component;
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(SettingComponent, _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
            value: value,
            onChange: handleChange
          }, _this.props));

        case 'text':
        default:
          var id = Object(lodash__WEBPACK_IMPORTED_MODULE_10__["uniqueId"])(name);
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
            id: id,
            type: "text",
            name: name,
            onChange: handleChange,
            value: value,
            placeholder: inputText,
            disabled: disabled
          });
      }
    };

    _this.handleInputCallback = function () {
      var _this$props2 = _this.props,
          createNotice = _this$props2.createNotice,
          callback = _this$props2.callback;

      if (typeof callback !== 'function') {
        return;
      }

      return new Promise(function (resolve, reject) {
        _this.setState({
          disabled: true
        });

        callback(resolve, reject, createNotice);
      }).then(function () {
        _this.setState({
          disabled: false
        });
      }).catch(function () {
        _this.setState({
          disabled: false
        });
      });
    };

    _this.state = {
      disabled: false
    };
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(Setting, [{
    key: "renderCheckboxOptions",
    value: function renderCheckboxOptions(options) {
      var _this$props3 = this.props,
          handleChange = _this$props3.handleChange,
          name = _this$props3.name,
          value = _this$props3.value;
      var disabled = this.state.disabled;
      return options.map(function (option) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__["CheckboxControl"], {
          key: name + '-' + option.value,
          label: option.label,
          name: name,
          checked: value && value.includes(option.value),
          onChange: function onChange(checked) {
            return handleChange({
              target: {
                checked: checked,
                name: name,
                type: 'checkbox',
                value: option.value
              }
            });
          },
          disabled: disabled
        });
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          helpText = _this$props4.helpText,
          label = _this$props4.label,
          name = _this$props4.name;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-setting"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-setting__label",
        id: name + '-label'
      }, label), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-setting__input"
      }, this.renderInput(), helpText && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
        className: "woocommerce-setting__help"
      }, helpText)));
    }
  }]);

  return Setting;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

Setting.propTypes = {
  /**
   * A callback that is fired after actionable items, such as buttons.
   */
  callback: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.func,

  /**
   * Function assigned to the onChange of all inputs.
   */
  handleChange: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.func.isRequired,

  /**
   * Optional help text displayed underneath the setting.
   */
  helpText: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.oneOfType([prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string, prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.array]),

  /**
   * Text used as placeholder or button text in the input area.
   */
  inputText: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string,

  /**
   * Type of input to use; defaults to a text input.
   */
  inputType: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.oneOf(['button', 'checkbox', 'checkboxGroup', 'text', 'component']),

  /**
   * Label used for describing the setting.
   */
  label: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string.isRequired,

  /**
   * Setting slug applied to input names.
   */
  name: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string.isRequired,

  /**
   * Array of options used for when the `inputType` allows multiple selections.
   */
  options: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.shape({
    /**
     * Input value for this option.
     */
    value: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string,

    /**
     * Label for this option or above a group for a group `inputType`.
     */
    label: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string,

    /**
     * Description used for screen readers.
     */
    description: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string,

    /**
     * Key used for a group `inputType`.
     */
    key: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string,

    /**
     * Nested options for a group `inputType`.
     */
    options: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.array
  })),

  /**
   * The string value used for the input or array of items if the input allows multiselection.
   */
  value: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.oneOfType([prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.string, prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.array])
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_8__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(Setting));

/***/ }),

/***/ "./client/analytics/settings/setting.scss":
/*!************************************************!*\
  !*** ./client/analytics/settings/setting.scss ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);
//# sourceMappingURL=analytics-settings.73c9509ccfdcd5674ba2.min.js.map
