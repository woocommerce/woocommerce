/**
 * External dependencies
 */
import { Icon, card } from '@woocommerce/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerExperimentalBlockType( metadata, {
	icon: {
		src: <Icon srcElement={ card } />,
		foreground: '#7f54b3',
	},
	edit: Edit,
	save: Save,
} );
