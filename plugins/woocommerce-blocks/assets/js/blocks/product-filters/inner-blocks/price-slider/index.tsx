/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { listItem } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import './style.scss';

registerBlockType( metadata, {
	edit,
	save,
	icon: listItem,
} );
