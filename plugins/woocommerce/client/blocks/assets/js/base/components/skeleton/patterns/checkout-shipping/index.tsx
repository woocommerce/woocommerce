/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CheckoutShippingSkeleton = () => {
	return (
		<div
			className="wc-block-components-skeleton wc-block-components-skeleton--checkout-shipping"
			aria-live="polite"
			aria-label={ __( 'Loading shipping options…', 'woocommerce' ) }
		>
			<Skeleton height="20px" width="20px" borderRadius="100%" />
			<Skeleton height="20px" maxWidth="148px" />
			<Skeleton height="20px" width="50px" />
		</div>
	);
};
