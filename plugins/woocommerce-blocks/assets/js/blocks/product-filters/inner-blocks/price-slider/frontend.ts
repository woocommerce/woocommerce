/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import PriceSlider from './price-slider';

renderFrontend(
	'.wp-block-woocommerce-product-filter-price-slider',
	PriceSlider
);
