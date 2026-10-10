"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7860],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _assertThisInitialized)
/* harmony export */ });
function _assertThisInitialized(e) {
  if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  return e;
}


/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/extends.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _extends)
/* harmony export */ });
function _extends() {
  return _extends = Object.assign ? Object.assign.bind() : function (n) {
    for (var e = 1; e < arguments.length; e++) {
      var t = arguments[e];
      for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
    }
    return n;
  }, _extends.apply(null, arguments);
}


/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _inheritsLoose)
});

;// ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js
function _setPrototypeOf(t, e) {
  return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) {
    return t.__proto__ = e, t;
  }, _setPrototypeOf(t, e);
}

;// ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js

function _inheritsLoose(t, o) {
  t.prototype = Object.create(o.prototype), t.prototype.constructor = t, _setPrototypeOf(t, o);
}


/***/ }),

/***/ "../../packages/js/components/src/list/stories/list.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  BeforeAndAfter: () => (/* binding */ BeforeAndAfter),
  CustomStyleAndTags: () => (/* binding */ CustomStyleAndTags),
  Default: () => (/* binding */ Default),
  "default": () => (/* binding */ list_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/blocks.js + 4 modules
var blocks = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/blocks.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-PZ2KFUGG.js
var chunk_PZ2KFUGG = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-PZ2KFUGG.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-LAYPZ6CY.js
var chunk_LAYPZ6CY = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-LAYPZ6CY.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-FX5LXYO2.js
var chunk_FX5LXYO2 = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/_browser-chunks/chunk-FX5LXYO2.js");
;// ../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/index.js





// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js + 1 modules
var TransitionGroup = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js + 3 modules
var CSSTransition = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js
var keycodes_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/link/index.tsx
var src_link = __webpack_require__("../../packages/js/components/src/link/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/list/list-item.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


function handleKeyDown(event, onClick) {
  if (typeof onClick === 'function' && event.keyCode === keycodes_build_module/* ENTER */.Fm) {
    onClick();
  }
}
function getItemLinkType(item) {
  const {
    href,
    linkType
  } = item;
  if (linkType) {
    return linkType;
  }
  return href ? 'external' : null;
}

/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list item
 */
function ListItem(props) {
  const {
    item
  } = props;
  const {
    before,
    title,
    after,
    content,
    onClick,
    href,
    target,
    listItemTag
  } = item;
  const hasAction = typeof onClick === 'function' || href;
  const InnerTag = href ? src_link/* default */.A : 'div';
  const innerTagProps = {
    className: 'woocommerce-list__item-inner',
    onClick: typeof onClick === 'function' ? onClick : null,
    'aria-disabled': hasAction ? 'false' : null,
    tabIndex: hasAction ? '0' : null,
    role: hasAction ? 'menuitem' : null,
    onKeyDown: e => hasAction ? handleKeyDown(e, onClick) : null,
    target: href ? target : null,
    type: getItemLinkType(item),
    href,
    'data-list-item-tag': listItemTag
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(InnerTag, {
    ...innerTagProps,
    children: [before && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-list__item-before",
      children: before
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-list__item-text",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "woocommerce-list__item-title",
        children: title
      }), content && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "woocommerce-list__item-content",
        children: content
      })]
    }), after && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-list__item-after",
      children: after
    })]
  });
}
/* harmony default export */ const list_item = (ListItem);
;
ListItem.__docgenInfo = {
  "description": "List component to display a list of items.\n\n@param {Object} props props for list item",
  "methods": [],
  "displayName": "ListItem",
  "props": {
    "item": {
      "description": "An array of list items.",
      "type": {
        "name": "shape",
        "value": {
          "after": {
            "name": "node",
            "description": "Content displayed after the list item text.",
            "required": false
          },
          "before": {
            "name": "node",
            "description": "Content displayed before the list item text.",
            "required": false
          },
          "className": {
            "name": "string",
            "description": "Additional class name to style the list item.",
            "required": false
          },
          "content": {
            "name": "union",
            "value": [{
              "name": "string"
            }, {
              "name": "node"
            }],
            "description": "Content displayed beneath the list item title.",
            "required": false
          },
          "href": {
            "name": "string",
            "description": "Href attribute used in a Link wrapped around the item.",
            "required": false
          },
          "onClick": {
            "name": "func",
            "description": "Called when the list item is clicked.",
            "required": false
          },
          "target": {
            "name": "string",
            "description": "Target attribute used for Link wrapper.",
            "required": false
          },
          "title": {
            "name": "union",
            "value": [{
              "name": "string"
            }, {
              "name": "node"
            }],
            "description": "Title displayed for the list item.",
            "required": false
          }
        }
      },
      "required": true
    }
  }
};
;// ../../packages/js/components/src/list/index.js
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list
 */

