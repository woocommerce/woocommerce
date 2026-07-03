/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { SelectableItem } from '../../types/type-defs/selectable-items';

type DropdownContext = {
	storeNamespace: string;
};

type SelectableParent = {
	state: {
		selectableItems: readonly SelectableItem[];
	};
	actions: {
		toggle: ( item?: SelectableItem ) => void;
		navigate?: () => void;
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
	'woocommerce/dropdown',
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

				// Choosing the empty <option> unselects the selected option.
				if ( value === '' ) {
					const items = Array.isArray( parent.state.selectableItems )
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
