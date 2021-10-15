/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon, search } from '@woocommerce/icons';
/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import Block from './block.js';
import edit from './edit.js';

const attributes = {
	/**
	 * Whether to show the field label.
	 */
	hasLabel: {
		type: 'boolean',
		default: true,
	},

	/**
	 * Search field label.
	 */
	label: {
		type: 'string',
		default: __( 'Search', 'woo-gutenberg-products-block' ),
	},

	/**
	 * Search field placeholder.
	 */
	placeholder: {
		type: 'string',
		default: __( 'Search products…', 'woo-gutenberg-products-block' ),
	},

	/**
	 * Store the instance ID.
	 */
	formId: {
		type: 'string',
		default: '',
	},
};

registerBlockType( 'woocommerce/product-search', {
	title: __( 'Product Search', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ search } />,
		foreground: '#7f54b3',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'A search box to allow customers to search for products by keyword.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
	},
	example: {
		attributes: {
			hasLabel: true,
		},
	},
	attributes,
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/legacy-widget' ],
				// We can't transform if raw instance isn't shown in the REST API.
				isMatch: ( { idBase, instance } ) =>
					idBase === 'woocommerce_product_search' && !! instance?.raw,
				transform: ( { instance } ) =>
					createBlock( 'woocommerce/product-search', {
						label:
							instance.raw.title === ''
								? __( 'Search', 'woo-gutenberg-products-block' )
								: instance.raw.title,
					} ),
			},
		],
	},
	deprecated: [
		{
			attributes,
			save( props ) {
				return (
					<div>
						<Block { ...props } />
					</div>
				);
			},
		},
	],
	edit,
	save() {
		return null;
	},
} );