function List(props) {
  const {
    className,
    items,
    children
  } = props;
  const listClassName = (0,clsx/* default */.A)('woocommerce-list', className);
  (0,build_module/* default */.A)('List with items prop is deprecated', {
    version: '9.0.0',
    hint: 'See ExperimentalList / ExperimentalListItem for the new API that will replace this component in future versions.'
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(TransitionGroup/* default */.A, {
    component: "ul",
    className: listClassName,
    role: "menu",
    children: items.map((item, index) => {
      const {
        className: itemClasses,
        href,
        key,
        onClick
      } = item;
      const hasAction = typeof onClick === 'function' || href;
      const itemClassName = (0,clsx/* default */.A)('woocommerce-list__item', itemClasses, {
        'has-action': hasAction
      });
      return /*#__PURE__*/(0,jsx_runtime.jsx)(CSSTransition/* default */.A, {
        timeout: 500,
        classNames: "woocommerce-list__item",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
          className: itemClassName,
          children: children ? children(item, index) : /*#__PURE__*/(0,jsx_runtime.jsx)(list_item, {
            item: item
          })
        })
      }, key || index);
    })
  });
}
/* harmony default export */ const list = (List);
;
List.__docgenInfo = {
  "description": "List component to display a list of items.\n\n@param {Object} props props for list",
  "methods": [],
  "displayName": "List",
  "props": {
    "className": {
      "description": "Additional class name to style the component.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "items": {
      "description": "An array of list items.",
      "type": {
        "name": "arrayOf",
        "value": {
          "name": "shape",
          "value": {
            "after": {
              "name": "node",
              "description": "Content displayed after the list item text.",
              "required": false
            },
            "before": {
              "name": "node",
              "description": "Content displayed before the list item text.",
              "required": false
            },
            "className": {
              "name": "string",
              "description": "Additional class name to style the list item.",
              "required": false
            },
            "content": {
              "name": "union",
              "value": [{
                "name": "string"
              }, {
                "name": "node"
              }],
              "description": "Content displayed beneath the list item title.",
              "required": false
            },
            "href": {
              "name": "string",
              "description": "Href attribute used in a Link wrapped around the item.",
              "required": false
            },
            "onClick": {
              "name": "func",
              "description": "Called when the list item is clicked.",
              "required": false
            },
            "target": {
              "name": "string",
              "description": "Target attribute used for Link wrapper.",
              "required": false
            },
            "title": {
              "name": "union",
              "value": [{
                "name": "string"
              }, {
                "name": "node"
              }],
              "description": "Title displayed for the list item.",
              "required": false
            },
            "key": {
              "name": "string",
              "description": "Unique key for list item.",
              "required": false
            }
          }
        }
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/list/stories/style.scss
// extracted by mini-css-extract-plugin

;// ../../packages/js/components/src/list/stories/list.story.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



function logItemClick(event) {
  const a = event.currentTarget;
  const itemDescription = a.href ? `[${a.textContent}](${a.href}) ${a.dataset.linkType}` : `[${a.textContent}]`;
  const itemTag = a.dataset.listItemTag ? `'${a.dataset.listItemTag}'` : 'not set';
  const logMessage = `[${itemDescription} item clicked (tag: ${itemTag})`;

  // eslint-disable-next-line no-console
  console.log(logMessage);
  event.preventDefault();
  return false;
}
/* harmony default export */ const list_story = ({
  title: 'Components/List',
  component: list,
  decorators: [chunk_LAYPZ6CY/* withLinks */.q9],
  parameters: {
    docs: {
      page: () => /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(blocks/* Title */.hE, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(blocks/* Subtitle */.Pd, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(blocks/* Description */.VY, {
          markdown: `[deprecated] and will be replaced by
                        <a
                            data-sb-kind="woocommerce-admin-experimental-list"
                            data-sb-story="default"
                        >
                            ExperimentalList
                        </a>`
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(blocks/* Primary */.Tn, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(blocks/* Controls */.H2, {}), /*#__PURE__*/(0,jsx_runtime.jsx)(blocks/* Stories */.om, {})]
      })
    }
  }
});
const Default = () => {
  const listItems = [{
    title: 'WooCommerce.com',
    href: 'https://woocommerce.com',
    onClick: logItemClick
  }, {
    title: 'WordPress.org',
    href: 'https://wordpress.org',
    onClick: logItemClick
  }, {
    title: 'A list item with no action'
  }, {
    title: 'Click me!',
    content: 'An alert will be triggered.',
    onClick: event => {
      // eslint-disable-next-line no-alert
      window.alert('List item clicked');
      return logItemClick(event);
    }
  }];
  return /*#__PURE__*/(0,jsx_runtime.jsx)(list, {
    items: listItems
  });
};
Default.storyName = 'Default (deprecated)';
const BeforeAndAfter = () => {
  const listItems = [{
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "cart"
    }),
    after: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WooCommerce.com',
    href: 'https://woocommerce.com',
    onClick: logItemClick
  }, {
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "my-sites"
    }),
    after: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WordPress.org',
    href: 'https://wordpress.org',
    onClick: logItemClick
  }, {
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "link-break"
    }),
    title: 'A list item with no action',
    description: 'List item description text'
  }, {
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "notice"
    }),
    title: 'Click me!',
    content: 'An alert will be triggered.',
    onClick: event => {
      // eslint-disable-next-line no-alert
      window.alert('List item clicked');
      return logItemClick(event);
    }
  }];
  return /*#__PURE__*/(0,jsx_runtime.jsx)(list, {
    items: listItems
  });
};
BeforeAndAfter.storyName = 'Before and after (deprecated)';
const CustomStyleAndTags = () => {
  const listItems = [{
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "cart"
    }),
    after: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WooCommerce.com',
    href: 'https://woocommerce.com',
    onClick: logItemClick,
    listItemTag: 'woo.com-link'
  }, {
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "my-sites"
    }),
    after: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "chevron-right"
    }),
    title: 'WordPress.org',
    href: 'https://wordpress.org',
    onClick: logItemClick,
    listItemTag: 'wordpress.org-link'
  }, {
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "link-break"
    }),
    title: 'A list item with no action'
  }, {
    before: /*#__PURE__*/(0,jsx_runtime.jsx)(dist/* default */.A, {
      icon: "notice"
    }),
    title: 'Click me!',
    content: 'An alert will be triggered.',
    onClick: event => {
      // eslint-disable-next-line no-alert
      window.alert('List item clicked');
      return logItemClick(event);
    },
    listItemTag: 'click-me'
  }];
  return /*#__PURE__*/(0,jsx_runtime.jsx)(list, {
    items: listItems,
    className: "storybook-custom-list"
  });
};
CustomStyleAndTags.storyName = 'Custom style and tags (deprecated)';
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "() => {\n  const listItems = [{\n    title: 'WooCommerce.com',\n    href: 'https://woocommerce.com',\n    onClick: logItemClick\n  }, {\n    title: 'WordPress.org',\n    href: 'https://wordpress.org',\n    onClick: logItemClick\n  }, {\n    title: 'A list item with no action'\n  }, {\n    title: 'Click me!',\n    content: 'An alert will be triggered.',\n    onClick: event => {\n      // eslint-disable-next-line no-alert\n      window.alert('List item clicked');\n      return logItemClick(event);\n    }\n  }];\n  return <List items={listItems} />;\n}",
      ...Default.parameters?.docs?.source
    }
  }
};
BeforeAndAfter.parameters = {
  ...BeforeAndAfter.parameters,
  docs: {
    ...BeforeAndAfter.parameters?.docs,
    source: {
      originalSource: "() => {\n  const listItems = [{\n    before: <Gridicon icon=\"cart\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WooCommerce.com',\n    href: 'https://woocommerce.com',\n    onClick: logItemClick\n  }, {\n    before: <Gridicon icon=\"my-sites\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WordPress.org',\n    href: 'https://wordpress.org',\n    onClick: logItemClick\n  }, {\n    before: <Gridicon icon=\"link-break\" />,\n    title: 'A list item with no action',\n    description: 'List item description text'\n  }, {\n    before: <Gridicon icon=\"notice\" />,\n    title: 'Click me!',\n    content: 'An alert will be triggered.',\n    onClick: event => {\n      // eslint-disable-next-line no-alert\n      window.alert('List item clicked');\n      return logItemClick(event);\n    }\n  }];\n  return <List items={listItems} />;\n}",
      ...BeforeAndAfter.parameters?.docs?.source
    }
  }
};
CustomStyleAndTags.parameters = {
  ...CustomStyleAndTags.parameters,
  docs: {
    ...CustomStyleAndTags.parameters?.docs,
    source: {
      originalSource: "() => {\n  const listItems = [{\n    before: <Gridicon icon=\"cart\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WooCommerce.com',\n    href: 'https://woocommerce.com',\n    onClick: logItemClick,\n    listItemTag: 'woo.com-link'\n  }, {\n    before: <Gridicon icon=\"my-sites\" />,\n    after: <Gridicon icon=\"chevron-right\" />,\n    title: 'WordPress.org',\n    href: 'https://wordpress.org',\n    onClick: logItemClick,\n    listItemTag: 'wordpress.org-link'\n  }, {\n    before: <Gridicon icon=\"link-break\" />,\n    title: 'A list item with no action'\n  }, {\n    before: <Gridicon icon=\"notice\" />,\n    title: 'Click me!',\n    content: 'An alert will be triggered.',\n    onClick: event => {\n      // eslint-disable-next-line no-alert\n      window.alert('List item clicked');\n      return logItemClick(event);\n    },\n    listItemTag: 'click-me'\n  }];\n  return <List items={listItems} className=\"storybook-custom-list\" />;\n}",
      ...CustomStyleAndTags.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs");

const logged = /* @__PURE__ */ Object.create(null);
function deprecated(feature, options = {}) {
  const { since, version, alternative, plugin, link, hint } = options;
  const pluginMessage = plugin ? ` from ${plugin}` : "";
  const sinceMessage = since ? ` since version ${since}` : "";
  const versionMessage = version ? ` and will be removed${pluginMessage} in version ${version}` : "";
  const useInsteadMessage = alternative ? ` Please use ${alternative} instead.` : "";
  const linkMessage = link ? ` See: ${link}` : "";
  const hintMessage = hint ? ` Note: ${hint}` : "";
  const message = `${feature} is deprecated${sinceMessage}${versionMessage}.${useInsteadMessage}${linkMessage}${hintMessage}`;
  if (message in logged) {
    return;
  }
  (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)("deprecated", feature, options, message);
  console.warn(message);
  logged[message] = true;
}

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  __: () => (/* reexport */ __),
  _n: () => (/* reexport */ _n),
  V8: () => (/* reexport */ isRTL),
  nv: () => (/* reexport */ sprintf)
});

// UNUSED EXPORTS: _nx, _x, createI18n, defaultI18n, getLocaleData, hasTranslation, resetLocaleData, setLocaleData, subscribe

// EXTERNAL MODULE: ../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js
var src = __webpack_require__("../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/sprintf.mjs
// packages/i18n/src/sprintf.ts

function sprintf(format, ...args) {
  return (0,src/* default */.A)(format, ...args);
}

//# sourceMappingURL=sprintf.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js + 4 modules
var node_modules_tannin = __webpack_require__("../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/create-i18n.mjs
// packages/i18n/src/create-i18n.ts

var DEFAULT_LOCALE_DATA = {
  "": {
    plural_forms(n) {
      return n === 1 ? 0 : 1;
    }
  }
};
var I18N_HOOK_REGEXP = /^i18n\.(n?gettext|has_translation)(_|$)/;
var createI18n = (initialData, initialDomain, hooks) => {
  const tannin = new node_modules_tannin/* default */.A({});
  const listeners = /* @__PURE__ */ new Set();
  const notifyListeners = () => {
    listeners.forEach((listener) => listener());
  };
  const subscribe = (callback) => {
    listeners.add(callback);
    return () => listeners.delete(callback);
  };
  const getLocaleData = (domain = "default") => tannin.data[domain];
  const doSetLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data
    };
    tannin.data[domain][""] = {
      ...DEFAULT_LOCALE_DATA[""],
      ...tannin.data[domain]?.[""]
    };
    delete tannin.pluralForms[domain];
  };
  const setLocaleData = (data, domain) => {
    doSetLocaleData(data, domain);
    notifyListeners();
  };
  const addLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data,
      // Populate default domain configuration (supported locale date which omits
      // a plural forms expression).
      "": {
        ...DEFAULT_LOCALE_DATA[""],
        ...tannin.data[domain]?.[""],
        ...data?.[""]
      }
    };
    delete tannin.pluralForms[domain];
    notifyListeners();
  };
  const resetLocaleData = (data, domain) => {
    tannin.data = {};
    tannin.pluralForms = {};
    setLocaleData(data, domain);
  };
  const dcnpgettext = (domain = "default", context, single, plural, number) => {
    if (!tannin.data[domain]) {
      doSetLocaleData(void 0, domain);
    }
    return tannin.dcnpgettext(domain, context, single, plural, number);
  };
  const getFilterDomain = (domain) => domain || "default";
  const __ = (text, domain) => {
    let translation = dcnpgettext(domain, void 0, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext",
      translation,
      text,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_" + getFilterDomain(domain),
      translation,
      text,
      domain
    );
  };
  const _x = (text, context, domain) => {
    let translation = dcnpgettext(domain, context, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext_with_context",
      translation,
      text,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_with_context_" + getFilterDomain(domain),
      translation,
      text,
      context,
      domain
    );
  };
  const _n = (single, plural, number, domain) => {
    let translation = dcnpgettext(
      domain,
      void 0,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext",
      translation,
      single,
      plural,
      number,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      domain
    );
  };
  const _nx = (single, plural, number, context, domain) => {
    let translation = dcnpgettext(
      domain,
      context,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext_with_context",
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_with_context_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
  };
  const isRTL = () => {
    return "rtl" === _x("ltr", "text direction");
  };
  const hasTranslation = (single, context, domain) => {
    const key = context ? context + "" + single : single;
    let result = !!tannin.data?.[domain ?? "default"]?.[key];
    if (hooks) {
      result = hooks.applyFilters(
        "i18n.has_translation",
        result,
        single,
        context,
        domain
      );
      result = hooks.applyFilters(
        "i18n.has_translation_" + getFilterDomain(domain),
        result,
        single,
        context,
        domain
      );
    }
    return result;
  };
  if (initialData) {
    setLocaleData(initialData, initialDomain);
  }
  if (hooks) {
    const onHookAddedOrRemoved = (hookName) => {
      if (I18N_HOOK_REGEXP.test(hookName)) {
        notifyListeners();
      }
    };
    hooks.addAction("hookAdded", "core/i18n", onHookAddedOrRemoved);
    hooks.addAction("hookRemoved", "core/i18n", onHookAddedOrRemoved);
  }
  return {
    getLocaleData,
    setLocaleData,
    addLocaleData,
    resetLocaleData,
    subscribe,
    __,
    _x,
    _n,
    _nx,
    isRTL,
    hasTranslation
  };
};

