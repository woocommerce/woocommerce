/**
 * External dependencies
 */
import { Fragment, useEffect, useState } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './product-list-content.scss';
import ProductCard from '../product-card/product-card';
import { Product, ProductCardType, ProductType } from '../product-list/types';
import { appendURLParams } from '../../utils/functions';
import { getAdminSetting } from '~/utils/admin-settings';

export default function ProductListContent( props: {
	products: Product[];
	group?: string;
	productGroup?: string;
	type: ProductType;
	cardType?: ProductCardType;
	className?: string;
	searchTerm?: string;
	category?: string;
} ): JSX.Element {
	const wccomHelperSettings = getAdminSetting( 'wccomHelper', {} );

	const [ productsToShow, setProductsToShow ] = useState( props.products );
	const [ columns, setColumns ] = useState( 1 );

	const updateColumns = () => {
		const screenWidth = window.innerWidth;
		if ( screenWidth >= 1920 ) {
			setColumns( 4 );
		} else if ( screenWidth >= 1024 ) {
			setColumns( 3 );
		} else if ( screenWidth >= 769 ) {
			setColumns( 2 );
		} else {
			setColumns( 1 );
		}
	};

	useEffect( () => {
		updateColumns();

		// Update columns on screen resize to adjust for responsive layout
		window.addEventListener( 'resize', updateColumns );

		return () => window.removeEventListener( 'resize', updateColumns );
	}, [] );

	/**
	 * If the product group is set, we are showing featured products. This a curated list
	 * of products, fetched from WooCommerce.com API.
	 *
	 * As a design choice, we decided to:
	 * - Always try to show a full row.
	 * - Show two, three or four products per row depending on the design.
	 *
	 * To do this, this useEffect listens for changes to columns and calculates the number
	 * rows. Depending on the number of rows, it will slice the props.products.
	 */
	useEffect( () => {
		/**
		 * If the product group is not set, don't operate. This could be a search or category result, where we
		 * want to show the full result set.
		 *
		 * If the productGroups set, component is likely used on Discover or NoResult component.
		 */
		if ( ! props.productGroup ) {
			setProductsToShow( props.products );
			return;
		}

		// For compact cards, show all products.
		if ( props.cardType === ProductCardType.compact ) {
			setProductsToShow( props.products );
			return;
		}

		// If we don't have enough products to fill a row, show all products.
		if ( props.products.length < columns ) {
			setProductsToShow( props.products );

			return;
		}

		// Calculate the number of complete rows we can show.
		let completeRows = Math.floor( props.products.length / columns );

		if ( columns === 1 ) {
			completeRows = Math.min( completeRows, 4 );
		}

		if ( columns === 2 ) {
			completeRows = Math.min( completeRows, 2 );
		}

		// Slice the products, this will get rid of any rows that are not fully filled.
		setProductsToShow( props.products.slice( 0, completeRows * columns ) );
	}, [ columns, props.products, props.productGroup, props.cardType ] );

	const classes = clsx(
		'woocommerce-marketplace__product-list-content',
		props.className
	);

	return (
		<>
			<div className={ classes }>
				{ productsToShow.map( ( product, index ) => (
					<Fragment key={ product.id }>
						<ProductCard
							type={ props.type }
							cardType={ props.cardType }
							product={ {
								id: product.id,
								slug: product.slug,
								title: product.title,
								image: product.image,
								type: product.type,
								freemium_type: product.freemium_type,
								icon: product.icon,
								label: product.label,
								primary_color: product.primary_color,
								vendorName: product.vendorName,
								vendorUrl: product.vendorUrl
									? appendURLParams( product.vendorUrl, [
											[
												'utm_source',
												'extensionsscreen',
											],
											[ 'utm_medium', 'product' ],
											[ 'utm_campaign', 'wcaddons' ],
											[ 'utm_content', 'devpartner' ],
									  ] )
									: '',
								price: product.price,
								url: appendURLParams(
									product.url,
									Object.entries( {
										...wccomHelperSettings.inAppPurchaseURLParams,
										...( props.productGroup !== undefined
											? { utm_group: props.productGroup }
											: {} ),
									} )
								),
								averageRating: product.averageRating,
								reviewsCount: product.reviewsCount,
								description: product.description,
								isInstallable: product.isInstallable,
								color: product.color,
								featuredImage: product.featuredImage,
								productCategory: product.productCategory,
								billingPeriod: product.billingPeriod,
								billingPeriodInterval:
									product.billingPeriodInterval,
								currency: product.currency,
								isOnSale: product.isOnSale,
								regularPrice: product.regularPrice,
							} }
							tracksData={ {
								position: index + 1,
								...( product.label && {
									label: product.label,
								} ),
								...( props.productGroup && {
									group_id: props.productGroup,
								} ),
								...( props.group && { group: props.group } ),
								...( props.searchTerm && {
									searchTerm: props.searchTerm,
								} ),
								...( props.category && {
									category: props.category,
								} ),
							} }
						/>
					</Fragment>
				) ) }
			</div>
		</>
	);
}
