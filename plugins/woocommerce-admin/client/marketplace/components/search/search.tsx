/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, search } from '@wordpress/icons';
import { useEffect, useState } from '@wordpress/element';
import { navigateTo, getNewPath, useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './search.scss';
import { MARKETPLACE_PATH } from '../constants';

const searchPlaceholder = __(
	'Search for extensions, themes, and business services',
	'woocommerce'
);

/**
 * Search component.
 *
 * @return {JSX.Element} Search component.
 */
function Search(): JSX.Element {
	const [ searchTerm, setSearchTerm ] = useState( '' );

	const query = useQuery();

	useEffect( () => {
		if ( query.term ) {
			setSearchTerm( query.term );
		} else {
			setSearchTerm( '' );
		}
	}, [ query.term ] );

	useEffect( () => {
		if ( query.tab !== 'search' ) {
			setSearchTerm( '' );
		}
	}, [ query.tab ] );

	const runSearch = () => {
		const term = searchTerm.trim();

		const newQuery: { term?: string; tab?: string } = {};
		if ( term !== '' ) {
			newQuery.term = term;
			newQuery.tab = 'search';
		}

		// When the search term changes, we reset the query string on purpose.
		navigateTo( {
			url: getNewPath( newQuery, MARKETPLACE_PATH, {} ),
		} );

		return [];
	};

	const handleInputChange = (
		event: React.ChangeEvent< HTMLInputElement >
	) => {
		setSearchTerm( event.target.value );
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
		<div className="woocommerce-marketplace__search">
			<label
				className="screen-reader-text"
				htmlFor="woocommerce-marketplace-search-query"
			>
				{ searchPlaceholder }
			</label>
			<input
				id="woocommerce-marketplace-search-query"
				value={ searchTerm }
				className="woocommerce-marketplace__search-input"
				type="search"
				name="woocommerce-marketplace-search-query"
				placeholder={ searchPlaceholder }
				onChange={ handleInputChange }
				onKeyUp={ handleKeyUp }
			/>
			<button
				id="woocommerce-marketplace-search-button"
				className="woocommerce-marketplace__search-button"
				aria-label={ __( 'Search', 'woocommerce' ) }
				onClick={ runSearch }
			>
				<Icon icon={ search } size={ 32 } />
			</button>
		</div>
	);
}

export default Search;
