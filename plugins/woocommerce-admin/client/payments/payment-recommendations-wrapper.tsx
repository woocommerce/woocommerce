/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';
import RecommendationsEligibilityWrapper from '../settings-recommendations/recommendations-eligibility-wrapper';

const PaymentRecommendationsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "payment-recommendations" */ './payment-recommendations'
		)
);

export const PaymentRecommendations: React.FC< EmbeddedBodyProps > = ( {
	page,
	tab,
	section,
} ) => {
	if ( page === 'wc-settings' && tab === 'checkout' && ! section ) {
		if (
			window?.wcAdminFeatures?.[
				'reactify-classic-payments-settings'
			] === true
		) {
			return null;
		}

		return (
			<RecommendationsEligibilityWrapper>
				<Suspense fallback={ null }>
					<PaymentRecommendationsChunk />
				</Suspense>
			</RecommendationsEligibilityWrapper>
		);
	}
	return null;
};
