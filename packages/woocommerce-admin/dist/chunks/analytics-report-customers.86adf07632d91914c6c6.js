(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[11],{

/***/ 723:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "default", function() { return /* binding */ customers_CustomersReport; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(15);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(41);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(40);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(44);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(29);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(42);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/asyncToGenerator.js
var asyncToGenerator = __webpack_require__(46);
var asyncToGenerator_default = /*#__PURE__*/__webpack_require__.n(asyncToGenerator);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: external {"this":["wp","htmlEntities"]}
var external_this_wp_htmlEntities_ = __webpack_require__(69);

// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(26);

// EXTERNAL MODULE: ./client/lib/async-requests/index.js
var async_requests = __webpack_require__(739);

// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(24);

// CONCATENATED MODULE: ./client/analytics/report/customers/config.js


/**
 * External dependencies
 */





var _getSetting = Object(settings["g" /* getSetting */])('dataEndpoints', {
  countries: {}
}),
    countries = _getSetting.countries;
/**
 * Internal dependencies
 */




var CUSTOMERS_REPORT_FILTERS_FILTER = 'woocommerce_admin_customers_report_filters';
var CUSTOMERS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_customers_report_advanced_filters';
var config_filters = Object(external_this_wp_hooks_["applyFilters"])(CUSTOMERS_REPORT_FILTERS_FILTER, [{
  label: Object(external_this_wp_i18n_["__"])('Show', 'woocommerce'),
  staticParams: [],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(external_this_wp_i18n_["__"])('All Customers', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(external_this_wp_i18n_["__"])('Single Customer', 'woocommerce'),
    value: 'select_customer',
    chartMode: 'item-comparison',
    subFilters: [{
      component: 'Search',
      value: 'single_customer',
      chartMode: 'item-comparison',
      path: ['select_customer'],
      settings: {
        type: 'customers',
        param: 'customers',
        getLabels: async_requests["c" /* getCustomerLabels */],
        labels: {
          placeholder: Object(external_this_wp_i18n_["__"])('Type to search for a customer', 'woocommerce'),
          button: Object(external_this_wp_i18n_["__"])('Single Customer', 'woocommerce')
        }
      }
    }]
  }, {
    label: Object(external_this_wp_i18n_["__"])('Advanced Filters', 'woocommerce'),
    value: 'advanced'
  }]
}]);
/*eslint-disable max-len*/

var config_advancedFilters = Object(external_this_wp_hooks_["applyFilters"])(CUSTOMERS_REPORT_ADVANCED_FILTERS_FILTER, {
  title: Object(external_this_wp_i18n_["_x"])('Customers Match {{select /}} Filters', 'A sentence describing filters for Customers. See screen shot for context: https://cloudup.com/cCsm3GeXJbE', 'woocommerce'),
  filters: {
    name: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('Name', 'woocommerce'),
        placeholder: Object(external_this_wp_i18n_["__"])('Search', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove customer name filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select a customer name filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(external_this_wp_i18n_["__"])('{{title}}Name{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(external_this_wp_i18n_["__"])('Select customer name', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer names including a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Includes', 'customer names', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer names excluding a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Excludes', 'customer names', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'customers',
        getLabels: Object(async_requests["e" /* getRequestByIdString */])(constants["c" /* NAMESPACE */] + '/customers', function (customer) {
          return {
            id: customer.id,
            label: customer.name
          };
        })
      }
    },
    country: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('Country / Region', 'woocommerce'),
        placeholder: Object(external_this_wp_i18n_["__"])('Search', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove country / region filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select a country / region filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(external_this_wp_i18n_["__"])('{{title}}Country / Region{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(external_this_wp_i18n_["__"])('Select country / region', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to countries including a given country or countries. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Includes', 'countries', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to countries excluding a given country or countries. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Excludes', 'countries', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'countries',
        getLabels: function () {
          var _getLabels = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(value) {
            var allLabels, labels;
            return regeneratorRuntime.wrap(function _callee$(_context) {
              while (1) {
                switch (_context.prev = _context.next) {
                  case 0:
                    allLabels = countries.map(function (country) {
                      return {
                        key: country.code,
                        label: Object(external_this_wp_htmlEntities_["decodeEntities"])(country.name)
                      };
                    });
                    labels = value.split(',');
                    _context.next = 4;
                    return allLabels.filter(function (label) {
                      return labels.includes(label.key);
                    });

                  case 4:
                    return _context.abrupt("return", _context.sent);

                  case 5:
                  case "end":
                    return _context.stop();
                }
              }
            }, _callee);
          }));

          function getLabels(_x2) {
            return _getLabels.apply(this, arguments);
          }

          return getLabels;
        }()
      }
    },
    username: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('Username', 'woocommerce'),
        placeholder: Object(external_this_wp_i18n_["__"])('Search customer username', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove customer username filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select a customer username filter match', 'woocommerce'),

        /* translators: A sentence describing a customer username filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(external_this_wp_i18n_["__"])('{{title}}Username{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(external_this_wp_i18n_["__"])('Select customer username', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer usernames including a given username(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Includes', 'customer usernames', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer usernames excluding a given username(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Excludes', 'customer usernames', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'usernames',
        getLabels: async_requests["c" /* getCustomerLabels */]
      }
    },
    email: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('Email', 'woocommerce'),
        placeholder: Object(external_this_wp_i18n_["__"])('Search customer email', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove customer email filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select a customer email filter match', 'woocommerce'),

        /* translators: A sentence describing a customer email filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(external_this_wp_i18n_["__"])('{{title}}Email{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(external_this_wp_i18n_["__"])('Select customer email', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer emails including a given email(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Includes', 'customer emails', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer emails excluding a given email(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Excludes', 'customer emails', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'emails',
        getLabels: Object(async_requests["e" /* getRequestByIdString */])(constants["c" /* NAMESPACE */] + '/customers', function (customer) {
          return {
            id: customer.id,
            label: customer.email
          };
        })
      }
    },
    orders_count: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('No. of Orders', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove order filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select an order count filter match', 'woocommerce'),
        title: Object(external_this_wp_i18n_["__"])('{{title}}No. of Orders{{/title}} {{rule /}} {{filter /}}', 'woocommerce')
      },
      rules: [{
        value: 'max',

        /* translators: Sentence fragment, logical, "Less Than" refers to number of orders a customer has placed, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Less Than', 'number of orders', 'woocommerce')
      }, {
        value: 'min',

        /* translators: Sentence fragment, logical, "More Than" refers to number of orders a customer has placed, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('More Than', 'number of orders', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to number of orders a customer has placed, between two given integers. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Between', 'number of orders', 'woocommerce')
      }],
      input: {
        component: 'Number'
      }
    },
    total_spend: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('Total Spend', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove total spend filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select a total spend filter match', 'woocommerce'),
        title: Object(external_this_wp_i18n_["__"])('{{title}}Total Spend{{/title}} {{rule /}} {{filter /}}', 'woocommerce')
      },
      rules: [{
        value: 'max',

        /* translators: Sentence fragment, logical, "Less Than" refers to total spending by a customer, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Less Than', 'total spend by customer', 'woocommerce')
      }, {
        value: 'min',

        /* translators: Sentence fragment, logical, "Less Than" refers to total spending by a customer, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('More Than', 'total spend by customer', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to total spending by a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Between', 'total spend by customer', 'woocommerce')
      }],
      input: {
        component: 'Currency'
      }
    },
    avg_order_value: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('AOV', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove average order value filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select an average order value filter match', 'woocommerce'),
        title: Object(external_this_wp_i18n_["__"])('{{title}}AOV{{/title}} {{rule /}} {{filter /}}', 'woocommerce')
      },
      rules: [{
        value: 'max',

        /* translators: Sentence fragment, logical, "Less Than" refers to average order value of a customer, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Less Than', 'average order value of customer', 'woocommerce')
      }, {
        value: 'min',

        /* translators: Sentence fragment, logical, "Less Than" refers to average order value of a customer, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('More Than', 'average order value of customer', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Between', 'average order value of customer', 'woocommerce')
      }],
      input: {
        component: 'Currency'
      }
    },
    registered: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('Registered', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove registered filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select a registered filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(external_this_wp_i18n_["__"])('{{title}}Registered{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(external_this_wp_i18n_["__"])('Select registered date', 'woocommerce')
      },
      rules: [{
        value: 'before',

        /* translators: Sentence fragment, logical, "Before" refers to customers registered before a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Before', 'date', 'woocommerce')
      }, {
        value: 'after',

        /* translators: Sentence fragment, logical, "after" refers to customers registered after a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('After', 'date', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Between', 'date', 'woocommerce')
      }],
      input: {
        component: 'Date'
      }
    },
    last_active: {
      labels: {
        add: Object(external_this_wp_i18n_["__"])('Last active', 'woocommerce'),
        remove: Object(external_this_wp_i18n_["__"])('Remove last active filter', 'woocommerce'),
        rule: Object(external_this_wp_i18n_["__"])('Select a last active filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(external_this_wp_i18n_["__"])('{{title}}Last active{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(external_this_wp_i18n_["__"])('Select registered date', 'woocommerce')
      },
      rules: [{
        value: 'before',

        /* translators: Sentence fragment, logical, "Before" refers to customers registered before a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Before', 'date', 'woocommerce')
      }, {
        value: 'after',

        /* translators: Sentence fragment, logical, "after" refers to customers registered after a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('After', 'date', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(external_this_wp_i18n_["_x"])('Between', 'date', 'woocommerce')
      }],
      input: {
        component: 'Date'
      }
    }
  }
});
/*eslint-enable max-len*/
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(59);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/tooltip/index.js
var tooltip = __webpack_require__(110);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: external {"this":["wc","number"]}
var external_this_wc_number_ = __webpack_require__(204);

// EXTERNAL MODULE: ./client/lib/date.js
var date = __webpack_require__(104);

// EXTERNAL MODULE: ./client/analytics/components/report-table/index.js + 2 modules
var report_table = __webpack_require__(746);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(203);

// CONCATENATED MODULE: ./client/analytics/report/customers/table.js








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */






var table_getSetting = Object(settings["g" /* getSetting */])('dataEndpoints', {
  countries: {}
}),
    table_countries = table_getSetting.countries;
/**
 * Internal dependencies
 */





var table_CustomersReportTable = /*#__PURE__*/function (_Component) {
  inherits_default()(CustomersReportTable, _Component);

  var _super = _createSuper(CustomersReportTable);

  function CustomersReportTable() {
    var _this;

    classCallCheck_default()(this, CustomersReportTable);

    _this = _super.call(this);
    _this.getHeadersContent = _this.getHeadersContent.bind(assertThisInitialized_default()(_this));
    _this.getRowsContent = _this.getRowsContent.bind(assertThisInitialized_default()(_this));
    _this.getSummary = _this.getSummary.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(CustomersReportTable, [{
    key: "getHeadersContent",
    value: function getHeadersContent() {
      return [{
        label: Object(external_this_wp_i18n_["__"])('Name', 'woocommerce'),
        key: 'name',
        required: true,
        isLeftAligned: true,
        isSortable: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Username', 'woocommerce'),
        key: 'username',
        hiddenByDefault: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Last Active', 'woocommerce'),
        key: 'date_last_active',
        defaultSort: true,
        isSortable: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Sign Up', 'woocommerce'),
        key: 'date_registered',
        isSortable: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Email', 'woocommerce'),
        key: 'email'
      }, {
        label: Object(external_this_wp_i18n_["__"])('Orders', 'woocommerce'),
        key: 'orders_count',
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Total Spend', 'woocommerce'),
        key: 'total_spend',
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('AOV', 'woocommerce'),
        screenReaderLabel: Object(external_this_wp_i18n_["__"])('Average Order Value', 'woocommerce'),
        key: 'avg_order_value',
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Country / Region', 'woocommerce'),
        key: 'country',
        isSortable: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('City', 'woocommerce'),
        key: 'city',
        hiddenByDefault: true,
        isSortable: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Region', 'woocommerce'),
        key: 'state',
        hiddenByDefault: true,
        isSortable: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Postal Code', 'woocommerce'),
        key: 'postcode',
        hiddenByDefault: true,
        isSortable: true
      }];
    }
  }, {
    key: "getCountryName",
    value: function getCountryName(code) {
      return typeof table_countries[code] !== 'undefined' ? table_countries[code] : null;
    }
  }, {
    key: "getRowsContent",
    value: function getRowsContent(customers) {
      var _this2 = this;

      var dateFormat = Object(settings["g" /* getSetting */])('dateFormat', date["c" /* defaultTableDateFormat */]);
      var _this$context = this.context,
          formatCurrency = _this$context.formatCurrency,
          getCurrencyFormatDecimal = _this$context.formatDecimal,
          getCurrency = _this$context.getCurrency;
      return customers.map(function (customer) {
        var avgOrderValue = customer.avg_order_value,
            dateLastActive = customer.date_last_active,
            dateRegistered = customer.date_registered,
            email = customer.email,
            name = customer.name,
            userId = customer.user_id,
            ordersCount = customer.orders_count,
            username = customer.username,
            totalSpend = customer.total_spend,
            postcode = customer.postcode,
            city = customer.city,
            state = customer.state,
            country = customer.country;

        var countryName = _this2.getCountryName(country);

        var customerNameLink = userId ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
          href: Object(settings["f" /* getAdminLink */])('user-edit.php?user_id=' + userId),
          type: "wp-admin"
        }, name) : name;
        var dateLastActiveDisplay = dateLastActive ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Date"], {
          date: dateLastActive,
          visibleFormat: dateFormat
        }) : '—';
        var dateRegisteredDisplay = dateRegistered ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Date"], {
          date: dateRegistered,
          visibleFormat: dateFormat
        }) : '—';
        var countryDisplay = Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(tooltip["a" /* default */], {
          text: countryName
        }, Object(external_this_wp_element_["createElement"])("span", {
          "aria-hidden": "true"
        }, country)), Object(external_this_wp_element_["createElement"])("span", {
          className: "screen-reader-text"
        }, countryName));
        return [{
          display: customerNameLink,
          value: name
        }, {
          display: username,
          value: username
        }, {
          display: dateLastActiveDisplay,
          value: dateLastActive
        }, {
          display: dateRegisteredDisplay,
          value: dateRegistered
        }, {
          display: Object(external_this_wp_element_["createElement"])("a", {
            href: 'mailto:' + email
          }, email),
          value: email
        }, {
          display: Object(external_this_wc_number_["formatValue"])(getCurrency(), 'number', ordersCount),
          value: ordersCount
        }, {
          display: formatCurrency(totalSpend),
          value: getCurrencyFormatDecimal(totalSpend)
        }, {
          display: formatCurrency(avgOrderValue),
          value: getCurrencyFormatDecimal(avgOrderValue)
        }, {
          display: countryDisplay,
          value: country
        }, {
          display: city,
          value: city
        }, {
          display: state,
          value: state
        }, {
          display: postcode,
          value: postcode
        }];
      });
    }
  }, {
    key: "getSummary",
    value: function getSummary(totals) {
      var _totals$customers_cou = totals.customers_count,
          customersCount = _totals$customers_cou === void 0 ? 0 : _totals$customers_cou,
          _totals$avg_orders_co = totals.avg_orders_count,
          avgOrdersCount = _totals$avg_orders_co === void 0 ? 0 : _totals$avg_orders_co,
          _totals$avg_total_spe = totals.avg_total_spend,
          avgTotalSpend = _totals$avg_total_spe === void 0 ? 0 : _totals$avg_total_spe,
          _totals$avg_avg_order = totals.avg_avg_order_value,
          avgAvgOrderValue = _totals$avg_avg_order === void 0 ? 0 : _totals$avg_avg_order;
      var _this$context2 = this.context,
          formatCurrency = _this$context2.formatCurrency,
          getCurrency = _this$context2.getCurrency;
      var currency = getCurrency();
      return [{
        label: Object(external_this_wp_i18n_["_n"])('customer', 'customers', customersCount, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', customersCount)
      }, {
        label: Object(external_this_wp_i18n_["_n"])('average order', 'average orders', avgOrdersCount, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', avgOrdersCount)
      }, {
        label: Object(external_this_wp_i18n_["__"])('average lifetime spend', 'woocommerce'),
        value: formatCurrency(avgTotalSpend)
      }, {
        label: Object(external_this_wp_i18n_["__"])('average order value', 'woocommerce'),
        value: formatCurrency(avgAvgOrderValue)
      }];
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          isRequesting = _this$props.isRequesting,
          query = _this$props.query,
          filters = _this$props.filters,
          advancedFilters = _this$props.advancedFilters;
      return Object(external_this_wp_element_["createElement"])(report_table["a" /* default */], {
        endpoint: "customers",
        getHeadersContent: this.getHeadersContent,
        getRowsContent: this.getRowsContent,
        getSummary: this.getSummary,
        summaryFields: ['customers_count', 'avg_orders_count', 'avg_total_spend', 'avg_avg_order_value'],
        isRequesting: isRequesting,
        itemIdField: "id",
        query: query,
        labels: {
          placeholder: Object(external_this_wp_i18n_["__"])('Search by customer name', 'woocommerce')
        },
        searchBy: "customers",
        title: Object(external_this_wp_i18n_["__"])('Customers', 'woocommerce'),
        columnPrefsKey: "customers_report_columns",
        filters: filters,
        advancedFilters: advancedFilters
      });
    }
  }]);

  return CustomersReportTable;
}(external_this_wp_element_["Component"]);

