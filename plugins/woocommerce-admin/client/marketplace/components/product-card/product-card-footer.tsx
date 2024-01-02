/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export interface ProductCardFooterProps {
	price: number | null | undefined;
	currencySymbol: string;
	averageRating: number | null | undefined;
	reviewsCount: number | null | undefined;
}

function ProductCardFooter( props: ProductCardFooterProps ) {
	const { price, currencySymbol, averageRating, reviewsCount } = props;
	return (
		<footer className="woocommerce-marketplace__product-card__footer">
			<div className="woocommerce-marketplace__product-card__price">
				<span className="woocommerce-marketplace__product-card__price-label">
					{
						// '0' is a free product
						price === 0
							? __( 'Free download', 'woocommerce' )
							: currencySymbol + price
					}
				</span>
				<span className="woocommerce-marketplace__product-card__price-billing">
					{ props.price === 0
						? ''
						: __( ' annually', 'woocommerce' ) }
				</span>
			</div>
			<div className="woocommerce-marketplace__product-card__rating">
				{ averageRating !== null && (
					<>
						<span className="woocommerce-marketplace__product-card__rating-icon">
							<Icon icon={ 'star-filled' } size={ 16 } />
						</span>
						<span className="woocommerce-marketplace__product-card__rating-average">
							{ averageRating }
						</span>
						<span className="woocommerce-marketplace__product-card__rating-count">
							({ reviewsCount })
						</span>
					</>
				) }
			</div>
		</footer>
	);
}

export default ProductCardFooter;
