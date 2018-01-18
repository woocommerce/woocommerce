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


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __ = wp.i18n.__;
var _wp$blocks = wp.blocks,
    registerBlockType = _wp$blocks.registerBlockType,
    InspectorControls = _wp$blocks.InspectorControls,
    BlockControls = _wp$blocks.BlockControls;
var _wp$components = wp.components,
    Toolbar = _wp$components.Toolbar,
    withAPIData = _wp$components.withAPIData;
var RangeControl = InspectorControls.RangeControl,
    ToggleControl = InspectorControls.ToggleControl,
    SelectControl = InspectorControls.SelectControl;

/**
 * When the display mode is 'Specific products' search for and add products to the block.
 *
 * @todo Add the functionality and everything.
 */

var ProductsSpecificSelect = function (_React$Component) {
	_inherits(ProductsSpecificSelect, _React$Component);

	function ProductsSpecificSelect() {
		_classCallCheck(this, ProductsSpecificSelect);

		return _possibleConstructorReturn(this, (ProductsSpecificSelect.__proto__ || Object.getPrototypeOf(ProductsSpecificSelect)).apply(this, arguments));
	}

	_createClass(ProductsSpecificSelect, [{
		key: "render",
		value: function render() {
			return wp.element.createElement(
				"div",
				null,
				"TODO: Select specific products here"
			);
		}
	}]);

	return ProductsSpecificSelect;
}(React.Component);

/**
 * When the display mode is 'Product category' search for and select product categories to pull products from.
 *
 * @todo Save data
 */


var ProductsCategorySelect = function (_React$Component2) {
	_inherits(ProductsCategorySelect, _React$Component2);

	/**
  * Constructor.
  */
	function ProductsCategorySelect(props) {
		_classCallCheck(this, ProductsCategorySelect);

		var _this2 = _possibleConstructorReturn(this, (ProductsCategorySelect.__proto__ || Object.getPrototypeOf(ProductsCategorySelect)).call(this, props));

		_this2.state = {
			selectedCategories: [],
			filterQuery: ''
		};

		_this2.checkboxChange = _this2.checkboxChange.bind(_this2);
		_this2.filterResults = _this2.filterResults.bind(_this2);
		return _this2;
	}

	/**
  * Handle checkbox toggle.
  *
  * @param Event object evt
  */


	_createClass(ProductsCategorySelect, [{
		key: "checkboxChange",
		value: function checkboxChange(evt) {
			var selectedCategories = this.state.selectedCategories;

			if (evt.target.checked && !selectedCategories.includes(parseInt(evt.target.value, 10))) {
				selectedCategories.push(parseInt(evt.target.value, 10));
			} else if (!evt.target.checked) {
				selectedCategories = selectedCategories.filter(function (category) {
					return category !== parseInt(evt.target.value, 10);
				});
			}

			this.setState({
				selectedCategories: selectedCategories
			});
		}

		/**
   * Filter categories.
   *
   * @param Event object evt
   */

	}, {
		key: "filterResults",
		value: function filterResults(evt) {
			this.setState({
				filterQuery: evt.target.value
			});
		}

		/**
   * Render the list of categories and the search input.
   */

	}, {
		key: "render",
		value: function render() {
			return wp.element.createElement(
				"div",
				null,
				wp.element.createElement(ProductCategoryFilter, { filterResults: this.filterResults }),
				wp.element.createElement(ProductCategoryList, { filterQuery: this.state.filterQuery, selectedCategories: this.state.selectedCategories, checkboxChange: this.checkboxChange })
			);
		}
	}]);

	return ProductsCategorySelect;
}(React.Component);

/**
 * The category search input.
 */


var ProductCategoryFilter = function ProductCategoryFilter(_ref) {
	var filterResults = _ref.filterResults;

	return wp.element.createElement(
		"div",
		null,
		wp.element.createElement("input", { id: "product-category-search", type: "search", placeholder: __('Search for categories'), onChange: filterResults })
	);
};

/**
 * Fetch and build a tree of product categories.
 */
