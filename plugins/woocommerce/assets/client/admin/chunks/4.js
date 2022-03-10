(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[4],{

/***/ 547:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* reexport */ getPluginSlug; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ getPluginTrackKey; });
__webpack_require__.d(__webpack_exports__, "d", function() { return /* binding */ getUrlParams; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ getScreenName; });

// UNUSED EXPORTS: sift

// CONCATENATED MODULE: ./client/utils/plugins.ts
function getPluginSlug(id) {
  return (id || '').split(':', 1)[0];
}
function getPluginTrackKey(id) {
  const slug = getPluginSlug(id);
  const key = /^woocommerce(-|_)payments$/.test(slug) ? 'wcpay' : `${slug.replace(/-/g, '_')}`.split(':', 1)[0];
  return key;
}
// CONCATENATED MODULE: ./client/utils/index.js

/**
 * Get the URL params.
 *
 * @param {string} locationSearch - Querystring part of a URL, including the question mark (?).
 * @return {Object} - URL params.
 */

function getUrlParams(locationSearch) {
  if (locationSearch) {
    return locationSearch.substr(1).split('&').reduce((params, query) => {
      const chunks = query.split('=');
      const key = chunks[0];
      let value = decodeURIComponent(chunks[1]);
      value = isNaN(Number(value)) ? value : Number(value);
      return params[key] = value, params;
    }, {});
  }

  return {};
}
/**
 * Get the current screen name.
 *
 * @return {string} - Screen name.
 */

function getScreenName() {
  let screenName = '';
  const {
    page,
    path,
    post_type: postType
  } = getUrlParams(window.location.search);

  if (page) {
    const currentPage = page === 'wc-admin' ? 'home_screen' : page;
    screenName = path ? path.replace(/\//g, '_').substring(1) : currentPage;
  } else if (postType) {
    screenName = postType;
  }

  return screenName;
}
/**
 * Similar to filter, but return two arrays separated by a partitioner function
 *
 * @param {Array} arr - Original array of values.
 * @param {Function} partitioner - Function to return truthy/falsy values to separate items in array.
 *
 * @return {Array} - Array of two arrays, first including truthy values, and second including falsy.
 */

const sift = (arr, partitioner) => arr.reduce((all, curr) => {
  all[!!partitioner(curr) ? 0 : 1].push(curr);
  return all;
}, [[], []]);

/***/ }),

/***/ 550:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ activity_card_ActivityCard; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ placeholder; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/notice-outline.js
var notice_outline = __webpack_require__(582);
var notice_outline_default = /*#__PURE__*/__webpack_require__.n(notice_outline);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(11);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: ./client/activity-panel/activity-card/style.scss
var style = __webpack_require__(583);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// CONCATENATED MODULE: ./client/activity-panel/activity-card/placeholder.js


/**
 * External dependencies
 */





class placeholder_ActivityCardPlaceholder extends external_wp_element_["Component"] {
  render() {
    const {
      className,
      hasAction,
      hasDate,
      hasSubtitle,
      lines
    } = this.props;
    const cardClassName = classnames_default()('woocommerce-activity-card is-loading', className);
    return Object(external_wp_element_["createElement"])("div", {
      className: cardClassName,
      "aria-hidden": true
    }, Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-activity-card__icon"
    }, Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    })), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-activity-card__header"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-activity-card__title is-placeholder"
    }), hasSubtitle && Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-activity-card__subtitle is-placeholder"
    }), hasDate && Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-activity-card__date"
    }, Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    }))), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-activity-card__body"
    }, Object(external_lodash_["range"])(lines).map(i => Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder",
      key: i
    }))), hasAction && Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-activity-card__actions"
    }, Object(external_wp_element_["createElement"])("span", {
      className: "is-placeholder"
    })));
  }

}

placeholder_ActivityCardPlaceholder.propTypes = {
  className: prop_types_default.a.string,
  hasAction: prop_types_default.a.bool,
  hasDate: prop_types_default.a.bool,
  hasSubtitle: prop_types_default.a.bool,
  lines: prop_types_default.a.number
};
placeholder_ActivityCardPlaceholder.defaultProps = {
  hasAction: false,
  hasDate: false,
  hasSubtitle: false,
  lines: 1
};
/* harmony default export */ var placeholder = (placeholder_ActivityCardPlaceholder);
// CONCATENATED MODULE: ./client/activity-panel/activity-card/index.js


/**
 * External dependencies
 */







/**
 * Internal dependencies
 */


/**
 * Determine if the provided string is a date, as
 * formatted by wc_rest_prepare_date_response().
 *
 * @param {string} value String value
 */

