/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';
import RecommendationsEligibilityWrapper from '../settings-recommendations/recommendations-eligibility-wrapper';

const CheckoutRecoveryRecommendationsLoader = lazy(
	() =>
		import(
			/* webpackChunkName: "checkout-recovery-recommendations" */ './checkout-recovery-recommendations'
		)
);

const CHECKOUT_RECOVERY_EMAIL_SECTION = 'wc_email_customer_checkout_recovery';

export const CheckoutRecoveryRecommendations = ( {
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

	if ( section !== CHECKOUT_RECOVERY_EMAIL_SECTION ) {
		return null;
	}

	return (
		<RecommendationsEligibilityWrapper>
			<Suspense fallback={ null }>
				<CheckoutRecoveryRecommendationsLoader />
			</Suspense>
		</RecommendationsEligibilityWrapper>
	);
};
