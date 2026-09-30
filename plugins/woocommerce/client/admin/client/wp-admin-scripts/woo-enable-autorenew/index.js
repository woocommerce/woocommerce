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
		'.woocommerce-enable-autorenew',
		'woo_enable_autorenew_in_plugins'
	);
} );
