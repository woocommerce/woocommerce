"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7563],{

/***/ "../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AH: () => (/* binding */ css),
/* harmony export */   i7: () => (/* binding */ keyframes)
/* harmony export */ });
/* unused harmony exports ClassNames, Global, createElement, jsx */
/* harmony import */ var _emotion_element_f0de968e_browser_esm_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-element-f0de968e.browser.esm.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _emotion_use_insertion_effect_with_fallbacks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@emotion+use-insertion-effe_faa705d9266cb5c09641a73e3ce6b914/node_modules/@emotion/use-insertion-effect-with-fallbacks/dist/emotion-use-insertion-effect-with-fallbacks.browser.esm.js");
/* harmony import */ var _emotion_serialize__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@emotion+serialize@1.3.3/node_modules/@emotion/serialize/dist/emotion-serialize.esm.js");
/* harmony import */ var _emotion_cache__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@emotion+cache@11.14.0/node_modules/@emotion/cache/dist/emotion-cache.browser.esm.js");
/* harmony import */ var hoist_non_react_statics__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/hoist-non-react-statics@3.3.2/node_modules/hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js");
/* harmony import */ var hoist_non_react_statics__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(hoist_non_react_statics__WEBPACK_IMPORTED_MODULE_4__);












var jsx = function jsx(type, props) {
  // eslint-disable-next-line prefer-rest-params
  var args = arguments;

  if (props == null || !_emotion_element_f0de968e_browser_esm_js__WEBPACK_IMPORTED_MODULE_5__.h.call(props, 'css')) {
    return react__WEBPACK_IMPORTED_MODULE_0__.createElement.apply(undefined, args);
  }

  var argsLength = args.length;
  var createElementArgArray = new Array(argsLength);
  createElementArgArray[0] = _emotion_element_f0de968e_browser_esm_js__WEBPACK_IMPORTED_MODULE_5__.E;
  createElementArgArray[1] = (0,_emotion_element_f0de968e_browser_esm_js__WEBPACK_IMPORTED_MODULE_5__.c)(type, props);

  for (var i = 2; i < argsLength; i++) {
    createElementArgArray[i] = args[i];
  }

  return react__WEBPACK_IMPORTED_MODULE_0__.createElement.apply(null, createElementArgArray);
};

(function (_jsx) {
  var JSX;

  (function (_JSX) {})(JSX || (JSX = _jsx.JSX || (_jsx.JSX = {})));
})(jsx || (jsx = {}));

// initial render from browser, insertBefore context.sheet.tags[0] or if a style hasn't been inserted there yet, appendChild
// initial client-side render from SSR, use place of hydrating tag

var Global = /* #__PURE__ */(/* unused pure expression or super */ null && (withEmotionCache(function (props, cache) {

  var styles = props.styles;
  var serialized = serializeStyles([styles], undefined, React.useContext(ThemeContext));
  // but it is based on a constant that will never change at runtime
  // it's effectively like having two implementations and switching them out
  // so it's not actually breaking anything


  var sheetRef = React.useRef();
  useInsertionEffectWithLayoutFallback(function () {
    var key = cache.key + "-global"; // use case of https://github.com/emotion-js/emotion/issues/2675

    var sheet = new cache.sheet.constructor({
      key: key,
      nonce: cache.sheet.nonce,
      container: cache.sheet.container,
      speedy: cache.sheet.isSpeedy
    });
    var rehydrating = false;
    var node = document.querySelector("style[data-emotion=\"" + key + " " + serialized.name + "\"]");

    if (cache.sheet.tags.length) {
      sheet.before = cache.sheet.tags[0];
    }

    if (node !== null) {
      rehydrating = true; // clear the hash so this node won't be recognizable as rehydratable by other <Global/>s

      node.setAttribute('data-emotion', key);
      sheet.hydrate([node]);
    }

    sheetRef.current = [sheet, rehydrating];
    return function () {
      sheet.flush();
    };
  }, [cache]);
  useInsertionEffectWithLayoutFallback(function () {
    var sheetRefCurrent = sheetRef.current;
    var sheet = sheetRefCurrent[0],
        rehydrating = sheetRefCurrent[1];

    if (rehydrating) {
      sheetRefCurrent[1] = false;
      return;
    }

    if (serialized.next !== undefined) {
      // insert keyframes
      insertStyles(cache, serialized.next, true);
    }

    if (sheet.tags.length) {
      // if this doesn't exist then it will be null so the style element will be appended
      var element = sheet.tags[sheet.tags.length - 1].nextElementSibling;
      sheet.before = element;
      sheet.flush();
    }

    cache.insert("", serialized, sheet, false);
  }, [cache, serialized.name]);
  return null;
})));

function css() {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }

  return (0,_emotion_serialize__WEBPACK_IMPORTED_MODULE_2__/* .serializeStyles */ .J)(args);
}

function keyframes() {
  var insertable = css.apply(void 0, arguments);
  var name = "animation-" + insertable.name;
  return {
    name: name,
    styles: "@keyframes " + name + "{" + insertable.styles + "}",
    anim: 1,
    toString: function toString() {
      return "_EMO_" + this.name + "_" + this.styles + "_EMO_";
    }
  };
}

var classnames = function classnames(args) {
  var len = args.length;
  var i = 0;
  var cls = '';

  for (; i < len; i++) {
    var arg = args[i];
    if (arg == null) continue;
    var toAdd = void 0;

    switch (typeof arg) {
      case 'boolean':
        break;

      case 'object':
        {
          if (Array.isArray(arg)) {
            toAdd = classnames(arg);
          } else {

            toAdd = '';

            for (var k in arg) {
              if (arg[k] && k) {
                toAdd && (toAdd += ' ');
                toAdd += k;
              }
            }
          }

          break;
        }

      default:
        {
          toAdd = arg;
        }
    }

    if (toAdd) {
      cls && (cls += ' ');
      cls += toAdd;
    }
  }

  return cls;
};

function merge(registered, css, className) {
  var registeredStyles = [];
  var rawClassName = getRegisteredStyles(registered, registeredStyles, className);

  if (registeredStyles.length < 2) {
    return className;
  }

  return rawClassName + css(registeredStyles);
}

var Insertion = function Insertion(_ref) {
  var cache = _ref.cache,
      serializedArr = _ref.serializedArr;
  useInsertionEffectAlwaysWithSyncFallback(function () {

    for (var i = 0; i < serializedArr.length; i++) {
      insertStyles(cache, serializedArr[i], false);
    }
  });

  return null;
};

var ClassNames = /* #__PURE__ */(/* unused pure expression or super */ null && (withEmotionCache(function (props, cache) {
  var hasRendered = false;
  var serializedArr = [];

  var css = function css() {
    if (hasRendered && isDevelopment) {
      throw new Error('css can only be used during render');
    }

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    var serialized = serializeStyles(args, cache.registered);
    serializedArr.push(serialized); // registration has to happen here as the result of this might get consumed by `cx`

    registerStyles(cache, serialized, false);
    return cache.key + "-" + serialized.name;
  };

  var cx = function cx() {
    if (hasRendered && isDevelopment) {
      throw new Error('cx can only be used during render');
    }

    for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      args[_key2] = arguments[_key2];
    }

    return merge(cache.registered, css, classnames(args));
  };

  var content = {
    css: css,
    cx: cx,
    theme: React.useContext(ThemeContext)
  };
  var ele = props.children(content);
  hasRendered = true;
  return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Insertion, {
    cache: cache,
    serializedArr: serializedArr
  }), ele);
})));




