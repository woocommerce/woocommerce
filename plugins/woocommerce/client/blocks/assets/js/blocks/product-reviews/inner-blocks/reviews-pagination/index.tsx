/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { queryPagination as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import './style.scss';

// @ts-expect-error metadata is not typed.
registerBlockType( metadata, {
	icon,
	edit,
	save,
} );
