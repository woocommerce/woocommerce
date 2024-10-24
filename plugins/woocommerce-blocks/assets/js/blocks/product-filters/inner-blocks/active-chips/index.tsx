/**
 * External dependencies
 */
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterOptions } from '@woocommerce/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import Save from './save';
import './style.scss';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		edit: Edit,
		icon: productFilterOptions,
		save: Save,
	} );
}
