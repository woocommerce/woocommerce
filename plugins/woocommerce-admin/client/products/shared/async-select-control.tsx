/**
 * External dependencies
 */
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenuItem as MenuItem,
	__experimentalSelectControlMenu as Menu,
	Spinner,
} from '@woocommerce/components';
import { debounce } from 'lodash';

type AsyncSelectControlProps< ItemType > = {
	label: string;
	items: ItemType[];
	placeholder: string;
	onSearch: ( value: string | undefined ) => Promise< ItemType[] >;
	selected: ItemType | ItemType[] | null;
	onRemove?: ( item: ItemType ) => void;
	onSelect?: ( selected: ItemType ) => void;
	multiple?: boolean;
	disabled?: boolean;
	getItemLabel: ( item: ItemType | null ) => string;
	getItemValue: ( item: ItemType | null ) => string | number;
	triggerInitialFetch?: boolean;
	pageSize?: number;
	total?: number;
};

export function AsyncSelectControl< ItemType >( {
	items,
	label,
	disabled,
	placeholder,
	onSearch,
	selected,
	onRemove,
	onSelect,
	multiple,
	getItemLabel,
	getItemValue,
	triggerInitialFetch = true,
	pageSize,
	total,
}: AsyncSelectControlProps< ItemType > ) {
	const [ fetchedItems, setFetchedItems ] = useState< ItemType[] >( items );
	const [ isFetching, setIsFetching ] = useState( false );

	const fetchItems = useCallback(
		( value: string | undefined ) => {
			setIsFetching( true );
			onSearch( value ).then(
				( results ) => {
					setFetchedItems( results );
					setIsFetching( false );
				},
				() => {
					setIsFetching( false );
				}
			);
		},
		[ setFetchedItems, setIsFetching, onSearch ]
	);

	useEffect( () => {
		if ( triggerInitialFetch && items.length === 0 ) {
			fetchItems( '' );
		}
	}, [] );

	const searchDebounced = useMemo(
		() => debounce( fetchItems, 150 ),
		[ fetchItems ]
	);

	const filterClientSide =
		pageSize && total && pageSize > 0 && total < pageSize;

	const onInputChange = useCallback(
		( value?: string ) => {
			if ( filterClientSide ) {
				return;
			}
			searchDebounced( value );
		},
		[ filterClientSide ]
	);

	const getFilteredItems = useCallback(
		( allItems: ItemType[] ) => {
			return allItems;
		},
		[ filterClientSide ]
	);

	return (
		<>
			<SelectControl< ItemType >
				label={ label }
				multiple={ multiple }
				getFilteredItems={ getFilteredItems }
				disabled={ disabled }
				items={ fetchedItems }
				onInputChange={ onInputChange }
				selected={ selected }
				onSelect={ onSelect }
				onRemove={ onRemove }
				placeholder={ placeholder }
				getItemLabel={ getItemLabel }
				getItemValue={ getItemValue }
			>
				{ ( {
					items: selectControlItems,
					isOpen,
					highlightedIndex,
					getItemProps,
					getMenuProps,
				} ) => {
					return (
						<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
							{ isFetching ? (
								<Spinner />
							) : (
								selectControlItems.map(
									( item, index: number ) => (
										<MenuItem
											key={ `${ getItemValue( item ) }` }
											index={ index }
											isActive={
												highlightedIndex === index
											}
											item={ item }
											getItemProps={ getItemProps }
										>
											{ getItemLabel( item ) }
										</MenuItem>
									)
								)
							) }
						</Menu>
					);
				} }
			</SelectControl>
		</>
	);
}
