/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BetaFeaturesTrackingContainer } from './container';
import './style.scss';

const betaFeaturesRoot = document.createElement( 'div' );

betaFeaturesRoot.setAttribute( 'id', 'beta-features-tracking' );
createRoot( document.body.appendChild( betaFeaturesRoot ) ).render(
	<BetaFeaturesTrackingContainer />
);
