/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';
import { Icon } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CategoryLink from './category-link';
import { Category, CategoryAPIItem } from './types';
import { fetchCategories } from '../../utils/functions';
import { ProductType } from '../product-list/types';
import CategoryDropdown from './category-dropdown';
import './category-selector.scss';

const ALL_CATEGORIES_SLUGS = {
	[ ProductType.extension ]: '_all',
	[ ProductType.theme ]: 'themes',
	[ ProductType.businessService ]: 'business-services',
};

interface CategorySelectorProps {
	type: ProductType;
}

export default function CategorySelector(
	props: CategorySelectorProps
): JSX.Element {
	const [ selected, setSelected ] = useState< Category >();
	const [ isLoading, setIsLoading ] = useState( false );
	const [ categoriesToShow, setCategoriesToShow ] = useState< Category[] >(
		[]
	);
	const [ isOverflowing, setIsOverflowing ] = useState( false );
	const [ scrollPosition, setScrollPosition ] = useState<
		'start' | 'middle' | 'end'
	>( 'start' );

	const categorySelectorRef = useRef< HTMLUListElement >( null );
	const selectedCategoryRef = useRef< HTMLLIElement >( null );

	const query = useQuery();

	useEffect( () => {
		setIsLoading( true );

		fetchCategories( props.type )
			.then( ( categoriesFromAPI: CategoryAPIItem[] ) => {
				const categories: Category[] = categoriesFromAPI
					.map( ( categoryAPIItem: CategoryAPIItem ): Category => {
						return {
							...categoryAPIItem,
							selected: false,
						};
					} )
					.filter( ( category: Category ): boolean => {
						// The "featured" category is returned from the API for legacy reasons, but we don't need it:
						return category.slug !== '_featured';
					} );

				setCategoriesToShow( categories );
			} )
			.catch( () => {
				setCategoriesToShow( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ props.type, setCategoriesToShow ] );

	useEffect( () => {
		// If no category is selected, show All as selected
		let categoryToSearch = ALL_CATEGORIES_SLUGS[ props.type ];

		if ( query.category ) {
			categoryToSearch = query.category;
		}

		const selectedCategory = categoriesToShow.find(
			( category ) => category.slug === categoryToSearch
		);

		if ( selectedCategory ) {
			setSelected( selectedCategory );
		}
	}, [ query.category, props.type, categoriesToShow ] );

	useEffect( () => {
		if ( selectedCategoryRef.current ) {
			selectedCategoryRef.current.scrollIntoView( {
				block: 'nearest',
				inline: 'center',
			} );
		}
	}, [ selected ] );

	function checkOverflow() {
		if (
			categorySelectorRef.current &&
			categorySelectorRef.current.parentElement?.scrollWidth
		) {
			const isContentOverflowing =
				categorySelectorRef.current.scrollWidth >
				categorySelectorRef.current.parentElement.scrollWidth;

			setIsOverflowing( isContentOverflowing );
		}
	}

	function checkScrollPosition() {
		const ulElement = categorySelectorRef.current;

		if ( ! ulElement ) {
			return;
		}

		const { scrollLeft, scrollWidth, clientWidth } = ulElement;

		if ( scrollLeft < 10 ) {
			setScrollPosition( 'start' );

			return;
		}

		if ( scrollLeft + clientWidth < scrollWidth ) {
			setScrollPosition( 'middle' );

			return;
		}

		if ( scrollLeft + clientWidth === scrollWidth ) {
			setScrollPosition( 'end' );
		}
	}

	const debouncedCheckOverflow = useDebounce( checkOverflow, 300 );
	const debouncedScrollPosition = useDebounce( checkScrollPosition, 100 );

	function scrollCategories( scrollAmount: number ) {
		if ( categorySelectorRef.current ) {
			categorySelectorRef.current.scrollTo( {
				left: categorySelectorRef.current.scrollLeft + scrollAmount,
				behavior: 'smooth',
			} );
		}
	}

	function scrollToNextCategories() {
		scrollCategories( 200 );
	}

	function scrollToPrevCategories() {
		scrollCategories( -200 );
	}

	useEffect( () => {
		window.addEventListener( 'resize', debouncedCheckOverflow );

		const ulElement = categorySelectorRef.current;

		if ( ulElement ) {
			ulElement.addEventListener( 'scroll', debouncedScrollPosition );
		}

		return () => {
			window.removeEventListener( 'resize', debouncedCheckOverflow );

			if ( ulElement ) {
				ulElement.removeEventListener(
					'scroll',
					debouncedScrollPosition
				);
			}
		};
	}, [ debouncedCheckOverflow, debouncedScrollPosition ] );

	useEffect( () => {
		checkOverflow();
	}, [ categoriesToShow ] );

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

	if ( isLoading ) {
		return (
			<>
				<ul className="woocommerce-marketplace__category-selector">
					{ [ ...Array( 5 ) ].map( ( el, i ) => (
						<li
							key={ i }
							className="woocommerce-marketplace__category-item"
						>
							<CategoryLink slug="" label="" selected={ false } />
						</li>
					) ) }
				</ul>
			</>
		);
	}

	return (
		<>
			<ul
				className="woocommerce-marketplace__category-selector"
				aria-label="Categories"
				ref={ categorySelectorRef }
			>
				{ categoriesToShow.map( ( category ) => (
					<li
						className="woocommerce-marketplace__category-item"
						key={ category.slug }
						ref={
							category.slug === selected?.slug
								? selectedCategoryRef
								: null
						}
					>
						<CategoryLink
							{ ...category }
							selected={ category.slug === selected?.slug }
							aria-current={ category.slug === selected?.slug }
						/>
					</li>
				) ) }
			</ul>
			<div className="woocommerce-marketplace__category-selector--full-width">
				<CategoryDropdown
					type={ props.type }
					label={ mobileCategoryDropdownLabel() }
					categories={ categoriesToShow }
					buttonClassName="woocommerce-marketplace__category-dropdown-button"
					className="woocommerce-marketplace__category-dropdown"
					contentClassName="woocommerce-marketplace__category-dropdown-content"
					selected={ selected }
				/>
			</div>
			{ isOverflowing && (
				<>
					<button
						onClick={ scrollToPrevCategories }
						className="woocommerce-marketplace__category-navigation-button woocommerce-marketplace__category-navigation-button--prev"
						hidden={ scrollPosition === 'start' }
						aria-label="Scroll to previous categories"
						tabIndex={ -1 }
					>
						<Icon icon="arrow-left-alt2" />
					</button>
					<button
						onClick={ scrollToNextCategories }
						className="woocommerce-marketplace__category-navigation-button woocommerce-marketplace__category-navigation-button--next"
						hidden={ scrollPosition === 'end' }
						aria-label="Scroll to next categories"
						tabIndex={ -1 }
					>
						<Icon icon="arrow-right-alt2" />
					</button>
				</>
			) }
		</>
	);
}
