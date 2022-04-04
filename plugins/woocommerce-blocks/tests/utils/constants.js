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
export const BILLING_DETAILS = config.get( 'addresses.customer.billing' );
export const PERFORMANCE_REPORT_FILENAME = 'reports/e2e-performance.json';
export const SHIPPING_DETAILS = config.get( 'addresses.customer.shipping' );
export const CUSTOMER_USERNAME = config.get( 'users.customer.username' );
export const CUSTOMER_PASSWORD = config.get( 'users.customer.password' );
