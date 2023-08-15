/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { useQuery } from '@woocommerce/navigation';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import CategoryLink from './category-link';
import CategoryDropdown from './category-dropdown';
import { Category, CategoryAPIItem } from './types';
import { fetchCategories } from '../../utils/functions';
import './category-selector.scss';

const ALL_CATEGORIES_SLUG = '_all';

export default function CategorySelector(): JSX.Element {
	const [ visibleItems, setVisibleItems ] = useState< Category[] >( [] );
	const [ dropdownItems, setDropdownItems ] = useState< Category[] >( [] );
	const [ selected, setSelected ] = useState< Category >();
	const [ isLoading, setIsLoading ] = useState( false );

	const query = useQuery();

	useEffect( () => {
		// If no category is selected, show All as selected
		let categoryToSearch = ALL_CATEGORIES_SLUG;

		if ( query.category ) {
			categoryToSearch = query.category;
		}

		const allCategories = visibleItems.concat( dropdownItems );

		const selectedCategory = allCategories.find(
			( category ) => category.slug === categoryToSearch
		);

		if ( selectedCategory ) {
			setSelected( selectedCategory );
		}
	}, [ query, visibleItems, dropdownItems ] );

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
					if ( a.slug === ALL_CATEGORIES_SLUG ) {
						return -1;
					}

					return 1;
				} );

				// Split array into two from 7th item
				const visibleCategoryItems = categories.slice( 0, 7 );
				const dropdownCategoryItems = categories.slice( 7 );

				setVisibleItems( visibleCategoryItems );
				setDropdownItems( dropdownCategoryItems );
			} )
			.catch( () => {
				setVisibleItems( [] );
				setDropdownItems( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	function mobileCategoryDropdownLabel() {
		const allCategoriesText = __( 'All Categories', 'woocommerce' );

		if ( ! selected ) {
			return allCategoriesText;
		}

		if ( selected.label === 'All' ) {
			return allCategoriesText;
		}

		return selected.label;
	}

	function isSelectedInDropdown() {
		if ( ! selected ) {
			return false;
		}

		return dropdownItems.find(
			( category ) => category.slug === selected.slug
		);
	}

	if ( isLoading ) {
		return (
			<div className="woocommerce-marketplace__category-selector-loading">
				<p>{ __( 'Loading categories…', 'woocommerce' ) }</p>
				<Spinner />
			</div>
		);
	}

	return (
		<>
			<ul className="woocommerce-marketplace__category-selector">
				{ visibleItems.map( ( category ) => (
					<li
						className="woocommerce-marketplace__category-item"
						key={ category.slug }
					>
						<CategoryLink
							{ ...category }
							selected={ category.slug === selected?.slug }
						/>
					</li>
				) ) }
				<li className="woocommerce-marketplace__category-item">
					{ dropdownItems.length > 0 && (
						<CategoryDropdown
							label={ __( 'More', 'woocommerce' ) }
							categories={ dropdownItems }
							buttonClassName={ classNames(
								'woocommerce-marketplace__category-item-button',
								{
									'woocommerce-marketplace__category-item-button--selected':
										isSelectedInDropdown(),
								}
							) }
							contentClassName="woocommerce-marketplace__category-item-content"
							arrowIconSize={ 20 }
							selected={ selected }
						/>
					) }
				</li>
			</ul>

			<div className="woocommerce-marketplace__category-selector--full-width">
				<CategoryDropdown
					label={ mobileCategoryDropdownLabel() }
					categories={ visibleItems.concat( dropdownItems ) }
					buttonClassName="woocommerce-marketplace__category-dropdown-button"
					className="woocommerce-marketplace__category-dropdown"
					contentClassName="woocommerce-marketplace__category-dropdown-content"
					selected={ selected }
				/>
			</div>
		</>
	);
}
