/**
 * External dependencies
 */
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { blocksConfig } from '@woocommerce/block-settings';
import { getProducts } from '@woocommerce/editor-components/utils';
import { useDebouncedCallback } from 'use-debounce';
import type {
	ProductResponseItem,
	WithInjectedSearchedProducts,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { formatError } from '../base/utils/errors';

interface WithSearchedProductProps {
	selected: number[];
}

/**
 * A higher order component that enhances the provided component with products from a search query.
 */
const withSearchedProducts = <
	T extends Record< string, unknown > & WithSearchedProductProps
>(
	OriginalComponent: React.ComponentType< T & WithInjectedSearchedProducts >
) => {
	return ( { selected, ...props }: T ): JSX.Element => {
		const [ isLoading, setIsLoading ] = useState( true );
		const [ error, setError ] = useState< {
			message: string;
			type: string;
		} | null >( null );
		const [ productsList, setProductsList ] = useState<
			ProductResponseItem[]
		>( [] );
		const isLargeCatalog = blocksConfig.productCount > 100;

		const setErrorState = async ( e: {
			message: string;
			type: string;
		} ) => {
			const formattedError = ( await formatError( e ) ) as {
				message: string;
				type: string;
			};
			setError( formattedError );
			setIsLoading( false );
		};

		const selectedRef = useRef( selected );

		useEffect( () => {
			getProducts( { selected: selectedRef.current } )
				.then( ( results ) => {
					setProductsList( results as ProductResponseItem[] );
					setIsLoading( false );
				} )
				.catch( setErrorState );
		}, [ selectedRef ] );

		const debouncedSearch = useDebouncedCallback( ( search: string ) => {
			getProducts( { selected, search } )
				.then( ( results ) => {
					setProductsList( results as ProductResponseItem[] );
					setIsLoading( false );
				} )
				.catch( setErrorState );
		}, 400 );

		const onSearch = useCallback(
			( search: string ) => {
				setIsLoading( true );
				debouncedSearch( search );
			},
			[ setIsLoading, debouncedSearch ]
		);

		return (
			<OriginalComponent
				{ ...( props as T ) }
				selected={ selected }
				error={ error }
				products={ productsList }
				isLoading={ isLoading }
				onSearch={ isLargeCatalog ? onSearch : null }
			/>
		);
	};
};

export default withSearchedProducts;
