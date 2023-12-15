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
		placeholderText: string;
		isSelected: boolean;
	};

	actions: {
		toggleIsOpen: () => void;
		selectDropdownItem: ( event: MouseEvent ) => void;
		unselectDropdownItem: ( event: MouseEvent ) => void;
	};
};

store< DropdownStore >( 'woocommerce/interactivity-dropdown', {
	state: {
		get placeholderText(): string {
			const { selectType, selectedItems } =
				getContext< DropdownContext >();

			if ( selectType === 'single' ) {
				return selectedItems?.length && selectedItems[ 0 ].label
					? selectedItems[ 0 ]?.label
					: 'Select an option';
			}

			return '';
		},

		get isSelected(): boolean {
			const { currentItem, selectedItems } =
				getContext< DropdownContext >();

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

			const {
				currentItem: { label, value },
				selectedItems,
			} = context;

			const items = selectedItems || [];
			const selectedItemIndex = items.findIndex(
				( item ) => item.value === value && item.label === label
			);

			if ( selectedItemIndex !== -1 ) {
				items.splice( selectedItemIndex, 1 );
			}

			event.stopPropagation();
		},
		selectDropdownItem: ( event: MouseEvent ) => {
			const context = getContext< DropdownContext >();

			const {
				currentItem: { label, value },
				selectedItems,
			} = context;

			// check if item already selected
			const selectedItemIndex =
				selectedItems?.findIndex(
					( item ) => item.value === value && item.label === label
				) || -1;

			// if item already selected remove it from state:
			// else add it to state:
			if ( selectedItemIndex !== -1 ) {
				selectedItems?.splice( selectedItemIndex, 1 );
			} else {
				selectedItems.push( {
					label,
					value,
				} );
			}

			context.isOpen = false;

			event.stopPropagation();
		},
	},
} );
