/**
 * External dependencies
 */
import {
	useMemo,
	useState,
	createElement,
	Fragment,
	useRef,
} from '@wordpress/element';
import {
	selectControlStateChangeTypes,
	__experimentalSelectControl as SelectControl,
	__experimentalTreeControl as TreeControl,
	TreeItem,
	SelectTree,
} from '@woocommerce/components';
import { ProductCategory } from '@woocommerce/data';
import { debounce } from 'lodash';
import { Popover } from '@wordpress/components';

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
): TreeItem[] {
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

function mapToCategoryType(
	categories: TreeItem[]
): ProductCategoryLinkedList[] {
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
		return getFilteredItems( allItems, inputValue, selectedItems );
	};

	const selectedIds = value.map( ( item ) => item.id );

	const treeControlRef = useRef< any >();

	return (
		<>
			<SelectTree
				multiple
				shouldNotRecursivelySelect
				allowCreate
				createValue={ searchValue }
				label={ label }
				onInputChange={ searchDelayed }
				getFilteredItems={ categoryFieldGetFilteredItems }
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
					if ( Array.isArray( removedItems ) ) {
						const newValues = value.filter(
							( item ) =>
								! removedItems.some(
									( { value: removedValue } ) =>
										item.id === +removedValue
								)
						);
						onChange( newValues );
					}
				} }
			></SelectTree>
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
						case selectControlStateChangeTypes.InputKeyDownEscape:
							return {
								...changes,
								isOpen: false,
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
					highlightedIndex,
				} ) => {
					const { ref } = getMenuProps();
					const width = document
						.querySelector(
							'.woocommerce-experimental-select-control__combo-box-wrapper'
						)
						?.getBoundingClientRect().width; // TODO find a better way?
					return (
						<Popover
							ref={ ref }
							// @ts-expect-error this prop does exist, see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L180.
							__unstableSlotName="category-popover"
						>
							{ isOpen && (
								<TreeControl
									ref={ treeControlRef }
									multiple
									shouldNotRecursivelySelect
									allowCreate
									createValue={ searchValue }
									onCreateNew={ () => {
										setShowCreateNewModal( true );
									} }
									style={ {
										width,
									} }
									items={ mapFromCategoryType( items ) }
									selected={ mapFromCategoryType( value ) }
									onSelect={ ( selectedItems ) => {
										if ( Array.isArray( selectedItems ) ) {
											const newItems: ProductCategoryLinkedList[] =
												mapToCategoryType(
													selectedItems.filter(
														( {
															value: selectedItemValue,
														} ) =>
															! value.some(
																( item ) =>
																	item.id ===
																	+selectedItemValue
															)
													)
												);
											onChange( [
												...value,
												...newItems,
											] );
										}
									} }
									onRemove={ ( removedItems ) => {
										if ( Array.isArray( removedItems ) ) {
											const newValues = value.filter(
												( item ) =>
													! removedItems.some(
														( {
															value: removedValue,
														} ) =>
															item.id ===
															+removedValue
													)
											);
											onChange( newValues );
										}
									} }
								></TreeControl>
							) }
						</Popover>
					);
				} }
			</SelectControl>
			{ /* @ts-expect-error name does exist on PopoverSlot see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L555 */ }
			<Popover.Slot name="category-popover" />
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
