(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["activity-panels-inbox"],{

/***/ "./client/header/activity-panel/panels/reviews.js":
/*!********************************************************!*\
  !*** ./client/header/activity-panel/panels/reviews.js ***!
  \********************************************************/
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
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! gridicons */ "./node_modules/gridicons/dist/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _activity_card__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../activity-card */ "./client/header/activity-panel/activity-card/index.js");
/* harmony import */ var _activity_header__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../activity-header */ "./client/header/activity-panel/activity-header/index.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var lib_sanitize_html__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! lib/sanitize-html */ "./client/lib/sanitize-html/index.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");







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








var ReviewsPanel = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(ReviewsPanel, _Component);

  var _super = _createSuper(ReviewsPanel);

  function ReviewsPanel() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ReviewsPanel);

    _this = _super.call(this);
    _this.mountTime = new Date().getTime();
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ReviewsPanel, [{
    key: "renderReview",
    value: function renderReview(review, props) {
      var lastRead = props.lastRead;
      var product = review && review._embedded && review._embedded.up && review._embedded.up[0] || null;

      if (Object(lodash__WEBPACK_IMPORTED_MODULE_11__["isNull"])(product)) {
        return null;
      }

      var title = interpolate_components__WEBPACK_IMPORTED_MODULE_10___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('{{productLink}}%s{{/productLink}} reviewed by {{authorLink}}%s{{/authorLink}}', 'woocommerce'), product.name, review.reviewer),
        components: {
          productLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Link"], {
            href: product.permalink,
            type: "external"
          }),
          authorLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Link"], {
            href: 'mailto:' + review.reviewer_email,
            type: "external"
          })
        }
      });
      var subtitle = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["ReviewRating"], {
        review: review
      }), review.verified && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", {
        className: "woocommerce-review-activity-card__verified"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_9___default.a, {
        icon: "checkmark",
        size: 18
      }), Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Verified customer', 'woocommerce')));
      var productImage = Object(lodash__WEBPACK_IMPORTED_MODULE_11__["get"])(product, ['images', 0]) || Object(lodash__WEBPACK_IMPORTED_MODULE_11__["get"])(product, ['image']);
      var productImageClasses = classnames__WEBPACK_IMPORTED_MODULE_7___default()('woocommerce-review-activity-card__image-overlay__product', {
        'is-placeholder': !productImage || !productImage.src
      });
      var icon = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-review-activity-card__image-overlay"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Gravatar"], {
        user: review.reviewer_email,
        size: 24
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: productImageClasses
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["ProductImage"], {
        product: product
      })));
      var manageReviewEvent = {
        date: review.date_created_gmt,
        status: review.status
      };
      var cardActions = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
        isSecondary: true,
        onClick: function onClick() {
          return Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('review_manage_click', manageReviewEvent);
        },
        href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__["getAdminLink"])('comment.php?action=editcomment&c=' + review.id)
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Manage', 'woocommerce'));
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_15__["ActivityCard"], {
        className: "woocommerce-review-activity-card",
        key: review.id,
        title: title,
        subtitle: subtitle,
        date: review.date_created_gmt,
        icon: icon,
        actions: cardActions,
        unread: review.status === 'hold' || !lastRead || !review.date_created_gmt || new Date(review.date_created_gmt + 'Z').getTime() > lastRead
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", {
        dangerouslySetInnerHTML: Object(lib_sanitize_html__WEBPACK_IMPORTED_MODULE_18__["default"])(review.review)
      }));
    }
  }, {
    key: "renderEmptyMessage",
    value: function renderEmptyMessage() {
      var lastApprovedReviewTime = this.props.lastApprovedReviewTime;

      var title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('You have no reviews to moderate', 'woocommerce');

      var buttonUrl = '';
      var buttonTarget = '';
      var buttonText = '';
      var content = '';

      if (lastApprovedReviewTime) {
        var now = new Date();
        var DAY = 24 * 60 * 60 * 1000;

        if ((now.getTime() - lastApprovedReviewTime) / DAY > 30) {
          buttonUrl = 'https://woocommerce.com/posts/reviews-woocommerce-best-practices/';
          buttonTarget = '_blank';
          buttonText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Learn more', 'woocommerce');
          content = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("We noticed that it's been a while since your products had any reviews.", 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Take some time to learn about best practices for collecting and using your reviews.', 'woocommerce')));
        } else {
          buttonUrl = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__["getAdminLink"])('edit-comments.php?comment_type=review');
          buttonText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('View all Reviews', 'woocommerce');
          content = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])(
          /* eslint-disable max-len */
          "Awesome, you've moderated all of your product reviews. How about responding to some of those negative reviews?", 'woocommerce'
          /* eslint-enable */
          ));
        }
      } else {
        buttonUrl = 'https://woocommerce.com/posts/reviews-woocommerce-best-practices/';
        buttonTarget = '_blank';
        buttonText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Learn more', 'woocommerce');
        content = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("Your customers haven't started reviewing your products.", 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Take some time to learn about best practices for collecting and using your reviews.', 'woocommerce')));
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_15__["ActivityCard"], {
        className: "woocommerce-empty-activity-card",
        title: title,
        icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_9___default.a, {
          icon: "time",
          size: 48
        }),
        actions: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
          href: buttonUrl,
          target: buttonTarget,
          isSecondary: true
        }, buttonText)
      }, content);
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          isError = _this$props.isError,
          isRequesting = _this$props.isRequesting,
          reviews = _this$props.reviews;

      if (isError) {
        var _title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('There was an error getting your reviews. Please try again.', 'woocommerce');

        var actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Reload', 'woocommerce');

        var actionCallback = function actionCallback() {
          window.location.reload();
        };

        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["EmptyContent"], {
          title: _title,
          actionLabel: actionLabel,
          actionURL: null,
          actionCallback: actionCallback
        }));
      }

      var title = isRequesting || reviews.length ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Reviews', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('No reviews to moderate', 'woocommerce');
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_header__WEBPACK_IMPORTED_MODULE_16__["default"], {
        title: title
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Section"], null, isRequesting ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_15__["ActivityCardPlaceholder"], {
        className: "woocommerce-review-activity-card",
        hasAction: true,
        hasDate: true,
        lines: 2
      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, reviews.length ? reviews.map(function (review) {
        return _this2.renderReview(review, _this2.props);
      }) : this.renderEmptyMessage())));
    }
  }]);

  return ReviewsPanel;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

