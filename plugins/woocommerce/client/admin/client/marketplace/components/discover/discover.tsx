/**
 * External dependencies
 */
import { useContext, useEffect, useRef, useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ProductList from '../product-list/product-list';
import { fetchDiscoverPageData, ProductGroup } from '../../utils/functions';
import ProductLoader from '../product-loader/product-loader';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { ProductCardType, ProductType } from '../product-list/types';
import './discover.scss';
import { recordMarketplaceView } from '~/marketplace/utils/tracking';

export default function Discover(): JSX.Element | null {
	const [ productGroups, setProductGroups ] = useState<
		Array< ProductGroup >
	>( [] );
	const groupElements = useRef< Record< string, HTMLDivElement | null > >(
		{}
	);
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading, setIsLoading } = marketplaceContextValue;

	function recordTracksEvent( products: ProductGroup[] ) {
		const product_ids = products
			.flatMap( ( group ) => group.items )
			.map( ( product ) => product.id );
		const groups = Object.fromEntries(
			products.map( ( group ) => [
				group.id,
				group.items.map( ( product ) => product.id ),
			] )
		);

		// This is a new event specific to the Discover tab, added with Woo 8.4.
		recordEvent( 'marketplace_discover_viewed', {
			view: 'discover',
			product_ids,
			groups,
		} );

		// This is the new page view event added with Woo 8.3. It's improved with the marketplace_discover_viewed event
		// but we'll keep it for a while to keep it compatible.
		recordMarketplaceView( {
			view: 'discover',
		} );
	}

	function recordGroupViewedTrackEvent( group: ProductGroup ) {
		recordEvent( 'marketplace_discover_group_viewed', {
			view: 'discover',
			group_id: group.id,
			product_ids: group.items.map( ( product ) => product.id ),
		} );
	}

	// Get the content for this screen
	useEffect( () => {
		setIsLoading( true );

		fetchDiscoverPageData()
			.then(
				( response: Array< ProductGroup > | { success: boolean } ) => {
					if ( ! Array.isArray( response ) ) {
						return [];
					}
					return response as Array< ProductGroup >;
				}
			)
			.then( ( products: Array< ProductGroup > ) => {
				setProductGroups( products );
				recordTracksEvent( products );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ setIsLoading ] );

	useEffect( () => {
		if (
			isLoading ||
			! productGroups.length ||
			! ( 'IntersectionObserver' in window )
		) {
			return;
		}

		const productGroupsById = new Map(
			productGroups.map( ( productGroup ) => [
				productGroup.id,
				productGroup,
			] )
		);
		const seenGroups = new Set< string >();

		const observer = new IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( ! entry.isIntersecting ) {
						return;
					}

					const groupId = ( entry.target as HTMLDivElement ).dataset
						.groupId;

					if ( ! groupId || seenGroups.has( groupId ) ) {
						return;
					}

					const group = productGroupsById.get( groupId );

					if ( ! group ) {
						return;
					}

					recordGroupViewedTrackEvent( group );
					seenGroups.add( groupId );
					observer.unobserve( entry.target );
				} );
			},
			{ threshold: 0.25 }
		);

		productGroups.forEach( ( group ) => {
			const groupElement = groupElements.current[ group.id ];

			if ( groupElement ) {
				observer.observe( groupElement );
			}
		} );

		return () => {
			observer.disconnect();
		};
	}, [ isLoading, productGroups ] );

	if ( isLoading ) {
		return (
			<div className="woocommerce-marketplace__discover">
				<ProductLoader
					placeholderCount={ 9 }
					type={ ProductType.extension }
				/>
			</div>
		);
	}

	const groupsList = productGroups.flatMap( ( group ) => group );
	return (
		<div className="woocommerce-marketplace__discover">
			{ groupsList.map( ( groups ) => (
				<ProductList
					key={ groups.id }
					productGroup={ groups.id }
					title={ groups.title }
					description={ groups.description }
					products={ groups.items }
					groupURL={ groups.url }
					groupURLText={ groups.url_text }
					groupURLType={ groups.url_type }
					type={ groups.itemType }
					cardType={ groups.cardType ?? ProductCardType.regular }
					groupId={ groups.id }
					containerRef={ ( element ) => {
						groupElements.current[ groups.id ] = element;
					} }
				/>
			) ) }
		</div>
	);
}
