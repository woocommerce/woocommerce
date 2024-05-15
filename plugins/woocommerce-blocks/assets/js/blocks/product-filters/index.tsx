/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ProductFiltersBlockSettings } from './settings';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, ProductFiltersBlockSettings );
}
