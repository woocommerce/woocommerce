/** @format */
/**
 * External dependencies
 */
import { partial } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { CURRENCY } from '@woocommerce/wc-admin-settings';
import { numberFormat, formatValue, calculateDelta } from '@woocommerce/number';

// Compose the site currency settings with the format functions.
const storeNumberFormat = partial( numberFormat, CURRENCY );
const storeFormatValue = partial( formatValue, CURRENCY );

// Export the expected API for the consuming app.
export { storeNumberFormat as numberFormat, storeFormatValue as formatValue, calculateDelta };
