/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterStockStatus } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import edit from './edit';
import metadata from './block.json';
import save from './save';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: productFilterStockStatus,
		save,
		edit,
	} );
}
