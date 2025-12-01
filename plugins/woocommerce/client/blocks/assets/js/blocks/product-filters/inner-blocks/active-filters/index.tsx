/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { productFilterActive } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import Save from './save';

registerBlockType( metadata, {
	icon: productFilterActive,
	edit: Edit,
	save: Save,
} );
