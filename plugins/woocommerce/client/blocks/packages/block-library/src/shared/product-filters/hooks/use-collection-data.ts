/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

type RatingCount = {
	rating: number;
	count: number;
};

type StockStatusCount = {
	status: string;
	count: number;
};

type TaxonomyCount = {
	term: number;
	count: number;
};

type AttributeCount = {
	term: number;
	count: number;
};

type PriceRange = {
	min_price?: string;
	max_price?: string;
	currency_code?: string;
	currency_symbol?: string;
	currency_minor_unit?: number;
	currency_decimal_separator?: string;
	currency_thousand_separator?: string;
	currency_prefix?: string;
	currency_suffix?: string;
};

type CollectionData = {
	rating_counts?: RatingCount[];
	stock_status_counts?: StockStatusCount[];
	taxonomy_counts?: TaxonomyCount[];
	attribute_counts?: AttributeCount[];
	price_range?: PriceRange;
};

type AttributeQuery = {
	taxonomy: string;
	queryType: string;
};

type UseCollectionDataProps = {
	queryRating?: boolean;
	queryStock?: boolean;
	queryPrices?: boolean;
	queryTaxonomy?: string;
	queryAttribute?: AttributeQuery;
	queryState?: Record< string, unknown >;
	isEditor?: boolean;
};

const EMPTY_QUERY_STATE = {};

export const useCollectionData = ( {
	queryRating,
	queryStock,
	queryPrices,
	queryTaxonomy,
	queryAttribute,
	queryState = EMPTY_QUERY_STATE,
	isEditor = false,
}: UseCollectionDataProps ) => {
	const [ data, setData ] = useState< CollectionData >( {} );
	const [ isLoading, setIsLoading ] = useState( isEditor );
	const queryStateKey = JSON.stringify( queryState );

	const query = useMemo( () => {
		const requestQueryState = JSON.parse( queryStateKey ) as Record<
			string,
			unknown
		>;

		return {
			...requestQueryState,
			page: undefined,
			per_page: undefined,
			orderby: undefined,
			order: undefined,
			...( queryRating && { calculate_rating_counts: true } ),
			...( queryStock && { calculate_stock_status_counts: true } ),
			...( queryPrices && { calculate_price_range: true } ),
			...( queryTaxonomy && {
				calculate_taxonomy_counts: [ queryTaxonomy ],
			} ),
			...( queryAttribute?.taxonomy && {
				calculate_attribute_counts: [
					{
						taxonomy: queryAttribute.taxonomy,
						query_type: queryAttribute.queryType,
					},
				],
			} ),
		};
	}, [
		queryAttribute?.queryType,
		queryAttribute?.taxonomy,
		queryPrices,
		queryRating,
		queryStateKey,
		queryStock,
		queryTaxonomy,
	] );

	useEffect( () => {
		let isMounted = true;

		setIsLoading( true );
		apiFetch( {
			path: addQueryArgs(
				'/wc/store/v1/products/collection-data',
				query
			),
		} )
			.then( ( response ) => {
				if ( isMounted ) {
					setData( response as CollectionData );
				}
			} )
			.catch( () => {
				if ( isMounted ) {
					setData( {} );
				}
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ query ] );

	return { data, isLoading };
};
