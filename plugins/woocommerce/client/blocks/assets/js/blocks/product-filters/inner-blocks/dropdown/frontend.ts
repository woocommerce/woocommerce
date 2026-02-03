/**
 * External dependencies
 */
import { getContext, store, getElement } from '@wordpress/interactivity';

type DropdownItem = {
	label: string;
	value: string;
	type: string;
};

export type DropdownContext = {
	defaultPlaceholder: string;
	item: DropdownItem;
	selectedItems: DropdownItem[];
	isOpen: boolean;
	searchQuery: string;
	items: DropdownItem[];
};

type DropdownStore = {
	state: {
		placeholderText: string;
		isItemFiltered: boolean;
	};

	actions: {
		toggleIsOpen: () => void;
		selectDropdownItem: ( event: MouseEvent ) => void;
		unselectDropdownItem: ( event: MouseEvent ) => void;
		handleClickOutside: ( event: MouseEvent ) => void;
		handleSearchInput: ( event: Event ) => void;
		handleSearchInputClick: ( event: MouseEvent ) => void;
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
		get isItemFiltered(): boolean {
			const context = getContext< DropdownContext >();
			const { searchQuery, item } = context;

			if ( ! searchQuery || searchQuery.trim() === '' ) {
				return false;
			}

			const query = searchQuery.toLowerCase();
			const label = item?.label?.toLowerCase() || '';

			return ! label.includes( query );
		},
	},
	actions: {
		toggleIsOpen: () => {
			const context = getContext< DropdownContext >();
			context.isOpen = ! context.isOpen;

			// Reset search query when closing
			if ( ! context.isOpen ) {
				context.searchQuery = '';
			}
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
				context.searchQuery = '';
			}
		},
		handleSearchInput( event: Event ) {
			const context = getContext< DropdownContext >();
			const input = event.target as HTMLInputElement;
			context.searchQuery = input.value;
		},
		handleSearchInputClick( event: MouseEvent ) {
			event.stopPropagation();
		},
	},
} );
