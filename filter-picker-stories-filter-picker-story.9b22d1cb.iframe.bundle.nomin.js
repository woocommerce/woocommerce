"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3942],{

/***/ "../../packages/js/components/src/filter-picker/stories/filter-picker.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/filter-picker/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const query = {
  meal: 'breakfast'
};
const config = {
  label: 'Meal',
  staticParams: [],
  param: 'meal',
  showFilters: () => true,
  filters: [{
    label: 'Breakfast',
    value: 'breakfast'
  }, {
    label: 'Lunch',
    value: 'lunch',
    subFilters: [{
      label: 'Meat',
      value: 'meat',
      path: ['lunch']
    }, {
      label: 'Vegan',
      value: 'vegan',
      path: ['lunch']
    }, {
      label: 'Pescatarian',
      value: 'fish',
      path: ['lunch'],
      subFilters: [{
        label: 'Snapper',
        value: 'snapper',
        path: ['lunch', 'fish']
      }, {
        label: 'Cod',
        value: 'cod',
        path: ['lunch', 'fish']
      },
      // Specify a custom component to render (Work in Progress)
      {
        label: 'Other',
        value: 'other_fish',
        path: ['lunch', 'fish'],
        component: 'OtherFish'
      }]
    }]
  }, {
    label: 'Dinner',
    value: 'dinner'
  }]
};
const Basic = ({
  path = new URL(document.location).searchParams.get('path')
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
    config: config,
    path: path,
    query: query
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/FilterPicker',
  component: ___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "({\n  path = new URL(document.location).searchParams.get('path')\n}) => {\n  return <FilterPicker config={config} path={path} query={query} />;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/animation-slider/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/TransitionGroup.js");
/* harmony import */ var react_transition_group__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react-transition-group@4.4._ea827a607bbb9ce48eba17f05126488f/node_modules/react-transition-group/esm/CSSTransition.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.
 * All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition
 */

class AnimationSlider extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  constructor() {
    super();
    this.state = {
      animate: null
    };
    this.container = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createRef)();
    this.onExited = this.onExited.bind(this);
  }
  onExited() {
    const {
      onExited
    } = this.props;
    if (onExited) {
      onExited(this.container.current);
    }
  }
  render() {
    const {
      children,
      animationKey,
      animate
    } = this.props;
    const containerClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-slide-animation', animate && `animate-${animate}`);
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: containerClasses,
      ref: this.container,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_transition_group__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A, {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_transition_group__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
          timeout: 200,
          classNames: "slide",
          ...this.props,
          onExited: this.onExited,
          children: status => children({
            status
          })
        }, animationKey)
      })
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AnimationSlider);
;
AnimationSlider.__docgenInfo = {
  "description": "This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.\nAll other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition",
  "methods": [{
    "name": "onExited",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "AnimationSlider",
  "props": {
    "children": {
      "description": "A function returning rendered content with argument status, reflecting `CSSTransition` status.",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "animationKey": {
      "description": "A unique identifier for each slideable page.",
      "type": {
        "name": "any"
      },
      "required": true
    },
    "animate": {
      "description": "null, 'left', 'right', to designate which direction to slide on a change.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "null",
          "computed": false
        }, {
          "value": "'left'",
          "computed": false
        }, {
          "value": "'right'",
          "computed": false
        }]
      },
      "required": false
    },
    "onExited": {
      "description": "A function to be executed after a transition is complete, passing the containing ref as the argument.",
      "type": {
        "name": "func"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/dropdown-button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */






/**
 * A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays
 * single or multiple lines rendered as `<span/>` elements.
 *
 * @param {Object} props Props passed to component.
 * @return {Object} -
 */

const DropdownButton = props => {
  const {
    labels,
    isOpen,
    ...otherProps
  } = props;
  const buttonClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-dropdown-button', {
    'is-open': isOpen,
    'is-multi-line': labels.length > 1
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
    className: buttonClasses,
    "aria-expanded": isOpen,
    ...otherProps,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: "woocommerce-dropdown-button__labels",
      children: labels.map((label, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        children: (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__/* .decodeEntities */ .S)(label)
      }, i))
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DropdownButton);
;
DropdownButton.__docgenInfo = {
  "description": "A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays\nsingle or multiple lines rendered as `<span/>` elements.\n\n@param {Object} props Props passed to component.\n@return {Object} -",
  "methods": [],
  "displayName": "DropdownButton",
  "props": {
    "labels": {
      "description": "An array of elements to be rendered as the content of the button.",
      "type": {
        "name": "array"
      },
      "required": true
    },
    "isOpen": {
      "description": "Boolean describing if the dropdown in open or not.",
      "type": {
        "name": "bool"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/filter-picker/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export DEFAULT_FILTER */
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dropdown/index.js");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom@4.33.1/node_modules/@wordpress/dom/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var _animation_slider__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__("../../packages/js/components/src/animation-slider/index.js");
/* harmony import */ var _dropdown_button__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__("../../packages/js/components/src/dropdown-button/index.js");
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../packages/js/components/src/search/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */











/**
 * Internal dependencies
 */




const DEFAULT_FILTER = 'all';

/**
 * Get the props to forward to the `Search` component of a filter.
 * The deprecated `type`, `autocompleter`, and `labels.placeholder` settings take precedence over `settings.searchProps`,
 * so extensions that change them on core filters keep working.
 *
 * @param {Object} settings The `settings` of a filter with a `component`.
 * @return {Object} Props for the `Search` component.
 */
function getSearchProps(settings) {
  const {
    type,
    autocompleter,
    labels = {},
    searchProps = {}
  } = settings;
  const legacySearchProps = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.omitBy)({
    type,
    autocompleter,
    placeholder: labels.placeholder
  }, lodash__WEBPACK_IMPORTED_MODULE_1__.isUndefined);
  if (Object.keys(legacySearchProps).length > 0) {
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)('Passing `type`, `autocompleter`, or `labels.placeholder` in the `settings` of a FilterPicker filter', {
      since: '14.2.0',
      version: '15.0.0',
      alternative: '`settings.searchProps`',
      link: 'https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/components/src/filter-picker/README.md',
      plugin: '@woocommerce/components'
    });
  }
  return {
    ...searchProps,
    ...legacySearchProps
  };
}

/**
 * Modify a url query parameter via a dropdown selection of configurable options.
 * This component manipulates the `filter` query parameter.
 */
class FilterPicker extends _wordpress_element__WEBPACK_IMPORTED_MODULE_5__.Component {
  constructor(props) {
    super(props);
    const selectedFilter = this.getFilter();
    this.state = {
      nav: selectedFilter.path || [],
      animate: null,
      selectedTag: null
    };
    this.selectSubFilter = this.selectSubFilter.bind(this);
    this.getVisibleFilters = this.getVisibleFilters.bind(this);
    this.updateSelectedTag = this.updateSelectedTag.bind(this);
    this.onTagChange = this.onTagChange.bind(this);
    this.onContentMount = this.onContentMount.bind(this);
    this.goBack = this.goBack.bind(this);
    if (selectedFilter.settings && selectedFilter.settings.getLabels) {
      const {
        query
      } = this.props;
      const {
        param: filterParam,
        getLabels
      } = selectedFilter.settings;
      getLabels(query[filterParam], query).then(this.updateSelectedTag);
    }
  }
  componentDidUpdate({
    query: prevQuery
  }) {
    const {
      query: nextQuery,
      config
    } = this.props;
    if (prevQuery[config.param] !== nextQuery[[config.param]]) {
      const selectedFilter = this.getFilter();
      if (selectedFilter && selectedFilter.component === 'Search') {
        /* eslint-disable react/no-did-update-set-state */
        this.setState({
          nav: selectedFilter.path || []
        });
        /* eslint-enable react/no-did-update-set-state */
        const {
          param: filterParam,
          getLabels
        } = selectedFilter.settings;
        getLabels(nextQuery[filterParam], nextQuery).then(this.updateSelectedTag);
      }
    }
  }
  updateSelectedTag(tags) {
    this.setState({
      selectedTag: tags[0]
    });
  }
  getFilter(value) {
    const {
      config,
      query
    } = this.props;
    const allFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .flattenFilters */ .SI)(config.filters);
    value = value || query[config.param] || config.defaultValue || DEFAULT_FILTER;
    return (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(allFilters, {
      value
    }) || {};
  }
  getButtonLabel(selectedFilter) {
    if (selectedFilter.component === 'Search') {
      const {
        selectedTag
      } = this.state;
      return [selectedTag && selectedTag.label, (0,lodash__WEBPACK_IMPORTED_MODULE_1__.get)(selectedFilter, 'settings.labels.button')];
    }
    return selectedFilter ? [selectedFilter.label] : [];
  }
  getVisibleFilters(filters, nav) {
    if (nav.length === 0) {
      return filters;
    }
    const value = nav[0];
    const nextFilters = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(filters, {
      value
    });
    return this.getVisibleFilters(nextFilters && nextFilters.subFilters, nav.slice(1));
  }
  selectSubFilter(value) {
    // Add the value onto the nav path
    this.setState(prevState => ({
      nav: [...prevState.nav, value],
      animate: 'left'
    }));
  }
  goBack() {
    // Remove the last item from the nav path
    this.setState(prevState => ({
      nav: prevState.nav.slice(0, -1),
      animate: 'right'
    }));
  }
  getAllFilterParams() {
    const {
      config
    } = this.props;
    const params = [];
    const getParam = filters => {
      filters.forEach(filter => {
        if (filter.settings && !params.includes(filter.settings.param)) {
          params.push(filter.settings.param);
        }
        if (filter.subFilters) {
          getParam(filter.subFilters);
        }
      });
    };
    getParam(config.filters);
    return params;
  }
  update(value, additionalQueries = {}) {
    const {
      path,
      query,
      config,
      onFilterSelect,
      advancedFilters
    } = this.props;
    let update = {
      [config.param]: (config.defaultValue || DEFAULT_FILTER) === value ? undefined : value,
      ...additionalQueries
    };
    // Keep any url parameters as designated by the config
    config.staticParams.forEach(param => {
      update[param] = query[param];
    });

    // Remove all of this filter's params not associated with the update while
    // leaving any other params from any other filter an extension may have added.
    this.getAllFilterParams().forEach(param => {
      if (!update[param]) {
        // Explicitly give value of undefined so it can be removed from the query.
        update[param] = undefined;
      }
    });

    // If the main filter is being set to anything but advanced, remove any advancedFilters.
    if (config.param === 'filter' && value !== 'advanced') {
      const resetAdvancedFilters = (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .getQueryFromActiveFilters */ .Sz)([], query, advancedFilters.filters || {});
      update = {
        ...update,
        ...resetAdvancedFilters
      };
    }
    (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_2__/* .updateQueryString */ .Ze)(update, path, query);
    onFilterSelect(update);
  }
  onTagChange(filter, onClose, config, tags) {
    const tag = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.last)(tags);
    const {
      value,
      settings
    } = filter;
    const {
      param: filterParam
    } = settings;
    if (tag) {
      this.update(value, {
        [filterParam]: tag.key
      });
      onClose();
    } else {
      this.update(config.defaultValue || DEFAULT_FILTER);
    }
    this.updateSelectedTag([tag]);
  }
  renderButton(filter, onClose, config) {
    if (filter.component) {
      const searchProps = getSearchProps(filter.settings);
      const persistedFilter = this.getFilter();
      const selectedTag = persistedFilter.value === filter.value ? this.state.selectedTag : null;
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_search__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
        ...searchProps,
        className: (0,clsx__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)('woocommerce-filters-filter__search', searchProps.className),
        selected: selectedTag ? [selectedTag] : [],
        onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partial)(this.onTagChange, filter, onClose, config),
        inlineTags: true,
        staticResults: true
      });
    }
    const selectFilter = event => {
      onClose(event);
      this.update(filter.value, filter.query || {});
      this.setState({
        selectedTag: null
      });
    };
    const selectSubFilter = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.partial)(this.selectSubFilter, filter.value);
    const selectedFilter = this.getFilter();
    const buttonIsSelected = selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_1__.includes)(selectedFilter.path, filter.value);
    const onClick = event => {
      if (buttonIsSelected) {
        // Don't navigate if the button is already selected.
        onClose(event);
        return;
      }
      if (filter.subFilters) {
        selectSubFilter(event);
        return;
      }
      selectFilter(event);
    };
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .Ay, {
      className: "woocommerce-filters-filter__button",
      onClick: onClick,
      children: filter.label
    });
  }
  onContentMount(content) {
    const {
      nav
    } = this.state;
    const parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
    const focusableIndex = parentFilter ? 1 : 0;
    const focusable = _wordpress_dom__WEBPACK_IMPORTED_MODULE_9__/* .focus */ .XC.tabbable.find(content)[focusableIndex];
    setTimeout(() => {
      focusable.focus();
    }, 0);
  }
  render() {
    const {
      config
    } = this.props;
    const {
      nav,
      animate
    } = this.state;
    const visibleFilters = this.getVisibleFilters(config.filters, nav);
    const parentFilter = nav.length ? this.getFilter(nav[nav.length - 1]) : false;
    const selectedFilter = this.getFilter();
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      className: "woocommerce-filters-filter",
      children: [config.label && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "woocommerce-filters-label",
        children: config.label
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__/* ["default"] */ .A, {
        contentClassName: "woocommerce-filters-filter__content",
        popoverProps: {
          placement: 'bottom'
        },
        expandOnMobile: true,
        headerTitle: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('filter report to show:', 'woocommerce'),
        renderToggle: ({
          isOpen,
          onToggle
        }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_dropdown_button__WEBPACK_IMPORTED_MODULE_11__/* ["default"] */ .A, {
          onClick: onToggle,
          isOpen: isOpen,
          labels: this.getButtonLabel(selectedFilter)
        }),
        renderContent: ({
          onClose
        }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_animation_slider__WEBPACK_IMPORTED_MODULE_12__/* ["default"] */ .A, {
          animationKey: nav,
          animate: animate,
          onExited: this.onContentMount,
          children: () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("ul", {
            className: "woocommerce-filters-filter__content-list",
            children: [parentFilter && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
              className: "woocommerce-filters-filter__content-list-item",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .Ay, {
                className: "woocommerce-filters-filter__button",
                onClick: this.goBack,
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_13__/* ["default"] */ .A, {
                  icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_14__/* ["default"] */ .A
                }), parentFilter.label]
              })
            }), visibleFilters.map(filter => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
              className: (0,clsx__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)('woocommerce-filters-filter__content-list-item', {
                'is-selected': selectedFilter.value === filter.value || selectedFilter.path && (0,lodash__WEBPACK_IMPORTED_MODULE_1__.includes)(selectedFilter.path, filter.value)
              }),
              children: this.renderButton(filter, onClose, config)
            }, filter.value))]
          })
        })
      })]
    });
  }
}
FilterPicker.defaultProps = {
  query: {},
  onFilterSelect: () => {}
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FilterPicker);
;
FilterPicker.__docgenInfo = {
  "description": "Modify a url query parameter via a dropdown selection of configurable options.\nThis component manipulates the `filter` query parameter.",
  "methods": [{
    "name": "updateSelectedTag",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "tags",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getFilter",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getButtonLabel",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "selectedFilter",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getVisibleFilters",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filters",
      "optional": false,
      "type": null
    }, {
      "name": "nav",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "selectSubFilter",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "goBack",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getAllFilterParams",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "update",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "value",
      "optional": false,
      "type": null
    }, {
      "name": "additionalQueries",
      "optional": true,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onTagChange",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filter",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }, {
      "name": "config",
      "optional": false,
      "type": null
    }, {
      "name": "tags",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "renderButton",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "filter",
      "optional": false,
      "type": null
    }, {
      "name": "onClose",
      "optional": false,
      "type": null
    }, {
      "name": "config",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "onContentMount",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "content",
      "optional": false,
      "type": null
    }],
    "returns": null
  }],
  "displayName": "FilterPicker",
  "props": {
    "query": {
      "defaultValue": {
        "value": "{}",
        "computed": false
      },
      "description": "The query string represented in object form.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "onFilterSelect": {
      "defaultValue": {
        "value": "() => {}",
        "computed": false
      },
      "description": "Function to be called after filter selection.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "config": {
      "description": "An array of filters and subFilters to construct the menu.",
      "type": {
        "name": "shape",
        "value": {
          "label": {
            "name": "string",
            "description": "A label above the filter selector.",
            "required": false
          },
          "staticParams": {
            "name": "array",
            "description": "Url parameters to persist when selecting a new filter.",
            "required": true
          },
          "param": {
            "name": "string",
            "description": "The url parameter this filter will modify.",
            "required": true
          },
          "defaultValue": {
            "name": "string",
            "description": "The default parameter value to use instead of 'all'.",
            "required": false
          },
          "showFilters": {
            "name": "func",
            "description": "Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.",
            "required": true
          },
          "filters": {
            "name": "arrayOf",
            "value": {
              "name": "shape",
              "value": {
                "chartMode": {
                  "name": "enum",
                  "value": [{
                    "value": "'item-comparison'",
                    "computed": false
                  }, {
                    "value": "'time-comparison'",
                    "computed": false
                  }],
                  "description": "The chart display mode to use for charts displayed when this filter is active.",
                  "required": false
                },
                "component": {
                  "name": "string",
                  "description": "A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.",
                  "required": false
                },
                "label": {
                  "name": "string",
                  "description": "The label for this filter. Optional only for custom component filters.",
                  "required": false
                },
                "path": {
                  "name": "string",
                  "description": "An array representing the \"path\" to this filter, if nested.",
                  "required": false
                },
                "settings": {
                  "name": "shape",
                  "value": {
                    "param": {
                      "name": "string",
                      "description": "The url parameter the selected value is stored in.",
                      "required": false
                    },
                    "getLabels": {
                      "name": "func",
                      "description": "Function used to fetch labels for the selected values, returns a Promise.",
                      "required": false
                    },
                    "labels": {
                      "name": "shape",
                      "value": {
                        "button": {
                          "name": "string",
                          "required": false
                        },
                        "placeholder": {
                          "name": "string",
                          "description": "@deprecated Use `searchProps.placeholder` instead.",
                          "required": false
                        }
                      },
                      "description": "Object of localized labels. `button` is shown in the dropdown button next to the selected value.",
                      "required": false
                    },
                    "searchProps": {
                      "name": "shape",
                      "value": {
                        "type": {
                          "name": "string",
                          "required": false
                        },
                        "autocompleter": {
                          "name": "object",
                          "required": false
                        },
                        "placeholder": {
                          "name": "string",
                          "required": false
                        }
                      },
                      "description": "Props forwarded to the `Search` component, except `selected`, `onChange`, `inlineTags`, and `staticResults`.",
                      "required": false
                    },
                    "type": {
                      "name": "string",
                      "description": "@deprecated Use `searchProps.type` instead.",
                      "required": false
                    },
                    "autocompleter": {
                      "name": "object",
                      "description": "@deprecated Use `searchProps.autocompleter` instead.",
                      "required": false
                    }
                  },
                  "description": "Settings for a filter with a `component`, or for a comparison filter.",
                  "required": false
                },
                "subFilters": {
                  "name": "array",
                  "description": "An array of more filter objects that act as \"children\" to this item.\nThis set of filters is shown if the parent filter is clicked.",
                  "required": false
                },
                "value": {
                  "name": "string",
                  "description": "The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.",
                  "required": true
                }
              }
            },
            "description": "An array of filter a user can select.",
            "required": false
          }
        }
      },
      "required": true
    },
    "path": {
      "description": "The `path` parameter supplied by React-Router.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "advancedFilters": {
      "description": "Advanced Filters configuration object.",
      "type": {
        "name": "object"
      },
      "required": false
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/search/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/components/src/search/search.tsx");
/**
 * Internal dependencies
 */


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_search__WEBPACK_IMPORTED_MODULE_0__/* .Search */ .v);
try {
    // @ts-ignore
    Search.displayName = "Search";
    // @ts-ignore
    Search.__docgenInfo = { "description": "A search box which autocompletes results while typing, allowing for the user to select an existing object\n(product, order, customer, etc). Currently only products are supported.", "displayName": "Search", "props": { "allowFreeTextSearch": { "defaultValue": { value: "false" }, "description": "Render additional options in the autocompleter to allow free text entering depending on the type.", "name": "allowFreeTextSearch", "required": false, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "Class name applied to parent div.", "name": "className", "required": false, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Function called when selected results change, passed result list.", "name": "onChange", "required": false, "type": { "name": "((value: Option | OptionCompletionValue[]) => unknown)" } }, "type": { "defaultValue": null, "description": "The object type to be used in searching.", "name": "type", "required": true, "type": { "name": "enum", "value": [{ "value": "\"custom\"" }, { "value": "\"countries\"" }, { "value": "\"attributes\"" }, { "value": "\"categories\"" }, { "value": "\"coupons\"" }, { "value": "\"customerNames\"" }, { "value": "\"customers\"" }, { "value": "\"downloadIps\"" }, { "value": "\"emails\"" }, { "value": "\"orders\"" }, { "value": "\"products\"" }, { "value": "\"registeredCustomers\"" }, { "value": "\"taxes\"" }, { "value": "\"usernames\"" }, { "value": "\"variableProducts\"" }, { "value": "\"variations\"" }] } }, "autocompleter": { "defaultValue": null, "description": "The custom autocompleter to be used in searching when type is 'custom'", "name": "autocompleter", "required": false, "type": { "name": "AutoCompleter" } }, "placeholder": { "defaultValue": null, "description": "A placeholder for the search input.", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": { value: "[]" }, "description": "An array of objects describing selected values or optionally a string for a single value.\nIf the label of the selected value is omitted, the Tag of that value will not\nbe rendered inside the search box.", "name": "selected", "required": false, "type": { "name": "string | { key: string; label: string; }[]" } }, "inlineTags": { "defaultValue": { value: "false" }, "description": "Render tags inside input, otherwise render below input.", "name": "inlineTags", "required": false, "type": { "name": "boolean" } }, "showClearButton": { "defaultValue": { value: "false" }, "description": "Render a 'Clear' button next to the input box to remove its contents.", "name": "showClearButton", "required": false, "type": { "name": "boolean" } }, "staticResults": { "defaultValue": { value: "false" }, "description": "Render results list positioned statically instead of absolutely.", "name": "staticResults", "required": false, "type": { "name": "boolean" } }, "disabled": { "defaultValue": { value: "false" }, "description": "Whether the control is disabled or not.", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "true" }, "description": "Allow multiple option selections.", "name": "multiple", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/search/index.tsx#Search"] = { docgenInfo: Search.__docgenInfo, name: "Search", path: "../../packages/js/components/src/search/index.tsx#Search" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);