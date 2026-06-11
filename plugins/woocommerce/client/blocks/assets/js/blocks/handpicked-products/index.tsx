/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, stack } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { DeprecatedBlockWarning } from '../../editor-components/deprecated-block-warning';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ stack }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},

	edit: ( { attributes } ) => (
		<DeprecatedBlockWarning
			blockName={ metadata.name }
			blockTitle={ __( 'Hand-picked Products', 'woocommerce' ) }
			attributes={ attributes }
		/>
	),

	save: () => null,
} );
