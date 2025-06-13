/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { filterThreeLines } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';

registerBlockType( metadata, {
	icon: <Icon icon={ filterThreeLines } />,
	edit: Edit,
	save: Save,
} );
