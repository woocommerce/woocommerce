/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './product-card.scss';
import { Product } from '../product-list/types';
import { getAdminSetting } from '../../../../client/utils/admin-settings';
import { appendUTMParams } from '../../utils/functions';

export interface ProductCardProps {
	type?: string;
	product: Product;
}

function ProductCard( props: ProductCardProps ): JSX.Element {
	const { product } = props;
	const currencySymbol = getAdminSetting( 'currency' )?.symbol;

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
								{ product.title }
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
				<Button
					className="woocommerce-marketplace__product-card__price"
					href={ product.url }
					target="_blank"
					variant="link"
				>
					<span>
						{ product.price === 0 || product.price === '0'
							? __( 'Free download', 'woocommerce' )
							: currencySymbol + product.price }
					</span>
					<span className="woocommerce-marketplace__product-card__price-billing">
						{ product.price === 0 || product.price === '0'
							? ''
							: __( ' annually', 'woocommerce' ) }
					</span>
				</Button>
			</div>
		</Card>
	);
}

export default ProductCard;