//# sourceMappingURL=create-i18n.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs + 10 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/default-i18n.mjs
// packages/i18n/src/default-i18n.ts


var i18n = createI18n(void 0, void 0, build_module/* defaultHooks */.se);
var default_i18n_default = (/* unused pure expression or super */ null && (i18n));
var getLocaleData = i18n.getLocaleData.bind(i18n);
var setLocaleData = i18n.setLocaleData.bind(i18n);
var resetLocaleData = i18n.resetLocaleData.bind(i18n);
var subscribe = i18n.subscribe.bind(i18n);
var __ = i18n.__.bind(i18n);
var _x = i18n._x.bind(i18n);
var _n = i18n._n.bind(i18n);
var _nx = i18n._nx.bind(i18n);
var isRTL = i18n.isRTL.bind(i18n);
var hasTranslation = i18n.hasTranslation.bind(i18n);

//# sourceMappingURL=default-i18n.mjs.map

;// ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs
// packages/i18n/src/index.ts




//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+keycodes@4.33.1/node_modules/@wordpress/keycodes/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Fm: () => (/* binding */ ENTER),
/* harmony export */   G_: () => (/* binding */ BACKSPACE),
/* harmony export */   M3: () => (/* binding */ LEFT),
/* harmony export */   NS: () => (/* binding */ RIGHT),
/* harmony export */   PX: () => (/* binding */ DOWN),
/* harmony export */   UP: () => (/* binding */ UP),
/* harmony export */   _f: () => (/* binding */ ESCAPE),
/* harmony export */   t6: () => (/* binding */ SPACE),
/* harmony export */   wn: () => (/* binding */ TAB)
/* harmony export */ });
/* unused harmony exports ALT, COMMAND, CTRL, DELETE, END, F10, HOME, PAGEDOWN, PAGEUP, SHIFT, ZERO, displayShortcut, displayShortcutList, isKeyboardEvent, modifiers, rawShortcut, shortcutAriaLabel */
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs");


