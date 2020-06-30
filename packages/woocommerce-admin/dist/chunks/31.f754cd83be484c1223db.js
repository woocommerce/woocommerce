(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[31],{

/***/ 146:
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(419);

var iterableToArrayLimit = __webpack_require__(420);

var unsupportedIterableToArray = __webpack_require__(172);

var nonIterableRest = __webpack_require__(421);

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;

/***/ }),

/***/ 419:
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;

/***/ }),

/***/ 420:
/***/ (function(module, exports) {

function _iterableToArrayLimit(arr, i) {
  if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return;
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;

/***/ }),

/***/ 421:
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;

/***/ }),

/***/ 720:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return SelectControl; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(15);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(112);
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(171);





/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


function SelectControl(_ref) {
  var help = _ref.help,
      label = _ref.label,
      _ref$multiple = _ref.multiple,
      multiple = _ref$multiple === void 0 ? false : _ref$multiple,
      onChange = _ref.onChange,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options,
      className = _ref.className,
      hideLabelFromVision = _ref.hideLabelFromVision,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_ref, ["help", "label", "multiple", "onChange", "options", "className", "hideLabelFromVision"]);

  var instanceId = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(SelectControl);
  var id = "inspector-select-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    if (multiple) {
      var selectedOptions = Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(event.target.options).filter(function (_ref2) {
        var selected = _ref2.selected;
        return selected;
      });

      var newValues = selectedOptions.map(function (_ref3) {
        var value = _ref3.value;
        return value;
      });
      onChange(newValues);
      return;
    }

    onChange(event.target.value);
  }; // Disable reason: A select with an onchange throws a warning

  /* eslint-disable jsx-a11y/no-onchange */


  return !Object(lodash__WEBPACK_IMPORTED_MODULE_4__["isEmpty"])(options) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_base_control__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("select", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    id: id,
    className: "components-select-control__input",
    onChange: onChangeValue,
    "aria-describedby": !!help ? "".concat(id, "__help") : undefined,
    multiple: multiple
  }, props), options.map(function (option, index) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("option", {
      key: "".concat(option.label, "-").concat(option.value, "-").concat(index),
      value: option.value,
      disabled: option.disabled
    }, option.label);
  })));
  /* eslint-enable jsx-a11y/no-onchange */
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 758:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export ALLOWED_TAGS */
/* unused harmony export ALLOWED_ATTR */
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(766);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

var ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p'];
var ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download'];
/* harmony default export */ __webpack_exports__["a"] = (function (html) {
  return {
    __html: Object(dompurify__WEBPACK_IMPORTED_MODULE_0__["sanitize"])(html, {
      ALLOWED_TAGS: ALLOWED_TAGS,
      ALLOWED_ATTR: ALLOWED_ATTR
    })
  };
});

/***/ }),

/***/ 906:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 907:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 938:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/slicedToArray.js
var slicedToArray = __webpack_require__(146);
var slicedToArray_default = /*#__PURE__*/__webpack_require__.n(slicedToArray);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(129);
var objectWithoutProperties_default = /*#__PURE__*/__webpack_require__.n(objectWithoutProperties);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/select-control/index.js
var select_control = __webpack_require__(720);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(43);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(22);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(38);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(37);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(39);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(42);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(26);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(23);

// EXTERNAL MODULE: ./client/wc-api/items/utils.js
var utils = __webpack_require__(274);

// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(261);

// EXTERNAL MODULE: ./client/lib/sanitize-html/index.js
var sanitize_html = __webpack_require__(758);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/analytics/components/leaderboard/style.scss
var style = __webpack_require__(906);

// CONCATENATED MODULE: ./client/analytics/components/leaderboard/index.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

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






