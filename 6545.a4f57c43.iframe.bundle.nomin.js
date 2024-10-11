"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6545],{

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {


var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var IndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/indexed-object.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");

var nativeJoin = uncurryThis([].join);

var ES3_STRINGS = IndexedObject !== Object;
var FORCED = ES3_STRINGS || !arrayMethodIsStrict('join', ',');

// `Array.prototype.join` method
// https://tc39.es/ecma262/#sec-array.prototype.join
$({ target: 'Array', proto: true, forced: FORCED }, {
  join: function join(separator) {
    return nativeJoin(toIndexedObject(this), separator === undefined ? ',' : separator);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/downshift@6.1.12_react@17.0.2/node_modules/downshift/dist/downshift.esm.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Bp: () => (/* binding */ useCombobox),
  mH: () => (/* binding */ useMultipleSelection),
  WM: () => (/* binding */ useSelect)
});

// UNUSED EXPORTS: default, resetIdCounter

// EXTERNAL MODULE: ../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__("../../node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js");
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-is@17.0.2/node_modules/react-is/index.js
var react_is = __webpack_require__("../../node_modules/.pnpm/react-is@17.0.2/node_modules/react-is/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/compute-scroll-into-view@1.0.20/node_modules/compute-scroll-into-view/dist/index.mjs
function t(t){return"object"==typeof t&&null!=t&&1===t.nodeType}function e(t,e){return(!e||"hidden"!==t)&&"visible"!==t&&"clip"!==t}function n(t,n){if(t.clientHeight<t.scrollHeight||t.clientWidth<t.scrollWidth){var r=getComputedStyle(t,null);return e(r.overflowY,n)||e(r.overflowX,n)||function(t){var e=function(t){if(!t.ownerDocument||!t.ownerDocument.defaultView)return null;try{return t.ownerDocument.defaultView.frameElement}catch(t){return null}}(t);return!!e&&(e.clientHeight<t.scrollHeight||e.clientWidth<t.scrollWidth)}(t)}return!1}function r(t,e,n,r,i,o,l,d){return o<t&&l>e||o>t&&l<e?0:o<=t&&d<=n||l>=e&&d>=n?o-t-r:l>e&&d<n||o<t&&d>n?l-e+i:0}var i=function(e,i){var o=window,l=i.scrollMode,d=i.block,f=i.inline,h=i.boundary,u=i.skipOverflowHiddenElements,s="function"==typeof h?h:function(t){return t!==h};if(!t(e))throw new TypeError("Invalid target");for(var a,c,g=document.scrollingElement||document.documentElement,p=[],m=e;t(m)&&s(m);){if((m=null==(c=(a=m).parentElement)?a.getRootNode().host||null:c)===g){p.push(m);break}null!=m&&m===document.body&&n(m)&&!n(document.documentElement)||null!=m&&n(m,u)&&p.push(m)}for(var w=o.visualViewport?o.visualViewport.width:innerWidth,v=o.visualViewport?o.visualViewport.height:innerHeight,W=window.scrollX||pageXOffset,H=window.scrollY||pageYOffset,b=e.getBoundingClientRect(),y=b.height,E=b.width,M=b.top,V=b.right,x=b.bottom,I=b.left,C="start"===d||"nearest"===d?M:"end"===d?x:M+y/2,R="center"===f?I+E/2:"end"===f?V:I,T=[],k=0;k<p.length;k++){var B=p[k],D=B.getBoundingClientRect(),O=D.height,X=D.width,Y=D.top,L=D.right,S=D.bottom,j=D.left;if("if-needed"===l&&M>=0&&I>=0&&x<=v&&V<=w&&M>=Y&&x<=S&&I>=j&&V<=L)return T;var N=getComputedStyle(B),q=parseInt(N.borderLeftWidth,10),z=parseInt(N.borderTopWidth,10),A=parseInt(N.borderRightWidth,10),F=parseInt(N.borderBottomWidth,10),G=0,J=0,K="offsetWidth"in B?B.offsetWidth-B.clientWidth-q-A:0,P="offsetHeight"in B?B.offsetHeight-B.clientHeight-z-F:0,Q="offsetWidth"in B?0===B.offsetWidth?0:X/B.offsetWidth:0,U="offsetHeight"in B?0===B.offsetHeight?0:O/B.offsetHeight:0;if(g===B)G="start"===d?C:"end"===d?C-v:"nearest"===d?r(H,H+v,v,z,F,H+C,H+C+y,y):C-v/2,J="start"===f?R:"center"===f?R-w/2:"end"===f?R-w:r(W,W+w,w,q,A,W+R,W+R+E,E),G=Math.max(0,G+H),J=Math.max(0,J+W);else{G="start"===d?C-Y-z:"end"===d?C-S+F+P:"nearest"===d?r(Y,S,O,z,F+P,C,C+y,y):C-(Y+O/2)+P/2,J="start"===f?R-j-q:"center"===f?R-(j+X/2)+K/2:"end"===f?R-L+A+K:r(j,L,X,q,A+K,R,R+E,E);var Z=B.scrollLeft,$=B.scrollTop;C+=$-(G=Math.max(0,Math.min($+G/U,B.scrollHeight-O/U+P))),R+=Z-(J=Math.max(0,Math.min(Z+J/Q,B.scrollWidth-X/Q+K)))}T.push({el:B,top:G,left:J})}return T};
//# sourceMappingURL=index.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/tslib@2.6.2/node_modules/tslib/tslib.es6.mjs
var tslib_es6 = __webpack_require__("../../node_modules/.pnpm/tslib@2.6.2/node_modules/tslib/tslib.es6.mjs");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/downshift@6.1.12_react@17.0.2/node_modules/downshift/dist/downshift.esm.js






let idCounter = 0;
/**
 * Accepts a parameter and returns it if it's a function
 * or a noop function if it's not. This allows us to
 * accept a callback, but not worry about it if it's not
 * passed.
 * @param {Function} cb the callback
 * @return {Function} a function
 */

function cbToCb(cb) {
  return typeof cb === 'function' ? cb : noop;
}

function noop() {}
/**
 * Scroll node into view if necessary
 * @param {HTMLElement} node the element that should scroll into view
 * @param {HTMLElement} menuNode the menu element of the component
 */


function scrollIntoView(node, menuNode) {
  if (!node) {
    return;
  }

  const actions = i(node, {
    boundary: menuNode,
    block: 'nearest',
    scrollMode: 'if-needed'
  });
  actions.forEach(_ref => {
    let {
      el,
      top,
      left
    } = _ref;
    el.scrollTop = top;
    el.scrollLeft = left;
  });
}
/**
 * @param {HTMLElement} parent the parent node
 * @param {HTMLElement} child the child node
 * @param {Window} environment The window context where downshift renders.
 * @return {Boolean} whether the parent is the child or the child is in the parent
 */


function isOrContainsNode(parent, child, environment) {
  const result = parent === child || child instanceof environment.Node && parent.contains && parent.contains(child);
  return result;
}
/**
 * Simple debounce implementation. Will call the given
 * function once after the time given has passed since
 * it was last called.
 * @param {Function} fn the function to call after the time
 * @param {Number} time the time to wait
 * @return {Function} the debounced function
 */


function debounce(fn, time) {
  let timeoutId;

  function cancel() {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
  }

  function wrapper() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    cancel();
    timeoutId = setTimeout(() => {
      timeoutId = null;
      fn(...args);
    }, time);
  }

  wrapper.cancel = cancel;
  return wrapper;
}
/**
 * This is intended to be used to compose event handlers.
 * They are executed in order until one of them sets
 * `event.preventDownshiftDefault = true`.
 * @param {...Function} fns the event handler functions
 * @return {Function} the event handler to add to an element
 */


function callAllEventHandlers() {
  for (var _len2 = arguments.length, fns = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
    fns[_key2] = arguments[_key2];
  }

  return function (event) {
    for (var _len3 = arguments.length, args = new Array(_len3 > 1 ? _len3 - 1 : 0), _key3 = 1; _key3 < _len3; _key3++) {
      args[_key3 - 1] = arguments[_key3];
    }

    return fns.some(fn => {
      if (fn) {
        fn(event, ...args);
      }

      return event.preventDownshiftDefault || event.hasOwnProperty('nativeEvent') && event.nativeEvent.preventDownshiftDefault;
    });
  };
}

function handleRefs() {
  for (var _len4 = arguments.length, refs = new Array(_len4), _key4 = 0; _key4 < _len4; _key4++) {
    refs[_key4] = arguments[_key4];
  }

  return node => {
    refs.forEach(ref => {
      if (typeof ref === 'function') {
        ref(node);
      } else if (ref) {
        ref.current = node;
      }
    });
  };
}
/**
 * This generates a unique ID for an instance of Downshift
 * @return {String} the unique ID
 */


function generateId() {
  return String(idCounter++);
}
/**
 * Resets idCounter to 0. Used for SSR.
 */


function resetIdCounter() {
  idCounter = 0;
}
/**
 * Default implementation for status message. Only added when menu is open.
 * Will specify if there are results in the list, and if so, how many,
 * and what keys are relevant.
 *
 * @param {Object} param the downshift state and other relevant properties
 * @return {String} the a11y status message
 */


function getA11yStatusMessage$1(_ref2) {
  let {
    isOpen,
    resultCount,
    previousResultCount
  } = _ref2;

  if (!isOpen) {
    return '';
  }

  if (!resultCount) {
    return 'No results are available.';
  }

  if (resultCount !== previousResultCount) {
    return `${resultCount} result${resultCount === 1 ? ' is' : 's are'} available, use up and down arrow keys to navigate. Press Enter key to select.`;
  }

  return '';
}
/**
 * Takes an argument and if it's an array, returns the first item in the array
 * otherwise returns the argument
 * @param {*} arg the maybe-array
 * @param {*} defaultValue the value if arg is falsey not defined
 * @return {*} the arg or it's first item
 */


function unwrapArray(arg, defaultValue) {
  arg = Array.isArray(arg) ?
  /* istanbul ignore next (preact) */
  arg[0] : arg;

  if (!arg && defaultValue) {
    return defaultValue;
  } else {
    return arg;
  }
}
/**
 * @param {Object} element (P)react element
 * @return {Boolean} whether it's a DOM element
 */


function isDOMElement(element) {


  return typeof element.type === 'string';
}
/**
 * @param {Object} element (P)react element
 * @return {Object} the props
 */


function getElementProps(element) {

  return element.props;
}
/**
 * Throws a helpful error message for required properties. Useful
 * to be used as a default in destructuring or object params.
 * @param {String} fnName the function name
 * @param {String} propName the prop name
 */


function requiredProp(fnName, propName) {
  // eslint-disable-next-line no-console
  console.error(`The property "${propName}" is required in "${fnName}"`);
}

const stateKeys = (/* unused pure expression or super */ null && (['highlightedIndex', 'inputValue', 'isOpen', 'selectedItem', 'type']));
/**
 * @param {Object} state the state object
 * @return {Object} state that is relevant to downshift
 */

function pickState(state) {
  if (state === void 0) {
    state = {};
  }

  const result = {};
  stateKeys.forEach(k => {
    if (state.hasOwnProperty(k)) {
      result[k] = state[k];
    }
  });
  return result;
}
/**
 * This will perform a shallow merge of the given state object
 * with the state coming from props
 * (for the controlled component scenario)
 * This is used in state updater functions so they're referencing
 * the right state regardless of where it comes from.
 *
 * @param {Object} state The state of the component/hook.
 * @param {Object} props The props that may contain controlled values.
 * @returns {Object} The merged controlled state.
 */


function getState(state, props) {
  return Object.keys(state).reduce((prevState, key) => {
    prevState[key] = isControlledProp(props, key) ? props[key] : state[key];
    return prevState;
  }, {});
}
/**
 * This determines whether a prop is a "controlled prop" meaning it is
 * state which is controlled by the outside of this component rather
 * than within this component.
 *
 * @param {Object} props The props that may contain controlled values.
 * @param {String} key the key to check
 * @return {Boolean} whether it is a controlled controlled prop
 */


function isControlledProp(props, key) {
  return props[key] !== undefined;
}
/**
 * Normalizes the 'key' property of a KeyboardEvent in IE/Edge
 * @param {Object} event a keyboardEvent object
 * @return {String} keyboard key
 */


function normalizeArrowKey(event) {
  const {
    key,
    keyCode
  } = event;
  /* istanbul ignore next (ie) */

  if (keyCode >= 37 && keyCode <= 40 && key.indexOf('Arrow') !== 0) {
    return `Arrow${key}`;
  }

  return key;
}
/**
 * Simple check if the value passed is object literal
 * @param {*} obj any things
 * @return {Boolean} whether it's object literal
 */


function isPlainObject(obj) {
  return Object.prototype.toString.call(obj) === '[object Object]';
}
/**
 * Returns the new index in the list, in a circular way. If next value is out of bonds from the total,
 * it will wrap to either 0 or itemCount - 1.
 *
 * @param {number} moveAmount Number of positions to move. Negative to move backwards, positive forwards.
 * @param {number} baseIndex The initial position to move from.
 * @param {number} itemCount The total number of items.
 * @param {Function} getItemNodeFromIndex Used to check if item is disabled.
 * @param {boolean} circular Specify if navigation is circular. Default is true.
 * @returns {number} The new index after the move.
 */


function getNextWrappingIndex(moveAmount, baseIndex, itemCount, getItemNodeFromIndex, circular) {
  if (circular === void 0) {
    circular = true;
  }

  if (itemCount === 0) {
    return -1;
  }

  const itemsLastIndex = itemCount - 1;

  if (typeof baseIndex !== 'number' || baseIndex < 0 || baseIndex >= itemCount) {
    baseIndex = moveAmount > 0 ? -1 : itemsLastIndex + 1;
  }

  let newIndex = baseIndex + moveAmount;

  if (newIndex < 0) {
    newIndex = circular ? itemsLastIndex : 0;
  } else if (newIndex > itemsLastIndex) {
    newIndex = circular ? 0 : itemsLastIndex;
  }

  const nonDisabledNewIndex = getNextNonDisabledIndex(moveAmount, newIndex, itemCount, getItemNodeFromIndex, circular);

  if (nonDisabledNewIndex === -1) {
    return baseIndex >= itemCount ? -1 : baseIndex;
  }

  return nonDisabledNewIndex;
}
/**
 * Returns the next index in the list of an item that is not disabled.
 *
 * @param {number} moveAmount Number of positions to move. Negative to move backwards, positive forwards.
 * @param {number} baseIndex The initial position to move from.
 * @param {number} itemCount The total number of items.
 * @param {Function} getItemNodeFromIndex Used to check if item is disabled.
 * @param {boolean} circular Specify if navigation is circular. Default is true.
 * @returns {number} The new index. Returns baseIndex if item is not disabled. Returns next non-disabled item otherwise. If no non-disabled found it will return -1.
 */


function getNextNonDisabledIndex(moveAmount, baseIndex, itemCount, getItemNodeFromIndex, circular) {
  const currentElementNode = getItemNodeFromIndex(baseIndex);

  if (!currentElementNode || !currentElementNode.hasAttribute('disabled')) {
    return baseIndex;
  }

  if (moveAmount > 0) {
    for (let index = baseIndex + 1; index < itemCount; index++) {
      if (!getItemNodeFromIndex(index).hasAttribute('disabled')) {
        return index;
      }
    }
  } else {
    for (let index = baseIndex - 1; index >= 0; index--) {
      if (!getItemNodeFromIndex(index).hasAttribute('disabled')) {
        return index;
      }
    }
  }

  if (circular) {
    return moveAmount > 0 ? getNextNonDisabledIndex(1, 0, itemCount, getItemNodeFromIndex, false) : getNextNonDisabledIndex(-1, itemCount - 1, itemCount, getItemNodeFromIndex, false);
  }

  return -1;
}
/**
 * Checks if event target is within the downshift elements.
 *
 * @param {EventTarget} target Target to check.
 * @param {HTMLElement[]} downshiftElements The elements that form downshift (list, toggle button etc).
 * @param {Window} environment The window context where downshift renders.
 * @param {boolean} checkActiveElement Whether to also check activeElement.
 *
 * @returns {boolean} Whether or not the target is within downshift elements.
 */


function targetWithinDownshift(target, downshiftElements, environment, checkActiveElement) {
  if (checkActiveElement === void 0) {
    checkActiveElement = true;
  }

  return downshiftElements.some(contextNode => contextNode && (isOrContainsNode(contextNode, target, environment) || checkActiveElement && isOrContainsNode(contextNode, environment.document.activeElement, environment)));
} // eslint-disable-next-line import/no-mutable-exports


let validateControlledUnchanged = (/* unused pure expression or super */ null && (noop));
/* istanbul ignore next */

if (false) {}

const cleanupStatus = debounce(documentProp => {
  getStatusDiv(documentProp).textContent = '';
}, 500);
/**
 * @param {String} status the status message
 * @param {Object} documentProp document passed by the user.
 */

function setStatus(status, documentProp) {
  const div = getStatusDiv(documentProp);

  if (!status) {
    return;
  }

  div.textContent = status;
  cleanupStatus(documentProp);
}
/**
 * Get the status node or create it if it does not already exist.
 * @param {Object} documentProp document passed by the user.
 * @return {HTMLElement} the status node.
 */


function getStatusDiv(documentProp) {
  if (documentProp === void 0) {
    documentProp = document;
  }

  let statusDiv = documentProp.getElementById('a11y-status-message');

  if (statusDiv) {
    return statusDiv;
  }

  statusDiv = documentProp.createElement('div');
  statusDiv.setAttribute('id', 'a11y-status-message');
  statusDiv.setAttribute('role', 'status');
  statusDiv.setAttribute('aria-live', 'polite');
  statusDiv.setAttribute('aria-relevant', 'additions text');
  Object.assign(statusDiv.style, {
    border: '0',
    clip: 'rect(0 0 0 0)',
    height: '1px',
    margin: '-1px',
    overflow: 'hidden',
    padding: '0',
    position: 'absolute',
    width: '1px'
  });
  documentProp.body.appendChild(statusDiv);
  return statusDiv;
}

const unknown =  false ? 0 : 0;
const mouseUp =  false ? 0 : 1;
const itemMouseEnter =  false ? 0 : 2;
const keyDownArrowUp =  false ? 0 : 3;
const keyDownArrowDown =  false ? 0 : 4;
const keyDownEscape =  false ? 0 : 5;
const keyDownEnter =  false ? 0 : 6;
const keyDownHome =  false ? 0 : 7;
const keyDownEnd =  false ? 0 : 8;
const clickItem =  false ? 0 : 9;
const blurInput =  false ? 0 : 10;
const changeInput =  false ? 0 : 11;
const keyDownSpaceButton =  false ? 0 : 12;
const clickButton =  false ? 0 : 13;
const blurButton =  false ? 0 : 14;
const controlledPropUpdatedSelectedItem =  false ? 0 : 15;
const touchEnd =  false ? 0 : 16;

var stateChangeTypes$3 = /*#__PURE__*/Object.freeze({
  __proto__: null,
  unknown: unknown,
  mouseUp: mouseUp,
  itemMouseEnter: itemMouseEnter,
  keyDownArrowUp: keyDownArrowUp,
  keyDownArrowDown: keyDownArrowDown,
  keyDownEscape: keyDownEscape,
  keyDownEnter: keyDownEnter,
  keyDownHome: keyDownHome,
  keyDownEnd: keyDownEnd,
  clickItem: clickItem,
  blurInput: blurInput,
  changeInput: changeInput,
  keyDownSpaceButton: keyDownSpaceButton,
  clickButton: clickButton,
  blurButton: blurButton,
  controlledPropUpdatedSelectedItem: controlledPropUpdatedSelectedItem,
  touchEnd: touchEnd
});

/* eslint camelcase:0 */

const Downshift = /*#__PURE__*/(/* unused pure expression or super */ null && ((() => {
  class Downshift extends Component {
    constructor(_props) {
      var _this;

      super(_props);
      _this = this;
      this.id = this.props.id || `downshift-${generateId()}`;
      this.menuId = this.props.menuId || `${this.id}-menu`;
      this.labelId = this.props.labelId || `${this.id}-label`;
      this.inputId = this.props.inputId || `${this.id}-input`;

      this.getItemId = this.props.getItemId || (index => `${this.id}-item-${index}`);

      this.input = null;
      this.items = [];
      this.itemCount = null;
      this.previousResultCount = 0;
      this.timeoutIds = [];

      this.internalSetTimeout = (fn, time) => {
        const id = setTimeout(() => {
          this.timeoutIds = this.timeoutIds.filter(i => i !== id);
          fn();
        }, time);
        this.timeoutIds.push(id);
      };

      this.setItemCount = count => {
        this.itemCount = count;
      };

      this.unsetItemCount = () => {
        this.itemCount = null;
      };

      this.setHighlightedIndex = function (highlightedIndex, otherStateToSet) {
        if (highlightedIndex === void 0) {
          highlightedIndex = _this.props.defaultHighlightedIndex;
        }

        if (otherStateToSet === void 0) {
          otherStateToSet = {};
        }

        otherStateToSet = pickState(otherStateToSet);

        _this.internalSetState({
          highlightedIndex,
          ...otherStateToSet
        });
      };

      this.clearSelection = cb => {
        this.internalSetState({
          selectedItem: null,
          inputValue: '',
          highlightedIndex: this.props.defaultHighlightedIndex,
          isOpen: this.props.defaultIsOpen
        }, cb);
      };

      this.selectItem = (item, otherStateToSet, cb) => {
        otherStateToSet = pickState(otherStateToSet);
        this.internalSetState({
          isOpen: this.props.defaultIsOpen,
          highlightedIndex: this.props.defaultHighlightedIndex,
          selectedItem: item,
          inputValue: this.props.itemToString(item),
          ...otherStateToSet
        }, cb);
      };

      this.selectItemAtIndex = (itemIndex, otherStateToSet, cb) => {
        const item = this.items[itemIndex];

        if (item == null) {
          return;
        }

        this.selectItem(item, otherStateToSet, cb);
      };

      this.selectHighlightedItem = (otherStateToSet, cb) => {
        return this.selectItemAtIndex(this.getState().highlightedIndex, otherStateToSet, cb);
      };

      this.internalSetState = (stateToSet, cb) => {
        let isItemSelected, onChangeArg;
        const onStateChangeArg = {};
        const isStateToSetFunction = typeof stateToSet === 'function'; // we want to call `onInputValueChange` before the `setState` call
        // so someone controlling the `inputValue` state gets notified of
        // the input change as soon as possible. This avoids issues with
        // preserving the cursor position.
        // See https://github.com/downshift-js/downshift/issues/217 for more info.

        if (!isStateToSetFunction && stateToSet.hasOwnProperty('inputValue')) {
          this.props.onInputValueChange(stateToSet.inputValue, { ...this.getStateAndHelpers(),
            ...stateToSet
          });
        }

        return this.setState(state => {
          state = this.getState(state);
          let newStateToSet = isStateToSetFunction ? stateToSet(state) : stateToSet; // Your own function that could modify the state that will be set.

          newStateToSet = this.props.stateReducer(state, newStateToSet); // checks if an item is selected, regardless of if it's different from
          // what was selected before
          // used to determine if onSelect and onChange callbacks should be called

          isItemSelected = newStateToSet.hasOwnProperty('selectedItem'); // this keeps track of the object we want to call with setState

          const nextState = {}; // this is just used to tell whether the state changed
          // and we're trying to update that state. OR if the selection has changed and we're
          // trying to update the selection

          if (isItemSelected && newStateToSet.selectedItem !== state.selectedItem) {
            onChangeArg = newStateToSet.selectedItem;
          }

          newStateToSet.type = newStateToSet.type || unknown;
          Object.keys(newStateToSet).forEach(key => {
            // onStateChangeArg should only have the state that is
            // actually changing
            if (state[key] !== newStateToSet[key]) {
              onStateChangeArg[key] = newStateToSet[key];
            } // the type is useful for the onStateChangeArg
            // but we don't actually want to set it in internal state.
            // this is an undocumented feature for now... Not all internalSetState
            // calls support it and I'm not certain we want them to yet.
            // But it enables users controlling the isOpen state to know when
            // the isOpen state changes due to mouseup events which is quite handy.


            if (key === 'type') {
              return;
            }

            newStateToSet[key]; // if it's coming from props, then we don't care to set it internally

            if (!isControlledProp(this.props, key)) {
              nextState[key] = newStateToSet[key];
            }
          }); // if stateToSet is a function, then we weren't able to call onInputValueChange
          // earlier, so we'll call it now that we know what the inputValue state will be.

          if (isStateToSetFunction && newStateToSet.hasOwnProperty('inputValue')) {
            this.props.onInputValueChange(newStateToSet.inputValue, { ...this.getStateAndHelpers(),
              ...newStateToSet
            });
          }

          return nextState;
        }, () => {
          // call the provided callback if it's a function
          cbToCb(cb)(); // only call the onStateChange and onChange callbacks if
          // we have relevant information to pass them.

          const hasMoreStateThanType = Object.keys(onStateChangeArg).length > 1;

          if (hasMoreStateThanType) {
            this.props.onStateChange(onStateChangeArg, this.getStateAndHelpers());
          }

          if (isItemSelected) {
            this.props.onSelect(stateToSet.selectedItem, this.getStateAndHelpers());
          }

          if (onChangeArg !== undefined) {
            this.props.onChange(onChangeArg, this.getStateAndHelpers());
          } // this is currently undocumented and therefore subject to change
          // We'll try to not break it, but just be warned.


          this.props.onUserAction(onStateChangeArg, this.getStateAndHelpers());
        });
      };

      this.rootRef = node => this._rootNode = node;

      this.getRootProps = function (_temp, _temp2) {
        let {
          refKey = 'ref',
          ref,
          ...rest
        } = _temp === void 0 ? {} : _temp;
        let {
          suppressRefError = false
        } = _temp2 === void 0 ? {} : _temp2;
        // this is used in the render to know whether the user has called getRootProps.
        // It uses that to know whether to apply the props automatically
        _this.getRootProps.called = true;
        _this.getRootProps.refKey = refKey;
        _this.getRootProps.suppressRefError = suppressRefError;

        const {
          isOpen
        } = _this.getState();

        return {
          [refKey]: handleRefs(ref, _this.rootRef),
          role: 'combobox',
          'aria-expanded': isOpen,
          'aria-haspopup': 'listbox',
          'aria-owns': isOpen ? _this.menuId : null,
          'aria-labelledby': _this.labelId,
          ...rest
        };
      };

      this.keyDownHandlers = {
        ArrowDown(event) {
          event.preventDefault();

          if (this.getState().isOpen) {
            const amount = event.shiftKey ? 5 : 1;
            this.moveHighlightedIndex(amount, {
              type: keyDownArrowDown
            });
          } else {
            this.internalSetState({
              isOpen: true,
              type: keyDownArrowDown
            }, () => {
              const itemCount = this.getItemCount();

              if (itemCount > 0) {
                const {
                  highlightedIndex
                } = this.getState();
                const nextHighlightedIndex = getNextWrappingIndex(1, highlightedIndex, itemCount, index => this.getItemNodeFromIndex(index));
                this.setHighlightedIndex(nextHighlightedIndex, {
                  type: keyDownArrowDown
                });
              }
            });
          }
        },

        ArrowUp(event) {
          event.preventDefault();

          if (this.getState().isOpen) {
            const amount = event.shiftKey ? -5 : -1;
            this.moveHighlightedIndex(amount, {
              type: keyDownArrowUp
            });
          } else {
            this.internalSetState({
              isOpen: true,
              type: keyDownArrowUp
            }, () => {
              const itemCount = this.getItemCount();

              if (itemCount > 0) {
                const {
                  highlightedIndex
                } = this.getState();
                const nextHighlightedIndex = getNextWrappingIndex(-1, highlightedIndex, itemCount, index => this.getItemNodeFromIndex(index));
                this.setHighlightedIndex(nextHighlightedIndex, {
                  type: keyDownArrowUp
                });
              }
            });
          }
        },

        Enter(event) {
          if (event.which === 229) {
            return;
          }

          const {
            isOpen,
            highlightedIndex
          } = this.getState();

          if (isOpen && highlightedIndex != null) {
            event.preventDefault();
            const item = this.items[highlightedIndex];
            const itemNode = this.getItemNodeFromIndex(highlightedIndex);

            if (item == null || itemNode && itemNode.hasAttribute('disabled')) {
              return;
            }

            this.selectHighlightedItem({
              type: keyDownEnter
            });
          }
        },

        Escape(event) {
          event.preventDefault();
          this.reset({
            type: keyDownEscape,
            ...(!this.state.isOpen && {
              selectedItem: null,
              inputValue: ''
            })
          });
        }

      };
      this.buttonKeyDownHandlers = { ...this.keyDownHandlers,

        ' '(event) {
          event.preventDefault();
          this.toggleMenu({
            type: keyDownSpaceButton
          });
        }

      };
      this.inputKeyDownHandlers = { ...this.keyDownHandlers,

        Home(event) {
          const {
            isOpen
          } = this.getState();

          if (!isOpen) {
            return;
          }

          event.preventDefault();
          const itemCount = this.getItemCount();

          if (itemCount <= 0 || !isOpen) {
            return;
          } // get next non-disabled starting downwards from 0 if that's disabled.


          const newHighlightedIndex = getNextNonDisabledIndex(1, 0, itemCount, index => this.getItemNodeFromIndex(index), false);
          this.setHighlightedIndex(newHighlightedIndex, {
            type: keyDownHome
          });
        },

        End(event) {
          const {
            isOpen
          } = this.getState();

          if (!isOpen) {
            return;
          }

          event.preventDefault();
          const itemCount = this.getItemCount();

          if (itemCount <= 0 || !isOpen) {
            return;
          } // get next non-disabled starting upwards from last index if that's disabled.


          const newHighlightedIndex = getNextNonDisabledIndex(-1, itemCount - 1, itemCount, index => this.getItemNodeFromIndex(index), false);
          this.setHighlightedIndex(newHighlightedIndex, {
            type: keyDownEnd
          });
        }

      };

      this.getToggleButtonProps = function (_temp3) {
        let {
          onClick,
          onPress,
          onKeyDown,
          onKeyUp,
          onBlur,
          ...rest
        } = _temp3 === void 0 ? {} : _temp3;

        const {
          isOpen
        } = _this.getState();

        const enabledEventHandlers = {
          onClick: callAllEventHandlers(onClick, _this.buttonHandleClick),
          onKeyDown: callAllEventHandlers(onKeyDown, _this.buttonHandleKeyDown),
          onKeyUp: callAllEventHandlers(onKeyUp, _this.buttonHandleKeyUp),
          onBlur: callAllEventHandlers(onBlur, _this.buttonHandleBlur)
        };
        const eventHandlers = rest.disabled ? {} : enabledEventHandlers;
        return {
          type: 'button',
          role: 'button',
          'aria-label': isOpen ? 'close menu' : 'open menu',
          'aria-haspopup': true,
          'data-toggle': true,
          ...eventHandlers,
          ...rest
        };
      };

      this.buttonHandleKeyUp = event => {
        // Prevent click event from emitting in Firefox
        event.preventDefault();
      };

      this.buttonHandleKeyDown = event => {
        const key = normalizeArrowKey(event);

        if (this.buttonKeyDownHandlers[key]) {
          this.buttonKeyDownHandlers[key].call(this, event);
        }
      };

      this.buttonHandleClick = event => {
        event.preventDefault(); // handle odd case for Safari and Firefox which
        // don't give the button the focus properly.

        /* istanbul ignore if (can't reasonably test this) */

        if (this.props.environment.document.activeElement === this.props.environment.document.body) {
          event.target.focus();
        } // to simplify testing components that use downshift, we'll not wrap this in a setTimeout
        // if the NODE_ENV is test. With the proper build system, this should be dead code eliminated
        // when building for production and should therefore have no impact on production code.


        if (false) {} else {
          // Ensure that toggle of menu occurs after the potential blur event in iOS
          this.internalSetTimeout(() => this.toggleMenu({
            type: clickButton
          }));
        }
      };

      this.buttonHandleBlur = event => {
        const blurTarget = event.target; // Save blur target for comparison with activeElement later
        // Need setTimeout, so that when the user presses Tab, the activeElement is the next focused element, not body element

        this.internalSetTimeout(() => {
          if (!this.isMouseDown && (this.props.environment.document.activeElement == null || this.props.environment.document.activeElement.id !== this.inputId) && this.props.environment.document.activeElement !== blurTarget // Do nothing if we refocus the same element again (to solve issue in Safari on iOS)
          ) {
            this.reset({
              type: blurButton
            });
          }
        });
      };

      this.getLabelProps = props => {
        return {
          htmlFor: this.inputId,
          id: this.labelId,
          ...props
        };
      };

      this.getInputProps = function (_temp4) {
        let {
          onKeyDown,
          onBlur,
          onChange,
          onInput,
          onChangeText,
          ...rest
        } = _temp4 === void 0 ? {} : _temp4;
        let onChangeKey;
        let eventHandlers = {};
        /* istanbul ignore next (preact) */

        {
          onChangeKey = 'onChange';
        }

        const {
          inputValue,
          isOpen,
          highlightedIndex
        } = _this.getState();

        if (!rest.disabled) {
          eventHandlers = {
            [onChangeKey]: callAllEventHandlers(onChange, onInput, _this.inputHandleChange),
            onKeyDown: callAllEventHandlers(onKeyDown, _this.inputHandleKeyDown),
            onBlur: callAllEventHandlers(onBlur, _this.inputHandleBlur)
          };
        }

        return {
          'aria-autocomplete': 'list',
          'aria-activedescendant': isOpen && typeof highlightedIndex === 'number' && highlightedIndex >= 0 ? _this.getItemId(highlightedIndex) : null,
          'aria-controls': isOpen ? _this.menuId : null,
          'aria-labelledby': _this.labelId,
          // https://developer.mozilla.org/en-US/docs/Web/Security/Securing_your_site/Turning_off_form_autocompletion
          // revert back since autocomplete="nope" is ignored on latest Chrome and Opera
          autoComplete: 'off',
          value: inputValue,
          id: _this.inputId,
          ...eventHandlers,
          ...rest
        };
      };

      this.inputHandleKeyDown = event => {
        const key = normalizeArrowKey(event);

        if (key && this.inputKeyDownHandlers[key]) {
          this.inputKeyDownHandlers[key].call(this, event);
        }
      };

      this.inputHandleChange = event => {
        this.internalSetState({
          type: changeInput,
          isOpen: true,
          inputValue: event.target.value,
          highlightedIndex: this.props.defaultHighlightedIndex
        });
      };

      this.inputHandleBlur = () => {
        // Need setTimeout, so that when the user presses Tab, the activeElement is the next focused element, not the body element
        this.internalSetTimeout(() => {
          const downshiftButtonIsActive = this.props.environment.document && !!this.props.environment.document.activeElement && !!this.props.environment.document.activeElement.dataset && this.props.environment.document.activeElement.dataset.toggle && this._rootNode && this._rootNode.contains(this.props.environment.document.activeElement);

          if (!this.isMouseDown && !downshiftButtonIsActive) {
            this.reset({
              type: blurInput
            });
          }
        });
      };

      this.menuRef = node => {
        this._menuNode = node;
      };

      this.getMenuProps = function (_temp5, _temp6) {
        let {
          refKey = 'ref',
          ref,
          ...props
        } = _temp5 === void 0 ? {} : _temp5;
        let {
          suppressRefError = false
        } = _temp6 === void 0 ? {} : _temp6;
        _this.getMenuProps.called = true;
        _this.getMenuProps.refKey = refKey;
        _this.getMenuProps.suppressRefError = suppressRefError;
        return {
          [refKey]: handleRefs(ref, _this.menuRef),
          role: 'listbox',
          'aria-labelledby': props && props['aria-label'] ? null : _this.labelId,
          id: _this.menuId,
          ...props
        };
      };

      this.getItemProps = function (_temp7) {
        let {
          onMouseMove,
          onMouseDown,
          onClick,
          onPress,
          index,
          item =  true ?
          /* istanbul ignore next */
          undefined : 0,
          ...rest
        } = _temp7 === void 0 ? {} : _temp7;

        if (index === undefined) {
          _this.items.push(item);

          index = _this.items.indexOf(item);
        } else {
          _this.items[index] = item;
        }

        const onSelectKey = 'onClick';
        const customClickHandler = onClick;
        const enabledEventHandlers = {
          // onMouseMove is used over onMouseEnter here. onMouseMove
          // is only triggered on actual mouse movement while onMouseEnter
          // can fire on DOM changes, interrupting keyboard navigation
          onMouseMove: callAllEventHandlers(onMouseMove, () => {
            if (index === _this.getState().highlightedIndex) {
              return;
            }

            _this.setHighlightedIndex(index, {
              type: itemMouseEnter
            }); // We never want to manually scroll when changing state based
            // on `onMouseMove` because we will be moving the element out
            // from under the user which is currently scrolling/moving the
            // cursor


            _this.avoidScrolling = true;

            _this.internalSetTimeout(() => _this.avoidScrolling = false, 250);
          }),
          onMouseDown: callAllEventHandlers(onMouseDown, event => {
            // This prevents the activeElement from being changed
            // to the item so it can remain with the current activeElement
            // which is a more common use case.
            event.preventDefault();
          }),
          [onSelectKey]: callAllEventHandlers(customClickHandler, () => {
            _this.selectItemAtIndex(index, {
              type: clickItem
            });
          })
        }; // Passing down the onMouseDown handler to prevent redirect
        // of the activeElement if clicking on disabled items

        const eventHandlers = rest.disabled ? {
          onMouseDown: enabledEventHandlers.onMouseDown
        } : enabledEventHandlers;
        return {
          id: _this.getItemId(index),
          role: 'option',
          'aria-selected': _this.getState().highlightedIndex === index,
          ...eventHandlers,
          ...rest
        };
      };

      this.clearItems = () => {
        this.items = [];
      };

      this.reset = function (otherStateToSet, cb) {
        if (otherStateToSet === void 0) {
          otherStateToSet = {};
        }

        otherStateToSet = pickState(otherStateToSet);

        _this.internalSetState(_ref => {
          let {
            selectedItem
          } = _ref;
          return {
            isOpen: _this.props.defaultIsOpen,
            highlightedIndex: _this.props.defaultHighlightedIndex,
            inputValue: _this.props.itemToString(selectedItem),
            ...otherStateToSet
          };
        }, cb);
      };

      this.toggleMenu = function (otherStateToSet, cb) {
        if (otherStateToSet === void 0) {
          otherStateToSet = {};
        }

        otherStateToSet = pickState(otherStateToSet);

        _this.internalSetState(_ref2 => {
          let {
            isOpen
          } = _ref2;
          return {
            isOpen: !isOpen,
            ...(isOpen && {
              highlightedIndex: _this.props.defaultHighlightedIndex
            }),
            ...otherStateToSet
          };
        }, () => {
          const {
            isOpen,
            highlightedIndex
          } = _this.getState();

          if (isOpen) {
            if (_this.getItemCount() > 0 && typeof highlightedIndex === 'number') {
              _this.setHighlightedIndex(highlightedIndex, otherStateToSet);
            }
          }

          cbToCb(cb)();
        });
      };

      this.openMenu = cb => {
        this.internalSetState({
          isOpen: true
        }, cb);
      };

      this.closeMenu = cb => {
        this.internalSetState({
          isOpen: false
        }, cb);
      };

      this.updateStatus = debounce(() => {
        const state = this.getState();
        const item = this.items[state.highlightedIndex];
        const resultCount = this.getItemCount();
        const status = this.props.getA11yStatusMessage({
          itemToString: this.props.itemToString,
          previousResultCount: this.previousResultCount,
          resultCount,
          highlightedItem: item,
          ...state
        });
        this.previousResultCount = resultCount;
        setStatus(status, this.props.environment.document);
      }, 200);
      // fancy destructuring + defaults + aliases
      // this basically says each value of state should either be set to
      // the initial value or the default value if the initial value is not provided
      const {
        defaultHighlightedIndex,
        initialHighlightedIndex: _highlightedIndex = defaultHighlightedIndex,
        defaultIsOpen,
        initialIsOpen: _isOpen = defaultIsOpen,
        initialInputValue: _inputValue = '',
        initialSelectedItem: _selectedItem = null
      } = this.props;

      const _state = this.getState({
        highlightedIndex: _highlightedIndex,
        isOpen: _isOpen,
        inputValue: _inputValue,
        selectedItem: _selectedItem
      });

      if (_state.selectedItem != null && this.props.initialInputValue === undefined) {
        _state.inputValue = this.props.itemToString(_state.selectedItem);
      }

      this.state = _state;
    }

    /**
     * Clear all running timeouts
     */
    internalClearTimeouts() {
      this.timeoutIds.forEach(id => {
        clearTimeout(id);
      });
      this.timeoutIds = [];
    }
    /**
     * Gets the state based on internal state or props
     * If a state value is passed via props, then that
     * is the value given, otherwise it's retrieved from
     * stateToMerge
     *
     * @param {Object} stateToMerge defaults to this.state
     * @return {Object} the state
     */


    getState(stateToMerge) {
      if (stateToMerge === void 0) {
        stateToMerge = this.state;
      }

      return getState(stateToMerge, this.props);
    }

    getItemCount() {
      // things read better this way. They're in priority order:
      // 1. `this.itemCount`
      // 2. `this.props.itemCount`
      // 3. `this.items.length`
      let itemCount = this.items.length;

      if (this.itemCount != null) {
        itemCount = this.itemCount;
      } else if (this.props.itemCount !== undefined) {
        itemCount = this.props.itemCount;
      }

      return itemCount;
    }

    getItemNodeFromIndex(index) {
      return this.props.environment.document.getElementById(this.getItemId(index));
    }

    scrollHighlightedItemIntoView() {
      /* istanbul ignore else (react-native) */
      {
        const node = this.getItemNodeFromIndex(this.getState().highlightedIndex);
        this.props.scrollIntoView(node, this._menuNode);
      }
    }

    moveHighlightedIndex(amount, otherStateToSet) {
      const itemCount = this.getItemCount();
      const {
        highlightedIndex
      } = this.getState();

      if (itemCount > 0) {
        const nextHighlightedIndex = getNextWrappingIndex(amount, highlightedIndex, itemCount, index => this.getItemNodeFromIndex(index));
        this.setHighlightedIndex(nextHighlightedIndex, otherStateToSet);
      }
    }

    getStateAndHelpers() {
      const {
        highlightedIndex,
        inputValue,
        selectedItem,
        isOpen
      } = this.getState();
      const {
        itemToString
      } = this.props;
      const {
        id
      } = this;
      const {
        getRootProps,
        getToggleButtonProps,
        getLabelProps,
        getMenuProps,
        getInputProps,
        getItemProps,
        openMenu,
        closeMenu,
        toggleMenu,
        selectItem,
        selectItemAtIndex,
        selectHighlightedItem,
        setHighlightedIndex,
        clearSelection,
        clearItems,
        reset,
        setItemCount,
        unsetItemCount,
        internalSetState: setState
      } = this;
      return {
        // prop getters
        getRootProps,
        getToggleButtonProps,
        getLabelProps,
        getMenuProps,
        getInputProps,
        getItemProps,
        // actions
        reset,
        openMenu,
        closeMenu,
        toggleMenu,
        selectItem,
        selectItemAtIndex,
        selectHighlightedItem,
        setHighlightedIndex,
        clearSelection,
        clearItems,
        setItemCount,
        unsetItemCount,
        setState,
        // props
        itemToString,
        // derived
        id,
        // state
        highlightedIndex,
        inputValue,
        isOpen,
        selectedItem
      };
    } //////////////////////////// ROOT


    componentDidMount() {
      /* istanbul ignore if (react-native) */
      if (false) {}
      /* istanbul ignore if (react-native) */


      {
        // this.isMouseDown helps us track whether the mouse is currently held down.
        // This is useful when the user clicks on an item in the list, but holds the mouse
        // down long enough for the list to disappear (because the blur event fires on the input)
        // this.isMouseDown is used in the blur handler on the input to determine whether the blur event should
        // trigger hiding the menu.
        const onMouseDown = () => {
          this.isMouseDown = true;
        };

        const onMouseUp = event => {
          this.isMouseDown = false; // if the target element or the activeElement is within a downshift node
          // then we don't want to reset downshift

          const contextWithinDownshift = targetWithinDownshift(event.target, [this._rootNode, this._menuNode], this.props.environment);

          if (!contextWithinDownshift && this.getState().isOpen) {
            this.reset({
              type: mouseUp
            }, () => this.props.onOuterClick(this.getStateAndHelpers()));
          }
        }; // Touching an element in iOS gives focus and hover states, but touching out of
        // the element will remove hover, and persist the focus state, resulting in the
        // blur event not being triggered.
        // this.isTouchMove helps us track whether the user is tapping or swiping on a touch screen.
        // If the user taps outside of Downshift, the component should be reset,
        // but not if the user is swiping


        const onTouchStart = () => {
          this.isTouchMove = false;
        };

        const onTouchMove = () => {
          this.isTouchMove = true;
        };

        const onTouchEnd = event => {
          const contextWithinDownshift = targetWithinDownshift(event.target, [this._rootNode, this._menuNode], this.props.environment, false);

          if (!this.isTouchMove && !contextWithinDownshift && this.getState().isOpen) {
            this.reset({
              type: touchEnd
            }, () => this.props.onOuterClick(this.getStateAndHelpers()));
          }
        };

        const {
          environment
        } = this.props;
        environment.addEventListener('mousedown', onMouseDown);
        environment.addEventListener('mouseup', onMouseUp);
        environment.addEventListener('touchstart', onTouchStart);
        environment.addEventListener('touchmove', onTouchMove);
        environment.addEventListener('touchend', onTouchEnd);

        this.cleanup = () => {
          this.internalClearTimeouts();
          this.updateStatus.cancel();
          environment.removeEventListener('mousedown', onMouseDown);
          environment.removeEventListener('mouseup', onMouseUp);
          environment.removeEventListener('touchstart', onTouchStart);
          environment.removeEventListener('touchmove', onTouchMove);
          environment.removeEventListener('touchend', onTouchEnd);
        };
      }
    }

    shouldScroll(prevState, prevProps) {
      const {
        highlightedIndex: currentHighlightedIndex
      } = this.props.highlightedIndex === undefined ? this.getState() : this.props;
      const {
        highlightedIndex: prevHighlightedIndex
      } = prevProps.highlightedIndex === undefined ? prevState : prevProps;
      const scrollWhenOpen = currentHighlightedIndex && this.getState().isOpen && !prevState.isOpen;
      const scrollWhenNavigating = currentHighlightedIndex !== prevHighlightedIndex;
      return scrollWhenOpen || scrollWhenNavigating;
    }

    componentDidUpdate(prevProps, prevState) {
      if (false) {}

      if (isControlledProp(this.props, 'selectedItem') && this.props.selectedItemChanged(prevProps.selectedItem, this.props.selectedItem)) {
        this.internalSetState({
          type: controlledPropUpdatedSelectedItem,
          inputValue: this.props.itemToString(this.props.selectedItem)
        });
      }

      if (!this.avoidScrolling && this.shouldScroll(prevState, prevProps)) {
        this.scrollHighlightedItemIntoView();
      }
      /* istanbul ignore else (react-native) */


      {
        this.updateStatus();
      }
    }

    componentWillUnmount() {
      this.cleanup(); // avoids memory leak
    }

    render() {
      const children = unwrapArray(this.props.children, noop); // because the items are rerendered every time we call the children
      // we clear this out each render and it will be populated again as
      // getItemProps is called.

      this.clearItems(); // we reset this so we know whether the user calls getRootProps during
      // this render. If they do then we don't need to do anything,
      // if they don't then we need to clone the element they return and
      // apply the props for them.

      this.getRootProps.called = false;
      this.getRootProps.refKey = undefined;
      this.getRootProps.suppressRefError = undefined; // we do something similar for getMenuProps

      this.getMenuProps.called = false;
      this.getMenuProps.refKey = undefined;
      this.getMenuProps.suppressRefError = undefined; // we do something similar for getLabelProps

      this.getLabelProps.called = false; // and something similar for getInputProps

      this.getInputProps.called = false;
      const element = unwrapArray(children(this.getStateAndHelpers()));

      if (!element) {
        return null;
      }

      if (this.getRootProps.called || this.props.suppressRefError) {
        if (false) {}

        return element;
      } else if (isDOMElement(element)) {
        // they didn't apply the root props, but we can clone
        // this and apply the props ourselves
        return /*#__PURE__*/cloneElement(element, this.getRootProps(getElementProps(element)));
      }
      /* istanbul ignore else */


      if (false) {}
      /* istanbul ignore next */


      return undefined;
    }

  }

  Downshift.defaultProps = {
    defaultHighlightedIndex: null,
    defaultIsOpen: false,
    getA11yStatusMessage: getA11yStatusMessage$1,
    itemToString: i => {
      if (i == null) {
        return '';
      }

      if (false) {}

      return String(i);
    },
    onStateChange: noop,
    onInputValueChange: noop,
    onUserAction: noop,
    onChange: noop,
    onSelect: noop,
    onOuterClick: noop,
    selectedItemChanged: (prevItem, item) => prevItem !== item,
    environment:
    /* istanbul ignore next (ssr) */
    typeof window === 'undefined' ? {} : window,
    stateReducer: (state, stateToSet) => stateToSet,
    suppressRefError: false,
    scrollIntoView
  };
  Downshift.stateChangeTypes = stateChangeTypes$3;
  return Downshift;
})()));

 false ? 0 : void 0;
var Downshift$1 = (/* unused pure expression or super */ null && (Downshift));

function validateGetMenuPropsCalledCorrectly(node, _ref3) {
  let {
    refKey
  } = _ref3;

  if (!node) {
    // eslint-disable-next-line no-console
    console.error(`downshift: The ref prop "${refKey}" from getMenuProps was not applied correctly on your menu element.`);
  }
}

function validateGetRootPropsCalledCorrectly(element, _ref4) {
  let {
    refKey
  } = _ref4;
  const refKeySpecified = refKey !== 'ref';
  const isComposite = !isDOMElement(element);

  if (isComposite && !refKeySpecified && !isForwardRef(element)) {
    // eslint-disable-next-line no-console
    console.error('downshift: You returned a non-DOM element. You must specify a refKey in getRootProps');
  } else if (!isComposite && refKeySpecified) {
    // eslint-disable-next-line no-console
    console.error(`downshift: You returned a DOM element. You should not specify a refKey in getRootProps. You specified "${refKey}"`);
  }

  if (!isForwardRef(element) && !getElementProps(element)[refKey]) {
    // eslint-disable-next-line no-console
    console.error(`downshift: You must apply the ref prop "${refKey}" from getRootProps onto your root element.`);
  }
}

const dropdownDefaultStateValues = {
  highlightedIndex: -1,
  isOpen: false,
  selectedItem: null,
  inputValue: ''
};

function callOnChangeProps(action, state, newState) {
  const {
    props,
    type
  } = action;
  const changes = {};
  Object.keys(state).forEach(key => {
    invokeOnChangeHandler(key, action, state, newState);

    if (newState[key] !== state[key]) {
      changes[key] = newState[key];
    }
  });

  if (props.onStateChange && Object.keys(changes).length) {
    props.onStateChange({
      type,
      ...changes
    });
  }
}

function invokeOnChangeHandler(key, action, state, newState) {
  const {
    props,
    type
  } = action;
  const handler = `on${capitalizeString(key)}Change`;

  if (props[handler] && newState[key] !== undefined && newState[key] !== state[key]) {
    props[handler]({
      type,
      ...newState
    });
  }
}
/**
 * Default state reducer that returns the changes.
 *
 * @param {Object} s state.
 * @param {Object} a action with changes.
 * @returns {Object} changes.
 */


function stateReducer(s, a) {
  return a.changes;
}
/**
 * Returns a message to be added to aria-live region when item is selected.
 *
 * @param {Object} selectionParameters Parameters required to build the message.
 * @returns {string} The a11y message.
 */


function getA11ySelectionMessage(selectionParameters) {
  const {
    selectedItem,
    itemToString: itemToStringLocal
  } = selectionParameters;
  return selectedItem ? `${itemToStringLocal(selectedItem)} has been selected.` : '';
}
/**
 * Debounced call for updating the a11y message.
 */


const updateA11yStatus = debounce((getA11yMessage, document) => {
  setStatus(getA11yMessage(), document);
}, 200); // istanbul ignore next

const useIsomorphicLayoutEffect = typeof window !== 'undefined' && typeof window.document !== 'undefined' && typeof window.document.createElement !== 'undefined' ? react.useLayoutEffect : react.useEffect;

function useElementIds(_ref) {
  let {
    id = `downshift-${generateId()}`,
    labelId,
    menuId,
    getItemId,
    toggleButtonId,
    inputId
  } = _ref;
  const elementIdsRef = (0,react.useRef)({
    labelId: labelId || `${id}-label`,
    menuId: menuId || `${id}-menu`,
    getItemId: getItemId || (index => `${id}-item-${index}`),
    toggleButtonId: toggleButtonId || `${id}-toggle-button`,
    inputId: inputId || `${id}-input`
  });
  return elementIdsRef.current;
}

function getItemIndex(index, item, items) {
  if (index !== undefined) {
    return index;
  }

  if (items.length === 0) {
    return -1;
  }

  return items.indexOf(item);
}

function itemToString(item) {
  return item ? String(item) : '';
}

function isAcceptedCharacterKey(key) {
  return /^\S{1}$/.test(key);
}

function capitalizeString(string) {
  return `${string.slice(0, 1).toUpperCase()}${string.slice(1)}`;
}

function useLatestRef(val) {
  const ref = (0,react.useRef)(val); // technically this is not "concurrent mode safe" because we're manipulating
  // the value during render (so it's not idempotent). However, the places this
  // hook is used is to support memoizing callbacks which will be called
  // *during* render, so we need the latest values *during* render.
  // If not for this, then we'd probably want to use useLayoutEffect instead.

  ref.current = val;
  return ref;
}
/**
 * Computes the controlled state using a the previous state, props,
 * two reducers, one from downshift and an optional one from the user.
 * Also calls the onChange handlers for state values that have changed.
 *
 * @param {Function} reducer Reducer function from downshift.
 * @param {Object} initialState Initial state of the hook.
 * @param {Object} props The hook props.
 * @returns {Array} An array with the state and an action dispatcher.
 */


function useEnhancedReducer(reducer, initialState, props) {
  const prevStateRef = (0,react.useRef)();
  const actionRef = (0,react.useRef)();
  const enhancedReducer = (0,react.useCallback)((state, action) => {
    actionRef.current = action;
    state = getState(state, action.props);
    const changes = reducer(state, action);
    const newState = action.props.stateReducer(state, { ...action,
      changes
    });
    return newState;
  }, [reducer]);
  const [state, dispatch] = (0,react.useReducer)(enhancedReducer, initialState);
  const propsRef = useLatestRef(props);
  const dispatchWithProps = (0,react.useCallback)(action => dispatch({
    props: propsRef.current,
    ...action
  }), [propsRef]);
  const action = actionRef.current;
  (0,react.useEffect)(() => {
    if (action && prevStateRef.current && prevStateRef.current !== state) {
      callOnChangeProps(action, getState(prevStateRef.current, action.props), state);
    }

    prevStateRef.current = state;
  }, [state, props, action]);
  return [state, dispatchWithProps];
}
/**
 * Wraps the useEnhancedReducer and applies the controlled prop values before
 * returning the new state.
 *
 * @param {Function} reducer Reducer function from downshift.
 * @param {Object} initialState Initial state of the hook.
 * @param {Object} props The hook props.
 * @returns {Array} An array with the state and an action dispatcher.
 */


function useControlledReducer$1(reducer, initialState, props) {
  const [state, dispatch] = useEnhancedReducer(reducer, initialState, props);
  return [getState(state, props), dispatch];
}

const defaultProps$3 = {
  itemToString,
  stateReducer,
  getA11ySelectionMessage,
  scrollIntoView,
  circularNavigation: false,
  environment:
  /* istanbul ignore next (ssr) */
  typeof window === 'undefined' ? {} : window
};

function getDefaultValue$1(props, propKey, defaultStateValues) {
  if (defaultStateValues === void 0) {
    defaultStateValues = dropdownDefaultStateValues;
  }

  const defaultValue = props[`default${capitalizeString(propKey)}`];

  if (defaultValue !== undefined) {
    return defaultValue;
  }

  return defaultStateValues[propKey];
}

function getInitialValue$1(props, propKey, defaultStateValues) {
  if (defaultStateValues === void 0) {
    defaultStateValues = dropdownDefaultStateValues;
  }

  const value = props[propKey];

  if (value !== undefined) {
    return value;
  }

  const initialValue = props[`initial${capitalizeString(propKey)}`];

  if (initialValue !== undefined) {
    return initialValue;
  }

  return getDefaultValue$1(props, propKey, defaultStateValues);
}

function getInitialState$2(props) {
  const selectedItem = getInitialValue$1(props, 'selectedItem');
  const isOpen = getInitialValue$1(props, 'isOpen');
  const highlightedIndex = getInitialValue$1(props, 'highlightedIndex');
  const inputValue = getInitialValue$1(props, 'inputValue');
  return {
    highlightedIndex: highlightedIndex < 0 && selectedItem && isOpen ? props.items.indexOf(selectedItem) : highlightedIndex,
    isOpen,
    selectedItem,
    inputValue
  };
}

function getHighlightedIndexOnOpen(props, state, offset, getItemNodeFromIndex) {
  const {
    items,
    initialHighlightedIndex,
    defaultHighlightedIndex
  } = props;
  const {
    selectedItem,
    highlightedIndex
  } = state;

  if (items.length === 0) {
    return -1;
  } // initialHighlightedIndex will give value to highlightedIndex on initial state only.


  if (initialHighlightedIndex !== undefined && highlightedIndex === initialHighlightedIndex) {
    return initialHighlightedIndex;
  }

  if (defaultHighlightedIndex !== undefined) {
    return defaultHighlightedIndex;
  }

  if (selectedItem) {
    if (offset === 0) {
      return items.indexOf(selectedItem);
    }

    return getNextWrappingIndex(offset, items.indexOf(selectedItem), items.length, getItemNodeFromIndex, false);
  }

  if (offset === 0) {
    return -1;
  }

  return offset < 0 ? items.length - 1 : 0;
}
/**
 * Reuse the movement tracking of mouse and touch events.
 *
 * @param {boolean} isOpen Whether the dropdown is open or not.
 * @param {Array<Object>} downshiftElementRefs Downshift element refs to track movement (toggleButton, menu etc.)
 * @param {Object} environment Environment where component/hook exists.
 * @param {Function} handleBlur Handler on blur from mouse or touch.
 * @returns {Object} Ref containing whether mouseDown or touchMove event is happening
 */


function useMouseAndTouchTracker(isOpen, downshiftElementRefs, environment, handleBlur) {
  const mouseAndTouchTrackersRef = (0,react.useRef)({
    isMouseDown: false,
    isTouchMove: false
  });
  (0,react.useEffect)(() => {
    // The same strategy for checking if a click occurred inside or outside downsift
    // as in downshift.js.
    const onMouseDown = () => {
      mouseAndTouchTrackersRef.current.isMouseDown = true;
    };

    const onMouseUp = event => {
      mouseAndTouchTrackersRef.current.isMouseDown = false;

      if (isOpen && !targetWithinDownshift(event.target, downshiftElementRefs.map(ref => ref.current), environment)) {
        handleBlur();
      }
    };

    const onTouchStart = () => {
      mouseAndTouchTrackersRef.current.isTouchMove = false;
    };

    const onTouchMove = () => {
      mouseAndTouchTrackersRef.current.isTouchMove = true;
    };

    const onTouchEnd = event => {
      if (isOpen && !mouseAndTouchTrackersRef.current.isTouchMove && !targetWithinDownshift(event.target, downshiftElementRefs.map(ref => ref.current), environment, false)) {
        handleBlur();
      }
    };

    environment.addEventListener('mousedown', onMouseDown);
    environment.addEventListener('mouseup', onMouseUp);
    environment.addEventListener('touchstart', onTouchStart);
    environment.addEventListener('touchmove', onTouchMove);
    environment.addEventListener('touchend', onTouchEnd);
    return function cleanup() {
      environment.removeEventListener('mousedown', onMouseDown);
      environment.removeEventListener('mouseup', onMouseUp);
      environment.removeEventListener('touchstart', onTouchStart);
      environment.removeEventListener('touchmove', onTouchMove);
      environment.removeEventListener('touchend', onTouchEnd);
    }; // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen, environment]);
  return mouseAndTouchTrackersRef;
}
/* istanbul ignore next */
// eslint-disable-next-line import/no-mutable-exports


let useGetterPropsCalledChecker = () => noop;
/**
 * Custom hook that checks if getter props are called correctly.
 *
 * @param  {...any} propKeys Getter prop names to be handled.
 * @returns {Function} Setter function called inside getter props to set call information.
 */

/* istanbul ignore next */


if (false) {}

function useA11yMessageSetter(getA11yMessage, dependencyArray, _ref2) {
  let {
    isInitialMount,
    highlightedIndex,
    items,
    environment,
    ...rest
  } = _ref2;
  // Sets a11y status message on changes in state.
  (0,react.useEffect)(() => {
    if (isInitialMount || false) {
      return;
    }

    updateA11yStatus(() => getA11yMessage({
      highlightedIndex,
      highlightedItem: items[highlightedIndex],
      resultCount: items.length,
      ...rest
    }), environment.document); // eslint-disable-next-line react-hooks/exhaustive-deps
  }, dependencyArray);
}

function useScrollIntoView(_ref3) {
  let {
    highlightedIndex,
    isOpen,
    itemRefs,
    getItemNodeFromIndex,
    menuElement,
    scrollIntoView: scrollIntoViewProp
  } = _ref3;
  // used not to scroll on highlight by mouse.
  const shouldScrollRef = (0,react.useRef)(true); // Scroll on highlighted item if change comes from keyboard.

  useIsomorphicLayoutEffect(() => {
    if (highlightedIndex < 0 || !isOpen || !Object.keys(itemRefs.current).length) {
      return;
    }

    if (shouldScrollRef.current === false) {
      shouldScrollRef.current = true;
    } else {
      scrollIntoViewProp(getItemNodeFromIndex(highlightedIndex), menuElement);
    } // eslint-disable-next-line react-hooks/exhaustive-deps

  }, [highlightedIndex]);
  return shouldScrollRef;
} // eslint-disable-next-line import/no-mutable-exports


let useControlPropsValidator = noop;
/* istanbul ignore next */

if (false) {}

/* eslint-disable complexity */

function downshiftCommonReducer(state, action, stateChangeTypes) {
  const {
    type,
    props
  } = action;
  let changes;

  switch (type) {
    case stateChangeTypes.ItemMouseMove:
      changes = {
        highlightedIndex: action.disabled ? -1 : action.index
      };
      break;

    case stateChangeTypes.MenuMouseLeave:
      changes = {
        highlightedIndex: -1
      };
      break;

    case stateChangeTypes.ToggleButtonClick:
    case stateChangeTypes.FunctionToggleMenu:
      changes = {
        isOpen: !state.isOpen,
        highlightedIndex: state.isOpen ? -1 : getHighlightedIndexOnOpen(props, state, 0)
      };
      break;

    case stateChangeTypes.FunctionOpenMenu:
      changes = {
        isOpen: true,
        highlightedIndex: getHighlightedIndexOnOpen(props, state, 0)
      };
      break;

    case stateChangeTypes.FunctionCloseMenu:
      changes = {
        isOpen: false
      };
      break;

    case stateChangeTypes.FunctionSetHighlightedIndex:
      changes = {
        highlightedIndex: action.highlightedIndex
      };
      break;

    case stateChangeTypes.FunctionSetInputValue:
      changes = {
        inputValue: action.inputValue
      };
      break;

    case stateChangeTypes.FunctionReset:
      changes = {
        highlightedIndex: getDefaultValue$1(props, 'highlightedIndex'),
        isOpen: getDefaultValue$1(props, 'isOpen'),
        selectedItem: getDefaultValue$1(props, 'selectedItem'),
        inputValue: getDefaultValue$1(props, 'inputValue')
      };
      break;

    default:
      throw new Error('Reducer called without proper action type.');
  }

  return { ...state,
    ...changes
  };
}
/* eslint-enable complexity */

function getItemIndexByCharacterKey(_a) {
    var keysSoFar = _a.keysSoFar, highlightedIndex = _a.highlightedIndex, items = _a.items, itemToString = _a.itemToString, getItemNodeFromIndex = _a.getItemNodeFromIndex;
    var lowerCasedKeysSoFar = keysSoFar.toLowerCase();
    for (var index = 0; index < items.length; index++) {
        var offsetIndex = (index + highlightedIndex + 1) % items.length;
        var item = items[offsetIndex];
        if (item !== undefined &&
            itemToString(item)
                .toLowerCase()
                .startsWith(lowerCasedKeysSoFar)) {
            var element = getItemNodeFromIndex(offsetIndex);
            if (!(element === null || element === void 0 ? void 0 : element.hasAttribute('disabled'))) {
                return offsetIndex;
            }
        }
    }
    return highlightedIndex;
}
var propTypes$2 = {
    items: (prop_types_default()).array.isRequired,
    itemToString: (prop_types_default()).func,
    getA11yStatusMessage: (prop_types_default()).func,
    getA11ySelectionMessage: (prop_types_default()).func,
    circularNavigation: (prop_types_default()).bool,
    highlightedIndex: (prop_types_default()).number,
    defaultHighlightedIndex: (prop_types_default()).number,
    initialHighlightedIndex: (prop_types_default()).number,
    isOpen: (prop_types_default()).bool,
    defaultIsOpen: (prop_types_default()).bool,
    initialIsOpen: (prop_types_default()).bool,
    selectedItem: (prop_types_default()).any,
    initialSelectedItem: (prop_types_default()).any,
    defaultSelectedItem: (prop_types_default()).any,
    id: (prop_types_default()).string,
    labelId: (prop_types_default()).string,
    menuId: (prop_types_default()).string,
    getItemId: (prop_types_default()).func,
    toggleButtonId: (prop_types_default()).string,
    stateReducer: (prop_types_default()).func,
    onSelectedItemChange: (prop_types_default()).func,
    onHighlightedIndexChange: (prop_types_default()).func,
    onStateChange: (prop_types_default()).func,
    onIsOpenChange: (prop_types_default()).func,
    environment: prop_types_default().shape({
        addEventListener: (prop_types_default()).func,
        removeEventListener: (prop_types_default()).func,
        document: prop_types_default().shape({
            getElementById: (prop_types_default()).func,
            activeElement: (prop_types_default()).any,
            body: (prop_types_default()).any
        })
    })
};
/**
 * Default implementation for status message. Only added when menu is open.
 * Will specift if there are results in the list, and if so, how many,
 * and what keys are relevant.
 *
 * @param {Object} param the downshift state and other relevant properties
 * @return {String} the a11y status message
 */
function getA11yStatusMessage(_a) {
    var isOpen = _a.isOpen, resultCount = _a.resultCount, previousResultCount = _a.previousResultCount;
    if (!isOpen) {
        return '';
    }
    if (!resultCount) {
        return 'No results are available.';
    }
    if (resultCount !== previousResultCount) {
        return "".concat(resultCount, " result").concat(resultCount === 1 ? ' is' : 's are', " available, use up and down arrow keys to navigate. Press Enter or Space Bar keys to select.");
    }
    return '';
}
var defaultProps$2 = (0,tslib_es6/* __assign */.Cl)((0,tslib_es6/* __assign */.Cl)({}, defaultProps$3), { getA11yStatusMessage: getA11yStatusMessage });
// eslint-disable-next-line import/no-mutable-exports
var validatePropTypes$2 = noop;
/* istanbul ignore next */
if (false) {}

const MenuKeyDownArrowDown =  false ? 0 : 0;
const MenuKeyDownArrowUp =  false ? 0 : 1;
const MenuKeyDownEscape =  false ? 0 : 2;
const MenuKeyDownHome =  false ? 0 : 3;
const MenuKeyDownEnd =  false ? 0 : 4;
const MenuKeyDownEnter =  false ? 0 : 5;
const MenuKeyDownSpaceButton =  false ? 0 : 6;
const MenuKeyDownCharacter =  false ? 0 : 7;
const MenuBlur =  false ? 0 : 8;
const MenuMouseLeave$1 =  false ? 0 : 9;
const ItemMouseMove$1 =  false ? 0 : 10;
const ItemClick$1 =  false ? 0 : 11;
const ToggleButtonClick$1 =  false ? 0 : 12;
const ToggleButtonKeyDownArrowDown =  false ? 0 : 13;
const ToggleButtonKeyDownArrowUp =  false ? 0 : 14;
const ToggleButtonKeyDownCharacter =  false ? 0 : 15;
const FunctionToggleMenu$1 =  false ? 0 : 16;
const FunctionOpenMenu$1 =  false ? 0 : 17;
const FunctionCloseMenu$1 =  false ? 0 : 18;
const FunctionSetHighlightedIndex$1 =  false ? 0 : 19;
const FunctionSelectItem$1 =  false ? 0 : 20;
const FunctionSetInputValue$1 =  false ? 0 : 21;
const FunctionReset$2 =  false ? 0 : 22;

var stateChangeTypes$2 = /*#__PURE__*/Object.freeze({
  __proto__: null,
  MenuKeyDownArrowDown: MenuKeyDownArrowDown,
  MenuKeyDownArrowUp: MenuKeyDownArrowUp,
  MenuKeyDownEscape: MenuKeyDownEscape,
  MenuKeyDownHome: MenuKeyDownHome,
  MenuKeyDownEnd: MenuKeyDownEnd,
  MenuKeyDownEnter: MenuKeyDownEnter,
  MenuKeyDownSpaceButton: MenuKeyDownSpaceButton,
  MenuKeyDownCharacter: MenuKeyDownCharacter,
  MenuBlur: MenuBlur,
  MenuMouseLeave: MenuMouseLeave$1,
  ItemMouseMove: ItemMouseMove$1,
  ItemClick: ItemClick$1,
  ToggleButtonClick: ToggleButtonClick$1,
  ToggleButtonKeyDownArrowDown: ToggleButtonKeyDownArrowDown,
  ToggleButtonKeyDownArrowUp: ToggleButtonKeyDownArrowUp,
  ToggleButtonKeyDownCharacter: ToggleButtonKeyDownCharacter,
  FunctionToggleMenu: FunctionToggleMenu$1,
  FunctionOpenMenu: FunctionOpenMenu$1,
  FunctionCloseMenu: FunctionCloseMenu$1,
  FunctionSetHighlightedIndex: FunctionSetHighlightedIndex$1,
  FunctionSelectItem: FunctionSelectItem$1,
  FunctionSetInputValue: FunctionSetInputValue$1,
  FunctionReset: FunctionReset$2
});

/* eslint-disable complexity */

function downshiftSelectReducer(state, action) {
  const {
    type,
    props,
    shiftKey
  } = action;
  let changes;

  switch (type) {
    case ItemClick$1:
      changes = {
        isOpen: getDefaultValue$1(props, 'isOpen'),
        highlightedIndex: getDefaultValue$1(props, 'highlightedIndex'),
        selectedItem: props.items[action.index]
      };
      break;

    case ToggleButtonKeyDownCharacter:
      {
        const lowercasedKey = action.key;
        const inputValue = `${state.inputValue}${lowercasedKey}`;
        const itemIndex = getItemIndexByCharacterKey({
          keysSoFar: inputValue,
          highlightedIndex: state.selectedItem ? props.items.indexOf(state.selectedItem) : -1,
          items: props.items,
          itemToString: props.itemToString,
          getItemNodeFromIndex: action.getItemNodeFromIndex
        });
        changes = {
          inputValue,
          ...(itemIndex >= 0 && {
            selectedItem: props.items[itemIndex]
          })
        };
      }
      break;

    case ToggleButtonKeyDownArrowDown:
      changes = {
        highlightedIndex: getHighlightedIndexOnOpen(props, state, 1, action.getItemNodeFromIndex),
        isOpen: true
      };
      break;

    case ToggleButtonKeyDownArrowUp:
      changes = {
        highlightedIndex: getHighlightedIndexOnOpen(props, state, -1, action.getItemNodeFromIndex),
        isOpen: true
      };
      break;

    case MenuKeyDownEnter:
    case MenuKeyDownSpaceButton:
      changes = {
        isOpen: getDefaultValue$1(props, 'isOpen'),
        highlightedIndex: getDefaultValue$1(props, 'highlightedIndex'),
        ...(state.highlightedIndex >= 0 && {
          selectedItem: props.items[state.highlightedIndex]
        })
      };
      break;

    case MenuKeyDownHome:
      changes = {
        highlightedIndex: getNextNonDisabledIndex(1, 0, props.items.length, action.getItemNodeFromIndex, false)
      };
      break;

    case MenuKeyDownEnd:
      changes = {
        highlightedIndex: getNextNonDisabledIndex(-1, props.items.length - 1, props.items.length, action.getItemNodeFromIndex, false)
      };
      break;

    case MenuKeyDownEscape:
      changes = {
        isOpen: false,
        highlightedIndex: -1
      };
      break;

    case MenuBlur:
      changes = {
        isOpen: false,
        highlightedIndex: -1
      };
      break;

    case MenuKeyDownCharacter:
      {
        const lowercasedKey = action.key;
        const inputValue = `${state.inputValue}${lowercasedKey}`;
        const highlightedIndex = getItemIndexByCharacterKey({
          keysSoFar: inputValue,
          highlightedIndex: state.highlightedIndex,
          items: props.items,
          itemToString: props.itemToString,
          getItemNodeFromIndex: action.getItemNodeFromIndex
        });
        changes = {
          inputValue,
          ...(highlightedIndex >= 0 && {
            highlightedIndex
          })
        };
      }
      break;

    case MenuKeyDownArrowDown:
      changes = {
        highlightedIndex: getNextWrappingIndex(shiftKey ? 5 : 1, state.highlightedIndex, props.items.length, action.getItemNodeFromIndex, props.circularNavigation)
      };
      break;

    case MenuKeyDownArrowUp:
      changes = {
        highlightedIndex: getNextWrappingIndex(shiftKey ? -5 : -1, state.highlightedIndex, props.items.length, action.getItemNodeFromIndex, props.circularNavigation)
      };
      break;

    case FunctionSelectItem$1:
      changes = {
        selectedItem: action.selectedItem
      };
      break;

    default:
      return downshiftCommonReducer(state, action, stateChangeTypes$2);
  }

  return { ...state,
    ...changes
  };
}
/* eslint-enable complexity */

/* eslint-disable max-statements */
useSelect.stateChangeTypes = stateChangeTypes$2;

function useSelect(userProps) {
  if (userProps === void 0) {
    userProps = {};
  }

  validatePropTypes$2(userProps, useSelect); // Props defaults and destructuring.

  const props = { ...defaultProps$2,
    ...userProps
  };
  const {
    items,
    scrollIntoView,
    environment,
    initialIsOpen,
    defaultIsOpen,
    itemToString,
    getA11ySelectionMessage,
    getA11yStatusMessage
  } = props; // Initial state depending on controlled props.

  const initialState = getInitialState$2(props);
  const [state, dispatch] = useControlledReducer$1(downshiftSelectReducer, initialState, props);
  const {
    isOpen,
    highlightedIndex,
    selectedItem,
    inputValue
  } = state; // Element efs.

  const toggleButtonRef = (0,react.useRef)(null);
  const menuRef = (0,react.useRef)(null);
  const itemRefs = (0,react.useRef)({}); // used not to trigger menu blur action in some scenarios.

  const shouldBlurRef = (0,react.useRef)(true); // used to keep the inputValue clearTimeout object between renders.

  const clearTimeoutRef = (0,react.useRef)(null); // prevent id re-generation between renders.

  const elementIds = useElementIds(props); // used to keep track of how many items we had on previous cycle.

  const previousResultCountRef = (0,react.useRef)();
  const isInitialMountRef = (0,react.useRef)(true); // utility callback to get item element.

  const latest = useLatestRef({
    state,
    props
  }); // Some utils.

  const getItemNodeFromIndex = (0,react.useCallback)(index => itemRefs.current[elementIds.getItemId(index)], [elementIds]); // Effects.
  // Sets a11y status message on changes in state.

  useA11yMessageSetter(getA11yStatusMessage, [isOpen, highlightedIndex, inputValue, items], {
    isInitialMount: isInitialMountRef.current,
    previousResultCount: previousResultCountRef.current,
    items,
    environment,
    itemToString,
    ...state
  }); // Sets a11y status message on changes in selectedItem.

  useA11yMessageSetter(getA11ySelectionMessage, [selectedItem], {
    isInitialMount: isInitialMountRef.current,
    previousResultCount: previousResultCountRef.current,
    items,
    environment,
    itemToString,
    ...state
  }); // Scroll on highlighted item if change comes from keyboard.

  const shouldScrollRef = useScrollIntoView({
    menuElement: menuRef.current,
    highlightedIndex,
    isOpen,
    itemRefs,
    scrollIntoView,
    getItemNodeFromIndex
  }); // Sets cleanup for the keysSoFar callback, debounded after 500ms.

  (0,react.useEffect)(() => {
    // init the clean function here as we need access to dispatch.
    clearTimeoutRef.current = debounce(outerDispatch => {
      outerDispatch({
        type: FunctionSetInputValue$1,
        inputValue: ''
      });
    }, 500); // Cancel any pending debounced calls on mount

    return () => {
      clearTimeoutRef.current.cancel();
    };
  }, []); // Invokes the keysSoFar callback set up above.

  (0,react.useEffect)(() => {
    if (!inputValue) {
      return;
    }

    clearTimeoutRef.current(dispatch);
  }, [dispatch, inputValue]);
  useControlPropsValidator({
    isInitialMount: isInitialMountRef.current,
    props,
    state
  });
  /* Controls the focus on the menu or the toggle button. */

  (0,react.useEffect)(() => {
    // Don't focus menu on first render.
    if (isInitialMountRef.current) {
      // Unless it was initialised as open.
      if ((initialIsOpen || defaultIsOpen || isOpen) && menuRef.current) {
        menuRef.current.focus();
      }

      return;
    } // Focus menu on open.


    if (isOpen) {
      // istanbul ignore else
      if (menuRef.current) {
        menuRef.current.focus();
      }

      return;
    } // Focus toggleButton on close, but not if it was closed with (Shift+)Tab.


    if (environment.document.activeElement === menuRef.current) {
      // istanbul ignore else
      if (toggleButtonRef.current) {
        shouldBlurRef.current = false;
        toggleButtonRef.current.focus();
      }
    } // eslint-disable-next-line react-hooks/exhaustive-deps

  }, [isOpen]);
  (0,react.useEffect)(() => {
    if (isInitialMountRef.current) {
      return;
    }

    previousResultCountRef.current = items.length;
  }); // Add mouse/touch events to document.

  const mouseAndTouchTrackersRef = useMouseAndTouchTracker(isOpen, [menuRef, toggleButtonRef], environment, () => {
    dispatch({
      type: MenuBlur
    });
  });
  const setGetterPropCallInfo = useGetterPropsCalledChecker('getMenuProps', 'getToggleButtonProps'); // Make initial ref false.

  (0,react.useEffect)(() => {
    isInitialMountRef.current = false;
  }, []); // Reset itemRefs on close.

  (0,react.useEffect)(() => {
    if (!isOpen) {
      itemRefs.current = {};
    }
  }, [isOpen]); // Event handler functions.

  const toggleButtonKeyDownHandlers = (0,react.useMemo)(() => ({
    ArrowDown(event) {
      event.preventDefault();
      dispatch({
        type: ToggleButtonKeyDownArrowDown,
        getItemNodeFromIndex,
        shiftKey: event.shiftKey
      });
    },

    ArrowUp(event) {
      event.preventDefault();
      dispatch({
        type: ToggleButtonKeyDownArrowUp,
        getItemNodeFromIndex,
        shiftKey: event.shiftKey
      });
    }

  }), [dispatch, getItemNodeFromIndex]);
  const menuKeyDownHandlers = (0,react.useMemo)(() => ({
    ArrowDown(event) {
      event.preventDefault();
      dispatch({
        type: MenuKeyDownArrowDown,
        getItemNodeFromIndex,
        shiftKey: event.shiftKey
      });
    },

    ArrowUp(event) {
      event.preventDefault();
      dispatch({
        type: MenuKeyDownArrowUp,
        getItemNodeFromIndex,
        shiftKey: event.shiftKey
      });
    },

    Home(event) {
      event.preventDefault();
      dispatch({
        type: MenuKeyDownHome,
        getItemNodeFromIndex
      });
    },

    End(event) {
      event.preventDefault();
      dispatch({
        type: MenuKeyDownEnd,
        getItemNodeFromIndex
      });
    },

    Escape() {
      dispatch({
        type: MenuKeyDownEscape
      });
    },

    Enter(event) {
      event.preventDefault();
      dispatch({
        type: MenuKeyDownEnter
      });
    },

    ' '(event) {
      event.preventDefault();
      dispatch({
        type: MenuKeyDownSpaceButton
      });
    }

  }), [dispatch, getItemNodeFromIndex]); // Action functions.

  const toggleMenu = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionToggleMenu$1
    });
  }, [dispatch]);
  const closeMenu = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionCloseMenu$1
    });
  }, [dispatch]);
  const openMenu = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionOpenMenu$1
    });
  }, [dispatch]);
  const setHighlightedIndex = (0,react.useCallback)(newHighlightedIndex => {
    dispatch({
      type: FunctionSetHighlightedIndex$1,
      highlightedIndex: newHighlightedIndex
    });
  }, [dispatch]);
  const selectItem = (0,react.useCallback)(newSelectedItem => {
    dispatch({
      type: FunctionSelectItem$1,
      selectedItem: newSelectedItem
    });
  }, [dispatch]);
  const reset = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionReset$2
    });
  }, [dispatch]);
  const setInputValue = (0,react.useCallback)(newInputValue => {
    dispatch({
      type: FunctionSetInputValue$1,
      inputValue: newInputValue
    });
  }, [dispatch]); // Getter functions.

  const getLabelProps = (0,react.useCallback)(labelProps => ({
    id: elementIds.labelId,
    htmlFor: elementIds.toggleButtonId,
    ...labelProps
  }), [elementIds]);
  const getMenuProps = (0,react.useCallback)(function (_temp, _temp2) {
    let {
      onMouseLeave,
      refKey = 'ref',
      onKeyDown,
      onBlur,
      ref,
      ...rest
    } = _temp === void 0 ? {} : _temp;
    let {
      suppressRefError = false
    } = _temp2 === void 0 ? {} : _temp2;
    const latestState = latest.current.state;

    const menuHandleKeyDown = event => {
      const key = normalizeArrowKey(event);

      if (key && menuKeyDownHandlers[key]) {
        menuKeyDownHandlers[key](event);
      } else if (isAcceptedCharacterKey(key)) {
        dispatch({
          type: MenuKeyDownCharacter,
          key,
          getItemNodeFromIndex
        });
      }
    };

    const menuHandleBlur = () => {
      // if the blur was a result of selection, we don't trigger this action.
      if (shouldBlurRef.current === false) {
        shouldBlurRef.current = true;
        return;
      }

      const shouldBlur = !mouseAndTouchTrackersRef.current.isMouseDown;
      /* istanbul ignore else */

      if (shouldBlur) {
        dispatch({
          type: MenuBlur
        });
      }
    };

    const menuHandleMouseLeave = () => {
      dispatch({
        type: MenuMouseLeave$1
      });
    };

    setGetterPropCallInfo('getMenuProps', suppressRefError, refKey, menuRef);
    return {
      [refKey]: handleRefs(ref, menuNode => {
        menuRef.current = menuNode;
      }),
      id: elementIds.menuId,
      role: 'listbox',
      'aria-labelledby': elementIds.labelId,
      tabIndex: -1,
      ...(latestState.isOpen && latestState.highlightedIndex > -1 && {
        'aria-activedescendant': elementIds.getItemId(latestState.highlightedIndex)
      }),
      onMouseLeave: callAllEventHandlers(onMouseLeave, menuHandleMouseLeave),
      onKeyDown: callAllEventHandlers(onKeyDown, menuHandleKeyDown),
      onBlur: callAllEventHandlers(onBlur, menuHandleBlur),
      ...rest
    };
  }, [dispatch, latest, menuKeyDownHandlers, mouseAndTouchTrackersRef, setGetterPropCallInfo, elementIds, getItemNodeFromIndex]);
  const getToggleButtonProps = (0,react.useCallback)(function (_temp3, _temp4) {
    let {
      onClick,
      onKeyDown,
      refKey = 'ref',
      ref,
      ...rest
    } = _temp3 === void 0 ? {} : _temp3;
    let {
      suppressRefError = false
    } = _temp4 === void 0 ? {} : _temp4;

    const toggleButtonHandleClick = () => {
      dispatch({
        type: ToggleButtonClick$1
      });
    };

    const toggleButtonHandleKeyDown = event => {
      const key = normalizeArrowKey(event);

      if (key && toggleButtonKeyDownHandlers[key]) {
        toggleButtonKeyDownHandlers[key](event);
      } else if (isAcceptedCharacterKey(key)) {
        dispatch({
          type: ToggleButtonKeyDownCharacter,
          key,
          getItemNodeFromIndex
        });
      }
    };

    const toggleProps = {
      [refKey]: handleRefs(ref, toggleButtonNode => {
        toggleButtonRef.current = toggleButtonNode;
      }),
      id: elementIds.toggleButtonId,
      'aria-haspopup': 'listbox',
      'aria-expanded': latest.current.state.isOpen,
      'aria-labelledby': `${elementIds.labelId} ${elementIds.toggleButtonId}`,
      ...rest
    };

    if (!rest.disabled) {
      toggleProps.onClick = callAllEventHandlers(onClick, toggleButtonHandleClick);
      toggleProps.onKeyDown = callAllEventHandlers(onKeyDown, toggleButtonHandleKeyDown);
    }

    setGetterPropCallInfo('getToggleButtonProps', suppressRefError, refKey, toggleButtonRef);
    return toggleProps;
  }, [dispatch, latest, toggleButtonKeyDownHandlers, setGetterPropCallInfo, elementIds, getItemNodeFromIndex]);
  const getItemProps = (0,react.useCallback)(function (_temp5) {
    let {
      item,
      index,
      onMouseMove,
      onClick,
      refKey = 'ref',
      ref,
      disabled,
      ...rest
    } = _temp5 === void 0 ? {} : _temp5;
    const {
      state: latestState,
      props: latestProps
    } = latest.current;

    const itemHandleMouseMove = () => {
      if (index === latestState.highlightedIndex) {
        return;
      }

      shouldScrollRef.current = false;
      dispatch({
        type: ItemMouseMove$1,
        index,
        disabled
      });
    };

    const itemHandleClick = () => {
      dispatch({
        type: ItemClick$1,
        index
      });
    };

    const itemIndex = getItemIndex(index, item, latestProps.items);

    if (itemIndex < 0) {
      throw new Error('Pass either item or item index in getItemProps!');
    }

    const itemProps = {
      disabled,
      role: 'option',
      'aria-selected': `${itemIndex === latestState.highlightedIndex}`,
      id: elementIds.getItemId(itemIndex),
      [refKey]: handleRefs(ref, itemNode => {
        if (itemNode) {
          itemRefs.current[elementIds.getItemId(itemIndex)] = itemNode;
        }
      }),
      ...rest
    };

    if (!disabled) {
      itemProps.onClick = callAllEventHandlers(onClick, itemHandleClick);
    }

    itemProps.onMouseMove = callAllEventHandlers(onMouseMove, itemHandleMouseMove);
    return itemProps;
  }, [dispatch, latest, shouldScrollRef, elementIds]);
  return {
    // prop getters.
    getToggleButtonProps,
    getLabelProps,
    getMenuProps,
    getItemProps,
    // actions.
    toggleMenu,
    openMenu,
    closeMenu,
    setHighlightedIndex,
    selectItem,
    reset,
    setInputValue,
    // state.
    highlightedIndex,
    isOpen,
    selectedItem,
    inputValue
  };
}

