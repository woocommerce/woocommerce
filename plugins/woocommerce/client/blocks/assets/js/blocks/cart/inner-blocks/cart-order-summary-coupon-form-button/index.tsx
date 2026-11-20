/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit, Save } from './edit';

registerBlockType( metadata, {
	icon: 'tag',
	edit: Edit,
	save: Save,
} );
