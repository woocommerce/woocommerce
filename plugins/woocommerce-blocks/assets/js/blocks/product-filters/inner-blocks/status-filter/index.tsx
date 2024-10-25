/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterStatus } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import edit from './edit';
import metadata from './block.json';
import save from './save';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: productFilterStatus,
		save,
		edit,
	} );
}
