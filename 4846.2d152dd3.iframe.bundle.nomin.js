"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4846],{

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/date.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ date)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPickerSingleDateController.js
var DayPickerSingleDateController = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPickerSingleDateController.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/utils.js
/**
 * External dependencies
 */

/**
 * Create a Moment object from a date string. With no date supplied, default to a Moment
 * object representing now. If a null value is passed, return a null value.
 *
 * @param {?string} date Date representing the currently selected date or null to signify no selection.
 * @return {?moment.Moment} Moment object for selected date or null.
 */

const getMomentDate = date => {
  if (null === date) {
    return null;
  }

  return date ? moment_default()(date) : moment_default()();
};
//# sourceMappingURL=utils.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/date-time/date.js


/**
 * External dependencies
 */

 // react-dates doesn't tree-shake correctly, so we import from the individual
// component here, to avoid including too much of the library


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Module Constants
 */

const TIMEZONELESS_FORMAT = 'YYYY-MM-DDTHH:mm:ss';
const ARIAL_LABEL_TIME_FORMAT = 'dddd, LL';

function DatePickerDay(_ref) {
  let {
    day,
    events = []
  } = _ref;
  const ref = (0,react.useRef)();
  /*
   * a11y hack to make the `There is/are n events` string
   * available speaking for readers,
   * re-defining the aria-label attribute.
   * This attribute is handled by the react-dates component.
   */

  (0,react.useEffect)(() => {
    var _ref$current;

    // Bail when no parent node.
    if (!(ref !== null && ref !== void 0 && (_ref$current = ref.current) !== null && _ref$current !== void 0 && _ref$current.parentNode)) {
      return;
    }

    const {
      parentNode
    } = ref.current;
    const dayAriaLabel = moment_default()(day).format(ARIAL_LABEL_TIME_FORMAT);

    if (!events.length) {
      // Set aria-label without event description.
      parentNode.setAttribute('aria-label', dayAriaLabel);
      return;
    }

    const dayWithEventsDescription = (0,build_module/* sprintf */.nv)( // translators: 1: Calendar day format, 2: Calendar event number.
    (0,build_module._n)('%1$s. There is %2$d event.', '%1$s. There are %2$d events.', events.length), dayAriaLabel, events.length);
    parentNode.setAttribute('aria-label', dayWithEventsDescription);
  }, [events.length]);
  return (0,react.createElement)("div", {
    ref: ref,
    className: classnames_default()('components-datetime__date__day', {
      'has-events': events === null || events === void 0 ? void 0 : events.length
    })
  }, day.format('D'));
}

function DatePicker(_ref2) {
  let {
    currentDate,
    onChange,
    events,
    isInvalidDate,
    onMonthPreviewed
  } = _ref2;
  const nodeRef = (0,react.useRef)();

  const onMonthPreviewedHandler = newMonthDate => {
    onMonthPreviewed === null || onMonthPreviewed === void 0 ? void 0 : onMonthPreviewed(newMonthDate.toISOString());
    keepFocusInside();
  };
  /*
   * Todo: We should remove this function ASAP.
   * It is kept because focus is lost when we click on the previous and next month buttons.
   * This focus loss closes the date picker popover.
   * Ideally we should add an upstream commit on react-dates to fix this issue.
   */


  const keepFocusInside = () => {
    if (!nodeRef.current) {
      return;
    }

    const {
      ownerDocument
    } = nodeRef.current;
    const {
      activeElement
    } = ownerDocument; // If focus was lost.

    if (!activeElement || !nodeRef.current.contains(ownerDocument.activeElement)) {
      // Retrieve the focus region div.
      const focusRegion = nodeRef.current.querySelector('.DayPicker_focusRegion');

      if (!focusRegion) {
        return;
      } // Keep the focus on focus region.


      focusRegion.focus();
    }
  };

  const onChangeMoment = newDate => {
    // If currentDate is null, use now as momentTime to designate hours, minutes, seconds.
    const momentDate = currentDate ? moment_default()(currentDate) : moment_default()();
    const momentTime = {
      hours: momentDate.hours(),
      minutes: momentDate.minutes(),
      seconds: 0
    };
    onChange(newDate.set(momentTime).format(TIMEZONELESS_FORMAT)); // Keep focus on the date picker.

    keepFocusInside();
  };

  const getEventsPerDay = day => {
    if (!(events !== null && events !== void 0 && events.length)) {
      return [];
    }

    return events.filter(eventDay => day.isSame(eventDay.date, 'day'));
  };

  const momentDate = getMomentDate(currentDate);
  return (0,react.createElement)("div", {
    className: "components-datetime__date",
    ref: nodeRef
  }, (0,react.createElement)(DayPickerSingleDateController/* default */.A, {
    date: momentDate,
    daySize: 30,
    focused: true,
    hideKeyboardShortcutsPanel: true // This is a hack to force the calendar to update on month or year change
    // https://github.com/airbnb/react-dates/issues/240#issuecomment-361776665
    ,
    key: `datepicker-controller-${momentDate ? momentDate.format('MM-YYYY') : 'null'}`,
    noBorder: true,
    numberOfMonths: 1,
    onDateChange: onChangeMoment,
    transitionDuration: 0,
    weekDayFormat: "ddd",
    dayAriaLabelFormat: ARIAL_LABEL_TIME_FORMAT,
    isRTL: (0,build_module/* isRTL */.V8)(),
    isOutsideRange: date => {
      return isInvalidDate && isInvalidDate(date.toDate());
    },
    onPrevMonthClick: onMonthPreviewedHandler,
    onNextMonthClick: onMonthPreviewedHandler,
    renderDayContents: day => (0,react.createElement)(DatePickerDay, {
      day: day,
      events: getEventsPerDay(day)
    })
  }));
}

/* harmony default export */ const date = (DatePicker);
//# sourceMappingURL=date.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/dropdown/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ Dropdown)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/popover/index.js");


// @ts-nocheck

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function useObservableState(initialState, onStateChange) {
  const [state, setState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(initialState);
  return [state, value => {
    setState(value);

    if (onStateChange) {
      onStateChange(value);
    }
  }];
}

function Dropdown(props) {
  var _popoverProps$anchorR;

  const {
    renderContent,
    renderToggle,
    position = 'bottom right',
    className,
    contentClassName,
    expandOnMobile,
    headerTitle,
    focusOnMount,
    popoverProps,
    onClose,
    onToggle
  } = props;
  const containerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)();
  const [isOpen, setIsOpen] = useObservableState(false, onToggle);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => () => {
    if (onToggle) {
      onToggle(false);
    }
  }, []);

  function toggle() {
    setIsOpen(!isOpen);
  }
  /**
   * Closes the popover when focus leaves it unless the toggle was pressed or
   * focus has moved to a separate dialog. The former is to let the toggle
   * handle closing the popover and the latter is to preserve presence in
   * case a dialog has opened, allowing focus to return when it's dismissed.
   */


  function closeIfFocusOutside() {
    const {
      ownerDocument
    } = containerRef.current;
    const dialog = ownerDocument.activeElement.closest('[role="dialog"]');

    if (!containerRef.current.contains(ownerDocument.activeElement) && (!dialog || dialog.contains(containerRef.current))) {
      close();
    }
  }

  function close() {
    if (onClose) {
      onClose();
    }

    setIsOpen(false);
  }

  const args = {
    isOpen,
    onToggle: toggle,
    onClose: close
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('components-dropdown', className),
    ref: containerRef // Some UAs focus the closest focusable parent when the toggle is
    // clicked. Making this div focusable ensures such UAs will focus
    // it and `closeIfFocusOutside` can tell if the toggle was clicked.
    ,
    tabIndex: "-1"
  }, renderToggle(args), isOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_popover__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)({
    position: position,
    onClose: close,
    onFocusOutside: closeIfFocusOutside,
    expandOnMobile: expandOnMobile,
    headerTitle: headerTitle,
    focusOnMount: focusOnMount
  }, popoverProps, {
    anchorRef: (_popoverProps$anchorR = popoverProps === null || popoverProps === void 0 ? void 0 : popoverProps.anchorRef) !== null && _popoverProps$anchorR !== void 0 ? _popoverProps$anchorR : containerRef.current,
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()('components-dropdown__content', popoverProps ? popoverProps.className : undefined, contentClassName)
  }), renderContent(args)));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/react-addons-shallow-compare@15.6.3/node_modules/react-addons-shallow-compare/index.js":
/***/ ((module) => {

/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @providesModule shallowCompare
 */



var hasOwnProperty = Object.prototype.hasOwnProperty;

/**
 * inlined Object.is polyfill to avoid requiring consumers ship their own
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/is
 */
function is(x, y) {
  // SameValue algorithm
  if (x === y) {
    // Steps 1-5, 7-10
    // Steps 6.b-6.e: +0 != -0
    // Added the nonzero y check to make Flow happy, but it is redundant
    return x !== 0 || y !== 0 || 1 / x === 1 / y;
  } else {
    // Step 6.a: NaN == NaN
    return x !== x && y !== y;
  }
}

/**
 * Performs equality by iterating through keys on an object and returning false
 * when any key has values which are not strictly equal between the arguments.
 * Returns true when the values of all keys are strictly equal.
 */
function shallowEqual(objA, objB) {
  if (is(objA, objB)) {
    return true;
  }

  if (typeof objA !== 'object' || objA === null || typeof objB !== 'object' || objB === null) {
    return false;
  }

  var keysA = Object.keys(objA);
  var keysB = Object.keys(objB);

  if (keysA.length !== keysB.length) {
    return false;
  }

  // Test for A's keys different from B.
  for (var i = 0; i < keysA.length; i++) {
    if (!hasOwnProperty.call(objB, keysA[i]) || !is(objA[keysA[i]], objB[keysA[i]])) {
      return false;
    }
  }

  return true;
}

/**
 * Does a shallow comparison for props and state.
 * See ReactComponentWithPureRenderMixin
 * See also https://facebook.github.io/react/docs/shallow-compare.html
 */
function shallowCompare(instance, nextProps, nextState) {
  return (
    !shallowEqual(instance.props, nextProps) ||
    !shallowEqual(instance.state, nextState)
  );
}

module.exports = shallowCompare;


/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarDay.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.PureCalendarDay = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactAddonsShallowCompare = __webpack_require__("../../node_modules/.pnpm/react-addons-shallow-compare@15.6.3/node_modules/react-addons-shallow-compare/index.js");

var _reactAddonsShallowCompare2 = _interopRequireDefault(_reactAddonsShallowCompare);

var _reactMomentProptypes = __webpack_require__("../../node_modules/.pnpm/react-moment-proptypes@1.8.1_moment@2.29.4/node_modules/react-moment-proptypes/src/index.js");

var _reactMomentProptypes2 = _interopRequireDefault(_reactMomentProptypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _getCalendarDaySettings = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarDaySettings.js");

var _getCalendarDaySettings2 = _interopRequireDefault(_getCalendarDaySettings);

var _ModifiersShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ModifiersShape.js");

var _ModifiersShape2 = _interopRequireDefault(_ModifiersShape);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {
  day: _reactMomentProptypes2['default'].momentObj,
  daySize: _airbnbPropTypes.nonNegativeInteger,
  isOutsideDay: _propTypes2['default'].bool,
  modifiers: _ModifiersShape2['default'],
  isFocused: _propTypes2['default'].bool,
  tabIndex: _propTypes2['default'].oneOf([0, -1]),
  onDayClick: _propTypes2['default'].func,
  onDayMouseEnter: _propTypes2['default'].func,
  onDayMouseLeave: _propTypes2['default'].func,
  renderDayContents: _propTypes2['default'].func,
  ariaLabelFormat: _propTypes2['default'].string,

  // internationalization
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.CalendarDayPhrases))
}));

var defaultProps = {
  day: (0, _moment2['default'])(),
  daySize: _constants.DAY_SIZE,
  isOutsideDay: false,
  modifiers: new Set(),
  isFocused: false,
  tabIndex: -1,
  onDayClick: function () {
    function onDayClick() {}

    return onDayClick;
  }(),
  onDayMouseEnter: function () {
    function onDayMouseEnter() {}

    return onDayMouseEnter;
  }(),
  onDayMouseLeave: function () {
    function onDayMouseLeave() {}

    return onDayMouseLeave;
  }(),

  renderDayContents: null,
  ariaLabelFormat: 'dddd, LL',

  // internationalization
  phrases: _defaultPhrases.CalendarDayPhrases
};

var CalendarDay = function (_React$Component) {
  _inherits(CalendarDay, _React$Component);

  function CalendarDay() {
    var _ref;

    _classCallCheck(this, CalendarDay);

    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    var _this = _possibleConstructorReturn(this, (_ref = CalendarDay.__proto__ || Object.getPrototypeOf(CalendarDay)).call.apply(_ref, [this].concat(args)));

    _this.setButtonRef = _this.setButtonRef.bind(_this);
    return _this;
  }

  _createClass(CalendarDay, [{
    key: 'shouldComponentUpdate',
    value: function () {
      function shouldComponentUpdate(nextProps, nextState) {
        return (0, _reactAddonsShallowCompare2['default'])(this, nextProps, nextState);
      }

      return shouldComponentUpdate;
    }()
  }, {
    key: 'componentDidUpdate',
    value: function () {
      function componentDidUpdate(prevProps) {
        var _props = this.props,
            isFocused = _props.isFocused,
            tabIndex = _props.tabIndex;

        if (tabIndex === 0) {
          if (isFocused || tabIndex !== prevProps.tabIndex) {
            this.buttonRef.focus();
          }
        }
      }

      return componentDidUpdate;
    }()
  }, {
    key: 'onDayClick',
    value: function () {
      function onDayClick(day, e) {
        var onDayClick = this.props.onDayClick;

        onDayClick(day, e);
      }

      return onDayClick;
    }()
  }, {
    key: 'onDayMouseEnter',
    value: function () {
      function onDayMouseEnter(day, e) {
        var onDayMouseEnter = this.props.onDayMouseEnter;

        onDayMouseEnter(day, e);
      }

      return onDayMouseEnter;
    }()
  }, {
    key: 'onDayMouseLeave',
    value: function () {
      function onDayMouseLeave(day, e) {
        var onDayMouseLeave = this.props.onDayMouseLeave;

        onDayMouseLeave(day, e);
      }

      return onDayMouseLeave;
    }()
  }, {
    key: 'onKeyDown',
    value: function () {
      function onKeyDown(day, e) {
        var onDayClick = this.props.onDayClick;
        var key = e.key;

        if (key === 'Enter' || key === ' ') {
          onDayClick(day, e);
        }
      }

      return onKeyDown;
    }()
  }, {
    key: 'setButtonRef',
    value: function () {
      function setButtonRef(ref) {
        this.buttonRef = ref;
      }

      return setButtonRef;
    }()
  }, {
    key: 'render',
    value: function () {
      function render() {
        var _this2 = this;

        var _props2 = this.props,
            day = _props2.day,
            ariaLabelFormat = _props2.ariaLabelFormat,
            daySize = _props2.daySize,
            isOutsideDay = _props2.isOutsideDay,
            modifiers = _props2.modifiers,
            renderDayContents = _props2.renderDayContents,
            tabIndex = _props2.tabIndex,
            styles = _props2.styles,
            phrases = _props2.phrases;


        if (!day) return _react2['default'].createElement('td', null);

        var _getCalendarDaySettin = (0, _getCalendarDaySettings2['default'])(day, ariaLabelFormat, daySize, modifiers, phrases),
            daySizeStyles = _getCalendarDaySettin.daySizeStyles,
            useDefaultCursor = _getCalendarDaySettin.useDefaultCursor,
            selected = _getCalendarDaySettin.selected,
            hoveredSpan = _getCalendarDaySettin.hoveredSpan,
            isOutsideRange = _getCalendarDaySettin.isOutsideRange,
            ariaLabel = _getCalendarDaySettin.ariaLabel;

        return _react2['default'].createElement(
          'td',
          _extends({}, (0, _reactWithStyles.css)(styles.CalendarDay, useDefaultCursor && styles.CalendarDay__defaultCursor, styles.CalendarDay__default, isOutsideDay && styles.CalendarDay__outside, modifiers.has('today') && styles.CalendarDay__today, modifiers.has('first-day-of-week') && styles.CalendarDay__firstDayOfWeek, modifiers.has('last-day-of-week') && styles.CalendarDay__lastDayOfWeek, modifiers.has('hovered-offset') && styles.CalendarDay__hovered_offset, modifiers.has('highlighted-calendar') && styles.CalendarDay__highlighted_calendar, modifiers.has('blocked-minimum-nights') && styles.CalendarDay__blocked_minimum_nights, modifiers.has('blocked-calendar') && styles.CalendarDay__blocked_calendar, hoveredSpan && styles.CalendarDay__hovered_span, modifiers.has('selected-span') && styles.CalendarDay__selected_span, modifiers.has('last-in-range') && styles.CalendarDay__last_in_range, modifiers.has('selected-start') && styles.CalendarDay__selected_start, modifiers.has('selected-end') && styles.CalendarDay__selected_end, selected && styles.CalendarDay__selected, isOutsideRange && styles.CalendarDay__blocked_out_of_range, daySizeStyles), {
            role: 'button' // eslint-disable-line jsx-a11y/no-noninteractive-element-to-interactive-role
            , ref: this.setButtonRef,
            'aria-label': ariaLabel,
            onMouseEnter: function () {
              function onMouseEnter(e) {
                _this2.onDayMouseEnter(day, e);
              }

              return onMouseEnter;
            }(),
            onMouseLeave: function () {
              function onMouseLeave(e) {
                _this2.onDayMouseLeave(day, e);
              }

              return onMouseLeave;
            }(),
            onMouseUp: function () {
              function onMouseUp(e) {
                e.currentTarget.blur();
              }

              return onMouseUp;
            }(),
            onClick: function () {
              function onClick(e) {
                _this2.onDayClick(day, e);
              }

              return onClick;
            }(),
            onKeyDown: function () {
              function onKeyDown(e) {
                _this2.onKeyDown(day, e);
              }

              return onKeyDown;
            }(),
            tabIndex: tabIndex
          }),
          renderDayContents ? renderDayContents(day, modifiers) : day.format('D')
        );
      }

      return render;
    }()
  }]);

  return CalendarDay;
}(_react2['default'].Component);

CalendarDay.propTypes = propTypes;
CalendarDay.defaultProps = defaultProps;

exports.PureCalendarDay = CalendarDay;
exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref2) {
  var _ref2$reactDates = _ref2.reactDates,
      color = _ref2$reactDates.color,
      font = _ref2$reactDates.font;
  return {
    CalendarDay: {
      boxSizing: 'border-box',
      cursor: 'pointer',
      fontSize: font.size,
      textAlign: 'center',

      ':active': {
        outline: 0
      }
    },

    CalendarDay__defaultCursor: {
      cursor: 'default'
    },

    CalendarDay__default: {
      border: '1px solid ' + String(color.core.borderLight),
      color: color.text,
      background: color.background,

      ':hover': {
        background: color.core.borderLight,
        border: '1px double ' + String(color.core.borderLight),
        color: 'inherit'
      }
    },

    CalendarDay__hovered_offset: {
      background: color.core.borderBright,
      border: '1px double ' + String(color.core.borderLight),
      color: 'inherit'
    },

    CalendarDay__outside: {
      border: 0,
      background: color.outside.backgroundColor,
      color: color.outside.color,

      ':hover': {
        border: 0
      }
    },

    CalendarDay__blocked_minimum_nights: {
      background: color.minimumNights.backgroundColor,
      border: '1px solid ' + String(color.minimumNights.borderColor),
      color: color.minimumNights.color,

      ':hover': {
        background: color.minimumNights.backgroundColor_hover,
        color: color.minimumNights.color_active
      },

      ':active': {
        background: color.minimumNights.backgroundColor_active,
        color: color.minimumNights.color_active
      }
    },

    CalendarDay__highlighted_calendar: {
      background: color.highlighted.backgroundColor,
      color: color.highlighted.color,

      ':hover': {
        background: color.highlighted.backgroundColor_hover,
        color: color.highlighted.color_active
      },

      ':active': {
        background: color.highlighted.backgroundColor_active,
        color: color.highlighted.color_active
      }
    },

    CalendarDay__selected_span: {
      background: color.selectedSpan.backgroundColor,
      border: '1px solid ' + String(color.selectedSpan.borderColor),
      color: color.selectedSpan.color,

      ':hover': {
        background: color.selectedSpan.backgroundColor_hover,
        border: '1px solid ' + String(color.selectedSpan.borderColor),
        color: color.selectedSpan.color_active
      },

      ':active': {
        background: color.selectedSpan.backgroundColor_active,
        border: '1px solid ' + String(color.selectedSpan.borderColor),
        color: color.selectedSpan.color_active
      }
    },

    CalendarDay__last_in_range: {
      borderRight: color.core.primary
    },

    CalendarDay__selected: {
      background: color.selected.backgroundColor,
      border: '1px solid ' + String(color.selected.borderColor),
      color: color.selected.color,

      ':hover': {
        background: color.selected.backgroundColor_hover,
        border: '1px solid ' + String(color.selected.borderColor),
        color: color.selected.color_active
      },

      ':active': {
        background: color.selected.backgroundColor_active,
        border: '1px solid ' + String(color.selected.borderColor),
        color: color.selected.color_active
      }
    },

    CalendarDay__hovered_span: {
      background: color.hoveredSpan.backgroundColor,
      border: '1px solid ' + String(color.hoveredSpan.borderColor),
      color: color.hoveredSpan.color,

      ':hover': {
        background: color.hoveredSpan.backgroundColor_hover,
        border: '1px solid ' + String(color.hoveredSpan.borderColor),
        color: color.hoveredSpan.color_active
      },

      ':active': {
        background: color.hoveredSpan.backgroundColor_active,
        border: '1px solid ' + String(color.hoveredSpan.borderColor),
        color: color.hoveredSpan.color_active
      }
    },

    CalendarDay__blocked_calendar: {
      background: color.blocked_calendar.backgroundColor,
      border: '1px solid ' + String(color.blocked_calendar.borderColor),
      color: color.blocked_calendar.color,

      ':hover': {
        background: color.blocked_calendar.backgroundColor_hover,
        border: '1px solid ' + String(color.blocked_calendar.borderColor),
        color: color.blocked_calendar.color_active
      },

      ':active': {
        background: color.blocked_calendar.backgroundColor_active,
        border: '1px solid ' + String(color.blocked_calendar.borderColor),
        color: color.blocked_calendar.color_active
      }
    },

    CalendarDay__blocked_out_of_range: {
      background: color.blocked_out_of_range.backgroundColor,
      border: '1px solid ' + String(color.blocked_out_of_range.borderColor),
      color: color.blocked_out_of_range.color,

      ':hover': {
        background: color.blocked_out_of_range.backgroundColor_hover,
        border: '1px solid ' + String(color.blocked_out_of_range.borderColor),
        color: color.blocked_out_of_range.color_active
      },

      ':active': {
        background: color.blocked_out_of_range.backgroundColor_active,
        border: '1px solid ' + String(color.blocked_out_of_range.borderColor),
        color: color.blocked_out_of_range.color_active
      }
    },

    CalendarDay__selected_start: {},
    CalendarDay__selected_end: {},
    CalendarDay__today: {},
    CalendarDay__firstDayOfWeek: {},
    CalendarDay__lastDayOfWeek: {}
  };
})(CalendarDay);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarMonth.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactAddonsShallowCompare = __webpack_require__("../../node_modules/.pnpm/react-addons-shallow-compare@15.6.3/node_modules/react-addons-shallow-compare/index.js");

var _reactAddonsShallowCompare2 = _interopRequireDefault(_reactAddonsShallowCompare);

var _reactMomentProptypes = __webpack_require__("../../node_modules/.pnpm/react-moment-proptypes@1.8.1_moment@2.29.4/node_modules/react-moment-proptypes/src/index.js");

var _reactMomentProptypes2 = _interopRequireDefault(_reactMomentProptypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _CalendarWeek = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarWeek.js");

var _CalendarWeek2 = _interopRequireDefault(_CalendarWeek);

var _CalendarDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarDay.js");

var _CalendarDay2 = _interopRequireDefault(_CalendarDay);

var _calculateDimension = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/calculateDimension.js");

var _calculateDimension2 = _interopRequireDefault(_calculateDimension);

var _getCalendarMonthWeeks = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarMonthWeeks.js");

var _getCalendarMonthWeeks2 = _interopRequireDefault(_getCalendarMonthWeeks);

var _isSameDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isSameDay.js");

var _isSameDay2 = _interopRequireDefault(_isSameDay);

var _toISODateString = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toISODateString.js");

var _toISODateString2 = _interopRequireDefault(_toISODateString);

var _ModifiersShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ModifiersShape.js");

var _ModifiersShape2 = _interopRequireDefault(_ModifiersShape);

var _ScrollableOrientationShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ScrollableOrientationShape.js");

var _ScrollableOrientationShape2 = _interopRequireDefault(_ScrollableOrientationShape);

var _DayOfWeekShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/DayOfWeekShape.js");

var _DayOfWeekShape2 = _interopRequireDefault(_DayOfWeekShape);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; } /* eslint react/no-array-index-key: 0 */

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {
  month: _reactMomentProptypes2['default'].momentObj,
  horizontalMonthPadding: _airbnbPropTypes.nonNegativeInteger,
  isVisible: _propTypes2['default'].bool,
  enableOutsideDays: _propTypes2['default'].bool,
  modifiers: _propTypes2['default'].objectOf(_ModifiersShape2['default']),
  orientation: _ScrollableOrientationShape2['default'],
  daySize: _airbnbPropTypes.nonNegativeInteger,
  onDayClick: _propTypes2['default'].func,
  onDayMouseEnter: _propTypes2['default'].func,
  onDayMouseLeave: _propTypes2['default'].func,
  onMonthSelect: _propTypes2['default'].func,
  onYearSelect: _propTypes2['default'].func,
  renderMonthText: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),
  renderCalendarDay: _propTypes2['default'].func,
  renderDayContents: _propTypes2['default'].func,
  renderMonthElement: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),
  firstDayOfWeek: _DayOfWeekShape2['default'],
  setMonthTitleHeight: _propTypes2['default'].func,
  verticalBorderSpacing: _airbnbPropTypes.nonNegativeInteger,

  focusedDate: _reactMomentProptypes2['default'].momentObj, // indicates focusable day
  isFocused: _propTypes2['default'].bool, // indicates whether or not to move focus to focusable day

  // i18n
  monthFormat: _propTypes2['default'].string,
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.CalendarDayPhrases)),
  dayAriaLabelFormat: _propTypes2['default'].string
}));

var defaultProps = {
  month: (0, _moment2['default'])(),
  horizontalMonthPadding: 13,
  isVisible: true,
  enableOutsideDays: false,
  modifiers: {},
  orientation: _constants.HORIZONTAL_ORIENTATION,
  daySize: _constants.DAY_SIZE,
  onDayClick: function () {
    function onDayClick() {}

    return onDayClick;
  }(),
  onDayMouseEnter: function () {
    function onDayMouseEnter() {}

    return onDayMouseEnter;
  }(),
  onDayMouseLeave: function () {
    function onDayMouseLeave() {}

    return onDayMouseLeave;
  }(),
  onMonthSelect: function () {
    function onMonthSelect() {}

    return onMonthSelect;
  }(),
  onYearSelect: function () {
    function onYearSelect() {}

    return onYearSelect;
  }(),

  renderMonthText: null,
  renderCalendarDay: function () {
    function renderCalendarDay(props) {
      return _react2['default'].createElement(_CalendarDay2['default'], props);
    }

    return renderCalendarDay;
  }(),
  renderDayContents: null,
  renderMonthElement: null,
  firstDayOfWeek: null,
  setMonthTitleHeight: null,

  focusedDate: null,
  isFocused: false,

  // i18n
  monthFormat: 'MMMM YYYY', // english locale
  phrases: _defaultPhrases.CalendarDayPhrases,
  dayAriaLabelFormat: undefined,
  verticalBorderSpacing: undefined
};

