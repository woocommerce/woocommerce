/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import icon from './icon';
import { Edit } from './edit';
import { Save } from './save';
import metadata from './block.json';

registerBlockType( metadata as BlockConfiguration, {
	icon,
	edit: Edit,
	save: Save,
} );