ReviewsPanel.propTypes = {
  reviews: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.array.isRequired,
  isError: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.bool,
  isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.bool
};
ReviewsPanel.defaultProps = {
  reviews: [],
  isError: false,
  isRequesting: false
};
/* harmony default export */ __webpack_exports__["default"] = (Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__["default"])(function (select, props) {
  var hasUnapprovedReviews = props.hasUnapprovedReviews;

  var _select = select('wc-api'),
      getReviews = _select.getReviews,
      getReviewsError = _select.getReviewsError,
      isGetReviewsRequesting = _select.isGetReviewsRequesting;

  var reviews = [];
  var isError = false;
  var isRequesting = false;
  var lastApprovedReviewTime = null;

  if (hasUnapprovedReviews) {
    var reviewsQuery = {
      page: 1,
      per_page: wc_api_constants__WEBPACK_IMPORTED_MODULE_17__["QUERY_DEFAULTS"].pageSize,
      status: 'hold',
      _embed: 1
    };
    reviews = getReviews(reviewsQuery);
    isError = Boolean(getReviewsError(reviewsQuery));
    isRequesting = isGetReviewsRequesting(reviewsQuery);
  } else {
    var approvedReviewsQuery = {
      page: 1,
      per_page: 1,
      status: 'approved',
      _embed: 1
    };
    var approvedReviews = getReviews(approvedReviewsQuery);

    if (approvedReviews.length) {
      var lastApprovedReview = approvedReviews[0];

      if (lastApprovedReview.date_created_gmt) {
        var creationDate = new Date(lastApprovedReview.date_created_gmt);
        lastApprovedReviewTime = creationDate.getTime();
      }
    }

    isError = Boolean(getReviewsError(approvedReviewsQuery));
    isRequesting = isGetReviewsRequesting(approvedReviewsQuery);
  }

  return {
    reviews: reviews,
    isError: isError,
    isRequesting: isRequesting,
    lastApprovedReviewTime: lastApprovedReviewTime
  };
})(ReviewsPanel));

/***/ })

}]);
//# sourceMappingURL=activity-panels-inbox.6876970a41c2e9eb7bee.min.js.map