var CalendarMonth = function (_React$Component) {
  _inherits(CalendarMonth, _React$Component);

  function CalendarMonth(props) {
    _classCallCheck(this, CalendarMonth);

    var _this = _possibleConstructorReturn(this, (CalendarMonth.__proto__ || Object.getPrototypeOf(CalendarMonth)).call(this, props));

    _this.state = {
      weeks: (0, _getCalendarMonthWeeks2['default'])(props.month, props.enableOutsideDays, props.firstDayOfWeek == null ? _moment2['default'].localeData().firstDayOfWeek() : props.firstDayOfWeek)
    };

    _this.setCaptionRef = _this.setCaptionRef.bind(_this);
    _this.setMonthTitleHeight = _this.setMonthTitleHeight.bind(_this);
    return _this;
  }

  _createClass(CalendarMonth, [{
    key: 'componentDidMount',
    value: function () {
      function componentDidMount() {
        this.setMonthTitleHeightTimeout = setTimeout(this.setMonthTitleHeight, 0);
      }

      return componentDidMount;
    }()
  }, {
    key: 'componentWillReceiveProps',
    value: function () {
      function componentWillReceiveProps(nextProps) {
        var month = nextProps.month,
            enableOutsideDays = nextProps.enableOutsideDays,
            firstDayOfWeek = nextProps.firstDayOfWeek;
        var _props = this.props,
            prevMonth = _props.month,
            prevEnableOutsideDays = _props.enableOutsideDays,
            prevFirstDayOfWeek = _props.firstDayOfWeek;

        if (!month.isSame(prevMonth) || enableOutsideDays !== prevEnableOutsideDays || firstDayOfWeek !== prevFirstDayOfWeek) {
          this.setState({
            weeks: (0, _getCalendarMonthWeeks2['default'])(month, enableOutsideDays, firstDayOfWeek == null ? _moment2['default'].localeData().firstDayOfWeek() : firstDayOfWeek)
          });
        }
      }

      return componentWillReceiveProps;
    }()
  }, {
    key: 'shouldComponentUpdate',
    value: function () {
      function shouldComponentUpdate(nextProps, nextState) {
        return (0, _reactAddonsShallowCompare2['default'])(this, nextProps, nextState);
      }

      return shouldComponentUpdate;
    }()
  }, {
    key: 'componentWillUnmount',
    value: function () {
      function componentWillUnmount() {
        if (this.setMonthTitleHeightTimeout) {
          clearTimeout(this.setMonthTitleHeightTimeout);
        }
      }

      return componentWillUnmount;
    }()
  }, {
    key: 'setMonthTitleHeight',
    value: function () {
      function setMonthTitleHeight() {
        var setMonthTitleHeight = this.props.setMonthTitleHeight;

        if (setMonthTitleHeight) {
          var captionHeight = (0, _calculateDimension2['default'])(this.captionRef, 'height', true, true);
          setMonthTitleHeight(captionHeight);
        }
      }

      return setMonthTitleHeight;
    }()
  }, {
    key: 'setCaptionRef',
    value: function () {
      function setCaptionRef(ref) {
        this.captionRef = ref;
      }

      return setCaptionRef;
    }()
  }, {
    key: 'render',
    value: function () {
      function render() {
        var _props2 = this.props,
            dayAriaLabelFormat = _props2.dayAriaLabelFormat,
            daySize = _props2.daySize,
            focusedDate = _props2.focusedDate,
            horizontalMonthPadding = _props2.horizontalMonthPadding,
            isFocused = _props2.isFocused,
            isVisible = _props2.isVisible,
            modifiers = _props2.modifiers,
            month = _props2.month,
            monthFormat = _props2.monthFormat,
            onDayClick = _props2.onDayClick,
            onDayMouseEnter = _props2.onDayMouseEnter,
            onDayMouseLeave = _props2.onDayMouseLeave,
            onMonthSelect = _props2.onMonthSelect,
            onYearSelect = _props2.onYearSelect,
            orientation = _props2.orientation,
            phrases = _props2.phrases,
            renderCalendarDay = _props2.renderCalendarDay,
            renderDayContents = _props2.renderDayContents,
            renderMonthElement = _props2.renderMonthElement,
            renderMonthText = _props2.renderMonthText,
            styles = _props2.styles,
            verticalBorderSpacing = _props2.verticalBorderSpacing;
        var weeks = this.state.weeks;

        var monthTitle = renderMonthText ? renderMonthText(month) : month.format(monthFormat);

        var verticalScrollable = orientation === _constants.VERTICAL_SCROLLABLE;

        return _react2['default'].createElement(
          'div',
          _extends({}, (0, _reactWithStyles.css)(styles.CalendarMonth, { padding: '0 ' + String(horizontalMonthPadding) + 'px' }), {
            'data-visible': isVisible
          }),
          _react2['default'].createElement(
            'div',
            _extends({
              ref: this.setCaptionRef
            }, (0, _reactWithStyles.css)(styles.CalendarMonth_caption, verticalScrollable && styles.CalendarMonth_caption__verticalScrollable)),
            renderMonthElement ? renderMonthElement({ month: month, onMonthSelect: onMonthSelect, onYearSelect: onYearSelect }) : _react2['default'].createElement(
              'strong',
              null,
              monthTitle
            )
          ),
          _react2['default'].createElement(
            'table',
            _extends({}, (0, _reactWithStyles.css)(!verticalBorderSpacing && styles.CalendarMonth_table, verticalBorderSpacing && styles.CalendarMonth_verticalSpacing, verticalBorderSpacing && { borderSpacing: '0px ' + String(verticalBorderSpacing) + 'px' }), {
              role: 'presentation'
            }),
            _react2['default'].createElement(
              'tbody',
              null,
              weeks.map(function (week, i) {
                return _react2['default'].createElement(
                  _CalendarWeek2['default'],
                  { key: i },
                  week.map(function (day, dayOfWeek) {
                    return renderCalendarDay({
                      key: dayOfWeek,
                      day: day,
                      daySize: daySize,
                      isOutsideDay: !day || day.month() !== month.month(),
                      tabIndex: isVisible && (0, _isSameDay2['default'])(day, focusedDate) ? 0 : -1,
                      isFocused: isFocused,
                      onDayMouseEnter: onDayMouseEnter,
                      onDayMouseLeave: onDayMouseLeave,
                      onDayClick: onDayClick,
                      renderDayContents: renderDayContents,
                      phrases: phrases,
                      modifiers: modifiers[(0, _toISODateString2['default'])(day)],
                      ariaLabelFormat: dayAriaLabelFormat
                    });
                  })
                );
              })
            )
          )
        );
      }

      return render;
    }()
  }]);

  return CalendarMonth;
}(_react2['default'].Component);

CalendarMonth.propTypes = propTypes;
CalendarMonth.defaultProps = defaultProps;

exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref) {
  var _ref$reactDates = _ref.reactDates,
      color = _ref$reactDates.color,
      font = _ref$reactDates.font,
      spacing = _ref$reactDates.spacing;
  return {
    CalendarMonth: {
      background: color.background,
      textAlign: 'center',
      verticalAlign: 'top',
      userSelect: 'none'
    },

    CalendarMonth_table: {
      borderCollapse: 'collapse',
      borderSpacing: 0
    },

    CalendarMonth_verticalSpacing: {
      borderCollapse: 'separate'
    },

    CalendarMonth_caption: {
      color: color.text,
      fontSize: font.captionSize,
      textAlign: 'center',
      paddingTop: spacing.captionPaddingTop,
      paddingBottom: spacing.captionPaddingBottom,
      captionSide: 'initial'
    },

    CalendarMonth_caption__verticalScrollable: {
      paddingTop: 12,
      paddingBottom: 7
    }
  };
})(CalendarMonth);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarMonthGrid.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactAddonsShallowCompare = __webpack_require__("../../node_modules/.pnpm/react-addons-shallow-compare@15.6.3/node_modules/react-addons-shallow-compare/index.js");

var _reactAddonsShallowCompare2 = _interopRequireDefault(_reactAddonsShallowCompare);

var _reactMomentProptypes = __webpack_require__("../../node_modules/.pnpm/react-moment-proptypes@1.8.1_moment@2.29.4/node_modules/react-moment-proptypes/src/index.js");

var _reactMomentProptypes2 = _interopRequireDefault(_reactMomentProptypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _consolidatedEvents = __webpack_require__("../../node_modules/.pnpm/consolidated-events@2.0.2/node_modules/consolidated-events/lib/index.esm.js");

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _CalendarMonth = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarMonth.js");

var _CalendarMonth2 = _interopRequireDefault(_CalendarMonth);

var _isTransitionEndSupported = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isTransitionEndSupported.js");

var _isTransitionEndSupported2 = _interopRequireDefault(_isTransitionEndSupported);

var _getTransformStyles = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getTransformStyles.js");

var _getTransformStyles2 = _interopRequireDefault(_getTransformStyles);

var _getCalendarMonthWidth = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarMonthWidth.js");

var _getCalendarMonthWidth2 = _interopRequireDefault(_getCalendarMonthWidth);

var _toISOMonthString = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toISOMonthString.js");

var _toISOMonthString2 = _interopRequireDefault(_toISOMonthString);

var _isPrevMonth = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isPrevMonth.js");

var _isPrevMonth2 = _interopRequireDefault(_isPrevMonth);

var _isNextMonth = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isNextMonth.js");

var _isNextMonth2 = _interopRequireDefault(_isNextMonth);

var _ModifiersShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ModifiersShape.js");

var _ModifiersShape2 = _interopRequireDefault(_ModifiersShape);

var _ScrollableOrientationShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ScrollableOrientationShape.js");

var _ScrollableOrientationShape2 = _interopRequireDefault(_ScrollableOrientationShape);

var _DayOfWeekShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/DayOfWeekShape.js");

var _DayOfWeekShape2 = _interopRequireDefault(_DayOfWeekShape);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {
  enableOutsideDays: _propTypes2['default'].bool,
  firstVisibleMonthIndex: _propTypes2['default'].number,
  horizontalMonthPadding: _airbnbPropTypes.nonNegativeInteger,
  initialMonth: _reactMomentProptypes2['default'].momentObj,
  isAnimating: _propTypes2['default'].bool,
  numberOfMonths: _propTypes2['default'].number,
  modifiers: _propTypes2['default'].objectOf(_propTypes2['default'].objectOf(_ModifiersShape2['default'])),
  orientation: _ScrollableOrientationShape2['default'],
  onDayClick: _propTypes2['default'].func,
  onDayMouseEnter: _propTypes2['default'].func,
  onDayMouseLeave: _propTypes2['default'].func,
  onMonthTransitionEnd: _propTypes2['default'].func,
  onMonthChange: _propTypes2['default'].func,
  onYearChange: _propTypes2['default'].func,
  renderMonthText: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),
  renderCalendarDay: _propTypes2['default'].func,
  renderDayContents: _propTypes2['default'].func,
  translationValue: _propTypes2['default'].number,
  renderMonthElement: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),
  daySize: _airbnbPropTypes.nonNegativeInteger,
  focusedDate: _reactMomentProptypes2['default'].momentObj, // indicates focusable day
  isFocused: _propTypes2['default'].bool, // indicates whether or not to move focus to focusable day
  firstDayOfWeek: _DayOfWeekShape2['default'],
  setMonthTitleHeight: _propTypes2['default'].func,
  isRTL: _propTypes2['default'].bool,
  transitionDuration: _airbnbPropTypes.nonNegativeInteger,
  verticalBorderSpacing: _airbnbPropTypes.nonNegativeInteger,

  // i18n
  monthFormat: _propTypes2['default'].string,
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.CalendarDayPhrases)),
  dayAriaLabelFormat: _propTypes2['default'].string
}));

var defaultProps = {
  enableOutsideDays: false,
  firstVisibleMonthIndex: 0,
  horizontalMonthPadding: 13,
  initialMonth: (0, _moment2['default'])(),
  isAnimating: false,
  numberOfMonths: 1,
  modifiers: {},
  orientation: _constants.HORIZONTAL_ORIENTATION,
  onDayClick: function () {
    function onDayClick() {}

    return onDayClick;
  }(),
  onDayMouseEnter: function () {
    function onDayMouseEnter() {}

    return onDayMouseEnter;
  }(),
  onDayMouseLeave: function () {
    function onDayMouseLeave() {}

    return onDayMouseLeave;
  }(),
  onMonthChange: function () {
    function onMonthChange() {}

    return onMonthChange;
  }(),
  onYearChange: function () {
    function onYearChange() {}

    return onYearChange;
  }(),
  onMonthTransitionEnd: function () {
    function onMonthTransitionEnd() {}

    return onMonthTransitionEnd;
  }(),

  renderMonthText: null,
  renderCalendarDay: undefined,
  renderDayContents: null,
  translationValue: null,
  renderMonthElement: null,
  daySize: _constants.DAY_SIZE,
  focusedDate: null,
  isFocused: false,
  firstDayOfWeek: null,
  setMonthTitleHeight: null,
  isRTL: false,
  transitionDuration: 200,
  verticalBorderSpacing: undefined,

  // i18n
  monthFormat: 'MMMM YYYY', // english locale
  phrases: _defaultPhrases.CalendarDayPhrases,
  dayAriaLabelFormat: undefined
};

function getMonths(initialMonth, numberOfMonths, withoutTransitionMonths) {
  var month = initialMonth.clone();
  if (!withoutTransitionMonths) month = month.subtract(1, 'month');

  var months = [];
  for (var i = 0; i < (withoutTransitionMonths ? numberOfMonths : numberOfMonths + 2); i += 1) {
    months.push(month);
    month = month.clone().add(1, 'month');
  }

  return months;
}

var CalendarMonthGrid = function (_React$Component) {
  _inherits(CalendarMonthGrid, _React$Component);

  function CalendarMonthGrid(props) {
    _classCallCheck(this, CalendarMonthGrid);

    var _this = _possibleConstructorReturn(this, (CalendarMonthGrid.__proto__ || Object.getPrototypeOf(CalendarMonthGrid)).call(this, props));

    var withoutTransitionMonths = props.orientation === _constants.VERTICAL_SCROLLABLE;
    _this.state = {
      months: getMonths(props.initialMonth, props.numberOfMonths, withoutTransitionMonths)
    };

    _this.isTransitionEndSupported = (0, _isTransitionEndSupported2['default'])();
    _this.onTransitionEnd = _this.onTransitionEnd.bind(_this);
    _this.setContainerRef = _this.setContainerRef.bind(_this);

    _this.locale = _moment2['default'].locale();
    _this.onMonthSelect = _this.onMonthSelect.bind(_this);
    _this.onYearSelect = _this.onYearSelect.bind(_this);
    return _this;
  }

  _createClass(CalendarMonthGrid, [{
    key: 'componentDidMount',
    value: function () {
      function componentDidMount() {
        this.removeEventListener = (0, _consolidatedEvents.addEventListener)(this.container, 'transitionend', this.onTransitionEnd);
      }

      return componentDidMount;
    }()
  }, {
    key: 'componentWillReceiveProps',
    value: function () {
      function componentWillReceiveProps(nextProps) {
        var _this2 = this;

        var initialMonth = nextProps.initialMonth,
            numberOfMonths = nextProps.numberOfMonths,
            orientation = nextProps.orientation;
        var months = this.state.months;
        var _props = this.props,
            prevInitialMonth = _props.initialMonth,
            prevNumberOfMonths = _props.numberOfMonths;

        var hasMonthChanged = !prevInitialMonth.isSame(initialMonth, 'month');
        var hasNumberOfMonthsChanged = prevNumberOfMonths !== numberOfMonths;
        var newMonths = months;

        if (hasMonthChanged && !hasNumberOfMonthsChanged) {
          if ((0, _isNextMonth2['default'])(prevInitialMonth, initialMonth)) {
            newMonths = months.slice(1);
            newMonths.push(months[months.length - 1].clone().add(1, 'month'));
          } else if ((0, _isPrevMonth2['default'])(prevInitialMonth, initialMonth)) {
            newMonths = months.slice(0, months.length - 1);
            newMonths.unshift(months[0].clone().subtract(1, 'month'));
          } else {
            var withoutTransitionMonths = orientation === _constants.VERTICAL_SCROLLABLE;
            newMonths = getMonths(initialMonth, numberOfMonths, withoutTransitionMonths);
          }
        }

        if (hasNumberOfMonthsChanged) {
          var _withoutTransitionMonths = orientation === _constants.VERTICAL_SCROLLABLE;
          newMonths = getMonths(initialMonth, numberOfMonths, _withoutTransitionMonths);
        }

        var momentLocale = _moment2['default'].locale();
        if (this.locale !== momentLocale) {
          this.locale = momentLocale;
          newMonths = newMonths.map(function (m) {
            return m.locale(_this2.locale);
          });
        }

        this.setState({
          months: newMonths
        });
      }

      return componentWillReceiveProps;
    }()
  }, {
    key: 'shouldComponentUpdate',
    value: function () {
      function shouldComponentUpdate(nextProps, nextState) {
        return (0, _reactAddonsShallowCompare2['default'])(this, nextProps, nextState);
      }

      return shouldComponentUpdate;
    }()
  }, {
    key: 'componentDidUpdate',
    value: function () {
      function componentDidUpdate() {
        var _props2 = this.props,
            isAnimating = _props2.isAnimating,
            transitionDuration = _props2.transitionDuration,
            onMonthTransitionEnd = _props2.onMonthTransitionEnd;

        // For IE9, immediately call onMonthTransitionEnd instead of
        // waiting for the animation to complete. Similarly, if transitionDuration
        // is set to 0, also immediately invoke the onMonthTransitionEnd callback

        if ((!this.isTransitionEndSupported || !transitionDuration) && isAnimating) {
          onMonthTransitionEnd();
        }
      }

      return componentDidUpdate;
    }()
  }, {
    key: 'componentWillUnmount',
    value: function () {
      function componentWillUnmount() {
        if (this.removeEventListener) this.removeEventListener();
      }

      return componentWillUnmount;
    }()
  }, {
    key: 'onTransitionEnd',
    value: function () {
      function onTransitionEnd() {
        var onMonthTransitionEnd = this.props.onMonthTransitionEnd;

        onMonthTransitionEnd();
      }

      return onTransitionEnd;
    }()
  }, {
    key: 'onMonthSelect',
    value: function () {
      function onMonthSelect(currentMonth, newMonthVal) {
        var newMonth = currentMonth.clone();
        var _props3 = this.props,
            onMonthChange = _props3.onMonthChange,
            orientation = _props3.orientation;
        var months = this.state.months;

        var withoutTransitionMonths = orientation === _constants.VERTICAL_SCROLLABLE;
        var initialMonthSubtraction = months.indexOf(currentMonth);
        if (!withoutTransitionMonths) {
          initialMonthSubtraction -= 1;
        }
        newMonth.set('month', newMonthVal).subtract(initialMonthSubtraction, 'months');
        onMonthChange(newMonth);
      }

      return onMonthSelect;
    }()
  }, {
    key: 'onYearSelect',
    value: function () {
      function onYearSelect(currentMonth, newYearVal) {
        var newMonth = currentMonth.clone();
        var _props4 = this.props,
            onYearChange = _props4.onYearChange,
            orientation = _props4.orientation;
        var months = this.state.months;

        var withoutTransitionMonths = orientation === _constants.VERTICAL_SCROLLABLE;
        var initialMonthSubtraction = months.indexOf(currentMonth);
        if (!withoutTransitionMonths) {
          initialMonthSubtraction -= 1;
        }
        newMonth.set('year', newYearVal).subtract(initialMonthSubtraction, 'months');
        onYearChange(newMonth);
      }

      return onYearSelect;
    }()
  }, {
    key: 'setContainerRef',
    value: function () {
      function setContainerRef(ref) {
        this.container = ref;
      }

      return setContainerRef;
    }()
  }, {
    key: 'render',
    value: function () {
      function render() {
        var _this3 = this;

        var _props5 = this.props,
            enableOutsideDays = _props5.enableOutsideDays,
            firstVisibleMonthIndex = _props5.firstVisibleMonthIndex,
            horizontalMonthPadding = _props5.horizontalMonthPadding,
            isAnimating = _props5.isAnimating,
            modifiers = _props5.modifiers,
            numberOfMonths = _props5.numberOfMonths,
            monthFormat = _props5.monthFormat,
            orientation = _props5.orientation,
            translationValue = _props5.translationValue,
            daySize = _props5.daySize,
            onDayMouseEnter = _props5.onDayMouseEnter,
            onDayMouseLeave = _props5.onDayMouseLeave,
            onDayClick = _props5.onDayClick,
            renderMonthText = _props5.renderMonthText,
            renderCalendarDay = _props5.renderCalendarDay,
            renderDayContents = _props5.renderDayContents,
            renderMonthElement = _props5.renderMonthElement,
            onMonthTransitionEnd = _props5.onMonthTransitionEnd,
            firstDayOfWeek = _props5.firstDayOfWeek,
            focusedDate = _props5.focusedDate,
            isFocused = _props5.isFocused,
            isRTL = _props5.isRTL,
            styles = _props5.styles,
            phrases = _props5.phrases,
            dayAriaLabelFormat = _props5.dayAriaLabelFormat,
            transitionDuration = _props5.transitionDuration,
            verticalBorderSpacing = _props5.verticalBorderSpacing,
            setMonthTitleHeight = _props5.setMonthTitleHeight;
        var months = this.state.months;

        var isVertical = orientation === _constants.VERTICAL_ORIENTATION;
        var isVerticalScrollable = orientation === _constants.VERTICAL_SCROLLABLE;
        var isHorizontal = orientation === _constants.HORIZONTAL_ORIENTATION;

        var calendarMonthWidth = (0, _getCalendarMonthWidth2['default'])(daySize, horizontalMonthPadding);

        var width = isVertical || isVerticalScrollable ? calendarMonthWidth : (numberOfMonths + 2) * calendarMonthWidth;

        var transformType = isVertical || isVerticalScrollable ? 'translateY' : 'translateX';
        var transformValue = transformType + '(' + String(translationValue) + 'px)';

        return _react2['default'].createElement(
          'div',
          _extends({}, (0, _reactWithStyles.css)(styles.CalendarMonthGrid, isHorizontal && styles.CalendarMonthGrid__horizontal, isVertical && styles.CalendarMonthGrid__vertical, isVerticalScrollable && styles.CalendarMonthGrid__vertical_scrollable, isAnimating && styles.CalendarMonthGrid__animating, isAnimating && transitionDuration && {
            transition: 'transform ' + String(transitionDuration) + 'ms ease-in-out'
          }, (0, _object2['default'])({}, (0, _getTransformStyles2['default'])(transformValue), {
            width: width
          })), {
            ref: this.setContainerRef,
            onTransitionEnd: onMonthTransitionEnd
          }),
          months.map(function (month, i) {
            var isVisible = i >= firstVisibleMonthIndex && i < firstVisibleMonthIndex + numberOfMonths;
            var hideForAnimation = i === 0 && !isVisible;
            var showForAnimation = i === 0 && isAnimating && isVisible;
            var monthString = (0, _toISOMonthString2['default'])(month);
            return _react2['default'].createElement(
              'div',
              _extends({
                key: monthString
              }, (0, _reactWithStyles.css)(isHorizontal && styles.CalendarMonthGrid_month__horizontal, hideForAnimation && styles.CalendarMonthGrid_month__hideForAnimation, showForAnimation && !isVertical && !isRTL && {
                position: 'absolute',
                left: -calendarMonthWidth
              }, showForAnimation && !isVertical && isRTL && {
                position: 'absolute',
                right: 0
              }, showForAnimation && isVertical && {
                position: 'absolute',
                top: -translationValue
              }, !isVisible && !isAnimating && styles.CalendarMonthGrid_month__hidden)),
              _react2['default'].createElement(_CalendarMonth2['default'], {
                month: month,
                isVisible: isVisible,
                enableOutsideDays: enableOutsideDays,
                modifiers: modifiers[monthString],
                monthFormat: monthFormat,
                orientation: orientation,
                onDayMouseEnter: onDayMouseEnter,
                onDayMouseLeave: onDayMouseLeave,
                onDayClick: onDayClick,
                onMonthSelect: _this3.onMonthSelect,
                onYearSelect: _this3.onYearSelect,
                renderMonthText: renderMonthText,
                renderCalendarDay: renderCalendarDay,
                renderDayContents: renderDayContents,
                renderMonthElement: renderMonthElement,
                firstDayOfWeek: firstDayOfWeek,
                daySize: daySize,
                focusedDate: isVisible ? focusedDate : null,
                isFocused: isFocused,
                phrases: phrases,
                setMonthTitleHeight: setMonthTitleHeight,
                dayAriaLabelFormat: dayAriaLabelFormat,
                verticalBorderSpacing: verticalBorderSpacing,
                horizontalMonthPadding: horizontalMonthPadding
              })
            );
          })
        );
      }

      return render;
    }()
  }]);

  return CalendarMonthGrid;
}(_react2['default'].Component);

CalendarMonthGrid.propTypes = propTypes;
CalendarMonthGrid.defaultProps = defaultProps;

exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref) {
  var _ref$reactDates = _ref.reactDates,
      color = _ref$reactDates.color,
      noScrollBarOnVerticalScrollable = _ref$reactDates.noScrollBarOnVerticalScrollable,
      spacing = _ref$reactDates.spacing,
      zIndex = _ref$reactDates.zIndex;
  return {
    CalendarMonthGrid: {
      background: color.background,
      textAlign: 'left',
      zIndex: zIndex
    },

    CalendarMonthGrid__animating: {
      zIndex: zIndex + 1
    },

    CalendarMonthGrid__horizontal: {
      position: 'absolute',
      left: spacing.dayPickerHorizontalPadding
    },

    CalendarMonthGrid__vertical: {
      margin: '0 auto'
    },

    CalendarMonthGrid__vertical_scrollable: (0, _object2['default'])({
      margin: '0 auto',
      overflowY: 'scroll'
    }, noScrollBarOnVerticalScrollable && {
      '-webkitOverflowScrolling': 'touch',
      '::-webkit-scrollbar': {
        '-webkit-appearance': 'none',
        display: 'none'
      }
    }),

    CalendarMonthGrid_month__horizontal: {
      display: 'inline-block',
      verticalAlign: 'top',
      minHeight: '100%'
    },

    CalendarMonthGrid_month__hideForAnimation: {
      position: 'absolute',
      zIndex: zIndex - 1,
      opacity: 0,
      pointerEvents: 'none'
    },

    CalendarMonthGrid_month__hidden: {
      visibility: 'hidden'
    }
  };
})(CalendarMonthGrid);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarWeek.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = CalendarWeek;

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _CalendarDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarDay.js");

var _CalendarDay2 = _interopRequireDefault(_CalendarDay);

var _CustomizableCalendarDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CustomizableCalendarDay.js");

var _CustomizableCalendarDay2 = _interopRequireDefault(_CustomizableCalendarDay);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)({
  children: (0, _airbnbPropTypes.or)([(0, _airbnbPropTypes.childrenOfType)(_CalendarDay2['default']), (0, _airbnbPropTypes.childrenOfType)(_CustomizableCalendarDay2['default'])]).isRequired
});

function CalendarWeek(_ref) {
  var children = _ref.children;

  return _react2['default'].createElement(
    'tr',
    null,
    children
  );
}

CalendarWeek.propTypes = propTypes;

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/ChevronDown.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

var ChevronDown = function () {
  function ChevronDown(props) {
    return _react2['default'].createElement(
      'svg',
      props,
      _react2['default'].createElement('path', {
        d: 'M967.5 288.5L514.3 740.7c-11 11-21 11-32 0L29.1 288.5c-4-5-6-11-6-16 0-13 10-23 23-23 6 0 11 2 15 7l437.2 436.2 437.2-436.2c4-5 9-7 16-7 6 0 11 2 16 7 9 10.9 9 21 0 32z'
      })
    );
  }

  return ChevronDown;
}();

ChevronDown.defaultProps = {
  viewBox: '0 0 1000 1000'
};
exports["default"] = ChevronDown;

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/ChevronUp.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

var ChevronUp = function () {
  function ChevronUp(props) {
    return _react2['default'].createElement(
      'svg',
      props,
      _react2['default'].createElement('path', {
        d: 'M32.1 712.6l453.2-452.2c11-11 21-11 32 0l453.2 452.2c4 5 6 10 6 16 0 13-10 23-22 23-7 0-12-2-16-7L501.3 308.5 64.1 744.7c-4 5-9 7-15 7-7 0-12-2-17-7-9-11-9-21 0-32.1z'
      })
    );
  }

  return ChevronUp;
}();

ChevronUp.defaultProps = {
  viewBox: '0 0 1000 1000'
};
exports["default"] = ChevronUp;

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CloseButton.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

var CloseButton = function () {
  function CloseButton(props) {
    return _react2['default'].createElement(
      'svg',
      props,
      _react2['default'].createElement('path', {
        fillRule: 'evenodd',
        d: 'M11.53.47a.75.75 0 0 0-1.061 0l-4.47 4.47L1.529.47A.75.75 0 1 0 .468 1.531l4.47 4.47-4.47 4.47a.75.75 0 1 0 1.061 1.061l4.47-4.47 4.47 4.47a.75.75 0 1 0 1.061-1.061l-4.47-4.47 4.47-4.47a.75.75 0 0 0 0-1.061z'
      })
    );
  }

  return CloseButton;
}();

