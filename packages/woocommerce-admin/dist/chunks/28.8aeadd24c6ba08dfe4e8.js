(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[28],{

/***/ 797:
/***/ (function(module, exports, __webpack_require__) {

var map = {
	"./advanced-filters/docs/example": 798,
	"./animation-slider/docs/example": 799,
	"./calendar/docs/example": 800,
	"./card/docs/example": 801,
	"./chart/docs/example": 802,
	"./compare-filter/docs/example": 803,
	"./count/docs/example": 804,
	"./date-range-filter-picker/docs/example": 805,
	"./date/docs/example": 806,
	"./dropdown-button/docs/example": 807,
	"./ellipsis-menu/docs/example": 808,
	"./empty-content/docs/example": 809,
	"./filter-picker/docs/example": 810,
	"./filters/docs/example": 811,
	"./flag/docs/example": 812,
	"./form/docs/example": 813,
	"./gravatar/docs/example": 814,
	"./image-upload/docs/example": 815,
	"./link/docs/example": 816,
	"./list/docs/example": 817,
	"./order-status/docs/example": 818,
	"./pagination/docs/example": 819,
	"./product-image/docs/example": 820,
	"./rating/docs/example": 821,
	"./scroll-to/docs/example": 822,
	"./search-list-control/docs/example": 823,
	"./search/docs/example": 824,
	"./section-header/docs/example": 825,
	"./section/docs/example": 826,
	"./segmented-selection/docs/example": 827,
	"./select-control/docs/example": 828,
	"./spinner/docs/example": 829,
	"./stepper/docs/example": 830,
	"./summary/docs/example": 831,
	"./table/docs/example": 832,
	"./tag/docs/example": 833,
	"./text-control-with-affixes/docs/example": 834,
	"./text-control/docs/example": 835,
	"./view-more-list/docs/example": 836,
	"./web-preview/docs/example": 837
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = 797;

/***/ }),

/***/ 798:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(22);


/**
 * Internal dependencies
 */


var ORDER_STATUSES = {
  cancelled: 'Cancelled',
  completed: 'Completed',
  failed: 'Failed',
  'on-hold': 'On hold',
  pending: 'Pending payment',
  processing: 'Processing',
  refunded: 'Refunded'
};
var siteLocale = 'en_US';
var path = new URL(document.location).searchParams.get('path') || '/devdocs';
var query = {
  component: 'advanced-filters'
};
var advancedFilters = {
  title: 'Orders Match {{select /}} Filters',
  filters: {
    status: {
      labels: {
        add: 'Order Status',
        remove: 'Remove order status filter',
        rule: 'Select an order status filter match',
        title: '{{title}}Order Status{{/title}} {{rule /}} {{filter /}}',
        filter: 'Select an order status'
      },
      rules: [{
        value: 'is',
        label: 'Is'
      }, {
        value: 'is_not',
        label: 'Is Not'
      }],
      input: {
        component: 'SelectControl',
        options: Object.keys(ORDER_STATUSES).map(function (key) {
          return {
            value: key,
            label: ORDER_STATUSES[key]
          };
        })
      }
    },
    product: {
      labels: {
        add: 'Products',
        placeholder: 'Search products',
        remove: 'Remove products filter',
        rule: 'Select a product filter match',
        title: '{{title}}Product{{/title}} {{rule /}} {{filter /}}',
        filter: 'Select products'
      },
      rules: [{
        value: 'includes',
        label: 'Includes'
      }, {
        value: 'excludes',
        label: 'Excludes'
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: function getLabels() {
          return Promise.resolve([]);
        }
      }
    },
    customer: {
      labels: {
        add: 'Customer Type',
        remove: 'Remove customer filter',
        rule: 'Select a customer filter match',
        title: '{{title}}Customer is{{/title}} {{filter /}}',
        filter: 'Select a customer type'
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'new',
          label: 'New'
        }, {
          value: 'returning',
          label: 'Returning'
        }],
        defaultOption: 'new'
      }
    },
    quantity: {
      labels: {
        add: 'Item Quantity',
        remove: 'Remove item quantity filter',
        rule: 'Select an item quantity filter match',
        title: '{{title}}Item Quantity is{{/title}} {{rule /}} {{filter /}}'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number'
      }
    },
    subtotal: {
      labels: {
        add: 'Subtotal',
        remove: 'Remove subtotal filter',
        rule: 'Select a subtotal filter match',
        title: '{{title}}Subtotal is{{/title}} {{rule /}} {{filter /}}'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number',
        type: 'currency'
      }
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["AdvancedFilters"], {
    siteLocale: siteLocale,
    path: path,
    query: query,
    filterTitle: "Orders",
    config: advancedFilters,
    currency: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* CURRENCY */ "b"]
  });
});

/***/ }),

/***/ 799:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return MyAnimationSlider; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(62);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * Internal dependencies
 */

/**
 * External dependencies
 */



var MyAnimationSlider = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(MyAnimationSlider, _Component);

  var _super = _createSuper(MyAnimationSlider);

  function MyAnimationSlider() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, MyAnimationSlider);

    _this = _super.call(this);
    _this.state = {
      pages: [44, 55, 66, 77, 88],
      page: 0,
      animate: null
    };
    _this.forward = _this.forward.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.back = _this.back.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(MyAnimationSlider, [{
    key: "forward",
    value: function forward() {
      this.setState(function (state) {
        return {
          page: state.page + 1,
          animate: 'left'
        };
      });
    }
  }, {
    key: "back",
    value: function back() {
      this.setState(function (state) {
        return {
          page: state.page - 1,
          animate: 'right'
        };
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state = this.state,
          page = _this$state.page,
          pages = _this$state.pages,
          animate = _this$state.animate;
      var style = {
        margin: '16px 0',
        padding: '8px 16px',
        color: 'white',
        fontWeight: 'bold',
        backgroundColor: '#246EB9'
      };
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["AnimationSlider"], {
        animationKey: page,
        animate: animate
      }, function () {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          style: style
        }, pages[page]);
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
        onClick: this.back,
        disabled: page === 0
      }, "Back"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("button", {
        onClick: this.forward,
        disabled: page === pages.length - 1
      }, "Forward"));
    }
  }]);

  return MyAnimationSlider;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);



/***/ }),

/***/ 800:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(16);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */



var dateFormat = 'MM/DD/YYYY';
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])({
  after: null,
  afterText: '',
  before: null,
  beforeText: '',
  afterError: null,
  beforeError: null,
  focusedInput: 'startDate'
})(function (_ref) {
  var after = _ref.after,
      afterText = _ref.afterText,
      before = _ref.before,
      beforeText = _ref.beforeText,
      afterError = _ref.afterError,
      focusedInput = _ref.focusedInput,
      setState = _ref.setState;

  function onRangeUpdate(update) {
    setState(update);
  }

  function onDatePickerUpdate(_ref2) {
    var date = _ref2.date,
        text = _ref2.text,
        error = _ref2.error;
    setState({
      after: date,
      afterText: text,
      afterError: error
    });
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Date Range Picker"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["DateRange"], {
    after: after,
    afterText: afterText,
    before: before,
    beforeText: beforeText,
    onUpdate: onRangeUpdate,
    shortDateFormat: dateFormat,
    focusedInput: focusedInput,
    isInvalidDate: function isInvalidDate(date) {
      return moment__WEBPACK_IMPORTED_MODULE_2___default()().isBefore(moment__WEBPACK_IMPORTED_MODULE_2___default()(date), 'date');
    }
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Date Picker"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["DatePicker"], {
    date: after,
    text: afterText,
    error: afterError,
    onUpdate: onDatePickerUpdate,
    dateFormat: dateFormat,
    isInvalidDate: function isInvalidDate(date) {
      return moment__WEBPACK_IMPORTED_MODULE_2___default()(date).day() === 1;
    }
  })));
}));

/***/ }),

/***/ 801:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Card"], {
    title: "Store Performance",
    description: "Key performance metrics"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, "Your stuff in a Card.")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Card"], {
    title: "Inactive Card",
    isInactive: true
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, "This Card is grayed out and has no box-shadow.")));
});

/***/ }),

/***/ 802:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

var data = [{
  date: '2018-05-30T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 21599
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 38537
  },
  Cap: {
    label: 'Cap',
    value: 106010
  }
}, {
  date: '2018-05-31T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 14205
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 24721
  },
  Cap: {
    label: 'Cap',
    value: 70131
  }
}, {
  date: '2018-06-01T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 10581
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 19991
  },
  Cap: {
    label: 'Cap',
    value: 53552
  }
}, {
  date: '2018-06-02T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 9250
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 16072
  },
  Cap: {
    label: 'Cap',
    value: 47821
  }
}];
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Chart"], {
    data: data,
    title: "Example Chart",
    layout: "item-comparison"
  }));
});

/***/ }),

/***/ 803:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(80);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__);



/**
 * Internal dependencies
 */

var path = new URL(document.location).searchParams.get('path') || '/devdocs';
var query = {};
var compareFilter = {
  type: 'products',
  param: 'product',
  getLabels: function getLabels() {
    return Promise.resolve([]);
  },
  labels: {
    helpText: 'Select at least two products to compare',
    placeholder: 'Search for products to compare',
    title: 'Compare Products',
    update: 'Compare'
  }
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["CompareFilter"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    path: path,
    query: query
  }, compareFilter));
});

/***/ }),

/***/ 804:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Count"], {
    count: 33
  });
});

/***/ }),

/***/ 805:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(24);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);


/**
 * Internal dependencies
 */


/**
 * External dependencies
 */


var query = {};
var defaultDateRange = 'period=month&compare=previous_year';
var storeGetDateParamsFromQuery = Object(lodash__WEBPACK_IMPORTED_MODULE_3__["partialRight"])(_woocommerce_date__WEBPACK_IMPORTED_MODULE_2__["getDateParamsFromQuery"], defaultDateRange);
var storeGetCurrentDates = Object(lodash__WEBPACK_IMPORTED_MODULE_3__["partialRight"])(_woocommerce_date__WEBPACK_IMPORTED_MODULE_2__["getCurrentDates"], defaultDateRange);

var _storeGetDateParamsFr = storeGetDateParamsFromQuery(query),
    period = _storeGetDateParamsFr.period,
    compare = _storeGetDateParamsFr.compare,
    before = _storeGetDateParamsFr.before,
    after = _storeGetDateParamsFr.after;

var _storeGetCurrentDates = storeGetCurrentDates(query),
    primaryDate = _storeGetCurrentDates.primary,
    secondaryDate = _storeGetCurrentDates.secondary;

var dateQuery = {
  period: period,
  compare: compare,
  before: before,
  after: after,
  primaryDate: primaryDate,
  secondaryDate: secondaryDate
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["DateRangeFilterPicker"], {
    key: "daterange",
    query: query,
    onRangeSelect: function onRangeSelect() {},
    dateQuery: dateQuery,
    isoDateFormat: _woocommerce_date__WEBPACK_IMPORTED_MODULE_2__["isoDateFormat"]
  });
});

/***/ }),

/***/ 806:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Date"], {
    date: "2019-01-01"
  });
});

/***/ }),

/***/ 807:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(721);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"], {
    renderToggle: function renderToggle(_ref) {
      var isOpen = _ref.isOpen,
          onToggle = _ref.onToggle;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["DropdownButton"], {
        onClick: onToggle,
        isOpen: isOpen,
        labels: ['All Products Sold']
      });
    },
    renderContent: function renderContent() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, "Dropdown content here");
    }
  });
});

/***/ }),

/***/ 808:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(96);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */




/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  showCustomers: true,
  showOrders: true
})(function (_ref) {
  var setState = _ref.setState,
      showCustomers = _ref.showCustomers,
      showOrders = _ref.showOrders;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["EllipsisMenu"], {
    label: "Choose which analytics to display",
    renderContent: function renderContent(_ref2) {
      var onToggle = _ref2.onToggle;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["MenuTitle"], null, "Display Stats"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["MenuItem"], {
        isCheckbox: true,
        isClickable: true,
        checked: showCustomers,
        onInvoke: function onInvoke() {
          return setState({
            showCustomers: !showCustomers
          });
        }
      }, "Show Customers"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["MenuItem"], {
        isCheckbox: true,
        isClickable: true,
        checked: showOrders,
        onInvoke: function onInvoke() {
          return setState({
            showOrders: !showOrders
          });
        }
      }, "Show Orders"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["MenuItem"], {
        isClickable: true,
        onInvoke: onToggle
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"], {
        icon: "no-alt"
      }), "Close Menu"));
    }
  });
}));

/***/ }),

/***/ 809:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["EmptyContent"], {
    title: "Nothing here",
    message: "Some descriptive text",
    actionLabel: "Reload page",
    actionURL: "#"
  });
});

/***/ }),

/***/ 810:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

var path = new URL(document.location).searchParams.get('path') || '/devdocs';
var query = {
  meal: 'breakfast'
};
var config = {
  label: 'Meal',
  staticParams: [],
  param: 'meal',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: 'Breakfast',
    value: 'breakfast'
  }, {
    label: 'Lunch',
    value: 'lunch',
    subFilters: [{
      label: 'Meat',
      value: 'meat',
      path: ['lunch']
    }, {
      label: 'Vegan',
      value: 'vegan',
      path: ['lunch']
    }, {
      label: 'Pescatarian',
      value: 'fish',
      path: ['lunch'],
      subFilters: [{
        label: 'Snapper',
        value: 'snapper',
        path: ['lunch', 'fish']
      }, {
        label: 'Cod',
        value: 'cod',
        path: ['lunch', 'fish']
      }, // Specify a custom component to render (Work in Progress)
      {
        label: 'Other',
        value: 'other_fish',
        path: ['lunch', 'fish'],
        component: 'OtherFish'
      }]
    }]
  }, {
    label: 'Dinner',
    value: 'dinner'
  }]
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["FilterPicker"], {
    config: config,
    path: path,
    query: query
  });
});

/***/ }),

/***/ 811:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(80);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(24);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);



/**
 * Internal dependencies
 */


/**
 * External dependencies
 */


var ORDER_STATUSES = {
  cancelled: 'Cancelled',
  completed: 'Completed',
  failed: 'Failed',
  'on-hold': 'On hold',
  pending: 'Pending payment',
  processing: 'Processing',
  refunded: 'Refunded'
}; // Fetch store default date range and compose with date utility functions.

