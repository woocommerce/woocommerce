/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import {
	selectControlStateChangeTypes,
	Spinner,
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
} from '@woocommerce/components';
import { ProductCategory } from '@woocommerce/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import './category-field.scss';
import { CategoryFieldItem, CategoryTreeItem } from './category-field-item';
import { useCategorySearch } from './use-category-search';

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

	const parentId = item.parent
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

	const onInputChange = ( searchString?: string ) => {
		searchCategories( searchString || '' );
	};

	const searchDelayed = useMemo(
		() => debounce( onInputChange, 150 ),
		[ onInputChange ]
	);

	const onSelect = ( itemId: number, selected: boolean ) => {
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
	const selectControlItems = categoriesSelectList;

	return (
		<SelectControl< Pick< ProductCategory, 'id' | 'name' > >
			className="woocommerce-category-field-dropdown components-base-control"
			multiple
			items={ selectControlItems }
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
			getFilteredItems={ getFilteredItems }
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
		>
			{ ( {
				items,
				isOpen,
				getMenuProps,
				getItemProps,
				selectItem,
				highlightedIndex,
			} ) => {
				const rootItems =
					items.length > 0
						? items.filter(
								( item ) =>
									categoryTreeKeyValues[ item.id ]
										?.parentID === 0
						  )
						: [];
				return (
					<>
						<Menu
							isOpen={ isOpen }
							getMenuProps={ getMenuProps }
							className="woocommerce-category-field-dropdown__menu"
						>
							<>
								{ isOpen && isSearching && items.length === 0 && (
									<li className="woocommerce-category-field-dropdown__item">
										<div className="woocommerce-category-field-dropdown__item-content">
											<Spinner />
										</div>
									</li>
								) }
								{ isOpen &&
									rootItems.map( ( item ) => (
										<CategoryFieldItem
											key={ `${ item.id }` }
											item={
												categoryTreeKeyValues[ item.id ]
											}
											highlightedIndex={
												highlightedIndex
											}
											selectedIds={ selectedIds }
											onSelect={ selectItem }
											items={ items }
											getItemProps={ getItemProps }
										/>
									) ) }
							</>
						</Menu>
					</>
				);
			} }
		</SelectControl>
	);
};
