/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, starEmpty } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';

registerBlockType( metadata, {
	icon: <Icon icon={ starEmpty } />,
	edit,
	save,
} );