var defaultDateRange = 'period=month&compare=previous_year';
var storeGetDateParamsFromQuery = Object(lodash__WEBPACK_IMPORTED_MODULE_4__["partialRight"])(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["getDateParamsFromQuery"], defaultDateRange);
var storeGetCurrentDates = Object(lodash__WEBPACK_IMPORTED_MODULE_4__["partialRight"])(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["getCurrentDates"], defaultDateRange); // Package date utilities for filter picker component.

var storeDate = {
  getDateParamsFromQuery: storeGetDateParamsFromQuery,
  getCurrentDates: storeGetCurrentDates,
  isoDateFormat: _woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["isoDateFormat"]
};
var siteLocale = 'en_US';
var path = '';
var query = {};
var filters = [{
  label: 'Show',
  staticParams: ['chart'],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: 'All Orders',
    value: 'all'
  }, {
    label: 'Advanced Filters',
    value: 'advanced'
  }]
}];
var advancedFilters = {
  title: 'Orders Match {{select /}} Filters',
  filters: {
    status: {
      labels: {
        add: 'Order Status',
        remove: 'Remove order status filter',
        rule: 'Select an order status filter match',
        title: 'Order Status {{rule /}} {{filter /}}',
        filter: 'Select an order status'
      },
      rules: [{
        value: 'is',
        label: 'Is'
      }, {
        value: 'is_not',
        label: 'Is Not'
      }],
      input: {
        component: 'SelectControl',
        options: Object.keys(ORDER_STATUSES).map(function (key) {
          return {
            value: key,
            label: ORDER_STATUSES[key]
          };
        })
      }
    },
    product: {
      labels: {
        add: 'Products',
        placeholder: 'Search products',
        remove: 'Remove products filter',
        rule: 'Select a product filter match',
        title: 'Product {{rule /}} {{filter /}}',
        filter: 'Select products'
      },
      rules: [{
        value: 'includes',
        label: 'Includes'
      }, {
        value: 'excludes',
        label: 'Excludes'
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: function getLabels() {
          return Promise.resolve([]);
        }
      }
    },
    customer: {
      labels: {
        add: 'Customer Type',
        remove: 'Remove customer filter',
        rule: 'Select a customer filter match',
        title: 'Customer is {{filter /}}',
        filter: 'Select a customer type'
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'new',
          label: 'New'
        }, {
          value: 'returning',
          label: 'Returning'
        }],
        defaultOption: 'new'
      }
    },
    quantity: {
      labels: {
        add: 'Item Quantity',
        remove: 'Remove item quantity filter',
        rule: 'Select an item quantity filter match',
        title: 'Item Quantity is {{rule /}} {{filter /}}'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number'
      }
    },
    subtotal: {
      labels: {
        add: 'Subtotal',
        remove: 'Remove subtotal filter',
        rule: 'Select a subtotal filter match',
        title: 'Subtotal is {{rule /}} {{filter /}}'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number',
        type: 'currency'
      }
    }
  }
};
var compareFilter = {
  type: 'products',
  param: 'product',
  getLabels: function getLabels() {
    return Promise.resolve([]);
  },
  labels: {
    helpText: 'Select at least two products to compare',
    placeholder: 'Search for products to compare',
    title: 'Compare Products',
    update: 'Compare'
  }
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Date picker only"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["ReportFilters"], {
    path: path,
    query: query,
    storeDate: storeDate
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Date picker & more filters"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["ReportFilters"], {
    filters: filters,
    path: path,
    query: query,
    storeDate: storeDate
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Advanced Filters"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["AdvancedFilters"], {
    siteLocale: siteLocale,
    path: path,
    query: query,
    filterTitle: "Orders",
    config: advancedFilters
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Compare Filter"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["CompareFilter"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    path: path,
    query: query
  }, compareFilter))));
});

/***/ }),

/***/ 812:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Default (inherits parent font size)"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Flag"], {
    code: "VU"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Large"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Flag"], {
    code: "VU",
    size: 48
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Invalid Country Code"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Flag"], {
    code: "invalid country code"
  })));
});

/***/ }),

/***/ 813:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(80);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(724);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(720);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(771);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(912);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(67);



/**
 * Internal dependencies
 */

/**
 * External dependencies
 */



var validate = function validate(values) {
  var errors = {};

  if (!values.firstName) {
    errors.firstName = 'First name is required';
  }

  if (values.lastName.length < 3) {
    errors.lastName = 'Last name must be at least 3 characters';
  }

  return errors;
}; // eslint-disable-next-line no-console


var onSubmitCallback = function onSubmitCallback(values) {
  return console.log(values);
};

var initialValues = {
  firstName: '',
  lastName: '',
  select: '3',
  checkbox: true,
  radio: '2'
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Form"], {
    validate: validate,
    onSubmitCallback: onSubmitCallback,
    initialValues: initialValues
  }, function (_ref) {
    var getInputProps = _ref.getInputProps,
        values = _ref.values,
        errors = _ref.errors,
        handleSubmit = _ref.handleSubmit;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
      label: 'First Name'
    }, getInputProps('firstName'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
      label: 'Last Name'
    }, getInputProps('lastName'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
      label: "Select",
      options: [{
        label: 'Option 1',
        value: '1'
      }, {
        label: 'Option 2',
        value: '2'
      }, {
        label: 'Option 3',
        value: '3'
      }]
    }, getInputProps('select'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
      label: "Checkbox"
    }, getInputProps('checkbox'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
      label: "Radio",
      options: [{
        label: 'Option 1',
        value: '1'
      }, {
        label: 'Option 2',
        value: '2'
      }, {
        label: 'Option 3',
        value: '3'
      }]
    }, getInputProps('radio'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"], {
      isPrimary: true,
      onClick: handleSubmit,
      disabled: Object.keys(errors).length
    }, "Submit"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("h3", null, "Return data:"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("pre", null, "Values: ", JSON.stringify(values), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("br", null), "Errors: ", JSON.stringify(errors)));
  });
});

/***/ }),

/***/ 814:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Gravatar"], {
    user: "email@example.org",
    size: 48
  });
});

/***/ }),

/***/ 815:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  image: null
})(function (_ref) {
  var setState = _ref.setState,
      logo = _ref.logo;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ImageUpload"], {
    image: logo,
    onChange: function onChange(image) {
      return setState({
        logo: image
      });
    }
  });
}));

/***/ }),

/***/ 816:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Link"], {
    href: "edit.php?post_type=shop_coupon",
    type: "wp-admin"
  }, "Coupons");
});

/***/ }),

/***/ 817:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(90);
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_2__);


/* eslint-disable no-alert */

/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


var listItems = [{
  title: 'List item title',
  content: 'List item description text'
}, {
  before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_2___default.a, {
    icon: "star"
  }),
  title: 'List item with before icon',
  content: 'List item description text'
}, {
  before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_2___default.a, {
    icon: "star"
  }),
  after: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_2___default.a, {
    icon: "chevron-right"
  }),
  title: 'List item with before and after icons',
  content: 'List item description text'
}, {
  title: 'Clickable list item',
  content: 'List item description text',
  // eslint-disable-next-line no-undef
  onClick: function onClick() {
    return alert('List item clicked');
  }
}];
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["List"], {
    items: listItems
  }));
});

/***/ }),

/***/ 818:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


var orderStatusMap = {
  processing: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Processing Order'),
  pending: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Pending Order'),
  completed: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Completed Order')
};
/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["OrderStatus"], {
    order: {
      status: 'processing'
    },
    orderStatusMap: orderStatusMap
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["OrderStatus"], {
    order: {
      status: 'pending'
    },
    orderStatusMap: orderStatusMap
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["OrderStatus"], {
    order: {
      status: 'completed'
    },
    orderStatusMap: orderStatusMap
  }));
});

/***/ }),

/***/ 819:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  page: 2,
  perPage: 50
})(function (_ref) {
  var page = _ref.page,
      perPage = _ref.perPage,
      setState = _ref.setState;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Pagination"], {
    page: page,
    perPage: perPage,
    total: 500,
    onPageChange: function onPageChange(newPage) {
      return setState({
        page: newPage
      });
    },
    onPerPageChange: function onPerPageChange(newPerPage) {
      return setState({
        perPage: newPerPage
      });
    }
  });
}));

/***/ }),

/***/ 820:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ProductImage"], {
    product: null
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ProductImage"], {
    product: {
      images: []
    }
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ProductImage"], {
    product: {
      images: [{
        src: 'https://cldup.com/6L9h56D9Bw.jpg'
      }]
    }
  }));
});

/***/ }),

/***/ 821:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  var product = {
    average_rating: 3.5
  };
  var review = {
    rating: 5
  };
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Rating"], {
    rating: 4,
    totalStars: 5
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Rating"], {
    rating: 2.5,
    totalStars: 6
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ProductRating"], {
    product: product
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ReviewRating"], {
    review: review
  })));
});

/***/ }),

/***/ 822:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */


var MyScrollTo = function MyScrollTo() {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ScrollTo"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, "Have the web browser automatically scroll to this component on page render."));
}; // The ScrollTo Component will trigger scrolling if rendered on the main docs page.


/* harmony default export */ __webpack_exports__["default"] = (MyScrollTo);

/***/ }),

/***/ 823:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  selected: [],
  loading: true
})(function (_ref) {
  var selected = _ref.selected,
      loading = _ref.loading,
      setState = _ref.setState;
  var list = [{
    id: 1,
    name: 'Apricots'
  }, {
    id: 2,
    name: 'Clementine'
  }, {
    id: 3,
    name: 'Elderberry'
  }, {
    id: 4,
    name: 'Guava'
  }, {
    id: 5,
    name: 'Lychee'
  }, {
    id: 6,
    name: 'Mulberry'
  }];
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("button", {
    onClick: function onClick() {
      return setState({
        loading: !loading
      });
    }
  }, "Toggle loading state"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SearchListControl"], {
    list: list,
    isLoading: loading,
    selected: selected,
    onChange: function onChange(items) {
      return setState({
        selected: items
      });
    }
  }));
}));

/***/ }),

/***/ 824:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  selected: [],
  inlineSelected: []
})(function (_ref) {
  var selected = _ref.selected,
      inlineSelected = _ref.inlineSelected,
      setState = _ref.setState;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Tags Below Input"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Search"], {
    type: "products",
    placeholder: "Search for a product",
    selected: selected,
    onChange: function onChange(items) {
      return setState({
        selected: items
      });
    }
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Tags Inline with Input"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Search"], {
    type: "products",
    placeholder: "Search for a product",
    selected: inlineSelected,
    onChange: function onChange(items) {
      return setState({
        inlineSelected: items
      });
    },
    inlineTags: true
  })));
}));

/***/ }),

/***/ 825:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SectionHeader"], {
    title: 'Store Performance'
  });
});

/***/ }),

/***/ 826:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Header using a contextual level (h3)"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: "article"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, "This is an article component wrapper."), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "Another header with contextual level (h4)"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, "There is no wrapper component here."), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["H"], null, "This is an h5"))));
});

/***/ }),

/***/ 827:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


var name = 'number';
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  selected: 'two'
})(function (_ref) {
  var selected = _ref.selected,
      setState = _ref.setState;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SegmentedSelection"], {
    options: [{
      value: 'one',
      label: 'One'
    }, {
      value: 'two',
      label: 'Two'
    }, {
      value: 'three',
      label: 'Three'
    }, {
      value: 'four',
      label: 'Four'
    }],
    selected: selected,
    legend: "Select a number",
    onSelect: function onSelect(data) {
      return setState({
        selected: data[name]
      });
    },
    name: name
  });
}));

/***/ }),

/***/ 828:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


var options = [{
  key: 'apple',
  label: 'Apple',
  value: {
    id: 'apple'
  }
}, {
  key: 'apricot',
  label: 'Apricot',
  value: {
    id: 'apricot'
  }
}, {
  key: 'banana',
  label: 'Banana',
  keywords: ['best', 'fruit'],
  value: {
    id: 'banana'
  }
}, {
  key: 'blueberry',
  label: 'Blueberry',
  value: {
    id: 'blueberry'
  }
}, {
  key: 'cherry',
  label: 'Cherry',
  value: {
    id: 'cherry'
  }
}, {
  key: 'cantaloupe',
  label: 'Cantaloupe',
  value: {
    id: 'cantaloupe'
  }
}, {
  key: 'dragonfruit',
  label: 'Dragon Fruit',
  value: {
    id: 'dragonfruit'
  }
}, {
  key: 'elderberry',
  label: 'Elderberry',
  value: {
    id: 'elderberry'
  }
}];
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  simpleSelected: [],
  simpleMultipleSelected: [],
  singleSelected: [],
  singleSelectedShowAll: [],
  multipleSelected: [],
  inlineSelected: []
})(function (_ref) {
  var simpleSelected = _ref.simpleSelected,
      simpleMultipleSelected = _ref.simpleMultipleSelected,
      singleSelected = _ref.singleSelected,
      singleSelectedShowAll = _ref.singleSelectedShowAll,
      multipleSelected = _ref.multipleSelected,
      inlineSelected = _ref.inlineSelected,
      setState = _ref.setState;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SelectControl"], {
    label: "Simple single value",
    onChange: function onChange(selected) {
      return setState({
        simpleSelected: selected
      });
    },
    options: options,
    placeholder: "Start typing to filter options...",
    selected: simpleSelected
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SelectControl"], {
    label: "Multiple values",
    multiple: true,
    onChange: function onChange(selected) {
      return setState({
        simpleMultipleSelected: selected
      });
    },
    options: options,
    placeholder: "Start typing to filter options...",
    selected: simpleMultipleSelected
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SelectControl"], {
    label: "Single value searchable",
    isSearchable: true,
    onChange: function onChange(selected) {
      return setState({
        singleSelected: selected
      });
    },
    options: options,
    placeholder: "Start typing to filter options...",
    selected: singleSelected
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SelectControl"], {
    label: "Single value searchable with options on refocus",
    isSearchable: true,
    onChange: function onChange(selected) {
      return setState({
        singleSelectedShowAll: selected
      });
    },
    options: options,
    placeholder: "Start typing to filter options...",
    selected: singleSelectedShowAll,
    showAllOnFocus: true
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SelectControl"], {
    label: "Inline tags searchable",
    isSearchable: true,
    multiple: true,
    inlineTags: true,
    onChange: function onChange(selected) {
      return setState({
        inlineSelected: selected
      });
    },
    options: options,
    placeholder: "Start typing to filter options...",
    selected: inlineSelected
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SelectControl"], {
    hideBeforeSearch: true,
    isSearchable: true,
    label: "Hidden options before search",
    multiple: true,
    onChange: function onChange(selected) {
      return setState({
        multipleSelected: selected
      });
    },
    options: options,
    placeholder: "Start typing to filter options...",
    selected: multipleSelected,
    showClearButton: true
  }));
}));

