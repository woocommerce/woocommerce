(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["analytics-report-customers"],{

/***/ "./client/analytics/report/customers/config.js":
/*!*****************************************************!*\
  !*** ./client/analytics/report/customers/config.js ***!
  \*****************************************************/
/*! exports provided: filters, advancedFilters */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "filters", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "advancedFilters", function() { return advancedFilters; });
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var lib_async_requests__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! lib/async-requests */ "./client/lib/async-requests/index.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");


/**
 * External dependencies
 */





var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_4__["getSetting"])('dataEndpoints', {
  countries: {}
}),
    countries = _getSetting.countries;
/**
 * Internal dependencies
 */




var CUSTOMERS_REPORT_FILTERS_FILTER = 'woocommerce_admin_customers_report_filters';
var CUSTOMERS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_customers_report_advanced_filters';
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__["applyFilters"])(CUSTOMERS_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Show', 'woocommerce'),
  staticParams: ['paged', 'per_page'],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('All Customers', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Single Customer', 'woocommerce'),
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
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_5__["getCustomerLabels"],
        labels: {
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Type to search for a customer', 'woocommerce'),
          button: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Single Customer', 'woocommerce')
        }
      }
    }]
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Advanced Filters', 'woocommerce'),
    value: 'advanced'
  }]
}]);
/*eslint-disable max-len*/

var advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__["applyFilters"])(CUSTOMERS_REPORT_ADVANCED_FILTERS_FILTER, {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Customers Match {{select /}} Filters', 'A sentence describing filters for Customers. See screen shot for context: https://cloudup.com/cCsm3GeXJbE', 'woocommerce'),
  filters: {
    name: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Name', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove customer name filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a customer name filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Name{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select customer name', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer names including a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'customer names', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer names excluding a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'customer names', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'customers',
        getLabels: Object(lib_async_requests__WEBPACK_IMPORTED_MODULE_5__["getRequestByIdString"])(wc_api_constants__WEBPACK_IMPORTED_MODULE_6__["NAMESPACE"] + '/customers', function (customer) {
          return {
            id: customer.id,
            label: customer.name
          };
        })
      }
    },
    country: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Country / Region', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove country / region filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a country / region filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Country / Region{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select country / region', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to countries including a given country or countries. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'countries', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to countries excluding a given country or countries. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'countries', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'countries',
        getLabels: function () {
          var _getLabels = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(value) {
            var allLabels, labels;
            return regeneratorRuntime.wrap(function _callee$(_context) {
              while (1) {
                switch (_context.prev = _context.next) {
                  case 0:
                    allLabels = countries.map(function (country) {
                      return {
                        key: country.code,
                        label: Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__["decodeEntities"])(country.name)
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
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Username', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search customer username', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove customer username filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a customer username filter match', 'woocommerce'),

        /* translators: A sentence describing a customer username filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Username{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select customer username', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer usernames including a given username(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'customer usernames', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer usernames excluding a given username(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'customer usernames', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'usernames',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_5__["getCustomerLabels"]
      }
    },
    email: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Email', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search customer email', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove customer email filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a customer email filter match', 'woocommerce'),

        /* translators: A sentence describing a customer email filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Email{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select customer email', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer emails including a given email(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'customer emails', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer emails excluding a given email(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'customer emails', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'emails',
        getLabels: Object(lib_async_requests__WEBPACK_IMPORTED_MODULE_5__["getRequestByIdString"])(wc_api_constants__WEBPACK_IMPORTED_MODULE_6__["NAMESPACE"] + '/customers', function (customer) {
          return {
            id: customer.id,
            label: customer.email
          };
        })
      }
    },
    orders_count: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('No. of Orders', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove order filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select an order count filter match', 'woocommerce'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}No. of Orders{{/title}} {{rule /}} {{filter /}}', 'woocommerce')
      },
      rules: [{
        value: 'max',

        /* translators: Sentence fragment, logical, "Less Than" refers to number of orders a customer has placed, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Less Than', 'number of orders', 'woocommerce')
      }, {
        value: 'min',

        /* translators: Sentence fragment, logical, "More Than" refers to number of orders a customer has placed, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('More Than', 'number of orders', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to number of orders a customer has placed, between two given integers. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Between', 'number of orders', 'woocommerce')
      }],
      input: {
        component: 'Number'
      }
    },
    total_spend: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Total Spend', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove total spend filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a total spend filter match', 'woocommerce'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Total Spend{{/title}} {{rule /}} {{filter /}}', 'woocommerce')
      },
      rules: [{
        value: 'max',

        /* translators: Sentence fragment, logical, "Less Than" refers to total spending by a customer, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Less Than', 'total spend by customer', 'woocommerce')
      }, {
        value: 'min',

        /* translators: Sentence fragment, logical, "Less Than" refers to total spending by a customer, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('More Than', 'total spend by customer', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to total spending by a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Between', 'total spend by customer', 'woocommerce')
      }],
      input: {
        component: 'Currency'
      }
    },
    avg_order_value: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('AOV', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove average order value filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select an average order value filter match', 'woocommerce'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}AOV{{/title}} {{rule /}} {{filter /}}', 'woocommerce')
      },
      rules: [{
        value: 'max',

        /* translators: Sentence fragment, logical, "Less Than" refers to average order value of a customer, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Less Than', 'average order value of customer', 'woocommerce')
      }, {
        value: 'min',

        /* translators: Sentence fragment, logical, "Less Than" refers to average order value of a customer, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('More Than', 'average order value of customer', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Between', 'average order value of customer', 'woocommerce')
      }],
      input: {
        component: 'Currency'
      }
    },
    registered: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Registered', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove registered filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a registered filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Registered{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select registered date', 'woocommerce')
      },
      rules: [{
        value: 'before',

        /* translators: Sentence fragment, logical, "Before" refers to customers registered before a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Before', 'date', 'woocommerce')
      }, {
        value: 'after',

        /* translators: Sentence fragment, logical, "after" refers to customers registered after a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('After', 'date', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Between', 'date', 'woocommerce')
      }],
      input: {
        component: 'Date'
      }
    },
    last_active: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Last active', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove last active filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a last active filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Last active{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select registered date', 'woocommerce')
      },
      rules: [{
        value: 'before',

        /* translators: Sentence fragment, logical, "Before" refers to customers registered before a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Before', 'date', 'woocommerce')
      }, {
        value: 'after',

        /* translators: Sentence fragment, logical, "after" refers to customers registered after a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('After', 'date', 'woocommerce')
      }, {
        value: 'between',

        /* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Between', 'date', 'woocommerce')
      }],
      input: {
        component: 'Date'
      }
    }
  }
});
/*eslint-enable max-len*/

/***/ }),

/***/ "./client/analytics/report/customers/index.js":
/*!****************************************************!*\
  !*** ./client/analytics/report/customers/index.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return CustomersReport; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
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
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _config__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./config */ "./client/analytics/report/customers/config.js");
/* harmony import */ var _table__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./table */ "./client/analytics/report/customers/table.js");
/* harmony import */ var analytics_components_report_filters__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! analytics/components/report-filters */ "./client/analytics/components/report-filters/index.js");








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





var CustomersReport = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(CustomersReport, _Component);

  var _super = _createSuper(CustomersReport);

  function CustomersReport() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, CustomersReport);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(CustomersReport, [{
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

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(analytics_components_report_filters__WEBPACK_IMPORTED_MODULE_10__["default"], {
        query: query,
        path: path,
        filters: _config__WEBPACK_IMPORTED_MODULE_8__["filters"],
        showDatePicker: false,
        advancedFilters: _config__WEBPACK_IMPORTED_MODULE_8__["advancedFilters"],
        report: "customers"
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_table__WEBPACK_IMPORTED_MODULE_9__["default"], {
        isRequesting: isRequesting,
        query: tableQuery,
        filters: _config__WEBPACK_IMPORTED_MODULE_8__["filters"],
        advancedFilters: _config__WEBPACK_IMPORTED_MODULE_8__["advancedFilters"]
      }));
    }
  }]);

  return CustomersReport;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);


CustomersReport.propTypes = {
  query: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.object.isRequired
};

/***/ }),

