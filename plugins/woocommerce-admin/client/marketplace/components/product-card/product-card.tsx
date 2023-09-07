/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './product-card.scss';
import { Product } from '../product-list/types';
import { appendUTMParams } from '../../utils/functions';

export interface ProductCardProps {
	type?: string;
	product: Product;
}

function ProductCard( props: ProductCardProps ): JSX.Element {
	const { product } = props;
	// We hardcode this for now while we only display prices in USD.
	const currencySymbol = '$';

	// Append UTM parameters to the vendor URL
	let vendorUrl = '';
	if ( product.vendorUrl ) {
		vendorUrl = appendUTMParams( product.vendorUrl, [
			[ 'utm_source', 'extensionsscreen' ],
			[ 'utm_medium', 'product' ],
			[ 'utm_campaign', 'wcaddons' ],
			[ 'utm_content', 'devpartner' ],
		] );
	}

	let productVendor: string | JSX.Element | null = product?.vendorName;
	if ( product?.vendorName && product?.vendorUrl ) {
		productVendor = (
			<a href={ vendorUrl } target="_blank" rel="noopener noreferrer">
				{ product.vendorName }
			</a>
		);
	}

	return (
		<Card className="woocommerce-marketplace__product-card">
			<div className="woocommerce-marketplace__product-card__content">
				<div className="woocommerce-marketplace__product-card__header">
					<div className="woocommerce-marketplace__product-card__details">
						{ product.icon && (
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
									target="_blank"
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
				<p className="woocommerce-marketplace__product-card__description">
					{ product.description }
				</p>
				<div className="woocommerce-marketplace__product-card__price">
					<span>
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
