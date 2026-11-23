/**
 * External dependencies
 */
import {
	store,
	getContext,
	getElement,
	withSyncEvent,
} from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { SelectableItem } from '@woocommerce/types';
import { getSelectionSummary } from './utils';
import {
	getVisualAttributeTermStyleString,
	isVisualAttributeTermEmpty,
} from '../../base/utils/visual-attribute-terms';
import type { VisualAttributeTerm } from '../../base/utils/visual-attribute-terms';
import { getClosestColor } from '../product-filters/utils/get-closest-color';

type DropdownContext = {
	storeNamespace: string;
	placeholder: string;
	isOpen: boolean;
	fieldStyle: string;
	item: SelectableItem< { visual?: VisualAttributeTerm } >;
};

type SelectableParent = {
	state: {
		selectableItems: readonly SelectableItem[];
		selectedItems?: readonly SelectableItem[];
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
			get triggerStyle(): string {
				const { fieldStyle, isOpen } = getContext< DropdownContext >();
				return (
					fieldStyle +
					( isOpen
						? 'border-bottom-left-radius:0;border-bottom-right-radius:0;'
						: '' )
				);
			},
			get items() {
				const { storeNamespace } = getContext< DropdownContext >();
				return store< SelectableParent >( storeNamespace ).state
					.selectableItems;
			},
			get selectionSummary(): string {
				const { storeNamespace, placeholder } =
					getContext< DropdownContext >();
				const parent = store< SelectableParent >( storeNamespace );
				return getSelectionSummary(
					parent.state.selectedItems ?? parent.state.selectableItems,
					placeholder
				);
			},
			get colorSwatchStyle(): string {
				return getVisualAttributeTermStyleString(
					getContext< DropdownContext >().item.visual
				);
			},
			get isColorSwatchEmpty(): boolean {
				return isVisualAttributeTermEmpty(
					getContext< DropdownContext >().item.visual
				);
			},
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
			togglePanel() {
				const context = getContext< DropdownContext >();
				context.isOpen = ! context.isOpen;
			},
			toggleItem() {
				const { storeNamespace, item } =
					getContext< DropdownContext >();
				if ( isToggleableItem( item ) ) {
					store< SelectableParent >( storeNamespace ).actions.toggle(
						item
					);
				}
			},
			onKeydown: withSyncEvent( ( event: KeyboardEvent ) => {
				const context = getContext< DropdownContext >();
				const { ref } = getElement();
				if ( event.key === 'Escape' && context.isOpen ) {
					event.stopPropagation();
					context.isOpen = false;
					ref?.querySelector< HTMLButtonElement >(
						'.wc-block-dropdown__trigger'
					)?.focus();
				}
			} ),
			closeOnClickOutside( event: MouseEvent ) {
				const { ref } = getElement();
				if (
					event.target instanceof Node &&
					! ref?.contains( event.target )
				) {
					getContext< DropdownContext >().isOpen = false;
				}
			},
			closeOnFocusOutside( event: FocusEvent ) {
				const { ref } = getElement();
				if (
					! ( event.relatedTarget instanceof Node ) ||
					! ref?.contains( event.relatedTarget )
				) {
					getContext< DropdownContext >().isOpen = false;
				}
			},
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
		callbacks: {
			initColors() {
				const { ref } = getElement();
				const background = getClosestColor( ref, 'backgroundColor' );
				if ( ref && background ) {
					ref.style.setProperty(
						'--wc-dropdown-background',
						background
					);
				}
			},
		},
	},
	{ lock: true }
);

export type { DropdownContext };
