/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { title as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

// @ts-expect-error metadata is not typed.
registerBlockType( metadata, {
	edit,
	icon,
} );
