/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card } from '@wordpress/components';
import classnames from 'classnames';
import { ExtraProperties, queueRecordEvent } from '@woocommerce/tracks';
import { useQuery } from '@woocommerce/navigation';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './product-card.scss';
import ProductCardFooter from './product-card-footer';
import { Product, ProductTracksData, ProductType } from '../product-list/types';
import { appendURLParams } from '../../utils/functions';

export interface ProductCardProps {
	type?: string;
	product?: Product;
	isLoading?: boolean;
	tracksData: ProductTracksData;
	small?: boolean;
}

function ProductCard( props: ProductCardProps ): JSX.Element {
	const SPONSORED_PRODUCT_LABEL = 'promoted'; // what product.label indicates a sponsored placement
	const SPONSORED_PRODUCT_STRIPE_SIZE = '5px'; // unfortunately can't be defined in CSS - height of "stripe"

	const { isLoading, type } = props;
	const query = useQuery();
	// Get the product if provided; if not provided, render a skeleton loader
	const product = props.product ?? {
		title: '',
		description: '',
		vendorName: '',
		vendorUrl: '',
		icon: '',
		label: null,
		primary_color: null,
		url: '',
		price: 0,
		image: '',
		averageRating: null,
		reviewsCount: null,
		featuredImage: '',
		color: '',
		productCategory: '',
	};

	const [ headerImgsrc, setHeaderImgSrc ] = useState( product.featuredImage );

	useEffect( () => {
		const img = new Image();
		img.src = product.featuredImage || '';

		img.onload = function () {
			// Get the natural height of the image
			const naturalHeight = img.naturalHeight;

			// Check if the natural height is greater than 288px
			if ( naturalHeight > 288 ) {
				setHeaderImgSrc( `${ product.featuredImage }?h=288` );
			} else {
				setHeaderImgSrc( product.featuredImage );
			}
		};
	}, [ product.featuredImage ] );

	function isSponsored(): boolean {
		return SPONSORED_PRODUCT_LABEL === product.label;
	}

	/**
	 * Sponsored products with a primary_color set have that color applied as a dynamically-colored stripe at the top of the card.
	 * In an ideal world this could be set in a data- attribute and we'd use CSS calc() and attr() to get it, but
	 * attr() doesn't have very good support yet, so we need to apply some inline CSS to stripe sponsored results.
	 */
	function inlineCss(): object {
		if ( ! isSponsored() || ! product.primary_color ) {
			return {};
		}
		return {
			background: `linear-gradient(${ product.primary_color } 0, ${ product.primary_color } ${ SPONSORED_PRODUCT_STRIPE_SIZE }, white ${ SPONSORED_PRODUCT_STRIPE_SIZE }, white)`,
		};
	}

	function recordTracksEvent( event: string, data: ExtraProperties ) {
		const tracksData = props.tracksData;

		if ( tracksData.position ) {
			data.position = tracksData.position;
		}

		if ( tracksData.label ) {
			data.label = tracksData.label;
		}

		if ( tracksData.group ) {
			data.group = tracksData.group;
		}

		if ( tracksData.searchTerm ) {
			data.search_term = tracksData.searchTerm;
		}

		if ( tracksData.category ) {
			data.category = tracksData.category;
		}

		queueRecordEvent( event, data );
	}

	const isTheme = type === ProductType.theme;
	const isBusinessService = type === ProductType.businessService;
	let productVendor: string | JSX.Element | null = product?.vendorName;
	if ( product?.vendorName && product?.vendorUrl ) {
		productVendor = (
			<a
				href={ product.vendorUrl }
				rel="noopener noreferrer"
				onClick={ () => {
					recordTracksEvent(
						'marketplace_product_card_vendor_clicked',
						{
							product: product.title,
							vendor: product.vendorName,
							product_type: type,
						}
					);
				} }
			>
				{ product.vendorName }
			</a>
		);
	}

	const productUrl = () => {
		if ( query.ref ) {
			return appendURLParams( product.url, [
				[ 'utm_content', query.ref ],
			] );
		}
		return product.url;
	};

	const classNames = classnames(
		'woocommerce-marketplace__product-card',
		`woocommerce-marketplace__product-card--${ type }`,
		{
			'is-loading': isLoading,
			'is-small': props.small,
			'is-sponsored': isSponsored(),
		}
	);

	const CardLink = () => (
		<a
			className="woocommerce-marketplace__product-card__link"
			href={ productUrl() }
			rel="noopener noreferrer"
			onClick={ () => {
				recordTracksEvent( 'marketplace_product_card_clicked', {
					product: product.title,
					vendor: product.vendorName,
					product_type: type,
				} );
			} }
		>
			{ isLoading ? ' ' : product.title }
		</a>
	);

	const BusinessService = () => (
		<div className="woocommerce-marketplace__business-card">
			<div
				className="woocommerce-marketplace__business-card__header"
				style={ { backgroundColor: product.color } }
			>
				<img src={ headerImgsrc } alt="" />
			</div>
			<div className="woocommerce-marketplace__business-card__content">
				<div className="woocommerce-marketplace__business-card__main-content">
					<h2>
						<CardLink />
					</h2>
					<p>{ product.description }</p>
				</div>
				<div className="woocommerce-marketplace__business-card__badge">
					<span>{ product.productCategory }</span>
				</div>
			</div>
		</div>
	);

	return (
		<Card
			className={ classNames }
			aria-hidden={ isLoading }
			style={ inlineCss() }
		>
			{ isBusinessService ? (
				<BusinessService />
			) : (
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
									<CardLink />
								</h2>
								{ isLoading && (
									<p className="woocommerce-marketplace__product-card__vendor-details">
										<span className="woocommerce-marketplace__product-card__vendor" />
									</p>
								) }
								{ ! isLoading && (
									<p className="woocommerce-marketplace__product-card__vendor-details">
										{ productVendor && (
											<span className="woocommerce-marketplace__product-card__vendor">
												<span>
													{ __(
														'By ',
														'woocommerce'
													) }
												</span>
												{ productVendor }
											</span>
										) }
										{ productVendor && isSponsored() && (
											<span
												aria-hidden="true"
												className="woocommerce-marketplace__product-card__vendor-details__separator"
											>
												·
											</span>
										) }
										{ isSponsored() && (
											<span className="woocommerce-marketplace__product-card__sponsored-label">
												{ __(
													'Sponsored',
													'woocommerce'
												) }
											</span>
										) }
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
					<footer className="woocommerce-marketplace__product-card__footer">
						{ isLoading && (
							<div className="woocommerce-marketplace__product-card__price" />
						) }
						{ ! isLoading && props.product && (
							<ProductCardFooter product={ props.product } />
						) }
					</footer>
				</div>
			) }
		</Card>
	);
}

export default ProductCard;