/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   l: () => (/* binding */ COLORS)
/* harmony export */ });
/* unused harmony export default */
const white = "#fff";
const GRAY = {
  900: "#1e1e1e",
  800: "#2f2f2f",
  /** Meets 4.6:1 text contrast against white. */
  700: "#757575",
  /** Meets 3:1 UI or large text contrast against white. */
  600: "#949494",
  400: "#ccc",
  /** Used for most borders. */
  300: "#ddd",
  /** Used sparingly for light borders. */
  200: "#e0e0e0",
  /** Used for light gray backgrounds. */
  100: "#f0f0f0"
};
const ALERT = {
  yellow: "#f0b849",
  red: "#d94f4f",
  green: "#4ab866"
};
const THEME = {
  accent: `var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9))`,
  accentDarker10: `var(--wp-components-color-accent-darker-10, var(--wp-admin-theme-color-darker-10, #2145e6))`,
  accentDarker20: `var(--wp-components-color-accent-darker-20, var(--wp-admin-theme-color-darker-20, #183ad6))`,
  /** Used when placing text on the accent color. */
  accentInverted: `var(--wp-components-color-accent-inverted, ${white})`,
  background: `var(--wp-components-color-background, ${white})`,
  foreground: `var(--wp-components-color-foreground, ${GRAY[900]})`,
  /** Used when placing text on the foreground color. */
  foregroundInverted: `var(--wp-components-color-foreground-inverted, ${white})`,
  gray: {
    /** @deprecated Use `COLORS.theme.foreground` instead. */
    900: `var(--wp-components-color-foreground, ${GRAY[900]})`,
    800: `var(--wp-components-color-gray-800, ${GRAY[800]})`,
    700: `var(--wp-components-color-gray-700, ${GRAY[700]})`,
    600: `var(--wp-components-color-gray-600, ${GRAY[600]})`,
    400: `var(--wp-components-color-gray-400, ${GRAY[400]})`,
    300: `var(--wp-components-color-gray-300, ${GRAY[300]})`,
    200: `var(--wp-components-color-gray-200, ${GRAY[200]})`,
    100: `var(--wp-components-color-gray-100, ${GRAY[100]})`
  }
};
const UI = {
  background: THEME.background,
  backgroundDisabled: THEME.gray[100],
  border: THEME.gray[600],
  borderHover: THEME.gray[700],
  borderFocus: THEME.accent,
  borderDisabled: THEME.gray[400],
  textDisabled: THEME.gray[600],
  // Matches @wordpress/base-styles
  darkGrayPlaceholder: `color-mix(in srgb, ${THEME.foreground}, transparent 38%)`,
  lightGrayPlaceholder: `color-mix(in srgb, ${THEME.background}, transparent 35%)`
};
const COLORS = Object.freeze({
  /**
   * The main gray color object.
   *
   * @deprecated Use semantic aliases in `COLORS.ui` or theme-ready variables in `COLORS.theme.gray`.
   */
  gray: GRAY,
  // TODO: Stop exporting this when everything is migrated to `theme` or `ui`
  /**
   * @deprecated Prefer theme-ready variables in `COLORS.theme`.
   */
  white,
  alert: ALERT,
  /**
   * Theme-ready variables with fallbacks.
   *
   * Prefer semantic aliases in `COLORS.ui` when applicable.
   */
  theme: THEME,
  /**
   * Semantic aliases (prefer these over raw variables when applicable).
   */
  ui: UI
});
var colors_values_default = (/* unused pure expression or super */ null && (COLORS));

//# sourceMappingURL=colors-values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   x: () => (/* binding */ space)
/* harmony export */ });
const GRID_BASE = "4px";
function space(value) {
  if (typeof value === "undefined") {
    return void 0;
  }
  if (!value) {
    return "0";
  }
  const asInt = typeof value === "number" ? value : Number(value);
  if (typeof window !== "undefined" && window.CSS?.supports?.("margin", value.toString()) || Number.isNaN(asInt)) {
    return value.toString();
  }
  return `calc(${GRID_BASE} * ${value})`;
}

//# sourceMappingURL=space.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ domReady)
/* harmony export */ });
// packages/dom-ready/src/index.ts
function domReady(callback) {
  if (typeof document === "undefined") {
    return;
  }
  if (document.readyState === "complete" || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
  document.readyState === "interactive") {
    return void callback();
  }
  document.addEventListener("DOMContentLoaded", callback);
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Q8: () => (/* reexport */ media_upload_default),
  o7: () => (/* reexport */ uploadMedia)
});

// UNUSED EXPORTS: privateApis, transformAttachment, validateFileSize, validateMimeType, validateMimeTypeForUser

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/components/media-upload/index.js


