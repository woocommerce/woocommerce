/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { without } from 'lodash';
import { Icon, stonks } from '@woocommerce/icons';
import { createBlock, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Block from './block';
import { deprecatedConvertToShortcode } from '../../utils/deprecations';
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';

registerBlockType( 'woocommerce/product-best-sellers', {
	title: __( 'Best Selling Products', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ stonks } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of your all-time best selling products.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	example: {
		attributes: {
			isPreview: true,
		},
	},
	attributes: {
		...sharedAttributes,
	},

	transforms: {
		from: [
			{
				type: 'block',
				blocks: without(
					sharedAttributeBlockTypes,
					'woocommerce/product-best-sellers'
				),
				transform: ( attributes ) =>
					createBlock(
						'woocommerce/product-best-sellers',
						attributes
					),
			},
		],
	},

	deprecated: [
		{
			// Deprecate shortcode save method in favor of dynamic rendering.
			attributes: sharedAttributes,
			save: deprecatedConvertToShortcode(
				'woocommerce/product-best-sellers'
			),
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
