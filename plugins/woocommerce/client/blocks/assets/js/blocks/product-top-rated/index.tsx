/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { thumbUp } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { DeprecatedBlockWarning } from '../../editor-components/deprecated-block-warning';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ thumbUp }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},

	/**
	 * Renders and manages the block.
	 */
	edit: () => (
		<DeprecatedBlockWarning
			blockName={ __( 'Top Rated Products', 'woocommerce' ) }
		/>
	),

	save: () => null,
} );