const DEFAULT_EMPTY_GALLERY = [];
const getFeaturedImageMediaFrame = () => {
  const { wp } = window;
  return wp.media.view.MediaFrame.Select.extend({
    /**
     * Enables the Set Featured Image Button.
     *
     * @param {Object} toolbar toolbar for featured image state
     * @return {void}
     */
    featuredImageToolbar(toolbar) {
      this.createSelectToolbar(toolbar, {
        text: wp.media.view.l10n.setFeaturedImage,
        state: this.options.state
      });
    },
    /**
     * Handle the edit state requirements of selected media item.
     *
     * @return {void}
     */
    editState() {
      const selection = this.state("featured-image").get("selection");
      const view = new wp.media.view.EditImage({
        model: selection.single(),
        controller: this
      }).render();
      this.content.set(view);
      view.loadEditor();
    },
    /**
     * Create the default states.
     *
     * @return {void}
     */
    createStates: function createStates() {
      this.on(
        "toolbar:create:featured-image",
        this.featuredImageToolbar,
        this
      );
      this.on("content:render:edit-image", this.editState, this);
      this.states.add([
        new wp.media.controller.FeaturedImage(),
        new wp.media.controller.EditImage({
          model: this.options.editImage
        })
      ]);
    }
  });
};
const getSingleMediaFrame = () => {
  const { wp } = window;
  return wp.media.view.MediaFrame.Select.extend({
    /**
     * Create the default states on the frame.
     */
    createStates() {
      const options = this.options;
      if (this.options.states) {
        return;
      }
      this.states.add([
        // Main states.
        new wp.media.controller.Library({
          library: wp.media.query(options.library),
          multiple: options.multiple,
          title: options.title,
          priority: 20,
          filterable: "uploaded"
          // Allow filtering by uploaded images.
        }),
        new wp.media.controller.EditImage({
          model: options.editImage
        })
      ]);
    }
  });
};
const getGalleryDetailsMediaFrame = () => {
  const { wp } = window;
  return wp.media.view.MediaFrame.Post.extend({
    /**
     * Set up gallery toolbar.
     *
     * @return {void}
     */
    galleryToolbar() {
      const editing = this.state().get("editing");
      this.toolbar.set(
        new wp.media.view.Toolbar({
          controller: this,
          items: {
            insert: {
              style: "primary",
              text: editing ? wp.media.view.l10n.updateGallery : wp.media.view.l10n.insertGallery,
              priority: 80,
              requires: { library: true },
              /**
               * @fires wp.media.controller.State#update
               */
              click() {
                const controller = this.controller, state = controller.state();
                controller.close();
                state.trigger(
                  "update",
                  state.get("library")
                );
                controller.setState(controller.options.state);
                controller.reset();
              }
            }
          }
        })
      );
    },
    /**
     * Handle the edit state requirements of selected media item.
     *
     * @return {void}
     */
    editState() {
      const selection = this.state("gallery").get("selection");
      const view = new wp.media.view.EditImage({
        model: selection.single(),
        controller: this
      }).render();
      this.content.set(view);
      view.loadEditor();
    },
    /**
     * Create the default states.
     *
     * @return {void}
     */
    createStates: function createStates() {
      this.on("toolbar:create:main-gallery", this.galleryToolbar, this);
      this.on("content:render:edit-image", this.editState, this);
      this.states.add([
        new wp.media.controller.Library({
          id: "gallery",
          title: wp.media.view.l10n.createGalleryTitle,
          priority: 40,
          toolbar: "main-gallery",
          filterable: "uploaded",
          multiple: "add",
          editable: false,
          library: wp.media.query({
            type: "image",
            ...this.options.library
          })
        }),
        new wp.media.controller.EditImage({
          model: this.options.editImage
        }),
        new wp.media.controller.GalleryEdit({
          library: this.options.selection,
          editing: this.options.editing,
          menu: "gallery",
          displaySettings: false,
          multiple: true
        }),
        new wp.media.controller.GalleryAdd()
      ]);
    }
  });
};
const slimImageObject = (img) => {
  const attrSet = [
    "sizes",
    "mime",
    "type",
    "subtype",
    "id",
    "url",
    "alt",
    "link",
    "caption"
  ];
  return attrSet.reduce((result, key) => {
    if (img?.hasOwnProperty(key)) {
      result[key] = img[key];
    }
    return result;
  }, {});
};
const getAttachmentsCollection = (ids) => {
  const { wp } = window;
  return wp.media.query({
    order: "ASC",
    orderby: "post__in",
    post__in: ids,
    posts_per_page: -1,
    query: true,
    type: "image"
  });
};
class MediaUpload extends react.Component {
  constructor() {
    super(...arguments);
    this.openModal = this.openModal.bind(this);
    this.onOpen = this.onOpen.bind(this);
    this.onSelect = this.onSelect.bind(this);
    this.onUpdate = this.onUpdate.bind(this);
    this.onClose = this.onClose.bind(this);
  }
  initializeListeners() {
    this.frame.on("select", this.onSelect);
    this.frame.on("update", this.onUpdate);
    this.frame.on("open", this.onOpen);
    this.frame.on("close", this.onClose);
  }
  /**
   * Sets the Gallery frame and initializes listeners.
   *
   * @return {void}
   */
  buildAndSetGalleryFrame() {
    const {
      addToGallery = false,
      allowedTypes,
      multiple = false,
      value = DEFAULT_EMPTY_GALLERY
    } = this.props;
    if (value === this.lastGalleryValue) {
      return;
    }
    const { wp } = window;
    this.lastGalleryValue = value;
    if (this.frame) {
      this.frame.remove();
    }
    let currentState;
    if (addToGallery) {
      currentState = "gallery-library";
    } else {
      currentState = value && value.length ? "gallery-edit" : "gallery";
    }
    if (!this.GalleryDetailsMediaFrame) {
      this.GalleryDetailsMediaFrame = getGalleryDetailsMediaFrame();
    }
    const attachments = getAttachmentsCollection(value);
    const selection = new wp.media.model.Selection(attachments.models, {
      props: attachments.props.toJSON(),
      multiple
    });
    this.frame = new this.GalleryDetailsMediaFrame({
      mimeType: allowedTypes,
      state: currentState,
      multiple,
      selection,
      editing: !!value?.length
    });
    wp.media.frame = this.frame;
    this.initializeListeners();
  }
  /**
   * Initializes the Media Library requirements for the featured image flow.
   *
   * @return {void}
   */
  buildAndSetFeatureImageFrame() {
    const { wp } = window;
    const { value: featuredImageId, multiple, allowedTypes } = this.props;
    const featuredImageFrame = getFeaturedImageMediaFrame();
    const attachments = getAttachmentsCollection(featuredImageId);
    const selection = new wp.media.model.Selection(attachments.models, {
      props: attachments.props.toJSON()
    });
    this.frame = new featuredImageFrame({
      mimeType: allowedTypes,
      state: "featured-image",
      multiple,
      selection,
      editing: featuredImageId
    });
    wp.media.frame = this.frame;
    wp.media.view.settings.post = {
      ...wp.media.view.settings.post,
      featuredImageId: featuredImageId || -1
    };
  }
  /**
   * Initializes the Media Library requirements for the single image flow.
   *
   * @return {void}
   */
  buildAndSetSingleMediaFrame() {
    const { wp } = window;
    const {
      allowedTypes,
      multiple = false,
      title = (0,build_module.__)("Select or Upload Media"),
      value
    } = this.props;
    const frameConfig = {
      title,
      multiple
    };
    if (!!allowedTypes) {
      frameConfig.library = { type: allowedTypes };
    }
    if (this.frame) {
      this.frame.remove();
    }
    const singleImageFrame = getSingleMediaFrame();
    const attachments = getAttachmentsCollection(value);
    const selection = new wp.media.model.Selection(attachments.models, {
      props: attachments.props.toJSON()
    });
    this.frame = new singleImageFrame({
      mimeType: allowedTypes,
      multiple,
      selection,
      ...frameConfig
    });
    wp.media.frame = this.frame;
  }
  componentWillUnmount() {
    this.frame?.remove();
  }
  onUpdate(selections) {
    const { onSelect, multiple = false } = this.props;
    const state = this.frame.state();
    const selectedImages = selections || state.get("selection");
    if (!selectedImages || !selectedImages.models.length) {
      return;
    }
    if (multiple) {
      onSelect(
        selectedImages.models.map(
          (model) => slimImageObject(model.toJSON())
        )
      );
    } else {
      onSelect(slimImageObject(selectedImages.models[0].toJSON()));
    }
  }
  onSelect() {
    const { onSelect, multiple = false } = this.props;
    const attachment = this.frame.state().get("selection").toJSON();
    onSelect(multiple ? attachment : attachment[0]);
  }
  onOpen() {
    const { wp } = window;
    const { value } = this.props;
    this.updateCollection();
    if (this.props.mode) {
      this.frame.content.mode(this.props.mode);
    }
    const hasMedia = Array.isArray(value) ? !!value?.length : !!value;
    if (!hasMedia) {
      return;
    }
    const isGallery = this.props.gallery;
    const selection = this.frame.state().get("selection");
    const valueArray = Array.isArray(value) ? value : [value];
    if (!isGallery) {
      valueArray.forEach((id) => {
        selection.add(wp.media.attachment(id));
      });
    }
    const attachments = getAttachmentsCollection(valueArray);
    attachments.more().done(function() {
      if (isGallery && attachments?.models?.length) {
        selection.add(attachments.models);
      }
    });
  }
  onClose() {
    const { onClose } = this.props;
    if (onClose) {
      onClose();
    }
    this.frame.detach();
  }
  updateCollection() {
    const frameContent = this.frame.content.get();
    if (frameContent && frameContent.collection) {
      const collection = frameContent.collection;
      collection.toArray().forEach((model) => model.trigger("destroy", model));
      collection.mirroring._hasMore = true;
      collection.more();
    }
  }
  openModal() {
    const {
      gallery = false,
      unstableFeaturedImageFlow = false,
      modalClass
    } = this.props;
    if (gallery) {
      this.buildAndSetGalleryFrame();
    } else {
      this.buildAndSetSingleMediaFrame();
    }
    if (modalClass) {
      this.frame.$el.addClass(modalClass);
    }
    if (unstableFeaturedImageFlow) {
      this.buildAndSetFeatureImageFrame();
    }
    this.initializeListeners();
    this.frame.open();
  }
  render() {
    return this.props.render({ open: this.openModal });
  }
}
var media_upload_default = MediaUpload;

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/components/index.js


//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+blob@4.48.1/node_modules/@wordpress/blob/build-module/index.mjs
// packages/blob/src/index.ts
var cache = {};
function createBlobURL(file) {
  const url = window.URL.createObjectURL(file);
  cache[url] = file;
  return url;
}
function getBlobByURL(url) {
  return cache[url];
}
function getBlobTypeByURL(url) {
  return getBlobByURL(url)?.type.split("/")[0];
}
function revokeBlobURL(url) {
  if (cache[url]) {
    window.URL.revokeObjectURL(url);
  }
  delete cache[url];
}
function isBlobURL(url) {
  if (!url || !url.indexOf) {
    return false;
  }
  return url.indexOf("blob:") === 0;
}
function downloadBlob(filename, content, contentType = "") {
  if (!filename || !content) {
    return;
  }
  const file = new window.Blob([content], { type: contentType });
  const url = window.URL.createObjectURL(file);
  const anchorElement = document.createElement("a");
  anchorElement.href = url;
  anchorElement.download = filename;
  anchorElement.style.display = "none";
  document.body.appendChild(anchorElement);
  anchorElement.click();
  document.body.removeChild(anchorElement);
  window.URL.revokeObjectURL(url);
}

