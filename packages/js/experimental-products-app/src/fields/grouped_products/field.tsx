/**
 * External dependencies
 */
import { Combobox as BaseCombobox } from '@base-ui/react/combobox';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { resolveSelect } from '@wordpress/data';
import type { DataFormControlProps, Field } from '@wordpress/dataviews';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import type { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import {
	Combobox,
	SearchableChipSelectControl,
	type SearchableChipSelectItem,
} from '../components/searchable-chip-select';

const SEARCH_DEBOUNCE_MS = 300;
const SEARCH_PER_PAGE = 20;

type Item = SearchableChipSelectItem & {
	image?: { src: string; alt: string };
};

function productToItem(
	product: Pick< Product, 'id' | 'name' > & {
		images?: { src: string; alt: string }[];
	}
): Item {
	const thumbnail = product.images?.[ 0 ];
	return {
		value: product.id.toString(),
		label:
			decodeEntities( product.name ?? '' ) ||
			__( '(Untitled product)', 'woocommerce' ),
		image: thumbnail?.src
			? { src: thumbnail.src, alt: thumbnail.alt ?? '' }
			: undefined,
	};
}

function GroupedProductsEdit( {
	data,
	field,
	onChange,
}: DataFormControlProps< ProductEntityRecord > ) {
	const selectedIds = useMemo( () => {
		const ids = data?.grouped_products;
		return Array.isArray( ids ) ? ids : [];
	}, [ data?.grouped_products ] );

	const [ inputValue, setInputValue ] = useState( '' );
	const [ selectedProducts, setSelectedProducts ] = useState< Item[] >( [] );
	const [ suggestions, setSuggestions ] = useState< Item[] >( [] );
	const [ isLoadingSelected, setIsLoadingSelected ] = useState( false );
	const [ isSearching, setIsSearching ] = useState( false );
	const searchRequestIdRef = useRef( 0 );

	// Load full records for the currently selected ids so we can show their
	// names and thumbnails in the chips.
	useEffect( () => {
		if ( selectedIds.length === 0 ) {
			setSelectedProducts( [] );
			return;
		}

		let cancelled = false;
		setIsLoadingSelected( true );

		void resolveSelect( coreStore )
			.getEntityRecords( 'root', 'product', {
				include: selectedIds,
				per_page: selectedIds.length,
			} )
			.then( ( records: unknown ) => {
				if ( cancelled || ! Array.isArray( records ) ) {
					return;
				}
				const byId = new Map(
					( records as Product[] ).map( ( p ) => [ p.id, p ] )
				);
				setSelectedProducts(
					selectedIds
						.map( ( id ) => byId.get( id ) )
						.filter( ( p ): p is Product => p !== undefined )
						.map( productToItem )
				);
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoadingSelected( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ selectedIds ] );

	// Browseable list of products in the dropdown. Empty input loads the
	// first page of the catalog; typing debounces a server-side search.
	useEffect( () => {
		const query = inputValue.trim();

		setIsSearching( true );
		const requestId = ++searchRequestIdRef.current;

		// Exclude only the current product itself; already-selected products
		// stay in the dropdown so the chip-select can mark them as selected
		// (checkmark + highlight), matching the categories treatment.
		const excludeIds = data?.id ? [ data.id ] : [];

		const queryParams: Record< string, unknown > = {
			per_page: SEARCH_PER_PAGE,
			orderby: 'title',
			order: 'asc',
		};
		if ( excludeIds.length > 0 ) {
			queryParams.exclude = excludeIds;
		}
		if ( query ) {
			queryParams.search = query;
		}

		const delay = query ? SEARCH_DEBOUNCE_MS : 0;
		const timer = window.setTimeout( () => {
			void resolveSelect( coreStore )
				.getEntityRecords( 'root', 'product', queryParams )
				.then( ( records: unknown ) => {
					if ( requestId !== searchRequestIdRef.current ) {
						return;
					}
					setSuggestions(
						Array.isArray( records )
							? ( records as Product[] ).map( productToItem )
							: []
					);
				} )
				.finally( () => {
					if ( requestId === searchRequestIdRef.current ) {
						setIsSearching( false );
					}
				} );
		}, delay );

		return () => {
			window.clearTimeout( timer );
		};
	}, [ inputValue, selectedIds, data?.id ] );

	// Browseable catalog first, then
	// selected products appended so the chips can resolve their labels.
	const items = useMemo( () => {
		const byValue = new Map< string, Item >();
		for ( const item of suggestions ) {
			byValue.set( item.value, item );
		}
		for ( const item of selectedProducts ) {
			if ( ! byValue.has( item.value ) ) {
				byValue.set( item.value, item );
			}
		}
		return Array.from( byValue.values() );
	}, [ selectedProducts, suggestions ] );

	const value = useMemo( () => {
		const byValue = new Map(
			items.map( ( item ) => [ item.value, item ] )
		);
		return selectedIds
			.map( ( id ) => byValue.get( id.toString() ) )
			.filter( ( item ): item is Item => item !== undefined );
	}, [ items, selectedIds ] );

	const handleValueChange = ( newItems: Item[] ) => {
		const newIds = newItems
			.map( ( item ) => parseInt( item.value, 10 ) )
			.filter( ( id ) => ! Number.isNaN( id ) );
		onChange( { grouped_products: newIds } );
	};

	const emptyContent = isSearching ? (
		<Spinner />
	) : (
		__( 'No products found.', 'woocommerce' )
	);

	return (
		<SearchableChipSelectControl
			label={ field.label }
			description={ field.description }
			items={ items }
			value={ value }
			onValueChange={ handleValueChange }
			inputValue={ inputValue }
			onInputValueChange={ setInputValue }
			searchPlaceholder={ __( 'Search', 'woocommerce' ) }
			disabled={ isLoadingSelected }
			emptyContent={ emptyContent }
			placeholderChip={
				value.length === 0 ? field.placeholder : undefined
			}
			// Always server-side search.
			filter={ null }
			chipsContent={ ( selectedItems: Item[] ) =>
				selectedItems.map( ( item ) => (
					<Combobox.ChipWithRemove
						key={ item.value }
						prefix={
							item.image?.src ? (
								<img
									src={ item.image.src }
									alt={ item.image.alt ?? '' }
									className="woocommerce-grouped-products-edit__chip-thumbnail"
								/>
							) : (
								<span className="woocommerce-grouped-products-edit__chip-thumbnail woocommerce-grouped-products-edit__chip-thumbnail--empty" />
							)
						}
					>
						{ item.label }
					</Combobox.ChipWithRemove>
				) )
			}
		>
			{ ( item: Item ) => (
				<Combobox.Item
					key={ item.value }
					value={ item }
					disabled={ item.disabled }
				>
					<div className="woocommerce-grouped-products-edit__option">
						<span
							className="woocommerce-grouped-products-edit__option-indicator"
							aria-hidden="true"
						>
							<BaseCombobox.ItemIndicator>
								<svg
									width="16"
									height="16"
									viewBox="0 0 24 24"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<path
										d="M5 13l4 4L19 7"
										stroke="currentColor"
										strokeWidth="2"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
								</svg>
							</BaseCombobox.ItemIndicator>
						</span>
						{ item.image?.src ? (
							<img
								src={ item.image.src }
								alt={ item.image.alt ?? '' }
								className="woocommerce-grouped-products-edit__option-thumbnail"
							/>
						) : (
							<span className="woocommerce-grouped-products-edit__option-thumbnail woocommerce-grouped-products-edit__option-thumbnail--empty" />
						) }
						<span className="woocommerce-grouped-products-edit__option-label">
							{ item.label }
						</span>
					</div>
				</Combobox.Item>
			) }
		</SearchableChipSelectControl>
	);
}

const fieldDefinition = {
	label: __( 'Grouped products', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	Edit: GroupedProductsEdit,
};