/***/ }),

/***/ 829:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Spinner"], null));
});

/***/ }),

/***/ 830:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  currentStep: 'first',
  isComplete: false,
  isPending: false
})(function (_ref) {
  var currentStep = _ref.currentStep,
      isComplete = _ref.isComplete,
      isPending = _ref.isPending,
      setState = _ref.setState;

  var goToStep = function goToStep(key) {
    setState({
      currentStep: key
    });
  };

  var steps = [{
    key: 'first',
    label: 'First',
    description: 'Step item description',
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, "First step content."),
    onClick: goToStep
  }, {
    key: 'second',
    label: 'Second',
    description: 'Step item description',
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, "Second step content."),
    onClick: goToStep
  }, {
    label: 'Third',
    key: 'third',
    description: 'Step item description',
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, "Third step content."),
    onClick: goToStep
  }, {
    label: 'Fourth',
    key: 'fourth',
    description: 'Step item description',
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, "Fourth step content."),
    onClick: goToStep
  }];
  var currentIndex = steps.findIndex(function (s) {
    return currentStep === s.key;
  });

  if (isComplete) {
    steps.forEach(function (s) {
      return s.isComplete = true;
    });
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, isComplete ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("button", {
    onClick: function onClick() {
      return setState({
        currentStep: 'first',
        isComplete: false
      });
    }
  }, "Reset") : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("button", {
    onClick: function onClick() {
      return setState({
        currentStep: steps[currentIndex - 1].key
      });
    },
    disabled: currentIndex < 1
  }, "Previous step"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("button", {
    onClick: function onClick() {
      return setState({
        currentStep: steps[currentIndex + 1].key
      });
    },
    disabled: currentIndex >= steps.length - 1
  }, "Next step"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("button", {
    onClick: function onClick() {
      return setState({
        isComplete: true
      });
    },
    disabled: currentIndex !== steps.length - 1
  }, "Complete"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("button", {
    onClick: function onClick() {
      return setState({
        isPending: !isPending
      });
    }
  }, "Toggle Spinner")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Stepper"], {
    steps: steps,
    currentStep: currentStep,
    isPending: isPending
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Stepper"], {
    isPending: isPending,
    isVertical: true,
    steps: steps,
    currentStep: currentStep
  }));
}));

/***/ }),

/***/ 831:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SummaryList"], null, function () {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SummaryNumber"], {
      key: "revenue",
      value: '$829.40',
      label: "Total Sales",
      delta: 29,
      href: "/analytics/report"
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SummaryNumber"], {
      key: "refunds",
      value: '$24.00',
      label: "Refunds",
      delta: -10,
      href: "/analytics/report",
      selected: true
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["SummaryNumber"], {
      key: "coupons",
      value: '$49.90',
      label: "Coupons",
      href: "/analytics/report"
    })];
  });
});

/***/ }),

/***/ 832:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(728);



/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


var headers = [{
  key: 'month',
  label: 'Month'
}, {
  key: 'orders',
  label: 'Orders'
}, {
  key: 'revenue',
  label: 'Revenue'
}];
var rows = [[{
  display: 'January',
  value: 1
}, {
  display: 10,
  value: 10
}, {
  display: '$530.00',
  value: 530
}], [{
  display: 'February',
  value: 2
}, {
  display: 13,
  value: 13
}, {
  display: '$675.00',
  value: 675
}], [{
  display: 'March',
  value: 3
}, {
  display: 9,
  value: 9
}, {
  display: '$460.00',
  value: 460
}]];
var summary = [{
  label: 'Gross Income',
  value: '$830.00'
}, {
  label: 'Taxes',
  value: '$96.32'
}, {
  label: 'Shipping',
  value: '$50.00'
}];
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])({
  query: {
    paged: 1
  }
})(function (_ref) {
  var query = _ref.query,
      setState = _ref.setState;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "TableCard"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["TableCard"], {
    title: "Revenue Last Week",
    rows: rows,
    headers: headers,
    onQueryChange: function onQueryChange(param) {
      return function (value) {
        return setState({
          query: _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, param, value)
        });
      };
    },
    query: query,
    rowsPerPage: 7,
    totalRows: 10,
    summary: summary
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Table only"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Card"], {
    className: "woocommerce-analytics__card"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Table"], {
    caption: "Revenue Last Week",
    rows: rows,
    headers: headers
  }))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Summary only"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["TableSummary"], {
    data: summary
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Placeholder"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Card"], {
    className: "woocommerce-analytics__card"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["TablePlaceholder"], {
    caption: "Revenue Last Week",
    headers: headers
  }))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["H"], null, "Empty Table"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Section"], {
    component: false
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["EmptyTable"], null, "There are no entries.")));
}));

/***/ }),

/***/ 833:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */


var noop = function noop() {};

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Tag"], {
    label: "My tag",
    id: 1
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Tag"], {
    label: "Removable tag",
    id: 2,
    remove: noop
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["Tag"], {
    label: "Tag with popover",
    popoverContents: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, "This is a popover")
  }));
});

/***/ }),

/***/ 834:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  first: '',
  second: '',
  third: '',
  fourth: '',
  fifth: ''
})(function (_ref) {
  var first = _ref.first,
      second = _ref.second,
      third = _ref.third,
      fourth = _ref.fourth,
      fifth = _ref.fifth,
      setState = _ref.setState;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    label: "Text field without affixes",
    value: first,
    placeholder: "Placeholder",
    onChange: function onChange(value) {
      return setState({
        first: value
      });
    }
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    label: "Disabled text field without affixes",
    value: first,
    placeholder: "Placeholder",
    onChange: function onChange(value) {
      return setState({
        first: value
      });
    },
    disabled: true
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    prefix: "$",
    label: "Text field with a prefix",
    value: second,
    onChange: function onChange(value) {
      return setState({
        second: value
      });
    }
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    prefix: "$",
    label: "Disabled text field with a prefix",
    value: second,
    onChange: function onChange(value) {
      return setState({
        second: value
      });
    },
    disabled: true
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    prefix: "Prefix",
    suffix: "Suffix",
    label: "Text field with both affixes",
    value: third,
    onChange: function onChange(value) {
      return setState({
        third: value
      });
    }
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    prefix: "Prefix",
    suffix: "Suffix",
    label: "Disabled text field with both affixes",
    value: third,
    onChange: function onChange(value) {
      return setState({
        third: value
      });
    },
    disabled: true
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    suffix: "%",
    label: "Text field with a suffix",
    value: fourth,
    onChange: function onChange(value) {
      return setState({
        fourth: value
      });
    }
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    suffix: "%",
    label: "Disabled text field with a suffix",
    value: fourth,
    onChange: function onChange(value) {
      return setState({
        fourth: value
      });
    },
    disabled: true
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    prefix: "$",
    label: "Text field with prefix and help text",
    value: fifth,
    onChange: function onChange(value) {
      return setState({
        fifth: value
      });
    },
    help: "This is some help text."
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControlWithAffixes"], {
    prefix: "$",
    label: "Disabled text field with prefix and help text",
    value: fifth,
    onChange: function onChange(value) {
      return setState({
        fifth: value
      });
    },
    help: "This is some help text.",
    disabled: true
  }));
}));

/***/ }),

/***/ 835:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(728);


/**
 * Internal dependencies
 */

/**
 * External dependencies
 */


/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])({
  value: ''
})(function (_ref) {
  var setState = _ref.setState,
      value = _ref.value;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControl"], {
    name: "text-control",
    label: "Enter text here",
    onChange: function onChange(newValue) {
      return setState({
        value: newValue
      });
    },
    value: value
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["TextControl"], {
    label: "Disabled field",
    disabled: true,
    value: ""
  }));
}));

/***/ }),

/***/ 836:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["ViewMoreList"] // eslint-disable-next-line react/jsx-key
  , {
    items: [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("i", null, "Lorem"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("i", null, "Ipsum"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("i", null, "Dolor"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("i", null, "Sit")]
  });
});

/***/ }),

/***/ 837:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__["WebPreview"], {
    src: "https://themes.woocommerce.com/?name=galleria",
    title: "My Web Preview"
  }));
});

/***/ }),

/***/ 843:
/***/ (function(module, exports, __webpack_require__) {

var map = {
	"./advanced-filters/README.md": 844,
	"./animation-slider/README.md": 845,
	"./calendar/README.md": 846,
	"./card/README.md": 847,
	"./chart/README.md": 848,
	"./chart/d3chart/d3base/README.md": 849,
	"./compare-filter/README.md": 850,
	"./count/README.md": 851,
	"./date-range-filter-picker/README.md": 852,
	"./date/README.md": 853,
	"./dropdown-button/README.md": 854,
	"./ellipsis-menu/README.md": 855,
	"./empty-content/README.md": 856,
	"./filter-picker/README.md": 857,
	"./filters/README.md": 858,
	"./flag/README.md": 859,
	"./form/README.md": 860,
	"./gravatar/README.md": 861,
	"./higher-order/use-filters/README.md": 862,
	"./image-upload/README.md": 863,
	"./link/README.md": 864,
	"./list/README.md": 865,
	"./order-status/README.md": 866,
	"./pagination/README.md": 867,
	"./plugins/README.md": 868,
	"./product-image/README.md": 869,
	"./rating/README.md": 870,
	"./scroll-to/README.md": 871,
	"./search-list-control/README.md": 872,
	"./search/README.md": 873,
	"./section-header/README.md": 874,
	"./section/README.md": 875,
	"./segmented-selection/README.md": 876,
	"./select-control/README.md": 877,
	"./spinner/README.md": 878,
	"./stepper/README.md": 879,
	"./summary/README.md": 880,
	"./table/README.md": 881,
	"./tag/README.md": 882,
	"./text-control-with-affixes/README.md": 883,
	"./text-control/README.md": 884,
	"./view-more-list/README.md": 885,
	"./web-preview/README.md": 886
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = 843;

/***/ }),

/***/ 844:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Advanced Filters\n===\n\nDisplays a configurable set of filters which can modify query parameters. Display, behavior, and types of filters can be designated by a configuration object.\n\n## Usage\n\nBelow is a config example complete with translation strings. Advanced Filters makes use of [interpolateComponents](https://github.com/Automattic/interpolate-components#readme) to organize sentence structure, resulting in a filter visually represented as a sentence fragment in any language.\n\n```js\nconst config = {\n\ttitle: __(\n\t\t// A sentence describing filters for Orders\n\t\t// See screen shot for context: https://cloudup.com/cSsUY9VeCVJ\n\t\t'Orders Match {{select /}} Filters',\n\t\t'woocommerce'\n\t),\n\tfilters: {\n\t\tstatus: {\n\t\t\tlabels: {\n\t\t\t\tadd: __( 'Order Status', 'woocommerce' ),\n\t\t\t\tremove: __( 'Remove order status filter', 'woocommerce' ),\n\t\t\t\trule: __( 'Select an order status filter match', 'woocommerce' ),\n\t\t\t\t// A sentence describing an Order Status filter\n\t\t\t\t// See screen shot for context: https://cloudup.com/cSsUY9VeCVJ\n\t\t\t\ttitle: __( 'Order Status {{rule /}} {{filter /}}', 'woocommerce' ),\n\t\t\t\tfilter: __( 'Select an order status', 'woocommerce' ),\n\t\t\t},\n\t\t\trules: [\n\t\t\t\t{\n\t\t\t\t\tvalue: 'is',\n\t\t\t\t\t// Sentence fragment, logical, \"Is\"\n\t\t\t\t\t// Refers to searching for orders matching a chosen order status\n\t\t\t\t\t// Screenshot for context: https://cloudup.com/cSsUY9VeCVJ\n\t\t\t\t\tlabel: _x( 'Is', 'order status', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t\t{\n\t\t\t\t\tvalue: 'is_not',\n\t\t\t\t\t// Sentence fragment, logical, \"Is Not\"\n\t\t\t\t\t// Refers to searching for orders that don't match a chosen order status\n\t\t\t\t\t// Screenshot for context: https://cloudup.com/cSsUY9VeCVJ\n\t\t\t\t\tlabel: _x( 'Is Not', 'order status', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t],\n\t\t\tinput: {\n\t\t\t\tcomponent: 'SelectControl',\n\t\t\t\toptions: Object.keys( orderStatuses ).map( key => ( {\n\t\t\t\t\tvalue: key,\n\t\t\t\t\tlabel: orderStatuses[ key ],\n\t\t\t\t} ) ),\n\t\t\t},\n\t\t},\n\t},\n};\n```\n\nWhen filters are applied, the query string will be modified using a combination of rule names and selected filter values.\n\nTaking the above configuration as an example, applying the filter will result in a query parameter like `status_is=pending` or `status_is_not=cancelled`.\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`config` | Object | `null` | (required) The configuration object required to render filters. See example above.\n`path` | String | `null` | (required) Name of this filter, used in translations.\n`query` | Object | `null` | The query string represented in object form.\n`onAdvancedFilterAction` | Function | `null` | Function to be called after an advanced filter action has been taken.\n`siteLocale` | string | `'en_US'` | The siteLocale for the site.\n`currency` | Object | `null` | (required) The currency instance for the site (@woocommerce/currency).\n\n\n## Input Components\n\n\n### SelectControl\n\nRender a select component with options.\n\n```js\nconst config = {\n\t...,\n\tfilters: {\n\t\tfruit: {\n\t\t\tinput: {\n\t\t\t\tcomponent: 'SelectControl',\n\t\t\t\toptions: [\n\t\t\t\t\t{ label: 'Apples', key: 'apples' },\n\t\t\t\t\t{ label: 'Oranges', key: 'oranges' },\n\t\t\t\t\t{ label: 'Bananas', key: 'bananas' },\n\t\t\t\t\t{ label: 'Cherries', key: 'cherries' },\n\t\t\t\t],\n\t\t\t},\n\t\t},\n\t},\n};\n```\n\n`options`: An array of objects with `key` and `label` properties.\n\n\n### Search\n\nRender an input for users to search and select using an autocomplete.\n\n```js\nconst config = {\n\t...,\n\tfilters: {\n\t\tproduct: {\n\t\t\tinput: {\n\t\t\t\tcomponent: 'Search',\n\t\t\t\ttype: 'products',\n\t\t\t\tgetLabels: getRequestByIdString( NAMESPACE + 'products', product => ( {\n\t\t\t\t\tid: product.id,\n\t\t\t\t\tlabel: product.name,\n\t\t\t\t} ) ),\n\t\t\t},\n\t\t},\n\t},\n};\n```\n\n`type`: A string Autocompleter type used by the [Search Component](https://github.com/woocommerce/woocommerce-admin/tree/master/packages/components/src/search).\n`getLabels`: A function returning a Promise resolving to an array of objects with `id` and `label` properties.\n\n\n### Date\n\nRenders an input or two inputs allowing a user to filter based on a date value or range of values.\n\n```js\nconst config = {\n\t...,\n\tfilters: {\n\t\tregistered: {\n\t\t\trules: [\n\t\t\t\t{\n\t\t\t\t\tvalue: 'before',\n\t\t\t\t\tlabel: __( 'Before', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t\t{\n\t\t\t\t\tvalue: 'after',\n\t\t\t\t\tlabel: __( 'After', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t\t{\n\t\t\t\t\tvalue: 'between',\n\t\t\t\t\tlabel: __( 'Between', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t],\n\t\t\tinput: {\n\t\t\t\tcomponent: 'Date',\n\t\t\t},\n\t\t},\n\t},\n};\n```\n\n\n### Numeric Value\n\nRenders an input or two inputs allowing a user to filter based on a numeric value or range of values. Can also render inputs for currency values.\n\nValid rule values are `after`, `before`, and `between`. Use any combination you'd like.\n\n```js\nconst config = {\n\t...,\n\tfilters: {\n\t\tquantity: {\n\t\t\trules: [\n\t\t\t\t{\n\t\t\t\t\tvalue: 'lessthan',\n\t\t\t\t\tlabel: __( 'Less Than', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t\t{\n\t\t\t\t\tvalue: 'morethan',\n\t\t\t\t\tlabel: __( 'More Than', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t\t{\n\t\t\t\t\tvalue: 'between',\n\t\t\t\t\tlabel: __( 'Between', 'woocommerce' ),\n\t\t\t\t},\n\t\t\t],\n\t\t\tinput: {\n\t\t\t\tcomponent: 'Number',\n\t\t\t},\n\t\t},\n\t},\n};\n```\n\nValid rule values are `lessthan`, `morethan`, and `between`. Use any combination you'd like.\n\nSpecify `input.type` as `'currency'` if you'd like to render currency inputs, which respects store currency locale.\n");

/***/ }),

