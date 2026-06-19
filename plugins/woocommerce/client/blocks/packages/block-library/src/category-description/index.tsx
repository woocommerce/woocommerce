/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';
import { page as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit, { CategoryDescriptionAttributes } from './edit';

export const settings = {
	edit,
	icon,
	save: () => null,
};

registerBlockType(
	metadata as BlockConfiguration< CategoryDescriptionAttributes >,
	settings
);
