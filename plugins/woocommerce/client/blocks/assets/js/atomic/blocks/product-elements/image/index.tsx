/**
 * External dependencies
 */
import clsx from 'clsx';
import { InnerBlocks } from '@wordpress/block-editor';
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
	save: ( { attributes }: { attributes: BlockAttributes } ) => {
		if (
			attributes.isDescendentOfQueryLoop ||
			attributes.isDescendentOfSingleProductBlock
		) {
			return <InnerBlocks.Content />;
		}
		return <div className={ clsx( 'is-loading', attributes.className ) } />;
	},
} );
