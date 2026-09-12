(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5750],{

/***/ "../../packages/js/components/src/chart/stories/chart.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Default: () => (/* binding */ Default),
  "default": () => (/* binding */ chart_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-format@1.4.5/node_modules/d3-format/src/defaultLocale.js + 8 modules
var defaultLocale = __webpack_require__("../../node_modules/.pnpm/d3-format@1.4.5/node_modules/d3-format/src/defaultLocale.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/line-graph.js
var line_graph = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/line-graph.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/stats-alt.js
var stats_alt = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/stats-alt.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js + 4 modules
var select_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js + 4 modules
var menu = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/navigable-container/menu.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-scale-chromatic@1.5.0/node_modules/d3-scale-chromatic/src/sequential-multi/viridis.js + 1 modules
var viridis = __webpack_require__("../../node_modules/.pnpm/d3-scale-chromatic@1.5.0/node_modules/d3-scale-chromatic/src/sequential-multi/viridis.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js
var memoize_one_esm = __webpack_require__("../../node_modules/.pnpm/memoize-one@6.0.0/node_modules/memoize-one/dist/memoize-one.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js + 29 modules
var viewport_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/sanitize/src/index.ts + 3 modules
var src = __webpack_require__("../../packages/js/sanitize/src/index.ts");
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 4 modules
var navigation_src = __webpack_require__("../../packages/js/navigation/src/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/chart/placeholder.js
/**
 * External dependencies
 */




/**
 * `ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.
 */

class ChartPlaceholder extends react.Component {
  render() {
    const {
      height
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      "aria-hidden": "true",
      className: "woocommerce-chart-placeholder",
      style: {
        height
      },
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {})
    });
  }
}
ChartPlaceholder.defaultProps = {
  height: 0
};
/* harmony default export */ const placeholder = (ChartPlaceholder);
;
ChartPlaceholder.__docgenInfo = {
  "description": "`ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.",
  "methods": [],
  "displayName": "ChartPlaceholder",
  "props": {
    "height": {
      "defaultValue": {
        "value": "0",
        "computed": false
      },
      "description": "",
      "type": {
        "name": "number"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../packages/js/components/src/section/header.tsx
var header = __webpack_require__("../../packages/js/components/src/section/header.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/section/section.tsx
var section = __webpack_require__("../../packages/js/components/src/section/section.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js + 1 modules
var with_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-time-format@2.3.0/node_modules/d3-time-format/src/defaultLocale.js + 4 modules
var src_defaultLocale = __webpack_require__("../../node_modules/.pnpm/d3-time-format@2.3.0/node_modules/d3-time-format/src/defaultLocale.js");
;// ../../packages/js/components/src/chart/d3chart/utils/index.js
/**
 * External dependencies
 */




/**
 * Allows an overriding formatter or defaults to d3Format or d3TimeFormat
 *
 * @param {string|Function} format    - either a format string for the D3 formatters or an overriding formatting method
 * @param {Function}        formatter - default d3Format or another formatting method, which accepts the string `format`
 * @return {Function} to be used to format an input given the format and formatter
 */
const getFormatter = (format, formatter = defaultLocale/* format */.GP) => typeof format === 'function' ? format : formatter(format);

/**
 * Returns an array of unique keys contained in the data.
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Array} Array of unique keys.
 */
const getUniqueKeys = data => {
  const keys = new Set(data.reduce((acc, curr) => acc.concat(Object.keys(curr)), []));
  return [...keys].filter(key => key !== 'date');
};

/**
 * Describes `getOrderedKeys`
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Array} Array of unique category keys ordered by cumulative total value
 */
const getOrderedKeys = data => {
  const keys = getUniqueKeys(data);
  return keys.map(key => ({
    key,
    focus: true,
    total: data.reduce((a, c) => a + c[key].value, 0),
    visible: true
  })).sort((a, b) => b.total - a.total);
};

/**
 * Describes `getUniqueDates`
 *
 * @param {Array}  data       - the chart component's `data` prop.
 * @param {string} dateParser - D3 time format
 * @return {Array} an array of unique date values sorted from earliest to latest
 */
const getUniqueDates = (data, dateParser) => {
  const parseDate = (0,src_defaultLocale/* utcParse */.GY)(dateParser);
  const dates = new Set(data.map(d => d.date));
  return [...dates].sort((a, b) => parseDate(a) - parseDate(b));
};

/**
 * Check whether data is empty.
 *
 * @param {Array}  data      - the chart component's `data` prop.
 * @param {number} baseValue - base value to test data values against.
 * @return {boolean} `false` if there was at least one data value different than
 * the baseValue.
 */
const isDataEmpty = (data, baseValue = 0) => {
  for (let i = 0; i < data.length; i++) {
    for (const [key, item] of Object.entries(data[i])) {
      if (key !== 'date' && !(0,lodash.isNil)(item.value) && item.value !== baseValue) {
        return false;
      }
    }
  }
  return true;
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.33.1/node_modules/@wordpress/hooks/build-module/index.js + 10 modules
var hooks_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.33.1/node_modules/@wordpress/hooks/build-module/index.js");
;// ../../packages/js/components/src/chart/constants.js
// This is the max number of items that can be selected/shown on a chart at one time.
// If this number changes, the color scale also needs to be adjusted.
const selectionLimit = 10;
const colorScales = [[], [0.5], [0.333, 0.667], [0.2, 0.5, 0.8], [0.12, 0.375, 0.625, 0.88], [0, 0.25, 0.5, 0.75, 1], [0, 0.2, 0.4, 0.6, 0.8, 1], [0, 0.16, 0.32, 0.48, 0.64, 0.8, 1], [0, 0.14, 0.28, 0.42, 0.56, 0.7, 0.84, 1], [0, 0.12, 0.24, 0.36, 0.48, 0.6, 0.72, 0.84, 1], [0, 0.11, 0.22, 0.33, 0.44, 0.55, 0.66, 0.77, 0.88, 1]];
;// ../../packages/js/components/src/chart/d3chart/utils/color.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const getColor = (orderedKeys, colorScheme) => key => {
  const len = orderedKeys.length > selectionLimit ? selectionLimit : orderedKeys.length;
  const idx = (0,lodash.findIndex)(orderedKeys, d => d.key === key);

  /**
   * Color to be used for a chart item.
   *
   * @filter woocommerce_admin_chart_item_color
   * @example
   * addFilter(
   * 	'woocommerce_admin_chart_item_color',
   * 	'example',
   * ( idx ) => {
   * 	const colorScales = [
   *	  "#0A2F51",
   *	  "#0E4D64",
   *	  "#137177",
   *	  "#188977",
   *	];
   * 	return colorScales[ idx ] || false;
   * });
   *
   */
  const color = (0,hooks_build_module.applyFilters)('woocommerce_admin_chart_item_color', idx, key, orderedKeys);
  if (color && color.toString().startsWith('#')) {
    return color;
  }
  const keyValue = idx <= selectionLimit - 1 ? colorScales[len][idx] : 0;
  return colorScheme(keyValue);
};
;// ../../packages/js/components/src/chart/d3chart/legend.js
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */




/**
 * A legend specifically designed for the WooCommerce admin charts.
 */

class D3Legend extends react.Component {
  constructor() {
    super();
    this.listRef = (0,react.createRef)();
    this.state = {
      isScrollable: false
    };
  }
  componentDidMount() {
    this.updateListScroll();
    window.addEventListener('resize', this.updateListScroll);
  }
  componentWillUnmount() {
    window.removeEventListener('resize', this.updateListScroll);
  }
  updateListScroll() {
    if (!this || !this.listRef) {
      return;
    }
    const list = this.listRef.current;
    const scrolledToEnd = list.scrollHeight - list.scrollTop <= list.offsetHeight;
    this.setState({
      isScrollable: !scrolledToEnd
    });
  }
  render() {
    const {
      colorScheme,
      data,
      handleLegendHover,
      handleLegendToggle,
      interactive,
      legendDirection,
      legendValueFormat,
      instanceId,
      totalLabel
    } = this.props;
    const {
      isScrollable
    } = this.state;
    const visibleData = data.filter(key => key.visible);
    const numberOfRowsVisible = visibleData.length;
    const showTotalLabel = legendDirection === 'column' && data.length > selectionLimit && totalLabel;
    const keys = data.length > selectionLimit ? visibleData : data;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('woocommerce-legend', `woocommerce-legend__direction-${legendDirection}`, {
        'has-total': showTotalLabel,
        'is-scrollable': isScrollable
      }, this.props.className),
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("ul", {
        className: "woocommerce-legend__list",
        ref: this.listRef,
        onScroll: showTotalLabel ? this.updateListScroll : null,
        children: data.map(row => /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
          className: (0,clsx/* default */.A)('woocommerce-legend__item', {
            'woocommerce-legend__item-checked': row.visible
          }),
          id: `woocommerce-legend-${instanceId}__item__${row.key}`,
          onMouseEnter: handleLegendHover,
          onMouseLeave: handleLegendHover,
          onBlur: handleLegendHover,
          onFocus: handleLegendHover,
          children: /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
            role: "checkbox",
            "aria-checked": row.visible ? 'true' : 'false',
            onClick: handleLegendToggle,
            id: `woocommerce-legend-${instanceId}__item-button__${row.key}`,
            disabled: row.visible && numberOfRowsVisible <= 1 || !row.visible && numberOfRowsVisible >= selectionLimit || !interactive,
            title: numberOfRowsVisible >= selectionLimit ? (0,build_module/* sprintf */.nv)(/* translators: %d: number of items selected */
            (0,build_module.__)('You may select up to %d items.', 'woocommerce'), selectionLimit) : '',
            children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
              className: "woocommerce-legend__item-container",
              children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                className: (0,clsx/* default */.A)('woocommerce-legend__item-checkmark', {
                  'woocommerce-legend__item-checkmark-checked': row.visible
                }),
                style: row.visible ? {
                  color: getColor(keys, colorScheme)(row.key)
                } : null
              }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                className: "woocommerce-legend__item-title",
                children: row.label
              }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                className: "woocommerce-legend__item-total",
                children: getFormatter(legendValueFormat)(row.total)
              })]
            })
          })
        }, row.key))
      }), showTotalLabel && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-legend__total",
        children: totalLabel
      })]
    });
  }
}
D3Legend.defaultProps = {
  interactive: true,
  legendDirection: 'row',
  legendValueFormat: ','
};
/* harmony default export */ const d3chart_legend = ((0,with_instance_id/* default */.A)(D3Legend));
;
D3Legend.__docgenInfo = {
  "description": "A legend specifically designed for the WooCommerce admin charts.",
  "methods": [{
    "name": "updateListScroll",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "D3Legend",
  "props": {
    "interactive": {
      "defaultValue": {
        "value": "true",
        "computed": false
      },
      "description": "Determines whether or not you can click on the legend",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "legendDirection": {
      "defaultValue": {
        "value": "'row'",
        "computed": false
      },
      "description": "Display legend items as a `row` or `column` inside a flex-box.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'row'",
          "computed": false
        }, {
          "value": "'column'",
          "computed": false
        }]
      },
      "required": false
    },
    "legendValueFormat": {
      "defaultValue": {
        "value": "','",
        "computed": false
      },
      "description": "A number formatting string or function to format the value displayed in the legend.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
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
    "colorScheme": {
      "description": "A chromatic color function to be passed down to d3.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "data": {
      "description": "An array of `orderedKeys`.",
      "type": {
        "name": "array"
      },
      "required": true
    },
    "handleLegendToggle": {
      "description": "Handles `onClick` event.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "handleLegendHover": {
      "description": "Handles `onMouseEnter`/`onMouseLeave` events.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "totalLabel": {
      "description": "Label to describe the legend items. It will be displayed in the legend of\ncomparison charts when there are many.",
      "type": {
        "name": "string"
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/select.js + 40 modules
var src_select = __webpack_require__("../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/select.js");
;// ../../packages/js/components/src/chart/d3chart/d3base/index.js
/**
 * External dependencies
 */






/**
 * Provides foundation to use D3 within React.
 *
 * React is responsible for determining when a chart should be updated (e.g. whenever data changes or the browser is
 * resized), while D3 is responsible for the actual rendering of the chart (which is performed via DOM operations that
 * happen outside of React's control).
 *
 * This component makes use of new lifecycle methods that come with React 16.3. Thus, while this component (i.e. the
 * container of the chart) is rendered during the 'render phase' the chart itself is only rendered during the 'commit
 * phase' (i.e. in 'componentDidMount' and 'componentDidUpdate' methods).
 */

class D3Base extends react.Component {
  constructor(props) {
    super(props);
    this.chartRef = (0,react.createRef)();
  }
  componentDidMount() {
    this.drawUpdatedChart();
  }
  shouldComponentUpdate(nextProps) {
    return this.props.className !== nextProps.className || !(0,lodash.isEqual)(this.props.data, nextProps.data) || !(0,lodash.isEqual)(this.props.orderedKeys, nextProps.orderedKeys) || this.props.drawChart !== nextProps.drawChart || this.props.height !== nextProps.height || this.props.chartType !== nextProps.chartType || this.props.width !== nextProps.width;
  }
  componentDidUpdate() {
    this.drawUpdatedChart();
  }
  componentWillUnmount() {
    this.deleteChart();
  }
  delayedScroll() {
    const {
      tooltip
    } = this.props;
    return (0,lodash.throttle)(() => {
      // eslint-disable-next-line no-unused-expressions
      tooltip && tooltip.hide();
    }, 300);
  }
  deleteChart() {
    (0,src_select/* default */.A)(this.chartRef.current).selectAll('svg').remove();
  }

  /**
   * Renders the chart, or triggers a rendering by updating the list of params.
   */
  drawUpdatedChart() {
    const {
      drawChart
    } = this.props;
    const svg = this.getContainer();
    drawChart(svg);
  }
  getContainer() {
    const {
      className,
      height,
      width
    } = this.props;
    this.deleteChart();
    const svg = (0,src_select/* default */.A)(this.chartRef.current).append('svg').attr('viewBox', `0 0 ${width} ${height}`).attr('height', height).attr('width', width).attr('preserveAspectRatio', 'xMidYMid meet');
    if (className) {
      svg.attr('class', `${className}__viewbox`);
    }
    return svg.append('g');
  }
  render() {
    const {
      className
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: (0,clsx/* default */.A)('d3-base', className),
      ref: this.chartRef,
      onScroll: this.delayedScroll()
    });
  }
}
;
D3Base.__docgenInfo = {
  "description": "Provides foundation to use D3 within React.\n\nReact is responsible for determining when a chart should be updated (e.g. whenever data changes or the browser is\nresized), while D3 is responsible for the actual rendering of the chart (which is performed via DOM operations that\nhappen outside of React's control).\n\nThis component makes use of new lifecycle methods that come with React 16.3. Thus, while this component (i.e. the\ncontainer of the chart) is rendered during the 'render phase' the chart itself is only rendered during the 'commit\nphase' (i.e. in 'componentDidMount' and 'componentDidUpdate' methods).",
  "methods": [{
    "name": "delayedScroll",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "deleteChart",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "drawUpdatedChart",
    "docblock": "Renders the chart, or triggers a rendering by updating the list of params.",
    "modifiers": [],
    "params": [],
    "returns": null,
    "description": "Renders the chart, or triggers a rendering by updating the list of params."
  }, {
    "name": "getContainer",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "D3Base",
  "props": {
    "className": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "data": {
      "description": "",
      "type": {
        "name": "array"
      },
      "required": false
    },
    "orderedKeys": {
      "description": "",
      "type": {
        "name": "array"
      },
      "required": false
    },
    "tooltip": {
      "description": "",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "chartType": {
      "description": "",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-scale@2.2.2/node_modules/d3-scale/src/index.js + 58 modules
var d3_scale_src = __webpack_require__("../../node_modules/.pnpm/d3-scale@2.2.2/node_modules/d3-scale/src/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js
var moment = __webpack_require__("../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment);
;// ../../packages/js/components/src/chart/d3chart/utils/scales.js
/**
 * External dependencies
 */



/**
 * Describes getXScale
 *
 * @param {Array}   uniqueDates - from `getUniqueDates`
 * @param {number}  width       - calculated width of the charting space
 * @param {boolean} compact     - whether the chart must be compact (without padding
                                between days)
 * @return {Function} a D3 scale of the dates
 */
const getXScale = (uniqueDates, width, compact = false) => (0,d3_scale_src/* scaleBand */.WH)().domain(uniqueDates).range([0, width]).paddingInner(compact ? 0 : 0.1);

/**
 * Describes getXGroupScale
 *
 * @param {Array}    orderedKeys - from `getOrderedKeys`
 * @param {Function} xScale      - from `getXScale`
 * @param {boolean}  compact     - whether the chart must be compact (without padding
                                 between days)
 * @return {Function} a D3 scale for each category within the xScale range
 */
const getXGroupScale = (orderedKeys, xScale, compact = false) => (0,d3_scale_src/* scaleBand */.WH)().domain(orderedKeys.filter(d => d.visible).map(d => d.key)).rangeRound([0, xScale.bandwidth()]).padding(compact ? 0 : 0.07);

/**
 * Describes getXLineScale
 *
 * @param {Array}  uniqueDates - from `getUniqueDates`
 * @param {number} width       - calculated width of the charting space
 * @return {Function} a D3 scaletime for each date
 */
const getXLineScale = (uniqueDates, width) => (0,d3_scale_src/* scaleTime */.w7)().domain([moment_default()(uniqueDates[0], 'YYYY-MM-DD HH:mm').toDate(), moment_default()(uniqueDates[uniqueDates.length - 1], 'YYYY-MM-DD HH:mm').toDate()]).rangeRound([0, width]);
const getYValueLimits = data => {
  let maxYValue = Number.NEGATIVE_INFINITY;
  let minYValue = Number.POSITIVE_INFINITY;
  data.forEach(d => {
    for (const [key, item] of Object.entries(d)) {
      if (key !== 'date' && Number.isFinite(item.value) && item.value > maxYValue) {
        maxYValue = item.value;
      }
      if (key !== 'date' && Number.isFinite(item.value) && item.value < minYValue) {
        minYValue = item.value;
      }
    }
  });
  return {
    upper: maxYValue,
    lower: minYValue
  };
};
const calculateStep = (minValue, maxValue) => {
  if (!Number.isFinite(minValue) || !Number.isFinite(maxValue)) {
    return 1;
  }
  if (maxValue === 0 && minValue === 0) {
    return 1 / 3;
  }
  const maxAbsValue = Math.max(-minValue, maxValue);
  const maxLimit = 4 / 3 * maxAbsValue;
  const pow3Y =
  // eslint-disable-next-line no-bitwise
  Math.pow(10, (Math.log(maxLimit) * Math.LOG10E + 1 | 0) - 2) * 3;
  const step = Math.ceil(maxLimit / pow3Y) * pow3Y / 3;
  if (maxValue < 1 && minValue > -1) {
    return Math.round(step * 4) / 4;
  }
  return Math.ceil(step);
};

/**
 * Returns the lower and upper limits of the Y scale and the calculated step to use in the axis, rounding
 * them to the nearest thousand, ten-thousand, million etc. In case it is a decimal number, ceils it.
 *
 * @param {Array} data - The chart component's `data` prop.
 * @return {Object} Object containing the `lower` and `upper` limits and a `step` value.
 */
const getYScaleLimits = data => {
  const {
    lower: minValue,
    upper: maxValue
  } = getYValueLimits(data);
  const step = calculateStep(minValue, maxValue);
  const limits = {
    lower: 0,
    upper: 0,
    step
  };
  if (Number.isFinite(minValue) || minValue < 0) {
    limits.lower = Math.floor(minValue / step) * step;
    if (limits.lower === minValue && minValue !== 0) {
      limits.lower -= step;
    }
  }
  if (Number.isFinite(maxValue) || maxValue > 0) {
    limits.upper = Math.ceil(maxValue / step) * step;
    if (limits.upper === maxValue && maxValue !== 0) {
      limits.upper += step;
    }
  }
  return limits;
};

/**
 * Describes getYScale
 *
 * @param {number} height - calculated height of the charting space
 * @param {number} yMin   - minimum y value
 * @param {number} yMax   - maximum y value
 * @return {Function} the D3 linear scale from 0 to the value from `getYMax`
 */
const getYScale = (height, yMin, yMax) => (0,d3_scale_src/* scaleLinear */.m4)().domain([Math.min(yMin, 0), yMax === 0 && yMin === 0 ? 1 : Math.max(yMax, 0)]).rangeRound([height, 0]);
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-axis@1.0.12/node_modules/d3-axis/src/index.js + 3 modules
var d3_axis_src = __webpack_require__("../../node_modules/.pnpm/d3-axis@1.0.12/node_modules/d3-axis/src/index.js");
;// ../../packages/js/components/src/chart/d3chart/utils/breakpoints.js
const smallBreak = 783;
const wideBreak = 1365;
;// ../../packages/js/components/src/chart/d3chart/utils/axis-x.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const dayTicksThreshold = 63;
const weekTicksThreshold = 9;
const mediumBreak = 1130;
const smallPoints = 7;
const mediumPoints = 12;
const largePoints = 16;
const mostPoints = 31;

/**
 * Calculate the maximum number of ticks allowed in the x-axis based on the width and mode of the chart
 *
 * @param {number} width - calculated page width
 * @param {string} mode  - item-comparison or time-comparison
 * @return {number} number of x-axis ticks based on width and chart mode
 */
const calculateMaxXTicks = (width, mode) => {
  if (width < smallBreak) {
    return smallPoints;
  } else if (width >= smallBreak && width <= mediumBreak) {
    return mediumPoints;
  } else if (width > mediumBreak && width <= wideBreak) {
    if (mode === 'time-comparison') {
      return largePoints;
    } else if (mode === 'item-comparison') {
      return mediumPoints;
    }
  } else if (width > wideBreak) {
    if (mode === 'time-comparison') {
      return mostPoints;
    } else if (mode === 'item-comparison') {
      return largePoints;
    }
  }
  return largePoints;
};

/**
 * Filter out irrelevant dates so only the first date of each month is kept.
 *
 * @param {Array} dates - string dates.
 * @return {Array} Filtered dates.
 */
const getFirstDatePerMonth = dates => {
  return dates.filter((date, i) => i === 0 || moment_default()(date).toDate().getMonth() !== moment_default()(dates[i - 1]).toDate().getMonth());
};

/**
 * Given an array of dates, returns true if the first and last one belong to the same day.
 *
 * @param {Array} dates - an array of dates
 * @return {boolean} whether the first and last date are different hours from the same date.
 */
const areDatesInTheSameDay = dates => {
  const firstDate = moment_default()(dates[0]).toDate();
  const lastDate = moment_default()(dates[dates.length - 1]).toDate();
  return firstDate.getDate() === lastDate.getDate() && firstDate.getMonth() === lastDate.getMonth() && firstDate.getFullYear() === lastDate.getFullYear();
};

/**
 * Describes `smallestFactor`
 *
 * @param {number} inputNum - any double or integer
 * @return {number} smallest factor of num
 */
const getFactors = inputNum => {
  const numFactors = [];
  for (let i = 1; i <= Math.floor(Math.sqrt(inputNum)); i++) {
    if (inputNum % i === 0) {
      numFactors.push(i);
      // eslint-disable-next-line no-unused-expressions
      inputNum / i !== i && numFactors.push(inputNum / i);
    }
  }
  numFactors.sort((x, y) => x - y); // numeric sort

  return numFactors;
};

/**
 * Calculates the increment factor between ticks so there aren't more than maxTicks.
 *
 * @param {Array}  uniqueDates - all the unique dates from the input data for the chart
 * @param {number} maxTicks    - maximum number of ticks that can be displayed in the x-axis
 * @return {number} x-axis ticks increment factor
 */
const calculateXTicksIncrementFactor = (uniqueDates, maxTicks) => {
  let factors = [];
  let i = 1;
  // First we get all the factors of the length of the uniqueDates array
  // if the number is a prime number or near prime (with 3 factors) then we
  // step down by 1 integer and try again.
  while (factors.length <= 3) {
    factors = getFactors(uniqueDates.length - i);
    i += 1;
  }
  return factors.find(f => uniqueDates.length / f < maxTicks);
};

/**
 * Get x-axis ticks given the unique dates and the increment factor.
 *
 * @param {Array}  uniqueDates     - all the unique dates from the input data for the chart
 * @param {number} incrementFactor - increment factor for the visible ticks.
 * @return {Array} Ticks for the x-axis.
 */
const getXTicksFromIncrementFactor = (uniqueDates, incrementFactor) => {
  const ticks = [];
  for (let idx = 0; idx < uniqueDates.length; idx = idx + incrementFactor) {
    ticks.push(uniqueDates[idx]);
  }

  // If the first date is missing from the ticks array, add it back in.
  if (ticks[0] !== uniqueDates[0]) {
    ticks.unshift(uniqueDates[0]);
  }
  return ticks;
};

/**
 * Returns ticks for the x-axis.
 *
 * @param {Array}  uniqueDates - all the unique dates from the input data for the chart
 * @param {number} width       - calculated page width
 * @param {string} mode        - item-comparison or time-comparison
 * @param {string} interval    - string of the interval used in the graph (hour, day, week...)
 * @return {number} number of x-axis ticks based on width and chart mode
 */
const getXTicks = (uniqueDates, width, mode, interval) => {
  const maxTicks = calculateMaxXTicks(width, mode);
  if (uniqueDates.length >= dayTicksThreshold && interval === 'day' || uniqueDates.length >= weekTicksThreshold && interval === 'week') {
    uniqueDates = getFirstDatePerMonth(uniqueDates);
  }
  if (uniqueDates.length <= maxTicks || interval === 'hour' && areDatesInTheSameDay(uniqueDates) && width > smallBreak) {
    return uniqueDates;
  }
  const incrementFactor = calculateXTicksIncrementFactor(uniqueDates, maxTicks);
  return getXTicksFromIncrementFactor(uniqueDates, incrementFactor);
};

/**
 * Compares 2 strings and returns a list of words that are unique from s2
 *
 * @param {string}        s1        - base string to compare against
 * @param {string}        s2        - string to compare against the base string
 * @param {string|Object} splitChar - character or RegExp to use to deliminate words
 * @return {Array} of unique words that appear in s2 but not in s1, the base string
 */
const compareStrings = (s1, s2, splitChar = new RegExp([' |,'], 'g')) => {
  const string1 = s1.split(splitChar);
  const string2 = s2.split(splitChar);
  const diff = [];
  const long = s1.length > s2.length ? string1 : string2;
  for (let x = 0; x < long.length; x++) {
    // eslint-disable-next-line no-unused-expressions
    string1[x] !== string2[x] && diff.push(string2[x]);
  }
  return diff;
};
const removeDuplicateDates = (d, i, ticks, formatter) => {
  const monthDate = moment_default()(d).toDate();
  let prevMonth = i !== 0 ? ticks[i - 1] : ticks[i];
  prevMonth = prevMonth instanceof Date ? prevMonth : moment_default()(prevMonth).toDate();
  return i === 0 ? formatter(monthDate) : compareStrings(formatter(prevMonth), formatter(monthDate)).join(' ');
};
const drawXAxis = (node, params, scales, formats) => {
  const height = scales.yScale.range()[0];
  let ticks = getXTicks(params.uniqueDates, scales.xScale.range()[1], params.mode, params.interval);
  if (params.chartType === 'line') {
    ticks = ticks.map(d => moment_default()(d).toDate());
  }
  node.append('g').attr('class', 'axis').attr('aria-hidden', 'true').attr('transform', `translate(0, ${height})`).call((0,d3_axis_src/* axisBottom */.l7)(scales.xScale).tickValues(ticks).tickFormat((d, i) => params.interval === 'hour' ? formats.xFormat(d instanceof Date ? d : moment_default()(d).toDate()) : removeDuplicateDates(d, i, ticks, formats.xFormat)));
  node.append('g').attr('class', 'axis axis-month').attr('aria-hidden', 'true').attr('transform', `translate(0, ${height + 14})`).call((0,d3_axis_src/* axisBottom */.l7)(scales.xScale).tickValues(ticks).tickFormat((d, i) => removeDuplicateDates(d, i, ticks, formats.x2Format)));
  node.append('g').attr('class', 'pipes').attr('transform', `translate(0, ${height})`).call((0,d3_axis_src/* axisBottom */.l7)(scales.xScale).tickValues(ticks).tickSize(5).tickFormat(''));
};
;// ../../packages/js/components/src/chart/d3chart/utils/axis-y.js
/**
 * External dependencies
 */

const calculateYGridValues = (numberOfTicks, limit, roundValues) => {
  const grids = [];
  for (let i = 0; i < numberOfTicks; i++) {
    const val = (i + 1) / numberOfTicks * limit;
    const rVal = roundValues ? Math.round(val) : val;
    if (grids[grids.length - 1] !== rVal) {
      grids.push(rVal);
    }
  }
  return grids;
};
const getNegativeYGrids = (yMin, step) => {
  if (yMin >= 0) {
    return [];
  }
  const numberOfTicks = Math.ceil(-yMin / step);
  return calculateYGridValues(numberOfTicks, yMin, yMin < -1);
};
const getPositiveYGrids = (yMax, step) => {
  if (yMax <= 0) {
    return [];
  }
  const numberOfTicks = Math.ceil(yMax / step);
  return calculateYGridValues(numberOfTicks, yMax, yMax > 1);
};
const getYGrids = (yMin, yMax, step) => {
  return [0, ...getNegativeYGrids(yMin, step), ...getPositiveYGrids(yMax, step)];
};
const drawYAxis = (node, scales, formats, margin, isRTL) => {
  const yGrids = getYGrids(scales.yScale.domain()[0], scales.yScale.domain()[1], scales.step);
  const width = scales.xScale.range()[1];
  const xPosition = isRTL ? width + margin.left + margin.right / 2 - 15 : -margin.left / 2 - 15;
  const withPositiveValuesClass = scales.yMin >= 0 || scales.yMax > 0 ? ' with-positive-ticks' : '';
  node.append('g').attr('class', 'grid' + withPositiveValuesClass).attr('transform', `translate(-${margin.left}, 0)`).call((0,d3_axis_src/* axisLeft */.V4)(scales.yScale).tickValues(yGrids).tickSize(-width - margin.left - margin.right).tickFormat(''));
  node.append('g').attr('class', 'axis y-axis').attr('aria-hidden', 'true').attr('transform', 'translate(' + xPosition + ', 12)').attr('text-anchor', 'start').call((0,d3_axis_src/* axisLeft */.V4)(scales.yScale).tickValues(scales.yMax === 0 && scales.yMin === 0 ? [yGrids[0]] : yGrids).tickFormat(d => {
    if (d > -1 && d < 1 && formats.yBelow1Format) {
      return formats.yBelow1Format(d);
    }
    return formats.yFormat(d);
  }));
};
;// ../../packages/js/components/src/chart/d3chart/utils/axis.js
/**
 * Internal dependencies
 */


const drawAxis = (node, params, scales, formats, margin, isRTL) => {
  drawXAxis(node, params, scales, formats);
  drawYAxis(node, scales, formats, margin, isRTL);
  node.selectAll('.domain').remove();
  node.selectAll('.axis .tick line').remove();
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/selection/on.js
var on = __webpack_require__("../../node_modules/.pnpm/d3-selection@1.4.2/node_modules/d3-selection/src/selection/on.js");
;// ../../packages/js/components/src/chart/d3chart/utils/bar-chart.js
/**
 * External dependencies
 */



const drawBars = (node, data, params, scales, formats, tooltip) => {
  const height = scales.yScale.range()[0];
  const barGroup = node.append('g').attr('class', 'bars').selectAll('g').data(data).enter().append('g').attr('transform', d => `translate(${scales.xScale(d.date)}, 0)`).attr('class', 'bargroup').attr('role', 'region').attr('aria-label', d => params.mode === 'item-comparison' ? formats.screenReaderFormat(d.date instanceof Date ? d.date : moment_default()(d.date).toDate()) : null);
  barGroup.append('rect').attr('class', 'barfocus').attr('x', 0).attr('y', 0).attr('width', scales.xGroupScale.range()[1]).attr('height', height).attr('opacity', '0').on('mouseover', (d, i, nodes) => {
    tooltip.show(data.find(e => e.date === d.date), on/* event */.f0.target, nodes[i].parentNode);
  }).on('mouseout', () => tooltip.hide());
  const basePosition = scales.yScale(0);
  barGroup.selectAll('.bar').data(d => params.visibleKeys.map(row => ({
    key: row.key,
    focus: row.focus,
    value: (0,lodash.get)(d, [row.key, 'value'], 0),
    label: row.label,
    visible: row.visible,
    date: d.date
  }))).enter().append('rect').attr('class', 'bar').attr('x', d => scales.xGroupScale(d.key)).attr('y', d => Math.min(basePosition, scales.yScale(d.value))).attr('width', scales.xGroupScale.bandwidth()).attr('height', d => Math.abs(basePosition - scales.yScale(d.value))).attr('fill', d => params.getColor(d.key)).attr('pointer-events', 'none').attr('tabindex', '0').attr('aria-label', d => {
    let label = d.label || d.key;
    if (params.mode === 'time-comparison') {
      const dayData = data.find(e => e.date === d.date);
      label = formats.screenReaderFormat(moment_default()(dayData[d.key].labelDate).toDate());
    }
    return `${label} ${tooltip.valueFormat(d.value)}`;
  }).style('opacity', d => {
    const opacity = d.focus ? 1 : 0.1;
    return d.visible ? opacity : 0;
  }).on('focus', (d, i, nodes) => {
    const targetNode = d.value > 0 ? on/* event */.f0.target : on/* event */.f0.target.parentNode;
    tooltip.show(data.find(e => e.date === d.date), targetNode, nodes[i].parentNode);
  }).on('blur', () => tooltip.hide());
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/d3-shape@1.3.7/node_modules/d3-shape/src/line.js + 4 modules
var line = __webpack_require__("../../node_modules/.pnpm/d3-shape@1.3.7/node_modules/d3-shape/src/line.js");
;// ../../packages/js/components/src/chart/d3chart/utils/line-chart.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


/**
 * Describes getDateSpaces
 *
 * @param {Array}    data        - The chart component's `data` prop.
 * @param {Array}    uniqueDates - from `getUniqueDates`
 * @param {Array}    visibleKeys - visible keys from the input data for the chart
 * @param {number}   width       - calculated width of the charting space
 * @param {Function} xScale      - from `getXLineScale`
 * @return {Array} that includes the date, start (x position) and width to mode the mouseover rectangles
 */
const getDateSpaces = (data, uniqueDates, visibleKeys, width, xScale) => {
  const reversedKeys = visibleKeys.slice().reverse();
  return uniqueDates.map((d, i) => {
    const datapoints = (0,lodash.first)(data.filter(item => item.date === d));
    const xNow = xScale(moment_default()(d).toDate());
    const xPrev = i >= 1 ? xScale(moment_default()(uniqueDates[i - 1]).toDate()) : xScale(moment_default()(uniqueDates[0]).toDate());
    const xNext = i < uniqueDates.length - 1 ? xScale(moment_default()(uniqueDates[i + 1]).toDate()) : xScale(moment_default()(uniqueDates[uniqueDates.length - 1]).toDate());
    let xWidth = i === 0 ? xNext - xNow : xNow - xPrev;
    const xStart = i === 0 ? 0 : xNow - xWidth / 2;
    xWidth = i === 0 || i === uniqueDates.length - 1 ? xWidth / 2 : xWidth;
    return {
      date: d,
      start: uniqueDates.length > 1 ? xStart : 0,
      width: uniqueDates.length > 1 ? xWidth : width,
      values: reversedKeys.map(({
        key
      }) => {
        const datapoint = datapoints[key];
        if (!datapoint) {
          return null;
        }
        return {
          key,
          value: datapoint.value,
          date: d
        };
      }).filter(Boolean)
    };
  });
};

/**
 * Describes getLine
 *
 * @param {Function} xScale - from `getXLineScale`.
 * @param {Function} yScale - from `getYScale`.
 * @return {Function} the D3 line function for plotting all category values
 */
const getLine = (xScale, yScale) => (0,line/* default */.A)().x(d => xScale(moment_default()(d.date).toDate())).y(d => yScale(d.value));

/**
 * Describes `getLineData`
 *
 * @param {Array} data        - The chart component's `data` prop.
 * @param {Array} orderedKeys - from `getOrderedKeys`.
 * @return {Array} an array objects with a category `key` and an array of `values` with `date` and `value` properties
 */
const getLineData = (data, orderedKeys) => orderedKeys.map(row => ({
  key: row.key,
  focus: row.focus,
  visible: row.visible,
  label: row.label,
  values: data.map(d => ({
    // To have the same X-axis scale, we use the same dates for all lines.
    date: d.date,
    // To have actual date for the screenReader, we need to use label date.
    labelDate: d[row.key].labelDate,
    focus: row.focus,
    value: (0,lodash.get)(d, [row.key, 'value'], 0),
    visible: row.visible
  }))
}));
const drawLines = (node, data, params, scales, formats, tooltip) => {
  const height = scales.yScale.range()[0];
  const width = scales.xScale.range()[1];
  const line = getLine(scales.xScale, scales.yScale);
  const lineData = getLineData(data, params.visibleKeys);
  const series = node.append('g').attr('class', 'lines').selectAll('.line-g').data(lineData.filter(d => d.visible).reverse()).enter().append('g').attr('class', 'line-g').attr('role', 'region').attr('aria-label', d => d.label || d.key);
  const dateSpaces = getDateSpaces(data, params.uniqueDates, params.visibleKeys, width, scales.xScale);
  let lineStroke = width <= wideBreak || params.uniqueDates.length > 50 ? 2 : 3;
  lineStroke = width <= smallBreak ? 1.25 : lineStroke;
  const dotRadius = width <= wideBreak ? 4 : 6;

  // eslint-disable-next-line no-unused-expressions
  params.uniqueDates.length > 1 && series.append('path').attr('fill', 'none').attr('stroke-width', lineStroke).attr('stroke-linejoin', 'round').attr('stroke-linecap', 'round').attr('stroke', d => params.getColor(d.key)).style('opacity', d => {
    const opacity = d.focus ? 1 : 0.1;
    return d.visible ? opacity : 0;
  }).attr('d', d => line(d.values));
  const minDataPointSpacing = 36;
  // eslint-disable-next-line no-unused-expressions
  width / params.uniqueDates.length > minDataPointSpacing && series.selectAll('circle').data((d, i) => d.values.map(row => ({
    ...row,
    i,
    visible: d.visible,
    key: d.key
  }))).enter().append('circle').attr('r', dotRadius).attr('fill', d => params.getColor(d.key)).attr('stroke', '#fff').attr('stroke-width', lineStroke + 1).style('opacity', d => {
    const opacity = d.focus ? 1 : 0.1;
    return d.visible ? opacity : 0;
  }).attr('cx', d => scales.xScale(moment_default()(d.date).toDate())).attr('cy', d => scales.yScale(d.value)).attr('tabindex', '0').attr('role', 'graphics-symbol').attr('aria-label', d => {
    const label = formats.screenReaderFormat(d.labelDate instanceof Date ? d.labelDate : moment_default()(d.labelDate).toDate());
    return `${label} ${tooltip.valueFormat(d.value)}`;
  }).on('focus', (d, i, nodes) => {
    tooltip.show(data.find(e => e.date === d.date), nodes[i].parentNode, on/* event */.f0.target);
  }).on('blur', () => tooltip.hide());
  const focus = node.append('g').attr('class', 'focusspaces').selectAll('.focus').data(dateSpaces).enter().append('g').attr('class', 'focus');
  const focusGrid = focus.append('g').attr('class', 'focus-grid').attr('opacity', '0');
  focusGrid.append('line').attr('x1', d => scales.xScale(moment_default()(d.date).toDate())).attr('y1', 0).attr('x2', d => scales.xScale(moment_default()(d.date).toDate())).attr('y2', height);
  focusGrid.selectAll('circle').data(d => d.values).enter().append('circle').attr('r', dotRadius + 2).attr('fill', d => params.getColor(d.key)).attr('stroke', '#fff').attr('stroke-width', lineStroke + 2).attr('cx', d => scales.xScale(moment_default()(d.date).toDate())).attr('cy', d => scales.yScale(d.value));
  focus.append('rect').attr('class', 'focus-g').attr('x', d => d.start).attr('y', 0).attr('width', d => d.width).attr('height', height).attr('opacity', 0).on('mouseover', (d, i, nodes) => {
    const isTooltipLeftAligned = (i === 0 || i === dateSpaces.length - 1) && params.uniqueDates.length > 1;
    const elementWidthRatio = isTooltipLeftAligned ? 0 : 0.5;
    tooltip.show(data.find(e => e.date === d.date), on/* event */.f0.target, nodes[i].parentNode, elementWidthRatio);
  }).on('mouseout', () => tooltip.hide());
};
;// ../../packages/js/components/src/chart/d3chart/utils/tooltip.js
/**
 * External dependencies
 */


class ChartTooltip {
  constructor() {
    this.ref = null;
    this.chart = null;
    this.position = '';
    this.title = '';
    this.labelFormat = '';
    this.valueFormat = '';
    this.visibleKeys = '';
    this.getColor = null;
    this.margin = 24;
  }
  calculateXPosition(elementCoords, chartCoords, elementWidthRatio) {
    const tooltipSize = this.ref.getBoundingClientRect();
    const d3BaseCoords = this.ref.parentNode.querySelector('.d3-base').getBoundingClientRect();
    const leftMargin = Math.max(d3BaseCoords.left, chartCoords.left);
    if (this.position === 'below') {
      return Math.max(this.margin, Math.min(elementCoords.left + elementCoords.width * 0.5 - tooltipSize.width / 2 - leftMargin, d3BaseCoords.width - tooltipSize.width - this.margin));
    }
    const xPosition = elementCoords.left + elementCoords.width * elementWidthRatio + this.margin - leftMargin;
    if (xPosition + tooltipSize.width + this.margin > d3BaseCoords.width) {
      return Math.max(this.margin, elementCoords.left + elementCoords.width * (1 - elementWidthRatio) - tooltipSize.width - this.margin - leftMargin);
    }
    return xPosition;
  }
  calculateYPosition(elementCoords, chartCoords) {
    if (this.position === 'below') {
      return chartCoords.height;
    }
    const tooltipSize = this.ref.getBoundingClientRect();
    const yPosition = elementCoords.top + this.margin - chartCoords.top;
    if (yPosition + tooltipSize.height + this.margin > chartCoords.height) {
      return Math.max(0, elementCoords.top - tooltipSize.height - this.margin - chartCoords.top);
    }
    return yPosition;
  }
  calculatePosition(element, elementWidthRatio = 1) {
    const elementCoords = element.getBoundingClientRect();
    const chartCoords = this.chart.getBoundingClientRect();
    if (this.position === 'below') {
      elementWidthRatio = 0;
    }
    return {
      x: this.calculateXPosition(elementCoords, chartCoords, elementWidthRatio),
      y: this.calculateYPosition(elementCoords, chartCoords)
    };
  }
  hide() {
    (0,src_select/* default */.A)(this.chart).selectAll('.barfocus, .focus-grid').attr('opacity', '0');
    (0,src_select/* default */.A)(this.ref).style('visibility', 'hidden');
  }
  getTooltipRowLabel(d, row) {
    if (d[row.key].labelDate) {
      return this.labelFormat(moment_default()(d[row.key].labelDate).toDate());
    }
    return row.label || row.key;
  }
  show(d, triggerElement, parentNode, elementWidthRatio = 1) {
    if (!this.visibleKeys.length) {
      return;
    }
    (0,src_select/* default */.A)(parentNode).select('.focus-grid, .barfocus').attr('opacity', '1');
    const position = this.calculatePosition(triggerElement, elementWidthRatio);
    const keys = this.visibleKeys.map(row => `
					<li class="key-row">
						<div class="key-container">
							<span
								class="key-color"
								style="background-color: ${this.getColor(row.key)}">
							</span>
							<span class="key-key">${this.getTooltipRowLabel(d, row)}</span>
						</div>
						<span class="key-value">${this.valueFormat(d[row.key].value)}</span>
					</li>
				`);
    const tooltipTitle = this.title ? this.title : this.labelFormat(moment_default()(d.date).toDate());
    (0,src_select/* default */.A)(this.ref).style('left', position.x + 'px').style('top', position.y + 'px').style('visibility', 'visible').html(`
				<div>
					<h4>${tooltipTitle}</h4>
					<ul>
					${keys.join('')}
					</ul>
				</div>
			`);
  }
}
/* harmony default export */ const utils_tooltip = (ChartTooltip);
;// ../../packages/js/components/src/chart/d3chart/chart.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */










const isRTL = () => document.documentElement.dir === 'rtl';

/**
 * A simple D3 line and bar chart component for timeseries data in React.
 */
class D3Chart extends react.Component {
  constructor(props) {
    super(props);
    this.drawChart = this.drawChart.bind(this);
    this.getParams = this.getParams.bind(this);
    this.tooltipRef = (0,react.createRef)();
  }
  getFormatParams() {
    const {
      screenReaderFormat,
      xFormat,
      x2Format,
      yFormat,
      yBelow1Format
    } = this.props;
    return {
      screenReaderFormat: getFormatter(screenReaderFormat, src_defaultLocale/* timeFormat */.DC),
      xFormat: getFormatter(xFormat, src_defaultLocale/* timeFormat */.DC),
      x2Format: getFormatter(x2Format, src_defaultLocale/* timeFormat */.DC),
      yBelow1Format: getFormatter(yBelow1Format),
      yFormat: getFormatter(yFormat)
    };
  }
  getScaleParams(uniqueDates) {
    const {
      data,
      height,
      orderedKeys,
      chartType
    } = this.props;
    const margin = this.getMargin();
    const adjHeight = height - margin.top - margin.bottom;
    const adjWidth = this.getWidth() - margin.left - margin.right;
    const {
      upper: yMax,
      lower: yMin,
      step
    } = getYScaleLimits(data);
    const yScale = getYScale(adjHeight, yMin, yMax);
    if (chartType === 'line') {
      return {
        step,
        xScale: getXLineScale(uniqueDates, adjWidth),
        yMax,
        yMin,
        yScale
      };
    }
    const compact = this.shouldBeCompact();
    const xScale = getXScale(uniqueDates, adjWidth, compact);
    return {
      step,
      xGroupScale: getXGroupScale(orderedKeys, xScale, compact),
      xScale,
      yMax,
      yMin,
      yScale
    };
  }
  getParams(uniqueDates) {
    const {
      chartType,
      colorScheme,
      data,
      interval,
      mode,
      orderedKeys
    } = this.props;
    const newOrderedKeys = orderedKeys || getOrderedKeys(data);
    const visibleKeys = newOrderedKeys.filter(key => key.visible);
    const colorKeys = newOrderedKeys.length > selectionLimit ? visibleKeys : newOrderedKeys;
    return {
      getColor: getColor(colorKeys, colorScheme),
      interval,
      mode,
      chartType,
      uniqueDates,
      visibleKeys
    };
  }
  createTooltip(chart, getColorFunction, visibleKeys) {
    const {
      tooltipLabelFormat,
      tooltipPosition,
      tooltipTitle,
      tooltipValueFormat
    } = this.props;
    const tooltip = new utils_tooltip();
    tooltip.ref = this.tooltipRef.current;
    tooltip.chart = chart;
    tooltip.position = tooltipPosition;
    tooltip.title = tooltipTitle;
    tooltip.labelFormat = getFormatter(tooltipLabelFormat, src_defaultLocale/* timeFormat */.DC);
    tooltip.valueFormat = getFormatter(tooltipValueFormat);
    tooltip.visibleKeys = visibleKeys;
    tooltip.getColor = getColorFunction;
    this.tooltip = tooltip;
  }
  drawChart(node) {
    const {
      data,
      dateParser,
      chartType
    } = this.props;
    const margin = this.getMargin();
    const uniqueDates = getUniqueDates(data, dateParser);
    const formats = this.getFormatParams();
    const params = this.getParams(uniqueDates);
    const scales = this.getScaleParams(uniqueDates);
    const g = node.attr('id', 'chart').append('g').attr('transform', `translate(${margin.left}, ${margin.top})`);
    this.createTooltip(g.node(), params.getColor, params.visibleKeys);
    drawAxis(g, params, scales, formats, margin, isRTL());
    // eslint-disable-next-line no-unused-expressions
    chartType === 'line' && drawLines(g, data, params, scales, formats, this.tooltip);
    // eslint-disable-next-line no-unused-expressions
    chartType === 'bar' && drawBars(g, data, params, scales, formats, this.tooltip);
  }
  shouldBeCompact() {
    const {
      data,
      chartType,
      width
    } = this.props;
    if (chartType !== 'bar') {
      return false;
    }
    const margin = this.getMargin();
    const widthWithoutMargins = width - margin.left - margin.right;
    const columnsPerDate = data && data.length ? Object.keys(data[0]).length - 1 : 0;
    const minimumWideWidth = data.length * (columnsPerDate + 1);
    return widthWithoutMargins < minimumWideWidth;
  }
  getMargin() {
    const {
      margin
    } = this.props;
    if (isRTL()) {
      return {
        bottom: margin.bottom,
        left: margin.right,
        right: margin.left,
        top: margin.top
      };
    }
    return margin;
  }
  getWidth() {
    const {
      data,
      chartType,
      width
    } = this.props;
    if (chartType !== 'bar') {
      return width;
    }
    const margin = this.getMargin();
    const columnsPerDate = data && data.length ? Object.keys(data[0]).length - 1 : 0;
    const minimumWidth = this.shouldBeCompact() ? data.length * columnsPerDate : data.length * (columnsPerDate + 1);
    return Math.max(width, minimumWidth + margin.left + margin.right);
  }
  getEmptyMessage() {
    const {
      baseValue,
      data,
      emptyMessage
    } = this.props;
    if (emptyMessage && isDataEmpty(data, baseValue)) {
      return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "d3-chart__empty-message",
        children: emptyMessage
      });
    }
  }
  render() {
    const {
      className,
      data,
      height,
      orderedKeys,
      chartType
    } = this.props;
    const computedWidth = this.getWidth();
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('d3-chart__container', className),
      style: {
        height
      },
      children: [this.getEmptyMessage(), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "d3-chart__tooltip",
        ref: this.tooltipRef
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(D3Base, {
        className: (0,clsx/* default */.A)(className),
        data: data,
        drawChart: this.drawChart,
        height: height,
        orderedKeys: orderedKeys,
        tooltip: this.tooltip,
        chartType: chartType,
        width: computedWidth
      })]
    });
  }
}
D3Chart.defaultProps = {
  baseValue: 0,
  data: [],
  dateParser: '%Y-%m-%dT%H:%M:%S',
  height: 200,
  margin: {
    bottom: 30,
    left: 40,
    right: 0,
    top: 20
  },
  mode: 'time-comparison',
  screenReaderFormat: '%B %-d, %Y',
  tooltipPosition: 'over',
  tooltipLabelFormat: '%B %-d, %Y',
  tooltipValueFormat: ',',
  chartType: 'line',
  width: 600,
  xFormat: '%Y-%m-%d',
  x2Format: '',
  yBelow1Format: '.3~f',
  yFormat: '.3~s'
};
/* harmony default export */ const chart = (D3Chart);
;
D3Chart.__docgenInfo = {
  "description": "A simple D3 line and bar chart component for timeseries data in React.",
  "methods": [{
    "name": "getFormatParams",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getScaleParams",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "uniqueDates",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "getParams",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "uniqueDates",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "createTooltip",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "chart",
      "optional": false,
      "type": null
    }, {
      "name": "getColorFunction",
      "optional": false,
      "type": null
    }, {
      "name": "visibleKeys",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "drawChart",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "node",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "shouldBeCompact",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getMargin",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getWidth",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getEmptyMessage",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "D3Chart",
  "props": {
    "baseValue": {
      "defaultValue": {
        "value": "0",
        "computed": false
      },
      "description": "Base chart value. If no data value is different than the baseValue, the\n`emptyMessage` will be displayed if provided.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "data": {
      "defaultValue": {
        "value": "[]",
        "computed": false
      },
      "description": "An array of data.",
      "type": {
        "name": "array"
      },
      "required": false
    },
    "dateParser": {
      "defaultValue": {
        "value": "'%Y-%m-%dT%H:%M:%S'",
        "computed": false
      },
      "description": "Format to parse dates into d3 time format",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "height": {
      "defaultValue": {
        "value": "200",
        "computed": false
      },
      "description": "Height of the `svg`.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "margin": {
      "defaultValue": {
        "value": "{\n\tbottom: 30,\n\tleft: 40,\n\tright: 0,\n\ttop: 20,\n}",
        "computed": false
      },
      "description": "Margins for axis and chart padding.",
      "type": {
        "name": "shape",
        "value": {
          "bottom": {
            "name": "number",
            "required": false
          },
          "left": {
            "name": "number",
            "required": false
          },
          "right": {
            "name": "number",
            "required": false
          },
          "top": {
            "name": "number",
            "required": false
          }
        }
      },
      "required": false
    },
    "mode": {
      "defaultValue": {
        "value": "'time-comparison'",
        "computed": false
      },
      "description": "`items-comparison` (default) or `time-comparison`, this is used to generate correct\nARIA properties.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'item-comparison'",
          "computed": false
        }, {
          "value": "'time-comparison'",
          "computed": false
        }]
      },
      "required": false
    },
    "screenReaderFormat": {
      "defaultValue": {
        "value": "'%B %-d, %Y'",
        "computed": false
      },
      "description": "A datetime formatting string or overriding function to format the screen reader labels.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "tooltipPosition": {
      "defaultValue": {
        "value": "'over'",
        "computed": false
      },
      "description": "The position where to render the tooltip can be `over` the chart or `below` the chart.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'below'",
          "computed": false
        }, {
          "value": "'over'",
          "computed": false
        }]
      },
      "required": false
    },
    "tooltipLabelFormat": {
      "defaultValue": {
        "value": "'%B %-d, %Y'",
        "computed": false
      },
      "description": "A datetime formatting string or overriding function to format the tooltip label.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "tooltipValueFormat": {
      "defaultValue": {
        "value": "','",
        "computed": false
      },
      "description": "A number formatting string or function to format the value displayed in the tooltips.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "chartType": {
      "defaultValue": {
        "value": "'line'",
        "computed": false
      },
      "description": "Chart type of either `line` or `bar`.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'bar'",
          "computed": false
        }, {
          "value": "'line'",
          "computed": false
        }]
      },
      "required": false
    },
    "width": {
      "defaultValue": {
        "value": "600",
        "computed": false
      },
      "description": "Width of the `svg`.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "xFormat": {
      "defaultValue": {
        "value": "'%Y-%m-%d'",
        "computed": false
      },
      "description": "A datetime formatting string or function, passed to d3TimeFormat.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "x2Format": {
      "defaultValue": {
        "value": "''",
        "computed": false
      },
      "description": "A datetime formatting string or function, passed to d3TimeFormat.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "yBelow1Format": {
      "defaultValue": {
        "value": "'.3~f'",
        "computed": false
      },
      "description": "A number formatting string or function for numbers between -1 and 1, passed to d3Format.\nIf missing, `yFormat` will be used.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "yFormat": {
      "defaultValue": {
        "value": "'.3~s'",
        "computed": false
      },
      "description": "A number formatting string or function, passed to d3Format.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
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
    "colorScheme": {
      "description": "A chromatic color function to be passed down to d3.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "emptyMessage": {
      "description": "The message to be displayed if there is no data to render. If no message is provided,\nnothing will be displayed.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "interval": {
      "description": "Interval specification (hourly, daily, weekly etc.)",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'hour'",
          "computed": false
        }, {
          "value": "'day'",
          "computed": false
        }, {
          "value": "'week'",
          "computed": false
        }, {
          "value": "'month'",
          "computed": false
        }, {
          "value": "'quarter'",
          "computed": false
        }, {
          "value": "'year'",
          "computed": false
        }]
      },
      "required": false
    },
    "orderedKeys": {
      "description": "The list of labels for this chart.",
      "type": {
        "name": "array"
      },
      "required": false
    },
    "tooltipTitle": {
      "description": "A string to use as a title for the tooltip. Takes preference over `tooltipFormat`.",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/chart/index.js
/**
 * External dependencies
 */















/**
 * Internal dependencies
 */






function getD3CurrencyFormat(symbol, position) {
  switch (position) {
    case 'left_space':
      return [symbol + ' ', ''];
    case 'right':
      return ['', symbol];
    case 'right_space':
      return ['', ' ' + symbol];
    case 'left':
    default:
      return [symbol, ''];
  }
}

/**
 * A chart container using d3, to display timeseries data with an interactive legend.
 */
class Chart extends react.Component {
  constructor(props) {
    super(props);
    this.chartBodyRef = (0,react.createRef)();
    const dataKeys = this.getDataKeys();
    this.state = {
      focusedKeys: [],
      visibleKeys: dataKeys.slice(0, selectionLimit),
      width: 0
    };
    this.prevDataKeys = dataKeys.sort();
    this.handleTypeToggle = this.handleTypeToggle.bind(this);
    this.handleLegendToggle = this.handleLegendToggle.bind(this);
    this.handleLegendHover = this.handleLegendHover.bind(this);
    this.updateDimensions = this.updateDimensions.bind(this);
    this.getVisibleData = (0,memoize_one_esm/* default */.A)(this.getVisibleData);
    this.getOrderedKeys = (0,memoize_one_esm/* default */.A)(this.getOrderedKeys);
    this.setInterval = this.setInterval.bind(this);
  }
  getDataKeys() {
    const {
      data,
      filterParam,
      mode,
      query
    } = this.props;
    if (mode === 'item-comparison') {
      const selectedIds = filterParam ? (0,navigation_src/* getIdsFromQuery */.DF)(query[filterParam]) : [];
      return this.getOrderedKeys([], [], selectedIds).map(orderedItem => orderedItem.key);
    }
    return getUniqueKeys(data);
  }
  componentDidUpdate() {
    const {
      data
    } = this.props;
    if (!data || !data.length) {
      return;
    }
    const uniqueKeys = getUniqueKeys(data).sort();
    if (!(0,lodash.isEqual)(uniqueKeys, this.prevDataKeys)) {
      const dataKeys = this.getDataKeys();
      this.prevDataKeys = uniqueKeys;
      /* eslint-disable react/no-did-update-set-state */
      this.setState({
        visibleKeys: dataKeys.slice(0, selectionLimit)
      });
      /* eslint-enable react/no-did-update-set-state */
    }
  }
  componentDidMount() {
    this.updateDimensions();
    this.setD3DefaultFormat();
    window.addEventListener('resize', this.updateDimensions);
  }
  componentWillUnmount() {
    window.removeEventListener('resize', this.updateDimensions);
  }
  setD3DefaultFormat() {
    const {
      symbol: currencySymbol,
      symbolPosition,
      decimalSeparator: decimal,
      thousandSeparator: thousands
    } = this.props.currency;
    (0,defaultLocale/* default */.Ay)({
      decimal,
      thousands,
      grouping: [3],
      currency: getD3CurrencyFormat(currencySymbol, symbolPosition)
    });
  }
  getOrderedKeys(focusedKeys, visibleKeys, selectedIds = []) {
    const {
      data,
      legendTotals,
      mode
    } = this.props;
    if (!data || data.length === 0) {
      return [];
    }
    const uniqueKeys = data.reduce((accum, curr) => {
      Object.entries(curr).forEach(([key, value]) => {
        if (key !== 'date' && !accum[key]) {
          accum[key] = value.label;
        }
      });
      return accum;
    }, {});
    const updatedKeys = Object.entries(uniqueKeys).map(([key, label]) => {
      label = (0,src/* sanitizeHTML */.p9)(label, {
        tags: []
      });
      return {
        focus: focusedKeys.length === 0 || focusedKeys.includes(key),
        key,
        label,
        total: legendTotals && typeof legendTotals[key] !== 'undefined' ? legendTotals[key] : data.reduce((a, c) => a + c[key].value, 0),
        visible: visibleKeys.includes(key)
      };
    });
    if (mode === 'item-comparison') {
      return updatedKeys.sort((a, b) => b.total - a.total).filter(key => key.total > 0 || selectedIds.includes(parseInt(key.key, 10)));
    }
    return updatedKeys;
  }
  handleTypeToggle(chartType) {
    if (this.props.chartType !== chartType) {
      const {
        path,
        query
      } = this.props;
      (0,navigation_src/* updateQueryString */.Ze)({
        chartType
      }, path, query);
    }
  }
  handleLegendToggle(event) {
    const {
      interactiveLegend
    } = this.props;
    if (!interactiveLegend) {
      return;
    }
    const key = event.currentTarget.id.split('_').pop();
    const {
      focusedKeys,
      visibleKeys
    } = this.state;
    if (visibleKeys.includes(key)) {
      this.setState({
        focusedKeys: (0,lodash.without)(focusedKeys, key),
        visibleKeys: (0,lodash.without)(visibleKeys, key)
      });
    } else {
      this.setState({
        focusedKeys: focusedKeys.concat([key]),
        visibleKeys: visibleKeys.concat([key])
      });
    }
  }
  handleLegendHover(event) {
    if (event.type === 'mouseleave' || event.type === 'blur') {
      this.setState({
        focusedKeys: []
      });
    } else if (event.type === 'mouseenter' || event.type === 'focus') {
      const key = event.currentTarget.id.split('__').pop();
      this.setState({
        focusedKeys: [key]
      });
    }
  }
  updateDimensions() {
    this.setState({
      width: this.chartBodyRef.current.offsetWidth
    });
  }
  getVisibleData(data, orderedKeys) {
    const visibleKeys = orderedKeys.filter(d => d.visible);
    return data.map(d => {
      const newRow = {
        date: d.date
      };
      visibleKeys.forEach(row => {
        newRow[row.key] = d[row.key];
      });
      return newRow;
    });
  }
  setInterval(interval) {
    const {
      path,
      query
    } = this.props;
    (0,navigation_src/* updateQueryString */.Ze)({
      interval
    }, path, query);
  }
  renderIntervalSelector() {
    const {
      interval,
      allowedIntervals
    } = this.props;
    if (!allowedIntervals || allowedIntervals.length < 1) {
      return null;
    }
    const intervalLabels = {
      hour: (0,build_module.__)('By hour', 'woocommerce'),
      day: (0,build_module.__)('By day', 'woocommerce'),
      week: (0,build_module.__)('By week', 'woocommerce'),
      month: (0,build_module.__)('By month', 'woocommerce'),
      quarter: (0,build_module.__)('By quarter', 'woocommerce'),
      year: (0,build_module.__)('By year', 'woocommerce')
    };
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-chart__interval-select",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true,
        value: interval,
        options: allowedIntervals.map(allowedInterval => ({
          value: allowedInterval,
          label: intervalLabels[allowedInterval]
        })),
        onChange: this.setInterval
      })
    });
  }
  getChartHeight() {
    const {
      isViewportLarge,
      isViewportMobile
    } = this.props;
    if (isViewportMobile) {
      return 180;
    }
    if (isViewportLarge) {
      return 300;
    }
    return 220;
  }
  getLegendPosition() {
    const {
      legendPosition,
      mode,
      isViewportWide
    } = this.props;
    if (legendPosition) {
      return legendPosition;
    }
    if (isViewportWide && mode === 'time-comparison') {
      return 'top';
    }
    if (isViewportWide && mode === 'item-comparison') {
      return 'side';
    }
    return 'bottom';
  }
  render() {
    const {
      focusedKeys,
      visibleKeys,
      width
    } = this.state;
    const {
      baseValue,
      chartType,
      data,
      dateParser,
      emptyMessage,
      filterParam,
      interactiveLegend,
      interval,
      isRequesting,
      isViewportLarge,
      itemsLabel,
      mode,
      query,
      screenReaderFormat,
      showHeaderControls,
      title,
      tooltipLabelFormat,
      tooltipValueFormat,
      tooltipTitle,
      valueType,
      xFormat,
      x2Format,
      yBelow1Format,
      yFormat
    } = this.props;
    const selectedIds = filterParam ? (0,navigation_src/* getIdsFromQuery */.DF)(query[filterParam]) : [];
    const orderedKeys = this.getOrderedKeys(focusedKeys, visibleKeys, selectedIds);
    const visibleData = isRequesting ? null : this.getVisibleData(data, orderedKeys);
    const legendPosition = this.getLegendPosition();
    const legendDirection = legendPosition === 'top' ? 'row' : 'column';
    const chartDirection = legendPosition === 'side' ? 'row' : 'column';

    // Items label is not defined for all the reports.
    const totalLabel = itemsLabel ? (0,build_module/* sprintf */.nv)(itemsLabel, orderedKeys.length) : '';
    const chartHeight = this.getChartHeight();
    const legend = legendPosition !== 'hidden' && isRequesting ? null : /*#__PURE__*/(0,jsx_runtime.jsx)(d3chart_legend, {
      colorScheme: viridis/* default */.Ay,
      data: orderedKeys,
      handleLegendHover: this.handleLegendHover,
      handleLegendToggle: this.handleLegendToggle,
      interactive: interactiveLegend,
      legendDirection: legendDirection,
      legendValueFormat: tooltipValueFormat,
      totalLabel: totalLabel
    });
    const margin = {
      bottom: 50,
      left: 80,
      right: 30,
      top: 0
    };
    let d3chartYFormat = yFormat;
    let d3chartYBelow1Format = yBelow1Format;
    if (!yFormat) {
      switch (valueType) {
        case 'average':
          d3chartYFormat = ',.0f';
          break;
        case 'currency':
          d3chartYFormat = '$.3~s';
          d3chartYBelow1Format = '$.3~f';
          break;
        case 'number':
          d3chartYFormat = ',.0f';
          break;
      }
    }
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-chart",
      children: [showHeaderControls && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-chart__header",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(header.H, {
          className: "woocommerce-chart__title",
          children: title
        }), legendPosition === 'top' && legend, this.renderIntervalSelector(), /*#__PURE__*/(0,jsx_runtime.jsxs)(menu/* default */.Ay, {
          className: "woocommerce-chart__types",
          orientation: "horizontal",
          role: "menubar",
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            className: (0,clsx/* default */.A)('woocommerce-chart__type-button', {
              'woocommerce-chart__type-button-selected': chartType === 'line'
            }),
            title: (0,build_module.__)('Line chart', 'woocommerce'),
            "aria-checked": chartType === 'line',
            role: "menuitemradio",
            tabIndex: chartType === 'line' ? 0 : -1,
            onClick: (0,lodash.partial)(this.handleTypeToggle, 'line'),
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(line_graph/* default */.A, {})
          }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            className: (0,clsx/* default */.A)('woocommerce-chart__type-button', {
              'woocommerce-chart__type-button-selected': chartType === 'bar'
            }),
            title: (0,build_module.__)('Bar chart', 'woocommerce'),
            "aria-checked": chartType === 'bar',
            role: "menuitemradio",
            tabIndex: chartType === 'bar' ? 0 : -1,
            onClick: (0,lodash.partial)(this.handleTypeToggle, 'bar'),
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(stats_alt/* default */.A, {})
          })]
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)(section/* Section */.w, {
        component: false,
        children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: (0,clsx/* default */.A)('woocommerce-chart__body', `woocommerce-chart__body-${chartDirection}`),
          ref: this.chartBodyRef,
          children: [legendPosition === 'side' && legend, isRequesting && /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
            children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
              className: "screen-reader-text",
              children: (0,build_module.__)('Your requested data is loading', 'woocommerce')
            }), /*#__PURE__*/(0,jsx_runtime.jsx)(placeholder, {
              height: chartHeight
            })]
          }), !isRequesting && width > 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(chart, {
            baseValue: baseValue,
            chartType: chartType,
            colorScheme: viridis/* default */.Ay,
            data: visibleData,
            dateParser: dateParser,
            height: chartHeight,
            emptyMessage: emptyMessage,
            interval: interval,
            margin: margin,
            mode: mode,
            orderedKeys: orderedKeys,
            screenReaderFormat: screenReaderFormat,
            tooltipLabelFormat: tooltipLabelFormat,
            tooltipValueFormat: tooltipValueFormat,
            tooltipPosition: isViewportLarge ? 'over' : 'below',
            tooltipTitle: tooltipTitle,
            valueType: valueType,
            width: chartDirection === 'row' ? width - 320 : width,
            xFormat: xFormat,
            x2Format: x2Format,
            yBelow1Format: d3chartYBelow1Format,
            yFormat: d3chartYFormat
          })]
        }), legendPosition === 'bottom' && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "woocommerce-chart__footer",
          children: legend
        })]
      })]
    });
  }
}
Chart.defaultProps = {
  baseValue: 0,
  chartType: 'line',
  data: [],
  dateParser: '%Y-%m-%dT%H:%M:%S',
  interactiveLegend: true,
  interval: 'day',
  isRequesting: false,
  mode: 'time-comparison',
  screenReaderFormat: '%B %-d, %Y',
  showHeaderControls: true,
  tooltipLabelFormat: '%B %-d, %Y',
  tooltipValueFormat: ',',
  xFormat: '%d',
  x2Format: '%b %Y',
  currency: {
    symbol: '$',
    symbolPosition: 'left',
    decimalSeparator: '.',
    thousandSeparator: ','
  }
};
/* harmony default export */ const src_chart = ((0,viewport_build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium',
  isViewportLarge: '>= large',
  isViewportWide: '>= wide'
})(Chart));
;
Chart.__docgenInfo = {
  "description": "A chart container using d3, to display timeseries data with an interactive legend.",
  "methods": [{
    "name": "getDataKeys",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "setD3DefaultFormat",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getOrderedKeys",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "focusedKeys",
      "optional": false,
      "type": null
    }, {
      "name": "visibleKeys",
      "optional": false,
      "type": null
    }, {
      "name": "selectedIds",
      "optional": true,
      "type": null
    }],
    "returns": null
  }, {
    "name": "handleTypeToggle",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "chartType",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "handleLegendToggle",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "event",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "handleLegendHover",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "event",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "updateDimensions",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getVisibleData",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "data",
      "optional": false,
      "type": null
    }, {
      "name": "orderedKeys",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "setInterval",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "interval",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "renderIntervalSelector",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getChartHeight",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "getLegendPosition",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "Chart",
  "props": {
    "baseValue": {
      "defaultValue": {
        "value": "0",
        "computed": false
      },
      "description": "Base chart value. If no data value is different than the baseValue, the\n`emptyMessage` will be displayed if provided.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "chartType": {
      "defaultValue": {
        "value": "'line'",
        "computed": false
      },
      "description": "Chart type of either `line` or `bar`.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'bar'",
          "computed": false
        }, {
          "value": "'line'",
          "computed": false
        }]
      },
      "required": false
    },
    "data": {
      "defaultValue": {
        "value": "[]",
        "computed": false
      },
      "description": "An array of data.",
      "type": {
        "name": "array"
      },
      "required": false
    },
    "dateParser": {
      "defaultValue": {
        "value": "'%Y-%m-%dT%H:%M:%S'",
        "computed": false
      },
      "description": "Format to parse dates into d3 time format",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "interactiveLegend": {
      "defaultValue": {
        "value": "true",
        "computed": false
      },
      "description": "Whether the legend items can be activated/deactivated.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "interval": {
      "defaultValue": {
        "value": "'day'",
        "computed": false
      },
      "description": "Interval specification (hourly, daily, weekly etc).",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'hour'",
          "computed": false
        }, {
          "value": "'day'",
          "computed": false
        }, {
          "value": "'week'",
          "computed": false
        }, {
          "value": "'month'",
          "computed": false
        }, {
          "value": "'quarter'",
          "computed": false
        }, {
          "value": "'year'",
          "computed": false
        }]
      },
      "required": false
    },
    "isRequesting": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "description": "Render a chart placeholder to signify an in-flight data request.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "mode": {
      "defaultValue": {
        "value": "'time-comparison'",
        "computed": false
      },
      "description": "`item-comparison` (default) or `time-comparison`, this is used to generate correct\nARIA properties.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'item-comparison'",
          "computed": false
        }, {
          "value": "'time-comparison'",
          "computed": false
        }]
      },
      "required": false
    },
    "screenReaderFormat": {
      "defaultValue": {
        "value": "'%B %-d, %Y'",
        "computed": false
      },
      "description": "A datetime formatting string or overriding function to format the screen reader labels.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "showHeaderControls": {
      "defaultValue": {
        "value": "true",
        "computed": false
      },
      "description": "Whether header UI controls must be displayed.",
      "type": {
        "name": "bool"
      },
      "required": false
    },
    "tooltipLabelFormat": {
      "defaultValue": {
        "value": "'%B %-d, %Y'",
        "computed": false
      },
      "description": "A datetime formatting string or overriding function to format the tooltip label.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "tooltipValueFormat": {
      "defaultValue": {
        "value": "','",
        "computed": false
      },
      "description": "A number formatting string or function to format the value displayed in the tooltips.",
      "type": {
        "name": "union",
        "value": [{
          "name": "string"
        }, {
          "name": "func"
        }]
      },
      "required": false
    },
    "xFormat": {
      "defaultValue": {
        "value": "'%d'",
        "computed": false
      },
      "description": "A datetime formatting string, passed to d3TimeFormat.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "x2Format": {
      "defaultValue": {
        "value": "'%b %Y'",
        "computed": false
      },
      "description": "A datetime formatting string, passed to d3TimeFormat.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "currency": {
      "defaultValue": {
        "value": "{\n\tsymbol: '$',\n\tsymbolPosition: 'left',\n\tdecimalSeparator: '.',\n\tthousandSeparator: ',',\n}",
        "computed": false
      },
      "description": "A currency object passed to d3Format.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "allowedIntervals": {
      "description": "Allowed intervals to show in a dropdown.",
      "type": {
        "name": "array"
      },
      "required": false
    },
    "emptyMessage": {
      "description": "The message to be displayed if there is no data to render. If no message is provided,\nnothing will be displayed.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "filterParam": {
      "description": "Name of the param used to filter items. If specified, it will be used, in combination\nwith query, to detect which elements are being used by the current filter and must be\ndisplayed even if their value is 0.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "itemsLabel": {
      "description": "Label describing the legend items.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "path": {
      "description": "Current path",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "query": {
      "description": "The query string represented in object form",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "intervalData": {
      "description": "Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "legendPosition": {
      "description": "Position the legend must be displayed in. If it's not defined, it's calculated\ndepending on the viewport width and the mode.",
      "type": {
        "name": "enum",
        "value": [{
          "value": "'bottom'",
          "computed": false
        }, {
          "value": "'side'",
          "computed": false
        }, {
          "value": "'top'",
          "computed": false
        }, {
          "value": "'hidden'",
          "computed": false
        }]
      },
      "required": false
    },
    "legendTotals": {
      "description": "Values to overwrite the legend totals. If not defined, the sum of all line values will be used.",
      "type": {
        "name": "object"
      },
      "required": false
    },
    "title": {
      "description": "A title describing this chart.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "tooltipTitle": {
      "description": "A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "valueType": {
      "description": "What type of data is to be displayed? Number, Average, String?",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "yBelow1Format": {
      "description": "A number formatting string, passed to d3Format.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "yFormat": {
      "description": "A number formatting string, passed to d3Format.",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/chart/stories/chart.story.js
/**
 * Internal dependencies
 */


const data = [{
  date: '2018-05-30T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 21599
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 38537
  },
  Cap: {
    label: 'Cap',
    value: 106010
  },
  Tshirt: {
    label: 'Tshirt',
    value: 26784
  },
  Jeans: {
    label: 'Jeans',
    value: 35645
  },
  Headphones: {
    label: 'Headphones',
    value: 19500
  },
  Lamp: {
    label: 'Lamp',
    value: 21599
  },
  Socks: {
    label: 'Socks',
    value: 32572
  },
  Mug: {
    label: 'Mug',
    value: 10991
  },
  Case: {
    label: 'Case',
    value: 35537
  }
}, {
  date: '2018-05-31T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 14205
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 24721
  },
  Cap: {
    label: 'Cap',
    value: 70131
  },
  Tshirt: {
    label: 'Tshirt',
    value: 16784
  },
  Jeans: {
    label: 'Jeans',
    value: 25645
  },
  Headphones: {
    label: 'Headphones',
    value: 39500
  },
  Lamp: {
    label: 'Lamp',
    value: 15599
  },
  Socks: {
    label: 'Socks',
    value: 27572
  },
  Mug: {
    label: 'Mug',
    value: 110991
  },
  Case: {
    label: 'Case',
    value: 21537
  }
}, {
  date: '2018-06-01T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 10581
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 19991
  },
  Cap: {
    label: 'Cap',
    value: 53552
  },
  Tshirt: {
    label: 'Tshirt',
    value: 41784
  },
  Jeans: {
    label: 'Jeans',
    value: 17645
  },
  Headphones: {
    label: 'Headphones',
    value: 22500
  },
  Lamp: {
    label: 'Lamp',
    value: 25599
  },
  Socks: {
    label: 'Socks',
    value: 14572
  },
  Mug: {
    label: 'Mug',
    value: 20991
  },
  Case: {
    label: 'Case',
    value: 11537
  }
}, {
  date: '2018-06-02T00:00:00',
  Hoodie: {
    label: 'Hoodie',
    value: 9250
  },
  Sunglasses: {
    label: 'Sunglasses',
    value: 16072
  },
  Cap: {
    label: 'Cap',
    value: 47821
  },
  Tshirt: {
    label: 'Tshirt',
    value: 18784
  },
  Jeans: {
    label: 'Jeans',
    value: 29645
  },
  Headphones: {
    label: 'Headphones',
    value: 24500
  },
  Lamp: {
    label: 'Lamp',
    value: 18599
  },
  Socks: {
    label: 'Socks',
    value: 23572
  },
  Mug: {
    label: 'Mug',
    value: 20991
  },
  Case: {
    label: 'Case',
    value: 16537
  }
}];
/* harmony default export */ const chart_story = ({
  title: 'Components/Chart',
  component: src_chart,
  args: {
    legendPosition: undefined
  },
  argTypes: {
    legendPosition: {
      control: {
        type: 'select'
      },
      options: [undefined, 'bottom', 'side', 'top', 'hidden']
    }
  }
});
const Default = ({
  legendPosition
}) => /*#__PURE__*/(0,jsx_runtime.jsx)(src_chart, {
  data: data,
  legendPosition: legendPosition
});
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "({\n  legendPosition\n}) => <Chart data={data} legendPosition={legendPosition} />",
      ...Default.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/section/context.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $: () => (/* binding */ Level)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */


/**
 * Context container for heading level. We start at 2 because the `h1` is defined in <Header />
 *
 * See https://medium.com/@Heydon/managing-heading-levels-in-design-systems-18be9a746fa3
 */
const Level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(2);

try {
    // @ts-ignore
    Context.displayName = "Context";
    // @ts-ignore
    Context.__docgenInfo = { "description": "Context lets components pass information deep down without explicitly\npassing props.\n\nCreated from {@link createContext}", "displayName": "Context", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/context.tsx#Context"] = { docgenInfo: Context.__docgenInfo, name: "Context", path: "../../packages/js/components/src/section/context.tsx#Context" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/header.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H: () => (/* binding */ H)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
 * (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
 * heading level.
 *
 * @type {HTMLElement}
 */

function H(props) {
  const level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$);
  const Heading = 'h' + Math.min(level, 6);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Heading, {
    ...props
  });
}
try {
    // @ts-ignore
    H.displayName = "H";
    // @ts-ignore
    H.__docgenInfo = { "description": "These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels\n(`h2`, `h3`, \u2026) you can use `<H />` to create \"section headings\", which look to the parent `<Section />`s for the appropriate\nheading level.", "displayName": "H", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/header.tsx#H"] = { docgenInfo: H.__docgenInfo, name: "H", path: "../../packages/js/components/src/section/header.tsx#H" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/section.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   w: () => (/* binding */ Section)
/* harmony export */ });
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 */
const Section = ({
  component,
  children,
  ...props
}) => {
  const Component = component || 'div';
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Consumer, {
    children: level => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Provider, {
      value: level + 1,
      children: component === false ? children : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Component, {
        ...props,
        children: children
      })
    })
  });
};
try {
    // @ts-ignore
    Section.displayName = "Section";
    // @ts-ignore
    Section.__docgenInfo = { "description": "The section wrapper, used to indicate a sub-section (and change the header level context).", "displayName": "Section", "props": { "component": { "defaultValue": null, "description": "The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component.", "name": "component", "required": false, "type": { "name": "string | false | ComponentType<{ className?: string; }>" } }, "className": { "defaultValue": null, "description": "Optional classname", "name": "className", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "The children inside this section, rendered in the `component`. This increases the context level for the next heading used.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/section.tsx#Section"] = { docgenInfo: Section.__docgenInfo, name: "Section", path: "../../packages/js/components/src/section/section.tsx#Section" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/sanitize/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  p9: () => (/* reexport */ sanitize_sanitizeHTML)
});

// UNUSED EXPORTS: DEFAULT_ALLOWED_ATTR, DEFAULT_ALLOWED_TAGS, getTrustedTypesPolicy

// EXTERNAL MODULE: ../../node_modules/.pnpm/dompurify@3.4.14/node_modules/dompurify/dist/purify.es.mjs
var purify_es = __webpack_require__("../../node_modules/.pnpm/dompurify@3.4.14/node_modules/dompurify/dist/purify.es.mjs");
;// ../../packages/js/sanitize/src/noop-trusted-types-policy.ts
/**
 * External dependencies
 */

/**
 * The type for our no-op trusted types policy.
 */

/**
 * Cached no-op policy instance to avoid duplicate creation.
 */
let noopPolicyInstance;
function getNoopTrustedTypesPolicy() {
  if (noopPolicyInstance !== undefined) {
    return noopPolicyInstance;
  }
  if (typeof window === 'undefined' || !window.trustedTypes) {
    noopPolicyInstance = null;
    return null;
  }
  try {
    noopPolicyInstance = window.trustedTypes.createPolicy('woocommerce-sanitize-noop', {
      createHTML: input => input
    });
  } catch (error) {
    noopPolicyInstance = null;
    // eslint-disable-next-line no-console
    console.warn('Failed to create "woocommerce-sanitize-noop" trusted type policy:', error);
  }
  return noopPolicyInstance;
}
;// ../../packages/js/sanitize/src/sanitize.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Default allowed HTML tags for basic sanitization.
 */
const DEFAULT_ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p', 'br', 'abbr'];

/**
 * Default allowed HTML attributes for basic sanitization.
 */
const DEFAULT_ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download', 'title'];

/**
 * The set of supported return type kinds for sanitized content.
 * These are the configuration values you can pass via `returnType`.
 */

/**
 * Mapping between `SanitizeReturnKind` and the actual returned value types.
 */

/**
 * Union of the concrete value types this sanitizer can return.
 * Useful when you want to accept any possible sanitizer output.
 */

/**
 * Configuration options for HTML sanitization.
 */

/**
 * Sanitizes HTML content using DOMPurify with default allowed tags and attributes.
 *
 * @param html   - The HTML content to sanitize.
 * @param config - Optional configuration for allowed tags and attributes.
 *
 * @return Sanitized HTML content.
 */
function sanitize_sanitizeHTML(html, config) {
  const allowedTags = config?.tags || DEFAULT_ALLOWED_TAGS;
  const allowedAttr = config?.attr || DEFAULT_ALLOWED_ATTR;
  const purifyConfig = {
    ALLOWED_TAGS: [...allowedTags],
    ALLOWED_ATTR: [...allowedAttr]
  };

  // Provide a no-op TT policy (when supported) to prevent DOMPurify from
  // creating its internal policy and emitting warnings with multiple instances.
  const ttNoopPolicy = getNoopTrustedTypesPolicy();
  if (ttNoopPolicy) {
    purifyConfig.TRUSTED_TYPES_POLICY = ttNoopPolicy;
  }

  // Only pass a single RETURN_* flag if a non-string return type is requested
  if (config?.returnType === 'HTMLBodyElement') {
    purifyConfig.RETURN_DOM = true;
  } else if (config?.returnType === 'DocumentFragment') {
    purifyConfig.RETURN_DOM_FRAGMENT = true;
  }
  return purify_es/* default */.A.sanitize(html ?? '', purifyConfig);
}
;// ../../packages/js/sanitize/src/trusted-types-policy.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * The type for our trusted types policy.
 */

/**
 * Cached policy instance to ensure it's only created once.
 */
let policyInstance;

/**
 * Get or create a trusted types policy for DOMPurify.
 *
 * @return TrustedTypePolicy object or null if not supported.
 */
function getTrustedTypesPolicy() {
  if (policyInstance !== undefined) {
    return policyInstance;
  }
  if (typeof window === 'undefined' || !window.trustedTypes) {
    policyInstance = null;
    return null;
  }
  try {
    policyInstance = window.trustedTypes.createPolicy('woocommerce-sanitize', {
      createHTML: input => sanitizeHTML(input)
    });
  } catch (error) {
    policyInstance = null;
    // eslint-disable-next-line no-console
    console.warn('Failed to create "woocommerce-sanitize" trusted type policy:', error);
  }
  return policyInstance;
}
;// ../../packages/js/sanitize/src/index.ts



/***/ }),

