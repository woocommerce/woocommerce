/**
 * External dependencies
 */
import { Icon, totals } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';
import metadata from './block.json';

registerFeaturePluginBlockType( metadata, {
	icon: {
		src: <Icon srcElement={ totals } />,
		foreground: '#874FB9',
	},
	attributes,
	edit: Edit,
	save: Save,
} );