CloseButton.defaultProps = {
  viewBox: '0 0 12 12'
};
exports["default"] = CloseButton;

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CustomizableCalendarDay.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.PureCustomizableCalendarDay = exports.selectedStyles = exports.lastInRangeStyles = exports.selectedSpanStyles = exports.hoveredSpanStyles = exports.blockedOutOfRangeStyles = exports.blockedCalendarStyles = exports.blockedMinNightsStyles = exports.highlightedCalendarStyles = exports.outsideStyles = exports.defaultStyles = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactAddonsShallowCompare = __webpack_require__("../../node_modules/.pnpm/react-addons-shallow-compare@15.6.3/node_modules/react-addons-shallow-compare/index.js");

var _reactAddonsShallowCompare2 = _interopRequireDefault(_reactAddonsShallowCompare);

var _reactMomentProptypes = __webpack_require__("../../node_modules/.pnpm/react-moment-proptypes@1.8.1_moment@2.29.4/node_modules/react-moment-proptypes/src/index.js");

var _reactMomentProptypes2 = _interopRequireDefault(_reactMomentProptypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _getCalendarDaySettings = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarDaySettings.js");

var _getCalendarDaySettings2 = _interopRequireDefault(_getCalendarDaySettings);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

var _DefaultTheme = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/theme/DefaultTheme.js");

var _DefaultTheme2 = _interopRequireDefault(_DefaultTheme);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var color = _DefaultTheme2['default'].reactDates.color;


function getStyles(stylesObj, isHovered) {
  if (!stylesObj) return null;

  var hover = stylesObj.hover;

  if (isHovered && hover) {
    return hover;
  }

  return stylesObj;
}

var DayStyleShape = _propTypes2['default'].shape({
  background: _propTypes2['default'].string,
  border: (0, _airbnbPropTypes.or)([_propTypes2['default'].string, _propTypes2['default'].number]),
  color: _propTypes2['default'].string,

  hover: _propTypes2['default'].shape({
    background: _propTypes2['default'].string,
    border: (0, _airbnbPropTypes.or)([_propTypes2['default'].string, _propTypes2['default'].number]),
    color: _propTypes2['default'].string
  })
});

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {
  day: _reactMomentProptypes2['default'].momentObj,
  daySize: _airbnbPropTypes.nonNegativeInteger,
  isOutsideDay: _propTypes2['default'].bool,
  modifiers: _propTypes2['default'].instanceOf(Set),
  isFocused: _propTypes2['default'].bool,
  tabIndex: _propTypes2['default'].oneOf([0, -1]),
  onDayClick: _propTypes2['default'].func,
  onDayMouseEnter: _propTypes2['default'].func,
  onDayMouseLeave: _propTypes2['default'].func,
  renderDayContents: _propTypes2['default'].func,
  ariaLabelFormat: _propTypes2['default'].string,

  // style overrides
  defaultStyles: DayStyleShape,
  outsideStyles: DayStyleShape,
  todayStyles: DayStyleShape,
  firstDayOfWeekStyles: DayStyleShape,
  lastDayOfWeekStyles: DayStyleShape,
  highlightedCalendarStyles: DayStyleShape,
  blockedMinNightsStyles: DayStyleShape,
  blockedCalendarStyles: DayStyleShape,
  blockedOutOfRangeStyles: DayStyleShape,
  hoveredSpanStyles: DayStyleShape,
  selectedSpanStyles: DayStyleShape,
  lastInRangeStyles: DayStyleShape,
  selectedStyles: DayStyleShape,
  selectedStartStyles: DayStyleShape,
  selectedEndStyles: DayStyleShape,
  afterHoveredStartStyles: DayStyleShape,

  // internationalization
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.CalendarDayPhrases))
}));

var defaultStyles = exports.defaultStyles = {
  border: '1px solid ' + String(color.core.borderLight),
  color: color.text,
  background: color.background,

  hover: {
    background: color.core.borderLight,
    border: '1px double ' + String(color.core.borderLight),
    color: 'inherit'
  }
};

var outsideStyles = exports.outsideStyles = {
  background: color.outside.backgroundColor,
  border: 0,
  color: color.outside.color
};

var highlightedCalendarStyles = exports.highlightedCalendarStyles = {
  background: color.highlighted.backgroundColor,
  color: color.highlighted.color,

  hover: {
    background: color.highlighted.backgroundColor_hover,
    color: color.highlighted.color_active
  }
};

var blockedMinNightsStyles = exports.blockedMinNightsStyles = {
  background: color.minimumNights.backgroundColor,
  border: '1px solid ' + String(color.minimumNights.borderColor),
  color: color.minimumNights.color,

  hover: {
    background: color.minimumNights.backgroundColor_hover,
    color: color.minimumNights.color_active
  }
};

var blockedCalendarStyles = exports.blockedCalendarStyles = {
  background: color.blocked_calendar.backgroundColor,
  border: '1px solid ' + String(color.blocked_calendar.borderColor),
  color: color.blocked_calendar.color,

  hover: {
    background: color.blocked_calendar.backgroundColor_hover,
    border: '1px solid ' + String(color.blocked_calendar.borderColor),
    color: color.blocked_calendar.color_active
  }
};

var blockedOutOfRangeStyles = exports.blockedOutOfRangeStyles = {
  background: color.blocked_out_of_range.backgroundColor,
  border: '1px solid ' + String(color.blocked_out_of_range.borderColor),
  color: color.blocked_out_of_range.color,

  hover: {
    background: color.blocked_out_of_range.backgroundColor_hover,
    border: '1px solid ' + String(color.blocked_out_of_range.borderColor),
    color: color.blocked_out_of_range.color_active
  }
};

var hoveredSpanStyles = exports.hoveredSpanStyles = {
  background: color.hoveredSpan.backgroundColor,
  border: '1px solid ' + String(color.hoveredSpan.borderColor),
  color: color.hoveredSpan.color,

  hover: {
    background: color.hoveredSpan.backgroundColor_hover,
    border: '1px solid ' + String(color.hoveredSpan.borderColor),
    color: color.hoveredSpan.color_active
  }
};

var selectedSpanStyles = exports.selectedSpanStyles = {
  background: color.selectedSpan.backgroundColor,
  border: '1px solid ' + String(color.selectedSpan.borderColor),
  color: color.selectedSpan.color,

  hover: {
    background: color.selectedSpan.backgroundColor_hover,
    border: '1px solid ' + String(color.selectedSpan.borderColor),
    color: color.selectedSpan.color_active
  }
};

var lastInRangeStyles = exports.lastInRangeStyles = {
  borderRight: color.core.primary
};

var selectedStyles = exports.selectedStyles = {
  background: color.selected.backgroundColor,
  border: '1px solid ' + String(color.selected.borderColor),
  color: color.selected.color,

  hover: {
    background: color.selected.backgroundColor_hover,
    border: '1px solid ' + String(color.selected.borderColor),
    color: color.selected.color_active
  }
};

var defaultProps = {
  day: (0, _moment2['default'])(),
  daySize: _constants.DAY_SIZE,
  isOutsideDay: false,
  modifiers: new Set(),
  isFocused: false,
  tabIndex: -1,
  onDayClick: function () {
    function onDayClick() {}

    return onDayClick;
  }(),
  onDayMouseEnter: function () {
    function onDayMouseEnter() {}

    return onDayMouseEnter;
  }(),
  onDayMouseLeave: function () {
    function onDayMouseLeave() {}

    return onDayMouseLeave;
  }(),

  renderDayContents: null,
  ariaLabelFormat: 'dddd, LL',

  // style defaults
  defaultStyles: defaultStyles,
  outsideStyles: outsideStyles,
  todayStyles: {},
  highlightedCalendarStyles: highlightedCalendarStyles,
  blockedMinNightsStyles: blockedMinNightsStyles,
  blockedCalendarStyles: blockedCalendarStyles,
  blockedOutOfRangeStyles: blockedOutOfRangeStyles,
  hoveredSpanStyles: hoveredSpanStyles,
  selectedSpanStyles: selectedSpanStyles,
  lastInRangeStyles: lastInRangeStyles,
  selectedStyles: selectedStyles,
  selectedStartStyles: {},
  selectedEndStyles: {},
  afterHoveredStartStyles: {},
  firstDayOfWeekStyles: {},
  lastDayOfWeekStyles: {},

  // internationalization
  phrases: _defaultPhrases.CalendarDayPhrases
};

var CustomizableCalendarDay = function (_React$Component) {
  _inherits(CustomizableCalendarDay, _React$Component);

  function CustomizableCalendarDay() {
    var _ref;

    _classCallCheck(this, CustomizableCalendarDay);

    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    var _this = _possibleConstructorReturn(this, (_ref = CustomizableCalendarDay.__proto__ || Object.getPrototypeOf(CustomizableCalendarDay)).call.apply(_ref, [this].concat(args)));

    _this.state = {
      isHovered: false
    };

    _this.setButtonRef = _this.setButtonRef.bind(_this);
    return _this;
  }

  _createClass(CustomizableCalendarDay, [{
    key: 'shouldComponentUpdate',
    value: function () {
      function shouldComponentUpdate(nextProps, nextState) {
        return (0, _reactAddonsShallowCompare2['default'])(this, nextProps, nextState);
      }

      return shouldComponentUpdate;
    }()
  }, {
    key: 'componentDidUpdate',
    value: function () {
      function componentDidUpdate(prevProps) {
        var _props = this.props,
            isFocused = _props.isFocused,
            tabIndex = _props.tabIndex;

        if (tabIndex === 0) {
          if (isFocused || tabIndex !== prevProps.tabIndex) {
            this.buttonRef.focus();
          }
        }
      }

      return componentDidUpdate;
    }()
  }, {
    key: 'onDayClick',
    value: function () {
      function onDayClick(day, e) {
        var onDayClick = this.props.onDayClick;

        onDayClick(day, e);
      }

      return onDayClick;
    }()
  }, {
    key: 'onDayMouseEnter',
    value: function () {
      function onDayMouseEnter(day, e) {
        var onDayMouseEnter = this.props.onDayMouseEnter;

        this.setState({ isHovered: true });
        onDayMouseEnter(day, e);
      }

      return onDayMouseEnter;
    }()
  }, {
    key: 'onDayMouseLeave',
    value: function () {
      function onDayMouseLeave(day, e) {
        var onDayMouseLeave = this.props.onDayMouseLeave;

        this.setState({ isHovered: false });
        onDayMouseLeave(day, e);
      }

      return onDayMouseLeave;
    }()
  }, {
    key: 'onKeyDown',
    value: function () {
      function onKeyDown(day, e) {
        var onDayClick = this.props.onDayClick;
        var key = e.key;

        if (key === 'Enter' || key === ' ') {
          onDayClick(day, e);
        }
      }

      return onKeyDown;
    }()
  }, {
    key: 'setButtonRef',
    value: function () {
      function setButtonRef(ref) {
        this.buttonRef = ref;
      }

      return setButtonRef;
    }()
  }, {
    key: 'render',
    value: function () {
      function render() {
        var _this2 = this;

        var _props2 = this.props,
            day = _props2.day,
            ariaLabelFormat = _props2.ariaLabelFormat,
            daySize = _props2.daySize,
            isOutsideDay = _props2.isOutsideDay,
            modifiers = _props2.modifiers,
            tabIndex = _props2.tabIndex,
            renderDayContents = _props2.renderDayContents,
            styles = _props2.styles,
            phrases = _props2.phrases,
            defaultStylesWithHover = _props2.defaultStyles,
            outsideStylesWithHover = _props2.outsideStyles,
            todayStylesWithHover = _props2.todayStyles,
            firstDayOfWeekStylesWithHover = _props2.firstDayOfWeekStyles,
            lastDayOfWeekStylesWithHover = _props2.lastDayOfWeekStyles,
            highlightedCalendarStylesWithHover = _props2.highlightedCalendarStyles,
            blockedMinNightsStylesWithHover = _props2.blockedMinNightsStyles,
            blockedCalendarStylesWithHover = _props2.blockedCalendarStyles,
            blockedOutOfRangeStylesWithHover = _props2.blockedOutOfRangeStyles,
            hoveredSpanStylesWithHover = _props2.hoveredSpanStyles,
            selectedSpanStylesWithHover = _props2.selectedSpanStyles,
            lastInRangeStylesWithHover = _props2.lastInRangeStyles,
            selectedStylesWithHover = _props2.selectedStyles,
            selectedStartStylesWithHover = _props2.selectedStartStyles,
            selectedEndStylesWithHover = _props2.selectedEndStyles,
            afterHoveredStartStylesWithHover = _props2.afterHoveredStartStyles;
        var isHovered = this.state.isHovered;


        if (!day) return _react2['default'].createElement('td', null);

        var _getCalendarDaySettin = (0, _getCalendarDaySettings2['default'])(day, ariaLabelFormat, daySize, modifiers, phrases),
            daySizeStyles = _getCalendarDaySettin.daySizeStyles,
            useDefaultCursor = _getCalendarDaySettin.useDefaultCursor,
            selected = _getCalendarDaySettin.selected,
            hoveredSpan = _getCalendarDaySettin.hoveredSpan,
            isOutsideRange = _getCalendarDaySettin.isOutsideRange,
            ariaLabel = _getCalendarDaySettin.ariaLabel;

        return _react2['default'].createElement(
          'td',
          _extends({}, (0, _reactWithStyles.css)(styles.CalendarDay, useDefaultCursor && styles.CalendarDay__defaultCursor, daySizeStyles, getStyles(defaultStylesWithHover, isHovered), isOutsideDay && getStyles(outsideStylesWithHover, isHovered), modifiers.has('today') && getStyles(todayStylesWithHover, isHovered), modifiers.has('first-day-of-week') && getStyles(firstDayOfWeekStylesWithHover, isHovered), modifiers.has('last-day-of-week') && getStyles(lastDayOfWeekStylesWithHover, isHovered), modifiers.has('highlighted-calendar') && getStyles(highlightedCalendarStylesWithHover, isHovered), modifiers.has('blocked-minimum-nights') && getStyles(blockedMinNightsStylesWithHover, isHovered), modifiers.has('blocked-calendar') && getStyles(blockedCalendarStylesWithHover, isHovered), hoveredSpan && getStyles(hoveredSpanStylesWithHover, isHovered), modifiers.has('after-hovered-start') && getStyles(afterHoveredStartStylesWithHover, isHovered), modifiers.has('selected-span') && getStyles(selectedSpanStylesWithHover, isHovered), modifiers.has('last-in-range') && getStyles(lastInRangeStylesWithHover, isHovered), selected && getStyles(selectedStylesWithHover, isHovered), modifiers.has('selected-start') && getStyles(selectedStartStylesWithHover, isHovered), modifiers.has('selected-end') && getStyles(selectedEndStylesWithHover, isHovered), isOutsideRange && getStyles(blockedOutOfRangeStylesWithHover, isHovered)), {
            role: 'button' // eslint-disable-line jsx-a11y/no-noninteractive-element-to-interactive-role
            , ref: this.setButtonRef,
            'aria-label': ariaLabel,
            onMouseEnter: function () {
              function onMouseEnter(e) {
                _this2.onDayMouseEnter(day, e);
              }

              return onMouseEnter;
            }(),
            onMouseLeave: function () {
              function onMouseLeave(e) {
                _this2.onDayMouseLeave(day, e);
              }

              return onMouseLeave;
            }(),
            onMouseUp: function () {
              function onMouseUp(e) {
                e.currentTarget.blur();
              }

              return onMouseUp;
            }(),
            onClick: function () {
              function onClick(e) {
                _this2.onDayClick(day, e);
              }

              return onClick;
            }(),
            onKeyDown: function () {
              function onKeyDown(e) {
                _this2.onKeyDown(day, e);
              }

              return onKeyDown;
            }(),
            tabIndex: tabIndex
          }),
          renderDayContents ? renderDayContents(day, modifiers) : day.format('D')
        );
      }

      return render;
    }()
  }]);

  return CustomizableCalendarDay;
}(_react2['default'].Component);

CustomizableCalendarDay.propTypes = propTypes;
CustomizableCalendarDay.defaultProps = defaultProps;

exports.PureCustomizableCalendarDay = CustomizableCalendarDay;
exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref2) {
  var font = _ref2.reactDates.font;
  return {
    CalendarDay: {
      boxSizing: 'border-box',
      cursor: 'pointer',
      fontSize: font.size,
      textAlign: 'center',

      ':active': {
        outline: 0
      }
    },

    CalendarDay__defaultCursor: {
      cursor: 'default'
    }
  };
})(CustomizableCalendarDay);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPicker.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.PureDayPicker = exports.defaultProps = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactAddonsShallowCompare = __webpack_require__("../../node_modules/.pnpm/react-addons-shallow-compare@15.6.3/node_modules/react-addons-shallow-compare/index.js");

var _reactAddonsShallowCompare2 = _interopRequireDefault(_reactAddonsShallowCompare);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _throttle = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/throttle.js");

var _throttle2 = _interopRequireDefault(_throttle);

var _isTouchDevice = __webpack_require__("../../node_modules/.pnpm/is-touch-device@1.0.1/node_modules/is-touch-device/build/index.js");

var _isTouchDevice2 = _interopRequireDefault(_isTouchDevice);

var _reactOutsideClickHandler = __webpack_require__("../../node_modules/.pnpm/react-outside-click-handler@1.3.0_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-outside-click-handler/index.js");

var _reactOutsideClickHandler2 = _interopRequireDefault(_reactOutsideClickHandler);

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _CalendarMonthGrid = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CalendarMonthGrid.js");

var _CalendarMonthGrid2 = _interopRequireDefault(_CalendarMonthGrid);

var _DayPickerNavigation = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPickerNavigation.js");

var _DayPickerNavigation2 = _interopRequireDefault(_DayPickerNavigation);

var _DayPickerKeyboardShortcuts = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPickerKeyboardShortcuts.js");

var _DayPickerKeyboardShortcuts2 = _interopRequireDefault(_DayPickerKeyboardShortcuts);

var _getNumberOfCalendarMonthWeeks = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getNumberOfCalendarMonthWeeks.js");

var _getNumberOfCalendarMonthWeeks2 = _interopRequireDefault(_getNumberOfCalendarMonthWeeks);

var _getCalendarMonthWidth = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarMonthWidth.js");

var _getCalendarMonthWidth2 = _interopRequireDefault(_getCalendarMonthWidth);

var _calculateDimension = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/calculateDimension.js");

var _calculateDimension2 = _interopRequireDefault(_calculateDimension);

var _getActiveElement = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getActiveElement.js");

var _getActiveElement2 = _interopRequireDefault(_getActiveElement);

var _isDayVisible = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isDayVisible.js");

var _isDayVisible2 = _interopRequireDefault(_isDayVisible);

var _ModifiersShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ModifiersShape.js");

var _ModifiersShape2 = _interopRequireDefault(_ModifiersShape);

var _ScrollableOrientationShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ScrollableOrientationShape.js");

var _ScrollableOrientationShape2 = _interopRequireDefault(_ScrollableOrientationShape);

var _DayOfWeekShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/DayOfWeekShape.js");

var _DayOfWeekShape2 = _interopRequireDefault(_DayOfWeekShape);

var _CalendarInfoPositionShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/CalendarInfoPositionShape.js");

var _CalendarInfoPositionShape2 = _interopRequireDefault(_CalendarInfoPositionShape);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var MONTH_PADDING = 23;
var PREV_TRANSITION = 'prev';
var NEXT_TRANSITION = 'next';
var MONTH_SELECTION_TRANSITION = 'month_selection';
var YEAR_SELECTION_TRANSITION = 'year_selection';

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {

  // calendar presentation props
  enableOutsideDays: _propTypes2['default'].bool,
  numberOfMonths: _propTypes2['default'].number,
  orientation: _ScrollableOrientationShape2['default'],
  withPortal: _propTypes2['default'].bool,
  onOutsideClick: _propTypes2['default'].func,
  hidden: _propTypes2['default'].bool,
  initialVisibleMonth: _propTypes2['default'].func,
  firstDayOfWeek: _DayOfWeekShape2['default'],
  renderCalendarInfo: _propTypes2['default'].func,
  calendarInfoPosition: _CalendarInfoPositionShape2['default'],
  hideKeyboardShortcutsPanel: _propTypes2['default'].bool,
  daySize: _airbnbPropTypes.nonNegativeInteger,
  isRTL: _propTypes2['default'].bool,
  verticalHeight: _airbnbPropTypes.nonNegativeInteger,
  noBorder: _propTypes2['default'].bool,
  transitionDuration: _airbnbPropTypes.nonNegativeInteger,
  verticalBorderSpacing: _airbnbPropTypes.nonNegativeInteger,
  horizontalMonthPadding: _airbnbPropTypes.nonNegativeInteger,

  // navigation props
  navPrev: _propTypes2['default'].node,
  navNext: _propTypes2['default'].node,
  noNavButtons: _propTypes2['default'].bool,
  onPrevMonthClick: _propTypes2['default'].func,
  onNextMonthClick: _propTypes2['default'].func,
  onMonthChange: _propTypes2['default'].func,
  onYearChange: _propTypes2['default'].func,
  onMultiplyScrollableMonths: _propTypes2['default'].func, // VERTICAL_SCROLLABLE daypickers only

  // month props
  renderMonthText: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),
  renderMonthElement: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),

  // day props
  modifiers: _propTypes2['default'].objectOf(_propTypes2['default'].objectOf(_ModifiersShape2['default'])),
  renderCalendarDay: _propTypes2['default'].func,
  renderDayContents: _propTypes2['default'].func,
  onDayClick: _propTypes2['default'].func,
  onDayMouseEnter: _propTypes2['default'].func,
  onDayMouseLeave: _propTypes2['default'].func,

  // accessibility props
  isFocused: _propTypes2['default'].bool,
  getFirstFocusableDay: _propTypes2['default'].func,
  onBlur: _propTypes2['default'].func,
  showKeyboardShortcuts: _propTypes2['default'].bool,

  // internationalization
  monthFormat: _propTypes2['default'].string,
  weekDayFormat: _propTypes2['default'].string,
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.DayPickerPhrases)),
  dayAriaLabelFormat: _propTypes2['default'].string
}));

var defaultProps = exports.defaultProps = {
  // calendar presentation props
  enableOutsideDays: false,
  numberOfMonths: 2,
  orientation: _constants.HORIZONTAL_ORIENTATION,
  withPortal: false,
  onOutsideClick: function () {
    function onOutsideClick() {}

    return onOutsideClick;
  }(),

  hidden: false,
  initialVisibleMonth: function () {
    function initialVisibleMonth() {
      return (0, _moment2['default'])();
    }

    return initialVisibleMonth;
  }(),
  firstDayOfWeek: null,
  renderCalendarInfo: null,
  calendarInfoPosition: _constants.INFO_POSITION_BOTTOM,
  hideKeyboardShortcutsPanel: false,
  daySize: _constants.DAY_SIZE,
  isRTL: false,
  verticalHeight: null,
  noBorder: false,
  transitionDuration: undefined,
  verticalBorderSpacing: undefined,
  horizontalMonthPadding: 13,

  // navigation props
  navPrev: null,
  navNext: null,
  noNavButtons: false,
  onPrevMonthClick: function () {
    function onPrevMonthClick() {}

    return onPrevMonthClick;
  }(),
  onNextMonthClick: function () {
    function onNextMonthClick() {}

    return onNextMonthClick;
  }(),
  onMonthChange: function () {
    function onMonthChange() {}

    return onMonthChange;
  }(),
  onYearChange: function () {
    function onYearChange() {}

    return onYearChange;
  }(),
  onMultiplyScrollableMonths: function () {
    function onMultiplyScrollableMonths() {}

    return onMultiplyScrollableMonths;
  }(),


  // month props
  renderMonthText: null,
  renderMonthElement: null,

  // day props
  modifiers: {},
  renderCalendarDay: undefined,
  renderDayContents: null,
  onDayClick: function () {
    function onDayClick() {}

    return onDayClick;
  }(),
  onDayMouseEnter: function () {
    function onDayMouseEnter() {}

    return onDayMouseEnter;
  }(),
  onDayMouseLeave: function () {
    function onDayMouseLeave() {}

    return onDayMouseLeave;
  }(),


  // accessibility props
  isFocused: false,
  getFirstFocusableDay: null,
  onBlur: function () {
    function onBlur() {}

    return onBlur;
  }(),

  showKeyboardShortcuts: false,

  // internationalization
  monthFormat: 'MMMM YYYY',
  weekDayFormat: 'dd',
  phrases: _defaultPhrases.DayPickerPhrases,
  dayAriaLabelFormat: undefined
};

