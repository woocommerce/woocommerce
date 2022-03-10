(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[0],{

/***/ 536:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getFilteredCurrencyInstance; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return CurrencyContext; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(101);
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_currency__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(23);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const appCurrency = _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default()(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__[/* CURRENCY */ "a"]);
const getFilteredCurrencyInstance = query => {
  const config = appCurrency.getCurrencyConfig();
  /**
   * Filter the currency context. This affects all WooCommerce Admin currency formatting.
   *
   * @filter woocommerce_admin_report_currency
   * @param {Object} config Currency configuration.
   * @param {Object} query Url query parameters.
   */

  const filteredConfig = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])('woocommerce_admin_report_currency', config, query);
  return _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default()(filteredConfig);
};
const CurrencyContext = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createContext"])(appCurrency // default value
);

/***/ }),

/***/ 537:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__);


/**
 * External dependencies
 */



/**
 * Component to render when there is an error in a report component due to data
 * not being loaded or being invalid.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional class name to style the component.
 */

function ReportError(_ref) {
  let {
    className
  } = _ref;

  const title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('There was an error getting your stats. Please try again.', 'woocommerce-admin');

  const actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Reload', 'woocommerce-admin');

  const actionCallback = () => {
    // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
    window.location.reload();
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["EmptyContent"], {
    className: className,
    title: title,
    actionLabel: actionLabel,
    actionCallback: actionCallback
  });
}

ReportError.propTypes = {
  /**
   * Additional class name to style the component.
   */
  className: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string
};
/* harmony default export */ __webpack_exports__["a"] = (ReportError);

/***/ }),

/***/ 544:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(5);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(20);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(17);
/* harmony import */ var _woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _lib_currency_context__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(536);
/* harmony import */ var _customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(69);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(23);


/**
 * External dependencies
 */









/**
 * Internal dependencies
 */





class ReportFilters extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"] {
  constructor() {
    super();
    this.onDateSelect = this.onDateSelect.bind(this);
    this.onFilterSelect = this.onFilterSelect.bind(this);
    this.onAdvancedFilterAction = this.onAdvancedFilterAction.bind(this);
  }

  onDateSelect(data) {
    const {
      report,
      addCesSurveyForAnalytics
    } = this.props;
    addCesSurveyForAnalytics();
    Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"])('datepicker_update', {
      report,
      ...Object(lodash__WEBPACK_IMPORTED_MODULE_3__["omitBy"])(data, lodash__WEBPACK_IMPORTED_MODULE_3__["isUndefined"])
    });
  }

  onFilterSelect(data) {
    const {
      report,
      addCesSurveyForAnalytics
    } = this.props; // This event gets triggered in the following cases.
    // 1. Select "Single product" and choose a product.
    // 2. Select "Comparison" or any other filter types.
    // The comparsion and other filter types require a user to click
    // a button to execute a query, so this is not a good place to
    // trigger a CES survey for those.

    const triggerCesFor = ['single_product', 'single_category', 'single_coupon', 'single_variation'];
    const filterName = data.filter || data['filter-variations'];

    if (triggerCesFor.includes(filterName)) {
      addCesSurveyForAnalytics();
    }

    const eventProperties = {
      report,
      filter: data.filter || 'all'
    };

    if (data.filter === 'single_product') {
      eventProperties.filter_variation = data['filter-variations'] || 'all';
    }

    Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"])('analytics_filter', eventProperties);
  }

  onAdvancedFilterAction(action, data) {
    const {
      report,
      addCesSurveyForAnalytics
    } = this.props;

    switch (action) {
      case 'add':
        Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"])('analytics_filters_add', {
          report,
          filter: data.key
        });
        break;

      case 'remove':
        Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"])('analytics_filters_remove', {
          report,
          filter: data.key
        });
        break;

      case 'filter':
        const snakeCaseData = Object.keys(data).reduce((result, property) => {
          result[Object(lodash__WEBPACK_IMPORTED_MODULE_3__["snakeCase"])(property)] = data[property];
          return result;
        }, {});
        addCesSurveyForAnalytics();
        Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"])('analytics_filters_filter', {
          report,
          ...snakeCaseData
        });
        break;

      case 'clear_all':
        Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"])('analytics_filters_clear_all', {
          report
        });
        break;

      case 'match':
        Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"])('analytics_filters_all_any', {
          report,
          value: data.match
        });
        break;
    }
  }

  render() {
    const {
      advancedFilters,
      filters,
      path,
      query,
      showDatePicker,
      defaultDateRange
    } = this.props;
    const {
      period,
      compare,
      before,
      after
    } = Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_7__["getDateParamsFromQuery"])(query, defaultDateRange);
    const {
      primary: primaryDate,
      secondary: secondaryDate
    } = Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_7__["getCurrentDates"])(query, defaultDateRange);
    const dateQuery = {
      period,
      compare,
      before,
      after,
      primaryDate,
      secondaryDate
    };
    const Currency = this.context;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__["ReportFilters"], {
      query: query,
      siteLocale: _utils_admin_settings__WEBPACK_IMPORTED_MODULE_11__[/* LOCALE */ "b"].siteLocale,
      currency: Currency.getCurrencyConfig(),
      path: path,
      filters: filters,
      advancedFilters: advancedFilters,
      showDatePicker: showDatePicker,
      onDateSelect: this.onDateSelect,
      onFilterSelect: this.onFilterSelect,
      onAdvancedFilterAction: this.onAdvancedFilterAction,
      dateQuery: dateQuery,
      isoDateFormat: _woocommerce_date__WEBPACK_IMPORTED_MODULE_7__["isoDateFormat"]
    });
  }

}

