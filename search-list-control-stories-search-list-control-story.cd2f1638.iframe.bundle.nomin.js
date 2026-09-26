"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5854],{

/***/ "../../packages/js/components/src/search-list-control/stories/search-list-control.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ search_list_control_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js + 1 modules
var with_spoken_messages = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js + 1 modules
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/notice-outline.js
var notice_outline = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/notice-outline.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
;// ../../packages/js/components/src/search-list-control/hierarchy.js
/**
 * External dependencies
 */


/**
 * Returns terms in a tree form.
 *
 * @param {Array} filteredList Array of terms, possibly a subset of all terms, in flat format.
 * @param {Array} list         Array of the full list of terms, defaults to the filteredList.
 *
 * @return {Array} Array of terms in tree format.
 */
function buildTermsTree(filteredList, list = filteredList) {
  const termsByParent = (0,lodash.groupBy)(filteredList, 'parent');
  const listById = (0,lodash.keyBy)(list, 'id');
  const getParentsName = (term = {}) => {
    if (!term.parent) {
      return term.name ? [term.name] : [];
    }
    const parentName = getParentsName(listById[term.parent]);
    return [...parentName, term.name];
  };
  const fillWithChildren = terms => {
    return terms.map(term => {
      const children = termsByParent[term.id];
      delete termsByParent[term.id];
      return {
        ...term,
        breadcrumbs: getParentsName(listById[term.parent]),
        children: children && children.length ? fillWithChildren(children) : []
      };
    });
  };
  const tree = fillWithChildren(termsByParent['0'] || []);
  delete termsByParent['0'];

  // anything left in termsByParent has no visible parent
  (0,lodash.forEach)(termsByParent, terms => {
    tree.push(...fillWithChildren(terms || []));
  });
  return tree;
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/search-list-control/item.js
/**
 * External dependencies
 */




function getHighlightedName(name, search) {
  if (!search) {
    return name;
  }
  const re = new RegExp((0,lodash.escapeRegExp)(search), 'ig');
  const nameParts = name.split(re);
  return nameParts.map((part, i) => {
    if (i === 0) {
      return part;
    }
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
        children: search
      }), part]
    }, i);
  });
}
function getBreadcrumbsForDisplay(breadcrumbs) {
  if (breadcrumbs.length === 1) {
    return (0,lodash.first)(breadcrumbs);
  }
  if (breadcrumbs.length === 2) {
    return (0,lodash.first)(breadcrumbs) + ' › ' + (0,lodash.last)(breadcrumbs);
  }
  return (0,lodash.first)(breadcrumbs) + ' … ' + (0,lodash.last)(breadcrumbs);
}
const SearchListItem = ({
  countLabel,
  className,
  depth = 0,
  controlId = '',
  item,
  isSelected,
  isSingle,
  onSelect,
  search = '',
  ...props
}) => {
  const showCount = !(0,lodash.isNil)(countLabel) || !(0,lodash.isNil)(item.count);
  const classes = [className, 'woocommerce-search-list__item'];
  classes.push(`depth-${depth}`);
  if (isSingle) {
    classes.push('is-radio-button');
  }
  if (showCount) {
    classes.push('has-count');
  }
  const hasBreadcrumbs = item.breadcrumbs && item.breadcrumbs.length;
  const name = props.name || `search-list-item-${controlId}`;
  const id = `${name}-${item.id}`;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("label", {
    htmlFor: id,
    className: classes.join(' '),
    children: [isSingle ? /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
      type: "radio",
      id: id,
      name: name,
      value: item.value,
      onChange: onSelect(item),
      checked: isSelected,
      className: "woocommerce-search-list__item-input",
      ...props
    }) : /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
      type: "checkbox",
      id: id,
      name: name,
      value: item.value,
      onChange: onSelect(item),
      checked: isSelected,
      className: "woocommerce-search-list__item-input",
      ...props
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
      className: "woocommerce-search-list__item-label",
      children: [hasBreadcrumbs ? /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "woocommerce-search-list__item-prefix",
        children: getBreadcrumbsForDisplay(item.breadcrumbs)
      }) : null, /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "woocommerce-search-list__item-name",
        children: getHighlightedName(item.name, search)
      })]
    }), !!showCount && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "woocommerce-search-list__item-count",
      children: countLabel || item.count
    })]
  });
};
/* harmony default export */ const item = (SearchListItem);
;
SearchListItem.__docgenInfo = {
  "description": "",
  "methods": [],
  "displayName": "SearchListItem",
  "props": {
    "depth": {
      "defaultValue": {
        "value": "0",
        "computed": false
      },
      "description": "Depth, non-zero if the list is hierarchical.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "controlId": {
      "defaultValue": {
        "value": "''",
        "computed": false
      },
      "description": "Unique id of the parent control.",
      "type": {
        "name": "node"
      },
      "required": false
    },
    "search": {
      "defaultValue": {
        "value": "''",
        "computed": false
      },
      "description": "Search string, used to highlight the substring in the item name.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "countLabel": {
      "description": "Label to display in the count bubble. Takes preference over `item.count`.",
      "type": {
        "name": "node"
      },
      "required": false
    },
    "item": {
      "description": "Current item to display.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "name": {
      "description": "Name of the inputs. Used to group input controls together. See:\nhttps://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-name\nIf not provided, a default name will be generated using the controlId.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "isSelected": {
      "description": "Whether this item is selected.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "isSingle": {
      "description": "Whether this should only display a single item (controls radio vs checkbox icon).",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "onSelect": {
      "description": "Callback for selecting the item.",
      "type": {
        "name": "func"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../packages/js/components/src/tag/index.tsx
var tag = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
;// ../../packages/js/components/src/search-list-control/index.js
/**
 * External dependencies
 */









/**
 * Internal dependencies
 */




const defaultMessages = {
  clear: (0,build_module.__)('Clear all selected items', 'woocommerce'),
  noItems: (0,build_module.__)('No items found.', 'woocommerce'),
  /* translators: %s: search term */
  noResults: (0,build_module.__)('No results for %s', 'woocommerce'),
  search: (0,build_module.__)('Search for items', 'woocommerce'),
  selected: n => (0,build_module/* sprintf */.nv)(/* translators: Number of items selected from list. */
  (0,build_module._n)('%d item selected', '%d items selected', n, 'woocommerce'), n),
  updated: (0,build_module.__)('Search results updated.', 'woocommerce')
};

/**
 * Component to display a searchable, selectable list of items.
 *
 * @param {Object} props
 */
const SearchListControl = props => {
  const [searchValue, setSearchValue] = (0,react.useState)(props.search || '');
  const {
    isSingle,
    isLoading,
    onChange,
    selected,
    instanceId,
    messages: propsMessages,
    isCompact,
    debouncedSpeak,
    onSearch,
    className = ''
  } = props;
  const messages = {
    ...defaultMessages,
    ...propsMessages
  };
  (0,react.useEffect)(() => {
    if (typeof onSearch === 'function') {
      onSearch(searchValue);
    }
  }, [onSearch, searchValue]);
  const onRemove = id => {
    return () => {
      if (isSingle) {
        onChange([]);
      }
      const i = (0,lodash.findIndex)(selected, {
        id
      });
      onChange([...selected.slice(0, i), ...selected.slice(i + 1)]);
    };
  };
  const isSelected = item => (0,lodash.findIndex)(selected, {
    id: item.id
  }) !== -1;
  const getFilteredList = (list, search) => {
    const {
      isHierarchical
    } = props;
    if (!search) {
      return isHierarchical ? buildTermsTree(list) : list;
    }
    const re = new RegExp((0,lodash.escapeRegExp)(search), 'i');
    debouncedSpeak(messages.updated);
    const filteredList = list.map(item => re.test(item.name) ? item : false).filter(Boolean);
    return isHierarchical ? buildTermsTree(filteredList, list) : filteredList;
  };
  const onSelect = item => {
    return () => {
      if (isSelected(item)) {
        onRemove(item.id)();
        return;
      }
      if (isSingle) {
        onChange([item]);
      } else {
        onChange([...selected, item]);
      }
    };
  };
  const defaultRenderItem = args => {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(item, {
      ...args
    });
  };
  const renderList = (list, depth = 0) => {
    const renderItem = props.renderItem || defaultRenderItem;
    if (!list) {
      return null;
    }
    return list.map(item => /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("li", {
        children: renderItem({
          item,
          isSelected: isSelected(item),
          onSelect,
          isSingle,
          search: searchValue,
          depth,
          controlId: instanceId
        })
      }), renderList(item.children, depth + 1)]
    }, item.id));
  };
  const renderListSection = () => {
    if (isLoading) {
      return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-search-list__list is-loading",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {})
      });
    }
    const list = getFilteredList(props.list, searchValue);
    if (!list.length) {
      return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-search-list__list is-not-found",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "woocommerce-search-list__not-found-icon",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(notice_outline/* default */.A, {
            role: "img",
            "aria-hidden": "true",
            focusable: "false"
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "woocommerce-search-list__not-found-text",
          children: searchValue ?
          // eslint-disable-next-line @wordpress/valid-sprintf
          (0,build_module/* sprintf */.nv)(messages.noResults || '', searchValue) : messages.noItems
        })]
      });
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)("ul", {
      className: "woocommerce-search-list__list",
      children: renderList(list)
    });
  };
  const renderSelectedSection = () => {
    if (isLoading || isSingle || !selected) {
      return null;
    }
    const selectedCount = selected.length;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-search-list__selected",
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-search-list__selected-header",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
          children: messages.selected(selectedCount)
        }), selectedCount > 0 ? /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          isLink: true,
          isDestructive: true,
          onClick: onChange([]),
          "aria-label": messages.clear,
          children: (0,build_module.__)('Clear all', 'woocommerce')
        }) : null]
      }), selectedCount > 0 ? /*#__PURE__*/(0,jsx_runtime.jsx)("ul", {
        children: selected.map((item, i) => /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(tag/* default */.A, {
            label: item.name,
            id: item.id,
            remove: onRemove
          })
        }, i))
      }) : null]
    });
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: (0,clsx/* default */.A)('woocommerce-search-list', className, {
      'is-compact': isCompact
    }),
    children: [renderSelectedSection(), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-search-list__search",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
        label: messages.search,
        type: "search",
        value: searchValue,
        onChange: value => setSearchValue(value)
      })
    }), renderListSection()]
  });
};
/* harmony default export */ const search_list_control = ((0,compose/* default */.A)([with_spoken_messages/* default */.A, with_instance_id/* default */.A])(SearchListControl));
;
SearchListControl.__docgenInfo = {
  "description": "Component to display a searchable, selectable list of items.\n\n@param {Object} props",
  "methods": [],
  "displayName": "SearchListControl",
  "props": {
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "isCompact": {
      "description": "Whether it should be displayed in a compact way, so it occupies less space.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "isHierarchical": {
      "description": "Whether the list of items is hierarchical or not. If true, each list item is expected to\nhave a parent property.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "isLoading": {
      "description": "Whether the list of items is still loading.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "isSingle": {
      "description": "Restrict selections to one item.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "list": {
      "description": "A complete list of item objects, each with id, name properties. This is displayed as a\nclickable/keyboard-able list, and possibly filtered by the search term (searches name).",
      "type": {
        "name": "arrayOf",
        "value": {
          "name": "shape",
          "value": {
            "id": {
              "name": "number",
              "required": false
            },
            "name": {
              "name": "string",
              "required": false
            }
          }
        }
      },
      "required": false
    },
    "messages": {
      "description": "Messages displayed or read to the user. Configure these to reflect your object type.\nSee `defaultMessages` above for examples.",
      "type": {
        "name": "shape",
        "value": {
          "clear": {
            "name": "string",
            "description": "A more detailed label for the \"Clear all\" button, read to screen reader users.",
            "required": false
          },
          "noItems": {
            "name": "string",
            "description": "Message to display when the list is empty (implies nothing loaded from the server\nor parent component).",
            "required": false
          },
          "noResults": {
            "name": "string",
            "description": "Message to display when no matching results are found. %s is the search term.",
            "required": false
          },
          "search": {
            "name": "string",
            "description": "Label for the search input",
            "required": false
          },
          "selected": {
            "name": "func",
            "description": "Label for the selected items. This is actually a function, so that we can pass\nthrough the count of currently selected items.",
            "required": false
          },
          "updated": {
            "name": "string",
            "description": "Label indicating that search results have changed, read to screen reader users.",
            "required": false
          }
        }
      },
      "required": false
    },
    "onChange": {
      "description": "Callback fired when selected items change, whether added, cleared, or removed.\nPassed an array of item objects (as passed in via props.list).",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "onSearch": {
      "description": "Callback fired when the search field is used.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "renderItem": {
      "description": "Callback to render each item in the selection list, allows any custom object-type rendering.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "selected": {
      "description": "The list of currently selected items.",
      "type": {
        "name": "array"
      },
      "required": true
    },
    "debouncedSpeak": {
      "description": "",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "instanceId": {
      "description": "",
      "type": {
        "name": "number"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/search-list-control/stories/search-list-control.story.js
/**
 * External dependencies
 */



const SearchListControlExample = ({
  showCount,
  isCompact,
  isSingle
}) => {
  const [selected, setSelected] = (0,react.useState)([]);
  const [loading, setLoading] = (0,react.useState)(false);
  let list = [{
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
  const counts = [3, 1, 1, 5, 2, 0];
  if (showCount) {
    list = list.map((item, i) => ({
      ...item,
      count: counts[i]
    }));
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("button", {
      onClick: () => setLoading(!loading),
      children: "Toggle loading state"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(search_list_control, {
      list: list,
      isCompact: isCompact,
      isLoading: loading,
      selected: selected,
      onChange: items => setSelected(items),
      isSingle: isSingle
    })]
  });
};
const Basic = args => /*#__PURE__*/(0,jsx_runtime.jsx)(SearchListControlExample, {
  ...args
});
/* harmony default export */ const search_list_control_story = ({
  title: 'Components/SearchListControl',
  component: search_list_control,
  args: {
    showCount: false,
    isCompact: false,
    isSingle: false
  },
  argTypes: {
    showCount: {
      control: {
        type: 'boolean'
      }
    },
    isCompact: {
      control: {
        type: 'boolean'
      }
    },
    isSingle: {
      control: {
        type: 'boolean'
      }
    }
  }
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "args => <SearchListControlExample {...args} />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/tag/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */








const Tag = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(({
  id,
  label,
  popoverContents,
  remove,
  screenReaderLabel,
  className
}, removeButtonRef) => {
  const [isVisible, setIsVisible] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Tag).toString();
  const labelId = `woocommerce-tag__label-${instanceId}`;
  screenReaderLabel = screenReaderLabel || label;
  if (!label) {
    // A null label probably means something went wrong
    // @todo Maybe this should be a loading indicator?
    return null;
  }
  label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__/* .decodeEntities */ .S)(label);
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)('woocommerce-tag', className, {
    'has-remove': !!remove
  });
  const labelTextNode = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "screen-reader-text",
      children: screenReaderLabel
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      "aria-hidden": "true",
      children: label
    })]
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("span", {
    className: classes,
    children: [popoverContents ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__text",
      id: labelId,
      onClick: () => setIsVisible(true),
      children: labelTextNode
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "woocommerce-tag__text",
      id: labelId,
      children: labelTextNode
    }), popoverContents && isVisible && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .Ay, {
      onClose: () => setIsVisible(false),
      children: popoverContents
    }), remove && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__remove",
      ref: removeButtonRef,
      onClick: remove(id),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(
      // translators: %s is the name of the tag being removed.
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Remove %s', 'woocommerce'), label),
      "aria-describedby": labelId,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A,
        size: 20,
        className: "clear-icon"
      })
    })]
  });
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tag);
try {
    // @ts-ignore
    tag.displayName = "tag";
    // @ts-ignore
    tag.__docgenInfo = { "description": "", "displayName": "tag", "props": { "label": { "defaultValue": null, "description": "The name for this item, displayed as the tag's text.", "name": "label", "required": true, "type": { "name": "string" } }, "id": { "defaultValue": null, "description": "A unique ID for this item. This is used to identify the item when the remove button is clicked.", "name": "id", "required": false, "type": { "name": "string | number" } }, "popoverContents": { "defaultValue": null, "description": "Contents to display on click in a popover", "name": "popoverContents", "required": false, "type": { "name": "ReactNode" } }, "remove": { "defaultValue": null, "description": "A function called when the remove X is clicked. If not used, no X icon will display.", "name": "remove", "required": false, "type": { "name": "((id: string | number) => MouseEventHandler<HTMLButtonElement>)" } }, "screenReaderLabel": { "defaultValue": null, "description": "A more descriptive label for screen reader users. Defaults to the `name` prop.", "name": "screenReaderLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Additional CSS classes.", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tag/index.tsx#tag"] = { docgenInfo: tag.__docgenInfo, name: "tag", path: "../../packages/js/components/src/tag/index.tsx#tag" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);