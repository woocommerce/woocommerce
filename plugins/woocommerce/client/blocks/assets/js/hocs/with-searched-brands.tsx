/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { getBrands } from '@woocommerce/editor-components/utils';
import type {
	ProductBrandResponseItem,
	WithInjectedSearchedBrands,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { formatError } from '../base/utils/errors';

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

		useEffect( () => {
			getBrands( {} )
				.then( ( results ) => {
					setBrandsList( results as ProductBrandResponseItem[] );
					setIsLoading( false );
				} )
				.catch( setErrorState );
		}, [] );

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
