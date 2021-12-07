/**
 * External dependencies
 */
import { Icon, filledCart } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerFeaturePluginBlockType( metadata, {
	icon: {
		src: <Icon srcElement={ filledCart } />,
		foreground: '#7f54b3',
	},
	edit: Edit,
	save: Save,
} );
