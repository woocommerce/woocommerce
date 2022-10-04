/**
 * External dependencies
 */
import { Spinner } from '@wordpress/components';
import {
	createElement,
	useState,
	useMemo,
	useCallback,
	useEffect,
	useRef,
} from '@wordpress/element';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { SelectControlProps, SelectControl } from './select-control';
import { Menu } from './menu';
import { MenuItem } from './menu-item';
import {
	defaultGetFilteredItems,
	defaultGetItemLabel,
	defaultGetItemValue,
} from './utils';
import { getItemLabelType } from './types';

type AsyncSelectControlProps< ItemType > = Omit<
	SelectControlProps< ItemType >,
	'onInputChange' | 'items'
> & {
	onSearch: ( value: string | undefined ) => Promise< ItemType[] >;
	items?: ItemType[];
	triggerInitialFetch?: boolean;
	pageSize?: number;
	total?: number;
};

export function AsyncSelectControl< ItemType >( {
	onSearch,
	getItemLabel = defaultGetItemLabel,
	getItemValue = defaultGetItemValue,
	items = [],
	triggerInitialFetch = true,
	pageSize,
	total,
	...remainingSelectControlProps
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
		[ setFetchedItems, setIsFetching ]
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
		(
			allItems: ItemType[],
			inputValue: string,
			selectedItems: ItemType[],
			getItemLabel: getItemLabelType< ItemType >
		) => {
			if ( filterClientSide ) {
				const getFilteredItemsFunc =
					remainingSelectControlProps.getFilteredItems ??
					defaultGetFilteredItems;
				return getFilteredItemsFunc(
					allItems,
					inputValue,
					selectedItems,
					getItemLabel
				);
			}
			return allItems;
		},
		[ filterClientSide ]
	);

	return (
		<SelectControl< ItemType >
			{ ...remainingSelectControlProps }
			getFilteredItems={ getFilteredItems }
			items={ fetchedItems }
			onInputChange={ onInputChange }
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
							selectControlItems.map( ( item, index: number ) => (
								<MenuItem
									key={ `${ getItemValue( item ) }` }
									index={ index }
									isActive={ highlightedIndex === index }
									item={ item }
									getItemProps={ getItemProps }
								>
									{ getItemLabel( item ) }
								</MenuItem>
							) )
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
}
