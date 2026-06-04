/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, tag } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { DeprecatedBlockWarning } from '../../editor-components/deprecated-block-warning';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ tag }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
	},

	edit: () => {
		return (
			<DeprecatedBlockWarning
				blockName={ __( 'Products by Tag', 'woocommerce' ) }
			/>
		);
	},

	save: () => {
		return null;
	},
} );
