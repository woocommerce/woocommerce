export const COOKIE_NAME = 'woocommerceanalytics_session';
export const EVENT_PREFIX = 'woocommerceanalytics_';
export const EVENT_NAME_REGEX = /^[a-z_][a-z0-9_]*$/;

// API Configuration
export const API_NAMESPACE = 'woocommerce-analytics/v1';
export const API_ENDPOINT = 'track';
export const BATCH_SIZE = 10;
export const DEBOUNCE_DELAY = 1000; // 1 second

export const CLICK_HOUSE_EVENTS = [
	'session_started',
	'session_engagement',
	'product_view',
	'cart_view',
	'add_to_cart',
	'remove_from_cart',
	'checkout_view',
	'product_checkout',
	'product_purchase',
	'order_confirmation_view',
	'search',
	'page_view',
];