const InputKeyDownArrowDown =  false ? 0 : 0;
const InputKeyDownArrowUp =  false ? 0 : 1;
const InputKeyDownEscape =  false ? 0 : 2;
const InputKeyDownHome =  false ? 0 : 3;
const InputKeyDownEnd =  false ? 0 : 4;
const InputKeyDownEnter =  false ? 0 : 5;
const InputChange =  false ? 0 : 6;
const InputBlur =  false ? 0 : 7;
const MenuMouseLeave =  false ? 0 : 8;
const ItemMouseMove =  false ? 0 : 9;
const ItemClick =  false ? 0 : 10;
const ToggleButtonClick =  false ? 0 : 11;
const FunctionToggleMenu =  false ? 0 : 12;
const FunctionOpenMenu =  false ? 0 : 13;
const FunctionCloseMenu =  false ? 0 : 14;
const FunctionSetHighlightedIndex =  false ? 0 : 15;
const FunctionSelectItem =  false ? 0 : 16;
const FunctionSetInputValue =  false ? 0 : 17;
const FunctionReset$1 =  false ? 0 : 18;
const ControlledPropUpdatedSelectedItem =  false ? 0 : 19;

var stateChangeTypes$1 = /*#__PURE__*/Object.freeze({
  __proto__: null,
  InputKeyDownArrowDown: InputKeyDownArrowDown,
  InputKeyDownArrowUp: InputKeyDownArrowUp,
  InputKeyDownEscape: InputKeyDownEscape,
  InputKeyDownHome: InputKeyDownHome,
  InputKeyDownEnd: InputKeyDownEnd,
  InputKeyDownEnter: InputKeyDownEnter,
  InputChange: InputChange,
  InputBlur: InputBlur,
  MenuMouseLeave: MenuMouseLeave,
  ItemMouseMove: ItemMouseMove,
  ItemClick: ItemClick,
  ToggleButtonClick: ToggleButtonClick,
  FunctionToggleMenu: FunctionToggleMenu,
  FunctionOpenMenu: FunctionOpenMenu,
  FunctionCloseMenu: FunctionCloseMenu,
  FunctionSetHighlightedIndex: FunctionSetHighlightedIndex,
  FunctionSelectItem: FunctionSelectItem,
  FunctionSetInputValue: FunctionSetInputValue,
  FunctionReset: FunctionReset$1,
  ControlledPropUpdatedSelectedItem: ControlledPropUpdatedSelectedItem
});