var DayPicker = function (_React$Component) {
  _inherits(DayPicker, _React$Component);

  function DayPicker(props) {
    _classCallCheck(this, DayPicker);

    var _this = _possibleConstructorReturn(this, (DayPicker.__proto__ || Object.getPrototypeOf(DayPicker)).call(this, props));

    var currentMonth = props.hidden ? (0, _moment2['default'])() : props.initialVisibleMonth();

    var focusedDate = currentMonth.clone().startOf('month');
    if (props.getFirstFocusableDay) {
      focusedDate = props.getFirstFocusableDay(currentMonth);
    }

    var horizontalMonthPadding = props.horizontalMonthPadding;


    var translationValue = props.isRTL && _this.isHorizontal() ? -(0, _getCalendarMonthWidth2['default'])(props.daySize, horizontalMonthPadding) : 0;

    _this.hasSetInitialVisibleMonth = !props.hidden;
    _this.state = {
      currentMonth: currentMonth,
      monthTransition: null,
      translationValue: translationValue,
      scrollableMonthMultiple: 1,
      calendarMonthWidth: (0, _getCalendarMonthWidth2['default'])(props.daySize, horizontalMonthPadding),
      focusedDate: !props.hidden || props.isFocused ? focusedDate : null,
      nextFocusedDate: null,
      showKeyboardShortcuts: props.showKeyboardShortcuts,
      onKeyboardShortcutsPanelClose: function () {
        function onKeyboardShortcutsPanelClose() {}

        return onKeyboardShortcutsPanelClose;
      }(),

      isTouchDevice: (0, _isTouchDevice2['default'])(),
      withMouseInteractions: true,
      calendarInfoWidth: 0,
      monthTitleHeight: null,
      hasSetHeight: false
    };

    _this.setCalendarMonthWeeks(currentMonth);

    _this.calendarMonthGridHeight = 0;
    _this.setCalendarInfoWidthTimeout = null;

    _this.onKeyDown = _this.onKeyDown.bind(_this);
    _this.throttledKeyDown = (0, _throttle2['default'])(_this.onFinalKeyDown, 200, { trailing: false });
    _this.onPrevMonthClick = _this.onPrevMonthClick.bind(_this);
    _this.onNextMonthClick = _this.onNextMonthClick.bind(_this);
    _this.onMonthChange = _this.onMonthChange.bind(_this);
    _this.onYearChange = _this.onYearChange.bind(_this);

    _this.multiplyScrollableMonths = _this.multiplyScrollableMonths.bind(_this);
    _this.updateStateAfterMonthTransition = _this.updateStateAfterMonthTransition.bind(_this);

    _this.openKeyboardShortcutsPanel = _this.openKeyboardShortcutsPanel.bind(_this);
    _this.closeKeyboardShortcutsPanel = _this.closeKeyboardShortcutsPanel.bind(_this);

    _this.setCalendarInfoRef = _this.setCalendarInfoRef.bind(_this);
    _this.setContainerRef = _this.setContainerRef.bind(_this);
    _this.setTransitionContainerRef = _this.setTransitionContainerRef.bind(_this);
    _this.setMonthTitleHeight = _this.setMonthTitleHeight.bind(_this);
    return _this;
  }

  _createClass(DayPicker, [{
    key: 'componentDidMount',
    value: function () {
      function componentDidMount() {
        var currentMonth = this.state.currentMonth;

        if (this.calendarInfo) {
          this.setState({
            isTouchDevice: (0, _isTouchDevice2['default'])(),
            calendarInfoWidth: (0, _calculateDimension2['default'])(this.calendarInfo, 'width', true, true)
          });
        } else {
          this.setState({ isTouchDevice: (0, _isTouchDevice2['default'])() });
        }

        this.setCalendarMonthWeeks(currentMonth);
      }

      return componentDidMount;
    }()
  }, {
    key: 'componentWillReceiveProps',
    value: function () {
      function componentWillReceiveProps(nextProps) {
        var hidden = nextProps.hidden,
            isFocused = nextProps.isFocused,
            showKeyboardShortcuts = nextProps.showKeyboardShortcuts,
            onBlur = nextProps.onBlur,
            renderMonthText = nextProps.renderMonthText,
            horizontalMonthPadding = nextProps.horizontalMonthPadding;
        var currentMonth = this.state.currentMonth;


        if (!hidden) {
          if (!this.hasSetInitialVisibleMonth) {
            this.hasSetInitialVisibleMonth = true;
            this.setState({
              currentMonth: nextProps.initialVisibleMonth()
            });
          }
        }

        var _props = this.props,
            daySize = _props.daySize,
            prevIsFocused = _props.isFocused,
            prevRenderMonthText = _props.renderMonthText;


        if (nextProps.daySize !== daySize) {
          this.setState({
            calendarMonthWidth: (0, _getCalendarMonthWidth2['default'])(nextProps.daySize, horizontalMonthPadding)
          });
        }

        if (isFocused !== prevIsFocused) {
          if (isFocused) {
            var focusedDate = this.getFocusedDay(currentMonth);

            var onKeyboardShortcutsPanelClose = this.state.onKeyboardShortcutsPanelClose;

            if (nextProps.showKeyboardShortcuts) {
              // the ? shortcut came from the input and we should return input there once it is close
              onKeyboardShortcutsPanelClose = onBlur;
            }

            this.setState({
              showKeyboardShortcuts: showKeyboardShortcuts,
              onKeyboardShortcutsPanelClose: onKeyboardShortcutsPanelClose,
              focusedDate: focusedDate,
              withMouseInteractions: false
            });
          } else {
            this.setState({ focusedDate: null });
          }
        }

        if (renderMonthText !== prevRenderMonthText) {
          this.setState({
            monthTitleHeight: null
          });
        }
      }

      return componentWillReceiveProps;
    }()
  }, {
    key: 'shouldComponentUpdate',
    value: function () {
      function shouldComponentUpdate(nextProps, nextState) {
        return (0, _reactAddonsShallowCompare2['default'])(this, nextProps, nextState);
      }

      return shouldComponentUpdate;
    }()
  }, {
    key: 'componentWillUpdate',
    value: function () {
      function componentWillUpdate() {
        var _this2 = this;

        var transitionDuration = this.props.transitionDuration;

        // Calculating the dimensions trigger a DOM repaint which
        // breaks the CSS transition.
        // The setTimeout will wait until the transition ends.

        if (this.calendarInfo) {
          this.setCalendarInfoWidthTimeout = setTimeout(function () {
            var calendarInfoWidth = _this2.state.calendarInfoWidth;

            var calendarInfoPanelWidth = (0, _calculateDimension2['default'])(_this2.calendarInfo, 'width', true, true);
            if (calendarInfoWidth !== calendarInfoPanelWidth) {
              _this2.setState({
                calendarInfoWidth: calendarInfoPanelWidth
              });
            }
          }, transitionDuration);
        }
      }

      return componentWillUpdate;
    }()
  }, {
    key: 'componentDidUpdate',
    value: function () {
      function componentDidUpdate(prevProps) {
        var _props2 = this.props,
            orientation = _props2.orientation,
            daySize = _props2.daySize,
            isFocused = _props2.isFocused,
            numberOfMonths = _props2.numberOfMonths;
        var _state = this.state,
            focusedDate = _state.focusedDate,
            monthTitleHeight = _state.monthTitleHeight;


        if (this.isHorizontal() && (orientation !== prevProps.orientation || daySize !== prevProps.daySize)) {
          var visibleCalendarWeeks = this.calendarMonthWeeks.slice(1, numberOfMonths + 1);
          var calendarMonthWeeksHeight = Math.max.apply(Math, [0].concat(_toConsumableArray(visibleCalendarWeeks))) * (daySize - 1);
          var newMonthHeight = monthTitleHeight + calendarMonthWeeksHeight + 1;
          this.adjustDayPickerHeight(newMonthHeight);
        }

        if (!prevProps.isFocused && isFocused && !focusedDate) {
          this.container.focus();
        }
      }

      return componentDidUpdate;
    }()
  }, {
    key: 'componentWillUnmount',
    value: function () {
      function componentWillUnmount() {
        clearTimeout(this.setCalendarInfoWidthTimeout);
      }

      return componentWillUnmount;
    }()
  }, {
    key: 'onKeyDown',
    value: function () {
      function onKeyDown(e) {
        e.stopPropagation();
        if (!_constants.MODIFIER_KEY_NAMES.has(e.key)) {
          this.throttledKeyDown(e);
        }
      }

      return onKeyDown;
    }()
  }, {
    key: 'onFinalKeyDown',
    value: function () {
      function onFinalKeyDown(e) {
        this.setState({ withMouseInteractions: false });

        var _props3 = this.props,
            onBlur = _props3.onBlur,
            isRTL = _props3.isRTL;
        var _state2 = this.state,
            focusedDate = _state2.focusedDate,
            showKeyboardShortcuts = _state2.showKeyboardShortcuts;

        if (!focusedDate) return;

        var newFocusedDate = focusedDate.clone();

        var didTransitionMonth = false;

        // focus might be anywhere when the keyboard shortcuts panel is opened so we want to
        // return it to wherever it was before when the panel was opened
        var activeElement = (0, _getActiveElement2['default'])();
        var onKeyboardShortcutsPanelClose = function () {
          function onKeyboardShortcutsPanelClose() {
            if (activeElement) activeElement.focus();
          }

          return onKeyboardShortcutsPanelClose;
        }();

        switch (e.key) {
          case 'ArrowUp':
            e.preventDefault();
            newFocusedDate.subtract(1, 'week');
            didTransitionMonth = this.maybeTransitionPrevMonth(newFocusedDate);
            break;
          case 'ArrowLeft':
            e.preventDefault();
            if (isRTL) {
              newFocusedDate.add(1, 'day');
            } else {
              newFocusedDate.subtract(1, 'day');
            }
            didTransitionMonth = this.maybeTransitionPrevMonth(newFocusedDate);
            break;
          case 'Home':
            e.preventDefault();
            newFocusedDate.startOf('week');
            didTransitionMonth = this.maybeTransitionPrevMonth(newFocusedDate);
            break;
          case 'PageUp':
            e.preventDefault();
            newFocusedDate.subtract(1, 'month');
            didTransitionMonth = this.maybeTransitionPrevMonth(newFocusedDate);
            break;

          case 'ArrowDown':
            e.preventDefault();
            newFocusedDate.add(1, 'week');
            didTransitionMonth = this.maybeTransitionNextMonth(newFocusedDate);
            break;
          case 'ArrowRight':
            e.preventDefault();
            if (isRTL) {
              newFocusedDate.subtract(1, 'day');
            } else {
              newFocusedDate.add(1, 'day');
            }
            didTransitionMonth = this.maybeTransitionNextMonth(newFocusedDate);
            break;
          case 'End':
            e.preventDefault();
            newFocusedDate.endOf('week');
            didTransitionMonth = this.maybeTransitionNextMonth(newFocusedDate);
            break;
          case 'PageDown':
            e.preventDefault();
            newFocusedDate.add(1, 'month');
            didTransitionMonth = this.maybeTransitionNextMonth(newFocusedDate);
            break;

          case '?':
            this.openKeyboardShortcutsPanel(onKeyboardShortcutsPanelClose);
            break;

          case 'Escape':
            if (showKeyboardShortcuts) {
              this.closeKeyboardShortcutsPanel();
            } else {
              onBlur();
            }
            break;

          default:
            break;
        }

        // If there was a month transition, do not update the focused date until the transition has
        // completed. Otherwise, attempting to focus on a DOM node may interrupt the CSS animation. If
        // didTransitionMonth is true, the focusedDate gets updated in #updateStateAfterMonthTransition
        if (!didTransitionMonth) {
          this.setState({
            focusedDate: newFocusedDate
          });
        }
      }

      return onFinalKeyDown;
    }()
  }, {
    key: 'onPrevMonthClick',
    value: function () {
      function onPrevMonthClick(nextFocusedDate, e) {
        var _props4 = this.props,
            daySize = _props4.daySize,
            isRTL = _props4.isRTL,
            numberOfMonths = _props4.numberOfMonths;
        var _state3 = this.state,
            calendarMonthWidth = _state3.calendarMonthWidth,
            monthTitleHeight = _state3.monthTitleHeight;


        if (e) e.preventDefault();

        var translationValue = void 0;
        if (this.isVertical()) {
          var calendarMonthWeeksHeight = this.calendarMonthWeeks[0] * (daySize - 1);
          translationValue = monthTitleHeight + calendarMonthWeeksHeight + 1;
        } else if (this.isHorizontal()) {
          translationValue = calendarMonthWidth;
          if (isRTL) {
            translationValue = -2 * calendarMonthWidth;
          }

          var visibleCalendarWeeks = this.calendarMonthWeeks.slice(0, numberOfMonths);
          var _calendarMonthWeeksHeight = Math.max.apply(Math, [0].concat(_toConsumableArray(visibleCalendarWeeks))) * (daySize - 1);
          var newMonthHeight = monthTitleHeight + _calendarMonthWeeksHeight + 1;
          this.adjustDayPickerHeight(newMonthHeight);
        }

        this.setState({
          monthTransition: PREV_TRANSITION,
          translationValue: translationValue,
          focusedDate: null,
          nextFocusedDate: nextFocusedDate
        });
      }

      return onPrevMonthClick;
    }()
  }, {
    key: 'onMonthChange',
    value: function () {
      function onMonthChange(currentMonth) {
        this.setCalendarMonthWeeks(currentMonth);
        this.calculateAndSetDayPickerHeight();

        // Translation value is a hack to force an invisible transition that
        // properly rerenders the CalendarMonthGrid
        this.setState({
          monthTransition: MONTH_SELECTION_TRANSITION,
          translationValue: 0.00001,
          focusedDate: null,
          nextFocusedDate: currentMonth,
          currentMonth: currentMonth
        });
      }

      return onMonthChange;
    }()
  }, {
    key: 'onYearChange',
    value: function () {
      function onYearChange(currentMonth) {
        this.setCalendarMonthWeeks(currentMonth);
        this.calculateAndSetDayPickerHeight();

        // Translation value is a hack to force an invisible transition that
        // properly rerenders the CalendarMonthGrid
        this.setState({
          monthTransition: YEAR_SELECTION_TRANSITION,
          translationValue: 0.0001,
          focusedDate: null,
          nextFocusedDate: currentMonth,
          currentMonth: currentMonth
        });
      }

      return onYearChange;
    }()
  }, {
    key: 'onNextMonthClick',
    value: function () {
      function onNextMonthClick(nextFocusedDate, e) {
        var _props5 = this.props,
            isRTL = _props5.isRTL,
            numberOfMonths = _props5.numberOfMonths,
            daySize = _props5.daySize;
        var _state4 = this.state,
            calendarMonthWidth = _state4.calendarMonthWidth,
            monthTitleHeight = _state4.monthTitleHeight;


        if (e) e.preventDefault();

        var translationValue = void 0;

        if (this.isVertical()) {
          var firstVisibleMonthWeeks = this.calendarMonthWeeks[1];
          var calendarMonthWeeksHeight = firstVisibleMonthWeeks * (daySize - 1);
          translationValue = -(monthTitleHeight + calendarMonthWeeksHeight + 1);
        }

        if (this.isHorizontal()) {
          translationValue = -calendarMonthWidth;
          if (isRTL) {
            translationValue = 0;
          }

          var visibleCalendarWeeks = this.calendarMonthWeeks.slice(2, numberOfMonths + 2);
          var _calendarMonthWeeksHeight2 = Math.max.apply(Math, [0].concat(_toConsumableArray(visibleCalendarWeeks))) * (daySize - 1);
          var newMonthHeight = monthTitleHeight + _calendarMonthWeeksHeight2 + 1;
          this.adjustDayPickerHeight(newMonthHeight);
        }

        this.setState({
          monthTransition: NEXT_TRANSITION,
          translationValue: translationValue,
          focusedDate: null,
          nextFocusedDate: nextFocusedDate
        });
      }

      return onNextMonthClick;
    }()
  }, {
    key: 'getFirstDayOfWeek',
    value: function () {
      function getFirstDayOfWeek() {
        var firstDayOfWeek = this.props.firstDayOfWeek;

        if (firstDayOfWeek == null) {
          return _moment2['default'].localeData().firstDayOfWeek();
        }

        return firstDayOfWeek;
      }

      return getFirstDayOfWeek;
    }()
  }, {
    key: 'getFirstVisibleIndex',
    value: function () {
      function getFirstVisibleIndex() {
        var orientation = this.props.orientation;
        var monthTransition = this.state.monthTransition;


        if (orientation === _constants.VERTICAL_SCROLLABLE) return 0;

        var firstVisibleMonthIndex = 1;
        if (monthTransition === PREV_TRANSITION) {
          firstVisibleMonthIndex -= 1;
        } else if (monthTransition === NEXT_TRANSITION) {
          firstVisibleMonthIndex += 1;
        }

        return firstVisibleMonthIndex;
      }

      return getFirstVisibleIndex;
    }()
  }, {
    key: 'getFocusedDay',
    value: function () {
      function getFocusedDay(newMonth) {
        var _props6 = this.props,
            getFirstFocusableDay = _props6.getFirstFocusableDay,
            numberOfMonths = _props6.numberOfMonths;


        var focusedDate = void 0;
        if (getFirstFocusableDay) {
          focusedDate = getFirstFocusableDay(newMonth);
        }

        if (newMonth && (!focusedDate || !(0, _isDayVisible2['default'])(focusedDate, newMonth, numberOfMonths))) {
          focusedDate = newMonth.clone().startOf('month');
        }

        return focusedDate;
      }

      return getFocusedDay;
    }()
  }, {
    key: 'setMonthTitleHeight',
    value: function () {
      function setMonthTitleHeight(monthTitleHeight) {
        var _this3 = this;

        this.setState({
          monthTitleHeight: monthTitleHeight
        }, function () {
          _this3.calculateAndSetDayPickerHeight();
        });
      }

      return setMonthTitleHeight;
    }()
  }, {
    key: 'setCalendarMonthWeeks',
    value: function () {
      function setCalendarMonthWeeks(currentMonth) {
        var numberOfMonths = this.props.numberOfMonths;


        this.calendarMonthWeeks = [];
        var month = currentMonth.clone().subtract(1, 'months');
        var firstDayOfWeek = this.getFirstDayOfWeek();
        for (var i = 0; i < numberOfMonths + 2; i += 1) {
          var numberOfWeeks = (0, _getNumberOfCalendarMonthWeeks2['default'])(month, firstDayOfWeek);
          this.calendarMonthWeeks.push(numberOfWeeks);
          month = month.add(1, 'months');
        }
      }

      return setCalendarMonthWeeks;
    }()
  }, {
    key: 'setContainerRef',
    value: function () {
      function setContainerRef(ref) {
        this.container = ref;
      }

      return setContainerRef;
    }()
  }, {
    key: 'setCalendarInfoRef',
    value: function () {
      function setCalendarInfoRef(ref) {
        this.calendarInfo = ref;
      }

      return setCalendarInfoRef;
    }()
  }, {
    key: 'setTransitionContainerRef',
    value: function () {
      function setTransitionContainerRef(ref) {
        this.transitionContainer = ref;
      }

      return setTransitionContainerRef;
    }()
  }, {
    key: 'maybeTransitionNextMonth',
    value: function () {
      function maybeTransitionNextMonth(newFocusedDate) {
        var numberOfMonths = this.props.numberOfMonths;
        var _state5 = this.state,
            currentMonth = _state5.currentMonth,
            focusedDate = _state5.focusedDate;


        var newFocusedDateMonth = newFocusedDate.month();
        var focusedDateMonth = focusedDate.month();
        var isNewFocusedDateVisible = (0, _isDayVisible2['default'])(newFocusedDate, currentMonth, numberOfMonths);
        if (newFocusedDateMonth !== focusedDateMonth && !isNewFocusedDateVisible) {
          this.onNextMonthClick(newFocusedDate);
          return true;
        }

        return false;
      }

      return maybeTransitionNextMonth;
    }()
  }, {
    key: 'maybeTransitionPrevMonth',
    value: function () {
      function maybeTransitionPrevMonth(newFocusedDate) {
        var numberOfMonths = this.props.numberOfMonths;
        var _state6 = this.state,
            currentMonth = _state6.currentMonth,
            focusedDate = _state6.focusedDate;


        var newFocusedDateMonth = newFocusedDate.month();
        var focusedDateMonth = focusedDate.month();
        var isNewFocusedDateVisible = (0, _isDayVisible2['default'])(newFocusedDate, currentMonth, numberOfMonths);
        if (newFocusedDateMonth !== focusedDateMonth && !isNewFocusedDateVisible) {
          this.onPrevMonthClick(newFocusedDate);
          return true;
        }

        return false;
      }

      return maybeTransitionPrevMonth;
    }()
  }, {
    key: 'multiplyScrollableMonths',
    value: function () {
      function multiplyScrollableMonths(e) {
        var onMultiplyScrollableMonths = this.props.onMultiplyScrollableMonths;

        if (e) e.preventDefault();

        if (onMultiplyScrollableMonths) onMultiplyScrollableMonths(e);

        this.setState(function (_ref) {
          var scrollableMonthMultiple = _ref.scrollableMonthMultiple;
          return {
            scrollableMonthMultiple: scrollableMonthMultiple + 1
          };
        });
      }

      return multiplyScrollableMonths;
    }()
  }, {
    key: 'isHorizontal',
    value: function () {
      function isHorizontal() {
        var orientation = this.props.orientation;

        return orientation === _constants.HORIZONTAL_ORIENTATION;
      }

      return isHorizontal;
    }()
  }, {
    key: 'isVertical',
    value: function () {
      function isVertical() {
        var orientation = this.props.orientation;

        return orientation === _constants.VERTICAL_ORIENTATION || orientation === _constants.VERTICAL_SCROLLABLE;
      }

      return isVertical;
    }()
  }, {
    key: 'updateStateAfterMonthTransition',
    value: function () {
      function updateStateAfterMonthTransition() {
        var _this4 = this;

        var _props7 = this.props,
            onPrevMonthClick = _props7.onPrevMonthClick,
            onNextMonthClick = _props7.onNextMonthClick,
            numberOfMonths = _props7.numberOfMonths,
            onMonthChange = _props7.onMonthChange,
            onYearChange = _props7.onYearChange,
            isRTL = _props7.isRTL;
        var _state7 = this.state,
            currentMonth = _state7.currentMonth,
            monthTransition = _state7.monthTransition,
            focusedDate = _state7.focusedDate,
            nextFocusedDate = _state7.nextFocusedDate,
            withMouseInteractions = _state7.withMouseInteractions,
            calendarMonthWidth = _state7.calendarMonthWidth;


        if (!monthTransition) return;

        var newMonth = currentMonth.clone();
        var firstDayOfWeek = this.getFirstDayOfWeek();
        if (monthTransition === PREV_TRANSITION) {
          newMonth.subtract(1, 'month');
          if (onPrevMonthClick) onPrevMonthClick(newMonth);
          var newInvisibleMonth = newMonth.clone().subtract(1, 'month');
          var numberOfWeeks = (0, _getNumberOfCalendarMonthWeeks2['default'])(newInvisibleMonth, firstDayOfWeek);
          this.calendarMonthWeeks = [numberOfWeeks].concat(_toConsumableArray(this.calendarMonthWeeks.slice(0, -1)));
        } else if (monthTransition === NEXT_TRANSITION) {
          newMonth.add(1, 'month');
          if (onNextMonthClick) onNextMonthClick(newMonth);
          var _newInvisibleMonth = newMonth.clone().add(numberOfMonths, 'month');
          var _numberOfWeeks = (0, _getNumberOfCalendarMonthWeeks2['default'])(_newInvisibleMonth, firstDayOfWeek);
          this.calendarMonthWeeks = [].concat(_toConsumableArray(this.calendarMonthWeeks.slice(1)), [_numberOfWeeks]);
        } else if (monthTransition === MONTH_SELECTION_TRANSITION) {
          if (onMonthChange) onMonthChange(newMonth);
        } else if (monthTransition === YEAR_SELECTION_TRANSITION) {
          if (onYearChange) onYearChange(newMonth);
        }

        var newFocusedDate = null;
        if (nextFocusedDate) {
          newFocusedDate = nextFocusedDate;
        } else if (!focusedDate && !withMouseInteractions) {
          newFocusedDate = this.getFocusedDay(newMonth);
        }

        this.setState({
          currentMonth: newMonth,
          monthTransition: null,
          translationValue: isRTL && this.isHorizontal() ? -calendarMonthWidth : 0,
          nextFocusedDate: null,
          focusedDate: newFocusedDate
        }, function () {
          // we don't want to focus on the relevant calendar day after a month transition
          // if the user is navigating around using a mouse
          if (withMouseInteractions) {
            var activeElement = (0, _getActiveElement2['default'])();
            if (activeElement && activeElement !== document.body && _this4.container.contains(activeElement)) {
              activeElement.blur();
            }
          }
        });
      }

      return updateStateAfterMonthTransition;
    }()
  }, {
    key: 'adjustDayPickerHeight',
    value: function () {
      function adjustDayPickerHeight(newMonthHeight) {
        var _this5 = this;

        var monthHeight = newMonthHeight + MONTH_PADDING;
        if (monthHeight !== this.calendarMonthGridHeight) {
          this.transitionContainer.style.height = String(monthHeight) + 'px';
          if (!this.calendarMonthGridHeight) {
            setTimeout(function () {
              _this5.setState({ hasSetHeight: true });
            }, 0);
          }
          this.calendarMonthGridHeight = monthHeight;
        }
      }

      return adjustDayPickerHeight;
    }()
  }, {
    key: 'calculateAndSetDayPickerHeight',
    value: function () {
      function calculateAndSetDayPickerHeight() {
        var _props8 = this.props,
            daySize = _props8.daySize,
            numberOfMonths = _props8.numberOfMonths;
        var monthTitleHeight = this.state.monthTitleHeight;


        var visibleCalendarWeeks = this.calendarMonthWeeks.slice(1, numberOfMonths + 1);
        var calendarMonthWeeksHeight = Math.max.apply(Math, [0].concat(_toConsumableArray(visibleCalendarWeeks))) * (daySize - 1);
        var newMonthHeight = monthTitleHeight + calendarMonthWeeksHeight + 1;

        if (this.isHorizontal()) {
          this.adjustDayPickerHeight(newMonthHeight);
        }
      }

      return calculateAndSetDayPickerHeight;
    }()
  }, {
    key: 'openKeyboardShortcutsPanel',
    value: function () {
      function openKeyboardShortcutsPanel(onCloseCallBack) {
        this.setState({
          showKeyboardShortcuts: true,
          onKeyboardShortcutsPanelClose: onCloseCallBack
        });
      }

      return openKeyboardShortcutsPanel;
    }()
  }, {
    key: 'closeKeyboardShortcutsPanel',
    value: function () {
      function closeKeyboardShortcutsPanel() {
        var onKeyboardShortcutsPanelClose = this.state.onKeyboardShortcutsPanelClose;


        if (onKeyboardShortcutsPanelClose) {
          onKeyboardShortcutsPanelClose();
        }

        this.setState({
          onKeyboardShortcutsPanelClose: null,
          showKeyboardShortcuts: false
        });
      }

      return closeKeyboardShortcutsPanel;
    }()
  }, {
    key: 'renderNavigation',
    value: function () {
      function renderNavigation() {
        var _this6 = this;

        var _props9 = this.props,
            navPrev = _props9.navPrev,
            navNext = _props9.navNext,
            noNavButtons = _props9.noNavButtons,
            orientation = _props9.orientation,
            phrases = _props9.phrases,
            isRTL = _props9.isRTL;


        if (noNavButtons) {
          return null;
        }

        var onNextMonthClick = void 0;
        if (orientation === _constants.VERTICAL_SCROLLABLE) {
          onNextMonthClick = this.multiplyScrollableMonths;
        } else {
          onNextMonthClick = function () {
            function onNextMonthClick(e) {
              _this6.onNextMonthClick(null, e);
            }

            return onNextMonthClick;
          }();
        }

        return _react2['default'].createElement(_DayPickerNavigation2['default'], {
          onPrevMonthClick: function () {
            function onPrevMonthClick(e) {
              _this6.onPrevMonthClick(null, e);
            }

            return onPrevMonthClick;
          }(),
          onNextMonthClick: onNextMonthClick,
          navPrev: navPrev,
          navNext: navNext,
          orientation: orientation,
          phrases: phrases,
          isRTL: isRTL
        });
      }

      return renderNavigation;
    }()
  }, {
    key: 'renderWeekHeader',
    value: function () {
      function renderWeekHeader(index) {
        var _props10 = this.props,
            daySize = _props10.daySize,
            horizontalMonthPadding = _props10.horizontalMonthPadding,
            orientation = _props10.orientation,
            weekDayFormat = _props10.weekDayFormat,
            styles = _props10.styles;
        var calendarMonthWidth = this.state.calendarMonthWidth;

        var verticalScrollable = orientation === _constants.VERTICAL_SCROLLABLE;
        var horizontalStyle = {
          left: index * calendarMonthWidth
        };
        var verticalStyle = {
          marginLeft: -calendarMonthWidth / 2
        };

        var weekHeaderStyle = {}; // no styles applied to the vertical-scrollable orientation
        if (this.isHorizontal()) {
          weekHeaderStyle = horizontalStyle;
        } else if (this.isVertical() && !verticalScrollable) {
          weekHeaderStyle = verticalStyle;
        }

        var firstDayOfWeek = this.getFirstDayOfWeek();

        var header = [];
        for (var i = 0; i < 7; i += 1) {
          header.push(_react2['default'].createElement(
            'li',
            _extends({ key: i }, (0, _reactWithStyles.css)(styles.DayPicker_weekHeader_li, { width: daySize })),
            _react2['default'].createElement(
              'small',
              null,
              (0, _moment2['default'])().day((i + firstDayOfWeek) % 7).format(weekDayFormat)
            )
          ));
        }

        return _react2['default'].createElement(
          'div',
          _extends({}, (0, _reactWithStyles.css)(styles.DayPicker_weekHeader, this.isVertical() && styles.DayPicker_weekHeader__vertical, verticalScrollable && styles.DayPicker_weekHeader__verticalScrollable, weekHeaderStyle, { padding: '0 ' + String(horizontalMonthPadding) + 'px' }), {
            key: 'week-' + String(index)
          }),
          _react2['default'].createElement(
            'ul',
            (0, _reactWithStyles.css)(styles.DayPicker_weekHeader_ul),
            header
          )
        );
      }

      return renderWeekHeader;
    }()
  }, {
    key: 'render',
    value: function () {
      function render() {
        var _this7 = this;

        var _state8 = this.state,
            calendarMonthWidth = _state8.calendarMonthWidth,
            currentMonth = _state8.currentMonth,
            monthTransition = _state8.monthTransition,
            translationValue = _state8.translationValue,
            scrollableMonthMultiple = _state8.scrollableMonthMultiple,
            focusedDate = _state8.focusedDate,
            showKeyboardShortcuts = _state8.showKeyboardShortcuts,
            isTouch = _state8.isTouchDevice,
            hasSetHeight = _state8.hasSetHeight,
            calendarInfoWidth = _state8.calendarInfoWidth,
            monthTitleHeight = _state8.monthTitleHeight;
        var _props11 = this.props,
            enableOutsideDays = _props11.enableOutsideDays,
            numberOfMonths = _props11.numberOfMonths,
            orientation = _props11.orientation,
            modifiers = _props11.modifiers,
            withPortal = _props11.withPortal,
            onDayClick = _props11.onDayClick,
            onDayMouseEnter = _props11.onDayMouseEnter,
            onDayMouseLeave = _props11.onDayMouseLeave,
            firstDayOfWeek = _props11.firstDayOfWeek,
            renderMonthText = _props11.renderMonthText,
            renderCalendarDay = _props11.renderCalendarDay,
            renderDayContents = _props11.renderDayContents,
            renderCalendarInfo = _props11.renderCalendarInfo,
            renderMonthElement = _props11.renderMonthElement,
            calendarInfoPosition = _props11.calendarInfoPosition,
            hideKeyboardShortcutsPanel = _props11.hideKeyboardShortcutsPanel,
            onOutsideClick = _props11.onOutsideClick,
            monthFormat = _props11.monthFormat,
            daySize = _props11.daySize,
            isFocused = _props11.isFocused,
            isRTL = _props11.isRTL,
            styles = _props11.styles,
            theme = _props11.theme,
            phrases = _props11.phrases,
            verticalHeight = _props11.verticalHeight,
            dayAriaLabelFormat = _props11.dayAriaLabelFormat,
            noBorder = _props11.noBorder,
            transitionDuration = _props11.transitionDuration,
            verticalBorderSpacing = _props11.verticalBorderSpacing,
            horizontalMonthPadding = _props11.horizontalMonthPadding;
        var dayPickerHorizontalPadding = theme.reactDates.spacing.dayPickerHorizontalPadding;


        var isHorizontal = this.isHorizontal();

        var numOfWeekHeaders = this.isVertical() ? 1 : numberOfMonths;
        var weekHeaders = [];
        for (var i = 0; i < numOfWeekHeaders; i += 1) {
          weekHeaders.push(this.renderWeekHeader(i));
        }

        var verticalScrollable = orientation === _constants.VERTICAL_SCROLLABLE;
        var height = void 0;
        if (isHorizontal) {
          height = this.calendarMonthGridHeight;
        } else if (this.isVertical() && !verticalScrollable && !withPortal) {
          // If the user doesn't set a desired height,
          // we default back to this kind of made-up value that generally looks good
          height = verticalHeight || 1.75 * calendarMonthWidth;
        }

        var isCalendarMonthGridAnimating = monthTransition !== null;

        var shouldFocusDate = !isCalendarMonthGridAnimating && isFocused;

        var keyboardShortcutButtonLocation = _DayPickerKeyboardShortcuts.BOTTOM_RIGHT;
        if (this.isVertical()) {
          keyboardShortcutButtonLocation = withPortal ? _DayPickerKeyboardShortcuts.TOP_LEFT : _DayPickerKeyboardShortcuts.TOP_RIGHT;
        }

        var shouldAnimateHeight = isHorizontal && hasSetHeight;

        var calendarInfoPositionTop = calendarInfoPosition === _constants.INFO_POSITION_TOP;
        var calendarInfoPositionBottom = calendarInfoPosition === _constants.INFO_POSITION_BOTTOM;
        var calendarInfoPositionBefore = calendarInfoPosition === _constants.INFO_POSITION_BEFORE;
        var calendarInfoPositionAfter = calendarInfoPosition === _constants.INFO_POSITION_AFTER;
        var calendarInfoIsInline = calendarInfoPositionBefore || calendarInfoPositionAfter;

        var calendarInfo = renderCalendarInfo && _react2['default'].createElement(
          'div',
          _extends({
            ref: this.setCalendarInfoRef
          }, (0, _reactWithStyles.css)(calendarInfoIsInline && styles.DayPicker_calendarInfo__horizontal)),
          renderCalendarInfo()
        );

        var calendarInfoPanelWidth = renderCalendarInfo && calendarInfoIsInline ? calendarInfoWidth : 0;

        var firstVisibleMonthIndex = this.getFirstVisibleIndex();
        var wrapperHorizontalWidth = calendarMonthWidth * numberOfMonths + 2 * dayPickerHorizontalPadding;
        // Adding `1px` because of whitespace between 2 inline-block
        var fullHorizontalWidth = wrapperHorizontalWidth + calendarInfoPanelWidth + 1;

        var transitionContainerStyle = {
          width: isHorizontal && wrapperHorizontalWidth,
          height: height
        };

        var dayPickerWrapperStyle = {
          width: isHorizontal && wrapperHorizontalWidth
        };

        var dayPickerStyle = {
          width: isHorizontal && fullHorizontalWidth,

          // These values are to center the datepicker (approximately) on the page
          marginLeft: isHorizontal && withPortal ? -fullHorizontalWidth / 2 : null,
          marginTop: isHorizontal && withPortal ? -calendarMonthWidth / 2 : null
        };

        return _react2['default'].createElement(
          'div',
          _extends({
            role: 'application',
            'aria-label': phrases.calendarLabel
          }, (0, _reactWithStyles.css)(styles.DayPicker, isHorizontal && styles.DayPicker__horizontal, verticalScrollable && styles.DayPicker__verticalScrollable, isHorizontal && withPortal && styles.DayPicker_portal__horizontal, this.isVertical() && withPortal && styles.DayPicker_portal__vertical, dayPickerStyle, !monthTitleHeight && styles.DayPicker__hidden, !noBorder && styles.DayPicker__withBorder)),
          _react2['default'].createElement(
            _reactOutsideClickHandler2['default'],
            { onOutsideClick: onOutsideClick },
            (calendarInfoPositionTop || calendarInfoPositionBefore) && calendarInfo,
            _react2['default'].createElement(
              'div',
              (0, _reactWithStyles.css)(dayPickerWrapperStyle, calendarInfoIsInline && isHorizontal && styles.DayPicker_wrapper__horizontal),
              _react2['default'].createElement(
                'div',
                _extends({}, (0, _reactWithStyles.css)(styles.DayPicker_weekHeaders, isHorizontal && styles.DayPicker_weekHeaders__horizontal), {
                  'aria-hidden': 'true',
                  role: 'presentation'
                }),
                weekHeaders
              ),
              _react2['default'].createElement(
                'div',
                _extends({}, (0, _reactWithStyles.css)(styles.DayPicker_focusRegion), {
                  ref: this.setContainerRef,
                  onClick: function () {
                    function onClick(e) {
                      e.stopPropagation();
                    }

                    return onClick;
                  }(),
                  onKeyDown: this.onKeyDown,
                  onMouseUp: function () {
                    function onMouseUp() {
                      _this7.setState({ withMouseInteractions: true });
                    }

                    return onMouseUp;
                  }(),
                  role: 'region',
                  tabIndex: -1
                }),
                !verticalScrollable && this.renderNavigation(),
                _react2['default'].createElement(
                  'div',
                  _extends({}, (0, _reactWithStyles.css)(styles.DayPicker_transitionContainer, shouldAnimateHeight && styles.DayPicker_transitionContainer__horizontal, this.isVertical() && styles.DayPicker_transitionContainer__vertical, verticalScrollable && styles.DayPicker_transitionContainer__verticalScrollable, transitionContainerStyle), {
                    ref: this.setTransitionContainerRef
                  }),
                  _react2['default'].createElement(_CalendarMonthGrid2['default'], {
                    setMonthTitleHeight: !monthTitleHeight ? this.setMonthTitleHeight : undefined,
                    translationValue: translationValue,
                    enableOutsideDays: enableOutsideDays,
                    firstVisibleMonthIndex: firstVisibleMonthIndex,
                    initialMonth: currentMonth,
                    isAnimating: isCalendarMonthGridAnimating,
                    modifiers: modifiers,
                    orientation: orientation,
                    numberOfMonths: numberOfMonths * scrollableMonthMultiple,
                    onDayClick: onDayClick,
                    onDayMouseEnter: onDayMouseEnter,
                    onDayMouseLeave: onDayMouseLeave,
                    onMonthChange: this.onMonthChange,
                    onYearChange: this.onYearChange,
                    renderMonthText: renderMonthText,
                    renderCalendarDay: renderCalendarDay,
                    renderDayContents: renderDayContents,
                    renderMonthElement: renderMonthElement,
                    onMonthTransitionEnd: this.updateStateAfterMonthTransition,
                    monthFormat: monthFormat,
                    daySize: daySize,
                    firstDayOfWeek: firstDayOfWeek,
                    isFocused: shouldFocusDate,
                    focusedDate: focusedDate,
                    phrases: phrases,
                    isRTL: isRTL,
                    dayAriaLabelFormat: dayAriaLabelFormat,
                    transitionDuration: transitionDuration,
                    verticalBorderSpacing: verticalBorderSpacing,
                    horizontalMonthPadding: horizontalMonthPadding
                  }),
                  verticalScrollable && this.renderNavigation()
                ),
                !isTouch && !hideKeyboardShortcutsPanel && _react2['default'].createElement(_DayPickerKeyboardShortcuts2['default'], {
                  block: this.isVertical() && !withPortal,
                  buttonLocation: keyboardShortcutButtonLocation,
                  showKeyboardShortcutsPanel: showKeyboardShortcuts,
                  openKeyboardShortcutsPanel: this.openKeyboardShortcutsPanel,
                  closeKeyboardShortcutsPanel: this.closeKeyboardShortcutsPanel,
                  phrases: phrases
                })
              )
            ),
            (calendarInfoPositionBottom || calendarInfoPositionAfter) && calendarInfo
          )
        );
      }

      return render;
    }()
  }]);

  return DayPicker;
}(_react2['default'].Component);

