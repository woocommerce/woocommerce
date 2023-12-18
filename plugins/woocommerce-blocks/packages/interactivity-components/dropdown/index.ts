/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import './style.scss';

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

type DropdownStore = {
	state: {
		selectedItem?: {
			label: string | null;
			value: string | null;
		};
		placeholderText: string;
		isSelected: boolean;
	};

	actions: {
		toggleIsOpen: () => void;
		selectDropdownItem: ( event: MouseEvent ) => void;
	};
};

const { state } = store< DropdownStore >(
	'woocommerce/interactivity-dropdown',
	{
		state: {
			get placeholderText(): string {
				const { selectedItem } = state;

				return selectedItem?.label || 'Select an option';
			},

			get isSelected(): boolean {
				const { currentItem } = getContext< DropdownContext >();
				const { selectedItem } = state;

				return selectedItem?.value === currentItem.value;
			},
		},
		actions: {
			toggleIsOpen: () => {
				const context = getContext< DropdownContext >();

				context.isOpen = ! context.isOpen;
			},
			selectDropdownItem: ( event: MouseEvent ) => {
				const context = getContext< DropdownContext >();
				const { selectedItem } = state;

				const {
					currentItem: { label, value },
				} = context;

				if (
					selectedItem?.value === value &&
					selectedItem?.label === label
				) {
					state.selectedItem = {
						label: null,
						value: null,
					};
					context.selectedItem = {
						label: null,
						value: null,
					};
				} else {
					state.selectedItem = { label, value };
					context.selectedItem = { label, value };
				}

				context.isOpen = false;

				event.stopPropagation();
			},
		},
	}
);
