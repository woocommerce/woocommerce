/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, percent } from '@wordpress/icons';
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
				icon={ percent }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},

	edit: () => (
		<DeprecatedBlockWarning
			blockName={ __( 'On Sale Products', 'woocommerce' ) }
		/>
	),

	save: () => null,
} );
