/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { ProductFiltersContext } from '../../types';
import type { ProductFiltersStore } from '../../frontend';
import type {
	RemovableItem,
	RemovableItemsParentStore,
} from '../../../../types/type-defs/removable-items';
import { PRODUCT_FILTERS_STORE_NAME } from '../../constants';

type RemovableItemContext = {
	item: RemovableItem;
};

const activeFiltersStore = {
	state: {
		get removableItems(): RemovableItem[] {
			return state.activeFilters.map( ( f ) => ( {
				id: f.type + '_' + f.value,
				type: f.type,
				value: f.value,
				label: f.activeLabel,
			} ) );
		},
		get removeItemLabel() {
			const { item } = getContext< RemovableItemContext >();
			const { removeLabelTemplate } = getConfig();
			const template =
				typeof removeLabelTemplate === 'string'
					? removeLabelTemplate
					: '{{label}}';
			const label = typeof item?.label === 'string' ? item.label : '';
			return template.replace( '{{label}}', label );
		},
		get hasActiveFilters(): boolean {
			return state.activeFilters.length > 0;
		},
	},
	actions: {
		removeAll: () => {
			const context = getContext< ProductFiltersContext >();
			context.activeFilters = [];
			void actions.navigate();
		},
		remove: () => {
			const { item } = getContext< RemovableItemContext >();
			actions.removeActiveFiltersBy(
				( filter ) =>
					filter.value === item.value && filter.type === item.type
			);
			void actions.navigate();
		},
	},
};

// Compile-time protocol conformance check.
// eslint-disable-next-line @typescript-eslint/no-unused-expressions
activeFiltersStore satisfies RemovableItemsParentStore;

const { state, actions } = store<
	ProductFiltersStore & typeof activeFiltersStore
>( PRODUCT_FILTERS_STORE_NAME, activeFiltersStore );
