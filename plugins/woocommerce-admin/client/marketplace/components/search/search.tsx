/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './search.scss';

const searchPlaceholder = __( 'Search extensions and themes', 'woocommerce' );

const marketplaceAPI = 'https://woocommerce.com/wp-json';

export interface SearchProps {
	locale?: string | 'en_US';
	country?: string | undefined;
}

/**
 * Search component.
 *
 * @param {SearchProps} props - Search props: locale and country.
 * @return {JSX.Element} Search component.
 */
function Search( props: SearchProps ): JSX.Element {
	const locale = props.locale ?? 'en_US';
	const country = props.country ?? '';

	const build_parameter_string = (
		query_string: string,
		query_country: string,
		query_locale: string
	): string => {
		const params = new URLSearchParams();
		params.append( 'term', query_string );
		params.append( 'country', query_country );
		params.append( 'locale', query_locale );
		return params.toString();
	};

	const runSearch = () => {
		const query = (
			document.getElementById( 'search-query' ) as HTMLInputElement
		 ).value.trim();

		const params = build_parameter_string( query, country, locale );
		fetch( marketplaceAPI + '?' + params, {
			method: 'GET',
		} )
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				return response;
			} );
		return [];
	};

	const handleKeyUp = ( event: { key: string } ) => {
		if ( event.key === 'Enter' ) {
			runSearch();
		}
	};

	const renderSearch = () => {
		return (
			<div className="marketplace-search-form">
				<label className="screen-reader-text" htmlFor="search-query">
					{ searchPlaceholder }
				</label>
				<input
					id="search-query"
					className="search-form__search-input"
					type="search"
					name="search-query"
					placeholder={ searchPlaceholder }
					onKeyUp={ handleKeyUp }
				/>
				<button
					id="wccom-header-search-button"
					className="search-form__search-button"
					aria-label={ __( 'Search', 'woocommerce' ) }
					onClick={ runSearch }
				>
					<Icon icon={ search } size={ 32 } />
				</button>
			</div>
		);
	};

	return <div className="marketplace-search-wrapper">{ renderSearch() }</div>;
}

export default Search;
