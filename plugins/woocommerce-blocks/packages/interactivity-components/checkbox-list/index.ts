/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import { HTMLElementEvent } from '../../../assets/js/types';

/**
 * Internal dependencies
 */
import './style.scss';

export type CheckboxListContext = {
	items: {
		id: string;
		label: string;
		value: string;
		checked: boolean;
	}[];
	initialItemsCount: number;
	showAll?: boolean;
};

store( 'woocommerce/interactivity-checkbox-list', {
	state: {
		get visibleItems() {
			const context = getContext< CheckboxListContext >();
			console.log( context );
			if ( ! context?.showAll && Array.isArray( context.items ) ) {
				return context.items.slice( 0, context.initialItemsCount );
			}
			return context.items;
		},
	},
	actions: {
		showAllItems: () => {
			const context = getContext< CheckboxListContext >();
			context.showAll = true;
		},
		selectCheckboxItem: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			const context = getContext< CheckboxListContext >();
			const value = event.target.value;

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
