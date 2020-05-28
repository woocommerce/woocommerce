/**
 * External dependencies
 */
import { WC_BLOCKS_ASSET_URL } from '@woocommerce/block-settings';

export const checkoutBlockPreview = (
	<img
		src={ WC_BLOCKS_ASSET_URL + 'img/checkout-preview.svg' }
		alt=""
		width="230"
		height="250"
		style={ {
			width: '100%',
		} }
	/>
);
