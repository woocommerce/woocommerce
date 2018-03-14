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
var RawHTML = wp.element.RawHTML;
var _wp$blocks = wp.blocks,
    registerBlockType = _wp$blocks.registerBlockType,
    InspectorControls = _wp$blocks.InspectorControls,
    BlockControls = _wp$blocks.BlockControls;
var _wp$components = wp.components,
    Toolbar = _wp$components.Toolbar,
    withAPIData = _wp$components.withAPIData,
    Dropdown = _wp$components.Dropdown,
    Dashicon = _wp$components.Dashicon,
    RangeControl = _wp$components.RangeControl,
    Tooltip = _wp$components.Tooltip;
var ToggleControl = InspectorControls.ToggleControl,
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

			var icon = 'arrow-right-alt2';

			if ('filter' === this.props.value && this.props.extended) {
				icon = 'arrow-down-alt2';
			}

			var classes = 'wc-products-display-options__option wc-products-display-options__option--' + this.props.value;

			if (this.props.current === this.props.value) {
				icon = 'yes';
				classes += ' wc-products-display-options__option--current';
			}

			return wp.element.createElement(
				'div',
				{ className: classes, onClick: function onClick() {
						_this2.props.update_display_callback(_this2.props.value);
					} },
				wp.element.createElement(
					'div',
					{ className: 'wc-products-display-options__option-content' },
					wp.element.createElement(
						'span',
						{ className: 'wc-products-display-options__option-title' },
						this.props.title
					),
					wp.element.createElement(
						'p',
						{ className: 'wc-products-display-options__option-description' },
						this.props.description
					)
				),
				wp.element.createElement(
					'div',
					{ className: 'wc-products-display-options__icon' },
					wp.element.createElement(Dashicon, { icon: icon })
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

	/**
  * Constructor.
  */
	function ProductsBlockSettingsEditorDisplayOptions(props) {
		_classCallCheck(this, ProductsBlockSettingsEditorDisplayOptions);

		var _this3 = _possibleConstructorReturn(this, (ProductsBlockSettingsEditorDisplayOptions.__proto__ || Object.getPrototypeOf(ProductsBlockSettingsEditorDisplayOptions)).call(this, props));

		_this3.setWrapperRef = _this3.setWrapperRef.bind(_this3);
		_this3.handleClickOutside = _this3.handleClickOutside.bind(_this3);
		return _this3;
	}

	/**
  * Hook in the listener for closing menu when clicked outside.
  */


	_createClass(ProductsBlockSettingsEditorDisplayOptions, [{
		key: 'componentDidMount',
		value: function componentDidMount() {
			if (this.props.existing) {
				document.addEventListener('mousedown', this.handleClickOutside);
			}
		}

		/**
   * Remove the listener for closing menu when clicked outside.
   */

	}, {
		key: 'componentWillUnmount',
		value: function componentWillUnmount() {
			if (this.props.existing) {
				document.removeEventListener('mousedown', this.handleClickOutside);
			}
		}

		/**
   * Set the wrapper reference.
   *
   * @param node DOMNode
   */

	}, {
		key: 'setWrapperRef',
		value: function setWrapperRef(node) {
			this.wrapperRef = node;
		}

		/**
   * Close the menu when user clicks outside the search area.
   */

	}, {
		key: 'handleClickOutside',
		value: function handleClickOutside(evt) {
			if (this.wrapperRef && !this.wrapperRef.contains(event.target) && 'wc-products-settings-heading__change-button button-link' !== event.target.getAttribute('class')) {
				this.props.closeMenu();
			}
		}

		/**
   * Render the list of options.
   */

	}, {
		key: 'render',
		value: function render() {
			var classes = 'wc-products-display-options';

			if (this.props.extended) {
				classes += ' wc-products-display-options--extended';
			}

			if (this.props.existing) {
				classes += ' wc-products-display-options--popover';
			}

			var display_settings = [];
			for (var setting_key in PRODUCTS_BLOCK_DISPLAY_SETTINGS) {
				display_settings.push(wp.element.createElement(ProductsBlockSettingsEditorDisplayOption, _extends({}, PRODUCTS_BLOCK_DISPLAY_SETTINGS[setting_key], { update_display_callback: this.props.update_display_callback, extended: this.props.extended, current: this.props.current })));
			}

			var arrow = wp.element.createElement('span', { className: 'wc-products-display-options--popover__arrow' });
			var description = wp.element.createElement(
				'p',
				{ className: 'wc-products-block-description' },
				__('Choose which products you\'d like to display:')
			);

			return wp.element.createElement(
				'div',
				{ className: classes, ref: this.setWrapperRef },
				this.props.existing && arrow,
				!this.props.existing && description,
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
		_this4.closeMenu = _this4.closeMenu.bind(_this4);
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
	}, {
		key: 'closeMenu',
		value: function closeMenu() {
			this.setState({
				menu_visible: false
			});
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
				extra_settings = wp.element.createElement(_attributeSelect.ProductsAttributeSelect, this.props);
			}

			var menu = this.state.menu_visible ? wp.element.createElement(ProductsBlockSettingsEditorDisplayOptions, { extended: this.state.expanded_group ? true : false, existing: this.state.display ? true : false, current: this.state.display, closeMenu: this.closeMenu, update_display_callback: this.updateDisplay }) : null;

			var heading = null;
			if (this.state.display) {
				var group_options = ['featured', 'best_sellers', 'best_rated', 'on_sale', 'attribute'];
				var should_group_expand = group_options.includes(this.state.display ? this.state.display : '');
				var menu_link = wp.element.createElement(
					'button',
					{ type: 'button', className: 'wc-products-settings-heading__change-button button-link', onClick: function onClick() {
							_this5.setState({ menu_visible: !_this5.state.menu_visible, expanded_group: should_group_expand });
						} },
					__('Display different products')
				);

				heading = wp.element.createElement(
					'div',
					{ className: 'wc-products-settings-heading' },
					wp.element.createElement(
						'div',
						{ className: 'wc-products-settings-heading__current' },
						__('Displaying '),
						wp.element.createElement(
							'strong',
							null,
							__(PRODUCTS_BLOCK_DISPLAY_SETTINGS[this.state.display].title)
						)
					),
					wp.element.createElement(
						'div',
						{ className: 'wc-products-settings-heading__change' },
						menu_link
					)
				);
			}

			var done_button = wp.element.createElement(
				'button',
				{ type: 'button', className: 'button wc-products-settings__footer-button', onClick: this.props.done_callback },
				__('Done')
			);
			if (['', 'specific', 'category', 'attribute'].includes(this.state.display) && !this.props.selected_display_setting.length) {
				var done_tooltips = {
					'': __('Please select which products you\'d like to display'),
					specific: __('Please search for and select products to display'),
					category: __('Please select at least one category to display'),
					attribute: __('Please select an attribute')
				};

				done_button = wp.element.createElement(
					Tooltip,
					{ text: done_tooltips[this.state.display] },
					wp.element.createElement(
						'button',
						{ type: 'button', className: 'button wc-products-settings__footer-button disabled' },
						__('Done')
					)
				);
			}

			return wp.element.createElement(
				'div',
				{ className: 'wc-products-settings ' + (this.state.expanded_group ? 'expanded-group-' + this.state.expanded_group : '') },
				wp.element.createElement(
					'h4',
					{ className: 'wc-products-settings__title' },
					wp.element.createElement(Dashicon, { icon: 'universal-access-alt' }),
					' ',
					__('Products')
				),
				heading,
				menu,
				extra_settings,
				wp.element.createElement(
					'div',
					{ className: 'wc-products-settings__footer' },
					done_button
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

			return wp.element.createElement(
				'div',
				{ className: 'product-preview' },
				image,
				wp.element.createElement(
					'div',
					{ className: 'product-title' },
					product.name
				),
				wp.element.createElement('div', { className: 'product-price', dangerouslySetInnerHTML: { __html: product.price_html } }),
				wp.element.createElement(
					'span',
					{ className: 'product-add-to-cart' },
					__('Add to cart')
				)
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
	    display = attributes.display,
	    display_setting = attributes.display_setting,
	    block_layout = attributes.block_layout;


	var query = {
		per_page: 'list' === block_layout ? rows : rows * columns
	};

	if ('specific' === display) {
		query.include = display_setting.join(',');
		query.orderby = 'include';
	} else if ('category' === display) {
		query.category = display_setting.join(',');
	} else if ('attribute' === display && display_setting.length) {
		query.attribute = display_setting[0];

		if (display_setting.length > 1) {
			query.attribute_term = display_setting.slice(1).join(',');
		}
	} else if ('featured' === display) {
		query.featured = 1;
	} else if ('best_sellers' === display) {
		// @todo Not possible in the API yet.
	} else if ('best_rated' === display) {
		// @todo Not possible in the API yet.
	} else if ('on_sale' === display) {
		query.on_sale = 1;
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

	var classes = "wc-products-block-preview " + attributes.block_layout + " cols-" + attributes.columns;

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
		block_layout: {
			type: 'string',
			default: 'grid'
		},

		/**
   * Number of columns.
   */
		columns: {
			type: 'number',
			default: wc_product_block_data.default_columns
		},

		/**
   * Number of rows.
   */
		rows: {
			type: 'number',
			default: 1
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
		var block_layout = attributes.block_layout,
		    rows = attributes.rows,
		    columns = attributes.columns,
		    display = attributes.display,
		    display_setting = attributes.display_setting,
		    edit_mode = attributes.edit_mode;

		/**
   * Get the components for the sidebar settings area that is rendered while focused on a Products block.
   *
   * @return Component
   */

		function getInspectorControls() {

			// Column controls don't make sense in a list layout.
			var columnControl = null;
			if ('list' !== block_layout) {
				columnControl = wp.element.createElement(RangeControl, {
					label: __('Columns'),
					value: columns,
					onChange: function onChange(value) {
						return setAttributes({ columns: value });
					},
					min: wc_product_block_data.min_columns,
					max: wc_product_block_data.max_columns
				});
			}

			return wp.element.createElement(
				InspectorControls,
				{ key: 'inspector' },
				wp.element.createElement(
					'h3',
					null,
					__('Layout')
				),
				columnControl,
				wp.element.createElement(RangeControl, {
					label: __('Rows'),
					value: rows,
					onChange: function onChange(value) {
						return setAttributes({ rows: value });
					},
					min: 1,
					max: 6
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
					return setAttributes({ block_layout: 'list' });
				},
				isActive: 'list' === block_layout
			}, {
				icon: 'grid-view',
				title: __('Grid View'),
				onClick: function onClick() {
					return setAttributes({ block_layout: 'grid' });
				},
				isActive: 'grid' === block_layout
			}];

			// Edit button should not do anything if valid product selection has not been made.
			var shouldDisableEditButton = ['', 'specific', 'category', 'attribute'].includes(display) && !display_setting.length;

			var editButton = [{
				icon: 'edit',
				title: __('Edit'),
				onClick: shouldDisableEditButton ? function () {} : function () {
					return setAttributes({ edit_mode: !edit_mode });
				},
				isActive: edit_mode
			}];

			return wp.element.createElement(
				BlockControls,
				{ key: 'controls' },
				wp.element.createElement(Toolbar, { controls: edit_mode ? [] : layoutControls }),
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

			var update_display_callback = function update_display_callback(value) {
				if (display !== value) {
					setAttributes({
						display: value,
						display_setting: []
					});
				}
			};

			return wp.element.createElement(ProductsBlockSettingsEditor, {
				selected_display: display,
				selected_display_setting: display_setting,
				update_display_callback: update_display_callback,
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
		    block_layout = _props$attributes.block_layout,
		    rows = _props$attributes.rows,
		    columns = _props$attributes.columns,
		    display = _props$attributes.display,
		    display_setting = _props$attributes.display_setting;


		var shortcode_atts = new Map();
		shortcode_atts.set('limit', 'grid' === block_layout ? rows * columns : rows);

		if ('list' === block_layout) {
			shortcode_atts.set('class', 'list-layout');
		}

		if ('grid' === block_layout) {
			shortcode_atts.set('columns', columns);
		}

		if ('specific' === display) {
			shortcode_atts.set('ids', display_setting.join(','));
		} else if ('category' === display) {
			shortcode_atts.set('category', display_setting.join(','));
		} else if ('featured' === display) {
			shortcode_atts.set('visibility', 'featured');
		} else if ('best_sellers' === display) {
			shortcode_atts.set('best_selling', '1');
		} else if ('best_rated' === display) {
			shortcode_atts.set('orderby', 'rating');
		} else if ('on_sale' === display) {
			shortcode_atts.set('on_sale', '1');
		} else if ('attribute' === display) {
			var attribute = display_setting.length ? display_setting[0] : '';
			var terms = display_setting.length > 1 ? display_setting.slice(1).join(',') : '';

			shortcode_atts.set('attribute', attribute);
			if (terms.length) {
				shortcode_atts.set('terms', terms);
			}
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

		return wp.element.createElement(
			RawHTML,
			null,
			shortcode
		);
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
    Dropdown = _wp$components.Dropdown,
    Dashicon = _wp$components.Dashicon;
var _ReactTransitionGroup = ReactTransitionGroup,
    TransitionGroup = _ReactTransitionGroup.TransitionGroup,
    CSSTransition = _ReactTransitionGroup.CSSTransition;

/**
 * Product data cache.
 * Reduces the number of API calls and makes UI smoother and faster.
 */

var PRODUCT_DATA = {};

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

	/**
  * Add a product to the list of selected products.
  *
  * @param id int Product ID.
  */


	_createClass(ProductsSpecificSelect, [{
		key: 'addProduct',
		value: function addProduct(id) {

			var selectedProducts = this.state.selectedProducts;
			selectedProducts.push(id);

			this.setState({
				selectedProducts: selectedProducts
			});

			/**
    * We need to copy the existing data into a new array.
    * We can't just push the new product onto the end of the existing array because Gutenberg seems
    * to do some sort of check by reference to determine whether to *actually* update the attribute
    * and will not update it if we just pass back the same array with an extra element on the end.
    */
			this.props.update_display_setting_callback(selectedProducts.slice());
		}

		/**
   * Remove a product from the list of selected products.
   *
   * @param id int Product ID.
   */

	}, {
		key: 'removeProduct',
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

		/**
   * Render the product specific select screen.
   */

	}, {
		key: 'render',
		value: function render() {
			return wp.element.createElement(
				'div',
				{ className: 'wc-products-list-card wc-products-list-card--specific' },
				wp.element.createElement(ProductsSpecificSearchField, {
					addProductCallback: this.addProduct.bind(this),
					selectedProducts: this.state.selectedProducts
				}),
				wp.element.createElement(ProductSpecificSelectedProducts, {
					productIds: this.state.selectedProducts,
					removeProductCallback: this.removeProduct.bind(this)
				})
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

	/**
  * Constructor.
  */
	function ProductsSpecificSearchField(props) {
		_classCallCheck(this, ProductsSpecificSearchField);

		var _this2 = _possibleConstructorReturn(this, (ProductsSpecificSearchField.__proto__ || Object.getPrototypeOf(ProductsSpecificSearchField)).call(this, props));

		_this2.state = {
			searchText: ''
		};

		_this2.updateSearchResults = _this2.updateSearchResults.bind(_this2);
		_this2.setWrapperRef = _this2.setWrapperRef.bind(_this2);
		_this2.handleClickOutside = _this2.handleClickOutside.bind(_this2);
		return _this2;
	}

	/**
  * Hook in the listener for closing menu when clicked outside.
  */


	_createClass(ProductsSpecificSearchField, [{
		key: 'componentDidMount',
		value: function componentDidMount() {
			document.addEventListener('mousedown', this.handleClickOutside);
		}

		/**
   * Remove the listener for closing menu when clicked outside.
   */

	}, {
		key: 'componentWillUnmount',
		value: function componentWillUnmount() {
			document.removeEventListener('mousedown', this.handleClickOutside);
		}

		/**
   * Set the wrapper reference.
   *
   * @param node DOMNode
   */

	}, {
		key: 'setWrapperRef',
		value: function setWrapperRef(node) {
			this.wrapperRef = node;
		}

		/**
   * Close the menu when user clicks outside the search area.
   */

	}, {
		key: 'handleClickOutside',
		value: function handleClickOutside(evt) {
			if (this.wrapperRef && !this.wrapperRef.contains(event.target)) {
				this.setState({
					searchText: ''
				});
			}
		}

		/**
   * Event handler for updating results when text is typed into the input.
   *
   * @param evt Event object.
   */

	}, {
		key: 'updateSearchResults',
		value: function updateSearchResults(evt) {
			this.setState({
				searchText: evt.target.value
			});
		}

		/**
   * Render the product search UI.
   */

	}, {
		key: 'render',
		value: function render() {
			return wp.element.createElement(
				'div',
				{ className: 'wc-products-list-card__search-wrapper', ref: this.setWrapperRef },
				wp.element.createElement('input', { type: 'search',
					className: 'wc-products-list-card__search',
					value: this.state.searchText,
					placeholder: __('Search for products to display'),
					onChange: this.updateSearchResults
				}),
				wp.element.createElement(ProductSpecificSearchResults, {
					searchString: this.state.searchText,
					addProductCallback: this.props.addProductCallback,
					selectedProducts: this.props.selectedProducts
				})
			);
		}
	}]);

	return ProductsSpecificSearchField;
}(React.Component);

/**
 * Render product search results based on the text entered into the textbox.
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
	    addProductCallback = _ref.addProductCallback,
	    selectedProducts = _ref.selectedProducts;

	if (!products.data) {
		return null;
	}

	if (0 === products.data.length) {
		return __('No products found');
	}

	// Populate the cache.
	var _iteratorNormalCompletion2 = true;
	var _didIteratorError2 = false;
	var _iteratorError2 = undefined;

	try {
		for (var _iterator2 = products.data[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
			var product = _step2.value;

			PRODUCT_DATA[product.id] = product;
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

	return wp.element.createElement(ProductSpecificSearchResultsDropdown, {
		products: products.data,
		addProductCallback: addProductCallback,
		selectedProducts: selectedProducts
	});
});

/**
 * The dropdown of search results.
 */

var ProductSpecificSearchResultsDropdown = function (_React$Component3) {
	_inherits(ProductSpecificSearchResultsDropdown, _React$Component3);

	function ProductSpecificSearchResultsDropdown() {
		_classCallCheck(this, ProductSpecificSearchResultsDropdown);

		return _possibleConstructorReturn(this, (ProductSpecificSearchResultsDropdown.__proto__ || Object.getPrototypeOf(ProductSpecificSearchResultsDropdown)).apply(this, arguments));
	}

	_createClass(ProductSpecificSearchResultsDropdown, [{
		key: 'render',


		/**
   * Render dropdown.
   */
		value: function render() {
			var _props = this.props,
			    products = _props.products,
			    addProductCallback = _props.addProductCallback,
			    selectedProducts = _props.selectedProducts;


			var productElements = [];

			var _iteratorNormalCompletion3 = true;
			var _didIteratorError3 = false;
			var _iteratorError3 = undefined;

			try {
				for (var _iterator3 = products[Symbol.iterator](), _step3; !(_iteratorNormalCompletion3 = (_step3 = _iterator3.next()).done); _iteratorNormalCompletion3 = true) {
					var product = _step3.value;

					if (selectedProducts.includes(product.id)) {
						continue;
					}

					productElements.push(wp.element.createElement(
						CSSTransition,
						{
							key: product.slug,
							classNames: 'wc-products-list-card__content--transition',
							timeout: { exit: 700 }
						},
						wp.element.createElement(ProductSpecificSearchResultsDropdownElement, {
							product: product,
							addProductCallback: addProductCallback
						})
					));
				}
			} catch (err) {
				_didIteratorError3 = true;
				_iteratorError3 = err;
			} finally {
				try {
					if (!_iteratorNormalCompletion3 && _iterator3.return) {
						_iterator3.return();
					}
				} finally {
					if (_didIteratorError3) {
						throw _iteratorError3;
					}
				}
			}

			return wp.element.createElement(
				'div',
				{ role: 'menu', className: 'wc-products-list-card__search-results', 'aria-orientation': 'vertical', 'aria-label': '{ __( \'Products list\' ) }' },
				wp.element.createElement(
					TransitionGroup,
					null,
					productElements
				)
			);
		}
	}]);

	return ProductSpecificSearchResultsDropdown;
}(React.Component);

/**
 * One search result.
 */


var ProductSpecificSearchResultsDropdownElement = function (_React$Component4) {
	_inherits(ProductSpecificSearchResultsDropdownElement, _React$Component4);

	/**
  * Constructor.
  */
	function ProductSpecificSearchResultsDropdownElement(props) {
		_classCallCheck(this, ProductSpecificSearchResultsDropdownElement);

		var _this4 = _possibleConstructorReturn(this, (ProductSpecificSearchResultsDropdownElement.__proto__ || Object.getPrototypeOf(ProductSpecificSearchResultsDropdownElement)).call(this, props));

		_this4.state = {
			clicked: false
		};

		_this4.handleClick = _this4.handleClick.bind(_this4);
		return _this4;
	}

	/**
  * Add product to main list and change UI to show it was added.
  */


	_createClass(ProductSpecificSearchResultsDropdownElement, [{
		key: 'handleClick',
		value: function handleClick() {
			this.setState({ clicked: true });
			this.props.addProductCallback(this.props.product.id);
		}

		/**
   * Render one result in the search results.
   */

	}, {
		key: 'render',
		value: function render() {
			var product = this.props.product;

			return wp.element.createElement(
				'div',
				{ className: 'wc-products-list-card__content' },
				wp.element.createElement('img', { src: product.images[0].src }),
				wp.element.createElement(
					'span',
					{ className: 'wc-products-list-card__content-item-name' },
					this.state.clicked ? __('Added') : product.name
				),
				wp.element.createElement(
					'button',
					{ type: 'button',
						className: 'button-link',
						id: 'product-' + product.id,
						onClick: this.handleClick },
					__('Add')
				)
			);
		}
	}]);

	return ProductSpecificSearchResultsDropdownElement;
}(React.Component);

/**
 * List preview of selected products.
 */


var ProductSpecificSelectedProducts = withAPIData(function (props) {

	if (!props.productIds.length) {
		return {
			products: []
		};
	}

	// Determine which products are not already in the cache and only fetch uncached products.
	var uncachedProducts = [];
	var _iteratorNormalCompletion4 = true;
	var _didIteratorError4 = false;
	var _iteratorError4 = undefined;

	try {
		for (var _iterator4 = props.productIds[Symbol.iterator](), _step4; !(_iteratorNormalCompletion4 = (_step4 = _iterator4.next()).done); _iteratorNormalCompletion4 = true) {
			var productId = _step4.value;

			if (!PRODUCT_DATA.hasOwnProperty(productId)) {
				uncachedProducts.push(productId);
			}
		}
	} catch (err) {
		_didIteratorError4 = true;
		_iteratorError4 = err;
	} finally {
		try {
			if (!_iteratorNormalCompletion4 && _iterator4.return) {
				_iterator4.return();
			}
		} finally {
			if (_didIteratorError4) {
				throw _iteratorError4;
			}
		}
	}

	return {
		products: uncachedProducts.length ? '/wc/v2/products?include=' + uncachedProducts.join(',') : []
	};
})(function (_ref2) {
	var productIds = _ref2.productIds,
	    products = _ref2.products,
	    removeProductCallback = _ref2.removeProductCallback;


	// Add new products to cache.
	if (products.data) {
		var _iteratorNormalCompletion5 = true;
		var _didIteratorError5 = false;
		var _iteratorError5 = undefined;

		try {
			for (var _iterator5 = products.data[Symbol.iterator](), _step5; !(_iteratorNormalCompletion5 = (_step5 = _iterator5.next()).done); _iteratorNormalCompletion5 = true) {
				var product = _step5.value;

				PRODUCT_DATA[product.id] = product;
			}
		} catch (err) {
			_didIteratorError5 = true;
			_iteratorError5 = err;
		} finally {
			try {
				if (!_iteratorNormalCompletion5 && _iterator5.return) {
					_iterator5.return();
				}
			} finally {
				if (_didIteratorError5) {
					throw _iteratorError5;
				}
			}
		}
	}

	if (0 === productIds.length) {
		return __('No products selected');
	}

	var productElements = [];

	var _loop = function _loop(productId) {

		// Skip products that aren't in the cache yet or failed to fetch.
		if (!PRODUCT_DATA.hasOwnProperty(productId)) {
			return 'continue';
		}

		var productData = PRODUCT_DATA[productId];

		productElements.push(wp.element.createElement(
			'li',
			{ className: 'wc-products-list-card__item' },
			wp.element.createElement(
				'div',
				{ className: 'wc-products-list-card__content' },
				wp.element.createElement('img', { src: productData.images[0].src }),
				wp.element.createElement(
					'span',
					{ className: 'wc-products-list-card__content-item-name' },
					productData.name
				),
				wp.element.createElement(
					'button',
					{
						type: 'button',
						id: 'product-' + productData.id,
						onClick: function onClick() {
							removeProductCallback(productData.id);
						} },
					wp.element.createElement(Dashicon, { icon: 'no-alt' })
				)
			)
		));
	};

	var _iteratorNormalCompletion6 = true;
	var _didIteratorError6 = false;
	var _iteratorError6 = undefined;

	try {
		for (var _iterator6 = productIds[Symbol.iterator](), _step6; !(_iteratorNormalCompletion6 = (_step6 = _iterator6.next()).done); _iteratorNormalCompletion6 = true) {
			var productId = _step6.value;

			var _ret = _loop(productId);

			if (_ret === 'continue') continue;
		}
	} catch (err) {
		_didIteratorError6 = true;
		_iteratorError6 = err;
	} finally {
		try {
			if (!_iteratorNormalCompletion6 && _iterator6.return) {
				_iterator6.return();
			}
		} finally {
			if (_didIteratorError6) {
				throw _iteratorError6;
			}
		}
	}

	return wp.element.createElement(
		'div',
		{ className: 'wc-products-list-card__results-wrapper' },
		wp.element.createElement(
			'div',
			{ role: 'menu', className: 'wc-products-list-card__results', 'aria-orientation': 'vertical', 'aria-label': '{ __( \'Products list\' ) }' },
			wp.element.createElement(
				'ul',
				null,
				productElements
			)
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
			openAccordion: [],
			filterQuery: '',
			firstLoad: true
		};

		_this.checkboxChange = _this.checkboxChange.bind(_this);
		_this.accordionToggle = _this.accordionToggle.bind(_this);
		_this.filterResults = _this.filterResults.bind(_this);
		_this.setFirstLoad = _this.setFirstLoad.bind(_this);
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
			var openAccordions = this.state.openAccordion;

			if (openAccordions.includes(category)) {
				openAccordions = openAccordions.filter(function (c) {
					return c !== category;
				});
			} else {
				openAccordions.push(category);
			}

			this.setState({
				openAccordion: openAccordions
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
   * Update firstLoad state.
   *
   * @param Booolean loaded
   */

	}, {
		key: "setFirstLoad",
		value: function setFirstLoad(loaded) {
			this.setState({
				firstLoad: !!loaded
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
				{ className: "wc-products-list-card wc-products-list-card--taxonomy wc-products-list-card--taxonomy-category" },
				wp.element.createElement(ProductCategoryFilter, { filterResults: this.filterResults }),
				wp.element.createElement(ProductCategoryList, {
					filterQuery: this.state.filterQuery,
					selectedCategories: this.state.selectedCategories,
					checkboxChange: this.checkboxChange,
					accordionToggle: this.accordionToggle,
					openAccordion: this.state.openAccordion,
					firstLoad: this.state.firstLoad,
					setFirstLoad: this.setFirstLoad
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
		wp.element.createElement("input", { className: "wc-products-list-card__search", type: "search", placeholder: __('Search for categories'), onChange: filterResults })
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
	    openAccordion = _ref2.openAccordion,
	    firstLoad = _ref2.firstLoad,
	    setFirstLoad = _ref2.setFirstLoad;

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

	var isIndeterminate = function isIndeterminate(category, categories) {

		// Currect category selected?
		if (selectedCategories.includes(category.id)) {
			return false;
		}

		// Has children?
		var children = getCategoryChildren(category, categories).map(function (category) {
			return category.id;
		});

		var _iteratorNormalCompletion = true;
		var _didIteratorError = false;
		var _iteratorError = undefined;

		try {
			for (var _iterator = children[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
				var child = _step.value;

				if (selectedCategories.includes(child)) {
					return true;
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

		return false;
	};

	var AccordionButton = function AccordionButton(_ref3) {
		var category = _ref3.category,
		    categories = _ref3.categories;

		var icon = 'arrow-down-alt2';

		if (openAccordion.includes(category.id)) {
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
				}, className: "wc-products-list-card__accordion-button", style: style, type: "button" },
			wp.element.createElement(Dashicon, { icon: icon })
		);
	};

	var CategoryTree = function CategoryTree(_ref4) {
		var categories = _ref4.categories,
		    parent = _ref4.parent;

		var filteredCategories = categories.filter(function (category) {
			return category.parent === parent;
		});

		if (firstLoad && selectedCategories.length > 0) {
			categoriesData.filter(function (category) {
				return category.parent === 0;
			}).forEach(function (category) {
				var children = getCategoryChildren(category, categoriesData);

				var _iteratorNormalCompletion2 = true;
				var _didIteratorError2 = false;
				var _iteratorError2 = undefined;

				try {
					for (var _iterator2 = children[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
						var child = _step2.value;

						if (selectedCategories.includes(child.id) && !openAccordion.includes(category.id)) {
							accordionToggle(category.id);
							break;
						}
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
			});

			setFirstLoad(false);
		}

		return filteredCategories.length > 0 && wp.element.createElement(
			"ul",
			null,
			filteredCategories.map(function (category) {
				return wp.element.createElement(
					"li",
					{ key: category.id, className: openAccordion.includes(category.id) ? 'wc-products-list-card__item wc-products-list-card__accordion-open' : 'wc-products-list-card__item' },
					wp.element.createElement(
						"label",
						{ className: 0 === category.parent ? 'wc-products-list-card__content' : '', htmlFor: 'product-category-' + category.id },
						wp.element.createElement("input", { type: "checkbox",
							id: 'product-category-' + category.id,
							value: category.id,
							checked: selectedCategories.includes(category.id),
							onChange: function onChange(evt) {
								return handleCategoriesToCheck(evt, category, categories);
							},
							ref: function ref(el) {
								return el && (el.indeterminate = isIndeterminate(category, categories));
							}
						}),
						" ",
						category.name,
						0 === category.parent && wp.element.createElement(AccordionButton, { category: category, categories: categories }),
						wp.element.createElement(
							"span",
							{ className: "wc-products-list-card__taxonomy-count" },
							category.count
						)
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
		{ className: "wc-products-list-card__results" },
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
 * Attribute data cache.
 * Needed because it takes a lot of API calls to generate attribute info.
 */

var PRODUCT_ATTRIBUTE_DATA = {};

/**
 * When the display mode is 'Attribute' search for and select product attributes to pull products from.
 */

var ProductsAttributeSelect = exports.ProductsAttributeSelect = function (_React$Component) {
	_inherits(ProductsAttributeSelect, _React$Component);

	/**
  * Constructor.
  */
	function ProductsAttributeSelect(props) {
		_classCallCheck(this, ProductsAttributeSelect);

		/**
   * The first item in props.selected_display_setting is the attribute slug.
   * The rest are the term ids for any selected terms.
   */
		var _this = _possibleConstructorReturn(this, (ProductsAttributeSelect.__proto__ || Object.getPrototypeOf(ProductsAttributeSelect)).call(this, props));

		_this.state = {
			selectedAttribute: props.selected_display_setting.length ? props.selected_display_setting[0] : '',
			selectedTerms: props.selected_display_setting.length > 1 ? props.selected_display_setting.slice(1) : [],
			filterQuery: ''
		};

		_this.setSelectedAttribute = _this.setSelectedAttribute.bind(_this);
		_this.addTerm = _this.addTerm.bind(_this);
		_this.removeTerm = _this.removeTerm.bind(_this);
		return _this;
	}

	/**
  * Set the selected attribute.
  *
  * @param slug string Attribute slug.
  */


	_createClass(ProductsAttributeSelect, [{
		key: 'setSelectedAttribute',
		value: function setSelectedAttribute(slug) {
			this.setState({
				selectedAttribute: slug,
				selectedTerms: []
			});

			this.props.update_display_setting_callback([slug]);
		}

		/**
   * Add a term to the selected attribute's terms.
   *
   * @param id int Term id.
   */

	}, {
		key: 'addTerm',
		value: function addTerm(id) {
			var terms = this.state.selectedTerms;
			terms.push(id);
			this.setState({
				selectedTerms: terms
			});

			var displaySetting = [this.state.selectedAttribute];
			displaySetting = displaySetting.concat(terms);
			this.props.update_display_setting_callback(displaySetting);
		}

		/**
   * Remove a term from the selected attribute's terms.
   *
   * @param id int Term id.
   */

	}, {
		key: 'removeTerm',
		value: function removeTerm(id) {
			var newTerms = [];
			var _iteratorNormalCompletion = true;
			var _didIteratorError = false;
			var _iteratorError = undefined;

			try {
				for (var _iterator = this.state.selectedTerms[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
					var termId = _step.value;

					if (termId !== id) {
						newTerms.push(termId);
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
				selectedTerms: newTerms
			});

			var displaySetting = [this.state.selectedAttribute];
			displaySetting = displaySetting.concat(newTerms);
			this.props.update_display_setting_callback(displaySetting);
		}

		/**
   * Update the search results when typing in the attributes box.
   *
   * @param evt Event object
   */

	}, {
		key: 'updateFilter',
		value: function updateFilter(evt) {
			this.setState({
				filterQuery: evt.target.value
			});
		}

		/**
   * Render the whole section.
   */

	}, {
		key: 'render',
		value: function render() {
			return wp.element.createElement(
				'div',
				{ className: 'wc-products-list-card wc-products-list-card--taxonomy wc-products-list-card--taxonomy-atributes' },
				wp.element.createElement(ProductAttributeFilter, { updateFilter: this.updateFilter.bind(this) }),
				wp.element.createElement(ProductAttributeList, {
					selectedAttribute: this.state.selectedAttribute,
					selectedTerms: this.state.selectedTerms,
					filterQuery: this.state.filterQuery,
					setSelectedAttribute: this.setSelectedAttribute.bind(this),
					addTerm: this.addTerm.bind(this),
					removeTerm: this.removeTerm.bind(this)
				})
			);
		}
	}]);

	return ProductsAttributeSelect;
}(React.Component);

/**
 * Search area for filtering through the attributes list.
 */


var ProductAttributeFilter = function ProductAttributeFilter(props) {
	return wp.element.createElement(
		'div',
		null,
		wp.element.createElement('input', { className: 'wc-products-list-card__search', type: 'search', placeholder: __('Search for attributes'), onChange: props.updateFilter })
	);
};

/**
 * List of attributes.
 */
var ProductAttributeList = withAPIData(function (props) {
	return {
		attributes: '/wc/v2/products/attributes'
	};
})(function (_ref) {
	var attributes = _ref.attributes,
	    selectedAttribute = _ref.selectedAttribute,
	    filterQuery = _ref.filterQuery,
	    selectedTerms = _ref.selectedTerms,
	    setSelectedAttribute = _ref.setSelectedAttribute,
	    addTerm = _ref.addTerm,
	    removeTerm = _ref.removeTerm;

	if (!attributes.data) {
		return __('Loading');
	}

	if (0 === attributes.data.length) {
		return __('No attributes found');
	}

	var filter = filterQuery.toLowerCase();
	var attributeElements = [];
	var _iteratorNormalCompletion2 = true;
	var _didIteratorError2 = false;
	var _iteratorError2 = undefined;

	try {
		for (var _iterator2 = attributes.data[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
			var attribute = _step2.value;


			// Filter out attributes that don't match the search query.
			if (filter.length && -1 === attribute.name.toLowerCase().indexOf(filter)) {
				continue;
			}

			if (PRODUCT_ATTRIBUTE_DATA.hasOwnProperty(attribute.slug)) {
				attributeElements.push(wp.element.createElement(ProductAttributeElement, {
					selectedAttribute: selectedAttribute,
					selectedTerms: selectedTerms,
					attribute: attribute,
					setSelectedAttribute: setSelectedAttribute,
					addTerm: addTerm,
					removeTerm: removeTerm
				}));
			} else {
				attributeElements.push(wp.element.createElement(UncachedProductAttributeElement, {
					selectedAttribute: selectedAttribute,
					selectedTerms: selectedTerms,
					attribute: attribute,
					setSelectedAttribute: setSelectedAttribute,
					addTerm: addTerm,
					removeTerm: removeTerm
				}));
			}
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

	return wp.element.createElement(
		'div',
		{ className: 'wc-products-list-card__results' },
		attributeElements
	);
});

/**
 * Caches then renders a product attribute term element.
 */
var UncachedProductAttributeElement = withAPIData(function (props) {
	return {
		terms: '/wc/v2/products/attributes/' + props.attribute.id + '/terms'
	};
})(function (_ref2) {
	var terms = _ref2.terms,
	    selectedAttribute = _ref2.selectedAttribute,
	    selectedTerms = _ref2.selectedTerms,
	    attribute = _ref2.attribute,
	    setSelectedAttribute = _ref2.setSelectedAttribute,
	    addTerm = _ref2.addTerm,
	    removeTerm = _ref2.removeTerm;

	if (!terms.data || 0 === terms.data.length) {
		return null;
	}

	// Populate cache.
	PRODUCT_ATTRIBUTE_DATA[attribute.slug] = { terms: [] };

	var totalCount = 0;
	var _iteratorNormalCompletion3 = true;
	var _didIteratorError3 = false;
	var _iteratorError3 = undefined;

	try {
		for (var _iterator3 = terms.data[Symbol.iterator](), _step3; !(_iteratorNormalCompletion3 = (_step3 = _iterator3.next()).done); _iteratorNormalCompletion3 = true) {
			var term = _step3.value;

			totalCount += term.count;
			PRODUCT_ATTRIBUTE_DATA[attribute.slug].terms.push(term);
		}
	} catch (err) {
		_didIteratorError3 = true;
		_iteratorError3 = err;
	} finally {
		try {
			if (!_iteratorNormalCompletion3 && _iterator3.return) {
				_iterator3.return();
			}
		} finally {
			if (_didIteratorError3) {
				throw _iteratorError3;
			}
		}
	}

	PRODUCT_ATTRIBUTE_DATA[attribute.slug].count = totalCount;

	return wp.element.createElement(ProductAttributeElement, {
		selectedAttribute: selectedAttribute,
		selectedTerms: selectedTerms,
		attribute: attribute,
		setSelectedAttribute: setSelectedAttribute,
		addTerm: addTerm,
		removeTerm: removeTerm
	});
});

/**
 * A product attribute term element.
 */

var ProductAttributeElement = function (_React$Component2) {
	_inherits(ProductAttributeElement, _React$Component2);

	/**
  * Constructor.
  */
	function ProductAttributeElement(props) {
		_classCallCheck(this, ProductAttributeElement);

		var _this2 = _possibleConstructorReturn(this, (ProductAttributeElement.__proto__ || Object.getPrototypeOf(ProductAttributeElement)).call(this, props));

		_this2.handleAttributeChange = _this2.handleAttributeChange.bind(_this2);
		_this2.handleTermChange = _this2.handleTermChange.bind(_this2);
		return _this2;
	}

	/**
  * Propagate and reset values when the selected attribute is changed.
  *
  * @param evt Event object
  */


	_createClass(ProductAttributeElement, [{
		key: 'handleAttributeChange',
		value: function handleAttributeChange(evt) {
			if (!evt.target.checked) {
				return;
			}

			var slug = evt.target.value;
			this.props.setSelectedAttribute(slug);
		}

		/**
   * Add or remove selected terms.
   *
   * @param evt Event object
   */

	}, {
		key: 'handleTermChange',
		value: function handleTermChange(evt) {
			if (evt.target.checked) {
				this.props.addTerm(evt.target.value);
			} else {
				this.props.removeTerm(evt.target.value);
			}
		}

		/**
   * Render the details for one attribute.
   */

	}, {
		key: 'render',
		value: function render() {
			var _this3 = this;

			var attribute = PRODUCT_ATTRIBUTE_DATA[this.props.attribute.slug];
			var isSelected = this.props.selectedAttribute === this.props.attribute.slug;

			var attributeTerms = null;
			if (isSelected) {
				attributeTerms = wp.element.createElement(
					'ul',
					null,
					attribute.terms.map(function (term) {
						return wp.element.createElement(
							'li',
							{ className: 'wc-products-list-card__item' },
							wp.element.createElement(
								'label',
								{ className: 'wc-products-list-card__content' },
								wp.element.createElement('input', { type: 'checkbox',
									value: term.id,
									onChange: _this3.handleTermChange,
									checked: _this3.props.selectedTerms.includes(String(term.id))
								}),
								term.name,
								wp.element.createElement(
									'span',
									{ className: 'wc-products-list-card__taxonomy-count' },
									term.count
								)
							)
						);
					})
				);
			}

			var cssClasses = ['wc-products-list-card--taxonomy-atributes__atribute'];

			if (isSelected) {
				cssClasses.push('wc-products-list-card__accordion-open');
			}

			return wp.element.createElement(
				'div',
				{ className: cssClasses.join(' ') },
				wp.element.createElement(
					'div',
					null,
					wp.element.createElement(
						'label',
						{ className: 'wc-products-list-card__content' },
						wp.element.createElement('input', { type: 'radio',
							value: this.props.attribute.slug,
							onChange: this.handleAttributeChange,
							checked: isSelected
						}),
						this.props.attribute.name,
						wp.element.createElement(
							'span',
							{ className: 'wc-products-list-card__taxonomy-count' },
							attribute.count
						)
					)
				),
				attributeTerms
			);
		}
	}]);

	return ProductAttributeElement;
}(React.Component);

/***/ })
/******/ ]);