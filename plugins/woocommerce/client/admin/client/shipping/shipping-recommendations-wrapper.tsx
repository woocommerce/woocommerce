/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';
import RecommendationsEligibilityWrapper from '../settings-recommendations/recommendations-eligibility-wrapper';

const isShippingNativeSpike = () =>
	window.wcAdminFeatures[ 'shipping-smart-defaults' ] &&
	new URLSearchParams( window.location.search ).has(
		'_shipping_native_spike'
	);

const ShippingRecommendationsLoader = lazy( () => {
	if ( isShippingNativeSpike() ) {
		return import(
			/* webpackChunkName: "shipping-native-settings" */ './native-shipping-settings'
		);
	}

	if ( window.wcAdminFeatures[ 'shipping-smart-defaults' ] ) {
		return import(
			/* webpackChunkName: "shipping-recommendations" */ './experimental-shipping-recommendations'
		);
	}

	return import(
		/* webpackChunkName: "shipping-recommendations" */ './shipping-recommendations'
	);
} );

export const ShippingRecommendations = ( {
	page,
	tab,
	section,
	zone_id,
}: EmbeddedBodyProps ) => {
	if ( page !== 'wc-settings' ) {
		return null;
	}

	if ( tab !== 'shipping' ) {
		return null;
	}

	if ( Boolean( section ) ) {
		return null;
	}

	if ( Boolean( zone_id ) ) {
		return null;
	}

	if ( isShippingNativeSpike() ) {
		return (
			<Suspense fallback={ null }>
				<ShippingRecommendationsLoader />
			</Suspense>
		);
	}

	return (
		<RecommendationsEligibilityWrapper>
			<Suspense fallback={ null }>
				<ShippingRecommendationsLoader />
			</Suspense>
		</RecommendationsEligibilityWrapper>
	);
};
