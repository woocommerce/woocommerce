/**
 * External dependencies
 */
import React, { useEffect, useId, useRef, useState } from 'react';
import { check, chevronDown, chevronUp, Icon } from '@wordpress/icons';
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useSelect, UseSelectState } from 'downshift';

/**
 * Internal dependencies
 */
import './style.scss';

export interface ListItem {
	key: string;
	name: string;
	group?: string;
	context?: string;
	className?: string;
	items?: string[];
}

export interface GroupedSelectControlProps< ItemType > {
	label: string;
	options: ItemType[];
	value?: ItemType | null;
	placeholder?: string;
	searchable?: boolean;
	name?: string;
	className?: string;
	onChange?: ( changes: Partial< UseSelectState< ItemType > > ) => void;
}

const GroupedSelectControl = < ItemType extends ListItem >( {
	name,
	className,
	label,
	options: listItems,
	onChange: onSelectedItemChange,
	value,
	placeholder,
	searchable,
}: GroupedSelectControlProps< ItemType > ): JSX.Element => {
	const searchRef = useRef< HTMLInputElement >( null );
	const toggleButtonRef = useRef< HTMLButtonElement >( null );
	const restoreFocusRef = useRef( false );
	const instanceId = useId();
	const previousStateRef = useRef< {
		visibleItems: Set< string >;
	} >();
	const groupKeys = listItems
		.filter( ( item ) => item.items?.length )
		.map( ( group ) => group.key );

	const [ openedGroups, setOpenedGroups ] = useState(
		new Set( [ groupKeys[ 0 ] ] )
	);

	const [ visibleItems, setVisibleItems ] = useState(
		new Set( [ ...groupKeys, ...( listItems[ 0 ]?.items || [] ) ] )
	);

	const [ searchText, setSearchText ] = useState( '' );

	const itemsToRender = listItems.filter( ( item ) =>
		visibleItems.has( item.key )
	);

	// Groups only toggle their items, which search has already expanded, so
	// search results show them as headings that can't be highlighted or picked.
	const isSearching = searchText !== '';
	const selectableItems = isSearching
		? itemsToRender.filter( ( item ) => ! item.items )
		: itemsToRender;
	const getGroupHeadingId = ( groupKey: string ) =>
		`${ instanceId }-group-${ groupKey }`;
	const labelId = `${ instanceId }-label`;
	const menuId = `${ instanceId }-menu`;
	const getItemId = ( index: number ) => `${ instanceId }-item-${ index }`;

	const {
		isOpen,
		selectedItem,
		getToggleButtonProps,
		getMenuProps,
		getLabelProps,
		highlightedIndex,
		getItemProps,
		setHighlightedIndex,
		selectItem,
		closeMenu,
	} = useSelect( {
		items: selectableItems,
		labelId,
		menuId,
		getItemId,
		itemToString: ( item ) => item?.name || '',
		selectedItem: value || ( {} as ItemType ),
		onSelectedItemChange,
		onIsOpenChange: ( { isOpen: willOpen, type } ) => {
			restoreFocusRef.current =
				! willOpen && type === useSelect.stateChangeTypes.ItemClick;
		},
		stateReducer: ( state, { changes, type } ) => {
			if (
				searchable &&
				type === useSelect.stateChangeTypes.ToggleButtonKeyDownArrowDown
			) {
				return state;
			}

			if ( changes.selectedItem && changes.selectedItem.items ) {
				const key = changes.selectedItem.key;
				if ( openedGroups.has( key ) ) {
					openedGroups.delete( key );
					changes.selectedItem.items.forEach( ( itemKey ) =>
						visibleItems.delete( itemKey )
					);
				} else {
					openedGroups.add( key );
					changes.selectedItem.items.forEach( ( itemKey ) =>
						visibleItems.add( itemKey )
					);
				}
				setOpenedGroups( openedGroups );
				setVisibleItems( visibleItems );
				return state;
			}

			// Picking an option from the search field closes the menu in the
			// same update, so Downshift still reports the selection.
			if (
				type === useSelect.stateChangeTypes.FunctionSelectItem &&
				changes.selectedItem
			) {
				return { ...changes, isOpen: false };
			}

			return changes;
		},
	} );

	const handleSearch = ( {
		target,
	}: React.ChangeEvent< HTMLInputElement > ) => {
		if ( ! previousStateRef.current ) {
			previousStateRef.current = {
				visibleItems,
			};
		}

		if ( target.value === '' ) {
			setVisibleItems( previousStateRef.current.visibleItems );
			previousStateRef.current = undefined;
		} else {
			const filteredItems = listItems.filter(
				( item ) =>
					item?.group &&
					`${ item.name } ${ item.context || '' }`
						.toLowerCase()
						.includes( target.value.toLowerCase() )
			);
			const filteredGroups = filteredItems.map(
				( item ): string => item?.group || ''
			);
			const filteredVisibleItems = new Set( [
				...filteredItems.map( ( i ) => i.key ),
				...filteredGroups,
			] );
			setVisibleItems( filteredVisibleItems );
		}

		setSearchText( target.value );
	};

	// Downshift keeps focus on the toggle button, so a searchable menu moves it
	// to the search field and drives the list from there.
	useEffect( () => {
		if ( isOpen && searchable ) {
			searchRef.current?.focus();
		} else if ( ! isOpen && restoreFocusRef.current ) {
			// A clicked option unmounts the focused search field with the menu.
			restoreFocusRef.current = false;
			toggleButtonRef.current?.focus();
		}
	}, [ isOpen, searchable ] );

	const handleSearchKeyDown = (
		event: React.KeyboardEvent< HTMLInputElement >
	) => {
		const lastIndex = selectableItems.length - 1;

		switch ( event.key ) {
			case 'ArrowDown':
				event.preventDefault();
				if ( lastIndex >= 0 ) {
					setHighlightedIndex(
						Math.min( highlightedIndex + 1, lastIndex )
					);
				}
				break;
			case 'ArrowUp':
				event.preventDefault();
				if ( lastIndex >= 0 ) {
					setHighlightedIndex( Math.max( highlightedIndex - 1, 0 ) );
				}
				break;
			case 'Enter': {
				event.preventDefault();
				const item = selectableItems[ highlightedIndex ];
				if ( ! item ) {
					break;
				}
				selectItem( item );
				if ( ! item.items ) {
					toggleButtonRef.current?.focus();
				}
				break;
			}
			case 'Escape':
				event.preventDefault();
				closeMenu();
				toggleButtonRef.current?.focus();
				break;
			case 'Tab':
				closeMenu();
				break;
		}
	};

	const menuProps = getMenuProps( {
		className: 'components-grouped-select-control__list',
		'aria-hidden': ! isOpen,
		onFocus: () => searchRef.current?.focus(),
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		onBlur: ( event: any ) => {
			if ( event.relatedTarget === searchRef.current ) {
				event.nativeEvent.preventDownshiftDefault = true;
			}
		},
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		onKeyDown: ( event: any ) => {
			if ( event.code === 'Space' ) {
				event.nativeEvent.preventDownshiftDefault = true;
			}
		},
	} );

	return (
		<div
			className={ clsx(
				'woopayments components-grouped-select-control',
				className
			) }
		>
			{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
			<label
				{ ...getLabelProps( {
					className: 'components-grouped-select-control__label',
				} ) }
			>
				{ label }
			</label>
			<button
				{ ...getToggleButtonProps( {
					ref: toggleButtonRef,
					type: 'button',
					onBlur: ( event: React.FocusEvent ) => {
						if ( event.relatedTarget === searchRef.current ) {
							(
								event.nativeEvent as FocusEvent & {
									preventDownshiftDefault?: boolean;
								}
							 ).preventDownshiftDefault = true;
						}
					},
					className: clsx(
						'components-text-control__input components-grouped-select-control__button',
						{ placeholder: ! selectedItem?.name }
					),
					name,
				} ) }
			>
				<span className="components-grouped-select-control__button-value">
					{ selectedItem?.name || placeholder }
				</span>
				<Icon
					icon={ chevronDown }
					className="components-grouped-select-control__button-icon"
				/>
			</button>
			<div { ...menuProps }>
				{ isOpen && (
					<>
						{ searchable && (
							<input
								className="components-grouped-select-control__search"
								ref={ searchRef }
								type="text"
								role="combobox"
								aria-label={ __( 'Search', 'woocommerce' ) }
								aria-controls={ menuId }
								aria-expanded={ isOpen }
								aria-autocomplete="list"
								aria-activedescendant={
									selectableItems[ highlightedIndex ]
										? getItemId( highlightedIndex )
										: undefined
								}
								value={ searchText }
								onChange={ handleSearch }
								onKeyDown={ handleSearchKeyDown }
								tabIndex={ -1 }
								placeholder={ __( 'Search…', 'woocommerce' ) }
							/>
						) }
						{ /* The menu element is the listbox; this list only scrolls its options. */ }
						<ul
							role="presentation"
							className="components-grouped-select-control__list-container"
						>
							{ itemsToRender.map( ( item ) => {
								const isGroup = !! item.items;

								if ( isSearching && isGroup ) {
									return (
										<li
											key={ item.key }
											id={ getGroupHeadingId( item.key ) }
											role="presentation"
											className="components-grouped-select-control__item is-group"
										>
											<div className="components-grouped-select-control__item-content">
												{ item.name }
											</div>
											<Icon
												icon={ chevronUp }
												size={ 18 }
											/>
										</li>
									);
								}

								const index = selectableItems.indexOf( item );

								return (
									<li
										key={ item.key }
										{ ...getItemProps( {
											item,
											index,
											'aria-describedby':
												isSearching && item.group
													? getGroupHeadingId(
															item.group
														)
													: undefined,
											className: clsx(
												'components-grouped-select-control__item',
												item.className,
												{
													'is-highlighted':
														index ===
														highlightedIndex,
												},
												{
													'is-group': isGroup,
													'is-item': ! isGroup,
												}
											),
										} ) }
									>
										<div className="components-grouped-select-control__item-content">
											{ item.name }
										</div>
										{ item.key === selectedItem?.key && (
											<Icon icon={ check } />
										) }
										{ isGroup && (
											<Icon
												icon={
													openedGroups.has( item.key )
														? chevronUp
														: chevronDown
												}
												size={ 18 }
											/>
										) }
									</li>
								);
							} ) }
						</ul>
					</>
				) }
			</div>
		</div>
	);
};

export default GroupedSelectControl;
