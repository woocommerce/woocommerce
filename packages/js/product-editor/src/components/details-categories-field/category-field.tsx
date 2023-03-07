/**
 * External dependencies
 */
import { useMemo, useState, createElement, Fragment } from '@wordpress/element';
import {
	selectControlStateChangeTypes,
	Spinner,
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenuSlot as MenuSlot,
	__experimentalSelectControlMenu as Menu,
} from '@woocommerce/components';
import { ProductCategory } from '@woocommerce/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { CategoryFieldItem, CategoryTreeItem } from './category-field-item';
import { useCategorySearch } from './use-category-search';
import { CreateCategoryModal } from './create-category-modal';
import { CategoryFieldAddNewItem } from './category-field-add-new-item';

type CategoryFieldProps = {
	label: string;
	placeholder: string;
	value?: Pick< ProductCategory, 'id' | 'name' >[];
	onChange: ( value: Pick< ProductCategory, 'id' | 'name' >[] ) => void;
};

/**
 * Recursive function that adds the current item to the selected list and all it's parents
 * if not included already.
 */
function getSelectedWithParents(
	selected: Pick< ProductCategory, 'id' | 'name' >[] = [],
	item: ProductCategory,
	treeKeyValues: Record< number, CategoryTreeItem >
): Pick< ProductCategory, 'id' | 'name' >[] {
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

export const CategoryField: React.FC< CategoryFieldProps > = ( {
	label,
	placeholder,
	value = [],
	onChange,
} ) => {
	const {
		isSearching,
		categoriesSelectList,
		categoryTreeKeyValues,
		searchCategories,
		getFilteredItems,
	} = useCategorySearch();
	const [ showCreateNewModal, setShowCreateNewModal ] = useState( false );
	const [ searchValue, setSearchValue ] = useState( '' );

	const onInputChange = ( searchString?: string ) => {
		setSearchValue( searchString || '' );
		searchCategories( searchString || '' );
	};

	const searchDelayed = useMemo(
		() => debounce( onInputChange, 150 ),
		[ onInputChange ]
	);

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

	const categoryFieldGetFilteredItems = (
		allItems: Pick< ProductCategory, 'id' | 'name' >[],
		inputValue: string,
		selectedItems: Pick< ProductCategory, 'id' | 'name' >[]
	) => {
		const filteredItems = getFilteredItems(
			allItems,
			inputValue,
			selectedItems
		);
		if (
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
	};

	const selectedIds = value.map( ( item ) => item.id );

	return (
		<>
			<SelectControl< Pick< ProductCategory, 'id' | 'name' > >
				className="woocommerce-category-field-dropdown components-base-control"
				multiple
				items={ categoriesSelectList }
				label={ label }
				selected={ value }
				getItemLabel={ ( item ) => item?.name || '' }
				getItemValue={ ( item ) => item?.id || '' }
				onSelect={ ( item ) => {
					if ( item ) {
						onSelect( item.id, ! selectedIds.includes( item.id ) );
					}
				} }
				onRemove={ ( item ) => item && onSelect( item.id, false ) }
				onInputChange={ searchDelayed }
				getFilteredItems={ categoryFieldGetFilteredItems }
				placeholder={ value.length === 0 ? placeholder : '' }
				stateReducer={ ( state, actionAndChanges ) => {
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
				} }
				__experimentalOpenMenuOnFocus
			>
				{ ( {
					items,
					isOpen,
					getMenuProps,
					getItemProps,
					highlightedIndex,
				} ) => {
					const rootItems =
						items.length > 0
							? items.filter(
									( item ) =>
										categoryTreeKeyValues[ item.id ]
											?.parentID === 0 || item.id === -99
							  )
							: [];
					const notCreatedItem = items.find(
						( item ) => item.id === -99
					);
					return (
						<Menu
							isOpen={ isOpen }
							getMenuProps={ getMenuProps }
							fixedPositionElement={
								notCreatedItem && (
									<CategoryFieldAddNewItem
										item={ notCreatedItem }
										highlightedIndex={ highlightedIndex }
										items={ items }
										getItemProps={ getItemProps }
									/>
								)
							}
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
										return item.id !== -99 ? (
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
												items={ items }
												getItemProps={ getItemProps }
											/>
										) : null;
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
						onInputChange( '' );
					} }
				/>
			) }
		</>
	);
};
