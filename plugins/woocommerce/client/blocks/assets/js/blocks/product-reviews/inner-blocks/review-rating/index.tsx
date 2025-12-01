/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { starHalf } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

// @ts-expect-error metadata is not typed.
registerBlockType( metadata, {
	edit,
	icon: starHalf,
} );
