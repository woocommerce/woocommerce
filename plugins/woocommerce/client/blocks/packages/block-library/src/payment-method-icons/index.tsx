/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit, { BlockAttributes } from './edit';
import paymentMethodsIcon from './icon';

registerBlockType( metadata as BlockConfiguration< BlockAttributes >, {
	icon: {
		src: <Icon icon={ paymentMethodsIcon } />,
	},
	edit,
	save() {
		return null;
	},
} );
