(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[33],{

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

/***/ 648:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 649:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 670:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(537);

// EXTERNAL MODULE: ./client/lib/sanitize-html/index.js
var sanitize_html = __webpack_require__(546);

// EXTERNAL MODULE: ./client/analytics/components/leaderboard/style.scss
var style = __webpack_require__(648);

// CONCATENATED MODULE: ./client/analytics/components/leaderboard/index.js


/**
 * External dependencies
 */










/**
 * Internal dependencies
 */




class leaderboard_Leaderboard extends external_wp_element_["Component"] {
  getFormattedHeaders() {
    return this.props.headers.map((header, i) => {
      return {
        isLeftAligned: i === 0,
        hiddenByDefault: false,
        isSortable: false,
        key: header.label,
        label: header.label
      };
    });
  }

  getFormattedRows() {
    return this.props.rows.map(row => {
      return row.map(column => {
        return {
          display: Object(external_wp_element_["createElement"])("div", {
            dangerouslySetInnerHTML: Object(sanitize_html["a" /* default */])(column.display)
          }),
          value: column.value
        };
      });
    });
  }

  render() {
    const {
      isRequesting,
      isError,
      totalRows,
      title
    } = this.props;
    const classes = 'woocommerce-leaderboard';

    if (isError) {
      return Object(external_wp_element_["createElement"])(report_error["a" /* default */], {
        className: classes
      });
    }

    const rows = this.getFormattedRows();

    if (!isRequesting && rows.length === 0) {
      return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
        className: classes
      }, Object(external_wp_element_["createElement"])(external_wp_components_["CardHeader"], null, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
        size: 16,
        weight: 600,
        as: "h3",
        color: "#23282d"
      }, title)), Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], {
        size: null
      }, Object(external_wp_element_["createElement"])(external_wc_components_["EmptyTable"], null, Object(external_wp_i18n_["__"])('No data recorded for the selected time period.', 'woocommerce-admin'))));
    }

    return Object(external_wp_element_["createElement"])(external_wc_components_["TableCard"], {
      className: classes,
      headers: this.getFormattedHeaders(),
      isLoading: isRequesting,
      rows: rows,
      rowsPerPage: totalRows,
      showMenu: false,
      title: title,
      totalRows: totalRows
    });
  }

}
leaderboard_Leaderboard.propTypes = {
  /**
   * An array of column headers.
   */
  headers: prop_types_default.a.arrayOf(prop_types_default.a.shape({
    label: prop_types_default.a.string
  })),

  /**
   * String of leaderboard ID to display.
   */
  id: prop_types_default.a.string.isRequired,

  /**
   * Query args added to the report table endpoint request.
   */
  query: prop_types_default.a.object,

  /**
   * Which column should be the row header, defaults to the first item (`0`) (see `Table` props).
   */
  rows: prop_types_default.a.arrayOf(prop_types_default.a.arrayOf(prop_types_default.a.shape({
    display: prop_types_default.a.node,
    value: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.number, prop_types_default.a.bool])
  }))).isRequired,

  /**
   * String to display as the title of the table.
   */
  title: prop_types_default.a.string.isRequired,

  /**
   * Number of table rows.
   */
  totalRows: prop_types_default.a.number.isRequired
};
leaderboard_Leaderboard.defaultProps = {
  rows: [],
  isError: false,
  isRequesting: false
};
/* harmony default export */ var components_leaderboard = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])((select, props) => {
  const {
    id,
    query,
    totalRows,
    filters
  } = props;
  const {
    woocommerce_default_date_range: defaultDateRange
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings');
  const filterQuery = Object(external_wc_data_["getFilterQuery"])({
    filters,
    query
  });
  const leaderboardQuery = {
    id,
    per_page: totalRows,
    persisted_query: Object(external_wc_navigation_["getPersistedQuery"])(query),
    query,
    select,
    defaultDateRange,
    filterQuery
  };
  const leaderboardData = Object(external_wc_data_["getLeaderboard"])(leaderboardQuery);
  return leaderboardData;
}))(leaderboard_Leaderboard));
// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// EXTERNAL MODULE: ./client/dashboard/leaderboards/style.scss
var leaderboards_style = __webpack_require__(649);

// CONCATENATED MODULE: ./client/dashboard/leaderboards/index.js


/**
 * External dependencies
 */









/**
 * Internal dependencies
 */





