/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

export type DropdownContext = {
	currentItem: {
		label: string;
		value: string;
	};
	selectedItem: {
		label: string | null;
		value: string | null;
	};
	hoveredItem: {
		label: string | null;
		value: string | null;
	};
	isOpen: boolean;
};

store( 'woocommerce/interactivity-dropdown', {
	state: {
		get placeholderText() {
			const context = getContext< DropdownContext >();
			const { selectedItem } = context;

			return selectedItem.label || 'Select an option';
		},
		get isSelected() {
			const context = getContext< DropdownContext >();

			const {
				currentItem: { value },
			} = context;

			return (
				context.selectedItem.value === value ||
				context.hoveredItem.value === value
			);
		},
	},
	actions: {
		toggleIsOpen: () => {
			const context = getContext< DropdownContext >();

			context.isOpen = ! context.isOpen;
		},
		selectDropdownItem: ( event: MouseEvent ) => {
			const context = getContext< DropdownContext >();

			const {
				currentItem: { label, value },
			} = context;

			const { selectedItem } = context;

			if (
				selectedItem.value === value &&
				selectedItem.label === label
			) {
				context.selectedItem = {
					label: null,
					value: null,
				};
			} else {
				context.selectedItem = { label, value };
			}

			context.isOpen = false;

			event.stopPropagation();
		},
		addHoverClass: () => {
			const context = getContext< DropdownContext >();

			const {
				currentItem: { label, value },
			} = context;

			context.hoveredItem = { label, value };
		},
		removeHoverClass: () => {
			const context = getContext< DropdownContext >();

			context.hoveredItem = {
				label: null,
				value: null,
			};
		},
	},
} );
