"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7790],{

/***/ "../../packages/js/components/src/scroll-to/stories/scroll-to.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ scroll_to_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/scroll-to/index.js
/**
 * External dependencies
 */



class ScrollTo extends react.Component {
  constructor(props) {
    super(props);
    this.scrollTo = this.scrollTo.bind(this);
  }
  componentDidMount() {
    setTimeout(this.scrollTo, 250);
  }
  scrollTo() {
    const {
      offset
    } = this.props;
    if (this.ref.current && this.ref.current.offsetTop) {
      window.scrollTo(0, this.ref.current.offsetTop + parseInt(offset, 10));
    } else {
      setTimeout(this.scrollTo, 250);
    }
  }
  render() {
    const {
      children
    } = this.props;
    this.ref = (0,react.createRef)();
    return /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      ref: this.ref,
      children: children
    });
  }
}
ScrollTo.defaultProps = {
  offset: '0'
};
/* harmony default export */ const scroll_to = (ScrollTo);
;
ScrollTo.__docgenInfo = {
  "description": "",
  "methods": [{
    "name": "scrollTo",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "ScrollTo",
  "props": {
    "offset": {
      "defaultValue": {
        "value": "'0'",
        "computed": false
      },
      "description": "The offset from the top of the component.",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/scroll-to/stories/scroll-to.story.js
/**
 * External dependencies
 */


const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(scroll_to, {
  children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    children: "Have the web browser automatically scroll to this component on page render."
  })
});
/* harmony default export */ const scroll_to_story = ({
  title: 'Components/ScrollTo',
  component: scroll_to
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <ScrollTo>\n        <div>\n            Have the web browser automatically scroll to this component on page\n            render.\n        </div>\n    </ScrollTo>",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/react@18.3.1/node_modules/react/cjs/react-jsx-runtime.production.min.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

/**
 * @license React
 * react-jsx-runtime.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var f=__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js"),k=Symbol.for("react.element"),l=Symbol.for("react.fragment"),m=Object.prototype.hasOwnProperty,n=f.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,p={key:!0,ref:!0,__self:!0,__source:!0};
function q(c,a,g){var b,d={},e=null,h=null;void 0!==g&&(e=""+g);void 0!==a.key&&(e=""+a.key);void 0!==a.ref&&(h=a.ref);for(b in a)m.call(a,b)&&!p.hasOwnProperty(b)&&(d[b]=a[b]);if(c&&c.defaultProps)for(b in a=c.defaultProps,a)void 0===d[b]&&(d[b]=a[b]);return{$$typeof:k,type:c,key:e,ref:h,props:d,_owner:n.current}}exports.Fragment=l;exports.jsx=q;exports.jsxs=q;


/***/ }),

/***/ "../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/cjs/react-jsx-runtime.production.min.js");
} else {}


/***/ })

}]);