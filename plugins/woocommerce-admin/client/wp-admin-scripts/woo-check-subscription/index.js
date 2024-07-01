/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CheckSubscriptionModal from './modal';
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
} = window.wooCheckSubscriptionData;

const container = document.createElement( 'div' );
container.setAttribute( 'id', 'woo-check-subscription' );

render(
	<CheckSubscriptionModal
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
	/>,
	document.body.appendChild( container )
);
