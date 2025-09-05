/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

// @ts-expect-error registerBlockType typing
registerBlockType( metadata, {
	edit,
	save: () => null,
} );
