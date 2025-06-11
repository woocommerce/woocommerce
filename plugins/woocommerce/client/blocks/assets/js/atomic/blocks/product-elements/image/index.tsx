/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import deprecated from './deprecated';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';

registerBlockType( metadata, {
	deprecated,
	icon,
	edit,
	save: () => {
		return <InnerBlocks.Content />;
	},
} );
