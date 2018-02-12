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


var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

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
			selectedCategories: props.selected_display_setting,
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

			this.props.update_display_setting_callback(selectedCategories);
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
				{ className: "product-category-select" },
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
					{ key: category.id },
					wp.element.createElement(
						"label",
						{ htmlFor: 'product-category-' + category.id },
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
 * One option from the list of all available ways to display products.
 */

var ProductsBlockSettingsEditorDisplayOption = function (_React$Component3) {
	_inherits(ProductsBlockSettingsEditorDisplayOption, _React$Component3);

	function ProductsBlockSettingsEditorDisplayOption() {
		_classCallCheck(this, ProductsBlockSettingsEditorDisplayOption);

		return _possibleConstructorReturn(this, (ProductsBlockSettingsEditorDisplayOption.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditorDisplayOption)).apply(this, arguments));
	}

	_createClass(ProductsBlockSettingsEditorDisplayOption, [{
		key: "render",
		value: function render() {
			var _this4 = this;

			return wp.element.createElement(
				"div",
				{ className: "wc-products-display-option", onClick: function onClick() {
						_this4.props.update_display_callback(_this4.props.value);
					} },
				wp.element.createElement(
					"h4",
					null,
					this.props.title
				),
				wp.element.createElement(
					"p",
					null,
					this.props.description
				)
			);
		}
	}]);

	return ProductsBlockSettingsEditorDisplayOption;
}(React.Component);

/**
 * A list of all available ways to display products.
 */


var ProductsBlockSettingsEditorDisplayOptions = function (_React$Component4) {
	_inherits(ProductsBlockSettingsEditorDisplayOptions, _React$Component4);

	function ProductsBlockSettingsEditorDisplayOptions() {
		_classCallCheck(this, ProductsBlockSettingsEditorDisplayOptions);

		return _possibleConstructorReturn(this, (ProductsBlockSettingsEditorDisplayOptions.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditorDisplayOptions)).apply(this, arguments));
	}

	_createClass(ProductsBlockSettingsEditorDisplayOptions, [{
		key: "render",
		value: function render() {
			var _this6 = this;

			var products_block_display_settings = [{
				title: __('All'),
				description: __('All products'),
				value: 'all'
			}, {
				title: __('Specific'),
				description: __('Hand-picked products'),
				value: 'specific'
			}, {
				title: __('Category'),
				description: __('Products from a specific category'),
				value: 'category'
			}];

			var classes = 'display-settings-container';
			if (this.props.existing) {
				classes += ' existing';
			}

			return wp.element.createElement(
				"div",
				{ className: classes },
				wp.element.createElement(
					"p",
					null,
					__('Select the scope for products to display:')
				),
				products_block_display_settings.map(function (setting) {
					return wp.element.createElement(ProductsBlockSettingsEditorDisplayOption, _extends({}, setting, { update_display_callback: _this6.props.update_display_callback }));
				})
			);
		}
	}]);

	return ProductsBlockSettingsEditorDisplayOptions;
}(React.Component);

/**
 * The products block when in Edit mode.
 */


var ProductsBlockSettingsEditor = function (_React$Component5) {
	_inherits(ProductsBlockSettingsEditor, _React$Component5);

	/**
  * Constructor.
  */
	function ProductsBlockSettingsEditor(props) {
		_classCallCheck(this, ProductsBlockSettingsEditor);

		var _this7 = _possibleConstructorReturn(this, (ProductsBlockSettingsEditor.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditor)).call(this, props));

		_this7.state = {
			display: props.selected_display,
			menu_visible: props.selected_display ? false : true
		};

		_this7.updateDisplay = _this7.updateDisplay.bind(_this7);
		return _this7;
	}

	/**
  * Update the display settings for the block.
  *
  * @param Event object evt
  */


	_createClass(ProductsBlockSettingsEditor, [{
		key: "updateDisplay",
		value: function updateDisplay(value) {
			this.setState({
				display: value,
				menu_visible: false
			});

			this.props.update_display_callback(value);
		}

		/**
   * Render the display settings dropdown and any extra contextual settings.
   */

	}, {
		key: "render",
		value: function render() {
			var _this8 = this;

			var extra_settings = null;
			if ('specific' === this.state.display) {
				extra_settings = wp.element.createElement(ProductsSpecificSelect, null);
			} else if ('category' === this.state.display) {
				extra_settings = wp.element.createElement(ProductsCategorySelect, this.props);
			}

			var menu = this.state.menu_visible ? wp.element.createElement(ProductsBlockSettingsEditorDisplayOptions, { existing: this.state.display ? true : false, update_display_callback: this.updateDisplay }) : null;

			var heading = wp.element.createElement(
				"h4",
				null,
				__('Products')
			);
			if (this.state.display && !this.state.menu_visible) {
				heading = wp.element.createElement(
					"h4",
					null,
					__('Displaying ' + this.state.display),
					" ",
					wp.element.createElement(
						"a",
						{ onClick: function onClick() {
								_this8.setState({ menu_visible: true });
							} },
						__('Change')
					)
				);
			} else if (this.state.display) {
				heading = wp.element.createElement(
					"h4",
					null,
					__('Displaying ' + this.state.display),
					" ",
					wp.element.createElement(
						"a",
						{ onClick: function onClick() {
								_this8.setState({ menu_visible: false });
							} },
						__('Cancel')
					)
				);
			}

			return wp.element.createElement(
				"div",
				{ className: "wc-product-display-settings" },
				heading,
				menu,
				extra_settings,
				wp.element.createElement(
					"div",
					{ className: "block-footer" },
					wp.element.createElement(
						"button",
						{ type: "button", className: "button button-large", onClick: this.props.done_callback },
						__('Done')
					)
				)
			);
		}
	}]);

	return ProductsBlockSettingsEditor;
}(React.Component);

