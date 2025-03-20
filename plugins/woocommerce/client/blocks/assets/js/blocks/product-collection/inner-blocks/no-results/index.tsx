/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { loop as loopIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';

registerBlockType( metadata, {
	icon: loopIcon,
	supports: {
		...metadata.supports,
	},
	edit,
	save,
} );
