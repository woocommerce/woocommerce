/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ProductFiltersOverlayBlockSettings } from './settings';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, ProductFiltersOverlayBlockSettings );
}
