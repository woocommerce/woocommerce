export type SearchAPIProductType = {
	title: string;
	image: string;
	type: ProductType;
	excerpt: string;
	link: string;
	demo_url: string;
	price: string;
	raw_price: number;
	hash: string;
	slug: string;
	id: number;
	rating: number | null;
	reviews_count: number | null;
	vendor_name: string;
	vendor_url: string;
	icon: string;
};

export interface Product {
	id?: number;
	title: string;
	image: string;
	type: ProductType;
	description: string;
	vendorName: string;
	vendorUrl: string;
	icon: string;
	url: string;
	price: number;
	productType?: string;
	averageRating?: number | null;
	reviewsCount?: number | null;
}

export enum ProductType {
	theme = 'theme',
	extension = 'extension',
}

export enum SearchResultType {
	theme = 'theme',
	extension = 'extension',
	all = 'all',
}
