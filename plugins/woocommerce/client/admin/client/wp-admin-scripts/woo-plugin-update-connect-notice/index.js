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
		'.woocommerce-connect-your-store',
		'woo_connect_notice_in_plugins'
	);
} );
