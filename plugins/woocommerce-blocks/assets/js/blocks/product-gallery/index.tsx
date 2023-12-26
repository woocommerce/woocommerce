/**
 * External dependencies
 */
import { isExperimentalBuild } from '@woocommerce/block-settings';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ProductGalleryBlockSettings } from './settings';
import './style.scss';
import './inner-blocks/product-gallery-large-image-next-previous';
import './inner-blocks/product-gallery-pager';
import './inner-blocks/product-gallery-thumbnails';

if ( isExperimentalBuild() ) {
	// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core.
	registerBlockType( metadata, ProductGalleryBlockSettings );
}