//# sourceMappingURL=index.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs + 2 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs
var implementation = __webpack_require__("../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/lock-unlock.mjs
// packages/api-fetch/src/lock-unlock.ts

var { lock, unlock } = (0,implementation/* __dangerousOptInToUnstableAPIsOnlyForCoreModules */.yf)(
  "I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.",
  "@wordpress/api-fetch"
);

//# sourceMappingURL=lock-unlock.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/nonce.mjs
// packages/api-fetch/src/middlewares/nonce.ts
function createNonceMiddleware(nonce) {
  const middleware = (options, next) => {
    const { headers = {} } = options;
    for (const headerName in headers) {
      if (headerName.toLowerCase() === "x-wp-nonce" && headers[headerName] === middleware.nonce) {
        return next(options);
      }
    }
    return next({
      ...options,
      headers: {
        ...headers,
        "X-WP-Nonce": middleware.nonce
      }
    });
  };
  middleware.nonce = nonce;
  return middleware;
}
var nonce_default = createNonceMiddleware;

//# sourceMappingURL=nonce.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/namespace-endpoint.mjs
// packages/api-fetch/src/middlewares/namespace-endpoint.ts
var namespaceAndEndpointMiddleware = (options, next) => {
  let path = options.path;
  let namespaceTrimmed, endpointTrimmed;
  if (typeof options.namespace === "string" && typeof options.endpoint === "string") {
    namespaceTrimmed = options.namespace.replace(/^\/|\/$/g, "");
    endpointTrimmed = options.endpoint.replace(/^\//, "");
    if (endpointTrimmed) {
      path = namespaceTrimmed + "/" + endpointTrimmed;
    } else {
      path = namespaceTrimmed;
    }
  }
  delete options.namespace;
  delete options.endpoint;
  return next({
    ...options,
    path
  });
};
var namespace_endpoint_default = namespaceAndEndpointMiddleware;

//# sourceMappingURL=namespace-endpoint.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/root-url.mjs
// packages/api-fetch/src/middlewares/root-url.ts

var createRootURLMiddleware = (rootURL) => (options, next) => {
  return namespace_endpoint_default(options, (optionsWithPath) => {
    let url = optionsWithPath.url;
    let path = optionsWithPath.path;
    let apiRoot;
    if (typeof path === "string") {
      apiRoot = rootURL;
      if (-1 !== rootURL.indexOf("?")) {
        path = path.replace("?", "&");
      }
      path = path.replace(/^\//, "");
      if ("string" === typeof apiRoot && -1 !== apiRoot.indexOf("?")) {
        path = path.replace("?", "&");
      }
      url = apiRoot + path;
    }
    return next({
      ...optionsWithPath,
      url
    });
  });
};
var root_url_default = createRootURLMiddleware;

//# sourceMappingURL=root-url.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/normalize-path.mjs
var normalize_path = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/normalize-path.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs + 2 modules
var get_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs + 1 modules
var add_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/preloading.mjs
// packages/api-fetch/src/middlewares/preloading.ts

var ENABLE_MULTI_USE = /* @__PURE__ */ Symbol("preloadingEnableMultiUse");
var CLEAR = /* @__PURE__ */ Symbol("preloadingClear");
function createPreloadingMiddleware(preloadedData) {
  const { OPTIONS = {}, ...GET } = Object.fromEntries(
    Object.entries(preloadedData).map(([path, data]) => [
      (0,normalize_path/* normalizePath */.F)(path),
      data
    ])
  );
  const unusedGet = new Set(Object.keys(GET));
  const unusedOptions = new Set(Object.keys(OPTIONS));
  let multiUse = false;
  const middleware = (options, next) => {
    const { parse = true } = options;
    let rawPath = options.path;
    if (!rawPath && options.url) {
      const { rest_route: pathFromQuery, ...queryArgs } = (0,get_query_args/* getQueryArgs */.u)(
        options.url
      );
      if (typeof pathFromQuery === "string") {
        rawPath = (0,add_query_args/* addQueryArgs */.F)(pathFromQuery, queryArgs);
      }
    }
    if (typeof rawPath !== "string") {
      return next(options);
    }
    const method = options.method || "GET";
    const path = (0,normalize_path/* normalizePath */.F)(rawPath);
    if ("GET" === method && GET[path]) {
      const data = GET[path];
      if (!multiUse) {
        delete GET[path];
      }
      unusedGet.delete(path);
      return prepareResponse(data, !!parse);
    } else if ("OPTIONS" === method && OPTIONS[path]) {
      const data = OPTIONS[path];
      if (!multiUse) {
        delete OPTIONS[path];
      }
      unusedOptions.delete(path);
      return prepareResponse(data, !!parse);
    }
    return next(options);
  };
  middleware[ENABLE_MULTI_USE] = () => {
    multiUse = true;
  };
  middleware[CLEAR] = () => {
    const tags = [
      ...Array.from(unusedGet, (p) => `GET ${p}`),
      ...Array.from(unusedOptions, (p) => `OPTIONS ${p}`)
    ];
    if (tags.length) {
      console.warn(
        "[api-fetch][preload] Some preloads were never consumed:",
        tags
      );
    } else {
      console.log("[api-fetch][preload] All preloads consumed.");
    }
    unusedGet.clear();
    unusedOptions.clear();
    for (const key of Object.keys(GET)) {
      delete GET[key];
    }
    for (const key of Object.keys(OPTIONS)) {
      delete OPTIONS[key];
    }
  };
  return middleware;
}
function prepareResponse(responseData, parse) {
  if (parse) {
    return Promise.resolve(responseData.body);
  }
  try {
    return Promise.resolve(
      new window.Response(JSON.stringify(responseData.body), {
        status: 200,
        statusText: "OK",
        headers: responseData.headers
      })
    );
  } catch {
    Object.entries(
      responseData.headers
    ).forEach(([key, value]) => {
      if (key.toLowerCase() === "link") {
        responseData.headers[key] = value.replace(
          /<([^>]+)>/,
          (_, url) => `<${encodeURI(url)}>`
        );
      }
    });
    return Promise.resolve(
      parse ? responseData.body : new window.Response(JSON.stringify(responseData.body), {
        status: 200,
        statusText: "OK",
        headers: responseData.headers
      })
    );
  }
}
var preloading_default = createPreloadingMiddleware;

//# sourceMappingURL=preloading.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/fetch-all-middleware.mjs
// packages/api-fetch/src/middlewares/fetch-all-middleware.ts


var modifyQuery = ({ path, url, ...options }, queryArgs) => ({
  ...options,
  url: url && (0,add_query_args/* addQueryArgs */.F)(url, queryArgs),
  path: path && (0,add_query_args/* addQueryArgs */.F)(path, queryArgs)
});
var parseResponse = (response) => response.json ? response.json() : Promise.reject(response);
var parseLinkHeader = (linkHeader) => {
  if (!linkHeader) {
    return {};
  }
  const match = linkHeader.match(/<([^>]+)>; rel="next"/);
  return match ? {
    next: match[1]
  } : {};
};
var getNextPageUrl = (response) => {
  const { next } = parseLinkHeader(response.headers.get("link"));
  return next;
};
var requestContainsUnboundedQuery = (options) => {
  const pathIsUnbounded = !!options.path && options.path.indexOf("per_page=-1") !== -1;
  const urlIsUnbounded = !!options.url && options.url.indexOf("per_page=-1") !== -1;
  return pathIsUnbounded || urlIsUnbounded;
};
var fetchAllMiddleware = async (options, next) => {
  if (options.parse === false) {
    return next(options);
  }
  if (!requestContainsUnboundedQuery(options)) {
    return next(options);
  }
  const response = await index_default({
    ...modifyQuery(options, {
      per_page: 100
    }),
    // Ensure headers are returned for page 1.
    parse: false
  });
  const results = await parseResponse(response);
  if (!Array.isArray(results)) {
    return results;
  }
  let nextPage = getNextPageUrl(response);
  if (!nextPage) {
    return results;
  }
  let mergedResults = [].concat(results);
  while (nextPage) {
    const nextResponse = await index_default({
      ...options,
      // Ensure the URL for the next page is used instead of any provided path.
      path: void 0,
      url: nextPage,
      // Ensure we still get headers so we can identify the next page.
      parse: false
    });
    const nextResults = await parseResponse(nextResponse);
    mergedResults = mergedResults.concat(nextResults);
    nextPage = getNextPageUrl(nextResponse);
  }
  return mergedResults;
};
var fetch_all_middleware_default = fetchAllMiddleware;

//# sourceMappingURL=fetch-all-middleware.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/http-v1.mjs
// packages/api-fetch/src/middlewares/http-v1.ts
var OVERRIDE_METHODS = /* @__PURE__ */ new Set(["PATCH", "PUT", "DELETE"]);
var DEFAULT_METHOD = "GET";
var httpV1Middleware = (options, next) => {
  const { method = DEFAULT_METHOD } = options;
  if (OVERRIDE_METHODS.has(method.toUpperCase())) {
    options = {
      ...options,
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
        "X-HTTP-Method-Override": method
      },
      method: "POST"
    };
  }
  return next(options);
};
var http_v1_default = httpV1Middleware;

//# sourceMappingURL=http-v1.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/has-query-arg.mjs
var has_query_arg = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/has-query-arg.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/user-locale.mjs
// packages/api-fetch/src/middlewares/user-locale.ts

var userLocaleMiddleware = (options, next) => {
  if (typeof options.url === "string" && !(0,has_query_arg/* hasQueryArg */.d)(options.url, "_locale")) {
    options.url = (0,add_query_args/* addQueryArgs */.F)(options.url, { _locale: "user" });
  }
  if (typeof options.path === "string" && !(0,has_query_arg/* hasQueryArg */.d)(options.path, "_locale")) {
    options.path = (0,add_query_args/* addQueryArgs */.F)(options.path, { _locale: "user" });
  }
  return next(options);
};
var user_locale_default = userLocaleMiddleware;

//# sourceMappingURL=user-locale.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/utils/response.mjs
// packages/api-fetch/src/utils/response.ts

async function parseJsonAndNormalizeError(response) {
  try {
    return await response.json();
  } catch {
    throw {
      code: "invalid_json",
      message: (0,i18n_build_module.__)("The response is not a valid JSON response.")
    };
  }
}
async function parseResponseAndNormalizeError(response, shouldParseResponse = true) {
  if (!shouldParseResponse) {
    return response;
  }
  if (response.status === 204) {
    return null;
  }
  return await parseJsonAndNormalizeError(response);
}
async function parseAndThrowError(response, shouldParseResponse = true) {
  if (!shouldParseResponse) {
    throw response;
  }
  throw await parseJsonAndNormalizeError(response);
}

//# sourceMappingURL=response.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/media-upload.mjs
// packages/api-fetch/src/middlewares/media-upload.ts


function isMediaUploadRequest(options) {
  const isCreateMethod = !!options.method && options.method === "POST";
  const isMediaEndpoint = !!options.path && options.path.indexOf("/wp/v2/media") !== -1 || !!options.url && options.url.indexOf("/wp/v2/media") !== -1;
  return isMediaEndpoint && isCreateMethod;
}
var mediaUploadMiddleware = (options, next) => {
  if (!isMediaUploadRequest(options)) {
    return next(options);
  }
  let retries = 0;
  const maxRetries = 5;
  const postProcess = (attachmentId) => {
    retries++;
    return next({
      path: `/wp/v2/media/${attachmentId}/post-process`,
      method: "POST",
      data: { action: "create-image-subsizes" },
      parse: false
    }).catch(() => {
      if (retries < maxRetries) {
        return postProcess(attachmentId);
      }
      next({
        path: `/wp/v2/media/${attachmentId}?force=true`,
        method: "DELETE"
      });
      return Promise.reject();
    });
  };
  return next({ ...options, parse: false }).catch((response) => {
    if (!(response instanceof globalThis.Response)) {
      return Promise.reject(response);
    }
    const attachmentId = response.headers.get(
      "x-wp-upload-attachment-id"
    );
    if (response.status >= 500 && response.status < 600 && attachmentId) {
      return postProcess(attachmentId).catch(() => {
        if (options.parse !== false) {
          return Promise.reject({
            code: "post_process",
            message: (0,i18n_build_module.__)(
              "Media upload failed. If this is a photo or a large image, please scale it down and try again."
            )
          });
        }
        return Promise.reject(response);
      });
    }
    return parseAndThrowError(response, options.parse);
  }).then(
    (response) => parseResponseAndNormalizeError(response, options.parse)
  );
};
var media_upload_media_upload_default = mediaUploadMiddleware;

//# sourceMappingURL=media-upload.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs
var get_query_arg = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/remove-query-args.mjs
var remove_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/remove-query-args.mjs");
;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/middlewares/theme-preview.mjs
// packages/api-fetch/src/middlewares/theme-preview.ts

var createThemePreviewMiddleware = (themePath) => (options, next) => {
  if (typeof options.url === "string") {
    const wpThemePreview = (0,get_query_arg/* getQueryArg */.d)(
      options.url,
      "wp_theme_preview"
    );
    if (wpThemePreview === void 0) {
      options.url = (0,add_query_args/* addQueryArgs */.F)(options.url, {
        wp_theme_preview: themePath
      });
    } else if (wpThemePreview === "") {
      options.url = (0,remove_query_args/* removeQueryArgs */.m)(
        options.url,
        "wp_theme_preview"
      );
    }
  }
  if (typeof options.path === "string") {
    const wpThemePreview = (0,get_query_arg/* getQueryArg */.d)(
      options.path,
      "wp_theme_preview"
    );
    if (wpThemePreview === void 0) {
      options.path = (0,add_query_args/* addQueryArgs */.F)(options.path, {
        wp_theme_preview: themePath
      });
    } else if (wpThemePreview === "") {
      options.path = (0,remove_query_args/* removeQueryArgs */.m)(
        options.path,
        "wp_theme_preview"
      );
    }
  }
  return next(options);
};
var theme_preview_default = createThemePreviewMiddleware;

//# sourceMappingURL=theme-preview.mjs.map

;// ../../node_modules/.pnpm/@wordpress+api-fetch@7.48.1/node_modules/@wordpress/api-fetch/build-module/index.mjs
// packages/api-fetch/src/index.ts












var DEFAULT_HEADERS = {
  // The backend uses the Accept header as a condition for considering an
  // incoming request as a REST request.
  //
  // See: https://core.trac.wordpress.org/ticket/44534
  Accept: "application/json, */*;q=0.1"
};
var DEFAULT_OPTIONS = {
  credentials: "include"
};
var middlewares = [
  user_locale_default,
  namespace_endpoint_default,
  http_v1_default,
  fetch_all_middleware_default
];
function registerMiddleware(middleware) {
  middlewares.unshift(middleware);
}
function enablePreloadMultiUse() {
  for (const middleware of middlewares) {
    middleware[ENABLE_MULTI_USE]?.();
  }
}
function clearPreloadedData() {
  for (const middleware of middlewares) {
    middleware[CLEAR]?.();
  }
}
var defaultFetchHandler = (nextOptions) => {
  const { url, path, data, parse = true, ...remainingOptions } = nextOptions;
  let { body, headers } = nextOptions;
  headers = { ...DEFAULT_HEADERS, ...headers };
  if (data) {
    body = JSON.stringify(data);
    headers["Content-Type"] = "application/json";
  }
  const responsePromise = globalThis.fetch(
    // Fall back to explicitly passing `window.location` which is the behavior if `undefined` is passed.
    url || path || window.location.href,
    {
      ...DEFAULT_OPTIONS,
      ...remainingOptions,
      body,
      headers
    }
  );
  return responsePromise.then(
    (response) => {
      if (!response.ok) {
        return parseAndThrowError(response, parse);
      }
      return parseResponseAndNormalizeError(response, parse);
    },
    (err) => {
      if (err && err.name === "AbortError") {
        throw err;
      }
      if (!globalThis.navigator.onLine) {
        throw {
          code: "offline_error",
          message: (0,i18n_build_module.__)(
            "Unable to connect. Please check your Internet connection."
          )
        };
      }
      throw {
        code: "fetch_error",
        message: (0,i18n_build_module.__)(
          "Could not get a valid response from the server."
        )
      };
    }
  );
};
var fetchHandler = defaultFetchHandler;
function setFetchHandler(newFetchHandler) {
  fetchHandler = newFetchHandler;
}
var apiFetch = (options) => {
  const enhancedHandler = middlewares.reduceRight(
    (next, middleware) => {
      return (workingOptions) => middleware(workingOptions, next);
    },
    fetchHandler
  );
  return enhancedHandler(options).catch((error) => {
    if (error.code !== "rest_cookie_invalid_nonce") {
      return Promise.reject(error);
    }
    return globalThis.fetch(apiFetch.nonceEndpoint).then((response) => {
      if (!response.ok) {
        return Promise.reject(error);
      }
      return response.text();
    }).then((text) => {
      apiFetch.nonceMiddleware.nonce = text;
      return apiFetch(options);
    });
  });
};
apiFetch.use = registerMiddleware;
apiFetch.setFetchHandler = setFetchHandler;
apiFetch.privateApis = {};
lock(apiFetch.privateApis, {
  enablePreloadMultiUse,
  clearPreloadedData
});
apiFetch.createNonceMiddleware = nonce_default;
apiFetch.createPreloadingMiddleware = preloading_default;
apiFetch.createRootURLMiddleware = root_url_default;
apiFetch.fetchAllMiddleware = fetch_all_middleware_default;
apiFetch.mediaUploadMiddleware = media_upload_media_upload_default;
apiFetch.createThemePreviewMiddleware = theme_preview_default;
var index_default = apiFetch;

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/flatten-form-data.js
function isPlainObject(data) {
  return data !== null && typeof data === "object" && Object.getPrototypeOf(data) === Object.prototype;
}
function flattenFormData(formData, key, data) {
  if (isPlainObject(data)) {
    for (const [name, value] of Object.entries(data)) {
      flattenFormData(formData, `${key}[${name}]`, value);
    }
  } else if (data !== void 0) {
    formData.append(key, String(data));
  }
}

//# sourceMappingURL=flatten-form-data.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/transform-attachment.js
function transformAttachment(attachment) {
  const { alt_text, source_url, ...savedMediaProps } = attachment;
  return {
    ...savedMediaProps,
    alt: attachment.alt_text,
    caption: attachment.caption?.raw ?? "",
    title: attachment.title.raw,
    url: attachment.source_url,
    poster: attachment._embedded?.["wp:featuredmedia"]?.[0]?.source_url || void 0
  };
}

//# sourceMappingURL=transform-attachment.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/upload-to-server.js



async function uploadToServer(file, additionalData = {}, signal) {
  const data = new FormData();
  data.append("file", file, file.name || file.type.replace("/", "."));
  for (const [key, value] of Object.entries(additionalData)) {
    flattenFormData(
      data,
      key,
      value
    );
  }
  return transformAttachment(
    await index_default({
      // This allows the video block to directly get a video's poster image.
      path: "/wp/v2/media?_embed=wp:featuredmedia",
      body: data,
      method: "POST",
      signal
    })
  );
}

//# sourceMappingURL=upload-to-server.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/upload-error.js
class UploadError extends Error {
  code;
  file;
  constructor({ code, message, file, cause }) {
    super(message, { cause });
    Object.setPrototypeOf(this, new.target.prototype);
    this.code = code;
    this.file = file;
  }
}

//# sourceMappingURL=upload-error.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/validate-mime-type.js


function validateMimeType(file, allowedTypes) {
  if (!allowedTypes) {
    return;
  }
  const isAllowedType = allowedTypes.some((allowedType) => {
    if (allowedType.includes("/")) {
      return allowedType === file.type;
    }
    return file.type.startsWith(`${allowedType}/`);
  });
  if (file.type && !isAllowedType) {
    throw new UploadError({
      code: "MIME_TYPE_NOT_SUPPORTED",
      message: (0,build_module/* sprintf */.nv)(
        // translators: %s: file name.
        (0,build_module.__)("%s: Sorry, this file type is not supported here."),
        file.name
      ),
      file
    });
  }
}

//# sourceMappingURL=validate-mime-type.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/get-mime-types-array.js
function getMimeTypesArray(wpMimeTypesObject) {
  if (!wpMimeTypesObject) {
    return null;
  }
  return Object.entries(wpMimeTypesObject).flatMap(
    ([extensionsString, mime]) => {
      const [type] = mime.split("/");
      const extensions = extensionsString.split("|");
      return [
        mime,
        ...extensions.map(
          (extension) => `${type}/${extension}`
        )
      ];
    }
  );
}

//# sourceMappingURL=get-mime-types-array.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/validate-mime-type-for-user.js



function validateMimeTypeForUser(file, wpAllowedMimeTypes) {
  const allowedMimeTypesForUser = getMimeTypesArray(wpAllowedMimeTypes);
  if (!allowedMimeTypesForUser) {
    return;
  }
  const isAllowedMimeTypeForUser = allowedMimeTypesForUser.includes(
    file.type
  );
  if (file.type && !isAllowedMimeTypeForUser) {
    throw new UploadError({
      code: "MIME_TYPE_NOT_ALLOWED_FOR_USER",
      message: (0,build_module/* sprintf */.nv)(
        // translators: %s: file name.
        (0,build_module.__)(
          "%s: Sorry, you are not allowed to upload this file type."
        ),
        file.name
      ),
      file
    });
  }
}

//# sourceMappingURL=validate-mime-type-for-user.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/validate-file-size.js


function validateFileSize(file, maxUploadFileSize) {
  if (file.size <= 0) {
    throw new UploadError({
      code: "EMPTY_FILE",
      message: (0,build_module/* sprintf */.nv)(
        // translators: %s: file name.
        (0,build_module.__)("%s: This file is empty."),
        file.name
      ),
      file
    });
  }
  if (maxUploadFileSize && file.size > maxUploadFileSize) {
    throw new UploadError({
      code: "SIZE_ABOVE_LIMIT",
      message: (0,build_module/* sprintf */.nv)(
        // translators: %s: file name.
        (0,build_module.__)(
          "%s: This file exceeds the maximum upload size for this site."
        ),
        file.name
      ),
      file
    });
  }
}

//# sourceMappingURL=validate-file-size.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/upload-media.js







function uploadMedia({
  wpAllowedMimeTypes,
  allowedTypes,
  additionalData = {},
  filesList,
  maxUploadFileSize,
  onError,
  onFileChange,
  signal,
  multiple = true
}) {
  if (!multiple && filesList.length > 1) {
    onError?.(new Error((0,build_module.__)("Only one file can be used here.")));
    return;
  }
  const validFiles = [];
  const filesSet = [];
  const setAndUpdateFiles = (index, value) => {
    if (!window.__experimentalMediaProcessing) {
      if (filesSet[index]?.url) {
        revokeBlobURL(filesSet[index].url);
      }
    }
    filesSet[index] = value;
    onFileChange?.(
      filesSet.filter((attachment) => attachment !== null)
    );
  };
  for (const mediaFile of filesList) {
    try {
      validateMimeTypeForUser(mediaFile, wpAllowedMimeTypes);
    } catch (error) {
      onError?.(error);
      continue;
    }
    try {
      validateMimeType(mediaFile, allowedTypes);
    } catch (error) {
      onError?.(error);
      continue;
    }
    try {
      validateFileSize(mediaFile, maxUploadFileSize);
    } catch (error) {
      onError?.(error);
      continue;
    }
    validFiles.push(mediaFile);
    if (!window.__experimentalMediaProcessing) {
      filesSet.push({ url: createBlobURL(mediaFile) });
      onFileChange?.(filesSet);
    }
  }
  validFiles.map(async (file, index) => {
    try {
      const attachment = await uploadToServer(
        file,
        additionalData,
        signal
      );
      setAndUpdateFiles(index, attachment);
    } catch (error) {
      setAndUpdateFiles(index, null);
      let message;
      if (typeof error === "object" && error !== null && "message" in error) {
        message = typeof error.message === "string" ? error.message : String(error.message);
      } else {
        message = (0,build_module/* sprintf */.nv)(
          // translators: %s: file name
          (0,build_module.__)("Error while uploading file %s to the media library."),
          file.name
        );
      }
      onError?.(
        new UploadError({
          code: "GENERAL",
          message,
          file,
          cause: error instanceof Error ? error : void 0
        })
      );
    }
  });
}

//# sourceMappingURL=upload-media.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/sideload-to-server.js



async function sideloadToServer(file, attachmentId, additionalData = {}, signal) {
  const data = new FormData();
  data.append("file", file, file.name || file.type.replace("/", "."));
  for (const [key, value] of Object.entries(additionalData)) {
    flattenFormData(
      data,
      key,
      value
    );
  }
  return transformAttachment(
    await index_default({
      path: `/wp/v2/media/${attachmentId}/sideload`,
      body: data,
      method: "POST",
      signal
    })
  );
}

//# sourceMappingURL=sideload-to-server.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/utils/sideload-media.js



const noop = () => {
};
async function sideloadMedia({
  file,
  attachmentId,
  additionalData = {},
  signal,
  onFileChange,
  onError = noop
}) {
  try {
    const attachment = await sideloadToServer(
      file,
      attachmentId,
      additionalData,
      signal
    );
    onFileChange?.([attachment]);
  } catch (error) {
    let message;
    if (error instanceof Error) {
      message = error.message;
    } else {
      message = (0,build_module/* sprintf */.nv)(
        // translators: %s: file name
        (0,build_module.__)("Error while sideloading file %s to the server."),
        file.name
      );
    }
    onError(
      new UploadError({
        code: "GENERAL",
        message,
        file,
        cause: error instanceof Error ? error : void 0
      })
    );
  }
}

//# sourceMappingURL=sideload-media.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/lock-unlock.js

const { lock: lock_unlock_lock, unlock: lock_unlock_unlock } = (0,implementation/* __dangerousOptInToUnstableAPIsOnlyForCoreModules */.yf)(
  "I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.",
  "@wordpress/media-utils"
);

//# sourceMappingURL=lock-unlock.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/private-apis.js


const privateApis = {};
lock_unlock_lock(privateApis, {
  sideloadMedia: sideloadMedia
});

//# sourceMappingURL=private-apis.js.map

;// ../../node_modules/.pnpm/@wordpress+media-utils@5.33.1/node_modules/@wordpress/media-utils/build-module/index.js








//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   yf: () => (/* binding */ __dangerousOptInToUnstableAPIsOnlyForCoreModules)
/* harmony export */ });
/* unused harmony exports allowCoreModule, resetAllowedCoreModules */
// packages/private-apis/src/implementation.ts
var CORE_MODULES_USING_PRIVATE_APIS = [
  "@wordpress/admin-ui",
  "@wordpress/api-fetch",
  "@wordpress/block-directory",
  "@wordpress/block-editor",
  "@wordpress/block-library",
  "@wordpress/blocks",
  "@wordpress/boot",
  "@wordpress/commands",
  "@wordpress/compose",
  "@wordpress/connectors",
  "@wordpress/workflows",
  "@wordpress/components",
  "@wordpress/content-types",
  "@wordpress/core-commands",
  "@wordpress/core-data",
  "@wordpress/customize-widgets",
  "@wordpress/data",
  "@wordpress/edit-post",
  "@wordpress/edit-site",
  "@wordpress/edit-widgets",
  "@wordpress/editor",
  "@wordpress/font-list-route",
  "@wordpress/format-library",
  "@wordpress/patterns",
  "@wordpress/preferences",
  "@wordpress/reusable-blocks",
  "@wordpress/rich-text",
  "@wordpress/route",
  "@wordpress/router",
  "@wordpress/routes",
  "@wordpress/storybook",
  "@wordpress/sync",
  "@wordpress/theme",
  "@wordpress/dataviews",
  "@wordpress/fields",
  "@wordpress/lazy-editor",
  "@wordpress/media-editor",
  "@wordpress/media-utils",
  "@wordpress/upload-media",
  "@wordpress/global-styles-ui",
  "@wordpress/ui",
  "@wordpress/views"
];
var requiredConsent = "I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.";
var __dangerousOptInToUnstableAPIsOnlyForCoreModules = (consent, moduleName) => {
  if (!CORE_MODULES_USING_PRIVATE_APIS.includes(moduleName)) {
    throw new Error(
      `You tried to opt-in to unstable APIs as module "${moduleName}". This feature is only for JavaScript modules shipped with WordPress core. Please do not use it in plugins and themes as the unstable APIs will be removed without a warning. If you ignore this error and depend on unstable features, your product will inevitably break on one of the next WordPress releases.`
    );
  }
  if (consent !== requiredConsent) {
    throw new Error(
      `You tried to opt-in to unstable APIs without confirming you know the consequences. This feature is only for JavaScript modules shipped with WordPress core. Please do not use it in plugins and themes as the unstable APIs will removed without a warning. If you ignore this error and depend on unstable features, your product will inevitably break on the next WordPress release.`
    );
  }
  return {
    lock,
    unlock
  };
};
function lock(object, privateData) {
  if (!object) {
    throw new Error("Cannot lock an undefined object.");
  }
  const _object = object;
  if (!(__private in _object)) {
    _object[__private] = {};
  }
  lockedData.set(_object[__private], privateData);
}
function unlock(object) {
  if (!object) {
    throw new Error("Cannot unlock an undefined object.");
  }
  const _object = object;
  if (!(__private in _object)) {
    throw new Error(
      "Cannot unlock an object that was not locked before. "
    );
  }
  return lockedData.get(_object[__private]);
}
var lockedData = /* @__PURE__ */ new WeakMap();
var __private = /* @__PURE__ */ Symbol("Private API ID");
function allowCoreModule(name) {
  CORE_MODULES_USING_PRIVATE_APIS.push(name);
}
function resetAllowedCoreModules() {
  while (CORE_MODULES_USING_PRIVATE_APIS.length) {
    CORE_MODULES_USING_PRIVATE_APIS.pop();
  }
}