ReportFilters.contextType = _lib_currency_context__WEBPACK_IMPORTED_MODULE_9__[/* CurrencyContext */ "a"];
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__["withSelect"])(select => {
  const {
    woocommerce_default_date_range: defaultDateRange
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings');
  return {
    defaultDateRange
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__["withDispatch"])(dispatch => {
  const {
    addCesSurveyForAnalytics
  } = dispatch(_customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_10__[/* STORE_KEY */ "c"]);
  return {
    addCesSurveyForAnalytics
  };
}))(ReportFilters));
ReportFilters.propTypes = {
  /**
   * Config option passed through to `AdvancedFilters`
   */
  advancedFilters: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.object,

  /**
   * Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
   */
  filters: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.array,

  /**
   * The `path` parameter supplied by React-Router
   */
  path: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string.isRequired,

  /**
   * The query string represented in object form
   */
  query: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.object,

  /**
   * Whether the date picker must be shown..
   */
  showDatePicker: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.bool,

  /**
   * The report where filter are placed.
   */
  report: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string.isRequired
};

/***/ }),

/***/ 545:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(40);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","dom"]
var external_wp_dom_ = __webpack_require__(112);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","csvExport"]
var external_wc_csvExport_ = __webpack_require__(508);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// CONCATENATED MODULE: ./client/analytics/components/report-table/download-icon.js

/* harmony default export */ var download_icon = (() => Object(external_wp_element_["createElement"])("svg", {
  role: "img",
  "aria-hidden": "true",
  focusable: "false",
  version: "1.1",
  xmlns: "http://www.w3.org/2000/svg",
  x: "0px",
  y: "0px",
  viewBox: "0 0 24 24"
}, Object(external_wp_element_["createElement"])("path", {
  d: "M18,9c-0.009,0-0.017,0.002-0.025,0.003C17.72,5.646,14.922,3,11.5,3C7.91,3,5,5.91,5,9.5c0,0.524,0.069,1.031,0.186,1.519 C5.123,11.016,5.064,11,5,11c-2.209,0-4,1.791-4,4c0,1.202,0.541,2.267,1.38,3h18.593C22.196,17.089,23,15.643,23,14 C23,11.239,20.761,9,18,9z M12,16l-4-5h3V8h2v3h3L12,16z"
})));
// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(537);

