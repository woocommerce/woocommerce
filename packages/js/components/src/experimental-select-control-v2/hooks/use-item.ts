/**
 * External dependencies
 */
import { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { DefaultItem, Selected } from '../types';
import { isSelected } from '../utils/is-selected';

type useItemProps< Item > = {
	deselectItem: ( item: Item ) => void;
	highlightedOption: Item | null;
	multiple: boolean;
	selected: Selected< Item >;
	selectItem: ( item: Item ) => void;
	onMouseOver?: ( e: MouseEvent, item: Item ) => void;
};

export function useItem< Item = DefaultItem >( {
	deselectItem,
	highlightedOption,
	multiple,
	selected,
	selectItem,
	onMouseOver = () => {},
}: useItemProps< Item > ) {
	function isHighlighted( item: Item ) {
		return item === highlightedOption;
	}

	function onClick( item: Item ) {
		if ( multiple && isSelected( item, selected ) ) {
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
			'aria-selected': isSelected( item, selected ),
			isHighlighted: isHighlighted( item ),
			isSelected: isSelected( item, selected ),
			onClick: () => onClick( item ),
			onMouseDown,
			onMouseOver: ( e: MouseEvent ) => onMouseOver( e, item ),
			role: 'option',
		};
	}

	return {
		getItemProps,
	};
}