var ProductCategoryList = withAPIData(function (props) {
	return {
		categories: '/wc/v2/products/categories'
	};
})(function (_ref2) {
	var categories = _ref2.categories,
	    filterQuery = _ref2.filterQuery,
	    selectedCategories = _ref2.selectedCategories,
	    checkboxChange = _ref2.checkboxChange;

	if (!categories.data) {
		return __('Loading');
	}

	if (0 === categories.data.length) {
		return __('No categories found');
	}

	var CategoryTree = function CategoryTree(_ref3) {
		var categories = _ref3.categories,
		    parent = _ref3.parent;

		var filteredCategories = categories.filter(function (category) {
			return category.parent === parent;
		});

		return filteredCategories.length > 0 && wp.element.createElement(
			"ul",
			null,
			filteredCategories.map(function (category) {
				return wp.element.createElement(
					"li",
					null,
					wp.element.createElement(
						"label",
						{ "for": 'product-category-' + category.id },
						wp.element.createElement("input", { type: "checkbox",
							id: 'product-category-' + category.id,
							value: category.id,
							checked: selectedCategories.includes(category.id),
							onChange: checkboxChange
						}),
						" ",
						category.name
					),
					wp.element.createElement(CategoryTree, { categories: categories, parent: category.id })
				);
			})
		);
	};

	var categoriesData = categories.data;

	if ('' !== filterQuery) {
		categoriesData = categoriesData.filter(function (category) {
			return category.slug.includes(filterQuery.toLowerCase());
		});
	}

	return wp.element.createElement(
		"div",
		null,
		wp.element.createElement(CategoryTree, { categories: categoriesData, parent: 0 })
	);
});

/**
 * The products block when in Edit mode.
 */

var ProductsBlockSettingsEditor = function (_React$Component3) {
	_inherits(ProductsBlockSettingsEditor, _React$Component3);

	/**
  * Constructor.
  */
	function ProductsBlockSettingsEditor(props) {
		_classCallCheck(this, ProductsBlockSettingsEditor);

		var _this3 = _possibleConstructorReturn(this, (ProductsBlockSettingsEditor.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditor)).call(this, props));

		_this3.state = {
			display: props.selected_display
		};

		_this3.updateDisplay = _this3.updateDisplay.bind(_this3);
		return _this3;
	}

	/**
  * Update the display settings for the block.
  *
  * @param Event object evt
  */


	_createClass(ProductsBlockSettingsEditor, [{
		key: "updateDisplay",
		value: function updateDisplay(evt) {
			this.setState({
				display: evt.target.value
			});

			this.props.update_display_callback(evt.target.value);
		}

		/**
   * Render the display settings dropdown and any extra contextual settings.
   */

	}, {
		key: "render",
		value: function render() {
			var extra_settings = null;
			if ('specific' === this.state.display) {
				extra_settings = wp.element.createElement(ProductsSpecificSelect, null);
			} else if ('category' === this.state.display) {
				extra_settings = wp.element.createElement(ProductsCategorySelect, null);
			}

			return wp.element.createElement(
				"div",
				{ className: "wc-product-display-settings" },
				wp.element.createElement(
					"h3",
					null,
					__('Products')
				),
				wp.element.createElement(
					"select",
					{ value: this.state.display, onChange: this.updateDisplay },
					wp.element.createElement(
						"option",
						{ value: "all" },
						__('All')
					),
					wp.element.createElement(
						"option",
						{ value: "specific" },
						__('Specific products')
					),
					wp.element.createElement(
						"option",
						{ value: "category" },
						__('Product Category')
					)
				),
				extra_settings
			);
		}
	}]);

	return ProductsBlockSettingsEditor;
}(React.Component);

/**
 * The products block when in Preview mode.
 *
 * @todo This will need to be converted to pull dynamic data from the API for the preview similar to https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/.
 */