const renderLeaderboardToggles = _ref => {
  let {
    allLeaderboards,
    hiddenBlocks,
    onToggleHiddenBlock
  } = _ref;
  return allLeaderboards.map(leaderboard => {
    const checked = !hiddenBlocks.includes(leaderboard.id);
    return Object(external_wp_element_["createElement"])(external_wc_components_["MenuItem"], {
      checked: checked,
      isCheckbox: true,
      isClickable: true,
      key: leaderboard.id,
      onInvoke: () => {
        onToggleHiddenBlock(leaderboard.id)();
        Object(external_wc_tracks_["recordEvent"])('dash_leaderboards_toggle', {
          status: checked ? 'off' : 'on',
          key: leaderboard.id
        });
      }
    }, leaderboard.label);
  });
};

const renderLeaderboards = _ref2 => {
  let {
    allLeaderboards,
    hiddenBlocks,
    query,
    rowsPerTable,
    filters
  } = _ref2;
  return allLeaderboards.map(leaderboard => {
    if (hiddenBlocks.includes(leaderboard.id)) {
      return undefined;
    }

    return Object(external_wp_element_["createElement"])(components_leaderboard, {
      headers: leaderboard.headers,
      id: leaderboard.id,
      key: leaderboard.id,
      query: query,
      title: leaderboard.label,
      totalRows: rowsPerTable,
      filters: filters
    });
  });
};

const Leaderboards = props => {
  const {
    allLeaderboards,
    controls: Controls,
    isFirst,
    isLast,
    hiddenBlocks,
    onMove,
    onRemove,
    onTitleBlur,
    onTitleChange,
    onToggleHiddenBlock,
    query,
    title,
    titleInput,
    filters
  } = props;
  const {
    updateUserPreferences,
    ...userPrefs
  } = Object(external_wc_data_["useUserPreferences"])();
  const [rowsPerTable, setRowsPerTableState] = Object(external_wp_element_["useState"])(parseInt(userPrefs.dashboard_leaderboard_rows || 5, 10));

  const setRowsPerTable = rows => {
    setRowsPerTableState(parseInt(rows, 10));
    const userDataFields = {
      dashboard_leaderboard_rows: parseInt(rows, 10)
    };
    updateUserPreferences(userDataFields);
  };

  const renderMenu = () => Object(external_wp_element_["createElement"])(external_wc_components_["EllipsisMenu"], {
    label: Object(external_wp_i18n_["__"])('Choose which leaderboards to display and other settings', 'woocommerce-admin'),
    renderContent: _ref3 => {
      let {
        onToggle
      } = _ref3;
      return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wc_components_["MenuTitle"], null, Object(external_wp_i18n_["__"])('Leaderboards', 'woocommerce-admin')), renderLeaderboardToggles({
        allLeaderboards,
        hiddenBlocks,
        onToggleHiddenBlock
      }), Object(external_wp_element_["createElement"])(external_wp_components_["SelectControl"], {
        className: "woocommerce-dashboard__dashboard-leaderboards__select",
        label: Object(external_wp_i18n_["__"])('Rows per table', 'woocommerce-admin'),
        value: rowsPerTable,
        options: Array.from({
          length: 20
        }, (v, key) => ({
          v: key + 1,
          label: key + 1
        })),
        onChange: setRowsPerTable
      }), Object(external_wp_element_["createElement"])(Controls, {
        onToggle: onToggle,
        onMove: onMove,
        onRemove: onRemove,
        isFirst: isFirst,
        isLast: isLast,
        onTitleBlur: onTitleBlur,
        onTitleChange: onTitleChange,
        titleInput: titleInput
      }));
    }
  });

  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-dashboard__dashboard-leaderboards"
  }, Object(external_wp_element_["createElement"])(external_wc_components_["SectionHeader"], {
    title: title || Object(external_wp_i18n_["__"])('Leaderboards', 'woocommerce-admin'),
    menu: renderMenu()
  }), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-dashboard__columns"
  }, renderLeaderboards({
    allLeaderboards,
    hiddenBlocks,
    query,
    rowsPerTable,
    filters
  }))));
};

Leaderboards.propTypes = {
  query: prop_types_default.a.object.isRequired
};
/* harmony default export */ var leaderboards = __webpack_exports__["default"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getItems,
    getItemsError
  } = select(external_wc_data_["ITEMS_STORE_NAME"]);
  const {
    leaderboards: allLeaderboards
  } = Object(admin_settings["d" /* getAdminSetting */])('dataEndpoints', {
    leaderboards: []
  });
  return {
    allLeaderboards,
    getItems,
    getItemsError
  };
}))(Leaderboards));

/***/ })

}]);