var leaderboard_Leaderboard = /*#__PURE__*/function (_Component) {
  inherits_default()(Leaderboard, _Component);

  var _super = _createSuper(Leaderboard);

  function Leaderboard() {
    classCallCheck_default()(this, Leaderboard);

    return _super.apply(this, arguments);
  }

  createClass_default()(Leaderboard, [{
    key: "getFormattedHeaders",
    value: function getFormattedHeaders() {
      return this.props.headers.map(function (header, i) {
        return {
          isLeftAligned: i === 0,
          hiddenByDefault: false,
          isSortable: false,
          key: header.label,
          label: header.label
        };
      });
    }
  }, {
    key: "getFormattedRows",
    value: function getFormattedRows() {
      return this.props.rows.map(function (row) {
        return row.map(function (column) {
          return {
            display: Object(external_this_wp_element_["createElement"])("div", {
              dangerouslySetInnerHTML: Object(sanitize_html["a" /* default */])(column.display)
            }),
            value: column.value
          };
        });
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          isRequesting = _this$props.isRequesting,
          isError = _this$props.isError,
          totalRows = _this$props.totalRows,
          title = _this$props.title;
      var classes = 'woocommerce-leaderboard';

      if (isError) {
        return Object(external_this_wp_element_["createElement"])(report_error["a" /* default */], {
          className: classes,
          isError: true
        });
      }

      var rows = this.getFormattedRows();

      if (!isRequesting && rows.length === 0) {
        return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
          title: title,
          className: classes
        }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["EmptyTable"], null, Object(external_this_wp_i18n_["__"])('No data recorded for the selected time period.', 'woocommerce')));
      }

      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TableCard"], {
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
  }]);

  return Leaderboard;
}(external_this_wp_element_["Component"]);
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
/* harmony default export */ var components_leaderboard = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select, props) {
  var id = props.id,
      query = props.query,
      totalRows = props.totalRows;

  var _select$getSetting = select(external_this_wc_data_["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  var leaderboardQuery = {
    id: id,
    per_page: totalRows,
    persisted_query: Object(external_this_wc_navigation_["getPersistedQuery"])(query),
    query: query,
    select: select,
    defaultDateRange: defaultDateRange
  };
  var leaderboardData = Object(utils["a" /* getLeaderboard */])(leaderboardQuery);
  return leaderboardData;
}))(leaderboard_Leaderboard));
// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(63);

// EXTERNAL MODULE: ./client/dashboard/leaderboards/style.scss
var leaderboards_style = __webpack_require__(907);

// CONCATENATED MODULE: ./client/dashboard/leaderboards/index.js




/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */






var leaderboards_renderLeaderboardToggles = function renderLeaderboardToggles(_ref) {
  var allLeaderboards = _ref.allLeaderboards,
      hiddenBlocks = _ref.hiddenBlocks,
      onToggleHiddenBlock = _ref.onToggleHiddenBlock;
  return allLeaderboards.map(function (leaderboard) {
    var checked = !hiddenBlocks.includes(leaderboard.id);
    return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuItem"], {
      checked: checked,
      isCheckbox: true,
      isClickable: true,
      key: leaderboard.id,
      onInvoke: function onInvoke() {
        onToggleHiddenBlock(leaderboard.id)();
        Object(tracks["b" /* recordEvent */])('dash_leaderboards_toggle', {
          status: checked ? 'off' : 'on',
          key: leaderboard.id
        });
      }
    }, leaderboard.label);
  });
};

var leaderboards_renderLeaderboards = function renderLeaderboards(_ref2) {
  var allLeaderboards = _ref2.allLeaderboards,
      hiddenBlocks = _ref2.hiddenBlocks,
      query = _ref2.query,
      rowsPerTable = _ref2.rowsPerTable;
  return allLeaderboards.map(function (leaderboard) {
    if (hiddenBlocks.includes(leaderboard.id)) {
      return undefined;
    }

    return Object(external_this_wp_element_["createElement"])(components_leaderboard, {
      headers: leaderboard.headers,
      id: leaderboard.id,
      key: leaderboard.id,
      query: query,
      title: leaderboard.label,
      totalRows: rowsPerTable
    });
  });
};

