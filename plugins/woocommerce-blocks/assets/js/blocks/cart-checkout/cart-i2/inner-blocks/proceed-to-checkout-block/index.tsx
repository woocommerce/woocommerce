/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import attributes from './attributes';
import { Edit, Save } from './edit';
import metadata from './block.json';

registerExperimentalBlockType( metadata, {
	icon: {
		src: <Icon icon={ button } />,
		foreground: '#874FB9',
	},
	attributes,
	edit: Edit,
	save: Save,
} );
