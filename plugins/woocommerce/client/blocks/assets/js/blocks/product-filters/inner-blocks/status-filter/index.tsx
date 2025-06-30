/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { productFilterStatus } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import edit from './edit';
import metadata from './block.json';
import save from './save';

registerBlockType( metadata, {
	icon: productFilterStatus,
	save,
	edit,
} );