DayPicker.propTypes = propTypes;
DayPicker.defaultProps = defaultProps;

exports.PureDayPicker = DayPicker;
exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref2) {
  var _ref2$reactDates = _ref2.reactDates,
      color = _ref2$reactDates.color,
      font = _ref2$reactDates.font,
      noScrollBarOnVerticalScrollable = _ref2$reactDates.noScrollBarOnVerticalScrollable,
      spacing = _ref2$reactDates.spacing,
      zIndex = _ref2$reactDates.zIndex;
  return {
    DayPicker: {
      background: color.background,
      position: 'relative',
      textAlign: 'left'
    },

    DayPicker__horizontal: {
      background: color.background
    },

    DayPicker__verticalScrollable: {
      height: '100%'
    },

    DayPicker__hidden: {
      visibility: 'hidden'
    },

    DayPicker__withBorder: {
      boxShadow: '0 2px 6px rgba(0, 0, 0, 0.05), 0 0 0 1px rgba(0, 0, 0, 0.07)',
      borderRadius: 3
    },

    DayPicker_portal__horizontal: {
      boxShadow: 'none',
      position: 'absolute',
      left: '50%',
      top: '50%'
    },

    DayPicker_portal__vertical: {
      position: 'initial'
    },

    DayPicker_focusRegion: {
      outline: 'none'
    },

    DayPicker_calendarInfo__horizontal: {
      display: 'inline-block',
      verticalAlign: 'top'
    },

    DayPicker_wrapper__horizontal: {
      display: 'inline-block',
      verticalAlign: 'top'
    },

    DayPicker_weekHeaders: {
      position: 'relative'
    },

    DayPicker_weekHeaders__horizontal: {
      marginLeft: spacing.dayPickerHorizontalPadding
    },

    DayPicker_weekHeader: {
      color: color.placeholderText,
      position: 'absolute',
      top: 62,
      zIndex: zIndex + 2,
      textAlign: 'left'
    },

    DayPicker_weekHeader__vertical: {
      left: '50%'
    },

    DayPicker_weekHeader__verticalScrollable: {
      top: 0,
      display: 'table-row',
      borderBottom: '1px solid ' + String(color.core.border),
      background: color.background,
      marginLeft: 0,
      left: 0,
      width: '100%',
      textAlign: 'center'
    },

    DayPicker_weekHeader_ul: {
      listStyle: 'none',
      margin: '1px 0',
      paddingLeft: 0,
      paddingRight: 0,
      fontSize: font.size
    },

    DayPicker_weekHeader_li: {
      display: 'inline-block',
      textAlign: 'center'
    },

    DayPicker_transitionContainer: {
      position: 'relative',
      overflow: 'hidden',
      borderRadius: 3
    },

    DayPicker_transitionContainer__horizontal: {
      transition: 'height 0.2s ease-in-out'
    },

    DayPicker_transitionContainer__vertical: {
      width: '100%'
    },

    DayPicker_transitionContainer__verticalScrollable: (0, _object2['default'])({
      paddingTop: 20,
      height: '100%',
      position: 'absolute',
      top: 0,
      bottom: 0,
      right: 0,
      left: 0,
      overflowY: 'scroll'
    }, noScrollBarOnVerticalScrollable && {
      '-webkitOverflowScrolling': 'touch',
      '::-webkit-scrollbar': {
        '-webkit-appearance': 'none',
        display: 'none'
      }
    })
  };
})(DayPicker);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPickerKeyboardShortcuts.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.BOTTOM_RIGHT = exports.TOP_RIGHT = exports.TOP_LEFT = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _KeyboardShortcutRow = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/KeyboardShortcutRow.js");

var _KeyboardShortcutRow2 = _interopRequireDefault(_KeyboardShortcutRow);

var _CloseButton = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/CloseButton.js");

var _CloseButton2 = _interopRequireDefault(_CloseButton);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var TOP_LEFT = exports.TOP_LEFT = 'top-left';
var TOP_RIGHT = exports.TOP_RIGHT = 'top-right';
var BOTTOM_RIGHT = exports.BOTTOM_RIGHT = 'bottom-right';

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {
  block: _propTypes2['default'].bool,
  buttonLocation: _propTypes2['default'].oneOf([TOP_LEFT, TOP_RIGHT, BOTTOM_RIGHT]),
  showKeyboardShortcutsPanel: _propTypes2['default'].bool,
  openKeyboardShortcutsPanel: _propTypes2['default'].func,
  closeKeyboardShortcutsPanel: _propTypes2['default'].func,
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.DayPickerKeyboardShortcutsPhrases))
}));

var defaultProps = {
  block: false,
  buttonLocation: BOTTOM_RIGHT,
  showKeyboardShortcutsPanel: false,
  openKeyboardShortcutsPanel: function () {
    function openKeyboardShortcutsPanel() {}

    return openKeyboardShortcutsPanel;
  }(),
  closeKeyboardShortcutsPanel: function () {
    function closeKeyboardShortcutsPanel() {}

    return closeKeyboardShortcutsPanel;
  }(),

  phrases: _defaultPhrases.DayPickerKeyboardShortcutsPhrases
};

function getKeyboardShortcuts(phrases) {
  return [{
    unicode: '↵',
    label: phrases.enterKey,
    action: phrases.selectFocusedDate
  }, {
    unicode: '←/→',
    label: phrases.leftArrowRightArrow,
    action: phrases.moveFocusByOneDay
  }, {
    unicode: '↑/↓',
    label: phrases.upArrowDownArrow,
    action: phrases.moveFocusByOneWeek
  }, {
    unicode: 'PgUp/PgDn',
    label: phrases.pageUpPageDown,
    action: phrases.moveFocusByOneMonth
  }, {
    unicode: 'Home/End',
    label: phrases.homeEnd,
    action: phrases.moveFocustoStartAndEndOfWeek
  }, {
    unicode: 'Esc',
    label: phrases.escape,
    action: phrases.returnFocusToInput
  }, {
    unicode: '?',
    label: phrases.questionMark,
    action: phrases.openThisPanel
  }];
}

var DayPickerKeyboardShortcuts = function (_React$Component) {
  _inherits(DayPickerKeyboardShortcuts, _React$Component);

  function DayPickerKeyboardShortcuts() {
    var _ref;

    _classCallCheck(this, DayPickerKeyboardShortcuts);

    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    var _this = _possibleConstructorReturn(this, (_ref = DayPickerKeyboardShortcuts.__proto__ || Object.getPrototypeOf(DayPickerKeyboardShortcuts)).call.apply(_ref, [this].concat(args)));

    var phrases = _this.props.phrases;

    _this.keyboardShortcuts = getKeyboardShortcuts(phrases);

    _this.onShowKeyboardShortcutsButtonClick = _this.onShowKeyboardShortcutsButtonClick.bind(_this);
    _this.setShowKeyboardShortcutsButtonRef = _this.setShowKeyboardShortcutsButtonRef.bind(_this);
    _this.setHideKeyboardShortcutsButtonRef = _this.setHideKeyboardShortcutsButtonRef.bind(_this);
    _this.handleFocus = _this.handleFocus.bind(_this);
    _this.onKeyDown = _this.onKeyDown.bind(_this);
    return _this;
  }

  _createClass(DayPickerKeyboardShortcuts, [{
    key: 'componentWillReceiveProps',
    value: function () {
      function componentWillReceiveProps(nextProps) {
        var phrases = this.props.phrases;

        if (nextProps.phrases !== phrases) {
          this.keyboardShortcuts = getKeyboardShortcuts(nextProps.phrases);
        }
      }

      return componentWillReceiveProps;
    }()
  }, {
    key: 'componentDidUpdate',
    value: function () {
      function componentDidUpdate() {
        this.handleFocus();
      }

      return componentDidUpdate;
    }()
  }, {
    key: 'onKeyDown',
    value: function () {
      function onKeyDown(e) {
        e.stopPropagation();

        var closeKeyboardShortcutsPanel = this.props.closeKeyboardShortcutsPanel;
        // Because the close button is the only focusable element inside of the panel, this
        // amounts to a very basic focus trap. The user can exit the panel by "pressing" the
        // close button or hitting escape

        switch (e.key) {
          case 'Enter':
          case ' ':
          case 'Spacebar': // for older browsers
          case 'Escape':
            closeKeyboardShortcutsPanel();
            break;

          // do nothing - this allows the up and down arrows continue their
          // default behavior of scrolling the content of the Keyboard Shortcuts Panel
          // which is needed when only a single month is shown for instance.
          case 'ArrowUp':
          case 'ArrowDown':
            break;

          // completely block the rest of the keys that have functionality outside of this panel
          case 'Tab':
          case 'Home':
          case 'End':
          case 'PageUp':
          case 'PageDown':
          case 'ArrowLeft':
          case 'ArrowRight':
            e.preventDefault();
            break;

          default:
            break;
        }
      }

      return onKeyDown;
    }()
  }, {
    key: 'onShowKeyboardShortcutsButtonClick',
    value: function () {
      function onShowKeyboardShortcutsButtonClick() {
        var _this2 = this;

        var openKeyboardShortcutsPanel = this.props.openKeyboardShortcutsPanel;

        // we want to return focus to this button after closing the keyboard shortcuts panel

        openKeyboardShortcutsPanel(function () {
          _this2.showKeyboardShortcutsButton.focus();
        });
      }

      return onShowKeyboardShortcutsButtonClick;
    }()
  }, {
    key: 'setShowKeyboardShortcutsButtonRef',
    value: function () {
      function setShowKeyboardShortcutsButtonRef(ref) {
        this.showKeyboardShortcutsButton = ref;
      }

      return setShowKeyboardShortcutsButtonRef;
    }()
  }, {
    key: 'setHideKeyboardShortcutsButtonRef',
    value: function () {
      function setHideKeyboardShortcutsButtonRef(ref) {
        this.hideKeyboardShortcutsButton = ref;
      }

      return setHideKeyboardShortcutsButtonRef;
    }()
  }, {
    key: 'handleFocus',
    value: function () {
      function handleFocus() {
        if (this.hideKeyboardShortcutsButton) {
          // automatically move focus into the dialog by moving
          // to the only interactive element, the hide button
          this.hideKeyboardShortcutsButton.focus();
        }
      }

      return handleFocus;
    }()
  }, {
    key: 'render',
    value: function () {
      function render() {
        var _this3 = this;

        var _props = this.props,
            block = _props.block,
            buttonLocation = _props.buttonLocation,
            showKeyboardShortcutsPanel = _props.showKeyboardShortcutsPanel,
            closeKeyboardShortcutsPanel = _props.closeKeyboardShortcutsPanel,
            styles = _props.styles,
            phrases = _props.phrases;


        var toggleButtonText = showKeyboardShortcutsPanel ? phrases.hideKeyboardShortcutsPanel : phrases.showKeyboardShortcutsPanel;

        var bottomRight = buttonLocation === BOTTOM_RIGHT;
        var topRight = buttonLocation === TOP_RIGHT;
        var topLeft = buttonLocation === TOP_LEFT;

        return _react2['default'].createElement(
          'div',
          null,
          _react2['default'].createElement(
            'button',
            _extends({
              ref: this.setShowKeyboardShortcutsButtonRef
            }, (0, _reactWithStyles.css)(styles.DayPickerKeyboardShortcuts_buttonReset, styles.DayPickerKeyboardShortcuts_show, bottomRight && styles.DayPickerKeyboardShortcuts_show__bottomRight, topRight && styles.DayPickerKeyboardShortcuts_show__topRight, topLeft && styles.DayPickerKeyboardShortcuts_show__topLeft), {
              type: 'button',
              'aria-label': toggleButtonText,
              onClick: this.onShowKeyboardShortcutsButtonClick,
              onKeyDown: function () {
                function onKeyDown(e) {
                  if (e.key === 'Enter') {
                    e.preventDefault();
                  } else if (e.key === 'Space') {
                    _this3.onShowKeyboardShortcutsButtonClick(e);
                  }
                }

                return onKeyDown;
              }(),
              onMouseUp: function () {
                function onMouseUp(e) {
                  e.currentTarget.blur();
                }

                return onMouseUp;
              }()
            }),
            _react2['default'].createElement(
              'span',
              (0, _reactWithStyles.css)(styles.DayPickerKeyboardShortcuts_showSpan, bottomRight && styles.DayPickerKeyboardShortcuts_showSpan__bottomRight, topRight && styles.DayPickerKeyboardShortcuts_showSpan__topRight, topLeft && styles.DayPickerKeyboardShortcuts_showSpan__topLeft),
              '?'
            )
          ),
          showKeyboardShortcutsPanel && _react2['default'].createElement(
            'div',
            _extends({}, (0, _reactWithStyles.css)(styles.DayPickerKeyboardShortcuts_panel), {
              role: 'dialog',
              'aria-labelledby': 'DayPickerKeyboardShortcuts_title',
              'aria-describedby': 'DayPickerKeyboardShortcuts_description'
            }),
            _react2['default'].createElement(
              'div',
              _extends({}, (0, _reactWithStyles.css)(styles.DayPickerKeyboardShortcuts_title), {
                id: 'DayPickerKeyboardShortcuts_title'
              }),
              phrases.keyboardShortcuts
            ),
            _react2['default'].createElement(
              'button',
              _extends({
                ref: this.setHideKeyboardShortcutsButtonRef
              }, (0, _reactWithStyles.css)(styles.DayPickerKeyboardShortcuts_buttonReset, styles.DayPickerKeyboardShortcuts_close), {
                type: 'button',
                tabIndex: '0',
                'aria-label': phrases.hideKeyboardShortcutsPanel,
                onClick: closeKeyboardShortcutsPanel,
                onKeyDown: this.onKeyDown
              }),
              _react2['default'].createElement(_CloseButton2['default'], (0, _reactWithStyles.css)(styles.DayPickerKeyboardShortcuts_closeSvg))
            ),
            _react2['default'].createElement(
              'ul',
              _extends({}, (0, _reactWithStyles.css)(styles.DayPickerKeyboardShortcuts_list), {
                id: 'DayPickerKeyboardShortcuts_description'
              }),
              this.keyboardShortcuts.map(function (_ref2) {
                var unicode = _ref2.unicode,
                    label = _ref2.label,
                    action = _ref2.action;
                return _react2['default'].createElement(_KeyboardShortcutRow2['default'], {
                  key: label,
                  unicode: unicode,
                  label: label,
                  action: action,
                  block: block
                });
              })
            )
          )
        );
      }

      return render;
    }()
  }]);

  return DayPickerKeyboardShortcuts;
}(_react2['default'].Component);

DayPickerKeyboardShortcuts.propTypes = propTypes;
DayPickerKeyboardShortcuts.defaultProps = defaultProps;

exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref3) {
  var _ref3$reactDates = _ref3.reactDates,
      color = _ref3$reactDates.color,
      font = _ref3$reactDates.font,
      zIndex = _ref3$reactDates.zIndex;
  return {
    DayPickerKeyboardShortcuts_buttonReset: {
      background: 'none',
      border: 0,
      borderRadius: 0,
      color: 'inherit',
      font: 'inherit',
      lineHeight: 'normal',
      overflow: 'visible',
      padding: 0,
      cursor: 'pointer',
      fontSize: font.size,

      ':active': {
        outline: 'none'
      }
    },

    DayPickerKeyboardShortcuts_show: {
      width: 22,
      position: 'absolute',
      zIndex: zIndex + 2
    },

    DayPickerKeyboardShortcuts_show__bottomRight: {
      borderTop: '26px solid transparent',
      borderRight: '33px solid ' + String(color.core.primary),
      bottom: 0,
      right: 0,

      ':hover': {
        borderRight: '33px solid ' + String(color.core.primary_dark)
      }
    },

    DayPickerKeyboardShortcuts_show__topRight: {
      borderBottom: '26px solid transparent',
      borderRight: '33px solid ' + String(color.core.primary),
      top: 0,
      right: 0,

      ':hover': {
        borderRight: '33px solid ' + String(color.core.primary_dark)
      }
    },

    DayPickerKeyboardShortcuts_show__topLeft: {
      borderBottom: '26px solid transparent',
      borderLeft: '33px solid ' + String(color.core.primary),
      top: 0,
      left: 0,

      ':hover': {
        borderLeft: '33px solid ' + String(color.core.primary_dark)
      }
    },

    DayPickerKeyboardShortcuts_showSpan: {
      color: color.core.white,
      position: 'absolute'
    },

    DayPickerKeyboardShortcuts_showSpan__bottomRight: {
      bottom: 0,
      right: -28
    },

    DayPickerKeyboardShortcuts_showSpan__topRight: {
      top: 1,
      right: -28
    },

    DayPickerKeyboardShortcuts_showSpan__topLeft: {
      top: 1,
      left: -28
    },

    DayPickerKeyboardShortcuts_panel: {
      overflow: 'auto',
      background: color.background,
      border: '1px solid ' + String(color.core.border),
      borderRadius: 2,
      position: 'absolute',
      top: 0,
      bottom: 0,
      right: 0,
      left: 0,
      zIndex: zIndex + 2,
      padding: 22,
      margin: 33
    },

    DayPickerKeyboardShortcuts_title: {
      fontSize: 16,
      fontWeight: 'bold',
      margin: 0
    },

    DayPickerKeyboardShortcuts_list: {
      listStyle: 'none',
      padding: 0,
      fontSize: font.size
    },

    DayPickerKeyboardShortcuts_close: {
      position: 'absolute',
      right: 22,
      top: 22,
      zIndex: zIndex + 2,

      ':active': {
        outline: 'none'
      }
    },

    DayPickerKeyboardShortcuts_closeSvg: {
      height: 15,
      width: 15,
      fill: color.core.grayLighter,

      ':hover': {
        fill: color.core.grayLight
      },

      ':focus': {
        fill: color.core.grayLight
      }
    }
  };
})(DayPickerKeyboardShortcuts);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPickerNavigation.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _LeftArrow = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/LeftArrow.js");

var _LeftArrow2 = _interopRequireDefault(_LeftArrow);

var _RightArrow = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/RightArrow.js");

var _RightArrow2 = _interopRequireDefault(_RightArrow);

var _ChevronUp = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/ChevronUp.js");

var _ChevronUp2 = _interopRequireDefault(_ChevronUp);

var _ChevronDown = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/ChevronDown.js");

var _ChevronDown2 = _interopRequireDefault(_ChevronDown);

var _ScrollableOrientationShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ScrollableOrientationShape.js");

var _ScrollableOrientationShape2 = _interopRequireDefault(_ScrollableOrientationShape);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {
  navPrev: _propTypes2['default'].node,
  navNext: _propTypes2['default'].node,
  orientation: _ScrollableOrientationShape2['default'],

  onPrevMonthClick: _propTypes2['default'].func,
  onNextMonthClick: _propTypes2['default'].func,

  // internationalization
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.DayPickerNavigationPhrases)),

  isRTL: _propTypes2['default'].bool
}));

var defaultProps = {
  navPrev: null,
  navNext: null,
  orientation: _constants.HORIZONTAL_ORIENTATION,

  onPrevMonthClick: function () {
    function onPrevMonthClick() {}

    return onPrevMonthClick;
  }(),
  onNextMonthClick: function () {
    function onNextMonthClick() {}

    return onNextMonthClick;
  }(),


  // internationalization
  phrases: _defaultPhrases.DayPickerNavigationPhrases,
  isRTL: false
};

