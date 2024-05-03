export type SearchAPIJSONType = {
	products: Array< SearchAPIProductType >;
};

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
	is_installable: boolean;
};

export interface Product {
	id?: number;
	slug?: string;
	position?: number;
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
	label?: string;
	primary_color?: string;
	group?: string;
	searchTerm?: string;
	category?: string;
	isInstallable: boolean;
}

export interface ProductTracksData {
	position?: number;
	label?: string;
	group?: string;
	searchTerm?: string;
	category?: string;
}

export enum ProductType {
	theme = 'theme',
	extension = 'extension',
	businessService = 'business-service',
}

export enum SearchResultType {
	theme = 'theme',
	extension = 'extension',
	businessService = 'business-service',
	all = 'all',
}