/***/ 845:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("AnimationSlider\n===\n\nThis component creates slideable content controlled by an animate prop to direct the contents to slide left or right.\nAll other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition\n\n## Usage\n\n```jsx\n<AnimationSlider animationKey=\"1\" animate=\"right\">\n\t{ ( status ) => (\n\t\t<span>One (1)</span>\n\t) }\n</AnimationSlider>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`children` | function | `null` | (required) A function returning rendered content with argument status, reflecting `CSSTransition` status\n`animationKey` | any | `null` | (required) A unique identifier for each slideable page\n`animate` | string | `null` | null, 'left', 'right', to designate which direction to slide on a change\n`onExited` | function | `null` | A function to be executed after a transition is complete, passing the containing ref as the argument\n");

/***/ }),

/***/ 846:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("DatePicker\n===\n\n## Usage\n\n```jsx\n<DatePicker\n\tdate={ date }\n\ttext={ text }\n\terror={ error }\n\tonUpdate={ ( { date, text, error } ) => setState( { date, text, error } ) }\n\tdateFormat=\"MM/DD/YYYY\"\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`date` | Object | `null` | A moment date object representing the selected date. `null` for no selection\n`disabled` | Boolean | `null` | Whether the input is disabled\n`text` | String | `null` | The date in human-readable format. Displayed in the text input\n`error` | String | `null` | A string error message, shown to the user\n`onUpdate` | Function | `null` | (required) A function called upon selection of a date or input change\n`dateFormat` | String | `null` | (required) The date format in moment.js-style tokens\n`isInvalidDate` | Function | `null` | A function to determine if a day on the calendar is not valid\n\n\nDateRange\n===\n\nThis is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.\n\n## Usage\n\n```jsx\n<DateRange\n\tafter={ after }\n\tafterText={ afterText }\n\tbefore={ before }\n\tbeforeText={ beforeText }\n\tonUpdate={ ( update ) => setState( update ) }\n\tshortDateFormat=\"MM/DD/YYYY\"\n\tfocusedInput=\"startDate\"\n\tisInvalidDate={ date => (\n\t\t// not a future date\n\t\tmoment().isBefore( moment( date ), 'date' )\n\t) }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`after` | Object | `null` | A moment date object representing the selected start. `null` for no selection\n`afterError` | String | `null` | A string error message, shown to the user\n`afterText` | String | `null` | The start date in human-readable format. Displayed in the text input\n`before` | Object | `null` | A moment date object representing the selected end. `null` for no selection\n`beforeError` | String | `null` | A string error message, shown to the user\n`beforeText` | String | `null` | The end date in human-readable format. Displayed in the text input\n`focusedInput` | String | `null` | String identifying which is the currently focused input (start or end)\n`isInvalidDate` | Function | `null` | A function to determine if a day on the calendar is not valid\n`onUpdate` | Function | `null` | (required) A function called upon selection of a date\n`shortDateFormat` | String | `null` | (required) The date format in moment.js-style tokens\n");

/***/ }),

/***/ 847:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Card\n===\n\nA basic card component with a header. The header can contain a title, an action, and an `EllipsisMenu` menu.\n\n## Usage\n\n```jsx\n<div>\n\t<Card title=\"Store Performance\" description=\"Key performance metrics\">\n\t\t<p>Your stuff in a Card.</p>\n\t</Card>\n\t<Card title=\"Inactive Card\" isInactive>\n\t\t<p>This Card is grayed out and has no box-shadow.</p>\n\t</Card>\n</div>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`action` | ReactNode | `null` | One \"primary\" action for this card, appears in the card header\n`className` | String | `null` | Additional CSS classes\n`description` | One of type: string, node | `null` | The description displayed beneath the title\n`isInactive` | Boolean | `null` | Boolean representing whether the card is inactive or not\n`menu` | (custom validator) | `null` | An `EllipsisMenu`, with filters used to control the content visible in this card\n`title` | One of type: string, node | `null` | The title to use for this card\n");

/***/ }),

/***/ 848:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Chart\n===\n\nA chart container using d3, to display timeseries data with an interactive legend.\n\n## Usage\n\n```jsx\nconst data = [\n\t{\n\t\tdate: '2018-05-30T00:00:00',\n\t\tHoodie: {\n\t\t\tlabel: 'Hoodie',\n\t\t\tvalue: 21599,\n\t\t},\n\t\tSunglasses: {\n\t\t\tlabel: 'Sunglasses',\n\t\t\tvalue: 38537,\n\t\t},\n\t\tCap: {\n\t\t\tlabel: 'Cap',\n\t\t\tvalue: 106010,\n\t\t},\n\t},\n\t{\n\t\tdate: '2018-05-31T00:00:00',\n\t\tHoodie: {\n\t\t\tlabel: 'Hoodie',\n\t\t\tvalue: 14205,\n\t\t},\n\t\tSunglasses: {\n\t\t\tlabel: 'Sunglasses',\n\t\t\tvalue: 24721,\n\t\t},\n\t\tCap: {\n\t\t\tlabel: 'Cap',\n\t\t\tvalue: 70131,\n\t\t},\n\t},\n\t{\n\t\tdate: '2018-06-01T00:00:00',\n\t\tHoodie: {\n\t\t\tlabel: 'Hoodie',\n\t\t\tvalue: 10581,\n\t\t},\n\t\tSunglasses: {\n\t\t\tlabel: 'Sunglasses',\n\t\t\tvalue: 19991,\n\t\t},\n\t\tCap: {\n\t\t\tlabel: 'Cap',\n\t\t\tvalue: 53552,\n\t\t},\n\t},\n\t{\n\t\tdate: '2018-06-02T00:00:00',\n\t\tHoodie: {\n\t\t\tlabel: 'Hoodie',\n\t\t\tvalue: 9250,\n\t\t},\n\t\tSunglasses: {\n\t\t\tlabel: 'Sunglasses',\n\t\t\tvalue: 16072,\n\t\t},\n\t\tCap: {\n\t\t\tlabel: 'Cap',\n\t\t\tvalue: 47821,\n\t\t},\n\t},\n];\n\n<Chart data={ data } title=\"Example Chart\" layout=\"item-comparison\" />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`allowedIntervals` | Array | `null` | Allowed intervals to show in a dropdown\n`baseValue` | Number | `0` | Base chart value. If no data value is different than the baseValue, the `emptyMessage` will be displayed if provided\n`chartType` | One of: 'bar', 'line' | `'line'` | Chart type of either `line` or `bar`\n`data` | Array | `[]` | An array of data\n`dateParser` | String | `'%Y-%m-%dT%H:%M:%S'` | Format to parse dates into d3 time format\n`emptyMessage` | String | `null` | The message to be displayed if there is no data to render. If no message is provided, nothing will be displayed\n`filterParam` | String | `null` | Name of the param used to filter items. If specified, it will be used, in combination with query, to detect which elements are being used by the current filter and must be displayed even if their value is 0\n`itemsLabel` | String | `null` | Label describing the legend items\n`mode` | One of: 'item-comparison', 'time-comparison' | `'time-comparison'` | `item-comparison` (default) or `time-comparison`, this is used to generate correct ARIA properties\n`path` | String | `null` | Current path\n`query` | Object | `null` | The query string represented in object form\n`interactiveLegend` | Boolean | `true` | Whether the legend items can be activated/deactivated\n`interval` | One of: 'hour', 'day', 'week', 'month', 'quarter', 'year' | `'day'` | Interval specification (hourly, daily, weekly etc)\n`intervalData` | Object | `null` | Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`\n`isRequesting` | Boolean | `false` | Render a chart placeholder to signify an in-flight data request\n`legendPosition` | One of: 'bottom', 'side', 'top' | `null` | Position the legend must be displayed in. If it's not defined, it's calculated depending on the viewport width and the mode\n`legendTotals` | Object | `null` | Values to overwrite the legend totals. If not defined, the sum of all line values will be used\n`screenReaderFormat` | One of type: string, func | `'%B %-d, %Y'` | A datetime formatting string or overriding function to format the screen reader labels\n`showHeaderControls` | Boolean | `true` | Wether header UI controls must be displayed\n`title` | String | `null` | A title describing this chart\n`tooltipLabelFormat` | One of type: string, func | `'%B %-d, %Y'` | A datetime formatting string or overriding function to format the tooltip label\n`tooltipValueFormat` | One of type: string, func | `','` | A number formatting string or function to format the value displayed in the tooltips\n`tooltipTitle` | String | `null` | A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`\n`valueType` | String | `null` | What type of data is to be displayed? Number, Average, String?\n`xFormat` | String | `'%d'` | A datetime formatting string, passed to d3TimeFormat\n`x2Format` | String | `'%b %Y'` | A datetime formatting string, passed to d3TimeFormat\n`yBelow1Format` | String | `null` | A number formatting string, passed to d3Format\n`yFormat` | String | `null` | A number formatting string, passed to d3Format\n`currency` | Object | `{}` | An object with currency properties for usage in the chart. The following properties are expected: `decimal`, `symbol`, `symbolPosition`, `thousands`. This is passed to d3Format.\n");

/***/ }),

/***/ 849:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("# D3 Base Component\n\nIntegrate React Lifecyle methods with d3.js charts.\n\n### Base Component Responsibilities\n\n* Create and manage mounting and unmounting parent `div` and `svg`\n* Handle resize events, resulting re-renders, and event listeners\n* Handle re-renders as a result of new props\n\n## Props\n\n### className\n{ string } A class to be applied to the parent `div`\n\n### getParams( node )\n{ function } A function returning an object containing required properties for drawing a chart. This object is created before re-render, making it an ideal place for calculating scales and other props or user input based properties.\n* `svg` { node } The parent `div`. Useful for calculating available widths\n\n### drawChart( svg, params )\n{ function } draw the chart\n* `svg` { node } Base element \n* `params` { Object } Properties created by the `getParams` function ");

/***/ }),

/***/ 850:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("CompareFilter\n===\n\nDisplays a card + search used to filter results as a comparison between objects.\n\n## Usage\n\n```jsx\nconst path = ''; // from React Router\nconst getLabels = () => Promise.resolve( [] );\nconst labels = {\n\thelpText: 'Select at least two products to compare',\n\tplaceholder: 'Search for products to compare',\n\ttitle: 'Compare Products',\n\tupdate: 'Compare',\n};\n\n<CompareFilter\n\ttype=\"products\"\n\tparam=\"product\"\n\tpath={ path }\n\tgetLabels={ getLabels }\n\tlabels={ labels }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`getLabels` | Function | `null` | (required) Function used to fetch object labels via an API request, returns a Promise\n`labels` | Object | `{}` | Object of localized labels\n`param` | String | `null` | (required) The parameter to use in the querystring\n`path` | String | `null` | (required) The `path` parameter supplied by React-Router\n`query` | Object | `{}` | The query string represented in object form\n`type` | String | `null` | (required) Which type of autocompleter should be used in the Search\n");

/***/ }),

/***/ 851:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Count\n===\n\nDisplay a number with a styled border.\n\n## Usage\n\n```jsx\n<Count count={ 33 } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`count` | Number | `null` | (required) Value of the number to be displayed\n`label` | String | `''` | A translated label with the number in context, used for screen readers\n");

/***/ }),