/**
 * One product in the product block preview.
 */


var ProductPreview = function (_React$Component6) {
	_inherits(ProductPreview, _React$Component6);

	function ProductPreview() {
		_classCallCheck(this, ProductPreview);

		return _possibleConstructorReturn(this, (ProductPreview.__proto__ || Object.getPrototypeOf(ProductPreview)).apply(this, arguments));
	}

	_createClass(ProductPreview, [{
		key: "render",
		value: function render() {
			var _props = this.props,
			    attributes = _props.attributes,
			    product = _props.product;


			var image = null;
			if (product.images.length) {
				image = wp.element.createElement("img", { src: product.images[0].src });
			}

			var title = null;
			if (attributes.display_title) {
				title = wp.element.createElement(
					"div",
					{ className: "product-title" },
					product.name
				);
			}

			var price = null;
			if (attributes.display_price) {
				price = wp.element.createElement(
					"div",
					{ className: "product-price" },
					product.price
				);
			}

			var add_to_cart = null;
			if (attributes.display_add_to_cart) {
				add_to_cart = wp.element.createElement(
					"span",
					{ className: "product-add-to-cart" },
					__('Add to cart')
				);
			}

			return wp.element.createElement(
				"div",
				{ className: "product-preview" },
				image,
				title,
				price,
				add_to_cart
			);
		}
	}]);

	return ProductPreview;
}(React.Component);

/**
 * Renders a preview of what the block will look like with current settings.
 */


var ProductsBlockPreview = withAPIData(function (_ref4) {
	var attributes = _ref4.attributes;
	var columns = attributes.columns,
	    rows = attributes.rows,
	    order = attributes.order,
	    display = attributes.display,
	    display_setting = attributes.display_setting,
	    layout = attributes.layout;


	var query = {
		per_page: 'list' === layout ? columns : rows * columns,
		orderby: order
	};

	// @todo These will likely need to be modified to work with the final version of the category/product picker attributes.
	if ('specific' === display) {
		query.include = JSON.stringify(display_setting);
		query.orderby = 'include';
	} else if ('category' === display) {
		query.category = display_setting.join(',');
	}

	var query_string = '?';
	var _iteratorNormalCompletion = true;
	var _didIteratorError = false;
	var _iteratorError = undefined;

	try {
		for (var _iterator = Object.keys(query)[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
			var key = _step.value;

			query_string += key + '=' + query[key] + '&';
		}
	} catch (err) {
		_didIteratorError = true;
		_iteratorError = err;
	} finally {
		try {
			if (!_iteratorNormalCompletion && _iterator.return) {
				_iterator.return();
			}
		} finally {
			if (_didIteratorError) {
				throw _iteratorError;
			}
		}
	}

	return {
		products: '/wc/v2/products' + query_string
	};
})(function (_ref5) {
	var products = _ref5.products,
	    attributes = _ref5.attributes;


	if (!products.data) {
		return __('Loading');
	}

	if (0 === products.data.length) {
		return __('No products found');
	}

	var classes = "wc-products-block-preview " + attributes.layout + " cols-" + attributes.columns;

	return wp.element.createElement(
		"div",
		{ className: classes },
		products.data.map(function (product) {
			return wp.element.createElement(ProductPreview, { key: product.id, product: product, attributes: attributes });
		})
	);
});

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
   * Order to use for products. 'date', or 'title'.
   */
		order: {
			type: 'string',
			default: 'date'
		},

		/**
   * What types of products to display. 'all', 'specific', or 'category'.
   */
		display: {
			type: 'string',
			default: ''
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
						value: 'date'
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
			return wp.element.createElement(ProductsBlockPreview, { attributes: attributes });
		}

		/**
   * Get the block edit component for edit mode.
   *
   * @return Component
   */
		function getSettingsEditor() {
			return wp.element.createElement(ProductsBlockSettingsEditor, {
				selected_display: display,
				selected_display_setting: display_setting,
				update_display_callback: function update_display_callback(value) {
					return setAttributes({ display: value });
				},
				update_display_setting_callback: function update_display_setting_callback(value) {
					return setAttributes({ display_setting: value });
				},
				done_callback: function done_callback() {
					return setAttributes({ edit_mode: false });
				}
			});
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