/***/ "./client/analytics/report/customers/table.js":
/*!****************************************************!*\
  !*** ./client/analytics/report/customers/table.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @woocommerce/number */ "@woocommerce/number");
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lib/date */ "./client/lib/date.js");
/* harmony import */ var analytics_components_report_table__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! analytics/components/report-table */ "./client/analytics/components/report-table/index.js");
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lib/currency-context */ "./client/lib/currency-context.js");








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */






var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__["getSetting"])('dataEndpoints', {
  countries: {}
}),
    countries = _getSetting.countries;
/**
 * Internal dependencies
 */





var CustomersReportTable = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(CustomersReportTable, _Component);

  var _super = _createSuper(CustomersReportTable);

  function CustomersReportTable() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, CustomersReportTable);

    _this = _super.call(this);
    _this.getHeadersContent = _this.getHeadersContent.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.getRowsContent = _this.getRowsContent.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.getSummary = _this.getSummary.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(CustomersReportTable, [{
    key: "getHeadersContent",
    value: function getHeadersContent() {
      return [{
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Name', 'woocommerce'),
        key: 'name',
        required: true,
        isLeftAligned: true,
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Username', 'woocommerce'),
        key: 'username',
        hiddenByDefault: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Last Active', 'woocommerce'),
        key: 'date_last_active',
        defaultSort: true,
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Sign Up', 'woocommerce'),
        key: 'date_registered',
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Email', 'woocommerce'),
        key: 'email'
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Orders', 'woocommerce'),
        key: 'orders_count',
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Total Spend', 'woocommerce'),
        key: 'total_spend',
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('AOV', 'woocommerce'),
        screenReaderLabel: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Average Order Value', 'woocommerce'),
        key: 'avg_order_value',
        isNumeric: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Country / Region', 'woocommerce'),
        key: 'country',
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('City', 'woocommerce'),
        key: 'city',
        hiddenByDefault: true,
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Region', 'woocommerce'),
        key: 'state',
        hiddenByDefault: true,
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Postal Code', 'woocommerce'),
        key: 'postcode',
        hiddenByDefault: true,
        isSortable: true
      }];
    }
  }, {
    key: "getCountryName",
    value: function getCountryName(code) {
      return typeof countries[code] !== 'undefined' ? countries[code] : null;
    }
  }, {
    key: "getRowsContent",
    value: function getRowsContent(customers) {
      var _this2 = this;

      var dateFormat = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__["getSetting"])('dateFormat', lib_date__WEBPACK_IMPORTED_MODULE_12__["defaultTableDateFormat"]);
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

        var customerNameLink = userId ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["Link"], {
          href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_11__["getAdminLink"])('user-edit.php?user_id=' + userId),
          type: "wp-admin"
        }, name) : name;
        var dateLastActiveDisplay = dateLastActive ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["Date"], {
          date: dateLastActive,
          visibleFormat: dateFormat
        }) : '—';
        var dateRegisteredDisplay = dateRegistered ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["Date"], {
          date: dateRegistered,
          visibleFormat: dateFormat
        }) : '—';
        var countryDisplay = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Tooltip"], {
          text: countryName
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
          "aria-hidden": "true"
        }, country)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
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
          display: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("a", {
            href: 'mailto:' + email
          }, email),
          value: email
        }, {
          display: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_10__["formatValue"])(getCurrency(), 'number', ordersCount),
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["_n"])('customer', 'customers', customersCount, 'woocommerce'),
        value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_10__["formatValue"])(currency, 'number', customersCount)
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["_n"])('average order', 'average orders', avgOrdersCount, 'woocommerce'),
        value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_10__["formatValue"])(currency, 'number', avgOrdersCount)
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('average lifetime spend', 'woocommerce'),
        value: formatCurrency(avgTotalSpend)
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('average order value', 'woocommerce'),
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(analytics_components_report_table__WEBPACK_IMPORTED_MODULE_13__["default"], {
        endpoint: "customers",
        getHeadersContent: this.getHeadersContent,
        getRowsContent: this.getRowsContent,
        getSummary: this.getSummary,
        summaryFields: ['customers_count', 'avg_orders_count', 'avg_total_spend', 'avg_avg_order_value'],
        isRequesting: isRequesting,
        itemIdField: "id",
        query: query,
        labels: {
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Search by customer name', 'woocommerce')
        },
        searchBy: "customers",
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Customers', 'woocommerce'),
        columnPrefsKey: "customers_report_columns",
        filters: filters,
        advancedFilters: advancedFilters
      });
    }
  }]);

  return CustomersReportTable;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