var ProductsBlockPreview = function (_React$Component4) {
	_inherits(ProductsBlockPreview, _React$Component4);

	function ProductsBlockPreview() {
		_classCallCheck(this, ProductsBlockPreview);

		return _possibleConstructorReturn(this, (ProductsBlockPreview.__proto__ || Object.getPrototypeOf(ProductsBlockPreview)).apply(this, arguments));
	}

	_createClass(ProductsBlockPreview, [{
		key: "render",
		value: function render() {
			return wp.element.createElement(
				"div",
				null,
				"PREVIEWING"
			);
		}
	}]);

	return ProductsBlockPreview;
}(React.Component);

/**
 * Register and run the products block.
 */


registerBlockType('woocommerce/products', {
	title: __('Products'),
	icon: 'universal-access-alt', // @todo Needs a good icon.
	category: 'widgets',

	attributes: {

		/**
   * Layout to use. 'grid' or 'list'.
   */
		layout: {
			type: 'string',
			default: 'grid'
		},

		/**
   * Number of columns.
   */
		columns: {
			type: 'number',
			default: 3
		},

		/**
   * Number of rows.
   */
		rows: {
			type: 'number',
			default: 1
		},

		/**
   * Whether to display product titles.
   */
		display_title: {
			type: 'boolean',
			default: true
		},

		/**
   * Whether to display prices.
   */
		display_price: {
			type: 'boolean',
			default: true
		},

		/**
   * Whether to display Add to Cart buttons.
   */
		display_add_to_cart: {
			type: 'boolean',
			default: false
		},

		/**
   * Order to use for products. 'newness', 'title', or 'best-selling'.
   */
		order: {
			type: 'string',
			default: 'newness'
		},

		/**
   * What types of products to display. 'all', 'specific', or 'category'.
   */
		display: {
			type: 'string',
			default: 'all'
		},

		/**
   * Which products to display if 'display' is 'specific' or 'category'. Array of product ids or category slugs depending on setting.
   */
		display_setting: {
			type: 'array',
			default: []
		},

		/**
   * Whether the block is in edit or preview mode.
   */
		edit_mode: {
			type: 'boolean',
			default: true
		}
	},

	/**
  * Renders and manages the block.
  */
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
		    order = attributes.order,
		    display = attributes.display,
		    display_setting = attributes.display_setting,
		    edit_mode = attributes.edit_mode;

		/**
   * Get the components for the sidebar settings area that is rendered while focused on a Products block.
   *
   * @return Component
   */

		function getInspectorControls() {
			return wp.element.createElement(
				InspectorControls,
				{ key: "inspector" },
				wp.element.createElement(
					"h3",
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
					key: "query-panel-select",
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

		/**
   * Get the components for the toolbar area that appears on top of the block when focused.
   *
   * @return Component
   */
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

			var editButton = [{
				icon: 'edit',
				title: __('Edit'),
				onClick: function onClick() {
					return setAttributes({ edit_mode: !edit_mode });
				},
				isActive: edit_mode
			}];

			return wp.element.createElement(
				BlockControls,
				{ key: "controls" },
				wp.element.createElement(Toolbar, { controls: layoutControls }),
				wp.element.createElement(Toolbar, { controls: editButton })
			);
		}

		/**
   * Get the block preview component for preview mode.
   *
   * @return Component
   */
		function getPreview() {
			return wp.element.createElement(ProductsBlockPreview, { selected_attributes: attributes });
		}

		/**
   * Get the block edit component for edit mode.
   *
   * @return Component
   */
		function getSettingsEditor() {
			return wp.element.createElement(ProductsBlockSettingsEditor, { selected_display: display, update_display_callback: function update_display_callback(value) {
					return setAttributes({ display: value });
				} });
		}

		return [!!focus ? getInspectorControls() : null, !!focus ? getToolbarControls() : null, edit_mode ? getSettingsEditor() : getPreview()];
	},


	/**
  * Save the block content in the post content.
  *
  * @return string
  */
	save: function save() {
		// @todo
		// This can either build a shortcode out of all the settings (good backwards compatibility but may need extra shortcode work)
		// Or it can return null and the html can be generated with PHP like the example at https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/
		return '[products limit="3"]';
	}
});

/***/ })
/******/ ]);