var leaderboards_Leaderboards = function Leaderboards(props) {
  var allLeaderboards = props.allLeaderboards,
      Controls = props.controls,
      isFirst = props.isFirst,
      isLast = props.isLast,
      hiddenBlocks = props.hiddenBlocks,
      onMove = props.onMove,
      onRemove = props.onRemove,
      onTitleBlur = props.onTitleBlur,
      onTitleChange = props.onTitleChange,
      onToggleHiddenBlock = props.onToggleHiddenBlock,
      query = props.query,
      title = props.title,
      titleInput = props.titleInput;

  var _useUserPreferences = Object(external_this_wc_data_["useUserPreferences"])(),
      updateUserPreferences = _useUserPreferences.updateUserPreferences,
      userPrefs = objectWithoutProperties_default()(_useUserPreferences, ["updateUserPreferences"]);

  var _useState = Object(external_this_wp_element_["useState"])(parseInt(userPrefs.dashboard_leaderboard_rows || 5, 10)),
      _useState2 = slicedToArray_default()(_useState, 2),
      rowsPerTable = _useState2[0],
      setRowsPerTableState = _useState2[1];

  var setRowsPerTable = function setRowsPerTable(rows) {
    setRowsPerTableState(parseInt(rows, 10));
    var userDataFields = {
      dashboard_leaderboard_rows: parseInt(rows, 10)
    };
    updateUserPreferences(userDataFields);
  };

  var renderMenu = function renderMenu() {
    return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["EllipsisMenu"], {
      label: Object(external_this_wp_i18n_["__"])('Choose which leaderboards to display and other settings', 'woocommerce'),
      renderContent: function renderContent(_ref3) {
        var onToggle = _ref3.onToggle;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuTitle"], null, Object(external_this_wp_i18n_["__"])('Leaderboards', 'woocommerce')), leaderboards_renderLeaderboardToggles({
          allLeaderboards: allLeaderboards,
          hiddenBlocks: hiddenBlocks,
          onToggleHiddenBlock: onToggleHiddenBlock
        }), Object(external_this_wp_element_["createElement"])(select_control["a" /* default */], {
          className: "woocommerce-dashboard__dashboard-leaderboards__select",
          label: Object(external_this_wp_i18n_["__"])('Rows Per Table', 'woocommerce'),
          value: rowsPerTable,
          options: Array.from({
            length: 20
          }, function (v, key) {
            return {
              v: key + 1,
              label: key + 1
            };
          }),
          onChange: setRowsPerTable
        }), window.wcAdminFeatures['analytics-dashboard/customizable'] && Object(external_this_wp_element_["createElement"])(Controls, {
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
  };

  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-dashboard__dashboard-leaderboards"
  }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SectionHeader"], {
    title: title || Object(external_this_wp_i18n_["__"])('Leaderboards', 'woocommerce'),
    menu: renderMenu()
  }), Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-dashboard__columns"
  }, leaderboards_renderLeaderboards({
    allLeaderboards: allLeaderboards,
    hiddenBlocks: hiddenBlocks,
    query: query,
    rowsPerTable: rowsPerTable
  }))));
};

leaderboards_Leaderboards.propTypes = {
  query: prop_types_default.a.object.isRequired
};
/* harmony default export */ var leaderboards = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getItems = _select.getItems,
      getItemsError = _select.getItemsError,
      isGetItemsRequesting = _select.isGetItemsRequesting;

  var _getSetting = Object(settings["g" /* getSetting */])('dataEndpoints', {
    leaderboards: []
  }),
      allLeaderboards = _getSetting.leaderboards;

  return {
    allLeaderboards: allLeaderboards,
    getItems: getItems,
    getItemsError: getItemsError,
    isGetItemsRequesting: isGetItemsRequesting
  };
}))(leaderboards_Leaderboards));

/***/ })

}]);