function DayPickerNavigation(_ref) {
  var navPrev = _ref.navPrev,
      navNext = _ref.navNext,
      onPrevMonthClick = _ref.onPrevMonthClick,
      onNextMonthClick = _ref.onNextMonthClick,
      orientation = _ref.orientation,
      phrases = _ref.phrases,
      isRTL = _ref.isRTL,
      styles = _ref.styles;

  var isHorizontal = orientation === _constants.HORIZONTAL_ORIENTATION;
  var isVertical = orientation !== _constants.HORIZONTAL_ORIENTATION;
  var isVerticalScrollable = orientation === _constants.VERTICAL_SCROLLABLE;

  var navPrevIcon = navPrev;
  var navNextIcon = navNext;
  var isDefaultNavPrev = false;
  var isDefaultNavNext = false;
  if (!navPrevIcon) {
    isDefaultNavPrev = true;
    var Icon = isVertical ? _ChevronUp2['default'] : _LeftArrow2['default'];
    if (isRTL && !isVertical) {
      Icon = _RightArrow2['default'];
    }
    navPrevIcon = _react2['default'].createElement(Icon, (0, _reactWithStyles.css)(isHorizontal && styles.DayPickerNavigation_svg__horizontal, isVertical && styles.DayPickerNavigation_svg__vertical));
  }

  if (!navNextIcon) {
    isDefaultNavNext = true;
    var _Icon = isVertical ? _ChevronDown2['default'] : _RightArrow2['default'];
    if (isRTL && !isVertical) {
      _Icon = _LeftArrow2['default'];
    }
    navNextIcon = _react2['default'].createElement(_Icon, (0, _reactWithStyles.css)(isHorizontal && styles.DayPickerNavigation_svg__horizontal, isVertical && styles.DayPickerNavigation_svg__vertical));
  }

  var isDefaultNav = isVerticalScrollable ? isDefaultNavNext : isDefaultNavNext || isDefaultNavPrev;

  return _react2['default'].createElement(
    'div',
    _reactWithStyles.css.apply(undefined, [styles.DayPickerNavigation, isHorizontal && styles.DayPickerNavigation__horizontal].concat(_toConsumableArray(isVertical && [styles.DayPickerNavigation__vertical, isDefaultNav && styles.DayPickerNavigation__verticalDefault]), _toConsumableArray(isVerticalScrollable && [styles.DayPickerNavigation__verticalScrollable, isDefaultNav && styles.DayPickerNavigation__verticalScrollableDefault]))),
    !isVerticalScrollable && _react2['default'].createElement(
      'div',
      _extends({
        role: 'button',
        tabIndex: '0'
      }, _reactWithStyles.css.apply(undefined, [styles.DayPickerNavigation_button, isDefaultNavPrev && styles.DayPickerNavigation_button__default].concat(_toConsumableArray(isHorizontal && [styles.DayPickerNavigation_button__horizontal].concat(_toConsumableArray(isDefaultNavPrev && [styles.DayPickerNavigation_button__horizontalDefault, !isRTL && styles.DayPickerNavigation_leftButton__horizontalDefault, isRTL && styles.DayPickerNavigation_rightButton__horizontalDefault]))), _toConsumableArray(isVertical && [styles.DayPickerNavigation_button__vertical].concat(_toConsumableArray(isDefaultNavPrev && [styles.DayPickerNavigation_button__verticalDefault, styles.DayPickerNavigation_prevButton__verticalDefault]))))), {
        'aria-label': phrases.jumpToPrevMonth,
        onClick: onPrevMonthClick,
        onKeyUp: function () {
          function onKeyUp(e) {
            var key = e.key;

            if (key === 'Enter' || key === ' ') onPrevMonthClick(e);
          }

          return onKeyUp;
        }(),
        onMouseUp: function () {
          function onMouseUp(e) {
            e.currentTarget.blur();
          }

          return onMouseUp;
        }()
      }),
      navPrevIcon
    ),
    _react2['default'].createElement(
      'div',
      _extends({
        role: 'button',
        tabIndex: '0'
      }, _reactWithStyles.css.apply(undefined, [styles.DayPickerNavigation_button, isDefaultNavNext && styles.DayPickerNavigation_button__default].concat(_toConsumableArray(isHorizontal && [styles.DayPickerNavigation_button__horizontal].concat(_toConsumableArray(isDefaultNavNext && [styles.DayPickerNavigation_button__horizontalDefault, isRTL && styles.DayPickerNavigation_leftButton__horizontalDefault, !isRTL && styles.DayPickerNavigation_rightButton__horizontalDefault]))), _toConsumableArray(isVertical && [styles.DayPickerNavigation_button__vertical, styles.DayPickerNavigation_nextButton__vertical].concat(_toConsumableArray(isDefaultNavNext && [styles.DayPickerNavigation_button__verticalDefault, styles.DayPickerNavigation_nextButton__verticalDefault, isVerticalScrollable && styles.DayPickerNavigation_nextButton__verticalScrollableDefault]))))), {
        'aria-label': phrases.jumpToNextMonth,
        onClick: onNextMonthClick,
        onKeyUp: function () {
          function onKeyUp(e) {
            var key = e.key;

            if (key === 'Enter' || key === ' ') onNextMonthClick(e);
          }

          return onKeyUp;
        }(),
        onMouseUp: function () {
          function onMouseUp(e) {
            e.currentTarget.blur();
          }

          return onMouseUp;
        }()
      }),
      navNextIcon
    )
  );
}

DayPickerNavigation.propTypes = propTypes;
DayPickerNavigation.defaultProps = defaultProps;

exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref2) {
  var _ref2$reactDates = _ref2.reactDates,
      color = _ref2$reactDates.color,
      zIndex = _ref2$reactDates.zIndex;
  return {
    DayPickerNavigation: {
      position: 'relative',
      zIndex: zIndex + 2
    },

    DayPickerNavigation__horizontal: {
      height: 0
    },

    DayPickerNavigation__vertical: {},
    DayPickerNavigation__verticalScrollable: {},

    DayPickerNavigation__verticalDefault: {
      position: 'absolute',
      width: '100%',
      height: 52,
      bottom: 0,
      left: 0
    },

    DayPickerNavigation__verticalScrollableDefault: {
      position: 'relative'
    },

    DayPickerNavigation_button: {
      cursor: 'pointer',
      userSelect: 'none',
      border: 0,
      padding: 0,
      margin: 0
    },

    DayPickerNavigation_button__default: {
      border: '1px solid ' + String(color.core.borderLight),
      backgroundColor: color.background,
      color: color.placeholderText,

      ':focus': {
        border: '1px solid ' + String(color.core.borderMedium)
      },

      ':hover': {
        border: '1px solid ' + String(color.core.borderMedium)
      },

      ':active': {
        background: color.backgroundDark
      }
    },

    DayPickerNavigation_button__horizontal: {},

    DayPickerNavigation_button__horizontalDefault: {
      position: 'absolute',
      top: 18,
      lineHeight: 0.78,
      borderRadius: 3,
      padding: '6px 9px'
    },

    DayPickerNavigation_leftButton__horizontalDefault: {
      left: 22
    },

    DayPickerNavigation_rightButton__horizontalDefault: {
      right: 22
    },

    DayPickerNavigation_button__vertical: {},

    DayPickerNavigation_button__verticalDefault: {
      padding: 5,
      background: color.background,
      boxShadow: '0 0 5px 2px rgba(0, 0, 0, 0.1)',
      position: 'relative',
      display: 'inline-block',
      height: '100%',
      width: '50%'
    },

    DayPickerNavigation_prevButton__verticalDefault: {},

    DayPickerNavigation_nextButton__verticalDefault: {
      borderLeft: 0
    },

    DayPickerNavigation_nextButton__verticalScrollableDefault: {
      width: '100%'
    },

    DayPickerNavigation_svg__horizontal: {
      height: 19,
      width: 19,
      fill: color.core.grayLight,
      display: 'block'
    },

    DayPickerNavigation_svg__vertical: {
      height: 42,
      width: 42,
      fill: color.text,
      display: 'block'
    }
  };
})(DayPickerNavigation);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPickerSingleDateController.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;


__webpack_unused_export__ = ({
  value: true
});

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _reactMomentProptypes = __webpack_require__("../../node_modules/.pnpm/react-moment-proptypes@1.8.1_moment@2.29.4/node_modules/react-moment-proptypes/src/index.js");

var _reactMomentProptypes2 = _interopRequireDefault(_reactMomentProptypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _object3 = __webpack_require__("../../node_modules/.pnpm/object.values@1.1.7/node_modules/object.values/index.js");

var _object4 = _interopRequireDefault(_object3);

var _isTouchDevice = __webpack_require__("../../node_modules/.pnpm/is-touch-device@1.0.1/node_modules/is-touch-device/build/index.js");

var _isTouchDevice2 = _interopRequireDefault(_isTouchDevice);

var _defaultPhrases = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js");

var _getPhrasePropTypes = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js");

var _getPhrasePropTypes2 = _interopRequireDefault(_getPhrasePropTypes);

var _isSameDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isSameDay.js");

var _isSameDay2 = _interopRequireDefault(_isSameDay);

var _isAfterDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isAfterDay.js");

var _isAfterDay2 = _interopRequireDefault(_isAfterDay);

var _getVisibleDays = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getVisibleDays.js");

var _getVisibleDays2 = _interopRequireDefault(_getVisibleDays);

var _isDayVisible = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isDayVisible.js");

var _isDayVisible2 = _interopRequireDefault(_isDayVisible);

var _toISODateString = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toISODateString.js");

var _toISODateString2 = _interopRequireDefault(_toISODateString);

var _toISOMonthString = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toISOMonthString.js");

var _toISOMonthString2 = _interopRequireDefault(_toISOMonthString);

var _ScrollableOrientationShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ScrollableOrientationShape.js");

var _ScrollableOrientationShape2 = _interopRequireDefault(_ScrollableOrientationShape);

var _DayOfWeekShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/DayOfWeekShape.js");

var _DayOfWeekShape2 = _interopRequireDefault(_DayOfWeekShape);

var _CalendarInfoPositionShape = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/CalendarInfoPositionShape.js");

var _CalendarInfoPositionShape2 = _interopRequireDefault(_CalendarInfoPositionShape);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

var _DayPicker = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/DayPicker.js");

var _DayPicker2 = _interopRequireDefault(_DayPicker);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)({
  date: _reactMomentProptypes2['default'].momentObj,
  onDateChange: _propTypes2['default'].func,

  focused: _propTypes2['default'].bool,
  onFocusChange: _propTypes2['default'].func,
  onClose: _propTypes2['default'].func,

  keepOpenOnDateSelect: _propTypes2['default'].bool,
  isOutsideRange: _propTypes2['default'].func,
  isDayBlocked: _propTypes2['default'].func,
  isDayHighlighted: _propTypes2['default'].func,

  // DayPicker props
  renderMonthText: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),
  renderMonthElement: (0, _airbnbPropTypes.mutuallyExclusiveProps)(_propTypes2['default'].func, 'renderMonthText', 'renderMonthElement'),
  enableOutsideDays: _propTypes2['default'].bool,
  numberOfMonths: _propTypes2['default'].number,
  orientation: _ScrollableOrientationShape2['default'],
  withPortal: _propTypes2['default'].bool,
  initialVisibleMonth: _propTypes2['default'].func,
  firstDayOfWeek: _DayOfWeekShape2['default'],
  hideKeyboardShortcutsPanel: _propTypes2['default'].bool,
  daySize: _airbnbPropTypes.nonNegativeInteger,
  verticalHeight: _airbnbPropTypes.nonNegativeInteger,
  noBorder: _propTypes2['default'].bool,
  verticalBorderSpacing: _airbnbPropTypes.nonNegativeInteger,
  transitionDuration: _airbnbPropTypes.nonNegativeInteger,
  horizontalMonthPadding: _airbnbPropTypes.nonNegativeInteger,

  navPrev: _propTypes2['default'].node,
  navNext: _propTypes2['default'].node,

  onPrevMonthClick: _propTypes2['default'].func,
  onNextMonthClick: _propTypes2['default'].func,
  onOutsideClick: _propTypes2['default'].func,
  renderCalendarDay: _propTypes2['default'].func,
  renderDayContents: _propTypes2['default'].func,
  renderCalendarInfo: _propTypes2['default'].func,
  calendarInfoPosition: _CalendarInfoPositionShape2['default'],

  // accessibility
  onBlur: _propTypes2['default'].func,
  isFocused: _propTypes2['default'].bool,
  showKeyboardShortcuts: _propTypes2['default'].bool,

  // i18n
  monthFormat: _propTypes2['default'].string,
  weekDayFormat: _propTypes2['default'].string,
  phrases: _propTypes2['default'].shape((0, _getPhrasePropTypes2['default'])(_defaultPhrases.DayPickerPhrases)),
  dayAriaLabelFormat: _propTypes2['default'].string,

  isRTL: _propTypes2['default'].bool
});

var defaultProps = {
  date: undefined, // TODO: use null
  onDateChange: function () {
    function onDateChange() {}

    return onDateChange;
  }(),


  focused: false,
  onFocusChange: function () {
    function onFocusChange() {}

    return onFocusChange;
  }(),
  onClose: function () {
    function onClose() {}

    return onClose;
  }(),


  keepOpenOnDateSelect: false,
  isOutsideRange: function () {
    function isOutsideRange() {}

    return isOutsideRange;
  }(),
  isDayBlocked: function () {
    function isDayBlocked() {}

    return isDayBlocked;
  }(),
  isDayHighlighted: function () {
    function isDayHighlighted() {}

    return isDayHighlighted;
  }(),


  // DayPicker props
  renderMonthText: null,
  enableOutsideDays: false,
  numberOfMonths: 1,
  orientation: _constants.HORIZONTAL_ORIENTATION,
  withPortal: false,
  hideKeyboardShortcutsPanel: false,
  initialVisibleMonth: null,
  firstDayOfWeek: null,
  daySize: _constants.DAY_SIZE,
  verticalHeight: null,
  noBorder: false,
  verticalBorderSpacing: undefined,
  transitionDuration: undefined,
  horizontalMonthPadding: 13,

  navPrev: null,
  navNext: null,

  onPrevMonthClick: function () {
    function onPrevMonthClick() {}

    return onPrevMonthClick;
  }(),
  onNextMonthClick: function () {
    function onNextMonthClick() {}

    return onNextMonthClick;
  }(),
  onOutsideClick: function () {
    function onOutsideClick() {}

    return onOutsideClick;
  }(),


  renderCalendarDay: undefined,
  renderDayContents: null,
  renderCalendarInfo: null,
  renderMonthElement: null,
  calendarInfoPosition: _constants.INFO_POSITION_BOTTOM,

  // accessibility
  onBlur: function () {
    function onBlur() {}

    return onBlur;
  }(),

  isFocused: false,
  showKeyboardShortcuts: false,

  // i18n
  monthFormat: 'MMMM YYYY',
  weekDayFormat: 'dd',
  phrases: _defaultPhrases.DayPickerPhrases,
  dayAriaLabelFormat: undefined,

  isRTL: false
};

var DayPickerSingleDateController = function (_React$Component) {
  _inherits(DayPickerSingleDateController, _React$Component);

  function DayPickerSingleDateController(props) {
    _classCallCheck(this, DayPickerSingleDateController);

    var _this = _possibleConstructorReturn(this, (DayPickerSingleDateController.__proto__ || Object.getPrototypeOf(DayPickerSingleDateController)).call(this, props));

    _this.isTouchDevice = false;
    _this.today = (0, _moment2['default'])();

    _this.modifiers = {
      today: function () {
        function today(day) {
          return _this.isToday(day);
        }

        return today;
      }(),
      blocked: function () {
        function blocked(day) {
          return _this.isBlocked(day);
        }

        return blocked;
      }(),
      'blocked-calendar': function () {
        function blockedCalendar(day) {
          return props.isDayBlocked(day);
        }

        return blockedCalendar;
      }(),
      'blocked-out-of-range': function () {
        function blockedOutOfRange(day) {
          return props.isOutsideRange(day);
        }

        return blockedOutOfRange;
      }(),
      'highlighted-calendar': function () {
        function highlightedCalendar(day) {
          return props.isDayHighlighted(day);
        }

        return highlightedCalendar;
      }(),
      valid: function () {
        function valid(day) {
          return !_this.isBlocked(day);
        }

        return valid;
      }(),
      hovered: function () {
        function hovered(day) {
          return _this.isHovered(day);
        }

        return hovered;
      }(),
      selected: function () {
        function selected(day) {
          return _this.isSelected(day);
        }

        return selected;
      }(),
      'first-day-of-week': function () {
        function firstDayOfWeek(day) {
          return _this.isFirstDayOfWeek(day);
        }

        return firstDayOfWeek;
      }(),
      'last-day-of-week': function () {
        function lastDayOfWeek(day) {
          return _this.isLastDayOfWeek(day);
        }

        return lastDayOfWeek;
      }()
    };

    var _this$getStateForNewM = _this.getStateForNewMonth(props),
        currentMonth = _this$getStateForNewM.currentMonth,
        visibleDays = _this$getStateForNewM.visibleDays;

    _this.state = {
      hoverDate: null,
      currentMonth: currentMonth,
      visibleDays: visibleDays
    };

    _this.onDayMouseEnter = _this.onDayMouseEnter.bind(_this);
    _this.onDayMouseLeave = _this.onDayMouseLeave.bind(_this);
    _this.onDayClick = _this.onDayClick.bind(_this);

    _this.onPrevMonthClick = _this.onPrevMonthClick.bind(_this);
    _this.onNextMonthClick = _this.onNextMonthClick.bind(_this);
    _this.onMonthChange = _this.onMonthChange.bind(_this);
    _this.onYearChange = _this.onYearChange.bind(_this);

    _this.getFirstFocusableDay = _this.getFirstFocusableDay.bind(_this);
    return _this;
  }

  _createClass(DayPickerSingleDateController, [{
    key: 'componentDidMount',
    value: function () {
      function componentDidMount() {
        this.isTouchDevice = (0, _isTouchDevice2['default'])();
      }

      return componentDidMount;
    }()
  }, {
    key: 'componentWillReceiveProps',
    value: function () {
      function componentWillReceiveProps(nextProps) {
        var _this2 = this;

        var date = nextProps.date,
            focused = nextProps.focused,
            isOutsideRange = nextProps.isOutsideRange,
            isDayBlocked = nextProps.isDayBlocked,
            isDayHighlighted = nextProps.isDayHighlighted,
            initialVisibleMonth = nextProps.initialVisibleMonth,
            numberOfMonths = nextProps.numberOfMonths,
            enableOutsideDays = nextProps.enableOutsideDays;
        var _props = this.props,
            prevIsOutsideRange = _props.isOutsideRange,
            prevIsDayBlocked = _props.isDayBlocked,
            prevIsDayHighlighted = _props.isDayHighlighted,
            prevNumberOfMonths = _props.numberOfMonths,
            prevEnableOutsideDays = _props.enableOutsideDays,
            prevInitialVisibleMonth = _props.initialVisibleMonth,
            prevFocused = _props.focused,
            prevDate = _props.date;
        var visibleDays = this.state.visibleDays;


        var recomputeOutsideRange = false;
        var recomputeDayBlocked = false;
        var recomputeDayHighlighted = false;

        if (isOutsideRange !== prevIsOutsideRange) {
          this.modifiers['blocked-out-of-range'] = function (day) {
            return isOutsideRange(day);
          };
          recomputeOutsideRange = true;
        }

        if (isDayBlocked !== prevIsDayBlocked) {
          this.modifiers['blocked-calendar'] = function (day) {
            return isDayBlocked(day);
          };
          recomputeDayBlocked = true;
        }

        if (isDayHighlighted !== prevIsDayHighlighted) {
          this.modifiers['highlighted-calendar'] = function (day) {
            return isDayHighlighted(day);
          };
          recomputeDayHighlighted = true;
        }

        var recomputePropModifiers = recomputeOutsideRange || recomputeDayBlocked || recomputeDayHighlighted;

        if (numberOfMonths !== prevNumberOfMonths || enableOutsideDays !== prevEnableOutsideDays || initialVisibleMonth !== prevInitialVisibleMonth && !prevFocused && focused) {
          var newMonthState = this.getStateForNewMonth(nextProps);
          var currentMonth = newMonthState.currentMonth;
          visibleDays = newMonthState.visibleDays;

          this.setState({
            currentMonth: currentMonth,
            visibleDays: visibleDays
          });
        }

        var didDateChange = date !== prevDate;
        var didFocusChange = focused !== prevFocused;

        var modifiers = {};

        if (didDateChange) {
          modifiers = this.deleteModifier(modifiers, prevDate, 'selected');
          modifiers = this.addModifier(modifiers, date, 'selected');
        }

        if (didFocusChange || recomputePropModifiers) {
          (0, _object4['default'])(visibleDays).forEach(function (days) {
            Object.keys(days).forEach(function (day) {
              var momentObj = (0, _moment2['default'])(day);
              if (_this2.isBlocked(momentObj)) {
                modifiers = _this2.addModifier(modifiers, momentObj, 'blocked');
              } else {
                modifiers = _this2.deleteModifier(modifiers, momentObj, 'blocked');
              }

              if (didFocusChange || recomputeOutsideRange) {
                if (isOutsideRange(momentObj)) {
                  modifiers = _this2.addModifier(modifiers, momentObj, 'blocked-out-of-range');
                } else {
                  modifiers = _this2.deleteModifier(modifiers, momentObj, 'blocked-out-of-range');
                }
              }

              if (didFocusChange || recomputeDayBlocked) {
                if (isDayBlocked(momentObj)) {
                  modifiers = _this2.addModifier(modifiers, momentObj, 'blocked-calendar');
                } else {
                  modifiers = _this2.deleteModifier(modifiers, momentObj, 'blocked-calendar');
                }
              }

              if (didFocusChange || recomputeDayHighlighted) {
                if (isDayHighlighted(momentObj)) {
                  modifiers = _this2.addModifier(modifiers, momentObj, 'highlighted-calendar');
                } else {
                  modifiers = _this2.deleteModifier(modifiers, momentObj, 'highlighted-calendar');
                }
              }
            });
          });
        }

        var today = (0, _moment2['default'])();
        if (!(0, _isSameDay2['default'])(this.today, today)) {
          modifiers = this.deleteModifier(modifiers, this.today, 'today');
          modifiers = this.addModifier(modifiers, today, 'today');
          this.today = today;
        }

        if (Object.keys(modifiers).length > 0) {
          this.setState({
            visibleDays: (0, _object2['default'])({}, visibleDays, modifiers)
          });
        }
      }

      return componentWillReceiveProps;
    }()
  }, {
    key: 'componentWillUpdate',
    value: function () {
      function componentWillUpdate() {
        this.today = (0, _moment2['default'])();
      }

      return componentWillUpdate;
    }()
  }, {
    key: 'onDayClick',
    value: function () {
      function onDayClick(day, e) {
        if (e) e.preventDefault();
        if (this.isBlocked(day)) return;
        var _props2 = this.props,
            onDateChange = _props2.onDateChange,
            keepOpenOnDateSelect = _props2.keepOpenOnDateSelect,
            onFocusChange = _props2.onFocusChange,
            onClose = _props2.onClose;


        onDateChange(day);
        if (!keepOpenOnDateSelect) {
          onFocusChange({ focused: false });
          onClose({ date: day });
        }
      }

      return onDayClick;
    }()
  }, {
    key: 'onDayMouseEnter',
    value: function () {
      function onDayMouseEnter(day) {
        if (this.isTouchDevice) return;
        var _state = this.state,
            hoverDate = _state.hoverDate,
            visibleDays = _state.visibleDays;


        var modifiers = this.deleteModifier({}, hoverDate, 'hovered');
        modifiers = this.addModifier(modifiers, day, 'hovered');

        this.setState({
          hoverDate: day,
          visibleDays: (0, _object2['default'])({}, visibleDays, modifiers)
        });
      }

      return onDayMouseEnter;
    }()
  }, {
    key: 'onDayMouseLeave',
    value: function () {
      function onDayMouseLeave() {
        var _state2 = this.state,
            hoverDate = _state2.hoverDate,
            visibleDays = _state2.visibleDays;

        if (this.isTouchDevice || !hoverDate) return;

        var modifiers = this.deleteModifier({}, hoverDate, 'hovered');

        this.setState({
          hoverDate: null,
          visibleDays: (0, _object2['default'])({}, visibleDays, modifiers)
        });
      }

      return onDayMouseLeave;
    }()
  }, {
    key: 'onPrevMonthClick',
    value: function () {
      function onPrevMonthClick() {
        var _props3 = this.props,
            onPrevMonthClick = _props3.onPrevMonthClick,
            numberOfMonths = _props3.numberOfMonths,
            enableOutsideDays = _props3.enableOutsideDays;
        var _state3 = this.state,
            currentMonth = _state3.currentMonth,
            visibleDays = _state3.visibleDays;


        var newVisibleDays = {};
        Object.keys(visibleDays).sort().slice(0, numberOfMonths + 1).forEach(function (month) {
          newVisibleDays[month] = visibleDays[month];
        });

        var prevMonth = currentMonth.clone().subtract(1, 'month');
        var prevMonthVisibleDays = (0, _getVisibleDays2['default'])(prevMonth, 1, enableOutsideDays);

        this.setState({
          currentMonth: prevMonth,
          visibleDays: (0, _object2['default'])({}, newVisibleDays, this.getModifiers(prevMonthVisibleDays))
        }, function () {
          onPrevMonthClick(prevMonth.clone());
        });
      }

      return onPrevMonthClick;
    }()
  }, {
    key: 'onNextMonthClick',
    value: function () {
      function onNextMonthClick() {
        var _props4 = this.props,
            onNextMonthClick = _props4.onNextMonthClick,
            numberOfMonths = _props4.numberOfMonths,
            enableOutsideDays = _props4.enableOutsideDays;
        var _state4 = this.state,
            currentMonth = _state4.currentMonth,
            visibleDays = _state4.visibleDays;


        var newVisibleDays = {};
        Object.keys(visibleDays).sort().slice(1).forEach(function (month) {
          newVisibleDays[month] = visibleDays[month];
        });

        var nextMonth = currentMonth.clone().add(numberOfMonths, 'month');
        var nextMonthVisibleDays = (0, _getVisibleDays2['default'])(nextMonth, 1, enableOutsideDays);

        var newCurrentMonth = currentMonth.clone().add(1, 'month');
        this.setState({
          currentMonth: newCurrentMonth,
          visibleDays: (0, _object2['default'])({}, newVisibleDays, this.getModifiers(nextMonthVisibleDays))
        }, function () {
          onNextMonthClick(newCurrentMonth.clone());
        });
      }

      return onNextMonthClick;
    }()
  }, {
    key: 'onMonthChange',
    value: function () {
      function onMonthChange(newMonth) {
        var _props5 = this.props,
            numberOfMonths = _props5.numberOfMonths,
            enableOutsideDays = _props5.enableOutsideDays,
            orientation = _props5.orientation;

        var withoutTransitionMonths = orientation === _constants.VERTICAL_SCROLLABLE;
        var newVisibleDays = (0, _getVisibleDays2['default'])(newMonth, numberOfMonths, enableOutsideDays, withoutTransitionMonths);

        this.setState({
          currentMonth: newMonth.clone(),
          visibleDays: this.getModifiers(newVisibleDays)
        });
      }

      return onMonthChange;
    }()
  }, {
    key: 'onYearChange',
    value: function () {
      function onYearChange(newMonth) {
        var _props6 = this.props,
            numberOfMonths = _props6.numberOfMonths,
            enableOutsideDays = _props6.enableOutsideDays,
            orientation = _props6.orientation;

        var withoutTransitionMonths = orientation === _constants.VERTICAL_SCROLLABLE;
        var newVisibleDays = (0, _getVisibleDays2['default'])(newMonth, numberOfMonths, enableOutsideDays, withoutTransitionMonths);

        this.setState({
          currentMonth: newMonth.clone(),
          visibleDays: this.getModifiers(newVisibleDays)
        });
      }

      return onYearChange;
    }()
  }, {
    key: 'getFirstFocusableDay',
    value: function () {
      function getFirstFocusableDay(newMonth) {
        var _this3 = this;

        var _props7 = this.props,
            date = _props7.date,
            numberOfMonths = _props7.numberOfMonths;


        var focusedDate = newMonth.clone().startOf('month');
        if (date) {
          focusedDate = date.clone();
        }

        if (this.isBlocked(focusedDate)) {
          var days = [];
          var lastVisibleDay = newMonth.clone().add(numberOfMonths - 1, 'months').endOf('month');
          var currentDay = focusedDate.clone();
          while (!(0, _isAfterDay2['default'])(currentDay, lastVisibleDay)) {
            currentDay = currentDay.clone().add(1, 'day');
            days.push(currentDay);
          }

          var viableDays = days.filter(function (day) {
            return !_this3.isBlocked(day) && (0, _isAfterDay2['default'])(day, focusedDate);
          });
          if (viableDays.length > 0) {
            var _viableDays = _slicedToArray(viableDays, 1);

            focusedDate = _viableDays[0];
          }
        }

        return focusedDate;
      }

      return getFirstFocusableDay;
    }()
  }, {
    key: 'getModifiers',
    value: function () {
      function getModifiers(visibleDays) {
        var _this4 = this;

        var modifiers = {};
        Object.keys(visibleDays).forEach(function (month) {
          modifiers[month] = {};
          visibleDays[month].forEach(function (day) {
            modifiers[month][(0, _toISODateString2['default'])(day)] = _this4.getModifiersForDay(day);
          });
        });

        return modifiers;
      }

      return getModifiers;
    }()
  }, {
    key: 'getModifiersForDay',
    value: function () {
      function getModifiersForDay(day) {
        var _this5 = this;

        return new Set(Object.keys(this.modifiers).filter(function (modifier) {
          return _this5.modifiers[modifier](day);
        }));
      }

      return getModifiersForDay;
    }()
  }, {
    key: 'getStateForNewMonth',
    value: function () {
      function getStateForNewMonth(nextProps) {
        var _this6 = this;

        var initialVisibleMonth = nextProps.initialVisibleMonth,
            date = nextProps.date,
            numberOfMonths = nextProps.numberOfMonths,
            enableOutsideDays = nextProps.enableOutsideDays;

        var initialVisibleMonthThunk = initialVisibleMonth || (date ? function () {
          return date;
        } : function () {
          return _this6.today;
        });
        var currentMonth = initialVisibleMonthThunk();
        var visibleDays = this.getModifiers((0, _getVisibleDays2['default'])(currentMonth, numberOfMonths, enableOutsideDays));
        return { currentMonth: currentMonth, visibleDays: visibleDays };
      }

      return getStateForNewMonth;
    }()
  }, {
    key: 'addModifier',
    value: function () {
      function addModifier(updatedDays, day, modifier) {
        var _props8 = this.props,
            numberOfVisibleMonths = _props8.numberOfMonths,
            enableOutsideDays = _props8.enableOutsideDays,
            orientation = _props8.orientation;
        var _state5 = this.state,
            firstVisibleMonth = _state5.currentMonth,
            visibleDays = _state5.visibleDays;


        var currentMonth = firstVisibleMonth;
        var numberOfMonths = numberOfVisibleMonths;
        if (orientation === _constants.VERTICAL_SCROLLABLE) {
          numberOfMonths = Object.keys(visibleDays).length;
        } else {
          currentMonth = currentMonth.clone().subtract(1, 'month');
          numberOfMonths += 2;
        }
        if (!day || !(0, _isDayVisible2['default'])(day, currentMonth, numberOfMonths, enableOutsideDays)) {
          return updatedDays;
        }

        var iso = (0, _toISODateString2['default'])(day);

        var updatedDaysAfterAddition = (0, _object2['default'])({}, updatedDays);
        if (enableOutsideDays) {
          var monthsToUpdate = Object.keys(visibleDays).filter(function (monthKey) {
            return Object.keys(visibleDays[monthKey]).indexOf(iso) > -1;
          });

          updatedDaysAfterAddition = monthsToUpdate.reduce(function (days, monthIso) {
            var month = updatedDays[monthIso] || visibleDays[monthIso];
            var modifiers = new Set(month[iso]);
            modifiers.add(modifier);
            return (0, _object2['default'])({}, days, _defineProperty({}, monthIso, (0, _object2['default'])({}, month, _defineProperty({}, iso, modifiers))));
          }, updatedDaysAfterAddition);
        } else {
          var monthIso = (0, _toISOMonthString2['default'])(day);
          var month = updatedDays[monthIso] || visibleDays[monthIso];

          var modifiers = new Set(month[iso]);
          modifiers.add(modifier);
          updatedDaysAfterAddition = (0, _object2['default'])({}, updatedDaysAfterAddition, _defineProperty({}, monthIso, (0, _object2['default'])({}, month, _defineProperty({}, iso, modifiers))));
        }

        return updatedDaysAfterAddition;
      }

      return addModifier;
    }()
  }, {
    key: 'deleteModifier',
    value: function () {
      function deleteModifier(updatedDays, day, modifier) {
        var _props9 = this.props,
            numberOfVisibleMonths = _props9.numberOfMonths,
            enableOutsideDays = _props9.enableOutsideDays,
            orientation = _props9.orientation;
        var _state6 = this.state,
            firstVisibleMonth = _state6.currentMonth,
            visibleDays = _state6.visibleDays;


        var currentMonth = firstVisibleMonth;
        var numberOfMonths = numberOfVisibleMonths;
        if (orientation === _constants.VERTICAL_SCROLLABLE) {
          numberOfMonths = Object.keys(visibleDays).length;
        } else {
          currentMonth = currentMonth.clone().subtract(1, 'month');
          numberOfMonths += 2;
        }
        if (!day || !(0, _isDayVisible2['default'])(day, currentMonth, numberOfMonths, enableOutsideDays)) {
          return updatedDays;
        }

        var iso = (0, _toISODateString2['default'])(day);

        var updatedDaysAfterDeletion = (0, _object2['default'])({}, updatedDays);
        if (enableOutsideDays) {
          var monthsToUpdate = Object.keys(visibleDays).filter(function (monthKey) {
            return Object.keys(visibleDays[monthKey]).indexOf(iso) > -1;
          });

          updatedDaysAfterDeletion = monthsToUpdate.reduce(function (days, monthIso) {
            var month = updatedDays[monthIso] || visibleDays[monthIso];
            var modifiers = new Set(month[iso]);
            modifiers['delete'](modifier);
            return (0, _object2['default'])({}, days, _defineProperty({}, monthIso, (0, _object2['default'])({}, month, _defineProperty({}, iso, modifiers))));
          }, updatedDaysAfterDeletion);
        } else {
          var monthIso = (0, _toISOMonthString2['default'])(day);
          var month = updatedDays[monthIso] || visibleDays[monthIso];

          var modifiers = new Set(month[iso]);
          modifiers['delete'](modifier);
          updatedDaysAfterDeletion = (0, _object2['default'])({}, updatedDaysAfterDeletion, _defineProperty({}, monthIso, (0, _object2['default'])({}, month, _defineProperty({}, iso, modifiers))));
        }

        return updatedDaysAfterDeletion;
      }

      return deleteModifier;
    }()
  }, {
    key: 'isBlocked',
    value: function () {
      function isBlocked(day) {
        var _props10 = this.props,
            isDayBlocked = _props10.isDayBlocked,
            isOutsideRange = _props10.isOutsideRange;

        return isDayBlocked(day) || isOutsideRange(day);
      }

      return isBlocked;
    }()
  }, {
    key: 'isHovered',
    value: function () {
      function isHovered(day) {
        var _ref = this.state || {},
            hoverDate = _ref.hoverDate;

        return (0, _isSameDay2['default'])(day, hoverDate);
      }

      return isHovered;
    }()
  }, {
    key: 'isSelected',
    value: function () {
      function isSelected(day) {
        var date = this.props.date;

        return (0, _isSameDay2['default'])(day, date);
      }

      return isSelected;
    }()
  }, {
    key: 'isToday',
    value: function () {
      function isToday(day) {
        return (0, _isSameDay2['default'])(day, this.today);
      }

      return isToday;
    }()
  }, {
    key: 'isFirstDayOfWeek',
    value: function () {
      function isFirstDayOfWeek(day) {
        var firstDayOfWeek = this.props.firstDayOfWeek;

        return day.day() === (firstDayOfWeek || _moment2['default'].localeData().firstDayOfWeek());
      }

      return isFirstDayOfWeek;
    }()
  }, {
    key: 'isLastDayOfWeek',
    value: function () {
      function isLastDayOfWeek(day) {
        var firstDayOfWeek = this.props.firstDayOfWeek;

        return day.day() === ((firstDayOfWeek || _moment2['default'].localeData().firstDayOfWeek()) + 6) % 7;
      }

      return isLastDayOfWeek;
    }()
  }, {
    key: 'render',
    value: function () {
      function render() {
        var _props11 = this.props,
            numberOfMonths = _props11.numberOfMonths,
            orientation = _props11.orientation,
            monthFormat = _props11.monthFormat,
            renderMonthText = _props11.renderMonthText,
            navPrev = _props11.navPrev,
            navNext = _props11.navNext,
            onOutsideClick = _props11.onOutsideClick,
            withPortal = _props11.withPortal,
            focused = _props11.focused,
            enableOutsideDays = _props11.enableOutsideDays,
            hideKeyboardShortcutsPanel = _props11.hideKeyboardShortcutsPanel,
            daySize = _props11.daySize,
            firstDayOfWeek = _props11.firstDayOfWeek,
            renderCalendarDay = _props11.renderCalendarDay,
            renderDayContents = _props11.renderDayContents,
            renderCalendarInfo = _props11.renderCalendarInfo,
            renderMonthElement = _props11.renderMonthElement,
            calendarInfoPosition = _props11.calendarInfoPosition,
            isFocused = _props11.isFocused,
            isRTL = _props11.isRTL,
            phrases = _props11.phrases,
            dayAriaLabelFormat = _props11.dayAriaLabelFormat,
            onBlur = _props11.onBlur,
            showKeyboardShortcuts = _props11.showKeyboardShortcuts,
            weekDayFormat = _props11.weekDayFormat,
            verticalHeight = _props11.verticalHeight,
            noBorder = _props11.noBorder,
            transitionDuration = _props11.transitionDuration,
            verticalBorderSpacing = _props11.verticalBorderSpacing,
            horizontalMonthPadding = _props11.horizontalMonthPadding;
        var _state7 = this.state,
            currentMonth = _state7.currentMonth,
            visibleDays = _state7.visibleDays;


        return _react2['default'].createElement(_DayPicker2['default'], {
          orientation: orientation,
          enableOutsideDays: enableOutsideDays,
          modifiers: visibleDays,
          numberOfMonths: numberOfMonths,
          onDayClick: this.onDayClick,
          onDayMouseEnter: this.onDayMouseEnter,
          onDayMouseLeave: this.onDayMouseLeave,
          onPrevMonthClick: this.onPrevMonthClick,
          onNextMonthClick: this.onNextMonthClick,
          onMonthChange: this.onMonthChange,
          onYearChange: this.onYearChange,
          monthFormat: monthFormat,
          withPortal: withPortal,
          hidden: !focused,
          hideKeyboardShortcutsPanel: hideKeyboardShortcutsPanel,
          initialVisibleMonth: function () {
            function initialVisibleMonth() {
              return currentMonth;
            }

            return initialVisibleMonth;
          }(),
          firstDayOfWeek: firstDayOfWeek,
          onOutsideClick: onOutsideClick,
          navPrev: navPrev,
          navNext: navNext,
          renderMonthText: renderMonthText,
          renderCalendarDay: renderCalendarDay,
          renderDayContents: renderDayContents,
          renderCalendarInfo: renderCalendarInfo,
          renderMonthElement: renderMonthElement,
          calendarInfoPosition: calendarInfoPosition,
          isFocused: isFocused,
          getFirstFocusableDay: this.getFirstFocusableDay,
          onBlur: onBlur,
          phrases: phrases,
          daySize: daySize,
          isRTL: isRTL,
          showKeyboardShortcuts: showKeyboardShortcuts,
          weekDayFormat: weekDayFormat,
          dayAriaLabelFormat: dayAriaLabelFormat,
          verticalHeight: verticalHeight,
          noBorder: noBorder,
          transitionDuration: transitionDuration,
          verticalBorderSpacing: verticalBorderSpacing,
          horizontalMonthPadding: horizontalMonthPadding
        });
      }

      return render;
    }()
  }]);

  return DayPickerSingleDateController;
}(_react2['default'].Component);

