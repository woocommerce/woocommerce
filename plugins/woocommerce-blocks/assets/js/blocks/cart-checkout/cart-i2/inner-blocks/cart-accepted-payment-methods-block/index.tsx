/**
 * External dependencies
 */
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';
import { Icon, card } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerFeaturePluginBlockType( metadata, {
	icon: {
		src: <Icon srcElement={ card } />,
		foreground: '#874FB9',
	},
	edit: Edit,
	save: Save,
} );
