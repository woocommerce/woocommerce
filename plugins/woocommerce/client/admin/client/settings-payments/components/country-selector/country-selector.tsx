/**
 * External dependencies
 */
import { useRef, useState } from 'react';
import clsx from 'clsx';
import {
	useSelect,
	UseSelectState,
	UseSelectStateChangeOptions,
} from 'downshift';
import { Button } from '@wordpress/components';
import { useThrottle } from '@wordpress/compose';
import { useCallback, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { check, chevronDown, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { Item, ControlProps, UseSelectStateChangeOptionsProps } from './types';
import './country-selector.scss';

// Retrieves the display label for a given value from a list of options.
const getOptionLabel = ( value: string, options: Item[] ) => {
	const item = options.find( ( option ) => option.key === value );
	return item?.name ? item.name : '';
};

// State reducer to control selection navigation
const stateReducer = < ItemType extends Item >(
	state: UseSelectState< ItemType | null >,
	actionAndChanges: UseSelectStateChangeOptions< ItemType | null >
): Partial< UseSelectState< ItemType > > => {
	const extendedAction =
		actionAndChanges as UseSelectStateChangeOptionsProps< ItemType | null >; // Cast to the extended type

	const { changes, type, props } = extendedAction;
	const { items } = props;
	const { selectedItem } = state;

	switch ( type ) {
		case useSelect.stateChangeTypes.ToggleButtonBlur:
			// Prevent menu from closing when focus moves to search input.
			// Also preserve the current selection to avoid resetting it.
			return {
				...changes,
				isOpen: state.isOpen,
				selectedItem: state.selectedItem,
			};
		case useSelect.stateChangeTypes.ItemClick:
			return {
				...changes,
				isOpen: true, // Keep menu open after selection.
				highlightedIndex: state.highlightedIndex,
			};
		case useSelect.stateChangeTypes.ToggleButtonKeyDownArrowDown:
			// If we already have a selected item, try to select the next one,
			// without circular navigation. Otherwise, select the first item.
			return {
				selectedItem:
					items[
						selectedItem
							? Math.min(
									items.indexOf( selectedItem ) + 1,
									items.length - 1
							  )
							: 0
					],
				isOpen: true, // Keep menu open after selection.
			};
		case useSelect.stateChangeTypes.ToggleButtonKeyDownArrowUp:
			// If we already have a selected item, try to select the previous one,
			// without circular navigation. Otherwise, select the last item.
			return {
				selectedItem:
					items[
						selectedItem
							? Math.max( items.indexOf( selectedItem ) - 1, 0 )
							: items.length - 1
					],
				isOpen: true, // Keep menu open after selection.
			};
		default:
			return changes;
	}
};

/**
 * Removes accents and diacritical marks from a given string.
 *
 * This function uses Unicode normalization to decompose accented characters into their base characters
 * and diacritical marks, then removes the diacritical marks. It is commonly used for case-insensitive
 * and accent-insensitive text searches or comparisons.
 *
 * @example
 * // Returns 'Cafe'
 * removeAccents('Café');
 *
 * // Returns 'aeeioou'
 * removeAccents('áèêíòóú');
 */
const removeAccents = ( str: string ) => {
	return str.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
};

/**
 * A flexible dropdown component for selecting a country from a list. Supports search,
 * custom rendering of items, and a variety of state management options.
 */
export const CountrySelector = < ItemType extends Item >( {
	name,
	className,
	label,
	describedBy,
	options: items,
	onChange,
	value,
	placeholder,
	children,
}: ControlProps< ItemType > ): JSX.Element => {
	const [ searchText, setSearchText ] = useState( '' );
	const [ keyboardHighlightIndex, setKeyboardHighlightIndex ] = useState<
		number | null
	>( null );

	// only run filter every 200ms even if the user is typing
	const throttledApplySearchToItems = useThrottle(
		useCallback(
			( searchString: string, itemSet: ItemType[] ) =>
				new Set(
					itemSet.filter( ( item: Item ) =>
						`${ removeAccents( item.name ?? '' ) }`
							.toLowerCase()
							.includes(
								removeAccents( searchString.toLowerCase() )
							)
					)
				),
			[]
		),
		200
	);

	const visibleItems =
		searchText !== ''
			? throttledApplySearchToItems( searchText, items ) ?? new Set()
			: new Set( items );

	const {
		getToggleButtonProps,
		getMenuProps,
		getItemProps,
		isOpen,
		highlightedIndex,
		selectedItem,
		closeMenu,
		selectItem,
	} = useSelect< ItemType >( {
		initialSelectedItem: value,
		items: [ ...visibleItems ],
		stateReducer,
	} );

	const applyButtonRef = useRef< HTMLButtonElement >( null );

	const itemString = getOptionLabel( value.key, items );
	const selectedValue = selectedItem ? selectedItem.key : '';

	const menuRef = useRef< HTMLInputElement >( null );
	const searchRef = useRef< HTMLInputElement >( null );

	function getDescribedBy() {
		if ( describedBy ) {
			return describedBy;
		}

		if ( ! itemString ) {
			return __( 'No selection', 'woocommerce' );
		}

		return sprintf(
			// translators: %s: The selected option.
			__( 'Currently selected: %s', 'woocommerce' ),
			itemString
		);
	}

	const highlightSelectedCountry = useCallback(
		( itemIndex: number ) => {
			const menuElement = menuRef.current;

			const highlightedItem = menuElement?.querySelector(
				`[data-index="${ itemIndex }"]`
			);

			if ( highlightedItem ) {
				highlightedItem.scrollIntoView( {
					block: 'nearest',
				} );
			}
		},
		[ menuRef ]
	);

	const getSearchSuffix = ( focused: boolean ) => {
		if ( focused ) {
			return (
				<img
					src={ WC_ASSET_URL + 'images/icons/clear.svg' }
					alt={ __( 'Clear search', 'woocommerce' ) }
				/>
			);
		}

		return (
			<img
				src={ WC_ASSET_URL + 'images/icons/search.svg' }
				alt={ __( 'Search', 'woocommerce' ) }
			/>
		);
	};

	// Check if the search input is clearable.
	const isSearchClearable = searchText !== '';

	const menuProps = getMenuProps( {
		className: 'components-country-select-control__menu',
		'aria-hidden': ! isOpen,
		ref: menuRef, // Ref to the menu element.
	} );

	const onApplyHandler = useCallback(
		( e: React.MouseEvent< HTMLButtonElement > ) => {
			e.stopPropagation();
			onChange( selectedValue );
			closeMenu();
		},
		[ onChange, selectedValue, closeMenu ]
	);

	const onClearClickedHandler = useCallback(
		( e: React.MouseEvent< HTMLButtonElement > ) => {
			e.preventDefault();

			if ( searchText !== '' ) {
				setSearchText( '' );
			}

			if ( selectedItem !== null ) {
				// Timeout the highlight to ensure the list is updated.
				setTimeout( () => {
					highlightSelectedCountry( items.indexOf( selectedItem ) );
				}, 10 );
			}
		},
		[ searchText, selectedItem ]
	);

	const onSearchKeyDown = useCallback(
		( event: React.KeyboardEvent< HTMLInputElement > ) => {
			const itemsArray = [ ...visibleItems ];
			const itemCount = itemsArray.length;

			switch ( event.key ) {
				case 'ArrowDown':
					event.preventDefault();
					setKeyboardHighlightIndex( ( prev ) => {
						const newIndex =
							prev === null || prev === -1
								? 0
								: Math.min( prev + 1, itemCount - 1 );
						// Scroll the item into view.
						setTimeout( () => {
							highlightSelectedCountry( newIndex );
						}, 0 );
						return newIndex;
					} );
					break;
				case 'ArrowUp':
					event.preventDefault();
					setKeyboardHighlightIndex( ( prev ) => {
						const newIndex =
							prev === null || prev === -1
								? itemCount - 1
								: Math.max( prev - 1, 0 );
						// Scroll the item into view.
						setTimeout( () => {
							highlightSelectedCountry( newIndex );
						}, 0 );
						return newIndex;
					} );
					break;
				case 'Enter': {
					event.preventDefault();
					// Use highlighted item if available, otherwise use current selection.
					const itemToApply =
						keyboardHighlightIndex !== null &&
						keyboardHighlightIndex >= 0 &&
						keyboardHighlightIndex < itemCount
							? itemsArray[ keyboardHighlightIndex ]
							: selectedItem;
					if ( itemToApply ) {
						onChange( itemToApply.key );
					}
					closeMenu();
					break;
				}
				case 'Escape':
					event.preventDefault();
					closeMenu();
					break;
				case 'Tab':
					// Allow default Tab behavior to move to Apply button.
					break;
				default:
					break;
			}
		},
		[
			visibleItems,
			keyboardHighlightIndex,
			selectedItem,
			onChange,
			closeMenu,
		]
	);

	useEffect( () => {
		if ( isOpen ) {
			// Sync the selected item with the value prop when the menu opens.
			// This ensures the correct country is selected after applying changes.
			if ( selectedItem?.key !== value.key ) {
				selectItem( value );
			}

			// Focus the search input when the menu is opened.
			// Use a small timeout to ensure the input is rendered.
			setTimeout( () => {
				searchRef.current?.focus();
			}, 0 );

			// Highlight the selected country when the menu is opened.
			// Use value instead of selectedItem since we just synced it.
			const valueIndex = Array.from( visibleItems ).findIndex(
				( item ) => item.key === value.key
			);
			if ( valueIndex >= 0 ) {
				highlightSelectedCountry( valueIndex );
				setKeyboardHighlightIndex( valueIndex );
			} else {
				// If the value is not in the visible items, highlight the first item.
				setKeyboardHighlightIndex( 0 );
			}
		} else {
			// Reset highlight when menu closes.
			setKeyboardHighlightIndex( null );
		}
	}, [ isOpen ] );

	return (
		<div
			className={ clsx(
				'woopayments components-country-select-control',
				className
			) }
		>
			<Button
				{ ...getToggleButtonProps( {
					'aria-label': label,
					'aria-labelledby': undefined,
					'aria-describedby': getDescribedBy(),
					className: clsx(
						'components-country-select-control__button',
						{ placeholder: ! itemString }
					),
					name,
				} ) }
			>
				<span className="components-country-select-control__button-value">
					<span className="components-country-select-control__label">
						{ label }
					</span>
					{ itemString || placeholder }
				</span>
				<Icon
					icon={ chevronDown }
					className="components-custom-select-control__button-icon"
				/>
			</Button>
			<div { ...menuProps }>
				{ isOpen && (
					<>
						<div className="components-country-select-control__search wc-settings-prevent-change-event">
							<input
								className="components-country-select-control__search--input"
								ref={ searchRef }
								type="text"
								value={ searchText }
								onChange={ ( { target } ) =>
									setSearchText( target.value )
								}
								onKeyDown={ onSearchKeyDown }
								placeholder={ __( 'Search', 'woocommerce' ) }
								aria-label={ __(
									'Search countries',
									'woocommerce'
								) }
							/>
							<button
								className="components-country-select-control__search--input-suffix"
								onClick={ onClearClickedHandler }
								tabIndex={ -1 }
								aria-label={
									isSearchClearable
										? __( 'Clear search', 'woocommerce' )
										: __( 'Search', 'woocommerce' )
								}
							>
								{ getSearchSuffix( isSearchClearable ) }
							</button>
						</div>
						<div className="components-country-select-control__list">
							{ [ ...visibleItems ].map( ( item, index ) => (
								<div
									{ ...getItemProps( {
										item,
										index,
										key: item.key,
										className: clsx(
											item.className,
											'components-country-select-control__item',
											{
												'is-highlighted':
													keyboardHighlightIndex !==
													null
														? index ===
														  keyboardHighlightIndex
														: index ===
														  highlightedIndex,
											}
										),
										'data-index': index,
										style: item.style,
									} ) }
									key={ item.key }
								>
									{ item.key === selectedValue && (
										<Icon
											icon={ check }
											className="components-country-select-control__item-icon"
										/>
									) }
									{ children ? children( item ) : item.name }
								</div>
							) ) }
						</div>
						<div className="components-country-select-control__apply">
							<button
								ref={ applyButtonRef }
								className="components-button is-primary"
								onClick={ onApplyHandler }
							>
								{ __( 'Apply', 'woocommerce' ) }
							</button>
						</div>
					</>
				) }
			</div>
		</div>
	);
};
