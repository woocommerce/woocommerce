/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ProductGalleryBlockSettings } from './settings';
import './style.scss';
import './inner-blocks/product-gallery-large-image-next-previous';
import './inner-blocks/product-gallery-thumbnails';

const blockConfig = {
	...metadata,
	...ProductGalleryBlockSettings,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
