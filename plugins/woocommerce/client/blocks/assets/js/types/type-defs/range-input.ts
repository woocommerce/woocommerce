export interface RangeInputContext {
	min: number;
	max: number;
	currentMin: number;
	currentMax: number;
	step?: number;
	changeAction: string;
	storeNamespace: string;
}

export type RangeInputBlockContext = {
	'woocommerce/rangeInput': RangeInputContext;
};
