/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CheckSubscriptionModal } from './modal';
import './style.scss';

const {
	manageSubscriptionsUrl,
	productId,
	productName,
	productRegularPrice,
	dismissAction,
	dismissNonce,
	remindLaterAction,
	remindLaterNonce,
	colorScheme,
	subscriptionState,
} = window.wooCheckSubscriptionData;

const container = document.createElement( 'div' );
container.setAttribute( 'id', 'woo-check-subscription' );

render(
	<CheckSubscriptionModal
		manageSubscriptionsUrl={ manageSubscriptionsUrl }
		productId={ productId }
		productName={ productName }
		productRegularPrice={ productRegularPrice }
		dismissAction={ dismissAction }
		dismissNonce={ dismissNonce }
		remindLaterAction={ remindLaterAction }
		remindLaterNonce={ remindLaterNonce }
		colorScheme={ colorScheme }
		subscriptionState={ subscriptionState }
	/>,
	document.body.appendChild( container )
);
