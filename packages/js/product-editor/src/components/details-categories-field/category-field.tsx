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
import { useCategorySearch, ProductCategoryNode } from './use-category-search';
import { CreateCategoryModal } from './create-category-modal';

type CategoryFieldProps = {
	label: string;
	placeholder: string;
	value?: ProductCategoryNode[];
	onChange: ( value: ProductCategoryNode[] ) => void;
};

/**
 * Recursive function that adds the current item to the selected list and all it's parents
 * if not included already.
 */
function getSelectedWithParents(
	selected: ProductCategoryNode[] = [],
	item: ProductCategory,
	treeKeyValues: Record< number, CategoryTreeItem >
): ProductCategoryNode[] {
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

export function mapFromCategoryType(
	categories: ProductCategoryNode[]
): TreeItemType[] {
	return categories.map( ( val ) =>
		val.parent
			? {
					value: String( val.id ),
					label: val.name,
					parent: String( val.parent ),
			  }
			: {
					value: String( val.id ),
					label: val.name,
			  }
	);
}

export function mapToCategoryType(
	categories: TreeItemType[]
): ProductCategoryNode[] {
	return categories.map( ( cat ) => ( {
		id: +cat.value,
		name: cat.label,
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
				createValue={ searchValue }
				label={ label }
				isLoading={ isSearching }
				onInputChange={ searchDelayed }
				placeholder={ value.length === 0 ? placeholder : '' }
				onCreateNew={ () => {
					setShowCreateNewModal( true );
				} }
				shouldShowCreateButton={ ( typedValue ) =>
					! typedValue ||
					categoriesSelectList.findIndex(
						( item ) => item.name === typedValue
					) === -1
				}
				items={ getFilteredItemsForSelectTree(
					mapFromCategoryType( categoriesSelectList ),
					searchValue,
					mapFromCategoryType( value )
				) }
				selected={ mapFromCategoryType( value ) }
				onSelect={ ( selectedItems ) => {
					if ( Array.isArray( selectedItems ) ) {
						const newItems: ProductCategoryNode[] =
							mapToCategoryType(
								selectedItems.filter(
									( { value: selectedItemValue } ) =>
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
										( { value: removedValue } ) =>
											item.id === +removedValue
									)
						  )
						: value.filter(
								( item ) => item.id !== +removedItems.value
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