//# sourceMappingURL=implementation.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  F: () => (/* binding */ addQueryArgs)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs + 2 modules
var get_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs
var build_query_string = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs");
;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-fragment.mjs
// packages/url/src/get-fragment.ts
function getFragment(url) {
  const matches = /^\S+?(#[^\s\?]*)/.exec(url);
  if (matches) {
    return matches[1];
  }
}

//# sourceMappingURL=get-fragment.mjs.map

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/add-query-args.mjs
// packages/url/src/add-query-args.ts



function addQueryArgs(url = "", args) {
  if (!args || !Object.keys(args).length) {
    return url;
  }
  const fragment = getFragment(url) || "";
  let baseUrl = url.replace(fragment, "");
  const queryStringIndex = url.indexOf("?");
  if (queryStringIndex !== -1) {
    args = Object.assign((0,get_query_args/* getQueryArgs */.u)(url), args);
    baseUrl = baseUrl.substr(0, queryStringIndex);
  }
  return baseUrl + "?" + (0,build_query_string/* buildQueryString */.G)(args) + fragment;
}

//# sourceMappingURL=add-query-args.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   G: () => (/* binding */ buildQueryString)
/* harmony export */ });
// packages/url/src/build-query-string.ts
function buildQueryString(data) {
  let string = "";
  const stack = Object.entries(data);
  let pair;
  while (pair = stack.shift()) {
    let [key, value] = pair;
    const hasNestedData = Array.isArray(value) || value && value.constructor === Object;
    if (hasNestedData) {
      const valuePairs = Object.entries(value).reverse();
      for (const [member, memberValue] of valuePairs) {
        stack.unshift([`${key}[${member}]`, memberValue]);
      }
    } else if (value !== void 0) {
      if (value === null) {
        value = "";
      }
      string += "&" + [key, String(value)].map(encodeURIComponent).join("=");
    }
  }
  return string.substr(1);
}

