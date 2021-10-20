/**
 * External dependencies
 */
import { Icon, asterisk } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';
/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerFeaturePluginBlockType( metadata, {
	icon: {
		src: <Icon srcElement={ asterisk } />,
		foreground: '#7f54b3',
	},
	edit: Edit,
	save: Save,
} );
