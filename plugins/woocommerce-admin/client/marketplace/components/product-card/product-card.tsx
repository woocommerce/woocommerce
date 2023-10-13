/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './product-card.scss';
import { Product, ProductType } from '../product-list/types';

export interface ProductCardProps {
	type: ProductType;
	product: Product;
}

function ProductCard( props: ProductCardProps ): JSX.Element {
	const { product, type } = props;
	// We hardcode this for now while we only display prices in USD.
	const currencySymbol = '$';

	const isTheme = type === ProductType.theme;
	let productVendor: string | JSX.Element | null = product?.vendorName;
	if ( product?.vendorName && product?.vendorUrl ) {
		productVendor = (
			<a href={ product.vendorUrl } rel="noopener noreferrer">
				{ product.vendorName }
			</a>
		);
	}

	return (
		<Card
			className={ `woocommerce-marketplace__product-card woocommerce-marketplace__product-card--${ type }` }
		>
			<div className="woocommerce-marketplace__product-card__content">
				{ isTheme && (
					<div className="woocommerce-marketplace__product-card__image">
						<img
							className="woocommerce-marketplace__product-card__image-inner"
							src={ product.image }
							alt={ product.title }
						/>
					</div>
				) }
				<div className="woocommerce-marketplace__product-card__header">
					<div className="woocommerce-marketplace__product-card__details">
						{ ! isTheme && product.icon && (
							<img
								className="woocommerce-marketplace__product-card__icon"
								src={ product.icon }
								alt={ product.title }
							/>
						) }
						<div className="woocommerce-marketplace__product-card__meta">
							<h2 className="woocommerce-marketplace__product-card__title">
								<a
									className="woocommerce-marketplace__product-card__link"
									href={ product.url }
									rel="noopener noreferrer"
								>
									{ product.title }
								</a>
							</h2>
							{ productVendor && (
								<p className="woocommerce-marketplace__product-card__vendor">
									<span>{ __( 'By ', 'woocommerce' ) }</span>
									{ productVendor }
								</p>
							) }
						</div>
					</div>
				</div>
				{ ! isTheme && (
					<p className="woocommerce-marketplace__product-card__description">
						{ product.description }
					</p>
				) }
				<div className="woocommerce-marketplace__product-card__price">
					<span className="woocommerce-marketplace__product-card__price-label">
						{
							// '0' is a free product
							product.price === 0
								? __( 'Free download', 'woocommerce' )
								: currencySymbol + product.price
						}
					</span>
					<span className="woocommerce-marketplace__product-card__price-billing">
						{ product.price === 0
							? ''
							: __( ' annually', 'woocommerce' ) }
					</span>
				</div>
			</div>
		</Card>
	);
}

export default ProductCard;
