/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import save from '../save';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';

registerBlockType( metadata, {
	save,
	icon,
	edit,
} );