table_CustomersReportTable.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var table = (table_CustomersReportTable);
// EXTERNAL MODULE: ./client/analytics/components/report-filters/index.js
var report_filters = __webpack_require__(745);

// CONCATENATED MODULE: ./client/analytics/report/customers/index.js








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function customers_createSuper(Derived) { var hasNativeReflectConstruct = customers_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function customers_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





var customers_CustomersReport = /*#__PURE__*/function (_Component) {
  inherits_default()(CustomersReport, _Component);

  var _super = customers_createSuper(CustomersReport);

  function CustomersReport() {
    classCallCheck_default()(this, CustomersReport);

    return _super.apply(this, arguments);
  }

  createClass_default()(CustomersReport, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          isRequesting = _this$props.isRequesting,
          query = _this$props.query,
          path = _this$props.path;

      var tableQuery = _objectSpread({
        orderby: 'date_last_active',
        order: 'desc'
      }, query);

      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(report_filters["a" /* default */], {
        query: query,
        path: path,
        filters: config_filters,
        showDatePicker: false,
        advancedFilters: config_advancedFilters,
        report: "customers"
      }), Object(external_this_wp_element_["createElement"])(table, {
        isRequesting: isRequesting,
        query: tableQuery,
        filters: config_filters,
        advancedFilters: config_advancedFilters
      }));
    }
  }]);

  return CustomersReport;
}(external_this_wp_element_["Component"]);


