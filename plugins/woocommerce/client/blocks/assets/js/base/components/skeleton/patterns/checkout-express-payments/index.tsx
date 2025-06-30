/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import '../../../../../blocks/cart-checkout-shared/payment-methods/express-payment/style.scss';
import './style.scss';

export const CheckoutExpressPaymentsSkeleton = ( {
	showLabels = false,
}: {
	showLabels?: boolean;
} ) => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-express-payments">
			<div className="wc-block-components-express-payment wc-block-components-express-payment--checkout">
				<div className="wc-block-components-express-payment__title-container">
					{ showLabels ? (
						<div className="wc-block-components-title wc-block-components-express-payment__title">
							{ __( 'Express Payments', 'woocommerce' ) }
						</div>
					) : (
						<div className="wc-block-components-title wc-block-components-express-payment__title wc-block-components-express-payment__title--skeleton">
							<Skeleton width="127px" height="18px" />
						</div>
					) }
				</div>
				<div className="wc-block-components-express-payment__content">
					<ul className="wc-block-components-express-payment__event-buttons">
						<li>
							<Skeleton height="48px" />
						</li>
						<li>
							<Skeleton height="48px" />
						</li>
					</ul>
				</div>
			</div>
			<div className="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--checkout">
				{ showLabels ? (
					__( 'Or continue below', 'woocommerce' )
				) : (
					<Skeleton width="127px" height="18px" />
				) }
			</div>
		</div>
	);
};
