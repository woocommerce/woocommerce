/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CheckoutShippingSkeleton = ( { rows = 1 }: { rows?: number } ) => {
	return (
		<div
			className="wc-block-components-skeleton wc-block-components-skeleton--checkout-shipping"
			aria-live="polite"
			aria-label={ __( 'Loading shipping options…', 'woocommerce' ) }
		>
			{ Array.from( { length: Math.max( 1, rows ) } ).map(
				( _, index ) => (
					<div
						className="wc-block-components-skeleton__shipping-option"
						key={ index }
					>
						<Skeleton
							height="20px"
							width="20px"
							borderRadius="100%"
						/>
						<Skeleton height="20px" maxWidth="148px" />
						<Skeleton height="20px" width="50px" />
					</div>
				)
			) }
		</div>
	);
};