CustomersReportTable.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_14__["CurrencyContext"];
/* harmony default export */ __webpack_exports__["default"] = (CustomersReportTable);

/***/ }),

/***/ "./client/analytics/report/taxes/utils.js":
/*!************************************************!*\
  !*** ./client/analytics/report/taxes/utils.js ***!
  \************************************************/
/*! exports provided: getTaxCode */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getTaxCode", function() { return getTaxCode; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
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

/***/ "./client/lib/async-requests/index.js":
/*!********************************************!*\
  !*** ./client/lib/async-requests/index.js ***!
  \********************************************/
/*! exports provided: getRequestByIdString, getCategoryLabels, getCouponLabels, getCustomerLabels, getProductLabels, getTaxRateLabels, getVariationLabels */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getRequestByIdString", function() { return getRequestByIdString; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCategoryLabels", function() { return getCategoryLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCouponLabels", function() { return getCouponLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCustomerLabels", function() { return getCustomerLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getProductLabels", function() { return getProductLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getTaxRateLabels", function() { return getTaxRateLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getVariationLabels", function() { return getVariationLabels; });
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! analytics/report/taxes/utils */ "./client/analytics/report/taxes/utils.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
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
var getCategoryLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__["NAMESPACE"] + '/products/categories', function (category) {
  return {
    key: category.id,
    label: category.name
  };
});
var getCouponLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__["NAMESPACE"] + '/coupons', function (coupon) {
  return {
    key: coupon.id,
    label: coupon.code
  };
});
var getCustomerLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__["NAMESPACE"] + '/customers', function (customer) {
  return {
    key: customer.id,
    label: customer.name
  };
});
var getProductLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__["NAMESPACE"] + '/products', function (product) {
  return {
    key: product.id,
    label: product.name
  };
});
var getTaxRateLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__["NAMESPACE"] + '/taxes', function (taxRate) {
  return {
    key: taxRate.id,
    label: Object(analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_4__["getTaxCode"])(taxRate)
  };
});
var getVariationLabels = getRequestByIdString(function (query) {
  return wc_api_constants__WEBPACK_IMPORTED_MODULE_5__["NAMESPACE"] + "/products/".concat(query.products, "/variations");
}, function (variation) {
  return {
    key: variation.id,
    label: variation.attributes.reduce(function (desc, attribute, index, arr) {
      return desc + "".concat(attribute.option).concat(arr.length === index + 1 ? '' : ', ');
    }, '')
  };
});

/***/ })

}]);
//# sourceMappingURL=analytics-report-customers.0edb2f7ecedb71ec6077.min.js.map
