/*
 * These components are copy-pasted from a private repository while we wait for
 * @wordpress/ui to support an equivalent component. Remove this implementation
 * once @wordpress/ui provides one.
 */

/**
 * Internal dependencies
 */
import { ChipWithRemove } from './chip-with-remove';
import { Item } from './item';
import { SearchableChipSelect as _SearchableChipSelect } from './searchable-chip-select';
import { SearchableChipSelectControl as _SearchableChipSelectControl } from './searchable-chip-select-control';

Item.displayName = 'SearchableChipSelect.Item';
ChipWithRemove.displayName = 'SearchableChipSelect.ChipWithRemove';

export const SearchableChipSelect = Object.assign( _SearchableChipSelect, {
	Item,
	ChipWithRemove,
} );

export const Combobox = SearchableChipSelect;

export const SearchableChipSelectControl = Object.assign(
	_SearchableChipSelectControl,
	{
		Item,
		ChipWithRemove,
	}
);

export type {
	Item as SearchableChipSelectItem,
	SearchableChipSelectChipWithRemoveProps,
	SearchableChipSelectControlProps,
	SearchableChipSelectItemProps,
	SearchableChipSelectProps,
} from './types';