/***/ 852:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Date Range Picker\n===\n\nSelect a range of dates or single dates\n\n## Usage\n\n```jsx\nimport {\n\tgetDateParamsFromQuery,\n\tgetCurrentDates,\n\tisoDateFormat,\n\tloadLocaleData,\n} from '@woocommerce/date';\n\n/**\n * External dependencies\n */\nimport { partialRight } from 'lodash';\n\nconst query = {};\n\n// Fetch locale from store settings and load for date functions.\nconst localeSettings = {\n\tuserLocale: 'fr_FR',\n\tweekdaysShort: [ 'dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam' ],\n};\nloadLocaleData( localeSettings );\n\nconst defaultDateRange = 'period=month&compare=previous_year';\nconst storeGetDateParamsFromQuery = partialRight( getDateParamsFromQuery, defaultDateRange );\nconst storeGetCurrentDates = partialRight( getCurrentDates, defaultDateRange );\nconst { period, compare, before, after } = storeGetDateParamsFromQuery( query );\nconst { primary: primaryDate, secondary: secondaryDate } = storeGetCurrentDates( query );\nconst dateQuery = {\n\tperiod,\n\tcompare,\n\tbefore,\n\tafter,\n\tprimaryDate,\n\tsecondaryDate,\n};\n\n<DateRangeFilterPicker\n\tkey=\"daterange\"\n\tonRangeSelect={ () => {} }\n\tdateQuery={ dateQuery }\n\tisoDateFormat={ isoDateFormat }\n/>\n```\n\n### Props\n\nName    | Type     | Default | Description\n------- | -------- | ------- | ---\n`isDateFormat` | string | `null` | (required) ISO date format string\n`onRangeSelect` | Function | `null` | Callback called when selection is made\n`dateQuery` | object | `null` | (required) Date initialization object\n\n## URL as the source of truth\n\nThe Date Range Picker reads parameters from the URL querystring and updates them by creating a link to reflect newly selected parameters, which is rendered as the \"Update\" button.\n\nURL Parameter | Default | Possible Values\n--- | --- | ---\n`period` | `today` | `today`, `yesterday`, `week`, `last_week`, `month`, `last_month`, `quarter`, `last_quarter`, `year`, `last_year`, `custom`\n`compare` | `previous_period` | `previous_period`, `previous_year`\n`start` | none | start date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)\n`end` | none | end date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)\n");

/***/ }),

/***/ 853:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Date\n===\n\nUse the `Date` component to display accessible dates or times.\n\n## Usage\n\n```jsx\n<Date date=\"2019-01-01\" />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`date` | One of type: string, object | `null` | (required) Date to use in the component\n`machineFormat` | String | `'Y-m-d H:i:s'` | Date format used in the `datetime` prop of the `time` element\n`screenReaderFormat` | String | `'F j, Y'` | Date format used for screen readers\n`visibleFormat` | String | `'Y-m-d'` | Date format displayed in the page\n");

/***/ }),

/***/ 854:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("DropdownButton\n===\n\nA button useful for a launcher of a dropdown component. The button is 100% width of its container and displays single or multiple lines rendered as `<span/>` elments.\n\n## Usage\n\n```jsx\n<Dropdown\n\trenderToggle={ ( { isOpen, onToggle } ) => (\n\t\t<DropdownButton\n\t\t\tonClick={ onToggle }\n\t\t\tisOpen={ isOpen }\n\t\t\tlabels={ [ 'All Products Sold' ] }\n\t\t/>\n\t) }\n\trenderContent={ () => (\n\t\t<p>Dropdown content here</p>\n\t) }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`labels` | Array | `null` | (required) An array of elements to be rendered as the content of the button\n`isOpen` | Boolean | `null` | Boolean describing if the dropdown in open or not\n");

/***/ }),

/***/ 855:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("EllipsisMenu\n===\n\nThis is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.\n\n## Usage\n\n```jsx\n<EllipsisMenu label=\"Choose which analytics to display\"\n\trenderContent={ ( { onToggle } )=> {\n\t\treturn (\n\t\t\t<div>\n\t\t\t\t<MenuTitle>Display Stats</MenuTitle>\n\t\t\t\t<MenuItem onInvoke={ () => setState( { showCustomers: ! showCustomers } ) }>\n\t\t\t\t\t<ToggleControl\n\t\t\t\t\t\tlabel=\"Show Customers\"\n\t\t\t\t\t\tchecked={ showCustomers }\n\t\t\t\t\t\tonChange={ () => setState( { showCustomers: ! showCustomers } ) }\n\t\t\t\t\t/>\n\t\t\t\t</MenuItem>\n\t\t\t\t<MenuItem onInvoke={ () => setState( { showOrders: ! showOrders } ) }>\n\t\t\t\t\t<ToggleControl\n\t\t\t\t\t\tlabel=\"Show Orders\"\n\t\t\t\t\t\tchecked={ showOrders }\n\t\t\t\t\t\tonChange={ () => setState( { showOrders: ! showOrders } ) }\n\t\t\t\t\t/>\n\t\t\t\t</MenuItem>\n\t\t\t\t<MenuItem onInvoke={ onToggle }>\n\t\t\t\t\t<Button\n\t\t\t\t\t\tlabel=\"Close menu\"\n\t\t\t\t\t\tonClick={ onToggle }\n\t\t\t\t\t>\n\t\t\t\t\tClose Menu\n\t\t\t\t\t</Button>\n\t\t\t\t</MenuItem>\n\t\t\t</div>\n\t\t);\n\t} }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`label` | String | `null` | (required) The label shown when hovering/focusing on the icon button\n`renderContent` | Function | `null` | A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments\n\n\nMenuItem\n===\n\n`MenuItem` is used to give the item an accessible wrapper, with the `menuitem` role and added keyboard functionality (`onInvoke`).\n`MenuItem`s can also be deemed \"clickable\", though this is disabled by default because generally the inner component handles\nthe click event.\n\n## Usage\n\n```jsx\n<MenuItem onInvoke={ onToggle }>\n\t<Button\n\t\tlabel=\"Close menu\"\n\t\tonClick={ onToggle }\n\t>\n\tClose Menu\n\t</Button>\n</MenuItem>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`checked` | Boolean | `null` | Whether the menu item is checked or not. Only relevant for menu items with `isCheckbox`\n`children` | ReactNode | `null` | A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`\n`isCheckbox` | Boolean | `false` | Whether the menu item is a checkbox (will render a FormToggle and use the `menuitemcheckbox` role)\n`isClickable` | Boolean | `false` | Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component handles the click event\n`onInvoke` | Function | `null` | (required) A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked (only if `isClickable` is set)\n\n\nMenuTitle\n===\n\n`MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated\n(so this should not be used in place of the `EllipsisMenu` prop `label`).\n\n## Usage\n\n```jsx\n<MenuTitle>Display Stats</MenuTitle>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`children` | ReactNode | `null` | A renderable component (or string) which will be displayed as the content of this item\n");

/***/ }),

/***/ 856:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("EmptyContent\n===\n\nA component to be used when there is no data to show.\nIt can be used as an opportunity to provide explanation or guidance to help a user progress.\n\n## Usage\n\n```jsx\n<EmptyContent\n\ttitle=\"Nothing here\"\n\tmessage=\"Some descriptive text\"\n\tactionLabel=\"Reload page\"\n\tactionURL=\"#\"\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`title` | String | `null` | (required) The title to be displayed\n`message` | String | `null` | An additional message to be displayed\n`illustration` | String | `'/empty-content.svg'` | The url string of an image path. Prefix with `/` to load an image relative to the plugin directory\n`illustrationHeight` | Number | `null` | Height to use for the illustration\n`illustrationWidth` | Number | `400` | Width to use for the illustration\n`actionLabel` | String | `null` | (required) Label to be used for the primary action button\n`actionURL` | String | `null` | URL to be used for the primary action button\n`actionCallback` | Function | `null` | Callback to be used for the primary action button\n`secondaryActionLabel` | String | `null` | Label to be used for the secondary action button\n`secondaryActionURL` | String | `null` | URL to be used for the secondary action button\n`secondaryActionCallback` | Function | `null` | Callback to be used for the secondary action button\n`className` | String | `null` | Additional CSS classes\n");

/***/ }),

/***/ 857:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Filter Picker\n===\n\nModify a url query parameter via a dropdown selection of configurable options. This component manipulates the `filter` query parameter.\n\n## Usage\n\n```jsx\nimport { FilterPicker } from '@woocommerce/components';\n\nconst renderFilterPicker = () => {\n\tconst filters = [\n\t\t{ label: 'Breakfast', value: 'breakfast' },\n\t\t{\n\t\t\tlabel: 'Lunch',\n\t\t\tvalue: 'lunch',\n\t\t\tsubFilters: [\n\t\t\t\t{ label: 'Meat', value: 'meat', path: [ 'lunch' ] },\n\t\t\t\t{ label: 'Vegan', value: 'vegan', path: [ 'lunch' ] },\n\t\t\t\t{\n\t\t\t\t\tlabel: 'Pescatarian',\n\t\t\t\t\tvalue: 'fish',\n\t\t\t\t\tpath: [ 'lunch' ],\n\t\t\t\t\tsubFilters: [\n\t\t\t\t\t\t{ label: 'Snapper', value: 'snapper', path: [ 'lunch', 'fish' ] },\n\t\t\t\t\t\t{ label: 'Cod', value: 'cod', path: [ 'lunch', 'fish' ] },\n\t\t\t\t\t\t// Specify a custom component to render (Work in Progress)\n\t\t\t\t\t\t{\n\t\t\t\t\t\t\tlabel: 'Other',\n\t\t\t\t\t\t\tvalue: 'other_fish',\n\t\t\t\t\t\t\tpath: [ 'lunch', 'fish' ],\n\t\t\t\t\t\t\tcomponent: 'OtherFish',\n\t\t\t\t\t\t},\n\t\t\t\t\t],\n\t\t\t\t},\n\t\t\t],\n\t\t},\n\t\t{ label: 'Dinner', value: 'dinner' },\n\t];\n\n\treturn <FilterPicker filters={ filters } path={ path } query={ query } />;\n};\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`config` | Object | `null` | (required) An array of filters and subFilters to construct the menu\n`path` | String | `null` | (required) The `path` parameter supplied by React-Router\n`query` | Object | `{}` | The query string represented in object form\n`onFilterSelect` | Function | `() => {}` | Function to be called after filter selection\n\n### `config` structure\n\nThe `config` prop has the following structure:\n\n- `label`: String - A label above the filter selector.\n- `staticParams`: Array - Url parameters to persist when selecting a new filter.\n- `param`: String - The url paramter this filter will modify.\n- `defaultValue`: String - The default paramter value to use instead of 'all'.\n- `showFilters`: Function - Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.\n- `filters`: Array - Array of filter objects.\n\n### `filters` structure\n\nThe `filters` prop is an array of filter objects. Each filter object should have the following format:\n\n- `chartMode`: One of: 'item-comparison', 'time-comparison'\n- `component`: String - A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.\n- `label`: String - The label for this filter. Optional only for custom component filters.\n- `path`: String - An array representing the \"path\" to this filter, if nested.\n- `subFilters`: Array - An array of more filter objects that act as \"children\" to this item. This set of filters is shown if the parent filter is clicked.\n- `value`: String - The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.\n");

/***/ }),

/***/ 858:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("ReportFilters\n===\n\nAdd a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the \"basic\" filters, and `AdvancedFilters`\nor a comparison card if \"advanced\" or \"compare\" are picked from `FilterPicker`.\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`advancedFilters` | Object | `{}` | Config option passed through to `AdvancedFilters`\n`siteLocale` | string| `en_US` | The locale of the site. Passed through to `AdvancedFilters`\n`currency` | object | {} | The currency of the site. Passed through to `AdvancedFilters`\n`filters` | Array | `[]` | Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed\n`path` | String | `null` | (required) The `path` parameter supplied by React-Router\n`query` | Object | `{}` | The query string represented in object form\n`showDatePicker` | Boolean | `true` | Whether the date picker must be shown\n`onDateSelect` | Function | `() => {}` | Function to be called after date selection\n`onFilterSelect` | Function | `null` | Function to be called after filter selection\n`onAdvancedFilterAction` | Function | `null` | Function to be called after an advanced filter action has been taken\n`storeDate` | object | `null` | (required) Date utility function object bound to store settings.\n");

/***/ }),

/***/ 859:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Flag\n===\n\nUse the `Flag` component to display a country's flag using the operating system's emojis.\n\n React component.\n\n## Usage\n\n```jsx\n<Flag code=\"VU\" size={ 48 } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`code` | String | `null` | Two letter, three letter or three digit country code\n`order` | Object | `null` | An order can be passed instead of `code` and the code will automatically be pulled from the billing or shipping data\n`className` | String | `null` | Additional CSS classes\n`size` | Number | `null` | Supply a font size to be applied to the emoji flag\n");

/***/ }),

/***/ 860:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Form\n===\n\nA form component to handle form state and provide input helper props.\n\n## Usage\n\n```jsx\nconst initialValues = { firstName: '' };\n\n<Form\n\tonSubmitCallback={ ( values ) => {} }\n\tinitialValues={ initialValues }\n>\n\t{ ( {\n\t\tgetInputProps,\n\t\tvalues,\n\t\terrors,\n\t\thandleSubmit,\n\t} ) => (\n\t\t<div>\n\t\t\t<TextControl\n\t\t\t\tlabel={ 'First Name' }\n\t\t\t\t{ ...getInputProps( 'firstName' ) }\n\t\t\t/>\n\t\t\t<Button\n\t\t\t\tisPrimary\n\t\t\t\tonClick={ handleSubmit }\n\t\t\t\tdisabled={ Object.keys( errors ).length }\n\t\t\t>\n\t\t\t\tSubmit\n\t\t\t</Button>\n\t\t</div>\n\t) }\n</Form>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`children` | * | `null` | A renderable component in which to pass this component's state and helpers. Generally a number of input or other form elements\n`errors` | Object | `{}` | Object of all initial errors to store in state\n`initialValues` | Object | `{}` | Object key:value pair list of all initial field values\n`onSubmitCallback` | Function | `noop` | Function to call when a form is submitted with valid fields\n`validate` | Function | `noop` | A function that is passed a list of all values and should return an `errors` object with error response\n`touched` |  | `{}` | \n");

/***/ }),

/***/ 861:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Gravatar\n===\n\nDisplay a users Gravatar.\n\n## Usage\n\n```jsx\n<Gravatar\n\tuser=\"email@example.org\"\n\tsize={ 48 }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`user` | One of type: object, string | `null` | The address to hash for displaying a Gravatar. Can be an email address or WP-API user object\n`alt` | String | `null` | Text to display as the image alt attribute\n`title` | String | `null` | Text to use for the image's title\n`size` | Number | `60` | Default 60. The size of Gravatar to request\n`className` | String | `null` | Additional CSS classes\n");

/***/ }),

