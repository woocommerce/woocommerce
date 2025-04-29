/**
 * External dependencies
 */
import { queryPaginationNumbers as icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

const { name } = metadata;
export { metadata, name };

registerBlockType( metadata, {
	icon,
	edit,
	example: {},
} );
