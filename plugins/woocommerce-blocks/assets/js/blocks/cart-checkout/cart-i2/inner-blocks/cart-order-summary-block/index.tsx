/**
 * External dependencies
 */
import { Icon, totals } from '@woocommerce/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';
import metadata from './block.json';

registerExperimentalBlockType( metadata, {
	icon: {
		src: <Icon srcElement={ totals } />,
		foreground: '#7f54b3',
	},
	attributes,
	edit: Edit,
	save: Save,
} );
