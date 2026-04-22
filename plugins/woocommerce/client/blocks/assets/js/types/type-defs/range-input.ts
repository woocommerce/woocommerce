export interface RangeInputContext {
	min: number;
	max: number;
	currentMin: number;
	currentMax: number;
	step?: number;
	changeAction: string;
	storeNamespace: string;
	isLoading?: boolean;
}

export type RangeInputBlockContext = {
	'woocommerce/rangeInput': RangeInputContext;
};
