/**
 * External dependencies
 */
import { getElement, getContext, store } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */

export type ChipsContext = {
	items: {
		id: string;
		label: string;
		value: string;
		checked: boolean;
	}[];
	showAll: boolean;
};

store( 'woocommerce/product-filter-chips', {
	actions: {
		showAllItems: () => {
			const context = getContext< ChipsContext >();
			context.showAll = true;
		},

		selectItem: () => {
			const { ref } = getElement();
			const value = ref.getAttribute( 'value' );

			if ( ! value ) return;

			const context = getContext< ChipsContext >();

			context.items = context.items.map( ( item ) => {
				if ( item.value.toString() === value ) {
					return {
						...item,
						checked: ! item.checked,
					};
				}
				return item;
			} );
		},
	},
} );
