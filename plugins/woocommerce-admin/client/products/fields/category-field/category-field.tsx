/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import {
	selectControlStateChangeTypes,
	Spinner,
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenuSlot as MenuSlot,
	__experimentalSelectControlMenu as Menu,
	useAsyncFilter,
	SelectControlProps,
} from '@woocommerce/components';
import { ProductCategory } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './category-field.scss';
import { CategoryFieldItem, CategoryTreeItem } from './category-field-item';
import { useCategorySearch } from './use-category-search';
import { CreateCategoryModal } from './create-category-modal';
import { CategoryFieldAddNewItem } from './category-field-add-new-item';

type CategoryItem = Pick< ProductCategory, 'id' | 'name' >;

type CategoryFieldProps = {
	label: string;
	placeholder: string;
	value?: CategoryItem[];
	onChange: ( value: CategoryItem[] ) => void;
};

/**
 * Recursive function that adds the current item to the selected list and all it's parents
 * if not included already.
 */
function getSelectedWithParents(
	selected: CategoryItem[] = [],
	item: ProductCategory,
	treeKeyValues: Record< number, CategoryTreeItem >
): CategoryItem[] {
	selected.push( { id: item.id, name: item.name } );

	const parentId =
		item.parent !== undefined
			? item.parent
			: treeKeyValues[ item.id ].parentID;
	if (
		parentId > 0 &&
		treeKeyValues[ parentId ] &&
		! selected.find(
			( selectedCategory ) => selectedCategory.id === parentId
		)
	) {
		getSelectedWithParents(
			selected,
			treeKeyValues[ parentId ].data,
			treeKeyValues
		);
	}

	return selected;
}

/**
 * Add a new item at the end of the list if the
 * current set of items do not match exactly the
 * input's value
 *
 * @param filteredItems The item list
 * @param inputValue The input's value
 * @param isSearching If searching categories
 * @return The `allItems` + 1 new item if condition is met
 */
function addCreateNewCategoryItem(
	filteredItems: CategoryItem[],
	inputValue: string,
	isSearching: boolean
) {
	if (
		inputValue.length > 0 &&
		! isSearching &&
		! filteredItems.find(
			( item ) => item.name.toLowerCase() === inputValue.toLowerCase()
		)
	) {
		return [
			...filteredItems,
			{
				id: -99,
				name: inputValue,
			},
		];
	}
	return filteredItems;
}

export const CategoryField: React.FC< CategoryFieldProps > = ( {
	label,
	placeholder,
	value = [],
	onChange,
} ) => {
	const [ items, setItems ] = useState< CategoryItem[] >( [] );
	const {
		isSearching,
		categoriesSelectList,
		categoryTreeKeyValues,
		searchCategories,
	} = useCategorySearch( value as ProductCategory[] );
	const [ showCreateNewModal, setShowCreateNewModal ] = useState( false );
	const [ searchValue, setSearchValue ] = useState( '' );

	useEffect( () => {
		if ( items.length === 0 ) setItems( categoriesSelectList );
	}, [ categoriesSelectList, items ] );

	const onSelect = ( itemId: number, selected: boolean ) => {
		if ( itemId === -99 ) {
			setShowCreateNewModal( true );
			return;
		}
		if ( selected ) {
			const item = categoryTreeKeyValues[ itemId ].data;
			if ( item ) {
				onChange(
					getSelectedWithParents(
						[ ...value ],
						item,
						categoryTreeKeyValues
					)
				);
			}
		} else {
			onChange( value.filter( ( i ) => i.id !== itemId ) );
		}
	};

	const selectedIds = value.map( ( item ) => item.id );

	const selectProps: SelectControlProps< CategoryItem > = {
		className:
			'woocommerce-category-field-dropdown components-base-control',
		multiple: true,
		items,
		label,
		selected: value,
		getItemLabel: ( item: CategoryItem | null ) => item?.name || '',
		getItemValue: ( item: CategoryItem | null ) => item?.id || '',
		onSelect: ( item: CategoryItem ) => {
			if ( item ) {
				onSelect( item.id, ! selectedIds.includes( item.id ) );
			}
		},
		onRemove: ( item: CategoryItem ) => item && onSelect( item.id, false ),
		placeholder: value.length === 0 ? placeholder : '',
		stateReducer: ( state, actionAndChanges ) => {
			const { changes, type } = actionAndChanges;
			switch ( type ) {
				case selectControlStateChangeTypes.ControlledPropUpdatedSelectedItem:
					return {
						...changes,
						inputValue: state.inputValue,
					};
				case selectControlStateChangeTypes.ItemClick:
					if (
						changes.selectedItem &&
						changes.selectedItem.id === -99
					) {
						return changes;
					}
					return {
						...changes,
						isOpen: true,
						inputValue: state.inputValue,
						highlightedIndex: state.highlightedIndex,
					};
				default:
					return changes;
			}
		},
		__experimentalOpenMenuOnFocus: true,
	};

	const selectPropsWithAsycFilter = useAsyncFilter< CategoryItem >( {
		...selectProps,
		async filter( inputValue?: string ) {
			return searchCategories( inputValue ).then( ( categories ) => {
				return Promise.resolve(
					addCreateNewCategoryItem(
						categories,
						inputValue ?? '',
						isSearching
					)
				);
			} );
		},
		onInputChange( inputValue?: string ) {
			setSearchValue( inputValue ?? '' );
		},
		onFilterEnd( filteredItems ) {
			setItems( filteredItems );
		},
	} );

	return (
		<>
			<SelectControl< CategoryItem > { ...selectPropsWithAsycFilter }>
				{ ( {
					items: internalItems,
					isOpen,
					getMenuProps,
					getItemProps,
					highlightedIndex,
				} ) => {
					const rootItems =
						internalItems.length > 0
							? internalItems.filter(
									( item ) =>
										categoryTreeKeyValues[ item.id ]
											?.parentID === 0 || item.id === -99
							  )
							: [];
					return (
						<Menu
							isOpen={ isOpen }
							getMenuProps={ getMenuProps }
							className="woocommerce-category-field-dropdown__menu"
						>
							<>
								{ isSearching ? (
									<li className="woocommerce-category-field-dropdown__item">
										<div className="woocommerce-category-field-dropdown__item-content">
											<Spinner />
										</div>
									</li>
								) : (
									rootItems.map( ( item ) => {
										return item.id === -99 ? (
											<CategoryFieldAddNewItem
												key={ `${ item.id }` }
												item={ item }
												highlightedIndex={
													highlightedIndex
												}
												items={ internalItems }
												getItemProps={ getItemProps }
											/>
										) : (
											<CategoryFieldItem
												key={ `${ item.id }` }
												item={
													categoryTreeKeyValues[
														item.id
													]
												}
												highlightedIndex={
													highlightedIndex
												}
												selectedIds={ selectedIds }
												items={ internalItems }
												getItemProps={ getItemProps }
											/>
										);
									} )
								) }
							</>
						</Menu>
					);
				} }
			</SelectControl>
			<MenuSlot />
			{ showCreateNewModal && (
				<CreateCategoryModal
					initialCategoryName={ searchValue }
					onCancel={ () => setShowCreateNewModal( false ) }
					onCreate={ ( newCategory ) => {
						onChange(
							getSelectedWithParents(
								[ ...value ],
								newCategory,
								categoryTreeKeyValues
							)
						);
						setShowCreateNewModal( false );
						setSearchValue( '' );
					} }
				/>
			) }
		</>
	);
};
