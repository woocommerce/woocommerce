/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, navigation } from '@wordpress/icons';

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
	icon: <Icon icon={ navigation } />,
} );