// CONCATENATED MODULE: ./client/analytics/components/report-table/utils.js
/**
 * External dependencies
 */

function extendTableData(extendedStoreSelector, props, queriedTableData) {
  const {
    extendItemsMethodNames,
    itemIdField
  } = props;
  const itemsData = queriedTableData.items.data;

  if (!Array.isArray(itemsData) || !itemsData.length || !extendItemsMethodNames || !itemIdField) {
    return queriedTableData;
  }

  const {
    [extendItemsMethodNames.getError]: getErrorMethod,
    [extendItemsMethodNames.isRequesting]: isRequestingMethod,
    [extendItemsMethodNames.load]: loadMethod
  } = extendedStoreSelector;
  const extendQuery = {
    include: itemsData.map(item => item[itemIdField]).join(','),
    per_page: itemsData.length
  };
  const extendedItems = loadMethod(extendQuery);
  const isExtendedItemsRequesting = isRequestingMethod ? isRequestingMethod(extendQuery) : false;
  const isExtendedItemsError = getErrorMethod ? getErrorMethod(extendQuery) : false;
  const extendedItemsData = itemsData.map(item => {
    const extendedItemData = Object(external_lodash_["first"])(extendedItems.filter(extendedItem => item.id === extendedItem.id));
    return { ...item,
      ...extendedItemData
    };
  });
  const isRequesting = queriedTableData.isRequesting || isExtendedItemsRequesting;
  const isError = queriedTableData.isError || isExtendedItemsError;
  return { ...queriedTableData,
    isRequesting,
    isError,
    items: { ...queriedTableData.items,
      data: extendedItemsData
    }
  };
}
// EXTERNAL MODULE: ./client/customer-effort-score-tracks/data/constants.js
var constants = __webpack_require__(69);

// EXTERNAL MODULE: ./client/analytics/components/report-table/style.scss
var style = __webpack_require__(632);

// CONCATENATED MODULE: ./client/analytics/components/report-table/index.js



/**
 * External dependencies
 */














/**
 * Internal dependencies
 */






const TABLE_FILTER = 'woocommerce_admin_report_table';

