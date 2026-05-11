/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { ProductFiltersStore } from '../../frontend';
import type { FilterOptionItem } from '../../types';

type DropdownContext = {
	storeNamespace: string;
	filterItemType: string;
};

type SelectableRow = FilterOptionItem & {
	selected?: boolean;
	value?: string;
	type?: string;
};

function isToggleableItem(
	item: SelectableRow | undefined
): item is FilterOptionItem & {
	type: string;
	value: string;
} {
	return (
		!! item &&
		typeof item.type === 'string' &&
		item.type.length > 0 &&
		typeof item.value === 'string' &&
		item.value.length > 0
	);
}

store(
	'woocommerce/product-filter-dropdown',
	{
		state: {
			get selectValue(): string {
				const { storeNamespace } = getContext< DropdownContext >();
				const parent = store< ProductFiltersStore >( storeNamespace );
				const items = Array.isArray( parent.state.selectableItems )
					? parent.state.selectableItems
					: [];
				const selected = items.find( ( row ) => row.selected );
				return selected?.value ?? '';
			},
		},
		actions: {
			onDropdownChange( event: Event ) {
				const target = event.currentTarget;
				if ( ! ( target instanceof HTMLSelectElement ) ) {
					return;
				}
				const { storeNamespace, filterItemType } =
					getContext< DropdownContext >();
				const parent = store< ProductFiltersStore >( storeNamespace );
				const value = target.value;

				// Clear previously selected option.
				if ( filterItemType ) {
					parent.actions.removeActiveFiltersBy(
						( filter ) => filter.type === filterItemType
					);
				}

				const row = parent.state.selectableItems.find(
					( item ) => item.value === value
				);

				// Don't try to toggle empty option ("") or invalid options.
				if ( ! isToggleableItem( row ) ) {
					parent.actions.navigate();
					return;
				}

				parent.actions.toggle( row );
			},
		},
	},
	{ lock: true }
);

export type { DropdownContext };
