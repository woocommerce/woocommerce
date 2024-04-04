/**
 * External dependencies
 */
import { getProducts } from '@woocommerce/editor-components/utils';
import { ProductResponseItem } from '@woocommerce/types';
import { decodeEntities } from '@wordpress/html-entities';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	FormTokenField,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { QueryControlProps } from '../../types';

/**
 * Returns:
 * - productsMap: Map of products by id and name.
 * - productsList: List of products retrieved.
 */
function useProducts() {
	// Creating a map for fast lookup of products by id or name.
	const [ productsMap, setProductsMap ] = useState<
		Map< number | string, ProductResponseItem >
	>( new Map() );

	// List of products retrieved
	const [ productsList, setProductsList ] = useState< ProductResponseItem[] >(
		[]
	);

	useEffect( () => {
		getProducts( {
			selected: [],
			queryArgs: {
				// Fetch all products.
				per_page: 0,
			},
		} ).then( ( results ) => {
			const newProductsMap = new Map();
			( results as ProductResponseItem[] ).forEach( ( product ) => {
				newProductsMap.set( product.id, product );
				newProductsMap.set( product.name, product );
			} );

			setProductsList( results as ProductResponseItem[] );
			setProductsMap( newProductsMap );
		} );
	}, [] );

	return { productsMap, productsList };
}

const HandPickedProductsControl = ( {
	query,
	setQueryAttribute,
}: QueryControlProps ) => {
	const selectedProductIds = query.woocommerceHandPickedProducts;
	const { productsMap, productsList } = useProducts();

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
		},
		[ setQueryAttribute, productsMap ]
	);

	const suggestions = useMemo( () => {
		return (
			productsList
				// Filter out products that are already selected.
				.filter(
					( product ) =>
						! selectedProductIds?.includes( String( product.id ) )
				)
				.map( ( product ) => product.name )
		);
	}, [ productsList, selectedProductIds ] );

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

	const deselectCallback = () => {
		setQueryAttribute( {
			woocommerceHandPickedProducts: [],
		} );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Hand-picked Products', 'woocommerce' ) }
			hasValue={ () => !! selectedProductIds?.length }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<FormTokenField
				disabled={ ! productsMap.size }
				displayTransform={ transformTokenIntoProductName }
				label={ __( 'Hand-picked Products', 'woocommerce' ) }
				onChange={ onTokenChange }
				suggestions={ suggestions }
				// @ts-expect-error Using experimental features
				__experimentalValidateInput={ ( value: string ) =>
					productsMap.has( value )
				}
				value={
					! productsMap.size
						? [ __( 'Loading…', 'woocommerce' ) ]
						: selectedProductIds || []
				}
				__experimentalExpandOnFocus={ true }
				__experimentalShowHowTo={ false }
			/>
		</ToolsPanelItem>
	);
};

export default HandPickedProductsControl;
