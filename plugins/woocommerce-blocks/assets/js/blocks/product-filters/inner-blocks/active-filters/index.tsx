/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterOptions } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import './style.scss';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: productFilterOptions,
		edit: Edit,
	} );
}
