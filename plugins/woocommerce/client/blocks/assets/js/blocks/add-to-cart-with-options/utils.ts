/**
 * External dependencies
 */
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { getSetting, getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean } from '@woocommerce/types';

// Pick the value of the "blockify add to cart flag"
const isBlockifiedAddToCart = getSettingWithCoercion(
	'isBlockifiedAddToCart',
	false,
	isBoolean
);

const isBlockTheme = getSetting< boolean >( 'isBlockTheme' );

export const shouldBlockifiedAddToCartWithOptionsBeRegistered =
	isExperimentalBlocksEnabled() && isBlockifiedAddToCart && isBlockTheme;
