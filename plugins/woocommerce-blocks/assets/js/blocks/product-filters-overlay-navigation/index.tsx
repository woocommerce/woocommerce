/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import './style.scss';

registerBlockType( metadata, {
	edit: Edit,
	save: Save,
} );
