/**
 * External dependencies
 */
import { useMemo, useState, createElement, Fragment } from '@wordpress/element';
import {
	TreeItemType,
	__experimentalSelectTreeControl as SelectTree,
} from '@woocommerce/components';
import { ProductCategory } from '@woocommerce/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { CategoryTreeItem } from './category-field-item';
import {
	useCategorySearch,
	ProductCategoryLinkedList,
} from './use-category-search';
import { CreateCategoryModal } from './create-category-modal';

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

function mapFromCategoryType(
	categories: ProductCategoryLinkedList[]
): TreeItemType[] {
	return categories.map( ( val ) =>
		val.parent
			? {
					id: String( val.id ),
					name: val.name,
					parent: String( val.parent ),
			  }
			: {
					id: String( val.id ),
					name: val.name,
			  }
	);
}

function mapToCategoryType(
	categories: TreeItemType[]
): ProductCategoryLinkedList[] {
	return categories.map( ( cat ) => ( {
		id: +cat.id,
		name: cat.name,
		parent: cat.parent ? +cat.parent : 0,
	} ) );
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
		getFilteredItemsForSelectTree,
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

	return (
		<>
			<SelectTree
				id="category-field"
				multiple
				shouldNotRecursivelySelect
				allowCreate
				createValue={ searchValue }
				label={ label }
				isLoading={ isSearching }
				onInputChange={ searchDelayed }
				getFilteredItems={ ( allItems, inputValue, selectedItems ) => {
					return getFilteredItemsForSelectTree(
						allItems,
						inputValue,
						selectedItems
					);
				} }
				placeholder={ value.length === 0 ? placeholder : '' }
				onCreateNew={ () => {
					setShowCreateNewModal( true );
				} }
				items={ mapFromCategoryType( categoriesSelectList ) }
				selected={ mapFromCategoryType( value ) }
				onSelect={ ( selectedItems ) => {
					if ( Array.isArray( selectedItems ) ) {
						const newItems: ProductCategoryLinkedList[] =
							mapToCategoryType(
								selectedItems.filter(
									( { id: selectedItemValue } ) =>
										! value.some(
											( item ) =>
												item.id === +selectedItemValue
										)
								)
							);
						onChange( [ ...value, ...newItems ] );
					}
				} }
				onRemove={ ( removedItems ) => {
					const newValues = Array.isArray( removedItems )
						? value.filter(
								( item ) =>
									! removedItems.some(
										( { id: removedValue } ) =>
											item.id === +removedValue
									)
						  )
						: value.filter(
								( item ) => item.id !== +removedItems.id
						  );
					onChange( newValues );
				} }
			></SelectTree>
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