exports.A = DayPickerSingleDateController;


DayPickerSingleDateController.propTypes = propTypes;
DayPickerSingleDateController.defaultProps = defaultProps;

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/KeyboardShortcutRow.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

var _reactWithStyles = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

var propTypes = (0, _airbnbPropTypes.forbidExtraProps)((0, _object2['default'])({}, _reactWithStyles.withStylesPropTypes, {
  unicode: _propTypes2['default'].string.isRequired,
  label: _propTypes2['default'].string.isRequired,
  action: _propTypes2['default'].string.isRequired,
  block: _propTypes2['default'].bool
}));

var defaultProps = {
  block: false
};

function KeyboardShortcutRow(_ref) {
  var unicode = _ref.unicode,
      label = _ref.label,
      action = _ref.action,
      block = _ref.block,
      styles = _ref.styles;

  return _react2['default'].createElement(
    'li',
    (0, _reactWithStyles.css)(styles.KeyboardShortcutRow, block && styles.KeyboardShortcutRow__block),
    _react2['default'].createElement(
      'div',
      (0, _reactWithStyles.css)(styles.KeyboardShortcutRow_keyContainer, block && styles.KeyboardShortcutRow_keyContainer__block),
      _react2['default'].createElement(
        'span',
        _extends({}, (0, _reactWithStyles.css)(styles.KeyboardShortcutRow_key), {
          role: 'img',
          'aria-label': String(label) + ',' // add comma so screen readers will pause before reading action
        }),
        unicode
      )
    ),
    _react2['default'].createElement(
      'div',
      (0, _reactWithStyles.css)(styles.KeyboardShortcutRow_action),
      action
    )
  );
}

KeyboardShortcutRow.propTypes = propTypes;
KeyboardShortcutRow.defaultProps = defaultProps;

exports["default"] = (0, _reactWithStyles.withStyles)(function (_ref2) {
  var color = _ref2.reactDates.color;
  return {
    KeyboardShortcutRow: {
      listStyle: 'none',
      margin: '6px 0'
    },

    KeyboardShortcutRow__block: {
      marginBottom: 16
    },

    KeyboardShortcutRow_keyContainer: {
      display: 'inline-block',
      whiteSpace: 'nowrap',
      textAlign: 'right',
      marginRight: 6
    },

    KeyboardShortcutRow_keyContainer__block: {
      textAlign: 'left',
      display: 'inline'
    },

    KeyboardShortcutRow_key: {
      fontFamily: 'monospace',
      fontSize: 12,
      textTransform: 'uppercase',
      background: color.core.grayLightest,
      padding: '2px 6px'
    },

    KeyboardShortcutRow_action: {
      display: 'inline',
      wordBreak: 'break-word',
      marginLeft: 8
    }
  };
})(KeyboardShortcutRow);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/LeftArrow.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

var LeftArrow = function () {
  function LeftArrow(props) {
    return _react2['default'].createElement(
      'svg',
      props,
      _react2['default'].createElement('path', {
        d: 'M336.2 274.5l-210.1 210h805.4c13 0 23 10 23 23s-10 23-23 23H126.1l210.1 210.1c11 11 11 21 0 32-5 5-10 7-16 7s-11-2-16-7l-249.1-249c-11-11-11-21 0-32l249.1-249.1c21-21.1 53 10.9 32 32z'
      })
    );
  }

  return LeftArrow;
}();

LeftArrow.defaultProps = {
  viewBox: '0 0 1000 1000'
};
exports["default"] = LeftArrow;

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/components/RightArrow.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

var RightArrow = function () {
  function RightArrow(props) {
    return _react2['default'].createElement(
      'svg',
      props,
      _react2['default'].createElement('path', {
        d: 'M694.4 242.4l249.1 249.1c11 11 11 21 0 32L694.4 772.7c-5 5-10 7-16 7s-11-2-16-7c-11-11-11-21 0-32l210.1-210.1H67.1c-13 0-23-10-23-23s10-23 23-23h805.4L662.4 274.5c-21-21.1 11-53.1 32-32.1z'
      })
    );
  }

  return RightArrow;
}();

RightArrow.defaultProps = {
  viewBox: '0 0 1000 1000'
};
exports["default"] = RightArrow;

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var DISPLAY_FORMAT = exports.DISPLAY_FORMAT = 'L';
var ISO_FORMAT = exports.ISO_FORMAT = 'YYYY-MM-DD';
var ISO_MONTH_FORMAT = exports.ISO_MONTH_FORMAT = 'YYYY-MM';

var START_DATE = exports.START_DATE = 'startDate';
var END_DATE = exports.END_DATE = 'endDate';

var HORIZONTAL_ORIENTATION = exports.HORIZONTAL_ORIENTATION = 'horizontal';
var VERTICAL_ORIENTATION = exports.VERTICAL_ORIENTATION = 'vertical';
var VERTICAL_SCROLLABLE = exports.VERTICAL_SCROLLABLE = 'verticalScrollable';

var ICON_BEFORE_POSITION = exports.ICON_BEFORE_POSITION = 'before';
var ICON_AFTER_POSITION = exports.ICON_AFTER_POSITION = 'after';

var INFO_POSITION_TOP = exports.INFO_POSITION_TOP = 'top';
var INFO_POSITION_BOTTOM = exports.INFO_POSITION_BOTTOM = 'bottom';
var INFO_POSITION_BEFORE = exports.INFO_POSITION_BEFORE = 'before';
var INFO_POSITION_AFTER = exports.INFO_POSITION_AFTER = 'after';

var ANCHOR_LEFT = exports.ANCHOR_LEFT = 'left';
var ANCHOR_RIGHT = exports.ANCHOR_RIGHT = 'right';

var OPEN_DOWN = exports.OPEN_DOWN = 'down';
var OPEN_UP = exports.OPEN_UP = 'up';

var DAY_SIZE = exports.DAY_SIZE = 39;
var BLOCKED_MODIFIER = exports.BLOCKED_MODIFIER = 'blocked';
var WEEKDAYS = exports.WEEKDAYS = [0, 1, 2, 3, 4, 5, 6];

var FANG_WIDTH_PX = exports.FANG_WIDTH_PX = 20;
var FANG_HEIGHT_PX = exports.FANG_HEIGHT_PX = 10;
var DEFAULT_VERTICAL_SPACING = exports.DEFAULT_VERTICAL_SPACING = 22;

var MODIFIER_KEY_NAMES = exports.MODIFIER_KEY_NAMES = new Set(['Shift', 'Control', 'Alt', 'Meta']);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/defaultPhrases.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var calendarLabel = 'Calendar';
var closeDatePicker = 'Close';
var focusStartDate = 'Interact with the calendar and add the check-in date for your trip.';
var clearDate = 'Clear Date';
var clearDates = 'Clear Dates';
var jumpToPrevMonth = 'Move backward to switch to the previous month.';
var jumpToNextMonth = 'Move forward to switch to the next month.';
var keyboardShortcuts = 'Keyboard Shortcuts';
var showKeyboardShortcutsPanel = 'Open the keyboard shortcuts panel.';
var hideKeyboardShortcutsPanel = 'Close the shortcuts panel.';
var openThisPanel = 'Open this panel.';
var enterKey = 'Enter key';
var leftArrowRightArrow = 'Right and left arrow keys';
var upArrowDownArrow = 'up and down arrow keys';
var pageUpPageDown = 'page up and page down keys';
var homeEnd = 'Home and end keys';
var escape = 'Escape key';
var questionMark = 'Question mark';
var selectFocusedDate = 'Select the date in focus.';
var moveFocusByOneDay = 'Move backward (left) and forward (right) by one day.';
var moveFocusByOneWeek = 'Move backward (up) and forward (down) by one week.';
var moveFocusByOneMonth = 'Switch months.';
var moveFocustoStartAndEndOfWeek = 'Go to the first or last day of a week.';
var returnFocusToInput = 'Return to the date input field.';
var keyboardNavigationInstructions = 'Press the down arrow key to interact with the calendar and\n  select a date. Press the question mark key to get the keyboard shortcuts for changing dates.';

var chooseAvailableStartDate = function chooseAvailableStartDate(_ref) {
  var date = _ref.date;
  return 'Choose ' + String(date) + ' as your check-in date. It\u2019s available.';
};
var chooseAvailableEndDate = function chooseAvailableEndDate(_ref2) {
  var date = _ref2.date;
  return 'Choose ' + String(date) + ' as your check-out date. It\u2019s available.';
};
var chooseAvailableDate = function chooseAvailableDate(_ref3) {
  var date = _ref3.date;
  return date;
};
var dateIsUnavailable = function dateIsUnavailable(_ref4) {
  var date = _ref4.date;
  return 'Not available. ' + String(date);
};
var dateIsSelected = function dateIsSelected(_ref5) {
  var date = _ref5.date;
  return 'Selected. ' + String(date);
};

exports["default"] = {
  calendarLabel: calendarLabel,
  closeDatePicker: closeDatePicker,
  focusStartDate: focusStartDate,
  clearDate: clearDate,
  clearDates: clearDates,
  jumpToPrevMonth: jumpToPrevMonth,
  jumpToNextMonth: jumpToNextMonth,
  keyboardShortcuts: keyboardShortcuts,
  showKeyboardShortcutsPanel: showKeyboardShortcutsPanel,
  hideKeyboardShortcutsPanel: hideKeyboardShortcutsPanel,
  openThisPanel: openThisPanel,
  enterKey: enterKey,
  leftArrowRightArrow: leftArrowRightArrow,
  upArrowDownArrow: upArrowDownArrow,
  pageUpPageDown: pageUpPageDown,
  homeEnd: homeEnd,
  escape: escape,
  questionMark: questionMark,
  selectFocusedDate: selectFocusedDate,
  moveFocusByOneDay: moveFocusByOneDay,
  moveFocusByOneWeek: moveFocusByOneWeek,
  moveFocusByOneMonth: moveFocusByOneMonth,
  moveFocustoStartAndEndOfWeek: moveFocustoStartAndEndOfWeek,
  returnFocusToInput: returnFocusToInput,
  keyboardNavigationInstructions: keyboardNavigationInstructions,

  chooseAvailableStartDate: chooseAvailableStartDate,
  chooseAvailableEndDate: chooseAvailableEndDate,
  dateIsUnavailable: dateIsUnavailable,
  dateIsSelected: dateIsSelected
};
var DateRangePickerPhrases = exports.DateRangePickerPhrases = {
  calendarLabel: calendarLabel,
  closeDatePicker: closeDatePicker,
  clearDates: clearDates,
  focusStartDate: focusStartDate,
  jumpToPrevMonth: jumpToPrevMonth,
  jumpToNextMonth: jumpToNextMonth,
  keyboardShortcuts: keyboardShortcuts,
  showKeyboardShortcutsPanel: showKeyboardShortcutsPanel,
  hideKeyboardShortcutsPanel: hideKeyboardShortcutsPanel,
  openThisPanel: openThisPanel,
  enterKey: enterKey,
  leftArrowRightArrow: leftArrowRightArrow,
  upArrowDownArrow: upArrowDownArrow,
  pageUpPageDown: pageUpPageDown,
  homeEnd: homeEnd,
  escape: escape,
  questionMark: questionMark,
  selectFocusedDate: selectFocusedDate,
  moveFocusByOneDay: moveFocusByOneDay,
  moveFocusByOneWeek: moveFocusByOneWeek,
  moveFocusByOneMonth: moveFocusByOneMonth,
  moveFocustoStartAndEndOfWeek: moveFocustoStartAndEndOfWeek,
  returnFocusToInput: returnFocusToInput,
  keyboardNavigationInstructions: keyboardNavigationInstructions,
  chooseAvailableStartDate: chooseAvailableStartDate,
  chooseAvailableEndDate: chooseAvailableEndDate,
  dateIsUnavailable: dateIsUnavailable,
  dateIsSelected: dateIsSelected
};

var DateRangePickerInputPhrases = exports.DateRangePickerInputPhrases = {
  focusStartDate: focusStartDate,
  clearDates: clearDates,
  keyboardNavigationInstructions: keyboardNavigationInstructions
};

var SingleDatePickerPhrases = exports.SingleDatePickerPhrases = {
  calendarLabel: calendarLabel,
  closeDatePicker: closeDatePicker,
  clearDate: clearDate,
  jumpToPrevMonth: jumpToPrevMonth,
  jumpToNextMonth: jumpToNextMonth,
  keyboardShortcuts: keyboardShortcuts,
  showKeyboardShortcutsPanel: showKeyboardShortcutsPanel,
  hideKeyboardShortcutsPanel: hideKeyboardShortcutsPanel,
  openThisPanel: openThisPanel,
  enterKey: enterKey,
  leftArrowRightArrow: leftArrowRightArrow,
  upArrowDownArrow: upArrowDownArrow,
  pageUpPageDown: pageUpPageDown,
  homeEnd: homeEnd,
  escape: escape,
  questionMark: questionMark,
  selectFocusedDate: selectFocusedDate,
  moveFocusByOneDay: moveFocusByOneDay,
  moveFocusByOneWeek: moveFocusByOneWeek,
  moveFocusByOneMonth: moveFocusByOneMonth,
  moveFocustoStartAndEndOfWeek: moveFocustoStartAndEndOfWeek,
  returnFocusToInput: returnFocusToInput,
  keyboardNavigationInstructions: keyboardNavigationInstructions,
  chooseAvailableDate: chooseAvailableDate,
  dateIsUnavailable: dateIsUnavailable,
  dateIsSelected: dateIsSelected
};

var SingleDatePickerInputPhrases = exports.SingleDatePickerInputPhrases = {
  clearDate: clearDate,
  keyboardNavigationInstructions: keyboardNavigationInstructions
};

var DayPickerPhrases = exports.DayPickerPhrases = {
  calendarLabel: calendarLabel,
  jumpToPrevMonth: jumpToPrevMonth,
  jumpToNextMonth: jumpToNextMonth,
  keyboardShortcuts: keyboardShortcuts,
  showKeyboardShortcutsPanel: showKeyboardShortcutsPanel,
  hideKeyboardShortcutsPanel: hideKeyboardShortcutsPanel,
  openThisPanel: openThisPanel,
  enterKey: enterKey,
  leftArrowRightArrow: leftArrowRightArrow,
  upArrowDownArrow: upArrowDownArrow,
  pageUpPageDown: pageUpPageDown,
  homeEnd: homeEnd,
  escape: escape,
  questionMark: questionMark,
  selectFocusedDate: selectFocusedDate,
  moveFocusByOneDay: moveFocusByOneDay,
  moveFocusByOneWeek: moveFocusByOneWeek,
  moveFocusByOneMonth: moveFocusByOneMonth,
  moveFocustoStartAndEndOfWeek: moveFocustoStartAndEndOfWeek,
  returnFocusToInput: returnFocusToInput,
  chooseAvailableStartDate: chooseAvailableStartDate,
  chooseAvailableEndDate: chooseAvailableEndDate,
  chooseAvailableDate: chooseAvailableDate,
  dateIsUnavailable: dateIsUnavailable,
  dateIsSelected: dateIsSelected
};

var DayPickerKeyboardShortcutsPhrases = exports.DayPickerKeyboardShortcutsPhrases = {
  keyboardShortcuts: keyboardShortcuts,
  showKeyboardShortcutsPanel: showKeyboardShortcutsPanel,
  hideKeyboardShortcutsPanel: hideKeyboardShortcutsPanel,
  openThisPanel: openThisPanel,
  enterKey: enterKey,
  leftArrowRightArrow: leftArrowRightArrow,
  upArrowDownArrow: upArrowDownArrow,
  pageUpPageDown: pageUpPageDown,
  homeEnd: homeEnd,
  escape: escape,
  questionMark: questionMark,
  selectFocusedDate: selectFocusedDate,
  moveFocusByOneDay: moveFocusByOneDay,
  moveFocusByOneWeek: moveFocusByOneWeek,
  moveFocusByOneMonth: moveFocusByOneMonth,
  moveFocustoStartAndEndOfWeek: moveFocustoStartAndEndOfWeek,
  returnFocusToInput: returnFocusToInput
};

var DayPickerNavigationPhrases = exports.DayPickerNavigationPhrases = {
  jumpToPrevMonth: jumpToPrevMonth,
  jumpToNextMonth: jumpToNextMonth
};

var CalendarDayPhrases = exports.CalendarDayPhrases = {
  chooseAvailableDate: chooseAvailableDate,
  dateIsUnavailable: dateIsUnavailable,
  dateIsSelected: dateIsSelected
};

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/CalendarInfoPositionShape.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

exports["default"] = _propTypes2['default'].oneOf([_constants.INFO_POSITION_TOP, _constants.INFO_POSITION_BOTTOM, _constants.INFO_POSITION_BEFORE, _constants.INFO_POSITION_AFTER]);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/DayOfWeekShape.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

