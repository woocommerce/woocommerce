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


var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _specificSelect = __webpack_require__(1);

var _categorySelect = __webpack_require__(2);

var _attributeSelect = __webpack_require__(3);

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
    withAPIData = _wp$components.withAPIData,
    Dropdown = _wp$components.Dropdown;
var RangeControl = InspectorControls.RangeControl,
    ToggleControl = InspectorControls.ToggleControl,
    SelectControl = InspectorControls.SelectControl;


/**
 * A setting has the following properties:
 *    title - Display title of the setting.
 *    description - Display description of the setting.
 *    value - Display setting slug to set when selected.
 *    group_container - (optional) If set the setting is a parent container.
 */
var PRODUCTS_BLOCK_DISPLAY_SETTINGS = {
	'specific': {
		title: __('Individual products'),
		description: __('Hand-pick which products to display'),
		value: 'specific'
	},
	'category': {
		title: __('Product category'),
		description: __('Display products from a specific category or multiple categories'),
		value: 'category'
	},
	'filter': {
		title: __('Filter products'),
		description: __('E.g. featured products, or products with a specific attribute like size or color'),
		value: 'filter',
		group_container: 'filter'
	},
	'featured': {
		title: __('Featured products'),
		description: '',
		value: 'featured'
	},
	'best_sellers': {
		title: __('Best sellers'),
		description: '',
		value: 'best_sellers'
	},
	'best_rated': {
		title: __('Best rated'),
		description: '',
		value: 'best_rated'
	},
	'on_sale': {
		title: __('On sale'),
		description: '',
		value: 'on_sale'
	},
	'attribute': {
		title: __('Attribute'),
		description: '',
		value: 'attribute'
	},
	'all': {
		title: __('All products'),
		description: __('Display all products ordered chronologically'),
		value: 'all'
	}
};

/**
 * One option from the list of all available ways to display products.
 */

