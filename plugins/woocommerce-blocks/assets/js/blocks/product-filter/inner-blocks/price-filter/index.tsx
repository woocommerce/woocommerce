/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterOptions } from '@woocommerce/icons';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './style.scss';
import metadata from './block.json';
import Edit from './edit';
import { withPriceControls } from './components/with-price-controls';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: productFilterOptions,
		edit: Edit,
	} );

	addFilter(
		'editor.BlockEdit',
		'woocommerce/product-filter-price/inspector-controls',
		withPriceControls
	);
}
