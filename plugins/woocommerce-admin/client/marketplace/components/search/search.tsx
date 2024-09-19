/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { navigateTo, getNewPath, useQuery } from '@woocommerce/navigation';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
// eslint-disable-next-line @woocommerce/dependency-group
import { SearchControl } from '@wordpress/components';
// The @ts-ignore is needed because the SearchControl types are not exported from the @wordpress/components package,
// even though the component itself is. This is likely due to an older version of the package being used.

/**
 * Internal dependencies
 */
import './search.scss';
import { MARKETPLACE_PATH } from '../constants';

/**
 * Search component.
 *
 * @return {JSX.Element} Search component.
 */
function Search(): JSX.Element {
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const searchPlaceholder = __( 'Search Marketplace', 'woocommerce' );

	const query = useQuery();

	useEffect( () => {
		if ( query.term ) {
			setSearchTerm( query.term );
		} else {
			setSearchTerm( '' );
		}
	}, [ query.term ] );

	const runSearch = () => {
		const newQuery: { term?: string; tab?: string } = query;

		// If we're on 'Discover' or 'My subscriptions' when a search is initiated, move to the extensions tab
		if ( ! newQuery.tab || newQuery.tab === 'my-subscriptions' ) {
			newQuery.tab = 'extensions';
		}

		newQuery.term = searchTerm.trim();

		// When the search term changes, we reset the query string on purpose.
		navigateTo( {
			url: getNewPath( newQuery, MARKETPLACE_PATH, {} ),
		} );

		return [];
	};

	const handleKeyUp = ( event: { key: string } ) => {
		if ( event.key === 'Enter' ) {
			runSearch();
		}

		if ( event.key === 'Escape' ) {
			setSearchTerm( '' );
		}
	};

	return (
		<SearchControl
			label={ searchPlaceholder }
			placeholder={ searchPlaceholder }
			value={ searchTerm }
			onChange={ setSearchTerm }
			onKeyUp={ handleKeyUp }
			className="woocommerce-marketplace__search"
		/>
	);
}

export default Search;