//# sourceMappingURL=build-query-string.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   d: () => (/* binding */ getQueryArg)
/* harmony export */ });
/* harmony import */ var _get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
// packages/url/src/get-query-arg.ts

function getQueryArg(url, arg) {
  return (0,_get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__/* .getQueryArgs */ .u)(url)[arg];
}

//# sourceMappingURL=get-query-arg.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  u: () => (/* binding */ getQueryArgs)
});

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/safe-decode-uri-component.mjs
// packages/url/src/safe-decode-uri-component.ts
function safeDecodeURIComponent(uriComponent) {
  try {
    return decodeURIComponent(uriComponent);
  } catch {
    return uriComponent;
  }
}

//# sourceMappingURL=safe-decode-uri-component.mjs.map

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-string.mjs
// packages/url/src/get-query-string.ts
function getQueryString(url) {
  let query;
  try {
    query = new URL(url, "http://example.com").search.substring(1);
  } catch {
  }
  if (query) {
    return query;
  }
}

//# sourceMappingURL=get-query-string.mjs.map

;// ../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs
// packages/url/src/get-query-args.ts


function setPath(object, path, value) {
  const length = path.length;
  const lastIndex = length - 1;
  for (let i = 0; i < length; i++) {
    let key = path[i];
    if (!key && Array.isArray(object)) {
      key = object.length.toString();
    }
    key = ["__proto__", "constructor", "prototype"].includes(key) ? key.toUpperCase() : key;
    const isNextKeyArrayIndex = !isNaN(Number(path[i + 1]));
    object[key] = i === lastIndex ? (
      // If at end of path, assign the intended value.
      value
    ) : (
      // Otherwise, advance to the next object in the path, creating
      // it if it does not yet exist.
      object[key] || (isNextKeyArrayIndex ? [] : {})
    );
    if (Array.isArray(object[key]) && !isNextKeyArrayIndex) {
      object[key] = { ...object[key] };
    }
    object = object[key];
  }
}
function getQueryArgs(url) {
  return (getQueryString(url) || "").replace(/\+/g, "%20").split("&").reduce((accumulator, keyValue) => {
    const [key, value = ""] = keyValue.split("=").filter(Boolean).map(safeDecodeURIComponent);
    if (key) {
      const segments = key.replace(/\]/g, "").split("[");
      setPath(accumulator, segments, value);
    }
    return accumulator;
  }, /* @__PURE__ */ Object.create(null));
}