const BACKSPACE = 8;
const TAB = 9;
const ENTER = 13;
const ESCAPE = 27;
const SPACE = 32;
const PAGEUP = 33;
const PAGEDOWN = 34;
const END = 35;
const HOME = 36;
const LEFT = 37;
const UP = 38;
const RIGHT = 39;
const DOWN = 40;
const DELETE = 46;
const F10 = 121;
const ALT = "alt";
const CTRL = "ctrl";
const COMMAND = "meta";
const SHIFT = "shift";
const ZERO = 48;
function capitaliseFirstCharacter(string) {
  return string.length < 2 ? string.toUpperCase() : string.charAt(0).toUpperCase() + string.slice(1);
}
function mapValues(object, mapFn) {
  return Object.fromEntries(
    Object.entries(object).map(([key, value]) => [
      key,
      mapFn(value)
    ])
  );
}
const modifiers = {
  primary: (_isApple) => _isApple() ? [COMMAND] : [CTRL],
  primaryShift: (_isApple) => _isApple() ? [SHIFT, COMMAND] : [CTRL, SHIFT],
  primaryAlt: (_isApple) => _isApple() ? [ALT, COMMAND] : [CTRL, ALT],
  secondary: (_isApple) => _isApple() ? [SHIFT, ALT, COMMAND] : [CTRL, SHIFT, ALT],
  access: (_isApple) => _isApple() ? [CTRL, ALT] : [SHIFT, ALT],
  ctrl: () => [CTRL],
  alt: () => [ALT],
  ctrlShift: () => [CTRL, SHIFT],
  shift: () => [SHIFT],
  shiftAlt: () => [SHIFT, ALT],
  undefined: () => []
};
const rawShortcut = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(modifiers, (modifier) => {
  return (character, _isApple = isAppleOS) => {
    return [...modifier(_isApple), character.toLowerCase()].join(
      "+"
    );
  };
})));
const displayShortcutList = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(
  modifiers,
  (modifier) => {
    return (character, _isApple = isAppleOS) => {
      const isApple = _isApple();
      const replacementKeyMap = {
        [ALT]: isApple ? "\u2325" : "Alt",
        [CTRL]: isApple ? "\u2303" : "Ctrl",
        // Make sure ⌃ is the U+2303 UP ARROWHEAD unicode character and not the caret character.
        [COMMAND]: "\u2318",
        [SHIFT]: isApple ? "\u21E7" : "Shift"
      };
      const modifierKeys = modifier(_isApple).reduce(
        (accumulator, key) => {
          const replacementKey = replacementKeyMap[key] ?? key;
          if (isApple) {
            return [...accumulator, replacementKey];
          }
          return [...accumulator, replacementKey, "+"];
        },
        []
      );
      return [
        ...modifierKeys,
        capitaliseFirstCharacter(character)
      ];
    };
  }
)));
const displayShortcut = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(
  displayShortcutList,
  (shortcutList) => {
    return (character, _isApple = isAppleOS) => shortcutList(character, _isApple).join("");
  }
)));
const shortcutAriaLabel = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(modifiers, (modifier) => {
  return (character, _isApple = isAppleOS) => {
    const isApple = _isApple();
    const replacementKeyMap = {
      [SHIFT]: "Shift",
      [COMMAND]: isApple ? "Command" : "Control",
      [CTRL]: "Control",
      [ALT]: isApple ? "Option" : "Alt",
      /* translators: comma as in the character ',' */
      ",": __("Comma"),
      /* translators: period as in the character '.' */
      ".": __("Period"),
      /* translators: backtick as in the character '`' */
      "`": __("Backtick"),
      /* translators: tilde as in the character '~' */
      "~": __("Tilde")
    };
    return [...modifier(_isApple), character].map(
      (key) => capitaliseFirstCharacter(replacementKeyMap[key] ?? key)
    ).join(isApple ? " " : " + ");
  };
})));
function getEventModifiers(event) {
  return [ALT, CTRL, COMMAND, SHIFT].filter(
    (key) => event[`${key}Key`]
  );
}
const isKeyboardEvent = /* @__PURE__ */ (/* unused pure expression or super */ null && (mapValues(modifiers, (getModifiers) => {
  return (event, character, _isApple = isAppleOS) => {
    const mods = getModifiers(_isApple);
    const eventMods = getEventModifiers(event);
    const replacementWithShiftKeyMap = {
      Comma: ",",
      Backslash: "\\",
      // Windows returns `\` for both IntlRo and IntlYen.
      IntlRo: "\\",
      IntlYen: "\\"
    };
    const modsDiff = mods.filter(
      (mod) => !eventMods.includes(mod)
    );
    const eventModsDiff = eventMods.filter(
      (mod) => !mods.includes(mod)
    );
    if (modsDiff.length > 0 || eventModsDiff.length > 0) {
      return false;
    }
    let key = event.key.toLowerCase();
    if (!character) {
      return mods.includes(key);
    }
    if (event.altKey && character.length === 1) {
      key = String.fromCharCode(event.keyCode).toLowerCase();
    }
    if (event.shiftKey && character.length === 1 && replacementWithShiftKeyMap[event.code]) {
      key = replacementWithShiftKeyMap[event.code];
    }
    if (character === "del") {
      character = "delete";
    }
    return key === character.toLowerCase();
  };
})));

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
const Link = ({
  href,
  children,
  type = 'wc-admin',
  ...props
}) => {
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  const wcAdminLinkHandler = (onClick, event) => {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
      return;
    }
    event?.preventDefault();

    // If there is an onclick event, execute it.
    const onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if (event?.target instanceof Element) {
      const closestEventTarget = event.target.closest('a')?.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  const passProps = {
    ...props,
    'data-link-type': type
  };
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
    href: href,
    ...passProps,
    children: children
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Link);
try {
    // @ts-ignore
    Link.displayName = "Link";
    // @ts-ignore
    Link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "Link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#Link"] = { docgenInfo: Link.__docgenInfo, name: "Link", path: "../../packages/js/components/src/link/index.tsx#Link" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    link.displayName = "link";
    // @ts-ignore
    link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#link"] = { docgenInfo: link.__docgenInfo, name: "link", path: "../../packages/js/components/src/link/index.tsx#link" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ esm_TransitionGroup)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js + 1 modules
var inheritsLoose = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.29.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroupContext.js");
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/utils/ChildMapping.js

/**
 * Given `this.props.children`, return an object mapping key to child.
 *
 * @param {*} children `this.props.children`
 * @return {object} Mapping of key to child
 */

function getChildMapping(children, mapFn) {
  var mapper = function mapper(child) {
    return mapFn && (0,react.isValidElement)(child) ? mapFn(child) : child;
  };

  var result = Object.create(null);
  if (children) react.Children.map(children, function (c) {
    return c;
  }).forEach(function (child) {
    // run the map function here instead so that the key is the computed one
    result[child.key] = mapper(child);
  });
  return result;
}
/**
 * When you're adding or removing children some may be added or removed in the
 * same render pass. We want to show *both* since we want to simultaneously
 * animate elements in and out. This function takes a previous set of keys
 * and a new set of keys and merges them with its best guess of the correct
 * ordering. In the future we may expose some of the utilities in
 * ReactMultiChild to make this easy, but for now React itself does not
 * directly have this concept of the union of prevChildren and nextChildren
 * so we implement it here.
 *
 * @param {object} prev prev children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @param {object} next next children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @return {object} a key set that contains all keys in `prev` and all keys
 * in `next` in a reasonable order.
 */

function mergeChildMappings(prev, next) {
  prev = prev || {};
  next = next || {};

  function getValueForKey(key) {
    return key in next ? next[key] : prev[key];
  } // For each key of `next`, the list of keys to insert before that key in
  // the combined list


  var nextKeysPending = Object.create(null);
  var pendingKeys = [];

  for (var prevKey in prev) {
    if (prevKey in next) {
      if (pendingKeys.length) {
        nextKeysPending[prevKey] = pendingKeys;
        pendingKeys = [];
      }
    } else {
      pendingKeys.push(prevKey);
    }
  }

  var i;
  var childMapping = {};

  for (var nextKey in next) {
    if (nextKeysPending[nextKey]) {
      for (i = 0; i < nextKeysPending[nextKey].length; i++) {
        var pendingNextKey = nextKeysPending[nextKey][i];
        childMapping[nextKeysPending[nextKey][i]] = getValueForKey(pendingNextKey);
      }
    }

    childMapping[nextKey] = getValueForKey(nextKey);
  } // Finally, add the keys which didn't appear before any key in `next`


  for (i = 0; i < pendingKeys.length; i++) {
    childMapping[pendingKeys[i]] = getValueForKey(pendingKeys[i]);
  }

  return childMapping;
}

function getProp(child, prop, props) {
  return props[prop] != null ? props[prop] : child.props[prop];
}

function getInitialChildMapping(props, onExited) {
  return getChildMapping(props.children, function (child) {
    return (0,react.cloneElement)(child, {
      onExited: onExited.bind(null, child),
      in: true,
      appear: getProp(child, 'appear', props),
      enter: getProp(child, 'enter', props),
      exit: getProp(child, 'exit', props)
    });
  });
}
function getNextChildMapping(nextProps, prevChildMapping, onExited) {
  var nextChildMapping = getChildMapping(nextProps.children);
  var children = mergeChildMappings(prevChildMapping, nextChildMapping);
  Object.keys(children).forEach(function (key) {
    var child = children[key];
    if (!(0,react.isValidElement)(child)) return;
    var hasPrev = (key in prevChildMapping);
    var hasNext = (key in nextChildMapping);
    var prevChild = prevChildMapping[key];
    var isLeaving = (0,react.isValidElement)(prevChild) && !prevChild.props.in; // item is new (entering)

    if (hasNext && (!hasPrev || isLeaving)) {
      // console.log('entering', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: true,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    } else if (!hasNext && hasPrev && !isLeaving) {
      // item is old (exiting)
      // console.log('leaving', key)
      children[key] = (0,react.cloneElement)(child, {
        in: false
      });
    } else if (hasNext && hasPrev && (0,react.isValidElement)(prevChild)) {
      // item hasn't changed transition states
      // copy over the last transition props;
      // console.log('unchanged', key)
      children[key] = (0,react.cloneElement)(child, {
        onExited: onExited.bind(null, child),
        in: prevChild.props.in,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    }
  });
  return children;
}
;// ../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js









var values = Object.values || function (obj) {
  return Object.keys(obj).map(function (k) {
    return obj[k];
  });
};

var defaultProps = {
  component: 'div',
  childFactory: function childFactory(child) {
    return child;
  }
};
/**
 * The `<TransitionGroup>` component manages a set of transition components
 * (`<Transition>` and `<CSSTransition>`) in a list. Like with the transition
 * components, `<TransitionGroup>` is a state machine for managing the mounting
 * and unmounting of components over time.
 *
 * Consider the example below. As items are removed or added to the TodoList the
 * `in` prop is toggled automatically by the `<TransitionGroup>`.
 *
 * Note that `<TransitionGroup>`  does not define any animation behavior!
 * Exactly _how_ a list item animates is up to the individual transition
 * component. This means you can mix and match animations across different list
 * items.
 */

var TransitionGroup = /*#__PURE__*/function (_React$Component) {
  (0,inheritsLoose/* default */.A)(TransitionGroup, _React$Component);

  function TransitionGroup(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;

    var handleExited = _this.handleExited.bind((0,assertThisInitialized/* default */.A)(_this)); // Initial children should all be entering, dependent on appear


    _this.state = {
      contextValue: {
        isMounting: true
      },
      handleExited: handleExited,
      firstRender: true
    };
    return _this;
  }

  var _proto = TransitionGroup.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this.mounted = true;
    this.setState({
      contextValue: {
        isMounting: false
      }
    });
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    this.mounted = false;
  };

  TransitionGroup.getDerivedStateFromProps = function getDerivedStateFromProps(nextProps, _ref) {
    var prevChildMapping = _ref.children,
        handleExited = _ref.handleExited,
        firstRender = _ref.firstRender;
    return {
      children: firstRender ? getInitialChildMapping(nextProps, handleExited) : getNextChildMapping(nextProps, prevChildMapping, handleExited),
      firstRender: false
    };
  } // node is `undefined` when user provided `nodeRef` prop
  ;

  _proto.handleExited = function handleExited(child, node) {
    var currentChildMapping = getChildMapping(this.props.children);
    if (child.key in currentChildMapping) return;

    if (child.props.onExited) {
      child.props.onExited(node);
    }

    if (this.mounted) {
      this.setState(function (state) {
        var children = (0,esm_extends/* default */.A)({}, state.children);

        delete children[child.key];
        return {
          children: children
        };
      });
    }
  };

  _proto.render = function render() {
    var _this$props = this.props,
        Component = _this$props.component,
        childFactory = _this$props.childFactory,
        props = (0,objectWithoutPropertiesLoose/* default */.A)(_this$props, ["component", "childFactory"]);

    var contextValue = this.state.contextValue;
    var children = values(this.state.children).map(childFactory);
    delete props.appear;
    delete props.enter;
    delete props.exit;

    if (Component === null) {
      return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
        value: contextValue
      }, children);
    }

    return /*#__PURE__*/react.createElement(TransitionGroupContext/* default */.A.Provider, {
      value: contextValue
    }, /*#__PURE__*/react.createElement(Component, props, children));
  };

  return TransitionGroup;
}(react.Component);

TransitionGroup.propTypes =  false ? 0 : {};
TransitionGroup.defaultProps = defaultProps;
/* harmony default export */ const esm_TransitionGroup = (TransitionGroup);

/***/ })

}]);