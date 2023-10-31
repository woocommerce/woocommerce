/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './product-card.scss';
import { Product, ProductType } from '../product-list/types';

export interface ProductCardProps {
	type?: string;
	product?: Product;
	isLoading?: boolean;
}

function ProductCard( props: ProductCardProps ): JSX.Element {
	const { isLoading, type } = props;
	// Get the product if provided; if not provided, render a skeleton loader
	const product = props.product ?? {
		title: '',
		description: '',
		vendorName: '',
		vendorUrl: '',
		icon: '',
		url: '',
		price: 0,
		image: '',
	};

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

	const classNames = classnames(
		'woocommerce-marketplace__product-card',
		`woocommerce-marketplace__product-card--${ type }`,
		{
			'is-loading': isLoading,
		}
	);

	return (
		<Card className={ classNames } aria-hidden={ isLoading }>
			<div className="woocommerce-marketplace__product-card__content">
				{ isTheme && (
					<div className="woocommerce-marketplace__product-card__image">
						{ ! isLoading && (
							<img
								className="woocommerce-marketplace__product-card__image-inner"
								src={ product.image }
								alt={ product.title }
							/>
						) }
					</div>
				) }
				<div className="woocommerce-marketplace__product-card__header">
					<div className="woocommerce-marketplace__product-card__details">
						{ ! isTheme && (
							<>
								{ isLoading && (
									<div className="woocommerce-marketplace__product-card__icon" />
								) }
								{ ! isLoading && product.icon && (
									<img
										className="woocommerce-marketplace__product-card__icon"
										src={ product.icon }
										alt={ product.title }
									/>
								) }
							</>
						) }
						<div className="woocommerce-marketplace__product-card__meta">
							<h2 className="woocommerce-marketplace__product-card__title">
								<a
									className="woocommerce-marketplace__product-card__link"
									href={ product.url }
									rel="noopener noreferrer"
								>
									{ isLoading ? ' ' : product.title }
								</a>
							</h2>
							{ isLoading && (
								<p className="woocommerce-marketplace__product-card__vendor" />
							) }
							{ ! isLoading && productVendor && (
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
						{ ! isLoading && product.description }
					</p>
				) }
				<div className="woocommerce-marketplace__product-card__price">
					{ ! isLoading && (
						<>
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
						</>
					) }
				</div>
			</div>
		</Card>
	);
}

export default ProductCard;