/***/ 862:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("useFilters\n==========\n\n`useFilters` is a fork of [gutenberg's `withFilters`.](https://github.com/WordPress/gutenberg/tree/master/packages/components/src/higher-order/with-filters) It is also a React [higher-order component.](https://facebook.github.io/react/docs/higher-order-components.html)\n\nWrapping a component with `useFilters` provides a filtering capability controlled externally by the list of `hookName`s.\n\n## Usage\n\n```jsx\nimport { applyFilters } from '@wordpress/hooks';\nimport { useFilters } from '@woocommerce/components';\n\nfunction MyCustomElement() {\n\treturn <h3>{ applyFilters( 'woocommerce.componentTitle', 'Title Text' ) }</h3>;\n}\n\nexport default useFilters( [ 'woocommerce.componentTitle' ] )( MyCustomElement );\n```\n\n`useFilters` expects an array argument which provides a list of hook names. It returns a function which can then be used in composing your component. The list of hook names are used in your component with `applyFilters`. Any filters added to the given hooks are run when added, and update your content (the title text, in this example).\n\n### Adding filters\n\n```js\nfunction editText( string ) {\n\treturn `Filtered: ${ string }`;\n}\naddFilter( 'woocommerce.componentTitle', 'editText', editText );\n```\n\nIf we added this filter, our `MyCustomElement` component would display:\n\n```html\n<h3>Filtered: Title Text</h3>\n```\n");

/***/ }),

/***/ 863:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("ImageUpload\n===\n\nImageUpload - Adds an upload area for selecting or uploading an image from the WordPress media gallery.\n\n## Usage\n\n```jsx\n\t<ImageUpload image={ image } onChange={ newImage => setState( { url: newImage } ) } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`image` | Object | `null` | Image information containing media gallery `id` and image `url`\n`onChange` | Function | `null` | Function to trigger when the selected image changes\n`className` | String | `null` | Additional class name to style the component\n");

/***/ }),

/***/ 864:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Link\n===\n\nUse `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.\n\n## Usage\n\n```jsx\n<Link\n\thref=\"edit.php?post_type=shop_coupon\"\n\ttype=\"wp-admin\"\n>\n\tCoupons\n</Link>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`href` | String | `null` | (required) The resource to link to\n`type` | One of: 'wp-admin', 'wc-admin', 'external' | `'wc-admin'` | Type of link. For wp-admin and wc-admin, the correct prefix is appended\n");

/***/ }),

/***/ 865:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("List\n===\n\nList component to display a list of items.\n\n## Usage\n\n```jsx\nconst listItems = [\n\t{\n\t\ttitle: 'List item title',\n\t\tdescription: 'List item description text',\n\t},\n\t{\n\t\tbefore: <Gridicon icon=\"star\" />,\n\t\ttitle: 'List item with before icon',\n\t\tdescription: 'List item description text',\n\t},\n\t{\n\t\tbefore: <Gridicon icon=\"star\" />,\n\t\tafter: <Gridicon icon=\"chevron-right\" />,\n\t\ttitle: 'List item with before and after icons',\n\t\tdescription: 'List item description text',\n\t},\n\t{\n\t\ttitle: 'Clickable list item',\n\t\tdescription: 'List item description text',\n\t\tonClick: () => alert( 'List item clicked' ),\n\t},\n];\n\n<List items={ listItems } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional class name to style the component\n`items` | Array | `null` | (required) An array of list items\n\n`items` structure:\n\n* `after`: ReactNode - Content displayed after the list item text.\n* `before`: ReactNode - Content displayed before the list item text.\n* `className`: String - Additional class name to style the list item.\n* `description`: String - Description displayed beneath the list item title.\n* `href`: String - Href attribute used in a Link wrapped around the item.\n* `onClick`: Function - Called when the list item is clicked.\n* `target`: String - Target attribute used for Link wrapper.\n* `title`: String - Title displayed for the list item.");

/***/ }),

/***/ 866:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("OrderStatus\n===\n\nUse `OrderStatus` to display a badge with human-friendly text describing the current order status.\n\n## Usage\n\n```jsx\nconst order = { status: 'processing' }; // Use a real WooCommerce Order here.\n\n<OrderStatus order={ order } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`order` | Object | `null` | (required) The order to display a status for. See: https://woocommerce.github.io/woocommerce-rest-api-docs/#order-properties\n`className` | String | `null` | Additional CSS classes\n`orderStatusMap` | Object | {} | A map of order status to human-friendly label.\n");

/***/ }),

/***/ 867:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Pagination\n===\n\nUse `Pagination` to allow navigation between pages that represent a collection of items.\nThe component allows for selecting a new page and items per page options.\n\n## Usage\n\n```jsx\n<Pagination\n\tpage={ 1 }\n\tperPage={ 10 }\n\ttotal={ 500 }\n\tonPageChange={ ( newPage ) => setState( { page: newPage } ) }\n\tonPerPageChange={ ( newPerPage ) => setState( { perPage: newPerPage } ) }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`page` | Number | `null` | (required) The current page of the collection\n`onPageChange` | Function | `noop` | A function to execute when the page is changed\n`perPage` | Number | `null` | (required) The amount of results that are being displayed per page\n`onPerPageChange` | Function | `noop` | A function to execute when the per page option is changed\n`total` | Number | `null` | (required) The total number of results\n`className` | String | `null` | Additional classNames\n`showPagePicker` | Boolean | `true` | Whether the page picker should be shown.\n`showPerPagePicker` | Boolean | `true` | Whether the per page picker should shown.\n`showPageArrowsLabel` | Boolean | `true` | Whether the page arrows label should be shown.\n");

/***/ }),

/***/ 868:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Plugins\n===\n\nUse `Plugins` to install and activate a list of plugins.\n\n## Usage\n\n```jsx\n<Plugins onComplete={ this.complete } pluginSlugs={ [ 'jetpack', 'woocommerce-services' ] } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`onComplete` | Function |  | Called when the plugin installer is completed\n`onSkip` | Function | `noop` | Called when the plugin installer is skipped\n`skipText` | String |  | Text used for the skip installer button\n`autoInstall` | Boolean | false | If installation should happen automatically, or require user confirmation\n`pluginSlugs` | Array | `[ 'jetpack', 'woocommerce-services' ],` | An array of plugin slugs to install.\n");

/***/ }),

/***/ 869:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("ProductImage\n===\n\nUse `ProductImage` to display a product's or variation's featured image.\nIf no image can be found, a placeholder matching the front-end image\nplaceholder will be displayed.\n\n## Usage\n\n```jsx\n// Use a real WooCommerce Product here.\nconst product = {\n\timages: [\n\t\t{\n\t\t\tsrc: 'https://cldup.com/6L9h56D9Bw.jpg',\n\t\t},\n\t],\n};\n\n<ProductImage product={ product } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`width` | Number | `60` | The width of image to display\n`height` | Number | `60` | The height of image to display\n`className` | String | `''` | Additional CSS classes\n`product` | Object | `null` | Product or variation object. The image to display will be pulled from `product.images` or `variation.image`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties and https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties\n`alt` | String | `null` | Text to use as the image alt attribute\n");

/***/ }),

/***/ 870:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Rating\n===\n\nUse `Rating` to display a set of stars, filled, empty or half-filled, that represents a\nrating in a scale between 0 and the prop `totalStars` (default 5).\n\n## Usage\n\n```jsx\n<Rating rating={ 2.5 } totalStars={ 6 } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`rating` | Number | `0` | Number of stars that should be filled. You can pass a partial number of stars like `2.5`\n`totalStars` | Number | `5` | The total number of stars the rating is out of\n`size` | Number | `18` | The size in pixels the stars should be rendered at\n`className` | String | `null` | Additional CSS classes\n\n\nProductRating\n===\n\nDisplay a set of stars representing the product's average rating.\n\n## Usage\n\n```jsx\n// Use a real WooCommerce Product here.\nconst product = { average_rating: 3.5 };\n\n<ProductRating product={ product } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`product` | Object | `null` | (required) A product object containing a `average_rating`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#products\n\n\nReviewRating\n===\n\nDisplay a set of stars representing the review's rating.\n\n## Usage\n\n```jsx\n// Use a real WooCommerce Review here.\nconst review = { rating: 5 };\n\n<ReviewRating review={ review } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`review` | Object | `null` | (required) A review object containing a `rating`. See https://woocommerce.github.io/woocommerce-rest-api-docs/#retrieve-product-reviews\n");

/***/ }),

/***/ 871:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("ScrollTo\n===\n\n\n\n## Usage\n\n```jsx\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`offset` | String | ``'0'`` | The offset from the top of the component\n");

/***/ }),

/***/ 872:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("SearchListControl\n===\n\nComponent to display a searchable, selectable list of items.\n\n## Usage\n\n```jsx\n<SearchListControl\n\tlist={ list }\n\tisLoading={ loading }\n\tselected={ selected }\n\tonChange={ items => setState( { selected: items } ) }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional CSS classes\n`isHierarchical` | Boolean | `null` | Whether the list of items is hierarchical or not. If true, each list item is expected to have a parent property\n`isLoading` | Boolean | `null` | Whether the list of items is still loading\n`isSingle` | Boolean | `null` | Restrict selections to one item\n`list` | Array | `null` | A complete list of item objects, each with id, name properties. This is displayed as a clickable/keyboard-able list, and possibly filtered by the search term (searches name)\n`messages` | Object | `null` | Messages displayed or read to the user. Configure these to reflect your object type. See `defaultMessages` above for examples\n`onChange` | Function | `null` | (required) Callback fired when selected items change, whether added, cleared, or removed. Passed an array of item objects (as passed in via props.list)\n`onSearch` | Function | `null` | Callback fired when the search field is used\n`renderItem` | Function | `null` | Callback to render each item in the selection list, allows any custom object-type rendering\n`selected` | Array | `null` | (required) The list of currently selected items\n`search` | String | `null` | \n`setState` | Function | `null` | \n`debouncedSpeak` | Function | `null` | \n`instanceId` | Number | `null` | \n\n### `list` item structure:\n\n  - `id`: Number\n  - `name`: String\n\n### `messages` object structure:\n\n  - `clear`: String - A more detailed label for the \"Clear all\" button, read to screen reader users.\n  - `list`: String - Label for the list of selectable items, only read to screen reader users.\n  - `noItems`: String - Message to display when the list is empty (implies nothing loaded from the server\nor parent component).\n  - `noResults`: String - Message to display when no matching results are found. %s is the search term.\n  - `search`: String - Label for the search input\n  - `selected`: Function - Label for the selected items. This is actually a function, so that we can pass\nthrough the count of currently selected items.\n  - `updated`: String - Label indicating that search results have changed, read to screen reader users.\n\n\nSearchListItem\n===\n\n## Usage\n\nUsed implicitly by `SearchListControl` when the `renderItem` prop is omitted.\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional CSS classes\n`countLabel` | ReactNode | `null` | Label to display if `showCount` is set to true. If undefined, it will use `item.count`\n`depth` | Number | `0` | Depth, non-zero if the list is hierarchical\n`item` | Object | `null` | Current item to display\n`isSelected` | Boolean | `null` | Whether this item is selected\n`isSingle` | Boolean | `null` | Whether this should only display a single item (controls radio vs checkbox icon)\n`onSelect` | Function | `null` | Callback for selecting the item\n`search` | String | `''` | Search string, used to highlight the substring in the item name\n`showCount` | Boolean | `false` | Toggles the \"count\" bubble on/off\n");

/***/ }),

/***/ 873:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Search\n===\n\nA search box which autocompletes results while typing, allowing for the user to select an existing object\n(product, order, customer, etc). Currently only products are supported.\n\n## Usage\n\n```jsx\n<Search\n\ttype=\"products\"\n\tplaceholder=\"Search for a product\"\n\tselected={ selected }\n\tonChange={ items => setState( { selected: items } ) }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`allowFreeTextSearch` | Boolean | `false` | Render additional options in the autocompleter to allow free text entering depending on the type\n`className` | String | `null` | Class name applied to parent div\n`onChange` | Function | `noop` | Function called when selected results change, passed result list\n`type` | One of: 'categories', 'countries', 'coupons', 'customers', 'downloadIps', 'emails', 'orders', 'products', 'taxes', 'usernames', 'variations', 'custom' | `null` | (required) The object type to be used in searching\n`autocompleter` | Completer | `null` | Custom [completer](https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface) to be used in searching. Required when `type` is 'custom'\n`placeholder` | String | `null` | A placeholder for the search input\n`selected` | Array | `[]` | An array of objects describing selected values. If the label of the selected value is omitted, the Tag of that value will not be rendered inside the search box.\n`inlineTags` | Boolean | `false` | Render tags inside input, otherwise render below input\n`showClearButton` | Boolean | `false` | Render a 'Clear' button next to the input box to remove its contents\n`staticResults` | Boolean | `false` | Render results list positioned statically instead of absolutely\n`disabled` | Boolean | `false` | Whether the control is disabled or not\n\n### `selected` item structure:\n\n- `id`: One of type: number, string\n- `label`: String");

/***/ }),

/***/ 874:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("SectionHeader\n===\n\nA header component. The header can contain a title, actions via children, and an `EllipsisMenu` menu.\n\n## Usage\n\n```jsx\n<SectionHeader title=\"Section Title\" />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional CSS classes\n`menu` | (custom validator) | `null` | An `EllipsisMenu`, with filters used to control the content visible in this card\n`title` | One of type: string, node | `null` | (required) The title to use for this card\n");

/***/ }),

/***/ 875:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("H\n===\n\nThese components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels\n(`h2`, `h3`, …) you can use `<H />` to create \"section headings\", which look to the parent `<Section />`s for the appropriate\nheading level.\n\n## Usage\n\n```jsx\n<div>\n\t<H>Header using a contextual level (h3)</H>\n\t<Section component=\"article\">\n\t\t<p>This is an article component wrapper.</p>\n\t\t<H>Another header with contextual level (h4)</H>\n\t\t<Section component={ false }>\n\t\t\t<p>There is no wrapper component here.</p>\n\t\t\t<H>This is an h5</H>\n\t\t</Section>\n\t</Section>\n</div>\n```\n\nSection\n===\n\nThe section wrapper, used to indicate a sub-section (and change the header level context).\n\n## Usage\n\nSee above\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`component` | One of type: func, string, bool | `null` | The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component\n`children` | ReactNode | `null` | The children inside this section, rendered in the `component`. This increases the context level for the next heading used\n");

/***/ }),

