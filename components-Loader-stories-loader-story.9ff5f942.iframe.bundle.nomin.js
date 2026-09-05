"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9167],{

/***/ "../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ExampleLoaderWithControls: () => (/* binding */ ExampleLoaderWithControls),
/* harmony export */   ExampleNonLoopingLoader: () => (/* binding */ ExampleNonLoopingLoader),
/* harmony export */   ExampleSimpleLoader: () => (/* binding */ ExampleSimpleLoader),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/onboarding/src/components/Loader/index.ts");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/** Simple straightforward example of how to use the <Loader> compound component */

const ExampleSimpleLoader = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a, {
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Layout, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Illustration, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("img", {
        src: "https://placekitten.com/200/200",
        alt: "a cute kitteh"
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Title, {
      children: "Very Impressive Title"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.ProgressBar, {
      progress: 30
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Sequence, {
      interval: 1000,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Subtext, {
        children: "Message 1"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Subtext, {
        children: "Message 2"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Subtext, {
        children: "Message 3"
      })]
    })]
  })
});
const ExampleNonLoopingLoader = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a, {
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Layout, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Illustration, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("img", {
        src: "https://placekitten.com/200/200",
        alt: "a cute kitteh"
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Title, {
      children: "Very Impressive Title"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.ProgressBar, {
      progress: 30
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Sequence, {
      interval: 1000,
      shouldLoop: false,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Subtext, {
        children: "Message 1"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Subtext, {
        children: "Message 2"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Subtext, {
        children: "Message 3"
      })]
    })]
  })
});

