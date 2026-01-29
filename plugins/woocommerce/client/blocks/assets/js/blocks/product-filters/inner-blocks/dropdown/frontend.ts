/**
 * External dependencies
 */
import { getContext, store, getElement } from '@wordpress/interactivity';

export type DropdownContext = {
	defaultPlaceholder: string;
	item: {
		label: string;
		value: string;
		type: string;
	};
	selectedItems: {
		label: string | null;
		value: string | null;
		type: string | null;
	}[];
	isOpen: boolean;
};

type DropdownStore = {
	state: {
		placeholderText: string;
	};

	actions: {
		toggleIsOpen: () => void;
		selectDropdownItem: ( event: MouseEvent ) => void;
		unselectDropdownItem: ( event: MouseEvent ) => void;
		handleClickOutside: ( event: MouseEvent ) => void;
	};
};

store< DropdownStore >( 'woocommerce/product-filters', {
	state: {
		get placeholderText(): string {
			const { selectedItems, defaultPlaceholder } =
				getContext< DropdownContext >();

			if ( selectedItems.length === 0 ) {
				return defaultPlaceholder;
			}

			return '';
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
				item: { label, value },
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
				item: { label, value, type },
				selectedItems,
			} = context;

			// check if item already selected
			const selectedItemIndex = selectedItems.findIndex(
				( item ) => item.value === value && item.label === label
			);

			if ( selectedItemIndex !== -1 ) {
				selectedItems.splice( selectedItemIndex, 1 );
			}

			if ( selectedItemIndex === -1 ) {
				selectedItems.push( {
					label,
					value,
					type,
				} );
			}

			context.isOpen = false;
			event.stopPropagation();
		},
		handleClickOutside( event: MouseEvent ) {
			const context = getContext< DropdownContext >();

			if ( ! context.isOpen ) {
				return;
			}

			const element = getElement();
			const dropdownElement = element?.ref;

			const target = event.target;
			if (
				dropdownElement &&
				target instanceof Node &&
				! dropdownElement.contains( target )
			) {
				context.isOpen = false;
			}
		},
	},
} );