const ReportTable = props => {
  const {
    getHeadersContent,
    getRowsContent,
    getSummary,
    isRequesting,
    primaryData,
    tableData,
    endpoint,
    // These props are not used in the render function, but are destructured
    // so they are not included in the `tableProps` variable.
    // eslint-disable-next-line no-unused-vars
    itemIdField,
    // eslint-disable-next-line no-unused-vars
    tableQuery,
    compareBy,
    compareParam,
    searchBy,
    labels = {},
    ...tableProps
  } = props; // Pull these props out separately because they need to be included in tableProps.

  const {
    query,
    columnPrefsKey
  } = props;
  const {
    items,
    query: reportQuery
  } = tableData;
  const initialSelectedRows = query[compareParam] ? Object(external_wc_navigation_["getIdsFromQuery"])(query[compareBy]) : [];
  const [selectedRows, setSelectedRows] = Object(external_wp_element_["useState"])(initialSelectedRows);
  const scrollPointRef = Object(external_wp_element_["useRef"])(null);
  const {
    updateUserPreferences,
    ...userData
  } = Object(external_wc_data_["useUserPreferences"])(); // Bail early if we've encountered an error.

  const isError = tableData.isError || primaryData.isError;

  if (isError) {
    return Object(external_wp_element_["createElement"])(report_error["a" /* default */], null);
  }

  let userPrefColumns = [];

  if (columnPrefsKey) {
    userPrefColumns = userData && userData[columnPrefsKey] ? userData[columnPrefsKey] : userPrefColumns;
  }

  const onPageChange = (newPage, source) => {
    scrollPointRef.current.scrollIntoView();
    const tableElement = scrollPointRef.current.nextSibling.querySelector('.woocommerce-table__table');
    const focusableElements = external_wp_dom_["focus"].focusable.find(tableElement);

    if (focusableElements.length) {
      focusableElements[0].focus();
    }

    if (source) {
      if (source === 'goto') {
        Object(external_wc_tracks_["recordEvent"])('analytics_table_go_to_page', {
          report: endpoint,
          page: newPage
        });
      } else {
        Object(external_wc_tracks_["recordEvent"])('analytics_table_page_click', {
          report: endpoint,
          direction: source
        });
      }
    }
  };

  const trackTableSearch = () => {
    // @todo: decide if this should only fire for new tokens (not any/all changes).
    Object(external_wc_tracks_["recordEvent"])('analytics_table_filter', {
      report: endpoint
    });
  };

  const onSort = (key, direction) => {
    Object(external_wc_navigation_["onQueryChange"])('sort')(key, direction);
    const eventProps = {
      report: endpoint,
      column: key,
      direction
    };
    Object(external_wc_tracks_["recordEvent"])('analytics_table_sort', eventProps);
  };

  const filterShownHeaders = (headers, hiddenKeys) => {
    // If no user preferences, set visibilty based on column default.
    if (!hiddenKeys) {
      return headers.map(header => ({ ...header,
        visible: header.required || !header.hiddenByDefault
      }));
    } // Set visibilty based on user preferences.


    return headers.map(header => ({ ...header,
      visible: header.required || !hiddenKeys.includes(header.key)
    }));
  };

  const applyTableFilters = (data, totals, totalResults) => {
    const summary = getSummary ? getSummary(totals, totalResults) : null;
    /**
     * Filter report table for the CSV download.
     *
     * Enables manipulation of data used to create the report CSV.
     *
     * @filter woocommerce_admin_report_table
     * @param {Object} reportTableData - data used to create the table.
     * @param {string} reportTableData.endpoint - table api endpoint.
     * @param {Array} reportTableData.headers - table headers data.
     * @param {Array} reportTableData.rows - table rows data.
     * @param {Object} reportTableData.totals - total aggregates for request.
     * @param {Array} reportTableData.summary - summary numbers data.
     * @param {Object} reportTableData.items - response from api requerst.
     */

    return Object(external_wp_hooks_["applyFilters"])(TABLE_FILTER, {
      endpoint,
      headers: getHeadersContent(),
      rows: getRowsContent(data),
      totals,
      summary,
      items
    });
  };

  const onClickDownload = () => {
    const {
      createNotice,
      startExport,
      title
    } = props;
    const params = Object.assign({}, query);
    const {
      data,
      totalResults
    } = items;
    let downloadType = 'browser'; // Delete unnecessary items from filename.

    delete params.extended_info;

    if (params.search) {
      delete params[searchBy];
    }

    if (data && data.length === totalResults) {
      const {
        headers,
        rows
      } = applyTableFilters(data, totalResults);
      Object(external_wc_csvExport_["downloadCSVFile"])(Object(external_wc_csvExport_["generateCSVFileName"])(title, params), Object(external_wc_csvExport_["generateCSVDataFromTable"])(headers, rows));
    } else {
      downloadType = 'email';
      startExport(endpoint, reportQuery).then(() => createNotice('success', Object(external_wp_i18n_["sprintf"])(
      /* translators: %s = type of report */
      Object(external_wp_i18n_["__"])('Your %s Report will be emailed to you.', 'woocommerce-admin'), title))).catch(error => createNotice('error', error.message || Object(external_wp_i18n_["sprintf"])(
      /* translators: %s = type of report */
      Object(external_wp_i18n_["__"])('There was a problem exporting your %s Report. Please try again.', 'woocommerce-admin'), title)));
    }

    Object(external_wc_tracks_["recordEvent"])('analytics_table_download', {
      report: endpoint,
      rows: totalResults,
      download_type: downloadType
    });
  };

  const onCompare = () => {
    if (compareBy) {
      Object(external_wc_navigation_["onQueryChange"])('compare')(compareBy, compareParam, selectedRows.join(','));
    }
  };

  const onSearchChange = values => {
    const {
      baseSearchQuery,
      addCesSurveyForCustomerSearch
    } = props; // A comma is used as a separator between search terms, so we want to escape
    // any comma they contain.

    const searchTerms = values.map(v => v.label.replace(',', '%2C'));

    if (searchTerms.length) {
      Object(external_wc_navigation_["updateQueryString"])({
        filter: undefined,
        [compareParam]: undefined,
        [searchBy]: undefined,
        ...baseSearchQuery,
        search: Object(external_lodash_["uniq"])(searchTerms).join(',')
      }); // Prompt survey if user is searching for something.

      addCesSurveyForCustomerSearch();
    } else {
      Object(external_wc_navigation_["updateQueryString"])({
        search: undefined
      });
    }

    trackTableSearch();
  };

  const selectAllRows = checked => {
    const {
      ids
    } = props;
    setSelectedRows(checked ? ids : []);
  };

  const selectRow = (i, checked) => {
    const {
      ids
    } = props;

    if (checked) {
      setSelectedRows(Object(external_lodash_["uniq"])([ids[i], ...selectedRows]));
    } else {
      const index = selectedRows.indexOf(ids[i]);
      setSelectedRows([...selectedRows.slice(0, index), ...selectedRows.slice(index + 1)]);
    }
  };

  const getCheckbox = i => {
    const {
      ids = []
    } = props;
    const isChecked = selectedRows.indexOf(ids[i]) !== -1;
    return {
      display: Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
        onChange: Object(external_lodash_["partial"])(selectRow, i),
        checked: isChecked
      }),
      value: false
    };
  };

  const getAllCheckbox = () => {
    const {
      ids = []
    } = props;
    const hasData = ids.length > 0;
    const isAllChecked = hasData && ids.length === selectedRows.length;
    return {
      cellClassName: 'is-checkbox-column',
      key: 'compare',
      label: Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
        onChange: selectAllRows,
        "aria-label": Object(external_wp_i18n_["__"])('Select All'),
        checked: isAllChecked,
        disabled: !hasData
      }),
      required: true
    };
  };

  const isLoading = isRequesting || tableData.isRequesting || primaryData.isRequesting;
  const totals = Object(external_lodash_["get"])(primaryData, ['data', 'totals'], {});
  const totalResults = items.totalResults || 0;
  const downloadable = totalResults > 0; // Search words are in the query string, not the table query.

  const searchWords = Object(external_wc_navigation_["getSearchWords"])(query);
  const searchedLabels = searchWords.map(v => ({
    key: v,
    label: v
  }));
  const {
    data
  } = items;
  const applyTableFiltersResult = applyTableFilters(data, totals, totalResults);
  let {
    headers,
    rows
  } = applyTableFiltersResult;
  const {
    summary
  } = applyTableFiltersResult;

  const onColumnsChange = (shownColumns, toggledColumn) => {
    const columns = headers.map(header => header.key);
    const hiddenColumns = columns.filter(column => !shownColumns.includes(column));

    if (columnPrefsKey) {
      const userDataFields = {
        [columnPrefsKey]: hiddenColumns
      };
      updateUserPreferences(userDataFields);
    }

    if (toggledColumn) {
      const eventProps = {
        report: endpoint,
        column: toggledColumn,
        status: shownColumns.includes(toggledColumn) ? 'on' : 'off'
      };
      Object(external_wc_tracks_["recordEvent"])('analytics_table_header_toggle', eventProps);
    }
  }; // Add in selection for comparisons.


  if (compareBy) {
    rows = rows.map((row, i) => {
      return [getCheckbox(i), ...row];
    });
    headers = [getAllCheckbox(), ...headers];
  } // Hide any headers based on user prefs, if loaded.


  const filteredHeaders = filterShownHeaders(headers, userPrefColumns);
  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-report-table__scroll-point",
    ref: scrollPointRef,
    "aria-hidden": true
  }), Object(external_wp_element_["createElement"])(external_wc_components_["TableCard"], extends_default()({
    className: 'woocommerce-report-table',
    hasSearch: !!searchBy,
    actions: [compareBy && Object(external_wp_element_["createElement"])(external_wc_components_["CompareButton"], {
      key: "compare",
      className: "woocommerce-table__compare",
      count: selectedRows.length,
      helpText: labels.helpText || Object(external_wp_i18n_["__"])('Check at least two items below to compare', 'woocommerce-admin'),
      onClick: onCompare,
      disabled: !downloadable
    }, labels.compareButton || Object(external_wp_i18n_["__"])('Compare', 'woocommerce-admin')), searchBy && Object(external_wp_element_["createElement"])(external_wc_components_["Search"], {
      allowFreeTextSearch: true,
      inlineTags: true,
      key: "search",
      onChange: onSearchChange,
      placeholder: labels.placeholder || Object(external_wp_i18n_["__"])('Search by item name', 'woocommerce-admin'),
      selected: searchedLabels,
      showClearButton: true,
      type: searchBy,
      disabled: !downloadable
    }), downloadable && Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      key: "download",
      className: "woocommerce-table__download-button",
      disabled: isLoading,
      onClick: onClickDownload
    }, Object(external_wp_element_["createElement"])(download_icon, null), Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-table__download-button__label"
    }, labels.downloadButton || Object(external_wp_i18n_["__"])('Download', 'woocommerce-admin')))],
    headers: filteredHeaders,
    isLoading: isLoading,
    onQueryChange: external_wc_navigation_["onQueryChange"],
    onColumnsChange: onColumnsChange,
    onSort: onSort,
    onPageChange: onPageChange,
    rows: rows,
    rowsPerPage: parseInt(reportQuery.per_page, 10) || external_wc_data_["QUERY_DEFAULTS"].pageSize,
    summary: summary,
    totalRows: totalResults
  }, tableProps)));
};

