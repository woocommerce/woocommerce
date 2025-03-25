/**
 * This file is shared between the index.tsx and embed.tsx files.
 */

/**
 * External dependencies
 */
import { CustomerEffortScoreTracksContainer } from '@woocommerce/customer-effort-score';
import { createRoot } from '@wordpress/element';
import debugFactory from 'debug';

const debug = debugFactory( 'wc-admin:client' );

export const renderCustomerEffortScoreTracks = ( root: HTMLElement ) => {
	// Render the CustomerEffortScoreTracksContainer only if the feature flag is enabled.
	if (
		! window.wcAdminFeatures ||
		window.wcAdminFeatures[ 'customer-effort-score-tracks' ] !== true
	) {
		return;
	}

	if ( ! root ) {
		debug( 'Customer Effort Score Tracks root not found' );
		return;
	}

	createRoot(
		root.insertBefore( document.createElement( 'div' ), null )
	).render( <CustomerEffortScoreTracksContainer /> );
};
