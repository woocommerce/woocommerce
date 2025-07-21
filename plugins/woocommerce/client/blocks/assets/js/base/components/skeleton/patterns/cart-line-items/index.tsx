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

export const CartLineItemsSkeleton = ( { rows = 2 }: { rows?: number } ) => {
	return (
		<div
			className="wc-block-components-skeleton wc-block-components-skeleton--cart-line-items wc-block-cart is-large"
			aria-live="polite"
			aria-label={ __( 'Loading your cart…', 'woocommerce' ) }
		>
			<table className="wc-block-cart-items wp-block-woocommerce-cart-line-items-block">
				<thead>
					<tr className="wc-block-cart-items__header">
						<th className="wc-block-cart-items__header-image"></th>
						<th className="wc-block-cart-items__header-product"></th>
						<th className="wc-block-cart-items__header-total"></th>
					</tr>
				</thead>
				<tbody>
					{ Array.from( { length: rows } ).map( ( _, index ) => (
						<tr className="wc-block-cart-items__row" key={ index }>
							<td className="wc-block-cart-item__image">
								<Skeleton width="78px" height="78px" />
							</td>
							<td className="wc-block-cart-item__product">
								<div className="wc-block-cart-item__wrap">
									<Skeleton maxWidth="173px" />
									<Skeleton width="78px" />
								</div>
							</td>
							<td className="wc-block-cart-item__total">
								<Skeleton />
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
};
