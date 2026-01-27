/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';
import { getProductBrands } from '@woocommerce/editor-components/utils';
import type { ProductBrandResponseItem } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { formatError } from '../base/utils/errors';

export interface WithInjectedSearchedBrands {
	brands: ProductBrandResponseItem[];
	isLoading: boolean;
	error: { message: string; type: string } | null;
	selected: number[];
}

export interface WithSearchedBrandsProps {
	selected: number[];
}

/**
 * A higher order component that enhances the provided component with brands from a search query.
 */
const withSearchedBrands = <
	T extends Record< string, unknown > & WithSearchedBrandsProps
>(
	OriginalComponent: React.ComponentType< T & WithInjectedSearchedBrands >
) => {
	return ( { selected, ...props }: T ): JSX.Element => {
		const [ isLoading, setIsLoading ] = useState( true );
		const [ error, setError ] = useState< {
			message: string;
			type: string;
		} | null >( null );
		const [ brandsList, setBrandsList ] = useState<
			ProductBrandResponseItem[]
		>( [] );

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
			getProductBrands( { selected: selectedRef.current } )
				.then( ( results ) => {
					setBrandsList( results as ProductBrandResponseItem[] );
					setIsLoading( false );
				} )
				.catch( setErrorState );
		}, [ selectedRef ] );

		return (
			<OriginalComponent
				{ ...( props as T ) }
				selected={ selected }
				error={ error }
				brands={ brandsList }
				isLoading={ isLoading }
			/>
		);
	};
};

export default withSearchedBrands;