//# sourceMappingURL=get-query-args.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/has-query-arg.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   d: () => (/* binding */ hasQueryArg)
/* harmony export */ });
/* harmony import */ var _get_query_arg_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-arg.mjs");
// packages/url/src/has-query-arg.ts

function hasQueryArg(url, arg) {
  return (0,_get_query_arg_mjs__WEBPACK_IMPORTED_MODULE_0__/* .getQueryArg */ .d)(url, arg) !== void 0;
}

//# sourceMappingURL=has-query-arg.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/normalize-path.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   F: () => (/* binding */ normalizePath)
/* harmony export */ });
// packages/url/src/normalize-path.ts
function normalizePath(path) {
  const split = path.split("?");
  const query = split[1];
  const base = split[0];
  if (!query) {
    return base;
  }
  return base + "?" + query.split("&").map((entry) => entry.split("=")).map((pair) => pair.map(decodeURIComponent)).sort((a, b) => a[0].localeCompare(b[0])).map((pair) => pair.map(encodeURIComponent)).map((pair) => pair.join("=")).join("&");
}

//# sourceMappingURL=normalize-path.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/remove-query-args.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   m: () => (/* binding */ removeQueryArgs)
/* harmony export */ });
/* harmony import */ var _get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/get-query-args.mjs");
/* harmony import */ var _build_query_string_mjs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@4.48.1/node_modules/@wordpress/url/build-module/build-query-string.mjs");
// packages/url/src/remove-query-args.ts


