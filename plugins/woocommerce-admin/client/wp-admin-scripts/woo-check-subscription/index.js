/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CheckSubscriptionModal } from './modal';
import './style.scss';

const missingSubscriptionModalRoot = document.createElement( 'div' );
missingSubscriptionModalRoot.setAttribute(
	'id',
	'missing-subscription-modal-root'
);

const {
	manageSubscriptionsUrl,
	productId,
	productName,
	actionName,
	dismissNonce,
} = window.wooCheckSubscriptionData;

render(
	<CheckSubscriptionModal
		manageSubscriptionsUrl={ manageSubscriptionsUrl }
		productId={ productId }
		productName={ productName }
		actionName={ actionName }
		dismissNonce={ dismissNonce }
	/>,
	document.body.appendChild( missingSubscriptionModalRoot )
);
