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

	function handleKeyDown( event: React.KeyboardEvent< HTMLOListElement > ) {
		if ( typeof onKeyDown === 'function' ) {
			onKeyDown( event );
		}

		if ( level !== 1 ) {
			return;
		}

		if ( event.code === 'ArrowDown' || event.code === 'ArrowUp' ) {
			event.preventDefault();

			const selector =
				'.experimental-woocommerce-tree-item > .experimental-woocommerce-tree-item__heading > .experimental-woocommerce-tree-item__label';

			const focusableElements =
				treeRef.current?.querySelectorAll< HTMLLabelElement >(
					selector
				);

			if ( ! focusableElements?.length ) {
				return;
			}

			const currentFocusedElement = treeRef.current?.querySelector(
				`${ selector }:focus-within`
			);

			let currentFocusedElementIndex = 0;
			if ( currentFocusedElement ) {
				for ( const element of focusableElements ) {
					if ( element === currentFocusedElement ) {
						break;
					}
					currentFocusedElementIndex++;
				}
			}

			if ( event.code === 'ArrowDown' ) {
				if (
					currentFocusedElementIndex <
					focusableElements.length - 1
				) {
					focusableElements[ ++currentFocusedElementIndex ].focus();
				}
			}

			if ( event.code === 'ArrowUp' ) {
				if ( currentFocusedElementIndex > 0 ) {
					focusableElements[ --currentFocusedElementIndex ].focus();
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