/** <Loader> component story with controls */
const Template = ({
  progress,
  title,
  messages,
  shouldLoop
}) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a, {
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Layout, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Illustration, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("img", {
        src: "https://placekitten.com/200/200",
        alt: "a cute kitteh"
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Title, {
      children: title
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.ProgressBar, {
      progress: progress
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Sequence, {
      interval: 1000,
      shouldLoop: shouldLoop,
      children: messages.map((message, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* .Loader */ .a.Subtext, {
        children: message
      }, index))
    })]
  })
});
const ExampleLoaderWithControls = Template.bind({});
ExampleLoaderWithControls.args = {
  title: 'Very Impressive Title',
  progress: 30,
  shouldLoop: true,
  messages: ['Message 1', 'Message 2', 'Message 3']
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Onboarding/Loader',
  component: ExampleLoaderWithControls,
  argTypes: {
    title: {
      control: 'text'
    },
    progress: {
      control: {
        type: 'range',
        min: 0,
        max: 100
      }
    },
    shouldLoop: {
      control: 'boolean'
    },
    messages: {
      control: 'object'
    }
  }
});
ExampleSimpleLoader.parameters = {
  ...ExampleSimpleLoader.parameters,
  docs: {
    ...ExampleSimpleLoader.parameters?.docs,
    source: {
      originalSource: "() => <Loader>\n        <Loader.Layout>\n            <Loader.Illustration>\n                <img src=\"https://placekitten.com/200/200\" alt=\"a cute kitteh\" />\n            </Loader.Illustration>\n            <Loader.Title>Very Impressive Title</Loader.Title>\n            <Loader.ProgressBar progress={30} />\n            <Loader.Sequence interval={1000}>\n                <Loader.Subtext>Message 1</Loader.Subtext>\n                <Loader.Subtext>Message 2</Loader.Subtext>\n                <Loader.Subtext>Message 3</Loader.Subtext>\n            </Loader.Sequence>\n        </Loader.Layout>\n    </Loader>",
      ...ExampleSimpleLoader.parameters?.docs?.source
    },
    description: {
      story: "Simple straightforward example of how to use the <Loader> compound component",
      ...ExampleSimpleLoader.parameters?.docs?.description
    }
  }
};
ExampleNonLoopingLoader.parameters = {
  ...ExampleNonLoopingLoader.parameters,
  docs: {
    ...ExampleNonLoopingLoader.parameters?.docs,
    source: {
      originalSource: "() => <Loader>\n        <Loader.Layout>\n            <Loader.Illustration>\n                <img src=\"https://placekitten.com/200/200\" alt=\"a cute kitteh\" />\n            </Loader.Illustration>\n            <Loader.Title>Very Impressive Title</Loader.Title>\n            <Loader.ProgressBar progress={30} />\n            <Loader.Sequence interval={1000} shouldLoop={false}>\n                <Loader.Subtext>Message 1</Loader.Subtext>\n                <Loader.Subtext>Message 2</Loader.Subtext>\n                <Loader.Subtext>Message 3</Loader.Subtext>\n            </Loader.Sequence>\n        </Loader.Layout>\n    </Loader>",
      ...ExampleNonLoopingLoader.parameters?.docs?.source
    }
  }
};
ExampleLoaderWithControls.parameters = {
  ...ExampleLoaderWithControls.parameters,
  docs: {
    ...ExampleLoaderWithControls.parameters?.docs,
    source: {
      originalSource: "({\n  progress,\n  title,\n  messages,\n  shouldLoop\n}) => <Loader>\n        <Loader.Layout>\n            <Loader.Illustration>\n                <img src=\"https://placekitten.com/200/200\" alt=\"a cute kitteh\" />\n            </Loader.Illustration>\n            <Loader.Title>{title}</Loader.Title>\n            <Loader.ProgressBar progress={progress} />\n            <Loader.Sequence interval={1000} shouldLoop={shouldLoop}>\n                {messages.map((message, index) => <Loader.Subtext key={index}>{message}</Loader.Subtext>)}\n            </Loader.Sequence>\n        </Loader.Layout>\n    </Loader>",
      ...ExampleLoaderWithControls.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    ExampleSimpleLoader.displayName = "ExampleSimpleLoader";
    // @ts-ignore
    ExampleSimpleLoader.__docgenInfo = { "description": "Simple straightforward example of how to use the <Loader> compound component", "displayName": "ExampleSimpleLoader", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleSimpleLoader"] = { docgenInfo: ExampleSimpleLoader.__docgenInfo, name: "ExampleSimpleLoader", path: "../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleSimpleLoader" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ExampleLoaderWithControls.displayName = "ExampleLoaderWithControls";
    // @ts-ignore
    ExampleLoaderWithControls.__docgenInfo = { "description": "", "displayName": "ExampleLoaderWithControls", "props": { "progress": { "defaultValue": null, "description": "", "name": "progress", "required": true, "type": { "name": "any" } }, "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "any" } }, "messages": { "defaultValue": null, "description": "", "name": "messages", "required": true, "type": { "name": "any" } }, "shouldLoop": { "defaultValue": null, "description": "", "name": "shouldLoop", "required": true, "type": { "name": "any" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleLoaderWithControls"] = { docgenInfo: ExampleLoaderWithControls.__docgenInfo, name: "ExampleLoaderWithControls", path: "../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleLoaderWithControls" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/onboarding/src/components/Loader/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  a: () => (/* reexport */ Loader)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/onboarding/src/components/Loader/ProgressBar.tsx

/**
 * External dependencies
 */

const ProgressBar_ProgressBar = ({
  className = '',
  percent = 0,
  color = '#674399',
  bgcolor = 'var(--wp-admin-theme-color)'
}) => {
  const containerStyles = {
    backgroundColor: bgcolor
  };
  const fillerStyles = {
    backgroundColor: color,
    width: `${percent}%`,
    display: percent === 0 ? 'none' : 'inherit'
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: `woocommerce-onboarding-progress-bar ${className}`,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-onboarding-progress-bar__container",
      style: containerStyles,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-onboarding-progress-bar__filler",
        style: fillerStyles
      })
    })
  });
};
/* harmony default export */ const Loader_ProgressBar = (ProgressBar_ProgressBar);
try {
    // @ts-ignore
    ProgressBar_ProgressBar.displayName = "ProgressBar";
    // @ts-ignore
    ProgressBar_ProgressBar.__docgenInfo = { "description": "", "displayName": "ProgressBar", "props": { "className": { "defaultValue": { value: "" }, "description": "Component classname", "name": "className", "required": false, "type": { "name": "string" } }, "percent": { "defaultValue": { value: "0" }, "description": "Progress percentage (0 to 100)", "name": "percent", "required": false, "type": { "name": "number" } }, "color": { "defaultValue": { value: "#674399" }, "description": "Color of the progress bar", "name": "color", "required": false, "type": { "name": "string" } }, "bgcolor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "Background color of the progress container", "name": "bgcolor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/ProgressBar.tsx#ProgressBar"] = { docgenInfo: ProgressBar_ProgressBar.__docgenInfo, name: "ProgressBar", path: "../../packages/js/onboarding/src/components/Loader/ProgressBar.tsx#ProgressBar" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/onboarding/src/components/Loader/Loader.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const Loader = ({
  children,
  className
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: (0,clsx/* default */.A)('woocommerce-onboarding-loader', className),
    children: children
  });
};
Loader.Layout = ({
  children,
  className
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: (0,clsx/* default */.A)('woocommerce-onboarding-loader-wrapper', className),
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: (0,clsx/* default */.A)('woocommerce-onboarding-loader-container', className),
      children: children
    })
  });
};
Loader.Illustration = ({
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: children
  });
};
Loader.Title = ({
  children,
  className
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("h1", {
    className: (0,clsx/* default */.A)('woocommerce-onboarding-loader__title', className),
    children: children
  });
};
Loader.ProgressBar = ({
  progress,
  className
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(Loader_ProgressBar, {
    className: (0,clsx/* default */.A)('progress-bar', className),
    percent: progress ?? 0,
    color: 'var(--wp-admin-theme-color)',
    bgcolor: '#E0E0E0'
  });
};
Loader.Subtext = ({
  children,
  className
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
    className: (0,clsx/* default */.A)('woocommerce-onboarding-loader__paragraph', className),
    children: children
  });
};
const LoaderSequence = ({
  interval,
  shouldLoop = true,
  children,
  onChange = () => {}
}) => {
  const [index, setIndex] = (0,react.useState)(0);
  const childCount = react.Children.count(children);
  (0,react.useEffect)(() => {
    const rotateInterval = setInterval(() => {
      setIndex(prevIndex => {
        const nextIndex = prevIndex + 1;
        if (shouldLoop) {
          const updatedIndex = nextIndex % childCount;
          onChange(updatedIndex);
          return updatedIndex;
        }
        if (nextIndex < childCount) {
          onChange(nextIndex);
          return nextIndex;
        }
        clearInterval(rotateInterval);
        return prevIndex;
      });
    }, interval);
    return () => clearInterval(rotateInterval);
  }, [interval, children, shouldLoop, childCount]);
  const childToDisplay = react.Children.toArray(children)[index];
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: childToDisplay
  });
};
Loader.Sequence = LoaderSequence; // eslint rule-of-hooks can't handle the compound component definition directly
try {
    // @ts-ignore
    Loader.displayName = "Loader";
    // @ts-ignore
    Loader.__docgenInfo = { "description": "", "displayName": "Loader", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader"] = { docgenInfo: Loader.__docgenInfo, name: "Loader", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Layout.displayName = "Loader.Layout";
    // @ts-ignore
    Layout.__docgenInfo = { "description": "", "displayName": "Loader.Layout", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Layout"] = { docgenInfo: Loader.Layout.__docgenInfo, name: "Loader.Layout", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Layout" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Illustration.displayName = "Loader.Illustration";
    // @ts-ignore
    Illustration.__docgenInfo = { "description": "", "displayName": "Loader.Illustration", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Illustration"] = { docgenInfo: Loader.Illustration.__docgenInfo, name: "Loader.Illustration", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Illustration" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Title.displayName = "Loader.Title";
    // @ts-ignore
    Title.__docgenInfo = { "description": "", "displayName": "Loader.Title", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Title"] = { docgenInfo: Loader.Title.__docgenInfo, name: "Loader.Title", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Title" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ProgressBar.displayName = "Loader.ProgressBar";
    // @ts-ignore
    ProgressBar.__docgenInfo = { "description": "", "displayName": "Loader.ProgressBar", "props": { "progress": { "defaultValue": null, "description": "", "name": "progress", "required": true, "type": { "name": "number" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.ProgressBar"] = { docgenInfo: Loader.ProgressBar.__docgenInfo, name: "Loader.ProgressBar", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.ProgressBar" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Subtext.displayName = "Loader.Subtext";
    // @ts-ignore
    Subtext.__docgenInfo = { "description": "", "displayName": "Loader.Subtext", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Subtext"] = { docgenInfo: Loader.Subtext.__docgenInfo, name: "Loader.Subtext", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Subtext" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Sequence.displayName = "Loader.Sequence";
    // @ts-ignore
    Sequence.__docgenInfo = { "description": "", "displayName": "Loader.Sequence", "props": { "interval": { "defaultValue": null, "description": "", "name": "interval", "required": true, "type": { "name": "number" } }, "shouldLoop": { "defaultValue": { value: "true" }, "description": "", "name": "shouldLoop", "required": false, "type": { "name": "boolean" } }, "onChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onChange", "required": false, "type": { "name": "((index: number) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Sequence"] = { docgenInfo: Loader.Sequence.__docgenInfo, name: "Loader.Sequence", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Sequence" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/onboarding/src/components/Loader/index.ts


/***/ }),

/***/ "../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export clsx */
function r(e){var t,f,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(f=r(e[t]))&&(n&&(n+=" "),n+=f)}else for(f in e)e[f]&&(n&&(n+=" "),n+=f);return n}function clsx(){for(var e,t,f=0,n="",o=arguments.length;f<o;f++)(e=arguments[f])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (clsx);

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