ReportTable.propTypes = {
  /**
   * Pass in query parameters to be included in the path when onSearch creates a new url.
   */
  baseSearchQuery: prop_types_default.a.object,

  /**
   * The string to use as a query parameter when comparing row items.
   */
  compareBy: prop_types_default.a.string,

  /**
   * Url query parameter compare function operates on
   */
  compareParam: prop_types_default.a.string,

  /**
   * The key for user preferences settings for column visibility.
   */
  columnPrefsKey: prop_types_default.a.string,

  /**
   * The endpoint to use in API calls to populate the table rows and summary.
   * For example, if `taxes` is provided, data will be fetched from the report
   * `taxes` endpoint (ie: `/wc-analytics/reports/taxes` and `/wc/v4/reports/taxes/stats`).
   * If the provided endpoint doesn't exist, an error will be shown to the user
   * with `ReportError`.
   */
  endpoint: prop_types_default.a.string,

  /**
   * Name of the methods available via `select` that will be used to
   * load more data for table items. If omitted, no call will be made and only
   * the data returned by the reports endpoint will be used.
   */
  extendItemsMethodNames: prop_types_default.a.shape({
    getError: prop_types_default.a.string,
    isRequesting: prop_types_default.a.string,
    load: prop_types_default.a.string
  }),

  /**
   * Name of store on which extendItemsMethodNames can be found.
   */
  extendedItemsStoreName: prop_types_default.a.string,

  /**
   * A function that returns the headers object to build the table.
   */
  getHeadersContent: prop_types_default.a.func.isRequired,

  /**
   * A function that returns the rows array to build the table.
   */
  getRowsContent: prop_types_default.a.func.isRequired,

  /**
   * A function that returns the summary object to build the table.
   */
  getSummary: prop_types_default.a.func,

  /**
   * The name of the property in the item object which contains the id.
   */
  itemIdField: prop_types_default.a.string,

  /**
   * Custom labels for table header actions.
   */
  labels: prop_types_default.a.shape({
    compareButton: prop_types_default.a.string,
    downloadButton: prop_types_default.a.string,
    helpText: prop_types_default.a.string,
    placeholder: prop_types_default.a.string
  }),

  /**
   * Primary data of that report. If it's not provided, it will be automatically
   * loaded via the provided `endpoint`.
   */
  primaryData: prop_types_default.a.object,

  /**
   * The string to use as a query parameter when searching row items.
   */
  searchBy: prop_types_default.a.string,

  /**
   * List of fields used for summary numbers. (Reduces queries)
   */
  summaryFields: prop_types_default.a.arrayOf(prop_types_default.a.string),

  /**
   * Table data of that report. If it's not provided, it will be automatically
   * loaded via the provided `endpoint`.
   */
  tableData: prop_types_default.a.object.isRequired,

  /**
   * Properties to be added to the query sent to the report table endpoint.
   */
  tableQuery: prop_types_default.a.object,

  /**
   * String to display as the title of the table.
   */
  title: prop_types_default.a.string.isRequired
};
ReportTable.defaultProps = {
  primaryData: {},
  tableData: {
    items: {
      data: [],
      totalResults: 0
    },
    query: {}
  },
  tableQuery: {},
  compareParam: 'filter',
  downloadable: false,
  onSearch: external_lodash_["noop"],
  baseSearchQuery: {}
};
const EMPTY_ARRAY = [];
const EMPTY_OBJECT = {};
/* harmony default export */ var report_table = __webpack_exports__["a"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])((select, props) => {
  const {
    endpoint,
    getSummary,
    isRequesting,
    itemIdField,
    query,
    tableData,
    tableQuery,
    filters,
    advancedFilters,
    summaryFields,
    extendedItemsStoreName
  } = props;
  /* eslint @wordpress/no-unused-vars-before-return: "off" */

  const reportStoreSelector = select(external_wc_data_["REPORTS_STORE_NAME"]);
  const extendedStoreSelector = extendedItemsStoreName ? select(extendedItemsStoreName) : null;
  const {
    woocommerce_default_date_range: defaultDateRange
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings');
  const noSearchResultsFound = query.search && !(query[endpoint] && query[endpoint].length);

  if (isRequesting || noSearchResultsFound) {
    return EMPTY_OBJECT;
  } // Category charts are powered by the /reports/products/stats endpoint.


  const chartEndpoint = endpoint === 'categories' ? 'products' : endpoint;
  const primaryData = getSummary ? Object(external_wc_data_["getReportChartData"])({
    endpoint: chartEndpoint,
    selector: reportStoreSelector,
    dataType: 'primary',
    query,
    filters,
    advancedFilters,
    defaultDateRange,
    fields: summaryFields
  }) : EMPTY_OBJECT;
  const queriedTableData = tableData || Object(external_wc_data_["getReportTableData"])({
    endpoint,
    query,
    selector: reportStoreSelector,
    tableQuery,
    filters,
    advancedFilters,
    defaultDateRange
  });
  const extendedTableData = extendedStoreSelector ? extendTableData(extendedStoreSelector, props, queriedTableData) : queriedTableData;
  return {
    primaryData,
    ids: itemIdField && extendedTableData.items.data ? extendedTableData.items.data.map(item => item[itemIdField]) : EMPTY_ARRAY,
    tableData: extendedTableData,
    query
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    startExport
  } = dispatch(external_wc_data_["EXPORT_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');
  const {
    addCesSurveyForCustomerSearch
  } = dispatch(constants["c" /* STORE_KEY */]);
  return {
    createNotice,
    startExport,
    addCesSurveyForCustomerSearch
  };
}))(ReportTable));

/***/ }),

/***/ 632:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);