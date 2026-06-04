/**
 * External dependencies
 */
import { Icon, trendingUp } from '@wordpress/icons';
import { createBlock, registerBlockType } from '@wordpress/blocks';
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
				icon={ trendingUp }
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
					( value ) => value !== 'woocommerce/product-best-sellers'
				),
				transform: ( attributes ) =>
					createBlock(
						'woocommerce/product-best-sellers',
						attributes
					),
			},
		],
	},

	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit: () => {
		return (
			<DeprecatedBlockWarning
				blockName={ __( 'Best Selling Products', 'woocommerce' ) }
			/>
		);
	},

	save: () => {
		return null;
	},
} );