function getInitialState$1(props) {
  const initialState = getInitialState$2(props);
  const {
    selectedItem
  } = initialState;
  let {
    inputValue
  } = initialState;

  if (inputValue === '' && selectedItem && props.defaultInputValue === undefined && props.initialInputValue === undefined && props.inputValue === undefined) {
    inputValue = props.itemToString(selectedItem);
  }

  return { ...initialState,
    inputValue
  };
}

const propTypes$1 = {
  items: (prop_types_default()).array.isRequired,
  itemToString: (prop_types_default()).func,
  getA11yStatusMessage: (prop_types_default()).func,
  getA11ySelectionMessage: (prop_types_default()).func,
  circularNavigation: (prop_types_default()).bool,
  highlightedIndex: (prop_types_default()).number,
  defaultHighlightedIndex: (prop_types_default()).number,
  initialHighlightedIndex: (prop_types_default()).number,
  isOpen: (prop_types_default()).bool,
  defaultIsOpen: (prop_types_default()).bool,
  initialIsOpen: (prop_types_default()).bool,
  selectedItem: (prop_types_default()).any,
  initialSelectedItem: (prop_types_default()).any,
  defaultSelectedItem: (prop_types_default()).any,
  inputValue: (prop_types_default()).string,
  defaultInputValue: (prop_types_default()).string,
  initialInputValue: (prop_types_default()).string,
  id: (prop_types_default()).string,
  labelId: (prop_types_default()).string,
  menuId: (prop_types_default()).string,
  getItemId: (prop_types_default()).func,
  inputId: (prop_types_default()).string,
  toggleButtonId: (prop_types_default()).string,
  stateReducer: (prop_types_default()).func,
  onSelectedItemChange: (prop_types_default()).func,
  onHighlightedIndexChange: (prop_types_default()).func,
  onStateChange: (prop_types_default()).func,
  onIsOpenChange: (prop_types_default()).func,
  onInputValueChange: (prop_types_default()).func,
  environment: prop_types_default().shape({
    addEventListener: (prop_types_default()).func,
    removeEventListener: (prop_types_default()).func,
    document: prop_types_default().shape({
      getElementById: (prop_types_default()).func,
      activeElement: (prop_types_default()).any,
      body: (prop_types_default()).any
    })
  })
};
/**
 * The useCombobox version of useControlledReducer, which also
 * checks if the controlled prop selectedItem changed between
 * renders. If so, it will also update inputValue with its
 * string equivalent. It uses the common useEnhancedReducer to
 * compute the rest of the state.
 *
 * @param {Function} reducer Reducer function from downshift.
 * @param {Object} initialState Initial state of the hook.
 * @param {Object} props The hook props.
 * @returns {Array} An array with the state and an action dispatcher.
 */

