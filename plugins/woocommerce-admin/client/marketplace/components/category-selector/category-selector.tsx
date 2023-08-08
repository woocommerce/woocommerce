/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CategoryLink from './category-link';
import './category-selector.scss';
import CategoryDropdown from './category-dropdown';
import { MARKETPLACE_URL } from '../constants';

export type Category = {
	readonly slug: string;
	readonly label: string;
	selected: boolean;
};

export type CategoryAPIItem = {
	readonly slug: string;
	readonly label: string;
};

function fetchCategories(): Promise< CategoryAPIItem[] > {
	return fetch( MARKETPLACE_URL + '/wp-json/wccom-extensions/1.0/categories' )
		.then( ( response ) => {
			if ( ! response.ok ) {
				throw new Error( response.statusText );
			}

			return response.json();
		} )
		.then( ( json ) => {
			return json;
		} )
		.catch( () => {
			return [];
		} );
}

export default function CategorySelector(): JSX.Element {
	const [ firstBatch, setFirstBatch ] = useState< Category[] >( [] );
	const [ secondBatch, setSecondBatch ] = useState< Category[] >( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	useEffect( () => {
		setIsLoading( true );
		fetchCategories()
			.then( ( categoriesFromAPI: CategoryAPIItem[] ) => {
				const categories: Category[] = categoriesFromAPI.map(
					( categoryAPIItem: CategoryAPIItem ): Category => {
						return {
							...categoryAPIItem,
							selected: false,
						};
					}
				);

				// Put the "All" category to the beginning
				categories.sort( ( a ) => {
					if ( a.slug === '_all' ) {
						return -1;
					}

					return 1;
				} );

				// Split array into two from 7th item
				const firstBatchCategories = categories.slice( 0, 7 );
				const secondBatchCategories = categories.slice( 7 );

				setFirstBatch( firstBatchCategories );
				setSecondBatch( secondBatchCategories );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	if ( isLoading ) {
		return (
			<>
				{ __( 'Loading categories…', 'woocommerce' ) }
				<Spinner />
			</>
		);
	}

	return (
		<>
			<ul className="woocommerce-marketplace__category-selector">
				{ firstBatch.map( ( category ) => (
					<li
						className="woocommerce-marketplace__category-item"
						key={ category.slug }
					>
						<CategoryLink { ...category } />
					</li>
				) ) }
				<li className="woocommerce-marketplace__category-item">
					<CategoryDropdown
						label={ __( 'More', 'woocommerce' ) }
						categories={ secondBatch }
						buttonClassName="woocommerce-marketplace__category-item-button"
						contentClassName="woocommerce-marketplace__category-item-content"
						arrowIconSize={ 20 }
					/>
				</li>
			</ul>

			<div className="woocommerce-marketplace__category-selector--full-width">
				<CategoryDropdown
					label={ __( 'All Categories', 'woocommerce' ) }
					categories={ firstBatch.concat( secondBatch ) }
					buttonClassName="woocommerce-marketplace__category-dropdown-button"
					className="woocommerce-marketplace__category-dropdown"
					contentClassName="woocommerce-marketplace__category-dropdown-content"
				/>
			</div>
		</>
	);
}
