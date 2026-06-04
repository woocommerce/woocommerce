/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, grid } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import '@woocommerce/atomic-blocks';

/**
 * Internal dependencies
 */
import { DeprecatedBlockWarning } from '../../../editor-components/deprecated-block-warning';
import metadata from './block.json';
import deprecated from './deprecated';
import save from './save';
import defaults from './defaults';

const { name } = metadata;
export { metadata, name };

registerBlockType( name, {
	icon: {
		src: (
			<Icon
				icon={ grid }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: () => (
		<DeprecatedBlockWarning
			blockName={ __( 'All Products', 'woocommerce' ) }
		/>
	),
	save,
	deprecated,
	defaults,
} );
