/**
 * External dependencies
 */
import { getProducts } from '@woocommerce/editor-components/utils';
import { ProductResponseItem } from '@woocommerce/types';
import { decodeEntities } from '@wordpress/html-entities';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { blocksConfig } from '@woocommerce/block-settings';
import {
	FormTokenField,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { QueryControlProps, CoreFilterNames } from '../../types';
import { DEFAULT_FILTERS } from '../../constants';

/**
 * Returns:
 * - productsMap: Map of products by id and name.
 * - productsList: List of products retrieved.
 */
function useProducts(
	isLargeCatalog: boolean,
	search: string,
	selected: string[] = []
) {
	// Creating a map for fast lookup of products by id or name.
	const [ productsMap, setProductsMap ] = useState<
		Map< number | string, ProductResponseItem >
	>( new Map() );

	// List of products retrieved
	const [ productsList, setProductsList ] = useState< ProductResponseItem[] >(
		[]
	);

	// Flag to check if products are loaded
	const [ productsLoaded, setProductsLoaded ] = useState( false );

	useEffect( () => {
		// We take two strategies here because of internal logic of
		// `getProducts` and `getProductsRequests` that skips request for
		// `selected` products for small stores. So fetching products per user's input
		// breaks selected items:
		// 1. For large stores (>100 products) we fetch products as input changes AND
		// `selected` products.
		// 2. For small stores (<=100 products) we fetch all products just once.

		const query = {
			selected: isLargeCatalog ? selected.map( Number ) : [],
			queryArgs: isLargeCatalog
				? {
						search,
						// Limit search to 40 results. If results are not satisfying
						// user needs to type more characters to get closer to actual
						// product name.
						per_page: 40,
				  }
				: {
						// For a small catalog we fetch all the products.
						per_page: 0,
				  },
		};
		getProducts( query ).then( ( results ) => {
			const newProductsMap = new Map();
			( results as ProductResponseItem[] ).forEach( ( product ) => {
				newProductsMap.set( product.id, product );
				newProductsMap.set( product.name, product );
			} );

			setProductsList( results as ProductResponseItem[] );
			setProductsMap( newProductsMap );
			setProductsLoaded( true );
		} );
	}, [ isLargeCatalog, search, selected ] );

	return { productsMap, productsList, productsLoaded };
}

export const HandPickedProductsControlField = ( {
	query,
	trackInteraction,
	setQueryAttribute,
}: QueryControlProps ) => {
	const isLargeCatalog = ( blocksConfig.productCount || 0 ) > 100;
	const selectedProductIds = query.woocommerceHandPickedProducts;
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const { productsMap, productsList, productsLoaded } = useProducts(
		isLargeCatalog,
		searchQuery,
		selectedProductIds
	);
	const handleSearch = useDebounce( setSearchQuery, 250 );

	// Filter out any selected product IDs that no longer exist
	const validSelectedProductIds = useMemo( () => {
		if ( ! selectedProductIds?.length || ! productsMap.size )
			return selectedProductIds || [];
		return selectedProductIds.filter( ( id ) => {
			const product = productsMap.get( Number( id ) );
			return !! product;
		} );
	}, [ selectedProductIds, productsMap ] );

	// Updates the query attribute when invalid products are filtered out
	useEffect( () => {
		if ( validSelectedProductIds.length !== selectedProductIds.length ) {
			setQueryAttribute( {
				woocommerceHandPickedProducts: validSelectedProductIds,
			} );
		}
	}, [ validSelectedProductIds, selectedProductIds, setQueryAttribute ] );

	const onTokenChange = useCallback(
		( values: string[] ) => {
			// Map the tokens to product ids.
			const newHandPickedProductsSet = values.reduce(
				( acc, nameOrId ) => {
					const product =
						productsMap.get( nameOrId ) ||
						productsMap.get( Number( nameOrId ) );
					if ( product ) acc.add( String( product.id ) );
					return acc;
				},
				new Set< string >()
			);

			setQueryAttribute( {
				woocommerceHandPickedProducts: Array.from(
					newHandPickedProductsSet
				),
			} );
			trackInteraction( CoreFilterNames.HAND_PICKED );
		},
		[ setQueryAttribute, trackInteraction, productsMap ]
	);

	const suggestions = useMemo( () => {
		return (
			productsList
				// Filter out products that are already selected.
				.filter(
					( product ) =>
						! validSelectedProductIds?.includes(
							String( product.id )
						)
				)
				.map( ( product ) => product.name )
		);
	}, [ productsList, validSelectedProductIds ] );

	/**
	 * Transforms a token into a product name.
	 * - If the token is a number, it will be used to lookup the product name.
	 * - Otherwise, the token will be used as is.
	 */
	const transformTokenIntoProductName = ( token: string ) => {
		const parsedToken = Number( token );

		if ( Number.isNaN( parsedToken ) ) {
			return decodeEntities( token ) || '';
		}

		const product = productsMap.get( parsedToken );

		return decodeEntities( product?.name ) || '';
	};

	return (
		<FormTokenField
			displayTransform={ transformTokenIntoProductName }
			label={ __( 'Hand-Picked', 'woocommerce' ) }
			onChange={ onTokenChange }
			onInputChange={ isLargeCatalog ? handleSearch : undefined }
			suggestions={ suggestions }
			// @ts-expect-error Using experimental features
			__experimentalValidateInput={ ( value: string ) =>
				productsMap.has( value )
			}
			value={
				! productsLoaded
					? [ __( 'Loading…', 'woocommerce' ) ]
					: validSelectedProductIds || []
			}
			__experimentalExpandOnFocus={ true }
			__experimentalShowHowTo={ false }
			placeholder={ __(
				'Search for products to display…',
				'woocommerce'
			) }
		/>
	);
};

const HandPickedProductsControl = ( {
	query,
	trackInteraction,
	setQueryAttribute,
}: QueryControlProps ) => {
	const selectedProductIds = query.woocommerceHandPickedProducts;
	const deselectCallback = () => {
		setQueryAttribute( {
			woocommerceHandPickedProducts:
				DEFAULT_FILTERS.woocommerceHandPickedProducts,
		} );
		trackInteraction( CoreFilterNames.HAND_PICKED );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Hand-Picked', 'woocommerce' ) }
			hasValue={ () => !! selectedProductIds?.length }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<HandPickedProductsControlField
				query={ query }
				trackInteraction={ trackInteraction }
				setQueryAttribute={ setQueryAttribute }
			/>
		</ToolsPanelItem>
	);
};

export default HandPickedProductsControl;
