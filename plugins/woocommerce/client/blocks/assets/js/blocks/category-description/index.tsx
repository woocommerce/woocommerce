/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { page as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

registerBlockType( metadata, {
	edit,
	icon,
	save: () => null,
} );