function useControlledReducer(reducer, initialState, props) {
  const previousSelectedItemRef = (0,react.useRef)();
  const [state, dispatch] = useEnhancedReducer(reducer, initialState, props); // ToDo: if needed, make same approach as selectedItemChanged from Downshift.

  (0,react.useEffect)(() => {
    if (isControlledProp(props, 'selectedItem')) {
      if (previousSelectedItemRef.current !== props.selectedItem) {
        dispatch({
          type: ControlledPropUpdatedSelectedItem,
          inputValue: props.itemToString(props.selectedItem)
        });
      }

      previousSelectedItemRef.current = state.selectedItem === previousSelectedItemRef.current ? props.selectedItem : state.selectedItem;
    }
  });
  return [getState(state, props), dispatch];
} // eslint-disable-next-line import/no-mutable-exports


let validatePropTypes$1 = noop;
/* istanbul ignore next */

if (false) {}

const defaultProps$1 = { ...defaultProps$3,
  getA11yStatusMessage: getA11yStatusMessage$1,
  circularNavigation: true
};

/* eslint-disable complexity */

function downshiftUseComboboxReducer(state, action) {
  const {
    type,
    props,
    shiftKey
  } = action;
  let changes;

  switch (type) {
    case ItemClick:
      changes = {
        isOpen: getDefaultValue$1(props, 'isOpen'),
        highlightedIndex: getDefaultValue$1(props, 'highlightedIndex'),
        selectedItem: props.items[action.index],
        inputValue: props.itemToString(props.items[action.index])
      };
      break;

    case InputKeyDownArrowDown:
      if (state.isOpen) {
        changes = {
          highlightedIndex: getNextWrappingIndex(shiftKey ? 5 : 1, state.highlightedIndex, props.items.length, action.getItemNodeFromIndex, props.circularNavigation)
        };
      } else {
        changes = {
          highlightedIndex: getHighlightedIndexOnOpen(props, state, 1, action.getItemNodeFromIndex),
          isOpen: props.items.length >= 0
        };
      }

      break;

    case InputKeyDownArrowUp:
      if (state.isOpen) {
        changes = {
          highlightedIndex: getNextWrappingIndex(shiftKey ? -5 : -1, state.highlightedIndex, props.items.length, action.getItemNodeFromIndex, props.circularNavigation)
        };
      } else {
        changes = {
          highlightedIndex: getHighlightedIndexOnOpen(props, state, -1, action.getItemNodeFromIndex),
          isOpen: props.items.length >= 0
        };
      }

      break;

    case InputKeyDownEnter:
      changes = { ...(state.isOpen && state.highlightedIndex >= 0 && {
          selectedItem: props.items[state.highlightedIndex],
          isOpen: getDefaultValue$1(props, 'isOpen'),
          highlightedIndex: getDefaultValue$1(props, 'highlightedIndex'),
          inputValue: props.itemToString(props.items[state.highlightedIndex])
        })
      };
      break;

    case InputKeyDownEscape:
      changes = {
        isOpen: false,
        highlightedIndex: -1,
        ...(!state.isOpen && {
          selectedItem: null,
          inputValue: ''
        })
      };
      break;

    case InputKeyDownHome:
      changes = {
        highlightedIndex: getNextNonDisabledIndex(1, 0, props.items.length, action.getItemNodeFromIndex, false)
      };
      break;

    case InputKeyDownEnd:
      changes = {
        highlightedIndex: getNextNonDisabledIndex(-1, props.items.length - 1, props.items.length, action.getItemNodeFromIndex, false)
      };
      break;

    case InputBlur:
      changes = {
        isOpen: false,
        highlightedIndex: -1,
        ...(state.highlightedIndex >= 0 && action.selectItem && {
          selectedItem: props.items[state.highlightedIndex],
          inputValue: props.itemToString(props.items[state.highlightedIndex])
        })
      };
      break;

    case InputChange:
      changes = {
        isOpen: true,
        highlightedIndex: getDefaultValue$1(props, 'highlightedIndex'),
        inputValue: action.inputValue
      };
      break;

    case FunctionSelectItem:
      changes = {
        selectedItem: action.selectedItem,
        inputValue: props.itemToString(action.selectedItem)
      };
      break;

    case ControlledPropUpdatedSelectedItem:
      changes = {
        inputValue: action.inputValue
      };
      break;

    default:
      return downshiftCommonReducer(state, action, stateChangeTypes$1);
  }

  return { ...state,
    ...changes
  };
}
/* eslint-enable complexity */

