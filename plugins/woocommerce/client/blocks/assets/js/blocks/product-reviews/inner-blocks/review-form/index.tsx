/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { postCommentsForm as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

// @ts-expect-error metadata is not typed.
registerBlockType( metadata, {
	icon,
	edit,
} );
