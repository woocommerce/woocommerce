/**
 * External dependencies
 */
import React, { useRef } from 'react';

/**
 * Internal dependencies
 */
import { TreeProps } from '../types';

export function useTree( {
	ref,
	items,
	multiple,
	selected,
	level = 1,
	onSelect,
	onRemove,
	onKeyDown,
	...props
}: TreeProps ): TreeProps {
	const treeRef = useRef< HTMLOListElement >();
	const highlightedRef = useRef< number >( 0 );

	function handleKeyDown( event: React.KeyboardEvent< HTMLOListElement > ) {
		if ( typeof onKeyDown === 'function' ) {
			onKeyDown( event );
		}

		if ( level !== 1 ) {
			return;
		}

		if ( event.code === 'ArrowDown' || event.code === 'ArrowUp' ) {
			event.preventDefault();

			const focusableElements =
				treeRef.current?.querySelectorAll< HTMLLabelElement >(
					'.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label'
				);

			if ( ! focusableElements?.length ) {
				return;
			}

			const currentFocusedElement = treeRef.current?.querySelector(
				'.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label:focus-within'
			);

			if ( currentFocusedElement ) {
				let index = 0;
				for ( const element of focusableElements ) {
					if ( element === currentFocusedElement ) {
						highlightedRef.current = index;
						break;
					}
					index++;
				}
			}

			if ( event.code === 'ArrowDown' ) {
				if ( highlightedRef.current < focusableElements.length - 1 ) {
					highlightedRef.current++;

					focusableElements[ highlightedRef.current ].focus();
				}
			}

			if ( event.code === 'ArrowUp' ) {
				if ( highlightedRef.current >= 0 ) highlightedRef.current--;

				if ( highlightedRef.current >= 0 ) {
					focusableElements[ highlightedRef.current ].focus();
				}
			}
		}
	}

	return {
		...props,
		items,
		selected,
		multiple,
		level,
		'aria-multiselectable': multiple,
		ref( element: HTMLOListElement ) {
			treeRef.current = element;
			if ( typeof ref === 'function' ) {
				ref( element );
			}
		},
		onSelect,
		onRemove,
		onKeyDown: handleKeyDown,
	};
}
