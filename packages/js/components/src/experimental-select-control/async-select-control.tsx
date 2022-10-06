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
} from '@wordpress/element';
import { debounce } from 'lodash';
import { ReactElement, Component } from 'react';

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
import { ChildrenProps } from './types';

type AsyncSelectControlProps< ItemType > = Omit<
	SelectControlProps< ItemType >,
	'onInputChange' | 'items' | 'children'
> & {
	onSearch: ( value: string | undefined ) => Promise< ItemType[] >;
	items?: ItemType[];
	triggerInitialFetch?: boolean;
	pageSize?: number;
	total?: number;
	children: (
		props: ChildrenProps< ItemType > & {
			isFetching: boolean;
		}
	) => ReactElement | Component;
};

export function AsyncSelectControl< ItemType >( {
	onSearch,
	getItemLabel = defaultGetItemLabel,
	getItemValue = defaultGetItemValue,
	items = [],
	triggerInitialFetch = true,
	pageSize,
	total,
	children = ( {
		items: renderItems,
		highlightedIndex,
		getItemProps,
		getMenuProps,
		isOpen,
		isFetching,
	} ) => {
		return (
			<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
				{ isFetching ? (
					<Spinner />
				) : (
					renderItems.map( ( item, index: number ) => (
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
	},
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
		[ setFetchedItems, setIsFetching, onSearch ]
	);

	useEffect( () => {
		if (
			triggerInitialFetch &&
			! remainingSelectControlProps.disabled &&
			items.length === 0
		) {
			fetchItems( '' );
		}
	}, [ remainingSelectControlProps.disabled ] );

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
			selectedItems: ItemType[]
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
			{ ( { ...childProps } ) => {
				return children( {
					...childProps,
					isFetching,
				} );
			} }
		</SelectControl>
	);
}
