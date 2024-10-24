/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterPrice } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import Save from './save';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: productFilterPrice,
		edit: Edit,
		save: Save,
	} );
}
