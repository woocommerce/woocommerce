/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { SelectableItem } from '../../../../types/type-defs/selectable-items';

type DropdownContext = {
	storeNamespace: string;
	filterItemType: string;
};

type SelectableParent = {
	state: {
		selectableItems: readonly SelectableItem[];
	};
	actions: {
		toggle: ( item?: SelectableItem ) => void;
		navigate?: () => void;
		removeActiveFiltersBy?: (
			callback: ( item: { type: string; value: string } ) => boolean
		) => void;
	};
};

function isToggleableItem(
	item: SelectableItem | undefined
): item is SelectableItem & { value: string } {
	return (
		!! item &&
		typeof item.value === 'string' &&
		item.value.length > 0 &&
		! item.disabled &&
		! item.hidden
	);
}

store(
	'woocommerce/product-filter-dropdown',
	{
		state: {
			get selectValue(): string {
				const { storeNamespace } = getContext< DropdownContext >();
				const parent = store< SelectableParent >( storeNamespace );
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
				const { storeNamespace } = getContext< DropdownContext >();
				const parent = store< SelectableParent >( storeNamespace );
				const value = target.value;

				if ( value === '' ) {
					// Add to Cart + Options: choosing the placeholder clears this attribute
					// (the store has no `navigate`, so we must toggle off the current selection).
					if (
						storeNamespace ===
						'woocommerce/add-to-cart-with-options'
					) {
						const items = Array.isArray(
							parent.state.selectableItems
						)
							? parent.state.selectableItems
							: [];
						const selected = items.find( ( row ) => row.selected );
						if (
							selected &&
							typeof parent.actions.toggle === 'function'
						) {
							parent.actions.toggle( selected );
						}
						return;
					}
					// Product Filters: clear active filters for this filter type.
					if ( typeof parent.actions.navigate === 'function' ) {
						parent.actions.navigate();
					}
					return;
				}

				const items = Array.isArray( parent.state.selectableItems )
					? parent.state.selectableItems
					: [];
				const row = items.find( ( item ) => item.value === value );

				if ( ! isToggleableItem( row ) ) {
					if ( typeof parent.actions.navigate === 'function' ) {
						parent.actions.navigate();
					}
					return;
				}

				parent.actions.toggle( row );
			},
		},
	},
	{ lock: true }
);

export type { DropdownContext };
