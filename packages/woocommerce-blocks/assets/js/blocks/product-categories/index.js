/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, list } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './editor.scss';
import './style.scss';
import Block from './block.js';

registerBlockType( 'woocommerce/product-categories', {
	title: __( 'Product Categories List', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ list } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Show all product categories as a list or dropdown.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	example: {
		attributes: {
			hasCount: true,
			hasImage: false,
		},
	},
	attributes: {
		/**
		 * Alignment of the block.
		 */
		align: {
			type: 'string',
		},

		/**
		 * Whether to show the product count in each category.
		 */
		hasCount: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Whether to show the category image in each category.
		 */
		hasImage: {
			type: 'boolean',
			default: false,
		},

		/**
		 * Whether to show empty categories in the list.
		 */
		hasEmpty: {
			type: 'boolean',
			default: false,
		},

		/**
		 * Whether to display product categories as a dropdown (true) or list (false).
		 */
		isDropdown: {
			type: 'boolean',
			default: false,
		},

		/**
		 * Whether the product categories should display with hierarchy.
		 */
		isHierarchical: {
			type: 'boolean',
			default: true,
		},
	},

	deprecated: [
		{
			// Deprecate HTML save method in favor of dynamic rendering.
			attributes: {
				hasCount: {
					type: 'boolean',
					default: true,
					source: 'attribute',
					selector: 'div',
					attribute: 'data-has-count',
				},
				hasEmpty: {
					type: 'boolean',
					default: false,
					source: 'attribute',
					selector: 'div',
					attribute: 'data-has-empty',
				},
				isDropdown: {
					type: 'boolean',
					default: false,
					source: 'attribute',
					selector: 'div',
					attribute: 'data-is-dropdown',
				},
				isHierarchical: {
					type: 'boolean',
					default: true,
					source: 'attribute',
					selector: 'div',
					attribute: 'data-is-hierarchical',
				},
			},
			migrate( attributes ) {
				return attributes;
			},
			save( props ) {
				const {
					hasCount,
					hasEmpty,
					isDropdown,
					isHierarchical,
				} = props.attributes;
				const data = {};
				if ( hasCount ) {
					data[ 'data-has-count' ] = true;
				}
				if ( hasEmpty ) {
					data[ 'data-has-empty' ] = true;
				}
				if ( isDropdown ) {
					data[ 'data-is-dropdown' ] = true;
				}
				if ( isHierarchical ) {
					data[ 'data-is-hierarchical' ] = true;
				}
				return (
					<div className="is-loading" { ...data }>
						{ isDropdown ? (
							<span
								aria-hidden
								className="wc-block-product-categories__placeholder"
							/>
						) : (
							<ul aria-hidden>
								<li>
									<span className="wc-block-product-categories__placeholder" />
								</li>
								<li>
									<span className="wc-block-product-categories__placeholder" />
								</li>
								<li>
									<span className="wc-block-product-categories__placeholder" />
								</li>
							</ul>
						) }
					</div>
				);
			},
		},
	],

	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	/**
	 * Save nothing; rendered by server.
	 */
	save() {
		return null;
	},
} );
