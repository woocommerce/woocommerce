/**
 * External dependencies
 */
import { Icon, bookmark } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit } from './edit';
import metadata from './block.json';

registerFeaturePluginBlockType( metadata, {
	icon: {
		src: (
			<Icon
				srcElement={ bookmark }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
} );
