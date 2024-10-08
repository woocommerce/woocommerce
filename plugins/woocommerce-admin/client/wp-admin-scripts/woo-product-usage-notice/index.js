/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductUsageNoticeModal from './modal';
import './style.scss';

const {
	renewUrl,
	subscribeUrl,
	productId,
	productName,
	productRegularPrice,
	dismissAction,
	dismissNonce,
	remindLaterAction,
	remindLaterNonce,
	colorScheme,
	subscriptionState,
	screenId,
} = window.wooProductUsageNotice;

const container = document.createElement( 'div' );
container.setAttribute( 'id', 'woo-product-usage-notice' );

createRoot( document.body.appendChild( container ) ).render(
	<ProductUsageNoticeModal
		renewUrl={ renewUrl }
		subscribeUrl={ subscribeUrl }
		productId={ productId }
		productName={ productName }
		productRegularPrice={ productRegularPrice }
		dismissAction={ dismissAction }
		dismissNonce={ dismissNonce }
		remindLaterAction={ remindLaterAction }
		remindLaterNonce={ remindLaterNonce }
		colorScheme={ colorScheme }
		subscriptionState={ subscriptionState }
		screenId={ screenId }
	/>
);
