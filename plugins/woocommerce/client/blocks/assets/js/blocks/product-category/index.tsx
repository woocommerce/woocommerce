/**
 * External dependencies
 */
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon, file } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { DeprecatedBlockWarning } from '../../editor-components/deprecated-block-warning';
import metadata from './block.json';
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ file }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
		...sharedAttributes,
	},

	transforms: {
		from: [
			{
				type: 'block',
				blocks: sharedAttributeBlockTypes.filter(
					( value ) => value !== 'woocommerce/product-category'
				),
				transform: ( attributes ) =>
					createBlock( 'woocommerce/product-category', {
						...attributes,
						editMode: false,
					} ),
			},
		],
	},

	edit: () => {
		return (
			<DeprecatedBlockWarning
				blockName={ __( 'Products by Category', 'woocommerce' ) }
			/>
		);
	},

	save: () => {
		return null;
	},
} );
