/**
 * External dependencies
 */
import { Icon, trendingUp } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
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
				icon={ trendingUp }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},

	edit: () => (
		<DeprecatedBlockWarning
			blockName={ __( 'Best Selling Products', 'woocommerce' ) }
		/>
	),

	save: () => null,
} );