/* eslint-disable max-statements */
useCombobox.stateChangeTypes = stateChangeTypes$1;

function useCombobox(userProps) {
  if (userProps === void 0) {
    userProps = {};
  }

  validatePropTypes$1(userProps, useCombobox); // Props defaults and destructuring.

  const props = { ...defaultProps$1,
    ...userProps
  };
  const {
    initialIsOpen,
    defaultIsOpen,
    items,
    scrollIntoView,
    environment,
    getA11yStatusMessage,
    getA11ySelectionMessage,
    itemToString
  } = props; // Initial state depending on controlled props.

  const initialState = getInitialState$1(props);
  const [state, dispatch] = useControlledReducer(downshiftUseComboboxReducer, initialState, props);
  const {
    isOpen,
    highlightedIndex,
    selectedItem,
    inputValue
  } = state; // Element refs.

  const menuRef = (0,react.useRef)(null);
  const itemRefs = (0,react.useRef)({});
  const inputRef = (0,react.useRef)(null);
  const toggleButtonRef = (0,react.useRef)(null);
  const comboboxRef = (0,react.useRef)(null);
  const isInitialMountRef = (0,react.useRef)(true); // prevent id re-generation between renders.

  const elementIds = useElementIds(props); // used to keep track of how many items we had on previous cycle.

  const previousResultCountRef = (0,react.useRef)(); // utility callback to get item element.

  const latest = useLatestRef({
    state,
    props
  });
  const getItemNodeFromIndex = (0,react.useCallback)(index => itemRefs.current[elementIds.getItemId(index)], [elementIds]); // Effects.
  // Sets a11y status message on changes in state.

  useA11yMessageSetter(getA11yStatusMessage, [isOpen, highlightedIndex, inputValue, items], {
    isInitialMount: isInitialMountRef.current,
    previousResultCount: previousResultCountRef.current,
    items,
    environment,
    itemToString,
    ...state
  }); // Sets a11y status message on changes in selectedItem.

  useA11yMessageSetter(getA11ySelectionMessage, [selectedItem], {
    isInitialMount: isInitialMountRef.current,
    previousResultCount: previousResultCountRef.current,
    items,
    environment,
    itemToString,
    ...state
  }); // Scroll on highlighted item if change comes from keyboard.

  const shouldScrollRef = useScrollIntoView({
    menuElement: menuRef.current,
    highlightedIndex,
    isOpen,
    itemRefs,
    scrollIntoView,
    getItemNodeFromIndex
  });
  useControlPropsValidator({
    isInitialMount: isInitialMountRef.current,
    props,
    state
  }); // Focus the input on first render if required.

  (0,react.useEffect)(() => {
    const focusOnOpen = initialIsOpen || defaultIsOpen || isOpen;

    if (focusOnOpen && inputRef.current) {
      inputRef.current.focus();
    } // eslint-disable-next-line react-hooks/exhaustive-deps

  }, []);
  (0,react.useEffect)(() => {
    if (isInitialMountRef.current) {
      return;
    }

    previousResultCountRef.current = items.length;
  }); // Add mouse/touch events to document.

  const mouseAndTouchTrackersRef = useMouseAndTouchTracker(isOpen, [comboboxRef, menuRef, toggleButtonRef], environment, () => {
    dispatch({
      type: InputBlur,
      selectItem: false
    });
  });
  const setGetterPropCallInfo = useGetterPropsCalledChecker('getInputProps', 'getComboboxProps', 'getMenuProps'); // Make initial ref false.

  (0,react.useEffect)(() => {
    isInitialMountRef.current = false;
  }, []); // Reset itemRefs on close.

  (0,react.useEffect)(() => {
    if (!isOpen) {
      itemRefs.current = {};
    }
  }, [isOpen]);
  /* Event handler functions */

  const inputKeyDownHandlers = (0,react.useMemo)(() => ({
    ArrowDown(event) {
      event.preventDefault();
      dispatch({
        type: InputKeyDownArrowDown,
        shiftKey: event.shiftKey,
        getItemNodeFromIndex
      });
    },

    ArrowUp(event) {
      event.preventDefault();
      dispatch({
        type: InputKeyDownArrowUp,
        shiftKey: event.shiftKey,
        getItemNodeFromIndex
      });
    },

    Home(event) {
      if (!latest.current.state.isOpen) {
        return;
      }

      event.preventDefault();
      dispatch({
        type: InputKeyDownHome,
        getItemNodeFromIndex
      });
    },

    End(event) {
      if (!latest.current.state.isOpen) {
        return;
      }

      event.preventDefault();
      dispatch({
        type: InputKeyDownEnd,
        getItemNodeFromIndex
      });
    },

    Escape(event) {
      const latestState = latest.current.state;

      if (latestState.isOpen || latestState.inputValue || latestState.selectedItem || latestState.highlightedIndex > -1) {
        event.preventDefault();
        dispatch({
          type: InputKeyDownEscape
        });
      }
    },

    Enter(event) {
      const latestState = latest.current.state; // if closed or no highlighted index, do nothing.

      if (!latestState.isOpen || latestState.highlightedIndex < 0 || event.which === 229 // if IME composing, wait for next Enter keydown event.
      ) {
        return;
      }

      event.preventDefault();
      dispatch({
        type: InputKeyDownEnter,
        getItemNodeFromIndex
      });
    }

  }), [dispatch, latest, getItemNodeFromIndex]); // Getter props.

  const getLabelProps = (0,react.useCallback)(labelProps => ({
    id: elementIds.labelId,
    htmlFor: elementIds.inputId,
    ...labelProps
  }), [elementIds]);
  const getMenuProps = (0,react.useCallback)(function (_temp, _temp2) {
    let {
      onMouseLeave,
      refKey = 'ref',
      ref,
      ...rest
    } = _temp === void 0 ? {} : _temp;
    let {
      suppressRefError = false
    } = _temp2 === void 0 ? {} : _temp2;
    setGetterPropCallInfo('getMenuProps', suppressRefError, refKey, menuRef);
    return {
      [refKey]: handleRefs(ref, menuNode => {
        menuRef.current = menuNode;
      }),
      id: elementIds.menuId,
      role: 'listbox',
      'aria-labelledby': elementIds.labelId,
      onMouseLeave: callAllEventHandlers(onMouseLeave, () => {
        dispatch({
          type: MenuMouseLeave
        });
      }),
      ...rest
    };
  }, [dispatch, setGetterPropCallInfo, elementIds]);
  const getItemProps = (0,react.useCallback)(function (_temp3) {
    let {
      item,
      index,
      refKey = 'ref',
      ref,
      onMouseMove,
      onMouseDown,
      onClick,
      onPress,
      disabled,
      ...rest
    } = _temp3 === void 0 ? {} : _temp3;
    const {
      props: latestProps,
      state: latestState
    } = latest.current;
    const itemIndex = getItemIndex(index, item, latestProps.items);

    if (itemIndex < 0) {
      throw new Error('Pass either item or item index in getItemProps!');
    }

    const onSelectKey = 'onClick';
    const customClickHandler = onClick;

    const itemHandleMouseMove = () => {
      if (index === latestState.highlightedIndex) {
        return;
      }

      shouldScrollRef.current = false;
      dispatch({
        type: ItemMouseMove,
        index,
        disabled
      });
    };

    const itemHandleClick = () => {
      dispatch({
        type: ItemClick,
        index
      });
    };

    const itemHandleMouseDown = e => e.preventDefault();

    return {
      [refKey]: handleRefs(ref, itemNode => {
        if (itemNode) {
          itemRefs.current[elementIds.getItemId(itemIndex)] = itemNode;
        }
      }),
      disabled,
      role: 'option',
      'aria-selected': `${itemIndex === latestState.highlightedIndex}`,
      id: elementIds.getItemId(itemIndex),
      ...(!disabled && {
        [onSelectKey]: callAllEventHandlers(customClickHandler, itemHandleClick)
      }),
      onMouseMove: callAllEventHandlers(onMouseMove, itemHandleMouseMove),
      onMouseDown: callAllEventHandlers(onMouseDown, itemHandleMouseDown),
      ...rest
    };
  }, [dispatch, latest, shouldScrollRef, elementIds]);
  const getToggleButtonProps = (0,react.useCallback)(function (_temp4) {
    let {
      onClick,
      onPress,
      refKey = 'ref',
      ref,
      ...rest
    } = _temp4 === void 0 ? {} : _temp4;

    const toggleButtonHandleClick = () => {
      dispatch({
        type: ToggleButtonClick
      });

      if (!latest.current.state.isOpen && inputRef.current) {
        inputRef.current.focus();
      }
    };

    return {
      [refKey]: handleRefs(ref, toggleButtonNode => {
        toggleButtonRef.current = toggleButtonNode;
      }),
      id: elementIds.toggleButtonId,
      tabIndex: -1,
      ...(!rest.disabled && { ...({
          onClick: callAllEventHandlers(onClick, toggleButtonHandleClick)
        })
      }),
      ...rest
    };
  }, [dispatch, latest, elementIds]);
  const getInputProps = (0,react.useCallback)(function (_temp5, _temp6) {
    let {
      onKeyDown,
      onChange,
      onInput,
      onBlur,
      onChangeText,
      refKey = 'ref',
      ref,
      ...rest
    } = _temp5 === void 0 ? {} : _temp5;
    let {
      suppressRefError = false
    } = _temp6 === void 0 ? {} : _temp6;
    setGetterPropCallInfo('getInputProps', suppressRefError, refKey, inputRef);
    const latestState = latest.current.state;

    const inputHandleKeyDown = event => {
      const key = normalizeArrowKey(event);

      if (key && inputKeyDownHandlers[key]) {
        inputKeyDownHandlers[key](event);
      }
    };

    const inputHandleChange = event => {
      dispatch({
        type: InputChange,
        inputValue: event.target.value
      });
    };

    const inputHandleBlur = () => {
      /* istanbul ignore else */
      if (latestState.isOpen && !mouseAndTouchTrackersRef.current.isMouseDown) {
        dispatch({
          type: InputBlur,
          selectItem: true
        });
      }
    };
    /* istanbul ignore next (preact) */


    const onChangeKey = 'onChange';
    let eventHandlers = {};

    if (!rest.disabled) {
      eventHandlers = {
        [onChangeKey]: callAllEventHandlers(onChange, onInput, inputHandleChange),
        onKeyDown: callAllEventHandlers(onKeyDown, inputHandleKeyDown),
        onBlur: callAllEventHandlers(onBlur, inputHandleBlur)
      };
    }

    return {
      [refKey]: handleRefs(ref, inputNode => {
        inputRef.current = inputNode;
      }),
      id: elementIds.inputId,
      'aria-autocomplete': 'list',
      'aria-controls': elementIds.menuId,
      ...(latestState.isOpen && latestState.highlightedIndex > -1 && {
        'aria-activedescendant': elementIds.getItemId(latestState.highlightedIndex)
      }),
      'aria-labelledby': elementIds.labelId,
      // https://developer.mozilla.org/en-US/docs/Web/Security/Securing_your_site/Turning_off_form_autocompletion
      // revert back since autocomplete="nope" is ignored on latest Chrome and Opera
      autoComplete: 'off',
      value: latestState.inputValue,
      ...eventHandlers,
      ...rest
    };
  }, [dispatch, inputKeyDownHandlers, latest, mouseAndTouchTrackersRef, setGetterPropCallInfo, elementIds]);
  const getComboboxProps = (0,react.useCallback)(function (_temp7, _temp8) {
    let {
      refKey = 'ref',
      ref,
      ...rest
    } = _temp7 === void 0 ? {} : _temp7;
    let {
      suppressRefError = false
    } = _temp8 === void 0 ? {} : _temp8;
    setGetterPropCallInfo('getComboboxProps', suppressRefError, refKey, comboboxRef);
    return {
      [refKey]: handleRefs(ref, comboboxNode => {
        comboboxRef.current = comboboxNode;
      }),
      role: 'combobox',
      'aria-haspopup': 'listbox',
      'aria-owns': elementIds.menuId,
      'aria-expanded': latest.current.state.isOpen,
      ...rest
    };
  }, [latest, setGetterPropCallInfo, elementIds]); // returns

  const toggleMenu = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionToggleMenu
    });
  }, [dispatch]);
  const closeMenu = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionCloseMenu
    });
  }, [dispatch]);
  const openMenu = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionOpenMenu
    });
  }, [dispatch]);
  const setHighlightedIndex = (0,react.useCallback)(newHighlightedIndex => {
    dispatch({
      type: FunctionSetHighlightedIndex,
      highlightedIndex: newHighlightedIndex
    });
  }, [dispatch]);
  const selectItem = (0,react.useCallback)(newSelectedItem => {
    dispatch({
      type: FunctionSelectItem,
      selectedItem: newSelectedItem
    });
  }, [dispatch]);
  const setInputValue = (0,react.useCallback)(newInputValue => {
    dispatch({
      type: FunctionSetInputValue,
      inputValue: newInputValue
    });
  }, [dispatch]);
  const reset = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionReset$1
    });
  }, [dispatch]);
  return {
    // prop getters.
    getItemProps,
    getLabelProps,
    getMenuProps,
    getInputProps,
    getComboboxProps,
    getToggleButtonProps,
    // actions.
    toggleMenu,
    openMenu,
    closeMenu,
    setHighlightedIndex,
    setInputValue,
    selectItem,
    reset,
    // state.
    highlightedIndex,
    isOpen,
    selectedItem,
    inputValue
  };
}

