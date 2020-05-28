/**
 * External dependencies
 */
import { WC_BLOCKS_ASSET_URL } from '@woocommerce/block-settings';

export const singleProductBlockPreview = (
	<img
		src={ WC_BLOCKS_ASSET_URL + 'img/single-product-preview.svg' }
		alt=""
		width="230"
		height="250"
		style={ {
			width: '100%',
		} }
	/>
);
