/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { heading as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

// @ts-expect-error registerBlockType typing
registerBlockType( metadata, {
	edit,
	icon,
	save: () => null,
} );
