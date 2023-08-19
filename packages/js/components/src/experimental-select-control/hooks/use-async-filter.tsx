/**
 * External dependencies
 */
import { Spinner } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import {
	useCallback,
	useState,
	createElement,
	useEffect,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SelectControlProps } from '../select-control';
import { SuffixIcon } from '../suffix-icon';

export const DEFAULT_DEBOUNCE_TIME = 250;

export default function useAsyncFilter< T >( {
	filter,
	onFilterStart,
	onFilterEnd,
	onFilterError,
	debounceTime,
	fetchOnMount = false,
}: UseAsyncFilterInput< T > ): UseAsyncFilterOutput< T > {
	const [ isFetching, setIsFetching ] = useState( false );

	function doFilter( value?: string ) {
		if ( typeof filter === 'function' ) {
			if ( typeof onFilterStart === 'function' ) onFilterStart( value );

			setIsFetching( true );

			filter( value )
				.then( ( filteredItems ) => {
					if ( typeof onFilterEnd === 'function' )
						onFilterEnd( filteredItems, value );
				} )
				.catch( ( error: Error ) => {
					if ( typeof onFilterError === 'function' )
						onFilterError( error, value );
				} )
				.finally( () => {
					setIsFetching( false );
				} );
		}
	}

	const handleInputChange = useCallback( doFilter, [
		filter,
		onFilterStart,
		onFilterEnd,
		onFilterError,
	] );

	useEffect(
		function doFilterOnMount() {
			if ( fetchOnMount ) {
				doFilter();
			}
		},
		[ fetchOnMount ]
	);

	return {
		isFetching,
		suffix:
			isFetching === true ? (
				<SuffixIcon icon={ <Spinner /> } />
			) : undefined,
		getFilteredItems: ( items ) => items,
		onInputChange: useDebounce(
			handleInputChange,
			typeof debounceTime === 'number'
				? debounceTime
				: DEFAULT_DEBOUNCE_TIME
		),
	};
}

export type UseAsyncFilterInput< T > = {
	filter( value?: string ): Promise< T[] >;
	onFilterStart?( value?: string ): void;
	onFilterEnd?( filteredItems: T[], value?: string ): void;
	onFilterError?( error: Error, value?: string ): void;
	debounceTime?: number;
	fetchOnMount?: boolean;
};

export type UseAsyncFilterOutput< T > = Pick<
	SelectControlProps< T >,
	'suffix' | 'onInputChange' | 'getFilteredItems'
> & {
	isFetching: boolean;
};
