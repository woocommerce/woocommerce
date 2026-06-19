export const optionsStore = 'wc/admin/options';
export const productsStore = 'wc/admin/products';

export type Product = {
	attributes?: {
		name: string;
		options: string[];
	}[];
	dimensions?: {
		height: string;
		length: string;
		width: string;
	};
	weight?: string;
};
