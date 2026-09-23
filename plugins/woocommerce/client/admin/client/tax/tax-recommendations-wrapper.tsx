/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';
import RecommendationsEligibilityWrapper from '../settings-recommendations/recommendations-eligibility-wrapper';

const TaxRecommendationsLoader = lazy(
	() =>
		import(
			/* webpackChunkName: "tax-recommendations" */ './tax-recommendations'
		)
);

export const TaxRecommendations = ( {
	page,
	tab,
	section,
}: EmbeddedBodyProps ) => {
	if ( page !== 'wc-settings' ) {
		return null;
	}

	if ( tab !== 'tax' ) {
		return null;
	}

	if ( Boolean( section ) ) {
		return null;
	}

	return (
		<RecommendationsEligibilityWrapper>
			<Suspense fallback={ null }>
				<TaxRecommendationsLoader />
			</Suspense>
		</RecommendationsEligibilityWrapper>
	);
};
