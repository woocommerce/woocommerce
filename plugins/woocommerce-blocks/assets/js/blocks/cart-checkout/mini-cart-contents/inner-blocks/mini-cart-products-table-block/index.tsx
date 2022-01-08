/**
 * External dependencies
 */
import { Icon, list } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerFeaturePluginBlockType( metadata, {
	icon: (
		<Icon
			srcElement={ list }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit: Edit,
	save: Save,
} );
