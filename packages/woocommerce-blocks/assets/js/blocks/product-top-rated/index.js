/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import Gridicon from 'gridicons';
import { without } from 'lodash';

/**
 * Internal dependencies
 */
import Block from './block';
import { deprecatedConvertToShortcode } from '../../utils/deprecations';
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';

const blockTypeName = 'woocommerce/product-top-rated';

registerBlockType( blockTypeName, {
	title: __( 'Top Rated Products', 'woocommerce' ),
	icon: {
		src: <Gridicon icon="trophy" />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display a grid of your top rated products.',
		'woocommerce'
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
				blocks: without( sharedAttributeBlockTypes, blockTypeName ),
				transform: ( attributes ) =>
					createBlock( 'woocommerce/product-top-rated', attributes ),
			},
		],
	},

	deprecated: [
		{
			// Deprecate shortcode save method in favor of dynamic rendering.
			attributes: sharedAttributes,
			save: deprecatedConvertToShortcode( blockTypeName ),
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
