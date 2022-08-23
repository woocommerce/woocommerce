/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';
import RecommendationsEligibilityWrapper from '../settings-recommendations/recommendations-eligibility-wrapper';
import { ShippingTour } from '../guided-tours/shipping-tour';

const ShippingRecommendationsLoader = lazy( () => {
	if ( window.wcAdminFeatures[ 'shipping-smart-defaults' ] ) {
		return import(
			/* webpackChunkName: "shipping-recommendations" */ './experimental-shipping-recommendations'
		);
	}

	return import(
		/* webpackChunkName: "shipping-recommendations" */ './shipping-recommendations'
	);
} );

export const ShippingRecommendations: React.FC< EmbeddedBodyProps > = ( {
	page,
	tab,
	section,
	zone_id,
} ) => {
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

	return (
		<RecommendationsEligibilityWrapper>
			<Suspense fallback={ null }>
				{ window.wcAdminFeatures[ 'shipping-setting-tour' ] && (
					<ShippingTour />
				) }
				<ShippingRecommendationsLoader />
			</Suspense>
		</RecommendationsEligibilityWrapper>
	);
};
