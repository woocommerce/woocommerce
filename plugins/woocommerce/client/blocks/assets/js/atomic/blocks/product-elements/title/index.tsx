/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import { Save } from './save';
import { BLOCK_ICON } from './constants';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		src: BLOCK_ICON,
	},
	edit,
	save: Save,
} );
