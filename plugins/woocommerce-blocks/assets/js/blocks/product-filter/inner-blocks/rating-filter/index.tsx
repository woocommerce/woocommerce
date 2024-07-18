/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterOptions } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: productFilterOptions,
		attributes: {
			...metadata.attributes,
		},
		edit,
	} );
}
