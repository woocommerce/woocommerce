/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card } from '@wordpress/components';
import clsx from 'clsx';
import { ExtraProperties, queueRecordEvent } from '@woocommerce/tracks';
import { useQuery } from '@woocommerce/navigation';
import { decodeEntities } from '@wordpress/html-entities';
import { useState, useContext, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './product-card.scss';
import ProductCardFooter from './product-card-footer';
import {
	Product,
	ProductCardType,
	ProductTracksData,
	ProductType,
} from '../product-list/types';
import { appendURLParams } from '../../utils/functions';
import ProductPreviewModal from '../product-preview-modal/product-preview-modal';
import { MarketplaceContext } from '../../contexts/marketplace-context';
export interface ProductCardProps {
	type?: string;
	product?: Product;
	isLoading?: boolean;
	tracksData: ProductTracksData;
	small?: boolean;
	cardType?: ProductCardType;
}

function ProductCard( props: ProductCardProps ): JSX.Element {
	const SPONSORED_PRODUCT_LABEL = 'promoted'; // what product.label indicates a sponsored placement
	const SPONSORED_PRODUCT_STRIPE_SIZE = '5px'; // unfortunately can't be defined in CSS - height of "stripe"

	const { isLoading, cardType } = props;
	const isCompact = cardType === 'compact';
	const query = useQuery();
	const [ isPreviewModalOpen, setIsPreviewModalOpen ] = useState( false );
	const linkRef = useRef< HTMLAnchorElement >( null );
	// Get the product if provided; if not provided, render a skeleton loader
	const product = props.product ?? {
		id: null,
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
		billingPeriod: '',
		billingPeriodInterval: 0,
		currency: '',
		isOnSale: false,
		regularPrice: 0,
		type: '',
	};

	// Business service cards can be mixed with extensions. Check product type to properly set the card type.
	// For extensions and themes, type is not set on product object.
	const type =
		product.type === ProductType.businessService
			? product.type
			: props.type;

	const isTheme = type === ProductType.theme;
	const isBusinessService = type === ProductType.businessService;
	const { iamSettings } = useContext( MarketplaceContext );
	const shouldShowPreview =
		iamSettings?.product_previews === 'modal' &&
		! isTheme &&
		! isBusinessService;

	const showVendor = ! isCompact && ! isLoading;
	const showVendorLoading = ! isCompact && isLoading;
	const showDescription = ! isTheme && ! isCompact;
	const showCardIcon = ! isTheme || isCompact;
	const showBigImage = isTheme && ! isCompact;
	const decodedDescription = decodeEntities( product.description );

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
		const { tracksData } = props;

		if ( tracksData.position ) {
			data.position = tracksData.position;
		}

		if ( tracksData.label ) {
			data.label = tracksData.label;
		}

		if ( tracksData.group ) {
			data.group = tracksData.group;
		}

		if ( tracksData.group_id ) {
			data.group_id = tracksData.group_id;
		}

		if ( tracksData.searchTerm ) {
			data.search_term = tracksData.searchTerm;
		}

		if ( tracksData.category ) {
			data.category = tracksData.category;
		}

		data.tab = query.tab || 'discover';

		queueRecordEvent( event, data );
	}

	const screenReaderText = (
		<span className="screen-reader-text">
			{ __( 'Opens in a new tab', 'woocommerce' ) }
		</span>
	);

	const createVendorLink = ( eventName: string ) => {
		if ( ! product?.vendorName || ! product?.vendorUrl ) {
			return product?.vendorName || null;
		}

		return (
			<a
				href={ product.vendorUrl }
				rel="noopener noreferrer"
				target="_blank"
				onClick={ () => {
					recordTracksEvent( eventName, {
						product: product.title,
						vendor: product.vendorName,
						product_type: type,
					} );
				} }
			>
				{ product.vendorName }
				{ screenReaderText }
			</a>
		);
	};

	const productVendor = createVendorLink(
		'marketplace_product_card_vendor_clicked'
	);

	const productUrl = () => {
		if ( query.ref ) {
			return appendURLParams( product.url, [
				[ 'utm_content', query.ref ],
			] );
		}
		return product.url;
	};

	const handleCardClick = () => {
		recordTracksEvent( 'marketplace_product_card_clicked', {
			product_id: product.id,
			product_name: product.title,
			vendor: product.vendorName,
			product_type: type,
		} );

		if ( shouldShowPreview ) {
			setIsPreviewModalOpen( true );
		}
	};

	const handleModalOpen = () => {
		const data: ExtraProperties = {
			product_id: product.id,
			product_name: product.title,
			vendor: product.vendorName,
			product_type: type,
		};

		recordTracksEvent( 'marketplace_product_preview_modal_opened', data );
	};

	const handleModalClose = ( closeType?: string ) => {
		const data: ExtraProperties = {
			product_id: product.id,
			product_name: product.title,
			vendor: product.vendorName,
			product_type: type,
		};

		const tracksEvent =
			closeType || 'marketplace_product_preview_modal_dismissed';

		recordTracksEvent( tracksEvent, data );
		setIsPreviewModalOpen( false );
	};

	const classNames = clsx(
		'woocommerce-marketplace__product-card',
		`woocommerce-marketplace__product-card--${ type }`,
		{
			'is-loading': isLoading,
			'is-small': props.small,
			'is-sponsored': isSponsored(),
			'is-compact': isCompact,
		}
	);

	const CardLink = () => {
		return (
			<a
				ref={ linkRef }
				className="woocommerce-marketplace__product-card__link"
				href={ productUrl() }
				rel="noopener noreferrer"
				target="_blank"
				onClick={ ( e ) => {
					if ( shouldShowPreview ) {
						e.preventDefault();
						handleCardClick();
					} else {
						recordTracksEvent( 'marketplace_product_card_clicked', {
							product_id: product.id,
							product_name: product.title,
							vendor: product.vendorName,
							product_type: type,
						} );
					}
				} }
			>
				{ isLoading ? ' ' : product.title }
				{ screenReaderText }
			</a>
		);
	};

	const BusinessService = () => {
		const mainImage = isCompact ? product.icon : product.featuredImage;
		const imageHeight = isCompact ? 96 : 288;
		return (
			<div className="woocommerce-marketplace__business-card">
				<div
					className="woocommerce-marketplace__business-card__header"
					style={ { backgroundColor: product.color } }
				>
					<img
						src={ `${
							mainImage || product.featuredImage
						}?h=${ imageHeight }` }
						alt=""
					/>
				</div>
				<div className="woocommerce-marketplace__business-card__content">
					<div className="woocommerce-marketplace__business-card__main-content">
						<h2>
							<CardLink />
						</h2>
						<p className="woocommerce-marketplace__product-card__description">
							{ decodedDescription }
						</p>
					</div>
					<div className="woocommerce-marketplace__business-card__badge">
						<span>{ product.productCategory }</span>
					</div>
				</div>
			</div>
		);
	};

	const footer = ! isBusinessService ? (
		<footer className="woocommerce-marketplace__product-card__footer">
			{ isLoading && (
				<div className="woocommerce-marketplace__product-card__price" />
			) }
			{ ! isLoading && props.product && (
				<ProductCardFooter product={ props.product } />
			) }
		</footer>
	) : null;

	const cardContent = (
		<div className="woocommerce-marketplace__product-card__content">
			{ showBigImage && (
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
					{ showCardIcon && (
						<>
							{ isLoading && (
								<div className="woocommerce-marketplace__product-card__icon" />
							) }
							{ ! isLoading && product.icon && (
								<img
									className="woocommerce-marketplace__product-card__icon"
									src={ product.icon || product.image }
									alt={ product.title }
								/>
							) }
						</>
					) }
					<div className="woocommerce-marketplace__product-card__meta">
						<h2 className="woocommerce-marketplace__product-card__title">
							<CardLink />
						</h2>
						{ showVendorLoading && (
							<p className="woocommerce-marketplace__product-card__vendor-details">
								<span className="woocommerce-marketplace__product-card__vendor" />
							</p>
						) }
						{ showVendor && (
							<p className="woocommerce-marketplace__product-card__vendor-details">
								{ productVendor && (
									<span className="woocommerce-marketplace__product-card__vendor">
										<span>
											{ __( 'By ', 'woocommerce' ) }
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
										{ __( 'Sponsored', 'woocommerce' ) }
									</span>
								) }
							</p>
						) }
						{ isCompact && footer }
					</div>
				</div>
			</div>
			{ showDescription && (
				<p className="woocommerce-marketplace__product-card__description">
					{ ! isLoading && decodedDescription }
				</p>
			) }
			{ ! isCompact && footer }
		</div>
	);

	const CardWrapper = () => {
		if ( isLoading ) {
			return isBusinessService ? <BusinessService /> : cardContent;
		}

		return (
			<div className="woocommerce-marketplace__product-card-wrapper">
				{ isBusinessService ? <BusinessService /> : cardContent }
			</div>
		);
	};

	return (
		<>
			<Card
				className={ classNames }
				id={ `product-${ product.id }` }
				tabIndex={ -1 }
				aria-hidden={ isLoading }
				style={ inlineCss() }
			>
				<CardWrapper />
			</Card>

			{ shouldShowPreview && isPreviewModalOpen && product && (
				<ProductPreviewModal
					productTitle={ product.title }
					productVendor={ createVendorLink(
						'marketplace_product_preview_vendor_clicked'
					) }
					productIcon={ product.icon || '' }
					onOpen={ handleModalOpen }
					onClose={ handleModalClose }
					productId={ product.id as number }
					triggerRef={ linkRef }
				/>
			) }
		</>
	);
}

export default ProductCard;