/***/ 876:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("SegmentedSelection\n===\n\nCreate a panel of styled selectable options rendering stylized checkboxes and labels\n\n## Usage\n\n```jsx\n<SegmentedSelection\n\toptions={ [\n\t\t{ value: 'one', label: 'One' },\n\t\t{ value: 'two', label: 'Two' },\n\t\t{ value: 'three', label: 'Three' },\n\t\t{ value: 'four', label: 'Four' },\n\t] }\n\tselected={ selected }\n\tlegend=\"Select a number\"\n\tonSelect={ ( data ) => setState( { selected: data[ name ] } ) }\n\tname={ name }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional CSS classes\n`options` | Array | `null` | (required) An Array of options to render. The array needs to be composed of objects with properties `label` and `value`\n`selected` | String | `null` | Value of selected item\n`onSelect` | Function | `null` | (required) Callback to be executed after selection\n`name` | String | `null` | (required) This will be the key in the key and value arguments supplied to `onSelect`\n`legend` | String | `null` | (required) Create a legend visible to screen readers\n\n### `options` structure\n\nThe `options` array needs to be composed of objects with properties:\n\n- `value`: String - Input value for this option.\n- `label`: String - Label for this option.");

/***/ }),

/***/ 877:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("# SelectControl\n\nA search box which filters options while typing,\nallowing a user to select from an option from a filtered list.\n\n## Usage\n\n```jsx\nconst options = [\n\t{\n\t\tkey: 'apple',\n\t\tlabel: 'Apple',\n\t\tvalue: { id: 'apple' },\n\t},\n\t{\n\t\tkey: 'apricot',\n\t\tlabel: 'Apricot',\n\t\tvalue: { id: 'apricot' },\n\t},\n];\n\n<SelectControl\n\tlabel=\"Single value\"\n\tonChange={ selected => setState( { singleSelected: selected } ) }\n\toptions={ options }\n\tplaceholder=\"Start typing to filter options...\"\n\tselected={ singleSelected }\n/>;\n```\n\n### Props\n\n| Name                     | Type         | Default    | Description                                                                                                                                                     |\n| ------------------------ | ------------ | ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |\n| `className`              | string       | `null`     | Class name applied to parent div                                                                                                                                |\n| `excludeSelectedOptions` | boolean      | `true`     | Exclude already selected options from the options list                                                                                                          |\n| `onFilter`               | function     | `identity` | Add or remove items to the list of options after filtering, passed the array of filtered options and should return an array of options.                         |\n| `getSearchExpression`    | function     | `identity` | Function to add regex expression to the filter the results, passed the search query                                                                             |\n| `help`                   | string\\|node | `null`     | Help text to be appended beneath the input                                                                                                                      |\n| `inlineTags`             | boolean      | `false`    | Render tags inside input, otherwise render below input                                                                                                          |\n| `label`                  | string       | `null`     | A label to use for the main input                                                                                                                               |\n| `onChange`               | function     | `noop`     | Function called when selected results change, passed result list                                                                                                |\n| `onSearch`               | function     | `noop`     | Function to run after the search query is updated, passed the search query                                                                                      |\n| `options`                | array        | `null`     | (required) An array of objects for the options list. The option along with its key, label and value will be returned in the onChange event                      |\n| `placeholder`            | string       | `null`     | A placeholder for the search input                                                                                                                              |\n| `selected`               | array        | `[]`       | An array of objects describing selected values. If the label of the selected value is omitted, the Tag of that value will not be rendered inside the search box |\n| `maxResults`             | number       | `0`        | A limit for the number of results shown in the options menu. Set to 0 for no limit                                                                              |\n| `multiple`               | boolean      | `false`    | Allow multiple option selections                                                                                                                                |\n| `showClearButton`        | boolean      | `false`    | Render a 'Clear' button next to the input box to remove its contents                                                                                            |\n| `hideBeforeSearch`       | boolean      | `false`    | Only show list options after typing a search query                                                                                                              |\n| `staticList`             | boolean      | `false`    | Render results list positioned statically instead of absolutely                                                                                                 |\n");

/***/ }),

/***/ 878:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Spinner\n===\n\nSpinner - An indeterminate circular progress indicator.\n\n## Usage\n\n```jsx\n<Spinner />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional class name to style the component\n");

/***/ }),

/***/ 879:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Stepper\n===\n\nA stepper component to indicate progress in a set number of steps.\n\n## Usage\n\n```jsx\nconst steps = [\n\t{\n\t\tkey: 'first',\n\t\tlabel: 'First',\n\t\tdescription: 'Step item description',\n\t\tcontent: <div>First step content.</div>,\n\t},\n\t{\n\t\tkey: 'second',\n\t\tlabel: 'Second',\n\t\tdescription: 'Step item description',\n\t\tcontent: <div>Second step content.</div>,\n\t},\n];\n\n<Stepper\n\tsteps={ steps }\n\tcurrentStep=\"first\"\n\tisPending={ true }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional class name to style the component\n`currentStep` | String | `null` | (required) The current step's key\n`steps` | Array | `null` | (required) An array of steps used\n`isVertical` | Boolean | `false` | If the stepper is vertical instead of horizontal\n`isPending` | Boolean | `false` | Optionally mark the current step as pending to show a spinner\n\n### `steps` structure\n\nArray of step objects with properties:\n\n- `key:` String - Key used to identify step.\n- `label`: String - Label displayed in stepper.\n- `description`: String - Description displayed beneath the label.\n- `isComplete`: Boolean - Optionally mark a step complete regardless of step index.\n- `content`: ReactNode - Content displayed when the step is active.");

/***/ }),

/***/ 880:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("SummaryList\n===\n\nA container element for a list of SummaryNumbers. This component handles detecting & switching to the mobile format on smaller screens.\n\n## Usage\n\n```jsx\n<SummaryList>\n\t{ () => {\n\t\treturn [\n\t\t\t<SummaryNumber\n\t\t\t\tkey=\"revenue\"\n\t\t\t\tvalue={ '$829.40' }\n\t\t\t\tlabel=\"Total Sales\"\n\t\t\t\tdelta={ 29 }\n\t\t\t\thref=\"/analytics/report\"\n\t\t\t/>,\n\t\t\t<SummaryNumber\n\t\t\t\tkey=\"refunds\"\n\t\t\t\tvalue={ '$24.00' }\n\t\t\t\tlabel=\"Refunds\"\n\t\t\t\tdelta={ -10 }\n\t\t\t\thref=\"/analytics/report\"\n\t\t\t\tselected\n\t\t\t/>,\n\t\t];\n\t} }\n</SummaryList>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`children` | Function | `null` | (required) A function returning a list of `<SummaryNumber />`s\n`label` | String | `__( 'Performance Indicators', 'woocommerce' )` | An optional label of this group, read to screen reader users\n\n\nSummaryNumber\n===\n\nA component to show a value, label, and an optional change percentage. Can also act as a link to a specific report focus.\n\n## Usage\n\nSee above\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`delta` | Number | `null` | A number to represent the percentage change since the last comparison period - positive numbers will show a green up arrow, negative numbers will show a red down arrow, and zero will show a flat right arrow. If omitted, no change value will display\n`href` | String | `''` | An internal link to the report focused on this number\n`isOpen` | Boolean | `false` | Boolean describing whether the menu list is open. Only applies in mobile view, and only applies to the toggle-able item (first in the list)\n`label` | String | `null` | (required) A string description of this value, ex \"Revenue\", or \"New Customers\"\n`onToggle` | Function | `null` | A function used to switch the given SummaryNumber to a button, and called on click\n`prevLabel` | String | `__( 'Previous Period:', 'woocommerce' )` | A string description of the previous value's timeframe, ex \"Previous Year:\"\n`prevValue` | One of type: number, string | `null` | A string or number value to display - a string is allowed so we can accept currency formatting. If omitted, this section won't display\n`reverseTrend` | Boolean | `false` | A boolean used to indicate that a negative delta is \"good\", and should be styled like a positive (and vice-versa)\n`selected` | Boolean | `false` | A boolean used to show a highlight style on this number\n`value` | One of type: number, string | `null` | A string or number value to display - a string is allowed so we can accept currency formatting\n`onLinkClickCallback` | Function | `noop` | A function to be called after a SummaryNumber, rendered as a link, is clicked\n\n\nSummaryListPlaceholder\n===\n\n`SummaryListPlaceholder` behaves like `SummaryList` but displays placeholder summary items instead of data. This can be used while loading data.\n\n## Usage\n\n```jsx\n<SummaryListPlaceholder numberOfItems={ 2 } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`numberOfItems` | Number | `null` | (required) An integer with the number of summary items to display\n`numberOfRows` |  | `5` | \n");

/***/ }),

/***/ 881:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("TableCard\n===\n\nThis is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).\nIt accepts `headers` for column headers, and `rows` for the table content.\n`rowHeader` can be used to define the index of the row header (or false if no header).\n\n`TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.\nThis includes filtering and comparison functionality for report pages.\n\n## Usage\n\n```jsx\nconst headers = [\n\t{ key: 'month', label: 'Month' },\n\t{ key: 'orders', label: 'Orders' },\n\t{ key: 'revenue', label: 'Revenue' },\n];\nconst rows = [\n\t[\n\t\t{ display: 'January', value: 1 },\n\t\t{ display: 10, value: 10 },\n\t\t{ display: '$530.00', value: 530 },\n\t],\n\t[\n\t\t{ display: 'February', value: 2 },\n\t\t{ display: 13, value: 13 },\n\t\t{ display: '$675.00', value: 675 },\n\t],\n\t[\n\t\t{ display: 'March', value: 3 },\n\t\t{ display: 9, value: 9 },\n\t\t{ display: '$460.00', value: 460 },\n\t],\n];\nconst summary = [\n\t{ label: 'Gross Income', value: '$830.00' },\n\t{ label: 'Taxes', value: '$96.32' },\n\t{ label: 'Shipping', value: '$50.00' },\n];\n\n<TableCard\n\ttitle=\"Revenue Last Week\"\n\trows={ rows }\n\theaders={ headers }\n\tquery={ { page: 2 } }\n\trowsPerPage={ 7 }\n\ttotalRows={ 10 }\n\tsummary={ summary }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`compareBy` | String | `null` | The string to use as a query parameter when comparing row items\n`compareParam` | String | `'filter'` | Url query parameter compare function operates on\n`headers` | Array | `null` | An array of column headers (see `Table` props)\n`labels` | Object | `null` | Custom labels for table header actions\n`ids` | Array | `null` | A list of IDs, matching to the row list so that ids[ 0 ] contains the object ID for the object displayed in row[ 0 ]\n`isLoading` | Boolean | `false` | Defines if the table contents are loading. It will display `TablePlaceholder` component instead of `Table` if that's the case\n`onQueryChange` | Function | `noop` | A function which returns a callback function to update the query string for a given `param`\n`onColumnsChange` | Function | `noop` | A function which returns a callback function which is called upon the user changing the visiblity of columns\n`onSearch` | Function | `noop` | A function which is called upon the user searching in the table header\n`onSort` | Function | `undefined` | A function which is called upon the user changing the sorting of the table\n`downloadable` | Boolean | `false` | Whether the table must be downloadable. If true, the download button will appear\n`onClickDownload` | Function | `null` | A callback function called when the \"download\" button is pressed. Optional, if used, the download button will appear\n`query` | Object | `{}` | An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`\n`rowHeader` | One of type: number, bool | `0` | An array of arrays of display/value object pairs (see `Table` props)\n`rows` | Array | `[]` | Which column should be the row header, defaults to the first item (`0`) (see `Table` props)\n`rowsPerPage` | Number | `null` | (required) The total number of rows to display per page\n`searchBy` | String | `null` | The string to use as a query parameter when searching row items\n`showMenu` | Boolean | `true` | Boolean to determine whether or not ellipsis menu is shown\n`summary` | Array | `null` | An array of objects with `label` & `value` properties, which display in a line under the table. Optional, can be left off to show no summary\n`title` | String | `null` | (required) The title used in the card header, also used as the caption for the content in this table\n`totalRows` | Number | `null` | (required) The total number of rows (across all pages)\n`baseSearchQuery` | Object | `{}` | Pass in query parameters to be included in the path when onSearch creates a new url\n\n### `labels` structure\n\nTable header action labels object with properties:\n\n- `compareButton`: String - Compare button label\n- `downloadButton`: String - Download button label\n- `helpText`: String - \n- `placeholder`: String - \n\n### `summary` structure\n\nArray of summary items objects with properties:\n\n- `label`: ReactNode\n- `value`: One of type: string, number \n\n\nEmptyTable\n===\n\n`EmptyTable` displays a blank space with an optional message passed as a children node\nwith the purpose of replacing a table with no rows.\nIt mimics the same height a table would have according to the `numberOfRows` prop.\n\n## Usage\n\n```jsx\n<EmptyTable>\n\tThere are no entries.\n</EmptyTable>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`numberOfRows` | Number | `5` | An integer with the number of rows the box should occupy\n\n\nTablePlaceholder\n===\n\n`TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.\n\n## Usage\n\n```jsx\nconst headers = [\n\t{ key: 'month', label: 'Month' },\n\t{ key: 'orders', label: 'Orders' },\n\t{ key: 'revenue', label: 'Revenue' },\n];\n\n<TablePlaceholder\n\tcaption=\"Revenue Last Week\"\n\theaders={ headers }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`query` | Object | `null` | An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`\n`caption` | String | `null` | (required) A label for the content in this table\n`headers` | Array | `null` | An array of column headers (see `Table` props)\n`numberOfRows` | Number | `5` | An integer with the number of rows to display\n\n\nTableSummary\n===\n\nA component to display summarized table data - the list of data passed in on a single line.\n\n## Usage\n\n```jsx\nconst summary = [\n\t{ label: 'Gross Income', value: '$830.00' },\n\t{ label: 'Taxes', value: '$96.32' },\n\t{ label: 'Shipping', value: '$50.00' },\n];\n\n<TableSummary data={ summary } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`data` | Array | `null` | An array of objects with `label` & `value` properties, which display on a single line\n\n\nTable\n===\n\nA table component, without the Card wrapper. This is a basic table display, sortable, but no default filtering.\n\nRow data should be passed to the component as a list of arrays, where each array is a row in the table.\nHeaders are passed in separately as an array of objects with column-related properties. For example,\nthis data would render the following table.\n\n```js\nconst headers = [ { label: 'Month' }, { label: 'Orders' }, { label: 'Revenue' } ];\nconst rows = [\n\t[\n\t\t{ display: 'January', value: 1 },\n\t\t{ display: 10, value: 10 },\n\t\t{ display: '$530.00', value: 530 },\n\t],\n\t[\n\t\t{ display: 'February', value: 2 },\n\t\t{ display: 13, value: 13 },\n\t\t{ display: '$675.00', value: 675 },\n\t],\n\t[\n\t\t{ display: 'March', value: 3 },\n\t\t{ display: 9, value: 9 },\n\t\t{ display: '$460.00', value: 460 },\n\t],\n]\n```\n\n|   Month  | Orders | Revenue |\n| ---------|--------|---------|\n| January  |     10 | $530.00 |\n| February |     13 | $675.00 |\n| March    |      9 | $460.00 |\n\n## Usage\n\n```jsx\n<Table\n\tcaption=\"Revenue Last Week\"\n\trows={ rows }\n\theaders={ headers }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`ariaHidden` | Boolean | `false` | Controls whether this component is hidden from screen readers. Used by the loading state, before there is data to read. Don't use this on real tables unless the table data is loaded elsewhere on the page\n`caption` | String | `null` | (required) A label for the content in this table\n`className` | String | `null` | Additional CSS classes\n`headers` | Array | `[]` | An array of column headers, as objects\n`onSort` | Function | `noop` | A function called when sortable table headers are clicked, gets the `header.key` as argument\n`query` | Object | `{}` | The query string represented in object form\n`rows` | Array | `null` | (required) An array of arrays of display/value object pairs\n`rowHeader` | One of type: number, bool | `0` | Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col is checkboxes, for example). Set to false to disable row headers\n\n### `headers` structure\n\nArray of column header objects with properties:\n\n- `defaultSort`: Boolean - Boolean, true if this column is the default for sorting. Only one column should have this set.\n- `defaultOrder`: String - String, asc|desc if this column is the default for sorting. Only one column should have this set.\n- `isLeftAligned`: Boolean - Boolean, true if this column should be aligned to the left.\n- `isNumeric`: Boolean - Boolean, true if this column is a number value.\n- `isSortable`: Boolean - Boolean, true if this column is sortable.\n- `key`: String - The API parameter name for this column, passed to `orderby` when sorting via API.\n- `label`: ReactNode - The display label for this column.\n- `required`: Boolean - Boolean, true if this column should always display in the table (not shown in toggle-able list).\n- `screenReaderLabel`: String - The label used for screen readers for this column. \n\n### `rows` structure\n\nArray of arrays representing rows and columns. Column object properties:\n\n- `display`: ReactNode - Display value, used for rendering - strings or elements are best here.\n- `value`: One of type: string, number, bool");

