/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import { trackPluginNoticeLinks } from '~/utils/plugin-notice-tracking';

domReady( () => {
	trackPluginNoticeLinks(
		'.woocommerce-renew-subscription',
		'woo_renew_subscription_in_plugins'
	);
} );
