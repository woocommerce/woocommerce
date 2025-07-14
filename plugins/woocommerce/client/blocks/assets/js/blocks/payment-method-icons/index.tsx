/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { paymentMethodsIcon } from './icon';
import edit from './edit';
import './style.scss';

registerBlockType( metadata, {
	icon: {
		src: <Icon icon={ paymentMethodsIcon } />,
	},
	edit,
	save: () => null,
} );
