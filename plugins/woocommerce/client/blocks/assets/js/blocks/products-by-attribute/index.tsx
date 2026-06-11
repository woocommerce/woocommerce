/**
 * External dependencies
 */
import { Icon, category } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
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
				icon={ category }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
	},

	edit: ( { attributes } ) => (
		<DeprecatedBlockWarning
			blockName={ metadata.name }
			blockTitle={ __( 'Products by Attribute', 'woocommerce' ) }
			attributes={ attributes }
		/>
	),

	save: () => null,
} );