customers_CustomersReport.propTypes = {
  query: prop_types_default.a.object.isRequired
};

/***/ }),

/***/ 739:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return getRequestByIdString; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getCategoryLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getCouponLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return getCustomerLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getProductLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return getTaxRateLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "g", function() { return getVariationLabels; });
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(30);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(20);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(740);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(24);
/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */



/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|Function} path - api path string or a function of the query returning api path string
 * @param {Function} [handleData] - function applied to each iteration of data
 * @return {Function} - a function of ids returning a promise
 */

function getRequestByIdString(path) {
  var handleData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : lodash__WEBPACK_IMPORTED_MODULE_2__["identity"];
  return function () {
    var queryString = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    var query = arguments.length > 1 ? arguments[1] : undefined;
    var pathString = typeof path === 'function' ? path(query) : path;
    var idList = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__["getIdsFromQuery"])(queryString);

    if (idList.length < 1) {
      return Promise.resolve([]);
    }

    var payload = {
      include: idList.join(','),
      per_page: idList.length
    };
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__["addQueryArgs"])(pathString, payload)
    }).then(function (data) {
      return data.map(handleData);
    });
  };
}
var getCategoryLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/products/categories', function (category) {
  return {
    key: category.id,
    label: category.name
  };
});
var getCouponLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/coupons', function (coupon) {
  return {
    key: coupon.id,
    label: coupon.code
  };
});
var getCustomerLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/customers', function (customer) {
  return {
    key: customer.id,
    label: customer.name
  };
});
var getProductLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/products', function (product) {
  return {
    key: product.id,
    label: product.name
  };
});
var getTaxRateLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/taxes', function (taxRate) {
  return {
    key: taxRate.id,
    label: Object(analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_4__[/* getTaxCode */ "a"])(taxRate)
  };
});
var getVariationLabels = getRequestByIdString(function (query) {
  return wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + "/products/".concat(query.products, "/variations");
}, function (variation) {
  return {
    key: variation.id,
    label: variation.attributes.reduce(function (desc, attribute, index, arr) {
      return desc + "".concat(attribute.option).concat(arr.length === index + 1 ? '' : ', ');
    }, '')
  };
});

/***/ }),

/***/ 740:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getTaxCode; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

function getTaxCode(tax) {
  return [tax.country, tax.state, tax.name || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('TAX', 'woocommerce'), tax.priority].map(function (item) {
    return item.toString().toUpperCase().trim();
  }).filter(Boolean).join('-');
}

/***/ }),

/***/ 749:
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(750);

var iterableToArrayLimit = __webpack_require__(751);

var unsupportedIterableToArray = __webpack_require__(425);

var nonIterableRest = __webpack_require__(752);

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;

/***/ }),

/***/ 750:
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;

/***/ }),

/***/ 751:
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

/***/ 752:
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;

/***/ })

}]);
