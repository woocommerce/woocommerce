/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

registerBlockType( metadata, {
	edit,
	save() {
		return <InnerBlocks.Content />;
	},
} );
