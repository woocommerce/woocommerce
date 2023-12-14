/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import './style.scss';

export type DropdownContext = {
	selectType: 'multiple' | 'single';
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
		unselectDropdownItem: ( event: MouseEvent ) => void;
	};
};

const { state } = store< DropdownStore >(
	'woocommerce/interactivity-dropdown',
	{
		state: {
			get placeholderText(): string {
				const { selectType } = getContext< DropdownContext >();

				if ( selectType === 'single' ) {
					const { selectedItems } = state;

					return selectedItems?.length && selectedItems[ 0 ].label
						? selectedItems[ 0 ]?.label
						: 'Select an option';
				}

				return '';
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
			unselectDropdownItem: ( event: MouseEvent ) => {
				const context = getContext< DropdownContext >();

				const { selectedItems } = state;

				const {
					currentItem: { label, value },
				} = context;

				const items = selectedItems || [];
				const selectedItemIndex = items.findIndex(
					( item ) => item.value === value && item.label === label
				);

				if ( selectedItemIndex !== -1 ) {
					items.splice( selectedItemIndex, 1 );

					state.selectedItems = items || [];
					context.selectedItems = items || [];
				}

				event.stopPropagation();
			},
			selectDropdownItem: ( event: MouseEvent ) => {
				const context = getContext< DropdownContext >();
				const { selectedItems } = state;

				const {
					currentItem: { label, value },
				} = context;

				// check if item already selected
				const selectedItemIndex =
					selectedItems?.findIndex(
						( item ) => item.value === value && item.label === label
					) || -1;

				// if item already selected remove it from state:
				// else add it to state:
				if ( selectedItemIndex !== -1 ) {
					const items = selectedItems?.splice( selectedItemIndex, 1 );
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
