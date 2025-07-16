/**
 * External dependencies
 */
import { VisuallyHidden } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CheckoutPaymentSkeleton = () => {
	return (
		<>
			<VisuallyHidden aria-live="polite">
				{ __( 'Loading payment options… ', 'woocommerce' ) }
			</VisuallyHidden>
			<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-payment">
				<div className="wc-block-components-skeleton--checkout-payment-container">
					<Skeleton height="20px" width="20px" borderRadius="100%" />
					<Skeleton height="20px" maxWidth="148px" />
				</div>
				<Skeleton height="20px" />
			</div>
		</>
	);
};
