/**
 * External dependencies
 */
import type { BrowserContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { BASE_URL } from './constants';

/**
 * Test plugins that `blocks setup` activates for all specs. Each one acts only
 * on requests that send a cookie with the plugin slug as its name, so a spec
 * enables it for its own browser context with `enableTestPlugin()`.
 */
export const COOKIE_GATED_TEST_PLUGINS = [
	'additional-checkout-fields',
	'item-data-display',
	'item-data-display-hidden',
	'item-data-display-malformed',
	'item-data-display-mixed',
	'locale-hide-country',
	'product-collection-compatibility-layer',
	'single-product-template-compatibility-layer',
	'update-price',
] as const;

export async function enableTestPlugin(
	context: BrowserContext,
	plugin: ( typeof COOKIE_GATED_TEST_PLUGINS )[ number ]
) {
	await context.addCookies( [
		{
			name: `woocommerce-blocks-test-${ plugin }`,
			value: '1',
			url: BASE_URL,
		},
	] );
}
