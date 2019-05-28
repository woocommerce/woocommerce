/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { without } from 'lodash';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Block from './block';
import { deprecatedConvertToShortcode } from '../../utils/deprecations';
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';

registerBlockType( 'woocommerce/product-on-sale', {
	title: __( 'On Sale Products', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Gridicon icon="tag" />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of on sale products.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	attributes: {
		...sharedAttributes,

		/**
		 * How to order the products: 'date', 'popularity', 'price_asc', 'price_desc' 'rating', 'title'.
		 */
		orderby: {
			type: 'string',
			default: 'date',
		},
	},
	transforms: {
		from: [
			{
				type: 'block',
				blocks: without(
					sharedAttributeBlockTypes,
					'woocommerce/product-on-sale'
				),
				transform: ( attributes ) =>
					createBlock( 'woocommerce/product-on-sale', attributes ),
			},
		],
	},

	deprecated: [
		{
			// Deprecate shortcode save method in favor of dynamic rendering.
			attributes: {
				...sharedAttributes,
				orderby: {
					type: 'string',
					default: 'date',
				},
			},
			save: deprecatedConvertToShortcode( 'woocommerce/product-on-sale' ),
		},
	],

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	save() {
		return null;
	},
} );
