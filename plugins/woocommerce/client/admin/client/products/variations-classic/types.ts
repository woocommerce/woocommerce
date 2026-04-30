export interface VariationAttribute {
	id: number;
	name: string;
	option: string;
}

export interface VariationImage {
	id: number;
	src: string;
	alt: string;
}

export interface Variation {
	id: number;
	sku: string;
	price: string;
	regular_price: string;
	sale_price: string;
	status: 'publish' | 'private';
	virtual: boolean;
	downloadable: boolean;
	manage_stock: boolean;
	stock_quantity: number | null;
	stock_status: 'instock' | 'outofstock' | 'onbackorder';
	image: VariationImage | null;
	attributes: VariationAttribute[];
}

export interface WcVariationsClassicSettings {
	productId: number;
	nonce: string;
	restUrl: string;
}

declare global {
	interface Window {
		wcVariationsClassicSettings: WcVariationsClassicSettings;
	}
}
