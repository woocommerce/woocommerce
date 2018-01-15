/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var __ = wp.i18n.__;
var _wp$blocks = wp.blocks,
    registerBlockType = _wp$blocks.registerBlockType,
    InspectorControls = _wp$blocks.InspectorControls,
    BlockControls = _wp$blocks.BlockControls;
var Toolbar = wp.components.Toolbar;
var RangeControl = InspectorControls.RangeControl,
    ToggleControl = InspectorControls.ToggleControl,
    SelectControl = InspectorControls.SelectControl;

/**
 * Products block.
 */

registerBlockType('woocommerce/products', {
	title: __('Products'),
	icon: 'universal-access-alt', // @todo Needs a good icon.
	category: 'widgets',

	attributes: {
		layout: {
			type: 'string',
			default: 'grid'
		},
		columns: {
			type: 'number',
			default: 3
		},
		rows: {
			type: 'number',
			default: 1
		},
		display_title: {
			type: 'boolean',
			default: true
		},
		display_price: {
			type: 'boolean',
			default: true
		},
		display_add_to_cart: {
			type: 'boolean',
			default: false
		},
		order: {
			type: 'string',
			default: 'newness'

			// @todo
			// Needs attributes for the product/category select menu:
			// display: "specific_products", "product_category", etc.
			// display_setting: array of product/category ids?
		} },

	// @todo This will need to be converted to pull dynamic data from the API similar to https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/.
	edit: function edit(props) {
		var attributes = props.attributes,
		    className = props.className,
		    focus = props.focus,
		    setAttributes = props.setAttributes,
		    setFocus = props.setFocus;
		var layout = attributes.layout,
		    rows = attributes.rows,
		    columns = attributes.columns,
		    display_title = attributes.display_title,
		    display_price = attributes.display_price,
		    display_add_to_cart = attributes.display_add_to_cart,
		    order = attributes.order;

		/**
   * Get the components for the sidebar settings area that is rendered while focused on a Products block.
   */

		function getInspectorControls() {
			return wp.element.createElement(
				InspectorControls,
				{ key: 'inspector' },
				wp.element.createElement(
					'h3',
					null,
					__('Layout')
				),
				wp.element.createElement(RangeControl, {
					label: __('Columns'),
					value: columns,
					onChange: function onChange(value) {
						return setAttributes({ columns: value });
					},
					min: 1,
					max: 6
				}),
				wp.element.createElement(RangeControl, {
					label: __('Rows'),
					value: rows,
					onChange: function onChange(value) {
						return setAttributes({ rows: value });
					},
					min: 1,
					max: 6
				}),
				wp.element.createElement(ToggleControl, {
					label: __('Display title'),
					checked: display_title,
					onChange: function onChange() {
						return setAttributes({ display_title: !display_title });
					}
				}),
				wp.element.createElement(ToggleControl, {
					label: __('Display price'),
					checked: display_price,
					onChange: function onChange() {
						return setAttributes({ display_price: !display_price });
					}
				}),
				wp.element.createElement(ToggleControl, {
					label: __('Display add to cart button'),
					checked: display_add_to_cart,
					onChange: function onChange() {
						return setAttributes({ display_add_to_cart: !display_add_to_cart });
					}
				}),
				wp.element.createElement(SelectControl, {
					key: 'query-panel-select',
					label: __('Order'),
					value: order,
					options: [{
						label: __('Newness'),
						value: 'newness'
					}, {
						label: __('Best Selling'),
						value: 'sales'
					}, {
						label: __('Title'),
						value: 'title'
					}],
					onChange: function onChange(value) {
						return setAttributes({ order: value });
					}
				})
			);
		};

		function getToolbarControls() {
			var layoutControls = [{
				icon: 'list-view',
				title: __('List View'),
				onClick: function onClick() {
					return setAttributes({ layout: 'list' });
				},
				isActive: layout === 'list'
			}, {
				icon: 'grid-view',
				title: __('Grid View'),
				onClick: function onClick() {
					return setAttributes({ layout: 'grid' });
				},
				isActive: layout === 'grid'
			}];

			// @todo Hook this up to the Edit modal thing.
			var editButton = [{
				icon: 'edit',
				title: __('Edit'),
				onClick: function onClick() {}
			}];

			return wp.element.createElement(
				BlockControls,
				{ key: 'controls' },
				wp.element.createElement(Toolbar, { controls: layoutControls }),
				wp.element.createElement(Toolbar, { controls: editButton })
			);
		}

		return [!!focus ? getInspectorControls() : null, !!focus ? getToolbarControls() : null, wp.element.createElement(
			'div',
			{ className: className },
			'This needs to do stuff.'
		)];
	},
	save: function save() {
		// @todo
		// This can either build a shortcode out of all the settings (good backwards compatibility but may need extra shortcode work)
		// Or it can return null and the html can be generated with PHP like the example at https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/
		return '[products limit="3"]';
	}
});

/***/ })
/******/ ]);