/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import icon from './icon';
import { Edit } from './edit';
import metadata from './block.json';
import type { ProductGalleryThumbnailsBlockAttributes } from './types';

registerBlockType(
	metadata as BlockConfiguration< ProductGalleryThumbnailsBlockAttributes >,
	{
		icon,
		edit: Edit,
		save() {
			return null;
		},
	}
);
