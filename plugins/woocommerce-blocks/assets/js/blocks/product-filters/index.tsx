/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ProductFiltersBlockSettings } from './settings';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, ProductFiltersBlockSettings );
}
