/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { without } from 'lodash';
import { Icon, tag } from '@woocommerce/icons';
/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import { deprecatedConvertToShortcode } from '../../utils/deprecations';
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';

registerBlockType( 'woocommerce/product-on-sale', {
	title: __( 'On Sale Products', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ tag } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display a grid of products currently on sale.',
		'woocommerce'
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
	example: {
		attributes: {
			isPreview: true,
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
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	save() {
		return null;
	},
} );
