/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';
import { getCategories } from '@woocommerce/editor-components/utils';
import type {
	ProductCategoryResponseItem,
	WithInjectedSearchedCategories,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { formatError } from '../base/utils/errors';

export interface WithSearchedCategoriesProps {
	selected: number[];
}

/**
 * A higher order component that enhances the provided component with categories from a search query.
 */
const withSearchedCategories = <
	T extends Record< string, unknown > & WithSearchedCategoriesProps
>(
	OriginalComponent: React.ComponentType< T & WithInjectedSearchedCategories >
) => {
	return ( { selected, ...props }: T ): JSX.Element => {
		const [ isLoading, setIsLoading ] = useState( true );
		const [ error, setError ] = useState< {
			message: string;
			type: string;
		} | null >( null );
		const [ categoriesList, setCategoriesList ] = useState<
			ProductCategoryResponseItem[]
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
			getCategories( { selected: selectedRef.current } )
				.then( ( results ) => {
					setCategoriesList(
						results as ProductCategoryResponseItem[]
					);
					setIsLoading( false );
				} )
				.catch( setErrorState );
		}, [ selectedRef ] );

		return (
			<OriginalComponent
				{ ...( props as T ) }
				selected={ selected }
				error={ error }
				categories={ categoriesList }
				isLoading={ isLoading }
			/>
		);
	};
};

export default withSearchedCategories;