var ProductsBlockSettingsEditorDisplayOption = function (_React$Component) {
	_inherits(ProductsBlockSettingsEditorDisplayOption, _React$Component);

	function ProductsBlockSettingsEditorDisplayOption() {
		_classCallCheck(this, ProductsBlockSettingsEditorDisplayOption);

		return _possibleConstructorReturn(this, (ProductsBlockSettingsEditorDisplayOption.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditorDisplayOption)).apply(this, arguments));
	}

	_createClass(ProductsBlockSettingsEditorDisplayOption, [{
		key: 'render',
		value: function render() {
			var _this2 = this;

			return wp.element.createElement(
				'div',
				{ className: 'wc-products-display-option value-' + this.props.value, onClick: function onClick() {
						_this2.props.update_display_callback(_this2.props.value);
					} },
				wp.element.createElement(
					'h4',
					null,
					this.props.title
				),
				wp.element.createElement(
					'p',
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


var ProductsBlockSettingsEditorDisplayOptions = function (_React$Component2) {
	_inherits(ProductsBlockSettingsEditorDisplayOptions, _React$Component2);

	function ProductsBlockSettingsEditorDisplayOptions() {
		_classCallCheck(this, ProductsBlockSettingsEditorDisplayOptions);

		return _possibleConstructorReturn(this, (ProductsBlockSettingsEditorDisplayOptions.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditorDisplayOptions)).apply(this, arguments));
	}

	_createClass(ProductsBlockSettingsEditorDisplayOptions, [{
		key: 'render',
		value: function render() {
			var classes = 'display-settings-container';
			if (this.props.existing) {
				classes += ' existing';
			}

			var display_settings = [];
			for (var setting_key in PRODUCTS_BLOCK_DISPLAY_SETTINGS) {
				display_settings.push(wp.element.createElement(ProductsBlockSettingsEditorDisplayOption, _extends({}, PRODUCTS_BLOCK_DISPLAY_SETTINGS[setting_key], { update_display_callback: this.props.update_display_callback })));
			}

			var description = null;
			if (!this.props.existing) {
				description = wp.element.createElement(
					'p',
					null,
					__('Choose which products you\'d like to display:')
				);
			}

			return wp.element.createElement(
				'div',
				{ className: classes },
				description,
				display_settings
			);
		}
	}]);

	return ProductsBlockSettingsEditorDisplayOptions;
}(React.Component);

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

		var _this4 = _possibleConstructorReturn(this, (ProductsBlockSettingsEditor.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditor)).call(this, props));

		_this4.state = {
			display: props.selected_display,
			menu_visible: props.selected_display ? false : true,
			expanded_group: ''
		};

		_this4.updateDisplay = _this4.updateDisplay.bind(_this4);
		return _this4;
	}

	/**
  * Update the display settings for the block.
  *
  * @param value String
  */


	_createClass(ProductsBlockSettingsEditor, [{
		key: 'updateDisplay',
		value: function updateDisplay(value) {

			// If not a group update display.
			var new_state = {
				display: value,
				menu_visible: false,
				expanded_group: ''
			};

			var is_group = 'undefined' !== PRODUCTS_BLOCK_DISPLAY_SETTINGS[value].group_container && PRODUCTS_BLOCK_DISPLAY_SETTINGS[value].group_container;

			if (is_group) {
				// If the group has not been expanded, expand it.
				new_state = {
					menu_visible: true,
					expanded_group: value

					// If the group has already been expanded, collapse it.
				};if (this.state.expanded_group === PRODUCTS_BLOCK_DISPLAY_SETTINGS[value].group_container) {
					new_state.expanded_group = '';
				}
			}

			this.setState(new_state);

			// Only update the display setting if a non-group setting was selected.
			if (!is_group) {
				this.props.update_display_callback(value);
			}
		}

		/**
   * Render the display settings dropdown and any extra contextual settings.
   */

	}, {
		key: 'render',
		value: function render() {
			var _this5 = this;

			var extra_settings = null;
			if ('specific' === this.state.display) {
				extra_settings = wp.element.createElement(_specificSelect.ProductsSpecificSelect, this.props);
			} else if ('category' === this.state.display) {
				extra_settings = wp.element.createElement(_categorySelect.ProductsCategorySelect, this.props);
			} else if ('attribute' === this.state.display) {
				extra_settings = wp.element.createElement(_attributeSelect.ProductsAttributeSelect, null);
			}

			var menu = this.state.menu_visible ? wp.element.createElement(ProductsBlockSettingsEditorDisplayOptions, { existing: this.state.display ? true : false, update_display_callback: this.updateDisplay }) : null;

			var heading = null;
			if (this.state.display) {
				var menu_link = wp.element.createElement(
					'a',
					{ onClick: function onClick() {
							_this5.setState({ menu_visible: true });
						} },
					__('Display different products')
				);
				if (this.state.menu_visible) {
					menu_link = wp.element.createElement(
						'a',
						{ onClick: function onClick() {
								_this5.setState({ menu_visible: false });
							} },
						__('Cancel')
					);
				}

				heading = wp.element.createElement(
					'div',
					{ className: 'settings-heading' },
					wp.element.createElement(
						'div',
						{ className: 'currently-displayed' },
						__('Displaying '),
						wp.element.createElement(
							'strong',
							null,
							__(PRODUCTS_BLOCK_DISPLAY_SETTINGS[this.state.display].title)
						)
					),
					wp.element.createElement(
						'div',
						{ className: 'change-display' },
						menu_link
					)
				);
			}

			return wp.element.createElement(
				'div',
				{ className: 'wc-product-display-settings ' + (this.state.expanded_group ? 'expanded-group-' + this.state.expanded_group : '') },
				wp.element.createElement(
					'h4',
					null,
					__('Products')
				),
				heading,
				menu,
				extra_settings,
				wp.element.createElement(
					'div',
					{ className: 'block-footer' },
					wp.element.createElement(
						'button',
						{ type: 'button', className: 'button button-large', onClick: this.props.done_callback },
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


var ProductPreview = function (_React$Component4) {
	_inherits(ProductPreview, _React$Component4);

	function ProductPreview() {
		_classCallCheck(this, ProductPreview);

		return _possibleConstructorReturn(this, (ProductPreview.__proto__ || Object.getPrototypeOf(ProductPreview)).apply(this, arguments));
	}

	_createClass(ProductPreview, [{
		key: 'render',
		value: function render() {
			var _props = this.props,
			    attributes = _props.attributes,
			    product = _props.product;


			var image = null;
			if (product.images.length) {
				image = wp.element.createElement('img', { src: product.images[0].src });
			}

			var title = null;
			if (attributes.display_title) {
				title = wp.element.createElement(
					'div',
					{ className: 'product-title' },
					product.name
				);
			}

			var price = null;
			if (attributes.display_price) {
				price = wp.element.createElement(
					'div',
					{ className: 'product-price' },
					product.price
				);
			}

			var add_to_cart = null;
			if (attributes.display_add_to_cart) {
				add_to_cart = wp.element.createElement(
					'span',
					{ className: 'product-add-to-cart' },
					__('Add to cart')
				);
			}

			return wp.element.createElement(
				'div',
				{ className: 'product-preview' },
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


var ProductsBlockPreview = withAPIData(function (_ref) {
	var attributes = _ref.attributes;
	var columns = attributes.columns,
	    rows = attributes.rows,
	    order = attributes.order,
	    display = attributes.display,
	    display_setting = attributes.display_setting,
	    layout = attributes.layout;


	var query = {
		per_page: 'list' === layout ? rows : rows * columns,
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
})(function (_ref2) {
	var products = _ref2.products,
	    attributes = _ref2.attributes;


	if (!products.data) {
		return __('Loading');
	}

	if (0 === products.data.length) {
		return __('No products found');
	}

	var classes = "wc-products-block-preview " + attributes.layout + " cols-" + attributes.columns;

	return wp.element.createElement(
		'div',
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
				{ key: 'controls' },
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
  * Save the block content in the post content. Block content is saved as a products shortcode.
  *
  * @return string
  */
	save: function save(props) {
		var _props$attributes = props.attributes,
		    layout = _props$attributes.layout,
		    rows = _props$attributes.rows,
		    columns = _props$attributes.columns,
		    display_title = _props$attributes.display_title,
		    display_price = _props$attributes.display_price,
		    display_add_to_cart = _props$attributes.display_add_to_cart,
		    order = _props$attributes.order,
		    display = _props$attributes.display,
		    display_setting = _props$attributes.display_setting,
		    className = _props$attributes.className;


		var shortcode_atts = new Map();
		shortcode_atts.set('orderby', order);
		shortcode_atts.set('limit', 'grid' === layout ? rows * columns : rows);
		shortcode_atts.set('class', 'list' === layout ? className + ' list-layout' : className);

		if ('grid' === layout) {
			shortcode_atts.set('columns', columns);
		}

		if (!display_title) {
			shortcode_atts.set('show_title', 0);
		}

		if (!display_price) {
			shortcode_atts.set('show_price', 0);
		}

		if (!display_add_to_cart) {
			shortcode_atts.set('show_add_to_cart', 0);
		}

		if ('specific' === display) {
			shortcode_atts.set('include', display_setting.join(','));
		}

		if ('category' === display) {
			shortcode_atts.set('category', display_setting.join(','));
		}

		// Build the shortcode string out of the set shortcode attributes.
		var shortcode = '[products';
		var _iteratorNormalCompletion2 = true;
		var _didIteratorError2 = false;
		var _iteratorError2 = undefined;

		try {
			for (var _iterator2 = shortcode_atts[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
				var _ref3 = _step2.value;

				var _ref4 = _slicedToArray(_ref3, 2);

				var key = _ref4[0];
				var value = _ref4[1];

				shortcode += ' ' + key + '="' + value + '"';
			}
		} catch (err) {
			_didIteratorError2 = true;
			_iteratorError2 = err;
		} finally {
			try {
				if (!_iteratorNormalCompletion2 && _iterator2.return) {
					_iterator2.return();
				}
			} finally {
				if (_didIteratorError2) {
					throw _iteratorError2;
				}
			}
		}

		shortcode += ']';

		return shortcode;
	}
});

/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
	value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __ = wp.i18n.__;
var _wp$components = wp.components,
    Toolbar = _wp$components.Toolbar,
    withAPIData = _wp$components.withAPIData,
    Dropdown = _wp$components.Dropdown;

/**
 * When the display mode is 'Specific products' search for and add products to the block.
 *
 * @todo Add the functionality and everything.
 */

var ProductsSpecificSelect = exports.ProductsSpecificSelect = function (_React$Component) {
	_inherits(ProductsSpecificSelect, _React$Component);

	/**
  * Constructor.
  */
	function ProductsSpecificSelect(props) {
		_classCallCheck(this, ProductsSpecificSelect);

		var _this = _possibleConstructorReturn(this, (ProductsSpecificSelect.__proto__ || Object.getPrototypeOf(ProductsSpecificSelect)).call(this, props));

		_this.state = {
			selectedProducts: props.selected_display_setting || []
		};
		return _this;
	}

	_createClass(ProductsSpecificSelect, [{
		key: "addProduct",
		value: function addProduct(id) {
			var selectedProducts = this.state.selectedProducts;
			selectedProducts.push(id);

			this.setState({
				selectedProducts: selectedProducts
			});

			this.props.update_display_setting_callback(selectedProducts);
		}
	}, {
		key: "removeProduct",
		value: function removeProduct(id) {
			var oldProducts = this.state.selectedProducts;
			var newProducts = [];

			var _iteratorNormalCompletion = true;
			var _didIteratorError = false;
			var _iteratorError = undefined;

			try {
				for (var _iterator = oldProducts[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
					var productId = _step.value;

					if (productId !== id) {
						newProducts.push(productId);
					}
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

			this.setState({
				selectedProducts: newProducts
			});

			this.props.update_display_setting_callback(newProducts);
		}
	}, {
		key: "render",
		value: function render() {
			return wp.element.createElement(
				"div",
				{ className: "product-specific-select" },
				wp.element.createElement(ProductsSpecificSearchField, { addProductCallback: this.addProduct.bind(this) }),
				wp.element.createElement(ProductSpecificSelectedProducts, { products: this.state.selectedProducts, removeProductCallback: this.removeProduct.bind(this) })
			);
		}
	}]);

	return ProductsSpecificSelect;
}(React.Component);

/**
 * Product search area
 */


var ProductsSpecificSearchField = function (_React$Component2) {
	_inherits(ProductsSpecificSearchField, _React$Component2);

	function ProductsSpecificSearchField(props) {
		_classCallCheck(this, ProductsSpecificSearchField);

		var _this2 = _possibleConstructorReturn(this, (ProductsSpecificSearchField.__proto__ || Object.getPrototypeOf(ProductsSpecificSearchField)).call(this, props));

		_this2.state = {
			searchText: ''
		};

		_this2.updateSearchResults = _this2.updateSearchResults.bind(_this2);
		return _this2;
	}

	_createClass(ProductsSpecificSearchField, [{
		key: "updateSearchResults",
		value: function updateSearchResults(evt) {
			this.setState({
				searchText: evt.target.value
			});
		}
	}, {
		key: "render",
		value: function render() {
			var _this3 = this;

			return wp.element.createElement(
				"div",
				{ className: "product-search" },
				wp.element.createElement("input", { type: "text", value: this.state.searchText, placeholder: __('Search for products to display'), onChange: this.updateSearchResults }),
				wp.element.createElement(
					"span",
					{ className: "cancel", onClick: function onClick() {
							_this3.setState({ searchText: '' });
						} },
					"X"
				),
				wp.element.createElement(ProductSpecificSearchResults, { searchString: this.state.searchText, addProductCallback: this.props.addProductCallback })
			);
		}
	}]);

	return ProductsSpecificSearchField;
}(React.Component);

/**
 * Product search results based on the text entered into the textbox.
 */


var ProductSpecificSearchResults = withAPIData(function (props) {

	if (!props.searchString.length) {
		return {
			products: []
		};
	}

	return {
		products: '/wc/v2/products?per_page=10&search=' + props.searchString
	};
})(function (_ref) {
	var products = _ref.products,
	    addProductCallback = _ref.addProductCallback;

	if (!products.data) {
		return null;
	}

	if (0 === products.data.length) {
		return __('No products found');
	}

	return wp.element.createElement(
		"div",
		{ role: "menu", className: "product-search-results", "aria-orientation": "vertical", "aria-label": "{ __( 'Products list' ) }" },
		wp.element.createElement(
			"ul",
			null,
			products.data.map(function (product) {
				return wp.element.createElement(
					"li",
					null,
					wp.element.createElement(
						"button",
						{ type: "button", className: "components-button", id: 'product-' + product.id, onClick: function onClick() {
								addProductCallback(product.id);
							} },
						wp.element.createElement("img", { src: product.images[0].src, width: "30px" }),
						wp.element.createElement(
							"span",
							{ className: "product-name" },
							product.name
						),
						wp.element.createElement(
							"a",
							null,
							__('Add')
						)
					)
				);
			})
		)
	);
});

/**
 * List preview of selected products.
 */
var ProductSpecificSelectedProducts = withAPIData(function (props) {

	if (!props.products.length) {
		return {
			products: []
		};
	}

	return {
		products: '/wc/v2/products?include=' + props.products.join(',')
	};
})(function (_ref2) {
	var products = _ref2.products,
	    removeProductCallback = _ref2.removeProductCallback;

	if (!products.data) {
		return null;
	}

	if (0 === products.data.length) {
		return __('No products selected');
	}

	return wp.element.createElement(
		"div",
		{ role: "menu", className: "selected-products", "aria-orientation": "vertical", "aria-label": "{ __( 'Products list' ) }" },
		wp.element.createElement(
			"ul",
			null,
			products.data.map(function (product) {
				return wp.element.createElement(
					"li",
					null,
					wp.element.createElement(
						"button",
						{ type: "button", className: "components-button", id: 'product-' + product.id },
						wp.element.createElement("img", { src: product.images[0].src, width: "30px" }),
						wp.element.createElement(
							"span",
							{ className: "product-name" },
							product.name
						),
						wp.element.createElement(
							"a",
							{ onClick: function onClick() {
									removeProductCallback(product.id);
								} },
							__('X')
						)
					)
				);
			})
		)
	);
});

/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
	value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __ = wp.i18n.__;
var _wp$components = wp.components,
    Toolbar = _wp$components.Toolbar,
    withAPIData = _wp$components.withAPIData,
    Dropdown = _wp$components.Dropdown,
    Dashicon = _wp$components.Dashicon;

/**
 * When the display mode is 'Product category' search for and select product categories to pull products from.
 */

var ProductsCategorySelect = exports.ProductsCategorySelect = function (_React$Component) {
	_inherits(ProductsCategorySelect, _React$Component);

	/**
  * Constructor.
  */
	function ProductsCategorySelect(props) {
		_classCallCheck(this, ProductsCategorySelect);

		var _this = _possibleConstructorReturn(this, (ProductsCategorySelect.__proto__ || Object.getPrototypeOf(ProductsCategorySelect)).call(this, props));

		_this.state = {
			selectedCategories: props.selected_display_setting,
			openAccordion: null,
			filterQuery: ''
		};

		_this.checkboxChange = _this.checkboxChange.bind(_this);
		_this.accordionToggle = _this.accordionToggle.bind(_this);
		_this.filterResults = _this.filterResults.bind(_this);
		return _this;
	}

	/**
  * Handle checkbox toggle.
  *
  * @param Checked? boolean checked
  * @param Categories array categories
  */


	_createClass(ProductsCategorySelect, [{
		key: "checkboxChange",
		value: function checkboxChange(checked, categories) {
			var selectedCategories = this.state.selectedCategories;

			selectedCategories = selectedCategories.filter(function (category) {
				return !categories.includes(category);
			});

			if (checked) {
				var _selectedCategories;

				(_selectedCategories = selectedCategories).push.apply(_selectedCategories, _toConsumableArray(categories));
			}

			this.setState({
				selectedCategories: selectedCategories
			});

			this.props.update_display_setting_callback(selectedCategories);
		}

		/**
   * Handle accordion toggle.
   *
   * @param Category ID category
   */

	}, {
		key: "accordionToggle",
		value: function accordionToggle(category) {
			var value = category;

			if (value === this.state.openAccordion) {
				value = null;
			}

			this.setState({
				openAccordion: value
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
				{ className: "product-category-select" },
				wp.element.createElement(ProductCategoryFilter, { filterResults: this.filterResults }),
				wp.element.createElement(ProductCategoryList, {
					filterQuery: this.state.filterQuery,
					selectedCategories: this.state.selectedCategories,
					checkboxChange: this.checkboxChange,
					accordionToggle: this.accordionToggle,
					openAccordion: this.state.openAccordion
				})
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
	    checkboxChange = _ref2.checkboxChange,
	    accordionToggle = _ref2.accordionToggle,
	    openAccordion = _ref2.openAccordion;

	if (!categories.data) {
		return __('Loading');
	}

	if (0 === categories.data.length) {
		return __('No categories found');
	}

	var handleCategoriesToCheck = function handleCategoriesToCheck(evt, parent, categories) {
		var ids = getCategoryChildren(parent, categories).map(function (category) {
			return category.id;
		});

		ids.push(parent.id);

		checkboxChange(evt.target.checked, ids);
	};

	var getCategoryChildren = function getCategoryChildren(parent, categories) {
		var children = [];

		categories.filter(function (category) {
			return category.parent === parent.id;
		}).forEach(function (category) {
			children.push(category);
			children.push.apply(children, _toConsumableArray(getCategoryChildren(category, categories)));
		});

		return children;
	};

	var categoryHasChildren = function categoryHasChildren(parent, categories) {
		return !!getCategoryChildren(parent, categories).length;
	};

	var AccordionButton = function AccordionButton(_ref3) {
		var category = _ref3.category,
		    categories = _ref3.categories;

		var icon = 'arrow-down-alt2';

		if (openAccordion === category.id) {
			icon = 'arrow-up-alt2';
		}

		var style = null;

		if (!categoryHasChildren(category, categories)) {
			style = {
				visibility: 'hidden'
			};
		};

		return wp.element.createElement(
			"button",
			{ onClick: function onClick() {
					return accordionToggle(category.id);
				}, style: style, type: "button", className: "product-category-accordion-toggle" },
			wp.element.createElement(Dashicon, { icon: icon })
		);
	};

	var CategoryTree = function CategoryTree(_ref4) {
		var categories = _ref4.categories,
		    parent = _ref4.parent;

		var filteredCategories = categories.filter(function (category) {
			return category.parent === parent;
		});

		return filteredCategories.length > 0 && wp.element.createElement(
			"ul",
			null,
			filteredCategories.map(function (category) {
				return wp.element.createElement(
					"li",
					{ key: category.id, className: openAccordion === category.id ? 'product-category-accordion-open' : '' },
					wp.element.createElement(
						"label",
						{ htmlFor: 'product-category-' + category.id },
						wp.element.createElement("input", { type: "checkbox",
							id: 'product-category-' + category.id,
							value: category.id,
							checked: selectedCategories.includes(category.id),
							onChange: function onChange(evt) {
								return handleCategoriesToCheck(evt, category, categories);
							}
						}),
						" ",
						category.name,
						wp.element.createElement(
							"span",
							{ className: "product-category-count" },
							category.count
						),
						0 === category.parent && wp.element.createElement(AccordionButton, { category: category, categories: categories })
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
		{ className: "product-categories-list" },
		wp.element.createElement(CategoryTree, { categories: categoriesData, parent: 0 })
	);
});

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
	value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __ = wp.i18n.__;
var _wp$components = wp.components,
    Toolbar = _wp$components.Toolbar,
    withAPIData = _wp$components.withAPIData,
    Dropdown = _wp$components.Dropdown;

/**
 * When the display mode is 'Attribute' search for and select product attributes to pull products from.
 */

var ProductsAttributeSelect = exports.ProductsAttributeSelect = function (_React$Component) {
	_inherits(ProductsAttributeSelect, _React$Component);

	function ProductsAttributeSelect() {
		_classCallCheck(this, ProductsAttributeSelect);

		return _possibleConstructorReturn(this, (ProductsAttributeSelect.__proto__ || Object.getPrototypeOf(ProductsAttributeSelect)).apply(this, arguments));
	}

	_createClass(ProductsAttributeSelect, [{
		key: "render",
		value: function render() {
			return wp.element.createElement(
				"div",
				{ className: "product-attribute-select" },
				"TODO: Attribute select screen"
			);
		}
	}]);

	return ProductsAttributeSelect;
}(React.Component);

/***/ })
/******/ ]);