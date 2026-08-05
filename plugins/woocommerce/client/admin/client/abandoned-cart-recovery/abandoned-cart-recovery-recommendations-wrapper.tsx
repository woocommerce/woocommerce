/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';
import RecommendationsEligibilityWrapper from '../settings-recommendations/recommendations-eligibility-wrapper';

const AbandonedCartRecoveryRecommendationsLoader = lazy(
	() =>
		import(
			/* webpackChunkName: "abandoned-cart-recovery-recommendations" */ './abandoned-cart-recovery-recommendations'
		)
);

const ABANDONED_CART_RECOVERY_EMAIL_SECTION =
	'wc_email_customer_abandoned_cart_recovery';

export const AbandonedCartRecoveryRecommendations = ( {
	page,
	tab,
	section,
}: EmbeddedBodyProps ) => {
	if ( page !== 'wc-settings' ) {
		return null;
	}

	if ( tab !== 'email' ) {
		return null;
	}

	if ( section !== ABANDONED_CART_RECOVERY_EMAIL_SECTION ) {
		return null;
	}

	return (
		<RecommendationsEligibilityWrapper>
			<Suspense fallback={ null }>
				<AbandonedCartRecoveryRecommendationsLoader />
			</Suspense>
		</RecommendationsEligibilityWrapper>
	);
};
