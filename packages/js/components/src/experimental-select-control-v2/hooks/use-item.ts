/**
 * External dependencies
 */
import { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { DefaultItem } from '../types';

type useItemProps< Item > = {
	deselectItem: ( item: Item ) => void;
	highlightedOption: Item | null;
	multiple: boolean;
	selected: Item[];
	selectItem: ( item: Item ) => void;
};

export function useItem< Item = DefaultItem >( {
	deselectItem,
	highlightedOption,
	multiple,
	selected,
	selectItem,
}: useItemProps< Item > ) {
	function isHighlighted( item: Item ) {
		return item === highlightedOption;
	}

	function isSelected( item: Item ) {
		return selected.includes( item );
	}

	function onClick( item: Item ) {
		if ( multiple && isSelected( item ) ) {
			deselectItem( item );
			return;
		}
		selectItem( item );
	}

	function onMouseDown( event: MouseEvent< HTMLElement > ) {
		event.preventDefault();
	}

	function getItemProps( item: Item ) {
		return {
			'aria-selected': isSelected( item ),
			isHighlighted: isHighlighted( item ),
			isSelected: isSelected( item ),
			onClick: () => onClick( item ),
			onMouseDown,
			role: 'option',
		};
	}

	return {
		getItemProps,
	};
}
