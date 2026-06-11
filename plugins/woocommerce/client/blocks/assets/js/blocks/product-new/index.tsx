/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';
import { sparkles } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { DeprecatedBlockWarning } from '../../editor-components/deprecated-block-warning';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ sparkles }
				className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--sparkles"
			/>
		),
	},

	edit: ( { attributes } ) => (
		<DeprecatedBlockWarning
			blockName={ metadata.name }
			blockTitle={ __( 'Newest Products', 'woocommerce' ) }
			attributes={ attributes }
		/>
	),

	save: () => null,
} );
