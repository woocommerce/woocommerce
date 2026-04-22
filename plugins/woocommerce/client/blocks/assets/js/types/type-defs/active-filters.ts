export interface ActiveFilterItem {
	type: string;
	value: string;
	activeLabel: string;
}

export interface ActiveFiltersContext {
	activeFilters: ActiveFilterItem[];
	removeAction: string;
	storeNamespace: string;
}

export type ActiveFiltersBlockContext = {
	'woocommerce/activeFilters': ActiveFiltersContext;
};
