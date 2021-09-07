/**
 * External dependencies
 */
import { Icon, asterisk } from '@woocommerce/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerExperimentalBlockType( metadata, {
	icon: {
		src: <Icon srcElement={ asterisk } />,
		foreground: '#874FB9',
	},
	edit: Edit,
	save: Save,
} );
