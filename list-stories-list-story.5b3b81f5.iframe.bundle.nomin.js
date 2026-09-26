"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7860],{

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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-docs@10.5._fbcfbe431a86cf172343b3570ff1b50f/node_modules/@storybook/addon-docs/dist/blocks.js + 1 modules
var blocks = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-docs@10.5._fbcfbe431a86cf172343b3570ff1b50f/node_modules/@storybook/addon-docs/dist/blocks.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/index.js
var addon_links_dist = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-links@10.5_aeca451a4aec4b92063bb7c73c54f2e5/node_modules/@storybook/addon-links/dist/index.js");
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
  decorators: [addon_links_dist/* withLinks */.q9],
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

/***/ })

}]);