const defaultStateValues = {
  activeIndex: -1,
  selectedItems: []
};
/**
 * Returns the initial value for a state key in the following order:
 * 1. controlled prop, 2. initial prop, 3. default prop, 4. default
 * value from Downshift.
 *
 * @param {Object} props Props passed to the hook.
 * @param {string} propKey Props key to generate the value for.
 * @returns {any} The initial value for that prop.
 */

function getInitialValue(props, propKey) {
  return getInitialValue$1(props, propKey, defaultStateValues);
}
/**
 * Returns the default value for a state key in the following order:
 * 1. controlled prop, 2. default prop, 3. default value from Downshift.
 *
 * @param {Object} props Props passed to the hook.
 * @param {string} propKey Props key to generate the value for.
 * @returns {any} The initial value for that prop.
 */


function getDefaultValue(props, propKey) {
  return getDefaultValue$1(props, propKey, defaultStateValues);
}
/**
 * Gets the initial state based on the provided props. It uses initial, default
 * and controlled props related to state in order to compute the initial value.
 *
 * @param {Object} props Props passed to the hook.
 * @returns {Object} The initial state.
 */


function getInitialState(props) {
  const activeIndex = getInitialValue(props, 'activeIndex');
  const selectedItems = getInitialValue(props, 'selectedItems');
  return {
    activeIndex,
    selectedItems
  };
}
/**
 * Returns true if dropdown keydown operation is permitted. Should not be
 * allowed on keydown with modifier keys (ctrl, alt, shift, meta), on
 * input element with text content that is either highlighted or selection
 * cursor is not at the starting position.
 *
 * @param {KeyboardEvent} event The event from keydown.
 * @returns {boolean} Whether the operation is allowed.
 */


