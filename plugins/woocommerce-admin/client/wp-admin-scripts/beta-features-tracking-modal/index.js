/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BetaFeaturesTrackingContainer } from './container';
import './style.scss';

const betaFeaturesRoot = document.createElement( 'div' );
betaFeaturesRoot.setAttribute( 'id', 'beta-features-tracking' );

render(
	<BetaFeaturesTrackingContainer />,
	document.body.appendChild( betaFeaturesRoot )
);
