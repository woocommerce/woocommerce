/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CheckSubscriptionModal } from './modal';
import { CheckSubscriptionStickyFooter } from './sticky-footer';
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
	showAs,
	colorScheme,
} = window.wooCheckSubscriptionData;

const renderStickyFooter = showAs === 'sticky_footer';
const renderModal = showAs === 'modal';

const parent = renderModal
	? document.body
	: document.getElementById( 'wpcontent' );

const container = document.createElement( 'div' );
container.setAttribute( 'id', 'woo-check-subscription' );

if ( renderStickyFooter ) {
	render(
		<CheckSubscriptionStickyFooter
			manageSubscriptionsUrl={ manageSubscriptionsUrl }
			productId={ productId }
			productName={ productName }
			productRegularPrice={ productRegularPrice }
			dismissAction={ dismissAction }
			dismissNonce={ dismissNonce }
			colorScheme={ colorScheme }
		/>,
		parent.appendChild( container )
	);
} else {
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
		/>,
		parent.appendChild( container )
	);
}