const isDateString = value => // PHP date format: Y-m-d\TH:i:s.
/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value);

class activity_card_ActivityCard extends external_wp_element_["Component"] {
  getCard() {
    const {
      actions,
      className,
      children,
      date,
      icon,
      subtitle,
      title,
      unread
    } = this.props;
    const cardClassName = classnames_default()('woocommerce-activity-card', className);
    const actionsList = Array.isArray(actions) ? actions : [actions];
    const dateString = isDateString(date) ? external_moment_default.a.utc(date).fromNow() : date;
    return Object(external_wp_element_["createElement"])("section", {
      className: cardClassName
    }, unread && Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-activity-card__unread"
    }), icon && Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-activity-card__icon",
      "aria-hidden": true
    }, icon), title && Object(external_wp_element_["createElement"])("header", {
      className: "woocommerce-activity-card__header"
    }, Object(external_wp_element_["createElement"])(external_wc_components_["H"], {
      className: "woocommerce-activity-card__title"
    }, title), subtitle && Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-activity-card__subtitle"
    }, subtitle), dateString && Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-activity-card__date"
    }, dateString)), children && Object(external_wp_element_["createElement"])(external_wc_components_["Section"], {
      className: "woocommerce-activity-card__body"
    }, children), actions && Object(external_wp_element_["createElement"])("footer", {
      className: "woocommerce-activity-card__actions"
    }, actionsList.map((item, i) => Object(external_wp_element_["cloneElement"])(item, {
      key: i
    }))));
  }

  render() {
    const {
      onClick
    } = this.props;

    if (onClick) {
      return Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        className: "woocommerce-activity-card__button",
        onClick: onClick
      }, this.getCard());
    }

    return this.getCard();
  }

}

activity_card_ActivityCard.propTypes = {
  actions: prop_types_default.a.oneOfType([prop_types_default.a.arrayOf(prop_types_default.a.element), prop_types_default.a.element]),
  onClick: prop_types_default.a.func,
  className: prop_types_default.a.string,
  children: prop_types_default.a.node,
  date: prop_types_default.a.string,
  icon: prop_types_default.a.node,
  subtitle: prop_types_default.a.node,
  title: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.node]),
  unread: prop_types_default.a.bool
};
activity_card_ActivityCard.defaultProps = {
  icon: Object(external_wp_element_["createElement"])(notice_outline_default.a, {
    size: 48
  }),
  unread: false
};



/***/ }),

/***/ 574:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js + 1 modules
var TransitionGroup = __webpack_require__(520);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/CSSTransition.js + 2 modules
var CSSTransition = __webpack_require__(513);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(11);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: ./client/activity-panel/activity-card/index.js + 1 modules
var activity_card = __webpack_require__(550);

// EXTERNAL MODULE: ./client/inbox-panel/utils.js
var utils = __webpack_require__(187);

// EXTERNAL MODULE: ./client/utils/index.js + 1 modules
var client_utils = __webpack_require__(547);

// CONCATENATED MODULE: ./client/inbox-panel/dismiss-all-modal.js


/**
 * External dependencies
 */






const DismissAllModal = _ref => {
  let {
    onClose
  } = _ref;
  const {
    createNotice
  } = Object(external_wp_data_["useDispatch"])('core/notices');
  const {
    batchUpdateNotes,
    removeAllNotes
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["NOTES_STORE_NAME"]);

  const dismissAllNotes = async () => {
    Object(external_wc_tracks_["recordEvent"])('wcadmin_inbox_action_dismissall', {});

    try {
      const notesRemoved = await removeAllNotes({
        status: 'unactioned'
      });
      createNotice('success', Object(external_wp_i18n_["__"])('All messages dismissed', 'woocommerce-admin'), {
        actions: [{
          label: Object(external_wp_i18n_["__"])('Undo', 'woocommerce-admin'),
          onClick: () => {
            batchUpdateNotes(notesRemoved.map(note => note.id), {
              is_deleted: 0
            });
          }
        }]
      });
    } catch (e) {
      createNotice('error', Object(external_wp_i18n_["__"])('Messages could not be dismissed', 'woocommerce-admin'));
      onClose();
    }
  };

  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["Modal"], {
    title: Object(external_wp_i18n_["__"])('Dismiss all messages', 'woocommerce-admin'),
    className: "woocommerce-inbox-dismiss-all-modal",
    onRequestClose: onClose
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-inbox-dismiss-all-modal__wrapper"
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-usage-modal__message"
  }, Object(external_wp_i18n_["__"])('Are you sure? Inbox messages will be dismissed forever.', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-usage-modal__actions"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    onClick: onClose
  }, Object(external_wp_i18n_["__"])('Cancel', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isPrimary: true,
    onClick: () => {
      dismissAllNotes();
      onClose();
    }
  }, Object(external_wp_i18n_["__"])('Yes, dismiss all', 'woocommerce-admin'))))));
};

