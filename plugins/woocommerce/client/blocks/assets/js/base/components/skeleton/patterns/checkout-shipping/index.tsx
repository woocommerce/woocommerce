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

export const CheckoutShippingSkeleton = () => {
	return (
		<>
			<VisuallyHidden aria-live="polite">
				{ __( 'Loading shipping options…', 'woocommerce' ) }
			</VisuallyHidden>
			<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-shipping">
				<Skeleton height="20px" width="20px" borderRadius="100%" />
				<Skeleton height="20px" maxWidth="148px" />
				<Skeleton height="20px" width="50px" />
			</div>
		</>
	);
};
