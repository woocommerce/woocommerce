/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Internal dependencies
 */
import { AutocompleteItem, AutocompleteProps } from '../types';
import { useAllowCreate } from './use-allow-create';

function containsMatchingChildren(
	item: AutocompleteItem,
	text: string
): boolean {
	if ( ! text || ! item.children?.length ) return false;
	return item.children.some( ( child ) => {
		if ( new RegExp( text, 'ig' ).test( child.label ) ) {
			return true;
		}
		return containsMatchingChildren( child, text );
	} );
}

function getFirstMatchingItem(
	item: AutocompleteItem,
	text: string,
	memo: Record< string, string >
) {
	if ( ! text ) return false;
	if ( memo[ text ] === item.value ) return true;

	const matcher = new RegExp( text, 'ig' );
	if ( matcher.test( item.label ) ) {
		if ( ! memo[ text ] ) {
			memo[ text ] = item.value;
			return true;
		}
		return false;
	}

	item.children?.some( ( child ) => {
		return getFirstMatchingItem( child, text, memo );
	} );

	return false;
}

export function useAutocomplete( {
	allowCreate,
	ref,
	id,
	items,
	multiple,
	selected,
	onSelect,
	onRemove,
	onInputChange,
	onCreateClick,
	...props
}: AutocompleteProps ) {
	const autocompleteRef = useRef< HTMLDivElement >();
	const inputRef = useRef< HTMLInputElement >();
	const highlightedRef = useRef< number >( -1 );
	const menuContainerRef = useRef< HTMLDivElement >();
	const creatingButtonRef = useRef< HTMLButtonElement >();
	const highlightedItemRef = useRef< Record< string, string > >( {} );

	const [ inputValue, setInputValue ] = useState( '' );
	const [ isMenuOpen, setIsMenuOpen ] = useState( false );

	const { allowCreateProps, onInputKeyDown } = useAllowCreate( {
		allowCreate,
		inputValue,
		items,
		onCreateClick( value ) {
			if ( typeof onCreateClick === 'function' ) {
				onCreateClick( value );
			}
			if ( multiple && value ) {
				setInputValue( '' );
			}
		},
	} );

	const isItemExpanded = useCallback(
		( item: AutocompleteItem ) =>
			containsMatchingChildren( item, inputValue ),
		[ inputValue ]
	);

	const isItemHighlighted = useCallback(
		( item: AutocompleteItem ) =>
			getFirstMatchingItem(
				item,
				inputValue,
				highlightedItemRef.current
			),
		[ inputValue, highlightedItemRef ]
	);

	useEffect( () => {
		highlightedItemRef.current = {};
	}, [ items ] );

	useEffect( () => {
		function onDocumentClick() {
			setIsMenuOpen( false );
		}

		window.addEventListener( 'click', onDocumentClick );

		return () => {
			window.removeEventListener( 'click', onDocumentClick );
		};
	}, [] );

	useEffect( () => {
		if ( ! multiple && ! Array.isArray( selected ) && selected?.label ) {
			setInputValue( selected.label );
		}
	}, [ selected ] );

	function handleKeyDown( event: React.KeyboardEvent< HTMLDivElement > ) {
		if ( event.code === 'Escape' ) {
			setTimeout(
				( el ) => {
					el.focus();
					setIsMenuOpen( false );
				},
				0,
				inputRef.current
			);
		}

		if ( event.code === 'ArrowDown' || event.code === 'ArrowUp' ) {
			event.preventDefault();
			const listItems =
				menuContainerRef.current?.querySelectorAll< HTMLLabelElement >(
					'li > div > label'
				);

			if ( event.code === 'ArrowDown' ) {
				const totalItems = listItems?.length ?? 1;

				if (
					listItems?.length &&
					highlightedRef.current < listItems.length - 1
				) {
					highlightedRef.current++;

					listItems[ highlightedRef.current ].focus();
				} else if ( allowCreate ) {
					if ( highlightedRef.current < totalItems )
						highlightedRef.current++;

					creatingButtonRef.current?.focus();
				}
			}

			if ( event.code === 'ArrowUp' ) {
				if ( highlightedRef.current >= 0 ) highlightedRef.current--;

				if ( highlightedRef.current === -1 ) {
					setTimeout( ( el ) => el.focus(), 0, inputRef.current );
				}

				if ( listItems?.length && highlightedRef.current >= 0 ) {
					listItems[ highlightedRef.current ].focus();
				}
			}
		}
	}

	function handleInputChange( event: React.ChangeEvent< HTMLInputElement > ) {
		const { value } = event.currentTarget;
		if ( typeof onInputChange === 'function' ) {
			onInputChange( value );
		}
		if ( ! isMenuOpen ) {
			setIsMenuOpen( true );
		}
		setInputValue( value );
	}

	function handleInputFocus() {
		if ( ! isMenuOpen ) {
			setIsMenuOpen( true );
		}
		highlightedRef.current = -1;
		autocompleteRef.current?.classList.add( 'is-focused' );
	}

	function handleInputBlur() {
		autocompleteRef.current?.classList.remove( 'is-focused' );
	}

	function handleInputKeyDown(
		event: React.KeyboardEvent< HTMLInputElement >
	) {
		if ( typeof onInputKeyDown === 'function' ) {
			onInputKeyDown( event );
		}
	}

	return {
		autocompleteProps: {
			ref( element: HTMLDivElement ) {
				autocompleteRef.current = element;
			},
			className: 'experimental-woocommerce-autocomplete',
			onClick( event: React.MouseEvent< HTMLDivElement > ) {
				event.stopPropagation();
			},
			onKeyDown: handleKeyDown,
		},
		isMenuOpen,
		inputProps: {
			...props,
			ref( element: HTMLInputElement ) {
				inputRef.current = element;
				if ( typeof ref === 'function' ) {
					ref( element );
				}
			},
			id,
			value: inputValue,
			onChange: handleInputChange,
			onFocus: handleInputFocus,
			onBlur: handleInputBlur,
			onKeyDown: handleInputKeyDown,
		},
		comboBoxProps: {},
		menuContainerProps: {
			ref( element: HTMLDivElement ) {
				menuContainerRef.current = element;
			},
		},
		menuContainerWidth:
			menuContainerRef.current?.getBoundingClientRect().width,
		menuProps: {
			items,
			selected,
			multiple,
			isItemExpanded,
			isItemHighlighted,
			onSelect,
			onRemove,
		},
		allowCreateProps: {
			...allowCreateProps,
			ref( element: HTMLButtonElement ) {
				allowCreateProps?.ref( element );
				creatingButtonRef.current = element;
			},
		},
	};
}
