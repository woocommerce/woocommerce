/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { thumbUp } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';
import { Edit } from './edit';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ thumbUp }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a grid of your top rated products.',
		'woo-gutenberg-products-block'
	),
	attributes: {
		...sharedAttributes,
		...metadata.attributes,
	},
	transforms: {
		from: [
			{
				type: 'block',
				blocks: sharedAttributeBlockTypes.filter(
					( value ) => value !== 'woocommerce/product-top-rated'
				),
				transform: ( attributes ) =>
					createBlock( 'woocommerce/product-top-rated', attributes ),
			},
		],
	},

	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit: Edit,

	save() {
		return null;
	},
} );