function removeQueryArgs(url, ...args) {
  const fragment = url.replace(/^[^#]*/, "");
  url = url.replace(/#.*/, "");
  const queryStringIndex = url.indexOf("?");
  if (queryStringIndex === -1) {
    return url + fragment;
  }
  const query = (0,_get_query_args_mjs__WEBPACK_IMPORTED_MODULE_0__/* .getQueryArgs */ .u)(url);
  const baseURL = url.substr(0, queryStringIndex);
  args.forEach((arg) => delete query[arg]);
  const queryString = (0,_build_query_string_mjs__WEBPACK_IMPORTED_MODULE_1__/* .buildQueryString */ .G)(query);
  const updatedUrl = queryString ? baseURL + "?" + queryString : baseURL;
  return updatedUrl + fragment;
}

//# sourceMappingURL=remove-query-args.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/hoist-non-react-statics@3.3.2/node_modules/hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var reactIs = __webpack_require__("../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/index.js");

/**
 * Copyright 2015, Yahoo! Inc.
 * Copyrights licensed under the New BSD License. See the accompanying LICENSE file for terms.
 */
var REACT_STATICS = {
  childContextTypes: true,
  contextType: true,
  contextTypes: true,
  defaultProps: true,
  displayName: true,
  getDefaultProps: true,
  getDerivedStateFromError: true,
  getDerivedStateFromProps: true,
  mixins: true,
  propTypes: true,
  type: true
};
var KNOWN_STATICS = {
  name: true,
  length: true,
  prototype: true,
  caller: true,
  callee: true,
  arguments: true,
  arity: true
};
var FORWARD_REF_STATICS = {
  '$$typeof': true,
  render: true,
  defaultProps: true,
  displayName: true,
  propTypes: true
};
var MEMO_STATICS = {
  '$$typeof': true,
  compare: true,
  defaultProps: true,
  displayName: true,
  propTypes: true,
  type: true
};
var TYPE_STATICS = {};
TYPE_STATICS[reactIs.ForwardRef] = FORWARD_REF_STATICS;
TYPE_STATICS[reactIs.Memo] = MEMO_STATICS;

function getStatics(component) {
  // React v16.11 and below
  if (reactIs.isMemo(component)) {
    return MEMO_STATICS;
  } // React v16.12 and above


  return TYPE_STATICS[component['$$typeof']] || REACT_STATICS;
}

var defineProperty = Object.defineProperty;
var getOwnPropertyNames = Object.getOwnPropertyNames;
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
var getPrototypeOf = Object.getPrototypeOf;
var objectPrototype = Object.prototype;
function hoistNonReactStatics(targetComponent, sourceComponent, blacklist) {
  if (typeof sourceComponent !== 'string') {
    // don't hoist over string (html) components
    if (objectPrototype) {
      var inheritedComponent = getPrototypeOf(sourceComponent);

      if (inheritedComponent && inheritedComponent !== objectPrototype) {
        hoistNonReactStatics(targetComponent, inheritedComponent, blacklist);
      }
    }

    var keys = getOwnPropertyNames(sourceComponent);

    if (getOwnPropertySymbols) {
      keys = keys.concat(getOwnPropertySymbols(sourceComponent));
    }

    var targetStatics = getStatics(targetComponent);
    var sourceStatics = getStatics(sourceComponent);

    for (var i = 0; i < keys.length; ++i) {
      var key = keys[i];

      if (!KNOWN_STATICS[key] && !(blacklist && blacklist[key]) && !(sourceStatics && sourceStatics[key]) && !(targetStatics && targetStatics[key])) {
        var descriptor = getOwnPropertyDescriptor(sourceComponent, key);

        try {
          // Avoid failures from read-only properties
          defineProperty(targetComponent, key, descriptor);
        } catch (e) {}
      }
    }
  }

  return targetComponent;
}

module.exports = hoistNonReactStatics;


/***/ }),

/***/ "../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/cjs/react-is.production.min.js":
/***/ ((__unused_webpack_module, exports) => {

/** @license React v16.13.1
 * react-is.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var b="function"===typeof Symbol&&Symbol.for,c=b?Symbol.for("react.element"):60103,d=b?Symbol.for("react.portal"):60106,e=b?Symbol.for("react.fragment"):60107,f=b?Symbol.for("react.strict_mode"):60108,g=b?Symbol.for("react.profiler"):60114,h=b?Symbol.for("react.provider"):60109,k=b?Symbol.for("react.context"):60110,l=b?Symbol.for("react.async_mode"):60111,m=b?Symbol.for("react.concurrent_mode"):60111,n=b?Symbol.for("react.forward_ref"):60112,p=b?Symbol.for("react.suspense"):60113,q=b?
Symbol.for("react.suspense_list"):60120,r=b?Symbol.for("react.memo"):60115,t=b?Symbol.for("react.lazy"):60116,v=b?Symbol.for("react.block"):60121,w=b?Symbol.for("react.fundamental"):60117,x=b?Symbol.for("react.responder"):60118,y=b?Symbol.for("react.scope"):60119;
function z(a){if("object"===typeof a&&null!==a){var u=a.$$typeof;switch(u){case c:switch(a=a.type,a){case l:case m:case e:case g:case f:case p:return a;default:switch(a=a&&a.$$typeof,a){case k:case n:case t:case r:case h:return a;default:return u}}case d:return u}}}function A(a){return z(a)===m}exports.AsyncMode=l;exports.ConcurrentMode=m;exports.ContextConsumer=k;exports.ContextProvider=h;exports.Element=c;exports.ForwardRef=n;exports.Fragment=e;exports.Lazy=t;exports.Memo=r;exports.Portal=d;
exports.Profiler=g;exports.StrictMode=f;exports.Suspense=p;exports.isAsyncMode=function(a){return A(a)||z(a)===l};exports.isConcurrentMode=A;exports.isContextConsumer=function(a){return z(a)===k};exports.isContextProvider=function(a){return z(a)===h};exports.isElement=function(a){return"object"===typeof a&&null!==a&&a.$$typeof===c};exports.isForwardRef=function(a){return z(a)===n};exports.isFragment=function(a){return z(a)===e};exports.isLazy=function(a){return z(a)===t};
exports.isMemo=function(a){return z(a)===r};exports.isPortal=function(a){return z(a)===d};exports.isProfiler=function(a){return z(a)===g};exports.isStrictMode=function(a){return z(a)===f};exports.isSuspense=function(a){return z(a)===p};
exports.isValidElementType=function(a){return"string"===typeof a||"function"===typeof a||a===e||a===m||a===g||a===f||a===p||a===q||"object"===typeof a&&null!==a&&(a.$$typeof===t||a.$$typeof===r||a.$$typeof===h||a.$$typeof===k||a.$$typeof===n||a.$$typeof===w||a.$$typeof===x||a.$$typeof===y||a.$$typeof===v)};exports.typeOf=z;


/***/ }),

/***/ "../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/cjs/react-is.production.min.js");
} else {}


/***/ })

}]);