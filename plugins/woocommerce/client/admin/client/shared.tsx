/**
 * This file is shared between the index.tsx and embed.tsx files.
 */

/**
 * External dependencies
 */
import { CustomerEffortScoreTracksContainer } from '@woocommerce/customer-effort-score';
import type { WCUser } from '@woocommerce/data';
import { createRoot } from '@wordpress/element';
import debugFactory from 'debug';

const debug = debugFactory( 'wc-admin:client' );

const canAccessCustomerEffortScore = ( currentUser?: WCUser ) => {
	return Boolean(
		currentUser?.is_super_admin ||
			currentUser?.capabilities?.manage_woocommerce ||
			currentUser?.capabilities?.edit_others_shop_orders
	);
};

export const renderCustomerEffortScoreTracks = (
	root: HTMLElement,
	currentUser?: WCUser
) => {
	if ( ! root ) {
		debug( 'Customer Effort Score Tracks root not found' );
		return;
	}

	// This component has its own React root, so use the server-provided user
	// instead of waiting for the main layout's current-user hydration.
	if ( ! canAccessCustomerEffortScore( currentUser ) ) {
		return;
	}

	createRoot(
		root.insertBefore( document.createElement( 'div' ), null )
	).render( <CustomerEffortScoreTracksContainer /> );
};
