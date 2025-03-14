/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import icon from './icon';
import { Edit } from './edit';
import metadata from './block.json';
import './editor.scss';

// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core
registerBlockType( metadata, {
	icon,
	edit: Edit,
	save() {
		return null;
	},
	// TODO: Decide about the migration strategy.
	// deprecated: [
	// 	{
	// 		attributes: {
	// 			numberOfThumbnails: {
	// 				type: 'number',
	// 				default: 3,
	// 			},
	// 		},

	// 		migrate( { numberOfThumbnails } ) {
	// 			// Some arbitrary values to preserve the aspect ratio more or less.
	// 			// - 33% for 3 thumbnails
	// 			// - 12.5% for 8 thumbnails
	// 			return {
	// 				thumbnailSize: `${ 100 / numberOfThumbnails }%`,
	// 			};
	// 		},
	// 	},
	// ],
} );
