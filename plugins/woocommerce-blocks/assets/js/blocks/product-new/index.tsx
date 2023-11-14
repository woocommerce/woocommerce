/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon, sparkles } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';
import { Edit } from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	title: __( 'Newest Products', 'woo-gutenberg-products-block' ),
	icon: {
		src: (
			<Icon
				icon={ sparkles }
				className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--sparkles"
			/>
		),
	},
	attributes: {
		...sharedAttributes,
		...metadata.attributes,
	},
	transforms: {
		from: [
			{
				type: 'block',
				blocks: sharedAttributeBlockTypes.filter(
					( value ) => value !== 'woocommerce/product-new'
				),
				transform: ( attributes ) =>
					createBlock( 'woocommerce/product-new', attributes ),
			},
		],
	},

	/**
	 * Renders and manages the block.
	 *
	 */
	edit: Edit,
	save() {
		return null;
	},
} );
