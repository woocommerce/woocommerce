/**
 * External dependencies
 */
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { thumbUp } from '@woocommerce/icons';

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
				icon={ thumbUp }
				className="wc-block-editor-components-block-icon"
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
					( value ) => value !== 'woocommerce/product-top-rated'
				),
				transform: ( attributes ) =>
					createBlock( 'woocommerce/product-top-rated', attributes ),
			},
		],
	},

	/**
	 * Renders and manages the block.
	 */
	edit: () => {
		return (
			<DeprecatedBlockWarning
				blockName={ __( 'Top Rated Products', 'woocommerce' ) }
			/>
		);
	},

	save: () => {
		return null;
	},
} );
