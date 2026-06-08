/**
 * External dependencies
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import { heading as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit, { type CategoryTitleAttributes } from './edit';

registerBlockType< CategoryTitleAttributes >( metadata.name, {
	...( metadata as unknown as BlockConfiguration< CategoryTitleAttributes > ),
	edit: edit as unknown as BlockConfiguration< CategoryTitleAttributes >[ 'edit' ],
	icon,
	save: () => null,
} );
