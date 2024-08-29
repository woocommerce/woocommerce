/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import { supports } from './supports';
import sharedConfig from '../shared/config';
import metadata from './block.json';

registerBlockType( metadata, {
	...sharedConfig,
	icon,
	supports,
	edit,
} );
