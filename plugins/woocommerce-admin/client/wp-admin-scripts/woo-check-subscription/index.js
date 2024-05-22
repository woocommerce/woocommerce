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

render(
	<CheckSubscriptionModal />,
	document.body.appendChild( missingSubscriptionModalRoot )
);
