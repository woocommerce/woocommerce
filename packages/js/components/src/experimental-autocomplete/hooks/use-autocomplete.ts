/**
 * External dependencies
 */
import React, { useEffect, useRef, useState } from 'react';

/**
 * Internal dependencies
 */
import { AutocompleteProps } from '../types';
import { useAllowCreate } from './use-allow-create';

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
}: AutocompleteProps ) {
	const autocompleteRef = useRef< HTMLDivElement >();
	const inputRef = useRef< HTMLInputElement >();
	const highlightedRef = useRef< number >( -1 );
	const menuContainerRef = useRef< HTMLDivElement >();
	const creatingButtonRef = useRef< HTMLButtonElement >();

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

		if ( event.code === 'ArrowDown' ) {
			event.preventDefault();

			const listItems =
				menuContainerRef.current?.querySelectorAll< HTMLLabelElement >(
					'li > div > label'
				);

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
			event.preventDefault();

			if ( highlightedRef.current >= 0 ) highlightedRef.current--;

			if ( highlightedRef.current === -1 ) {
				setTimeout( ( el ) => el.focus(), 0, inputRef.current );
			}

			const listItems =
				menuContainerRef.current?.querySelectorAll< HTMLLabelElement >(
					'li > div > label'
				);

			if ( listItems?.length && highlightedRef.current >= 0 ) {
				listItems[ highlightedRef.current ].focus();
			}
		}
	}

	function handleInputChange( event: React.ChangeEvent< HTMLInputElement > ) {
		const { value } = event.currentTarget;
		setInputValue( value );
		if ( typeof onInputChange === 'function' ) {
			onInputChange( value );
		}
		if ( ! isMenuOpen ) {
			setIsMenuOpen( true );
		}
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
		menuProps: {
			items,
			selected,
			inputValue,
			multiple,
			onSelect,
			onRemove,
			style: {
				width: menuContainerRef.current?.getBoundingClientRect().width,
			},
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
