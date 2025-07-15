/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import '../../../cart-checkout/cart-line-items-table/style.scss';
import '../../../../../blocks/cart/style.scss';
import './style.scss';

export const CartLineItemsCartSkeleton = ( {
	rows = 2,
}: {
	rows?: number;
} ) => {
	return (
		<>
			{ Array.from( { length: rows } ).map( ( _, index ) => (
				<tr className="wc-block-cart-items__row" key={ index }>
					<td className="wc-block-cart-item__image">
						<Skeleton />
					</td>
					<td className="wc-block-cart-item__product">
						<div className="wc-block-cart-item__wrap">
							<Skeleton maxWidth="173px" height=".875em" />
							<Skeleton width="50%" height=".875em" />
						</div>
					</td>
					<td className="wc-block-cart-item__total">
						<Skeleton height=".875em" />
					</td>
				</tr>
			) ) }
		</>
	);
};

export const CartLineItemsCheckoutSkeleton = ( {
	rows = 2,
}: {
	rows?: number;
} ) => {
	return (
		<div
			className="wc-block-components-order-summary"
			aria-live="polite"
			aria-label={ __( 'Loading your cart…', 'woocommerce' ) }
		>
			<div className="wc-block-components-skeleton wc-block-components-skeleton--cart-line-items-checkout wc-block-components-order-summary__content">
				{ Array.from( { length: rows } ).map( ( _, index ) => (
					<div
						className="wc-block-components-order-summary-item"
						key={ index }
					>
						<div className="wc-block-components-order-summary-item__image">
							<Skeleton width="48px" height="48px" />
						</div>
						<div className="wc-block-components-order-summary-item__description">
							<Skeleton width="100%" />
							<Skeleton width="50%" />
						</div>
						<div className="wc-block-components-order-summary-item__total-price">
							<Skeleton maxWidth="45px" />
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
};
