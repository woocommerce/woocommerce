export type SearchAPIJSONType = {
	products: Array< SearchAPIProductType >;
	total_pages: number;
	total_products: number;
};

export type SearchAPIProductType = {
	title: string;
	image: string;
	type: ProductType;
	freemium_type: 'unset' | 'primary';
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
	featured_image: string;
	product_category: string;
	color: string;
	billing_period: string;
	billing_period_interval: number;
	currency: string;
	is_on_sale: boolean;
	regular_price: number;
};

export interface Product {
	id?: number;
	slug?: string;
	position?: number;
	title: string;
	image: string;
	type: ProductType;
	freemium_type?: 'unset' | 'primary';
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
	featuredImage?: string;
	productCategory?: string;
	color?: string;
	billingPeriod?: string;
	billingPeriodInterval?: number;
	currency: string;
	isOnSale: boolean;
	regularPrice: number;
}

export interface ProductTracksData {
	position?: number;
	label?: string;
	group?: string;
	group_id?: string;
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
