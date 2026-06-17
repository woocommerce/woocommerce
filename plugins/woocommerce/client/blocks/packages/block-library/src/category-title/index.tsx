/**
 * External dependencies
 */
import { heading } from '@wordpress/icons';
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit, { CategoryTitleAttributes } from './edit';

export const settings = {
	edit,
	icon: heading,
	save: () => null,
};

registerBlockType(
	metadata as BlockConfiguration< CategoryTitleAttributes >,
	settings
);
