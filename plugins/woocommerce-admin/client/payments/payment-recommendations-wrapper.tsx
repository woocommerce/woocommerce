/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';

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
		return (
			<Suspense fallback={ null }>
				<PaymentRecommendationsChunk />
			</Suspense>
		);
	}
	return null;
};