function isKeyDownOperationPermitted(event) {
  if (event.shiftKey || event.metaKey || event.ctrlKey || event.altKey) {
    return false;
  }

  const element = event.target;

  if (element instanceof HTMLInputElement && // if element is a text input
  element.value !== '' && ( // and we have text in it
  // and cursor is either not at the start or is currently highlighting text.
  element.selectionStart !== 0 || element.selectionEnd !== 0)) {
    return false;
  }

  return true;
}
/**
 * Returns a message to be added to aria-live region when item is removed.
 *
 * @param {Object} selectionParameters Parameters required to build the message.
 * @returns {string} The a11y message.
 */


function getA11yRemovalMessage(selectionParameters) {
  const {
    removedSelectedItem,
    itemToString: itemToStringLocal
  } = selectionParameters;
  return `${itemToStringLocal(removedSelectedItem)} has been removed.`;
}

const propTypes = {
  selectedItems: (prop_types_default()).array,
  initialSelectedItems: (prop_types_default()).array,
  defaultSelectedItems: (prop_types_default()).array,
  itemToString: (prop_types_default()).func,
  getA11yRemovalMessage: (prop_types_default()).func,
  stateReducer: (prop_types_default()).func,
  activeIndex: (prop_types_default()).number,
  initialActiveIndex: (prop_types_default()).number,
  defaultActiveIndex: (prop_types_default()).number,
  onActiveIndexChange: (prop_types_default()).func,
  onSelectedItemsChange: (prop_types_default()).func,
  keyNavigationNext: (prop_types_default()).string,
  keyNavigationPrevious: (prop_types_default()).string,
  environment: prop_types_default().shape({
    addEventListener: (prop_types_default()).func,
    removeEventListener: (prop_types_default()).func,
    document: prop_types_default().shape({
      getElementById: (prop_types_default()).func,
      activeElement: (prop_types_default()).any,
      body: (prop_types_default()).any
    })
  })
};
const defaultProps = {
  itemToString: defaultProps$3.itemToString,
  stateReducer: defaultProps$3.stateReducer,
  environment: defaultProps$3.environment,
  getA11yRemovalMessage,
  keyNavigationNext: 'ArrowRight',
  keyNavigationPrevious: 'ArrowLeft'
}; // eslint-disable-next-line import/no-mutable-exports

let validatePropTypes = noop;
/* istanbul ignore next */

if (false) {}

const SelectedItemClick =  false ? 0 : 0;
const SelectedItemKeyDownDelete =  false ? 0 : 1;
const SelectedItemKeyDownBackspace =  false ? 0 : 2;
const SelectedItemKeyDownNavigationNext =  false ? 0 : 3;
const SelectedItemKeyDownNavigationPrevious =  false ? 0 : 4;
const DropdownKeyDownNavigationPrevious =  false ? 0 : 5;
const DropdownKeyDownBackspace =  false ? 0 : 6;
const DropdownClick =  false ? 0 : 7;
const FunctionAddSelectedItem =  false ? 0 : 8;
const FunctionRemoveSelectedItem =  false ? 0 : 9;
const FunctionSetSelectedItems =  false ? 0 : 10;
const FunctionSetActiveIndex =  false ? 0 : 11;
const FunctionReset =  false ? 0 : 12;

var stateChangeTypes = /*#__PURE__*/Object.freeze({
  __proto__: null,
  SelectedItemClick: SelectedItemClick,
  SelectedItemKeyDownDelete: SelectedItemKeyDownDelete,
  SelectedItemKeyDownBackspace: SelectedItemKeyDownBackspace,
  SelectedItemKeyDownNavigationNext: SelectedItemKeyDownNavigationNext,
  SelectedItemKeyDownNavigationPrevious: SelectedItemKeyDownNavigationPrevious,
  DropdownKeyDownNavigationPrevious: DropdownKeyDownNavigationPrevious,
  DropdownKeyDownBackspace: DropdownKeyDownBackspace,
  DropdownClick: DropdownClick,
  FunctionAddSelectedItem: FunctionAddSelectedItem,
  FunctionRemoveSelectedItem: FunctionRemoveSelectedItem,
  FunctionSetSelectedItems: FunctionSetSelectedItems,
  FunctionSetActiveIndex: FunctionSetActiveIndex,
  FunctionReset: FunctionReset
});

/* eslint-disable complexity */

function downshiftMultipleSelectionReducer(state, action) {
  const {
    type,
    index,
    props,
    selectedItem
  } = action;
  const {
    activeIndex,
    selectedItems
  } = state;
  let changes;

  switch (type) {
    case SelectedItemClick:
      changes = {
        activeIndex: index
      };
      break;

    case SelectedItemKeyDownNavigationPrevious:
      changes = {
        activeIndex: activeIndex - 1 < 0 ? 0 : activeIndex - 1
      };
      break;

    case SelectedItemKeyDownNavigationNext:
      changes = {
        activeIndex: activeIndex + 1 >= selectedItems.length ? -1 : activeIndex + 1
      };
      break;

    case SelectedItemKeyDownBackspace:
    case SelectedItemKeyDownDelete:
      {
        let newActiveIndex = activeIndex;

        if (selectedItems.length === 1) {
          newActiveIndex = -1;
        } else if (activeIndex === selectedItems.length - 1) {
          newActiveIndex = selectedItems.length - 2;
        }

        changes = {
          selectedItems: [...selectedItems.slice(0, activeIndex), ...selectedItems.slice(activeIndex + 1)],
          ...{
            activeIndex: newActiveIndex
          }
        };
        break;
      }

    case DropdownKeyDownNavigationPrevious:
      changes = {
        activeIndex: selectedItems.length - 1
      };
      break;

    case DropdownKeyDownBackspace:
      changes = {
        selectedItems: selectedItems.slice(0, selectedItems.length - 1)
      };
      break;

    case FunctionAddSelectedItem:
      changes = {
        selectedItems: [...selectedItems, selectedItem]
      };
      break;

    case DropdownClick:
      changes = {
        activeIndex: -1
      };
      break;

    case FunctionRemoveSelectedItem:
      {
        let newActiveIndex = activeIndex;
        const selectedItemIndex = selectedItems.indexOf(selectedItem);

        if (selectedItemIndex >= 0) {
          if (selectedItems.length === 1) {
            newActiveIndex = -1;
          } else if (selectedItemIndex === selectedItems.length - 1) {
            newActiveIndex = selectedItems.length - 2;
          }

          changes = {
            selectedItems: [...selectedItems.slice(0, selectedItemIndex), ...selectedItems.slice(selectedItemIndex + 1)],
            activeIndex: newActiveIndex
          };
        }

        break;
      }

    case FunctionSetSelectedItems:
      {
        const {
          selectedItems: newSelectedItems
        } = action;
        changes = {
          selectedItems: newSelectedItems
        };
        break;
      }

    case FunctionSetActiveIndex:
      {
        const {
          activeIndex: newActiveIndex
        } = action;
        changes = {
          activeIndex: newActiveIndex
        };
        break;
      }

    case FunctionReset:
      changes = {
        activeIndex: getDefaultValue(props, 'activeIndex'),
        selectedItems: getDefaultValue(props, 'selectedItems')
      };
      break;

    default:
      throw new Error('Reducer called without proper action type.');
  }

  return { ...state,
    ...changes
  };
}

useMultipleSelection.stateChangeTypes = stateChangeTypes;

function useMultipleSelection(userProps) {
  if (userProps === void 0) {
    userProps = {};
  }

  validatePropTypes(userProps, useMultipleSelection); // Props defaults and destructuring.

  const props = { ...defaultProps,
    ...userProps
  };
  const {
    getA11yRemovalMessage,
    itemToString,
    environment,
    keyNavigationNext,
    keyNavigationPrevious
  } = props; // Reducer init.

  const [state, dispatch] = useControlledReducer$1(downshiftMultipleSelectionReducer, getInitialState(props), props);
  const {
    activeIndex,
    selectedItems
  } = state; // Refs.

  const isInitialMountRef = (0,react.useRef)(true);
  const dropdownRef = (0,react.useRef)(null);
  const previousSelectedItemsRef = (0,react.useRef)(selectedItems);
  const selectedItemRefs = (0,react.useRef)();
  selectedItemRefs.current = [];
  const latest = useLatestRef({
    state,
    props
  }); // Effects.

  /* Sets a11y status message on changes in selectedItem. */

  (0,react.useEffect)(() => {
    if (isInitialMountRef.current) {
      return;
    }

    if (selectedItems.length < previousSelectedItemsRef.current.length) {
      const removedSelectedItem = previousSelectedItemsRef.current.find(item => selectedItems.indexOf(item) < 0);
      setStatus(getA11yRemovalMessage({
        itemToString,
        resultCount: selectedItems.length,
        removedSelectedItem,
        activeIndex,
        activeSelectedItem: selectedItems[activeIndex]
      }), environment.document);
    }

    previousSelectedItemsRef.current = selectedItems; // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedItems.length]); // Sets focus on active item.

  (0,react.useEffect)(() => {
    if (isInitialMountRef.current) {
      return;
    }

    if (activeIndex === -1 && dropdownRef.current) {
      dropdownRef.current.focus();
    } else if (selectedItemRefs.current[activeIndex]) {
      selectedItemRefs.current[activeIndex].focus();
    }
  }, [activeIndex]);
  useControlPropsValidator({
    isInitialMount: isInitialMountRef.current,
    props,
    state
  });
  const setGetterPropCallInfo = useGetterPropsCalledChecker('getDropdownProps'); // Make initial ref false.

  (0,react.useEffect)(() => {
    isInitialMountRef.current = false;
  }, []); // Event handler functions.

  const selectedItemKeyDownHandlers = (0,react.useMemo)(() => ({
    [keyNavigationPrevious]() {
      dispatch({
        type: SelectedItemKeyDownNavigationPrevious
      });
    },

    [keyNavigationNext]() {
      dispatch({
        type: SelectedItemKeyDownNavigationNext
      });
    },

    Delete() {
      dispatch({
        type: SelectedItemKeyDownDelete
      });
    },

    Backspace() {
      dispatch({
        type: SelectedItemKeyDownBackspace
      });
    }

  }), [dispatch, keyNavigationNext, keyNavigationPrevious]);
  const dropdownKeyDownHandlers = (0,react.useMemo)(() => ({
    [keyNavigationPrevious](event) {
      if (isKeyDownOperationPermitted(event)) {
        dispatch({
          type: DropdownKeyDownNavigationPrevious
        });
      }
    },

    Backspace(event) {
      if (isKeyDownOperationPermitted(event)) {
        dispatch({
          type: DropdownKeyDownBackspace
        });
      }
    }

  }), [dispatch, keyNavigationPrevious]); // Getter props.

  const getSelectedItemProps = (0,react.useCallback)(function (_temp) {
    let {
      refKey = 'ref',
      ref,
      onClick,
      onKeyDown,
      selectedItem,
      index,
      ...rest
    } = _temp === void 0 ? {} : _temp;
    const {
      state: latestState
    } = latest.current;
    const itemIndex = getItemIndex(index, selectedItem, latestState.selectedItems);

    if (itemIndex < 0) {
      throw new Error('Pass either selectedItem or index in getSelectedItemProps!');
    }

    const selectedItemHandleClick = () => {
      dispatch({
        type: SelectedItemClick,
        index
      });
    };

    const selectedItemHandleKeyDown = event => {
      const key = normalizeArrowKey(event);

      if (key && selectedItemKeyDownHandlers[key]) {
        selectedItemKeyDownHandlers[key](event);
      }
    };

    return {
      [refKey]: handleRefs(ref, selectedItemNode => {
        if (selectedItemNode) {
          selectedItemRefs.current.push(selectedItemNode);
        }
      }),
      tabIndex: index === latestState.activeIndex ? 0 : -1,
      onClick: callAllEventHandlers(onClick, selectedItemHandleClick),
      onKeyDown: callAllEventHandlers(onKeyDown, selectedItemHandleKeyDown),
      ...rest
    };
  }, [dispatch, latest, selectedItemKeyDownHandlers]);
  const getDropdownProps = (0,react.useCallback)(function (_temp2, _temp3) {
    let {
      refKey = 'ref',
      ref,
      onKeyDown,
      onClick,
      preventKeyAction = false,
      ...rest
    } = _temp2 === void 0 ? {} : _temp2;
    let {
      suppressRefError = false
    } = _temp3 === void 0 ? {} : _temp3;
    setGetterPropCallInfo('getDropdownProps', suppressRefError, refKey, dropdownRef);

    const dropdownHandleKeyDown = event => {
      const key = normalizeArrowKey(event);

      if (key && dropdownKeyDownHandlers[key]) {
        dropdownKeyDownHandlers[key](event);
      }
    };

    const dropdownHandleClick = () => {
      dispatch({
        type: DropdownClick
      });
    };

    return {
      [refKey]: handleRefs(ref, dropdownNode => {
        if (dropdownNode) {
          dropdownRef.current = dropdownNode;
        }
      }),
      ...(!preventKeyAction && {
        onKeyDown: callAllEventHandlers(onKeyDown, dropdownHandleKeyDown),
        onClick: callAllEventHandlers(onClick, dropdownHandleClick)
      }),
      ...rest
    };
  }, [dispatch, dropdownKeyDownHandlers, setGetterPropCallInfo]); // returns

  const addSelectedItem = (0,react.useCallback)(selectedItem => {
    dispatch({
      type: FunctionAddSelectedItem,
      selectedItem
    });
  }, [dispatch]);
  const removeSelectedItem = (0,react.useCallback)(selectedItem => {
    dispatch({
      type: FunctionRemoveSelectedItem,
      selectedItem
    });
  }, [dispatch]);
  const setSelectedItems = (0,react.useCallback)(newSelectedItems => {
    dispatch({
      type: FunctionSetSelectedItems,
      selectedItems: newSelectedItems
    });
  }, [dispatch]);
  const setActiveIndex = (0,react.useCallback)(newActiveIndex => {
    dispatch({
      type: FunctionSetActiveIndex,
      activeIndex: newActiveIndex
    });
  }, [dispatch]);
  const reset = (0,react.useCallback)(() => {
    dispatch({
      type: FunctionReset
    });
  }, [dispatch]);
  return {
    getSelectedItemProps,
    getDropdownProps,
    addSelectedItem,
    removeSelectedItem,
    setSelectedItems,
    setActiveIndex,
    reset,
    selectedItems,
    activeIndex
  };
}




/***/ }),

