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
	defaultPlaceholder: string;
	item: {
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
			const { selectType, selectedItems, defaultPlaceholder } =
				getContext< DropdownContext >();

			if ( selectType === 'single' ) {
				return selectedItems?.length && selectedItems[ 0 ].label
					? selectedItems[ 0 ]?.label
					: defaultPlaceholder;
			} else if (
				selectType === 'multiple' &&
				selectedItems.length === 0
			) {
				return defaultPlaceholder;
			}

			return '';
		},

		get isSelected(): boolean {
			const { item, selectedItems } = getContext< DropdownContext >();

			return selectedItems.some( ( i ) => {
				return i.value === item.value && i.label === item.label;
			} );
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
				item: { label, value },
				selectedItems,
			} = context;

			// check if item already selected
			const selectedItemIndex = selectedItems.findIndex(
				( item ) => item.value === value && item.label === label
			);

			if ( selectedItemIndex !== -1 ) {
				selectedItems.splice( selectedItemIndex, 1 );
			}

			if ( context.selectType === 'single' && selectedItemIndex === -1 ) {
				selectedItems.splice( 0, 1, { label, value } );
			} else if ( selectedItemIndex === -1 ) {
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
