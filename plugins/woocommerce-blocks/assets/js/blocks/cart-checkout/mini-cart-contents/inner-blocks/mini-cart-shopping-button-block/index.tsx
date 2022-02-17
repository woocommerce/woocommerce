/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerFeaturePluginBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
} );