/***/ "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale sync recursive ^\\.\\/.*$":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./af": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/af.js",
	"./af.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/af.js",
	"./ar": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar.js",
	"./ar-dz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ma.js",
	"./ar-ps": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ps.js",
	"./ar-ps.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-ps.js",
	"./ar-sa": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar-tn.js",
	"./ar.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ar.js",
	"./az": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/az.js",
	"./az.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/az.js",
	"./be": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/be.js",
	"./be.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/be.js",
	"./bg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bg.js",
	"./bg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bg.js",
	"./bm": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bm.js",
	"./bm.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bm.js",
	"./bn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn.js",
	"./bn-bd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn-bd.js",
	"./bn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bn.js",
	"./bo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bo.js",
	"./bo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bo.js",
	"./br": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/br.js",
	"./br.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/br.js",
	"./bs": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bs.js",
	"./bs.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/bs.js",
	"./ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ca.js",
	"./ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ca.js",
	"./cs": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cs.js",
	"./cs.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cs.js",
	"./cv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cv.js",
	"./cv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cv.js",
	"./cy": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cy.js",
	"./cy.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/cy.js",
	"./da": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/da.js",
	"./da.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/da.js",
	"./de": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de.js",
	"./de-at": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-at.js",
	"./de-at.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-at.js",
	"./de-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de-ch.js",
	"./de.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/de.js",
	"./dv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/dv.js",
	"./dv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/dv.js",
	"./el": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/el.js",
	"./el.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/el.js",
	"./en-au": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-au.js",
	"./en-au.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-au.js",
	"./en-ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ca.js",
	"./en-gb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-gb.js",
	"./en-ie": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-ie.js",
	"./en-il": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-il.js",
	"./en-il.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-il.js",
	"./en-in": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-in.js",
	"./en-in.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-in.js",
	"./en-nz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-nz.js",
	"./en-sg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/en-sg.js",
	"./eo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eo.js",
	"./eo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eo.js",
	"./es": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es.js",
	"./es-do": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-do.js",
	"./es-do.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-do.js",
	"./es-mx": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-mx.js",
	"./es-us": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-us.js",
	"./es-us.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es-us.js",
	"./es.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/es.js",
	"./et": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/et.js",
	"./et.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/et.js",
	"./eu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eu.js",
	"./eu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/eu.js",
	"./fa": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fa.js",
	"./fa.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fa.js",
	"./fi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fi.js",
	"./fi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fi.js",
	"./fil": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fil.js",
	"./fil.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fil.js",
	"./fo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fo.js",
	"./fo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fo.js",
	"./fr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr.js",
	"./fr-ca": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr-ch.js",
	"./fr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fr.js",
	"./fy": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fy.js",
	"./fy.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/fy.js",
	"./ga": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ga.js",
	"./ga.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ga.js",
	"./gd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gd.js",
	"./gd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gd.js",
	"./gl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gl.js",
	"./gl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gl.js",
	"./gom-deva": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gom-latn.js",
	"./gu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gu.js",
	"./gu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/gu.js",
	"./he": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/he.js",
	"./he.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/he.js",
	"./hi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hi.js",
	"./hi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hi.js",
	"./hr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hr.js",
	"./hr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hr.js",
	"./hu": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hu.js",
	"./hu.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hu.js",
	"./hy-am": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/hy-am.js",
	"./id": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/id.js",
	"./id.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/id.js",
	"./is": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/is.js",
	"./is.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/is.js",
	"./it": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it.js",
	"./it-ch": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it-ch.js",
	"./it.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/it.js",
	"./ja": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ja.js",
	"./ja.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ja.js",
	"./jv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/jv.js",
	"./jv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/jv.js",
	"./ka": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ka.js",
	"./ka.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ka.js",
	"./kk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kk.js",
	"./kk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kk.js",
	"./km": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/km.js",
	"./km.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/km.js",
	"./kn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kn.js",
	"./kn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/kn.js",
	"./ko": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ko.js",
	"./ko.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ko.js",
	"./ku": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku.js",
	"./ku-kmr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku-kmr.js",
	"./ku-kmr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku-kmr.js",
	"./ku.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ku.js",
	"./ky": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ky.js",
	"./ky.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ky.js",
	"./lb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lb.js",
	"./lb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lb.js",
	"./lo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lo.js",
	"./lo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lo.js",
	"./lt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lt.js",
	"./lt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lt.js",
	"./lv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lv.js",
	"./lv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/lv.js",
	"./me": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/me.js",
	"./me.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/me.js",
	"./mi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mi.js",
	"./mi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mi.js",
	"./mk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mk.js",
	"./mk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mk.js",
	"./ml": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ml.js",
	"./ml.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ml.js",
	"./mn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mn.js",
	"./mn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mn.js",
	"./mr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mr.js",
	"./mr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mr.js",
	"./ms": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms.js",
	"./ms-my": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms-my.js",
	"./ms.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ms.js",
	"./mt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mt.js",
	"./mt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/mt.js",
	"./my": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/my.js",
	"./my.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/my.js",
	"./nb": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nb.js",
	"./nb.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nb.js",
	"./ne": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ne.js",
	"./ne.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ne.js",
	"./nl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl.js",
	"./nl-be": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl-be.js",
	"./nl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nl.js",
	"./nn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nn.js",
	"./nn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/nn.js",
	"./oc-lnc": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pa-in.js",
	"./pl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pl.js",
	"./pl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pl.js",
	"./pt": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt.js",
	"./pt-br": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt-br.js",
	"./pt.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/pt.js",
	"./ro": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ro.js",
	"./ro.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ro.js",
	"./ru": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ru.js",
	"./ru.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ru.js",
	"./sd": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sd.js",
	"./sd.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sd.js",
	"./se": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/se.js",
	"./se.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/se.js",
	"./si": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/si.js",
	"./si.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/si.js",
	"./sk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sk.js",
	"./sk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sk.js",
	"./sl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sl.js",
	"./sl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sl.js",
	"./sq": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sq.js",
	"./sq.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sq.js",
	"./sr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr.js",
	"./sr-cyrl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sr.js",
	"./ss": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ss.js",
	"./ss.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ss.js",
	"./sv": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sv.js",
	"./sv.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sv.js",
	"./sw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sw.js",
	"./sw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/sw.js",
	"./ta": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ta.js",
	"./ta.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ta.js",
	"./te": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/te.js",
	"./te.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/te.js",
	"./tet": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tet.js",
	"./tet.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tet.js",
	"./tg": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tg.js",
	"./tg.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tg.js",
	"./th": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/th.js",
	"./th.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/th.js",
	"./tk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tk.js",
	"./tk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tk.js",
	"./tl-ph": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tl-ph.js",
	"./tlh": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tlh.js",
	"./tlh.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tlh.js",
	"./tr": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tr.js",
	"./tr.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tr.js",
	"./tzl": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzl.js",
	"./tzl.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzl.js",
	"./tzm": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm.js",
	"./tzm-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/tzm.js",
	"./ug-cn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ug-cn.js",
	"./uk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uk.js",
	"./uk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uk.js",
	"./ur": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ur.js",
	"./ur.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/ur.js",
	"./uz": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz.js",
	"./uz-latn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz-latn.js",
	"./uz.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/uz.js",
	"./vi": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/vi.js",
	"./vi.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/vi.js",
	"./x-pseudo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/x-pseudo.js",
	"./yo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/yo.js",
	"./yo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/yo.js",
	"./zh-cn": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale/zh-tw.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "../../node_modules/.pnpm/moment@2.30.1/node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ })

}]);