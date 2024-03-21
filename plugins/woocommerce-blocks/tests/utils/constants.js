/**
 * External dependencies
 */
const config = require( 'config' );

/**
 * Constants used for E2E tests.
 *
 * @type {string}
 */
export const SIMPLE_VIRTUAL_PRODUCT_NAME = 'Woo Single #1';
export const SIMPLE_PHYSICAL_PRODUCT_NAME = '128GB USB Stick';
export const PAYMENT_COD = 'Cash on delivery';
export const PAYMENT_BACS = 'Direct bank transfer';
export const PAYMENT_CHEQUE = 'Check payments';
export const BILLING_DETAILS = config.get( 'addresses.customer.billing' );
export const PERFORMANCE_REPORT_FILENAME = 'reports/e2e-performance.json';
export const SHIPPING_DETAILS = config.get( 'addresses.customer.shipping' );
export const BASE_URL = config.get( 'url' );
export const DEFAULT_TIMEOUT = 30000;
