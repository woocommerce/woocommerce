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
	selectedItems: {
		label: string | null;
		value: string | null;
	}[];
	hoveredItem: {
		label: string | null;
		value: string | null;
	};
	isOpen: boolean;
};

type DropdownStore = {
	state: {
		selectedItems?: {
			label: string | null;
			value: string | null;
		}[];
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
				const { selectedItems } = state;

				return selectedItems?.length && selectedItems[ 0 ].label
					? selectedItems[ 0 ]?.label
					: 'Select an option';
			},

			get isSelected(): boolean {
				const { currentItem } = getContext< DropdownContext >();
				const { selectedItems } = state;

				return (
					selectedItems?.some( ( item ) => {
						return (
							item.value === currentItem.value &&
							item.label === currentItem.label
						);
					} ) || false
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
				const { selectedItems } = state;

				const {
					currentItem: { label, value },
				} = context;

				// check if item already selected
				const selectedItem = selectedItems?.find(
					( item ) => item.value === value && item.label === label
				);

				// if item already selected remove it from state:
				// else add it to state:
				if ( selectedItem ) {
					const items = selectedItems?.slice(
						selectedItems.indexOf( selectedItem ),
						1
					);
					state.selectedItems = items || [];
					context.selectedItems = items || [];
				} else {
					selectedItems?.push( {
						label,
						value,
					} );
					context.selectedItems.push( {
						label,
						value,
					} );
				}

				context.isOpen = false;

				event.stopPropagation();
			},
		},
	}
);