/* harmony default export */ var dismiss_all_modal = (DismissAllModal);
// EXTERNAL MODULE: ./client/inbox-panel/index.scss
var inbox_panel = __webpack_require__(588);

// CONCATENATED MODULE: ./client/inbox-panel/index.js


/**
 * External dependencies
 */










/**
 * Internal dependencies
 */







const renderEmptyCard = () => Object(external_wp_element_["createElement"])(activity_card["a" /* ActivityCard */], {
  className: "woocommerce-empty-activity-card",
  title: Object(external_wp_i18n_["__"])('Your inbox is empty', 'woocommerce-admin'),
  icon: false
}, Object(external_wp_i18n_["__"])('As things begin to happen in your store your inbox will start to fill up. ' + "You'll see things like achievements, new feature announcements, extension recommendations and more!", 'woocommerce-admin'));

const onBodyLinkClick = (note, innerLink) => {
  Object(external_wc_tracks_["recordEvent"])('inbox_action_click', {
    note_name: note.name,
    note_title: note.title,
    note_content_inner_link: innerLink
  });
};

let hasFiredPanelViewTrack = false;

const renderNotes = _ref => {
  let {
    hasNotes,
    isBatchUpdating,
    notes,
    onDismiss,
    onNoteActionClick,
    setShowDismissAllModal: onDismissAll,
    showHeader = true
  } = _ref;

  if (isBatchUpdating) {
    return;
  }

  if (!hasNotes) {
    return renderEmptyCard();
  }

  if (!hasFiredPanelViewTrack) {
    Object(external_wc_tracks_["recordEvent"])('inbox_panel_view', {
      total: notes.length
    });
    hasFiredPanelViewTrack = true;
  }

  const screen = Object(client_utils["c" /* getScreenName */])();

  const onNoteVisible = note => {
    Object(external_wc_tracks_["recordEvent"])('inbox_note_view', {
      note_content: note.content,
      note_name: note.name,
      note_title: note.title,
      note_type: note.type,
      screen
    });
  };

  const notesArray = Object.keys(notes).map(key => notes[key]);
  return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
    size: "large"
  }, showHeader && Object(external_wp_element_["createElement"])(external_wp_components_["CardHeader"], {
    size: "medium"
  }, Object(external_wp_element_["createElement"])("div", {
    className: "wooocommerce-inbox-card__header"
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    size: "20",
    lineHeight: "28px",
    variant: "title.small"
  }, Object(external_wp_i18n_["__"])('Inbox', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_components_["Badge"], {
    count: notesArray.length
  })), Object(external_wp_element_["createElement"])(external_wc_components_["EllipsisMenu"], {
    label: Object(external_wp_i18n_["__"])('Inbox Notes Options', 'woocommerce-admin'),
    renderContent: _ref2 => {
      let {
        onToggle
      } = _ref2;
      return Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-card__section-controls"
      }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        onClick: () => {
          onDismissAll(true);
          onToggle();
        }
      }, Object(external_wp_i18n_["__"])('Dismiss all', 'woocommerce-admin')));
    }
  })), Object(external_wp_element_["createElement"])(TransitionGroup["a" /* default */], {
    role: "menu"
  }, notesArray.map(note => {
    const {
      id: noteId,
      is_deleted: isDeleted
    } = note;

    if (isDeleted) {
      return null;
    }

    return Object(external_wp_element_["createElement"])(CSSTransition["a" /* default */], {
      key: noteId,
      timeout: 500,
      classNames: "woocommerce-inbox-message"
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["InboxNoteCard"], {
      key: noteId,
      note: note,
      onDismiss: onDismiss,
      onNoteActionClick: onNoteActionClick,
      onBodyLinkClick: onBodyLinkClick,
      onNoteVisible: onNoteVisible
    }));
  })));
};

const INBOX_QUERY = {
  page: 1,
  per_page: external_wc_data_["QUERY_DEFAULTS"].pageSize,
  status: 'unactioned',
  type: external_wc_data_["QUERY_DEFAULTS"].noteTypes,
  orderby: 'date',
  order: 'desc',
  _fields: ['id', 'name', 'title', 'content', 'type', 'status', 'actions', 'date_created', 'date_created_gmt', 'layout', 'image', 'is_deleted', 'is_read', 'locale']
};