exports["default"] = _propTypes2['default'].oneOf(_constants.WEEKDAYS);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ModifiersShape.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _airbnbPropTypes = __webpack_require__("../../node_modules/.pnpm/airbnb-prop-types@2.16.0_react@17.0.2/node_modules/airbnb-prop-types/index.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

exports["default"] = (0, _airbnbPropTypes.and)([_propTypes2['default'].instanceOf(Set), function () {
  function modifiers(props, propName) {
    for (var _len = arguments.length, rest = Array(_len > 2 ? _len - 2 : 0), _key = 2; _key < _len; _key++) {
      rest[_key - 2] = arguments[_key];
    }

    var propValue = props[propName];

    var firstError = void 0;
    [].concat(_toConsumableArray(propValue)).some(function (v, i) {
      var _PropTypes$string;

      var fakePropName = String(propName) + ': index ' + String(i);
      firstError = (_PropTypes$string = _propTypes2['default'].string).isRequired.apply(_PropTypes$string, [_defineProperty({}, fakePropName, v), fakePropName].concat(rest));
      return firstError != null;
    });
    return firstError == null ? null : firstError;
  }

  return modifiers;
}()], 'Modifiers (Set of Strings)');

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/shapes/ScrollableOrientationShape.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

exports["default"] = _propTypes2['default'].oneOf([_constants.HORIZONTAL_ORIENTATION, _constants.VERTICAL_ORIENTATION, _constants.VERTICAL_SCROLLABLE]);

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/theme/DefaultTheme.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var core = {
  white: '#fff',
  gray: '#484848',
  grayLight: '#82888a',
  grayLighter: '#cacccd',
  grayLightest: '#f2f2f2',

  borderMedium: '#c4c4c4',
  border: '#dbdbdb',
  borderLight: '#e4e7e7',
  borderLighter: '#eceeee',
  borderBright: '#f4f5f5',

  primary: '#00a699',
  primaryShade_1: '#33dacd',
  primaryShade_2: '#66e2da',
  primaryShade_3: '#80e8e0',
  primaryShade_4: '#b2f1ec',
  primary_dark: '#008489',

  secondary: '#007a87',

  yellow: '#ffe8bc',
  yellow_dark: '#ffce71'
};

exports["default"] = {
  reactDates: {
    zIndex: 0,
    border: {
      input: {
        border: 0,
        borderTop: 0,
        borderRight: 0,
        borderBottom: '2px solid transparent',
        borderLeft: 0,
        outlineFocused: 0,
        borderFocused: 0,
        borderTopFocused: 0,
        borderLeftFocused: 0,
        borderBottomFocused: '2px solid ' + String(core.primary_dark),
        borderRightFocused: 0,
        borderRadius: 0
      },
      pickerInput: {
        borderWidth: 1,
        borderStyle: 'solid',
        borderRadius: 2
      }
    },

    color: {
      core: core,

      disabled: core.grayLightest,

      background: core.white,
      backgroundDark: '#f2f2f2',
      backgroundFocused: core.white,
      border: 'rgb(219, 219, 219)',
      text: core.gray,
      textDisabled: core.border,
      textFocused: '#007a87',
      placeholderText: '#757575',

      outside: {
        backgroundColor: core.white,
        backgroundColor_active: core.white,
        backgroundColor_hover: core.white,
        color: core.gray,
        color_active: core.gray,
        color_hover: core.gray
      },

      highlighted: {
        backgroundColor: core.yellow,
        backgroundColor_active: core.yellow_dark,
        backgroundColor_hover: core.yellow_dark,
        color: core.gray,
        color_active: core.gray,
        color_hover: core.gray
      },

      minimumNights: {
        backgroundColor: core.white,
        backgroundColor_active: core.white,
        backgroundColor_hover: core.white,
        borderColor: core.borderLighter,
        color: core.grayLighter,
        color_active: core.grayLighter,
        color_hover: core.grayLighter
      },

      hoveredSpan: {
        backgroundColor: core.primaryShade_4,
        backgroundColor_active: core.primaryShade_3,
        backgroundColor_hover: core.primaryShade_4,
        borderColor: core.primaryShade_3,
        borderColor_active: core.primaryShade_3,
        borderColor_hover: core.primaryShade_3,
        color: core.secondary,
        color_active: core.secondary,
        color_hover: core.secondary
      },

      selectedSpan: {
        backgroundColor: core.primaryShade_2,
        backgroundColor_active: core.primaryShade_1,
        backgroundColor_hover: core.primaryShade_1,
        borderColor: core.primaryShade_1,
        borderColor_active: core.primary,
        borderColor_hover: core.primary,
        color: core.white,
        color_active: core.white,
        color_hover: core.white
      },

      selected: {
        backgroundColor: core.primary,
        backgroundColor_active: core.primary,
        backgroundColor_hover: core.primary,
        borderColor: core.primary,
        borderColor_active: core.primary,
        borderColor_hover: core.primary,
        color: core.white,
        color_active: core.white,
        color_hover: core.white
      },

      blocked_calendar: {
        backgroundColor: core.grayLighter,
        backgroundColor_active: core.grayLighter,
        backgroundColor_hover: core.grayLighter,
        borderColor: core.grayLighter,
        borderColor_active: core.grayLighter,
        borderColor_hover: core.grayLighter,
        color: core.grayLight,
        color_active: core.grayLight,
        color_hover: core.grayLight
      },

      blocked_out_of_range: {
        backgroundColor: core.white,
        backgroundColor_active: core.white,
        backgroundColor_hover: core.white,
        borderColor: core.borderLight,
        borderColor_active: core.borderLight,
        borderColor_hover: core.borderLight,
        color: core.grayLighter,
        color_active: core.grayLighter,
        color_hover: core.grayLighter
      }
    },

    spacing: {
      dayPickerHorizontalPadding: 9,
      captionPaddingTop: 22,
      captionPaddingBottom: 37,
      inputPadding: 0,
      displayTextPaddingVertical: undefined,
      displayTextPaddingTop: 11,
      displayTextPaddingBottom: 9,
      displayTextPaddingHorizontal: undefined,
      displayTextPaddingLeft: 11,
      displayTextPaddingRight: 11,
      displayTextPaddingVertical_small: undefined,
      displayTextPaddingTop_small: 7,
      displayTextPaddingBottom_small: 5,
      displayTextPaddingHorizontal_small: undefined,
      displayTextPaddingLeft_small: 7,
      displayTextPaddingRight_small: 7
    },

    sizing: {
      inputWidth: 130,
      inputWidth_small: 97,
      arrowWidth: 24
    },

    noScrollBarOnVerticalScrollable: false,

    font: {
      size: 14,
      captionSize: 18,
      input: {
        size: 19,
        lineHeight: '24px',
        size_small: 15,
        lineHeight_small: '18px',
        letterSpacing_small: '0.2px',
        styleDisabled: 'italic'
      }
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/calculateDimension.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = calculateDimension;
function calculateDimension(el, axis) {
  var borderBox = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  var withMargin = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;

  if (!el) {
    return 0;
  }

  var axisStart = axis === 'width' ? 'Left' : 'Top';
  var axisEnd = axis === 'width' ? 'Right' : 'Bottom';

  // Only read styles if we need to
  var style = !borderBox || withMargin ? window.getComputedStyle(el) : null;

  // Offset includes border and padding
  var offsetWidth = el.offsetWidth,
      offsetHeight = el.offsetHeight;

  var size = axis === 'width' ? offsetWidth : offsetHeight;

  // Get the inner size
  if (!borderBox) {
    size -= parseFloat(style['padding' + axisStart]) + parseFloat(style['padding' + axisEnd]) + parseFloat(style['border' + axisStart + 'Width']) + parseFloat(style['border' + axisEnd + 'Width']);
  }

  // Apply margin
  if (withMargin) {
    size += parseFloat(style['margin' + axisStart]) + parseFloat(style['margin' + axisEnd]);
  }

  return size;
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getActiveElement.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getActiveElement;
function getActiveElement() {
  return typeof document !== 'undefined' && document.activeElement;
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarDaySettings.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getCalendarDaySettings;

var _getPhrase = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrase.js");

var _getPhrase2 = _interopRequireDefault(_getPhrase);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function getCalendarDaySettings(day, ariaLabelFormat, daySize, modifiers, phrases) {
  var chooseAvailableDate = phrases.chooseAvailableDate,
      dateIsUnavailable = phrases.dateIsUnavailable,
      dateIsSelected = phrases.dateIsSelected;


  var daySizeStyles = {
    width: daySize,
    height: daySize - 1
  };

  var useDefaultCursor = modifiers.has('blocked-minimum-nights') || modifiers.has('blocked-calendar') || modifiers.has('blocked-out-of-range');

  var selected = modifiers.has('selected') || modifiers.has('selected-start') || modifiers.has('selected-end');

  var hoveredSpan = !selected && (modifiers.has('hovered-span') || modifiers.has('after-hovered-start'));

  var isOutsideRange = modifiers.has('blocked-out-of-range');

  var formattedDate = { date: day.format(ariaLabelFormat) };

  var ariaLabel = (0, _getPhrase2['default'])(chooseAvailableDate, formattedDate);
  if (modifiers.has(_constants.BLOCKED_MODIFIER)) {
    ariaLabel = (0, _getPhrase2['default'])(dateIsUnavailable, formattedDate);
  } else if (selected) {
    ariaLabel = (0, _getPhrase2['default'])(dateIsSelected, formattedDate);
  }

  return {
    daySizeStyles: daySizeStyles,
    useDefaultCursor: useDefaultCursor,
    selected: selected,
    hoveredSpan: hoveredSpan,
    isOutsideRange: isOutsideRange,
    ariaLabel: ariaLabel
  };
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarMonthWeeks.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getCalendarMonthWeeks;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function getCalendarMonthWeeks(month, enableOutsideDays) {
  var firstDayOfWeek = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : _moment2['default'].localeData().firstDayOfWeek();

  if (!_moment2['default'].isMoment(month) || !month.isValid()) {
    throw new TypeError('`month` must be a valid moment object');
  }
  if (_constants.WEEKDAYS.indexOf(firstDayOfWeek) === -1) {
    throw new TypeError('`firstDayOfWeek` must be an integer between 0 and 6');
  }

  // set utc offset to get correct dates in future (when timezone changes)
  var firstOfMonth = month.clone().startOf('month').hour(12);
  var lastOfMonth = month.clone().endOf('month').hour(12);

  // calculate the exact first and last days to fill the entire matrix
  // (considering days outside month)
  var prevDays = (firstOfMonth.day() + 7 - firstDayOfWeek) % 7;
  var nextDays = (firstDayOfWeek + 6 - lastOfMonth.day()) % 7;
  var firstDay = firstOfMonth.clone().subtract(prevDays, 'day');
  var lastDay = lastOfMonth.clone().add(nextDays, 'day');

  var totalDays = lastDay.diff(firstDay, 'days') + 1;

  var currentDay = firstDay.clone();
  var weeksInMonth = [];

  for (var i = 0; i < totalDays; i += 1) {
    if (i % 7 === 0) {
      weeksInMonth.push([]);
    }

    var day = null;
    if (i >= prevDays && i < totalDays - nextDays || enableOutsideDays) {
      day = currentDay.clone();
    }

    weeksInMonth[weeksInMonth.length - 1].push(day);

    currentDay.add(1, 'day');
  }

  return weeksInMonth;
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getCalendarMonthWidth.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getCalendarMonthWidth;
function getCalendarMonthWidth(daySize, calendarMonthPadding) {
  return 7 * daySize + 2 * calendarMonthPadding + 1;
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getNumberOfCalendarMonthWeeks.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getNumberOfCalendarMonthWeeks;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function getBlankDaysBeforeFirstDay(firstDayOfMonth, firstDayOfWeek) {
  var weekDayDiff = firstDayOfMonth.day() - firstDayOfWeek;
  return (weekDayDiff + 7) % 7;
}

function getNumberOfCalendarMonthWeeks(month) {
  var firstDayOfWeek = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : _moment2['default'].localeData().firstDayOfWeek();

  var firstDayOfMonth = month.clone().startOf('month');
  var numBlankDays = getBlankDaysBeforeFirstDay(firstDayOfMonth, firstDayOfWeek);
  return Math.ceil((numBlankDays + month.daysInMonth()) / 7);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrase.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getPhrase;
function getPhrase(phrase, args) {
  if (typeof phrase === 'string') return phrase;

  if (typeof phrase === 'function') {
    return phrase(args);
  }

  return '';
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getPhrasePropTypes.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getPhrasePropTypes;

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function getPhrasePropTypes(defaultPhrases) {
  return Object.keys(defaultPhrases).reduce(function (phrases, key) {
    return (0, _object2['default'])({}, phrases, _defineProperty({}, key, _propTypes2['default'].oneOfType([_propTypes2['default'].string, _propTypes2['default'].func, _propTypes2['default'].node])));
  }, {});
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getTransformStyles.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getTransformStyles;
function getTransformStyles(transformValue) {
  return {
    transform: transformValue,
    msTransform: transformValue,
    MozTransform: transformValue,
    WebkitTransform: transformValue
  };
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/getVisibleDays.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = getVisibleDays;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _toISOMonthString = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toISOMonthString.js");

var _toISOMonthString2 = _interopRequireDefault(_toISOMonthString);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function getVisibleDays(month, numberOfMonths, enableOutsideDays, withoutTransitionMonths) {
  if (!_moment2['default'].isMoment(month)) return {};

  var visibleDaysByMonth = {};
  var currentMonth = withoutTransitionMonths ? month.clone() : month.clone().subtract(1, 'month');
  for (var i = 0; i < (withoutTransitionMonths ? numberOfMonths : numberOfMonths + 2); i += 1) {
    var visibleDays = [];

    // set utc offset to get correct dates in future (when timezone changes)
    var baseDate = currentMonth.clone();
    var firstOfMonth = baseDate.clone().startOf('month').hour(12);
    var lastOfMonth = baseDate.clone().endOf('month').hour(12);

    var currentDay = firstOfMonth.clone();

    // days belonging to the previous month
    if (enableOutsideDays) {
      for (var j = 0; j < currentDay.weekday(); j += 1) {
        var prevDay = currentDay.clone().subtract(j + 1, 'day');
        visibleDays.unshift(prevDay);
      }
    }

    while (currentDay < lastOfMonth) {
      visibleDays.push(currentDay.clone());
      currentDay.add(1, 'day');
    }

    if (enableOutsideDays) {
      // weekday() returns the index of the day of the week according to the locale
      // this means if the week starts on Monday, weekday() will return 0 for a Monday date, not 1
      if (currentDay.weekday() !== 0) {
        // days belonging to the next month
        for (var k = currentDay.weekday(), count = 0; k < 7; k += 1, count += 1) {
          var nextDay = currentDay.clone().add(count, 'day');
          visibleDays.push(nextDay);
        }
      }
    }

    visibleDaysByMonth[(0, _toISOMonthString2['default'])(currentMonth)] = visibleDays;
    currentMonth = currentMonth.clone().add(1, 'month');
  }

  return visibleDaysByMonth;
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isAfterDay.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isAfterDay;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _isBeforeDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isBeforeDay.js");

var _isBeforeDay2 = _interopRequireDefault(_isBeforeDay);

var _isSameDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isSameDay.js");

var _isSameDay2 = _interopRequireDefault(_isSameDay);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function isAfterDay(a, b) {
  if (!_moment2['default'].isMoment(a) || !_moment2['default'].isMoment(b)) return false;
  return !(0, _isBeforeDay2['default'])(a, b) && !(0, _isSameDay2['default'])(a, b);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isBeforeDay.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isBeforeDay;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function isBeforeDay(a, b) {
  if (!_moment2['default'].isMoment(a) || !_moment2['default'].isMoment(b)) return false;

  var aYear = a.year();
  var aMonth = a.month();

  var bYear = b.year();
  var bMonth = b.month();

  var isSameYear = aYear === bYear;
  var isSameMonth = aMonth === bMonth;

  if (isSameYear && isSameMonth) return a.date() < b.date();
  if (isSameYear) return aMonth < bMonth;
  return aYear < bYear;
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isDayVisible.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isDayVisible;

var _isBeforeDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isBeforeDay.js");

var _isBeforeDay2 = _interopRequireDefault(_isBeforeDay);

var _isAfterDay = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isAfterDay.js");

var _isAfterDay2 = _interopRequireDefault(_isAfterDay);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function isDayVisible(day, month, numberOfMonths, enableOutsideDays) {
  var firstDayOfFirstMonth = month.clone().startOf('month');
  if (enableOutsideDays) firstDayOfFirstMonth = firstDayOfFirstMonth.startOf('week');
  if ((0, _isBeforeDay2['default'])(day, firstDayOfFirstMonth)) return false;

  var lastDayOfLastMonth = month.clone().add(numberOfMonths - 1, 'months').endOf('month');
  if (enableOutsideDays) lastDayOfLastMonth = lastDayOfLastMonth.endOf('week');
  return !(0, _isAfterDay2['default'])(day, lastDayOfLastMonth);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isNextMonth.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isNextMonth;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _isSameMonth = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isSameMonth.js");

var _isSameMonth2 = _interopRequireDefault(_isSameMonth);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function isNextMonth(a, b) {
  if (!_moment2['default'].isMoment(a) || !_moment2['default'].isMoment(b)) return false;
  return (0, _isSameMonth2['default'])(a.clone().add(1, 'month'), b);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isPrevMonth.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isPrevMonth;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _isSameMonth = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isSameMonth.js");

var _isSameMonth2 = _interopRequireDefault(_isSameMonth);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function isPrevMonth(a, b) {
  if (!_moment2['default'].isMoment(a) || !_moment2['default'].isMoment(b)) return false;
  return (0, _isSameMonth2['default'])(a.clone().subtract(1, 'month'), b);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isSameDay.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isSameDay;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function isSameDay(a, b) {
  if (!_moment2['default'].isMoment(a) || !_moment2['default'].isMoment(b)) return false;
  // Compare least significant, most likely to change units first
  // Moment's isSame clones moment inputs and is a tad slow
  return a.date() === b.date() && a.month() === b.month() && a.year() === b.year();
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isSameMonth.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isSameMonth;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function isSameMonth(a, b) {
  if (!_moment2['default'].isMoment(a) || !_moment2['default'].isMoment(b)) return false;
  // Compare least significant, most likely to change units first
  // Moment's isSame clones moment inputs and is a tad slow
  return a.month() === b.month() && a.year() === b.year();
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/isTransitionEndSupported.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = isTransitionEndSupported;
function isTransitionEndSupported() {
  return !!(typeof window !== 'undefined' && 'TransitionEvent' in window);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toISODateString.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = toISODateString;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _toMomentObject = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toMomentObject.js");

var _toMomentObject2 = _interopRequireDefault(_toMomentObject);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function toISODateString(date, currentFormat) {
  var dateObj = _moment2['default'].isMoment(date) ? date : (0, _toMomentObject2['default'])(date, currentFormat);
  if (!dateObj) return null;

  return dateObj.format(_constants.ISO_FORMAT);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toISOMonthString.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = toISOMonthString;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _toMomentObject = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toMomentObject.js");

var _toMomentObject2 = _interopRequireDefault(_toMomentObject);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function toISOMonthString(date, currentFormat) {
  var dateObj = _moment2['default'].isMoment(date) ? date : (0, _toMomentObject2['default'])(date, currentFormat);
  if (!dateObj) return null;

  return dateObj.format(_constants.ISO_MONTH_FORMAT);
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/utils/toMomentObject.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = toMomentObject;

var _moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");

var _moment2 = _interopRequireDefault(_moment);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-dates@17.2.0_moment@2.29.4_react-dom@17.0.2_react@17.0.2__react-with-direction@1.4.0_re_2kzes426n2jkup7alm55dynoru/node_modules/react-dates/lib/constants.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function toMomentObject(dateString, customFormat) {
  var dateFormats = customFormat ? [customFormat, _constants.DISPLAY_FORMAT, _constants.ISO_FORMAT] : [_constants.DISPLAY_FORMAT, _constants.ISO_FORMAT];

  var date = (0, _moment2['default'])(dateString, dateFormats, true);
  return date.isValid() ? date.hour(12) : null;
}

/***/ }),

/***/ "../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/ThemedStyleSheet.js":
/***/ ((__unused_webpack_module, exports) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var styleInterface = void 0;
var styleTheme = void 0;

var START_MARK = 'react-with-styles.resolve.start';
var END_MARK = 'react-with-styles.resolve.end';
var MEASURE_MARK = '\uD83D\uDC69\u200D\uD83C\uDFA8 [resolve]';

function registerTheme(theme) {
  styleTheme = theme;
}

function registerInterface(interfaceToRegister) {
  styleInterface = interfaceToRegister;
}

function create(makeFromTheme, createWithDirection) {
  var styles = createWithDirection(makeFromTheme(styleTheme));
  return function () {
    return styles;
  };
}

function createLTR(makeFromTheme) {
  return create(makeFromTheme, styleInterface.createLTR || styleInterface.create);
}

function createRTL(makeFromTheme) {
  return create(makeFromTheme, styleInterface.createRTL || styleInterface.create);
}

function get() {
  return styleTheme;
}

function resolve() {
  if (false) {}

  for (var _len = arguments.length, styles = Array(_len), _key = 0; _key < _len; _key++) {
    styles[_key] = arguments[_key];
  }

  var result = styleInterface.resolve(styles);

  if (false) {}

  return result;
}

function resolveLTR() {
  for (var _len2 = arguments.length, styles = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
    styles[_key2] = arguments[_key2];
  }

  if (styleInterface.resolveLTR) {
    return styleInterface.resolveLTR(styles);
  }

  return resolve(styles);
}

function resolveRTL() {
  for (var _len3 = arguments.length, styles = Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
    styles[_key3] = arguments[_key3];
  }

  if (styleInterface.resolveRTL) {
    return styleInterface.resolveRTL(styles);
  }

  return resolve(styles);
}

function flush() {
  if (styleInterface.flush) {
    styleInterface.flush();
  }
}

exports["default"] = {
  registerTheme: registerTheme,
  registerInterface: registerInterface,
  create: createLTR,
  createLTR: createLTR,
  createRTL: createRTL,
  get: get,
  resolve: resolveLTR,
  resolveLTR: resolveLTR,
  resolveRTL: resolveRTL,
  flush: flush
};

/***/ }),

/***/ "../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/withStyles.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.withStylesPropTypes = exports.css = undefined;

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

exports.withStyles = withStyles;

var _object = __webpack_require__("../../node_modules/.pnpm/object.assign@4.1.5/node_modules/object.assign/index.js");

var _object2 = _interopRequireDefault(_object);

var _react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var _react2 = _interopRequireDefault(_react);

var _propTypes = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");

var _propTypes2 = _interopRequireDefault(_propTypes);

var _hoistNonReactStatics = __webpack_require__("../../node_modules/.pnpm/hoist-non-react-statics@3.3.2/node_modules/hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js");

var _hoistNonReactStatics2 = _interopRequireDefault(_hoistNonReactStatics);

var _constants = __webpack_require__("../../node_modules/.pnpm/react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-with-direction/dist/constants.js");

var _brcast = __webpack_require__("../../node_modules/.pnpm/react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-with-direction/dist/proptypes/brcast.js");

var _brcast2 = _interopRequireDefault(_brcast);

var _ThemedStyleSheet = __webpack_require__("../../node_modules/.pnpm/react-with-styles@3.2.3_react-with-direction@1.4.0_react-dom@17.0.2_react@17.0.2__react@17.0.2__react@17.0.2/node_modules/react-with-styles/lib/ThemedStyleSheet.js");

var _ThemedStyleSheet2 = _interopRequireDefault(_ThemedStyleSheet);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; } /* eslint react/forbid-foreign-prop-types: off */

// Add some named exports to assist in upgrading and for convenience
var css = exports.css = _ThemedStyleSheet2['default'].resolveLTR;
var withStylesPropTypes = exports.withStylesPropTypes = {
  styles: _propTypes2['default'].object.isRequired, // eslint-disable-line react/forbid-prop-types
  theme: _propTypes2['default'].object.isRequired, // eslint-disable-line react/forbid-prop-types
  css: _propTypes2['default'].func.isRequired
};

var EMPTY_STYLES = {};
var EMPTY_STYLES_FN = function EMPTY_STYLES_FN() {
  return EMPTY_STYLES;
};

var START_MARK = 'react-with-styles.createStyles.start';
var END_MARK = 'react-with-styles.createStyles.end';

function baseClass(pureComponent) {
  if (pureComponent) {
    if (!_react2['default'].PureComponent) {
      throw new ReferenceError('withStyles() pureComponent option requires React 15.3.0 or later');
    }

    return _react2['default'].PureComponent;
  }

  return _react2['default'].Component;
}

var contextTypes = _defineProperty({}, _constants.CHANNEL, _brcast2['default']);

var defaultDirection = _constants.DIRECTIONS.LTR;

function withStyles(styleFn) {
  var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
      _ref$stylesPropName = _ref.stylesPropName,
      stylesPropName = _ref$stylesPropName === undefined ? 'styles' : _ref$stylesPropName,
      _ref$themePropName = _ref.themePropName,
      themePropName = _ref$themePropName === undefined ? 'theme' : _ref$themePropName,
      _ref$cssPropName = _ref.cssPropName,
      cssPropName = _ref$cssPropName === undefined ? 'css' : _ref$cssPropName,
      _ref$flushBefore = _ref.flushBefore,
      flushBefore = _ref$flushBefore === undefined ? false : _ref$flushBefore,
      _ref$pureComponent = _ref.pureComponent,
      pureComponent = _ref$pureComponent === undefined ? false : _ref$pureComponent;

  var styleDefLTR = void 0;
  var styleDefRTL = void 0;
  var currentThemeLTR = void 0;
  var currentThemeRTL = void 0;
  var BaseClass = baseClass(pureComponent);

  function getResolveMethod(direction) {
    return direction === _constants.DIRECTIONS.LTR ? _ThemedStyleSheet2['default'].resolveLTR : _ThemedStyleSheet2['default'].resolveRTL;
  }

  function getCurrentTheme(direction) {
    return direction === _constants.DIRECTIONS.LTR ? currentThemeLTR : currentThemeRTL;
  }

  function getStyleDef(direction, wrappedComponentName) {
    var currentTheme = getCurrentTheme(direction);
    var styleDef = direction === _constants.DIRECTIONS.LTR ? styleDefLTR : styleDefRTL;

    var registeredTheme = _ThemedStyleSheet2['default'].get();

    // Return the existing styles if they've already been defined
    // and if the theme used to create them corresponds to the theme
    // registered with ThemedStyleSheet
    if (styleDef && currentTheme === registeredTheme) {
      return styleDef;
    }

    if (false) {}

    var isRTL = direction === _constants.DIRECTIONS.RTL;

    if (isRTL) {
      styleDefRTL = styleFn ? _ThemedStyleSheet2['default'].createRTL(styleFn) : EMPTY_STYLES_FN;

      currentThemeRTL = registeredTheme;
      styleDef = styleDefRTL;
    } else {
      styleDefLTR = styleFn ? _ThemedStyleSheet2['default'].createLTR(styleFn) : EMPTY_STYLES_FN;

      currentThemeLTR = registeredTheme;
      styleDef = styleDefLTR;
    }

    if (false) { var measureName; }

    return styleDef;
  }

  function getState(direction, wrappedComponentName) {
    return {
      resolveMethod: getResolveMethod(direction),
      styleDef: getStyleDef(direction, wrappedComponentName)
    };
  }

  return function () {
    function withStylesHOC(WrappedComponent) {
      var wrappedComponentName = WrappedComponent.displayName || WrappedComponent.name || 'Component';

      // NOTE: Use a class here so components are ref-able if need be:
      // eslint-disable-next-line react/prefer-stateless-function

      var WithStyles = function (_BaseClass) {
        _inherits(WithStyles, _BaseClass);

        function WithStyles(props, context) {
          _classCallCheck(this, WithStyles);

          var _this = _possibleConstructorReturn(this, (WithStyles.__proto__ || Object.getPrototypeOf(WithStyles)).call(this, props, context));

          var direction = _this.context[_constants.CHANNEL] ? _this.context[_constants.CHANNEL].getState() : defaultDirection;

          _this.state = getState(direction, wrappedComponentName);
          return _this;
        }

        _createClass(WithStyles, [{
          key: 'componentDidMount',
          value: function () {
            function componentDidMount() {
              var _this2 = this;

              if (this.context[_constants.CHANNEL]) {
                // subscribe to future direction changes
                this.channelUnsubscribe = this.context[_constants.CHANNEL].subscribe(function (direction) {
                  _this2.setState(getState(direction, wrappedComponentName));
                });
              }
            }

            return componentDidMount;
          }()
        }, {
          key: 'componentWillUnmount',
          value: function () {
            function componentWillUnmount() {
              if (this.channelUnsubscribe) {
                this.channelUnsubscribe();
              }
            }

            return componentWillUnmount;
          }()
        }, {
          key: 'render',
          value: function () {
            function render() {
              var _ref2;

              // As some components will depend on previous styles in
              // the component tree, we provide the option of flushing the
              // buffered styles (i.e. to a style tag) **before** the rendering
              // cycle begins.
              //
              // The interfaces provide the optional "flush" method which
              // is run in turn by ThemedStyleSheet.flush.
              if (flushBefore) {
                _ThemedStyleSheet2['default'].flush();
              }

              var _state = this.state,
                  resolveMethod = _state.resolveMethod,
                  styleDef = _state.styleDef;


              return _react2['default'].createElement(WrappedComponent, _extends({}, this.props, (_ref2 = {}, _defineProperty(_ref2, themePropName, _ThemedStyleSheet2['default'].get()), _defineProperty(_ref2, stylesPropName, styleDef()), _defineProperty(_ref2, cssPropName, resolveMethod), _ref2)));
            }

            return render;
          }()
        }]);

        return WithStyles;
      }(BaseClass);

      WithStyles.WrappedComponent = WrappedComponent;
      WithStyles.displayName = 'withStyles(' + String(wrappedComponentName) + ')';
      WithStyles.contextTypes = contextTypes;
      if (WrappedComponent.propTypes) {
        WithStyles.propTypes = (0, _object2['default'])({}, WrappedComponent.propTypes);
        delete WithStyles.propTypes[stylesPropName];
        delete WithStyles.propTypes[themePropName];
        delete WithStyles.propTypes[cssPropName];
      }
      if (WrappedComponent.defaultProps) {
        WithStyles.defaultProps = (0, _object2['default'])({}, WrappedComponent.defaultProps);
      }

      return (0, _hoistNonReactStatics2['default'])(WithStyles, WrappedComponent);
    }

    return withStylesHOC;
  }();
}

/***/ })

}]);