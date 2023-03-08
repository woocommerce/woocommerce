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
	__experimentalTreeControl as TreeControl,
} from '@woocommerce/components';
import { Product, ProductCategory } from '@woocommerce/data';
import { debounce } from 'lodash';
import { Popover } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CategoryFieldItem, CategoryTreeItem } from './category-field-item';
import {
	useCategorySearch,
	ProductCategoryLinkedList,
} from './use-category-search';
import { CreateCategoryModal } from './create-category-modal';
import { CategoryFieldAddNewItem } from './category-field-add-new-item';

type CategoryFieldProps = {
	label: string;
	placeholder: string;
	value?: ProductCategoryLinkedList[];
	onChange: ( value: ProductCategoryLinkedList[] ) => void;
};

/**
 * Recursive function that adds the current item to the selected list and all it's parents
 * if not included already.
 */
function getSelectedWithParents(
	selected: ProductCategoryLinkedList[] = [],
	item: ProductCategory,
	treeKeyValues: Record< number, CategoryTreeItem >
): ProductCategoryLinkedList[] {
	selected.push( { id: item.id, name: item.name, parent: item.parent } );

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
		allItems: ProductCategoryLinkedList[],
		inputValue: string,
		selectedItems: ProductCategoryLinkedList[]
	): ProductCategoryLinkedList[] => {
		const filteredItems = getFilteredItems(
			allItems,
			inputValue,
			selectedItems
		);
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
					parent: 0,
				},
			];
		}
		return filteredItems;
	};

	const selectedIds = value.map( ( item ) => item.id );

	return (
		<>
			<SelectControl< ProductCategoryLinkedList >
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
							? items.map( ( i ) =>
									i.parent
										? {
												value: String( i.id ),
												label: i.name,
												parent: String( i.parent ),
										  }
										: {
												value: String( i.id ),
												label: i.name,
										  }
							  )
							: [];
					return (
						<Popover tabIndex={ 0 }>
							{ isOpen && (
								<TreeControl
									multiple
									items={ rootItems }
								></TreeControl>
							) }
						</Popover>
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
