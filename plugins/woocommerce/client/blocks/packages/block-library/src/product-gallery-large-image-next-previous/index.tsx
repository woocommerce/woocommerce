/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit } from './edit';
import metadata from './block.json';
import { Save } from './save';
import { Icon } from './icons';

registerBlockType( metadata as BlockConfiguration, {
	icon: Icon,
	edit: Edit,
	save: Save,
} );
