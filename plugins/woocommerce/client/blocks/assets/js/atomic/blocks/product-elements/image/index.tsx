/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';
import deprecated from './deprecated';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';

registerBlockType( metadata, {
	deprecated,
	icon,
	edit,
	save: () => null,
} );
