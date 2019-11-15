/**
 * External dependencies
 */
import { WC_BLOCKS_ASSET_URL } from '@woocommerce/block-settings';

export const gridBlockPreview = (
	<img
		src={ WC_BLOCKS_ASSET_URL + 'img/grid.svg' }
		alt="Grid Preview"
		width="230"
		height="250"
		style={ {
			width: '100%',
		} }
	/>
);
