/**
 * External dependencies
 */
import { useContext, useEffect, useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ProductList from '../product-list/product-list';
import { fetchDiscoverPageData, ProductGroup } from '../../utils/functions';
import ProductLoader from '../product-loader/product-loader';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { ProductType } from '../product-list/types';
import './discover.scss';
import { recordMarketplaceView } from '~/marketplace/utils/tracking';

export default function Discover(): JSX.Element | null {
	const [ productGroups, setProductGroups ] = useState<
		Array< ProductGroup >
	>( [] );
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading, setIsLoading } = marketplaceContextValue;

	function recordTracksEvent( products: ProductGroup[] ) {
		const product_ids = products
			.flatMap( ( group ) => group.items )
			.map( ( product ) => {
				return product.id;
			} );

		// This is a new event specific to the Discover tab, added with Woo 8.4.
		recordEvent( 'marketplace_discover_viewed', {
			view: 'discover',
			product_ids,
		} );

		// This is the new page view event added with Woo 8.3. It's improved with the marketplace_discover_viewed event
		// but we'll keep it for a while to keep it compatible.
		recordMarketplaceView( {
			view: 'discover',
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
	}, [] );

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
					products={ groups.items }
					groupURL={ groups.url }
					type={ groups.itemType }
				/>
			) ) }
		</div>
	);
}