/***/ }),

/***/ 882:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("Tag\n===\n\nThis component can be used to show an item styled as a \"tag\", optionally with an `X` + \"remove\"\nor with a popover that is shown on click.\n\n\n\n## Usage\n\n```jsx\n<Tag label=\"My tag\" id={ 1 } />\n<Tag label=\"Removable tag\" id={ 2 } remove={ noop } />\n<Tag label=\"Tag with popover\" popoverContents={ ( <p>This is a popover</p> ) } />\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`id` | One of type: number, string | `null` | The ID for this item, used in the remove function\n`label` | String | `null` | (required) The name for this item, displayed as the tag's text\n`popoverContents` | ReactNode | `null` | Contents to display on click in a popover\n`remove` | Function | `null` | A function called when the remove X is clicked. If not used, no X icon will display\n`screenReaderLabel` | String | `null` | A more descriptive label for screen reader users. Defaults to the `name` prop\n");

/***/ }),

/***/ 883:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("TextControlWithAffixes\n===\n\nThis component is essentially a wrapper (really a reimplementation) around the\nTextControl component that adds support for affixes, i.e. the ability to display\na fixed part either at the beginning or at the end of the text input.\n\n## Usage\n\n```jsx\n<TextControlWithAffixes\n    suffix=\"%\"\n    label=\"Text field with a suffix\"\n    value={ fourth }\n    onChange={ value => setState( { fourth: value } ) }\n/>\n<TextControlWithAffixes\n    prefix=\"$\"\n    label=\"Text field with prefix and help text\"\n    value={ fifth }\n    onChange={ value => setState( { fifth: value } ) }\n    help=\"This is some help text.\"\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`label` | String | `null` | If this property is added, a label will be generated using label property as the content\n`help` | String | `null` | If this property is added, a help text will be generated using help property as the content\n`type` | String | `'text'` | Type of the input element to render. Defaults to \"text\"\n`value` | String | `null` | (required) The current value of the input\n`className` | String | `null` | The class that will be added with \"components-base-control\" to the classes of the wrapper div. If no className is passed only components-base-control is used\n`onChange` | Function | `null` | (required) A function that receives the value of the input\n`prefix` | ReactNode | `null` | Markup to be inserted at the beginning of the input\n`suffix` | ReactNode | `null` | Markup to be appended at the end of the input\n`disabled` | Boolean | `null` | Disables the field\n");

/***/ }),

/***/ 884:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("TextControl\n===\n\nAn input field use for text inputs in forms.\n\n## Usage\n\n```jsx\n<TextControl\n\tlabel=\"Input label\"\n\tvalue={ value }\n\tonChange={ value => setState( { value } ) }\n/>;\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | ``null`` | Additional CSS classes\n`disabled` | Boolean | ``null`` | Disables the field\n`label` | String | ``null`` | Input label used as a placeholder\n`onClick` | Function | ``null`` | On click handler called when the component is clicked, passed the click event\n`value` | String | ``null`` | The value of the input field\n");

/***/ }),

/***/ 885:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("ViewMoreList\n===\n\nThis component displays a 'X more' button that displays a list of items on a popover when clicked.\n\n\n\n## Usage\n\n```jsx\n<ViewMoreList\n    items={ [ <i>Lorem</i>, <i>Ipsum</i>, <i>Dolor</i>, <i>Sit</i> ] }\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`items` | Array | `[]` | `ReactNodes` to list in the popover\n");

/***/ }),

/***/ 886:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ("WebPreview\n===\n\nWebPreview component to display an iframe of another page.\n\n## Usage\n\n```jsx\n<WebPreview\n    title=\"My Web Preview\"\n    src=\"https://themes.woocommerce.com/?name=galleria\"\n/>\n```\n\n### Props\n\nName | Type | Default | Description\n--- | --- | --- | ---\n`className` | String | `null` | Additional class name to style the component\n`loadingContent` | ReactNode | `<Spinner />` | Content shown when iframe is still loading\n`onLoad` | Function | `noop` | Function to fire when iframe content is loaded\n`src` | String | `null` | (required) Iframe src to load\n`title` | String | `null` | (required) Iframe title\n");

/***/ }),

/***/ 887:
/***/ (function(module) {

module.exports = JSON.parse("[{\"component\":\"AdvancedFilters\"},{\"component\":\"AnimationSlider\"},{\"component\":\"Calendar\"},{\"component\":\"Card\"},{\"component\":\"Chart\"},{\"component\":\"CompareFilter\"},{\"component\":\"Count\"},{\"component\":\"Date\"},{\"component\":\"DateRangeFilterPicker\"},{\"component\":\"DropdownButton\"},{\"component\":\"EllipsisMenu\"},{\"component\":\"EmptyContent\"},{\"component\":\"FilterPicker\"},{\"component\":\"Flag\"},{\"component\":\"Form\"},{\"component\":\"Gravatar\"},{\"component\":\"ImageAsset\"},{\"component\":\"ImageUpload\"},{\"component\":\"Link\"},{\"component\":\"List\"},{\"component\":\"OrderStatus\"},{\"component\":\"Pagination\"},{\"component\":\"ProductImage\"},{\"component\":\"Rating\"},{\"component\":\"ScrollTo\"},{\"component\":\"Search\"},{\"component\":\"SearchListControl\"},{\"component\":\"Section\"},{\"component\":\"SegmentedSelection\"},{\"component\":\"SelectControl\"},{\"component\":\"Spinner\"},{\"component\":\"SplitButton\"},{\"component\":\"Stepper\"},{\"component\":\"Summary\"},{\"component\":\"Table\"},{\"component\":\"Tag\"},{\"component\":\"TextControl\"},{\"component\":\"TextControlWithAffixes\"},{\"component\":\"ViewMoreList\"},{\"component\":\"WebPreview\"}]");

/***/ }),

/***/ 888:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 934:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "default", function() { return /* binding */ devdocs_default; });

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

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/asyncToGenerator.js
var asyncToGenerator = __webpack_require__(51);
var asyncToGenerator_default = /*#__PURE__*/__webpack_require__.n(asyncToGenerator);

// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(13);
var external_React_default = /*#__PURE__*/__webpack_require__.n(external_React_);

// CONCATENATED MODULE: ./client/devdocs/example.js








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



var example_Example = /*#__PURE__*/function (_Component) {
  inherits_default()(Example, _Component);

  var _super = _createSuper(Example);

  function Example() {
    var _temp, _this;

    classCallCheck_default()(this, Example);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return possibleConstructorReturn_default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.state = {
      example: null
    }, _temp));
  }

  createClass_default()(Example, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.getExample();
    }
  }, {
    key: "getExample",
    value: function () {
      var _getExample = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var exampleComponent;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                try {
                  exampleComponent = __webpack_require__(797)("./".concat(this.props.filePath, "/docs/example"));
                } catch (e) {
                  // eslint-disable-next-line no-console
                  console.error(e);
                }

                if (exampleComponent) {
                  _context.next = 3;
                  break;
                }

                return _context.abrupt("return");

              case 3:
                this.setState({
                  example: external_React_default.a.createElement(exampleComponent.default || exampleComponent)
                });

              case 4:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function getExample() {
        return _getExample.apply(this, arguments);
      }

      return getExample;
    }()
  }, {
    key: "render",
    value: function render() {
      var example = this.state.example;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-devdocs__example"
      }, example);
    }
  }]);

  return Example;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var devdocs_example = (example_Example);
// EXTERNAL MODULE: ./node_modules/marked/src/marked.js
var marked = __webpack_require__(838);
var marked_default = /*#__PURE__*/__webpack_require__.n(marked);

// EXTERNAL MODULE: ./node_modules/prismjs/prism.js
var prism = __webpack_require__(841);
var prism_default = /*#__PURE__*/__webpack_require__.n(prism);

// EXTERNAL MODULE: ./node_modules/prismjs/components/prism-jsx.js
var prism_jsx = __webpack_require__(842);

// CONCATENATED MODULE: ./client/devdocs/docs.js







function docs_createSuper(Derived) { var hasNativeReflectConstruct = docs_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function docs_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



 // Alias `javascript` language to `es6`

prism_default.a.languages.es6 = prism_default.a.languages.javascript; // Configure marked to use Prism for code-block highlighting.

marked_default.a.setOptions({
  highlight: function highlight(code, language) {
    var syntax = prism_default.a.languages[language];
    return syntax ? prism_default.a.highlight(code, syntax) : code;
  }
});

var docs_Docs = /*#__PURE__*/function (_Component) {
  inherits_default()(Docs, _Component);

  var _super = docs_createSuper(Docs);

  function Docs() {
    var _temp, _this;

    classCallCheck_default()(this, Docs);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return possibleConstructorReturn_default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.state = {
      readme: null
    }, _temp));
  }

  createClass_default()(Docs, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.getReadme();
    }
  }, {
    key: "getReadme",
    value: function getReadme() {
      var filePath = this.props.filePath;

      var readme = __webpack_require__(843)("./".concat(filePath, "/README.md")).default;

      if (!readme) {
        return;
      }

      this.setState({
        readme: readme
      });
    }
  }, {
    key: "render",
    value: function render() {
      var readme = this.state.readme;

      if (!readme) {
        return null;
      }

      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-devdocs__docs" //eslint-disable-next-line react/no-danger
        ,
        dangerouslySetInnerHTML: {
          __html: marked_default()(readme)
        }
      });
    }
  }]);

  return Docs;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var docs = (docs_Docs);
// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: ./client/devdocs/examples.json
var examples = __webpack_require__(887);

// EXTERNAL MODULE: ./client/devdocs/style.scss
var style = __webpack_require__(888);

// CONCATENATED MODULE: ./client/devdocs/index.js







function devdocs_createSuper(Derived) { var hasNativeReflectConstruct = devdocs_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function devdocs_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */







var camelCaseToSlug = function camelCaseToSlug(name) {
  return name.replace(/\.?([A-Z])/g, function (x, y) {
    return '-' + y.toLowerCase();
  }).replace(/^-/, '');
};

var devdocs_getExampleData = function getExampleData(example) {
  var componentName = Object(external_lodash_["get"])(example, 'component');
  var filePath = Object(external_lodash_["get"])(example, 'filePath', camelCaseToSlug(componentName));
  return {
    componentName: componentName,
    filePath: filePath
  };
};

var devdocs_default = /*#__PURE__*/function (_Component) {
  inherits_default()(_default, _Component);

  var _super = devdocs_createSuper(_default);

  function _default() {
    classCallCheck_default()(this, _default);

    return _super.apply(this, arguments);
  }

  createClass_default()(_default, [{
    key: "render",
    value: function render() {
      var component = this.props.query.component;
      var className = classnames_default()('woocommerce_devdocs', {
        'is-single': component,
        'is-list': !component
      });
      var exampleList = examples;

      if (component) {
        var example = Object(external_lodash_["find"])(examples, function (ex) {
          return component === camelCaseToSlug(ex.component);
        });
        exampleList = [example];
      }

      return Object(external_this_wp_element_["createElement"])("div", {
        className: className
      }, exampleList.map(function (example) {
        var _getExampleData = devdocs_getExampleData(example),
            componentName = _getExampleData.componentName,
            filePath = _getExampleData.filePath;

        var cardClasses = classnames_default()('woocommerce-devdocs__card', "woocommerce-devdocs__card--".concat(filePath), 'woocommerce-analytics__card');
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], {
          key: componentName
        }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
          key: "".concat(componentName, "-example"),
          className: cardClasses,
          title: component ? componentName : Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: "admin.php?page=wc-admin&path=/devdocs&component=".concat(filePath),
            type: "wc-admin"
          }, componentName),
          action: component ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: '?page=wc-admin&path=/devdocs',
            type: "wc-admin"
          }, "Full list") : null
        }, Object(external_this_wp_element_["createElement"])(devdocs_example, {
          asyncName: componentName,
          component: componentName,
          filePath: filePath
        })), component && Object(external_this_wp_element_["createElement"])(docs, {
          key: "".concat(componentName, "-readme"),
          componentName: componentName,
          filePath: filePath
        }));
      }));
    }
  }]);

  return _default;
}(external_this_wp_element_["Component"]);



/***/ })

}]);
