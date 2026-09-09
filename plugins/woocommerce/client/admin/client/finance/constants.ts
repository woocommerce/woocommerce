/**
 * External dependencies
 */
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';

export const FINANCE_API_PATH = `${ WC_ADMIN_NAMESPACE }/payments/finance`;

export const DEFAULT_PER_PAGE = 10;

export const PER_PAGE_SIZES = [ 10, 25, 50, 100 ];

export const PAYMENTS_SETTINGS_PATH = 'admin.php?page=wc-settings&tab=checkout';