/***/ "../../node_modules/.pnpm/react-is@17.0.2/node_modules/react-is/cjs/react-is.production.min.js":
/***/ ((__unused_webpack_module, exports) => {

var __webpack_unused_export__;
/** @license React v17.0.2
 * react-is.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var b=60103,c=60106,d=60107,e=60108,f=60114,g=60109,h=60110,k=60112,l=60113,m=60120,n=60115,p=60116,q=60121,r=60122,u=60117,v=60129,w=60131;
if("function"===typeof Symbol&&Symbol.for){var x=Symbol.for;b=x("react.element");c=x("react.portal");d=x("react.fragment");e=x("react.strict_mode");f=x("react.profiler");g=x("react.provider");h=x("react.context");k=x("react.forward_ref");l=x("react.suspense");m=x("react.suspense_list");n=x("react.memo");p=x("react.lazy");q=x("react.block");r=x("react.server.block");u=x("react.fundamental");v=x("react.debug_trace_mode");w=x("react.legacy_hidden")}
function y(a){if("object"===typeof a&&null!==a){var t=a.$$typeof;switch(t){case b:switch(a=a.type,a){case d:case f:case e:case l:case m:return a;default:switch(a=a&&a.$$typeof,a){case h:case k:case p:case n:case g:return a;default:return t}}case c:return t}}}var z=g,A=b,B=k,C=d,D=p,E=n,F=c,G=f,H=e,I=l;__webpack_unused_export__=h;__webpack_unused_export__=z;__webpack_unused_export__=A;__webpack_unused_export__=B;__webpack_unused_export__=C;__webpack_unused_export__=D;__webpack_unused_export__=E;__webpack_unused_export__=F;__webpack_unused_export__=G;__webpack_unused_export__=H;
__webpack_unused_export__=I;__webpack_unused_export__=function(){return!1};__webpack_unused_export__=function(){return!1};__webpack_unused_export__=function(a){return y(a)===h};__webpack_unused_export__=function(a){return y(a)===g};__webpack_unused_export__=function(a){return"object"===typeof a&&null!==a&&a.$$typeof===b};__webpack_unused_export__=function(a){return y(a)===k};__webpack_unused_export__=function(a){return y(a)===d};__webpack_unused_export__=function(a){return y(a)===p};__webpack_unused_export__=function(a){return y(a)===n};
__webpack_unused_export__=function(a){return y(a)===c};__webpack_unused_export__=function(a){return y(a)===f};__webpack_unused_export__=function(a){return y(a)===e};__webpack_unused_export__=function(a){return y(a)===l};__webpack_unused_export__=function(a){return"string"===typeof a||"function"===typeof a||a===d||a===f||a===v||a===e||a===l||a===m||a===w||"object"===typeof a&&null!==a&&(a.$$typeof===p||a.$$typeof===n||a.$$typeof===g||a.$$typeof===h||a.$$typeof===k||a.$$typeof===u||a.$$typeof===q||a[0]===r)?!0:!1};
__webpack_unused_export__=y;


/***/ }),

/***/ "../../node_modules/.pnpm/react-is@17.0.2/node_modules/react-is/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  /* unused reexport */ __webpack_require__("../../node_modules/.pnpm/react-is@17.0.2/node_modules/react-is/cjs/react-is.production.min.js");
} else {}


/***/ }),

/***/ "../../node_modules/.pnpm/tslib@2.6.2/node_modules/tslib/tslib.es6.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   C6: () => (/* binding */ __extends),
/* harmony export */   Cl: () => (/* binding */ __assign),
/* harmony export */   Ju: () => (/* binding */ __values),
/* harmony export */   Tt: () => (/* binding */ __rest),
/* harmony export */   fX: () => (/* binding */ __spreadArray),
/* harmony export */   zs: () => (/* binding */ __read)
/* harmony export */ });
/* unused harmony exports __decorate, __param, __esDecorate, __runInitializers, __propKey, __setFunctionName, __metadata, __awaiter, __generator, __createBinding, __exportStar, __spread, __spreadArrays, __await, __asyncGenerator, __asyncDelegator, __asyncValues, __makeTemplateObject, __importStar, __importDefault, __classPrivateFieldGet, __classPrivateFieldSet, __classPrivateFieldIn, __addDisposableResource, __disposeResources */
/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise, SuppressedError, Symbol */

var extendStatics = function(d, b) {
  extendStatics = Object.setPrototypeOf ||
      ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
      function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
  return extendStatics(d, b);
};

function __extends(d, b) {
  if (typeof b !== "function" && b !== null)
      throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
  extendStatics(d, b);
  function __() { this.constructor = d; }
  d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

var __assign = function() {
  __assign = Object.assign || function __assign(t) {
      for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
      }
      return t;
  }
  return __assign.apply(this, arguments);
}

function __rest(s, e) {
  var t = {};
  for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
      t[p] = s[p];
  if (s != null && typeof Object.getOwnPropertySymbols === "function")
      for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
          if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
              t[p[i]] = s[p[i]];
      }
  return t;
}

function __decorate(decorators, target, key, desc) {
  var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
  if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
  else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
  return c > 3 && r && Object.defineProperty(target, key, r), r;
}

function __param(paramIndex, decorator) {
  return function (target, key) { decorator(target, key, paramIndex); }
}

function __esDecorate(ctor, descriptorIn, decorators, contextIn, initializers, extraInitializers) {
  function accept(f) { if (f !== void 0 && typeof f !== "function") throw new TypeError("Function expected"); return f; }
  var kind = contextIn.kind, key = kind === "getter" ? "get" : kind === "setter" ? "set" : "value";
  var target = !descriptorIn && ctor ? contextIn["static"] ? ctor : ctor.prototype : null;
  var descriptor = descriptorIn || (target ? Object.getOwnPropertyDescriptor(target, contextIn.name) : {});
  var _, done = false;
  for (var i = decorators.length - 1; i >= 0; i--) {
      var context = {};
      for (var p in contextIn) context[p] = p === "access" ? {} : contextIn[p];
      for (var p in contextIn.access) context.access[p] = contextIn.access[p];
      context.addInitializer = function (f) { if (done) throw new TypeError("Cannot add initializers after decoration has completed"); extraInitializers.push(accept(f || null)); };
      var result = (0, decorators[i])(kind === "accessor" ? { get: descriptor.get, set: descriptor.set } : descriptor[key], context);
      if (kind === "accessor") {
          if (result === void 0) continue;
          if (result === null || typeof result !== "object") throw new TypeError("Object expected");
          if (_ = accept(result.get)) descriptor.get = _;
          if (_ = accept(result.set)) descriptor.set = _;
          if (_ = accept(result.init)) initializers.unshift(_);
      }
      else if (_ = accept(result)) {
          if (kind === "field") initializers.unshift(_);
          else descriptor[key] = _;
      }
  }
  if (target) Object.defineProperty(target, contextIn.name, descriptor);
  done = true;
};

function __runInitializers(thisArg, initializers, value) {
  var useValue = arguments.length > 2;
  for (var i = 0; i < initializers.length; i++) {
      value = useValue ? initializers[i].call(thisArg, value) : initializers[i].call(thisArg);
  }
  return useValue ? value : void 0;
};

function __propKey(x) {
  return typeof x === "symbol" ? x : "".concat(x);
};

function __setFunctionName(f, name, prefix) {
  if (typeof name === "symbol") name = name.description ? "[".concat(name.description, "]") : "";
  return Object.defineProperty(f, "name", { configurable: true, value: prefix ? "".concat(prefix, " ", name) : name });
};

function __metadata(metadataKey, metadataValue) {
  if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(metadataKey, metadataValue);
}

function __awaiter(thisArg, _arguments, P, generator) {
  function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
  return new (P || (P = Promise))(function (resolve, reject) {
      function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
      function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
      function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
      step((generator = generator.apply(thisArg, _arguments || [])).next());
  });
}

function __generator(thisArg, body) {
  var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
  return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
  function verb(n) { return function (v) { return step([n, v]); }; }
  function step(op) {
      if (f) throw new TypeError("Generator is already executing.");
      while (g && (g = 0, op[0] && (_ = 0)), _) try {
          if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
          if (y = 0, t) op = [op[0] & 2, t.value];
          switch (op[0]) {
              case 0: case 1: t = op; break;
              case 4: _.label++; return { value: op[1], done: false };
              case 5: _.label++; y = op[1]; op = [0]; continue;
              case 7: op = _.ops.pop(); _.trys.pop(); continue;
              default:
                  if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                  if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                  if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                  if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                  if (t[2]) _.ops.pop();
                  _.trys.pop(); continue;
          }
          op = body.call(thisArg, _);
      } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
      if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
  }
}

var __createBinding = Object.create ? (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  var desc = Object.getOwnPropertyDescriptor(m, k);
  if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
  }
  Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  o[k2] = m[k];
});

function __exportStar(m, o) {
  for (var p in m) if (p !== "default" && !Object.prototype.hasOwnProperty.call(o, p)) __createBinding(o, m, p);
}

function __values(o) {
  var s = typeof Symbol === "function" && Symbol.iterator, m = s && o[s], i = 0;
  if (m) return m.call(o);
  if (o && typeof o.length === "number") return {
      next: function () {
          if (o && i >= o.length) o = void 0;
          return { value: o && o[i++], done: !o };
      }
  };
  throw new TypeError(s ? "Object is not iterable." : "Symbol.iterator is not defined.");
}

function __read(o, n) {
  var m = typeof Symbol === "function" && o[Symbol.iterator];
  if (!m) return o;
  var i = m.call(o), r, ar = [], e;
  try {
      while ((n === void 0 || n-- > 0) && !(r = i.next()).done) ar.push(r.value);
  }
  catch (error) { e = { error: error }; }
  finally {
      try {
          if (r && !r.done && (m = i["return"])) m.call(i);
      }
      finally { if (e) throw e.error; }
  }
  return ar;
}

/** @deprecated */
function __spread() {
  for (var ar = [], i = 0; i < arguments.length; i++)
      ar = ar.concat(__read(arguments[i]));
  return ar;
}

/** @deprecated */
function __spreadArrays() {
  for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
  for (var r = Array(s), k = 0, i = 0; i < il; i++)
      for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
          r[k] = a[j];
  return r;
}

function __spreadArray(to, from, pack) {
  if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
      if (ar || !(i in from)) {
          if (!ar) ar = Array.prototype.slice.call(from, 0, i);
          ar[i] = from[i];
      }
  }
  return to.concat(ar || Array.prototype.slice.call(from));
}

function __await(v) {
  return this instanceof __await ? (this.v = v, this) : new __await(v);
}

function __asyncGenerator(thisArg, _arguments, generator) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var g = generator.apply(thisArg, _arguments || []), i, q = [];
  return i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i;
  function verb(n) { if (g[n]) i[n] = function (v) { return new Promise(function (a, b) { q.push([n, v, a, b]) > 1 || resume(n, v); }); }; }
  function resume(n, v) { try { step(g[n](v)); } catch (e) { settle(q[0][3], e); } }
  function step(r) { r.value instanceof __await ? Promise.resolve(r.value.v).then(fulfill, reject) : settle(q[0][2], r); }
  function fulfill(value) { resume("next", value); }
  function reject(value) { resume("throw", value); }
  function settle(f, v) { if (f(v), q.shift(), q.length) resume(q[0][0], q[0][1]); }
}

function __asyncDelegator(o) {
  var i, p;
  return i = {}, verb("next"), verb("throw", function (e) { throw e; }), verb("return"), i[Symbol.iterator] = function () { return this; }, i;
  function verb(n, f) { i[n] = o[n] ? function (v) { return (p = !p) ? { value: __await(o[n](v)), done: false } : f ? f(v) : v; } : f; }
}

function __asyncValues(o) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var m = o[Symbol.asyncIterator], i;
  return m ? m.call(o) : (o = typeof __values === "function" ? __values(o) : o[Symbol.iterator](), i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i);
  function verb(n) { i[n] = o[n] && function (v) { return new Promise(function (resolve, reject) { v = o[n](v), settle(resolve, reject, v.done, v.value); }); }; }
  function settle(resolve, reject, d, v) { Promise.resolve(v).then(function(v) { resolve({ value: v, done: d }); }, reject); }
}

function __makeTemplateObject(cooked, raw) {
  if (Object.defineProperty) { Object.defineProperty(cooked, "raw", { value: raw }); } else { cooked.raw = raw; }
  return cooked;
};

var __setModuleDefault = Object.create ? (function(o, v) {
  Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
  o["default"] = v;
};

function __importStar(mod) {
  if (mod && mod.__esModule) return mod;
  var result = {};
  if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
  __setModuleDefault(result, mod);
  return result;
}

function __importDefault(mod) {
  return (mod && mod.__esModule) ? mod : { default: mod };
}

function __classPrivateFieldGet(receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
}

function __classPrivateFieldSet(receiver, state, value, kind, f) {
  if (kind === "m") throw new TypeError("Private method is not writable");
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
  return (kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value)), value;
}

function __classPrivateFieldIn(state, receiver) {
  if (receiver === null || (typeof receiver !== "object" && typeof receiver !== "function")) throw new TypeError("Cannot use 'in' operator on non-object");
  return typeof state === "function" ? receiver === state : state.has(receiver);
}

function __addDisposableResource(env, value, async) {
  if (value !== null && value !== void 0) {
    if (typeof value !== "object" && typeof value !== "function") throw new TypeError("Object expected.");
    var dispose;
    if (async) {
        if (!Symbol.asyncDispose) throw new TypeError("Symbol.asyncDispose is not defined.");
        dispose = value[Symbol.asyncDispose];
    }
    if (dispose === void 0) {
        if (!Symbol.dispose) throw new TypeError("Symbol.dispose is not defined.");
        dispose = value[Symbol.dispose];
    }
    if (typeof dispose !== "function") throw new TypeError("Object not disposable.");
    env.stack.push({ value: value, dispose: dispose, async: async });
  }
  else if (async) {
    env.stack.push({ async: true });
  }
  return value;
}

var _SuppressedError = typeof SuppressedError === "function" ? SuppressedError : function (error, suppressed, message) {
  var e = new Error(message);
  return e.name = "SuppressedError", e.error = error, e.suppressed = suppressed, e;
};

function __disposeResources(env) {
  function fail(e) {
    env.error = env.hasError ? new _SuppressedError(e, env.error, "An error was suppressed during disposal.") : e;
    env.hasError = true;
  }
  function next() {
    while (env.stack.length) {
      var rec = env.stack.pop();
      try {
        var result = rec.dispose && rec.dispose.call(rec.value);
        if (rec.async) return Promise.resolve(result).then(next, function(e) { fail(e); return next(); });
      }
      catch (e) {
          fail(e);
      }
    }
    if (env.hasError) throw env.error;
  }
  return next();
}

/* unused harmony default export */ var __WEBPACK_DEFAULT_EXPORT__ = ({
  __extends,
  __assign,
  __rest,
  __decorate,
  __param,
  __metadata,
  __awaiter,
  __generator,
  __createBinding,
  __exportStar,
  __values,
  __read,
  __spread,
  __spreadArrays,
  __spreadArray,
  __await,
  __asyncGenerator,
  __asyncDelegator,
  __asyncValues,
  __makeTemplateObject,
  __importStar,
  __importDefault,
  __classPrivateFieldGet,
  __classPrivateFieldSet,
  __classPrivateFieldIn,
  __addDisposableResource,
  __disposeResources,
});


/***/ })

}]);