const InboxPanel = _ref3 => {
  let {
    showHeader = true
  } = _ref3;
  const {
    createNotice
  } = Object(external_wp_data_["useDispatch"])('core/notices');
  const {
    removeNote,
    updateNote,
    triggerNoteAction
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["NOTES_STORE_NAME"]);
  const {
    isError,
    isResolvingNotes,
    isBatchUpdating,
    notes
  } = Object(external_wp_data_["useSelect"])(select => {
    const {
      getNotes,
      getNotesError,
      isResolving,
      isNotesRequesting
    } = select(external_wc_data_["NOTES_STORE_NAME"]);
    const WC_VERSION_61_RELEASE_DATE = external_moment_default()('2022-01-11', 'YYYY-MM-DD').valueOf();
    const supportedLocales = ['en_US', 'en_AU', 'en_CA', 'en_GB', 'en_ZA'];
    return {
      notes: getNotes(INBOX_QUERY).map(note => {
        const noteDate = external_moment_default()(note.date_created_gmt, 'YYYY-MM-DD').valueOf();

        if (supportedLocales.includes(note.locale) && noteDate >= WC_VERSION_61_RELEASE_DATE) {
          return { ...note,
            content: Object(utils["c" /* truncateRenderableHTML */])(note.content, 320)
          };
        }

        return note;
      }),
      isError: Boolean(getNotesError('getNotes', [INBOX_QUERY])),
      isResolvingNotes: isResolving('getNotes', [INBOX_QUERY]),
      isBatchUpdating: isNotesRequesting('batchUpdateNotes')
    };
  });
  const [showDismissAllModal, setShowDismissAllModal] = Object(external_wp_element_["useState"])(false);

  const onDismiss = note => {
    const screen = Object(client_utils["c" /* getScreenName */])();
    Object(external_wc_tracks_["recordEvent"])('inbox_action_dismiss', {
      note_name: note.name,
      note_title: note.title,
      note_name_dismiss_all: false,
      note_name_dismiss_confirmation: true,
      screen
    });
    const noteId = note.id;

    try {
      removeNote(noteId);
      createNotice('success', Object(external_wp_i18n_["__"])('Message dismissed', 'woocommerce-admin'), {
        actions: [{
          label: Object(external_wp_i18n_["__"])('Undo', 'woocommerce-admin'),
          onClick: () => {
            updateNote(noteId, {
              is_deleted: 0
            });
          }
        }]
      });
    } catch (e) {
      createNotice('error', Object(external_wp_i18n_["_n"])('Message could not be dismissed', 'Messages could not be dismissed', 1, 'woocommerce-admin'));
    }
  };

  if (isError) {
    const title = Object(external_wp_i18n_["__"])('There was an error getting your inbox. Please try again.', 'woocommerce-admin');

    const actionLabel = Object(external_wp_i18n_["__"])('Reload', 'woocommerce-admin');

    const actionCallback = () => {
      // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
      window.location.reload();
    };

    return Object(external_wp_element_["createElement"])(external_wc_components_["EmptyContent"], {
      title: title,
      actionLabel: actionLabel,
      actionURL: null,
      actionCallback: actionCallback
    });
  }

  const hasNotes = Object(utils["b" /* hasValidNotes */])(notes); // @todo After having a pagination implemented we should call the method "getNotes" with a different query since
  // the current one is only getting 25 notes and the count of unread notes only will refer to this 25 and not all the existing ones.

  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, showDismissAllModal && Object(external_wp_element_["createElement"])(dismiss_all_modal, {
    onClose: () => {
      setShowDismissAllModal(false);
    }
  }), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-homepage-notes-wrapper"
  }, (isResolvingNotes || isBatchUpdating) && Object(external_wp_element_["createElement"])(external_wc_components_["Section"], null, Object(external_wp_element_["createElement"])(external_wc_experimental_["InboxNotePlaceholder"], {
    className: "banner message-is-unread"
  })), Object(external_wp_element_["createElement"])(external_wc_components_["Section"], null, !isResolvingNotes && !isBatchUpdating && renderNotes({
    hasNotes,
    isBatchUpdating,
    notes,
    onDismiss,
    onNoteActionClick: (note, action) => {
      triggerNoteAction(note.id, action.id);
    },
    setShowDismissAllModal,
    showHeader
  }))));
};

/* harmony default export */ var client_inbox_panel = __webpack_exports__["a"] = (InboxPanel);

/***/ }),

/***/ 582:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-notice-outline",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 4c4.411 0 8 3.589 8 8s-3.589 8-8 8-8-3.589-8-8 3.589-8 8-8m0-2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 13h-2v2h2v-2zm-2-2h2l.5-6h-3l.5 6z"})))}


/***/ }),

/***/ 583:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 588:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);